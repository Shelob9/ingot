<?php


/**
 * Get complete click type HTML
 *
 * @since 0.0.6
 *
 * @param int $id Group ID
 *
 * @return string
 */
function ingot_click_test( $id ) {
	$html = '';
	if ( ! is_array( $id ) ) {
		$group = \ingot\testing\crud\group::read( $id );
	}else{
		$group = $id;
	}

	if( ! is_array( $group ) || 'click' !== $group[ 'type'] ){
		return $html;
	}

	$type = $group[ 'sub_type' ];
	if ( in_array( $type, \ingot\testing\types::allowed_click_types() ) ) {
		switch ( $type ) {
			case in_array( $type, \ingot\testing\types::allowed_click_types() ) :
				$html = ingot_click_html_link( $type, $group );
				break;
			case is_callable( $type ) :
				$html = call_user_func( $type, $group );
				break;
			default :
				$html = '';

		}
	}

	return $html;

}

add_shortcode( 'ingot', 'ingot_shortcode' );
/**
 * Callback for the ingot shortcode
 *
 * @since 0.0.4
 *
 * @param $atts
 *
 * @return string
 */
function ingot_shortcode( $atts ) {
	$html = '';
	$atts = shortcode_atts( array(
		'id' => 0,
	), $atts, 'ingot' );
	if ( 0 != $atts[ 'id' ] ) {
		$html = ingot_click_test( $atts[ 'id' ] );

	}

	return $html;
}

/**
 * Get HTML for the link of a click test
 *
 * @since 0.0.6
 *
 * @param string $type Test type
 * @param int|array $group Group ID or config array
 *
 * @return string
 */
function ingot_click_html_link( $type, $group ) {
	switch ( $type ) {
		case 'link' == $type :
			$class = new ingot\ui\render\click_tests\link( $group );
			break;
		case 'button' == $type :
			$class = new \ingot\ui\render\click_tests\button( $group );
			break;
		case 'button_color' == $type :
			$class = new \ingot\ui\render\click_tests\button_color( $group );
			break;
		case 'text' == $type :
			$class = new \ingot\ui\render\click_tests\text( $group );
			break;
		default :
			return '';
		break;
	}

	$html = $class->get_html();

	return $html;

}

/**
 * Get User IP
 *
 * Returns the IP address of the current visitor
 *
 * @since 0.0.7
 * @return string $ip User's IP address
 */
function ingot_get_ip() {

	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return apply_filters( 'ingot_get_ip', $ip );

}

/**
 * Get user agent safely
 *
 * @since 0.0.7
 *
 * @return string
 */
function ingot_get_user_agent() {
	if( isset( $_SERVER ) && isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ){
		return wp_kses( $_SERVER[ 'HTTP_USER_AGENT' ], array() );

	}else{
		return '';

	}

}

/**
 * Get browser details
 *
 * @since 0.0.7
 *
 * @param null|string $user_agent User agent details. Optional. If null, the default ingot_get_user_agent() is used.
 *
 * @return string|bool Broswer name or false if not able to detect
 */
