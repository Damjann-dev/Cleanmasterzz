<?php
// Werkt voor zowel nieuw aanmaken ($action=create_form) als bewerken ($action=view)
$is_edit    = isset($view_license) && $view_license;
$form_action = $is_edit ? '/?action=save' : '/?action=create';
$lic         = $is_edit ? $view_license : array();

// Huidige features ophalen
$cur_feats = array();
if ($is_edit && !empty($lic['features'])) {
    $cur_feats = json_decode($lic['features'], true) ?: array();
}
$tier_now = $lic['tier'] ?? 'free';
$defaults = tier_defaults($tier_now);

function feat_on($key, $cur, $defaults) {
    if (array_key_exists($key, $cur)) return (bool)$cur[$key];
    return (bool)($defaults[$key] ?? false);
}

$max_svc_val = intval($cur_feats['max_services'] ?? $lic['max_services'] ?? $defaults['max_services'] ?? 3);
?>

<form method="post" action="<?=htmlspecialchars($form_action)?>">
<?php if($is_edit): ?><input type="hidden" name="id" value="<?=intval($lic['id'])?>"> <?php endif ?>

<div class="card">
  <div class="card-header"><h3><?=$is_edit?'Licentie bewerken':'Nieuwe licentie aanmaken'?></h3></div>
  <div style="padding:24px">

    <!-- Klantgegevens -->
    <div class="form-section">
      <h4>Klantgegevens</h4>
      <div class="form-grid">
        <div class="form-group">
          <label>Naam</label>
          <input name="name" type="text" placeholder="Jan Jansen" value="<?=htmlspecialchars($lic['name']??'')?>">
        </div>
        <div class="form-group">
          <label>E-mailadres *</label>
          <input name="email" type="email" required placeholder="klant@bedrijf.nl" value="<?=htmlspecialchars($lic['email']??'')?>">
        </div>
      </div>
    </div>

    <!-- Licentie instellingen -->
    <div class="form-section">
      <h4>Licentie instellingen</h4>
      <div class="form-grid">
        <div class="form-group">
          <label>Tier</label>
          <select name="tier" id="tier_select" onchange="applyTierDefaults(this.value)">
            <?php foreach(array('free','pro','boss','agency') as $t): ?>
            <option value="<?=$t?>" <?=($lic['tier']??'free')===$t?'selected':''?>><?=ucfirst($t)?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <?php foreach(array('active'=>'Actief','suspended'=>'Gepauzeerd','expired'=>'Verlopen') as $v=>$l): ?>
            <option value="<?=$v?>" <?=($lic['status']??'active')===$v?'selected':''?>><?=$l?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label>Max activaties (websites)</label>
          <input name="max_activations" type="number" min="1" max="999" value="<?=intval($lic['max_activations']??1)?>">
        </div>
        <div class="form-group">
          <label>Geldig tot (leeg = levenslang)</label>
          <input name="expires_at" type="date" value="<?=htmlspecialchars($lic['expires_at']?substr($lic['expires_at'],0,10):'')?>">
        </div>
      </div>
    </div>

    <!-- Feature toggles -->
    <div class="form-section">
      <h4>Features per licentie</h4>
      <div class="feature-grid" id="feature_grid">
        <?php
        $all_feats = array(
            'analytics'       => array('📊 Analytics', 'Omzet & statistieken dashboard'),
            'pdf_invoices'    => array('🧾 PDF Facturen', 'Automatische factuurGeneratie'),
            'boss_portal'     => array('👤 Boss Portal', 'Klantaccounts & portaal'),
            'custom_branding' => array('🎨 White-label', 'Eigen branding, geen Servicezz logo'),
            'multi_location'  => array('🏢 Multi-vestiging', 'Meerdere werkgebieden'),
        );
        foreach($all_feats as $key => list($label, $sub)):
            $checked = feat_on($key, $cur_feats, $defaults) ? 'checked' : '';
        ?>
        <label class="feature-toggle" for="feat_<?=$key?>">
          <input type="checkbox" name="feat_<?=$key?>" id="feat_<?=$key?>" <?=$checked?>>
          <div>
            <div class="ft-label"><?=$label?></div>
            <div class="ft-sub"><?=$sub?></div>
          </div>
        </label>
        <?php endforeach ?>
      </div>
    </div>

    <!-- Max diensten slider -->
    <div class="form-section">
      <h4>Aantal diensten</h4>
      <div style="display:flex;align-items:center;gap:16px">
        <div style="flex:1">
          <input type="range" id="max_services_range" min="1" max="999" value="<?=$max_svc_val?>" style="width:100%;accent-color:var(--primary)">
          <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-top:4px">
            <span>1 (Free)</span><span>3</span><span>10 (Pro)</span><span>∞ (Boss+)</span>
          </div>
        </div>
        <div style="text-align:center;min-width:60px">
          <div id="max_services_val" style="font-size:28px;font-weight:800;color:var(--primary)"><?=$max_svc_val>=999?'∞':$max_svc_val?></div>
          <div style="font-size:11px;color:var(--muted)">diensten</div>
        </div>
      </div>
      <input type="hidden" name="max_services" id="max_services" value="<?=$max_svc_val?>">
    </div>

    <!-- Notities -->
    <div class="form-section">
      <h4>Notities (intern)</h4>
      <textarea name="notes" rows="3" placeholder="Intern notities over deze klant..."><?=htmlspecialchars($lic['notes']??'')?></textarea>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end">
      <a href="/" class="btn btn-ghost">Annuleren</a>
      <button type="submit" class="btn btn-primary"><?=$is_edit?'💾 Opslaan':'➕ Licentie aanmaken'?></button>
    </div>

  </div>
</div>
</form>

<script>
// Tier defaults voor snelle feature-vulling
var tierDefaults = {
    free:   {analytics:false, pdf_invoices:false, boss_portal:false, custom_branding:false, multi_location:false, max_services:3},
    pro:    {analytics:true,  pdf_invoices:true,  boss_portal:false, custom_branding:false, multi_location:true,  max_services:10},
    boss:   {analytics:true,  pdf_invoices:true,  boss_portal:true,  custom_branding:false, multi_location:true,  max_services:999},
    agency: {analytics:true,  pdf_invoices:true,  boss_portal:true,  custom_branding:true,  multi_location:true,  max_services:999},
};

function applyTierDefaults(tier) {
    var d = tierDefaults[tier] || tierDefaults.free;
    Object.keys(d).forEach(function(k) {
        if (k === 'max_services') {
            var r = document.getElementById('max_services_range');
            if (r) { r.value = d[k]; r.dispatchEvent(new Event('input')); }
        } else {
            var el = document.getElementById('feat_' + k);
            if (el) el.checked = d[k];
        }
    });
}
</script>
