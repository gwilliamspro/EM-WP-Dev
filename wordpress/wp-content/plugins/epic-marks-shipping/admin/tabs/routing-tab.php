<?php
/**
 * Routing Tab - Tag Automation & Bulk Assignment
 *
 * @package Epic_Marks_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get all profiles for display
$profiles = EM_Shipping_Profile::get_all();
?>

<div class="em-routing-tab wrap">
    <h2><?php esc_html_e( 'Product Routing & Automation', 'epic-marks-shipping' ); ?></h2>
    <p class="description">
        <?php esc_html_e( 'Automate product-to-profile assignments using product tags. This is useful for bulk operations and initial migration.', 'epic-marks-shipping' ); ?>
    </p>

    <?php
    // Render bulk assignment section (from bulk-assignment.php)
    em_render_bulk_assignment_section();
    ?>

    <hr style="margin: 40px 0;">

    <div class="em-routing-section">
        <h3><?php esc_html_e( 'Common Automation Rules', 'epic-marks-shipping' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Quick shortcuts for common tag-to-profile mappings', 'epic-marks-shipping' ); ?>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Product Tag', 'epic-marks-shipping' ); ?></th>
                    <th><?php esc_html_e( 'Profile', 'epic-marks-shipping' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'epic-marks-shipping' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>SSAW-App</code></td>
                    <td><strong>SSAW Products</strong></td>
                    <td><?php esc_html_e( 'Products from dropship supplier', 'epic-marks-shipping' ); ?></td>
                </tr>
                <tr>
                    <td><code>available-in-store</code></td>
                    <td><strong>General</strong> (or custom profile)</td>
                    <td><?php esc_html_e( 'Products available for in-store pickup', 'epic-marks-shipping' ); ?></td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top: 15px;">
            <strong><?php esc_html_e( 'How to use:', 'epic-marks-shipping' ); ?></strong>
            <?php esc_html_e( 'Select the tag and profile above, then click "Start Bulk Assignment" to automatically assign matching products.', 'epic-marks-shipping' ); ?>
        </p>
    </div>
</div>

<style>
.em-routing-tab h2 {
    margin-bottom: 10px;
}

.em-routing-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.em-routing-section h3 {
    margin-top: 0;
}

.em-bulk-assignment-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.em-bulk-assignment-section h3 {
    margin-top: 0;
}

.em-progress-bar {
    position: relative;
}
</style>
