<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

////
// Get the installed version number
  function tep_get_version() {
    static $v;

    if (!isset($v)) {
      $v = trim(implode('', file(DIR_FS_CATALOG . 'includes/version.php')));
    }

    return $v;
  }

////
// Stop from parsing any further PHP code
// v2.3.3.1 now closes the session through a registered shutdown function
  function tep_exit() {
   exit();
  }

////
// Redirect to another page or site
  function tep_redirect($url) {
    if ( (strstr($url, "\n") != false) || (strstr($url, "\r") != false) ) { 
      tep_redirect(tep_href_link(FILENAME_DEFAULT, '', 'NONSSL', false));
    }

    if ( (ENABLE_SSL == true) && (getenv('HTTPS') == 'on') ) { // We are loading an SSL page
      if (substr($url, 0, strlen(HTTP_SERVER . DIR_WS_HTTP_CATALOG)) == HTTP_SERVER . DIR_WS_HTTP_CATALOG) { // NONSSL url
        $url = HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . substr($url, strlen(HTTP_SERVER . DIR_WS_HTTP_CATALOG)); // Change it to SSL
      }
    }

    if ( strpos($url, '&amp;') !== false ) {
      $url = str_replace('&amp;', '&', $url);
    }

    header('Location: ' . $url);

    tep_exit();
  }

////
// Parse the data used in the html tags to ensure the tags will not break
  function tep_parse_input_field_data($data, $parse) {
    return strtr(trim($data), $parse);
  }

  function tep_output_string($string, $translate = false, $protected = false) {
    if ($protected == true) {
      return htmlspecialchars($string, ENT_QUOTES, CHARSET);
    } else {
      if ($translate == false) {
        return tep_parse_input_field_data($string, array('"' => '&quot;'));
      } else {
        return tep_parse_input_field_data($string, $translate);
      }
    }
  }

  function tep_output_string_protected($string) {
    return tep_output_string($string, false, true);
  }

  function tep_sanitize_string($string) {
    $patterns = array ('/ +/','/[<>]/');
    $replace = array (' ', '_');
    return preg_replace($patterns, $replace, trim($string));
  }

////
// Return a random row from a database query
  function tep_random_select($query) {
    $random_product = '';
    $random_query = tep_db_query($query);
    $num_rows = tep_db_num_rows($random_query);
    if ($num_rows > 0) {
      $random_row = tep_rand(0, ($num_rows - 1));
      tep_db_data_seek($random_query, $random_row);
      $random_product = tep_db_fetch_array($random_query);
    }

    return $random_product;
  }

////
// Return a product's name
// TABLES: products
  function tep_get_products_name($product_id, $language = '') {
    global $languages_id;

    if (empty($language)) $language = $languages_id;

    $product_query = tep_db_query("select products_name from " . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . (int)$product_id . "' and language_id = '" . (int)$language . "'");
    $product = tep_db_fetch_array($product_query);

    return $product['products_name'];
  }

////
// My modification: Return a product's category name
// Table: products_to_categories, categories_description, categories
  function tep_get_products_categories_name($product_id, $language = '')
  {
    global $languages_id;
    if (empty($language)) $language = $languages_id;
    $product_categories_info_query = tep_db_query("select pc.products_id, cd.categories_name from " . TABLE_PRODUCTS_TO_CATEGORIES . " pc, " . TABLE_CATEGORIES . " c, ". TABLE_CATEGORIES_DESCRIPTION . " cd where pc.products_id = '" . (int)$product_id . "' and pc.categories_id=c.categories_id and c.categories_id=cd.categories_id and cd.language_id = '" . (int)$language . "'");
    $product_categories_info = tep_db_fetch_array($product_categories_info_query);    

    return $product_categories_info['categories_name'];
  }
// My modification: Get the super category name of the existing category name
  //function tep_get_products_super_categories_name($category_name, $language = '')
  function tep_get_products_super_categories_name($categories_id, $language = '')
  {
    global $languages_id;
    if (empty($language)) $language = $languages_id;

    //$product_categories_info_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = (select parent_id from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.categories_name= '" . $category_name . "' and cd.language_id = '" . (int)$language . "')");
    //$product_categories_info_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id = '". (int)$categories_id . "' and language_id = '" . (int)$language . "'");
    $product_categories_info_query = tep_db_query("select categories_name from " . TABLE_CATEGORIES_DESCRIPTION . " where categories_id in (select parent_id from " . TABLE_CATEGORIES . " where categories_id= '" . (int)$categories_id . "')");
    
    if (!tep_db_num_rows($product_categories_info_query))
      return "NO Super";
    $product_categories_info = tep_db_fetch_array($product_categories_info_query);
    return $product_categories_info['categories_name'];
  }

////
// Return a product's special price (returns nothing if there is no offer)
// TABLES: products

  /*function tep_get_products_special_price($product_id)
  {
    $product_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$product_id . "' and status = '1'");
    $product = tep_db_fetch_array($product_query);

    return $product['specials_new_products_price'];
  }*/
  
  //CGDiscountSpecials start
  function tep_get_customers_groups_id()
  {
    global $customer_id;
    $customers_groups_query = tep_db_query("select customers_groups_id from " . TABLE_CUSTOMERS . " where customers_id =  '" . $customer_id . "'");
    $customers_groups_id = tep_db_fetch_array($customers_groups_query);
    return $customers_groups_id['customers_groups_id'];
  }

  function tep_get_products_special_price($product_id)
  {
	  global $customer_id;
	  $product_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$product_id . "' and status = '1' and customers_id = '" . $customer_id . "' and customers_groups_id = '0'");
	  if (!tep_db_num_rows($product_query))
	  {
      $customer_groups_id = tep_get_customers_groups_id();
	    $product_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$product_id . "' and status = '1' and customers_groups_id = '" . $customer_groups_id . "' and customers_id = '0'");
	    if (!tep_db_num_rows($product_query))
	    {
  	    $product_query = tep_db_query("select specials_new_products_price from " . TABLE_SPECIALS . " where products_id = '" . (int)$product_id . "' and status = '1' and customers_groups_id = '0' and customers_id = '0'");
	    }
	  }
    $product = tep_db_fetch_array($product_query);
    return $product['specials_new_products_price'];
  }
  //CGDiscountSpecials end


////
// Return a product's stock
// TABLES: products

/*************** oringal code ****************************
  function tep_get_products_stock($products_id)
  {
    $products_id = tep_get_prid($products_id);
    $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
    $stock_values = tep_db_fetch_array($stock_query);

    return $stock_values['products_quantity'];
  }
*************** oringal code ****************************/

// QT Pro: Begin Changed code
  function tep_get_products_stock($products_id, $attributes=array())
  {
    global $languages_id;
    //$products_id = tep_get_prid($products_id);
    
    //My modification - colour sample
    if (is_coloursample_pid($products_id)) {
	    $products_id = tep_get_sample_prid($products_id);
    }
    else {
    	$products_id = tep_get_prid($products_id);
    }
    //My modification - colour sample

    if (sizeof($attributes)>0)
    {
      $all_nonstocked = true;
      $attr_list='';
      $options_list=implode(",",array_keys($attributes));
      $track_stock_query=tep_db_query("select products_options_id, products_options_track_stock from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id in (".$options_list.") and language_id= '" . (int)$languages_id . "' order by products_options_id");
      while($track_stock_array=tep_db_fetch_array($track_stock_query))
      {
        if ($track_stock_array['products_options_track_stock'])
        {
          $attr_list.=$track_stock_array['products_options_id'] . '-' . $attributes[$track_stock_array['products_options_id']] . ',';
          $all_nonstocked=false;
        }
      }
      $attr_list=substr($attr_list,0,strlen($attr_list)-1);
    }
    if ((sizeof($attributes)==0) | ($all_nonstocked))
    { //check if it's bundled products
    	if (is_bundled($products_id)) {
    		return check_bundle_qty($products_id);
    	}
      $stock_query = tep_db_query("select products_quantity as quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'");
    }
    else
    {
      $stock_query=tep_db_query("select products_stock_quantity as quantity from " . TABLE_PRODUCTS_STOCK . " where products_id='". (int)$products_id . "' and products_stock_attributes='".$attr_list."'");
    }
    if (tep_db_num_rows($stock_query)>0)
    {
      $stock=tep_db_fetch_array($stock_query);
      $quantity=$stock['quantity'];
    }
    else
    {
      $quantity = 0;
    }
    return $quantity;
  }
// QT Pro: End Changed Code

////
// Check if the required stock is available
// If insufficent stock is available return an out of stock message

/****************** original code  **********
  function tep_check_stock($products_id, $products_quantity)
  {
    $stock_left = tep_get_products_stock($products_id) - $products_quantity;
*********************************************/

// QT Pro: Begin Changed code
  function tep_check_stock($products_id, $products_quantity, $attributes=array())
  {
    //My modification - colour sample
    if (is_coloursample_pid($products_id)) {
	    $attributes =array();
	    return '';
    }
    //My modification - colour sample

    $stock_left = tep_get_products_stock($products_id, $attributes) - $products_quantity;
// QT Pro: End Changed Code
    $out_of_stock = '';

    if ($stock_left < 0)
    {
    	////My modification - check the none stock categories
    	if (NONE_STOCK_CATEGORIES_ENABLE=='true')
      { //$cats = explode(",",NONE_STOCK_CATEGORIES);
      	if (!is_nostock($products_id))
        {$out_of_stock = '<span class="messageStackError">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';}
      }
      else
      { $out_of_stock = '<span class="messageStackError">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
      }
      ////My modification - check the none stock categories
    }

    return $out_of_stock;
  }

