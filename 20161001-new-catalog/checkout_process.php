<?php


  include('includes/application_top.php');

// if the customer is not logged on, redirect them to the login page
  // {{ AMAZON PAYMENT
  // if (!tep_session_is_registered('customer_id') ) {
  if ( $payment!=='amazon_inline' && !tep_session_is_registered('customer_id') ) {
  // }} AMAZON PAYMENT
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT));
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }
// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }
// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!tep_session_is_registered('shipping') || !tep_session_is_registered('sendto')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
  }

  if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!tep_session_is_registered('payment')) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
  }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
    }
  }

  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

// load selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
// Start - CREDIT CLASS Gift Voucher Contribution
  if ($credit_covers) $payment='credit covers';
// End - CREDIT CLASS Gift Voucher Contribution
  $payment_modules = new payment($payment);

// load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($shipping);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

  $payment_modules->update_status();

  //if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
  /*if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) && (!$credit_covers) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
  }*/

  if ( ($payment_modules->selected_module != $payment) || ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
  	if (!$credit_covers) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
    }
  }

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;
  $order_totals = $order_total_modules->process();
// load the before_process function from the payment modules
  $payment_modules->before_process();

//My modification - check if it is postage payment only
  skip_manualorder_record($payment_modules, $order_total_modules, $cart);
