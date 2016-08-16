<?php
/*
  $Id: account.php,v 1.61 2003/06/09 23:03:52 hpdl Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2003 osCommerce

  Released under the GNU General Public License
*/

  require('includes/application_top.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    ////Original code:  tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
    //begin pwa
    tep_redirect(tep_href_link(FILENAME_LOGIN, 'my_account_f=' . $HTTP_GET_VARS['my_account_f'], 'SSL'));
    //end pwa
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
?>





































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<title><?php echo TITLE_HTML; ?></title>

      <meta name="robots" content="noindex, nofollow">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">


      <meta name="audience" content="all">
      <meta name="distribution" content="global">
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
      <meta http-equiv="Content-Language" content="EN-GB">
      <meta name="rights-standard" content="<?php echo STORE_NAME;?>" />


      <!--CSS-->
      <link rel="stylesheet" href="/css/normalize.css" />
      <link rel="stylesheet" href="styles.css" />

      <link rel="shortcut icon" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'fav_icon.ico';?>" />
      <link rel="apple-touch-icon-precomposed" href="//<?php echo ABS_STORE_SITE . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'ios_icon.png';?>" />

      <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" />
      <link rel="stylesheet" href="/css/responsive.css" />

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


      <!--<script src="ext/jquery/jquery-ui-1.9.2.custom.js"></script>-->
      <link rel="stylesheet" type="text/css" href="ext/jquery/ui/redmond/jquery-ui-1.11.4.css">
      <script src="ext/jquery/ui/jquery-ui-1.11.4.min.js"></script>
      <script src="ext/jquery/jquery.dialogOptions.js"></script>

      <script type="text/javascript" src="includes/z-dhtml/colour_match/ajaxfileupload.js"></script>
      <script type="text/javascript">
      /*<![CDATA[*/
      function ajaxFileUpload()
      {
        $("#color_uploaded").ajaxStart(function(){
          $(this).html("");
          $(this).hide();
        })
    
        $("#loading").
        ajaxStart(function(){
          //$(this).show();
          $(this).html("<img src=\"includes/z-dhtml/colour_match/loading.gif\" />");
          $(this).show();
        })
        .ajaxComplete(function(){
          $(this).html("");
          $(this).hide();
        });
        
        var customer_id = "<?php echo $customer_id; ?>";
        var comment = $( "#colorform" ).find('textarea[name="comment"]').val();
        <?php echo 'if (comment == \'' . COLOUR_MATCH_COMMENT . '\') comment = \'\';'; ?>
        $.ajaxFileUpload (
          {
            url:'includes/z-dhtml/colour_match/doajaxfileupload.php',
            secureuri:false,
            fileElementId:'colourfile',
            dataType: 'json',
            data:{name:'logan', id: customer_id, comment: comment },
            success: function (data, status)
            {
              if(typeof(data.error) != 'undefined')
              {
                if(data.error != '')
                {
                  //alert(data.error);
                  $("#color_uploaded").html(data.error);
                  $("#color_uploaded").css("display","block");
                  $("#color_uploaded").addClass( "ui-state-highlight" );
                  setTimeout(function() {
                    $("#color_uploaded").removeClass( "ui-state-highlight", 1500 ). fadeOut();
                   }, 1500 );
                } else {
                  //alert(data.msg);
                  $("#color_uploaded").html(data.msg);
                  $("#color_uploaded").css("display","block");
                  $("#color_uploaded").addClass( "ui-state-highlight" );
                  setTimeout(function() {
                    $("#color_uploaded").removeClass( "ui-state-highlight", 1500 ). fadeOut();
                   }, 1500 );
                }
              }
            },
            error: function (data, status, e)
            {
              //alert(e);
              $("#color_uploaded").html(e);
              $("#color_uploaded").css("display","block");
            }
          }
        )
        
        return false;
    
      }
      /*]]>*/
      </script>


    </head>














































    <body itemscope itemtype="http://schema.org/WebPage">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->

















    	  <div id="container">





          <div class="inner">
            <h1><?php echo HEADING_TITLE; ?></h1>
                <?php
                if ($messageStack->size('account') > 0) {
                  echo '                  ' . $messageStack->output('account') . "\n";
                }
                ?>

            <div class="grid-01">

              <div class="grid-01-01">
                <div class="table-wrapper">
                    	<h2 class="sub-title"><?php echo MY_ACCOUNT_TITLE;?></h4>
                      <ul>
                        <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL') . '">' . MY_ACCOUNT_INFORMATION . '</a>'; ?></li>
                        <li><?php echo '<a href="' . tep_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL') . '">' . MY_ACCOUNT_ADDRESS_BOOK . '</a>'; ?></li>
                        <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_PASSWORD, '', 'SSL') . '">' . MY_ACCOUNT_PASSWORD . '</a>'; ?></li>
                      </ul>
                      <br />

                      <h2 class="sub-title"><?php echo MY_ORDERS_TITLE;?></h2>
                      <ul>
                        <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL') . '">' . MY_ORDERS_VIEW . '</a>'; ?></li>
                      </ul>
                      <br />

