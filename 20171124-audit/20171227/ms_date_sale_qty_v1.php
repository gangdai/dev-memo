<?php
  /*
    20171216
    /localhost/2017/admin-cart/ms_date_sale_qty.php

    //OSCLogger::writeLog("07958256524", OSCLogger::DEBUG);
  */
exit;
  set_time_limit(200);
  ini_set('memory_limit','256M');

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
//header("Content-type: text/plain");
//header("Content-Disposition: attachment; filename=date.csv");
    //$onlineshop = " cash_carry !=0 and orders_ref_1 >0 ";
    //$date_range = "date_purchased >= '2015-04-01' and date_purchased < '2016-04-07'";
//$specials_string = "name, cat, att, qty" . "\r\n";

//checkout
    $date1 = "2015-07-01";
    $date2 = "2016-06-30";
    $hairextensions = [1,163,62,168];
    $attarray = []; //e.g. $attr["5-38,8-62"] = 'Length(inch)=22|Strands Unit=22" 25 Strands';
    $pvarray = [];

    $checkout_reasons_array = array();
    $checkout_reasons_query = tep_db_query("select ik_stock_checkout_reasons_id, checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where language_id = '" . (int)$languages_id . "' order by ik_stock_checkout_reasons_id");
    while ($checkout_reason = tep_db_fetch_array($checkout_reasons_query)) {
        $checkout_reasons_array[$checkout_reason['ik_stock_checkout_reasons_id']] = $checkout_reason['checkout_reasons_name'];
    }

    $stock_sku_a = [];
    $stockupdate_query = tep_db_query("select ish.ik_stock_products_history_id, ish.ik_stock_products_id, ish.user_name, ish.previous_qty, ish.quantity, ish.latest_qty, ish.comments, ish.date_modified, ish.products_id, ish.products_attributes, ish.ik_stock_checkout_reasons_id, p.parent_id, p.manufacturers_id, pd.products_name, p2c.categories_id, cd.categories_name, m.manufacturers_name from ik_stock_products_history ish left join products p on ish.products_id=p.products_id left join products_description pd on p.products_id=pd.products_id left join products_to_categories p2c on p.products_id=p2c.products_id left join categories_description cd on p2c.categories_id=cd.categories_id left join manufacturers m on p.manufacturers_id=m.manufacturers_id where ish.quantity!= 0 and ish.date_modified between '" . $date1 . " 00:00:00' and '" . $date2 . " 23:59:59' and ish.comments = 'out' and pd.language_id=1 and cd.language_id=1 " . " order by ish.ik_stock_checkout_reasons_id, user_name, concat(ish.products_id, ish.products_attributes), ish.comments");
    while($stockupdate = tep_db_fetch_array($stockupdate_query)) {
        if ($stockupdate['quantity'] ==0 ) continue;
        if (empty($stockupdate['products_name'])) continue;
        if (tep_not_null($stockupdate['products_attributes']) && $stockupdate['products_attributes'] != '') {
            if (tep_not_null($stockupdate['parent_id'])) {
                continue;
            }
            $stock_sku = $stockupdate['products_id'] . "|" . $stockupdate['products_attributes'];

            if (!array_key_exists($stockupdate['products_attributes'], $attarray)) {
                $attarray[$stockupdate['products_attributes']] = display_att($stockupdate['products_attributes']);
                $attarray[$stockupdate['products_attributes']] = preg_replace("/,/", " ", $attarray[$stockupdate['products_attributes']]);
            }
            if (!isset($stock_sku_a[$stock_sku])) {
                $stock_sku_a[$stock_sku] = ["qty" => abs($stockupdate['quantity']), "pname" => $stockupdate['products_name'], "patt" => $attarray[$stockupdate['products_attributes']], "mid" => $stockupdate['manufacturers_id'], "mname"=>$stockupdate['manufacturers_name'], "cid" => $stockupdate['categories_id'], "cname" => $stockupdate['categories_name']];
            } else {
                $stock_sku_a[$stock_sku]["qty"] += abs($stockupdate['quantity']);
            }
        } elseif (tep_not_null($stockupdate['parent_id'])) {
            $stock_sku = $stockupdate['products_id'];
            if (!array_key_exists($stockupdate['parent_id'] . "-" . $stockupdate['products_id'], $pvarray)) {
                $pvarray[$stockupdate['parent_id'] . "-" . $stockupdate['products_id']] = pv_slave_value($stockupdate['parent_id'], $stockupdate['products_id']);
                $pvarray[$stockupdate['parent_id'] . "-" . $stockupdate['products_id']] = preg_replace("/,/", " ", $pvarray[$stockupdate['parent_id'] . "-" . $stockupdate['products_id']]);
            }
            if (!isset($stock_sku_a[$stock_sku])) {
                $stock_sku_a[$stock_sku] = ["qty" => abs($stockupdate['quantity']), "pname" => $stockupdate['products_name'], "pv" => $pvarray[$stockupdate['parent_id'] . "-" . $stockupdate['products_id']], "mid" => $stockupdate['manufacturers_id'], "mname"=>$stockupdate['manufacturers_name'], "cid" => $stockupdate['categories_id'], "cname" => $stockupdate['categories_name']];
            } else {
                $stock_sku_a[$stock_sku]["qty"] += abs($stockupdate['quantity']);
            }
        } else {
            $stock_sku = $stockupdate['products_id'];
            if (!isset($stock_sku_a[$stock_sku])) {
                $stock_sku_a[$stock_sku] = ["qty" => abs($stockupdate['quantity']), "pname" => $stockupdate['products_name'], "mid" => $stockupdate['manufacturers_id'], "mname"=>$stockupdate['manufacturers_name'], "cid" => $stockupdate['categories_id'], "cname" => $stockupdate['categories_name']];
            } else {
                $stock_sku_a[$stock_sku]["qty"] += abs($stockupdate['quantity']);
            }
        }
    }
    $haircare_checkout = [];
    $hair_checkout = [];
    foreach ($stock_sku_a as $k => $v) {
        if (in_array($v['mid'], $hairextensions)) {
            if (!isset($hair_checkout[$v['cid']][$k])) {
                if (isset($v["pv"])) {
                    $patt_pv = $v["pv"];
                } elseif (isset($v["patt"])) {
                    $patt_pv = $v["patt"];
                } else {
                    $patt_pv = "";
                }
                $hair_checkout[$v['cid']][$k] = ["qty" => (int)$v['qty'], "pname" => $v["pname"], "patt_pv" => $patt_pv, "mid" =>$v['mid'], "cname" => $v["cname"]];
            }
            else {
                $hair_checkout[$v['cid']][$k]["qty"] += (int)$v['qty'];
            }
        } else {
            if (!isset($haircare_checkout[$v['mid']][$k])) {
                if (isset($v["pv"])) {
                    $patt_pv = $v["pv"];
                } elseif (isset($v["patt"])) {
                    $patt_pv = $v["patt"];
                } else {
                    $patt_pv = "";
                }
                $haircare_checkout[$v['mid']][$k] = ["qty" => (int)$v['qty'], "pname" => $v["pname"], "patt_pv" => $patt_pv, "mname" => $v["mname"]];
            } else {
                $haircare_checkout[$v['mid']][$k]["qty"] += (int)$v['qty'];
            }
        }
    }

    foreach ($hair_checkout as $k1 => $v1) {
        $cdesc = tep_db_fetch_array(tep_db_query("SELECT categories_name FROM categories_description WHERE categories_id = " . (int)$k1 . " and language_id=1"));
        $specials_string = $cdesc['categories_name'] . ", name, att, qty" . "\r\n";
        $cdesc['categories_name'] = preg_replace("/\//", "", $cdesc['categories_name']);
        foreach ($v1 as $k2 => $v2) {
            $specials_string .= " ," . $v2['pname'] . "," . $v2['patt_pv'] . "," . $v2['qty'] . "\r\n";
        }
        write_to_file($specials_string, $k1 . "_" . $cdesc['categories_name'] . ".csv");
    }
    foreach ($haircare_checkout as $k3 => $v3) {
        $mdesc = tep_db_fetch_array(tep_db_query("SELECT manufacturers_id, manufacturers_name FROM manufacturers where manufacturers_id = " . (int)$k3 . ""));
        if (!tep_not_null($mdesc['manufacturers_name'])) $mdesc['manufacturers_name'] = "unbranded";
        $specials_string = $mdesc['manufacturers_name'] . ", name, att, qty, sale" . "\r\n";
        $mdesc['manufacturers_name'] = preg_replace("/\//", "", $mdesc['manufacturers_name']);
        foreach ($v3 as $k4 => $v4) {
            $specials_string .= " ," . $v4['pname'] . "," . $v4['patt_pv'] . "," . $v4['qty'] . "\r\n";
        }
        write_to_file($specials_string, $k3 . "_" . $mdesc['manufacturers_name'] . ".csv");
    }
