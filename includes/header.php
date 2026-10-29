<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$pageTitle=$pageTitle??APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle)?> · <?=e(APP_NAME)?></title>
<meta name="description" content="Campus Coin — Smart Spending · Student Style">
<meta name="theme-color" content="#0d2117">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Libre+Caslon+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=e(asset_url('assets/css/app.css'))?>">
</head>
<body class="public-body">
<div class="cc-scroll-progress" aria-hidden="true"></div>
<header class="public-header" data-header>
<div class="container nav-wrap">
<a class="brand" href="<?=e(url('index.php'))?>" aria-label="Campus Coin home"><span class="brand-mark">C</span><span class="brand-copy"><strong>Campus Coin</strong><small>Smart Spending · Student Style</small></span></a>
<button class="public-menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" data-public-menu-toggle><span></span><span></span></button>
<nav class="top-nav" data-public-menu>
<a href="<?=e(url('index.php#features'))?>">Features</a>
<a href="<?=e(url('index.php#intelligence'))?>">Intelligence</a>
<a href="<?=e(url('index.php#flow'))?>">How it works</a>
<a href="<?=e(url('index.php#sitemap'))?>">Sitemap</a>
<button class="public-appearance-trigger" type="button" data-public-appearance-toggle aria-expanded="false">Appearance</button>
<a class="btn btn-ghost" href="<?=e(url('login.php'))?>">Login</a>
<a class="btn btn-primary" href="<?=e(url('register.php'))?>">Get Started</a>
</nav>
<div class="public-appearance-popover" data-public-appearance-popover hidden>
  <div class="appearance-popover-head"><strong>Display settings</strong><button type="button" class="appearance-close" data-public-appearance-close aria-label="Close display settings">×</button></div>
  <div class="appearance-section"><span>Theme</span><div class="appearance-choice-row">
    <button type="button" class="public-theme-choice" data-public-theme="dark">Dark green</button>
    <button type="button" class="public-theme-choice" data-public-theme="light">White</button>
  </div></div>
  <div class="appearance-section"><span>Text size</span><div class="appearance-choice-row">
    <button type="button" class="public-font-choice" data-public-font="medium">Medium</button>
    <button type="button" class="public-font-choice" data-public-font="large">Large</button>
  </div></div>
  <div class="appearance-actions"><a href="<?=e(url('login.php'))?>">Sign in to save preferences</a></div>
</div>
</div>
</header>
