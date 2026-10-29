<?php
declare(strict_types=1);
$pageTitle = 'Smart student budgeting';
require __DIR__ . '/includes/header.php';
?>
<main>
<section class="cc-hero" aria-labelledby="hero-title">
  <div class="cc-hero-grid" aria-hidden="true"></div>
  <div class="cc-hero-glow cc-glow-a" aria-hidden="true"></div>
  <div class="cc-hero-glow cc-glow-b" aria-hidden="true"></div>
  <div class="container cc-hero-inner">
    <div class="cc-hero-copy" data-reveal>
      <p class="cc-overline">NEXTGEN BUDGETBEE · CAMPUS COIN</p>
      <h1 id="hero-title">Money moves.<br><span class="cc-serif">Campus life keeps moving.</span></h1>
      <p class="cc-hero-lede">A student-first money companion for tracking income, expenses, budgets and personal saving insights — clear enough to use every day.</p>
      <div class="cc-hero-actions">
        <a class="cc-button cc-button-primary" href="<?=e(url('register.php'))?>">Create free account <span>↗</span></a>
        <a class="cc-text-link" href="#flow">See how it works <span>↓</span></a>
      </div>
      <div class="cc-hero-meta"><span>PURE PHP</span><i></i><span>MYSQL</span><i></i><span>RESPONSIVE WEB</span></div>
    </div>

    <div class="cc-hero-stage" data-reveal data-reveal-delay="140" aria-label="Campus Coin dashboard preview">
      <div class="cc-window">
        <div class="cc-window-top"><span class="cc-window-dots"><i></i><i></i><i></i></span><span>campus_coin / dashboard</span><span class="cc-live-dot">LIVE</span></div>
        <div class="cc-window-body">
          <div class="cc-preview-head"><div><small>GOOD EVENING</small><strong>Your financial picture</strong></div><span>SEP 2026</span></div>
          <div class="cc-preview-stats">
            <article><small>MONTH BALANCE</small><strong class="cc-positive">Rs. 42,850</strong><span>+8.4% vs last month</span></article>
            <article><small>EXPENSES</small><strong class="cc-negative">Rs. 17,150</strong><span>66% of monthly cap</span></article>
            <article><small>TOP CATEGORY</small><strong>Academics</strong><span>Rs. 3,600 this month</span></article>
          </div>
          <div class="cc-preview-chart">
            <div class="cc-chart-grid"></div>
            <svg viewBox="0 0 560 180" preserveAspectRatio="none" role="img" aria-label="Spending trend preview">
              <path d="M0,140 C55,132 65,105 115,112 S180,150 232,94 S304,82 355,110 S430,62 492,73 S535,50 560,35" fill="none" stroke="#8fd6a9" stroke-width="3" stroke-linecap="round"/>
              <path d="M0,152 C60,146 90,151 125,135 S185,143 235,122 S312,136 360,105 S430,117 492,94 S540,100 560,82" fill="none" stroke="#8ec7de" stroke-width="1.5" stroke-linecap="round" opacity=".8"/>
            </svg>
            <div class="cc-chart-labels"><span>01</span><span>07</span><span>14</span><span>21</span><span>30</span></div>
          </div>
          <div class="cc-preview-bottom"><span>Budget discipline <b>84%</b></span><span>Saving goal <b>70%</b></span><span>AI assist <b>Ready</b></span></div>
        </div>
      </div>
      <div class="cc-scroll-cue"><span>SCROLL</span><i></i></div>
    </div>
  </div>
</section>

<section class="cc-statement cc-section" id="statement">
  <div class="container">
    <div class="cc-section-kicker">01 / THE PROBLEM</div>
    <div class="cc-statement-grid">
      <h2>Student money is<br><span class="cc-serif">messy by nature.</span></h2>
      <div>
        <p>Allowance, transport, canteen food, hostel costs, books, subscriptions and one-off plans all land in different places. Campus Coin turns that everyday mess into one understandable financial picture.</p>
        <a class="cc-inline-link" href="#features">Explore the toolkit <span>↘</span></a>
      </div>
    </div>
  </div>
