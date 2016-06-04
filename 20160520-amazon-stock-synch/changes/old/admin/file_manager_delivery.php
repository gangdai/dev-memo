<?php
/*
  $Id: file_manager.php 1744 2007-12-21 02:22:21Z hpdl $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');


 /* ** GOOGLE CHECKOUT **/
  define('GC_STATE_NEW', 100);
  define('GC_STATE_PROCESSING', 101);
  define('GC_STATE_SHIPPED', 102);
  define('GC_STATE_REFUNDED', 103);
  define('GC_STATE_SHIPPED_REFUNDED', 104);
  define('GC_STATE_CANCELED', 105);

  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_QUICKSHIP);
  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

  $orders_statuses = array();
  $orders_status_array = array();
  $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = 1");
  while ($orders_status = tep_db_fetch_array($orders_status_query)) {
    $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                               'text' => $orders_status['orders_status_name']);
    $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
  }

  function google_checkout_state_change($check_status, $status, $oID,
                                              $cust_notify, $notify_comments, $qtracking='') {
      global $db,$messageStack, $orders_statuses;

      define('API_CALLBACK_ERROR_LOG',
                       DIR_FS_CATALOG. "/googlecheckout/logs/response_error.log");
      define('API_CALLBACK_MESSAGE_LOG',
                       DIR_FS_CATALOG . "/googlecheckout/logs/response_message.log");

      include_once(DIR_FS_CATALOG.'/includes/modules/payment/googlecheckout.php');
      include_once(DIR_FS_CATALOG.'/googlecheckout/library/googlerequest.php');

      $googlepayment = new googlecheckout();
     
      $Grequest = new GoogleRequest($googlepayment->merchantid,
                                    $googlepayment->merchantkey,
                                    MODULE_PAYMENT_GOOGLECHECKOUT_MODE==
                                      'https://sandbox.google.com/checkout/'
                                      ?"sandbox":"production",
                                    DEFAULT_CURRENCY);
      $Grequest->SetLogFiles(API_CALLBACK_ERROR_LOG, API_CALLBACK_MESSAGE_LOG);


      $google_answer = tep_db_fetch_array(tep_db_query("SELECT go.google_order_number, go.order_amount, o.customers_email_address, gc.buyer_id, o.customers_id
                                      FROM " . $googlepayment->table_order . " go
                                      inner join " . TABLE_ORDERS . " o on go.orders_id = o.orders_id
                                      inner join " . $googlepayment->table_name . " gc on gc.customers_id = o.customers_id
                                      WHERE go.orders_id = '" . (int)$oID ."'
                                      group by o.customers_id order by o.orders_id desc"));

      $google_order = $google_answer['google_order_number']; 
      $amount = $google_answer['order_amount']; 

    // If status update is from Google New -> Google Processing on the Admin UI
    // this invokes the processing-order and charge-order commands
    // 1->Google New, 2-> Google Processing
    if($check_status['orders_status'] == GC_STATE_NEW
               && $status == GC_STATE_PROCESSING && $google_order != '') {
      list($curl_status,) = $Grequest->SendChargeOrder($google_order, $amount);
      if($curl_status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_CHARGE_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_CHARGE_ORDER, 'success');
      }
      list($curl_status,) = $Grequest->SendProcessOrder($google_order);
      if($curl_status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_PROCESS_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_PROCESS_ORDER, 'success');
      }
    }
   
    // If status update is from Google Processing or Google Refunded -> Google Shipped on the Admin UI
    // this invokes the deliver-order and archive-order commands
    // 2->Google Processing or Google Refunded, 3-> Google Shipped (refunded)
    else if(($check_status['orders_status'] == GC_STATE_PROCESSING
            || $check_status['orders_status'] == GC_STATE_REFUNDED)
                 && ($status == GC_STATE_SHIPPED || $status == GC_STATE_SHIPPED_REFUNDED )
                 && $google_order != '') {
      $carrier = $tracking_no = "";
      // Add tracking Data
      if(tep_not_null($qtracking)) {
        $carrier = 'Other';
        $tracking_no = "  ".$qtracking;
        $comments = GOOGLECHECKOUT_STATE_STRING_TRACKING ."\n" .
                    GOOGLECHECKOUT_STATE_STRING_TRACKING_CARRIER . $carrier ."\n" .
                    GOOGLECHECKOUT_STATE_STRING_TRACKING_NUMBER . $tracking_no . "";
        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
                    (orders_id, orders_status_id, date_added, customer_notified, comments)
                    values ('" . (int)$oID . "',
                    '" . tep_db_input(($check_status['orders_status']==GC_STATE_REFUNDED
                                      ?GC_STATE_SHIPPED_REFUNDED:GC_STATE_SHIPPED)) . "',
                    now(),
                    '" . tep_db_input($cust_notify) . "',
                    '" . tep_db_input($comments)  . "')");
        
      }
     
      list($curl_status,) = $Grequest->SendDeliverOrder($google_order, $carrier,
                              $tracking_no, ($cust_notify==1)?"true":"false");
      if($curl_status != 200) {
        //$messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_DELIVER_ORDER, 'error');
        echo "Order# $oID " . GOOGLECHECKOUT_ERR_SEND_DELIVER_ORDER . '-';
      }
      else {
        //$messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_DELIVER_ORDER, 'success');
        echo "Order# $oID " . GOOGLECHECKOUT_SUCCESS_SEND_DELIVER_ORDER . '-';
      }
      list($curl_status,) = $Grequest->SendArchiveOrder($google_order);
      if($curl_status != 200) {
        //$messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_ARCHIVE_ORDER, 'error');
        echo GOOGLECHECKOUT_ERR_SEND_ARCHIVE_ORDER . '-';
      }
      else {
        //$messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_ARCHIVE_ORDER, 'success');
        echo GOOGLECHECKOUT_SUCCESS_SEND_ARCHIVE_ORDER . '-';
      }
    }
    // If status update is to Google Canceled on the Admin UI
    // this invokes the cancel-order and archive-order commands
    else if($check_status['orders_status'] != GC_STATE_CANCELED &&
            $status == GC_STATE_CANCELED && $google_order != '') {
      if($check_status['orders_status'] != GC_STATE_NEW){
        list($curl_status,) = $Grequest->SendRefundOrder($google_order, 0,
                                        GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED
                                        );
        if($curl_status != 200) {
          $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_REFUND_ORDER, 'error');
        }
        else {
          $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_REFUND_ORDER, 'success');         
        }
      }
      else {
        // Tell google witch is the OSC's internal order Number       
        list($curl_status,) = $Grequest->SendMerchantOrderNumber($google_order, $oID);
        if($curl_status != 200) {
          $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_MERCHANT_ORDER_NUMBER, 'error');
        }
        else {
          $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_MERCHANT_ORDER_NUMBER, 'success');         
        }
      }
