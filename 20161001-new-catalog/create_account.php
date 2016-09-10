<?php

  require('includes/application_top.php');

// PWA BOF
  if (isset($HTTP_GET_VARS['guest']) && $cart->count_contents() < 1) tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
// PWA EOF

 // +Country-State Selector
//require(DIR_WS_FUNCTIONS . 'ajax.php');
/*
  if (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'getStates' && isset($HTTP_POST_VARS['country'])) {
ajax_get_zones_html(tep_db_prepare_input($HTTP_POST_VARS['country']), '', true);
} else {
*/
 // -Country-State Selector
// needs to be included earlier to set the success message in the messageStack
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CREATE_ACCOUNT);

  $process = false;
  //if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process')) {
  if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $process = true;

    if (ACCOUNT_GENDER == 'true') {
      if (isset($HTTP_POST_VARS['gender'])) {
        $gender = tep_db_prepare_input($HTTP_POST_VARS['gender']);
      } else {
        $gender = false;
      }
    }
    $firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
    $lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
    if (ACCOUNT_DOB == 'true') $dob = (isset($HTTP_POST_VARS['dob']) && tep_not_null($HTTP_POST_VARS['dob'])) ? tep_db_prepare_input($HTTP_POST_VARS['dob']) : "01/01/1980"; //$dob = tep_db_prepare_input($HTTP_POST_VARS['dob']);
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    if (ACCOUNT_COMPANY == 'true') $company = tep_db_prepare_input($HTTP_POST_VARS['company']);
    $house_name = trim(tep_db_prepare_input($HTTP_POST_VARS['house_name']));
    $street_address = trim(tep_db_prepare_input($HTTP_POST_VARS['street_address']));
    if (ACCOUNT_SUBURB == 'true') $suburb = tep_db_prepare_input($HTTP_POST_VARS['suburb']);
    $postcode = tep_db_prepare_input($HTTP_POST_VARS['postcode']);
    $city = tep_db_prepare_input($HTTP_POST_VARS['city']);
//My modification - postcodeanywhere
    $country = tep_db_prepare_input($HTTP_POST_VARS['country']);
    if (ACCOUNT_STATE == 'true') {
      $state = tep_db_prepare_input($HTTP_POST_VARS['state']);
      if ($country==222) {
      	$zonename_query = tep_db_query("select zone_id from " . TABLE_ZONES . " where zone_name = '" . $state . "'");
      	if (tep_db_num_rows($zonename_query)==1) {
      		$zonename = tep_db_fetch_array($zonename_query);
      		$zone_id = $zonename['zone_id'];
      	}
      	else {
          $zone_id = false;
        }
      }
      else {
        if (isset($HTTP_POST_VARS['zone_id'])) {
          $zone_id = tep_db_prepare_input($HTTP_POST_VARS['zone_id']);
        } else {
          $zone_id = false;
        }
      }
    }
    //$country = tep_db_prepare_input($HTTP_POST_VARS['country']);
//My modification - postcodeanywhere
    $telephone = tep_db_prepare_input($HTTP_POST_VARS['telephone']);
    $fax = tep_db_prepare_input($HTTP_POST_VARS['fax']);
    if (isset($HTTP_POST_VARS['newsletter'])) {
      $newsletter = tep_db_prepare_input($HTTP_POST_VARS['newsletter']);
    } else {
      $newsletter = false;
    }
    $newsletter = true;
    $password = tep_db_prepare_input($HTTP_POST_VARS['password']);
    $confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);

    //My modification rmh referral start
    $source = tep_db_prepare_input($HTTP_POST_VARS['source']);
    if (isset($HTTP_POST_VARS['source_other'])) $source_other = tep_db_prepare_input($HTTP_POST_VARS['source_other']);
    //My modification rmh referral end

    $error = false;

    if (ACCOUNT_GENDER == 'true') {
      if ( ($gender != 'm') && ($gender != 'f') ) {
        $error = true;

        $messageStack->add('create_account', ENTRY_GENDER_ERROR);
      }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
    }

    if (ACCOUNT_DOB == 'true') {
      if ((strlen($dob) < ENTRY_DOB_MIN_LENGTH) || (!empty($dob) && (!is_numeric(tep_date_raw($dob)) || !@checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4))))) {
        $error = true;

        $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
      }
    }

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (tep_validate_email($email_address) == false) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
      //original code: $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
      // PWA BOF 2b
      $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "' and guest_account != '1'");
      // PWA EOF 2b
      $check_email = tep_db_fetch_array($check_email_query);
      if ($check_email['total'] > 0) {
        $error = true;

        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
      }

      //My modification - 20131119
      $check_email_query = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "' and guest_account = '1'");
      if (tep_db_num_rows($check_email_query) > 0) {
      	while ($pwa_email_query = tep_db_fetch_array($check_email_query)) {
          tep_db_query("delete from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_CUSTOMERS_INFO . " where customers_info_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_WHOS_ONLINE . " where customer_id = '" . (int)$pwa_email_query['customers_id'] . "'");
          tep_db_query("delete from " . TABLE_SOURCES_OTHER . " where customers_id = '" . (int)$pwa_email_query['customers_id'] . "'");
      	}
      }
      //My modification - 20131119

    }
