<?php
////Check ip address
function is_goodip() {
	if (getenv('HTTP_X_FORWARDED_FOR'))
  { $funnyip=getenv('HTTP_X_FORWARDED_FOR');}
  else
  { $funnyip=getenv('REMOTE_ADDR');}
  if ($funnyip==IANDK_HOME_IP_ADDRESS)
  { return true; }
  elseif (!tep_not_null(IANDK_HOME_IP_ADDRESS))
  { return true; }
  else
  { return false; }
}

////Get product's categories_id
//used in general.php-tep_check_stock(), pad_multiple_drowns.php
function get_products_categories_id($products_id)
{
  $cate_query=tep_db_query("select categories_id from " . TABLE_PRODUCTS_TO_CATEGORIES . " where products_id='". (int)$products_id . "'");
  $cate = tep_db_fetch_array($cate_query);
  return $cate['categories_id'];
}

////Check if this is a no stock category
function is_nostock($products_id) {
	$product_query=tep_db_query("select products_bo from " . TABLE_PRODUCTS . " where products_id ='". (int)$products_id . "'");
  $product = tep_db_fetch_array($product_query);
  return $product['products_bo'];
}


// IMAGE SUBDIRECTORY SUPPORT
function tep_image_subdirectory($image_subdirectory,$new_dir='off') {
    /*if (USE_REG_GLOBALS_PATCH == 'true') {
		link_post_variable('image_subdirectory'); // function included in the Register Globals Patch - functions/general.php
		link_post_variable('new_dir'); // function included in the Register Globals Patch - in functions/general.php
	}*/
	if (isset($new_dir) && $new_dir==1)
   $new_dir ='on';
  else
   $new_dir ='off';

// Here we strip away any extra slashes the user may add to the subdirectory field and make sure there is a final /.
   $image_subdirectory = preg_replace('/\\\/', '/', $image_subdirectory); // in case someone mistakenly uses backward slashes, flip 'em around
   if ($image_subdirectory == '/') { ($image_subdirectory = ''); // in case the user mistakenly entered only a single /
        }
   if ($image_subdirectory != '') { // we want to add a path
        $image_subdirectory = preg_replace('/\/\/+/', '/', $image_subdirectory); // change any multiple slashes to a single /
        if (strpos($image_subdirectory, '/') === 0) { $image_subdirectory = substr($image_subdirectory, 1); // strip any leading slash
        }
        if (strrpos($image_subdirectory, '/') != (strlen($image_subdirectory) -1)) { $image_subdirectory = ($image_subdirectory . '/'); // add one slash at the end if there wasn't one.
        }
   } // End corrections of any user errors

// Create directory if requested (Box Checked, $new_dir == 'on'
    if ($new_dir == 'on') {
    if (USE_UNIX_SLASHES == 'true') {
		$working_directory = DIR_FS_CATALOG_IMAGES . $image_subdirectory;
		if (USE_PHP5_MKDIR == 'true') {
		   if (!is_dir($working_directory)) { 
		   mkdir($working_directory, 0777, 1);
		   // chmod($working_directory, 0777); // Change to suit server needs, depending on directory owner
		   } 

		} else { // php4 mkdir recursion
	
			do {
			   $dir = $working_directory;

				while (!@mkdir($dir,0777)) {
					$dir = dirname($dir);
					if ($dir == '/' || is_dir($dir))
						break;
					}
			// chmod($dir, 0777); // Change to suit server needs, depending on directory owner
			} while ($dir != $working_directory);
	
			} 
		} else { // End unix mkdir routine

	// Windows mkdir routine start
		$working_directory = DIR_FS_CATALOG_IMAGES . preg_replace('/\//', '\\', $image_subdirectory); // replaces forward slashes with backwards ones
		if (USE_PHP5_MKDIR == 'true') {
		   if (!is_dir($working_directory)) { 
		   mkdir($working_directory, 0777, 1); // the 0777 chmod arg. has no effect under Windows, left in for consistency
		   } 

		} else { // php4 mkdir recursion
	
			do {
			   $dir = $working_directory;
				while (!@mkdir($dir)) {
					$dir = dirname($dir);
					if ($dir == '\\' || is_dir($dir))
						break;
					}
			} while ($dir != $working_directory);
	
			} 
		}	// End Windows mkdir routine
	}
  return $image_subdirectory;
}

function get_subdir_string($dirstring)
{
	$str_array= explode("/", $dirstring);
  return trim(str_ireplace($str_array[sizeof($str_array)-1], "", $dirstring));
}

function subdirwith_img($dirstring)
{
	if (preg_match("/gif/i",$dirstring) || preg_match("/jpg/i",$dirstring) || preg_match("/png/i",$dirstring))
    return true;
	else
	  return false;
}

function product_imginsert($products_id, $image_filename, $type='update')
{
	$duplicate_query = tep_db_query("select images_id from " . TABLE_PRODUCTS_IMAGES . " where products_id = '".(int)$products_id."' and image_filename = '" .$image_filename . "'");

	if ($type=='update') {
		if (tep_db_num_rows($duplicate_query)< 1)
		{
			$update_query = tep_db_query("select images_id from " . TABLE_PRODUCTS_IMAGES . " where products_id = '".(int)$products_id."' and 	images_order='1' and category_page='1' and product_page='1' and popup_page='1'");
			if (tep_db_num_rows($update_query)>0) {
				$update = tep_db_fetch_array($update_query);
				tep_db_query("update " . TABLE_PRODUCTS_IMAGES . " set image_filename = '" . tep_db_input($image_filename) . "', last_modified = now() where images_id = '" . (int)$update['images_id'] . "'");
			}
			else {
		  $insert_query = tep_db_query("insert " . TABLE_PRODUCTS_IMAGES . " set image_filename = '" . tep_db_input($image_filename) . "', products_id = '" . (int)$products_id . "', images_order = '1', image_desc='', category_page='1' , last_modified = now()");
      $insert_id = tep_db_insert_id();
      tep_set_image_page($insert_id, 'category_page');
      tep_set_image_page($insert_id, 'product_page');
      tep_set_image_page($insert_id, 'popup_page');
      }
      return true;
    }
    else
    {
    	$duplicate = tep_db_fetch_array($duplicate_query);
    	tep_set_image_page($duplicate['images_id'], 'category_page');
      tep_set_image_page($duplicate['images_id'], 'product_page');
      tep_set_image_page($duplicate['images_id'], 'popup_page');
      if (!tep_not_null($image_filename))
        tep_db_query("update " . TABLE_PRODUCTS_IMAGES . " set image_filename = '', last_modified = now() where images_id = '" . (int)$update['images_id'] . "'");
      return true;
    }
	}
	elseif ($type=='insert') {
    if (tep_db_num_rows($duplicate_query)< 1)
    {
    	$insert_query = tep_db_query("insert " . TABLE_PRODUCTS_IMAGES . " set image_filename = '" . tep_db_input($image_filename) . "', products_id = '" . (int)$products_id . "', images_order = '1', image_desc='', category_page='1' , last_modified = now()");
      $insert_id = tep_db_insert_id();
      tep_set_image_page($insert_id, 'category_page');
      tep_set_image_page($insert_id, 'product_page');
      tep_set_image_page($insert_id, 'popup_page');
      return true;
    }
  }
  return false;
}

////My modification - check if the product is pre-bonded nail/stick
/*
function check_nailstick($categoryname="", $categories_id=0, $products_id=0) {
	if (tep_not_null($products_id) || tep_not_null($categories_id)) {
		if ($categories_id) {
			$categories = tep_db_fetch_array(tep_db_query("select multi_price from " . TABLE_CATEGORIES . " where categories_id ='" . (int)$categories_id . "'"));
		}
		elseif ($products_id) {
			$categories = tep_db_fetch_array(tep_db_query("select c.multi_price from " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where c.categories_id=p2c.categories_id and p2c.products_id ='" . (int)$products_id . "'"));
		}
	  if ($categories['multi_price']>1)
	    return true;
	  else
	    return false;
	}
	else {
    global $languages_id;
	  $categories_query = tep_db_query("select c.multi_price from " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd where c.categories_id = cd.categories_id and cd.language_id = '" . (int)$languages_id . "' and cd.categories_name='". tep_db_input($categoryname) . "'");
	  $categories = tep_db_fetch_array($categories_query);
	  if ($categories['multi_price']>1)
	    return true;
	  else
	    return false;
	}
}
*/


// Function to reset SEO URLs database cache entries 
// Ultimate SEO URLs v2.1
/*
function tep_reset_cache_data_seo_urls($action){	
	switch ($action){
		case 'reset':
			tep_db_query("DELETE FROM cache WHERE cache_name LIKE '%seo_urls%'");
			tep_db_query("UPDATE configuration SET configuration_value='false' WHERE configuration_key='SEO_URLS_CACHE_RESET'");
			break;
		default:
			break;
	}
	# The return value is used to set the value upon viewing
	# It's NOT returining a false to indicate failure!!
	return 'false';
}
*/

