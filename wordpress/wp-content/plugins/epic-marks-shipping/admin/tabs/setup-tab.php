<?php
/**
 * Setup Tab - UPS Credentials & Global Settings
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = get_option( 'em_ups_settings', array() );
$locations = EM_Location_Manager::get_all_locations();
?>

<form method="post" action="options.php" class="em-shipping-settings-form">
    <?php settings_fields( 'em_ups_settings_group' ); ?>
    
    <?php if ( empty( $locations ) ) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'Location Configuration Required:', 'epic-marks-shipping' ); ?></strong>
                <?php esc_html_e( 'No shipping locations configured.', 'epic-marks-shipping' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations' ) ); ?>" class="button button-primary button-small" style="margin-left: 10px;">
                    <?php esc_html_e( 'Configure Locations', 'epic-marks-shipping' ); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="notice notice-info" style="position: relative;">
            <p>
                <span class="dashicons dashicons-location" style="color: #2271b1; margin-right: 5px;"></span>
                <strong><?php esc_html_e( 'Shipping Locations:', 'epic-marks-shipping' ); ?></strong>
                <?php
                printf(
                    esc_html__( 'You have %s configured. Manage locations in the %s tab.', 'epic-marks-shipping' ),
                    '<strong>' . count( $locations ) . ' ' . _n( 'location', 'locations', count( $locations ), 'epic-marks-shipping' ) . '</strong>',
                    '<a href="' . esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations' ) ) . '">' . esc_html__( 'Locations', 'epic-marks-shipping' ) . '</a>'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>
    
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
                <input type="text" id="ups_access_key" name="em_ups_settings[ups_access_key]" value="<?php echo esc_attr( $settings['ups_access_key'] ?? '' ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ups_user_id"><?php esc_html_e( 'User ID', 'epic-marks-shipping' ); ?></label>
            </th>
            <td>
                <input type="text" id="ups_user_id" name="em_ups_settings[ups_user_id]" value="<?php echo esc_attr( $settings['ups_user_id'] ?? '' ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ups_password"><?php esc_html_e( 'Password', 'epic-marks-shipping' ); ?></label>
            </th>
            <td>
                <input type="password" id="ups_password" name="em_ups_settings[ups_password]" value="<?php echo esc_attr( $settings['ups_password'] ?? '' ); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="ups_account_number"><?php esc_html_e( 'Account Number', 'epic-marks-shipping' ); ?></label>
            </th>
            <td>
                <input type="text" id="ups_account_number" name="em_ups_settings[ups_account_number]" value="<?php echo esc_attr( $settings['ups_account_number'] ?? '' ); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    
    <h2><?php esc_html_e( 'Services & Options', 'epic-marks-shipping' ); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php esc_html_e( 'Default UPS Services', 'epic-marks-shipping' ); ?></label>
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
                <p class="description"><?php esc_html_e( 'Default services for new locations. Individual locations can override this in the Locations tab.', 'epic-marks-shipping' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="free_shipping_threshold"><?php esc_html_e( 'Free Shipping Threshold', 'epic-marks-shipping' ); ?></label>
            </th>
            <td>
                <input type="number" id="free_shipping_threshold" name="em_ups_settings[free_shipping_threshold]" value="<?php echo esc_attr( $settings['free_shipping_threshold'] ?? '' ); ?>" step="0.01" min="0" class="small-text">
                <p class="description">
                    <?php esc_html_e( 'Cart total required for free shipping (leave empty to disable)', 'epic-marks-shipping' ); ?>
                    <br>
                    <em><?php esc_html_e( 'Note: This will be replaced by conditional rules in a future update for more flexible free shipping logic.', 'epic-marks-shipping' ); ?></em>
                </p>
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
