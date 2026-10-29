<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';
require __DIR__.'/../../includes/services.php';
require_student();
verify_csrf($_POST['csrf_token']??null);

$uid=(int)current_user()['user_id'];
$id=(int)($_POST['transaction_id']??0);
$type=$_POST['type']??'expense';
$amount=(float)($_POST['amount']??0);
$date=(string)($_POST['transaction_date']??'');
$desc=trim((string)($_POST['description']??''));
$cat=(int)($_POST['category_id']??0);

if(!in_array($type,['income','expense'],true)||$amount<=0||!valid_date($date)||$desc===''){
    flash('error','Enter a valid amount, date, type and description.');
    redirect('student/edit-transaction.php?id='.$id);
}
$category=find_category($cat,$uid,$type);
if(!$category){
    flash('error','Choose a category that matches the transaction type.');
    redirect('student/edit-transaction.php?id='.$id);
}

$oldStmt=db()->prepare('SELECT * FROM transactions WHERE transaction_id=? AND user_id=? AND status="active" LIMIT 1');
$oldStmt->execute([$id,$uid]);
$old=$oldStmt->fetch();
if(!$old){flash('error','Transaction not found.');redirect('student/transactions.php');}

try{
    db()->beginTransaction();
    db()->prepare('UPDATE transactions SET category_id=?,amount=?,type=?,description=?,transaction_date=? WHERE transaction_id=? AND user_id=? AND status="active"')
      ->execute([$cat,$amount,$type,$desc,$date,$id,$uid]);

    if(!empty($old['ai_suggested_category_id']) && (int)$old['ai_suggested_category_id'] !== $cat){
        db()->prepare('INSERT INTO ai_category_suggestions(transaction_id,user_id,description_text,suggested_category_id,accepted_category_id,confidence,was_overridden,resolved_at) VALUES(?,?,?,?,?,?,1,CURRENT_TIMESTAMP)')
          ->execute([$id,$uid,$desc,(int)$old['ai_suggested_category_id'],$cat,(float)($old['ai_confidence']??0)]);
    }

    db()->commit();
    add_activity($uid,'update','transaction',$id,['amount'=>$amount,'type'=>$type,'category_id'=>$cat]);
    ensure_budget_alerts($uid);
    flash('success','Transaction updated.');
    redirect('student/transactions.php');
}catch(Throwable $e){
    if(db()->inTransaction())db()->rollBack();
    error_log($e->getMessage());
    flash('error','Could not update the transaction.');
    redirect('student/edit-transaction.php?id='.$id);
}
