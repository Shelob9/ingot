<?php
/**
 * Load up the Ingot
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */
class ingot_bootstrap {

	/**
	 * Loads ingot if not already loaded.
	 *
	 * @since 0.0.5
	 */
	public static function maybe_load() {
		if ( did_action( 'ingot_loaded' ) ) {
			return;
		}

		if ( ! defined( 'INGOT_DEV_MODE' ) ){
			/**
			 * Puts Ingot into dev mode
			 *
			 * Don't use on a live site -- makes API totally open
			 *
			 * @since 0.0.5
			 */
			define( 'INGOT_DEV_MODE', false );
		}

		$load = true;
		if ( ! version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {
			$load = false;
		}

		$autoloader = dirname( __FILE__ ) . '/vendor/autoload.php';
		if ( ! file_exists( $autoloader ) ) {
			$load = false;
		}

		if ( $load ) {
			add_filter( 'ingot_force_update_table', '__return_true' );
			if (  ! did_action( 'ingot_loaded') ) {
				include_once( $autoloader );
				self::maybe_upgrade();
				self::add_tables();

				if( self::check_if_tables_exist() ) {
					ingot\testing\ingot::instance();

					//make admin go in admin
					if ( is_admin() ) {
						new \ingot\ui\admin\app\load();
					}

					self::init_cookies();

					/**
					 * Runs when Ingot has loaded.
					 *
					 * @since 0.0.5
					 *
					 */
					do_action( 'ingot_loaded' );
				}else{
					if ( is_admin() ) {
						printf( '<div class="error"><p>%s</p></div>', __( 'Ingot Not Loaded', 'ingot' ) );

					}

					/**
					 * Runs if Ingot failed to load
					 *
					 * @since 0.3.0
					 *
					 */
					do_action( 'ingot_loaded_failed' );

					return;

				}

			}

		}


	}

	/**
	* If needed, add the custom table for groups
	*
	* @since 0.0.7
	*
	* @param bool $drop_first Optional. If true, DROP TABLE statement is made first. Default is false
	*/
	public static function maybe_add_group_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\group::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				ID bigint(9) NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				type VARCHAR(255) NOT NULL,
				sub_type VARCHAR(255) NOT NULL,
				variants LONGTEXT NOT NULL,
				levers LONGTEXT NOT NULL,
				meta LONGTEXT NOT NULL,
				modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * If needed, add the custom table for variants
	 *
	 * @since 0.0.7
	 *
	 * @param bool $drop_first Optional. If true, DROP TABLE statement is made first. Default is false
	 */
	public static function maybe_add_variant_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\variant::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				ID bigint(9) NOT NULL AUTO_INCREMENT,
				type VARCHAR(255) NOT NULL,
				group_ID mediumint(9) NOT NULL,
				content LONGTEXT NOT NULL,
				meta LONGTEXT NOT NULL,
				modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * If needed, add the custom table for tracking
	 *
	 * @since 0.0.7
	 *
	 * @param bool $drop_first Optional. If true, DROP TABLE statemnt is made first. Default is false
	 */
	public static function maybe_add_tracking_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\tracking::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  ID bigint(9) NOT NULL AUTO_INCREMENT,
		  group_ID mediumint(9) NOT NULL,
		  ingot_ID mediumint(9) NOT NULL,
		  test_ID mediumint(9) NOT NULL,
		  IP VARCHAR(255) NOT NULL,
		  UTM LONGTEXT NOT NULL,
		  referrer VARCHAR(255) NOT NULL,
		  browser VARCHAR(255) NOT NULL,
		  meta LONGTEXT NOT NULL,
		  user_agent LONGTEXT NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}


