<?php
/**
 * Delivery Date Estimator
 *
 * Calculates estimated delivery dates based on processing time, transit time,
 * business days, and holiday calendars.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 * @since      2.3.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Delivery Estimator Class
 *
 * Handles delivery date estimation with business day calculation,
 * processing time, transit time, and holiday support.
 *
 * @since 2.3.0
 */
class EM_Delivery_Estimator {

	/**
	 * Calculate delivery estimate for a shipment.
	 *
	 * @since 2.3.0
	 * @param array  $location          Location configuration array.
	 * @param string $service           UPS service code (ground, 2day, nextday, etc.).
	 * @param array  $customer_address  Customer shipping address.
	 * @param int    $transit_days      Transit days from UPS API (optional).
	 * @return array {
	 *     Delivery estimate data.
	 *
	 *     @type int    $ship_date       Unix timestamp of ship date.
	 *     @type int    $delivery_date   Unix timestamp of estimated delivery.
	 *     @type string $delivery_range  Formatted delivery range string.
	 *     @type int    $processing_days Processing days calculated.
	 *     @type int    $transit_days    Transit days used.
	 * }
	 */
	public static function estimate( $location, $service, $customer_address, $transit_days = null ) {
		// Get processing time
		$processing_days = self::get_processing_time( $location );

		// Get transit time
		if ( null === $transit_days ) {
			$transit_days = self::get_transit_time( $service );
		}

		// Get holidays for this location
		$holidays = self::get_holidays( $location );

		// Calculate ship date (order date + processing time)
		$ship_date = self::add_business_days( current_time( 'timestamp' ), $processing_days, $holidays );

		// Calculate delivery date (ship date + transit time)
		$carrier_holidays = self::get_carrier_holidays();
		$delivery_date = self::add_business_days( $ship_date, $transit_days, $carrier_holidays );

		// Create delivery range (delivery date +/- 2 days buffer)
		$delivery_range = self::format_range( $delivery_date, 2 );

		return array(
			'ship_date'       => $ship_date,
			'delivery_date'   => $delivery_date,
			'delivery_range'  => $delivery_range,
			'processing_days' => $processing_days,
			'transit_days'    => $transit_days,
		);
	}

	/**
	 * Get processing time in business days for a location.
	 *
	 * @since 2.3.0
	 * @param array $location Location configuration array.
	 * @return int Processing days.
	 */
	private static function get_processing_time( $location ) {
		$location_type = isset( $location['type'] ) ? $location['type'] : 'warehouse';

		// SSAW warehouses: Same-day processing (0 days)
		if ( 'ssaw_warehouse' === $location_type ) {
			return 0;
		}

		// Store locations: Check cutoff time
		if ( 'store' === $location_type ) {
			$cutoff = isset( $location['cutoff_time'] ) ? $location['cutoff_time'] : '14:00';
			$now = current_time( 'H:i' );

			// If before cutoff, ships same day (0 days)
			// If after cutoff, ships next business day (1 day)
			return ( $now < $cutoff ) ? 0 : 1;
		}

		// Default warehouse: 1 business day
		return isset( $location['processing_time'] ) ? (int) $location['processing_time'] : 1;
	}

	/**
	 * Get estimated transit time for UPS service.
	 *
	 * @since 2.3.0
	 * @param string $service UPS service code.
	 * @return int Transit days.
	 */
	private static function get_transit_time( $service ) {
		$transit_times = array(
			'nextday' => 1,
			'2day'    => 2,
			'3day'    => 3,
			'ground'  => 5, // Average for UPS Ground
			'saver'   => 1,
		);

		return isset( $transit_times[ $service ] ) ? $transit_times[ $service ] : 5;
	}

	/**
	 * Add business days to a timestamp, skipping weekends and holidays.
	 *
	 * @since 2.3.0
	 * @param int   $start_timestamp Starting timestamp.
	 * @param int   $days            Number of business days to add.
	 * @param array $holidays        Array of holiday dates (Y-m-d format).
	 * @return int Resulting timestamp.
	 */
	private static function add_business_days( $start_timestamp, $days, $holidays = array() ) {
		if ( $days <= 0 ) {
			return $start_timestamp;
		}

		$current = new DateTime();
		$current->setTimestamp( $start_timestamp );
		$added = 0;

		while ( $added < $days ) {
			$current->modify( '+1 day' );

			// Skip weekends (Saturday = 6, Sunday = 7)
			$day_of_week = (int) $current->format( 'N' );
			if ( 6 === $day_of_week || 7 === $day_of_week ) {
				continue;
			}

			// Skip holidays
			$date_string = $current->format( 'Y-m-d' );
			if ( in_array( $date_string, $holidays, true ) ) {
				continue;
			}

			$added++;
		}

		return $current->getTimestamp();
	}

	/**
	 * Get holiday dates for a location.
	 *
	 * @since 2.3.0
	 * @param array $location Location configuration array.
	 * @return array Holiday dates in Y-m-d format.
	 */
	private static function get_holidays( $location ) {
		$holidays_source = isset( $location['holidays'] ) ? $location['holidays'] : 'carrier';

		// Store locations: Reuse countdown timer holiday list
		if ( 'countdown_timer' === $holidays_source ) {
			$countdown_holidays = get_option( 'em_countdown_holidays', array() );
			
			// Convert to Y-m-d format if needed
			$formatted_holidays = array();
			foreach ( $countdown_holidays as $holiday ) {
				if ( is_array( $holiday ) && isset( $holiday['date'] ) ) {
					$formatted_holidays[] = $holiday['date'];
				} elseif ( is_string( $holiday ) ) {
					$formatted_holidays[] = $holiday;
				}
			}
			
			return $formatted_holidays;
		}

		// Use carrier holidays (UPS/FedEx holidays)
		return self::get_carrier_holidays();
	}

