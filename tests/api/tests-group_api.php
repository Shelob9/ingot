<?php
/**
 * Tests for groups route of REST API
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class tests_group_api extends ingot_rest_test_case {

	public function setUp(){
		parent::setUp();

	}

	public function tearDown() {
		parent::tearDown();

	}

	/**
	 * Route name
	 *
	 * @since 0.4.0
	 *
	 * @var string
	 */
	protected $route_name = 'groups';


	/**
	 * Test getting a collection of items
	 *
	 * @since 0.4.0
	 *
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::get_items()
	 */
	public function testGetItems(){
		wp_set_current_user( 1 );
		$groups = ingot_tests_make_groups( true, 4, 3 );
		$request = new \WP_REST_Request( 'GET', $this->namespaced_route );
		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );
		$data = (array) $response->get_data();
		$this->assertEquals( count( $groups[ 'ids' ] ), count( $data ) );
		$fields = $this->get_fields_to_check_for();
		foreach( $data as $group  ){
			$this->assertTrue( in_array( $group[ 'ID' ], $groups[ 'ids' ] ) );
			$this->assertEquals( count( $group ), count( $fields ) );
			$this->assertEquals( 'click', $group[ 'type' ] );
			$group_direct = \ingot\testing\crud\group::read( $group[ 'ID' ] );
			foreach( $fields as $field ){
				$this->assertArrayHasKey( $field, $group );
				$this->assertEquals( $group_direct[ $field ], $group[ $field ] );
			}


		}
	}

	/**
	 * Test getting price tests via API
	 *
	 * @since 1.0.0
	 *
	 * @group price
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::get_items()
	 */
	public function testGetPrice(){
		wp_set_current_user( 1 );
		ingot_test_data_price::edd_tests();
		ingot_test_data_price::edd_tests();
		ingot_tests_make_groups( true, 4, 3 );

		$groups = ingot_tests_make_groups( true, 4, 3 );
		$request = new \WP_REST_Request( 'GET', $this->namespaced_route );
		$request->set_query_params( array(
			'type' => 'price'
		) );
		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );
		$data = (array) $response->get_data();
		foreach( $data as $group  ){
			$this->assertEquals( 'price', $group[ 'type' ] );
		}
	}


	/**
	 * Test getting a one item
	 *
	 * @since 0.4.0
	 *
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::get_item()
	 */
	public function testGetItem(){
		wp_set_current_user( 1 );
		$groups = ingot_tests_make_groups( true, 2, 3 );
		$id =  $groups[ 'ids' ][1];
		$request = new \WP_REST_Request( 'GET', $this->namespaced_route  . '/' . $id );
		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 201, $response->get_status() );
		$data = (array) $response->get_data();

		$group = \ingot\testing\crud\group::read( $id );



		$fields = $this->get_fields_to_check_for();
		foreach( $fields as $field ){
			$this->assertArrayHasKey( $field, $data );
			$this->assertEquals( $group[ $field ], $data[ $field ] );
		}
	}

	/**
	 * Test updating one item
	 *
	 * @since 0.4.0
	 *
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::update_item()
	 */
	public function testUpdateItem(){
		wp_set_current_user( 1 );
		$groups = ingot_tests_make_groups( true, 2, 3 );
		$id =  $groups[ 'ids' ][1];
		$request = new \WP_REST_Request( 'POST', $this->namespaced_route  . '/' . $id );
		$request->set_query_params( array(
			'ID' => $id,
			'type' => 'click',
			'sub_type' => 'button_color',
			'name' => 'josh'
		) );

		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 201, $response->get_status() );
		$data = (array) $response->get_data();
		$group = \ingot\testing\crud\group::read( $id );
		$fields = $this->get_fields_to_check_for();
		$this->assertTrue( is_array( $data ) );
		$this->assertEquals( count( $fields ), count( $data ), var_export( $data, true ) );
		foreach( $fields as $field ) {
			$this->assertArrayHasKey( $field, $data );
			$this->assertSame( $group[ $field ], $data[ $field ] );
		}


	}

	/**
	 * Test creating one item
	 *
	 * @since 0.4.0
	 *
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::delete_item()
	 */
	public function testDeleteItem(){
		wp_set_current_user( 1 );
		wp_set_current_user( 1 );
		$groups = ingot_tests_make_groups( true, 2, 3 );
		$id =  $groups[ 'ids' ][1];
		$request = new \WP_REST_Request( 'DELETE', $this->namespaced_route . '/' . $id );

		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 204, $response->get_status() );

		$deleted = \ingot\testing\crud\group::read( $id );
		$this->assertFalse( is_array($deleted ));
		$not_deleted = \ingot\testing\crud\group::read( $groups[ 'ids' ][0] );
		$this->assertTrue( is_array( $not_deleted ) );


	}

	/**
	 * Test deleting one item
	 *
	 * @since 0.4.0
	 *
	 * @group rest
	 * @group group_rest
	 * @group group
	 *
	 * @covers ingot\testing\api\rest\groups::create_item()
	 */
	public function testCreateItem(){
		wp_set_current_user( 1 );

		$request = new \WP_REST_Request( 'POST', $this->namespaced_route );
		$request->set_query_params( array(
			'type'      => 'click',
			'sub_type' => 'button_color',
			'name' => 'x2'
		) );

		$response = $this->server->dispatch( $request );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 201, $response->get_status() );
		$data = (array) $response->get_data();

		$this->assertArrayHasKey( 'ID', $data );
		$group = \ingot\testing\crud\group::read( $data[ 'ID'] );
		$fields = $this->get_fields_to_check_for();
		$this->assertTrue( is_array( $data ) );
		$this->assertEquals( count( $fields ), count( $data ), var_export( $data, true ) );
		foreach( $fields as $field ) {
			$this->assertArrayHasKey( $field, $data );
			$this->assertSame( $group[ $field ], $data[ $field ] );
		}


	}

	/**
	 * Get all fields that a group in a response should have
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	private function get_fields_to_check_for() {
		$fields = \ingot\testing\crud\group::get_all_fields();
		$fields[] = 'ID';

		$flipped = array_flip( $fields );
		$l = $flipped[ 'levers' ];
		unset( $fields[ $l ] );
		return $fields;
	}

}
