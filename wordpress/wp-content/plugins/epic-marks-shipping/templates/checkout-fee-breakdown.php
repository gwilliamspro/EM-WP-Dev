<?php
/**
 * Checkout Fee Breakdown Template
 *
 * Displays transparent shipping fees at checkout as separate line items.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/templates
 * @since      2.3.0
 * @var array $fees Array of fee objects from EM_Shipping_Fee_Calculator::calculate_fees()
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Bail if no fees provided
if ( empty( $fees ) ) {
	return;
}

// Filter only transparent fees
$transparent_fees = array_filter( $fees, function( $fee ) {
	return isset( $fee['type'] ) && 'transparent' === $fee['type'];
});

if ( empty( $transparent_fees ) ) {
	return;
}
?>

<div class="em-fee-breakdown">
	<?php foreach ( $transparent_fees as $fee ) : ?>
		<div class="em-fee-item">
			<span class="em-fee-label">
				+ <?php echo esc_html( $fee['label'] ); ?>
				<?php if ( ! empty( $fee['info'] ) ) : ?>
					<span class="em-fee-info" title="<?php echo esc_attr( $fee['info'] ); ?>">ⓘ</span>
				<?php endif; ?>
			</span>
			<span class="em-fee-cost">$<?php echo number_format( $fee['cost'], 2 ); ?></span>
		</div>
	<?php endforeach; ?>
</div>

<style>
.em-fee-breakdown {
	margin-top: 8px;
	padding-top: 8px;
	border-top: 1px dashed #ddd;
	font-size: 0.9em;
}

.em-fee-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 4px;
	color: #555;
}

.em-fee-label {
	flex: 1;
	display: flex;
	align-items: center;
	gap: 5px;
}

.em-fee-info {
	display: inline-block;
	width: 14px;
	height: 14px;
	border-radius: 50%;
	background: #999;
	color: #fff;
	font-size: 11px;
	line-height: 14px;
	text-align: center;
	cursor: help;
	font-style: normal;
}

.em-fee-info:hover {
	background: #666;
}

.em-fee-cost {
	font-weight: 500;
	color: #333;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.em-fee-breakdown {
		font-size: 0.85em;
	}
	
	.em-fee-item {
		margin-bottom: 6px;
	}
}
</style>
