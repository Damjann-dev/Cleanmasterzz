<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$werkgebieden = CMCalc_Admin::get_werkgebieden();
$bedrijven = CMCalc_Admin::get_bedrijven();
?>

<div class="cmcalc-section">
    <div class="cmcalc-section-header">
        <h2>Werkgebieden beheren</h2>
        <p class="description">Voeg werkgebieden toe via postcode. De calculator berekent voorrijkosten vanaf het dichtstbijzijnde actieve werkgebied.</p>
    </div>

    <?php $alle_bedrijven = CMCalc_Admin::get_bedrijven(); ?>
    <?php if ( count( $alle_bedrijven ) > 0 ) : ?>
    <div style="margin-bottom:16px;">
        <select id="cmcalcWgBedrijfFilter" style="padding:7px 12px;border:1.5px solid #e0e0e0;border-radius:6px;font-size:13px;">
            <option value="">Alle bedrijven</option>
            <?php foreach ( $alle_bedrijven as $b ) : ?>
                <option value="<?php echo esc_attr( $b->ID ); ?>"><?php echo esc_html( $b->post_title ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <div class="cmcalc-werkgebieden-grid" id="cmcalcWerkgebieden">
        <?php foreach ( $werkgebieden as $wg ) :
            $postcode = get_post_meta( $wg->ID, '_cmcalc_postcode', true );
            $lat      = get_post_meta( $wg->ID, '_cmcalc_lat', true );
            $lon      = get_post_meta( $wg->ID, '_cmcalc_lon', true );
            $free_km  = get_post_meta( $wg->ID, '_cmcalc_free_km', true ) ?: 20;
            $active   = get_post_meta( $wg->ID, '_cmcalc_active', true );
        ?>
        <div class="cmcalc-werkgebied-card <?php echo $active !== '1' ? 'cmcalc-inactive' : ''; ?>" data-id="<?php echo esc_attr( $wg->ID ); ?>">
            <div class="cmcalc-wg-header">
                <input type="text" class="cmcalc-wg-name" value="<?php echo esc_attr( $wg->post_title ); ?>" placeholder="Naam">
                <label class="cmcalc-toggle">
                    <input type="checkbox" class="cmcalc-wg-toggle" <?php checked( $active, '1' ); ?>>
                    <span class="cmcalc-toggle-slider"></span>
                </label>
            </div>
            <div class="cmcalc-wg-body">
                <div class="cmcalc-wg-field" style="margin-bottom:12px;">
                    <label style="font-size:11px;font-weight:600;color:var(--cmcalc-text-light);text-transform:uppercase;">Bedrijf</label>
                    <select class="cmcalc-wg-bedrijf" style="width:100%;padding:6px 10px;border:1.5px solid var(--cmcalc-border);border-radius:var(--cmcalc-radius-sm);">
                        <option value="">— Geen bedrijf —</option>
                        <?php foreach ( $bedrijven as $b ) : ?>
                            <option value="<?php echo esc_attr( $b->ID ); ?>" <?php selected( get_post_meta( $wg->ID, '_cmcalc_bedrijf_id', true ), $b->ID ); ?>><?php echo esc_html( $b->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cmcalc-wg-field">
                    <label>Postcode</label>
                    <div class="cmcalc-wg-postcode-row">
                        <input type="text" class="cmcalc-wg-postcode" value="<?php echo esc_attr( $postcode ); ?>" placeholder="1234AB" maxlength="7">
                        <button type="button" class="button cmcalc-wg-geocode" title="Geocode">
                            <span class="dashicons dashicons-location"></span>
                        </button>
                    </div>
                </div>
                <div class="cmcalc-wg-coords">
                    <div class="cmcalc-wg-field">
                        <label>Lat</label>
                        <input type="number" class="cmcalc-wg-lat" value="<?php echo esc_attr( $lat ); ?>" step="0.0001" readonly>
                    </div>
                    <div class="cmcalc-wg-field">
                        <label>Lon</label>
                        <input type="number" class="cmcalc-wg-lon" value="<?php echo esc_attr( $lon ); ?>" step="0.0001" readonly>
                    </div>
                </div>
                <div class="cmcalc-wg-field">
                    <label>Gratis km</label>
                    <input type="number" class="cmcalc-wg-freekm" value="<?php echo esc_attr( $free_km ); ?>" min="0">
                </div>
            </div>
            <div class="cmcalc-wg-footer">
                <button type="button" class="button button-primary cmcalc-wg-save">Opslaan</button>
                <button type="button" class="button cmcalc-wg-delete" title="Verwijderen">
                    <span class="dashicons dashicons-trash"></span> Verwijderen
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add new card -->
        <div class="cmcalc-werkgebied-card cmcalc-wg-add" id="cmcalcAddWerkgebied">
            <div class="cmcalc-wg-add-inner">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span>Nieuw werkgebied</span>
            </div>
        </div>
    </div>
</div>
