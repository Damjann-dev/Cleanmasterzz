<?php /* Analytics tab */ ?>
<div class="topbar">
  <h1 class="page-title">Analytics</h1>
  <div style="display:flex;gap:8px">
    <?php foreach([3,6,12,24] as $m): ?>
    <a href="/?action=analytics&months=<?=$m?>" class="btn <?=(($_GET['months']??12)==$m)?'btn-primary':'btn-ghost'?> btn-sm"><?=$m?> mnd</a>
    <?php endforeach ?>
  </div>
</div>

<?php $months_sel = intval($_GET['months']??12); $revenue = wp_api('revenue?months='.$months_sel); ?>

<?php if($revenue): ?>
<?php
$total_rev = array_sum(array_column($revenue,'revenue'));
$total_bk  = array_sum(array_column($revenue,'count'));
$avg       = $total_bk > 0 ? $total_rev / $total_bk : 0;
$best_idx  = array_search(max(array_column($revenue,'revenue')), array_column($revenue,'revenue'));
?>
<div class="stats" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat">
    <div class="stat-icon" style="background:rgba(16,185,129,.15)">💶</div>
    <div><div class="stat-num"><?=fmt_eur($total_rev)?></div><div class="stat-label">Totale omzet (<?=$months_sel?> mnd)</div></div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:rgba(59,130,246,.15)">📋</div>
    <div><div class="stat-num"><?=$total_bk?></div><div class="stat-label">Boekingen (<?=$months_sel?> mnd)</div></div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:rgba(139,92,246,.15)">📊</div>
    <div><div class="stat-num"><?=fmt_eur($avg)?></div><div class="stat-label">Gemiddeld per boeking</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><h3>Omzet per maand</h3></div>
    <div class="chart-wrap" style="height:320px"><canvas id="revenueBar"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><h3>Boekingen per maand</h3></div>
    <div class="chart-wrap" style="height:320px"><canvas id="bookingsLine"></canvas></div>
  </div>
</div>

<div class="card" style="margin-top:0">
  <div class="card-header"><h3>Maandoverzicht</h3></div>
  <table>
    <thead><tr><th>Maand</th><th>Boekingen</th><th>Omzet</th><th>Gem. per boeking</th></tr></thead>
    <tbody>
    <?php foreach(array_reverse($revenue) as $r): ?>
    <tr>
      <td style="color:var(--text);font-weight:500"><?=h($r['label'])?></td>
      <td><?=$r['count']?></td>
      <td style="font-weight:700;color:var(--text)"><?=fmt_eur($r['revenue'])?></td>
      <td><?=fmt_eur($r['count']>0?$r['revenue']/$r['count']:0)?></td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>

<script>
var labels  = <?=json_encode(array_column($revenue,'label'))?>;
var revData = <?=json_encode(array_column($revenue,'revenue'))?>;
var bkData  = <?=json_encode(array_column($revenue,'count'))?>;
var gridColor = 'rgba(255,255,255,.04)';
var tickColor = '#64748b';

new Chart(document.getElementById('revenueBar'),{
  type:'bar',data:{labels,datasets:[{label:'Omzet',data:revData,backgroundColor:'rgba(59,130,246,.5)',borderColor:'#3b82f6',borderWidth:1,borderRadius:4}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>'€'+c.parsed.y.toFixed(2).replace('.',',')}}},scales:{x:{grid:{color:gridColor},ticks:{color:tickColor}},y:{grid:{color:gridColor},ticks:{color:tickColor,callback:v=>'€'+v}}}}
});
new Chart(document.getElementById('bookingsLine'),{
  type:'line',data:{labels,datasets:[{label:'Boekingen',data:bkData,borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,.15)',fill:true,tension:.4,pointBackgroundColor:'#8b5cf6'}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:gridColor},ticks:{color:tickColor}},y:{grid:{color:gridColor},ticks:{color:tickColor,stepSize:1}}}}
});
</script>
<?php else: ?>
<div class="card"><div class="empty">Geen data beschikbaar. Controleer de WordPress API verbinding.</div></div>
<?php endif ?>
