<?php
/**
 * Admin Settings Page
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add settings page to WooCommerce menu
 */
function em_shipping_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __( 'UPS Shipping Settings', 'epic-marks-shipping' ),
        __( 'UPS Shipping', 'epic-marks-shipping' ),
        'manage_woocommerce',
        'em-ups-shipping',
        'em_shipping_render_settings_page'
    );
}
add_action( 'admin_menu', 'em_shipping_add_settings_page' );

/**
 * Register settings
 */
function em_shipping_register_settings() {
    register_setting(
        'em_ups_settings_group',
        'em_ups_settings',
        'em_shipping_sanitize_settings'
    );
}
add_action( 'admin_init', 'em_shipping_register_settings' );

/**
 * Sanitize settings
 */
function em_shipping_sanitize_settings( $input ) {
    $sanitized = array();
    
    // Basic settings
    $sanitized['enabled'] = isset( $input['enabled'] ) ? 'yes' : 'no';
    $sanitized['title'] = sanitize_text_field( $input['title'] ?? 'UPS Shipping' );
    $sanitized['test_mode'] = isset( $input['test_mode'] ) ? 'yes' : 'no';
    
    // UPS credentials
    $sanitized['ups_access_key'] = sanitize_text_field( $input['ups_access_key'] ?? '' );
    $sanitized['ups_user_id'] = sanitize_text_field( $input['ups_user_id'] ?? '' );
    $sanitized['ups_password'] = sanitize_text_field( $input['ups_password'] ?? '' );
    $sanitized['ups_account_number'] = sanitize_text_field( $input['ups_account_number'] ?? '' );
    
    // Warehouse address
    $sanitized['warehouse_address'] = sanitize_text_field( $input['warehouse_address'] ?? '' );
    $sanitized['warehouse_city'] = sanitize_text_field( $input['warehouse_city'] ?? '' );
    $sanitized['warehouse_state'] = sanitize_text_field( $input['warehouse_state'] ?? '' );
    $sanitized['warehouse_zip'] = sanitize_text_field( $input['warehouse_zip'] ?? '' );
    
    // Store address
    $sanitized['store_address'] = sanitize_text_field( $input['store_address'] ?? '' );
    $sanitized['store_city'] = sanitize_text_field( $input['store_city'] ?? '' );
    $sanitized['store_state'] = sanitize_text_field( $input['store_state'] ?? '' );
    $sanitized['store_zip'] = sanitize_text_field( $input['store_zip'] ?? '' );
    
    // Services
    $sanitized['services'] = isset( $input['services'] ) && is_array( $input['services'] ) 
        ? array_map( 'sanitize_text_field', $input['services'] )
        : array();
    
    // Shipping options
    $sanitized['free_shipping_threshold'] = floatval( $input['free_shipping_threshold'] ?? 0 );
    $sanitized['multi_origin_strategy'] = sanitize_text_field( $input['multi_origin_strategy'] ?? 'highest' );
    $sanitized['overlap_preference'] = sanitize_text_field( $input['overlap_preference'] ?? 'warehouse' );
    $sanitized['default_location'] = sanitize_text_field( $input['default_location'] ?? 'warehouse' );
    
    // Fallback rates
    $sanitized['fallback_rates'] = array(
        'ground' => floatval( $input['fallback_rates']['ground'] ?? 10 ),
        '2day' => floatval( $input['fallback_rates']['2day'] ?? 15 ),
        'nextday' => floatval( $input['fallback_rates']['nextday'] ?? 25 )
    );
    
    return $sanitized;
}

/**
 * Render settings page with tabs
 */
function em_shipping_render_settings_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <?php settings_errors( 'em_ups_settings_group' ); ?>
        
        <?php
        // Render tab navigation
        em_shipping_render_tab_navigation();
        
        // Render current tab content
        em_shipping_render_tab_content();
        ?>
    </div>
    <?php
}

/**
 * AJAX handler to clear shipping cache
 */
function em_shipping_clear_cache() {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_em_ups_rate_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_em_ups_rate_%'" );
    wp_send_json_success();
}
add_action( 'wp_ajax_em_clear_shipping_cache', 'em_shipping_clear_cache' );
