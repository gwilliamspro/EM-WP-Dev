<?php
/**
 * Location Manager Class
 *
 * Handles CRUD operations for shipping locations
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EM_Location_Manager class
 */
class EM_Location_Manager {

	/**
	 * Option name for storing locations
	 *
	 * @var string
	 */
	const OPTION_NAME = 'em_shipping_locations';

	/**
	 * Get all locations
	 *
	 * @return array Array of location objects
	 */
	public static function get_all_locations() {
		$locations = get_option( self::OPTION_NAME, array() );
		
		// Ensure it's an array
		if ( ! is_array( $locations ) ) {
			$locations = array();
		}

		return $locations;
	}

	/**
	 * Get location by ID
	 *
	 * @param string $location_id Location ID
	 * @return array|null Location data or null if not found
	 */
	public static function get_location( $location_id ) {
		$locations = self::get_all_locations();

		foreach ( $locations as $location ) {
			if ( isset( $location['id'] ) && $location['id'] === $location_id ) {
				return $location;
			}
		}

		return null;
	}

	/**
	 * Get locations by type
	 *
	 * @param string $type Location type (store, warehouse, ssaw_warehouse)
	 * @return array Array of locations matching the type
	 */
	public static function get_locations_by_type( $type ) {
		$locations = self::get_all_locations();
		$filtered = array();

		foreach ( $locations as $location ) {
			if ( isset( $location['type'] ) && $location['type'] === $type ) {
				$filtered[] = $location;
			}
		}

		return $filtered;
	}

	/**
	 * Get locations by group
	 *
	 * @param string $group Location group name
	 * @return array Array of locations in the group
	 */
	public static function get_locations_by_group( $group ) {
		$locations = self::get_all_locations();
		$filtered = array();

		foreach ( $locations as $location ) {
			if ( isset( $location['group'] ) && $location['group'] === $group ) {
				$filtered[] = $location;
			}
		}

		return $filtered;
	}

