<?php
/**
 * Base class for plugin-specific price testing
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\tests\price;


abstract class price {

	/**
	 * Slug for plugin
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Array of products we are tracking
	 *
	 * Must be keyed by product ID with ingot\testing\object\price\test object as value
	 *
	 * @var array
	 */
	protected $products = [];

	public function __construct( $products ){
		$this->products = $products;
		$this->add_hooks();
	}

	/**
	 * Add our hooks
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @since 0.0.9
	 *
	 */
	protected function add_hooks() {
		$this->variable_price_hooks();
		$this->non_variable_price_hooks();
		$this->purchase_hook();

	}

	/**
	 * Get a test object from cookie
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param int $id Product ID
	 *
	 * @return array|\ingot\testing\object\price\test
	 */
	protected function get_test( $id ){
		if( array_key_exists( $id, $this->products ) ){
			$test =  $this->products[ $id ];
			return \ingot\testing\utility\price::inflate_price_test( $test );
		}
	}


	/**
	 * Get the price set in test object -- respecting price testing
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param \ingot\testing\object\price\test $test
	 *
	 * @return string
	 */
	protected function get_price( \ingot\testing\object\price\test $test ){
		return $test->get_price();
	}



	/**
	 * Callback for the price hook for non-variable prices
	 *
	 * @since 0.0.9
	 *
	 * @param $price
	 * @param $id
	 *
	 * @return string
	 */
	public function filter_price( $price, $id ) {

		if( ! is_null( $test = $this->get_test( $id ) ) ){

			$price = $this->get_price( $test );

		}

		return $price;

	}

	/**
	 * Callback for the price hook for variable price products
	 *
	 * @since 0.0.9
	 *
	 * @param $prices
	 * @param $id
	 *
	 * @return mixed
	 */
	public function filter_variable_prices( $prices, $id ) {
		if( ! is_null( $test = $this->get_test( $id ) ) ){
			$prices = $this->handle_variable_prices( $prices, $test, $id );
		}

		return $prices;


	}


	/**
	 * Use in subclass to setup hooks for variably priced products
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	abstract protected function variable_price_hooks();

	/**
	 * Use in subclass to setup hooks for nonvariable products
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	abstract protected function non_variable_price_hooks();

	/**
	 * Hook for tracking purchases
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	abstract protected function purchase_hook();

	/**
	 * Sanatize price display
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @param float|string $price
	 *
	 * @return string
	 */
	protected function sanatize_price( $price ) {
		return ingot_sanitize_amount( $price );
	}


	/**
	 * Filter variable prices
	 *
	 * @since 1.1.0
	 *
	 * @param array $prices Variable prices
	 * @param \ingot\testing\object\price\test| $test Test object
	 * @param int $id Product ID
	 *
	 * @return array
	 */
	protected function handle_variable_prices( $prices, $test, $id  ){
		return $prices;
	}

}
