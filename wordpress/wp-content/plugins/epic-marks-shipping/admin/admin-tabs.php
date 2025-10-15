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
    $current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'setup';

    // Validate tab exists
    if ( ! isset( $tabs[ $current ] ) ) {
        $current = 'setup';
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

    // Check for profile edit/new action
    if ( $current_tab === 'profiles' && isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) ) {
        $profile_edit_file = EM_SHIPPING_PLUGIN_DIR . 'admin/profile-edit.php';
        if ( file_exists( $profile_edit_file ) ) {
            include $profile_edit_file;
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

    // Enqueue profile admin JS on profiles tab
    $current_tab = em_shipping_get_current_tab();
    if ( $current_tab === 'profiles' ) {
        wp_enqueue_script(
            'em-shipping-profile-admin',
            EM_SHIPPING_PLUGIN_URL . 'assets/profile-admin.js',
            array( 'jquery' ),
            EM_SHIPPING_VERSION,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'em_shipping_enqueue_tab_styles' );
