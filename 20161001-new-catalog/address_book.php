<?php

  require('includes/application_top.php');

  //require(DIR_WS_FUNCTIONS . 'ajax.php');

  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot();
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADDRESS_BOOK);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ADDRESS_BOOK_PROCESS);
  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_ACCOUNT);


  $breadcrumb->add(NAVBAR_TITLE_1, tep_href_link(FILENAME_ACCOUNT, '', 'SSL'));
  $breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<!--<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE9" />-->
    	<title><?php echo TITLE_HTML; ?></title>
      <meta name="description" id="description" content="" />
      <meta name="keywords" id="keywords" content="" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />

      <meta name="audience" content="all" />
      <meta name="distribution" content="global" />
      <meta name="geo.region" content="en" />
      <meta name="copyright" content="<?php echo STORE_NAME_UK;?>" />
      <meta http-equiv="Content-Language" content="EN-GB" />
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


      <!-- //craftyclicks -->
      <script type="text/javascript">
        var formname = 'address_update';
      </script>
      <script type="text/javascript" src="postcode/crafty_mymod.js" defer></script>
      <?php
          require('postcode/crafty_html_output.php');
          echo tep_crafty_script_add('address_update');
      ?>
      <!-- //craftyclicks -->

      <!--<script src="ext/jquery/jquery-ui-1.9.2.custom.js"></script>-->
      <link rel="stylesheet" type="text/css" href="ext/jquery/ui/redmond/jquery-ui-1.11.4.css">
      <script src="ext/jquery/ui/jquery-ui-1.11.4.min.js"></script>
      <script src="ext/jquery/jquery.dialogOptions.js"></script>


		  <script>
        /*<![CDATA[*/
		  	var osCsid = "<?php echo tep_session_id();?>";
        document.write("<style type='text/css'>button { display: none; }</style>");
        $(document).ready(function(){

          $(function() {
            // a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
            //$( "#dialog:ui-dialog" ).dialog( "destroy" );
            tips = $( "#dialog-address-update" ).find( ".validateTips" );
            //var postcode_max_length = 10;

            function updateTips( t ) {
              tips.text( t ).addClass( "ui-state-highlight" );
              setTimeout(function() {
                tips.removeClass( "ui-state-highlight", 3000 );
              }, 3000 );
            }
        
            function checkLength( o, n, min, max ) {
              //if ( o.val().length > max || o.val().length < min ) {
              if (o.attr("type") == "hidden") {
                return true;
              }
              else if (typeof(o) == 'undefined' || o == null) {
            	  return true;
              }

              if ( o.val().length < min ) {
                o.addClass( "ui-state-error" );
                if (max >0) {
                  updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
                }
                else {
                  updateTips( n );
                }
                return false;
              }
              else if (o.val().length > max && max >0) {
                o.addClass( "ui-state-error" );
                updateTips( "Length of " + n + " must be between " + min + " and " + max + "." );
                return false;
              }
              else {
                return true;
              }
            }
        
            function checkRegexp( o, regexp, n ) {
              if ( !( regexp.test( o.val() ) ) ) {
                o.addClass( "ui-state-error" );
                updateTips( n );
                return false;
              } else {
                return true;
              }
            }
        
            function check_postcode(field_name, message) {
              if (field_name.attr("type") != "hidden") {
                var objRegExp =/[^A-Za-z0-9\s]/; //Only letter and number and spaces
                if (objRegExp.test(field_name.val())) {
                  updateTips( message );
                  return false;
                }
              }
              return true;
            }
        
            function check_select(field_name, field_default, message) {
              if (field_name.attr("type") != "hidden") {
                if (field_name.val() == field_default) {
                  updateTips( message );
                  return false;
                }
              }
              return true;
            }
            
            $( "#dialog-address-update" ).dialog({
              autoOpen: false,
              height: 650,
              /*width: 600,*/
              width: "auto",
              create: function( event, ui ) {
                // Set maxWidth
                $(this).css("maxWidth", "600px");
              },

              modal: true,
              open: function () {
                      //alert($(this).parents(".ui-dialog:first").find(".ui-dialog-titlebar").css()+"qqqqq");
                      //$(this).parents(".ui-dialog:first").find(".ui-dialog-titlebar").addClass("ui-state-error");
                      //$(this).parents(".ui-dialog:first").find(".ui-widget-header").removeClass("ui-widget-header");//.addClass("ui-widget-header-custom");
                    },
              buttons: {
                "Update Address": function() {
        
                  var firstname = $( "#dialog-address-update" ).find( "#firstname" ),
                      lastname = $( "#dialog-address-update" ).find( "#lastname" ),
                      house_name = $( "#dialog-address-update" ).find( "#house_name" ),
                      street_address = $( "#dialog-address-update" ).find( "#street_address" ),
                      postcode = $( "#dialog-address-update" ).find( "#postcode" ),
                      city = $( "#dialog-address-update" ).find( "#city" ),
                      country = $( "#dialog-address-update" ).find( "#country" ),
                      formid = $("#dialog-address-update").find('#address_update input[name=formid]');
                      
                      //tips = $( "#dialog-address-update" ).find( ".validateTips" );
                  if ($('input[name=gender]:radio', '#address_update').length > 0) var gender = $('input[name=gender]:checked', '#address_update');
                  if ($("#dialog-address-update").find('fieldset').find('#company').length > 0) var company = $( "#dialog-address-update" ).find( "#company" );
                  if ($("#dialog-address-update").find('fieldset').find('#suburb').length > 0) var suburb = $( "#dialog-address-update" ).find( "#suburb" );
                  if ($("#dialog-address-update").find('fieldset').find('#state').length > 0) var state = $("#dialog-address-update").find('fieldset').find('#state');
                  if ($("#dialog-address-update").find('fieldset').find('#zone_id').length > 0) var zone_id = $("#dialog-address-update").find('fieldset').find('#zone_id');
                  if ($('input[name=primary]:checkbox', '#address_update').is(':checked')) var primary = $('input[name=primary]:checkbox', '#address_update');
                  var allFields = $( [] ).add( firstname ).add( lastname ).add( house_name ).add( street_address ).add( postcode ).add( suburb ).add( city ).add( state );

                  var bValid = true;
                  allFields.removeClass( "ui-state-error" );
        
                  bValid = bValid && checkLength( firstname, "<?php echo ENTRY_FIRST_NAME_ERROR; ?>", <?php echo ENTRY_FIRST_NAME_MIN_LENGTH; ?>, 0);
                  bValid = bValid && checkLength( lastname, "<?php echo ENTRY_LAST_NAME_ERROR; ?>", <?php echo ENTRY_LAST_NAME_MIN_LENGTH; ?>, 0 );
                  bValid = bValid && checkLength( house_name, "<?php echo ENTRY_HOUSE_NAME; ?>", <?php echo ENTRY_HOUSE_NAME_MIN_LENGTH; ?>, <?php echo ENTRY_HOUSE_NAME_MAX_LENGTH;?> );
                  bValid = bValid && checkLength( street_address, "<?php echo ENTRY_STREET_ADDRESS; ?>", <?php echo ENTRY_STREET_ADDRESS_MIN_LENGTH; ?>, <?php echo ENTRY_STREET_ADDRESS_MAX_LENGTH;?> );
                  bValid = bValid && checkLength( postcode, "<?php echo ENTRY_POST_CODE; ?>", <?php echo ENTRY_POSTCODE_MIN_LENGTH; ?>, <?php echo ENTRY_POSTCODE_MAX_LENGTH;?> );
                  bValid = bValid && check_postcode(postcode, "<?php echo ENTRY_POST_CODE_ERROR2; ?>");
                  if (typeof(suburb) != 'undefined' && suburb != null)
                    bValid = bValid && checkLength( suburb, "<?php echo ENTRY_SUBURB; ?>", 0, <?php echo ENTRY_SUBURB_MAX_LENGTH;?> );
                  bValid = bValid && checkLength( city, "<?php echo ENTRY_CITY; ?>", <?php echo ENTRY_CITY_MIN_LENGTH; ?>, <?php echo ENTRY_CITY_MAX_LENGTH;?> );
                  if (typeof(state) != 'undefined' && state != null)
                    bValid = bValid && checkLength( state, "<?php echo ENTRY_STATE; ?>", 0, <?php echo ENTRY_STATE_MAX_LENGTH;?> );
        
                  bValid = bValid && check_select(country, "", "<?php echo ENTRY_COUNTRY_ERROR; ?>");
                  bValid = bValid && (typeof(parseInt(country.val())) === 'number' && country.val() % 1 == 0);
                  //bValid = bValid && checkRegexp( name, emailregexp, "Incorrect email format, eg. johnsmith@mail.com" );
                  //bValid = bValid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );
        
                  if ( bValid ) {
                    //My modification ajax: save it to customer
                    var customer_id = "<?php echo $customer_id; ?>";
                    var update_address_book_id = $("#dialog-address-update").find('fieldset').find('#update_address_book_id').val();
                    if (update_address_book_id >0) {
                      //create new address
                      var address_action = "address_save";
                    }
                    else {
                      var address_action = "address_add";
                    }
        
                    var string_data= '{"action":"' + address_action + '",' + 
                                      '"address_book_id":"' + update_address_book_id + '",' +
                                      '"customer_id":"' + customer_id + '",' +
                                      '"firstname":"' + firstname.val() + '",' +
                                      '"lastname":"' + lastname.val() + '",' +
                                      '"house_name":"' + house_name.val() + '",' +
                                      '"street_address":"' + street_address.val() + '",' +
                                      '"postcode":"' + postcode.val() + '",' +
                                      '"city":"' + city.val() + '",' +
                                      '"country":"' + country.val() + '",' +
                                      ((typeof(gender) == 'undefined' || gender == null) ? '' : '"gender":"' + gender.val() + '",') +
                                      ((typeof(company) == 'undefined' || company == null) ? '' : '"company":"' + company.val() + '",') +
                                      ((typeof(suburb) == 'undefined' || suburb == null) ? '' : '"suburb":"' + suburb.val() + '",') +
                                      ((typeof(state) == 'undefined' || state == null) ? '' : '"state":"' + state.val() + '",') +
                                      ((typeof(zone_id) == 'undefined' || zone_id == null) ? '' : '"zone_id":"' + zone_id.val() + '",') +
                                      ((typeof(primary) == 'undefined' || primary == null) ? '' : '"primary":"' + primary.val() + '",') +
                                      '"formid":"' + formid.val() + '", "osCsid":"'+osCsid+'"}';

                    var JSONObject = eval ("(" + string_data + ")");
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                        $("#loading_"+update_address_book_id).html("<img src='simplecheckout/loading.gif' />");
                       },
                       data: JSONObject,
                       success: function(html) {
                         $('div#addresses_list').load('address_book.php #addresses_list', {customer_id: customer_id, action: "address_save_p", "osCsid":osCsid}, function() {
                           $('div#addresses_list button').button();
                           $('div#addresses_list button').trigger('refresh');
                           //show successful msg
                           if (update_address_book_id >0) {
                             $("#loading_"+update_address_book_id).show();
                             $("#loading_"+update_address_book_id).html(html.msg);
                             $("#loading_"+update_address_book_id).addClass( "ui-state-highlight" );
                             setTimeout(function() {  $("#loading_"+update_address_book_id).removeClass( "ui-state-highlight", 1500). fadeOut(); }, 1500 );
                           }
                           else {
                             $("#loading_"+update_address_book_id).show();
                             $("#loading_"+update_address_book_id).html(html.msg);
                             //$("#loading_"+update_address_book_id).addClass( "ui-state-highlight" );
                             //setTimeout(function() {  $("#loading_"+update_address_book_id).removeClass( "ui-state-highlight", 1500); }, 1500 );
                           }
                         });
                         
                         //show latest primary address on the according area if there is change
                         if ($('#customer_primary_address') . attr('defaultid') == update_address_book_id) {
                           $('div#customer_primary_address').load('address_book.php #customer_primary_address', {customer_id: customer_id, action: "address_save_p", "osCsid":osCsid}, function() {});
                           $('#customer_primary_address') . attr('defaultid', update_address_book_id);
                           $('div#customer_primary_addres').trigger('refresh');
                         }
                         else if (html.primary == 0){}
                         else {
                           $('#customer_primary_address') . attr('defaultid', html.primary);
                           $('#customer_primary_address') . html(html.primary_label);
                         }
                         //inject div#addresses_list with the output of send.php by POST request:
                         //$('div.classname').load('send.php', {param1: 'foo', param2: 'blah'});
                       }
                    });
        
                    $( this ).dialog( "close" );
                  }
                },
                Cancel: function() {
                  $( this ).dialog( "close" );
                }
              },
              close: function() {
                if (typeof allFields != 'undefined') {
                  allFields.val( "" ).removeClass( "ui-state-error" );
                }
              }
            });

              //$('button').button();
              //1 Way To Avoid the Flash of Unstyled Content
              $("button").removeClass("button").button();
              $("div#addresses_list").on('click', '.button_edit_address', function() {
                var firstname = $( "#dialog-address-update" ).find( "#firstname" ),
                lastname = $( "#dialog-address-update" ).find( "#lastname" ),
                house_name = $( "#dialog-address-update" ).find( "#house_name" ),
                street_address = $( "#dialog-address-update" ).find( "#street_address" ),
                postcode = $( "#dialog-address-update" ).find( "#postcode" ),
                city = $( "#dialog-address-update" ).find( "#city" ),
                country = $( "#dialog-address-update" ).find( "#country" ),
                formid = $("#dialog-address-update").find('#address_update input[name=formid]'),
                allFields = $( [] ).add( firstname ).add( lastname ).add( house_name ).add( street_address ).add( postcode ).add( city );
                allFields.removeClass( "ui-state-error" );
        
                /* pre populate dialog-form*/
                var button = $(this);
                var customer_id = "<?php echo $customer_id; ?>";
                var customer_default_id = $('#customer_primary_address') . attr('defaultid');
        
                var string_data= '{"action":"address_popup",' + '"address_book_id":"' +button.attr('address_book_id') + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
        
                //We can use this too: var JSONObject = eval ("(" + string_data + ")"); //The eval() function uses the JavaScript compiler which will parse the JSON text and produce a JavaScript object. The text must be wrapped in parenthesis to avoid a syntax error:
                var JSONObject = eval ("(" + string_data + ")");        //var JSONObject = jQuery.parseJSON(string_data);  //this is alternative
        
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                        $("#loading_"+button.attr('address_book_id')).html("<img src='simplecheckout/loading.gif' />");
                       },
                       dataType: "json",
                       data: JSONObject,
                       success: function(html) {
                         var str=html;
                         if ($('input[name=gender]:radio', '#address_update').length > 0) {
                           //if gender option is enable
                           //if ($('input[name=gender]:checked', '#address_update').length <1) {
                             //none of the option checked
                             $('input[name=gender]:radio', '#address_update') . filter("[value="+str.entry_gender+"]").attr("checked","checked");
                             //alert($('input[name=gender]:checked', '#address_update').val()+"eeeee");
                           //}
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#firstname').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#firstname').val(str.entry_firstname);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#lastname').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#lastname').val(str.entry_lastname);
                         }
        
                         if ($("#dialog-address-update").find('fieldset').find('#country').length > 0) {
                           if (str.entry_country_id ==0) str.entry_country_id=222;
                           $("#dialog-address-update").find('fieldset').find('#country').val(str.entry_country_id);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#postcode').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#postcode').val(str.entry_postcode);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#company').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#company').val(str.entry_company);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#house_name').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#house_name').val(str.entry_house_name);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#street_address').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#street_address').val(str.entry_street_address);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#suburb').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#suburb').val(str.entry_suburb);
                         }
                         if ($("#dialog-address-update").find('fieldset').find('#city').length > 0) {
                           $("#dialog-address-update").find('fieldset').find('#city').val(str.entry_city);
                         }
                         //if ($("#dialog-address-update").find('fieldset').find('#state').length > 0) {
                           //$("#dialog-address-update").find('fieldset').find('#state').val(str.entry_state);
                           //ajax_get_zones_html($entry['entry_country_id'],($entry['entry_zone_id']==0 ? $entry['entry_state'] : $entry['entry_zone_id']),false)
                           //$("#dialog-address-update").find('fieldset').find('#state') . val(str.entry_city);
                         //}
                         if ($("#dialog-address-update").find('fieldset').find('#state_input').length > 0) {
                           //state or zone_id
                           $("#state_input").html(str.state_entry);
                         }
                         //if ($("#dialog-address-update").find('fieldset').find('#zone_id').length > 0) {
                           //$("#dialog-address-update").find('fieldset').find('#zone_id').val(str.entry_zone_id);
                         //}
                         if ($("#dialog-address-update").find('fieldset').find('#update_address_book_id').length > 0) {
                           if (typeof(str.address_book_id) == 'undefined' || str.address_book_id == null) {
                             //create new address
                             $("#dialog-address-update").find('fieldset').find('#update_address_book_id').val(0);
                           }
                           else {
                             $("#dialog-address-update").find('fieldset').find('#update_address_book_id').val(str.address_book_id);
                           }
                         }
        
                         //check if primary addres option should be on
                         if (customer_default_id == str.address_book_id) {
                           $("#dialog-address-update").find('fieldset').find('#address_primary').html('');
                         }
                         else {
                           $("#dialog-address-update").find('fieldset').find('#address_primary').html('<div class="pblock"><div class="plabel"><label for="primary" class="p_popup"><?php echo SET_AS_PRIMARY; ?></label></div><div class="pinput"><div style="margin: 10px 0px 0px 0px; display:block; float:left; text-align:left;"><?php echo tep_draw_checkbox_field('primary', 'on', false, 'id="primary"'); ?></div></div></div>');
                         }
        
                          $( "#dialog-address-update" ).dialog( "open" );
                          $("#loading_"+button.attr('address_book_id')).html('');

                          //find postcode button on/off
                          switchmode();
                       }
                    });
        
              });
        
            $( "#dialog-address-delete" ).dialog({
              autoOpen: false,
              height: 300,
              width: 400,
              modal: true,
        
              buttons: [
              {
                id: "delete-address-confirm",
                text: "Delete Address",
                click: function() {
                  var formid = $("#dialog-address-delete").find('#address_delete input[name=formid]');
                  var update_address_book_id = $("#dialog-address-delete").find('#update_address_book_id').val();
                  var bValid = true;
                  bValid = bValid && (update_address_book_id >0);
                  if ( bValid ) {
                    //My modification ajax: save it to customer
                    var customer_id = "<?php echo $customer_id; ?>";
                    var string_data= '{"action":"address_delete_confirm",' + 
                                      '"address_book_id":"' + update_address_book_id + '",' +
                                      '"customer_id":"' + customer_id + '",' +
                                      '"formid":"' + formid.val() + '", "osCsid":"'+osCsid+'"}';
                    var JSONObject = eval ("(" + string_data + ")");
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                        $("#loading_"+update_address_book_id).html("<img src='simplecheckout/loading.gif' />");
                       },
                       data: JSONObject,
                       success: function(html) {
                         //html.warninghtml.msg
                         $('div#addresses_list').load('address_book.php #addresses_list', {customer_id: customer_id, action: "address_save_p", "osCsid":osCsid}, function() {
                           $('div#addresses_list button').button();
                           $('div#addresses_list button').trigger('refresh');
                         });
                       }
                    });
        
                    $( this ).dialog( "close" );
                  }
                }
              },
              {
                    id: "delete-address-cancel",
                    text: "Cance",
                    click: function() {
                        $(this).dialog("close");
                    }
              }
              ],
                //Cancel: function() {
                //  $( this ).dialog( "close" );
                //}
        
              close: function() {
              }
            });
        
              $("div#addresses_list").on('click', '.button_delete_address', function() {
                /* pre populate dialog-form*/
                var button = $(this);
                var customer_id = "<?php echo $customer_id; ?>";
                var customer_default_id = $('#customer_primary_address') . attr('defaultid');
                var string_data= '{"action":"address_delete_popup",' + '"address_book_id":"' +button.attr('address_book_id') + '","customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
                var JSONObject = eval ("(" + string_data + ")");        //var JSONObject = jQuery.parseJSON(string_data);  //this is alternative
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                        $("#loading_"+button.attr('address_book_id')).html("<img src='simplecheckout/loading.gif' />");
                       },
                       dataType: "json",
                       data: JSONObject,
                       success: function(html) {
                         var str=html;
                         $("#dialog-address-delete").find('#msg').html(str.msg);
                         if (str.warning == "success") {
                           $("#dialog-address-delete").find('#update_address_book_id').val(button.attr('address_book_id'));
                           $("#delete-address-confirm").button("enable");
                         }
                         else {
                           //this would works: $("#dialog-address-delete").nextAll(".ui-dialog-buttonpane").find("button:contains('Delete Address')").attr("disabled", true).addClass("ui-state-disabled");
                           $("#delete-address-confirm").button("disable");
                         }
                         $( "#dialog-address-delete" ).dialog( "open" );
                         $("#loading_"+button.attr('address_book_id')).html('');
                       }
                    });
              }); //$("div#addresses_list").on
        
              //Back button
              //$( "div#addresses_list button#go_back" ).button() .click(function() {
              $("div#addresses_list").on('click', "button#go_back", function() {
                //$("#loading_back").show();
                $(this).css({ borderStyle:"inset", cursor:"wait" });
                $(this).attr('disabled','disabled');
                //$("#loading_back").html("<img src='simplecheckout/loading.gif' />");
                //$(this).replaceWith("<img src='simplecheckout/loading.gif' />");
                document.location.href="<?php echo tep_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>";
              })
        
              //update state_input/zone_id
              $( "#dialog-address-update").on('change', '#country', function() {
                if ($("#dialog-address-update").find('fieldset').find('#state_input').length > 0) {
                    var update_address_book_id = $("#dialog-address-update").find('#update_address_book_id').val();
                    var country_id = $("#dialog-address-update").find('#country').val();
                    var customer_id = "<?php echo $customer_id; ?>";
                    var string_data= '{"action":"update_state_input",' + '"update_address_book_id":"' + update_address_book_id + '","country":"' + country_id + '", "customer_id":"' + customer_id + '", "osCsid":"'+osCsid+'"}';
                    var JSONObject = eval ("(" + string_data + ")");
                    switchmode(); //postcodeanywhere
                    $.ajax( {
                       type: "POST",
                       url: "<?php echo 'simplecheckout/address_update.php';?>",
                       cache: false,
                       beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                        //$("#loading_"+button.attr('address_book_id')).html("<img src='simplecheckout/loading.gif' />");
                       },
                       dataType: "json",
                       data: JSONObject,
                       success: function(html) {
                         var str=html;
                         $("#dialog-address-update").find("#state_input").html(str.state_entry).children().removeClass('register-input').addClass('p_popup ui-widget-content ui-corner-all')

                       }
                    });
        
                }
              });
        
        
          }); //$(function()

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
          	    <h1><?php echo HEADING_TITLE; ?></h1>
                <?php
                if ($messageStack->size('addressbook') > 0) {
                  echo $messageStack->output('addressbook') . "\n";
                }
                ?>
                <div class="grid-01">

                  <div class="grid-01-02 right">
                  	<div class="table-wrapper">
						            <h2 class="sub-title"><?php echo PRIMARY_ADDRESS_TITLE;?></h2>
                        <div id="customer_primary_address" defaultid="<?php echo $customer_default_address_id;?>" ><p><?php echo tep_address_label($customer_id, $customer_default_address_id, true, ' ', '<br />'); ?></p></div>

                        <p><em><span class="smallText"><?php echo PRIMARY_ADDRESS_DESCRIPTION; ?></span></em></p>
                    </div>

                    <div id="address_details">
                      <div id="addresses_list"> 

 
                        <div class="table-wrapper">
                            <h2 class="sub-title"><?php echo ADDRESS_BOOK_TITLE;?></h2>
                            <?php
                              $addresses_query = tep_db_query("select address_book_id, entry_firstname as firstname, entry_lastname as lastname, entry_company as company, entry_house_name as house_name, entry_street_address as street_address, entry_suburb as suburb, entry_city as city, entry_postcode as postcode, entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' order by firstname, lastname");
                              while ($addresses = tep_db_fetch_array($addresses_query)) {
                                $format_id = tep_get_address_format_id($addresses['country_id']);
                            ?>
                            <div class="single-address">
                                <div id="<?php echo $addresses['address_book_id'] . "_address";?>">
                                    <p>
                                    <strong><?php echo tep_output_string_protected($addresses['firstname'] . ' ' . $addresses['lastname']); ?></strong><?php if ($addresses['address_book_id'] == $customer_default_address_id) echo '&nbsp;<em>' . PRIMARY_ADDRESS . '</em>'; ?>
                                    <br />
                                    <?php echo tep_address_format($format_id, $addresses, true, ' ', '<br />'); ?>
                                    </p>
                                </div>
                                <p>
                                    <button class="button_edit_address" address_book_id="<?php echo $addresses['address_book_id'];?>"><span class="smallText"><?php echo SMALL_IMAGE_BUTTON_EDIT;?></span></button>
                                    <button class="button_delete_address" address_book_id="<?php echo $addresses['address_book_id'];?>"><span class="smallText"><?php echo SMALL_IMAGE_BUTTON_DELETE;?></span></button>
                                    <span id="<?php echo 'loading_' . $addresses['address_book_id'];?>"></span>
                                </p>
                                
                            </div><!--/.single-address-->
                            <?php
                              }
                            ?>
                            <ul id="bt-address-manage">
                                <li>
                                  <?php
                                    if (tep_count_customer_address_book_entries() < MAX_ADDRESS_BOOK_ENTRIES) {
                                      echo '                              <button id="go_back">' . BUTTON_GO_BACK . '</button> <button class="button_edit_address" address_book_id="0">' . SMALL_IMAGE_BUTTON_ADD . '</button>' . "\n";
                                    }
                                    else {
                                      echo '                              <button id="go_back">' . BUTTON_GO_BACK . '</button>' . "\n";
                                    }
                                  ?>
                                </li>
                            </ul><!--/#bt-address-manage-->
    
                            <div class="clearme"></div>
                        </div><!--/.table-wrapper readable-text-->


                      </div>
                    </div>


                    <div class="clearme"></div>
                    <div>


                      <!--start dialog form-->
                                <div id="dialog-address-update" title="address update" class="jdialog">
                      
                      <!---->
                      <div id="indicator" style="visibility:hidden;"><?php echo tep_image(DIR_WS_IMAGES . 'icons/indicator.gif'); ?></div>
                      <!---->
                      
                        <span class="validateTips"><?php echo FORM_REQUIRED_INFORMATION;?></span>
                      
                        <?php echo tep_draw_form("address_update", '', 'post', 'id="address_update"', true);?>
                        <fieldset class="p_popup">
                      
                      <?php
                        if (ACCOUNT_GENDER == 'true') {
                          $male = $female = false;
                          if (isset($gender)) {
                            $male = ($gender == 'm') ? true : false;
                            $female = !$male;
                          } elseif (isset($entry['entry_gender'])) {
                            $male = ($entry['entry_gender'] == 'm') ? true : false;
                            $female = !$male;
                          }
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="gender" class="p_popup"><?php echo ENTRY_GENDER . '&nbsp;' . (tep_not_null(ENTRY_GENDER_TEXT) ? '<span>' . ENTRY_GENDER_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_radio_field('gender', 'm', $male) . '&nbsp;&nbsp;' . MALE . '&nbsp;&nbsp;' . tep_draw_radio_field('gender', 'f', $female) . '&nbsp;&nbsp;' . FEMALE; ?></div>
                                    </div>
                      <?php
                        }
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="firstname" class="p_popup"><?php echo ENTRY_FIRST_NAME . '&nbsp;' . (tep_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span>' . ENTRY_FIRST_NAME_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('firstname', $entry['entry_firstname'], 'id="firstname" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                      
                                    <div class="pblock">
                                      <div class="plabel"><label for="lastname" class="p_popup"><?php echo ENTRY_LAST_NAME . '&nbsp;' . (tep_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('lastname', $entry['entry_lastname'],'id="lastname" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                      
                                    <div class="pblock">
                                      <div class="plabel"><label for="country" class="p_popup"><?php echo ENTRY_COUNTRY . '&nbsp;' . (tep_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_get_country_list('country', tep_not_null($country) ? $country : $entry['entry_country_id'], 'id="country" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                                    <div class="pblock"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '1'); ?></div>
                      <!-- //My modification - postcodeanywhere -->
                                    <div class="pblock">
                                      <div class="plabel"><label for="postcode" class="p_popup"><div id="btnFinddiv1"><?php echo ENTRY_POST_CODE . '&nbsp;' . (tep_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>': ''); ?></div></label></div>
                                      <div class="pinput">
                                        <div style="position:relative;height:30px;">
                                          <div style="position:absolute; top:0px; left:0px; width:110px;"><?php echo tep_draw_input_field('postcode',$entry['entry_postcode'],'id="postcode" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                          <div style="position:absolute; top:0px; left:110px; width:120px;"><div id="btnFinddiv2"><?php echo tep_crafty_button(); ?></div></div>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="pblock" style="height:30px;padding-top:5px;padding-bottom:5px;">
                                      <div class="plabel"><label for="" class="p_popup"></label></div>
                                      <div class="pinput"><div id="crafty_postcode_result_display">&nbsp;</div></div>
                                    </div>
                      <!-- //My modification - postcodeanywhere -->
                      
                      <?php
                        if (ACCOUNT_COMPANY == 'true') {
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="company" class="p_popup"><?php echo ENTRY_COMPANY . '&nbsp;' . (tep_not_null(ENTRY_COMPANY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COMPANY_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('company', $entry['entry_company'],'id="company" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                      <?php
                        }
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="house_name" class="p_popup"><?php echo ENTRY_HOUSE_NAME . '&nbsp;' . (tep_not_null(ENTRY_HOUSE_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_HOUSE_NAME_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('house_name', $entry['entry_street_address'],'id="house_name" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>

                                    <div class="pblock">
                                      <div class="plabel"><label for="street_address" class="p_popup"><?php echo ENTRY_STREET_ADDRESS . '&nbsp;' . (tep_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('street_address', $entry['entry_street_address'],'id="street_address" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>

                      <?php
                        if (ACCOUNT_SUBURB == 'true') {
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="suburb" class="p_popup"><?php echo ENTRY_SUBURB . '&nbsp;' . (tep_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('suburb', $entry['entry_suburb'],'id="suburb" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                      <?php
                        }
                      ?>
                      
                                    <div class="pblock">
                                      <div class="plabel"><label for="city" class="p_popup"><?php echo ENTRY_CITY . '&nbsp;' . (tep_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>': ''); ?></label></div>
                                      <div class="pinput"><?php echo tep_draw_input_field('city', $entry['entry_city'],'id="city" class="p_popup ui-widget-content ui-corner-all"'); ?></div>
                                    </div>
                      
                      <?php
                        if (ACCOUNT_STATE == 'true') {
                      ?>
                                    <div class="pblock">
                                      <div class="plabel"><label for="state_input" class="p_popup"><?php echo ENTRY_STATE; ?></label></div>
                                      <div class="pinput"><div id="state_input">
                                      <?php
                                          // +Country-State Selector
                                              //echo ajax_get_zones_html($entry['entry_country_id'],($entry['entry_zone_id']==0 ? $entry['entry_state'] : $entry['entry_zone_id']),false, 'class="p_popup ui-widget-content ui-corner-all"');
                                          // -Country-State Selector
                                          //if (tep_not_null(ENTRY_STATE_TEXT)) echo '&nbsp;<span class="inputRequirement">' . ENTRY_STATE_TEXT;
                                      ?></div>
                                      </div>
                                    </div>
                      <?php
                        }
                      ?>
                                    <div id="address_primary">
                                    </div>
                                    <div><?php echo tep_draw_hidden_field('update_address_book_id', '', 'id="update_address_book_id"'); ?></div>
                        </fieldset>
                        <?php echo '</form>' . "\n";?>
                      
                                </div>
                      <!--end dialog form-->

                    </div>
                    <div class="clearme"></div>
                    <div>
                          <!--start dialog form-->
                          <div id="dialog-address-delete" title="address delete" class="jdialog">
                            <?php echo tep_draw_form("address_delete", '', 'post', 'id="address_delete"', true);?>
                              <div id="msg"></div>
                              <div><?php echo tep_draw_hidden_field('update_address_book_id', '', 'id="update_address_book_id"'); ?></div>
                            <?php echo '</form>' . "\n";?>
                          </div>
                          <!--end dialog form-->
                    </div>
                    <div class="clearme"></div>
                    <div class="smallText"><br /><p><?php echo sprintf(TEXT_MAXIMUM_ENTRIES, MAX_ADDRESS_BOOK_ENTRIES); ?></p></div><br /><br />

                  </div><!--/.grid-01-02-->

                  <div class="grid-01-01">
                    <div class="table-wrapper">
                          <h2 class="sub-title"><?php echo COLOUR_MATCH_TITLE;?></h2>
                          <ul>
                            <li><?php echo '<a href="' . tep_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">' . COLOUR_MATCH_UPLOAD_1 . '</a>'; ?></li>
                          </ul>
                          <br />

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
                  <p><br /><br /><br />&nbsp;<br /><br /><br /></p>
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