<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_admin();
$rows=db()->query('SELECT a.*,u.name user_name FROM activity_logs a INNER JOIN users u ON u.user_id=a.user_id ORDER BY a.activity_id DESC LIMIT 250')->fetchAll();
$pageTitle='Activity Logs';$activePage='activity';$layout='app';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">AUDIT TRAIL</span><h1>Activity logs</h1><p>Recent authentication and data-change events across the system.</p></div></section>
<section class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>Details</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e(date('d M Y H:i:s',strtotime($r['created_at']))) ?></td><td><?= e($r['user_name']) ?></td><td><span class="tag"><?= e($r['action_type']) ?></span></td><td><?= e($r['entity_type']) ?></td><td><?= e((string)$r['entity_id']) ?></td><td><?= e((string)$r['metadata']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
