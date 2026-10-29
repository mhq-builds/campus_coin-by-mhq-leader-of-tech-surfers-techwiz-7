<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('forgot-password.php');
verify_csrf($_POST['csrf_token'] ?? null);
$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('reset_error', 'Enter a valid email address.');
    redirect('forgot-password.php');
}
try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    flash('reset_notice', 'If that account exists, a password reset link has been created.');
    if ($user) {
        $rawToken = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO auth_tokens(user_id,token_type,token_hash,expires_at) VALUES(?,"password_reset",?,DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 MINUTE))')->execute([$user['user_id'],hash('sha256',$rawToken)]);
        flash('dev_reset_link', url('reset-password.php?token='.urlencode($rawToken)));
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('reset_error','Could not create a reset link.');
}
redirect('forgot-password.php');
