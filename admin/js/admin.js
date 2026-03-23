(function($) {
    'use strict';

    var nonce = cmcalcAdmin.nonce;
    var ajaxUrl = cmcalcAdmin.ajaxUrl;
    var restUrl = cmcalcAdmin.restUrl;
    var restNonce = cmcalcAdmin.restNonce;

    // ─── Toast Notifications ───
    function showToast(message, type) {
        var icon = type === 'error'
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6b6b" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#20c997" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>';
        var $toast = $('<div class="cmcalc-toast">' + icon + ' ' + message + '</div>');
        $('body').append($toast);
        setTimeout(function() { $toast.remove(); }, 3000);
    }

    // ─── Diensten: Inline Edit ───

    var saveTimers = {};
    $(document).on('change blur', '.cmcalc-inline-edit', function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var postId = $row.data('id');
        var field = $input.data('field');
        var value = $input.val();
        var key = postId + '-' + field;

        clearTimeout(saveTimers[key]);
        saveTimers[key] = setTimeout(function() {
            $.post(ajaxUrl, {
                action: 'cmcalc_save_dienst',
                nonce: nonce,
                post_id: postId,
                field: field,
                value: value
            }).done(function(res) {
                if (res.success) {
                    $input.css('border-color', 'var(--cmcalc-green)');
                    setTimeout(function() { $input.css('border-color', ''); }, 1500);
                }
            });
        }, 300);
    });

    // ─── Diensten: Icon Select ───
    $(document).on('change', '.cmcalc-icon-select', function() {
        var $select = $(this);
        var postId = $select.data('id');
        var value = $select.val();
        $.post(ajaxUrl, {
            action: 'cmcalc_save_dienst',
            nonce: nonce,
            post_id: postId,
            field: 'icon',
            value: value
        }).done(function(res) {
            if (res.success) {
                $select.css('border-color', 'var(--cmcalc-green)');
                setTimeout(function() { $select.css('border-color', ''); }, 1500);
            }
        });
    });

    // ─── Diensten: Toggle Active ───

    $(document).on('change', '.cmcalc-toggle-active', function() {
        var $row = $(this).closest('tr');
        var postId = $row.data('id');

        $.post(ajaxUrl, {
            action: 'cmcalc_toggle_dienst',
            nonce: nonce,
            post_id: postId
        }).done(function(res) {
            if (res.success) {
                $row.toggleClass('cmcalc-inactive', res.data.active !== '1');
            }
        });
    });

    // ─── Diensten: Toggle Quote ───

    $(document).on('change', '.cmcalc-toggle-quote', function() {
        var $row = $(this).closest('tr');
        var postId = $row.data('id');
        var value = $(this).is(':checked') ? '1' : '0';

        $.post(ajaxUrl, {
            action: 'cmcalc_save_dienst',
            nonce: nonce,
            post_id: postId,
            field: 'requires_quote',
            value: value
        });
    });

    // ─── Diensten: Add New ───

    $('#cmcalcAddDienst').on('click', function() {
        var title = prompt('Naam van de nieuwe dienst:', 'Nieuwe dienst');
        if (!title) return;

        $.post(ajaxUrl, {
            action: 'cmcalc_add_dienst',
            nonce: nonce,
            title: title
        }).done(function(res) {
            if (res.success) {
                location.reload();
            }
        });
    });

    // ─── Diensten: Delete ───

    $(document).on('click', '.cmcalc-btn-delete', function() {
        if (!confirm('Weet u zeker dat u deze dienst wilt verwijderen?')) return;

        var $row = $(this).closest('tr');
        var postId = $row.data('id');

        $.post(ajaxUrl, {
            action: 'cmcalc_delete_dienst',
            nonce: nonce,
            post_id: postId
        }).done(function(res) {
            if (res.success) {
                $row.fadeOut(300, function() { $row.remove(); });
            }
        });
    });

    // ─── Diensten: Drag & Drop Reorder ───

    if ($('#cmcalcDienstenBody').length) {
        $('#cmcalcDienstenBody').sortable({
            handle: '.cmcalc-drag-handle',
            placeholder: 'ui-sortable-placeholder',
            axis: 'y',
            update: function() {
                var order = [];
                $('#cmcalcDienstenBody tr').each(function() {
                    order.push($(this).data('id'));
                });
                $.post(ajaxUrl, {
                    action: 'cmcalc_reorder',
                    nonce: nonce,
                    order: order
                });
            }
        });
    }

    // ─── Sub-opties Modal ───

    var currentSubDienstId = null;
    var currentSubOptions = [];

    $(document).on('click', '.cmcalc-btn-sub-options', function() {
        currentSubDienstId = $(this).data('id');
        var title = $(this).closest('tr').find('[data-field="title"]').val();
        currentSubOptions = [];

        try {
            currentSubOptions = JSON.parse($(this).attr('data-sub'));
        } catch(e) {
            currentSubOptions = [];
        }
        if (!Array.isArray(currentSubOptions)) currentSubOptions = [];

        $('#cmcalcSubModalTitle').text(title);
        renderSubOptions();
        $('#cmcalcSubOptionsModal').show();
    });

    // Generic modal close — works for ALL modals (sub-options, volume tiers, email, edit)
    $(document).on('click', '.cmcalc-modal-close, .cmcalc-modal-overlay', function() {
        $(this).closest('.cmcalc-modal').hide();
    });

    function renderSubOptions() {
        var $body = $('#cmcalcSubBody');
        $body.empty();

        currentSubOptions.forEach(function(opt, i) {
            var html = '<div class="cmcalc-sub-card" data-index="' + i + '">' +
                '<div class="cmcalc-sub-card-header">' +
                    '<div class="cmcalc-sub-card-fields">' +
                        '<div class="cmcalc-sub-field-group">' +
                            '<label>Label</label>' +
                            '<input type="text" class="cmcalc-sub-label" value="' + escAttr(opt.label) + '" placeholder="Bijv. Met de hand wassen">' +
                        '</div>' +
                        '<div class="cmcalc-sub-field-group cmcalc-sub-field-type">' +
                            '<label>Type</label>' +
                            '<select class="cmcalc-sub-type">' +
                                '<option value="checkbox"' + (opt.type === 'checkbox' ? ' selected' : '') + '>Checkbox</option>' +
                                '<option value="select"' + (opt.type === 'select' ? ' selected' : '') + '>Select (dropdown)</option>' +
                            '</select>' +
                        '</div>';

            if (opt.type === 'checkbox') {
                html += '<div class="cmcalc-sub-field-group cmcalc-sub-field-surcharge">' +
                    '<label>Toeslag</label>' +
                    '<div class="cmcalc-sub-surcharge-input">' +
                        '<span class="cmcalc-sub-euro">&euro;</span>' +
                        '<input type="number" class="cmcalc-sub-surcharge" value="' + (opt.surcharge || 0) + '" step="0.01" min="0">' +
                    '</div>' +
                '</div>';
            }

            html += '</div>' +
                '<button type="button" class="cmcalc-sub-delete" title="Verwijderen">&times;</button>' +
            '</div>';

            // Select options
            if (opt.type === 'select') {
                html += '<div class="cmcalc-sub-select-options">' +
                    '<div class="cmcalc-sub-select-header">' +
                        '<span>Dropdown opties</span>' +
                        '<button type="button" class="button cmcalc-sub-add-select-opt">' +
                            '<span class="dashicons dashicons-plus-alt2"></span> Optie toevoegen' +
                        '</button>' +
                    '</div>' +
                    '<div class="cmcalc-sub-select-list">';

                var options = opt.options || [];
                var surcharges = opt.surcharges || [];
                if (options.length === 0) {
                    options = [''];
                    surcharges = [0];
                }
                options.forEach(function(o, j) {
                    html += '<div class="cmcalc-sub-select-row">' +
                        '<span class="cmcalc-sub-select-num">' + (j + 1) + '.</span>' +
                        '<input type="text" class="cmcalc-sub-select-name" value="' + escAttr(o) + '" placeholder="Optie naam">' +
                        '<div class="cmcalc-sub-surcharge-input">' +
                            '<span class="cmcalc-sub-euro">&euro;</span>' +
                            '<input type="number" class="cmcalc-sub-select-surcharge" value="' + (surcharges[j] || 0) + '" step="0.01" min="0">' +
                        '</div>' +
                        '<button type="button" class="cmcalc-sub-select-remove" title="Verwijderen">&times;</button>' +
                    '</div>';
                });

                html += '</div></div>';
            }

            html += '</div>';
            $body.append(html);
        });
    }

    // Change type → re-render
    $(document).on('change', '.cmcalc-sub-type', function() {
        // Collect current state from DOM before re-render
        collectSubOptionsFromDOM();
        renderSubOptions();
    });

    // Add select option row
    $(document).on('click', '.cmcalc-sub-add-select-opt', function() {
        var $list = $(this).closest('.cmcalc-sub-select-options').find('.cmcalc-sub-select-list');
        var num = $list.find('.cmcalc-sub-select-row').length + 1;
        $list.append(
            '<div class="cmcalc-sub-select-row">' +
                '<span class="cmcalc-sub-select-num">' + num + '.</span>' +
                '<input type="text" class="cmcalc-sub-select-name" value="" placeholder="Optie naam">' +
                '<div class="cmcalc-sub-surcharge-input">' +
                    '<span class="cmcalc-sub-euro">&euro;</span>' +
                    '<input type="number" class="cmcalc-sub-select-surcharge" value="0" step="0.01" min="0">' +
                '</div>' +
                '<button type="button" class="cmcalc-sub-select-remove" title="Verwijderen">&times;</button>' +
            '</div>'
        );
    });

    // Remove select option row
    $(document).on('click', '.cmcalc-sub-select-remove', function() {
        $(this).closest('.cmcalc-sub-select-row').remove();
    });

    function collectSubOptionsFromDOM() {
        var subs = [];
        $('#cmcalcSubBody .cmcalc-sub-card').each(function() {
            var label = $(this).find('.cmcalc-sub-label').val().trim();
            var type = $(this).find('.cmcalc-sub-type').val();
            var item = { label: label, type: type, surcharge: 0 };

            if (type === 'checkbox') {
                item.surcharge = parseFloat($(this).find('.cmcalc-sub-surcharge').val()) || 0;
            } else if (type === 'select') {
                item.options = [];
                item.surcharges = [];
                $(this).find('.cmcalc-sub-select-row').each(function() {
                    var optName = $(this).find('.cmcalc-sub-select-name').val().trim();
                    if (optName) {
                        item.options.push(optName);
                        item.surcharges.push(parseFloat($(this).find('.cmcalc-sub-select-surcharge').val()) || 0);
                    }
                });
            }
            subs.push(item);
        });
        currentSubOptions = subs;
    }

    $('#cmcalcAddSubOption').on('click', function() {
        collectSubOptionsFromDOM();
        currentSubOptions.push({ label: '', type: 'checkbox', surcharge: 0 });
        renderSubOptions();
    });

    $(document).on('click', '.cmcalc-sub-delete', function() {
        $(this).closest('.cmcalc-sub-card').remove();
    });

    $('#cmcalcSaveSubOptions').on('click', function() {
        collectSubOptionsFromDOM();
        var subs = currentSubOptions.filter(function(s) { return s.label; });

        $.post(ajaxUrl, {
            action: 'cmcalc_save_sub_options',
            nonce: nonce,
            post_id: currentSubDienstId,
            sub_options: JSON.stringify(subs)
        }).done(function(res) {
            if (res.success) {
                var $btn = $('tr[data-id="' + currentSubDienstId + '"] .cmcalc-btn-sub-options');
                $btn.text('Beheer (' + subs.length + ')');
                $btn.attr('data-sub', JSON.stringify(subs));
                $('#cmcalcSubOptionsModal').hide();
                showToast('Sub-opties opgeslagen');
            }
        });
    });

    // ─── Werkgebieden ───

    // Geocode postcode
    $(document).on('click', '.cmcalc-wg-geocode', function() {
        var $card = $(this).closest('.cmcalc-werkgebied-card');
        var postcode = $card.find('.cmcalc-wg-postcode').val().trim();
        if (!postcode) return alert('Vul een postcode in');

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: restUrl + 'geocode-werkgebied',
            method: 'POST',
            headers: { 'X-WP-Nonce': restNonce },
            data: { postcode: postcode }
        }).done(function(res) {
            $card.find('.cmcalc-wg-lat').val(res.lat);
            $card.find('.cmcalc-wg-lon').val(res.lon);
            if (res.city && !$card.find('.cmcalc-wg-name').val()) {
                $card.find('.cmcalc-wg-name').val(res.city);
            }
        }).fail(function() {
            alert('Postcode niet gevonden');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    // Save werkgebied
    $(document).on('click', '.cmcalc-wg-save', function() {
        var $card = $(this).closest('.cmcalc-werkgebied-card');
        var wgId = $card.data('id') || 0;

        $.post(ajaxUrl, {
            action: 'cmcalc_save_werkgebied',
            nonce: nonce,
            werkgebied_id: wgId,
            name: $card.find('.cmcalc-wg-name').val(),
            postcode: $card.find('.cmcalc-wg-postcode').val(),
            lat: $card.find('.cmcalc-wg-lat').val(),
            lon: $card.find('.cmcalc-wg-lon').val(),
            free_km: $card.find('.cmcalc-wg-freekm').val(),
            bedrijf_id: $card.find('.cmcalc-wg-bedrijf').val() || 0
        }).done(function(res) {
            if (res.success) {
                if (!wgId) {
                    $card.data('id', res.data.id);
                    $card.attr('data-id', res.data.id);
                }
                var $btn = $card.find('.cmcalc-wg-save');
                $btn.text('Opgeslagen!');
                setTimeout(function() { $btn.text('Opslaan'); }, 2000);
            }
        });
    });

    // Toggle werkgebied
    $(document).on('change', '.cmcalc-wg-toggle', function() {
        var $card = $(this).closest('.cmcalc-werkgebied-card');
        var wgId = $card.data('id');
        if (!wgId) return;

        $.post(ajaxUrl, {
            action: 'cmcalc_toggle_werkgebied',
            nonce: nonce,
            werkgebied_id: wgId
        }).done(function(res) {
            if (res.success) {
                $card.toggleClass('cmcalc-inactive', res.data.active !== '1');
            }
        });
    });

    // Delete werkgebied
    $(document).on('click', '.cmcalc-wg-delete', function() {
        if (!confirm('Weet u zeker dat u dit werkgebied wilt verwijderen?')) return;

        var $card = $(this).closest('.cmcalc-werkgebied-card');
        var wgId = $card.data('id');

        if (!wgId) {
            $card.remove();
            return;
        }

        $.post(ajaxUrl, {
            action: 'cmcalc_delete_werkgebied',
            nonce: nonce,
            werkgebied_id: wgId
        }).done(function(res) {
            if (res.success) {
                $card.fadeOut(300, function() { $card.remove(); });
            }
        });
    });

    // Add new werkgebied
    $('#cmcalcAddWerkgebied').on('click', function() {
        var cardHtml =
            '<div class="cmcalc-werkgebied-card" data-id="">' +
                '<div class="cmcalc-wg-header">' +
                    '<input type="text" class="cmcalc-wg-name" value="" placeholder="Naam werkgebied">' +
                    '<label class="cmcalc-toggle">' +
                        '<input type="checkbox" class="cmcalc-wg-toggle" checked>' +
                        '<span class="cmcalc-toggle-slider"></span>' +
                    '</label>' +
                '</div>' +
                '<div class="cmcalc-wg-body">' +
                    '<div class="cmcalc-wg-field">' +
                        '<label>Postcode</label>' +
                        '<div class="cmcalc-wg-postcode-row">' +
                            '<input type="text" class="cmcalc-wg-postcode" placeholder="1234AB" maxlength="7">' +
                            '<button type="button" class="button cmcalc-wg-geocode" title="Geocode">' +
                                '<span class="dashicons dashicons-location"></span>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="cmcalc-wg-coords">' +
                        '<div class="cmcalc-wg-field">' +
                            '<label>Lat</label>' +
                            '<input type="number" class="cmcalc-wg-lat" step="0.0001" readonly>' +
                        '</div>' +
                        '<div class="cmcalc-wg-field">' +
                            '<label>Lon</label>' +
                            '<input type="number" class="cmcalc-wg-lon" step="0.0001" readonly>' +
                        '</div>' +
                    '</div>' +
                    '<div class="cmcalc-wg-field">' +
                        '<label>Gratis km</label>' +
                        '<input type="number" class="cmcalc-wg-freekm" value="20" min="0">' +
                    '</div>' +
                '</div>' +
                '<div class="cmcalc-wg-footer">' +
                    '<button type="button" class="button button-primary cmcalc-wg-save">Opslaan</button>' +
                    '<button type="button" class="button cmcalc-wg-delete">' +
                        '<span class="dashicons dashicons-trash"></span> Verwijderen' +
                    '</button>' +
                '</div>' +
            '</div>';

        $(cardHtml).insertBefore('#cmcalcAddWerkgebied');
    });

    // ─── Instellingen ───

    $('#cmcalcSaveTravelPrice').on('click', function() {
        var price = $('#cmcalcTravelPrice').val();
        $.post(ajaxUrl, {
            action: 'cmcalc_save_travel_price',
            nonce: nonce,
            price: price
        }).done(function(res) {
            if (res.success) {
                $('#cmcalcTravelStatus').text('Opgeslagen!').show();
                setTimeout(function() { $('#cmcalcTravelStatus').fadeOut(); }, 2000);
            }
        });
    });

    $('#cmcalcSeedDiensten').on('click', function() {
        if (!confirm('Standaard diensten aanmaken? Bestaande diensten worden niet overschreven.')) return;

        $.post(ajaxUrl, {
            action: 'cmcalc_seed_diensten',
            nonce: nonce
        }).done(function(res) {
            if (res.success) {
                $('#cmcalcSeedStatus').text('Aangemaakt!').show();
                setTimeout(function() { location.reload(); }, 1000);
            }
        });
    });

    // ─── Stijl Customizer ───

    var stylePreviewVarMap = {
        primary_color: '--preview-primary',
        secondary_color: '--preview-secondary',
        accent_color: '--preview-accent',
        text_color: '--preview-text',
        text_light_color: '--preview-text-light',
        bg_color: '--preview-bg',
        bg_light_color: '--preview-bg-light',
        border_color: '--preview-border'
    };

    function updateStylePreview() {
        var $preview = $('#cmcalcStylePreview');
        if (!$preview.length) return;

        // Colors
        $('.cmcalc-color-picker').each(function() {
            var varName = $(this).data('var');
            var color = $(this).val();
            if (varName && color && stylePreviewVarMap[varName]) {
                $preview[0].style.setProperty(stylePreviewVarMap[varName], color);
            }
        });

        // Border radius
        var radius = $('#cmcalcBorderRadius').val();
        $preview[0].style.setProperty('--preview-radius', radius + 'px');

        var btnRadius = $('#cmcalcBtnRadius').val();
        $preview[0].style.setProperty('--preview-btn-radius', btnRadius + 'px');

        // Shadow
        var shadowEnabled = $('#cmcalcShadowEnabled').is(':checked');
        var shadowIntensity = $('#cmcalcShadowIntensity').val();
        var shadowVal = 'none';
        if (shadowEnabled) {
            if (shadowIntensity === 'light') shadowVal = '0 1px 8px rgba(0,0,0,0.04)';
            else if (shadowIntensity === 'medium') shadowVal = '0 2px 15px rgba(0,0,0,0.08)';
            else if (shadowIntensity === 'strong') shadowVal = '0 4px 25px rgba(0,0,0,0.12)';
        }
        $preview[0].style.setProperty('--preview-shadow', shadowVal);

        // Font sizes
        var fontBase = $('#cmcalcFontBase').val();
        $preview[0].style.setProperty('--preview-font-base', fontBase + 'px');
        var fontTitle = $('#cmcalcFontTitle').val();
        $preview[0].style.setProperty('--preview-font-title', fontTitle + 'px');

        // Max width & padding
        var maxWidth = $('#cmcalcMaxWidth').val();
        if (maxWidth) {
            $preview[0].style.setProperty('--preview-max-width', maxWidth + 'px');
            $preview.css('max-width', Math.min(380, parseInt(maxWidth) * 0.35) + 'px');
        }
        var padding = $('#cmcalcPadding').val();
        if (padding) {
            $preview.css('padding', padding + 'px');
        }

        // Spacing
        var spacing = $('input[name="cmcalc_spacing"]:checked').val() || 'normal';
        var gapMap = { compact: 4, normal: 6, spacious: 10 };
        $preview.find('.sp-service').css('margin-bottom', (gapMap[spacing] || 6) + 'px');
        $preview.find('.sp-title').css('margin-bottom', (gapMap[spacing] || 6) + 'px');

        // Update selected service bg for dark themes
        var primary = $('.cmcalc-color-picker[data-var="primary_color"]').val() || '#1B2A4A';
        $preview.find('.sp-service.selected').css('background', hexToRgba(primary, 0.06));
    }

    function hexToRgba(hex, alpha) {
        hex = hex.replace('#', '');
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    // Init color pickers on Stijl tab
    if ($('.cmcalc-color-picker').length) {
        $('.cmcalc-color-picker').wpColorPicker({
            change: function(event, ui) {
                var $input = $(event.target);
                $input.val(ui.color.toString());
                updateStylePreview();
                // Switch to custom preset
                $('.cmcalc-preset-card').removeClass('active').find('.cmcalc-preset-check').remove();
                $('.cmcalc-preset-card[data-preset="custom"]').addClass('active');
            },
            clear: function() {
                updateStylePreview();
            }
        });
    }

    // Preset card click
    $(document).on('click', '.cmcalc-preset-card', function() {
        var presetKey = $(this).data('preset');
        if (presetKey === 'custom') return;

        var presets = cmcalcAdmin.presets || {};
        var preset = presets[presetKey];
        if (!preset) return;

        // Update all color pickers
        var colorFields = ['primary_color', 'secondary_color', 'accent_color', 'text_color', 'text_light_color', 'bg_color', 'bg_light_color', 'border_color'];
        colorFields.forEach(function(field) {
            if (preset[field]) {
                var $picker = $('.cmcalc-color-picker[data-var="' + field + '"]');
                $picker.val(preset[field]);
                $picker.wpColorPicker('color', preset[field]);
            }
        });

        // Update active state
        $('.cmcalc-preset-card').removeClass('active').find('.cmcalc-preset-check').remove();
        $(this).addClass('active').append('<div class="cmcalc-preset-check">✓</div>');

        updateStylePreview();
    });

    // Range sliders
    $(document).on('input', '.cmcalc-range-input', function() {
        var val = $(this).val();
        $(this).siblings('.cmcalc-range-value').text(val + 'px');
        updateStylePreview();
    });

    // Shadow toggle
    $('#cmcalcShadowEnabled').on('change', function() {
        $('#cmcalcShadowIntensity').prop('disabled', !$(this).is(':checked'));
        updateStylePreview();
    });
    $('#cmcalcShadowIntensity').on('change', function() {
        updateStylePreview();
    });

    // Spacing radio buttons
    $(document).on('change', 'input[name="cmcalc_spacing"]', function() {
        $('.cmcalc-spacing-option').removeClass('active');
        $(this).closest('.cmcalc-spacing-option').addClass('active');
        updateStylePreview();
    });

    // Save styles
    $('#cmcalcSaveStyles').on('click', function() {
        var data = {};

        // Colors
        $('.cmcalc-color-picker').each(function() {
            data[$(this).data('var')] = $(this).val();
        });

        // Layout
        data.border_radius = parseInt($('#cmcalcBorderRadius').val()) || 16;
        data.btn_radius = parseInt($('#cmcalcBtnRadius').val()) || 8;
        data.shadow_enabled = $('#cmcalcShadowEnabled').is(':checked');
        data.shadow_intensity = $('#cmcalcShadowIntensity').val();
        data.font_size_base = parseInt($('#cmcalcFontBase').val()) || 15;
        data.font_size_title = parseInt($('#cmcalcFontTitle').val()) || 22;
        data.calc_max_width = parseInt($('#cmcalcMaxWidth').val()) || 1100;
        data.calc_padding = parseInt($('#cmcalcPadding').val()) || 24;
        data.calc_spacing = $('input[name="cmcalc_spacing"]:checked').val() || 'normal';

        // Preset
        var activePreset = $('.cmcalc-preset-card.active').data('preset') || 'custom';
        data.preset = activePreset;

        $.post(ajaxUrl, {
            action: 'cmcalc_save_styles',
            nonce: nonce,
            styles: JSON.stringify(data)
        }).done(function(res) {
            if (res.success) {
                showToast('Stijl opgeslagen!');
            } else {
                showToast('Opslaan mislukt', 'error');
            }
        });
    });

    // Initial preview update
    if ($('#cmcalcStylePreview').length) {
        updateStylePreview();
    }

    // ─── Bedrijven ───

    // Save bedrijf
    $(document).on('click', '.cmcalc-bedrijf-save', function() {
        var $card = $(this).closest('.cmcalc-bedrijf-card');
        var bedrijf_id = $card.data('id') || 0;
        var $btn = $(this);
        $btn.prop('disabled', true).text('Opslaan...');

        $.post(ajaxUrl, {
            action: 'cmcalc_save_bedrijf',
            nonce: nonce,
            bedrijf_id: bedrijf_id,
            name: $card.find('.cmcalc-bedrijf-name').val(),
            address: $card.find('.cmcalc-bedrijf-address').val(),
            postcode: $card.find('.cmcalc-bedrijf-postcode').val(),
            huisnummer: $card.find('.cmcalc-bedrijf-huisnummer').val(),
            phone: $card.find('.cmcalc-bedrijf-phone').val(),
            email: $card.find('.cmcalc-bedrijf-email').val(),
            lat: $card.find('.cmcalc-bedrijf-lat').val(),
            lon: $card.find('.cmcalc-bedrijf-lon').val()
        }).done(function(res) {
            if (res.success) {
                if (!bedrijf_id) $card.data('id', res.data.id);
                showToast('Bedrijf opgeslagen');
            } else {
                showToast(res.data || 'Opslaan mislukt', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Opslaan');
        });
    });

    // Delete bedrijf
    $(document).on('click', '.cmcalc-bedrijf-delete', function() {
        if (!confirm('Weet u zeker dat u dit bedrijf wilt verwijderen? Werkgebieden en diensten worden ontkoppeld.')) return;
        var $card = $(this).closest('.cmcalc-bedrijf-card');
        var bedrijf_id = $card.data('id');
        if (!bedrijf_id) { $card.remove(); return; }

        $.post(ajaxUrl, {
            action: 'cmcalc_delete_bedrijf',
            nonce: nonce,
            bedrijf_id: bedrijf_id
        }).done(function(res) {
            if (res.success) {
                $card.fadeOut(300, function() { $(this).remove(); });
                showToast('Bedrijf verwijderd');
            }
        });
    });

    // Toggle bedrijf
    $(document).on('change', '.cmcalc-bedrijf-toggle', function() {
        var $card = $(this).closest('.cmcalc-bedrijf-card');
        var bedrijf_id = $card.data('id');
        if (!bedrijf_id) return;

        $.post(ajaxUrl, {
            action: 'cmcalc_toggle_bedrijf',
            nonce: nonce,
            bedrijf_id: bedrijf_id
        }).done(function(res) {
            if (res.success) {
                $card.toggleClass('inactive', !res.data.active);
                $card.find('.cmcalc-bedrijf-active-label').text(res.data.active ? 'Actief' : 'Inactief');
                showToast(res.data.active ? 'Bedrijf geactiveerd' : 'Bedrijf gedeactiveerd');
            }
        });
    });

    // Geocode bedrijf postcode
    $(document).on('click', '.cmcalc-bedrijf-geocode', function() {
        var $card = $(this).closest('.cmcalc-bedrijf-card');
        var pc = $card.find('.cmcalc-bedrijf-postcode').val().trim();
        if (!pc) return;
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', {
            q: pc, rows: 1, fq: 'type:postcode'
        }).done(function(data) {
            var docs = data.response && data.response.docs;
            if (docs && docs.length > 0) {
                var centroid = docs[0].centroide_ll || '';
                var m = centroid.match(/POINT\(([0-9.]+)\s+([0-9.]+)\)/);
                if (m) {
                    $card.find('.cmcalc-bedrijf-lat').val(parseFloat(m[2]).toFixed(6));
                    $card.find('.cmcalc-bedrijf-lon').val(parseFloat(m[1]).toFixed(6));
                    showToast('Locatie gevonden: ' + (docs[0].woonplaatsnaam || pc));
                }
            } else {
                showToast('Postcode niet gevonden', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    // Add new bedrijf
    $(document).on('click', '#cmcalcAddBedrijf', function() {
        var html = '<div class="cmcalc-bedrijf-card" data-id="0">' +
            '<div class="cmcalc-bedrijf-header">' +
                '<div class="cmcalc-bedrijf-status">' +
                    '<label class="cmcalc-toggle"><input type="checkbox" class="cmcalc-bedrijf-toggle" checked><span class="cmcalc-toggle-slider"></span></label>' +
                    '<span class="cmcalc-bedrijf-active-label">Actief</span>' +
                '</div>' +
                '<div class="cmcalc-bedrijf-stats"></div>' +
            '</div>' +
            '<div class="cmcalc-bedrijf-body">' +
                '<div class="cmcalc-field-group"><label>Bedrijfsnaam</label><input type="text" class="cmcalc-bedrijf-name regular-text" style="width:100%;" placeholder="Bedrijfsnaam"></div>' +
                '<div class="cmcalc-field-group"><label>Adres</label><input type="text" class="cmcalc-bedrijf-address regular-text" style="width:100%;" placeholder="Straat en huisnummer"></div>' +
                '<div class="cmcalc-field-row" style="display:flex;gap:10px;">' +
                    '<div class="cmcalc-field-group" style="flex:1;"><label>Postcode</label>' +
                        '<div style="display:flex;gap:6px;"><input type="text" class="cmcalc-bedrijf-postcode" maxlength="7" style="width:100px;" placeholder="1234AB">' +
                        '<input type="text" class="cmcalc-bedrijf-huisnummer" placeholder="Nr." style="width:70px;">' +
                        '<button type="button" class="button cmcalc-bedrijf-geocode" title="Geocode"><span class="dashicons dashicons-location-alt" style="vertical-align:middle;"></span></button></div>' +
                    '</div>' +
                    '<div class="cmcalc-field-group" style="flex:1;"><label>Telefoon</label><input type="tel" class="cmcalc-bedrijf-phone" style="width:100%;"></div>' +
                '</div>' +
                '<div class="cmcalc-field-group"><label>Email</label><input type="email" class="cmcalc-bedrijf-email regular-text" style="width:100%;"></div>' +
                '<div class="cmcalc-field-row" style="display:flex;gap:10px;">' +
                    '<div class="cmcalc-field-group" style="flex:1;"><label>Latitude</label><input type="text" class="cmcalc-bedrijf-lat" readonly style="width:100%;background:#f0f0f0;"></div>' +
                    '<div class="cmcalc-field-group" style="flex:1;"><label>Longitude</label><input type="text" class="cmcalc-bedrijf-lon" readonly style="width:100%;background:#f0f0f0;"></div>' +
                '</div>' +
            '</div>' +
            '<div class="cmcalc-bedrijf-footer">' +
                '<button type="button" class="button button-primary cmcalc-bedrijf-save">Opslaan</button>' +
                '<button type="button" class="button cmcalc-bedrijf-delete" style="color:#dc3545;border-color:#dc3545;">Verwijderen</button>' +
            '</div>' +
        '</div>';
        $('#cmcalcBedrijvenGrid').prepend(html);
        $('#cmcalcBedrijvenGrid .cmcalc-bedrijf-card:first .cmcalc-bedrijf-name').focus();
    });

    // Dienst bedrijven dropdown toggle
    $(document).on('click', '.cmcalc-dienst-bedrijven-btn', function(e) {
        e.stopPropagation();
        var $wrap = $(this).closest('.cmcalc-dienst-bedrijven-wrap');
        var $dd = $wrap.find('.cmcalc-dienst-bedrijven-dropdown');
        // Close all other dropdowns
        $('.cmcalc-dienst-bedrijven-dropdown').not($dd).hide();
        $dd.toggle();
    });

    // Close bedrijven dropdown on outside click
    $(document).on('click', function() {
        $('.cmcalc-dienst-bedrijven-dropdown').hide();
    });

    // Prevent dropdown close on checkbox click
    $(document).on('click', '.cmcalc-dienst-bedrijven-dropdown', function(e) {
        e.stopPropagation();
    });

    // Save dienst bedrijven on checkbox change
    $(document).on('change', '.cmcalc-dienst-bedrijf-check', function() {
        var $wrap = $(this).closest('.cmcalc-dienst-bedrijven-wrap');
        var dienstId = $wrap.data('dienst-id');
        var ids = [];
        $wrap.find('.cmcalc-dienst-bedrijf-check:checked').each(function() {
            ids.push(parseInt($(this).val()));
        });
        $wrap.find('.cmcalc-dienst-bedrijven-count').text(ids.length);

        $.post(ajaxUrl, {
            action: 'cmcalc_save_dienst_bedrijven',
            nonce: nonce,
            post_id: dienstId,
            bedrijf_ids: JSON.stringify(ids)
        }).done(function(res) {
            if (res.success) {
                showToast('Bedrijven bijgewerkt');
            }
        });
    });

    // Werkgebied bedrijf filter
    $(document).on('change', '#cmcalcWgBedrijfFilter', function() {
        var filterVal = $(this).val();
        $('.cmcalc-werkgebied-card, .cmcalc-wg-card').each(function() {
            if (!filterVal) {
                $(this).show();
            } else {
                var cardBedrijf = $(this).find('.cmcalc-wg-bedrijf').val();
                $(this).toggle(cardBedrijf === filterVal);
            }
        });
    });

    // Bedrijf filter in boekingen
    $(document).on('change', '#cmcalcBookingBedrijfFilter', function() {
        if (typeof bookingPage !== 'undefined') bookingPage = 1;
        if (typeof loadBookings === 'function') loadBookings();
    });

    // ─── Staffelprijzen (Volume Tiers) ───

    var currentVolumeDienstId = 0;

    $(document).on('click', '.cmcalc-volume-tiers-btn', function() {
        var $btn = $(this);
        currentVolumeDienstId = parseInt($btn.data('id'));
        var tiersJson = $btn.data('tiers') || '[]';
        var tiers = typeof tiersJson === 'string' ? JSON.parse(tiersJson) : tiersJson;
        var basePrice = parseFloat($btn.closest('tr').find('.cmcalc-inline-edit[data-field="base_price"]').val()) || 0;

        // Store per-bedrijf pricing data on the button
        var bedrijfPricingJson = $btn.data('bedrijf-pricing') || '{}';
        var bedrijfPricing = typeof bedrijfPricingJson === 'string' ? JSON.parse(bedrijfPricingJson) : bedrijfPricingJson;
        $btn.data('_bedrijf_pricing_obj', bedrijfPricing);
        $btn.data('_default_tiers', tiers);
        $btn.data('_default_base_price', basePrice);

        // Reset bedrijf selector
        var $bedrijfSelect = $('#cmcalcTiersBedrijfSelect');
        if ($bedrijfSelect.length) $bedrijfSelect.val('0');

        // Set base price
        $('#cmcalcTiersBasePrice').val(basePrice);

        var $list = $('#cmcalcVolumeTiersList');
        $list.empty();

        if (tiers.length === 0) {
            addVolumeTierRow($list, 1, 10, basePrice);
        } else {
            tiers.forEach(function(tier) {
                addVolumeTierRow($list, tier.min, tier.max, tier.price);
            });
        }

        $('#cmcalcVolumeTiersModal').show();
    });

    // When bedrijf selector changes, load that bedrijf's pricing
    $(document).on('change', '#cmcalcTiersBedrijfSelect', function() {
        var selectedBedrijf = $(this).val();
        var $triggerBtn = $('tr[data-id="' + currentVolumeDienstId + '"] .cmcalc-volume-tiers-btn');
        var bedrijfPricing = $triggerBtn.data('_bedrijf_pricing_obj') || {};
        var $list = $('#cmcalcVolumeTiersList');
        $list.empty();

        if (selectedBedrijf > 0 && bedrijfPricing[selectedBedrijf]) {
            var override = bedrijfPricing[selectedBedrijf];
            var overrideTiers = override.volume_tiers || [];
            var overrideBase = override.base_price !== undefined ? override.base_price : ($triggerBtn.data('_default_base_price') || 0);
            $('#cmcalcTiersBasePrice').val(overrideBase);
            if (overrideTiers.length === 0) {
                addVolumeTierRow($list, 1, 10, overrideBase);
            } else {
                overrideTiers.forEach(function(tier) {
                    addVolumeTierRow($list, tier.min, tier.max, tier.price);
                });
            }
        } else {
            // Default pricing
            var defaultTiers = $triggerBtn.data('_default_tiers') || [];
            var defaultBase = $triggerBtn.data('_default_base_price') || 0;
            $('#cmcalcTiersBasePrice').val(defaultBase);
            if (defaultTiers.length === 0) {
                addVolumeTierRow($list, 1, 10, defaultBase);
            } else {
                defaultTiers.forEach(function(tier) {
                    addVolumeTierRow($list, tier.min, tier.max, tier.price);
                });
            }
        }
    });

    function addVolumeTierRow($list, min, max, price) {
        var html = '<div class="cmcalc-volume-tier-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">' +
            '<div style="flex:1;"><label style="font-size:11px;color:#6c757d;">Vanaf</label><input type="number" class="cmcalc-vt-min" value="' + min + '" min="1" style="width:100%;padding:6px 8px;border:1.5px solid #e9ecef;border-radius:6px;"></div>' +
            '<div style="flex:1;"><label style="font-size:11px;color:#6c757d;">Tot</label><input type="number" class="cmcalc-vt-max" value="' + max + '" min="1" style="width:100%;padding:6px 8px;border:1.5px solid #e9ecef;border-radius:6px;"></div>' +
            '<div style="flex:1;"><label style="font-size:11px;color:#6c757d;">Prijs/eenheid</label><input type="number" step="0.01" class="cmcalc-vt-price" value="' + price + '" min="0" style="width:100%;padding:6px 8px;border:1.5px solid #e9ecef;border-radius:6px;"></div>' +
            '<button type="button" class="cmcalc-vt-remove" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:18px;margin-top:16px;">×</button>' +
        '</div>';
        $list.append(html);
    }

    $(document).on('click', '.cmcalc-vt-remove', function() {
        $(this).closest('.cmcalc-volume-tier-row').remove();
    });

    $(document).on('click', '#cmcalcAddVolumeTier', function() {
        var $list = $('#cmcalcVolumeTiersList');
        var lastMax = 0;
        $list.find('.cmcalc-vt-max').each(function() {
            var v = parseInt($(this).val()) || 0;
            if (v > lastMax) lastMax = v;
        });
        addVolumeTierRow($list, lastMax + 1, lastMax + 10, 0);
    });

    $(document).on('click', '#cmcalcSaveVolumeTiers', function() {
        var tiers = [];
        $('#cmcalcVolumeTiersList .cmcalc-volume-tier-row').each(function() {
            tiers.push({
                min: parseInt($(this).find('.cmcalc-vt-min').val()) || 1,
                max: parseInt($(this).find('.cmcalc-vt-max').val()) || 999,
                price: parseFloat($(this).find('.cmcalc-vt-price').val()) || 0
            });
        });

        var $btn = $(this);
        $btn.prop('disabled', true).text('Opslaan...');

        var bedrijfId = $('#cmcalcTiersBedrijfSelect').val() || '0';
        var basePrice = parseFloat($('#cmcalcTiersBasePrice').val()) || 0;

        $.post(ajaxUrl, {
            action: 'cmcalc_save_volume_tiers',
            nonce: nonce,
            post_id: currentVolumeDienstId,
            tiers: JSON.stringify(tiers),
            bedrijf_id: bedrijfId,
            base_price: basePrice
        }).done(function(res) {
            if (res.success) {
                var $triggerBtn = $('tr[data-id="' + currentVolumeDienstId + '"] .cmcalc-volume-tiers-btn');
                if (bedrijfId === '0' || bedrijfId === 0) {
                    // Update default tiers
                    $triggerBtn.data('tiers', JSON.stringify(res.data.tiers));
                    $triggerBtn.data('_default_tiers', res.data.tiers);
                    $triggerBtn.data('_default_base_price', basePrice);
                } else {
                    // Update bedrijf-specific pricing in local cache
                    var pricing = $triggerBtn.data('_bedrijf_pricing_obj') || {};
                    pricing[bedrijfId] = { base_price: basePrice, volume_tiers: res.data.tiers };
                    $triggerBtn.data('_bedrijf_pricing_obj', pricing);
                }
                $triggerBtn.find('.cmcalc-badge').text(res.data.tiers.length);
                showToast('Staffelprijzen opgeslagen' + (bedrijfId > 0 ? ' (bedrijf-specifiek)' : ''));
                $('#cmcalcVolumeTiersModal').hide();
            } else {
                showToast(res.data || 'Opslaan mislukt', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Opslaan');
        });
    });

    // Volume tiers modal close handled by generic .cmcalc-modal-close handler

    // ─── Boekingen ───

    var currentBookingId = null;
    var currentBookingData = null;
    var bookingPage = 1;
    var bookingsPerPage = 20;
    var noteSaveTimer = null;
    var bookingSearchTimer = null;

    var statusLabels = {
        'nieuw': 'Nieuw',
        'bevestigd': 'Bevestigd',
        'gepland': 'Gepland',
        'voltooid': 'Voltooid',
        'geannuleerd': 'Geannuleerd'
    };

    function formatEuro(num) {
        return '€' + parseFloat(num || 0).toFixed(2).replace('.', ',');
    }

    function loadBookings() {
        var $body = $('#cmcalcBookingsBody');
        $body.html('<tr><td colspan="6" style="padding:40px;text-align:center;color:#999;">Laden...</td></tr>');

        $.post(ajaxUrl, {
            action: 'cmcalc_get_bookings_page',
            nonce: nonce,
            status: $('#cmcalcBookingStatusFilter').val() || 'alle',
            search: $('#cmcalcBookingSearch').val() || '',
            date_from: $('#cmcalcDateFrom').val() || '',
            date_to: $('#cmcalcDateTo').val() || '',
            bedrijf_id: $('#cmcalcBookingBedrijfFilter').val() || '',
            page: bookingPage,
            per_page: bookingsPerPage
        }).done(function(res) {
            if (!res.success) return;
            renderBookingsTable(res.data.bookings);
            renderPagination(res.data.total, res.data.page, res.data.pages);
            $('#cmcalcBookingCount').text(res.data.total + ' boeking' + (res.data.total !== 1 ? 'en' : ''));
        });
    }

    function renderBookingsTable(bookings) {
        var $body = $('#cmcalcBookingsBody');
        $body.empty();

        if (!bookings || bookings.length === 0) {
            $body.html(
                '<tr><td colspan="6" class="cmcalc-empty-state">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>' +
                '<p>Geen boekingen gevonden</p>' +
                '</td></tr>'
            );
            return;
        }

        bookings.forEach(function(b) {
            var statusClass = 'cmcalc-status-' + b.status;
            var statusLabel = statusLabels[b.status] || b.status;
            var locationInfo = '';
            if (b.postcode) {
                locationInfo = b.postcode + ' ' + (b.house_number || '');
            }

            var $row = $(
                '<tr class="cmcalc-booking-row" data-id="' + b.id + '">' +
                    '<td>' + escAttr(b.date) + '</td>' +
                    '<td><strong>' + escAttr(b.name) + '</strong></td>' +
                    '<td>' + escAttr(b.email) + '</td>' +
                    '<td><span style="max-width:200px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escAttr(b.service) + '</span></td>' +
                    '<td><span class="cmcalc-status-badge ' + statusClass + '">' + statusLabel + '</span></td>' +
                    '<td><strong>' + formatEuro(b.total) + '</strong></td>' +
                '</tr>'
            );

            if (currentBookingId === b.id) $row.addClass('active');
            $body.append($row);
        });
    }

    function renderPagination(total, page, pages) {
        var $pag = $('#cmcalcPagination');
        if (pages <= 1) { $pag.empty(); return; }

        $pag.html(
            '<button type="button" class="button cmcalc-page-prev"' + (page <= 1 ? ' disabled' : '') + '>&laquo; Vorige</button>' +
            '<span class="cmcalc-pagination-info">Pagina ' + page + ' van ' + pages + '</span>' +
            '<button type="button" class="button cmcalc-page-next"' + (page >= pages ? ' disabled' : '') + '>Volgende &raquo;</button>'
        );
    }

    // Page navigation
    $(document).on('click', '.cmcalc-page-prev', function() {
        if (bookingPage > 1) { bookingPage--; loadBookings(); }
    });
    $(document).on('click', '.cmcalc-page-next', function() {
        bookingPage++; loadBookings();
    });

    // Filters
    $('#cmcalcBookingStatusFilter').on('change', function() { bookingPage = 1; loadBookings(); });
    $('#cmcalcDateFrom, #cmcalcDateTo').on('change', function() { bookingPage = 1; loadBookings(); });
    $('#cmcalcBookingSearch').on('input', function() {
        clearTimeout(bookingSearchTimer);
        bookingSearchTimer = setTimeout(function() { bookingPage = 1; loadBookings(); }, 400);
    });

    // Row click → open panel
    $(document).on('click', '.cmcalc-booking-row', function() {
        var id = $(this).data('id');
        openBookingPanel(id);
    });

    function openBookingPanel(id) {
        currentBookingId = id;
        $('.cmcalc-booking-row').removeClass('active');
        $('tr[data-id="' + id + '"]').addClass('active');

        // Show loading state
        $('#cmcalcPanelName').text('Laden...');
        $('#cmcalcBookingPanel').addClass('open');

        $.post(ajaxUrl, {
            action: 'cmcalc_get_booking_detail',
            nonce: nonce,
            post_id: id
        }).done(function(res) {
            if (!res.success) return;
            currentBookingData = res.data;
            populatePanel(res.data);
        });
    }

    function populatePanel(d) {
        $('#cmcalcPanelName').text(d.name || '—');
        $('#cmcalcPanelDate').text(d.date || '');
        $('#cmcalcPanelStatus').val(d.status);
        updatePanelStatusBadge(d.status);

        // Contact
        $('#cmcalcPanelEmail').text(d.email || '—').attr('href', d.email ? 'mailto:' + d.email : '#');
        $('#cmcalcPanelPhone').text(d.phone || '—').attr('href', d.phone ? 'tel:' + d.phone : '#');
        $('#cmcalcPanelAddress').text(d.address || '—');

        // Services
        var servicesHtml = '';
        if (d.services && d.services.length > 0) {
            servicesHtml = '<table class="cmcalc-panel-services-table">';
            d.services.forEach(function(s) {
                if (s.requires_quote) {
                    servicesHtml += '<tr><td>' + escAttr(s.title) + '</td><td><em>Offerte op maat</em></td></tr>';
                } else {
                    servicesHtml += '<tr><td>' + escAttr(s.title) + ' <small style="color:#999;">(' + s.quantity + ' ' + (s.unit || '') + ')</small></td><td>' + formatEuro(s.line_total) + '</td></tr>';
                }
                if (s.sub_options && s.sub_options.length > 0) {
                    s.sub_options.forEach(function(so) {
                        servicesHtml += '<tr><td colspan="2" class="cmcalc-panel-services-sub">→ ' + escAttr(so) + '</td></tr>';
                    });
                }
            });
            servicesHtml += '</table>';
        } else {
            servicesHtml = '<p style="color:#999;font-size:13px;">' + escAttr(d.service_summary || 'Geen diensten') + '</p>';
        }
        $('#cmcalcPanelServices').html(servicesHtml);

        // Location
        if (d.postcode) {
            $('#cmcalcPanelLocationSection').show();
            $('#cmcalcPanelPostcode').text(d.postcode + ' ' + (d.house_number || ''));
            $('#cmcalcPanelDistance').text(d.distance_km ? d.distance_km + ' km' : '—');
            $('#cmcalcPanelWerkgebied').text(d.nearest_werkgebied || '—');
            $('#cmcalcPanelTravel').text(d.travel_surcharge > 0 ? formatEuro(d.travel_surcharge) : 'Geen toeslag');
        } else {
            $('#cmcalcPanelLocationSection').hide();
        }

        // Extra info
        $('#cmcalcPanelPreferredDate').text(d.preferred_date || '—');
        $('#cmcalcPanelMessage').text(d.message || '—');
        if (!d.preferred_date && !d.message) {
            $('#cmcalcPanelExtraSection').hide();
        } else {
            $('#cmcalcPanelExtraSection').show();
        }

        // Total
        $('#cmcalcPanelTotal').text(formatEuro(d.total));

        // Notes
        $('#cmcalcPanelNotes').val(d.notes || '');
        $('#cmcalcNotesStatus').hide();
    }

    function updatePanelStatusBadge(status) {
        var label = statusLabels[status] || status;
        $('#cmcalcPanelStatusBadge').attr('class', 'cmcalc-status-badge cmcalc-status-' + status).text(label);
    }

    function closeBookingPanel() {
        $('#cmcalcBookingPanel').removeClass('open');
        $('.cmcalc-booking-row').removeClass('active');
        currentBookingId = null;
        currentBookingData = null;
    }

    // Close panel
    $(document).on('click', '#cmcalcPanelClose, #cmcalcPanelOverlay', closeBookingPanel);
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#cmcalcBookingPanel').hasClass('open')) closeBookingPanel();
    });

    // Status change
    $(document).on('change', '#cmcalcPanelStatus', function() {
        var newStatus = $(this).val();
        $.post(ajaxUrl, {
            action: 'cmcalc_update_booking_status',
            nonce: nonce,
            post_id: currentBookingId,
            status: newStatus
        }).done(function(res) {
            if (res.success) {
                updatePanelStatusBadge(newStatus);
                // Update table row badge
                var $row = $('tr[data-id="' + currentBookingId + '"]');
                $row.find('.cmcalc-status-badge').attr('class', 'cmcalc-status-badge cmcalc-status-' + newStatus).text(statusLabels[newStatus] || newStatus);
                showToast('Status bijgewerkt');
            }
        });
    });

    // Notes auto-save
    $(document).on('input', '#cmcalcPanelNotes', function() {
        clearTimeout(noteSaveTimer);
        var $notes = $(this);
        noteSaveTimer = setTimeout(function() {
            $.post(ajaxUrl, {
                action: 'cmcalc_save_booking_notes',
                nonce: nonce,
                post_id: currentBookingId,
                notes: $notes.val()
            }).done(function(res) {
                if (res.success) {
                    $('#cmcalcNotesStatus').text('Opgeslagen').show();
                    setTimeout(function() { $('#cmcalcNotesStatus').fadeOut(); }, 2000);
                }
            });
        }, 800);
    });

    // Email client
    $(document).on('click', '#cmcalcPanelEmailBtn', function() {
        if (!currentBookingData) return;
        $('#cmcalcEmailTo').val(currentBookingData.email);
        $('#cmcalcEmailSubjectField').val('Re: Uw boeking bij Cleanmasterzz');
        $('#cmcalcEmailMessage').val('');
        $('#cmcalcEmailModal').show();
    });

    $(document).on('click', '.cmcalc-email-cancel', function() {
        $(this).closest('.cmcalc-modal').hide();
    });

    $(document).on('click', '#cmcalcSendEmail', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Verzenden...');

        $.post(ajaxUrl, {
            action: 'cmcalc_email_client',
            nonce: nonce,
            post_id: currentBookingId,
            to: $('#cmcalcEmailTo').val(),
            subject: $('#cmcalcEmailSubjectField').val(),
            message: $('#cmcalcEmailMessage').val()
        }).done(function(res) {
            if (res.success) {
                showToast('Email verzonden!');
                $('#cmcalcEmailModal').hide();
                // Update notes in panel
                if (res.data && res.data.notes) {
                    $('#cmcalcPanelNotes').val(res.data.notes);
                }
            } else {
                showToast(res.data || 'Verzenden mislukt', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align:middle;margin-right:4px;"></span> Verzenden');
        });
    });

    // Resend to admin
    $(document).on('click', '#cmcalcPanelResendBtn', function() {
        if (!confirm('Boekingsoverzicht opnieuw verzenden naar uw admin e-mail?')) return;
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(ajaxUrl, {
            action: 'cmcalc_resend_booking',
            nonce: nonce,
            post_id: currentBookingId
        }).done(function(res) {
            if (res.success) {
                showToast('Boekingsoverzicht verzonden!');
            } else {
                showToast('Verzenden mislukt', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });

    // Edit booking
    $(document).on('click', '#cmcalcPanelEditBtn', function() {
        if (!currentBookingData) return;
        $('#cmcalcEditName').val(currentBookingData.name);
        $('#cmcalcEditEmail').val(currentBookingData.email);
        $('#cmcalcEditPhone').val(currentBookingData.phone);
        $('#cmcalcEditAddress').val(currentBookingData.address);
        $('#cmcalcEditDate').val(currentBookingData.preferred_date);
        $('#cmcalcEditMessage').val(currentBookingData.message);

        // Populate services for editing
        var servicesHtml = '';
        var services = currentBookingData.services || [];
        if (typeof services === 'string') {
            try { services = JSON.parse(services); } catch(e) { services = []; }
        }
        services.forEach(function(svc, idx) {
            servicesHtml += buildEditServiceRow(svc, idx);
        });
        $('#cmcalcEditServices').html(servicesHtml || '<p class="description">Geen diensten</p>');
        $('#cmcalcEditTravel').val(currentBookingData.travel_surcharge || 0);
        $('#cmcalcEditTotal').val(currentBookingData.total || 0);

        $('#cmcalcEditModal').show();
    });

    $(document).on('click', '.cmcalc-edit-cancel', function() {
        $(this).closest('.cmcalc-modal').hide();
    });

    // Add/remove service rows in edit modal
    $(document).on('click', '#cmcalcEditAddService', function() {
        var idx = $('#cmcalcEditServices .cmcalc-edit-service-row').length;
        // Remove "Geen diensten" placeholder if present
        $('#cmcalcEditServices .description').remove();
        $('#cmcalcEditServices').append(buildEditServiceRow({name:'', quantity:1, unit:'stuks', subtotal:0}, idx));
    });

    $(document).on('click', '.cmcalc-edit-svc-remove', function() {
        $(this).closest('.cmcalc-edit-service-row').remove();
    });

    $(document).on('click', '#cmcalcSaveEdit', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Opslaan...');

        // Collect edited services
        var editedServices = [];
        $('#cmcalcEditServices .cmcalc-edit-service-row').each(function() {
            editedServices.push({
                name: $(this).find('.cmcalc-edit-svc-name').val(),
                quantity: parseFloat($(this).find('.cmcalc-edit-svc-qty').val()) || 1,
                unit: $(this).find('.cmcalc-edit-svc-unit').val(),
                subtotal: parseFloat($(this).find('.cmcalc-edit-svc-price').val()) || 0
            });
        });

        $.post(ajaxUrl, {
            action: 'cmcalc_update_booking',
            nonce: nonce,
            post_id: currentBookingId,
            name: $('#cmcalcEditName').val(),
            email: $('#cmcalcEditEmail').val(),
            phone: $('#cmcalcEditPhone').val(),
            address: $('#cmcalcEditAddress').val(),
            date: $('#cmcalcEditDate').val(),
            message: $('#cmcalcEditMessage').val(),
            services: JSON.stringify(editedServices),
            travel_surcharge: $('#cmcalcEditTravel').val(),
            total: $('#cmcalcEditTotal').val()
        }).done(function(res) {
            if (res.success) {
                showToast('Boeking bijgewerkt!');
                $('#cmcalcEditModal').hide();
                // Refresh panel and table
                openBookingPanel(currentBookingId);
                loadBookings();
            } else {
                showToast('Opslaan mislukt', 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align:middle;margin-right:4px;"></span> Opslaan');
        });
    });

    // Delete booking
    $(document).on('click', '#cmcalcPanelDeleteBtn', function() {
        if (!confirm('Weet u zeker dat u deze boeking wilt verwijderen?')) return;

        $.post(ajaxUrl, {
            action: 'cmcalc_delete_booking',
            nonce: nonce,
            post_id: currentBookingId
        }).done(function(res) {
            if (res.success) {
                closeBookingPanel();
                $('tr[data-id="' + currentBookingId + '"]').fadeOut(300, function() { $(this).remove(); });
                showToast('Boeking verwijderd');
            }
        });
    });

    // Export CSV
    $(document).on('click', '#cmcalcExportBookings', function() {
        var params = new URLSearchParams({
            action: 'cmcalc_export_bookings',
            nonce: nonce,
            status: $('#cmcalcBookingStatusFilter').val() || '',
            search: $('#cmcalcBookingSearch').val() || '',
            date_from: $('#cmcalcDateFrom').val() || '',
            date_to: $('#cmcalcDateTo').val() || ''
        });
        window.location.href = ajaxUrl + '?' + params.toString();
    });

    // Init: load bookings if on boekingen tab
    if ($('#cmcalcBookingsBody').length) {
        loadBookings();
    }

    // ─── Extra Instellingen ───

    // Save teksten
    $('#cmcalcSaveTexts').on('click', function() {
        var data = {
            calc_title: $('#cmcalcCalcTitle').val(),
            btn_step1: $('#cmcalcBtnStep1').val(),
            btn_step2: $('#cmcalcBtnStep2').val(),
            btn_step3: $('#cmcalcBtnStep3').val(),
            disclaimer_text: $('#cmcalcDisclaimerText').val(),
            success_text: $('#cmcalcSuccessText').val()
        };
        saveSettings(data, '#cmcalcTextsStatus');
    });

    // Save email settings
    $('#cmcalcSaveEmail').on('click', function() {
        var data = {
            admin_email: $('#cmcalcAdminEmail').val(),
            email_subject: $('#cmcalcEmailSubject').val(),
            email_logo_url: $('#cmcalcEmailLogoUrl').val(),
            email_footer_text: $('#cmcalcEmailFooter').val(),
            email_customer_enabled: $('#cmcalcEmailCustomerEnabled').is(':checked') ? '1' : '0',
            email_status_enabled: $('#cmcalcEmailStatusEnabled').is(':checked') ? '1' : '0'
        };
        saveSettings(data, '#cmcalcEmailStatus');
    });

    // Email logo media picker
    $('#cmcalcEmailLogoBtn').on('click', function() {
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            showToast('Media library niet beschikbaar', 'error');
            return;
        }
        var frame = wp.media({ title: 'Kies een logo', multiple: false, library: { type: 'image' } });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#cmcalcEmailLogoUrl').val(attachment.url);
        });
        frame.open();
    });

    // Email preview
    $('#cmcalcPreviewEmail').on('click', function() {
        $.post(ajaxUrl, { action: 'cmcalc_preview_email', nonce: nonce }).done(function(res) {
            if (res.success) {
                var iframe = document.getElementById('cmcalcEmailPreviewFrame');
                var doc = iframe.contentDocument || iframe.contentWindow.document;
                doc.open();
                doc.write(res.data.html);
                doc.close();
                $('#cmcalcEmailPreviewModal').show();
            }
        });
    });

    // Save BTW settings
    $('#cmcalcSaveBtw').on('click', function() {
        var data = {
            btw_percentage: parseFloat($('#cmcalcBtwPercentage').val()) || 21,
            show_btw: $('#cmcalcShowBtw').val()
        };
        saveSettings(data, '#cmcalcBtwStatus');
    });

    function saveSettings(data, statusSelector) {
        // Merge with existing settings
        var existing = cmcalcAdmin.settings || {};
        var merged = $.extend({}, existing, data);

        $.post(ajaxUrl, {
            action: 'cmcalc_save_settings',
            nonce: nonce,
            settings: JSON.stringify(merged)
        }).done(function(res) {
            if (res.success) {
                cmcalcAdmin.settings = res.data;
                showToast('Instellingen opgeslagen!');
                if (statusSelector) {
                    $(statusSelector).text('Opgeslagen!').show();
                    setTimeout(function() { $(statusSelector).fadeOut(); }, 2000);
                }
            } else {
                showToast('Opslaan mislukt', 'error');
            }
        });
    }

    // ─── GitHub Token & Auto-updater ───

    $('#cmcalcSaveGithubToken').on('click', function() {
        var token = $('#cmcalcGithubToken').val().trim();
        $.post(ajaxUrl, {
            action: 'cmcalc_save_github_token',
            nonce: nonce,
            token: token
        }).done(function(res) {
            if (res.success) {
                showToast('GitHub token opgeslagen!');
                $('#cmcalcGithubTokenStatus').text('Opgeslagen!').show();
                setTimeout(function() { $('#cmcalcGithubTokenStatus').fadeOut(); }, 2000);
            } else {
                showToast('Opslaan mislukt: ' + (res.data || ''), 'error');
            }
        });
    });

    $('#cmcalcTestUpdate').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Controleren...');
        $.post(ajaxUrl, {
            action: 'cmcalc_check_update',
            nonce: nonce
        }).done(function(res) {
            if (res.success && res.data) {
                if (res.data.update_available) {
                    showToast('Update beschikbaar: v' + res.data.remote_version + '! Je wordt doorgestuurd naar Updates...');
                    setTimeout(function() {
                        window.location.href = cmcalcAdmin.adminUrl + 'update-core.php';
                    }, 1500);
                } else {
                    showToast('Je hebt de nieuwste versie (' + res.data.current_version + ')');
                }
            } else {
                var msg = 'Kon niet controleren';
                if (res.data && typeof res.data === 'object') {
                    msg += ': ' + (res.data.message || '') + ' | Body: ' + (res.data.body || '').substring(0, 200);
                    console.log('Update check debug:', res.data);
                } else {
                    msg += ': ' + (res.data || 'Controleer je token');
                }
                showToast(msg, 'error');
            }
        }).fail(function() {
            showToast('Verbinding mislukt', 'error');
        }).always(function() {
            $btn.prop('disabled', false).text('🔄 Check nu op updates');
        });
    });

    // ─── Calendar View ───

    var calendarDate = new Date();
    var allBookingsForCalendar = [];

    $('.cmcalc-view-btn').on('click', function() {
        $('.cmcalc-view-btn').removeClass('active');
        $(this).addClass('active');
        var view = $(this).data('view');
        if (view === 'calendar') {
            $('.cmcalc-table-wrap, .cmcalc-pagination').hide();
            $('#cmcalcCalendarView').show();
            loadAllBookingsForCalendar();
        } else {
            $('.cmcalc-table-wrap, .cmcalc-pagination').show();
            $('#cmcalcCalendarView').hide();
        }
    });

    $('.cmcalc-calendar-prev').on('click', function() {
        calendarDate.setMonth(calendarDate.getMonth() - 1);
        renderCalendar();
    });
    $('.cmcalc-calendar-next').on('click', function() {
        calendarDate.setMonth(calendarDate.getMonth() + 1);
        renderCalendar();
    });

    function loadAllBookingsForCalendar() {
        $.post(ajaxUrl, {
            action: 'cmcalc_get_bookings_page',
            nonce: nonce,
            status: $('#cmcalcBookingStatusFilter').val() || 'alle',
            search: '',
            date_from: '',
            date_to: '',
            bedrijf_id: $('#cmcalcBookingBedrijfFilter').val() || '',
            page: 1,
            per_page: 9999
        }).done(function(res) {
            if (res.success) {
                allBookingsForCalendar = res.data.bookings || [];
                renderCalendar();
            }
        });
    }

    function renderCalendar() {
        var year = calendarDate.getFullYear();
        var month = calendarDate.getMonth();
        var months = ['Januari','Februari','Maart','April','Mei','Juni','Juli','Augustus','September','Oktober','November','December'];
        $('#cmcalcCalendarMonth').text(months[month] + ' ' + year);

        var firstDay = new Date(year, month, 1).getDay(); // 0=Sun
        firstDay = firstDay === 0 ? 6 : firstDay - 1; // Convert to Mon=0
        var daysInMonth = new Date(year, month + 1, 0).getDate();

        var html = '<div class="cmcalc-calendar-weekdays">';
        ['Ma','Di','Wo','Do','Vr','Za','Zo'].forEach(function(d) {
            html += '<div class="cmcalc-calendar-weekday">' + d + '</div>';
        });
        html += '</div><div class="cmcalc-calendar-days">';

        // Empty cells before first day
        for (var i = 0; i < firstDay; i++) {
            html += '<div class="cmcalc-calendar-day cmcalc-calendar-day--empty"></div>';
        }

        // Days
        for (var d = 1; d <= daysInMonth; d++) {
            var dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            var dayBookings = allBookingsForCalendar.filter(function(b) {
                return b.preferred_date === dateStr || (b.date && b.date.substring(0, 10) === dateStr);
            });

            var isToday = (new Date().toISOString().substring(0,10) === dateStr);
            var classes = 'cmcalc-calendar-day';
            if (isToday) classes += ' cmcalc-calendar-day--today';
            if (dayBookings.length > 0) classes += ' cmcalc-calendar-day--has-bookings';

            html += '<div class="' + classes + '" data-date="' + dateStr + '">';
            html += '<span class="cmcalc-calendar-day-num">' + d + '</span>';

            dayBookings.forEach(function(b) {
                var statusClass = 'cmcalc-cal-status--' + (b.status || 'nieuw');
                html += '<div class="cmcalc-calendar-event ' + statusClass + '" data-id="' + b.id + '">';
                html += '<span class="cmcalc-cal-name">' + escAttr((b.name || '').substring(0,15)) + '</span>';
                html += '</div>';
            });

            html += '</div>';
        }

        html += '</div>';
        $('#cmcalcCalendarGrid').html(html);
    }

    // Click calendar event to open panel
    $(document).on('click', '.cmcalc-calendar-event', function(e) {
        e.stopPropagation();
        var id = $(this).data('id');
        if (id) openBookingPanel(id);
    });

    // ─── Helpers ───

    function buildEditServiceRow(svc, idx) {
        var name = (typeof svc === 'object') ? (svc.name || svc.service || svc.title || '') : svc;
        var qty = (typeof svc === 'object') ? (svc.quantity || svc.aantal || 1) : 1;
        var unit = (typeof svc === 'object') ? (svc.unit || svc.eenheid || 'stuks') : 'stuks';
        var price = (typeof svc === 'object') ? (svc.subtotal || svc.line_total || svc.price || 0) : 0;

        return '<div class="cmcalc-edit-service-row" data-index="' + idx + '">' +
            '<input type="text" class="cmcalc-edit-svc-name" value="' + escAttr(name) + '" placeholder="Dienst" style="flex:2;">' +
            '<input type="number" class="cmcalc-edit-svc-qty" value="' + qty + '" min="1" step="1" style="width:70px;" placeholder="Aantal">' +
            '<input type="text" class="cmcalc-edit-svc-unit" value="' + escAttr(unit) + '" style="width:80px;" placeholder="Eenheid">' +
            '<input type="number" class="cmcalc-edit-svc-price" value="' + parseFloat(price).toFixed(2) + '" step="0.01" min="0" style="width:90px;" placeholder="Bedrag">' +
            '<button type="button" class="button cmcalc-edit-svc-remove" title="Verwijderen">&times;</button>' +
            '</div>';
    }

    function escAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ─── Kortingscodes ───
    function loadDiscountCodes() {
        $.post(ajaxUrl, { action: 'cmcalc_get_discount_codes', nonce: nonce }).done(function(res) {
            if (res.success) renderDiscountTable(res.data);
        });
    }

    function renderDiscountTable(codes) {
        var $body = $('#cmcalcDiscountBody');
        if (!codes || codes.length === 0) {
            $body.html('<tr><td colspan="7" style="text-align:center;color:#999;">Nog geen kortingscodes</td></tr>');
            return;
        }
        var html = '';
        codes.forEach(function(c) {
            html += '<tr>';
            html += '<td><code style="background:#f1f3f5;padding:2px 8px;border-radius:4px;font-weight:600;">' + escAttr(c.code) + '</code></td>';
            html += '<td>' + (c.type === 'percentage' ? 'Percentage' : 'Vast bedrag') + '</td>';
            html += '<td>' + (c.type === 'percentage' ? c.value + '%' : '\u20AC' + parseFloat(c.value).toFixed(2)) + '</td>';
            html += '<td>' + (c.used || 0) + '</td>';
            html += '<td>' + (c.max_uses > 0 ? c.max_uses : '\u221E') + '</td>';
            html += '<td>' + (c.expires || '\u2014') + '</td>';
            html += '<td><button type="button" class="button cmcalc-delete-discount" data-code="' + escAttr(c.code) + '" title="Verwijderen" style="color:#dc3545;padding:0 8px;">\u2715</button></td>';
            html += '</tr>';
        });
        $body.html(html);
    }

    $('#cmcalcAddDiscount').on('click', function() {
        var code = $('#cmcalcDiscountCode').val().trim();
        if (!code) { showToast('Vul een code in', 'error'); return; }

        $.post(ajaxUrl, {
            action: 'cmcalc_save_discount_code',
            nonce: nonce,
            code: code,
            type: $('#cmcalcDiscountType').val(),
            value: $('#cmcalcDiscountValue').val(),
            max_uses: $('#cmcalcDiscountMaxUses').val() || 0,
            expires: $('#cmcalcDiscountExpires').val()
        }).done(function(res) {
            if (res.success) {
                showToast('Kortingscode toegevoegd!');
                renderDiscountTable(res.data);
                $('#cmcalcDiscountCode, #cmcalcDiscountValue, #cmcalcDiscountMaxUses, #cmcalcDiscountExpires').val('');
            } else {
                showToast(res.data || 'Fout', 'error');
            }
        });
    });

    $(document).on('click', '.cmcalc-delete-discount', function() {
        var code = $(this).data('code');
        if (!confirm('Kortingscode "' + code + '" verwijderen?')) return;
        $.post(ajaxUrl, {
            action: 'cmcalc_delete_discount_code',
            nonce: nonce,
            code: code
        }).done(function(res) {
            if (res.success) {
                showToast('Verwijderd');
                renderDiscountTable(res.data);
            }
        });
    });

    // Load discount codes on page load
    loadDiscountCodes();

    // ─── WhatsApp Save ───
    $('#cmcalcSaveWhatsApp').on('click', function() {
        saveSettings({ whatsapp_number: $('#cmcalcWhatsApp').val().trim() }, '#cmcalcWhatsAppStatus');
    });

    // ─── SMTP ───

    // Toggle SMTP velden actief/inactief
    $('#cmcalcSmtpEnabled').on('change', function() {
        var enabled = $(this).is(':checked');
        $('#cmcalcSmtpFields').css({ opacity: enabled ? '1' : '.5', 'pointer-events': enabled ? '' : 'none' });
    });

    // Auto-selecteer poort bij wijzigen encryptie
    $('#cmcalcSmtpEncryption').on('change', function() {
        var enc = $(this).val();
        var portMap = { tls: 587, ssl: 465, '': 25 };
        if (portMap[enc] !== undefined) {
            $('#cmcalcSmtpPort').val(portMap[enc]);
        }
    });

    $('#cmcalcSaveSmtp').on('click', function() {
        var $btn = $(this);
        var $status = $('#cmcalcSmtpStatus');
        $btn.prop('disabled', true).text('Opslaan...');

        var smtp = {
            enabled:    $('#cmcalcSmtpEnabled').is(':checked') ? '1' : '0',
            host:       $('#cmcalcSmtpHost').val().trim(),
            port:       $('#cmcalcSmtpPort').val(),
            encryption: $('#cmcalcSmtpEncryption').val(),
            username:   $('#cmcalcSmtpUsername').val().trim(),
            password:   $('#cmcalcSmtpPassword').val(),
            from_name:  $('#cmcalcSmtpFromName').val().trim(),
            from_email: $('#cmcalcSmtpFromEmail').val().trim()
        };

        $.post(ajaxUrl, {
            action: 'cmcalc_save_smtp',
            nonce:  nonce,
            smtp:   JSON.stringify(smtp)
        }).done(function(res) {
            if (res.success) {
                $status.text('Opgeslagen ✓').css('color', '#28a745').show();
                // Vervang wachtwoord placeholder
                if ($('#cmcalcSmtpPassword').val()) {
                    $('#cmcalcSmtpPassword').val('••••••••').attr('placeholder', 'Huidig wachtwoord verborgen — vul in om te wijzigen');
                }
                showToast('SMTP-instellingen opgeslagen');
            } else {
                $status.text('Fout bij opslaan').css('color', '#dc3545').show();
            }
        }).fail(function() {
            $status.text('Verbinding mislukt').css('color', '#dc3545').show();
        }).always(function() {
            $btn.prop('disabled', false).text('Opslaan');
            setTimeout(function() { $status.fadeOut(); }, 3500);
        });
    });

    $('#cmcalcTestSmtp').on('click', function() {
        var $btn    = $(this);
        var $result = $('#cmcalcSmtpTestResult');
        var to = prompt('Testmail versturen naar:', $('#cmcalcAdminEmail').val() || '');
        if (!to) return;

        $btn.prop('disabled', true).text('Versturen...');
        $result.hide();

        $.post(ajaxUrl, {
            action: 'cmcalc_test_smtp',
            nonce:  nonce,
            to:     to
        }).done(function(res) {
            if (res.success) {
                $result.text('✓ ' + (res.data.message || 'Testmail verzonden!')).css({ background: '#f0fdf4', color: '#166534', border: '1px solid #bbf7d0' }).show();
            } else {
                var msg = res.data && res.data.message ? res.data.message : (res.data || 'Onbekende fout');
                $result.text('✗ ' + msg).css({ background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca' }).show();
            }
        }).fail(function() {
            $result.text('Verbindingsfout').css({ background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca' }).show();
        }).always(function() {
            $btn.prop('disabled', false).text('📨 Testmail versturen');
        });
    });

    // ─── Klantportaal pagina ───

    $('#cmcalcSavePortalPage').on('click', function() {
        var $btn    = $(this);
        var $status = $('#cmcalcPortalStatus');
        $btn.prop('disabled', true).text('Opslaan...');

        $.post(ajaxUrl, {
            action:  'cmcalc_save_portal_page',
            nonce:   nonce,
            page_id: $('#cmcalcPortalPage').val()
        }).done(function(res) {
            if (res.success) {
                $status.text('Opgeslagen ✓').css('color', '#28a745').show();
                showToast('Portaalpagina opgeslagen');
            } else {
                $status.text('Fout').css('color', '#dc3545').show();
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Opslaan');
            setTimeout(function() { $status.fadeOut(); }, 3000);
        });
    });

})(jQuery);
