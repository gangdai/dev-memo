<?php
/*
      QT Pro Version 4.1
  
      stock.php
  
      Contribution extension to:
        osCommerce, Open Source E-Commerce Solutions
        http://www.oscommerce.com
     
      Copyright (c) 2004, 2005 Ralph Day
      Released under the GNU General Public License
  
      Based on prior works released under the GNU General Public License:
        QT Pro prior versions
          Ralph Day, October 2004
          Tom Wojcik aka TomThumb 2004/07/03 based on work by Michael Coffman aka coffman
          FREEZEHELL - 08/11/2003 freezehell@hotmail.com Copyright (c) 2003 IBWO
          Joseph Shain, January 2003
        osCommerce MS2
          Copyright (c) 2003 osCommerce
          
      Modifications made:
        11/2004 - Add input validation
                  clean up register globals off problems
                  use table name constant for products_stock instead of hard coded table name
        03/2005 - Change $_SERVER to $HTTP_SERVER_VARS for compatibility with older php versions

*******************************************************************************************
  
      QT Pro Stock Add/Update
  
      This is a page to that is linked from the osCommerce admin categories page when an
      item is selected.  It displays a products attributes stock and allows it to be updated.

*******************************************************************************************

  $Id: stock.php,v 1.00 2003/08/11 14:40:27 IBWO Exp $

  Enhancement module for osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Credit goes to original QTPRO developer.
  Attributes Inventory - FREEZEHELL - 08/11/2003 freezehell@hotmail.com
  Copyright (c) 2003 IBWO

  Released under the GNU General Public License
*/
  require('includes/application_top.php');

//20140131 please disable this function in 6 months
function temp_prebonded($categories_id=0) {
	$prebonded_array = array(40,41,182,183);
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
}


  if ($HTTP_SERVER_VARS['REQUEST_METHOD']=="GET") {
    $VARS=$_GET;
  } else {
    $VARS=$_POST;
  }
//echo print_r($VARS);echo "<br /><br />";exit;
$sequence = array();
//My modification: separate pre-bonded, p2f and pre-bonded luxury
//$product_categories_name = tep_get_products_categories_name($VARS['product_id'], $languages_id);

