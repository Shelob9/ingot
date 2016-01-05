<?php
/**
 * Tests for EDD price tests
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class tests_edd_price_tests extends \WP_UnitTestCase {

	public function setUp(){
		parent::setUp();
		\ingot\testing\crud\group::delete( 'all' );
		\ingot\testing\crud\variant::delete( 'all' );
	}

	/**
	 * Test that EDD sample data generation is valid
	 *
	 * @since 1.1.0
	 *
	 * @group price
	 * @group edd_price
	 */
	public function testDataGeneration(){
		$group_data  = ingot_test_data_price::edd_tests( 10 );
		$group = \ingot\testing\crud\group::read( $group_data[ 'group_ID'  ] );
		$this->assertTrue( \ingot\testing\crud\group::valid( $group  ) );
		foreach( $group_data[ 'variants' ] as $variant_id ){
			$this->assertTrue( is_numeric( $variant_id ) );
			$variant = \ingot\testing\crud\variant::read( $variant_id );
			$this->assertTrue( \ingot\testing\crud\variant::valid( $variant ) );
			$this->assertEquals( $variant[ 'content' ], $group[ 'meta'][ 'product_ID' ] );
		}

	}

	/**
	 * Test that EDD sale of non-variable product is properly priced/recorded
	 *
	 * @since 1.1.0
	 *
	 * @group pricez
	 * @group edd_price
	 *
	 * @covers \ingot\testing\tests\price\plugins\edd
	 */
	public function testPriceTrackingNonVariableProduct(){
		$price_is = 10;
		$product = ingot_test_data_price::edd_create_simple_download( $price_is );
		$group_id = \ingot\testing\crud\group::create([
			'type'     => 'price',
			'sub_type' => 'edd',
			'meta' => [ 'product_ID' => $product->ID ]
		],
			true
		);
		$variant_one = \ingot\testing\crud\variant::create( [
				'group_ID' => $group_id,
				'type'    => 'price',
				'meta'    => [
					'price' => [ 0.5 ]
				],
				'content' => $product->ID,
			],
			true
		);

		$variant_two = \ingot\testing\crud\variant::create( [
				'group_ID' => $group_id,
				'type'    => 'price',
				'meta'    => [
					'price' => [ 0.5 ]
				],
				'content' => $product->ID,
			],
			true
		);

		$group = \ingot\testing\crud\group::read( $group_id );
		$group[ 'variants' ] = [ $variant_one, $variant_two ];
		\ingot\testing\crud\group::update( $group, $group_id, true );

		$cookie_class = new \ingot\testing\cookies\price( [] );
		$price_cookie = $cookie_class->get_cookie();


		$this->assertArrayHasKey( 'edd', $price_cookie );
		$this->assertFalse( empty( $price_cookie[ 'edd' ] ) );
		$this->assertInternalType( 'array', $price_cookie[ 'edd' ] );

		$product_id = \ingot\testing\utility\price::get_product_ID( $group );
		$this->assertEquals( $product_id, $product->ID );
		$this->assertArrayHasKey( $product_id, $price_cookie[ 'edd' ] );

		new \ingot\testing\tests\price\plugins\edd( $price_cookie[ 'edd' ] );

		$test = \ingot\testing\utility\price::get_price_test_from_cookie( 'edd', $product->ID, $price_cookie );

		$this->assertInternalType( 'object', $test );
		$price_should_be = $test->get_price();
		//NOTE: USING edd_get_download_price here is to ensure we don't have recursion
		$this->assertEquals( edd_get_download_price( $product->ID ), $price_should_be );

		$group_obj = new \ingot\testing\object\group( $group_id );
		$lever = $group_obj->get_lever( $test->ID );
		$this->assertInternalType( 'object', $lever );
		$before_wins = $lever->getNumerator();

		$payment_id = ingot_test_data_price::edd_create_simple_payment( $product);
		edd_complete_purchase( $payment_id, 'publish', 'pending' );

		$group_obj = new \ingot\testing\object\group( $group_id );
		$lever = $group_obj->get_lever( $test->ID );
		$this->assertInternalType( 'object', $lever );
		$after_wins = $lever->getNumerator();

		$this->assertEquals( $before_wins + 1, $after_wins );


	}

	public function _testVariablePrice() {
		$download = ingot_test_data_price::edd_create_variable_download( 10 );
		$prices = edd_get_variable_prices( $download->ID );
	}
}
