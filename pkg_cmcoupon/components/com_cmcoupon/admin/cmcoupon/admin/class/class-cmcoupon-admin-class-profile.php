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
 */
class CmCoupon_Admin_Class_Profile extends CmCoupon_Library_Class {

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'profile';
		$this->_id = $id;
		$this->_orderby = 'title';
		$this->_primary = 'title';
		parent::__construct();
	}

	/**
	 * Column list
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" onclick="jQuery(this.form).find(\'td.checkcolumn input:checkbox\').prop(\'checked\',this.checked);" />',
			'title' => AC()->lang->__( 'Title' ),
			'from_name' => AC()->lang->__( 'From Name' ),
			'from_email' => AC()->lang->__( 'From Email' ),
			'email_text' => AC()->lang->__( 'E-mail' ),
			'email_subject' => AC()->lang->__( 'Email Subject' ),
			'id' => AC()->lang->__( 'ID' ),
		);
		return $columns;
	}

	/**
	 * Sortable columns
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'id'  => 'id',
			'title' => 'title',
			'from_name' => 'from_name',
			'from_email' => 'from_email',
			'email_subject' => 'email_subject',
		);
		return $sortable_columns;
	}

	/**
	 * Action row column
	 *
	 * @param object $row the object.
	 */
	protected function get_row_action( $row ) {

		if ( 1 === (int) $row->is_default ) {
			return array(
				'edit' => '<a href="#/cmcoupon/profile/edit?id=' . $row->id . '">' . AC()->lang->__( 'Edit' ) . '</a>',
				'preview' => '<a href="javascript:previewImage(' . $row->id . ')">' . AC()->lang->__( 'Preview' ) . '</a>',
				'copy' => '<a href="#/cmcoupon/profile?task=copy&id=' . $row->id . '">' . AC()->lang->__( 'Duplicate' ) . '</a>',
				'delete' => '<a href="#/cmcoupon/profile?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
			);
		} else {
			return array(
				'edit' => '<a href="#/cmcoupon/profile/edit?id=' . $row->id . '">' . AC()->lang->__( 'Edit' ) . '</a>',
				'default' => '<a href="#/cmcoupon/profile?task=default&id=' . $row->id . '">' . AC()->lang->__( 'Default' ) . '</a>',
				'preview' => '<a href="javascript:previewImage(' . $row->id . ')">' . AC()->lang->__( 'Preview' ) . '</a>',
				'copy' => '<a href="#/cmcoupon/profile?task=copy&id=' . $row->id . '">' . AC()->lang->__( 'Duplicate' ) . '</a>',
				'delete' => '<a href="#/cmcoupon/profile?task=delete&id=' . $row->id . '" class="submitdelete aria-button-if-js" onclick=\'return showNotice.warn();\'>' . AC()->lang->__( 'Delete' ) . '</a>',
			);
		}
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
	 * Email text column
	 *
	 * @param object $row the object.
	 */
	public function column_email_text( $row ) {
		$email_text = array();
		if ( ! empty( $row->bcc_admin ) ) {
			$email_text[] = AC()->lang->__( 'Bcc Admin' );
		}
		if ( ! empty( $row->cc_purchaser ) ) {
			$email_text[] = AC()->lang->__( 'Cc Purchaser' );
		}

		return empty( $email_text ) ? '---' : implode( ', ', $email_text );
	}

	/**
	 * Title column
	 *
	 * @param object $row the object.
	 */
	public function column_title( $row ) {
		return ( 1 === (int) $row->is_default ? '<img src="' . CMCOUPON_ASEET_URL . '/images/icon-16-default.png" border="0"  /> ' : '' ) . $row->title;
	}

	/**
	 * Get coupon data
	 */
	public function get_data() {
		parent::get_data();

		if ( ! empty( $this->_data ) ) {
			$lang = AC()->lang->get_current();

			$ids = array();
			$ptr = null;
			foreach ( $this->_data as $i => $row ) {
				$this->_data[ $i ]->email_subject = '';

				$ids[] = $row->id;
				$ptr[ $row->id ]['email_subject'] = &$this->_data[ $i ]->email_subject;
			}

			$rows = AC()->db->get_objectlist('
				SELECT elem_id,p.id as profile_id,text FROM #__cmcoupon_lang_text l
				  JOIN #__cmcoupon_profile p ON p.idlang_email_subject=l.elem_id
				 WHERE lang="' . $lang . '" AND p.id IN (' . implode( ',', $ids ) . ')
			');
			foreach ( $rows as $tmp ) {
				$ptr[ $tmp->profile_id ]['email_subject'] = $tmp->text;
			}
		}

		return $this->_data;
	}

	/**
	 * Build coupon list query
	 */
	public function buildquery() {
		// Get the WHERE, and ORDER BY clauses for the query.
		$where = $this->buildquery_where();
		$orderby = $this->buildquery_orderby();

		$sql = 'SELECT * FROM #__cmcoupon_profile
				 WHERE 1=1 
				 ' . ( ! empty( $where ) ? ' AND ' . implode( ' AND ', $where ) : '' ) . ' 
				 ' . ( ! empty( $orderby ) ? ' ORDER BY ' . $orderby : '' ) . '
		';
		return $sql;
	}

	/**
	 * Query where clause
	 */
	public function buildquery_where() {

		$search = AC()->helper->get_userstate_request( $this->name . '.search', 'search', '' );

		$where = array();
		if ( ! empty( $search ) ) {
			$where[] = ' LOWER(title) LIKE "%' . AC()->db->escape( $search, true ) . '%" ';
		}

		return $where;
	}

	/**
	 * Delete items
	 *
	 * @param array $cids the items to delete.
	 */
	public function delete( $cids ) {

		$cids = AC()->helper->scrubids( $cids );
		if ( empty( $cids ) ) {
			return true;
		}

		AC()->db->query( 'DELETE FROM #__cmcoupon_profile WHERE id IN (' . $cids . ')' );

		return true;
	}

	/**
	 * Set item as default
	 *
	 * @param array $cid the item to default.
	 */
	public function set_default( $cid = array() ) {
		$cid = current( $cid );

		$tmp = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_profile WHERE id=' . (int) $cid );
		if ( ! empty( $tmp ) ) {
			AC()->db->query( 'UPDATE #__cmcoupon_profile SET is_default=NULL' );
			AC()->db->query( 'UPDATE #__cmcoupon_profile SET is_default=1 WHERE id=' . (int) $cid );
		}
		return true;
	}

	/**
	 * Copy item
	 *
	 * @param array $id the item to copy.
	 */
	public function copy( $id ) {
		$id = (int) $id;
		if ( empty( $id ) ) {
			return false;
		}

		AC()->db->query('
			INSERT INTO #__cmcoupon_profile (title,is_default,from_name,from_email,bcc_admin,cc_purchaser,image,coupon_code_config,coupon_value_config,expiration_config,freetext1_config,freetext2_config,freetext3_config)
			SELECT title,NULL,from_name,from_email,bcc_admin,cc_purchaser,image,coupon_code_config,coupon_value_config,expiration_config,freetext1_config,freetext2_config,freetext3_config
			  FROM #__cmcoupon_profile 
			 WHERE id=' . $id . '
		');
		$profile_id = (int) AC()->db->get_insertid();
		if ( empty( $profile_id ) ) {
			return false;
		}

		$vars = $this->get_language_ids( '#__cmcoupon_profile', $id );

		foreach ( $vars as $elem => $old_id ) {
			$new_id = (int) AC()->db->get_value( 'SELECT MAX(elem_id) FROM #__cmcoupon_lang_text' ) + 1;

			$affected_rows = AC()->db->query('
				INSERT INTO #__cmcoupon_lang_text (elem_id,lang,text) 
				SELECT ' . $new_id . ',lang,text FROM #__cmcoupon_lang_text WHERE elem_id=' . (int) $old_id['elem_id'] . '
			');
			if ( ! empty( $affected_rows ) ) {
				AC()->db->query( 'UPDATE #__cmcoupon_profile SET ' . $elem . '=' . $new_id . ' WHERE id=' . $profile_id );
			}
		}

		return true;
	}

	/**
	 * Get item properties
	 */
	public function get_entry() {
		$this->_entry = AC()->db->get_table_instance( '#__cmcoupon_profile', 'id', $this->_id );

		$lang = AC()->lang->get_current();
		$this->_entry->languages = array();
		$this->_entry->languages[ $lang ] = new stdClass();
		$this->_entry->languages[ $lang ]->pdf_filename = 'attachment';
		$this->_entry->languages[ $lang ]->voucher_text = AC()->lang->__( 'Gift Certificate' ) . ': {voucher}<br />' . AC()->lang->__( 'Value' ) . ': {price}{expiration_text}<br /><br />';
		$this->_entry->languages[ $lang ]->voucher_text_exp = '<br />' . AC()->lang->__( 'Expiration' ) . ': {expiration}';
		$this->_entry->languages[ $lang ]->voucher_filename = 'voucher#';

		$this->_entry->couponcode = new stdclass();
		$this->_entry->couponcode->align = '';
		$this->_entry->couponcode->padding = '';
		$this->_entry->couponcode->y = '';
		$this->_entry->couponcode->font = '';
		$this->_entry->couponcode->font_size = '';
		$this->_entry->couponcode->font_color = '';

		$this->_entry->couponvalue = new stdclass();
		$this->_entry->couponvalue->align = '';
		$this->_entry->couponvalue->padding = '';
		$this->_entry->couponvalue->y = '';
		$this->_entry->couponvalue->font = '';
		$this->_entry->couponvalue->font_size = '';
		$this->_entry->couponvalue->font_color = '';

		$this->_entry->expiration = new stdclass();
		$this->_entry->expiration->align = '';
		$this->_entry->expiration->padding = '';
		$this->_entry->expiration->y = '';
		$this->_entry->expiration->font = '';
		$this->_entry->expiration->font_size = '';
		$this->_entry->expiration->font_color = '';
		$this->_entry->expiration->text = '';

		$this->_entry->freetext1 = new stdclass();
		$this->_entry->freetext1->align = '';
		$this->_entry->freetext1->padding = '';
		$this->_entry->freetext1->y = '';
		$this->_entry->freetext1->font = '';
		$this->_entry->freetext1->font_size = '';
		$this->_entry->freetext1->font_color = '';
		$this->_entry->freetext1->maxwidth = '';
		$this->_entry->freetext1->text = '';

		$this->_entry->freetext2 = new stdclass();
		$this->_entry->freetext2->align = '';
		$this->_entry->freetext2->padding = '';
		$this->_entry->freetext2->y = '';
		$this->_entry->freetext2->font = '';
		$this->_entry->freetext2->font_size = '';
		$this->_entry->freetext2->font_color = '';
		$this->_entry->freetext2->maxwidth = '';
		$this->_entry->freetext2->text = '';

		$this->_entry->freetext3 = new stdclass();
		$this->_entry->freetext3->align = '';
		$this->_entry->freetext3->padding = '';
		$this->_entry->freetext3->y = '';
		$this->_entry->freetext3->font = '';
		$this->_entry->freetext3->font_size = '';
		$this->_entry->freetext3->font_color = '';
		$this->_entry->freetext3->maxwidth = '';
		$this->_entry->freetext3->text = '';

		$this->_entry->imgplugin = array();
		$rtn = AC()->helper->trigger( 'cmcouponOnInitEmailtemplate' );
		foreach ( $rtn as $items ) {
			foreach ( $items as $k2 => $row ) {
				if ( empty( $row->key ) ) {
					continue;
				}
				$this->_entry->imgplugin[ $row->key ][ $k2 ] = $row;
			}
		}

		if ( ! empty( $this->_entry->id ) ) {

			$this->_entry->languages = array();
			$vars = $this->get_language_ids( '#__cmcoupon_profile', $this->_entry->id );
			foreach ( $vars as $field => $field_row ) {
				$tmp = AC()->db->get_objectlist( 'SELECT elem_id,text,lang FROM #__cmcoupon_lang_text WHERE elem_id=' . (int) $field_row['elem_id'] );
				foreach ( $tmp as $item ) {
					if ( ! isset( $this->_entry->languages[ $item->lang ] ) ) {
						$this->_entry->languages[ $item->lang ] = new stdClass();
					}
					$this->_entry->languages[ $item->lang ]->{$field_row['name']} = $item->text;
				}
			}

			$tmp = json_decode( $this->_entry->coupon_code_config, true );
			$this->_entry->couponcode->align = $tmp['align'];
			$this->_entry->couponcode->padding = $tmp['pad'];
			$this->_entry->couponcode->y = $tmp['y'];
			$this->_entry->couponcode->font = $tmp['font'];
			$this->_entry->couponcode->font_size = $tmp['size'];
			$this->_entry->couponcode->font_color = $tmp['color'];

			$tmp = json_decode( $this->_entry->coupon_value_config, true );
			$this->_entry->couponvalue->align = $tmp['align'];
			$this->_entry->couponvalue->padding = $tmp['pad'];
			$this->_entry->couponvalue->y = $tmp['y'];
			$this->_entry->couponvalue->font = $tmp['font'];
			$this->_entry->couponvalue->font_size = $tmp['size'];
			$this->_entry->couponvalue->font_color = $tmp['color'];

			if ( ! empty( $this->_entry->expiration_config ) ) {
				$tmp = json_decode( $this->_entry->expiration_config, true );
				$this->_entry->expiration->align = $tmp['align'];
				$this->_entry->expiration->padding = $tmp['pad'];
				$this->_entry->expiration->y = $tmp['y'];
				$this->_entry->expiration->font = $tmp['font'];
				$this->_entry->expiration->font_size = $tmp['size'];
				$this->_entry->expiration->font_color = $tmp['color'];
				$this->_entry->expiration->text = $tmp['text'];
			}
			if ( ! empty( $this->_entry->freetext1_config ) ) {
				$tmp = json_decode( $this->_entry->freetext1_config, true );
				$this->_entry->freetext1->align = $tmp['align'];
				$this->_entry->freetext1->padding = $tmp['pad'];
				$this->_entry->freetext1->y = $tmp['y'];
				$this->_entry->freetext1->font = $tmp['font'];
				$this->_entry->freetext1->font_size = $tmp['size'];
				$this->_entry->freetext1->font_color = $tmp['color'];
				$this->_entry->freetext1->maxwidth = ! empty( $tmp['maxwidth'] ) ? $tmp['maxwidth'] : '';
				$this->_entry->freetext1->text = $tmp['text'];
			}
			if ( ! empty( $this->_entry->freetext2_config ) ) {
				$tmp = json_decode( $this->_entry->freetext2_config, true );
				$this->_entry->freetext2->align = $tmp['align'];
				$this->_entry->freetext2->padding = $tmp['pad'];
				$this->_entry->freetext2->y = $tmp['y'];
				$this->_entry->freetext2->font = $tmp['font'];
				$this->_entry->freetext2->font_size = $tmp['size'];
				$this->_entry->freetext2->font_color = $tmp['color'];
				$this->_entry->freetext2->maxwidth = ! empty( $tmp['maxwidth'] ) ? $tmp['maxwidth'] : '';
				$this->_entry->freetext2->text = $tmp['text'];
			}
			if ( ! empty( $this->_entry->freetext3_config ) ) {
				$tmp = json_decode( $this->_entry->freetext3_config, true );
				$this->_entry->freetext3->align = $tmp['align'];
				$this->_entry->freetext3->padding = $tmp['pad'];
				$this->_entry->freetext3->y = $tmp['y'];
				$this->_entry->freetext3->font = $tmp['font'];
				$this->_entry->freetext3->font_size = $tmp['size'];
				$this->_entry->freetext3->font_color = $tmp['color'];
				$this->_entry->freetext3->maxwidth = ! empty( $tmp['maxwidth'] ) ? $tmp['maxwidth'] : '';
				$this->_entry->freetext3->text = $tmp['text'];
			}

			$rtn = AC()->helper->trigger( 'cmcouponOnBeforeEditEmailtemplate', array( $this->_entry->id ) );
			foreach ( $rtn as $items ) {
				foreach ( $items as $k2 => $row ) {
					if ( empty( $row->key ) ) {
						continue;
					}
					$this->_entry->imgplugin[ $row->key ][ $k2 ] = $row;
				}
			}

		}

		return $this->_entry;
	}

	/**
	 * Save item
	 *
	 * @param array $data the data to save.
	 */
	public function save( $data ) {
		$errors = array();

		// Set null fields.
		if ( empty( $data['from_name'] ) ) {
			$data['from_name'] = null;
		}
		if ( empty( $data['from_email'] ) ) {
			$data['from_email'] = null;
		}
		if ( empty( $data['bcc_admin'] ) ) {
			$data['bcc_admin'] = 0;
		}
		if ( empty( $data['cc_purchaser'] ) ) {
			$data['cc_purchaser'] = 0;
		}
		if ( empty( $data['is_pdf'] ) ) {
			$data['is_pdf'] = null;
		}

		$row = AC()->db->get_table_instance( '#__cmcoupon_profile', 'id', (int) $data['id'] );
		$row = AC()->db->bind_table_instance( $row, $data );
		if ( ! $row ) {
			$errors[] = AC()->lang->__( 'Unable to bind item' );
		}

		// Sanitise fields.
		$row->id = (int) $row->id;
		if ( empty( $row->bcc_admin ) ) {
			$row->bcc_admin = null;
		}
		if ( empty( $row->cc_purchaser ) ) {
			$row->cc_purchaser = null;
		}

		// Make sure the data is valid.
		$tmperr = $this->validate( $row, $data );
		foreach ( $tmperr as $err ) {
			$errors[] = $err;
		}

		// Take a break and return if there are any errors.
		if ( ! empty( $errors ) ) {
			return $errors;
		}

		if ( empty( $row->image ) ) {
			$row->image = null;
		} else {
			$row->coupon_code_config = AC()->helper->json_encode(array(
				'align' => $data['img']['couponcode']['align'],
				'pad' => 'C' === $data['img']['couponcode']['align'] ? '' : $data['img']['couponcode']['padding'],
				'y' => $data['img']['couponcode']['y'],
				'font' => $data['img']['couponcode']['font'],
				'size' => $data['img']['couponcode']['font_size'],
				'color' => $data['img']['couponcode']['font_color'],
			));

			$row->coupon_value_config = AC()->helper->json_encode(array(
				'align' => $data['img']['couponvalue']['align'],
				'pad' => 'C' === $data['img']['couponvalue']['align'] ? '' : $data['img']['couponvalue']['padding'],
				'y' => $data['img']['couponvalue']['y'],
				'font' => $data['img']['couponvalue']['font'],
				'size' => $data['img']['couponvalue']['font_size'],
				'color' => $data['img']['couponvalue']['font_color'],
			));

			if ( empty( $data['img']['expiration']['text'] ) ) {
				$row->expiration_config = null;
			} else {
				$row->expiration_config = AC()->helper->json_encode(array(
					'text' => $data['img']['expiration']['text'],
					'align' => $data['img']['expiration']['align'],
					'pad' => 'C' === $data['img']['expiration']['align'] ? '' : $data['img']['expiration']['padding'],
					'y' => $data['img']['expiration']['y'],
					'font' => $data['img']['expiration']['font'],
					'size' => $data['img']['expiration']['font_size'],
					'color' => $data['img']['expiration']['font_color'],
				));
			}

			for ( $i = 1; $i < 4; $i++ ) {
				if ( empty( $data['img'][ 'freetext' . $i ]['text'] ) ) {
					$row->{'freetext' . $i . '_config'} = null;
				} else {
					$row->{'freetext' . $i . '_config'} = AC()->helper->json_encode(array(
						'text' => $data['img'][ 'freetext' . $i ]['text'],
						'align' => $data['img'][ 'freetext' . $i ]['align'],
						'pad' => 'C' === $data['img'][ 'freetext' . $i ]['align'] ? '' : $data['img'][ 'freetext' . $i ]['padding'],
						'y' => $data['img'][ 'freetext' . $i ]['y'],
						'maxwidth' => $data['img'][ 'freetext' . $i ]['maxwidth'],
						'font' => $data['img'][ 'freetext' . $i ]['font'],
						'size' => $data['img'][ 'freetext' . $i ]['font_size'],
						'color' => $data['img'][ 'freetext' . $i ]['font_color'],
					));
				}
			}
		}

		$row = AC()->db->save_table_instance( '#__cmcoupon_profile', $row );

		AC()->helper->trigger( 'cmcouponOnAfterSaveEmailtemplate', array( $row, & $data ) );

		// Add language.
		if ( ! empty( $row->id ) ) {
			$languages = AC()->lang->get_languages();
			$idlang_fields = $this->get_language_ids( '#__cmcoupon_profile', $row->id );

			foreach ( $languages as $lang ) {
				foreach ( $idlang_fields as $field => $field_row ) {

					$l_data = isset( $data['idlang'][ $lang->locale ][ $field_row['name'] ] ) ? $data['idlang'][ $lang->locale ][ $field_row['name'] ] : '';

					if ( 'idlang_voucher_filename' === $field ) {
						if ( ! empty( $l_data ) ) {

							$voucher_filename_1 = '';
							$voucher_filename_2 = '';
							$number_position = strpos( $l_data, '#' );
							if ( false === $number_position ) {
								$voucher_filename_1 = $l_data;
							} else {
								$voucher_filename_1 = substr( $l_data, 0, $number_position );
								$voucher_filename_2 = substr( $l_data, $number_position + 1 );
							}
							$voucher_filename_1 = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', rtrim( $voucher_filename_1, '.' ) ) ); // Makesafe.
							$voucher_filename_2 = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', rtrim( $voucher_filename_2, '.' ) ) ); // Makesafe.
							$l_data = $voucher_filename_1 . '#' . $voucher_filename_2;
						}
					} elseif ( 'idlang_pdf_filename' === $field ) {
						if ( ! empty( $l_data ) ) {
							$l_data = trim( preg_replace( array( '#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#' ), '', rtrim( $l_data, '.' ) ) ); // Makesafe.
						}
					}

					$elem_id = AC()->lang->save_data( $field_row['elem_id'], $l_data, $lang->locale );
					if ( ! empty( $elem_id ) ) {
						$row->{$field} = $elem_id;
						$field_row['elem_id'] = $elem_id;
					}
				}
			}
			$row = AC()->db->save_table_instance( '#__cmcoupon_profile', $row );
		}

		$this->_entry = $row;
	}

	/**
	 * Check item before saving
	 *
	 * @param object $row table row.
	 * @param array  $post data turned in.
	 */
	public function validate( $row, $post ) {

		$err = array();

		if ( empty( $row->title ) ) {
			$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Title' ) );
		}

		if ( ! empty( $row->image ) ) {

			if ( 'C' !== $post['img']['couponcode']['align'] && ( empty( $post['img']['couponcode']['padding'] ) || ! AC()->helper->pos_int( $post['img']['couponcode']['padding'] ) ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) . '=>' . AC()->lang->__( 'Padding' ) );
			}
			if ( empty( $post['img']['couponcode']['y'] ) || ! AC()->helper->pos_int( $post['img']['couponcode']['y'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) . '=>' . AC()->lang->__( 'Y-Axis' ) );
			}
			if ( empty( $post['img']['couponcode']['font'] ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'Coupon Code' ) . '=>' . AC()->lang->__( 'Font' ) );
			}
			if ( empty( $post['img']['couponcode']['font_size'] ) || ! AC()->helper->pos_int( $post['img']['couponcode']['font_size'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Coupon Code' ) . '=>' . AC()->lang->__( 'Font Size' ) );
			}

			if ( 'C' !== $post['img']['couponvalue']['align'] && ( empty( $post['img']['couponvalue']['padding'] ) || ! AC()->helper->pos_int( $post['img']['couponvalue']['padding'] ) ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) . '=>' . AC()->lang->__( 'Padding' ) );
			}
			if ( empty( $post['img']['couponvalue']['y'] ) || ! AC()->helper->pos_int( $post['img']['couponvalue']['y'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Y-Axis' ) . '=>' . AC()->lang->__( 'Padding' ) );
			}
			if ( empty( $post['img']['couponvalue']['font'] ) ) {
				$err[] = AC()->lang->_e_select( AC()->lang->__( 'Value' ) . '=>' . AC()->lang->__( 'Font' ) );
			}
			if ( empty( $post['img']['couponvalue']['font_size'] ) || ! AC()->helper->pos_int( $post['img']['couponvalue']['font_size'] ) ) {
				$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Value' ) . '=>' . AC()->lang->__( 'Font Size' ) );
			}

			if ( ! empty( $post['img']['expiration']['text'] ) ) {
				if ( 'C' !== $post['img']['expiration']['align'] && ( empty( $post['img']['expiration']['padding'] ) || ! AC()->helper->pos_int( $post['img']['expiration']['padding'] ) ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) . '=>' . AC()->lang->__( 'Padding' ) );
				}
				if ( empty( $post['img']['expiration']['y'] ) || ! AC()->helper->pos_int( $post['img']['expiration']['y'] ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) . '=>' . AC()->lang->__( 'Y-Axis' ) );
				}
				if ( empty( $post['img']['expiration']['font'] ) ) {
					$err[] = AC()->lang->_e_select( AC()->lang->__( 'Expiration' ) . '=>' . AC()->lang->__( 'Font' ) );
				}
				if ( empty( $post['img']['expiration']['font_size'] ) || ! AC()->helper->pos_int( $post['img']['expiration']['font_size'] ) ) {
					$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Expiration' ) . '=>' . AC()->lang->__( 'Font Size' ) );
				}
			}

			for ( $i = 1; $i < 4; $i++ ) {
				if ( ! empty( $post['img'][ 'freetext' . $i ]['text'] ) ) {
					if ( 'C' !== $post['img'][ 'freetext' . $i ]['align'] && ( empty( $post['img'][ 'freetext' . $i ]['padding'] ) || ! AC()->helper->pos_int( $post['img'][ 'freetext' . $i ]['padding'] ) ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Free Text' ) . ' ' . $i . '=>' . AC()->lang->__( 'Padding' ) );
					}
					if ( empty( $post['img'][ 'freetext' . $i ]['y'] ) || ! AC()->helper->pos_int( $post['img'][ 'freetext' . $i ]['y'] ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Free Text' ) . ' ' . $i . '=>' . AC()->lang->__( 'Y-Axis' ) );
					}
					if ( ! empty( $post['img'][ 'freetext' . $i ]['maxwidth'] ) && ! AC()->helper->pos_int( $post['img'][ 'freetext' . $i ]['maxwidth'] ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Free Text' ) . ' ' . $i . '=>' . AC()->lang->__( 'Max Width' ) );
					}
					if ( empty( $post['img'][ 'freetext' . $i ]['font'] ) ) {
						$err[] = AC()->lang->_e_select( AC()->lang->__( 'Free Text' ) . ' ' . $i . '=>' . AC()->lang->__( 'Font' ) );
					}
					if ( empty( $post['img'][ 'freetext' . $i ]['font_size'] ) || ! AC()->helper->pos_int( $post['img'][ 'freetext' . $i ]['font_size'] ) ) {
						$err[] = AC()->lang->_e_valid( AC()->lang->__( 'Free Text' ) . ' ' . $i . '=>' . AC()->lang->__( 'Font Size' ) );
					}
				}
			}

			if ( ! empty( $post['imgplugin'] ) ) {
				$_template = array();
				$rtn = AC()->helper->trigger( 'cmcouponOnInitEmailtemplate' );
				foreach ( $rtn as $items ) {
					foreach ( $items as $k2 => $row ) {
						if ( empty( $row->key ) ) {
							continue;
						}
						$_template[ $row->key ][ $k2 ] = $row;
					}
				}
				foreach ( $post['imgplugin'] as $k1 => $pluginitems ) {
					foreach ( $pluginitems as $k2 => $pluginrow ) {
						if ( ! empty( $pluginrow['text'] ) ) {
							if ( 'C' !== $pluginrow['align'] && ( empty( $pluginrow['padding'] ) || ! AC()->helper->pos_int( $pluginrow['padding'] ) ) ) {
								$err[] = AC()->lang->_e_valid( $_template[ $k1 ][ $k2 ]->title . '=>' . AC()->lang->__( 'Padding' ) );
							}
							if ( empty( $pluginrow['y'] ) || ! AC()->helper->pos_int( $pluginrow['y'] ) ) {
								$err[] = AC()->lang->_e_valid( $_template[ $k1 ][ $k2 ]->title . '=>' . AC()->lang->__( 'Y-Axis' ) );
							}
							if ( ! empty( $pluginrow['maxwidth'] ) && ! AC()->helper->pos_int( $pluginrow['maxwidth'] ) ) {
								$err[] = AC()->lang->_e_valid( $_template[ $k1 ][ $k2 ]->title . '=>' . AC()->lang->__( 'Max Width' ) );
							}
							if ( empty( $_template[ $k1 ][ $k2 ]->is_ignore_font ) && empty( $pluginrow['font'] ) ) {
								$err[] = AC()->lang->_e_select( $_template[ $k1 ][ $k2 ]->title . '=>' . AC()->lang->__( 'Font' ) );
							}
							if ( empty( $_template[ $k1 ][ $k2 ]->is_ignore_font_size ) && ( empty( $pluginrow['font_size'] ) || ! AC()->helper->pos_int( $pluginrow['font_size'] ) ) ) {
								$err[] = AC()->lang->_e_valid( $_template[ $k1 ][ $k2 ]->title . '=>' . AC()->lang->__( 'Font Size' ) );
							}
						}
					}
				}
			}
		}
		return $err;
	}

	/**
	 * Get fonts from font folder
	 */
	public function get_fonts() {
		$fontdd = array();
		foreach ( glob( CMCOUPON_GIFTCERT_DIR . '/fonts/*.[tT][tT][fF]' ) as $font ) {
			$font = basename( $font );
			$fontdd[ $font ] = ucwords( substr( $font, 0, -4 ) );
		}
		return $fontdd;
	}

	/**
	 * Get images from iamge folder
	 */
	public function get_images() {
		$imagedd = array();
		$accepted_formats = array( 'png', 'jpg' );
		foreach ( glob( CMCOUPON_GIFTCERT_DIR . '/images/*.*' ) as $img ) {
			$parts = pathinfo( $img );
			if ( in_array( strtolower( $parts['extension'] ), $accepted_formats, true ) ) {
				$imagedd[ $parts['basename'] ] = $parts['basename'];
			}
		}
		return $imagedd;
	}
}
