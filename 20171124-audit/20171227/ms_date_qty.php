<?php
  /*
    20171216
    /localhost/2017/admin-cart/ms_date_qty.php

    //OSCLogger::writeLog("07958256524", OSCLogger::DEBUG);
  */

    set_time_limit(200);

    require('includes/application_top.php');
    function write_to_file($output_string, $filename) {
			if (file_exists(DIR_FS_CACHE . "sale_stock/" . $filename)) {
				@unlink(DIR_FS_CACHE . "sale_stock/" . $filename);
			}

			$myFile = DIR_FS_CACHE . "sale_stock/" . $filename;
			$fh = fopen($myFile, 'w') or die("can't open file");

			fwrite($fh, $output_string);
			fclose($fh);
    }

    function display_att_price($pid, $rawstr) {
  	    if (!tep_not_null($rawstr)) return null;
  	    global $languages_id;
    	//display the actual opt_val
    	$opt_val = explode(',', $rawstr);
    	$attprice=0;
    	foreach($opt_val as $val) {
       	    $opt_optval=explode('-', $val,2);
            $price_single_att = tep_db_fetch_array(tep_db_query("select options_values_price from products_attributes where products_id = '" . (int)$pid . "' and options_id = '" . (int)$opt_optval[0] . "' and options_values_id = '" . (int)$opt_optval[1] . "'"));
            if ((float)$price_single_att['options_values_price'] >0) $attprice += (float)$price_single_att['options_values_price'];
        }
        return $attprice;
    }

//header("Content-type: text/plain");
//header("Content-Disposition: attachment; filename=date.csv");
    //$onlineshop = " cash_carry !=0 and orders_ref_1 >0 ";
    //$date_range = "date_purchased >= '2015-04-01' and date_purchased < '2016-04-07'";
