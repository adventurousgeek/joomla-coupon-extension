<?php
/**
 * CmCoupon
 *
 * @package Joomla CmCoupon
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @license : GNU/GPL
 * @Website : http://cmdev.com
 **/

defined('_JEXEC') or die('Restricted access');
if ( ! defined( '_CM_' ) ) {
	exit;
}

class Cmcoupon_Helper_Estore_Virtuemart1_Installation {

	var $estore = 'virtuemart1';
	
	public function __construct() {
	}

	public function is_installation() {
		return true;
	}

	public function get_definition_file() {
		return	array(

			//'discount_core'=>	array('func'=>'inject_admin_class_discount',	'index'=>'onBeforeCouponLoad',		'name'=>'COM_CMCOUPON_FI_MSG_CORE_REQUIRED','file'=>'www/administrator/components/com_hikashop/classes/discount.php','desc'=>''),
			//'cart_core'=> 		array('func'=>'inject_admin_class_cart',		'index'=>'onAfterCartShippingLoad',	'name'=>'COM_CMCOUPON_FI_MSG_CORE_REQUIRED','file'=>'www/administrator/components/com_hikashop/classes/cart.php','desc'=>''),

			'ps_coupon_core1' => array(
				'func' => 'inject_ps_coupon_core',
				'index' => 'process',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_coupon.php',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'desc' => '',
			),
			'ps_coupon_core2' => array(
				'func' => 'inject_ps_coupon_core',
				'index' => 'remove',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_coupon.php',
				'name' => AC()->lang->__( 'Core (Required)' ),
				'desc' => '',
			),
			'automatic_coupon' => array(
				'func' => 'inject_html_basket',
				'index' => 'autocoupon',
				'file' => 'www/administrator/components/com_virtuemart/html/basket.php',
				'name' => AC()->lang->__( 'Add automatic coupon functionality' ),
				'desc' => '',
			),
			'couponField' => array(
				'func' => 'inject_couponField',
				'index' => 'hidden',
				'file' => 'www/components/com_virtuemart/themes/[template_name]/templates/common/couponField.tpl.php',
				'name' => AC()->lang->__( 'Add shipping fields to coupon form' ),
				'desc' => '',
			),
			'ps_checkout_order_number' => array(
				'func' => 'inject_ps_checkout',
				'index' => 'ordernum',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_checkout.php',
				'name' => AC()->lang->__( 'Order number in checkout' ),
				'desc' => '',
			),
			'ps_checkout_shipping' => array(
				'func' => 'inject_ps_checkout',
				'index' => 'shipping',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_checkout.php',
				'name' => AC()->lang->__( 'Call CmCoupon on selection of shipping' ),
				'desc' => '',
			),
			'ps_checkout_paymentmethod' => array(
				'func' => 'inject_ps_checkout',
				'index' => 'payment',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_checkout.php',
				'name' => AC()->lang->__( 'Call CmCoupon on selection of payment method' ),
				'desc' => '',
			),
			'basket_shipping' => array(
				'func' => 'inject_html_basket',
				'index' => 'shipbasket',
				'file' => 'www/administrator/components/com_virtuemart/html/basket.php',
				'name' => AC()->lang->__( 'Display correct shipping in basket' ),
				'desc' => '',
			),
			'basket_coupon_display_shipping' => array(
				'func' => 'inject_html_basket',
				'index' => 'shipcoupon',
				'file' => 'www/administrator/components/com_virtuemart/html/basket.php',
				'name' => AC()->lang->__( 'Show shipping discount in coupon field 1' ),
				'desc' => '',
			),
			'basket_coupon_display_shipping_ro' => array(
				'func' => 'inject_html_ro_basket',
				'index' => 'shipcoupon',
				'file' => 'www/administrator/components/com_virtuemart/html/ro_basket.php',
				'name' => AC()->lang->__( 'Show shipping discount in coupon field 2' ),
				'desc' => '',
			),
			'html_checkout_index_shipping' => array(
				'func' => 'inject_html_checkout_index',
				'index' => 'shipget',
				'file' => 'www/administrator/components/com_virtuemart/html/checkout.index.php',
				'name' => AC()->lang->__( 'Retrieve calculated shipping from CmCoupon' ),
				'desc' => '',
			),
			'ps_order' => array(
				'func' => 'inject_ps_order',
				'index' => 'orderstatustrigger',
				'file' => 'www/administrator/components/com_virtuemart/classes/ps_order.php',
				'name' => AC()->lang->__( 'Sellable gift certificates' ),
				'desc' => '',
			),
			'html_basket_coupon_field_persist' => array(
				'func' => 'inject_html_basket',
				'index' => 'persist',
				'file' => 'www/administrator/components/com_virtuemart/html/basket.php',
				'name' => AC()->lang->__( 'Persistent coupon field' ),
				'desc' => '',
			),
		);
	}	