	/**
	 * Create a new location
	 *
	 * @param array $location_data Location data
	 * @return array|WP_Error Location data with generated ID or WP_Error on failure
	 */
	public static function create_location( $location_data ) {
		// Validate required fields
		$validation = self::validate_location_data( $location_data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Generate unique ID if not provided
		if ( empty( $location_data['id'] ) ) {
			$location_data['id'] = self::generate_location_id( $location_data['name'] );
		}

		// Check for duplicate ID
		if ( self::get_location( $location_data['id'] ) !== null ) {
			return new WP_Error( 'duplicate_id', __( 'A location with this ID already exists.', 'epic-marks-shipping' ) );
		}

		// Set defaults
		$location_data = self::set_location_defaults( $location_data );

		// Get existing locations
		$locations = self::get_all_locations();
		$locations[] = $location_data;

		// Save locations
		update_option( self::OPTION_NAME, $locations );

		// Fire action hook
		do_action( 'em_location_saved', $location_data, 'create' );

		return $location_data;
	}

	/**
	 * Update an existing location
	 *
	 * @param string $location_id Location ID
	 * @param array  $location_data Updated location data
	 * @return array|WP_Error Updated location data or WP_Error on failure
	 */
	public static function update_location( $location_id, $location_data ) {
		// Validate required fields
		$validation = self::validate_location_data( $location_data, $location_id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Get existing locations
		$locations = self::get_all_locations();
		$found = false;

		foreach ( $locations as $index => $location ) {
			if ( isset( $location['id'] ) && $location['id'] === $location_id ) {
				// Preserve the ID
				$location_data['id'] = $location_id;
				
				// Set defaults for missing fields
				$location_data = self::set_location_defaults( $location_data );
				
				$locations[ $index ] = $location_data;
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new WP_Error( 'location_not_found', __( 'Location not found.', 'epic-marks-shipping' ) );
		}

		// Save locations
		update_option( self::OPTION_NAME, $locations );

		// Fire action hook
		do_action( 'em_location_saved', $location_data, 'update' );

		return $location_data;
	}

	/**
	 * Delete a location
	 *
	 * @param string $location_id Location ID
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function delete_location( $location_id ) {
		$locations = self::get_all_locations();
		$found = false;
		$deleted_location = null;

		foreach ( $locations as $index => $location ) {
			if ( isset( $location['id'] ) && $location['id'] === $location_id ) {
				$deleted_location = $location;
				unset( $locations[ $index ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new WP_Error( 'location_not_found', __( 'Location not found.', 'epic-marks-shipping' ) );
		}

		// Re-index array
		$locations = array_values( $locations );

		// Save locations
		update_option( self::OPTION_NAME, $locations );

		// Fire action hook
		do_action( 'em_location_deleted', $deleted_location );

		return true;
	}

	/**
	 * Validate location data
	 *
	 * @param array  $data Location data to validate
	 * @param string $existing_id ID of existing location being updated (optional)
	 * @return bool|WP_Error True if valid, WP_Error if invalid
	 */
	private static function validate_location_data( $data, $existing_id = '' ) {
		// Required: name
		if ( empty( $data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Location name is required.', 'epic-marks-shipping' ) );
		}

		// Required: type
		if ( empty( $data['type'] ) ) {
			return new WP_Error( 'missing_type', __( 'Location type is required.', 'epic-marks-shipping' ) );
		}

		// Validate type
		$valid_types = array( 'store', 'warehouse', 'ssaw_warehouse' );
		if ( ! in_array( $data['type'], $valid_types, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid location type.', 'epic-marks-shipping' ) );
		}

		// SSAW warehouses require warehouse_code
		if ( $data['type'] === 'ssaw_warehouse' && empty( $data['warehouse_code'] ) ) {
			return new WP_Error( 'missing_warehouse_code', __( 'SSAW warehouse locations require a warehouse code.', 'epic-marks-shipping' ) );
		}

		// Validate address (required for all location types)
		if ( empty( $data['address'] ) || ! is_array( $data['address'] ) ) {
			return new WP_Error( 'missing_address', __( 'Location address is required.', 'epic-marks-shipping' ) );
		}

		// Validate address components
		$required_address_fields = array( 'city', 'state', 'zip', 'country' );
		foreach ( $required_address_fields as $field ) {
			if ( empty( $data['address'][ $field ] ) ) {
				return new WP_Error( 
					'missing_address_field', 
					sprintf( __( 'Address %s is required.', 'epic-marks-shipping' ), $field ) 
				);
			}
		}

		// Validate state (2-letter code)
		if ( strlen( $data['address']['state'] ) !== 2 ) {
			return new WP_Error( 'invalid_state', __( 'State must be a 2-letter code (e.g., TX, CA).', 'epic-marks-shipping' ) );
		}

		// Validate ZIP code (basic validation)
		if ( ! preg_match( '/^\d{5}(-\d{4})?$/', $data['address']['zip'] ) ) {
			return new WP_Error( 'invalid_zip', __( 'ZIP code must be 5 digits or 5+4 format (e.g., 78681 or 78681-1234).', 'epic-marks-shipping' ) );
		}

		// Validate status
		if ( isset( $data['status'] ) && ! in_array( $data['status'], array( 'active', 'inactive' ), true ) ) {
			return new WP_Error( 'invalid_status', __( 'Invalid status. Must be "active" or "inactive".', 'epic-marks-shipping' ) );
		}

		return true;
	}

	/**
	 * Set default values for location data
	 *
	 * @param array $data Location data
	 * @return array Location data with defaults
	 */
	private static function set_location_defaults( $data ) {
		$defaults = array(
			'group' => '',
			'warehouse_code' => null,
			'capabilities' => array( 'shipping' ),
			'services' => array( 'ground', '2day', 'nextday' ),
			'processing_time' => 1,
			'cutoff_time' => '14:00',
			'holidays' => 'countdown_timer',
			'priority' => 99,
			'status' => 'active',
		);

		// Set type-specific defaults
		if ( isset( $data['type'] ) ) {
			switch ( $data['type'] ) {
				case 'ssaw_warehouse':
					$defaults['capabilities'] = array( 'shipping' );
					$defaults['services'] = array( 'ground' );
					$defaults['processing_time'] = 0;
					$defaults['group'] = 'ssaw_warehouses';
					break;
				case 'store':
					$defaults['capabilities'] = array( 'shipping', 'pickup' );
					$defaults['group'] = 'retail_stores';
					break;
			}
		}

		return wp_parse_args( $data, $defaults );
	}

	/**
	 * Generate location ID from name
	 *
	 * @param string $name Location name
	 * @return string Generated location ID
	 */
	private static function generate_location_id( $name ) {
		$id = sanitize_title( $name );
		
		// Ensure uniqueness
		$counter = 1;
		$original_id = $id;
		while ( self::get_location( $id ) !== null ) {
			$id = $original_id . '-' . $counter;
			$counter++;
		}

		return $id;
	}

	/**
	 * Get active locations
	 *
	 * @return array Array of active locations
	 */
	public static function get_active_locations() {
		$locations = self::get_all_locations();
		$active = array();

		foreach ( $locations as $location ) {
			if ( isset( $location['status'] ) && $location['status'] === 'active' ) {
				$active[] = $location;
			}
		}

		return $active;
	}

	/**
	 * Get SSAW warehouses
	 *
	 * @return array Array of SSAW warehouse locations
	 */
	public static function get_ssaw_warehouses() {
		return self::get_locations_by_type( 'ssaw_warehouse' );
	}

	/**
	 * Format location address for display
	 *
	 * @param array $location Location data
	 * @return string Formatted address
	 */
	public static function format_address( $location ) {
		if ( empty( $location['address'] ) || ! is_array( $location['address'] ) ) {
			return '';
		}

		$address = $location['address'];
		$parts = array();

		if ( ! empty( $address['address_1'] ) ) {
			$parts[] = $address['address_1'];
		}

		$city_state_zip = array();
		if ( ! empty( $address['city'] ) ) {
			$city_state_zip[] = $address['city'];
		}
		if ( ! empty( $address['state'] ) ) {
			$city_state_zip[] = $address['state'];
		}
		if ( ! empty( $address['zip'] ) ) {
			$city_state_zip[] = $address['zip'];
		}

		if ( ! empty( $city_state_zip ) ) {
			$parts[] = implode( ' ', $city_state_zip );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Get location type label
	 *
	 * @param string $type Location type
	 * @return string Type label
	 */
	public static function get_type_label( $type ) {
		$labels = array(
			'store' => __( 'Retail Store', 'epic-marks-shipping' ),
			'warehouse' => __( 'Warehouse', 'epic-marks-shipping' ),
			'ssaw_warehouse' => __( 'SSAW Warehouse', 'epic-marks-shipping' ),
		);

		return $labels[ $type ] ?? $type;
	}

	/**
	 * Import SSAW warehouses from CSV
	 *
	 * @param string $csv_content CSV content
	 * @return array|WP_Error Array with import results or WP_Error on failure
	 */
	public static function import_ssaw_warehouses_csv( $csv_content ) {
		$rows = array_map( 'str_getcsv', explode( "\n", $csv_content ) );
		
		if ( empty( $rows ) ) {
			return new WP_Error( 'empty_csv', __( 'CSV file is empty.', 'epic-marks-shipping' ) );
		}

		// Get headers
		$headers = array_shift( $rows );
		
		$imported = 0;
		$errors = array();

		foreach ( $rows as $index => $row ) {
			// Skip empty rows
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Map row to data
			$data = array_combine( $headers, $row );
			
			// Build location data
			$location_data = array(
				'name' => $data['name'] ?? ( 'SSAW ' . ( $data['warehouse_code'] ?? 'Unknown' ) ),
				'type' => 'ssaw_warehouse',
				'warehouse_code' => $data['warehouse_code'] ?? '',
				'address' => array(
					'company' => $data['company'] ?? 'SS Activewear',
					'address_1' => $data['address_1'] ?? '',
					'city' => $data['city'] ?? '',
					'state' => $data['state'] ?? '',
					'zip' => $data['zip'] ?? '',
					'country' => $data['country'] ?? 'US',
				),
			);

			// Create location
			$result = self::create_location( $location_data );
			
			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					__( 'Row %d: %s', 'epic-marks-shipping' ),
					$index + 2, // +2 because of header and 0-index
					$result->get_error_message()
				);
			} else {
				$imported++;
			}
		}

		return array(
			'imported' => $imported,
			'errors' => $errors,
		);
	}

	/**
	 * Import SSAW warehouses from JSON
	 *
	 * @param string $json_content JSON content
	 * @return array|WP_Error Array with import results or WP_Error on failure
	 */
	public static function import_ssaw_warehouses_json( $json_content ) {
		$data = json_decode( $json_content, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'epic-marks-shipping' ) );
		}

		if ( empty( $data['warehouses'] ) || ! is_array( $data['warehouses'] ) ) {
			return new WP_Error( 'no_warehouses', __( 'No warehouses found in JSON.', 'epic-marks-shipping' ) );
		}

		$imported = 0;
		$errors = array();

		foreach ( $data['warehouses'] as $index => $warehouse ) {
			// Build location data
			$location_data = array(
				'name' => $warehouse['name'] ?? ( 'SSAW ' . ( $warehouse['warehouse_code'] ?? 'Unknown' ) ),
				'type' => 'ssaw_warehouse',
				'warehouse_code' => $warehouse['warehouse_code'] ?? '',
				'address' => $warehouse['address'] ?? $warehouse['estimated_address'] ?? array(),
			);

			// Create location
			$result = self::create_location( $location_data );
			
			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					__( 'Warehouse %d: %s', 'epic-marks-shipping' ),
					$index + 1,
					$result->get_error_message()
				);
			} else {
				$imported++;
			}
		}

		return array(
			'imported' => $imported,
			'errors' => $errors,
		);
	}
}
