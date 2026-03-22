<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // AJAX handlers
        $ajax_actions = array(
            'cmcalc_save_dienst',
            'cmcalc_add_dienst',
            'cmcalc_delete_dienst',
            'cmcalc_toggle_dienst',
            'cmcalc_reorder',
            'cmcalc_save_sub_options',
            'cmcalc_save_werkgebied',
            'cmcalc_delete_werkgebied',
            'cmcalc_toggle_werkgebied',
            'cmcalc_save_travel_price',
            'cmcalc_seed_diensten',
            'cmcalc_save_styles',
            'cmcalc_save_settings',
            'cmcalc_get_bookings_page',
            'cmcalc_get_booking_detail',
            'cmcalc_update_booking_status',
            'cmcalc_update_booking',
            'cmcalc_delete_booking',
            'cmcalc_save_booking_notes',
            'cmcalc_email_client',
            'cmcalc_resend_booking',
            'cmcalc_export_bookings',
            'cmcalc_save_bedrijf',
            'cmcalc_delete_bedrijf',
            'cmcalc_toggle_bedrijf',
            'cmcalc_wizard_complete',
            'cmcalc_save_dienst_bedrijven',
            'cmcalc_save_volume_tiers',
            'cmcalc_preview_email',
            'cmcalc_save_github_token',
            'cmcalc_check_update',
        );
        foreach ( $ajax_actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, 'handle_' . $action ) );
        }
    }

    public static function add_menu() {
        add_menu_page(
            'Calculator Dashboard',
            'Calculator',
            'manage_options',
            'cmcalc-dashboard',
            array( __CLASS__, 'render_dashboard' ),
            'dashicons-calculator',
            26
        );

        add_submenu_page(
            null,
            'Calculator Setup',
            'Setup',
            'manage_options',
            'cmcalc-setup',
            array( __CLASS__, 'render_setup_wizard' )
        );
    }

    public static function render_setup_wizard() {
        include CMCALC_PLUGIN_DIR . 'admin/views/setup-wizard.php';
    }

    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_cmcalc-dashboard' && $hook !== 'admin_page_cmcalc-setup' ) return;

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'cmcalc-admin', CMCALC_PLUGIN_URL . 'admin/css/admin.css', array( 'wp-color-picker' ), CMCALC_VERSION );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'cmcalc-admin', CMCALC_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), CMCALC_VERSION, true );
        wp_localize_script( 'cmcalc-admin', 'cmcalcAdmin', array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'restUrl'   => rest_url( 'cleanmasterzz/v1/' ),
            'nonce'     => wp_create_nonce( 'cmcalc_admin_nonce' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'styles'    => self::get_styles(),
            'presets'   => self::get_style_presets(),
            'settings'  => self::get_settings(),
            'bedrijven' => self::get_bedrijven_data(),
        ) );
    }

    public static function render_dashboard() {
        include CMCALC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    // ─── AJAX: Diensten ───

    public static function handle_cmcalc_save_dienst() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $field   = sanitize_text_field( $_POST['field'] ?? '' );
        $value   = sanitize_text_field( $_POST['value'] ?? '' );

        if ( ! $post_id || ! $field ) wp_send_json_error( 'Ongeldige gegevens' );

        $allowed_fields = array(
            'title'            => 'post_title',
            'base_price'       => '_cm_base_price',
            'price_unit'       => '_cm_price_unit',
            'minimum_price'    => '_cm_minimum_price',
            'discount_percent' => '_cm_discount_percent',
            'requires_quote'   => '_cm_requires_quote',
        );

        if ( ! isset( $allowed_fields[ $field ] ) ) wp_send_json_error( 'Ongeldig veld' );

        if ( $field === 'title' ) {
            wp_update_post( array( 'ID' => $post_id, 'post_title' => $value ) );
        } else {
            update_post_meta( $post_id, $allowed_fields[ $field ], $value );
        }

        wp_send_json_success();
    }

    public static function handle_cmcalc_add_dienst() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $title = sanitize_text_field( $_POST['title'] ?? 'Nieuwe dienst' );

        $post_id = wp_insert_post( array(
            'post_type'   => 'dienst',
            'post_title'  => $title,
            'post_status' => 'publish',
            'menu_order'  => 99,
        ) );

        if ( is_wp_error( $post_id ) ) wp_send_json_error( 'Aanmaken mislukt' );

        update_post_meta( $post_id, '_cm_base_price', '0' );
        update_post_meta( $post_id, '_cm_price_unit', 'm2' );
        update_post_meta( $post_id, '_cm_minimum_price', '0' );
        update_post_meta( $post_id, '_cm_discount_percent', '0' );
        update_post_meta( $post_id, '_cm_requires_quote', '0' );
        update_post_meta( $post_id, '_cm_active', '1' );

        wp_send_json_success( array(
            'id'    => $post_id,
            'title' => $title,
        ) );
    }

    public static function handle_cmcalc_delete_dienst() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        wp_trash_post( $post_id );
        wp_send_json_success();
    }

    public static function handle_cmcalc_toggle_dienst() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        $current = get_post_meta( $post_id, '_cm_active', true );
        $new_val = ( $current === '1' ) ? '0' : '1';
        update_post_meta( $post_id, '_cm_active', $new_val );

        wp_send_json_success( array( 'active' => $new_val ) );
    }

    public static function handle_cmcalc_reorder() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $order = $_POST['order'] ?? array();
        if ( ! is_array( $order ) ) wp_send_json_error( 'Ongeldige data' );

        foreach ( $order as $index => $post_id ) {
            wp_update_post( array(
                'ID'         => intval( $post_id ),
                'menu_order' => intval( $index ),
            ) );
        }

        wp_send_json_success();
    }

    public static function handle_cmcalc_save_sub_options() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id     = intval( $_POST['post_id'] ?? 0 );
        $sub_options = $_POST['sub_options'] ?? '[]';

        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        // Validate JSON
        $decoded = json_decode( wp_unslash( $sub_options ), true );
        if ( ! is_array( $decoded ) ) wp_send_json_error( 'Ongeldige JSON' );

        // Sanitize each sub-option
        $clean = array();
        foreach ( $decoded as $opt ) {
            $item = array(
                'label'     => sanitize_text_field( $opt['label'] ?? '' ),
                'type'      => in_array( $opt['type'] ?? '', array( 'checkbox', 'select' ) ) ? $opt['type'] : 'checkbox',
                'surcharge' => floatval( $opt['surcharge'] ?? 0 ),
            );
            if ( $item['type'] === 'select' && ! empty( $opt['options'] ) ) {
                $item['options']    = array_map( 'sanitize_text_field', (array) $opt['options'] );
                $item['surcharges'] = array_map( 'floatval', (array) ( $opt['surcharges'] ?? array() ) );
            }
            if ( $item['label'] ) {
                $clean[] = $item;
            }
        }

        update_post_meta( $post_id, '_cm_sub_options', wp_json_encode( $clean ) );
        wp_send_json_success( array( 'sub_options' => $clean ) );
    }

    public static function handle_cmcalc_save_volume_tiers() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $tiers_json = wp_unslash( $_POST['tiers'] ?? '[]' );

        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        $decoded = json_decode( $tiers_json, true );
        if ( ! is_array( $decoded ) ) wp_send_json_error( 'Ongeldige JSON' );

        $clean = array();
        foreach ( $decoded as $tier ) {
            $item = array(
                'min'   => max( 1, intval( $tier['min'] ?? 1 ) ),
                'max'   => max( 1, intval( $tier['max'] ?? 999 ) ),
                'price' => floatval( $tier['price'] ?? 0 ),
            );
            $clean[] = $item;
        }

        // Sort by min ascending
        usort( $clean, function( $a, $b ) { return $a['min'] - $b['min']; } );

        $bedrijf_id = intval( $_POST['bedrijf_id'] ?? 0 );
        $base_price = isset( $_POST['base_price'] ) ? floatval( $_POST['base_price'] ) : null;

        if ( $bedrijf_id > 0 ) {
            // Per-bedrijf pricing override
            $pricing = json_decode( get_post_meta( $post_id, '_cm_bedrijf_pricing', true ) ?: '{}', true );
            if ( ! is_array( $pricing ) ) $pricing = array();

            $pricing[ $bedrijf_id ] = array(
                'volume_tiers' => $clean,
            );
            if ( $base_price !== null ) {
                $pricing[ $bedrijf_id ]['base_price'] = $base_price;
            }

            update_post_meta( $post_id, '_cm_bedrijf_pricing', wp_json_encode( $pricing ) );
        } else {
            // Default pricing (all companies)
            update_post_meta( $post_id, '_cm_volume_tiers', wp_json_encode( $clean ) );
            if ( $base_price !== null ) {
                update_post_meta( $post_id, '_cm_base_price', $base_price );
            }
        }

        wp_send_json_success( array( 'tiers' => $clean, 'bedrijf_id' => $bedrijf_id ) );
    }

    // ─── AJAX: Email Preview ───

    public static function handle_cmcalc_preview_email() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $html = CMCalc_Email::get_preview_html();
        wp_send_json_success( array( 'html' => $html ) );
    }

    // ─── AJAX: Werkgebieden ───

    public static function handle_cmcalc_save_werkgebied() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $werkgebied_id = intval( $_POST['werkgebied_id'] ?? 0 );
        $name          = sanitize_text_field( $_POST['name'] ?? '' );
        $postcode      = sanitize_text_field( $_POST['postcode'] ?? '' );
        $lat           = floatval( $_POST['lat'] ?? 0 );
        $lon           = floatval( $_POST['lon'] ?? 0 );
        $free_km       = intval( $_POST['free_km'] ?? 20 );

        if ( ! $name ) wp_send_json_error( 'Naam is verplicht' );

        if ( $werkgebied_id ) {
            wp_update_post( array( 'ID' => $werkgebied_id, 'post_title' => $name ) );
        } else {
            $werkgebied_id = wp_insert_post( array(
                'post_type'   => 'cm_werkgebied',
                'post_title'  => $name,
                'post_status' => 'publish',
            ) );
            if ( is_wp_error( $werkgebied_id ) ) wp_send_json_error( 'Aanmaken mislukt' );
            update_post_meta( $werkgebied_id, '_cmcalc_active', '1' );
        }

        update_post_meta( $werkgebied_id, '_cmcalc_postcode', $postcode );
        update_post_meta( $werkgebied_id, '_cmcalc_lat', $lat );
        update_post_meta( $werkgebied_id, '_cmcalc_lon', $lon );
        update_post_meta( $werkgebied_id, '_cmcalc_free_km', $free_km );

        if ( isset( $_POST['bedrijf_id'] ) ) {
            update_post_meta( $werkgebied_id, '_cmcalc_bedrijf_id', intval( $_POST['bedrijf_id'] ) );
        }

        wp_send_json_success( array( 'id' => $werkgebied_id ) );
    }

    public static function handle_cmcalc_delete_werkgebied() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $werkgebied_id = intval( $_POST['werkgebied_id'] ?? 0 );
        if ( ! $werkgebied_id ) wp_send_json_error( 'Ongeldig ID' );

        wp_delete_post( $werkgebied_id, true );
        wp_send_json_success();
    }

    public static function handle_cmcalc_toggle_werkgebied() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $werkgebied_id = intval( $_POST['werkgebied_id'] ?? 0 );
        if ( ! $werkgebied_id ) wp_send_json_error( 'Ongeldig ID' );

        $current = get_post_meta( $werkgebied_id, '_cmcalc_active', true );
        $new_val = ( $current === '1' ) ? '0' : '1';
        update_post_meta( $werkgebied_id, '_cmcalc_active', $new_val );

        wp_send_json_success( array( 'active' => $new_val ) );
    }

    // ─── AJAX: Boekingen ───

    public static function handle_cmcalc_get_bookings_page() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $status    = sanitize_text_field( $_POST['status'] ?? $_GET['status'] ?? '' );
        $search    = sanitize_text_field( $_POST['search'] ?? $_GET['search'] ?? '' );
        $date_from = sanitize_text_field( $_POST['date_from'] ?? $_GET['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_POST['date_to'] ?? $_GET['date_to'] ?? '' );
        $page      = max( 1, intval( $_POST['page'] ?? $_GET['page'] ?? 1 ) );
        $per_page  = max( 1, min( 100, intval( $_POST['per_page'] ?? 20 ) ) );

        $args = array(
            'post_type'      => 'boeking',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Status filter
        if ( $status && $status !== 'alle' ) {
            if ( $status === 'nieuw' ) {
                $args['meta_query'] = array(
                    'relation' => 'OR',
                    array( 'key' => '_cm_booking_status', 'value' => 'nieuw' ),
                    array( 'key' => '_cm_booking_status', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_cm_booking_status', 'value' => '' ),
                );
            } else {
                $args['meta_query'] = array(
                    array( 'key' => '_cm_booking_status', 'value' => $status ),
                );
            }
        }

        // Search
        if ( $search ) {
            // Use meta query for name/email search
            $search_meta = array(
                'relation' => 'OR',
                array( 'key' => '_cm_booking_name', 'value' => $search, 'compare' => 'LIKE' ),
                array( 'key' => '_cm_booking_email', 'value' => $search, 'compare' => 'LIKE' ),
            );
            if ( isset( $args['meta_query'] ) ) {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $args['meta_query'],
                    $search_meta,
                );
            } else {
                $args['meta_query'] = $search_meta;
            }
        }

        // Date range
        if ( $date_from || $date_to ) {
            $args['date_query'] = array();
            if ( $date_from ) $args['date_query']['after'] = $date_from;
            if ( $date_to )   $args['date_query']['before'] = $date_to . ' 23:59:59';
        }

        // Bedrijf filter
        if ( ! empty( $_POST['bedrijf_id'] ) ) {
            $meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
            if ( ! empty( $meta_query ) && ! isset( $meta_query['relation'] ) ) {
                $meta_query = array( 'relation' => 'AND', $meta_query );
            } elseif ( ! empty( $meta_query ) && isset( $meta_query['relation'] ) && $meta_query['relation'] === 'OR' ) {
                $meta_query = array( 'relation' => 'AND', $meta_query );
            }
            $meta_query[] = array( 'key' => '_cm_booking_bedrijf_id', 'value' => intval( $_POST['bedrijf_id'] ) );
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $status_val = get_post_meta( $post->ID, '_cm_booking_status', true );
            $booking_bedrijf_id = get_post_meta( $post->ID, '_cm_booking_bedrijf_id', true );
            $bedrijf_name = '';
            if ( $booking_bedrijf_id ) {
                $bedrijf_post = get_post( intval( $booking_bedrijf_id ) );
                if ( $bedrijf_post ) $bedrijf_name = $bedrijf_post->post_title;
            }
            $bookings[] = array(
                'id'        => $post->ID,
                'date'      => get_the_date( 'd-m-Y H:i', $post ),
                'name'      => get_post_meta( $post->ID, '_cm_booking_name', true ),
                'email'     => get_post_meta( $post->ID, '_cm_booking_email', true ),
                'service'   => get_post_meta( $post->ID, '_cm_booking_service', true ),
                'total'     => floatval( get_post_meta( $post->ID, '_cm_booking_total', true ) ),
                'status'    => $status_val ?: 'nieuw',
                'postcode'  => get_post_meta( $post->ID, '_cm_booking_postcode', true ),
                'house_number' => get_post_meta( $post->ID, '_cm_booking_house_number', true ),
                'distance_km'  => floatval( get_post_meta( $post->ID, '_cm_booking_distance_km', true ) ),
                'werkgebied'   => get_post_meta( $post->ID, '_cm_booking_nearest_werkgebied', true ),
                'bedrijf_name' => $bedrijf_name,
            );
        }

        wp_send_json_success( array(
            'bookings' => $bookings,
            'total'    => $query->found_posts,
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => $query->max_num_pages,
        ) );
    }

    public static function handle_cmcalc_get_booking_detail() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'boeking' ) wp_send_json_error( 'Boeking niet gevonden' );

        $services_json = get_post_meta( $post_id, '_cm_booking_services', true );
        $services = ! empty( $services_json ) ? json_decode( $services_json, true ) : array();
        $status = get_post_meta( $post_id, '_cm_booking_status', true );

        $booking_bedrijf_id = get_post_meta( $post_id, '_cm_booking_bedrijf_id', true );
        $bedrijf_name = '';
        if ( $booking_bedrijf_id ) {
            $bedrijf_post = get_post( intval( $booking_bedrijf_id ) );
            if ( $bedrijf_post ) $bedrijf_name = $bedrijf_post->post_title;
        }

        wp_send_json_success( array(
            'id'                 => $post_id,
            'date'               => get_the_date( 'd-m-Y H:i', $post ),
            'name'               => get_post_meta( $post_id, '_cm_booking_name', true ),
            'email'              => get_post_meta( $post_id, '_cm_booking_email', true ),
            'phone'              => get_post_meta( $post_id, '_cm_booking_phone', true ),
            'address'            => get_post_meta( $post_id, '_cm_booking_address', true ),
            'services'           => is_array( $services ) ? $services : array(),
            'service_summary'    => get_post_meta( $post_id, '_cm_booking_service', true ),
            'total'              => floatval( get_post_meta( $post_id, '_cm_booking_total', true ) ),
            'preferred_date'     => get_post_meta( $post_id, '_cm_booking_date', true ),
            'message'            => get_post_meta( $post_id, '_cm_booking_message', true ),
            'postcode'           => get_post_meta( $post_id, '_cm_booking_postcode', true ),
            'house_number'       => get_post_meta( $post_id, '_cm_booking_house_number', true ),
            'distance_km'        => floatval( get_post_meta( $post_id, '_cm_booking_distance_km', true ) ),
            'travel_surcharge'   => floatval( get_post_meta( $post_id, '_cm_booking_travel_surcharge', true ) ),
            'nearest_werkgebied' => get_post_meta( $post_id, '_cm_booking_nearest_werkgebied', true ),
            'status'             => $status ?: 'nieuw',
            'notes'              => get_post_meta( $post_id, '_cm_booking_notes', true ) ?: '',
            'bedrijf_id'         => $booking_bedrijf_id ? intval( $booking_bedrijf_id ) : 0,
            'bedrijf_name'       => $bedrijf_name,
        ) );
    }

    public static function handle_cmcalc_update_booking_status() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $status  = sanitize_text_field( $_POST['status'] ?? '' );
        $valid   = array( 'nieuw', 'bevestigd', 'gepland', 'voltooid', 'geannuleerd' );

        if ( ! $post_id || ! in_array( $status, $valid ) ) wp_send_json_error( 'Ongeldige gegevens' );

        $old_status = get_post_meta( $post_id, '_cm_booking_status', true ) ?: 'nieuw';
        update_post_meta( $post_id, '_cm_booking_status', $status );

        // Send status update email to customer
        if ( $old_status !== $status ) {
            CMCalc_Email::send_status_update( $post_id, $old_status, $status );
        }

        wp_send_json_success( array( 'status' => $status ) );
    }

    public static function handle_cmcalc_update_booking() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        $allowed = array(
            'name'    => '_cm_booking_name',
            'email'   => '_cm_booking_email',
            'phone'   => '_cm_booking_phone',
            'address' => '_cm_booking_address',
            'date'    => '_cm_booking_date',
            'message' => '_cm_booking_message',
        );

        foreach ( $allowed as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = $field === 'email'
                    ? sanitize_email( $_POST[ $field ] )
                    : sanitize_text_field( $_POST[ $field ] );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }

        // Update post title if name changed
        if ( isset( $_POST['name'] ) ) {
            $service_summary = get_post_meta( $post_id, '_cm_booking_service', true );
            wp_update_post( array(
                'ID'         => $post_id,
                'post_title' => sprintf( '%s - %s', sanitize_text_field( $_POST['name'] ), $service_summary ),
            ) );
        }

        wp_send_json_success();
    }

    public static function handle_cmcalc_delete_booking() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        wp_trash_post( $post_id );
        wp_send_json_success();
    }

    public static function handle_cmcalc_save_booking_notes() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $notes   = sanitize_textarea_field( $_POST['notes'] ?? '' );

        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        update_post_meta( $post_id, '_cm_booking_notes', $notes );
        wp_send_json_success();
    }

    public static function handle_cmcalc_email_client() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $to      = sanitize_email( $_POST['to'] ?? '' );
        $subject = sanitize_text_field( $_POST['subject'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( ! $post_id || ! $to || ! $subject || ! $message ) {
            wp_send_json_error( 'Alle velden zijn verplicht' );
        }

        $settings    = self::get_settings();
        $admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );
        $headers     = array( 'Reply-To: ' . $admin_email );

        $sent = wp_mail( $to, $subject, $message, $headers );

        if ( $sent ) {
            // Append to notes
            $notes = get_post_meta( $post_id, '_cm_booking_notes', true ) ?: '';
            $timestamp = date( 'd-m-Y H:i' );
            $notes .= ( $notes ? "\n" : '' ) . "[{$timestamp}] Email verzonden: {$subject}";
            update_post_meta( $post_id, '_cm_booking_notes', $notes );
            wp_send_json_success( array( 'notes' => $notes ) );
        } else {
            wp_send_json_error( 'Email kon niet worden verzonden' );
        }
    }

    public static function handle_cmcalc_resend_booking() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) wp_send_json_error( 'Ongeldig ID' );

        $sent = CMCalc_Email::send_admin_notification( $post_id );

        if ( $sent ) {
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Email kon niet worden verzonden' );
        }
    }

    public static function handle_cmcalc_export_bookings() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Geen toegang' );

        $status    = sanitize_text_field( $_GET['status'] ?? '' );
        $search    = sanitize_text_field( $_GET['search'] ?? '' );
        $date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
        $date_to   = sanitize_text_field( $_GET['date_to'] ?? '' );

        $args = array(
            'post_type'      => 'boeking',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $status && $status !== 'alle' ) {
            if ( $status === 'nieuw' ) {
                $args['meta_query'] = array(
                    'relation' => 'OR',
                    array( 'key' => '_cm_booking_status', 'value' => 'nieuw' ),
                    array( 'key' => '_cm_booking_status', 'compare' => 'NOT EXISTS' ),
                    array( 'key' => '_cm_booking_status', 'value' => '' ),
                );
            } else {
                $args['meta_query'] = array(
                    array( 'key' => '_cm_booking_status', 'value' => $status ),
                );
            }
        }

        if ( $search ) {
            $search_meta = array(
                'relation' => 'OR',
                array( 'key' => '_cm_booking_name', 'value' => $search, 'compare' => 'LIKE' ),
                array( 'key' => '_cm_booking_email', 'value' => $search, 'compare' => 'LIKE' ),
            );
            if ( isset( $args['meta_query'] ) ) {
                $args['meta_query'] = array( 'relation' => 'AND', $args['meta_query'], $search_meta );
            } else {
                $args['meta_query'] = $search_meta;
            }
        }

        if ( $date_from || $date_to ) {
            $args['date_query'] = array();
            if ( $date_from ) $args['date_query']['after']  = $date_from;
            if ( $date_to )   $args['date_query']['before'] = $date_to . ' 23:59:59';
        }

        $posts = get_posts( $args );

        $filename = 'boekingen-export-' . date( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $output = fopen( 'php://output', 'w' );
        // BOM for Excel UTF-8
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
        fputcsv( $output, array( 'ID', 'Datum', 'Naam', 'Email', 'Telefoon', 'Adres', 'Postcode', 'Huisnummer', 'Diensten', 'Totaal', 'Status', 'Werkgebied', 'Afstand (km)', 'Voorrijkosten', 'Bericht', 'Notities' ), ';' );

        foreach ( $posts as $post ) {
            $status_val = get_post_meta( $post->ID, '_cm_booking_status', true ) ?: 'nieuw';
            fputcsv( $output, array(
                $post->ID,
                get_the_date( 'd-m-Y H:i', $post ),
                get_post_meta( $post->ID, '_cm_booking_name', true ),
                get_post_meta( $post->ID, '_cm_booking_email', true ),
                get_post_meta( $post->ID, '_cm_booking_phone', true ),
                get_post_meta( $post->ID, '_cm_booking_address', true ),
                get_post_meta( $post->ID, '_cm_booking_postcode', true ),
                get_post_meta( $post->ID, '_cm_booking_house_number', true ),
                get_post_meta( $post->ID, '_cm_booking_service', true ),
                number_format( floatval( get_post_meta( $post->ID, '_cm_booking_total', true ) ), 2, ',', '.' ),
                $status_val,
                get_post_meta( $post->ID, '_cm_booking_nearest_werkgebied', true ),
                get_post_meta( $post->ID, '_cm_booking_distance_km', true ),
                number_format( floatval( get_post_meta( $post->ID, '_cm_booking_travel_surcharge', true ) ), 2, ',', '.' ),
                get_post_meta( $post->ID, '_cm_booking_message', true ),
                get_post_meta( $post->ID, '_cm_booking_notes', true ),
            ), ';' );
        }

        fclose( $output );
        die();
    }

    // ─── AJAX: Instellingen ───

    public static function handle_cmcalc_save_travel_price() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $price = floatval( $_POST['price'] ?? 0 );

        // Find travel service (dienst with price_unit = km)
        $travel = get_posts( array(
            'post_type'      => 'dienst',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_cm_price_unit', 'value' => 'km' ),
            ),
        ) );

        if ( ! empty( $travel ) ) {
            update_post_meta( $travel[0]->ID, '_cm_base_price', $price );
            wp_send_json_success();
        } else {
            wp_send_json_error( 'Voorrijkosten dienst niet gevonden' );
        }
    }

    public static function handle_cmcalc_seed_diensten() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        CMCalc_Seeder::seed();
        wp_send_json_success();
    }

    // ─── Helper: Get data for dashboard ───

    public static function get_diensten() {
        return get_posts( array(
            'post_type'      => 'dienst',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'any',
        ) );
    }

    public static function get_werkgebieden() {
        return get_posts( array(
            'post_type'      => 'cm_werkgebied',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ) );
    }

    public static function get_boekingen( $limit = 50 ) {
        return get_posts( array(
            'post_type'      => 'boeking',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
    }

    public static function get_travel_service() {
        $travel = get_posts( array(
            'post_type'      => 'dienst',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array( 'key' => '_cm_price_unit', 'value' => 'km' ),
            ),
        ) );
        return ! empty( $travel ) ? $travel[0] : null;
    }

    // ─── AJAX: Styles ───

    public static function handle_cmcalc_save_styles() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $raw = wp_unslash( $_POST['styles'] ?? '{}' );
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) wp_send_json_error( 'Ongeldige data' );

        $defaults = self::get_style_defaults();
        $clean = array();

        // Sanitize colors
        $color_fields = array( 'primary_color', 'secondary_color', 'accent_color', 'text_color', 'text_light_color', 'bg_color', 'bg_light_color', 'border_color' );
        foreach ( $color_fields as $field ) {
            $clean[ $field ] = isset( $data[ $field ] ) ? sanitize_hex_color( $data[ $field ] ) : $defaults[ $field ];
            if ( ! $clean[ $field ] ) $clean[ $field ] = $defaults[ $field ];
        }

        // Sanitize numbers
        $clean['border_radius']    = isset( $data['border_radius'] ) ? max( 0, min( 30, intval( $data['border_radius'] ) ) ) : $defaults['border_radius'];
        $clean['btn_radius']       = isset( $data['btn_radius'] ) ? max( 0, min( 20, intval( $data['btn_radius'] ) ) ) : $defaults['btn_radius'];
        $clean['font_size_base']   = isset( $data['font_size_base'] ) ? max( 12, min( 20, intval( $data['font_size_base'] ) ) ) : $defaults['font_size_base'];
        $clean['font_size_title']  = isset( $data['font_size_title'] ) ? max( 16, min( 32, intval( $data['font_size_title'] ) ) ) : $defaults['font_size_title'];
        $clean['calc_max_width']   = isset( $data['calc_max_width'] ) ? max( 400, min( 1400, intval( $data['calc_max_width'] ) ) ) : $defaults['calc_max_width'];
        $clean['calc_padding']     = isset( $data['calc_padding'] ) ? max( 8, min( 48, intval( $data['calc_padding'] ) ) ) : $defaults['calc_padding'];
        $clean['calc_spacing']     = in_array( $data['calc_spacing'] ?? '', array( 'compact', 'normal', 'spacious' ) ) ? $data['calc_spacing'] : 'normal';

        // Sanitize booleans and enums
        $clean['shadow_enabled']   = ! empty( $data['shadow_enabled'] );
        $clean['shadow_intensity'] = in_array( $data['shadow_intensity'] ?? '', array( 'light', 'medium', 'strong' ) ) ? $data['shadow_intensity'] : 'medium';
        $clean['preset']           = sanitize_text_field( $data['preset'] ?? 'custom' );

        update_option( 'cmcalc_styles', $clean );
        wp_send_json_success( $clean );
    }

    // ─── AJAX: Settings ───

    public static function handle_cmcalc_save_settings() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $raw = wp_unslash( $_POST['settings'] ?? '{}' );
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) ) wp_send_json_error( 'Ongeldige data' );

        $defaults = self::get_settings_defaults();
        $clean = array();

        $text_fields = array( 'calc_title', 'btn_step1', 'btn_step2', 'btn_step3', 'disclaimer_text', 'success_text', 'admin_email', 'email_subject', 'email_footer_text' );
        foreach ( $text_fields as $field ) {
            $clean[ $field ] = isset( $data[ $field ] ) ? sanitize_text_field( $data[ $field ] ) : $defaults[ $field ];
        }

        // URL fields
        $clean['email_logo_url'] = isset( $data['email_logo_url'] ) ? esc_url_raw( $data['email_logo_url'] ) : $defaults['email_logo_url'];

        // Toggle fields (checkboxes stored as '1' or '0')
        $toggle_fields = array( 'email_customer_enabled', 'email_status_enabled' );
        foreach ( $toggle_fields as $field ) {
            $clean[ $field ] = ! empty( $data[ $field ] ) ? '1' : '0';
        }

        $clean['btw_percentage'] = isset( $data['btw_percentage'] ) ? max( 0, min( 100, floatval( $data['btw_percentage'] ) ) ) : $defaults['btw_percentage'];
        $clean['show_btw']       = in_array( $data['show_btw'] ?? '', array( 'incl', 'excl' ) ) ? $data['show_btw'] : 'incl';

        update_option( 'cmcalc_settings', $clean );
        wp_send_json_success( $clean );
    }

    // ─── Helpers: Styles ───

    public static function get_style_defaults() {
        return array(
            'preset'           => 'default',
            'primary_color'    => '#1B2A4A',
            'secondary_color'  => '#28a745',
            'accent_color'     => '#4DA8DA',
            'text_color'       => '#2d3436',
            'text_light_color' => '#6c757d',
            'bg_color'         => '#ffffff',
            'bg_light_color'   => '#f8f9fa',
            'border_color'     => '#e9ecef',
            'border_radius'    => 16,
            'btn_radius'       => 8,
            'shadow_enabled'   => true,
            'shadow_intensity' => 'medium',
            'font_size_base'   => 15,
            'font_size_title'  => 22,
            'calc_max_width'   => 1100,
            'calc_padding'     => 24,
            'calc_spacing'     => 'normal',
        );
    }

    public static function get_styles() {
        return wp_parse_args( get_option( 'cmcalc_styles', array() ), self::get_style_defaults() );
    }

    public static function get_style_presets() {
        return array(
            'default' => array(
                'name'            => 'Standaard',
                'primary_color'   => '#1B2A4A',
                'secondary_color' => '#28a745',
                'accent_color'    => '#4DA8DA',
                'text_color'      => '#2d3436',
                'text_light_color'=> '#6c757d',
                'bg_color'        => '#ffffff',
                'bg_light_color'  => '#f8f9fa',
                'border_color'    => '#e9ecef',
            ),
            'dark' => array(
                'name'            => 'Donker',
                'primary_color'   => '#0d1117',
                'secondary_color' => '#238636',
                'accent_color'    => '#58a6ff',
                'text_color'      => '#c9d1d9',
                'text_light_color'=> '#8b949e',
                'bg_color'        => '#161b22',
                'bg_light_color'  => '#21262d',
                'border_color'    => '#30363d',
            ),
            'light' => array(
                'name'            => 'Licht & Fris',
                'primary_color'   => '#2563eb',
                'secondary_color' => '#16a34a',
                'accent_color'    => '#0ea5e9',
                'text_color'      => '#1e293b',
                'text_light_color'=> '#64748b',
                'bg_color'        => '#ffffff',
                'bg_light_color'  => '#f1f5f9',
                'border_color'    => '#e2e8f0',
            ),
            'nature' => array(
                'name'            => 'Natuur',
                'primary_color'   => '#064e3b',
                'secondary_color' => '#059669',
                'accent_color'    => '#34d399',
                'text_color'      => '#064e3b',
                'text_light_color'=> '#6b7280',
                'bg_color'        => '#f0fdf4',
                'bg_light_color'  => '#dcfce7',
                'border_color'    => '#bbf7d0',
            ),
            'warm' => array(
                'name'            => 'Warm',
                'primary_color'   => '#7c2d12',
                'secondary_color' => '#ea580c',
                'accent_color'    => '#f97316',
                'text_color'      => '#431407',
                'text_light_color'=> '#9a3412',
                'bg_color'        => '#fff7ed',
                'bg_light_color'  => '#ffedd5',
                'border_color'    => '#fed7aa',
            ),
        );
    }

    // ─── Helpers: Settings ───

    public static function get_settings_defaults() {
        return array(
            'calc_title'             => 'Stel uw pakket samen',
            'btn_step1'              => 'Bekijk overzicht',
            'btn_step2'              => 'Boek nu',
            'btn_step3'              => 'Boeking bevestigen',
            'disclaimer_text'        => 'Aan deze prijsindicatie kunnen geen rechten worden ontleend. Na uw aanvraag nemen wij altijd persoonlijk contact met u op.',
            'success_text'           => 'Wij nemen zo snel mogelijk contact met u op om de afspraak te bevestigen.',
            'admin_email'            => get_option( 'admin_email' ),
            'email_subject'          => 'Nieuwe boeking via calculator',
            'btw_percentage'         => 21,
            'show_btw'               => 'incl',
            'email_customer_enabled' => '1',
            'email_status_enabled'   => '1',
            'email_logo_url'         => '',
            'email_footer_text'      => 'Heeft u vragen? Neem gerust contact met ons op.',
        );
    }

    public static function get_settings() {
        return wp_parse_args( get_option( 'cmcalc_settings', array() ), self::get_settings_defaults() );
    }

    // ─── Helpers: Bedrijven ───

    public static function get_bedrijven( $args = array() ) {
        $defaults = array(
            'post_type'      => 'cm_bedrijf',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        return get_posts( array_merge( $defaults, $args ) );
    }

    public static function get_bedrijven_data() {
        $bedrijven = self::get_bedrijven();
        $result = array();
        foreach ( $bedrijven as $b ) {
            $result[] = array(
                'id'       => $b->ID,
                'name'     => $b->post_title,
                'address'  => get_post_meta( $b->ID, '_cm_bedrijf_address', true ),
                'postcode'   => get_post_meta( $b->ID, '_cm_bedrijf_postcode', true ),
                'huisnummer' => get_post_meta( $b->ID, '_cm_bedrijf_huisnummer', true ),
                'phone'      => get_post_meta( $b->ID, '_cm_bedrijf_phone', true ),
                'email'    => get_post_meta( $b->ID, '_cm_bedrijf_email', true ),
                'lat'      => floatval( get_post_meta( $b->ID, '_cm_bedrijf_lat', true ) ),
                'lon'      => floatval( get_post_meta( $b->ID, '_cm_bedrijf_lon', true ) ),
                'active'   => get_post_meta( $b->ID, '_cm_bedrijf_active', true ) !== '0',
            );
        }
        return $result;
    }

    public static function get_bedrijf_count() {
        return count( self::get_bedrijven() );
    }

    // ─── AJAX: Bedrijven ───

    public static function handle_cmcalc_save_bedrijf() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $bedrijf_id = intval( $_POST['bedrijf_id'] ?? 0 );
        $name       = sanitize_text_field( $_POST['name'] ?? '' );

        if ( ! $name ) wp_send_json_error( 'Bedrijfsnaam is verplicht' );

        if ( $bedrijf_id ) {
            wp_update_post( array( 'ID' => $bedrijf_id, 'post_title' => $name ) );
        } else {
            $bedrijf_id = wp_insert_post( array(
                'post_type'   => 'cm_bedrijf',
                'post_title'  => $name,
                'post_status' => 'publish',
            ) );
            if ( is_wp_error( $bedrijf_id ) ) wp_send_json_error( 'Aanmaken mislukt' );
        }

        update_post_meta( $bedrijf_id, '_cm_bedrijf_address', sanitize_text_field( $_POST['address'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_postcode', sanitize_text_field( $_POST['postcode'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_huisnummer', sanitize_text_field( $_POST['huisnummer'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_phone', sanitize_text_field( $_POST['phone'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_email', sanitize_email( $_POST['email'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_lat', floatval( $_POST['lat'] ?? 0 ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_lon', floatval( $_POST['lon'] ?? 0 ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_active', '1' );

        wp_send_json_success( array( 'id' => $bedrijf_id ) );
    }

    public static function handle_cmcalc_delete_bedrijf() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $bedrijf_id = intval( $_POST['bedrijf_id'] ?? 0 );
        if ( ! $bedrijf_id ) wp_send_json_error( 'Geen ID' );

        // Unlink werkgebieden
        $werkgebieden = get_posts( array(
            'post_type'      => 'cm_werkgebied',
            'posts_per_page' => -1,
            'meta_query'     => array( array( 'key' => '_cmcalc_bedrijf_id', 'value' => $bedrijf_id ) ),
        ) );
        foreach ( $werkgebieden as $wg ) {
            delete_post_meta( $wg->ID, '_cmcalc_bedrijf_id' );
        }

        // Unlink diensten
        $diensten = get_posts( array( 'post_type' => 'dienst', 'posts_per_page' => -1, 'post_status' => 'any' ) );
        foreach ( $diensten as $d ) {
            $ids = json_decode( get_post_meta( $d->ID, '_cm_bedrijf_ids', true ), true );
            if ( is_array( $ids ) && in_array( $bedrijf_id, $ids ) ) {
                $ids = array_values( array_diff( $ids, array( $bedrijf_id ) ) );
                update_post_meta( $d->ID, '_cm_bedrijf_ids', wp_json_encode( $ids ) );
            }
        }

        wp_delete_post( $bedrijf_id, true );
        wp_send_json_success();
    }

    public static function handle_cmcalc_toggle_bedrijf() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $bedrijf_id = intval( $_POST['bedrijf_id'] ?? 0 );
        if ( ! $bedrijf_id ) wp_send_json_error( 'Geen ID' );

        $current = get_post_meta( $bedrijf_id, '_cm_bedrijf_active', true );
        $new_val = $current === '0' ? '1' : '0';
        update_post_meta( $bedrijf_id, '_cm_bedrijf_active', $new_val );

        wp_send_json_success( array( 'active' => $new_val === '1' ) );
    }

    public static function handle_cmcalc_save_dienst_bedrijven() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $post_id     = intval( $_POST['post_id'] ?? 0 );
        $bedrijf_ids = json_decode( stripslashes( $_POST['bedrijf_ids'] ?? '[]' ), true );

        if ( ! $post_id ) wp_send_json_error( 'Geen dienst ID' );
        if ( ! is_array( $bedrijf_ids ) ) $bedrijf_ids = array();

        $bedrijf_ids = array_map( 'intval', $bedrijf_ids );
        update_post_meta( $post_id, '_cm_bedrijf_ids', wp_json_encode( $bedrijf_ids ) );

        wp_send_json_success();
    }

    // ─── AJAX: Setup Wizard ───

    public static function handle_cmcalc_wizard_complete() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $data = json_decode( stripslashes( $_POST['wizard_data'] ?? '{}' ), true );
        if ( empty( $data ) ) wp_send_json_error( 'Geen data' );

        // 1. Create bedrijf
        $bedrijf_id = wp_insert_post( array(
            'post_type'   => 'cm_bedrijf',
            'post_title'  => sanitize_text_field( $data['bedrijf']['name'] ?? 'Mijn Bedrijf' ),
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $bedrijf_id ) ) wp_send_json_error( 'Bedrijf aanmaken mislukt' );

        $b = $data['bedrijf'];
        update_post_meta( $bedrijf_id, '_cm_bedrijf_address', sanitize_text_field( $b['address'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_postcode', sanitize_text_field( $b['postcode'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_huisnummer', sanitize_text_field( $b['huisnummer'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_phone', sanitize_text_field( $b['phone'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_email', sanitize_email( $b['email'] ?? '' ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_lat', floatval( $b['lat'] ?? 0 ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_lon', floatval( $b['lon'] ?? 0 ) );
        update_post_meta( $bedrijf_id, '_cm_bedrijf_active', '1' );

        // 2. Create werkgebieden
        if ( ! empty( $data['werkgebieden'] ) && is_array( $data['werkgebieden'] ) ) {
            foreach ( $data['werkgebieden'] as $wg ) {
                $wg_id = wp_insert_post( array(
                    'post_type'   => 'cm_werkgebied',
                    'post_title'  => sanitize_text_field( $wg['name'] ?? '' ),
                    'post_status' => 'publish',
                ) );
                if ( ! is_wp_error( $wg_id ) ) {
                    update_post_meta( $wg_id, '_cmcalc_postcode', sanitize_text_field( $wg['postcode'] ?? '' ) );
                    update_post_meta( $wg_id, '_cmcalc_lat', floatval( $wg['lat'] ?? 0 ) );
                    update_post_meta( $wg_id, '_cmcalc_lon', floatval( $wg['lon'] ?? 0 ) );
                    update_post_meta( $wg_id, '_cmcalc_free_km', intval( $wg['free_km'] ?? 20 ) );
                    update_post_meta( $wg_id, '_cmcalc_active', '1' );
                    update_post_meta( $wg_id, '_cmcalc_bedrijf_id', $bedrijf_id );
                }
            }
        }

        // 3. Seed and link diensten
        if ( ! empty( $data['diensten'] ) && is_array( $data['diensten'] ) ) {
            // Ensure default diensten exist
            CMCalc_Seeder::seed_diensten( $bedrijf_id );

            // Update prices and bedrijf linkage for selected diensten
            foreach ( $data['diensten'] as $d ) {
                $post_id = intval( $d['id'] ?? 0 );
                if ( ! $post_id ) continue;

                // Update price if provided
                if ( isset( $d['base_price'] ) ) {
                    update_post_meta( $post_id, '_cm_base_price', floatval( $d['base_price'] ) );
                }

                // Ensure bedrijf is linked
                $ids = json_decode( get_post_meta( $post_id, '_cm_bedrijf_ids', true ), true );
                if ( ! is_array( $ids ) ) $ids = array();
                if ( ! in_array( $bedrijf_id, $ids ) ) {
                    $ids[] = $bedrijf_id;
                    update_post_meta( $post_id, '_cm_bedrijf_ids', wp_json_encode( $ids ) );
                }
            }

            // Unlink diensten NOT in the selected list
            $selected_ids = array_map( function($d) { return intval($d['id'] ?? 0); }, $data['diensten'] );
            $all_diensten = get_posts( array( 'post_type' => 'dienst', 'posts_per_page' => -1, 'post_status' => 'any' ) );
            foreach ( $all_diensten as $dienst ) {
                if ( in_array( $dienst->ID, $selected_ids ) ) continue;
                $ids = json_decode( get_post_meta( $dienst->ID, '_cm_bedrijf_ids', true ), true );
                if ( is_array( $ids ) && in_array( $bedrijf_id, $ids ) ) {
                    $ids = array_values( array_diff( $ids, array( $bedrijf_id ) ) );
                    update_post_meta( $dienst->ID, '_cm_bedrijf_ids', wp_json_encode( $ids ) );
                }
            }
        }

        // 4. Save style preset
        if ( ! empty( $data['preset'] ) ) {
            $presets = self::get_style_presets();
            if ( isset( $presets[ $data['preset'] ] ) ) {
                $styles = $presets[ $data['preset'] ];
                $styles['preset'] = $data['preset'];
                update_option( 'cmcalc_styles', $styles );
            }
        }

        wp_send_json_success( array( 'bedrijf_id' => $bedrijf_id ) );
    }

    // ─── GitHub Token & Auto-updater ───

    public static function handle_cmcalc_save_github_token() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        $token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
        update_option( 'cmcalc_github_token', $token );

        // Clear update cache so next check uses new token
        delete_transient( 'cmcalc_update_check' );
        delete_site_transient( 'update_plugins' );

        wp_send_json_success();
    }

    public static function handle_cmcalc_check_update() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang' );

        // Clear cache to force fresh check
        delete_transient( 'cmcalc_update_check' );
        delete_site_transient( 'update_plugins' );

        // Fetch remote info directly
        $token = get_option( 'cmcalc_github_token', '' );

        $args = array(
            'timeout'    => 15,
            'user-agent' => 'CleanmasterzzCalculator/' . CMCALC_VERSION,
            'headers'    => array(
                'Accept' => 'application/json',
            ),
        );
        if ( $token ) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $url = 'https://api.github.com/repos/Damjann-dev/Cleanmasterzz/releases';
        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $body = wp_remote_retrieve_body( $response );
            $headers_sent = wp_remote_retrieve_headers( $response );
            wp_send_json_error( array(
                'message' => 'GitHub API gaf status ' . $code,
                'body'    => substr( $body, 0, 500 ),
                'token_len' => strlen( $token ),
                'token_prefix' => substr( $token, 0, 4 ),
            ) );
        }

        $releases = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $releases ) || ! is_array( $releases ) ) {
            wp_send_json_error( 'Geen releases gevonden' );
        }

        // Find highest version
        $best_version = '0.0.0';
        foreach ( $releases as $release ) {
            if ( ! empty( $release['draft'] ) ) continue;
            $v = ltrim( $release['tag_name'], 'vV' );
            if ( version_compare( $v, $best_version, '>' ) ) {
                $best_version = $v;
            }
        }

        $update_available = version_compare( CMCALC_VERSION, $best_version, '<' );

        wp_send_json_success( array(
            'current_version'  => CMCALC_VERSION,
            'remote_version'   => $best_version,
            'update_available' => $update_available,
        ) );
    }
}