//My modification - check if it is postage payment only

  $sql_data_array = array('customers_id' => $customer_id,
                          'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                          'customers_company' => $order->customer['company'],
                          'customers_house_name' => $order->customer['house_name'],
                          'customers_street_address' => $order->customer['street_address'],
                          'customers_suburb' => $order->customer['suburb'],
                          'customers_city' => $order->customer['city'],
                          'customers_postcode' => $order->customer['postcode'],
                          'customers_state' => $order->customer['state'],
                          'customers_country' => $order->customer['country']['title'],
                          'customers_telephone' => $order->customer['telephone'],
                          'customers_email_address' => $order->customer['email_address'],
                          'customers_address_format_id' => $order->customer['format_id'],
                          'delivery_name' => trim($order->delivery['firstname'] . ' ' . $order->delivery['lastname']),
                          'delivery_company' => $order->delivery['company'],
                          'delivery_house_name' => $order->delivery['house_name'],
                          'delivery_street_address' => $order->delivery['street_address'],
                          'delivery_suburb' => $order->delivery['suburb'],
                          'delivery_city' => $order->delivery['city'],
                          'delivery_postcode' => $order->delivery['postcode'],
                          'delivery_state' => $order->delivery['state'],
                          'delivery_country' => $order->delivery['country']['title'],
                          'delivery_address_format_id' => $order->delivery['format_id'],
                          'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                          'billing_company' => $order->billing['company'],
                          'billing_house_name' => $order->billing['house_name'],
                          'billing_street_address' => $order->billing['street_address'],
                          'billing_suburb' => $order->billing['suburb'],
                          'billing_city' => $order->billing['city'],
                          'billing_postcode' => $order->billing['postcode'],
                          'billing_state' => $order->billing['state'],
                          'billing_country' => $order->billing['country']['title'],
                          'billing_address_format_id' => $order->billing['format_id'],
                          'payment_method' => $order->info['payment_method'],
                          'cc_type' => $order->info['cc_type'],
                          'cc_owner' => $order->info['cc_owner'],
                          'cc_number' => $order->info['cc_number'],
                          'cc_expires' => $order->info['cc_expires'],
                          'date_purchased' => 'now()',
                          //'orders_status' => $order->info['order_status'],
                          'orders_status' => ((is_cashcarry()) ? '3' : $order->info['order_status']),
                          'currency' => $order->info['currency'],
                          'currency_value' => $order->info['currency_value'],
                          // PWA BOF
                          'purchased_without_account' => $order->customer['is_dummy_account'],
                          // PWA EOF
                          'cash_carry' => ((is_cashcarry()) ? "1" : "0"));

  tep_db_perform(TABLE_ORDERS, $sql_data_array);
  $insert_id = tep_db_insert_id();

        //20140908

      	if ($order->delivery['country']['title'] == 'United Kingdom') {
      		if ($order_totals[2]['value'] >= 80) {
      			if(preg_match('/Standard/i',$order_totals[3]['title'])) {
      				$order_totals[3]['title'] = 'UK Express';
      			}
      		}
      	}

      	//20140908

  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    $sql_data_array = array('orders_id' => $insert_id,
                            'title' => $order_totals[$i]['title'],
                            'text' => $order_totals[$i]['text'],
                            'value' => $order_totals[$i]['value'], 
                            'class' => $order_totals[$i]['code'], 
                            'sort_order' => $order_totals[$i]['sort_order']);
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  }

  //My modification - no postage m_extraitems
  manual_ordertotal_extraitems($insert_id);
  //My modification - no postage m_extraitems

  $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
  $sql_data_array = array('orders_id' => $insert_id, 
                          'orders_status_id' => ((is_cashcarry()) ? "3" : $order->info['order_status']), 
                          'date_added' => 'now()',
                          'customer_notified' => $customer_notification,
                          'comments' => $order->info['comments']);
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// initialized for the email confirmation
  $products_ordered = '';
  $subtotal = 0;
  $total_tax = 0;

  //My modification - possible sub-order update
  $suborder_flag = false;
  //My modification - possible sub-order update

  //My modification - daily deals
  $dailydeal = tep_get_daily_deals();
  //My modification - daily deals

  for ($i=0, $n=sizeof($order->products); $i<$n; $i++)
  {
// Stock Update - Joao Correia;
    //My mod 20130530
    $product_order_backordered = "";
    //My mod 20130530


// QT Pro: Begin Changed code --- new added code
    $products_stock_attributes=null;

    if (STOCK_LIMITED == 'true')
    {
      $products_attributes = (isset($order->products[$i]['attributes'])) ? $order->products[$i]['attributes'] : '';

//My modification - colour sample
$attribute_pid1 = tep_get_prid($order->products[$i]['id']);
if (is_coloursample_pid($order->products[$i]['id'])) {
	$attribute_pid1 = (int)tep_get_sample_parentuprid($order->products[$i]['id']);
}
//My modification - colour sample
//    if (DOWNLOAD_ENABLED == 'true'
//    {
// QT Pro: End Changed Code --- new added code
        $stock_query_raw = "SELECT products_quantity, products_bundle, pad.products_attributes_filename 
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . (int)$attribute_pid1 . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop

// QT Pro: Begin Changed code --- new deleted code
//        $products_attributes = $order->products[$i]['attributes'];
// QT Pro: End Changed Code --- new deleted code
        
        if (is_array($products_attributes))
        {
          $stock_query_raw .= " AND pa.options_id = '" . (int)$products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . (int)$products_attributes[0]['value_id'] . "'";
        }
        $stock_query = tep_db_query($stock_query_raw);

    }else {
        $stock_query = tep_db_query("select products_quantity, products_bundle from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
    }

      if (tep_db_num_rows($stock_query) > 0)
      {
        $stock_values = tep_db_fetch_array($stock_query);
//++++ QT Pro: Begin Changed code
        $actual_stock_bought = $order->products[$i]['qty'];
        $download_selected = false;
        if ((DOWNLOAD_ENABLED == 'true') && isset($stock_values['products_attributes_filename']) && tep_not_null($stock_values['products_attributes_filename']))
        {
          $download_selected = true;
          $products_stock_attributes='$$DOWNLOAD$$';
        }
//      If not downloadable and attributes present, adjust attribute stock
        if (!$download_selected && is_array($products_attributes) && ($stock_values['products_bundle'] != 'yes') )
        {
          $all_nonstocked = true;
          $products_stock_attributes_array = array();
          foreach ($products_attributes as $attribute)
          {
            if ($attribute['track_stock'] == 1) {
              $products_stock_attributes_array[] = $attribute['option_id'] . "-" . $attribute['value_id'];
              $all_nonstocked = false;
            }
          } 
          if ($all_nonstocked)
          {
            $actual_stock_bought = $order->products[$i]['qty'];
          }
          //My modification - colour sample
          elseif (is_coloursample_pid($order->products[$i]['id'])) {
          	$actual_stock_bought = $order->products[$i]['qty'];
            asort($products_stock_attributes_array, SORT_NUMERIC);
            $products_stock_attributes = implode(",", $products_stock_attributes_array);
          }
          //My modification - colour sample
          else
          {
            asort($products_stock_attributes_array, SORT_NUMERIC);
            $products_stock_attributes = implode(",", $products_stock_attributes_array);

            $attributes_stock_query = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_stock_attributes = '".$products_stock_attributes."' AND products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
            if (tep_db_num_rows($attributes_stock_query) > 0)
            {
            	//My modification: prebonded product stock update
/*
           		//$product_categories_name = tep_get_products_categories_name(tep_get_prid($order->products[$i]['id']), $languages_id);
	            if (check_nailstick(tep_get_prid($order->products[$i]['id']))) {
	            	$actual_stock_bought=checkout_update_prebonded_qty(tep_get_prid($order->products[$i]['id']), $products_stock_attributes_array, $order->products[$i]['qty']);
	            }
	            //My modification: prebonded product stock update
	            else
	            {
*/
                $attributes_stock_values = tep_db_fetch_array($attributes_stock_query);
                $attributes_stock_left = $attributes_stock_values['products_stock_quantity'] - $order->products[$i]['qty'];
                tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = '" . (int)$attributes_stock_left . "' where products_stock_attributes = '". $products_stock_attributes ."' AND products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
                $actual_stock_bought = ($attributes_stock_left < 1) ? $attributes_stock_values['products_stock_quantity'] : $order->products[$i]['qty'];

              //}
            }
            else
            {
              $attributes_stock_left = 0 - $order->products[$i]['qty'];
              tep_db_query("insert into " . TABLE_PRODUCTS_STOCK . " (products_id, products_stock_attributes, products_stock_quantity) values ('" . tep_get_prid($order->products[$i]['id']) . "', '" . $products_stock_attributes . "', '" . $attributes_stock_left . "')");
              $actual_stock_bought = 0;
            }
                            
                //My modification - possible sub-order update
                if ($attributes_stock_left <0) {
                	$suborder_flag = true;
                	$product_order_backordered = BACKORDERED_ITEMS;
                }

          }
        }
//        $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
//      }
//      if (tep_db_num_rows($stock_query) > 0) {
//        $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists


        if (!$download_selected)
        {
        	if ($stock_values['products_bundle'] == 'yes') {
        		bundle_update($order->products[$i]);
        		
        		//My modification - check for suborder
        		if (is_bundle_suborder($order->products[$i]['id'])) {
        			$suborder_flag = true;
        			$product_order_backordered = BACKORDERED_ITEMS;
        		}
        		//My modification - check for suborder
        	}
        	else {
          //$stock_left = $stock_values['products_quantity'] - $actual_stock_bought;

          //My modification - colour sample
          //if (!tep_not_null($attribute_pid1))
          //  $attribute_pid1 = tep_get_prid($order->products[$i]['id']);
          //My modification - colour sample
          //My modification - colour sample
            $update_stock_pid = tep_get_prid($order->products[$i]['id']);
            if (is_coloursample_pid($order->products[$i]['id'])) {
	            $update_stock_pid = tep_get_sample_prid($order->products[$i]['id']);
            }
            //My modification - colour sample
            tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET products_quantity = products_quantity - '" . (int)$actual_stock_bought . "' WHERE products_id = '" . (int)$update_stock_pid . "'");

            //201512
            if ((int)$order->products[$i]['parent_id']>0) { //slave product
		          if (isset($order->products[$i]['products_variants']) && sizeof($order->products[$i]['products_variants']) > 0) {
                pv_update_h_pack(tep_get_prid($order->products[$i]['id']), $actual_stock_bought, $order->products[$i]['parent_id'], $order->products[$i]['products_variants']);
              }
            }

            $stock_values = tep_db_fetch_array(tep_db_query("select products_quantity, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'"));
            if ($stock_values['products_quantity'] <0) {
            	if (!is_array($products_attributes)) {
            	  $suborder_flag = true;
            	  if (!is_pickup_daily($stock_values['manufacturers_id']))
            	    $product_order_backordered = BACKORDERED_ITEMS;
            	}
            }
          }
//++++ QT Pro: End Changed Code
          /*if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') )
          {
            tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
          }*/
        }

      }

//  }//if (DOWNLOAD_ENABLED == 'true')

// Update products_ordered (for bestsellers list)
    tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
    if ((int)$order->products[$i]['parent_id']>0) {
    	tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['parent_id']) . "'");
    }

