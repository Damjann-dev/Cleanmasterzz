<?php
// $settings = array from wp_api('settings'), $discount_codes = array
$s = $settings ?: [];
$smtp = $s['smtp'] ?? [];
?>
<div class="topbar">
    <h1 class="page-title">Instellingen</h1>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= h($err) ?></div><?php endif; ?>

<!-- Tab navigation -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:0">
    <?php foreach (['algemeen'=>'Algemeen','email'=>'E-mail & SMTP','btw'=>'BTW & Prijzen','codes'=>'Kortingscodes'] as $t=>$l): ?>
    <button type="button" class="settings-tab-btn <?= ($t==='algemeen'?'active':'') ?>" data-tab="<?= $t ?>"
        style="padding:8px 18px;background:none;border:none;border-bottom:2px solid <?= ($t==='algemeen'?'var(--primary)':'transparent') ?>;color:<?= ($t==='algemeen'?'var(--text)':'var(--muted)') ?>;font-family:var(--font);font-size:13px;font-weight:600;cursor:pointer;margin-bottom:-1px;transition:.15s">
        <?= h($l) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Algemeen -->
<div class="settings-tab" id="tab-algemeen">
<form method="POST" action="/?action=save_settings&tab=algemeen">
<div class="card" style="padding:24px;margin-bottom:16px">
    <h3 style="font-size:13px;font-weight:700;margin-bottom:16px">Calculator teksten</h3>
    <div class="form-group">
        <label>Titel calculator</label>
        <input type="text" name="calc_title" value="<?= h($s['calc_title'] ?? '') ?>">
    </div>
    <div class="form-row">
        <div class="form-group"><label>Knoptekst stap 1</label><input type="text" name="btn_step1" value="<?= h($s['btn_step1'] ?? '') ?>"></div>
        <div class="form-group"><label>Knoptekst stap 2</label><input type="text" name="btn_step2" value="<?= h($s['btn_step2'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Knoptekst stap 3</label><input type="text" name="btn_step3" value="<?= h($s['btn_step3'] ?? '') ?>"></div>
        <div class="form-group"><label>WhatsApp nummer</label><input type="text" name="whatsapp_number" value="<?= h($s['whatsapp_number'] ?? '') ?>" placeholder="+31612345678"></div>
    </div>
    <div class="form-group">
        <label>Disclaimer tekst</label>
        <textarea name="disclaimer_text" rows="3" style="resize:vertical"><?= h($s['disclaimer_text'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
        <label>Succesbericht (na boeking)</label>
        <textarea name="success_text" rows="3" style="resize:vertical"><?= h($s['success_text'] ?? '') ?></textarea>
    </div>
</div>
<button type="submit" class="btn btn-primary">Opslaan</button>
</form>
</div>

<!-- E-mail & SMTP -->
<div class="settings-tab" id="tab-email" style="display:none">
<form method="POST" action="/?action=save_settings&tab=email">
<div class="card" style="padding:24px;margin-bottom:16px">
    <h3 style="font-size:13px;font-weight:700;margin-bottom:16px">E-mailinstellingen</h3>
    <div class="form-row">
        <div class="form-group"><label>Admin e-mail</label><input type="email" name="admin_email" value="<?= h($s['admin_email'] ?? '') ?>"></div>
        <div class="form-group"><label>E-mail onderwerp</label><input type="text" name="email_subject" value="<?= h($s['email_subject'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Logo URL</label><input type="url" name="email_logo_url" value="<?= h($s['email_logo_url'] ?? '') ?>"></div>
    <div class="form-group"><label>Footer tekst</label><input type="text" name="email_footer_text" value="<?= h($s['email_footer_text'] ?? '') ?>"></div>
    <div style="display:flex;gap:20px;margin-top:6px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="email_customer_enabled" value="1" <?= !empty($s['email_customer_enabled'])?'checked':'' ?>>
            <span style="font-size:13px;color:var(--dim)">Bevestigingsmail naar klant</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="email_status_enabled" value="1" <?= !empty($s['email_status_enabled'])?'checked':'' ?>>
            <span style="font-size:13px;color:var(--dim)">Statusupdate e-mails</span>
        </label>
    </div>
</div>
<div class="card" style="padding:24px;margin-bottom:16px">
    <h3 style="font-size:13px;font-weight:700;margin-bottom:16px">SMTP</h3>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:14px">
        <input type="checkbox" name="smtp_enabled" value="1" <?= !empty($smtp['enabled'])?'checked':'' ?>>
        <span style="font-size:13px;color:var(--dim)">SMTP inschakelen</span>
    </label>
    <div class="form-row">
        <div class="form-group"><label>SMTP Host</label><input type="text" name="smtp_host" value="<?= h($smtp['host'] ?? '') ?>"></div>
        <div class="form-group"><label>Poort</label><input type="number" name="smtp_port" value="<?= h($smtp['port'] ?? 587) ?>" style="width:100px"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Encryptie</label>
            <select name="smtp_encryption">
                <?php foreach (['tls'=>'TLS','ssl'=>'SSL','none'=>'Geen'] as $v=>$l): ?>
                <option value="<?= h($v) ?>" <?= ($smtp['encryption']??'tls')===$v?'selected':'' ?>><?= h($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Gebruikersnaam</label><input type="text" name="smtp_username" value="<?= h($smtp['username'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Wachtwoord (laat leeg om te bewaren)</label><input type="password" name="smtp_password" placeholder="••••••••"></div>
        <div class="form-group"><label>Afzendernaam</label><input type="text" name="smtp_from_name" value="<?= h($smtp['from_name'] ?? '') ?>"></div>
    </div>
    <div class="form-group"><label>Afzender e-mail</label><input type="email" name="smtp_from_email" value="<?= h($smtp['from_email'] ?? '') ?>"></div>
</div>
<button type="submit" class="btn btn-primary">Opslaan</button>
</form>
</div>

<!-- BTW & Prijzen -->
<div class="settings-tab" id="tab-btw" style="display:none">
<form method="POST" action="/?action=save_settings&tab=btw">
<div class="card" style="padding:24px;margin-bottom:16px">
    <h3 style="font-size:13px;font-weight:700;margin-bottom:16px">BTW & prijsweergave</h3>
    <div class="form-row">
        <div class="form-group">
            <label>BTW percentage (%)</label>
            <input type="number" name="btw_percentage" value="<?= h($s['btw_percentage'] ?? 21) ?>" min="0" max="100" step="1" style="width:100px">
        </div>
        <div class="form-group">
            <label>Prijsweergave</label>
            <select name="show_btw">
                <option value="incl" <?= ($s['show_btw']??'incl')==='incl'?'selected':'' ?>>Inclusief BTW</option>
                <option value="excl" <?= ($s['show_btw']??'')==='excl'?'selected':'' ?>>Exclusief BTW</option>
            </select>
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary">Opslaan</button>
</form>
</div>

<!-- Kortingscodes -->
<div class="settings-tab" id="tab-codes" style="display:none">
<div class="topbar" style="margin-bottom:16px">
    <h3 style="font-size:15px;font-weight:700">Kortingscodes</h3>
    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('newCodeForm').style.display=document.getElementById('newCodeForm').style.display?'':'none'">+ Nieuwe code</button>
</div>
<div id="newCodeForm" style="display:none">
<div class="card" style="padding:20px;margin-bottom:16px">
<form method="POST" action="/?action=add_discount_code">
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="margin:0;flex:1;min-width:120px"><label>Code</label><input type="text" name="code" placeholder="ZOMER10" style="text-transform:uppercase" required></div>
        <div class="form-group" style="margin:0"><label>Type</label><select name="type"><option value="percentage">Percentage</option><option value="fixed">Vast bedrag</option></select></div>
        <div class="form-group" style="margin:0;width:90px"><label>Waarde</label><input type="number" name="value" step="0.01" min="0" required></div>
        <div class="form-group" style="margin:0;width:90px"><label>Max gebruik</label><input type="number" name="max_uses" min="0" placeholder="0=∞"></div>
        <div class="form-group" style="margin:0"><label>Verloopt op</label><input type="date" name="expires_at"></div>
        <button type="submit" class="btn btn-primary btn-sm">Aanmaken</button>
    </div>
</form>
</div>
</div>

<div class="card">
    <table>
        <thead><tr><th>Code</th><th>Type</th><th>Waarde</th><th>Gebruik</th><th>Verloopt</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($discount_codes)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">Geen kortingscodes</td></tr>
        <?php else: foreach ($discount_codes as $code): ?>
            <tr>
                <td style="font-weight:700;color:var(--text);letter-spacing:.05em"><?= h($code['code']) ?></td>
                <td><?= h($code['type']==='percentage'?'Percentage':'Vast bedrag') ?></td>
                <td><?= $code['type']==='percentage' ? h($code['value']).'%' : fmt_eur($code['value']) ?></td>
                <td style="color:var(--muted)"><?= h($code['used']) ?><?= $code['max_uses']>0?' / '.h($code['max_uses']).' max':' / ∞' ?></td>
                <td style="color:var(--muted)"><?= h($code['expires_at'] ?: '—') ?></td>
                <td><form method="POST" action="/?action=delete_discount_code" style="margin:0">
                    <input type="hidden" name="code" value="<?= h($code['code']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Code verwijderen?')">Verwijderen</button>
                </form></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
</div>

<script>
document.querySelectorAll('.settings-tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.settings-tab-btn').forEach(function(b) {
            b.style.borderBottomColor = 'transparent';
            b.style.color = 'var(--muted)';
        });
        document.querySelectorAll('.settings-tab').forEach(function(t) { t.style.display='none'; });
        this.style.borderBottomColor = 'var(--primary)';
        this.style.color = 'var(--text)';
        document.getElementById('tab-' + this.dataset.tab).style.display = '';
    });
});
// Restore active tab after form submit
var activeTab = new URLSearchParams(location.search).get('tab') || 'algemeen';
var btn = document.querySelector('[data-tab="'+activeTab+'"]');
if (btn) btn.click();
</script>
