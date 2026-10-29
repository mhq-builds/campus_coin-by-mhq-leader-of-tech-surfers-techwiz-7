<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/services.php';
require_student();
$uid=(int)current_user()['user_id'];
$pageTitle='Saving Tips';$activePage='tips';require __DIR__.'/../includes/app_header.php';

$generated=saving_tips($uid);
foreach($generated as $g){
    $check=db()->prepare('SELECT saving_tip_id FROM saving_tips WHERE user_id=? AND period_month=? AND title=? AND status="active" ORDER BY saving_tip_id DESC LIMIT 1');
    $check->execute([$uid,month_start(),$g['title']]);
    if(!$check->fetch()){
        db()->prepare('INSERT INTO saving_tips(user_id,category_id,period_month,title,tip_text,potential_savings_amount,impact_score,rank_position,status,is_pinned) VALUES(?,?,?,?,?,?,?,?,"active",FALSE)')
          ->execute([$uid,$g['category_id']??null,month_start(),$g['title'],$g['tip_text'],$g['potential'],$g['impact'],$g['rank']??1]);
    }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $id=(int)($_POST['saving_tip_id']??0);$action=$_POST['tip_action']??'';
    if($action==='dismiss') db()->prepare('UPDATE saving_tips SET status="dismissed",dismissed_at=CURRENT_TIMESTAMP WHERE saving_tip_id=? AND user_id=?')->execute([$id,$uid]);
    if($action==='pin') db()->prepare('UPDATE saving_tips SET is_pinned=IF(is_pinned=1,0,1),pinned_at=IF(is_pinned=1,NULL,CURRENT_TIMESTAMP) WHERE saving_tip_id=? AND user_id=? AND status="active"')->execute([$id,$uid]);
    if($action==='bookmark') db()->prepare('INSERT IGNORE INTO bookmarks(user_id,item_type,item_id) VALUES(?,?,?)')->execute([$uid,'saving_tip',$id]);
    flash('success',$action==='dismiss'?'Tip dismissed.':($action==='pin'?'Pin status updated.':'Tip bookmarked.'));
    redirect('student/saving-tips.php');
}
$s=db()->prepare('SELECT * FROM saving_tips WHERE user_id=? AND period_month=? AND status="active" ORDER BY impact_score DESC,saving_tip_id DESC');$s->execute([$uid,month_start()]);$tips=$s->fetchAll();
?>
<section class="page-head"><div><span class="eyebrow">SMART SAVING</span><h1>Saving tips</h1><p>Potential impact is calculated from your current budget and spending data. Tips remain suggestions you can review or dismiss.</p></div><a class="btn btn-primary" href="<?=e(url('student/budgets.php'))?>">Tune budgets</a></section>
<div class="tip-grid">
<?php foreach($tips as $i=>$t):?>
<article class="tip-large <?=!empty($t['is_pinned'])?'pinned':''?>">
  <div class="row-between"><span class="muted">#<?=$i+1?></span><span class="text-green">Potential <?=money($t['potential_savings_amount'])?></span></div>
  <h2><?=e($t['title'])?></h2><p><?=e($t['tip_text'])?></p>
  <div class="tip-actions">
    <form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="saving_tip_id" value="<?=$t['saving_tip_id']?>"><input type="hidden" name="tip_action" value="bookmark"><button class="btn btn-ghost">Bookmark</button></form>
    <form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="saving_tip_id" value="<?=$t['saving_tip_id']?>"><input type="hidden" name="tip_action" value="pin"><button class="btn btn-ghost"><?=$t['is_pinned']?'Unpin':'Pin'?></button></form>
    <form method="post" onsubmit="return confirm('Dismiss this saving tip?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="saving_tip_id" value="<?=$t['saving_tip_id']?>"><input type="hidden" name="tip_action" value="dismiss"><button class="btn btn-ghost">Dismiss</button></form>
  </div>
</article>
<?php endforeach;?>
<?php if(!$tips):?><section class="panel"><div class="empty-state"><strong>No active tips.</strong><p>Add transactions or budgets to generate new suggestions.</p><a class="btn btn-primary" href="<?=e(url('student/transactions.php'))?>">View transactions</a></div></section><?php endif;?>
</div>
<?php require __DIR__.'/../includes/footer.php';?>
