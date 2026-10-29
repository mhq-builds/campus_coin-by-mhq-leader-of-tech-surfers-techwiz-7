<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/services.php';
require_student();
$uid=(int)current_user()['user_id'];
ensure_budget_alerts($uid);
$m=month_start();
$end=date('Y-m-d');
$tot=totals($uid,$m,$end);

$top=db()->prepare("SELECT c.name,SUM(t.amount) amount FROM transactions t JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? AND t.status='active' AND t.type='expense' AND t.transaction_date BETWEEN ? AND ? GROUP BY c.category_id,c.name ORDER BY amount DESC LIMIT 1");
$top->execute([$uid,$m,$end]);$top=$top->fetch();
$r=db()->prepare("SELECT t.*,c.name category FROM transactions t JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? AND t.status='active' ORDER BY t.transaction_date DESC,t.transaction_id DESC LIMIT 7");
$r->execute([$uid]);$recent=$r->fetchAll();
$b=db()->prepare("SELECT b.*,c.name,COALESCE(SUM(CASE WHEN t.status='active' THEN t.amount ELSE 0 END),0) spent FROM budgets b JOIN categories c ON c.category_id=b.category_id LEFT JOIN transactions t ON t.user_id=b.user_id AND t.category_id=b.category_id AND t.type='expense' AND t.transaction_date>=b.month_start AND t.transaction_date<DATE_ADD(b.month_start,INTERVAL 1 MONTH) WHERE b.user_id=? AND b.month_start=? AND b.is_active=1 GROUP BY b.budget_id,c.name ORDER BY spent DESC LIMIT 5");
$b->execute([$uid,$m]);$budgets=$b->fetchAll();
$iq=db()->prepare('SELECT monthly_allowance_baseline FROM users WHERE user_id=? LIMIT 1');$iq->execute([$uid]);$incomeBudget=(float)($iq->fetchColumn()?:0);
$incomeUsed=(float)$tot['income'];$incomePct=$incomeBudget>0?min(100,($incomeUsed/$incomeBudget)*100):0;
$insight=upsert_insight($uid);$tips=saving_tips($uid);
$hour=(int)date('G');$greeting=$hour<12?'morning':($hour<17?'afternoon':'evening');
$pageTitle='Dashboard';$activePage='dashboard';
require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head">
  <div><span class="eyebrow"><i></i> PERSONAL FINANCE OS</span><h1>Good <?=e($greeting)?>, <?=e(explode(' ',trim(current_user()['name']))[0])?> 👋</h1><p>Your financial picture for <?=e(date('F Y'))?>, built from your own activity.</p></div>
  <a class="btn btn-primary" href="<?=e(url('student/add-transaction.php'))?>">＋ Add transaction</a>
</section>
<div class="stats-grid">
  <div class="stat-card highlight"><span>Current balance</span><strong><?=money($tot['balance'])?></strong><small>Month to date</small></div>
  <div class="stat-card"><span>Income</span><strong><?=money($tot['income'])?></strong><small>Month to date</small></div>
  <div class="stat-card"><span>Expenses</span><strong><?=money($tot['expense'])?></strong><small>Month to date</small></div>
  <div class="stat-card"><span>Top category</span><strong><?=e($top['name']??'—')?></strong><small><?=$top?money($top['amount']).' spent':'No expense data yet'?></small></div>
</div>
<div class="dashboard-grid">
<section class="panel span-8 dashboard-chart-panel"><div class="panel-head"><div><span class="panel-kicker">SPENDING OVERVIEW</span><h2>Daily spending</h2></div><a href="<?=e(url('student/reports.php'))?>">Open reports →</a></div><div class="chart-box spending-chart-box"><svg id="spendingChart" class="spending-chart" viewBox="0 0 900 300" preserveAspectRatio="none" role="img" aria-label="Daily spending chart"></svg><div class="chart-empty" id="spendingChartEmpty" hidden>No expense activity yet for this month.</div></div></section>
<section class="panel span-4"><div class="panel-head"><div><span class="panel-kicker">INTELLIGENCE</span><h2>Monthly insight</h2></div><a href="<?=e(url('student/insights.php'))?>">History</a></div><?php if($insight): ?><div class="insight-box"><span class="ai-badge">ASSISTED</span><h3><?=e($insight['summary_text'])?></h3><p><?=e($insight['tip_text'])?></p></div><?php else: ?><div class="empty-state"><strong>Add more transactions.</strong><p>Your monthly insight appears after there is useful spending data.</p></div><?php endif;?></section>
<section class="panel span-12 budget-portal">
  <div class="panel-head"><div><span class="panel-kicker">PLAN YOUR MONTH</span><h2>Budget portal</h2></div><a href="<?=e(url('student/budgets.php'))?>">Manage →</a></div>
  <div class="budget-portal-grid">
    <article class="budget-stage income-stage"><div class="budget-stage-no">01</div><span>INCOME BUDGET</span><h3>Start with what comes in.</h3><p>Set your planned monthly income first, then measure how much of it has arrived this month.</p><div class="budget-stage-metric"><strong><?=money($incomeBudget)?></strong><small>planned income</small></div><div class="progress"><span style="width:<?=($incomeBudget>0?min(100,$incomePct):0)?>%"></span></div><div class="row-between muted"><span><?=money($incomeUsed)?> received</span><span><?=number_format($incomePct,0)?>%</span></div><a class="btn btn-ghost" href="<?=e(url('student/budgets.php#income-budget'))?>">Set income budget →</a></article>
    <article class="budget-stage expense-stage"><div class="budget-stage-no">02</div><span>EXPENSE BUDGET</span><h3>Then assign spending limits.</h3><p>Create category limits for food, transport, academics, rent and any custom campus category.</p><div class="budget-stage-metric"><strong><?=count($budgets)?></strong><small>active category budgets</small></div><div class="mini-budget-list"><?php foreach(array_slice($budgets,0,3) as $x):$pct=$x['limit_amount']>0?((float)$x['spent']/(float)$x['limit_amount'])*100:0;?><div><div class="row-between"><span><?=e($x['name'])?></span><small><?=number_format($pct,0)?>%</small></div><div class="progress"><span style="width:<?=min(100,$pct)?>%"></span></div></div><?php endforeach; ?><?php if(!$budgets): ?><span class="muted">Create your first category budget in the portal.</span><?php endif; ?></div><a class="btn btn-primary" href="<?=e(url('student/budgets.php#expense-budget'))?>">Build expense budgets →</a></article>
  </div>
