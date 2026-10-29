<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('login.php');
verify_csrf($_POST['csrf_token'] ?? null);

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    flash('login_error', 'Enter a valid email and password.');
    redirect('login.php');
}

try {
    $stmt = db()->prepare('SELECT user_id, role, name, email, password_hash, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('login_error', 'Invalid email or password.');
        redirect('login.php');
    }

    if ($user['status'] !== 'active') {
        flash('login_error', 'This account is not active. Please contact the administrator.');
        redirect('login.php');
    }

    login_user($user);
    db()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = ?')->execute([$user['user_id']]);
    add_activity((int)$user['user_id'], 'login', 'user', (int)$user['user_id']);

    redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('login_error', 'Login service is unavailable. Check the database connection.');
    redirect('login.php');
}
