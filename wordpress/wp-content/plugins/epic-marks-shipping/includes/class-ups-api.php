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
}
