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
jQuery( document ).ready(function() {
	jQuery('a.modal').live('click', function(e){
		e.preventDefault();
		url = jQuery(this).attr('href');
		tb_show("", url);
	});
});

</script>


<?php
/*<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo AC()->lang->__('Coupons'); ?></h1>
</div>
<hr class="wp-header-end">*/
?>

<?php echo $data->table_html; ?>
