<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
$month=$_GET['month']??date('Y-m');
if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$start=$month.'-01';$end=date('Y-m-t',strtotime($start));
$s=db()->prepare('SELECT transaction_date,SUM(CASE WHEN type="income" THEN amount ELSE 0 END) income,SUM(CASE WHEN type="expense" THEN amount ELSE 0 END) expense,COUNT(*) records FROM transactions WHERE user_id=? AND status="active" AND transaction_date BETWEEN ? AND ? GROUP BY transaction_date ORDER BY transaction_date');$s->execute([$uid,$start,$end]);$days=[];foreach($s->fetchAll() as $r)$days[$r['transaction_date']]=$r;
$first=(int)date('N',strtotime($start));$num=(int)date('t',strtotime($start));
$pageTitle='Financial Calendar';$activePage='calendar';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">MONTH VIEW</span><h1>Financial calendar</h1><p>See which days carried the heaviest income or spending load.</p></div><form class="filter-bar"><input class="input" type="month" name="month" value="<?= e($month) ?>"><button class="btn btn-primary">Open month</button></form></section>
<section class="panel"><div class="calendar-grid calendar-week"><b>Mon</b><b>Tue</b><b>Wed</b><b>Thu</b><b>Fri</b><b>Sat</b><b>Sun</b></div><div class="calendar-grid"><?php for($i=1;$i<$first;$i++): ?><div></div><?php endfor; for($d=1;$d<=$num;$d++): $dt=sprintf('%s-%02d',$month,$d);$r=$days[$dt]??null;$load=$r?((float)$r['expense']):0;$class=$load>3000?'heavy':($load>1000?'medium':'light'); ?><div class="calendar-day <?= $class ?>"><strong><?= $d ?></strong><?php if($r): ?><small>−<?= money($r['expense']) ?></small><em><?= (int)$r['records'] ?> tx</em><?php else: ?><small>—</small><?php endif; ?></div><?php endfor; ?></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
