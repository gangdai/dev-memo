<?php


  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_HISTORY);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));
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
      <script src="js/jquery.easyPaginate.js" defer></script>

      <?php require('includes/form_check.js.php'); ?>

		  <script>
        /*<![CDATA[*/
        $(document).ready(function() {
          $('#orderslist').easyPaginate({
              paginateElement: 'div',
              elementsPerPage: 10,
              effect: 'fade',
              firstButtonText: '<i class="fa fa-angle-double-left"></i>',
              lastButtonText: '<i class="fa fa-angle-double-right"></i>',
              prevButtonText: '<i class="fa fa-angle-left"></i>',
              nextButtonText: '<i class="fa fa-angle-right"></i>'
          });
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
          	<h1><?php echo HEADING_TITLE; ?></h1>
          	<div class="grid-01">
          		  <div class="grid-01-02 right">
                	  <div class="table-wrapper readable-text">
                	      <h2 class="sub-title"><?php echo HEADING_TITLE; ?></h2><br />
                	      <div id="orderslist">
                	  	  <?php
                	  	    $orders_total = tep_count_customer_orders();
                	  	    if ($orders_total > 0) {
                	  	    	$history_query_raw = "select o.orders_id, o.date_purchased, o.delivery_name, o.billing_name, ot.text as order_total, s.orders_status_name from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$customer_id . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.public_flag = '1' order by orders_id DESC";

                            $history_split = new splitPageResults($history_query_raw, MAX_DISPLAY_ORDER_HISTORY);
                            $history_query = tep_db_query($history_split->sql_query);
                            $count=0;
                            
                            $history_query = tep_db_query($history_query_raw);
                            while ($history = tep_db_fetch_array($history_query)) {
                              $products_query = tep_db_query("select count(*) as count from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$history['orders_id'] . "'");
                              $products = tep_db_fetch_array($products_query);

                              if (tep_not_null($history['delivery_name'])) {
                                $order_type = TEXT_ORDER_SHIPPED_TO;
                                $order_name = $history['delivery_name'];
                              } else {
                                $order_type = TEXT_ORDER_BILLED_TO;
                                $order_name = $history['billing_name'];
                              }
                              //echo '<div>' . "\n";
                              /*
                              if ($count==0)
                                echo '                        <div class="single-order first">' . "\n";
                              else
                                echo '                        <div class="single-order">' . "\n";
                              */
                              echo '                        <div class="single-order">' . "\n";
                              echo '                          <h3>' . TEXT_ORDER_NUMBER . ' ' . $history['orders_id'] . '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, (isset($HTTP_GET_VARS['page']) ? 'page=' . $HTTP_GET_VARS['page'] . '&' : '') . 'order_id=' . $history['orders_id'], 'SSL') . '">' . TEXT_VIEW_ORDER . '</a></h3>' . "\n";
                              echo '                          <p>' . "\n";
                              echo '                            <strong>' . TEXT_ORDER_STATUS . '</strong> ' . $history['orders_status_name'] . '<br />' . "\n";
                              echo '                            <strong>' . TEXT_ORDER_DATE . '</strong> ' . tep_date_long($history['date_purchased']) . '<br />' . "\n";
                              echo '                            <strong>' . $order_type . '</strong> ' . tep_output_string_protected($order_name) . '<br />' . "\n";
                              echo '                            <strong>' . TEXT_ORDER_PRODUCTS . '</strong> ' . $products['count'] . '<br />' . "\n";
                              echo '                            <strong>' . TEXT_ORDER_COST . '</strong> ' . strip_tags($history['order_total']) . "\n";
                              echo '                            ' . "\n";
                              echo '                          </p>' . "\n";
                              echo '                        </div><!--/.single-order first-->' . "\n";
                              $count++;
                            }
                	  	    }
                	  	    else {
                	  	    	echo '                	      <p>' . TEXT_NO_PURCHASES . '</p>' . "\n";
                	  	    }
                	  	    /*
                          if ($orders_total > 0) {
                          	echo '                        <div class="subcat-list-footer">' . "\n";
                          	echo '                          <p>' . $history_split->display_count(TEXT_DISPLAY_NUMBER_OF_ORDERS) . '</p>' . "\n";
                          	echo '                          <div class="pagination">' . "\n";
                          	echo '                            <ol>' . $history_split->display_links(MAX_DISPLAY_PAGE_LINKS, tep_get_all_get_params(array('page', 'info', 'x', 'y'))) . '</ol>' . "\n";
                          	echo '                          </div><!--/.pagination-->' . "\n";
                          	echo '                        </div><!--/.subcat-list-footer-->' . "\n";
                          }
                          */
                	  	  ?>
                	  	  </div>
                    </div><!--/.table-wrapper readable-text-->
          		  </div><!--/.grid-01-02-->
          		
                <div class="grid-01-01">
                   <div class="table-wrapper">
                          <h2 class="sub-title"><?php echo COLOUR_MATCH_TITLE;?></h2>
                          <ul>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">' . COLOUR_MATCH_UPLOAD_1 . '</a>'; ?></li>
                          </ul>
                          <br />

                          <h2 class="sub-title"><?php echo MY_ACCOUNT_TITLE;?></h4>
                          <ul>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL') . '">' . MY_ACCOUNT_INFORMATION . '</a>'; ?></li>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL') . '">' . MY_ACCOUNT_ADDRESS_BOOK . '</a>'; ?></li>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_PASSWORD, '', 'SSL') . '">' . MY_ACCOUNT_PASSWORD . '</a>'; ?></li>
                          </ul>
                          <br />

                          <h2 class="sub-title"><?php echo MY_ORDERS_TITLE;?></h2>
                          <ul>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL') . '">' . MY_ORDERS_VIEW . '</a>'; ?></li>
                          </ul>
                          <br />

    <!--
                          <h2 class="sub-title"><?php echo EMAIL_NOTIFICATIONS_TITLE;?></h2>
                          <ul>
                            <li><?php echo '<u><a href="' . tep_href_link(FILENAME_ACCOUNT_NEWSLETTERS, '', 'SSL') . '">' . EMAIL_NOTIFICATIONS_NEWSLETTERS . '</a></u>'; ?></li>
                          </ul>
                          <br />
    -->
                          <h2 class="sub-title"><?php echo GROUP_STATUS;?></h2>
                          <p><?php echo display_group_message(); ?></p>
                          <?php
                            //if (tep_session_is_registered('customer_id')) {
                              if (($gv_amount=get_gv_amount($customer_id)) > 0 ) {
                                echo '                      <br />' . "\n";
                                echo '                      <h2 class="sub-title">' . VOUCHER_BALANCE . '</h2>' . "\n";
                                echo '                      <p>' . VOUCHER_BALANCE . ':&nbsp;' . $currencies->format($gv_amount) . '</p>' . "\n";
                              }
                            //}
                          ?>
                   </div>
                </div><!--/.grid-01-01-->
                <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
                <div class="clearme"></div>
          	</div><!--/.grid-01-->
          </div><!--/.inner-->





        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>