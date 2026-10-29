<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require __DIR__.'/../includes/services.php';
require_student();
$uid=(int)current_user()['user_id'];
$flags=duplicate_transactions($uid);
$pageTitle='Smart Alerts';$activePage='alerts';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">SYSTEM INTELLIGENCE</span><h1>Smart alerts</h1><p>Potential duplicate transactions detected from matching amount, category and nearby date.</p></div></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">POSSIBLE DUPLICATES</span><h2><?= count($flags) ?> matches</h2></div></div><?php if($flags): ?><div class="table-wrap"><table><thead><tr><th>Category</th><th>Amount</th><th>First</th><th>Second</th><th>Description</th></tr></thead><tbody><?php foreach($flags as $f): ?><tr><td><?= e($f['category_name']) ?></td><td><?= money($f['amount']) ?></td><td><?= e(date('d M Y',strtotime($f['transaction_date']))) ?></td><td>Possible duplicate</td><td><?= e($f['description']?:'—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><strong>No possible duplicates detected.</strong><p>Your current transaction set has no obvious same-amount, same-category matches within one day.</p></div><?php endif; ?></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">DESIGN PRINCIPLE</span><h2>Review, don't auto-delete</h2></div></div><p class="muted">Campus Coin only flags potential issues. The user remains in control of what should be kept or removed.</p></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
