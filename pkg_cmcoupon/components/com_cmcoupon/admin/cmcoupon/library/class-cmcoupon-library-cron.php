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
class CmCoupon_Library_Cron {

	/**
	 * Process cron
	 *
	 * @param boolean $is_timecheck time check.
	 **/
	public static function process( $is_timecheck = true ) {
		$instance = new CmCoupon_Library_Cron();
		$instance->run( $is_timecheck );
	}

	/**
	 * Get the timestamp from the lat run
	 **/
	public static function get_last_run() {
		$instance = new CmCoupon_Library_Cron();
		return $instance->last_run();
	}

	/**
	 * Constructor
	 **/
	public function __construct() {
		$this->params = AC()->param;
		$this->estore = CMCOUPON_ESTORE;
		$this->cron_file = 'cmcoupon_cron';
		$this->cron_path = CMCOUPON_TEMP_DIR;
		$this->cron_default_minutes = 30;
		$this->previous_runtime = 0;
	}

	/**
	 * Run the cron
	 *
	 * @param boolean $is_timecheck if set to true then assume coming from page and check time.
	 **/
	public function run( $is_timecheck = true ) {
		if ( $is_timecheck ) {
			if ( ! $this->is_time_to_run() ) {
				return;
			}
		}

		$installer = AC()->helper->new_class( 'Cmcoupon_Helper_Estore_' . CMCOUPON_ESTORE . '_Installation' );
		if ( method_exists( $installer, 'get_definition_cron' ) ) {
			 $installer->get_definition_cron();
		}

		$this->check_reminder( 1 );
		$this->check_reminder( 2 );
		$this->check_reminder( 3 );

		$this->check_expired();

		if ( $is_timecheck ) {
			$this->write_file();
		}
	}

	/**
	 * Reminder cron
	 *
	 * @param int $num either 1,2,3.
	 **/
	private function check_reminder( $num ) {
		$max_per_query = 10;
		$number_processed = 0;
		$cron_type = 'reminder' . $num;

		if ( (int) $this->params->get( 'reminder_' . $num . '_enable', 0 ) !== 1 ) {
			return;
		}

		$days = (int) $this->params->get( 'reminder_' . $num . '_days', 0 );
		$profile_id = (int) $this->params->get( 'reminder_' . $num . '_profile', 0 );
		if ( $days <= 0 || $profile_id <= 0 ) {
			return;
		}

		$profile = AC()->db->get_arraylist( 'SELECT * FROM #__cmcoupon_profile WHERE id=' . $profile_id );
		if ( empty( $profile ) ) {
			return;
		}
		$profile = AC()->profile->decrypt_profile( current( $profile ) );

		$date_expire = AC()->helper->get_date( strtotime( '+' . $days . ' days' ), 'Y-m-d', 'utc2utc' );

		$sql = 'SELECT c.*,u.asset_id AS user_id,EXISTS(SELECT 1 FROM #__cmcoupon_tag WHERE coupon_id=c.id AND tag="{reminder_exclude}" LIMIT 1) AS reminder_exclude
				  FROM #__cmcoupon_asset u
				  JOIN #__cmcoupon c ON c.id=u.coupon_id
				  LEFT JOIN #__cmcoupon_cron cr ON cr.coupon_id=c.id AND cr.user_id=u.asset_id AND cr.type="' . $cron_type . '"
				 WHERE c.estore="' . $this->estore . '"
				   AND u.asset_key=0
				   AND u.asset_type="user"
				   AND c.state="published"
				   AND FROM_UNIXTIME(UNIX_TIMESTAMP(c.expiration),"%Y-%m-%d")="' . $date_expire . '"
				   AND cr.id IS NULL
				 GROUP BY c.id,u.asset_id
				HAVING reminder_exclude=0
				 LIMIT 100
		';
		$rows = AC()->db->get_objectlist( $sql );
		foreach ( $rows as $row ) {

			if ( ! empty( $row->num_of_uses_customer ) ) {
				$userlist = array();
				$cnt = AC()->db->get_value( 'SELECT COUNT(id) AS cnt FROM #__cmcoupon_history WHERE coupon_id=' . $row->id . ' AND user_id=' . $row->user_id . ' GROUP BY coupon_id,user_id' );
				if ( ! empty( $cnt ) && $cnt >= $row->num_of_uses_customer ) {
					$this->mark_processed( $row->id, $row->user_id, $cron_type, 'fail', 'num_uses_customer' );
					continue;
				}
			}
			if ( ! empty( $row->num_of_uses_total ) ) {
				$num = AC()->db->get_value( 'SELECT COUNT(id) FROM #__cmcoupon_history WHERE coupon_id=' . $row->id . ' GROUP BY coupon_id' );
				if ( ! empty( $num ) && $num >= $row->num_of_uses_total ) {
					$this->mark_processed( $row->id, $row->user_id, $cron_type, 'fail', 'num_uses_total' );
					continue;
				}
			}

			$row->coupon_price = '';
			if ( ! empty( $row->coupon_value ) ) {
				$row->coupon_price = 'amount' === $row->coupon_value_type
					? AC()->storecurrency->format( $row->coupon_value )
					: round( $row->coupon_value ) . '%';
			}

			$send_user = AC()->helper->get_user( $row->user_id );
			$dynamic_tags = array(
				'find' => array(
					'{user_name}',
					'{username}',
					'{voucher}',
					'{today_date}',
					'{voucher_value}',
					'{expiration}',
					'{expiration_year_4digit}',
					'{expiration_year_2digit}',
					'{expiration_month_namelong}',
					'{expiration_month_nameshort}',
					'{expiration_month_2digit}',
					'{expiration_month_1digit}',
					'{expiration_day_namelong}',
					'{expiration_day_nameshort}',
					'{expiration_day_2digit}',
					'{expiration_day_1digit}',
				),
				'replace' => array(
					$send_user->name,
					$send_user->username,
					$row->coupon_code,
					AC()->helper->get_date(),
					$row->coupon_price,
					AC()->helper->get_date( $row->expiration ),
				),
			);

			$date_fragment = AC()->helper->get_date( $row->expiration, 'Y y F M m n l D d j' );
			$date_fragment = explode( ' ', $date_fragment );
			$dynamic_tags['replace'] = array_merge( $dynamic_tags['replace'], $date_fragment );

			if ( AC()->profile->send_email( $send_user, array( $row ), $profile, $dynamic_tags, true ) ) {
				$this->mark_processed( $row->id, $row->user_id, $cron_type, 'success', 'expiration date: ' . $date_expire );
				$number_processed++;
			}

			if ( $number_processed >= $max_per_query ) {
				break;
			}
		}

		if ( $number_processed >= $max_per_query ) {
			return;
		}
	}

