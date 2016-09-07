<?php
/*
  $Id: checkout.php,v 1.139 2013/01/30 17:34:53 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');
  require('includes/classes/http_client.php');
  require(DIR_WS_FUNCTIONS . 'ajax.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT)); //original: FILENAME_SHOPPING_CART
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
    }
  }

  require(DIR_WS_CLASSES . 'shipping.php');
  require(DIR_WS_CLASSES . 'payment.php');
  require(DIR_WS_CLASSES . 'order.php');
  require(DIR_WS_CLASSES . 'order_total.php');

//++++ QT Pro: Begin Changed code
  if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $cart->get_products();
    $any_out_of_stock = 0;
    //My modification - Bundled Products
    $stock_checks = array();
    $stock_checks = bundle_stock_first($products);
    //My modification - Bundled Products
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
    	////My modification - Bundled productds
      $stock_check = $stock_checks[$i];
      ////My modification - Bundled productds
     /*if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))
     { $stock_check = tep_check_stock($products[$i]['id'], $products[$i]['quantity'], $products[$i]['attributes']);
     }
     else
     { $stock_check = tep_check_stock($products[$i]['id'], $products[$i]['quantity']);
     }*/
      if ($stock_check) $any_out_of_stock = 1;
  	}
    if ($any_out_of_stock == 1)
    {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
      break;
    }
 	}
//++++ QT Pro: End Changed Code


  //201604
  if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) {
  	//ajax call
  }
  elseif (tep_session_is_registered('customer_default_address_id')) { //reset the $customer_default_address_id if the current $customer_default_address_id has empty address
    $check_address_empty_1 = tep_db_fetch_array(tep_db_query("select entry_country_id from " . TABLE_ADDRESS_BOOK . " where address_book_id = '" . (int)$customer_default_address_id . "'"));
    $input_address_first = false;
  	if ((int)$check_address_empty_1['entry_country_id'] <1) {
  		$input_address_first = true;
      $check_address_empty_2_q = tep_db_query("select address_book_id, entry_country_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "'");
      if (tep_db_num_rows($check_address_empty_2_q)>1) {
      	while ($check_address_empty_2 = tep_db_fetch_array($check_address_empty_2_q)) {
      		if ((int)$check_address_empty_2['entry_country_id']>0) {
      			tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$check_address_empty_2['address_book_id'] . "' where customers_id = '" . (int)$customer_id . "'");
      			$customer_default_address_id = $check_address_empty_2['address_book_id'];
      			$input_address_first = false;
      			break;
      		}
        }
      }
  	}
  }
  //201604


// if no shipping destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('sendto')) {
    tep_session_register('sendto');
    $sendto = $customer_default_address_id;
  } else {
// verify the selected shipping address
    if ( (is_array($sendto) && empty($sendto)) || is_numeric($sendto) ) {
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$sendto . "'");
      $check_address = tep_db_fetch_array($check_address_query);
 
      if ($check_address['total'] != '1') {
        $sendto = $customer_default_address_id;
        if (tep_session_is_registered('shipping')) tep_session_unregister('shipping');
      }
    }
  }

  if (!tep_not_null($sendto)) $sendto = $customer_default_address_id;

// if no billing destination address was selected, use the customers own address as default
  if (!tep_session_is_registered('billto')) {
    tep_session_register('billto');
    $billto = $customer_default_address_id;
  } else {
// verify the selected billing address
    if ( (is_array($billto) && empty($billto)) || is_numeric($billto) ) {
      $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$billto . "'");
      $check_address = tep_db_fetch_array($check_address_query);

      if ($check_address['total'] != '1') {
        $billto = $customer_default_address_id;
        if (tep_session_is_registered('payment')) tep_session_unregister('payment');
      }
    }
  }
  if (!tep_not_null($billto)) $billto = $customer_default_address_id;

  $order = new order;

  //20141014 redirect to shopping_cart
  //if ($order->info['subtotal']> 8000) tep_redirect(tep_href_link(FILENAME_CUSTOMER_SERVICES));
  //20141014 redirect to shopping_cart

  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();
  $total_ship_count = $cart->count_ship_contents(); // Free shipping per product 1.0
// Start - CREDIT CLASS Gift Voucher Contribution
  $total_count = $cart->count_contents_virtual();
// End - CREDIT CLASS Gift Voucher Contribution
// load the selected shipping module

  $shipping_modules = new shipping;

// if the order contains only virtual products, forward the customer to the billing page as
// a shipping address is not needed
// Start - CREDIT CLASS Gift Voucher Contribution
//  if ($order->content_type == 'virtual') {
  if (($order->content_type == 'virtual') || ($order->content_type == 'virtual_weight') ) {
// End - CREDIT CLASS Gift Voucher Contribution
    if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
    $shipping = false;
    $sendto = false;
    //tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }

// Cash and Carry
  if (is_cashcarry()) {
    if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
    $shipping = false;
    $sendto = false;
    $cash_carry_order = true;
    //tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
  }
// Cash and Carry

// Start - CREDIT CLASS Gift Voucher Contribution
  //if ($credit_covers) $payment='credit_covers';

// End - CREDIT CLASS Gift Voucher Contribution
  $payment_modules = new payment;
  //$payment_modules = new payment($payment);////

  $payment_modules->update_status();////


  if (is_array($payment_modules->modules)) {
    $payment_modules->pre_confirmation_check();
  }

  // Start - CREDIT CLASS Gift Voucher Contribution
  $order_total_modules = new order_total;
  //$order_total_modules->clear_posts(); //Called in checkout process to clear session variables created by each credit class module.

  //$order_total_modules->collect_posts(); //validate code
  
  //$order_total_modules->process();

  //if(tep_session_is_registered('cot_gv')) {
    //$order_total_modules->pre_confirmation_check();
  //}
  if (!tep_session_is_registered('shipping'))	tep_session_register('shipping');
  if (!tep_session_is_registered('payment')) tep_session_register('payment');
/*
// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!tep_session_is_registered('shipping')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }
*/

/*
  if (!tep_session_is_registered('payment')) tep_session_register('payment');
  if (isset($HTTP_POST_VARS['payment'])) $payment = $HTTP_POST_VARS['payment'];

  if (!tep_session_is_registered('comments')) tep_session_register('comments');
  if (tep_not_null($HTTP_POST_VARS['comments'])) {
    $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
  }
  else {
  	$comments = null;
  }
*/


// Start - CREDIT CLASS Gift Voucher Contribution
if (!isset($HTTP_POST_VARS['action'])) {
	//if not ajax reload
  if(tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');
  if(tep_session_is_registered('cot_gv')) tep_session_unregister('cot_gv');
}
// End - CREDIT CLASS Gift Voucher Contribution


  /*if ( ($payment_modules->selected_module != $payment) || ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
  }*/
  


/*
  if ( ($payment_modules->selected_module != $payment) || ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
    if (!$credit_covers) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
    }
  }
*/
/*
// >>> FOR ERROR gv_redeem_code NULL 
if (isset($_POST['gv_redeem_code']) && ($_POST['gv_redeem_code'] == null)) {tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));}
// <<< end for error
*/
/*
//  if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
  if ( (is_array($payment_modules->modules)) && (sizeof($payment_modules->modules) > 1) && (!is_object($$payment)) && (!$credit_covers) ) {
// End - CREDIT CLASS Gift Voucher Contribution
  }
*/


  //require(DIR_WS_CLASSES . 'order_total.php');
  //$order_total_modules = new order_total;
// Start - CREDIT CLASS Gift Voucher Contribution
//  require(DIR_WS_CLASSES . 'order_total.php');
//  $order_total_modules = new order_total;
// End - CREDIT CLASS Gift Voucher Contribution

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_SHIPPING);
  //require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PAYMENT);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_CONFIRMATION);

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2);

