<?php
chdir("./../../..");
require('includes/application_top.php');

  $action = (isset($HTTP_POST_VARS['action']) ? $HTTP_POST_VARS['action'] : '');
  $languages = tep_get_languages();

  //$tax_class_array = array(array('id' => '0', 'text' => TEXT_NONE));
  $tax_class_query = tep_db_query("select tax_class_id, tax_class_title from " . TABLE_TAX_CLASS . " order by tax_class_title");
  while ($tax_class = tep_db_fetch_array($tax_class_query)) {
    $tax_class_array[] = array('id' => $tax_class['tax_class_id'],
                               'text' => $tax_class['tax_class_title']);
  }

  //New group - auto incremental group sort_order
  if (isset($action) && $action == 'pricechange_priceslist') {
    if (isset($HTTP_POST_VARS['mid']) || isset($HTTP_POST_VARS['cid'])) {
      $tbody_str ="";
      $tbody_str .= '  <tr class="dataTableHeadingRow">' . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Product Name</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Model</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Attributes</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Tax Class</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Price Exc/Inc</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Market Price Exc/Inc</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Buying Price Exc/Inc</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Wholesale Price Exc/Inc</td>'  . "\n" .
                                       '    <td class="dataTableHeadingContent" align="left">Check</td>' . "\n" .
                                       '  </tr>' . "\n";

      if (isset($HTTP_POST_VARS['mid']) && (int)$HTTP_POST_VARS['mid'] >0 ) {
        $products_query = tep_db_query("select pd.products_name, p.products_model, p.products_id, p.manufacturers_id, p.products_price, p.products_market_price, p.products_buying_price, p.products_wholesale_price, p.products_tax_class_id,p.products_free_shipping, p.parent_id, p.has_children from products p left join specials s on p.products_id = s.products_id, products_description pd, manufacturers m where p.products_status = '1' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id  . "' and p.manufacturers_id = m.manufacturers_id and m.manufacturers_id = '" . (int)$HTTP_POST_VARS['mid'] . "' order by p.products_model, pd.products_name");
      }
      elseif (isset($HTTP_POST_VARS['cid']) && (int)$HTTP_POST_VARS['cid'] >0 ) {
        if (tep_has_category_subcategories($HTTP_POST_VARS['cid'])) {
          $index_catlist = " in (0) ";
        }
        else {
          $listofcategories = get_descendants_cat($HTTP_POST_VARS['cid']);
          foreach ($listofcategories as $value) {
            $index_catlist .= $value . ",";
          }
          $index_catlist = "in (" . substr($index_catlist,0, -1) .") ";
        }
        $products_query = tep_db_query("select distinct pd.products_name, p.products_model, p.products_id, p.products_price, p.products_market_price, p.products_buying_price, p.products_wholesale_price, p.products_tax_class_id, p.products_free_shipping, p.parent_id, p.has_children from " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c, " . TABLE_CATEGORIES . " c where p.products_status = '1' and p.has_children is null and p.products_id = p2c.products_id and pd.products_id = p2c.products_id and c.categories_id=p2c.categories_id and pd.language_id = '" . (int)$languages_id . "' and p2c.categories_id " . $index_catlist . " order by c.sort_order, pd.products_name");
      }

      if (tep_db_num_rows($products_query)) {
        $pv_array = array();
        $print_pv_array = array();
        $pv_test_array = array();
        while($products_array = tep_db_fetch_array($products_query)) {
            //Filter the select attributes
            //display attributes
              $product_unique = $products_array['products_id'] ."|". $products_array['products_stock_id'];
              /*
              $price_value = number_format(tep_add_tax($products_array['products_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);
              $mprice_value = number_format(tep_add_tax($products_array['products_market_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);
              $bprice_value = number_format(tep_add_tax($products_array['products_buying_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);
              $wprice_value = number_format(tep_add_tax($products_array['products_wholesale_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);
              */
              $price_value = number_format($products_array['products_price'],4);
              $price_value_inc = number_format(tep_add_tax($products_array['products_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);

              $mprice_value = number_format($products_array['products_market_price'],4);
              $mprice_value_inc = number_format(tep_add_tax($products_array['products_market_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);

              $bprice_value = number_format($products_array['products_buying_price'],4);
              $bprice_value_inc = number_format(tep_add_tax($products_array['products_buying_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);

              $wprice_value = number_format($products_array['products_wholesale_price'],4);
              $wprice_value_inc = number_format(tep_add_tax($products_array['products_wholesale_price'], tep_get_tax_rate($products_array['products_tax_class_id'])),2);

              $display_name = $products_array['products_name'];
              $display_model = $products_array['products_model'];
              $att_str ="";
              if ((int)$products_array['parent_id']>0) {
                if ($temp_parent_id != $products_array['parent_id']) {
                  $display_parent_info = pv_slave_name($products_array['parent_id']);
                  $temp_parent_id = $products_array['parent_id'];
                }
                $display_name = $display_parent_info['products_name'];
                $display_model = $display_parent_info['products_model'];

                $att_str = pv_slave_value($products_array['parent_id'], $products_array['products_id']);

                $single_pv = pv_variants($products_array['products_id']);
                if (isset($HTTP_POST_VARS['pvid']) && (int)$HTTP_POST_VARS['pvid']>0) { //filter matching product variants
                  $single_pv_match_filter = false;
                  foreach ($single_pv as $v) {
                    if ((int)$v['value_id'] == (int)$HTTP_POST_VARS['pvid']) {
                      $single_pv_match_filter = true; break;
                    }
                  }
                  if (!$single_pv_match_filter) continue;
                }
                else {
                  foreach ($single_pv as $v) {
                    if (!in_array($v['value_id'], $pv_test_array)) {
                      $pv_array[$v['group'] . "-" . $v['value']] = array('id' => $v['value_id'], 'text' => $v['group'] . "-" . $v['value']);
                      $pv_test_array[] = $v['value_id'];
                    }
                  }
                }

                $print_pv_array[$display_name . "||" . $att_str . "||" . $products_array['products_id']] = array('pid' => $products_array['products_id'], 'pname' => $display_name, 'model' => $display_model, 'tax_id' => $products_array['products_tax_class_id'], 'punique' => $product_unique, 'price' => $price_value, 'price_inc' => $price_value_inc, 'mprice' => $mprice_value, 'mprice_inc' => $mprice_value_inc, 'bprice' => $bprice_value, 'bprice_inc' => $bprice_value_inc, 'wprice' => $wprice_value, 'wprice_inc' => $wprice_value_inc);

                continue;
              }
              elseif (tep_not_null($products_array['has_children'])) {
                continue;
              }
              elseif ((int)$products_array['products_tax_class_id'] < 1) {
              	continue;
              }

              if (isset($HTTP_POST_VARS['pvid']) && (int)$HTTP_POST_VARS['pvid']>0) {
                continue; //if there is filter input for products variants then skip the default products listing
              }

              $tbody_str .= '  <tr class="dataTableRow" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);">' . "\n" .
                                          '    <td class="smallText"><a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $products_array['products_id']) . '&action=new_product" target="blank">' . $display_name . '</a></td>' . "\n" .
                                          '    <td class="smallText" align="left" valign="top">' . $display_model . '</td>' . "\n".
                                          '    <td class="smallText" align="left" valign="top">' . $att_str . '</td>' . "\n" .
                                          '    <td class="smallText" align="left" valign="top">' . tep_draw_pull_down_menu($product_unique . "_" . 'tax_id', $tax_class_array, $products_array['products_tax_class_id'], '') . '</td>' . "\n" .
                                          '    <td class="smallText">' . tep_draw_input_field($product_unique."_".'oprice', $price_value, 'size=6 maxlength=8') . " " . tep_draw_input_field($product_unique."_".'oprice_inc', $price_value_inc, 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($product_unique."_".'mprice', $mprice_value, 'size=6 maxlength=8') . " " . tep_draw_input_field($product_unique."_".'mprice_inc', $mprice_value_inc, 'size=6 maxlength=8') .'</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($product_unique."_".'bprice', $bprice_value, 'size=6 maxlength=8') . " " . tep_draw_input_field($product_unique."_".'bprice_inc', $bprice_value_inc, 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($product_unique."_".'wprice', $wprice_value, 'size=6 maxlength=8') . " " . tep_draw_input_field($product_unique."_".'wprice_inc', $wprice_value_inc, 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . '<button edit="' . $products_array['products_id'] . '" class="spbutton"><span class="smallText">' . TEXT_UPDATE . '</span></button>' . '</td>' . "\n" .
                                          '  </tr>' . "\n" .
                                          '  <tr>' . "\n" .
                                          '    <td class="smallText" colspan="2"></td>' . "\n" .
                                          '  </tr>' . "\n";
        }
          ksort($print_pv_array);
          foreach ($print_pv_array as $k => $v) {
                $display_pv = explode("||", $k);
                $tbody_str .= '  <tr class="dataTableRow" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);">' . "\n" .
                                          '    <td class="smallText"><a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $v['pid']) . '&action=new_product" target="blank">' . $v['pname'] . '</a></td>' . "\n" .
                                          '    <td class="smallText" align="left" valign="top">' . $v['model'] . '</td>' . "\n".
                                          '    <td class="smallText" align="left" valign="top">' . $display_pv[1] . '</td>' . "\n" .
                                          '    <td class="smallText" align="left" valign="top">' . tep_draw_pull_down_menu($v['punique']."_".'tax_id', $tax_class_array, $v['tax_id'], '') . '</td>' . "\n".
                                          '    <td class="smallText">' . tep_draw_input_field($v['punique']."_".'oprice', $v['price'], 'size=6 maxlength=8') . " " . tep_draw_input_field($v['punique']."_".'oprice_inc', $v['price_inc'], 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($v['punique']."_".'mprice', $v['mprice'], 'size=6 maxlength=8') . " " . tep_draw_input_field($v['punique']."_".'mprice_inc', $v['mprice_inc'], 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($v['punique']."_".'bprice', $v['bprice'], 'size=6 maxlength=8') . " " . tep_draw_input_field($v['punique']."_".'bprice_inc', $v['bprice_inc'], 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . tep_draw_input_field($v['punique']."_".'wprice', $v['wprice'], 'size=6 maxlength=8') . " " . tep_draw_input_field($v['punique']."_".'wprice_inc', $v['wprice_inc'], 'size=6 maxlength=8') . '</td>' . "\n" .

                                          '    <td class="smallText">' . '<button edit="' . $v['pid'] . '" class="spbutton"><span class="smallText">' . TEXT_UPDATE . '</span></button>' . '</td>' . "\n" .
                                          '  </tr>' . "\n" .
                                          '  <tr>' . "\n" .
                                          '    <td class="smallText" colspan="2"></td>' . "\n" .
                                          '  </tr>' . "\n";
          }
      }
      $entry = array();
      $entry['pricelist'] = $tbody_str;
      if (sizeof($pv_array)>0) {
        ksort($pv_array);
        array_unshift($pv_array, array('id' => '', 'text' => "Please Select"));
        $entry['filter'] = ' Filter ' . tep_draw_pull_down_menu('pvid', array_values($pv_array), '', '');
      }
      echo json_encode($entry);
    }
    elseif (isset($HTTP_POST_VARS['pid'])) {
      if (isset($HTTP_POST_VARS['price']) && isset($HTTP_POST_VARS['mprice']) && isset($HTTP_POST_VARS['bprice']) && isset($HTTP_POST_VARS['wprice'])) {
        //$products_array = tep_db_fetch_array(tep_db_query("select products_tax_class_id from " . TABLE_PRODUCTS . " where products_id='" . (int)$HTTP_POST_VARS['pid'] . "'"));
              //$tax_ = tep_get_tax_rate($HTTP_POST_VARS['tax_id']);
              //$price = (float)$HTTP_POST_VARS['price'] / (($tax_ / 100) + 1);
              $price = (float)$HTTP_POST_VARS['price'];
              if ($price > 0 ) $price = tep_db_prepare_input($price); else $price = "null";

              //$mprice = (float)$HTTP_POST_VARS['mprice'] / (($tax_ / 100) + 1);
              $mprice = (float)$HTTP_POST_VARS['mprice'];
              if ($mprice > 0 ) $mprice = tep_db_prepare_input($mprice); else $mprice = "null";

              //$bprice = (float)$HTTP_POST_VARS['bprice'] / (($tax_ / 100) + 1);
              $bprice = (float)$HTTP_POST_VARS['bprice'];
              if ($bprice > 0 ) $bprice = tep_db_prepare_input($bprice); else $bprice = "null";

              //$wprice = (float)$HTTP_POST_VARS['wprice'] / (($tax_ / 100) + 1);
              $wprice = (float)$HTTP_POST_VARS['wprice'];
              if ($wprice > 0 ) $wprice = tep_db_prepare_input($wprice); else $wprice = "null";

              tep_db_query("update " . TABLE_PRODUCTS . " set products_market_price = " . tep_db_input($mprice) . ", products_price=" . tep_db_input($price) . ", products_buying_price=" . tep_db_input($bprice) . ", products_wholesale_price=" . tep_db_input($wprice) . " where products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'");
        $entry = array();
        //$entry['query'] = "update " . TABLE_PRODUCTS . " set products_market_price = " . tep_db_input($mprice) . ", products_price=" . tep_db_input($price) . ", products_buying_price=" . tep_db_input($bprice) . ", products_wholesale_price=" . tep_db_input($wprice) . " where products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'";
        $entry['success'] = 1;
        echo json_encode($entry);
      }
    }
  }
  elseif (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'getginfo') {
  }
  elseif (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'stockpage_stock_update') { //stock.php checkin/checkout
  	//$entry['pid'] = $HTTP_POST_VARS['update'] . " " . $HTTP_POST_VARS['pid'] . " " . $HTTP_POST_VARS['qty'] . " " . $HTTP_POST_VARS['checkoutreason'];
  	$qty_ = tep_db_fetch_array(tep_db_query("select products_quantity, p.products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p where p.products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'"));

  	if ($HTTP_POST_VARS['update'] == "checkinb") {
  		$updated_qty = (int)$qty_['products_quantity'] + (int)$HTTP_POST_VARS['qty'];
  		$comment = IKSH_COMMENTS_CHECKIN;
  		$history_qty = (int)$HTTP_POST_VARS['qty'];
  	}
  	elseif ($HTTP_POST_VARS['update'] == "checkoutb") {
  		$updated_qty = (int)$qty_['products_quantity'] - (int)$HTTP_POST_VARS['qty'];
  		$comment = IKSH_COMMENTS_CHECKOUT;
  		$history_qty = (int)(-$HTTP_POST_VARS['qty']);
  	}
  	if (!tep_not_null($qty_['products_bo']) && (int)$updated_qty < 0) {
      $updated_qty=0;
  	}
    tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$updated_qty . "' where products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'");

    if ((int)$qty_['parent_id']) {
     	pv_update_notbase_amount($HTTP_POST_VARS['pid'], $qty_['parent_id'], $updated_qty); //update based on 50g
    }

    $find_q = tep_db_query("select ik_stock_products_id, barcode from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'");
    if (tep_db_num_rows($find_q)>0) {
      $find = tep_db_fetch_array($find_q);
      $ik_pid = $find['ik_stock_products_id'];
    }
    else {
      $ik_pid = 0;
    }
    if (isset($HTTP_POST_VARS['checkoutreason']) && tep_not_null($HTTP_POST_VARS['checkoutreason'])) {
      $update_comment= (int)$HTTP_POST_VARS['checkoutreason'];
    }
    else {
      $update_comment= 'null';
    }

    $sql_data_array = array('ik_stock_products_id' => (int)$ik_pid,
                             'user_name' => tep_db_prepare_input($admin['username']),
                             'quantity' => $history_qty,
                             'latest_qty' => (int)$updated_qty,
                             'comments' => $comment,
                             'date_modified' => 'now()',
                             'products_id' => (int)$HTTP_POST_VARS['pid'],
                             'ik_stock_checkout_reasons_id' => $update_comment);
    tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

    //function to checkin/checkout to metro or bh 1-metro 14-braehead
    if ($update_comment ==14 || $update_comment == 16) {
      if (tep_not_null($find['barcode'])) {
        $shop[] = array('pid' => $HTTP_POST_VARS['pid'], 'psid' => $psid, 'barcode' => $find['barcode'], 'q_inout' => $history_qty, 'products_name' => $product_name['products_name'], 'attributes' => $display_att_, 'comment' => $comment);
        unset($find['barcode']); //destroy variable $find['barcode'] preventing the same barcode appear again
      }
    }

    	  if(sizeof($shop)>0) {
    	  	if ($update_comment == 14 || $update_comment == 16) {
    	  		$shopname = tep_db_fetch_array(tep_db_query("select checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where ik_stock_checkout_reasons_id = '" . (int)$update_comment . "' and language_id = '" . (int)$languages_id . "'"));
    	  	  if ($update_comment == 14) {
    	  	  	switch_shop_db(2);
    	  	  }
    	  	  elseif ($update_comment == 16) {
    	  	  	switch_shop_db(3);
    	  	  }

    	  	  foreach($shop as $v) {
    	  	  	if ($v['q_inout']==0) continue;
    	  	  	$shop_p_q = tep_db_query("select ik_stock_products_id, products_attributes, ori_products_id, products_stock_id from " . TABLE_IK_STOCK_PRODUCTS . " where barcode = '" . $v['barcode'] . "'");
              if (tep_db_num_rows($shop_p_q)>0) {
              	$shop_p = tep_db_fetch_array($shop_p_q);
              	if (tep_not_null($shop_p['products_attributes'])) {
              		$c_qty_q=tep_db_query("select products_stock_id, products_stock_attributes, products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_stock_id='" . (int)$shop_p['products_stock_id'] . "'");
    	  			    if (tep_db_num_rows($c_qty_q)>0) {
    	  				    $c_qty = tep_db_fetch_array($c_qty_q);
    	  				    //if ($v['comment'] == IKSH_COMMENTS_CHECKIN) $updated_qty = $c_qty['products_stock_quantity'] + (int)$v['q_inout'];
    	  				    //else $updated_qty = $c_qty['products_stock_quantity'] - (int)$v['q_inout'];
    	  				    $updated_qty = $c_qty['products_stock_quantity'] + (-$v['q_inout']);
   	                tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = '" . (int)$updated_qty . "' where products_stock_id = '" . (int)$shop_p['products_stock_id'] . "'");
   	                updatesummaryQty($shop_p['ori_products_id']);
    	  				  }
              	}
              	else { //no att
              	  $qty_q = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$shop_p['ori_products_id'] . "'");
                  if (tep_db_num_rows($qty_q)==1) {
                    $qty_ = tep_db_fetch_array($qty_q);
                    //if ($v['comment'] == IKSH_COMMENTS_CHECKIN) $updated_qty = $qty_['products_quantity'] + (int)$v['q_inout'];
                    //else $updated_qty = $qty_['products_quantity'] - (int)$v['q_inout'];
    	  				    $updated_qty = $qty_['products_quantity'] + (-$v['q_inout']);
       	       		  tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$updated_qty . "' where products_id = '" . (int)$shop_p['ori_products_id'] . "'");
                  }
              	}

              	//$product_name = tep_db_fetch_array(tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$shop_p['ori_products_id'] . "'"));
              	//if (tep_not_null($shop_p['products_attributes'])) {
              		//$display_att_= display_att($shop_p['products_attributes']);
              	//}

    	  		    //insert TABLE_IK_STOCK_PRODUCTS_HISTORY
              	  $update_comment = 1;
              	  //if there is no ik_stock_products_id
                  $sql_data_array = array('ik_stock_products_id' => (int)$shop_p['ik_stock_products_id'],
                                          'user_name' => tep_db_prepare_input($admin['username']),
                                          'quantity' => (int)(-$v['q_inout']),
                                          'latest_qty' => (int)$updated_qty,
                                          'comments' => ((-$v['q_inout'])>0) ? IKSH_COMMENTS_CHECKIN : IKSH_COMMENTS_CHECKOUT,
                                          'date_modified' => 'now()',
                                          'products_id' => (int)$shop_p['ori_products_id'],
                                          'products_attributes' => $shop_p['products_attributes'],
                                          'ik_stock_checkout_reasons_id' => $update_comment);
                  tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);
              }
              else {
              	//no barcode found on shop, failed msg
              	$entry['warning'] = 'no barcode found in shop ' . $shopname['checkout_reasons_name'];
              }
    	  	  }
    	  	  switch_shop_db(); //switch back
    	    }
    	  }
    	  elseif ($update_comment == 14 || $update_comment == 16) {
    	  	$shopname = tep_db_fetch_array(tep_db_query("select checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where ik_stock_checkout_reasons_id = '" . (int)$update_comment . "' and language_id = '" . (int)$languages_id . "'"));
    	  	$entry['warning'] = 'Barcode not available yet ' . $shopname['checkout_reasons_name'];
    	  }

    $entry['update'] = $HTTP_POST_VARS['update'] . " " . $HTTP_POST_VARS['pid'] . " " . $HTTP_POST_VARS['qty'] . " " . $HTTP_POST_VARS['checkoutreason'];
  	echo json_encode($entry);
  }









  tep_session_close();
//require(DIR_WS_INCLUDES . 'application_bottom.php');
?>