<?php
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PASSWORD_FORGOTTEN);

  $password_reset_initiated = false;

  if (isset($HTTP_GET_VARS['action']) && ($HTTP_GET_VARS['action'] == 'process') && isset($HTTP_POST_VARS['formid']) && ($HTTP_POST_VARS['formid'] == $sessiontoken)) {
    $email_address = tep_db_prepare_input($HTTP_POST_VARS['email_address']);

    $check_customer_query = tep_db_query("select customers_firstname, customers_lastname, customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
    if (tep_db_num_rows($check_customer_query)) {
      $check_customer = tep_db_fetch_array($check_customer_query);

      $actionRecorder = new actionRecorder('ar_reset_password', $check_customer['customers_id'], $email_address);

      if ($actionRecorder->canPerform()) {
        $actionRecorder->record();

        $reset_key = tep_create_random_value(40);

        tep_db_query("update " . TABLE_CUSTOMERS_INFO . " set password_reset_key = '" . tep_db_input($reset_key) . "', password_reset_date = now() where customers_info_id = '" . (int)$check_customer['customers_id'] . "'");

        $reset_key_url = tep_href_link(FILENAME_PASSWORD_RESET, 'account=' . urlencode($email_address) . '&key=' . $reset_key, 'SSL', false);

        if ( strpos($reset_key_url, '&amp;') !== false ) {
          $reset_key_url = str_replace('&amp;', '&', $reset_key_url);
        }

        tep_mail($check_customer['customers_firstname'] . ' ' . $check_customer['customers_lastname'], $email_address, EMAIL_PASSWORD_RESET_SUBJECT, sprintf(EMAIL_PASSWORD_RESET_BODY, $reset_key_url), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

        $password_reset_initiated = true;
      } else {
        $actionRecorder->record(false);

        $messageStack->add('password_forgotten', sprintf(ERROR_ACTION_RECORDER, (defined('MODULE_ACTION_RECORDER_RESET_PASSWORD_MINUTES') ? (int)MODULE_ACTION_RECORDER_RESET_PASSWORD_MINUTES : 5)));
      }
    } else {
      $messageStack->add('password_forgotten', TEXT_NO_EMAIL_ADDRESS_FOUND);
    }
  }

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo PASSWORD_TITLE; ?></title>
      <meta name="keywords" id="keywords" content="password_forgotten" />
      <meta name="description" id="description" content="password_forgotten" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">



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
                <div class="readable-text">
                		<h1><?php echo HEADING_TITLE; ?></h1>
                    <?php
                    if ($messageStack->size('password_forgotten') > 0) {
                      echo '                		' . $messageStack->output('password_forgotten') . "\n";
                    }
                    if ($password_reset_initiated == true) {
                    	echo '<p>' . TEXT_PASSWORD_RESET_INITIATED . '</p>';
                    }
                    else {
                      echo '<p>' . TEXT_MAIN . '</p>';
                      echo tep_draw_form('password_forgotten', tep_href_link(FILENAME_PASSWORD_FORGOTTEN, 'action=process', 'SSL'), 'post', 'id="form-password-forgotten"', true);
                    ?>
                      <label for="email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?><br />
                        <?php echo tep_draw_input_field('email_address', '', 'id="form-password-forgotten-email"');?>
                      </label><br />
                      <input type="submit" class="form-bt" value="Continue" />
                    <?php
                      echo '</form>' . "\n";
                    }
                    ?>

	            	</div><!--/.readbale-text--> 
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