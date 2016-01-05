<?php
/**
 * Handles ingot session
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\testing\object;


class session {

	/**
	 *
	 * @since 0.3.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected $ID;

	/**
	 *
	 * @since 0.3.0
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $session;

	/**
	 *
	 *
	 * @since 0.3.0
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected $ingot_id;


	/**
	 * Contructor
	 *
	 * @since 0.3.0
	 *
	 * @param null|int $id Optional. Session ID or null, the default, to create a new session
	 */
	public function __construct( $id = null ){
		$this->set_up_session ( $id );
	}


	/**
	 * Get session ID and ingot_id
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	public function get_session_info(){
		return [
			'ID' => $this->ID,
			'ingot_ID' => $this->ID,
			'session' => $this->session
		];
	}


	/**
	 * Set class properties
	 *
	 * @since 0.3.0
	 *
	 * @access private
	 *
	 * @param $id
	 */
	private function set_up_session( $id ) {
		if( is_null(  $id ) || ! is_array( \ingot\testing\crud\session::read( $id ) ) ) {
			$this->ID = \ingot\testing\crud\session::create( [
				'uID' => get_current_user_id(),
				'IP' => ingot_get_ip()
			], true );


		}

		$this->session = \ingot\testing\crud\session::read( $this->ID );


		$this->ID = $this->session[ 'ID' ];

		$this->ingot_id = $this->session[ 'ingot_ID' ];

	}


}
