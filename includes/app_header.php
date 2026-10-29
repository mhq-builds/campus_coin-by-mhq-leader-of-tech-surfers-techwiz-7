<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$user=current_user();
if(!$user)redirect('login.php');
$pageTitle=$pageTitle??APP_NAME;
$activePage=$activePage??'dashboard';
$role=$user['role'];

$pref=db()->prepare('SELECT dark_mode,font_size,currency_code FROM user_preferences WHERE user_id=? LIMIT 1');
$pref->execute([(int)$user['user_id']]);
$prefs=$pref->fetch();
if(!$prefs){
    db()->prepare('INSERT INTO user_preferences(user_id,dark_mode,font_size,currency_code) VALUES(?,1,"medium",?)')->execute([(int)$user['user_id'],DEFAULT_CURRENCY]);
    $prefs=['dark_mode'=>1,'font_size'=>'medium','currency_code'=>DEFAULT_CURRENCY];
}
$currencyCode=isset(SUPPORTED_CURRENCIES[$prefs['currency_code']])?$prefs['currency_code']:DEFAULT_CURRENCY;
$_SESSION['user']['currency_code']=$currencyCode;
$themeClass=!empty($prefs['dark_mode'])?'theme-dark':'theme-light';
$fontClass='font-'.($prefs['font_size']??'medium');
$settingsPath=$role==='admin'?'admin/settings.php':'student/settings.php';
$backPath=(string)($_SERVER['REQUEST_URI']??'');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?=e(csrf_token())?>">
<title><?=e($pageTitle)?> · Campus Coin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(asset_url('assets/css/app.css'))?>">
</head>
<body class="app-body <?=e($themeClass.' '.$fontClass)?>">
<div class="app-shell">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a class="brand" href="<?=e(url($role==='admin'?'admin/dashboard.php':'student/dashboard.php'))?>">
            <span class="brand-mark" aria-hidden="true">C</span>
            <span><strong>Campus Coin</strong><small><?=$role==='admin'?'Admin Control':'Student Finance'?></small></span>
        </a>
        <button class="sidebar-x" data-sidebar-close type="button" aria-label="Close navigation">Close</button>
    </div>

    <div class="sidebar-scroll" data-sidebar-scroll>
    <?php if($role==='student'): ?>
    <div class="nav-group">
        <span>OVERVIEW</span>
        <a class="<?=$activePage==='dashboard'?'active':''?>" href="<?=e(url('student/dashboard.php'))?>"><span>Dashboard</span></a>
        <a class="<?=$activePage==='transactions'?'active':''?>" href="<?=e(url('student/transactions.php'))?>"><span>Transactions</span></a>
        <a class="<?=$activePage==='reports'?'active':''?>" href="<?=e(url('student/reports.php'))?>"><span>Reports</span></a>
        <a class="<?=$activePage==='insights'?'active':''?>" href="<?=e(url('student/insights.php'))?>"><span>AI Insights</span></a>
    </div>
    <div class="nav-group">
        <span>PLAN &amp; IMPROVE</span>
        <a class="<?=$activePage==='budgets'?'active':''?>" href="<?=e(url('student/budgets.php'))?>"><span>Budgets</span></a>
        <a class="<?=$activePage==='tips'?'active':''?>" href="<?=e(url('student/saving-tips.php'))?>"><span>Saving Tips</span></a>
        <a class="<?=$activePage==='health'?'active':''?>" href="<?=e(url('student/financial-health.php'))?>"><span>Health Score</span></a>
        <a class="<?=$activePage==='forecast'?'active':''?>" href="<?=e(url('student/forecast.php'))?>"><span>Forecast</span></a>
        <a class="<?=$activePage==='alerts'?'active':''?>" href="<?=e(url('student/smart-alerts.php'))?>"><span>Smart Alerts</span></a>
        <a class="<?=$activePage==='calendar'?'active':''?>" href="<?=e(url('student/calendar.php'))?>"><span>Calendar</span></a>
    </div>
    <div class="nav-group">
        <span>ORGANIZE</span>
        <a class="<?=$activePage==='categories'?'active':''?>" href="<?=e(url('student/categories.php'))?>"><span>Categories</span></a>
        <a class="<?=$activePage==='import'?'active':''?>" href="<?=e(url('student/import.php'))?>"><span>CSV Example</span></a>
        <a class="<?=$activePage==='recurring'?'active':''?>" href="<?=e(url('student/recurring.php'))?>"><span>Recurring</span></a>
    </div>
    <div class="nav-group">
        <span>PERSONAL</span>
        <a class="<?=$activePage==='bookmarks'?'active':''?>" href="<?=e(url('student/bookmarks.php'))?>"><span>Bookmarks</span></a>
        <a class="<?=$activePage==='notes'?'active':''?>" href="<?=e(url('student/notes.php'))?>"><span>Notes</span></a>
        <a class="<?=$activePage==='notifications'?'active':''?>" href="<?=e(url('student/notifications.php'))?>"><span>Notifications</span></a>
        <a class="<?=$activePage==='profile'?'active':''?>" href="<?=e(url('student/profile.php'))?>"><span>Profile</span></a>
        <a class="<?=$activePage==='settings'?'active':''?>" href="<?=e(url('student/settings.php'))?>"><span>Settings</span></a>
    </div>
    <?php else: ?>
    <div class="nav-group">
        <span>CONTROL PANEL</span>
        <a class="<?=$activePage==='dashboard'?'active':''?>" href="<?=e(url('admin/dashboard.php'))?>"><span>Dashboard</span></a>
        <a class="<?=$activePage==='users'?'active':''?>" href="<?=e(url('admin/users.php'))?>"><span>Users</span></a>
        <a class="<?=$activePage==='categories'?'active':''?>" href="<?=e(url('admin/categories.php'))?>"><span>Categories</span></a>
        <a class="<?=$activePage==='tips'?'active':''?>" href="<?=e(url('admin/tips.php'))?>"><span>Tip Templates</span></a>
        <a class="<?=$activePage==='announcements'?'active':''?>" href="<?=e(url('admin/announcements.php'))?>"><span>Announcements</span></a>
        <a class="<?=$activePage==='transactions'?'active':''?>" href="<?=e(url('admin/transactions.php'))?>"><span>Transactions</span></a>
        <a class="<?=$activePage==='analytics'?'active':''?>" href="<?=e(url('admin/analytics.php'))?>"><span>Analytics</span></a>
        <a class="<?=$activePage==='activity'?'active':''?>" href="<?=e(url('admin/activity.php'))?>"><span>Activity Logs</span></a>
        <a class="<?=$activePage==='settings'?'active':''?>" href="<?=e(url('admin/settings.php'))?>"><span>Settings</span></a>
    </div>
    <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a class="profile-mini" href="<?=e(url($role==='admin'?'admin/dashboard.php':'student/profile.php'))?>">
            <span class="avatar"><?=e(strtoupper(substr($user['name'],0,1)))?></span>
            <span><strong><?=e($user['name'])?></strong><small><?=e($user['email'])?></small></span>
        </a>
    </div>