//OSCLogger::writeLog($checkout_reasons_array, OSCLogger::DEBUG);
exit;











//sale
    $display_only_instock = 1;

    $ski_pid = [288];
    //$bonded_legacy_array = ["25 Strands", "50 Strands", "75 Strands", "100 Strands", "125 Strands", "150 Strands", "175 Strands", "200 Strands"];
    $cash_carry_cond = "";
    
    $raw1="select op.orders_products_id, op.orders_id, op.products_id, op.products_model, op.products_name, op.final_price, op.products_tax, op.products_quantity, op.products_stock_attributes, opa.products_options, opa.products_options_values, opv.group_title, opv.value_title, p2c.categories_id, p.manufacturers_id from " . TABLE_ORDERS_PRODUCTS . " op left join " . TABLE_ORDERS . " o on op.orders_id=o.orders_id left join " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa on op.orders_products_id = opa.orders_products_id left join " . TABLE_OSC_ORDER_PRODUCTS_VARIANTS . " opv on op.orders_products_id=opv.orders_products_id left join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on op.products_id=p2c.products_id left join " . TABLE_PRODUCTS . " p on op.products_id = p.products_id where  o.date_purchased between '" . $date1 . " 00:00:00' and '" . $date2 . " 23:59:59'

    union all

    select op.orders_products_id, op.orders_id, op.products_id, op.products_model, op.products_name, op.final_price, op.products_tax, op.products_quantity, op.products_stock_attributes, NULL as products_options, NULL as products_options_values, opv.group_title, opv.value_title, p2c.categories_id, p.manufacturers_id from " . TABLE_TRADE_ORDERS_PRODUCTS . " op left join " . TABLE_TRADE_ORDERS . " o on op.orders_id=o.orders_id left join " . TABLE_TRADE_OSC_ORDER_PRODUCTS_VARIANTS . " opv on op.orders_products_id=opv.orders_products_id left join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on op.products_id=p2c.products_id left join " . TABLE_PRODUCTS . " p on op.products_id = p.products_id where o.date_purchased between '" . $date1 . " 00:00:00' and '" . $date2 . " 23:59:59'";

