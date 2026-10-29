<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
ensure_budget_alerts($uid);
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    db()->prepare('UPDATE notifications SET is_read=1,read_at=CURRENT_TIMESTAMP WHERE user_id=?')->execute([$uid]);
    flash('success','Notifications marked as read.');
    redirect('student/notifications.php');
}
$s=db()->prepare('SELECT notification_id,title,message,notification_type,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');$s->execute([$uid]);$rows=$s->fetchAll();
$a=db()->query('SELECT announcement_id,title,message,created_at FROM announcements WHERE is_active=1 AND (starts_at IS NULL OR starts_at<=CURRENT_TIMESTAMP) AND (ends_at IS NULL OR ends_at>=CURRENT_TIMESTAMP) ORDER BY created_at DESC LIMIT 10');$announcements=$a->fetchAll();
$pageTitle='Notifications';$activePage='notifications';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">ALERT CENTER</span><h1>Notifications</h1><p>Budget alerts, account messages and active campus announcements.</p></div><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="btn btn-ghost">Mark all read</button></form></section>
<?php if($announcements):?><section class="announcement-strip"><?php foreach($announcements as$n):?><article><span>▣</span><div><strong><?=e($n['title'])?></strong><p><?=e($n['message'])?></p></div></article><?php endforeach;?></section><?php endif;?>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker"><?=count($rows)?> MESSAGES</span><h2>Your alerts</h2></div></div><div class="notification-list"><?php foreach($rows as$n):?><article class="<?=$n['is_read']?'':'unread'?>"><span class="notification-icon"><?= $n['notification_type']==='budget_exceeded'?'':''?></span><div><strong><?=e($n['title'])?></strong><p><?=e($n['message'])?></p><small class="muted"><?=e(date('d M Y H:i',strtotime($n['created_at'])))?></small></div></article><?php endforeach;?><?php if(!$rows):?><div class="empty-state"><strong>You're all caught up.</strong><p>Budget alerts appear here automatically when a category crosses its threshold.</p></div><?php endif;?></div></section>
<?php require __DIR__.'/../includes/footer.php';?>
