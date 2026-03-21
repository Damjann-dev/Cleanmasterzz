<?php
/**
 * Cleanmasterzz Calculator Uninstall
 *
 * Fired when the plugin is uninstalled.
 * Data is preserved by default. To remove all data,
 * set the CMCALC_DELETE_DATA option to true before uninstalling.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Only delete data if explicitly requested
if ( ! get_option( 'cmcalc_delete_data_on_uninstall', false ) ) return;

// Delete werkgebieden
$werkgebieden = get_posts( array( 'post_type' => 'cm_werkgebied', 'numberposts' => -1, 'post_status' => 'any' ) );
foreach ( $werkgebieden as $post ) {
    wp_delete_post( $post->ID, true );
}

// Delete bedrijven
$bedrijven = get_posts( array( 'post_type' => 'cm_bedrijf', 'numberposts' => -1, 'post_status' => 'any' ) );
foreach ( $bedrijven as $post ) {
    wp_delete_post( $post->ID, true );
}

// Clean up options
delete_option( 'cmcalc_db_version' );
delete_option( 'cmcalc_delete_data_on_uninstall' );
delete_option( 'cmcalc_styles' );
delete_option( 'cmcalc_settings' );
