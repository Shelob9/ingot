<?php
/**
 * Defines the chosen variant and what prices/plugin it represents
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\object\price;


use ingot\testing\crud\group;
use ingot\testing\crud\variant;
use ingot\testing\ingot;
use ingot\testing\types;
use ingot\testing\utility\price;

class test implements \JsonSerializable {

	/**
	 * Variant ID
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	private $ID;

	/**
	 * Plugin slug
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $plugin;

	/**
	 * Variant config
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $variant;

	/**
	 * Time unix time in seconds when this test expires
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var int
	 */
	private $expires;

	/**
	 * Price with test applied
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $price;

	/**
	 * Product object
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var \WP_Post
	 */
	private $product;

	/**
	 * Callback function for getting base price
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var string|array
	 */
	private $price_callback;

	/**
	 * Create object with test details
	 *
	 * @param array $test Test details
	 *
	 * @throws \Exception If test invalid
	 */
	public function __construct( $test ) {

		$this->set_properties( $this->verify_test( $test ) );
		$this->set_price();
	}

	/**
	 * Record a victory for this test
	 *
	 * @since 1.1.0
	 *
	 * return int
	 */
	public function record_victory(){
		$group_id = $this->variant[ 'group_ID' ];
		$bandit = new \ingot\testing\bandit\price( $group_id );
		$bandit->record_victory( $this->ID );
		ingot::instance()->get_current_session();

	}

	/**
	 * Use to limit size of serialized object put into cookie
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'ID'      => $this->ID,
			'expires' => $this->expires
		];
	}

	/**
	 * Get price, with test variation applied
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_price(){
		if( ! isset( $this->price ) ){
			$this->set_price();
		}

		return $this->price;
	}

	/**
	 * Get any of the declared properties
	 *
	 * @since 1.1.0
	 *
	 * @param string $prop Name of property
	 *
	 * @return mixed
	 */
	public function __get( $prop ){
		if( property_exists( $this, $prop ) ){
			if( ! isset( $this->$prop ) && method_exists( $this, 'set_' . $prop ) ){
				call_user_func( array( $this, 'set_' . $prop ) );
			}

			if( method_exists( $this, 'get_' . $prop ) ){
				return call_user_func( array( $this, 'get_' . $prop ) );
			}

			return $this->$prop;

		}

	}

	/**
	 * Set price
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @return mixed
	 */
	protected function set_price(){
		$this->set_variant();
		$this->set_price_callback();
		$this->set_product();

		if ( is_callable( $this->price_callback ) ) {

			$variant = $this->variant;
			if ( is_array( $variant ) ) {
				$variation = price::get_price_variation( $variant );
				$base_price = call_user_func( $this->price_callback, $this->product->ID );
				if ( 0 == $base_price || 0 == $variation ) {
					return $base_price;
				}

				$variation   = $variation * 1;
				$this->price = $variation * $base_price;
			}

		}

	}

	/**
	 * Verify test is legit
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param array $test Test details to make object from
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function verify_test( $test ){
		$vars = array_keys( get_object_vars( $this ) );
		foreach( $vars as $key ){
			if( in_array( $key, [ 'expires', 'ID' ] ) && ! isset( $test[ $key ] ) ){
				throw new \Exception( __( 'invalid-price-test', 'ingot' ) );
			}

		}

		return $test;

	}

	/**
	 * Set all properties of this object
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @param array $test
	 */
	private function set_properties( $test ){
		foreach( $test as $prop => $value ){
			if( property_exists( $this, $prop ) ){
				if( 'variant' == $prop && is_numeric( $value ) ){
					$this->variant = variant::read( $value );
				}else{
					$this->$prop = $value;
				}

			}

		}

	}


	/**
	 * Set variant property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 */
	private function set_variant(){
		if( ! isset( $this->variant ) ){
			$this->variant = variant::read( $this->ID );
		}

	}

	/**
	 * Set price callback property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 */
	private function set_price_callback(){
		if( ! isset( $this->price_callback ) ){
			$this->set_plugin();
			$this->price_callback = price::get_price_callback( $this->plugin );
		}

	}

	/**
	 * Set plugin property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 */
	private function set_plugin(){
		if( ! isset( $this->plugin ) ) {
			$this->set_variant();
			$group        = group::read( $this->variant[ 'group_ID' ] );
			if ( in_array( $group[ 'sub_type' ], types::allowed_price_types() ) ) {
				$this->plugin = $group[ 'sub_type' ];
			}

		}

	}

	/**
	 * Set product property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 */
	private function set_product(){
		if ( ! is_object( $this->product ) ) {
			$group = group::read( $this->variant[ 'group_ID' ] );
			if ( in_array( $group[ 'sub_type' ], types::allowed_price_types() ) ) {
				$this->product = price::get_product( $group );
			}
		}

	}

}
