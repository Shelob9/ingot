<?php

/**
 * Class test_group_crud
 *
 * Test group CRUD
 */
class group_crud extends \WP_UnitTestCase {


	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
		\ingot\testing\crud\group::delete( 'all' );
	}

	/**
	 * Test that table name is right
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\tracking::get_table_name()
	 */
	public function testTableName() {
		$tablename = \ingot\testing\crud\group::get_table_name();
		global $wpdb;
		$this->assertEquals( $wpdb->prefix . 'ingot_group', $tablename );
	}


	/**
	 * Test create
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 */
	public function testCreateMinimal() {
		$params = array(
			'type' => 'click',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertTrue( is_int( $created ) );
		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );

	}

	/**
	 * Test create with extra params to make sure they don't return/get saved
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\crud::prepare_data()
	 */
	public function testExtraParams() {
		$params = array(
			'type' => 'click',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
			'hats' => 'bats'
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertTrue( is_int( $created ) );
		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );
		$group = \ingot\testing\crud\group::read( $created );
		$this->assertArrayNotHasKey( 'hats', $group );

		$params = array(
			'type' => 'click',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
			'hats' => 'bats',
			'cats' => 'dogs'
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertTrue( is_int( $created ) );
		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );
		$group = \ingot\testing\crud\group::read( $created );
		$this->assertArrayNotHasKey( 'hats', $group );

	}

	/**
	 * Test read
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\read::create()
	 */
	public function testRead() {
		$params = array(
			'type' => 'click',
			'sub_type' => 'button_color',
			'meta' => [ 'link' => 'https://bats.com' ],

		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertTrue( is_int( $created ) );
		$group = \ingot\testing\crud\group::read( $created );
		$this->assertTrue( is_array( $group ) );

		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'http://faces.com' ],

		);

		$created_2 = \ingot\testing\crud\group::create( $params );
		$group = \ingot\testing\crud\group::read( $created_2 );
		$this->assertTrue( is_array( $group ) );
		$this->assertEquals( $group[ 'meta' ][ 'link' ], 'http://faces.com' );
		$this->assertEquals( $group[ 'type' ], 'click' );
		$this->assertEquals( $group[ 'sub_type' ], 'button' );

		$group = \ingot\testing\crud\group::read( $created );
		$this->assertTrue( is_array( $group ) );
		$this->assertEquals( $group[ 'type' ], 'click' );
		$this->assertEquals( $group[ 'sub_type' ], 'button_color' );

	}

	/**
	 * Test that optional fields are filled in.
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\group::fill_in()
	 * @covers \ingot\testing\crud\group::validate_config()
	 */
	public function testCreateFillIn() {
		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'link',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );

		$group = \ingot\testing\crud\group::read( $created );

		foreach( \ingot\testing\crud\group::get_required_fields() as $field ) {
			$this->assertArrayHasKey( $field, $group );
		}

		foreach( \ingot\testing\crud\group::get_needed_fields() as $field ) {
			$this->assertArrayHasKey( $field, $group );
		}
	}

	/**
	 * Test that a missing required field will make an error
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\group::validate_config()
	 */
	public function testCreateRequired() {
		$params = array(
			rand() => rand()
		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertWPError( $created );

		$params = array(
			'name' => rand()
		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertWPError( $created );

	}

	/**
	 * Test that a group is of the right type doesn't false trip type check
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\group::validate_config()
	 * @covers \ingot\testing\crud\group::validate_type()
	 */
	public function testValidType() {
		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'link',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );

		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );

		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'button_color',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );

		$this->assertFalse(  is_wp_error( $created ) );
		$this->assertTrue( is_numeric( $created ) );
	}

	/**
	 * Test that invalid type groups return an error
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\group::validate_config()
	 * @covers \ingot\testing\crud\group::validate_type()
	 */
	public function testInvalidType() {
		$params = array(
			'type' => 'josh',
			'name' => 'bats',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertWPError( $created );
	}

	/**
	 * Test that invalid  sub_type groups return an error
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::create()
	 * @covers \ingot\testing\crud\crud::validate_config()
	 * @covers \ingot\testing\crud\crud::validate_sub_type()
	 */
	public function testInvalidSubType() {
		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'hats',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertWPError( $created );
	}


	/**
	 * Test that we can delete a group
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::delete()
	 */
	public function testDelete() {
		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );
		$this->assertNotEmpty( \ingot\testing\crud\group::read( $created ) );
		$deleted = \ingot\testing\crud\group::delete( $created );
		$this->assertFalse( \ingot\testing\crud\group::read( $created ) );
	}

	/**
	 * Test that we can update a group
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::update()
	 */
	public function testUpdate() {
		$params = array(
			'type' => 'click',
			'name' => 'bats',
			'sub_type' => 'button',
			'meta' => [ 'link' => 'https://bats.com' ],
		);

		$created = \ingot\testing\crud\group::create( $params );
		$group = \ingot\testing\crud\group::read( $created );
		$group[ 'name' ] = 'changed';
		\ingot\testing\crud\group::update( $group, $created );
		$group = \ingot\testing\crud\group::read( $created );

		foreach( \ingot\testing\crud\group::get_required_fields() as $field ) {
			$this->assertArrayHasKey( $field, $group );
		}

		foreach( \ingot\testing\crud\group::get_needed_fields() as $field ) {
			$this->assertArrayHasKey( $field, $group );
		}

		$this->assertEquals( $group[ 'name' ],  'changed' );
	}

	/**
	 * Test that we can delete a group
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::delete()
	 */
	public function testDeleteAll() {
		for ( $i=0; $i <= 7; $i++ ) {
			$params = array(
				'type' => 'click',
				'name' => $i,
				'sub_type' => 'button',
				'meta' => [ 'link' => 'https://bats.com' ],
			);
			$created[ $i ] = \ingot\testing\crud\group::create( $params );
		}


		\ingot\testing\crud\group::delete( 'all' );

		$items = \ingot\testing\crud\group::get_items( array() );
		$this->assertEquals( count( $items ), 0 );

	}

	/**
	 * Test limiting of get_all() query
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::get_items()
	 */
	public function testGetItemsLimit() {
		\ingot\testing\crud\group::delete( 'all' );
		for ( $i=1; $i <= 7; $i++ ) {
			$params = array(
				'name' => $i,
				'type' => 'click',
				'sub_type' => 'button',
				'meta' => [ 'link' => 'https://bats.com' ],
			);

			$created[ $i ] = \ingot\testing\crud\group::create( $params );
		}

		$params = array(
			'limit' => 5,
			'page' => 1,
		);

		$items = \ingot\testing\crud\group::get_items( $params );
		$this->assertEquals( count( $items ), 5 );


	}


	/**
	 * Test that when we ask for too high of a page, we get nothing back
	 *
	 * @since 0.0.7
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::get_items()
	 */
	public function testGetItemsPaginationTooHigh() {
		\ingot\testing\crud\group::delete( 'all' );
		for ( $i=1; $i <= 11; $i++ ) {
			$params = array(
				'name' => $i,
				'type' => 'click',
				'sub_type' => 'button',
				'meta' => [ 'link' => 'https://bats.com' ],
			);

			$created[ $i ] = \ingot\testing\crud\group::create( $params );
		}

		$params = array(
			'limit' => 5,
			'page' => 4,
		);

		$items = \ingot\testing\crud\group::get_items( $params );
		$this->assertEmpty( $items );



	}

	/**
	 * Test that the ingot_tests_make_groups() utility function works properly
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers ingot_tests_make_groups()
	 */
	public function testTestFunction(){
		$groups = ingot_tests_make_groups( true, 5, 3 );
		$this->assertSame( 6, count( $groups[ 'ids' ] ) );
		$groups = ingot_tests_make_groups( true, 2, 3 );
		$this->assertSame( 3, count( $groups[ 'ids' ] ) );

		$groups = ingot_tests_make_groups( true, 2, 3 );
		$group_ids = $groups[ 'ids' ];
		foreach( $group_ids as $id ){
			$variants = $groups[ 'variants' ][ $id ];
			$group = \ingot\testing\crud\group::read( $id );
			$this->assertTrue( is_array( $group ) );
			$this->assertSame( $variants, $group[ 'variants' ] );
		}

	}

	/**
	 * Test that when we can add variants to groups properly
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::update()
	 */
	public function testAddVariantsToGroup() {
		$groups = ingot_tests_make_groups( false, 1, 3 );
		$id = $groups[ 'ids'][0];
		$this->assertTrue( is_numeric( $id ) );
		$group = \ingot\testing\crud\group::read( $id );
		$group[ 'variants' ] = $groups[ 'variants' ][ $id ];
		$updated = \ingot\testing\crud\group::update( $group, $id  );
		$this->assertTrue( is_numeric( $updated ) );
		$group = \ingot\testing\crud\group::read( $id );
		$this->assertSame( $group[ 'variants' ], $groups[ 'variants' ][ $id ] );

	}


	/**
	 * Test that when we can add levers directly
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 *
	 * @covers \ingot\testing\crud\group::update()
	 */
	public function testLevers(){
		$groups = ingot_tests_make_groups( true, 1, 3 );
		$group_id = $groups[ 'ids' ][0];
		$bandit = new \ingot\testing\bandit\content( $group_id );
		$group = \ingot\testing\crud\group::read( $group_id );

		$this->assertFalse( empty( $group[ 'levers' ] ) );
		$this->assertArrayHasKey( (int) $group_id, $group[ 'levers' ] );
		$this->assertSame( 4, count( $group[ 'levers' ][ $group_id ] ) );
	}

	/**
	 * Test that when we can create price tests properly
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 * @group price
	 *
	 * @covers \ingot\testing\crud\group::create()
	 */
	public function testForPriceEDD(){
		$data = ingot_test_data_price::edd_tests();
		if( is_wp_error( $data ) ) {
			var_dump( $data );
			wp_die();
		}
		$group_id =  $data[ 'group_ID' ];
		$this->assertTrue( is_numeric( $group_id ) );
		$group = \ingot\testing\crud\group::read( $group_id );
		$this->assertTrue( is_array( $group ) );
		$this->assertSame( 'price', $group[ 'type' ] );
		$this->assertSame( 'edd', $group[ 'sub_type' ] );

	}

	/**
	 * Test that when we can create price tests properly
	 *
	 * @since 0.4.0
	 *
	 * @group crud
	 * @group group
	 * @group group_crud
	 * @group price
	 *
	 * @covers \ingot\testing\crud\group::get_items()
	 */
	public function testQueryByType(){
		$data = ingot_test_data_price::edd_tests();
		ingot_tests_make_groups( false, 11, 1 );
		$data = ingot_test_data_price::edd_tests();
		$group_id = $data[ 'group_ID' ];
		$items = \ingot\testing\crud\group::get_items( [
			'type' => 'price',
			'limit' => 5
		]);

		$this->assertTrue( is_array( $items ) );
		$this->assertNotEmpty( $items );
		$this->assertSame( 2, count( $items ) );
		foreach( $items as $item ){
			$this->assertEquals( 'price', $item[ 'type' ] );
		}
	}

}
