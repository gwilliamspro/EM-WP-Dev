<?php
/**
 * Rule Edit Form
 *
 * Create and edit individual shipping rules.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/admin
 * @since      2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get rule ID from URL (if editing).
$rule_id = isset( $_GET['rule_id'] ) ? sanitize_text_field( $_GET['rule_id'] ) : null;
$is_new  = empty( $rule_id );

// Load existing rule or create new.
if ( ! $is_new ) {
	$rule = EM_Shipping_Rule_Engine::get_rule( $rule_id );
	if ( ! $rule ) {
		wp_die( esc_html__( 'Rule not found.', 'epic-marks-shipping' ) );
	}
} else {
	// Default new rule structure.
	$rule = array(
		'id'         => '',
		'name'       => '',
		'priority'   => 10,
		'conditions' => array(
			'type'  => 'all',
			'rules' => array(
				array(
					'field'    => 'order_total',
					'operator' => '>=',
					'value'    => '',
				),
			),
		),
		'actions'    => array(
			'free_shipping'       => true,
			'applies_to_services' => array( 'ground' ),
		),
		'status'     => 'active',
	);
}

// Handle form submission.
if ( isset( $_POST['save_rule'] ) && check_admin_referer( 'em_save_rule', 'em_rule_nonce' ) ) {
	// Sanitize and build rule data.
	$rule_data = array(
		'id'       => $is_new ? '' : $rule_id,
		'name'     => sanitize_text_field( $_POST['rule_name'] ?? '' ),
		'priority' => absint( $_POST['rule_priority'] ?? 10 ),
		'status'   => sanitize_text_field( $_POST['rule_status'] ?? 'active' ),
	);

	// Build conditions.
	$conditions_type  = sanitize_text_field( $_POST['conditions_type'] ?? 'all' );
	$condition_fields = $_POST['condition_field'] ?? array();
	$condition_ops    = $_POST['condition_operator'] ?? array();
	$condition_values = $_POST['condition_value'] ?? array();

	$conditions_rules = array();
	if ( ! empty( $condition_fields ) ) {
		foreach ( $condition_fields as $index => $field ) {
			$operator = $condition_ops[ $index ] ?? '';
			$value    = $condition_values[ $index ] ?? '';

			// Convert numeric values.
			if ( in_array( $field, array( 'order_total', 'item_count', 'total_weight' ), true ) ) {
				$value = floatval( $value );
			}

			// Handle multi-value fields (tags, categories).
			if ( in_array( $field, array( 'product_tag', 'product_category' ), true ) && in_array( $operator, array( 'has_all', 'has_any' ), true ) ) {
				$value = array_map( 'trim', explode( ',', $value ) );
			}

			$conditions_rules[] = array(
				'field'    => sanitize_text_field( $field ),
				'operator' => sanitize_text_field( $operator ),
				'value'    => $value,
			);
		}
	}

	$rule_data['conditions'] = array(
		'type'  => $conditions_type,
		'rules' => $conditions_rules,
	);

	// Build actions.
	$free_shipping        = isset( $_POST['action_free_shipping'] );
	$applies_to_services  = $_POST['applies_to_services'] ?? array();

	$rule_data['actions'] = array(
		'free_shipping'       => $free_shipping,
		'applies_to_services' => array_map( 'sanitize_text_field', $applies_to_services ),
	);

	// Save rule.
	$result = EM_Shipping_Rule_Engine::save_rule( $rule_data );

	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} else {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule saved successfully.', 'epic-marks-shipping' ) . '</p></div>';
		// Redirect to rules list.
		echo '<script>window.location.href = "' . esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules' ) ) . '";</script>';
	}
}

// Get available shipping profiles for dropdown.
$profiles = EM_Profile_Manager::get_all_profiles();

?>

<div class="em-rule-edit">
	<div class="em-tab-header">
		<h2>
			<?php
			if ( $is_new ) {
				esc_html_e( 'Add New Rule', 'epic-marks-shipping' );
			} else {
				esc_html_e( 'Edit Rule', 'epic-marks-shipping' );
			}
			?>
		</h2>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules' ) ); ?>" class="button">
			<?php esc_html_e( '← Back to Rules', 'epic-marks-shipping' ); ?>
		</a>
	</div>

	<form method="post" action="" class="em-rule-form" style="margin-top: 20px;">
		<?php wp_nonce_field( 'em_save_rule', 'em_rule_nonce' ); ?>

		<!-- Basic Information -->
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="rule_name"><?php esc_html_e( 'Rule Name', 'epic-marks-shipping' ); ?> <span style="color: red;">*</span></label>
				</th>
				<td>
					<input type="text" id="rule_name" name="rule_name" value="<?php echo esc_attr( $rule['name'] ?? '' ); ?>" class="regular-text" required />
					<p class="description"><?php esc_html_e( 'A descriptive name for this rule (e.g., "SSAW Free Shipping", "DTF Free Shipping").', 'epic-marks-shipping' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="rule_priority"><?php esc_html_e( 'Priority', 'epic-marks-shipping' ); ?></label>
				</th>
				<td>
					<input type="number" id="rule_priority" name="rule_priority" value="<?php echo esc_attr( $rule['priority'] ?? 10 ); ?>" min="1" max="999" style="width: 80px;" />
					<p class="description"><?php esc_html_e( 'Lower numbers = higher priority. First matching rule will apply (default: 10).', 'epic-marks-shipping' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="rule_status"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></label>
				</th>
				<td>
					<select id="rule_status" name="rule_status">
						<option value="active" <?php selected( $rule['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'epic-marks-shipping' ); ?></option>
						<option value="inactive" <?php selected( $rule['status'] ?? 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'epic-marks-shipping' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Inactive rules are not evaluated during checkout.', 'epic-marks-shipping' ); ?></p>
				</td>
			</tr>
		</table>

		<hr style="margin: 30px 0;" />

		<!-- Conditions -->
		<h3><?php esc_html_e( 'Conditions', 'epic-marks-shipping' ); ?></h3>
		<p><?php esc_html_e( 'Define when this rule should apply:', 'epic-marks-shipping' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="conditions_type"><?php esc_html_e( 'Match Type', 'epic-marks-shipping' ); ?></label>
				</th>
				<td>
					<select id="conditions_type" name="conditions_type">
						<option value="all" <?php selected( $rule['conditions']['type'] ?? 'all', 'all' ); ?>><?php esc_html_e( 'Match ALL conditions (AND)', 'epic-marks-shipping' ); ?></option>
						<option value="any" <?php selected( $rule['conditions']['type'] ?? 'all', 'any' ); ?>><?php esc_html_e( 'Match ANY condition (OR)', 'epic-marks-shipping' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<div id="em-conditions-builder" style="margin-top: 15px;">
			<?php
			$conditions_rules = $rule['conditions']['rules'] ?? array();
			if ( empty( $conditions_rules ) ) {
				$conditions_rules = array(
					array(
						'field'    => 'order_total',
						'operator' => '>=',
						'value'    => '',
					),
				);
			}

			foreach ( $conditions_rules as $index => $condition ) :
				?>
				<div class="em-condition-row" style="margin-bottom: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
						<select name="condition_field[]" class="em-condition-field" style="width: 180px;">
							<option value="profile" <?php selected( $condition['field'] ?? '', 'profile' ); ?>><?php esc_html_e( 'Shipping Profile', 'epic-marks-shipping' ); ?></option>
							<option value="order_total" <?php selected( $condition['field'] ?? '', 'order_total' ); ?>><?php esc_html_e( 'Order Total', 'epic-marks-shipping' ); ?></option>
							<option value="product_tag" <?php selected( $condition['field'] ?? '', 'product_tag' ); ?>><?php esc_html_e( 'Product Tag', 'epic-marks-shipping' ); ?></option>
							<option value="product_category" <?php selected( $condition['field'] ?? '', 'product_category' ); ?>><?php esc_html_e( 'Product Category', 'epic-marks-shipping' ); ?></option>
							<option value="item_count" <?php selected( $condition['field'] ?? '', 'item_count' ); ?>><?php esc_html_e( 'Item Count', 'epic-marks-shipping' ); ?></option>
							<option value="total_weight" <?php selected( $condition['field'] ?? '', 'total_weight' ); ?>><?php esc_html_e( 'Total Weight', 'epic-marks-shipping' ); ?></option>
							<option value="shipping_location" <?php selected( $condition['field'] ?? '', 'shipping_location' ); ?>><?php esc_html_e( 'Shipping Location', 'epic-marks-shipping' ); ?></option>
							<option value="customer_role" <?php selected( $condition['field'] ?? '', 'customer_role' ); ?>><?php esc_html_e( 'Customer Role', 'epic-marks-shipping' ); ?></option>
						</select>

						<select name="condition_operator[]" class="em-condition-operator" style="width: 120px;">
							<option value=">=" <?php selected( $condition['operator'] ?? '', '>=' ); ?>><?php esc_html_e( '≥ (greater or equal)', 'epic-marks-shipping' ); ?></option>
							<option value="<=" <?php selected( $condition['operator'] ?? '', '<=' ); ?>><?php esc_html_e( '≤ (less or equal)', 'epic-marks-shipping' ); ?></option>
							<option value=">" <?php selected( $condition['operator'] ?? '', '>' ); ?>><?php esc_html_e( '> (greater than)', 'epic-marks-shipping' ); ?></option>
							<option value="<" <?php selected( $condition['operator'] ?? '', '<' ); ?>><?php esc_html_e( '< (less than)', 'epic-marks-shipping' ); ?></option>
							<option value="=" <?php selected( $condition['operator'] ?? '', '=' ); ?>><?php esc_html_e( '= (equals)', 'epic-marks-shipping' ); ?></option>
							<option value="is" <?php selected( $condition['operator'] ?? '', 'is' ); ?>><?php esc_html_e( 'is', 'epic-marks-shipping' ); ?></option>
							<option value="is_not" <?php selected( $condition['operator'] ?? '', 'is_not' ); ?>><?php esc_html_e( 'is not', 'epic-marks-shipping' ); ?></option>
							<option value="has" <?php selected( $condition['operator'] ?? '', 'has' ); ?>><?php esc_html_e( 'has', 'epic-marks-shipping' ); ?></option>
							<option value="has_all" <?php selected( $condition['operator'] ?? '', 'has_all' ); ?>><?php esc_html_e( 'has all', 'epic-marks-shipping' ); ?></option>
							<option value="has_any" <?php selected( $condition['operator'] ?? '', 'has_any' ); ?>><?php esc_html_e( 'has any', 'epic-marks-shipping' ); ?></option>
							<option value="has_not" <?php selected( $condition['operator'] ?? '', 'has_not' ); ?>><?php esc_html_e( 'has not', 'epic-marks-shipping' ); ?></option>
							<option value="in_group" <?php selected( $condition['operator'] ?? '', 'in_group' ); ?>><?php esc_html_e( 'in group', 'epic-marks-shipping' ); ?></option>
						</select>

						<input type="text" name="condition_value[]" value="<?php echo esc_attr( is_array( $condition['value'] ?? '' ) ? implode( ', ', $condition['value'] ) : $condition['value'] ?? '' ); ?>" class="em-condition-value" style="width: 250px;" placeholder="<?php esc_attr_e( 'Value', 'epic-marks-shipping' ); ?>" />

						<button type="button" class="button button-small em-remove-condition" style="color: #b32d2e;">
							<?php esc_html_e( 'Remove', 'epic-marks-shipping' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<button type="button" id="em-add-condition" class="button button-secondary" style="margin-top: 10px;">
			<?php esc_html_e( '+ Add Condition', 'epic-marks-shipping' ); ?>
		</button>

		<div class="em-condition-help" style="margin-top: 15px; padding: 15px; background: #fff9e6; border-left: 4px solid #ffb900;">
			<strong><?php esc_html_e( 'Condition Examples:', 'epic-marks-shipping' ); ?></strong>
			<ul style="margin: 10px 0 0 20px;">
				<li><code>profile is ssaw-products</code> - <?php esc_html_e( 'Products assigned to SSAW shipping profile', 'epic-marks-shipping' ); ?></li>
				<li><code>order_total >= 300</code> - <?php esc_html_e( 'Order subtotal is $300 or more', 'epic-marks-shipping' ); ?></li>
				<li><code>product_tag has dtf</code> - <?php esc_html_e( 'At least one product has "dtf" tag', 'epic-marks-shipping' ); ?></li>
				<li><code>product_tag has_any dtf,print</code> - <?php esc_html_e( 'Has either "dtf" OR "print" tag (comma-separated)', 'epic-marks-shipping' ); ?></li>
				<li><code>item_count >= 12</code> - <?php esc_html_e( 'Total quantity is 12 or more items', 'epic-marks-shipping' ); ?></li>
				<li><code>customer_role is wholesale</code> - <?php esc_html_e( 'Customer has "wholesale" role', 'epic-marks-shipping' ); ?></li>
			</ul>
		</div>

		<hr style="margin: 30px 0;" />

		<!-- Actions -->
		<h3><?php esc_html_e( 'Actions', 'epic-marks-shipping' ); ?></h3>
		<p><?php esc_html_e( 'Define what happens when conditions are met:', 'epic-marks-shipping' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="action_free_shipping"><?php esc_html_e( 'Free Shipping', 'epic-marks-shipping' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" id="action_free_shipping" name="action_free_shipping" value="1" <?php checked( $rule['actions']['free_shipping'] ?? true, true ); ?> />
						<?php esc_html_e( 'Offer free shipping when conditions are met', 'epic-marks-shipping' ); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Applies To Services', 'epic-marks-shipping' ); ?></label>
				</th>
				<td>
					<?php
					$applies_to = $rule['actions']['applies_to_services'] ?? array( 'ground' );
					$services   = array(
						'ground'  => __( 'UPS Ground', 'epic-marks-shipping' ),
						'2day'    => __( 'UPS 2nd Day Air', 'epic-marks-shipping' ),
						'nextday' => __( 'UPS Next Day Air', 'epic-marks-shipping' ),
						'3day'    => __( 'UPS 3 Day Select', 'epic-marks-shipping' ),
						'saver'   => __( 'UPS Worldwide Saver', 'epic-marks-shipping' ),
					);

					foreach ( $services as $service_code => $service_name ) :
						?>
						<label style="display: block; margin-bottom: 5px;">
							<input type="checkbox" name="applies_to_services[]" value="<?php echo esc_attr( $service_code ); ?>" <?php checked( in_array( $service_code, $applies_to, true ), true ); ?> />
							<?php echo esc_html( $service_name ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Select which shipping services should be free when this rule matches. Typically "Ground" for most free shipping offers.', 'epic-marks-shipping' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="save_rule" class="button button-primary button-large">
				<?php esc_html_e( 'Save Rule', 'epic-marks-shipping' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules' ) ); ?>" class="button button-large">
				<?php esc_html_e( 'Cancel', 'epic-marks-shipping' ); ?>
			</a>
		</p>
	</form>
</div>
