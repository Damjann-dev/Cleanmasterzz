<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$diensten = CMCalc_Admin::get_diensten();
$bedrijven = CMCalc_Admin::get_bedrijven();
$unit_options = array(
    'm2'     => 'Per m²',
    'stuk'   => 'Per stuk',
    'paneel' => 'Per paneel',
    'raam'   => 'Per raam',
    'vast'   => 'Vast bedrag',
);
?>

<div class="cmcalc-section">
    <div class="cmcalc-section-header">
        <h2>Diensten beheren</h2>
        <div class="cmcalc-section-actions">
            <button type="button" class="button button-primary" id="cmcalcAddDienst">
                <span class="dashicons dashicons-plus-alt2"></span> Nieuwe dienst
            </button>
        </div>
    </div>

    <table class="cmcalc-table widefat" id="cmcalcDienstenTable">
        <thead>
            <tr>
                <th class="cmcalc-col-drag"></th>
                <th class="cmcalc-col-active">Actief</th>
                <th class="cmcalc-col-title">Titel</th>
                <th class="cmcalc-col-price">Basisprijs</th>
                <th class="cmcalc-col-unit">Eenheid</th>
                <th class="cmcalc-col-min">Min. prijs</th>
                <th class="cmcalc-col-discount">Korting %</th>
                <th class="cmcalc-col-quote">Offerte</th>
                <th class="cmcalc-col-sub">Sub-opties</th>
                <th style="width:140px;">Bedrijven</th>
                <th class="cmcalc-col-actions">Acties</th>
            </tr>
        </thead>
        <tbody id="cmcalcDienstenBody">
            <?php foreach ( $diensten as $dienst ) :
                $price_unit = get_post_meta( $dienst->ID, '_cm_price_unit', true ) ?: 'm2';
                if ( $price_unit === 'km' ) continue; // Skip travel service
                $active       = get_post_meta( $dienst->ID, '_cm_active', true );
                if ( $active === '' ) $active = '1';
                $base_price   = get_post_meta( $dienst->ID, '_cm_base_price', true );
                $min_price    = get_post_meta( $dienst->ID, '_cm_minimum_price', true );
                $discount     = get_post_meta( $dienst->ID, '_cm_discount_percent', true );
                $needs_quote  = get_post_meta( $dienst->ID, '_cm_requires_quote', true );
                $sub_options  = get_post_meta( $dienst->ID, '_cm_sub_options', true );
                $sub_count    = 0;
                if ( $sub_options ) {
                    $decoded = json_decode( $sub_options, true );
                    $sub_count = is_array( $decoded ) ? count( $decoded ) : 0;
                }
                $volume_tiers = get_post_meta( $dienst->ID, '_cm_volume_tiers', true );
                $tier_count   = 0;
                if ( $volume_tiers ) {
                    $tiers_decoded = json_decode( $volume_tiers, true );
                    $tier_count = is_array( $tiers_decoded ) ? count( $tiers_decoded ) : 0;
                }
            ?>
            <tr data-id="<?php echo esc_attr( $dienst->ID ); ?>" class="cmcalc-dienst-row <?php echo $active !== '1' ? 'cmcalc-inactive' : ''; ?>">
                <td class="cmcalc-col-drag"><span class="cmcalc-drag-handle dashicons dashicons-menu"></span></td>
                <td class="cmcalc-col-active">
                    <label class="cmcalc-toggle">
                        <input type="checkbox" class="cmcalc-toggle-active" <?php checked( $active, '1' ); ?>>
                        <span class="cmcalc-toggle-slider"></span>
                    </label>
                </td>
                <td class="cmcalc-col-title">
                    <input type="text" class="cmcalc-inline-edit" data-field="title" value="<?php echo esc_attr( $dienst->post_title ); ?>">
                </td>
                <td class="cmcalc-col-price">
                    <div class="cmcalc-input-prefix">&euro;</div>
                    <input type="number" class="cmcalc-inline-edit" data-field="base_price" value="<?php echo esc_attr( $base_price ); ?>" step="0.01" min="0">
                </td>
                <td class="cmcalc-col-unit">
                    <select class="cmcalc-inline-edit" data-field="price_unit">
                        <?php foreach ( $unit_options as $val => $label ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $price_unit, $val ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="cmcalc-col-min">
                    <div class="cmcalc-input-prefix">&euro;</div>
                    <input type="number" class="cmcalc-inline-edit" data-field="minimum_price" value="<?php echo esc_attr( $min_price ); ?>" step="0.01" min="0">
                </td>
                <td class="cmcalc-col-discount">
                    <input type="number" class="cmcalc-inline-edit" data-field="discount_percent" value="<?php echo esc_attr( $discount ); ?>" min="0" max="100">
                    <span class="cmcalc-input-suffix">%</span>
                </td>
                <td class="cmcalc-col-quote">
                    <label class="cmcalc-toggle">
                        <input type="checkbox" class="cmcalc-toggle-quote" data-field="requires_quote" <?php checked( $needs_quote, '1' ); ?>>
                        <span class="cmcalc-toggle-slider"></span>
                    </label>
                </td>
                <td class="cmcalc-col-sub">
                    <button type="button" class="button cmcalc-btn-sub-options" data-id="<?php echo esc_attr( $dienst->ID ); ?>" data-sub='<?php echo esc_attr( $sub_options ?: '[]' ); ?>'>
                        Beheer (<?php echo $sub_count; ?>)
                    </button>
                    <button type="button" class="button cmcalc-volume-tiers-btn" data-id="<?php echo esc_attr( $dienst->ID ); ?>" data-tiers='<?php echo esc_attr( $volume_tiers ?: '[]' ); ?>'>
                        Staffelprijzen <span class="cmcalc-badge"><?php echo $tier_count; ?></span>
                    </button>
                </td>
                <?php
                    $dienst_bedrijf_ids = json_decode( get_post_meta( $dienst->ID, '_cm_bedrijf_ids', true ), true );
                    if ( ! is_array( $dienst_bedrijf_ids ) ) $dienst_bedrijf_ids = array();
                    $linked_count = count( $dienst_bedrijf_ids );
                ?>
                <td style="position:relative;">
                    <div class="cmcalc-dienst-bedrijven-wrap" data-dienst-id="<?php echo esc_attr( $dienst->ID ); ?>">
                        <button type="button" class="button cmcalc-dienst-bedrijven-btn" style="font-size:12px;padding:4px 10px;">
                            <?php echo $linked_count > 0 ? $linked_count . ' bedrijf' . ($linked_count > 1 ? 'en' : '') : 'Geen'; ?>
                        </button>
                        <div class="cmcalc-dienst-bedrijven-dropdown" style="display:none;position:absolute;background:#fff;border:1.5px solid #e9ecef;border-radius:8px;padding:10px;z-index:100;box-shadow:0 4px 15px rgba(0,0,0,0.1);min-width:180px;">
                            <?php foreach ( $bedrijven as $b ) : ?>
                            <label style="display:flex;align-items:center;gap:6px;padding:4px 0;font-size:13px;cursor:pointer;">
                                <input type="checkbox" class="cmcalc-dienst-bedrijf-check" value="<?php echo esc_attr( $b->ID ); ?>"
                                    <?php checked( in_array( $b->ID, $dienst_bedrijf_ids ) ); ?>>
                                <?php echo esc_html( $b->post_title ); ?>
                            </label>
                            <?php endforeach; ?>
                            <?php if ( empty( $bedrijven ) ) : ?>
                                <p style="font-size:12px;color:#999;margin:0;">Geen bedrijven. Maak eerst een bedrijf aan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="cmcalc-col-actions">
                    <button type="button" class="button cmcalc-btn-delete" title="Verwijderen">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Sub-opties Modal -->
<div id="cmcalcSubOptionsModal" class="cmcalc-modal" style="display: none;">
    <div class="cmcalc-modal-content" style="width: 800px;">
        <div class="cmcalc-modal-header">
            <h3>Sub-opties voor: <span id="cmcalcSubModalTitle"></span></h3>
            <button type="button" class="cmcalc-modal-close">&times;</button>
        </div>
        <div class="cmcalc-modal-body">
            <p class="cmcalc-sub-help">Voeg extra opties toe die de klant kan selecteren bij deze dienst. <strong>Checkbox</strong> = aan/uit vinken. <strong>Select</strong> = dropdown keuze.</p>
            <div id="cmcalcSubBody"></div>
            <button type="button" class="button" id="cmcalcAddSubOption" style="margin-top: 12px;">
                <span class="dashicons dashicons-plus-alt2"></span> Nieuwe sub-optie
            </button>
        </div>
        <div class="cmcalc-modal-footer">
            <button type="button" class="button button-primary" id="cmcalcSaveSubOptions">Opslaan</button>
            <button type="button" class="button cmcalc-modal-close">Annuleren</button>
        </div>
    </div>
</div>

<!-- Staffelprijzen (Volume Tiers) Modal -->
<div id="cmcalcVolumeTiersModal" class="cmcalc-modal" style="display: none;">
    <div class="cmcalc-modal-overlay"></div>
    <div class="cmcalc-modal-content" style="width: 700px;">
        <div class="cmcalc-modal-header">
            <h3>Staffelprijzen</h3>
            <button type="button" class="cmcalc-modal-close">&times;</button>
        </div>
        <div class="cmcalc-modal-body">
            <p style="color:#6c757d;font-size:13px;margin:0 0 16px;">Stel kortingen in op basis van het aantal. Klanten krijgen automatisch de juiste prijs.</p>
            <div id="cmcalcVolumeTiersList"></div>
            <button type="button" class="button" id="cmcalcAddVolumeTier" style="margin-top: 12px;">
                <span class="dashicons dashicons-plus-alt2"></span> Staffel toevoegen
            </button>
        </div>
        <div class="cmcalc-modal-footer">
            <button type="button" class="button button-primary" id="cmcalcSaveVolumeTiers">Opslaan</button>
            <button type="button" class="button cmcalc-modal-close">Annuleren</button>
        </div>
    </div>
</div>
