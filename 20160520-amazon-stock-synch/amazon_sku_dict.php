<?php
  //amazon_sku_dict.php
  set_time_limit(200);
  require('includes/application_top.php');

  $sku_barcode = array();
  $file_handle = fopen("z-temp/20160601-active-listing-report-2463043723016953.txt", "r");
  $counter=0;

  while (!feof($file_handle)) {
  	$counter++;
    $line_of_text = fgets($file_handle, 4096);
    if ($counter <=1) continue;
    //if ($counter >10) break;
  	$splitcontents = str_getcsv($line_of_text, "\t", '"');
  	if (tep_not_null($splitcontents[3]) && tep_not_null($splitcontents[22])) {
  		if (0 == preg_match('/[^\x00-\x7f]/', $splitcontents[3])) {} //check if contain non-ascii char
  		else { $splitcontents[3] = preg_replace("/[\x80-\xff]/", "-", $splitcontents[3]); } //replace non-ascii char (or extended ascii char by hyphen)
  		$sku_barcode[$splitcontents[3]] = $splitcontents[22];
  	}
  }

  $dict = array();
  foreach ($sku_barcode as $k => $v) {
  	$_ori_sku = tep_db_fetch_array(tep_db_query("select ori_products_id from ik_stock_products WHERE barcode = '" . tep_db_input($v) . "'"));
  	if (tep_not_null($_ori_sku['ori_products_id'])) {
  		$dict[$k] = $_ori_sku['ori_products_id'];
  	}
  }

$filename= 'amazon_sku_dict_.php';
$delimiter = ",";
header('Content-type: text/plain;');
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");
$output = "<?php" . "\n";
$output .= '$amazon_sku_dict = array(' . "\n";
foreach ($dict as $k => $v) {
  $output .= '"' . $k . '"=>' . $v . ',';
}
$output = substr($output, 0, -1);
$output .=  ");" . "\n"; 
$output .=  "?>";
echo $output;
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
