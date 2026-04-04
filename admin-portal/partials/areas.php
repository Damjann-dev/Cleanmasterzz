<?php
$editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$area = null;
if ($editing) {
    foreach ($areas as $a) { if ($a['id'] == $editing) { $area = $a; break; } }
}
$company_map = [];
foreach ($companies as $c) { $company_map[$c['id']] = $c['name']; }
?>
<?php if ($area): ?>
<div class="topbar">
    <div>
        <a href="/?action=areas" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Terug</a>
        <h1 class="page-title">Werkgebied bewerken</h1>
    </div>
</div>
<form method="POST" action="/?action=save_area">
    <input type="hidden" name="id" value="<?= h($area['id']) ?>">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">
        <div class="card" style="padding:24px">
            <div class="form-row">
                <div class="form-group">
                    <label>Naam</label>
                    <input type="text" name="name" value="<?= h($area['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Postcode</label>
                    <input type="text" name="postcode" value="<?= h($area['postcode']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Gratis km</label>
                    <input type="number" name="free_km" value="<?= h($area['free_km']) ?>" step="0.1" min="0">
                </div>
                <div class="form-group">
                    <label>Bedrijf</label>
                    <select name="bedrijf_id">
                        <option value="0">— Geen —</option>
                        <?php foreach ($companies as $c): ?>
                        <option value="<?= h($c['id']) ?>" <?= $area['bedrijf_id']==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" name="lat" value="<?= h($area['lat']) ?>" step="0.000001">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" name="lon" value="<?= h($area['lon']) ?>" step="0.000001">
                </div>
            </div>
        </div>
        <div>
            <div class="card" style="padding:20px">
                <h3 style="font-size:13px;font-weight:700;margin-bottom:14px">Status</h3>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="active" value="1" <?= $area['active']?'checked':'' ?> style="width:18px;height:18px">
                    <span style="font-size:13px;color:var(--dim)">Werkgebied actief</span>
                </label>
            </div>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                <button type="submit" class="btn btn-primary">Opslaan</button>
                <a href="/?action=delete_area&id=<?= h($area['id']) ?>" class="btn btn-danger"
                   onclick="return confirm('Werkgebied verwijderen?')">Verwijderen</a>
            </div>
        </div>
    </div>
</form>
<?php else: ?>
<div class="topbar">
    <h1 class="page-title">Werkgebieden</h1>
    <a href="/?action=new_area" class="btn btn-primary btn-sm">+ Nieuw werkgebied</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Naam</th><th>Postcode</th><th>Bedrijf</th><th>Gratis km</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($areas)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">Geen werkgebieden gevonden</td></tr>
        <?php else: foreach ($areas as $a): ?>
            <tr>
                <td style="color:var(--text);font-weight:600"><?= h($a['name']) ?></td>
                <td><?= h($a['postcode']) ?></td>
                <td><?= h($company_map[$a['bedrijf_id']] ?? '—') ?></td>
                <td><?= h($a['free_km']) ?> km</td>
                <td><?= $a['active']
                    ? '<span style="color:#6ee7b7;font-size:11px;font-weight:700">● ACTIEF</span>'
                    : '<span style="color:var(--muted);font-size:11px;font-weight:700">○ INACTIEF</span>' ?></td>
                <td><a href="/?action=areas&edit=<?= h($a['id']) ?>" class="btn btn-ghost btn-sm">Bewerken</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