////
// Break a word in a string if it is longer than a specified length ($len)
  function tep_break_string($string, $len, $break_char = '-') {
    $l = 0;
    $output = '';
    for ($i=0, $n=strlen($string); $i<$n; $i++) {
      $char = substr($string, $i, 1);
      if ($char != ' ') {
        $l++;
      } else {
        $l = 0;
      }
      if ($l > $len) {
        $l = 1;
        $output .= $break_char;
      }
      $output .= $char;
    }

    return $output;
  }

////
// Return all HTTP GET variables, except those passed as a parameter
  function tep_get_all_get_params($exclude_array = '') {
    global $HTTP_GET_VARS;

    if (!is_array($exclude_array)) $exclude_array = array();

    $get_url = '';
    if (is_array($HTTP_GET_VARS) && (sizeof($HTTP_GET_VARS) > 0)) {
      reset($HTTP_GET_VARS);
      while (list($key, $value) = each($HTTP_GET_VARS)) {
        if ( is_string($value) && (strlen($value) > 0) && ($key != tep_session_name()) && ($key != 'error') && (!in_array($key, $exclude_array)) && ($key != 'x') && ($key != 'y') ) {
          $get_url .= $key . '=' . rawurlencode(stripslashes($value)) . '&';
        }
      }
    }

    return $get_url;
  }

////
// Returns an array with countries
// TABLES: countries
  function tep_get_countries($countries_id = '', $with_iso_codes = false) {
    $countries_array = array();
    if (tep_not_null($countries_id)) {
      if ($with_iso_codes == true) {
        $countries = tep_db_query("select countries_name, countries_iso_code_2, countries_iso_code_3 from " . TABLE_COUNTRIES . " where countries_id = '" . (int)$countries_id . "' order by countries_name");
        $countries_values = tep_db_fetch_array($countries);
        $countries_array = array('countries_name' => $countries_values['countries_name'],
                                 'countries_iso_code_2' => $countries_values['countries_iso_code_2'],
                                 'countries_iso_code_3' => $countries_values['countries_iso_code_3']);
      } else {
        $countries = tep_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_id = '" . (int)$countries_id . "'");
        $countries_values = tep_db_fetch_array($countries);
        $countries_array = array('countries_name' => $countries_values['countries_name']);
      }
    } else {
      $countries = tep_db_query("select countries_id, countries_name from " . TABLE_COUNTRIES . " order by countries_name");
      while ($countries_values = tep_db_fetch_array($countries)) {
        $countries_array[] = array('countries_id' => $countries_values['countries_id'],
                                   'countries_name' => $countries_values['countries_name']);
      }
    }

    return $countries_array;
  }

////
// Alias function to tep_get_countries, which also returns the countries iso codes
  function tep_get_countries_with_iso_codes($countries_id) {
    return tep_get_countries($countries_id, true);
  }

////
// Generate a path to categories
   function tep_get_path($current_category_id = '') {
    global $cPath_array,$categoriesTree;

    if (tep_not_null($current_category_id)) {
      $cp_size = sizeof($cPath_array);
      if ($cp_size == 0) {
        $cPath_new = $current_category_id;
      } else {
        $cPath_new = '';
        /*$last_category_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$cPath_array[($cp_size-1)] . "'");
        $last_category = tep_db_fetch_array($last_category_query);

        $current_category_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$current_category_id . "'");
        $current_category = tep_db_fetch_array($current_category_query);*/
			  /* optimisation */
			  $last_category['parent_id']		= $categoriesTree->getPere((int)$cPath_array[($cp_size-1)]);
			  $current_category['parent_id']	= $categoriesTree->getPere((int)$current_category_id);
			  /* fin optimisation */

        if ($last_category['parent_id'] == $current_category['parent_id']) {
          for ($i=0; $i<($cp_size-1); $i++) {
            $cPath_new .= '_' . $cPath_array[$i];
          }
        } else {
          for ($i=0; $i<$cp_size; $i++) {
            $cPath_new .= '_' . $cPath_array[$i];
          }
        }
        $cPath_new .= '_' . $current_category_id;

        if (substr($cPath_new, 0, 1) == '_') {
          $cPath_new = substr($cPath_new, 1);
        }
      }
    } else {
      $cPath_new = implode('_', $cPath_array);
    }

    return 'cPath=' . $cPath_new;
  }

////
// Returns the clients browser
  function tep_browser_detect($component) {
    global $HTTP_USER_AGENT;

    return stristr($HTTP_USER_AGENT, $component);
  }

////
// Alias function to tep_get_countries()
  function tep_get_country_name($country_id) {
    $country_array = tep_get_countries($country_id);

    return $country_array['countries_name'];
  }

