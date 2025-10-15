<?php
/**
 * Plugin Name: Epic Marks Shipping
 * Plugin URI: https://epicmarks.com
 * Description: Custom UPS real-time shipping rates with multi-location support and PirateShip integration
 * Version: 2.0.0
 * Author: Epic Marks
 * Author URI: https://epicmarks.com
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * WC requires at least: 6.0
 * WC tested up to: 10.2
 * Text Domain: epic-marks-shipping
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'EM_SHIPPING_VERSION', '2.0.0' );
define( 'EM_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EM_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EM_SHIPPING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
function em_shipping_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'em_shipping_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function em_shipping_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Epic Marks Shipping requires WooCommerce to be installed and active.', 'epic-marks-shipping' ); ?></p>
	</div>
	<?php
}

/**
 * Initialize the plugin
 */
function em_shipping_init() {
	if ( ! em_shipping_check_woocommerce() ) {
		return;
	}

	// Load plugin classes (core)
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-ups-api.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-shipping-profile.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-profile-manager.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-profile-rate-calculator.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-package-splitter.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-shipping-method.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-shipping-reports.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'includes/migration.php';

	// Load admin files
	require_once EM_SHIPPING_PLUGIN_DIR . 'admin/settings-page.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'admin/admin-tabs.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'admin/product-meta.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'admin/order-meta-box.php';
	require_once EM_SHIPPING_PLUGIN_DIR . 'admin/bulk-assignment.php';
}
add_action( 'plugins_loaded', 'em_shipping_init' );

/**
 * Register the shipping method with WooCommerce
 */
