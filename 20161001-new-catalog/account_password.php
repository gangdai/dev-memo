<?php


  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// needs to be included earlier to set the success message in the messageStack
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT_PASSWORD);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  if (isset($HTTP_POST_VARS['action']) && ($HTTP_POST_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $password_current = tep_db_prepare_input($HTTP_POST_VARS['password_current']);
    $password_new = tep_db_prepare_input($HTTP_POST_VARS['password_new']);
    $password_confirmation = tep_db_prepare_input($HTTP_POST_VARS['password_confirmation']);

    $error = false;

    if (strlen($password_new) < ENTRY_PASSWORD_MIN_LENGTH) {
      $error = true;

      $messageStack->add('account_password', ENTRY_PASSWORD_NEW_ERROR);
    } elseif ($password_new != $password_confirmation) {
      $error = true;

      $messageStack->add('account_password', ENTRY_PASSWORD_NEW_ERROR_NOT_MATCHING);
    }

    if ($error == false) {
      $check_customer_query = tep_db_query("select customers_password from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
      $check_customer = tep_db_fetch_array($check_customer_query);

      if (tep_validate_password($password_current, $check_customer['customers_password'])) {
        tep_db_query("update " . TABLE_CUSTOMERS . " set customers_password = '" . tep_encrypt_password($password_new) . "' where customers_id = '" . (int)$customer_id . "'");

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set customers_info_date_account_last_modified = now() where customers_info_id = '" . (int)$customer_id . "'");

        $messageStack->add_session('account', SUCCESS_PASSWORD_UPDATED, 'success');

        tep_redirect(tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
      } else {
        $error = true;

        $messageStack->add('account_password', ERROR_CURRENT_PASSWORD_NOT_MATCHING);
      }
    }
  }

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ACCOUNT_PASSWORD, '', 'SSL'));
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
                if ($messageStack->size('account_password') > 0) {
                  echo '                  ' . $messageStack->output('account_password') . "\n";
                }
              ?>

              <div class="grid-01">
            	  <div class="grid-01-02 right">
                	  <div class="table-wrapper ">
						            <h2 class="sub-title"><?php echo MY_PASSWORD_TITLE . " " . FORM_REQUIRED_INFORMATION; ?></h2>
                        <?php echo tep_draw_form('account_password', tep_href_link(FILENAME_ACCOUNT_PASSWORD, '', 'SSL'), 'post', 'onSubmit="return check_form(account_password);"', true) . tep_draw_hidden_field('action', 'process'); ?>

                          	<label for="password_current">
                          	<?php
                          	  echo '                            	<p>' . ENTRY_PASSWORD_CURRENT . ' <span>' . (tep_not_null(ENTRY_PASSWORD_CURRENT_TEXT) ? ENTRY_PASSWORD_CURRENT_TEXT : '') . '</span></p>' . "\n";
                            	echo '                            	' . tep_draw_password_field('password_current', '', 'maxlength="40" class="register-input"') . "\n";
                            ?>
                            </label><!--/firstname-->

                            <label for="password_new">
                          	<?php
                          	  echo '                            	<p>' . ENTRY_PASSWORD_NEW . ' <span>' . (tep_not_null(ENTRY_PASSWORD_NEW_TEXT) ? ENTRY_PASSWORD_NEW_TEXT : '') . '</span></p>' . "\n";
                            	echo '                            	' . tep_draw_password_field('password_new', '', 'maxlength="40" class="register-input"') . "\n";
                            ?>
                            </label><!--/lastname-->

                            <label for="password_confirmation">
                          	<?php
                          	  echo '                            	<p>' . ENTRY_PASSWORD_CONFIRMATION . ' <span>' . (tep_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? ENTRY_PASSWORD_CONFIRMATION_TEXT : '') . '</span></p>' . "\n";
                            	echo '                            	' . tep_draw_password_field('password_confirmation', '', 'maxlength="40" class="register-input"') . "\n";
                            ?>
                            </label><!--/email_address-->

                            <input type="submit" class="form-bt" value="Update" />
                        <?php echo '</form>' . "\n";; ?>
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