<?php
/**
 * Test for price_query class
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class tests_price_queries extends \WP_UnitTestCase {

	public function tearDown(){
		parent::tearDown();
		ingot_destroy();
	}

	/**
	 * Test that we can query by product ID
	 *
	 * @since 1.1.0
	 *
	 * @group price_query
	 * @group price
	 *
	 * @covers \ingot\testing\crud\price_query::find_by_product()
	 */
	public function testFindByProduct(){

		$args =  [
			'type'     => 'price',
			'sub_type' => 'edd',
		];

		$variant_args = [
			'type' => 'price',
			'meta' => [
				'price' => [ ]
			],
		];
		$args['meta'] [ 'product_ID' ] = 99;
		$group_id = \ingot\testing\crud\group::create( $args, true );
		for ( $i=0; $i <= 4; $i++ ) {


			if( 0 == $i || 1 == $i ){
				$args['meta'] [ 'product_ID' ] = 5;
				$variant_args[ 'content' ] = 5;
			}else{
				$args['meta'] [ 'product_ID' ] = 9;
				$variant_args[ 'content' ] = 9;
			}

			$group_id = \ingot\testing\crud\group::create( $args, true );
			$this->assertTrue( is_numeric( $group_id  ) );

			if( 0 == $i || 1 == $i ){
				$expected_ids[] = $group_id;

			}

			$variant_args[ 'group_ID' ] = $group_id;
			$variant_id = \ingot\testing\crud\variant::create( $variant_args, true );
			$this->assertTrue( is_numeric( $variant_id ) );
			$variants[ $group_id ] = $variant_id;
		}



		$groups = \ingot\testing\crud\price_query::find_by_product( 5 );

		$this->assertInternalType( 'array', $groups );
		$this->assertFalse( empty( $groups ) );
		$this->assertSame( 2, count( $groups ) );
		$expected_ids = wp_list_pluck( $groups, 'ID' );
		$ids = wp_list_pluck( $groups, 'ID' );
		$this->assertSame( $expected_ids, $ids );
	}

	/**
	 * Test that we can query by plugin type
	 *
	 * @since 1.1.0
	 *
	 * @group price_query
	 * @group price
	 *
	 * @covers \ingot\testing\crud\price_query::find_by_plugin()
	 */
	public function testQueryByPlugin(){
		$args =  [
			'type'     => 'price',
			'sub_type' => 'edd',
			'meta' => [ 'product_ID'  => 5 ]
		];
		$group_edd = \ingot\testing\crud\group::create( $args, true );
		$this->assertTrue( is_numeric( $group_edd ) );
		for( $i = 0; $i <= 5; $i++ ) {
			\ingot\testing\crud\group::create( $args );
		}
		$args[ 'sub_type' ] = 'woo';
		$group_woo  = \ingot\testing\crud\group::create( $args, true );
		$this->assertTrue( is_numeric( $group_woo ) );
		for( $i = 0; $i <= 5; $i++ ) {
			\ingot\testing\crud\group::create( $args, true );
		}

		$woos = \ingot\testing\crud\price_query::find_by_plugin( 'woo' );
		$this->assertInternalType( 'array', $woos );
		$ids = wp_list_pluck( $woos, 'ID' );
		$this->assertTrue( in_array( $group_woo, $ids ) );
		foreach( $woos as $group ) {
			$this->assertSame( 'woo', $group[ 'sub_type' ] );
		}


	}

	/**
	 * Test that we can query by plugin and exclude those without variants
	 *
	 * @since 1.1.0
	 *
	 * @group xs
	 * @group price_query
	 * @group price
	 *
	 * @covers \ingot\testing\crud\price_query::find_by_plugin()
	 */
	public function testQueryByPluginNoVariant(){
		$args =  [
			'type'     => 'price',
			'sub_type' => 'edd',
			'meta' => [ 'product_ID'  => 5 ]
		];
		$variant_args = [
			'type' => 'price',
			'meta' => [
				'price' => [ ]
			],
			'content'  => 5
		];



		for( $i = 0; $i <= 5; $i++ ) {
			$group_id = \ingot\testing\crud\group::create( $args );
			if( 3 == $i ){
				$expected_id = $group_id;
				$variant_args[ 'group_ID' ] = $group_id;
				$variant_id = \ingot\testing\crud\variant::create( $variant_args, true );
				$group = \ingot\testing\crud\group::read( $group_id );
				$group[ 'variants' ] = [ $variant_id ];
				\ingot\testing\crud\group::update( $group, $group_id, true );

			}

		}

		$groups = \ingot\testing\crud\price_query::find_by_plugin( 'edd', true );
		$this->assertInternalType( 'array', $groups );
		$this->assertFalse( empty( $groups ) );
		$ids = wp_list_pluck( $groups, 'ID' );
		$this->assertTrue( in_array( $expected_id, $ids ) );
		$this->assertSame( 1, count( $groups ) );

	}

}
