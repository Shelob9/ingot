<?php
/**
 * Main class to make Ingot go.
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing;


use ingot\testing\api\rest\groups;
use ingot\testing\api\rest\products;
use ingot\testing\api\rest\util;
use ingot\testing\api\rest\variant;
use ingot\testing\cookies\init;
use ingot\testing\crud\settings;
use ingot\testing\utility\helpers;
use ingot\testing\utility\posts;

class ingot {

	/**
	 * Class instance
	 *
	 * @since 0.2.0
	 *
	 * @var ingot
	 */
	private static $instance;

	/**
	 * Current session data
	 *
	 * @since 1.1.0
	 *
	 * @access private
	 *
	 * @var array
	 */
	private $current_session_data;

	/**
	 * Get class instance
	 *
	 * @since 0.2.0
	 *
	 * @return ingot
	 */
	public static function instance() {
		if( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for class
	 *
	 * @access protected
	 *
	 * @since 0.0.5
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks
	 *
	 * @since 0.0.5
	 */
	public function hooks(){
		add_action( 'rest_api_init', array( __CLASS__ , 'boot_rest_api' ) );
		add_action( 'pre_update_option', array( $this, 'presave_settings' ), 10, 2  );

		add_action( 'save_post', array( $this, 'track_groups' ), 15, 2 );

		if ( ! ingot_is_bot() ) {
			add_action( 'wp_enqueue_scripts', function () {
				$version = INGOT_VER;
				$min = '.min';
				if ( SCRIPT_DEBUG ) {
					$min = '';
					$version = rand();
				}
				wp_enqueue_script( 'ingot', INGOT_URL . "/assets/front-end/js/ingot-click-test{$min}.js", array( 'jquery' ), $version, true );
				wp_localize_script( 'ingot', 'INGOT_UI', ingot::js_vars() );

			} );


			add_action( 'parse_request', array( $this, 'init_session' ), 50 );

		}




	}

	/**
	 * Load the REST API
	 *
	 * @uses "rest_api_init"
	 */
	public static function boot_rest_api() {
		if ( ! did_action( 'ingot_rest_api_booted' ) ) {
			$group = new groups();
			$group->register_routes();
			$variant = new variant();
			$variant->register_routes();
			$settings = new \ingot\testing\api\rest\settings();
			$settings->register_routes();
			$products = new products();
			$products->register_routes();
			$session = new \ingot\testing\api\rest\session();
			$session->register_routes();

			/**
			 * Runs after the Ingot REST API is booted up
			 *
			 * @since 0.2.0
			 *
			 * @param string $namespace Namespace of API
			 */
			do_action( 'ingot_rest_api_booted', util::get_namespace() );
		}

	}

	/**
	 * Sanatize Ingot settings
	 *
	 * @since 0.0.8
	 *
	 * @uses "pre_update_option"
	 */
	public function presave_settings( $value, $option ){
		$keys = settings::get_key_names();
		if( in_array( $option, $keys ) ) {
			$value = settings::sanatize_setting( $option, $value );
		}

		return $value;

	}

	/**
	 * Data to be localize as INGOT_UI
	 *
	 * @return array
	 */
	static public function js_vars() {
		$session = self::$instance->current_session_data;
		unset( $session[ 'session' ] );
		$vars = array(
			'api_url' => esc_url_raw( util::get_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'session_nonce' => wp_create_nonce( 'ingot_session' ),
			'session' => $session
		);

		return $vars;
	}

	/**
	 * Inititialize Ingot session
	 *
	 * @uses "parse_request"
	 *
	 * @since 0.3.0
	 */
	public function init_session(){
		$id = null;
		if( isset( $_GET[ 'ingot_session_ID' ] ) && ingot_verify_session_nonce( helpers::v( 'ingot_session_nonce', $_GET, '' ) ) ) {
			$id = helpers::v( 'ingot_session_ID', $_GET, null );
		}

		$session = new \ingot\testing\object\session( $id );
		$session_data = $session->get_session_info();

		/**
		 * Fired when Ingot session is setup at parse_request
		 *
		 * @since 0.3.0
		 *
		 * @param array $session_data has ID (session ID) and ingot_ID
		 */
		do_action( 'ingot_session_initialized', $session_data );


		$this->current_session_data = $session_data;
	}

	/**
	 * Update the post/group association when saving posts
	 *
	 * @since 1.1.0
	 *
	 * @param int $id
	 * @param \WP_Post $post
	 */
	public function track_groups( $id, $post ){
		posts::update_groups_in_post( $post );
	}

	/**
	 * Get current session data
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_current_session(){
		return $this->current_session_data;
	}

}






