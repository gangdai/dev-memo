<?php


  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . 'manual_tocart.php');

  if (!is_goodip() && !tep_session_is_registered('manual_order'))
  { exit; }

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_SHOPPING_CART));

?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo SITEMAP_TITLE; ?></title>
      <meta name="description" id="description" content="" />
      <meta name="keywords" id="keywords" content="" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

     	<meta name="robots" content="noindex,nofollow">
      <meta name="audience" content="all">


      <meta http-equiv="Content-Language" content="EN-GB">
      <meta name="rights-standard" content="<?php echo STORE_NAME;?>" />

      <!--CSS-->
      <link rel="stylesheet" href="css/normalize.css" />
      <link rel="stylesheet" href="styles.css" />

      <link rel="shortcut icon" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'fav_icon.ico';?>" />
      <link rel="apple-touch-icon-precomposed" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'ios_icon.png';?>" />

      <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" />
      <link rel="stylesheet" href="css/responsive.css" />

      <!--JS-->
      <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
      <script src="//code.jquery.com/jquery-migrate-1.4.0.min.js"></script>

      <script src="js/featherlight.min.js" defer></script>

      <script src="js/jquery.mmenu.min.js" defer></script>
      <script src="js/2017.js" defer></script>

      <!--CSS-->
      <link rel="stylesheet" href="css/jquery.mmenu.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.positioning.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.pagedim.css" />

      <link rel="stylesheet" href="css/featherlight.min.css" />
		  <script>
			/*<![CDATA[*/
			$(document).ready(function(){

			});
			/*]]>*/
		  </script>

      <script type="text/javascript" src="bundle.js"></script>
      <style>
