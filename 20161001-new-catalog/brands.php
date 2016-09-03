<?php

  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_BRANDS_LIST);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_BRANDS_LIST));
?>





<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_BRANDS_LIST; ?></title>
      <meta name="keywords" id="keywords" content="brand, a to z brands" />
      <meta name="description" id="description" content="brands list" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

     	<meta name="robots" content="index, follow" />
      <meta name="audience" content="all">
      <meta name="distribution" content="global">
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
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



    </head>

<!-- header //-->


<!-- header_eof //-->

<!-- body //-->
    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">



          <div class="inner">

            <div class="grid-full">
              <div class="cat-head">
                <h1><?php echo HEADING_TITLE;?></h1>
              </div><!--/.cat-head-->
              <?php
                $manufacturers_4all_q = tep_db_query("select manufacturers_id, manufacturers_name from " . TABLE_MANUFACTURERS . " order by manufacturers_name");
                $manufacturers_4all_a = array();
                while ($manufacturers = tep_db_fetch_array($manufacturers_4all_q)) {
                  $manufacturers_4all_a[strtoupper($manufacturers['manufacturers_name'][0])][] = array($manufacturers['manufacturers_id'] => $manufacturers['manufacturers_name']);

                }

                $hot_manufacturers = hotbrands();
                $count=0;
                foreach (range('A', 'Z') as $letter) {
                	if (isset($manufacturers_4all_a[$letter]) && sizeof($manufacturers_4all_a[$letter])) {
                		echo '<div class="brands-az-section' . (($count==0) ? " first" : "") . '">' . "\n";
                		echo '  <h2>' . $letter . '</h2>' . "\n";
                		echo '  <ul>' . "\n";
                	  foreach ($manufacturers_4all_a[$letter] as $v) {
                	  	if (in_array(key($v), $hot_manufacturers))
                	  	  echo '    <li><a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . key($v)) . '"><strong>' . $v[key($v)] . '</strong></a> <span class="menu-hot">HOT</span></li>' . "\n";
                	  	else
                        echo '    <li><a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . key($v)) . '">' . $v[key($v)] . '</a></li>' . "\n";
                	  }
                	  echo '  </ul>' . "\n";
                	  echo '</div><!--/.brands-az-section-->' . "\n";
                	  $count++;
                	}
                }
              ?>

            </div><!--/.grid-full-->
            <p></p>
				    <?php
              include(DIR_WS_BOXES . FILENAME_RECENTLY_VIEWED);
            ?>

          </div><!--/.inner-->



        </div><!--/#container-->

















      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>