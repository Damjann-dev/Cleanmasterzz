<?php /* Boeking detail */ ?>
<?php if(!$booking): ?>
<div class="empty">Boeking niet gevonden.</div>
<?php else: ?>
<div class="topbar">
  <div>
    <h1 class="page-title">Boeking #<?=$booking['id']?></h1>
    <div style="color:var(--muted);font-size:13px;margin-top:4px">Aangemaakt: <?=date('d M Y H:i',strtotime($booking['created_at']))?></div>
  </div>
  <div style="display:flex;gap:10px">
    <a href="/?action=bookings" class="btn btn-ghost">← Terug</a>
    <a href="<?=h(WP_SITE_URL)?>/wp-admin/post.php?post=<?=$booking['id']?>&action=edit" target="_blank" class="btn btn-ghost btn-sm">↗ Bewerken in WP</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px">
  <div>
    <div class="card">
      <div class="card-header"><h3>Klantgegevens</h3></div>
      <div class="detail-grid">
        <div class="detail-item"><label>Naam</label><div class="val"><?=h($booking['naam'])?></div></div>
        <div class="detail-item"><label>E-mail</label><div class="val"><a href="mailto:<?=h($booking['email'])?>"><?=h($booking['email'])?></a></div></div>
        <div class="detail-item"><label>Telefoon</label><div class="val"><?=h($booking['phone']?:'-')?></div></div>
        <div class="detail-item"><label>Adres</label><div class="val"><?=h($booking['address'])?>, <?=h($booking['city'])?></div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h3>Dienst & planning</h3></div>
      <div class="detail-grid">
        <div class="detail-item"><label>Dienst</label><div class="val"><?=h($booking['service'])?></div></div>
        <div class="detail-item"><label>Totaal</label><div class="val" style="font-size:18px;font-weight:800;color:var(--primary)"><?=fmt_eur($booking['total'])?></div></div>
        <div class="detail-item"><label>Datum</label><div class="val"><?=h($booking['date'])?></div></div>
        <div class="detail-item"><label>Tijd</label><div class="val"><?=h($booking['time']?:'-')?></div></div>
      </div>
      <?php if($booking['notes']): ?>
      <div style="padding:0 20px 20px">
        <label>Notities</label>
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:var(--r-sm);padding:12px;color:var(--dim);font-size:13px;white-space:pre-wrap"><?=h($booking['notes'])?></div>
      </div>
      <?php endif ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><h3>Status wijzigen</h3></div>
      <div style="padding:16px">
        <form method="post" action="/?action=update_status">
          <input type="hidden" name="id" value="<?=$booking['id']?>">
          <div class="form-group">
            <label>Huidige status</label>
            <div style="margin-bottom:12px"><?=status_badge($booking['status'])?></div>
            <select name="status">
              <?php foreach(['nieuw','bevestigd','gepland','voltooid','geannuleerd'] as $s): ?>
              <option value="<?=$s?>" <?=$booking['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
              <?php endforeach ?>
            </select>
          </div>
          <button class="btn btn-primary" style="width:100%;justify-content:center">Opslaan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif ?>
