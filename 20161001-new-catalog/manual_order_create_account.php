<?php

  require('includes/application_top.php');
  //require('includes/configure_simple_order_entry.php');
  require('includes/adminpwd.php');

  if (!is_goodip())
  { exit; }

// +Country-State Selector
require(DIR_WS_FUNCTIONS . 'ajax.php');
  if (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'getStates' && isset($HTTP_POST_VARS['country'])) {
ajax_get_zones_html(tep_db_prepare_input($HTTP_POST_VARS['country']), '', true);
} else {
// -Country-State Selector

// needs to be included earlier to set the success message in the messageStack
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADMIN_CREATE_ACCOUNT);

  $process = false;
  if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process')) {
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
    //if (ACCOUNT_DOB == 'true') $dob = tep_db_prepare_input($HTTP_POST_VARS['dob']);
    if (ACCOUNT_DOB == 'true') $dob = (isset($HTTP_POST_VARS['dob']) && tep_not_null($HTTP_POST_VARS['dob'])) ? tep_db_prepare_input($HTTP_POST_VARS['dob']) : "01/01/1990";
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    if (ACCOUNT_COMPANY == 'true') $company = tep_db_prepare_input($HTTP_POST_VARS['company']);
    $house_name = trim(tep_db_prepare_input($HTTP_POST_VARS['house_name']));
    $street_address = tep_db_prepare_input($HTTP_POST_VARS['street_address']);
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
//My modification - postcodeanywhere
    $telephone = tep_db_prepare_input($HTTP_POST_VARS['telephone']);
    $fax = tep_db_prepare_input($HTTP_POST_VARS['fax']);
    if (isset($HTTP_POST_VARS['newsletter'])) {
      $newsletter = tep_db_prepare_input($HTTP_POST_VARS['newsletter']);
    } else {
      $newsletter = false;
    }
    //$password = tep_db_prepare_input($HTTP_POST_VARS['password']);
    $password = (isset($HTTP_POST_VARS['password']) && tep_not_null($HTTP_POST_VARS['password'])) ? tep_db_prepare_input($HTTP_POST_VARS['password']) : "brown";
    //$confirmation = tep_db_prepare_input($HTTP_POST_VARS['confirmation']);
    $confirmation = (isset($HTTP_POST_VARS['confirmation']) && tep_not_null($HTTP_POST_VARS['confirmation'])) ? tep_db_prepare_input($HTTP_POST_VARS['confirmation']) : "brown";

    //My modification rmh referral start
    $source = tep_db_prepare_input($HTTP_POST_VARS['source']);
    if (isset($HTTP_POST_VARS['source_other'])) $source_other = tep_db_prepare_input($HTTP_POST_VARS['source_other']);
    //My modification rmh referral end

    $error = false;

    if (ACCOUNT_GENDER == 'true') {
      if ( ($gender != 'm') && ($gender != 'f') ) {
      //  $error = true;

      //  $messageStack->add('create_account', ENTRY_GENDER_ERROR);
      }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
      //$error = true;

      //$messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
      //$error = true;

      //$messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
    }

    if (ACCOUNT_DOB == 'true') {
      if (checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4)) == false) {
      //  $error = true;

      //  $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
      }
    }

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (tep_validate_email($email_address) == false) {
      $error = true;

      $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
      $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
      $check_email = tep_db_fetch_array($check_email_query);
      if ($check_email['total'] > 0) {
        $error = true;

        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
      }
    }

    if (strlen($house_name) < ENTRY_HOUSE_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_HOUSE_NAME_ERROR);
    }

    if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
      //$error = true;

      //$messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
    }

    if (!is_cashcarry()) {
    if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_POST_CODE_ERROR);
    }
    }


    if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
      //$error = true;

      //$messageStack->add('create_account', ENTRY_CITY_ERROR);
    }

    if (is_numeric($country) == false) {
      //$error = true;

      //$messageStack->add('create_account', ENTRY_COUNTRY_ERROR);
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
      //$error = true;

      //$messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
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
    //My modification rmh referral end

    if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
      $error = true;

      $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
    } elseif ($password != $confirmation) {
      $error = true;

      $messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
    }

    if ($error == false) {
      $sql_data_array = array('customers_firstname' => $firstname,
                              'customers_lastname' => $lastname,
                              'customers_email_address' => $email_address,
                              'customers_telephone' => $telephone,
                              'customers_fax' => $fax,
                              'customers_newsletter' => $newsletter,
                              'customers_password' => tep_encrypt_password($password));

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
                              'entry_country_id' => $country);

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
      
      $cart->reset();

      $customer_first_name = $firstname;
      $customer_default_address_id = $address_id;
      $customer_country_id = $country;
      $customer_zone_id = $zone_id;
      tep_session_register('customer_id');
      tep_session_register('customer_first_name');
      tep_session_register('customer_default_address_id');
      tep_session_register('customer_country_id');
      tep_session_register('customer_zone_id');

      tep_session_register('administrator_login');
      tep_session_unregister('referral_id'); //rmh referral

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
      //$email_text .= sprintf(EMAIL_ACCOUNT_DETAILS, $email_address, $password);

      tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      //tep_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, EMAIL_SUBJECT.$email_address, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      tep_session_register('manual_order');
      tep_redirect(MANUAL_ORDER_REDIRECT);
    }
  }

  if ($HTTP_GET_VARS["Manual_Admin"])
  {
    $breadcrumb->add('Manual Admin', tep_href_link(FILENAME_ADMIN, '', 'SSL'));
  }

  // +Country-State Selector 
  if (!isset($country)) $country = DEFAULT_COUNTRY;
  // -Country-State Selector
  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_ADMIN_CREATE_ACCOUNT, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title>Manual Order Create Account</title>
      <meta name="description" id="description" content="" />
      <meta name="keywords" id="keywords" content="" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

     	<meta name="robots" content="noindex,nofollow">
      <meta name="audience" content="all">

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




      <!-- //My modification - postcodeanywhere -->
      <script type="text/javascript">
        var formname = 'create_account';
      </script>
      <script type="text/javascript" src="postcode/crafty_mymod.js"></script>
      <script type="text/javascript">window.onload = function() { switchmode(); };</script>
      <?php
          require('postcode/crafty_html_output.php');
          echo tep_crafty_script_add('create_account');
      ?>
      <!-- //My modification - postcodeanywhere -->
      <?php require('includes/form_check.js.php'); require(DIR_WS_INCLUDES . 'z-dhtml/ajax.js.php'); ?>
    </head>














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">



          <div class="inner">
          	<div class="grid-02">
				      <div class="grid-02-01">
					    </div><!--/.grid-02-01-->
					    
					    <div class="grid-02-02">
                <h3><?php echo HEADING_TITLE; ?></h3>
                <?php
                      if ($messageStack->size('create_account') > 0) {
                        echo $messageStack->output('create_account');
                      }
                ?>


                	  <div class="table-wrapper readable-text">
                	      <?php echo tep_draw_form('create_account', tep_href_link(FILENAME_ADMIN_CREATE_ACCOUNT, '', 'SSL'), 'post', 'onSubmit="return check_form(create_account);"') . tep_draw_hidden_field('action', 'process'); ?><div id="indicator" style="visibility:hidden;"><?php echo tep_image(DIR_WS_IMAGES . 'icons/indicator.gif'); ?></div>
                		    <!--
                        <h2 class="sub-title">Customers Details</h2>
                        <p>* Required information</p>
                        -->

                        <?php
                          if (ACCOUNT_GENDER == 'true') {
                            echo '<label for="gender">' . "\n";
                            echo '  <p>' . ENTRY_GENDER . (tep_not_null(ENTRY_GENDER_TEXT) ? ' <span>' . ENTRY_GENDER_TEXT . '</span>': '') . '</p>';
                            echo tep_draw_radio_field('gender', 'm') . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f') . '&nbsp;&nbsp;' . FEMALE . '&nbsp;';
                            echo '</label><br /><br />' . "\n";
                          }
                        ?>

                        <label for="firstname">
                        	<?php echo '<p>' . ENTRY_FIRST_NAME . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? ' <span>' . ENTRY_FIRST_NAME_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('firstname','','class="register-input"') . "\n";?>
                        </label><!--/firstname-->

                        <label for="lastname">
                        	<?php echo '<p>' . ENTRY_LAST_NAME . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? ' <span>' . ENTRY_LAST_NAME_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('lastname','','class="register-input"') . "\n";?>
                        </label><!--/lastname-->
                        <?php
                          if (ACCOUNT_DOB == 'true') {
                          	echo '<label for="gender">' . "\n";
                          	echo '<p>' . ENTRY_DATE_OF_BIRTH . (tep_not_null(ENTRY_DATE_OF_BIRTH_TEXT) ? ' <span>' . ENTRY_DATE_OF_BIRTH_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('dob','','class="register-input"') . "\n";
                          	echo '</label><br /><br />' . "\n";
                          }
                        ?>
                        <label for="email_address">
                        	<?php echo '<p>' . ENTRY_EMAIL_ADDRESS . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? ' <span>' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('email_address','','class="register-input"') . "\n";?>
                        </label><!--/email-->

                        <label for="country">
                        	  <p><?php echo ENTRY_COUNTRY . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? ' <span>' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></p>
                        	  <?php echo tep_get_country_list('country','222','onChange="javascript:getStates(this.value, \'states\');javascript:switchmode();" class="register-input"'); ?>
                        </label><!--/country-->

						            <label for="postcode">
                        	  <p id="btnFinddiv1"><?php echo ENTRY_POST_CODE  . (tep_not_null(ENTRY_POST_CODE_TEXT) ? ' <span>' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></p>
                            <div class="postcode_form_element">
                              <div class="left"><?php echo tep_draw_input_field('postcode', '', 'class="register-input-half"'); ?></div>
                              <div class="right"><div id="btnFinddiv2"><?php echo tep_crafty_button(); ?></div></div>
                            </div>
                        	  <div class="clearme"></div>
                            <div><div id="crafty_postcode_result_display" class="address_form"></div></div>
                        </label><!--/postcode-->
                        <div class="clearme"></div>


                        <?php
                          if (ACCOUNT_COMPANY == 'true') {
                        ?>
                        <label for="company">
                          <?php echo '<p>' . ENTRY_COMPANY . (tep_not_null(ENTRY_COMPANY_TEXT) ? ' <span>' . ENTRY_COMPANY_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('company','','class="register-input"') . "\n";?>
                        </label><!--/company-->
                        <?php
                          }
                        ?>

                        <label for="house_name">
                            <?php echo '<p>' . ENTRY_HOUSE_NAME . (tep_not_null(ENTRY_HOUSE_NAME_TEXT) ? ' <span>' . ENTRY_HOUSE_NAME_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('house_name','','class="register-input"') . "\n";?>
                        </label><!--/house_name-->

                        <label for="street_address">
                            <?php echo '<p>' . ENTRY_STREET_ADDRESS . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? ' <span>' . ENTRY_STREET_ADDRESS_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('street_address','','class="register-input"') . "\n";?>
                        </label><!--/street_address-->

                        <?php
                          if (ACCOUNT_SUBURB == 'true') {
                        ?>
                        <label for="suburb">
                            <?php echo '<p>' . ENTRY_SUBURB . (tep_not_null(ENTRY_SUBURB_TEXT) ? ' <span>' . ENTRY_SUBURB_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('suburb','','class="register-input"') . "\n";?>
                        </label><!--/suburb-->
                        <?php
                          }
                        ?>
						
                        <label for="city">
                            <?php echo '<p>' . ENTRY_CITY . (tep_not_null(ENTRY_CITY_TEXT) ? ' <span>' . ENTRY_CITY_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('city','','class="register-input"') . "\n";?>
                        </label><!--/city-->
                        
                        <?php
                          if (ACCOUNT_STATE == 'true') {
                        ?>
                        <label for="state">
                            <?php //echo '<p>' . ENTRY_STATE . (tep_not_null(ENTRY_STATE_TEXT) ? ' <span>' . ENTRY_STATE_TEXT . '</span>': '') . '</p>'; ?>
                            <?php echo '<p>' . ENTRY_STATE . (tep_not_null(ENTRY_STATE_TEXT) ? ' <span>' . ENTRY_STATE_TEXT . '</span>': '') . '</p>' . "\n";?>
                            <div><div id="states">
                              <?php
                                  // +Country-State Selector
                                  echo ajax_get_zones_html($country,'',false);
                                  // -Country-State Selector
                                  //if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT;
                              ?>
                            </div></div>
                        </label><!--/state-->
                        <?php
                          }
                        ?>

                        <label for="telephone">
                        	  <?php echo '<p>' . ENTRY_TELEPHONE_NUMBER . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? ' <span>' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>': '') . '</p>' . tep_draw_input_field('telephone','','class="register-input"') . "\n";?>
                        </label><!--/telephone-->

                        <label for="newsletter">
                        	  <br /><p><?php echo tep_draw_checkbox_field('newsletter', '1', true, 'id="newsletter"') . (tep_not_null(ENTRY_NEWSLETTER_TEXT) ? ' <span>' . ENTRY_NEWSLETTER_TEXT . '</span>': '') . ENTRY_NEWSLETTER;?></p><br />
						            </label><!--/newsletter-->

                        <input type="submit" class="form-bt" value="Continue" />

                        <?php echo '</form>' . "\n";?>
                    </div><!--/.table-wrapper readable-text-->

					    </div><!--/.grid-02-02-->

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
<?php
// +Country-State Selector 
}
// -Country-State Selector
?>