$product_category_id = get_products_categories_id($HTTP_GET_VARS['product_id']);
/*
if(preg_match('/Pick2Fit pieces/i',$product_categories_name))
{
	if ((is_numeric($VARS['product_id']) and ($VARS['product_id']==(int)$VARS['product_id'])))
		tep_redirect(tep_href_link('stock_p2f.php', 'product_id='.$VARS['product_id'].'&is_multi=1'));
}
*/
/*
elseif (check_nailstick("", $product_category_id))
{
	if ((is_numeric($VARS['product_id']) and ($VARS['product_id']==(int)$VARS['product_id'])))
		tep_redirect(tep_href_link('stock_prebonded.php', 'product_id='.$VARS['product_id'].'&is_prebonded=1'));
}
*/
//elseif (preg_match('/Pre-Bonded Luxury/i',tep_get_products_super_categories_name($product_category_id)))
if (in_array($VARS['product_id'],$sequence)) {
	if ((is_numeric($VARS['product_id']) and ($VARS['product_id']==(int)$VARS['product_id'])))
		tep_redirect(tep_href_link('stock_remi.php', 'product_id='.$VARS['product_id'].'&is_remi=1'));
}
//My modification: separate pre-bonded, p2f and pre-bonded luxury

  if ($VARS['action']=="Add") {
    $inputok = true;
    if (!(is_numeric($VARS['product_id']) and ($VARS['product_id']==(int)$VARS['product_id']))) $inputok = false;
    while(list($v1,$v2)=each($VARS)) {
      if (preg_match("/^option(\d+)$/",$v1,$m1)) {
        if (is_numeric($v2) and ($v2==(int)$v2)) $val_array[]=$m1[1]."-".$v2;
        else $inputok = false;
      }
    }
    if (!(is_numeric($VARS['quantity']) and ($VARS['quantity']==(int)$VARS['quantity']))) $inputok = false;

    if (($inputok)) {
      sort($val_array, SORT_NUMERIC);
      $val=join(",",$val_array);
      //$q=tep_db_query("select products_stock_id as stock_id from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$VARS['product_id'] . " and products_stock_attributes='" . $val . "' order by products_stock_attributes");
      $q=tep_db_query("select products_quantity, products_stock_quantity, products_stock_id as stock_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_STOCK . " ps where p.products_id=ps.products_id and p.products_id=" . (int)$VARS['product_id'] . " and ps.products_stock_attributes='" . $val . "' order by products_stock_attributes");
      if (tep_db_num_rows($q)>0) {
        $stock_item=tep_db_fetch_array($q);
        $stock_id=$stock_item['stock_id'];
        //if ($VARS['quantity']=intval($VARS['quantity'])) {
        //My modification: update rather than delete
        if (is_numeric($VARS['quantity']))
          tep_db_query("update " . TABLE_PRODUCTS_STOCK . " set products_stock_quantity=" . (int)$VARS['quantity'] . " where products_stock_id=$stock_id");
        //} else {
        //  tep_db_query("delete from " . TABLE_PRODUCTS_STOCK . " where products_stock_id=$stock_id");
        //}
      } else {
        tep_db_query("insert into " . TABLE_PRODUCTS_STOCK . " values (0," . (int)$VARS['product_id'] . ",'$val'," . (int)$VARS['quantity'] . ")");
      }
      $q=tep_db_query("select sum(products_stock_quantity) as summa from " . TABLE_PRODUCTS_STOCK . " where products_id=" . (int)$VARS['product_id'] . " and products_stock_quantity>0");
      $list=tep_db_fetch_array($q);
      $summa= (empty($list[summa])) ? 0 : $list[summa];
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity=$summa where products_id=" . (int)$VARS['product_id']);
      if (($summa<1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
        //tep_db_query("update " . TABLE_PRODUCTS . " set products_status='0' where products_id=" . (int)$VARS['product_id']);
      }

      //store info to TABLE_IK_STOCK_PRODUCTS_HISTORY
    	  	$find_q = tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$VARS['product_id'] . "' and products_stock_id = '" . (int)$stock_id . "'");
          if (tep_db_num_rows($find_q)>0) {
            $find = tep_db_fetch_array($find_q);
            $ik_pid = $find['ik_stock_products_id'];
          }
          else {
            $ik_pid = 0;
          }

          $sql_data_array = array('ik_stock_products_id' => (int)$ik_pid,
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => $VARS['quantity'],
                                  'latest_qty' => $VARS['quantity'],
                                  'previous_qty' => (int)$stock_item['products_stock_quantity'],
                                  'comments' => tep_not_null($VARS['quantity']) ? IKSH_COMMENTS_UPDATE: "",
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$VARS['product_id'],
                                  'products_attributes' => tep_db_prepare_input($val),
                                  'ik_stock_checkout_reasons_id' => (int)CHECKOUT_REASONS_1);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);
    }
  }
  if ($VARS['action']=="Update") {
  	//store info to TABLE_IK_STOCK_PRODUCTS_HISTORY
  	$previous=tep_db_fetch_array(tep_db_query("select products_quantity, products_bo from " . TABLE_PRODUCTS . " where products_id = '" . (int)$VARS['product_id'] . "'"));
  	if (!tep_not_null($previous['products_bo']) && (int)$VARS['quantity'] < 0) {
  		$messageStack->add('This is not a backordered product', 'warning');
  	}
  	else {
    	$find_q = tep_db_query("select ik_stock_products_id from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id = '" . (int)$VARS['product_id'] . "'");
      if (tep_db_num_rows($find_q)>0) {
        $find = tep_db_fetch_array($find_q);
        $ik_pid = $find['ik_stock_products_id'];
      }
      else {
        $ik_pid = 0;
      }

      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity=" . (int)$VARS['quantity'] . " where products_id=" . (int)$VARS['product_id']);
      if (($VARS['quantity']<1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
        //tep_db_query("update " . TABLE_PRODUCTS . " set products_status='0' where products_id=" . (int)$VARS['product_id']);
      }

          $sql_data_array = array('ik_stock_products_id' => (int)$ik_pid,
                                  'user_name' => tep_db_prepare_input($admin['username']),
                                  'quantity' => $VARS['quantity'],
                                  'latest_qty' => $VARS['quantity'],
                                  'previous_qty' => (int)$previous['products_quantity'],
                                  'comments' => tep_not_null($VARS['quantity']) ? IKSH_COMMENTS_UPDATE: "",
                                  'date_modified' => 'now()',
                                  'products_id' => (int)$VARS['product_id'],
                                  'ik_stock_checkout_reasons_id' => 7);
          tep_db_perform(TABLE_IK_STOCK_PRODUCTS_HISTORY, $sql_data_array);

      //check 50g => 100g/150g
      //Array ( [quantity] => 26 [product_id] => 1710 [action] => Update ) 1
      if (isset($VARS['parent_id']) && (int)$VARS['parent_id']>0) {
        //get the pv
        pv_update_notbase_amount($VARS['product_id'], $VARS['parent_id'], $VARS['quantity']);  //update based on 50g
      }
    }
  }
  if ($VARS['action']=="Apply to all") {
  }



  $q=tep_db_query($sql="select products_name,products_options_name as _option,products_attributes.options_id as _option_id,products_options_values_name as _value,products_attributes.options_values_id as _value_id from ".
                  "products_description, products_attributes,products_options,products_options_values where ".
                  "products_attributes.products_id=products_description.products_id and ".
                  "products_attributes.products_id=" . (int)$VARS['product_id'] . " and ".
                  "products_attributes.options_id=products_options.products_options_id and ".
                  "products_attributes.options_values_id=products_options_values.products_options_values_id and ".
                  "products_description.language_id=" . (int)$languages_id . " and ".
                  "products_options_values.language_id=" . (int)$languages_id . " and products_options.products_options_track_stock=1 and ".
                  "products_options.language_id=" . (int)$languages_id . " order by products_attributes.options_id, products_attributes.options_values_id");
 //list($product_name,$option_name,$option_id,$value,$value_id)
  if (tep_db_num_rows($q)>0) {
    $flag=1;
    while($list=tep_db_fetch_array($q)) {
      $options[$list[_option_id]][]=array($list[_value],$list[_value_id]);
      $option_names[$list[_option_id]]=$list[_option];
      $product_name=$list[products_name];
    }
  }
  //Commented out so items with 0 stock will show up in the stock report.
  else {
  //  $flag=0;
    $q=tep_db_query("select products_quantity, products_name, products_model, p.parent_id, p.has_children, p.products_bo from " . TABLE_PRODUCTS . " p, products_description pd where pd.products_id=p.products_id and p.products_id='" . (int)$VARS['product_id'] . "' and pd.language_id = '" . (int)$languages_id . "'");

    $list=tep_db_fetch_array($q);
    $db_quantity=$list['products_quantity'];
    if (!empty($list['products_model'])) {
      $pmodel = " [" . $list['products_model'] . "]";
    }
    $product_name=stripslashes($list['products_name']);

    if ((int)$list['parent_id']>0) {
    	//$mname1=tep_db_fetch_array(tep_db_query("select pd.products_name from " . TABLE_PRODUCTS . " p, products_description pd where pd.products_id=p.products_id and p.products_id='" . (int)$list['parent_id'] . "' and pd.language_id = '" . (int)$languages_id . "'"));
    	$product_name = " <span class=dataTableContentGrey>" . stripslashes($list['products_name']) . $pmodel . " - " . pv_slave_value($list['parent_id'], $VARS['product_id']) . "</span>";
    }
    //My modifcation disable $admin['login_groups_id']>2
    /*
    if ($admin['login_groups_id']==3) {
    	if ($db_quantity>5)
    	  $db_quantity = ">5";
    	elseif ($db_quantity>0)
    	  $db_quantity = ">0";
    }
    */
    //My modifcation disable $admin['login_groups_id']>2
  }

  $product_investigation = qtpro_doctor_investigate_product($VARS['product_id']);
  
  //My modification - checkout reason
  $checkout_reasons = array();
  $checkout_reasons_array = array();
  $checkout_reasons_query = tep_db_query("select ik_stock_checkout_reasons_id, checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where language_id = '" . (int)$languages_id . "' order by ik_stock_checkout_reasons_id");
  $checkout_reasons[] = array('id' => '0', 'text' =>'Select');
  while ($checkout_reason = tep_db_fetch_array($checkout_reasons_query)) {
    $checkout_reasons[] = array('id' => $checkout_reason['ik_stock_checkout_reasons_id'],
                                'text' => $checkout_reason['checkout_reasons_name']);
    $checkout_reasons_array[$checkout_reason['ik_stock_checkout_reasons_id']] = $checkout_reason['checkout_reasons_name'];
  }

  require(DIR_WS_INCLUDES . 'template_top.php');
  
?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td>
		      <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
	  		    <td class="pageHeading"><?php echo $product_name . tep_draw_separator('pixel_trans.gif', 50, 1) . tep_draw_button('Refresh', 'info', tep_href_link(FILENAME_STOCK, 'product_id=' . $VARS['product_id']));?></td>
            <td class="pageHeading" align="right"><?php $model_name=tep_db_fetch_array(tep_db_query("select p.products_model,	p.manufacturers_id, m.manufacturers_name, manufacturers_logo from " . TABLE_PRODUCTS . " p, " . TABLE_MANUFACTURERS . " m where p.products_id='" . (int)$VARS['product_id'] . "' and p.manufacturers_id=m.manufacturers_id")); echo tep_image(DIR_WS_CATALOG . DIR_WS_IMAGES . $model_name['manufacturers_logo'], $model_name['manufacturers_name']);//echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
          <tr><td class="pageHeading"><?php if ((int)$list['parent_id']<1) {if (tep_not_null($model_name['products_model'])) echo $model_name['products_model'];}?></td></tr>
          </table>
		    </td>
      </tr>
      <tr>
        <td><form action="<?php echo $PHP_SELF;?>" method=get>
        <table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="1" cellpadding="2">
              <tr class="dataTableHeadingRow">
<?php
  $title_num=1;
  $exclude_status =" and o.orders_status in (1,2,101,100,13,12) ";
  if ($flag) {
    while(list($k,$v)=each($options)) {
      echo "<td class=\"dataTableHeadingContent\">&nbsp;&nbsp;$option_names[$k]</td>";
      $title[$title_num]=$k;
    }
    echo "<td class=\"dataTableHeadingContent\"><span class=smalltext>Qty</span></td><td></td><td class=\"dataTableHeadingContent\"></td>";
    echo "</tr>";
    $q=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id='" . (int)$VARS['product_id'] . "' order by products_stock_attributes");
    while($rec=tep_db_fetch_array($q)) {
      $val_array=explode(",",$rec['products_stock_attributes']);
      echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);">';
      foreach($val_array as $val) {
        if (preg_match("/^(\d+)-(\d+)$/",$val,$m1)) {
          echo "<td class=smalltext>&nbsp;&nbsp;&nbsp;" . tep_values_name($m1[2]) . "</td>";
        } else {
          echo "<td>&nbsp;</td>";
        }
      }
      for($i=0;$i<sizeof($options)-sizeof($val_array);$i++) {
        echo "<td>&nbsp;</td>";
      }

              if (tep_not_null($rec['products_stock_attributes'])) {
    	  				$ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' and op.products_stock_attributes='" . $rec['products_stock_attributes'] . "' " . $exclude_status . " 
                union all
                select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and op.products_id = pb.bundle_id and pb.products_stock_attributes='" . $rec['products_stock_attributes'] . "' and pb.subproduct_id='" . (int)$VARS['product_id'] . "' " . $exclude_status . " order by date_purchased desc");

    	  				$ordered_qty = 0; //ordered qty which has not been pick up from the warehouse
    	  				while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
    	  					$ordered_qty += $ordersproducts['products_quantity'];
    	  				}
    	  				$correct_qty = $rec['products_stock_quantity'] + $ordered_qty;
              }
              else {
              	/*
                $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' " . $exclude_status . " 
                union all
                select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and op.products_id = pb.bundle_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and pb.subproduct_id = '" . (int)$VARS['product_id'] . "' " . $exclude_status . " order by date_purchased desc");

                $ordered_qty = 0; //ordered qty which has not been pick up from the warehouse
                while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
                	$ordered_qty += $ordersproducts['products_quantity'];
                }

                $correct_qty = $_POST[$q_inout] - $ordered_qty;
                */
              }

      echo '<td class="smalltext" align="center">' . $rec['products_stock_quantity'] . '</td>' . "\n";
      $pbarcode = tep_db_fetch_array(tep_db_query("select barcode from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id ='" . (int)$VARS['product_id'] . "' and products_attributes ='" . tep_db_input($rec['products_stock_attributes']) . "'"));
      echo '<td class="smalltext" align="center">' . $pbarcode['barcode'] . '</td>' . "\n";
      //echo '<td class=smalltext>' . $correct_qty . '</td></tr>' . "\n";
      echo '<td class=smalltext></td></tr>' . "\n";
    }
    echo "<tr>";
    reset($options);
    $i=0;
    while(list($k,$v)=each($options)) {
      echo "<td class=dataTableHeadingRow><select name=option$k>";
      foreach($v as $v1) {
        echo "<option value=".$v1[1].">".$v1[0];
      }
      echo "</select></td>";
      $i++;
    }
  } else {
    $i=1;
    echo "<td class=dataTableHeadingContent>Quantity</td>";
  }

    echo "<td class=dataTableHeadingRow><input type=text name=quantity size=4 value=\"" . $db_quantity . "\">";
    echo tep_draw_hidden_field('product_id', $VARS['product_id']);
    if ((int)$list['parent_id']>0 ) echo tep_draw_hidden_field('parent_id', $list['parent_id']);
    if (tep_not_null($list['products_bo'])) echo tep_draw_hidden_field('is_backorder', '1');
    else  echo tep_draw_hidden_field('is_backorder', '0');
    echo " </td>";
  //if ($login_groups_id<2 || $login_groups_id ==3) {
  //if ($login_groups_id<3 || $login_groups_id==4) {
  if ($login_groups_id != 3) {
    if ((int)$list['parent_id']>0) {
    	if (!pv_is_notbase_amount($VARS['product_id'])) {
    		echo "<td width=\"100%\" class=dataTableHeadingRow>&nbsp;<input type=submit name=action value=" . ($flag?"Add":"Update") . ">&nbsp;</td>";
    	}
    }
    else {
        echo "<td width=\"100%\" class=dataTableHeadingRow>&nbsp;<input type=submit name=action value=" . ($flag?"Add":"Update") . ">&nbsp;</td>";
    }
    echo tep_hide_session_id();
  }

  echo "<td width=\"100%\" class=dataTableHeadingRow>&nbsp;</td>";
  //echo "<td width=\"100%\" class=dataTableHeadingRow>&nbsp;</td>";
