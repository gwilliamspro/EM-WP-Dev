/**
 * Checkout Routing Options - AJAX Handler
 *
 * Handles customer selection of shipping routing options (ship together vs split)
 * Updates shipping rates via AJAX when routing option changes
 *
 * @package EpicMarksShipping
 * @since 2.2.0
 */

(function($) {
	'use strict';

	var routingHandler = {
		
		/**
		 * Initialize routing option handler
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Handle routing option change
			$(document).on('change', 'input[name="em_routing_option"]', this.handleRoutingChange.bind(this));

			// Handle visual selection
			$(document).on('click', '.em-routing-option-label', this.handleLabelClick.bind(this));
		},

		/**
		 * Handle routing option change
		 */
		handleRoutingChange: function(e) {
			var selectedOption = $(e.target).val();
			
			// Update visual state
			$('.em-routing-option').removeClass('selected');
			$(e.target).closest('.em-routing-option').addClass('selected');

			// Show loading indicator
			this.showLoading();

			// Update WooCommerce shipping
			this.updateShipping(selectedOption);
		},

		/**
		 * Handle label click for better UX
		 */
		handleLabelClick: function(e) {
			var $label = $(e.currentTarget);
			var $radio = $label.find('input[type="radio"]');
			
			if ($radio.length && !$radio.prop('checked')) {
				$radio.prop('checked', true).trigger('change');
			}
		},

		/**
		 * Show loading indicator
		 */
		showLoading: function() {
			$('.em-routing-loading').show();
			$('.em-routing-options-list').css('opacity', '0.6');
		},

		/**
		 * Hide loading indicator
		 */
		hideLoading: function() {
			$('.em-routing-loading').hide();
			$('.em-routing-options-list').css('opacity', '1');
		},

		/**
		 * Update shipping via AJAX
		 *
		 * @param {string} routingOption Selected routing option ID
		 */
		updateShipping: function(routingOption) {
			var self = this;

			// Store selected routing option in session
			$.ajax({
				url: wc_checkout_params.ajax_url,
				type: 'POST',
				data: {
					action: 'em_update_routing_option',
					routing_option: routingOption,
					security: wc_checkout_params.update_order_review_nonce
				},
				success: function(response) {
					// Trigger WooCommerce to recalculate shipping
					$(document.body).trigger('update_checkout');
				},
				error: function() {
					// Hide loading on error
					self.hideLoading();
					
					// Show error message
					alert('Failed to update shipping option. Please try again.');
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		routingHandler.init();
	});

	/**
	 * Hide loading after checkout update completes
	 */
	$(document.body).on('updated_checkout', function() {
		routingHandler.hideLoading();
	});

})(jQuery);
