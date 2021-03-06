<?php

  require('includes/application_top.php');
  //require('includes/configure_simple_order_entry.php');
  require('includes/adminpwd.php');

  if (!is_goodip())
  { exit; }

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
  if ($session_started == false) {
    tep_redirect(tep_href_link(FILENAME_COOKIE_USAGE));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_LOGIN);

  $error = false;
  $email_address = tep_db_prepare_input($HTTP_GET_VARS['email_address']);

// Check if email exists
    $check_customer_query = tep_db_query("select customers_id, customers_firstname, customers_password, customers_email_address, customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($email_address) . "'");
    if (!tep_db_num_rows($check_customer_query)) {
      $error = true;
    } else {

      $check_customer = tep_db_fetch_array($check_customer_query);
// Check that password is good
	
        if (SESSION_RECREATE == 'True') {
          tep_session_recreate();
        }
        
        $cart->reset();

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
        tep_session_register('administrator_login');

//My modification - missing order - m_postage - m
if (isset($HTTP_GET_VARS['missorder']))
{	tep_session_register('missorder');}
elseif (isset($HTTP_GET_VARS['m_postage']))
{	tep_session_register('m_postage');}
elseif (isset($HTTP_GET_VARS['m_extraitems']))
{	tep_session_register('m_extraitems');}



if (isset($HTTP_GET_VARS['cash_carry']))
{	tep_session_register('cash_carry'); }
//My modification

tep_session_register('manual_order');

	// restore cart contents
        $cart->restore_contents();
        tep_redirect(MANUAL_ORDER_REDIRECT);
    }
    tep_redirect(tep_href_link(FILENAME_ADMIN,'','SSL'));

require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>