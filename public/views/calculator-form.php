<?php
/**
 * Calculator Form View - CleanMasterzz 3-Step Price Calculator
 *
 * @package CleanMasterzz_Calculator
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="cmcalc-calculator" id="cmcalcCalculator">

    <!-- Progress Bar -->
    <div class="cmcalc-progress">
        <div class="cmcalc-progress__track">
            <div class="cmcalc-progress__fill" id="cmcalcProgressBar"></div>
        </div>
        <div class="cmcalc-progress__dots">
            <button type="button" class="cmcalc-progress__dot is-active" data-step="1">
                <span class="dot-num">1</span>
                <span class="dot-label">Diensten</span>
            </button>
            <button type="button" class="cmcalc-progress__dot" data-step="2">
                <span class="dot-num">2</span>
                <span class="dot-label">Overzicht</span>
            </button>
            <button type="button" class="cmcalc-progress__dot" data-step="3">
                <span class="dot-num">3</span>
                <span class="dot-label">Boeken</span>
            </button>
        </div>
    </div>

    <!-- STEP 1: Service Selection -->
    <div class="cmcalc-step is-active" data-step="1">

        <div class="cmcalc-step__head">
            <h3 class="cmcalc-step__title">Stel uw pakket samen</h3>
            <p class="cmcalc-step__subtitle">Selecteer diensten en stel uw pakket samen</p>
        </div>

        <div class="cmcalc-customer-toggle">
            <span class="cmcalc-customer-toggle__label cmcalc-customer-toggle__label--active" id="cmcalcPartLabel">Particulier</span>
            <label class="cmcalc-customer-toggle__switch">
                <input type="checkbox" id="cmcalcZakelijkToggle">
                <span class="cmcalc-customer-toggle__slider"></span>
            </label>
            <span class="cmcalc-customer-toggle__label" id="cmcalcZakLabel">Zakelijk</span>
        </div>

        <div class="cmcalc-service-list" id="cmcalcServices">
            <div class="cmcalc-service-loading">
                <div class="cmcalc-spinner"></div>
                <span>Diensten laden&hellip;</span>
            </div>
        </div>

        <div class="cmcalc-running-total" id="cmcalcRunningTotal" style="display:none;">
            <span>Geschatte totaalprijs</span>
            <strong id="cmcalcRunningTotalPrice">&euro;0,00</strong>
        </div>

        <div class="cmcalc-step__actions">
            <p class="cmcalc-disclaimer">Prijzen zijn indicatief. Definitieve prijs kan afwijken op basis van locatie en specifieke situatie.</p>
            <button type="button" id="cmcalcToOverview" class="cmcalc-btn cmcalc-btn--primary" disabled>
                Bekijk overzicht
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </div>

    </div>

    <!-- STEP 2: Overview + Location -->
    <div class="cmcalc-step" data-step="2">

        <div class="cmcalc-step__head">
            <h3 class="cmcalc-step__title">Overzicht &amp; Locatie</h3>
            <p class="cmcalc-step__subtitle">Controleer uw selectie en vul uw adres in</p>
        </div>

        <h4 class="cmcalc-section-label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            Geselecteerde diensten
        </h4>
        <div class="cmcalc-overview" id="cmcalcOverview"></div>

        <h4 class="cmcalc-section-label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Uw locatie
        </h4>
        <div class="cmcalc-location-card">
            <div class="cmcalc-location-fields">
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcPostcode">Postcode</label>
                    <input type="text" id="cmcalcPostcode" class="cmcalc-field__input" placeholder="1234 AB" maxlength="7">
                </div>
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcHouseNumber">Huisnummer</label>
                    <input type="text" id="cmcalcHouseNumber" class="cmcalc-field__input" placeholder="12">
                </div>
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcCity">Plaats</label>
                    <input type="text" id="cmcalcCity" class="cmcalc-field__input" placeholder="Amsterdam">
                </div>
            </div>
            <div class="cmcalc-distance-result" id="cmcalcDistance" style="display:none;">
                <div class="cmcalc-distance-result__row">
                    <span>Afstand:</span>
                    <strong id="cmcalcKmDistance">0</strong> km
                </div>
                <div class="cmcalc-distance-result__row">
                    <span>Toeslag:</span>
                    <strong id="cmcalcKmSurcharge">&euro;0,00</strong>
                </div>
                <div class="cmcalc-distance-result__row">
                    <span>Dichtstbijzijnde vestiging:</span>
                    <strong id="cmcalcNearestArea">-</strong>
                </div>
            </div>
        </div>

        <h4 class="cmcalc-section-label">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            Prijsoverzicht
        </h4>
        <div class="cmcalc-totals-card">
            <div class="cmcalc-totals-card__row">
                <span>Subtotaal</span>
                <strong id="cmcalcSubtotal">&euro;0,00</strong>
            </div>
            <div class="cmcalc-totals-card__row" id="cmcalcSurchargeRow" style="display:none;">
                <span>Voorrijkosten</span>
                <strong id="cmcalcTravelSurcharge">&euro;0,00</strong>
            </div>
            <div class="cmcalc-totals-card__row">
                <span id="cmcalcBtwLabel">BTW (21%)</span>
                <strong id="cmcalcBtw">&euro;0,00</strong>
            </div>
            <div class="cmcalc-totals-card__row cmcalc-totals-card__row--total">
                <span id="cmcalcTotalLabel">Totaal incl. BTW</span>
                <strong id="cmcalcTotalInclBtw">&euro;0,00</strong>
            </div>
        </div>

        <div class="cmcalc-step__actions">
            <button type="button" class="cmcalc-btn cmcalc-btn--outline" data-goto="1">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                Vorige
            </button>
            <button type="button" class="cmcalc-btn cmcalc-btn--primary" data-goto="3">
                Gegevens invullen
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </div>

    </div>

    <!-- STEP 3: Booking Form -->
    <div class="cmcalc-step" data-step="3">

        <div class="cmcalc-step__head">
            <h3 class="cmcalc-step__title">Boeking afronden</h3>
            <p class="cmcalc-step__subtitle">Vul uw gegevens in om de boeking te voltooien</p>
        </div>

        <div class="cmcalc-booking-form">
            <div class="cmcalc-booking-form__grid">
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcBookingName">Naam *</label>
                    <input type="text" id="cmcalcBookingName" class="cmcalc-field__input" placeholder="Uw volledige naam" required>
                </div>
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcBookingEmail">E-mail *</label>
                    <input type="email" id="cmcalcBookingEmail" class="cmcalc-field__input" placeholder="uw@email.nl" required>
                </div>
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcBookingPhone">Telefoon *</label>
                    <input type="tel" id="cmcalcBookingPhone" class="cmcalc-field__input" placeholder="06-12345678" required>
                </div>
                <div class="cmcalc-field">
                    <label class="cmcalc-field__label" for="cmcalcBookingDate">Gewenste datum</label>
                    <input type="date" id="cmcalcBookingDate" class="cmcalc-field__input">
                </div>
            </div>
            <div class="cmcalc-field cmcalc-field--full">
                <label class="cmcalc-field__label" for="cmcalcBookingMessage">Opmerkingen</label>
                <textarea id="cmcalcBookingMessage" class="cmcalc-field__textarea" rows="4" placeholder="Eventuele opmerkingen of speciale wensen..."></textarea>
            </div>
            <div class="cmcalc-field cmcalc-field--full">
                <label class="cmcalc-terms">
                    <input type="checkbox" id="cmcalcTerms">
                    <span>Ik ga akkoord met de <a href="/algemene-voorwaarden/" target="_blank">algemene voorwaarden</a> *</span>
                </label>
            </div>
        </div>

        <div class="cmcalc-step__actions">
            <button type="button" class="cmcalc-btn cmcalc-btn--outline" data-goto="2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                Vorige
            </button>
            <button type="button" id="cmcalcSubmit" class="cmcalc-btn cmcalc-btn--primary">
                Boeking versturen
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
        </div>

    </div>

    <!-- STEP SUCCESS -->
    <div class="cmcalc-step" data-step="success">
        <div class="cmcalc-success">
            <div class="cmcalc-success__icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--cmcalc-secondary, #28a745)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h3 class="cmcalc-success__title">Boeking ontvangen!</h3>
            <p class="cmcalc-success__text">Bedankt voor uw aanvraag. Wij nemen zo snel mogelijk contact met u op om de details te bevestigen.</p>
            <button type="button" id="cmcalcRestart" class="cmcalc-btn cmcalc-btn--outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Nieuwe berekening
            </button>
        </div>
    </div>

    <!-- Questionnaire Popup -->
    <div class="cmcalc-questionnaire" id="cmcalcQuestionnaire" style="display:none;">
        <div class="cmcalc-questionnaire__overlay"></div>
        <div class="cmcalc-questionnaire__panel">
            <div class="cmcalc-questionnaire__header">
                <h3 class="cmcalc-questionnaire__title" id="cmcalcQTitle"></h3>
                <button type="button" class="cmcalc-questionnaire__close" aria-label="Sluiten">&times;</button>
            </div>
            <div class="cmcalc-questionnaire__body" id="cmcalcQBody"></div>
            <div class="cmcalc-questionnaire__footer">
                <button type="button" class="cmcalc-btn cmcalc-btn--outline cmcalc-questionnaire__cancel">Annuleren</button>
                <button type="button" class="cmcalc-btn cmcalc-btn--primary cmcalc-questionnaire__confirm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Toevoegen
                </button>
            </div>
        </div>
    </div>

    <!-- Templates -->
    <template id="cmcalcServiceCardTpl">
        <div class="cmcalc-service" data-service-id="" data-index="">
            <label class="cmcalc-service__check">
                <input type="checkbox" class="cmcalc-service__checkbox">
                <span class="cmcalc-service__tick">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
            </label>
            <div class="cmcalc-service__body">
                <div class="cmcalc-service__top">
                    <span class="cmcalc-service__name"></span>
                    <span class="cmcalc-service__badge cmcalc-service__badge--offerte" style="display:none;">Offerte</span>
                </div>
                <div class="cmcalc-service__meta">
                    <span class="cmcalc-service__price"></span>
                    <span class="cmcalc-service__unit"></span>
                    <span class="cmcalc-service__min" style="display:none;"></span>
                </div>
                <div class="cmcalc-service__tiers" style="display:none;"></div>
            </div>
            <div class="cmcalc-service__qty" style="display:none;">
                <button type="button" class="cmcalc-service__qty-btn cmcalc-service__qty-btn--minus" aria-label="Verminder">&minus;</button>
                <input type="number" class="cmcalc-service__qty-input" value="1" min="1">
                <button type="button" class="cmcalc-service__qty-btn cmcalc-service__qty-btn--plus" aria-label="Verhoog">+</button>
            </div>
            <span class="cmcalc-service__line-total" style="display:none;"></span>
        </div>
    </template>

    <template id="cmcalcOverviewRowTpl">
        <div class="cmcalc-overview__row">
            <div class="cmcalc-overview__item-info">
                <span class="cmcalc-overview__item-name"></span>
                <span class="cmcalc-overview__item-detail"></span>
            </div>
            <span class="cmcalc-overview__item-price"></span>
        </div>
    </template>

</div>
