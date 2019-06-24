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

$list = $data['list'];
?>
<ul>
	<li class="pagination-start"><?php echo $list['start']['data']; ?></li>
	<li class="pagination-prev"><?php echo $list['previous']['data']; ?></li>
	<?php foreach ( $list['pages'] as $page ) : ?>
		<?php echo '<li>' . $page['data'] . '</li>'; ?>
	<?php endforeach; ?>
	<li class="pagination-next"><?php echo $list['next']['data']; ?></li>
	<li class="pagination-end"><?php echo $list['end']['data']; ?></li>
</ul>
