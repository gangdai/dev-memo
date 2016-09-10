<?php
  require('includes/application_top.php');

  $category_depth = 'top';
  $categories_level = "top";
  if (isset($cPath) && tep_not_null($cPath))
  {
    $categories_products_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_TO_CATEGORIES . " where categories_id = '" . (int)$current_category_id . "'");
    $categories_products = tep_db_fetch_array($categories_products_query);
    if ($categories_products['total'] > 0) {
      $category_depth = 'products'; // display products 
    }
    else {
      $category_parent_query = tep_db_query("select count(*) as total from " . TABLE_CATEGORIES . " where parent_id = '" . (int)$current_category_id . "' and categories_status='1'");
      $category_parent = tep_db_fetch_array($category_parent_query);
      if ($category_parent['total'] > 0) {
        $category_depth = 'nested'; // navigate through the categories
      }
      else {
        $category_depth = 'products'; // category has no products, but display the 'no products' message
      }
    }
    //My modification - nest - 1st level
    $current_cpath = tep_parse_path($current_category_id);

    //My modification - redirect to 404 error page if it is a wrong cPath ID
    if (!tep_not_null($current_cpath)) {
      tep_redirect(tep_href_link(FILENAME_PAGE_NOT_FOUND));
    }

    $cPath_array = tep_parse_category_path($current_cpath);

    if ($category_depth == 'nested') {
      if (sizeof($cPath_array)==1) {
        $categories_level = "1st";
      }
      elseif (sizeof($cPath_array)>1) {
        $categories_level = "products";
      }
    }
    else {
      $categories_level = "products";
    }
    //My modification - nest - 1st level
  }

  //Temp - My modification - Sale Best Buy, Offers
  $arrayc_sale = array(247,284,285);
  $productlist = false;
  if (in_array($current_category_id, $arrayc_sale)) {
    $category_depth = 'products';
    $categories_level = "products";
    $productlist = true;
  }
  elseif (isset($HTTP_GET_VARS['productlist'])) {
    $category_depth = 'products';
    $categories_level = "products";
    $productlist = true;
  }
  elseif (isset($HTTP_GET_VARS['manufacturers_id']) && !empty($HTTP_GET_VARS['manufacturers_id'])) {
    $category_depth = 'products';
    $categories_level = "products";
    $productlist = true;
  }
  elseif ($category_depth == 'products' || $categories_level == "products") {
    $category_depth = 'products';
    $categories_level = "products";
    $productlist = true;
  }

  //$cat_with_a_pv_refine = array(92,52,443); //extensions/hairpieces/wigs
  //$productlist = false;
  //if (sizeof($cPath_array) >1 || isset($HTTP_GET_VARS['productlist'])) $productlist = true;

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_DEFAULT);
  $_canonicalUrl = canonicalUrl();
?>
















<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    <title><?php echo INDEXTITLE; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow" />
    <meta name="description" id="description" content="<?php echo METADESCRIPTION; ?>" />
    <meta name="keywords" id="keywords" content="<?php echo METAKEYWORD; ?>" />

    <meta name="audience" content="all" />
    <meta name="distribution" content="global" />
    <meta name="geo.region" content="en" />
    <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
    <meta http-equiv="Content-Language" content="EN-GB" />
    <meta name="rights-standard" content="<?php echo STORE_NAME;?>" />

    <!--<base href="<?php echo (($request_type == 'SSL') ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG; ?>" />-->

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
<!--
    <script src="js/jquery.elevateZoom-3.0.8.min.js" defer></script>
    <script src="js/jquery.barrating.js" defer></script>