// BOF: XSell
function rdel($path, $deldir = true) { 
        // $path is the path on the php file
        // $deldir (optional, defaults to true) allow if you want to delete the directory (true) or empty only (false)
  
        // it first checks the name of the directory contents "/" at the end, if we add it
        if ($path[strlen($path)-1] != "/") 
                $path .= "/"; 
  
        if (is_dir($path)) { 
                $d = opendir($path); 
  
                while ($f = readdir($d)) { 
                        if ($f != "." && $f != "..") { 
                                $rf = $path . $f; // path of the php file 
  
                                if (is_dir($rf)) // if it is the directory of the function recursively call
                                        rdel($rf); 
                                else // if you delete the file
                                        unlink($rf); 
                        } 
                } 
                closedir($d); 
  
                if ($deldir) // if $deldir is true you delete the directory
                        rmdir($path); 
        } 
        else { 
                unlink($path); 
        } 
} 
// EOF: XSell

////Bundled Products
function is_bundled($products_id) {
	$bundle_status_query = tep_db_query("SELECT products_bundle FROM " . TABLE_PRODUCTS . " where products_id = " . (int)$products_id);
	$bundle_status = tep_db_fetch_array($bundle_status_query);
	if ($bundle_status['products_bundle'] == "yes")
	  return true;
	else
	  return false;
}
////Restock Bundled products
function bundle_update_restock($products_id, $order_quantity) {
        $qty = 0;
        $product_bundle_query = tep_db_query("select subproduct_id, subproduct_qty, products_stock_attributes, products_stock_id from " . TABLE_PRODUCTS_BUNDLES . ", " . TABLE_PRODUCTS . " where bundle_id = '" . (int)$products_id . "' and  products_id = '" . (int)$products_id . "' and products_bundle = 'yes'");
        while ($product_bundle_data = tep_db_fetch_array($product_bundle_query)) {
          //$is_bundle = 'yes';
          if (tep_not_null($product_bundle_data['products_stock_attributes']) && tep_not_null($product_bundle_data['products_stock_id'])) {
          	if ($product_bundle_data['products_stock_attributes'] != '$$DOWNLOAD$$')
            {
              $attributes_stock_query = tep_db_query("SELECT products_stock_quantity 
                                                      FROM " . TABLE_PRODUCTS_STOCK . " 
                                                      WHERE products_stock_attributes = '" . $product_bundle_data['products_stock_attributes'] . "' 
                                                      AND products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");

              if (tep_db_num_rows($attributes_stock_query) > 0)
              {
                $attributes_stock_values = tep_db_fetch_array($attributes_stock_query);
                  $qty = $order_quantity * $product_bundle_data['subproduct_qty'];
                  tep_db_query("UPDATE " . TABLE_PRODUCTS_STOCK . " 
                                SET products_stock_quantity = products_stock_quantity + '" . (int)$qty . "' 
                                WHERE products_stock_attributes = '" . $product_bundle_data['products_stock_attributes'] . "' 
                                AND products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");
                  $product_stock_adjust = min($qty, $qty+$attributes_stock_values['products_stock_quantity']);
              }
              else
              {
                tep_db_query("INSERT into " . TABLE_PRODUCTS_STOCK . " (products_id, products_stock_attributes, products_stock_quantity) VALUES ('" . (int)$products_id . "', '" . $product_bundle_data['products_stock_attributes'] . "', '" . (int)$qty . "')");
                //$product_stock_adjust = $qty;
              }
            }
          }
          else
          {
          	$qty = $order_quantity * $product_bundle_data['subproduct_qty'];
            //$product_stock_adjust = $order['products_quantity'];
            $product_stock_adjust = $qty;
          }
          tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity + " . (int)$product_stock_adjust . ", products_ordered = products_ordered - " . (int)$qty . " where products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");
        }
}

////Checkout Bundled products - used in tep_checkout_order()
function bundle_update_checkout($products_id, $order_quantity) {
        $qty = 0;
        $product_bundle_query = tep_db_query("select subproduct_id, subproduct_qty, products_stock_attributes, products_stock_id from " . TABLE_PRODUCTS_BUNDLES . ", " . TABLE_PRODUCTS . " where bundle_id = '" . (int)$products_id . "' and products_id = '" . (int)$products_id . "' and products_bundle = 'yes'");
        while ($product_bundle_data = tep_db_fetch_array($product_bundle_query)) {
          //$is_bundle = 'yes';
          if (tep_not_null($product_bundle_data['products_stock_attributes']) && tep_not_null($product_bundle_data['products_stock_id'])) {
          	if ($product_bundle_data['products_stock_attributes'] != '$$DOWNLOAD$$')
            {
              $attributes_stock_query = tep_db_query("SELECT products_stock_quantity 
                                                      FROM " . TABLE_PRODUCTS_STOCK . " 
                                                      WHERE products_stock_attributes = '" . $product_bundle_data['products_stock_attributes'] . "' 
                                                      AND products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");

              if (tep_db_num_rows($attributes_stock_query) > 0)
              {
                $attributes_stock_values = tep_db_fetch_array($attributes_stock_query);
                  $qty = $order_quantity * $product_bundle_data['subproduct_qty'];
                	$checkout_left = $attributes_stock_values['products_stock_quantity'] - (int)$qty;
                	if ($checkout_left < 0 ) {
                		if (!is_nostock($product_bundle_data['subproduct_id']) )
                		  $checkout_left =0;
                	}
                  tep_db_query("UPDATE " . TABLE_PRODUCTS_STOCK . " 
                                SET products_stock_quantity = '" . (int)$checkout_left . "' 
                                WHERE products_stock_attributes = '" . $product_bundle_data['products_stock_attributes'] . "' 
                                AND products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");
                  $product_stock_adjust = $qty;
              }
              else
              {
                tep_db_query("INSERT into " . TABLE_PRODUCTS_STOCK . " (products_id, products_stock_attributes, products_stock_quantity) VALUES ('" . (int)$products_id . "', '" . $product_bundle_data['products_stock_attributes'] . "', '" . (int)$qty . "')");
                $product_stock_adjust = $qty;
              }
            }
          }
          else
          {
          	$qty = $order_quantity * $product_bundle_data['subproduct_qty'];
            //$product_stock_adjust = $order['products_quantity'];
            $product_stock_adjust = $qty;
          }

          $products_left_f = tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'"));
          $products_left = $products_left_f['products_quantity'] - (int)$product_stock_adjust;
        	if ($products_left < 0) {
            if (!is_nostock($product_bundle_data['subproduct_id']))
              $products_left =0;
        	}
          tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$products_left . "', products_ordered = products_ordered - " . (int)$qty . " where products_id = '" . (int)$product_bundle_data['subproduct_id'] . "'");
        }
}

function is_postbyinterlink($order) {
  //if (!preg_match('/' . INTERLINK_LABEL . '/i', $order->totals[3]['title'])) {
  if (trim($order->totals[3]['title']) != INTERLINK_LABEL) {
    if(preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	foreach($order->totals as $totl) {
    		//if (preg_match('/' . INTERLINK_LABEL . '/i', $totl['title'])) {
    		if (trim($totl['title']) == INTERLINK_LABEL) {
    			return true;
    		}
    	}
    }
  }
  else {
  	return true;
  }
  return false;
}

function is_postbyukmail($order) {
  if (!preg_match('/UKMail/i', $order->totals[3]['title'])) {
    if(preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	foreach($order->totals as $totl) {
    		if (preg_match('/UKMail/i', $totl['title'])) {
    			return true;
    		}
    	}
    }
  }
  else {
  	return true;
  }
  return false;
}

function is_postbyups($order) {
  //if (!preg_match('/' . UPS_LABEL . '/i', $order->totals[3]['title'])) {
  if (trim($order->totals[3]['title']) != UPS_LABEL) {
    if(preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	foreach($order->totals as $totl) {
    		if (trim($totl['title']) == UPS_LABEL) {
    			return true;
    		}
    	}
    }
  }
  else {
  	return true;
  }
  return false;
}

function is_postbyrm($order) {
  $is_royalmail_label = false;
  if ( trim($order->totals[3]['title']) == ROYALMAIL_LABEL_STANDARD ||
       trim($order->totals[3]['title']) == ROYALMAIL_LABEL_SPECIAL ||
       trim($order->totals[3]['title']) == ROYALMAIL_LABEL_SPECIAL9 ||
       trim($order->totals[3]['title']) == ROYALMAIL_LABEL_INTERSIGN ) {
    $is_royalmail_label = true;
  }

  if (!$is_royalmail_label) {
    if(preg_match('/GoogleCheckout/i', $order->info['payment_method'])) {
    	foreach($order->totals as $totl) {
    		//if (preg_match('/Royal Mail/i', $totl['title'])) {
    		if ( trim($totl['title']) == ROYALMAIL_LABEL_STANDARD ||
             trim($totl['title']) == ROYALMAIL_LABEL_SPECIAL ||
             trim($totl['title']) == ROYALMAIL_LABEL_SPECIAL9 ||
             trim($totl['title']) == ROYALMAIL_LABEL_INTERSIGN ) {
    			return trim($totl['title']);
    		}
    	}
    }
  }
  else {
  	return trim($order->totals[3]['title']);
  }
  return false;
}

//201512
function is_postbylc($order) {
  if ( trim($order->totals[3]['title']) == LOWCOST_LABEL_STANDARD || 
       trim($order->totals[3]['title']) == INTERLINK_LABEL) {
    return trim($order->totals[3]['title']);
  }
  else {
  	return false;
  }
}

// BOE Access with Level Account (v. 2.2a) for the Admin Area of osCommerce (MS2) 1 of 1
////
// comment below lines to disable this contribution
//Check login and file access
function tep_admin_check_login() {
  global $PHP_SELF, $login_groups_id;
  if (!tep_session_is_registered('login_id')) {
    tep_redirect(tep_href_link(FILENAME_LOGIN_ADMIN, '', 'SSL'));
  } else {
    $filename = basename( $PHP_SELF );
    if ($filename != FILENAME_DEFAULT && $filename != FILENAME_FORBIDDEN && $filename != FILENAME_LOGOFF_ADMIN && $filename != FILENAME_ADMIN_ACCOUNT && $filename != FILENAME_POPUP_IMAGE && $filename != 'packingslip.php' && $filename != 'invoice.php') {
      $db_file_query = tep_db_query("select admin_files_name from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_name = '" . $filename . "'");
      if (!tep_db_num_rows($db_file_query)) {
        tep_redirect(tep_href_link(FILENAME_FORBIDDEN));
      }
    }
  }
}

////
//Return 'true' or 'false' value to display boxes and files in index.php and column_left.php
function tep_admin_check_boxes($filename, $boxes='') {
  global $login_groups_id;

  $is_boxes = 1;
  if ($boxes == 'sub_boxes') {
    $is_boxes = 0;
  }
  $dbquery = tep_db_query("select admin_files_id from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_is_boxes = '" . $is_boxes . "' and admin_files_name = '" . $filename . "'");

  $return_value = false;
  if (tep_db_num_rows($dbquery)) {
    $return_value = true;
  }
  return $return_value;
}

////
//Return files stored in box that can be accessed by user
function tep_admin_files_boxes($filename, $sub_box_name, $parameters = '') {
  global $login_groups_id;
  $sub_boxes = '';

  $dbquery = tep_db_query("select admin_files_name from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_is_boxes = '0' and admin_files_name = '" . $filename . "'");
  if (tep_db_num_rows($dbquery)) {
    $sub_boxes = '<a href="' . tep_href_link($filename, $parameters) . '" class="menuBoxContentLink">' . $sub_box_name . '</a><br />';
  }

  return $sub_boxes;
}


function tep_admin_files_boxes_234($filename, $sub_box_name, $parameters = '') {
  global $login_groups_id;
  $sub_boxes = '';

  $dbquery = tep_db_query("select admin_files_name from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_is_boxes = '0' and admin_files_name = '" . $filename . "'");

  if (tep_db_num_rows($dbquery)) {
    //$sub_boxes = '<a href="' . tep_href_link($filename, $parameters) . '" class="menuBoxContentLink">' . $sub_box_name . '</a><br />';
    return array('code' => $filename,
                 'title' => $sub_box_name,
                 'link' => tep_href_link($filename, $parameters));
  }
  return null;
  
}

////
//Get selected file for index.php
function tep_selected_file($filename) {
  global $login_groups_id;
  $randomize = FILENAME_ADMIN_ACCOUNT;

  $dbquery = tep_db_query("select admin_files_id as boxes_id from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_is_boxes = '1' and admin_files_name = '" . $filename . "'");
  if (tep_db_num_rows($dbquery)) {
    $boxes_id = tep_db_fetch_array($dbquery);
    $randomize_query = tep_db_query("select admin_files_name from " . TABLE_ADMIN_FILES . " where FIND_IN_SET( '" . $login_groups_id . "', admin_groups_id) and admin_files_is_boxes = '0' and admin_files_to_boxes = '" . $boxes_id['boxes_id'] . "'");
    if (tep_db_num_rows($randomize_query)) {
      $file_selected = tep_db_fetch_array($randomize_query);
      $randomize = $file_selected['admin_files_name'];
    }
  }
  return $randomize;
}
// EOF Access with Level Account (v. 2.2a) for the Admin Area of osCommerce (MS2) 1 of 1

//used in stas_countries.php
function createDateRangeArray($strDateFrom,$strDateTo) {
  // takes two dates formatted as YYYY-MM-DD and creates an inclusive array of the dates between the from and to dates. could test validity of dates here but I'm already doing that in the main script
  $aryRange=array();
  $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),     substr($strDateFrom,8,2),substr($strDateFrom,0,4));
  $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),     substr($strDateTo,8,2),substr($strDateTo,0,4));
  if ($iDateTo>=$iDateFrom) {
    array_push($aryRange,date('Y-m-d',$iDateFrom)); // first entry
    while ($iDateFrom<$iDateTo) {
      $iDateFrom+=86400; // add 24 hours
      array_push($aryRange,date('Y-m-d',$iDateFrom));
    }
  }
  return $aryRange;
}

