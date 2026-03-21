<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$styles  = CMCalc_Admin::get_styles();
$presets = CMCalc_Admin::get_style_presets();
?>

<div class="cmcalc-stijl-layout">
    <!-- Left Column: Settings -->
    <div class="cmcalc-stijl-settings">

        <!-- Presets -->
        <div class="cmcalc-section">
            <h2 style="margin-top:0;">Thema kiezen</h2>
            <p class="cmcalc-text-muted" style="margin-bottom: 16px;">Kies een vooringesteld thema of pas de kleuren handmatig aan.</p>
            <div class="cmcalc-preset-grid">
                <?php foreach ( $presets as $key => $preset ) : ?>
                <div class="cmcalc-preset-card <?php echo $styles['preset'] === $key ? 'active' : ''; ?>" data-preset="<?php echo esc_attr( $key ); ?>">
                    <div class="cmcalc-preset-swatches">
                        <span style="background:<?php echo esc_attr( $preset['primary_color'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $preset['secondary_color'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $preset['accent_color'] ); ?>;"></span>
                    </div>
                    <div class="cmcalc-preset-bg" style="background:<?php echo esc_attr( $preset['bg_color'] ); ?>; color:<?php echo esc_attr( $preset['text_color'] ); ?>;">
                        <span class="cmcalc-preset-name"><?php echo esc_html( $preset['name'] ); ?></span>
                    </div>
                    <?php if ( $styles['preset'] === $key ) : ?>
                    <div class="cmcalc-preset-check">✓</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="cmcalc-preset-card <?php echo $styles['preset'] === 'custom' ? 'active' : ''; ?>" data-preset="custom">
                    <div class="cmcalc-preset-swatches">
                        <span style="background:<?php echo esc_attr( $styles['primary_color'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $styles['secondary_color'] ); ?>;"></span>
                        <span style="background:<?php echo esc_attr( $styles['accent_color'] ); ?>;"></span>
                    </div>
                    <div class="cmcalc-preset-bg" style="background: linear-gradient(135deg, #ddd, #eee);">
                        <span class="cmcalc-preset-name">Aangepast</span>
                    </div>
                    <?php if ( $styles['preset'] === 'custom' ) : ?>
                    <div class="cmcalc-preset-check">✓</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colors -->
        <div class="cmcalc-section">
            <h2 style="margin-top:0;">Kleuren</h2>
            <div class="cmcalc-color-grid">
                <div class="cmcalc-color-field">
                    <label>Primaire kleur</label>
                    <input type="text" class="cmcalc-color-picker" data-var="primary_color" value="<?php echo esc_attr( $styles['primary_color'] ); ?>">
                    <small>Knoppen, checkmarks, borders</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Secundaire kleur</label>
                    <input type="text" class="cmcalc-color-picker" data-var="secondary_color" value="<?php echo esc_attr( $styles['secondary_color'] ); ?>">
                    <small>Voltooide stappen, kortingen</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Accent kleur</label>
                    <input type="text" class="cmcalc-color-picker" data-var="accent_color" value="<?php echo esc_attr( $styles['accent_color'] ); ?>">
                    <small>Info elementen, links</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Tekst kleur</label>
                    <input type="text" class="cmcalc-color-picker" data-var="text_color" value="<?php echo esc_attr( $styles['text_color'] ); ?>">
                    <small>Primaire tekst</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Tekst licht</label>
                    <input type="text" class="cmcalc-color-picker" data-var="text_light_color" value="<?php echo esc_attr( $styles['text_light_color'] ); ?>">
                    <small>Subtitels, labels</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Achtergrond</label>
                    <input type="text" class="cmcalc-color-picker" data-var="bg_color" value="<?php echo esc_attr( $styles['bg_color'] ); ?>">
                    <small>Calculator achtergrond</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Achtergrond licht</label>
                    <input type="text" class="cmcalc-color-picker" data-var="bg_light_color" value="<?php echo esc_attr( $styles['bg_light_color'] ); ?>">
                    <small>Service rijen, kaarten</small>
                </div>
                <div class="cmcalc-color-field">
                    <label>Rand kleur</label>
                    <input type="text" class="cmcalc-color-picker" data-var="border_color" value="<?php echo esc_attr( $styles['border_color'] ); ?>">
                    <small>Borders, scheidingslijnen</small>
                </div>
            </div>
        </div>

        <!-- Format / Size -->
        <div class="cmcalc-section">
            <h2 style="margin-top:0;">Formaat &amp; Afmetingen</h2>
            <div class="cmcalc-layout-grid">
                <div class="cmcalc-layout-field">
                    <label>Calculator breedte</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcMaxWidth" min="400" max="1400" step="50" value="<?php echo esc_attr( $styles['calc_max_width'] ?? 1100 ); ?>" class="cmcalc-range-input" data-var="calc_max_width">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['calc_max_width'] ?? 1100 ); ?>px</span>
                    </div>
                    <small class="cmcalc-field-hint">Maximale breedte van de calculator</small>
                </div>
                <div class="cmcalc-layout-field">
                    <label>Binnenruimte (padding)</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcPadding" min="8" max="48" value="<?php echo esc_attr( $styles['calc_padding'] ?? 24 ); ?>" class="cmcalc-range-input" data-var="calc_padding">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['calc_padding'] ?? 24 ); ?>px</span>
                    </div>
                    <small class="cmcalc-field-hint">Ruimte rondom de inhoud</small>
                </div>
                <div class="cmcalc-layout-field cmcalc-layout-field--full">
                    <label>Dichtheid / Spacing</label>
                    <div class="cmcalc-spacing-options">
                        <?php $spacing = $styles['calc_spacing'] ?? 'normal'; ?>
                        <label class="cmcalc-spacing-option <?php echo $spacing === 'compact' ? 'active' : ''; ?>">
                            <input type="radio" name="cmcalc_spacing" value="compact" <?php checked( $spacing, 'compact' ); ?>>
                            <div class="cmcalc-spacing-option__visual">
                                <span class="cmcalc-spacing-bars cmcalc-spacing-bars--compact">
                                    <i></i><i></i><i></i><i></i>
                                </span>
                            </div>
                            <span class="cmcalc-spacing-option__label">Compact</span>
                        </label>
                        <label class="cmcalc-spacing-option <?php echo $spacing === 'normal' ? 'active' : ''; ?>">
                            <input type="radio" name="cmcalc_spacing" value="normal" <?php checked( $spacing, 'normal' ); ?>>
                            <div class="cmcalc-spacing-option__visual">
                                <span class="cmcalc-spacing-bars cmcalc-spacing-bars--normal">
                                    <i></i><i></i><i></i>
                                </span>
                            </div>
                            <span class="cmcalc-spacing-option__label">Normaal</span>
                        </label>
                        <label class="cmcalc-spacing-option <?php echo $spacing === 'spacious' ? 'active' : ''; ?>">
                            <input type="radio" name="cmcalc_spacing" value="spacious" <?php checked( $spacing, 'spacious' ); ?>>
                            <div class="cmcalc-spacing-option__visual">
                                <span class="cmcalc-spacing-bars cmcalc-spacing-bars--spacious">
                                    <i></i><i></i>
                                </span>
                            </div>
                            <span class="cmcalc-spacing-option__label">Ruim</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layout -->
        <div class="cmcalc-section">
            <h2 style="margin-top:0;">Layout &amp; Stijl</h2>
            <div class="cmcalc-layout-grid">
                <div class="cmcalc-layout-field">
                    <label>Container border-radius</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcBorderRadius" min="0" max="30" value="<?php echo esc_attr( $styles['border_radius'] ); ?>" class="cmcalc-range-input" data-var="border_radius">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['border_radius'] ); ?>px</span>
                    </div>
                </div>
                <div class="cmcalc-layout-field">
                    <label>Knop border-radius</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcBtnRadius" min="0" max="20" value="<?php echo esc_attr( $styles['btn_radius'] ); ?>" class="cmcalc-range-input" data-var="btn_radius">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['btn_radius'] ); ?>px</span>
                    </div>
                </div>
                <div class="cmcalc-layout-field">
                    <label>Schaduw</label>
                    <div class="cmcalc-shadow-controls">
                        <label class="cmcalc-toggle">
                            <input type="checkbox" id="cmcalcShadowEnabled" <?php checked( $styles['shadow_enabled'] ); ?>>
                            <span class="cmcalc-toggle-slider"></span>
                        </label>
                        <select id="cmcalcShadowIntensity" <?php echo ! $styles['shadow_enabled'] ? 'disabled' : ''; ?>>
                            <option value="light" <?php selected( $styles['shadow_intensity'], 'light' ); ?>>Licht</option>
                            <option value="medium" <?php selected( $styles['shadow_intensity'], 'medium' ); ?>>Middel</option>
                            <option value="strong" <?php selected( $styles['shadow_intensity'], 'strong' ); ?>>Sterk</option>
                        </select>
                    </div>
                </div>
                <div class="cmcalc-layout-field">
                    <label>Lettergrootte basis</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcFontBase" min="12" max="20" value="<?php echo esc_attr( $styles['font_size_base'] ); ?>" class="cmcalc-range-input" data-var="font_size_base">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['font_size_base'] ); ?>px</span>
                    </div>
                </div>
                <div class="cmcalc-layout-field">
                    <label>Lettergrootte titel</label>
                    <div class="cmcalc-range-wrap">
                        <input type="range" id="cmcalcFontTitle" min="16" max="32" value="<?php echo esc_attr( $styles['font_size_title'] ); ?>" class="cmcalc-range-input" data-var="font_size_title">
                        <span class="cmcalc-range-value"><?php echo esc_attr( $styles['font_size_title'] ); ?>px</span>
                    </div>
                </div>
            </div>

            <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveStyles" style="margin-top: 20px;">
                Stijl opslaan
            </button>
            <span class="cmcalc-save-status" id="cmcalcStyleStatus"></span>
        </div>
    </div>

    <!-- Right Column: Live Preview -->
    <div class="cmcalc-stijl-preview-wrap">
        <div class="cmcalc-stijl-preview-sticky">
            <h3 style="margin: 0 0 12px; font-size: 14px; color: var(--cmcalc-text-muted); text-transform: uppercase; letter-spacing: 1px;">Live Preview</h3>
            <div class="cmcalc-style-preview" id="cmcalcStylePreview">
                <!-- Mini calculator mockup -->
                <div class="sp-progress"><div class="sp-progress-bar" style="width: 33%;"></div></div>
                <div class="sp-steps">
                    <span class="sp-dot active">1</span>
                    <span class="sp-dot">2</span>
                    <span class="sp-dot">3</span>
                </div>
                <div class="sp-title">Stel uw pakket samen</div>
                <p class="sp-subtitle">Selecteer diensten en hoeveelheid</p>

                <div class="sp-service selected">
                    <div class="sp-service-row">
                        <span class="sp-check checked">✓</span>
                        <span class="sp-service-name">Glasbewassing</span>
                        <span class="sp-service-price">€2,00/raam</span>
                    </div>
                    <div class="sp-qty-row">
                        <span class="sp-qty-label">Aantal:</span>
                        <span class="sp-qty-val">10</span>
                        <span class="sp-qty-price">€20,00</span>
                    </div>
                    <div class="sp-sub-row">
                        <span class="sp-sub-check">☐</span>
                        <span>Handwas +€0,50</span>
                    </div>
                </div>
                <div class="sp-service">
                    <div class="sp-service-row">
                        <span class="sp-check"></span>
                        <span class="sp-service-name">Terrasreiniging</span>
                        <span class="sp-service-price">€3,50/m²</span>
                    </div>
                </div>

                <div class="sp-total">
                    <span>Geschatte totaalprijs:</span>
                    <strong>€20,00</strong>
                </div>

                <div class="sp-buttons">
                    <button class="sp-btn-outline">← Terug</button>
                    <button class="sp-btn-primary">Bekijk overzicht →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Stijl tab layout */
