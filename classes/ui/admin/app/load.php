<?php
/**
 * Load our admin Angular App
 *
 * @package   ingot
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock
 */

namespace ingot\ui\admin\app;


use ingot\testing\api\rest\util;
use ingot\testing\types;
use ingot\testing\utility\helpers;

class load {

	/**
	 * Menu slug
	 *
	 * @since 0.2.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $menu_slug = 'ingot-admin-app';

	/**
	 * Constructors this class
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		if( $this->menu_slug === helpers::v( 'page', $_GET, 0 ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );;
		}
	}

	/**
	 * Add menu page
	 *
	 * @uses "admin_menu"
	 *
	 * @since 0.2.0
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Ingot', 'ingot' ),
			__( 'Ingot', 'ingot' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'ingot_page' ),
			'dashicons-smiley',
			40
		);
	}

	/**
	 * Load scripts
	 *
	 * @todo Figure out how much of this is uneeded, switch the rest to local files managed with Bower or whatever
	 *
	 * @uses "admin_enqueue_scripts
	 *
	 * @since 0.2.0
	 */
	public function scripts() {
		$version = INGOT_VER;
		if( SCRIPT_DEBUG ) {
			$version = rand();
		}

		
		//other
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_style( 'font-awesome', INGOT_URL . "assets/admin/css/font-awesome.min.css" );
		wp_enqueue_script( 'angular-translatejs', INGOT_URL . "assets/vendor/js/angular-translate/angular-translate.js", array( 'angularjs' ), false, $version);
		wp_enqueue_style( 'bootstrap', INGOT_URL . 'assets/admin/css/bootstrap.min.css' );

		//dependencies
		$files = glob( INGOT_DIR . '/assets/vendor/js/**/*.js' );
		$root = '/' . INGOT_ROOT . '/';
		foreach( $files as $i => $path ){
			$handle = sanitize_key( basename( $path ) );
			$pos = strpos( $path, $root ) + strlen( $root );
			$path = INGOT_URL . substr( $path, $pos );
			if( 'angular-translatejs' == $handle ) {
				continue;
			}
			if ( 'angularjs' != $handle ) {
				$dep = [ 'angularjs', 'jquery' ];
			}else{
				wp_enqueue_script( $handle, $path, [], false, $version );
				continue;
			}
			wp_enqueue_script( $handle, $path, $dep, true, $version );
		}

		wp_enqueue_style( 'ingot-admin-dependencies', INGOT_URL . 'assets/admin/css/ingot-admin-dependencies.css' );

		//ingot
		wp_enqueue_script( 'ingot-admin-app', INGOT_URL . "assets/admin/js/admin-app.js", array( 'jquery', 'angularjs' ), rand() );
		wp_enqueue_style( 'ingot-admin-app', INGOT_URL . 'assets/admin/css/admin-app.css' );

		//data to use in admin app
		wp_localize_script( 'ingot-admin-app', 'INGOT_ADMIN', $this->vars() );

		//translation strings for admin app
		wp_localize_script( 'ingot-admin-app', 'INGOT_TRANSLATION', translations::strings() );



	}

	/**
	 * Initial markup for admin page
	 *
	 * @TODO move this into a partial
	 *
	 * @since 0.2.0
	 */
	public function ingot_page() {?>
		<div class="container" id="ingot-admin-app" ng-app="ingotApp">
			<nav class="navbar navbar-default">
				<div class="container-fluid">
					<div class="navbar-header">
						<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
							<span class="sr-only">
								<?php _e( 'Toogle Navigation', 'ingot' ); ?>
							</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<a class="navbar-brand active" ui-sref="otherwise" >
							<?php
								_e( 'Ingot', 'ingot' );
								printf( ' <small>%s</small>',  INGOT_VER );
							?>
						</a>
					</div>
					<div id="navbar" class="navbar-collapse collapse">
						<ul class="nav navbar-nav">
							<li ng-class="isActiveNav('clickTests');">
								<a ui-sref="clickTests">
									<?php _e( 'Content Tests', 'ingot' ); ?>
								</a>
							</li>
							<li ng-class="isActiveNav('price');">
								<a ui-sref="priceTests">
									<?php _e( 'Price Tests', 'ingot' ); ?>
								</a>
							</li>
						</ul>
						<ul class="nav navbar-nav navbar-right">
							<li>
								<a ui-sref="settings">
									<?php _e( 'Settings', 'ingot' ); ?>
								</a>
							</li>
							<li>
								<a ui-sref="support">
									<?php _e( 'Support', 'ingot' ); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</nav>
			<div ui-view></div>

		</div>

	<?php
	}

	/**
	 * Data needed in app
	 *
	 * @since 0.2.0
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected function vars() {
		return array(
			'api'                 => esc_url_raw( util::get_url() ),
			'nonce'               => wp_create_nonce( 'wp_rest' ),
			'partials'            => esc_url_raw( INGOT_URL . 'assets/admin/partials/' ),
			'spinner_url'         => trailingslashit( INGOT_URL ) . 'assets/img/loading.gif',
			'edd_active'          => esc_attr( ingot_is_edd_active() ),
			'woo_active'          => esc_attr( ingot_is_woo_active() ),
			'price_tests_enabled' => esc_attr( ingot_enable_price_testing() ),
			'click_type_options'  => types::allowed_click_types( true ),
			'price_type_options'  => types::allowed_price_types(),
		);
	}

}