	public function get_definition_sql() {
		return array(
			'db_vm1ids' => array(
				'func' => 'database_vm1ids',
				'name' => AC()->lang->__( 'Database tables to create ids needed' ),
				'desc' => '',
			),
		);
	}
	
	public function get_definition_plugin() {
		return array(
			'virtuemart' => array(
				'func' => 'plugin_installer',
				'name' => 'Virtuemart - CmCoupon',
				'folder' => 'virtuemart',
				'dir' => JPATH_ADMINISTRATOR . '/components/com_cmcoupon/helper/estore/virtuemart1/extensions/plugins/virtuemart',
				'desc' => '',
			),
		);
	}

	public function database_vm1ids( $type ) {

		$rtn = false;
		switch( $type ) {
			case 'install': {
				$ret = AC()->db->query('
					CREATE TABLE IF NOT EXISTS `#__cmcoupon_vm1ids` (
						id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						type ENUM("order_number","shipping_rate_id") NOT NULL,
						value VARCHAR(255) NOT NULL,
						field1 VARCHAR(255),
						field2 VARCHAR(255)
					)
				');
				if ( $ret === false ) {
					return new Exception( AC()->db->get_errormsg() );
				}
				$rtn = true;
				break;
			}
			case 'check': {
				$config = JFactory::getConfig();
				$p__ = $config->{version_compare( JVERSION, '1.6.0', 'ge' ) ? 'get' : 'getValue'} ( 'dbprefix' );
				JFactory::getDBO()->setQuery( 'SHOW TABLES' );
				$tables = JFactory::getDBO()->loadRowList();
				foreach( $tables as $table ) {
					if ( strcasecmp( $table[0], $p__ . 'cmcoupon_vm1ids') == 0 ) {
						$rtn = true;
						break;
					}
				}
				break;
			}
			case 'uninstall': {
				AC()->db->query( 'DROP TABLE IF EXISTS #__cmcoupon_vm1ids' );
				if ( $ret === false ) {
					return new Exception( AC()->db->get_errormsg() );
				}
				$rtn = true;
				break;
			}
		}

		return $rtn;
	}

	public function plugin_installer( $type, $key ) {
		$install_class = AC()->helper->new_class( 'CmCoupon_Helper_Installation' );
		return $install_class->plugin_installer( $type, $key, $this->get_definition_plugin() );
	}

	public function inject_ps_coupon_core( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'process'=>'/(function\s+process_coupon_code\s*\(\s*[$]d\s*\)\s*[{])/i',
						'remove'=>'/(function\s+remove_coupon_code\s*\(\s*[&][$]d\s*\)\s*[{])/i',
					),
					'replacements' => array(
						'process'=>'$1
		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'virtuemart\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onCouponProcess\', array($d));
		if(!empty($returnValues)){
			foreach ($returnValues as $returnValue) {
				if ($returnValue !== null  ) {
					return $returnValue;
				}
			}
		}
		# cmcoupon_code END =================================================================',
						'remove'=>'$1
		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'virtuemart\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onCouponRemove\', array($d));
		# cmcoupon_code END =================================================================',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'process'=>'/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'virtuemart\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$returnValues\s*\=\s*\$dispatcher\->trigger\s*\(\s*\'onCouponProcess\'\s*,\s*array\s*\(\s*\$d\s*\)\s*\)\s*;\s*'.
									'if\s*\(\s*\!empty\s*\(\s*\$returnValues\s*\)\s*\)\s*{\s*'.
										'foreach\s*\(\s*\$returnValues\s+as\s+\$returnValue\s*\)\s*{\s*'.
											'if\s*\(\s*\$returnValue\s*\!\=\=\s*null\s*\)\s*{\s*'.
												'return\s*\$returnValue\s*;\s*'.
											'}\s*'.
										'}\s*'.
									'}\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
						'remove'=>'/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'virtuemart\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$returnValues\s*\=\s*\$dispatcher\->trigger\s*\(\s*\'onCouponRemove\'\s*,\s*array\s*\(\s*\$d\s*\)\s*\);\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'process'=> '',
						'remove'=> '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/classes/ps_coupon.php',
			'vars' => $vars,
		);

	}

	public function inject_couponField( $type ) {

		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'hidden' => '/(<form .+?coupon_code)(.+?)(\s+<\/form>)/is',
					),
					'replacements' => array(
						'hidden'=>'$1$2
<?php 
# cmcoupon_code START ===============================================================
echo \'	<input type="hidden" name="ship_to_info_id" value="\'.vmGet( $_REQUEST, \'ship_to_info_id\').\'" />
		<input type="hidden" name="shipping_rate_id" value="\'.urldecode(vmGet( $_REQUEST, "shipping_rate_id", null )).\'" />
		<input type="hidden" name="payment_method_id" value="\'.vmGet( $_REQUEST, \'payment_method_id\').\'" />\';
$current_stage = ps_checkout::get_current_stage();
$checkout_steps = ps_checkout::get_checkout_steps();
if(!empty($checkout_steps[$current_stage-1])) {
	foreach( $checkout_steps[$current_stage-1] as $this_step ) echo \'<input type="hidden" name="checkout_this_step[]" value="\'.$this_step.\'" />\';
}
# cmcoupon_code END =================================================================
?>
$3',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {
				$vars = array(
					'patterns' => array(
						'hidden' => '/<\?php\s*'.
'# cmcoupon_code START ===============================================================\s*'.
'echo \'\s*<input type="hidden" name="ship_to_info_id" value="\'[.]vmGet\( \$_REQUEST, \'ship_to_info_id\'\)[.]\'" \/>\s*'.
		'<input type="hidden" name="shipping_rate_id" value="\'[.]urldecode\(vmGet\( \$_REQUEST, "shipping_rate_id", null \)\)[.]\'" \/>\s*'.
		'<input type="hidden" name="payment_method_id" value="\'[.]vmGet\( \$_REQUEST, \'payment_method_id\'\)[.]\'" \/>\';\s*'.
'\$current_stage = ps_checkout::get_current_stage\(\);\s*'.
'\$checkout_steps = ps_checkout::get_checkout_steps\(\);\s*'.
'if\(!empty\(\$checkout_steps\[\$current_stage-1\]\)\) {\s*'.
	'foreach\( \$checkout_steps\[\$current_stage-1\] as \$this_step \) echo \'<input type="hidden" name="checkout_this_step\[\]" value="\'[.]\$this_step[.]\'" \/>\';\s*'.
'}\s*'.
'# cmcoupon_code END =================================================================\s*'.
'\?>\n*/is',
					),
					'replacements' => array(
						'hidden'=>'',
					),
					'patterntype' =>'regex',
				);
				break;
			}
		}

