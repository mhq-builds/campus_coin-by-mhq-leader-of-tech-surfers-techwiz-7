<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf($_POST['csrf_token']??null);$id=(int)$_POST['bookmark_id'];db()->prepare('DELETE FROM bookmarks WHERE bookmark_id=? AND user_id=?')->execute([$id,$uid]);flash('success','Bookmark removed.');redirect('student/bookmarks.php');}
$tipStmt=db()->prepare('SELECT b.bookmark_id,b.created_at,s.title,s.tip_text,s.potential_savings_amount FROM bookmarks b INNER JOIN saving_tips s ON b.item_type="saving_tip" AND b.item_id=s.saving_tip_id WHERE b.user_id=? ORDER BY b.created_at DESC');$tipStmt->execute([$uid]);$tips=$tipStmt->fetchAll();
$insStmt=db()->prepare('SELECT b.bookmark_id,b.created_at,i.summary_text,i.tip_text,i.month_start FROM bookmarks b INNER JOIN insights i ON b.item_type="insight" AND b.item_id=i.insight_id WHERE b.user_id=? ORDER BY b.created_at DESC');$insStmt->execute([$uid]);$insights=$insStmt->fetchAll();
$pageTitle='Bookmarks';$activePage='bookmarks';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">SAVED</span><h1>Bookmarks</h1><p>Keep useful saving tips and monthly insights close for later review.</p></div></section>
<div class="two-column">
<section class="panel"><div class="panel-head"><div><span class="panel-kicker"><?=count($tips)?> SAVED</span><h2>Saving tips</h2></div></div><?php foreach($tips as $t): ?><div class="saved-card"><div class="row-between"><strong><?=e($t['title'])?></strong><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="bookmark_id" value="<?=$t['bookmark_id']?>"><button class="text-button">Remove</button></form></div><p><?=e($t['tip_text'])?></p><small class="muted">Potential <?=money($t['potential_savings_amount']??0)?></small></div><?php endforeach;?><?php if(!$tips): ?><div class="empty-state">No saving tips bookmarked yet.</div><?php endif;?></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker"><?=count($insights)?> SAVED</span><h2>Insights</h2></div></div><?php foreach($insights as$t): ?><div class="saved-card"><div class="row-between"><strong><?=e(date('F Y',strtotime($t['month_start'])))?></strong><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="bookmark_id" value="<?=$t['bookmark_id']?>"><button class="text-button">Remove</button></form></div><p><?=e($t['summary_text'])?></p><small class="muted"><?=e($t['tip_text'])?></small></div><?php endforeach;?><?php if(!$insights): ?><div class="empty-state">No insights bookmarked yet.</div><?php endif;?></section>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
