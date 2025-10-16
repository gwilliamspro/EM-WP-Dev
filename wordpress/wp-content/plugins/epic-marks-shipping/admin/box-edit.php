<?php
/**
 * Box Edit Form
 *
 * Create/edit form for box definitions
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/admin
 * @since      2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get box ID from URL (empty for new box)
$box_id = isset( $_GET['box_id'] ) ? sanitize_text_field( $_GET['box_id'] ) : '';
$is_new = empty( $box_id );

// Load existing box or create empty template
if ( ! $is_new ) {
    $box = EM_Box_Manager::get_box( $box_id );
    if ( ! $box ) {
        wp_die( esc_html__( 'Box not found.', 'epic-marks-shipping' ) );
    }
} else {
    $box = array(
        'id'                => '',
        'name'              => '',
        'type'              => 'box',
        'inner_dimensions'  => array( 'length' => '', 'width' => '', 'height' => '' ),
        'outer_dimensions'  => array( 'length' => '', 'width' => '', 'height' => '' ),
        'max_weight'        => '',
        'cost'              => '',
        'typical_use'       => '',
        'status'            => 'active',
    );
}

// Handle form submission
if ( isset( $_POST['em_save_box'] ) ) {
    check_admin_referer( 'em_save_box' );

    $box_data = array(
        'id'                => $is_new ? EM_Box_Manager::generate_box_id( sanitize_text_field( $_POST['box_name'] ) ) : $box_id,
        'name'              => sanitize_text_field( $_POST['box_name'] ),
        'type'              => sanitize_text_field( $_POST['box_type'] ),
        'inner_dimensions'  => array(
            'length' => floatval( $_POST['inner_length'] ),
            'width'  => floatval( $_POST['inner_width'] ),
            'height' => floatval( $_POST['inner_height'] ),
        ),
        'outer_dimensions'  => array(
            'length' => floatval( $_POST['outer_length'] ),
            'width'  => floatval( $_POST['outer_width'] ),
            'height' => floatval( $_POST['outer_height'] ),
        ),
        'max_weight'        => floatval( $_POST['max_weight'] ),
        'cost'              => floatval( $_POST['cost'] ),
        'typical_use'       => sanitize_text_field( $_POST['typical_use'] ),
        'status'            => sanitize_text_field( $_POST['status'] ),
    );

    $result = EM_Box_Manager::save_box( $box_data );

    if ( is_wp_error( $result ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Box saved successfully.', 'epic-marks-shipping' ) . '</p></div>';
        $box = $box_data;
        $is_new = false;
        $box_id = $box_data['id'];
    }
}

$dim_weight = ! empty( $box['outer_dimensions']['length'] ) ? EM_Box_Manager::calculate_dim_weight( $box ) : 0;
?>

<div class="wrap">
    <h1>
        <?php echo $is_new ? esc_html__( 'Add New Box', 'epic-marks-shipping' ) : esc_html__( 'Edit Box', 'epic-marks-shipping' ); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=package_control' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Back to Boxes', 'epic-marks-shipping' ); ?>
        </a>
    </h1>

    <form method="post" id="em-box-form" style="max-width: 800px;">
        <?php wp_nonce_field( 'em_save_box' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="box_name"><?php esc_html_e( 'Box Name', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" name="box_name" id="box_name" value="<?php echo esc_attr( $box['name'] ); ?>" class="regular-text" required>
                    <p class="description"><?php esc_html_e( 'A descriptive name for this box (e.g., "Small Box", "DTF Tube")', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="box_type"><?php esc_html_e( 'Box Type', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select name="box_type" id="box_type" required>
                        <?php foreach ( EM_Box_Manager::get_box_types() as $type_key => $type_label ) : ?>
                            <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $box['type'], $type_key ); ?>>
                                <?php echo esc_html( $type_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e( 'The type of packaging material', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'Inner Dimensions', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Inner Dimensions', 'epic-marks-shipping' ); ?></legend>
                        <label>
                            <?php esc_html_e( 'Length', 'epic-marks-shipping' ); ?>
                            <input type="number" name="inner_length" value="<?php echo esc_attr( $box['inner_dimensions']['length'] ); ?>" step="0.01" min="0" style="width: 80px;">
                            in
                        </label>
                        &nbsp;×&nbsp;
                        <label>
                            <?php esc_html_e( 'Width', 'epic-marks-shipping' ); ?>
                            <input type="number" name="inner_width" value="<?php echo esc_attr( $box['inner_dimensions']['width'] ); ?>" step="0.01" min="0" style="width: 80px;">
                            in
                        </label>
                        &nbsp;×&nbsp;
                        <label>
                            <?php esc_html_e( 'Height', 'epic-marks-shipping' ); ?>
                            <input type="number" name="inner_height" value="<?php echo esc_attr( $box['inner_dimensions']['height'] ); ?>" step="0.01" min="0" style="width: 80px;">
                            in
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'Interior dimensions available for products (optional)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e( 'Outer Dimensions', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Outer Dimensions', 'epic-marks-shipping' ); ?></legend>
                        <label>
                            <?php esc_html_e( 'Length', 'epic-marks-shipping' ); ?>
                            <input type="number" name="outer_length" id="outer_length" value="<?php echo esc_attr( $box['outer_dimensions']['length'] ); ?>" step="0.01" min="0" style="width: 80px;" required>
                            in
                        </label>
                        &nbsp;×&nbsp;
                        <label>
                            <?php esc_html_e( 'Width', 'epic-marks-shipping' ); ?>
                            <input type="number" name="outer_width" id="outer_width" value="<?php echo esc_attr( $box['outer_dimensions']['width'] ); ?>" step="0.01" min="0" style="width: 80px;" required>
                            in
                        </label>
                        &nbsp;×&nbsp;
                        <label>
                            <?php esc_html_e( 'Height', 'epic-marks-shipping' ); ?>
                            <input type="number" name="outer_height" id="outer_height" value="<?php echo esc_attr( $box['outer_dimensions']['height'] ); ?>" step="0.01" min="0" style="width: 80px;" required>
                            in
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'External dimensions used for dimensional weight calculation (required)', 'epic-marks-shipping' ); ?></p>
                    
                    <div id="dim-weight-display" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #0071a1; display: <?php echo $dim_weight > 0 ? 'block' : 'none'; ?>;">
                        <strong><?php esc_html_e( 'Calculated Dimensional Weight:', 'epic-marks-shipping' ); ?></strong>
                        <span id="dim-weight-value"><?php echo esc_html( number_format( $dim_weight, 2 ) ); ?></span> lbs
                        <br>
                        <small><?php esc_html_e( 'Formula: (L × W × H) / 166', 'epic-marks-shipping' ); ?></small>
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="max_weight"><?php esc_html_e( 'Maximum Weight', 'epic-marks-shipping' ); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="number" name="max_weight" id="max_weight" value="<?php echo esc_attr( $box['max_weight'] ); ?>" step="0.01" min="0" style="width: 100px;" required>
                    lbs
                    <p class="description"><?php esc_html_e( 'Maximum weight this box can hold', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="cost"><?php esc_html_e( 'Box Cost', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    $<input type="number" name="cost" id="cost" value="<?php echo esc_attr( $box['cost'] ); ?>" step="0.01" min="0" style="width: 100px;">
                    <p class="description"><?php esc_html_e( 'Cost of the box material (for internal tracking, not charged to customer)', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="typical_use"><?php esc_html_e( 'Typical Use', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <input type="text" name="typical_use" id="typical_use" value="<?php echo esc_attr( $box['typical_use'] ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'Description of what this box is typically used for (e.g., "1-3 shirts")', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="status"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></label>
                </th>
                <td>
                    <select name="status" id="status">
                        <option value="active" <?php selected( $box['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'epic-marks-shipping' ); ?></option>
                        <option value="inactive" <?php selected( $box['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'epic-marks-shipping' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Inactive boxes will not be used for new shipments', 'epic-marks-shipping' ); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="em_save_box" class="button button-primary">
                <?php echo $is_new ? esc_html__( 'Add Box', 'epic-marks-shipping' ) : esc_html__( 'Update Box', 'epic-marks-shipping' ); ?>
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=package_control' ) ); ?>" class="button">
                <?php esc_html_e( 'Cancel', 'epic-marks-shipping' ); ?>
            </a>
        </p>
    </form>

    <div class="em-box-help" style="max-width: 800px; margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
        <h3><?php esc_html_e( 'Box Configuration Tips', 'epic-marks-shipping' ); ?></h3>
        
        <h4><?php esc_html_e( 'Why Outer Dimensions Matter', 'epic-marks-shipping' ); ?></h4>
        <p><?php esc_html_e( 'UPS calculates dimensional weight using outer dimensions. A lightweight item in a large box can be charged as if it were much heavier. Always use the smallest box that fits to minimize shipping costs.', 'epic-marks-shipping' ); ?></p>

        <h4><?php esc_html_e( 'Example Dimensional Weight Impact', 'epic-marks-shipping' ); ?></h4>
        <ul>
            <li><strong><?php esc_html_e( 'Small Box (12.5 × 9.5 × 4.5):', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( '3.22 lbs dim weight', 'epic-marks-shipping' ); ?></li>
            <li><strong><?php esc_html_e( 'Medium Box (16.5 × 12.5 × 6.5):', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( '8.08 lbs dim weight', 'epic-marks-shipping' ); ?></li>
            <li><strong><?php esc_html_e( 'Large Box (18.5 × 14.5 × 8.5):', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( '13.73 lbs dim weight', 'epic-marks-shipping' ); ?></li>
        </ul>
        <p><?php esc_html_e( 'If 10 shirts (5 lbs actual) ship in a Large Box, UPS charges for 13.73 lbs - nearly 3× the actual weight!', 'epic-marks-shipping' ); ?></p>

        <h4><?php esc_html_e( 'Box Types', 'epic-marks-shipping' ); ?></h4>
        <ul>
            <li><strong><?php esc_html_e( 'Padded Envelope:', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( 'For single small items like one shirt', 'epic-marks-shipping' ); ?></li>
            <li><strong><?php esc_html_e( 'Box:', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( 'Standard cardboard boxes for most shipments', 'epic-marks-shipping' ); ?></li>
            <li><strong><?php esc_html_e( 'Tube:', 'epic-marks-shipping' ); ?></strong> <?php esc_html_e( 'For DTF rolls and items that cannot be folded', 'epic-marks-shipping' ); ?></li>
        </ul>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Calculate dimensional weight on dimension change
    function updateDimWeight() {
        var length = parseFloat($('#outer_length').val()) || 0;
        var width = parseFloat($('#outer_width').val()) || 0;
        var height = parseFloat($('#outer_height').val()) || 0;

        if (length > 0 && width > 0 && height > 0) {
            var dimWeight = (length * width * height) / 166;
            $('#dim-weight-value').text(dimWeight.toFixed(2));
            $('#dim-weight-display').show();
        } else {
            $('#dim-weight-display').hide();
        }
    }

    $('#outer_length, #outer_width, #outer_height').on('input', updateDimWeight);
});
</script>

<style>
.required {
    color: #d63638;
}
</style>