/*shopping cart*/
	.cart-items{width:100%; margin:20px 0; border-top:solid 1px #dfdfdf; text-align:left;}
	.cart-items.none-t-border {border-top:none;}
	.cart-items thead tr th{background:#f4f4f4; padding:10px 0; text-transform:uppercase;}
	.cart-items thead tr th span{padding:0 10px;}
	.cart-items thead tr th.description{width:55%;}
	.cart-items thead tr th.options{width:30%;}
	.cart-items thead tr th.price{width:15%; text-align:right;}

	.cart-items tbody tr{border-bottom:solid 1px #d6d6d6;}
	.cart-items tbody tr td{}
	.cart-items tbody tr td .inner{margin:7px 0; padding:10px; height:130px; position:relative;width:inherit;}
	.cart-items tbody tr td .inner p{font-size:12px; line-height:18px;}
	.cart-items tbody tr td .inner p a{font-size:12px; text-decoration:underline; color:#000;}
	.cart-items tbody tr td .inner p a:hover{text-decoration:underline;cursor:pointer;}
	.cart-items tbody tr td .inner p a.bt-update{text-decoration:none; padding:6px 10px; background:#eeeadf; color:#333;font-size:0.7em;text-transform: uppercase;}
	.cart-items tbody tr td.description .inner{padding-left:0; border-right:1px dotted #D6D6D6;}
	.cart-items tbody tr td.options .inner{border-right:1px dotted #D6D6D6;}
	.cart-items tbody tr td.options .inner p{line-height:20px;}
	.cart-items tbody tr td.options .inner span{display:inline-block; width:90px;}
	.cart-items tbody tr td.price .inner{text-align:right;}
	.cart-items tbody tr td.price .inner p{font-size:15px;}

	.cart-items .cart-image{height:100px; margin-right:10px; float:left;}
	.cart-items .remove{position:absolute; bottom:10px; left:110px; color:#666;}
	.cart-items .remove label{font-size:12px;}
	.cart-items .remove label input{border:solid 1px #000;}
	.cart-items .remove label span{}
	.cart-items .price-rrp{/*text-decoration:line-through;*/ color:#396}
	.cart-items .quantity{position:absolute; bottom:10px;}
	.cart-items .quantity p span{font-size:12px; color:#666;}
	.cart-items .quantity input{border:solid 1px #dfdfdf; padding:5px 0; text-align:center;}

	.cart-items .cart-price{width:100%;}
	.cart-items .cart-price tr{border-bottom:none;}
	.cart-items .cart-price td{padding:20px 0;}
	.cart-items .cart-price td.price{text-align:right;}
	.cart-items .cart-price p{font-size:20px;}
	.cart-items .cart-price p span{}
	
	.cart-items .cart-shipping-info{padding:10px 0 20px 0; border-bottom:solid 2px #000;}
	.cart-items .cart-shipping-info p{line-height:18px;}
	.cart-items .cart-shipping-info p.free-shipping{display:block; padding:12px; background:#F7F5F0; color:#FF4F73; font-size:20px; text-align:center;}
	.cart-items .cart-shipping-info p.shipping-info{display:block; padding:12px; font-size:12px; text-align:center;}

	.cart-items #shopping-cart-buttons{}
	.cart-items #shopping-cart-buttons li{float:left; margin:0 10px 0 0;}
	.cart-items #shopping-cart-buttons li a{display:inline-block; text-decoration:none;}
	.cart-items #shopping-cart-buttons li#bt-continue-shopping{}
	.cart-items #shopping-cart-buttons li#bt-update{}

	.cart-items #shopping-cart-buttons li#bt-continue-shopping a{padding:12px 20px; background:#000; color:#fff;}
	.cart-items #shopping-cart-buttons li#bt-update a{padding:8px 18px; background:#000; color:#fff;}
	.cart-items #shopping-cart-buttons li#bt-continue-shopping a:hover{background:#666;}
	.cart-items #shopping-cart-buttons li#bt-update a:hover{background:#666;}
	.cart-items #shopping-cart-buttons li#bt-update input{border:none;background:#000; color:#fff;height:36px;padding:8px 18px;font-family:'Merriweather Sans',sans-serif;cursor:pointer;}
	.cart-items #shopping-cart-buttons li#bt-update input:hover{background:#666;}

	.cart-items .cart-checkout-buttons{width:100%; border-top:solid 1px #dfdfdf; border-bottom:solid 1px #dfdfdf;}
	.cart-items .cart-checkout-buttons.none-t-border {border-top:none;}
	.cart-items .cart-checkout-buttons tr{border-bottom:none;}
	.cart-items .cart-checkout-buttons tr td{text-align:center; padding:5px 0 10px 0;}
	.cart-items .cart-checkout-buttons tr td p{color:#666;}
	.cart-items .cart-checkout-buttons tr td ul{padding-top:10px;}
	.cart-items .cart-checkout-buttons tr td li{display:inline-block; color:#666; margin-bottom:15px;}
	.cart-items .cart-checkout-buttons tr td li a{text-decoration:none;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon{display:block; background:#f7f7f7; text-decoration:none; border:solid 1px #f7f7f7; margin:0 10px 10px 10px;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon:hover{border-color:#E3B263;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon span{display:block; border:solid 2px #fff;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon span img{margin:15px 25px;}

	.cart-items .cart-checkout-buttons tr td li a.co-icon#bt-co-card{ background:#2b2b2b;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon#bt-co-card span img{margin:15px 130px;}
	.cart-items .cart-checkout-buttons tr td li a.co-icon#bt-co-paypal{}
	.cart-items .cart-checkout-buttons tr td li a.co-icon#bt-co-amazon{}

  .cart-items .cart-checkout-buttons tr td li#bt-co-others{display:inline-block;text-decoration:none;margin:0px 10px 10px 10px;}
  .cart-items .cart-checkout-buttons tr td li#bt-co-others span.opaypal{display:inline-block; margin:0px 0px 10px 0px;}
  .cart-items .cart-checkout-buttons tr td li#bt-co-others span.oamazon{display:inline-block; position:relative;top:-10px;}
  .grid-02-02 .left {float:left; width:50%;}
      </style>

    </head>














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">



          <div class="inner">
            <div class="grid-full">
                 <h3>Manual Cart</h3>

                 <div>
                 	  <?php
                 	    echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_ADMIN_SHOPPINGCART, 'action=update_product', 'SSL')); require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_SHOPPING_CART);
                 	    echo '<div width="100%" id="ajaxDiv4">' . '<script type="text/javascript">' . "fillCart('" . tep_session_id() . "');" . '</script></div>' . "\n";
                 	    echo '</form>' . "\n\n";
                 	  ?>
                 </div>

                <div class="clearme"></div>
            </div>

            <div class="grid-02">
            	<?php echo tep_draw_form('cart_load', tep_href_link(basename($PHP_SELF)), 'get');?>
				      <div class="grid-02-01">
                	  <div class="table-wrapper readable-text">
                        <h2 class="sub-title">Search</h2>
                        <?php 
                          //$att3 = 'onClick="fillProducts_search(document.cart_load.keywords_selector.value, \'' . tep_session_id() . '\')"';
                          echo tep_draw_input_field('keywords_selector', '', 'class="register-input"');
                        ?>
                       	<br />
                       	<span class="bt" <?php echo 'onClick="fillProducts_search(document.cart_load.keywords_selector.value, \'' . tep_session_id() . '\')"';?>>Search</span>
                    </div><!--/.table-wrapper readable-text-->

                    <div class="table-wrapper readable-text">
                        <h2 class="sub-title">Barcode</h2>
                        <?php echo tep_draw_input_field('barcode_selector', '', 'class="register-input"'); ?>
                        <br />
                       	<span class="bt" <?php echo 'onClick="fillProducts_barcode(document.cart_load.barcode_selector.value, \'' . tep_session_id() . '\')"';?>>Search</span>
                    </div><!--/.table-wrapper readable-text-->
					    </div><!--/.grid-02-01-->

					    <div class="grid-02-02">
				        <div class="left">
               		  <div class="table-wrapper readable-text">
                    	<h2 class="sub-title">Category</h2>
                      <?php
                      //put the session (sid) into the Ajax php file so that the shopping cart could be handled
                      //$att1 = 'onChange="fillProducts(document.cart_load.subcategory_selector.options[document.cart_load.subcategory_selector.selectedIndex].value, \'' . tep_session_id() . '\')"';
                      echo tep_draw_pull_down_menu('subcategory_selector', tep_get_paths(array(array('id' => '', 'text' => PULL_DOWN_DEFAULT))), $cPath, 'onChange="fillProducts(document.cart_load.subcategory_selector.options[document.cart_load.subcategory_selector.selectedIndex].value, \'' . tep_session_id() . '\')" class="register-input admin-select" id="subcategory_selector" size="22"');
                      ?>
                    </div><!--/.table-wrapper readable-text-->
                </div><!--/.centre-->

              	<div class="left">
                	<div class="table-wrapper readable-text">
                    	<h2 class="sub-title">Brands</h2>
                      <?php
                      $manufacturers_query = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " union select '0', 'nobrand' order by manufacturers_name");
                      $manufacturers_array = array();
                      $manufacturers_array[] = array('id' => '', 'text' => "Please Select");
                      
                            while ($manufacturers = tep_db_fetch_array($manufacturers_query)) {
                              if ($manufacturers['manufacturers_name'] == 'IK') continue;
                              $manufacturers_array[] = array('id' => $manufacturers['manufacturers_id'],
                                                             'text' => $manufacturers['manufacturers_name']);
                            }
                      //$att2 = 'onChange="fillProducts_brands(document.cart_load.brands_selector.options[document.cart_load.brands_selector.selectedIndex].value, \'' . tep_session_id() . '\')"';
                      echo tep_draw_pull_down_menu('brands_selector', $manufacturers_array, '', 'onChange="fillProducts_brands(document.cart_load.brands_selector.options[document.cart_load.brands_selector.selectedIndex].value, \'' . tep_session_id() . '\')" class="register-input admin-select" id="brands_selector" size="22"');
                      ?>
                   	</div><!--/.table-wrapper readable-text-->
                </div><!--.left-->
                <div class="clearme"></div>
                

                	  <div class="table-wrapper readable-text">
                    	<h2 class="sub-title">Products</h2>
                      <?php
                        //Products dropdown
                        echo "<div id='ajaxDiv'>";
                        echo '<select name="subproduct_selector" onChange="fillAtts(document.cart_load.subproduct_selector.options[document.cart_load.subproduct_selector.selectedIndex].value)" class="register-input admin-select">';
                        echo '<option name="null" value="" selected="selected">Products..</option>';
                        $products = tep_db_query("select pd.products_name, p.products_id, p.products_model from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_PRODUCTS_TO_CATEGORIES . " p2c where pd.products_id = p.products_id and pd.language_id = '" . $languages_id . "' and p.products_id <> '" . $HTTP_GET_VARS['pID'] . "' and p.products_status='1' and p.products_id=p2c.products_id and p2c.categories_id='0' order by p.products_model");
                    
                        while($products_values = tep_db_fetch_array($products)) {
                          echo "\n" . '<option name="' . $products_values['products_model'] . '" value="' . $products_values['products_id'] . '">' . $products_values['products_model'] . ' - ' . $products_values['products_name'] . " (" . $products_values['products_id'] . ')</option>';
                        }
                        echo '</select>';
                        echo "</div>\n";
                      ?>

                   
                   		<br /><h2 class="sub-title">Options</h2>
                   		<?php echo "<div id='ajaxDiv1'></div><div id='ajaxDiv2'></div><div id='ajaxDiv3'></div>"; ?>


                    </div><!--/.table-wrapper readable-text-->

					    </div><!--/.grid-02-02-->

              <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
              <div class="clearme"></div>
              <?php echo '</form>' . "\n";?>
            </div><!--/.grid-02-->
          </div><!--/.inner-->



        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>