</section>

<section class="cc-story cc-section" id="flow">
  <div class="container">
    <div class="cc-section-kicker">02 / HOW IT WORKS</div>
    <div class="cc-story-grid">
      <div class="cc-story-sticky">
        <h2>From first login<br><span class="cc-serif">to better habits.</span></h2>
        <p>Four simple stages keep the journey easy to explain and easy to use during a live evaluation.</p>
        <a class="cc-button cc-button-dark" href="<?=e(url('register.php'))?>">Start the journey <span>↗</span></a>
      </div>
      <div class="cc-steps">
        <article class="cc-step" data-reveal><div class="cc-step-index">01</div><div><h3>Register</h3><p>Create your student profile, academic year and savings goal.</p></div></article>
        <article class="cc-step" data-reveal><div class="cc-step-index">02</div><div><h3>Track</h3><p>Log income and expenses quickly, choose categories or let Smart Assist suggest one.</p></div></article>
        <article class="cc-step" data-reveal><div class="cc-step-index">03</div><div><h3>Understand</h3><p>Use budgets, reports, charts and monthly insights to see where your money actually went.</p></div></article>
        <article class="cc-step" data-reveal><div class="cc-step-index">04</div><div><h3>Improve</h3><p>Pin useful saving tips, watch alerts and carry a clearer plan into the next month.</p></div></article>
      </div>
    </div>
  </div>
</section>

<section class="cc-tools cc-section" id="features">
  <div class="container">
    <div class="cc-section-head" data-reveal>
      <div><div class="cc-section-kicker">03 / THE TOOLKIT</div><h2>Everything in one place.<br><span class="cc-serif">Nothing hidden behind clutter.</span></h2></div>
      <p>Every card below opens the part of Campus Coin it describes. The interface stays focused on real student actions rather than decorative dashboards.</p>
    </div>
    <div class="cc-feature-grid">
      <a class="cc-feature" data-reveal href="<?=e(url('student/add-transaction.php'))?>"><span class="cc-feature-no">01</span><span class="cc-feature-icon">₨</span><h3>Fast transactions</h3><p>Record income or expense with student-relevant categories.</p><span class="cc-feature-link">Open transaction center ↗</span></a>
      <a class="cc-feature" data-reveal href="<?=e(url('student/budgets.php'))?>"><span class="cc-feature-no">02</span><span class="cc-feature-icon">◔</span><h3>Budget vs actual</h3><p>Set category caps, watch progress and respond before the month drifts.</p><span class="cc-feature-link">Open budgets ↗</span></a>
      <a class="cc-feature" data-reveal href="<?=e(url('student/add-transaction.php'))?>"><span class="cc-feature-no">03</span><span class="cc-feature-icon">AI</span><h3>Smart category assist</h3><p>Get optional category suggestions while entering a description.</p><span class="cc-feature-link">Try smart assist ↗</span></a>
      <a class="cc-feature" data-reveal href="<?=e(url('student/reports.php'))?>"><span class="cc-feature-no">04</span><span class="cc-feature-icon">↗</span><h3>Reports & trends</h3><p>Move from daily activity to monthly and six-month patterns.</p><span class="cc-feature-link">Open reports ↗</span></a>
      <a class="cc-feature" data-reveal href="<?=e(url('student/saving-tips.php'))?>"><span class="cc-feature-no">05</span><span class="cc-feature-icon">◎</span><h3>Saving tips</h3><p>Turn your own history into ranked, actionable suggestions.</p><span class="cc-feature-link">Open saving engine ↗</span></a>
      <a class="cc-feature" data-reveal href="<?=e(url('student/settings.php'))?>"><span class="cc-feature-no">06</span><span class="cc-feature-icon">06</span><h3>Student-first privacy</h3><p>Your dashboard and transaction history stay tied to your authenticated account.</p><span class="cc-feature-link">Open settings ↗</span></a>
    </div>
  </div>