?>
              </tr>
<?php 
    if (!$flag) {
                  $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' " . $exclude_status . " 
                  union all
                  select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and op.products_id = pb.bundle_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -5 day), ' 00:00:00') and pb.subproduct_id = '" . (int)$VARS['product_id'] . "' " . $exclude_status . " order by date_purchased desc");

                  $ordered_qty = 0; //ordered qty which has not been pick up from the warehouse
                  while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
                  	$ordered_qty += $ordersproducts['products_quantity'];
                  }

                  $correct_qty = $db_quantity + $ordered_qty;
?>
<tr class="dataTableRow" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);">
	<td class=smalltext>Stock</td>
  <td class=smalltext>&nbsp;<?php echo $correct_qty;?></td>
  <?php
    $pbarcode = tep_db_fetch_array(tep_db_query("select barcode from " . TABLE_IK_STOCK_PRODUCTS . " where ori_products_id ='" . (int)$VARS['product_id'] . "'"));
  ?>
  <td class=smalltext><?php echo $pbarcode['barcode'];?></td>
</tr>
<?php
    }
?>
            </table></td>
          </tr>
        </table>
        </form></td>
      </tr>
<tr><td>

<br>

<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td class="dataTableHeadingRow">
<table width="100%" class="boxText" border="0" cellspacing="1" cellpadding="4">
<tr valign="top">
	<td class="dataTableHeadingContent" width="400" style="font-size: 12;">QTPro Doctor</td>
	<td class="dataTableHeadingContent" style="font-size: 12;">Links</td>
	<td class="dataTableHeadingContent" style="font-size: 12;"><?php
    echo tep_draw_form('search', FILENAME_STOCK, '', 'get');
    echo "Products: " . ' ' . tep_draw_input_field('search');
    echo tep_draw_hidden_field('product_id', $VARS['product_id']);
    echo "<input type=submit name=action value=\"search\" />";
    echo tep_hide_session_id() . '</form>';
    ?>
  </td>
</tr>

