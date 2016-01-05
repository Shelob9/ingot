<?php
/**
 * Setup a price test for Easy Digital Downloads
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\tests\price\plugins;


use ingot\testing\tests\price\price;

class edd extends price {


	/**
	 * Slug for plugin
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $plugin_slug = 'edd';

	/**
	 * Filter variable prices
	 *
	 * @since 1.1.0
	 *
	 * @param array $prices Variable prices
	 * @param \ingot\testing\object\price\test| $test Test object
	 * @param int $id Product ID
	 * @return array
	 */
	protected function handle_variable_prices( $prices, $test, $id ){

		if ( is_array( $prices ) && ! empty( $prices ) ) {
			foreach ( $prices as $i => $price ) {
				$prices[ $i ][ 'amount' ] = $this->filter_price( $prices[ $i ][ 'amount' ], $id );
			}
		}

		return $prices;
	}

	/**
	 * Hooks for variable price products
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	protected function variable_price_hooks() {
		add_filter( 'edd_get_variable_prices', array( $this, 'filter_variable_prices' ), 98, 2 );
	}

	/**
	 * Hooks for non-variable price products
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	protected function non_variable_price_hooks() {
		add_filter( 'edd_get_download_price', array( $this, 'filter_price' ), 98, 2 );
	}

	/**
	 * Hook for tracking purchases
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function purchase_hook(){
		add_action( 'edd_complete_purchase', array( $this, 'track_edd_sale' ) );
	}

	/**
	 * Track an EDD sale
	 *
	 * @since 0.0.0
	 *
	 * @uses "edd_complete_purchase"
	 *
	 * @param $payment_id
	 */
	public function track_edd_sale( $payment_id ) {
		$payment_meta = edd_get_payment_meta( $payment_id );
		$products = self::get_edd_downloads( $payment_meta );
		$this->check_for_winners( $products );
	}

	/**
	 * Check products in a sale for any we are testing and if so registers conversion
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @param array $products
	 */
	protected function check_for_winners( $products ) {
			foreach( $products as $product ){
				if( ! is_null( $test = $this->get_test( $product ) ) ){
					ingot_register_conversion( $test->ID );

				}
			}
	}

	/**
	 * Get products IDs from EDD payment meta
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @param array $payment_meta
	 *
	 * @return array
	 */
	protected  function get_edd_downloads( $payment_meta ) {
		$downloads = array();
		if( is_array( $payment_meta ) && isset( $payment_meta[ 'downloads' ] ) && ! empty( $payment_meta[ 'downloads' ] ) ) {
			$downloads = wp_list_pluck( $payment_meta[ 'downloads' ], 'id' );
		}

		return $downloads;
	}

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
		if ( function_exists( 'edd_sanitize_amount' ) ) {
			return edd_sanitize_amount( $price );
		}else{
			return parent::sanatize_price( $price );
		}

	}

}
