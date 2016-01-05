<?php
/**
 * CRUD for variants
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\crud;


class variant extends crud {

	public static $what = 'variant';


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
	public static function validate_config( $data ){

		if(  ! is_numeric( $data[ 'group_ID' ] ) ){
			return new \WP_Error( 'ingot-invalid-config', __(  'Variant\'s group_ID type field numeric', 'ingot'  ), $data );
		}

		if( 'price' == $data[ 'type' ] && ! is_numeric( $data[ 'content' ] ) ){
			return new \WP_Error( 'ingot-invalid-config', __(  'Variant\'s content field for price tests must be ID of a product', 'ingot'  ), $data );
		}

		if ( false == self::validate_type( $data ) ) {
			return new \WP_Error( 'ingot-invalid-group-type', __( 'Invalid group type', 'ingot'  ), $data );
		}

		foreach( self::required() as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				return new \WP_Error( 'ingot-invalid-config', __( sprintf( 'Variants require the field %s', $key ), 'ingot'  ), $data );
			}

		}


		return $data;


	}

	protected static function fill_in( $data ){
		if( ! isset( $data[ 'variants' ] ) || ! ! is_array( $data[ 'variants'] ) ) {
			$data[ 'variants' ] = [];
		}

		return parent::fill_in( $data );
	}


	/**
	 * Get array of required fields
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function required(){
		return [
			'type',
			'group_ID',
			'content'
		];
	}

	/**
	 * Get array of non-required, yet necessary fields
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function needed(){
		return [
			'meta',
			'created',
			'modified'
		];

	}



}
