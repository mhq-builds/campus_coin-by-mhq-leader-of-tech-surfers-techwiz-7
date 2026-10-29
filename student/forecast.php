<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/services.php';
require_student();
$uid=(int)current_user()['user_id'];
$f=forecast_next_month($uid);
$stmt=db()->prepare('SELECT DATE_FORMAT(transaction_date,"%Y-%m-01") month_start,SUM(CASE WHEN type="income" THEN amount ELSE 0 END) income,SUM(CASE WHEN type="expense" THEN amount ELSE 0 END) expense FROM transactions WHERE user_id=? AND status="active" AND transaction_date>=DATE_SUB(DATE_FORMAT(CURRENT_DATE,"%Y-%m-01"),INTERVAL 3 MONTH) AND transaction_date<DATE_FORMAT(CURRENT_DATE,"%Y-%m-01") GROUP BY month_start ORDER BY month_start');$stmt->execute([$uid]);$history=$stmt->fetchAll();
$pageTitle='Forecast';$activePage='forecast';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">OPTIONAL INTELLIGENCE</span><h1>Next-month forecast</h1><p>A projection based on up to the last three completed months of your own transaction history.</p></div></section>
<section class="stats-grid"><article class="stat-card highlight"><span>Projected income</span><strong class="text-green"><?= money($f['income']) ?></strong><small>Historical average</small></article><article class="stat-card"><span>Projected expense</span><strong class="text-red"><?= money($f['expense']) ?></strong><small>Historical average</small></article><article class="stat-card"><span>Projected balance</span><strong><?= money($f['balance']) ?></strong><small>Income minus expense</small></article><article class="stat-card"><span>Confidence</span><strong><?= number_format($f['confidence']*100,0) ?>%</strong><small>Higher with more historical months</small></article></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">SOURCE DATA</span><h2>Completed months used</h2></div></div><div class="table-wrap"><table><thead><tr><th>Month</th><th>Income</th><th>Expense</th><th>Balance</th></tr></thead><tbody><?php foreach($history as $r): ?><tr><td><?= e(date('M Y',strtotime($r['month_start']))) ?></td><td class="text-green"><?= money($r['income']) ?></td><td class="text-red"><?= money($r['expense']) ?></td><td><?= money((float)$r['income']-(float)$r['expense']) ?></td></tr><?php endforeach; ?><?php if(!$history): ?><tr><td colspan="4"><div class="empty-state">Not enough completed-month data for a meaningful forecast yet.</div></td></tr><?php endif; ?></tbody></table></div></section>
<div class="flash success">Forecasts are estimates from historical patterns, not guarantees.</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
