<?php
/**
 * API route for test groups.
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\api\rest;


use ingot\testing\crud\group;
use ingot\testing\crud\sequence;
use ingot\testing\crud\test;
use ingot\testing\crud\variant;
use ingot\testing\object\posts;
use ingot\testing\types;
use ingot\testing\utility\helpers;
use ingot\testing\api\rest\util;

class groups extends route {

	/**
	 * Identify object type for this route collection
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $what = 'groups';

	/**
	 * Register routes
	 *
	 * @since 0.4.0
	 */
	public function register_routes() {
		parent::register_routes();
		$namespace = $this->make_namespace();
		$base = $this->base();
		register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)/stats', array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(),
				),
			)
		);
		register_rest_route( $namespace, '/' . $base . '/post/(?P<id>[\d]+)/stats', array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_posts' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_posts' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => [
						'group_ids' => [
							'type' => 'array',
							'required' => true,
							''
						]
					],
				),
			)
		);
	}

	/**
	 * Get groups
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = array(
			'page' => $request->get_param( 'page' ),
			'limit' => $request->get_param( 'limit' ),
			'type' => $request->get_param( 'type' )
		);

		$groups = group::get_items( $args );
		if( ! empty( $groups ) ) {
			foreach( $groups as $i => $group ) {
				if ( is_array( $group ) ) {
					$groups[ $i ] = $this->prepare_group( $group, $request->get_param( 'context' ) );
				}
			}
		}

		return ingot_rest_response( $groups, 200, (int) group::total() );

	}

	/**
	 * Create a group
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_item( $request ) {
		$variants = helpers::sanitize( $request->get_param( 'variants' ) );

		$group_args = $this->prepare_item_for_database( $request, true );
		$group_args[ 'variants' ] = [];

		$id = group::create( $group_args );
		$item = new \WP_Error( 'ingot-unknown-error' );
		if( is_wp_error( $id ) ) {
			$item = $id;
		}

		if ( is_numeric( $id ) ) {
			$item = group::read( $id );
			$item[ 'variants' ] = $variants;
			if ( is_array( $item ) ) {
				$variants_ids = $this->save_variants( $item );
				if( is_wp_error( $variants_ids ) ) {
					return ingot_rest_response( $variants_ids, 500 );
				}

				$item[ 'variants' ] = $variants_ids;
				group::update( $item, $item[ 'ID' ] );
				$item = group::read( $item[ 'ID' ] );
				if ( is_array( $item ) ) {
					if ( is_array( $item ) ) {
						$item = $this->prepare_group( $item, $request->get_param( 'context' ) );
					}

					return ingot_rest_response( $item, 201, 1 );

				}

			}

		}

		return ingot_rest_response( $item, 500 );

	}

	/**
	 * Get a group
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ){

		$url = $request->get_url_params( );
		$id = helpers::v( 'id', $url, 0 );
		$group = group::read( $id );
		if( ! is_array( $group ) ){
			if ( is_wp_error( $group ) ) {
				return $group;
			}

			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}


		return ingot_rest_response(
			$this->prepare_group( $group, $request->get_param( 'context') ),
			201 );

	}


	/**
	 * Update a group
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ){
		$url = $request->get_url_params( );
		$id = helpers::v( 'id', $url, 0 );
		$existing = group::read( $id );
		if( ! is_array( $existing ) ){
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}


		$obj = new \ingot\testing\object\group( $existing );
		$group_args = $this->prepare_item_for_database( $request );

		$obj->update_group( $group_args );
		return ingot_rest_response(
			$this->prepare_group( $obj->get_group_config(), $request->get_param( 'context' ) ), 201 );

	}


	/**
	 * Delete a group
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete_item( $request ){
		$url = $request->get_url_params( );
		$id = helpers::v( 'id', $url, 0 );
		$existing = group::read( $id );
		if( ! is_array( $existing ) ){
			if ( is_wp_error( $existing ) ) {
				return $existing;
			}

			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}

		$deleted = group::delete( $id );
		if( $deleted ) {
			return ingot_rest_response(
				[ 'message' => esc_html__( 'Group Deleted', 'ingot') ],
				204
			);
		}

	}

	/**
	 * Get stats for a group
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_stats( $request ){
		$url = $request->get_url_params( );
		$id = helpers::v( 'id', $url, 0 );
		$group = group::read( $id );
		if( ! is_array( $group ) ){
			if ( is_wp_error( $group ) ) {
				return $group;
			}

			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}

		$obj = new \ingot\testing\object\group( $group );
		$stats = $obj->get_stats();
		if( 'admin' == $request->get_param( 'context' )  ) {
			$names = $obj->names();
			if( ! empty( $stats[ 'variants' ] ) && ! empty( $names[ 'variants' ] ) ){
				foreach( $names[ 'variants' ] as $v_id => $name ) {
					if( isset( $stats[ 'variants' ][ $v_id ] ) ){
						$stats[ 'variants' ][ $v_id ] = (array) $stats[ 'variants' ][ $v_id ];
						$stats[ 'variants' ][ $v_id ][ 'name' ] = $name;
						$stats[ 'variants' ][ $v_id ] = (object) $stats[ 'variants' ][ $v_id ];
					}

				}

			}

			$stats[ 'names' ] = $names;
		}

		return ingot_rest_response( $stats, 200 );

	}



	public function args( $require_id = true ) {
		$args =  array(
			'id'                   => array(
				'description'        => __( 'ID of group', 'ingot' ),
				'type'               => 'integer',
				'default'            => 1,
				'sanitize_callback'  => 'absint',
			),
			'name'              => array(
				'description'        => __( 'Name of Test Group', 'ingot' ),
				'type'               => 'string',
				'default'            => '',
				'sanitize_callback'  => array( $this, 'strip_tags' ),
				'required'           => true,
			),
			'type'               => array(
				'description'        => __( 'Type of Test Group', 'ingot' ),
				'type'               => 'string',
				'default'            => 'click',
				'validation_callback' => array( $this, 'allowed_type' ),
				'sanitize_callback'  => array( $this, 'strip_tags' ),
				'required'           => 'true',
			),
			'link' => array(
				'description'        => __( 'Link for content tests', 'ingot' ),
				'type'               => 'text',
				'default'            => '',
				'sanitize_callback'  => array( $this, 'url' ),
				'required'           => true,
			),
			'page' => array(
				'description'        => __( 'Page of results.', 'ingot' ),
				'type'               => 'integer',
				'default'            => 1,
				'sanitize_callback'  => 'absint',
			),
			'variants' => array(
				'description'       => __( 'Tests to add to group', 'ingot' ),
				'type'              => 'array',
				'default'           => array(),
			),
			'meta' => array(
				'description'       => __( 'Meta data for group', 'ingot' ),
				'type'              => 'array',
				'default'           => array(),
			),


		);

		if ( $require_id ){
			$args[ 'ID' ][ 'required' ] = true;
		}

		return $args;
	}



	protected function prepare_group( $group, $context = 'view' ){
		unset( $group[ 'levers' ] );
		if ( 'admin' == $context ) {
			$group = $this->prepare_group_in_admin_context( $group );
		}

		return $group;
	}

	/**
	 * Prepare a group if in admin context
	 *
	 * @since 0.2.0
	 *
	 * @access protected
	 *
	 * @param array $group Group Config
	 *
	 * @return array Group config with additional data for making admin
	 */
	protected function prepare_group_in_admin_context( $group ) {
		if( ! empty( $group[ 'variants' ] ) ){
			foreach( $group[ 'variants' ] as $i => $variant_id ) {
				unset( $group[ 'variants' ][ $i ] );
				if( ! is_numeric( $variant_id ) ) {
					if( is_array( $variant_id ) && isset( $variant_id[ 'ID' ] ) ){
						$variant_id = $variant_id[ 'ID' ];
					}else{
						continue;

					}

				}

				if( is_array( $variant = variant::read( $variant_id ) ) ) {
					$group[ 'variants' ][ $variant[ 'ID'] ] = variant::read( $variant_id );
				}

			}

		}

		return $group;
	}


	/**
	 * Get fields allowed to be saved in this route
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	protected function allowed_fields(){
		$fields = \ingot\testing\crud\group::get_all_fields();
		$fields = array_flip( $fields );
		unset( $fields[ 'levers' ] );
		$fields = array_flip( $fields );
		return $fields;

	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @since 0.4.0
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request, $new_group = false ) {
		$group_args = $request->get_params();
		$allowed    = $this->allowed_fields();
		if ( false == $new_group  ) {
			$group_args[ 'variants' ] = $this->save_variants( $group_args );
			if ( is_wp_error( $group_args[ 'variants' ] ) ) {
				return $group_args[ 'variants' ];

			}
		}

		foreach ( $group_args as $key => $value ) {
			if ( is_numeric( $key ) || ! in_array( $key, $allowed ) ) {
				unset( $group_args[ $key ] );
			}
		}

		return $group_args;

	}

	/**
	 * Save variants and return IDs
	 *
	 * @since 0.4.0
	 *
	 * @param array $group Group config -- variants key should be an array of variant configs to save
	 *
	 * @return array||WP_Error
	 */
	protected function save_variants( $group ){
		$variants_ids = [];
		$variants = helpers::v( 'variants', $group, [] );
		if( isset( $group[ 'ID' ] ) ){
			$group_id = $group[ 'ID' ];
		} elseif( isset( $group[ 'id'] ) ){
			$group_id = $group[ 'id' ];
		}else{
			return new \WP_Error( 'ingot-generalized-failure' );
		}
		if ( ! empty( $variants ) ) {
			foreach ( $variants as $variant ) {
				if( is_numeric( $variant ) ) {
					continue;
				}

				$variant[ 'group_ID' ] = $group_id;
				$variant[ 'type' ]     = $group[ 'type' ];
				if( ( ! isset( $variant[ 'content' ] ) || empty( $variant[ 'content'] ) ) && 'button_color' == $group[ 'sub_type'] )  {
					$variant[ 'content' ] = '  ';
				}

				if( ! isset( $variant[ 'ID' ] ) || 0 == abs( $variant[ 'ID' ] ) ) {
					unset( $variant[ 'ID' ] );
					$_variant_id           = variant::create( $variant );
				}else{
					$_variant_id           = variant::update( $variant, $variant[ 'ID' ] );
				}


				if ( is_wp_error( $_variant_id ) ) {
					return $_variant_id;
				}

				$variants_ids[] = $_variant_id;
			}

		}

		return $variants_ids;

	}

	/**
	 * Get groups associated with a post
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_groups( $request ){
		$url = $request->get_url_params( );
		$post_id = (int) helpers::v( 'id', $url, 0 );
		$post = get_post( $post_id );
		if( ! is_a( $post, 'WP_POST' ) ){
			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}

		$obj = new posts( $post );
		return ingot_rest_response( $obj->get_groups() );
	}

	/**
	 * Update groups associated with a post
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_posts( $request ){
		$url = $request->get_url_params( );
		$post_id = (int) helpers::v( 'id', $url, 0 );
		$post = get_post( $post_id );
		if( ! is_a( $post, 'WP_POST' ) ){
			return ingot_rest_response(
				[ 'message' => esc_html__( 'No group found', 'ingot') ]
			);
		}

		$obj = new posts( $post );
		$obj->add( $request->get_param( 'group_ids' ) );
		return ingot_rest_response( $obj->get_groups() );
		
	}

}