// rmh referral
  function tep_get_sources_name($source_id, $customers_id) {

    if ($source_id == '9999') {
      $sources_query = tep_db_query("select sources_other_name as sources_name from " . TABLE_SOURCES_OTHER . " where customers_id = '" . (int)$customers_id . "'");
    } else {
      $sources_query = tep_db_query("select sources_name from " . TABLE_SOURCES . " where sources_id = '" . (int)$source_id . "'");
    }

    if (!tep_db_num_rows($sources_query)) {
      if ($source_id == '9999') {
        return TEXT_OTHER;
      } else {
        return TEXT_NONE;
      }
    } else {
      $sources = tep_db_fetch_array($sources_query);
      return $sources['sources_name'];
    }
  }

  //Display Attributes name with a given raw string from DB
  function display_att($rawstr)
  {
  	if (!tep_not_null($rawstr)) return null;
  	global $languages_id;
    	 //display the actual opt_val
    	 $opt_val = explode(',', $rawstr);
    	 $attributes="";
    	 foreach($opt_val as $val)
       {
       	 $opt_optval=explode('-', $val,2);
	       $opt_q = tep_db_query("select products_options_name, products_options_track_stock from " . TABLE_PRODUCTS_OPTIONS . " where products_options_id = '" . $opt_optval[0] . "' and language_id = '" . (int)$languages_id . "'");
	       $opt = tep_db_fetch_array($opt_q);
	       $attributes .= "|".$opt['products_options_name'] . "=";
	       $optval_q = tep_db_query("select products_options_values_name from " . TABLE_PRODUCTS_OPTIONS_VALUES . " where products_options_values_id = '" . $opt_optval[1] . "' and language_id = '" . (int)$languages_id . "'");
	       $o_val = tep_db_fetch_array($optval_q);
	       $attributes .= $o_val['products_options_values_name'];
	       //For debug use: $attributes .= "&nbsp;".$val . "&nbsp;(track: " . $opt['products_options_track_stock'] . ")";
       }
       return substr($attributes, 1);
  }

// BOE: Attribute Sort with Clone Tool
  function tep_attributes_sort($attributes_id) {
    //global $languages_id;

    $attributes_sort = tep_db_query("select products_options_sort_order from " . TABLE_PRODUCTS_ATTRIBUTES . " where products_attributes_id = '" . (int)$attributes_id . "'");
    $attributes_sort_values = tep_db_fetch_array($attributes_sort);

    return $attributes_sort_values['products_options_sort_order'];
  }
// EOE: Attribute Sort with Clone Tool


//My modification: Used for redirect.php banner manager
function get_adv_redirect($banners_url, $website_url) {
	if (strstr($banners_url, $website_url) != false && strstr($banners_url, "products_id") != false) {
    //local category link
    $pos1 = stripos($banners_url, "products_id");
    $pidstr = substr($banners_url, $pos1);

    if (strstr($pidstr,"=") !=false) {
      //tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, $pidstr));
      return $pidstr;
    }
    elseif (strstr($pidstr,"/") !=false) {
    	$pidstr_arr = explode("/", $pidstr);
    	//tep_redirect(tep_href_link(FILENAME_PRODUCT_INFO, $pidstr_arr[0] ."=". $pidstr_arr[1]));
    	return $pidstr_arr[0] ."=". $pidstr_arr[1];
    }
  }
  elseif (strstr($banners_url, $website_url) != false && strstr($banners_url, "cPath") != false) {
    //product, special, etc..
    $pos1 = stripos($banners_url, "cPath");
    $cpathstr = substr($banners_url, $pos1);

    if (strstr($cpathstr,"=") !=false) {
      //tep_redirect(tep_href_link(FILENAME_DEFAULT, $cpathstr));
      return $cpathstr;
    }
    elseif (strstr($cpathstr,"/") !=false) {
    	$cpathstr_arr = explode("/", $cpathstr);
    	//tep_redirect(tep_href_link(FILENAME_DEFAULT, $cpathstr_arr[0] ."=". $cpathstr_arr[1]));
    	return $cpathstr_arr[0] ."=". $cpathstr_arr[1];
    }
  }
  elseif (strstr($banners_url, $website_url) != false && strstr($banners_url, "manufacturers_id") != false) {
    //product, special, etc..
    $pos1 = stripos($banners_url, "manufacturers_id");
    $cpathstr = substr($banners_url, $pos1);

    if (strstr($cpathstr,"=") !=false) {
      //tep_redirect(tep_href_link(FILENAME_DEFAULT, $cpathstr));
      return $cpathstr;
    }
    elseif (strstr($cpathstr,"/") !=false) {
    	$cpathstr_arr = explode("/", $cpathstr);
    	//tep_redirect(tep_href_link(FILENAME_DEFAULT, $cpathstr_arr[0] ."=". $cpathstr_arr[1]));
    	return $cpathstr_arr[0] ."=". $cpathstr_arr[1];
    }
  }
  elseif (strstr($banners_url, $website_url) != false) {
  	$str = substr($banners_url, strlen($website_url));
  	//tep_redirect(tep_href_link($str));
  	return $str;
  }
  else {
  	//tep_redirect($banners_url);
  	return $banners_url;
  }
}

