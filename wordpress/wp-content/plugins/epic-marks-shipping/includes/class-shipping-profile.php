<?php
/**
 * Shipping Profile Class
 *
 * Represents a shipping profile with fulfillment locations, zones, and methods
 *
 * @package    Epic_Marks_Shipping
 * @subpackage Epic_Marks_Shipping/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EM_Shipping_Profile {
    public $id;
    public $name;
    public $fulfillment_locations;
    public $zones;
    public $local_pickup;
    public $ship_to_store_enabled;
    public $ship_to_store_margin_type;
    public $ship_to_store_margin;
    public $ship_to_store_label;
    public $package_settings;

    public function __construct($data = array()) {
        $this->id = isset($data['id']) ? sanitize_key($data['id']) : '';
        $this->name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $this->fulfillment_locations = isset($data['fulfillment_locations']) ? (array) $data['fulfillment_locations'] : array('warehouse');
        $this->zones = isset($data['zones']) ? (array) $data['zones'] : array();
        $this->local_pickup = isset($data['local_pickup']) ? (bool) $data['local_pickup'] : false;
        $this->ship_to_store_enabled = isset($data['ship_to_store_enabled']) ? (bool) $data['ship_to_store_enabled'] : false;
        $this->ship_to_store_margin_type = isset($data['ship_to_store_margin_type']) ? sanitize_text_field($data['ship_to_store_margin_type']) : 'percentage';
        $this->ship_to_store_margin = isset($data['ship_to_store_margin']) ? floatval($data['ship_to_store_margin']) : 0;
        $this->ship_to_store_label = isset($data['ship_to_store_label']) ? sanitize_text_field($data['ship_to_store_label']) : 'Ship to Store (includes handling)';
        $this->package_settings = isset($data['package_settings']) ? (array) $data['package_settings'] : array();
    }

    public function to_array() {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'fulfillment_locations' => $this->fulfillment_locations,
            'zones' => $this->zones,
            'local_pickup' => $this->local_pickup,
            'ship_to_store_enabled' => $this->ship_to_store_enabled,
            'ship_to_store_margin_type' => $this->ship_to_store_margin_type,
            'ship_to_store_margin' => $this->ship_to_store_margin,
            'ship_to_store_label' => $this->ship_to_store_label,
            'package_settings' => $this->package_settings,
        );
    }

    public function validate() {
        $errors = array();
        if (empty($this->name)) $errors[] = 'Profile name is required.';
        if (empty($this->id)) $errors[] = 'Profile ID is required.';
        if (empty($this->fulfillment_locations)) $errors[] = 'At least one fulfillment location is required.';
        
        $valid_locations = array('warehouse', 'store');
        foreach ($this->fulfillment_locations as $location) {
            if (!in_array($location, $valid_locations)) {
                $errors[] = 'Invalid fulfillment location: ' . $location;
            }
        }
        
        if ($this->local_pickup && !in_array('store', $this->fulfillment_locations)) {
            $errors[] = 'Local pickup requires store fulfillment location.';
        }
        
        if ($this->ship_to_store_enabled) {
            if (!in_array('warehouse', $this->fulfillment_locations) || !in_array('store', $this->fulfillment_locations)) {
                $errors[] = 'Ship to Store requires both warehouse and store fulfillment locations.';
            }
            if (!in_array($this->ship_to_store_margin_type, array('percentage', 'flat'))) {
                $errors[] = 'Invalid ship to store margin type.';
            }
        }
        
        return !empty($errors) ? new WP_Error('invalid_profile', implode(' ', $errors)) : true;
    }

    public function save() {
        $validation = $this->validate();
        if (is_wp_error($validation)) return $validation;
        
        $profiles = get_option('em_shipping_profiles', array());
        $profiles[$this->id] = $this->to_array();
        $updated = update_option('em_shipping_profiles', $profiles);
        
        if (!$updated && !isset($profiles[$this->id])) {
            return new WP_Error('save_failed', 'Failed to save profile.');
        }
        return true;
    }

    public function delete() {
        $profiles = get_option('em_shipping_profiles', array());
        if (isset($profiles[$this->id])) {
            unset($profiles[$this->id]);
            return update_option('em_shipping_profiles', $profiles);
        }
        return false;
    }

    public static function get_all() {
        $profiles = get_option('em_shipping_profiles', array());
        $profile_objects = array();
        foreach ($profiles as $profile_data) {
            $profile_objects[] = new self($profile_data);
        }
        return $profile_objects;
    }

    public static function get($id) {
        $profiles = get_option('em_shipping_profiles', array());
        return isset($profiles[$id]) ? new self($profiles[$id]) : null;
    }

    public static function exists($id) {
        $profiles = get_option('em_shipping_profiles', array());
        return isset($profiles[$id]);
    }

    public static function create_default() {
        return new self(array(
            'id' => 'general',
            'name' => 'General Products',
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
            'ship_to_store_margin_type' => 'percentage',
            'ship_to_store_margin' => 30,
            'ship_to_store_label' => 'Ship to Store (includes handling)',
            'package_settings' => array(),
        ));
    }

    public function get_product_count() {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_shipping_profile' AND meta_value = %s",
            $this->id
        ));
        return (int) $count;
    }
}
