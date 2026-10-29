<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$error = flash('register_error');
$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-page">
  <section class="auth-shell">
    <aside class="auth-aside">
      <span class="eyebrow"><span class="dot"></span> Start smarter</span>
      <h1>Your money. Your campus. Your plan.</h1>
      <p>Create your student account and build a clearer picture of how your month actually behaves.</p>
      <ul class="auth-list">
        <li>Set your monthly allowance baseline</li>
        <li>Define a savings goal</li>
        <li>Track income & expense categories</li>
        <li>Ready for budgets, reports & insights</li>
      </ul>
    </aside>
    <div class="auth-form">
      <h2>Create account</h2>
      <p>Just the essentials to get your Campus Coin profile started.</p>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form action="<?=e(url('auth/register.php'))?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
          <label for="name">FULL NAME</label>
          <input class="form-control" id="name" name="name" type="text" maxlength="120" required>
        </div>
        <div class="form-group">
          <label for="email">EMAIL</label>
          <input class="form-control" id="email" name="email" type="email" maxlength="190" autocomplete="email" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="academic_year">ACADEMIC YEAR</label>
            <input class="form-control" id="academic_year" name="academic_year" type="text" maxlength="50" placeholder="e.g. 2nd Year">
          </div>
          <div class="form-group">
            <label for="allowance">MONTHLY ALLOWANCE</label>
            <input class="form-control" id="allowance" name="monthly_allowance_baseline" type="number" min="0" step="0.01" placeholder="0.00">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="goal">SAVINGS GOAL / MONTH</label>
            <input class="form-control" id="goal" name="monthly_savings_goal" type="number" min="0" step="0.01" placeholder="0.00">
          </div>
          <div class="form-group">
            <label for="password">PASSWORD</label>
            <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create Campus Coin Account</button>
      </form>
      <div class="auth-switch">Already registered? <a href="<?=e(url('login.php'))?>">Sign in</a></div>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