		global $mosConfig_absolute_path,$mosConfig_live_site;
		
		$admin_config = JPATH_SITE . '/administrator/components/com_virtuemart/virtuemart.cfg.php';
		if ( ! file_exists( $admin_config ) ) {
			return new Exception( AC()->lang->__( 'Could not find virtuemart config file' ) );
		}
		require_once $admin_config;

		$file = '';
		if ( ! defined( 'VM_COMPONENT_NAME' ) ) {
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.cfg.php';
		}
		$path = JPATH_SITE . '/' . VM_THEMEPATH . 'templates/';
		$default_path = JPATH_SITE . '/components/' . VM_COMPONENT_NAME . '/themes/default/templates/';
		if ( is_file( $path . 'common/couponField.tpl.php' ) ) {
			$file = $path . 'common/couponField.tpl.php';
		}
		elseif( is_file( $default_path . 'common/couponField.tpl.php' ) ) {
			$file = $default_path . 'common/couponField.tpl.php';
		}

		return (object) array(
			'file' => $file,
			'vars' => $vars,
		);
	}

	public function inject_ps_checkout ( $type ) {

		switch( $type ) {
			case 'inject' : {
				//	$order_number = $this->get_order_number();
				$vars = array(
					'patterns' => array(
						'ordernum' => '/(\$order_number\s*=\s*\$this\->get_order_number\s*\(\s*\)\s*;)/i',
						'shipping' => '/(function\s+process\s*\(.+?case\s+\'CHECK_OUT_GET_SHIPPING_METHOD\'\s*:.+?)(\s+break\s*;.+?}\s*\/\/ end function process)/is',
						'payment' => '/(function\s+process\s*\(.+?case\s+\'CHECK_OUT_GET_PAYMENT_METHOD\'\s*:.+?)(\s+break\s*;.+?}\s*\/\/ end function process)/is',
					),
					'replacements' => array(
						'ordernum' => '$1' . "\r\n\t\t" . '\$d[\'order_number\'] = \$order_number; # cmcoupon_code',
						'shipping' => '$1
# cmcoupon_code START ===============================================================
//needed to check coupon code against shipping method 
//if coupon code is enter before shipping is selected
if( !empty( $_SESSION[\'coupon_code\'] )) {
	// Update the Coupon Discount !!
	require_once(CLASSPATH.\'ps_coupon.php\');
	ps_coupon::process_coupon_code($d);
	$d[\'shipping_rate_id\'] = $_REQUEST[\'shipping_rate_id\'];
}
# cmcoupon_code END =================================================================
$2',
						'payment'=>'$1
# cmcoupon_code START ===============================================================
//needed to check coupon code against payment method 
if( !empty( $_SESSION[\'coupon_code\'] )) {
	// Update the Coupon Discount !!
	require_once(CLASSPATH.\'ps_coupon.php\');
	ps_coupon::process_coupon_code($d);
}
# cmcoupon_code END =================================================================
$2',
					),
				);
				break;
			}
			case 'check':
			case 'reject' : {
				$vars = array(
					'patterns' => array(
						'ordernum' => '/\s+\$d\[\'order_number\'\] = \$order_number; # cmcoupon_code/is',
						'shipping' => '/# cmcoupon_code START ===============================================================\s+'.
'\/\/needed to check coupon code against shipping method\s+'.
'\/\/if coupon code is enter before shipping is selected\s+'.
'if\( !empty\( \$_SESSION\[\'coupon_code\'\] \)\) \{\s*'.
	'\/\/ Update the Coupon Discount !!\s*'.
	'require_once\(CLASSPATH[.]\'ps_coupon[.]php\'\);\s*'.
	'ps_coupon::process_coupon_code\(\$d\);\s*'.
	'(\$d\[\'shipping_rate_id\'\]\s*=\s*\$_REQUEST\[\'shipping_rate_id\'\];\s*)?'.
'}\s*'.
'# cmcoupon_code END =================================================================\n*/is',
						'payment'=>'/# cmcoupon_code START ===============================================================\s+'.
'\/\/needed to check coupon code against payment method\s+'.
'if\( !empty\( \$_SESSION\[\'coupon_code\'\] \)\) \{\s*'.
	'\/\/ Update the Coupon Discount !!\s*'.
	'require_once\(CLASSPATH[.]\'ps_coupon[.]php\'\);\s*'.
	'ps_coupon::process_coupon_code\(\$d\);\s*'.
'}\s*'.
'# cmcoupon_code END =================================================================\n*/is',
					),
					'replacements' => array(
						'ordernum' => '',
						'shipping' => '',
						'payment' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/classes/ps_checkout.php',
			'vars'=>$vars,
		);
	}

	public function inject_html_basket( $type ) {

		switch( $type ) {
			case 'inject' : {
				$vars = array(
					'patterns' => array(
						'shipbasket' => '/(if\s*\(\s*\!empty\s*\(\s*\$shipping_rate_id\s*\)\s*\&\&\s*\!ps_checkout\:\:noShippingMethodNecessary\s*\(\s*\)\s*\)\s*[{])/is',
						'shipcoupon' => '/(\$tpl\-\>set_vars\s*\()/is',
						'persist' => '/(if\s*\(\s*PSHOP\_COUPONS\_ENABLE\s*\=\=\s*\\\'1\\\'\s*)(\&\&\s*\!\@\$\_SESSION\s*\[\s*\\\'coupon_redeemed\\\'\s*\])(\s*\/\/\s*\&\&\s*\(\s*\$page\s*==\s*"shop.cart"\s*\)\s*\)\s*{\s*\$basket\_html\s*\.\=\s*\$tpl\s*\-\>\s*fetch\s*\(\s*\\\'common\/couponField.tpl.php\\\'\s*\)\s*;\s*\})/is',
						'autocoupon' => '/(if\s*\(\s*\!empty\s*\(\s*\$\_POST\s*\[\s*\"do\_coupon\"\s*\]\s*\)\s*\|\|\s*\(\s*in\_array\s*\(\s*strtolower\s*\(\s*\$func\s*\)\s*,\s*array\s*\(\s*\\\'cartadd\\\'\s*,\s*\\\'cartupdate\\\'\s*,\s*\\\'cartdelete\\\'\s*\)\s*\)\s*\&\&\s*\!empty\s*\(\s*\$\_SESSION\s*\[\s*\\\'coupon\_redeemed\\\'\s*\]\s*\)\s*\)\s*\)\s*\{)/is',
					),
					'replacements' => array(
						'shipbasket'=>'$1
		$vars[\'shipping_rate_id\'] = urldecode(vmGet( $_REQUEST, "shipping_rate_id", null )); # cmcoupon_code',
						'shipcoupon'=>'if(empty($_SESSION[\'coupon_discount\'])) $discount_before = $discount_after = false; # cmcoupon_code
$1',
						'persist'=>'$1/*$2*/ # cmcoupon_code COMMENT OUT$3',
						'autocoupon'=>'# cmcoupon_code START ===============================================================
	JPluginHelper::importPlugin(\'virtuemart\');
	$dispatcher = JDispatcher::getInstance();
	$dispatcher->trigger(\'onCouponProcessAuto\', array($vars));
	# cmcoupon_code END =================================================================
	$1',
					),
				);
				break;
			}
			case 'check' :
			case 'reject' : {
				$vars = array(
					'patterns' => array(
						'shipbasket' => '/\s+\$vars\s*\[\s*\'shipping_rate_id\'\s*\]\s*\=\s*urldecode\s*\(\s*vmGet\s*\(\s*\$_REQUEST\s*,\s*"shipping_rate_id"\s*,\s*null\s*\)\s*\)\s*;\s*#\s*cmcoupon_code/is',
						'shipcoupon' => '/if\(empty\(\$_SESSION\[\'coupon_discount\'\]\)\) \$discount_before = \$discount_after \= false; # cmcoupon_code\s+/is',
						'persist' => '/\/\*\s*(\&\&\s*\!\s*\@\s*\$\_SESSION\s*\[\s*\\\'coupon_redeemed\\\'\s*\])\s*\*\/\s*\#\s*cmcoupon_code\s*COMMENT\s*OUT/is',
						'autocoupon' => '/#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
									'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'virtuemart\'\s*\)\s*;\s*'.
									'\$dispatcher\s*\=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
									'\$dispatcher\->trigger\s*\(\s*\'onCouponProcessAuto\'\s*,\s*array\s*\(\s*\$vars\s*\)\s*\)\s*;\s*'.
									'#\s*cmcoupon_code\s*END\s*=================================================================\s*/is',
					),
					'replacements' => array(
						'shipbasket' => '',
						'shipcoupon' => '',
						'persist' => '$1',
						'autocoupon' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/html/basket.php',
			'vars' => $vars,
		);
	}

	public function inject_html_ro_basket( $type ) {

		switch( $type ) {
			case 'inject' : {
				$vars = array(
					'patterns' => array(
						'shipcoupon' => '/(\$order_total_display\s*=\s*\$GLOBALS\[\'CURRENCY_DISPLAY\'\]\->getFullValue\(\$order_total\);)/is'
					),
					'replacements' => array(
						'shipcoupon' => '$1
	# cmcoupon_code START ===============================================================
	if($coupon_discount_before || $coupon_discount_after) {
		$val = $_SESSION[\'coupon_discount\'] + @$_SESSION[\'coupon_cm_shipping_discount\'];
		$coupon_display = "- ".$GLOBALS[\'CURRENCY_DISPLAY\']->getFullValue( $val );
		if(empty($val)) $coupon_discount_before = $coupon_discount_after = false;
		if(!empty($_SESSION[\'coupon_cm_shipping_discount\'])) $shipping_display = $GLOBALS[\'CURRENCY_DISPLAY\']->getFullValue($shipping_total + $_SESSION[\'coupon_cm_shipping_discount\']);	
	}
	# cmcoupon_code END =================================================================',
					),
				);
				break;
			}
			case 'check' :
			case 'reject' : {
				$vars = array(
					'patterns' => array(
						'shipcoupon' => '/\s*# cmcoupon_code START ===============================================================\s*'.
	'if\(\$coupon_discount_before \|\| \$coupon_discount_after\) \{\s*'.
		'\$val = \$_SESSION\[\'coupon_discount\'\] \+ \@\$_SESSION\[\'coupon_cm_shipping_discount\'\];\s*'.
		'\$coupon_display = "- "[.]\$GLOBALS\[\'CURRENCY_DISPLAY\'\]\->getFullValue\( \$val \);\s*'.
		'if\(empty\(\$val\)\) \$coupon_discount_before = \$coupon_discount_after = false;\s*'.
		'if\(!empty\(\$_SESSION\[\'coupon_cm_shipping_discount\'\]\)\) \$shipping_display = \$GLOBALS\[\'CURRENCY_DISPLAY\'\]\->getFullValue\(\$shipping_total \+ \$_SESSION\[\'coupon_cm_shipping_discount\'\]\);\s*'.
	'}\s*'.
	'# cmcoupon_code END =================================================================/is',
					),
					'replacements' => array(
						'shipcoupon' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/html/ro_basket.php',
			'vars' => $vars,
		);
	}

	public function inject_html_checkout_index( $type ) {

		switch( $type ) {
			case 'inject' : {
				$vars = array(
					'patterns' => array(
						'shipget' => '/(\$theme\-\>set_vars\s*\()/is'
					),
					'replacements' => array(
						'shipget' => '$shipping_rate_id = urldecode(vmGet( $_REQUEST, "shipping_rate_id", null )); # cmcoupon_code
$1',
					),
				);
				break;
			}
			case 'check' :
			case 'reject' : {
				$vars = array(
					'patterns' => array(
						'shipget' => '/\$shipping_rate_id = urldecode\(vmGet\( \$_REQUEST, "shipping_rate_id", null \)\); # cmcoupon_code\s+/is',
					),
					'replacements' => array(
						'shipget' => '',
					),
				);
				break;
			}
		}

		return (object) array(
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/html/checkout.index.php',
			'vars' => $vars,
		);
	}
	
	public function inject_ps_order( $type ) {
		
		switch( $type ) {
			case 'inject': {
				$vars = array(
					'patterns' => array(
						'orderstatustrigger' => '/(\$db\s*\->\s*buildQuery\s*\(\s*\'INSERT\'\s*,\s*\'#__{vm}_order_history\'\s*,\s*\$fields\s*\)\s*;\s*\$db\s*\->\s*query\(\)\s*;)/is',
					),
					'replacements' => array(
						'orderstatustrigger' => '$1
						
		# cmcoupon_code START ===============================================================
		JPluginHelper::importPlugin(\'virtuemart\');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger(\'onOrderStatusUpdate\', array($d,$curr_order_status));
		# cmcoupon_code END =================================================================',
					),
				);
				break;
			}
			case 'check':
			case 'reject': {

				$vars = array(
					'patterns' => array(
						'orderstatustrigger' =>
										'/\s*#\s*cmcoupon_code\s*START\s*===============================================================\s*'.
										'JPluginHelper\s*::\s*importPlugin\s*\(\s*\'virtuemart\'\s*\)\s*;\s*'.
										'\$dispatcher\s*=\s*JDispatcher\s*::\s*getInstance\s*\(\s*\)\s*;\s*'.
										'\$returnValues\s*=\s*\$dispatcher\s*\->\s*trigger\s*\(\s*\'onOrderStatusUpdate\'\s*,\s*array\s*\(\s*\$d\s*,\s*\$curr_order_status\s*\)\s*\)\s*;\s*'.
										'#\s*cmcoupon_code\s*END\s*=================================================================/is',
					),
					'replacements' => array(
						'orderstatustrigger' => '',
					),
				);
				break;
			}
		}

		return (object) array( 
			'file' => JPATH_SITE . '/administrator/components/com_virtuemart/classes/ps_order.php',
			'vars' => $vars,
		);
	}


}