// QT Pro: Begin Changed code
    if (!isset($products_stock_attributes)) $products_stock_attributes=null;
    $sql_data_array = array('orders_id' => $insert_id, 
                            //'products_id' => tep_get_prid($order->products[$i]['id']),
                            'products_id' => ((int)$order->products[$i]['id'] == coloursample_pid()) ? tep_get_sample_prid($order->products[$i]['id']) : tep_get_prid($order->products[$i]['id']),
                            'products_model' => (strlen($order->products[$i]['model'])>32) ? substr($order->products[$i]['model'], 0, 32) : $order->products[$i]['model'],
                            'products_name' => (strlen($order->products[$i]['name'])>96) ? substr($order->products[$i]['name'], 0, 96) : $order->products[$i]['name'],
                            'products_price' => $order->products[$i]['price'], 
                            'final_price' => $order->products[$i]['final_price'], 
                            'products_tax' => $order->products[$i]['tax'], 
                            'products_quantity' => $order->products[$i]['qty'],
                            'products_stock_attributes' => (tep_not_null($products_stock_attributes)) ? tep_db_prepare_input($products_stock_attributes) : 'null');
// QT Pro: End Changed Code
    //201512
    if (is_coloursample_pid($order->products[$i]['id'])) {
    	$ps_pv_a = pv_sample_variants($order->products[$i]['id']);
      if (sizeof($ps_pv_a)) {
        foreach ($ps_pv_a as $v) {
          $sql_data_array['products_name'] .= " " . $v['value'];
        }
      }
      $sql_data_array['products_model'] = tep_get_sample_parentuprid($order->products[$i]['id']);
    }
    //201512
    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = tep_db_insert_id();
