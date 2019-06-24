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
 * Admin class
 */
class CmCoupon_Admin_Class_Coupon extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id coupon_id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'coupon';
		$this->_id = $id;
		$this->_orderby = 'coupon_code';
		$this->_primary = 'coupon_code';
		parent::__construct();

	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'coupon_code' => AC()->lang->__( 'Coupon Code' ),
			'function_type' => AC()->lang->__( 'Function Type' ),
			'coupon_value' => AC()->lang->__( 'Value' ),
			'num_uses' => AC()->lang->__( 'Number of Uses' ),
			'num_used' => AC()->lang->__( 'History of Uses' ),
			'min_value' => AC()->lang->__( 'Minimum Value' ),
			'discount_type' => AC()->lang->__( 'Discount Type' ),
			'startdate' => AC()->lang->__( 'Start Date' ),
			'expiration' => AC()->lang->__( 'Expiration' ),
			'assets' => AC()->lang->__( 'Assets' ),
			'state' => AC()->lang->__( 'State' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'c.id',
			'coupon_code' => 'c.coupon_code',
			'function_type' => 'c.function_type',
			'coupon_value' => 'c.coupon_value',
			'num_uses' => 'num_of_uses_order',
			'min_value' => 'c.min_value',
			'discount_type' => 'c.discount_type',
			'startdate' => 'c.startdate',
			'expiration' => 'c.expiration',
		);
		if ( (int) AC()->param->get( 'disable_couponlist_history_ordering', 0 ) !== 1 ) {
			$sortable_columns['num_used'] = 'num_used';
		}

		return $sortable_columns;
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {

		$items = array(
			'inline' => '<a class="editinline" href="#" data-id="' . $row->id . '">' . AC()->lang->__( 'Detail' ) . '</a>',
			'edit' => '<a href="#/cmcoupon/coupon/edit?id=' . $row->id . '">' . AC()->lang->__( 'Edit' ) . '</a>',
			'delete' => '<a href="#/cmcoupon/coupon?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
			'duplicate' => '<a href="#/cmcoupon/coupon?task=copy&id=' . $row->id . '">' . AC()->lang->__( 'Duplicate' ) . '</a>',
		);

		if ( 'balance' === $row->state ) {
			unset( $items['edit'], $items['duplicate'] );
		}
		return $items;
	}

	/**
	 * Checkbox column
	 *
	 * @param object $row the object.
	 */
	public function column_cb( $row ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%1$s" />', $row->id );
	}

	/**
	 * Default column behavior
	 *
	 * @param object $item the object.
	 * @param string $column_name the column.
	 */
	public function column_default( $item, $column_name ) {
		return $item->{$column_name};
	}

	/**
	 * Function column
	 *
	 * @param object $row the object.
	 */
	public function column_function_type( $row ) {
		return AC()->helper->vars( 'function_type', $row->function_type );
	}

	/**
	 * Coupon value column
	 *
	 * @param object $row the object.
	 */
	public function column_coupon_value( $row ) {
		if ( 'combination' === $row->function_type ) {
			return '--';
		} else {
			$coupon_value_type_ = '';
			if ( ! empty( $row->coupon_value_type ) ) {
				$coupon_value_type_ = 'percent' === $row->coupon_value_type ? '%' : ' ' . AC()->helper->vars( 'coupon_value_type', $row->coupon_value_type );
			}
			return ! empty( $row->coupon_value )
					? number_format( $row->coupon_value, 2 ) . $coupon_value_type_
					: AC()->coupon->get_value_print( $row->coupon_value_def, $row->coupon_value_type );
		}
	}

	/**
	 * Number uses column
	 *
	 * @param object $row the object.
	 */
	public function column_num_uses( $row ) {
		$num_of_uses = '--';
		$discount_type = '--';
		$min_value = '--';
		if ( 'giftcert' !== $row->function_type ) {
			if ( empty( $row->num_of_uses_total ) && empty( $row->num_of_uses_customer ) ) {
				$num_of_uses = AC()->lang->__( 'Unlimited' );
			} else {
				$num_of_uses = '';
				if ( ! empty( $row->num_of_uses_total ) ) {
					$num_of_uses .= '<div>' . $row->num_of_uses_total . ' ' . AC()->helper->vars( 'num_of_uses_type','total' ) . '</div>';
				}
				if ( ! empty( $row->num_of_uses_customer ) ) {
					$num_of_uses .= '<div>' . $row->num_of_uses_customer . ' ' . AC()->helper->vars( 'num_of_uses_type','per_user' ) . '</div>';
				}
			}
		}
		return $num_of_uses;
	}

	/**
	 * Minumum value column
	 *
	 * @param object $row the object.
	 */
	public function column_min_value( $row ) {
		$min_value = '--';
		if ( ! in_array( $row->function_type, array( 'giftcert', 'combination' ), true ) ) {
			$min_value = '';
			if ( ! empty( $row->min_value ) ) {
				$min_value = number_format( $row->min_value, 2 ) . ' ' . AC()->helper->vars( 'min_value_type', ! empty( $row->params->min_value_type ) ? $row->params->min_value_type : 'overall' );
			}
		}
		return $min_value;
	}

	/**
	 * Discount type column
	 *
	 * @param object $row the object.
	 */
	public function column_discount_type( $row ) {
		$discount_type = '--';
		if ( ! in_array( $row->function_type, array( 'giftcert', 'combination' ), true ) ) {
			if ( ! empty( $row->discount_type ) ) {
				$discount_type = AC()->helper->vars( 'discount_type', $row->discount_type );
			}
		}
		return $discount_type;
	}

	/**
	 * Start date column
	 *
	 * @param object $row the object.
	 */
	public function column_startdate( $row ) {
		return empty( $row->startdate ) ? '' : AC()->helper->get_date( $row->startdate, 'datetime' );
	}

	/**
	 * Expiration column
	 *
	 * @param object $row the object.
	 */
	public function column_expiration( $row ) {
		return empty( $row->expiration ) ? '' : AC()->helper->get_date( $row->expiration, 'datetime' );
	}

	/**
	 * Asset column
	 *
	 * @param object $row the object.
	 */
	public function column_assets( $row ) {
		$html = '';

		if ( ! empty( $row->assetcount ) ) {
			$params = empty( $row->params->asset ) ? array() : json_decode( AC()->helper->json_encode( $row->params->asset ), true );
			foreach ( $row->assetcount as $asset_key => $r1 ) {
				foreach ( $r1 as $asset_type => $count ) {
					$asset_key = (int) $asset_key;
					if ( 0 === $asset_key ) {
						if ( 'coupon' === $asset_type ) {
							$html = '
								<div>
									<span>' . AC()->helper->vars( 'process_type_combination', $row->params->process_type ) . ' ' . $count->cnt . '</span>
									<span>' . AC()->lang->__( 'Coupons' ) . '</span>
								</div>
							';
						} else {
							$html .= '
								<div>
									<span>' . AC()->helper->vars( 'asset_mode', empty( $params[ $asset_key ]['rows'][ $asset_type ]['mode'] ) ? 'include' : $params[ $asset_key ]['rows'][ $asset_type ]['mode'] ) . ' ' . $count->cnt . '</span>
									<span>' . AC()->helper->vars( 'asset_type', $asset_type ) . '</span>
								</div>
							';
						}
					} else {
						if ( 'buyxy' === $row->function_type ) {
							$html .= '
								<div>
									<span>' . (1 === $asset_key ? AC()->lang->__( 'BuyX' ) : AC()->lang->__( 'GetY' ) ) . ': ' . AC()->helper->vars( 'asset_mode', $params[ $asset_key ]['rows'][ $asset_type ]['mode'] ) . ' ' . $count->cnt . '</span>
									<span>' . AC()->helper->vars( 'asset_type', $asset_type ) . '</span>
								</div>
							';
						} elseif ( 'buyxy2' === $row->function_type ) {
							$html .= '
								<div>
									<span>' . ( 3 === $asset_key ? AC()->lang->__( 'BuyX' ) : AC()->lang->__( 'GetY' ) ) . ': ' . $count->qty . '</span>
									<span>' . AC()->helper->vars( 'asset_type', $asset_type ) . '</span>
								</div>
							';
						}
					}
				}
			}
		}

		return $html;
	}

	/**
	 * State column
	 *
	 * @param object $row the object.
	 */
	public function column_state( $row ) {
		$link = '';
		if ( 'published' === $row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/published.png';
			$alt = AC()->lang->__( 'Published' );
			$link = '#/cmcoupon/coupon?task=unpublish&id=' . $row->id;
		} elseif ( 'template' === $row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/template.png';
			$alt = AC()->lang->__( 'Template' );
		} elseif ( 'balance' === $row->state ) {
			$img = CMCOUPON_ASEET_URL . '/images/status_credit.png';
			$alt = AC()->lang->__( 'Balance' );
		} else {
			$img = CMCOUPON_ASEET_URL . '/images/unpublished.png';
			$alt = AC()->lang->__( 'Unpublished' );
			$link = '#/cmcoupon/coupon?task=publish&id=' . $row->id;
		}

		return '<a href="' . $link . '"><img src="' . $img . '" width="16" height="16" class="hand" border="0" alt="' . $alt . '" title="' . $alt . '" /></a>';
	}

	/**
	 * Get coupon data
	 */
	public function get_data() {
		// Lets load the files if it doesn't already exist.
		if ( empty( $this->_data ) ) {
			$this->_data = $this->get_list( $this->buildquery(), 'id', $this->get_state( 'limitstart' ), $this->get_state( 'limit' ) );

			$ids = '';
			$ptr = null;
			foreach ( $this->_data as $i => $row ) {
				$this->_data[ $i ]->is_editable = ! empty( $this->_data[ $i ]->order_id ) || 'balance' === $this->_data[ $i ]->state ? false : true;
				$this->_data[ $i ]->params = json_decode( $this->_data[ $i ]->params );

				$ids .= $row->id . ',';
			}

			if ( ! empty( $ids ) ) {
				$ids = substr( $ids, 0, -1 );

				$rows = AC()->db->get_objectlist( 'SELECT coupon_id,asset_type,asset_key,SUM(qty) AS qty, count(asset_id) as cnt FROM #__cmcoupon_asset WHERE coupon_id IN (' . $ids . ') GROUP BY coupon_id,asset_type' );
				foreach ( $rows as $tmp ) {
					if ( ! is_array( $this->_data[ $tmp->coupon_id ]->assetcount ) ) {
						$this->_data[ $tmp->coupon_id ]->assetcount = array();
					}
					$this->_data[ $tmp->coupon_id ]->assetcount[ $tmp->asset_key ][ $tmp->asset_type ] = $tmp;
				}

				if ( (int) AC()->param->get( 'disable_couponlist_history_ordering', 0 ) === 1 ) {
					$sql = '
							SELECT COUNT(h1.id) AS num_used,h1.coupon_id
							  FROM #__cmcoupon_history h1
							  JOIN #__cmcoupon c1 ON c1.id=h1.coupon_id
							 WHERE c1.function_type!="combination" AND h1.coupon_id=h1.coupon_entered_id AND h1.coupon_id IN (' . $ids . ')
							 GROUP BY h1.coupon_id
									UNION
							SELECT COUNT(DISTINCT h2.order_id) AS num_used,h2.coupon_entered_id AS coupon_id
							  FROM #__cmcoupon_history h2
							  JOIN #__cmcoupon c2 ON c2.id=h2.coupon_entered_id
							 WHERE c2.function_type="combination" AND h2.coupon_entered_id IN (' . $ids . ')
							 GROUP BY h2.coupon_entered_id
					';
					$rows = AC()->db->get_objectlist( $sql );
					foreach ( $rows as $tmp ) {
						$this->_data[ $tmp->coupon_id ]->num_used = $tmp->num_used;
					}
				}
			}
		}

		return $this->_data;
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();
		if ( ! empty( $orderby ) ) {
			$orderby = ' ORDER BY ' . $orderby . ' ';
		}

		if ( (int) AC()->param->get( 'disable_couponlist_history_ordering', 0 ) === 1 ) {
			$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,
							c.coupon_value,c.coupon_value_def,c.upc,
							c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.order_id,c.state,
							0 as usercount,0 as assetcount,c.note,c.params,
							GROUP_CONCAT(t.tag separator ", ") as coupon_tags,
							0 AS num_used,
							IF(c.function_type="giftcert",
								0,
								IF((c.num_of_uses_customer IS NULL or c.num_of_uses_customer="") AND (c.num_of_uses_total IS NULL or c.num_of_uses_total="") ,
									999999999,
									IFNULL(c.num_of_uses_customer,0) + IFNULL(c.num_of_uses_total,0)
								)
							) AS num_of_uses_order
					 FROM #__cmcoupon c
					 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
					WHERE 1=1
						' . $where . '
					GROUP BY c.id ' . $orderby;
		} else {
			$sql = 'SELECT c.id,c.coupon_code,c.num_of_uses_total,c.num_of_uses_customer,c.coupon_value_type,
							c.coupon_value,c.coupon_value_def,c.upc,
							c.min_value,c.discount_type,c.function_type,c.startdate,c.expiration,c.order_id,c.state,
							0 as usercount,0 as assetcount,c.note,c.params,
							GROUP_CONCAT(t.tag separator ", ") as coupon_tags,
							h.num_used,
							IF(c.function_type="giftcert",
								0,
								IF((c.num_of_uses_customer IS NULL or c.num_of_uses_customer="") AND (c.num_of_uses_total IS NULL or c.num_of_uses_total="") ,
									999999999,
									IFNULL(c.num_of_uses_customer,0) + IFNULL(c.num_of_uses_total,0)
								)
							) AS num_of_uses_order
					 FROM #__cmcoupon c
					 LEFT JOIN #__cmcoupon_tag t ON t.coupon_id=c.id
					 LEFT JOIN (
							SELECT COUNT(h1.id) AS num_used,h1.coupon_id
							  FROM #__cmcoupon_history h1
							  JOIN #__cmcoupon c1 ON c1.id=h1.coupon_id
							 WHERE c1.function_type!="combination" AND h1.coupon_id=h1.coupon_entered_id
							 GROUP BY h1.coupon_id
									UNION
							SELECT COUNT(DISTINCT h2.order_id) AS num_used,h2.coupon_entered_id AS coupon_id
							  FROM #__cmcoupon_history h2
							  JOIN #__cmcoupon c2 ON c2.id=h2.coupon_entered_id
							 WHERE c2.function_type="combination"
							 GROUP BY h2.coupon_entered_id
					 ) h ON h.coupon_id=c.id
					WHERE 1=1
						' . $where . '
					GROUP BY c.id ' . $orderby;
		}

		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {

		$filter_function_type = AC()->helper->get_userstate_request( 'com_cmcoupon.coupons.filter_function_type', 'filter_function_type', '', 'cmd' );
		$filter_coupon_value_type = AC()->helper->get_userstate_request( $this->name . '.coupon_value_type', 'filter_coupon_value_type', '' );
		$filter_state = AC()->helper->get_userstate_request( $this->name . '.filter_state', 'filter_state', '' );
		$filter_discount_type = AC()->helper->get_userstate_request( $this->name . '.discount_type', 'filter_discount_type', '' );
		$filter_template = AC()->helper->get_userstate_request( $this->name . '.template', 'filter_template', '' );
		$filter_tag = AC()->helper->get_userstate_request( $this->name . '.tag', 'filter_tag', '' );
		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );

		$where = array();

		if ( $filter_state ) {
			if ( 'published' === $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = 'c.state="published"
				   AND ( ((c.startdate IS NULL OR c.startdate="") 	AND (c.expiration IS NULL OR c.expiration="")) OR
						 ((c.expiration IS NULL OR c.expiration="") AND c.startdate<="' . $current_date . '") OR
						 ((c.startdate IS NULL OR c.startdate="") 	AND c.expiration>="' . $current_date . '") OR
						 (c.startdate<="' . $current_date . '"      AND c.expiration>="' . $current_date . '")
					   )
				';
			} elseif ( 'unpublished' === $filter_state ) {
				$current_date = date( 'Y-m-d H:i:s' );
				$where[] = '(c.state="unpublished" OR c.startdate>"' . $current_date . '" OR c.expiration<"' . $current_date . '")';
			} else {
				$where[] = 'c.state="' . AC()->db->escape( $filter_state ) . '"';
			}
		}
		if ( $filter_coupon_value_type ) {
			$where[] = 'c.coupon_value_type = \'' . $filter_coupon_value_type . '\'';
		}
		if ( $filter_discount_type ) {
			$where[] = 'c.discount_type = \'' . $filter_discount_type . '\'';
		}
		if ( $filter_function_type ) {
			$where[] = 'c.function_type = \'' . $filter_function_type . '\'';
		}
		if ( $filter_template ) {
			$where[] = '(c.id=' . $filter_template . ' OR c.template_id=' . $filter_template . ')';
		}
		if ( $filter_tag ) {
			$where[] = 't.tag = \'' . $filter_tag . '\'';
		}
		if ( $search ) {
			$s = '"%' . AC()->db->escape( strtolower( trim( $search ) ), true ) . '%"';
			$where[] = ' (LOWER(c.coupon_code) LIKE ' . $s . ' OR c.note LIKE ' . $s . ' OR c.upc LIKE ' . $s . ') ';
		}

		$where = ( count( $where ) ? ' AND ' . implode( ' AND ', $where ) : '' );

		return $where;
	}

	/**
	 * Publish or unpublish coupon
	 *
	 * @param array $cid the values.
	 * @param int   $publish publish or unpublish.
	 */
	public function publish( $cid = array(), $publish = 'published' ) {

		if ( count( $cid ) ) {
			AC()->db->query( 'UPDATE #__cmcoupon SET state = "' . AC()->db->escape( $publish ) . '" WHERE id IN (' . AC()->helper->scrubids( $cid ) . ') AND state IN ("published", "unpublished")' );
		}
		return true;
	}

	/**
	 * Delete coupons
	 *
	 * @param array $cids the items to delete.
	 */
	public function delete( $cids ) {

		$cids = AC()->helper->scrubids( $cids );
		if ( empty( $cids ) ) {
			return true;
		}

		AC()->db->query( 'DELETE FROM #__cmcoupon_tag WHERE coupon_id IN (' . $cids . ')' );

		AC()->db->query( 'DELETE FROM #__cmcoupon_history WHERE coupon_id IN (' . $cids . ')' );

		AC()->db->query( 'DELETE FROM #__cmcoupon_asset WHERE coupon_id IN (' . $cids . ')' );

		AC()->db->query( 'DELETE FROM #__cmcoupon_customer_balance WHERE coupon_id IN (' . $cids . ')' );

		AC()->db->query( 'DELETE FROM #__cmcoupon WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Copy coupon
	 *
	 * @param int $id coupon_id.
	 */
	public function copy( $id ) {
		$template_id = (int) $id;
		if ( empty( $template_id ) ) {
			return false;
		}

		$rtn = AC()->coupon->generate( $template_id );
		if ( empty( $rtn->coupon_id ) ) {
			return false;
		}
		return $rtn;
	}

	/**
	 * Get coupon properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_instance( '#__cmcoupon', 'id', $this->_id );

		if ( ! empty( $this->_entry->id ) ) {

			$this->_entry->is_editable = 'balance' === $this->_entry->state ? false : true;

			$this->_entry->startdate_date = ! empty( $this->_entry->startdate ) ? substr( $this->_entry->startdate, 0, 10 ) : '';
			$this->_entry->startdate_time = ! empty( $this->_entry->startdate ) ? substr( $this->_entry->startdate, 11, 8 ) : '';
			$this->_entry->expiration_date = ! empty( $this->_entry->expiration ) ? substr( $this->_entry->expiration, 0, 10 ) : '';
			$this->_entry->expiration_time = ! empty( $this->_entry->expiration ) ? substr( $this->_entry->expiration, 11, 8 ) : '';
			$this->_entry->min_value_type = null;
			$this->_entry->min_qty_type = null;
			$this->_entry->min_qty = null;
			$this->_entry->exclude_special = null;
			$this->_entry->exclude_discounted = null;
			$this->_entry->exclude_giftcert = null;
			$this->_entry->exclusive = null;

			$this->_entry->process_type = null;
			$this->_entry->max_discount_qty = null;
			$this->_entry->max_discount_amt = null;
			$this->_entry->product_match = null;
			$this->_entry->addtocart = null;

			$this->_entry->tags = null;
			$this->_entry->params = json_decode( $this->_entry->params );
			$this->_entry->asset = AC()->store->get_coupon_asset( $this->_entry );

			if ( ! empty( $this->_entry->params->min_value_type ) ) {
				$this->_entry->min_value_type = $this->_entry->params->min_value_type;
			}
			if ( ! empty( $this->_entry->params->exclusive ) ) {
				$this->_entry->exclusive = $this->_entry->params->exclusive;
			}
			if ( ! empty( $this->_entry->params->exclude_special ) ) {
				$this->_entry->exclude_special = $this->_entry->params->exclude_special;
			}
			if ( ! empty( $this->_entry->params->exclude_discounted ) ) {
				$this->_entry->exclude_discounted = $this->_entry->params->exclude_discounted;
			}
			if ( ! empty( $this->_entry->params->exclude_giftcert ) ) {
				$this->_entry->exclude_giftcert = $this->_entry->params->exclude_giftcert;
			}
			if ( ! empty( $this->_entry->params->min_qty_type ) ) {
				$this->_entry->min_qty_type = $this->_entry->params->min_qty_type;
			}
			if ( ! empty( $this->_entry->params->min_qty ) ) {
				$this->_entry->min_qty = $this->_entry->params->min_qty;
			}
			if ( ! empty( $this->_entry->params->max_discount_amt ) ) {
				$this->_entry->max_discount_amt = $this->_entry->params->max_discount_amt;
			}

			if ( 'buyxy' === $this->_entry->function_type ) {

				$this->_entry->process_type = $this->_entry->params->process_type;
				$this->_entry->max_discount_qty = $this->_entry->params->max_discount_qty;
				$this->_entry->product_match = isset( $this->_entry->params->product_match ) ? $this->_entry->params->product_match : 0;
				$this->_entry->addtocart = isset( $this->_entry->params->addtocart ) ? $this->_entry->params->addtocart : 0;

			} elseif ( 'buyxy2' === $this->_entry->function_type ) {

				$this->_entry->process_type = $this->_entry->params->process_type;
				$this->_entry->max_discount_qty = $this->_entry->params->max_discount_qty;

			} elseif ( 'combination' === $this->_entry->function_type ) {

				$this->_entry->process_type = $this->_entry->params->process_type;
			}

			if ( 'combination' !== $this->_entry->function_type ) {
				$sql = 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_id=' . $this->_id . ' GROUP BY coupon_id';
			} else {
				$sql = 'SELECT COUNT(DISTINCT order_id) FROM #__cmcoupon_history WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_entered_id=' . $this->_id . ' GROUP BY coupon_entered_id';
			}
			$this->_entry->num_used = (int) AC()->db->get_value( $sql );

			$this->_entry->giftcert_used = 0;
			$this->_entry->giftcert_balance = 0;
			if ( 'giftcert' === $this->_entry->function_type ) {
				$this->_entry->giftcert_balance = AC()->coupon->get_giftcert_balance( $this->_id );
				$this->_entry->giftcert_used = $this->_entry->coupon_value - $this->_entry->giftcert_balance;
			}

			$atags = array();
			$tmp = AC()->db->get_objectlist( 'SELECT tag FROM #__cmcoupon_tag WHERE coupon_id=' . $this->_id );
			foreach ( $tmp as $t ) {
				$atags[] = $t->tag;
			}
			$this->_entry->tags = $atags;

		} else {

			$this->_entry = AC()->db->get_table_columns( '#__cmcoupon' );
			$this->_entry->is_editable = true;

			$this->_entry->expiration_date = '';
			$this->_entry->expiration_time = '';
			$this->_entry->startdate_date = '';
			$this->_entry->startdate_time = '';

			$this->_entry->min_qty = '';
			$this->_entry->min_qty_type = null;
			$this->_entry->min_value_type = null;

			$this->_entry->process_type = null;
			$this->_entry->asset2_function_type = null;
			$this->_entry->max_discount_qty = null;
			$this->_entry->max_discount_amt = null;
			$this->_entry->exclude_special = null;
			$this->_entry->exclude_discounted = null;
			$this->_entry->exclude_giftcert = null;
			$this->_entry->exclusive = null;
			$this->_entry->product_match = null;
			$this->_entry->addtocart = null;

			$this->_entry->tags = null;
		}
		return $this->_entry;
	}

	/**
	 * Save coupon
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		// Scrub assets.
		if ( 'buyxy' === $data['function_type'] && isset( $data['product_match'] ) && 1 === (int) $data['product_match'] && ! empty( $data['asset'][1]['rows'] ) && ! empty( $data['asset'][2]['rows'] ) ) {
			$data['asset'][2]['rows'] = $data['asset'][1]['rows'];
		}
		$data['asset_'] = $this->asset_post_to_db( $data['asset'] );
		$data['asset_param'] = $this->asset_post_to_db( $data['asset'] );
		foreach ( $data['asset_param'] as $key => $r0 ) {
			foreach ( $r0->rows as $k1 => $r1 ) {
				unset( $data['asset_param'][ $key ]->rows->{$k1}->rows );
			}
		}

		$asset0_types = array( 'product', 'category', 'vendor', 'manufacturer', 'custom' );
		$is_specific_assets = false;
		if ( 'buyxy' === $data['function_type'] ) {
			if ( 'buyxy1' === $data['function_type_buyxy'] ) {
				$asset_index = 1;
			}
			else {
				$asset_index = 3;
			}
		}
		else {
			$asset_index = 0;
		}
		foreach ( $asset0_types as $asset0_type ) {
			if ( ! empty( $data['asset_'][ $asset_index ]->rows->{$asset0_type}->rows ) ) {
				$is_specific_assets = true;
				break;
			}
		}
		$data['is_specific_assets'] = $is_specific_assets;

		// Set null fields.
		$data['params'] = null;

		$data['product_match'] = empty( $data['product_match'] ) ? 0 : 1;
		$data['addtocart'] = empty( $data['addtocart'] ) ? 0 : 1;

		$coupon  = AC()->db->get_table_instance( '#__cmcoupon', 'id', $data['id'] );
		if ( 'balance' === $coupon->state ) {
			$errors[] = AC()->lang->__( 'Cannot edit customer balance' );
			return $errors;
		}

		$row = AC()->db->get_table_instance( '#__cmcoupon', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		$allow_negative_value = (int) AC()->param->get( 'enable_negative_value_coupon', 0 );
		if ( ! isset( $data['coupon_value'] ) || ! is_numeric( $data['coupon_value'] ) || ( 1 !== $allow_negative_value && $data['coupon_value'] < 0 ) ) {
			$row->coupon_value = null;
		}
		if ( empty( $data['coupon_value_def'] ) ) {
			$row->coupon_value_def = null;
		}
		if ( empty( $data['num_of_uses_total'] ) ) {
			$row->num_of_uses_total = null;
		}
		if ( empty( $data['num_of_uses_customer'] ) ) {
			$row->num_of_uses_customer = null;
		}
		if ( empty( $data['min_value'] ) ) {
			$row->min_value = null;
		}
		if ( empty( $data['discount_type'] ) ) {
			$row->discount_type = null;
		}
		$row->startdate = null;
		$row->expiration = null;
		if ( ! empty( $data['startdate_date'] ) && 'YYYY-MM-DD' !== $data['startdate_date'] ) {
			$row->startdate = $data['startdate_date'] . ' ' . trim( ! empty( $data['startdate_time'] ) && 'hh:mm:ss' !== $data['startdate_time'] ? $data['startdate_time'] : '00:00:00' );
		}
		if ( ! empty( $data['expiration_date'] ) && 'YYYY-MM-DD' !== $data['expiration_date'] ) {
			$row->expiration = $data['expiration_date'] . ' ' . trim( ! empty( $data['expiration_time'] ) && 'hh:mm:ss' !== $data['expiration_time'] ? $data['expiration_time'] : '23:59:59' );
		}
		if ( empty( $data['order_id'] ) ) {
			$row->order_id = null;
		}
		if ( empty( $data['template_id'] ) ) {
			$row->template_id = null;
		}
		if ( empty( $data['note'] ) ) {
			$row->note = null;
		}
		if ( empty( $data['upc'] ) ) {
			$row->upc = null;
		}

		// Sanitise fields.
		$row->id = (int) $row->id;
		$row->estore = CMCOUPON_ESTORE;
		$row->coupon_code = trim( $row->coupon_code );
		if ( 'buyxy' === $row->function_type ) {
			if ( 'buyxy2' === $data['function_type_buyxy'] ) {
				$row->function_type = 'buyxy2';
			}
		}

		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) || ( ! empty( $data['is_error_check_only'] ) && true === $data['is_error_check_only'] ) ) {
			return $errors;
		}

		if ( empty( $row->passcode ) ) {
			$row->passcode = substr( md5( (string) time() . rand( 1, 1000 ) . $row->coupon_code ), 0, 6 );
		}

		// Correct invalid data.
		$params = array();
		if ( ! empty( $data['exclusive'] ) ) {
			$params['exclusive'] = 1;
		}
		if ( 'coupon' === $row->function_type ) {

			if ( 'basic' === $data['couponvalue_hidden'] ) {
				$row->coupon_value_def = null;
			} else {
				$row->coupon_value = null;
			}

			if ( ! empty( $data['min_value'] ) ) {
				if ( empty( $data['min_value_type'] ) || ! $data['is_specific_assets'] ) {
					$data['min_value_type'] = 'overall';
				}
				$params['min_value_type'] = $data['min_value_type'];
			}
			$data['min_qty'] = (int) $data['min_qty'];
			if ( ! empty( $data['min_qty'] ) && $data['min_qty'] > 0 && ! empty( $data['min_qty_type'] ) ) {
				$params['min_qty'] = $data['min_qty'];
				$params['min_qty_type'] = $data['min_qty_type'];
			}

			$data['max_discount_amt'] = (float) $data['max_discount_amt'];
			if ( ! empty( $data['max_discount_amt'] ) && $data['max_discount_amt'] > 0 ) {
				$params['max_discount_amt'] = $data['max_discount_amt'];
			}

			if ( ! empty( $data['exclude_special'] ) ) {
				$params['exclude_special'] = 1;
			}
			if ( ! empty( $data['exclude_discounted'] ) ) {
				$params['exclude_discounted'] = 1;
			}
			if ( ! empty( $data['exclude_giftcert'] ) ) {
				$params['exclude_giftcert'] = 1;
			}
		} elseif ( 'shipping' === $row->function_type ) {

			$row->coupon_value_def = null;
			if ( ! $data['is_specific_assets'] ) {
				$row->discount_type = null;
			}

			if ( ! empty( $data['min_value'] ) ) {
				if ( empty( $data['min_value_type'] ) || ! $data['is_specific_assets'] ) {
					$data['min_value_type'] = 'overall';
				}
				$params['min_value_type'] = $data['min_value_type'];
			}
			$data['min_qty'] = (int) $data['min_qty'];
			if ( ! empty( $data['min_qty'] ) && $data['min_qty'] > 0 && ! empty( $data['min_qty_type'] ) ) {
				$params['min_qty'] = $data['min_qty'];
				$params['min_qty_type'] = $data['min_qty_type'];
			}
			$data['max_discount_amt'] = (float) $data['max_discount_amt'];
			if ( ! empty( $data['max_discount_amt'] ) && $data['max_discount_amt'] > 0 ) {
				$params['max_discount_amt'] = $data['max_discount_amt'];
			}
		} elseif ( 'buyxy' === $row->function_type ) {

			$params['process_type'] = $data['process_type_buyxy'];
			$params['max_discount_qty'] = $data['max_discount_qty'];
			if ( ! empty( $data['product_match'] ) ) {
				$params['product_match'] = $data['product_match'];
			}
			$params['addtocart'] = $data['addtocart'];

			if ( isset( $params['product_match'] ) && 1 === (int) $params['product_match'] ) {
				$data['asset_'][2]->rows = $data['asset_'][1]->rows;
			}

			$row->coupon_value_def = null;
			$row->discount_type = null;
			if ( ! empty( $data['exclude_special'] ) ) {
				$params['exclude_special'] = 1;
			}
			if ( ! empty( $data['exclude_discounted'] ) ) {
				$params['exclude_discounted'] = 1;
			}
			if ( ! empty( $data['exclude_giftcert'] ) ) {
				$params['exclude_giftcert'] = 1;
			}

			if ( ! empty( $data['min_value'] ) ) {
				if ( empty( $data['min_value_type'] ) || ! $data['is_specific_assets'] ) {
					$data['min_value_type'] = 'overall';
				}
				$params['min_value_type'] = $data['min_value_type'];
			}

			$data['max_discount_amt'] = (float) $data['max_discount_amt'];
			if ( ! empty( $data['max_discount_amt'] ) && $data['max_discount_amt'] > 0 ) {
				$params['max_discount_amt'] = $data['max_discount_amt'];
			}
		} elseif ( 'buyxy2' === $row->function_type ) {

			$params['process_type'] = $data['process_type_buyxy'];
			$params['max_discount_qty'] = $data['max_discount_qty'];

			$row->coupon_value_def = null;
			$row->discount_type = null;
			if ( ! empty( $data['exclude_special'] ) ) {
				$params['exclude_special'] = 1;
			}
			if ( ! empty( $data['exclude_discounted'] ) ) {
				$params['exclude_discounted'] = 1;
			}
			if ( ! empty( $data['exclude_giftcert'] ) ) {
				$params['exclude_giftcert'] = 1;
			}

			if ( ! empty( $data['min_value'] ) ) {
				if ( empty( $data['min_value_type'] ) || ! $data['is_specific_assets'] ) {
					$data['min_value_type'] = 'overall';
				}
				$params['min_value_type'] = $data['min_value_type'];
			}

			$data['max_discount_amt'] = (float) $data['max_discount_amt'];
			if ( ! empty( $data['max_discount_amt'] ) && $data['max_discount_amt'] > 0 ) {
				$params['max_discount_amt'] = $data['max_discount_amt'];
			}
		} elseif ( 'combination' === $row->function_type ) {

			$params['process_type'] = $data['process_type_combination'];
			$row->coupon_value_type = null;
			$row->coupon_value = null;
			$row->coupon_value_def = null;
			$row->min_value = null;
			$row->discount_type = null;

		} elseif ( 'giftcert' === $row->function_type ) {
			$row->coupon_value_type = 'amount';
			$row->coupon_value_def = null;
			$row->num_of_uses_total = null;
			$row->num_of_uses_customer = null;
			$row->min_value = null;
			$row->discount_type = null;
		}
		if ( ! empty( $data['asset_param'] ) ) {
			$params['asset'] = $data['asset_param'];
		}

		$row->params = ! empty( $params ) ? AC()->helper->json_encode( $params ) : null;

		$row = AC()->db->save_table_instance( '#__cmcoupon', $row );

		if ( ! empty( $row->id ) ) {
			AC()->db->query( 'DELETE FROM #__cmcoupon_asset WHERE coupon_id = ' . $row->id );
			AC()->db->query( 'DELETE FROM #__cmcoupon_tag WHERE coupon_id = ' . $row->id );
		}

		if ( ! empty( $data['tags'] ) ) {
			$tags = $data['tags'];
			$insert_str = '';
			foreach ( $tags as $tmp ) {
				$insert_str .= '(' . $row->id . ',\'' . trim( $tmp ) . '\'),';
			}
			AC()->db->query( 'INSERT INTO #__cmcoupon_tag (coupon_id, tag) VALUES ' . substr( $insert_str, 0, -1 ) );
		}

		if ( ! empty( $data['asset_'] ) ) {
			$sql_array = array();
			foreach ( $data['asset_'] as $asset_key => $r0 ) {
				foreach ( $r0->rows as $asset_type => $r1 ) {
					$i = 0;
					foreach ( $r1->rows as $r2 ) {
						$qty = 'NULL';
						if ( isset( $r2->qty ) ) {
							$r2->qty = (int) $r2->qty;
							if ( $r2->qty <= 0 ) {
								continue;
							}
							$qty = (int) $r2->qty;
						}
						$order_by = 'NULL';
						if ( 'coupon' === $asset_type ) {
							$order_by = ++$i;
						}
						$sql_array[] = '(' . $row->id . ',' . $asset_key . ',"' . $asset_type . '",' . $qty . ',"' . AC()->db->escape( $r2->asset_id ) . '",' . $order_by . ')';
					}
				}
			}
			if ( ! empty( $sql_array ) ) {
				AC()->db->query( 'INSERT INTO #__cmcoupon_asset (coupon_id, asset_key, asset_type, qty, asset_id, order_by) VALUES ' . implode( ',', $sql_array ) );
			}
		}

		$this->_entry = $row;
	}

	/**
	 * Check coupon before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {
		$err = array();

		$allow_negative_value = (int) AC()->param->get( 'enable_negative_value_coupon', 0 );

		if ( empty( $row->coupon_code ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) );
		}
		$check = AC()->helper->vars( 'estore', $row->estore );
		if ( empty( $check ) ) {
			$err[] = AC()->lang->__( 'Error' );
		}

		if ( 'giftcert' === $row->function_type ) {
			if ( ! is_numeric( $row->coupon_value ) || ( 1 !== $allow_negative_value && $row->coupon_value < 0 ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
			}
		} elseif ( 'combination' === $row->function_type ) {
			if ( empty( $post['process_type_combination'] ) || ! in_array( $post['process_type_combination'], array( 'first', 'all', 'allonly', 'lowest', 'highest' ), true ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'Process Type' ) );
			}
		} else {
			$check = AC()->helper->vars( 'coupon_value_type', $row->coupon_value_type );
			if ( empty( $check ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Percent or Amount' ) );
			}

			if ( 'coupon' === $row->function_type ) {

				if ( 'basic' === $post['couponvalue_hidden'] ) {
					if ( ! is_numeric( $row->coupon_value ) || ( 1 !== $allow_negative_value && $row->coupon_value < 0 ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
					}
				} elseif ( 'advanced' === $post['couponvalue_hidden'] ) {
					if ( ! preg_match( '/^(\d+\-\d+([.]\d+)?;)+(\[[_a-z]+\=[a-z]+(\&[_a-z]+\=[a-z]+)*\])?$/', $row->coupon_value_def ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value Definition' ) );
					}
				}
				if ( empty( $row->discount_type ) || ! in_array( $row->discount_type, array( 'specific', 'overall' ), true ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Discount Type' ) );
				}
			} elseif ( 'shipping' === $row->function_type ) {
				if ( ! is_numeric( $row->coupon_value ) || ( 1 !== $allow_negative_value && $row->coupon_value < 0 ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
				}
				if ( ! empty( $row->discount_type ) && ! in_array( $row->discount_type, array( 'specific', 'overall' ), true ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Discount Type' ) );
				}
			} elseif ( 'buyxy' === $row->function_type ) {
				if ( ! is_numeric( $row->coupon_value ) || ( 1 !== $allow_negative_value && $row->coupon_value < 0 ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
				}
			} elseif ( 'buyxy2' === $row->function_type ) {
				if ( ! is_numeric( $row->coupon_value ) || ( 1 !== $allow_negative_value && $row->coupon_value < 0 ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) );
				}
			} else {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Function Type' ) );
			}

			if ( ! empty( $row->num_of_uses_total ) && ! is_numeric( $row->num_of_uses_total ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Number of Uses Total' ) );
			}
			if ( ! empty( $row->num_of_uses_customer ) && ! is_numeric( $row->num_of_uses_customer ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Number of Uses per Customer' ) );
			}
			if ( ! empty( $row->min_value ) && ! is_numeric( $row->min_value ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Minimum Value' ) );
			}
		}

		$is_start = true;
		if ( ! empty( $row->startdate ) ) {
			if ( ! preg_match( '/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/', $row->startdate ) ) {
				$is_start = false;
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Start Date' ) );
			} else {
				list( $dtmp, $ttmp ) = explode( ' ', $row->startdate );
				list( $y, $mnt, $d ) = explode( '-', $dtmp );
				list( $h, $m, $s ) = explode( ':', $ttmp );
				if ( $y > 2100 || $mnt > 12 || $d > 31 || $h > 23 || $m > 59 || $s > 59 ) {
					$is_start = false;
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Start Date' ) );
				}
			}
		} else {
			$is_start = false;
		}
		$is_end = true;
		if ( ! empty( $row->expiration ) ) {
			if ( ! preg_match( '/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/', $row->expiration ) ) {
				$is_end = true;
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) );
			} else {
				list( $dtmp, $ttmp ) = explode( ' ', $row->expiration );
				list( $y, $mnt, $d) = explode( '-', $dtmp );
				list( $h, $m, $s) = explode( ':', $ttmp );
				if ( $y > 2100 || $mnt > 12 || $d > 31 || $h > 23 || $m > 59 || $s > 59 ) {
					$is_end = true;
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) );
				}
			}
		} else {
			$is_end = false;
		}
		if ( $is_start && $is_end ) {
			list( $dtmp, $ttmp ) = explode( ' ', $row->startdate );
			list( $y, $mnt, $d ) = explode( '-', $dtmp );
			list( $h, $m, $s ) = explode( ':', $ttmp );
			$c1 = (int) $y . $mnt . $d . '.' . $h . $m . $s;
			list( $dtmp, $ttmp ) = explode( ' ', $row->expiration );
			list( $y, $mnt, $d ) = explode( '-', $dtmp );
			list( $h, $m, $s ) = explode( ':', $ttmp );
			$c2 = (int) $y . $mnt . $d . '.' . $h . $m . $s;
			if ( $c1 > $c2 ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Start Date' ) . '/' . AC()->lang->__( 'Expiration' ) );
			}
		}
		if ( ! empty( $row->order_id ) && ! AC()->helper->pos_int( $row->order_id ) ) {
			$err[] = AC()->lang->__( 'Invalid' );
		}
		if ( ! empty( $row->template_id ) && ! AC()->helper->pos_int( $row->template_id ) ) {
			$err[] = AC()->lang->__( 'Invalid' );
		}
		if ( empty( $row->state ) || ! in_array( $row->state, array( 'published', 'unpublished', 'template' ), true ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'State' ) );
		}

		if ( empty( $row->id ) ) {
			$tmp = AC()->db->get_objectlist( 'SELECT id FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_code = \'' . AC()->db->escape( $row->coupon_code ) . '\'' );
			if ( ! empty( $tmp ) ) {
				$err[] = AC()->lang->__( 'That coupon code already exists. Please try again' );
			}
		} else {
			$tmp = AC()->db->get_objectlist( 'SELECT id FROM #__cmcoupon WHERE estore="' . CMCOUPON_ESTORE . '" AND coupon_code = \'' . AC()->db->escape( $row->coupon_code ) . '\' AND id NOT IN (' . $row->id . ')' );
			if ( ! empty( $tmp ) ) {
				$err[] = AC()->lang->__( 'That coupon code already exists. Please try again' );
			}
		}

		if ( 'combination' === $row->function_type && empty( $post['asset_'][0]->rows->coupon->rows ) ) {
			$err[] = AC()->lang->_e_select( AC()->lang->__( 'Coupon' ) );
		}

		if ( 'coupon' === $row->function_type && ! $post['is_specific_assets'] && 'specific' === $row->discount_type ) {
			$err[] = AC()->lang->__( 'Please select at least one product for discount type of specific' );
		}

		if ( 'buyxy' === $row->function_type ) {
			if ( empty( $post['process_type_buyxy'] ) || ! in_array( $post['process_type_buyxy'], array( 'first', 'lowest', 'highest' ), true ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'Process Type' ) );
			}
			if ( ! empty( $post['max_discount_qty'] ) && ! AC()->helper->pos_int( $post['max_discount_qty'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Maximum Discount Qty' ) );
			}

			if ( empty( $post['asset_'][1]->rows ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'BuyX' ) );
			}
			if ( 0 === (int) $post['product_match'] && empty( $post['asset_'][2]->rows ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'GetY' ) );
			}

			if ( empty( $post['asset_'][1]->qty ) || ! AC()->helper->pos_int( $post['asset_'][1]->qty ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'BuyX' ) . ' -> ' . AC()->lang->__( 'Number' ) );
			}
			if ( empty( $post['asset_'][2]->qty ) || ! AC()->helper->pos_int( $post['asset_'][2]->qty ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'GetY' ) . ' -> ' . AC()->lang->__( 'Number' ) );
			}
		}

		if ( 'buyxy2' === $row->function_type ) {
			if ( empty( $post['process_type_buyxy'] ) || ! in_array( $post['process_type_buyxy'], array( 'first', 'lowest', 'highest' ), true ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'Process Type' ) );
			}
			if ( ! empty( $post['max_discount_qty'] ) && ! AC()->helper->pos_int( $post['max_discount_qty'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Maximum Discount Qty' ) );
			}
			if ( empty( $post['asset_'][3]->rows ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'BuyX' ) );
			}
			if ( empty( $post['asset_'][4]->rows ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'GetY' ) );
			}
		}

		return $err;
	}

	/**
	 * Generat coupons from template
	 *
	 * @param array $data input.
	 */
	public function generate_multiple( $data ) {
		$errors = '';

		$number = (int) $data['number'];
		$template_id = (int) $data['template'];
		if ( empty( $template_id ) ) {
			$errors[] = AC()->lang->__( 'Coupon template not found' );
			return $errors;
		}

		$processing_loop = array();
		if ( ! empty( $number ) && $number > 0 ) {
			$processing_loop = array_fill( 0, $number, null );
		} else {
			$lines = explode( "\r\n", $data['coupon_codes'] );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( empty( $line ) ) {
					continue;
				}
				$processing_loop[] = $line;
			}
		}
		if ( empty( $processing_loop ) ) {
			$errors[] = AC()->lang->__( 'No copies selected' );
			return $errors;
		}

		foreach ( $processing_loop as $coupon_code ) {
			AC()->coupon->generate( $template_id, $coupon_code );
		}
	}

	/**
	 * Get asset information from coupon and make it into readable object
	 *
	 * @param arra $inasset the asset.
	 */
	public function asset_post_to_db( $inasset ) {
		$asset = array();
		if ( empty( $inasset ) ) {
			return $asset;
		}

		foreach ( $inasset as $key => $r0 ) {
			if ( ! isset( $asset[ $key ] ) ) {
				$asset[ $key ] = new stdClass();
			}
			if ( ! empty( $r0['rows'] ) ) {
				foreach ( $r0['rows'] as $k2 => $r2 ) {
					if ( empty( $r2['rows'] ) ) {
						continue;
					}
					if ( ! isset( $asset[ $key ]->rows ) ) {
						if ( isset( $r0['qty'] ) ) {
							$asset[ $key ]->qty = $r0['qty'];
						}
						$asset[ $key ]->rows = new stdClass();
					}
					if ( ! isset( $asset[ $key ]->rows->{$k2} ) ) {
						$asset[ $key ]->rows->{$k2} = new stdClass();
					}
					$asset[ $key ]->rows->{$k2}->type = $k2;
					if ( isset( $r2['mode'] ) ) {
						$asset[ $key ]->rows->{$k2}->mode = $r2['mode'];
					}
					if ( isset( $r2['qty'] ) ) {
						$asset[ $key ]->rows->{$k2}->qty = $r2['qty'];
					}
					if ( isset( $r2['distinct'] ) ) {
						$asset[ $key ]->rows->{$k2}->distinct = $r2['distinct'];
					}
					foreach ( $r2['rows'] as $k3 => $r3 ) {
						if ( empty( $r3['asset_id'] ) ) {
							continue;
						}
						if ( ! isset( $asset[ $key ]->rows->{$k2}->rows ) ) {
							$asset[ $key ]->rows->{$k2}->rows = array();
							if ( isset( $r2['country'] ) ) {
								$asset[ $key ]->rows->{$k2}->country = $r2['country'];
							}
						}

						if ( is_array( $r3['asset_id'] ) ) {
							$r3['asset_id'] = current( $r3['asset_id'] );
						}
						$vals = new stdclass();
						$vals->asset_id = $r3['asset_id'];
						if ( isset( $r3['asset_name'] ) ) {
							$vals->asset_name = $r3['asset_name'];
						}
						if ( isset( $r3['qty'] ) ) {
							$vals->qty = $r3['qty'];
						}

						$asset[ $key ]->rows->{$k2}->rows[ $vals->asset_id ] = $vals;
					}
					if ( empty( $asset[ $key ]->rows->{$k2}->rows ) ) {
						unset( $asset[ $key ]->rows->{$k2} );
					}
				}
			}
			if ( empty( $asset[ $key ]->rows ) ) {
				unset( $asset[ $key ] );
			}
		}

		return $asset;
	}
}