<tr valign="top">
	<td class="menuBoxHeading" width="400">
	<span style="font-family: Verdana, Arial, sans-serif; font-size: 10px; color: black;">
	<?php 
  		//$product_investigation = qtpro_doctor_investigate_product($VARS['product_id']); //Defoined above? behövs då ej här
    if ($admin['login_groups_id']<=2) {
		  print qtpro_doctor_formulate_product_investigation($product_investigation, 'detailed');
		}
  	?>
	</span>
	</td>
	
  <td class="menuBoxHeading">
	<?php 
	
	echo '<br /><ul><li><a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $VARS['product_id'] . '&action=new_product') . '" class="menuBoxContentLink">Edit this product</a></li>';
	if ((int)$list['parent_id']>0) {
		echo '<li><a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID=' . $list['parent_id'] . '&action=new_product') . '" class="menuBoxContentLink">Go to master product</a></li>';
	}
  //echo '<li><a href="' . tep_href_link(FILENAME_STATS_LOW_STOCK_ATTRIB, '', 'NONSSL') . '" class="menuBoxContentLink">Go to the low stock report</a></li>';

	//class="menuBoxHeading columnLeft
	//We shall now generate links back to the product in the admin/categories.php page.
	//The same product can exist in differend categories.

	  //Generate both the text (in $path_array) and the parameter (in $cpath_string_array)
	  $raw_path_array =tep_generate_category_path($VARS['product_id'], 'product');
	  $path_array = array();
	  $cpath_string_array = array();
	  foreach($raw_path_array as $raw_path){
	    $path_in_progress='';
		$cpath_string_in_progress='';
	  	foreach($raw_path as $raw_path_piece){
	      $path_in_progress .= $raw_path_piece['text'].' >> ';
		  $cpath_string_in_progress .= $raw_path_piece['id'].'_';
	    }
		$path_array[]= substr($path_in_progress, 0, -4);
		$cpath_string_array[] = substr($cpath_string_in_progress, 0, -1);
	  }
	  
	  if (sizeof($raw_path_array)>0) {


		$curr_pos = 0;
		foreach($path_array as $neverusedvariable) {
		  print '<li><a href="' . tep_href_link(FILENAME_CATEGORIES, 'pID='.$VARS['product_id'].'&cPath='. $cpath_string_array[$curr_pos] , 'NONSSL') . '" class="menuBoxContentLink">Go to this product in '.$path_array[$curr_pos].'</a></li>';
		  $curr_pos++;
		}
	 }else{
	 	print '<span style="font-family: Verdana, Arial, sans-serif; font-size: 10px; color: #FF1111; font-weight: normal; text-decoration: none;">Warning! This product does not seem to exist in any category. Your customers can\'t find it.</span>';
	 }

		echo '<li>'. tep_draw_form('goto', FILENAME_CATEGORIES, '', 'get') . 'Go to ' . tep_draw_pull_down_menu('cPath', tep_get_category_tree(), $current_category_id, 'onChange="this.form.submit();"') . '</form></li>';

    if (!tep_not_null($list['has_children'])) {
      $checkout_reasons = array();
      $checkout_reasons_array = array();
      $checkout_reasons_query = tep_db_query("select ik_stock_checkout_reasons_id, checkout_reasons_name from " . TABLE_IK_STOCK_CHECKOUT_REASONS . " where language_id = '" . (int)$languages_id . "' order by ik_stock_checkout_reasons_id");
      $checkout_reasons[] = array('id' => '0', 'text' =>'Select');
      while ($checkout_reason = tep_db_fetch_array($checkout_reasons_query)) {
        $checkout_reasons[] = array('id' => $checkout_reason['ik_stock_checkout_reasons_id'],
                                    'text' => $checkout_reason['checkout_reasons_name']);
        $checkout_reasons_array[$checkout_reason['ik_stock_checkout_reasons_id']] = $checkout_reason['checkout_reasons_name'];
      }
      //echo '<li> </li>' . "\n";
      echo '<li><br />' . tep_draw_form('updatestock', FILENAME_STOCK, '', '') . tep_draw_pull_down_menu('checkout_reasons', $checkout_reasons) . " " . tep_draw_input_field('checkin', '', 'size=3 maxlength=4') . '<button id="checkinb" class="usbutton">' . 'checkin' . '</button>' . '&nbsp;&nbsp;' . tep_draw_input_field('checkout', '', 'size=3 maxlength=4') . '<button id="checkoutb" class="usbutton">' . 'checkout' . '</button>' . '</form></li>' . "\n";
      echo '</ul>' . "\n";
    ?>
      <script>
      $(document).ready(function(){
        $("#checkinb").button({icons:{primary:"ui-icon-plus"}}).addClass("ui-priority-secondary");
        $("#checkoutb").button({icons:{primary:"ui-icon-minus"}}).addClass("ui-priority-secondary");
        
        $("form[name='updatestock']").on('click', '.usbutton', function(e) {
          e.preventDefault();
          var inputvalue = $(this).prev().val();
          if (Math.floor(inputvalue) == inputvalue && $.isNumeric(inputvalue) && inputvalue >0) {}
          else {$(this).prev().val("");return false;}
  
          var checkoutreason = $("form[name='updatestock']").find('select[name="checkout_reasons"]').val();
          if (checkoutreason < 1) return false;
  
          var pid = "<?php echo $VARS['product_id'];?>";
          var string_data= '{"action":"stockpage_stock_update", "pid":"' + pid + '", "update":"' + this.id + '", "qty":"' + $(this).prev().val() + '", "checkoutreason":"' + checkoutreason + '"}';
          var JSONObject = eval ("(" + string_data + ")");
          $.ajax({
            url:"<?php echo str_replace('&amp;', '&', tep_href_link('includes/javascript/stock_admin/stock_admin.php', '', 'SSL')); ?>",
            cache: false,
            type: "POST",
            beforeSend: function(x) {
              if(x && x.overrideMimeType) {
                x.overrideMimeType("application/json;charset=UTF-8");
              }
            },
            dataType: "json",
            data: JSONObject,
            success: function(html) {
  //console.log(html.update);
  //console.log(html.warning);
  //return false;
              if ((typeof(html.warning) == 'undefined' || html.warning == null) || html.warning=='') window.location.replace("<?php echo tep_href_link(FILENAME_STOCK, 'product_id=' . $VARS['product_id']);?>");
              else alert(html.warning);
            }
          });
        });

      });
      </script>
      <style>li .ui-button .ui-button-text{padding: 4px 11px 4px 21px !important;}</style>
    <?php
    }
    else {
    	echo '</ul>' . "\n";
    }
    ?>


	</td>

  <td class="menuBoxHeading"><table width="100%" class="boxText" border="0" cellspacing="1" cellpadding="0">
<?php
    if (isset($HTTP_GET_VARS['search']) && $HTTP_GET_VARS['action'] == "search") {
      $search_products_query = tep_db_query("select p.products_id, p.products_model, pd.products_name, p.products_quantity, pi.image_filename, p.products_price, p.products_market_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p2c.categories_id, p.products_to_rss, p.products_free_shipping from " . TABLE_PRODUCTS . " p left join " . TABLE_PRODUCTS_IMAGES . " pi on p.products_id = pi.products_id and pi.product_page = '1', " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "' and p.products_id = p2c.products_id and (pd.products_name like '%" . tep_db_input($HTTP_GET_VARS['search']) . "%' or p.products_model like '%" . tep_db_input($HTTP_GET_VARS['search']) . "%') order by p.products_model, pd.products_name");
      while ($products_search = tep_db_fetch_array($search_products_query)) {
      	echo "<tr>\n";
      	echo "<td class=\"menuBoxHeading\">" . '<a href="' . tep_href_link(FILENAME_STOCK, 'product_id=' . $products_search['products_id']) . '">' . $products_search['products_name'] . '</a>' . "</td>" . "<td class=\"menuBoxHeading\">" . $products_search['products_model'] . "</td><td class=\"menuBoxHeading\">" . $products_search['products_id'] . "</td><td class=\"menuBoxHeading\">" . $products_search['products_quantity'] . "</td>";
      	echo "</tr>\n";
      }
    }

