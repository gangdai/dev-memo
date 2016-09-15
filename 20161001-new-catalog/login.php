<?php

  require('includes/application_top.php');

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
  if ($session_started == false) {
    if ( !isset($HTTP_GET_VARS['cookie_test']) ) {
      $all_get = tep_get_all_get_params();

      tep_redirect(tep_href_link(FILENAME_LOGIN, $all_get . (empty($all_get) ? '' : '&') . 'cookie_test=1', 'SSL'));
    }

    tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
  }

  if (tep_session_is_registered('customer_id')) {
    tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGIN);
  $error = false;

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    $password = tep_db_prepare_input($HTTP_POST_VARS['password']);

// Check if email exists
    ////original code: $check_customer_query = tep_db_query("select customers_id, customers_firstname, customers_password, customers_email_address, customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
// PWA BOF
    $check_customer_query = tep_db_query("select customers_id, customers_firstname, customers_password, customers_email_address, customers_default_address_id, guest_account  from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
// PWA EOF
    if (!tep_db_num_rows($check_customer_query)) {
      $error = true;
    } else {
      $check_customer = tep_db_fetch_array($check_customer_query);
// Check that password is good
      if (!tep_validate_password($password, $check_customer['customers_password'])) {
        $error = true;
      } else {
        if (SESSION_RECREATE == 'True') {
          tep_session_recreate();
        }

        $check_country_query = tep_db_query("select entry_country_id, entry_zone_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$check_customer['customers_id'] . "' and address_book_id = '" . (int)$check_customer['customers_default_address_id'] . "'");
        $check_country = tep_db_fetch_array($check_country_query);

        $customer_id = $check_customer['customers_id'];
        $customer_default_address_id = $check_customer['customers_default_address_id'];
        $customer_first_name = $check_customer['customers_firstname'];
        $customer_country_id = $check_country['entry_country_id'];
        $customer_zone_id = $check_country['entry_zone_id'];
        tep_session_register('customer_id');
        tep_session_register('customer_default_address_id');
        tep_session_register('customer_first_name');
        tep_session_register('customer_country_id');
        tep_session_register('customer_zone_id');
        tep_session_unregister('referral_id'); //rmh referral

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1, password_reset_key = null, password_reset_date = null where customers_info_id = '" . (int)$customer_id . "'");

// reset session token
        $sessiontoken = md5(tep_rand() . tep_rand() . tep_rand() . tep_rand());

// restore cart contents
        $cart->restore_contents();

        if (sizeof($navigation->snapshot) > 0) {
          $origin_href = tep_href_link($navigation->snapshot['page'], tep_array_to_string($navigation->snapshot['get'], array(tep_session_name())), $navigation->snapshot['mode']);
          $navigation->clear_snapshot();
          tep_redirect($origin_href);
        } else {
        	if (isset($HTTP_POST_VARS['added_get_para'])) {
        		tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, $HTTP_POST_VARS['added_get_para']));
          } else {
            tep_redirect(tep_href_link(FILENAME_DEFAULT));
          }
        }
      }
    }
  }

  if ($error == true) {
    $messageStack->add('login', TEXT_LOGIN_ERROR);
  }

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_LOGIN, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
      <!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
      <title><?php echo LOGIN_TITLE; ?></title>
      <meta name="description" id="description" content="<?php echo METADESCRIPTION; ?>" />
      <meta name="keywords" id="keywords" content="<?php echo METAKEYWORD; ?>" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

      <meta name="robots" content="index, follow" />
      <meta name="revisit after" content="7 days">
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

      <?php $_canonicalUrl = canonicalUrl(); echo '<link rel="canonical" href="' . $_canonicalUrl . '" />';?>
      <script type="text/javascript">
      /*<![CDATA[*/
      $(document).ready(function() {
      });
      /*]]>*/
      </script>


    </head>














































    <body itemscope itemtype="http://schema.org/WebPage" id="page-signinup">
      <!-- header -->
      <!-- header end -->

















        <div id="container">



          <div class="inner">
            <?php
              echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image(DIR_WS_ICONS . 'logo.gif', STORE_NAME, '', '', 'class="logo"') . '</a>' . "\n";
              echo tep_draw_form('login', tep_href_link(FILENAME_LOGIN, 'action=process', 'SSL'), 'post', 'class="form-signinup" id="form-signin"', true) . "\n";
              echo '<h1>' . HEADING_TITLE . '<span>' . HEADING_RETURNING_CUSTOMER . '</span></h1>';

              if ($messageStack->size('login') > 0) {
                echo '' . $messageStack->output('login') . "\n";
              }
            ?>
              <label for="email">
                <?php
                  echo '<span>' . ENTRY_EMAIL_ADDRESS . '</span>' . "\n"; echo tep_draw_input_field('email_address', '' , 'id="email-address"') . "\n";
                ?>
              </label>
              <label for="password">
                <?php
                  echo '<span>' . ENTRY_PASSWORD . '</span>' . "\n"; echo tep_draw_password_field('password','','id="password"') . "\n";
                ?>
              </label>

              <?php
                if (isset($HTTP_GET_VARS['products_id']) && isset($HTTP_GET_VARS['tab-reviews'])) {
                	echo tep_draw_hidden_field('added_get_para', 'products_id=' . $HTTP_GET_VARS['products_id'] . '&tab-reviews=1') . "\n";
                }

                echo '<input type="submit" value="' . SIGNIN_TEXT . '" class="submit" />' . "\n";
                echo '<p><a href="' . tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a></p>' . "\n";
              ?>
              <div class="form-footer">
                <?php echo '<h4>' . HEADING_NEW_CUSTOMER . '<a href="' . tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL') . '" class="bt">' . CREATE_ACCOUNT_TEXT . '</a></h4>' . "\n";?>
              </div><!--/.form-footer-->
            <?php echo '</form>' . "\n";?>
          </div><!--/.inner-->



        </div><!--/#container-->






































      <!-- footer -->

      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>