/*
    if (strlen($house_name) < ENTRY_HOUSE_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_HOUSE_NAME_ERROR);
    }

    if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
    }

    if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_POST_CODE_ERROR);
    }

    if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_CITY_ERROR);
    }

    if (is_numeric($country) == false) {
      $error = true;

      $messageStack->add('create_account', ENTRY_COUNTRY_ERROR);
    }

    if (ACCOUNT_STATE == 'true') {
      // +Country-State Selector
      if ($zone_id == 0) {
      // -Country-State Selector
        if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
          $error = true;

          $messageStack->add('create_account', ENTRY_STATE_ERROR);
        }
      }
    }

    if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
    }

    //My modification rmh referral start
    if ((REFERRAL_REQUIRED == 'true') && (is_numeric($source) == false)) {
        $error = true;

        $messageStack->add('create_account', ENTRY_SOURCE_ERROR);
    }

    if ((REFERRAL_REQUIRED == 'true') && (DISPLAY_REFERRAL_OTHER == 'true') && ($source == '9999') && (!tep_not_null($source_other)) ) {
        $error = true;

        $messageStack->add('create_account', ENTRY_SOURCE_OTHER_ERROR);
    }
*/
    //My modification rmh referral end

// PWA BOF
    if (!isset($HTTP_GET_VARS['guest']) && !isset($HTTP_POST_VARS['guest'])) {
  // PWA EOF
      if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
      } elseif ($password != $confirmation) {
        $error = true;
  
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
      }
  // PWA BOF
    }
// PWA EOF

    if ($error == false) {
		// PWA BOF 2b
		if (!isset($HTTP_GET_VARS['guest']) && !isset($HTTP_POST_VARS['guest'])) {
			$dbPass = tep_encrypt_password($password);
			$guestaccount = '0';
		} else {
			$dbPass = tep_encrypt_password('brown');
			$guestaccount = '1';
		}
		// PWA EOF 2b
      $sql_data_array = array('customers_firstname' => $firstname,
                              'customers_lastname' => $lastname,
                              'customers_email_address' => $email_address,
                              'customers_telephone' => $telephone,
                              'customers_fax' => $fax,
                              'customers_newsletter' => $newsletter,
                              ////original code: 'customers_password' => tep_encrypt_password($password));
                              // PWA BOF 2b
                              'customers_password' => $dbPass,
                              'guest_account' => $guestaccount);
                              // PWA EOF 2b

      if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
      if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($dob);

      tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);

      $customer_id = tep_db_insert_id();

      $sql_data_array = array('customers_id' => $customer_id,
                              'entry_firstname' => $firstname,
                              'entry_lastname' => $lastname,
                              'entry_house_name' => $house_name,
                              'entry_street_address' => $street_address,
                              'entry_postcode' => $postcode,
                              'entry_city' => $city,
                              //'entry_country_id' => $country
                              );

      if (ACCOUNT_GENDER == 'true') $sql_data_array['entry_gender'] = $gender;
      if (ACCOUNT_COMPANY == 'true') $sql_data_array['entry_company'] = $company;
      if (ACCOUNT_SUBURB == 'true') $sql_data_array['entry_suburb'] = $suburb;
      if (ACCOUNT_STATE == 'true') {
        if ($zone_id > 0) {
          $sql_data_array['entry_zone_id'] = $zone_id;
          $sql_data_array['entry_state'] = '';
        } else {
          $sql_data_array['entry_zone_id'] = '0';
          $sql_data_array['entry_state'] = $state;
        }
      }