</aside>

<div class="app-main">
<header class="app-top">
    <div class="topbar-left">
        <button class="mobile-menu" data-sidebar-open type="button" aria-label="Open navigation">Menu</button>
        <div class="app-greeting">
            <strong>Hey <?=e(explode(' ',trim($user['name']))[0]?:$user['name'])?> 👋</strong>
            <span>Welcome back · <?=e($user['email'])?></span>
        </div>
        <div class="crumb"><?=e($role==='admin'?'Admin':'Campus Coin')?> <span>/</span> <?=e($pageTitle)?></div>
    </div>
    <div class="top-actions">
        <?php if($role==='student'): ?>
            <?php $nq=db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');$nq->execute([(int)$user['user_id']]);$unread=(int)$nq->fetchColumn(); ?>
            <button class="appearance-trigger" type="button" data-appearance-toggle aria-expanded="false">Appearance</button>
            <a class="top-icon" href="<?=e(url('student/notifications.php'))?>" aria-label="Notifications">Notifications<?= $unread?' <b>'.$unread.'</b>':''?></a>
            <a class="quick-add" href="<?=e(url('student/add-transaction.php'))?>">Add transaction</a>
        <?php else: ?>
            <button class="appearance-trigger" type="button" data-appearance-toggle aria-expanded="false">Appearance</button>
        <?php endif; ?>
        <a class="top-signout" href="<?=e(url('auth/logout.php'))?>">Sign out</a>
    </div>
    <div class="appearance-popover" data-appearance-popover hidden>
        <div class="appearance-popover-head"><strong>Display settings</strong><button type="button" class="appearance-close" data-appearance-close aria-label="Close display settings">×</button></div>
        <form action="<?=e(url('auth/save-preferences.php'))?>" method="post" class="appearance-form">
            <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
            <input type="hidden" name="return_to" value="<?=e($backPath)?>">
            <div class="appearance-section"><span>Theme</span><div class="appearance-choice-row">
                <label><input type="radio" name="appearance" value="dark" <?=!empty($prefs['dark_mode'])?'checked':''?>><span>Dark green</span></label>
                <label><input type="radio" name="appearance" value="light" <?=empty($prefs['dark_mode'])?'checked':''?>><span>White</span></label>
            </div></div>
            <div class="appearance-section"><span>Text size</span><select class="input" name="font_size"><option value="small" <?=$prefs['font_size']==='small'?'selected':''?>>Small</option><option value="medium" <?=$prefs['font_size']==='medium'?'selected':''?>>Medium</option><option value="large" <?=$prefs['font_size']==='large'?'selected':''?>>Large</option></select></div>
            <div class="appearance-section"><span>Budget / display currency</span><select class="input" name="currency_code">
                <?php foreach(SUPPORTED_CURRENCIES as $code=>$meta): ?><option value="<?=e($code)?>" <?=$currencyCode===$code?'selected':''?>><?=e($code.' · '.$meta['name'])?></option><?php endforeach; ?>
            </select></div>
            <div class="appearance-actions"><a href="<?=e(url($settingsPath))?>">Full settings</a><button class="btn btn-primary" type="submit">Apply</button></div>
        </form>
    </div>
</header>
<main class="app-content">
<?php if($m=flash('success')): ?><div class="flash success"><?=e($m)?></div><?php endif; ?>
<?php if($m=flash('error')): ?><div class="flash error"><?=e($m)?></div><?php endif; ?>
