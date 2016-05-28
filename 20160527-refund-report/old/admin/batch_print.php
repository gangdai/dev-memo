<?php
require('includes/application_top.php');
include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ORDERS_INVOICE);

if (!function_exists('isExpensiveInterlink'))
{
        ////My modification check postcode for UK Interlink Expensive area
        function isExpensiveInterlink($postcode="")
        {
          //trim the end part and tidy the postcode first
          $postcode=str_replace(" ", "", trim($postcode));
          $postcode=substr(strtoupper($postcode), 0, -3);
        
          //Jersey, Guernsey, Isle of Man, part of Scottland
          $inarea_1 = array("JE","GY","IM","BT","PO","KA","KW","IV","AB","PH","HS","PA","P0");
          if (in_array(substr($postcode,0,2),$inarea_1)) {
            return true;
          }
          elseif (preg_match("/^(KW1[5-7]|ZE[1-3])$/i", $postcode) ||
                  preg_match("/^(HS1|PA23)$/i", $postcode)
                 )
          {
            return true;
          }
          else
            return false;
        }
}

$pageloop = "0";
if ($HTTP_GET_VARS['mkey'])
{
  $key = $HTTP_GET_VARS['mkey']; 
  $message = $error[$key]; 
  $HTTP_GET_VARS['act'] = 0;
}

if ($HTTP_GET_VARS['act'] == '') { $HTTP_GET_VARS['act'] = 0; }

if (strlen($HTTP_GET_VARS['act']) == 1 && is_numeric($HTTP_GET_VARS['act']))
{

  switch ($HTTP_GET_VARS['act'])
  {
    case 1:
    // check if invoice number is a empty field .. if its not empty do this .. if it is empty skip down to the check date entered code.
    //if ($invoicenumbers != '')

    if ($HTTP_POST_VARS['invoicenumbers']!='')
    {
      if (!isset($HTTP_POST_VARS['invoicenumbers'])) { message_handler('ERROR_BAD_INVOICENUMBERS');  }
      if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
      $time0 = time();
      $invoicenumbers = tep_db_prepare_input($HTTP_POST_VARS['invoicenumbers']);
      $arr_no = explode(',',$invoicenumbers);
      foreach ($arr_no as $key=>$value)
      {
        $arr_no[$key]=trim($value);
        if (substr_count($arr_no[$key],'-')>0)
        {
          $temp_range=explode('-',$arr_no[$key]);
          $arr_no[$key]=implode(',',range((int) $temp_range[0], (int) $temp_range[1]));
        }
      }
      $invoicenumbers=implode(',',$arr_no);
    }
    else
    {
      // CHECK DATE ENTERED, GRAB ALL ORDERS FROM THAT DATE, AND CREATE PDF FOR ORDERS
      if (!isset($HTTP_POST_VARS['startdate'])) { message_handler(); }
      if ((strlen($HTTP_POST_VARS['startdate']) != 10) || verify_start_date($HTTP_POST_VARS['startdate'])) { message_handler('ERROR_BAD_DATE'); }
      if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
      $time0 = time();
      $startdate = tep_db_prepare_input($HTTP_POST_VARS['startdate']);

      if (!isset($HTTP_POST_VARS['enddate'])) { message_handler(); }
      if ((strlen($HTTP_POST_VARS['enddate']) != 10) || verify_end_date($HTTP_POST_VARS['enddate'])) { message_handler('ERROR_BAD_DATE'); }
      if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
      $time0 = time();
      $enddate = tep_db_prepare_input($HTTP_POST_VARS['enddate']);
    }

//My modification, check if any order to exclude
    if ($exinvoicenumbers != '')
    {
      if (!isset($HTTP_POST_VARS['exinvoicenumbers'])) { message_handler('ERROR_BAD_INVOICENUMBERS_EXCLUDE');  }
      if (!is_writeable(BATCH_PDF_DIR)) { message_handler('SET_PERMISSIONS'); }
      $time0 = time();
      $exinvoicenumbers = tep_db_prepare_input($HTTP_POST_VARS['exinvoicenumbers']);
      $arr_no_ex = explode(',',$exinvoicenumbers);
      foreach ($arr_no_ex as $key=>$value)
      {
        $arr_no_ex[$key]=trim($value);
        if (substr_count($arr_no_ex[$key],'-')>0)
        {
          $temp_range_ex=explode('-',$arr_no_ex[$key]);
          $arr_no_ex[$key]=implode(',',range((int) $temp_range_ex[0], (int) $temp_range_ex[1]));
        }
      }
      $exinvoicenumbers=implode(',',$arr_no_ex);
    }
//My modification, check if any order to exclude

    require(DIR_WS_CLASSES . 'currencies.php');
    require(BATCH_PRINT_INC . 'class.ezpdf.php');
    require(DIR_WS_CLASSES . 'order.php');

    //201511
    require(BATCH_PRINT_INC . 'chars.php');

    //grab only the page size and layout from template
    require(BATCH_PRINT_INC . 'templates/' . $HTTP_POST_VARS['file_type']);
    $pageloop = "1";
    //$pdf = new Cezpdf($HTTP_POST_VARS['page'],$HTTP_POST_VARS['orientation']);

    if ($HTTP_POST_VARS['show_comments']) { $get_customer_comments = ' and h.orders_status_id = ' . DEFAULT_ORDERS_STATUS_ID; }
    if ($HTTP_POST_VARS['pull_status'])
    {
    	if ($HTTP_POST_VARS['pull_status']==110)
    	$pull_w_status = " and (o.orders_status=1 or o.orders_status=101)";
    	else
    	$pull_w_status = " and o.orders_status = ". $HTTP_POST_VARS['pull_status'];
    }

    //cash & carry
    $cash_carry_cond = " and o.cash_carry='0' ";
    //cash & carry

    // if there is a invoice number then use first order query otherwise use second date style order query
    if ($invoicenumbers != '')
    {
    	if ($exinvoicenumbers != '')
    	{
    		$orders_query = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.orders_id in (" . tep_db_input($invoicenumbers) . ") and o.orders_id not in (" . tep_db_input($exinvoicenumbers) . ") and h.orders_id = o.orders_id" . $pull_w_status . $get_customer_comments . $cash_carry_cond .' group by o.orders_id');
    	}
    	else
    	{
        $orders_query = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.orders_id in (" . tep_db_input($invoicenumbers) . ") and h.orders_id = o.orders_id" . $pull_w_status . $get_customer_comments . $cash_carry_cond . ' group by o.orders_id');
      }
    }
    else
    {
    	if ($exinvoicenumbers != '')
    	{
    		$orders_query = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.date_purchased between '" . tep_db_input($startdate) . "' and '" . tep_db_input($enddate) . " 23:59:59' and o.orders_id not in (" . tep_db_input($exinvoicenumbers) . ") and h.orders_id = o.orders_id" . $pull_w_status . $get_customer_comments . $cash_carry_cond . ' group by o.orders_id');
    	}
    	else
    	{
        $orders_query = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.date_purchased between '" . tep_db_input($startdate) . "' and '" . tep_db_input($enddate) . " 23:59:59' and h.orders_id = o.orders_id" . $pull_w_status . $get_customer_comments . $cash_carry_cond . ' group by o.orders_id');
      }
    }

    if (!tep_db_num_rows($orders_query) > 0) { message_handler('NO_ORDERS'); }
    $num = 0;$num_label=0;

//My modification: store the orders for different shipping method
$tt=array();
$flag_register=0; //if start with register shipping method, use this so that only other postal method will not start at the second page
$build_other_postal=0; //start building other postal methods
//My modification: store the orders for different shipping method
    while ($orders = tep_db_fetch_array($orders_query))
    {
      $order = new order($orders['orders_id']);

      if ($num != 0)
      { $pdf->EzNewPage();}

      // start of pdf layout ..   ################################
      require(BATCH_PRINT_INC . 'templates/' . $HTTP_POST_VARS['file_type']);
      // end pdf layout section   ###############################

      if ($HTTP_POST_VARS['status'] && ($HTTP_POST_VARS['status'] != $order->info['orders_status']))
      {
        $customer_notified = 0; 
        $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
        $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, BATCH_COMMENTS) . "\n\n";

        if ($HTTP_POST_VARS['notify'])
        {
          $status_query = tep_db_query("select orders_status_name as name from " . TABLE_ORDERS_STATUS . " where language_id = '" . $languages_id . "' and orders_status_id = " . tep_db_input($status));
          $status_name = tep_db_fetch_array($status_query);

          $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders['orders_id'] . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders['orders_id'], 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($order->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $status_name['name']);
          tep_mail($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
          $customer_notified = '1';
        }

        tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . $orders['orders_id'] . "'");
        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . $orders['orders_id'] . "', '" . tep_db_input($status) . "', now(), '" . $customer_notified . "', '" . $notify_comments  . "')");
      }

      $num++;
     	//Send fake header to avoid timeout, got this trick from phpMyAdmin
		  $time1 = time();
      if ($time1 >= $time0 + 30)
      {
        $time0 = $time1;
        header('X-bpPing: Pong');
			}
    }// EOWHILE


