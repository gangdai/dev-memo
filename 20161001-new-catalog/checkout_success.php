<?php

  require('includes/application_top.php');

// if the customer is not logged on, redirect them to the shopping cart page
  // {{ AMAZON PAYMENT
  //if (!tep_session_is_registered('customer_id')) {
  if ( !isset($_SESSION['amazon_order_id']) && !tep_session_is_registered('customer_id')) {
  // }} AMAZON PAYMENT
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'update')) {
    $notify_string = '';
    if (isset($HTTP_POST_VARS['notify']) && !empty($HTTP_POST_VARS['notify'])) {
      $notify = $HTTP_POST_VARS['notify'];

      if (!is_array($notify)) {
        $notify = array($notify);
      }

      for ($i=0, $n=sizeof($notify); $i<$n; $i++) {
        if (is_numeric($notify[$i])) {
          $notify_string .= 'notify[]=' . $notify[$i] . '&';
        }
      }

      if (!empty($notify_string)) {
        $notify_string = 'action=notify&' . substr($notify_string, 0, -1);
      }
    }

    tep_redirect(tep_href_link(FILENAME_DEFAULT, $notify_string));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SUCCESS);

  $breadcrumb->add(NAVBAR_TITLE_1);
  $breadcrumb->add(NAVBAR_TITLE_2);

  // {{ AMAZON PAYMENT
  $global = false;
  if ( tep_session_is_registered('customer_id') ) {
  // }} AMAZON PAYMENT
  $global_query = tep_db_query("select global_product_notifications from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$customer_id . "'");
  $global = tep_db_fetch_array($global_query);

  if ($global['global_product_notifications'] != '1') {
    $orders_query = tep_db_query("select orders_id from " . TABLE_ORDERS . " where customers_id = '" . (int)$customer_id . "' order by date_purchased desc limit 1");
    $orders = tep_db_fetch_array($orders_query);

    //// Original code:
    $products_array = array();
    $products_query = tep_db_query("select products_id, products_name from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$orders['orders_id'] . "' order by products_name");
    while ($products = tep_db_fetch_array($products_query)) {
      $products_array[] = array('id' => $products['products_id'],
                                'text' => $products['products_name']);
    }
  }

  // {{ AMAZON PAYMENT
  }
  // }} AMAZON PAYMENT
// PWA BOF 2b
//delete the temporary account
  $pwa_query = tep_db_query("select guest_account from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
  $pwa = tep_db_fetch_array($pwa_query);
  if ($pwa['guest_account'] == 1) {
  tep_db_query("delete from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
  tep_db_query("delete from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "'");
  tep_db_query("delete from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$customer_id . "'");
  tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id . "'");
  tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id . "'");
  tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where customer_id = '" . tep_db_input($customer_id) . "'");
  tep_session_unregister('guest_account');
  tep_session_unregister('customer_is_guest');
  tep_session_unregister('customer_id');
  tep_session_unregister('customer_default_address_id');
  tep_session_unregister('customer_first_name');
  tep_session_unregister('customer_country_id');
  tep_session_unregister('customer_zone_id');
  tep_session_unregister('comments');
  }
// PWA EOF 2b

?>


<?php
  //20150911
  $suspend_ga_ecommerce = false;
  //20150911

 //The Manual order section
  if (tep_session_is_registered('administrator_login')) {
    $suspend_ga_ecommerce = true;
    $Manual_Ord = 1;
    //logoff the manual order
    tep_session_unregister('administrator_login');

    //logoff the customer created
    tep_session_unregister('customer_id');
    tep_session_unregister('customer_default_address_id');
    tep_session_unregister('customer_first_name');
    tep_session_unregister('customer_country_id');
    tep_session_unregister('customer_zone_id');
    tep_session_unregister('comments');
    $cart->reset();
  }
//if (tep_session_is_registered('missorder'))
//{tep_session_unregister('missorder');}
//check and unregister the according session
manual_order_registerstatus1();

if (tep_session_is_registered('manual_order')) {
  $suspend_ga_ecommerce = true;
	tep_session_unregister('manual_order');
}
if (is_cashcarry()) {
  $suspend_ga_ecommerce = true;
	$cash_carry = 1;
	tep_session_unregister('cash_carry');
}

//20150915
if (tep_session_is_registered('facebookcampaign')) {
  $suspend_ga_ecommerce = true;
	tep_session_unregister('facebookcampaign');
}
if (tep_session_is_registered('land_on_manual_page')) {
  $suspend_ga_ecommerce = true;
  tep_session_unregister('land_on_manual_page');
}

if (tep_session_is_registered('webgains')) {
  tep_session_unregister('webgains');
}
//20150915
?>





































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>
      <meta name="keywords" id="keywords" content="" />
      <meta name="description" id="description" content="" />


      <meta name="viewport" content="width=device-width, initial-scale=1.0">


      <meta name="audience" content="all">
      <meta name="distribution" content="global">
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
      <meta http-equiv="Content-Language" content="EN-GB">
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
            	<div class="grid-full">
            		<h1 id="product-title"><?php echo HEADING_TITLE; ?></h1>
                	  <div id="checkout-customer-order">
                	  	  <?php
                	  	    echo tep_draw_form('order', tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'action=update', 'SSL')); 
                	  	    echo TEXT_SUCCESS;
                          //class="form-bt-s"
                          echo '                	  	  <input type="submit" class="bt-continue-checkout" value="Continue" />' . "\n";
                          echo '                	  	  <p>&nbsp;</p>' . "\n";
                          echo '</form>' . "\n";
                        ?>
                    </div><!--/#checkout-customer-order-->



                    <div>
                    	<p>
                        <?php
                          //PWA BOF
                          if (!tep_session_is_registered('customer_is_guest')) {
                          //PWA EOF
                            // {{ AMAZON PAYMENT
                            //if ($global['global_product_notifications'] != '1') {
                            if ( is_array($global) && $global['global_product_notifications'] != '1') {
                            // }} AMAZON PAYMENT
                              /*
                              echo TEXT_NOTIFY_PRODUCTS . '<br><p class="productsNotifications">';

                              $products_displayed = array();
                              for ($i=0, $n=sizeof($products_array); $i<$n; $i++) {
                                if (!in_array($products_array[$i]['id'], $products_displayed)) {
                                  echo tep_draw_checkbox_field('notify[]', $products_array[$i]['id']) . ' ' . $products_array[$i]['text'] . '<br>';
                                  $products_displayed[] = $products_array[$i]['id'];
                                }
                              }
                              echo '</p>';
                              */
                            } else {
                              //echo TEXT_SEE_ORDERS . '<br><br>' . TEXT_CONTACT_STORE_OWNER;
                              // {{ AMAZON PAYMENT
                              if ( tep_session_is_registered('customer_id') ) {
                                echo TEXT_SEE_ORDERS . '<br /><br />' . TEXT_CONTACT_STORE_OWNER;
                              }
                              // }} AMAZON PAYMENT
                            }
                          //PWA BOF
                          }
                          //PWA EOF


                          // {{ AMAZON PAYMENT
                          if ( isset($_SESSION['amazon_order_id']) && is_array($_SESSION['amazon_order_id']) ) {
                            if ( is_file(DIR_WS_MODULES.'/amazon_payment/inline_core.php') ) {
                              include_once(DIR_WS_MODULES.'/amazon_payment/inline_core.php');
                            }
                            if ( class_exists('AmazonPaymentInlineCore') ) {
                              $cbaCore = AmazonPaymentInlineCore::get();
                              echo AmazonPaymentInlineWidgets::getAmazonJS();
                              foreach( (array)$_SESSION['amazon_order_id'] as $aidx=>$amazon_order_id ) {
                              ?>
                              <script>
                                var orderDetailsWidget_<?php echo $aidx; ?> = new CBA.Widgets.OrderDetailsWidget({
                                  merchantId: "<?php echo $cbaCore->getConfig()->getMerchantID(); ?>",
                                  orderID: '<?php echo $amazon_order_id; ?>'
                                });
                              </script>
                              <p><?php echo sprintf(TEXT_YOUR_AMAZON_ORDER_S, '<a href="javascript:;" onclick="orderDetailsWidget_'.$aidx.'.open(\'230px\', \'554px\'); return false;">'.$amazon_order_id.'</a>');?></p>
                              <?php
                              }
                            }
                            unset($_SESSION['amazon_order_id']);
                          }
                          // }} AMAZON PAYMENT

                        ?>
                    	</p>
                    </div>


                    <div>

                    </div>

                    <!--Manual order-->
                    <?php
                      if ($Manual_Ord == 1) {
                      	$Manual_Ord = 0;
                      	//logoff the manual order
                        tep_session_unregister('administrator_login');
                    ?>

                    <?php
                      }
                    ?>
                    <!--Manual order-->

                    <div><p><?php if (DOWNLOAD_ENABLED == 'true') include(DIR_WS_MODULES . 'downloads.php'); ?></p></div>

                    <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
                    <div class="clearme"></div>
            	</div><!--/.grid-full-->
            </div><!--inner-->



        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->

    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>