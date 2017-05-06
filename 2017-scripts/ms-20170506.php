<?php
  /*
  20170506
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
#remove invalid telephone char, which is especially important for sagepay integration to avoid the warning [Sagepay 5045: The billing phone contain invalid characters]
$entry_query = tep_db_query("select c.customers_id, c.customers_telephone, length(c.customers_telephone) from " . TABLE_CUSTOMERS . " c where (c.customers_telephone is not null and length(c.customers_telephone)>0) order by c.customers_id desc");

while ($entry = tep_db_fetch_array($entry_query)) {
    $matches = [];
    if (preg_match("/[^A-Za-z0-9()\+\-\s]/", $entry['customers_telephone'], $matches)) {
        $update_telephone = preg_replace("/[^A-Za-z0-9()\+\-\s]/", '', $entry['customers_telephone']);
        tep_db_query("update " . TABLE_CUSTOMERS . " set customers_telephone = '" . tep_db_input(trim($update_telephone)) . "' where customers_id = '" . (int)$entry['customers_id'] . "'");
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