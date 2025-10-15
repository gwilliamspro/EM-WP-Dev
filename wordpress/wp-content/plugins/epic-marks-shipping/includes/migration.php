<?php
/**
 * Data Migration Script
 *
 * Handles one-time migration of existing products to profiles
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
     * Option key for migration version
     */
    const VERSION_OPTION_KEY = 'em_profile_migration_version';

    /**
     * Run migrations if needed
     */
    public static function maybe_run_migrations() {
        $current_version = get_option( self::VERSION_OPTION_KEY, '0' );

        // Check if migration needed
        if ( version_compare( $current_version, self::MIGRATION_VERSION, '<' ) ) {
            self::run_migrations();
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
    }

    /**
     * Display migration stats in admin
     */
    public static function get_migration_stats() {
        return get_option( 'em_migration_stats', array() );
    }

    /**
     * Reset migration (for testing)
     */
    public static function reset_migration() {
        delete_option( self::VERSION_OPTION_KEY );
        delete_option( 'em_migration_stats' );
        delete_option( 'em_migration_complete_notice' );

        // Optionally: Remove all _shipping_profile meta
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_shipping_profile'" );
    }
}

// Hook migration to run on plugin load
add_action( 'plugins_loaded', array( 'EM_Shipping_Migration', 'maybe_run_migrations' ), 20 );

// Display migration notice
add_action( 'admin_notices', array( 'EM_Shipping_Migration', 'display_migration_notice' ) );
