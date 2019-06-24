<?php
/**
 * @component CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined( '_JEXEC' ) or die( 'Restricted access' );

if (!class_exists ('vmPSPlugin'))  require(JPATH_VM_PLUGINS . '/vmpsplugin.php');

class plgVmPaymentCmCoupon extends vmPSPlugin {

	function __construct(& $subject, $config) { 
		parent::__construct ($subject, $config); 
		$varsToPush = array(
			'payment_logos' => array('', 'char'),
			'status_success' => array('', 'char'),
		);
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	}

	public function getVmPluginCreateTableSQL() {

		return;
	}
	
	public function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrency){
		if ( JFactory::getApplication()->isAdmin() ) {
			return null;
		}
	  
		// call once per session
		static $is_called = false;
		if($is_called) {
			return null;
		}
		$is_called = true;

		if ( ! class_exists( 'cmcoupon' ) ) {
			if ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
				return null;
			}
			require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
		}
		if ( ! class_exists( 'cmcoupon' ) ) {
			return null;
		}
		CmCoupon::instance();
		AC()->init();
		return AC()->storediscount->cart_coupon_validate_auto();
	}
	
	
	
	
	
	function plgVmConfirmedOrder($cart, $order) {

		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		$session = JFactory::getSession();
		$return_context = $session->getId();
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . '/models/orders.php');
		}
		if (!class_exists('VirtueMartModelCurrency')) {
			require(JPATH_VM_ADMINISTRATOR . '/models/currency.php');
		}

		$address = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);

		if (!class_exists('TableVendors')) {
			require(JPATH_VM_ADMINISTRATOR . '/tables/vendors.php');
		}
		$vendorModel = VmModel::getModel('Vendor');
		$vendorModel->setId(1);
		$vendor = $vendorModel->getVendor();
		$vendorModel->addImages($vendor, 1);
		$this->getPaymentCurrency($method);
		$email_currency = $this->getEmailCurrency($method);
		$currency_code_3 = shopFunctions::getCurrencyByID($method->payment_currency, 'currency_code_3');

		$paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
		$totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, FALSE), 2);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
		if ($totalInPaymentCurrency > 0) {
			vmInfo(JText::_('VMPAYMENT_PAYPAL_PAYMENT_AMOUNT_INCORRECT'));
			return FALSE;
		}
     
		$payment_name = $this->renderPluginName($method);
		$html = '<table>' . "\n";
		$html .= $this->getHtmlRow('STANDARD_PAYMENT_INFO', $payment_name);
		if (!empty($payment_info)) {
			$lang = & JFactory::getLanguage();
			if ($lang->hasKey($method->payment_info)) {
				$payment_info = JText::_($method->payment_info);
			} else {
				$payment_info = $method->payment_info;
			}
			$html .= $this->getHtmlRow('STANDARD_PAYMENTINFO', $payment_info);
		}
		$currency = CurrencyDisplay::getInstance('', $order['details']['BT']->virtuemart_vendor_id);
		$html .= $this->getHtmlRow('STANDARD_ORDER_NUMBER', $order['details']['BT']->order_number);
		$html .= $this->getHtmlRow('STANDARD_AMOUNT', $currency->priceDisplay($order['details']['BT']->order_total));
		$html .= '</table>' . "\n";
            
		//return $this->processConfirmedOrderPaymentResponse(true, $cart, $order, $html, $dbValues['payment_name'],'C');
		$modelOrder = VmModel::getModel('orders');
		$order['order_status'] = $method->status_success;
		$order['customer_notified'] = 1;
		$order['comments'] = '';
		$modelOrder->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, true);
		$order['paymentName']= $payment_name;
		//We delete the old stuff
		$cart->emptyCart();
		version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->setVar( 'html', $html ) : JRequest::setVar('html', $html);
		return false;

	}

	protected function checkConditions($cart, $method, $cart_prices) {
		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		require_once(JPATH_VM_ADMINISTRATOR . '/version.php'); 
		$vmversion = VmVersion::$RELEASE;	
		if(preg_match('/\d/',substr($vmversion,-1))==false) $vmversion = substr($vmversion,0,-1);
		$negative_multiplier = version_compare($vmversion, '2.0.21', '>=') ? -1 : 1;

		$p = isset($cart_prices['salesPrice']) ? $cart_prices : $cart->pricesUnformatted;
		
		if ( ! class_exists( 'cmcoupon' ) ) {
			if ( file_exists( JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php' ) ) {
				require JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/cmcoupon.php';
				if ( class_exists( 'cmcoupon' ) ) {
					CmCoupon::instance();
					AC()->init();
					if ( (int) AC()->param->get('virtuemart_inject_totals',0)==1) {
					// get cmcoupon discount since discount not available in virtuemart object at this point
						$coupon_discount = 0;
						$coupon_row = AC()->storediscount->get_coupon_session();
						if ( ! empty( $coupon_row ) ) {
							$coupon_discount = $coupon_row->product_discount + $coupon_row->shipping_discount;
						}
						@$p['salesPriceCoupon'] = $negative_multiplier * $coupon_discount;
					}
				}
			}
		}

		$amount = round($p['withTax'] + $p['salesPriceShipment'] - (@$p['salesPriceCoupon']*$negative_multiplier) + @$p['salesPricePayment'],2);
		if($amount>0) return false;
		
		return true;
	}
	








































	/**
	 * @param $virtuemart_paymentmethod_id
	 * @param $paymentCurrencyId
	 * @return bool|null
	 */
	function plgVmgetEmailCurrency($virtuemart_paymentmethod_id, $virtuemart_order_id, &$emailCurrencyId) {
		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		return '';
		

	}

	/**
	 * @param $html
	 * @return bool|null|string
	 */
	function plgVmOnPaymentResponseReceived(&$html) {
		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		$virtuemart_paymentmethod_id = version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->getInt( 'pm', 0 ) : JRequest::getInt('pm', 0);
		$order_number = version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->getString( 'on', 0 ) : JRequest::getString('on', 0);

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return NULL;
		}

		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}

		$payment_name = $this->renderPluginName($method);
		//We delete the old stuff
		// get the correct cart / session
		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();
		return TRUE;
	}

	/**
	 * @return bool|null
	 */
	function plgVmOnUserPaymentCancel() {
		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . '/models/orders.php');
		}

		$order_number = version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->getString( 'on', 0 ) : JRequest::getString('on', '');
		$virtuemart_paymentmethod_id = version_compare( JVERSION, '2.5', '>=' ) ? JFactory::getApplication()->input->getInt( 'pm', 0 ) : JRequest::getInt('pm', '');
		if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
			return NULL;
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
			return NULL;
		}

		return TRUE;
	}



	/**
	 * Display stored payment data for an order
	 *
	 * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
	 */
	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		if ( version_compare( JVERSION, '4.0.0', '>=' ) ) {
			return;
		}

		if (!$this->selectedThisByMethodId($payment_method_id)) {
			return NULL; // Another method was selected, do nothing
		}

		return '';
	}





	/**
	 * @param VirtueMartCart $cart
	 * @param                $method
	 * @param                $cart_prices
	 * @return int
	 */
	function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
		return 0;
	}


	/**
	 * We must reimplement this triggers for joomla 1.7
	 */

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {

		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valérie isaksen
	 *
	 * @param VirtueMartCart $cart: the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {

		return $this->OnSelectCheck($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object $cart Cart object
	 * @param integer $selected ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		return $this->displayListFE($cart, $selected, $htmlIn);
	}


	/**
	 * @param VirtueMartCart $cart
	 * @param array $cart_prices
	 * @param                $cart_prices_name
	 * @return bool|null
	 */
	public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}

	//function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method) {
	function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method, $progressive = true) {

		return 0;
	}

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {

		//$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}


	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment($order_number, $method_id) {

		return $this->onShowOrderPrint($order_number, $method_id);
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}

	function plgVmDeclarePluginParamsPayment($name, $id, &$data) {

		return $this->declarePluginParams('payment', $name, $id, $data);
	}

	/**
	 * @param $name
	 * @param $id
	 * @param $table
	 * @return bool
	 */
	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {

		return $this->setOnTablePluginParams($name, $id, $table);
	}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
