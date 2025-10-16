<?php
/**
 * Rules Tab
 *
 * Admin interface for managing conditional free shipping rules.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/admin/tabs
 * @since      2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle delete action.
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['rule_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_rule_' . $_GET['rule_id'] ) ) {
		$deleted = EM_Shipping_Rule_Engine::delete_rule( sanitize_text_field( $_GET['rule_id'] ) );
		
		if ( $deleted ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule deleted successfully.', 'epic-marks-shipping' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to delete rule.', 'epic-marks-shipping' ) . '</p></div>';
		}
	}
}

// Get all rules.
$rules = EM_Shipping_Rule_Engine::get_all_rules();

// Sort by priority.
usort( $rules, function( $a, $b ) {
	return ( $a['priority'] ?? 999 ) <=> ( $b['priority'] ?? 999 );
});

?>

<div class="em-rules-tab">
	<div class="em-tab-header">
		<h2><?php esc_html_e( 'Free Shipping Rules', 'epic-marks-shipping' ); ?></h2>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules&action=new' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Add New Rule', 'epic-marks-shipping' ); ?>
		</a>
	</div>

	<div class="em-rules-notice" style="margin-top: 15px;">
		<p style="font-size: 13px; color: #666;">
			<?php esc_html_e( 'Rules are evaluated in priority order (lower number = higher priority). The first matching rule will apply free shipping.', 'epic-marks-shipping' ); ?>
		</p>
		<p style="font-size: 13px; color: #666;">
			<?php
			printf(
				/* translators: %s: Legacy free shipping threshold setting name */
				esc_html__( 'Note: The legacy "Free Shipping Threshold" setting in the Setup tab has been replaced by this rule system. Your existing threshold has been automatically migrated.', 'epic-marks-shipping' )
			);
			?>
		</p>
	</div>

	<?php if ( empty( $rules ) ) : ?>
		<div class="em-empty-state" style="margin-top: 30px;">
			<div class="em-empty-icon" style="text-align: center; font-size: 48px; color: #ddd; margin-bottom: 15px;">
				📋
			</div>
			<h3 style="text-align: center; color: #666;">
				<?php esc_html_e( 'No Rules Yet', 'epic-marks-shipping' ); ?>
			</h3>
			<p style="text-align: center; color: #999; margin-bottom: 20px;">
				<?php esc_html_e( 'Create your first free shipping rule to offer conditional free shipping based on order total, product tags, shipping profile, and more.', 'epic-marks-shipping' ); ?>
			</p>
			<p style="text-align: center;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules&action=new' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( 'Create Your First Rule', 'epic-marks-shipping' ); ?>
				</a>
			</p>

			<div class="em-rule-examples" style="margin-top: 40px; padding: 20px; background: #f5f5f5; border-radius: 4px;">
				<h4 style="margin-top: 0;"><?php esc_html_e( 'Example Rules:', 'epic-marks-shipping' ); ?></h4>
				<ul style="list-style: none; padding: 0;">
					<li style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
						<strong><?php esc_html_e( 'SSAW Free Shipping:', 'epic-marks-shipping' ); ?></strong>
						<?php esc_html_e( 'Profile is "SSAW Products" AND Order Total ≥ $300 → Free Ground Shipping', 'epic-marks-shipping' ); ?>
					</li>
					<li style="padding: 8px 0; border-bottom: 1px solid #e0e0e0;">
						<strong><?php esc_html_e( 'DTF Free Shipping:', 'epic-marks-shipping' ); ?></strong>
						<?php esc_html_e( 'Product has tag "DTF" AND Order Total ≥ $50 → Free Ground Shipping', 'epic-marks-shipping' ); ?>
					</li>
					<li style="padding: 8px 0;">
						<strong><?php esc_html_e( 'Wholesale Free Shipping:', 'epic-marks-shipping' ); ?></strong>
						<?php esc_html_e( 'Customer role is "Wholesale" AND Item Count ≥ 12 → Free Ground Shipping', 'epic-marks-shipping' ); ?>
					</li>
				</ul>
			</div>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped em-rules-table" style="margin-top: 20px;">
			<thead>
				<tr>
					<th style="width: 50px;"><?php esc_html_e( 'Priority', 'epic-marks-shipping' ); ?></th>
					<th><?php esc_html_e( 'Rule Name', 'epic-marks-shipping' ); ?></th>
					<th><?php esc_html_e( 'Conditions', 'epic-marks-shipping' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'epic-marks-shipping' ); ?></th>
					<th style="width: 80px;"><?php esc_html_e( 'Status', 'epic-marks-shipping' ); ?></th>
					<th style="width: 150px;"><?php esc_html_e( 'Actions', 'epic-marks-shipping' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rules as $rule ) : ?>
					<tr>
						<td>
							<span class="em-priority-badge" style="display: inline-block; background: #0073aa; color: white; padding: 2px 8px; border-radius: 3px; font-weight: bold; font-size: 11px;">
								<?php echo esc_html( $rule['priority'] ?? '999' ); ?>
							</span>
						</td>
						<td>
							<strong><?php echo esc_html( $rule['name'] ?? __( 'Unnamed Rule', 'epic-marks-shipping' ) ); ?></strong>
							<?php if ( isset( $rule['migrated_from_threshold'] ) && $rule['migrated_from_threshold'] ) : ?>
								<span class="em-badge-migrated" style="display: inline-block; background: #f0f0f0; color: #666; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">
									<?php esc_html_e( 'MIGRATED', 'epic-marks-shipping' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							$conditions = $rule['conditions'] ?? array();
							$type       = $conditions['type'] ?? 'all';
							$cond_rules = $conditions['rules'] ?? array();
							
							if ( ! empty( $cond_rules ) ) {
								$condition_strings = array();
								foreach ( $cond_rules as $cond ) {
									$field    = $cond['field'] ?? '';
									$operator = $cond['operator'] ?? '';
									$value    = $cond['value'] ?? '';
									
									// Format value for display.
									if ( is_array( $value ) ) {
										$value = implode( ', ', $value );
									}
									
									$condition_strings[] = sprintf( '<code>%s %s %s</code>', esc_html( $field ), esc_html( $operator ), esc_html( $value ) );
								}
								
								$logic_label = $type === 'all' ? __( 'AND', 'epic-marks-shipping' ) : __( 'OR', 'epic-marks-shipping' );
								echo '<div style="font-size: 12px;">' . implode( " <strong>{$logic_label}</strong> ", $condition_strings ) . '</div>';
							} else {
								echo '<span style="color: #999;">' . esc_html__( 'No conditions', 'epic-marks-shipping' ) . '</span>';
							}
							?>
						</td>
						<td>
							<?php
							$actions = $rule['actions'] ?? array();
							if ( isset( $actions['free_shipping'] ) && $actions['free_shipping'] ) {
								$services = $actions['applies_to_services'] ?? array( 'all' );
								if ( $services === array( 'all' ) || in_array( 'all', $services, true ) ) {
									echo '<span style="color: #46b450; font-weight: bold;">✓ ' . esc_html__( 'Free Shipping (All Services)', 'epic-marks-shipping' ) . '</span>';
								} else {
									$service_labels = array_map( 'strtoupper', $services );
									echo '<span style="color: #46b450; font-weight: bold;">✓ ' . esc_html__( 'Free Shipping', 'epic-marks-shipping' ) . ' (' . implode( ', ', $service_labels ) . ')</span>';
								}
							}
							?>
						</td>
						<td>
							<?php
							$status = $rule['status'] ?? 'active';
							if ( $status === 'active' ) {
								echo '<span class="em-badge-active" style="display: inline-block; background: #46b450; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__( 'Active', 'epic-marks-shipping' ) . '</span>';
							} else {
								echo '<span class="em-badge-inactive" style="display: inline-block; background: #dc3232; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' . esc_html__( 'Inactive', 'epic-marks-shipping' ) . '</span>';
							}
							?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules&action=edit&rule_id=' . urlencode( $rule['id'] ?? '' ) ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'epic-marks-shipping' ); ?>
							</a>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=epic-marks-shipping&tab=rules&action=delete&rule_id=' . urlencode( $rule['id'] ?? '' ) ), 'delete_rule_' . $rule['id'], '_wpnonce' ) ); ?>" 
							   class="button button-small button-link-delete" 
							   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this rule?', 'epic-marks-shipping' ) ); ?>');"
							   style="color: #b32d2e;">
								<?php esc_html_e( 'Delete', 'epic-marks-shipping' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="em-rules-footer" style="margin-top: 20px;">
			<p style="font-size: 12px; color: #666;">
				<?php
				printf(
					/* translators: %d: Number of active rules */
					esc_html( _n( '%d rule configured', '%d rules configured', count( $rules ), 'epic-marks-shipping' ) ),
					count( $rules )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
