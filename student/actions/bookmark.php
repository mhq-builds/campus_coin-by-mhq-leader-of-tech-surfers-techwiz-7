<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';
require_student();
verify_csrf($_POST['csrf_token']??null);
$uid=(int)current_user()['user_id'];
$type=$_POST['item_type']??'';
if($type==='insight'){
    $itemId=(int)($_POST['item_id']??0);
    $check=db()->prepare('SELECT insight_id FROM insights WHERE insight_id=? AND user_id=? LIMIT 1');$check->execute([$itemId,$uid]);
    if($check->fetch())db()->prepare('INSERT IGNORE INTO bookmarks(user_id,item_type,item_id) VALUES(?,?,?)')->execute([$uid,'insight',$itemId]);
}elseif($type==='saving_tip'){
    $title=trim((string)($_POST['title']??'Saving tip'));$note=trim((string)($_POST['note']??'Review this suggestion.'));
    $s=db()->prepare('INSERT INTO saving_tips(user_id,period_month,title,tip_text,potential_savings_amount,impact_score,rank_position,status,is_pinned) VALUES(?,?,?,?,0,0,1,"active",TRUE)');$s->execute([$uid,month_start(),$title,$note]);$id=(int)db()->lastInsertId();db()->prepare('INSERT IGNORE INTO bookmarks(user_id,item_type,item_id) VALUES(?,?,?)')->execute([$uid,'saving_tip',$id]);
}else{flash('error','Unsupported bookmark.');}
flash('success','Saved to bookmarks.');redirect($_SERVER['HTTP_REFERER']??url('student/dashboard.php'));
