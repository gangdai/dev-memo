<?php
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PASSWORD_RESET);

  $error = false;

  if ( !isset($HTTP_GET_VARS['account']) || !isset($HTTP_GET_VARS['key']) ) {
    $error = true;

    $messageStack->add_session('password_forgotten', TEXT_NO_RESET_LINK_FOUND);
  }

  if ($error == false) {
    $email_address = tep_db_prepare_input($HTTP_GET_VARS['account']);
    $password_key = tep_db_prepare_input($HTTP_GET_VARS['key']);

    if ( (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) || (tep_validate_email($email_address) == false) ) {
      $error = true;

      $messageStack->add_session('password_forgotten', TEXT_NO_EMAIL_ADDRESS_FOUND);
    } elseif (strlen($password_key) != 40) {
      $error = true;

      $messageStack->add_session('password_forgotten', TEXT_NO_RESET_LINK_FOUND);
    } else {
      $check_customer_query = tep_db_query("select c.customers_id, c.customers_email_address, ci.password_reset_key, ci.password_reset_date from " . TABLE_CUSTOMERS . " c, " . TABLE_CUSTOMERS_INFO . " ci where c.customers_email_address = '" . tep_db_input($email_address) . "' and c.customers_id = ci.customers_info_id");
      if (tep_db_num_rows($check_customer_query)) {
        $check_customer = tep_db_fetch_array($check_customer_query);

        if ( empty($check_customer['password_reset_key']) || ($check_customer['password_reset_key'] != $password_key) || (strtotime($check_customer['password_reset_date'] . ' +1 day') <= time()) ) {
          $error = true;

          $messageStack->add_session('password_forgotten', TEXT_NO_RESET_LINK_FOUND);
        }
      } else {
        $error = true;

        $messageStack->add_session('password_forgotten', TEXT_NO_EMAIL_ADDRESS_FOUND);
      }
    }
  }

  if ($error == true) {
    tep_redirect(tep_href_link(FILENAME_PASSWORD_FORGOTTEN));
  }

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $password_new = tep_db_prepare_input($HTTP_POST_VARS['password']);
    $password_confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);

    if (strlen($password_new) < ENTRY_PASSWORD_MIN_LENGTH) {
      $error = true;

      $messageStack->add('password_reset', ENTRY_PASSWORD_NEW_ERROR);
    } elseif ($password_new != $password_confirmation) {
      $error = true;

      $messageStack->add('password_reset', ENTRY_PASSWORD_NEW_ERROR_NOT_MATCHING);
    }

    if ($error == false) {
      tep_db_query("update " . TABLE_CUSTOMERS . " set customers_password = '" . tep_encrypt_password($password_new) . "' where customers_id = '" . (int)$check_customer['customers_id'] . "'");

      tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_account_last_modified = now(), password_reset_key = null, password_reset_date = null where customers_info_id = '" . (int)$check_customer['customers_id'] . "'");

      $messageStack->add_session('login', SUCCESS_PASSWORD_RESET, 'success');

      tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
  }

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2);
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo HEADING_TITLE; ?></title>
      <meta name="keywords" id="keywords" content="password_forgotten" />
      <meta name="description" id="description" content="password_forgotten" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <meta name="audience" content="all">
      <meta name="distribution" content="global">
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
      <meta http-equiv="Content-Language" content="EN-GB">
      <meta name="rights-standard" content="<?php echo STORE_NAME;?>" />

      <base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>" />

      <link href="styles.css" rel="stylesheet" type="text/css" />
        <!--[if IE]>
        	<link href="styles-ie.css" rel="stylesheet" />
        <![endif]-->

      <link rel="shortcut icon" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'fav_icon.ico';?>" />
      <link rel="apple-touch-icon-precomposed" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'ios_icon.png';?>" />

      <!--JS-->
		  <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		  <script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

      <script src="jquery.hammer.min.js"></script>
      <script src="jquery.fancybox.pack.js?v=2.1.5"></script>
      <script src="jquery.fancybox-media.js?v=1.0.6"></script>
      <script src="jquery.mmenu.min.js"></script>
      <script src="jquery.mmenu.dragopen.min.js"></script>
      <script type="text/javascript" async data-pin-color="red" data-pin-height="28" data-pin-hover="true" src="//assets.pinterest.com/js/pinit.js"></script>




      <script type="text/javascript" src="ticker.js"></script>
		  <script>
			/*<![CDATA[*/
			$(document).ready(function(){

			});
			/*]]>*/
		  </script>

      <!--CSS-->

      <link href="jquery.fancybox.css?v=2.1.5" rel="stylesheet" media="screen" />
      <link href="jquery.mmenu.css" rel="stylesheet" />
      
      <?php require('includes/form_check.js.php'); ?>

    </head>














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">


          <div class="grid-03 centerme">
            <div class="left">
              <?php require(DIR_WS_INCLUDES . 'column_left.php');?>
            </div><!--/.left-->
            
            <div class="right">

                <div class="readable-text">
                		<h1><?php echo HEADING_TITLE; ?></h1><br />
                    <?php
                    if ($messageStack->size('password_reset') > 0) {
                      echo '                		' . $messageStack->output('password_reset') . "\n";
                    }

                      echo '<p>' . TEXT_MAIN . '</p>';
                      echo tep_draw_form('password_reset', tep_href_link(FILENAME_PASSWORD_RESET, 'account=' . $email_address . '&key=' . $password_key . '&action=process', 'SSL'), 'post', 'onsubmit="return check_form(password_reset);" id="form-password-forgotten"', true);
                    ?>
                      <label><?php echo ENTRY_PASSWORD; ?><br />
                        <?php echo tep_draw_password_field('password');?>
                      </label><br />
                      <label><?php echo ENTRY_PASSWORD_CONFIRMATION; ?><br />
                        <?php echo tep_draw_password_field('confirmation');?>
                      </label><br />
                      <input type="submit" class="form-bt-s" value="Continue" />
                    <?php
                      echo '</form>' . "\n";

                    ?>

	            	</div><!--/.readbale-text-->    

            </div><!--/.right-->
            <div class="clearme"></div>
          </div><!--/.grid-03-->



        </div><!--/#container-->

































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>