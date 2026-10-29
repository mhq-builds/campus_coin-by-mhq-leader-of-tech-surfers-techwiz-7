<?php
declare(strict_types=1);
require __DIR__.'/../../includes/bootstrap.php';
require_student();
verify_csrf($_POST['csrf_token']??null);
$uid=(int)current_user()['user_id'];
$income=(float)($_POST['income_budget']??-1);
if($income<0){flash('error','Enter a valid non-negative income budget.');redirect('student/budgets.php#income-budget');}
db()->prepare('UPDATE users SET monthly_allowance_baseline=? WHERE user_id=?')->execute([$income,$uid]);
flash('success','Monthly income budget saved. Now set your expense limits below.');
redirect('student/budgets.php#expense-budget');
