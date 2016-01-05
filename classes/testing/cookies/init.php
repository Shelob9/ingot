<?php
/**
 * Setup ingot cookies
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\cookies;


use ingot\testing\crud\price_test;
use ingot\testing\tests\flow;
use ingot\testing\utility\price;

class init {

	/**
	 * Index we use in cookie super global
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $cookie_name = 'ingot_cookie';


	/**
	 * Index of our cookie array
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $cookie_parts = array(
		'price',
		'click',
		'user'
	);

	/**
	 * The ingot cookie
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $cookie = array();

	/**
	 * @var \ingot\testing\cookies\init
	 */
	private static $instance;

	/**
	 * Set and check our cookies
	 *
	 * @since 0.0.9
	 *
	 * @uses "init"
	 *
	 * @access private
	 *
	 * @param array $cookies cookies super var
	 * @param bool $rebuild If true, trigger a rebuild.
	 */
	private function __construct( $cookies, $rebuild = true ) {
		if( $rebuild ) {
			$this->rebuild( $cookies );
		}

	}

	/**
	 * Get instance of class
	 *
	 * IMPORTANT: Can not be used to create instance. Must use create()
	 *
	 * @since 1.1.0
	 *
	 * @return \ingot\testing\cookies\init|\WP_Error
	 */
	public static function get_instance() {
		if( ! is_null( self::$instance ) ){
			return self::$instance;
		}else{
			return new \WP_Error( 'ingot-cookies-init-singleton-misuse', __( 'Ingot cookies object doe not exist. Use the create() method to create it.', 'ingot' ) );
		}
	}

	/**
	 * Create new class instance
	 *
	 * IMPORTANT: Can not be used to create instance. Must use create()
	 *
	 * @param array $cookies cookies super var
	 * @param bool $rebuild Optional. Trigger a rebuild.
	 *
	 * @return \ingot\testing\cookies\init|\WP_Error
	 */
	public static function create( $cookies, $rebuild = true ) {
		if( is_null( self::$instance ) ){
			self::$instance = new self( $cookies, $rebuild );
			return self::$instance;
		}else{
			return new \WP_Error( 'ingot-cookies-init-singleton-misuse', __( 'Ingot cookies object already exists. Use the get_instance() method to access it.', 'ingot' ) );
		}

	}

	/**
	 * Rebuild the cookies
	 *
	 * @since 0.2.0
	 *
	 * @param array $cookies cookies super var
	 */
	public function rebuild( $cookies ) {
		$this->set_cookie( $cookies );
		$this->get_ingot_cookie();
		$this->setup_cookies();
	}

	/**
	 * Get what we should set in the cookie
	 *
	 * @since 0.0.9
	 *
	 * @param bool $encode Optional. If true, the default, data is returned as a JSON encoded string. If false, as array.
	 *
	 * @return false|string
	 */
	public function get_ingot_cookie( $encode = true ) {
		if ( $encode ) {
			return wp_json_encode( $this->cookie );
		} else {
			return  $this->cookie;
		}
	}

	/**
	 * Get name of index we use in cookie super global
	 *
	 * @since 0.0.9
	 *
	 * @return string
	 */
	public function get_cookie_name(){
		return $this->cookie_name;
	}



	/**
	 * Set the cookie property of this class
	 *
	 * @since 0.0.9
	 *
	 * @access private
	 *
	 * @param array $cookies Array to pull from. Should be cookies super global.
	 */
	protected function set_cookie( $cookies ){
		if( isset( $cookies[ $this->cookie_name ] ) ) {
			$this->cookie = json_decode( $cookies[ $this->cookie_name ] );
		}else{
			$this->cookie = array();
		}

		foreach( $this->cookie_parts as $part ) {
			if( ! isset( $this->cookie[ $part ] ) ){
				$this->cookie[ $part ] = array();
			}

		}

	}

	/**
	 * Setup our cookies
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 *
	 */
	protected function setup_cookies() {
		$this->setup_price_cookie();
		//$this->setup_click_cookie();
		$this->setup_user_cookie();
	}

	/**
	 * Setup our price cookies
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	protected function setup_price_cookie() {

		$price = new \ingot\testing\cookies\price( $this->cookie[ 'price' ] );
		$this->cookie[ 'price' ] = $price->get_cookie();
	}


	/**
	 * Setup our click cookies
	 *
	 * @todo this
	 *
	 * @since 0.0.9
	 *
	 * @access protected
	 */
	protected function setup_click_cookie() {
		//do this later
	}

	/**
	 * Setup our user cookies
	 *
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function setup_user_cookie(){
		$user = new user( $this->cookie[ 'user' ] );
		$this->cookie[ 'user' ] = $user->get_cookie();
	}

}
