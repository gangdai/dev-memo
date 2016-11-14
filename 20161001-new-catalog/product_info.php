<?php
  require('includes/application_top.php');

  $product_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");
  $product_check = tep_db_fetch_array($product_check_query);

// begin recently_viewed
// Creates/updates a session variable -- a string of products IDs separated by commas
//    IDs are in order newest -> oldest
  if (($spider_flag) or (strpos($user_agent, "Googlebot")>0)){
  	//check if it is bot/spider
  }
  else {
    $recently_viewed_string = '';
    if ($product_check['total'] > 0) { //We don't want to add products that don't exist/are not available

      if (!tep_session_is_registered('recently_viewed')) {
        tep_session_register('recently_viewed');
      } else {
        $recently_viewed_string = $_SESSION['recently_viewed'];
      }

  // Deal with sessions created by the previous version
      if (substr_count ($recently_viewed_string, ';') > 0) {
        $_SESSION['recently_viewed'] = '';
        $recently_viewed_string = '';
      }

  // We only want a product to display once, so check that the product is not already in the session variable
      $products_id = (int) $_GET['products_id'];
      if ($recently_viewed_string == '') { // No other products
        $recently_viewed_string = (string) $products_id; // So set the variable to the current products ID
      } else {
        $recently_viewed_array = explode (',', $recently_viewed_string);
        if (!in_array ($products_id, $recently_viewed_array) ) {
          $recently_viewed_string = $products_id . ',' . $recently_viewed_string; //Add the products ID to the beginning of the variable
        }
      }

      $_SESSION['recently_viewed'] = $recently_viewed_string;
      $kill_sid = false;
    } //if ($product_check['total']
    else {
    	//20141202
      header('HTTP/1.1 404 Not Found');
      header('Location: ' . tep_href_link(FILENAME_PAGE_NOT_FOUND));
      //20141202
    }
  
  }
// end recently_viewed



  //201512
  $product_info = tep_db_fetch_array(tep_db_query("select p.products_id, pd.products_name, p.products_model, p.manufacturers_id, m.manufacturers_name, pd.products_description, p.products_quantity, p.products_price, p.products_market_price, p.products_tax_class_id, p.products_date_added, p.products_date_available, p.products_bo, p.products_free_shipping, p.products_bundle, p.parent_id, p.has_children from " . TABLE_PRODUCTS . " p left join " . TABLE_MANUFACTURERS . " m on p.manufacturers_id = m.manufacturers_id, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'"));

  if (isset($product_info['has_children']) && tep_not_null($product_info['has_children'])) { //master
    $mpid= (int)$HTTP_GET_VARS['products_id'];
  }
  elseif (isset($product_info['parent_id']) && tep_not_null($product_info['parent_id'])) { //slave
    $mpid= (int)$product_info['parent_id'];
    $slave_pid = (int)$HTTP_GET_VARS['products_id'];
  }

  if (tep_not_null($slave_pid)) {
  	$product_info_s = tep_db_fetch_array(tep_db_query("select pd.products_name, p.products_model, p.manufacturers_id, pd.products_description from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . $mpid . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'"));
	  $products_name = $product_info_s['products_name'];
	  $products_model = $product_info_s['products_model'];
	  $products_desc = $product_info_s['products_description'];
	  $product_categories_name = tep_get_products_categories_name($mpid, $languages_id);
  }
  else {
    $products_name = $product_info['products_name'];
    $products_model = $product_info['products_model'];
    $products_desc = $product_info['products_description'];
    $product_categories_name = tep_get_products_categories_name($HTTP_GET_VARS['products_id'], $languages_id);
  }
  //201512

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_PRODUCT_INFO);

  //My modification - free offer products user cannot add these product to cart
  $freeproductsoffer= $cart->get_allfreeproductsid();
  if (in_array($HTTP_GET_VARS['products_id'], $freeproductsoffer)) {
  	$freeproductsoffer_nocart = true;
  }
  if (is_coloursample_categories($HTTP_GET_VARS['products_id'])) {
  	$is_coloursample = true;
  }
?>






































<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
    <head>
    	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>" />
    	<title><?php if ($product_check['total'] > 0) {echo PRODUCTTITLE;} else { echo TEXT_PRODUCT_NOT_FOUND;} ?></title>

    	<?php
    	  if ($product_check['total'] > 0) {  
    	?>
      <meta name="keywords" id="keywords" content="<?php echo PRODUCTS_METAKEYWORD; ?>" />
      <meta name="description" id="description" content="<?php echo PRODUCTS_METADESCRIPTION; ?>" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />

     	<meta name="robots" content="index, follow" />
    	<?php
    	  }
    	?>
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
<!--
      <script src="js/jquery.elevateZoom-3.0.8.min.js"></script>
-->
      <script src="js/jquery.barrating.js" defer></script>
<!--
      <script src="js/owl.carousel.min.js" defer></script>
-->
      <script src="js/featherlight.min.js" defer></script>

      <script src="js/jquery.mmenu.min.js" defer></script>
      <script src="js/2017.js" defer></script>



		  <script src="jquery.bxslider.min.js" defer></script>

      <!--CSS-->
      <link rel="stylesheet" href="css/jquery.mmenu.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.positioning.css" />
      <link rel="stylesheet" href="css/jquery.mmenu.pagedim.css" />
<!--
      <link rel="stylesheet" href="css/owl.carousel.css" />
      <link rel="stylesheet" href="css/owl.theme.css" />