//    Is the order is not archive, I do it
      if($check_status['orders_status'] != GC_STATE_SHIPPED
         && $check_status['orders_status'] != GC_STATE_SHIPPED_REFUNDED){
        list($curl_status,) = $Grequest->SendArchiveOrder($google_order);
        if($curl_status != 200) {
          $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_ARCHIVE_ORDER, 'error');
        }
        else {
          $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_ARCHIVE_ORDER, 'success');         
        }
      }
//    Cancel the order
      list($curl_status,) = $Grequest->SendCancelOrder($google_order,
                                      GOOGLECHECKOUT_STATE_STRING_ORDER_CANCELED,
                                      $notify_comments);
      if($curl_status != 200) {
        $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_CANCEL_ORDER, 'error');
      }
      else {
        $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_CANCEL_ORDER, 'success');         
      }
    }
    else if($google_order != ''
            && $check_status['orders_status'] != $status){
      $statuses = array();
      foreach($orders_statuses as $status_array){
        $statuses[$status_array['id']] = $status_array['text'];
      }
      $messageStack->add_session( sprintf(GOOGLECHECKOUT_ERR_INVALID_STATE_TRANSITION,
                                  $statuses[$check_status['orders_status']],
                                  $statuses[$status],
                                  $statuses[$check_status['orders_status']]),
                                  'error');
    }   
   
    // Send Buyer's message
    if($cust_notify==1 && isset($notify_comments) && !empty($notify_comments)) {
      $cust_notify_ok = '0';     
      if(!((strlen(htmlentities(strip_tags($notify_comments))) > GOOGLE_MESSAGE_LENGTH)
              && MODULE_PAYMENT_GOOGLECHECKOUT_USE_CART_MESSAGING=='True')){
   
        list($curl_status,) = $Grequest->sendBuyerMessage($google_order,
                             $notify_comments, "true");
        if($curl_status != 200) {
          $messageStack->add_session(GOOGLECHECKOUT_ERR_SEND_MESSAGE_ORDER, 'error');
          $cust_notify_ok = '0';
        }
        else {
          $messageStack->add_session(GOOGLECHECKOUT_SUCCESS_SEND_MESSAGE_ORDER, 'success');         
          $cust_notify_ok = '1';
        }
        if(strlen(htmlentities(strip_tags($notify_comments))) > GOOGLE_MESSAGE_LENGTH) {
          $messageStack->add_session(
          sprintf(GOOGLECHECKOUT_WARNING_CHUNK_MESSAGE, GOOGLE_MESSAGE_LENGTH), 'warning');         
        }
      }
      // Cust notified
      return $cust_notify_ok;
    }
    // Cust notified
    return '0';
  }
  // ** END GOOGLE CHECKOUT **