//My modification for postalcheck only
    if (preg_match('/postalcheck/i',$HTTP_POST_VARS['file_type']))
    {
    	if (sizeof($tt)>0 && is_numeric($tt[0]))
    	{
    		$gg= array();
    		for ($i=0; $i<sizeof($tt); $i++)
    		{
    			if ($tt[$i]>0 && is_numeric($tt[$i]))
    			$gg[]=$tt[$i];
    		}
    		$other_postal = implode(',',$gg);
    		$other_postal = $other_postal;
    	  $orders_query_others = tep_db_query("select o.orders_id,h.comments,MIN(h.date_added) from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS_HISTORY . " h where o.orders_id in (" . tep_db_input($other_postal) . ") group by o.orders_id");

$build_other_postal = 1;
    	  while ($orders = tep_db_fetch_array($orders_query_others))
        {
          $order = new order($orders['orders_id']);
          if ($num != 0 && $flag_register==1)
          {	$pdf->EzNewPage();
          }
          if ($num == 1 && $flag_register==0)
          {//if no registered order at all, the the $num will be added once by previous check, deduct one so that only other orders are counted
          	$num--;
          }

      // start of pdf layout ..   ################################
      require(BATCH_PRINT_INC . 'templates/' . $HTTP_POST_VARS['file_type']);
      // end pdf layout section   ###############################

          $num++;

     	    //Send fake header to avoid timeout, got this trick from phpMyAdmin
		      $time1 = time();
          if ($time1 >= $time0 + 30)
          {
            $time0 = $time1;
            header('X-bpPing: Pong');
			    }
        }// EOWHILE
    	}
    }
//My modification

if (preg_match('/interlink/i', $HTTP_POST_VARS['file_type']) || preg_match('/ups/i', $HTTP_POST_VARS['file_type']) || preg_match('/rmtrack/i', $HTTP_POST_VARS['file_type']) || preg_match('/lowcostparcel/i', $HTTP_POST_VARS['file_type']) || preg_match('/ekomi/i', $HTTP_POST_VARS['file_type']) || preg_match('/ukmail/i', $HTTP_POST_VARS['file_type']) || preg_match('/sagepayhr/i', $HTTP_POST_VARS['file_type'])) { exit; }
    $pdf_code = $pdf->output();

    $fname = BATCH_PDF_DIR . BATCH_PDF_FILE;
    if($fp = fopen($fname,'w'))
    {
    fwrite($fp,$pdf_code);
    fclose($fp);
    }
    else { message_handler('FAILED_TO_OPEN'); }
    // changed below to cause pdf to open in a new window
    //echo print_r($HTTP_POST_VARS);

    $clicktodownload = HTTPS_SERVER . DIR_WS_ADMIN . BATCH_PDF_DIR . 'download.php';
    //$message =  'A PDF of ' . $num . ' record(s) was successful! <a href="'.$fname.'" target="_blank"><b>Click here</b></a> to download the order file. Or <a href="'.tep_href_link(FILENAME_BATCH_PRINT,'','SSL') .'"><b>Back</b></a>';
    $message =  'A PDF of ' . $num . ' record(s) was successful! <a href="'.$clicktodownload.'"><b>Download</b></a> the order file. Or <a href="'.tep_href_link(FILENAME_BATCH_PRINT,'','SSL') .'"><b>Back</b></a>';

    case 0:
     require(BATCH_PRINT_INC . 'batch_print_header.php');
     require(BATCH_PRINT_INC . 'batch_print_body.php');
     require(BATCH_PRINT_INC . 'batch_print_footer.php');
     break;
    default:
     message_handler();
  }//EOSWITCH
}
else
{ message_handler('ERROR_INVALID_INPUT');
}

//// FUNCTION AREA
function message_handler($message='')
{
  if ($message)
  { header("Location: " . tep_href_link(BATCH_PRINT_FILE, 'mkey=' . $message));
  }
  else
  { header("Location: " . tep_href_link(BATCH_PRINT_FILE));
  }
  exit(0);
}

function change_color($color)
{
  global $pdf;
  list($r,$g,$b) = explode(',', $color);
  $pdf->setColor($r,$g,$b);
}

function verify_start_date($startdate)
{
  $error = 0;
  list($year,$month,$day) = explode('-', $startdate);
  if ((strlen($year) != 4) || !is_numeric($year))
  { $error++;}
  if ((strlen($month) != 2) || !is_numeric($month))
  { $error++;}
  if ((strlen($day) != 2) || !is_numeric($day))
  { $error++;}
  return $error;
}

function verify_end_date($enddate)
{
  $error = 0;
  list($year,$month,$day) = explode('-', $enddate);
  if ((strlen($year) != 4) || !is_numeric($year))
  {$error++;}
  if ((strlen($month) != 2) || !is_numeric($month))
  {$error++;}
  if ((strlen($day) != 2) || !is_numeric($day))
  {$error++;}
  return $error;
}

