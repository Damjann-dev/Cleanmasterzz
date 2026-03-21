<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Post_Types {

    public static function register() {
        // Dienst (Service)
        register_post_type( 'dienst', array(
            'labels' => array(
                'name'               => 'Diensten',
                'singular_name'      => 'Dienst',
                'add_new'            => 'Nieuwe dienst',
                'add_new_item'       => 'Nieuwe dienst toevoegen',
                'edit_item'          => 'Dienst bewerken',
                'view_item'          => 'Dienst bekijken',
                'all_items'          => 'Alle diensten',
                'search_items'       => 'Diensten zoeken',
                'not_found'          => 'Geen diensten gevonden',
            ),
            'public'             => true,
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'diensten' ),
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
            'menu_icon'          => 'dashicons-hammer',
            'show_in_rest'       => true,
            'show_in_menu'       => false,
        ) );

        // Boeking (Booking)
        register_post_type( 'boeking', array(
            'labels' => array(
                'name'               => 'Boekingen',
                'singular_name'      => 'Boeking',
                'all_items'          => 'Alle boekingen',
                'edit_item'          => 'Boeking bekijken',
            ),
            'public'             => false,
            'show_ui'            => true,
            'supports'           => array( 'title', 'custom-fields' ),
            'menu_icon'          => 'dashicons-calendar-alt',
            'capability_type'    => 'post',
            'show_in_menu'       => false,
        ) );

        // Bedrijf (Company)
        register_post_type( 'cm_bedrijf', array(
            'labels' => array(
                'name'               => 'Bedrijven',
                'singular_name'      => 'Bedrijf',
            ),
            'public'             => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'supports'           => array( 'title' ),
            'show_in_rest'       => false,
        ) );

        // Werkgebied (Work Area)
        register_post_type( 'cm_werkgebied', array(
            'labels' => array(
                'name'               => 'Werkgebieden',
                'singular_name'      => 'Werkgebied',
            ),
            'public'             => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'supports'           => array( 'title' ),
            'show_in_rest'       => false,
        ) );
    }
}
