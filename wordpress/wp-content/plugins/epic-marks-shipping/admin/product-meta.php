<?php
/**
 * Product Shipping Meta Fields
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add shipping fields to product data tabs
 */
function em_product_shipping_tab_content() {
    global $post;

    // Get assigned profile
    $assigned_profile_id = get_post_meta( $post->ID, '_shipping_profile', true );

    // Get all profiles
    $all_profiles = EM_Shipping_Profile::get_all();
    $profile_options = array( '' => __( '-- Auto (from tags) --', 'epic-marks-shipping' ) );

    foreach ( $all_profiles as $profile_id => $profile_data ) {
        $profile_options[ $profile_id ] = $profile_data['name'];
    }

    echo '<div class="options_group">';

    // Profile selector
    woocommerce_wp_select( array(
        'id' => '_shipping_profile',
        'label' => __( 'Shipping Profile', 'epic-marks-shipping' ),
        'options' => $profile_options,
        'value' => $assigned_profile_id,
        'desc_tip' => true,
        'description' => __( 'Select a shipping profile for this product. Leave as "Auto" to use tag-based routing.', 'epic-marks-shipping' ),
    ) );

    // Get effective profile (assigned or from tags)
    if ( ! empty( $assigned_profile_id ) && isset( $all_profiles[ $assigned_profile_id ] ) ) {
        $profile = $all_profiles[ $assigned_profile_id ];
        $location_source = 'profile';
        $locations = $profile['fulfillment_locations'];
        $location_note = sprintf(
            __( 'Fulfillment: %s (from profile: %s)', 'epic-marks-shipping' ),
            implode( ', ', array_map( 'ucfirst', $locations ) ),
            $profile['name']
        );
    } else {
        // Fall back to tag-based detection
        $tags = wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'names' ) );
        if ( is_wp_error( $tags ) ) {
            $tags = array();
        }

        $settings = get_option( 'em_ups_settings', array() );
        $has_warehouse_tag = in_array( 'SSAW-App', $tags, true );
        $has_store_tag = in_array( 'available-in-store', $tags, true );

        if ( $has_warehouse_tag && $has_store_tag ) {
            $overlap_pref = $settings['overlap_preference'] ?? 'warehouse';
            $location = $overlap_pref;
            $location_note = sprintf(
                __( 'Ships from: %s (both tags present, using preference setting)', 'epic-marks-shipping' ),
                ucfirst( $overlap_pref )
            );
        } elseif ( $has_warehouse_tag ) {
            $location = 'warehouse';
            $location_note = __( 'Ships from: Warehouse (SSAW-App tag)', 'epic-marks-shipping' );
        } elseif ( $has_store_tag ) {
            $location = 'store';
            $location_note = __( 'Ships from: Store (available-in-store tag)', 'epic-marks-shipping' );
        } else {
            $default_location = $settings['default_location'] ?? 'warehouse';
            $location = $default_location;
            $location_note = sprintf(
                __( 'Ships from: %s (default - no location tags)', 'epic-marks-shipping' ),
                ucfirst( $default_location )
            );
        }

        $location_source = 'tags';
    }

    // Display location info
    echo '<p class="form-field"><strong>' . esc_html( $location_note ) . '</strong></p>';

    if ( $location_source === 'tags' ) {
        echo '<p class="form-field description">'
            . esc_html__( 'Location is determined by product tags: "SSAW-App" for warehouse, "available-in-store" for store. Assign a profile above for more control.', 'epic-marks-shipping' )
            . '</p>';
    }
    
    // Markup enable checkbox
    woocommerce_wp_checkbox( array(
        'id' => '_em_enable_shipping_markup',
        'label' => __( 'Enable Shipping Markup', 'epic-marks-shipping' ),
        'description' => __( 'Add markup to shipping rates for this product', 'epic-marks-shipping' ),
        'desc_tip' => true,
    ) );
    
    // Markup type
    woocommerce_wp_select( array(
        'id' => '_em_markup_type',
        'label' => __( 'Markup Type', 'epic-marks-shipping' ),
        'options' => array(
            'percentage' => __( 'Percentage', 'epic-marks-shipping' ),
            'flat' => __( 'Flat Rate', 'epic-marks-shipping' )
        ),
        'desc_tip' => true,
        'description' => __( 'Choose how markup is calculated', 'epic-marks-shipping' ),
    ) );
    
    // Markup value
    woocommerce_wp_text_input( array(
        'id' => '_em_markup_value',
        'label' => __( 'Markup Value', 'epic-marks-shipping' ),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => '0.01',
            'min' => '0'
        ),
        'desc_tip' => true,
        'description' => __( 'Enter percentage (e.g. 15 for 15%) or flat amount (e.g. 5.00 for $5)', 'epic-marks-shipping' ),
    ) );
    
    // Box assignment fields
    echo '<h4 style="margin-top: 20px;">' . esc_html__( 'Box & Package Configuration', 'epic-marks-shipping' ) . '</h4>';
    
    // Get all boxes
    $boxes = EM_Box_Manager::get_boxes();
    $box_options = array( '' => __( '-- Auto-select smallest box --', 'epic-marks-shipping' ) );
    
    foreach ( $boxes as $box ) {
        if ( isset( $box['status'] ) && $box['status'] === 'active' ) {
            $dim_weight = EM_Box_Manager::calculate_dim_weight( $box );
            $box_options[ $box['id'] ] = sprintf(
                '%s (%s × %s × %s in, %s lbs max, %s lbs dim)',
                $box['name'],
                $box['outer_dimensions']['length'],
                $box['outer_dimensions']['width'],
                $box['outer_dimensions']['height'],
                $box['max_weight'],
                number_format( $dim_weight, 2 )
            );
        }
    }
    
    // Preferred box selector
    woocommerce_wp_select( array(
        'id' => '_preferred_box',
        'label' => __( 'Preferred Box', 'epic-marks-shipping' ),
        'options' => $box_options,
        'desc_tip' => true,
        'description' => __( 'Choose a specific box for this product, or leave as auto to let the system select the smallest box that fits.', 'epic-marks-shipping' ),
    ) );
    
    // Ships separately checkbox
    woocommerce_wp_checkbox( array(
        'id' => '_ships_separately',
        'label' => __( 'Ships Separately', 'epic-marks-shipping' ),
        'description' => __( 'This item must ship in its own package and cannot be combined with other items', 'epic-marks-shipping' ),
        'desc_tip' => true,
    ) );
    
    // Requires tube checkbox
    woocommerce_wp_checkbox( array(
        'id' => '_requires_tube',
        'label' => __( 'Requires Tube Packaging', 'epic-marks-shipping' ),
        'description' => __( 'This item cannot be folded and must ship in a tube (e.g., DTF rolls, posters)', 'epic-marks-shipping' ),
        'desc_tip' => true,
    ) );
    
    // Fragile handling checkbox
    woocommerce_wp_checkbox( array(
        'id' => '_requires_fragile_handling',
        'label' => __( 'Fragile Item', 'epic-marks-shipping' ),
        'description' => __( 'Add fragile handling fee for this item', 'epic-marks-shipping' ),
        'desc_tip' => true,
    ) );
    
    // Signature required checkbox
    woocommerce_wp_checkbox( array(
        'id' => '_requires_signature',
        'label' => __( 'Signature Required', 'epic-marks-shipping' ),
        'description' => __( 'Require signature on delivery for this item', 'epic-marks-shipping' ),
        'desc_tip' => true,
    ) );
    
    echo '</div>';
}
add_action( 'woocommerce_product_options_shipping', 'em_product_shipping_tab_content' );

