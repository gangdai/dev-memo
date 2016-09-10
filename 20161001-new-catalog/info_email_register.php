<?php


  require('includes/application_top.php');


  $breadcrumb->add("Get Email", tep_href_link(FILENAME_INFO_EMAIL_REGISTER));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo 'Email Signup | ' . STORE_NAME; ?></title>
      <meta name="keywords" id="keywords" content="email sign up, email signup, follow us" />
      <meta name="description" id="description" content="email newsletter and email signup" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

     	<meta name="robots" content="index, follow" />
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
                		<?php
                		  if (!isset($HTTP_POST_VARS['register_newsletter_email'])) echo "                  <h1>Email Signup</h1>";

                		  if (isset($HTTP_POST_VARS['register_newsletter_email']) && tep_not_null($HTTP_POST_VARS['register_newsletter_email'])) {
                        if(filter_var($HTTP_POST_VARS['register_newsletter_email'], FILTER_VALIDATE_EMAIL)) {
                          //check if email exist
                          $check_email_q = tep_db_query("select customers_id, customers_email_address, customers_newsletter from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($HTTP_POST_VARS['register_newsletter_email']) . "'");
                          //no such email
                          if (tep_db_num_rows($check_email_q) < 1) {
                            $check_email_q1 = tep_db_query("select customers_email_address from " . TABLE_EMAIL_REGISTERED_CUSTOMERS . " where customers_email_address = '" . tep_db_input($HTTP_POST_VARS['register_newsletter_email']) . "'");
                            if (tep_db_num_rows($check_email_q1) >0) {
                            }
                            else {
                                //insert into email_registered_customers
                                $sql_data_array = array('customers_email_address' => $HTTP_POST_VARS['register_newsletter_email'], 'option_id' => 1);
                                tep_db_perform(TABLE_EMAIL_REGISTERED_CUSTOMERS, $sql_data_array);
                            }
                          }
                          elseif (tep_db_num_rows($check_email_q) == 1) {
                            //update existing customer account to accept newsletter
                            $check_email = tep_db_fetch_array($check_email_q);
                            if ($check_email['customers_newsletter'] != '1') {
                              tep_db_query("update " . TABLE_CUSTOMERS . " set customers_newsletter = '1' where customers_id = '" . (int)$check_email['customers_id'] . "'");
                            }
                          }
                          else {
                            while ($check_email = tep_db_fetch_array($check_email_q)) {
                              if ($check_email['customers_newsletter'] != '1') {
                                tep_db_query("update " . TABLE_CUSTOMERS . " set customers_newsletter = '1' where customers_id = '" . (int)$check_email['customers_id'] . "'");
                              }
                            }
                          }
                        }
                        echo '                 <h1>THANKS FOR SIGNING UP!</h1><p>Please make sure you add email from ' . STORE_DOMAIN . ' to your email address book, your email system will always recognise messages from us, ensuring our newin, latest story and special offers won&rsquo;t be missed out.</p>' . "\n";
                		  }
                		  else {
                		  	echo tep_draw_form('register_email', tep_href_link(basename(FILENAME_INFO_EMAIL_REGISTER), '', 'NONSSL', false), 'post', 'id="form-password-forgotten" onSubmit="javascript:return validate_email(\'register_email\',\'register_newsletter_email\');"') . tep_hide_session_id();
                        echo '                  <p>Stay in touch &amp; in style via your inbox, with the latest from ' . STORE_NAME . ', exclusive online offers &amp; sales, new fashion and news. Sign up today and opt-out at anytime.</p>' . "\n";
                        echo '                  <label for="register_newsletter_email">Email address:<br />' . "\n";
                        echo '                    ' . tep_draw_input_field('register_newsletter_email', '', 'id="form-password-forgotten-email" placeholder="Enter email address" required');
                        echo '                  </label><br />' . "\n";
                        //echo tep_image_submit('button_continue.gif', BOX_REGISTER_NEWSLETTER, 'class="bt-continue"');
                        echo '                            <input type="submit" class="form-bt" value="Submit" />' . "\n";
                        echo '                </form>' . "\n";
                		  }
                		?>

	            	  </div><!--/.readbale-text-->   

                </div><!--/.grid-02-02-->

                <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
                <div class="clearme"></div>
          	</div><!--/.grid-02-->
          </div><!--/.inner-->



        </div><!--/#container-->


          <script language="javascript"><!--
            function validate_email(form_name,field_name) {
              var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
              var address = document.forms[form_name].elements[field_name].value;
              if(reg.test(address) == false) {
                alert('Invalid Email Address.');
                return false;
              }
            }
          //--></script>



































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>