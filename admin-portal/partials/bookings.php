<?php /* Boekingen tab */ ?>
<div class="topbar">
  <h1 class="page-title">Boekingen</h1>
  <div style="display:flex;gap:10px">
    <a href="<?=h(WP_SITE_URL)?>/wp-admin/edit.php?post_type=boeking" target="_blank" class="btn btn-ghost btn-sm">↗ WordPress</a>
    <a href="<?=h(WP_SITE_URL)?>/wp-json/cleanmasterzz/v1/admin/export/bookings?<?=http_build_query(['_wpnonce'=>''])?>" class="btn btn-ghost btn-sm">⬇ CSV export</a>
  </div>
</div>

<div class="card">
  <form method="get" action="/" class="filter-bar">
    <input type="hidden" name="action" value="bookings">
    <input name="search" placeholder="Zoek op naam, email..." value="<?=h($_GET['search']??'')?>">
    <select name="status">
      <option value="">Alle statussen</option>
      <?php foreach(['nieuw','bevestigd','gepland','voltooid','geannuleerd'] as $s): ?>
      <option value="<?=$s?>" <?=($_GET['status']??'')===$s?'selected':''?>><?=ucfirst($s)?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-primary" type="submit">Zoeken</button>
    <?php if(!empty($_GET['search'])||!empty($_GET['status'])): ?>
    <a href="/?action=bookings" class="btn btn-ghost">Wissen</a>
    <?php endif ?>
  </form>

  <?php if(empty($bookings['bookings'])): ?>
  <div class="empty">Geen boekingen gevonden.</div>
  <?php else: ?>

  <!-- Bulk acties -->
  <form method="post" action="/?action=bulk_status" id="bulkForm" style="border-bottom:1px solid var(--border);padding:12px 20px;display:flex;gap:10px;align-items:center;background:rgba(255,255,255,.01)">
    <input type="checkbox" id="selectAll" title="Alles selecteren" style="width:16px;height:16px;accent-color:var(--primary)">
    <label for="selectAll" style="margin:0;color:var(--dim);font-size:12px;font-weight:500">Alles</label>
    <select name="status" style="width:auto;min-width:150px">
      <option value="">Bulk status →</option>
      <?php foreach(['bevestigd','gepland','voltooid','geannuleerd'] as $s): ?>
      <option value="<?=$s?>"><?=ucfirst($s)?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-ghost btn-sm" type="submit">Toepassen</button>
    <span id="selectedCount" style="color:var(--muted);font-size:12px"></span>
  </form>

  <table>
    <thead><tr><th style="width:32px"></th><th>#</th><th>Klant</th><th>Dienst</th><th>Datum</th><th>Status</th><th>Bedrag</th><th>Aangemaakt</th><th></th></tr></thead>
    <tbody>
    <?php foreach($bookings['bookings'] as $b): ?>
    <tr>
      <td><input type="checkbox" name="ids[]" form="bulkForm" value="<?=$b['id']?>" class="row-check" style="width:16px;height:16px;accent-color:var(--primary)"></td>
      <td><a href="/?action=booking_detail&id=<?=$b['id']?>" style="font-family:monospace;font-size:12px;color:var(--primary)">#<?=$b['id']?></a></td>
      <td>
        <div style="font-weight:600;color:var(--text)"><?=h($b['naam'])?></div>
        <div style="font-size:11px;color:var(--muted)"><?=h($b['email'])?></div>
      </td>
      <td><?=h($b['service'])?></td>
      <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?=h($b['date'])?> <?=h($b['time'])?></td>
      <td>
        <form method="post" action="/?action=update_status" style="display:inline">
          <input type="hidden" name="id" value="<?=$b['id']?>">
          <select name="status" onchange="this.form.submit()" style="width:auto;font-size:12px;padding:4px 8px">
            <?php foreach(['nieuw','bevestigd','gepland','voltooid','geannuleerd'] as $s): ?>
            <option value="<?=$s?>" <?=$b['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
            <?php endforeach ?>
          </select>
        </form>
      </td>
      <td style="font-weight:700;color:var(--text)"><?=fmt_eur($b['total'])?></td>
      <td style="color:var(--muted);font-size:11px"><?=date('d M',strtotime($b['created_at']))?></td>
      <td><a href="/?action=booking_detail&id=<?=$b['id']?>" class="btn btn-ghost btn-sm">Detail</a></td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>

  <?php if(($bookings['total']??0) > count($bookings['bookings'])): ?>
  <div class="pagination">
    <span style="color:var(--muted);font-size:12px"><?=count($bookings['bookings'])?> van <?=$bookings['total']?> boekingen</span>
    <?php $page_f=max(1,intval($_GET['p']??1)); ?>
    <?php if($page_f>1): ?><a href="?action=bookings&p=<?=$page_f-1?>&status=<?=h($_GET['status']??'')?>&search=<?=h($_GET['search']??'')?>" class="btn btn-ghost btn-sm">← Vorige</a><?php endif ?>
    <a href="?action=bookings&p=<?=$page_f+1?>&status=<?=h($_GET['status']??'')?>&search=<?=h($_GET['search']??'')?>" class="btn btn-ghost btn-sm">Volgende →</a>
  </div>
  <?php endif ?>
  <?php endif ?>
</div>

<script>
document.getElementById('selectAll').addEventListener('change',function(){
  document.querySelectorAll('.row-check').forEach(function(cb){cb.checked=this.checked},this);
  updateCount();
});
document.querySelectorAll('.row-check').forEach(function(cb){cb.addEventListener('change',updateCount)});
function updateCount(){
  var n=document.querySelectorAll('.row-check:checked').length;
  document.getElementById('selectedCount').textContent=n?n+' geselecteerd':'';
}
</script>
