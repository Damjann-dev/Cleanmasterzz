<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CMCalc_Meta_Boxes {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_dienst', array( __CLASS__, 'save_dienst_pricing' ) );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'cmcalc_dienst_pricing',
            'Prijsinformatie',
            array( __CLASS__, 'render_pricing_box' ),
            'dienst',
            'side',
            'high'
        );
    }

    public static function render_pricing_box( $post ) {
        wp_nonce_field( 'cmcalc_dienst_pricing', 'cmcalc_pricing_nonce' );

        $base_price   = get_post_meta( $post->ID, '_cm_base_price', true );
        $price_unit   = get_post_meta( $post->ID, '_cm_price_unit', true );
        $min_price    = get_post_meta( $post->ID, '_cm_minimum_price', true );
        $discount     = get_post_meta( $post->ID, '_cm_discount_percent', true );
        $needs_quote  = get_post_meta( $post->ID, '_cm_requires_quote', true );
        $active       = get_post_meta( $post->ID, '_cm_active', true );
        if ( $active === '' ) $active = '1';
        ?>
        <p>
            <label><input type="checkbox" name="cm_active" value="1" <?php checked( $active, '1' ); ?>> <strong>Actief in calculator</strong></label>
        </p>
        <p>
            <label for="cm_base_price"><strong>Basisprijs (&euro;):</strong></label><br>
            <input type="number" id="cm_base_price" name="cm_base_price" value="<?php echo esc_attr( $base_price ); ?>" step="0.01" style="width: 100%;">
        </p>
        <p>
            <label for="cm_price_unit"><strong>Prijs per:</strong></label><br>
            <select id="cm_price_unit" name="cm_price_unit" style="width: 100%;">
                <option value="m2" <?php selected( $price_unit, 'm2' ); ?>>Per m&sup2;</option>
                <option value="stuk" <?php selected( $price_unit, 'stuk' ); ?>>Per stuk</option>
                <option value="paneel" <?php selected( $price_unit, 'paneel' ); ?>>Per paneel</option>
                <option value="raam" <?php selected( $price_unit, 'raam' ); ?>>Per raam</option>
                <option value="vast" <?php selected( $price_unit, 'vast' ); ?>>Vast bedrag</option>
                <option value="km" <?php selected( $price_unit, 'km' ); ?>>Per kilometer</option>
            </select>
        </p>
        <p>
            <label for="cm_minimum_price"><strong>Minimumprijs (&euro;):</strong></label><br>
            <input type="number" id="cm_minimum_price" name="cm_minimum_price" value="<?php echo esc_attr( $min_price ); ?>" step="0.01" style="width: 100%;">
        </p>
        <p>
            <label for="cm_discount_percent"><strong>Korting (%):</strong></label><br>
            <input type="number" id="cm_discount_percent" name="cm_discount_percent" value="<?php echo esc_attr( $discount ); ?>" min="0" max="100" style="width: 100%;">
        </p>
        <p>
            <label>
                <input type="checkbox" name="cm_requires_quote" value="1" <?php checked( $needs_quote, '1' ); ?>>
                <strong>Offerte op aanvraag / maatwerk</strong>
            </label>
        </p>
        <?php
    }

    public static function save_dienst_pricing( $post_id ) {
        if ( ! isset( $_POST['cmcalc_pricing_nonce'] ) || ! wp_verify_nonce( $_POST['cmcalc_pricing_nonce'], 'cmcalc_dienst_pricing' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array(
            'cm_base_price'       => '_cm_base_price',
            'cm_price_unit'       => '_cm_price_unit',
            'cm_minimum_price'    => '_cm_minimum_price',
            'cm_discount_percent' => '_cm_discount_percent',
        );

        foreach ( $fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        update_post_meta( $post_id, '_cm_requires_quote', isset( $_POST['cm_requires_quote'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_cm_active', isset( $_POST['cm_active'] ) ? '1' : '0' );
    }
}
