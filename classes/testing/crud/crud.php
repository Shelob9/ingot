<?php
/**
 * Abstract class for test/sequence/test group CRUD
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\crud;


use ingot\testing\types;
use ingot\testing\utility\helpers;

abstract class crud {

	/**
	 * Name of the object this CRUD is for
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected static $what;

	/**
	 * Get name of the object this CRUD is for
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return string
	 */
	protected static function what() {
		return static::$what;
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
	 *  $type string|bool Optional  If false, the default, both price and click groups are returned if is price or click, those types are returned. Only applies to group CRUD
	 * }
	 *
	 * @return array
	 */
	public static function get_items( $params ) {
		$args = wp_parse_args(
			$params,
			array(
				'group_ID' => null,
				'ids' => array(),
				'limit' => -1,
				'page' => 1,
				'return' => '*',
				'type' => false,
			)
		);

		if( -1 == $args[ 'limit' ] ) {
			$args[ 'limit' ] = 999999999;
		}

		if( strtolower( 'ids' ) !== $args[ 'return' ] ) {
			$fields = '*';
		}else{
			$fields = '`ID`';
		}

		if( is_bool( $args[ 'type' ] ) ||  ! in_array( $args[ 'type'], types::allowed_types() ) ) {
			$args[ 'type' ] = false;
		}

		global $wpdb;
		$where = false;
		$table_name = self::get_table_name();
		if( helpers::v( 'group_ID', $args, null ) ){
			$where = true;
			$sql = sprintf(
				'SELECT %s FROM `%s` WHERE `group_ID` = %d',
				$fields,
				$table_name, helpers::v( 'group_ID', $params )
			);
		}elseif( ! empty( helpers::v( 'ids', $args, array() ) ) ){
			$where = true;
			$in = implode( ',', helpers::v( 'ids', $params, array() ) );
			$sql = sprintf( 'SELECT %s FROM `%s` WHERE `ID` IN( %s)', $fields,$table_name, $in );
		}else{
			$sql = sprintf( 'SELECT %s FROM `%s`', $fields, $table_name );
		}

		if( is_string( $args[ 'type' ] ) ){
			if( $where ) {
				$first_part = ' AND';
			}else{
				$first_part = ' WHERE';
			}
			$where = true;
			$sql .= sprintf( ' %s `type` = "%s"', $first_part, $args[ 'type' ] );
		}

		if( helpers::v( 'current', $args, false ) ) {
			$sql .= ' AND `completed` != 1';
		}

		$sql .= sprintf( ' ORDER BY `ID` ASC LIMIT %d OFFSET %d', $args[ 'limit' ], self::calculate_offset( $args[ 'limit' ], $args[ 'page' ] )  );

		return self::bulk_query( $sql );

	}

	/**
	 * Create an item
	 *
	 * @since 0.0.4
	 *
	 * @param array $data Item data
	 * @param bool|false $bypass_cap
	 *
	 * @return ID|bool||WP_Error Item ID,or false if not created, or error if not allowed.
	 */
	public static function create( $data, $bypass_cap = false ){
		unset( $data[ 'ID'] );
		/**
		 * Runs before an object is created
		 *
		 * @since 0.0.6
		 *
		 * @param array $data Data to be saved.
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_create', $data, static::what() );
		$data = apply_filters( 'ingot_crud_create', $data, static::what() );

		$id = static::save( $data, null, $bypass_cap );
		if( is_wp_error( $id ) ) {
			return $id;
		}

		if ( $id ){
			/**
			 * Runs after an object is created
			 *
			 * @since 0.0.6
			 *
			 * @param int $id Item ID
			 * @param string $what Object name
			 */
			do_action( 'ingot_crud_created', $id, static::what() );
		}

		return $id;

	}

	/**
	 * Get one item from table
	 *
	 * @since 0.0.7
	 *
	 * @param int $id
	 *
	 * @return array|mixed|null|object|void
	 */
	public static function read( $id ) {
		/**
		 * Runs before an object is read.
		 *
		 * @since 0.0.6
		 *
		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_read', $id, static::what() );

		global $wpdb;

		$table = static::get_table_name();
		if ( is_numeric( $id )  ) {
			$sql     = $wpdb->prepare( "SELECT * FROM $table WHERE `ID` = %d", $id );
			$results = $wpdb->get_results( $sql, ARRAY_A );
			if ( is_array( $results ) && ! empty( $results ) && isset( $results[ 0 ] ) ) {
				$results = $results[ 0 ];
			} else {
				return false;

			}

			$results = self::unseralize( $results );

			/**
			 * Runs before an object is returned from DB
			 *
			 * @since 0.0.6
			 *
			 * @param array $item Data to be returned
			 * @param string $what Object name
			 */
			$results = apply_filters( 'ingot_crud_read', $results, static::what() );

			return $results;
		}else{
			$type = gettype( $id );
			if( is_wp_error( $id ) ) {
				$errors = $id->get_error_messages();
				if( ! is_string( $errors ) ) {
					$errors = var_export( $errors, true );
				}
				$type .= ' ' . $errors;
			}

			$warning = __( sprintf( 'ID must be numeric for %s, type is %s', static::what(), $type ), 'ingot' );

			return new \WP_Error( 'ingot-crud-id-not-numeric', $warning, var_export( [ 'id' => $id, 'what' => static::what() ], true ) );

		}

	}

	/**
	 * Update an item
	 *
	 * @since 0.0.4
	 *
	 * @param array $data Item config
	 * @param int $id Item ID.
	 * @param bool|false $bypass_cap
	 *
	 * @return int|bool||WP_Error Item ID if created, or false if not updated, or error if not allowed to create.
	 */
	public static function update( $data, $id , $bypass_cap = false ) {

		/**
		 * Runs before an object is updated.
		 *
		 * @since 0.0.6
		 *
		 * @param array $data Data to be saved.
		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_update', $data, $id, static::what() );

		/**
		 * Modify data before saving (read or update)
		 *
		 * @since 0.0.6
		 *
		 * @param array $data Data to be saved.
		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		$data = apply_filters( 'ingot_crud_update', $data, $id, static::what() );

		$id = static::save( $data, $id, $bypass_cap );
		if ( $id ){
			/**
			 * Runs after an object is updated.
			 *
			 * @since 0.0.6
			 *
			 * @param int $id Item ID
			 * @param string $what Object name
			 */
			do_action( 'ingot_crud_updated', $id, static::what() );
		}

		return $id;

	}




	/**
	 * Delete an item or all items
	 *
	 * @since 0.0.7
	 *
	 * @param int|string $id Item id or "all" to delete all
	 *
	 * @return bool
	 */
	public static function delete( $id ) {
		/**
		 * Runs before an object is deleted.
		 *
		 * @since 0.0.6

		 * @param int $id Item ID
		 * @param string $what Object name
		 */
		do_action( 'ingot_crud_pre_delete', $id, static::what() );

		if( 'all' == $id ) {
			return self::delete_all();
		}

		global $wpdb;
		$deleted = $wpdb->delete( self::get_table_name(), array( 'ID' => $id ), array( '%d' ) );
		if( is_numeric( $deleted ) ){
			return true;

		}else{
			return false;

		}

	}

	/**
	 * Delete all rows from table
	 *
	 * @since 0.0.7
	 *
	 * @return bool
	 */
	protected static function delete_all() {
		global $wpdb;
		$table = static::get_table_name();
		$deleted = $wpdb->query( "truncate table $table" );

		if( is_numeric( $deleted ) ){
			return true;

		}else{
			return false;

		}
	}

	/**
	 * Get table name
	 *
	 * @since 0.0.7
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'ingot_' . static::what();
	}


	/**
	 * Generic save for read/update
	 *
	 * @since 0.0.4
	 *
	 * @param array $data Item con
	 * @param int $id Optional. Item ID. Not used or needed if using to create.
	 * @param bool|false $bypass_cap
	 *
	 * @return int|bool||WP_Error Item ID if created, or false if not created, or error if not allowed to create.
	 */
	protected static function save( $data, $id = null, $bypass_cap = false  ) {

		$data = static::prepare_data( $data );
		if( is_wp_error( $data ) || ! is_array( $data ) ) {
			return $data;
		}

		$table_name = static::get_table_name();

		foreach( $data as $key => $datum ) {
			if( is_array( $data[ $key ] ) ) {
				if ( empty( $data[ $key ]) ) {
					$data[ $key ] = serialize( [] );
				}else{
					$data[ $key ] = helpers::sanitize( $data[ $key ] );
					$data[ $key ] = serialize( $datum );
				}
			}

		}

		if( self::can( $id, $bypass_cap ) ) {


			global $wpdb;
			if( $id ) {
				$data[ 'ID' ] = $id;
				$wpdb->update(
					$table_name,
					$data,
					array( 'ID' => (int) $id )
				);
			}else{
				unset( $data[ 'ID' ] );
				$wpdb->insert(
					$table_name,
					$data
				);
				$id =  $wpdb->insert_id;

			}



			return $id;

		}else{
			return false;

		}


	}

	/**
	 * Get total number of items
	 *
	 * @since 0.2.0
	 *
	 * @return int
	 */
	public static function total() {
		global $wpdb;
		$table_name = static::get_table_name();
		$sql = sprintf( 'SELECT COUNT(ID) FROM %s', $table_name );
		$results = $wpdb->get_results( $sql, ARRAY_A );
		if( ! empty( $results ) && isset( $results[0], $results[0][ 'COUNT(ID)'] ) ){
			return $results[0][ 'COUNT(ID)'];
		}

	}



	/**
	 * Get array of non-required, yet needed fields
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function needed(){
		_doing_it_wrong( __CLASS__, __METHOD__, '0.0.4' );
		return [];
	}

	/**
	 * Get array of required fields
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected static function required(){
		_doing_it_wrong( __CLASS__, __METHOD__, '0.0.4' );
		return [];
	}

	/**
	 * Validate item config
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @param array $data Item config
	 *
	 * @return \WP_Error|array Item config array if valid, WP_Error if not.
	 */
	 protected static function validate_config( $data ){
		 _doing_it_wrong( __CLASS__, __METHOD__, '0.0.4' );
		 return $data;
	 }

	/**
	 * Fill in needed, but not required keys
	 *
	 * @since 0.0.4
	 *
	 * @access protected
	 *
	 * @param $data
	 *
	 * @return array
	 */
	 protected static function fill_in( $data ) {
		 foreach( static::needed() as $field ) {
			 if( in_array( $field, [ 'created', 'modified', 'time' ] ) ) {
				 if ( ! isset( $data[ $field ] ) ) {
					 $data[ 'field' ] = current_time( 'mysql' );
				 } else {
					 $data[ 'field' ] = self::date_validation( $data[ $field ] );
				 }
			 }elseif(  'meta' == $field ) {
				if(  ! isset( $data[ $field ] ) ||! is_array( $data[ $field ] ) ) {
					$data[ $field ] = [];
				}
			 }else{
				 $data[ $field ] = '';
			 }
		 }

		 return $data;
	 }

	/**
	 * Process multiple rows from a SQL query
	 *
	 * Should be result of `$wpdb->get_results( $sql, ARRAY_A );`
	 *
	 * @since 1.1.0
	 *
	 * @param array $results
	 *
	 * @return array
	 */
	public static function bulk_results( $results ) {
		if ( ! empty( $results ) ) {
			foreach ( $results as $i => $result ) {
				$results[ $i ] = self::unseralize( $result );
			}

		}

		return $results;
	}


	/**
	 * Validate item types
	 *
	 * @since 0.0.7
	 *
	 * @access protected
	 *
	 * @param array $data Item config
	 * @param string $key Optional. Array key to test in. Default is "types"
	 *
	 * @return bool True if valid, false if not
	 */
	protected static function validate_type( $data, $key = 'type' ) {
		if( ! in_array(  $data[ $key ], types::allowed_types() ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Validate item click type
	 *
	 * @since 0.0.7
	 *
	 * @access protected
	 *
	 * @param array $data Item config
	 *
	 * @return bool True if valid, false if not
	 */
	protected static function validate_click_type( $data ) {
		if( ! in_array( $data[ 'sub_type' ], types::allowed_click_types() ) ) {
			return false;

		}

		return true;

	}


	/**
	 * Ensure a date is a MySQL data
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @param int|string $date
	 *
	 * @return string
	 */
	protected static function date_validation( $date ){
		if( 0 == $date || empty( $date ) ) {
			$date = current_time( 'mysql' );
		}elseif( is_numeric( $date ) ) {
			$date = date("Y-m-d H:i:s", $date );
		}

		$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $date );
		if(  $date != $dt->format('Y-m-d H:i:s') ) {
			$date = current_time( 'mysql' );
		}

		return $date;

	}

	/**
	 * Calculate a offset for a paginated query
	 *
	 * @since 0.0.7
	 *
	 * @param int $limit Total results to show.
	 * @param int $page Page of results to get
	 *
	 * @return int
	 */
	protected static function calculate_offset( $limit, $page ) {
		if ( 1 == $page ) {
			$offset = 0;

			return $offset;

		} else {
			$offset = ( (int) $limit * ( (int) $page - 1 ) ) - 1;

			return absint( $offset );

		}

	}


	/**
	 * Get the required fields
	 *
	 * @since 0.0.6
	 *
	 * @return array
	 */
	public static function get_required_fields() {
		return static::required();

	}

	/**
	 * Get the needed, but not required fields
	 *
	 * @since 0.0.6
	 *
	 * @return array
	 */
	public static function get_needed_fields() {
		return static::needed();

	}

	/**
	 * Get all the fields
	 *
	 * @since 0.0.6
	 *
	 * @return array
	 */
	public static function get_all_fields() {
		return array_merge( static::get_required_fields(), static::get_needed_fields() );

	}

	/**
	 * Prepare data for save/update
	 *
	 * @since 0.0.7
	 *
	 * @param $data
	 *
	 * @return array|\WP_Error Data as array or WP_Error if invalid
	 */
	protected static function prepare_data( $data ) {
		$data = static::fill_in( $data );
		$data = static::validate_config( $data );
		if ( ! is_array( $data ) ) {
			if( is_wp_error( $data ) ) {
				return $data;
			}

			$class = get_called_class();
			$class = explode( "\\", $class );
			$class =  array_pop( $class );

			return new \WP_Error( 'ingot-invalid-config', '', array( $class ) );

		}

		$allowed = static::get_all_fields();

		foreach ( $data as $k => $v ) {
			if ( is_numeric( $k ) || ! in_array( $k, $allowed ) ) {
				unset( $data[ $k ] );
			}
		}


		if( isset( $data[ 'modified' ] ) ){
			$data[ 'modified' ] = current_time( 'mysql' );
		}

		return $data;

	}

	/**
	 * Check if current user can (if cap check is needed
	 *
	 * @since 0.0.7
	 *
	 * @access protected
	 *
	 * @param int $id Item ID
	 * @param bool $bypass_cap Whether to bypass capabilities check.
	 *
	 * @return bool True if user can, false if not
	 */
	protected static function can( $id, $bypass_cap ) {
		if ( ! $bypass_cap ) {
			/**
			 * Sets the capability required to preform update
			 *
			 * @since 0.0.5
			 *
			 * @param string $cap The capability
			 */
			$cap = apply_filters( 'ingot_save_cap', 'manage_options', static::what(), $id );
			$can = current_user_can( $cap );

		} else {
			$can = true;


		}

		/**
		 * Filter if user can
		 *
		 * @since 0.0.7
		 */
		return apply_filters( 'ingot_user_can', $can, $id, static::what() );
	}

	/**
	 * @param $results
	 *
	 * @return mixed
	 */
	private static function unseralize( $results ) {
		foreach ( [ 'variants', 'meta', 'UTM', 'levers' ] as $field ) {
			if ( isset( $results[ $field  ] ) ) {
				$results[ $field ] = maybe_unserialize( $results[ $field ] );
			}

			if ( 'levers' == $field && isset( $results[ 'levers' ] ) ) {
				$results = self::maybe_rekey_levers( $results, $field );
			}

		}

		return $results;

	}

	/**
	 * If needed re-key levers by lever ID
	 *
	 * @since 0.4.0
	 *
	 * @access protected
	 *
	 * @param array $results
	 *
	 * @return array
	 */
	protected static function maybe_rekey_levers( $results ) {

		if ( is_array( $results[ 'levers' ] ) && ! empty( $results[ 'levers' ] ) && isset( $results[ 'levers' ][ $results[ 'ID' ] ][ 0 ] ) ) {
			$_levers = $results[ 'levers' ][ $results[ 'ID' ] ];
			$levers  = [ ];
			foreach ( $_levers as $lever ) {
				$levers[ $results[ 'ID' ] ][ $lever->getValue() ] = $lever;
			}

			$results[ 'levers' ] = $levers;

			return $results;


		}

		return $results;

	}

	/**
	 * Do bulk query
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param $sql
	 *
	 * @return mixed
	 */
	protected static function bulk_query( $sql ) {
		global $wpdb;
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::bulk_results( $results);

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
		if( ! is_array( $data ) ){
			return false;
		}

		foreach( static::get_all_fields() as $field ) {
			if( ! array_key_exists( $field, $data ) ) {
				return false;

			}

		}

		return true;
	}


}
