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
		wp_localize_script( 'ingot-admin-app', 'INGOT_TRANSLATION', $this->translation_strings() );



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
	 * Translation strings
	 *
	 * @since 0.2.0
	 *
	 * @access protected
	 *
	 * @return array
	 */
	protected function translation_strings() {
		return array(
			'group_saved'              => esc_html__( 'Group Saved', 'ingot' ),
			'fail'                     => esc_html__( 'Could Not Save', 'ingot' ),
			'sorry'                    => esc_html__( 'Please try again and/or contact support', 'ingot' ),
			'close'                    => esc_html__( 'Close', 'ingot' ),
			'saved'                    => esc_html__( 'Saved Group: ', 'ingot' ),
			'cant_remove'              => esc_html__( 'At this time, you can not remove a test from a group.', 'ingot' ),
			'beta_error_header'        => esc_html__( 'Beta Limitation Encountered', 'ingot' ),
			'beta_message'             => esc_html__( 'Sorry about that but Ingot is still in beta. We will be adding this feature soon.', 'ingot' ),
			'no_stats'                 => esc_html__( 'We do not have a functional stats viewer yet.', 'ingot' ),
			'deleted'                  => esc_html__( 'Test Group Deleted', 'ingot' ),
			'are_you_sure'             => esc_html__( 'Are You Sure About That?', 'ingot' ),
			'delete_confirm'           => esc_html__( 'Deleting all groups is not reversible or undoable.', 'ingot' ),
			'delete'                   => esc_html__( 'Delete', 'ingot' ),
			'cancel'                   => esc_html__( 'Cancel', 'ignot' ),
			'canceled'                 => esc_html__( 'Canceled', 'ingot' ),
			'spinner_alt'              => esc_html__( 'Loading Spinner', 'ingot' ),
			'no_tests'                 => esc_html__( 'This group has no tests', 'ingot' ),
			'invalid_price_test_range' => esc_html__( 'Please enter a number between -.99 and .99', 'ingot' ),
			'settings_saved'           => esc_html__( 'Settings Saved', 'ingot' ),
			'stats'                    => array(
				'no_stats' => esc_html__( 'No Stats for this group yet', 'ingot' ),
			),
			'groups'                   => array(
				'click_group_page_title' => esc_html__( 'Content Test Groups', 'ingot' ),
				'price_group_page_title' => esc_html__( 'Price Test Groups', 'ingot' ),
				'show_all'               => esc_html__( 'Show All', 'ingot' ),
				'create_new'             => esc_html__( 'Create New', 'ingot' ),
				'edit'                   => esc_html__( 'Edit Group', 'ingot' ),
				'stats'                  => esc_html__( 'Group Stats', 'ingot' ),
				'delete'                 => esc_html__( 'Delete Group', 'ingot' ),
			),
			'group'                    => array(
				'save_group'                           => esc_html__( 'Save Group', 'ingot' ),
				'type'                                 => esc_html__( 'Type', 'ingot' ),
				'name'                                 => esc_html__( 'Name', 'ingot' ),
				'group_settings_header'                => esc_html__( 'Group Settings', 'ingot' ),
				'link_label_group_setting'             => esc_html__( 'Link', 'ingot' ),
				'text_label_group_setting'             => esc_html__( 'Text (Used For All Buttons)', 'ingot' ),
				'color_label_group_setting'            => esc_html__( 'Color (Used For All Buttons)', 'ingot' ),
				'background_color_label_group_setting' => esc_html__( 'Background (Used For All Buttons)', 'ingot' ),
				'tests_header'                         => esc_html__( 'Tests', 'ingot' ),
				'text_label_test_setting'              => esc_html__( 'Text', 'ingot' ),
				'color_label_test_setting'             => esc_html__( 'Button Text Color', 'ingot' ),
				'background_color_label_test_setting'  => esc_html__( 'Button Background Color', 'ingot' ),
				'add_test'                             => esc_html__( 'Add Test', 'ingot' ),
				'plugin'                               => esc_html__( 'eCommerce Plugin', 'ingot' ),
				'product'                              => esc_html__( 'Product', 'ingot' ),
				'price_variation'                      => esc_html__( 'Price Variation (percentage)', 'ingot ' ),
				'delete'                               => esc_html__( 'Delete', 'ingot' )
			),
			'settings'                 => array(
				'page_header'          => esc_html__( 'Settings', 'ingot' ),
				'cache_mode_label'     => esc_html__( 'Work around caching', 'ingot' ),
				'cache_mode_desc'      => esc_html__( 'If you are using a static HTML cache testing will not work properly, since the same version of your site is shown to all visitors. Use this mode to work around this issue.', 'ingot' ),
				'click_tracking_label' => esc_html__( 'Advanced Click Tracking', 'ingot' ),
				'click_tracking_desc'  => esc_html__( 'Ingot always tracks clicks, in advanced mode, more details are tracked. This takes up more space in the database, but enables Ingot to be more powerful.', 'ingot' ),
				'anon_tracking_label'  => esc_html__( 'Share Your Data Anonymously', 'ingot' ),
				'anon_tracking_desc'   => esc_html__( 'When enabled, your test data is shared with Ingot to help us improve the service.', 'ingot' ),
				'license_code_label'   => esc_html__( 'License Code', 'ingot' ),
				'license_code_desc'    => esc_html__( 'Enter your license code to enable support and updates.', 'ingot' ),
				'save'                 => esc_html__( 'Save Settings', 'ingot' )

			),
			'welcome'                  => array(
				'banner'       => esc_url( INGOT_URL . 'assets/img/Ingot-logo-dark.png' ),
				'banner_alt'   => esc_html__( 'Ingot Banner Logo', 'ingot' ),
				'header'       => esc_html__( 'Ingot: Do Less, Convert More', 'ingot' ),
				'links_header' => esc_html__( 'Helpful Links', 'ingot' ),
				'video_header' => esc_html__( 'Watch This Short Video To Learn How To Use Ingot', 'ingot' ),
				'price_tests'  => esc_html__( 'Price Tests', 'ingot' ),
				'click_tests'  => esc_html__( 'Click Tests', 'ingot' ),
				'learn_more'   => esc_html__( 'Learn more about Ingot', 'ingot' ),
				'docs'         => esc_html__( 'Documentation', 'ingot' ),
				'support'      => esc_html__( 'Support', 'ingot' )
			)

		);
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