/*
    $raw1="select op.orders_products_id, op.orders_id, op.products_id, op.products_model, op.products_name, op.final_price, op.products_tax, op.products_quantity, op.products_stock_attributes, opa.products_options, opa.products_options_values, opv.group_title, opv.value_title, p2c.categories_id, p.manufacturers_id from " . TABLE_ORDERS_PRODUCTS . " op left join " . TABLE_ORDERS . " o on op.orders_id=o.orders_id left join " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa on op.orders_products_id = opa.orders_products_id left join " . TABLE_OSC_ORDER_PRODUCTS_VARIANTS . " opv on op.orders_products_id=opv.orders_products_id left join " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c on op.products_id=p2c.products_id left join " . TABLE_PRODUCTS . " p on op.products_id = p.products_id where  p.manufacturers_id = 1 and op.products_stock_attributes like '%,%' and o.orders_id = 211719";
//echo display_att("5-38,8-62");
//exit;
*/


    $sales_products_query = tep_db_query($raw1);
    $opid = [];

    while ($sales_products = tep_db_fetch_array($sales_products_query)) {

        if (in_array($sales_products['products_id'], $ski_pid) && (float)$sales_products['final_price'] <1) $sales_products['products_name'] = "Sample " . $sales_products['products_name'];
        if (!tep_not_null($sales_products['manufacturers_id'])) $sales_products['manufacturers_id'] = 0;
        //$psa = ""; $patt = ""; $pv = "";
        $sales_products['products_name'] = preg_replace("/,/", " ", $sales_products['products_name']);
        //$sales_products['products_options_values'] = preg_replace("/,/", " ", $sales_products['products_options_values']);
        $sales_products['value_title'] = preg_replace("/,/", " ", $sales_products['value_title']);
//products_options_values
//"25 Strands", "50 Strands", "75 Strands", "100 Strands", "125 Strands", "150 Strands", "175 Strands", "200 Strands"

//if (preg_match("/Strands/", $sales_products['products_options_values'])) {
//}
        if (!isset($opid[$sales_products['orders_products_id']])) {
            if (tep_not_null($sales_products['products_stock_attributes'])) {
                //$psa = $sales_products['products_stock_attributes'];
                //$patt = $sales_products['products_options_values'];
                if (!array_key_exists($sales_products['products_stock_attributes'], $attarray)) {
                    $attarray[$sales_products['products_stock_attributes']] = display_att($sales_products['products_stock_attributes']);
                    $attarray[$sales_products['products_stock_attributes']] = preg_replace("/,/", " ", $attarray[$sales_products['products_stock_attributes']]);

                    $bonding_factor = 1; //of 25s
                    if (preg_match("/8-65/", $sales_products['products_stock_attributes']) ||
                        preg_match("/8-57/", $sales_products['products_stock_attributes']) ||
                        preg_match("/8-454/", $sales_products['products_stock_attributes'])) {
                        //100
                        $bonding_factor=4;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/100 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-64/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-56/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-453/", $sales_products['products_stock_attributes'])) {
                        //75
                        $bonding_factor=3;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/75 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-63/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-55/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-452/", $sales_products['products_stock_attributes'])) {
                        //50
                        $bonding_factor=2;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/50 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-58/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-66/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-455/", $sales_products['products_stock_attributes'])) {
                        //125
                        $bonding_factor=5;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/125 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-59/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-67/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-456/", $sales_products['products_stock_attributes'])) {
                        //150
                        $bonding_factor=6;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/150 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-60/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-68/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-457/", $sales_products['products_stock_attributes'])) {
                        //175
                        $bonding_factor=7;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/175 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-61/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-69/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-458/", $sales_products['products_stock_attributes'])) {
                        //200
                        $bonding_factor=8;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/200 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                    } elseif (preg_match("/8-54/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-62/", $sales_products['products_stock_attributes']) ||
                              preg_match("/8-451/", $sales_products['products_stock_attributes'])) {
                        //200
                        $bonding_factor=1;
                        $attarray[$sales_products['products_stock_attributes']] = preg_replace("/25 Strands/", "", $attarray[$sales_products['products_stock_attributes']]);
                        
                    }
                }
                $opid[$sales_products['orders_products_id']] = ["pid" => $sales_products['products_id'],
                                                                "sku" => $sales_products['products_id'] . "|" . $sales_products['products_stock_attributes'],
                                                                "psa" => $sales_products['products_stock_attributes'],
                                                                "patt" => $attarray[$sales_products['products_stock_attributes']],
                                                                "pname" => $sales_products['products_name'],
                                                                "price" => (float)$sales_products['final_price'],
                                                                "tax" => $sales_products['products_tax'],
                                                                'qty'  => (int)$sales_products['products_quantity'],
                                                                'bonding_factor' => $bonding_factor,
                                                                'mid'  => $sales_products['manufacturers_id'],
                                                                'cid' => $sales_products['categories_id']];
            } elseif (tep_not_null($sales_products['value_title'])) {
                $opid[$sales_products['orders_products_id']] = ["pid" => $sales_products['products_id'],
                                                                "sku" => $sales_products['products_id'],
                                                                "pv" => $sales_products['value_title'],
                                                                "pname" => $sales_products['products_name'],
                                                                "price" => (float)$sales_products['final_price'],
                                                                "tax" => $sales_products['products_tax'],
                                                                'qty'  => $sales_products['products_quantity'],
                                                                'mid'  => $sales_products['manufacturers_id'],
                                                                'cid' => $sales_products['categories_id']];
            } else {
                $opid[$sales_products['orders_products_id']] = ["pid" => $sales_products['products_id'],
                                                                "sku" => $sales_products['products_id'],
                                                                "pname" => $sales_products['products_name'],
                                                                "price" => (float)$sales_products['final_price'],
                                                                "tax" => $sales_products['products_tax'],
                                                                'qty'  => $sales_products['products_quantity'],
                                                                'mid'  => $sales_products['manufacturers_id'],
                                                                'cid' => $sales_products['categories_id']];
            }
        } else {
            /*
            if (tep_not_null($sales_products['products_stock_attributes'])) {
                $opid[$sales_products['orders_products_id']]["patt"] .= " " . $sales_products['products_options_values'];
            } elseif (tep_not_null($sales_products['value_title'])) {
                $opid[$sales_products['orders_products_id']]["pv"] .= " " . $sales_products['value_title'];
            }*/
            if (tep_not_null($sales_products['value_title'])) {
                $opid[$sales_products['orders_products_id']]["pv"] .= " " . $sales_products['value_title'];
            }
        }
    }

    $haircare = [];
    $hair = [];
    foreach ($opid as $k => $v) {
        if (in_array($v['mid'], $hairextensions)) {
            if (!isset($hair[$v['cid']][$v['sku']])) {
                if (isset($v["pv"])) {
                    $patt_pv = $v["pv"];
                } elseif (isset($v["patt"])) {
                    $patt_pv = $v["patt"];
                } else {
                    $patt_pv = "";
                }
                $hair[$v['cid']][$v['sku']] = ["qty" => (int)$v['qty'] * (isset($v['bonding_factor'])?$v['bonding_factor']:1), "sale" => (float)$v['price'] * (int)$v['qty'], "tax" => $v["tax"], "pname" => $v["pname"], "patt_pv" => $patt_pv, "mid" =>$v['mid']];
            }
            else {
                $hair[$v['cid']][$v['sku']]["qty"] += (int)$v['qty'];
                $hair[$v['cid']][$v['sku']]["sale"] += (int)$v['qty'] * (float)$v['price'];
            }
        } else {
            if (!isset($haircare[$v['mid']][$v['sku']])) {
                if (isset($v["pv"])) {
                    $patt_pv = $v["pv"];
                } elseif (isset($v["patt"])) {
                    $patt_pv = $v["patt"];
                } else {
                    $patt_pv = "";
                }
                $haircare[$v['mid']][$v['sku']] = ["qty" => (int)$v['qty'], "sale" => (float)$v['price'] * (int)$v['qty'], "tax" => $v["tax"], "pname" => $v["pname"], "patt_pv" => $patt_pv];
            } else {
                $haircare[$v['mid']][$v['sku']]["qty"] += (int)$v['qty'];
                $haircare[$v['mid']][$v['sku']]["sale"] += (int)$v['qty'] * (float)$v['price'];
            }
        }
    }
    
