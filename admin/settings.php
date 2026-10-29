<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_admin();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $dark=(($_POST['appearance']??'dark')==='dark')?1:0;
    $size=in_array($_POST['font_size']??'', ['small','medium','large'], true)?$_POST['font_size']:'medium';
    $currency=(string)($_POST['currency_code']??DEFAULT_CURRENCY);
    if(!isset(SUPPORTED_CURRENCIES[$currency]))$currency=DEFAULT_CURRENCY;
    db()->prepare('INSERT INTO user_preferences(user_id,dark_mode,font_size,currency_code) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE dark_mode=VALUES(dark_mode),font_size=VALUES(font_size),currency_code=VALUES(currency_code)')->execute([$uid,$dark,$size,$currency]);
    $_SESSION['user']['currency_code']=$currency;
    flash('success','Admin display settings saved.');
    redirect('admin/settings.php');
}
$s=db()->prepare('SELECT dark_mode,font_size,currency_code FROM user_preferences WHERE user_id=? LIMIT 1');$s->execute([$uid]);$p=$s->fetch()?:['dark_mode'=>1,'font_size'=>'medium','currency_code'=>DEFAULT_CURRENCY];
$pageTitle='Display Settings';$activePage='settings';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">CONTROL PANEL / APPEARANCE</span><h1>Display settings</h1><p>Keep the admin workspace readable and consistent while reviewing users, categories, tips and analytics.</p></div></section>
<section class="two-column">
<section class="panel form-panel"><form class="form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="settings-option-group"><span class="settings-label">Theme</span><div class="appearance-options">
<label class="appearance-option <?=!empty($p['dark_mode'])?'selected':''?>"><input type="radio" name="appearance" value="dark" <?=!empty($p['dark_mode'])?'checked':''?>><span><strong>Dark green</strong><small>Signature high-contrast Campus Coin admin presentation.</small></span></label>
<label class="appearance-option <?=empty($p['dark_mode'])?'selected':''?>"><input type="radio" name="appearance" value="light" <?=empty($p['dark_mode'])?'checked':''?>><span><strong>White</strong><small>Light admin canvas with dark, fully readable controls.</small></span></label>
</div></div>
<label>Text size<select class="input" name="font_size"><option value="small" <?=$p['font_size']==='small'?'selected':''?>>Small</option><option value="medium" <?=$p['font_size']==='medium'?'selected':''?>>Medium</option><option value="large" <?=$p['font_size']==='large'?'selected':''?>>Large</option></select></label>
<label>Display currency<select class="input" name="currency_code"><?php foreach(SUPPORTED_CURRENCIES as $code=>$meta): ?><option value="<?=e($code)?>" <?=$p['currency_code']===$code?'selected':''?>><?=e($code.' · '.$meta['name'])?></option><?php endforeach; ?></select></label>
<div class="currency-note"><strong>18 supported currencies</strong><span>Admin analytics and transaction displays follow the selected display currency label.</span></div>
<button class="btn btn-primary">Save settings</button></form></section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">ADMIN VIEW</span><h2>Quick access</h2></div></div><div class="settings-feature-list"><article><strong>Appearance from the top bar</strong><p>The Appearance control stays next to Sign out on every admin page.</p></article><article><strong>White mode contrast</strong><p>Tables, panels, forms, badges, chart surfaces and secondary text remain visible on the white canvas.</p></article><article><strong>Same account preferences</strong><p>Settings are stored per admin account in the same user preference system as students.</p></article></div></section>
</section>
<?php require __DIR__.'/../includes/footer.php'; ?>
