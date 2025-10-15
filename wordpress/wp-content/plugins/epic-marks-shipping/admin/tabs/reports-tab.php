<?php
/**
 * Reports Tab
 *
 * Main reports dashboard with date range picker and report navigation
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current report and date range
$current_report = isset( $_GET['report'] ) ? sanitize_text_field( $_GET['report'] ) : 'cost-analysis';
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : date( 'Y-m-01' );
$end_date = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : date( 'Y-m-d' );

// Handle CSV export
if ( isset( $_GET['export'] ) && $_GET['export'] === 'csv' ) {
	$export_data = array();
	
	switch ( $current_report ) {
		case 'cost-analysis':
			$data = EM_Shipping_Reports::get_cost_analysis( $start_date, $end_date );
			foreach ( $data['orders'] as $order ) {
				$export_data[] = array(
					'Order Number'    => $order->get_order_number(),
					'Date'            => $order->get_date_created()->format( 'Y-m-d' ),
					'Customer'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
					'Shipping Method' => $order->get_shipping_method(),
					'Estimated Cost'  => $order->get_shipping_total(),
					'Actual Cost'     => $order->get_meta( '_pirateship_label_cost', true ),
					'Difference'      => floatval( $order->get_shipping_total() ) - floatval( $order->get_meta( '_pirateship_label_cost', true ) ),
				);
			}
			EM_Shipping_Reports::export_to_csv( $export_data, 'cost-analysis-' . $start_date . '-' . $end_date . '.csv' );
			break;

		case 'fulfillment':
			$data = EM_Shipping_Reports::get_fulfillment_breakdown( $start_date, $end_date );
			$export_data = array(
				array(
					'Location'       => 'Warehouse',
					'Order Count'    => $data['warehouse_count'],
					'Shipping Cost'  => $data['warehouse_cost'],
					'Ship to Store'  => $data['ship_to_store_count'],
					'Ship to Store Revenue' => $data['ship_to_store_cost'],
				),
				array(
					'Location'       => 'Store',
					'Order Count'    => $data['store_count'],
					'Shipping Cost'  => $data['store_cost'],
					'Local Pickup'   => $data['pickup_count'],
					'Ship to Store'  => 0,
				),
			);
			EM_Shipping_Reports::export_to_csv( $export_data, 'fulfillment-breakdown-' . $start_date . '-' . $end_date . '.csv' );
			break;

		case 'service-performance':
			$data = EM_Shipping_Reports::get_service_performance( $start_date, $end_date );
			foreach ( $data['services'] as $service => $stats ) {
				$export_data[] = array(
					'Service'     => $service,
					'Order Count' => $stats['count'],
					'Percentage'  => number_format( $stats['percentage'], 2 ) . '%',
					'Total Cost'  => $stats['total_cost'],
					'Avg Cost'    => $stats['count'] > 0 ? $stats['total_cost'] / $stats['count'] : 0,
				);
			}
			EM_Shipping_Reports::export_to_csv( $export_data, 'service-performance-' . $start_date . '-' . $end_date . '.csv' );
			break;

		case 'missing-labels':
			$data = EM_Shipping_Reports::get_missing_labels( $start_date, $end_date );
			EM_Shipping_Reports::export_to_csv( $data, 'missing-labels-' . $start_date . '-' . $end_date . '.csv' );
			break;
	}
}

// Available reports
$reports = array(
	'cost-analysis'       => 'Cost Analysis',
	'fulfillment'         => 'Fulfillment Breakdown',
	'service-performance' => 'Service Performance',
	'missing-labels'      => 'Missing Labels',
);
?>

<div class="em-reports-dashboard">
	<h2>Shipping Reports</h2>

	<!-- Date Range Picker -->
	<div class="em-date-range-picker">
		<form method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
			<input type="hidden" name="page" value="em-ups-shipping">
			<input type="hidden" name="tab" value="reports">
			<input type="hidden" name="report" value="<?php echo esc_attr( $current_report ); ?>">

			<label for="start_date">Start Date:</label>
			<input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" required>

			<label for="end_date">End Date:</label>
			<input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>" required>

			<button type="submit" class="button button-primary">Update Range</button>

			<!-- Quick Date Shortcuts -->
			<span class="em-date-shortcuts">
				<button type="button" class="button button-secondary em-date-shortcut" data-days="7">Last 7 Days</button>
				<button type="button" class="button button-secondary em-date-shortcut" data-days="30">Last 30 Days</button>
				<button type="button" class="button button-secondary em-date-shortcut" data-month="current">This Month</button>
				<button type="button" class="button button-secondary em-date-shortcut" data-month="previous">Last Month</button>
			</span>
		</form>
	</div>

	<!-- Report Navigation -->
	<div class="em-report-nav">
		<nav class="nav-tab-wrapper">
			<?php foreach ( $reports as $report_key => $report_name ) : ?>
				<?php
				$url = add_query_arg(
					array(
						'page'       => 'em-ups-shipping',
						'tab'        => 'reports',
						'report'     => $report_key,
						'start_date' => $start_date,
						'end_date'   => $end_date,
					),
					admin_url( 'admin.php' )
				);
				$active = $current_report === $report_key ? 'nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="nav-tab <?php echo $active; ?>">
					<?php echo esc_html( $report_name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>

	<!-- Report Content -->
	<div class="em-report-content">
		<?php
		// Load the selected report
		$report_file = EM_SHIPPING_PLUGIN_DIR . 'admin/reports/' . $current_report . '.php';
		
		if ( file_exists( $report_file ) ) {
			include $report_file;
		} else {
			// Missing Labels report (inline)
			if ( $current_report === 'missing-labels' ) {
				$missing = EM_Shipping_Reports::get_missing_labels( $start_date, $end_date );
				?>
				<div class="em-report-container">
					<h2>Orders Missing Shipping Labels</h2>
					<p class="description">
						Orders in "Processing" or "On Hold" status without PirateShip tracking numbers.
					</p>

					<?php if ( ! empty( $missing ) ) : ?>
						<p><strong><?php echo count( $missing ); ?> orders need labels</strong></p>
						
						<table class="widefat striped">
							<thead>
								<tr>
									<th>Order #</th>
									<th>Date</th>
									<th>Customer</th>
									<th>Shipping Method</th>
									<th>Total</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $missing as $order_data ) : ?>
									<tr>
										<td>
											<a href="<?php echo admin_url( 'post.php?post=' . $order_data['order_id'] . '&action=edit' ); ?>">
												#<?php echo $order_data['order_number']; ?>
											</a>
										</td>
										<td><?php echo date( 'M j, Y', strtotime( $order_data['date'] ) ); ?></td>
										<td><?php echo esc_html( $order_data['customer'] ); ?></td>
										<td><?php echo esc_html( $order_data['shipping_method'] ); ?></td>
										<td>$<?php echo number_format( $order_data['total'], 2 ); ?></td>
										<td>
											<a href="<?php echo admin_url( 'post.php?post=' . $order_data['order_id'] . '&action=edit' ); ?>" 
											   class="button button-small button-primary">
												Purchase Label
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<!-- Export Button -->
						<div class="em-report-actions">
							<a href="<?php echo admin_url( 'admin.php?page=em-ups-shipping&tab=reports&report=missing-labels&export=csv&start_date=' . $start_date . '&end_date=' . $end_date ); ?>" 
							   class="button button-secondary">
								Export to CSV
							</a>
						</div>
					<?php else : ?>
						<div class="notice notice-success">
							<p>✓ All orders have shipping labels! No action needed.</p>
						</div>
					<?php endif; ?>
				</div>
				<?php
			} else {
				echo '<div class="notice notice-error"><p>Report not found.</p></div>';
			}
		}
		?>
	</div>
</div>

<style>
.em-reports-dashboard {
	background: #fff;
	padding: 20px;
	margin: 20px 0 0 0;
}

.em-date-range-picker {
	background: #f0f0f1;
	padding: 15px;
	margin: 20px 0;
	border-radius: 4px;
}

.em-date-range-picker form {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
}

.em-date-range-picker label {
	font-weight: 600;
	margin-left: 10px;
}

.em-date-range-picker input[type="date"] {
	padding: 5px 10px;
	border: 1px solid #8c8f94;
	border-radius: 3px;
}

.em-date-shortcuts {
	margin-left: 20px;
	display: inline-flex;
	gap: 5px;
}

.em-report-nav {
	margin: 20px 0;
}

.em-report-content {
	margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Date shortcuts
	$('.em-date-shortcut').on('click', function() {
		var days = $(this).data('days');
		var month = $(this).data('month');
		var today = new Date();
		var startDate, endDate;

		if (days) {
			// Last N days
			endDate = new Date();
			startDate = new Date();
			startDate.setDate(startDate.getDate() - days);
		} else if (month === 'current') {
			// This month
			startDate = new Date(today.getFullYear(), today.getMonth(), 1);
			endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
		} else if (month === 'previous') {
			// Last month
			startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
			endDate = new Date(today.getFullYear(), today.getMonth(), 0);
		}

		if (startDate && endDate) {
			$('#start_date').val(formatDate(startDate));
			$('#end_date').val(formatDate(endDate));
			$(this).closest('form').submit();
		}
	});

	function formatDate(date) {
		var year = date.getFullYear();
		var month = String(date.getMonth() + 1).padStart(2, '0');
		var day = String(date.getDate()).padStart(2, '0');
		return year + '-' + month + '-' + day;
	}
});
</script>