?>
    </table>
  </td>
  </tr>
</table></td></tr></table>


<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="45%"><?php echo tep_draw_separator('pixel_trans.gif', '50%', 10); ?></td>
		<td width="50%"><?php echo tep_draw_separator('pixel_trans.gif', '50%', 10); ?></td>
	</tr>
	<tr>
		<td width="50%" valign="top">
<!--Checkin Checkout info-->

<?php
//if (!isset($HTTP_GET_VARS['quantity'])) {
    $q=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id='" . (int)$VARS['product_id'] . "' order by products_stock_attributes");

    if (tep_db_num_rows($q) >0) {    while($rec=tep_db_fetch_array($q)) {
?>

<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"><?php echo display_att($rec['products_stock_attributes']); ?></td></tr>
	<tr class="dataTableHeadingRow">
              	  <td class="dataTableHeadingContent">User</td>
              	  <td class="dataTableHeadingContent">Date</td>
              	  <td class="dataTableHeadingContent">Comments</td>
              	  <td class="dataTableHeadingContent">Quantity</td>
              	  <td class="dataTableHeadingContent">Latest</td>
              	  <td class="dataTableHeadingContent">Previous</td>
              	  <td class="dataTableHeadingContent">Comments</td>
  </tr>
  <?php
    $his_q=tep_db_query("select * from " . TABLE_IK_STOCK_PRODUCTS_HISTORY . " where products_id='" . (int)$VARS['product_id'] . "' and products_attributes = '" . $rec['products_stock_attributes'] ."' and date_modified > concat(date_add(curdate(), INTERVAL " . STOCK_CHECK_PERIOD . " month), ' 00:00:00') order by date_modified desc");
           while($his=tep_db_fetch_array($his_q))
           {
           	 if ($alternate == "1")
             {$color = "#ffffff"; $alternate = "2"; }
	           else
	           {$color = "#efefef"; $alternate = "1"; }

	           echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
   	         echo "<td class=smalltext>".$his['user_name']."</td>";
   	         echo "<td class=smalltext>".$his['date_modified']."</td>";
   	         echo "<td class=smalltext>".$his['comments']."</td>";
   	         echo "<td class=smalltext>".$his['quantity']."</td>";
   	         echo "<td class=smalltext>".$his['latest_qty']."</td>";
   	         echo "<td class=smalltext>".$his['previous_qty']."</td>";
   	         echo "<td class=smalltext>".$checkout_reasons_array[$his['ik_stock_checkout_reasons_id']]."</td>";
   	         echo "</tr>";
           }
  ?>
</table>
<?php }
    }
    else {
      if (tep_not_null($list['has_children'])) {
      	$sl_pid_q = tep_db_query("select products_id from products where parent_id='" . (int)$VARS['product_id'] . "'");
      	$sl_pid_str = "";
      	while ($sl_pid = tep_db_fetch_array($sl_pid_q)) {
          $sl_pid_str .= $sl_pid['products_id'] . ",";
      	}
      	$sl_pid_str = " in (" . substr($sl_pid_str, 0, -1) . ")";

        $sl_pv_values = array();
        $products_s_q = tep_db_query("select opv.products_id, opv.products_variants_values_id, opv.default_combo, opvv.products_variants_groups_id, opvv.title as vtitle, opvv.image, opvv.sort_order as vorder, opvg.title as gtitle, opvg.sort_order as gorder from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id where opv.products_id " . $sl_pid_str . " order by opvg.sort_order, opvv.sort_order");
        while ($products_s = tep_db_fetch_array($products_s_q)) {
          $sl_pv_values[$products_s['products_id']] .= $products_s['vtitle'] . " ";
        }

        asort($sl_pv_values);
        foreach ($sl_pv_values as $k => $v) {
        	$his_q=tep_db_query("select * from " . TABLE_IK_STOCK_PRODUCTS_HISTORY . " where products_id='" . (int)$k . "' and date_modified > concat(date_add(curdate(), INTERVAL " . STOCK_CHECK_PERIOD . " month), ' 00:00:00') order by date_modified desc");
?>
<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
	<tr><td colspan="2" width="45%"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"><?php echo '<a href="' . tep_href_link(FILENAME_STOCK, 'product_id=' . $k) . '" target="_blank">' . $sl_pv_values[$k] . '</a>'; ?></td></tr>
	<tr class="dataTableHeadingRow">
              	  <td class="dataTableHeadingContent">User</td>
              	  <td class="dataTableHeadingContent">Date</td>
              	  <td class="dataTableHeadingContent">Comments</td>
              	  <td class="dataTableHeadingContent">Quantity</td>
              	  <td class="dataTableHeadingContent">Latest</td>
              	  <td class="dataTableHeadingContent">Previous</td>
              	  <td class="dataTableHeadingContent">Comments</td>
  </tr>
<?php
    	    while($rec=tep_db_fetch_array($his_q)) {
           	 if ($alternate == "1")
             {$color = "#ffffff"; $alternate = "2"; }
	           else
	           {$color = "#efefef"; $alternate = "1"; }

	           echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
   	         echo "<td class=smalltext>".$rec['user_name']."</td>";
   	         echo "<td class=smalltext>".$rec['date_modified']."</td>";
   	         echo "<td class=smalltext>".$rec['comments']."</td>";
   	         echo "<td class=smalltext>".$rec['quantity']."</td>";
   	         echo "<td class=smalltext>".$rec['latest_qty']."</td>";
   	         echo "<td class=smalltext>".$rec['previous_qty']."</td>";
   	         echo "<td class=smalltext>".$checkout_reasons_array[$rec['ik_stock_checkout_reasons_id']]."</td>";
   	         echo "</tr>";
    	    }
?>
</table>
<?php
        }
      }
      else {
        $his_q=tep_db_query("select * from " . TABLE_IK_STOCK_PRODUCTS_HISTORY . " where products_id='" . (int)$VARS['product_id'] . "' and date_modified > concat(date_add(curdate(), INTERVAL " . STOCK_CHECK_PERIOD . " month), ' 00:00:00') order by date_modified desc");
?>
<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
	<tr><td colspan="2" width="45%"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"></td></tr>
	<tr class="dataTableHeadingRow">
              	  <td class="dataTableHeadingContent">User</td>
              	  <td class="dataTableHeadingContent">Date</td>
              	  <td class="dataTableHeadingContent">Comments</td>
              	  <td class="dataTableHeadingContent">Quantity</td>
              	  <td class="dataTableHeadingContent">Latest</td>
              	  <td class="dataTableHeadingContent">Previous</td>
              	  <td class="dataTableHeadingContent">Comments</td>
  </tr>
<?php
    	  while($rec=tep_db_fetch_array($his_q)) {
           	 if ($alternate == "1")
             {$color = "#ffffff"; $alternate = "2"; }
	           else
	           {$color = "#efefef"; $alternate = "1"; }

	           echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
   	         echo "<td class=smalltext>".$rec['user_name']."</td>";
   	         echo "<td class=smalltext>".$rec['date_modified']."</td>";
   	         echo "<td class=smalltext>".$rec['comments']."</td>";
   	         echo "<td class=smalltext>".$rec['quantity']."</td>";
   	         echo "<td class=smalltext>".$rec['latest_qty']."</td>";
   	         echo "<td class=smalltext>".$rec['previous_qty']."</td>";
   	         echo "<td class=smalltext>".$checkout_reasons_array[$rec['ik_stock_checkout_reasons_id']]."</td>";
   	         echo "</tr>";
    	  }
?>
</table>
<?php
      }
    }
