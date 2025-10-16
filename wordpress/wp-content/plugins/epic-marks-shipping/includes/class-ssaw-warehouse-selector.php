<?php
/**
 * SSAW Warehouse Selector
 *
 * Selects the optimal SSAW warehouse based on shipping cost to customer destination.
 * Calculates rates from all active SSAW warehouses and returns the cheapest option.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SSAW Warehouse Selection Algorithm
 *
 * This class handles the logic for selecting the optimal SSAW warehouse for a given
 * customer order. It calculates UPS Ground rates from all SSAW warehouses and selects
 * the warehouse that provides the lowest cost after applying the 155% markup.
 *
 * @since 2.1.0
 */
class EM_SSAW_Warehouse_Selector {

	/**
	 * SSAW markup percentage (155% = 1.55 multiplier)
	 *
	 * @var float
	 */
	const SSAW_MARKUP = 1.55;

	/**
	 * Cache duration for warehouse rate calculations (30 minutes)
	 *
	 * @var int
	 */
	const CACHE_DURATION = 1800;

	/**
	 * Default fallback warehouse code (TX = Fort Worth)
	 *
	 * @var string
	 */
	const DEFAULT_WAREHOUSE = 'TX';

	/**
	 * Select the optimal SSAW warehouse for a shipment
	 *
	 * Calculates rates from all active SSAW warehouses and returns the warehouse
	 * with the lowest final cost (base rate × 155% markup).
	 *
	 * @param array $package       WooCommerce package array with contents, destination, etc.
	 * @param array $customer_addr Customer shipping address.
	 * @param array $ssaw_warehouses Array of SSAW warehouse location objects.
	 *
	 * @return array|WP_Error Selected warehouse data with rate info, or WP_Error on failure.
	 */
	public static function select_warehouse( $package, $customer_addr, $ssaw_warehouses = array() ) {
		// Get SSAW warehouses if not provided
		if ( empty( $ssaw_warehouses ) ) {
			$ssaw_warehouses = EM_Location_Manager::get_locations_by_type( 'ssaw_warehouse' );
		}

		// Filter only active warehouses
		$ssaw_warehouses = array_filter( $ssaw_warehouses, function( $warehouse ) {
			return isset( $warehouse['status'] ) && 'active' === $warehouse['status'];
		});

		if ( empty( $ssaw_warehouses ) ) {
			return new WP_Error(
				'no_ssaw_warehouses',
				__( 'No active SSAW warehouses configured.', 'epic-marks-shipping' )
			);
		}

		$rates = array();

		// Calculate rate from each warehouse
		foreach ( $ssaw_warehouses as $warehouse ) {
			$warehouse_rate = self::get_warehouse_rate( $warehouse, $customer_addr, $package );

			if ( ! is_wp_error( $warehouse_rate ) ) {
				$rates[ $warehouse['id'] ] = $warehouse_rate;
			} else {
				// Log individual warehouse failures but continue
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf(
						'SSAW Warehouse %s rate calculation failed: %s',
						$warehouse['id'],
						$warehouse_rate->get_error_message()
					) );
				}
			}
		}

		// If all warehouse calls failed, use fallback
		if ( empty( $rates ) ) {
			return self::get_fallback_warehouse( $ssaw_warehouses, $customer_addr, $package );
		}

		// Sort by final cost (cheapest first)
		uasort( $rates, function( $a, $b ) {
			return $a['final_cost'] <=> $b['final_cost'];
		});

		// Return cheapest warehouse
		$selected = reset( $rates );

		// Log selection for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'SSAW Warehouse selected: %s (base: $%.2f, final: $%.2f, transit: %d days)',
				$selected['warehouse']['id'],
				$selected['base_cost'],
				$selected['final_cost'],
				$selected['transit_days']
			) );
		}

		return $selected;
	}

	/**
	 * Get shipping rate from a specific warehouse
	 *
	 * Calculates UPS Ground rate with caching per warehouse + customer ZIP.
	 *
	 * @param array $warehouse     Warehouse location object.
	 * @param array $customer_addr Customer address.
	 * @param array $package       Package data.
	 *
	 * @return array|WP_Error Rate data or error.
	 */
	private static function get_warehouse_rate( $warehouse, $customer_addr, $package ) {
		// Build cache key: warehouse ID + customer ZIP + package weight
		$weight     = self::get_package_weight( $package );
		$cache_key  = sprintf(
			'em_ssaw_rate_%s_%s_%.2f',
			$warehouse['id'],
			sanitize_text_field( $customer_addr['postcode'] ?? '' ),
			$weight
		);

		// Check cache first
		$cached_rate = get_transient( $cache_key );
		if ( false !== $cached_rate ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'SSAW rate cache HIT for %s', $warehouse['id'] ) );
			}
			return $cached_rate;
		}

		// Calculate rate via UPS API
		$ups_response = EM_UPS_API::get_rate(
			$warehouse['address'],
			$customer_addr,
			$package,
			'ground'  // SSAW only uses Ground service
		);

		if ( is_wp_error( $ups_response ) ) {
			return $ups_response;
		}

		// Apply SSAW markup (155%)
		$rate_data = array(
			'warehouse'    => $warehouse,
			'base_cost'    => floatval( $ups_response['cost'] ),
			'final_cost'   => floatval( $ups_response['cost'] ) * self::SSAW_MARKUP,
			'transit_days' => intval( $ups_response['transit_days'] ?? 5 ),
			'service'      => 'ground',
		);

		// Cache for 30 minutes
		set_transient( $cache_key, $rate_data, self::CACHE_DURATION );

		return $rate_data;
	}

	/**
	 * Get fallback warehouse when all API calls fail
	 *
	 * Uses the default TX warehouse or first available warehouse.
	 *
	 * @param array $warehouses    All SSAW warehouses.
	 * @param array $customer_addr Customer address.
	 * @param array $package       Package data.
	 *
	 * @return array|WP_Error Fallback warehouse rate or error.
	 */
	private static function get_fallback_warehouse( $warehouses, $customer_addr, $package ) {
		// Try to find default TX warehouse
		$default_warehouse = null;
		foreach ( $warehouses as $warehouse ) {
			if ( isset( $warehouse['warehouse_code'] ) && self::DEFAULT_WAREHOUSE === $warehouse['warehouse_code'] ) {
				$default_warehouse = $warehouse;
				break;
			}
		}

		// If no TX warehouse, use first available
		if ( ! $default_warehouse && ! empty( $warehouses ) ) {
			$default_warehouse = reset( $warehouses );
		}

		if ( ! $default_warehouse ) {
			return new WP_Error(
				'no_fallback_warehouse',
				__( 'No SSAW warehouse available for fallback.', 'epic-marks-shipping' )
			);
		}

		// Try to get rate from fallback warehouse (no cache on fallback)
		$fallback_rate = EM_UPS_API::get_rate(
			$default_warehouse['address'],
			$customer_addr,
			$package,
			'ground'
		);

		if ( is_wp_error( $fallback_rate ) ) {
			// Complete failure - return error
			error_log( 'SSAW: All warehouse API calls failed including fallback' );
			return new WP_Error(
				'ssaw_rate_failure',
				__( 'Unable to calculate SSAW shipping rate. Please try again.', 'epic-marks-shipping' )
			);
		}

		// Log fallback usage
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'SSAW: Using fallback warehouse %s (all other warehouses failed)',
				$default_warehouse['id']
			) );
		}

		return array(
			'warehouse'    => $default_warehouse,
			'base_cost'    => floatval( $fallback_rate['cost'] ),
			'final_cost'   => floatval( $fallback_rate['cost'] ) * self::SSAW_MARKUP,
			'transit_days' => intval( $fallback_rate['transit_days'] ?? 5 ),
			'service'      => 'ground',
			'is_fallback'  => true,
		);
	}

	/**
	 * Calculate total weight of package contents
	 *
	 * @param array $package WooCommerce package.
	 *
	 * @return float Total weight in pounds.
	 */
	private static function get_package_weight( $package ) {
		$weight = 0;

		if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
			foreach ( $package['contents'] as $item ) {
				if ( isset( $item['data'] ) ) {
					$product = $item['data'];
					$qty     = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
					$weight += floatval( $product->get_weight() ) * $qty;
				}
			}
		}

		// Default to 1 lb minimum if no weight
		return max( 1.0, $weight );
	}

	/**
	 * Clear cached rates for all SSAW warehouses
	 *
	 * Useful when warehouse addresses change or for testing.
	 *
	 * @return int Number of cache entries cleared.
	 */
	public static function clear_warehouse_cache() {
		global $wpdb;

		$cache_pattern = '_transient_em_ssaw_rate_%';
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$cache_pattern
			)
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Cleared %d SSAW warehouse rate cache entries', $deleted ) );
		}

		return $deleted;
	}

	/**
	 * Get all cached rates for debugging
	 *
	 * Returns array of all cached warehouse rates with metadata.
	 *
	 * @return array Cached rate data.
	 */
	public static function get_cached_rates() {
		global $wpdb;

		$cache_pattern = '_transient_em_ssaw_rate_%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$cache_pattern
			),
			ARRAY_A
		);

		$cached_rates = array();
		foreach ( $results as $row ) {
			$key = str_replace( '_transient_', '', $row['option_name'] );
			$cached_rates[ $key ] = maybe_unserialize( $row['option_value'] );
		}

		return $cached_rates;
	}
}
