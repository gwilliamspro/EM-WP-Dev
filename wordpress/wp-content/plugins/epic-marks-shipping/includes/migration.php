<?php
/**
 * Data Migration Script
 *
 * Handles one-time migration of existing products to profiles and old location system to new
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EM_Shipping_Migration {

    /**
     * Migration version
     */
    const MIGRATION_VERSION = '2.0.0';

    /**
     * Location migration version
     */
    const LOCATION_MIGRATION_VERSION = '2.1.0';

    /**
     * Option key for migration version
     */
    const VERSION_OPTION_KEY = 'em_profile_migration_version';

    /**
     * Option key for location migration version
     */
    const LOCATION_VERSION_OPTION_KEY = 'em_location_migration_version';

    /**
     * Run migrations if needed
     */
    public static function maybe_run_migrations() {
        $current_version = get_option( self::VERSION_OPTION_KEY, '0' );

        // Check if profile migration needed
        if ( version_compare( $current_version, self::MIGRATION_VERSION, '<' ) ) {
            self::run_migrations();
        }

        // Check if location migration needed
        $current_location_version = get_option( self::LOCATION_VERSION_OPTION_KEY, '0' );
        if ( version_compare( $current_location_version, self::LOCATION_MIGRATION_VERSION, '<' ) ) {
            self::run_location_migration();
        }
    }

    /**
     * Run all migrations
     */
    public static function run_migrations() {
        // Ensure default profiles exist
        EM_Profile_Manager::initialize_default_profiles();

        // Migrate products with tags to profiles
        self::migrate_tagged_products();

        // Update migration version
        update_option( self::VERSION_OPTION_KEY, self::MIGRATION_VERSION );

        // Add admin notice
        add_option( 'em_migration_complete_notice', true );
    }

    /**
     * Run location migration
     */
    public static function run_location_migration() {
        // Check if locations already exist
        $existing_locations = EM_Location_Manager::get_all_locations();
        if ( ! empty( $existing_locations ) ) {
            // Locations already configured, skip migration
            update_option( self::LOCATION_VERSION_OPTION_KEY, self::LOCATION_MIGRATION_VERSION );
            return;
        }

        // Get old settings
        $settings = get_option( 'em_ups_settings', array() );

        $migrated_count = 0;

        // Migrate warehouse location
        if ( ! empty( $settings['warehouse_zip'] ) ) {
            $warehouse_location = array(
                'id' => 'warehouse',
                'name' => 'Warehouse',
                'type' => 'warehouse',
                'group' => '',
                'address' => array(
                    'company' => 'Epic Marks',
                    'address_1' => $settings['warehouse_address'] ?? '',
                    'city' => $settings['warehouse_city'] ?? '',
                    'state' => strtoupper( $settings['warehouse_state'] ?? '' ),
                    'zip' => $settings['warehouse_zip'] ?? '',
                    'country' => 'US',
                ),
                'capabilities' => array( 'shipping' ),
                'services' => $settings['services'] ?? array( 'ground', '2day', 'nextday' ),
                'processing_time' => 1,
                'cutoff_time' => '14:00',
                'holidays' => 'ups',
                'priority' => 2,
                'status' => 'active',
            );

            $result = EM_Location_Manager::create_location( $warehouse_location );
            if ( ! is_wp_error( $result ) ) {
                $migrated_count++;
            }
        }

        // Migrate retail store location
        if ( ! empty( $settings['store_zip'] ) ) {
            $store_location = array(
                'id' => 'round-rock-store',
                'name' => 'Round Rock Store',
                'type' => 'store',
                'group' => 'retail_stores',
                'address' => array(
                    'company' => 'Epic Marks',
                    'address_1' => $settings['store_address'] ?? '',
                    'city' => $settings['store_city'] ?? '',
                    'state' => strtoupper( $settings['store_state'] ?? '' ),
                    'zip' => $settings['store_zip'] ?? '',
                    'country' => 'US',
                ),
                'capabilities' => array( 'shipping', 'pickup' ),
                'services' => $settings['services'] ?? array( 'ground', '2day', 'nextday' ),
                'processing_time' => 0,
                'cutoff_time' => '14:00',
                'holidays' => 'countdown_timer',
                'priority' => 1,
                'status' => 'active',
            );

            $result = EM_Location_Manager::create_location( $store_location );
            if ( ! is_wp_error( $result ) ) {
                $migrated_count++;
            }
        }

        // Update migration version
        update_option( self::LOCATION_VERSION_OPTION_KEY, self::LOCATION_MIGRATION_VERSION );

        // Add admin notice
        if ( $migrated_count > 0 ) {
            update_option( 'em_location_migration_stats', array(
                'migrated_count' => $migrated_count,
                'timestamp' => current_time( 'mysql' ),
            ) );
            add_option( 'em_location_migration_complete_notice', true );
        }
    }

    /**
     * Migrate products with specific tags to profiles
     */
    private static function migrate_tagged_products() {
        $migrations = array(
            'SSAW-App' => 'ssaw-products',
            'available-in-store' => 'general', // Can be customized based on requirements
        );

        $total_migrated = 0;

        foreach ( $migrations as $tag => $profile_id ) {
            // Get products with this tag
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_tag',
                        'field' => 'name',
                        'terms' => $tag,
                    ),
                ),
                'meta_query' => array(
                    array(
                        'key' => '_shipping_profile',
                        'compare' => 'NOT EXISTS', // Only migrate products without profile
                    ),
                ),
            );

            $products = get_posts( $args );

            // Batch assign products
            foreach ( $products as $product_id ) {
                if ( EM_Profile_Manager::assign_product_to_profile( $product_id, $profile_id ) ) {
                    $total_migrated++;
                }
            }
        }

        // Store migration stats
        update_option( 'em_migration_stats', array(
            'total_migrated' => $total_migrated,
            'timestamp' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Display migration complete notice
     */
    public static function display_migration_notice() {
        if ( get_option( 'em_migration_complete_notice' ) ) {
            $stats = get_option( 'em_migration_stats', array() );
            $migrated = $stats['total_migrated'] ?? 0;
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'Epic Marks Shipping:', 'epic-marks-shipping' ); ?></strong>
                    <?php
                    printf(
                        esc_html__( 'Profile system initialized! %s products were automatically assigned to profiles based on their tags.', 'epic-marks-shipping' ),
                        '<strong>' . number_format( $migrated ) . '</strong>'
                    );
                    ?>
                </p>
                <p>
                    <?php esc_html_e( 'You can now manage shipping profiles in WooCommerce > UPS Shipping > Profiles.', 'epic-marks-shipping' ); ?>
                </p>
            </div>
            <?php
            delete_option( 'em_migration_complete_notice' );
        }

        // Display location migration notice
        if ( get_option( 'em_location_migration_complete_notice' ) ) {
            $stats = get_option( 'em_location_migration_stats', array() );
            $migrated = $stats['migrated_count'] ?? 0;
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php esc_html_e( 'Epic Marks Shipping:', 'epic-marks-shipping' ); ?></strong>
                    <?php
                    printf(
                        esc_html__( 'Location system upgraded! %s location(s) migrated successfully.', 'epic-marks-shipping' ),
                        '<strong>' . number_format( $migrated ) . '</strong>'
                    );
                    ?>
                </p>
                <p>
                    <?php esc_html_e( 'You can now manage locations in WooCommerce > UPS Shipping > Locations.', 'epic-marks-shipping' ); ?>
                </p>
            </div>
            <?php
            delete_option( 'em_location_migration_complete_notice' );
        }
    }

    /**
     * Display migration stats in admin
     */
    public static function get_migration_stats() {
        return get_option( 'em_migration_stats', array() );
    }

    /**
     * Get location migration stats
     */
    public static function get_location_migration_stats() {
        return get_option( 'em_location_migration_stats', array() );
    }

    /**
     * Reset migration (for testing)
     */
    public static function reset_migration() {
        delete_option( self::VERSION_OPTION_KEY );
        delete_option( self::LOCATION_VERSION_OPTION_KEY );
        delete_option( 'em_migration_stats' );
        delete_option( 'em_location_migration_stats' );
        delete_option( 'em_migration_complete_notice' );
        delete_option( 'em_location_migration_complete_notice' );

        // Optionally: Remove all _shipping_profile meta
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_shipping_profile'" );
        
        // Optionally: Clear locations
        delete_option( EM_Location_Manager::OPTION_NAME );
    }
}

// Hook migration to run on plugin load
add_action( 'plugins_loaded', array( 'EM_Shipping_Migration', 'maybe_run_migrations' ), 20 );

// Display migration notice
add_action( 'admin_notices', array( 'EM_Shipping_Migration', 'display_migration_notice' ) );