function print_address($x, $y, $nums)
{
  global $pdf, $num, $billing;
  $pos = $y;
  global $orders_query;
  global $order;
  global $orders;
  global $languages_id;
  global $HTTP_POST_VARS;
  if ($order)
  {
    if($billing == true)
	    $addressparts = explode("\n", tep_address_format($order->billing['format_id'], $order->billing, 1, '', " \n"));
    else
	    $addressparts = explode("\n", tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', " \n"));
    foreach($addressparts as $addresspart)
    {
	    $fontsize = GENERAL_FONT_SIZE;
	    while ($pdf->getTextWidth($fontsize, $addresspart) > LABEL_WIDTH)
	    {
		   $fontsize--;
    	}
	    //$addresspart = preg_replace("%,[[:space:]]*$%", "", $addresspart);
	    $pdf->addText($x+26,$pos -=(GENERAL_LINE_SPACING-2),$fontsize,html_entity_decode(html_encode($addresspart)));
    }
    $pdf->addText($x + LABEL_WIDTH - ORDERIDXOFFSET - 22 - 100,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,$orders['orders_id']);

    if ($HTTP_POST_VARS['status'] && ($HTTP_POST_VARS['status'] != $order->info['orders_status']))
    {
      $customer_notified = 0; 
      $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
      $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, BATCH_COMMENTS) . "\n\n";

      if ($HTTP_POST_VARS['notify'])
      {
        $status_query = tep_db_query("select orders_status_name as name from " . TABLE_ORDERS_STATUS . " where language_id = " . $languages_id . " and orders_status_id = " . tep_db_input($status));
        $status_name = tep_db_fetch_array($status_query);

        $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders['orders_id'] . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($order->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $status_name['name']);
        tep_mail($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        $customer_notified = '1';
      }

      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . $orders['orders_id'] . "'");
      tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . $orders['orders_id'] . "', '" . tep_db_input($status) . "', now(), '" . $customer_notified . "', '" . $notify_comments  . "')");
    }

    if(($nums % NUM_LABELS_PER_PAGE) == (NUM_LABELS_PER_PAGE-1))
    {
      $order = false;
      return false;
    }
    else
    {
      if($orders = tep_db_fetch_array($orders_query))
      {
        $order = new order($orders['orders_id']);
        return true;
      }
      else
      {
        $order = false;
        return false;
      }
    }
  }
  else
  {
    return false;
  }
}

function print_despatch_address($x, $y, $nums)
{
  global $pdf, $num, $billing;
  $pos = $y;
  global $orders_query;
  global $order;
  global $orders;
  global $languages_id;
  global $HTTP_POST_VARS;
  global $build_other_postal,$orders_query_others;
  if ($order)
  {
    if($billing == true)
	    $addressparts = explode("\n", tep_address_format($order->billing['format_id'], $order->billing, 1, '', " \n"));
    else
	    $addressparts = explode("\n", tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', " \n"));

    //My modification Get shipping method printed
    $orders_delivery_query = tep_db_query("select title from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orders['orders_id'] . "' and class='ot_shipping'");
    $orders_delivery = tep_db_fetch_array($orders_delivery_query);

    $shipping_array = array(); $shipping_pos=$y;
    for ($i=0; $i<strlen(substr($orders_delivery['title'],0)); $i=$i+29)
    {
      if (strlen(substr($orders_delivery['title'], $i)) >30)
      { $shipping_array[]= substr($orders_delivery['title'], $i, 29); }
      elseif (strlen(substr($orders_delivery['title'], $i)) >0 && strlen(substr($orders_delivery['title'], $i)) <=30)
      {	$shipping_array[]= substr($orders_delivery['title'], $i); }
      else
      { continue;}
    }
    foreach($shipping_array as $shipping_method)
    { $pdf->addText($x+20,$shipping_pos -=(GENERAL_LINE_SPACING-8),GENERAL_FONT_SIZE-4,html_entity_decode($shipping_method)); }   
    //My modification get shipping method printed

    foreach($addressparts as $addresspart)
    {
	    $fontsize = GENERAL_FONT_SIZE-4;
	    while ($pdf->getTextWidth($fontsize, $addresspart) > LABEL_WIDTH)
	    {
		   $fontsize--;
    	}
	    //$addresspart = preg_replace("%,[[:space:]]*$%", "", $addresspart);
	    $pdf->addText($x+296,$pos -=(GENERAL_LINE_SPACING-8),$fontsize,html_entity_decode(html_encode($addresspart)));
    }
    $pdf->addText($x + LABEL_WIDTH - ORDERIDXOFFSET - 22,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,$orders['orders_id']);

    if ($HTTP_POST_VARS['status'] && ($HTTP_POST_VARS['status'] != $order->info['orders_status']))
    {
      $customer_notified = 0; 
      $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
      $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, BATCH_COMMENTS) . "\n\n";

      if ($HTTP_POST_VARS['notify'])
      {
        $status_query = tep_db_query("select orders_status_name as name from " . TABLE_ORDERS_STATUS . " where language_id = " . $languages_id . " and orders_status_id = " . tep_db_input($status));
        $status_name = tep_db_fetch_array($status_query);

        $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders['orders_id'] . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($order->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $status_name['name']);
        tep_mail($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        $customer_notified = '1';
      }

      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . $orders['orders_id'] . "'");
      tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . $orders['orders_id'] . "', '" . tep_db_input($status) . "', now(), '" . $customer_notified . "', '" . $notify_comments  . "')");
    }

    if(($nums % 5) == (5-1))
    {
      $order = false;
      return false;
    }
    else
    {
      if($build_other_postal==0)
      {
        if ($orders = tep_db_fetch_array($orders_query))
        { $order = new order($orders['orders_id']);
          return true;
        }
        else
        {
          $order = false;
          return false;
        }
      }
      elseif ($build_other_postal==1)
      {
        if ($orders = tep_db_fetch_array($orders_query_others))
      	{
          $order = new order($orders['orders_id']);
          return true;
        }
        else
        {
          $order = false;
          return false;
        }
      }
    }
  }
  else
  {
    return false;
  }
}