//}
?>
		</td>
		<td width="50%" valign="top" style="padding-left:10px;">
<!--Orders info-->
<?php
     //if (!isset($HTTP_GET_VARS['quantity'])) {
     	   $orders_statuses = array();
         $orders_status_array = array();
         $orders_status_query = tep_db_query("select orders_status_id, orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = '" . (int)$languages_id . "' order by orders_status_id");
         while ($orders_status = tep_db_fetch_array($orders_status_query)) {
           $orders_statuses[] = array('id' => $orders_status['orders_status_id'],
                                      'text' => $orders_status['orders_status_name']);
           $orders_status_array[$orders_status['orders_status_id']] = $orders_status['orders_status_name'];
         }
         //$orders_status_array[$status]
     	
     	 $q=tep_db_query("select * from " . TABLE_PRODUCTS_STOCK . " where products_id='" . (int)$VARS['product_id'] . "' order by products_stock_attributes");
     	if (tep_db_num_rows($q) >0) {
        while($rec=tep_db_fetch_array($q)) {
?>

<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
  <tr><td width="100%" colspan="5"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"><?php echo display_att($rec['products_stock_attributes']); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"><?php $prebonded_array = array(40,41,182,183); if (in_array($product_category_id, $prebonded_array)) { echo "<u><a href=\"" . tep_href_link('stock_prebonded.php', 'product_id='.$VARS['product_id'].'&is_prebonded=1') ."\" target=\"_blank\">click to see the earlier history</a></u>";}?></td></tr>
	<tr class="dataTableHeadingRow">
              	  <td class="dataTableHeadingContent">Order#</td>
              	  <td class="dataTableHeadingContent">Order Date</td>
              	  <td class="dataTableHeadingContent">Qty</td>
              	  <td class="dataTableHeadingContent">OrderStatus</td>
              	  <td class="dataTableHeadingContent">Customer</td>
              	  <td class="dataTableHeadingContent">Cash&Carry</td>
              	  <td class="dataTableHeadingContent">Bundle</td>
  </tr>
  <?php
    //$ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -40 day), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' and op.products_stock_attributes='" . $rec['products_stock_attributes'] . "' order by o.date_purchased desc");
          $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' and op.products_stock_attributes='" . $rec['products_stock_attributes'] . "'
          union all
          select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and op.products_id = pb.bundle_id and pb.products_stock_attributes='" . $rec['products_stock_attributes'] . "' and pb.subproduct_id='" . (int)$VARS['product_id'] . "' order by date_purchased desc");
          while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
           	if ($alternate == "1")
            {$color = "#ffffff"; $alternate = "2"; }
	          else
	          {$color = "#efefef"; $alternate = "1"; }
 
            if (tep_not_null($ordersproducts['subproduct_qty'])) {
	           	$ordersproducts_qty_f = $ordersproducts['subproduct_qty'];
	           	$is_bundle = "*";
	          }
	          else {
	            $ordersproducts_qty_f = 1;
	            $is_bundle = "";
	          }
	          if ($ordersproducts['cash_carry'] >0) {
	            $is_cashcarry = "*";
	          }
	          else {
	            $is_cashcarry = "";
	          }

	          echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
   	        echo "<td class=smalltext width=\"10%\">". '<a href="' . tep_href_link(FILENAME_ORDERS, 'oID=' . $ordersproducts['orders_id'] . '&action=edit') . '">' . $ordersproducts['orders_id']. '</a>' . "</td>";
   	        echo "<td class=smalltext width=\"20%\">".$ordersproducts['date_purchased']."</td>";
   	        echo "<td class=smalltext width=\"5%\">".($ordersproducts['products_quantity'] * $ordersproducts_qty_f)."</td>";
   	        echo "<td class=smalltext width=\"20%\">".$orders_status_array[$ordersproducts['orders_status']]."</td>";
   	        echo "<td class=smalltext width=\"15%\">". '<a href="' . tep_href_link(FILENAME_ORDERS, 'cID=' . $ordersproducts['customers_id']) . '">' . $ordersproducts['customers_firstname'] ." ". $ordersproducts['customers_lastname'] . "</td>";
   	        echo "<td class=smalltext align=\"center\" width=\"5%\">".$is_cashcarry."</td>";
   	        echo "<td class=smalltext width=\"5%\">".$is_bundle."</td>";
   	        echo "</tr>";
          }
 ?>
</table>
<?php   }
     	}
     	else {
        if (tep_not_null($list['has_children'])) {
        	if (isset($sl_pv_values)) {
        		foreach ($sl_pv_values as $k => $v) {
?>
<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
  <tr><td width="100%" colspan="5"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"><?php echo '<a href="' . tep_href_link(FILENAME_STOCK, 'product_id=' . $k) . '" target="_blank">' . $sl_pv_values[$k] . '</a>'; ?></td></tr>
	<tr class="dataTableHeadingRow">
	                <!--<td class="dataTableHeadingContent"></td>-->
              	  <td class="dataTableHeadingContent">Order#</td>
              	  <td class="dataTableHeadingContent">Order Date</td>
              	  <td class="dataTableHeadingContent">Qty</td>
              	  <td class="dataTableHeadingContent">OrderStatus</td>
              	  <td class="dataTableHeadingContent">Customer</td>
              	  <td class="dataTableHeadingContent">Cash&Carry</td>
              	  <td class="dataTableHeadingContent">Bundle</td>
  </tr>
  <?php

    $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and op.products_id = '" . (int)$k . "'
    union all
    select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and op.products_id = pb.bundle_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and pb.subproduct_id = '" . (int)$k . "' order by date_purchased desc");

              while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
                if ($alternate == "1")
                {$color = "#ffffff"; $alternate = "2"; }
                else
                {$color = "#efefef"; $alternate = "1"; }
  
                if (tep_not_null($ordersproducts['subproduct_qty'])) {
                  $ordersproducts_qty_f = $ordersproducts['subproduct_qty'];
                  $is_bundle = "*";
                }
                else {
                  $ordersproducts_qty_f = 1;
                  $is_bundle = "";
                }
                if ($ordersproducts['cash_carry'] >0) {
                  $is_cashcarry = "*";
                }
                else {
                  $is_cashcarry = "";
                }

                echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
                //echo "<td class=smalltext width=\"10%\">" . ((tep_not_null($ordersproducts['products_stock_attributes'])) ? display_att($ordersproducts['products_stock_attributes']) : "") . "</td>";
                echo "<td class=smalltext width=\"10%\">" . '<a href="' . tep_href_link(FILENAME_ORDERS, 'oID=' . $ordersproducts['orders_id'] . '&action=edit') . '">' . $ordersproducts['orders_id'] . '</a>' . "</td>";
                echo "<td class=smalltext width=\"15%\">".$ordersproducts['date_purchased']."</td>";
                echo "<td class=smalltext width=\"5%\">" . ($ordersproducts['products_quantity'] * $ordersproducts_qty_f) . "</td>";
                echo "<td class=smalltext width=\"15%\">".$orders_status_array[$ordersproducts['orders_status']]."</td>";
                echo "<td class=smalltext width=\"15%\">". '<a href="' . tep_href_link(FILENAME_ORDERS, 'cID=' . $ordersproducts['customers_id']) . '">' . $ordersproducts['customers_firstname'] ." ". $ordersproducts['customers_lastname'] . "</a></td>";
                echo "<td class=smalltext align=\"center\" width=\"5%\">".$is_cashcarry."</td>";
                echo "<td class=smalltext width=\"5%\">".$is_bundle."</td>";
                echo "</tr>";
              }
 ?>
</table>
<?php
            }
        	}
     	  }
     	  else {
?>
<table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
  <tr><td width="100%" colspan="5"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
	<tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"></td></tr>
	<tr class="dataTableHeadingRow">
	                <!--<td class="dataTableHeadingContent"></td>-->
              	  <td class="dataTableHeadingContent">Order#</td>
              	  <td class="dataTableHeadingContent">Order Date</td>
              	  <td class="dataTableHeadingContent">Qty</td>
              	  <td class="dataTableHeadingContent">OrderStatus</td>
              	  <td class="dataTableHeadingContent">Customer</td>
              	  <td class="dataTableHeadingContent">Cash&Carry</td>
              	  <td class="dataTableHeadingContent">Bundle</td>
  </tr>
  <?php
    //$ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL -40 day), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "' order by o.date_purchased desc");
    $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "'
    union all
    select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_ORDERS . " o left join " . TABLE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and op.products_id = pb.bundle_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and pb.subproduct_id = '" . (int)$VARS['product_id'] . "' order by date_purchased desc");

           while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
           	 if ($alternate == "1")
             {$color = "#ffffff"; $alternate = "2"; }
	           else
	           {$color = "#efefef"; $alternate = "1"; }

	           if (tep_not_null($ordersproducts['subproduct_qty'])) {
	           	 $ordersproducts_qty_f = $ordersproducts['subproduct_qty'];
	           	 $is_bundle = "*";
	           }
	           else {
	           	 $ordersproducts_qty_f = 1;
	           	 $is_bundle = "";
	           }
	           if ($ordersproducts['cash_carry'] >0) {
	           	 $is_cashcarry = "*";
	           }
	           else {
	           	 $is_cashcarry = "";
	           }

	           echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
	           //echo "<td class=smalltext width=\"10%\">" . ((tep_not_null($ordersproducts['products_stock_attributes'])) ? display_att($ordersproducts['products_stock_attributes']) : "") . "</td>";
   	         echo "<td class=smalltext width=\"10%\">" . '<a href="' . tep_href_link(FILENAME_ORDERS, 'oID=' . $ordersproducts['orders_id'] . '&action=edit') . '">' . $ordersproducts['orders_id'] . '</a>' . "</td>";
   	         echo "<td class=smalltext width=\"15%\">".$ordersproducts['date_purchased']."</td>";
   	         echo "<td class=smalltext width=\"5%\">" . ($ordersproducts['products_quantity'] * $ordersproducts_qty_f) . "</td>";
   	         echo "<td class=smalltext width=\"15%\">".$orders_status_array[$ordersproducts['orders_status']]."</td>";
   	         echo "<td class=smalltext width=\"15%\">". '<a href="' . tep_href_link(FILENAME_ORDERS, 'cID=' . $ordersproducts['customers_id']) . '">' . $ordersproducts['customers_firstname'] ." ". $ordersproducts['customers_lastname'] . "</a></td>";
   	         echo "<td class=smalltext align=\"center\" width=\"5%\">".$is_cashcarry."</td>";
   	         echo "<td class=smalltext width=\"5%\">".$is_bundle."</td>";
   	         echo "</tr>";
           }
 ?>