// Start - CREDIT CLASS Gift Voucher Contribution
// CCGV 5.19 Fix for GV Queue with Paypal IPN
  $order_total_modules->update_credit_account($i,$insert_id);
// End - CREDIT CLASS Gift Voucher Contribution

//------insert customer choosen option to order--------
    $attributes_exist = '0';
    $products_ordered_attributes = '';
    if (isset($order->products[$i]['attributes'])) {
      //My modification - colour sample
      if (!tep_not_null($attribute_pid1))
        $attribute_pid1 = tep_get_prid($order->products[$i]['id']);
      //My modification - colour sample

      $attributes_exist = '1';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        if (DOWNLOAD_ENABLED == 'true') {
          $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . (int)$attribute_pid1 . "' 
                                and pa.options_id = '" . (int)$order->products[$i]['attributes'][$j]['option_id'] . "' 
                                and pa.options_id = popt.products_options_id 
                                and pa.options_values_id = '" . (int)$order->products[$i]['attributes'][$j]['value_id'] . "' 
                                and pa.options_values_id = poval.products_options_values_id 
                                and popt.language_id = '" . (int)$languages_id . "' 
                                and poval.language_id = '" . (int)$languages_id . "'";
          $attributes = tep_db_query($attributes_query);
        } else {
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . (int)$order->products[$i]['id'] . "' and pa.options_id = '" . (int)$order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . (int)$languages_id . "' and poval.language_id = '" . (int)$languages_id . "'");
        }
        $attributes_values = tep_db_fetch_array($attributes);

        $sql_data_array = array('orders_id' => $insert_id, 
                                'orders_products_id' => $order_products_id, 
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'], 
                                'options_values_price' => $attributes_values['options_values_price'], 
                                'price_prefix' => $attributes_values['price_prefix'],
                                //My modification store option_id and value_id to DB, mainly for paypal ipn module
                                'option_id' => $order->products[$i]['attributes'][$j]['option_id'],
                                'value_id' => $order->products[$i]['attributes'][$j]['value_id']);
                                //My modification store option_id and value_id to DB, mainly for paypal ipn module
        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
          $sql_data_array = array('orders_id' => $insert_id, 
                                  'orders_products_id' => $order_products_id, 
                                  'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                  'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                  'download_count' => $attributes_values['products_attributes_maxcount']);
          tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
        }
        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
      }
    }
		elseif ((int)$order->products[$i]['parent_id']>0) { //slave product
		  if (isset($order->products[$i]['products_variants']) && sizeof($order->products[$i]['products_variants']) > 0) {
        foreach ($order->products[$i]['products_variants'] as $k => $v) {
          $sql_data_array = array('orders_id' => (int)$insert_id, 'orders_products_id' => (int)$order_products_id, 'group_title' => $v['group'], 'value_title' => $v['value']);
          tep_db_perform(TABLE_OSC_ORDER_PRODUCTS_VARIANTS, $sql_data_array);
          $products_ordered_attributes .= "\n\t" . $v['group'] . ' ' . $v['value'];
        }
      }
		}