</section>
<section class="panel span-7"><div class="panel-head"><div><span class="panel-kicker">BUDGET HEALTH</span><h2>Budget vs actual</h2></div><a href="<?=e(url('student/budgets.php#expense-budget'))?>">Manage →</a></div><?php foreach($budgets as $x):$pct=$x['limit_amount']>0?((float)$x['spent']/(float)$x['limit_amount'])*100:0;?><div class="budget-row"><div class="row-between"><span><?=e($x['name'])?></span><small><?=money($x['spent'])?> / <?=money($x['limit_amount'])?></small></div><div class="progress <?=$pct>=100?'danger':($pct>=80?'warn':'')?>"><span style="width:<?=min(100,$pct)?>%"></span></div><small class="muted"><?=number_format($pct,0)?>% used</small></div><?php endforeach;?><?php if(!$budgets): ?><div class="empty-state"><strong>No expense budgets yet.</strong><a class="btn btn-primary" href="<?=e(url('student/budgets.php#expense-budget'))?>">Create budget</a></div><?php endif;?></section>
<section class="panel span-5"><div class="panel-head"><div><span class="panel-kicker">SMART SAVING</span><h2>Top tip</h2></div><a href="<?=e(url('student/saving-tips.php'))?>">See all →</a></div><?php if($tips):$t=$tips[0];?><div class="tip-card"><span class="ai-badge">POTENTIAL IMPACT</span><h3><?=e($t['title'])?></h3><p><?=e($t['tip_text'])?></p><strong class="text-green"><?=money($t['potential'])?> potential</strong></div><?php else: ?><div class="empty-state"><strong>No tip yet.</strong><p>Add transactions or budgets to personalize this engine.</p></div><?php endif;?></section>
<section class="panel span-12"><div class="panel-head"><div><span class="panel-kicker">RECENT ACTIVITY</span><h2>Latest transactions</h2></div><a href="<?=e(url('student/transactions.php'))?>">View all →</a></div><div class="table-wrap"><table><thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Type</th><th>Amount</th></tr></thead><tbody><?php foreach($recent as $x):?><tr><td><?=e(date('d M Y',strtotime($x['transaction_date'])))?></td><td><?=e($x['description']?:'—')?></td><td><span class="tag"><?=e($x['category'])?></span></td><td><span class="status <?=$x['type']==='income'?'income':'expense'?>"><?=e(ucfirst($x['type']))?></span></td><td class="<?=$x['type']==='income'?'text-green':'text-red'?>"><?=$x['type']==='income'?'+':'-'?><?=money($x['amount'])?></td></tr><?php endforeach;?><?php if(!$recent):?><tr><td colspan="5"><div class="empty-state">No transactions yet.</div></td></tr><?php endif;?></tbody></table></div></section>
</div>
<script>window.CAMPUS_CHART=<?=json_encode((function()use($uid,$m){$s=db()->prepare("SELECT transaction_date,SUM(amount) total FROM transactions WHERE user_id=? AND status='active' AND type='expense' AND transaction_date BETWEEN ? AND ? GROUP BY transaction_date ORDER BY transaction_date");$s->execute([$uid,$m,date('Y-m-t',strtotime($m))]);$raw=[];foreach($s->fetchAll()as$r)$raw[$r['transaction_date']]=(float)$r['total'];$lab=[];$v=[];for($d=1;$d<=date('j');$d++){ $dt=sprintf('%s-%02d',substr($m,0,7),$d);$lab[]=(string)$d;$v[]=$raw[$dt]??0;}return ['labels'=>$lab,'values'=>$v,'hasData'=>array_sum($v)>0];})(),JSON_UNESCAPED_UNICODE)?>;</script>
<?php require __DIR__.'/../includes/footer.php';?>