function quickship($oID, $status='3', $trackingno='') {
  global $language, $orders_status_array;
  $order_updated = false;
  //20141016
  if ((int)$oID < 250000 || (int)$oID > 500000) return;
  //20141016

  //My modification
  /*
  $num_rows = tep_db_num_rows(tep_db_query("select google_order_number from google_orders where orders_id= ". (int)$oID));
  if($num_rows != 0) {
    $status=GC_STATE_SHIPPED;
  }
  */
        //20160217
        $is_updated_q = tep_db_query("select orders_status_history_id from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$oID . "' and tracking_no like '" . tep_db_prepare_input($trackingno) . "'");
        if (tep_db_num_rows($is_updated_q)) return;
        //20160217

  $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
  if (!tep_db_num_rows($check_status_query)) { return;}
  $check_status = tep_db_fetch_array($check_status_query);
 
  if (tep_not_null($status)) {
     //if ($check_status['orders_status'] !== $status)
     //{
      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '". (int)$status . "', last_modified = now() where orders_id = '" . (int)$oID . "'");
      //$customer_notified = '0';
// ** GOOGLE CHECKOUT **
      //chdir("./..");
      //require_once(DIR_WS_LANGUAGES . $language . '/modules/payment/googlecheckout.php');
      //$payment_value= MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_TITLE;
      //$num_rows = tep_db_num_rows(tep_db_query("select google_order_number from google_orders where orders_id= ". (int)$oID));
      //chdir("./admin-cart");
      //chdir("." . substr(DIR_WS_ADMIN, 0,-1));

/*
      if($num_rows != 0)
      {
        $customer_notified = google_checkout_state_change($check_status, $status, $oID, 1, '', $trackingno);
      }
*/

      $customer_notified = isset($customer_notified)?$customer_notified:'0';
// ** END GOOGLE CHECKOUT **

      $notify_comments = '';
      if (tep_not_null($trackingno)) {
          $delivery_class_query = tep_db_query("select title from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$oID . "' and class = 'ot_shipping'");
          $delivery_class_result = tep_db_fetch_array($delivery_class_query);
          ////My modification check postcode for interlink UK shipping

          if ($delivery_class_result['title'] == LOWCOST_LABEL_STANDARD || $delivery_class_result['title'] == INTERLINK_LABEL) {
          	$delivery_spec = "Dispatched with " . substr($delivery_class_result['title'],0,-1) . ". It can be tracked at www.yodel.co.uk/track/, the tracking number is: " . $trackingno;
          }
          elseif ($delivery_class_result['title'] == INTERLINK_LABEL) {
              $delivery_spec = "Dispatched with " . substr($delivery_class_result['title'],0,-1) . ". It can be tracked at www.interlinkexpress.com, the tracking number is: " . $trackingno;
          }
          elseif ($delivery_class_result['title'] == UPS_LABEL) {
              $delivery_spec = "Dispatched with " . substr($delivery_class_result['title'],0,-1) . ". It can be tracked at www.ups.com/content/gb/en/index.jsx?WT.svl=BrndMrk, the tracking number is: " . $trackingno;
          }
          elseif (preg_match('/UKMail/i',$delivery_class_result['title'])) {
              //My modification - temp
              if (strlen($trackingno) ==23)
                $trackingno = substr(substr($trackingno, 6), 0, -3);

              $delivery_spec = "Dispatched with " . substr($delivery_class_result['title'],0,-1) . ". It can be tracked at http://www.iconsign.biz, consignment number: " . $trackingno;
          }
          ////My modification check postcode for UK fastway shipping
          else {
            $delivery_spec = "Dispatched with " . substr($delivery_class_result['title'],0,-1) . ". It can be tracked at www.royalmail.com, the tracking number is: " . $trackingno;
          }
          //$delivery_spec = "Parcel tracking info: " . $trackingno;
          $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, 'blank' . "\n" . $delivery_spec) . "\n\n";
      }

// ** GOOGLE CHECKOUT **
/*
      $force_email = false;
      if($num_rows != 0 && (strlen(htmlentities(strip_tags($notify_comments))) > GOOGLE_MESSAGE_LENGTH && MODULE_PAYMENT_GOOGLECHECKOUT_USE_CART_MESSAGING == 'True'))
      {
        $force_email = true;
        //$messageStack->add_session(GOOGLECHECKOUT_WARNING_SYSTEM_EMAIL_SENT, 'warning');         
      }
*/
      //if($num_rows == 0 || $force_email) {//send emails, not a google order or configured to use both messaging systems
      if($notify_comments) {
            //// PWA
            $pwa_check_query= tep_db_query("select purchased_without_account from " . TABLE_ORDERS . " where orders_id = '" . tep_db_input($oID) . "'");
            $pwa_check= tep_db_fetch_array($pwa_check_query);
            if ($pwa_check['purchased_without_account'] != '1')
            {
               $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            }
            else
            {
               $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
            }
            tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1. $oID . EMAIL_TEXT_SUBJECT_2 . $orders_status_array[$status], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

            $customer_notified = '1';
            //// PWA
      }//send extra emails
       
      tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, tracking_no) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', NULL, '" . tep_db_input($trackingno) . "')");
      $order_updated = true;
     //}
  }
}//function quickship
















  if (!tep_session_is_registered('current_path')) {
    $current_path = DIR_FS_DOCUMENT_ROOT;
    tep_session_register('current_path');
  }

  if (isset($HTTP_GET_VARS['goto'])) {
    $current_path = $HTTP_GET_VARS['goto'];
    tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));
  }

  if (strstr($current_path, '..')) $current_path = DIR_FS_DOCUMENT_ROOT;

  if (!is_dir($current_path)) $current_path = DIR_FS_DOCUMENT_ROOT;

  //if (!preg_match('/^/' . DIR_FS_DOCUMENT_ROOT, $current_path)) $current_path = DIR_FS_DOCUMENT_ROOT;
  
  //My modification - $current_path
  $current_path = DIR_FS_ADMIN . 'includes/modules/batch_print/temp_pdf/deliveries';
  //echo $current_path;

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'reset':
        tep_session_unregister('current_path');
        tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));
        break;
      case 'deleteconfirm':
        if (strstr($HTTP_GET_VARS['info'], '..')) tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));

        tep_remove($current_path . '/' . $HTTP_GET_VARS['info']);
        if (!$tep_remove_error) tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));
        break;
      case 'insert':
        if (isset($HTTP_POST_VARS['folder_name']) && tep_not_null(basename($HTTP_POST_VARS['folder_name'])) && mkdir($current_path . '/' . basename($HTTP_POST_VARS['folder_name']), 0777)) {
          tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode($HTTP_POST_VARS['folder_name'])));
        }
        break;
      case 'save':
        if (isset($HTTP_POST_VARS['filename']) && tep_not_null(basename($HTTP_POST_VARS['filename']))) {
          if (is_writeable($current_path) && ($fp = fopen($current_path . '/' . basename($HTTP_POST_VARS['filename']), 'w+'))) {
            fputs($fp, stripslashes($HTTP_POST_VARS['file_contents']));
            fclose($fp);
            tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode(basename($HTTP_POST_VARS['filename']))));
          }
        } else {
          $action = 'new_file';
          $directory_writeable = true;
          $messageStack->add(ERROR_FILENAME_EMPTY, 'error');
        }
        break;
      case 'processuploads':