//------insert customer choosen option eof ----
    $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
    //$total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
    $total_tax += tep_calculate_tax($order->products[$i]['final_price'], $order->products[$i]['tax']) * $order->products[$i]['qty'];
    //$total_cost += $total_products_price;

    //$products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
    $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price_nodiscount($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";

    //My mod 20130530
    if (tep_not_null($product_order_backordered)) $products_ordered .= $product_order_backordered . "\n\n";
    //My mod 20130530

    //My modification - daily deals
    if ($dailydeal['pid'] == tep_get_prid($order->products[$i]['id'])) {
    	if (tep_not_null($dailydeal['att']) && isset($order->products[$i]['attributes'])) {
    		$dailydeal_price_att = "";
        for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        	if (strlen($dailydeal_price_att)>0)
            $dailydeal_price_att .= "," . $order->products[$i]['attributes'][$j]['option_id'] . '-'. $order->products[$i]['attributes'][$j]['value_id'];
          else
            $dailydeal_price_att .= $order->products[$i]['attributes'][$j]['option_id'] . '-'. $order->products[$i]['attributes'][$j]['value_id'];
        }
        if ($dailydeal_price_att == $dailydeal['att']) {
        	update_daily_deals(tep_get_prid($order->products[$i]['id']), $dailydeal_price_att, $order->products[$i]['qty'], $dailydeal);
        }
    	}
    	else {
    		update_daily_deals(tep_get_prid($order->products[$i]['id']), "", $order->products[$i]['qty'], $dailydeal);
    	}
    }
    //My modification - daily deals
    
    //20140522
    specials_limit_update(tep_get_prid($order->products[$i]['id']), $order->products[$i]['qty']);
    //20140522
  }//order products loop

    //My modification - possible sub-order update
    if ($suborder_flag && !is_cashcarry()) {
    	//if ($order->info['order_status'] != 7) {
      //$suborder_flag = true;
      $suborder_status_id = 12;
      tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (int)$suborder_status_id . "' where orders_id = '" . (int)$insert_id . "'");
      $sql_data_array = array('orders_id' => $insert_id,
                              'orders_status_id' => $suborder_status_id,
                              'date_added' => 'now()',
                              'customer_notified' => 0,
                              'comments' => '');
      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      //}
    }

// Start - CREDIT CLASS Gift Voucher Contribution
  $order_total_modules->apply_credit();
// End - CREDIT CLASS Gift Voucher Contribution

//My modification - colour sample
checkout_setup_giftvoucher($order);
//My modification - colour sample


//My modification - discount voucher code
//checkout_setup_t1_giftvoucher($order);
//My modification - discount voucher code

  // PWA
