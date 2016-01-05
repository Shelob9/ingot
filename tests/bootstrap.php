<?php

$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = '';
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';



function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../ingot.php';
	require dirname( __FILE__ ) . '/../../easy-digital-downloads/easy-digital-downloads.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

add_filter( 'ingot_user_can', '__return_true' );


activate_plugin( 'ingot/ingot.php' );

include_once( dirname( __FILE__ ) . '/functions_for_tests.php' );
ingot_destroy();
edd_install();

global $current_user;

$current_user = new WP_User(1);
$current_user->set_role('administrator');
global $wp_rest_server;
if(  ! is_object( $wp_rest_server )) {
	$wp_rest_server_class = apply_filters( 'wp_rest_server_class', 'WP_REST_Server' );
	$wp_rest_server = new $wp_rest_server_class;
}

if( ! defined( 'INGOT_DEV_MODE' ) ) {
	define( 'INGOT_DEV_MODE', true );
}


// Include helpers
include_once( dirname( __FILE__ ) .'/api/rest-test-case.php' );