//echo "<br /><br /><br /><br />" . print_r($HTTP_POST_FILES);exit;
        //for ($i=1; $i<6; $i++) {
          if (is_uploaded_file($HTTP_POST_FILES['file_']['tmp_name'])) {
            new upload('file_', $current_path);

            if ($HTTP_POST_VARS['filetype'] ==1) {
              // create an array to hold directory list
                  $results = array();
                  // create a handler for the directory
                  $handler = opendir($current_path);
                  // open directory and walk through the filenames
                  while ($file = readdir($handler)) {
                    // if file isn't this directory or its parent, add it to the results
                    if ($file != "." && $file != "..") {
                      $results[] = $file;
                      if (preg_match('/despatch/i', $file)) {
              //          echo "<br /><b>" . substr($file, 0,5) . "</b><br />";

                        $file_handle = fopen($current_path . "/" . $file, "r");
                        while (!feof($file_handle) ) {
                          $line_of_text = fgetcsv($file_handle, 4096);
                          
                          if (!tep_not_null($line_of_text[0])) continue;
                          //quickship($line_of_text[22], '3', $line_of_text[0]);
                          $line_of_text[0] = preg_replace("/\"/i", "", $line_of_text[0]);
                          $line_of_text[1] = preg_replace("/\"/i", "", $line_of_text[1]);
                          if (!is_numeric($line_of_text[0])) continue;
                          //temp remove tracking number with AAA
                          if (preg_match("/aaa/i", $line_of_text[1])) $line_of_text[1] ="";
                          quickship($line_of_text[0], '3', $line_of_text[1]);
                        }
                        fclose($file_handle);
                      }
                      elseif (preg_match('/EXPORT.CSV/i', $file)) {
                        //echo "<br /><b>" . substr($file, 0,5) . "</b><br />";
              
                        $file_handle = fopen($current_path . "/" . $file, "r");
                        while (!feof($file_handle) ) {
                          $line_of_text = fgetcsv($file_handle, 4096);
                          if (!tep_not_null($line_of_text[3])) continue;
                          //quickship($line_of_text[3], '3', substr($line_of_text[2], 1, -1));
                          quickship((int)$line_of_text[3], '3', $line_of_text[2]);
              //            echo substr($line_of_text[2], 1, -1) ."=". $line_of_text[3] . "<br />";
                        }
                        fclose($file_handle);
                      }
                      elseif (preg_match('/lcp_export_/i', $file)) {
                        $file_handle = fopen($current_path . "/" . $file, "r");
                        while (!feof($file_handle) ) {
                          $line_of_text = fgetcsv($file_handle, 4096);
                          if (!tep_not_null($line_of_text[14])) continue;
                          quickship((int)$line_of_text[14], '3', $line_of_text[19]);
                        }
                        fclose($file_handle);
                      }

                      tep_remove($current_path . '/' . $file);
                    }
                  }
                  // tidy up: close the handler
                  closedir($handler);
                  // done!
                  //return $results;
                  //echo print_r($results);
            }
            elseif ($HTTP_POST_VARS['filetype'] ==2) { //amazon/ebay -> royalmail
                  $handler = opendir($current_path);
                  // open directory and walk through the filenames
                  while ($file = readdir($handler)) {
                    if ($file != "." && $file != "..") {
                      if ($file == $HTTP_POST_FILES['file_']['name']) {
                        if ($HTTP_POST_VARS['servicetype'] ==1) {
                          $servicetype = "FIRST";
                        }
                        else {
                          $servicetype = "SECOND";
                        }
                        if ($HTTP_POST_VARS['sizetype'] ==1) {
                        	$sizetype = "P";
                        	$servicetype = "STANDARD";
                        }
                        else {
                        	$sizetype = "F";
                        }
                      	$counter=0;
                      	$delimiter=",";
                        $file_handle = fopen($current_path . "/" . $file, "r");

                        $downloadfile_name = date("Ymd_His") . "_rm.csv";
                        $downloadfile = fopen($current_path . "/" . $downloadfile_name, "w") or die("Unable to open file!");
                        fwrite($downloadfile, "Reference,Recipient,Recipient Address line 1,Recipient Address line 2,Recipient Address line 3,Recipient Post Town,Recipient Postcode,Recipient Country Code,Service Reference,Service,Service Enhancement,Service Format,Signature,Items,Weight (kgs),Recipient Complementary Name,Safe Place,Recipient Tel #,Recipient Email Address" . "\r\n");

                        $order_array = array();
                        while (!feof($file_handle) ) {
                        	$counter++;
                          $line_of_text = fgets($file_handle, 4096);
                          if ($counter ==1) continue;

                          //$splitcontents = explode("\t", $line_of_text);
                          $splitcontents = str_getcsv($line_of_text, "\t", '"');
                          //check amazon/ebay
                          if (sizeof($splitcontents) > 1 && sizeof($splitcontents) == 24) { //amazon
                          	if (in_array(trim($splitcontents[0]), $order_array)) continue;
                            $output = array();
                            $output[] = str_replace(",", " ", trim($splitcontents[0]));
                            $output[] = str_replace(",", " ", trim($splitcontents[16]));
                            $output[] = str_replace(",", " ", trim($splitcontents[17]));
                            $output[] = str_replace(",", " ", trim($splitcontents[18]));
                            $output[] = str_replace(",", " ", trim(trim($splitcontents[19]) . " " . trim($splitcontents[21])));
                            $output[] = str_replace(",", " ", trim($splitcontents[20]));
                            $output[] = str_replace(",", " ", trim($splitcontents[22]));
                            $output[] = "GB"; //str_replace(",", " ", trim($splitcontents[23]));
                            $output[] = 1;
                            $output[] = $servicetype;
                            $output[] = "";
                            $output[] = $sizetype;
                            $output[] = "";
                            $output[] = 1;
                            $output[] = 60;
                            $output[] = "";
                            $output[] = "";
                            if (preg_match('/^07/', trim($splitcontents[24])) || preg_match('/^\+447/', trim($splitcontents[24])) || preg_match('/^00447/', trim($splitcontents[24]))) {
                            	$output[] = str_replace(' ', '', trim($splitcontents[24]));
                            }
                            else {
    	                        $output[] = "";
                            }
                            $output[] = ""; //email str_replace(",", " ", trim($splitcontents[4]));

                            $dataRowString = implode($delimiter, $output);
                            fwrite($downloadfile, $dataRowString . "\r\n");
                            $order_array[] = trim($splitcontents[0]);
                          }
                          else { //ebay export 48
                          	if (sizeof($splitcontents) < 48 ) {
                          		$splitcontents = str_getcsv($line_of_text, ",", '"');
                          	}
                          	if (sizeof($splitcontents) == 48) {
                              if (in_array(trim($splitcontents[0]), $order_array)) continue;
                              elseif (empty($splitcontents[0])) continue; //no order id
                              elseif (empty($splitcontents[41])) continue; ////no address1
                              $output = array();
                              $output[] = str_replace(",", " ", trim($splitcontents[0])); //Reference
                              $output[] = str_replace(",", " ", trim($splitcontents[2])); //Recipient
                              $output[] = str_replace(",", " ", trim($splitcontents[41])); //Recipient Address line 1
                              $output[] = str_replace(",", " ", trim($splitcontents[42])); //Recipient Address line 2
                              $output[] = str_replace(",", " ", trim($splitcontents[44])); //Recipient Address line 3
                              $output[] = str_replace(",", " ", trim($splitcontents[43])); //Recipient Post Town
                              $output[] = str_replace(",", " ", trim($splitcontents[45])); //Recipient Postcode
                              $output[] = "GB"; //Recipient Country Code
                              $output[] = 1;
                              $output[] = $servicetype;
                              $output[] = "";
                              $output[] = $sizetype;
                              $output[] = "";
                              $output[] = 1;
                              $output[] = 60;
                              $output[] = "";
                              $output[] = ""; //email str_replace(",", " ", trim($splitcontents[4]));
                              if (preg_match('/^07/', trim($splitcontents[3])) || preg_match('/^\+447/', trim($splitcontents[3])) || preg_match('/^00447/', trim($splitcontents[3]))) {
                                $output[] = str_replace(' ', '', trim($splitcontents[3]));
                              }
                              else {
                                $output[] = "";
                              }

                              $dataRowString = implode($delimiter, $output);
                              fwrite($downloadfile, $dataRowString . "\r\n");
                              $order_array[] = trim($splitcontents[0]);
                            }
                          }
                        }
                        fclose($downloadfile);
                        fclose($file_handle);

                      }
                      tep_remove($current_path . '/' . $file);
                    }
                  }
                  closedir($handler);
            }
            elseif ($HTTP_POST_VARS['filetype'] ==3) { //lcp
                  $handler = opendir($current_path);
                  // open directory and walk through the filenames
                  while ($file = readdir($handler)) {
                    if ($file != "." && $file != "..") {
                      if ($file == $HTTP_POST_FILES['file_']['name']) {
                      	$counter=0;
                      	$delimiter=",";
                        $file_handle = fopen($current_path . "/" . $file, "r");

                        $downloadfile_name = date("Ymd_His") . "_lcp.csv";
                        $downloadfile = fopen($current_path . "/" . $downloadfile_name, "w") or die("Unable to open file!");
                        fwrite($downloadfile, "Contact Name,Department,Phone Number,Email Address,Address 1,Address 2,Town,County,Postcode,Customer Ref,Internal Ref,Custom Field,Order ID,Items,Service ID" . "\r\n");
                        $order_array = array();
                        while (!feof($file_handle)) {
                        	$counter++;
                          $line_of_text = fgets($file_handle, 4096);
                          if ($counter ==1) continue;

                          //$splitcontents = explode("\t", $line_of_text);
                          $splitcontents = str_getcsv($line_of_text, "\t", '"');
                          if (sizeof($splitcontents)>1 && sizeof($splitcontents) == 24) { //amazon export 24
                          	if (in_array(trim($splitcontents[0]), $order_array)) continue;
                          	elseif (empty($splitcontents[0])) continue;
                            $output = array();
                            $output[] = str_replace(",", " ", trim($splitcontents[16])); //Contact Name
                            $output[] = ""; //Department
                            $output[] = str_replace(' ', '', trim($splitcontents[24])); //Phone Number
                            $output[] = ""; //Email Address
                            $output[] = str_replace(",", " ", trim($splitcontents[17])); //Address 1
                            $output[] = str_replace(",", " ", trim($splitcontents[18])); //Address 2
                            $output[] = str_replace(",", " ", trim($splitcontents[20])); //Town
                            $output[] = str_replace(",", " ", trim($splitcontents[21])); //County
                            $output[] = str_replace(",", " ", trim($splitcontents[22])); //Postcode
                            $output[] = str_replace(",", " ", trim($splitcontents[0])); //Customer Ref
                            $output[] = ""; //Internal Ref
                            $output[] = ""; //Custom Field
                            $output[] = ""; //Order ID
                            $output[] = 1; //Items
                            $output[] = 340; //Service ID

                            $dataRowString = implode($delimiter, $output);
                            fwrite($downloadfile, $dataRowString . "\r\n");
                            $order_array[] = trim($splitcontents[0]);
                          }
                          else { //ebay export 48
                          	if (sizeof($splitcontents) < 48 ) {
                          		$splitcontents = str_getcsv($line_of_text, ",", '"');
                          	}
                          	if (sizeof($splitcontents) == 48 ) {
                              if (in_array(trim($splitcontents[0]), $order_array)) continue;
                              elseif (empty($splitcontents[0])) continue; //no order id
                              elseif (empty($splitcontents[41])) continue; //no address 1
                              $output = array();
                              $output[] = str_replace(",", " ", trim($splitcontents[2])); //Contact Name
                              $output[] = ""; //Department
                              $output[] = ""; //Phone Number
                              $output[] = ""; //Email Address
                              $output[] = str_replace(",", " ", trim($splitcontents[41])); //Address 1
                              $output[] = str_replace(",", " ", trim($splitcontents[42])); //Address 2
                              $output[] = str_replace(",", " ", trim($splitcontents[43])); //Town
                              $output[] = str_replace(",", " ", trim($splitcontents[44])); //County
                              $output[] = str_replace(",", " ", trim($splitcontents[45])); //Postcode
                              $output[] = str_replace(",", " ", trim($splitcontents[0])); //Customer Ref
                              $output[] = ""; //Internal Ref
                              $output[] = ""; //Custom Field
                              $output[] = ""; //Order ID
                              $output[] = 1; //Items
                              $output[] = 340; //Service ID

                              $dataRowString = implode($delimiter, $output);
                              fwrite($downloadfile, $dataRowString . "\r\n");
                              $order_array[] = trim($splitcontents[0]);
                            }
                          }
                        }
                        fclose($downloadfile);
                        fclose($file_handle);
                      }
                      tep_remove($current_path . '/' . $file);
                    }
                  }
                  closedir($handler);
            }

          }
          else {
          	if ($HTTP_POST_VARS['filetype'] ==4) { //Select the "amazon/lcp update" to upload the lcp daily file and the amazon daily order shipped report. it then generates a file
          	  if (is_uploaded_file($HTTP_POST_FILES['file_a1']['tmp_name']) && is_uploaded_file($HTTP_POST_FILES['file_a2']['tmp_name'])) {
          		  new upload('file_a1', $current_path);
          		  new upload('file_a2', $current_path);

                $handler = opendir($current_path);
                $lcp_records = array();
                $amazon_records = array();

                while ($file = readdir($handler)) {
                  if ($file != "." && $file != "..") {
                    if ($file == $HTTP_POST_FILES['file_a1']['name'] || $file == $HTTP_POST_FILES['file_a2']['name']) {
                    	if (preg_match('/lcp_export_/i', $file)) { //lcp daily file
                    		$file_handle = fopen($current_path . "/" . $file, "r");
                        while (!feof($file_handle) ) {
                          $line_of_text = fgetcsv($file_handle, 4096);
                          $line_of_text[3] = preg_replace("/\"/i", "", $line_of_text[3]);
                          $line_of_text[14] = preg_replace("/\"/i", "", $line_of_text[14]);
                          $line_of_text[19] = preg_replace("/\"/i", "", $line_of_text[19]);
                          if (!tep_not_null($line_of_text[14])) continue;
                          if (strlen($line_of_text[14]) != 19) continue;
                          $lcp_records[$line_of_text[14]] = array('dispatch_date' => date("Y-m-d", strtotime($line_of_text[3])), 'jd_number' => $line_of_text[19]);
                        }
                        fclose($file_handle);
                    	}
                    	elseif (preg_match('/.txt/i', $file)) {
                    		$counter=0;
                    		$file_handle = fopen($current_path . "/" . $file, "r");
                        while (!feof($file_handle) ) {
                          $counter++;
                          $line_of_text = fgets($file_handle, 4096);
                          if ($counter ==1) continue;

                          $splitcontents = str_getcsv($line_of_text, "\t", '"');
                          if (!tep_not_null($splitcontents[0])) continue;
                          //$amazon_records[$splitcontents[0]] = array('order-item-id' => $splitcontents[1], 'quantity-purchased' => $splitcontents[12], 'ship-service-level' => $splitcontents[15]);
                          $amazon_records[] = array('order-id' => $splitcontents[0], 'order-item-id' => $splitcontents[1], 'quantity-purchased' => $splitcontents[12], 'ship-service-level' => $splitcontents[15]);
                        }
                        fclose($file_handle);
                    	}
                    	if (sizeof($lcp_records) && sizeof($amazon_records)) {
                    		//$result_key=array_intersect_key($lcp_records,$amazon_records);
                    		//if (sizeof($result_key)) {
                    			$delimiter=",";
                          $downloadfile_name = date("Ymd_His") . "_alcp.csv";
                          $downloadfile = fopen($current_path . "/" . $downloadfile_name, "w") or die("Unable to open file!");
                          fwrite($downloadfile, "order-id,order-item-id,quantity,ship-date,carrier-code,carrier-name,tracking-number,ship-method" . "\r\n");                          foreach($amazon_records as $v) {
                            if (isset($lcp_records[$v['order-id']])) {
                              $output = array();
                              $output[] = $v['order-id'];
                              $output[] = $v['order-item-id'];
                              $output[] = $v['quantity-purchased'];
                              $output[] = $lcp_records[$v['order-id']]['dispatch_date'];
                              $output[] = "Other";
                              $output[] = "YODEL";
                              $output[] = $lcp_records[$v['order-id']]['jd_number'];
                              $output[] = $v['ship-service-level'];
                              $dataRowString = implode($delimiter, $output);
                              fwrite($downloadfile, $dataRowString . "\r\n");
                            }
                          }
                          fclose($downloadfile);
                    		//}
                    	}

                    }
                    tep_remove($current_path . '/' . $file);

                    if (isset($HTTP_POST_FILES['file_']['tmp_name'])) {
                    }
                  }
                }

                closedir($handler);
          	  }
            }
          }
        //}

        //tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));
        break;
      case 'download':
        header('Content-type: application/x-octet-stream');
        header('Content-disposition: attachment; filename=' . urldecode($HTTP_GET_VARS['filename']));
        readfile($current_path . '/' . urldecode($HTTP_GET_VARS['filename']));
        exit;
        break;
      case 'upload':
      case 'new_folder':
      case 'new_file':
        $directory_writeable = true;
        if (!is_writeable($current_path)) {
          $directory_writeable = false;
          $messageStack->add(sprintf(ERROR_DIRECTORY_NOT_WRITEABLE, $current_path), 'error');
        }
        break;
      case 'edit':
        if (strstr($HTTP_GET_VARS['info'], '..')) tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));

        $file_writeable = true;
        if (!is_writeable($current_path . '/' . $HTTP_GET_VARS['info'])) {
          $file_writeable = false;
          $messageStack->add(sprintf(ERROR_FILE_NOT_WRITEABLE, $current_path . '/' . $HTTP_GET_VARS['info']), 'error');
        }
        break;
      case 'delete':
        if (strstr($HTTP_GET_VARS['info'], '..')) tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));
        break;
    }
  }

  $in_directory = substr(substr(DIR_FS_DOCUMENT_ROOT, strrpos(DIR_FS_DOCUMENT_ROOT, '/')), 1);
  $current_path_array = explode('/', $current_path);
  $document_root_array = explode('/', DIR_FS_DOCUMENT_ROOT);
  $goto_array = array(array('id' => DIR_FS_DOCUMENT_ROOT, 'text' => $in_directory));
  for ($i=0, $n=sizeof($current_path_array); $i<$n; $i++) {
    if ((isset($document_root_array[$i]) && ($current_path_array[$i] != $document_root_array[$i])) || !isset($document_root_array[$i])) {
      $goto_array[] = array('id' => implode('/', array_slice($current_path_array, 0, $i+1)), 'text' => $current_path_array[$i]);
    }
  }
  require(DIR_WS_INCLUDES . 'template_top.php');
