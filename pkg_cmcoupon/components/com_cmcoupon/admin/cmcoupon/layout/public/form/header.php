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

$action = AC()->helper->get_link();
$formid = 'cmcouponForm' . mt_rand();
?>

<form action="<?php echo $action; ?>" method="post" id="<?php echo $formid; ?>" name="<?php echo $formid; ?>" >
	<input type="hidden" name="form_id" value="<?php echo $formid; ?>" />
