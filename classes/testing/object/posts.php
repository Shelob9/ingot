<?php
/**
 * Handles post association by group ID
 *
 * @package   @ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\object;


use ingot\testing\utility\helpers;

class posts {

	/**
	 * Meta key to store in
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var string
	 */
	private $meta_key;

	/**
	 * Groups associated with this post
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $groups = [];

	/**
	 * Post object for this collection
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Construct object
	 *
	 * @since 1.1.0
	 *
	 * @param |WP_Post|integer $post
	 */
	public function __construct( $post ){
		$this->meta_key = \ingot\testing\utility\posts::meta_key();
		$this->set_post( $post );
		if( $this->post ) {
			$this->set_groups();
		}

	}

	/**
	 * Get IDs of Ingot test groups associated with this post
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_groups(){
		return $this->groups;
	}

	/**
	 * Add group(s) to association
	 *
	 * @since 1.1.0
	 *
	 * @param int|array $ids Group ID or array of IDs to add.
	 * @param bool $overwrite Optional. If true, overwrite saved with new values. Default is false.
	 */
	public function add( $ids, $overwrite = false ){
		if( true == $overwrite && ( is_numeric( $ids ) || is_array( $ids ) ) ){
			$this->groups = [];
		}

		if( is_numeric( $ids ) ) {
			$ids = [$ids];
		}

		if( is_array( $ids ) ) {
			if( empty( $this->groups ) ) {
				$this->groups = helpers::make_array_values_numeric( $ids );
			}else{
				foreach( $ids as $i ){
					if ( is_numeric( $i ) ) {
						$this->groups[] = (int) $i;
					}
				}

				$this->groups = array_unique( $this->groups );
			}
			$this->update();
		}

	}

	/**
	 * Group to add to association
	 *
	 * @since 1.1.0
	 *
	 * @param int $id Group ID to remove.
	 */
	public function remove( $id ){
		if( is_numeric( $id ) ) {
			$key = array_search( $id, $this->groups );
			if ( false !== $key ) {
				unset( $this->groups[ $key ] );
				$this->update();
			}

		}

	}

	/**
	 * Save groups back into post meta
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function update( ) {
		$this->clean_groups_var();

		if( ! empty( $current =  $this->current_meta() ) ) {
			foreach( $current as $value ) {
				delete_post_meta( $this->post->ID, $this->meta_key, $value );
			}
		}

		foreach( $this->groups as $group ){
			add_post_meta( $this->post->ID, $this->meta_key, (int) $group );
		}

	}

	/**
	 * Set groups property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 */
	private function set_groups(){
		$this->groups = $this->current_meta();
	}

	/**
	 * Set posts property
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @param |WP_Post|integer $post
	 */
	private function set_post( $post ){
		if( is_numeric( $post ) ) {
			$this->set_post( get_post( $post ) );
		}

		if( is_a( $post, 'WP_Post' ) ) {
			$this->post = $post;
		}

	}

	protected function clean_groups_var() {
		if ( ! empty( $this->groups ) ) {
			$this->groups = array_unique( $this->groups );
			$this->groups = array_values( $this->groups );
		}

	}

	/**
	 * @return array
	 */
	private function current_meta() {
		return helpers::make_array_values_numeric( get_post_meta( $this->post->ID, $this->meta_key, false ) );
	}


}
