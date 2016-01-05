<?php
/**
 * Special queries for price test groups
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\crud;


use ingot\testing\types;

class price_query {

	/**
	 * Find all price tests by product ID
	 *
	 * @since 1.1.0
	 *
	 * @param int $id Product ID
	 *
	 * @return array
	 */
	public static function find_by_product( $id ){
		$group_table_name = group::get_table_name();
		$variant_table_name = variant::get_table_name();
		global $wpdb;
		$sql = sprintf( 'select A.* from %s A inner join %s B on A.id = B.group_ID where B.content = %d', $group_table_name, $variant_table_name, $id  );
		return self::query( $wpdb, $sql );

	}

	/**
	 * Find all price tests by plugin type
	 *
	 * @since 1.1.0
	 *
	 * @param string $plugin Plugin -- must be allowed by ingot\testing\types::allowed_price_types()
	 *
	 * @return array
	 */
	public static function find_by_plugin( $plugin, $skip_no_variants = false ){
		if( in_array( $plugin, types::allowed_price_types() ) ) {
			$table_name = group::get_table_name();
			global $wpdb;
			$sql = sprintf( 'SELECT * FROM `%s` WHERE `sub_type` = "%s" AND `type` = "price"', $table_name, $plugin  );
			if( $skip_no_variants ) {
				$empty = serialize( [] );
				$sql .= sprintf(' AND `variants` != "%s"', $empty );
			}

			return self::query( $wpdb, $sql );

		}

	}

	/**
	 * Do query
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param $wpdb
	 * @param $sql
	 *
	 * @return array
	 */
	protected static  function query( $wpdb, $sql ) {
		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( is_array( $results ) ) {
			return group::bulk_results( $results );
		}

		return [];
	}

}
