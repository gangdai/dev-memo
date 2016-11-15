<?php
require_once('includes/application_top.php');

if (!function_exists('protxCleanUrl'))
{
	function protxCleanUrl($url)
	{
	  return str_replace('&amp;', '&', $url);
	}
}

$nojs = (isset($_REQUEST['nojs']) ? TRUE : FALSE);

if (tep_not_null($_GET['action']))
{
  $action = $_GET['action'];
	if ($action == 'process')
	{

    // Code taken from checkout_process.php to prepare the order before processing payment

      // if the customer is not logged on, redirect them to the login page
      if (!tep_session_is_registered('customer_id'))
      {
        if ($nojs)
        {
          $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT));
          tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
        }
        else
        {
          echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_LOGIN, '', 'SSL').'";</script>';
          tep_exit();
        }
      }

    // if there is nothing in the customers cart, redirect them to the shopping cart page
      if ($cart->count_contents() < 1)
      {
        if ($nojs)
        {
        	tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
        }
        else
        {
          echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL').'";</script>';
          tep_exit();
        }
      }

    // if no shipping method has been selected, redirect the customer to the shipping method selection page
      if (!tep_session_is_registered('shipping') || !tep_session_is_registered('sendto'))
      {
        if ($nojs)
        {
        	tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
        }
        else
        {
          echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_CHECKOUT, '', 'SSL').'";</script>';
          tep_exit();
        }
      }

      if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!tep_session_is_registered('payment')) )
      {
        if ($nojs)
        {
        	tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
        }
        else
        {
          echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_CHECKOUT, '', 'SSL').'";</script>';
          tep_exit();
        }
      }

    // avoid hack attempts during the checkout procedure by checking the internal cartID
      if (isset($cart->cartID) && tep_session_is_registered('cartID'))
      {
        if ($cart->cartID != $cartID) {
          if ($nojs)
          {
          	tep_redirect(tep_href_link(FILENAME_CHECKOUT, '', 'SSL'));
          }
          else
          {
            echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_CHECKOUT, '', 'SSL').'";</script>';
            tep_exit();
          }
        }
      }

    // load selected payment module
      require(DIR_WS_CLASSES . 'payment.php');
      $payment_modules = new payment($payment);

    // load the selected shipping module
      require(DIR_WS_CLASSES . 'shipping.php');
      $shipping_modules = new shipping($shipping);

      require(DIR_WS_CLASSES . 'order.php');
      $order = new order;

    // Stock Check
      $any_out_of_stock = false;
      if (STOCK_CHECK == 'true') {
/*        for ($i = 0, $n = sizeof($order->products); $i<$n; $i++) {
          if (tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
            $any_out_of_stock = true;
          }
        }
*/
    // QT Pro: Begin Changed code
    $check_stock='';
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++)
    {
      if (isset($order->products[$i]['attributes']) && is_array($order->products[$i]['attributes']))
      {
        $attributes=array();
        foreach ($order->products[$i]['attributes'] as $attribute)
        {
          $attributes[$attribute['option_id']]=$attribute['value_id'];
        }
        $check_stock[$i] = tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty'], $attributes);
      }
      else
      {
        $check_stock[$i] = tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty']);
      }
      if ($check_stock[$i])
      {
        $any_out_of_stock = true;
      }
    }
    // QT Pro: End Changed Code
        // Out of Stock
        if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($any_out_of_stock == true) ) {
          if ($nojs)
          {
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
          }
          else
          {
            echo '<script type="text/javascript"> top.location.href="'.tep_href_link(FILENAME_SHOPPING_CART).'";</script>';
            tep_exit();
          }
        }
      }

      $payment_modules->update_status();

      if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
        tep_redirect(protxCleanUrl(tep_href_link(FILENAME_CHECKOUT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL')));
      }

      require(DIR_WS_CLASSES . 'order_total.php');
      $order_total_modules = new order_total;

      $order_totals = $order_total_modules->process();
    //END CHECKOUT_PROCESS.PHP CODE

////My modification
  if (!tep_session_is_registered('m_postage') && !tep_session_is_registered('missorder')) {
    $last_order_id_query_1 = tep_db_query("SHOW TABLE STATUS from `" . DB_DATABASE . "` like '" . TABLE_ORDERS . "'");
    $last_order_id_1 = tep_db_fetch_array($last_order_id_query_1);
    $new_order_id_1 = $last_order_id_1['Auto_increment'];

    $sagepay_local_query = tep_db_query("select * from " . TABLE_PROTX_DIRECT . " where customer_id='". (int)$customer_id . "' order by txtime desc");
    if (tep_db_num_rows($sagepay_local_query)>0) {
      $sagepay_local = tep_db_fetch_array($sagepay_local_query);
      if ($sagepay_local['customer_id']==$customer_id && $sagepay_local['order_id']==$new_order_id_1 && $sagepay_local['status']=='OK') {
        echo '<script type="text/javascript">window.location.href="'.tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL').'";</script>';
        tep_exit();
      }
    }
  }

////My modification

		$response = $GLOBALS['protx_direct']->start_transaction();

		if ($response['authorised'] === FALSE)
		{
		  $msg = 'Sorry your payment could not be processed.';
		  if ($nojs)
		  {
		  	tep_redirect(protxCleanUrl(tep_href_link(FILENAME_CHECKOUT, 'payment_error=protx_direct&error='.urlencode($msg . ' (' . $response['detail'].')'), 'SSL')));
		  }
		  else
		  {
  			echo '<div class="messageStackWarning">'.$msg.'</div><h5>&nbsp;&nbsp;'.$response['detail'] . '</h5>';
		  }
		}
		elseif ($response['authorised'] === TRUE)
		{
		  tep_session_register('protx_id');
		  $_SESSION['protx_id'] = $GLOBALS['protx_direct']->protx_id;
		  if ($nojs)
		  {
		  	tep_redirect(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
		  }
		  else
		  {
		    //echo '<script type="text/javascript">top.location.href="'.tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL').'";</script>';
		    echo 'normalsuccess';
		    tep_exit();
		  }
		}
		elseif ($response['authorised'] == '3DAUTH')
		{
		  tep_session_register('protx_PAReq');
		  $_SESSION['protx_PAReq'] = $response['detail']['PAReq'];

		  tep_session_register('protx_MD');
		  $_SESSION['protx_MD'] = $response['detail']['MD'];

		  tep_session_register('protx_ACSURL');
		  $_SESSION['protx_ACSURL'] = $response['detail']['ACSURL'];

		  if ($nojs)
		  {
		    tep_redirect(protxCleanUrl(tep_href_link(FILENAME_PROTX_PROCESS, 'nojs&action=iframe&termurl='.urlencode(tep_href_link(FILENAME_PROTX_PROCESS, 'nojs&action=3Dreturn&protx_id='.$GLOBALS['protx_direct']->protx_id, 'SSL', true, false)), 'SSL', true, false)));
		  }
		  else
		  {
		    echo '<iframe src="'.tep_href_link(FILENAME_PROTX_PROCESS, 'action=iframe&termurl='.urlencode(str_replace('&amp;', '&', tep_href_link(FILENAME_PROTX_PROCESS, 'action=3Dreturn&protx_id='.$GLOBALS['protx_direct']->protx_id, 'SSL', true, false))), 'SSL', true, false).'" id="3Dsecure" style="width: 100%; height: 420px; border: none;"></iframe>';
		    tep_exit();
		  }
		}
	}
	elseif ($action == 'iframe')
	{
	  echo
        '<html>
          <head>
          <title>3D-Secure Validation</title>
            <script type="text/javascript">
             function OnLoadEvent() { document.getElementById(\'theform\').submit(); }
            </script>
          </head>
          <body OnLoad="OnLoadEvent();">
            <form id="theform" action="'.$_SESSION['protx_ACSURL'].'" method="POST" onsubmit="document.getElementById(\'submit_go\').disabled=\'true\';" />
              <input type="hidden" name="PaReq" value="'.$_SESSION['protx_PAReq'].'" />
              <input type="hidden" name="TermUrl" value="'.urldecode($_GET['termurl']).'" />
              <input type="hidden" name="MD" value="'.$_SESSION['protx_MD'].'" />
            <noscript>
              <center>
                <p>Your card issuer requires you to validate this transaction using Verified by Visa / MasterCard SecureCode</p>
                <p>Please click button below to be transferred to your bank\'s website to authenticate your card</p>
                <p><input type="submit" value="Go" id="submit_go" /></p>
              </center>
            </noscript>
           </form>
          </body>
        </html>';

    tep_session_unregister('protx_ACSURL');
    tep_session_unregister('protx_PAReq');
    tep_session_unregister('protx_MD');

    tep_exit();
	}
	elseif ($action == '3Dreturn')
	{
    require(DIR_WS_CLASSES . 'payment.php');
    $payment_modules = new payment($payment);

    $GLOBALS['protx_direct']->protx_id = (int)$_GET['protx_id'];
    $response = $GLOBALS['protx_direct']->do3Dreturn();

		if ($response['authorised'] === FALSE)
		{
		  $msg = 'Sorry your payment could not be processed.';
		  if ($nojs)
		  {
		    tep_redirect(protxCleanUrl(tep_href_link(FILENAME_CHECKOUT, 'payment_error=protx_direct&error='.urlencode($msg . ' (' . $response['detail'].')'), 'SSL')));
		  }
		  else
		  {
			  //echo '<strong><span style="color: red;">'.$msg.'</span></strong><br><br>'.$response['detail'];
			  echo '<p><br /><br /><br /><br /><br /></p><div class="messageStackWarning"><p>'.$msg.'</p></div><br /><p>'.$response['detail'] . ' <a href="'.tep_href_link(FILENAME_CHECKOUT, '', 'SSL').'" target="_parent">Re-Try</a></p>';
		  }
		}
		elseif ($response['authorised'] === TRUE)
		{
		  tep_session_register('protx_id');
		  $_SESSION['protx_id'] = $GLOBALS['protx_direct']->protx_id;
		  if ($nojs)
		  {
		  	tep_redirect(tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'));
		  }
		  else
		  {
		    echo '<script type="text/javascript">top.location.href="'.tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL').'";</script>';
		    tep_exit();
		  }
		}
	}
}