<?php
/**
 * Plugin Name: Cleanmasterzz Calculator
 * Plugin URI:  https://cleanmasterzz.nl
 * Description: Prijscalculator met multi-dienst selectie, werkgebieden, sub-opties en boekingen.
 * Version:     1.0.3-beta
 * Author:      CleanMasterzz
 * Text Domain: cleanmasterzz-calculator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CMCALC_VERSION', '1.0.3-beta' );
define( 'CMCALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CMCALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-post-types.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-meta-boxes.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-rest-api.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-shortcode.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-seeder.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-admin.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-email.php';
require_once CMCALC_PLUGIN_DIR . 'includes/class-cmcalc-updater.php';

// Init
add_action( 'init', array( 'CMCalc_Post_Types', 'register' ), 5 );
add_action( 'rest_api_init', array( 'CMCalc_REST_API', 'register_routes' ) );
add_action( 'init', array( 'CMCalc_Shortcode', 'register' ) );

if ( is_admin() ) {
    CMCalc_Admin::init();
    CMCalc_Meta_Boxes::init();
}

// Auto-updater (GitHub releases)
CMCalc_Updater::init( __FILE__ );

// Activation
register_activation_hook( __FILE__, function() {
    CMCalc_Post_Types::register();
    flush_rewrite_rules();
    CMCalc_Seeder::seed();
    update_option( 'cmcalc_activation_redirect', 'yes' );
} );

// Redirect to setup wizard on first activation
add_action( 'admin_init', function() {
    if ( get_option( 'cmcalc_activation_redirect' ) === 'yes' ) {
        delete_option( 'cmcalc_activation_redirect' );
        if ( ! wp_doing_ajax() && ! isset( $_GET['activate-multi'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=cmcalc-setup' ) );
            exit;
        }
    }
} );