	/**
	 * Expired coupon cron
	 **/
	private function check_expired() {
		$days_expired = AC()->param->get( 'delete_expired', '' );
		if ( empty( $days_expired ) || ! ctype_digit( $days_expired ) ) {
			return;
		}

		$current_date = date( 'Y-m-d H:i:s', strtotime( '-' . $days_expired . ' days' ) );
		$list = AC()->db->get_column( '
			SELECT c.id FROM #__cmcoupon c
			  LEFT JOIN #__cmcoupon_history h ON h.coupon_id=c.id
			 WHERE h.id IS NULL AND c.expiration<"' . $current_date . '"' );
		if ( empty( $list ) ) {
			return;
		}

		AC()->helper->add_class( 'CmCoupon_Library_Class' );
		$class = AC()->helper->new_class( 'CmCoupon_Admin_Class_Coupon' );
		$class->delete( $list );
	}

	/**
	 * Mark reminder emails processed
	 *
	 * @param int    $coupon_id the copon.
	 * @param int    $user_id the user notified.
	 * @param string $type the cron type.
	 * @param string $status options fail or success.
	 * @param string $notes extra notes.
	 **/
	private function mark_processed( $coupon_id, $user_id, $type, $status, $notes ) {
		AC()->db->query(
			'
			INSERT INTO #__cmcoupon_cron (coupon_id,user_id,type,status,notes)
			VALUES (' . (int) $coupon_id . ',' . (int) $user_id . ',"' . $type . '","' . $status . '","' . $notes . '")
		'
		);
	}

	/**
	 * Check if enough time has passed before reprocessing cron
	 **/
	private function is_time_to_run() {
		if ( (int) $this->params->get( 'cron_enable', 0 ) !== 1 ) {
			return false;
		}

		$files = glob( $this->cron_path . '/' . $this->cron_file . '.*' );
		$file = array_pop( $files );

		if ( ! empty( $file ) ) {
			if ( ! is_writeable( $file ) ) {
				return false;
			}
		} else {
			// touch a test file to make sure can write to directory.
			$tmp_file = 'cmcoupon_test_file_' . time();
			if ( ! touch( $this->cron_path . '/' . $tmp_file ) ) {
				return false;
			}
			unlink( $this->cron_path . '/' . $tmp_file );
		}

		$time = time();
		$this->previous_runtime = empty( $file ) ? $time - 1 : substr( $file, -10, 10 );

		if ( $time <= $this->previous_runtime ) {
			return false;
		}

		return true;
	}

	/**
	 * After cron process write to file
	 **/
	private function write_file() {

		$time_interval = (int) $this->params->get( 'cron_minutes', $this->cron_default_minutes );
		if ( $time_interval < 1 ) {
			$time_interval = $this->cron_default_minutes;
		}
		$time_interval *= 60;

		$files = glob( $this->cron_path . '/' . $this->cron_file . '.*' );

		// delete any old files.
		foreach ( $files as $file ) {
			unlink( $file );
		}

		// create new runtime.
		$time = time();
		$runtime = $this->previous_runtime;
		while ( $runtime < $time ) {
			$runtime += $time_interval;
		}
		if ( file_put_contents( $this->cron_path . '/' . $this->cron_file . '.' . $runtime, time() ) === false ) {
			return;
		}
	}

	/**
	 * Check the last run time
	 **/
	private function last_run() {
		$files = glob( $this->cron_path . '/' . $this->cron_file . '.*' );

		$file = array_pop( $files );
		if ( empty( $file ) ) {
			return;
		}

		$lastrun = (int) file_get_contents( $file );
		if ( empty( $lastrun ) ) {
			return;
		}

		return AC()->helper->get_date( gmdate( 'Y-m-d H:i:s', $lastrun ), 'Y-m-d H:i:s' );
	}

}
