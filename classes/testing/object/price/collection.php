<?php
/**
 * Collection of price tests by product ID/ or plugin
 *
 * NOTE: THIS CLASS ISN't USED. Josh should find a use for it or delete it, even though he is oddly fond of it.
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\object\price;



use ingot\testing\crud\price_query;
use ingot\testing\types;

class collection {

	/**
	 * All the tests for this collection
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	private $tests;


	/**
	 *  Construct object
	 *
	 * @since 1.1.0
	 *
	 * @param string $by What to query by options are plugin or product_ID
	 * @param int|string $value What value to use for query. Product ID or plugin slug (edd|woo)
	 */
	public function __construct( $by, $value ){
		if( 'plugin' == $by  && in_array( $value, types::allowed_price_types() ) ){
			$this->set_tests_by_plugin(  $value );
		}elseif( 'product_ID' == $by && is_object( get_post( $value ) ) ){
			$this->set_tests_by_product_id( $value );
		};

	}

	/**
	 * Return queried tests
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_tests(){
		return $this->tests;
	}

	/**
	 * Set tests property by plugin slug
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @param string $plugin Plugin slug
	 */
	private function set_tests_by_plugin( $plugin ){
		$this->tests = price_query::find_by_plugin( $plugin );
	}
	/**
	 * Set tests property by product ID
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @param int $id Product ID
	 */
	private function set_tests_by_product_id( $id ){
		$this->tests = price_query::find_by_product( $id );
	}


}
