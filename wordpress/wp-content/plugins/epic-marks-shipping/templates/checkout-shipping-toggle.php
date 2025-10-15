<?php
/**
 * Checkout Shipping Mode Toggle Template
 *
 * Displays toggle for customer to choose between "Ship All" and "Partial Pickup" modes
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current shipping mode from session
$current_mode = WC()->session->get( 'em_shipping_mode', 'ship_all' );
?>

<div class="em-shipping-mode-toggle">
    <h3><?php esc_html_e( 'Shipping Options', 'epic-marks-shipping' ); ?></h3>
    <p class="em-shipping-notice">
        <?php esc_html_e( 'Your order contains items from multiple locations. Choose your preferred shipping method:', 'epic-marks-shipping' ); ?>
    </p>

    <div class="em-shipping-mode-options">
        <label class="em-shipping-mode-option <?php echo $current_mode === 'ship_all' ? 'selected' : ''; ?>">
            <input 
                type="radio" 
                name="em_shipping_mode" 
                value="ship_all" 
                <?php checked( $current_mode, 'ship_all' ); ?>
            />
            <span class="em-mode-label">
                <strong><?php esc_html_e( 'Ship Everything to Me', 'epic-marks-shipping' ); ?></strong>
                <span class="em-mode-description">
                    <?php esc_html_e( 'All items shipped to your address', 'epic-marks-shipping' ); ?>
                </span>
            </span>
        </label>

        <label class="em-shipping-mode-option <?php echo $current_mode === 'partial_pickup' ? 'selected' : ''; ?>">
            <input 
                type="radio" 
                name="em_shipping_mode" 
                value="partial_pickup" 
                <?php checked( $current_mode, 'partial_pickup' ); ?>
            />
            <span class="em-mode-label">
                <strong><?php esc_html_e( 'Partial In-Store Pickup', 'epic-marks-shipping' ); ?></strong>
                <span class="em-mode-description">
                    <?php esc_html_e( 'Pick up available items in store, ship the rest', 'epic-marks-shipping' ); ?>
                </span>
            </span>
        </label>
    </div>

    <div class="em-shipping-loading" style="display:none;">
        <span class="spinner is-active"></span>
        <?php esc_html_e( 'Updating shipping options...', 'epic-marks-shipping' ); ?>
    </div>
</div>
