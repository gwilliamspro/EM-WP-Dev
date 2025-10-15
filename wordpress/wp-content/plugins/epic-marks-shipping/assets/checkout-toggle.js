/**
 * Checkout Shipping Mode Toggle Script
 *
 * Handles customer selection between "Ship All" and "Partial Pickup" modes
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

(function($) {
    'use strict';

    var emShippingToggle = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Listen for shipping mode radio button changes
            $(document).on('change', 'input[name="em_shipping_mode"]', this.handleModeChange.bind(this));
        },

        handleModeChange: function(e) {
            var selectedMode = $(e.target).val();
            
            // Update visual selection
            $('.em-shipping-mode-option').removeClass('selected');
            $(e.target).closest('.em-shipping-mode-option').addClass('selected');

            // Show loading indicator
            this.showLoading();

            // Send AJAX request to update session
            this.updateShippingMode(selectedMode);
        },

        updateShippingMode: function(mode) {
            var self = this;

            $.ajax({
                type: 'POST',
                url: emCheckout.ajaxurl,
                data: {
                    action: 'em_update_shipping_mode',
                    mode: mode,
                    nonce: emCheckout.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger WooCommerce checkout update
                        self.refreshCheckout();
                    } else {
                        self.hideLoading();
                        self.showError(response.data.message || 'Failed to update shipping mode');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showError('Connection error. Please try again.');
                }
            });
        },

        refreshCheckout: function() {
            // Trigger WooCommerce's built-in checkout update
            $(document.body).trigger('update_checkout');

            // Hide loading after a short delay (WooCommerce will show its own loading)
            setTimeout(this.hideLoading.bind(this), 300);
        },

        showLoading: function() {
            $('.em-shipping-loading').show();
            $('.em-shipping-mode-options').css('opacity', '0.6');
        },

        hideLoading: function() {
            $('.em-shipping-loading').hide();
            $('.em-shipping-mode-options').css('opacity', '1');
        },

        showError: function(message) {
            // Remove any existing error notices
            $('.em-shipping-error').remove();

            // Add error notice
            $('.em-shipping-mode-toggle').after(
                '<div class="woocommerce-error em-shipping-error">' + message + '</div>'
            );

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.em-shipping-error').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        emShippingToggle.init();
    });

})(jQuery);
