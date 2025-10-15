<?php
/**
 * Profiles Tab - Profile Management UI
 */
if (!defined('ABSPATH')) exit;

// Handle profile actions
if (isset($_POST['em_profile_action'])) {
    check_admin_referer('em_shipping_profile_action');
    
    $action = sanitize_text_field($_POST['em_profile_action']);
    
    if ($action === 'save') {
        // Save profile
        $profile_data = array(
            'id' => isset($_POST['profile_id']) ? sanitize_key($_POST['profile_id']) : '',
            'name' => isset($_POST['profile_name']) ? sanitize_text_field($_POST['profile_name']) : '',
            'fulfillment_locations' => isset($_POST['fulfillment_locations']) ? (array) $_POST['fulfillment_locations'] : array(),
            'zones' => isset($_POST['zones']) ? (array) $_POST['zones'] : array(),
            'local_pickup' => isset($_POST['local_pickup']) ? (bool) $_POST['local_pickup'] : false,
            'ship_to_store_enabled' => isset($_POST['ship_to_store_enabled']) ? (bool) $_POST['ship_to_store_enabled'] : false,
            'ship_to_store_margin_type' => isset($_POST['ship_to_store_margin_type']) ? sanitize_text_field($_POST['ship_to_store_margin_type']) : 'percentage',
            'ship_to_store_margin' => isset($_POST['ship_to_store_margin']) ? floatval($_POST['ship_to_store_margin']) : 0,
            'ship_to_store_label' => isset($_POST['ship_to_store_label']) ? sanitize_text_field($_POST['ship_to_store_label']) : 'Ship to Store (includes handling)',
        );
        
        $profile = new EM_Shipping_Profile($profile_data);
        $result = $profile->save();
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Profile saved successfully.</p></div>';
        }
    } elseif ($action === 'delete') {
        // Delete profile
        $profile_id = isset($_POST['profile_id']) ? sanitize_key($_POST['profile_id']) : '';
        $profile = EM_Shipping_Profile::get($profile_id);
        
        if ($profile) {
            $product_count = $profile->get_product_count();
            if ($product_count > 0) {
                echo '<div class="notice notice-error"><p>Cannot delete profile: ' . $product_count . ' products are assigned to this profile.</p></div>';
            } else {
                $profile->delete();
                echo '<div class="notice notice-success"><p>Profile deleted successfully.</p></div>';
            }
        }
    }
}

// Handle initialization
if (isset($_POST['em_init_profiles'])) {
    check_admin_referer('em_init_profiles');
    EM_Profile_Manager::initialize_default_profiles();
    echo '<div class="notice notice-success"><p>Default profiles initialized successfully.</p></div>';
}

// Get all profiles
$profiles = EM_Shipping_Profile::get_all();
?>

<div class="em-profiles-tab wrap">
    <h2>Shipping Profiles
        <a href="<?php echo esc_url(admin_url('admin.php?page=em-ups-shipping&tab=profiles&action=new')); ?>" class="page-title-action">Add New Profile</a>
    </h2>
    
    <?php if (empty($profiles)): ?>
        <div class="em-no-profiles">
            <p>No shipping profiles found. Initialize default profiles to get started.</p>
            <form method="post">
                <?php wp_nonce_field('em_init_profiles'); ?>
                <input type="hidden" name="em_init_profiles" value="1">
                <button type="submit" class="button button-primary">Initialize Default Profiles</button>
            </form>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Profile Name</th>
                    <th scope="col">Products</th>
                    <th scope="col">Fulfillment Locations</th>
                    <th scope="col">Local Pickup</th>
                    <th scope="col">Ship to Store</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><strong><?php echo esc_html($profile->name); ?></strong></td>
                        <td><?php echo number_format($profile->get_product_count()); ?></td>
                        <td><?php echo esc_html(implode(', ', array_map('ucfirst', $profile->fulfillment_locations))); ?></td>
                        <td><?php echo $profile->local_pickup ? '<span class="dashicons dashicons-yes" style="color:green;"></span>' : ''; ?></td>
                        <td><?php echo $profile->ship_to_store_enabled ? '<span class="dashicons dashicons-yes" style="color:green;"></span>' : ''; ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=em-ups-shipping&tab=profiles&action=edit&profile=' . $profile->id)); ?>" class="button button-small">Edit</a>
                            <?php if ($profile->id !== 'general'): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this profile?');">
                                    <?php wp_nonce_field('em_shipping_profile_action'); ?>
                                    <input type="hidden" name="em_profile_action" value="delete">
                                    <input type="hidden" name="profile_id" value="<?php echo esc_attr($profile->id); ?>">
                                    <button type="submit" class="button button-small button-link-delete">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.em-no-profiles {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 30px;
    text-align: center;
    margin: 20px 0;
}
.em-profiles-tab table {
    margin-top: 20px;
}
</style>