?>

<script>
$(document).ready(function(){
	$('form[name="file"]').on('change', 'select[name="filetype"]', function() {
//console.log($(this).val() + "-" + $(this).text());
		if (this.value == 2) {
			$('form[name="file"]').find('select[name="servicetype"]').show();
			$('form[name="file"]').find('select[name="sizetype"]').show();
		}
		else {
			$('form[name="file"]').find('select[name="servicetype"]').hide();
			$('form[name="file"]').find('select[name="sizetype"]').hide();
		}
		
		if (this.value==4 ) {
				$('form[name="file"]').find('input[name="file_a1"]').show();
	      $('form[name="file"]').find('input[name="file_a2"]').show();
        if ($('form[name="file"]').find('input[name="file_"]').val() != "") {
        	$('form[name="file"]').find('input[name="file_"]').val("");
        }
        $('form[name="file"]').find('input[name="file_"]').hide();
			}
			else {
	      $('form[name="file"]').find('input[name="file_a1"]').hide();
	      $('form[name="file"]').find('input[name="file_a2"]').hide();

        $('form[name="file"]').find('input[name="file_"]').show();
			}

	});

	$('form[name="file"]').find('select[name="servicetype"]').hide();
	$('form[name="file"]').find('select[name="sizetype"]').hide();
	$('form[name="file"]').find('input[name="file_a1"]').hide();
	$('form[name="file"]').find('input[name="file_a2"]').hide();
});
</script>
<!-- body //-->
    <table border="0" width="100%" cellspacing="2" cellpadding="2">
    	<?php
    	if ($admin['login_groups_id'] <2) {
    	?>
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr><?php echo tep_draw_form('goto', FILENAME_FILE_MANAGER_DELIVERY, '', 'get'); ?>
            <td class="pageHeading"><?php echo HEADING_TITLE . '<br><span class="smallText">' . $current_path . '</span>'; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', '1', HEADING_IMAGE_HEIGHT); ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_pull_down_menu('goto', $goto_array, $current_path, 'onChange="this.form.submit();"'); ?></td>
          <?php echo tep_hide_session_id(); ?></form></tr>
        </table></td>
      </tr>
      <?php
      }
      ?>
