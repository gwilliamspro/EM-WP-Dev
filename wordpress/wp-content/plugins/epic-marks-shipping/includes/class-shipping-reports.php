<?php
/**
 * Epic Marks Shipping Reports
 *
 * Query methods for shipping analytics and reporting
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EM_Shipping_Reports {

	/**
	 * Get cost analysis report
	 *
	 * Compares estimated shipping costs vs actual PirateShip label costs
	 *
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Report data
	 */
	public static function get_cost_analysis( $start_date, $end_date ) {
		global $wpdb;

		// Query orders in date range
		$orders = wc_get_orders( array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => array( 'wc-completed', 'wc-processing', 'wc-shipped' ),
		) );

		$total_orders = count( $orders );
		$estimated_total = 0;
		$actual_total = 0;
		$labels_purchased = 0;

		foreach ( $orders as $order ) {
			// Estimated cost (what customer paid for shipping)
			$estimated_total += floatval( $order->get_shipping_total() );

			// Actual cost (PirateShip label cost)
			$label_cost = $order->get_meta( '_pirateship_label_cost', true );
			if ( $label_cost ) {
				$actual_total += floatval( $label_cost );
				$labels_purchased++;
			}
		}

		$savings = $estimated_total - $actual_total;
		$avg_label_cost = $labels_purchased > 0 ? $actual_total / $labels_purchased : 0;

		return array(
			'total_orders'      => $total_orders,
			'labels_purchased'  => $labels_purchased,
			'estimated_total'   => $estimated_total,
			'actual_total'      => $actual_total,
			'savings'           => $savings,
			'avg_label_cost'    => $avg_label_cost,
			'orders'            => $orders, // For detailed view
		);
	}

	/**
	 * Get fulfillment breakdown report
	 *
	 * Shows warehouse vs store fulfillment statistics
	 *
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Report data
	 */
	public static function get_fulfillment_breakdown( $start_date, $end_date ) {
		$orders = wc_get_orders( array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => array( 'wc-completed', 'wc-processing', 'wc-shipped' ),
		) );

		$warehouse_count = 0;
		$warehouse_cost = 0;
		$store_count = 0;
		$store_cost = 0;
		$pickup_count = 0;
		$ship_to_store_count = 0;
		$ship_to_store_cost = 0;
		$unknown_count = 0;

		foreach ( $orders as $order ) {
			$location = $order->get_meta( '_fulfillment_location', true );
			$shipping_cost = floatval( $order->get_shipping_total() );

			// Check for local pickup
			$shipping_method = $order->get_shipping_method();
			$is_pickup = ( stripos( $shipping_method, 'pickup' ) !== false );

			// Check for ship to store
			$ship_to_store_selected = $order->get_meta( '_ship_to_store_selected', true );

			if ( $is_pickup ) {
				$pickup_count++;
				$store_count++;
			} elseif ( $ship_to_store_selected ) {
				$ship_to_store_count++;
				$ship_to_store_cost += floatval( $order->get_meta( '_ship_to_store_cost', true ) );
				$warehouse_count++;
			} elseif ( $location === 'warehouse' ) {
				$warehouse_count++;
				$warehouse_cost += $shipping_cost;
			} elseif ( $location === 'store' ) {
				$store_count++;
				$store_cost += $shipping_cost;
			} else {
				// Unknown location (orders before profile system)
				$unknown_count++;
			}
		}

		return array(
			'warehouse_count'     => $warehouse_count,
			'warehouse_cost'      => $warehouse_cost,
			'store_count'         => $store_count,
			'store_cost'          => $store_cost,
			'pickup_count'        => $pickup_count,
			'ship_to_store_count' => $ship_to_store_count,
			'ship_to_store_cost'  => $ship_to_store_cost,
			'unknown_count'       => $unknown_count,
			'total_orders'        => count( $orders ),
		);
	}

	/**
	 * Get service performance report
	 *
	 * Shows breakdown by shipping service level
	 *
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Report data
	 */
	public static function get_service_performance( $start_date, $end_date ) {
		$orders = wc_get_orders( array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => array( 'wc-completed', 'wc-processing', 'wc-shipped' ),
		) );

		$services = array();
		$total_orders = count( $orders );

		foreach ( $orders as $order ) {
			// Get service from PirateShip meta (most accurate)
			$service = $order->get_meta( '_pirateship_service', true );

			// Fallback: parse from shipping method title
			if ( ! $service ) {
				$shipping_method = $order->get_shipping_method();
				$service = self::parse_service_from_method( $shipping_method );
			}

			if ( ! $service ) {
				$service = 'Unknown';
			}

			if ( ! isset( $services[ $service ] ) ) {
				$services[ $service ] = array(
					'count'      => 0,
					'percentage' => 0,
					'total_cost' => 0,
				);
			}

			$services[ $service ]['count']++;
			$services[ $service ]['total_cost'] += floatval( $order->get_shipping_total() );
		}

		// Calculate percentages
		foreach ( $services as $service => &$data ) {
			$data['percentage'] = $total_orders > 0 ? ( $data['count'] / $total_orders ) * 100 : 0;
		}

		// Sort by count descending
		uasort( $services, function( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return array(
			'total_orders' => $total_orders,
			'services'     => $services,
		);
	}

	/**
	 * Get orders missing labels
	 *
	 * Find orders that need shipping labels purchased
	 *
	 * @param string $start_date Start date (Y-m-d format)
	 * @param string $end_date End date (Y-m-d format)
	 * @return array Order IDs and details
	 */
	public static function get_missing_labels( $start_date, $end_date ) {
		$orders = wc_get_orders( array(
			'limit'        => -1,
			'date_created' => $start_date . '...' . $end_date,
			'status'       => array( 'wc-processing', 'wc-on-hold' ),
		) );

		$missing = array();

		foreach ( $orders as $order ) {
			// Skip local pickup orders
			$shipping_method = $order->get_shipping_method();
			if ( stripos( $shipping_method, 'pickup' ) !== false ) {
				continue;
			}

			// Check for tracking number
			$tracking = $order->get_meta( '_pirateship_tracking_number', true );

			if ( ! $tracking ) {
				$missing[] = array(
					'order_id'        => $order->get_id(),
					'order_number'    => $order->get_order_number(),
					'date'            => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
					'customer'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
					'shipping_method' => $shipping_method,
					'total'           => $order->get_total(),
				);
			}
		}

		return $missing;
	}

	/**
	 * Parse service level from shipping method title
	 *
	 * @param string $method_title Shipping method title
	 * @return string Service code or name
	 */
	private static function parse_service_from_method( $method_title ) {
		$method_lower = strtolower( $method_title );

		if ( stripos( $method_lower, 'next day' ) !== false || stripos( $method_lower, 'overnight' ) !== false ) {
			return 'UPS Next Day Air';
		}

		if ( stripos( $method_lower, '2nd day' ) !== false || stripos( $method_lower, '2 day' ) !== false ) {
			return 'UPS 2nd Day Air';
		}

		if ( stripos( $method_lower, '3 day' ) !== false ) {
			return 'UPS 3 Day Select';
		}

		if ( stripos( $method_lower, 'ground' ) !== false ) {
			return 'UPS Ground';
		}

		if ( stripos( $method_lower, 'saver' ) !== false ) {
			return 'UPS Worldwide Saver';
		}

		if ( stripos( $method_lower, 'free' ) !== false ) {
			return 'Free Shipping';
		}

		if ( stripos( $method_lower, 'pickup' ) !== false ) {
			return 'Local Pickup';
		}

		if ( stripos( $method_lower, 'ship to store' ) !== false ) {
			return 'Ship to Store';
		}

		return 'Unknown';
	}

	/**
	 * Check if PirateShip plugin is active
	 *
	 * @return bool
	 */
	public static function is_pirateship_active() {
		return is_plugin_active( 'pirateship-for-woocommerce/pirateship-for-woocommerce.php' );
	}

	/**
	 * Export report data to CSV
	 *
	 * @param array $data Report data
	 * @param string $filename Filename for download
	 */
	public static function export_to_csv( $data, $filename = 'shipping-report.csv' ) {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write headers (first row keys)
		if ( ! empty( $data ) && is_array( $data[0] ) ) {
			fputcsv( $output, array_keys( $data[0] ) );
		}

		// Write data rows
		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}
}