-->
    <script src="js/featherlight.min.js" defer></script>
    <script src="js/owl.carousel.min.js" defer></script>

    <script src="js/featherlight.min.js" defer></script>
    <script src="js/jquery.mmenu.min.js" defer></script>
    <script src="js/2017.js" defer></script>

    <meta property="og:title" content="<?php echo INDEXTITLE; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?php echo $_canonicalUrl;?>" />
    <meta property="og:image" content="" />
    <meta property="og:site_name" content="<?php echo STORE_NAME_UK;?>" />
    <meta property="fb:admins" content="1222057982" />
    <meta property="og:description" content="<?php echo METADESCRIPTION; ?>" />

    <link href="" rel="publisher" />
    <link href="" rel="author" />

    <link rel="stylesheet" href="css/jquery.mmenu.css" />
    <link rel="stylesheet" href="css/jquery.mmenu.positioning.css" />
    <link rel="stylesheet" href="css/jquery.mmenu.pagedim.css" />

    <link rel="stylesheet" href="css/owl.carousel.css" />
    <link rel="stylesheet" href="css/owl.theme.css" />
    <link rel="stylesheet" href="css/featherlight.min.css" />

    <link rel="canonical" href="<?php echo $_canonicalUrl; ?>" />



      /*<![CDATA[*/
      $(document).ready(function(){


      });
      /*]]>*/
    </script>


  </head>









































  <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->




















      <div id="container">
        <div class="inner">







              <?php
                if ($category_depth == 'nested' && $categories_level != "products") {
              ?>
				          <div class="grid-02">
				          	<div class="grid-02-01">
					          <?php require(DIR_WS_INCLUDES . 'column_left.php');?>
					          </div><!--/.grid-02-01-->
                    <div class="grid-02-02">
                    	<?php include(DIR_WS_BOXES . 'lv1.php');?>
                    </div><!--/.grid-02-02-->

                    <div class="clearme"></div>
              	  </div><!--/.grid-02-->
              <?php
                  include(DIR_WS_BOXES . 'lv1-cathead.php');
               	  include(DIR_WS_BOXES . 'promo-squares-hairextensions.php');
               	  include(DIR_WS_BOXES . FILENAME_RECENTLY_VIEWED);

                }
                elseif ($category_depth == 'products' || (isset($HTTP_GET_VARS['manufacturers_id']) && !empty($HTTP_GET_VARS['manufacturers_id'])) || isset($HTTP_GET_VARS['mcid']) || $categories_level == "products") {
                	/*
                	if (isset($HTTP_GET_VARS['manufacturers_id']) && !empty($HTTP_GET_VARS['manufacturers_id'])) {
                		$manufacturers_info = tep_db_fetch_array(tep_db_query("select manufacturers_image, manufacturers_name from " . TABLE_MANUFACTURERS . " where manufacturers_id = '" . (int)$HTTP_GET_VARS['manufacturers_id'] . "'"));
                		if (tep_not_null($manufacturers_info['manufacturers_image'])) {
                			echo '<div class="cat-hero brand-hero">' . tep_image(DIR_WS_IMAGES . $manufacturers_info['manufacturers_image'], $manufacturers_info['manufacturers_name']) . '</div>' . "\n";
                		}
                	}
                	*/
              ?>

                  <div class="grid-02">
				          	<div class="grid-02-01">
					            <?php require(DIR_WS_INCLUDES . 'column_left.php');?>
					          </div><!--/.grid-02-01-->
                    <div class="grid-02-02">
                    	<?php
                    	  include(DIR_WS_BOXES . 'lv2.php');
                    	?>
                    </div><!--/.grid-02-02-->
                  	<div class="clearme"></div>
                  </div><!--/.grid-02-->

              <?php
                  include(DIR_WS_BOXES . 'lv1-cathead.php');
               	  include(DIR_WS_BOXES . 'promo-squares-hairextensions.php');
               	  include(DIR_WS_BOXES . FILENAME_RECENTLY_VIEWED);
               	  include(DIR_WS_BOXES . 'mobile_refine.php'); //mobile mmenu
               	  include(DIR_WS_BOXES . 'lv2.js.php');
                }
                else {
                      echo '<div id="hero-container">' . "\n";
                      $banner_main = tep_db_fetch_array(tep_db_query("select * from " . TABLE_BANNERS . " where banners_categories_id=0 and banners_group='1200x380' and status=1 order by sort_order"));
                      $banner0 = tep_banner_exists('static', $banner_main['banners_id']);
                      if (sizeof($banner0)) {
                        echo tep_display_banner('static', $banner0) . "\n";
                      }
                      echo '</div><!--/#hero-container-->' . "\n";

                    include(DIR_WS_BOXES . 'home-m.php');
                    include(DIR_WS_BOXES . 'home-bestseller.php');
                    include(DIR_WS_BOXES . 'promo-squares-global.php');
                    include(DIR_WS_BOXES . 'home-m2.php');
                    if (file_exists(DIR_FS_CACHE . "home_blog.cache.php")) {
                      include(DIR_FS_CACHE . "home_blog.cache.php");
                    }
                }
              ?>








        </div><!--/.inner-->
      </div><!--/#container-->




















      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>