	/**
	 * If needed, add the custom table for session
	 *
	 * @since 0.3.0
	 *
	 * @param bool $drop_first Optional. If true, DROP TABLE statement is made first. Default is false
	 */
	public static function maybe_add_session_table( $drop_first = false ) {
		global $wpdb;

		$table_name = \ingot\testing\crud\session::get_table_name();

		if( $drop_first  ) {
			if( self::table_exists( $table_name )  ) {
				$wpdb->query( "DROP TABLE $table_name" );
			}

		}

		if(  self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  ID BIGINT NOT NULL AUTO_INCREMENT,
		  used tinyint NOT NULL,
		  IP VARCHAR(255) NOT NULL,
		  uID BIGINT NOT NULL,
		  ingot_ID BIGINT NOT NULL,
		  slug VARCHAR(255) NOT NULL,
		  click_url VARCHAR(255) NOT NULL,
		  click_test_ID BIGINT NOT NULL,
		  created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY ID (ID)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	/**
	 * Check if table exists
	 *
	 * @since 0.0.7
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 */
	protected static function table_exists( $table_name ) {
		global $wpdb;
		if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name  ) {
			return true;

		}else{
			return false;

		}

	}

	/**
	 * Setup our cookies
	 *
	 * @uses "init"
	 *
	 * @since 0.0.9
	 */
	public static function init_cookies() {

		if( false == ingot_is_front_end() ) {
			return false;
		}

		$cookies = array();
		if( isset( $_COOKIE ) && is_array( $_COOKIE ) ) {
			$cookies = $_COOKIE;
		}

		$cookies = ingot\testing\cookies\init::create( $cookies );
		$ingot_cookies = ingot\testing\cookies\init::get_instance()->get_ingot_cookie();
		if( ! empty( $ingot_cookies ) ){
			$cookie_time = 15 * DAY_IN_SECONDS;

			/**
			 * Change cookie time
			 *
			 * @since 0.2.0
			 *
			 * @param int $cookie_time Length to keep cookie. Default is 15 days
			 */
			$cookie_time = apply_filters( 'ingot_cookie_time', $cookie_time );
			$cookie_name = $cookies->get_cookie_name();
			//setcookie( $cookie_name, $ingot_cookies, time() + $cookie_time, COOKIEPATH, COOKIE_DOMAIN, false );
			
		}

		/**
		 * Fires after Ingot Cookies Are Set
		 *
		 * Note: will fire if they were set empty
		 * Should happen at init:25
		 *
		 * @since 0.0.9
		 *
		 * @param \ingot\testing\cookies\init $cookies Cookies object
		 */
		do_action( 'ingot_cookies_set', $cookies );
		if ( ingot_is_edd_active() && isset( $ingot_cookies[ 'price' ][ 'edd' ] ) && ! empty( $ingot_cookies[ 'price' ][ 'edd' ] )  ) {
			new \ingot\testing\tests\price\plugins\edd( $ingot_cookies[ 'price' ][ 'edd' ] );

		}

	}





	/**
	 * Add tables if needed
	 *
	 * @since 0.3.0
	 *
	 * @access protected
	 */
	protected static function add_tables() {
		self::maybe_add_tracking_table();
		self::maybe_add_session_table();
		self::maybe_add_group_table();
		self::maybe_add_variant_table();
		self::check_if_tables_exist();
	}

	/**
	 * Check if all tables exists
	 *
	 * @since 0.3.0
	 *
	 * @access protected
	 *
	 * @return bool
	 */
	protected static function check_if_tables_exist() {
		if ( ! self::table_exists( \ingot\testing\crud\tracking::get_table_name() )
		     || ! self::table_exists( \ingot\testing\crud\group::get_table_name() )
		     || ! self::table_exists( \ingot\testing\crud\session::get_table_name() )
		     || ! self::table_exists( \ingot\testing\crud\variant::get_table_name() )
		) {
			return false;


		}

		return true;
	}

	/**
	 * Maybe run DB updater
	 *
	 * @access protected
	 *
	 * @since 1.0.1
	 */
	protected static function maybe_upgrade(){
		if( INGOT_VER != get_option( 'ingot_version', 0 ) ) {
			self::dump_prebeta();
			update_option( 'ingot_version', INGOT_VER, false );
		}


	}

	/**
	 * Dump pre-beta tables
	 *
	 * @access protected
	 *
	 * @since 1.0.1
	 */
	protected static function dump_prebeta(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'ingot_sequence';
		if( self::table_exists( $table_name ) ) {
			ingot_destroy();
		}

	}


}
