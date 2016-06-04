<?php
chdir("./../../..");
require('includes/application_top.php');
require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ORDERS);

  require(DIR_WS_CLASSES . 'currencies.php');
  $currencies = new currencies();

/*
  function get_attprice($rawstr,$pid) {
    $opt_val = explode(',', $rawstr);
    foreach($opt_val as $val) {
      $opt_optval=explode('-', $val,2);

	    $opt_q = tep_db_fetch_array(tep_db_query("select options_values_price from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . (int)$opt_optval[0] . "' and options_values_id = '" . (int)$opt_optval[1] . "' and products_id='" . (int)$pid . "'"));
	    if ($opt_q['options_values_price'] >0) {
	      return $opt_optval;
	    }
    }
    return null;
  }
*/
  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  $languages = tep_get_languages();
  if (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'pout') { //seach product by cat_id
  	if(isset($HTTP_POST_VARS['cid'])) {
      $entry = array();

      $products_q = tep_db_query("select pd.products_name, p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_tax_class_id, p.parent_id, p.has_children, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, s.customers_id, s.customers_groups_id from " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_status='1' and p.products_id=p2c.products_id and p2c.categories_id='" . (int)$HTTP_POST_VARS['cid'] . "' order by p.products_model");

      $products_array = array();
      $products_array[] = array('id' => 0, 'text' => 'Products..');
      $print_pv_array = array();
      while ($products = tep_db_fetch_array($products_q)) {
        if (tep_not_null($products['specials_new_products_price'])) {
          $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['specials_new_products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        else {
          if ($products['products_price'] !=0)
            $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        $display_name = $products['products_name'];
        $display_model = $products['products_model'];

        if (!is_nostock($products['products_id'])) {
          //products that we stock
          $products_atts = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$products['products_id'] . "'");
          if (tep_db_num_rows($products_atts)<1) {
            //product without attribute, diplay qty from products table
            $stock = ' - Stock:' . $products['products_quantity'];
          }
          else {
            $stock = '';
          }
        }
        else {
          //backorder products
          $stock = '';
        }

        if (tep_not_null($products['parent_id'])) {
          $display_a = pv_slave_name($products['parent_id']);
          $display_name = pv_slave_value($products['parent_id'], $products['products_id']);
          $display_model = $display_a['products_name'];

          $print_pv_array[$display_model . "||" . $display_name . "||" . $products['products_id']] = array('pid' => $products['products_id'], 'display_name' => $display_name, 'display_model' => $display_model, 'price' => $price_display, 'stock_display' => $stock);
          continue;
        }
        elseif (tep_not_null($products['has_children'])) {
          continue;
        }
        else {
          $products_array[] = array('id' => $products['products_id'], 'text' => $display_model . "-" . $display_name . " " . $price_display . " " . $stock);
        }
      }
      ksort($print_pv_array);
      foreach ($print_pv_array as $k => $v) {
        $display_pv = explode("||", $k);
        $products_array[] = array('id' => $v['pid'], 'text' => $v['display_model'] . "-" . $display_pv[1] . " " . $v['price'] . " " . $v['stock_display']);
      }
      $entry['plist'] = tep_draw_pull_down_menu('rm_cat_plist', $products_array, '', 'id="rm_cat_plist"');
      echo json_encode($entry);
    }
    elseif(isset($HTTP_POST_VARS['mid'])) {
    	$entry = array();

    	$products_q = tep_db_query("select pd.products_name, p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_tax_class_id, p.parent_id, p.has_children, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, s.customers_id, s.customers_groups_id from " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . $languages_id . "' and p.products_status='1' and p.manufacturers_id='". (int)$HTTP_POST_VARS['mid'] . "' order by p.products_model");

      $products_array = array();
      $products_array[] = array('id' => 0, 'text' => 'Products..');

      $print_pv_array = array();
      while ($products = tep_db_fetch_array($products_q)) {
        if (tep_not_null($products['specials_new_products_price'])) {
          $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['specials_new_products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        else {
          if ($products['products_price'] !=0)
            $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        $display_name = $products['products_name'];
        $display_model = $products['products_model'];

        if (!is_nostock($products['products_id'])) {
          //products that we stock
          $products_atts = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$products['products_id'] . "'");
          if (tep_db_num_rows($products_atts)<1) {
            //product without attribute, diplay qty from products table
            $stock = ' - Stock:' . $products['products_quantity'];
          }
          else {
            $stock = '';
          }
        }
        else {
          //backorder products
          $stock = '';
        }

        if (tep_not_null($products['parent_id'])) {
          $display_a = pv_slave_name($products['parent_id']);
          $display_name = pv_slave_value($products['parent_id'], $products['products_id']);
          $display_model = $display_a['products_name'];

          $print_pv_array[$display_model . "||" . $display_name . "||" . $products['products_id']] = array('pid' => $products['products_id'], 'display_name' => $display_name, 'display_model' => $display_model, 'price' => $price_display, 'stock_display' => $stock);
          continue;
        }
        elseif (tep_not_null($products['has_children'])) {
          continue;
        }
        else {
          $products_array[] = array('id' => $products['products_id'], 'text' => $display_model . "-" . $display_name . " " . $price_display . " " . $stock);
        }
      }
      ksort($print_pv_array);
      foreach ($print_pv_array as $k => $v) {
        $display_pv = explode("||", $k);
        $products_array[] = array('id' => $v['pid'], 'text' => $v['display_model'] . "-" . $display_pv[1] . " " . $v['price'] . " " . $v['stock_display']);
      }
    	$entry['plist'] = tep_draw_pull_down_menu('rm_cat_plist', $products_array, '', 'id="rm_cat_plist"');
    	echo json_encode($entry);
    }
    elseif(isset($HTTP_POST_VARS['keyword'])) {

      $entry = array();
      //$keywords = $HTTP_GET_VARS['keywords'];
      $keywords = stripslashes($HTTP_POST_VARS['keyword']);
      $keywords = str_replace("(","",$keywords);
      $keywords = str_replace(")","",$keywords);

      if (!tep_parse_search_string($keywords, $search_keywords)) {
        //show no product
        $where_str = "and p.products_id =0 ";
      }
      else {
        if (isset($search_keywords) && (sizeof($search_keywords) > 0)) {
          $where_str .= " and (";
          for ($i=0, $n=sizeof($search_keywords); $i<$n; $i++ ) {
            switch ($search_keywords[$i]) {
              case '(':
              case ')':
              case 'and':
              case 'or':
                $where_str .= " " . $search_keywords[$i] . " ";
                break;
              default:
                $keyword = tep_db_prepare_input($search_keywords[$i]);
                $where_str .= "(pd.products_name like '%" . tep_db_input($keyword) . "%' or p.products_model like '%" . tep_db_input($keyword) . "%' or m.manufacturers_name like '%" . tep_db_input($keyword) . "%'";
                $where_str .= " or pd.products_description like '%" . tep_db_input($keyword) . "%'";
                $where_str .= ')';
                break;
            }
          }
          $where_str .= " )";
        }
      }

      //This is the search method from advanced_search_result.php
      $products_q = tep_db_query("select distinct pi.image_filename, p.products_quantity, p.products_model, m.manufacturers_id, p.products_id, pd.products_name, pd.products_description, p.products_price, p.products_market_price, p.products_tax_class_id, p.parent_id, p.has_children, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, IF(s.status, s.specials_new_products_price, p.products_price) as final_price from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m using(manufacturers_id) left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id left join " . TABLE_PRODUCTS_IMAGES . " pi on p.products_id = pi.products_id and pi.product_page = '1' , " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_status = '1' and p.products_id = pd.products_id and pd.language_id = '1' and p.products_id = p2c.products_id and p2c.categories_id = c.categories_id " . $where_str . " order by p.products_model, pd.products_name");

      $products_array = array();
      $products_array[] = array('id' => 0, 'text' => 'Products..');

      $print_pv_array = array();
      while ($products = tep_db_fetch_array($products_q)) {
        if (tep_not_null($products['specials_new_products_price'])) {
          $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['specials_new_products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        else {
          if ($products['products_price'] !=0)
            $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
        }
        $display_name = $products['products_name'];
        $display_model = $products['products_model'];

        if (!is_nostock($products['products_id'])) {
          //products that we stock
          $products_atts = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$products['products_id'] . "'");
          if (tep_db_num_rows($products_atts)<1) {
            //product without attribute, diplay qty from products table
            $stock = ' - Stock:' . $products['products_quantity'];
          }
          else {
            $stock = '';
          }
        }
        else {
          //backorder products
          $stock = '';
        }

        if (tep_not_null($products['parent_id'])) {
          $display_a = pv_slave_name($products['parent_id']);
          $display_name = pv_slave_value($products['parent_id'], $products['products_id']);
          $display_model = $display_a['products_name'];

          $print_pv_array[$display_model . "||" . $display_name . "||" . $products['products_id']] = array('pid' => $products['products_id'], 'display_name' => $display_name, 'display_model' => $display_model, 'price' => $price_display, 'stock_display' => $stock);
          continue;
        }
        elseif (tep_not_null($products['has_children'])) {
          continue;
        }
        else {
          $products_array[] = array('id' => $products['products_id'], 'text' => $display_model . "-" . $display_name . " " . $price_display . " " . $stock);
        }
      }
      ksort($print_pv_array);
      foreach ($print_pv_array as $k => $v) {
        $display_pv = explode("||", $k);
        $products_array[] = array('id' => $v['pid'], 'text' => $v['display_model'] . "-" . $display_pv[1] . " " . $v['price'] . " " . $v['stock_display']);
      }
    	$entry['plist'] = tep_draw_pull_down_menu('rm_cat_plist', $products_array, '', 'id="rm_cat_plist"');
    	echo json_encode($entry);

    }
    elseif(isset($HTTP_POST_VARS['barcode'])) {
    	$entry = array();

      $get_orip_query = tep_db_query("select ori_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where barcode LIKE '%" . tep_db_prepare_input(trim($HTTP_POST_VARS['barcode'])) . "%'");
      if (tep_db_num_rows($get_orip_query) > 0) {
        $get_orip_str = "";
        while($get_orip = tep_db_fetch_array($get_orip_query)) {
          $get_orip_str .= $get_orip['ori_products_id'] . ",";
        }
        $get_orip_str = " in (" . substr($get_orip_str, 0, -1) . ")";

        $products_q = tep_db_query("select pd.products_name, p.products_id, p.products_quantity, p.products_model, p.products_price, p.products_tax_class_id, p.parent_id, p.has_children, IF(s.status, s.specials_new_products_price, NULL) as specials_new_products_price, s.customers_id, s.customers_groups_id from " . TABLE_PRODUCTS . " p left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd where pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_status='1' and p.products_id " . $get_orip_str . " order by p.products_model");

        $products_array = array();
        $products_array[] = array('id' => 0, 'text' => 'Products..');

        $print_pv_array = array();
        while ($products = tep_db_fetch_array($products_q)) {
          if (tep_not_null($products['specials_new_products_price'])) {
            $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['specials_new_products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
          }
          else {
            if ($products['products_price'] !=0)
              $price_display = str_replace("&pound;", "&#163;", $currencies->display_price($products['products_price'], tep_get_tax_rate($products['products_tax_class_id'])));
          }
          $display_name = $products['products_name'];
          $display_model = $products['products_model'];

          if (!is_nostock($products['products_id'])) {
            //products that we stock
            $products_atts = tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$products['products_id'] . "'");
            if (tep_db_num_rows($products_atts)<1) {
              //product without attribute, diplay qty from products table
              $stock = ' - Stock:' . $products['products_quantity'];
            }
            else {
              $stock = '';
            }
          }
          else {
            //backorder products
            $stock = '';
          }

          if (tep_not_null($products['parent_id'])) {
            $display_a = pv_slave_name($products['parent_id']);
            $display_name = pv_slave_value($products['parent_id'], $products['products_id']);
            $display_model = $display_a['products_name'];
  
            $print_pv_array[$display_model . "||" . $display_name . "||" . $products['products_id']] = array('pid' => $products['products_id'], 'display_name' => $display_name, 'display_model' => $display_model, 'price' => $price_display, 'stock_display' => $stock);
            continue;
          }
          elseif (tep_not_null($products['has_children'])) {
            continue;
          }
          else {
            $products_array[] = array('id' => $products['products_id'], 'text' => $display_model . "-" . $display_name . " " . $price_display . " " . $stock);
          }
        }
        ksort($print_pv_array);
        foreach ($print_pv_array as $k => $v) {
          $display_pv = explode("||", $k);
          $products_array[] = array('id' => $v['pid'], 'text' => $v['display_model'] . "-" . $display_pv[1] . " " . $v['price'] . " " . $v['stock_display']);
        }
        $entry['plist'] = tep_draw_pull_down_menu('rm_cat_plist', $products_array, '', 'id="rm_cat_plist"');
        echo json_encode($entry);
      }
    }
  }
  elseif (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'pout_a') { //search attributes by single product
  	$entry = array();
  	$products_atts = tep_db_query("select p.products_id, pd.products_name, p.products_model, ps.products_stock_id, p.products_tax_class_id, ps.products_stock_attributes, ps.products_stock_quantity from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_STOCK . " ps where p.products_id=ps.products_id and p.products_id=pd.products_id and pd.language_id = '" . (int)$languages_id . "' and ps.products_id = '" . (int)$HTTP_POST_VARS['pid'] . "'");
  	if (tep_db_num_rows($products_atts)>0) {

      //echo '<script type="text/javascript">\n';

      //echo '<select name="subatt_selector" onChange="fillCodes_atts(\'' . tep_session_id() . '\')" class="register-input admin-select">';
      //echo '<option name="null" value="" selected>Attributes..</option>';
      $products_array = array();
      $products_array[] = array('id' => 0, 'text' => 'Attributes..');
      while($products_values = tep_db_fetch_array($products_atts)) {
        $display_att=display_att($products_values['products_stock_attributes']);

        $price_display = 0;
        $opt_val = explode(',', $products_values['products_stock_attributes']);
        foreach($opt_val as $val) {
          $opt_optval=explode('-', $val,2);

          $opt_q = tep_db_fetch_array(tep_db_query("select options_values_price from " . TABLE_PRODUCTS_ATTRIBUTES . " where options_id = '" . (int)$opt_optval[0] . "' and options_values_id = '" . (int)$opt_optval[1] . "' and products_id='" . (int)$products_values['products_id'] . "'"));
          if ($opt_q['options_values_price'] >0) {
          	$price_display += $opt_q['options_values_price'];
          }
        }
        $price_display = $currencies->display_price($price_display, tep_get_tax_rate($products_values['products_tax_class_id']));

        if (!is_nostock($products_values['products_id'])) {
          //products that we stock
          $stock = " Stock:" . $products_values['products_stock_quantity'];
        }
        else {
          //backorder products
          $stock = '';
        }

        $products_array[] = array('id' => $products_values['products_stock_attributes'], 'text' => $display_att . " " . $price_display . " " . $stock);
        $entry['alist'] = tep_draw_pull_down_menu($products_values['products_id'], $products_array, '', 'id="rm_p_alist"');

        //get stock info
        //$stock_display= checkStock($products_values['products_id'],$products_values['products_stock_quantity'], 0, $products_values['products_stock_attributes']);
        //if (deprecateForReseller($products_values['products_id'], $products_values['products_stock_attributes']))
        //  continue;
        //echo "\n" . '<option name="' . $products_values['products_id'] . '" value="' . $products_values['products_stock_attributes'] . '">' . $display_att . "---{patt:".$products_values['products_stock_attributes'] . ';pid:' . $products_values['products_id'] . '}' . $price_display . ' ' . $stock_display . '</option>';
      }
      //echo '</select>';
  	}
  	echo json_encode($entry);


    //check_pricing_categories($products_id, $product_categories_name="");
  }
  elseif (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'outp_add') { //add products
  	$entry = array();
  	if (isset($HTTP_POST_VARS['pid']) && isset($HTTP_POST_VARS['att'])) {
      $products_atts = tep_db_fetch_array(tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_bo, ps.products_stock_id, p.products_tax_class_id, ps.products_stock_attributes, ps.products_stock_quantity from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_STOCK . " ps where p.products_status='1' and p.products_id=ps.products_id and p.products_id=pd.products_id and pd.language_id = '" . (int)$languages_id . "' and ps.products_id ='" . (int)$HTTP_POST_VARS['pid'] . "' and ps.products_stock_attributes='" . $HTTP_POST_VARS['att'] . "'"));
      if (!tep_not_null($products_atts['products_bo'])) {
        if (((int)$products_atts['products_stock_quantity'] -(int)$HTTP_POST_VARS['qty'])<0) {
      	  $entry['error'] = "out of stock";
        }
      }

      if (!isset($entry['error'])) {
      	$entry['sku'] = $HTTP_POST_VARS['pid'] . "-" . $HTTP_POST_VARS['att'];
      	$entry['pname'] = $products_atts['products_name'] . " " . display_att($products_atts['products_stock_attributes']);
      	/*var rmbutton = "<?php echo ;?>";*/
      	//$entry['append'] = '<p><input type="hidden" name="' . $entry['sku'] . '" value="' . $entry['sku'] . '" />' . $entry['pname'] . ' <strong class="remP">' . '<span class="ui-icon ui-icon-close"></span>' . '</strong></p>';
      	$entry['append'] = '<p name="' . $entry['sku'] . '"><input type="text" name="' . $entry['sku'] . '_rm_out_sku" value="' . $HTTP_POST_VARS['qty'] . '" size="1" /> ' .  $entry['pname'] . ' <button class="rm_remp"></button>' . '</p>';
      }
  	}
  	elseif (isset($HTTP_POST_VARS['pid'])) {
      $products = tep_db_fetch_array(tep_db_query("select p.products_id, p.parent_id, pd.products_name, p.products_quantity, p.products_bo from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status='1' and p.products_id=pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id ='" . (int)$HTTP_POST_VARS['pid'] . "'"));
      if (!tep_not_null($products['products_bo'])) {
        if (((int)$products['products_quantity'] - (int)$HTTP_POST_VARS['qty'])<0) {
      	  $entry['error'] = "out of stock";
        }
      }

      if (!isset($entry['error'])) {
      	$entry['sku'] = $HTTP_POST_VARS['pid'];
        if (tep_not_null($products['parent_id'])) {
          $display_a = pv_slave_name($products['parent_id']);
          $display_v = pv_slave_value($products['parent_id'], $products['products_id']);
          $display_model = $display_a['products_name'];
          $entry['pname'] = $display_a['products_name'] . " " . $display_v;
        }
        else {
        	$entry['pname'] = $products['products_name'];
        }
      	$entry['append'] = '<p name="' . $entry['sku'] . '"><input type="text" name="' . $entry['sku'] . '_rm_out_sku" value="' . $HTTP_POST_VARS['qty'] . '" size="1" /> ' .  $entry['pname'] . ' <button class="rm_remp"></button>' . '</p>';
      }
  	}
  	echo json_encode($entry);
  }
  elseif (isset($HTTP_POST_VARS['action']) && $HTTP_POST_VARS['action'] == 'rm_save') {
  	$entry = array();
  	//{"4971-5-34,6-102_rm_in":"1","151-5-32_rm_in":"2","rm_return_reasons":"4","rm_status":"103","rm_refund_shipping_fee":"on","rm_refund_overwrite":"1.56","2847_rm_out_sku":"2","5110-5-34,6-103_rm_out_sku":"2","89-5-38_rm_out_sku":"1"}
    //{"rm_status":"1","rm-oid":"290189","action":"rm_save"}
    //{"rm_return_reasons":"4","rm_status":"1","rm_refund_shipping_fee":"on","rm_refund_overwrite":"1.56","rm-oid":"290189","action":"rm_save"}
    //check products out stock
    //$entry['error'] = "o/s"; //echo json_encode($entry); //exit;

    foreach($HTTP_POST_VARS as $key => $value) {
    	if (preg_match("/_rm_out_sku/i", $key)) {
  		  $pval=explode('_rm_out_sku', $key,2);
  		  $psku_val=explode('-', $pval[0],2);
  		  $pid = $psku_val[0];
  		  $attr = $psku_val[1];
  		    if (isset($attr)) {
            $products_atts_qty = tep_db_fetch_array(tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_bo, ps.products_stock_id, p.products_tax_class_id, ps.products_stock_attributes, ps.products_stock_quantity from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_STOCK . " ps where p.products_id=ps.products_id and p.products_id=pd.products_id and pd.language_id = '" . (int)$languages_id . "' and ps.products_id = '" . (int)$pid . "' and ps.products_stock_attributes='" . tep_db_prepare_input($attr) . "'"));
            if (!$products_atts_qty['products_bo']) {
              if ((int)$products_atts_qty['products_stock_quantity'] - (int)$value <0) {
                $entry['error'] .= "o/s: " . $products_atts_qty['products_name'] . " ";
              }
            }
  		    }
  		    else {
            $products_qty = tep_db_fetch_array(tep_db_query("select p.products_id, pd.products_name, p.products_model, p.products_quantity, p.products_bo from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id=pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = '" . (int)$pid . "'"));
            if (!$products_qty['products_bo']) {
              if ((int)$products_qty['products_quantity'] - (int)$value <0) {
                $entry['error'] .= "o/s: " . $products_qty['products_name'] . " ";
              }
            }
  		    }
    	}
    }

    if (tep_not_null($entry['error'])) {
    	echo json_encode($entry);
    	exit;
    }

    foreach($HTTP_POST_VARS as $key => $value) {
      if (preg_match("/_rm_in/i", $key)) { //checkin products
  		  $pval=explode('_rm_in', $key,2);
  		  $psku_val=explode('-', $pval[0],2);
  		  $pid = $psku_val[0];
  		  $attr = $psku_val[1];
  		  //update products quantity & history
  		  if (isset($attr)) {
          tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = products_stock_quantity +" . (int)$value . " where products_id = '" . (int)$pid . "' and products_stock_attributes='" . tep_db_prepare_input($attr) . "'");
          tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity +" . (int)$value . " where products_id = '" . (int)$pid . "'");
          $patt_qty= tep_db_fetch_array(tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$pid . "' and products_stock_attributes='" . tep_db_prepare_input($attr) . "'"));
          $ikstockid= tep_db_fetch_array(tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where products_attributes like '" . tep_db_prepare_input($attr) . "' and ori_products_id = '" . (int)$pid . "'"));

          if (!tep_not_null($ikstockid['ik_stock_products_id'])) $ikstockid['ik_stock_products_id'] =0;
          $sql_data_array = array('ik_stock_products_id' => (int)$ikstockid['ik_stock_products_id'],
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => (int)$value,
                                  'latest_qty' => (int)$patt_qty['products_stock_quantity'],
                                  'comments' => IKSH_COMMENTS_CHECKIN,
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$pid,
                                  'products_attributes' => tep_db_prepare_input($attr),
                                  'ik_stock_checkout_reasons_id' => 4);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

          //get orders_products_id
          $op_id= tep_db_fetch_array(tep_db_query("select orders_products_id from " . TABLE_ORDERS_PRODUCTS . " where orders_id ='" . (int)$HTTP_POST_VARS['rm-oid'] . "' and products_id ='" . (int)$pid . "' and products_stock_attributes like '" . tep_db_prepare_input($attr) . "'"));
  		  }
  		  else {
  		  	tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity +" . (int)$value . " where products_id = '" . (int)$pid . "'");
  		  	$pqty= tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$pid . "'"));

  		  	$ikstockid= tep_db_fetch_array(tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$pid . "'"));
  		  	if (!tep_not_null($ikstockid['ik_stock_products_id'])) $ikstockid['ik_stock_products_id'] =0;
          $sql_data_array = array('ik_stock_products_id' => (int)$ikstockid['ik_stock_products_id'],
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => (int)$value,
                                  'latest_qty' => (int)$pqty['products_quantity'],
                                  'comments' => IKSH_COMMENTS_CHECKIN,
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$pid,
                                  'ik_stock_checkout_reasons_id' => 4);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

          $qty_ = tep_db_fetch_array(tep_db_query("select products_quantity, p.products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p where p.products_id = '" . (int)$pid . "'"));
          if ((int)$qty_['parent_id']) {
     	      pv_update_h_pack($pid, $value, $qty_['parent_id'], IKSH_COMMENTS_CHECKIN);
          }

          //get orders_products_id
          $op_id= tep_db_fetch_array(tep_db_query("select orders_products_id from " . TABLE_ORDERS_PRODUCTS . " where orders_id ='" . (int)$HTTP_POST_VARS['rm-oid'] . "' and products_id ='" . (int)$pid . "'"));
  		  }

  		  //TABLE_ORDERS_RETURNS
        $sql_data_array = array('orders_id' => $HTTP_POST_VARS['rm-oid'],
                                'products_id' => $pid,
                                'orders_products_id' => (int)$op_id['orders_products_id'],
                                'qty' => (int)$value,
                                'date' => 'now()',
                                'orders_returns_status' => 1,
                                'orders_returned_reasons_id' => $HTTP_POST_VARS['rm_return_reasons']);
        tep_db_perform(TABLE_ORDERS_RETURNS, $sql_data_array);
      }
      elseif (preg_match("/_rm_out_sku/i", $key)) { //checkout products
  		  $pval=explode('_rm_out_sku', $key,2);
  		  $psku_val=explode('-', $pval[0],2);
  		  $pid = $psku_val[0];
  		  $attr = $psku_val[1];
  		  //update products quantity & history
  		  if (isset($attr)) {
          tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity = products_stock_quantity -" . (int)$value . " where products_id = '" . (int)$pid . "' and products_stock_attributes='" . tep_db_prepare_input($attr) . "'");
          tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity -" . (int)$value . " where products_id = '" . (int)$pid . "'");
          $patt_qty= tep_db_fetch_array(tep_db_query("select products_stock_quantity from " . TABLE_PRODUCTS_STOCK . " where products_id = '" . (int)$pid . "' and products_stock_attributes='" . tep_db_prepare_input($attr) . "'"));
          $ikstockid= tep_db_fetch_array(tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where products_attributes like '" . tep_db_prepare_input($attr) . "' and ori_products_id = '" . (int)$pid . "'"));

          if (!tep_not_null($ikstockid['ik_stock_products_id'])) $ikstockid['ik_stock_products_id'] =0;
          $sql_data_array = array('ik_stock_products_id' => (int)$ikstockid['ik_stock_products_id'],
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => (int)$value,
                                  'latest_qty' => (int)$patt_qty['products_stock_quantity'],
                                  'comments' => IKSH_COMMENTS_CHECKOUT,
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$pid,
                                  'products_attributes' => tep_db_prepare_input($attr),
                                  'ik_stock_checkout_reasons_id' => 5);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

          //get orders_products_id
          $op_id= tep_db_fetch_array(tep_db_query("select orders_products_id from " . TABLE_ORDERS_PRODUCTS . " where orders_id ='" . (int)$HTTP_POST_VARS['rm-oid'] . "' and products_id ='" . (int)$pid . "' and products_stock_attributes like '" . tep_db_prepare_input($attr) . "'"));
  		  }
  		  else {
  		  	tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity -" . (int)$value . " where products_id = '" . (int)$pid . "'");
  		  	$pqty= tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$pid . "'"));

  		  	$ikstockid= tep_db_fetch_array(tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$pid . "'"));
  		  	if (!tep_not_null($ikstockid['ik_stock_products_id'])) $ikstockid['ik_stock_products_id'] =0;
          $sql_data_array = array('ik_stock_products_id' => $ikstockid['ik_stock_products_id'],
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => (int)$value,
                                  'latest_qty' => (int)$pqty['products_quantity'],
                                  'comments' => IKSH_COMMENTS_CHECKOUT,
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$pid,
                                  'ik_stock_checkout_reasons_id' => 5);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

          $qty_ = tep_db_fetch_array(tep_db_query("select products_quantity, p.products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p where p.products_id = '" . (int)$pid . "'"));
          if ((int)$qty_['parent_id']) {
     	      pv_update_h_pack($pid, $value, $qty_['parent_id'], IKSH_COMMENTS_CHECKOUT);
          }

          $op_id= tep_db_fetch_array(tep_db_query("select orders_products_id from " . TABLE_ORDERS_PRODUCTS . " where orders_id ='" . (int)$HTTP_POST_VARS['rm-oid'] . "' and products_id ='" . (int)$pid . "'"));
  		  }

  		  //TABLE_ORDERS_RETURNS
        $sql_data_array = array('orders_id' => $HTTP_POST_VARS['rm-oid'],
                                'products_id' => $pid,
                                'orders_products_id' => (int)$op_id['orders_products_id'],
                                'qty' => (int)$value,
                                'date' => 'now()',
                                'orders_returns_status' => 2,
                                'orders_returned_reasons_id' => (tep_not_null($HTTP_POST_VARS['rm_return_reasons']) ? (int)$HTTP_POST_VARS['rm_return_reasons']: "null"),
                                'products_stock_attributes' => tep_db_prepare_input($attr));
        tep_db_perform(TABLE_ORDERS_RETURNS, $sql_data_array);
      }
    }//foreach

      if (isset($HTTP_POST_VARS['rm_status'])) { //order status
      	$check_status = tep_db_fetch_array(tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "'"));
      	if ($check_status['orders_status'] != $HTTP_POST_VARS['rm_status']) {
      		tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($HTTP_POST_VARS['rm_status']) . "', last_modified = now() where orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "'");
        }
      }

      //order total update
      if (isset($HTTP_POST_VARS['rm_refund_overwrite']) && tep_not_null($HTTP_POST_VARS['rm_refund_overwrite'])) { //rm_refund_overwrite
      	include(DIR_WS_CLASSES . 'order.php');
      	$order = new order($HTTP_POST_VARS['rm-oid']);
      	$refund_currency = tep_db_prepare_input($order->info['currency']);
       	$refund_currency_value = tep_db_prepare_input($order->info['currency_value']);
       	$comments = "";
      	//$order->info['currency'] => GBP
      	//$order->info['currency_value'] => 1.0000
      	//$latest_refund_amount_value = (float)$HTTP_POST_VARS['rm_refund_overwrite']

      	$latest_refund_amount = tep_db_prepare_input($HTTP_POST_VARS['rm_refund_overwrite']);
      	$latest_refund_amount_value = tep_round(((float)$latest_refund_amount)/((float)$refund_currency_value), 2); //value in default currency

        $total = tep_db_fetch_array(tep_db_query("select orders_total_id, text, value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "' and class = 'ot_total'"));
        $order_total = $total['value']*$refund_currency_value;
        $order_total_value = $total['value']; //value in default currency

      	tep_db_query("insert into " . TABLE_ORDERS_TOTAL . " (orders_id, title, text, value, class, sort_order) values ('" . (int)$HTTP_POST_VARS['rm-oid'] . "', '" . ORDER_REFUND . "', '" . $currencies->format($latest_refund_amount * -1, false, $refund_currency) . "', '" . $latest_refund_amount_value . "', '" . OT_REFUND . "', '51')");
      	$refund_order_total_id = tep_db_insert_id();

      	$comments .= "Refund:" . $currencies->format($latest_refund_amount, false, $refund_currency) . " ";

        $net_rev = tep_db_fetch_array(tep_db_query("select orders_total_id, text, value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "' and class = '" . OT_NET_REVENUE . "'"));

        if ($net_rev['orders_total_id'] == '' || empty($net_rev['orders_total_id'])) {
          //latest refund total
          tep_db_query("insert into " . TABLE_ORDERS_TOTAL . " (orders_id, title, text, value, class, sort_order) values ('" . (int)$HTTP_POST_VARS['rm-oid'] . "', '" . ORDER_REFUND_TOTAL . "', '" . $currencies->format($latest_refund_amount * -1, false, $refund_currency) . "', '". $latest_refund_amount_value . "', '" . OT_REFUND_TOTAL . "', '58')");
          $comments .= "Refund Total:" . $currencies->format($latest_refund_amount, false, $refund_currency);

         	//net revenue
          $net_revenue = number_format((number_format($order_total,2) - number_format($latest_refund_amount,2)),2);
          $new_revenue_value = number_format((number_format($order_total_value,2) - number_format($latest_refund_amount_value,2)),2);
          tep_db_query("insert into " . TABLE_ORDERS_TOTAL . " (orders_id, title, text, value, class, sort_order) values ('" . (int)$HTTP_POST_VARS['rm-oid'] . "', '" . ORDER_NET_REVENUE . "', '" . $currencies->format($net_revenue, false, $refund_currency) . "', '". (float)$new_revenue_value . "', '" . OT_NET_REVENUE . "', '59')");
        }
        else {
          $refund_total = tep_db_fetch_array(tep_db_query("select orders_total_id, text, value, sort_order from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "' and class = '" . OT_REFUND_TOTAL . "'"));

          $latest_refund_total = (((float)$refund_total['value'])*$refund_currency_value) + $latest_refund_amount;
          $latest_refund_total_value = ((float)$refund_total['value']) + $latest_refund_amount_value; //value in default currency

          tep_db_query("update " . TABLE_ORDERS_TOTAL . " set orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "', title='". ORDER_REFUND_TOTAL . "', text='" . $currencies->format($latest_refund_total * -1, false, $refund_currency) . "', value='" . $latest_refund_total_value . "', class='" . OT_REFUND_TOTAL . "', sort_order='58' where orders_total_id='" . $refund_total['orders_total_id'] ."'");
          $comments .= "Refund Total:" . $currencies->format($latest_refund_total, false, $refund_currency);

          $net_revenue = number_format((number_format($order_total,2) - number_format($latest_refund_total,2)),2);
          $new_revenue_value = number_format((number_format($order_total_value,2) - number_format($latest_refund_total_value,2)),2); //value in default currency
          tep_db_query("update " . TABLE_ORDERS_TOTAL . " set orders_id = '" . (int)$HTTP_POST_VARS['rm-oid'] . "', title='" . ORDER_NET_REVENUE . "', text='" . $currencies->format($net_revenue, false, $refund_currency) . "', value='" . (float)$new_revenue_value . "', class='" . OT_NET_REVENUE . "', sort_order='59' where orders_total_id='" . $net_rev['orders_total_id'] ."'");
        }

        //email customer
        $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n\n";
        $orders_status = tep_db_fetch_array(tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "' and orders_status_id='" . (int)$HTTP_POST_VARS['rm_status'] . "'"));

        if (!$order->customer['is_dummy_account']) {
          $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $HTTP_POST_VARS['rm-oid'] . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $HTTP_POST_VARS['rm-oid'], 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status['orders_status_name']);
        }
        else {
          $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $HTTP_POST_VARS['rm-oid'] . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status['orders_status_name']);
        }

        tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT_1 . $HTTP_POST_VARS['rm-oid'] . EMAIL_TEXT_SUBJECT_2 . $orders_status['orders_status_name'], $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

        //orders_status_history
        //tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, tracking_no, updated_by, orders_total_id, orders_returned_reasons_id) values ('" . (int)$HTTP_POST_VARS['rm-oid'] . "', '" . tep_db_input($HTTP_POST_VARS['rm_status']) . "', now(), '1', '" . tep_db_input($comments) . "', 'null', '" . tep_db_input($admin['username'])  . "', '" . $refund_order_total_id . "', '" . $HTTP_POST_VARS['rm_return_reasons'] . "')");
        tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments, tracking_no, updated_by, orders_total_id, orders_returned_reasons_id) values ('" . (int)$HTTP_POST_VARS['rm-oid'] . "', '" . tep_db_input($HTTP_POST_VARS['rm_status']) . "', now(), '1', NULL, NULL, '" . tep_db_input($admin['username'])  . "', '" . (int)$refund_order_total_id . "', " . (tep_not_null($HTTP_POST_VARS['rm_return_reasons']) ? (int)$HTTP_POST_VARS['rm_return_reasons'] : 'NULL') . ")");
      }

  	echo json_encode($entry);
  }

  tep_session_close();
//require(DIR_WS_INCLUDES . 'application_bottom.php');
?>