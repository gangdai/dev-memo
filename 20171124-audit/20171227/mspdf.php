<?php
  /*
    201701110
    /admin/mspdf.php
    
    //OSCLogger::writeLog("07958256524", OSCLogger::DEBUG);
  */

set_time_limit(400);
ini_set('memory_limit','256M');
require('includes/application_top.php');

include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_BATCH_PRINT);
include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ORDERS_INVOICE);
    function change_color($color) {
      global $pdf;
      list($r,$g,$b) = explode(',', $color);
      $pdf->setColor($r,$g,$b);
    }

    require(DIR_WS_CLASSES . 'currencies.php');
    require(BATCH_PRINT_INC . 'class.ezpdf.php');
    require(DIR_WS_CLASSES . 'order.php');

    require(BATCH_PRINT_INC . 'chars.php');
    $pageloop = "0";
    require(BATCH_PRINT_INC . 'templates/invoice_temp.php');
    $pageloop = "1";

    $orderlist = [];
    $onlineshop = " cash_carry !=0 and orders_ref_1 >0 ";
    $date_range = "date_purchased >= '2015-04-01' and date_purchased < '2016-04-07'";
    $orders_query_raw = "select o.orders_id, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' and " . $onlineshop . " and " . $date_range . " order by o.orders_id DESC";

//OSCLogger::writeLog($orders_query_raw, OSCLogger::DEBUG);
//exit;
    $orders_query = tep_db_query($orders_query_raw);
    while ($orders = tep_db_fetch_array($orders_query)) {
        //$content .= $orders['orders_id'] . "," . $orders['customers_name'] . "," . tep_date_short($orders['date_purchased']) . "," . $orders['orders_status_name'] . "," . preg_replace("/&pound;/", "", strip_tags($orders['order_total'])) . "\r\n";
        $orderlist[] = $orders['orders_id'];
    }

    // do your Db stuff here to get the content into $content
    //echo "This is some text...\n";
    //echo substr($orderlist, 0, -1);

    //$a1 = explode(',', $invoicenumbers);
    $orderlist_chunk = array_chunk($orderlist, 100);
//OSCLogger::writeLog($orderlist_chunk, OSCLogger::DEBUG);
//exit;
    $newdoc = 0;
    foreach ($orderlist_chunk as $v) {

        $savefilename = reset($v) . "-" . end($v) . ".pdf";
        reset($v);
        $num1 =0;

        if ($newdoc>0) { $pdf = new Cezpdf(A4,portrait); }
        foreach ($v as $v1) {
              $order = new order($v1);
              if ($num1 != 0) { $pdf->EzNewPage();}
              $orders['orders_id'] = $v1;
              // start of pdf layout ..   ################################
              require(BATCH_PRINT_INC . 'templates/invoice_temp.php');
              // end pdf layout section   ###############################

                $num1++;
                //Send fake header to avoid timeout, got this trick from phpMyAdmin
                $time1 = time();
                if ($time1 >= $time0 + 30) {
                    $time0 = $time1;
                    header('X-bpPing: Pong');
                }
        }

//OSCLogger::writeLog(reset($v) . "-" . end($v) . ".pdf", OSCLogger::DEBUG);
//OSCLogger::writeLog($v, OSCLogger::DEBUG);
        $pdf_code = $pdf->output();

        //$savefilename = reset($v) . "-" . end($v) . ".pdf";
        $fname = BATCH_PDF_DIR . $savefilename;
        if($fp = fopen($fname,'w')) {
            fwrite($fp,$pdf_code);
            fclose($fp);
        }
        else { message_handler('FAILED_TO_OPEN'); }

        $newdoc++;
    }

exit;

  require(DIR_WS_INCLUDES . 'template_top.php');

?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo "Manual"; ?></td>
            <td class="pageHeading" align="right"></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
<?php

?>
        </td>
      </tr>

    </table>

<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>