function ingot_get_browser( $user_agent = null ) {
	if( is_null( $user_agent ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ){
		$user_agent = ingot_get_user_agent();
	}else{
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
	}

	if ( is_string( $user_agent ) ) {
		if ( strpos( $user_agent, 'Lynx' ) !== false ) {
			return 'lynx';
		} elseif ( stripos( $user_agent, 'chrome' ) !== false ) {
			return 'chrome';
		} elseif ( stripos( $user_agent, 'safari' ) !== false ) {
			return 'safari';
		} elseif ( ( strpos( $user_agent, 'MSIE' ) !== false || strpos( $user_agent, 'Trident' ) !== false ) && strpos( $user_agent, 'Win' ) !== false ) {
			return 'ie';
		} elseif ( strpos( $user_agent, 'MSIE' ) !== false && strpos( $user_agent, 'Mac' ) !== false ) {
			return 'ie';
		} elseif ( strpos( $user_agent, 'Gecko' ) !== false ) {
			return 'gecko';
		} elseif ( strpos( $user_agent, 'Opera' ) !== false ) {
			return 'opera';
		} elseif ( strpos( $user_agent, 'Nav' ) !== false && strpos( $user_agent, 'Mozilla/4.' ) !== false ) {
			return 'ns4';
		}else{
			return '';
		}

	}

	return '';

}


/**
 * Get reffering source, if tracking from that source
 *
 * @since 0.0.7
 *
 * @return bool
 */
function ingot_get_refferer() {
	$utm = ingot_get_utm();
	if ( ! empty( $utm ) ) {
		$_utm = array_keys( array_flip( $utm ) );
		foreach ( ingot_get_referrers_to_track() as $allowed ) {
			if( in_array( $allowed, $_utm ) ) {
				return $allowed;
			}

		}
	}

	$refferer = false;
	if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		$refferer = wp_unslash( $_REQUEST['_wp_http_referer'] );
	}
	elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$refferer = wp_unslash( $_SERVER['HTTP_REFERER'] );
	}else{
		return false;

	}


	$_parse = parse_url( $refferer );
	if( is_array( $_parse )  && isset( $_parse[ 'host' ] ) ) {
		foreach( ingot_get_referrers_to_track() as $ref ) {
			if( false !== strpos( $_parse[ 'host' ], $ref ) ) {
				return $ref;

			}

		}

	}

	return false;

}

/**
 * Get UTM tags into an array safely.
 *
 * @since 0.0.7
 *
 * @return array
 */
function ingot_get_utm() {
	$utm = array();
	if( ! empty( $_GET ) ) {
		foreach( $_GET as $var => $value ) {
			if( false !== strpos( $var, 'UTM' ) || false !== strpos( $var, 'utm' ) ) {
				$key = sanitize_key( str_replace( 'utm_', '', $var ) );
				$utm[ $key ] = strip_tags( $value );
			}

		}

	}

	return $utm;

}

/**
 * Get networks to track referrals from/
 *
 * @since 0.0.7
 *
 * @return array
 */
function ingot_get_referrers_to_track() {
	$referrers = array(
		'twitter',
		'facebook',
		'google',
	);

	/**
	 * Add or subtract referrers to track from
	 *
	 * @since 0.0.7
	 *
	 * @param array $referrers
	 */
	$referrers = apply_filters( 'ingot_referrers_to_track', $referrers );

	return $referrers;


}

/**
 * Check that admin ajax is happening
 *
 * @since 0.0.8
 *
 * @return bool
 */
function ingot_is_admin_ajax() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return true;

	}

}

function ingot_validate_boolean( $val ) {
	if( 'on' == $val ) {
		return true;
	}

	if( filter_var( $val, FILTER_VALIDATE_BOOLEAN ) ) {
		return true;
	}
}

/**
 * Get the full URL of the current page
 *
 * @since 0.0.9
 *
 * @return string Full URL of the current page

 */
function ingot_current_url () {
	$url = 'http';

	if ( isset( $_SERVER[ 'HTTPS' ] ) && 'off' != $_SERVER[ 'HTTPS' ] && 0 != $_SERVER[ 'HTTPS' ] )
		$url = 'https';

	$url .= '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];

	return $url;

}

/**
 * Sanitize Amount
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * Copied form ingot_sanitize_amount()
 *
 * @since 0.0.9
 *
 * @param string $amount Price amount to format
 * @return string $amount Newly sanitized amount
 */
