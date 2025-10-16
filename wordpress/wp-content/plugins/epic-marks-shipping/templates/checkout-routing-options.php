<?php
/**
 * Checkout Routing Options Template
 *
 * Displays ship-together vs split shipment options at checkout
 * Allows customer to choose preferred routing option
 *
 * @package EpicMarksShipping
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract variables
$routing_data = isset( $args['routing_data'] ) ? $args['routing_data'] : array();
$options = isset( $routing_data['options'] ) ? $routing_data['options'] : array();

if ( empty( $options ) ) {
	return; // No routing options to display
}

// Find recommended option
$recommended_key = '';
foreach ( $options as $key => $option ) {
	if ( isset( $option['recommended'] ) && $option['recommended'] ) {
		$recommended_key = $key;
		break;
	}
}

// Default to first option if no recommendation
if ( empty( $recommended_key ) ) {
	$recommended_key = key( $options );
}

?>

<div class="em-shipping-routing-options" id="em-routing-options">
	<h3><?php esc_html_e( 'Shipping Options', 'epic-marks-shipping' ); ?></h3>
	<p class="em-routing-description">
		<?php esc_html_e( 'Your cart contains items from multiple locations. Choose how you would like your order shipped:', 'epic-marks-shipping' ); ?>
	</p>

	<div class="em-routing-options-list">
		<?php foreach ( $options as $option_key => $option ) : ?>
			<?php
			$is_recommended = ( $option_key === $recommended_key );
			$is_selected = $is_recommended; // Pre-select recommended option
			$option_id = 'em_routing_' . esc_attr( $option_key );
			?>

			<div class="em-routing-option <?php echo $is_selected ? 'selected' : ''; ?>" data-option="<?php echo esc_attr( $option_key ); ?>">
				<label for="<?php echo esc_attr( $option_id ); ?>" class="em-routing-option-label">
					<input 
						type="radio" 
						name="em_routing_option" 
						id="<?php echo esc_attr( $option_id ); ?>" 
						value="<?php echo esc_attr( $option_key ); ?>"
						<?php checked( $is_selected, true ); ?>
						class="em-routing-radio"
					/>
					
					<span class="em-routing-option-content">
						<span class="em-routing-option-header">
							<span class="em-routing-option-title">
								<?php echo esc_html( $option['label'] ); ?>
								<?php if ( $is_recommended ) : ?>
									<span class="em-routing-recommended-badge">
										⭐ <?php esc_html_e( 'Recommended', 'epic-marks-shipping' ); ?>
									</span>
								<?php endif; ?>
							</span>
							<span class="em-routing-option-cost">
								$<?php echo number_format( $option['cost'], 2 ); ?>
							</span>
						</span>

						<?php if ( ! empty( $option['description'] ) ) : ?>
							<span class="em-routing-option-description">
								<?php echo esc_html( $option['description'] ); ?>
							</span>
						<?php endif; ?>

						<?php if ( isset( $option['estimated_delivery'] ) && ! empty( $option['estimated_delivery'] ) ) : ?>
							<span class="em-routing-option-delivery">
								<?php
								/* translators: %s: Estimated delivery date */
								printf( esc_html__( 'Estimated delivery: %s', 'epic-marks-shipping' ), esc_html( $option['estimated_delivery'] ) );
								?>
							</span>
						<?php endif; ?>

						<?php
						// Display package breakdown for split shipment
						if ( $option_key === 'split' && isset( $option['package_breakdown'] ) && ! empty( $option['package_breakdown'] ) ) :
						?>
							<div class="em-routing-option-breakdown">
								<ul class="em-package-breakdown-list">
									<?php foreach ( $option['package_breakdown'] as $breakdown ) : ?>
										<li>
											<?php echo esc_html( $breakdown['description'] ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</span>
				</label>
			</div>

		<?php endforeach; ?>
	</div>

	<?php
	// Show savings indicator if there's a cost difference
	$costs = wp_list_pluck( $options, 'cost' );
	$min_cost = min( $costs );
	$max_cost = max( $costs );
	$savings = $max_cost - $min_cost;

	if ( $savings > 0 ) :
	?>
		<p class="em-routing-savings">
			<?php
			/* translators: %s: Savings amount */
			printf( esc_html__( 'Save $%s by choosing the recommended option', 'epic-marks-shipping' ), number_format( $savings, 2 ) );
			?>
		</p>
	<?php endif; ?>

	<div class="em-routing-loading" style="display:none;">
		<span class="spinner"></span>
		<?php esc_html_e( 'Updating shipping rates...', 'epic-marks-shipping' ); ?>
	</div>
</div>
