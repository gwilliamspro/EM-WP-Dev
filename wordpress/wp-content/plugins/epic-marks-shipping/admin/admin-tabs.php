<?php
/**
 * Admin Tabs Router
 *
 * Manages tabbed navigation for settings page
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get available tabs
 */
function em_shipping_get_tabs() {
    return array(
        'locations' => array(
            'label' => __( 'Locations', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-location',
            'file'  => 'locations-tab.php',
        ),
        'setup' => array(
            'label' => __( 'Setup', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-admin-generic',
            'file'  => 'setup-tab.php',
        ),
        'profiles' => array(
            'label' => __( 'Profiles', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-list-view',
            'file'  => 'profiles-tab.php',
        ),
        'rules' => array(
            'label' => __( 'Rules', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-yes-alt',
            'file'  => 'rules-tab.php',
        ),
        'routing' => array(
            'label' => __( 'Routing', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-randomize',
            'file'  => 'routing-tab.php',
        ),
        'package-control' => array(
            'label' => __( 'Package Control', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-archive',
            'file'  => 'package-control-tab.php',
        ),
        'reports' => array(
            'label' => __( 'Reports', 'epic-marks-shipping' ),
            'icon'  => 'dashicons-chart-bar',
            'file'  => 'reports-tab.php',
        ),
    );
}

/**
 * Get current active tab
 */
function em_shipping_get_current_tab() {
    $tabs = em_shipping_get_tabs();
    $current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'locations';

    // Validate tab exists
    if ( ! isset( $tabs[ $current ] ) ) {
        $current = 'locations';
    }

    return $current;
}

/**
 * Render tab navigation
 */
function em_shipping_render_tab_navigation() {
    $tabs = em_shipping_get_tabs();
    $current_tab = em_shipping_get_current_tab();
    $page_url = admin_url( 'admin.php?page=em-ups-shipping' );
    ?>
    <nav class="nav-tab-wrapper em-shipping-tabs">
        <?php foreach ( $tabs as $tab_key => $tab ) : ?>
            <?php
            $is_active = ( $current_tab === $tab_key );
            $tab_url = add_query_arg( 'tab', $tab_key, $page_url );
            $classes = array( 'nav-tab' );
            if ( $is_active ) {
                $classes[] = 'nav-tab-active';
            }
            ?>
            <a href="<?php echo esc_url( $tab_url ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                <?php echo esc_html( $tab['label'] ); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

/**
 * Render current tab content
 */
function em_shipping_render_tab_content() {
    $tabs = em_shipping_get_tabs();
    $current_tab = em_shipping_get_current_tab();

    if ( ! isset( $tabs[ $current_tab ] ) ) {
        echo '<p>' . esc_html__( 'Invalid tab selected.', 'epic-marks-shipping' ) . '</p>';
        return;
    }

    // Check for location edit/new action
    if ( $current_tab === 'locations' && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) ) {
        $location_edit_file = EM_SHIPPING_PLUGIN_DIR . 'admin/location-edit.php';
        if ( file_exists( $location_edit_file ) ) {
            include $location_edit_file;
            return;
        }
    }

    // Check for profile edit/new action
    if ( $current_tab === 'profiles' && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) ) {
        $profile_edit_file = EM_SHIPPING_PLUGIN_DIR . 'admin/profile-edit.php';
        if ( file_exists( $profile_edit_file ) ) {
            include $profile_edit_file;
            return;
        }
    }

    // Check for rule edit/new action
    if ( $current_tab === 'rules' && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) ) {
        $rule_edit_file = EM_SHIPPING_PLUGIN_DIR . 'admin/rule-edit.php';
        if ( file_exists( $rule_edit_file ) ) {
            include $rule_edit_file;
            return;
        }
    }

    // Check for box edit/new action
    if ( $current_tab === 'package-control' && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) ) {
        $box_edit_file = EM_SHIPPING_PLUGIN_DIR . 'admin/box-edit.php';
        if ( file_exists( $box_edit_file ) ) {
            include $box_edit_file;
            return;
        }
    }

    $tab_file = EM_SHIPPING_PLUGIN_DIR . 'admin/tabs/' . $tabs[ $current_tab ]['file'];

    if ( file_exists( $tab_file ) ) {
        include $tab_file;
    } else {
        echo '<p>' . esc_html__( 'Tab content file not found.', 'epic-marks-shipping' ) . '</p>';
    }
}

/**
 * Enqueue admin tab styles and scripts
 */
function em_shipping_enqueue_tab_styles( $hook ) {
    // Only load on our settings page
    if ( 'woocommerce_page_em-ups-shipping' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'em-shipping-admin-tabs',
        EM_SHIPPING_PLUGIN_URL . 'assets/admin-tabs.css',
        array(),
        EM_SHIPPING_VERSION
    );

    // Get current tab
    $current_tab = em_shipping_get_current_tab();

    // Enqueue location admin JS and CSS on locations tab
    if ( $current_tab === 'locations' ) {
        wp_enqueue_script(
            'em-shipping-location-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/location-admin.js',
            array( 'jquery' ),
            EM_SHIPPING_VERSION,
            true
        );
        
        wp_localize_script(
            'em-shipping-location-admin',
            'emLocationAdmin',
            array(
                'nonce' => wp_create_nonce( 'em_location_nonce' ),
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'confirmDelete' => __( 'Are you sure you want to delete this location? This action cannot be undone.', 'epic-marks-shipping' ),
            )
        );

        wp_enqueue_style(
            'em-shipping-location-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/location-admin.css',
            array(),
            EM_SHIPPING_VERSION
        );
    }

    // Enqueue profile admin JS on profiles tab
    if ( $current_tab === 'profiles' ) {
        wp_enqueue_script(
            'em-shipping-profile-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/profile-admin.js',
            array( 'jquery' ),
            EM_SHIPPING_VERSION,
            true
        );
    }

    // Enqueue rule admin JS and CSS on rules tab
    if ( $current_tab === 'rules' ) {
        wp_enqueue_script(
            'em-shipping-rule-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/rule-admin.js',
            array( 'jquery' ),
            EM_SHIPPING_VERSION,
            true
        );

        wp_enqueue_style(
            'em-shipping-rule-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/rule-admin.css',
            array(),
            EM_SHIPPING_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'em_shipping_enqueue_tab_styles' );