<?php
  if ( (($action == 'new_file') && ($directory_writeable == true)) || ($action == 'edit') ) {
    if (isset($HTTP_GET_VARS['info']) && strstr($HTTP_GET_VARS['info'], '..')) tep_redirect(tep_href_link(FILENAME_FILE_MANAGER_DELIVERY));

    if (!isset($file_writeable)) $file_writeable = true;
    $file_contents = '';
    if ($action == 'new_file') {
      $filename_input_field = tep_draw_input_field('filename');
    } elseif ($action == 'edit') {
      if ($file_array = file($current_path . '/' . $HTTP_GET_VARS['info'])) {
        $file_contents = implode('', $file_array);
      }
      $filename_input_field = $HTTP_GET_VARS['info'] . tep_draw_hidden_field('filename', $HTTP_GET_VARS['info']);
    }
?>
      <tr>
        <td><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr><?php echo tep_draw_form('new_file', FILENAME_FILE_MANAGER_DELIVERY, 'action=save'); ?>
        <td><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><?php echo TEXT_FILE_NAME; ?></td>
            <td class="main"><?php echo $filename_input_field; ?></td>
          </tr>
          <tr>
            <td class="main" valign="top"><?php echo TEXT_FILE_CONTENTS; ?></td>
            <td class="main"><?php echo tep_draw_textarea_field('file_contents', 'soft', '80', '20', $file_contents, (($file_writeable) ? '' : 'readonly')); ?></td>
          </tr>
          <tr>
            <td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
          </tr>
          <tr>
            <td align="right" class="main" colspan="2"><?php if ($file_writeable == true) echo tep_image_submit('button_save.gif', IMAGE_SAVE) . '&nbsp;'; echo '<a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, (isset($HTTP_GET_VARS['info']) ? 'info=' . urlencode($HTTP_GET_VARS['info']) : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
          </tr>
        </table></td>
      </form></tr>
<?php
  } else {
    $showuser = (function_exists('posix_getpwuid') ? true : false);
    $contents = array();
    $dir = dir($current_path);
    while ($file = $dir->read()) {
      if ( ($file != '.') && ($file != 'CVS') && ( ($file != '..') || ($current_path != DIR_FS_DOCUMENT_ROOT) ) ) {
        $file_size = number_format(filesize($current_path . '/' . $file)) . ' bytes';

        $permissions = tep_get_file_permissions(fileperms($current_path . '/' . $file));
        if ($showuser) {
          $user = @posix_getpwuid(fileowner($current_path . '/' . $file));
          $group = @posix_getgrgid(filegroup($current_path . '/' . $file));
        } else {
          $user = $group = array();
        }

        $contents[] = array('name' => $file,
                            'is_dir' => is_dir($current_path . '/' . $file),
                            'last_modified' => strftime(DATE_TIME_FORMAT, filemtime($current_path . '/' . $file)),
                            'size' => $file_size,
                            'permissions' => $permissions,
                            'user' => $user['name'],
                            'group' => $group['name']);
      }
    }

    function tep_cmp($a, $b) {
      return strcmp( ($a['is_dir'] ? 'D' : 'F') . $a['name'], ($b['is_dir'] ? 'D' : 'F') . $b['name']);
    }
    usort($contents, 'tep_cmp');
?>

      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_FILENAME; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_SIZE; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_PERMISSIONS; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_USER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_GROUP; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_LAST_MODIFIED; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  for ($i=0, $n=sizeof($contents); $i<$n; $i++) {
    if ((!isset($HTTP_GET_VARS['info']) || (isset($HTTP_GET_VARS['info']) && ($HTTP_GET_VARS['info'] == $contents[$i]['name']))) && !isset($fInfo) && ($action != 'upload') && ($action != 'new_folder')) {
      $fInfo = new objectInfo($contents[$i]);
    }

    if ($contents[$i]['name'] == '..') {
      $goto_link = substr($current_path, 0, strrpos($current_path, '/'));
    } else {
      $goto_link = $current_path . '/' . $contents[$i]['name'];
    }

    if (isset($fInfo) && is_object($fInfo) && ($contents[$i]['name'] == $fInfo->name)) {
      if ($fInfo->is_dir) {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
        $onclick_link = 'goto=' . $goto_link;
      } else {
        echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
        $onclick_link = 'info=' . urlencode($fInfo->name) . '&action=edit';
      }
    } else {
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">' . "\n";
      $onclick_link = 'info=' . urlencode($contents[$i]['name']);
    }

    if ($contents[$i]['is_dir']) {
      if ($contents[$i]['name'] == '..') {
        $icon = tep_image(DIR_WS_ICONS . 'previous_level.gif', ICON_PREVIOUS_LEVEL);
      } else {
        $icon = (isset($fInfo) && is_object($fInfo) && ($contents[$i]['name'] == $fInfo->name) ? tep_image(DIR_WS_ICONS . 'current_folder.gif', ICON_CURRENT_FOLDER) : tep_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER));
      }
      $link = tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'goto=' . $goto_link);
    } else {
      $icon = tep_image(DIR_WS_ICONS . 'file_download.gif', ICON_FILE_DOWNLOAD);
      $link = tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'action=download&filename=' . urlencode($contents[$i]['name']));
    }
