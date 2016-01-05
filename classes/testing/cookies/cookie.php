<?php
/**
 * Base class for making cookies
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\cookies;


abstract class cookie {
	/**
	 * The cookie
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $cookie;


	/**
	 * Construct object
	 *
	 * @since 1.1.0
	 *
	 * @param array $cookie Current contents of this part of cookie
	 * @param bool $reset Optional. Whether to rest or not, default is false
	 */
	public function __construct(  $cookie, $reset = true ){
		$this->set_cookie( $cookie, $reset );

	}

	/**
	 * Get cookie
	 *
	 * @since 1.1.0
	 *
	 * @return array Price cookie portion of our cookie
	 */
	public function get_cookie() {
		return $this->cookie;
	}

	/**
	 * Set cookie property for class
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param array $cookie Current contents of this part of cookie
	 * @param bool $reset Optional. Whether to rest or not, default is false
	 */
	abstract protected function set_cookie( $cookie, $reset );


	/**
	 * Get expiration time for tests
	 *
	 * @todo filter/option/etc?
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @return int
	 */
	protected function expires() {
		return time() + ( 10 * DAY_IN_SECONDS );

	}

	/**
	 * Check for expiration
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 *
	 * @param $expiration
	 *
	 * @return bool
	 */
	protected function expired( $expiration ) {
		if( $expiration > $this->expires() ){
			return true;

		}
		
	}

}