//lets start with the email confirmation
// DDB - 041103 - Add test for PWA : no display of invoice URL if PWA customer
//if (!tep_session_is_registered('noaccount')) 
//{
  //My modification - Add new content to the order email
  $email_order = "Dear " . $order->customer['firstname'] . ' ' . $order->customer['lastname'] . ",\n\n";
  $email_order .= EMAIL_TEXT_1 . "\n\n";
  if ($suborder_flag)
    $email_order .= EMAIL_TEXT_7 . "\n\n";

  $email_order .= EMAIL_TEXT_6 . "\n\n";
  //My modification - Add new content to the order email

  $email_order .= STORE_NAME . "\n" . 
                 EMAIL_SEPARATOR . "\n" . 
                 EMAIL_TEXT_ORDER_NUMBER . ' ' . $insert_id . "\n" .
                 ((!$order->customer['is_dummy_account']) ? (EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n") : '') .
                 EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
 // PWA BOF
  if ($order->customer['is_dummy_account']) {
    $email_order .= EMAIL_WARNING . "\n\n";
  }
  // PWA EOF
  if ($order->info['comments']) 
  {
    $email_order .= tep_db_output($order->info['comments']) . "\n\n";
  }
  $email_order .= EMAIL_TEXT_PRODUCTS . "\n" . 
                  EMAIL_SEPARATOR . "\n" . 
                  $products_ordered . 
                  EMAIL_SEPARATOR . "\n";
//}
/*else {
  $email_order = STORE_NAME . "\n" . 
                 EMAIL_SEPARATOR . "\n" . 
                 EMAIL_TEXT_ORDER_NUMBER . ' ' . $insert_id . "\n" .
                 EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
  if ($order->info['comments']) {
    $email_order .= tep_db_output($order->info['comments']) . "\n\n";
  }
  $email_order .= EMAIL_TEXT_PRODUCTS . "\n" . 
                  EMAIL_SEPARATOR . "\n" . 
                  $products_ordered . 
                  EMAIL_SEPARATOR . "\n";
  }*/

  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
  }

  if ($order->content_type != 'virtual') {
    $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" . 
                    EMAIL_SEPARATOR . "\n" .
                    tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
  }

  $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                  EMAIL_SEPARATOR . "\n" .
                  tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";
  if (is_object($$payment)) {
    $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . 
                    EMAIL_SEPARATOR . "\n";
    $payment_class = $$payment;
    $email_order .= $order->info['payment_method'] . "\n\n";
    if ($payment_class->email_footer) { 
      $email_order .= $payment_class->email_footer . "\n\n";
    }
  }

  $email_order .= EMAIL_TEXT_2 . "\n\n";
  $email_order .= EMAIL_TEXT_3 . "\n\n";
  $email_order .= EMAIL_TEXT_4 . "\n\n";
  $email_order .= EMAIL_TEXT_5 . "\n\n";
  
  // {{ AMAZON PAYMENT
  if ( $payment!='amazon_inline' ) {
  // }} AMAZON PAYMENT
  //tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
  if (!is_cashcarry() && !isFlatCustomer($order->customer['email_address']) ) {
    if (!tep_session_is_registered('missorder')) {
      tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT_1 . ' ' . $insert_id . ' ' .EMAIL_TEXT_SUBJECT_2 , $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
    }
  }
//end PWA

// send emails to other people
  if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
    //tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT_1 . ' ' . $insert_id . ' ' .EMAIL_TEXT_SUBJECT_2, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
  }
  // {{ AMAZON PAYMENT
  }
  // }} AMAZON PAYMENT

  ////My modification - check and auto email customer for discount
  if (AUTO_CUSTOMER_DISCOUNT_ENABLE == 'true') {
    check_customer_discount($customer_id);
  }
  ////My modification - check and auto email customer for discount




// load the after_process function from the payment modules
  $payment_modules->after_process();

  $cart->reset(true);

// unregister session variables used during checkout
  tep_session_unregister('sendto');
  tep_session_unregister('billto');
  tep_session_unregister('shipping');
  tep_session_unregister('payment');
  tep_session_unregister('comments');
// Start - CREDIT CLASS Gift Voucher Contribution
  if(tep_session_is_registered('credit_covers')) tep_session_unregister('credit_covers');
  $order_total_modules->clear_posts();
// End - CREDIT CLASS Gift Voucher Contribution
  tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>