function is_cashcarry() {
	//global $HTTP_GET_VARS;
	if (tep_session_is_registered('cash_carry')) {
		return true;
	}
	elseif (isset($HTTP_GET_VARS['cash_carry'])) {
		return true;
	}
	else
	  return false;
}

//My modification - rmtrack
/*function switch_linksdb($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link')
{
	tep_db_close();
	tep_db_connect($server, $username, $password, $database, $link) or die('Unable to connect to database server!');
}*/

function get_descendants_cat($dacat) {
	global $language, $cached_root_categories;
	if (!isset($cached_root_categories)) {
	  if (file_exists(DIR_FS_CACHE . 'home_root_categories-' . $language . ".cache" . ".php")) {
		  include(DIR_FS_CACHE . 'home_root_categories-' . $language . ".cache" . ".php");
	  }
	  if (isset($cached_root_categories) && array_key_exists($dacat, $cached_root_categories)) {
		  if (sizeof($cached_root_categories[$dacat])) return $cached_root_categories[$dacat];
	  }
  }
  else {
  	if (sizeof($cached_root_categories[$dacat])) return $cached_root_categories[$dacat];
  }

	$cat_all = array();
  $parent_query = tep_db_query("select categories_id from ".TABLE_CATEGORIES." where parent_id = '". (int)$dacat. "' and categories_status='1' order by categories_id");
  if (tep_db_num_rows($parent_query)>0) {
    while ($level1 = tep_db_fetch_array($parent_query)) {
    	if (tep_has_category_subcategories($level1['categories_id'])) {
    		$cat_all=array_merge($cat_all, get_descendants_cat($level1['categories_id']));
    	}
    	else {
    		$cat_all[] = $level1['categories_id'];
    	}
    }
  }
  else {
  	$cat_all[] = $dacat;
  }
  return $cat_all;
}

//My modification - get sagepay info from table protx_direct table
//sagepay_risk_check - used in /admin/orders.php
    function sagepay_risk_check($oid) {
    	$sagepay_risk_query = tep_db_query("select customer_id, order_id, vendortxcode, txtype, value, status, statusdetail, txauthno, securitykey, avscv2, address_result, postcode_result, CV2_result, 3DSecureStatus, CAVV, txtime, delivery_name, delivery_company, delivery_house_name, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_house_name, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, currency_value, customers_email_address from " . TABLE_PROTX_DIRECT . " spd, " . TABLE_ORDERS . " o where order_id ='" . (int)$oid . "' and spd.order_id=o.orders_id and status = 'OK'");

    	if (tep_db_num_rows($sagepay_risk_query) >0) {
    		$sagepay_risk = tep_db_fetch_array($sagepay_risk_query);
    		//check the risk here
    		if (tep_not_null($sagepay_risk['CAVV'])) {
    			//3D full - low risk
    			$riskinfo = ""; //"low risk";
    		}
    		elseif ($sagepay_risk['CV2_result'] == "MATCHED" && ($sagepay_risk['value']/$sagepay_risk['currency_value']) < 30) {
    			//cv2 match and value < 30
    		  	$riskinfo = ""; //"low risk";
    		}
    		elseif ($sagepay_risk['avscv2'] == "ALL MATCH" && $sagepay_risk['address_result'] == "MATCHED" && $sagepay_risk['postcode_result'] == "MATCHED" && $sagepay_risk['CV2_result'] == "MATCHED"){
    			//all matched and billing address = shipping address
    			if ($sagepay_risk['delivery_house_name'] == $sagepay_risk['billing_house_name'] &&
    			    $sagepay_risk['delivery_street_address'] == $sagepay_risk['billing_street_address'] &&
    			    $sagepay_risk['delivery_city'] == $sagepay_risk['billing_city'] &&
    			    $sagepay_risk['delivery_postcode'] == $sagepay_risk['billing_postcode'] &&
    			    $sagepay_risk['delivery_country'] == $sagepay_risk['billing_country']) {
    			  $riskinfo = ""; //"low risk";
    			}
    		  else {
    		  	//medium risk - need further check
    		  	if (($sagepay_risk['value']/$sagepay_risk['currency_value']) < 40)
    		  	  $riskinfo = ""; //"low risk";
    		  	elseif (($sagepay_risk['value']/$sagepay_risk['currency_value']) < 80)
    		  	  $riskinfo = 'medium'; //tep_image(DIR_WS_ICONS . 'sagepay_mediumrisk.gif', "medium need further check");
    		  	else
    		  	  $riskinfo = 'high - please go through order'; //tep_image(DIR_WS_ICONS . 'sagepay_highrisk.gif', "high need further check");
    		  }
    		}
    		elseif ($sagepay_risk['address_result'] != "MATCHED" && $sagepay_risk['postcode_result'] != "MATCHED") {
    			//address & postcode not match & value > 100 pounds
    		  if ($sagepay_risk['value']/$sagepay_risk['currency_value'] >= 70) {
    		  	$riskinfo = 'high - please go through order';
    		  }
    		  else {
    		  	//medium risk - need further check
    		  	$riskinfo = 'medium';
    		  }
    		}
    		else {
    			//medium risk - need further check
    			$riskinfo = 'medium';
    		}
    	}
    	else {
    		//other payment method
    		$riskinfo ="";
    	}
    	////My modification - added on 09/10/12
    	if (tep_not_null($riskinfo)) {
        $check_regular_q = tep_db_query("select orders_id, date_purchased, orders_status from " . TABLE_ORDERS . " where customers_id='" . (int)$sagepay_risk['customer_id'] . "' and orders_id != '" . (int)$oid . "' order by orders_id desc");
        $check_regular_n = tep_db_num_rows($check_regular_q);
        if ($check_regular_n >7) {
        	$riskinfo ="";
        }
        elseif ($check_regular_n >=3) {
        	$riskinfo = ""; //"low risk";
          //20131210
          //>= 3 orders within 3 days(high) or >= 3 orders within 30 days(medium)
          while ($check_p = tep_db_fetch_array($check_regular_q)) {
            $time_range[] =$check_p['date_purchased'];
          }
          $earliest = $time_range[0];
          $latest = $time_range[sizeof($time_range)-1];

          $diff = abs(strtotime($latest) - strtotime($earliest));
          $years = floor($diff / (365*60*60*24));
          $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
          $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
          if ($months<1 && $years<1 ) {
              if ($days <=7) {
                //within 7 days
                $riskinfo = 'high - please go through order';
              }
              elseif ($days <=30) {
                //within 30 days
                $riskinfo = 'medium';
              }
          }
          //20131210
        }
        elseif ($check_regular_n >0 && $check_regular_n <3) {
        	//check if last order is chargedback
        	$check_regular = tep_db_fetch_array($check_regular_q);
        	$risk_array = array(4,6,7,8,9,11,13,103,104,105); //high risk order status
        	if (in_array($check_regular['orders_status'], $risk_array)) {
        	  $riskinfo = 'high - please go through order';
        	}
        	else {
        		$riskinfo = 'medium';
        	}
        }
        else {
        	//check postcode if someone from same address (postcode and house number)ordered before
        	//$check_regular_q = tep_db_query("select orders_id, customers_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, orders_status from " . TABLE_ORDERS . " where delivery_postcode='" . tep_db_input($sagepay_risk['delivery_postcode']) . "' and delivery_street_address='" . tep_db_input($sagepay_risk['delivery_street_address']) . "' and orders_id != '" . (int)$oid . "' and orders_status in (3) order by orders_id desc");
        	//$check_regular_q = tep_db_query("select orders_id, customers_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, orders_status, date_purchased from " . TABLE_ORDERS . " where delivery_postcode='" . tep_db_input($sagepay_risk['delivery_postcode']) . "' and delivery_street_address='" . tep_db_input($sagepay_risk['delivery_street_address']) . "' and orders_id != '" . (int)$oid . "' order by orders_id desc");

        	if (($sagepay_risk['value']/$sagepay_risk['currency_value']) < 30) {
    			  //order value < 30
    		  	$riskinfo = ""; //"low risk";
    		  }
        	/*elseif (tep_db_num_rows($check_regular_q)>3) {
        		$riskinfo = ""; //"low risk";
        	}*/
        	elseif ($sagepay_risk['delivery_country'] != $sagepay_risk['billing_country']) {
        		$riskinfo = 'high - please go through order';
        	}
        	else {
        		//check order amount
        		//$check_amount = tep_db_fetch_array(tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id='" . (int)$oid . "' and class = 'ot_total'"));
        		if (($sagepay_risk['value']/$sagepay_risk['currency_value'])<150) {
        			//check northern europe
        		  $lowrisk_country = array('Denmark','Finland','Norway','Sweden','Iceland');
        		  if (in_array($sagepay_risk['delivery_country'], $lowrisk_country)) {
        		    $riskinfo = ""; //"low risk";
        		  }
        		}
        	}
        }

      }
      ////My modification - added on 09/10/12

/*
      //20131210
      //same delivery postcode 
      //>= 3 orders within 3 days(high) or >= 3 orders within 30 days(medium)
      $check_p_q = tep_db_query("select orders_id, orders_status, date_purchased from " . TABLE_ORDERS . " where delivery_postcode='" . tep_db_input($sagepay_risk['delivery_postcode']) . "' order by date_purchased");
      $check_p_n = tep_db_num_rows($check_p_q);
      $count_p_n = 0;
      $time_range = array();
      while ($check_p = tep_db_fetch_array($check_p_q)) {
      	$time_range[] =$check_p['date_purchased'];
      }
      $earliest = $time_range[0];
      $latest = $time_range[sizeof($time_range)-1];

      if ($check_p_n >=3) {
      	$diff = abs(strtotime($latest) - strtotime($earliest));
        $years = floor($diff / (365*60*60*24));
        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
        $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));

        if ($months<1 && $years<1 ) {
          if ($days <=3) {
            //>= 3 orders within 3 days
            $riskinfo = tep_image(DIR_WS_ICONS . 'sagepay_highrisk.gif', "high need further check");
          }
          elseif ($days <=30) {
            //>= 3 orders within 30 days
            $riskinfo = tep_image(DIR_WS_ICONS . 'sagepay_mediumrisk.gif', "medium need further check");
          }
        }
      }
      //20131210
*/
    	return $riskinfo;
    }



