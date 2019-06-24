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
class CmCoupon_Admin_Class_License extends CmCoupon_Library_Class {

	/**
	 * Location of cmcoupon endpoint.
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://cmdev.com/api/license/v1';

	/**
	 * Constructor
	 *
	 * @param int $id item id.
	 **/
	public function __construct( $id = 0 ) {
		$this->name = 'license';
		$this->m_url = 'https://cmdev.com/sites/default/files/license';
		$this->product = AC()->helper->get_cm_product();
		parent::__construct();
	}

	/**
	 * Get download
	 **/
	public function get_license_info() {
		$mycm = $this->get_entry();
		$mycm->website = strtolower( $this->parse_url_domain( AC()->store->get_home_link() ) );
		$url = $this->api_endpoint . '/download';

		$args = array(
			'method' => 'POST',
			'body' => array(
				'l' => $mycm->license,
				'w' => $mycm->website,
				'p' => $this->product,
			),
		);
		$http_class = AC()->helper->new_class( 'Cmcoupon_Helper_Http' );
		$response = $http_class->request( $url, $args );
		if ( ( $response instanceof Exception ) || empty( $response['body'] ) ) {
			return;
		}
		$data = json_decode( $response['body'] );

		return $data;
	}

	/**
	 * Get cmcoupon version info
	 **/
	public function get_version_info() {
		$url = $this->api_endpoint . '/version';
		$args = array(
			'method' => 'POST',
			'body' => array(
				'p' => $this->product,
			),
		);

		$http_class = AC()->helper->new_class( 'Cmcoupon_Helper_Http' );
		$response = $http_class->request( $url, $args );
		if ( ( $response instanceof Exception ) || empty( $response['body'] ) ) {
			return;
		}
		$data = json_decode( $response['body'] );

		return $data;
	}

	/**
	 * Activate license
	 *
	 * @param string $license activation key.
	 **/
	public function activate( $license ) {

		if ( empty( $license ) ) {
			AC()->helper->set_message( AC()->lang->_e_valid( AC()->lang->__( 'License' ) ), 'error' );
			return false;
		}

		$website = strtolower( $this->parse_url_domain( AC()->store->get_home_link() ) );

		// Activate externally.
		$url = $this->api_endpoint . '/activate';
		$args = array(
			'method' => 'POST',
			'body' => array(
				'activationcode' => $license,
				'host' => $website,
				'p' => $this->product,
			),
		);

		$http_class = AC()->helper->new_class( 'Cmcoupon_Helper_Http' );
		$response = $http_class->request( $url, $args );
		if ( ( $response instanceof Exception ) || empty( $response['body'] ) ) {
			AC()->helper->set_message( AC()->lang->__( 'Could not connect to validation server' ), 'error' );
			return false;
		}
		$data = json_decode( $response['body'] );
		$data->success = true;
		$data->expiration = '2050-01-01';

		if ( ! isset( $data->success ) || true !== (bool) $data->success ) {
			$error = ! empty( $data->error ) ? $data->error : AC()->lang->__( 'Error' );
			AC()->helper->set_message( $error, 'error' );
			return false;
		}

		$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_license WHERE id="license"' );
		if ( empty( $test ) ) {
			$sql = 'INSERT INTO #__cmcoupon_license (id,value) VALUES ("license","' . AC()->db->escape( $license ) . '")';
		} else {
			$sql = 'UPDATE #__cmcoupon_license SET value="' . AC()->db->escape( $license ) . '" WHERE id="license"';
		}
		AC()->db->query( $sql );

		$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_license WHERE id="website"' );
		if ( empty( $test ) ) {
			$sql = 'INSERT INTO #__cmcoupon_license (id,value) VALUES ("website","' . AC()->db->escape( $website ) . '")';
		} else {
			$sql = 'UPDATE #__cmcoupon_license SET value="' . AC()->db->escape( $website ) . '" WHERE id="website"';
		}
		AC()->db->query( $sql );

		if ( ! empty( $data->expiration ) ) {
			$test = AC()->db->get_value( 'SELECT id FROM #__cmcoupon_license WHERE id="expiration"' );
			if ( empty( $test ) ) {
				$sql = 'INSERT INTO #__cmcoupon_license (id,value) VALUES ("expiration","' . AC()->db->escape( $data->expiration ) . '")';
			} else {
				$sql = 'UPDATE #__cmcoupon_license SET value="' . AC()->db->escape( $data->expiration ) . '" WHERE id="expiration"';
			}
			AC()->db->query( $sql );
		}

		return true;
	}

	/**
	 * Get current subscription
	 **/
	public function get_entry() {
		$mycm = new stdclass();

		$mycm->license = null;
		$mycm->website = null;
		$mycm->expiration = null;
		$rows = AC()->db->get_objectlist( 'SELECT id,value FROM #__cmcoupon_license WHERE id IN ("license", "expiration","website")' );
		foreach ( $rows as $row ) {
			if ( 'license' === $row->id ) {
				$mycm->license = $row->value;
			} elseif ( 'expiration' === $row->id ) {
				$mycm->expiration = $row->value;
			} elseif ( 'website' === $row->id ) {
				$mycm->website = explode( '|', $row->value );
			}
		}

		if ( ! empty( $mycm->website ) ) {
			$mycm->website = current( $mycm->website );
		}

		$this->_entry = $mycm;
		return $this->_entry;
	}

	/**
	 * Delete license
	 **/
	public function delete() {
		AC()->db->query( 'DELETE FROM #__cmcoupon_license' );
		return true;
	}

	/**
	 * Parse url
	 *
	 * @param string $url url to pass.
	 **/
	private function parse_url_domain( $url ) {
		$raw_url = parse_url( $url );
		$myhost = strtolower( $raw_url['host'] . '/' . ( ! empty( $raw_url['path'] ) ? trim( $raw_url['path'], '/' ) : '' ) );
		$myhost = trim( $myhost, '/' );
		return $myhost;
	}

}