//$specials_string = "name, cat, att, qty" . "\r\n";
    $gethair = 1;

    if (!$gethair)
        $specials_string = "brand, name, cat, att, cost, price, qty" . "\r\n";
    else
        $specials_string = "categories, name, cat, att, cost, price, qty" . "\r\n";
    $display_only_instock = 1;

    $raw1 = "SELECT manufacturers_id,manufacturers_name FROM manufacturers union all select NULL as manufacturers_id, NULL as manufacturers_name";
    $q1 = tep_db_query($raw1);
    while ($qq1 = tep_db_fetch_array($q1)) {
        //if ((int)$qq1['manufacturers_id'] != 1) continue;
        //$raw2 = "select p.products_id, p.products_quantity, p.products_model, p.products_tax_class_id, p.parent_id, p.has_children, p.products_status, pd.products_name, ps.products_stock_id, ps.products_stock_attributes, ps.products_stock_quantity from products p left join products_stock ps on p.products_id=ps.products_id, products_description pd where p.products_status=1 and p.products_id=pd.products_id and pd.language_id = '1' and p.manufacturers_id = '" . $qq1['manufacturers_id'] . "' order by p.products_id, p.products_model, pd.products_name, ps.products_stock_attributes";

        
        if ($qq1['manufacturers_id'] == 1 || $qq1['manufacturers_id'] == 62 || $qq1['manufacturers_id'] == 163 || $qq1['manufacturers_id']==168) {
            if ($gethair<1) continue;
        //if ($qq1['manufacturers_id'] == 62) {    
            $raw2 = "select p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_buying_price, p.products_tax_class_id, p.parent_id, p.has_children, p.products_status, pd.products_name, ps.products_stock_id, ps.products_stock_attributes, ps.products_stock_quantity, p2c.categories_id, cd.categories_name, pb.bundle_id from products p left join products_stock ps on p.products_id=ps.products_id left join products_to_categories p2c on p.products_id=p2c.products_id left join products_bundles pb on p.products_id=pb.bundle_id left join categories_description cd on p2c.categories_id=cd.categories_id, products_description pd where p.products_status=1 and p.products_id=pd.products_id and pd.language_id = '1' and p.manufacturers_id = '" . $qq1['manufacturers_id'] . "' order by p.products_id, p2c.categories_id, pd.products_name, ps.products_stock_attributes";
            
            $brand_name = preg_replace("/\//", "", $qq1['manufacturers_name']);
            $specials_string .= $brand_name . ",,,,," . "\r\n";

            $temp_sku= [];
            $products_query = tep_db_query($raw2);
            $temp_cat = [];
            while($products_array = tep_db_fetch_array($products_query)) {

                //sku
                if (tep_not_null($products_array['products_stock_attributes']) && !tep_not_null($products_array['parent_id'])) {
                    //attribute
                    $t_temp_sku = $products_array['products_id'] . "|" . $products_array['products_stock_attributes'];
                    $t_products_prices = $products_array['products_price'] + display_att_price($products_array['products_id'], $products_array['products_stock_attributes']);
                } else {
                    $t_temp_sku = $products_array['products_id'];
                    $t_products_prices = $products_array['products_price'];
                }

                if (!in_array($t_temp_sku, $temp_sku)) $temp_sku[]=$t_temp_sku;
                else continue;

                $products_array['categories_name'] = preg_replace("/\//", "", $products_array['categories_name']);
                $temp_cat[$products_array['categories_name']][] = ["products_id" => $products_array['products_id'], "products_quantity" => $products_array['products_quantity'], "products_model" => $products_array['products_model'], "products_price" => $t_products_prices, "products_buying_price" => $products_array['products_buying_price'], "products_tax_class_id" => $products_array['products_tax_class_id'], "parent_id" => $products_array['parent_id'], "has_children" => $products_array['has_children'], "products_status" => $products_array['products_status'], "products_name" => $products_array['products_name'], "products_stock_id" => $products_array['products_stock_id'], "products_stock_attributes" => $products_array['products_stock_attributes'], "products_stock_quantity" => $products_array['products_stock_quantity'], "categories_id" => $products_array['categories_id'], "categories_name" => $products_array['categories_name'], "bundle_id" => $products_array['bundle_id']];
            }
//OSCLogger::writeLog($temp_cat, OSCLogger::DEBUG);
//exit;
            foreach ($temp_cat as $k => $v) { //categories
                $print_pv_array = [];
                $specials_string .= $k . ",, , , , " . "\r\n";
                foreach ($v as $v1) { //products
                    $att_str = "";
                    if (tep_not_null($v1['products_stock_attributes'])) {
                        $att_str = display_att($v1['products_stock_attributes']);
                        $product_qty = $v1['products_stock_quantity'];
                        $display_name = $v1['products_name'];
                        $display_model = $v1['products_model'];
                    }
                    else {
                        $product_qty = $v1['products_quantity'];
                        $display_name = $v1['products_name'];
                        $display_model = $v1['products_model'];
                        if ((int)$v1['parent_id']>0) {
                              //201512 check if it's 100g/150g
                            if (pv_is_notbase_amount($v1['products_id'], $v1['parent_id'])) {
                            continue;
                            }
                          if ($temp_parent_id != $v1['parent_id']) {
                            $display_parent_info = pv_slave_name($v1['parent_id']);
                            $temp_parent_id = $v1['parent_id'];
                          }
                          $display_name = $display_parent_info['products_name'];
                          $display_model = $display_parent_info['products_model'];
          
                          $att_str = pv_slave_value($v1['parent_id'], $v1['products_id']);
                        }
                        elseif (tep_not_null($v1['has_children'])) {
                          continue;
                        }
                    }

                    $display_name = preg_replace("/,/", "", $display_name);
                    if ($display_only_instock) {
                        //ignore o/s item
                        if ((int)$product_qty <= 0) {
                            //$product_qty = abs($product_qty);
                            continue;
                        }
                    } else {
                        //ignore instock item
                        if ((int)$product_qty > 0) {
                            continue;
                        }
                    }
                    if (tep_not_null($v1['bundle_id'])) {
                        continue;
                        $product_qty = "n/a";
                    }

                    $product_unique = $v1['products_id'] . "|" . $v1['products_stock_id'];
                    $checklogic = false;

                    //get barcode
                    if (tep_not_null($v1['products_stock_id'])) {
                        $barcode_array_ = tep_db_fetch_array(tep_db_query("select barcode from ik_stock_products where ori_products_id='" . (int)$v1['products_id'] . "' and products_stock_id='" . (int)$v1['products_stock_id'] . "'"));
                    }
                    else {
                        $barcode_array_ = tep_db_fetch_array(tep_db_query("select barcode from ik_stock_products where ori_products_id='" . (int)$v1['products_id'] . "'"));
                    }
                    $barcode = $barcode_array_['barcode'];

                    #undelivered suppliers
                    $suppliers_undelivered_qty = 0;
                    $suppliers_undelivered_q1 = tep_db_query("select order_quantity, delivered_quantity from " . TABLE_SUPPLIERS_ORDERS_PRODUCTS . " where products_id = " . (int)$v1['products_id'] . " and suppliers_orders_products_status is null");

                    while($suppliers_undelivered = tep_db_fetch_array($suppliers_undelivered_q1)) {
                        $suppliers_undelivered_qty += ($suppliers_undelivered['order_quantity'] - $suppliers_undelivered['delivered_quantity']);
                    }

                    if ((int)$v1['parent_id']>0) {
                        $print_pv_array[$display_name . "||" . $att_str . "||" . $v1['products_id']] = array('pid' => $v1['products_id'], 'pstatus' => $v1['products_status'], 'pname' => $display_name, 'products_price' => $v1['products_price'], 'products_buying_price' => $v1['products_buying_price'], 'model' => $display_model, 'barcode' => $barcode, 'qty' => $product_qty, 'supplier' => $suppliers_undelivered_qty, 'unique' => $product_unique, 'checklogic' => $checklogic);
                        continue;
                    }
                    $specials_string .= "," . $display_name . "," . "," . $att_str . "," . $v1['products_buying_price'] . "," . $v1['products_price'] . "," . $product_qty . "\r\n";
                }
    		    ksort($print_pv_array);
    		    foreach ($print_pv_array as $k2 => $v3) {
    		  	    $display_pv = explode("||", $k2);
                    $specials_string .= "," . $v3['pname'] . "," . "," . $display_pv[1] . "," . $v3['products_buying_price'] . "," . $v3['products_price'] . "," . $v3['qty'] . "\r\n";
    		    }

                //$cached_file = $brand_name . "_" . $k . ".csv";
                //write_to_file($specials_string, $cached_file);
            }
            continue;
        } elseif (tep_not_null($qq1['manufacturers_id'])) {
            if ($gethair>0) continue;
            $raw2 = "select p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_buying_price, p.products_tax_class_id, p.parent_id, p.has_children, p.products_status, pd.products_name, ps.products_stock_id, ps.products_stock_attributes, ps.products_stock_quantity, pb.bundle_id from products p left join products_stock ps on p.products_id=ps.products_id left join products_bundles pb on p.products_id=pb.bundle_id, products_description pd where p.products_status=1 and p.products_id=pd.products_id and pd.language_id = '1' and p.manufacturers_id = '" . $qq1['manufacturers_id'] . "' order by p.products_id, p.products_model, pd.products_name, ps.products_stock_attributes";
        } else {
            if ($gethair>0) continue;
            $raw2 = "select p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_buying_price, p.products_tax_class_id, p.parent_id, p.has_children, p.products_status, pd.products_name, ps.products_stock_id, ps.products_stock_attributes, ps.products_stock_quantity, pb.bundle_id from products p left join products_stock ps on p.products_id=ps.products_id left join products_bundles pb on p.products_id=pb.bundle_id, products_description pd where p.products_status=1 and p.products_id=pd.products_id and pd.language_id = '1' and (p.manufacturers_id is null or p.manufacturers_id <1) order by p.products_id, p.products_model, pd.products_name, ps.products_stock_attributes";
        }

//$specials_string .= $brand_name . "\r\n";
        $products_query = tep_db_query($raw2);
        if (tep_db_num_rows($products_query)) {
            if (tep_not_null($qq1['manufacturers_name'])) {
                $brand_name = $qq1['manufacturers_name'];
            } else {
                $brand_name = "unbranded";
            }

            $specials_string .= $brand_name . ", , , , ," . "\r\n";
            $temp_sku = [];
            $print_pv_array = array();
    		while($products_array = tep_db_fetch_array($products_query)) {

                //sku
                if (tep_not_null($products_array['products_stock_attributes']) && !tep_not_null($products_array['parent_id'])) {
                    //attribute
                    $t_temp_sku = $products_array['products_id'] . "|" . $products_array['products_stock_attributes'];
                } else {
                    $t_temp_sku = $products_array['products_id'];
                }

                if (!in_array($t_temp_sku, $temp_sku)) $temp_sku[]=$t_temp_sku;
                else continue;


              //display attributes
	            //$att_str = tep_not_null($products_array['products_stock_attributes']) ? display_att($products_array['products_stock_attributes']) : "";
	            $att_str = "";
	            if (tep_not_null($products_array['products_stock_attributes'])) {
	            	$att_str = display_att($products_array['products_stock_attributes']);
	            	$product_qty = $products_array['products_stock_quantity'];
	          	    $display_name = $products_array['products_name'];
	          	    $display_model = $products_array['products_model'];
	            }
	            else {
	            	$product_qty = $products_array['products_quantity'];
	            	$display_name = $products_array['products_name'];
	          	    $display_model = $products_array['products_model'];
                    if ((int)$products_array['parent_id']>0) {
                        //201512 check if it's 100g/150g
                        if (pv_is_notbase_amount($products_array['products_id'], $products_array['parent_id'])) {
                            continue;
                        }
                      if ($temp_parent_id != $products_array['parent_id']) {
                        $display_parent_info = pv_slave_name($products_array['parent_id']);
                        $temp_parent_id = $products_array['parent_id'];
                      }
                      $display_name = $display_parent_info['products_name'];
                      $display_model = $display_parent_info['products_model'];

                      $att_str = pv_slave_value($products_array['parent_id'], $products_array['products_id']);
                    }
                    elseif (tep_not_null($products_array['has_children'])) {
                      continue;
                    }
	            }

                $display_name = preg_replace("/,/", "", $display_name);
                if ($display_only_instock) {
                    //ignore o/s item
                    if ((int)$product_qty <= 0) {
                        //$product_qty = abs($product_qty);
                        continue;
                    }
                } else {
                    //ignore instock item
                    if ((int)$product_qty > 0) {
                        continue;
                    }
                }
                if (tep_not_null($products_array['bundle_id'])) {
                    continue;
                    $product_qty = "n/a";
                }

	            $product_unique = $products_array['products_id'] . "|" . $products_array['products_stock_id'];
	            $checklogic = false;

	            //get barcode
	            if (tep_not_null($products_array['products_stock_id'])) {
	            	$barcode_array_ = tep_db_fetch_array(tep_db_query("select barcode from ik_stock_products where ori_products_id='" . (int)$products_array['products_id'] . "' and products_stock_id='" . (int)$products_array['products_stock_id'] . "'"));
	            }
	            else {
	            	$barcode_array_ = tep_db_fetch_array(tep_db_query("select barcode from ik_stock_products where ori_products_id='" . (int)$products_array['products_id'] . "'"));
	            }
	            $barcode = $barcode_array_['barcode'];

                #undelivered suppliers
                $suppliers_undelivered_qty = 0;
                $suppliers_undelivered_q1 = tep_db_query("select order_quantity, delivered_quantity from " . TABLE_SUPPLIERS_ORDERS_PRODUCTS . " where products_id = " . (int)$products_array['products_id'] . " and suppliers_orders_products_status is null");

                while($suppliers_undelivered = tep_db_fetch_array($suppliers_undelivered_q1)) {
                    $suppliers_undelivered_qty += ($suppliers_undelivered['order_quantity'] - $suppliers_undelivered['delivered_quantity']);
                }

                if ((int)$products_array['parent_id']>0) {
                    $print_pv_array[$display_name . "||" . $att_str . "||" . $products_array['products_id']] = array('pid' => $products_array['products_id'], 'pstatus' => $products_array['products_status'], 'pname' => $display_name, "products_price" => $products_array['products_price'], "products_buying_price" => $products_array['products_buying_price'], 'model' => $display_model, 'barcode' => $barcode, 'qty' => $product_qty, 'supplier' => $suppliers_undelivered_qty, 'unique' => $product_unique, 'checklogic' => $checklogic);
                    continue;
                }
                $specials_string .= "," . $display_name . "," . "," . $att_str . "," . $products_array['products_buying_price'] . "," . $products_array['products_price'] . "," . $product_qty . "\r\n";
    		}
    		    ksort($print_pv_array);
    		    foreach ($print_pv_array as $k => $v) {
    		  	    $display_pv = explode("||", $k);
                    $specials_string .= "," . $v['pname'] . "," . "," . $display_pv[1] . "," . $products_array['products_buying_price'] . "," . $products_array['products_price'] . "," . $v['qty'] . "\r\n";
    		    }
//$specials_string .= "\r\n";
            //$cached_file = $brand_name . ".csv";
	        //write_to_file($specials_string, $cached_file);
        }
    }

    if ($gethair>0) write_to_file($specials_string, "h_stockcount" . ".csv" );
    else write_to_file($specials_string, "hc_stockcount" . ".csv" );

//echo substr($specials_string, 0, -1);

exit;
    
 
?>