-->
      <link rel="stylesheet" href="css/featherlight.min.css" />
		  <link href="jquery.bxslider.css" rel="stylesheet" />

      <?php $_canonicalUrl = canonicalUrl(); echo '<link rel="canonical" href="' . $_canonicalUrl . '" />';?>

      <script src="js/jquery.easyPaginate.js" defer></script>

		  <script>
			/*<![CDATA[*/
			var osCsid = "<?php echo tep_session_id();?>";

			$(document).ready(function(){

        //COLOUR MATCH
        $("#colour-match").mmenu({
          extensions: ["pagedim-black"],
          navbar: {
            add: false  
          },
          offCanvas: {
            position  : "right",
            zposition : "front"
          }
        });

        var colormenu = $("#colour-match").data("mmenu");

        //$("#my-button").click(function() {
         
        //});

          function addToCart(gc_product, eventlabel) {
return false; //comment this when live
            if (eventlabel =='add to cart - os')
              var eventvalue = 0;
            else
              var eventvalue = 1;
            ga('ec:addProduct', {
              'id': gc_product.pid,
              'name': gc_product.pname,
              'brand': gc_product.pbrand,
              'variant': gc_product.pvariant,
              'quantity': gc_product.pqty
            });
            ga('ec:setAction', 'add');
            ga('send', 'event', 'button', 'click', eventlabel, eventvalue);
          }

        //CONTENT TABS
        if ( $( "ul.content-tabs-nav" ).length ) {
          $('ul.content-tabs-nav > li > a, .content-accordion-title').click(function(event){
            var tab_id = $(this).attr('data-tab');

            $('ul.content-tabs-nav > li > a').removeClass('active');
            $('.content-tabs-content').removeClass('active');

            $(this).addClass('active');
            $("#"+tab_id).addClass('active');

            event.preventDefault();
          });
        }

        if (window.location.hash && window.location.hash == "#tab-reviews") {
        	$("#tab-reviews").trigger("click");
        }

        //TITLE REVIEWS LINK
        $('.tab-anchor-open').click(function(){
          var tab_id = $(this).attr('data-tab');
          $("#"+tab_id).trigger("click");
        });
        //STAR RATING
        $('#review-rating').barrating({
          theme: 'fontawesome-stars',
          wrapperClass: 'star-wrapper'
        });
/*
        //WRITE A REVIEW
        $("#review-text").bind("input", function () {
            if (!$.trim($("#review-text").val())) {
            $("#review-additional").slideUp('fast');
            $(this).css({'height': '45'});
          } else {
                $("#review-additional").slideDown('fast');
            $(this).css({'height': '145'});
          }
        }).trigger("input");
*/
					//BX SLIDER (Product Main images)
					var smain= $('#product-image-big').bxSlider({
  					pagerCustom: '#product-image-list',
						mode: 'fade',
            onSliderLoad: function(){
            },
            onSlideAfter: function(){
            },
						controls: false
					});

					//BX SLIDER (product thumbs)
					var sthumb = $('#product-image-list').bxSlider({
						slideWidth: 60,
						minSlides: 6,
						maxSlides: 6,
						moveSlides: 6,
						controls: false,
						infiniteLoop: false,
						pager: ($("#product-image-list a").length > 6) ? true: false
  					//hideControlOnEnd: true
					});
          setTimeout(function showMorepics() { $('#product-image-list a').fadeIn(); }, 800);

          setTimeout(function showShipping() { $('#product_shipping').load('info/info_shipping_product.php');},1000);

          $('form[name="product_review"]').on('click', 'input#review-submit', function(e) {
          	e.preventDefault();
          	var rtitle = $('form[name="product_review"]').find('input#review-title').val();
          	var rrating = $('form[name="product_review"]').find('select#review-rating').val();
          	var rtext = $('form[name="product_review"]').find('textarea#review-text').val();
          	var minimum_length = parseInt("<?php echo REVIEW_TEXT_MIN_LENGTH;?>");

          	if (!rtitle || !rrating || rtext.length < minimum_length ) {
          		if (rtext.length < minimum_length ) {
          			$('form[name="product_review"]').find('span#error').html("<?php echo JS_REVIEW_TEXT;?>").show(); setTimeout(function() { $('form[name="product_review"]').find('span#error').fadeOut();}, 3000);
          		} else {
          			$('form[name="product_review"]').find('span#error').html("Please complete the review details").show(); setTimeout(function() { $('form[name="product_review"]').find('span#error').fadeOut();}, 3000);
          		}
          	}
          	else {
          		var customer_id = parseInt("<?php echo $customer_id;?>"); if (customer_id < 1 || isNaN(customer_id)) return false;
              var string_data = '{"action":"addreview","osCsid":"' + osCsid + '", "rtitle":"' + rtitle + '", "rrating":"' + rrating + '", "rtext":"' + rtext + '", "pid":"<?php echo $products_id;?>"}';
              var JSONObject = eval ("(" + string_data + ")");
              $.ajax( {
                type: "POST",
                url: "<?php echo 'reviews.php';?>",
                cache: false,
                beforeSend: function(x) {
                  if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); } 
                },
                dataType: "json",
                data: JSONObject,
                success: function(html) {
                	$("#reviews-write").html("<p><?php echo TEXT_REVIEW_RECEIVED;?></p>");
                }
              });
          	}
          })
          .on('keyup', 'textarea#review-text', function(e) {
            if (!$.trim($("#review-text").val())) {
              $("#review-additional").slideUp('fast');
              $(this).css({'height': '45'});
            } else {
              $("#review-additional").slideDown('fast');
              $(this).css({'height': '130'});
            }
          });

            //add to cart
            $("form[name='cart_quantity']").submit(function() { return false;});
            $('body').on('click', '#button_buynow', function(e) {
            	  e.preventDefault();
                var attributes = true;
                if (typeof chksel == 'function') {
                  attributes = chksel();
                  if (attributes) {} else attributes =false;
                }

                if (attributes) {












                  var inoarray = new Array();
                  var is_withmaster = 0;
                  var pvstr_name = "";
                  var removeElements = function(ptext, selector) {
                            var wrapped = $("<div>" + ptext + "</div>");
                            wrapped.find(selector).remove();
                            return wrapped.html();
                  }
                  $("#products_variants").find(":radio").each(function(){
                    if ($(this).is(':checked')) {
                      inoarray[this.name] = this.id;
                      pvstr_name += removeElements($("#products_variants").find("#" + this.id).parent().find('h3').html(), "span").trim() + " " + $("#products_variants").find("#" + this.id).val() + "<br />";
                    }
                    else {
                      if (inoarray[this.name] == 'undefined' || inoarray[this.name] == null) {inoarray[this.name] = 0;}
                      else if (inoarray[this.name] == 0) {}
                      else {}
                    }
                    is_withmaster++;
                  });
                  if (pvstr_name.length)
                    pvstr_name = pvstr_name.substring(0, pvstr_name.length - 6);

                  if (is_withmaster) {
                    var is_select_completed = true;
                    for (var key in inoarray) {
                      if (inoarray.hasOwnProperty(key)) {
                        if (inoarray[key] ==0) {
                          $("#products_variants").find("input:radio[name=" + key + "]:first").parent().find('h3').find('.error').html("Please select a " + $("#products_variants").find("input:radio[name=" + key + "]:first").parent().find('h3').find(".attributes").attr("attr"));
                          is_select_completed = false;
                        }
                      }
                    }

                    if (is_select_completed) {
                    }
                    else {
                      return false;
                    }
                  }




                  var string_data = "{";
                  $("form[name='cart_quantity'] :input").each( function() {
                    if (this.name) {
                      string_data += '"'+this.name + '":"' + escape(this.value)+'",';
                    }
                  });
                  string_data += '"action":"addtocart","osCsid":"' + osCsid + '"}';

                  var JSONObject = eval ("(" + string_data + ")");
                      $.ajax( {
                        type: "POST",
                        url: "<?php echo 'cart.php';?>",
                        cache: false,
                        beforeSend: function(x) {
                          if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); } 
                          if ($('.btos').is(":visible")) {
                          	$('.btos').hide();
                          }
                          $("#add_to_cart").find("#aviewcart").hide();
                          $("#add_to_cart").find("#acheckout").hide();
                          $("#add_to_cart").find(".ainner").removeClass('aicon');
                          $("#add_to_cart").slideDown('fast').addClass('loadingx');
                        },
                        dataType: "json",
                        data: JSONObject,
                        success: function(html) {
                          //alert(html.products_name +"=="+ html.products_model +"=="+html.attribute+"==" +html.qty+"=="+html.os+"=="+html.number_of_items+"=="+html.cart_value);
                          //20150115
                          if (html.attribute) { var gc_pvariant = html.attribute.trim(); }
                          else { var gc_pvariant = "<?php echo addslashes($products_model);?>"; }
                          var gc_p = {pid:$("#cart_quantity").find("input[name=products_id]").val(), pname:"<?php echo addslashes($products_name);?>", pbrand:"<?php echo addslashes($product_info['manufacturers_name']);?>", pvariant: gc_pvariant, pqty: html.qty};
                          //20150115
                          if (html.os.length>0) {
                            $("#add_to_cart").removeClass('loadingx');
                            $("#add_to_cart").hide();
                            $('.availability').hide();
                            $('.btos').show();

                            //20150115
                            addToCart(gc_p, 'add to cart - os');
                            //20150115
                          }
                          else {
                          	//$("#add_to_cart").find(".ainner").show();
                            $("#add_to_cart").find("#atitle").html("<?php echo "ITEM ADDED TO CART";?>");
                            $("#add_to_cart").find(".ainner").addClass('aicon');

                            if (typeof is_withmaster != "undefined" && is_withmaster>0) {
                            	$("#add_to_cart").find("#aname").html("<?php echo addslashes($products_name);?>");
                            	$("#add_to_cart").find("#amodel").html("<?php echo addslashes($products_model);?>");
                            	if (pvstr_name) {
                            		$("#add_to_cart").find("#aattribute").show().html(pvstr_name);
                            	}
                            }
                            else {
                              $("#add_to_cart").find("#aname").html(html.products_name);
                              $("#add_to_cart").find("#amodel").html(html.products_model);
                              if (html.attribute) { $("#add_to_cart").find("#aattribute").show().html(html.attribute);}
                              else {$("#add_to_cart").find("#aattribute").hide();}
                            }

                            $("#add_to_cart").find("#aqty").html('Qty <strong>' + html.qty + '</strong>');
                            $("#add_to_cart").find("#aviewcart").show();$("#add_to_cart").find("#acheckout").show();
                            $(".cartcount").html(html.carttext);
                            $("#add_to_cart").removeClass('loadingx');

                            //20150115
                            addToCart(gc_p, 'add to cart');
                            //20150115
                          }

                        }
                      });
                }

                return false;
              //$('input[type=select][name=input_quantity]').val()
            })
            .on('click', 'a#ordersample', function(e) {
                //sample
                e.preventDefault();
                var attributes = true;
                if (typeof chksel == 'function') {
                  attributes = chksel();
                  if (attributes) {} else attributes =false;
                }
                if (attributes) {
                  var string_data = "{";
                  $("form[name='cart_quantity'] :input").each( function() {
                    if (this.name) {
                      string_data += '"'+this.name + '":"' + escape(this.value)+'",';
                      if (this.name == "products_id") string_data += '"samplecolour_products_id":"'+ escape(this.value) + '",';
                    }
                  });

                  string_data += '"action":"addtocart","osCsid":"' + osCsid + '"}';

                  var inoarray = new Array();
                  var is_withmaster = 0;
                  var pvstr_name = "";
                  var removeElements = function(ptext, selector) {
                            var wrapped = $("<div>" + ptext + "</div>");
                            wrapped.find(selector).remove();
                            return wrapped.html();
                  }
                  $("#products_variants").find(":radio").each(function(){
                    if ($(this).is(':checked')) {
                      inoarray[this.name] = this.id;
                      pvstr_name += removeElements($("#products_variants").find("#" + this.id).parent().find('h3').html(), "span").trim() + " " + $("#products_variants").find("#" + this.id).val() + "<br />";
                    }
                    else {
                      if (inoarray[this.name] == 'undefined' || inoarray[this.name] == null) {inoarray[this.name] = 0;}
                      else if (inoarray[this.name] == 0) {}
                      else {}
                    }
                    is_withmaster++;
                  });
                  if (pvstr_name.length)
                    pvstr_name = pvstr_name.substring(0, pvstr_name.length - 6);

                  if (is_withmaster) {
                    var is_select_completed = true;
                    for (var key in inoarray) {
                      if (inoarray.hasOwnProperty(key)) {
                        if (inoarray[key] ==0) {
                          $("#products_variants").find("input:radio[name=" + key + "]:first").parent().find('h3').find('.error').html("Please select a " + $("#products_variants").find("input:radio[name=" + key + "]:first").parent().find('h3').find(".attributes").attr("attr"));
                          is_select_completed = false;
                        }
                      }
                    }

                    if (is_select_completed) {
                    }
                    else {
                      return false;
                    }
                  }








                  var JSONObject = eval ("(" + string_data + ")");
                      $.ajax( {
                        type: "POST",
                        url: "<?php echo 'cart.php';?>",
                        cache: false,
                        beforeSend: function(x) {
                          if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                          if ($('.btos').is(":visible")) {
                          	$('.btos').hide();
                          }
                          $("#add_to_cart").find("#aviewcart").hide();
                          $("#add_to_cart").find("#acheckout").hide();
                          $("#add_to_cart").find(".ainner").removeClass('aicon');
                          $("#add_to_cart").slideDown('fast').addClass('loadingx');
                        },
                        dataType: "json",
                        data: JSONObject,
                        success: function(html) {
                          //20150115
                          if (html.attribute) { var gc_pvariant = html.attribute.trim(); }
                          else { var gc_pvariant = "<?php echo addslashes($products_model);?>"; }
                          var gc_p = {pid:"<?php echo coloursample_pid();?>", pname:"<?php echo addslashes($products_name);?>", pbrand:"<?php echo addslashes($product_info['manufacturers_name']);?>", pvariant: gc_pvariant, pqty: html.qty};
                          //20150115
                          if (html.os.length>0) {
                            $("#add_to_cart").removeClass('loadingx');
                            $("#add_to_cart").hide();
                            $('.availability').hide();
                            $('.btos').show();

                            //20150115
                            addToCart(gc_p, 'add to cart - os');
                            //20150115
                          }
                          else {
                          	//$("#add_to_cart").find(".ainner").show();
                            $("#add_to_cart").find("#atitle").html("<?php echo "ITEM ADDED TO CART";?>");
                            $("#add_to_cart").find(".ainner").addClass('aicon');

                            if (typeof is_withmaster != "undefined" && is_withmaster>0) {
                            	$("#add_to_cart").find("#aname").html("<?php echo addslashes($products_name);?>");
                            	$("#add_to_cart").find("#amodel").html("<?php echo addslashes($products_model);?>");
                            	if (pvstr_name) $("#add_to_cart").find("#aattribute").show().html(pvstr_name);
                            }
                            else {
                              $("#add_to_cart").find("#aname").html(html.products_name);
                              $("#add_to_cart").find("#amodel").html(html.products_model);
                              if (html.attribute) { $("#add_to_cart").find("#aattribute").show().html(html.attribute);}
                              else {$("#add_to_cart").find("#aattribute").hide();}
                            }
                            $("#add_to_cart").find("#aqty").html('Qty <strong>' + html.qty + '</strong>');
                            $("#add_to_cart").find("#aviewcart").show();$("#add_to_cart").find("#acheckout").show();
                            $(".cartcount").html(html.carttext);
                            $("#add_to_cart").removeClass('loadingx');

                            //20150115
                            addToCart(gc_p, 'add to cart');
                            //20150115
                          }

                        }
                      });
                }
            })
            .on('click', '#askquestion', function() {
              //$(this).css({ borderStyle:"inset", cursor:"wait" });
              $(this).attr('disabled','disabled');
              var from_name = $("form[name='ask_question']").find('input[name=from_name]').val();
              var from_email_address = $("form[name='ask_question']").find('input[name=from_email_address]').val();
              var to_name = $("form[name='ask_question']").find('input[name=to_name]').val();
              var to_email_address = $("form[name='ask_question']").find('input[name=to_email_address]').val()
              var message = $("form[name='ask_question']").find('textarea[name=message]').val();
              var products_id = "<?php echo $HTTP_GET_VARS['products_id'];?>";
              var string_data = '{"from_name":"' + from_name +'",';

              string_data += '"from_email_address":"' + from_email_address +'",';
              string_data += '"to_name":"' + to_name +'",';
              string_data += '"to_email_address":"' + to_email_address +'",';
              string_data += '"message":"' + message +'",';
              string_data += '"products_id":"' + products_id +'",';
              string_data += '"action":"askaquestion","osCsid":"' + osCsid + '"}';

                  var JSONObject = eval ("(" + string_data + ")");
          
                      $.ajax( {
                        type: "POST",
                        url: "<?php echo 'cart.php';?>",
                        cache: false,
                        beforeSend: function(x) {
                          if(x && x.overrideMimeType) { x.overrideMimeType("application/json;charset=UTF-8"); }
                          $('div#ask_a_question').addClass('loadingx');
                        },
                        dataType: "json",
                        data: JSONObject,
                        success: function(html) {
                          if (html.error>0) {
                            alert(html.msg);
                          }
                          else $('#ask_a_question').html(html.msg);

                          if ($("#askquestion").attr('disabled')) {
                            $("#askquestion").removeAttr('disabled');
                          }
                          $('div#ask_a_question').removeClass('loadingx');
                        }
                      });
          
              return false;
            });
















            if ($("#cart_quantity").find("#products_variants").length>0) {
                  var pid = parseInt("<?php echo $HTTP_GET_VARS['products_id'];?>");
                  if ($("#cart_quantity").find("input[name=products_id]").length>0) {
                    if ($("#cart_quantity").find("input[name=products_id]").val() != pid)
                      $("#cart_quantity").find("input[name=products_id]").val(pid);
                  }

                  var spidimgpid;
                  if (typeof slavepid != "undefined") {
                  	spidimgpid = parseInt(slavepid);
                    var mpid = parseInt("<?php echo $product_info['parent_id'];?>");
                  }
                  else {
                    var mpid = pid;

                    if (typeof pvprices !== 'undefined') {
                      var lowest = 0.00;var lowestkey = 0;var temp_c = 0;
                      for (var key in pvprices) {
                        if (temp_c ==0 ) {
                          lowest = parseFloat(pvprices[key].replace("&#163;", "")); lowestkey = key;
                        }
                        if (parseFloat(pvprices[key].replace("&#163;", "")) < lowest) {
                          lowest = parseFloat(pvprices[key].replace("&#163;", "")); lowestkey =key;
                        }
                        temp_c++;
                      }
                      //$("#cart_quantity").find("#single-product-price").find("#single-product-ourprice").find("[itemprop=price]").html(pvprices[lowestkey]);
                      $("#cart_quantity").find("#product-price").find(".price-now").html(pvprices[lowestkey]);
                    }
                  }

                  var radioimagec = "imagec";
                  var osimage = '<img src="images/attrimgs/os.png" class="os" />';

                  var firstimg = 0;
                  var radioGroupOnpage = new Array();
                  var tmpgcheck = new Array();
                  
                  var colorquery = "colorquery";

                  $("#products_variants").find(":radio").each(function(){
                    if ($(this).attr("class") == radioimagec) {
                      $(this).parent().find('h3').find('.error').empty();
                      if(!$(this).is(':enabled')) {
                        $(this).attr("disabled", false);
                        $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").children('.os').remove();
                      }
                      if (firstimg == 0) {
                      	if (typeof slavepid != "undefined") {
                          if ($(this).is(':checked')) {
                            $(this).prop("checked", false);
                          }
                        } else {
                          if (!$(this).is(':checked')) {
                            $(this).prop("checked", true);
                          }
                          spidimgpid = parseInt($(this).attr('spidimgpid'));
                          $(this).parent().find('h3').find('.attributes').html($(this).val());
                        }
                      }
                      firstimg++;
                    }
                    else {
                      if ($(this).is(':checked')) {
                        $(this).prop("checked", false);
                        $(this).parent().find('h3').find('.attributes').empty();
                        $(this).parent().find('h3').find('.error').empty();
                      }
                      else if(!$(this).is(':enabled')) {
                        $(this).attr("disabled", false);
                      }
                    }

                    if (radioGroupOnpage.indexOf(this.name) < 0) {
                      radioGroupOnpage.push(this.name);
                    }

                  });

                  if (Object.keys(radioGroupOnpage).length <2 && (typeof slavepid === "undefined") ) {
                    	if (typeof spidimgpid !== 'undefined') {
                    		if (mpid == pid && mpid != spidimgpid) {
                    			//1 imagec radio group with default imagec which wont match the master pid, need to set pid to the spidimgpid of default imagec
                          if ($("#cart_quantity").find("input[name=products_id]").val() == pid) $("#cart_quantity").find("input[name=products_id]").val(spidimgpid);
                        }
                    	}
                  }



                  if (typeof defaultrg != "undefined") {
                    for (var key in defaultrg) {
                      if (defaultrg.hasOwnProperty(key)) {
                        var radiogroup = new Array();
                        for (var key1 in defaultrg[key]) {
                          radiogroup.push(key + "-" + defaultrg[key][key1]);
                        }
                        //update attrs
                        $("#products_variants").find('input[name="' + key + '"][type=radio]').each(function (index) {
                            if (this.id ==colorquery) {return true;}
                            if (radiogroup.indexOf($(this).attr('id')) > -1) {
                              if(!$(this).is(':enabled')) {
                                $(this).attr("disabled", false);
                                if ($(this).attr("class") == radioimagec) {
                                  $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").children('.os').remove();
                                }
                              }
                              if (typeof slavepid != "undefined") {
                                if (!$(this).is(':checked')) {
                                	if (this.id == s_default[this.name]) {
                                    $(this).prop("checked", true);
                                    $(this).parent().find('h3').find('.attributes').html($(this).val());
                                  }
                                }
                              }
                            }
                            else {
                              if ($(this).is(':checked')) {
                                $(this).prop("checked", false);
                                $(this).parent().find('h3').find('.attributes').empty();
                                $(this).parent().find('h3').find('.error').empty();
                              }
                              if($(this).is(':enabled')) {
                                $(this).attr("disabled", true);
                              }
                              if ($(this).attr("class") == radioimagec) {
                              	if (!$("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").find(".os").length)
                                  $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").append(osimage);
                              }

                            }
                        });
                      }
                    }
                  }

                  //append to morepics
                  if (typeof slavepid != "undefined") {
                  	//var is_s = 1;
                  }
                  else {
                  	//var is_s = 0;
                    if (typeof spidimgpid !== 'undefined') {
                      var string_data= '{"action":"attrimg",' + '"spid":"' + spidimgpid + '","pid":"' + mpid + '"}';
                      var JSONObject = eval ("(" + string_data + ")");
                      $.ajax( {
                        type: "POST",
                        url:"<?php echo str_replace('&amp;', '&', tep_href_link('pv_call.php')); ?>",
                        cache: false,
                        beforeSend: function(x) {
                          if(x && x.overrideMimeType) {
                            x.overrideMimeType("application/json;charset=UTF-8");
                          }
                        },
                        dataType: "json",
                        data: JSONObject,
                        success: function(html) {
                          if (typeof(html) == 'undefined' || html == null) {
                          }
                          else {
                            for (var key in html) {
                              if (!html.hasOwnProperty(key)) continue;
                              var obj = html[key];
                              if (typeof(obj["large"]) != 'undefined' && obj["large"] != null) {
                                $('#product-image-big:not(.bx-clone)').append('<li spid="' + spidimgpid + '">' + obj["large"] + '</li>');
                              }
                              else {
                              	$('#product-image-big:not(.bx-clone)').append('<li spid="' + spidimgpid + '"><img src="images/' + obj["img"] + '" /></li>');
                              }
                              var thumbnail_num = parseInt($('#product-image-list a:last-child').attr("data-slide-index")) +1;
                              $('#product-image-list:not(.bx-clone)').append('<a href="#" data-slide-index="' + thumbnail_num + '" spid="' + spidimgpid + '"><img src="images/' + obj["img"] + '" alt="" itemprop="image" />');
                            }
/*
                            for (i = 0; i < html.length; i++) {
                              $('#product-image-big:not(.bx-clone)').append('<li spid="' + spidimgpid + '"><img src="images/' + html[i] + '" /></li>');
                              var thumbnail_num = parseInt($('#product-image-list a:last-child').attr("data-slide-index")) +1;
                              $('#product-image-list:not(.bx-clone)').append('<a href="#" data-slide-index="' + thumbnail_num + '" spid="' + spidimgpid + '"><img src="images/' + html[i] + '" alt="" itemprop="image" />');
                            }
*/
                            if ($("#product-image-list a").length>0)
                              sthumb.reloadSlider();
                            //smain.reloadSlider();

                              smain.reloadSlider({
                                pagerCustom: '#product-image-list',
                                mode: 'fade',
                                onSliderLoad: function(){
                                  //DESTROY IMAGE ZOOM ON RESIZE
                                  $('.zoomContainer').remove();
                                  $(".product-image-zoom").removeData('elevateZoom');
                                  $(".product-image-zoom").removeData('zoomImage');
                                  $(".product-image-zoom").each(function(){
                                    $("#"+ this.id).elevateZoom({ zoomType : "window", cursor:"crosshair", zoomWindowFadeIn: 200, zoomWindowFadeOut: 200, responsive: true});
                                  });
                                },
                                controls: false,
                              });

                            //smain.goToSlide(2);
                          }
                        }
                      });
                    }
                  }

            }
            $("#products_variants").on('click', "label", function() {

              var radioid = $(this).attr('for');
              var thisrb = $("#products_variants").find('#' + radioid);

              if (radioid == colorquery) { colormenu.open();return false;}

              if (typeof thisrb.attr('spidimgpid') !== 'undefined') {

              	if (typeof cspimgpid !== 'undefined') {
              		if (cspimgpid == thisrb.attr('spidimgpid')) {
              		}
              		else if (thisrb.attr('spidimgpid') == spidimgpid && cspimgpid == 0) {
              		}
              		else {
              			cspimgpid = thisrb.attr('spidimgpid');
              			//get pv img currently clicked
              			var string_data= '{"action":"attrimg",' + '"spid":"' + cspimgpid + '","pid":"' + mpid + '"}';
                    var JSONObject = eval ("(" + string_data + ")");
                    $.ajax( {
                      type: "POST",
                      url:"<?php echo str_replace('&amp;', '&', tep_href_link('pv_call.php')); ?>",
                      cache: false,
                      beforeSend: function(x) {
                        if(x && x.overrideMimeType) {
                          x.overrideMimeType("application/json;charset=UTF-8");
                        }
                      },
                      dataType: "json",
                      data: JSONObject,
                      success: function(html) {
                      	//remove existing pv imgs

                        $('#product-image-big').children('li').each(function () {
                          if (typeof $(this).attr("spid") !== 'undefined') {
                            $(this).remove();
                          }
                        });
                        $('#product-image-list').children('a').each(function () {
                          if (typeof $(this).attr("spid") !== 'undefined') {
                            $(this).remove();
                          }
                        });
                        var thumbnail_num_o = parseInt($('#product-image-list a:last-child').attr("data-slide-index"));


                        if (typeof(html) == 'undefined' || html == null) {
                        }
                        else {
                          if (html.length>0) {
                            for (var key in html) {
                              if (!html.hasOwnProperty(key)) continue;
                              var obj = html[key];
                              if (typeof(obj["large"]) != 'undefined' && obj["large"] != null) {
                                $('#product-image-big:not(.bx-clone)').append('<li spid="' + cspimgpid + '">' + obj["large"] + '</li>');
                              }
                              else {
                                $('#product-image-big:not(.bx-clone)').append('<li spid="' + cspimgpid + '"><img src="images/' + obj["img"] + '" /></li>');
                              }
                              var thumbnail_num = parseInt($('#product-image-list a:last-child').attr("data-slide-index")) +1;
                              $('#product-image-list:not(.bx-clone)').append('<a href="#" data-slide-index="' + thumbnail_num + '" spid="' + cspimgpid + '"><img src="images/' + obj["img"] + '" alt="" itemprop="image" />');
                            }
                            /*
                            for (i = 0; i < html.length; i++) {
                              $('#product-image-big:not(.bx-clone)').append('<li spid="' + cspimgpid + '"><img src="images/' + html[i] + '" /></li>');
                              var thumbnail_num = parseInt($('#product-image-list a:last-child').attr("data-slide-index")) +1;
                              $('#product-image-list:not(.bx-clone)').append('<a href="#" data-slide-index="' + thumbnail_num + '" spid="' + cspimgpid + '"><img src="images/' + html[i] + '" alt="" itemprop="image" />');
                            }
                            */
                            thumbnail_num_o = thumbnail_num_o + 1;
                          } else {
                            thumbnail_num_o=0;
                          }
                        }

                        if ($("#product-image-list a").length>0)
                          sthumb.reloadSlider();
                        //smain.reloadSlider();
					              smain.reloadSlider({
  						            startSlide: thumbnail_num_o,	
  						            pagerCustom: '#product-image-list',
						              mode: 'fade',
						              onSliderLoad: function(){
                            //DESTROY IMAGE ZOOM ON RESIZE
                            $('.zoomContainer').remove();
                            $(".product-image-zoom").removeData('elevateZoom');
                            $(".product-image-zoom").removeData('zoomImage');
                            $(".product-image-zoom").each(function(){
                              $("#"+ this.id).elevateZoom({ zoomType : "window", cursor:"crosshair", zoomWindowFadeIn: 200, zoomWindowFadeOut: 200, responsive: true});
                            });
                          },
						              controls: false,
					              });
                        //smain.goToSlide(thumbnail_num);
                        $('#product-image-list a').fadeIn();
                      }//ajax.success
                    });

              		}
              	}
              }
              //imagec pv img




     

            	if (thisrb.attr("disabled")) {
            	  var radioname = $("#products_variants").find('#'+radioid).attr("name");
            	  var radioval = $("#products_variants").find('#'+radioid).val();
            	  var errormsg = "";

                  $("#products_variants").find(":radio").each(function() {
                    if (this.name != radioname) {
                      if ($(this).is(':checked')) {
                        errormsg = errormsg + this.value + " & ";
                      }
                    }
                  });
                  if (errormsg.length) {
                    $(this).parent().find('h3').find(".error").html(radioval + " is unavailable in " + errormsg.substring(0, errormsg.length - 3) + ". Please select different " + $(this).parent().find('h3').find(".attributes").attr("attr"));
                  }
                //if ($("#products_variants").find('#'+radioid).is(':checked')) {
                //}
                return false;
            	}
            	else if (thisrb.is(':checked')) {
                thisrb.prop("checked", false);
                $(this).parent().find('h3').find('.attributes').empty();
                $(this).parent().find('h3').find('.error').empty();

              	//refresh attribute
                var rChecked = "";
                var rNotChecked = "";
                for (var i = 0; i < radioGroupOnpage.length; i++) {
                  if (typeof($("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").val()) != 'undefined' && $("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").val() != null) {
                    rChecked += $("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").attr("id") + ",";
                  } else {
                  	rNotChecked += radioGroupOnpage[i] + ",";
                  }
                }
                rChecked = rChecked.substring(0, rChecked.length - 1);
                rNotChecked = rNotChecked.substring(0, rNotChecked.length - 1);

                var string_data= '{"action":"attrcheck",' + '"urid":"' + radioid + '", "rSize":"' + radioGroupOnpage.length + '", "rNotChecked":"' + rNotChecked + '", "rChecked":"' + rChecked + '", "pid":"' + mpid + '"}';

                var JSONObject = eval ("(" + string_data + ")");
                $.ajax( {
                  type: "POST",
                  url:"<?php echo str_replace('&amp;', '&', tep_href_link('pv_call.php')); ?>",
                  cache: false,
                  beforeSend: function(x) {
                    if(x && x.overrideMimeType) {
                      x.overrideMimeType("application/json;charset=UTF-8");
                    }
                  },
                  dataType: "json",
                  data: JSONObject,
                  success: function(html) {
                    for (var key in html) {
                      if (html.hasOwnProperty(key)) {
                        var radiogroup = new Array();
                        for (var key1 in html[key]) {
                          radiogroup.push(key + "-" + html[key][key1]);
                        }
                        //update attrs
                        
                        $("#products_variants").find('input[name="' + key + '"][type=radio]').each(function (index) {
                            if (this.id == colorquery) {return true;}
                            if (radiogroup.indexOf($(this).attr('id')) > -1) {
                              if(!$(this).is(':enabled')) {
                                $(this).attr("disabled", false);
                                if ($(this).attr("class") == radioimagec) {
                                  $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").children('.os').remove();
                                  $(this).parent().find('h3').find('.error').empty();
                                }
                              }
                            }
                            else {
                              if ($(this).is(':checked')) {
                                $(this).prop("checked", false);
                                $(this).parent().find('h3').find('.attributes').empty();
                                $(this).parent().find('h3').find('.error').empty();
                              }
                              if($(this).is(':enabled')) {
                                $(this).attr("disabled", true);
                              }
                              if ($(this).attr("class") == radioimagec) {
                              	if (!$("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").find(".os").length) {
                                  $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").append(osimage);
                                }
                              }
                            }

                        });
                      }
                    }
                  }//ajax.success
                });
                return false; //disable the onchange event further here
            	}


              //radio button onchange() event
              if (radioid != $('input[name="' + thisrb.attr("name") + '"]:checked').attr('id')) {

                if (!thisrb.attr("disabled")) {
                	thisrb.prop("checked", true);
                  thisrb.parent().find('h3').find('.attributes').html(thisrb.val());
                  $("#products_variants").find('h3').find('.error').empty();
                  //refresh attribute
                  var rChecked = "";
                  var rNotChecked = "";
                  for (var i = 0; i < radioGroupOnpage.length; i++) {
                    if (typeof($("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").val()) != 'undefined' && $("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").val() != null) {
                      rChecked += $("input[type=radio][name=" + radioGroupOnpage[i] + "]:checked").attr("id") + ",";
                    } else {
                      rNotChecked += radioGroupOnpage[i] + ",";
                    }
                  }
                  rChecked = rChecked.substring(0, rChecked.length - 1);
                  rNotChecked = rNotChecked.substring(0, rNotChecked.length - 1);

                  var string_data= '{"action":"attrcheck",' + '"rid":"' + thisrb.attr('id') + '", "rSize":"' + radioGroupOnpage.length + '", "rNotChecked":"' + rNotChecked + '", "rChecked":"' + rChecked + '", "pid":"' + mpid + '"}';

                  var JSONObject = eval ("(" + string_data + ")");
                  $.ajax( {
                    type: "POST",
                    url:"<?php echo str_replace('&amp;', '&', tep_href_link('pv_call.php')); ?>",
                    cache: false,
                    beforeSend: function(x) {
                      if(x && x.overrideMimeType) {
                        x.overrideMimeType("application/json;charset=UTF-8");
                      }
                    },
                    dataType: "json",
                    data: JSONObject,
                    success: function(html) {
                      for (var key in html) {
                        if (html.hasOwnProperty(key)) {
  
                          var radiogroup = new Array();
                          for (var key1 in html[key]) {
                            radiogroup.push(key + "-" + html[key][key1]);
                            if (key=="pv") {
                              var pvid = parseInt(html[key][key1]);
                            }
                          }
                          //update attrs
                          $("#products_variants").find('input[name="' + key + '"][type=radio]').each(function (index) {
                          	  if ($(this).attr('id') == colorquery) {return true;}
                              if (radiogroup.indexOf($(this).attr('id')) > -1) {
                                if(!$(this).is(':enabled')) {
                                  $(this).attr("disabled", false);
                                  if ($(this).attr("class") == radioimagec) {
                                    $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").children('.os').remove();
                                  }
                                }
                              }
                              else {
                                if ($(this).is(':checked')) {
                                  $(this).prop("checked", false);
                                  $(this).parent().find('h3').find('.attributes').empty();
                                  $(this).parent().find('h3').find('.error').empty();
                                }
                                if($(this).is(':enabled')) {
                                  $(this).attr("disabled", true);
                                }
                                if ($(this).attr("class") == radioimagec) {
                                  if (!$("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").find(".os").length) {
                                    $("#products_variants").find("[for='" + $(this).attr('id') + "']").find(".oswrapper").append(osimage);
                                  }
                                }
                              }
  
                          });
  
                        }
                      }
  
                      if (typeof pvid !== 'undefined') {
                        if ($("#cart_quantity").find("input[name=products_id]").length>0) {
                          $("#cart_quantity").find("input[name=products_id]").val(pvid);
                          if (typeof pvprices !== 'undefined') {
                            //$("#cart_quantity").find("#single-product-price").find("#single-product-ourprice").find("[itemprop=price]").html(pvprices[pvid]);
                            $("#cart_quantity").find("#product-price").find(".price-now").html(pvprices[pvid]);
                          }
                          //refresh the availability
                          $('#cart_quantity .availability').html('<?php echo AVAILABILITY_IN;?>');
                        }
                      }
                    }//ajax.success
                  });
                  
                }
                else {
                  return false;
                }

              }


            })
            .on('mouseover', "label.imagec .oswrapper", function() {
              var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
              if (iOS) {return false;}
              else if ($(window).width() < 600) {return false;}

              if ($(this).parent().attr('for') == colorquery) return false;

              if (!$('#products_variants').find(".tooltip").is(":visible")) {
            	  $('#products_variants').find(".tooltip").html($(this).parent().find('.tooltipi').html());
            	  var heightoft = $('#products_variants').find(".tooltip").outerHeight(true);
            	  var position = $(this).position();
                $('#products_variants').find(".tooltip").css({top: position.top-heightoft-10, left: position.left}).show();
                return false;
              }
            })
            .on('mouseout', "label.imagec .oswrapper", function() {
            	if ($('#products_variants').find(".tooltip").is(":visible")) {
            	  $('#products_variants').find(".tooltip").hide();
            	  return false;
            	}
            });

            $("#colour-match").on('click', '#sample-query', function(e) {
            	e.preventDefault();
            	colormenu.close();
            	$('html, body').animate({scrollTop: $("#ordersample").offset().top}, 500);
            });


        <?php
          if (isset($HTTP_GET_VARS['tab-reviews'])) {
          	echo '$("html, body").animate({scrollTop: $("#tab-reviews").offset().top}, 500);' . "\n";
          	echo '$("#tab-reviews").trigger("click");' . "\n";
          }
        ?>


          $('#reviewslist').easyPaginate({
              paginateElement: 'div',
              elementsPerPage: 20,
              effect: 'fade',
              firstButtonText: '<i class="fa fa-angle-double-left"></i>',
              lastButtonText: '<i class="fa fa-angle-double-right"></i>',
              prevButtonText: '<i class="fa fa-angle-left"></i>',
              nextButtonText: '<i class="fa fa-angle-right"></i>'
          });


			});
			/*]]>*/
		  </script>


    </head>














































  <body id="page-product">
      <!-- header -->
      <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
      <!-- header end -->




















      <div id="container" itemscope itemtype="http://schema.org/Product">
      	<div class="inner">
        <?php
          if ($product_check['total'] < 1) {
            echo '<h2>' . TEXT_PRODUCT_NOT_FOUND . '</h2><br />' . "\n";
            echo '<p><a href="' . tep_href_link(FILENAME_DEFAULT) . '">' . tep_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a></p>' . "\n";
            echo '<p><br /><br /><br /><br /><br /></p><div class="clearme"></div>' . "\n";
          }
          else {
        ?>
      		<div class="grid-01">

            <div class="grid-01-00">
            	<?php
            	  echo '              <h1 itemprop="name" id="product-title">' . $products_name  . '</h1>' . "\n";
                    //reviews
                    if (isset($product_info['has_children']) && tep_not_null($product_info['has_children'])) { //master
                    	$rating_query = tep_db_query("select reviews_rating from " . TABLE_REVIEWS . " r left join " . TABLE_PRODUCTS . " p on r.products_id=p.products_id where (p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' or p.parent_id = '" . (int)$HTTP_GET_VARS['products_id'] . "') and reviews_rating > 1 and date_added < DATE_SUB(NOW(), INTERVAL 1 day) order by date_added desc");
                    }
                    elseif (isset($product_info['parent_id']) && tep_not_null($product_info['parent_id'])) { //slave
                      $rating_query = tep_db_query("select reviews_rating from " . TABLE_REVIEWS . " r left join " . TABLE_PRODUCTS . " p on r.products_id=p.products_id where (p.products_id = '" . (int)$product_info['parent_id'] . "' or p.parent_id = '" . (int)$product_info['parent_id'] . "') and reviews_rating > 1 and date_added < DATE_SUB(NOW(), INTERVAL 1 day) order by date_added desc");
                    }
                    else { //default
                      $rating_query = tep_db_query("select reviews_rating from " . TABLE_REVIEWS . " where products_id = '" . (int)$products_id . "' and reviews_rating > 1 and date_added < DATE_SUB(NOW(), INTERVAL 1 day) order by date_added desc");
                    }
                    if (tep_db_num_rows($rating_query)) {
                      $countreview=0; $counttotal=0;
                      while($rating_array = tep_db_fetch_array($rating_query)) { $countreview++; $counttotal +=(int)$rating_array['reviews_rating']; }
                      if ($countreview) {
                        $productrating1 = tep_round($counttotal/$countreview, 1);
                        $productrating0 = floor($counttotal/$countreview);
                        $star_text = '';
                        if ($productrating1> $productrating0) { // 4.7 > 4
                          for ($i = 0; $i < $productrating0; $i++) {
                            $star_text .= '<i class="fa fa-star"></i>';
                          }
                          $star_text .= '<i class="fa fa-star-half"></i>';
                        }
                        else {
                          for ($i = 0; $i < $productrating0; $i++) {
                            $star_text .= '<i class="fa fa-star"></i> ';
                          }
                        }
                        echo '<div id="product-title-review" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">' . "\n";
                        echo '<meta itemprop="itemReviewed" content="' . $products_name  . '" />';
                        echo $star_text . "&nbsp;";
                        echo '<meta itemprop="ratingValue" content="' . $productrating1 . '" />';
                        echo '<a href="#content-tabs" data-tab="tab-reviews" class="tab-anchor-open"><span itemprop="ratingCount">' . $countreview . '</span> reviews</a>';
                        echo '</div><!--/#product-title-review-->' . "\n";
                      }
                    }
              ?>
            </div><!--/#grid-01-00-->

            <div class="grid-01-01">
            	<?php include(DIR_WS_MODULES . FILENAME_MORE_PICS);?>

              <div id="product-info">
                <!--<p class="product-code">Product code: <strong>GHD00001</strong></p>-->
                <?php
                  echo '                <p class="product-brand" itemscope itemtype="http://schema.org/Organization"><a href="' . tep_href_link(FILENAME_DEFAULT, 'manufacturers_id=' . $product_info['manufacturers_id']) . '" id="single-product-brand" itemprop="url"><span itemprop="brand">' . $product_info['manufacturers_name'] . '</span></a></p>' . "\n";
                ?>
              </div><!--/#product-info-->
            </div><!--/.grid-01-01-->

            <div class="grid-01-02">
            	<?php
            	  tep_db_query("update " . TABLE_PRODUCTS_DESCRIPTION . " set products_viewed = products_viewed+1 where products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and language_id = '" . (int)$languages_id . "'");

            	  echo tep_draw_form('cart_quantity', tep_href_link(FILENAME_PRODUCT_INFO, tep_get_all_get_params(array('action')) . 'action=add_product'), 'post', 'id="cart_quantity"') . "\n";

                $check_multiprice = check_pricing_categories($product_info['products_id']);
          	    $products_market_price = "";
          	    $products_price = "";
          	    $products_special_price = "";

                if ($new_price = tep_get_products_special_price($product_info['products_id'])) { //there is a special price
                  //CGDiscountSpecials start
                  if ($check_multiprice=="single") {
                  	  $products_price = $currencies->display_normal_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
                  	  $products_special_price = $currencies->display_price_nodiscount($new_price, tep_get_tax_rate($product_info['products_tax_class_id']));
                      if (tep_not_null($product_info['products_market_price']) && $product_info['products_market_price'] > $product_info['products_price']) {
                        $products_market_price = $currencies->display_normal_price($product_info['products_market_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
                      }
                  }
                  else {
                    $products_special_price = 'Offer';
                  }
                  //CGDiscountSpecials end
                  $saving_from_price = $currencies->display_price_nodiscount(($product_info['products_price'] - $new_price), tep_get_tax_rate($product_info['products_tax_class_id']));
                }
                else {
                  if ($check_multiprice=="single") {
                  	$products_price = $currencies->display_price($product_info['products_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
                    if (tep_not_null($product_info['products_market_price']) && $product_info['products_market_price'] > $product_info['products_price']) {
                    	$products_market_price = $currencies->display_normal_price($product_info['products_market_price'], tep_get_tax_rate($product_info['products_tax_class_id']));
                    }
                  }
                }

                echo '<div id="product-price" itemscope itemprop="offers" itemtype="http://schema.org/Offer">' . "\n";
                echo '  <meta itemprop="pricecurrency" content="GBP" />' . "\n";
                echo '  <meta itemprop="itemCondition" itemtype="http://schema.org/OfferItemCondition" content="http://schema.org/NewCondition" />' . "\n";
                if ((int)$product_info['products_quantity']>0 || (int)$product_info['products_bo']>0) {
                  echo '  <meta itemprop="availability" content="http://schema.org/InStock" />';
                } else {
                  echo '  <meta itemprop="availability" content="http://schema.org/OutOfStock" />';
                }

                if ($check_multiprice!="single") {
	                echo '  <span class="price-now" id="mprice"></span>' . "\n";
                }
                else {
                	$our_price = $products_price;
                	if (tep_not_null($products_special_price)) {
                    echo '  <span class="price-was">Was: <s>' . $products_price . '</s> <span class="price-icon price-save">Save ' . $saving_from_price . '</span></span>' . "\n";//<a href="#" class="price-icon price-offer">3 FOR 2</a>
                    $our_price = $products_special_price;
                  } elseif (tep_not_null($products_market_price)) {
                    echo '  <span class="price-was">Was ' . $products_market_price . ' </span>';
                  }
                  if ($product_info['products_free_shipping']>0) echo '<span class="price-freedelivery"><i class="fa fa-truck"></i> Free Delivery</span>' . "\n";
                  echo '  <div class="clearme"></div><span class="price-now" itemprop="price">' . $our_price . '</span>' . "\n";
                }
                echo '</div><!--/#product-price-->' . "\n";

                          //attributes or variants
                          $products_attributes_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_ATTRIBUTES . " patrib where patrib.products_id='" . (int)$HTTP_GET_VARS['products_id'] . "' and patrib.options_id = popt.products_options_id and popt.language_id = '" . (int)$languages_id . "'");
                          $products_attributes = tep_db_fetch_array($products_attributes_query);
                          if ($products_attributes['total'] > 0) {
                          	echo '                    <div id="single-product-att">' . "\n";
                            //if (is_sequenced_dropdowns($HTTP_GET_VARS['products_id'])) {
                                 // QT Pro: Begin Changed code
                                 $products_id=(preg_match("/^\d{1,10}(\{\d{1,10}\}\d{1,10})*$/",$HTTP_GET_VARS['products_id']) ? $HTTP_GET_VARS['products_id'] : (int)$HTTP_GET_VARS['products_id']);
                                 require(DIR_WS_CLASSES . 'addon_classes/' . 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN . '.php');
                                 $class = 'pad_' . PRODINFO_ATTRIBUTE_PLUGIN;
                                 $pad = new $class($products_id);
                                 echo $pad->draw();
                                 // QT Pro: End Changed Code
                            //}
                            echo '                    </div>' . "\n";
                          }
                          else {
                          	require_once('pv.php');
                          }
            	?>
                        <?php
                          if (!$freeproductsoffer_nocart) {
                            echo tep_draw_hidden_field('products_id', $product_info['products_id']);
                        ?>

                        <div id="product-options">
                            <div id="product-quantity">
                            	<div class="product-option-title">
                                <h2><span class="current-selection current-quantity"></span></h2>
                              </div><!--/.product-option-title-->

                              <div class="product-availability">
                                <p class="qty">QTY&nbsp;&nbsp;</p>
                              </div><!--/#product-availability-->
                              <div class="select-wrap select-wrap-quantity">
                              	<?php
                              	  $the_quantity=array(array('id'=>1,'text' => '1'),array('id'=>2,'text' => '2'),array('id'=>3,'text' => '3'),array('id'=>4,'text' => '4'),array('id'=>5,'text' => '5'),array('id'=>6,'text' => '6'),array('id'=>7,'text' => '7'),array('id'=>8,'text' => '8'));
                              	  echo tep_draw_pull_down_menu('input_quantity', $the_quantity, '', 'id="product-quantity-select"');?>
                    			    </div><!--/.select-wrap .select-wrap-quantity-->

                              <div class="product-availability">
                              	<!-- <p>Availability: <strong id="stock-low">Low in stock <i class="fa fa-clock-o"></i></strong></p> -->
                                <!-- <p>Availability: <strong id="stock-no">Not in stock</strong></p> -->
                                <p class="availability"><?php echo is_backordered();?></p>
                                <!--<p class="is">Availability: <strong id="stock-yes">In Stock</strong></p>-->
                                <p class="btos hideme" id="btos">Currently out of stock</p>
                              </div><!--/#product-availability-->

                              <div class="clearme"></div>
                            </div><!--/#product-quantity-->
                        </div><!--/#product-options-->


                          <div id="product-checkout">
                        	  <ul>
                            	<li><a href="#" class="cta-buy" id="button_buynow"><span>ADD TO CART</span></a></li>
                              <li id="time-ndd">
                                <div id="time-ndd-container">
                                    <span class="time-append">Order before</span>
                                    <span class="time time-hour"> 3pm (GMT) </span>
                                    <span class="time-prepend">for next day delivery</span>
                                </div><!--/#time-ndd-container-->
                              </li><!--/#time-ndd-->
                              <?php
                                if ($is_coloursample) {
                              ?>
                              <li><a href="#" class="cta-buy-sample" id="ordersample"><span><i class="fa fa-barcode"></i>&nbsp;&nbsp;ORDER SAMPLE</span></a><span class="cta-buy-sample-text"><a href="info/info_sample.php" data-featherlight-variant="flwidth" data-featherlight="ajax">About colour sample</a></span>
                              </li>
                              <?php
                                }
                              ?>
                              <!--<li><a href="#colour-match"><span>?</span></a></li>-->

                            <div class="clearme"></div>
                            <div class="added-to-cart" id="add_to_cart">
                              <div class="ainner">
                  		          <h3 id="atitle"></h3>
                                <h5 id="aname"></h5>
                  			        <p id="amodel"></p>
                  			        <p id="aattribute"></p>
                                <p id="aqty"></p><br />

                                <div class="add-to-cart-buttons">
                            	    <a href="<?php echo tep_href_link(FILENAME_SHOPPING_CART);?>" class="bt" id="aviewcart"><span>View Cart</span></a> &nbsp; <a href="<?php echo tep_href_link(FILENAME_CHECKOUT, '', 'SSL');?>" class="bt" id="acheckout"><span>Checkout</span></a>
                        	      </div><!--/.add-to-cart-buttons-->
                              </div><!--/.ainner-->
                            </div><!--/.added-to-cart-->
                            <div class="clearme"></div>
                          </div><!--/#product-checkout-->
                        <?php
                          }
                        ?>


            	<?php echo '                    </form>' . "\n";?>

                        <div class="content-tabs content-tabs-01" id="content-tabs">
                        	<ul class="content-tabs-nav">
                            <li><a href="#" data-tab="content-tab-01" id="tab-details" class="active">Details</a></li>
                            <li><a href="#" data-tab="content-tab-02" id="tab-shipping">Shipping & Returns</a></li>
                            <li><a href="#" data-tab="content-tab-04" id="tab-askaquestion">Ask a question</a></li>
                            <li><a href="#" data-tab="content-tab-03" id="tab-reviews">Reviews<?php echo ($countreview>0) ? ' <span id="reviews-number">(' . $countreview . ')</span>' : "";?></a></li>
                          </ul><!--/.content-tab-nav-->

                          <div class="clearme"></div>

							            <h2 class="content-accordion-title" data-tab="content-tab-01">Product Details</h2>
                          <div id="content-tab-01" class="content-tabs-content active" itemprop="description">
                          <?php
                            if (tep_not_null($products_model)) {
                              echo '  <strong>' . $products_model . '</strong><br />' . "\n";
                            }
                            echo "<p>" . stripslashes($products_desc) . "</p>";
                          ?>
                          </div><!--/#content-tab-01-->


							            <h2 class="content-accordion-title" data-tab="content-tab-02">Shipping & Returns</h2>
                          <div id="content-tab-02" class="content-tabs-content">
                            <div id="product_shipping"></div>
                          </div><!--/#content-tab-02-->


							            <h2 class="content-accordion-title" data-tab="content-tab-04">Ask a question</h2>
                          <div id="content-tab-04" class="content-tabs-content">
                            <div id="ask_a_question"><?php include(DIR_WS_BOXES . 'ask_a_question.php');?></div>
                          </div><!--/#content-tab-04-->


							            <h2 class="content-accordion-title" data-tab="content-tab-03">Reviews <?php echo ($countreview>0) ? ' <span id="reviews-number">(' . $countreview . ')</span>' : "";?></h2>
                          <div id="content-tab-03" class="content-tabs-content">
                            <?php include(DIR_WS_BOXES . 'reviews.php');?>
                          </div><!--/#content-tab-03-->

                        </div><!--/.content-tabs-->

                        <div>
                          <!--FB OG-->
                          <meta property="og:title" content="<?php echo PRODUCTTITLE; ?>" />
                          <meta property="og:type" content="product" />
                          <meta property="og:url" content="<?php echo $_canonicalUrl;?>" />
                          <?php
                          //$product_images_forfb = tep_db_fetch_array(tep_db_query("select image_filename from " . TABLE_PRODUCTS_IMAGES . " where products_id = '" . (int)$_GET['products_id'] . "' and product_page = '1'"));
                          //$products_main_image_forfb = HTTP_SERVER . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . $product_images_forfb['image_filename'];
                          ?>
                          <meta property="og:image" content="<?php echo $products_main_image_forfb;?>" />
                          <meta property="og:site_name" content="" />
                          <meta property="fb:admins" content="1222057982" />
                          <meta property="og:description" content="<?php echo strip_tags($products_desc); ?>" />

                          <!--TW CARDS-->
                          <meta name="twitter:card" content="product" />
                          <meta name="twitter:site" content="@" />
                          <meta name="twitter:title" content="<?php echo PRODUCTTITLE; ?>" />
                          <meta name="twitter:description" content="<?php echo PRODUCTS_METADESCRIPTION; ?>" />
                          <meta name="twitter:creator" content="@" />
                          <meta name="twitter:image" content="<?php echo $products_main_image_forfb;?>" />
                          <meta name="twitter:data1" content="&pound;5.00" />
                          <meta name="twitter:label1" content="Price" /> 
                        </div>
            </div><!--/.grid-01-02-->
            <div class="clearme"></div>

      		</div>

      	<?php
      		  include(DIR_WS_BOXES . FILENAME_RECENTLY_VIEWED);
          }
        ?>
        
      	</div><!--/.inner-->
      </div><!--/#container-->



















      <!-- footer -->
      <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
      <!-- footer end-->

      <div class="off-canvas" id="colour-match">
        <div class="off-canvas-inner">
          <h3>FREE Colour Match</h3>
          <p><strong>Not sure which colour is best for you?</strong></p>
          <p>We understand it may be difficult to choose the correct shade when purchasing hair extensions online.</p>
          <p>We are here to help you out!</p>
          <p>
            <?php
              if ($is_coloursample) {
              	echo '&bull; <a href="#" id="sample-query">Order Colour Sample</a>';
              	echo '<div class="desctext">Please select the desired colour/length and click the Order Sample button</div>' . "\n";
              }
              echo '&bull; <a href="' .  tep_href_link(FILENAME_CUSTOMER_SERVICES, 'cat=2#2-03') . '" target="_blank">Colour Match Services</a><br />' . "\n";
              echo '&bull; <a href="' .  tep_href_link(FILENAME_PRODUCT_INFO, 'products_id=183') . '" target="_blank">Colour Ring</a><br />' . "\n";
              //echo '&bull; <a href="' .  tep_href_link(FILENAME_CUSTOMER_SERVICES, 'cat=2#2-03') . '">Colour Match via post</a><br />' . "\n";
            ?>
          </p>
        </div><!--/.off-canvas-inner-->
      </div><!--/.off-canvas #off-canvas-right-->

  </body>

</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>