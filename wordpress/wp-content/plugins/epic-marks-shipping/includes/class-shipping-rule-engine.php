<?php
/**
 * Shipping Rule Engine
 *
 * Evaluates conditional free shipping rules based on cart contents, order total,
 * product tags, categories, and other criteria.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 * @since      2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping Rule Engine class.
 *
 * @since 2.1.0
 */
class EM_Shipping_Rule_Engine {

	/**
	 * Evaluate all active rules against the current cart and package.
	 *
	 * @since 2.1.0
	 *
	 * @param array $cart    WooCommerce cart data.
	 * @param array $package Shipping package data.
	 * @return array|null Rule actions if a rule matches, null otherwise.
	 */
	public static function evaluate_rules( $cart, $package ) {
		$rules = get_option( 'em_shipping_rules', array() );

		if ( empty( $rules ) ) {
			return null;
		}

		// Sort by priority (lower number = higher priority).
		usort( $rules, function( $a, $b ) {
			return ( $a['priority'] ?? 999 ) <=> ( $b['priority'] ?? 999 );
		});

		// Evaluate each rule in priority order.
		foreach ( $rules as $rule ) {
			// Skip inactive rules.
			if ( isset( $rule['status'] ) && $rule['status'] !== 'active' ) {
				continue;
			}

			// Check if conditions are met.
			if ( self::conditions_met( $rule['conditions'] ?? array(), $cart, $package ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( 'Shipping rule matched: %s (ID: %s)', $rule['name'] ?? 'Unnamed', $rule['id'] ?? 'unknown' ) );
				}

				return $rule['actions'] ?? array();
			}
		}

