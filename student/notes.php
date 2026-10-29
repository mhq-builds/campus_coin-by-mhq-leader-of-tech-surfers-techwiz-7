<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $action=$_POST['action']??'add';
    if($action==='delete'){db()->prepare('DELETE FROM user_notes WHERE note_id=? AND user_id=?')->execute([(int)$_POST['note_id'],$uid]);flash('success','Note removed.');}
    else{
        $type=$_POST['item_type']??'insight';$id=(int)($_POST['item_id']??0);$text=trim((string)($_POST['note_text']??''));
        if(in_array($type,['insight','saving_tip'],true)&&$id>0&&$text!==''){db()->prepare('INSERT INTO user_notes(user_id,item_type,item_id,note_text) VALUES(?,?,?,?)')->execute([$uid,$type,$id,$text]);flash('success','Note saved.');}else flash('error','Choose a valid item and write a note.');
    }
    redirect('student/notes.php');
}
$ins=db()->prepare('SELECT insight_id,month_start,summary_text FROM insights WHERE user_id=? ORDER BY month_start DESC,version_no DESC LIMIT 20');$ins->execute([$uid]);$insights=$ins->fetchAll();
$tips=db()->prepare('SELECT saving_tip_id,title FROM saving_tips WHERE user_id=? ORDER BY generated_at DESC LIMIT 20');$tips->execute([$uid]);$tipsRows=$tips->fetchAll();
$notes=db()->prepare('SELECT n.*,CASE WHEN n.item_type="insight" THEN i.summary_text WHEN n.item_type="saving_tip" THEN s.title END item_title FROM user_notes n LEFT JOIN insights i ON n.item_type="insight" AND n.item_id=i.insight_id LEFT JOIN saving_tips s ON n.item_type="saving_tip" AND n.item_id=s.saving_tip_id WHERE n.user_id=? ORDER BY n.created_at DESC');$notes->execute([$uid]);$rows=$notes->fetchAll();
$pageTitle='Notes';$activePage='notes';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">PERSONAL NOTES</span><h1>Notes</h1><p>Keep your own context beside a saving tip or monthly insight.</p></div></section>
<div class="two-column">
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">NEW NOTE</span><h2>Add a note</h2></div></div>
<form class="form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add"><label>Attach to<select class="input" name="item_type" id="note-type"><option value="insight">Insight</option><option value="saving_tip">Saving tip</option></select></label><label>Item<select class="input" name="item_id" id="note-item"><?php foreach($insights as$i):?><option data-type="insight" value="<?=$i['insight_id']?>"><?=e(date('F Y',strtotime($i['month_start'])))?> · <?=e(mb_substr($i['summary_text'],0,70))?></option><?php endforeach;?><?php foreach($tipsRows as$t):?><option data-type="saving_tip" value="<?=$t['saving_tip_id']?>" style="display:none"><?=e($t['title'])?></option><?php endforeach;?></select></label><label>Your note<textarea class="input" name="note_text" rows="5" maxlength="1000" required placeholder="What did you notice? What do you want to change next month?"></textarea></label><button class="btn btn-primary">Save note</button></form></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker"><?=count($rows)?> SAVED</span><h2>Your notes</h2></div></div><?php foreach($rows as$n):?><article class="saved-card"><div class="row-between"><strong><?=e($n['item_title']?:'Campus Coin item')?></strong><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="note_id" value="<?=$n['note_id']?>"><button class="text-button">Remove</button></form></div><p><?=e($n['note_text'])?></p><small class="muted"><?=e(date('d M Y H:i',strtotime($n['created_at'])))?></small></article><?php endforeach;?><?php if(!$rows):?><div class="empty-state">No notes yet.</div><?php endif;?></section>
</div>
<script>
const noteType=document.getElementById('note-type'),noteItem=document.getElementById('note-item');
function syncNoteItems(){const type=noteType.value;[...noteItem.options].forEach(o=>{o.style.display=o.dataset.type===type?'':'none';});const visible=[...noteItem.options].find(o=>o.dataset.type===type);if(visible)noteItem.value=visible.value;}
noteType?.addEventListener('change',syncNoteItems);syncNoteItems();
</script>
<?php require __DIR__.'/../includes/footer.php';?>
