/**
 * Profile Admin JavaScript
 * Handles zone/method UI interactions
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Confirm before deleting profile
    $('.em-profile-delete-form').on('submit', function(e) {
        if (!confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-update profile ID slug from name (new profiles only)
    $('#profile_name').on('input', function() {
        var $profileId = $('#profile_id');
        if ($profileId.length && !$profileId.prop('readonly')) {
            var name = $(this).val();
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $profileId.val(slug);
        }
    });
    
    // Ship to Store settings toggle
    $('#ship_to_store_enabled').on('change', function() {
        var $settings = $('#ship_to_store_settings');
        if ($(this).is(':checked')) {
            $settings.slideDown();
        } else {
            $settings.slideUp();
        }
    });
    
    // Update margin unit display (% or $)
    $('input[name="ship_to_store_margin_type"]').on('change', function() {
        var unit = $(this).val() === 'percentage' ? '%' : '$';
        $('#margin_unit').text(unit);
    });
    
    // Fulfillment location dependencies
    $('input[name="fulfillment_locations[]"]').on('change', function() {
        var hasWarehouse = $('input[name="fulfillment_locations[]"][value="warehouse"]').is(':checked');
        var hasStore = $('input[name="fulfillment_locations[]"][value="store"]').is(':checked');
        
        // Local pickup requires store
        if (!hasStore) {
            $('input[name="local_pickup"]').prop('checked', false).prop('disabled', true);
        } else {
            $('input[name="local_pickup"]').prop('disabled', false);
        }
        
        // Ship to store requires both
        if (!hasWarehouse || !hasStore) {
            $('input[name="ship_to_store_enabled"]').prop('checked', false).prop('disabled', true);
            $('#ship_to_store_settings').slideUp();
        } else {
            $('input[name="ship_to_store_enabled"]').prop('disabled', false);
        }
    });
    
    // Initialize on page load
    $('input[name="fulfillment_locations[]"]').trigger('change');
    
    // Zone management (placeholder for Phase 2A+)
    $('.em-add-zone').on('click', function(e) {
        e.preventDefault();
        // Will be implemented in future phases
        alert('Zone management will be available in a future update.');
    });
    
    console.log('Epic Marks Shipping - Profile Admin JS loaded');
});
