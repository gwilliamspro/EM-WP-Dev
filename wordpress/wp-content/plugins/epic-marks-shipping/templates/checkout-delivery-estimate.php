<?php
/**
 * Checkout Delivery Estimate Template
 *
 * Displays estimated delivery dates at checkout for shipping methods.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/templates
 * @since      2.3.0
 * @var array $estimate Delivery estimate data from EM_Delivery_Estimator::estimate()
 * @var array $location Location configuration array
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Bail if no estimate provided
if ( empty( $estimate ) || empty( $estimate['delivery_range'] ) ) {
	return;
}
?>

<div class="em-delivery-estimate">
	<div class="em-delivery-date">
		<span class="em-delivery-label"><?php echo esc_html( EM_Delivery_Estimator::format_estimate( $estimate ) ); ?></span>
	</div>
	
	<?php if ( ! empty( $location ) ) : ?>
		<?php $cutoff_message = EM_Delivery_Estimator::get_cutoff_message( $location ); ?>
		<?php if ( ! empty( $cutoff_message ) ) : ?>
			<div class="em-cutoff-info">
				<span class="em-cutoff-text">(<?php echo esc_html( $cutoff_message ); ?>)</span>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<style>
.em-delivery-estimate {
	font-size: 0.9em;
	color: #555;
	margin-top: 5px;
	line-height: 1.4;
}

.em-delivery-date {
	margin-bottom: 3px;
}

.em-delivery-label {
	font-weight: 500;
}

.em-cutoff-info {
	font-size: 0.95em;
	color: #666;
}

.em-cutoff-text {
	font-style: italic;
}
</style>
