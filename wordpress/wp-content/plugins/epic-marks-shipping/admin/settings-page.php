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
    $sanitized['ups_access_key'] = sanitize_text_field( $input['ups_access_key'] ?? '');
    $sanitized['ups_user_id'] = sanitize_text_field( $input['ups_user_id'] ?? '');
    $sanitized['ups_password'] = sanitize_text_field( $input['ups_password'] ?? '');
    $sanitized['ups_account_number'] = sanitize_text_field( $input['ups_account_number'] ?? '');
    
    // Warehouse address
    $sanitized['warehouse_address'] = sanitize_text_field( $input['warehouse_address'] ?? '');
    $sanitized['warehouse_city'] = sanitize_text_field( $input['warehouse_city'] ?? '');
    $sanitized['warehouse_state'] = sanitize_text_field( $input['warehouse_state'] ?? '');
    $sanitized['warehouse_zip'] = sanitize_text_field( $input['warehouse_zip'] ?? '');
    
    // Store address
    $sanitized['store_address'] = sanitize_text_field( $input['store_address'] ?? '');
    $sanitized['store_city'] = sanitize_text_field( $input['store_city'] ?? '');
    $sanitized['store_state'] = sanitize_text_field( $input['store_state'] ?? '');
    $sanitized['store_zip'] = sanitize_text_field( $input['store_zip'] ?? '');
    
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
 * Render settings page
 */
