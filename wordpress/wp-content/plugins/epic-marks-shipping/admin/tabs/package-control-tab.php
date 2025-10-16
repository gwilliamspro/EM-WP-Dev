<?php
/**
 * Package Control Tab
 *
 * Box management UI for dimensional weight and packaging configuration
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/admin/tabs
 * @since      2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle delete action
if ( isset( $_POST['action'] ) && $_POST['action'] === 'delete' && isset( $_POST['box_id'] ) ) {
    check_admin_referer( 'em_delete_box' );
    EM_Box_Manager::delete_box( sanitize_text_field( $_POST['box_id'] ) );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Box deleted successfully.', 'epic-marks-shipping' ) . '</p></div>';
}

// Handle reset to defaults
if ( isset( $_POST['action'] ) && $_POST['action'] === 'reset_defaults' ) {
    check_admin_referer( 'em_reset_boxes' );
    $default_boxes = EM_Box_Manager::get_default_boxes();
    update_option( 'em_shipping_boxes', $default_boxes );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Boxes reset to defaults successfully.', 'epic-marks-shipping' ) . '</p></div>';
}

$boxes = EM_Box_Manager::get_boxes();
?>

<div class="em-shipping-tab-content">
    <h2><?php esc_html_e( 'Box & Package Configuration', 'epic-marks-shipping' ); ?></h2>
    
    <p class="description">
        <?php esc_html_e( 'Define box sizes for accurate dimensional weight calculations. Dimensional weight is calculated as (Length × Width × Height) / 166. UPS charges based on the greater of actual weight or dimensional weight.', 'epic-marks-shipping' ); ?>
    </p>

    <div class="em-boxes-header" style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=package_control&action=new' ) ); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                <?php esc_html_e( 'Add New Box', 'epic-marks-shipping' ); ?>
            </a>
        </div>
        <div>
            <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to reset all boxes to defaults? This will delete all custom boxes.', 'epic-marks-shipping' ) ); ?>');">
                <?php wp_nonce_field( 'em_reset_boxes' ); ?>
                <input type="hidden" name="action" value="reset_defaults">
                <button type="submit" class="button button-secondary">
                    <span class="dashicons dashicons-image-rotate" style="margin-top: 3px;"></span>
                    <?php esc_html_e( 'Reset to Defaults', 'epic-marks-shipping' ); ?>
                </button>
            </form>
        </div>
    </div>

    <?php if ( empty( $boxes ) ) : ?>
        <div class="em-empty-state" style="text-align: center; padding: 60px 20px; background: #f9f9f9; border: 1px dashed #ddd; border-radius: 4px;">
            <span class="dashicons dashicons-archive" style="font-size: 64px; color: #ccc; width: 64px; height: 64px;"></span>
            <h3><?php esc_html_e( 'No Boxes Defined', 'epic-marks-shipping' ); ?></h3>
            <p><?php esc_html_e( 'Add your first box to start calculating dimensional weight for accurate shipping rates.', 'epic-marks-shipping' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=package_control&action=new' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Add Your First Box', 'epic-marks-shipping' ); ?>
            </a>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th style="width: 20%;"><?php esc_html_e( 'Box Name', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Type', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 20%;"><?php esc_html_e( 'Outer Dimensions (L×W×H)', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Max Weight', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Dim Weight', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Cost', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></th>
                    <th style="width: 10%;"><?php esc_html_e( 'Actions', 'epic-marks-shipping' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $boxes as $box ) : 
                    $dim_weight = EM_Box_Manager::calculate_dim_weight( $box );
                    $status = isset( $box['status'] ) ? $box['status'] : 'active';
                    $status_class = $status === 'active' ? 'em-badge-success' : 'em-badge-secondary';
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $box['name'] ); ?></strong>
                            <?php if ( ! empty( $box['typical_use'] ) ) : ?>
                                <br><span class="description"><?php echo esc_html( $box['typical_use'] ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $types = EM_Box_Manager::get_box_types();
                            echo esc_html( isset( $types[ $box['type'] ] ) ? $types[ $box['type'] ] : ucfirst( $box['type'] ) );
                            ?>
                        </td>
                        <td>
                            <?php 
                            echo esc_html( 
                                sprintf(
                                    '%s × %s × %s in',
                                    $box['outer_dimensions']['length'],
                                    $box['outer_dimensions']['width'],
                                    $box['outer_dimensions']['height']
                                )
                            );
                            ?>
                            <br>
                            <span class="description">
                                <?php echo esc_html( sprintf( __( 'Volume: %s cu in', 'epic-marks-shipping' ), number_format( EM_Box_Manager::calculate_volume( $box['outer_dimensions'] ), 0 ) ) ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( $box['max_weight'] ); ?> lbs</td>
                        <td>
                            <strong><?php echo esc_html( number_format( $dim_weight, 2 ) ); ?> lbs</strong>
                            <?php if ( $dim_weight > $box['max_weight'] ) : ?>
                                <br><span class="description" style="color: #d63638;">
                                    <?php esc_html_e( '⚠ Exceeds max weight', 'epic-marks-shipping' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>$<?php echo esc_html( number_format( $box['cost'], 2 ) ); ?></td>
                        <td>
                            <span class="em-badge <?php echo esc_attr( $status_class ); ?>">
                                <?php echo esc_html( ucfirst( $status ) ); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=package_control&action=edit&box_id=' . urlencode( $box['id'] ) ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit', 'epic-marks-shipping' ); ?>
                            </a>
                            <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this box?', 'epic-marks-shipping' ) ); ?>');">
                                <?php wp_nonce_field( 'em_delete_box' ); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="box_id" value="<?php echo esc_attr( $box['id'] ); ?>">
                                <button type="submit" class="button button-small button-link-delete" style="color: #b32d2e;">
                                    <?php esc_html_e( 'Delete', 'epic-marks-shipping' ); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="em-box-info" style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0071a1; border-radius: 4px;">
            <h4 style="margin-top: 0;"><?php esc_html_e( 'Understanding Dimensional Weight', 'epic-marks-shipping' ); ?></h4>
            <p><?php esc_html_e( 'UPS charges based on the GREATER of actual weight or dimensional weight. This is critical for light but bulky items like apparel in large boxes.', 'epic-marks-shipping' ); ?></p>
            
            <h5><?php esc_html_e( 'Example:', 'epic-marks-shipping' ); ?></h5>
            <ul>
                <li><?php esc_html_e( 'Large Box (18.5 × 14.5 × 8.5 in) has dimensional weight of 13.73 lbs', 'epic-marks-shipping' ); ?></li>
                <li><?php esc_html_e( '10 shirts weigh 5 lbs actual weight', 'epic-marks-shipping' ); ?></li>
                <li><strong><?php esc_html_e( 'UPS charges for 13.73 lbs, not 5 lbs', 'epic-marks-shipping' ); ?></strong></li>
                <li><?php esc_html_e( 'Without dim weight calculation, you would lose $13 on this shipment!', 'epic-marks-shipping' ); ?></li>
            </ul>

            <p>
                <strong><?php esc_html_e( 'Formula:', 'epic-marks-shipping' ); ?></strong>
                <?php esc_html_e( 'Dimensional Weight = (Length × Width × Height) / 166', 'epic-marks-shipping' ); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.em-badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
}
.em-badge-success {
    background: #d4edda;
    color: #155724;
}
.em-badge-secondary {
    background: #e2e3e5;
    color: #383d41;
}
</style>
