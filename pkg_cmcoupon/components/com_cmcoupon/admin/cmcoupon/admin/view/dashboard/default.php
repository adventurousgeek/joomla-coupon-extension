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

echo AC()->helper->render_layout( 'admin.header' );
?>

<style>
	div.icon { text-align: center; margin-right: 15px; float: left; margin-bottom: 15px; }
	#cpanel span { display: block; text-align: center; }
	#cpanel img { padding: 10px 0; margin: 0 auto; }
	#cpanel div.icon a {background-color: white;background-position: -30px;display: block;float: left;height: 97px;width: 108px;color: #565656;vertical-align: middle;text-decoration: none;border: 1px solid #CCC;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;-webkit-transition-property: background-position, -webkit-border-bottom-left-radius, -webkit-box-shadow;-moz-transition-property: background-position, -moz-border-radius-bottomleft, -moz-box-shadow;-webkit-transition-duration: 0.8s;-moz-transition-duration: 0.8s;border-top-left-radius: 5px 5px;border-top-right-radius: 5px 5px;border-bottom-right-radius: 5px 5px;border-bottom-left-radius: 5px 5px;}

	table { width: 100%; }
	table  td { border-top: #ececec 1px solid; padding: 3px 0; white-space: nowrap; }
	table tr.first td { border-top: none; }
	td.b { padding-right: 6px; text-align: right; font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, serif; font-size: 14px; }
	td.b a { font-size: 18px; }
	td.b a:hover { color: #d54e21; }
	.t { font-size: 12px; padding-right: 12px; padding-top: 6px; color: #777; }
	td.first, td.last { width: 1px; }
	.inactive { color: red; }
	.template { color: darkblue; }
	.trackbacks { color: black; }
	.waiting { color: orange;	}
	.approved { color: green; }
	
	table.licensetbl tr td:nth-child(2) { padding-left: 5px; font-weight: bold; }
</style>

<?php echo AC()->helper->render_layout( 'admin.message' ); ?>

<form action="index.php" method="post" id="adminForm" name="adminForm">
	<input type="hidden" name="option" value="com_cmcoupon" />
	<input type="hidden" name="view" value="dashboard" />
	<input type="hidden" name="cid" value="" />
	<input type="hidden" name="cid2" value="" />
	<input type="hidden" name="task" value="" />
</form>
<div id="dash_generalstats">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td width="55%" valign="top" style="border:none;">
			<div id="cpanel" class="panel postbox">					
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/coupon/edit"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-new.png" alt=""><span><?php echo AC()->lang->__( 'New Coupon' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/coupon"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/coupons.png" alt=""><span><?php echo AC()->lang->__( 'Coupons' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/giftcert"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-giftcert.png" alt=""><span><?php echo AC()->lang->__( 'Gift Certificates' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/profile"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-profile.png" alt=""><span><?php echo AC()->lang->__( 'Email Templates' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/history"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-history.png" alt=""><span><?php echo AC()->lang->__( 'History of Uses' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/report"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-report.png" alt=""><span><?php echo AC()->lang->__( 'Reports' ); ?></span></a></div></div>
				<div style="float:left;"><div class="icon"><a href="#/cmcoupon/config"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-config.png" alt=""><span><?php echo AC()->lang->__( 'Configuration' ); ?></span></a></div></div>
				<?php if ( $data->is_installation ) { ?>
					<div style="float:left;"><div class="icon"><a href="#/cmcoupon/installation"><img src="<?php echo CMCOUPON_ASEET_URL; ?>/images/icon-48-installation.png" alt=""><span><?php echo AC()->lang->__( 'Installation Check' ); ?></span></a></div></div>
				<?php } ?>
				<div style="clear"></div>
			</div>
		</td>
		<td width="45%" valign="top" style="border:none;">
			<div class="panel postbox">
				<table>
				<thead><tr><th colspan="2"><?php echo AC()->lang->__( 'General Statistics' ); ?></th></tr></thead>
				<tr class="first"><td class="first b"><?php echo $data->genstats->total; ?></td><td class="t"><?php echo AC()->lang->__( 'Total Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->active; ?></td><td class=" t approved"><?php echo AC()->lang->__( 'Active Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->inactive; ?></td><td class=" t inactive"><?php echo AC()->lang->__( 'Inactive Coupons' ); ?></td></tr>
				<tr><td class="first b"><?php echo $data->genstats->templates; ?></td><td class=" t template"><?php echo AC()->lang->__( 'Template Coupons' ); ?></td></tr>
				</table>
			</div>

			<div class="panel postbox">
				<table class="adminlist">
				<thead><tr><th colspan="2"><?php echo AC()->lang->__( 'Subscription' ); ?></th></tr></thead>
				<tbody>
				<?php if ( '' != $data->license->l ) { ?>
					<tr><td width="33%" class="first"><?php echo AC()->lang->__( 'Key' ); ?>:</td><td><?php echo $data->license->l; ?></td></tr>
					<tr><td width="33%" class="first"><?php echo AC()->lang->__( 'Website' ); ?>:</td><td><?php echo $data->license->url; ?></td></tr>
					<tr>
						<td width="33%" class="first"><?php echo AC()->lang->__( 'Expiration' ); ?>:</td>
						<td><b><?php if ( ! empty( $data->license->exp ) ) { ?>
									<span style="color:green;"><?php echo $data->license->exp; ?></span>
								<?php } ?>
							</b>
						</td>
					</tr>
				<?php } else { ?>
					<tr><td colspan="2" class="first">
						<a href="<?php CmCoupon::instance()->admin_url(); ?>#/cmcoupon/license">
							<span style="color:red; font-size:1.2em;font-weight:bold;"><?php echo AC()->lang->__( 'Activate subscription' ); ?></span>
						</a>
					</td></tr>
				<?php } ?>
				</tbody>
				</table>
			</div>

			<div class="panel postbox">

				<table class="adminlist licensetbl">
				<thead><tr><th colspan="2"><?php echo AC()->lang->__( 'Check for Updates' ); ?></th></tr></thead>
				<tbody>
				<?php
				if ( $data->versionchecker ) {
					if ( 0 == $data->check['connect'] ) {
						?>
						<tr><td colspan="2" class="first"><?php echo '<b><span style="color:red;">' . AC()->lang->__( 'Connection Failed' ) . '</span></b>'; ?></td></tr>
					<?php
					} elseif ( 1 == $data->check['enabled'] ) {
					?>
						<tr><td colspan="2" class="first">
							<?php
							if ( 0 == $data->check['current'] ) {
								echo '<strong><span style="color:green;">' . AC()->lang->__( 'Latest version installed' ) . '</span></strong>';
							} elseif ( -1 == $data->check['current'] ) {
								echo '
									<div style="float:left;">
										<b><span style="color:red;">' . AC()->lang->__( 'Old version currently installed' ) . '</span></b>
										&nbsp; &nbsp; [ <a href="' . $data->check['release_notes'] . '" target="_blank">release notes</a> ]
									</div>
								';
								if ( AC()->helper->get_cm_product() !== 'cmcouponwp' ) {
									echo '
										<div style="float:right;">
											<div class="icon">
												<a href="#/cmcoupon/upgrade">
													<span><img src="' . CMCOUPON_ASEET_URL . '/images/update-32.png" height="20" border="0" /></span>
													<span class="liveupdate-icon-updates">' . AC()->lang->__( 'UPDATE FOUND! CLICK TO UPDATE' ) . '</span>
												</a>
											</div>
										</div>
									';
								}
								echo '<div style="clear:both;"></div>';
							} else {
								echo '<b><span style="color:orange;">' . AC()->lang->__( 'Newer Version' ) . '</span></b>';
							}
							?>
							</td>
						</tr>
						<?php
						if ( 0 != $data->check['current'] ) {
						?>
						<tr>
							<td width="33%" class="first"><?php echo AC()->lang->__( 'Latest version' ) . ':'; ?></td>
							<td><?php echo $data->check['version'] . ' (' . $data->check['released'] . ')'; ?></td>
						</tr>
						<?php
						}
					}
				}
				?>
				<tr>
					<td width="33%" class="first"><?php echo AC()->lang->__( 'Installed version' ) . ':'; ?></td>
					<td><?php echo $data->check['current_version']; ?>
						<?php /*&nbsp; &nbsp;<button type="button" onclick="version_checker('<?php echo $data->versionchecker ? 'disable' : 'enable'; ?>');"><?php echo $data->versionchecker ? AC()->lang->__( 'Disable' ) : AC()->lang->__( 'Enable' ); ?></button>*/; ?>
					</td>
				</tr>
				</tbody>
				</table>
			</div>
		</td>
	</tr>
</table>
</div>

