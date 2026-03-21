<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$bedrijven = CMCalc_Admin::get_bedrijven();
?>

<div class="cmcalc-section-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div>
        <h3 style="margin:0;">Bedrijven / Vestigingen</h3>
        <p style="color:var(--cmcalc-text-light);margin:4px 0 0;">Beheer uw bedrijven en vestigingen. Elk bedrijf heeft eigen werkgebieden en diensten.</p>
    </div>
    <button type="button" class="button button-primary" id="cmcalcAddBedrijf">
        <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-right:4px;"></span> Nieuw bedrijf
    </button>
</div>

<div class="cmcalc-bedrijven-grid" id="cmcalcBedrijvenGrid">
    <?php foreach ( $bedrijven as $bedrijf ) :
        $active   = get_post_meta( $bedrijf->ID, '_cm_bedrijf_active', true ) !== '0';
        $address  = get_post_meta( $bedrijf->ID, '_cm_bedrijf_address', true );
        $postcode   = get_post_meta( $bedrijf->ID, '_cm_bedrijf_postcode', true );
        $huisnummer = get_post_meta( $bedrijf->ID, '_cm_bedrijf_huisnummer', true );
        $phone    = get_post_meta( $bedrijf->ID, '_cm_bedrijf_phone', true );
        $email    = get_post_meta( $bedrijf->ID, '_cm_bedrijf_email', true );
        $lat      = get_post_meta( $bedrijf->ID, '_cm_bedrijf_lat', true );
        $lon      = get_post_meta( $bedrijf->ID, '_cm_bedrijf_lon', true );
        // Count linked werkgebieden
        $wg_count = count( get_posts( array(
            'post_type' => 'cm_werkgebied',
            'posts_per_page' => -1,
            'meta_query' => array( array( 'key' => '_cmcalc_bedrijf_id', 'value' => $bedrijf->ID ) ),
        ) ) );
        // Count linked diensten
        $diensten = get_posts( array( 'post_type' => 'dienst', 'posts_per_page' => -1, 'post_status' => 'any' ) );
        $d_count = 0;
        foreach ( $diensten as $d ) {
            $ids = json_decode( get_post_meta( $d->ID, '_cm_bedrijf_ids', true ), true );
            if ( is_array( $ids ) && in_array( $bedrijf->ID, $ids ) ) $d_count++;
        }
    ?>
    <div class="cmcalc-bedrijf-card <?php echo $active ? '' : 'inactive'; ?>" data-id="<?php echo esc_attr( $bedrijf->ID ); ?>">
        <div class="cmcalc-bedrijf-header">
            <div class="cmcalc-bedrijf-status">
                <label class="cmcalc-toggle">
                    <input type="checkbox" class="cmcalc-bedrijf-toggle" <?php checked( $active ); ?>>
                    <span class="cmcalc-toggle-slider"></span>
                </label>
                <span class="cmcalc-bedrijf-active-label"><?php echo $active ? 'Actief' : 'Inactief'; ?></span>
            </div>
            <div class="cmcalc-bedrijf-stats">
                <span title="Werkgebieden"><span class="dashicons dashicons-location" style="font-size:14px;vertical-align:middle;"></span> <?php echo $wg_count; ?></span>
                <span title="Diensten"><span class="dashicons dashicons-hammer" style="font-size:14px;vertical-align:middle;"></span> <?php echo $d_count; ?></span>
            </div>
        </div>

        <div class="cmcalc-bedrijf-body">
            <div class="cmcalc-field-group">
                <label>Bedrijfsnaam</label>
                <input type="text" class="cmcalc-bedrijf-name regular-text" value="<?php echo esc_attr( $bedrijf->post_title ); ?>" style="width:100%;">
            </div>

            <div class="cmcalc-field-group">
                <label>Adres</label>
                <input type="text" class="cmcalc-bedrijf-address regular-text" value="<?php echo esc_attr( $address ); ?>" style="width:100%;">
            </div>

            <div class="cmcalc-field-row" style="display:flex;gap:10px;">
                <div class="cmcalc-field-group" style="flex:1;">
                    <label>Postcode</label>
                    <div style="display:flex;gap:6px;">
                        <input type="text" class="cmcalc-bedrijf-postcode" value="<?php echo esc_attr( $postcode ); ?>" maxlength="7" style="width:100px;">
                        <input type="text" class="cmcalc-bedrijf-huisnummer" value="<?php echo esc_attr( $huisnummer ); ?>" placeholder="Nr." style="width:70px;">
                        <button type="button" class="button cmcalc-bedrijf-geocode" title="Geocode postcode">
                            <span class="dashicons dashicons-location-alt" style="vertical-align:middle;"></span>
                        </button>
                    </div>
                </div>
                <div class="cmcalc-field-group" style="flex:1;">
                    <label>Telefoon</label>
                    <input type="tel" class="cmcalc-bedrijf-phone" value="<?php echo esc_attr( $phone ); ?>" style="width:100%;">
                </div>
            </div>

            <div class="cmcalc-field-group">
                <label>Email</label>
                <input type="email" class="cmcalc-bedrijf-email regular-text" value="<?php echo esc_attr( $email ); ?>" style="width:100%;">
            </div>

            <div class="cmcalc-field-row" style="display:flex;gap:10px;">
                <div class="cmcalc-field-group" style="flex:1;">
                    <label>Latitude</label>
                    <input type="text" class="cmcalc-bedrijf-lat" value="<?php echo esc_attr( $lat ); ?>" readonly style="width:100%;background:#f0f0f0;">
                </div>
                <div class="cmcalc-field-group" style="flex:1;">
                    <label>Longitude</label>
                    <input type="text" class="cmcalc-bedrijf-lon" value="<?php echo esc_attr( $lon ); ?>" readonly style="width:100%;background:#f0f0f0;">
                </div>
            </div>
        </div>

        <div class="cmcalc-bedrijf-footer">
            <button type="button" class="button button-primary cmcalc-bedrijf-save">Opslaan</button>
            <button type="button" class="button cmcalc-bedrijf-delete" style="color:#dc3545;border-color:#dc3545;">Verwijderen</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
