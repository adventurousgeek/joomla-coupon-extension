<?php
/**
 * CmCoupon
 *
 * @package CmCoupon Global
 * @copyright Copyright (C) Seyi Cmfadeju - All rights reserved.
 * @Website : http://cmdev.com
 **/

if ( ! defined( '_CM_' ) ) {
	exit;
}

/**
 * Class
 **/
class CmCoupon_Admin_Menu {

	/**
	 * Plugin files, location of menu.
	 *
	 * @var array
	 */
	var $menu_files = array();

	/**
	 * Generate menu
	 **/
	public function process() {
		$this->define_menu();
		$this->define_plugin_menu();
		return $this->print_menu();
	}

	public function processjs() {
		return $this->define_menujs() . $this->define_plugin_menujs();
	}
	/**
	 * Add plugin file to display menu
	 *
	 * @param string $class name of the plugin.
	 * @param string $file location of the file.
	 **/
	public function add_file( $class, $file ) {
		$file = (string) $file;
		$this->menu_files[ (string) $class ] = (string) $file;
	}

	/**
	 * Define each menu item
	 **/
	public function define_menu() {

		CmCoupon::instance();
		AC()->init();

		$this->my_admin_link = CmCoupon::instance()->admin_url() . '#/cmcoupon';
		$is_balance = 1 === (int) AC()->param->get( 'enable_frontend_balance', 0 ) ? true : false;

		$include_installation = false;
		$installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . CMCOUPON_ESTORE . '_Installation' );
		if ( ! empty( $installer ) ) {
			$include_installation = $installer->is_installation();
		}

