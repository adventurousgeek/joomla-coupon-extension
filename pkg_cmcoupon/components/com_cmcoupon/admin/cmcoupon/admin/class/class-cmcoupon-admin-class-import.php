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
class CmCoupon_Admin_Class_Import extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id coupon_id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'import';
		parent::__construct();
	}

	/**
	 * Export to csv
	 *
	 * @param array $coupon_ids coupons to export.
	 */
	public function export( $coupon_ids = null ) {

		$sql = 'SELECT * FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '"';
		if ( ! empty( $coupon_ids ) ) {
			$coupon_ids = AC()->helper->scrubids( $coupon_ids );
			$sql .= ' AND id IN ( ' . $coupon_ids . ' ) ';
		}
		$_data = $this->get_list( $sql, 'id' );
		if ( empty( $_data ) ) {
			return;
		}

		$coupon_ids = array();
		$columns = array();

		foreach ( $_data as $row ) {
			if ( empty( $coupon_ids ) ) {
				foreach ( $row as $c_key => $c_val ) {
					$columns[ $c_key ] = $c_key;
				}
			}
			$coupon_ids[] = $row->id;
		}

		if ( ! empty( $coupon_ids ) ) {

			$tmp = AC()->db->get_objectlist( 'SELECT coupon_id,tag FROM #__cmcoupon_tag WHERE coupon_id IN (' . implode( ',', $coupon_ids ) . ')' );
			foreach ( $tmp as $row ) {
				$columns['tags'] = 'tags';
				if ( ! isset( $_data[ $row->coupon_id ]->tags ) ) {
					$_data[ $row->coupon_id ]->tags = array();
				}
				$_data[ $row->coupon_id ]->tags[] = $row->tag;
			}

			$tmp = AC()->db->get_objectlist( 'SELECT * FROM #__cmcoupon_asset WHERE coupon_id IN (' . implode( ',', $coupon_ids ) . ')' );
			foreach ( $tmp as $row ) {
				$col_type = 'asset_' . $row->asset_key . '_' . $row->asset_type;
				$columns[ $col_type ] = $col_type;
				if ( ! isset( $_data[ $row->coupon_id ]->{$col_type} ) ) {
					$_data[ $row->coupon_id ]->{$col_type} = array();
				}
				$_data[ $row->coupon_id ]->{$col_type}[] = $row->asset_id;

				if ( ! empty( $row->qty ) ) {
					$col_qty = 'asset_' . $row->asset_key . '_' . $row->asset_type . '_qty';
					$columns[ $col_qty ] = $col_qty;
					if ( ! isset( $_data[ $row->coupon_id ]->{$col_qty} ) ) {
						$_data[ $row->coupon_id ]->{$col_qty} = array();
					}
					$_data[ $row->coupon_id ]->{$col_qty}[] = $row->qty;
				}
			}
		}

		$columns_blank = array_fill_keys( $columns, '' );
		$delimiter = AC()->param->get( 'csvDelimiter', ',' );

		$output = '';
		$output .= AC()->helper->fputcsv2( $columns, $delimiter );

		foreach ( $_data as $row ) {
			if ( ! is_array( $row ) ) {
				$row = (array) $row;
			}
			$d = array_merge( $columns_blank, $row );
			array_walk( $d, function ( & $item, $key ) {
				if ( is_array( $item ) ) {
					$item = implode( ';', $item );
				}
			});

			$output .= AC()->helper->fputcsv2( $d, $delimiter );
		}
		return $output;
	}

	/**
	 * Method to store the entry
	 *
	 * @param array $in_data data to save.
	 **/
	public function save( $in_data ) {
		if ( empty( $in_data['lines'] ) ) {
			return;
		}

		$store_none_errors = (int) $in_data['store_none_errors'];
		$_is_only_store_no_errors = 1 === $store_none_errors ? false : true;

		$distinct_ids = array();
		foreach ( $in_data['lines']  as $row ) {
			if ( ! isset( $distinct_ids[ (int) $row['id'] ] ) ) {
				$distinct_ids[ (int) $row['id'] ] = 0;
			}
			$distinct_ids[ (int) $row['id'] ]++;
		}

		$_map = AC()->helper->vars( '__all__' );
		$_map_user = array_keys( AC()->store->get_users() );
		$_map_usergroup = array_keys( AC()->store->get_groups() );
		$_map_product = array_keys( AC()->store->get_products() );
		$_map_coupon = AC()->db->get_column( 'SELECT id FROM #__cmcoupon' );
		$_map_category = array_keys( AC()->store->get_categorys( null, null, 100000 ) );
		$_map_manufacturer = array_keys( AC()->store->get_manufacturers() );
		$_map_vendor = array_keys( AC()->store->get_vendors() );
		$_map_custom = array_keys( AC()->store->get_assetcustoms( null, null, 100000 ) );
		$_map_shipping = array_keys( AC()->store->get_shippings() );
		$_map_country = array_keys( AC()->store->get_countrys() );
		$_map_countrystate = array_keys( AC()->store->get_countrystates() );
		$_map_paymentmethod = array_keys( AC()->store->get_paymentmethods() );

		$asset_keys = array_keys( $in_data['lines'][0] );
		foreach ( $asset_keys as $k => $r ) {
			if ( substr( $r, 0, 6 ) !== 'asset_' ) {
				unset( $asset_keys[ $k ] );
			}
		}
		$errors = array();
		$data = array();
		foreach ( $in_data['lines'] as $row ) {

			$id = (int) $row['id'];
			if ( $id < 0 ) {
				$id = 0;
			}
			$code = trim( $row['coupon_code'] );
			if ( empty( $code ) ) {
				$errors['          '][] = AC()->lang->__( 'No coupon code specified' );
				continue;
			}
			if ( isset( $data[ $code ] ) ) {
				$errors[ $code ][] = AC()->lang->__( 'Duplicate coupon code' );
				continue;
			}

			if ( ! empty( $id ) && $distinct_ids[ $id ] > 1 ) {
				$errors[ $code ][] = AC()->lang->__( 'Duplicate ID' );
			}

			$params = ! empty( $row['params'] ) ? json_decode( $row['params'], true ) : array();
			$data[ $code ] = array(
				'id' => $id,
				'function_type' => isset( $row['function_type'] ) ? $row['function_type'] : null,
				'function_type_buyxy' => '',
				'coupon_code' => isset( $row['coupon_code'] ) ? $row['coupon_code'] : null,
				'state' => isset( $row['state'] ) ? $row['state'] : null,
				'startdate_date' => ! empty( $row['startdate'] ) ? substr( $row['startdate'], 0, 10 ) : '',
				'startdate_time' => ! empty( $row['startdate'] ) ? substr( $row['startdate'], 11, 8 ) : '',
				'expiration_date' => ! empty( $row['expiration'] ) ? substr( $row['expiration'], 0, 10 ) : '',
				'expiration_time' => ! empty( $row['expiration'] ) ? substr( $row['expiration'], 11, 8 ) : '',
				'process_type_combination' => isset( $params['process_type'] ) ? $params['process_type'] : null,
				'process_type_buyxy' => isset( $params['process_type'] ) ? $params['process_type'] : null,
				'coupon_value_type' => isset( $row['coupon_value_type'] ) ? $row['coupon_value_type'] : null,
				'coupon_value' => isset( $row['coupon_value'] ) ? $row['coupon_value'] : null,
				'coupon_value_def' => isset( $row['coupon_value_def'] ) ? $row['coupon_value_def'] : null,
				'couponvalue_hidden' => ! empty( $row['coupon_value'] ) || empty( $row['coupon_value_def'] ) ? 'basic' : 'advanced',
				'max_discount_amt' => isset( $params['max_discount_amt'] ) ? $params['max_discount_amt'] : null,
				'num_of_uses_total' => isset( $row['num_of_uses_total'] ) ? $row['num_of_uses_total'] : null,
				'num_of_uses_customer' => isset( $row['num_of_uses_customer'] ) ? $row['num_of_uses_customer'] : null,
				'min_value' => isset( $row['min_value'] ) ? $row['min_value'] : null,
				'min_value_type' => isset( $params['min_value_type'] ) ? $params['min_value_type'] : null,
				'min_qty' => isset( $params['min_qty'] ) ? $params['min_qty'] : null,
				'min_qty_type' => isset( $params['min_qty_type'] ) ? $params['min_qty_type'] : null,
				'max_discount_qty' => isset( $params['max_discount_qty'] ) ? $params['max_discount_qty'] : null,
				'discount_type' => isset( $row['discount_type'] ) ? $row['discount_type'] : null,
				'upc' => isset( $row['upc'] ) ? $row['upc'] : null,
				'note' => isset( $row['note'] ) ? $row['note'] : null,

				'exclusive' => isset( $params['exclusive'] ) ? $params['exclusive'] : null,
				'exclude_discounted' => isset( $params['exclude_discounted'] ) ? $params['exclude_discounted'] : null,
				'exclude_giftcert' => isset( $params['exclude_giftcert'] ) ? $params['exclude_giftcert'] : null,
				'addtocart' => isset( $params['addtocart'] ) ? $params['addtocart'] : null,
				'product_match' => isset( $params['product_match'] ) ? $params['product_match'] : null,

				'tags' => ! empty( $row['tags'] ) ? explode( ';', $row['tags'] ) : array(),

				'asset' => ! empty( $params['asset'] ) ? json_decode( AC()->helper->json_encode( $params['asset'] ), true ) : array(),
			);

			if ( ! isset( $_map['state'][ $data[ $code ]['state'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[state]' );
			}
			if ( ! isset( $_map['function_type'][ $data[ $code ]['function_type'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[function_type]' );
			}
			if ( ! empty( $data[ $code ]['coupon_value_type'] ) && ! isset( $_map['coupon_value_type'][ $data[ $code ]['coupon_value_type'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[coupon_value_type]' );
			}
			if ( ! empty( $data[ $code ]['discount_type'] ) && ! isset( $_map['discount_type'][ $data[ $code ]['discount_type'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[discount_type]' );
			}
			if ( ! empty( $data[ $code ]['min_value_type'] ) && ! isset( $_map['min_value_type'][ $data[ $code ]['min_value_type'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[min_value_type]' );
			}
			if ( ! empty( $data[ $code ]['min_qty_type'] ) && ! isset( $_map['min_qty_type'][ $data[ $code ]['min_qty_type'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[min_qty_type]' );
			}
			if ( 'combination' === $data[ $code ]['function_type'] && ! isset( $_map['process_type_combination'][ $data[ $code ]['process_type_combination'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[process_type]' );
			}
			if ( in_array( $data[ $code ]['function_type'], array( 'buyxy', 'buyxy2' ), true ) && ! isset( $_map['process_type_buyxy'][ $data[ $code ]['process_type_buyxy'] ] ) ) {
				$errors[ $code ][] = AC()->lang->_e_valid( '[process_type]' );
			}

			foreach ( $asset_keys as $asset_column ) {
				if ( ! empty( $row[ $asset_column ] ) ) {
					$parts = explode( '_', $asset_column );
					if ( isset( $parts[2] ) && isset( $data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ] ) ) {
						if ( ! isset( $_map['asset_type'][ $parts[2] ] ) ) {
							$errors[ $code ][] = AC()->lang->_e_valid( '[asset_type]' );
						}
						$assets = explode( ';', $row[ $asset_column ] );
						$assets = array_map( 'trim', $assets );
						if ( count( $parts ) === 3 ) {
							$map = null;
							switch ( $parts[2] ) {
								case 'product':
									$map = $_map_product;
									break;
								case 'category':
									$map = $_map_category;
									break;
								case 'manufacturer':
									$map = $_map_manufacturer;
									break;
								case 'vendor':
									$map = $_map_vendor;
									break;
								case 'custom':
									$map = $_map_custom;
									break;
								case 'shipping':
									$map = $_map_shipping;
									break;
								case 'coupon':
									$map = $_map_coupon;
									break;
								case 'user':
									$map = $_map_user;
									break;
								case 'usergroup':
									$map = $_map_usergroup;
									break;
								case 'country':
									$map = $_map_country;
									break;
								case 'countrystate':
									$map = $_map_countrystate;
									break;
								case 'paymentmethod':
									$map = $_map_paymentmethod;
									break;
							}
							if ( ! empty( $map ) ) {
								$err = array_diff( $assets, $map );
								if ( ! empty( $err ) ) {
									$errors[ $code ][] = '[' . $asset_column . '] ' . AC()->lang->__( 'one or more do not exist' );
								}
							}
							$tmp_values = null;
							if ( ! empty( $data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ]['rows'] ) ) {
								$tmp_values = $data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ]['rows'];
							}
							foreach ( $assets as $k1 => $asset_value ) {
								$data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ]['rows'][ $k1 ] = array(
									'asset_id' => $asset_value,
								);
								if ( isset( $tmp_values[ $k1 ] ) ) {
									foreach ( $tmp_values[ $k2 ] as $val ) {
										$data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ]['rows'][ $k1 ][ $k2 ] = $val;
									}
								}
							}
						} elseif ( count( $parts ) === 4 ) {
							foreach ( $assets as $k => $asset_value ) {
								$data[ $code ]['asset'][ $parts[1] ]['rows'][ $parts[2] ]['rows'][ $k ][ $parts[3] ] = $asset_value;
							}
						}
					}
				}
			}
		}

		$model_coupon = AC()->helper->new_class( 'CmCoupon_Admin_Class_Coupon' );
		$dbdata = array();
		foreach ( $data as $code => $row ) {
			if ( empty( $errors[ $code ] ) ) {
				// Check or insert into database.
				$data = $row;
				if ( true === $_is_only_store_no_errors ) {
					$data['is_error_check_only'] = true;
				}
				$coupon_errors = $model_coupon->save( $data );
				if ( ! empty( $coupon_errors ) ) {
					$errors[ $code ] = $coupon_errors;
				} else {
					$dbdata[ $code ] = $row;
				}
			}
		}

		if ( true === $_is_only_store_no_errors && count( $dbdata ) === count( $in_data['lines'] ) ) {
			// If just check and there are no errors, insert everything.
			foreach ( $dbdata as $code => $row ) {
				$coupon_errors = $model_coupon->save( $row );
				if ( ! empty( $coupon_errors ) ) {
					$errors[ $code ] = $coupon_errors;
				}
			}
		}

		return $errors;
	}

}
