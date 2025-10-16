<?php
/**
 * UPS API Wrapper Class
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EM_UPS_API {
    
    /**
     * UPS API endpoints
     */
    const PRODUCTION_URL = 'https://onlinetools.ups.com/ship/v1/rating/Rate';
    const SANDBOX_URL = 'https://wwwcie.ups.com/ship/v1/rating/Rate';
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Test mode flag
     */
    private $test_mode;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option( 'em_ups_settings', array() );
        $this->test_mode = isset( $this->settings['test_mode'] ) && $this->settings['test_mode'] === 'yes';
    }
    
    /**
     * Get UPS shipping rates
     *
     * @param array $params Rate request parameters
     * @return array|WP_Error Rates array or error
     */
    public function get_rates( $params ) {
        // Validate required parameters
        if ( empty( $params['origin_zip'] ) || empty( $params['destination_zip'] ) || empty( $params['weight'] ) ) {
            return new WP_Error( 'missing_params', __( 'Missing required shipping parameters', 'epic-marks-shipping' ) );
        }
        
        // Check cache first
        $cache_key = $this->get_cache_key( $params );
        $cached_rates = get_transient( $cache_key );
        
        if ( false !== $cached_rates ) {
            $this->log( 'Cache hit for: ' . $cache_key );
            return $cached_rates;
        }
        
        // Build request payload
        $request_data = $this->build_request( $params );
        
        // Make API call
        $response = $this->make_request( $request_data );
        
        if ( is_wp_error( $response ) ) {
            $this->log( 'UPS API Error: ' . $response->get_error_message(), 'error' );
            return $response;
        }
        
        // Parse response
        $rates = $this->parse_response( $response );
        
        if ( ! is_wp_error( $rates ) ) {
            // Cache successful response for 30 minutes
            set_transient( $cache_key, $rates, 30 * MINUTE_IN_SECONDS );
            $this->log( 'Cached rates for: ' . $cache_key );
        }
        
        return $rates;
    }
    
    /**
     * Build UPS API request payload
     *
     * @param array $params Request parameters
     * @return array Request payload
     */
    private function build_request( $params ) {
        $origin = $params['origin'] ?? array();
        $destination = $params['destination'] ?? array();
        
        return array(
            'RateRequest' => array(
                'Request' => array(
                    'TransactionReference' => array(
                        'CustomerContext' => 'Epic Marks Rate Request'
                    )
                ),
                'Shipment' => array(
                    'Shipper' => array(
                        'Address' => array(
                            'AddressLine' => array( $origin['address'] ?? ''),
                            'City' => $origin['city'] ?? '',
                            'StateProvinceCode' => $origin['state'] ?? '',
                            'PostalCode' => $params['origin_zip'],
                            'CountryCode' => 'US'
                        )
                    ),
                    'ShipTo' => array(
                        'Address' => array(
                            'AddressLine' => array( $destination['address'] ?? ''),
                            'City' => $destination['city'] ?? '',
                            'StateProvinceCode' => $destination['state'] ?? '',
                            'PostalCode' => $params['destination_zip'],
                            'CountryCode' => 'US'
                        )
                    ),
                    'ShipFrom' => array(
                        'Address' => array(
                            'AddressLine' => array( $origin['address'] ?? ''),
                            'City' => $origin['city'] ?? '',
                            'StateProvinceCode' => $origin['state'] ?? '',
                            'PostalCode' => $params['origin_zip'],
                            'CountryCode' => 'US'
                        )
                    ),
                    'Service' => array(
                        'Code' => '03' // Ground - will request all services
                    ),
                    'Package' => array(
                        'PackagingType' => array(
                            'Code' => '02' // Customer supplied package
                        ),
                        'PackageWeight' => array(
                            'UnitOfMeasurement' => array(
                                'Code' => 'LBS'
                            ),
                            'Weight' => (string) $params['weight']
                        )
                    )
                )
            )
        );
    }
    
    /**
     * Make HTTP request to UPS API
     *
     * @param array $data Request payload
     * @return array|WP_Error Response data or error
     */
    private function make_request( $data ) {
        $url = $this->test_mode ? self::SANDBOX_URL : self::PRODUCTION_URL;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'AccessLicenseNumber' => $this->settings['ups_access_key'] ?? '',
            'Username' => $this->settings['ups_user_id'] ?? '',
            'Password' => $this->settings['ups_password'] ?? '',
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode( $data ),
            'timeout' => 10,
            'sslverify' => ! $this->test_mode
        );
        
        $this->log( 'Making UPS API request to: ' . $url );
        
        $response = wp_remote_post( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        if ( 200 !== $response_code ) {
            return new WP_Error(
                'ups_api_error',
                sprintf( __( 'UPS API returned error code: %d', 'epic-marks-shipping' ), $response_code )
            );
        }
        
        $decoded = json_decode( $response_body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', __( 'Invalid JSON response from UPS', 'epic-marks-shipping' ) );
        }
        
        return $decoded;
    }
    
    /**
     * Parse UPS API response
     *
     * @param array $response API response
     * @return array|WP_Error Parsed rates or error
     */
    private function parse_response( $response ) {
        if ( isset( $response['Fault'] ) || isset( $response['response']['errors'] ) ) {
            $error_msg = $response['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description'] 
                ?? $response['response']['errors'][0]['message'] 
                ?? __( 'Unknown UPS API error', 'epic-marks-shipping' );
            return new WP_Error( 'ups_error', $error_msg );
        }
        
        $rates = array();
        
        if ( isset( $response['RateResponse']['RatedShipment'] ) ) {
            $shipments = $response['RateResponse']['RatedShipment'];
            
            // Ensure array format (single result might not be in array)
            if ( ! isset( $shipments[0] ) ) {
                $shipments = array( $shipments );
            }
            
            foreach ( $shipments as $shipment ) {
                $service_code = $shipment['Service']['Code'] ?? '';
                $cost = $shipment['TotalCharges']['MonetaryValue'] ?? 0;
                
                $rates[ $service_code ] = array(
                    'service' => $this->get_service_name( $service_code ),
                    'code' => $service_code,
                    'cost' => floatval( $cost )
                );
            }
        }
        
        return $rates;
    }
    
    /**
     * Get service name from UPS service code
     *
     * @param string $code Service code
     * @return string Service name
     */
    private function get_service_name( $code ) {
        $services = array(
            '03' => 'UPS Ground',
            '02' => 'UPS 2nd Day Air',
            '01' => 'UPS Next Day Air',
            '12' => 'UPS 3 Day Select',
            '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early',
        );
        
        return $services[ $code ] ?? sprintf( __( 'UPS Service %s', 'epic-marks-shipping' ), $code );
    }
    
    /**
     * Generate cache key for rate request
     *
     * @param array $params Request parameters
     * @return string Cache key
     */
    private function get_cache_key( $params ) {
        $key_parts = array(
            $params['origin_zip'],
            $params['destination_zip'],
            $params['weight']
        );
        
        return 'em_ups_rate_' . md5( implode( '_', $key_parts ) );
    }
    
    /**
     * Log message (if WP_DEBUG is enabled)
     *
     * @param string $message Log message
     * @param string $level Log level
     */
    private function log( $message, $level = 'info' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( '[Epic Marks Shipping - %s] %s', strtoupper( $level ), $message ) );
        }
    }

    /**
     * Static method to get a single rate for SSAW warehouse selection
     *
     * Used by EM_SSAW_Warehouse_Selector to calculate rates from multiple warehouses.
     *
     * @param array  $origin_address  Origin warehouse address array with keys: address_1, city, state, zip, country
     * @param array  $dest_address    Destination customer address array
     * @param array  $package         WooCommerce package array with contents
     * @param string $service         UPS service code ('ground', '2day', 'nextday', etc.)
     * @return array|WP_Error Rate data with 'cost' and 'transit_days', or WP_Error on failure
     */
    public static function get_rate( $origin_address, $dest_address, $package, $service = 'ground' ) {
        // Map service names to UPS service codes
        $service_codes = array(
            'ground'  => '03',
            '2day'    => '02',
            'nextday' => '01',
            '3day'    => '12',
            'saver'   => '13',
        );

        $service_code = isset( $service_codes[ $service ] ) ? $service_codes[ $service ] : '03';

        // Calculate package weight
        $weight = self::calculate_package_weight( $package );

        // Build cache key including origin (for multi-warehouse support)
        $origin_zip = isset( $origin_address['zip'] ) ? $origin_address['zip'] : '';
        $dest_zip   = isset( $dest_address['postcode'] ) ? $dest_address['postcode'] : '';
        $cache_key  = sprintf(
            'em_ups_rate_%s_%s_%s_%.2f',
            sanitize_text_field( $origin_zip ),
            sanitize_text_field( $dest_zip ),
            $service_code,
            $weight
        );

        // Check cache
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        // Get UPS settings
        $settings = get_option( 'em_ups_settings', array() );
        $test_mode = isset( $settings['test_mode'] ) && 'yes' === $settings['test_mode'];

        // Build request
        $request_data = array(
            'RateRequest' => array(
                'Request' => array(
                    'TransactionReference' => array(
                        'CustomerContext' => 'Epic Marks SSAW Rate Request'
                    )
                ),
                'Shipment' => array(
                    'Shipper' => array(
                        'Address' => array(
                            'AddressLine' => array( $origin_address['address_1'] ?? '' ),
                            'City' => $origin_address['city'] ?? '',
                            'StateProvinceCode' => $origin_address['state'] ?? '',
                            'PostalCode' => $origin_zip,
                            'CountryCode' => $origin_address['country'] ?? 'US'
                        )
                    ),
                    'ShipTo' => array(
                        'Address' => array(
                            'City' => $dest_address['city'] ?? '',
                            'StateProvinceCode' => $dest_address['state'] ?? '',
                            'PostalCode' => $dest_zip,
                            'CountryCode' => $dest_address['country'] ?? 'US'
                        )
                    ),
                    'ShipFrom' => array(
                        'Address' => array(
                            'AddressLine' => array( $origin_address['address_1'] ?? '' ),
                            'City' => $origin_address['city'] ?? '',
                            'StateProvinceCode' => $origin_address['state'] ?? '',
                            'PostalCode' => $origin_zip,
                            'CountryCode' => $origin_address['country'] ?? 'US'
                        )
                    ),
                    'Service' => array(
                        'Code' => $service_code
                    ),
                    'Package' => array(
                        'PackagingType' => array(
                            'Code' => '02' // Customer supplied package
                        ),
                        'PackageWeight' => array(
                            'UnitOfMeasurement' => array(
                                'Code' => 'LBS'
                            ),
                            'Weight' => (string) $weight
                        )
                    )
                )
            )
        );

        // Make API call
        $url = $test_mode ? self::SANDBOX_URL : self::PRODUCTION_URL;

        $headers = array(
            'Content-Type' => 'application/json',
            'AccessLicenseNumber' => $settings['ups_access_key'] ?? '',
            'Username' => $settings['ups_user_id'] ?? '',
            'Password' => $settings['ups_password'] ?? '',
        );

        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode( $request_data ),
            'timeout' => 10,
            'sslverify' => ! $test_mode
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( 200 !== $response_code ) {
            return new WP_Error(
                'ups_api_error',
                sprintf( __( 'UPS API returned error code: %d', 'epic-marks-shipping' ), $response_code )
            );
        }

        $decoded = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', __( 'Invalid JSON response from UPS', 'epic-marks-shipping' ) );
        }

        // Parse response
        if ( isset( $decoded['Fault'] ) || isset( $decoded['response']['errors'] ) ) {
            $error_msg = $decoded['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description']
                ?? $decoded['response']['errors'][0]['message']
                ?? __( 'Unknown UPS API error', 'epic-marks-shipping' );
            return new WP_Error( 'ups_error', $error_msg );
        }

        if ( ! isset( $decoded['RateResponse']['RatedShipment'] ) ) {
            return new WP_Error( 'no_rate', __( 'No rate returned from UPS', 'epic-marks-shipping' ) );
        }

        $shipment = $decoded['RateResponse']['RatedShipment'];

        // Handle single result (not in array)
        if ( isset( $shipment['Service'] ) ) {
            $shipment = array( $shipment );
        }

        // Get the first (and should be only) result for this service
        $rate_data = array(
            'cost' => floatval( $shipment[0]['TotalCharges']['MonetaryValue'] ?? 0 ),
            'transit_days' => intval( $shipment[0]['GuaranteedDelivery']['BusinessDaysInTransit'] ?? 5 ),
            'service_code' => $service_code,
            'service' => $service,
        );

        // Cache for 30 minutes
        set_transient( $cache_key, $rate_data, 30 * MINUTE_IN_SECONDS );

        return $rate_data;
    }

    /**
     * Calculate total weight of package contents
     *
     * @param array $package WooCommerce package array
     * @return float Weight in pounds
     */
    private static function calculate_package_weight( $package ) {
        $weight = 0;

        if ( isset( $package['contents'] ) && is_array( $package['contents'] ) ) {
            foreach ( $package['contents'] as $item ) {
                if ( isset( $item['data'] ) ) {
                    $product = $item['data'];
                    $qty = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
                    $item_weight = floatval( $product->get_weight() );
                    $weight += $item_weight * $qty;
                }
            }
        }

        // Minimum 1 lb
        return max( 1.0, $weight );
    }
}
