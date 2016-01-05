<?php
/**
 * Group CRUD
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\crud;


use ingot\testing\utility\helpers;

class group extends crud {


	/**
	 * Name of this object
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected static $what = 'group';

	protected static function what() {
		return 'group';
	}

	/**
	 * Ensure an array has all the needed fields for a specific type
	 *
	 * @since 1.1.0
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public static function valid( $data ){
		if( parent::valid( $data ) && 'price' == $data[ 'type' ] ){
			return isset( $data[ 'meta' ][ 'product_ID' ] ) && is_numeric( $data[ 'meta' ][ 'product_ID' ] );
		}

	}

	/**
	 * Get a collection of items
	 *
	 * @since 0.4.0
	 *
	 * @param array $params {
	 *  $group_id int ID of group to get all
	 *  $ids array Optional. Array of ids to get.
	 *  $limit int Optional. Limit results, default is -1 which gets all.
	 *  $page int Optional. Page of results, used with $limit. Default is 1
	 *  $return string Optional. What to return all|IDs Return all fields or just IDs
	 *  $type string|bool Optional  If false, the default, both price and click groups are returned if is price or click, those types are returned
	 * }
	 *
	 * @return array
	 */

	/**
	 * Save levers for a group
	 *
	 * @since 0.4.0
	 *
	 * @param int $id Group ID
	 * @param array $levers Array of \MaBandit\Lever objects
	 *
	 * @return bool|int
	 */
	public static function save_levers( $id, $levers ) {

		foreach( $levers[ $id ] as $i => $lever ) {
			if( ! is_object( $lever ) || ! is_a( $lever, '\MaBandit\Lever' ) ) {
				unset( $levers[ $i ] );
			}
		}

		$table_name = static::get_table_name();
		if( self::can( $id, true ) ) {
			global $wpdb;
			$wpdb->update(
				$table_name,
				array(
					'levers' => serialize( $levers )
				),
				array( 'ID' => $id )

			);


			$id =  $wpdb->insert_id;

			return $id;

		}else{
			return false;

		}

	}

	/**
	 * Get a group's levers
	 *
	 * @since 0.4.0
	 *
	 * @param int|array $group Group ID or array
	 *
	 * @return array
	 */
	public static function get_levers( $group ) {
		if( is_numeric( $group ) ) {
			$group = self::read( $group );
		}

		if( is_array( $group ) && ! empty( $group[ 'levers' ] ) ) {
			return  $group[ 'levers' ];

		}

	}

	/**
	 * Get a group's levers by ID
	 *
	 * @since 0.4.0
	 *
	 * @param int $id Group ID
	 *
	 * @return array
	 */
	protected static function get_levers_by_id( $id ) {
		return $group = self::read( $id)[ 'levers' ];
	}

	/**
	 * Validate item config
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @param array $data Item config
	 *
	 * @return array|\WP_Error Item config array if valid, WP_Error if not
	 */
	protected static function validate_config( $data ) {
		foreach( self::required() as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return new \WP_Error( 'ingot-invalid-config', __( sprintf( 'Groups require the field: %s', $key ), 'ingot'  ), $data );
			}

		}

		if ( false == self::validate_type( $data ) ) {
			return new \WP_Error( 'ingot-invalid-group-type', __( 'Invalid group type', 'ingot'  ), $data );
		}

		if( 'click' == $data[ 'type' ] && false == self::validate_click_type( $data ) ) {
			return new \WP_Error( 'ingot-invalid-click-group-type', __( 'Invalid click group type', 'ingot'  ), $data );

		}

		//@todo this validation for price group subtypes
		if( 'price' == $data[ 'type' ] && 1 == 3 ) {
			return new \WP_Error( 'ingot-invalid-click-group-type', __( 'Invalid price group type', 'ingot'  ), $data );

		}

		foreach( self::get_all_fields() as $field ){
			if( ! in_array( $field, array( 'variants', 'meta', 'levers' ) ) ) {
				$data[ $field ] = (string) $data[ $field ];
			}else{
				if( ! is_array( $data[ $field ] ) ) {
					$data[ $field ] = array();
				}

			}
		}

		$data = self::prepare_meta( $data );
		if( is_wp_error( $data ) ) {
			return $data;

		}

		$data[ 'variants' ] == helpers::make_array_values_numeric( $data[ 'variants' ], true );

		return $data;

	}

	/**
	 * Validate and sanitize meta array
	 *
	 * @since 0.4.0
	 *
	 * @param array $data
	 *
	 * @return array|\WP_Error Prepared array or WP_Error if invalid
	 */
	protected static function prepare_meta( $data ) {
		if( ! isset( $data[ 'meta' ] ) || empty( $data[ 'meta' ] || ! is_array( $data[ 'meta']) ) ){
			$data[ 'meta'] = [];
			return $data;
		}

		if ( 'click' == $data[ 'type' ] ) {
			foreach ( [ 'color', 'background_color', 'color_test_text', 'link' ] as $field ) {
				if ( isset( $data[ 'meta' ][ $field ] ) && ! empty( $data[ 'meta' ][ $field ] ) && is_string( $data[ 'meta' ][ $field ] ) ) {

					if ( 'link' == $field ) {
						if ( filter_var( $data[ 'meta' ][ $field ], FILTER_VALIDATE_URL ) ) {
							$data[ 'meta' ][ $field ] = esc_url_raw( $data[ 'meta' ][ $field ] );
						} else {
							return new \WP_Error( 'ingot-invalid-config-click-link', __( 'Click groups must have a valid link.', 'ingot' ) );
						}

					} else {
						$data[ 'meta' ][ $field ] = strip_tags( $data[ 'meta' ][ $field ] );
					}
				}else{
					$data[ 'meta' ][ $field ] = '';
				}

			}
		}

		if( 'price' == $data[ 'type' ] ) {

			if( ! isset($data[ 'meta' ][ 'product_ID' ]  ) ){
				return new \WP_Error( 'ingot-invalid-config-no-product-id', __( 'Ingot price tests must set product ID in meta.product_ID', 'ingot' ) );
			}

			if( isset( $data[ 'meta' ][ 'variable_prices' ] ) && is_array(  $data[ 'meta' ][ 'variable_prices' ] ) ){
				$data[ 'meta' ][ 'variable_prices' ] = helpers::make_array_values_numeric( $data[ 'meta' ][ 'variable_prices' ] );
			}else{
				$data[ 'meta' ][ 'variable_prices' ] = [];
			}


		}

		return $data;

	}

	/**
	 * Fill in needed, but not required keys
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @param $data
	 *
	 * @return array
	 */
	protected static function fill_in( $data ) {
		foreach( self::needed() as $field ) {
			if( 'created' == $field || 'modified' == $field ) {
				if( ! isset( $data[ $field ] ) ) {
					$data[ $field ] = current_time( 'mysql' );
				}else{
					$data[  $field  ] = self::date_validation( $data[ $field ] );
				}

			}elseif( in_array( $field, [ 'variants', 'meta', 'levers' ] ) && ( ! isset( $data[  $field ] ) || ! is_array( $data[ $field ] ) ) ) {
				$data[ $field ] = [];
			}else{
				if ( ! isset( $data[ $field ] ) ) {
					$data[ $field ] = '';
				}
			}
		}

		return $data;
	}

	/**
	 * Get a group by variant ID
	 *
	 * @since 0.4.0
	 *
	 * @param int $variant_id
	 *
	 * @return array|bool Group config if found or false if not found
	 */
	public static function get_by_variant_id( $variant_id ) {
		if( is_array( $variant = variant::read( $variant_id ) ) ){
			$group_id = helpers::v( 'group_ID', $variant, 0 );
			if( is_array( $group = self::read( $group_id ) ) ){
				return $group;
			}

		}

		return false;

	}


	/**
	 * Required fields of this object
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function required() {
		$required = array(
			'type'
		);

		return $required;
	}

	/**
	 * Necessary, but not required fields of this object
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function needed() {
		$needed = array(
			'name',
			'sub_type',
			'variants',
			'meta',
			'modified',
			'created',
			'levers'
		);

		return $needed;
	}

}
