<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Seeder {

    public static function seed() {
        $bedrijf_id = self::seed_bedrijf();
        self::seed_diensten( $bedrijf_id );
        self::seed_werkgebied( $bedrijf_id );
    }

    public static function seed_bedrijf() {
        $existing = get_posts( array(
            'post_type'      => 'cm_bedrijf',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ) );

        if ( ! empty( $existing ) ) return $existing[0]->ID;

        $post_id = wp_insert_post( array(
            'post_type'   => 'cm_bedrijf',
            'post_title'  => 'Mijn Bedrijf',
            'post_status' => 'publish',
        ) );

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_cm_bedrijf_address', '' );
            update_post_meta( $post_id, '_cm_bedrijf_postcode', '4811AA' );
            update_post_meta( $post_id, '_cm_bedrijf_huisnummer', '' );
            update_post_meta( $post_id, '_cm_bedrijf_phone', '' );
            update_post_meta( $post_id, '_cm_bedrijf_email', get_option( 'admin_email' ) );
            update_post_meta( $post_id, '_cm_bedrijf_lat', 51.5719 );
            update_post_meta( $post_id, '_cm_bedrijf_lon', 4.7683 );
            update_post_meta( $post_id, '_cm_bedrijf_active', '1' );
        }

        return $post_id;
    }

    public static function seed_diensten( $bedrijf_id = 0 ) {
        $diensten = self::get_default_diensten();

        foreach ( $diensten as $dienst ) {
            $existing = get_posts( array(
                'post_type'      => 'dienst',
                'title'          => $dienst['title'],
                'posts_per_page' => 1,
                'post_status'    => 'any',
            ) );

            if ( ! empty( $existing ) ) {
                // Link existing dienst to bedrijf if not yet linked
                if ( $bedrijf_id ) {
                    $ids = json_decode( get_post_meta( $existing[0]->ID, '_cm_bedrijf_ids', true ), true );
                    if ( ! is_array( $ids ) ) $ids = array();
                    if ( ! in_array( $bedrijf_id, $ids ) ) {
                        $ids[] = $bedrijf_id;
                        update_post_meta( $existing[0]->ID, '_cm_bedrijf_ids', wp_json_encode( $ids ) );
                    }
                }
                continue;
            }

            $post_id = wp_insert_post( array(
                'post_type'    => 'dienst',
                'post_title'   => $dienst['title'],
                'post_excerpt' => $dienst['excerpt'],
                'post_status'  => 'publish',
                'menu_order'   => $dienst['order'],
            ) );

            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_cm_base_price', $dienst['base_price'] );
                update_post_meta( $post_id, '_cm_price_unit', $dienst['price_unit'] );
                update_post_meta( $post_id, '_cm_minimum_price', $dienst['minimum_price'] );
                update_post_meta( $post_id, '_cm_discount_percent', $dienst['discount'] );
                update_post_meta( $post_id, '_cm_requires_quote', $dienst['requires_quote'] ? '1' : '0' );
                update_post_meta( $post_id, '_cm_active', '1' );
                if ( ! empty( $dienst['sub_options'] ) ) {
                    update_post_meta( $post_id, '_cm_sub_options', wp_json_encode( $dienst['sub_options'] ) );
                }
                if ( isset( $dienst['free_km'] ) ) {
                    update_post_meta( $post_id, '_cm_free_km', $dienst['free_km'] );
                }
                if ( ! empty( $dienst['icon'] ) ) {
                    update_post_meta( $post_id, '_cm_icon', $dienst['icon'] );
                }
                if ( $bedrijf_id ) {
                    update_post_meta( $post_id, '_cm_bedrijf_ids', wp_json_encode( array( $bedrijf_id ) ) );
                }
            }
        }
    }

    public static function seed_werkgebied( $bedrijf_id = 0 ) {
        $existing = get_posts( array(
            'post_type'      => 'cm_werkgebied',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ) );

        if ( ! empty( $existing ) ) return;

        $post_id = wp_insert_post( array(
            'post_type'   => 'cm_werkgebied',
            'post_title'  => 'Breda',
            'post_status' => 'publish',
        ) );

        if ( ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_cmcalc_postcode', '4811AA' );
            update_post_meta( $post_id, '_cmcalc_lat', 51.5719 );
            update_post_meta( $post_id, '_cmcalc_lon', 4.7683 );
            update_post_meta( $post_id, '_cmcalc_free_km', 20 );
            update_post_meta( $post_id, '_cmcalc_active', '1' );
            if ( $bedrijf_id ) {
                update_post_meta( $post_id, '_cmcalc_bedrijf_id', $bedrijf_id );
            }
        }
    }

    public static function get_default_services() {
        $unit_labels = array(
            'm2' => 'm²', 'stuk' => 'stuk(s)', 'paneel' => 'paneel/panelen',
            'raam' => 'ra(a)m(en)', 'vast' => 'vast bedrag',
        );
        $diensten = self::get_default_diensten();
        $result = array();
        foreach ( $diensten as $d ) {
            if ( $d['price_unit'] === 'km' ) continue;
            $result[] = array(
                'id'             => 0,
                'title'          => $d['title'],
                'base_price'     => $d['base_price'],
                'price_unit'     => $d['price_unit'],
                'unit_label'     => $unit_labels[ $d['price_unit'] ] ?? $d['price_unit'],
                'minimum_price'  => $d['minimum_price'],
                'discount'       => $d['discount'],
                'requires_quote' => $d['requires_quote'],
                'sub_options'    => $d['sub_options'] ?? array(),
            );
        }
        return $result;
    }

    public static function get_default_diensten() {
        return array(
            array(
                'title' => 'Glasbewassing',
                'excerpt' => 'Laat uw pand of woning weer stralen met streeploze ramen.',
                'base_price' => 3.50,
                'price_unit' => 'raam',
                'minimum_price' => 75,
                'discount' => 0,
                'requires_quote' => false,
                'order' => 1,
                'icon' => 'window',
                'sub_options' => array(
                    array(
                        'type' => 'select',
                        'label' => 'Zijde',
                        'options' => array( 'Alleen voorkant', 'Alleen achterkant', 'Gehele woning (voor + achter)' ),
                        'surcharges' => array( 0, 0, 2.00 ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Verdiepingen',
                        'options' => array( 'Alleen begane grond', 'Begane grond + 1e verdieping', 'Begane grond + 1e + 2e verdieping', 'Alle verdiepingen (3+)' ),
                        'surcharges' => array( 0, 1.00, 2.00, 3.50 ),
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Dakkapelramen meepakken',
                        'surcharge' => 5.00,
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Velux ramen meepakken',
                        'surcharge' => 8.00,
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Ladder nodig (bijv. over schuur klimmen)',
                        'surcharge' => 15.00,
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Ik bevestig dat de poort/toegang altijd open is',
                        'surcharge' => 0,
                    ),
                ),
            ),
            array(
                'title' => 'Terrasreiniging',
                'excerpt' => 'Groene aanslag en vuil verdwijnen als sneeuw voor de zon.',
                'base_price' => 8,
                'price_unit' => 'm2',
                'minimum_price' => 150,
                'discount' => 0,
                'requires_quote' => false,
                'order' => 2,
                'icon' => 'terrace',
                'sub_options' => array(
                    array(
                        'type' => 'checkbox',
                        'label' => 'Terras opnieuw invegen na reiniging',
                        'surcharge' => 2.50,
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Beschermlaag / impregneerlaag aanbrengen',
                        'surcharge' => 3.00,
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Meubels verplaatsen',
                        'options' => array( 'Klant doet het zelf', 'Wij verplaatsen de meubels' ),
                        'surcharges' => array( 0, 25.00 ),
                    ),
                ),
            ),
            array( 'title' => 'Gevelreiniging', 'excerpt' => 'Verwijder jarenlange vervuiling van uw gevel.', 'base_price' => 12, 'price_unit' => 'm2', 'minimum_price' => 250, 'discount' => 0, 'requires_quote' => false, 'order' => 3, 'icon' => 'facade' ),
            array( 'title' => 'Hogedrukreiniging', 'excerpt' => 'Hardnekkig vuil verwijderen van opritten, stoepen en bestrating.', 'base_price' => 6, 'price_unit' => 'm2', 'minimum_price' => 125, 'discount' => 0, 'requires_quote' => false, 'order' => 4, 'icon' => 'pressure' ),
            array( 'title' => 'Heetwater HD reiniging', 'excerpt' => 'Extra krachtige reiniging met heetwater hogedruk.', 'base_price' => 9, 'price_unit' => 'm2', 'minimum_price' => 175, 'discount' => 0, 'requires_quote' => false, 'order' => 5 ),
            array( 'title' => 'Zonnepanelen reinigen', 'excerpt' => 'Haal het maximale rendement uit uw installatie.', 'base_price' => 4, 'price_unit' => 'paneel', 'minimum_price' => 500, 'discount' => 0, 'requires_quote' => false, 'order' => 6, 'icon' => 'solar' ),
            array( 'title' => 'Na-bouw reiniging', 'excerpt' => 'Complete schoonmaak na verbouwing of nieuwbouw.', 'base_price' => 0, 'price_unit' => 'm2', 'minimum_price' => 0, 'discount' => 0, 'requires_quote' => true, 'order' => 7, 'icon' => 'construction' ),
            array( 'title' => 'Studentenhuizen', 'excerpt' => 'Professionele reiniging van studentenwoningen.', 'base_price' => 0, 'price_unit' => 'm2', 'minimum_price' => 0, 'discount' => 0, 'requires_quote' => true, 'order' => 8 ),
            array( 'title' => 'VVE panden', 'excerpt' => 'Schoonmaak en onderhoud van VVE complexen.', 'base_price' => 0, 'price_unit' => 'm2', 'minimum_price' => 0, 'discount' => 0, 'requires_quote' => true, 'order' => 9 ),
            array( 'title' => 'Garagevloeren', 'excerpt' => 'Verwijder olie, vet en vuil van uw garagevloer.', 'base_price' => 7, 'price_unit' => 'm2', 'minimum_price' => 150, 'discount' => 0, 'requires_quote' => false, 'order' => 10 ),
            array(
                'title' => 'Dakkapel reiniging',
                'excerpt' => 'Bescherm uw dakkapel tegen algen, mos en houtrot.',
                'base_price' => 85,
                'price_unit' => 'stuk',
                'minimum_price' => 85,
                'discount' => 0,
                'requires_quote' => false,
                'order' => 11,
                'icon' => 'roof',
                'sub_options' => array(
                    array(
                        'type' => 'checkbox',
                        'label' => 'Sterk vervuild (extra behandeling nodig)',
                        'surcharge' => 25.00,
                    ),
                ),
            ),
            array( 'title' => 'Opritten reinigen', 'excerpt' => 'Uw oprit weer als nieuw.', 'base_price' => 6, 'price_unit' => 'm2', 'minimum_price' => 125, 'discount' => 0, 'requires_quote' => false, 'order' => 12 ),
            array( 'title' => 'Voorrijkosten', 'excerpt' => 'Kilometervergoeding voor locaties buiten het gratis bereik.', 'base_price' => 0.50, 'price_unit' => 'km', 'minimum_price' => 0, 'discount' => 0, 'requires_quote' => false, 'order' => 99, 'free_km' => 20, 'icon' => 'car' ),
        );
    }
}