////
// Returns the zone (State/Province) name
// TABLES: zones
  function tep_get_zone_name($country_id, $zone_id, $default_zone) {
    $zone_query = tep_db_query("select zone_name from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country_id . "' and zone_id = '" . (int)$zone_id . "'");
    if (tep_db_num_rows($zone_query)) {
      $zone = tep_db_fetch_array($zone_query);
      return $zone['zone_name'];
    } else {
      return $default_zone;
    }
  }

////
// Returns the zone (State/Province) code
// TABLES: zones
  function tep_get_zone_code($country_id, $zone_id, $default_zone) {
    $zone_query = tep_db_query("select zone_code from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country_id . "' and zone_id = '" . (int)$zone_id . "'");
    if (tep_db_num_rows($zone_query)) {
      $zone = tep_db_fetch_array($zone_query);
      return $zone['zone_code'];
    } else {
      return $default_zone;
    }
  }

////
// Wrapper function for round()
  function tep_round($number, $precision) {
    if (strpos($number, '.') && (strlen(substr($number, strpos($number, '.')+1)) > $precision)) {
      $number = substr($number, 0, strpos($number, '.') + 1 + $precision + 1);

      if (substr($number, -1) >= 5) {
        if ($precision > 1) {
          $number = substr($number, 0, -1) + ('0.' . str_repeat(0, $precision-1) . '1');
        } elseif ($precision == 1) {
          $number = substr($number, 0, -1) + 0.1;
        } else {
          $number = substr($number, 0, -1) + 1;
        }
      } else {
        $number = substr($number, 0, -1);
      }
    }

    return $number;
  }

////
// Returns the tax rate for a zone / class
// TABLES: tax_rates, zones_to_geo_zones
  function tep_get_tax_rate($class_id, $country_id = -1, $zone_id = -1) {

      //My modification - no vat for some special customers
      global $customer_id;
      if ((int)$customer_id >0 && isNOVATCustomer($customer_id)) {
      	return 0;
      }
      //My modification - no vat for some special customers

    global $customer_zone_id, $customer_country_id;
    static $tax_rates = array();

    if ( ($country_id == -1) && ($zone_id == -1) ) {
      if (!tep_session_is_registered('customer_id')) {
        $country_id = STORE_COUNTRY;
        $zone_id = STORE_ZONE;
      }
      elseif (!isset($customer_country_id) || !isset($customer_zone_id)) { //20160420
        if (!isset($customer_country_id)) {
          $country_id = STORE_COUNTRY;
        }
        if (!isset($customer_zone_id)) {
          $zone_id = STORE_ZONE;
        }
      } else {
        $country_id = $customer_country_id;
        $zone_id = $customer_zone_id;
      }
    }
    elseif (!isset($customer_country_id) || !isset($customer_zone_id)) { //20160420
      if (!isset($customer_country_id)) {
        $country_id = STORE_COUNTRY;
      }
      if (!isset($customer_zone_id)) {
        $zone_id = STORE_ZONE;
      }
    }

    if (!isset($tax_rates[$class_id][$country_id][$zone_id]['rate'])) {
      $tax_query = tep_db_query("select sum(tax_rate) as tax_rate from " . TABLE_TAX_RATES . " tr left join " . TABLE_ZONES_TO_GEO_ZONES . " za on (tr.tax_zone_id = za.geo_zone_id) left join " . TABLE_GEO_ZONES . " tz on (tz.geo_zone_id = tr.tax_zone_id) where (za.zone_country_id is null or za.zone_country_id = '0' or za.zone_country_id = '" . (int)$country_id . "') and (za.zone_id is null or za.zone_id = '0' or za.zone_id = '" . (int)$zone_id . "') and tr.tax_class_id = '" . (int)$class_id . "' group by tr.tax_priority");
      if (tep_db_num_rows($tax_query)) {
        $tax_multiplier = 1.0;
        while ($tax = tep_db_fetch_array($tax_query)) {
          $tax_multiplier *= 1.0 + ($tax['tax_rate'] / 100);
        }

        $tax_rates[$class_id][$country_id][$zone_id]['rate'] = ($tax_multiplier - 1.0) * 100;
      } else {
        $tax_rates[$class_id][$country_id][$zone_id]['rate'] = 0;
      }
    }

    return $tax_rates[$class_id][$country_id][$zone_id]['rate'];
  }

////
// Return the tax description for a zone / class
// TABLES: tax_rates;
  function tep_get_tax_description($class_id, $country_id, $zone_id) {
    static $tax_rates = array();

    //20160420
    if ((int)$country_id ==0 || (int)$zone_id ==0) {
      if ((int)$country_id ==0) {
        $country_id = STORE_COUNTRY;
      }
      if ((int)$zone_id ==0) {
        $zone_id = STORE_ZONE;
      }
    }
    //20160420

    if (!isset($tax_rates[$class_id][$country_id][$zone_id]['description'])) {
      $tax_query = tep_db_query("select tax_description from " . TABLE_TAX_RATES . " tr left join " . TABLE_ZONES_TO_GEO_ZONES . " za on (tr.tax_zone_id = za.geo_zone_id) left join " . TABLE_GEO_ZONES . " tz on (tz.geo_zone_id = tr.tax_zone_id) where (za.zone_country_id is null or za.zone_country_id = '0' or za.zone_country_id = '" . (int)$country_id . "') and (za.zone_id is null or za.zone_id = '0' or za.zone_id = '" . (int)$zone_id . "') and tr.tax_class_id = '" . (int)$class_id . "' order by tr.tax_priority");
      if (tep_db_num_rows($tax_query)) {
        $tax_description = '';
        while ($tax = tep_db_fetch_array($tax_query)) {
          $tax_description .= $tax['tax_description'] . ' + ';
        }
        $tax_description = substr($tax_description, 0, -3);

        $tax_rates[$class_id][$country_id][$zone_id]['description'] = $tax_description;
      } else {
        $tax_rates[$class_id][$country_id][$zone_id]['description'] = TEXT_UNKNOWN_TAX_RATE;
      }
    }

    return $tax_rates[$class_id][$country_id][$zone_id]['description'];
  }

////
// Add tax to a products price
  function tep_add_tax($price, $tax) {
      //My modification - no vat for some special customers
      global $customer_id;
      if ((int)$customer_id >0 && isNOVATCustomer($customer_id)) {
      	$tax=0;
      }
      //My modification - no vat for some special customers

    if ( (DISPLAY_PRICE_WITH_TAX == 'true') && ($tax > 0) ) {
      return $price + tep_calculate_tax($price, $tax);
    } else {
      return $price;
    }
  }

// Calculates Tax rounding the result
  function tep_calculate_tax($price, $tax) {
    return $price * $tax / 100;
  }

////
// Return the number of products in a category
// TABLES: products, products_to_categories, categories
  function tep_count_products_in_category($category_id, $include_inactive = false) {
    $products_count = 0;
    if ($include_inactive == true) {
      $products_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = p2c.products_id and p2c.categories_id = '" . (int)$category_id . "'");
    } else {
      $products_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = p2c.products_id and p.products_status = '1' and p2c.categories_id = '" . (int)$category_id . "'");
    }
    $products = tep_db_fetch_array($products_query);
    $products_count += $products['total'];

    $child_categories_query = tep_db_query("select categories_id from " . TABLE_CATEGORIES . " where parent_id = '" . (int)$category_id . "'");
    if (tep_db_num_rows($child_categories_query)) {
      while ($child_categories = tep_db_fetch_array($child_categories_query)) {
        $products_count += tep_count_products_in_category($child_categories['categories_id'], $include_inactive);
      }
    }

    return $products_count;
  }

////
// Return true if the category has subcategories
// TABLES: categories
  function tep_has_category_subcategories($category_id) {
    $child_category_query = tep_db_query("select count(*) as count from " . TABLE_CATEGORIES . " where parent_id = '" . (int)$category_id . "' and categories_status='1'");
    $child_category = tep_db_fetch_array($child_category_query);

    if ($child_category['count'] > 0) {
      return true;
    } else {
      return false;
    }
  }

////
// Returns the address_format_id for the given country
// TABLES: countries;
  function tep_get_address_format_id($country_id) {
    $address_format_query = tep_db_query("select address_format_id as format_id from " . TABLE_COUNTRIES . " where countries_id = '" . (int)$country_id . "'");
    if (tep_db_num_rows($address_format_query)) {
      $address_format = tep_db_fetch_array($address_format_query);
      return $address_format['format_id'];
    } else {
      return '1';
    }
  }

////
// Return a formatted address
// TABLES: address_format
  function tep_address_format($address_format_id, $address, $html, $boln, $eoln) {
    $address_format_query = tep_db_query("select address_format as format from " . TABLE_ADDRESS_FORMAT . " where address_format_id = '" . (int)$address_format_id . "'");
    $address_format = tep_db_fetch_array($address_format_query);

    $company = tep_output_string_protected($address['company']);
    if (isset($address['firstname']) && tep_not_null($address['firstname'])) {
      $firstname = tep_output_string_protected($address['firstname']);
      $lastname = tep_output_string_protected($address['lastname']);
    } elseif (isset($address['name']) && tep_not_null($address['name'])) {
      $firstname = tep_output_string_protected($address['name']);
      $lastname = '';
    } else {
      $firstname = '';
      $lastname = '';
    }
    $house = trim(tep_output_string_protected($address['house_name']));
    $street = tep_output_string_protected($address['street_address']);
    $suburb = tep_output_string_protected($address['suburb']);
    $city = tep_output_string_protected($address['city']);
    $state = tep_output_string_protected($address['state']);
    if (isset($address['country_id']) && tep_not_null($address['country_id'])) {
      $country = tep_get_country_name($address['country_id']);

      if (isset($address['zone_id']) && tep_not_null($address['zone_id'])) {
        $state = tep_get_zone_code($address['country_id'], $address['zone_id'], $state);
      }
    } elseif (isset($address['country']) && tep_not_null($address['country'])) {
      $country = tep_output_string_protected($address['country']['title']);
    } else {
      $country = '';
    }
    $postcode = tep_output_string_protected($address['postcode']);
    $zip = $postcode;

    if ($html) {
// HTML Mode
      $HR = '<hr />';
      $hr = '<hr />';
      if ( ($boln == '') && ($eoln == "\n") ) { // Values not specified, use rational defaults
        $CR = '<br />';
        $cr = '<br />';
        $eoln = $cr;
      } else { // Use values supplied
        $CR = $eoln . $boln;
        $cr = $CR;
      }
    } else {
// Text Mode
      $CR = $eoln;
      $cr = $CR;
      $HR = '----------------------------------------';
      $hr = '----------------------------------------';
    }

    $statecomma = '';
    $streets = $house . " " . $street;
    if ($suburb != '') $streets = $house . " " . $street . $cr . $suburb;
    //if ($country == '') $country = tep_output_string_protected($address['country']);
    if ($state != '') $statecomma = $state . ', ';

    $fmt = $address_format['format'];
    eval("\$address = \"$fmt\";");

    if ( (ACCOUNT_COMPANY == 'true') && (tep_not_null($company)) ) {
      $address = $company . $cr . $address;
    }

    return $address;
  }

////
// Return a formatted address
// TABLES: customers, address_book
  function tep_address_label($customers_id, $address_id = 1, $html = false, $boln = '', $eoln = "\n") {
    if (is_array($address_id) && !empty($address_id)) {
      return tep_address_format($address_id['address_format_id'], $address_id, $html, $boln, $eoln);
    }

    $address_query = tep_db_query("select entry_firstname as firstname, entry_lastname as lastname, entry_company as company, entry_house_name as house_name, entry_street_address as street_address, entry_suburb as suburb, entry_city as city, entry_postcode as postcode, entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customers_id . "' and address_book_id = '" . (int)$address_id . "'");
    $address = tep_db_fetch_array($address_query);

    $format_id = tep_get_address_format_id($address['country_id']);

    return tep_address_format($format_id, $address, $html, $boln, $eoln);
  }

  function tep_row_number_format($number) {
    if ( ($number < 10) && (substr($number, 0, 1) != '0') ) $number = '0' . $number;

    return $number;
  }

  function tep_get_categories($categories_array = '', $parent_id = '0', $indent = '') {
    global $languages_id;

    if (!is_array($categories_array)) $categories_array = array();

    $categories_query = tep_db_query("select c.categories_id, cd.categories_name from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where parent_id = '" . (int)$parent_id . "' and c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' order by sort_order, cd.categories_name");
    while ($categories = tep_db_fetch_array($categories_query)) {
      $categories_array[] = array('id' => $categories['categories_id'],
                                  'text' => $indent . $categories['categories_name']);

      if ($categories['categories_id'] != $parent_id) {
        $categories_array = tep_get_categories($categories_array, $categories['categories_id'], $indent . '&nbsp;&nbsp;');
      }
    }

    return $categories_array;
  }

  function tep_get_manufacturers($manufacturers_array = '') {
    if (!is_array($manufacturers_array)) $manufacturers_array = array();

    $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
    while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
      $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'], 'text' => $manufacturers['manufacturers_name']);
    }

    return $manufacturers_array;
  }

////
// Return all subcategory IDs
// TABLES: categories
  function tep_get_subcategories(&$subcategories_array, $parent_id = 0) {
    $subcategories_query = tep_db_query("select categories_id from " . TABLE_CATEGORIES . " where parent_id = '" . (int)$parent_id . "'");
    while ($subcategories = tep_db_fetch_array($subcategories_query)) {
      $subcategories_array[sizeof($subcategories_array)] = $subcategories['categories_id'];
      if ($subcategories['categories_id'] != $parent_id) {
        tep_get_subcategories($subcategories_array, $subcategories['categories_id']);
      }
    }
  }

// Output a raw date string in the selected locale date format
// $raw_date needs to be in this format: YYYY-MM-DD HH:MM:SS
  function tep_date_long($raw_date) {
    if ( ($raw_date == '0000-00-00 00:00:00') || ($raw_date == '') ) return false;

    $year = (int)substr($raw_date, 0, 4);
    $month = (int)substr($raw_date, 5, 2);
    $day = (int)substr($raw_date, 8, 2);
    $hour = (int)substr($raw_date, 11, 2);
    $minute = (int)substr($raw_date, 14, 2);
    $second = (int)substr($raw_date, 17, 2);

    return strftime(DATE_FORMAT_LONG, mktime($hour,$minute,$second,$month,$day,$year));
  }

////
// Output a raw date string in the selected locale date format
// $raw_date needs to be in this format: YYYY-MM-DD HH:MM:SS
// NOTE: Includes a workaround for dates before 01/01/1970 that fail on windows servers
  function tep_date_short($raw_date) {
    if ( ($raw_date == '0000-00-00 00:00:00') || empty($raw_date) ) return false;

    $year = substr($raw_date, 0, 4);
    $month = (int)substr($raw_date, 5, 2);
    $day = (int)substr($raw_date, 8, 2);
    $hour = (int)substr($raw_date, 11, 2);
    $minute = (int)substr($raw_date, 14, 2);
    $second = (int)substr($raw_date, 17, 2);

    if (@date('Y', mktime($hour, $minute, $second, $month, $day, $year)) == $year) {
      return date(DATE_FORMAT, mktime($hour, $minute, $second, $month, $day, $year));
    } else {
      return preg_replace('/2037$/', $year, date(DATE_FORMAT, mktime($hour, $minute, $second, $month, $day, 2037)));
    }
  }

////
// Parse search string into indivual objects
  function tep_parse_search_string($search_str = '', &$objects) {
    $search_str = trim(strtolower($search_str));

// Break up $search_str on whitespace; quoted string will be reconstructed later
    $pieces = preg_split('/[[:space:]]+/', $search_str);
    $objects = array();
    $tmpstring = '';
    $flag = '';

    for ($k=0; $k<count($pieces); $k++) {
      while (substr($pieces[$k], 0, 1) == '(') {
        $objects[] = '(';
        if (strlen($pieces[$k]) > 1) {
          $pieces[$k] = substr($pieces[$k], 1);
        } else {
          $pieces[$k] = '';
        }
      }

      $post_objects = array();

      while (substr($pieces[$k], -1) == ')')  {
        $post_objects[] = ')';
        if (strlen($pieces[$k]) > 1) {
          $pieces[$k] = substr($pieces[$k], 0, -1);
        } else {
          $pieces[$k] = '';
        }
      }

// Check individual words

      if ( (substr($pieces[$k], -1) != '"') && (substr($pieces[$k], 0, 1) != '"') ) {
        $objects[] = trim($pieces[$k]);

        for ($j=0; $j<count($post_objects); $j++) {
          $objects[] = $post_objects[$j];
        }
      } else {
/* This means that the $piece is either the beginning or the end of a string.
   So, we'll slurp up the $pieces and stick them together until we get to the
   end of the string or run out of pieces.
*/

// Add this word to the $tmpstring, starting the $tmpstring
        $tmpstring = trim(preg_replace('/"/', ' ', $pieces[$k]));

// Check for one possible exception to the rule. That there is a single quoted word.
        if (substr($pieces[$k], -1 ) == '"') {
// Turn the flag off for future iterations
          $flag = 'off';

          $objects[] = trim(preg_replace('/"/', ' ', $pieces[$k]));

          for ($j=0; $j<count($post_objects); $j++) {
            $objects[] = $post_objects[$j];
          }

          unset($tmpstring);

// Stop looking for the end of the string and move onto the next word.
          continue;
        }

// Otherwise, turn on the flag to indicate no quotes have been found attached to this word in the string.
        $flag = 'on';

// Move on to the next word
        $k++;

// Keep reading until the end of the string as long as the $flag is on

        while ( ($flag == 'on') && ($k < count($pieces)) ) {
          while (substr($pieces[$k], -1) == ')') {
            $post_objects[] = ')';
            if (strlen($pieces[$k]) > 1) {
              $pieces[$k] = substr($pieces[$k], 0, -1);
            } else {
              $pieces[$k] = '';
            }
          }

// If the word doesn't end in double quotes, append it to the $tmpstring.
          if (substr($pieces[$k], -1) != '"') {
// Tack this word onto the current string entity
            $tmpstring .= ' ' . $pieces[$k];

// Move on to the next word
            $k++;
            continue;
          } else {
/* If the $piece ends in double quotes, strip the double quotes, tack the
   $piece onto the tail of the string, push the $tmpstring onto the $haves,
   kill the $tmpstring, turn the $flag "off", and return.
*/
            $tmpstring .= ' ' . trim(preg_replace('/"/', ' ', $pieces[$k]));

// Push the $tmpstring onto the array of stuff to search for
            $objects[] = trim($tmpstring);

            for ($j=0; $j<count($post_objects); $j++) {
              $objects[] = $post_objects[$j];
            }

            unset($tmpstring);

// Turn off the flag to exit the loop
            $flag = 'off';
          }
        }
      }
    }

// add default logical operators if needed
    $temp = array();
    for($i=0; $i<(count($objects)-1); $i++) {
      $temp[] = $objects[$i];
      if ( ($objects[$i] != 'and') &&
           ($objects[$i] != 'or') &&
           ($objects[$i] != '(') &&
           ($objects[$i+1] != 'and') &&
           ($objects[$i+1] != 'or') &&
           ($objects[$i+1] != ')') ) {
        $temp[] = ADVANCED_SEARCH_DEFAULT_OPERATOR;
      }
    }
    $temp[] = $objects[$i];
    $objects = $temp;

    $keyword_count = 0;
    $operator_count = 0;
    $balance = 0;
    for($i=0; $i<count($objects); $i++) {
      if ($objects[$i] == '(') $balance --;
      if ($objects[$i] == ')') $balance ++;
      if ( ($objects[$i] == 'and') || ($objects[$i] == 'or') ) {
        $operator_count ++;
      } elseif ( ($objects[$i]) && ($objects[$i] != '(') && ($objects[$i] != ')') ) {
        $keyword_count ++;
      }
    }

    if ( ($operator_count < $keyword_count) && ($balance == 0) ) {
      return true;
    } else {
      return false;
    }
  }

////
// Check date
  function tep_checkdate($date_to_check, $format_string, &$date_array) {
    $separator_idx = -1;

    $separators = array('-', ' ', '/', '.');
    $month_abbr = array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
    $no_of_days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

    $format_string = strtolower($format_string);

    if (strlen($date_to_check) != strlen($format_string)) {
      return false;
    }

    $size = sizeof($separators);
    for ($i=0; $i<$size; $i++) {
      $pos_separator = strpos($date_to_check, $separators[$i]);
      if ($pos_separator != false) {
        $date_separator_idx = $i;
        break;
      }
    }

    for ($i=0; $i<$size; $i++) {
      $pos_separator = strpos($format_string, $separators[$i]);
      if ($pos_separator != false) {
        $format_separator_idx = $i;
        break;
      }
    }

    if ($date_separator_idx != $format_separator_idx) {
      return false;
    }

    if ($date_separator_idx != -1) {
      $format_string_array = explode( $separators[$date_separator_idx], $format_string );
      if (sizeof($format_string_array) != 3) {
        return false;
      }

      $date_to_check_array = explode( $separators[$date_separator_idx], $date_to_check );
      if (sizeof($date_to_check_array) != 3) {
        return false;
      }

      $size = sizeof($format_string_array);
      for ($i=0; $i<$size; $i++) {
        if ($format_string_array[$i] == 'mm' || $format_string_array[$i] == 'mmm') $month = $date_to_check_array[$i];
        if ($format_string_array[$i] == 'dd') $day = $date_to_check_array[$i];
        if ( ($format_string_array[$i] == 'yyyy') || ($format_string_array[$i] == 'aaaa') ) $year = $date_to_check_array[$i];
      }
    } else {
      if (strlen($format_string) == 8 || strlen($format_string) == 9) {
        $pos_month = strpos($format_string, 'mmm');
        if ($pos_month != false) {
          $month = substr( $date_to_check, $pos_month, 3 );
          $size = sizeof($month_abbr);
          for ($i=0; $i<$size; $i++) {
            if ($month == $month_abbr[$i]) {
              $month = $i;
              break;
            }
          }
        } else {
          $month = substr($date_to_check, strpos($format_string, 'mm'), 2);
        }
      } else {
        return false;
      }

      $day = substr($date_to_check, strpos($format_string, 'dd'), 2);
      $year = substr($date_to_check, strpos($format_string, 'yyyy'), 4);
    }

    if (strlen($year) != 4) {
      return false;
    }

    if (!settype($year, 'integer') || !settype($month, 'integer') || !settype($day, 'integer')) {
      return false;
    }

    if ($month > 12 || $month < 1) {
      return false;
    }

    if ($day < 1) {
      return false;
    }

    if (tep_is_leap_year($year)) {
      $no_of_days[1] = 29;
    }

    if ($day > $no_of_days[$month - 1]) {
      return false;
    }

    $date_array = array($year, $month, $day);

    return true;
  }

////
// Check if year is a leap year
  function tep_is_leap_year($year) {
    if ($year % 100 == 0) {
      if ($year % 400 == 0) return true;
    } else {
      if (($year % 4) == 0) return true;
    }

    return false;
  }

////
// Return table heading with sorting capabilities
  function tep_create_sort_heading($sortby, $colnum, $heading) {
    global $PHP_SELF;

    $sort_prefix = '';
    $sort_suffix = '';

    if ($sortby) {
      $sort_prefix = '<a href="' . tep_href_link($PHP_SELF, tep_get_all_get_params(array('page', 'info', 'sort')) . 'page=1&sort=' . $colnum . ($sortby == $colnum . 'a' ? 'd' : 'a')) . '" title="' . tep_output_string(TEXT_SORT_PRODUCTS . ($sortby == $colnum . 'd' || substr($sortby, 0, 1) != $colnum ? TEXT_ASCENDINGLY : TEXT_DESCENDINGLY) . TEXT_BY . $heading) . '" class="productListing-heading">' ;
      $sort_suffix = (substr($sortby, 0, 1) == $colnum ? (substr($sortby, 1, 1) == 'a' ? '+' : '-') : '') . '</a>';
    }

    return $sort_prefix . $heading . $sort_suffix;
  }

////
// Recursively go through the categories and retreive all parent categories IDs
// TABLES: categories
  function tep_get_parent_categories(&$categories, $categories_id) {
    /*$parent_categories_query = tep_db_query("select parent_id from " . TABLE_CATEGORIES . " where categories_id = '" . (int)$categories_id . "'");
    while ($parent_categories = tep_db_fetch_array($parent_categories_query)) {
      if ($parent_categories['parent_id'] == 0) return true;
      $categories[sizeof($categories)] = $parent_categories['parent_id'];
      if ($parent_categories['parent_id'] != $categories_id) {
        tep_get_parent_categories($categories, $parent_categories['parent_id']);
      }
    }*/
  global $categoriesTree;
	$pere = $categoriesTree->getPere($categories_id);
	if($pere == 0)
	{
		return true;
	}
	else
	{
		$categories[sizeof($categories)] = $pere;
		tep_get_parent_categories($categories, $pere);
	}

  }

////
// Construct a category path to the product
// TABLES: products_to_categories
  function tep_get_product_path($products_id) {
    $cPath = '';

    $category_query = tep_db_query("select p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = '" . (int)$products_id . "' and p.products_status = '1' and p.products_id = p2c.products_id limit 1");
    if (tep_db_num_rows($category_query)) {
      $category = tep_db_fetch_array($category_query);

      $categories = array();
      tep_get_parent_categories($categories, $category['categories_id']);

      $categories = array_reverse($categories);

      $cPath = implode('_', $categories);

      if (tep_not_null($cPath)) $cPath .= '_';
      $cPath .= $category['categories_id'];
    }

    return $cPath;
  }

//// My modification
// Construct a category path array to the product
// TABLES: products_to_categories
  function tep_get_product_path_all($products_id) {
    $cPath = '';
    $cPath_array = array();

    $category_query = tep_db_query("select p2c.categories_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = '" . (int)$products_id . "' and p.products_status = '1' and p.products_id = p2c.products_id");
    if (tep_db_num_rows($category_query)) {
      while ($category = tep_db_fetch_array($category_query)) {
    	  $categories = array();
        tep_get_parent_categories($categories, $category['categories_id']);
        $categories = array_reverse($categories);
        $cPath = implode('_', $categories);

        if (tep_not_null($cPath)) $cPath .= '_';
        $cPath .= $category['categories_id'];
        
        $cPath_array[] = $cPath;
      }
    }
    return $cPath_array;
  }

////
// Return a product ID with attributes
  function tep_get_uprid($prid, $params) {
    if (is_numeric($prid)) {
      $uprid = (int)$prid;

      if (is_array($params) && (sizeof($params) > 0)) {
        $attributes_check = true;
        $attributes_ids = '';

        reset($params);
        while (list($option, $value) = each($params)) {
          if (is_numeric($option) && is_numeric($value)) {
            $attributes_ids .= '{' . (int)$option . '}' . (int)$value;
          } else {
            $attributes_check = false;
            break;
          }
        }

        if ($attributes_check == true) {
          $uprid .= $attributes_ids;
        }
      }
    } else {
      $uprid = tep_get_prid($prid);

      if (is_numeric($uprid)) {
        if (strpos($prid, '{') !== false) {
          $attributes_check = true;
          $attributes_ids = '';

// strpos()+1 to remove up to and including the first { which would create an empty array element in explode()
          $attributes = explode('{', substr($prid, strpos($prid, '{')+1));

          for ($i=0, $n=sizeof($attributes); $i<$n; $i++) {
            $pair = explode('}', $attributes[$i]);

            if (is_numeric($pair[0]) && is_numeric($pair[1])) {
              $attributes_ids .= '{' . (int)$pair[0] . '}' . (int)$pair[1];
            } else {
              $attributes_check = false;
              break;
            }
          }

          if ($attributes_check == true) {
            $uprid .= $attributes_ids;
          }
        }
      } else {
        return false;
      }
    }

    return $uprid;
  }

////
// Return a product ID from a product ID with attributes
  function tep_get_prid($uprid) {
    $pieces = explode('{', $uprid);

    if (is_numeric($pieces[0])) {
      return (int)$pieces[0];
    } else {
      return false;
    }
  }

////
// Return a customer greeting
  function tep_customer_greeting() {
    global $customer_id, $customer_first_name;

    if (tep_session_is_registered('customer_first_name') && tep_session_is_registered('customer_id')) {
      $greeting_string = sprintf(TEXT_GREETING_PERSONAL, tep_output_string_protected($customer_first_name), tep_href_link(FILENAME_PRODUCTS_NEW));
    } else {
      $greeting_string = sprintf(TEXT_GREETING_GUEST, tep_href_link(FILENAME_LOGIN, '', 'SSL'), tep_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'));
    }

    return $greeting_string;
  }

////
//! Send email (text/html) using MIME
// This is the central mail function. The SMTP Server should be configured
// correct in php.ini
// Parameters:
// $to_name           The name of the recipient, e.g. "Jan Wildeboer"
// $to_email_address  The eMail address of the recipient,
//                    e.g. jan.wildeboer@gmx.de
// $email_subject     The subject of the eMail
// $email_text        The text of the eMail, may contain HTML entities
// $from_email_name   The name of the sender, e.g. Shop Administration
// $from_email_adress The eMail address of the sender,
//                    e.g. info@mytepshop.com

  function tep_mail($to_name, $to_email_address, $email_subject, $email_text, $from_email_name, $from_email_address) {
    if (SEND_EMAILS != 'true') return false;

    // Instantiate a new mail object
    $message = new email(array('X-Mailer: osCommerce'));

    // Build the text version
    $text = strip_tags($email_text);
    if (EMAIL_USE_HTML == 'true') {
      $message->add_html($email_text, $text);
    } else {
      $message->add_text($text);
    }

    // Send message
    $message->build_message();
    $message->send($to_name, $to_email_address, $from_email_name, $from_email_address, $email_subject);
  }

////
// Check if product has attributes
  function tep_has_product_attributes($products_id) {
    $attributes_query = tep_db_query("select count(*) as count from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '" . (int)$products_id . "'");
    $attributes = tep_db_fetch_array($attributes_query);

    if ($attributes['count'] > 0) {
      return true;
    } else {
      return false;
    }
  }

////
// Get the number of times a word/character is present in a string
  function tep_word_count($string, $needle) {
    $temp_array = preg_split('/' . $needle . '/', $string);

    return sizeof($temp_array);
  }

  function tep_count_modules($modules = '') {
    $count = 0;

    if (empty($modules)) return $count;

    $modules_array = explode(';', $modules);

    for ($i=0, $n=sizeof($modules_array); $i<$n; $i++) {
      $class = substr($modules_array[$i], 0, strrpos($modules_array[$i], '.'));

      if (isset($GLOBALS[$class]) && is_object($GLOBALS[$class])) {
        if ($GLOBALS[$class]->enabled) {
          $count++;
        }
      }
    }

    return $count;
  }

  function tep_count_payment_modules() {
    return tep_count_modules(MODULE_PAYMENT_INSTALLED);
  }

  function tep_count_shipping_modules() {
    return tep_count_modules(MODULE_SHIPPING_INSTALLED);
  }

  function tep_create_random_value($length, $type = 'mixed') {
    if ( ($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) $type = 'mixed';

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digits = '0123456789';

    $base = '';

    if ( ($type == 'mixed') || ($type == 'chars') ) {
      $base .= $chars;
    }

    if ( ($type == 'mixed') || ($type == 'digits') ) {
      $base .= $digits;
    }

    $value = '';

    if (!class_exists('PasswordHash')) {
      include(DIR_WS_CLASSES . 'passwordhash.php');
    }

    $hasher = new PasswordHash(10, true);

    do {
      $random = base64_encode($hasher->get_random_bytes($length));

      for ($i = 0, $n = strlen($random); $i < $n; $i++) {
        $char = substr($random, $i, 1);

        if ( strpos($base, $char) !== false ) {
          $value .= $char;
        }
      }
    } while ( strlen($value) < $length );

    if ( strlen($value) > $length ) {
      $value = substr($value, 0, $length);
    }

    return $value;
  }

  function tep_array_to_string($array, $exclude = '', $equals = '=', $separator = '&') {
    if (!is_array($exclude)) $exclude = array();

    $get_string = '';
    if (sizeof($array) > 0) {
      while (list($key, $value) = each($array)) {
        if ( (!in_array($key, $exclude)) && ($key != 'x') && ($key != 'y') ) {
          $get_string .= $key . $equals . $value . $separator;
        }
      }
      $remove_chars = strlen($separator);
      $get_string = substr($get_string, 0, -$remove_chars);
    }

    return $get_string;
  }

  function tep_not_null($value) {
    if (is_array($value)) {
      if (sizeof($value) > 0) {
        return true;
      } else {
        return false;
      }
    } else {
      if (($value != '') && (strtolower($value) != 'null') && (strlen(trim($value)) > 0)) {
        return true;
      } else {
        return false;
      }
    }
  }

////
// Output the tax percentage with optional padded decimals
  function tep_display_tax_value($value, $padding = TAX_DECIMAL_PLACES) {
    if (strpos($value, '.')) {
      $loop = true;
      while ($loop) {
        if (substr($value, -1) == '0') {
          $value = substr($value, 0, -1);
        } else {
          $loop = false;
          if (substr($value, -1) == '.') {
            $value = substr($value, 0, -1);
          }
        }
      }
    }

    if ($padding > 0) {
      if ($decimal_pos = strpos($value, '.')) {
        $decimals = strlen(substr($value, ($decimal_pos+1)));
        for ($i=$decimals; $i<$padding; $i++) {
          $value .= '0';
        }
      } else {
        $value .= '.';
        for ($i=0; $i<$padding; $i++) {
          $value .= '0';
        }
      }
    }

    return $value;
  }

////
// Checks to see if the currency code exists as a currency
// TABLES: currencies
  function tep_currency_exists($code) {
    $code = tep_db_prepare_input($code);

    $currency_query = tep_db_query("select code from " . TABLE_CURRENCIES . " where code = '" . tep_db_input($code) . "' limit 1");
    if (tep_db_num_rows($currency_query)) {
      $currency = tep_db_fetch_array($currency_query);
      return $currency['code'];
    } else {
      return false;
    }
  }

  function tep_string_to_int($string) {
    return (int)$string;
  }

////
// Parse and secure the cPath parameter values
  function tep_parse_category_path($cPath) {
// make sure the category IDs are integers
    $cPath_array = array_map('tep_string_to_int', explode('_', $cPath));

// make sure no duplicate category IDs exist which could lock the server in a loop
    $tmp_array = array();
    $n = sizeof($cPath_array);
    for ($i=0; $i<$n; $i++) {
      if (!in_array($cPath_array[$i], $tmp_array)) {
        $tmp_array[] = $cPath_array[$i];
      }
    }

    return $tmp_array;
  }

////
// Return a random value
  function tep_rand($min = null, $max = null) {
    static $seeded;

    if (!isset($seeded)) {
      $seeded = true;

      if ( (PHP_VERSION < '4.2.0') ) {
        mt_srand((double)microtime()*1000000);
      }
    }

    if (isset($min) && isset($max)) {
      if ($min >= $max) {
        return $min;
      } else {
        return mt_rand($min, $max);
      }
    } else {
      return mt_rand();
    }
  }

  function tep_setcookie($name, $value = '', $expire = 0, $path = '/', $domain = '', $secure = 0) {
    setcookie($name, $value, $expire, $path, (tep_not_null($domain) ? $domain : ''), $secure);
  }

  function tep_validate_ip_address($ip_address) {
    if (function_exists('filter_var') && defined('FILTER_VALIDATE_IP')) {
      return filter_var($ip_address, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4));
    }

    if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip_address)) {
      $parts = explode('.', $ip_address);

      foreach ($parts as $ip_parts) {
        if ( (intval($ip_parts) > 255) || (intval($ip_parts) < 0) ) {
          return false; // number is not within 0-255
        }
      }

      return true;
    }

    return false;
  }

  function tep_get_ip_address() {
    global $HTTP_SERVER_VARS;

    $ip_address = null;
    $ip_addresses = array();

    if (isset($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR']) && !empty($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])) {
      foreach ( array_reverse(explode(',', $HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])) as $x_ip ) {
        $x_ip = trim($x_ip);

        if (tep_validate_ip_address($x_ip)) {
          $ip_addresses[] = $x_ip;
        }
      }
    }

    if (isset($HTTP_SERVER_VARS['HTTP_CLIENT_IP']) && !empty($HTTP_SERVER_VARS['HTTP_CLIENT_IP'])) {
      $ip_addresses[] = $HTTP_SERVER_VARS['HTTP_CLIENT_IP'];
    }

    if (isset($HTTP_SERVER_VARS['HTTP_X_CLUSTER_CLIENT_IP']) && !empty($HTTP_SERVER_VARS['HTTP_X_CLUSTER_CLIENT_IP'])) {
      $ip_addresses[] = $HTTP_SERVER_VARS['HTTP_X_CLUSTER_CLIENT_IP'];
    }

    if (isset($HTTP_SERVER_VARS['HTTP_PROXY_USER']) && !empty($HTTP_SERVER_VARS['HTTP_PROXY_USER'])) {
      $ip_addresses[] = $HTTP_SERVER_VARS['HTTP_PROXY_USER'];
    }

    $ip_addresses[] = $HTTP_SERVER_VARS['REMOTE_ADDR'];

    foreach ( $ip_addresses as $ip ) {
      if (!empty($ip) && tep_validate_ip_address($ip)) {
        $ip_address = $ip;
        break;
      }
    }

    return $ip_address;
  }

  function tep_count_customer_orders($id = '', $check_session = true) {
    global $customer_id, $languages_id;

    if (is_numeric($id) == false) {
      if (tep_session_is_registered('customer_id')) {
        $id = $customer_id;
      } else {
        return 0;
      }
    }

    if ($check_session == true) {
      if ( (tep_session_is_registered('customer_id') == false) || ($id != $customer_id) ) {
        return 0;
      }
    }

    $orders_check_query = tep_db_query("select count(*) as total from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$id . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.public_flag = '1'");
    $orders_check = tep_db_fetch_array($orders_check_query);

    return $orders_check['total'];
  }

  function tep_count_customer_address_book_entries($id = '', $check_session = true) {
    global $customer_id;

    if (is_numeric($id) == false) {
      if (tep_session_is_registered('customer_id')) {
        $id = $customer_id;
      } else {
        return 0;
      }
    }

    if ($check_session == true) {
      if ( (tep_session_is_registered('customer_id') == false) || ($id != $customer_id) ) {
        return 0;
      }
    }

    $addresses_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$id . "'");
    $addresses = tep_db_fetch_array($addresses_query);

    return $addresses['total'];
  }

