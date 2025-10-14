<?php
/**
 * Epic Marks UPS Shipping Method
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EM_UPS_Shipping_Method extends WC_Shipping_Method {
    
    /**
     * UPS API instance
     */
    private $ups_api;
    
    /**
     * Plugin settings
     */
    private $settings_data;
    
    /**
     * Constructor
     */
    public function __construct( $instance_id = 0 ) {
        $this->id = 'epic_marks_ups';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'UPS Live Rates', 'epic-marks-shipping' );
        $this->method_description = __( 'Real-time UPS shipping rates with multi-location support', 'epic-marks-shipping' );
        $this->supports = array( 'shipping-zones', 'instance-settings' );
        
        // Load settings
        $this->settings_data = get_option( 'em_ups_settings', array() );
        $this->enabled = $this->settings_data['enabled'] ?? 'yes';
        $this->title = $this->settings_data['title'] ?? __( 'UPS Shipping', 'epic-marks-shipping' );
        
        // Initialize UPS API
        $this->ups_api = new EM_UPS_API();
    }
    
    /**
     * Calculate shipping rates
     *
     * @param array $package Cart package
     */
    public function calculate_shipping( $package = array() ) {
        if ( empty( $package['contents'] ) ) {
            return;
        }
        
        // Group products by shipping origin (based on tags)
        $grouped_products = $this->group_by_location( $package['contents'] );
        
        if ( empty( $grouped_products ) ) {
            return;
        }
        
        // Get rates for each origin
        $all_rates = array();
        
        foreach ( $grouped_products as $location => $products ) {
            $origin_address = $this->get_origin_address( $location );
            
            if ( ! $origin_address ) {
                continue;
            }
            
            // Calculate total weight for this origin
            $total_weight = $this->calculate_weight( $products );
            
            // Build API request parameters
            $params = array(
                'origin' => $origin_address,
                'origin_zip' => $origin_address['zip'],
                'destination' => $package['destination'],
                'destination_zip' => $package['destination']['postcode'],
                'weight' => max( $total_weight, 1 ) // Minimum 1 lb
            );
            
            // Get UPS rates
            $rates = $this->ups_api->get_rates( $params );
            
            if ( is_wp_error( $rates ) ) {
                $this->add_fallback_rates();
                return;
            }
            
            // Apply markup to rates
            $rates = $this->apply_markup( $rates, $products );
            
            $all_rates[ $location ] = $rates;
        }
        
        // Combine rates from multiple origins
        $final_rates = $this->combine_rates( $all_rates );
        
        // Check free shipping threshold
        $cart_total = WC()->cart->get_subtotal();
        $free_threshold = floatval( $this->settings_data['free_shipping_threshold'] ?? 0 );
        
        // Add rates to WooCommerce
        foreach ( $final_rates as $rate ) {
            $cost = $rate['cost'];
            
            // Apply free shipping if threshold met
            if ( $free_threshold > 0 && $cart_total >= $free_threshold ) {
                $cost = 0;
            }
            
            $this->add_rate( array(
                'id' => $this->id . '_' . $rate['code'],
                'label' => $rate['service'],
                'cost' => $cost,
                'meta_data' => array(
                    'service_code' => $rate['code'],
                    'free_shipping_applied' => ( $cost === 0 && $free_threshold > 0 && $cart_total >= $free_threshold )
                )
            ) );
        }
    }
    
    /**
     * Group products by shipping location based on tags
     *
     * @param array $cart_items Cart items
     * @return array Products grouped by location
     */
    private function group_by_location( $cart_items ) {
        $grouped = array(
            'warehouse' => array(),
            'store' => array()
        );
        
        foreach ( $cart_items as $item ) {
            $product = $item['data'];
            $product_id = $product->get_id();
            
            // Get product tags
            $tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
            
            if ( is_wp_error( $tags ) ) {
                $tags = array();
            }
            
            $location = $this->determine_location( $tags );
            
            $grouped[ $location ][] = array(
                'product_id' => $product_id,
                'product' => $product,
                'quantity' => $item['quantity'],
                'weight' => floatval( $product->get_weight() ),
            );
        }
        
        // Remove empty locations
        return array_filter( $grouped );
    }
    
    /**
     * Determine shipping location based on product tags
     *
     * @param array $tags Product tags
     * @return string Location (warehouse or store)
     */
    private function determine_location( $tags ) {
        $has_warehouse_tag = in_array( 'SSAW-App', $tags, true );
        $has_store_tag = in_array( 'available-in-store', $tags, true );
        
        // Both tags present - use overlap preference
        if ( $has_warehouse_tag && $has_store_tag ) {
            $overlap_pref = $this->settings_data['overlap_preference'] ?? 'warehouse';
            return $overlap_pref;
        }
        
        // Single tag
        if ( $has_warehouse_tag ) {
            return 'warehouse';
        }
        
        if ( $has_store_tag ) {
            return 'store';
        }
        
        // No tags - use default location
        return $this->settings_data['default_location'] ?? 'warehouse';
    }
    
    /**
     * Get origin address for location
     *
     * @param string $location Location type (warehouse or store)
     * @return array|false Address data or false
     */
    private function get_origin_address( $location ) {
        $prefix = ( $location === 'warehouse' ) ? 'warehouse' : 'store';
        
        return array(
            'address' => $this->settings_data[ $prefix . '_address' ] ?? '',
            'city' => $this->settings_data[ $prefix . '_city' ] ?? '',
            'state' => $this->settings_data[ $prefix . '_state' ] ?? '',
            'zip' => $this->settings_data[ $prefix . '_zip' ] ?? '',
        );
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
     * Apply markup to rates based on product settings
     *
     * @param array $rates UPS rates
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
                    foreach ( $rates as $code => &$rate ) {
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
     * Combine rates from multiple origins
     *
     * @param array $all_rates Rates from all origins
     * @return array Combined rates
     */
    private function combine_rates( $all_rates ) {
        if ( count( $all_rates ) === 1 ) {
            return reset( $all_rates );
        }
        
        $strategy = $this->settings_data['multi_origin_strategy'] ?? 'highest';
        $combined = array();
        
        // Get all service codes
        $service_codes = array();
        foreach ( $all_rates as $rates ) {
            $service_codes = array_merge( $service_codes, array_keys( $rates ) );
        }
        $service_codes = array_unique( $service_codes );
        
        foreach ( $service_codes as $code ) {
            $costs = array();
            $service_name = '';
            
            foreach ( $all_rates as $rates ) {
                if ( isset( $rates[ $code ] ) ) {
                    $costs[] = $rates[ $code ]['cost'];
                    $service_name = $rates[ $code ]['service'];
                }
            }
            
            if ( empty( $costs ) ) {
                continue;
            }
            
            // Apply combination strategy
            switch ( $strategy ) {
                case 'sum':
                    $final_cost = array_sum( $costs );
                    break;
                case 'highest':
                default:
                    $final_cost = max( $costs );
                    break;
            }
            
            $combined[ $code ] = array(
                'service' => $service_name,
                'code' => $code,
                'cost' => $final_cost
            );
        }
        
        return $combined;
    }
    
    /**
     * Add fallback rates when API fails
     */
    private function add_fallback_rates() {
        $fallback = $this->settings_data['fallback_rates'] ?? array();
        
        $services = array(
            'ground' => __( 'UPS Ground', 'epic-marks-shipping' ),
            '2day' => __( 'UPS 2nd Day Air', 'epic-marks-shipping' ),
            'nextday' => __( 'UPS Next Day Air', 'epic-marks-shipping' )
        );
        
        foreach ( $services as $code => $name ) {
            if ( isset( $fallback[ $code ] ) && $fallback[ $code ] > 0 ) {
                $this->add_rate( array(
                    'id' => $this->id . '_' . $code,
                    'label' => $name . ' (Estimated)',
                    'cost' => floatval( $fallback[ $code ] )
                ) );
            }
        }
    }
}
