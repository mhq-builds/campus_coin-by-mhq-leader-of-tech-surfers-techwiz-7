# Campus Coin — TechSurfers Final Competition Build

**TechWiz_7 · Full Stack Web Application Development**  
**Stack:** Pure PHP + MySQL + HTML5/CSS3/JavaScript  
**Theme:** NextGen BudgetBee · Smart Spending Student Style

## What this build contains

Campus Coin is a student-first budgeting and spending platform covering the required SRS flow plus connected additional intelligence features:

- Student registration/login/logout and password reset token flow
- Direct administrator login and role protection
- Secure PHP sessions, CSRF tokens and PDO prepared statements
- Editable student profile and accessibility settings
- Income/expense transaction management with history retention
- Default + personal categories
- Recurring transaction support
- CSV import with category assistance
- AI-assisted description-based category suggestions with correction-learning storage
- Monthly budgets, progress, thresholds and in-app alerts
- Monthly insight generation and insight history
- Saving-tip engine with ranking and bookmarking
- Reports, date/category/type filters and browser Print/Save-as-PDF
- Notifications, bookmarks and activity logging
- Financial health score
- Upcoming-month historical forecast
- Duplicate-transaction detector
- Financial calendar
- Admin dashboard, users, categories, tip templates, announcements, transaction ledger, analytics and audit logs
- Responsive mobile/tablet/desktop UI
- Dark green + white display themes, font-size preferences and persistent appearance controls
- 18 supported display/budget currencies (PKR, USD, EUR, GBP, AED, SAR, INR, BDT, LKR, MYR, SGD, CAD, AUD, JPY, CNY, TRY, CHF, ZAR)
- Public landing-page Appearance control beside Sitemap
- Student Appearance control beside Notifications and Admin Appearance control beside Sign out
- Student budget portal: income budget first, expense budgets second
- Dashboard daily-spending chart with no-data fallback and render protection
- Sitemap section on the landing page

## Final folder structure

```text
campus_coin/
├── index.php
├── login.php
├── register.php
├── forgot-password.php
├── reset-password.php
├── .htaccess
├── auth/
├── api/
├── student/
├── admin/
├── config/
├── includes/
├── assets/
└── database/
    └── campus_coin.sql
```

## Installation — XAMPP

1. Extract the **campus_coin** folder into:
   `C:\xampp\htdocs\`
2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin.
4. Import:
   `database/campus_coin.sql`
5. The SQL script creates/recreates the `campus_coin` database for a fresh competition install.
6. Open:
   `http://localhost/campus_coin/`

### Database credentials used by the app

Default local XAMPP configuration:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `campus_coin`
- Username: `root`
- Password: empty

If the local MySQL password is different, edit `config/database.php`.

## Demo credentials

### Administrator

- Email: `admin@campuscoin.local`
- Password: `Admin@12345`

### Student

- Email: `student@campuscoin.local`
- Password: `Student@12345`

Change/remove demo credentials before any public deployment.

## Main demonstration flow

```text
Landing Page
  → Register / Login
  → Student Dashboard
  → Display / Appearance Settings
  → Add Expense / Income
  → Smart Category Suggestion
  → Budget + Progress
  → Notification
  → Reports
  → AI Insight
  → Saving Tip
  → Bookmark
  → CSV Import
  → Health Score / Forecast / Smart Alerts
  → Admin Panel + Analytics
```

## AI usage disclosure

AI tools were used as a development aid for planning, debugging, code assistance and UI ideation. The project team should review, modify, test and understand the implementation before submission and should include the competition-required AI acknowledgement in the official project documentation.

## Important competition note

The official project report/documentation should be created and finalized by the team according to the competition rules. This repository README is an installation/developer aid, not the complete submission report.


## Day 4 final touches

The final competition build keeps the existing Campus Coin theme and workflow while adding the requested last-day polish:

- Appearance settings are reachable from the public navbar and from every authenticated page through the top bar.
- Light/white mode has explicit contrast rules for panels, tables, forms, charts, badges, links, progress bars and supporting text.
- Morning/afternoon/evening greeting uses the configured Pakistan local timezone consistently.
- Budget currency can be selected from 18 supported currencies. This is a display/budget currency selector; it does not perform exchange-rate conversion.
- The dashboard now introduces a two-stage Budget Portal: **Income Budget first**, then **Expense Budgets**, followed by live Budget vs Actual monitoring.
- The Daily Spending chart has explicit rendering protection and a friendly empty state.

## Final submission caution

The supplied demo accounts are for the competition demonstration only. Before any public deployment, replace or remove them and set production database/session credentials.
