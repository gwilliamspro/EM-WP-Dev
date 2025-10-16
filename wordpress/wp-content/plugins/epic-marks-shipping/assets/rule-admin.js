/**
 * Rule Admin JavaScript
 *
 * Handles dynamic condition builder for shipping rules.
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/assets
 * @since      2.1.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize rule admin functionality.
	 */
	$(document).ready(function() {
		// Add new condition row.
		$('#em-add-condition').on('click', function() {
			const newRow = `
				<div class="em-condition-row" style="margin-bottom: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
						<select name="condition_field[]" class="em-condition-field" style="width: 180px;">
							<option value="profile">Shipping Profile</option>
							<option value="order_total" selected>Order Total</option>
							<option value="product_tag">Product Tag</option>
							<option value="product_category">Product Category</option>
							<option value="item_count">Item Count</option>
							<option value="total_weight">Total Weight</option>
							<option value="shipping_location">Shipping Location</option>
							<option value="customer_role">Customer Role</option>
						</select>

						<select name="condition_operator[]" class="em-condition-operator" style="width: 120px;">
							<option value=">=" selected>≥ (greater or equal)</option>
							<option value="<=">≤ (less or equal)</option>
							<option value=">"> (greater than)</option>
							<option value="<">< (less than)</option>
							<option value="=">=  (equals)</option>
							<option value="is">is</option>
							<option value="is_not">is not</option>
							<option value="has">has</option>
							<option value="has_all">has all</option>
							<option value="has_any">has any</option>
							<option value="has_not">has not</option>
							<option value="in_group">in group</option>
						</select>

						<input type="text" name="condition_value[]" value="" class="em-condition-value" style="width: 250px;" placeholder="Value" />

						<button type="button" class="button button-small em-remove-condition" style="color: #b32d2e;">
							Remove
						</button>
					</div>
				</div>
			`;

			$('#em-conditions-builder').append(newRow);
			updateOperators();
		});

		// Remove condition row.
		$(document).on('click', '.em-remove-condition', function() {
			const conditionRows = $('.em-condition-row');
			
			// Don't allow removing the last condition.
			if (conditionRows.length > 1) {
				$(this).closest('.em-condition-row').remove();
			} else {
				alert('At least one condition is required.');
			}
		});

		// Update operator options based on field type.
		$(document).on('change', '.em-condition-field', function() {
			updateOperators();
		});

		/**
		 * Update operator dropdowns based on selected field types.
		 */
		function updateOperators() {
			$('.em-condition-row').each(function() {
				const row = $(this);
				const field = row.find('.em-condition-field').val();
				const operatorSelect = row.find('.em-condition-operator');
				const currentOperator = operatorSelect.val();

				// Define operators for each field type.
				const operatorsByField = {
					'profile': ['is', 'is_not'],
					'order_total': ['>=', '<=', '>', '<', '='],
					'product_tag': ['has', 'has_all', 'has_any', 'has_not'],
					'product_category': ['has', 'has_all', 'has_any', 'has_not'],
					'item_count': ['>=', '<=', '>', '<', '='],
					'total_weight': ['>=', '<=', '>', '<', '='],
					'shipping_location': ['is', 'is_not', 'in_group'],
					'customer_role': ['is', 'is_not']
				};

				const operatorLabels = {
					'>=': '≥ (greater or equal)',
					'<=': '≤ (less or equal)',
					'>': '> (greater than)',
					'<': '< (less than)',
					'=': '= (equals)',
					'is': 'is',
					'is_not': 'is not',
					'has': 'has',
					'has_all': 'has all',
					'has_any': 'has any',
					'has_not': 'has not',
					'in_group': 'in group'
				};

				const allowedOperators = operatorsByField[field] || ['>=', '<=', '='];

				// Rebuild operator dropdown.
				operatorSelect.empty();
				allowedOperators.forEach(function(op) {
					const label = operatorLabels[op] || op;
					const selected = (op === currentOperator) ? 'selected' : '';
					operatorSelect.append(`<option value="${op}" ${selected}>${label}</option>`);
				});

				// Update placeholder for value field based on field type.
				const valueInput = row.find('.em-condition-value');
				const placeholders = {
					'profile': 'Profile ID (e.g., ssaw-products)',
					'order_total': 'Amount (e.g., 300)',
					'product_tag': 'Tag slug (e.g., dtf)',
					'product_category': 'Category slug (e.g., apparel)',
					'item_count': 'Quantity (e.g., 12)',
					'total_weight': 'Weight in lbs (e.g., 50)',
					'shipping_location': 'Location ID or group',
					'customer_role': 'Role (e.g., wholesale, guest)'
				};

				valueInput.attr('placeholder', placeholders[field] || 'Value');
			});
		}

		// Initialize operators on page load.
		updateOperators();

		// Form validation.
		$('.em-rule-form').on('submit', function(e) {
			const ruleName = $('#rule_name').val().trim();
			
			if (!ruleName) {
				alert('Please enter a rule name.');
				e.preventDefault();
				return false;
			}

			// Check that at least one condition has a value.
			let hasValidCondition = false;
			$('.em-condition-value').each(function() {
				if ($(this).val().trim() !== '') {
					hasValidCondition = true;
					return false; // break
				}
			});

			if (!hasValidCondition) {
				alert('Please enter at least one condition value.');
				e.preventDefault();
				return false;
			}

			// Check that at least one service is selected.
			if (!$('input[name="applies_to_services[]"]:checked').length) {
				alert('Please select at least one shipping service.');
				e.preventDefault();
				return false;
			}

			return true;
		});
	});

})(jQuery);