	/**
	 * Get carrier holiday calendar (UPS/FedEx standard holidays).
	 *
	 * @since 2.3.0
	 * @return array Holiday dates in Y-m-d format.
	 */
	private static function get_carrier_holidays() {
		$year = (int) current_time( 'Y' );
		
		// Major US shipping holidays
		$holidays = array(
			sprintf( '%d-01-01', $year ),     // New Year's Day
			sprintf( '%d-07-04', $year ),     // Independence Day
			sprintf( '%d-12-25', $year ),     // Christmas
		);

		// Calculate dynamic holidays
		$holidays[] = self::get_memorial_day( $year );      // Last Monday in May
		$holidays[] = self::get_labor_day( $year );         // First Monday in September
		$holidays[] = self::get_thanksgiving_day( $year );  // Fourth Thursday in November

		/**
		 * Filter carrier holiday calendar.
		 *
		 * @since 2.3.0
		 * @param array $holidays Holiday dates in Y-m-d format.
		 * @param int   $year     Current year.
		 */
		return apply_filters( 'em_carrier_holidays', $holidays, $year );
	}

	/**
	 * Calculate Memorial Day (last Monday in May).
	 *
	 * @since 2.3.0
	 * @param int $year Year.
	 * @return string Date in Y-m-d format.
	 */
	private static function get_memorial_day( $year ) {
		$date = new DateTime( "last monday of may {$year}" );
		return $date->format( 'Y-m-d' );
	}

	/**
	 * Calculate Labor Day (first Monday in September).
	 *
	 * @since 2.3.0
	 * @param int $year Year.
	 * @return string Date in Y-m-d format.
	 */
	private static function get_labor_day( $year ) {
		$date = new DateTime( "first monday of september {$year}" );
		return $date->format( 'Y-m-d' );
	}

	/**
	 * Calculate Thanksgiving Day (fourth Thursday in November).
	 *
	 * @since 2.3.0
	 * @param int $year Year.
	 * @return string Date in Y-m-d format.
	 */
	private static function get_thanksgiving_day( $year ) {
		$date = new DateTime( "fourth thursday of november {$year}" );
		return $date->format( 'Y-m-d' );
	}

	/**
	 * Format delivery date as a range.
	 *
	 * @since 2.3.0
	 * @param int $delivery_timestamp Delivery date timestamp.
	 * @param int $buffer_days        Buffer days (+/-).
	 * @return string Formatted date range (e.g., "Dec 18-20, 2025").
	 */
	private static function format_range( $delivery_timestamp, $buffer_days = 2 ) {
		$start = new DateTime();
		$start->setTimestamp( $delivery_timestamp );
		$start->modify( "-{$buffer_days} days" );

		$end = new DateTime();
		$end->setTimestamp( $delivery_timestamp );
		$end->modify( "+{$buffer_days} days" );

		// Same month
		if ( $start->format( 'm' ) === $end->format( 'm' ) ) {
			return sprintf(
				'%s-%s, %s',
				$start->format( 'M j' ),
				$end->format( 'j' ),
				$end->format( 'Y' )
			);
		}

		// Different months
		return sprintf(
			'%s-%s, %s',
			$start->format( 'M j' ),
			$end->format( 'M j' ),
			$end->format( 'Y' )
		);
	}

	/**
	 * Format delivery estimate for display at checkout.
	 *
	 * @since 2.3.0
	 * @param array $estimate Delivery estimate array from estimate().
	 * @return string Formatted delivery estimate text.
	 */
	public static function format_estimate( $estimate ) {
		if ( empty( $estimate['delivery_range'] ) ) {
			return '';
		}

		return sprintf(
			/* translators: %s: Delivery date range (e.g., Dec 18-20, 2025) */
			__( 'Estimated delivery: %s', 'epic-marks-shipping' ),
			esc_html( $estimate['delivery_range'] )
		);
	}

	/**
	 * Get cutoff message for location.
	 *
	 * @since 2.3.0
	 * @param array $location Location configuration array.
	 * @return string Cutoff message.
	 */
	public static function get_cutoff_message( $location ) {
		$location_type = isset( $location['type'] ) ? $location['type'] : 'warehouse';
		
		if ( 'store' === $location_type ) {
			$cutoff = isset( $location['cutoff_time'] ) ? $location['cutoff_time'] : '14:00';
			$now = current_time( 'H:i' );
			
			// Convert 24-hour to 12-hour format for display
			$cutoff_obj = DateTime::createFromFormat( 'H:i', $cutoff );
			$cutoff_display = $cutoff_obj ? $cutoff_obj->format( 'g:i A' ) : '2:00 PM';
			
			if ( $now < $cutoff ) {
				return sprintf(
					/* translators: %s: Cutoff time (e.g., 2:00 PM) */
					__( 'Order by %s CT for same-day shipping', 'epic-marks-shipping' ),
					$cutoff_display
				);
			} else {
				return __( 'Orders placed now ship next business day', 'epic-marks-shipping' );
			}
		}
		
		return '';
	}
}
