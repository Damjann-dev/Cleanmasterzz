<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_REST_API {

    public static function register_routes() {
        $ns = 'cleanmasterzz/v1';

        register_rest_route( $ns, '/services', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_services' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/calculate-price', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'calculate_price' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/submit-booking', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'submit_booking' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/validate-code', array(
            'methods'  => 'POST',
            'callback' => array( __CLASS__, 'validate_discount_code' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $ns, '/geocode-werkgebied', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'geocode_werkgebied' ),
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ) );
    }

    /**
     * Get all active services, travel service, and werkgebieden
     */
    public static function get_services( $request = null ) {
        $services = get_posts( array(
            'post_type'      => 'dienst',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                array( 'key' => '_cm_active', 'value' => '1' ),
                array( 'key' => '_cm_active', 'compare' => 'NOT EXISTS' ),
            ),
        ) );

        $result = array();
        $travel_service = null;

        $unit_labels = array(
            'm2'     => 'm²',
            'stuk'   => 'stuk(s)',
            'paneel' => 'paneel/panelen',
            'raam'   => 'ra(a)m(en)',
            'vast'   => 'vast bedrag',
        );

        foreach ( $services as $service ) {
            $price_unit = get_post_meta( $service->ID, '_cm_price_unit', true ) ?: 'm2';

            $sub_options_raw = get_post_meta( $service->ID, '_cm_sub_options', true );
            $sub_options = ! empty( $sub_options_raw ) ? json_decode( $sub_options_raw, true ) : array();
            if ( ! is_array( $sub_options ) ) $sub_options = array();

            $service_data = array(
                'id'             => $service->ID,
                'title'          => $service->post_title,
                'base_price'     => floatval( get_post_meta( $service->ID, '_cm_base_price', true ) ),
                'price_unit'     => $price_unit,
                'unit_label'     => $unit_labels[ $price_unit ] ?? $price_unit,
                'minimum_price'  => floatval( get_post_meta( $service->ID, '_cm_minimum_price', true ) ),
                'discount'       => intval( get_post_meta( $service->ID, '_cm_discount_percent', true ) ),
                'requires_quote' => get_post_meta( $service->ID, '_cm_requires_quote', true ) === '1',
                'sub_options'    => $sub_options,
                'volume_tiers'   => json_decode( get_post_meta( $service->ID, '_cm_volume_tiers', true ) ?: '[]', true ),
                'icon'           => get_post_meta( $service->ID, '_cm_icon', true ),
            );

            if ( $price_unit === 'km' ) {
                $travel_service = $service_data;
            } else {
                $result[] = $service_data;
            }
        }

        // Fallback defaults
        if ( empty( $result ) ) {
            $result = CMCalc_Seeder::get_default_services();
        }

        // Bedrijf filtering
        $bedrijf_id = 0;
        $bedrijf_info = null;
        $postcode_param = $request->get_param( 'postcode' );
        $bedrijf_param = $request->get_param( 'bedrijf_id' );

        // Check if any bedrijven exist at all
        $bedrijven_exist = count( get_posts( array( 'post_type' => 'cm_bedrijf', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) > 0;

        if ( $bedrijven_exist ) {
            if ( $postcode_param ) {
                // Geocode the postcode
                $geo_url = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?q=' . urlencode( $postcode_param ) . '&rows=1&fq=type:postcode';
                $geo_response = wp_remote_get( $geo_url, array( 'timeout' => 10 ) );
                if ( ! is_wp_error( $geo_response ) ) {
                    $geo_body = json_decode( wp_remote_retrieve_body( $geo_response ), true );
                    $docs = $geo_body['response']['docs'] ?? array();
                    if ( ! empty( $docs ) ) {
                        $centroid = $docs[0]['centroide_ll'] ?? '';
                        if ( preg_match( '/POINT\(([0-9.]+)\s+([0-9.]+)\)/', $centroid, $m ) ) {
                            $nearest = self::find_nearest_bedrijf( floatval( $m[2] ), floatval( $m[1] ) );
                            if ( $nearest ) {
                                $bedrijf_id = $nearest['bedrijf_id'];
                            }
                        }
                    }
                }
            } elseif ( $bedrijf_param ) {
                $bedrijf_id = intval( $bedrijf_param );
            }

            if ( $bedrijf_id ) {
                // Load bedrijf info
                $b_post = get_post( $bedrijf_id );
                if ( $b_post ) {
                    $bedrijf_info = array(
                        'id'       => $bedrijf_id,
                        'name'     => $b_post->post_title,
                        'postcode' => get_post_meta( $bedrijf_id, '_cm_bedrijf_postcode', true ),
                        'phone'    => get_post_meta( $bedrijf_id, '_cm_bedrijf_phone', true ),
                        'email'    => get_post_meta( $bedrijf_id, '_cm_bedrijf_email', true ),
                    );
                }

                // Filter result to only diensten linked to this bedrijf
                $filtered = array();
                foreach ( $result as $svc ) {
                    $svc_id = $svc['id'];
                    if ( $svc_id > 0 ) {
                        $ids = json_decode( get_post_meta( $svc_id, '_cm_bedrijf_ids', true ), true );
                        if ( is_array( $ids ) && in_array( $bedrijf_id, $ids ) ) {
                            $filtered[] = $svc;
                        }
                    } else {
                        $filtered[] = $svc; // keep fallback services
                    }
                }
                $result = $filtered;

                // Apply per-bedrijf pricing overrides
                foreach ( $result as &$svc ) {
                    if ( $svc['id'] > 0 ) {
                        $pricing_raw = get_post_meta( $svc['id'], '_cm_bedrijf_pricing', true );
                        if ( ! empty( $pricing_raw ) ) {
                            $pricing = json_decode( $pricing_raw, true );
                            if ( is_array( $pricing ) && isset( $pricing[ $bedrijf_id ] ) ) {
                                $override = $pricing[ $bedrijf_id ];
                                if ( isset( $override['base_price'] ) ) {
                                    $svc['base_price'] = floatval( $override['base_price'] );
                                }
                                if ( isset( $override['volume_tiers'] ) && is_array( $override['volume_tiers'] ) ) {
                                    $svc['volume_tiers'] = $override['volume_tiers'];
                                }
                            }
                        }
                    }
                }
                unset( $svc );

                // Filter travel service too
                if ( $travel_service && $travel_service['id'] > 0 ) {
                    $t_ids = json_decode( get_post_meta( $travel_service['id'], '_cm_bedrijf_ids', true ), true );
                    if ( is_array( $t_ids ) && ! in_array( $bedrijf_id, $t_ids ) ) {
                        $travel_service = null;
                    }
                }
            }
        }

        // Get active werkgebieden
        $werkgebieden = self::get_active_werkgebieden( $bedrijf_id );

        return rest_ensure_response( array(
            'services'       => $result,
            'travel_service' => $travel_service,
            'werkgebieden'   => $werkgebieden,
            'bedrijf'        => $bedrijf_info,
        ) );
    }

    /**
     * Get active werkgebieden
     */
    private static function get_active_werkgebieden( $bedrijf_id = 0 ) {
        $args = array(
            'post_type'      => 'cm_werkgebied',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array( 'key' => '_cmcalc_active', 'value' => '1' ),
            ),
        );

        if ( $bedrijf_id > 0 ) {
            $args['meta_query'][] = array( 'key' => '_cmcalc_bedrijf_id', 'value' => $bedrijf_id );
        }

        $posts = get_posts( $args );

        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id'      => $post->ID,
                'name'    => $post->post_title,
                'lat'     => floatval( get_post_meta( $post->ID, '_cmcalc_lat', true ) ),
                'lon'     => floatval( get_post_meta( $post->ID, '_cmcalc_lon', true ) ),
                'free_km' => intval( get_post_meta( $post->ID, '_cmcalc_free_km', true ) ),
            );
        }

        // Fallback if no werkgebieden exist
        if ( empty( $result ) ) {
            $result[] = array(
                'id'      => 0,
                'name'    => 'Breda',
                'lat'     => 51.5719,
                'lon'     => 4.7683,
                'free_km' => 20,
            );
        }

        return $result;
    }

    /**
     * Calculate price for a single service
     */
    public static function calculate_price( $request ) {
        $service_id = intval( $request->get_param( 'service_id' ) );
        $quantity   = floatval( $request->get_param( 'quantity' ) );

        if ( ! $service_id || ! $quantity ) {
            $service_key = sanitize_text_field( $request->get_param( 'service_key' ) );
            if ( $service_key ) {
                $defaults = CMCalc_Seeder::get_default_services();
                foreach ( $defaults as $s ) {
                    if ( sanitize_title( $s['title'] ) === $service_key ) {
                        return self::do_calculation( $s, $quantity );
                    }
                }
            }
            return new WP_Error( 'invalid_data', 'Ongeldige gegevens', array( 'status' => 400 ) );
        }

        $service = array(
            'base_price'     => floatval( get_post_meta( $service_id, '_cm_base_price', true ) ),
            'price_unit'     => get_post_meta( $service_id, '_cm_price_unit', true ) ?: 'm2',
            'minimum_price'  => floatval( get_post_meta( $service_id, '_cm_minimum_price', true ) ),
            'discount'       => intval( get_post_meta( $service_id, '_cm_discount_percent', true ) ),
            'requires_quote' => get_post_meta( $service_id, '_cm_requires_quote', true ) === '1',
            'title'          => get_the_title( $service_id ),
        );

        return self::do_calculation( $service, $quantity );
    }

    private static function do_calculation( $service, $quantity ) {
        if ( ! empty( $service['requires_quote'] ) ) {
            return rest_ensure_response( array(
                'requires_quote' => true,
                'message'        => 'Voor deze dienst maken wij graag een offerte op maat.',
            ) );
        }

        // Gestapelde staffelprijzen (cumulatief, als belastingschijven)
        $volume_tiers = array();
        $tiers_raw = get_post_meta( $service['id'] ?? 0, '_cm_volume_tiers', true );
        if ( ! empty( $tiers_raw ) ) {
            $volume_tiers = json_decode( $tiers_raw, true );
            if ( ! is_array( $volume_tiers ) ) $volume_tiers = array();
        }

        $subtotal = 0;
        if ( ! empty( $volume_tiers ) ) {
            $remaining = $quantity;
            foreach ( $volume_tiers as $tier ) {
                if ( $remaining <= 0 ) break;
                $tier_min  = floatval( $tier['min'] );
                $tier_max  = floatval( $tier['max'] );
                $tier_price = floatval( $tier['price'] );
                $tier_size = $tier_max - $tier_min + 1;
                $in_tier   = min( $remaining, $tier_size );
                $subtotal += $in_tier * $tier_price;
                $remaining -= $in_tier;
            }
            if ( $remaining > 0 ) {
                $last_price = floatval( $volume_tiers[ count( $volume_tiers ) - 1 ]['price'] );
                $subtotal += $remaining * $last_price;
            }
        } else {
            $subtotal = $service['base_price'] * $quantity;
        }

        if ( $service['minimum_price'] > 0 && $subtotal < $service['minimum_price'] ) {
            $subtotal = $service['minimum_price'];
        }

        $discount_amount = 0;
        if ( $service['discount'] > 0 ) {
            $discount_amount = $subtotal * ( $service['discount'] / 100 );
        }

        $total = $subtotal - $discount_amount;

        $unit_labels = array(
            'm2' => 'm²', 'stuk' => 'stuk(s)', 'paneel' => 'paneel/panelen',
            'raam' => 'ra(a)m(en)', 'vast' => 'vast bedrag',
        );

        return rest_ensure_response( array(
            'requires_quote'  => false,
            'service'         => $service['title'] ?? '',
            'quantity'        => $quantity,
            'unit'            => $unit_labels[ $service['price_unit'] ] ?? $service['price_unit'],
            'base_price'      => round( $service['base_price'], 2 ),
            'subtotal'        => round( $subtotal, 2 ),
            'discount'        => $service['discount'],
            'discount_amount' => round( $discount_amount, 2 ),
            'total'           => round( $total, 2 ),
        ) );
    }

    /**
     * Eenvoudige IP-gebaseerde rate limiting via WordPress transients.
     */
    private static function check_rate_limit( $action, $max_per_hour ) {
        $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
        $key = 'cmcalc_rl_' . $action . '_' . md5( $ip );
        $hits = intval( get_transient( $key ) );

        if ( $hits >= $max_per_hour ) {
            return false;
        }

        if ( $hits === 0 ) {
            set_transient( $key, 1, HOUR_IN_SECONDS );
        } else {
            set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
        }

        return true;
    }

    /**
     * Herbereken de prijs server-side voor een dienst (veiligheidsvalidatie).
     * Gebruikt dezelfde staffellogica als do_calculation().
     */
    private static function server_recalculate_line_total( $service_id, $quantity ) {
        if ( ! $service_id || $quantity <= 0 ) {
            return null;
        }

        $price_unit     = get_post_meta( $service_id, '_cm_price_unit', true ) ?: 'm2';
        $base_price     = floatval( get_post_meta( $service_id, '_cm_base_price', true ) );
        $minimum_price  = floatval( get_post_meta( $service_id, '_cm_minimum_price', true ) );
        $discount_pct   = intval( get_post_meta( $service_id, '_cm_discount_percent', true ) );
        $requires_quote = get_post_meta( $service_id, '_cm_requires_quote', true ) === '1';

        if ( $requires_quote ) {
            return 0;
        }

        $volume_tiers = json_decode( get_post_meta( $service_id, '_cm_volume_tiers', true ) ?: '[]', true );
        if ( ! is_array( $volume_tiers ) ) {
            $volume_tiers = array();
        }

        $subtotal = 0;
        if ( ! empty( $volume_tiers ) ) {
            $remaining = $quantity;
            foreach ( $volume_tiers as $tier ) {
                if ( $remaining <= 0 ) break;
                $tier_min   = floatval( $tier['min'] );
                $tier_max   = floatval( $tier['max'] );
                $tier_price = floatval( $tier['price'] );
                $tier_size  = $tier_max - $tier_min + 1;
                $in_tier    = min( $remaining, $tier_size );
                $subtotal  += $in_tier * $tier_price;
                $remaining -= $in_tier;
            }
            if ( $remaining > 0 ) {
                $last_price = floatval( $volume_tiers[ count( $volume_tiers ) - 1 ]['price'] );
                $subtotal  += $remaining * $last_price;
            }
        } else {
            $subtotal = $base_price * $quantity;
        }

        if ( $minimum_price > 0 && $subtotal < $minimum_price ) {
            $subtotal = $minimum_price;
        }

        if ( $discount_pct > 0 ) {
            $subtotal -= $subtotal * ( $discount_pct / 100 );
        }

        return round( $subtotal, 2 );
    }

    /**
     * Submit a booking
     */
    public static function submit_booking( $request ) {
        // Rate limiting: max 8 boekingen per uur per IP
        if ( ! self::check_rate_limit( 'booking', 8 ) ) {
            return new WP_Error( 'rate_limit', 'Te veel verzoeken. Probeer het later opnieuw.', array( 'status' => 429 ) );
        }

        $name               = sanitize_text_field( $request->get_param( 'name' ) );
        $email              = sanitize_email( $request->get_param( 'email' ) );
        $phone              = sanitize_text_field( $request->get_param( 'phone' ) );
        $address            = sanitize_text_field( $request->get_param( 'address' ) );
        $date               = sanitize_text_field( $request->get_param( 'preferred_date' ) );
        $message            = sanitize_textarea_field( $request->get_param( 'message' ) );
        $services           = $request->get_param( 'services' );
        $postcode           = sanitize_text_field( $request->get_param( 'postcode' ) );
        $house_number       = sanitize_text_field( $request->get_param( 'house_number' ) );
        $distance_km        = floatval( $request->get_param( 'distance_km' ) );
        $travel_surcharge   = floatval( $request->get_param( 'travel_surcharge' ) );
        $nearest_werkgebied = sanitize_text_field( $request->get_param( 'nearest_werkgebied' ) );
        $service_legacy     = sanitize_text_field( $request->get_param( 'service' ) );

        if ( ! $name || ! $email ) {
            return new WP_Error( 'missing_fields', 'Naam en e-mailadres zijn verplicht', array( 'status' => 400 ) );
        }

        // ── Server-side prijsberekening (beveiligingsfix) ──────────────────────
        // De client-submitted totaalprijs wordt NIET vertrouwd.
        // We herberekenen elke regelprijs op basis van de service-ID + hoeveelheid.
        $services_summary    = '';
        $services_data       = array();
        $server_services_total = 0;
        $has_quote_service   = false;

        if ( ! empty( $services ) && is_array( $services ) ) {
            foreach ( $services as $s ) {
                $service_id      = intval( $s['id'] ?? 0 );
                $quantity        = floatval( $s['quantity'] ?? 0 );
                $requires_quote  = ! empty( $s['requires_quote'] );

                // Herbereken line_total server-side als service-ID beschikbaar
                if ( $service_id > 0 && ! $requires_quote ) {
                    $verified_line = self::server_recalculate_line_total( $service_id, $quantity );
                    $line_total    = ( $verified_line !== null ) ? $verified_line : floatval( $s['line_total'] ?? 0 );
                    $server_services_total += $line_total;
                } else {
                    $line_total = 0;
                    if ( $requires_quote ) $has_quote_service = true;
                }

                $services_data[] = array(
                    'id'             => $service_id,
                    'title'          => sanitize_text_field( $s['title'] ?? '' ),
                    'quantity'       => $quantity,
                    'unit'           => sanitize_text_field( $s['unit'] ?? '' ),
                    'requires_quote' => $requires_quote,
                    'line_total'     => $line_total,
                    'sub_options'    => isset( $s['sub_options'] ) ? array_map( 'sanitize_text_field', (array) $s['sub_options'] ) : array(),
                );
                $services_summary .= sanitize_text_field( $s['title'] ?? '' ) . ', ';
            }
            $services_summary = rtrim( $services_summary, ', ' );
        } else {
            $services_summary = $service_legacy;
        }

        // Totaal = server-berekende diensten + geverifieerde voorrijkosten
        // Voorrijkosten valideren: max 200 km × hoogste km-prijs (bescherming)
        $max_travel = 200 * 5.0; // sanity cap
        $travel_surcharge = min( abs( $travel_surcharge ), $max_travel );
        $total = round( $server_services_total + $travel_surcharge, 2 );

        // Kortingscode toepassen op server-berekend totaal
        $discount_code = strtoupper( sanitize_text_field( $request->get_param( 'discount_code' ) ) );
        $discount_amount = 0;
        if ( $discount_code ) {
            $codes = get_option( 'cmcalc_discount_codes', array() );
            foreach ( $codes as $c ) {
                if ( $c['code'] !== $discount_code ) continue;
                if ( ! $c['active'] ) break;
                if ( $c['expires'] && strtotime( $c['expires'] ) < time() ) break;
                if ( $c['max_uses'] > 0 && $c['used'] >= $c['max_uses'] ) break;
                if ( $c['type'] === 'percentage' ) {
                    $discount_amount = $total * ( floatval( $c['value'] ) / 100 );
                } else {
                    $discount_amount = min( floatval( $c['value'] ), $total );
                }
                break;
            }
            $total = round( max( 0, $total - $discount_amount ), 2 );
        }

        // Create booking post
        $booking_id = wp_insert_post( array(
            'post_type'   => 'boeking',
            'post_title'  => sprintf( '%s - %s - %s', $name, $services_summary, date( 'd-m-Y H:i' ) ),
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $booking_id ) ) {
            return new WP_Error( 'booking_failed', 'Boeking kon niet worden opgeslagen', array( 'status' => 500 ) );
        }

        // Save meta (totaal is server-berekend)
        update_post_meta( $booking_id, '_cm_booking_name', $name );
        update_post_meta( $booking_id, '_cm_booking_email', $email );
        update_post_meta( $booking_id, '_cm_booking_phone', $phone );
        update_post_meta( $booking_id, '_cm_booking_address', $address );
        update_post_meta( $booking_id, '_cm_booking_services', wp_json_encode( $services_data ) );
        update_post_meta( $booking_id, '_cm_booking_service', $services_summary );
        update_post_meta( $booking_id, '_cm_booking_total', $total );
        update_post_meta( $booking_id, '_cm_booking_date', $date );
        update_post_meta( $booking_id, '_cm_booking_message', $message );
        if ( $postcode ) {
            update_post_meta( $booking_id, '_cm_booking_postcode', $postcode );
            update_post_meta( $booking_id, '_cm_booking_house_number', $house_number );
            update_post_meta( $booking_id, '_cm_booking_distance_km', $distance_km );
            update_post_meta( $booking_id, '_cm_booking_travel_surcharge', $travel_surcharge );
            update_post_meta( $booking_id, '_cm_booking_nearest_werkgebied', $nearest_werkgebied );
        }

        $bedrijf_id_param = intval( $request->get_param( 'bedrijf_id' ) );
        if ( $bedrijf_id_param ) {
            update_post_meta( $booking_id, '_cm_booking_bedrijf_id', $bedrijf_id_param );
        }

        // Set default status
        update_post_meta( $booking_id, '_cm_booking_status', 'nieuw' );

        // Kortingscode gebruik verhogen
        if ( $discount_code ) {
            $codes = get_option( 'cmcalc_discount_codes', array() );
            foreach ( $codes as &$c ) {
                if ( $c['code'] === $discount_code ) {
                    $c['used'] = ( $c['used'] ?? 0 ) + 1;
                    break;
                }
            }
            update_option( 'cmcalc_discount_codes', $codes );
            update_post_meta( $booking_id, '_cm_discount_code', $discount_code );
        }

        // Genereer uniek portaaltoken voor klantportaal
        CMCalc_Portal::generate_token( $booking_id );

        // Send HTML emails
        CMCalc_Email::send_admin_notification( $booking_id );
        CMCalc_Email::send_customer_confirmation( $booking_id );

        return rest_ensure_response( array(
            'success'    => true,
            'booking_id' => $booking_id,
            'message'    => 'Uw boeking is succesvol ontvangen! Wij nemen zo snel mogelijk contact met u op.',
        ) );
    }

    /**
     * Build email body from a saved booking
     */
    public static function build_booking_email_body( $booking_id ) {
        $name               = get_post_meta( $booking_id, '_cm_booking_name', true );
        $email              = get_post_meta( $booking_id, '_cm_booking_email', true );
        $phone              = get_post_meta( $booking_id, '_cm_booking_phone', true );
        $address            = get_post_meta( $booking_id, '_cm_booking_address', true );
        $total              = floatval( get_post_meta( $booking_id, '_cm_booking_total', true ) );
        $date               = get_post_meta( $booking_id, '_cm_booking_date', true );
        $message            = get_post_meta( $booking_id, '_cm_booking_message', true );
        $services_summary   = get_post_meta( $booking_id, '_cm_booking_service', true );
        $services_json      = get_post_meta( $booking_id, '_cm_booking_services', true );
        $postcode           = get_post_meta( $booking_id, '_cm_booking_postcode', true );
        $house_number       = get_post_meta( $booking_id, '_cm_booking_house_number', true );
        $distance_km        = floatval( get_post_meta( $booking_id, '_cm_booking_distance_km', true ) );
        $travel_surcharge   = floatval( get_post_meta( $booking_id, '_cm_booking_travel_surcharge', true ) );
        $nearest_werkgebied = get_post_meta( $booking_id, '_cm_booking_nearest_werkgebied', true );

        $services_data = ! empty( $services_json ) ? json_decode( $services_json, true ) : array();

        $settings      = CMCalc_Admin::get_settings();
        $subject       = ! empty( $settings['email_subject'] )
            ? $settings['email_subject'] . ': ' . $services_summary . ' - ' . $name
            : sprintf( 'Nieuwe boeking: %s - %s', $services_summary, $name );

        $services_text = '';
        if ( ! empty( $services_data ) && is_array( $services_data ) ) {
            foreach ( $services_data as $sd ) {
                if ( ! empty( $sd['requires_quote'] ) ) {
                    $services_text .= sprintf( "- %s (offerte op maat)\n", $sd['title'] );
                } else {
                    $services_text .= sprintf( "- %s: %s %s = €%s\n", $sd['title'], $sd['quantity'], $sd['unit'], number_format( $sd['line_total'], 2, ',', '.' ) );
                }
                if ( ! empty( $sd['sub_options'] ) ) {
                    foreach ( $sd['sub_options'] as $so ) {
                        $services_text .= sprintf( "  → %s\n", $so );
                    }
                }
            }
        } else {
            $services_text = $services_summary;
        }

        $travel_text = '';
        if ( $postcode && $distance_km > 0 ) {
            $area_name = $nearest_werkgebied ?: 'Breda';
            $travel_text = sprintf( "\nLocatie: %s %s (%.0f km vanaf %s)", $postcode, $house_number, $distance_km, $area_name );
            if ( $travel_surcharge > 0 ) {
                $travel_text .= sprintf( "\nVoorrijkosten: €%s", number_format( $travel_surcharge, 2, ',', '.' ) );
            } else {
                $travel_text .= "\nVoorrijkosten: Geen (binnen gratis bereik)";
            }
        }

        $body = sprintf(
            "Nieuwe boeking ontvangen:\n\nNaam: %s\nEmail: %s\nTelefoon: %s\nAdres: %s%s\n\nDiensten:\n%s\nTotaal: €%s\nVoorkeursdatum: %s\nBericht: %s",
            $name, $email, $phone, $address, $travel_text, $services_text, number_format( $total, 2, ',', '.' ), $date, $message
        );

        return array( 'subject' => $subject, 'body' => $body );
    }

    /**
     * Haversine distance in km
     */
    private static function haversine_distance( $lat1, $lon1, $lat2, $lon2 ) {
        $R = 6371;
        $dLat = deg2rad( $lat2 - $lat1 );
        $dLon = deg2rad( $lon2 - $lon1 );
        $a = sin( $dLat / 2 ) * sin( $dLat / 2 ) +
             cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
             sin( $dLon / 2 ) * sin( $dLon / 2 );
        return $R * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
    }

    /**
     * Find nearest bedrijf based on lat/lon
     */
    private static function find_nearest_bedrijf( $lat, $lon ) {
        $werkgebieden = get_posts( array(
            'post_type'      => 'cm_werkgebied',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'OR',
                array( 'key' => '_cmcalc_active', 'value' => '1' ),
                array( 'key' => '_cmcalc_active', 'compare' => 'NOT EXISTS' ),
            ),
        ) );

        $nearest = null;
        $min_distance = PHP_FLOAT_MAX;

        foreach ( $werkgebieden as $wg ) {
            $wg_lat = floatval( get_post_meta( $wg->ID, '_cmcalc_lat', true ) );
            $wg_lon = floatval( get_post_meta( $wg->ID, '_cmcalc_lon', true ) );
            if ( ! $wg_lat || ! $wg_lon ) continue;

            $dist = self::haversine_distance( $lat, $lon, $wg_lat, $wg_lon );
            if ( $dist < $min_distance ) {
                $min_distance = $dist;
                $nearest = array(
                    'werkgebied_id' => $wg->ID,
                    'werkgebied'    => $wg->post_title,
                    'bedrijf_id'    => intval( get_post_meta( $wg->ID, '_cmcalc_bedrijf_id', true ) ),
                    'distance'      => round( $dist, 1 ),
                    'free_km'       => intval( get_post_meta( $wg->ID, '_cmcalc_free_km', true ) ),
                );
            }
        }

        return $nearest;
    }

    /**
     * Validate a discount code (public endpoint)
     */
    public static function validate_discount_code( $request ) {
        // Rate limiting: max 30 validaties per uur per IP (beschermt tegen brute-force)
        if ( ! self::check_rate_limit( 'discount', 30 ) ) {
            return new WP_REST_Response( array( 'valid' => false, 'message' => 'Te veel pogingen. Probeer later opnieuw.' ), 429 );
        }

        $code = strtoupper( sanitize_text_field( $request->get_param( 'code' ) ) );
        $subtotal = floatval( $request->get_param( 'subtotal' ) );

        $codes = get_option( 'cmcalc_discount_codes', array() );

        foreach ( $codes as &$c ) {
            if ( $c['code'] !== $code ) continue;
            if ( ! $c['active'] ) return new WP_REST_Response( array( 'valid' => false, 'message' => 'Code is niet meer actief' ), 200 );
            if ( $c['expires'] && strtotime( $c['expires'] ) < time() ) return new WP_REST_Response( array( 'valid' => false, 'message' => 'Code is verlopen' ), 200 );
            if ( $c['max_uses'] > 0 && $c['used'] >= $c['max_uses'] ) return new WP_REST_Response( array( 'valid' => false, 'message' => 'Code is maximaal gebruikt' ), 200 );

            $discount = 0;
            if ( $c['type'] === 'percentage' ) {
                $discount = $subtotal * ( $c['value'] / 100 );
            } else {
                $discount = min( $c['value'], $subtotal );
            }

            return new WP_REST_Response( array(
                'valid'    => true,
                'type'     => $c['type'],
                'value'    => $c['value'],
                'discount' => round( $discount, 2 ),
                'label'    => $c['type'] === 'percentage' ? '-' . $c['value'] . '%' : '-€' . number_format( $c['value'], 2, ',', '.' ),
            ), 200 );
        }

        return new WP_REST_Response( array( 'valid' => false, 'message' => 'Ongeldige code' ), 200 );
    }

    /**
     * Geocode a postcode via PDOK (admin only)
     */
    public static function geocode_werkgebied( $request ) {
        $postcode = sanitize_text_field( $request->get_param( 'postcode' ) );
        if ( ! $postcode ) {
            return new WP_Error( 'missing_postcode', 'Postcode is verplicht', array( 'status' => 400 ) );
        }

        $url = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?q=' . urlencode( $postcode ) . '&rows=1&fq=type:postcode';
        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'geocode_failed', 'Geocoding mislukt', array( 'status' => 500 ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $docs = $body['response']['docs'] ?? array();

        if ( empty( $docs ) ) {
            return new WP_Error( 'not_found', 'Postcode niet gevonden', array( 'status' => 404 ) );
        }

        $doc = $docs[0];
        $centroid = $doc['centroide_ll'] ?? '';
        $city = $doc['woonplaatsnaam'] ?? '';

        // Parse POINT(lon lat)
        $lat = 0;
        $lon = 0;
        if ( preg_match( '/POINT\(([0-9.]+)\s+([0-9.]+)\)/', $centroid, $m ) ) {
            $lon = floatval( $m[1] );
            $lat = floatval( $m[2] );
        }

        return rest_ensure_response( array(
            'postcode' => $postcode,
            'city'     => $city,
            'lat'      => $lat,
            'lon'      => $lon,
        ) );
    }
}
