<?php
/**
 * @TODO What this does.
 *
 * @package   @TODO
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\utility;


class posts {

	/**
	 * Search a string for ingot shortcodes and return ids used
	 *
	 * @since 1.1.0
	 *
	 * @param string $content String to search
	 *
	 * @return array Array of group IDs
	 */
	public static function find_ids( $content ) {
		$ids  = [];
		$tag= 'ingot';
		if ( ! has_shortcode( $content, $tag ) || false === strpos( $content, '[' ) ) {
			return $ids;
		}


		if ( shortcode_exists( $tag ) ) {
			preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );
			if ( empty( $matches ) ) {
				return $ids;

			}


			foreach ( $matches as $shortcode ) {
				if ( $tag === $shortcode[2] && isset( $shortcode[3]) ) {
					$_id = self::find_id( $shortcode );

					if ( is_numeric( $_id ) ) {
						$ids[] = $_id;
					}
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], $tag ) ) {
					$_id = self::find_id( $shortcode );
					if ( is_numeric( $_id ) ) {
						$ids[] = $_id;
					}
				}
			}
		}

		return $ids;

	}

	/**
	 * Update post associated with group by searching post content for them
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post
	 */
	public static function update_groups_in_post( $post ){
		if ( is_a( $post, 'WP_Post') ) {

			$obj      = new \ingot\testing\object\posts( $post );
			$current  = self::find_ids( $post->post_content );
			if( empty( $current ) ) {
				$obj->add( [], true );
			}else{
				$existing = $obj->get_groups();
				if( empty( $existing ) || ! empty( array_diff( $existing, $current ) ) ){

					$obj->add( $current, true );
				}

			}

		}

	}

	/**
	 * @param $group_ids
	 *
	 * @return \WP_Query
	 */
	public static function posts_by_group( $group_ids ){
		if( is_numeric( $group_ids ) ){
			$group_ids = [ $group_ids ];
		}

		$args = [
			'post_type' => 'any',
			'posts_per_page' => 50,
			'meta_query' => array(
				array(
					'key'     => self::meta_key(),
					'value'   => $group_ids,
					'compare' => 'IN',
					'type' => 'NUMERIC'
				),
			),
		];

		/**
		 * Filter args for WP_Query used to query by group IDs
		 *
		 * @since 1.1.0
		 *
		 * @param array $args WP_Query args
		 */
		$args = apply_filters( 'ingot_query_by_groups_args', $args );

		return new \WP_Query( $args );

	}

	/**
	 * Find group ID in shortcode
	 *
	 * @since 1.1.0
	 *
	 * @param array $shortcode Result of shortcode search to try and find ID in
	 *
	 * @return int
	 */
	protected static function find_id( $shortcode ) {
		$parsed =  shortcode_parse_atts( $shortcode[ 3 ] );
		if( is_array( $parsed ) && isset( $parsed['id'] ) &&  is_numeric( $parsed[ 'id' ] ) ){
			return  (int) $parsed[ 'id' ];
		}

	}

	/**
	 * Get name of meta key used to store group/post association
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public static function meta_key(){
		return 'ingot_groups';
	}

}
