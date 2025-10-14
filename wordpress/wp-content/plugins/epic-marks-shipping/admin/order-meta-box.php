<?php
/**
 * Order Meta Box - PirateShip Integration
 *
 * @package Epic_Marks_Shipping
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add PirateShip meta box to order edit page
 */
function em_add_pirateship_meta_box() {
    add_meta_box(
        'em_pirateship_shipping',
        __( 'Purchase Shipping Label', 'epic-marks-shipping' ),
        'em_render_pirateship_meta_box',
        'shop_order',
        'side',
        'default'
    );
    
    // HPOS compatibility
    add_meta_box(
        'em_pirateship_shipping',
        __( 'Purchase Shipping Label', 'epic-marks-shipping' ),
        'em_render_pirateship_meta_box',
        'woocommerce_page_wc-orders',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'em_add_pirateship_meta_box' );

/**
 * Render PirateShip meta box content
 */
function em_render_pirateship_meta_box( $post_or_order ) {
    // Get order object (HPOS compatible)
    $order = ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
    
    if ( ! $order ) {
        return;
    }
    
    // Get shipping information
    $shipping_address = $order->get_shipping_address_1();
    $shipping_address_2 = $order->get_shipping_address_2();
    $shipping_city = $order->get_shipping_city();
    $shipping_state = $order->get_shipping_state();
    $shipping_postcode = $order->get_shipping_postcode();
    $shipping_country = $order->get_shipping_country();
    $shipping_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
    
    // Get shipping method
    $shipping_methods = $order->get_shipping_methods();
    $shipping_method = '';
    $service_code = '';
    
    foreach ( $shipping_methods as $method ) {
        $shipping_method = $method->get_method_title();
        $meta_data = $method->get_meta_data();
        foreach ( $meta_data as $meta ) {
            if ( $meta->key === 'service_code' ) {
                $service_code = $meta->value;
                break;
            }
        }
        break;
    }
    
    // Calculate total weight
    $total_weight = 0;
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( $product ) {
            $weight = $product->get_weight() ? floatval( $product->get_weight() ) : 1;
            $total_weight += $weight * $item->get_quantity();
        }
    }
    
    // Map service code to PirateShip service
    $pirateship_service = em_map_ups_to_pirateship_service( $service_code );
    
    // Build PirateShip URL
    $pirateship_url = em_build_pirateship_url( array(
        'to_name' => $shipping_name,
        'to_address' => $shipping_address,
        'to_address2' => $shipping_address_2,
        'to_city' => $shipping_city,
        'to_state' => $shipping_state,
        'to_zip' => $shipping_postcode,
        'to_country' => $shipping_country,
        'weight' => $total_weight,
        'service' => $pirateship_service
    ) );
    
    ?>
    <div class="em-pirateship-info">
        <p><strong><?php esc_html_e( 'Shipping Details:', 'epic-marks-shipping' ); ?></strong></p>
        <p>
            <?php echo esc_html( $shipping_name ); ?><br>
            <?php echo esc_html( $shipping_address ); ?><br>
            <?php if ( $shipping_address_2 ) echo esc_html( $shipping_address_2 ) . '<br>'; ?>
            <?php echo esc_html( $shipping_city ); ?>, <?php echo esc_html( $shipping_state ); ?> <?php echo esc_html( $shipping_postcode ); ?>
        </p>
        
        <p>
            <strong><?php esc_html_e( 'Service:', 'epic-marks-shipping' ); ?></strong>
            <?php echo esc_html( $shipping_method ); ?>
        </p>
        
        <p>
            <strong><?php esc_html_e( 'Total Weight:', 'epic-marks-shipping' ); ?></strong>
            <?php echo esc_html( number_format( $total_weight, 2 ) ); ?> lbs
        </p>
        
        <p>
            <a href="<?php echo esc_url( $pirateship_url ); ?>" target="_blank" class="button button-primary" style="width:100%;text-align:center;">
                <?php esc_html_e( 'Purchase Label on PirateShip', 'epic-marks-shipping' ); ?>
            </a>
        </p>
        
        <p class="description">
            <?php esc_html_e( 'Click to open PirateShip with pre-filled shipping information. You may need to connect your PirateShip account first.', 'epic-marks-shipping' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Map UPS service code to PirateShip service
 */
function em_map_ups_to_pirateship_service( $code ) {
    $service_map = array(
        '03' => 'ups_ground',
        '02' => 'ups_2day',
        '01' => 'ups_next_day',
        '12' => 'ups_3day',
    );
    
    return $service_map[ $code ] ?? 'ups_ground';
}

/**
 * Build PirateShip deep link URL
 */
function em_build_pirateship_url( $params ) {
    $base_url = 'https://ship.pirateship.com/ship';
    
    $query_params = array(
        'to_name' => $params['to_name'] ?? '',
        'to_street1' => $params['to_address'] ?? '',
        'to_street2' => $params['to_address2'] ?? '',
        'to_city' => $params['to_city'] ?? '',
        'to_state' => $params['to_state'] ?? '',
        'to_zip' => $params['to_zip'] ?? '',
        'to_country' => $params['to_country'] ?? 'US',
        'weight_lb' => $params['weight'] ?? 1,
        'service' => $params['service'] ?? 'ups_ground'
    );
    
    return add_query_arg( array_filter( $query_params ), $base_url );
}
