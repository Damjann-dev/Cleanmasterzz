<?php /* Dashboard tab */ ?>
<div class="topbar">
  <h1 class="page-title">Dashboard</h1>
  <span style="color:var(--muted);font-size:13px"><?=date('l j F Y')?></span>
</div>

<?php if($stats): ?>
<div class="stats">
  <div class="stat">
    <div class="stat-icon" style="background:rgba(59,130,246,.15)">📋</div>
    <div>
      <div class="stat-num"><?=$stats['total_bookings']?></div>
      <div class="stat-label">Totaal boekingen</div>
      <div class="stat-sub"><?=$stats['status_counts']['nieuw']??0?> nieuw</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:rgba(16,185,129,.15)">💶</div>
    <div>
      <div class="stat-num"><?=fmt_eur($stats['total_revenue'])?></div>
      <div class="stat-label">Totale omzet</div>
      <div class="stat-sub"><?=fmt_eur($stats['month_revenue'])?> deze maand</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:rgba(139,92,246,.15)">👥</div>
    <div>
      <div class="stat-num"><?=$stats['customer_count']?></div>
      <div class="stat-label">Klanten</div>
    </div>
  </div>
  <div class="stat">
    <div class="stat-icon" style="background:rgba(245,158,11,.15)">💬</div>
    <div>
      <div class="stat-num"><?=$stats['unread_messages']?></div>
      <div class="stat-label">Ongelezen berichten</div>
    </div>
  </div>
</div>

<!-- Status overzicht -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
  <div class="card">
    <div class="card-header"><h3>Status overzicht</h3></div>
    <div style="padding:16px;display:flex;flex-direction:column;gap:10px">
    <?php
    $sc = $stats['status_counts'] ?? array();
    $total = max(1, $stats['total_bookings']);
    $status_colors = array('nieuw'=>'#3b82f6','bevestigd'=>'#6366f1','gepland'=>'#f59e0b','voltooid'=>'#10b981','geannuleerd'=>'#ef4444');
    foreach($sc as $s=>$count):
      $pct = round($count/$total*100);
      $color = $status_colors[$s] ?? '#64748b';
    ?>
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:4px">
        <span style="font-size:12px;color:var(--dim)"><?=ucfirst(h($s))?></span>
        <span style="font-size:12px;font-weight:700;color:var(--text)"><?=$count?> (<?=$pct?>%)</span>
      </div>
      <div style="height:6px;background:var(--card);border-radius:100px;overflow:hidden">
        <div style="height:100%;width:<?=$pct?>%;background:<?=$color?>;border-radius:100px;transition:.3s"></div>
      </div>
    </div>
    <?php endforeach ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>Omzet afgelopen 6 maanden</h3></div>
    <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
  </div>
</div>
<?php endif ?>

<!-- Recente boekingen -->
<?php if($bookings&&!empty($bookings['bookings'])): ?>
<div class="card">
  <div class="card-header">
    <h3>Recente boekingen</h3>
    <a href="/?action=bookings" class="btn btn-ghost btn-sm">Alles bekijken →</a>
  </div>
  <table>
    <thead><tr><th>#</th><th>Klant</th><th>Dienst</th><th>Datum</th><th>Status</th><th>Bedrag</th></tr></thead>
    <tbody>
    <?php foreach(array_slice($bookings['bookings'],0,8) as $b): ?>
    <tr>
      <td><a href="/?action=booking_detail&id=<?=$b['id']?>" style="font-family:monospace;font-size:12px">#<?=$b['id']?></a></td>
      <td>
        <div style="font-weight:600;color:var(--text)"><?=h($b['naam'])?></div>
        <div style="font-size:11px;color:var(--muted)"><?=h($b['email'])?></div>
      </td>
      <td><?=h($b['service'])?></td>
      <td style="color:var(--muted);font-size:12px"><?=h($b['date'])?></td>
      <td><?=status_badge($b['status'])?></td>
      <td style="font-weight:700;color:var(--text)"><?=fmt_eur($b['total'])?></td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php endif ?>

<?php if($revenue): ?>
<script>
var ctx = document.getElementById('revenueChart');
if(ctx){
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?=json_encode(array_column($revenue,'label'))?>,
      datasets:[{
        label:'Omzet',
        data: <?=json_encode(array_column($revenue,'revenue'))?>,
        backgroundColor:'rgba(59,130,246,.5)',
        borderColor:'#3b82f6',
        borderWidth:1,
        borderRadius:4,
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return'€'+c.parsed.y.toFixed(2).replace('.',',')}}}},
      scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#64748b',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#64748b',font:{size:11},callback:function(v){return'€'+v}}}}
    }
  });
}
</script>
<?php endif ?>
