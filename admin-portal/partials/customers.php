<?php /* Klanten tab */ ?>
<div class="topbar"><h1 class="page-title">Klanten</h1></div>
<div class="card">
  <form method="get" action="/" class="filter-bar">
    <input type="hidden" name="action" value="customers">
    <input name="search" placeholder="Zoek op naam, e-mail, bedrijf..." value="<?=h($_GET['search']??'')?>">
    <button class="btn btn-primary" type="submit">Zoeken</button>
    <?php if(!empty($_GET['search'])): ?><a href="/?action=customers" class="btn btn-ghost">Wissen</a><?php endif ?>
  </form>
  <?php if(empty($customers)): ?>
  <div class="empty">Geen klanten gevonden.</div>
  <?php else: ?>
  <table>
    <thead><tr><th>Naam</th><th>E-mail</th><th>Bedrijf</th><th>Telefoon</th><th>Geregistreerd</th><th></th></tr></thead>
    <tbody>
    <?php foreach($customers as $c): ?>
    <tr>
      <td style="font-weight:600;color:var(--text)"><?=h($c['first_name'].' '.$c['last_name'])?></td>
      <td><a href="mailto:<?=h($c['email'])?>"><?=h($c['email'])?></a></td>
      <td><?=h($c['company_name']?:'-')?></td>
      <td><?=h($c['phone']?:'-')?></td>
      <td style="color:var(--muted);font-size:12px"><?=date('d M Y',strtotime($c['created_at']))?></td>
      <td><a href="/?action=messages&account=<?=$c['id']?>" class="btn btn-ghost btn-sm">Berichten</a></td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php endif ?>
</div>