// PWA BOF
     if (isset($HTTP_GET_VARS['guest']) or isset($HTTP_POST_VARS['guest']))
       tep_session_register('customer_is_guest');
// PWA EOF

      tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

      $address_id = tep_db_insert_id();

      tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

      //tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$customer_id . "', '0', now())");
      //My modifcation rmh referral start
      tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_source_id) values ('" . (int)$customer_id . "', '0', now(), '". (int)$source . "')");

      if ($source == '9999') {
        tep_db_perform(TABLE_SOURCES_OTHER, array('customers_id' => (int)$customer_id, 'sources_other_name' => tep_db_input($source_other)));
      }
      //My modifcation rmh referral end

      if (SESSION_RECREATE == 'True') {
        tep_session_recreate();
      }

      $customer_first_name = $firstname;
      $customer_default_address_id = $address_id;
      $customer_country_id = $country;
      $customer_zone_id = $zone_id;
      tep_session_register('customer_id');
      tep_session_register('customer_first_name');
      tep_session_register('customer_default_address_id');
      tep_session_register('customer_country_id');
      tep_session_register('customer_zone_id');
      tep_session_unregister('referral_id'); //rmh referral

// reset session token
      $sessiontoken = md5(tep_rand() . tep_rand() . tep_rand() . tep_rand());
// restore cart contents

// PWA BOF
      if (isset($HTTP_GET_VARS['guest']) or isset($HTTP_POST_VARS['guest'])) tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
// PWA EOF

// restore cart contents
      $cart->restore_contents();

// build the message content
      $name = $firstname . ' ' . $lastname;

      if (ACCOUNT_GENDER == 'true') {
         if ($gender == 'm') {
           $email_text = sprintf(EMAIL_GREET_MR, $lastname);
         } else {
           $email_text = sprintf(EMAIL_GREET_MS, $lastname);
         }
      } else {
        $email_text = sprintf(EMAIL_GREET_NONE, $firstname);
      }
      $email_text .= EMAIL_WELCOME . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;
