<?php declare(strict_types=1); ?>
<footer class="public-footer">
<div class="container footer-grid">
<div class="footer-lead"><span class="footer-kicker">CAMPUS COIN · TECHSURFERS</span><p>A student-first money companion for tracking, planning and understanding everyday campus spending.</p></div>
<div><h4>Explore</h4><a href="<?=e(url('index.php#features'))?>">Features</a><a href="<?=e(url('index.php#intelligence'))?>">Intelligence</a><a href="<?=e(url('index.php#flow'))?>">How it works</a><a href="<?=e(url('index.php#sitemap'))?>">Sitemap</a></div>
<div><h4>Account</h4><a href="<?=e(url('register.php'))?>">Create account</a><a href="<?=e(url('login.php'))?>">Student login</a><a href="<?=e(url('admin/login.php'))?>">Admin login</a></div>
</div>
<div class="container footer-bottom"><span>© <?=date('Y')?> Campus Coin · TechSurfers</span><span>Pure PHP + MySQL</span></div>
</footer>
<script src="<?=e(asset_url('assets/js/app.js'))?>"></script>
</body></html>