function checknext_postal()
{//check if next order is a non-registerd postal shipping order
	global $orders, $orders_query, $tt;
	if($orders = tep_db_fetch_array($orders_query))
  {
  	$orders_delivery_query = tep_db_query("select title from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orders['orders_id'] . "' and class='ot_shipping'");
    $orders_delivery = tep_db_fetch_array($orders_delivery_query);
    if (!preg_match('/Royal Mail Registered/i',$orders_delivery['title']))
    {
    	$tt[]= $orders['orders_id'];
    	return 3; //non registered shipping method
    }
    else
    {	return 2; //registered shipping method
    }
  }
  else 
  {
  	return 1;  //no next order break
  }
}

function print_interlink_lable($order)
{
  global $billing;
  global $delimiter;
  //global $order;
  global $orders; //$orders['orders_id']
  //global $languages_id;
  //global $HTTP_POST_VARS;
  //Set target filename - see above comment on file extension.

  if (!is_postbyinterlink($order))
    return false;
 
  if (isExpensiveInterlink($order->delivery['postcode']))
    return false;

  //if ($order && is_interlink($order->delivery['country']))
  if ($order)
  {
    /*if($billing == true)
	    $addressparts = explode("\n", tep_address_format($order->billing['format_id'], $order->billing, 1, '', " \n"));
    else
	    $addressparts = explode("\n", tep_address_format($order->delivery['format_id'], $order->delivery, 1, '', " \n"));
	    
    $data = array();
    foreach($addressparts as $addresspart)
    {
	    //$data[] = html_entity_decode(html_encode($addresspart));
	    //if (eregi('&amp;', $addresspart))
	    //str_replace("&", "And", $addresspart);
	    $data[] = $addresspart;
	    //print html_entity_decode(html_encode($addresspart)) . $delimiter
    }
    $dataRowString = implode($delimiter, $data);
    print $dataRowString . "\r\n";*/

    //Comments if any
    $instructions1 ="";
    $instructions2 ="";
    if (!preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	if (preg_match('/PayPal/i', $order->info['payment_method'])) {
    		$comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    		if (!empty($comment['comments'])) {
    			if (strlen($comment['comments'])>25) {
    				$instructions1 = substr($comment['comments'], 0, 25);
    				$instructions2 = substr($comment['comments'], 25, 25);
    			}
    			else {
    				$instructions1 = $comment['comments'];
    			}
    		}
    	}
    	else {
    	  $comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    	  if (strlen($comment['comments'])>25) {
    				$instructions1 = substr($comment['comments'], 0, 25);
    				$instructions2 = substr($comment['comments'], 25, 25);
    		}
    		else {
    			$instructions1 = $comment['comments'];
    		}
      }
    }

    $output = array();
    $output[] = $orders['orders_id'];  //Order no
    if (preg_match('/United Kingdom/i', $order->delivery['country']))
    { $output[] = "3"; }
    else
    { $output[] = "5"; }               //Job Type 1: DPD UK 5: International
    $output[] = "";                    //Customer Code: space
    $output[] = (tep_not_null($order->delivery['company'])) ? $order->delivery['company']. "," .$order->delivery['name'] : $order->delivery['name'];  //Name
    $output[] = trim($order->delivery['house_name']) . " " . trim($order->delivery['street_address']);   //Address1
    $output[] = $order->delivery['suburb'];           //Address2
    $output[] = $order->delivery['city'];             //Town
    $output[] = $order->delivery['state'];            //County
    $output[] = $order->delivery['postcode'];         //Postcode
    $output[] = $instructions1;                                   //Instructions1
    $output[] = $instructions2;                                   //Instructions2
    $output[] = "1";                                   //Qty of Labels
    $output[] = "1";                                   //Service
    $output[] = getIso2code($order->delivery['country']);          //country iso2 code
    $output[] = "1";                                  //weight
    $output[] = date("d/m/Y");                        //Delivery date
    $output[] = $order->customer['email_address'];    //Custoemr Email address
    $output[] = $order->customer['telephone'];        //Telephone
    $output[] = $order->customer['name'];             //Contact
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
    //print $orders['orders_id'] . $delimiter . "1" . $delimiter . " " . $order->delivery['name']
    return true;
  }
  else
  {
  	print $orders['orders_id'] . "\r\n";
    return false;
  }
}

function print_ukmail_lable($order)
{
  global $billing;
  global $delimiter;
  global $orders; //$orders['orders_id']

  if (!is_postbyukmail($order))
    return false;

  if ($order && is_ukmail($order->delivery['country']))
  {
    //Comments if any
    $instructions1 ="";
    $instructions2 ="";
    if (!preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	if (preg_match('/PayPal/i', $order->info['payment_method'])) {
    		$comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    		if (!empty($comment['comments'])) {
    			if (strlen($comment['comments'])>25) {
    				$instructions1 = substr($comment['comments'], 0, 25);
    				$instructions2 = substr($comment['comments'], 25, 25);
    			}
    			else {
    				$instructions1 = $comment['comments'];
    			}
    		}
    	}
    	else {
    	  $comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    	  if (strlen($comment['comments'])>25) {
    				$instructions1 = substr($comment['comments'], 0, 25);
    				$instructions2 = substr($comment['comments'], 25, 25);
    		}
    		else {
    			$instructions1 = $comment['comments'];
    		}
      }
    }

    $output = array();
    $output[] = $orders['orders_id'];  //Order no
    if (preg_match('/United Kingdom/i', $order->delivery['country']))
    { $output[] = "3"; }
    else
    { $output[] = "5"; }               //Job Type 1: DPD UK 5: International
    $output[] = "";                    //Customer Code: space
    $output[] = (tep_not_null($order->delivery['company'])) ? $order->delivery['company']. "," .$order->delivery['name'] : $order->delivery['name'];  //Name
    $output[] = trim($order->delivery['house_name']) . " " . trim($order->delivery['street_address']);   //Address1
    $output[] = $order->delivery['suburb'];           //Address2
    $output[] = $order->delivery['city'];             //Town
    $output[] = $order->delivery['state'];            //County
    $output[] = $order->delivery['postcode'];         //Postcode
    $output[] = $instructions1;                                   //Instructions1
    $output[] = $instructions2;                                   //Instructions2
    $output[] = "1";                                   //Qty of Labels
    $output[] = "1";                                   //Service
    $output[] = getIso2code($order->delivery['country']);          //country iso2 code
    $output[] = "1";                                  //weight
    $output[] = date("d/m/Y");                        //Delivery date
    $output[] = $order->customer['email_address'];    //Custoemr Email address
    $output[] = $order->customer['telephone'];        //Telephone
    $output[] = $order->customer['name'];             //Contact
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
    //print $orders['orders_id'] . $delimiter . "1" . $delimiter . " " . $order->delivery['name']
    return true;
  }
  else
  {
    return false;
  }
}

function print_ups_lable_header() {
	print "ORDERNUMBER,COMPANY,ATTEN,CUSTOMERNAME,ADDRESS1,ADDRESS2,ADDRESS3,TOWN,COUNTYSTATE,POSTCODE,COUNTRY,ITEMS,WEIGHT,SERVICE,PACKAGETYPE,BILLINGOPTION,TELEPHONE,DESCRIPTION,ref1,ref2,QVN,QVNemail\r\n";
}

function print_ups_lable($order)
{
  global $billing;
  global $delimiter;
  //global $order;
  global $orders; //$orders['orders_id']
  //global $languages_id;
  //global $HTTP_POST_VARS;
  //Set target filename - see above comment on file extension.

  if (!is_postbyups($order))
    return false;

  //if ($order && is_interlink($order->delivery['country']))
  //{
  if (!preg_match('/United Kingdom/i', $order->delivery['country'])) {
    //Comments if any
    $instructions1 ="";
    if (!preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	if (preg_match('/PayPal/i', $order->info['payment_method'])) {
    		$comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    		if (!empty($comment['comments'])) {
    				$instructions1 = $comment['comments'];
    		}
    	}
    	else {
    	  $comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    			$instructions1 = $comment['comments'];
      }
    }

    $output = array();
    $output[] = $orders['orders_id'];  //ORDERNUMBER
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['company']));   //COMPANY
    //$output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->customer['name']));      //ATTEN
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['name']));      //ATTEN
    //$output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->customer['name']));        //CUSTOMERNAME
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['name']));        //CUSTOMERNAME
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['house_name']));   //Address1
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['street_address']));           //Address2
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['suburb']));           //Address3
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->delivery['city']));             //Town
    if ($order->delivery['country']=='Canada' || $order->delivery['country']=='United States') {
    $country_q = tep_db_query("select zone_code from ". TABLE_ZONES.", ".TABLE_COUNTRIES. " where countries.countries_name='".tep_db_input($order->delivery['country'])."' and countries.countries_id=zones.zone_country_id and zones.zone_name='". tep_db_input($order->delivery['state']) ."'");
      if (tep_db_num_rows($country_q)==1) {
    	  $country = tep_db_fetch_array($country_q);
    	  $output[] = $country['zone_code'];            //COUNTYSTATE
      }
      else {
      	$output[] = "";            //COUNTYSTATE
      }
    }
    else {
    	$output[] = $order->delivery['state'];            //COUNTYSTATE
    }
    $output[] = str_replace(",", " ", $order->delivery['postcode']);         //Postcode
    $output[] = getIso2code($order->delivery['country']);          //country iso2 code
    $output[] = "1";                                  //ITEMS
    $output[] = "1";                                  //weight
    $output[] = get_ups_servietype(getIso2code($order->delivery['country']));   //SERVICE
    $output[] = "CP";                                 //PACKAGETYPE
    $output[] = "PP";                                 //BILLINGOPTION
    $output[] = $order->customer['telephone'];        //TELEPHONE
    $output[] = "Hair Products";                                   //DESCRIPTION
    $output[] = $orders['orders_id'];                 //ref1
    $output[] = str_replace(",", " ", $instructions1);  //str_replace(",", " ", $instructions1); //ref2
    $output[] = "Y";                                  //QVN
    $output[] = $order->customer['email_address'];    //QVNemail
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
  }
  else
  {
    return false;
  }
}