		return null;
	}

	/**
	 * Check if all conditions in a rule are met.
	 *
	 * @since 2.1.0
	 *
	 * @param array $conditions Rule conditions.
	 * @param array $cart       WooCommerce cart data.
	 * @param array $package    Shipping package data.
	 * @return bool True if conditions are met, false otherwise.
	 */
	private static function conditions_met( $conditions, $cart, $package ) {
		if ( empty( $conditions['rules'] ) ) {
			return false;
		}

		$type    = $conditions['type'] ?? 'all'; // 'all' or 'any'
		$results = array();

		foreach ( $conditions['rules'] as $condition ) {
			$results[] = self::check_condition( $condition, $cart, $package );
		}

		// 'all' = AND logic (all must be true), 'any' = OR logic (at least one true).
		return $type === 'all' ? ! in_array( false, $results, true ) : in_array( true, $results, true );
	}

	/**
	 * Check a single condition.
	 *
	 * @since 2.1.0
	 *
	 * @param array $condition Condition to check.
	 * @param array $cart      WooCommerce cart data.
	 * @param array $package   Shipping package data.
	 * @return bool True if condition passes, false otherwise.
	 */
	private static function check_condition( $condition, $cart, $package ) {
		$field    = $condition['field'] ?? '';
		$operator = $condition['operator'] ?? '';
		$value    = $condition['value'] ?? '';

		switch ( $field ) {
			case 'profile':
				return self::check_profile_condition( $operator, $value, $package );

			case 'order_total':
				return self::check_order_total_condition( $operator, $value, $cart );

			case 'product_tag':
				return self::check_product_tag_condition( $operator, $value, $package );

			case 'product_category':
				return self::check_product_category_condition( $operator, $value, $package );

			case 'item_count':
				return self::check_item_count_condition( $operator, $value, $package );

			case 'total_weight':
				return self::check_total_weight_condition( $operator, $value, $package );

			case 'shipping_location':
				return self::check_shipping_location_condition( $operator, $value, $package );

			case 'customer_role':
				return self::check_customer_role_condition( $operator, $value );

			default:
				// Unknown field, allow filtering for custom conditions.
				return apply_filters( 'em_shipping_rule_check_condition', false, $condition, $cart, $package );
		}
	}

	/**
	 * Check shipping profile condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Value to compare against.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_profile_condition( $operator, $value, $package ) {
		// Get primary profile from package.
		$package_profile = $package['em_profile_id'] ?? null;

		if ( ! $package_profile ) {
			return false;
		}

		switch ( $operator ) {
			case 'is':
				return $package_profile === $value;

			case 'is_not':
				return $package_profile !== $value;

			default:
				return false;
		}
	}

	/**
	 * Check order total condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Value to compare against.
	 * @param array  $cart     WooCommerce cart data.
	 * @return bool
	 */
	private static function check_order_total_condition( $operator, $value, $cart ) {
		$cart_total = WC()->cart ? WC()->cart->get_subtotal() : 0;

		switch ( $operator ) {
			case '>=':
				return $cart_total >= (float) $value;

			case '<=':
				return $cart_total <= (float) $value;

			case '>':
				return $cart_total > (float) $value;

			case '<':
				return $cart_total < (float) $value;

			case '=':
				return abs( $cart_total - (float) $value ) < 0.01;

			case 'between':
				// Value should be array [min, max].
				if ( is_array( $value ) && count( $value ) === 2 ) {
					return $cart_total >= (float) $value[0] && $cart_total <= (float) $value[1];
				}
				return false;

			default:
				return false;
		}
	}

	/**
	 * Check product tag condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Tag slug(s) to check.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_product_tag_condition( $operator, $value, $package ) {
		$product_tags = array();

		// Collect all tags from products in package.
		foreach ( $package['contents'] as $item ) {
			$product_id = $item['product_id'];
			$tags       = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'slugs' ) );

			if ( ! is_wp_error( $tags ) ) {
				$product_tags = array_merge( $product_tags, $tags );
			}
		}

		$product_tags = array_unique( $product_tags );

		switch ( $operator ) {
			case 'has':
				// At least one product has the tag.
				return in_array( $value, $product_tags, true );

			case 'has_all':
				// All specified tags must be present.
				$required_tags = is_array( $value ) ? $value : array( $value );
				return count( array_intersect( $required_tags, $product_tags ) ) === count( $required_tags );

			case 'has_any':
				// At least one of the specified tags must be present.
				$required_tags = is_array( $value ) ? $value : array( $value );
				return count( array_intersect( $required_tags, $product_tags ) ) > 0;

			case 'has_not':
				// None of the products have the tag.
				return ! in_array( $value, $product_tags, true );

			default:
				return false;
		}
	}

	/**
	 * Check product category condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Category slug(s) to check.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_product_category_condition( $operator, $value, $package ) {
		$product_categories = array();

		// Collect all categories from products in package.
		foreach ( $package['contents'] as $item ) {
			$product_id = $item['product_id'];
			$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );

			if ( ! is_wp_error( $categories ) ) {
				$product_categories = array_merge( $product_categories, $categories );
			}
		}

		$product_categories = array_unique( $product_categories );

		switch ( $operator ) {
			case 'has':
				return in_array( $value, $product_categories, true );

			case 'has_all':
				$required_categories = is_array( $value ) ? $value : array( $value );
				return count( array_intersect( $required_categories, $product_categories ) ) === count( $required_categories );

			case 'has_any':
				$required_categories = is_array( $value ) ? $value : array( $value );
				return count( array_intersect( $required_categories, $product_categories ) ) > 0;

			case 'has_not':
				return ! in_array( $value, $product_categories, true );

			default:
				return false;
		}
	}

	/**
	 * Check item count condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Value to compare against.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_item_count_condition( $operator, $value, $package ) {
		$item_count = 0;

		foreach ( $package['contents'] as $item ) {
			$item_count += $item['quantity'];
		}

		switch ( $operator ) {
			case '>=':
				return $item_count >= (int) $value;

			case '<=':
				return $item_count <= (int) $value;

			case '>':
				return $item_count > (int) $value;

			case '<':
				return $item_count < (int) $value;

			case '=':
				return $item_count === (int) $value;

			default:
				return false;
		}
	}

	/**
	 * Check total weight condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Value to compare against.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_total_weight_condition( $operator, $value, $package ) {
		$total_weight = 0;

		foreach ( $package['contents'] as $item ) {
			$product = $item['data'];
			$weight  = (float) $product->get_weight();
			$total_weight += $weight * $item['quantity'];
		}

		switch ( $operator ) {
			case '>=':
				return $total_weight >= (float) $value;

			case '<=':
				return $total_weight <= (float) $value;

			case '>':
				return $total_weight > (float) $value;

			case '<':
				return $total_weight < (float) $value;

			case '=':
				return abs( $total_weight - (float) $value ) < 0.01;

			default:
				return false;
		}
	}

	/**
	 * Check shipping location condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Location ID or group name.
	 * @param array  $package  Shipping package data.
	 * @return bool
	 */
	private static function check_shipping_location_condition( $operator, $value, $package ) {
		$package_location = $package['em_location_id'] ?? null;

		if ( ! $package_location ) {
			return false;
		}

		switch ( $operator ) {
			case 'is':
				return $package_location === $value;

			case 'is_not':
				return $package_location !== $value;

			case 'in_group':
				// Check if location belongs to a group.
				$location = EM_Location_Manager::get_location( $package_location );
				return $location && isset( $location['group'] ) && $location['group'] === $value;

			default:
				return false;
		}
	}

	/**
	 * Check customer role condition.
	 *
	 * @since 2.1.0
	 *
	 * @param string $operator Comparison operator.
	 * @param mixed  $value    Role name to check.
	 * @return bool
	 */
	private static function check_customer_role_condition( $operator, $value ) {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			// Guest user.
			$user_role = 'guest';
		} else {
			$user_role = $user->roles[0] ?? 'guest';
		}

		switch ( $operator ) {
			case 'is':
				return $user_role === $value;

			case 'is_not':
				return $user_role !== $value;

			default:
				return false;
		}
	}

	/**
	 * Get all configured rules.
	 *
	 * @since 2.1.0
	 *
	 * @return array Array of rules.
	 */
	public static function get_all_rules() {
		return get_option( 'em_shipping_rules', array() );
	}

	/**
	 * Get a single rule by ID.
	 *
	 * @since 2.1.0
	 *
	 * @param string $rule_id Rule ID.
	 * @return array|null Rule data or null if not found.
	 */
	public static function get_rule( $rule_id ) {
		$rules = self::get_all_rules();

		foreach ( $rules as $rule ) {
			if ( isset( $rule['id'] ) && $rule['id'] === $rule_id ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Save a rule.
	 *
	 * @since 2.1.0
	 *
	 * @param array $rule_data Rule data to save.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function save_rule( $rule_data ) {
		// Validate required fields.
		if ( empty( $rule_data['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Rule name is required.', 'epic-marks-shipping' ) );
		}

		if ( empty( $rule_data['conditions'] ) ) {
			return new WP_Error( 'missing_conditions', __( 'At least one condition is required.', 'epic-marks-shipping' ) );
		}

		if ( empty( $rule_data['actions'] ) ) {
			return new WP_Error( 'missing_actions', __( 'At least one action is required.', 'epic-marks-shipping' ) );
		}

		$rules = self::get_all_rules();

		// Generate ID if new rule.
		if ( empty( $rule_data['id'] ) ) {
			$rule_data['id'] = 'rule_' . uniqid();
		}

		// Check if rule exists (update) or is new (create).
		$rule_exists = false;
		foreach ( $rules as $index => $rule ) {
			if ( $rule['id'] === $rule_data['id'] ) {
				$rules[ $index ] = $rule_data;
				$rule_exists     = true;
				break;
			}
		}

		if ( ! $rule_exists ) {
			$rules[] = $rule_data;
		}

		// Save to database.
		update_option( 'em_shipping_rules', $rules );

		return true;
	}

	/**
	 * Delete a rule by ID.
	 *
	 * @since 2.1.0
	 *
	 * @param string $rule_id Rule ID to delete.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_rule( $rule_id ) {
		$rules = self::get_all_rules();
		$found = false;

		foreach ( $rules as $index => $rule ) {
			if ( $rule['id'] === $rule_id ) {
				unset( $rules[ $index ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return false;
		}

		// Re-index array and save.
		$rules = array_values( $rules );
		update_option( 'em_shipping_rules', $rules );

		return true;
	}

	/**
	 * Migrate legacy free shipping threshold to a rule.
	 *
	 * @since 2.1.0
	 *
	 * @return bool True if migration occurred, false otherwise.
	 */
	public static function migrate_free_shipping_threshold() {
		$settings = get_option( 'em_ups_settings', array() );
		$threshold = $settings['free_shipping_threshold'] ?? null;

		if ( empty( $threshold ) || $threshold <= 0 ) {
			return false;
		}

		// Check if migration already occurred.
		$rules = self::get_all_rules();
		foreach ( $rules as $rule ) {
			if ( isset( $rule['migrated_from_threshold'] ) && $rule['migrated_from_threshold'] ) {
				return false; // Already migrated.
			}
		}

		// Create rule from threshold.
		$rule = array(
			'id'                       => 'legacy_free_shipping',
			'name'                     => __( 'Free Shipping (Legacy)', 'epic-marks-shipping' ),
			'priority'                 => 999,
			'conditions'               => array(
				'type'  => 'all',
				'rules' => array(
					array(
						'field'    => 'order_total',
						'operator' => '>=',
						'value'    => (float) $threshold,
					),
				),
			),
			'actions'                  => array(
				'free_shipping'        => true,
				'applies_to_services'  => array( 'ground' ),
			),
			'status'                   => 'active',
			'migrated_from_threshold'  => true,
		);

		self::save_rule( $rule );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Migrated free shipping threshold ($%.2f) to rule engine', $threshold ) );
		}

		return true;
	}
}
