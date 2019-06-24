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
<style>
label.awbtn {
	display: inline-block; line-height: 18px; margin: 0; min-width: 84px; padding: 2px 12px;
}
</style>

<fieldset class="inline-edit-col-left"><legend class="inline-edit-legend"><?php echo AC()->lang->__( 'General' ); ?></legend>
	<div class="inner" >
		<?php
		if ( 'published' == $data->row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/published.png';
			$alt = AC()->lang->__( 'Published' );
		} elseif ( 'template' == $data->row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/template.png';
			$alt = AC()->lang->__( 'Coupon template' );
		} elseif ( 'balance' == $data->row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/status_credit.png';
			$alt = AC()->lang->__( 'Gift certificate balance' );
		} else {
			$img = CMCOUPON_ASEET_URL . '/images/unpublished.png';
			$alt = AC()->lang->__( 'Unpublished' );
		}
		if ( empty( $data->row->num_of_uses_total ) && empty( $data->row->num_of_uses_customer ) ) {
			$num_of_uses = AC()->lang->__( 'Unlimited' );
		} else {
			$num_of_uses = array();
			if ( ! empty( $data->row->num_of_uses_total ) ) {
				$num_of_uses[] = $data->row->num_of_uses_total . ' ' . AC()->helper->vars( 'num_of_uses_type', 'total' );
			}
			if ( ! empty( $data->row->num_of_uses_customer ) ) {
				$num_of_uses[] = $data->row->num_of_uses_customer . ' ' . AC()->helper->vars( 'num_of_uses_type', 'per_user' );
			}
			$num_of_uses = implode( ', ', $num_of_uses );
		}
		$coupon_value_type = 'combination' == $data->row->function_type ? '' : AC()->helper->vars( 'coupon_value_type', $data->row->coupon_value_type );
		$check_discount = AC()->helper->vars( 'discount_type', $data->row->discount_type );
		if ( ! empty( $check ) ) {
			$discount_type = $check_discount;
		}
		$function_type = AC()->helper->vars( 'function_type', $data->row->function_type );
		$coupon_value = ! empty( $data->row->coupon_value )
				? $data->row->coupon_value
				: AC()->coupon->get_value_print( $data->row->coupon_value_def, $data->row->coupon_value_type );
		$exclude_str = array();
		if ( ! empty( $data->row->params->exclude_special ) ) {
			$exclude_str[] = AC()->lang->__( 'Specials' );
		}
		if ( ! empty( $data->row->params->exclude_discounted ) ) {
			$exclude_str[] = AC()->lang->__( 'Discounted' );
		}
		if ( ! empty( $data->row->params->exclude_giftcert ) ) {
			$exclude_str[] = AC()->lang->__( 'Gift Products' );
		}
		?>

		<table class="widefat striped">
		<tr><td><?php echo AC()->lang->__( 'Coupon Code' ); ?></td>
			<td><?php echo $data->row->coupon_code; ?></td>
		</tr>
		<tr><td><?php echo AC()->lang->__( 'Secret Key' ); ?></td>
			<td><?php echo $data->row->passcode; ?></td>
		</tr>
		<?php if ( ! empty( $data->row->upc ) ) { ?>
			<tr valign="top"><td><?php echo AC()->lang->__( 'UPC' ); ?></td>
				<td><?php echo $data->row->upc; ?></td>
			</tr>
		<?php } ?>
		<tr><td><?php echo AC()->lang->__( 'Published' ); ?></td>
			<td ><?php echo '<img src="' . $img . '" width="16" height="16" border="0" alt="' . $alt . '" title="' . $alt . '" />'; ?></td>
		</tr>
		<tr><td><?php echo AC()->lang->__( 'Function Type' ); ?></td>
			<td><?php echo $function_type; ?></td>
		</tr>
		<?php if ( 'giftcert' != $data->row->function_type && ! empty( $num_of_uses ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Number of Uses' ); ?></td>
				<td><?php echo $num_of_uses; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $coupon_value_type ) ) { ?>
			<tr valign="top"><td><?php echo AC()->lang->__( 'Percent or Amount' ); ?></td>
				<td><?php echo $coupon_value_type; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $discount_type ) ) { ?>
			<tr valign="top"><td><?php echo AC()->lang->__( 'Discount Type' ); ?></td>
				<td><?php echo $discount_type; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $coupon_value ) ) { ?>
			<tr valign="top"><td><?php echo AC()->lang->__( 'Value' ); ?></td>
				<td><?php echo $coupon_value; ?></td>
			</tr>
		<?php } ?>
		<?php if ( in_array( $data->row->function_type, array( 'buyxy', 'buyxy2' ) ) && ! empty( $data->row->params->process_type ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Process Type' ); ?></td>
				<td><?php echo AC()->helper->vars( 'process_type_buyxy', $data->row->params->process_type ); ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->min_value ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Minimum Value' ); ?></td>
				<td><?php echo number_format( $data->row->min_value, 2 ) . ' ' . AC()->helper->vars( 'min_value_type', ! empty( $data->row->params->min_value_type ) ? $data->row->params->min_value_type : 'overall' ); ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->params->min_qty ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Minimum Product Quantity' ); ?></td>
				<td><?php echo number_format( $data->row->params->min_qty, 2 ) . ' ' . AC()->helper->vars( 'discount_type', ! empty( $data->row->params->min_qty_type ) ? $data->row->params->min_qty_type : 'overall' ); ?></td>
			</tr>
		<?php } ?>

		<?php if ( ! empty( $data->row->params->max_discount_qty ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Maximum Discount Qty' ); ?></td>
				<td><?php echo $data->row->params->max_discount_qty; ?></td>
			</tr>
		<?php } ?>
		<?php if ( 'combination' == $data->row->function_type ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Process Type' ); ?></td>
				<td><?php echo AC()->helper->vars( 'process_type_buyxy', $data->row->params->process_type ); ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->asset[1]->qty ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'BuyX' ); ?></td>
				<td><?php echo $data->row->asset[1]->qty; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->asset[2]->qty ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'GetY' ); ?></td>
				<td><?php echo $data->row->asset[2]->qty; ?></td>
			</tr>
		<?php } ?>
		<?php if ( 'buyxy' == $data->row->function_type ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Don\'t Mix Products' ); ?></td>
				<td><?php echo empty( $data->row->params->product_match ) ? AC()->lang->__( 'No' ) : AC()->lang->__( 'Yes' ); ?></td>
			</tr>
			<tr><td><?php echo AC()->lang->__( 'Automatically add to cart \'Get Y\' product' ); ?></td>
				<td><?php echo empty( $data->row->params->addtocart ) ? AC()->lang->__( 'No' ) : AC()->lang->__( 'Yes' ); ?></td>
			</tr>
		<?php } ?>

		<?php if ( ! empty( $data->row->startdate ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Start Date' ); ?></td>
				<td><?php echo $data->row->startdate; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->expiration ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Expiration' ); ?></td>
				<td><?php echo $data->row->expiration; ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $exclude_str ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Exclude' ); ?></td>
				<td><?php echo implode( ', ', $exclude_str ); ?></td>
			</tr>
		<?php } ?>
		<?php if ( ! empty( $data->row->note ) ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Admin Note' ); ?></td>
				<td><?php echo nl2br( $data->row->note ); ?></td>
			</tr>
		<?php } ?>
		<?php if ( 'giftcert' != $data->row->function_type ) { ?>
			<tr><td><?php echo AC()->lang->__( 'History of Uses' ); ?></td>
				<td><?php echo $data->row->num_used; ?></td>
			</tr>
		<?php } ?>
		<?php if ( 'giftcert' == $data->row->function_type ) { ?>
			<tr><td><?php echo AC()->lang->__( 'Value Used' ); ?></td>
				<td><?php echo number_format( $data->row->giftcert_used, 2 ); ?></td>
			</tr>
			<tr><td><?php echo AC()->lang->__( 'Balance' ); ?></td>
				<td><?php echo number_format( $data->row->giftcert_balance, 2 ); ?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
</fieldset>




<?php
if ( 'buyxy' == $data->row->function_type ) {
	foreach ( array( 1, 2 ) as $asset_key ) {
		if ( ! empty( $data->row->asset[ $asset_key ] ) ) {
?>
		<fieldset class="inline-edit-col-left"><legend class="inline-edit-legend"><?php echo 1 == $asset_key ? AC()->lang->__( 'BuyX' ) : AC()->lang->__( 'GetY' ); ?></legend>
			<div class="inner">
				<table class="widefat striped">
				<thead>
					<tr>
						<th width="5">#</th>
						<th class="title" width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Type' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Mode' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Asset' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data->row->asset[ $asset_key ]->rows as $asset_type => $r1 ) {
						foreach ( $r1->rows as $i => $row ) {
						?>
					<tr class="row<?php echo ( $i % 2 ); ?>">
						<td><?php echo ( $i + 1 ); ?></td>
						<td align="right"><?php echo $row->asset_id; ?>&nbsp;&nbsp;</td>
						<td align=""><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></td>
						<td>
							<?php if ( empty( $r1->mode ) || 'include' == $r1->mode ) { ?>
								<label class="awbtn active awbtn-success" style="display: inline-block; line-height: 18px; margin: 0; min-width: 84px; padding: 2px 12px;"><?php echo AC()->lang->__( 'Include' ); ?></label>
							<?php } ?>
							<?php if ( ! empty( $r1->mode ) && 'exclude' == $r1->mode ) { ?>
								<label class="awbtn active awbtn-danger" style="display: inline-block; line-height: 18px; margin: 0; min-width: 84px; padding: 2px 12px;"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
							<?php } ?>
						</td>
						<td align=""><?php echo $row->asset_name; ?></td>
					</tr>
					<?php
						}
					}
					?>
				</tbody>
				</table>
			</div>
		</fieldset>
<?php
		}
	}
}
?>




<?php
if ( 'buyxy2' == $data->row->function_type ) {
	foreach ( array( 3, 4 ) as $asset_key ) {
		if ( ! empty( $data->row->asset[ $asset_key ] ) ) {
?>
		<fieldset class="inline-edit-col-left"><legend class="inline-edit-legend"><?php echo 3 == $asset_key ? AC()->lang->__( 'BuyX' ) : AC()->lang->__( 'GetY' ); ?></legend>
			<div class="inner">
				<table class="widefat striped">
				<thead>
					<tr>
						<th width="5">#</th>
						<th class="title" width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Type' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Number' ); ?></th>
						<th class="title"><?php echo AC()->lang->__( 'Asset' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data->row->asset[ $asset_key ]->rows as $asset_type => $r1 ) {
						foreach ( $r1->rows as $i => $row ) {
					?>
					<tr class="row<?php echo ( $i % 2 ); ?>">
						<td><?php echo ( $i + 1 ); ?></td>
						<td align="right"><?php echo $row->asset_id; ?>&nbsp;&nbsp;</td>
						<td align=""><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></td>
						<td><?php echo $row->qty; ?></td>
						<td align=""><?php echo $row->asset_name; ?></td>
					</tr>
					<?php
						}
					}
					?>
				</tbody>
				</table>
			</div>
		</fieldset>
<?php
		}
	}
}
?>




