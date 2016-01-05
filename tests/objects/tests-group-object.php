<?php
/**
 * Test groups and variants working together via Group Object
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class tests_groups_variants extends  \WP_UnitTestCase {
	/**
	 * Test that when we can create object using group ID
	 *
	 * @since 0.4.0
	 *
	 * @group group
	 * @group group_object
	 *
	 * @covers \ingot\testing\object\group::__construct()
	 * @covers \ingot\testing\object\group::get_group_config()
	 * @covers \ingot\testing\object\group::get_ID()
	 * @covers \ingot\testing\object\group::set_group()
	 * @covers \ingot\testing\object\group::validate_group()
	 */
	public function testInitObjectByID() {
		$groups = ingot_tests_make_groups( false, 1, 1 );
		$id = $groups[ 'ids'][0];
		$group = \ingot\testing\crud\group::read( $id );
		$obj = new \ingot\testing\object\group( $id );
		$this->assertInstanceOf( '\ingot\testing\object\group', $obj );
		$this->assertEquals( $group, $obj->get_group_config() );
		$this->assertEquals( $id, $obj->get_ID() );
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
	 * @covers \ingot\testing\object\group::get_group_config()
	 * @covers \ingot\testing\object\group::set_group()
	 * @covers \ingot\testing\object\group::validate_group()
	 */
	public function testExtraParams() {
		$params = array(
			'type'     => 'click',
			'sub_type' => 'button',
			'meta'     => [ 'link' => 'https://bats.com' ],
			'hats'     => 'bats'
		);

		$id = \ingot\testing\crud\group::create( $params );

		$obj = new \ingot\testing\object\group( $id );

		$this->assertArrayNotHasKey( 'hats', $obj->get_group_config() );

		$obj->update_group( ['cats' => 'dogs' ] );

		$this->assertArrayNotHasKey( 'cats', $obj->get_group_config() );
		$group = \ingot\testing\crud\group::read( $id );
		$this->assertArrayNotHasKey( 'cats', $group );
		$this->assertArrayNotHasKey( 'hats', $group );
	}

	/**
	 * Test that when we can create object using group config array
	 *
	 * @since 0.4.0

	 * @group group
	 * @group group_object
	 *
	 * @covers \ingot\testing\object\group::__construct()
	 * @covers \ingot\testing\object\group::get_group_config()
	 * @covers \ingot\testing\object\group::get_ID()
	 * @covers \ingot\testing\object\group::set_group()
	 * @covers \ingot\testing\object\group::validate_group()
	 */
	public function testInitObjectByArray() {
		$groups = ingot_tests_make_groups( false, 1, 1 );
		$id = $groups[ 'ids'][0];
		$group = \ingot\testing\crud\group::read( $id );
		$obj = new \ingot\testing\object\group( $group );
		$this->assertInstanceOf( '\ingot\testing\object\group', $obj );
		$this->assertEquals( $group, $obj->get_group_config() );
		$this->assertEquals( $id, $obj->get_ID() );
	}

	/**
	 * Test that when we can update variants through group object
	 *
	 * @since 0.4.0

	 * @group group
	 * @group group_object
	 *
	 * @covers \ingot\testing\object\group::get_group_config()
	 * @covers \ingot\testing\object\group::update_group()
	 */
	public function testVariantUpdateThroughObject() {
		$groups = ingot_tests_make_groups( false, 1, 3 );
		$id = $groups[ 'ids'][0];
		$this->assertTrue( is_numeric( $id ) );
		$group = \ingot\testing\crud\group::read( $id );
		$variants = $groups[ 'variants' ][ $id ];

		$obj = new \ingot\testing\object\group( $id );
		$obj->update_group( [ 'variants' => $variants ] );
		$this->assertEquals( $variants, $obj->get_group_config()[ 'variants' ] );
		$group = \ingot\testing\crud\group::read( $id );
		$this->assertEquals( $variants, $group[ 'variants' ] );

	}

	/**
	 * Test that when we can update other stuff through group object
	 *
	 * @since 0.4.0
	 *
	 * @group group
	 * @group group_object
	 *
	 * @covers \ingot\testing\object\group::get_group_config()
	 * @covers \ingot\testing\object\group::update_group()
	 */
	public function testUpdateThroughObject() {
		$groups = ingot_tests_make_groups( true, 1, 3 );
		$id = $groups[ 'ids'][0];
		$this->assertTrue( is_numeric( $id ) );
		$variants = $groups[ 'variants' ][ $id ];

		$obj = new \ingot\testing\object\group( $id );
		$this->assertEquals( $variants, $obj->get_group_config()[ 'variants' ] );

		$new_data = [ 'name' => 'BATMAN'];
		$obj->update_group( $new_data );
		$group = \ingot\testing\crud\group::read( $id );
		$this->assertEquals( 'BATMAN', $group[ 'name' ] );

		$new_data = [
			'name' => 'Hi Roy',
			'sub_type' => 'button_color'
		];


		$obj->update_group( $new_data );
		$group = \ingot\testing\crud\group::read( $id );
		$this->assertEquals( 'Hi Roy', $group[ 'name' ] );
		$this->assertEquals( 'button_color', $group[ 'sub_type' ] );
	}

}
