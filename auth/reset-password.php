<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('login.php');
verify_csrf($_POST['csrf_token'] ?? null);
$token=(string)($_POST['token']??'');
$password=(string)($_POST['password']??'');
$confirmation=(string)($_POST['password_confirmation']??'');
if($token===''||strlen($password)<PASSWORD_MIN_LENGTH||$password!==$confirmation){flash('reset_error','Passwords must match and contain at least 8 characters.');redirect('reset-password.php?token='.urlencode($token));}
try{
    $pdo=db();
    $q=$pdo->prepare('SELECT auth_token_id,user_id FROM auth_tokens WHERE token_hash=? AND token_type="password_reset" AND used_at IS NULL AND expires_at>CURRENT_TIMESTAMP LIMIT 1');
    $q->execute([hash('sha256',$token)]);$row=$q->fetch();
    if(!$row){flash('reset_error','This reset link is invalid or expired.');redirect('login.php');}
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET password_hash=? WHERE user_id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$row['user_id']]);
    $pdo->prepare('UPDATE auth_tokens SET used_at=CURRENT_TIMESTAMP WHERE auth_token_id=?')->execute([$row['auth_token_id']]);
    $pdo->commit();
    flash('login_success','Password updated. You can sign in now.');
    redirect('login.php');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log($e->getMessage());flash('reset_error','Password reset could not be completed.');redirect('reset-password.php?token='.urlencode($token));}