<!--
                      <h2 class="sub-title"><?php echo EMAIL_NOTIFICATIONS_TITLE;?></h2>
                      <ul>
                        <li><?php echo '<u><a href="' . tep_href_link(FILENAME_ACCOUNT_NEWSLETTERS, '', 'SSL') . '">' . EMAIL_NOTIFICATIONS_NEWSLETTERS . '</a></u>'; ?></li>
                      </ul>
                      <br />
-->
                      <h2 class="sub-title"><?php echo GROUP_STATUS;?></h2>
                      <p><?php echo display_group_message(); ?></p>
                      <?php
                        //if (tep_session_is_registered('customer_id')) {
                          if (($gv_amount=get_gv_amount($customer_id)) > 0 ) {
                            echo '                      <br />' . "\n";
                            echo '                      <h2 class="sub-title">' . VOUCHER_BALANCE . '</h2>' . "\n";
                            echo '                      <p>' . VOUCHER_BALANCE . ':&nbsp;' . $currencies->format($gv_amount) . '</p>' . "\n";
                          }
                        //}
                      ?>
                </div>

              </div><!--/.grid-01-01-->

              <div class="grid-01-02">
                <div class="table-wrapper">

						          <h2 class="sub-title"><?php echo COLOUR_MATCH_TITLE;?></h2>
                      <p><?php echo COLOUR_MATCH_TITLE_DESC; ?></p>
                      <script type="text/javascript">
                      $(function() {
                          $("#buttonUpload").button().click(function( event ) {
                                  return ajaxFileUpload();
                          });
                      });
                      </script>
                      <div id="colour_match_form">
                      	<form id="colorform" action="" method="POST" enctype="multipart/form-data">
                          <p>
                            <strong><?php echo COLOUR_MATCH_UPLOAD;?></strong><br /><?php echo COLOUR_FILE_FORMAT; ?>
                            <div><input id="colourfile" type="file" size="45" name="colourfile" class="input"></div>
                            <br />
                            <?php echo tep_draw_textarea_field('comment', 'soft', 1, 3, COLOUR_MATCH_COMMENT, 'onblur="if(this.value==\'\') this.value=\''. COLOUR_MATCH_COMMENT. '\';" onfocus="if(this.value==\'' . COLOUR_MATCH_COMMENT . '\') this.value=\'\';"'); ?>
                            <br />
                            <button id="buttonUpload"><span class="smallText">Upload</span></button>
                            <div class="left" id="color_uploaded"></div>
                            <div class="left" id="loading"></div>
                          </p>
                        </form>
                      </div>
                      <div class="clearme"></div>

                      <?php echo COLOUR_MATCH_FACTORS; ?>

                </div>
              </div><!--/.grid-01-02-->
              <div class="clearme"></div>

            </div>

          </div><!--/.inner-->





        </div><!--/#container-->






































      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->
    </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>