// Start - CREDIT CLASS Gift Voucher Contribution
/*
  if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
    $coupon_code = create_coupon_code();
    $insert_query = tep_db_query("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created) values ('" . $coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())");
    $insert_id = tep_db_insert_id();
    $insert_query = tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id ."', '0', 'Admin', '" . $email_address . "', now() )");

    $email_text .= sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) . "\n\n" .
                   sprintf(EMAIL_GV_REDEEM, $coupon_code) . "\n\n" .
                   EMAIL_GV_LINK . tep_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code,'NONSSL', false) .
                   "\n\n";
  }
  if (NEW_SIGNUP_DISCOUNT_COUPON != '') {
		$coupon_code = NEW_SIGNUP_DISCOUNT_COUPON;
    $coupon_query = tep_db_query("select * from " . TABLE_COUPONS . " where coupon_code = '" . $coupon_code . "'");
    $coupon = tep_db_fetch_array($coupon_query);
		$coupon_id = $coupon['coupon_id'];		
    $coupon_desc_query = tep_db_query("select * from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $coupon_id . "' and language_id = '" . (int)$languages_id . "'");
    $coupon_desc = tep_db_fetch_array($coupon_desc_query);
    $insert_query = tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $coupon_id ."', '0', 'Admin', '" . $email_address . "', now() )");
    $email_text .= EMAIL_COUPON_INCENTIVE_HEADER .  "\n" .
                   sprintf("%s", $coupon_desc['coupon_description']) ."\n\n" .
                   sprintf(EMAIL_COUPON_REDEEM, $coupon['coupon_code']) . "\n\n" .
                   "\n\n";
  }
*/
// End - CREDIT CLASS Gift Voucher Contribution

      //tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

      //tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS, '', 'SSL'));
      //if (isset($HTTP_GET_VARS['quickcheckout']) or isset($HTTP_POST_VARS['quickcheckout'])) tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
      if (sizeof($navigation->snapshot) > 0 && $navigation->snapshot['page'] == FILENAME_CHECKOUT) {
      	$navigation->clear_snapshot();
  		  tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
  	  }
      else tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
    }
  }

  // +Country-State Selector
  if (!isset($country)) $country = DEFAULT_COUNTRY;
  // -Country-State Selector
  ////original code: $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'));
  // PWA BOF
  if (!isset($HTTP_GET_VARS['guest']) && !isset($HTTP_POST_VARS['guest'])) {
    $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'));
  } else {
    $breadcrumb->add(NAVBAR_TITLE_PWA, tep_href_link(FILENAME_CREATE_ACCOUNT, 'guest=guest', 'SSL'));
  }
  // PWA EOF
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>
      <meta name="keywords" id="keywords" content="create account, new customers, register" />
      <meta name="description" id="description" content="create an account or go to checkout" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
		  <script>
			/*<![CDATA[*/
			$(document).ready(function(){

			});
			/*]]>*/
		  </script>

      <!--CSS-->




      <?php require('includes/form_check.js.php'); require(DIR_WS_INCLUDES . 'ajax.js.php'); ?>
    </head>













































    <body itemscope itemtype="http://schema.org/WebPage" id="page-signinup">
      <!-- header -->
      <!-- header end -->

















    	  <div id="container">



        	<div class="inner">
            <?php
              echo '<a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image(DIR_WS_ICONS . 'logo.gif', STORE_NAME, '', '', 'class="logo"') . '</a>';

              echo tep_draw_form('create_account', tep_href_link(FILENAME_CREATE_ACCOUNT, (isset($HTTP_GET_VARS['guest'])? 'guest=guest':''), 'SSL'), 'post', 'onSubmit="return check_form(create_account);" class="form-signinup" id="form-signup"', true) . tep_draw_hidden_field('action', 'process') . "\n";
              //echo '<div id="indicator" style="visibility:hidden;">' . tep_image(DIR_WS_IMAGES . 'icons/indicator.gif') . '</div>' . "\n";
              echo '<h1>' . HEADING_TITLE . '<span>' . HEADING_NEW_CUSTOMER . '</span></h1>';

              if ($messageStack->size('create_account') > 0) {
                echo $messageStack->output('create_account');
              }

              echo '<label for="email_address">' . "\n";
              echo '  <span>' . ENTRY_EMAIL_ADDRESS . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? ' ' . ENTRY_EMAIL_ADDRESS_TEXT . '': '') . '</span>' . "\n";
              echo '  ' . tep_draw_input_field('email_address','','') . "\n";
              echo '</label>' . "\n";

              echo '<label for="firstname">' . "\n";
              echo '  <span>' . ENTRY_FIRST_NAME . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? ' ' . ENTRY_FIRST_NAME_TEXT . '</span>': '') . '</span>' . "\n";
              echo '  ' . tep_draw_input_field('firstname','','') . "\n";
              echo '</label>' . "\n";

              echo '<label for="lastname">' . "\n";
              echo '  <span>' . ENTRY_LAST_NAME . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? ' ' . ENTRY_LAST_NAME_TEXT . '': '') . '</span>' . "\n";
              echo '  ' .tep_draw_input_field('lastname','','') . "\n";
              echo '</label>' . "\n";

              echo '<label for="register-password">' . "\n";
              echo '  <span>' . ENTRY_PASSWORD . (tep_not_null(ENTRY_PASSWORD_TEXT) ? ' ' . ENTRY_PASSWORD_TEXT . '': '') . '</span>' . "\n";
              echo '  ' . tep_draw_password_field('password', '', '') . "\n";
              echo '</label>' . "\n";

              echo '<label for="confirmation">' . "\n";
              echo '  <span>' . ENTRY_PASSWORD_CONFIRMATION . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? ' ' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '': '') . '</span>' . "\n";
              echo '  ' .  tep_draw_password_field('confirmation', '', '') . "\n";
              echo '</label>' . "\n";

              echo '<input type="submit" class="submit" value="CONTINUE" />' . "\n";
              echo '<div class="form-footer">' . "\n";
              echo '  <h4>' . TEXT_EXISTING_CUSTOMERS . '<a href="' . tep_href_link(FILENAME_LOGIN, '', 'SSL') . '" class="bt">SIGN IN</a></h4>' . "\n";
              echo '</div><!--/.form-footer-->' . "\n";
              echo '</form>' . "\n";
            ?>

        	</div><!--/.inner-->



        </div><!--/#container-->






































      <!-- footer -->

      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
