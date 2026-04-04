<?php
$editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$company = null;
if ($editing) {
    foreach ($companies as $c) { if ($c['id'] == $editing) { $company = $c; break; } }
}
?>
<?php if ($company): ?>
<div class="topbar">
    <div>
        <a href="/?action=companies" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Terug</a>
        <h1 class="page-title">Bedrijf bewerken</h1>
    </div>
</div>
<form method="POST" action="/?action=save_company">
    <input type="hidden" name="id" value="<?= h($company['id']) ?>">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
        <div class="card" style="padding:24px">
            <div class="form-group">
                <label>Bedrijfsnaam</label>
                <input type="text" name="name" value="<?= h($company['name']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Adres</label>
                    <input type="text" name="address" value="<?= h($company['address']) ?>">
                </div>
                <div class="form-group">
                    <label>Huisnummer</label>
                    <input type="text" name="huisnummer" value="<?= h($company['huisnummer']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Postcode</label>
                    <input type="text" name="postcode" value="<?= h($company['postcode']) ?>">
                </div>
                <div class="form-group">
                    <label>Telefoon</label>
                    <input type="text" name="phone" value="<?= h($company['phone']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= h($company['email']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" name="lat" value="<?= h($company['lat']) ?>" step="0.000001">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" name="lon" value="<?= h($company['lon']) ?>" step="0.000001">
                </div>
            </div>
        </div>
        <div>
            <div class="card" style="padding:20px">
                <h3 style="font-size:13px;font-weight:700;margin-bottom:14px">Status</h3>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="active" value="1" <?= $company['active']?'checked':'' ?> style="width:18px;height:18px">
                    <span style="font-size:13px;color:var(--dim)">Bedrijf actief</span>
                </label>
            </div>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                <button type="submit" class="btn btn-primary">Opslaan</button>
                <a href="/?action=delete_company&id=<?= h($company['id']) ?>" class="btn btn-danger"
                   onclick="return confirm('Bedrijf verwijderen?')">Verwijderen</a>
            </div>
        </div>
    </div>
</form>
<?php else: ?>
<div class="topbar">
    <h1 class="page-title">Bedrijven</h1>
    <a href="/?action=new_company" class="btn btn-primary btn-sm">+ Nieuw bedrijf</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Naam</th><th>Adres</th><th>Telefoon</th><th>E-mail</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($companies)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">Geen bedrijven gevonden</td></tr>
        <?php else: foreach ($companies as $c): ?>
            <tr>
                <td style="color:var(--text);font-weight:600"><?= h($c['name']) ?></td>
                <td><?= h($c['address'] . ' ' . $c['huisnummer'] . ', ' . $c['postcode']) ?></td>
                <td><?= h($c['phone']) ?></td>
                <td><?= h($c['email']) ?></td>
                <td><?= $c['active']
                    ? '<span style="color:#6ee7b7;font-size:11px;font-weight:700">● ACTIEF</span>'
                    : '<span style="color:var(--muted);font-size:11px;font-weight:700">○ INACTIEF</span>' ?></td>
                <td><a href="/?action=companies&edit=<?= h($c['id']) ?>" class="btn btn-ghost btn-sm">Bewerken</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
