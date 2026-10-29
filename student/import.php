<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_student();
$pageTitle = 'CSV Example';
$activePage = 'import';
require __DIR__ . '/../includes/app_header.php';
?>
<section class="page-head">
  <div>
    <span class="eyebrow">IMPORT REFERENCE</span>
    <h1>CSV example</h1>
    <p>This page keeps the approved transaction format visible for demonstrations and documentation. CSV uploading is disabled in the final presentation build.</p>
  </div>
</section>
<section class="panel csv-reference-panel">
  <div class="panel-head">
    <div><span class="panel-kicker">EXAMPLE</span><h2>Required format</h2></div>
  </div>
  <pre class="code-sample">date,type,amount,description,category
2026-09-01,income,60000,Monthly allowance,Allowance
2026-09-02,expense,450,Campus Cafe,Food
2026-09-03,expense,250,Bus fare,Transport</pre>
  <a class="btn btn-ghost" href="<?=e(url('demo/sample_transactions.csv'))?>" download>Download sample CSV</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
