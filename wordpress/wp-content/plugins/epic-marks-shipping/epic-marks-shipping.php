<?php
/**
 * Plugin Name: Epic Marks Shipping
 * Plugin URI: https://epicmarks.com
 * Description: Custom UPS real-time shipping rates with multi-location support and PirateShip integration
 * Version: 1.0.0
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
define( 'EM_SHIPPING_VERSION', '1.0.0' );
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

    // Load plugin classes
    require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-ups-api.php';
    require_once EM_SHIPPING_PLUGIN_DIR . 'includes/class-shipping-method.php';
    require_once EM_SHIPPING_PLUGIN_DIR . 'admin/settings-page.php';
    require_once EM_SHIPPING_PLUGIN_DIR . 'admin/product-meta.php';
    require_once EM_SHIPPING_PLUGIN_DIR . 'admin/order-meta-box.php';
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
        'store_state' => '',
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
