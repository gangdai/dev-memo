<?php
  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  if (!isset($HTTP_GET_VARS['order_id']) || (isset($HTTP_GET_VARS['order_id']) && !is_numeric($HTTP_GET_VARS['order_id']))) {
    tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  }

  $customer_info_query = tep_db_query("select o.customers_id from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS . " s where o.orders_id = '". (int)$HTTP_GET_VARS['order_id'] . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.public_flag = '1'");
  $customer_info = tep_db_fetch_array($customer_info_query);
  if ($customer_info['customers_id'] != $customer_id) {
    tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_HISTORY_INFO);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
  $breadcrumb->add(sprintf(NAVBAR_TITLE_3, $HTTP_GET_VARS['order_id']), tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $HTTP_GET_VARS['order_id'], 'SSL'));

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order($HTTP_GET_VARS['order_id']);
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>
      <meta name="description" id="description" content="" />
      <meta name="keywords" id="keywords" content="" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />


      <meta name="audience" content="all" />
      <meta name="distribution" content="global" />
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
      <meta http-equiv="Content-Language" content="EN-GB" />
      <meta name="rights-standard" content="<?php echo STORE_NAME;?>" />

      <!--CSS-->
      <link rel="stylesheet" href="css/normalize.css" />
      <link rel="stylesheet" href="styles.css" />

      <link rel="shortcut icon" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'fav_icon.ico';?>" />
      <link rel="apple-touch-icon-precomposed" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'ios_icon.png';?>" />

      <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" />
      <link rel="stylesheet" href="css/responsive.css" />

      <!--JS-->
      <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
      <script src="//code.jquery.com/jquery-migrate-1.4.0.min.js"></script>

      <script src="js/featherlight.min.js" defer></script>

      <script src="js/jquery.mmenu.min.js" defer></script>
      <script src="js/2017.js" defer></script>

      <!--CSS-->
      <link rel="stylesheet" href="css/jquery.mmenu.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.positioning.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.pagedim.css" />

      <link rel="stylesheet" href="css/featherlight.min.css" />

		  <script>
        /*<![CDATA[*/
        $(document).ready(function(){
        });
        /*]]>*/
		  </script>

    </head>














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">





            <div class="inner">
            	<div class="grid-02">
				        <div class="grid-02-01">
					          <?php $help_column=true; require(DIR_WS_INCLUDES . 'column_left.php');?>
					      </div><!--/.grid-02-01-->

                <div class="grid-02-02">

                	  <div class="readable-text single-order-page">
                		  <!--<h1><?php echo HEADING_TITLE; ?></h1>-->
                		  
  						        <div id="account-details">
							          <p><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL') . '#tab-reviews" target="_blank"><i class="fa fa-angle-double-left"></i>BACK</a><br /><br />' . sprintf(HEADING_ORDER_NUMBER, $HTTP_GET_VARS['order_id']); ?></p>
						          </div><!--/#account-details-->

    					        <p><?php echo '<strong>' . HEADING_ORDER_DATE . '</strong> ' . tep_date_long($order->info['date_purchased']); ?></p>
                      <p><?php echo '<strong>' . HEADING_ORDER_TOTAL . '</strong> ' . $order->info['total']; ?></p>
						          <p><?php echo '<strong>' . HEADING_PAYMENT_METHOD . '</strong> ' . $order->info['payment_method']; ?></p>

						          <div class="inner-left">
    						            <p><?php echo '<strong>' . HEADING_DELIVERY_ADDRESS . '</strong>';?></p>
                          	<p><?php echo tep_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br />'); ?></p>
    					        </div><!--/.inner-left-->

                      <div class="inner-right">
                        	  <p><?php echo '<strong>' . HEADING_BILLING_ADDRESS . '</strong>'; ?></p>
                            <p><?php echo tep_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br />'); ?></p>
                      </div><!--/.inner-right-->
    					        <div class="clearme"></div>

    					        <p>
                        <?php
                            echo '<strong>' . HEADING_ORDER_HISTORY . '</strong><br />' . "\n";
                            $statuses_query = tep_db_query("select os.orders_status_name, osh.date_added, osh.comments from " . TABLE_ORDERS_STATUS . " os, " . TABLE_ORDERS_STATUS_HISTORY . " osh where osh.orders_id = '" . (int)$HTTP_GET_VARS['order_id'] . "' and osh.orders_status_id = os.orders_status_id and os.language_id = '" . (int)$languages_id . "' and os.public_flag = '1' and osh.customer_notified ='1' order by osh.date_added");
                            while ($statuses = tep_db_fetch_array($statuses_query)) {
                            	echo '<em>' . tep_date_short($statuses['date_added']) . '</em> - ' . $statuses['orders_status_name'] . '<br />';
                            }
		                    ?>
                      </p>

    					        <p><strong><?php echo HEADING_PRODUCTS; ?></strong></p>
                        <table id="table-zebra-stripe" class="table-single-order">
                        	  <thead>
                            	<tr>
                            		<th class="col-01">Quantity</th>
                                    <th class="col-02">Product</th>
                                    <th class="col-022"></th>
                                    <th class="col-03">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $count=0;
                                for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
                                  if ($count % 2 == 0) {
                                    echo '                                <tr class="zebra-even">' . "\n";
                                  }
                                  else {
                                    echo '                                <tr class="zebra-odd">' . "\n";
                                  }
                                  echo '                                  <td class="col-01">' . $order->products[$i]['qty'] . '</td>' . "\n";

                                  echo '                                  <td class="col-02"><strong>' . ((is_coloursample_pid($order->products[$i]['id'])) ? "Sample " : "") . $order->products[$i]['name'] . '</strong>' . "\n";
                                  if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
                                    for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                                      echo '                                    <br /><span>' . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'] . '</span>' . "\n";
                                    }
                                  }
                                  elseif (isset($order->products[$i]['products_variants']) && sizeof($order->products[$i]['products_variants'])) {
                                  	foreach ($order->products[$i]['products_variants'] as $v) {
                                  		echo '                                    <br /><span>' . $v['group'] . ': ' . $v['value'] . '</span>' . "\n";
                                  	}
                                  }
                                  echo '                                  </td>' . "\n";
                                  echo '                                  <td class="col-022">' . ((!is_coloursample_pid($order->products[$i]['id'])) ? '<a href="' . tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=' . $order->products[$i]['id']) . '#tab-reviews" target="_blank"><u>Write a review</u></a>' : "") . '</td>' . "\n";
                                  echo '                                  <td class="col-03">' . $currencies->format(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'], true, $order->info['currency'], $order->info['currency_value']) . '</td>' . "\n";
                                  echo '                                </tr>' . "\n";
                                  $count++;
                                }
                            ?> 
                            </tbody>

                            <tfoot>
                            <?php
                                $count=0;
                                for ($i=0, $n=sizeof($order->totals); $i<$n; $i++) {
                                  if ($count==0) {
                                    echo '                                <tr class="row-total-01">' . "\n";
                                  }
                                  else {
                                    echo '                                <tr class="row-total-02">' . "\n";
                                  }
                                  echo '                                  <td class="col-03" colspan="3">' . $order->totals[$i]['title'] . '</td>' . "\n";
                                  echo '                                  <td class="col-03">' . $order->totals[$i]['text'] . '</td>' . "\n";
                                  echo '                                </tr>' . "\n";
                                  $count++;
                                }
                            ?>
                            </tfoot>
                        </table><!--/.table-zebra-stripe table-single-order-->
                   	</div><!--/.readbale-text .single-order-page-->  

                </div><!--/.grid-02-02-->

                <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
                <div class="clearme"></div>
            	</div><!--/.grid-02-->
            </div><!--/.inner-->





        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>