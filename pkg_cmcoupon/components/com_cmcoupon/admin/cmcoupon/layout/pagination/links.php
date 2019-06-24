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
$pages = $list['pages'];

$show_limit_box   = true;
$show_pages_links = true;
$show_limit_start = false;

// Calculate to display range of pages.
$current_page = 1;
$range = 1;
$step = 5;

if ( ! empty( $pages['pages'] ) ) {
	foreach ( $pages['pages'] as $k => $page ) {
		if ( ! $page['active'] ) {
			$current_page = $k;
		}
	}
}

if ( $current_page >= $step ) {
	if ( 0 === $current_page % $step ) {
		$range = ceil( $current_page / $step ) + 1;
	} else {
		$range = ceil( $current_page / $step );
	}
}
?>

<div class="pagination pagination-toolbar clearfix" style="text-align: center;">

	<?php if ( $show_limit_box ) : ?>
		<div class="limit pull-right">
			<?php echo AC()->lang->__( 'Display #' ) . $list['limitfield']; ?>
		</div>
	<?php endif; ?>

	<?php if ( $show_pages_links && ( ! empty( $pages ) ) ) : ?>
		<ul class="pagination-list">
			<?php
				echo AC()->helper->render_layout( 'pagination.link', $pages['start'] );
				echo AC()->helper->render_layout( 'pagination.link', $pages['previous'] );
			?>
			<?php foreach ( $pages['pages'] as $k => $page ) : ?>

				<?php $output = AC()->helper->render_layout( 'pagination.link', $page ); ?>
				<?php if ( in_array( $k, range( $range * $step - ( $step + 1 ), $range * $step ), true ) ) : ?>
					<?php if ( ( 0 === $k % $step || $k === $range * $step - ( $step + 1 ) ) && $k !== $current_page && $k !== $range * $step - $step ) : ?>
						<?php $output = preg_replace( '#(<a.*?>).*?(</a>)#', '$1...$2', $output ); ?>
					<?php endif; ?>
				<?php endif; ?>

				<?php echo $output; ?>
			<?php endforeach; ?>
			<?php
				echo AC()->helper->render_layout( 'pagination.link', $pages['next'] );
				echo AC()->helper->render_layout( 'pagination.link', $pages['end'] );
			?>
		</ul>
	<?php endif; ?>

	<?php if ( $show_limit_start ) : ?>
		<input type="hidden" name="<?php echo $list['prefix']; ?>limitstart" value="<?php echo $list['limitstart']; ?>" />
	<?php endif; ?>

</div>
