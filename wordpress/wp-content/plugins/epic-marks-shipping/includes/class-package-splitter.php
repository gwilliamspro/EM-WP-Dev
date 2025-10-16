<?php
/**
 * Package Splitter Class
 *
 * Handles splitting cart items into packages based on:
 * - Shipping profile
 * - Fulfillment location (warehouse/store)
 * - Local pickup availability
 * - Ship to Store options
 * - Box compatibility (tube vs regular, ships separately)
 * - Intelligent routing (ship together vs split)
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EM_Package_Splitter {

	/**
	 * Split cart items with intelligent routing options
	 *
	 * Detects mixed carts (store + warehouse items) and calculates:
	 * - Ship together option (all from store)
	 * - Split shipment option (store from store, warehouse from warehouse)
	 * Returns routing options with cost comparison and recommendation
	 *
	 * @param array $cart_items WooCommerce cart items
	 * @param array $destination Customer shipping address
	 * @return array Packages or routing options array
	 */
	public static function split_with_routing_options( $cart_items, $destination = array() ) {
		// First, detect if this is a mixed cart
		$has_store_items = false;
		$has_warehouse_items = false;
		$store_items = array();
		$warehouse_items = array();

		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			$profile = EM_Profile_Manager::get_product_profile( $product_id );

			if ( ! $profile ) {
				$profile = EM_Shipping_Profile::get( 'general' );
			}

			$locations = isset( $profile->fulfillment_locations ) ? $profile->fulfillment_locations : array( 'warehouse' );
			$primary_location = ! empty( $locations ) ? $locations[0] : 'warehouse';

			if ( in_array( 'store', $locations, true ) || $primary_location === 'store' ) {
				$has_store_items = true;
				$store_items[ $cart_item_key ] = $cart_item;
			}

			// SSAW warehouses or regular warehouse
			if ( in_array( 'warehouse', $locations, true ) || 
			     in_array( 'ssaw_warehouse', $locations, true ) || 
			     $primary_location === 'warehouse' ||
			     $primary_location === 'ssaw_warehouse' ) {
				$has_warehouse_items = true;
				$warehouse_items[ $cart_item_key ] = $cart_item;
			}
		}

		// If mixed cart, calculate routing options
		if ( $has_store_items && $has_warehouse_items ) {
			return self::calculate_routing_options( $cart_items, $store_items, $warehouse_items, $destination );
		}

		// Single location cart - use standard split
		return array(
			'type' => 'standard',
			'packages' => self::split( $cart_items, $destination ),
		);
	}

	/**
	 * Calculate routing options for mixed cart
	 *
	 * @param array $all_items All cart items
	 * @param array $store_items Items that can ship from store
	 * @param array $warehouse_items Items that must ship from warehouse
	 * @param array $destination Customer address
	 * @return array Routing options with cost comparison
	 */
	private static function calculate_routing_options( $all_items, $store_items, $warehouse_items, $destination ) {
		$options = array();

		// Option 1: Ship together from store (if feasible)
		$ship_together = self::calculate_ship_together_option( $all_items, $destination );
		if ( $ship_together ) {
			$options['ship_together'] = $ship_together;
		}

		// Option 2: Split shipment (always available)
		$options['split'] = self::calculate_split_option( $store_items, $warehouse_items, $destination );

		// Determine cheapest option and mark as recommended
		$costs = array();
		foreach ( $options as $key => $option ) {
			$costs[ $key ] = $option['cost'];
		}

		$cheapest_cost = min( $costs );
		foreach ( $options as $key => $option ) {
			$options[ $key ]['recommended'] = ( $option['cost'] === $cheapest_cost );
		}

		return array(
			'type' => 'routing',
			'options' => $options,
		);
	}

	/**
	 * Calculate ship-together option (all items from store)
	 *
	 * @param array $all_items All cart items
	 * @param array $destination Customer address
	 * @return array|null Ship-together option or null if not feasible
	 */
	private static function calculate_ship_together_option( $all_items, $destination ) {
		// Check if store has stock for all items
		// For now, we'll assume it's always possible (stock check would be added later)
		// In production, you'd check each item's _stock_round_rock or _stock_south_austin meta

		// Create single package from store with all items
		$store_packages = self::split( $all_items, $destination );
		
		// Force all packages to ship from store location
		foreach ( $store_packages as &$package ) {
			$package['location'] = 'store';
		}

		// Calculate total cost using store location
		$total_cost = self::calculate_packages_cost( $store_packages, $destination );

		if ( is_wp_error( $total_cost ) || $total_cost === false ) {
			return null; // Cannot calculate ship-together
		}

		// Get estimated delivery date (earliest from all packages)
		$delivery_dates = array();
		foreach ( $store_packages as $package ) {
			// TODO: Add delivery date calculation when Phase 2C is implemented
			$delivery_dates[] = '+3 days'; // Placeholder
		}

		return array(
			'id' => 'ship_together',
			'label' => __( 'Ship Everything Together', 'epic-marks-shipping' ),
			'description' => __( 'All items ship from store location', 'epic-marks-shipping' ),
			'cost' => $total_cost,
			'packages' => $store_packages,
			'fulfillment_location' => 'store',
			'estimated_delivery' => reset( $delivery_dates ),
			'recommended' => false, // Will be set by parent function
		);
	}

	/**
	 * Calculate split shipment option
	 *
	 * @param array $store_items Items from store
	 * @param array $warehouse_items Items from warehouse
	 * @param array $destination Customer address
	 * @return array Split option data
	 */
	private static function calculate_split_option( $store_items, $warehouse_items, $destination ) {
		$all_items = array_merge( $store_items, $warehouse_items );
		$packages = self::split( $all_items, $destination );

		// Calculate total cost for split packages
		$total_cost = self::calculate_packages_cost( $packages, $destination );

		if ( is_wp_error( $total_cost ) || $total_cost === false ) {
			$total_cost = 0; // Fallback
		}

		// Build package breakdown for display
		$package_breakdown = array();
		foreach ( $packages as $package ) {
			$location = $package['location'] ?? 'warehouse';
			$profile = $package['profile'] ?? null;
			$item_count = count( $package['contents'] );

			$package_breakdown[] = array(
				'location' => $location,
				'profile' => $profile ? $profile->name : 'General',
				'item_count' => $item_count,
				'description' => self::get_package_label( $package ),
			);
		}

		return array(
			'id' => 'split',
			'label' => __( 'Split Shipment', 'epic-marks-shipping' ),
			'description' => sprintf(
				_n(
					'Ships from %d location',
					'Ships from %d locations',
					count( $package_breakdown ),
					'epic-marks-shipping'
				),
				count( $package_breakdown )
			),
			'cost' => $total_cost,
			'packages' => $packages,
			'package_breakdown' => $package_breakdown,
			'recommended' => false, // Will be set by parent function
		);
	}

	/**
	 * Calculate total cost for multiple packages
	 *
	 * This is a simplified calculation. In production, this would call
	 * the UPS API for each package and sum the costs.
	 *
	 * @param array $packages Array of packages
	 * @param array $destination Customer address
	 * @return float|false Total cost or false on failure
	 */
	private static function calculate_packages_cost( $packages, $destination ) {
		// This is a placeholder for cost calculation
		// In production, this would:
		// 1. Loop through each package
		// 2. Call UPS API or rate calculator for each
		// 3. Sum all costs
		// 4. Apply any free shipping rules

		// For now, return a mock cost based on package count and weight
		$total_cost = 0;

		foreach ( $packages as $package ) {
			$weight = self::calculate_package_weight( $package['contents'], $package );
			// Mock calculation: $8 base + $1 per lb
			$package_cost = 8.00 + ( $weight * 1.00 );
			$total_cost += $package_cost;
		}

		return $total_cost;
	}

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

			// Check for incompatible packaging requirements
			$requires_tube = get_post_meta( $product_id, '_requires_tube', true );
			$ships_separately = get_post_meta( $product_id, '_ships_separately', true );

			// Create package key: profile_location_pickup_packaging
			$package_suffix = ( $pickup_available ? 'pickup' : 'ship' );

			// Items requiring tube or shipping separately get their own package
			if ( $requires_tube ) {
				$package_suffix .= '_tube';
			} elseif ( $ships_separately ) {
				$package_suffix .= '_separate_' . $cart_item_key; // Unique key for each separate item
			}

			$package_key = $profile->id . '_' . $location . '_' . $package_suffix;

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
					'requires_tube'           => $requires_tube,
					'ships_separately'        => $ships_separately,
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

		// Calculate package weight (with dimensional weight consideration)
		$weight = self::calculate_package_weight( $package['contents'], $package );

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
	 * Calculate total weight of items in package (with dimensional weight)
	 *
	 * @param array $items Cart items
	 * @param array $package Optional package data for box selection
	 * @return float Total weight in pounds (billable weight if box available)
	 */
	public static function calculate_package_weight( $items, $package = array() ) {
		$actual_weight = 0;

		foreach ( $items as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( ! $product ) {
				continue;
			}

			$weight = floatval( $product->get_weight() );
			$quantity = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
			$actual_weight += $weight * $quantity;
		}

		// Get selected box for this package
		$box = self::get_package_box( $items, $package );

		if ( $box ) {
			// Use billable weight (max of actual or dimensional)
			return EM_Box_Manager::get_billable_weight( $actual_weight, $box );
		}

		// No box assigned - return actual weight
		return $actual_weight;
	}

	/**
	 * Get box for package based on items and packaging requirements
	 *
	 * @param array $items Cart items
	 * @param array $package Package data
	 * @return array|null Box data or null if not found
	 */
	public static function get_package_box( $items, $package = array() ) {
		// Check if package has specific box requirements
		if ( isset( $package['requires_tube'] ) && $package['requires_tube'] ) {
			$boxes = EM_Box_Manager::get_boxes();
			return EM_Box_Manager::get_box_by_type( $boxes, 'tube' );
		}

		// Check if all items have same preferred box
		$preferred_boxes = array();
		foreach ( $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : ( isset( $item['data'] ) ? $item['data']->get_id() : 0 );
			$preferred_box = get_post_meta( $product_id, '_preferred_box', true );

			if ( $preferred_box && $preferred_box !== 'auto' ) {
				$preferred_boxes[] = $preferred_box;
			}
		}

		// If all items prefer same box, use it
		if ( ! empty( $preferred_boxes ) && count( array_unique( $preferred_boxes ) ) === 1 ) {
			$box_id = $preferred_boxes[0];
			return EM_Box_Manager::get_box( $box_id );
		}

		// Auto-select best box for items
		return EM_Box_Manager::get_box_for_items( $items );
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

		// Add packaging info to label if applicable
		$packaging_info = '';
		if ( isset( $package['requires_tube'] ) && $package['requires_tube'] ) {
			$packaging_info = ' (Tube)';
		} elseif ( isset( $package['ships_separately'] ) && $package['ships_separately'] ) {
			$packaging_info = ' (Ships Separately)';
		}

		return sprintf(
			'%s - %s (%d %s)%s',
			$profile_name,
			$location_label,
			$item_count,
			_n( 'item', 'items', $item_count, 'epic-marks-shipping' ),
			$packaging_info
		);
	}
}
