<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_login();
verify_csrf($_POST['csrf_token']??null);
$uid=(int)current_user()['user_id'];
$dark=(($_POST['appearance']??'dark')==='dark')?1:0;
$font=in_array($_POST['font_size']??'', ['small','medium','large'], true)?$_POST['font_size']:'medium';
$currency=(string)($_POST['currency_code']??DEFAULT_CURRENCY);
if(!isset(SUPPORTED_CURRENCIES[$currency]))$currency=DEFAULT_CURRENCY;
db()->prepare('INSERT INTO user_preferences(user_id,dark_mode,font_size,currency_code) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE dark_mode=VALUES(dark_mode),font_size=VALUES(font_size),currency_code=VALUES(currency_code)')->execute([$uid,$dark,$font,$currency]);
$_SESSION['user']['currency_code']=$currency;
$back=(string)($_POST['return_to']??'');
if($back===''||!str_starts_with($back,BASE_URL.'/')){
    $back=current_user()['role']==='admin'?url('admin/dashboard.php'):url('student/dashboard.php');
}else{$back=html_entity_decode($back,ENT_QUOTES,'UTF-8');}
flash('success','Display settings updated.');
header('Location: '.$back);
exit;