		$img_path = CMCOUPON_ASEET_URL . '/images';
		$this->menu_items = array(
			array(
				AC()->lang->__( 'CmCoupon' ),
				$this->my_admin_link . '/',
				$img_path . '/cmcoupon-small.png',
				array(
					array( AC()->lang->__( 'Dashboard' ), $this->my_admin_link . '/', $img_path . '/icon-16-home.png' ),
					array( AC()->lang->__( 'Configuration' ), $this->my_admin_link . '/config', $img_path . '/icon-16-config.png' ),
					$include_installation ? array( AC()->lang->__( 'Installation Check' ), $this->my_admin_link . '/installation', $img_path . '/icon-16-installation.png' ) : array(),
					array( AC()->lang->__( 'Subscription' ), $this->my_admin_link . '/license', $img_path . '/icon-16-license.png' ),
					array( AC()->lang->__( 'About' ), $this->my_admin_link . '/about', $img_path . '/icon-16-info.png' ),
				),
			),
			array(
				AC()->lang->__( 'Coupons' ),
				$this->my_admin_link . '/coupon',
				$img_path . '/icon-16-coupons.png',
				array(
					array( AC()->lang->__( 'New Coupon' ), $this->my_admin_link . '/coupon/edit', $img_path . '/icon-16-new.png' ),
					array( AC()->lang->__( 'Coupons' ), $this->my_admin_link . '/coupon', $img_path . '/icon-16-list.png' ),
					array( AC()->lang->__( 'Automatic Discounts' ), $this->my_admin_link . '/couponauto', $img_path . '/icon-16-auto.png' ),
					array( AC()->lang->__( 'Generate Coupons' ), $this->my_admin_link . '/coupon/generate', $img_path . '/icon-16-copy.png' ),
					array( AC()->lang->__( 'Import' ), $this->my_admin_link . '/import', $img_path . '/icon-16-import.png' ),
				),
			),
			array(
				AC()->lang->__( 'Tools' ),
				'',
				$img_path . '/icon-16-tools.png',
				array(
					array( AC()->lang->__( 'Send a Voucher' ), $this->my_admin_link . '/emailcoupon', $img_path . '/icon-16-mail.png' ),
					array( '--separator--' ),
					$is_balance ? array( AC()->lang->__( 'Add Gift Certificate to Balance' ), $this->my_admin_link . '/storecredit/edit', $img_path . '/icon-16-new.png' ) : array(),
					$is_balance ? array( AC()->lang->__( 'Customer Balance' ), $this->my_admin_link . '/storecredit', $img_path . '/icon-16-bank.png' ) : array(),
					$is_balance ? array( '--separator--' ) : array(),
					array( AC()->lang->__( 'New Gift Certificate Product' ), $this->my_admin_link . '/giftcert/edit', $img_path . '/icon-16-new.png ' ),
					array( AC()->lang->__( 'Gift Certificate Products' ), $this->my_admin_link . '/giftcert', $img_path . '/icon-16-giftcert.png' ),
					array( AC()->lang->__( 'Codes' ), $this->my_admin_link . '/giftcertcode', $img_path . '/icon-16-import.png' ),
					array( '--separator--' ),
					array( AC()->lang->__( 'New Email Template' ), $this->my_admin_link . '/profile/edit', $img_path . '/icon-16-new.png' ),
					array( AC()->lang->__( 'Email Templates' ), $this->my_admin_link . '/profile', $img_path . '/icon-16-profile.png' ),
					array( '--separator--' ),
					array( AC()->lang->__( 'Reports' ), $this->my_admin_link . '/report', $img_path . '/icon-16-report.png' ),
				),
			),
			array(
				AC()->lang->__( 'History of Uses' ),
				$this->my_admin_link . '/history',
				$img_path . '/icon-16-history.png',
				array(
					array( AC()->lang->__( 'Coupons' ), $this->my_admin_link . '/history', $img_path . '/icon-16-coupons.png' ),
					array( AC()->lang->__( 'Gift Certificates' ), $this->my_admin_link . '/history/giftcert', $img_path . '/icon-16-giftcert.png' ),
					array( AC()->lang->__( 'Orders' ), $this->my_admin_link . '/history/order', $img_path . '/icon-16-cart.png' ),
				),
			),
		);
	}

	public function define_menujs() {
		CmCoupon::instance();
		AC()->init();

		return '
			sammy_cmcoupon_ajax_url = "' . CmCoupon::instance()->ajax_url() . '";
			
			appsammyjs = $.sammy("#cm-main", function() {
			
				this.get("#/cmcoupon/", function(context) { gotoUrl("type=admin&view=dashboard",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/dashboard", function(context) { gotoUrl("type=admin&view=dashboard",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/about", function(context) { gotoUrl("type=admin&view=about",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/config", function(context) { gotoUrl("type=admin&view=config",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/config", function(context) { postUrl("type=admin&view=config",sammy_cmcoupon_ajax_url,context); });

				this.get("#/cmcoupon/coupon", function(context) { gotoUrl("type=admin&view=coupon",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/coupon/edit", function(context) { gotoUrl("type=admin&view=coupon&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/coupon/generate", function(context) { gotoUrl("type=admin&view=coupon&layout=generate",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/coupon", function(context) { postUrl("type=admin&view=coupon",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/coupon/edit", function(context) { postUrl("type=admin&view=coupon&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/coupon/generate", function(context,x) { postUrl("type=admin&view=coupon&layout=generate",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/couponauto", function(context) { gotoUrl("type=admin&view=couponauto",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/couponauto/edit", function(context) { gotoUrl("type=admin&view=couponauto&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/couponauto", function(context) { postUrl("type=admin&view=couponauto",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/couponauto/edit", function(context) { postUrl("type=admin&view=couponauto&layout=edit",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/giftcert", function(context) { gotoUrl("type=admin&view=giftcert",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/giftcert/edit", function(context) { gotoUrl("type=admin&view=giftcert&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/giftcert", function(context) { postUrl("type=admin&view=giftcert",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/giftcert/edit", function(context) { postUrl("type=admin&view=giftcert&layout=edit",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/giftcertcode", function(context) { gotoUrl("type=admin&view=giftcertcode",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/giftcertcode/edit", function(context) { gotoUrl("type=admin&view=giftcertcode&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/giftcertcode", function(context) { postUrl("type=admin&view=giftcertcode",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/giftcertcode/edit", function(context) { postUrl("type=admin&view=giftcertcode&layout=edit",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/history", function(context) { gotoUrl("type=admin&view=history",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/history/edit", function(context) { gotoUrl("type=admin&view=history&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/history/giftcert", function(context) { gotoUrl("type=admin&view=history&layout=giftcert",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/history/order", function(context) { gotoUrl("type=admin&view=history&layout=order",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/history", function(context) { postUrl("type=admin&view=history",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/history/edit", function(context) { postUrl("type=admin&view=history&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/history/giftcert", function(context) { postUrl("type=admin&view=history&layout=giftcert",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/history/order", function(context) { postUrl("type=admin&view=history&layout=order",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/profile", function(context) { gotoUrl("type=admin&view=profile",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/profile/edit", function(context) { gotoUrl("type=admin&view=profile&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/profile/imagemanager", function(context) { gotoUrl("type=admin&view=profile&layout=imagemanager",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/profile", function(context) { postUrl("type=admin&view=profile",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/profile/edit", function(context) { postUrl("type=admin&view=profile&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/profile/imagemanager", function(context) { postUrl("type=admin&view=profile&layout=imagemanager",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/storecredit", function(context) { gotoUrl("type=admin&view=storecredit",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/storecredit/edit", function(context) { gotoUrl("type=admin&view=storecredit&layout=edit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/storecredit", function(context) { postUrl("type=admin&view=storecredit",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/storecredit/edit", function(context) { postUrl("type=admin&view=storecredit&layout=edit",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/emailcoupon", function(context) { gotoUrl("type=admin&view=emailcoupon",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/emailcoupon", function(context) { postUrl("type=admin&view=emailcoupon",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/report", function(context) { gotoUrl("type=admin&view=report",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/report", function(context) { gotoUrl("type=admin&view=report",sammy_cmcoupon_ajax_url,context); });
				this.get("#/cmcoupon/report/run", function(context) { gotoUrl("type=admin&view=report&layout=list",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/report/run", function(context) { 
					params = context.params;
					params = Object.assign({}, params); // convert Sammy.object to normal object
					parameters = jQuery.trim(jQuery.param(params));
					window.location.hash="#/cmcoupon/report/run?"+parameters; /*postUrl("type=admin&view=report&layout=list",sammy_cmcoupon_ajax_url,context);*/ 
				});
				
				this.get("#/cmcoupon/license", function(context) { gotoUrl("type=admin&view=license",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/license", function(context) { postUrl("type=admin&view=license",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/import", function(context) { gotoUrl("type=admin&view=import",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/import", function(context) { postUrlUpload("type=admin&view=import",sammy_cmcoupon_ajax_url,context); });
				
				this.get("#/cmcoupon/upgrade", function(context) { gotoUrl("type=admin&view=upgrade",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/upgrade", function(context) { postUrl("type=admin&view=upgrade",sammy_cmcoupon_ajax_url,context); });

				this.get("#/cmcoupon/installation", function(context) { gotoUrl("type=admin&view=installation",sammy_cmcoupon_ajax_url,context); });
				this.post("#/cmcoupon/installation", function(context) { postUrl("type=admin&view=installation",sammy_cmcoupon_ajax_url,context); });
			});

		';
	}

	/**
	 * Extensions can add heir menu
	 **/
	public function define_plugin_menu() {

		$files = array();
		AC()->helper->trigger( 'getCmCouponExtensionMenu', array( & $files ) );
		if ( ! empty( $files ) && is_array( $files ) ) {
			foreach ( $files as $class => $file ) {
				$this->add_file( $class, $file );
			}
		}

		$files = $this->menu_files;
		foreach ( $files as $classname => $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				$class = new $classname();
				$this->menu_items[] = $class->define_menu();
			}
		}
	}

	public function define_plugin_menujs() {

		$js = '';
		$files = $this->menu_files;
		foreach ( $files as $classname => $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				$class = new $classname();
				if ( method_exists( $class, 'define_menujs' ) ) {
					$js .= $class->define_menujs();
				}
			}
		}
		return $js;
	}

	/**
	 * Print menu
	 **/
	private function print_menu() {

		// Set current url.
		$current_url = '';

		// Get all the urls into an array.
		$menu_urls = array();
		foreach ( $this->menu_items as $item ) {
			if ( ! empty( $item[1] ) ) {
				$menu_urls[] = $item[1];
			}
			if ( ! empty( $item[3] ) && is_array( $item[3] ) ) {
				foreach ( $item[3] as $item2 ) {
					if ( ! empty( $item2[1] ) ) {
						$menu_urls[] = $item2[1];
					}
					if ( ! empty( $item2[3] ) && is_array( $item2[3] ) ) {
						foreach ( $item2[3] as $item3 ) {
							if ( ! empty( $item3[1] ) ) {
								$menu_urls[] = $item3[1];
							}
							if ( isset( $item3[4] ) && $item3[4] === true ) {
								$current_url = $item3[1];
							}
						}
					}
					if ( empty( $current_url ) && isset( $item2[4] ) && $item2[4] === true ) {
						$current_url = $item2[1];
					}
				}
			}
			if ( empty( $current_url ) && isset( $item[4] ) && $item[4] === true ) {
				$current_url = $item[1];
			}
		}

		$store = '';
		if ( file_exists( CMCOUPON_DIR . '/helper/estore/' . CMCOUPON_ESTORE . '/logo.png' ) ) {
			$store = '<a href="' . AC()->store->get_app_link() . '" class="store"><img src="' . AC()->plugin_url() . '/helper/estore/' . CMCOUPON_ESTORE . '/logo.png">&nbsp;</a>';
		}

		// Process.
		$html_menu = '
			<div id="cmmenu">
				<div id="cmmenu_container">
					<div class="navbar">
						<div class="navbar-inner">
							<ul id="" class="nav" >
								<li>' . $store . '</li>
		';

		foreach ( $this->menu_items as $item ) {
			if ( empty( $item ) ) {
				continue;
			}
			$is_active_1 = false;
			$html_menu_2 = '';
			if ( ! empty( $item[3] ) && is_array( $item[3] ) ) {
				$html_menu_2 = '<ul class="dropdown-menu">';
				foreach ( $item[3] as $item2 ) {
					if ( empty( $item2 ) ) {
						continue;
					}
					if ( ! empty( $item2[1] ) && $current_url === $item2[1] ) {
						$is_active_1 = true;
					}
					$is_active_2 = false;
					$html_menu_3 = '';
					if ( ! empty( $item2[3] ) && is_array( $item2[3] ) ) {
						$html_menu_3 = '<ul>';
						foreach ( $item2[3] as $item3 ) {
							if ( empty( $item3 ) ) {
								continue;
							}
							if ( ! empty( $item3[1] ) && $current_url === $item3[1] ) {
								$is_active_2 = true;
							}
							$html_menu_3 .= $this->print_menu_helper( $item3, 3, $current_url ) . '</li>';
						}
						$html_menu_3 .= '</ul>';
					}
					$html_menu_2 .= $this->print_menu_helper( $item2, 2, $current_url, $is_active_2 ) . $html_menu_3 . '</li>';
				}
				$html_menu_2 .= '</ul>';
			}
			$html_menu .= $this->print_menu_helper( $item, 1, $current_url, $is_active_1 ) . $html_menu_2 . '</li>';
		}
		$refresh_html = '<div style="display:block;float:right;padding-top:10px;"><a href="javascript:refreshPage();"><img src="' . CMCOUPON_ASEET_URL . '/images/refresh.png" style="height:24px;"></a></div></div></div>';
		$html_menu .= '</ul>' . $refresh_html . '</div></div><div class="clr"></div>';

		return $html_menu;
	}

	/**
	 * Print each menu item
	 *
	 * @param array   $item menu item.
	 * @param int     $level the level.
	 * @param string  $current_url the current url.
	 * @param boolean $force_active if item is active.
	 **/
	private function print_menu_helper( $item, $level, $current_url, $force_active = false ) {
		$html = '';
		$image = '';
		$a_class = '';
		if ( ! empty( $item[2] ) ) {
			if ( 'class:' === substr( $item[2], 0, 6 ) ) {
				$a_class = substr( $item[2], 6 );
			} else {
				$image = '<img src="' . $item[2] . '" class="tmb"/>';
			}
		} else {
			$image = '<div style="display:inline-block;width:16px;">&nbsp;</div>';
		}

		$active_css = $force_active || ( ! empty( $item[1] ) && $current_url === $item[1] ) ? 'current' : '';

		$html .= '<li class="';
		if ( 1 === $level ) {
			$html .= ' dropdown ';
		} else {
			if ( '--separator--' === $item[0] ) {
				$html .= ' divider ';
			}
		}
		$html .= $active_css;
		$html .= '">';

		if ( '--separator--' !== $item[0] ) {
			$html .= '<a class="';
			$html .= '" ';
			$html .= ' href="' . ( ! empty( $item[1] ) ? $item[1] : '#' ) . '"';
			$html .= '>' . $image . ' ' . $item[0] . '</a>';
		} else {
			$html .= '<span></span>';
		}
		return $html;
	}
}

