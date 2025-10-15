<?php
/**
 * Cost Analysis Report
 *
 * Compares estimated shipping costs vs actual PirateShip label costs
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get date range from request
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-d' );

// Get report data
$data = EM_Shipping_Reports::get_cost_analysis( $start_date, $end_date );

// Check if PirateShip is active
$pirateship_active = EM_Shipping_Reports::is_pirateship_active();
?>

<div class="em-report-container">
	<h2>Shipping Cost Analysis</h2>
	<p class="description">
		Compare estimated shipping costs (what customers paid) vs actual label costs (what you paid PirateShip).
	</p>

	<?php if ( ! $pirateship_active ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong>PirateShip plugin not detected.</strong>
				Install <a href="https://wordpress.org/plugins/pirateship-for-woocommerce/" target="_blank">PirateShip for WooCommerce</a>
				to enable full cost tracking.
			</p>
		</div>
	<?php endif; ?>

	<!-- Summary Stats -->
	<div class="em-report-stats">
		<div class="em-stat-box">
			<h3><?php echo number_format( $data['total_orders'] ); ?></h3>
			<p>Total Orders</p>
		</div>

		<div class="em-stat-box">
			<h3><?php echo number_format( $data['labels_purchased'] ); ?></h3>
			<p>Labels Purchased</p>
		</div>

		<div class="em-stat-box">
			<h3>$<?php echo number_format( $data['estimated_total'], 2 ); ?></h3>
			<p>Customer Paid (Estimated)</p>
		</div>

		<div class="em-stat-box">
			<h3>$<?php echo number_format( $data['actual_total'], 2 ); ?></h3>
			<p>You Paid (Actual)</p>
		</div>

		<div class="em-stat-box <?php echo $data['savings'] > 0 ? 'positive' : 'negative'; ?>">
			<h3>$<?php echo number_format( $data['savings'], 2 ); ?></h3>
			<p><?php echo $data['savings'] > 0 ? 'Savings' : 'Loss'; ?></p>
		</div>

		<div class="em-stat-box">
			<h3>$<?php echo number_format( $data['avg_label_cost'], 2 ); ?></h3>
			<p>Avg Label Cost</p>
		</div>
	</div>

	<!-- Chart -->
	<?php if ( $data['labels_purchased'] > 0 ) : ?>
		<div class="em-chart-container">
			<canvas id="costAnalysisChart" width="400" height="200"></canvas>
		</div>

		<script>
		var costData = {
			estimated: <?php echo json_encode( $data['estimated_total'] ); ?>,
			actual: <?php echo json_encode( $data['actual_total'] ); ?>,
			savings: <?php echo json_encode( $data['savings'] ); ?>
		};
		</script>
	<?php else : ?>
		<div class="notice notice-info">
			<p>No label costs available for this date range. Purchase labels via PirateShip to see cost analysis.</p>
		</div>
	<?php endif; ?>

	<!-- Export Button -->
	<div class="em-report-actions">
		<a href="<?php echo admin_url( 'admin.php?page=em-ups-shipping&tab=reports&report=cost-analysis&export=csv&start_date=' . $start_date . '&end_date=' . $end_date ); ?>" 
		   class="button button-secondary">
			Export to CSV
		</a>
	</div>

	<!-- Detailed Table -->
	<?php if ( ! empty( $data['orders'] ) ) : ?>
		<h3>Order Details</h3>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Order #</th>
					<th>Date</th>
					<th>Customer</th>
					<th>Shipping Method</th>
					<th>Estimated Cost</th>
					<th>Actual Cost</th>
					<th>Difference</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['orders'] as $order ) : ?>
					<?php
					$estimated = floatval( $order->get_shipping_total() );
					$actual = floatval( $order->get_meta( '_pirateship_label_cost', true ) );
					$diff = $estimated - $actual;
					?>
					<tr>
						<td>
							<a href="<?php echo $order->get_edit_order_url(); ?>">
								#<?php echo $order->get_order_number(); ?>
							</a>
						</td>
						<td><?php echo $order->get_date_created()->format( 'Y-m-d' ); ?></td>
						<td><?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?></td>
						<td><?php echo $order->get_shipping_method(); ?></td>
						<td>$<?php echo number_format( $estimated, 2 ); ?></td>
						<td><?php echo $actual > 0 ? '$' . number_format( $actual, 2 ) : '—'; ?></td>
						<td class="<?php echo $diff > 0 ? 'positive' : ( $diff < 0 ? 'negative' : '' ); ?>">
							<?php echo $actual > 0 ? '$' . number_format( $diff, 2 ) : '—'; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p>No orders found for the selected date range.</p>
	<?php endif; ?>
</div>

<style>
.em-report-stats {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 15px;
	margin: 20px 0;
}

.em-stat-box {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	text-align: center;
	border-radius: 4px;
}

.em-stat-box h3 {
	margin: 0 0 5px 0;
	font-size: 28px;
	color: #23282d;
}

.em-stat-box p {
	margin: 0;
	color: #646970;
	font-size: 13px;
}

.em-stat-box.positive h3 {
	color: #00a32a;
}

.em-stat-box.negative h3 {
	color: #d63638;
}

.em-chart-container {
	background: #fff;
	padding: 20px;
	margin: 20px 0;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
}

.em-report-actions {
	margin: 20px 0;
}

table td.positive {
	color: #00a32a;
	font-weight: 600;
}

table td.negative {
	color: #d63638;
	font-weight: 600;
}
</style>
