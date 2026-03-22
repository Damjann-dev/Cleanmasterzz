<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$status_labels = array(
    'nieuw'       => 'Nieuw',
    'bevestigd'   => 'Bevestigd',
    'gepland'     => 'Gepland',
    'voltooid'    => 'Voltooid',
    'geannuleerd' => 'Geannuleerd',
);
?>

<!-- Toolbar -->
<div class="cmcalc-booking-toolbar">
    <select id="cmcalcBookingStatusFilter">
        <option value="alle">Alle statussen</option>
        <?php foreach ( $status_labels as $key => $label ) : ?>
            <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select>

    <input type="text" id="cmcalcBookingSearch" placeholder="Zoek op naam of email..." class="cmcalc-booking-search">

    <div class="cmcalc-booking-date-range">
        <input type="date" id="cmcalcDateFrom" title="Van datum">
        <span>—</span>
        <input type="date" id="cmcalcDateTo" title="Tot datum">
    </div>

    <button type="button" class="button" id="cmcalcExportBookings" title="Exporteer naar CSV">
        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span> CSV Export
    </button>

    <span class="cmcalc-booking-count" id="cmcalcBookingCount"></span>
</div>

<!-- View Toggle -->
<div class="cmcalc-view-toggle">
    <button type="button" class="cmcalc-view-btn active" data-view="table">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Lijst
    </button>
    <button type="button" class="cmcalc-view-btn" data-view="calendar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Kalender
    </button>
</div>

<!-- Table -->
<div class="cmcalc-table-wrap">
    <table class="cmcalc-table">
        <thead>
            <tr>
                <th style="width:130px;">Datum</th>
                <th>Naam</th>
                <th>Email</th>
                <th>Diensten</th>
                <th style="width:110px;">Status</th>
                <th style="width:100px;">Totaal</th>
            </tr>
        </thead>
        <tbody id="cmcalcBookingsBody">
            <tr><td colspan="6" class="text-center" style="padding:40px;color:#999;">Laden...</td></tr>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="cmcalc-pagination" id="cmcalcPagination"></div>

<!-- Calendar View -->
<div id="cmcalcCalendarView" class="cmcalc-calendar" style="display:none;">
    <div class="cmcalc-calendar-header">
        <button type="button" class="button cmcalc-calendar-prev">&larr;</button>
        <h3 class="cmcalc-calendar-month" id="cmcalcCalendarMonth"></h3>
        <button type="button" class="button cmcalc-calendar-next">&rarr;</button>
    </div>
    <div class="cmcalc-calendar-grid" id="cmcalcCalendarGrid"></div>
</div>

