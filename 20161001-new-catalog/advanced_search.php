<?php


  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADVANCED_SEARCH);

    if ( (isset($HTTP_GET_VARS['pfrom']) && !is_numeric($HTTP_GET_VARS['pfrom'])) ||
         (isset($HTTP_GET_VARS['pto']) && !is_numeric($HTTP_GET_VARS['pto'])) ||
         (isset($HTTP_GET_VARS['manufacturers_id']) && !is_numeric($HTTP_GET_VARS['manufacturers_id']))
       ) {
       	$error = true;
       	tep_redirect(tep_href_link(FILENAME_ADVANCED_SEARCH, '', 'NONSSL', true, false));
    }

  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ADVANCED_SEARCH));
?>

<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo HTML_TITLE_SEARCH; ?></title>
      <meta name="keywords" id="keywords" content="search,keywords" />
      <meta name="description" id="description" content="search for products by keywords" />
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
      <script language="javascript" src="includes/general.js"></script>
      <script language="javascript"><!--
      function check_form() {
        var error_message = "<?php echo JS_ERROR; ?>";
        var error_found = false;
        var error_field;
        var keywords = document.advanced_search.keywords.value;
        /*var dfrom = document.advanced_search.dfrom.value;
        var dto = document.advanced_search.dto.value;
        var pfrom = document.advanced_search.pfrom.value;
        var pto = document.advanced_search.pto.value;*/
        var pfrom_float;
        var pto_float;
        if ( (keywords == '') || (keywords.length < 1) ) {
          error_message = error_message + "* <?php echo ERROR_INVALID_KEYWORDS_EMPTY; ?>\n";
          error_field = document.advanced_search.keywords;
          error_found = true;
        }
 
        if (error_found == true) {
          alert(error_message);
          error_field.focus();
          return false;
        } else {
          RemoveFormatString(document.advanced_search.dfrom, "<?php echo DOB_FORMAT_STRING; ?>");
          RemoveFormatString(document.advanced_search.dto, "<?php echo DOB_FORMAT_STRING; ?>");
          return true;
        }
      }

      //--></script>
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
					    <h1 class="centerme">Search</h1>
					    <?php
					      echo tep_draw_form('advanced_search', tep_href_link(FILENAME_ADVANCED_SEARCH_RESULT, '', 'NONSSL', false), 'get', 'onSubmit="return check_form(this);" id="full-page-search"') . tep_hide_session_id();
					      echo tep_draw_input_field('keywords', '', 'class="full-page-text" placeholder="What are you looking for?" autofocus') . "\n";
					      echo '<br /><input type="submit" class="form-bt" value="Search" />' . "\n";
					      echo '</form>' . "\n";
					    ?>
					    <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
					    <div class="clearme"></div>
				    </div><!--/.grid-full-->

          </div><!--/.inner-->



        </div><!--/#container-->

















      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>