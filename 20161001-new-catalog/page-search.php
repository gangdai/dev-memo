<?php
  require('includes/application_top.php');
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

    <link rel="stylesheet" href="css/jquery.mmenu.css" />
    <link rel="stylesheet" href="css/jquery.mmenu.positioning.css" />
    <link rel="stylesheet" href="css/jquery.mmenu.pagedim.css" />
    <!--  
    <link rel="stylesheet" href="css/owl.carousel.css" />
    <link rel="stylesheet" href="css/owl.theme.css" />
    <link rel="stylesheet" href="css/featherlight.min.css" />
    -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/responsive.css" />

    <!--JS-->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="//code.jquery.com/jquery-migrate-1.4.0.min.js"></script>
    <!--
    <script src="js/jquery.elevateZoom-3.0.8.min.js" defer></script>
    <script src="js/featherlight.min.js" defer></script>
    <script src="js/jquery.barrating.js" defer></script>
    <script src="js/owl.carousel.min.js" defer></script>
    -->
    <script src="js/jquery.mmenu.min.js" defer></script>
    <script src="js/2017.js" defer></script>


    <meta property="og:title" content="<?php echo INDEXTITLE; ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?php echo $_canonicalUrl;?>" />
    <meta property="og:image" content="f" />
    <meta property="og:site_name" content="<?php echo STORE_NAME_UK;?>" />
    <meta property="fb:admins" content="" />
    <meta property="og:description" content="<?php echo METADESCRIPTION; ?>" />



    <link rel="canonical" href="<?php echo $_canonicalUrl; ?>" />

    <!--<script type="text/javascript" src="jquery.lazyload.js" defer></script>-->
    <!--
      <link rel="stylesheet" type="text/css" href="ext/jquery/ui/redmond/jquery-ui-1.11.4.css">
      <script src="ext/jquery/ui/jquery-ui-1.11.4.min.js"></script>
      <script src="ext/jquery/jquery.dialogOptions.js"></script>
      -->
    <script>
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


          <div class="grid-full">
            <h1 id="product-title">Search</h1>
            <form id="full-page-search" action="page-search-results.php">
              <input type="text" class="full-page-text" placeholder="What are you looking for?" autofocus /><br />
              <input type="submit" class="form-bt"  value="Search" />
            </form><!--/#full-page-search-->
            <div class="clearme"></div>
          </div><!--/.grid-01-->


        </div><!--/.inner-->
      </div><!--/#container-->








      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>