<?php
/**
 * Location Edit/Create Form
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine if we're editing or creating
$is_new = isset( $_GET['action'] ) && $_GET['action'] === 'new';
$location_id = isset( $_GET['location_id'] ) ? sanitize_key( $_GET['location_id'] ) : '';
$location = null;

if ( ! $is_new && $location_id ) {
    $location = EM_Location_Manager::get_location( $location_id );
    if ( ! $location ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Location not found.', 'epic-marks-shipping' ) . '</p></div>';
        return;
    }
}

// Handle form submission
if ( isset( $_POST['em_save_location'] ) && check_admin_referer( 'em_save_location' ) ) {
    $location_data = array(
        'name' => sanitize_text_field( $_POST['location_name'] ?? '' ),
        'type' => sanitize_key( $_POST['location_type'] ?? '' ),
        'group' => sanitize_text_field( $_POST['location_group'] ?? '' ),
        'warehouse_code' => sanitize_text_field( $_POST['warehouse_code'] ?? '' ),
        'address' => array(
            'company' => sanitize_text_field( $_POST['address_company'] ?? '' ),
            'address_1' => sanitize_text_field( $_POST['address_1'] ?? '' ),
            'city' => sanitize_text_field( $_POST['address_city'] ?? '' ),
            'state' => strtoupper( sanitize_text_field( $_POST['address_state'] ?? '' ) ),
            'zip' => sanitize_text_field( $_POST['address_zip'] ?? '' ),
            'country' => strtoupper( sanitize_text_field( $_POST['address_country'] ?? 'US' ) ),
        ),
        'capabilities' => isset( $_POST['capabilities'] ) && is_array( $_POST['capabilities'] ) ? array_map( 'sanitize_key', $_POST['capabilities'] ) : array(),
        'services' => isset( $_POST['services'] ) && is_array( $_POST['services'] ) ? array_map( 'sanitize_key', $_POST['services'] ) : array(),
        'processing_time' => absint( $_POST['processing_time'] ?? 1 ),
        'cutoff_time' => sanitize_text_field( $_POST['cutoff_time'] ?? '14:00' ),
        'holidays' => sanitize_text_field( $_POST['holidays'] ?? 'countdown_timer' ),
        'priority' => absint( $_POST['priority'] ?? 99 ),
        'status' => sanitize_key( $_POST['status'] ?? 'active' ),
    );

    if ( $is_new ) {
        $result = EM_Location_Manager::create_location( $location_data );
    } else {
        $result = EM_Location_Manager::update_location( $location_id, $location_data );
    }

    if ( is_wp_error( $result ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Location saved successfully.', 'epic-marks-shipping' ) . '</p></div>';
        $location = $result;
        $location_id = $result['id'];
        $is_new = false;
    }
}

// Set defaults for new location
if ( $is_new ) {
    $location = array(
        'name' => '',
        'type' => 'store',
        'group' => 'retail_stores',
        'warehouse_code' => '',
        'address' => array(
            'company' => '',
            'address_1' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => 'US',
        ),
        'capabilities' => array( 'shipping', 'pickup' ),
        'services' => array( 'ground', '2day', 'nextday' ),
        'processing_time' => 1,
        'cutoff_time' => '14:00',
        'holidays' => 'countdown_timer',
        'priority' => 99,
        'status' => 'active',
    );
}
?>

<div class="wrap em-location-edit">
    <h1 class="wp-heading-inline">
        <?php echo $is_new ? esc_html__( 'Add New Location', 'epic-marks-shipping' ) : esc_html__( 'Edit Location', 'epic-marks-shipping' ); ?>
    </h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Back to Locations', 'epic-marks-shipping' ); ?>
    </a>
    <hr class="wp-header-end">

    <form method="post" action="" class="em-location-form">
        <?php wp_nonce_field( 'em_save_location' ); ?>

        <h2><?php esc_html_e( 'Basic Information', 'epic-marks-shipping' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="location_name"><?php esc_html_e( 'Location Name', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="location_name" name="location_name" value="<?php echo esc_attr( $location['name'] ?? '' ); ?>" class="regular-text" required>
                    <p class="description"><?php esc_html_e( 'e.g., Round Rock Store, South Austin Store, SSAW Texas Warehouse', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="location_type"><?php esc_html_e( 'Location Type', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select id="location_type" name="location_type" required>
                        <option value="store" <?php selected( $location['type'] ?? 'store', 'store' ); ?>><?php esc_html_e( 'Retail Store', 'epic-marks-shipping' ); ?></option>
                        <option value="warehouse" <?php selected( $location['type'] ?? '', 'warehouse' ); ?>><?php esc_html_e( 'Warehouse', 'epic-marks-shipping' ); ?></option>
                        <option value="ssaw_warehouse" <?php selected( $location['type'] ?? '', 'ssaw_warehouse' ); ?>><?php esc_html_e( 'SSAW Warehouse', 'epic-marks-shipping' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Select the type of fulfillment location', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr class="em-warehouse-code-row" style="<?php echo ( $location['type'] ?? '' ) === 'ssaw_warehouse' ? '' : 'display:none;'; ?>">
                <th scope="row">
                    <label for="warehouse_code"><?php esc_html_e( 'Warehouse Code', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="warehouse_code" name="warehouse_code" value="<?php echo esc_attr( $location['warehouse_code'] ?? '' ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'SSAW warehouse code (e.g., TX, IL, CA)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="location_group"><?php esc_html_e( 'Location Group', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="text" id="location_group" name="location_group" value="<?php echo esc_attr( $location['group'] ?? '' ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'Group locations together for routing and reporting (e.g., retail_stores, ssaw_warehouses)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="status"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <select id="status" name="status">
                        <option value="active" <?php selected( $location['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'epic-marks-shipping' ); ?></option>
                        <option value="inactive" <?php selected( $location['status'] ?? 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'epic-marks-shipping' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Address', 'epic-marks-shipping' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="address_company"><?php esc_html_e( 'Company', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="text" id="address_company" name="address_company" value="<?php echo esc_attr( $location['address']['company'] ?? '' ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="address_1"><?php esc_html_e( 'Street Address', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="text" id="address_1" name="address_1" value="<?php echo esc_attr( $location['address']['address_1'] ?? '' ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="address_city"><?php esc_html_e( 'City', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="address_city" name="address_city" value="<?php echo esc_attr( $location['address']['city'] ?? '' ); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="address_state"><?php esc_html_e( 'State', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="address_state" name="address_state" value="<?php echo esc_attr( $location['address']['state'] ?? '' ); ?>" class="small-text" maxlength="2" placeholder="TX" style="text-transform: uppercase;" required>
                    <p class="description"><?php esc_html_e( '2-letter state code (e.g., TX, CA, NY)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="address_zip"><?php esc_html_e( 'ZIP Code', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="address_zip" name="address_zip" value="<?php echo esc_attr( $location['address']['zip'] ?? '' ); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="address_country"><?php esc_html_e( 'Country', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="address_country" name="address_country" value="<?php echo esc_attr( $location['address']['country'] ?? 'US' ); ?>" class="small-text" maxlength="2" placeholder="US" style="text-transform: uppercase;" required>
                    <p class="description"><?php esc_html_e( '2-letter country code (e.g., US, CA, MX)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Capabilities & Services', 'epic-marks-shipping' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'Capabilities', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <fieldset>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="capabilities[]" value="shipping" <?php checked( in_array( 'shipping', $location['capabilities'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'Shipping', 'epic-marks-shipping' ); ?>
                        </label>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="capabilities[]" value="pickup" <?php checked( in_array( 'pickup', $location['capabilities'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'Pickup', 'epic-marks-shipping' ); ?>
                        </label>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="capabilities[]" value="local_delivery" <?php checked( in_array( 'local_delivery', $location['capabilities'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'Local Delivery', 'epic-marks-shipping' ); ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'What fulfillment methods this location supports', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'UPS Services', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <fieldset>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="services[]" value="ground" <?php checked( in_array( 'ground', $location['services'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'UPS Ground', 'epic-marks-shipping' ); ?>
                        </label>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="services[]" value="2day" <?php checked( in_array( '2day', $location['services'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'UPS 2nd Day Air', 'epic-marks-shipping' ); ?>
                        </label>
                        <label style="display:block;margin-bottom:5px;">
                            <input type="checkbox" name="services[]" value="nextday" <?php checked( in_array( 'nextday', $location['services'] ?? array(), true ) ); ?>>
                            <?php esc_html_e( 'UPS Next Day Air', 'epic-marks-shipping' ); ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'Which UPS services are available from this location (SSAW warehouses typically only support Ground)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Processing & Scheduling', 'epic-marks-shipping' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="processing_time"><?php esc_html_e( 'Processing Time (days)', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="number" id="processing_time" name="processing_time" value="<?php echo esc_attr( $location['processing_time'] ?? 1 ); ?>" min="0" max="30" class="small-text">
                    <p class="description"><?php esc_html_e( 'Number of business days to process orders before shipping (0 = same day)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cutoff_time"><?php esc_html_e( 'Same-Day Cutoff Time', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="time" id="cutoff_time" name="cutoff_time" value="<?php echo esc_attr( $location['cutoff_time'] ?? '14:00' ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'Orders placed before this time ship same day (if processing time is 0)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="holidays"><?php esc_html_e( 'Holiday Calendar', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <select id="holidays" name="holidays">
                        <option value="countdown_timer" <?php selected( $location['holidays'] ?? 'countdown_timer', 'countdown_timer' ); ?>><?php esc_html_e( 'Countdown Timer Holidays (Store Closures)', 'epic-marks-shipping' ); ?></option>
                        <option value="ups" <?php selected( $location['holidays'] ?? 'countdown_timer', 'ups' ); ?>><?php esc_html_e( 'UPS Holiday Calendar', 'epic-marks-shipping' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Which holiday calendar to use for delivery date estimation', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="priority"><?php esc_html_e( 'Priority', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="number" id="priority" name="priority" value="<?php echo esc_attr( $location['priority'] ?? 99 ); ?>" min="1" max="999" class="small-text">
                    <p class="description"><?php esc_html_e( 'Lower numbers have higher priority for routing decisions (1 = highest priority)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="em_save_location" class="button button-primary button-large">
                <span class="dashicons dashicons-saved"></span>
                <?php echo $is_new ? esc_html__( 'Create Location', 'epic-marks-shipping' ) : esc_html__( 'Update Location', 'epic-marks-shipping' ); ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations' ) ); ?>" class="button button-large">
                <?php esc_html_e( 'Cancel', 'epic-marks-shipping' ); ?>
            </a>
        </p>
    </form>
</div>
