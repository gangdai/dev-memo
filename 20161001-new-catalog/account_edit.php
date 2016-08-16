<?php


  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// needs to be included earlier to set the success message in the messageStack
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_EDIT);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  //if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process')) {
  if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    if (ACCOUNT_GENDER == 'true') $gender = tep_db_prepare_input($HTTP_POST_VARS['gender']);
    $firstname = tep_db_prepare_input($HTTP_POST_VARS['firstname']);
    $lastname = tep_db_prepare_input($HTTP_POST_VARS['lastname']);
    if (ACCOUNT_DOB == 'true') $dob = tep_db_prepare_input($HTTP_POST_VARS['dob']);
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);
    $telephone = tep_db_prepare_input($HTTP_POST_VARS['telephone']);
    $fax = tep_db_prepare_input($HTTP_POST_VARS['fax']);

    $error = false;

    if (ACCOUNT_GENDER == 'true') {
      if ( ($gender != 'm') && ($gender != 'f') ) {
        $error = true;

        $messageStack->add('account_edit', ENTRY_GENDER_ERROR);
      }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_FIRST_NAME_ERROR);
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_LAST_NAME_ERROR);
    }

    if (ACCOUNT_DOB == 'true') {
      //if (!checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4))) {
      //if ((is_numeric(tep_date_raw($dob)) == false) || (@checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4)) == false)) {
      if ((strlen($dob) < ENTRY_DOB_MIN_LENGTH) || (!empty($dob) && (!is_numeric(tep_date_raw($dob)) || !@checkdate(substr(tep_date_raw($dob), 4, 2), substr(tep_date_raw($dob), 6, 2), substr(tep_date_raw($dob), 0, 4))))) {
        $error = true;

        $messageStack->add('account_edit', ENTRY_DATE_OF_BIRTH_ERROR);
      }
    }

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_EMAIL_ADDRESS_ERROR);
    }

    if (!tep_validate_email($email_address)) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    }

    $check_email_query = tep_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "' and customers_id != '" . (int)$customer_id . "'");
    $check_email = tep_db_fetch_array($check_email_query);
    if ($check_email['total'] > 0) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
    }
/*
    if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
      $error = true;

      $messageStack->add('account_edit', ENTRY_TELEPHONE_NUMBER_ERROR);
    }
*/
    if ($error == false) {
      $sql_data_array = array('customers_firstname' => $firstname,
                              'customers_lastname' => $lastname,
                              'customers_email_address' => $email_address,
                              'customers_telephone' => $telephone,
                              'customers_fax' => $fax);

      if (ACCOUNT_GENDER == 'true') $sql_data_array['customers_gender'] = $gender;
      if (ACCOUNT_DOB == 'true') $sql_data_array['customers_dob'] = tep_date_raw($dob);

      tep_db_perform(TABLE_CUSTOMERS, $sql_data_array, 'update', "customers_id = '" . (int)$customer_id . "'");

      tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_account_last_modified = now() where customers_info_id = '" . (int)$customer_id . "'");

      $sql_data_array = array('entry_firstname' => $firstname,
                              'entry_lastname' => $lastname);

      tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', "customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$customer_default_address_id . "'");

// reset the session variables
      $customer_first_name = $firstname;

      $messageStack->add_session('account', SUCCESS_ACCOUNT_UPDATED, 'success');

      tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
    }
  }

  $account_query = tep_db_query("select customers_gender, customers_firstname, customers_lastname, customers_dob, customers_email_address, customers_telephone, customers_fax from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
  $account = tep_db_fetch_array($account_query);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
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

      <?php require('includes/form_check.js.php'); ?>

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
          	 <h1><?php echo HEADING_TITLE; ?></h1>
                <?php
                if ($messageStack->size('account_edit') > 0) {
                  echo '                  ' . $messageStack->output('account_edit') . "\n";
                }
                ?>
             <div class="grid-01">
             	   <div class="grid-01-02 right">
                	  <div class="table-wrapper readable-text">
                	  	  
						            <h2 class="sub-title"><?php echo HEADING_TITLE; ?></h2>
						            <?php echo tep_draw_form('account_edit', tep_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL'), 'post', 'onSubmit="return check_form(account_edit);"', true) . tep_draw_hidden_field('action', 'process'); ?>
                        	  <label for="firstname">
                            	<?php
                            	  echo '                            	<p>' . ENTRY_FIRST_NAME . ' <span>' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? ENTRY_FIRST_NAME_TEXT : '') . '</span></p>' . "\n";
                            	  echo '                            	' . tep_draw_input_field('firstname', $account['customers_firstname'], 'class="register-input"') . "\n";
                              ?>
                            </label><!--/firstname-->
                            
                            <label for="lastname">
                            	<?php
                            	  echo '                            	<p>' . ENTRY_LAST_NAME . ' <span>' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? ENTRY_LAST_NAME_TEXT : '') . '</span></p>' . "\n";
                            	  echo '                            	' . tep_draw_input_field('lastname', $account['customers_lastname'], 'class="register-input"') . "\n";
                              ?>
                            </label><!--/lastname-->

                            <label for="email_address">
                            	<?php
                            	  echo '                            	<p>' . ENTRY_EMAIL_ADDRESS . ' <span>' . (tep_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? ENTRY_EMAIL_ADDRESS_TEXT : '') . '</span></p>' . "\n";
                            	  echo '                            	' . tep_draw_input_field('email_address', $account['customers_email_address'], 'class="register-input"') . "\n";
                              ?>
                            </label><!--/email_address-->

                            <label for="telephone">
                            	<?php
                            	  echo '                            	<p>' . ENTRY_TELEPHONE_NUMBER . ' <span>' . (tep_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? ENTRY_TELEPHONE_NUMBER_TEXT : '') . '</span></p>' . "\n";
                            	  echo '                            	' . tep_draw_input_field('telephone', $account['customers_telephone'], 'class="register-input"') . "\n";
                              ?>
                            </label><!--/telephone-->

                            <?php echo '<input type="submit" class="form-bt" value="Update" />' . "\n";?>
                        <?php echo '</form>' . "\n"; ?>

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