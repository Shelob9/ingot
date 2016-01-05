<?php
/**
 * @TODO What this does.
 *
 * @package   @TODO
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 Josh Pollock
 */
class tests_price_test extends \WP_UnitTestCase {

	/**
	 * Test creating of object
	 *
	 * @since 1.1.0
	 *
	 * @group price_object
	 * @group price
	 * @group edd_price
	 */
	public function testWithMinimal(){
		$data = ingot_test_data_price::edd_tests( 10 );
		$bandit = new \ingot\testing\bandit\price( $data[ 'group_ID' ] );
		$variant_id = $bandit->choose();
		$obj = new \ingot\testing\object\price\test(
			[
			    'ID' => $variant_id,
				'expires' => time() * 167234
			]
		);

		$product_ID = \ingot\testing\utility\price::get_product_ID( $data[ 'group' ] );


		$variant = \ingot\testing\crud\variant::read( $variant_id );
		$this->assertEquals( $variant_id, $obj->ID );
		$this->assertEquals( $variant, $obj->variant );
		$this->assertEquals( get_post( $product_ID ), $obj->product );
		$this->assertEquals( 'edd_get_download_price', $obj->price_callback );
	}

	/**
	 * Test inflating/defalting object
	 *
	 * @since 1.1.0
	 *
	 * @group price_object
	 * @group price
	 * @group edd_price
	 */
	function testInflationDefaltion(){
		$data = ingot_test_data_price::edd_tests( 10 );
		$bandit = new \ingot\testing\bandit\price( $data[ 'group_ID' ] );
		$variant_id = $bandit->choose();
		$args = [
			'ID'      => $variant_id,
			'expires' => 167234
		];
		$obj = new \ingot\testing\object\price\test($args);

		$as_json = wp_json_encode( $obj );
		$this->assertEquals( wp_json_encode($args), $as_json );
		$inflated = \ingot\testing\utility\price::inflate_price_test( $as_json );
		$this->assertSame( $obj->ID, $inflated->ID );
		$this->assertSame( $obj->variant, $inflated->variant );
	}
}