//OSCLogger::writeLog($hair, OSCLogger::DEBUG);
//OSCLogger::writeLog("======================", OSCLogger::DEBUG);
//OSCLogger::writeLog($haircare, OSCLogger::DEBUG);


    foreach ($hair as $k1 => $v1) {
        $cdesc = tep_db_fetch_array(tep_db_query("SELECT categories_name FROM categories_description WHERE categories_id = " . (int)$k1 . " and language_id=1"));
        $specials_string = $cdesc['categories_name'] . ", name, att, qty, sale" . "\r\n";
        $cdesc['categories_name'] = preg_replace("/\//", "", $cdesc['categories_name']);
        foreach ($v1 as $k2 => $v2) {
            $specials_string .= " ," . $v2['pname'] . "," . $v2['patt_pv'] . "," . $v2['qty'] . "," . $v2['sale'] . "\r\n";
        }
        write_to_file($specials_string, $k1 . "_" . $cdesc['categories_name'] . ".csv");
    }
    foreach ($haircare as $k3 => $v3) {
        $mdesc = tep_db_fetch_array(tep_db_query("SELECT manufacturers_id, manufacturers_name FROM manufacturers where manufacturers_id = " . (int)$k3 . ""));
        if (!tep_not_null($mdesc['manufacturers_name'])) $mdesc['manufacturers_name'] = "unbranded";
        $specials_string = $mdesc['manufacturers_name'] . ", name, att, qty, sale" . "\r\n";
        $mdesc['manufacturers_name'] = preg_replace("/\//", "", $mdesc['manufacturers_name']);
        foreach ($v3 as $k4 => $v4) {
            $specials_string .= " ," . $v4['pname'] . "," . $v4['patt_pv'] . "," . $v4['qty'] . "," . $v4['sale'] . "\r\n";
        }
        write_to_file($specials_string, $k3 . "_" . $mdesc['manufacturers_name'] . ".csv");
    }

    
    
exit;


?>