function em_shipping_register_method( $methods ) {
	$methods['epic_marks_ups'] = 'EM_UPS_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'em_shipping_register_method' );

/**
 * Split cart into packages based on shipping mode selection
 *
 * @param array $packages WooCommerce packages
 * @return array Modified packages
 */
function em_shipping_split_packages( $packages ) {
	// Check if customer selected partial pickup mode
	$shipping_mode = WC()->session->get( 'em_shipping_mode', 'ship_all' );

	if ( $shipping_mode !== 'partial_pickup' || empty( $packages ) ) {
		// Standard mode - no splitting
		return $packages;
	}

	// Get first package (WooCommerce creates one by default)
	$original_package = reset( $packages );

	// Split into multiple packages by profile/location
	$split_packages = EM_Package_Splitter::split( 
		$original_package['contents'], 
		$original_package['destination'] 
	);

	// Preserve required WooCommerce package fields
	$new_packages = array();
	foreach ( $split_packages as $index => $package ) {
		$package['applied_coupons'] = $original_package['applied_coupons'] ?? array();
		$package['user'] = $original_package['user'] ?? array();
		$package['package_name'] = EM_Package_Splitter::get_package_label( $package );
		
		$new_packages[] = $package;
	}

	return ! empty( $new_packages ) ? $new_packages : $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'em_shipping_split_packages', 10 );

/**
 * Enqueue admin scripts and styles
 */
function em_shipping_enqueue_admin_scripts( $hook ) {
	// Only load on plugin settings page
	if ( 'woocommerce_page_em-ups-shipping' !== $hook ) {
		return;
	}

	// Get current tab
	$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'setup';

	// Enqueue bulk assignment scripts on routing tab
	if ( $current_tab === 'routing' ) {
		wp_enqueue_script(
			'em-bulk-assignment',
			EM_SHIPPING_PLUGIN_URL . 'assets/bulk-assignment.js',
			array( 'jquery' ),
			EM_SHIPPING_VERSION,
			true
		);

		wp_localize_script(
			'em-bulk-assignment',
			'emBulkAssignment',
			array(
				'nonce' => wp_create_nonce( 'em_bulk_assignment_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			)
		);
	}

	// Enqueue reports scripts on reports tab
	if ( $current_tab === 'reports' ) {
		// Chart.js from CDN
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// Reports charts script
		wp_enqueue_script(
			'em-reports-charts',
			EM_SHIPPING_PLUGIN_URL . 'assets/reports-charts.js',
			array( 'jquery', 'chartjs' ),
			EM_SHIPPING_VERSION,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'em_shipping_enqueue_admin_scripts' );

/**
 * Enqueue frontend scripts and styles for checkout
 */
function em_shipping_enqueue_checkout_scripts() {
	if ( ! is_checkout() ) {
		return;
	}

	// Enqueue checkout toggle script
	wp_enqueue_script(
		'em-checkout-toggle',
		EM_SHIPPING_PLUGIN_URL . 'assets/checkout-toggle.js',
		array( 'jquery' ),
		EM_SHIPPING_VERSION,
		true
	);

	wp_localize_script(
		'em-checkout-toggle',
		'emCheckout',
		array(
			'nonce' => wp_create_nonce( 'em_checkout_nonce' ),
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		)
	);

	// Enqueue checkout styles
	wp_enqueue_style(
		'em-checkout-packages',
		EM_SHIPPING_PLUGIN_URL . 'assets/checkout-packages.css',
		array(),
		EM_SHIPPING_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'em_shipping_enqueue_checkout_scripts' );

/**
 * AJAX handler to update shipping mode
 */
function em_ajax_update_shipping_mode() {
	check_ajax_referer( 'em_checkout_nonce', 'nonce' );

	$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'ship_all';

	if ( ! in_array( $mode, array( 'ship_all', 'partial_pickup' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid shipping mode' ) );
	}

	// Store in session
	WC()->session->set( 'em_shipping_mode', $mode );

	wp_send_json_success( array( 'mode' => $mode ) );
}
add_action( 'wp_ajax_em_update_shipping_mode', 'em_ajax_update_shipping_mode' );
add_action( 'wp_ajax_nopriv_em_update_shipping_mode', 'em_ajax_update_shipping_mode' );

/**
 * Display shipping mode toggle on checkout page
 */
function em_display_shipping_mode_toggle() {
	// Check if cart has products from multiple locations
	$cart_items = WC()->cart->get_cart();
	if ( empty( $cart_items ) ) {
		return;
	}

	// Check if there are products from different locations
	$locations = array();
	foreach ( $cart_items as $item ) {
		$profile = EM_Profile_Manager::get_product_profile( $item['product_id'] );
		if ( $profile && ! empty( $profile->fulfillment_locations ) ) {
			foreach ( $profile->fulfillment_locations as $location ) {
				$locations[ $location ] = true;
			}
		}
	}

	// Only show toggle if products from both warehouse and store
	if ( count( $locations ) < 2 ) {
		return;
	}

	// Load template
	$template_path = EM_SHIPPING_PLUGIN_DIR . 'templates/checkout-shipping-toggle.php';
	if ( file_exists( $template_path ) ) {
		include $template_path;
	}
}
add_action( 'woocommerce_before_checkout_shipping_form', 'em_display_shipping_mode_toggle', 5 );

/**
 * Exclude cart and checkout pages from WP Super Cache
 */
function em_shipping_exclude_from_cache( $excluded_urls ) {
	$excluded_urls[] = '/cart';
	$excluded_urls[] = '/checkout';
	$excluded_urls[] = '/my-account';
	return $excluded_urls;
}
add_filter( 'wpsc_cache_uris', 'em_shipping_exclude_from_cache' );

/**
 * Plugin activation hook
 */
function em_shipping_activate() {
	// Check for WooCommerce
	if ( ! class_exists( 'WooCommerce' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Epic Marks Shipping requires WooCommerce to be installed and active.', 'epic-marks-shipping' ),
			esc_html__( 'Plugin Activation Error', 'epic-marks-shipping' ),
			array( 'back_link' => true )
		);
	}

	// Set default plugin options
	$default_settings = array(
		'enabled' => 'yes',
		'title' => 'UPS Shipping',
		'test_mode' => 'yes',
		'ups_access_key' => '',
		'ups_user_id' => '',
		'ups_password' => '',
		'ups_account_number' => '',
		'warehouse_address' => '',
		'warehouse_city' => '',
		'warehouse_state' => '',
		'warehouse_zip' => '',
		'store_address' => '',
		'store_city' => '',
		'store_zip' => '',
		'services' => array( 'ground', '2day', 'nextday' ),
		'free_shipping_threshold' => '',
		'multi_origin_strategy' => 'highest',
		'overlap_preference' => 'warehouse',
		'default_location' => 'warehouse',
		'fallback_rates' => array(
			'ground' => '10.00',
			'2day' => '15.00',
			'nextday' => '25.00'
		)
	);

	add_option( 'em_ups_settings', $default_settings );
}
register_activation_hook( __FILE__, 'em_shipping_activate' );

/**
 * Plugin deactivation hook
 */
function em_shipping_deactivate() {
	// Clear all shipping rate transients
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_em_ups_rate_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_em_ups_rate_%'" );
}
register_deactivation_hook( __FILE__, 'em_shipping_deactivate' );

/**
 * Declare HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
