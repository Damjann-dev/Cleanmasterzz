<?php
// Requires: $services (array from wp_api), $companies (array)
$editing = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$service = null;
if ($editing) {
    foreach ($services as $s) { if ($s['id'] == $editing) { $service = $s; break; } }
}
$unit_labels = ['m2'=>'m²','meter'=>'Meter','stuk'=>'Stuk(s)','paneel'=>'Paneel/panelen','raam'=>'Ra(a)m(en)','vast'=>'Vast bedrag'];
$icon_opts   = ['window'=>'🪟 Ramen','terrace'=>'🌿 Terras','facade'=>'🏗️ Gevel','pressure'=>'💧 Hogedrukreiniger','solar'=>'☀️ Zonnepanelen','construction'=>'🏚️ Bouwreiniging','roof'=>'🏠 Dakgoot','clean'=>'🧹 Algemeen','car'=>'🚗 Auto','custom'=>'⭐ Overig'];
?>

<?php if ($service): ?>
<!-- ─── Edit Service ─────────────────────────────────────────────────── -->
<div class="topbar">
    <div>
        <a href="/?action=services" class="btn btn-ghost btn-sm" style="margin-bottom:8px">← Terug naar diensten</a>
        <h1 class="page-title">Dienst bewerken</h1>
    </div>