function print_rmtrack_lable_header($order_, $orders_query_) {
	print "Reference,Recipient,Recipient Address line 1,Recipient Address line 2,Recipient Address line 3,Recipient Post Town,Recipient Postcode,Recipient Country Code,Service Reference,Service,Service Enhancement,Service Format,Signature,Items,Weight (kgs),Recipient Complementary Name,Safe Place,Recipient Tel #,Recipient Email Address" . "\r\n";
}

function print_rmtrack_lable($order) {
  //check service Id
  //20140402 - royalmail services/code update
  $tracked_countries = array("US", "FI", "NZ", "BE", "DK", "FR", "DE", "HK", "HU", "NL", "PL", "PT", "PT", "SE", "CH", "MT", "CA", "IE");
  $tracked_and_signed_countries = array("CY", "CZ", "GR", "IT", "JP", "LI", "RO", "SI");
  //20140402 - royalmail services/code update

  $service = is_postbyrm($order);
	if ($service) {
    //My modification - validate postcode
    if (!checkPostcode(trim($order->delivery['postcode']), getIso2code($order->delivery['country']))) {
      return false;
    }
    //My modification - validate postcode
		global $delimiter;
		global $orders;

    //20141111
    if ((int)$orders['orders_id'] < 250000 || (int)$oID > 500000) return;
    //20141111

		$preg_alphanumeric_o = "/[^a-zA-Z0-9,.-\s]+/";
		$preg_alphanumeric_r = " ";

    foreach ($order->delivery as &$v) {
  	  $v = transliterateString($v);
    }

		$output = array();

		$output[] = strval($orders['orders_id']);  //Reference
		//$output[] = preg_replace($preg_alphanumeric_o, $preg_alphanumeric_r, str_replace(",", " ", $order->delivery['name']));   //Recipient
		$output[] = str_replace(",", " ", $order->delivery['name']);   //Recipient
		//$output[] = preg_replace($preg_alphanumeric_o, $preg_alphanumeric_r, str_replace(",", " ", $order->delivery['street_address']));  //Recipient Address line 1
		$full_street_address = trim($order->delivery['house_name']) . " " . trim($order->delivery['street_address']);
		if (strlen($full_street_address)>30) {
			$r_reddress_1_a = substr($full_street_address, 0, 26);
			$r_reddress_1_b = substr($full_street_address, 26);
			$output[] = str_replace(",", " ", $r_reddress_1_a);  //Recipient Address line 1
			$output[] = str_replace(",", " ", $r_reddress_1_b . $order->delivery['suburb']);  //Recipient Address line 2
		}
		else {
			$output[] = str_replace(",", " ", $full_street_address);  //Recipient Address line 1
			$output[] = str_replace(",", " ", $order->delivery['suburb']);  //Recipient Address line 2
		}

		//$output[] = preg_replace($preg_alphanumeric_o, $preg_alphanumeric_r, str_replace(",", " ", $order->delivery['suburb']));  //Recipient Address line 2
		$output[] = str_replace(",", " ", $order->delivery['state']); //Recipient Address line 3
		$output[] = str_replace(",", " ", $order->delivery['city']);//Recipient Post Town
		$output[] = str_replace(",", " ", $order->delivery['postcode']); //Recipient Postcode
		$country_code = getIso2code($order->delivery['country']); //Recipient Country Code;
/*
		if ($country_code != "GB") {
			return;
		}
		//20150130
		$temp_skynet = array("AT", "BE", "BG", "CZ", "FR", "DE", "GR", "HU", "IE", "IT", "LU", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "ZA", "CN", "AU", "AL", "BN", "EG", "EE", "HK", "IL", "MT", "TR", "TW");
		if (in_array($country_code, $temp_skynet)) {
			return;
		}
		//20150130
*/

		$output[] = $country_code;
		$output[] = 1; //Service Reference


    $order_tel = str_replace(' ', '', $order->customer['telephone']);
    if (preg_match('/^07/',$order_tel) || preg_match('/^\+447/',$order_tel) || preg_match('/^00447/',$order_tel)) {
    }
    else {
    	$order_tel ="";
    }

//ROYALMAIL_LABEL_STANDARD ROYALMAIL_LABEL_SPECIAL ROYALMAIL_LABEL_SPECIAL9 ROYALMAIL_LABEL_INTERSIGN
    if ($service == ROYALMAIL_LABEL_STANDARD) {
    	$service_id = "STANDARD";//Packet Post Daily Rate 2nd Class
    	$service_enchance = "";
    }
    elseif (preg_match('/Royal Mail Registered/i',$service)) {
    	$service_id = "STANDARD"; //Packet Post Daily Rate 2nd Class
    	$service_enchance = "11";
    }
    elseif ($service == ROYALMAIL_LABEL_SPECIAL9) {
    	$service_id = "SPECIAL9";
      if (tep_not_null($order_tel)) {
      	$service_enchance = "SMS"; //EML-email only, SMS-sms only, SNE-email and sms
      }
      else {
    	  $service_enchance = "";
      }
    }
    elseif ($service == ROYALMAIL_LABEL_SPECIAL) {
    	$service_id = "SPECIAL";
      if (tep_not_null($order_tel)) {
      	$service_enchance = "SMS";
      }
      else {
    	  $service_enchance = "";
      }
    }
    elseif ($service == ROYALMAIL_LABEL_INTERSIGN) {
    	  $service_id = "INTERSIGN";//MP501 - International Contract Signed For
    	  $service_enchance = "";
    }
    elseif ($service == ROYALMAIL_LABEL_AIRMAIL) {
    	  $service_id = "AIR";
    	  $service_enchance = "";
    }

    //weight - get the weight from orders_products
    $weight = 0;
    $orders_products_query = tep_db_query("select p2c.categories_id, op.orders_products_id, op.products_id, p.products_weight, op.products_quantity, op.products_stock_attributes from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where op.orders_id = '" . (int)$orders['orders_id'] . "' and op.products_id=p.products_id and p2c.products_id = op.products_id group by op.products_id");

    //check if the pre-bonded hair is >5 for the format
    $total_prebonded_packs = 0;
    $array_for_stw_goldclip_haircare = array();
    $array_for_haircarepack = array();
    $array_for_curly = array();
    while ($orders_products = tep_db_fetch_array($orders_products_query)) {
    	/*
    	$prebonded_strands = 0;

      if ($prebonded_strands > 0) {
        $weight += (int)$orders_products['products_weight'] * (int)$orders_products['products_quantity'] * $prebonded_strands;
        $total_prebonded_packs += $prebonded_strands;
      }
      else {
      */
      	$weight += (int)$orders_products['products_weight'] * (int)$orders_products['products_quantity'];
      //}
      //check for the following condition: Gold Clip/STW *2, Hair Care, Hair Electrical
      //$cPath = tep_get_product_path($HTTP_GET_VARS['products_id']);
      $cPath_products = tep_parse_path($orders_products['categories_id']);
      $cPath_products_array = explode("_", $cPath_products);
      if ($cPath_products_array[0] == 66 || $cPath_products_array[0] == 249) {
      	$array_for_haircarepack[] = $orders_products['products_id'];
      }
      elseif ($cPath_products_array[1] == 30 || $cPath_products_array[1] == 21 || $cPath_products_array[0] == 51) {
      	$array_for_stw_goldclip_haircare[] = $orders_products['products_id'];
      }
      elseif ($cPath_products_array[2] == 42 || $cPath_products_array[2] == 34 || $cPath_products_array[2] == 39 || $cPath_products_array[1] == 50) {
      	$array_for_curly[] = $orders_products['products_id'];
      }
    }

    if ($service_id == "INTERSIGN") {
    	if ($weight >= 500) {
    		$weight = 495;
    	}
    }
    
    if ($weight > 2000) {
    	if ($service_id == "STANDARD") $weight = 1995;
    }

    //descide Flat or packet
    $pformat = "P";
    if ($weight <= 1) {
    	$pformat = "F";
    }
    /*
    elseif ($total_prebonded_packs >= 4) {
    	$pformat = "P";
    }
    */
    /*
    elseif (sizeof($array_for_stw_goldclip_haircare)>0 ) {
    	if ($weight >= 330) {
    		$pformat = "P";
    	}
    }
    elseif (sizeof($array_for_haircarepack)>0) {
    	if ($weight >= 300) {
    		$pformat = "P";
    	}
    }
    elseif (sizeof($array_for_curly)>0) {
    	$pformat = "P";
    }
    */
    if ($service_id == "SPECIAL" || $service_id == "SPECIAL9") {
    	$pformat = "N";
    }
    elseif ($service_id == "INTERSIGN") {
    	//if ($pformat == "F") $pformat = "IF"; //20140331 - MTM     if ($pformat == "F") {$pformat = "IF";$service_id == "MTM"}
    	//elseif ($pformat == "P") $pformat = "IP";

      //20140402 - royalmail services/code update
      /*
      INTLMS - Intl bus Mail Signed
      INTLMT - Intl bus Mail Tracked
      INTLMTS - Intl bus Mail Tracked&Signed
      INTLPTS - Intl bus Parcel Tracked&Signed
      INTERSIGN - Intl bus Parcel Signed
      AIRSURE - Intl bus Parcel Tracked
      F - Inland Large letter
      P - Inland Parcel
      L - Inland letter
      IL - International Letter
      IF - International Large Letter
      IP - International Parcel
      N - inland not applicable
      STANDARD - 48/2nd class
      FIRST - 24/1nd class
      */
    	//$pformat = "IP";
    	if ($pformat == "P") {
    		$pformat = "IP"; //parcel
    	  if (in_array($country_code, $tracked_countries)) {
    		  $service_id = "AIRSURE";
        }
        elseif (in_array($country_code, $tracked_and_signed_countries)) {
        	$service_id = "INTLPTS";
        }
        else {
        	$service_id = "INTERSIGN";
        }
    	}
    	else {
    		$pformat = "IF"; //large letter
    	  if (in_array($country_code, $tracked_countries)) {
    		  $service_id = "INTLMT";
        }
        elseif (in_array($country_code, $tracked_and_signed_countries)) {
        	$service_id = "INTLMTS";
        }
        else {
        	$service_id = "INTLMS";
        }
      }
      //20140402 - royalmail services/code update

    }
    elseif ($service_id == "AIR") {
    	$pformat = "IL";
    }
    //

		$output[] = $service_id; //Service
		$output[] = $service_enchance; //Service Enhancement

		$output[] = $pformat;//Service Format
		$output[] = "";//Signature
		$output[] = 1;//Items
		if ($weight < 1) $weight = 50;
		$output[] = $weight;
		//$output[] = preg_replace($preg_alphanumeric_o, $preg_alphanumeric_r, str_replace(",", " ", $order->delivery['company']));//Recipient Complementary Name
		$output[] = str_replace(",", " ", $order->delivery['company']);//Recipient Complementary Name

    if (!preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
      $comment = tep_db_fetch_array(tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id='". (int)$orders['orders_id'] . "' and orders_status_id='1'"));
    	$safeplace = trim(str_replace(",", "", $comment['comments']));
    	//$safeplace = preg_replace($preg_alphanumeric_o, $preg_alphanumeric_r, str_replace(",", " ", $safeplace));
    }
		$output[] = (strlen($safeplace) > 30) ? substr($safeplace, 0, 30) : $safeplace;//Safe Place
		$output[] = $order_tel; //added on 05/08/2013
		$output[] = $order->customer['email_address']; //added on 05/08/2013

    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
	}
}

function print_rmtrack_lable_trailer($from_header) {
	print "~9~" . $from_header['batch_number'] . "~" . $from_header['count'] . "~~";// . "\r\n";
	//return array('batch_number' => $batch_number, 'count' => $number);
}
/*
function is_interlink($country) {
	//$interlink = array("FR","IE","BE","DE","LU","NL","AT","DK","LI","CH","CZ","IT","SK","ES","EE","FI","HU","PL","PT","SI","SE","BA","BG","HR","LV","LT","RO");
	$interlink_str="";
	$get_interlink_iso2_query = tep_db_query("select configuration_value from configuration where configuration_key like '%MODULE_SHIPPING_INTERLINK_COUNTRIES%' or configuration_key like '%MODULE_SHIPPING_INTERLINKUK_COUNTRIES%'");
  while ($get_interlink_iso2 = tep_db_fetch_array($get_interlink_iso2_query)) {
  	$interlink_str .= trim($get_interlink_iso2['configuration_value']) . ",";
  }
  $interlink_str = substr($interlink_str, 0, -1);
  $interlink = explode(",", $interlink_str);
	//$code = tep_db_fetch_array(tep_db_query("select countries_iso_code_2 from " . TABLE_COUNTRIES . " where countries_name='" . $country . "'"));
  if (in_array(getIso2code($country), $interlink))
    return true;
  else
    return false;
}
*/

//201512
function print_lowcostparcel_lable_header($order_, $orders_query_) {
	print "Contact Name,Department,Phone Number,Email Address,Address 1,Address 2,Town,County,Postcode,Customer Ref,Internal Ref,Custom Field,Order ID,Items,Service ID" . "\r\n";
}

function print_lowcostparcel_lable($order) {
  $service = is_postbylc($order);
	if ($service) {
    //My modification - validate postcode
    if (!checkPostcode(trim($order->delivery['postcode']), getIso2code($order->delivery['country']))) {
      return false;
    }
    //My modification - validate postcode
		global $delimiter;
		global $orders;

    //20141111
    if ((int)$orders['orders_id'] < 250000 || (int)$oID > 500000) return;
    //20141111

		$preg_alphanumeric_o = "/[^a-zA-Z0-9,.-\s]+/";
		$preg_alphanumeric_r = " ";

    foreach ($order->delivery as &$v) {
  	  $v = transliterateString($v);
    }

		$output = array();
		$output[] = str_replace(",", " ", $order->delivery['name']); //Contact Name
		$output[] = str_replace(",", " ", $order->delivery['company']);//Department , company here
		$output[] = str_replace(' ', '', $order->customer['telephone']); //Phone Number
		$output[] = $order->customer['email_address']; //Email Address
		$full_street_address = trim($order->delivery['house_name']) . " " . trim($order->delivery['street_address']);
		if (strlen($full_street_address)>30) {
			$r_reddress_1_a = substr($full_street_address, 0, 26);
			$r_reddress_1_b = substr($full_street_address, 26);
			$output[] = str_replace(",", " ", $r_reddress_1_a);  //Address 1
			$output[] = str_replace(",", " ", $r_reddress_1_b . $order->delivery['suburb']);  //Address 2
		}
		else {
			$output[] = str_replace(",", " ", $full_street_address);  //Address 1
			$output[] = str_replace(",", " ", $order->delivery['suburb']);  //Address 2
		}
		
		$output[] = str_replace(",", " ", $order->delivery['city']); //Town
		$output[] = str_replace(",", " ", $order->delivery['state']); //County
		$output[] = str_replace(",", " ", $order->delivery['postcode']); //Postcode
		$output[] = strval($orders['orders_id']); //Customer Ref
		$output[] = ""; //strval($orders['orders_id']); //Internal Ref
		$output[] = ""; //Custom Field
		$output[] = ""; //strval($orders['orders_id']); //Order ID
		$output[] = ""; //Items
		
		//Service ID
		if ($service == INTERLINK_LABEL) { //next day
			$output[] = "130"; //next day
		}
		elseif ($service == LOWCOST_LABEL_STANDARD) { //packet/economy
			if (is_large_size($order)) $output[] = "310"; //economy
		  else $output[] = "340"; //packet
		}

    /*
    Economy - 310
    Next Day - 130
    Highlands and Islands - 510
    Northern Ireland - 320
    Packet 3kg - 330
    Packet 1kg - 340
    */
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
	}
}

function is_ukmail($country) {
	$ukmail_str="";
	$get_ukmail_iso2_query = tep_db_query("select configuration_value from configuration where configuration_key like '%MODULE_SHIPPING_UKMAILUK_COUNTRIES%'");
  while ($get_ukmail_iso2 = tep_db_fetch_array($get_ukmail_iso2_query)) {
  	$ukmail_str .= trim($get_ukmail_iso2['configuration_value']) . ",";
  }
  $ukmail_str = substr($ukmail_str, 0, -1);
  $ukmail = explode(",", $ukmail_str);
  if (in_array(getIso2code($country), $ukmail))
    return true;
  else
    return false;
}

function getIso2code($country) {
 	//$interlink = array("FR","IE","BE","DE","LU","NL","AT","DK","LI","CH","CZ","IT","SK","ES","EE","FI","HU","PL","PT","SI","SE","BA","BG","HR","LV","LT","RO");
 	/*$interlink_str="";
	$get_interlink_iso2_query = tep_db_query("select configuration_value from configuration where configuration_key like '%MODULE_SHIPPING_INTERLINK_COUNTRIES%'");
  while ($get_interlink_iso2 = tep_db_fetch_array($get_interlink_iso2_query)) {
  	$interlink_str .= trim($get_interlink_iso2['configuration_value']) . ",";
  }
  $interlink_str = substr($interlink_str, 0, -1);
  $interlink = explode(",", $interlink_str);*/
	$code = tep_db_fetch_array(tep_db_query("select countries_iso_code_2 from " . TABLE_COUNTRIES . " where countries_name='" . $country . "'"));
	return $code['countries_iso_code_2'];
}

//check if it use ups express saver
function get_ups_servietype($country) {
  //SV - Express Saver
  //ST - Standard (All EU plus Liechtenstein, Norway, Switzerland)
  $eu_countries = array("AT","BE","BG","HR","CY","CZ","DK","EE","FI","FR","DE","GR","HU","IE","IT","LV","LT","LU","MT","NL","PL","PT","RO","SI","ES","SE","GB","CH","NO","LI");
  if (in_array($country, $eu_countries)) {
    return "ST";
  }
  else
    return "SV";
}

  //My modification - if the categories_id is not a full path then parse it into full path e.g. 51_278_186_187, 186->51_278_186, 278_186->51_278_186
/*  function tep_parse_path($categories_id) {
  	$cid_q = tep_db_query("select categories_id from " . TABLE_CATEGORIES ." where categories_id='" . (int)$categories_id . "'");
  	if (tep_db_num_rows($cid_q)>0) {
      $cPath = '';
      $categories = array();
      tep_get_parent_categories($categories, $categories_id);
      $categories = array_reverse($categories);
      $cPath = implode('_', $categories);
      if (tep_not_null($cPath)) $cPath .= '_';
      $cPath .= $categories_id;
      return $cPath;
    }
    else {
    	return '';
    }
  }
  */

function print_ekomi_feedback_header() {
	print "ORDERNUMBER,CUSTOMERNAME,CUSTOMEREMAIL\r\n";
}

function print_ekomi_feedback($order)
{
  global $startdate,$enddate;
  //global $HTTP_POST_VARS;
  global $delimiter;
  global $orders; //$orders['orders_id']

  //My modification - if more than 2 history ignore
  $orders_history_count = tep_db_fetch_array(tep_db_query("select count(*) as c1 from " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id ='" . (int)$orders['orders_id'] . "'"));
  if ($orders_history_count['c1'] >2) {
    return false;
  }
  //My modification - if more than 2 history ignore

  $skip_email = array("longlocksextensions@hotmail.co.uk", "accounts@harlandcorp.co.uk");
  //$orders_query1 = tep_db_fetch_array(tep_db_query("select orders_status, cash_carry, customers_id, customers_email_address from " . TABLE_ORDERS . " where orders_id='" . (int)$orders['orders_id'] . "'"));
  $orders_query1 = tep_db_query("select o.orders_status, o.cash_carry, o.customers_id, o.customers_email_address from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_STATUS_HISTORY . " h on o.orders_id=h.orders_id where o.orders_id='" . (int)$orders['orders_id'] . "' and length(h.tracking_no)>1 and h.date_added between concat(date_add(curdate(), INTERVAL -14 day), ' 00:00:00') and concat(date_add(curdate(), INTERVAL -8 day), ' 23:59:59')");

  if (!tep_db_num_rows($orders_query1)) {
  	return false;
  }

  $order_info = tep_db_fetch_array($orders_query1);
  if (in_array($order_info['customers_email_address'], $skip_email)) {
  	return false;
  }
  elseif ($order_info['customers_email_address'] ==3) {
  	return false;
  }
  if ($order->info['orders_status'] ==3 && $order_info['cash_carry'] == 0) {
    $output = array();
    $output[] = $orders['orders_id'];  //ORDERNUMBER
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $order->customer['name']));        //CUSTOMERNAME
    $output[] = $order->customer['email_address'];    //Email
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
  }
  else
  {
    return false;
  }
}

function print_sagepayhr_header() {
	print "ORDERNUMBER,HRINFO,POSTCODE\r\n";
}

function print_sagepayhr($order)
{
  //global $HTTP_POST_VARS;
  global $delimiter;
  global $orders; //$orders['orders_id']

  //My modification 
  if ($order_info['cash_carry'] != 0) return false;

  $rishinfo = sagepay_risk_check($orders['orders_id']);
  if (!tep_not_null($rishinfo)) {
  	return false;
  }
  //My modification
  if (($order->info['orders_status'] ==1 || $order->info['orders_status'] ==101 || $order->info['orders_status'] ==12)&& $order_info['cash_carry'] == 0) {
    $output = array();
    $output[] = $orders['orders_id'];  //ORDERNUMBER
    $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $rishinfo));
    $output[] = $order->delivery['postcode'];
    $dataRowString = implode($delimiter, $output);
    print $dataRowString . "\r\n";
  }
}
/*
////
// Recursively go through the categories and retreive all parent categories IDs
// TABLES: categories
  function tep_get_parent_categories(&$categories, $categories_id) {
    $parent_categories_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$categories_id . "'");
    while ($parent_categories = tep_db_fetch_array($parent_categories_query)) {
      if ($parent_categories['parent_id'] == 0) return true;
      $categories[sizeof($categories)] = $parent_categories['parent_id'];
      if ($parent_categories['parent_id'] != $categories_id) {
        tep_get_parent_categories($categories, $parent_categories['parent_id']);
      }
    }
  }
*/

