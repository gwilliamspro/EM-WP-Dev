<?php
/**
 * Locations Tab - Location Management
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle SSAW warehouse import
if ( isset( $_POST['em_import_ssaw_warehouses'] ) && check_admin_referer( 'em_import_ssaw_warehouses' ) ) {
    $import_result = null;
    
    if ( isset( $_FILES['ssaw_import_file'] ) && $_FILES['ssaw_import_file']['error'] === UPLOAD_ERR_OK ) {
        $file_content = file_get_contents( $_FILES['ssaw_import_file']['tmp_name'] );
        $file_ext = pathinfo( $_FILES['ssaw_import_file']['name'], PATHINFO_EXTENSION );
        
        if ( $file_ext === 'csv' ) {
            $import_result = EM_Location_Manager::import_ssaw_warehouses_csv( $file_content );
        } elseif ( $file_ext === 'json' ) {
            $import_result = EM_Location_Manager::import_ssaw_warehouses_json( $file_content );
        } else {
            $import_result = new WP_Error( 'invalid_file_type', __( 'Invalid file type. Please upload a CSV or JSON file.', 'epic-marks-shipping' ) );
        }
    }
    
    if ( is_wp_error( $import_result ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $import_result->get_error_message() ) . '</p></div>';
    } elseif ( $import_result ) {
        $message = sprintf(
            __( 'Successfully imported %d warehouse(s).', 'epic-marks-shipping' ),
            $import_result['imported']
        );
        
        if ( ! empty( $import_result['errors'] ) ) {
            $message .= ' ' . __( 'Errors:', 'epic-marks-shipping' );
            $message .= '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $import_result['errors'] ) ) . '</li></ul>';
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
    }
}

// Handle delete action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['location_id'] ) && check_admin_referer( 'delete-location_' . $_GET['location_id'] ) ) {
    $location_id = sanitize_key( $_GET['location_id'] );
    $result = EM_Location_Manager::delete_location( $location_id );
    
    if ( is_wp_error( $result ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Location deleted successfully.', 'epic-marks-shipping' ) . '</p></div>';
    }
}

// Get all locations
$locations = EM_Location_Manager::get_all_locations();
?>

<div class="wrap em-locations-tab">
    <div class="em-locations-header">
        <div class="em-locations-header-left">
            <h2><?php esc_html_e( 'Shipping Locations', 'epic-marks-shipping' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Manage shipping locations for your store. Add retail stores, warehouses, and SSAW fulfillment centers.', 'epic-marks-shipping' ); ?>
            </p>
        </div>
        <div class="em-locations-header-right">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations&action=new' ) ); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e( 'Add Location', 'epic-marks-shipping' ); ?>
            </a>
        </div>
    </div>

    <?php if ( empty( $locations ) ) : ?>
        <div class="em-empty-state">
            <div class="em-empty-state-icon">
                <span class="dashicons dashicons-location"></span>
            </div>
            <h3><?php esc_html_e( 'No locations configured', 'epic-marks-shipping' ); ?></h3>
            <p><?php esc_html_e( 'Add your first location to start managing shipping from multiple fulfillment centers.', 'epic-marks-shipping' ); ?></p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations&action=new' ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Add First Location', 'epic-marks-shipping' ); ?>
                </a>
                <button type="button" class="button button-secondary button-large em-toggle-import">
                    <?php esc_html_e( 'Import SSAW Warehouses', 'epic-marks-shipping' ); ?>
                </button>
            </p>
        </div>
    <?php else : ?>
        <div class="em-locations-actions">
            <button type="button" class="button em-toggle-import">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e( 'Import SSAW Warehouses', 'epic-marks-shipping' ); ?>
            </button>
        </div>

        <table class="wp-list-table widefat fixed striped em-locations-table">
            <thead>
                <tr>
                    <th class="column-name"><?php esc_html_e( 'Name', 'epic-marks-shipping' ); ?></th>
                    <th class="column-type"><?php esc_html_e( 'Type', 'epic-marks-shipping' ); ?></th>
                    <th class="column-address"><?php esc_html_e( 'Address', 'epic-marks-shipping' ); ?></th>
                    <th class="column-group"><?php esc_html_e( 'Group', 'epic-marks-shipping' ); ?></th>
                    <th class="column-capabilities"><?php esc_html_e( 'Capabilities', 'epic-marks-shipping' ); ?></th>
                    <th class="column-status"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></th>
                    <th class="column-actions"><?php esc_html_e( 'Actions', 'epic-marks-shipping' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $locations as $location ) : ?>
                    <tr>
                        <td class="column-name">
                            <strong><?php echo esc_html( $location['name'] ?? '' ); ?></strong>
                            <?php if ( ! empty( $location['warehouse_code'] ) ) : ?>
                                <br>
                                <code class="em-warehouse-code"><?php echo esc_html( $location['warehouse_code'] ); ?></code>
                            <?php endif; ?>
                        </td>
                        <td class="column-type">
                            <span class="em-type-badge em-type-<?php echo esc_attr( $location['type'] ?? 'warehouse' ); ?>">
                                <?php echo esc_html( EM_Location_Manager::get_type_label( $location['type'] ?? 'warehouse' ) ); ?>
                            </span>
                        </td>
                        <td class="column-address">
                            <?php echo esc_html( EM_Location_Manager::format_address( $location ) ); ?>
                        </td>
                        <td class="column-group">
                            <?php echo esc_html( $location['group'] ?? '—' ); ?>
                        </td>
                        <td class="column-capabilities">
                            <?php 
                            if ( ! empty( $location['capabilities'] ) && is_array( $location['capabilities'] ) ) {
                                echo esc_html( implode( ', ', array_map( 'ucfirst', $location['capabilities'] ) ) );
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td class="column-status">
                            <?php if ( ( $location['status'] ?? 'active' ) === 'active' ) : ?>
                                <span class="em-status-badge em-status-active">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e( 'Active', 'epic-marks-shipping' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="em-status-badge em-status-inactive">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php esc_html_e( 'Inactive', 'epic-marks-shipping' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations&action=edit&location_id=' . urlencode( $location['id'] ) ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit', 'epic-marks-shipping' ); ?>
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=em-ups-shipping&tab=locations&action=delete&location_id=' . urlencode( $location['id'] ) ), 'delete-location_' . $location['id'] ) ); ?>" class="button button-small button-link-delete em-delete-location" data-location-name="<?php echo esc_attr( $location['name'] ?? '' ); ?>">
                                <?php esc_html_e( 'Delete', 'epic-marks-shipping' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- SSAW Import Panel -->
    <div class="em-import-panel" style="display: none;">
        <h3><?php esc_html_e( 'Import SSAW Warehouses', 'epic-marks-shipping' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Import multiple SSAW warehouse locations from a CSV or JSON file.', 'epic-marks-shipping' ); ?>
        </p>
        
        <form method="post" enctype="multipart/form-data" class="em-import-form">
            <?php wp_nonce_field( 'em_import_ssaw_warehouses' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ssaw_import_file"><?php esc_html_e( 'Import File', 'epic-marks-shipping' ); ?></label>
                    </th>
                    <td>
                        <input type="file" id="ssaw_import_file" name="ssaw_import_file" accept=".csv,.json" required>
                        <p class="description">
                            <?php esc_html_e( 'Upload a CSV or JSON file containing SSAW warehouse data.', 'epic-marks-shipping' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div class="em-import-format-help">
                <h4><?php esc_html_e( 'CSV Format', 'epic-marks-shipping' ); ?></h4>
                <pre>warehouse_code,name,company,address_1,city,state,zip,country
TX,Texas Warehouse,SS Activewear,123 Main St,Dallas,TX,75201,US
IL,Illinois Warehouse,SS Activewear,456 Oak Ave,Chicago,IL,60601,US</pre>
                
                <h4><?php esc_html_e( 'JSON Format', 'epic-marks-shipping' ); ?></h4>
                <pre>{
  "warehouses": [
    {
      "warehouse_code": "TX",
      "name": "Texas Warehouse",
      "address": {
        "company": "SS Activewear",
        "address_1": "123 Main St",
        "city": "Dallas",
        "state": "TX",
        "zip": "75201",
        "country": "US"
      }
    }
  ]
}</pre>
            </div>
            
            <p class="submit">
                <button type="submit" name="em_import_ssaw_warehouses" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e( 'Import Warehouses', 'epic-marks-shipping' ); ?>
                </button>
                <button type="button" class="button em-cancel-import">
                    <?php esc_html_e( 'Cancel', 'epic-marks-shipping' ); ?>
                </button>
            </p>
        </form>
    </div>
</div>