/**
 * Save product shipping meta
 */
function em_save_product_shipping_meta( $post_id ) {
    // Save shipping profile
    if ( isset( $_POST['_shipping_profile'] ) ) {
        $profile_id = sanitize_text_field( $_POST['_shipping_profile'] );
        if ( ! empty( $profile_id ) ) {
            update_post_meta( $post_id, '_shipping_profile', $profile_id );
        } else {
            delete_post_meta( $post_id, '_shipping_profile' );
        }
    }

    // Enable markup checkbox
    $enable_markup = isset( $_POST['_em_enable_shipping_markup'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_em_enable_shipping_markup', $enable_markup );
    
    // Markup type
    if ( isset( $_POST['_em_markup_type'] ) ) {
        $markup_type = sanitize_text_field( $_POST['_em_markup_type'] );
        update_post_meta( $post_id, '_em_markup_type', $markup_type );
    }
    
    // Markup value
    if ( isset( $_POST['_em_markup_value'] ) ) {
        $markup_value = floatval( $_POST['_em_markup_value'] );
        update_post_meta( $post_id, '_em_markup_value', $markup_value );
    }
    
    // Box assignment fields
    if ( isset( $_POST['_preferred_box'] ) ) {
        $preferred_box = sanitize_text_field( $_POST['_preferred_box'] );
        if ( ! empty( $preferred_box ) ) {
            update_post_meta( $post_id, '_preferred_box', $preferred_box );
        } else {
            delete_post_meta( $post_id, '_preferred_box' );
        }
    }
    
    // Ships separately checkbox
    $ships_separately = isset( $_POST['_ships_separately'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_ships_separately', $ships_separately );
    
    // Requires tube checkbox
    $requires_tube = isset( $_POST['_requires_tube'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_requires_tube', $requires_tube );
    
    // Fragile handling checkbox
    $requires_fragile = isset( $_POST['_requires_fragile_handling'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_requires_fragile_handling', $requires_fragile );
    
    // Signature required checkbox
    $requires_signature = isset( $_POST['_requires_signature'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_requires_signature', $requires_signature );
    
    // Check if product weight is set
    $product = wc_get_product( $post_id );
    if ( $product && ! $product->get_weight() ) {
        update_post_meta( $post_id, '_em_weight_warning', 'yes' );
    } else {
        delete_post_meta( $post_id, '_em_weight_warning' );
    }
}
add_action( 'woocommerce_process_product_meta', 'em_save_product_shipping_meta' );

/**
 * Display weight warning if product has no weight
 */
function em_product_weight_warning() {
    global $post;
    
    $has_warning = get_post_meta( $post->ID, '_em_weight_warning', true );
    
    if ( $has_warning === 'yes' ) {
        ?>
        <div class="notice notice-warning inline">
            <p>
                <strong><?php esc_html_e( 'Shipping Warning:', 'epic-marks-shipping' ); ?></strong>
                <?php esc_html_e( 'This product has no weight set. A default weight of 1 lb will be used for UPS rate calculations.', 'epic-marks-shipping' ); ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'edit_form_after_title', 'em_product_weight_warning' );
