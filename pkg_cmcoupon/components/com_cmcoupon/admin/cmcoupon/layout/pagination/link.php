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

$item = $data['data'];

$display = $item->text;

$inner_html = '';
switch ( (string) $item->text ) {
	// Check for "Start" item.
	case AC()->lang->__( 'Start' ):
		$icon = 'icon-navigation';
		$inner_html = '&laquo;';
		break;

	// Check for "Prev" item.
	case AC()->lang->__( 'Prev' ) === $item->text:
		$item->text = AC()->lang->__( 'Previous' );
		$icon = 'icon-navigation';
		$inner_html = '&lsaquo;';
		break;

	// Check for "Next" item.
	case AC()->lang->__( 'Next' ):
		$icon = 'icon-navigation';
		$inner_html = '&rsaquo;';
		break;

	// Check for "End" item.
	case AC()->lang->__( 'End' ):
		$icon = 'icon-navigation';
		$inner_html = '&raquo;';
		break;

	default:
		$icon = null;
		break;
}

if ( null !== $icon ) {
	$display = '<span class="' . $icon . '">' . $inner_html . '</span>';
}

if ( $data['active'] ) {
	if ( $item->base > 0 ) {
		$limit = 'limitstart.value=' . $item->base;
	} else {
		$limit = 'limitstart.value=0';
	}

	$css_classes = array();

	$title = '';

	if ( ! is_numeric( $item->text ) ) {
		$css_classes[] = 'hasTooltip';
		$title = ' title="' . $item->text . '" ';
	}

	$on_click = 'document.adminForm.' . $item->prefix . 'limitstart.value=' . ( $item->base > 0 ? $item->base : '0' ) . '; this.form.submit();return false;';
} else {
	$class = ( property_exists( $item, 'active' ) && $item->active ) ? 'active' : 'disabled';
}
?>
<?php if ( $data['active'] ) { ?>
	<li>
		<a class="<?php echo implode( ' ', $css_classes ); ?>" <?php echo $title; ?> href="<?php echo $item->link; ?>" >
			<?php echo $display; ?>
		</a>
	</li>
<?php } else { ?>
	<li class="<?php echo $class; ?>">
		<span><?php echo $display; ?></span>
	</li>
<?php
}
