/**
 * Bulk Product Assignment - Frontend JavaScript
 *
 * Handles AJAX progress for bulk product assignment
 */

jQuery(document).ready(function($) {
    'use strict';

    let isRunning = false;
    let totalProducts = 0;
    let processedProducts = 0;

    const $startButton = $('#em_start_bulk_assignment');
    const $progressSection = $('#em_bulk_progress');
    const $progressBar = $('#em_progress_bar_fill');
    const $progressText = $('#em_progress_text');
    const $progressStatus = $('#em_progress_status');
    const $resultSection = $('#em_bulk_result');

    /**
     * Start bulk assignment
     */
    $startButton.on('click', function() {
        if (isRunning) {
            return;
        }

        const tag = $('#em_bulk_tag').val();
        const profileId = $('#em_bulk_profile').val();

        // Validation
        if (!tag) {
            alert('Please select a product tag.');
            return;
        }

        if (!profileId) {
            alert('Please select a profile.');
            return;
        }

        // Confirm action
        const confirmMsg = 'This will assign all products with the tag "' + tag + '" to the selected profile. Continue?';
        if (!confirm(confirmMsg)) {
            return;
        }

        startBulkAssignment(tag, profileId);
    });

    /**
     * Start the bulk assignment process
     */
    function startBulkAssignment(tag, profileId) {
        isRunning = true;
        totalProducts = 0;
        processedProducts = 0;

        // UI updates
        $startButton.prop('disabled', true);
        $progressSection.show();
        $resultSection.html('');
        updateProgress(0, 'Initializing...');

        // Start batch processing
        processBatch(tag, profileId, 0);
    }

    /**
     * Process a batch of products
     */
    function processBatch(tag, profileId, offset) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'em_bulk_assign_products',
                nonce: emBulkAssignment.nonce,
                tag: tag,
                profile_id: profileId,
                batch_size: 100, // Process 100 products per request
                offset: offset
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    totalProducts = data.total;
                    processedProducts = data.processed;

                    const percentage = data.percentage;
                    const statusMsg = 'Processing: ' + processedProducts.toLocaleString() + ' / ' + totalProducts.toLocaleString() + ' products';

                    updateProgress(percentage, statusMsg);

                    if (data.complete) {
                        // Complete
                        completeBulkAssignment(processedProducts);
                    } else {
                        // Continue with next batch
                        processBatch(tag, profileId, data.processed);
                    }
                } else {
                    handleError(response.data.message || 'Unknown error occurred');
                }
            },
            error: function(xhr, status, error) {
                handleError('AJAX error: ' + error);
            }
        });
    }

    /**
     * Update progress bar and text
     */
    function updateProgress(percentage, statusText) {
        $progressBar.css('width', percentage + '%');
        $progressText.text(percentage + '%');
        $progressStatus.text(statusText);
    }

    /**
     * Handle completion
     */
    function completeBulkAssignment(count) {
        isRunning = false;
        $startButton.prop('disabled', false);
        
        updateProgress(100, 'Complete!');
        
        $resultSection.html(
            '<div class="notice notice-success inline">' +
            '<p><strong>Success!</strong> Assigned ' + count.toLocaleString() + ' products to the selected profile.</p>' +
            '</div>'
        );

        // Hide progress after delay
        setTimeout(function() {
            $progressSection.fadeOut();
        }, 3000);
    }

    /**
     * Handle errors
     */
    function handleError(message) {
        isRunning = false;
        $startButton.prop('disabled', false);
        
        $resultSection.html(
            '<div class="notice notice-error inline">' +
            '<p><strong>Error:</strong> ' + message + '</p>' +
            '</div>'
        );

        $progressSection.hide();
    }
});