</table>
<?php
     	  }
      }
?>


		</td>
	</tr>
	<tr>
	  <td width="50%"></td>
	  <td width="50%" valign="top" style="padding-left:10px;">
        <?php
          $ordersproducts_q = tep_db_query("select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, op.products_stock_attributes, null as bundle_id, null as subproduct_qty from " . TABLE_TRADE_ORDERS_PRODUCTS . " op, " . TABLE_TRADE_ORDERS . " o left join " . TABLE_TRADE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and op.products_id = '" . (int)$VARS['product_id'] . "'
          union all
          select c.customers_firstname, c.customers_lastname, o.customers_id, o.orders_id, o.orders_status, o.date_purchased, o.cash_carry, op.products_quantity, pb.products_stock_attributes, pb.bundle_id, pb.subproduct_qty from " . TABLE_TRADE_ORDERS_PRODUCTS . " op, " . TABLE_PRODUCTS_BUNDLES . " pb, " . TABLE_TRADE_ORDERS . " o left join " . TABLE_TRADE_CUSTOMERS . " c on c.customers_id = o.customers_id where o.orders_id = op.orders_id and op.products_id = pb.bundle_id and o.date_purchased > concat(date_add(curdate(), INTERVAL " . STOCK_ORDER_PERIOD . " month), ' 00:00:00') and pb.subproduct_id = '" . (int)$VARS['product_id'] . "' order by date_purchased desc");
          if (tep_db_num_rows($ordersproducts_q)) {
          ?>
            <table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
              <tr><td width="100%" colspan="5"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
              <tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"></td></tr>
              <tr class="dataTableHeadingRow">
                              <!--<td class="dataTableHeadingContent"></td>-->
                              <td class="dataTableHeadingContent">Trade Order#</td>
                              <td class="dataTableHeadingContent">Order Date</td>
                              <td class="dataTableHeadingContent">Qty</td>
                              <td class="dataTableHeadingContent">OrderStatus</td>
                              <td class="dataTableHeadingContent">Customer</td>
                              <td class="dataTableHeadingContent">Cash&Carry</td>
                              <td class="dataTableHeadingContent">Bundle</td>
              </tr>
          <?php
                 while($ordersproducts = tep_db_fetch_array($ordersproducts_q)) {
                   if ($alternate == "1") {$color = "#ffffff"; $alternate = "2"; }
                   else {$color = "#efefef"; $alternate = "1"; }
      
                   if (tep_not_null($ordersproducts['subproduct_qty'])) {
                     $ordersproducts_qty_f = $ordersproducts['subproduct_qty'];
                     $is_bundle = "*";
                   }
                   else {
                     $ordersproducts_qty_f = 1;
                     $is_bundle = "";
                   }
                   if ($ordersproducts['cash_carry'] >0) {
                     $is_cashcarry = "*";
                   }
                   else {
                     $is_cashcarry = "";
                   }
      
                   echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
                   //echo "<td class=smalltext width=\"10%\">" . ((tep_not_null($ordersproducts['products_stock_attributes'])) ? display_att($ordersproducts['products_stock_attributes']) : "") . "</td>";
                   echo "<td class=smalltext width=\"10%\">" . '' . $ordersproducts['orders_id'] . '' . "</td>";
                   echo "<td class=smalltext width=\"15%\">".$ordersproducts['date_purchased']."</td>";
                   echo "<td class=smalltext width=\"5%\">" . ($ordersproducts['products_quantity'] * $ordersproducts_qty_f) . "</td>";
                   echo "<td class=smalltext width=\"15%\">".$orders_status_array[$ordersproducts['orders_status']]."</td>";
                   echo "<td class=smalltext width=\"15%\">". '' . $ordersproducts['customers_firstname'] ." ". $ordersproducts['customers_lastname'] . "</td>";
                   echo "<td class=smalltext align=\"center\" width=\"5%\">".$is_cashcarry."</td>";
                   echo "<td class=smalltext width=\"5%\">".$is_bundle."</td>";
                   echo "</tr>";
                 }
          ?>
                  </table>
          <?php
          }
       ?>


	  </td>
	</tr>
	<tr>
		<td width="50%">
      <?php
        $his_q=tep_db_query("select * from ik_stock_products_history_dump where products_id='" . (int)$VARS['product_id'] . "' and date_modified > concat(date_add(curdate(), INTERVAL " . STOCK_CHECK_PERIOD . " month), ' 00:00:00') order by products_attributes, date_modified desc");
        if (tep_db_num_rows($his_q)) {
      ?>
          <table width="100%" class="boxText" border="0" cellspacing="0" cellpadding="0">
            <tr><td colspan="2" width="45%"><?php echo tep_draw_separator('pixel_trans.gif', 5, 5); ?></td></tr>
            <tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background: #ffffff; color: #622703;" colspan="5"></td></tr>
            <tr class="dataTableHeadingRow">
                            <td class="dataTableHeadingContent"></td>
                            <td class="dataTableHeadingContent">User</td>
                            <td class="dataTableHeadingContent">Date</td>
                            <td class="dataTableHeadingContent">Comments</td>
                            <td class="dataTableHeadingContent">Quantity</td>
                            <td class="dataTableHeadingContent">Latest</td>
                            <td class="dataTableHeadingContent">Previous</td>
                            <td class="dataTableHeadingContent">Comments</td>
            </tr>
          <?php
                while($rec=tep_db_fetch_array($his_q)) {
                       if ($alternate == "1")
                       {$color = "#ffffff"; $alternate = "2"; }
                       else
                       {$color = "#efefef"; $alternate = "1"; }

                       echo "<tr valign=\"top\" bgcolor=\"" . $color . "\">";
                       echo "<td class=smalltext>" . (tep_not_null($rec['products_attributes']) ? display_att($rec['products_attributes']) : "") . "</td>";
                       echo "<td class=smalltext>".$rec['user_name']."</td>";
                       echo "<td class=smalltext>".$rec['date_modified']."</td>";
                       echo "<td class=smalltext>".$rec['comments']."</td>";
                       echo "<td class=smalltext>".$rec['quantity']."</td>";
                       echo "<td class=smalltext>".$rec['latest_qty']."</td>";
                       echo "<td class=smalltext>".$rec['previous_qty']."</td>";
                       echo "<td class=smalltext>".$checkout_reasons_array[$rec['ik_stock_checkout_reasons_id']]."</td>";
                       echo "</tr>";
                }
          ?>
          </table>
        <?php
        }
        ?>
		</td>
		<td width="50%"></td>
	</tr>	
	<tr>
		<td width="50%"><?php echo tep_draw_separator('pixel_trans.gif', '50%', 200); ?></td>
		<td width="50%"><?php echo tep_draw_separator('pixel_trans.gif', '50%', 200); ?></td>
	</tr>
	<tr>
		<td width="50%">
		  <table width="100%" class="boxText" border="0" cellspacing="1" cellpadding="0">
			  <?php
          if ($admin['login_groups_id'] > 1) {
    	      $_supplier_p_q = tep_db_query("select sop.suppliers_orders_products_id, sop.suppliers_orders_id, sop.products_attributes, sop.order_quantity, sop.delivered_quantity, sop.suppliers_orders_products_status, so.suppliers_orders_status, so.user_name, so.date_created, so.date_verified, sos.suppliers_orders_status_name from " . TABLE_SUPPLIERS_ORDERS_PRODUCTS . " sop left join " . TABLE_SUPPLIERS_ORDERS . " so on sop.suppliers_orders_id=so.suppliers_orders_id inner join " . TABLE_SUPPLIERS_ORDERS_STATUS . " sos on so.suppliers_orders_status=sos.suppliers_orders_status_id left join " . TABLE_ADMINISTRATORS . " a on so.user_name=a.user_name WHERE products_id = '" . (int)$VARS['product_id'] . "' and a.admin_groups_id>1 order by sop.suppliers_orders_id desc");
          }
          else {
			  	  $_supplier_p_q = tep_db_query("select sop.suppliers_orders_products_id, sop.suppliers_orders_id, sop.products_attributes, sop.order_quantity, sop.delivered_quantity, sop.suppliers_orders_products_status, so.suppliers_orders_status, so.user_name, so.date_created, so.date_verified, sos.suppliers_orders_status_name from " . TABLE_SUPPLIERS_ORDERS_PRODUCTS . " sop left join " . TABLE_SUPPLIERS_ORDERS . " so on sop.suppliers_orders_id=so.suppliers_orders_id inner join " . TABLE_SUPPLIERS_ORDERS_STATUS . " sos on so.suppliers_orders_status=sos.suppliers_orders_status_id WHERE products_id = '" . (int)$VARS['product_id'] . "' order by sop.suppliers_orders_id desc");
			  	}
			  	if (tep_db_num_rows($_supplier_p_q)) {
        ?>
          <tr class="dataTableHeadingRow"><td class="dataTableHeadingContent" style="background:#7F7F7F;color:#fff;" colspan="7">Supplier Orders History</td></tr>
          <tr class="dataTableHeadingRow">
            <td class="dataTableHeadingContent">User</td>
            <td class="dataTableHeadingContent">Created</td>
            <td class="dataTableHeadingContent">Verified</td>
            <td class="dataTableHeadingContent">Order Status</td>
            <td class="dataTableHeadingContent">Ordered</td>
            <td class="dataTableHeadingContent">Delivered</td>
            <td class="dataTableHeadingContent">Attributes</td>
          </tr>
        <?php
			  	  while($_supplier_p=tep_db_fetch_array($_supplier_p_q)) {
			  ?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);">
                <td class="dataTableContent"><?php echo $_supplier_p['user_name'];?></td>
                <td class="dataTableContent"><?php echo $_supplier_p['date_created'];?></td>
                <td class="dataTableContent"><?php if (tep_not_null($_supplier_p['date_verified'])) echo $_supplier_p['date_verified'];?></td>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIERS_ADMIN, 'action=view&s_oid='.$_supplier_p['suppliers_orders_id']) . '" target="_blank">' . $_supplier_p['suppliers_orders_status_name'] . '</a>';?></td>
                <td class="dataTableContent"><?php echo $_supplier_p['order_quantity'];?></td>
                <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_SUPPLIERS_ADMIN, 'action=viewproduct&s_oid='.$_supplier_p['suppliers_orders_id'].'&s_opid='.$_supplier_p['suppliers_orders_products_id']) . '" target="_blank">' . $_supplier_p['delivered_quantity'] . '</a>';?></td>
                <td class="dataTableContent"><?php if (tep_not_null($_supplier_p['products_attributes'])) echo display_att($_supplier_p['products_attributes']);?></td>
              </tr>
        <?php
            }
          }
        ?>
      </table>
		</td>
		<td width="50%"><?php echo tep_draw_separator('pixel_trans.gif', '50%', 10); ?></td>
	</tr>
	
</table>


<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php'); 
?>