</section>

<section class="cc-intelligence cc-section" id="intelligence">
  <div class="container">
    <div class="cc-intel-frame" data-reveal>
      <div class="cc-intel-copy">
        <div class="cc-section-kicker">04 / OPTIONAL INTELLIGENCE</div>
        <h2>Useful guidance,<br><span class="cc-serif">not a black box.</span></h2>
        <p>Campus Coin can suggest categories, surface monthly patterns and turn current spending into saving advice. The student remains in control and can override the suggestion.</p>
        <div class="cc-intel-list"><span><b>01</b> Category suggestion</span><span><b>02</b> Monthly narrative</span><span><b>03</b> Actionable saving tip</span></div>
        <a class="cc-inline-link" href="<?=e(url('student/insights.php'))?>">Open AI Insights ↗</a>
      </div>
      <div class="cc-intel-board" aria-hidden="true">
        <div class="cc-note cc-note-a"><small>MONTHLY INSIGHT</small><strong>Academics is your largest expense this month.</strong><span>Pattern-based review</span></div>
        <div class="cc-note cc-note-b"><small>SAVING TIP</small><strong>Reduce academics spending by 10%.</strong><span>Potential: Rs. 360</span></div>
        <div class="cc-note cc-note-c"><small>BUDGET HEALTH</small><strong>84%</strong><span>On track</span></div>
        <div class="cc-orbit cc-orbit-one"></div><div class="cc-orbit cc-orbit-two"></div>
      </div>
    </div>
  </div>
</section>

<section class="cc-footer-cta cc-section">
  <div class="container">
    <div class="cc-cta-panel" data-reveal>
      <div><div class="cc-section-kicker">05 / READY</div><h2>Make the next month<br><span class="cc-serif">easier to understand.</span></h2></div>
      <div><p>One clean workflow. Your own data. A campus-first money system built to be explained as clearly as it is used.</p><div class="cc-hero-actions"><a class="cc-button cc-button-primary" href="<?=e(url('register.php'))?>">Create account <span>↗</span></a><a class="cc-text-link dark" href="<?=e(url('login.php'))?>">Sign in <span>↗</span></a></div></div>
    </div>
  </div>
</section>

<section class="cc-sitemap cc-section" id="sitemap">
  <div class="container">
    <div class="cc-section-kicker">06 / SITEMAP</div>
    <div class="cc-section-head"><h2>Every route.<br><span class="cc-serif">One clear system.</span></h2><p>Direct links keep the project easy to navigate during a live demo.</p></div>
    <div class="cc-sitemap-grid">
      <div data-reveal><b>Public</b><a href="<?=e(url('login.php'))?>">Login</a><a href="<?=e(url('register.php'))?>">Register</a><a href="<?=e(url('forgot-password.php'))?>">Password recovery</a></div>
      <div data-reveal><b>Student</b><a href="<?=e(url('student/dashboard.php'))?>">Dashboard</a><a href="<?=e(url('student/transactions.php'))?>">Transactions</a><a href="<?=e(url('student/reports.php'))?>">Reports</a><a href="<?=e(url('student/insights.php'))?>">AI insights</a></div>
      <div data-reveal><b>Planning</b><a href="<?=e(url('student/budgets.php'))?>">Budgets</a><a href="<?=e(url('student/saving-tips.php'))?>">Saving tips</a><a href="<?=e(url('student/financial-health.php'))?>">Health score</a><a href="<?=e(url('student/forecast.php'))?>">Forecast</a></div>
      <div data-reveal><b>Tools</b><a href="<?=e(url('student/import.php'))?>">CSV import</a><a href="<?=e(url('student/calendar.php'))?>">Calendar</a><a href="<?=e(url('student/notifications.php'))?>">Notifications</a><a href="<?=e(url('admin/login.php'))?>">Admin</a></div>
    </div>
  </div>
</section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
