<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
require_student();
$uid=(int)current_user()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf($_POST['csrf_token']??null);
    $dark=(($_POST['appearance']??'dark')==='dark')?1:0;
    $size=in_array($_POST['font_size']??'', ['small','medium','large'], true)?$_POST['font_size']:'medium';
    $currency=(string)($_POST['currency_code']??DEFAULT_CURRENCY);
    if(!isset(SUPPORTED_CURRENCIES[$currency]))$currency=DEFAULT_CURRENCY;
    db()->prepare('INSERT INTO user_preferences(user_id,dark_mode,font_size,currency_code) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE dark_mode=VALUES(dark_mode),font_size=VALUES(font_size),currency_code=VALUES(currency_code)')->execute([$uid,$dark,$size,$currency]);
    $_SESSION['user']['currency_code']=$currency;
    flash('success','Display settings saved across your student portal.');
    redirect('student/settings.php');
}
$s=db()->prepare('SELECT dark_mode,font_size,currency_code FROM user_preferences WHERE user_id=? LIMIT 1');$s->execute([$uid]);$p=$s->fetch()?:['dark_mode'=>1,'font_size'=>'medium','currency_code'=>DEFAULT_CURRENCY];
$pageTitle='Display Settings';$activePage='settings';require __DIR__.'/../includes/app_header.php';
?>
<section class="page-head"><div><span class="eyebrow">APPEARANCE</span><h1>Display settings</h1><p>Choose a theme, reading scale and budget currency. These settings follow you across every student page.</p></div></section>
<section class="two-column">
<section class="panel form-panel">
<form class="form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="settings-option-group"><span class="settings-label">Theme</span><div class="appearance-options">
<label class="appearance-option <?=!empty($p['dark_mode'])?'selected':''?>"><input type="radio" name="appearance" value="dark" <?=!empty($p['dark_mode'])?'checked':''?>><span><strong>Dark green</strong><small>Original Campus Coin presentation with maximum contrast.</small></span></label>
<label class="appearance-option <?=empty($p['dark_mode'])?'selected':''?>"><input type="radio" name="appearance" value="light" <?=empty($p['dark_mode'])?'checked':''?>><span><strong>White</strong><small>Clean light workspace with dark text and fully visible controls.</small></span></label>
</div></div>
<label>Text size<select class="input" name="font_size"><option value="small" <?=$p['font_size']==='small'?'selected':''?>>Small</option><option value="medium" <?=$p['font_size']==='medium'?'selected':''?>>Medium</option><option value="large" <?=$p['font_size']==='large'?'selected':''?>>Large</option></select></label>
<label>Budget / display currency<select class="input" name="currency_code"><?php foreach(SUPPORTED_CURRENCIES as $code=>$meta): ?><option value="<?=e($code)?>" <?=$p['currency_code']===$code?'selected':''?>><?=e($code.' · '.$meta['name'])?></option><?php endforeach; ?></select></label>
<div class="currency-note"><strong>18 supported currencies</strong><span>Amounts are displayed in the selected currency across dashboards, budgets, reports and insights. Campus Coin does not perform exchange-rate conversion.</span></div>
<div class="settings-preview"><span>Preview</span><strong>Example budget: <?=e(money(5000,$p['currency_code']))?></strong><small>All interface content stays readable in either theme. Your underlying numeric amounts are not changed when you change display currency.</small></div>
<button class="btn btn-primary">Save settings</button>
</form>
</section>
<section class="panel"><div class="panel-head"><div><span class="panel-kicker">QUICK ACCESS</span><h2>What changes</h2></div></div><div class="settings-feature-list"><article><strong>Theme</strong><p>Switch between the signature dark-green experience and a high-clarity white workspace.</p></article><article><strong>Reading scale</strong><p>Small, medium and large text options are applied to the authenticated application shell.</p></article><article><strong>Budget currency</strong><p>Pick from 18 currencies for budget labels, cards, reports and money insights.</p></article></div></section>
</section>
<?php require __DIR__.'/../includes/footer.php'; ?>
