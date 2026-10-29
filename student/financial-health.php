<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/services.php';
require_student();
$uid=(int)current_user()['user_id'];
$health=final_financial_health($uid);
$pageTitle='Financial Health';$activePage='health';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">CAMPUS COIN SCORE</span><h1>Financial health</h1><p>A transparent product metric based on current budget discipline, savings-goal progress and spending stability.</p></div></section>
<section class="health-hero panel"><div class="health-score"><span>YOUR SCORE</span><strong><?= $health['score'] ?></strong><small>/ 100</small></div><div><span class="ai-badge">EXPLAINABLE SCORE</span><h2><?= $health['score']>=80?'Strong month so far':($health['score']>=60?'Room to improve':'Needs attention') ?></h2><p>Use the component scores below to understand which part of your money workflow is changing.</p></div></section>
<div class="stats-grid"><article class="stat-card"><span>Budget discipline</span><strong><?= $health['budget'] ?></strong><small>How consistently active budgets are staying within target.</small></article><article class="stat-card"><span>Goal progress</span><strong><?= $health['saving'] ?></strong><small>Current balance relative to your savings goal.</small></article><article class="stat-card"><span>Spending stability</span><strong><?= $health['stability'] ?></strong><small>Consistency of recent monthly expense levels.</small></article><article class="stat-card highlight"><span>Current balance</span><strong><?= money($health['balance']) ?></strong><small>Month-to-date balance.</small></article></div>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">HOW IT WORKS</span><h2>Three transparent signals</h2></div></div><div class="health-bars"><div><div class="row-between"><span>Budget discipline</span><b><?= $health['budget'] ?>%</b></div><div class="progress"><span style="width:<?= $health['budget'] ?>%"></span></div></div><div><div class="row-between"><span>Savings-goal progress</span><b><?= $health['saving'] ?>%</b></div><div class="progress"><span style="width:<?= $health['saving'] ?>%"></span></div></div><div><div class="row-between"><span>Spending stability</span><b><?= $health['stability'] ?>%</b></div><div class="progress"><span style="width:<?= $health['stability'] ?>%"></span></div></div></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