<!-- Slide-in Detail Panel -->
<div id="cmcalcBookingPanel" class="cmcalc-booking-panel">
    <div class="cmcalc-panel-overlay" id="cmcalcPanelOverlay"></div>
    <div class="cmcalc-panel-content">
        <div class="cmcalc-panel-header">
            <div>
                <h3 id="cmcalcPanelName">—</h3>
                <span class="cmcalc-status-badge" id="cmcalcPanelStatusBadge"></span>
                <small class="cmcalc-panel-date" id="cmcalcPanelDate"></small>
            </div>
            <button type="button" class="cmcalc-panel-close" id="cmcalcPanelClose">&times;</button>
        </div>

        <div class="cmcalc-panel-body">
            <!-- Status -->
            <div class="cmcalc-panel-section">
                <label class="cmcalc-panel-label">Status wijzigen</label>
                <select id="cmcalcPanelStatus" class="cmcalc-panel-select">
                    <?php foreach ( $status_labels as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Contact -->
            <div class="cmcalc-panel-section">
                <h4 class="cmcalc-panel-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Contactgegevens
                </h4>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Email</span>
                    <a id="cmcalcPanelEmail" href="" class="cmcalc-panel-field-value"></a>
                </div>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Telefoon</span>
                    <a id="cmcalcPanelPhone" href="" class="cmcalc-panel-field-value"></a>
                </div>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Adres</span>
                    <span id="cmcalcPanelAddress" class="cmcalc-panel-field-value"></span>
                </div>
            </div>

            <!-- Services -->
            <div class="cmcalc-panel-section">
                <h4 class="cmcalc-panel-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
                    Diensten
                </h4>
                <div id="cmcalcPanelServices"></div>
            </div>

            <!-- Location -->
            <div class="cmcalc-panel-section" id="cmcalcPanelLocationSection" style="display:none;">
                <h4 class="cmcalc-panel-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Locatie & Reiskosten
                </h4>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Postcode</span>
                    <span id="cmcalcPanelPostcode" class="cmcalc-panel-field-value"></span>
                </div>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Afstand</span>
                    <span id="cmcalcPanelDistance" class="cmcalc-panel-field-value"></span>
                </div>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Werkgebied</span>
                    <span id="cmcalcPanelWerkgebied" class="cmcalc-panel-field-value"></span>
                </div>
                <div class="cmcalc-panel-field">
                    <span class="cmcalc-panel-field-label">Voorrijkosten</span>
                    <span id="cmcalcPanelTravel" class="cmcalc-panel-field-value"></span>
                </div>
            </div>

            <!-- Extra info -->
            <div class="cmcalc-panel-section" id="cmcalcPanelExtraSection">
                <h4 class="cmcalc-panel-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Aanvullende info
                </h4>
                <div class="cmcalc-panel-field" id="cmcalcPanelPreferredDateWrap">
                    <span class="cmcalc-panel-field-label">Voorkeursdatum</span>
                    <span id="cmcalcPanelPreferredDate" class="cmcalc-panel-field-value"></span>
                </div>
                <div class="cmcalc-panel-field" id="cmcalcPanelMessageWrap">
                    <span class="cmcalc-panel-field-label">Bericht</span>
                    <p id="cmcalcPanelMessage" class="cmcalc-panel-field-value" style="white-space: pre-wrap;"></p>
                </div>
            </div>

            <!-- Total -->
            <div class="cmcalc-panel-total">
                <span>Totaal</span>
                <strong id="cmcalcPanelTotal">€0,00</strong>
            </div>

            <!-- Notes -->
            <div class="cmcalc-panel-section">
                <h4 class="cmcalc-panel-section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Interne notities
                </h4>
                <textarea id="cmcalcPanelNotes" class="cmcalc-panel-notes" rows="4" placeholder="Notities toevoegen..."></textarea>
                <small class="cmcalc-notes-status" id="cmcalcNotesStatus"></small>
            </div>
        </div>

        <div class="cmcalc-panel-footer">
            <button type="button" class="button" id="cmcalcPanelEmailBtn">
                <span class="dashicons dashicons-email" style="vertical-align:middle;margin-right:4px;"></span> Email klant
            </button>
            <button type="button" class="button" id="cmcalcPanelResendBtn">
                <span class="dashicons dashicons-update" style="vertical-align:middle;margin-right:4px;"></span> Opnieuw verzenden
            </button>
            <button type="button" class="button" id="cmcalcPanelEditBtn">
                <span class="dashicons dashicons-edit" style="vertical-align:middle;margin-right:4px;"></span> Bewerken
            </button>
            <button type="button" class="button cmcalc-btn-danger" id="cmcalcPanelDeleteBtn">
                <span class="dashicons dashicons-trash" style="vertical-align:middle;margin-right:4px;"></span> Verwijderen
            </button>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div id="cmcalcEmailModal" class="cmcalc-modal" style="display:none;">
    <div class="cmcalc-modal-content" style="width:560px;">
        <div class="cmcalc-modal-header">
            <h3>Email versturen naar klant</h3>
            <button type="button" class="cmcalc-modal-close">&times;</button>
        </div>
        <div class="cmcalc-modal-body">
            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailTo">Aan</label>
                <input type="email" id="cmcalcEmailTo" class="regular-text" style="width:100%;">
            </div>
            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailSubjectField">Onderwerp</label>
                <input type="text" id="cmcalcEmailSubjectField" class="regular-text" style="width:100%;">
            </div>
            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailMessage">Bericht</label>
                <textarea id="cmcalcEmailMessage" rows="8" style="width:100%;"></textarea>
            </div>
        </div>
        <div class="cmcalc-modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid #e9ecef;">
            <button type="button" class="button cmcalc-email-cancel">Annuleren</button>
            <button type="button" class="button button-primary" id="cmcalcSendEmail">
                <span class="dashicons dashicons-yes" style="vertical-align:middle;margin-right:4px;"></span> Verzenden
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="cmcalcEditModal" class="cmcalc-modal" style="display:none;">
    <div class="cmcalc-modal-content" style="width:560px;">
        <div class="cmcalc-modal-header">
            <h3>Boeking bewerken</h3>
            <button type="button" class="cmcalc-modal-close">&times;</button>
        </div>
        <div class="cmcalc-modal-body">
            <div class="cmcalc-edit-grid">
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditName">Naam</label>
                    <input type="text" id="cmcalcEditName" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditEmail">Email</label>
                    <input type="email" id="cmcalcEditEmail" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditPhone">Telefoon</label>
                    <input type="tel" id="cmcalcEditPhone" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditDate">Voorkeursdatum</label>
                    <input type="date" id="cmcalcEditDate" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field" style="grid-column: 1 / -1;">
                    <label for="cmcalcEditAddress">Adres</label>
                    <input type="text" id="cmcalcEditAddress" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field" style="grid-column: 1 / -1;">
                    <label for="cmcalcEditMessage">Bericht</label>
                    <textarea id="cmcalcEditMessage" rows="3" style="width:100%;"></textarea>
                </div>
                <!-- Diensten bewerken -->
                <div class="cmcalc-settings-field" style="grid-column: 1 / -1;">
                    <label>Diensten</label>
                    <div id="cmcalcEditServices" class="cmcalc-edit-services"></div>
                    <button type="button" class="button" id="cmcalcEditAddService" style="margin-top:8px;">
                        <span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;"></span> Dienst toevoegen
                    </button>
                </div>
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditTravel">Voorrijkosten (&euro;)</label>
                    <input type="number" id="cmcalcEditTravel" step="0.01" min="0" class="regular-text" style="width:100%;">
                </div>
                <div class="cmcalc-settings-field">
                    <label for="cmcalcEditTotal">Totaal (&euro;)</label>
                    <input type="number" id="cmcalcEditTotal" step="0.01" min="0" class="regular-text" style="width:100%;">
                </div>
            </div>
        </div>
        <div class="cmcalc-modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid #e9ecef;">
            <button type="button" class="button cmcalc-edit-cancel">Annuleren</button>
            <button type="button" class="button button-primary" id="cmcalcSaveEdit">
                <span class="dashicons dashicons-yes" style="vertical-align:middle;margin-right:4px;"></span> Opslaan
            </button>
        </div>
    </div>
</div>
