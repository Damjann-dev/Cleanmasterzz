<?php /* Berichten tab */ ?>
<div class="topbar"><h1 class="page-title">Berichten</h1></div>

<?php if(empty($messages)): ?>
<div class="card"><div class="empty">Geen berichten gevonden.</div></div>
<?php else: ?>

<?php
// Groepeer per klant
$by_account = array();
foreach($messages as $m){
  $key = $m['account_id'];
  if(!isset($by_account[$key])){
    $by_account[$key] = array('info'=>array('name'=>trim($m['first_name'].' '.$m['last_name']),'email'=>$m['email'],'company'=>$m['company_name'],'id'=>$m['account_id']),'msgs'=>array(),'unread'=>0);
  }
  $by_account[$key]['msgs'][] = $m;
  if($m['direction']==='in'&&!$m['is_read']) $by_account[$key]['unread']++;
}
$active_account = intval($_GET['account']??array_key_first($by_account));
?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px">
  <!-- Klant lijst -->
  <div class="card" style="overflow:visible">
    <?php foreach($by_account as $aid=>$ac): ?>
    <a href="/?action=messages&account=<?=$aid?>" style="display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:<?=$active_account==$aid?'rgba(59,130,246,.08)':'transparent'?>;border-left:2px solid <?=$active_account==$aid?'var(--primary)':'transparent'?>">
      <div style="width:38px;height:38px;background:var(--grad);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0"><?=strtoupper(substr($ac['info']['name'],0,1))?></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;color:var(--text);font-size:13px"><?=h($ac['info']['name'])?><?php if($ac['unread']): ?> <span style="background:rgba(239,68,68,.2);color:#fca5a5;border-radius:100px;padding:0 6px;font-size:10px;font-weight:700"><?=$ac['unread']?></span><?php endif ?></div>
        <div style="font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=h($ac['info']['email'])?></div>
      </div>
    </a>
    <?php endforeach ?>
  </div>

  <!-- Gesprek -->
  <?php if(isset($by_account[$active_account])): $ac=$by_account[$active_account]; ?>
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <div>
          <div style="font-weight:700;color:var(--text)"><?=h($ac['info']['name'])?></div>
          <div style="font-size:12px;color:var(--muted)"><?=h($ac['info']['email'])?> <?php if($ac['info']['company']): ?>· <?=h($ac['info']['company'])?><?php endif ?></div>
        </div>
      </div>
      <div class="msg-thread">
        <?php foreach(array_reverse($ac['msgs']) as $m): ?>
        <div class="msg-bubble msg-<?=h($m['direction'])?>">
          <div class="msg-meta"><?=$m['direction']==='in'?h($ac['info']['name']):'CleanMasterzz'?> · <?=date('d M H:i',strtotime($m['created_at']))?></div>
          <div class="msg-subject"><?=h($m['subject'])?></div>
          <div class="msg-body"><?=h($m['body'])?></div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
    <!-- Reply formulier -->
    <div class="card">
      <div class="card-header"><h3>Antwoord sturen</h3></div>
      <form method="post" action="/?action=reply_message" style="padding:16px;display:flex;flex-direction:column;gap:12px">
        <input type="hidden" name="account_id" value="<?=$active_account?>">
        <div class="form-group">
          <label>Onderwerp</label>
          <input name="subject" placeholder="Antwoord van CleanMasterzz">
        </div>
        <div class="form-group">
          <label>Bericht</label>
          <textarea name="body" rows="4" required placeholder="Typ je antwoord..."></textarea>
        </div>
        <button class="btn btn-primary" style="align-self:flex-end">Verstuur antwoord →</button>
      </form>
    </div>
  </div>
  <?php endif ?>
</div>
<?php endif ?>