</div>
<form method="POST" action="/?action=save_service" id="serviceForm">
    <input type="hidden" name="id" value="<?= h($service['id']) ?>">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">
        <div>
            <div class="card" style="padding:24px">
                <div class="form-row">
                    <div class="form-group">
                        <label>Naam van dienst</label>
                        <input type="text" name="title" value="<?= h($service['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Icoon</label>
                        <select name="icon">
                            <?php foreach ($icon_opts as $v => $l): ?>
                            <option value="<?= h($v) ?>" <?= $service['icon']==$v?'selected':'' ?>><?= h($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Basisprijs (€)</label>
                        <input type="number" name="base_price" value="<?= h($service['base_price']) ?>" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Eenheid</label>
                        <select name="price_unit">
                            <?php foreach ($unit_labels as $v => $l): ?>
                            <option value="<?= h($v) ?>" <?= $service['price_unit']==$v?'selected':'' ?>><?= h($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimumprijs (€)</label>
                        <input type="number" name="minimum_price" value="<?= h($service['minimum_price']) ?>" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Korting (%)</label>
                        <input type="number" name="discount" value="<?= h($service['discount']) ?>" step="1" min="0" max="100">
                    </div>
                </div>
                <div class="form-group">
                    <label>Sorteervolgorde</label>
                    <input type="number" name="sort_order" value="<?= h($service['sort_order']) ?>" min="0" style="width:120px">
                </div>
            </div>

            <!-- Sub-opties -->
            <div class="card" style="margin-top:16px">
                <div class="card-header">
                    <h3>Sub-opties</h3>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addSubOption()">+ Toevoegen</button>
                </div>
                <div id="subOptList" style="padding:0 20px"></div>
                <input type="hidden" name="sub_options_json" id="subOptionsJson" value="<?= h(json_encode($service['sub_options'])) ?>">
            </div>
        </div>

        <div>
            <div class="card" style="padding:20px">
                <h3 style="font-size:13px;font-weight:700;margin-bottom:16px">Status</h3>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="active" value="1" <?= $service['active']?'checked':'' ?> style="width:18px;height:18px">
                    <span style="font-size:13px;color:var(--dim)">Dienst actief</span>
                </label>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                    <input type="checkbox" name="requires_quote" value="1" <?= $service['requires_quote']?'checked':'' ?> style="width:18px;height:18px">
                    <span style="font-size:13px;color:var(--dim)">Offerte vereist</span>
                </label>
            </div>
            <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px">
                <button type="submit" class="btn btn-primary">Opslaan</button>
                <a href="/?action=delete_service&id=<?= h($service['id']) ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Dienst verwijderen?')">Verwijderen</a>
            </div>
        </div>
    </div>
</form>

<script>
var subOpts = <?= json_encode($service['sub_options']) ?>;
var unitLabels = {per_keer:'per keer (eenmalig)',per_m2:'per m²',per_raam:'per raam',per_meter:'per meter',per_uur:'per uur',per_stuk:'per stuk'};

function renderSubOpts() {
    var el = document.getElementById('subOptList');
    if (!subOpts.length) { el.innerHTML = '<p style="padding:16px 0;color:var(--muted);font-size:13px">Geen sub-opties</p>'; return; }
    el.innerHTML = subOpts.map(function(o,i) {
        return '<div style="padding:14px 0;border-bottom:1px solid var(--border)">' +
            '<div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">' +
                '<div style="flex:1;min-width:160px"><label>Label</label><input type="text" value="'+escH(o.label)+'" oninput="subOpts['+i+'].label=this.value;syncJson()"></div>' +
                '<div><label>Type</label><select onchange="subOpts['+i+'].type=this.value;renderSubOpts()" style="width:120px">'+
                    '<option value="checkbox"'+(o.type==='checkbox'?' selected':'')+'>Checkbox</option>'+
                    '<option value="select"'+(o.type==='select'?' selected':'')+'>Select</option>'+
                '</select></div>'+
                (o.type==='checkbox' ? '<div><label>Toeslag (€)</label><input type="number" value="'+(o.surcharge||0)+'" step="0.01" min="0" style="width:90px" oninput="subOpts['+i+'].surcharge=parseFloat(this.value)||0;syncJson()"></div>' : '')+
                '<div><label>Per</label><select style="width:130px" onchange="subOpts['+i+'].price_type=this.value;syncJson()">'+
                    Object.keys(unitLabels).map(function(k){return '<option value="'+k+'"'+(( o.price_type||'per_keer')===k?' selected':'')+'>'+unitLabels[k]+'</option>';}).join('')+
                '</select></div>'+
                '<button type="button" onclick="subOpts.splice('+i+',1);renderSubOpts()" style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:12px;margin-bottom:1px">✕</button>'+
            '</div>'+
        '</div>';
    }).join('');
    syncJson();
}
function syncJson() { document.getElementById('subOptionsJson').value = JSON.stringify(subOpts); }
function addSubOption() { subOpts.push({label:'',type:'checkbox',surcharge:0,price_type:'per_keer'}); renderSubOpts(); }
function escH(s){ var d=document.createElement('div');d.textContent=s;return d.innerHTML; }
renderSubOpts();
</script>

<?php else: ?>
<!-- ─── Services List ────────────────────────────────────────────────── -->
<div class="topbar">
    <h1 class="page-title">Diensten</h1>
    <a href="/?action=new_service" class="btn btn-primary btn-sm">+ Nieuwe dienst</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Naam</th>
                <th>Prijs</th>
                <th>Eenheid</th>
                <th>Sub-opties</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($services)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">Geen diensten gevonden</td></tr>
        <?php else: foreach ($services as $s): ?>
            <tr>
                <td style="color:var(--muted)"><?= h($s['id']) ?></td>
                <td style="color:var(--text);font-weight:600"><?= h($s['title']) ?></td>
                <td><?= fmt_eur($s['base_price']) ?></td>
                <td><?= h($unit_labels[$s['price_unit']] ?? $s['price_unit']) ?></td>
                <td style="color:var(--muted)"><?= count($s['sub_options']) ?> opties</td>
                <td><?= $s['active']
                    ? '<span style="color:#6ee7b7;font-size:11px;font-weight:700">● ACTIEF</span>'
                    : '<span style="color:var(--muted);font-size:11px;font-weight:700">○ INACTIEF</span>' ?></td>
                <td><a href="/?action=services&edit=<?= h($s['id']) ?>" class="btn btn-ghost btn-sm">Bewerken</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
