<?php
/**
 * Package Splitter Class
 *
 * Handles splitting cart items into packages based on:
 * - Shipping profile
 * - Fulfillment location (warehouse/store)
 * - Local pickup availability
 * - Ship to Store options
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EM_Package_Splitter {

	/**
	 * Split cart items into packages by profile, location, and pickup availability
	 *
	 * @param array $cart_items WooCommerce cart items
	 * @param array $destination Customer shipping address
	 * @return array Array of packages
	 */
	public static function split( $cart_items, $destination = array() ) {
		$packages = array();

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			
			// Get product's shipping profile
			$profile = EM_Profile_Manager::get_product_profile( $product_id );
			
			if ( ! $profile ) {
				// Fallback to general profile
				$profile = EM_Shipping_Profile::get( 'general' );
			}

			// Determine primary fulfillment location
			$locations = isset( $profile->fulfillment_locations ) ? $profile->fulfillment_locations : array( 'warehouse' );
			$location = ! empty( $locations ) ? $locations[0] : 'warehouse';

			// Check if local pickup is available for this product
			$pickup_available = ( $profile->local_pickup && $location === 'store' );
			
			// Check if ship to store is available (warehouse items can ship to store)
			$ship_to_store_available = ( 
				isset( $profile->ship_to_store_enabled ) && 
				$profile->ship_to_store_enabled && 
				$location === 'warehouse' &&
				in_array( 'store', $locations, true )
			);

			// Create package key: profile_location_pickup
			$package_key = $profile->id . '_' . $location . '_' . ( $pickup_available ? 'pickup' : 'ship' );

			// Initialize package if it doesn't exist
			if ( ! isset( $packages[ $package_key ] ) ) {
				$packages[ $package_key ] = array(
					'contents'                => array(),
					'contents_cost'           => 0,
					'applied_coupons'         => array(),
					'user'                    => array(),
					'destination'             => $destination,
					'profile'                 => $profile,
					'profile_id'              => $profile->id,
					'location'                => $location,
					'pickup_available'        => $pickup_available,
					'ship_to_store_available' => $ship_to_store_available,
				);
			}

			// Add item to package
			$packages[ $package_key ]['contents'][ $cart_item_key ] = $cart_item;
			$packages[ $package_key ]['contents_cost'] += $cart_item['line_total'];
		}

		return array_values( $packages );
	}

	/**
	 * Calculate ship-to-store rate using UPS API
	 *
	 * Calculates the cost for shipping warehouse items to store for customer pickup
	 * Applies profile's margin to cover supplier shipping costs
	 *
	 * @param array $package Package data
	 * @param object $profile Shipping profile
	 * @return array|false Rate data or false on failure
	 */
	public static function calculate_ship_to_store_rate( $package, $profile ) {
		// Get addresses
		$settings = get_option( 'em_ups_settings', array() );
		$warehouse_zip = isset( $settings['warehouse_zip'] ) ? $settings['warehouse_zip'] : '';
		$store_zip = isset( $settings['store_zip'] ) ? $settings['store_zip'] : '';

		if ( empty( $warehouse_zip ) || empty( $store_zip ) ) {
			return false; // Can't calculate without addresses
		}

		// Calculate package weight
		$weight = self::calculate_package_weight( $package['contents'] );

		if ( $weight <= 0 ) {
			return false;
		}

		// Get UPS rate for warehouse → store
		$ups_api = new EM_UPS_API();
		$params = array(
			'origin_zip'      => $warehouse_zip,
			'origin_country'  => 'US',
			'destination_zip' => $store_zip,
			'destination_country' => 'US',
			'weight'          => $weight,
			'services'        => array( '03' ), // UPS Ground only for ship to store
		);

		$rates = $ups_api->get_rates( $params );

		if ( is_wp_error( $rates ) || empty( $rates ) ) {
			return false; // Don't offer Ship to Store if API fails
		}

		// Get the ground rate (first/only rate since we specified service 03)
		$base_rate = is_array( $rates ) ? reset( $rates ) : $rates;
		$base_cost = isset( $base_rate['cost'] ) ? floatval( $base_rate['cost'] ) : 0;

		if ( $base_cost <= 0 ) {
			return false;
		}

		// Apply profile's ship-to-store margin
		$margin_type = isset( $profile->ship_to_store_margin_type ) ? $profile->ship_to_store_margin_type : 'percentage';
		$margin_value = isset( $profile->ship_to_store_margin ) ? floatval( $profile->ship_to_store_margin ) : 0;

		if ( $margin_type === 'percentage' ) {
			$final_cost = $base_cost * ( 1 + ( $margin_value / 100 ) );
		} else {
			$final_cost = $base_cost + $margin_value;
		}

		// Get custom label
		$label = isset( $profile->ship_to_store_label ) && ! empty( $profile->ship_to_store_label ) 
			? $profile->ship_to_store_label 
			: 'Ship to Store (includes handling)';

		return array(
			'id'        => 'ship_to_store',
			'label'     => $label,
			'cost'      => $final_cost,
			'meta_data' => array(
				'base_cost'           => $base_cost,
				'margin_applied'      => $final_cost - $base_cost,
				'margin_type'         => $margin_type,
				'margin_value'        => $margin_value,
				'warehouse_to_store'  => true,
				'profile_id'          => $profile->id,
			),
		);
	}

	/**
	 * Calculate total weight of items in package
	 *
	 * @param array $items Cart items
	 * @return float Total weight in pounds
	 */
	public static function calculate_package_weight( $items ) {
		$total_weight = 0;

		foreach ( $items as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( ! $product ) {
				continue;
			}

			$weight = floatval( $product->get_weight() );
			$quantity = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
			$total_weight += $weight * $quantity;
		}

		return $total_weight;
	}

	/**
	 * Get package label for customer display
	 *
	 * @param array $package Package data
	 * @return string Package label
	 */
	public static function get_package_label( $package ) {
		$profile_name = isset( $package['profile']->name ) ? $package['profile']->name : 'Products';
		$location = isset( $package['location'] ) ? $package['location'] : '';
		$item_count = count( $package['contents'] );

		$location_label = ( $location === 'store' ) ? 'In-Store' : 'Warehouse';

		return sprintf( '%s - %s (%d %s)', 
			$profile_name, 
			$location_label, 
			$item_count,
			_n( 'item', 'items', $item_count, 'epic-marks-shipping' )
		);
	}
}
