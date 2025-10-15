<?php
/**
 * Service Performance Report
 *
 * Shows breakdown by shipping service level
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
$data = EM_Shipping_Reports::get_service_performance( $start_date, $end_date );
?>

<div class="em-report-container">
	<h2>Service Level Performance</h2>
	<p class="description">
		Analyze which shipping services customers choose most frequently.
	</p>

	<!-- Summary Stats -->
	<div class="em-report-stats">
		<div class="em-stat-box">
			<h3><?php echo number_format( $data['total_orders'] ); ?></h3>
			<p>Total Orders</p>
		</div>

		<div class="em-stat-box">
			<h3><?php echo count( $data['services'] ); ?></h3>
			<p>Shipping Services Used</p>
		</div>

		<?php
		// Find most popular service
		$most_popular = '';
		$highest_count = 0;
		foreach ( $data['services'] as $service => $stats ) {
			if ( $stats['count'] > $highest_count ) {
				$highest_count = $stats['count'];
				$most_popular = $service;
			}
		}
		?>

		<?php if ( $most_popular ) : ?>
			<div class="em-stat-box popular">
				<h3><?php echo esc_html( $most_popular ); ?></h3>
				<p>Most Popular Service</p>
			</div>

			<div class="em-stat-box">
				<h3><?php echo number_format( $data['services'][ $most_popular ]['percentage'], 1 ); ?>%</h3>
				<p>Of Total Orders</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Service Breakdown Table -->
	<?php if ( ! empty( $data['services'] ) ) : ?>
		<h3>Service Breakdown</h3>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Service</th>
					<th>Order Count</th>
					<th>Percentage</th>
					<th>Total Revenue</th>
					<th>Avg Cost/Order</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['services'] as $service => $stats ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $service ); ?></strong></td>
						<td><?php echo number_format( $stats['count'] ); ?></td>
						<td>
							<div class="em-percentage-bar">
								<div class="em-percentage-fill" style="width: <?php echo $stats['percentage']; ?>%"></div>
								<span class="em-percentage-text"><?php echo number_format( $stats['percentage'], 1 ); ?>%</span>
							</div>
						</td>
						<td>$<?php echo number_format( $stats['total_cost'], 2 ); ?></td>
						<td>
							<?php
							$avg = $stats['count'] > 0 ? $stats['total_cost'] / $stats['count'] : 0;
							echo '$' . number_format( $avg, 2 );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<div class="notice notice-info">
			<p>No orders found for the selected date range.</p>
		</div>
	<?php endif; ?>

	<!-- Chart -->
	<?php if ( ! empty( $data['services'] ) ) : ?>
		<div class="em-chart-container">
			<h3>Service Distribution</h3>
			<canvas id="serviceBarChart" width="800" height="400"></canvas>
		</div>

		<script>
		var serviceData = <?php echo json_encode( $data['services'] ); ?>;
		</script>
	<?php endif; ?>

	<!-- Export Button -->
	<div class="em-report-actions">
		<a href="<?php echo admin_url( 'admin.php?page=em-ups-shipping&tab=reports&report=service-performance&export=csv&start_date=' . $start_date . '&end_date=' . $end_date ); ?>" 
		   class="button button-secondary">
			Export to CSV
		</a>
	</div>

	<!-- Insights -->
	<?php if ( ! empty( $data['services'] ) ) : ?>
		<div class="em-insights">
			<h3>Insights</h3>
			<ul>
				<?php
				// Ground shipping
				$ground_services = array( 'UPS Ground', 'USPS Ground', 'Ground' );
				$ground_count = 0;
				foreach ( $ground_services as $service_name ) {
					if ( isset( $data['services'][ $service_name ] ) ) {
						$ground_count += $data['services'][ $service_name ]['count'];
					}
				}

				if ( $ground_count > 0 ) {
					$ground_pct = ( $ground_count / $data['total_orders'] ) * 100;
					?>
					<li>
						<strong><?php echo number_format( $ground_pct, 1 ); ?>%</strong>
						of customers chose Ground shipping (most economical option).
					</li>
				<?php } ?>

				<?php
				// Expedited shipping
				$expedited_services = array( 'UPS Next Day Air', 'UPS 2nd Day Air', 'UPS 3 Day Select' );
				$expedited_count = 0;
				foreach ( $expedited_services as $service_name ) {
					if ( isset( $data['services'][ $service_name ] ) ) {
						$expedited_count += $data['services'][ $service_name ]['count'];
					}
				}

				if ( $expedited_count > 0 ) {
					$expedited_pct = ( $expedited_count / $data['total_orders'] ) * 100;
					?>
					<li>
						<strong><?php echo number_format( $expedited_pct, 1 ); ?>%</strong>
						of customers paid for expedited shipping (2-Day or Next Day).
					</li>
				<?php } ?>

				<?php
				// Free shipping
				if ( isset( $data['services']['Free Shipping'] ) ) {
					$free_pct = $data['services']['Free Shipping']['percentage'];
					?>
					<li>
						<strong><?php echo number_format( $free_pct, 1 ); ?>%</strong>
						of orders qualified for free shipping.
					</li>
				<?php } ?>

				<?php
				// Local pickup
				if ( isset( $data['services']['Local Pickup'] ) ) {
					$pickup_pct = $data['services']['Local Pickup']['percentage'];
					?>
					<li>
						<strong><?php echo number_format( $pickup_pct, 1 ); ?>%</strong>
						of customers chose local pickup (zero shipping cost).
					</li>
				<?php } ?>

				<?php
				// Ship to Store
				if ( isset( $data['services']['Ship to Store'] ) ) {
					$sts_data = $data['services']['Ship to Store'];
					$avg_sts = $sts_data['count'] > 0 ? $sts_data['total_cost'] / $sts_data['count'] : 0;
					?>
					<li>
						<strong><?php echo number_format( $sts_data['count'] ); ?> customers</strong>
						chose Ship to Store, averaging <strong>$<?php echo number_format( $avg_sts, 2 ); ?></strong> per order.
					</li>
				<?php } ?>
			</ul>
		</div>
	<?php endif; ?>
</div>

<style>
.em-stat-box.popular {
	border-left: 4px solid #2271b1;
	background: #f0f6fc;
}

.em-percentage-bar {
	position: relative;
	background: #f0f0f1;
	height: 24px;
	border-radius: 3px;
	overflow: hidden;
	min-width: 150px;
}

.em-percentage-fill {
	background: linear-gradient(to right, #2271b1, #135e96);
	height: 100%;
	transition: width 0.3s ease;
}

.em-percentage-text {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	font-weight: 600;
	font-size: 12px;
	color: #23282d;
	text-shadow: 0 0 2px #fff;
}

.widefat th {
	text-align: left;
}

.widefat td {
	vertical-align: middle;
}
</style>