?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>
      <meta name="keywords" id="keywords" content="" />
      <meta name="description" id="description" content="" />
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
      <!--<script src="ext/jquery/jquery-ui-1.9.2.custom.js"></script>-->
      <link rel="stylesheet" type="text/css" href="ext/jquery/ui/redmond/jquery-ui-1.11.4.css">
      <script src="ext/jquery/ui/jquery-ui-1.11.4.min.js"></script>
      <script src="ext/jquery/jquery.dialogOptions.js"></script>


      <!-- //craftyclicks -->
      <?php
          require('postcode/crafty_html_output.php');
          echo tep_crafty_script_add('address_update');
          //echo tep_crafty_script_add2('se_address');
      ?>
      <script type="text/javascript">
        var formname = 'address_update';
        var formname2 = 'se_address';
      </script>
      <script type="text/javascript" src="postcode/crafty_mymod_c.js"></script>
      <!-- //craftyclicks -->
      <?php
          // +Country-State Selector    
          require(DIR_WS_INCLUDES . 'z-dhtml/ajax.js.php');
          // -Country-State Selector
      ?>

      <?php
      if (is_cashcarry()) {
      	echo '<script type="text/javascript" src="' . DIR_WS_INCLUDES . 'z-dhtml/cash_carry/cash_carry.js"></script>';
      	echo '<script type="text/javascript">window.onload = function() { openChild(\'' . tep_href_link('popup_cashcarry_print.php') . '\', \'win2\')}</script>';
      }
      ?>






      <style type='text/css'>
      	button {display: none;}
      	#address-update{display:none}
      	#new_billing_address_info {}
      	#shipping_sum{display:none}
      	#payment_pane{display:none}
      	#se-selected-edit {text-align:right;position:relative;padding:0px 0px 0px 0px;cursor:pointer;cursor:hand;}
      	.whiteopen {}
      </style>

		  <script>
			/*<![CDATA[*/
			var osCsid = "<?php echo tep_session_id();?>";
      var url = 'checkout.php';
      document.write("");
			$(document).ready(function(){


          /* order-total refresh */
          function ot_refresh() {
              $.ajax( {
                type: "POST",
                url: "<?php echo 'simplecheckout/confirmation.php';?>",
                cache: false,
                data: "action=ot_refresh"+"&osCsid=" + osCsid,
                beforeSend: function(x) {
                  if(x && x.overrideMimeType) { x.overrideMimeType("application/html;charset=UTF-8"); }
                  $('#order-total').addClass('loadingx');
                  },
                success: function(html) {
                 	$("div#order-total").html(html);
                 	$('#order-total').removeClass('loadingx');
                },
                error: function (xhr, status) {}
              });
          }

/*
			    var shipping_of_order = "<?php echo $shipping['id'];?>";
			    if (shipping_of_order) {
			    	var shipping_selected = $("#delivery_reload").find('input:radio[name=shipping][checked]').val();
            if (shipping_selected)  {
          	  if (shipping_selected != shipping_of_order) {ot_refresh();}
            }
			    }
			    else {
			    	//when user first come to the page without a previous selected shipping method refresh the order total to get correct order total //this is necessary because the line of $order = new order; is executed before the $shipping is in place therefore the $order_total_modules->output_confirmpage() sometime may not reflect the true when the shipping is first registered
			    	ot_refresh();
			    }
*/
          ot_refresh();

          function bsp_sum_refresh(panenumber) {
              //1-address, 2-shipping, 3-payment
              if (panenumber ==1 ) {
                //addresses
                if ($("#addresses-display").css("display") == "none") {}
                else { $("#addresses-display").hide(); }
                if ($("#address-update").css("display") == "none") {$("#address-update").show();}
                else {}

                //shipping 
                if ($("#shipping_sum").css("display") == "none") {$("#shipping_sum").show().children().show();}
                else {}
                if ($("#shipping_pane").css("display") == "none") {}
                else {$("#shipping_pane").hide();}
      
                //payment
                if ($("#payment_sum").css("display") == "none") {$("#payment_sum").show();}
                else {}
                if ($("#payment_pane").css("display") == "none") {}
                else {$("#payment_pane").hide();}
              }
              else if (panenumber ==2) {
                //shipping
                if ($("#shipping_sum").css("display") == "none") {}
                else { $("#shipping_sum").hide(); }
                if ($("#shipping_pane").css("display") == "none") {$("#shipping_pane").show();}
                else {}
      
                //address
                if ($("#addresses-display").css("display") == "none") {$("#addresses-display").show();}
                else {}
                if ($("#address-update").css("display") == "none") {}
                else {$("#address-update").hide();}
      
                //payment
                if ($("#payment_sum").css("display") == "none") {$("#payment_sum").show();}
                else {}
                if ($("#payment_pane").css("display") == "none") {}
                else {$("#payment_pane").hide();}
              }
              else if (panenumber >2) {
                //address
                if ($("#addresses-display").css("display") == "none") {$("#addresses-display").show();}
                else {}
                if ($("#address-update").css("display") == "none") {}
                else {$("#address-update").hide();}
      
                //shipping 
                if ($("#shipping_sum").css("display") == "none") {$("#shipping_sum").show().children().show();}
                else {}
                if ($("#shipping_pane").css("display") == "none") {}
                else {$("#shipping_pane").hide();}
      
                //payment
                if ($("#payment_sum").css("display") == "none") {}
                else { $("#payment_sum").hide(); }
                if ($("#payment_pane").css("display") == "none") {$("#payment_pane").show();}
                else {}
              }
          }


          $("#delivery_reload")
          .on('change', 'input:radio[name=shipping]', function() {
            //$('#delivery_reload').load(url + " #delivery_reload", {'shipping':$(this).val(),'action':'shippingSet'}, function(){ot_refresh();});
          })
          .on('mouseover', '.moduleRow', function() {
            $(this).addClass('moduleRowOver');
          })
          .on('mouseout', '.moduleRow', function() {
            $(this).removeClass('moduleRowOver');
          })
          .on('click', '.moduleRow', function() {
            $("input:radio[name=shipping]", "#delivery_reload").removeAttr("checked");
            $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
            $(this).find('input:radio[name=shipping]').attr("checked", "checked");
            $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');
            //safeplace
            var sshiping_id = $(this).find('input:radio[name=shipping][checked]').val();
            if (typeof(sshiping_id) == 'undefined' || sshiping_id == null) {
              return false;
            }

                    //safeplace
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       data: "action=shipping_safeplace&shipping="+sshiping_id+"&text=<?php echo TABLE_HEADING_COMMENTS_DELIVERY;?>"+"&osCsid=" + osCsid,
      
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/html;charset=UTF-8");
                        }
                        //$(this).addClass('loadingx');
                       },
                       success: function(html) {
                         $("#delivery_reload").find('#safeplace').html(html);//hide().fadeIn('fast');
                         //$(this).removeClass('loadingx');
                       },
                       error: function (xhr, status) {}
                    });
          });


          function protx_update_fields(value) {
              if (value == "AMEX" || value == "SOLO" || value == "MAESTRO") {
                $(".protx_hidden").css("visibility","");
              } else {
                $(".protx_hidden").css("visibility", "hidden");
                $('input[type=text][name=protx_direct_cc_issue]').val("");
              }
          }

          protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
          $("#payment_reload").on('change', 'input:radio[name=payment]', function() {
          })
          .on('change', 'select[name=protx_direct_cc_type]', function() {
            protx_update_fields($(this).val());
          })
          .on('mouseover', '.moduleRow', function() {
            $(this).addClass('moduleRowOver');
          })
          .on('mouseout', '.moduleRow', function() {
            $(this).removeClass('moduleRowOver');
          })
          .on('click', '.moduleRow', function() {
            $("input:radio[name=payment]", "#payment_reload").removeAttr("checked");
            $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
            $(this).find('input:radio[name=payment]').attr("checked", "checked");
            $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

            $('#payment_reload').load(url + " #payment_reload", {'payment':$(this).find('input:radio[name=payment]').val(),'action':'paymentSet', 'osCsid':osCsid}, function() {
              //init_thickbox();
              protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
              //ot_refresh();
            });
          })
          .on('click', 'input#submitpayment', function(e) {

    	      $(this).css({ borderStyle:"inset", cursor:"wait" });
    	      $(this).attr('disabled','disabled');

            if ($("#credit_cover_div").find('input[type="checkbox"][name="cot_gv"]').is(':checked')) var cot_gv_on = true;
            else var cot_gv_on = false;

            var rctrue= true;
            if (cot_gv_on && ($('#payment_reload').find('input:radio[name=payment]').attr('disabled'))) {
            	//credit cover
            }
            else if ($('#credit_cover_t').val()>0) {
            	//credit cover
            }
            else {
            	if ($('input[type=radio][name=payment]', '#payment-options-form').length) {
            		var payment_s = $("#payment_reload").find('input:radio[name=payment][checked]').val();
            	  if (!payment_s) alert("Please select payment option");
                rctrue = rctrue && payment_s;
              }
            }

            var cash_carry_order="<?php echo $cash_carry_order; ?>";
            if (cash_carry_order) {
            }
            else {
            	//if ($('input[type=radio][name=shipping]', '#delivery-options-form').length) {
            		var shipping_s = $("#delivery_reload").find('input:radio[name=shipping][checked]').val();
                if (!shipping_s) alert("Please select shipping option");
                rctrue = rctrue && shipping_s;
              //}
            }
    	  	  if ($(this).attr('disabled')) {
              $(this).css({ borderStyle:"none", cursor:"hand", cursor:"pointer" });
              $(this).removeAttr('disabled');
    	  	  }

            //furthur check bofore confirm order
            if (rctrue) {
            }
            else {
            	//fail placing the order
            	e.preventDefault();
            	return false;
            }
          })
          .on('click', 'a#submitpayment', function(e) {

    	      e.preventDefault();
    	      $(this).css({ borderStyle:"inset", cursor:"wait"});
    	      $(this).attr('disabled','disabled');
    	      $('#payment_reload').find('#paymentinput').hide();
    	      $('#payment_reload').find('#paymentloading').show().addClass('loadingx');

            if ($("#credit_cover_div").find('input[type="checkbox"][name="cot_gv"]').is(':checked')) var cot_gv_on = true;
            else var cot_gv_on = false;

            var rctrue= true;
            if (cot_gv_on && ($('#payment_reload').find('input:radio[name=payment]').attr('disabled'))) {
            	//credit cover
            }
            else if ($('#credit_cover_t').val()>0) {
            	//credit cover
            }
            else {
            	if ($('input[type=radio][name=payment]', '#payment-options-form').length) {
            		var payment_s = $("#payment_reload").find('input:radio[name=payment][checked]').val();
            	  if (!payment_s) alert("Please select payment option");
                rctrue = rctrue && payment_s;
              }
            }

            var cash_carry_order="<?php echo $cash_carry_order; ?>";
            if (cash_carry_order) {
            }
            else {
              //if ($('input[type=radio][name=shipping]', '#delivery-options-form').length) {
                var shipping_s = $("#delivery_reload").find('input:radio[name=shipping][checked]').val();
                if (!shipping_s) alert("Please select shipping option");
                rctrue = rctrue && shipping_s;
              //}
            }

            //furthur check bofore confirm order
            if (rctrue) {
            	//ajax
            	var checkoutsuccess = "<?php echo tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');?>";
              var inputs = [];
              $(":input").each(
                 function() {
                   inputs.push(this.name + "=" + escape(this.value));
                 }
               );

              $.ajax({
                url: "<?php echo str_replace('&amp;', '&', tep_href_link(FILENAME_PROTX_PROCESS, 'action=process', 'SSL')); ?>",
                cache: false,
                type: "POST",
                dataType: "html",
                data: inputs.join("&"),
                success: function(html)
                {
                  if (html=='normalsuccess') window.location.href = checkoutsuccess;
                  else {
                  	//error
                  	$('#payment_reload').find('#paymentloading').removeClass('loadingx');
                    if (html.indexOf("Sorry your payment could not be processed") > -1) {
                      $('#payment_reload').find('#paymentloading').html(html);
                      setTimeout(function() {
                        $('#payment_reload').find('#paymentloading').empty().hide();
                        $('#payment_reload').find('#paymentinput').show();
                      }, 2500);
                      if ($('a#submitpayment').attr('disabled')) {
                        $('a#submitpayment').css({ borderStyle:"none", cursor:"hand", cursor:"pointer" });
                        $('a#submitpayment').removeAttr('disabled');
                      }
                      //if ($('a#submitpayment').is(":hidden")) $('a#submitpayment').show();
                    }
                    else {
                    	//3d
                    	$('#payment_reload').find('#paymentloading').html(html);
                    	$('a#submitpayment').hide();
                    }
                  }

                }
              });
            }
            else {
            	//fail placing the order
              $('#payment_reload').find('#paymentloading').empty().hide();
              $('#payment_reload').find('#paymentinput').show();
              if ($('a#submitpayment').attr('disabled')) {
                $('a#submitpayment').css({ borderStyle:"none", cursor:"hand", cursor:"pointer" });
                $('a#submitpayment').removeAttr('disabled');
              }
            	return false;
            }
          });

          function tosameadd_reset() {
            //reset tosameadd radio buttons
            if ($('#address-update').find('#se-selected').parent().css('display') != 'none') {
              //to different shipping address
              $("input:radio[name=tosameadd][value=no]", "#address-update").parent().parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
              $("input:radio[name=tosameadd][value=no]", "#address-update").attr("checked", "checked");
              $("input:radio[name=tosameadd][value=no]", "#address-update").parent().addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');
            }
            else {
              $("input:radio[name=tosameadd][value=yes]", "#address-update").parent().parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
              $("input:radio[name=tosameadd][value=yes]", "#address-update").attr("checked", "checked");
              $("input:radio[name=tosameadd][value=yes]", "#address-update").parent().addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');
            }
          }


          //address update radio button   //address-update ->be_select
          $("#be_select")
          .on('change', 'input:radio[name=billing_add_list]', function() {
          })
          .on('mouseover', '.moduleRow', function() {
            $(this).addClass('moduleRowOver');
          })
          .on('mouseout', '.moduleRow', function() {
            $(this).removeClass('moduleRowOver');
          })
          .on('click', '.moduleRow', function() {
            $("input:radio[name=billing_add_list]", "#be_select").removeAttr("checked");
            $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
            $(this).find('input:radio[name=billing_add_list]').attr("checked", "checked");
            $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

            //update edit for content
            var add_id = $(this).find('input:radio[name=billing_add_list]').val();
            if (isNaN(add_id)) {add_id=0;}
            var customer_id = "<?php echo $customer_id; ?>";
            //var string_data= '{"action":"pop_address_1",' + '"add_id":"' + add_id + '","customer_id":"' + customer_id + '"}';
            var string_data= '{"action":"address_popup",' + '"address_book_id":"' + add_id + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
            var JSONObject = eval ("(" + string_data + ")");
            update_edit_form(add_id, JSONObject, 1);
            //reset tosameadd radio buttons
            //tosameadd_reset();
          });

          //ajax function updating the edit form
    	    function update_edit_form(add_id, JSONObject,showloading) {
						$.ajax( {
               type: "POST",
	             url: "<?php echo 'simplecheckout/address_update.php';?>",
	             cache: false,
	             beforeSend: function(x) {
	               if(x && x.overrideMimeType) {
	                 x.overrideMimeType("application/json;charset=UTF-8");
	               }
	               if (showloading>0) {
	                 //$("#address-update").show().children().show();
	                 $("#address-update").find('form#address_update').addClass('loadingx');
	               }
	             },
	             dataType: "json",
	             data: JSONObject,
	             success: function(html) {

	             	 var str=html;
                 if ($('input[name=gender]:radio', '#address-update').length > 0) {
                   $('input[name=gender]:radio', '#address-update').filter("[value="+str.entry_gender+"]").attr("checked","checked");
                 }
                 if ($("#address-update").find('#be_firstname').length > 0) {
                   $("#address-update").find('#be_firstname').val(str.entry_firstname).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_lastname').length > 0) {
                   $("#address-update").find('#be_lastname').val(str.entry_lastname).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_country').length > 0) {
                   if (str.entry_country_id ==0) str.entry_country_id=222;
                   $("#address-update").find('#be_country').val(str.entry_country_id);
                 }
                 if ($("#address-update").find('#be_postcode').length > 0) {
                   $("#address-update").find('#be_postcode').val(str.entry_postcode).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_company').length > 0) {
                   $("#address-update").find('#be_company').val(str.entry_company);
                 }
                 if ($("#address-update").find('#be_house_name').length > 0) {
                   $("#address-update").find('#be_house_name').val(str.entry_house_name).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_street_address').length > 0) {
                   $("#address-update").find('#be_street_address').val(str.entry_street_address).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_suburb').length > 0) {
                   $("#address-update").find('#be_suburb').val(str.entry_suburb);
                 }
                 if ($("#address-update").find('#be_city').length > 0) {
                   $("#address-update").find('#be_city').val(str.entry_city).removeClass( "ui-state-error" );
                 }
                 if ($("#address-update").find('#be_state_input').length > 0) {
                   $("#address-update").find("#be_state_input").html(str.state_entry);
                   $("#address-update").find("#be_state_input").children().removeClass('p_popup').addClass('register-input');
                 }
                 if ($("#address-update").find('#be_update_address_book_id').length > 0) {
                   if (typeof(str.address_book_id) == 'undefined' || str.address_book_id == null) {
                   	 $("#address-update").find('#be_update_address_book_id').val(0);
                   }
                   else {
                   	 $("#address-update").find('#be_update_address_book_id').val(str.address_book_id);
                   }
                 }
                 if ($("#address-update").find('#be_update_old_id').length > 0) {
                 	 $("#address-update").find('#be_update_old_id').val(0);
                 }

                 if (showloading>0) {
                   $("#address-update").find('form#address_update').removeClass('loadingx');
                 }

                 //$("#address-update").fadeIn('fast');//$("#address-update").show();$("#address-update").css("display","");
                  //find postcode button on/off
                  switchmode();
               },
               error: function (xhr, status) {
               }
            });
          }


          //address edit button
          $("div#addresses-display").on('click', '.edit_addresses', function() {
              bsp_sum_refresh(1);
              //pre fill the address edit form
              //var be_update_address_book_id = $('#address-update').find("input:radio[name=billing_add_list][checked]").val();
              var be_update_address_book_id = $('#address-update').find('#be_update_address_book_id').val();
              var billto_id = "<?php echo $billto; ?>";
              if (be_update_address_book_id>0) billto_id = be_update_address_book_id;
              if (isNaN(billto_id)) billto_id=0;
              var customer_id = "<?php echo $customer_id; ?>";
              //var string_data= '{"action":"pop_address_1",' + '"add_id":"' + add_id + '","customer_id":"' + customer_id + '"}';
              var string_data= '{"action":"address_popup",' + '"address_book_id":"' + billto_id + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
              var JSONObject = eval ("(" + string_data + ")");
              //switchmode(); //postcodeanywhere
  
              update_edit_form(billto_id, JSONObject, 1);
              //$("#address-update").css("display","");
              //reset tosameadd radio buttons
              //tosameadd_reset();
  
              //var se_update_address_book_id = $('#se-address').find('#se_list').find("input:radio[name=shipping_add_list][checked]").val();
              var se_update_address_book_id = $('#se-address').find('#se_update_address_book_id').val();
              if (typeof(se_update_address_book_id) == 'undefined' || se_update_address_book_id == null || se_update_address_book_id <1) se_update_address_book_id = $('#address-update').find('#be_update_sendto_id').val();
              var sendto_id = "<?php echo $sendto; ?>";
              if (se_update_address_book_id>0) {sendto_id = se_update_address_book_id;}
              if (isNaN(sendto_id)) sendto_id =0;
              if (billto_id == sendto_id) {
                $('#address-update').find('#se-selected').parent().hide();
              }
              else {
                //alert(u_address_label);
                $('#address-update').find('#se-selected').parent().show();
                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   data: "action=se-selected_update&address_book_id="+sendto_id+"&customer_id="+customer_id+"&osCsid=" + osCsid,
                   beforeSend: function(x) {
                    if(x && x.overrideMimeType) {
                      x.overrideMimeType("application/html;charset=UTF-8");
                    }
                   },
                   success: function(html) {
                     $('#address-update').find('#se-selected').find('span').html(html);
                   },
                   error: function (xhr, status) {}
                });
                //$('#address-update').find('#se-selected').trigger('refresh');
              }
              tosameadd_reset();
          });



          //update state_input/zone_id
          $( "#se-address").on('change', '#se_country', function() {
              if ($("#se-address").find('#se_state_input').length > 0) {
                var update_address_book_id = $("#se-address").find('#se_update_address_book_id').val();
                var country_id = $("#se-address").find('#se_country').val();
                var customer_id = "<?php echo $customer_id; ?>";
                var string_data= '{"action":"update_state_input",' + '"update_address_book_id":"' + update_address_book_id + '","country":"' + country_id + '", "customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
                var JSONObject = eval ("(" + string_data + ")");

                switchmode(); //postcodeanywhere
                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   beforeSend: function(x) {
                     if(x && x.overrideMimeType) {
                       x.overrideMimeType("application/json;charset=UTF-8");
                     }
                   },
                   dataType: "json",
                   data: JSONObject,
                   success: function(html) {
                     var str=html;
                     $("#se-address").find("#se_state_input").html(str.state_entry).children().removeClass('register-input').addClass('p_popup ui-widget-content ui-corner-all');
                   }
                });
              }
          });


    	    function update_edit_form_s(add_id, JSONObject,showloading) {
						$.ajax( {
               type: "POST",
	             url: "<?php echo 'simplecheckout/address_update.php';?>",
	             cache: false,
	             beforeSend: function(x) {
	               if(x && x.overrideMimeType) {
	                 x.overrideMimeType("application/json;charset=UTF-8");
	               }
	             },
	             dataType: "json",
	             data: JSONObject,
	             success: function(html) {
	             	 var str=html;
                 if ($('input[name=gender]:radio', '#se-address').length > 0) {
                     $('input[name=gender]:radio', '#se-address').filter("[value="+str.entry_gender+"]").attr("checked","checked");
                 }
                 if ($("#se-address").find('#se_firstname').length > 0) {
                     $("#se-address").find('#se_firstname').val(str.entry_firstname).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_lastname').length > 0) {
                     $("#se-address").find('#se_lastname').val(str.entry_lastname).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_country').length > 0) {
                 	   if (str.entry_country_id ==0) str.entry_country_id=222;
                     $("#se-address").find('#se_country').val(str.entry_country_id);
                 }
                 if ($("#se-address").find('#se_postcode').length > 0) {
                     $("#se-address").find('#se_postcode').val(str.entry_postcode).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_company').length > 0) {
                     $("#se-address").find('#se_company').val(str.entry_company);
                 }
                 if ($("#se-address").find('#se_house_name').length > 0) {
                     $("#se-address").find('#se_house_name').val(str.entry_house_name).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_street_address').length > 0) {
                     $("#se-address").find('#se_street_address').val(str.entry_street_address).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_suburb').length > 0) {
                     $("#se-address").find('#se_suburb').val(str.entry_suburb);
                 }
                 if ($("#se-address").find('#se_city').length > 0) {
                     $("#se-address").find('#se_city').val(str.entry_city).removeClass( "ui-state-error" );
                 }
                 if ($("#se-address").find('#se_state_input').length > 0) {
                     $("#se-address").find("#se_state_input").html(str.state_entry);
                 }
                 if ($("#se-address").find('#se_update_address_book_id').length > 0) {
                   if (typeof(str.address_book_id) == 'undefined' || str.address_book_id == null) {
                   	 $("#se-address").find('#se_update_address_book_id').val(0);
                   }
                   else {
                   	 $("#se-address").find('#se_update_address_book_id').val(str.address_book_id);
                   }
                 }
                 if ($("#se-address").find('#se_update_old_id').length > 0) {
                 	 $("#se-address").find('#se_update_old_id').val(0);
                 }
                 $("#se-address").find('.validateTips').html("<?php echo FORM_REQUIRED_INFORMATION;?>");

                  //find postcode button on/off
                  switchmode();
               },
               error: function (xhr, status) {
                 document.location.href="<?php echo tep_href_link(FILENAME_CHECKOUT, '', 'SSL'); ?>";
               }
            });
          }

          function se_address() {
      	    //open dialog for different shipping address
      	    /* pre populate dialog-form*/
            var customer_id = "<?php echo $customer_id; ?>";
				    var se_update_address_book_id = $('#se-address').find('#se_update_address_book_id').val();
				    if (typeof(se_update_address_book_id) == 'undefined' || se_update_address_book_id == null || se_update_address_book_id <1) { se_update_address_book_id = $('#address-update').find('#be_update_sendto_id').val();}
				    var sendto_id = "<?php echo $sendto; ?>";
				    if (se_update_address_book_id>0) sendto_id = se_update_address_book_id;
				    if (isNaN(sendto_id)) sendto_id =0;
				    var billing_id = $("#address-update").find('#be_update_address_book_id').val();
            //se_list
						$.ajax( {
               type: "POST",
	             url: "<?php echo 'simplecheckout/address_update.php';?>",
	             cache: false,
	             data: "action=se_list&address_book_id="+sendto_id+"&customer_id="+customer_id+"&billing_id=" + billing_id + "&osCsid=" + osCsid,
	             beforeSend: function(x) {
	              if(x && x.overrideMimeType) {
	                x.overrideMimeType("application/html;charset=UTF-8");
	              }
	             },
	             success: function(html) {
		             $('#se-address').find('#se_list').html(html);
		             //reset radio buttons row style
		             $('#se-address').find('#se_list').find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
                 $('#se-address').find('#se_list').find("input:radio[name=shipping_add_list][checked]").parent().parent().addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

				         if (isNaN($('#se-address').find('#se_list').find("input:radio[name=shipping_add_list][checked]").val())) {
				         	 //add shipping address 
				         	 var string_data= '{"action":"address_popup",' + '"address_book_id":"0","customer_id":"' + customer_id + '"}';
				           var JSONObject = eval ("(" + string_data + ")");
				         	 update_edit_form_s(sendto_id, JSONObject,0);
				         }
				         else {
				         	 var string_data= '{"action":"address_popup",' + '"address_book_id":"' + $('#se-address').find('#se_list').find("input:radio[name=shipping_add_list][checked]").val() + '","customer_id":"' + customer_id + '"}';
				           var JSONObject = eval ("(" + string_data + ")");
				         	 update_edit_form_s(sendto_id, JSONObject,0);
				         }
                 $('#se-address').find('#change_billingadd_warning').hide().html("").removeClass("ui-state-error");                 
                 $( "#se-address" ).dialog( "open" );
               },
               error: function (xhr, status) {}
            });

          }

          //ship to same/different address update radio button   //address-update ->tosameadd_select
          $("#tosameadd_select")
          .on('change', 'input:radio[name=tosameadd]', function() {
          })
          .on('mouseover', '.moduleRow', function() {
            $(this).addClass('moduleRowOver');
          })
          .on('mouseout', '.moduleRow', function() {
            $(this).removeClass('moduleRowOver');
          })
          .on('click', '.moduleRow', function() {
            $("input:radio[name=tosameadd]", "#address-update").removeAttr("checked");
            $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
            $(this).find('input:radio[name=tosameadd]').attr("checked", "checked");
            $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

            if ($("input:radio[name=tosameadd][checked]", "#address-update").val() == "no") {
              se_address();
            }
            else {
                $('#address-update').find('#se-selected').parent().fadeOut("fast");
                //reset the sendto same as the billing address
                var customer_id = "<?php echo $customer_id; ?>";
                var be_update_address_book_id = $('#address-update').find("input:radio[name=billing_add_list][checked]").val();
                var sendto_id = "<?php echo $billto; ?>";
                if (be_update_address_book_id>0) sendto_id = be_update_address_book_id;
                var formid = $("#address-update").find('input[name=formid]');
                var address_action = "set_se";
                var string_data= '{"action":"' + address_action + '",' + 
                                  '"address_book_id":"' + sendto_id + '",' +
                                  '"customer_id":"' + customer_id + '",' +
                                  '"formid":"' + formid.val() + '",' +
                                  '"osCsid":"'+osCsid+'"}';

                var JSONObject = eval ("(" + string_data + ")");
                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   beforeSend: function(x) {
                     if(x && x.overrideMimeType) {
                       x.overrideMimeType("application/json;charset=UTF-8");
                     }
                   },
                   data: JSONObject,
                   success: function(html) {
                      $('#pre-display-address').load(url + " #pre-display-address", {'action':'reload', 'osCsid':osCsid}, function() {});
                      $('#delivery_reload').load(url + " #delivery_reload", {'action':'reload', 'osCsid':osCsid}, function() {
                        $("#shipping_sum").load(url + " #shipping_sum", {'action':'reload', 'osCsid':osCsid}, function() {
                          $("#shipping_sum").show().children().show(); //for some reason without this it does not work
                          //$("#shipping_sum button").button(); 
                        });
                        ot_refresh();
                      });
                      //update latest shipping id
                      $("#se-address").find('#se_update_address_book_id').val(html.sendto);
                      //reset radio button of se_list
                      var se_update_address_book_id = $('#se-address').find('#se_update_address_book_id').val();
                      var sendto_id = "<?php echo $sendto; ?>";
                      if (se_update_address_book_id>0) sendto_id = se_update_address_book_id;
                      if (isNaN(sendto_id)) sendto_id =0;
                      $("input:radio[name=shipping_add_list]", "#se-address").removeAttr("checked");
                      $('#se-address').find("#se_list").find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
                      $("input:radio[name=shipping_add_list][value="+sendto_id+"]", "#se-address").attr("checked", "checked");
                      $("input:radio[name=shipping_add_list][value="+sendto_id+"]", "#se-address").parent().parent().addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');
                      //reset radio button of se_list
                   },
                   error: function (xhr, status) {
                   }
                });
            }
          });


          $('div#address-update').on('click', '#se-selected-edit', function() {
  	        se_address();
          })
          .on('change', '#billing_edit', function() {
            //bill address form change
            $('#address-update').find('#billing_edit').find('#be_update_old_id').val(1);
          });

          //tips = $( "#se-address" ).find( ".validateTips" );
          //var postcode_max_length = 10;

          function updateTips( t ) {
            tips.text( t ).addClass( "ui-state-highlight" );
            setTimeout(function() {
              tips.removeClass( "ui-state-highlight", 3000 );
            }, 3000 );
          }

          function checkLength( o, n, min, max ) {
            //if ( o.val().length > max || o.val().length < min ) {
            if (o.attr("type") == "hidden") {
              return true;
            }
            else if (typeof(o) == 'undefined' || o == null) {
            	return true;
            }

            if ( o.val().length < min ) {
              o.addClass( "ui-state-error" );
              if (max >0) {
              	updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
              }
              else {
                updateTips( n );
              }
              return false;
            }

            else if (o.val().length > max && max >0) {
              o.addClass( "ui-state-error" );
              updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
              return false;
            }
            else {
              return true;
            }
          }

          function checkRegexp( o, regexp, n ) {
            if ( !( regexp.test( o.val() ) ) ) {
              o.addClass( "ui-state-error" );
              updateTips( n );
              return false;
            } else {
              return true;
            }
          }

          function check_postcode(field_name, message) {
            if (field_name.attr("type") != "hidden") {
              var objRegExp =/[^A-Za-z0-9\s]/; //Only letter and number and spaces
              if (objRegExp.test(field_name.val())) {
                updateTips( message );
                return false;
              }
            }
            return true;
          }

          function check_select(field_name, field_default, message) {
            if (field_name.attr("type") != "hidden") {
              if (field_name.val() == field_default) {
                updateTips( message );
                return false;
              }
            }
            return true;
          }

          $('#se-address').on('change', '#se_address', function() {
            //form change
            $("#se-address").find('#se_update_old_id').val(1);
          })
          .on('dialogclose', function(event) {
    	      //close event: cancel, X and excape
            tosameadd_reset();
          });

          $( "#se-address" ).dialog({
            autoOpen: false,
            height: 'auto',
            width: 'auto',
            //maxWidth: 600, this does't work
            modal: true,
            fluid: true,
            resizable: false,
            create: function( event, ui ) {
              // Set maxWidth
              $(this).css("maxWidth", "600px");
              $(this).css("maxHeight", "800px");
            },
            open: function () {
                  },
            beforeClose: function(event, ui) { 
            },
            buttons: {
              "Save": function() {
                var firstname = $( "#se-address" ).find( "#se_firstname" ),
                    lastname = $( "#se-address" ).find( "#se_lastname" ),
                    house_name = $( "#se-address" ).find( "#se_house_name" ),
                    street_address = $( "#se-address" ).find( "#se_street_address" ),
                    postcode = $( "#se-address" ).find( "#se_postcode" ),
                    city = $( "#se-address" ).find( "#se_city" ),
                    country = $( "#se-address" ).find( "#se_country" ),
                    formid = $("#se-address").find('input[name=formid]');
                    tips = $( "#se-address" ).find( ".validateTips" );
                if ($('input[name=gender]:radio', '#se-address').length > 0) var gender = $('input[name=gender]:checked', '#se-address');
                if ($("#se-address").find('#se_company').length > 0)  var company = $( "#se-address" ).find( "#se_company" );
                if ($("#se-address").find('#se_suburb').length > 0) var suburb = $( "#se-address" ).find( "#se_suburb" );
                if ($("#se-address").find('#se_state_input').find('#state').length > 0) var state = $("#se-address").find('#se_state_input').find('#state');
                if ($("#se-address").find('#se_state_input').find('#zone_id').length > 0) var zone_id = $("#se-address").find('#se_state_input').find('#zone_id');
                var allFields = $( [] ).add( firstname ).add( lastname ).add( house_name ).add( street_address ).add( postcode ).add( suburb ).add( city ).add( state );

                var bValid = true;
                allFields.removeClass( "ui-state-error" );

                bValid = bValid && checkLength( firstname, "<?php echo ENTRY_FIRST_NAME_ERROR; ?>", <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>, 0);
                bValid = bValid && checkLength( lastname, "<?php echo ENTRY_LAST_NAME_ERROR; ?>", <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>, 0 );
                bValid = bValid && checkLength( house_name, "<?php echo ENTRY_HOUSE_NAME; ?>", <?php echo ENTRY_HOUSE_NAME_MIN_LENGTH; ?>, <?php echo ENTRY_HOUSE_NAME_MAX_LENGTH;?> );
                bValid = bValid && checkLength( street_address, "<?php echo ENTRY_STREET_ADDRESS; ?>", <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>, <?php echo ENTRY_STREET_ADDRESS_MAX_LENGTH;?> );
                bValid = bValid && checkLength( postcode, "<?php echo ENTRY_POST_CODE; ?>", <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>, <?php echo ENTRY_POSTCODE_MAX_LENGTH;?> );
                bValid = bValid && check_postcode(postcode, "<?php echo ENTRY_POST_CODE_ERROR2; ?>");
                if (typeof(suburb) != 'undefined' && suburb != null)
                  bValid = bValid && checkLength( suburb, "<?php echo ENTRY_SUBURB; ?>", 0, <?php echo ENTRY_SUBURB_MAX_LENGTH;?> );
                bValid = bValid && checkLength( city, "<?php echo ENTRY_CITY; ?>", <?php echo ENTRY_CITY_MIN_LENGTH; ?>, <?php echo ENTRY_CITY_MAX_LENGTH;?> );
                if (typeof(state) != 'undefined' && state != null)
                  bValid = bValid && checkLength( state, "<?php echo ENTRY_STATE; ?>", 0, <?php echo ENTRY_STATE_MAX_LENGTH;?> );

                bValid = bValid && check_select(country, "", "<?php echo ENTRY_COUNTRY_ERROR; ?>");
                bValid = bValid && (typeof(parseInt(country.val())) === 'number' && country.val() % 1 == 0);

                if ( bValid ) {
                  //My modification ajax: save it to customer
                  var customer_id = "<?php echo $customer_id; ?>";
                  var update_address_book_id = $("#se-address").find('#se_update_address_book_id').val();
                  var update_address_book_id_edit = $("#se-address").find('#se_update_old_id').val();
                  if (update_address_book_id >0 && update_address_book_id_edit <1) {
                    //different shipping address from the address book
                    var address_action = "set_se";
                  }
                  else if (update_address_book_id >0 && update_address_book_id_edit >0) {
                    //update existing address before set it for shipping address
                    var address_action = "set_save_se";
                  }
                  else if (update_address_book_id <1 && update_address_book_id_edit >0) {
                    //adding new shipping address to the address book&set
                    var address_action = "set_add_se";
                  }
      
                  var string_data= '{"action":"' + address_action + '",' + 
                                    '"address_book_id":"' + update_address_book_id + '",' +
                                    '"customer_id":"' + customer_id + '",' +
                                    '"firstname":"' + firstname.val() + '",' +
                                    '"lastname":"' + lastname.val() + '",' +
                                    '"house_name":"' + house_name.val() + '",' +
                                    '"street_address":"' + street_address.val() + '",' +
                                    '"postcode":"' + postcode.val() + '",' +
                                    '"city":"' + city.val() + '",' +
                                    '"country":"' + country.val() + '",' +
                                    ((typeof(gender) == 'undefined' || gender == null) ? '' : '"gender":"' + gender.val() + '",') +
                                    ((typeof(company) == 'undefined' || company == null) ? '' : '"company":"' + company.val() + '",') +
                                    ((typeof(suburb) == 'undefined' || suburb == null) ? '' : '"suburb":"' + suburb.val() + '",') +
                                    ((typeof(state) == 'undefined' || state == null) ? '' : '"state":"' + state.val() + '",') +
                                    ((typeof(zone_id) == 'undefined' || zone_id == null) ? '' : '"zone_id":"' + zone_id.val() + '",') +
                                    '"formid":"' + formid.val() + '", "osCsid":"'+osCsid+'"}';

                  var JSONObject = eval ("(" + string_data + ")");
                  $.ajax( {
                     type: "POST",
                     url: "<?php echo 'simplecheckout/address_update.php';?>",
                     cache: false,
                     beforeSend: function(x) {
                       if(x && x.overrideMimeType) {
                         x.overrideMimeType("application/json;charset=UTF-8");
                       }
                       $('#address-update').find('#se-selected').parent().show();
                       $('#address-update').find('#se-selected').parent().addClass('loadingx');
                     },
                     data: JSONObject,
                     success: function(html) {
                         //set shipping address and $sendto
                         $('#address-update').find('#se-selected').parent().removeClass('loadingx');
                         $('#address-update').find('#se-selected').parent().show();
                         $('#address-update').find('#se-selected').find('span').html(html.u_address_label);

                         $('#delivery_reload').load(url + " #delivery_reload", {'action':'reload', 'osCsid':osCsid}, function(){
                           $("#shipping_sum").load(url + " #shipping_sum", {'action':'reload', 'osCsid':osCsid}, function() {
                             $("#shipping_sum").show().children().show(); //for some reason without this it does not work
                             //$("#shipping_sum button").button(); 
                           });
                           $('#payment_reload').load(url + " #payment_reload", {'action':'reload', 'osCsid':osCsid}, function() {
                             //init_thickbox();
                             protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
                           });
                           ot_refresh();
                         });
                         tosameadd_reset();
                         var be_update_address_book_id = $('#address-update').find('#be_update_address_book_id').val();
                         var billto_id = "<?php echo $billto; ?>";
                         if (be_update_address_book_id>0) billto_id = be_update_address_book_id;
                         if (isNaN(billto_id)) billto_id=0;

                         $('#be_select').load(url + " #be_select", {'action':'reload', 'osCsid':osCsid}, function() {
                             //reset radio buttons to what the user has selected
                             /*
                             if (billto_id ==0) {
                               $("#address-update").find('#new_billing_address_info').fadeIn('fast');
                             }
                             else {
                               $("#address-update").find('#new_billing_address_info').hide();
                             }
                             */
                             $("input:radio[name=billing_add_list]", "#address-update").removeAttr("checked");
                             $('#address-update').find("#be_select").find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
                             $("input:radio[name=billing_add_list][value="+billto_id+"]", "#address-update").attr("checked", "checked");
                             $("input:radio[name=billing_add_list][value="+billto_id+"]", "#address-update").parent().parent().addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');
                             //reset radio buttons to what the user has selected
                         });
      
                         var customer_id = "<?php echo $customer_id; ?>";
                         //var string_data= '{"action":"pop_address_1",' + '"add_id":"' + add_id + '","customer_id":"' + customer_id + '"}';
                         var string_data= '{"action":"address_popup",' + '"address_book_id":"' + billto_id + '","customer_id":"' + customer_id + '"}';
                         var JSONObject = eval ("(" + string_data + ")");
                         update_edit_form(billto_id, JSONObject, 1);
      
                         //update the latest shipping address id in the se-address formid
                         $("#se-address").find('#se_update_address_book_id').val(html.sendto);
                         //
                         $('#address-update').find('#be_update_sendto_id').val(0);
      
                         //update the display address
                         $('#pre-display-address').load(url + " #pre-display-address", {'action':'reload', 'osCsid':osCsid}, function() {});
                     },
                     error: function (xhr, status) {
                       /*document.location.href="<?php echo tep_href_link(FILENAME_CHECKOUT, '', 'SSL'); ?>";*/
                     }
                  });
                  $( this ).dialog( "close" );
                }
              },
              Cancel: function() {
                $( this ).dialog( "close" );
              }
            },
            close: function() {
              if (typeof allFields != 'undefined') {
                allFields.val( "" ).removeClass( "ui-state-error" );
              }
            }
          });

          //address update radio button   //se-address ->se_list
          $("#se_list")
          .on('change', 'input:radio[name=shipping_add_list]', function() {
          })
          .on('mouseover', '.moduleRow', function() {
            $(this).addClass('moduleRowOver');
          })
          .on('mouseout', '.moduleRow', function() {
            $(this).removeClass('moduleRowOver');
          })
          .on('click', '.moduleRow', function() {
            $("input:radio[name=shipping_add_list]", "#se_list").removeAttr("checked");
            $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
            $(this).find('input:radio[name=shipping_add_list]').attr("checked", "checked");
            $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

            //update edit for content
            var add_id = $(this).find('input:radio[name=shipping_add_list]').val();

            //My modification 20130328
            var current_billing_id = $("#address-update").find('#be_update_address_book_id').val();
            if (current_billing_id == add_id) {
              $('#se-address').find('#change_billingadd_warning').fadeIn('fast').prepend('<div class="readable-text inline-important"><span class="smallText">Any changes you make below will update your current billing address</span></div>');
            }
            else {
              $('#se-address').find('#change_billingadd_warning').hide().html("");
            }
            //My modification 20130328

            if (isNaN(add_id)) {
              add_id=0;
            }

            var customer_id = "<?php echo $customer_id; ?>";
            var string_data= '{"action":"address_popup",' + '"address_book_id":"' + add_id + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
            var JSONObject = eval ("(" + string_data + ")");
            update_edit_form_s(add_id, JSONObject, 0);
          });

          $("#address-update").on('click', '.update_addresses', function() {
              //udpate address buttons

              var firstname = $( "#address-update" ).find( "#be_firstname" ),
                  lastname = $( "#address-update" ).find( "#be_lastname" ),
                  house_name = $( "#address-update" ).find( "#be_house_name" ),
                  street_address = $( "#address-update" ).find( "#be_street_address" ),
                  postcode = $( "#address-update" ).find( "#be_postcode" ),
                  city = $( "#address-update" ).find( "#be_city" ),
                  country = $( "#address-update" ).find( "#be_country" ),
                  formid = $("#address-update").find('input[name=formid]');
                  tips = $( "#address-update" ).find( ".validateTips" );
              if ($('input[name=gender]:radio', '#address-update').length > 0) var gender = $('input[name=gender]:checked', '#address-update');
              if ($("#address-update").find('#be_company').length > 0)  var company = $( "#address-update" ).find( "#be_company" );
              if ($("#address-update").find('#be_suburb').length > 0) var suburb = $( "#address-update" ).find( "#be_suburb" );
              if ($("#address-update").find('#be_state_input').find('#state').length > 0) var state = $("#address-update").find('#be_state_input').find('#state');
              if ($("#address-update").find('#be_state_input').find('#zone_id').length > 0) var zone_id = $("#address-update").find('#be_state_input').find('#zone_id');
              var allFields = $( [] ).add( firstname ).add( lastname ).add( house_name ).add( street_address ).add( postcode ).add( suburb ).add( city ).add( state );

              var bValid = true;
              allFields.removeClass( "ui-state-error" );

              bValid = bValid && checkLength( firstname, "<?php echo ENTRY_FIRST_NAME_ERROR; ?>", <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>, 0);
              bValid = bValid && checkLength( lastname, "<?php echo ENTRY_LAST_NAME_ERROR; ?>", <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>, 0 );
              bValid = bValid && checkLength( house_name, "<?php echo ENTRY_HOUSE_NAME; ?>", <?php echo ENTRY_HOUSE_NAME_MIN_LENGTH; ?>, <?php echo ENTRY_HOUSE_NAME_MAX_LENGTH;?> );
              bValid = bValid && checkLength( street_address, "<?php echo ENTRY_STREET_ADDRESS; ?>", <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>, <?php echo ENTRY_STREET_ADDRESS_MAX_LENGTH;?> );
              bValid = bValid && checkLength( postcode, "<?php echo ENTRY_POST_CODE; ?>", <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>, <?php echo ENTRY_POSTCODE_MAX_LENGTH;?> );
              bValid = bValid && check_postcode(postcode, "<?php echo ENTRY_POST_CODE_ERROR2; ?>");
              if (typeof(suburb) != 'undefined' && suburb != null)
                bValid = bValid && checkLength( suburb, "<?php echo ENTRY_SUBURB; ?>", 0, <?php echo ENTRY_SUBURB_MAX_LENGTH;?> );
              bValid = bValid && checkLength( city, "<?php echo ENTRY_CITY; ?>", <?php echo ENTRY_CITY_MIN_LENGTH; ?>, <?php echo ENTRY_CITY_MAX_LENGTH;?> );
              if (typeof(state) != 'undefined' && state != null)
                bValid = bValid && checkLength( state, "<?php echo ENTRY_STATE; ?>", 0, <?php echo ENTRY_STATE_MAX_LENGTH;?> );

              bValid = bValid && check_select(country, "", "<?php echo ENTRY_COUNTRY_ERROR; ?>");
              bValid = bValid && (typeof(parseInt(country.val())) === 'number' && country.val() % 1 == 0);

              if ( bValid ) {
                $(this).css({ borderStyle:"inset", cursor:"wait" });
                $(this).attr('disabled','disabled');

                //My modification ajax: save it to customer
                var customer_id = "<?php echo $customer_id; ?>";
                var update_address_book_id = $("#address-update").find('#be_update_address_book_id').val();
                var update_address_book_id_edit = $("#address-update").find('#be_update_old_id').val();
                if (update_address_book_id >0 && update_address_book_id_edit <1) {
                  //update existing address before set it for billing address
                  var address_action = "set_be";
                }
                else if (update_address_book_id >0 && update_address_book_id_edit >0) {
                  //update existing address before set it for billing address
                  var address_action = "set_save_be";
                }
                else if (update_address_book_id <1 && update_address_book_id_edit >0) {
                  var address_action = "set_add_be";
                }
    
                var sendto_id = $("#se-address").find('#se_update_address_book_id').val();
                var tosameadd = $('#address-update').find('#tosameadd_select').find("input:radio[name=tosameadd][checked]").val();
                var tosamebilling = '';
                if (tosameadd == "yes") tosamebilling = '"tosamebilling":"yes",';
                else tosamebilling = '"tosamebilling":"no",';
    
                var string_data= '{"action":"' + address_action + '",' + 
                                  '"address_book_id":"' + update_address_book_id + '",' +
                                  tosamebilling +
                                  '"customer_id":"' + customer_id + '",' +
                                  '"firstname":"' + firstname.val() + '",' +
                                  '"lastname":"' + lastname.val() + '",' +
                                  '"house_name":"' + house_name.val() + '",' +
                                  '"street_address":"' + street_address.val() + '",' +
                                  '"postcode":"' + postcode.val() + '",' +
                                  '"city":"' + city.val() + '",' +
                                  '"country":"' + country.val() + '",' +
                                  ((typeof(gender) == 'undefined' || gender == null) ? '' : '"gender":"' + gender.val() + '",') +
                                  ((typeof(company) == 'undefined' || company == null) ? '' : '"company":"' + company.val() + '",') +
                                  ((typeof(suburb) == 'undefined' || suburb == null) ? '' : '"suburb":"' + suburb.val() + '",') +
                                  ((typeof(state) == 'undefined' || state == null) ? '' : '"state":"' + state.val() + '",') +
                                  ((typeof(zone_id) == 'undefined' || zone_id == null) ? '' : '"zone_id":"' + zone_id.val() + '",') +
                                  '"formid":"' + formid.val() + '", "osCsid":"'+osCsid+'"}';
    
                var JSONObject = eval ("(" + string_data + ")");

                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   beforeSend: function(x) {
                     if(x && x.overrideMimeType) {
                       x.overrideMimeType("application/json;charset=UTF-8");
                     }
                     //$("#address-update").addClass('loadingx');
                   },
                   data: JSONObject,
                   success: function(html) {

                       //update the latest billing address id
                       $('#address-update').find('#be_update_address_book_id').val(html.billto);
                       //update the latest shipping address id
                       if (tosameadd == "yes") {
                         if (html.billto<1) html.billto="<?php echo $billto;?>";
                         $('#address-update').find('#be_update_sendto_id').val(html.billto);
                         $("#se-address").find('#se_update_address_book_id').val(html.billto);
                       }
                       else {
                         if (sendto_id <1) sendto_id=$('#address-update').find('#be_update_sendto_id').val();
                         if (sendto_id <1) sendto_id="<?php echo $sendto; ?>";
                         $('#address-update').find('#be_update_sendto_id').val(sendto_id);
                         $("#se-address").find('#se_update_address_book_id').val(sendto_id);
                       }

                       //display the #addresses-display and hide the #address-update
                       $('#addresses-display').load(url + " #addresses-display", {'action':'reload', 'osCsid':osCsid}, function() {
                         $('#be_select').load(url + " #be_select", {'action':'reload', 'osCsid':osCsid}, function() { });
                         bsp_sum_refresh(2);
                         //$("#address-update").removeClass('loadingx');
                         if ($("div#address-update").find(".update_addresses").attr('disabled')) {
                           $("div#address-update").find(".update_addresses").css({ borderStyle:"", cursor:"pointer" }).removeAttr('disabled');
                         }
                       });

                       $('#delivery_reload').load(url + " #delivery_reload", {'action':'reload', 'osCsid':osCsid}, function(){ $("#shipping_sum").load(url + " #shipping_sum", {'action':'reload', 'osCsid':osCsid}, function() { $("#shipping_sum button").button(); });});
                       $('#payment_reload').load(url + " #payment_reload", {'action':'reload', 'osCsid':osCsid}, function() {
                         //init_thickbox();
                         ot_refresh();
                         protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
                       });

                   },
                   error: function (xhr, status) {
                   }
                });

              }
              else {
                $(this).css({ borderStyle:"none", cursor:"pointer" });
                $(this).removeAttr("disabled");
              }

          })
          .on('change', '#be_country', function() {
              //update state_input/zone_id
              if ($("#address-update").find('#be_state_input').length > 0) {
                var update_address_book_id = $("#address-update").find('#be_update_address_book_id').val();
                var country_id = $("#address-update").find('#be_country').val();
                var customer_id = "<?php echo $customer_id; ?>";
                var string_data= '{"action":"update_state_input",' + '"update_address_book_id":"' + update_address_book_id + '","country":"' + country_id + '", "customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
                var JSONObject = eval ("(" + string_data + ")");

                switchmode(); //postcodeanywhere
                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   beforeSend: function(x) {
                    if(x && x.overrideMimeType) {
                      x.overrideMimeType("application/json;charset=UTF-8");
                    }
                   },
                   dataType: "json",
                   data: JSONObject,
                   success: function(html) {
                     var str=html;
                     $("#address-update").find("#be_state_input").html(str.state_entry);
                   }
                });
              }
          });


          $("#shipping_sum").on('click', ".edit_shipping", function() {
            bsp_sum_refresh(2);
          });

          //shipping_sum
          $("#shipping_pane").on('click', ".update_shipping", function() {

            if ($("#delivery_reload").find('input:radio[name=shipping]').length <1) {
            	$("#delivery_reload").append('<div class="messageStackWarning">Please input address first</div>');
            	setTimeout(function() {$('#delivery_reload div.messageStackWarning').remove();},1500);
            	return false;
            }

            //update shipping for the order
            $(this).css({ borderStyle:"inset", cursor:"wait" });
            $(this).attr('disabled','disabled');

            if ($("#delivery_reload").find("#safeplace").find("#delivery-comment").length>0) 
              var safeplace = $("#delivery_reload").find("#safeplace").find("#delivery-comment").find('input[type="text"][name="comments"]').val();
            else
              var safeplace = "";

            $('#delivery_reload').load(url + " #delivery_reload", {'comments': safeplace, 'shipping':$('#delivery_reload').find('input:radio[name=shipping][checked]').val(),'action':'shippingSet', 'osCsid':osCsid}, function() {
              ot_refresh();
              $("#shipping_sum").load(url + " #shipping_sum", {'action':'reload', 'osCsid':osCsid}, function() {
                $("#shipping_sum button").button();
                bsp_sum_refresh(3);
                if ($("#shipping_pane").find(".update_shipping").attr('disabled')) {
                  $("#shipping_pane").find(".update_shipping").css({ borderStyle:"", cursor:"pointer" }).removeAttr('disabled');
                }
              });
            });
          });

			    //voucher_coupon
			    $("form[name='checkout_payment_gift']").submit(function() { return false;});
			    $("#coupon-voucher")
			    .on('click', '.ot_cg_button', function() {
			    	if ($('input[type="text"][name="gv_redeem_code"]', '#coupon-voucher')) {
			    		var ot_c = $('input[type="text"][name="gv_redeem_code"]', '#coupon-voucher').val();
			    		if (!$('input[type="checkbox"][name="cot_gv"]', "#credit_cover_div").is(':checked')) {
			    		    if (ot_c) {
			              $.ajax( {
			                type: "POST",
			                url: "<?php echo 'simplecheckout/ot_gv_coupon.php';?>",
			                cache: false,
			                data: "action=redeem&gv_redeem_code="+ot_c+"&osCsid=" + osCsid,

			                beforeSend: function(x) {
			                  if(x && x.overrideMimeType) {
			                    x.overrideMimeType("application/html;charset=UTF-8");
			                  }
			                  $("#coupon-voucher").addClass('loadingx');
			                },
			                success: function(html) {
			                 	 if (html) {
			                 	 	 ot_refresh();
			                 	 	 $("#credit_cover_div").load(url + " #credit_cover_div", {'action':'reload', 'osCsid':osCsid});
			                 	   $("#coupon-voucher").find('#coupon-voucher-msg').html(html).fadeIn('fast').addClass("ui-state-error");
			                 	 }
			                 	 $("#coupon-voucher").removeClass('loadingx');
			                },
			                error: function (xhr, status) {}
			              });
			            }
			        }
			        else { $("#coupon-voucher").find('#coupon-voucher-msg').html("You need to un-select the Gift Voucher Credit below to enable the coupon").fadeIn('fast').delay(2000).fadeOut("slow"); }
			    	}
			    });

          //ot_gv
          $("#credit_cover_div").on('click', 'input[type="checkbox"][name="cot_gv"]', function() {
            //update order_total
                  if ($(this).is(':checked')) { var usergv = "&cot_gv=on"; } else { var usergv = "&cot_gv=0";}
                  $.ajax( {
                      type: "POST",
                      url: "<?php echo 'simplecheckout/ot_gv_coupon.php';?>",
                      cache: false,
                      data: "action=redeem_gv"+usergv+"&osCsid=" + osCsid,
                      beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/html;charset=UTF-8");
                        }
                      },
                      success: function(html) {
                        ot_refresh();
                        var mystring = html;
                        var matches = mystring.match(/credit_cover/);
      
                        //if (html == "credit_covers_ok") {
                        if (matches != null) {
                          if (usergv == "&cot_gv=on") {
                            $('#payment_reload').find('input:radio[name=payment]').prop('checked', false).attr('disabled',true);
                            $('#payment_reload').off('click', '.moduleRow');
                            $('#payment_reload').find('#payment-options-form').attr("action", "<?php echo tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');?>");
                            $("#p_button_reload").html('<input type="submit" class="form-bt" value="<?php echo IMAGE_BUTTON_CONFIRM_ORDER;?>" id="submitpayment" />');

                            $('#credit_cover_t').val(1);
                          }
                          else {
                            $('#credit_cover_t').val(0);
                          }
                        }
                        else {
                            if (usergv == "&cot_gv=0") {
                              if ($('#payment_reload').find('input:radio[name=payment]').attr('disabled')) {
                                $('#payment_reload').find('input:radio[name=payment]').removeAttr('disabled');
      
                                $('#payment_reload')
                                .on('click', '.moduleRow', function() {
                                  $("input:radio[name=payment]", "#payment_reload").removeAttr("checked");
                                  $(this).parent().find('#defaultSelected').removeClass('moduleRowSelected').addClass('moduleRow').removeAttr('id');
                                  $(this).find('input:radio[name=payment]').attr("checked", "checked");
                                  $(this).addClass('moduleRowSelected').removeClass('moduleRow').removeClass('moduleRowOver').attr('id', 'defaultSelected');

                                  $('#payment_reload').load(url + " #payment_reload", {'payment':$(this).find('input:radio[name=payment]').val(),'action':'paymentSet', 'osCsid':osCsid}, function() {
                                    //init_thickbox();
                                    protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
                                  });
                                });
      
                              }
                              else {
                                $('#payment_reload').load(url + " #payment_reload", {'action':'reload', 'osCsid':osCsid}, function() {
                                  //init_thickbox();
                                  protx_update_fields($('input[type=select][name=protx_direct_cc_type]').val());
                                });
                              }
                            }
                            $('#credit_cover_t').val(0);
                        }
                      },
                      error: function (xhr, status) {}
                  });
      
          });























          //payment 
          $("#payment_sum").on('click', function() {
            bsp_sum_refresh(3);
          });


          //edit shopping cart
          $("div#products_pane").on('click', ".bt-checkout-edit", function() {
            $(this).css({ borderStyle:"inset", cursor:"wait" });
            $(this).attr('disabled','disabled');
            document.location.href="<?php echo tep_href_link(FILENAME_SHOPPING_CART); ?>";
          });

          $("button").removeClass("button").button();


