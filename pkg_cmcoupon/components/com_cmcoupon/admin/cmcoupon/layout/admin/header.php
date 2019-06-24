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

?>
<script>
	<?php echo file_get_contents( CMCOUPON_DIR . '/cmcoupon/media/assets/js/cmcoupon.load.js' ); ?>
	<?php
	if ( file_exists( CMCOUPON_DIR . '/assets/js/cmcoupon.load.js' ) ) {
		echo file_get_contents( CMCOUPON_DIR . '/assets/js/cmcoupon.load.js' );
	}
	?>
</script>