.cmcalc-stijl-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1200px) {
    .cmcalc-stijl-layout {
        grid-template-columns: 1fr;
    }
}
.cmcalc-stijl-preview-sticky {
    position: sticky;
    top: 46px;
}

/* Preset grid */
.cmcalc-preset-grid {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.cmcalc-preset-card {
    width: 100px;
    border: 2px solid var(--cmcalc-border);
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    transition: var(--cmcalc-transition);
    position: relative;
}
.cmcalc-preset-card:hover {
    border-color: var(--cmcalc-blue);
    transform: translateY(-2px);
    box-shadow: var(--cmcalc-shadow);
}
.cmcalc-preset-card.active {
    border-color: var(--cmcalc-navy);
    box-shadow: 0 0 0 3px rgba(27, 42, 74, 0.2);
}
.cmcalc-preset-swatches {
    display: flex;
    height: 24px;
}
.cmcalc-preset-swatches span {
    flex: 1;
}
.cmcalc-preset-bg {
    padding: 8px 6px;
    text-align: center;
}
.cmcalc-preset-name {
    font-size: 11px;
    font-weight: 700;
}
.cmcalc-preset-check {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 18px;
    height: 18px;
    background: var(--cmcalc-navy);
    color: #fff;
    border-radius: 50%;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

/* Color grid */
.cmcalc-color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}
.cmcalc-color-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--cmcalc-text);
    margin-bottom: 6px;
}
.cmcalc-color-field small {
    display: block;
    font-size: 11px;
    color: var(--cmcalc-text-muted);
    margin-top: 4px;
}

