/**
 * Cleanmasterzz Calculator - Multi-Service Price Calculator
 * With volume pricing, sub-options, zakelijk toggle, and correct BTW handling.
 *
 * @package CleanMasterzz_Calculator
 */
(function() {
    'use strict';

    // ──────────────────────────────────────────────
    // DOM root
    // ──────────────────────────────────────────────
    var calculator = document.getElementById('cmcalcCalculator');
    if (!calculator) return;

    // ──────────────────────────────────────────────
    // Settings from WordPress
    // ──────────────────────────────────────────────
    var cfg        = window.cmCalc || {};
    var restUrl    = cfg.restUrl || '/wp-json/cleanmasterzz/v1/';
    var nonce      = cfg.nonce   || '';
    var texts      = cfg.texts   || {};
    var settings   = cfg.settings || {};

    var btwPercentage = parseFloat(settings.btw_percentage) || 21;
    // 'incl' = prices include BTW (default), 'excl' = prices exclude BTW
    var btwMode       = settings.show_btw || 'incl';

    // ──────────────────────────────────────────────
    // State
    // ──────────────────────────────────────────────
    var currentStep          = 1;
    var allServices          = [];
    var travelService        = null;
    var werkgebieden         = [];
    var currentDistance       = 0;
    var currentTravelSurcharge = 0;
    var currentNearestArea   = null;
    var debounceTimer        = null;
    var isZakelijk           = false;
    var subOptionSelections  = {};  // { serviceIndex: [{checked, value, label}, ...] }
    var pendingQServiceIndex = -1;  // Service index awaiting questionnaire confirmation

    // ──────────────────────────────────────────────
    // Unit labels
    // ──────────────────────────────────────────────
    var unitLabels = {
        m2:     'm\u00B2',
        stuk:   'stuk(s)',
        paneel: 'paneel/panelen',
        raam:   'ra(a)m(en)',
        vast:   'vast bedrag'
    };

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /** Toast notification (replaces alert()) */
    function showToast(message, type) {
        type = type || 'error';
        var existing = document.querySelector('.cmcalc-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'cmcalc-toast cmcalc-toast--' + type;

        var icons = {
            error: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
            success: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>',
            info: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };

        toast.innerHTML = (icons[type] || icons.info) + '<span>' + message + '</span>';
        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(function() {
            toast.classList.add('is-visible');
        });

        // Auto dismiss
        setTimeout(function() {
            toast.classList.remove('is-visible');
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    }

    /** Format a number as Dutch-style Euro string */
    function formatPrice(num) {
        if (num === null || num === undefined || isNaN(num)) return '\u20AC0,00';
        return '\u20AC' + num.toFixed(2).replace('.', ',');
    }

    /** Haversine distance in km */
    function haversineDistance(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /** Find nearest werkgebied to given coordinates */
    function findNearestWerkgebied(lat, lon) {
        var nearest = null;
        var minDist = Infinity;
        for (var i = 0; i < werkgebieden.length; i++) {
            var wg = werkgebieden[i];
            var dist = haversineDistance(wg.lat, wg.lon, lat, lon);
            if (dist < minDist) {
                minDist = dist;
                nearest = wg;
            }
        }
        return { area: nearest, distance: Math.round(minDist) };
    }

    /** Simple debounce */
    function debounce(fn, delay) {
        var timer;
        return function() {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() { fn.apply(ctx, args); }, delay);
        };
    }

    // ──────────────────────────────────────────────
    // Price Calculation
    // ──────────────────────────────────────────────

    function calcServicePrice(service, quantity, selectedSubOptions) {
        if (service.requires_quote) return null;

        var subtotal = 0;

        // Volume pricing (staffelprijzen) – GESTAPELD / CUMULATIEF
        // Elke schijf rekent zijn eigen prijs, net als belastingschijven.
        // Voorbeeld: tier1 = 1-4 à €3.50, tier2 = 5-10 à €1.50
        //   → 6 stuks = (4 × €3.50) + (2 × €1.50) = €17.00
        if (service.volume_tiers && service.volume_tiers.length > 0) {
            var remaining = quantity;
            for (var i = 0; i < service.volume_tiers.length; i++) {
                if (remaining <= 0) break;
                var tier = service.volume_tiers[i];
                var tierMin = parseFloat(tier.min);
                var tierMax = parseFloat(tier.max);
                var tierPrice = parseFloat(tier.price);
                var tierSize = tierMax - tierMin + 1; // hoeveel passen in deze schijf
                var inThisTier = Math.min(remaining, tierSize);
                subtotal += inThisTier * tierPrice;
                remaining -= inThisTier;
            }
            // Als er nog over zijn boven alle tiers, reken tegen laatste tier prijs
            if (remaining > 0) {
                var lastTierPrice = parseFloat(service.volume_tiers[service.volume_tiers.length - 1].price);
                subtotal += remaining * lastTierPrice;
            }
        } else {
            // Geen staffel, gewoon base_price × quantity
            subtotal = service.base_price * quantity;
        }

        if (service.minimum_price > 0 && subtotal < service.minimum_price) {
            subtotal = service.minimum_price;
        }

        // Sub-option surcharges
        if (selectedSubOptions && service.sub_options) {
            service.sub_options.forEach(function(opt, i) {
                var sel = selectedSubOptions[i];
                if (!sel) return;
                if (opt.type === 'checkbox' && sel.checked && opt.surcharge > 0) {
                    subtotal += opt.surcharge * quantity;
                } else if (opt.type === 'select' && opt.surcharges && sel.value !== undefined) {
                    var idx = parseInt(sel.value, 10);
                    if (opt.surcharges && opt.surcharges[idx] > 0) {
                        subtotal += opt.surcharges[idx] * quantity;
                    }
                }
            });
        }

        // Discount
        if (service.discount > 0) {
            subtotal -= subtotal * (service.discount / 100);
        }
        return subtotal;
    }

    /** Determine current volume tier index for a given quantity */
    function currentTierIndex(service, quantity) {
        if (!service.volume_tiers || service.volume_tiers.length === 0) return -1;
        for (var i = 0; i < service.volume_tiers.length; i++) {
            var t = service.volume_tiers[i];
            if (quantity >= parseFloat(t.min) && quantity <= parseFloat(t.max)) return i;
        }
        // Above all tiers → last
        if (quantity > parseFloat(service.volume_tiers[service.volume_tiers.length - 1].max)) {
            return service.volume_tiers.length - 1;
        }
        return 0;
    }

    // ──────────────────────────────────────────────
    // Service Rendering (template-based)
    // ──────────────────────────────────────────────

    function renderServices(services) {
        var container = document.getElementById('cmcalcServices');
        if (!container) return;

        var tpl = document.getElementById('cmcalcServiceCardTpl');
        container.innerHTML = '';

        services.forEach(function(service, index) {
            var isQuote = !!service.requires_quote || isZakelijk;
            var unitLabel = service.unit_label || unitLabels[service.price_unit] || service.price_unit || '';

            // Clone template
            var card;
            if (tpl) {
                card = tpl.content.cloneNode(true).firstElementChild;
            } else {
                card = buildFallbackCard();
            }

            card.setAttribute('data-service-id', service.id || '');
            card.setAttribute('data-index', index);

            // Name
            var nameEl = card.querySelector('.cmcalc-service__name');
            if (nameEl) nameEl.textContent = service.title;

            // Price info
            var priceEl = card.querySelector('.cmcalc-service__price');
            var unitEl  = card.querySelector('.cmcalc-service__unit');
            if (service.requires_quote || isZakelijk) {
                if (priceEl) priceEl.textContent = 'Op maat';
                if (unitEl)  unitEl.textContent  = '';
            } else {
                if (priceEl) priceEl.textContent = formatPrice(service.base_price);
                if (unitEl)  unitEl.textContent  = 'per ' + unitLabel;
            }

            // Offerte badge
            var badge = card.querySelector('.cmcalc-service__badge--offerte');
            if (badge) {
                badge.style.display = (service.requires_quote || isZakelijk) ? '' : 'none';
            }

            // Quantity controls: hide for quote services
            var qtyWrap = card.querySelector('.cmcalc-service__qty');
            if (qtyWrap && (service.requires_quote || isZakelijk)) {
                qtyWrap.remove();
            }

            // Set default quantity
            if (!service.requires_quote && !isZakelijk) {
                var qtyInput = card.querySelector('.cmcalc-service__qty-input');
                if (qtyInput) {
                    var defaultQty = service.price_unit === 'vast' ? 1 : (service.default_quantity || 1);
                    qtyInput.value = defaultQty;
                }
            }

            // Volume tiers - build tier display
            if (!service.requires_quote && !isZakelijk && service.volume_tiers && service.volume_tiers.length > 0) {
                var tiersWrap = card.querySelector('.cmcalc-service__tiers');
                if (tiersWrap) {
                    buildTierDisplay(tiersWrap, service, 1);
                    tiersWrap.style.display = '';
                }
            }

            // Sub-options: show "Opties bewerken" link (popup-based, not inline)
            if (service.sub_options && service.sub_options.length > 0 && !service.requires_quote && !isZakelijk) {
                var editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'cmcalc-service__edit-options';
                editBtn.style.display = 'none'; // shown when selected
                editBtn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Opties bewerken';
                var bodyEl = card.querySelector('.cmcalc-service__body');
                if (bodyEl) bodyEl.appendChild(editBtn);

                // Add a badge showing sub-option count
                var optBadge = document.createElement('span');
                optBadge.className = 'cmcalc-service__badge cmcalc-service__badge--options';
                optBadge.textContent = service.sub_options.length + ' opties';
                var topEl = card.querySelector('.cmcalc-service__top');
                if (topEl) topEl.appendChild(optBadge);
            }

            // Minimum price note
            if (!service.requires_quote && !isZakelijk && service.minimum_price > 0) {
                var minNote = document.createElement('div');
                minNote.className = 'cmcalc-service__min-note';
                minNote.textContent = 'Min. ' + formatPrice(service.minimum_price);
                var infoEl = card.querySelector('.cmcalc-service__meta');
                if (infoEl) infoEl.appendChild(minNote);
            }

            // Discount note
            if (!service.requires_quote && !isZakelijk && service.discount > 0) {
                var discNote = document.createElement('span');
                discNote.className = 'cmcalc-service__discount-badge';
                discNote.textContent = '-' + service.discount + '%';
                var header = card.querySelector('.cmcalc-service__top');
                if (header) header.appendChild(discNote);
            }

            // Line total display
            if (!service.requires_quote && !isZakelijk) {
                var lineTotal = document.createElement('div');
                lineTotal.className = 'cmcalc-service__line-total';
                lineTotal.style.display = 'none';
                card.appendChild(lineTotal);
            }

            container.appendChild(card);
        });
    }

    /** Build sub-options HTML for a service */
    function buildSubOptionsHtml(service, index) {
        if (!service.sub_options || service.sub_options.length === 0) return '';
        var html = '';
        service.sub_options.forEach(function(opt, i) {
            if (opt.type === 'checkbox') {
                var surchargeText = opt.surcharge > 0 ? ' <span class="cmcalc-service__sub-surcharge">+' + formatPrice(opt.surcharge) + '</span>' : '';
                html += '<label class="cmcalc-service__sub-option">' +
                    '<input type="checkbox" data-sub-index="' + index + '-' + i + '" class="cmcalc-service__sub-input">' +
                    ' ' + (opt.label || '') + surchargeText +
                '</label>';
            } else if (opt.type === 'select' && opt.options) {
                html += '<div class="cmcalc-service__sub-option">' +
                    '<label class="cmcalc-service__sub-label">' + (opt.label || '') + '</label>' +
                    '<select data-sub-index="' + index + '-' + i + '" class="cmcalc-service__sub-input cmcalc-service__sub-select">';
                opt.options.forEach(function(o, j) {
                    var surcharge = opt.surcharges && opt.surcharges[j] > 0 ? ' (+' + formatPrice(opt.surcharges[j]) + ')' : '';
                    html += '<option value="' + j + '">' + o + surcharge + '</option>';
                });
                html += '</select></div>';
            }
        });
        return html;
    }

    /** Build and populate tier display inside the tiers wrapper */
    function buildTierDisplay(tiersWrap, service, quantity) {
        if (!service.volume_tiers || service.volume_tiers.length === 0) return;

        var html = '<div class="cmcalc-tier-grid">';

        service.volume_tiers.forEach(function(tier, i) {
            var tierPrice = parseFloat(tier.price);
            var tierMin = parseFloat(tier.min);
            var tierMax = parseFloat(tier.max);
            var baseTierPrice = parseFloat(service.volume_tiers[0].price);
            var savings = baseTierPrice > 0 ? Math.round((1 - tierPrice / baseTierPrice) * 100) : 0;

            // Hoeveel stuks vallen in deze schijf bij de huidige quantity?
            var inThisTier = 0;
            var tierSize = tierMax - tierMin + 1;
            var cumBefore = 0;
            for (var j = 0; j < i; j++) {
                cumBefore += parseFloat(service.volume_tiers[j].max) - parseFloat(service.volume_tiers[j].min) + 1;
            }
            if (quantity > cumBefore) {
                inThisTier = Math.min(quantity - cumBefore, tierSize);
            }
            var used = inThisTier > 0;

            html += '<div class="cmcalc-tier-item' + (used ? ' is-used' : '') + '">';
            html += '<span class="cmcalc-tier-range">' + tierMin + '\u2013' + tierMax + ' st</span>';
            html += '<span class="cmcalc-tier-price">' + formatPrice(tierPrice) + '/st</span>';
            if (savings > 0 && i > 0) {
                html += '<span class="cmcalc-tier-savings">-' + savings + '%</span>';
            }
            if (used) {
                html += '<span class="cmcalc-tier-used">' + inThisTier + '\u00D7</span>';
            }
            html += '</div>';
        });

        html += '</div>';

        // Toon de opbouw als er meerdere tiers actief zijn
        if (quantity > 0) {
            var breakdown = [];
            var remaining = quantity;
            for (var k = 0; k < service.volume_tiers.length; k++) {
                if (remaining <= 0) break;
                var t = service.volume_tiers[k];
                var tMin = parseFloat(t.min);
                var tMax = parseFloat(t.max);
                var tPrice = parseFloat(t.price);
                var tSize = tMax - tMin + 1;
                var count = Math.min(remaining, tSize);
                breakdown.push(count + ' \u00D7 ' + formatPrice(tPrice));
                remaining -= count;
            }
            if (remaining > 0) {
                var lastP = parseFloat(service.volume_tiers[service.volume_tiers.length - 1].price);
                breakdown.push(remaining + ' \u00D7 ' + formatPrice(lastP));
            }
            if (breakdown.length > 1) {
                html += '<div class="cmcalc-tier-breakdown">' + breakdown.join(' + ') + '</div>';
            }
        }

        tiersWrap.innerHTML = html;
    }

    /** Update tier display when quantity changes */
    function updateTierDisplay(card, service, quantity) {
        var tiersWrap = card.querySelector('.cmcalc-service__tiers');
        if (!tiersWrap || !service.volume_tiers || service.volume_tiers.length === 0) return;
        buildTierDisplay(tiersWrap, service, quantity);
    }

    /** Fallback if template is missing */
    function buildFallbackCard() {
        var div = document.createElement('div');
        div.className = 'cmcalc-service';
        div.innerHTML =
            '<label class="cmcalc-service__check">' +
                '<input type="checkbox" class="cmcalc-service__checkbox">' +
                '<span class="cmcalc-service__tick"></span>' +
            '</label>' +
            '<div class="cmcalc-service__body">' +
                '<div class="cmcalc-service__top">' +
                    '<span class="cmcalc-service__name"></span>' +
                    '<span class="cmcalc-service__badge cmcalc-service__badge--offerte" style="display:none;">Offerte</span>' +
                '</div>' +
                '<div class="cmcalc-service__meta">' +
                    '<span class="cmcalc-service__price"></span>' +
                    '<span class="cmcalc-service__unit"></span>' +
                '</div>' +
                '<div class="cmcalc-service__tiers" style="display:none;"></div>' +
            '</div>' +
            '<div class="cmcalc-service__qty" style="display:none;">' +
                '<button type="button" class="cmcalc-service__qty-btn cmcalc-service__qty-btn--minus" aria-label="Minder">&minus;</button>' +
                '<input type="number" class="cmcalc-service__qty-input" value="1" min="1" step="1" aria-label="Aantal">' +
                '<button type="button" class="cmcalc-service__qty-btn cmcalc-service__qty-btn--plus" aria-label="Meer">+</button>' +
            '</div>';
        return div;
    }

    // ──────────────────────────────────────────────
    // Service interaction
    // ──────────────────────────────────────────────

    /** Handle service checkbox toggle */
    function onServiceToggle(checkbox) {
        var card    = checkbox.closest('.cmcalc-service');
        if (!card) return;
        var index   = parseInt(card.getAttribute('data-index'), 10);
        var service = allServices[index];
        if (!service) return;

        var isChecked = checkbox.checked;

        // If checking ON and service has sub-options → show questionnaire popup
        if (isChecked && !service.requires_quote && !isZakelijk &&
            service.sub_options && service.sub_options.length > 0) {
            // Don't select yet — wait for questionnaire confirmation
            openQuestionnaire(index);
            return;
        }

        applyServiceSelection(card, index, isChecked);
    }

    /** Apply the visual selection state to a service card */
    function applyServiceSelection(card, index, isChecked) {
        var qtyWrap   = card.querySelector('.cmcalc-service__qty');
        var lineTotal = card.querySelector('.cmcalc-service__line-total');
        var editBtn   = card.querySelector('.cmcalc-service__edit-options');

        if (isChecked) {
            card.classList.add('is-selected');
            if (qtyWrap) qtyWrap.style.display = '';
            if (lineTotal) lineTotal.style.display = '';
            // Show "Opties bewerken" link if has sub-options
            if (editBtn) editBtn.style.display = '';
            // Show summary of selected options
            updateSubOptionsSummary(card, index);
        } else {
            card.classList.remove('is-selected');
            if (qtyWrap) qtyWrap.style.display = 'none';
            if (lineTotal) lineTotal.style.display = 'none';
            if (editBtn) editBtn.style.display = 'none';
            // Clear sub-option selections
            delete subOptionSelections[index];
            // Hide summary
            var summary = card.querySelector('.cmcalc-service__options-summary');
            if (summary) summary.style.display = 'none';
        }

        updateRunningTotal();
    }

    /** Show a summary of selected sub-options on the service card */
    function updateSubOptionsSummary(card, index) {
        var service = allServices[index];
        var selections = subOptionSelections[index];
        if (!service || !service.sub_options || !selections) return;

        var summary = card.querySelector('.cmcalc-service__options-summary');
        if (!summary) {
            summary = document.createElement('div');
            summary.className = 'cmcalc-service__options-summary';
            var body = card.querySelector('.cmcalc-service__body');
            if (body) body.appendChild(summary);
        }

        var parts = [];
        service.sub_options.forEach(function(opt, i) {
            var sel = selections[i];
            if (!sel) return;
            if (opt.type === 'checkbox' && sel.checked) {
                parts.push(opt.label);
            } else if (opt.type === 'select' && sel.label) {
                parts.push(opt.label + ': ' + sel.label);
            }
        });

        if (parts.length > 0) {
            summary.innerHTML = parts.map(function(p) {
                return '<span class="cmcalc-service__option-tag">' + p + '</span>';
            }).join('');
            summary.style.display = '';
        } else {
            summary.style.display = 'none';
        }
    }

    // ──────────────────────────────────────────────
    // Questionnaire Popup
    // ──────────────────────────────────────────────

    function openQuestionnaire(serviceIndex) {
        var service = allServices[serviceIndex];
        if (!service || !service.sub_options) return;

        pendingQServiceIndex = serviceIndex;

        var modal = document.getElementById('cmcalcQuestionnaire');
        var title = document.getElementById('cmcalcQTitle');
        var body  = document.getElementById('cmcalcQBody');

        if (title) title.textContent = service.title;

        // Build questionnaire form
        var html = '';
        service.sub_options.forEach(function(opt, i) {
            html += '<div class="cmcalc-q-item">';
            html += '<label class="cmcalc-q-item__label">' + (opt.label || '') + '</label>';

            if (opt.type === 'checkbox') {
                var surchargeText = opt.surcharge > 0 ? ' (+' + formatPrice(opt.surcharge) + ')' : '';
                // Pre-check if previously selected
                var wasChecked = subOptionSelections[serviceIndex] && subOptionSelections[serviceIndex][i] && subOptionSelections[serviceIndex][i].checked;
                html += '<label class="cmcalc-q-item__checkbox">' +
                    '<input type="checkbox" data-q-index="' + i + '"' + (wasChecked ? ' checked' : '') + '>' +
                    '<span class="cmcalc-q-item__checkmark"></span>' +
                    '<span>Ja' + surchargeText + '</span>' +
                '</label>';
            } else if (opt.type === 'select' && opt.options) {
                var prevValue = subOptionSelections[serviceIndex] && subOptionSelections[serviceIndex][i] ? subOptionSelections[serviceIndex][i].value : '0';
                html += '<div class="cmcalc-q-item__select-wrap">';
                opt.options.forEach(function(o, j) {
                    var surcharge = opt.surcharges && opt.surcharges[j] > 0 ? ' (+' + formatPrice(opt.surcharges[j]) + ')' : '';
                    var isSelected = (String(j) === String(prevValue));
                    html += '<label class="cmcalc-q-item__radio">' +
                        '<input type="radio" name="cmcalc_q_' + i + '" value="' + j + '"' + (isSelected ? ' checked' : '') + ' data-q-index="' + i + '">' +
                        '<span class="cmcalc-q-item__radiomark"></span>' +
                        '<span>' + o + surcharge + '</span>' +
                    '</label>';
                });
                html += '</div>';
            }

            html += '</div>';
        });

        if (body) body.innerHTML = html;
        if (modal) modal.style.display = '';

        // Animate in
        requestAnimationFrame(function() {
            if (modal) modal.classList.add('is-open');
        });
    }

    function closeQuestionnaire(confirmed) {
        var modal = document.getElementById('cmcalcQuestionnaire');
        if (modal) {
            modal.classList.remove('is-open');
            setTimeout(function() { modal.style.display = 'none'; }, 250);
        }

        var index = pendingQServiceIndex;
        pendingQServiceIndex = -1;
        if (index < 0) return;

        var card = calculator.querySelector('.cmcalc-service[data-index="' + index + '"]');
        var checkbox = card ? card.querySelector('.cmcalc-service__checkbox') : null;

        if (confirmed) {
            // Collect answers from the questionnaire
            var service = allServices[index];
            var body = document.getElementById('cmcalcQBody');
            var selections = [];

            if (service && service.sub_options && body) {
                service.sub_options.forEach(function(opt, i) {
                    if (opt.type === 'checkbox') {
                        var cb = body.querySelector('[data-q-index="' + i + '"]');
                        selections.push({
                            checked: cb ? cb.checked : false,
                            label: opt.label
                        });
                    } else if (opt.type === 'select') {
                        var radio = body.querySelector('input[name="cmcalc_q_' + i + '"]:checked');
                        var val = radio ? radio.value : '0';
                        selections.push({
                            value: val,
                            label: opt.options ? opt.options[parseInt(val, 10)] : ''
                        });
                    }
                });
            }

            subOptionSelections[index] = selections;
            if (checkbox) checkbox.checked = true;
            if (card) applyServiceSelection(card, index, true);
        } else {
            // Cancelled — uncheck the service
            if (checkbox) checkbox.checked = false;
            if (card) applyServiceSelection(card, index, false);
        }
    }

    /** Get selected sub-options for a service by index (reads from subOptionSelections map) */
    function getSubOptionsForService(index) {
        var service = allServices[index];
        if (!service || !service.sub_options || service.sub_options.length === 0) return null;
        return subOptionSelections[index] || null;
    }

    /** Collect all selected services with their quantities and sub-options */
    function getSelectedServices() {
        var selected = [];
        var cards = calculator.querySelectorAll('.cmcalc-service');
        for (var i = 0; i < cards.length; i++) {
            var cb = cards[i].querySelector('.cmcalc-service__checkbox');
            if (!cb || !cb.checked) continue;
            var idx     = parseInt(cards[i].getAttribute('data-index'), 10);
            var service = allServices[idx];
            if (!service) continue;

            var qtyInput = cards[i].querySelector('.cmcalc-service__qty-input');
            var quantity = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;
            var subOpts  = getSubOptionsForService(idx);

            selected.push({
                index:            idx,
                id:               service.id,
                title:            service.title,
                base_price:       service.base_price,
                price_unit:       service.price_unit,
                unit_label:       service.unit_label || unitLabels[service.price_unit] || service.price_unit,
                minimum_price:    service.minimum_price,
                discount:         service.discount,
                requires_quote:   service.requires_quote || isZakelijk,
                volume_tiers:     service.volume_tiers,
                sub_options:      service.sub_options || [],
                quantity:         quantity,
                selectedSubOptions: subOpts
            });
        }
        return selected;
    }

    // ──────────────────────────────────────────────
    // Running Total (Step 1)
    // ──────────────────────────────────────────────

    function updateRunningTotal() {
        var selected  = getSelectedServices();
        var total     = 0;
        var hasQuote  = false;
        var hasPrice  = false;

        selected.forEach(function(s) {
            if (s.requires_quote) {
                hasQuote = true;
                return;
            }
            var price = calcServicePrice(s, s.quantity, s.selectedSubOptions);
            if (price !== null) {
                total += price;
                hasPrice = true;
            }
        });

        // Update line totals on individual cards
        var cards = calculator.querySelectorAll('.cmcalc-service');
        for (var i = 0; i < cards.length; i++) {
            var cb = cards[i].querySelector('.cmcalc-service__checkbox');
            if (!cb || !cb.checked) continue;
            var idx = parseInt(cards[i].getAttribute('data-index'), 10);
            var svc = allServices[idx];
            if (!svc || svc.requires_quote || isZakelijk) continue;

            var qtyInput  = cards[i].querySelector('.cmcalc-service__qty-input');
            var qty       = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;
            var subOpts   = getSubOptionsForService(idx);
            var linePrice = calcServicePrice(svc, qty, subOpts);

            var lineTotalEl = cards[i].querySelector('.cmcalc-service__line-total');
            if (lineTotalEl) {
                lineTotalEl.textContent = linePrice !== null ? formatPrice(linePrice) : '';
            }

            // Update tier display
            updateTierDisplay(cards[i], svc, qty);
        }

        // Running total bar
        var runningTotal      = document.getElementById('cmcalcRunningTotal');
        var runningTotalPrice = document.getElementById('cmcalcRunningTotalPrice');
        var overviewBtn       = document.getElementById('cmcalcToOverview');

        if (selected.length > 0) {
            if (runningTotal)      runningTotal.style.display = '';
            if (runningTotalPrice) runningTotalPrice.textContent = hasPrice ? formatPrice(total) : 'Offerte op maat';
            if (overviewBtn)       overviewBtn.disabled = false;
        } else {
            if (runningTotal)      runningTotal.style.display = 'none';
            if (overviewBtn)       overviewBtn.disabled = true;
        }
    }

    // ──────────────────────────────────────────────
    // Event Delegation (Step 1 service interactions)
    // ──────────────────────────────────────────────
    var debouncedUpdateTotal = debounce(updateRunningTotal, 300);

    calculator.addEventListener('change', function(e) {
        var target = e.target;

        // Service checkbox
        if (target.classList.contains('cmcalc-service__checkbox')) {
            onServiceToggle(target);
            return;
        }

        // Sub-option input
        if (target.classList.contains('cmcalc-service__sub-input')) {
            updateRunningTotal();
            return;
        }

        // Zakelijk toggle
        if (target.id === 'cmcalcZakelijkToggle') {
            isZakelijk = target.checked;
            // Update toggle labels
            var partLabel = document.getElementById('cmcalcPartLabel');
            var zakLabel = document.getElementById('cmcalcZakLabel');
            if (partLabel) partLabel.classList.toggle('cmcalc-customer-toggle__label--active', !isZakelijk);
            if (zakLabel) zakLabel.classList.toggle('cmcalc-customer-toggle__label--active', isZakelijk);
            // Re-render services
            renderServices(allServices);
            updateRunningTotal();
            // Update BTW label
            updateBtwLabel();
            return;
        }
    });

    calculator.addEventListener('input', function(e) {
        var target = e.target;

        // Quantity input (debounced)
        if (target.classList.contains('cmcalc-service__qty-input')) {
            debouncedUpdateTotal();
            return;
        }

        // Postcode / house number
        if (target.id === 'cmcalcPostcode' || target.id === 'cmcalcHouseNumber') {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(calculateDistance, 500);
        }
    });

    calculator.addEventListener('click', function(e) {
        var target = e.target;

        // Plus button
        var plusBtn = target.closest('.cmcalc-service__qty-btn--plus');
        if (plusBtn) {
            var card = plusBtn.closest('.cmcalc-service');
            var input = card ? card.querySelector('.cmcalc-service__qty-input') : null;
            if (input) {
                input.value = (parseInt(input.value, 10) || 0) + 1;
                updateRunningTotal();
            }
            return;
        }

        // Minus button
        var minusBtn = target.closest('.cmcalc-service__qty-btn--minus');
        if (minusBtn) {
            var card2 = minusBtn.closest('.cmcalc-service');
            var input2 = card2 ? card2.querySelector('.cmcalc-service__qty-input') : null;
            if (input2) {
                var val = (parseInt(input2.value, 10) || 1) - 1;
                if (val < 1) val = 1;
                input2.value = val;
                updateRunningTotal();
            }
            return;
        }

        // Overview button (step 1 → 2)
        if (target.closest('#cmcalcToOverview')) {
            buildOverview();
            goToStep(2);
            return;
        }

        // data-goto navigation buttons (Vorige / Gegevens invullen)
        var gotoBtn = target.closest('[data-goto]');
        if (gotoBtn) {
            var gotoStep = parseInt(gotoBtn.getAttribute('data-goto'), 10);
            if (!isNaN(gotoStep)) {
                if (gotoStep === 2) buildOverview();
                goToStep(gotoStep);
            }
            return;
        }

        // Submit button
        if (target.closest('#cmcalcSubmit')) {
            submitBooking();
            return;
        }

        // Restart button
        if (target.closest('#cmcalcRestart')) {
            restartCalculator();
            return;
        }

        // Questionnaire confirm
        if (target.closest('.cmcalc-questionnaire__confirm')) {
            closeQuestionnaire(true);
            return;
        }

        // Questionnaire cancel / close / overlay
        if (target.closest('.cmcalc-questionnaire__cancel') || target.closest('.cmcalc-questionnaire__close')) {
            closeQuestionnaire(false);
            return;
        }
        if (target.classList.contains('cmcalc-questionnaire__overlay')) {
            closeQuestionnaire(false);
            return;
        }

        // Edit options button (re-open questionnaire for already-selected service)
        if (target.closest('.cmcalc-service__edit-options')) {
            var editCard = target.closest('.cmcalc-service');
            if (editCard) {
                var editIndex = parseInt(editCard.getAttribute('data-index'), 10);
                openQuestionnaire(editIndex);
            }
            return;
        }

        // Service card click (toggle selection by clicking anywhere on the card)
        var serviceCard = target.closest('.cmcalc-service');
        if (serviceCard && !target.closest('.cmcalc-service__qty') && !target.closest('.cmcalc-service__sub-options') && !target.closest('.cmcalc-service__check')) {
            var cb = serviceCard.querySelector('.cmcalc-service__checkbox');
            if (cb) {
                cb.checked = !cb.checked;
                onServiceToggle(cb);
            }
        }
    });

    // ──────────────────────────────────────────────
    // Distance Calculation
    // ──────────────────────────────────────────────

    function calculateDistance() {
        var postcodeEl    = document.getElementById('cmcalcPostcode');
        var houseNumberEl = document.getElementById('cmcalcHouseNumber');
        var distanceWrap  = document.getElementById('cmcalcDistance');
        var kmDistanceEl  = document.getElementById('cmcalcKmDistance');
        var kmSurchargeEl = document.getElementById('cmcalcKmSurcharge');
        var cityEl        = document.getElementById('cmcalcCity');
        var nearestAreaEl = document.getElementById('cmcalcNearestArea');

        if (!postcodeEl) return;

        var postcode    = postcodeEl.value.trim().replace(/\s/g, '');
        var houseNumber = houseNumberEl ? houseNumberEl.value.trim() : '';

        if (postcode.length < 4) {
            if (distanceWrap) distanceWrap.style.display = 'none';
            if (cityEl) cityEl.value = '';
            currentDistance = 0;
            currentTravelSurcharge = 0;
            currentNearestArea = null;
            buildOverview();
            return;
        }

        var query = postcode;
        if (houseNumber) query += ' ' + houseNumber;

        fetch('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?q=' + encodeURIComponent(query) + '&rows=1&fq=type:adres')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.response || !data.response.docs || data.response.docs.length === 0) {
                    return fetch('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free?q=' + encodeURIComponent(postcode) + '&rows=1')
                        .then(function(r) { return r.json(); });
                }
                return data;
            })
            .then(function(data) {
                if (!data.response || !data.response.docs || data.response.docs.length === 0) {
                    if (distanceWrap) distanceWrap.style.display = 'none';
                    currentDistance = 0;
                    currentTravelSurcharge = 0;
                    currentNearestArea = null;
                    buildOverview();
                    return;
                }

                var doc = data.response.docs[0];
                var centroide = doc.centroide_ll;

                if (cityEl && doc.woonplaatsnaam) {
                    cityEl.value = doc.woonplaatsnaam;
                }

                if (!centroide) return;

                var match = centroide.match(/POINT\(([^ ]+) ([^)]+)\)/);
                if (!match) return;

                var lon = parseFloat(match[1]);
                var lat = parseFloat(match[2]);

                // Find nearest werkgebied
                var result = findNearestWerkgebied(lat, lon);
                currentNearestArea = result.area;
                currentDistance = result.distance;

                if (kmDistanceEl) kmDistanceEl.textContent = currentDistance;
                if (nearestAreaEl && currentNearestArea) {
                    nearestAreaEl.textContent = currentNearestArea.name;
                }

                // Calculate travel surcharge
                currentTravelSurcharge = 0;
                if (travelService && currentNearestArea && currentDistance > currentNearestArea.free_km) {
                    currentTravelSurcharge = (currentDistance - currentNearestArea.free_km) * travelService.base_price;
                }

                if (kmSurchargeEl) {
                    if (currentTravelSurcharge > 0) {
                        kmSurchargeEl.textContent = '+ ' + formatPrice(currentTravelSurcharge);
                        kmSurchargeEl.className = 'cmcalc-distance__surcharge has-surcharge';
                    } else {
                        kmSurchargeEl.textContent = 'Geen toeslag';
                        kmSurchargeEl.className = 'cmcalc-distance__surcharge no-surcharge';
                    }
                }

                if (distanceWrap) distanceWrap.style.display = '';
                buildOverview();
            })
            .catch(function() {
                if (distanceWrap) distanceWrap.style.display = 'none';
                currentDistance = 0;
                currentTravelSurcharge = 0;
                currentNearestArea = null;
            });
    }

    // ──────────────────────────────────────────────
    // BTW Calculation helpers
    // ──────────────────────────────────────────────

    /**
     * Calculate BTW amounts based on mode:
     * - 'incl': prices include BTW, back-calculate the BTW portion
     * - 'excl': prices exclude BTW, add on top
     */
    function calculateBtw(servicesSubtotal, travelSurcharge) {
        var totalBeforeBtw = servicesSubtotal + travelSurcharge;

        if (btwMode === 'incl') {
            // Prices already include BTW
            // BTW = total - (total / (1 + percentage/100))
            var btwAmount = totalBeforeBtw - (totalBeforeBtw / (1 + btwPercentage / 100));
            var exclBtw = totalBeforeBtw - btwAmount;
            return {
                servicesExcl: servicesSubtotal - (servicesSubtotal - servicesSubtotal / (1 + btwPercentage / 100)),
                subtotalExcl: exclBtw,
                btwAmount: btwAmount,
                grandTotal: totalBeforeBtw // total stays the same, BTW is already in
            };
        } else {
            // Prices exclude BTW, add on top
            var btwAmount2 = totalBeforeBtw * (btwPercentage / 100);
            return {
                servicesExcl: servicesSubtotal,
                subtotalExcl: totalBeforeBtw,
                btwAmount: btwAmount2,
                grandTotal: totalBeforeBtw + btwAmount2
            };
        }
    }

    function updateBtwLabel() {
        var btwLabelEl = calculator.querySelector('#cmcalcBtwLabel');
        if (btwLabelEl) {
            if (btwMode === 'incl') {
                btwLabelEl.textContent = 'Waarvan BTW (' + btwPercentage + '%)';
            } else {
                btwLabelEl.textContent = 'BTW (' + btwPercentage + '%)';
            }
        }

        var totalLabelEl = calculator.querySelector('#cmcalcTotalLabel');
        if (totalLabelEl) {
            if (btwMode === 'incl') {
                totalLabelEl.textContent = 'Totaal incl. BTW';
            } else {
                totalLabelEl.textContent = 'Totaal incl. BTW';
            }
        }
    }

    // ──────────────────────────────────────────────
    // Step 2: Overview
    // ──────────────────────────────────────────────

    function buildOverview() {
        var selected      = getSelectedServices();
        var overviewEl    = document.getElementById('cmcalcOverview');
        var subtotalEl    = document.getElementById('cmcalcSubtotal');
        var surchargeRow  = document.getElementById('cmcalcSurchargeRow');
        var surchargeEl   = document.getElementById('cmcalcTravelSurcharge');
        var btwEl         = document.getElementById('cmcalcBtw');
        var totalInclEl   = document.getElementById('cmcalcTotalInclBtw');
        var tpl           = document.getElementById('cmcalcOverviewRowTpl');

        if (!overviewEl) return;
        overviewEl.innerHTML = '';

        var servicesSubtotal = 0;
        var hasQuoteItems = false;

        selected.forEach(function(s) {
            var row;
            if (tpl) {
                row = tpl.content.cloneNode(true).firstElementChild;
            } else {
                row = document.createElement('div');
                row.className = 'cmcalc-overview__row';
                row.innerHTML =
                    '<div class="cmcalc-overview__item-info">' +
                        '<span class="cmcalc-overview__item-name"></span>' +
                        '<span class="cmcalc-overview__item-detail"></span>' +
                    '</div>' +
                    '<span class="cmcalc-overview__item-price"></span>';
            }

            var nameEl   = row.querySelector('.cmcalc-overview__item-name');
            var detailEl = row.querySelector('.cmcalc-overview__item-detail');
            var priceEl  = row.querySelector('.cmcalc-overview__item-price');

            if (nameEl) nameEl.textContent = s.title;

            if (s.requires_quote) {
                hasQuoteItems = true;
                if (detailEl) detailEl.textContent = '';
                if (priceEl)  priceEl.textContent  = 'Offerte op maat';
                row.classList.add('cmcalc-overview__item--quote');
            } else {
                var lineTotal = calcServicePrice(s, s.quantity, s.selectedSubOptions);
                servicesSubtotal += lineTotal || 0;
                if (detailEl) detailEl.textContent = s.quantity + ' ' + s.unit_label;
                if (priceEl)  priceEl.textContent  = formatPrice(lineTotal);

                // Sub-option details
                if (s.selectedSubOptions && s.sub_options) {
                    s.sub_options.forEach(function(opt, i) {
                        var sel = s.selectedSubOptions[i];
                        if (!sel) return;
                        var label = '';
                        if (opt.type === 'checkbox' && sel.checked) {
                            label = opt.label;
                            if (opt.surcharge > 0) label += ' (+' + formatPrice(opt.surcharge) + '/st)';
                        } else if (opt.type === 'select' && sel.label) {
                            label = opt.label + ': ' + sel.label;
                        }
                        if (label && detailEl) {
                            var subDetail = document.createElement('span');
                            subDetail.className = 'cmcalc-overview__item-sub';
                            subDetail.textContent = '\u2192 ' + label;
                            detailEl.appendChild(document.createElement('br'));
                            detailEl.appendChild(subDetail);
                        }
                    });
                }
            }

            overviewEl.appendChild(row);
        });

        // Travel surcharge row
        var travelAmount = 0;
        if (surchargeRow) {
            if (currentTravelSurcharge > 0) {
                travelAmount = currentTravelSurcharge;
                surchargeRow.style.display = '';
                if (surchargeEl) surchargeEl.textContent = formatPrice(currentTravelSurcharge);
            } else {
                surchargeRow.style.display = 'none';
            }
        }

        // BTW calculation (correct!)
        var btwCalc = calculateBtw(servicesSubtotal, travelAmount);

        // Update subtotal display (services only, without travel)
        if (subtotalEl) subtotalEl.textContent = formatPrice(servicesSubtotal);

        // BTW row
        var btwRowEl = btwEl ? btwEl.closest('.cmcalc-totals-card__row') : null;
        if (btwEl) btwEl.textContent = formatPrice(btwCalc.btwAmount);

        // Grand total
        if (totalInclEl) totalInclEl.textContent = formatPrice(btwCalc.grandTotal);

        // Update BTW label
        updateBtwLabel();

        // Quote note
        var existingNote = overviewEl.querySelector('.cmcalc-overview__quote-note');
        if (existingNote) existingNote.remove();
        if (hasQuoteItems) {
            var note = document.createElement('div');
            note.className = 'cmcalc-overview__quote-note';
            note.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> ' +
                (isZakelijk
                    ? 'Als zakelijke klant ontvangt u een offerte op maat. Wij nemen contact met u op.'
                    : 'Voor diensten met "Offerte op maat" nemen wij apart contact met u op.');
            overviewEl.appendChild(note);
        }
    }

    // ──────────────────────────────────────────────
    // Step Navigation
    // ──────────────────────────────────────────────

    function goToStep(step) {
        if (step < 1) step = 1;
        if (step > 3) step = 3;
        currentStep = step;

        // Hide all steps
        var steps = calculator.querySelectorAll('.cmcalc-step');
        for (var i = 0; i < steps.length; i++) {
            steps[i].classList.remove('is-active');
        }

        // Show target step
        var target = calculator.querySelector('.cmcalc-step[data-step="' + step + '"]');
        if (target) {
            target.classList.add('is-active');
            // Animate in
            target.style.opacity = '0';
            target.style.transform = 'translateY(12px)';
            requestAnimationFrame(function() {
                target.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                target.style.opacity = '1';
                target.style.transform = 'translateY(0)';
            });
        }

        // Progress dots
        var dots = calculator.querySelectorAll('.cmcalc-progress__dot');
        for (var j = 0; j < dots.length; j++) {
            var dotStep = parseInt(dots[j].getAttribute('data-step'), 10);
            dots[j].classList.remove('is-active', 'is-completed');
            if (dotStep === step) {
                dots[j].classList.add('is-active');
            } else if (dotStep < step) {
                dots[j].classList.add('is-completed');
            }
        }

        // Progress track fill
        var progressFill = document.getElementById('cmcalcProgressBar');
        if (progressFill) {
            var percent = step === 1 ? 0 : step === 2 ? 50 : 100;
            progressFill.style.width = percent + '%';
        }

        // Build overview when navigating to step 2
        if (step === 2) {
            buildOverview();
        }

        // Scroll to top of calculator
        calculator.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ──────────────────────────────────────────────
    // Step 3: Booking Submission
    // ──────────────────────────────────────────────

    function submitBooking() {
        var nameEl    = document.getElementById('cmcalcBookingName');
        var emailEl   = document.getElementById('cmcalcBookingEmail');
        var phoneEl   = document.getElementById('cmcalcBookingPhone');
        var dateEl    = document.getElementById('cmcalcBookingDate');
        var messageEl = document.getElementById('cmcalcBookingMessage');
        var termsEl   = document.getElementById('cmcalcTerms');

        var postcodeEl    = document.getElementById('cmcalcPostcode');
        var houseNumberEl = document.getElementById('cmcalcHouseNumber');
        var cityEl        = document.getElementById('cmcalcCity');

        var name  = nameEl  ? nameEl.value.trim()  : '';
        var email = emailEl ? emailEl.value.trim()  : '';
        var phone = phoneEl ? phoneEl.value.trim()  : '';
        var date  = dateEl  ? dateEl.value           : '';
        var msg   = messageEl ? messageEl.value.trim() : '';

        var postcode    = postcodeEl    ? postcodeEl.value.trim()    : '';
        var houseNumber = houseNumberEl ? houseNumberEl.value.trim() : '';
        var city        = cityEl        ? cityEl.value.trim()        : '';
        var address     = (postcode + ' ' + houseNumber + ', ' + city).trim();

        // Validation
        if (!name || !email) {
            highlightField(nameEl, !name);
            highlightField(emailEl, !email);
            return;
        }

        // Simple email check
        if (email.indexOf('@') === -1 || email.indexOf('.') === -1) {
            highlightField(emailEl, true);
            return;
        }

        // Terms check
        if (termsEl && !termsEl.checked) {
            var termsLabel = termsEl.closest('.cmcalc-terms');
            if (termsLabel) {
                termsLabel.classList.add('is-error');
                setTimeout(function() { termsLabel.classList.remove('is-error'); }, 3000);
            }
            return;
        }

        // Show loading state
        var submitBtn = document.getElementById('cmcalcSubmit');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        // Build payload
        var selected   = getSelectedServices();
        var grandTotal = 0;
        selected.forEach(function(s) {
            if (!s.requires_quote) {
                grandTotal += calcServicePrice(s, s.quantity, s.selectedSubOptions) || 0;
            }
        });
        grandTotal += currentTravelSurcharge;

        var servicesPayload = selected.map(function(s) {
            var subLabels = [];
            if (s.selectedSubOptions && s.sub_options) {
                s.sub_options.forEach(function(opt, i) {
                    var sel = s.selectedSubOptions[i];
                    if (!sel) return;
                    if (opt.type === 'checkbox' && sel.checked) {
                        subLabels.push(opt.label + (opt.surcharge > 0 ? ' (+' + formatPrice(opt.surcharge) + ')' : ''));
                    } else if (opt.type === 'select' && sel.label) {
                        subLabels.push(opt.label + ': ' + sel.label);
                    }
                });
            }
            return {
                id:             s.id,
                title:          s.title,
                quantity:       s.quantity,
                unit:           s.price_unit,
                requires_quote: s.requires_quote,
                line_total:     s.requires_quote ? 0 : (calcServicePrice(s, s.quantity, s.selectedSubOptions) || 0),
                sub_options:    subLabels
            };
        });

        fetch(restUrl + 'submit-booking', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce':   nonce
            },
            body: JSON.stringify({
                name:               name,
                email:              email,
                phone:              phone,
                address:            address,
                postcode:           postcode,
                house_number:       houseNumber,
                city:               city,
                distance_km:        currentDistance,
                travel_surcharge:   currentTravelSurcharge,
                nearest_werkgebied: currentNearestArea ? currentNearestArea.name : '',
                services:           servicesPayload,
                total:              grandTotal,
                preferred_date:     date,
                message:            msg,
                is_zakelijk:        isZakelijk
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showSuccess();
            } else {
                showSubmitError(data.message || 'Er ging iets mis. Probeer het opnieuw.');
            }
        })
        .catch(function() {
            showSubmitError('Er ging iets mis. Probeer het later opnieuw.');
        });
    }

    function highlightField(el, isError) {
        if (!el) return;
        if (isError) {
            el.classList.add('is-error');
            el.addEventListener('input', function handler() {
                el.classList.remove('is-error');
                el.removeEventListener('input', handler);
            });
        } else {
            el.classList.remove('is-error');
        }
    }

    function showSubmitError(message) {
        showToast(message, 'error');
        resetSubmitButton();
    }

    function resetSubmitButton() {
        var submitBtn = document.getElementById('cmcalcSubmit');
        if (!submitBtn) return;
        submitBtn.disabled = false;
    }

    function showSuccess() {
        // Hide all steps
        var steps = calculator.querySelectorAll('.cmcalc-step');
        for (var i = 0; i < steps.length; i++) {
            steps[i].classList.remove('is-active');
        }

        // Show success step
        var success = calculator.querySelector('.cmcalc-step[data-step="success"]');
        if (success) {
            success.classList.add('is-active');
            success.style.opacity = '0';
            success.style.transform = 'translateY(12px)';
            requestAnimationFrame(function() {
                success.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                success.style.opacity = '1';
                success.style.transform = 'translateY(0)';
            });
        }

        // Hide progress
        var progressEl = calculator.querySelector('.cmcalc-progress');
        if (progressEl) progressEl.style.display = 'none';
    }

    function restartCalculator() {
        // Reset state
        currentStep = 1;
        currentDistance = 0;
        currentTravelSurcharge = 0;
        currentNearestArea = null;
        isZakelijk = false;

        // Reset zakelijk toggle
        var zakelijkToggle = document.getElementById('cmcalcZakelijkToggle');
        if (zakelijkToggle) zakelijkToggle.checked = false;

        // Uncheck all services
        var checkboxes = calculator.querySelectorAll('.cmcalc-service__checkbox');
        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                checkboxes[i].checked = false;
                onServiceToggle(checkboxes[i]);
            }
        }

        // Clear form fields
        var fields = ['cmcalcPostcode', 'cmcalcHouseNumber', 'cmcalcCity',
                      'cmcalcBookingName', 'cmcalcBookingEmail', 'cmcalcBookingPhone',
                      'cmcalcBookingDate', 'cmcalcBookingMessage'];
        fields.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.value = '';
        });

        var termsEl = document.getElementById('cmcalcTerms');
        if (termsEl) termsEl.checked = false;

        // Show progress
        var progressEl = calculator.querySelector('.cmcalc-progress');
        if (progressEl) progressEl.style.display = '';

        // Re-render services (back to particulier)
        renderServices(allServices);

        // Reset submit button
        resetSubmitButton();

        // Go to step 1
        goToStep(1);
        updateRunningTotal();
    }

    // ──────────────────────────────────────────────
    // Load Services from REST API
    // ──────────────────────────────────────────────

    function loadServices() {
        var container = document.getElementById('cmcalcServices');
        if (!container) return;

        fetch(restUrl + 'services', {
            headers: { 'X-WP-Nonce': nonce }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var services = data.services || data;
            travelService = data.travel_service || null;
            werkgebieden  = data.werkgebieden || [];
            allServices   = Array.isArray(services) ? services : [];

            renderServices(allServices);
            applyCustomTexts();
            updateBtwLabel();
        })
        .catch(function() {
            container.innerHTML = '<div class="cmcalc-services__error">' +
                '<p>Kon diensten niet laden. Probeer het later opnieuw.</p>' +
            '</div>';
        });
    }

    // ──────────────────────────────────────────────
    // Apply Custom Texts from Settings
    // ──────────────────────────────────────────────

    function applyCustomTexts() {
        if (texts.calc_title) {
            var titleEl = calculator.querySelector('.cmcalc-step__title');
            if (titleEl) titleEl.textContent = texts.calc_title;
        }

        if (texts.btn_step1) {
            var btnStep1 = document.getElementById('cmcalcToOverview');
            if (btnStep1) {
                var textNode = null;
                for (var i = 0; i < btnStep1.childNodes.length; i++) {
                    if (btnStep1.childNodes[i].nodeType === 3) {
                        textNode = btnStep1.childNodes[i];
                        break;
                    }
                }
                if (textNode) {
                    textNode.textContent = texts.btn_step1 + ' ';
                }
            }
        }

        if (texts.disclaimer_text) {
            var disclaimers = calculator.querySelectorAll('.cmcalc-disclaimer');
            for (var d = 0; d < disclaimers.length; d++) {
                disclaimers[d].textContent = texts.disclaimer_text;
            }
        }

        if (texts.success_text) {
            var successText = calculator.querySelector('.cmcalc-success__text');
            if (successText) successText.textContent = texts.success_text;
        }
    }

    // ──────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function() {
        loadServices();
    });

    // If DOM is already loaded (script loaded late), run immediately
    if (document.readyState !== 'loading') {
        loadServices();
    }

})();
