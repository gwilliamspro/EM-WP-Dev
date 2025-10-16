<?php
/**
 * Shipping Fee Calculator
 *
 * Calculates transparent shipping fees like fragile handling, overweight surcharges,
 * and signature requirements. Separates transparent fees from hidden markups.
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
 * Shipping Fee Calculator Class
 *
 * Handles calculation of transparent shipping fees that are displayed
 * as separate line items at checkout (vs hidden markups that are baked into rates).
 *
 * @since 2.3.0
 */
class EM_Shipping_Fee_Calculator {

	/**
	 * Calculate all applicable fees for a package.
	 *
	 * @since 2.3.0
	 * @param array  $package Package contents array.
	 * @param object $profile Shipping profile object.
	 * @return array {
	 *     Array of fee objects.
	 *
	 *     @type array $fee {
	 *         @type string $label Fee label for display.
	 *         @type float  $cost  Fee cost.
	 *         @type string $type  Fee type ('transparent' or 'hidden').
	 *     }
	 * }
	 */
	public static function calculate_fees( $package, $profile = null ) {
		$fees = array();

		// Check for fragile items
		if ( self::has_fragile_items( $package['contents'] ) ) {
			$fragile_fee = self::get_fragile_fee( $profile );
			if ( $fragile_fee > 0 ) {
				$fees[] = array(
					'label' => __( 'Fragile Item Handling', 'epic-marks-shipping' ),
					'cost'  => $fragile_fee,
					'type'  => 'transparent',
				);
			}
		}

		// Check for overweight package
		$weight = self::calculate_weight( $package['contents'] );
		if ( $weight > 50 ) {
			$fees[] = array(
				'label' => __( 'Overweight Surcharge', 'epic-marks-shipping' ),
				'cost'  => 5.00,
				'type'  => 'transparent',
			);
		}

		// Check for signature requirement
		if ( self::requires_signature( $package['contents'] ) ) {
			$fees[] = array(
				'label' => __( 'Signature Required', 'epic-marks-shipping' ),
				'cost'  => 5.00,
				'type'  => 'transparent',
			);
		}

		/**
		 * Filter calculated shipping fees.
		 *
		 * @since 2.3.0
		 * @param array  $fees    Array of fee objects.
		 * @param array  $package Package contents array.
		 * @param object $profile Shipping profile object.
		 */
		return apply_filters( 'em_shipping_fees', $fees, $package, $profile );
	}

	/**
	 * Check if package contains fragile items.
	 *
	 * @since 2.3.0
	 * @param array $items Package items.
	 * @return bool True if package contains fragile items.
	 */
	private static function has_fragile_items( $items ) {
		foreach ( $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : 0;
			$requires_fragile = get_post_meta( $product_id, '_requires_fragile_handling', true );
			
			if ( '1' === $requires_fragile || 'yes' === $requires_fragile ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Check if package requires signature.
	 *
	 * @since 2.3.0
	 * @param array $items Package items.
	 * @return bool True if package requires signature.
	 */
	private static function requires_signature( $items ) {
		foreach ( $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : 0;
			$requires_signature = get_post_meta( $product_id, '_requires_signature', true );
			
			if ( '1' === $requires_signature || 'yes' === $requires_signature ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Calculate total package weight.
	 *
	 * @since 2.3.0
	 * @param array $items Package items.
	 * @return float Total weight in pounds.
	 */
	private static function calculate_weight( $items ) {
		$total_weight = 0;

		foreach ( $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? $item['product_id'] : 0;
			$product = wc_get_product( $product_id );
			
			if ( $product ) {
				$weight = (float) $product->get_weight();
				$quantity = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
				$total_weight += ( $weight * $quantity );
			}
		}

		return $total_weight;
	}

	/**
	 * Get fragile handling fee for profile.
	 *
	 * @since 2.3.0
	 * @param object $profile Shipping profile object.
	 * @return float Fragile fee amount.
	 */
	private static function get_fragile_fee( $profile ) {
		if ( ! $profile ) {
			return 3.00; // Default fragile fee
		}

		// Check profile settings for custom fragile fee
		if ( isset( $profile->fragile_handling_fee ) && $profile->fragile_handling_fee > 0 ) {
			return (float) $profile->fragile_handling_fee;
		}

		return 3.00; // Default
	}

	/**
	 * Calculate total transparent fees.
	 *
	 * @since 2.3.0
	 * @param array $fees Array of fee objects from calculate_fees().
	 * @return float Total transparent fees.
	 */
	public static function get_total_transparent_fees( $fees ) {
		$total = 0;

		foreach ( $fees as $fee ) {
			if ( 'transparent' === $fee['type'] ) {
				$total += (float) $fee['cost'];
			}
		}

		return $total;
	}

	/**
	 * Format fees for display at checkout.
	 *
	 * @since 2.3.0
	 * @param array $fees Array of fee objects from calculate_fees().
	 * @return string HTML formatted fee breakdown.
	 */
	public static function format_fee_breakdown( $fees ) {
		if ( empty( $fees ) ) {
			return '';
		}

		$output = '<div class="em-fee-breakdown">';

		foreach ( $fees as $fee ) {
			if ( 'transparent' === $fee['type'] ) {
				$output .= sprintf(
					'<div class="em-fee-item"><span class="em-fee-label">+ %s</span><span class="em-fee-cost">$%.2f</span></div>',
					esc_html( $fee['label'] ),
					$fee['cost']
				);
			}
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Add fees to WooCommerce cart.
	 *
	 * @since 2.3.0
	 * @param array $fees Array of fee objects from calculate_fees().
	 * @return void
	 */
	public static function add_fees_to_cart( $fees ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		foreach ( $fees as $fee ) {
			if ( 'transparent' === $fee['type'] && $fee['cost'] > 0 ) {
				WC()->cart->add_fee( $fee['label'], $fee['cost'] );
			}
		}
	}

	/**
	 * Get fee explanation text for customer.
	 *
	 * @since 2.3.0
	 * @param string $fee_type Fee type (fragile, overweight, signature).
	 * @return string Explanation text.
	 */
	public static function get_fee_explanation( $fee_type ) {
		$explanations = array(
			'fragile'    => __( 'Fragile items require special packaging materials and handling to ensure safe delivery.', 'epic-marks-shipping' ),
			'overweight' => __( 'Packages over 50 lbs require additional handling and may incur carrier surcharges.', 'epic-marks-shipping' ),
			'signature'  => __( 'High-value items require signature confirmation to protect against theft or loss.', 'epic-marks-shipping' ),
		);

		return isset( $explanations[ $fee_type ] ) ? $explanations[ $fee_type ] : '';
	}
}