<?php
  //201604
  if ($input_address_first) {
?>
              bsp_sum_refresh(1);
              //pre fill the address edit form
              //var be_update_address_book_id = $('#address-update').find("input:radio[name=billing_add_list][checked]").val();
              var be_update_address_book_id = $('#address-update').find('#be_update_address_book_id').val();
              var billto_id = "<?php echo $billto; ?>";
              if (be_update_address_book_id>0) billto_id = be_update_address_book_id;
              if (isNaN(billto_id)) billto_id=0;
              var customer_id = "<?php echo $customer_id; ?>";
              //var string_data= '{"action":"pop_address_1",' + '"add_id":"' + add_id + '","customer_id":"' + customer_id + '"}';
              var string_data= '{"action":"address_popup",' + '"address_book_id":"' + billto_id + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
              var JSONObject = eval ("(" + string_data + ")");
              //switchmode(); //postcodeanywhere

              update_edit_form(billto_id, JSONObject, 1);
              //$("#address-update").css("display","");
              //reset tosameadd radio buttons
              //tosameadd_reset();
              //var se_update_address_book_id = $('#se-address').find('#se_list').find("input:radio[name=shipping_add_list][checked]").val();
              var se_update_address_book_id = $('#se-address').find('#se_update_address_book_id').val();
              if (typeof(se_update_address_book_id) == 'undefined' || se_update_address_book_id == null || se_update_address_book_id <1) se_update_address_book_id = $('#address-update').find('#be_update_sendto_id').val();
              var sendto_id = "<?php echo $sendto; ?>";
              if (se_update_address_book_id>0) {sendto_id = se_update_address_book_id;}
              if (isNaN(sendto_id)) sendto_id =0;
              if (billto_id == sendto_id) {
                $('#address-update').find('#se-selected').parent().hide();
              }
              else {
                //alert(u_address_label);
                $('#address-update').find('#se-selected').parent().show();
                $.ajax( {
                   type: "POST",
                   url: "<?php echo 'simplecheckout/address_update.php';?>",
                   cache: false,
                   data: "action=se-selected_update&address_book_id="+sendto_id+"&customer_id="+customer_id+"&osCsid=" + osCsid,
                   beforeSend: function(x) {
                    if(x && x.overrideMimeType) {
                      x.overrideMimeType("application/html;charset=UTF-8");
                    }
                   },
                   success: function(html) {
                     $('#address-update').find('#se-selected').find('span').html(html);
                   },
                   error: function (xhr, status) {}
                });
                //$('#address-update').find('#se-selected').trigger('refresh');
              }
              tosameadd_reset();
<?php
  }
