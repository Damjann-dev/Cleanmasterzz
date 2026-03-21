<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Shortcode {

    public static function register() {
        add_shortcode( 'prijscalculator', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        self::enqueue_assets();

        $styles   = CMCalc_Admin::get_styles();
        $settings = CMCalc_Admin::get_settings();

        ob_start();
        echo self::generate_custom_css( $styles );
        include CMCALC_PLUGIN_DIR . 'public/views/calculator-form.php';
        return ob_get_clean();
    }

    private static function enqueue_assets() {
        wp_enqueue_style(
            'cmcalc-calculator',
            CMCALC_PLUGIN_URL . 'public/css/calculator.css',
            array(),
            CMCALC_VERSION
        );

        wp_enqueue_script(
            'cmcalc-calculator',
            CMCALC_PLUGIN_URL . 'public/js/calculator.js',
            array(),
            CMCALC_VERSION,
            true
        );

        $settings = CMCalc_Admin::get_settings();

        wp_localize_script( 'cmcalc-calculator', 'cmCalc', array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'restUrl'  => rest_url( 'cleanmasterzz/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'texts'    => array(
                'calc_title'      => $settings['calc_title'],
                'btn_step1'       => $settings['btn_step1'],
                'btn_step2'       => $settings['btn_step2'],
                'btn_step3'       => $settings['btn_step3'],
                'disclaimer_text' => $settings['disclaimer_text'],
                'success_text'    => $settings['success_text'],
            ),
            'settings' => array(
                'btw_percentage' => $settings['btw_percentage'],
                'show_btw'       => $settings['show_btw'],
            ),
        ) );
    }

    private static function generate_custom_css( $styles ) {
        // Shadow mapping
        $shadow = 'none';
        if ( ! empty( $styles['shadow_enabled'] ) ) {
            switch ( $styles['shadow_intensity'] ?? 'medium' ) {
                case 'light':  $shadow = '0 1px 8px rgba(0,0,0,0.04)'; break;
                case 'strong': $shadow = '0 8px 32px rgba(0,0,0,0.15)'; break;
                default:       $shadow = '0 4px 24px rgba(0,0,0,0.08)'; break;
            }
        }

        $css = '<style id="cmcalc-custom-styles">' . "\n";
        $css .= '.cmcalc-calculator {' . "\n";
        $css .= '  --cmcalc-primary: ' . esc_attr( $styles['primary_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-secondary: ' . esc_attr( $styles['secondary_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-accent: ' . esc_attr( $styles['accent_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-text: ' . esc_attr( $styles['text_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-text-light: ' . esc_attr( $styles['text_light_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-bg: ' . esc_attr( $styles['bg_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-bg-light: ' . esc_attr( $styles['bg_light_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-border: ' . esc_attr( $styles['border_color'] ) . ';' . "\n";
        $css .= '  --cmcalc-radius: ' . intval( $styles['border_radius'] ) . 'px;' . "\n";
        $css .= '  --cmcalc-btn-radius: ' . intval( $styles['btn_radius'] ?? 8 ) . 'px;' . "\n";
        $css .= '  --cmcalc-shadow: ' . $shadow . ';' . "\n";
        $css .= '  --cmcalc-font-size: ' . intval( $styles['font_size_base'] ) . 'px;' . "\n";
        $css .= '  --cmcalc-title-size: ' . intval( $styles['font_size_title'] ) . 'px;' . "\n";
        $css .= '  --cmcalc-max-width: ' . intval( $styles['calc_max_width'] ?? 1100 ) . 'px;' . "\n";
        $css .= '  --cmcalc-padding: ' . intval( $styles['calc_padding'] ?? 24 ) . 'px;' . "\n";

        // Spacing mode
        $spacing = $styles['calc_spacing'] ?? 'normal';
        $gap_map = array( 'compact' => '8', 'normal' => '16', 'spacious' => '24' );
        $css .= '  --cmcalc-gap: ' . ( $gap_map[ $spacing ] ?? '16' ) . 'px;' . "\n";

        $css .= '}' . "\n";
        $css .= '</style>' . "\n";

        return $css;
    }
}
