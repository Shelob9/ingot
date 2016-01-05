<?php
/**
 * Sets up price cookies
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\cookies;

use ingot\testing\crud\crud;
use ingot\testing\crud\group;
use ingot\testing\crud\price_query;
use ingot\testing\object\price\test;
use ingot\testing\types;
use ingot\testing\utility\helpers;


class price extends cookie {

	/**
	 * Hold all groups we need to track
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	private $groups = [];

	/**
	 * Construct object
	 *
	 * @since 1.1.0
	 *
	 * @param array $cookie Current contents of this part of cookie
	 * @param bool $reset Optional. Whether to rest or not, default is false
	 */
	public function __construct(  $cookie, $reset = true ){
		parent::__construct( $cookie, $reset );
		$this->set_groups();
		if( ! empty( $this->groups ) ){
			$this->setup_cookie();
		}

	}

	/**
	 * Set tests property with all test we need to track
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @return array|mixed
	 */
	private function set_groups(){
		$key = md5( __FUNCTION__ );
		if( WP_DEBUG || ! is_array( $groups = get_transient( $key ) ) && ! empty( $groups ) ){
			foreach( types::allowed_price_types() as $plugin ){
				$this->groups[ $plugin ] = price_query::find_by_plugin( $plugin );
			}

			set_transient( $key, $this->groups, HOUR_IN_SECONDS );

		}

	}

	/**
	 * Setup the cookie contents
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function setup_cookie(){
		foreach( $this->groups as $groups_by_type ) {
			foreach( $groups_by_type as $type => $group ){
				if ( $this->needed_to_add( $group ) ) {
					$this->add_test( $group );
				}

			}

		}

		//remove uneeded?
	}

	/**
	 * @param $group
	 *
	 * @return \ingot\testing\object\price\test
	 */
	protected function setup_test_object( $group ) {
		if( is_numeric( $group ) ) {
			$group = group::read( $group );
		}

		if( ! group::valid($group ) ){
			return false;
		}

		$bandit  = new \ingot\testing\bandit\price( $group[ 'ID' ] );
		$variant = $bandit->choose();

		$test = new test( [
			'plugin'  => $group[ 'sub_type' ],
			'ID'      => $group[ 'ID' ],
			'expires' => $this->expires(),
			'variant' => $variant,
			'product' => get_post( $group[ 'meta' ][ 'product_ID' ] ),
			'price_callback' => \ingot\testing\utility\price::get_price_callback( $group[ 'sub_type' ] )
		] );

		return $test;

	}

	/**
	 * Test if we need to add test to cookie
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param array $group
	 *
	 * @return bool
	 */
	protected function needed_to_add( $group ){
		if( ! group::valid( $group ) ) {
			return true;
		}else {
			//not in cookie true
			if( ! isset( $this->cookie[ $group[ 'sub_type' ] ][ $group[ 'ID' ] ] ) ) {
				return true;
			}

			//in cookie and expired true
			$obj = $this->get_test_from_cookie( $group );
			if( is_object( $obj ) && $this->expired( $obj->expires ) ) {
				return true;
			}

		}

		//in cookie and not expired false
		return false;

	}

	/**
	 * Get test out of cookie
	 *
	 * @since 1.1.0
	 *
	 * @param array  $group
	 *
	 * @return bool|\ingot\testing\object\price\test
	 */
	protected function get_test_from_cookie( $group ){

		$test = \ingot\testing\utility\price::get_price_test_from_cookie( $group[ 'sub_type' ], \ingot\testing\utility\price::get_product_ID( $group ), $this->cookie );

		return $test;

	}

	/**
	 * Add test to cookie
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param array $group Group config
	 */
	protected function add_test( $group ) {
		if( group::valid( $group ) ){
			$test = $this->setup_test_object( $group );
			if ( is_object( $test ) ) {
				$product_ID = \ingot\testing\utility\price::get_product_ID( $group );
				if ( is_object( $test ) && is_numeric( $product_ID ) ) {
					$this->cookie[ $group[ 'sub_type' ] ][ $product_ID ] = wp_json_encode( $test );
				}
			}

		}

	}

	/**
	 * Set cookie property for class
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @param array $cookie Current contents of this part of cookie
	 * @param bool $reset Optional. Whether to rest or not, default is false
	 */
	protected function set_cookie( $cookie, $reset ) {
		if ( false == $reset ) {
			$this->cookie = $cookie;
		} else {
			$this->cookie = [ ];
		}

		foreach ( types::allowed_price_types() as $type ) {
			if ( ! isset( $this->cookie[ $type ] ) ) {
				$this->cookie[ $type ] = [ ];
			}

		}

	}

}