// nl2br() prior PHP 4.2.0 did not convert linefeeds on all OSs (it only converted \n)
  function tep_convert_linefeeds($from, $to, $string) {
    if ((PHP_VERSION < "4.0.5") && is_array($from)) {
      return preg_replace('/(' . implode('|', $from) . ')/', $to, $string);
    } else {
      return str_replace($from, $to, $string);
    }
  }

  //My modification 4/5/2006
  function tep_array_values_to_string($array)
  {
    foreach ($array as $index => $val)
    {
       $val2 .=$val.",";
    }
    return $val2; 
  }


//My modification: show VAT on the order form and invoice
//Order details display no VAT for those countries
  function is_show_VAT($order) {
    if (!$order) return false;
    $novat_countries = array("AU", "CA", "CH", "JP", "NO", "NZ", "CL", "US", "GI", "IL", "ZA", "SG", "TR", "HU", "HK", "CR", "AE", "RU", "SA", "HR");
    if (in_array($order->billing['country']['iso_code_2'], $novat_countries)) {
        return false;
    }
    else
        return true;
  }
////My modification: check the certain categories for pricing choice
function check_pricing_categories($products_id, $product_categories_name="")
{//single:only one price for the product
  $price_query = tep_db_query("select options_values_price from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_id = '". (int)$products_id . "'");
  while ($price = tep_db_fetch_array($price_query)) {
  	if ($price['options_values_price'] > 0) return "multiple";
  }
  return "single";
}

