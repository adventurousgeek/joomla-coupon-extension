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

if ( empty( $data->url ) ) {
	return;
}
?>
<html>
<head>
<style>
@media print {
	.noprint { display:none; }
}
</style>
</head>
<body>

<div style="text-align:center;">

	<div><img src="<?php echo $data->url; ?>" alt="coupon" /></div>
	<br />
	<div class="noprint">
		<button onclick="window.print();return false;" type="button"><?php echo AC()->lang->__( 'Print' ); ?></button>
	</div>

</div>
</body>
</html>