?>
                <td class="dataTableContent" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><?php echo '<a href="' . $link . '">' . $icon . '</a>&nbsp;' . $contents[$i]['name']; ?></td>
                <td class="dataTableContent" align="right" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><?php echo ($contents[$i]['is_dir'] ? '&nbsp;' : $contents[$i]['size']); ?></td>
                <td class="dataTableContent" align="center" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><tt><?php echo $contents[$i]['permissions']; ?></tt></td>
                <td class="dataTableContent" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><?php echo $contents[$i]['user']; ?></td>
                <td class="dataTableContent" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><?php echo $contents[$i]['group']; ?></td>
                <td class="dataTableContent" align="center" onclick="document.location.href='<?php echo tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, $onclick_link); ?>'"><?php echo $contents[$i]['last_modified']; ?></td>
                <td class="dataTableContent" align="right"><?php if ($contents[$i]['name'] != '..') echo '<a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode($contents[$i]['name']) . '&action=delete') . '">' . tep_image(DIR_WS_ICONS . 'delete.gif', ICON_DELETE) . '</a>&nbsp;'; if (isset($fInfo) && is_object($fInfo) && ($fInfo->name == $contents[$i]['name'])) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode($contents[$i]['name'])) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
  }
?>
              <tr>
                <td colspan="7"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                  <tr valign="top">
                    <td class="smallText"><?php echo tep_draw_button(IMAGE_RESET, 'arrowrefresh-1-e', tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'action=reset')); ?></td>
                    <td class="smallText" align="right"><?php echo tep_draw_button(IMAGE_UPLOAD, 'arrow-1-n', tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, (isset($HTTP_GET_VARS['info']) ? 'info=' . urlencode($HTTP_GET_VARS['info']) . '&' : '') . 'action=upload')); ?></td>
                  </tr>
                </table></td>
              </tr>
            </table></td>
