<?php
/**
 * Profile Rate Calculator
 *
 * Calculates shipping rates based on profile configuration
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EM_Profile_Rate_Calculator {

    /**
     * UPS API instance
     */
    private $ups_api;

    /**
     * Plugin settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ups_api = new EM_UPS_API();
        $this->settings = get_option( 'em_ups_settings', array() );
    }

    /**
     * Calculate rates for a profile and package
     *
     * @param array $profile Profile configuration
     * @param array $package WooCommerce package array
     * @param array $products Products in this package
     * @return array Array of rates
     */
    public function calculate( $profile, $package, $products ) {
        // Get customer destination
        $destination = $package['destination'];

        // Determine zone for customer
        $zone = $this->get_matching_zone( $profile, $destination );

        if ( ! $zone ) {
            // No zone matches - return empty rates or fallback
            return $this->get_fallback_rates();
        }

        $rates = array();

        // For now, profile zones are not yet implemented (Phase 2A focused on profile CRUD)
        // Use UPS rates as default, similar to current behavior
        // When zones are fully implemented in future, this will check zone methods

        // Get primary fulfillment location
        $fulfillment_locations = $profile['fulfillment_locations'] ?? array( 'warehouse' );
        $primary_location = $fulfillment_locations[0] ?? 'warehouse';

        // Get origin address for this location
        $origin_address = $this->get_origin_address( $primary_location );

        if ( ! $origin_address ) {
            return array();
        }

        // Calculate total weight
        $total_weight = $this->calculate_weight( $products );

        // Build API request parameters
        $params = array(
            'origin' => $origin_address,
            'origin_zip' => $origin_address['zip'],
            'destination' => $destination,
            'destination_zip' => $destination['postcode'],
            'weight' => max( $total_weight, 1 ), // Minimum 1 lb
            'profile_id' => $profile['id'] ?? 'general' // For cache key
        );

        // Get UPS rates
        $ups_rates = $this->ups_api->get_rates( $params );

        if ( is_wp_error( $ups_rates ) ) {
            return $this->get_fallback_rates();
        }

        // Convert UPS rates to WooCommerce rate format
        foreach ( $ups_rates as $code => $rate ) {
            $rates[] = array(
                'service' => $rate['service'],
                'code' => $code,
                'cost' => $rate['cost']
            );
        }

        // Apply markup based on products
        $rates = $this->apply_markup( $rates, $products );

        return $rates;
    }

    /**
     * Get matching zone for customer destination
     *
     * @param array $profile Profile data
     * @param array $destination Customer destination
     * @return array|false Zone configuration or false
     */
    private function get_matching_zone( $profile, $destination ) {
        // Zone matching will be implemented when zones are added to profiles
        // For now, return a default zone that enables UPS rates
        return array(
            'name' => 'Default',
            'methods' => array(
                array( 'type' => 'ups_rates' )
            )
        );
    }

    /**
     * Get origin address for fulfillment location
     *
     * @param string $location Location type (warehouse or store)
     * @return array|false Address data or false
     */
    private function get_origin_address( $location ) {
        $prefix = ( $location === 'warehouse' ) ? 'warehouse' : 'store';

        $address = array(
            'address' => $this->settings[ $prefix . '_address' ] ?? '',
            'city' => $this->settings[ $prefix . '_city' ] ?? '',
            'state' => $this->settings[ $prefix . '_state' ] ?? '',
            'zip' => $this->settings[ $prefix . '_zip' ] ?? '',
        );

        // Validate address has required fields
        if ( empty( $address['zip'] ) ) {
            return false;
        }

        return $address;
    }

    /**
     * Calculate total weight for products
     *
     * @param array $products Product array
     * @return float Total weight in pounds
     */
    private function calculate_weight( $products ) {
        $total = 0;

        foreach ( $products as $item ) {
            $weight = $item['weight'] > 0 ? $item['weight'] : 1; // Default 1 lb if missing
            $total += $weight * $item['quantity'];
        }

        return $total;
    }

    /**
     * Apply product-specific markup to rates
     *
     * @param array $rates Shipping rates
     * @param array $products Products array
     * @return array Rates with markup applied
     */
    private function apply_markup( $rates, $products ) {
        foreach ( $products as $item ) {
            $product_id = $item['product_id'];

            $markup_enabled = get_post_meta( $product_id, '_em_enable_shipping_markup', true );

            if ( $markup_enabled === 'yes' ) {
                $markup_type = get_post_meta( $product_id, '_em_markup_type', true );
                $markup_value = floatval( get_post_meta( $product_id, '_em_markup_value', true ) );

                if ( $markup_value > 0 ) {
                    foreach ( $rates as &$rate ) {
                        if ( $markup_type === 'percentage' ) {
                            $rate['cost'] += ( $rate['cost'] * ( $markup_value / 100 ) );
                        } else {
                            $rate['cost'] += $markup_value;
                        }
                    }
                }
            }
        }

        return $rates;
    }

    /**
     * Get fallback rates when API fails
     *
     * @return array Fallback rates
     */
    private function get_fallback_rates() {
        $fallback = $this->settings['fallback_rates'] ?? array();
        $rates = array();

        $services = array(
            'ground' => __( 'UPS Ground', 'epic-marks-shipping' ),
            '2day' => __( 'UPS 2nd Day Air', 'epic-marks-shipping' ),
            'nextday' => __( 'UPS Next Day Air', 'epic-marks-shipping' )
        );

        foreach ( $services as $code => $name ) {
            if ( isset( $fallback[ $code ] ) && $fallback[ $code ] > 0 ) {
                $rates[] = array(
                    'service' => $name . ' (Estimated)',
                    'code' => $code,
                    'cost' => floatval( $fallback[ $code ] )
                );
            }
        }

        return $rates;
    }

    /**
     * Group cart items by profile
     *
     * @param array $cart_items WooCommerce cart items
     * @return array Products grouped by profile ID
     */
    public static function group_by_profile( $cart_items ) {
        $grouped = array();

        foreach ( $cart_items as $item ) {
            $product = $item['data'];
            $product_id = $product->get_id();

            // Get profile for this product
            $profile = EM_Profile_Manager::get_product_profile( $product_id );

            if ( ! $profile ) {
                // Fallback to general profile
                $profile_id = 'general';
            } else {
                $profile_id = $profile['id'];
            }

            if ( ! isset( $grouped[ $profile_id ] ) ) {
                $grouped[ $profile_id ] = array(
                    'profile' => $profile,
                    'products' => array()
                );
            }

            $grouped[ $profile_id ]['products'][] = array(
                'product_id' => $product_id,
                'product' => $product,
                'quantity' => $item['quantity'],
                'weight' => floatval( $product->get_weight() ),
            );
        }

        return $grouped;
    }
}
