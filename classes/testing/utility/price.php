<?php
/**
 * Price test utilities
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\utility;


use ingot\testing\cookies\init;
use ingot\testing\crud\group;
use ingot\testing\crud\price_test;
use ingot\testing\crud\variant;
use ingot\testing\object\price\test;
use ingot\testing\types;

class price {

	/**
	 * Ensure a number is a float to represent a percentage.
	 *
	 * Must be between -.9 and .9
	 *
	 * @since 0.2.0
	 *
	 * @param float $float
	 *
	 * @return bool
	 */
	public static function valid_percentage( $float ){
		if ( is_numeric( $float ) ) {
			if ( - 1 < $float && 1 > $float ) {
				return true;

			}
		}

		return false;

	}

	/**
	 * Get product associated with a price group
	 *
	 * @since 1.1.0
	 *
	 * @param $group
	 *
	 * @return array|null|\WP_Post
	 */
	public static function get_product( $group ){
		$product_ID = self::get_product_ID( $group );

		if( is_numeric( $product_ID ) ){
			$post = call_user_func( self::get_product_function( $group[ 'sub_type' ] ), $product_ID );
			if( is_object( $post ) ) {
				return $post;
			}

		}

	}

	/**
	 * Get price variation or variation as a float for a variant
	 *
	 * @since 1.1.0
	 *
	 * @param int|array $variant Variation or variations ID
	 *
	 * @return float|void
	 */
	public static function get_price_variation( $variant ){
		if( is_numeric( $variant ) ){
			$variant = variant::read( $variant );
		}

		if( variant::valid( $variant ) && 'price' == $variant[ 'type' ] ){
			return $variant[ 'meta' ][ 'price' ][0];
		}

		return 1;


	}

	/**
	 * Get callback function for finding price of a product by plugin
	 *
	 * @since 1.1.0
	 *
	 * @param $plugin
	 *
	 * @return string
	 */
	public static function get_price_callback( $plugin ){
		/**
		 * Set the function to get the price for a product.
		 *
		 * Function must accept product ID as first argument.
		 *
		 * @since 1.1.0
		 *
		 * @param null|string|array $callback Name of callback. Defaults to null, which uses internal logic
		 * @param string $plugin Slug of plugin edd|woo
		 */
		$callback = apply_filters( 'ingot_get_price_callback', null, $plugin );
		if ( is_null( $callback ) ) {
			switch ( $plugin ) {
				case 'edd' :
					$callback = 'edd_get_download_price';
					break;
				default :
					$callback = '__return_false';
					break;
			}

		}

		return $callback;
	}

	/**
	 * Get callback function for finding a product
	 *
	 * @since 1.1.0
	 *
	 * @param string $plugin Slug of plugin edd|woo
	 *
	 * @return string
	 */
	public static function get_product_function( $plugin ){
		/**
		 * Set the function to get the product.
		 *
		 * Function must accept product ID as first argument.
		 *
		 * @since 1.1.0
		 *
		 * @param null|string|array $callback Name of callback. Defaults to null, which uses internal logic
		 * * @param string $plugin Slug of plugin edd|woo
		 */
		$callback = apply_filters( 'ingot_get_price_callback', null, $plugin );
		if (  is_null( $callback ) ) {
			switch ( $plugin ) {
				case 'edd' :
					$callback = 'edd_get_download';
					break;
				default:
					$callback = 'get_post';
					break;
			}
		}

		return $callback;
	}

	/**
	 * Get price test object from the price cookie
	 *
	 * @since 1.1.0
	 *
	 * @param string $plugin Slug of plugin edd|woo
	 * @param int $id  Product ID
	 * @param array|null $cookie
	 *
	 * @return \ingot\testing\object\price\test
	 */
	public static function get_price_test_from_cookie( $plugin, $id, $cookie = null ){

		if ( in_array( $plugin, types::allowed_price_types() ) ) {
			if ( is_null( $cookie ) ) {
				$cookie = init::get_instance()->get_ingot_cookie( false )[ 'price' ];
			}


			if ( isset( $cookie[ $plugin ][ $id ] ) ) {

				$test =  $cookie[ $plugin ][ $id ];

				return self::inflate_price_test( $test );

			}

		}


	}

	/**
	 * Get product ID from a group
	 *
	 * @param array|int $group
	 *
	 * @return int|null
	 */
	public static function get_product_ID( $group ){
		if( is_numeric( $group ) ) {
			$group = group::read( $group );
		}

		if( group::valid( $group ) ){
			return (int) helpers::v( 'product_ID', $group[ 'meta' ], null );
		}
	}

	/**
	 * Holds inflated test objects
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $inflated = [];

	/**
	 * Turn price test when coming from cookie back into price/test object
	 *
	 * @since 1.1.0
	 *
	 * @param array $test
	 *
	 * @return array|\ingot\testing\object\price\test
	 */
	public static function inflate_price_test( $test ) {

		if ( is_array( $test ) || ( ! is_object( $test ) && is_array( $test = json_decode( $test, true  ) ) ) ) {
			if( isset( self::$inflated[ $test[ 'ID' ] ] ) ){
				return self::$inflated[ $test[ 'ID' ] ];
			}

			$test = new test( $test );
			self::$inflated[ $test->ID ] = $test ;
		}

		return $test;

	}




}
