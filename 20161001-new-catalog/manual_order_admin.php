<?php
  require('includes/application_top.php');
  require('includes/password_protection/adminpwd.php');

  if (!is_goodip())
  { exit; }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADMIN);
  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_ADMIN, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title>Manual Order Admin</title>
      <meta name="description" id="description" content="" />
      <meta name="keywords" id="keywords" content="" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0">

     	<meta name="robots" content="noindex,nofollow">
      <meta name="audience" content="all">



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














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">



          <div class="inner">
          	<div class="grid-02">
				      <div class="grid-02-01">
					      <?php require(DIR_WS_INCLUDES . 'column_left.php');?>
					    </div><!--/.grid-02-01-->

              <div class="grid-02-02">
                <?php
              		  if (!isset($HTTP_GET_VARS['search_email'])) {
              		  ?>
                    <div class="table-wrapper readable-text">
                        <h2 class="sub-title">Search Results</h2>
                        <p>Search for existing customers to place their order.</p>
                    </div><!--/.table-wrapper readable-text-->
              		  <?php
              		  }
						        if ($HTTP_GET_VARS['search_email']) {
						          $search_email = tep_db_prepare_input($HTTP_GET_VARS['search_email']);
						          $where_clause = "c.customers_email_address RLIKE '".tep_db_input($search_email)."'";
						        }
						  
						        if ($HTTP_GET_VARS['search_phone']) {
						          $search_phone = tep_db_prepare_input($HTTP_GET_VARS['search_phone']);
						          $where_clause .= ($where_clause ? ' or ' : '')."c.customers_telephone RLIKE '".tep_db_input($search_phone)."'";
						        }
						  
						        if ($HTTP_GET_VARS['search_lastname']) {
						          $search_lastname = tep_db_prepare_input($HTTP_GET_VARS['search_lastname']);
						          $where_clause .= ($where_clause ? ' or ' : '')." c.customers_lastname RLIKE '".tep_db_input($search_lastname)."'";
						        }
						  
						        if ($HTTP_GET_VARS['search_postcode']) {
						          $search_postcode = tep_db_prepare_input($HTTP_GET_VARS['search_postcode']);
						          $where_clause .= ($where_clause ? ' or ' : '')." o.delivery_postcode RLIKE '".tep_db_input($search_postcode)."'";
						        }
						  
						        $miss = manual_order_status1($HTTP_GET_VARS);
						  
						        //Cash & Carry
						        if (isset($HTTP_GET_VARS['cash_carry'])) {
						          $cash_carry = "&cash_carry=1";
						        }
						        else {
						          $cash_carry ="";
						        }
                    if ($where_clause) {
                      $info_box_contents = array();
                      //$search_sql = "select * from ".TABLE_CUSTOMERS." where ".$where_clause;
                      $search_query = tep_db_query("select c.customers_id, c.customers_telephone, c.customers_firstname, c.customers_lastname, c.customers_groups_id, c.customers_discount, c.customers_email_address, o.delivery_postcode, cg.customers_groups_id, cg.customers_groups_discount from " . TABLE_CUSTOMERS . " c left join " . TABLE_ORDERS . " o on c.customers_id=o.customers_id, " . TABLE_CUSTOMERS_GROUPS . " cg where c.customers_groups_id=cg.customers_groups_id and " . $where_clause);
                      if (tep_db_num_rows($search_query)) {
                        $info_box_contents[] = array('align' => 'center', 'text'  => TEXT_ADMIN_MATCHES);
                        $search_display = '<table border="0" width="100%" cellspacing="0" cellpadding="2" class="tinfo">';
                        $search_display .= '<tr><td class="tinfo">'.TEXT_ADMIN_SEARCH_EMAIL.'</td><td class="tinfo">'.TEXT_ADMIN_SEARCH_NAME.'</td><td class="tinfo">'.TEXT_ADMIN_SEARCH_PHONE.'</td><td class="tinfo">'.TEXT_ADMIN_SEARCH_DISCOUNT.'</td><td class="tinfo">'.TEXT_ADMIN_SEARCH_ID.'</td></tr>';
                        $list_email_duplicate_array = array();
                        echo '                	  <div class="table-wrapper readable-text">' . "\n";
                        echo '                	    <h2 class="sub-title">Search Results</h2>' . "\n";
                        echo '                	    <p>' . TEXT_ADMIN_MATCHES . '</p>' . "\n";
                        echo '                	    <table width="100%" id="table-zebra-stripe">' . "\n";
                        echo '                	      <thead>' . "\n";
                        echo '                	        <tr>' . "\n";
                        echo '                	          <th><strong>Email</strong></th>' . "\n";
                        echo '                	          <th><strong>Name</strong></th>' . "\n";
                        echo '                	          <th><strong>Telephone</strong></th>' . "\n";
                        echo '                	          <th><strong>Discount(%)</strong></th>' . "\n";
                        echo '                	          <th><strong>ID</strong></th>' . "\n";
                        echo '                	        </tr>' . "\n";
                        echo '                	      </thead>' . "\n";
                        echo '                	      <tbody>' . "\n";

                        $count = 0;
                        while ($search_result = tep_db_fetch_array($search_query)) {
                          if (in_array($search_result['customers_email_address'], $list_email_duplicate_array)) continue;
                          if ($search_result['customers_discount'] !=0 ) $cdiscount = $search_result['customers_discount'];
                          elseif ($search_result['customers_groups_id'] >1 ) $cdiscount = $search_result['customers_groups_discount'];

                          $list_email_duplicate_array[] = $search_result['customers_email_address'];

                          if ($count % 2 == 0 ) {
                            echo '                	        <tr class="zebra-even">' . "\n";
                          }
                          else {
                            echo '                	        <tr class="zebra-odd">' . "\n";
                          }
                          echo '                	          <td><a href="'.tep_href_link(FILENAME_ADMIN_LOGIN,'email_address='.$search_result['customers_email_address'].$miss.$cash_carry,'SSL').'">' . $search_result['customers_email_address'] . '</a></td>' . "\n";
                          echo '                	          <td>' . $search_result['customers_firstname'] . ' ' . $search_result['customers_lastname'] . '</td>' . "\n";
                          echo '                	          <td>' . $search_result['customers_telephone'] . '</td>' . "\n";
                          echo '                	          <td>' . $cdiscount . '</td>' . "\n";
                          echo '                	          <td>' . $search_result['customers_id'] . '</td>' . "\n";
                          echo '                	        </tr><!--/.zebra-odd-->' . "\n";
                          $count++;
                        }
                        echo '                	      <tbody>' . "\n";
                        echo '                	    </table><!--/#table-zebra-stripe-->' . "\n";
                        echo '                	  </div><!--/.table-wrapper readable-text-->' . "\n";
                        $search_display .= '</table>';
                        $info_box_contents[] = array('align' => 'left', 'text'  => $search_display);
                      } else {
                        //$info_box_contents[] = array('align' => 'center', 'text'  => TEXT_ADMIN_NO_MATCHES);
                        echo '                	  <div class="table-wrapper readable-text">' . "\n";
                        echo '                	    <h2 class="sub-title">Search Results</h2>' . "\n";
                        echo '                	    <p>'. TEXT_ADMIN_NO_MATCHES . '</p>' . "\n";
                        echo '                	  </div><!--/.table-wrapper readable-text-->' . "\n";
                      }
                    }
						        ?>
              </div><!--/.grid-02-02-->

              <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
              <div class="clearme"></div>

          	</div><!--/.grid-02-->
          </div><!--/.inner-->



        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>