<?php
  /*
    20170701
    /admin/ms.php
    
    //OSCLogger::writeLog("07958256524", OSCLogger::DEBUG);
  */

  set_time_limit(200);

  require('includes/application_top.php');

  header("Content-type: text/plain");
  header("Content-Disposition: attachment; filename=shop-orders.csv");

    $onlineshop = " cash_carry !=0 and orders_ref_1 >0 ";
    $date_range = "date_purchased >= '2015-04-01' and date_purchased < '2016-04-07'";
    $orders_query_raw = "select o.orders_id, o.customers_name, o.payment_method, o.date_purchased, o.last_modified, o.currency, o.currency_value, s.orders_status_name, ot.text as order_total from " . TABLE_ORDERS . " o left join " . TABLE_ORDERS_TOTAL . " ot on (o.orders_id = ot.orders_id), " . TABLE_ORDERS_STATUS . " s where o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and ot.class = 'ot_total' and " . $onlineshop . " and " . $date_range . " order by o.orders_id DESC";

    $orderlist = "order id, customer name, date purchased, orders_status, order total (GBP)" . "\r\n";
    $orders_query = tep_db_query($orders_query_raw);
    while ($orders = tep_db_fetch_array($orders_query)) {
        $orderlist .= $orders['orders_id'] . "," . $orders['customers_name'] . "," . tep_date_short($orders['date_purchased']) . "," . $orders['orders_status_name'] . "," . preg_replace("/&pound;/", "", strip_tags($orders['order_total'])) . "\r\n";
        //$orderlist .= $orders['orders_id'] . ",";
    }

    // do your Db stuff here to get the content into $content
    //echo "This is some text...\n";
    echo substr($orderlist, 0, -1);

exit;
   

?>