/* Layout grid */
.cmcalc-layout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 900px) {
    .cmcalc-layout-grid { grid-template-columns: 1fr; }
}
.cmcalc-layout-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--cmcalc-text);
    margin-bottom: 8px;
}
.cmcalc-range-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cmcalc-range-input {
    flex: 1;
    accent-color: var(--cmcalc-navy);
}
.cmcalc-range-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--cmcalc-navy);
    min-width: 40px;
}
.cmcalc-shadow-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}
.cmcalc-shadow-controls select {
    padding: 6px 10px;
    border: 1.5px solid var(--cmcalc-border);
    border-radius: 6px;
    font-size: 13px;
}

/* Field hints */
.cmcalc-field-hint {
    display: block;
    font-size: 11px;
    color: var(--cmcalc-text-muted);
    margin-top: 4px;
}
.cmcalc-layout-field--full {
    grid-column: 1 / -1;
}

/* Spacing selector */
.cmcalc-spacing-options {
    display: flex;
    gap: 12px;
}
.cmcalc-spacing-option {
    flex: 1;
    cursor: pointer;
    border: 2px solid var(--cmcalc-border);
    border-radius: 10px;
    padding: 16px 12px;
    text-align: center;
    transition: var(--cmcalc-transition);
    position: relative;
}
.cmcalc-spacing-option:hover {
    border-color: var(--cmcalc-blue);
    transform: translateY(-2px);
    box-shadow: var(--cmcalc-shadow);
}
.cmcalc-spacing-option.active {
    border-color: var(--cmcalc-navy);
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.15);
    background: rgba(15, 23, 42, 0.03);
}
.cmcalc-spacing-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.cmcalc-spacing-option__visual {
    display: flex;
    justify-content: center;
    margin-bottom: 8px;
}
.cmcalc-spacing-bars {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 50px;
}
.cmcalc-spacing-bars i {
    display: block;
    width: 100%;
    height: 6px;
    background: var(--cmcalc-navy, #0f172a);
    border-radius: 3px;
    opacity: 0.4;
}
.cmcalc-spacing-option.active .cmcalc-spacing-bars i {
    opacity: 0.7;
}
.cmcalc-spacing-bars--compact i { margin-bottom: 3px; }
.cmcalc-spacing-bars--normal i { margin-bottom: 6px; }
.cmcalc-spacing-bars--spacious i { margin-bottom: 10px; }
.cmcalc-spacing-bars i:last-child { margin-bottom: 0; }
.cmcalc-spacing-option__label {
    font-size: 12px;
    font-weight: 700;
    color: var(--cmcalc-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ─── Live Preview ─── */
.cmcalc-style-preview {
    background: var(--preview-bg, #ffffff);
    border-radius: var(--preview-radius, 16px);
    padding: 24px;
    box-shadow: var(--preview-shadow, 0 2px 15px rgba(0,0,0,0.08));
    font-size: var(--preview-font-base, 15px);
    color: var(--preview-text, #2d3436);
    border: 1px solid var(--cmcalc-border);
    transition: all 0.3s ease;
    max-width: 380px;
}
.sp-progress {
    height: 4px;
    background: var(--preview-border, #e9ecef);
    border-radius: 4px;
    margin-bottom: 12px;
}
.sp-progress-bar {
    height: 100%;
    background: var(--preview-primary, #1B2A4A);
    border-radius: 4px;
    transition: background 0.3s;
}
.sp-steps {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 16px;
}
.sp-dot {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--preview-border, #e9ecef);
    color: var(--preview-text-light, #6c757d);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}
.sp-dot.active {
    background: var(--preview-primary, #1B2A4A);
    color: var(--preview-bg, #ffffff);
}
.sp-title {
    font-size: var(--preview-font-title, 22px);
    font-weight: 700;
    color: var(--preview-primary, #1B2A4A);
    margin-bottom: 4px;
    transition: all 0.3s;
}
.sp-subtitle {
    color: var(--preview-text-light, #6c757d);
    font-size: 0.85em;
    margin: 0 0 14px;
}
.sp-service {
    background: var(--preview-bg-light, #f8f9fa);
    border: 2px solid transparent;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 6px;
    transition: all 0.3s;
}
.sp-service.selected {
    border-color: var(--preview-primary, #1B2A4A);
    background: rgba(26,42,74,0.04);
}
.sp-service-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.sp-check {
    width: 20px;
    height: 20px;
    border: 2px solid var(--preview-border, #e9ecef);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: all 0.3s;
    background: var(--preview-bg, #fff);
}
.sp-check.checked {
    background: var(--preview-primary, #1B2A4A);
    border-color: var(--preview-primary, #1B2A4A);
    color: var(--preview-bg, #ffffff);
}
.sp-service-name {
    font-weight: 600;
    font-size: 0.9em;
}
.sp-service-price {
    margin-left: auto;
    font-size: 0.8em;
    color: var(--preview-text-light, #6c757d);
}
.sp-qty-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0 0 28px;
    font-size: 0.8em;
    color: var(--preview-text-light, #6c757d);
}
.sp-qty-val {
    background: var(--preview-bg, #fff);
    border: 1px solid var(--preview-border, #e9ecef);
    border-radius: 4px;
    padding: 2px 8px;
    font-weight: 600;
    color: var(--preview-text, #2d3436);
}
.sp-qty-price {
    margin-left: auto;
    font-weight: 700;
    color: var(--preview-primary, #1B2A4A);
}
.sp-sub-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 0 0 28px;
    font-size: 0.8em;
    color: var(--preview-text, #2d3436);
}
.sp-sub-check {
    font-size: 12px;
    color: var(--preview-border, #e9ecef);
}
.sp-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--preview-bg-light, #f8f9fa);
    border: 2px solid var(--preview-primary, #1B2A4A);
    border-radius: 10px;
    padding: 12px 14px;
    margin-top: 12px;
    font-size: 0.9em;
    transition: all 0.3s;
}
.sp-total strong {
    color: var(--preview-primary, #1B2A4A);
    font-size: 1.2em;
}
.sp-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 14px;
}
.sp-btn-primary {
    background: var(--preview-primary, #1B2A4A);
    color: var(--preview-bg, #ffffff);
    border: none;
    padding: 8px 16px;
    border-radius: var(--preview-btn-radius, 8px);
    font-weight: 600;
    font-size: 0.85em;
    cursor: default;
    transition: all 0.3s;
}
.sp-btn-outline {
    background: transparent;
    color: var(--preview-primary, #1B2A4A);
    border: 2px solid var(--preview-primary, #1B2A4A);
    padding: 6px 14px;
    border-radius: var(--preview-btn-radius, 8px);
    font-weight: 600;
    font-size: 0.85em;
    cursor: default;
    transition: all 0.3s;
}
</style>