function print_comments($x, $y, $nums)
{
  global $pdf, $num, $billing;
  $pos = $y;
  global $orders_query;
  global $order;
  global $orders;
  global $languages_id;
  global $HTTP_POST_VARS;
  if ($order)
  {
    	if (is_postbyrm($order)) {
        $status_query = tep_db_query("select comments from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$orders['orders_id'] . "' and orders_status_id = '1' and customer_notified='1' and updated_by is null and CHAR_LENGTH(comments) >0 order by date_added limit 1");
        if (tep_db_num_rows($status_query)>0) {
    	    $status_result = tep_db_fetch_array($status_query);
    	    $fontsize = GENERAL_FONT_SIZE;

          $comment_string = html_entity_decode(html_encode($status_result['comments']));
          $comment_string1= trim(cut($comment_string,25));
          $comment_string2= trim(str_replace($comment_string1, "", $comment_string));

    	    if (tep_not_null($comment_string2)) {
    	    	$pdf->addText($x+0,$pos -=(GENERAL_LINE_SPACING+5),ORDERIDFONTSIZE+1,$comment_string1);
    	    	if (strlen($comment_string2)<=25) {
    		      $pdf->addText($x+0,$pos -=(GENERAL_LINE_SPACING),ORDERIDFONTSIZE+1,$comment_string2);
    		    }
    		    else {
    		    	$comment_string21= trim(cut($comment_string2,25));
              $comment_string22= trim(str_replace($comment_string21, "", $comment_string2));
              $pdf->addText($x+0,$pos -=(GENERAL_LINE_SPACING),ORDERIDFONTSIZE+1,$comment_string21);
              $pdf->addText($x+0,$pos -=(GENERAL_LINE_SPACING),ORDERIDFONTSIZE+1,$comment_string22);
    		    }
        	}
        	else {
    	    	$pdf->addText($x+0,$pos -=(GENERAL_LINE_SPACING+5),ORDERIDFONTSIZE+1,$comment_string1);
        	}
      //$pdf->addText($x+10,$pos -=(GENERAL_LINE_SPACING),ORDERIDFONTSIZE+1,html_entity_decode(html_encode($status_result['comments'])));
        //$pdf->addText($x + ORDERIDXOFFSET -10 ,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,"Delivery Note:");  //Delivery Note:
        //$pdf->addText($x + LABEL_WIDTH - ORDERIDXOFFSET - 28,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,$orders['orders_id']);  //Order Number:
          $pdf->addText($x,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,"Delivery Note:");  //Delivery Note:
          $pdf->addText($x + LABEL_WIDTH - ORDERIDXOFFSET - 30,$y + ORDERIDYOFFSET,ORDERIDFONTSIZE,$orders['orders_id']);  //Order Number:
        
        }
        else {
          if(($nums % NUM_LABELS_PER_PAGE) == (NUM_LABELS_PER_PAGE-1))
          {
            $order = false;
            return false;
          }
          else
          {
            if($orders = tep_db_fetch_array($orders_query))
            {
              $order = new order($orders['orders_id']);
              print_comments($x, $y, $nums);
              return true;
            }
            else
            {
              $order = false;
              return false;
            }
          }
        }
    	}
      else {
        if(($nums % NUM_LABELS_PER_PAGE) == (NUM_LABELS_PER_PAGE-1))
        {
          $order = false;
          return false;
        }
        else
        {
          if($orders = tep_db_fetch_array($orders_query))
          {
            $order = new order($orders['orders_id']);
            print_comments($x, $y, $nums);
            return true;
          }
          else
          {
            $order = false;
            return false;
          }
        }
      }

/*
    if ($HTTP_POST_VARS['status'] && ($HTTP_POST_VARS['status'] != $order->info['orders_status']))
    {
      $customer_notified = 0; 
      $status = tep_db_prepare_input($HTTP_POST_VARS['status']);
      $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, BATCH_COMMENTS) . "\n\n";

      if ($HTTP_POST_VARS['notify'])
      {
        $status_query = tep_db_query("select orders_status_name as name from " . TABLE_ORDERS_STATUS . " where language_id = " . $languages_id . " and orders_status_id = " . tep_db_input($status));
        $status_name = tep_db_fetch_array($status_query);

        $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $orders['orders_id'] . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($order->info['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $status_name['name']);
        tep_mail($order->customer['name'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, nl2br($email), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        $customer_notified = '1';
      }

      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . $orders['orders_id'] . "'");
      tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . $orders['orders_id'] . "', '" . tep_db_input($status) . "', now(), '" . $customer_notified . "', '" . $notify_comments  . "')");
    }
*/
    if(($nums % NUM_LABELS_PER_PAGE) == (NUM_LABELS_PER_PAGE-1))
    {
      $order = false;
      return false;
    }
    else
    {
      if($orders = tep_db_fetch_array($orders_query))
      {
        $order = new order($orders['orders_id']);
        return true;
      }
      else
      {
        $order = false;
        return false;
      }
    }
  }
  else
  {
    return false;
  }
}

 //cut a string with out breaking the words
 function cut($string, $max_length) {  
     if (strlen($string) > $max_length){  
         $string = substr($string, 0, $max_length);  
         $pos = strrpos($string, " ");  
         if($pos === false) {  
                 return substr($string, 0, $max_length);  
         }  
             return substr($string, 0, $pos);  
     }else{  
         return $string;  
     }  
 }


?>