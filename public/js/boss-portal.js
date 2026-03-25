/* CleanMasterzz Boss Portal JS */
(function ($) {
    'use strict';

    // ─── Auth tab switching ───────────────────────────────────────────────────
    $(document).on('click', '.cm-boss-tab', function () {
        var tab = $(this).data('tab');
        $('.cm-boss-tab').removeClass('active');
        $(this).addClass('active');
        $('.cm-boss-tab-content').removeClass('active');
        $('#cm-tab-' + tab).addClass('active');
    });

    // ─── Helper: show notice ──────────────────────────────────────────────────
    function showNotice($el, msg, type) {
        $el.removeClass('success error').addClass(type).text(msg).show();
        if (type === 'success') {
            setTimeout(function () { $el.fadeOut(); }, 4000);
        }
    }

    // ─── Login form ───────────────────────────────────────────────────────────
    $(document).on('submit', '#cmBossLoginForm', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn  = $form.find('button[type="submit"]');
        var $notice = $('#cmBossLoginNotice');

        $btn.prop('disabled', true).text('Bezig...');

        $.post(cmcalcBoss.ajax_url, {
            action:   'cmcalc_boss_login',
            nonce:    cmcalcBoss.nonce,
            email:    $form.find('[name="email"]').val(),
            password: $form.find('[name="password"]').val(),
        })
        .done(function (res) {
            if (res.success) {
                showNotice($notice, 'Ingelogd! Pagina wordt herladen...', 'success');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                showNotice($notice, res.data || 'Inloggen mislukt.', 'error');
                $btn.prop('disabled', false).text('Inloggen');
            }
        })
        .fail(function () {
            showNotice($notice, 'Verbindingsfout. Probeer opnieuw.', 'error');
            $btn.prop('disabled', false).text('Inloggen');
        });
    });

    // ─── Register form ────────────────────────────────────────────────────────
    $(document).on('submit', '#cmBossRegisterForm', function (e) {
        e.preventDefault();
        var $form   = $(this);
        var $btn    = $form.find('button[type="submit"]');
        var $notice = $('#cmBossRegisterNotice');

        $btn.prop('disabled', true).text('Bezig...');

        $.post(cmcalcBoss.ajax_url, {
            action:       'cmcalc_boss_register',
            nonce:        cmcalcBoss.nonce,
            email:        $form.find('[name="email"]').val(),
            password:     $form.find('[name="password"]').val(),
            first_name:   $form.find('[name="first_name"]').val(),
            last_name:    $form.find('[name="last_name"]').val(),
            company_name: $form.find('[name="company_name"]').val(),
            phone:        $form.find('[name="phone"]').val(),
        })
        .done(function (res) {
            if (res.success) {
                showNotice($notice, res.data.message + ' Pagina wordt herladen...', 'success');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showNotice($notice, res.data || 'Registratie mislukt.', 'error');
                $btn.prop('disabled', false).text('Account aanmaken');
            }
        })
        .fail(function () {
            showNotice($notice, 'Verbindingsfout. Probeer opnieuw.', 'error');
            $btn.prop('disabled', false).text('Account aanmaken');
        });
    });

    // ─── Logout ───────────────────────────────────────────────────────────────
    $(document).on('click', '#cmBossLogout', function () {
        $.post(cmcalcBoss.ajax_url, {
            action: 'cmcalc_boss_logout',
            nonce:  cmcalcBoss.nonce,
        }).always(function () {
            location.reload();
        });
    });

    // ─── Send message form ────────────────────────────────────────────────────
    $(document).on('submit', '#cmBossMessageForm', function (e) {
        e.preventDefault();
        var $form   = $(this);
        var $btn    = $form.find('button[type="submit"]');
        var $notice = $('#cmBossMsgNotice');

        $btn.prop('disabled', true).text('Versturen...');

        $.post(cmcalcBoss.ajax_url, {
            action:  'cmcalc_boss_send_msg',
            nonce:   cmcalcBoss.nonce,
            subject: $form.find('[name="subject"]').val() || 'Bericht van klant',
            body:    $form.find('[name="body"]').val(),
        })
        .done(function (res) {
            if (res.success) {
                showNotice($notice, res.data.message, 'success');
                $form[0].reset();
                // Ververs berichtenlijst
                loadMessages();
            } else {
                showNotice($notice, res.data || 'Verzenden mislukt.', 'error');
            }
            $btn.prop('disabled', false).text('Verstuur bericht');
        })
        .fail(function () {
            showNotice($notice, 'Verbindingsfout.', 'error');
            $btn.prop('disabled', false).text('Verstuur bericht');
        });
    });

    // ─── Profile form ─────────────────────────────────────────────────────────
    $(document).on('submit', '#cmBossProfileForm', function (e) {
        e.preventDefault();
        var $form   = $(this);
        var $btn    = $form.find('button[type="submit"]');
        var $notice = $('#cmBossProfileNotice');

        $btn.prop('disabled', true).text('Opslaan...');

        $.post(cmcalcBoss.ajax_url, {
            action:           'cmcalc_boss_update_profile',
            nonce:            cmcalcBoss.nonce,
            first_name:       $form.find('[name="first_name"]').val(),
            last_name:        $form.find('[name="last_name"]').val(),
            company_name:     $form.find('[name="company_name"]').val(),
            phone:            $form.find('[name="phone"]').val(),
            current_password: $form.find('[name="current_password"]').val(),
            new_password:     $form.find('[name="new_password"]').val(),
        })
        .done(function (res) {
            if (res.success) {
                showNotice($notice, res.data.message, 'success');
                $form.find('[name="current_password"], [name="new_password"]').val('');
            } else {
                showNotice($notice, res.data || 'Opslaan mislukt.', 'error');
            }
            $btn.prop('disabled', false).text('Opslaan');
        })
        .fail(function () {
            showNotice($notice, 'Verbindingsfout.', 'error');
            $btn.prop('disabled', false).text('Opslaan');
        });
    });

    // ─── Auto-load messages on berichten tab ──────────────────────────────────
    function loadMessages() {
        var $list = $('.cm-boss-messages');
        if (!$list.length) return;

        $.post(cmcalcBoss.ajax_url, {
            action: 'cmcalc_boss_get_msgs',
            nonce:  cmcalcBoss.nonce,
        })
        .done(function (res) {
            if (!res.success || !res.data.length) return;
            // Re-render messages — simpele aanpak: reload als er nieuwe zijn
            // Voor een echte SPA-aanpak zou je de DOM bijwerken
        });
    }

    // Laad berichten als de pagina op berichten tab staat
    if (window.location.search.indexOf('tab=berichten') !== -1) {
        loadMessages();
    }

})(jQuery);
