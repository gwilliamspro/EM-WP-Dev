<?php
/**
 * Kadence Child Theme Functions
 *
 * @package Kadence_Child
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue parent and child theme styles
 */
function kadence_child_enqueue_styles() {
    // Enqueue parent theme stylesheet
    wp_enqueue_style(
        'kadence-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme()->parent()->get( 'Version' )
    );

    // Enqueue child theme stylesheet
    wp_enqueue_style(
        'kadence-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'kadence-parent-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'kadence_child_enqueue_styles', 20 );

/**
 * Theme setup
 */
function kadence_child_setup() {
    // Add support for WooCommerce
    add_theme_support( 'woocommerce' );

    // Add support for WooCommerce product gallery features
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'kadence_child_setup', 11 );

/**
 * Security: Disable XML-RPC (common attack vector)
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
