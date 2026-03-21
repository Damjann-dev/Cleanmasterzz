<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Email {

    /**
     * Send HTML admin notification for a new booking
     */
    public static function send_admin_notification( $booking_id ) {
        $settings   = CMCalc_Admin::get_settings();
        $admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );

        $name    = get_post_meta( $booking_id, '_cm_booking_name', true );
        $service = get_post_meta( $booking_id, '_cm_booking_service', true );
        $subject = ! empty( $settings['email_subject'] )
            ? $settings['email_subject'] . ': ' . $service . ' - ' . $name
            : sprintf( 'Nieuwe boeking: %s - %s', $service, $name );

        $html = self::render_booking_email( $booking_id, 'admin' );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        return wp_mail( $admin_email, $subject, $html, $headers );
    }

    /**
     * Send HTML customer confirmation email
     */
    public static function send_customer_confirmation( $booking_id ) {
        $settings = CMCalc_Admin::get_settings();
        if ( empty( $settings['email_customer_enabled'] ) || $settings['email_customer_enabled'] !== '1' ) {
            return false;
        }

        $email   = get_post_meta( $booking_id, '_cm_booking_email', true );
        if ( ! $email ) return false;

        $subject = 'Bevestiging van uw boeking';
        $bedrijf_naam = self::get_bedrijf_naam( $booking_id );
        if ( $bedrijf_naam ) {
            $subject .= ' bij ' . $bedrijf_naam;
        }

        $html    = self::render_booking_email( $booking_id, 'customer' );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        // Set From header using admin email
        $admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );
        $headers[]   = 'Reply-To: ' . $admin_email;

        return wp_mail( $email, $subject, $html, $headers );
    }

    /**
     * Send status update email to customer
     */
    public static function send_status_update( $booking_id, $old_status, $new_status ) {
        $settings = CMCalc_Admin::get_settings();
        if ( empty( $settings['email_status_enabled'] ) || $settings['email_status_enabled'] !== '1' ) {
            return false;
        }

        $email = get_post_meta( $booking_id, '_cm_booking_email', true );
        if ( ! $email ) return false;

        $name = get_post_meta( $booking_id, '_cm_booking_name', true );
        $subject = 'Status update: uw boeking is ' . self::translate_status( $new_status );

        $html    = self::render_status_email( $booking_id, $old_status, $new_status );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );
        $headers[]   = 'Reply-To: ' . $admin_email;

        return wp_mail( $email, $subject, $html, $headers );
    }

    /**
     * Render the booking email from template
     */
    private static function render_booking_email( $booking_id, $type = 'admin' ) {
        $settings = CMCalc_Admin::get_settings();
        $styles   = CMCalc_Admin::get_styles();

        $name             = get_post_meta( $booking_id, '_cm_booking_name', true );
        $email            = get_post_meta( $booking_id, '_cm_booking_email', true );
        $phone            = get_post_meta( $booking_id, '_cm_booking_phone', true );
        $address          = get_post_meta( $booking_id, '_cm_booking_address', true );
        $total            = floatval( get_post_meta( $booking_id, '_cm_booking_total', true ) );
        $date             = get_post_meta( $booking_id, '_cm_booking_date', true );
        $message          = get_post_meta( $booking_id, '_cm_booking_message', true );
        $services_json    = get_post_meta( $booking_id, '_cm_booking_services', true );
        $postcode         = get_post_meta( $booking_id, '_cm_booking_postcode', true );
        $house_number     = get_post_meta( $booking_id, '_cm_booking_house_number', true );
        $distance_km      = floatval( get_post_meta( $booking_id, '_cm_booking_distance_km', true ) );
        $travel_surcharge = floatval( get_post_meta( $booking_id, '_cm_booking_travel_surcharge', true ) );
        $nearest_wg       = get_post_meta( $booking_id, '_cm_booking_nearest_werkgebied', true );

        $services_data = ! empty( $services_json ) ? json_decode( $services_json, true ) : array();
        $primary_color = $styles['primary_color'] ?? '#1B2A4A';
        $bedrijf_naam  = self::get_bedrijf_naam( $booking_id );

        // Build diensten rows HTML
        $diensten_html = '';
        if ( ! empty( $services_data ) ) {
            foreach ( $services_data as $sd ) {
                $diensten_html .= '<tr style="border-bottom:1px solid #f1f3f5;">';
                $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;">';
                $diensten_html .= esc_html( $sd['title'] ?? '' );
                if ( ! empty( $sd['sub_options'] ) ) {
                    foreach ( $sd['sub_options'] as $so ) {
                        $diensten_html .= '<br><span style="color:#6c757d;font-size:12px;">&bull; ' . esc_html( $so ) . '</span>';
                    }
                }
                $diensten_html .= '</td>';

                if ( ! empty( $sd['requires_quote'] ) ) {
                    $diensten_html .= '<td style="padding:10px 14px;color:#6c757d;font-size:13px;text-align:center;">-</td>';
                    $diensten_html .= '<td style="padding:10px 14px;color:#6c757d;font-size:13px;text-align:right;font-style:italic;">Offerte op maat</td>';
                } else {
                    $qty  = floatval( $sd['quantity'] ?? 0 );
                    $unit = $sd['unit'] ?? '';
                    $line = floatval( $sd['line_total'] ?? 0 );
                    $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:center;">' . $qty . ' ' . esc_html( $unit ) . '</td>';
                    $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:right;font-weight:500;">&euro;' . number_format( $line, 2, ',', '.' ) . '</td>';
                }
                $diensten_html .= '</tr>';
            }
        }

        // Voorrijkosten row
        $voorrijkosten_html = '';
        if ( $travel_surcharge > 0 ) {
            $area_name = $nearest_wg ?: 'werkgebied';
            $voorrijkosten_html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:4px;">';
            $voorrijkosten_html .= '<tr><td style="padding:8px 14px;color:#6c757d;font-size:13px;">Voorrijkosten (' . round( $distance_km ) . ' km vanaf ' . esc_html( $area_name ) . ')</td>';
            $voorrijkosten_html .= '<td style="padding:8px 14px;color:#2d3436;font-size:14px;text-align:right;font-weight:500;">&euro;' . number_format( $travel_surcharge, 2, ',', '.' ) . '</td></tr></table>';
        }

        // Location row
        $locatie_rij = '';
        if ( $postcode ) {
            $locatie_rij = '<tr><td style="padding:4px 0;color:#6c757d;font-size:13px;">Locatie</td>';
            $locatie_rij .= '<td style="padding:4px 0;color:#2d3436;font-size:14px;">' . esc_html( $postcode . ' ' . $house_number );
            if ( $distance_km > 0 ) {
                $locatie_rij .= ' (' . round( $distance_km ) . ' km)';
            }
            $locatie_rij .= '</td></tr>';
        }

        // Bericht blok
        $bericht_html = '';
        if ( $message ) {
            $bericht_html = '<div style="margin-top:24px;padding:16px 20px;background:#fff8e1;border-radius:8px;border-left:4px solid #ffc107;">';
            $bericht_html .= '<p style="margin:0 0 4px;color:#6c757d;font-size:12px;font-weight:600;">Bericht van klant</p>';
            $bericht_html .= '<p style="margin:0;color:#2d3436;font-size:14px;line-height:1.5;">' . nl2br( esc_html( $message ) ) . '</p></div>';
        }

        // BTW text
        $btw_percentage = floatval( $settings['btw_percentage'] ?? 21 );
        $btw_mode = $settings['show_btw'] ?? 'incl';
        if ( $btw_mode === 'incl' ) {
            $btw_amount = $total - ( $total / ( 1 + $btw_percentage / 100 ) );
            $btw_tekst = 'Inclusief &euro;' . number_format( $btw_amount, 2, ',', '.' ) . ' BTW (' . $btw_percentage . '%)';
        } else {
            $btw_tekst = 'Exclusief BTW (' . $btw_percentage . '%)';
        }

        // Logo
        $logo_html = '';
        $logo_url = $settings['email_logo_url'] ?? '';
        if ( $logo_url ) {
            $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-width:160px;max-height:50px;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">';
        }

        // Footer text
        $footer_tekst = $settings['email_footer_text'] ?? 'Heeft u vragen? Neem gerust contact met ons op.';

        // Intro text
        if ( $type === 'customer' ) {
            $header_titel = 'Bedankt voor uw boeking!';
            $intro_tekst  = 'Beste ' . esc_html( $name ) . ',<br><br>Wij hebben uw boeking ontvangen. Hieronder vindt u een overzicht. Wij nemen zo snel mogelijk contact met u op om de afspraak te bevestigen.';
        } else {
            $header_titel = 'Nieuwe boeking ontvangen';
            $intro_tekst  = 'Er is een nieuwe boeking binnengekomen via de prijscalculator.';
        }

        // Load template
        $template = file_get_contents( CMCALC_PLUGIN_DIR . 'templates/email-booking.html' );

        // Replace placeholders
        $replacements = array(
            '{primary_color}'    => $primary_color,
            '{logo_html}'        => $logo_html,
            '{header_titel}'     => $header_titel,
            '{intro_tekst}'      => $intro_tekst,
            '{naam}'             => esc_html( $name ),
            '{email}'            => esc_html( $email ),
            '{telefoon}'         => esc_html( $phone ?: '-' ),
            '{adres}'            => esc_html( $address ?: '-' ),
            '{locatie_rij}'      => $locatie_rij,
            '{datum}'            => esc_html( $date ?: '-' ),
            '{diensten_rijen}'   => $diensten_html,
            '{voorrijkosten_rij}'=> $voorrijkosten_html,
            '{totaal}'           => '&euro;' . number_format( $total, 2, ',', '.' ),
            '{btw_tekst}'        => $btw_tekst,
            '{bericht_blok}'     => $bericht_html,
            '{footer_tekst}'     => esc_html( $footer_tekst ),
            '{bedrijf_naam}'     => esc_html( $bedrijf_naam ?: get_bloginfo( 'name' ) ),
            '{onderwerp}'        => 'Boeking',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /**
     * Render status update email
     */
    private static function render_status_email( $booking_id, $old_status, $new_status ) {
        $settings = CMCalc_Admin::get_settings();
        $styles   = CMCalc_Admin::get_styles();

        $name          = get_post_meta( $booking_id, '_cm_booking_name', true );
        $total         = floatval( get_post_meta( $booking_id, '_cm_booking_total', true ) );
        $date          = get_post_meta( $booking_id, '_cm_booking_date', true );
        $service       = get_post_meta( $booking_id, '_cm_booking_service', true );
        $primary_color = $styles['primary_color'] ?? '#1B2A4A';
        $bedrijf_naam  = self::get_bedrijf_naam( $booking_id );

        // Status color mapping
        $status_colors = array(
            'nieuw'        => '#3b82f6',
            'bevestigd'    => '#8b5cf6',
            'gepland'      => '#f59e0b',
            'voltooid'     => '#10b981',
            'geannuleerd'  => '#ef4444',
        );
        $status_kleur = $status_colors[ $new_status ] ?? '#6c757d';

        // Status messages
        $status_berichten = array(
            'bevestigd'   => 'Uw boeking is bevestigd. Wij nemen binnenkort contact op om een definitieve datum af te spreken.',
            'gepland'     => 'Uw boeking is ingepland. Wij komen op de afgesproken datum langs.',
            'voltooid'    => 'De werkzaamheden zijn voltooid. Bedankt voor uw vertrouwen! Wij hopen u in de toekomst weer van dienst te mogen zijn.',
            'geannuleerd' => 'Uw boeking is geannuleerd. Neem contact met ons op als dit niet klopt of als u een nieuwe afspraak wilt maken.',
            'nieuw'       => 'Uw boeking wordt opnieuw bekeken. Wij nemen zo snel mogelijk contact met u op.',
        );
        $status_bericht = $status_berichten[ $new_status ] ?? '';

        // Logo
        $logo_html = '';
        $logo_url = $settings['email_logo_url'] ?? '';
        if ( $logo_url ) {
            $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-width:160px;max-height:50px;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">';
        }

        $footer_tekst = $settings['email_footer_text'] ?? 'Heeft u vragen? Neem gerust contact met ons op.';

        // Load template
        $template = file_get_contents( CMCALC_PLUGIN_DIR . 'templates/email-status-update.html' );

        $replacements = array(
            '{primary_color}'        => $primary_color,
            '{logo_html}'            => $logo_html,
            '{naam}'                 => esc_html( $name ),
            '{oude_status}'          => esc_html( self::translate_status( $old_status ) ),
            '{nieuwe_status}'        => esc_html( self::translate_status( $new_status ) ),
            '{status_kleur}'         => $status_kleur,
            '{diensten_samenvatting}'=> esc_html( $service ),
            '{datum}'                => esc_html( $date ?: '-' ),
            '{totaal}'               => '&euro;' . number_format( $total, 2, ',', '.' ),
            '{status_bericht}'       => esc_html( $status_bericht ),
            '{footer_tekst}'         => esc_html( $footer_tekst ),
            '{bedrijf_naam}'         => esc_html( $bedrijf_naam ?: get_bloginfo( 'name' ) ),
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /**
     * Translate status to Dutch display label
     */
    private static function translate_status( $status ) {
        $labels = array(
            'nieuw'       => 'Nieuw',
            'bevestigd'   => 'Bevestigd',
            'gepland'     => 'Gepland',
            'voltooid'    => 'Voltooid',
            'geannuleerd' => 'Geannuleerd',
        );
        return $labels[ $status ] ?? ucfirst( $status );
    }

    /**
     * Get bedrijf name for a booking
     */
    private static function get_bedrijf_naam( $booking_id ) {
        $bedrijf_id = get_post_meta( $booking_id, '_cm_booking_bedrijf_id', true );
        if ( $bedrijf_id ) {
            $bedrijf = get_post( intval( $bedrijf_id ) );
            if ( $bedrijf ) return $bedrijf->post_title;
        }
        return '';
    }

    /**
     * Generate a preview of the booking email (for admin preview button)
     */
    public static function get_preview_html() {
        $settings = CMCalc_Admin::get_settings();
        $styles   = CMCalc_Admin::get_styles();
        $primary_color = $styles['primary_color'] ?? '#1B2A4A';

        $logo_html = '';
        $logo_url = $settings['email_logo_url'] ?? '';
        if ( $logo_url ) {
            $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="Logo" style="max-width:160px;max-height:50px;margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">';
        }

        $footer_tekst = $settings['email_footer_text'] ?? 'Heeft u vragen? Neem gerust contact met ons op.';

        $diensten_html  = '<tr style="border-bottom:1px solid #f1f3f5;">';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;">Glasbewassing<br><span style="color:#6c757d;font-size:12px;">&bull; Gehele woning (voor + achter)</span></td>';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:center;">12 ramen</td>';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:right;font-weight:500;">&euro;75,00</td></tr>';
        $diensten_html .= '<tr style="border-bottom:1px solid #f1f3f5;">';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;">Terrasreiniging</td>';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:center;">25 m&sup2;</td>';
        $diensten_html .= '<td style="padding:10px 14px;color:#2d3436;font-size:14px;text-align:right;font-weight:500;">&euro;200,00</td></tr>';

        $template = file_get_contents( CMCALC_PLUGIN_DIR . 'templates/email-booking.html' );

        $replacements = array(
            '{primary_color}'     => $primary_color,
            '{logo_html}'         => $logo_html,
            '{header_titel}'      => 'Bedankt voor uw boeking!',
            '{intro_tekst}'       => 'Beste Jan de Vries,<br><br>Wij hebben uw boeking ontvangen. Hieronder vindt u een overzicht. Wij nemen zo snel mogelijk contact met u op om de afspraak te bevestigen.',
            '{naam}'              => 'Jan de Vries',
            '{email}'             => 'jan@voorbeeld.nl',
            '{telefoon}'          => '06-12345678',
            '{adres}'             => 'Voorbeeldstraat 1, Breda',
            '{locatie_rij}'       => '<tr><td style="padding:4px 0;color:#6c757d;font-size:13px;">Locatie</td><td style="padding:4px 0;color:#2d3436;font-size:14px;">4811AA 12 (5 km)</td></tr>',
            '{datum}'             => '15-04-2026',
            '{diensten_rijen}'    => $diensten_html,
            '{voorrijkosten_rij}' => '',
            '{totaal}'            => '&euro;275,00',
            '{btw_tekst}'         => 'Inclusief &euro;47,69 BTW (21%)',
            '{bericht_blok}'      => '<div style="margin-top:24px;padding:16px 20px;background:#fff8e1;border-radius:8px;border-left:4px solid #ffc107;"><p style="margin:0 0 4px;color:#6c757d;font-size:12px;font-weight:600;">Bericht van klant</p><p style="margin:0;color:#2d3436;font-size:14px;line-height:1.5;">Graag in de ochtend, voor 12:00 uur.</p></div>',
            '{footer_tekst}'      => esc_html( $footer_tekst ),
            '{bedrijf_naam}'      => esc_html( get_bloginfo( 'name' ) ),
            '{onderwerp}'         => 'Preview',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }
}
