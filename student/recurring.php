<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $id=(int)($_POST['recurring_id']??0);$action=$_POST['action']??'toggle';
    if($action==='delete')db()->prepare('DELETE FROM recurring_transactions WHERE recurring_id=? AND user_id=?')->execute([$id,$uid]);
    else db()->prepare('UPDATE recurring_transactions SET is_active=IF(is_active=1,0,1) WHERE recurring_id=? AND user_id=?')->execute([$id,$uid]);
    flash('success','Recurring rule updated.');redirect('student/recurring.php');
}
$s=db()->prepare('SELECT r.*,c.name category FROM recurring_transactions r JOIN categories c ON c.category_id=r.category_id WHERE r.user_id=? ORDER BY r.is_active DESC,r.next_run_date');$s->execute([$uid]);$rows=$s->fetchAll();
$pageTitle='Recurring';$activePage='recurring';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">AUTOMATION</span><h1>Recurring entries</h1><p>Use recurring rules for allowances, subscriptions and other repeated campus costs.</p></div><a class="btn btn-primary" href="<?=e(url('student/add-transaction.php'))?>"> Create from transaction</a></section>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Description</th><th>Category</th><th>Frequency</th><th>Amount</th><th>Next run</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($rows as$r):?><tr><td><?=e($r['description']?:'Recurring entry')?></td><td><?=e($r['category'])?></td><td><?=e(ucfirst($r['frequency']))?></td><td><?=money($r['amount'])?></td><td><?=e(date('d M Y',strtotime($r['next_run_date'])))?></td><td><span class="status <?=$r['is_active']?'income':'expense'?>"><?=$r['is_active']?'Active':'Paused'?></span></td><td><form method="post" style="display:flex;gap:6px"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="recurring_id" value="<?=$r['recurring_id']?>"><input type="hidden" name="action" value="toggle"><button class="text-button"><?=$r['is_active']?'Pause':'Resume'?></button></form><form method="post" onsubmit="return confirm('Delete this recurring rule?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="recurring_id" value="<?=$r['recurring_id']?>"><input type="hidden" name="action" value="delete"><button class="text-button">Delete</button></form></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="7"><div class="empty-state"><strong>No recurring rules yet.</strong><p>Check "Make recurring" while adding an allowance or subscription.</p></div></td></tr><?php endif;?></tbody></table></div></section>
<?php require __DIR__.'/../includes/footer.php';?>
