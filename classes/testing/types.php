<?php
/**
 * Common util functions for testing types
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
namespace ingot\testing;


class types {

	/**
	 * Allowed test types
	 *
	 * @since 0.0.7
	 *
	 * @return array $types The allowed test types
	 */
	public static function allowed_types() {
		$types = array(
			'click',
			'price',
		);

		/**
		 * Allowed test types
		 *
		 * @since 0.0.7
		 *
		 * @param array $types The allowed test types
		 */
		return apply_filters( 'ingot_allowed_types', $types );

	}

	/**
	 * Allowed click test types
	 *
	 * @since 0.0.7
	 *
	 * @param bool $with_labels Optional. If true, labels are included. If false, the default, only types are returned
	 * @param bool $api_format Optional. Format for use in API. Default is true. Only used if $with_labels is true
	 *
	 * @return array $types The allowed click test types
	 */
	public static function allowed_click_types( $with_labels = false, $api_format = true ) {
		$types = self::internal_click_types( true );

		//@todo figure out how to make content blocks work
		unset( $types[ 'text' ] );

		/**
		 * Allowed test types
		 *
		 * Types are keys, labels are values
		 *
		 * @since 0.0.7
		 *
		 * @param array $types The allowed click test types
		 */
		$types = apply_filters( 'ingot_allowed_click_types', $types );

		if( false == $with_labels ) {
			return array_keys( $types );

		}else{
			if( $api_format ) {
				$options = array();
				foreach ( $types as $value => $label ) {
					$options[] = array(
						'value' => $value,
						'label' => $label['name'],
						'description' => $label['description'],
					);
				}

				return $options;
			}

			return $types;

		}

	}

	/**
	 * Allowed price test types
	 *
	 * @todo deprecate this or ingot_accepted_plugins_for_price_tests()
	 * @since 0.0.7
	 *
	 *  @param bool $with_labels Optional. If true labels as values. Default is false
	 *
	 * @return array $types The allowed click test types
	 */
	public static function allowed_price_types( $with_labels = false ) {
		return ingot_accepted_plugins_for_price_tests( $with_labels );

	}

	/**
	 * Get the internal click types
	 *
	 * @since 1.1.0
	 * @param bool $with_labels Optional. If true, labels are included. If false, the default, only types are returned
	 *
	 * @return array
	 */
	public static function internal_click_types( $with_labels = false ) {
		$types = array(
			'link'         => array(
				'name'        => __( 'Link', 'ingot' ),
				'description' => 'Use this to test link location on a call to action'
			),
			'text'         => array(
				'name'        => __( 'Text', 'ingot' ),
				'description' => 'Use this to test text on a call to action'
			),
			'button'       => array(
				'name'        => __( 'Button', 'ingot' ),
				'description' => 'Use this to test button text call to action'
			),
			'button_color' => array(
				'name'        => __( 'Button Color', 'ingot' ),
				'description' => 'Use this to test button coloring on a call to action'
			),
		);

		if( false == $with_labels ) {
			return array_keys( $types );

		}

		return $types;

	}

}
