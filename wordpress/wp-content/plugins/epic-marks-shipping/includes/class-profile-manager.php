<?php
/**
 * Profile Manager Helper Class
 *
 * Provides helper methods for profile queries and validation
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EM_Profile_Manager {
    /**
     * Get product's assigned profile
     *
     * @param int $product_id Product ID
     * @return EM_Shipping_Profile|null Profile object or null
     */
    public static function get_product_profile($product_id) {
        // Get profile ID from product meta
        $profile_id = get_post_meta($product_id, '_shipping_profile', true);
        
        // If no profile assigned, try tag-based fallback
        if (empty($profile_id)) {
            $profile_id = self::get_profile_from_tags($product_id);
        }
        
        // If still no profile, use default
        if (empty($profile_id)) {
            $profile_id = 'general';
        }
        
        // Get profile object
        $profile = EM_Shipping_Profile::get($profile_id);
        
        // If profile not found (was deleted), use general
        if (!$profile) {
            $profile = EM_Shipping_Profile::get('general');
            
            // If general doesn't exist, create it
            if (!$profile) {
                $profile = EM_Shipping_Profile::create_default();
                $profile->save();
            }
        }
        
        return $profile;
    }
    
    /**
     * Determine profile from product tags (backward compatibility)
     *
     * @param int $product_id Product ID
     * @return string|null Profile ID or null
     */
    public static function get_profile_from_tags($product_id) {
        $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'slugs'));
        
        if (is_wp_error($tags)) {
            return null;
        }
        
        // Check for SSAW-App tag
        if (in_array('ssaw-app', $tags)) {
            return 'ssaw-products';
        }
        
        // Check for available-in-store tag
        if (in_array('available-in-store', $tags)) {
            return 'store-products';
        }
        
        return null;
    }
    
    /**
     * Get all products assigned to a profile
     *
     * @param string $profile_id Profile ID
     * @param int $limit Limit results (default: -1 for all)
     * @return array Array of product IDs
     */
    public static function get_profile_products($profile_id, $limit = -1) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_shipping_profile',
                    'value' => $profile_id,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        return $query->posts;
    }
    
    /**
     * Assign product to profile
     *
     * @param int $product_id Product ID
     * @param string $profile_id Profile ID
     * @return bool True on success
     */
    public static function assign_product_to_profile($product_id, $profile_id) {
        // Validate profile exists
        if (!EM_Shipping_Profile::exists($profile_id)) {
            return false;
        }
        
        // Update product meta
        return update_post_meta($product_id, '_shipping_profile', $profile_id);
    }
    
    /**
     * Bulk assign products by tag
     *
     * @param string $tag Tag slug
     * @param string $profile_id Profile ID
     * @param int $batch_size Number of products per batch (default: 100)
     * @param int $offset Offset for pagination (default: 0)
     * @return array Result with 'assigned' count and 'has_more' flag
     */
    public static function bulk_assign_by_tag($tag, $profile_id, $batch_size = 100, $offset = 0) {
        // Validate profile exists
        if (!EM_Shipping_Profile::exists($profile_id)) {
            return array('error' => 'Profile does not exist', 'assigned' => 0, 'has_more' => false);
        }
        
        // Get products with tag
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_tag',
                    'field' => 'slug',
                    'terms' => $tag
                )
            )
        );
        
        $query = new WP_Query($args);
        $assigned = 0;
        
        foreach ($query->posts as $product_id) {
            if (self::assign_product_to_profile($product_id, $profile_id)) {
                $assigned++;
            }
        }
        
        return array(
            'assigned' => $assigned,
            'has_more' => ($query->found_posts > ($offset + $batch_size)),
            'total' => $query->found_posts
        );
    }
    
    /**
     * Validate profile configuration
     *
     * @param array $profile_data Profile data array
     * @return true|WP_Error True if valid, WP_Error if invalid
     */
    public static function validate_profile_config($profile_data) {
        $profile = new EM_Shipping_Profile($profile_data);
        return $profile->validate();
    }
    
    /**
     * Get profile statistics
     *
     * @return array Statistics for all profiles
     */
    public static function get_profile_stats() {
        $profiles = EM_Shipping_Profile::get_all();
        $stats = array();
        
        foreach ($profiles as $profile) {
            $stats[$profile->id] = array(
                'name' => $profile->name,
                'product_count' => $profile->get_product_count(),
                'fulfillment_locations' => $profile->fulfillment_locations,
                'local_pickup' => $profile->local_pickup,
                'ship_to_store' => $profile->ship_to_store_enabled
            );
        }
        
        return $stats;
    }
    
    /**
     * Get products without profile assignment
     *
     * @param int $limit Limit results (default: 100)
     * @return array Array of product IDs
     */
    public static function get_unassigned_products($limit = 100) {
        global $wpdb;
        
        $sql = "SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'product' 
                AND ID NOT IN (
                    SELECT post_id FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_shipping_profile'
                )
                LIMIT %d";
        
        $product_ids = $wpdb->get_col($wpdb->prepare($sql, $limit));
        
        return $product_ids;
    }
    
    /**
     * Initialize default profiles
     *
     * Creates General and SSAW Products profiles if they don't exist
     *
     * @return bool True on success
     */
    public static function initialize_default_profiles() {
        // Create General profile if doesn't exist
        if (!EM_Shipping_Profile::exists('general')) {
            $general = EM_Shipping_Profile::create_default();
            $general->save();
        }
        
        // Create SSAW Products profile if doesn't exist
        if (!EM_Shipping_Profile::exists('ssaw-products')) {
            $ssaw = new EM_Shipping_Profile(array(
                'id' => 'ssaw-products',
                'name' => 'SSAW Products',
                'fulfillment_locations' => array('warehouse'),
                'zones' => array(
                    'domestic_us' => array(
                        'name' => 'Domestic US',
                        'type' => 'country',
                        'countries' => array('US'),
                        'methods' => array(
                            array('type' => 'ups_rates', 'enabled' => true),
                            array('type' => 'free_shipping', 'enabled' => true, 'threshold' => 100.00),
                        ),
                    ),
                ),
                'local_pickup' => false,
                'ship_to_store_enabled' => false,
                'package_settings' => array(),
            ));
            $ssaw->save();
        }
        
        return true;
    }
}
