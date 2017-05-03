<?php
  /*
  20170502
  /admin/ms.php
  
  */

  set_time_limit(200);

  require('includes/application_top.php');


  //require(DIR_WS_CLASSES . 'currencies.php');
  //$currencies = new currencies();

  require(DIR_WS_INCLUDES . 'template_top.php');

?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo "Manual Script"; ?></td>
            <td class="pageHeading" align="right"></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td>
<?php
        $o_q = tep_db_query(" select p.products_id, p.parent_id, pd.products_name, p.manufacturers_id, pi.image_filename from products p left join products_images pi on (p.products_id = pi.products_id and pi.product_page = '1') left join products_description pd on p.products_id=pd.products_id where p.products_status='1' and pd.language_id = 1 ORDER BY p.manufacturers_id ASC");
        while ($o_ = tep_db_fetch_array($o_q)) {
            if (!file_exists(DIR_FS_CATALOG . DIR_WS_IMAGES . $o_['image_filename']) || !tep_not_null($o_['image_filename'])) {
                if ( tep_not_null($o_['parent_id']) && (int)$o_['parent_id'] >0 ) {
                   
                  $att_color = "";
                  $att_length = "";
                  $products_attr_q = tep_db_query("select opv.products_variants_values_id, opv.default_combo, opvv.products_variants_groups_id, opvv.title as vtitle, opvv.image, opvv.sort_order as vorder, opvg.title as gtitle, opvg.sort_order as gorder from " . TABLE_OSC_PRODUCTS_VARIANTS . " opv left join " . TABLE_OSC_PRODUCTS_VARIANTS_VALUES . " opvv on opv.products_variants_values_id=opvv.id left join " . TABLE_OSC_PRODUCTS_VARIANTS_GROUP . " opvg on opvv.products_variants_groups_id=opvg.id where opv.products_id='" . (int)$o_['products_id'] . "' order by opvg.sort_order, opvv.sort_order");
                  while ($products_attr = tep_db_fetch_array($products_attr_q)) {
                    if (tep_not_null($products_attr['image'])) {
                        $att_color = $products_attr['vtitle'];
                    }
                    elseif (preg_match("/Colour/i", $products_attr['gtitle'])) { #Colour for accessories not hair with image thumb
                        $att_color = $products_attr['vtitle'];
                    }
                    elseif (preg_match("/length|size/i", $products_attr['gtitle'])) {
                        $att_length = $products_attr['vtitle'];
                    }
                  }
                }
                echo '<a href="stock.php?product_id=' . $o_['products_id'] . '" target="_blank">' . $o_['products_name'] . "," . $att_length . " " . $att_color . "," . $o_['image_filename'] . "," . $o_['products_id'] . '</a>' . "<br />";
            }
        }
?>
        </td>
      </tr>
    </table>
    
    
    
<?php
  require(DIR_WS_INCLUDES . 'template_bottom.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>