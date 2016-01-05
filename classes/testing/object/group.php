<?php
/**
 * Group object
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\object;

use ingot\testing\crud\variant;

class group {

	/**
	 * Group ID
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 * @var int
	 */
	private $ID;

	/**
	 * Group's levers
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 * @var array Contains objects of \MaBandit\Lever class
	 */
	private $levers;

	/**
	 * Group stats
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 * @var object
	 */
	private $stats;

	/**
	 * Group config
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $group;

	private $bypass_cap = false;

	/**
	 * Construct group object
	 *
	 * Passing an invalid group config (or bad ID) will throw an uncatchable exception, a fatal error will occur and you will feel bad.
	 *
	 * @since 0.4.0
	 *
	 * @param int|array $group Group config array or ID
	 */
	public function __construct( $group ) {
		$this->set_group( $group );
	}

	/**
	 * Get group ID
	 *
	 * @since 0.4.0
	 *
	 * @return int
	 */
	public function get_ID(){
		return $this->ID;
	}

	/**
	 * Get group configuration
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	public function get_group_config(){
		return $this->group;
	}



	/**
	 * Update group config
	 *
	 * Note, as opposed to using \ingot\testing\crud\group::update() directly, you do not need to pass all fields, just the ones to be changed. Also, the config in this object will be updated.
	 *
	 * @since 0.4.0
	 *
	 * @param array $data
	 *
	 * @return bool|int|\WP_Error True if updated, or bool if couldn't save or WP_Error if not allowed.
	 */
	public function update_group( $data ) {
		$allowed = $this->all_fields();
		foreach( array_keys( $data ) as $key  ){
			if( ! in_array( $key, $allowed ) ){
				unset( $data[ $key ] );
			}

		}

		$group = wp_parse_args(
			$data,
			$this->group
		);

		$saved = \ingot\testing\crud\group::update( $group, $this->ID, $this->bypass_cap );
		if( is_numeric( $saved ) ){
			$this->set_group( $group );
			$this->set_levers();
			return true;

		}else{
			return $saved;

		}

	}

	public function update_levers( $levers, $save_in_db = true ){
		if ( $save_in_db ) {
			$this->bypass_cap = true;
			$this->update_group( [ 'levers' => $levers ] );
			$this->bypass_cap = false;
		}else{
			$this->levers = $levers;
		}
	}

	/**
	 * Check if this group has saved levers
	 *
	 * @since 0.4.0
	 *
	 * @return bool
	 */
	public function has_levers(){
		$levers = $this->get_levers();
		if( ! empty( $levers ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Get test levers for this group
	 *
	 * @since 0.4.0
	 *
	 * @return array Contains objects of \MaBandit\Lever class
	 */
	public function get_levers(){
		if( is_null( $this->levers ) ){
			$this->set_levers();
		}

		return $this->levers;
	}

	/**
	 * Get a lever by variant ID
	 *
	 * @since 1.1.0
	 *
	 * @param int $variant_id ID of variant
	 *
	 * @return \MaBandit\Lever|null
	 */
	public function get_lever( $variant_id ){
		$levers = $this->get_levers();
		if( is_array( $levers ) && isset( $levers[ $this->ID ], $levers[ $this->ID ][ $variant_id ] ) ){
			return $levers[ $this->ID ][ $variant_id ];

		}
	}

	protected function get_lever_ids(){
		$levers = $this->levers;
	}

	/**
	 * Get stats object
	 *
	 * @since 0.4.0
	 *
	 * @return object
	 */
	public function get_stats() {
		if( is_null( $this->stats ) ){
			$this->set_stats();
		}

		return $this->stats;
	}

	/**
	 * Validate a group config
	 *
	 * @param array $group Group config to check
	 *
	 * @since 0.4.0
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	protected function validate_group( $group ){
		if( ! is_array( $group ) ) {
			throw new \Exception( esc_html__( 'Invalid group passed to Ingot group object.', 'ingot' ) );
		}else{
			$fields = $this->all_fields();
			foreach ( $fields as $field ) {
				if ( ! array_key_exists( $field, $group ) ) {
					throw new \Exception( esc_html__( 'Invalid group config passed to Ingot group object.', 'ingot' ) );
					break;
					return false;
				}

			}
		}

		return true;
	}

	/**
	 * Set group property and ID property if group is valid
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 * @param array|int $group Group config or ID of a saved group
	 *
	 * @throws \Exception
	 */
	private function set_group( $group ) {
		if( is_numeric( $group ) ) {
			$group = \ingot\testing\crud\group::read( $group, true );
		}

		$this->validate_group( $group );
		$this->group = $group;
		$this->ID = $this->group[ 'ID' ];

	}

	/**
	 * Set the stats property
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 */
	private function set_stats() {
		$this->set_levers();
		$obj = new group_stats( $this->levers[ $this->ID ] );
		$this->stats = $obj->get_stats();
	}

	/**
	 * Set the levers property of this class
	 *
	 * Will replace currently set property -- use to refresh.
	 *
	 * @since 0.4.0
	 *
	 * @access private
	 *
	 */
	private function set_levers(){

		if ( ! empty( $this->group[ 'levers' ] ) ) {
			$this->levers = $this->group[ 'levers' ];
		}else{
			//@todo create if ! empty( $this->group[ 'variants' ]  ) ??
		}

	}

	/**
	 * Get all fields of a group object
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected function all_fields() {
		$fields = \ingot\testing\crud\group::get_all_fields();
		$fields[] = 'ID';
		return $fields;

	}

	/**
	 * Get names of group and variants
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	public function names(){
		$data = [
			'variants' => [],
			'group' => $this->group[ 'name' ]
		];

		if( ! empty( $this->group[ 'variants' ] ) ){
			//this loop is dumb -- https://github.com/Ingothq/ingot/issues/101
			foreach( $this->group[ 'variants' ] as $i => $variant_id ) {

				if( is_array( $variant_id ) && isset( $variant_id[ 'content' ] ) ) {
					$name = $variant_id[ 'content' ];
					$variant_id = $variant_id[ 'ID' ];
				}else{
					$variant = variant::read( $variant_id );
					if ( is_array( $variant ) ) {
						$name = $variant[ 'content' ];
					}
				}

				if ( is_numeric( $variant_id ) ) {
					$data[ 'variants' ][ $variant_id ] = $name;
				}

			}

		}

		return $data;
	}

}
