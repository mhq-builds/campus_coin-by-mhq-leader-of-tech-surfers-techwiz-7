<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $action=$_POST['action']??'create';
    try{
        if($action==='create'){
            $name=trim((string)$_POST['name']);$type=$_POST['type'];
            if($name===''||!in_array($type,['income','expense'],true))throw new RuntimeException('Enter a valid category.');
            db()->prepare('INSERT INTO categories(user_id,name,type,is_default,is_active) VALUES(?,?,?,FALSE,TRUE)')->execute([$uid,$name,$type]);
            flash('success','Personal category created.');
        }elseif($action==='update'){
            $id=(int)$_POST['category_id'];$name=trim((string)$_POST['name']);
            if($name==='')throw new RuntimeException('Category name cannot be empty.');
            db()->prepare('UPDATE categories SET name=? WHERE category_id=? AND user_id=? AND is_default=0 AND is_active=1')->execute([$name,$id,$uid]);
            flash('success','Category updated.');
        }elseif($action==='delete'){
            $id=(int)$_POST['category_id'];
            db()->prepare('UPDATE categories SET is_active=0 WHERE category_id=? AND user_id=? AND is_default=0')->execute([$id,$uid]);
            flash('success','Category removed from your active list.');
        }
    }catch(Throwable $e){flash('error','That category could not be saved.');}
    redirect('student/categories.php');
}
$s=db()->prepare('SELECT * FROM categories WHERE is_active=1 AND (is_default=1 OR user_id=?) ORDER BY type,is_default DESC,name');$s->execute([$uid]);$rows=$s->fetchAll();
$pageTitle='Categories';$activePage='categories';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">ORGANIZE</span><h1>Categories</h1><p>Default categories are shared; your personal categories can be created, edited and removed.</p></div></section>
<div class="two-column">
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">PERSONAL</span><h2>Create category</h2></div></div><form class="form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create"><label>Name<input class="input" name="name" maxlength="100" required></label><label>Type<select class="input" name="type"><option value="expense">Expense</option><option value="income">Income</option></select></label><button class="btn btn-primary">Create category</button></form></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker"><?=count($rows)?> ACTIVE</span><h2>Category list</h2></div></div><?php foreach($rows as$c): ?><div class="saved-card"><div class="row-between"><div><strong><?=e($c['name'])?></strong><small class="muted"><?=e($c['type'])?> · <?=$c['is_default']?'System default':'Personal'?></small></div><?php if(!$c['is_default']): ?><form method="post" style="display:flex;gap:6px;align-items:center"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="category_id" value="<?=$c['category_id']?>"><input type="hidden" name="action" value="update"><input class="input mini-input" name="name" value="<?=e($c['name'])?>"><button class="text-button">Edit</button></form><?php endif; ?></div><?php if(!$c['is_default']): ?><form method="post" style="margin-top:8px"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="category_id" value="<?=$c['category_id']?>"><input type="hidden" name="action" value="delete"><button class="text-button">Remove category</button></form><?php endif; ?></div><?php endforeach; ?></section>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