//My modification - 
  function tep_get_languages_directory($code) {
    global $languages_id;

    $language_query = tep_db_query("select languages_id, directory from " . TABLE_LANGUAGES . " where code = '" . tep_db_input($code) . "'");
    if (tep_db_num_rows($language_query)) {
      $language = tep_db_fetch_array($language_query);
      $languages_id = $language['languages_id'];
      return $language['directory'];
    } else {
      return false;
    }
  }

////My modification, check the lowest price for product listing
function lowest_price_listing($listing,$lc_text="", $currencies)
{//$listing is an array from $listing_sql
    ////My modification, check the lowest price
    //global $currencies, $languages_id;
    global $languages_id;
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
    		/*if (tep_not_null($listing['products_market_price']) && $listing['products_market_price'] > $listing['products_price']) {
    			$market_price = "Market Price: " . $currencies->display_price($listing['products_market_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '<br />Our Price: ';
    		}
    		else {
    			$market_price = "";
    		}
    		$temp_price = $market_price . $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id']));*/
        if (tep_not_null($listing['products_market_price']) && $listing['products_market_price'] > $listing['products_price']) {
    		  $temp_price = '<table border="0" width="' . (SMALL_IMAGE_WIDTH-2) . '" cellspacing="0" cellpadding="0"><tr><td class="smallText">Market Price:</td><td class="smallText">'.$currencies->display_price($listing['products_market_price'], tep_get_tax_rate($listing['products_tax_class_id'])).'</td></tr><tr><td class="main"><strong>Our Price:</strong></td><td class="main"><strong>'.$currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])).'</strong></td></tr></table>';
    	  }
    	  else {
    	  	$temp_price = "<span style=\"font-weight:normal\">".$currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . "</span>";
    	  }
    	}
    	else $temp_price= '<span style="font-weight:normal">From ' . $currencies->display_price_nodiscount($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . "</span>";
    }
    else
    {	
    	if (!is_gv_products($listing['products_id']))
    	  $temp_price = '<span style="font-weight:normal">From ' . $currencies->display_price($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . '</span>';
    	else
    	  $temp_price = '<span style="font-weight:normal">From ' . $currencies->display_price_nodiscount($lowest, tep_get_tax_rate($listing['products_tax_class_id'])) . '</span>';
    }
    if (tep_not_null($listing['specials_new_products_price']))
    {
    	if ($check_pricing_categories=="single")
    	{
    		if (tep_not_null($listing['products_market_price']) && $listing['products_market_price'] > $listing['products_price']) {
    			$lc_text = '<s>' .  $currencies->display_price($listing['products_market_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</s>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price_nodiscount($listing['specials_new_products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</span>';
    		}
    		else {
    			$lc_text = '<s>' .  $currencies->display_price($listing['products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</s>&nbsp;&nbsp;<span class="productSpecialPrice">' . $currencies->display_price_nodiscount($listing['specials_new_products_price'], tep_get_tax_rate($listing['products_tax_class_id'])) . '</span>';
    		}
      }
      else
      { $lc_text = '<s>'.$temp_price.'</s>&nbsp;<span class="productSpecialPrice"><strong>Special Offer!</strong></span>';
      }
    }
    else
    { $lc_text = $temp_price . ' <span style="font-weight:normal;">Inc.VAT</a>';//$lc_text = $temp_price . '<br />Inc. VAT';
    }
    return $lc_text;
}

//My modification - validating uk postcode
function checkPostcode($toCheck, $country_iso2_code) {
	if ($country_iso2_code !="GB") {
		return ture;
	}
  // Permitted letters depend upon their position in the postcode.
  $alpha1 = "[abcdefghijklmnoprstuwyz]";                          // Character 1
  $alpha2 = "[abcdefghklmnopqrstuvwxy]";                          // Character 2
  $alpha3 = "[abcdefghjkpmnrstuvwxy]";                            // Character 3
  $alpha4 = "[abehmnprvwxy]";                                     // Character 4
  $alpha5 = "[abdefghjlnpqrstuwxyz]";                             // Character 5
  
  // Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA with a space
  $pcexp[0] = '/^('.$alpha1.'{1}'.$alpha2.'{0,1}[0-9]{1,2})([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$/';
  // Expression for postcodes: ANA NAA
  $pcexp[1] =  '/^('.$alpha1.'{1}[0-9]{1}'.$alpha3.'{1})([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$/';
  // Expression for postcodes: AANA NAA
  $pcexp[2] =  '/^('.$alpha1.'{1}'.$alpha2.'{1}[0-9]{1}'.$alpha4.')([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$/';
  // Exception for the special postcode GIR 0AA
  $pcexp[3] =  '/^(gir)(0aa)$/';
  // Standard BFPO numbers
  $pcexp[4] = '/^(bfpo)([0-9]{1,4})$/';
  // c/o BFPO numbers
  $pcexp[5] = '/^(bfpo)(c\/o[0-9]{1,3})$/';
  // Overseas Territories
  $pcexp[6] = '/^([a-z]{4})(1zz)$/i';
  // Load up the string to check, converting into lowercase
  $postcode = strtolower($toCheck);

  // Assume we are not going to find a valid postcode
  $valid = false;
  
  // Check the string against the six types of postcodes
  foreach ($pcexp as $regexp) {
    if (preg_match($regexp,$postcode, $matches)) {
      // Load new postcode back into the form element  
		  $postcode = strtoupper ($matches[1] . ' ' . $matches [3]);
      // Take account of the special BFPO c/o format
      $postcode = preg_replace ('/C\/O/', 'c/o ', $postcode);
      // Remember that we have found that the code is valid and break from loop
      $valid = true;
      break;
    }
  }
  // Return with the reformatted valid postcode in uppercase if the postcode was 
  // valid
  if ($valid) {
	  $toCheck = $postcode; 
		return true;
	} 
	else return false;
}

// Function to reset SEO URLs database cache entries
// Ultimate SEO URLs v2.2d
function tep_reset_cache_data_seo_urls($action) {
  switch ($action){
    case 'reset':
    case 'uninstall':
       tep_db_query("DELETE FROM cache WHERE cache_name LIKE '%seo_urls%'");
       tep_db_query("UPDATE configuration SET configuration_value='false' WHERE configuration_key='SEO_URLS_CACHE_RESET'");

       if ($action == 'reset') break;
    
       tep_db_query("DELETE FROM configuration_group WHERE configuration_group_title LIKE '%seo_urls%'");
	     tep_db_query("DELETE FROM configuration WHERE configuration_key LIKE 'SEO%' OR configuration_key LIKE 'USE_SEO%'");
    break;    
    default:
    break;
  }
  # The return value is used to set the value upon viewing
  # It's NOT returining a false to indicate failure!!
  return 'false';
}


//Use for Gift Voucher to determine whether it's a Gift Voucher product integrate with CGDiscountSpecials
//If it's a GV product then no discount even the customer has discount
//Currently the gv products are multiple priced and have attributes and use attributes prices
function is_gv_products($pid) {
	if (get_products_categories_id($pid)==176)
	  return true;
	else
	  return false;
}

//Colour ring product pad_colorring.dropdowns.php module, used in product_info.php, general.php
function colourring_model($product_model) {
	//check if it is the colour ring sample product
	if ($product_model != "Colour Sample")
		return false;
	else
	  return true;
}

function coloursample_pid() {
	return 288;
}

function is_coloursample_pid($pid) {
	if ((int)$pid == coloursample_pid())
	  return true;
	else
	  return false;
}


//stock_prebonded.php popup_stock_update.php
//temp for 6 months
/*
function update_prebonded_qty($product_id, $opt_optval, $qty)
{
	//e.g. $opt_optval = "5-32"
	if (empty($product_id) || empty($opt_optval) || !is_numeric($qty))
	  return;
	//get all the strand units option values
	$q_strands=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$product_id . " and products_stock_attributes like '" . $opt_optval . "%' order by products_stock_attributes");
	while($strand=tep_db_fetch_array($q_strands))
	{
    switch ($strand[products_stock_attributes])
    {
    	//14" strand units
      case '5-32,8-451' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$qty . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-32,8-452' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-32,8-453' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-32,8-454' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-32,8-455' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-32,8-456' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-32,8-457' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-32,8-458' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands

    	//18" strand units
      case '5-34,8-54' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$qty . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-34,8-55' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-34,8-56' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-34,8-57' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-34,8-58' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-34,8-59' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-34,8-60' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-34,8-61' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands

      //22" strand units
      case '5-38,8-62' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$qty . " where products_stock_id='".$strand['products_stock_id']."'"); break; //25 strands
      case '5-38,8-63' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/2)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //50 strands
      case '5-38,8-64' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/3)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //75 strands
      case '5-38,8-65' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/4)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //100 strands
      case '5-38,8-66' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/5)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //125 strands
      case '5-38,8-67' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/6)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //150 strands
      case '5-38,8-68' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/7)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //175 strands
      case '5-38,8-69' : tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . ((int)floor($qty/8)) . " where products_stock_id='".$strand['products_stock_id']."'"); break; //200 strands
    }
	}
}
*/

function updatesummaryQty($products_id, $isPrebonded=false, $single=false)
{
  if ($single)
  {
  	//tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity=" . (int)$VARS['quantity'] . " where products_id=" . (int)$VARS['product_id']);
    //if (($VARS['quantity']<1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
    //  tep_db_query("update " . TABLE_PRODUCTS . " set products_status='0' where products_id=" . (int)$VARS['product_id']);
    //}
  }
  else
  {
  	if ($isPrebonded)
	  {
		  $q=tep_db_query("select sum(products_stock_quantity) as summa from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$products_id . " and products_stock_quantity>0 and (products_stock_attributes='5-32,8-451' or products_stock_attributes='5-38,8-62' or products_stock_attributes='5-34,8-54')");
      $list=tep_db_fetch_array($q);
      $summa= (empty($list['summa'])) ? 0 : $list['summa'];
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity='" . (int)$summa . "' where products_id=" . (int)$products_id);
      /*if (($summa<1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
        tep_db_query("update " . TABLE_PRODUCTS . " set products_status='0' where products_id=" . (int)$products_id);
      }*/
	  }
	  elseif (!$single)
	  {
      $q=tep_db_query("select sum(products_stock_quantity) as summa from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$products_id . " and products_stock_quantity>0");
      $list=tep_db_fetch_array($q);
      $summa= (empty($list['summa'])) ? 0 : $list['summa'];
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity='" . (int)$summa . "' where products_id=" . (int)$products_id);
      /*if (($summa<1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
          tep_db_query("update " . TABLE_PRODUCTS . " set products_status='0' where products_id=" . (int)$products_id);
      }*/
    }
  }
}

  function tep_parse_path($categories_id) {
  	$cid_q = tep_db_query("select categories_id from " . TABLE_CATEGORIES ." where categories_id='" . (int)$categories_id . "'");
  	if (tep_db_num_rows($cid_q)>0) {
      $cPath = '';
      $categories = array();
      tep_get_parent_categories($categories, $categories_id);
      $categories = array_reverse($categories);
      $cPath = implode('_', $categories);
      if (tep_not_null($cPath)) $cPath .= '_';
      $cPath .= $categories_id;
      return $cPath;
    }
    else {
    	return '';
    }
  }

//20140306 - royalmail prohibited
function get_orders_prohibited_items($order) {
	$p_pid_cond = " where products_id in (";
  for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
  	$p_pid_cond .= $order->products[$i]['products_id'] . ",";
  }
  $p_pid_cond = substr($p_pid_cond, 0, -1) . ")";
  
  $prohibited_q = tep_db_query("select products_id from " . TABLE_PROHIBITED_RM . $p_pid_cond);//echo "select prohibited_rm_id from " . TABLE_PROHIBITED_RM . $p_pid_cond;
  $pg_array = array();
	while ($prohibited = tep_db_fetch_array($prohibited_q)) {
		$pg_array[] = $prohibited['products_id'];
	}
  return $pg_array;
}
//20140306 - royalmail prohibited

//My modification - disable some option if the login is not admin group
function is_disalbed_login() {
	global $admin;

	if ($admin['login_groups_id']>2) return true;
	else return false;
}

//201512
function pv_slave_value($parent_id=0, $products_id=0) {
  if ((int)$parent_id>0) {
    $display_str ="";
    $products_s_q = tep_db_query("select opv.products_variants_values_id, opv.default_combo, opvv.products_variants_groups_id, opvv.title as vtitle, opvv.image, opvv.sort_order as vorder, opvg.title as gtitle, opvg.sort_order as gorder from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id where opv.products_id='" . (int)$products_id . "' order by opvg.sort_order, opvv.sort_order");
    while ($products_s = tep_db_fetch_array($products_s_q)) {
      $display_str .= $products_s['vtitle'] . " ";
    }
    return trim($display_str);
  }
  else return null;
}

function pv_slave_name($parent_id=0) {
	global $languages_id;
	$parent_info = tep_db_fetch_array(tep_db_query("select pd.products_name, p.products_model, pi.image_filename from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_IMAGES . " pi on p.products_id = pi.products_id and pi.product_page = '1', " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$parent_id . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'"));
	return $parent_info;
}

function pv_variants($products_id) {
  $ps_pv_a = array();
  $products_s_q = tep_db_query("select opv.products_variants_values_id, opvv.products_variants_groups_id, opvv.title as vtitle, opvv.sort_order as vorder, opvg.title as gtitle, opvg.sort_order as gorder from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id where opv.products_id='" . (int)$products_id . "' order by opvg.sort_order");
  while ($products_s = tep_db_fetch_array($products_s_q)) {
    $ps_pv_a[] = array('group' => $products_s['gtitle'], 'value' => $products_s['vtitle'], 'group_id' => $products_s['products_variants_groups_id'], 'value_id' => $products_s['products_variants_values_id']);
  }
  return $ps_pv_a;
}

function pv_amount_unit() { //100g/150g
	return array(14 => array(183=> 1, 184=> 2, 185 => 3));
}

function pv_amount_unit_shift($unit) {
  foreach ($unit[key($unit)] as $k => &$v) {
		if ($v == 1)
		  unset($unit[key($unit)][$k]);
	}
	return $unit;
}

function pv_is_notbase_amount($product_id=0) {
	//check if it's 100g/150g
	$unit_array = pv_amount_unit_shift(pv_amount_unit());

  $unit_not_base_a = reset($unit_array);
  $is_notbase = false;
	$products_ms_q = tep_db_query("select opv.products_id, opvv.products_variants_groups_id, opv.products_variants_values_id from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_PRODUCTS . " p on opv.products_id=p.products_id left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.products_id='" . (int)$product_id . "' order by opvg.sort_order, opvv.sort_order");
	while ($products_ms = tep_db_fetch_array($products_ms_q)) {
    if (array_key_exists($products_ms['products_variants_groups_id'],$unit_array)) {
      if (array_key_exists($products_ms['products_variants_values_id'], $unit_not_base_a)) {
        $is_notbase = true;
        break;
      }
    }
	}
	return $is_notbase;
}

function pv_update_notbase_amount($products_id=0, $parent_id=0, $qty=0) {
	$is_unitpack = false;
  $unit_array = pv_amount_unit();//array(19 => array(17=>1,18=>2,19=>3));
  $first_temp = reset($unit_array);
  $base_unit = array(key($first_temp) => reset($first_temp));

  $current_product = array();
  $products_ms_a =array();
  $is_notbase_amount_a = array();

	$products_ms_q = tep_db_query("select opv.products_id, opvv.products_variants_groups_id, opv.products_variants_values_id from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_PRODUCTS . " p on opv.products_id=p.products_id left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.parent_id='" . (int)$parent_id . "' order by opvg.sort_order, opvv.sort_order");
  while ($products_ms = tep_db_fetch_array($products_ms_q)) {
    $products_ms_a[][$products_ms['products_id']] =array($products_ms['products_variants_groups_id'] => $products_ms['products_variants_values_id']); //array(759 => array(2 => 1))
    if ($products_ms['products_id'] == $products_id) {
    	if (array_key_exists($products_ms['products_variants_groups_id'],$unit_array)) {
    		if (!array_key_exists($products_ms['products_variants_values_id'], $base_unit)) {
    		  $is_notbase_amount_a[$products_ms['products_variants_groups_id']] = $products_ms['products_variants_values_id'];
    		}
    		$is_unitpack = true;
    	}
      else $current_product[$products_ms['products_variants_groups_id']] = $products_ms['products_variants_values_id'];
    }
  }
  
  if (!$is_unitpack) return null;

//echo print_r($is_notbase_amount_a). " " .$products_id;exit;
  if (sizeof($is_notbase_amount_a)) { //restock current product is 100g/150g => update 50g and one of 100g/150g
  	//$current_p_pv = $current_product + $is_notbase_amount_a;  //format Array ( [2] => 24 [1] => 8 [19] => 18 )
    $current_unit_amount = $unit_array[key($unit_array)][$is_notbase_amount_a[key($is_notbase_amount_a)]];

    $otherpv_base = $current_product + array(key($unit_array) => key($base_unit));
    $pvid_a = array();
		foreach($products_ms_a as $k1 => $v1) {
			$ms_g = key($v1[key($v1)]);$ms_v = $v1[key($v1)][$ms_g];
			if (array_key_exists($ms_g, $otherpv_base) && $otherpv_base[$ms_g] == $ms_v) {
				$pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
			}
		}

    if (sizeof($pvid_a)>1)
      $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>17)
    else
      $pid_of_an_amount_a = reset($pvid_a);
		$pid_of_an_amount = reset($pid_of_an_amount_a); //base unit product

    $actual_stock_bought = $qty * $current_unit_amount;
//echo print_r(reset($unit_array));exit;//Array ( [19] => Array ( [17] => 1 [18] => 2 [19] => 3 ) ) 1
    tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET products_quantity = products_quantity + " . (int)$actual_stock_bought . ", products_ordered = products_ordered - " . (int)$actual_stock_bought . " WHERE products_id = '" . (int)$pid_of_an_amount . "'");

   	$stock_values_of_base = tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$pid_of_an_amount . "'"));
		if (!tep_not_null($stock_values_of_base['products_quantity'])) $stock_values_of_base['products_quantity'] =0;
		
	  $otherunit_a = reset($unit_array);
	  
	  foreach ($otherunit_a as $k => $v) { //Array ( [17] => 1 [18] => 2 [19] => 3 ) )
	  	if (array_key_exists($k, $base_unit)) continue;

      $otherpv_temp = $current_product + array((int)key($unit_array) => (int)$k); //array(2 => 24, 1 => 8, 19=>18) OR array(2 => 24, 1 => 8, 19=>19);

      $pvid_a = array();
		  foreach($products_ms_a as $k1 => $v1) {
		  	$ms_g = key($v1[key($v1)]); $ms_v = $v1[key($v1)][$ms_g];
		  	if (array_key_exists($ms_g, $otherpv_temp) && $otherpv_temp[$ms_g] == $ms_v) {
				  $pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
		  	}
	  	}
      if (sizeof($pvid_a)>1)
        $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>18) or array(2 => 24, 1 => 8, 19=>19)
      else
        $pid_of_an_amount_a = reset($pvid_a);

		  $pid_of_an_amount = reset($pid_of_an_amount_a);
		  $new_stock_of_an_amount = floor((int)$stock_values_of_base['products_quantity']/(int)$v);
//echo $pid_of_an_amount . " " . $stock_values_of_base['products_quantity'] . " " . $v . " " . $new_stock_of_an_amount . " ";
//echo "update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'";
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'");
	  }
//exit;
//echo print_r($base_p_pv);
//echo $pid_of_an_amount . " " . $qty . " " . $current_unit_amount . " " . $actual_stock_bought;
//exit;
    //echo $current_unit_amount;
    
    //update the unit base stock

  	//$unit_base_amount=reset($unit);//1
//echo print_r($current_p_pv);
//echo print_r($is_notbase_amount_a);exit;

//echo reset($base_unit);exit;

//sizeof($is_notbase_amount_a)
//echo print_r($unit_array);
//echo "<br /><br />";
//echo print_r($current_p_pv);exit;
  }
  else {
    //get the current stock amount of 19-17 and update the 19-18 and 19-19
	  $stock_values_of_base = tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'"));
	  if (!tep_not_null($stock_values_of_base['products_quantity'])) $stock_values_of_base['products_quantity'] =0;

    $unit_array = pv_amount_unit_shift($unit_array);
    $other_unit_a = reset($unit_array); //array(18=>2,19=>3)
    $groupid = key($unit_array); //19
    foreach ($other_unit_a as $k => $v) {
      $otherpv_temp = $current_product + array((int)$groupid => (int)$k);

      $pvid_a = array();
		  foreach($products_ms_a as $k1 => $v1) {
		  	$ms_g = key($v1[key($v1)]); $ms_v = $v1[key($v1)][$ms_g];
		  	if (array_key_exists($ms_g, $otherpv_temp) && $otherpv_temp[$ms_g] == $ms_v) {
				  $pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
		  	}
	  	}

      if (sizeof($pvid_a)>1)
        $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>18) or array(2 => 24, 1 => 8, 19=>19)
      else
        $pid_of_an_amount_a = reset($pvid_a);
		  $pid_of_an_amount = reset($pid_of_an_amount_a);

		  $new_stock_of_an_amount = floor((int)$stock_values_of_base['products_quantity']/(int)$v);
		  tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'");
    }
  }
}


//20131123 - stephen
function switch_db($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link')
{
	tep_db_close();
	tep_db_connect($server, $username, $password, $database, $link) or die('Unable to connect to database server!');
}

function switch_shop_db($shop=0) {
	//1-metro, 2-braehead
	//orig: $server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD, $database = DB_DATABASE, $link = 'db_link', 
	if ($shop >0) {
		/*
		if ($shop ==1) {
			$username = METROUSER_NAME;
			$database = METROUSER_DB;
			$password = DB_SERVER_PASSWORD;
		}
		*/
		if ($shop ==2) {
			$username = BRAEHEAD_NAME;
			$database = BRAEHEAD_DB;
			$password = DB_SERVER_PASSWORD;
		}
		elseif ($shop ==3) {
			$username = METROSHOP_NAME;
			$database = METROSHOP_DB;
			$password = DB_SERVER_PASSWORD;
		}
		else {
			return;
		}
	}
	else {
		$username = DB_SERVER_USERNAME;
		$database = DB_DATABASE;
		$password = DB_SERVER_PASSWORD;
	}
	$server = DB_SERVER;
	
	$link = 'db_link';

	tep_db_close();
	tep_db_connect($server, $username, $password, $database, $link) or die('Unable to connect to database server!');
}

  function shop_braehead_id() {
  	return BRAEHEAD_CHECKOUTREASON_ID;
  }
  function shop_metro_id() {
  	return METRO_CHECKOUTREASON_ID;
  }
  function shop_amazon_id() {
  	return AMAZON_CHECKOUTREASON_ID;
  }
  function update_stock($products_id, $qty=0, $comment=IKSH_COMMENTS_CHECKIN, $updatereason=0) { //update stock
    if (!isset($products_id) || !is_numeric($products_id) || $qty <= 0 || $updatereason==0) return false;

  	$qty_ = tep_db_fetch_array(tep_db_query("select products_id, products_quantity, p.products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p where p.products_id = '" . (int)$products_id . "'"));
  	if (!tep_not_null($qty_['products_id'])) return false;

  	if ($comment == IKSH_COMMENTS_CHECKIN) {
  		$updated_qty = (int)$qty_['products_quantity'] + (int)$qty;
  		//$comment = IKSH_COMMENTS_CHECKIN;
  		$history_qty = (int)$qty;
  	}
  	elseif ($comment == IKSH_COMMENTS_CHECKOUT) {
  		$updated_qty = (int)$qty_['products_quantity'] - (int)$qty;
  		//$comment = IKSH_COMMENTS_CHECKOUT;
  		$history_qty = (int)(-$qty);
  	}
  	if (!tep_not_null($qty_['products_bo']) && (int)$updated_qty < 0) {
      $updated_qty=0;
  	}
    tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$updated_qty . "' where products_id = '" . (int)$products_id . "'");

    if ((int)$qty_['parent_id']) {
     	//pv_update_notbase_amount($products_id, $qty_['parent_id'], $updated_qty); //update based on 50g
     	pv_update_h_pack($products_id, $qty, $qty_['parent_id'], $comment);
    }

    $find_q = tep_db_query("select ik_stock_products_id, barcode from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$products_id . "'");
    if (tep_db_num_rows($find_q)>0) {
      $find = tep_db_fetch_array($find_q);
      $ik_pid = $find['ik_stock_products_id'];
    }
    else {
      $ik_pid = 0;
    }

    global $admin, $languages_id;
    $sql_data_array = array('ik_stock_products_id' => (int)$ik_pid,
                            'user_name' => tep_db_prepare_input($admin['username']),
                            'quantity' => $history_qty,
                            'latest_qty' => (int)$updated_qty,
                            'comments' => $comment,
                            'date_modified' => 'now()',
                            'products_id' => (int)$products_id,
                            'ik_stock_checkout_reasons_id' => $updatereason);
    tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

    //function to checkin/checkout to metro or bh: 16-metro 14-braehead
    if ($updatereason == shop_braehead_id() || $updatereason == shop_metro_id()) {
      if (tep_not_null($find['barcode'])) {
        $shop[] = array('pid' => $products_id, 'psid' => $psid, 'barcode' => $find['barcode'], 'q_inout' => $history_qty, 'products_name' => $product_name['products_name'], 'attributes' => $display_att_, 'comment' => $comment);
        unset($find['barcode']); //destroy variable $find['barcode'] preventing the same barcode appear again
      }
    }

    	  if(sizeof($shop)>0) {
    	  	if ($updatereason == shop_braehead_id() || $updatereason == shop_metro_id()) {
    	  		$shopname = tep_db_fetch_array(tep_db_query("select checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where ik_stock_checkout_reasons_id = '" . (int)$updatereason . "' and language_id = '" . (int)$languages_id . "'"));
    	  	  if ($updatereason == shop_braehead_id()) {
    	  	  	switch_shop_db(2);
    	  	  }
    	  	  elseif ($updatereason == shop_metro_id()) {
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
    	  elseif ($updatereason == shop_braehead_id() || $updatereason == shop_metro_id()) {
    	  	$shopname = tep_db_fetch_array(tep_db_query("select checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where ik_stock_checkout_reasons_id = '" . (int)$updatereason . "' and language_id = '" . (int)$languages_id . "'"));
    	  	$entry['warning'] = 'Barcode not available yet ' . $shopname['checkout_reasons_name'];
    	  }

    $entry['update'] = $HTTP_POST_VARS['update'] . " " . $HTTP_POST_VARS['pid'] . " " . $HTTP_POST_VARS['qty'] . " " . $HTTP_POST_VARS['checkoutreason'];
    return $entry;
    //$entry['update'] = $HTTP_POST_VARS['update'] . " " . $HTTP_POST_VARS['pid'] . " " . $HTTP_POST_VARS['qty'] . " " . $HTTP_POST_VARS['checkoutreason'];
  	//echo json_encode($entry);
  }


function pv_update_h_pack($products_id=0, $qty=0, $parent_id=0, $comment=IKSH_COMMENTS_CHECKIN) { //50g/100g/150g
	$products_variants = array();
	$products_pv_q = tep_db_query("select opv.products_id, opv.products_variants_values_id, opv.default_combo, opvv.products_variants_groups_id, opvv.title as vtitle, opvv.image, opvv.sort_order as vorder, opvg.title as gtitle, opvg.sort_order as gorder from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id where opv.products_id='" . (int)$products_id . "' order by opvg.sort_order, opvv.sort_order");
  while ($products_pv = tep_db_fetch_array($products_pv_q)) {
    $products_variants[] = array('group' => $products_pv['gtitle'],
                                 'value' => $products_pv['vtitle'],
                                 'group_id' => $products_pv['products_variants_groups_id'],
                                 'value_id' => $products_pv['products_variants_values_id']);
  }
	if ((int)$parent_id <1 || sizeof($products_variants) <1) return null;

	$unit= array("14-183"=> 1, "14-184"=> 2, "14-185"=> 3);//1-50g,2-100g,3-150g
  $unit_group_a = explode("-", key($unit));
	$unit_group = $unit_group_a[0];

	//get the current unit amount
	$unit_current = array();
	$current_p_pv = array();
	$current_amount = 0;
	foreach ($products_variants as $v) {
		if ($v['group_id']==$unit_group) {
			if (array_key_exists($v['group_id'] . "-" . $v['value_id'], $unit)) {
				$unit_current[$v['group_id']] = $v['value_id'];
				$current_amount = $unit[$v['group_id'] . "-" . $v['value_id']];
				if ($current_amount ==1 ) {
				  unset($unit[$v['group_id'] . "-" . $v['value_id']]);
				}
			}
		}
		$current_p_pv[$v['group_id']] = $v['value_id'];
	}

	if ($current_amount <1) return null;

  $products_ms_q = tep_db_query("select opv.products_id, opvv.products_variants_groups_id, opv.products_variants_values_id from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_PRODUCTS . " p on opv.products_id=p.products_id left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id left join " . TABLE_SPECIALS . " s on p.products_id = s.products_id where p.parent_id='" . (int)$parent_id . "' order by opvg.sort_order, opvv.sort_order");

  if (!tep_db_num_rows($products_ms_q)) return null;

  $products_ms_a =array();
  while ($products_ms = tep_db_fetch_array($products_ms_q)) {
    $products_ms_a[][$products_ms['products_id']] =array($products_ms['products_variants_groups_id'] => $products_ms['products_variants_values_id']); //array(759 => array(2 => 1))
  }

	if ($current_amount ==1) {
		//get the current stock amount of 19-17 and update the 19-18 and 19-19
		$stock_values_of_base = tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'"));
		if (!tep_not_null($stock_values_of_base['products_quantity'])) $stock_values_of_base['products_quantity'] =0;

    $otherpv = array_diff($current_p_pv, $unit_current); //array(2 => 24, 1 => 8);
	  //update other unit's stock
	  foreach ($unit as $k => $v) { //$unit=array( "19-18"=> 2, "19-19"=> 3);
		  $unit_gv = explode("-", $k);
      $otherpv_temp = $otherpv + array((int)$unit_gv[0] => (int)$unit_gv[1]); //array(2 => 24, 1 => 8, 19=>18) OR array(2 => 24, 1 => 8, 19=>19);
      $pvid_a = array();
		  foreach($products_ms_a as $k1 => $v1) {
		  	$ms_g = key($v1[key($v1)]); $ms_v = $v1[key($v1)][$ms_g];
		  	if (array_key_exists($ms_g, $otherpv_temp) && $otherpv_temp[$ms_g] == $ms_v) {
				  $pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
		  	}
	  	}

      if (sizeof($pvid_a)>1)
        $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>18) or array(2 => 24, 1 => 8, 19=>19)
      else
        $pid_of_an_amount_a = reset($pvid_a);

		  $pid_of_an_amount = reset($pid_of_an_amount_a);

		  $new_stock_of_an_amount = floor((int)$stock_values_of_base['products_quantity']/(int)$v);
//echo "update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'<br /><br />";
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'");
	  }
	}
	else { //$current_amount ==2 or $current_amount ==3
		//get the base stock amount
    $unit_base_amount=reset($unit);//1
    $unit_base_gv= array_search(1, $unit);//OR $unit_base_gv= key($unit)

    $unit_gv = explode("-", $unit_base_gv);
    $otherpv = array_diff($current_p_pv, $unit_current); //array(2 => 24, 1 => 8);
    $otherpv_base = $otherpv + array((int)$unit_gv[0] => (int)$unit_gv[1]); //array(2 => 24, 1 => 8, 19=>17)

    $pvid_a = array();
		foreach($products_ms_a as $k1 => $v1) {
			$ms_g = key($v1[key($v1)]);$ms_v = $v1[key($v1)][$ms_g];
			if (array_key_exists($ms_g, $otherpv_base) && $otherpv_base[$ms_g] == $ms_v) {
				$pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
			}
		}

    if (sizeof($pvid_a)>1)
      $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>17)
    else
      $pid_of_an_amount_a = reset($pvid_a);
		$pid_of_an_amount = reset($pid_of_an_amount_a); //base unit product

		$qty_ = tep_db_fetch_array(tep_db_query("select products_quantity, p.products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p where p.products_id = '" . (int)$pid_of_an_amount . "'"));

  	if ($comment == IKSH_COMMENTS_CHECKIN) {
  		$updated_qty = (int)$qty_['products_quantity'] + (int)$qty * $current_amount;
  		//$history_qty = (int)$qty;
  	}
  	elseif ($comment == IKSH_COMMENTS_CHECKOUT) {
  		$updated_qty = (int)$qty_['products_quantity'] - (int)$qty * $current_amount;
  		//$history_qty = (int)(-$qty);
  	}
  	if (!tep_not_null($qty_['products_bo']) && (int)$updated_qty < 0) {
      $updated_qty=0;
  	}

		//$actual_stock_bought = $qty * $current_amount;
    tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$updated_qty . "' where products_id = '" . (int)$pid_of_an_amount . "'");
    unset($unit[$unit_gv[0] . "-" . $unit_gv[1]]);
    //update other unit exclude the current unit, e.g 100g or 150g
   	$stock_values_of_base = tep_db_fetch_array(tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . (int)$pid_of_an_amount . "'"));
		if (!tep_not_null($stock_values_of_base['products_quantity'])) $stock_values_of_base['products_quantity'] =0;

	  foreach ($unit as $k => $v) { //$unit=array( "19-18"=> 2); OR $unit=array("19-19"=> 3);
		  $unit_gv = explode("-", $k);
      $otherpv_temp = $otherpv + array((int)$unit_gv[0] => (int)$unit_gv[1]); //array(2 => 24, 1 => 8, 19=>18) OR array(2 => 24, 1 => 8, 19=>19);

      $pvid_a = array();
		  foreach($products_ms_a as $k1 => $v1) {
		  	$ms_g = key($v1[key($v1)]); $ms_v = $v1[key($v1)][$ms_g];
		  	if (array_key_exists($ms_g, $otherpv_temp) && $otherpv_temp[$ms_g] == $ms_v) {
				  $pvid_a[$ms_g . "-" . $ms_v][] = key($v1);
		  	}
	  	}

      if (sizeof($pvid_a)>1)
        $pid_of_an_amount_a = call_user_func_array('array_intersect', $pvid_a);//product id of pv: array(2 => 24, 1 => 8, 19=>18) or array(2 => 24, 1 => 8, 19=>19)
      else
        $pid_of_an_amount_a = reset($pvid_a);

		  $pid_of_an_amount = reset($pid_of_an_amount_a);
		  $new_stock_of_an_amount = floor((int)$stock_values_of_base['products_quantity']/(int)$v);
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int)$new_stock_of_an_amount . "' where products_id = '" . (int)$pid_of_an_amount . "'");
	  }
	}
}
?>