//My modification: sub product name for product_listing display only
function sub_product_name($listing_products_name)
{
  if (preg_match('/Human Hair Silky Weaves/i',$listing_products_name))
  { return preg_replace("/Human Hair Silky Weaves/i", "", $listing_products_name);
  }
  elseif (preg_match('/Human Hair Deep Wave/i',$listing_products_name))
  {	return preg_replace("/Human Hair Deep Wave/i", "", $listing_products_name);
  }
  elseif (preg_match('/Human Hair weave Body Wave/i',$listing_products_name))
  {	return preg_replace("/Human Hair weave Body Wave/i", "", $listing_products_name);
  }
  elseif (preg_match('/Clip in Straight Hair/i',$listing_products_name))
  {	return preg_replace("/Clip in Straight Hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/Clip in Body wave/i',$listing_products_name))
  {	return preg_replace("/Clip in Body wave/i", "", $listing_products_name);
  }
  elseif (preg_match('/Clip in Highlight/i',$listing_products_name))
  {	return preg_replace("/Clip in Highlight/i", "", $listing_products_name);
  }
  elseif (preg_match('/Pick2Fit pieces/i',$listing_products_name))
  {	return preg_replace("/Pick2Fit pieces/i", "", $listing_products_name);
  }
  elseif (preg_match('/12" width Quick-Length piece/i',$listing_products_name))
  {	return preg_replace("/12\" width Quick-Length piece/i", "", $listing_products_name);
  }
  elseif (preg_match('/Pre-bonded nail hair/i',$listing_products_name))
  {	return preg_replace("/Pre-bonded nail hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/Pre-Bonded Stick Hair/i',$listing_products_name))
  {	return preg_replace("/Pre-Bonded Stick Hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/Pre-Bonded REMI Hair/i',$listing_products_name))
  {	return preg_replace("/Pre-Bonded REMI Hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/Clip-in Pony Tail/i',$listing_products_name))
  {	return preg_replace("/Clip-in Pony Tail/i", "", $listing_products_name);
  }
  elseif (preg_match('/I&K Instant Clip-in Hair/i',$listing_products_name))
  {	return preg_replace("/I&K Instant Clip-in Hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/I&amp;K Instant Clip-in Hair/i',$listing_products_name))
  {	return preg_replace("/I&amp;K Instant Clip-in Hair/i", "", $listing_products_name);
  }
  elseif (preg_match('/Micro Loop Ring Hair Extensions 20 strands per Pack/i',$listing_products_name))
  {	return preg_replace("/Micro Loop Ring Hair Extensions 20 strands per Pack/i", "", $listing_products_name);
  }
  else
  {return $listing_products_name;}
}

////My modification, check the lowest price for product listing
function lowest_price_listing($listing,$lc_text="")
{//$listing is an array from $listing_sql
    ////My modification, check the lowest price
    global $currencies, $languages_id;
    //Check if the product has attribute
    $products_attributes_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$listing['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "'");
    $products_attributes = tep_db_fetch_array($products_attributes_query);
    if ($products_attributes['total'] > 0)
    {
      $products_options_array = array();
      $products_options_name_query = tep_db_query("select distinct popt.products_options_id, popt.products_options_name from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$listing['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "' order by popt.products_options_name");
      while ($products_options_name = tep_db_fetch_array($products_options_name_query))
      {//Get options' name
         $products_options_query = tep_db_query("select pov.products_options_values_id, pov.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov where pa.products_id = '" . (int)$listing['products_id'] . "' and pa.options_id = '" . (int)$products_options_name['products_options_id'] . "' and pa.options_values_id = pov.products_options_values_id and pov.language_id = '" . (int)$languages_id . "'order by pov.products_options_values_name");
         while ($products_options = tep_db_fetch_array($products_options_query))
         {//Get every price from each option
            if ($products_options['options_values_price'] != '0')
            {//Store the prices into an array
              $products_options_array[] = $products_options['options_values_price'];
            }
         }
      }
      //Check the lowest price from the option(s)
      sort($products_options_array, SORT_NUMERIC);
      //My modification 30/09/2011 - remi cutical hair attributes price updated fixed
      /*
      if ($listing['products_id'] ==392 || $listing['products_id'] ==4971 || $listing['products_id'] ==5110) {
     	  foreach ($products_options_array as $index => $val) {
     	    if ($val < 10) unset($products_options_array[$index]);
        }
        sort($products_options_array, SORT_NUMERIC);
      }
      */
      //My modification 30/09/2011 - remi cutical hair attributes price updated fixed
      $lowest = $products_options_array[0];
    ////My modification, check the lowest price
    }
    //$product_categories_name = tep_get_products_categories_name($listing['products_id'], $languages_id);
    $check_pricing_categories = check_pricing_categories($listing['products_id']);
    if ($check_pricing_categories=="single")
    {
    	$is_colourring = colourring_model($listing['products_model']);
    	if (!$is_colourring)
    	{
        if (tep_not_null($listing['products_market_price']) && $listing['products_market_price'] > $listing['products_price']) {
    		  $temp_price = '<h5>RRP: ' . $currencies->display_normal_price($listing['products_market_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</h5><h5>' . $currencies->display_normal_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</h5>';
    	  }
    	  else {
    	  	$temp_price = '<h5>' . $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</h5>';
    	  }
    	}
    	else $temp_price= '<h5>From ' . $currencies->display_price_nodiscount($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . "</h5>";
    }
    else
    {
    	if (!is_gv_products($listing['products_id']))
    	  $temp_price = '<h5>From ' . $currencies->display_price($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . '</h5>';
    	else
    	  $temp_price = '<h5>From ' . $currencies->display_price_nodiscount($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . '</h5>';
    }
    if (tep_not_null($listing['specials_new_products_price']))
    {
    	if ($check_pricing_categories=="single")
    	{
    		if (tep_not_null($listing['products_market_price']) && $listing['products_market_price'] > $listing['products_price']) {
    			$lc_text = '<h5><s>RRP ' .  $currencies->display_price($listing['products_market_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</s></h5><h5><strong>' . $currencies->display_price_nodiscount($listing['specials_new_products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</strong></h5>';
    		}
    		else {
    			$lc_text = '<h5><s>' .  $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</s></h5><h5><strong>' . $currencies->display_price_nodiscount($listing['specials_new_products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</strong></h5>';
    		}
      }
      else
      { $lc_text = '<h5><strong>SPECIAL!</strong></h5>';
      }
    }
    else
    { $lc_text = $temp_price;
    }
    
    //tag offer
    global $tag1;
    //if (sizeof($tagoffer_array) >0 && in_array($listing['manufacturers_id'], $tagoffer_array)) $lc_text .= '<div style="padding:5px 0px 0px 0px; clear: both;"><div style="border-width: 1px;background-color: #e0e0e0; border-color: #cccccc;  border-style: solid;display:inline">&nbsp;3 FOR 2&nbsp;</div></div>';
    if (sizeof($tag1) >0 && in_array($listing['products_id'], $tag1)) $lc_text .= '<div style="padding:5px 0px 0px 0px; clear: both;"><div style="border-width:1px;background-color:#f35c93;border-color:#cccccc; color:#ffffff; border-style:solid;display:inline;font-size:14px;">&nbsp;3 FOR 2&nbsp;</div></div>';
    return $lc_text;
}

  /*My modification: the $order->customer['country'] is filled with iso code from google response msg, this function converts it to country name as osc does previously, e.g. GB -> United Kingdom*/
  function convert_google_countries_name($google_country_code)
  {
  	if(tep_not_null($google_country_code) && strlen($google_country_code) == 2)
  	{	
      $country_name = tep_db_query("select countries_name from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $google_country_code . "'");
      $country_name_values = tep_db_fetch_array($country_name);

      return $country_name_values['countries_name'];
    }
    else
    {	return $google_country_code;
    }
  }

////My modification check postcode for UK fastway shipping
function checkFastway($postcode="")
{
	//trim the end part and tidy the postcode first
	$postcode=str_replace(" ", "", trim($postcode));
  $postcode=substr(strtoupper($postcode), 0, -3);

  //array for matching the double alphabet at the beginning
	$doubleall = array("AL","BB","BL","BN","CB","CM","CO","CT","CV","DA","DL","DN","DY","EH","EN","FY","GL","HD","HG","HP","HR","HU","HX","IP","LE","LS","LU","ME","MK","ML","NG","NN","NR","OL","PR","RM","SG","SS","TN","TS","WD","WF","WN","WR","WS","WV","YO");
	$singleall = array("B","G","L","M","S");
	if (in_array(substr($postcode,0,2),$doubleall))
	{//double all
		return "d1";
	}
	elseif (in_array(substr($postcode,0,1),$singleall) && is_numeric(substr($postcode,1,1)))
	{//single all
		return "s1";
	}
	elseif (preg_match("/^(BD[1-9]|BD1[0-9]|BD2[0-2])$/i", $postcode) ||
	        preg_match("/^(CH4[0-9]|CH5[0-9]|CH60)$/i", $postcode) ||
	        preg_match("/^(GU2[8-9]|GU3[0-1])$/i", $postcode) ||
	        preg_match("/^((KA[1-9]|KA1[0-7])|(KA2[0-5])|(KA29|KA30))$/i", $postcode) ||
	        preg_match("/^(PA[1-9]|PA1[0-9])$/i", $postcode) ||
	        preg_match("/^((PE[1-9]|PE1[0-9])|(PE2[6-9])|(PE3[0-8]))$/i", $postcode) ||
	        preg_match("/^(PO[1-9]|PO1[0-9]|PO2[0-2])$/i", $postcode) ||
	        preg_match("/^(RH1[0-9]|RH20)$/i", $postcode) ||
	        preg_match("/^(SK[1-8]|SK1[3-6]|SK22)$/i", $postcode) ||
	        preg_match("/^(SN[1-7]|SN2[5-6]|SN38)$/i", $postcode) ||
	        preg_match("/^(WA[1-9]|WA1[0-5])$/i", $postcode)
	       )
	{//double range
		return "d2";
	}
	elseif (preg_match('/'. substr($postcode,0,2) . '/i',"SR") ||
	        preg_match("/^((NE[1-9]|NE1[0-7])|(NE[2-3][0-9]|NE4[0-2])|NE6[1-4])$/i", $postcode) ||
	        preg_match("/^(DH[0-9])$/i", $postcode))
	{//nextday1
		return "n1";
	}
	elseif (preg_match("/^(DH[8-9])$/i", $postcode) || preg_match("/^(NE0|NE61|NE[3-5]|(NE1[8-9]|NE20)|NE4[3-9]|(NE6[5-9]|NE7[0-1]))$/i", $postcode))
	{//nextday1
		return "n1";
	}
	else
	return "";
}
////My modification check postcode for UK fastway shipping

/*
The flow of the function
$input_strand-qty -> actual input qty of 25 strands -> get the current 25 strands product qty -> get the attribute stock left for 25 strands attribute -> update all attribute qty
The pre-condition: all the pre-bonded product attributes are present in DB
*/
/*
function checkout_update_prebonded_qty($product_id, $opt_optval_array_raw, $qty)
{
	if (empty($product_id) || sizeof($opt_optval_array_raw)<1 || empty($qty))
	  return $qty;
	//$input_strand_qty represent the factor of the input qty
  $input_strand_qty = 1;

  //for later operation correct the array key order e.g avoid something like: Array([1]=>5-34 [0]=>8-54), the key should start with 0
  $kk = array_keys($opt_optval_array_raw);
  if ($kk[0]>$kk[1])
  { $opt_optval_array=array();
	  foreach ($kk as $kkk)
    {$opt_optval_array[]=$opt_optval_array_raw[$kkk];}
  }
  else
  {	$opt_optval_array = $opt_optval_array_raw; }

	//if a strand units is selected by the user, other strand units of the same length will all be updated, but the based quantity of the product is for 25 strands
	switch ($opt_optval_array[1])
	{
		  //14" strand units
      case '8-451' : $input_strand_qty=1; break; //25 strands
      case '8-452' : $input_strand_qty=2; break; //50 strands
      case '8-453' : $input_strand_qty=3; break; //75 strands
      case '8-454' : $input_strand_qty=4; break; //100 strands
      case '8-455' : $input_strand_qty=5; break; //125 strands
      case '8-456' : $input_strand_qty=6; break; //150 strands
      case '8-457' : $input_strand_qty=7; break; //175 strands
      case '8-458' : $input_strand_qty=8; break; //200 strands

		  //18" strand units
      case '8-54' : $input_strand_qty=1; break; //25 strands
      case '8-55' : $input_strand_qty=2; break; //50 strands
      case '8-56' : $input_strand_qty=3; break; //75 strands
      case '8-57' : $input_strand_qty=4; break; //100 strands
      case '8-58' : $input_strand_qty=5; break; //125 strands
      case '8-59' : $input_strand_qty=6; break; //150 strands
      case '8-60' : $input_strand_qty=7; break; //175 strands
      case '8-61' : $input_strand_qty=8; break; //200 strands

      //22" strand units
      case '8-62' : $input_strand_qty=1; break; //25 strands
      case '8-63' : $input_strand_qty=2; break; //50 strands
      case '8-64' : $input_strand_qty=3; break; //75 strands
      case '8-65' : $input_strand_qty=4; break; //100 strands
      case '8-66' : $input_strand_qty=5; break; //125 strands
      case '8-67' : $input_strand_qty=6; break; //150 strands
      case '8-68' : $input_strand_qty=7; break; //175 strands
      case '8-69' : $input_strand_qty=8; break; //200 strands
	}

	//The actual qty for the 25 strands products: eg. if the input_strand_qty is 3 (75 strands) then the acutal qty update for 25 strands is 25*3=75
	$input_strand_qty = $input_strand_qty*$qty;
	$attributes_stock_left=0;

  //Firstly update the 25 strands stock qty (either 18" or 22") for this product, The actual 25-strands attribute of this product: $attributes_stock_left=$strand['products_stock_quantity']-$input_strand_qty
  if (preg_match('/5-34/i', $opt_optval_array[0]))
  {//18"
    $attributes_stock_query=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$product_id . " and products_stock_attributes = '5-34,8-54' order by products_stock_attributes");
    if (tep_db_num_rows($attributes_stock_query) > 0)
    { $t_strands = tep_db_fetch_array($attributes_stock_query);
    	$attributes_stock_left=$t_strands['products_stock_quantity']-$input_strand_qty;
    }
  }
  elseif (preg_match('/5-38/i', $opt_optval_array[0]))
  {//22"
  	$attributes_stock_query=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$product_id . " and products_stock_attributes = '5-38,8-62' order by products_stock_attributes");
    if (tep_db_num_rows($attributes_stock_query) > 0)
    { $t_strands = tep_db_fetch_array($attributes_stock_query);
    	$attributes_stock_left=$t_strands['products_stock_quantity']-$input_strand_qty;
    }
  }
  elseif (preg_match('/5-32/i', $opt_optval_array[0]))
  {//14"
  	$attributes_stock_query=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$product_id . " and products_stock_attributes = '5-32,8-451' order by products_stock_attributes");
    if (tep_db_num_rows($attributes_stock_query) > 0)
    { $t_strands = tep_db_fetch_array($attributes_stock_query);
    	$attributes_stock_left=$t_strands['products_stock_quantity']-$input_strand_qty;
    }
  }

	//get all the strand units' option values
	$q_strands=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$product_id . " and products_stock_attributes like '" . $opt_optval_array[0] . "%' order by products_stock_attributes");

	while($strand=tep_db_fetch_array($q_strands))
	{
    switch ($strand[products_stock_attributes]) 
    {
    	//14" strand units
      case '5-32,8-451' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$attributes_stock_left . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-32,8-452' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-32,8-453' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-32,8-454' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-32,8-455' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-32,8-456' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-32,8-457' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-32,8-458' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands

    	//18" strand units
      case '5-34,8-54' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$attributes_stock_left . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-34,8-55' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-34,8-56' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-34,8-57' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-34,8-58' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-34,8-59' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-34,8-60' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-34,8-61' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands

      //22" strand units
      case '5-38,8-62' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$attributes_stock_left . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-38,8-63' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-38,8-64' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-38,8-65' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-38,8-66' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-38,8-67' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-38,8-68' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-38,8-69' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($attributes_stock_left/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands
    }
	}
	//Actuall brought for the 25 strands products
	return $input_strand_qty;
}
*/

////
////My modification - skip deprecated products for index products listing
//make sure the these products_id and categories doesn't change
/*
function deprecateListing($index=true, $products_id='', $category=true)
{
	if ($index)
	  return " and p.products_id not in (395,396) ";
	else
	{ 
		if ($category)
		{
			return " and c.categories_id not in (60)";
		}
		else
		{
			return "";
		}
	}
}
*/

////My modification - Restock items, used in responsehandler.php for restock cancelled GC orders
  function tep_remove_order($order_id, $restock = false, $delete=true)
  {
// QT Pro: Begin Changed code
    if ($restock == 'on')
    {
      $order_query = tep_db_query("select products_id, products_quantity, products_stock_attributes from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
      while ($order = tep_db_fetch_array($order_query))
      {
        $product_stock_adjust = 0;
        if (tep_not_null($order['products_stock_attributes']))
        {
          if ($order['products_stock_attributes'] != '$$DOWNLOAD$$')
          {
            $attributes_stock_query = tep_db_query("SELECT products_stock_quantity 
                                                    FROM " . TABLE_PRODUCTS_STOCK . " 
                                                    WHERE products_stock_attributes = '" . $order['products_stock_attributes'] . "' 
                                                    AND products_id = '" . (int)$order['products_id'] . "'");
            if (tep_db_num_rows($attributes_stock_query) > 0)
            {
                $attributes_stock_values = tep_db_fetch_array($attributes_stock_query);
                /*
                //My modification: prebonded product stock update
                //$product_categories_name = tep_get_products_categories_name($order['products_id']);
	              if (check_nailstick($order['products_id']))
	              {
	              	$products_stock_attributes_array = explode(',', $order['products_stock_attributes'], 2);
	              	asort($products_stock_attributes_array, SORT_NUMERIC);
	              	$actual_stock_remove=checkout_update_prebonded_qty($order['products_id'], $products_stock_attributes_array, $order['products_quantity']);
	                $product_stock_adjust = min($actual_stock_remove,  $actual_stock_remove+$attributes_stock_values['products_stock_quantity']);
	              }
                //My modification: prebonded product stock update
                else
                {
                */
                  tep_db_query("UPDATE " . TABLE_PRODUCTS_STOCK . " 
                              SET products_stock_quantity = products_stock_quantity + '" . (int)$order['products_quantity'] . "' 
                              WHERE products_stock_attributes = '" . $order['products_stock_attributes'] . "' 
                              AND products_id = '" . (int)$order['products_id'] . "'");
                  $product_stock_adjust = min($order['products_quantity'],  $order['products_quantity']+$attributes_stock_values['products_stock_quantity']);
                //}
            }
            else
            {
                tep_db_query("INSERT into " . TABLE_PRODUCTS_STOCK . " 
                              (products_id, products_stock_attributes, products_stock_quantity)
                              VALUES ('" . (int)$order['products_id'] . "', '" . $order['products_stock_attributes'] . "', '" . (int)$order['products_quantity'] . "')");
                $product_stock_adjust = $order['products_quantity'];
            }
          }
        }
        else
        {
          $product_stock_adjust = $order['products_quantity'];
        }
        ////My modification - Bundled products
        if (is_bundled($order['products_id'])) {
        	bundle_update_restock($order['products_id'], $order['products_quantity']);
        }
        else {
          tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET products_quantity = products_quantity + " . $product_stock_adjust . ", products_ordered = products_ordered - " . (int)$order['products_quantity'] . " WHERE products_id = '" . (int)$order['products_id'] . "'");
        }
        ////My modification - Bundled products
      }
    }
// QT Pro: End Changed Code

    if ($delete) {
      tep_db_query("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
      tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
      tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$order_id . "'");
      tep_db_query("delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$order_id . "'");
      tep_db_query("delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "'");
    }
  }

require('general_add.php');
?>