function ingot_sanitize_amount( $amount ) {
	$is_negative   = false;

	/**
	 * Change thousands separator to use for price display
	 *
	 * @since 0.0.9
	 *
	 * @param string $thousands_separator
	 */
	$thousands_sep = apply_filters( 'thousands_separator', ',' );

	/**
	 * Chane decimal separator to use for price display
	 *
	 * @since 0.0.9
	 *
	 * @param string $decimal_separator
	 */
	$decimal_sep   = apply_filters( 'decimal_separator', '.' );

	// Sanitize the amount
	if ( $decimal_sep == ',' && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
		if ( ( $thousands_sep == '.' || $thousands_sep == ' ' ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		} elseif( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
			$amount = str_replace( '.', '', $amount );
		}

		$amount = str_replace( $decimal_sep, '.', $amount );
	} elseif( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( $thousands_sep, '', $amount );
	}

	if( $amount < 0 ) {
		$is_negative = true;
	}

	$amount   = preg_replace( '/[^0-9\.]/', '', $amount );

	/**
	 * Filter number of decimals to use for prices
	 *
	 * @since 0.0.9
	 *
	 * @param int $number Number of decimals
	 * @param int|string $amount Price
	 */
	$decimals = apply_filters( 'ingot_sanitize_amount_decimals', 2, $amount );
	$amount   = number_format( (double) $amount, $decimals, '.', '' );

	if( $is_negative ) {
		$amount *= -1;
	}

	/**
	 * Filter the sanitized price before returning
	 *
	 * @since 0.0.9
	 *
	 * @param string $amount Price
	 */
	return apply_filters( 'ingot_sanitize_amount', $amount );
}

/**
 * Get the allowed plugins to make price tests with
 *
 * @since 0.0.9
 *
 * @param bool $with_labels Optional. If true labels as values. Default is false
 *
 * @return array
 */
function ingot_accepted_plugins_for_price_tests( $with_labels = false ) {
	$plugins = array(
		'edd' => __( 'Easy Digital Downloads', 'ingot' ),
		'woo' => __( 'WooCommerce', 'ingot' )
	);



	/**
	 * Add or remove allowed plugins for price tests
	 *
	 * @since 0.0.9
	 *
	 * @param array $plugins Array of plugins
	 */
	$plugins = apply_filters( 'ingot_accepted_plugins_for_price_tests', $plugins );

	if( false == $with_labels ) {
		return array_keys( $plugins );
	}else{
		return $plugins;
	}
}

/**
 * Check if a plugin is acceptable for use in a price test
 *
 * @since 0.0.9
 *
 * @param $plugin
 *
 * @return bool
 */
function ingot_acceptable_plugin_for_price_test( $plugin ){
	return in_array( $plugin, ingot_accepted_plugins_for_price_tests() );
}

/**
 * Check if this is a front-end request
 *
 * @since 0.0.9
 *
 * @return bool
 */
function ingot_is_front_end() {
	if( is_admin() || ingot_is_admin_ajax() || ingot_is_rest_api() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
		return false;
	}

	return true;
}

/**
 * Check if this is a REST API request
 *
 * @since 0.0.9
 *
 * @return bool
 */
function ingot_is_rest_api() {

	if ( isset( $GLOBALS[ 'wp' ] ) && ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		return true;
	}

	if( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}

}

/**
 * Check if price tests are allowed
 *
 * @since 0.0.9
 *
 * @return bool
 */
function ingot_enable_price_testing() {
	$enable = false;
	foreach( ingot_accepted_plugins_for_price_tests() as $plugin ){
		$func = "ingot_is_{$plugin}_active";
		if ( function_exists( $func ) ) {
			$active = call_user_func( $func );
			if( $active ) {
				$enable = true;
				break;
			}
		}
	}

	/**
	 * Ovveride the enable/disable of price tests, based on active checks
	 *
	 * @since 0.0.9
	 *
	 * @param bool $enable True to allow, false to not allow.
	 */
	return (bool) apply_filters( 'ingot_enable_price_testing', $enable );

}

/**
 * Delete all Ingot data
 *
 * @since 0.2.0
 */
function ingot_destroy(){
	ingot_bootstrap::maybe_add_tracking_table( true );
	ingot_bootstrap::maybe_add_session_table( true );
	ingot_bootstrap::maybe_add_group_table( true );
	ingot_bootstrap::maybe_add_variant_table( true );

}

/**
 * Get a photo of Roy
 *
 * @since 0.4.0
 *
 * @return string
 */
function ingot_roy(){
	return 'http://videos.videopress.com/Ie8AvTuy/video-a83b381a23_dvd.original.jpg';

}


/**
 * Verify the Ingot session nonce
 *
 * @since 0.3.0
 *
 * @param string $nonce The nonce to check
 *
 * @return bool
 */
function ingot_verify_session_nonce( $nonce ) {
	$good =  (bool) wp_verify_nonce( $nonce, 'ingot_session' );
	return $good;

}

/**
 * Create a REST response
 *
 * @param array|object|\WP_Error $data Response data
 * @param int $code Optional. Status cod. Default is 200
 * @param int|null $total Optional. if is an integer, will be used to set X-Ingot-Total header
 *
 * @return \WP_REST_Response|\WP_Error
 */
function ingot_rest_response( $data, $code = 200, $total = null ){
	if ( ! is_wp_error( $data )  ) {
		if ( 404 == $code || empty( $data ) ) {
			$response = new \WP_REST_Response( null, 404 );
		} else {
			$response = new \WP_REST_Response( $data, $code );
		}

		if ( 0 < absint( $total ) ) {
			$response->header( 'X-Ingot-Total', (int) \ingot\testing\crud\group::total() );
		}

		return $response;
	} else {
		return $data;

	}

}

/**
 * Register a conversion
 *
 * @since 0.4.0
 *
 * @param int|array $variant Variant config or Variant ID to register conversion for
 * @param int $session_ID Optional. Session ID. If a valid session ID is passed, that session will be marked as having converted with this vartiant ID.
 */
function ingot_register_conversion( $variant, $session_ID = 0 ){
	if ( is_numeric( $variant ) ) {
		$variant = \ingot\testing\crud\variant::read( $variant );
	}

	if ( is_array( $variant ) ) {
		$bandit = new \ingot\testing\bandit\content( $variant[ 'group_ID' ] );
		$bandit->record_victory( $variant[ 'ID' ] );

		if ( 0 < absint( $session_ID ) && is_array( $session = \ingot\testing\crud\session::read( $session_ID ) ) ) {

		}else{
			$session = \ingot\testing\ingot::instance()->get_current_session()[ 'session' ];

		}

		if ( \ingot\testing\crud\session::valid( $session ) ) {
			$session[ 'click_ID' ] = $variant[ 'ID' ];
			$session[ 'used' ]     = true;
			if ( 0 !== ( $userID = get_current_user_id() ) ) {
				$session[ 'click_url' ] = $userID;
			}

			\ingot\testing\crud\session::update( $session, $session[ 'ID' ], true );

		}

	}

}

/**
 * Detect if current visitor is likely a bot
 *
 * @since 0.4.0
 *
 * @return bool
 */
function ingot_is_bot(){
	$is_bot = false;
	$detect = new \Jaybizzle\CrawlerDetect\CrawlerDetect();
	if( $detect->isCrawler() ) {
		$is_bot = true;
	}

	/**
	 * Override bot detection
	 *
	 * @since 0.4.0
	 *
	 * @param bool $is_bot Whether to treat current visitor as bot or not
	 */
	return (bool) apply_filters( 'ingot_is_bot', $is_bot );

}

/**
 * Detect if current visitor is likely batman
 *
 * @since 0.4.0
 *
 * @return bool
 */
function ingot_is_batman(){
	$is_batman = false;
	$id = get_current_user_id();
	if( 0 != $id && is_object( $user = get_user_by( 'id', $id ) ) ) {
		if( 'Bruce Wayne' == $user->display_name ) {
			$is_batman = true;
		}
	}

	/**
	 * Override Batman detection
	 *
	 * @since 0.4.0
	 *
	 * @param bool $is_bot Whether to treat current visitor as bot or not
	 */
	return (bool) apply_filters( 'ingot_is_batman', $is_batman );

}

