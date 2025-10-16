/**
 * Location Admin JavaScript
 *
 * Handles UI interactions on the locations tab
 *
 * @package Epic_Marks_Shipping
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Toggle import panel
        $('.em-toggle-import').on('click', function(e) {
            e.preventDefault();
            $('.em-import-panel').slideToggle();
        });

        // Cancel import
        $('.em-cancel-import').on('click', function(e) {
            e.preventDefault();
            $('.em-import-panel').slideUp();
        });

        // Delete location confirmation
        $('.em-delete-location').on('click', function(e) {
            var locationName = $(this).data('location-name');
            var confirmMessage = emLocationAdmin.confirmDelete.replace('%s', locationName);
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
        });

        // Location type change handler (show/hide warehouse code field)
        $('#location_type').on('change', function() {
            var type = $(this).val();
            
            if (type === 'ssaw_warehouse') {
                $('.em-warehouse-code-row').show();
                $('#warehouse_code').prop('required', true);
                
                // Set SSAW defaults
                $('input[name="capabilities[]"]').prop('checked', false);
                $('input[name="capabilities[]"][value="shipping"]').prop('checked', true);
                
                $('input[name="services[]"]').prop('checked', false);
                $('input[name="services[]"][value="ground"]').prop('checked', true);
                
                $('#location_group').val('ssaw_warehouses');
                $('#processing_time').val(0);
                $('#holidays').val('ups');
            } else {
                $('.em-warehouse-code-row').hide();
                $('#warehouse_code').prop('required', false);
                
                if (type === 'store') {
                    // Set store defaults
                    $('input[name="capabilities[]"][value="shipping"]').prop('checked', true);
                    $('input[name="capabilities[]"][value="pickup"]').prop('checked', true);
                    
                    $('input[name="services[]"]').prop('checked', true);
                    
                    $('#location_group').val('retail_stores');
                    $('#processing_time').val(0);
                    $('#holidays').val('countdown_timer');
                } else if (type === 'warehouse') {
                    // Set warehouse defaults
                    $('input[name="capabilities[]"]').prop('checked', false);
                    $('input[name="capabilities[]"][value="shipping"]').prop('checked', true);
                    
                    $('input[name="services[]"]').prop('checked', true);
                    
                    $('#location_group').val('');
                    $('#processing_time').val(1);
                    $('#holidays').val('ups');
                }
            }
        });

        // Uppercase state and country codes as user types
        $('#address_state, #address_country').on('input', function() {
            this.value = this.value.toUpperCase();
        });
        
    });

})(jQuery);
