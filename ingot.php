<?php
/**
 Plugin Name: Ingot
Version: 1.0.0
 */


define( 'INGOT_VER', '1.0.0' );
define( 'INGOT_URL', plugin_dir_url( __FILE__ ) );
define( 'INGOT_DIR', dirname( __FILE__ ) );
define( 'INGOT_UI_PARTIALS_DIR', dirname( __FILE__ ) . '/classes/ui/admin/partials/' );
define( 'INGOT_ROOT', basename( dirname( __FILE__ ) ) );


add_action( 'init', function(){
//	include_once( dirname( __FILE__ ) . '/tests/functions_for_tests.php' );
//	include_once( dirname( __FILE__ ) . '/includes/functions.php' );
//	ingot_destroy();
//	$groups = ingot_tests_data::click_link_group(true, 1, 4 );
//	$id = $groups[ 'ids' ][0];
//	for ( $i = 0; $i <= 100; $i++  ) {
//		$render = new \ingot\ui\render\click_tests\button( $id );
//		$chosen = $render->get_chosen_variant_id();
//		if ( in_array( $i, [ 2, 3, 5, 8, 13, 21, 34, 55, 89 ] ) ) {
//			ingot_register_conversion( $chosen );
//
//		}
//
//	}
});


add_action( 'plugins_loaded', 'ingot_maybe_load', 0 );
function ingot_maybe_load() {
	$fail = false;
	if ( ! version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {


		if ( is_admin() ) {
			include_once( dirname( __FILE__ ) . '/vendor/calderawp/dismissible-notice/src/functions.php' );
			$message = __( sprintf( 'Ingot requires PHP version %1s or later. Current version is %2s.', '5.5.0', PHP_VERSION ), 'ingot' );

			echo caldera_warnings_dismissible_notice( $message, true, 'activate_plugins' );
			$fail = true;
		}

	}

	global $wp_version;
	if ( ! version_compare( $wp_version, '4.4', '>=' ) ) {


		if ( is_admin() ) {
			include_once( dirname( __FILE__ ) . 'vendor/calderawp/dismissible-notice/src/functions.php' );
			$message = __( sprintf( 'Ingot requires WordPress version %1s or later. Current version is %2s.', '4.0', $wp_version ), 'ingot' );

			echo caldera_warnings_dismissible_notice( $message, true, 'activate_plugins' );
			$fail = true;
		}

	}


	if( false == $fail ){
		include_once( dirname(__FILE__ ) . '/ingot_bootstrap.php' );
		add_action( 'plugins_loaded', array( 'ingot_bootstrap', 'maybe_load' ) );
	}


}

/**
 * EDD Licensing
 */
define( 'INGOT_SL_STORE_URL', 'http://ingothq.com' );
define( 'INGOT_SL_ITEM_NAME', 'Ingot Plugin: The Automatic A/B Tester' );
add_action( 'admin_init', 'ingot_sl_plugin_updater', 0 );
add_action( 'admin_init', 'ingot_sl_register_option');
if( is_admin() && ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( dirname( __FILE__ ) . '/includes/EDD_SL_Plugin_Updater.php' );
}

