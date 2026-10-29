<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('register.php');
verify_csrf($_POST['csrf_token'] ?? null);

$name = trim((string)($_POST['name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$academicYear = trim((string)($_POST['academic_year'] ?? ''));
$allowance = trim((string)($_POST['monthly_allowance_baseline'] ?? ''));
$goal = trim((string)($_POST['monthly_savings_goal'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < PASSWORD_MIN_LENGTH) {
    flash('register_error', 'Please provide a valid name, email, and password of at least 8 characters.');
    redirect('register.php');
}

$allowanceValue = $allowance === '' ? null : (float)$allowance;
$goalValue = $goal === '' ? null : (float)$goal;
if (($allowanceValue !== null && $allowanceValue < 0) || ($goalValue !== null && $goalValue < 0)) {
    flash('register_error', 'Allowance and savings goal cannot be negative.');
    redirect('register.php');
}

try {
    $pdo = db();
    $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        flash('register_error', 'An account with this email already exists.');
        redirect('register.php');
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO users (role,name,email,password_hash,academic_year,monthly_allowance_baseline,monthly_savings_goal,status,email_verified_at) VALUES ("student",?,?,?,?,?,? ,"active",CURRENT_TIMESTAMP)');
    $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$academicYear ?: null,$allowanceValue,$goalValue]);
    $userId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO user_preferences(user_id,dark_mode,font_size,currency_code) VALUES(?,1,"medium","PKR")')->execute([$userId]);
    $pdo->commit();

    login_user(['user_id'=>$userId,'role'=>'student','name'=>$name,'email'=>$email]);
    add_activity($userId,'create','user',$userId,['event'=>'registration']);
    redirect('student/dashboard.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    flash('register_error', 'Registration could not be completed. Check the database connection.');
    redirect('register.php');
}
