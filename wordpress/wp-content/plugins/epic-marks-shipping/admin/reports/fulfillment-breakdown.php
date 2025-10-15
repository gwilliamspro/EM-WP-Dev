<?php
/**
 * Fulfillment Breakdown Report
 *
 * Shows warehouse vs store fulfillment statistics
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
$data = EM_Shipping_Reports::get_fulfillment_breakdown( $start_date, $end_date );

$total_orders = $data['total_orders'];
$warehouse_pct = $total_orders > 0 ? ( $data['warehouse_count'] / $total_orders ) * 100 : 0;
$store_pct = $total_orders > 0 ? ( $data['store_count'] / $total_orders ) * 100 : 0;
$unknown_pct = $total_orders > 0 ? ( $data['unknown_count'] / $total_orders ) * 100 : 0;
?>

<div class="em-report-container">
	<h2>Fulfillment Location Breakdown</h2>
	<p class="description">
		Analyze order distribution between warehouse shipping, store pickup, and ship-to-store options.
	</p>

	<!-- Summary Stats -->
	<div class="em-report-stats">
		<div class="em-stat-box">
			<h3><?php echo number_format( $total_orders ); ?></h3>
			<p>Total Orders</p>
		</div>

		<div class="em-stat-box warehouse">
			<h3><?php echo number_format( $data['warehouse_count'] ); ?></h3>
			<p>Warehouse (<?php echo number_format( $warehouse_pct, 1 ); ?>%)</p>
		</div>

		<div class="em-stat-box store">
			<h3><?php echo number_format( $data['store_count'] ); ?></h3>
			<p>Store (<?php echo number_format( $store_pct, 1 ); ?>%)</p>
		</div>

		<div class="em-stat-box pickup">
			<h3><?php echo number_format( $data['pickup_count'] ); ?></h3>
			<p>Local Pickup</p>
		</div>

		<div class="em-stat-box ship-to-store">
			<h3><?php echo number_format( $data['ship_to_store_count'] ); ?></h3>
			<p>Ship to Store</p>
		</div>

		<?php if ( $data['unknown_count'] > 0 ) : ?>
			<div class="em-stat-box unknown">
				<h3><?php echo number_format( $data['unknown_count'] ); ?></h3>
				<p>Unknown (<?php echo number_format( $unknown_pct, 1 ); ?>%)</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Cost Breakdown -->
	<h3>Shipping Costs by Location</h3>
	<div class="em-report-stats">
		<div class="em-stat-box warehouse">
			<h3>$<?php echo number_format( $data['warehouse_cost'], 2 ); ?></h3>
			<p>Warehouse Shipping</p>
		</div>

		<div class="em-stat-box store">
			<h3>$<?php echo number_format( $data['store_cost'], 2 ); ?></h3>
			<p>Store Shipping</p>
		</div>

		<div class="em-stat-box ship-to-store">
			<h3>$<?php echo number_format( $data['ship_to_store_cost'], 2 ); ?></h3>
			<p>Ship to Store Revenue</p>
		</div>

		<div class="em-stat-box">
			<h3>$<?php echo number_format( $data['warehouse_cost'] + $data['store_cost'] + $data['ship_to_store_cost'], 2 ); ?></h3>
			<p>Total Shipping Revenue</p>
		</div>
	</div>

	<!-- Charts -->
	<?php if ( $total_orders > 0 ) : ?>
		<div class="em-charts-row">
			<div class="em-chart-container">
				<h3>Orders by Location</h3>
				<canvas id="fulfillmentPieChart" width="400" height="400"></canvas>
			</div>

			<div class="em-chart-container">
				<h3>Shipping Revenue by Type</h3>
				<canvas id="revenuePieChart" width="400" height="400"></canvas>
			</div>
		</div>

		<script>
		var fulfillmentData = {
			warehouse: <?php echo json_encode( $data['warehouse_count'] ); ?>,
			store: <?php echo json_encode( $data['store_count'] ); ?>,
			unknown: <?php echo json_encode( $data['unknown_count'] ); ?>,
			pickup: <?php echo json_encode( $data['pickup_count'] ); ?>,
			ship_to_store: <?php echo json_encode( $data['ship_to_store_count'] ); ?>,
			warehouse_cost: <?php echo json_encode( $data['warehouse_cost'] ); ?>,
			store_cost: <?php echo json_encode( $data['store_cost'] ); ?>,
			ship_to_store_cost: <?php echo json_encode( $data['ship_to_store_cost'] ); ?>
		};
		</script>
	<?php else : ?>
		<div class="notice notice-info">
			<p>No orders found for the selected date range.</p>
		</div>
	<?php endif; ?>

	<!-- Export Button -->
	<div class="em-report-actions">
		<a href="<?php echo admin_url( 'admin.php?page=em-ups-shipping&tab=reports&report=fulfillment&export=csv&start_date=' . $start_date . '&end_date=' . $end_date ); ?>" 
		   class="button button-secondary">
			Export to CSV
		</a>
	</div>

	<!-- Insights -->
	<?php if ( $total_orders > 0 ) : ?>
		<div class="em-insights">
			<h3>Insights</h3>
			<ul>
				<?php if ( $data['pickup_count'] > 0 ) : ?>
					<li>
						<strong><?php echo number_format( ( $data['pickup_count'] / $total_orders ) * 100, 1 ); ?>%</strong>
						of customers chose local pickup, saving shipping costs.
					</li>
				<?php endif; ?>

				<?php if ( $data['ship_to_store_count'] > 0 ) : ?>
					<li>
						<strong><?php echo number_format( $data['ship_to_store_count'] ); ?> orders</strong>
						used Ship to Store, generating <strong>$<?php echo number_format( $data['ship_to_store_cost'], 2 ); ?></strong>
						in revenue with built-in margin.
					</li>
				<?php endif; ?>

				<?php if ( $warehouse_pct > $store_pct ) : ?>
					<li>
						<strong><?php echo number_format( $warehouse_pct, 1 ); ?>%</strong>
						of orders shipped from warehouse vs <strong><?php echo number_format( $store_pct, 1 ); ?>%</strong> from store.
					</li>
				<?php endif; ?>

				<?php if ( $data['unknown_count'] > 0 ) : ?>
					<li class="warning">
						<strong><?php echo number_format( $data['unknown_count'] ); ?> orders</strong>
						have unknown fulfillment location (placed before profile system was activated).
					</li>
				<?php endif; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>

<style>
.em-stat-box.warehouse {
	border-left: 4px solid #2271b1;
}

.em-stat-box.store {
	border-left: 4px solid #00a32a;
}

.em-stat-box.pickup {
	border-left: 4px solid #00a32a;
}

.em-stat-box.ship-to-store {
	border-left: 4px solid #007cba;
}

.em-stat-box.unknown {
	border-left: 4px solid #dba617;
}

.em-charts-row {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.em-insights {
	background: #f0f6fc;
	border: 1px solid #c3e7ff;
	padding: 20px;
	margin: 20px 0;
	border-radius: 4px;
}

.em-insights h3 {
	margin-top: 0;
	color: #1d4ed8;
}

.em-insights ul {
	margin: 0;
	padding-left: 20px;
}

.em-insights li {
	margin-bottom: 10px;
	line-height: 1.6;
}

.em-insights li.warning {
	color: #946c00;
}

.em-insights strong {
	color: #23282d;
}
</style>