?>

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
          	<?php
          	echo '<h1>' . HEADING_TITLE_CONFIRMATION . '</h1>' . "\n";
            if ($messageStack->size('checkout') > 0) {
              echo '                  ' . $messageStack->output('checkout', 'center') . "\n";
            }
            ?>
          	<div class="grid-01">
          		<div class="grid-01-02 right">
          			<div class="table-wrapper" id="products_pane">
          				<h2 class="sub-title">Shopping Cart <a class="bt-checkout-edit"><?php echo TEXT_EDIT;?></a></h2>
          				<div class="clearme"></div><p></p>
		              <?php
		                      for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
		                        $products_c_query = tep_db_query("select p.products_id, pd.products_name, p.products_model, pi.image_filename, p.products_bundle, p.products_price, p.products_market_price, p.products_weight, p.products_tax_class_id, products_market_price, products_free_shipping, p.parent_id, p.has_children from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_IMAGES . " pi on p.products_id = pi.products_id and pi.product_page = '1', " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$order->products[$i]['id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");

		                        $products_att_a = array();
		                        if ($products_c = tep_db_fetch_array($products_c_query)) {
		                        	$display_image = $products_c['image_filename'];
		                        }

                            if ((int)$order->products[$i]['parent_id']>0) { //slave product
                              $parent_info = tep_db_fetch_array(tep_db_query("select pd.products_name, p.products_model, pi.image_filename from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_IMAGES . " pi on p.products_id = pi.products_id and pi.product_page = '1', " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . (int)$products_c['parent_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'"));
                              $display_name = $parent_info['products_name'];
                              $display_model = $parent_info['products_model'];
                              $display_image = $parent_info['image_filename'];
                            }
                            else {
                            	$display_name = $order->products[$i]['name'];
                            	$display_model = $order->products[$i]['model'];
                            }
                            if (!file_exists(DIR_FS_CATALOG . DIR_WS_IMAGES . $display_image)) {
                            	$display_image = "demo/no_picture.jpg";
                            }

		                        if ($i ==$n-1) {
                              echo '<div class="single-cart-item last">' . "\n";
                            }
                            else {
                              echo '<div class="single-cart-item">' . "\n";
                            }
                            echo '  <div class="left">' . "\n";
                            echo '    <a>' . tep_image(DIR_WS_IMAGES . $display_image) . '</a>' . "\n";
                            echo '  </div>' . "\n";
                            echo '  <div class="right">' . "\n";
                            echo '    <h3><a>' . $display_name . '</a></h3>' . "\n";
                            if (tep_not_null($display_model)) {
                              echo '    <h5>' . $display_model . '</h5>' . "\n";
                            }
		                        if ( (isset($order->products[$i]['attributes'])) && (sizeof($order->products[$i]['attributes']) > 0) ) {
		                          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
		                            echo '    <h5>' . $order->products[$i]['attributes'][$j]['option'] . ' <nobr>' . $order->products[$i]['attributes'][$j]['value'] . '</nobr></h5>' . "\n";
		                            $products_att_a[$order->products[$i]['attributes'][$j]['option_id']] = $order->products[$i]['attributes'][$j]['value_id'];
		                          }
		                        }
		                        elseif ((int)$order->products[$i]['parent_id']>0) { //slave product
		                          if (isset($order->products[$i]['products_variants']) && sizeof($order->products[$i]['products_variants']) > 0) {
                        	      foreach ($order->products[$i]['products_variants'] as $v) {
                        		     echo '    <h5>' . $v['group'] . ' <nobr>' . $v['value'] . '</nobr></h5>' . "\n";
                        	      }
                              }
		                        }
                            elseif (is_coloursample_pid($order->products[$i]['id'])) { //colour sample
                              $ps_pv_a = pv_sample_variants($order->products[$i]['id']);
                              if (sizeof($ps_pv_a)) {
                        	      foreach ($ps_pv_a as $v) {
                        		     echo '    <h5>' . $v['group'] . ' <nobr>' . $v['value'] . '</nobr></h5>' . "\n";
                        	      }
                              }
                            }
                            echo '    <h5>Qty: ' . $order->products[$i]['qty'] . '</h5>' . "\n";
		                        if ($order->products[$i]['price'] > $order->products[$i]['final_price']) {
			                        //special offer
			                        if (tep_not_null($products_c['products_market_price']) && $products_c['products_market_price'] > $order->products[$i]['price']) {
			                        	echo '    <h5><strong>' . "<s>" . $currencies->display_price_nodiscount($products_c['products_market_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . "</s></strong></h5><h5><strong><s>" .$currencies->display_price_nodiscount($order->products[$i]['price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . "</s></strong></h5><h5><strong>" . $currencies->display_price_nodiscount($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</strong></h5>' . "\n";
			                        }
			                        else {
			                        	echo '    <h5><strong><s>' . $currencies->display_price_nodiscount($order->products[$i]['price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</s></strong></h5><h5><strong>' . $currencies->display_price_nodiscount($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</strong></h5>' . "\n";
			                        }
		                        }
		                        else {
			                        	echo '    <h5><strong>' . $currencies->display_price_nodiscount($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . '</strong></h5>' . "\n";
		                        }
                            echo '    <br />' . "\n";
		                        //My mod 20130529 backordered
		                        if (STOCK_CHECK == 'true') {
		                        	if (is_coloursample_pid($order->products[$i]['id'])) {}
		                        	elseif (!is_pickup_daily(0,$order->products[$i]['id'])) {
		                            if (sizeof($products_att_a)) {
		                        	    $stock_left = tep_get_products_stock(tep_get_prid($order->products[$i]['id']), $products_att_a) - $order->products[$i]['qty'];
		                            }
		                            else {
		                          	  $stock_left = tep_get_products_stock(tep_get_prid($order->products[$i]['id'])) - $order->products[$i]['qty'];
		                            }
		                            if ($stock_left <0) {
		                          	  echo '    <p><a href="info/info_backordered.php" class="lightbox fancybox.ajax masterTooltip" title="' . BACKORDERED_ITEMS . '">' . BACKORDERED_ITEMS . '</a></p>' . "\n";
		                            }
		                          }
		                        }
		                        //My mod 20130529 backordered

                            echo '  </div>' . "\n";
                            echo '  <div class="clearme"></div>' . "\n";
                            echo '</div><!--/.single-cart-item-->' . "\n";
		                      }
		              ?>
          			</div><!--/.table-wrapper-->

					      <div class="table-wrapper readable-text checkout-wrapper" id="order-total-parent">
                  <div class="single-cart-item checkout-total last" id="order-total">
                            <p>
                            <?php
                              /* generated by js:ot_refresh() in document.ready
                              $order_total_modules->process();
                              if (MODULE_ORDER_TOTAL_INSTALLED) {
                                echo $order_total_modules->output_confirmpage(); //echo $order_total_modules->output();
                              }
                              */
                            ?>
                            </p>
                  </div><!--/.single-cart-item checkout-total-->
                </div><!--/.table-wrapper readable-text-->

            	</div><!--/.grid-01-02 right-->

            	<div class="grid-01-01">
                <div id="addresses-display">
                      <div class="table-wrapper checkout-wrapper">
                          <h2 class="sub-title">1. Address <a class="bt-checkout-edit edit_addresses" billto_id="<?php echo $billto;?>">Edit</a></h2><br />
                          <div id="pre-display-address">
                            <div class="addressLeft">
                              <?php echo '<p><strong>' . HEADING_BILLING_ADDRESS . '</strong><br />' . tep_address_label($customer_id, $billto, true, ' ', '<br />') . '</p>' . "\n"; ?>
                            </div>
                            <div class="addressRight">
                              <?php echo ($billto ==$sendto) ? '<p><strong>' . HEADING_DELIVERY_ADDRESS . '</strong><br />' . HEADING_DELIVERY_ADDRESS_SAME . '</p>' . "\n" : '<p><strong>' . HEADING_DELIVERY_ADDRESS . '</strong><br />' .  tep_address_label($customer_id, $sendto, true, ' ', '<br />') . '</p>' . "\n"; ?>
                            </div>
                            <div class="clearme"></div>
                          </div>
                      </div><!--/.table-wrapper-->
                </div><!--/.#addresses-display-->

                <div class="table-wrapper checkout-wrapper open" id="address-update">
                        <h2 class="sub-title">1. Addresses <a class="bt-checkout-requirement"><?php echo FORM_REQUIRED_INFORMATION;?></a></h2><br />

                          <?php echo tep_draw_form("address_update", '', 'post', 'id="address_update"', true) . "\n";?>
                              <p><strong><?php echo HEADING_BILLING_ADDRESS;?></strong><br /><em><span class="smallText"><?php echo HEADING_ADDRESS_UPDATE_PROMPT;?></span></em></p>
                              <div id="be_select">
                              <?php
                                  $addresses_query = tep_db_query("select address_book_id, entry_firstname as firstname, entry_lastname as lastname, entry_company as company, entry_house_name as house_name, entry_street_address as street_address, entry_suburb as suburb, entry_city as city, entry_postcode as postcode, entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "'");
                                  while ($addresses = tep_db_fetch_array($addresses_query)) {
                                      $format_id = tep_get_address_format_id($addresses['country_id']);
                                      if ($addresses['address_book_id'] == $billto) {
                                        echo '<div id="defaultSelected" class="moduleRowSelected" >' . "\n";
                                      } else {
                                        echo '<div class="moduleRow" >' . "\n";
                                      }
                                      echo '  <div class="radioLeft">' . tep_draw_radio_field('billing_add_list', $addresses['address_book_id'], ($addresses['address_book_id'] == $billto)) . '</div>' . "\n";
                                      echo '  <div class="radioRight">' . tep_address_format($format_id, $addresses, true, ' ', ', ') . '</div>' . "\n";
                                      echo '  <div class="clearme"></div>' . "\n";
                                      echo '</div>' . "\n";
                                  }
                                  if (tep_db_num_rows($addresses_query) <MAX_ADDRESS_BOOK_ENTRIES) {
                                      echo '<div class="moduleRow" >' . "\n";
                                      echo '  <div class="radioLeft">' . tep_draw_radio_field('billing_add_list', 0, ($addresses['address_book_id'] == $billto)) . '</div>' . "\n";
                                      echo '  <div class="radioRight">Add new address (input new address below)</div>' . "\n";
                                      echo '  <div class="clearme"></div>' . "\n";
                                      echo '</div>' . "\n";
                                  }
                              ?>
                              </div>
                              <!--<div id="new_billing_address_info"><p class="smallText"><?php echo "Please input new address below";?></p></div>-->
                              <div class="validateTips"></div>

                              <div id="billing_edit">
                              	  <div id="indicator" style="visibility:hidden;"><?php echo tep_image(DIR_WS_IMAGES . 'icons/indicator.gif'); ?></div>
                                  <?php
                                  if (ACCOUNT_GENDER == 'true') {
                                    $male = $female = false;
                                    if (isset($gender)) {
                                      $male = ($gender == 'm') ? true : false;
                                      $female = !$male;
                                    } elseif (isset($entry['entry_gender'])) {
                                      $male = ($entry['entry_gender'] == 'm') ? true : false;
                                      $female = !$male;
                                    }
                                  ?>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><p><?php echo ENTRY_GENDER . '&nbsp;' . (tep_not_null(ENTRY_GENDER_TEXT) ? '<span>' . ENTRY_GENDER_TEXT . '</span>': ''); ?></p></div>
                                    <div class="sfRight"><?php echo tep_draw_radio_field('gender', 'm', $male) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f', $female) . '&nbsp;&nbsp;' . FEMALE; ?></div>
                                  </div>
                                  <?php
                                  }
                                  ?>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_FIRST_NAME . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span>' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('firstname', '', 'id="be_firstname" class="register-input"'); ?></div>
                                  </div>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_LAST_NAME . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span>' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('lastname', '', 'id="be_lastname" class="register-input"'); ?></div>
                                  </div>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_COUNTRY . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span>' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_get_country_list('country', tep_not_null($country) ? $country : $entry['entry_country_id'], 'id="be_country" class="register-input"'); ?></div>
                                  </div>

                                  <!-- //My modification - postcodeanywhere -->
                                  <div class="sfBlock">
                                    <div class="sfLeft"><div id="btnFinddiv1"><?php echo ENTRY_POST_CODE . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span>' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></div></div>
                                    <div class="sfRight">
                                      <div class="postcode_form_element">
                                        <div class="left"><?php echo tep_draw_input_field('postcode', '', 'id="be_postcode" class="register-input-half"'); ?></div>
                                        <div class="right"><div id="btnFinddiv2"><?php echo tep_crafty_button(); ?></div></div>
                                      </div>
                                      <div class="clearme"></div>
                                      <div><div id="crafty_postcode_result_display" class="address_form"></div></div>
                                    </div>
                                  </div>
                                  <!-- //My modification - postcodeanywhere -->

                                  <?php
                                  if (ACCOUNT_COMPANY == 'true') {
                                  ?>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_COMPANY . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TEXT) ? '<span>' . ENTRY_COMPANY_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('company', '', 'id="be_company" class="register-input"'); ?></div>
                                  </div>
                                  <?php
                                  }
                                  ?>

                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_HOUSE_NAME . '&nbsp;' . (tep_not_null(ENTRY_HOUSE_NAME_TEXT) ? '<span>' . ENTRY_HOUSE_NAME_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('house_name', '', 'id="be_house_name" class="register-input"'); ?></div>
                                  </div>

                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_STREET_ADDRESS . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span>' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('street_address', '', 'id="be_street_address" class="register-input"'); ?></div>
                                  </div>

                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_SUBURB . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span>' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('suburb', '', 'id="be_suburb" class="register-input"'); ?></div>
                                  </div>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_CITY . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span>' . ENTRY_CITY_TEXT . '</span>': ''); ?></div>
                                    <div class="sfRight"><?php echo tep_draw_input_field('city', '', 'id="be_city" class="register-input"'); ?></div>
                                  </div>

                                  <?php
                                  if (ACCOUNT_STATE == 'true') {
                                  ?>
                                  <div class="sfBlock">
                                    <div class="sfLeft"><?php echo ENTRY_STATE; ?></div>
                                    <div class="sfRight"><div id="be_state_input"></div></div>
                                  </div>
                                  <?php
                                  }
                                  ?>
                                  <div><?php echo tep_draw_hidden_field('update_address_book_id', '', 'id="be_update_address_book_id"'); ?></div>
                                  <div><?php echo tep_draw_hidden_field('update_old_id', '', 'id="be_update_old_id"'); ?></div>
                                  <div><?php echo tep_draw_hidden_field('update_sendto_id', '', 'id="be_update_sendto_id"'); ?></div>
                              </div><!--#billing_edit-->
                              
                              
                              <p>
                                  <div class="sLeft"><strong><?php echo HEADING_SHIPPING_ADDRESS;?></strong></div>
                                  <div class="sRight">
                                    <div id="tosameadd_select">
                                        <div id="defaultSelected" class="moduleRowSelected">
                                          <?php echo tep_draw_radio_field('tosameadd', "yes", ($billto == $sendto));?> Ship to my billing address
                                        </div>
                                        <div class="clearme"></div>
                                        <div class="moduleRow">
                                          <?php echo tep_draw_radio_field('tosameadd', "no", ($billto != $sendto));?> Ship to a different address
                                        </div>
                                    </div>
                                    <div class="clearme"></div>
                                    <div class="hideme">
                                      <p class="sShiping" id="se-selected">
                                      	<a class="bt-checkout-requirement" id="se-selected-edit"><i class="fa fa-exchange"></i> <u>change</u></a>
                                      	<span></span>
                                      </p>
                                    </div>
                                    <div class="clearme"></div>
                                  </div>
                                  <div class="clearme"></div>
                              </p>
  
                              
                          <?php echo '</form>' . "\n";?>
                          <div class="clearme"></div>
                          <span class="form-bt update_addresses">Continue</span>

                </div><!--/.table-wrapper open #address-update-->

                <!--start se_address dialog-->
                <div id="se-address" title="<?php echo HEADING_DELIVERY_ADDRESS;?>" class="jdialog">
                      <br /><div id="se_list" style=""></div>
                      <div style="border-style:dotted;border-color:#ccc;border-width:1px 0px 0px 0px;margin:10px 0px 10px 0px;"></div>
                      <div class="hideme" id="change_billingadd_warning"></div>
                      <!--<div style="clear:both;text-align:left;padding:0px 0px 10px 0px;display:none;" id="new_shippinging_address_info"><span class="smallText">Please input a new address below</span></div>-->
                      <p class="validateTips"><?php echo FORM_REQUIRED_INFORMATION;?></p>

                        <?php echo tep_draw_form("se_address", '', 'post', 'id="se_address"', true) . "\n";?>
                        <fieldset class="p_popup">
                          <?php
                            if (ACCOUNT_GENDER == 'true') {
                              $male = $female = false;
                              if (isset($gender)) {
                                $male = ($gender == 'm') ? true : false;
                                $female = !$male;
                              } elseif (isset($entry['entry_gender'])) {
                                $male = ($entry['entry_gender'] == 'm') ? true : false;
                                $female = !$male;
                              }
                          ?>

                          <div class="pblock">
                            <div class="plabel"><label for="gender" class="p_popup"><?php echo ENTRY_GENDER . '&nbsp;' . (tep_not_null(ENTRY_GENDER_TEXT) ? '<span>' . ENTRY_GENDER_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_radio_field('gender', 'm', $male) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('se_gender', 'f', $female) . '&nbsp;&nbsp;' . FEMALE; ?></div>
                          </div>
                          <?php
                            }
                          ?>
                          <div class="pblock">
                            <div class="plabel"><label for="firstname" class="p_popup"><?php echo ENTRY_FIRST_NAME . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span>' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('firstname', '', 'id="se_firstname" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>

                          <div class="pblock">
                            <div class="plabel"><label for="lastname" class="p_popup"><?php echo ENTRY_LAST_NAME . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span>' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('lastname', '','id="se_lastname" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>

                          <div class="pblock">
                            <div class="plabel"><label for="country" class="p_popup"><?php echo ENTRY_COUNTRY . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span>' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_get_country_list('country', tep_not_null($country) ? $country : '', 'id="se_country" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>

                          <!-- //My modification - postcodeanywhere -->
                          <div class="pblock">
                            <div class="plabel"><label for="postcode" class="p_popup"><div id="btnFinddiv1"><?php echo ENTRY_POST_CODE . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span>' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></div></label></div>
                            <div class="pinput">
                              <div style="position:relative;height:30px;">
                                <div style="position:absolute; top:0px; left:0px; width:110px;"><?php echo tep_draw_input_field('postcode','','id="se_postcode" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                <div style="position:absolute; top:0px; left:110px; width:120px;"><div id="btnFinddiv22"><?php echo tep_crafty_button_add(2); ?></div></div>
                              </div>
                            </div>
                          </div>
                          <div class="pblock">
                            <div class="plabel"><label for="" class="p_popup"></label></div>
                            <div class="pinput"><div id="crafty_postcode_result_display2"></div></div>
                          </div>
                          <!-- //My modification - postcodeanywhere -->
    
                          <?php
                            if (ACCOUNT_COMPANY == 'true') {
                          ?>
                          <div class="pblock">
                            <div class="plabel"><label for="company" class="p_popup"><?php echo ENTRY_COMPANY . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TEXT) ? '<span>' . ENTRY_COMPANY_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('company', '', 'id="se_company" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>
                          <?php
                            }
                          ?>
                          <div class="pblock">
                            <div class="plabel"><label for="house_name" class="p_popup"><?php echo ENTRY_HOUSE_NAME . '&nbsp;' . (tep_not_null(ENTRY_HOUSE_NAME_TEXT) ? '<span>' . ENTRY_HOUSE_NAME_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('house_name', '', 'id="se_house_name" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>

                          <div class="pblock">
                            <div class="plabel"><label for="street_address" class="p_popup"><?php echo ENTRY_STREET_ADDRESS . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span>' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('street_address', '', 'id="se_street_address" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>

                          <?php
                            if (ACCOUNT_SUBURB == 'true') {
                          ?>
                          <div class="pblock">
                            <div class="plabel"><label for="suburb" class="p_popup"><?php echo ENTRY_SUBURB . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span>' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('suburb', '', 'id="se_suburb" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>
                          <?php
                            }
                          ?>

                          <div class="pblock">
                            <div class="plabel"><label for="city" class="p_popup"><?php echo ENTRY_CITY . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span>' . ENTRY_CITY_TEXT . '</span>': ''); ?></label></div>
                            <div class="pinput"><?php echo tep_draw_input_field('city', '', 'id="se_city" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                          </div>
    
                          <?php
                            if (ACCOUNT_STATE == 'true') {
                          ?>
                              <div class="pblock">
                                <div class="plabel"><label for="state_input" class="p_popup"><?php echo ENTRY_STATE; ?></label></div>
                                <div class="pinput"><div id="se_state_input"></div></div>
                              </div>
                          <?php
                            }
                          ?>
                          <div><?php echo tep_draw_hidden_field('se_update_address_book_id', '', 'id="se_update_address_book_id"'); ?></div>
                          <div><?php echo tep_draw_hidden_field('se_update_old_id', '', 'id="se_update_old_id"'); ?></div>
                          <p><?php echo INFO_ADDRESS_REMOVAL;?></p>
                        </fieldset>
                        <?php echo '</form>' . "\n";?>
                </div>
                <!--end se_address dialog-->

                <div class="table-wrapper checkout-wrapper open" id="shipping_pane">
                    	  <h2 class="sub-title">2. Delivery <a href="info/info_shipping.php" class="bt-checkout-requirement" data-featherlight-variant="flwidth" data-featherlight="ajax"><u>shipping rates</u></a></h2><br/>

                        <div id="delivery_reload">
                            <?php
                              if ( defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') ) {
                                $pass = false;

                                switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                                  case 'national':
                                    if ($order->delivery['country_id'] == STORE_COUNTRY) {
                                      $pass = true;
                                    }
                                    break;
                                  case 'international':
                                    if ($order->delivery['country_id'] != STORE_COUNTRY) {
                                      $pass = true;
                                    }
                                    break;
                                  case 'both':
                                    $pass = true;
                                    break;
                                }

                                $free_shipping = false;
                                if ( ($pass == true) && ($order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) ) {
                                  $free_shipping = true;

                                  include(DIR_WS_LANGUAGES . $language . '/modules/order_total/ot_shipping.php');
                                }
                              } else {
                                $free_shipping = false;
                              }
                            // get all available shipping quotes
                              $quotes = $shipping_modules->quote();

                            // if no shipping method has been selected, automatically select the cheapest method.
                            // if the modules status was changed when none were available, to save on implementing
                            // a javascript force-selection method, also automatically select the cheapest shipping
                            // method if more than one module is now enabled
                              if (tep_count_shipping_modules() > 0 && $HTTP_POST_VARS['action'] != 'shippingSet') {
                                if ($cash_carry_order) {}
                                elseif ( !tep_session_is_registered('shipping') || ( tep_session_is_registered('shipping') && ($shipping == false) && (tep_count_shipping_modules() > 1) ) ) $shipping = $shipping_modules->cheapest();
                                elseif (tep_session_is_registered('shipping')) {
                                    //this is necessary because the $shipping variable is not reset when the shopping cart is update where some shipping method may be switch off so e.g. if the customer update the shopping cart and press checkout button from cart then the $shipping might not be the same as $shipping_modules->cheapest() and end up not option is select in shipping method radio buttons and the $order_total_modules->output_confirmpage() is getting the result from $shipping

                                    $is_shipping_valid = false; //check if the shipping options valid
                                    $reset_shipping = false;
                                    list($c_shippingid1, $c_shippingid2) = explode('_', $shipping['id']);
                                    for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
                                      if (isset($quotes[$i]['error']) && $quotes[$i]['id']==$c_shippingid1) {
                                        $reset_shipping = true;
                                      }
                                      //20140508
                                      elseif ($quotes[$i]['id']==$c_shippingid1) {
                                        if (sizeof($quotes[$i]['methods'])==1) {
                                        	$is_shipping_valid = true;
                                          if ($quotes[$i]['methods'][0]['cost'] != $shipping['cost']) {
                                            //syn with the quote price again - because a delivery method might become a different prices in that case the order total on the right pane shows a different prices to the shipping options in delivery-options-form
                                            $shipping['cost'] = $quotes[$i]['methods'][0]['cost'];
                                          }
                                        }
                                      }
                                      //20140508
                                    }
                                    if (!is_array($shipping)) $reset_shipping = true;
                                    elseif (!$is_shipping_valid) $reset_shipping = true;
                                    if ($reset_shipping) $shipping = $shipping_modules->cheapest();
                                }
                              }

                            //tep_session_unregister('shipping');
                            //$shipping = false;

                              //20140508 - shipping register
                              /*if (tep_session_is_registered('shipping')) {
                                $latest_cheapest = $shipping_modules->cheapest();
                                if ($shipping['id'] != $latest_cheapest['id']) $shipping = $latest_cheapest;
                              }*/

                              if ((isset($shipping['id']) && $shipping['cost']< 0.1) || (isset($HTTP_POST_VARS['shipping']) && strpos($HTTP_POST_VARS['shipping'], '_')) ) {
                                //no method selected or first come into checkout without selecting one
                                //for customer select a different method

                                if (!tep_session_is_registered('shipping')) tep_session_register('shipping');
                                if (isset($HTTP_POST_VARS['shipping']) && strpos($HTTP_POST_VARS['shipping'], '_')) {
                                  $shipping['id'] = $HTTP_POST_VARS['shipping'];
                                }
                                if ( (tep_count_shipping_modules() > 0) || ($free_shipping == true) ) {
                                  if ( (isset($shipping['id'])) && (strpos($shipping['id'], '_')) ) {
                                    $shipping = $shipping['id'];
                            
                                    list($module, $method) = explode('_', $shipping);
                                    if ( is_object($$module) || ($shipping == 'free_free') ) {
                                      if ($shipping == 'free_free') {
                                        $quote[0]['methods'][0]['title'] = 'Free shipping';
                                        $quote[0]['methods'][0]['cost'] = '0';
                                      } else {
                                        $quote = $shipping_modules->quote($method, $module);
                                      }
                                      if (isset($quote['error'])) {
                                        tep_session_unregister('shipping');
                                      } else {
                                        if ( (isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost'])) ) {
                                          $shipping = array('id' => $shipping,
                                                            //'title' => (($free_shipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                                                            'title' => (($free_shipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module']),
                                                            //'cost' => $quote[0]['methods'][0]['cost']);
                                                            //my modification for resolving the rounding up shipping cost
                                                            'cost' => sprintf("%f", $quote[0]['methods'][0]['cost']));
                                        }
                                      }
                                    } else {
                                      tep_session_unregister('shipping');
                                    }
                                  }
                                } else {
                                  $shipping = false;
                                }
                              }

                              //My modification - shipping register
                            ?>

                            <?php 
                              echo tep_draw_form("delivery-options-form", '', 'post', 'id="delivery-options-form"', true);

                              if (tep_count_shipping_modules() > 0) {
                                if ($free_shipping == true) {
                                  echo "<p>" . FREE_SHIPPING_TITLE . sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . tep_draw_hidden_field('shipping', 'free_free') . "</p>";
                                }
                                else {
                                  $radio_buttons = 0;
                                  $international_error="";
                                  for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
                                    //20140509
                                    if (isset($quotes[$i]['dest_zone']) && !tep_not_null($quotes[$i]['dest_zone'])) continue;
                                    //20140509
                                    for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
                                      $checked = (($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $shipping['id']) ? true : false);
                                      if ( ($checked == true) || ($n == 1 && $n2 == 1) ) {
                                        echo '<div id="defaultSelected" class="moduleRowSelected" >' . "\n";
                                      } else {
                                        echo '<div class="moduleRow" >' . "\n";
                                      }

                                      //if (isset($quotes[$i]['icon']) && tep_not_null($quotes[$i]['icon'])) { echo $quotes[$i]['icon']; }
                                      
                                      if (!isset($quotes[$i]['error'])) {
                                        if ( ($n > 1) || ($n2 > 1) ) {
                                          echo '    <div class="radioLeft">' . tep_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked) . '</div>' . "\n";
                                        } else {
                                          echo $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))) . tep_draw_hidden_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']);
                                        }
                                      }

                                      echo '    <div class="radioRight">' . $quotes[$i]['module'] . '&nbsp;' . $currencies->format(tep_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0)));
                                      if (isset($quotes[$i]['error'])) {
                                        echo '      <div class="messageStackWarning">' . $quotes[$i]['error'] . '</div>';
                                      } else {
                                        echo specialmethod_desc($quotes[$i]['methods'][$j]['id'], $quotes[$i]['methods'][$j]['title']);
                                      }
                                      echo '    </div>' . "\n";
                                      echo '    <div class="clearme"></div>' . "\n";
                                      echo '</div>' . "\n";
                                      $radio_buttons++;
                                    }
                                  }
                                  if ($radio_buttons <1) echo '<p>'. $international_error . '</p>' . "\n";
                                }
                              }
                            ?>



                              <div id="safeplace">
                              <!-- comment -->
                              <?php
                                if (!tep_session_is_registered('comments')) tep_session_register('comments');
                                if (isset($HTTP_POST_VARS['comments']) && tep_not_null($HTTP_POST_VARS['comments'])) {
                                  $comments = tep_db_prepare_input($HTTP_POST_VARS['comments']);
                                }
                                else {
                                  if (!tep_not_null($comments))
                                    $comments = null;
                                }

                                if (isset($shipping) && tep_not_null($shipping)) {
                                  list($module, $method) = explode('_', $shipping['id']);
                                  if ($$module->delivery_comment) {
                                    echo '    <div id="delivery-comment" class="masterTooltip" title="' . TABLE_HEADING_COMMENTS_DELIVERY . '"><span class="smallText">' . TABLE_HEADING_COMMENTS . "</span>" . tep_draw_input_field('comments', $comments, 'class="register-input" maxlength="40" size="50"') . '</div><br />' . "\n";
                                  }
                                  else {
                                  	$comments = null;
                                  }
                                }
                              ?>
                              </div>
                            <?php echo '</form>' . "\n";?>

                        </div><!--<div id="delivery_reload">-->
                        <div class="clearme"></div><div><?php echo tep_draw_separator('pixel_cccccc.gif', '1', '1'); ?></div><div class="clearme"></div>
                        <span class="form-bt update_shipping">Continue</span>
                </div><!--/.table-wrapper #shipping_pane-->

                <div id="shipping_sum">
                      <div class="table-wrapper checkout-wrapper">
                          <h2 class="sub-title">2. DELIVERY <a class="bt-checkout-edit edit_shipping"><?php echo SMALL_IMAGE_BUTTON_EDIT;?></a></h2><br />
                          <div id="sum_shipping">
                                <?php
                                list($sum1, $sum2) = explode('_', $shipping['id']); //if (!($$sum1 instanceof MyClass))    $obj = new MyClass();
                                //find out if $$sum1 an object
                                if (isset($$sum1)) {
                                  $sum_shipping = $$sum1->quote();
                                  echo '<strong>' . $sum_shipping['module'] . '</strong>&nbsp;';
                                  echo $currencies->format(tep_add_tax($shipping['cost'], (isset($sum_shipping['tax']) ? $sum_shipping['tax'] : 0))) . "<br />"; //$sum_shipping['methods'][0]['cost']
                                  echo specialmethod_desc($sum_shipping['methods'][0]['id'], $sum_shipping['methods'][0]['title']);
                                }
                                else {
                                  echo "<strong>" . $shipping['title'] . "</strong><br /><br />";
                                  echo specialmethod_desc($sum1);
                                }
                                if (tep_not_null($comments)) echo '<div class="smallText">&nbsp;&nbsp;Your msg: ' . $comments . '</div>';
                                ?>
                          </div>
                          <div class="clearme"></div><div><?php echo tep_draw_separator('pixel_cccccc.gif', '1', '1'); ?></div><div class="clearme"></div>
                      </div><!--/.table-wrapper -->
                </div><!--/.#shipping_sum-->

                <div class="table-wrapper checkout-wrapper" id="payment_sum">
                  <div id="bt-checkout-payment">
                    <h2 class="sub-title"><?php echo '3. ' . HEADING_PAYMENT_INFORMATION; ?></h2>                            
                  </div><!--/#bt-checkout-payment-->
                </div><!--/.table-wrapper #payment_sum-->

                <div class="table-wrapper checkout-wrapper open" id="payment_pane">
                    	  <h2 class="sub-title"><?php echo '3. ' . HEADING_PAYMENT_INFORMATION; ?></h2><br />
                        <div id="coupon-voucher">
                        	<span ><?php echo TABLE_HEADING_CREDIT;?></span><br />
                        	<?php echo $order_total_modules->credit_selection();?>
                        	<span id="coupon-voucher-msg" class="hideme smallText"></span>
                        </div>
                        <br />
                        <div class="single-cart-subtotal"></div>
                        <div id="payment_reload">

                            <?php
                              if (isset($HTTP_POST_VARS['payment']) && tep_not_null($HTTP_POST_VARS['payment'])) $payment = $HTTP_POST_VARS['payment'];

                              // ** GOOGLE CHECKOUT **
                              // Skips Google checkout as a payment option on the payments page since that option is provided in the checkout cart page
                              $selection = $payment_modules->selection();
/*
                              for($i=0, $n=sizeof($selection); $i<$n; $i++) {
  	                            if (!tep_session_is_registered('missorder')) {
                                  if($selection[$i]['id'] == 'googlecheckout') {
                                    array_splice($selection, $i, 1);	
                                    break;
                                  }
                                }
                              }
*/
                              // ** END GOOGLE CHECKOUT **

                              //Paypal Express
                              for($i=0, $n=sizeof($selection); $i<$n; $i++) {
  	                            if (!tep_session_is_registered('missorder')) {
                                  if($selection[$i]['id'] == 'paypal_express' && !tep_session_is_registered('ppe_token')) {
                                    array_splice($selection, $i, 1);	
                                    break;
                                  }
                                }
                              }
                              //Paypal Express

                              if (sizeof($selection) > 1) {} 
                              else {
                              	$payment = $selection[0]['id'];
                              }
                              if (isset($$payment->form_action_url)) {
  	                            if (!tep_session_is_registered('missorder') && !tep_session_is_registered('cash_carry')) {
  	                            	 $form_action_url = $$payment->form_action_url;
  	                            }
                                else {
                               	  $form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
                               	}
                              } else {
                                $form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
                              }
                              echo tep_draw_form('checkout_confirmation', $form_action_url, 'post', 'id="payment-options-form"', true);

                              //payment selected form/input
                              if (is_array($payment_modules->modules)) {
                                    	$payment_inputform ="";
                                      if (isset($payment) && tep_not_null($payment)) {
                                      	if ($payment != "credit_covers") {
                                          if ($confirmation = $$payment->confirmation()) {
                                            $payment_inputform .= '  <div id="paymentinput">' . "\n";
                                            if (tep_not_null($confirmation['title']))
                                              $payment_inputform .= '  <div class="smallText">'. $confirmation['title'] . '</div><br />' . "\n";
                                            if (isset($confirmation['fields'])) {
                                              for ($i=0, $n=sizeof($confirmation['fields']); $i<$n; $i++) {
                                                  $payment_inputform .= '  <div class="sfBlock">' . "\n";
                                                  $payment_inputform .= '    <div class="sfLeft"><p>' . $confirmation['fields'][$i]['title'] . '</p></div>' . "\n";
                                                  $payment_inputform .= '    <div class="sfRight">' . $confirmation['fields'][$i]['field'] . '</div>' . "\n";
                                                  $payment_inputform .= '  </div>' . "\n";
                                              }
                                            }
                                            $payment_inputform .= '  </div>' . "\n";
                                            $payment_inputform .= '  <div id="paymentloading" class="loadingpayment"></div>' . "\n";
                                          }
                                        }
                                      }
                              }

                              $radio_buttons = 0;
                              for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
                                if ( ($selection[$i]['id'] == $payment) || ($n == 1) ) {
                                  echo '                  <div id="defaultSelected" class="moduleRowSelected">' . "\n";
                                } else {
                                  echo '                  <div class="moduleRow">' . "\n";
                                }

                                if (sizeof($selection) > 1) {
                                	echo '<div class="radioLeft">';
                                  echo tep_draw_radio_field('payment', $selection[$i]['id'], ($selection[$i]['id'] == $payment));
                                  echo '</div>';
                                  echo '<div class="radioRight"><strong>' . $selection[$i]['module'] . '</strong></div>' . "\n";
                                  echo '<div class="clearme"></div>' . "\n";
                                } else {
                                	echo '<div class="sLeft">';
                                  echo tep_draw_hidden_field('payment', $selection[$i]['id']);
                                  //echo '<span class="smallText">' . $selection[$i]['module'] . '</span>';
                                  //echo '<span class="smallText">&nbsp;</span>';
                                  echo '</div>';
                                  echo '<div class="sRight">' . $selection[$i]['icon'] . '</div>';
                                  echo '<div class="clearme"></div>';
                                }
                                if (isset($selection[$i]['error'])) {
                                  echo "<p>" . $selection[$i]['error'] . "</p>";
                                } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
                                }
                                else {
                                  if (sizeof($selection) > 1) {echo ($selection[$i]['id'] == $payment ? $payment_inputform: ''); }
                                  else {echo $payment_inputform;}
                                }
                                echo '</div>' . "\n";
                                $radio_buttons++;
                              }
                            ?>


                            <div id="payment_confirm_button">
                              <div id="p_button_reload">
                              <?php
                                if ($payment == 'protx_direct') {
                                  echo $$payment->process_button();
                                }
                                else {
                                	echo '<input type="submit" class="form-bt" value="Confirm Payment" id="submitpayment" />' . "\n";
                                }
                              ?>
                              </div>
                            </div>

                            <?php echo '</form>';?>
                        </div><!--payment_reload-->

                    	<p>
				                <div><input type="hidden" name="credit_cover_t" value="0" id="credit_cover_t" /></div>
				                <div>
				                    <?php
				                      // Start - CREDIT CLASS Gift Voucher Contribution
				                      echo '<div id="credit_cover_div">' . "\n";
				                              if (tep_session_is_registered('customer_id')) {
				                              	$gv_query = tep_db_query("select amount from " . TABLE_COUPON_GV_CUSTOMER . " where customer_id = '" . $customer_id . "'");

					                              $gv_result = tep_db_fetch_array($gv_query);
				                                if ($gv_result['amount']>0 && !tep_session_is_registered('cc_id')) { //My modification: GC and CC cannot be used at the same time
				                                  //echo '<div style="float:right;background-color:#ffffff;padding:0px 0px 0px 0px;margin:0px 14px 0px 0px;">' . $order_total_modules->sub_credit_selection() . '</div>';
				                                  echo $order_total_modules->sub_credit_selection();
				                                }
				                              }
				                      echo '</div>' . "\n";
				                      // End - CREDIT CLASS Gift Voucher Contribution
				                    ?>
				                    <span class="smallText">SSL SECURE CHECKOUT</span>
				                </div>
                    	</p>

                </div><!--/.table-wrapper #payment_pane-->

            	</div><!--/.grid-01-01-->

              <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
              <div class="clearme"></div>
          	</div><!--/.grid-01-->
          </div>


        </div><!--/#container-->







































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>