function em_shipping_render_settings_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    
    $settings = get_option( 'em_ups_settings', array() );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        
        <?php settings_errors( 'em_ups_settings_group' ); ?>
        
        <form method="post" action="options.php">
            <?php
            settings_fields( 'em_ups_settings_group' );
            ?>
            
            <h2><?php esc_html_e( 'General Settings', 'epic-marks-shipping' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Enable/Disable', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="em_ups_settings[enabled]" value="yes" <?php checked( $settings['enabled'] ?? 'yes', 'yes' ); ?>>
                            <?php esc_html_e( 'Enable UPS shipping method', 'epic-marks-shipping' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="title"><?php esc_html_e( 'Method Title', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="title" name="em_ups_settings[title]" value="<?php echo esc_attr( $settings['title'] ?? 'UPS Shipping' ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'This controls the title which the user sees during checkout.', 'epic-marks-shipping' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Test Mode', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="em_ups_settings[test_mode]" value="yes" <?php checked( $settings['test_mode'] ?? 'yes', 'yes' ); ?>>
                            <?php esc_html_e( 'Enable UPS sandbox mode for testing', 'epic-marks-shipping' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e( 'UPS API Credentials', 'epic-marks-shipping' ); ?></h2>
            <p><?php esc_html_e( 'Get your credentials from', 'epic-marks-shipping' ); ?> <a href="https://developer.ups.com/" target="_blank">https://developer.ups.com/</a></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ups_access_key"><?php esc_html_e( 'Access Key', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ups_access_key" name="em_ups_settings[ups_access_key]" value="<?php echo esc_attr( $settings['ups_access_key'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ups_user_id"><?php esc_html_e( 'User ID', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ups_user_id" name="em_ups_settings[ups_user_id]" value="<?php echo esc_attr( $settings['ups_user_id'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ups_password"><?php esc_html_e( 'Password', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="ups_password" name="em_ups_settings[ups_password]" value="<?php echo esc_attr( $settings['ups_password'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ups_account_number"><?php esc_html_e( 'Account Number', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ups_account_number" name="em_ups_settings[ups_account_number]" value="<?php echo esc_attr( $settings['ups_account_number'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e( 'Shipping Locations', 'epic-marks-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Products are assigned to locations based on tags: "SSAW-App" for warehouse, "available-in-store" for retail store.', 'epic-marks-shipping' ); ?></p>
            
            <h3><?php esc_html_e( 'Warehouse Address', 'epic-marks-shipping' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="warehouse_address"><?php esc_html_e( 'Street Address', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="warehouse_address" name="em_ups_settings[warehouse_address]" value="<?php echo esc_attr( $settings['warehouse_address'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="warehouse_city"><?php esc_html_e( 'City', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="warehouse_city" name="em_ups_settings[warehouse_city]" value="<?php echo esc_attr( $settings['warehouse_city'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="warehouse_state"><?php esc_html_e( 'State', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="warehouse_state" name="em_ups_settings[warehouse_state]" value="<?php echo esc_attr( $settings['warehouse_state'] ?? ''); ?>" class="regular-text" placeholder="CA" maxlength="2"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="warehouse_zip"><?php esc_html_e( 'ZIP Code', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="warehouse_zip" name="em_ups_settings[warehouse_zip]" value="<?php echo esc_attr( $settings['warehouse_zip'] ?? ''); ?>" class="regular-text"></td>
                </tr>
            </table>
            
            <h3><?php esc_html_e( 'Retail Store Address', 'epic-marks-shipping' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="store_address"><?php esc_html_e( 'Street Address', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="store_address" name="em_ups_settings[store_address]" value="<?php echo esc_attr( $settings['store_address'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_city"><?php esc_html_e( 'City', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="store_city" name="em_ups_settings[store_city]" value="<?php echo esc_attr( $settings['store_city'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_state"><?php esc_html_e( 'State', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="store_state" name="em_ups_settings[store_state]" value="<?php echo esc_attr( $settings['store_state'] ?? ''); ?>" class="regular-text" placeholder="CA" maxlength="2"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="store_zip"><?php esc_html_e( 'ZIP Code', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="text" id="store_zip" name="em_ups_settings[store_zip]" value="<?php echo esc_attr( $settings['store_zip'] ?? ''); ?>" class="regular-text"></td>
                </tr>
            </table>
            
            <h2><?php esc_html_e( 'Location Settings', 'epic-marks-shipping' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="overlap_preference"><?php esc_html_e( 'Overlap Preference', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <select id="overlap_preference" name="em_ups_settings[overlap_preference]">
                            <option value="warehouse" <?php selected( $settings['overlap_preference'] ?? 'warehouse', 'warehouse' ); ?>><?php esc_html_e( 'Warehouse', 'epic-marks-shipping' ); ?></option>
                            <option value="store" <?php selected( $settings['overlap_preference'] ?? 'warehouse', 'store' ); ?>><?php esc_html_e( 'Store', 'epic-marks-shipping' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'When a product has both "SSAW-App" and "available-in-store" tags, ship from:', 'epic-marks-shipping' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="default_location"><?php esc_html_e( 'Default Location', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <select id="default_location" name="em_ups_settings[default_location]">
                            <option value="warehouse" <?php selected( $settings['default_location'] ?? 'warehouse', 'warehouse' ); ?>><?php esc_html_e( 'Warehouse', 'epic-marks-shipping' ); ?></option>
                            <option value="store" <?php selected( $settings['default_location'] ?? 'warehouse', 'store' ); ?>><?php esc_html_e( 'Store', 'epic-marks-shipping' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'When a product has no location tags, ship from:', 'epic-marks-shipping' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e( 'Services & Options', 'epic-marks-shipping' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'UPS Services', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <?php
                        $services = array(
                            'ground' => 'Ground',
                            '2day' => '2nd Day Air',
                            'nextday' => 'Next Day Air'
                        );
                        $selected_services = $settings['services'] ?? array( 'ground', '2day', 'nextday' );
                        foreach ( $services as $code => $name ) :
                        ?>
                            <label style="display:block;margin-bottom:5px;">
                                <input type="checkbox" name="em_ups_settings[services][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $selected_services, true ) ); ?>>
                                <?php echo esc_html( $name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="free_shipping_threshold"><?php esc_html_e( 'Free Shipping Threshold', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="free_shipping_threshold" name="em_ups_settings[free_shipping_threshold]" value="<?php echo esc_attr( $settings['free_shipping_threshold'] ?? ''); ?>" step="0.01" min="0" class="small-text">
                        <p class="description"><?php esc_html_e( 'Cart total required for free shipping (leave empty to disable)', 'epic-marks-shipping' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="multi_origin_strategy"><?php esc_html_e( 'Multi-Origin Strategy', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <select id="multi_origin_strategy" name="em_ups_settings[multi_origin_strategy]">
                            <option value="highest" <?php selected( $settings['multi_origin_strategy'] ?? 'highest', 'highest' ); ?>><?php esc_html_e( 'Highest Rate', 'epic-marks-shipping' ); ?></option>
                            <option value="sum" <?php selected( $settings['multi_origin_strategy'] ?? 'highest', 'sum' ); ?>><?php esc_html_e( 'Sum of Rates', 'epic-marks-shipping' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'How to combine rates when cart contains products from multiple locations', 'epic-marks-shipping' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <h2><?php esc_html_e( 'Fallback Rates', 'epic-marks-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'These rates will be used if the UPS API is unavailable.', 'epic-marks-shipping' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="fallback_ground"><?php esc_html_e( 'Ground', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="number" id="fallback_ground" name="em_ups_settings[fallback_rates][ground]" value="<?php echo esc_attr( $settings['fallback_rates']['ground'] ?? '10.00' ); ?>" step="0.01" min="0" class="small-text"> USD</td>
                </tr>
                <tr>
                    <th scope="row"><label for="fallback_2day"><?php esc_html_e( '2nd Day Air', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="number" id="fallback_2day" name="em_ups_settings[fallback_rates][2day]" value="<?php echo esc_attr( $settings['fallback_rates']['2day'] ?? '15.00' ); ?>" step="0.01" min="0" class="small-text"> USD</td>
                </tr>
                <tr>
                    <th scope="row"><label for="fallback_nextday"><?php esc_html_e( 'Next Day Air', 'epic-marks-shipping' ); ?></label></th>
                    <td><input type="number" id="fallback_nextday" name="em_ups_settings[fallback_rates][nextday]" value="<?php echo esc_attr( $settings['fallback_rates']['nextday'] ?? '25.00' ); ?>" step="0.01" min="0" class="small-text"> USD</td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <hr>
        <h2><?php esc_html_e( 'Clear Shipping Cache', 'epic-marks-shipping' ); ?></h2>
        <p><?php esc_html_e( 'Clear cached shipping rates to force fresh API calls.', 'epic-marks-shipping' ); ?></p>
        <button type="button" class="button" onclick="if(confirm('Clear all cached shipping rates?')) { jQuery.post(ajaxurl, {action: 'em_clear_shipping_cache'}, function() { alert('Cache cleared!'); }); }"><?php esc_html_e( 'Clear Cache', 'epic-marks-shipping' ); ?></button>
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
