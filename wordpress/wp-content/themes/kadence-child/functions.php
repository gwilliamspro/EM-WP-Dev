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

/**
 * Enqueue Inter font from Google Fonts
 */
function em_enqueue_inter_font() {
    wp_enqueue_style(
        'em-inter-font',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'em_enqueue_inter_font' );

/**
 * Set minimum password length to 8 characters
 */
function em_set_min_password_length( $errors, $user_data ) {
    $password = isset( $_POST['pass1'] ) ? $_POST['pass1'] : '';

    if ( ! empty( $password ) && strlen( $password ) < 8 ) {
        $errors->add( 'pass', __( '<strong>Error</strong>: Password must be at least 8 characters long.' ) );
    }

    return $errors;
}
add_filter( 'user_profile_update_errors', 'em_set_min_password_length', 10, 2 );
add_filter( 'registration_errors', 'em_set_min_password_length', 10, 2 );
add_filter( 'validate_password_reset', 'em_set_min_password_length', 10, 2 );

/**
 * Reduce password strength requirement
 * WordPress uses zxcvbn strength meter: 0 (weak) to 4 (strong)
 * Setting to 1 allows weak passwords that meet minimum length
 */
add_filter( 'woocommerce_min_password_strength', function() {
    return 1; // Allow weak passwords (just need 8+ characters)
} );

/**
 * Disable WordPress default password strength enforcement
 */
add_action( 'admin_enqueue_scripts', function() {
    wp_dequeue_script( 'wc-password-strength-meter' );
}, 100 );

add_action( 'wp_enqueue_scripts', function() {
    wp_dequeue_script( 'wc-password-strength-meter' );
}, 100 );

/**
 * Override WordPress password strength validation
 * Remove strict password requirements and only enforce 8 character minimum
 */
add_filter( 'user_profile_update_errors', 'em_override_password_strength', 0, 3 );
add_filter( 'registration_errors', 'em_override_password_strength', 0, 3 );
add_filter( 'validate_password_reset', 'em_override_password_strength', 0, 3 );

function em_override_password_strength( $errors, $update = null, $user = null ) {
    // Remove any existing password strength errors
    if ( isset( $errors->errors['pass'] ) ) {
        foreach ( $errors->errors['pass'] as $key => $error ) {
            // Remove strict password strength messages
            if ( strpos( $error, 'stronger password' ) !== false || 
                 strpos( $error, '12 characters' ) !== false ||
                 strpos( $error, 'Uppercase' ) !== false ) {
                unset( $errors->errors['pass'][$key] );
            }
        }
        // Clean up empty arrays
        if ( empty( $errors->errors['pass'] ) ) {
            unset( $errors->errors['pass'] );
        }
    }
    
    return $errors;
}

/**
 * Disable password strength meter JavaScript
 */
add_action( 'wp_print_scripts', function() {
    wp_dequeue_script( 'password-strength-meter' );
}, 100 );

add_action( 'admin_print_scripts', function() {
    wp_dequeue_script( 'password-strength-meter' );
}, 100 );
