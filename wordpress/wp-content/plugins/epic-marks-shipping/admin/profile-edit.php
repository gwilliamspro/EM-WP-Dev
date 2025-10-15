<?php
/**
 * Profile Edit Form
 *
 * Handles creating and editing shipping profiles
 */
if (!defined('ABSPATH')) exit;

// Check if editing or creating new
$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['profile']);
$profile_id = $editing ? sanitize_key($_GET['profile']) : '';

// Get profile if editing
$profile = $editing ? EM_Shipping_Profile::get($profile_id) : EM_Shipping_Profile::create_default();

// Generate new ID if creating
if (!$editing) {
    $profile->id = '';
    $profile->name = '';
}

// Handle form submission
if (isset($_POST['save_profile'])) {
    check_admin_referer('em_save_profile');
    
    $profile_data = array(
        'id' => isset($_POST['profile_id']) ? sanitize_key($_POST['profile_id']) : '',
        'name' => isset($_POST['profile_name']) ? sanitize_text_field($_POST['profile_name']) : '',
        'fulfillment_locations' => isset($_POST['fulfillment_locations']) ? array_map('sanitize_text_field', $_POST['fulfillment_locations']) : array(),
        'local_pickup' => isset($_POST['local_pickup']) ? true : false,
        'ship_to_store_enabled' => isset($_POST['ship_to_store_enabled']) ? true : false,
        'ship_to_store_margin_type' => isset($_POST['ship_to_store_margin_type']) ? sanitize_text_field($_POST['ship_to_store_margin_type']) : 'percentage',
        'ship_to_store_margin' => isset($_POST['ship_to_store_margin']) ? floatval($_POST['ship_to_store_margin']) : 0,
        'ship_to_store_label' => isset($_POST['ship_to_store_label']) ? sanitize_text_field($_POST['ship_to_store_label']) : 'Ship to Store (includes handling)',
        'zones' => array(), // Will be enhanced in future phases
        'package_settings' => array(),
    );
    
    $profile = new EM_Shipping_Profile($profile_data);
    $result = $profile->save();
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Profile saved successfully.</p></div>';
        $editing = true;
        $profile_id = $profile->id;
    }
}
?>

<div class="wrap em-profile-edit">
    <h1><?php echo $editing ? 'Edit Profile' : 'Add New Profile'; ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('em_save_profile'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="profile_id">Profile ID</label>
                </th>
                <td>
                    <?php if ($editing): ?>
                        <input type="text" id="profile_id" name="profile_id" value="<?php echo esc_attr($profile->id); ?>" readonly class="regular-text">
                        <p class="description">Profile ID cannot be changed after creation.</p>
                    <?php else: ?>
                        <input type="text" id="profile_id" name="profile_id" value="" required class="regular-text" pattern="[a-z0-9\-]+" placeholder="e-g-custom-profile">
                        <p class="description">Lowercase letters, numbers, and hyphens only. Example: custom-profile</p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="profile_name">Profile Name *</label>
                </th>
                <td>
                    <input type="text" id="profile_name" name="profile_name" value="<?php echo esc_attr($profile->name); ?>" required class="regular-text">
                    <p class="description">Display name for this profile</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Fulfillment Locations *</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="fulfillment_locations[]" value="warehouse" <?php checked(in_array('warehouse', $profile->fulfillment_locations)); ?>>
                            Warehouse
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="fulfillment_locations[]" value="store" <?php checked(in_array('store', $profile->fulfillment_locations)); ?>>
                            Store
                        </label>
                        <p class="description">Select at least one fulfillment location</p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Local Pickup</th>
                <td>
                    <label>
                        <input type="checkbox" name="local_pickup" value="1" <?php checked($profile->local_pickup); ?>>
                        Enable local pickup for this profile
                    </label>
                    <p class="description">Only available if Store is selected as fulfillment location</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Ship to Store</th>
                <td>
                    <label>
                        <input type="checkbox" name="ship_to_store_enabled" value="1" id="ship_to_store_enabled" <?php checked($profile->ship_to_store_enabled); ?>>
                        Enable "Ship to Store" option
                    </label>
                    <p class="description">Allows warehouse items to be shipped to store for customer pickup. Requires both Warehouse and Store locations.</p>
                </td>
            </tr>
        </table>
        
        <div id="ship_to_store_settings" style="<?php echo $profile->ship_to_store_enabled ? '' : 'display:none;'; ?>">
            <h2>Ship to Store Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Margin Type</th>
                    <td>
                        <label>
                            <input type="radio" name="ship_to_store_margin_type" value="percentage" <?php checked($profile->ship_to_store_margin_type, 'percentage'); ?>>
                            Percentage
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="ship_to_store_margin_type" value="flat" <?php checked($profile->ship_to_store_margin_type, 'flat'); ?>>
                            Flat Amount
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ship_to_store_margin">Margin Value</label>
                    </th>
                    <td>
                        <input type="number" id="ship_to_store_margin" name="ship_to_store_margin" value="<?php echo esc_attr($profile->ship_to_store_margin); ?>" step="0.01" min="0" class="small-text">
                        <span id="margin_unit"><?php echo $profile->ship_to_store_margin_type === 'percentage' ? '%' : '$'; ?></span>
                        <p class="description">Margin covers supplier shipping costs above UPS retail rates</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ship_to_store_label">Customer Label</label>
                    </th>
                    <td>
                        <input type="text" id="ship_to_store_label" name="ship_to_store_label" value="<?php echo esc_attr($profile->ship_to_store_label); ?>" class="regular-text">
                        <p class="description">Label shown to customers at checkout</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" name="save_profile" class="button button-primary">Save Profile</button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=em-ups-shipping&tab=profiles')); ?>" class="button">Cancel</a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide ship to store settings
    $('#ship_to_store_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#ship_to_store_settings').slideDown();
        } else {
            $('#ship_to_store_settings').slideUp();
        }
    });
    
    // Update margin unit display
    $('input[name="ship_to_store_margin_type"]').change(function() {
        if ($(this).val() === 'percentage') {
            $('#margin_unit').text('%');
        } else {
            $('#margin_unit').text('$');
        }
    });
    
    // Auto-generate profile ID from name (for new profiles)
    <?php if (!$editing): ?>
    $('#profile_name').on('input', function() {
        var name = $(this).val();
        var id = name.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        $('#profile_id').val(id);
    });
    <?php endif; ?>
});
</script>

<style>
.em-profile-edit .form-table th {
    width: 200px;
}
.em-profile-edit fieldset label {
    display: block;
    margin: 5px 0;
}
#ship_to_store_settings {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ddd;
    margin: 20px 0;
}
</style>
