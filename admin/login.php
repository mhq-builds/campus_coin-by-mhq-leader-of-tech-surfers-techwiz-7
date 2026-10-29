<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
if (current_user() && current_user()['role'] === 'admin') redirect('admin/dashboard.php');
$err = flash('admin_error');
$pageTitle = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>
<main class="auth-page admin-auth-page">
  <section class="auth-shell">
    <aside class="auth-aside">
      <span class="eyebrow"><span class="dot"></span> Control access</span>
      <h1>Run the system from one calm control center.</h1>
      <p>Administration stays inside the same Campus Coin visual system, with the same navigation language and dark-green identity used on the public experience.</p>
      <ul class="auth-list">
        <li>System-wide student management</li>
        <li>Category and content controls</li>
        <li>Usage analytics and activity logs</li>
        <li>Announcements and saving templates</li>
      </ul>
    </aside>
    <div class="auth-form">
      <div class="auth-brand-mark" aria-hidden="true">C</div>
      <h2>Admin sign in</h2>
      <p>Use administrator credentials to enter the control panel.</p>
      <?php if ($err): ?><div class="alert alert-error"><?=e($err)?></div><?php endif; ?>
      <form method="post" action="<?=e(url('admin/actions/login.php'))?>">
        <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
        <div class="form-group"><label for="admin-email">EMAIL</label><input class="form-control" id="admin-email" name="email" type="email" autocomplete="email" required></div>
        <div class="form-group"><label for="admin-password">PASSWORD</label><input class="form-control" id="admin-password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary btn-block" type="submit">Enter admin panel</button>
      </form>
      <div class="auth-switch"><a href="<?=e(url('index.php'))?>">Back to Campus Coin</a></div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
