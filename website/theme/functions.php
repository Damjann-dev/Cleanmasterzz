<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CM_VERSION', '1.0.0' );
define( 'CM_DIR',     get_template_directory() );
define( 'CM_URL',     get_template_directory_uri() );

// ─── Theme setup ──────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery', 'caption' ) );
    add_theme_support( 'custom-logo' );

    register_nav_menus( array(
        'primary' => 'Hoofdmenu',
        'footer'  => 'Footermenu',
    ) );
} );

// ─── Assets ───────────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    // Google Fonts
    wp_enqueue_style( 'cm-fonts',
        'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;600&display=swap',
        array(), null
    );

    // GSAP
    wp_enqueue_script( 'gsap',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
        array(), '3.12.2', true
    );
    wp_enqueue_script( 'gsap-st',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
        array( 'gsap' ), '3.12.2', true
    );
    wp_enqueue_script( 'gsap-text',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js',
        array( 'gsap' ), '3.12.2', true
    );

    // Theme CSS + JS
    wp_enqueue_style( 'cm-main', CM_URL . '/css/main.css', array(), CM_VERSION );
    wp_enqueue_script( 'cm-main', CM_URL . '/js/main.js', array( 'gsap', 'gsap-st' ), CM_VERSION, true );
} );

// ─── Admin assets ─────────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', function () {
    wp_enqueue_style( 'cm-admin', CM_URL . '/css/admin.css', array(), CM_VERSION );
} );

// ─── Custom nav walker ────────────────────────────────────────────────────────
require_once CM_DIR . '/inc/nav-walker.php';

// ─── Helper functions ─────────────────────────────────────────────────────────
function cm_get_option( $key, $default = '' ) {
    return get_option( 'cm_' . $key, $default );
}

function cm_noise() {
    return '<div class="cm-noise" aria-hidden="true"></div>';
}

function cm_glow( $color = 'indigo' ) {
    return '<div class="cm-glow cm-glow--' . esc_attr($color) . '" aria-hidden="true"></div>';
}
