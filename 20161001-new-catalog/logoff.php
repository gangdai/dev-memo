<?php

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGOFF);

  $breadcrumb->add(NAVBAR_TITLE);

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
  }

  tep_session_unregister('guest_account');
// PWA EOF

  tep_session_unregister('customer_id');
  tep_session_unregister('customer_default_address_id');
  tep_session_unregister('customer_first_name');
  tep_session_unregister('customer_country_id');
  tep_session_unregister('customer_zone_id');

  if ( tep_session_is_registered('sendto') ) {
    tep_session_unregister('sendto');
  }

  if ( tep_session_is_registered('billto') ) {
    tep_session_unregister('billto');
  }

  if ( tep_session_is_registered('shipping') ) {
    tep_session_unregister('shipping');
  }

  if ( tep_session_is_registered('payment') ) {
    tep_session_unregister('payment');
  }

  if ( tep_session_is_registered('comments') ) {
    tep_session_unregister('comments');
  }
// Start - CREDIT CLASS Gift Voucher Contribution
  if ( tep_session_is_registered('gv_id') ) {
    tep_session_unregister('gv_id');
  }
  if ( tep_session_is_registered('cot_gv') ) {
    tep_session_unregister('cot_gv');
  }
  if ( tep_session_is_registered('cc_id') ) {
    tep_session_unregister('cc_id');
  }
// End - CREDIT CLASS Gift Voucher Contribution

// recently_viewed
  if ( tep_session_is_registered('recently_viewed') ) {
    tep_session_unregister('recently_viewed'); //for customer's security, this line of code removes the recently_viewed info after logoff
  }
// recently_viewed

  //check and unregister the according session
  manual_order_registerstatus1();

  if (tep_session_is_registered('cash_carry')) {
    tep_session_unregister('cash_carry');
  }
  if (tep_session_is_registered('manual_order')) {
    tep_session_unregister('manual_order');
  }
  //manual order and cash/carry

  $cart->reset();
?>





































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>

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

      <script type="text/javascript">
      /*<![CDATA[*/

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
                <h2><?php echo HEADING_TITLE; ?></h2>
                <p><?php echo TEXT_MAIN; ?></p>


					    </div><!--/.grid-02-02-->
					    
					    <div class="clearme"></div>
					    <p><br /><br /><br /><br /><br /><br /></p>
            </div><!--/.grid-02-->
          </div>


        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>