<?php
$asset_key = 0;
if ( ! empty( $data->row->asset[ $asset_key ] ) ) {
	foreach ( $data->row->asset[ $asset_key ]->rows as $asset_type => $r1 ) {
?>
	<fieldset class="inline-edit-col-left"><legend class="inline-edit-legend"><?php echo AC()->helper->vars( 'asset_type', $asset_type ); ?></legend>
		<div class="inner">
			<table class="widefat striped">
			<thead>
				<tr>
					<th width="5">#</th>
					<th class="title" width="1"><?php echo AC()->lang->__( 'ID' ); ?></th>
					<th class="title"><?php echo AC()->lang->__( 'Mode' ); ?></th>
					<th class="title"><?php echo AC()->lang->__( 'Asset' ); ?></th>
					<?php if ( 'coupon' == $asset_type ) { ?>
						<th class="title"><?php echo AC()->lang->__( 'Published' ); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( 'coupon' == $asset_type ) {
					if ( ! empty( $r1->rows ) ) {
						$asset1 = array();
						foreach ( $r1->rows as $tmp1 ) {
							$asset1[ $tmp1->asset_id ] = $tmp1;
						}

						$tmp = AC()->db->get_objectlist( 'SELECT id,state FROM #__cmcoupon WHERE id IN (' . implode( ',', array_keys( $asset1 ) ) . ')' );
						foreach ( $tmp as $tmp1 ) {
							$r1->rows[ $tmp1->id ]->state = $tmp1->state;
						}
					}
				}

				foreach ( $r1->rows as $i => $row ) {
					if ( 'coupon' == $asset_type ) {
						if ( 'published' == $row->state ) {
							$img = CMCOUPON_ASEET_URL . '/images/published.png';
							$alt = AC()->lang->__( 'Published' );
						} elseif ( 'template' == $row->state ) {
							$img = CMCOUPON_ASEET_URL . '/images/template.png';
							$alt = AC()->lang->__( 'Coupon template' );
						} elseif ( 'balance' == $row->state ) {
							$img = CMCOUPON_ASEET_URL . '/images/status_credit.png';
							$alt = AC()->lang->__( 'Gift certificate balance' );
						} else {
							$img = CMCOUPON_ASEET_URL . '/images/unpublished.png';
							$alt = AC()->lang->__( 'Unpublished' );
						}
					}
					?>
				<tr class="row<?php echo ( $i % 2 ); ?>">
					<td><?php echo ( $i + 1 ); ?></td>
					<td align="right"><?php echo $row->asset_id; ?>&nbsp;&nbsp;</td>
					<td>
						<?php if ( empty( $r1->mode ) || 'include' == $r1->mode ) { ?>
							<label class="awbtn active awbtn-success" style="display: inline-block; line-height: 18px; margin: 0; min-width: 84px; padding: 2px 12px;"><?php echo AC()->lang->__( 'Include' ); ?></label>
						<?php } ?>
						<?php if ( ! empty( $r1->mode ) && 'exclude' == $r1->mode ) { ?>
							<label class="awbtn active awbtn-danger" style="display: inline-block; line-height: 18px; margin: 0; min-width: 84px; padding: 2px 12px;"><?php echo AC()->lang->__( 'Exclude' ); ?></label>
						<?php } ?>
					</td>
					<td align=""><?php echo $row->asset_name; ?></td>
					<?php if ( 'coupon' == $asset_type ) { ?>
						<td align="center"><?php echo '<img src="' . $img . '" width="16" height="16" class="hand" border="0" alt="' . $alt . '" title="' . $alt . '"/>'; ?></td>
					<?php } ?>
				</tr>
				<?php } ?>
			</tbody>
			</table>
		</div>
	</fieldset>
<?php
	}
}
?>
