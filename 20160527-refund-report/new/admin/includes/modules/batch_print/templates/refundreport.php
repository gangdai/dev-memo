<?php

if (!function_exists('print_refund_report_header')) {
function print_refund_report_header() {
	print "ORDERNUMBER,CUSTOMERNAME,RETURN REASON,AMOUNT,PAYMENT,DATE,COUNTRY\r\n";
}
}

if (!function_exists('print_refund_report_ln')) {
function print_refund_report_ln() {
  global $delimiter;
  global $orders; //$orders['orders_id']
  global $orders_returned_reasons_array;
  global $payment_type;

  if (!isset($orders['orders_total_id'])) return false;
  $output[] = $orders['orders_id']; //ORDERNUMBER
  $output[] = iconv('UTF-8', 'Windows-1252', str_replace(",", " ", $orders['billing_name'])); //CUSTOMERNAME
  if (tep_not_null($orders['orders_returned_reasons_id']))
    $output[] = $orders_returned_reasons_array[$orders['orders_returned_reasons_id']]; //RETURN REASON
  else
    $output[] = ""; //RETURN REASON
  $output[] = $orders['value'];
  $output[] = $orders['payment_method'];
  $output[] = tep_date_short($orders['date_added']);
  $output[] = $orders['billing_country'];
  $dataRowString = implode($delimiter, $output);
  print $dataRowString . "\r\n";
  $payment_type[$orders['payment_method']] += $orders['value'];
}
}

// set paper type and size
if ($pageloop == "0") {
//$pdf = new Cezpdf(A4,portrait);
} else {

if ($HTTP_POST_VARS['address']) { 
  if ($HTTP_POST_VARS['address'] == "billing") $billing = true;
  else $billing = false;
}
else { $billing = false; }

if (!tep_db_num_rows($orders_query) > 0) { message_handler('NO_ORDERS'); }




//change_color(GENERAL_FONT_COLOR);
$filename= date("Ymd") . '.csv';
$delimiter = ",";

//Send headers
//header("Content-type: application/octet-stream");
//header('Content-type: text/html; charset=utf-8');
header('Content-type: text/plain;');
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($orders_returned_reasons_array)) {
  $orders_returned_reasons_array = array();
  $orders_returned_reasons_query = tep_db_query("select orders_returned_reasons_id, orders_returned_reasons_name from " . TABLE_ORDERS_RETURNED_REASONS . " where language_id = '" . (int)$languages_id . "' order by orders_returned_reasons_id");
  while ($orders_returned_reason = tep_db_fetch_array($orders_returned_reasons_query)) {
    $orders_returned_reasons_array[$orders_returned_reason['orders_returned_reasons_id']] = $orders_returned_reason['orders_returned_reasons_name'];
  }
}

//First tep_db_fetch_array() is run in batch_print.php so first value $order is ready
//$neworderid = print_refund_report_header();
  $payment_type = array();
  print_refund_report_header();
  print_refund_report_ln();
  while ($orders = tep_db_fetch_array($orders_query)) {
    //$order = new order($orders['orders_id']);
    print_refund_report_ln();
    // Send fake header to avoid timeout, got this trick from phpMyAdmin
    $time1  = time();
    if ($time1 >= $time0 + 30) {
      $time0 = $time1;
      header('X-bpPing: Pong');
    }
  }// EOWHILE

  if (sizeof($payment_type)) {
  	print "\r\n";
  	$total_refund = 0;
  	foreach ($payment_type as $k => $v) {
  		print $k . "," . $v . "\r\n";
  		$total_refund +=$v;
  	}
  	print "Total," . $total_refund . "\r\n";
  }
}

?>