<?php
    $heading = array();
    $contents = array();

    switch ($action) {
      case 'delete':
        $heading[] = array('text' => '<b>' . $fInfo->name . '</b>');

        $contents = array('form' => tep_draw_form('file', FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode($fInfo->name) . '&action=deleteconfirm'));
        $contents[] = array('text' => TEXT_DELETE_INTRO);
        $contents[] = array('text' => '<br><b>' . $fInfo->name . '</b>');
        $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . ' <a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, (tep_not_null($fInfo->name) ? 'info=' . urlencode($fInfo->name) : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'new_folder':
        $heading[] = array('text' => '<b>' . TEXT_NEW_FOLDER . '</b>');

        $contents = array('form' => tep_draw_form('folder', FILENAME_FILE_MANAGER_DELIVERY, 'action=insert'));
        $contents[] = array('text' => TEXT_NEW_FOLDER_INTRO);
        $contents[] = array('text' => '<br>' . TEXT_FILE_NAME . '<br>' . tep_draw_input_field('folder_name'));
        $contents[] = array('align' => 'center', 'text' => '<br>' . (($directory_writeable == true) ? tep_image_submit('button_save.gif', IMAGE_SAVE) : '') . ' <a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, (isset($HTTP_GET_VARS['info']) ? 'info=' . urlencode($HTTP_GET_VARS['info']) : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
        break;
      case 'upload':
        $heading[] = array('text' => '<strong>' . TEXT_INFO_HEADING_UPLOAD . '</strong>');

        $contents = array('form' => tep_draw_form('file', FILENAME_FILE_MANAGER_DELIVERY, 'action=processuploads', 'post', 'enctype="multipart/form-data"'));
        $contents[] = array('text' => TEXT_UPLOAD_INTRO);

        $file_upload = '';
        //for ($i=1; $i<6; $i++) $file_upload .= tep_draw_file_field('file_' . $i) . '<br>';
        $file_upload .= tep_draw_file_field('file_') . '<br />';

        $contents[] = array('text' => '<br />' . $file_upload);

        //20151026
        $filetype = array(array('id' => 1, 'text' => TEXT_UPLOAD_ROYALMAIL), array('id' => 2, 'text' => TEXT_UPLOAD_RM), array('id' => 3, 'text' => TEXT_UPLOAD_LCP), array('id' => 4, 'text' => TEXT_AMAZON_SHIPPING_CONFIRMATION));
        $contents[] = array('text' => '<br />' . tep_draw_pull_down_menu('filetype', $filetype));

        $servicetype = array(array('id' => 1, 'text' => TEXT_UPLOAD_WEIGHT_FIRST), array('id' => 2, 'text' => TEXT_UPLOAD_WEIGHT_SECOND));
        $contents[] = array('text' => '<br />' . tep_draw_pull_down_menu('servicetype', $servicetype));

        $sizetype = array(array('id' => 0, 'text' => TEXT_UPLOAD_SIZE_NONE), array('id' => 1, 'text' => TEXT_UPLOAD_SIZE_PARCEL));
        $contents[] = array('text' => '<br />' . tep_draw_pull_down_menu('sizetype', $sizetype));

        $contents[] = array('text' => '<br />' . tep_draw_file_field('file_a1') . '<br />' . tep_draw_file_field('file_a2'));

        $contents[] = array('align' => 'center', 'text' => '<br />' . (($directory_writeable == true) ? tep_draw_button(IMAGE_UPLOAD, 'disk', null, 'primary') : '') . tep_draw_button(IMAGE_CANCEL, 'cancel', tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, (isset($HTTP_GET_VARS['info']) ? 'info=' . urlencode($HTTP_GET_VARS['info']) : ''))));

        //20151026
        break;
      default:
        if (isset($fInfo) && is_object($fInfo)) {
          $heading[] = array('text' => '<b>' . $fInfo->name . '</b>');

          if (!$fInfo->is_dir) $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_FILE_MANAGER_DELIVERY, 'info=' . urlencode($fInfo->name) . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a>');
          $contents[] = array('text' => '<br>' . TEXT_FILE_NAME . ' <b>' . $fInfo->name . '</b>');
          if (!$fInfo->is_dir) $contents[] = array('text' => '<br>' . TEXT_FILE_SIZE . ' <b>' . $fInfo->size . '</b>');
          $contents[] = array('text' => '<br>' . TEXT_LAST_MODIFIED . ' ' . $fInfo->last_modified);
        }
    }

    if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
      echo '            <td width="25%" valign="top">' . "\n";

      $box = new box;
      echo $box->infoBox($heading, $contents);

      echo '            </td>' . "\n";
    }
?>
          </tr>
        </table>
<?php
  }
?>
 

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>