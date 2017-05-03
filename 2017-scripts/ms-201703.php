<?php
  /*
  201703
  /admin/ms.php
  
  
    30% off OUR PRICE in a certain category, db: specials
  */
  
  require('includes/application_top.php');



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


        $o_q = tep_db_query("select p2c.products_id, p2c.categories_id, p.products_price, s.specials_id, s.specials_new_products_price from products_to_categories p2c left join products p on p2c.products_id=p.products_id left join specials s on p2c.products_id=s.products_id where p2c.categories_id = 129 and p.products_status >0");
        while ($o_ = tep_db_fetch_array($o_q)) {
            //if ($o_['products_id'] != 6413) continue;
            if ( tep_not_null($o_['specials_id']) && tep_not_null($o_['specials_new_products_price'])) {
                 $sql_data_array = array('products_id' => $o_['products_id'],
                                        'specials_new_products_price' => round($o_['products_price'] * 0.7, 4),
                                        'specials_last_modified' => 'now()',
                                        'expires_date' => 'null',
                                        'date_status_change' => 'null',
                                        'status' => 1,
                                        'specials_limit' => 'null',
                                        'specials_sold' => 'null'
                                       );
                tep_db_perform(TABLE_SPECIALS, $sql_data_array, 'update', 'products_id=' . (int)$o_['products_id']);
                echo $o_['products_id'] . " " . $o_['products_price'] . " " . "===== Exist & Update<br />";
            } else {
                $sql_data_array = array('products_id' => $o_['products_id'],
                                        'specials_new_products_price' => round($o_['products_price'] * 0.7, 4),
                                        'specials_last_modified' => 'now()',
                                        'status' => 1,
                                        'customers_groups_id' => 0,
                                        'customers_id' => 0
                                       );
                tep_db_perform(TABLE_SPECIALS, $sql_data_array);
                echo $o_['products_id'] . " " . $o_['products_price'] . " " . $o_['specials_new_products_price'] . "<br />";
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
