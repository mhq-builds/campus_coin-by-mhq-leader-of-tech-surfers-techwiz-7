USE campus_coin;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ================================================================
-- 1. USERS / AUTHENTICATION
-- ================================================================

CREATE TABLE users (
    user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    academic_year VARCHAR(50) NULL,
    monthly_allowance_baseline DECIMAL(12,2) NULL,
    monthly_savings_goal DECIMAL(12,2) NULL,
    status ENUM('pending', 'active', 'disabled') NOT NULL DEFAULT 'pending',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_allowance CHECK (
        monthly_allowance_baseline IS NULL OR monthly_allowance_baseline >= 0
    ),
    CONSTRAINT chk_users_savings_goal CHECK (
        monthly_savings_goal IS NULL OR monthly_savings_goal >= 0
    )
) ENGINE=InnoDB;

CREATE TABLE user_preferences (
    preference_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    dark_mode BOOLEAN NOT NULL DEFAULT FALSE,
    font_size ENUM('small', 'medium', 'large') NOT NULL DEFAULT 'medium',
    currency_code CHAR(3) NOT NULL DEFAULT 'PKR',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_preferences_user UNIQUE (user_id),
    CONSTRAINT fk_user_preferences_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
    auth_token_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_type ENUM('email_verification', 'password_reset') NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_auth_tokens_hash UNIQUE (token_hash),
    CONSTRAINT fk_auth_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_auth_tokens_user_type (user_id, token_type),
    INDEX idx_auth_tokens_expiry (expires_at)
) ENGINE=InnoDB;

CREATE TABLE user_sessions (
    session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_token_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    expires_at DATETIME NOT NULL,
    last_activity_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_sessions_token UNIQUE (session_token_hash),
    CONSTRAINT fk_user_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_sessions_user (user_id),
    INDEX idx_user_sessions_expiry (expires_at)
) ENGINE=InnoDB;

-- ================================================================
-- 2. CATEGORIES
-- Default categories are system-wide (user_id NULL).
-- Personal categories belong to one student.
-- ================================================================

CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_categories_owner CHECK (
        (is_default = TRUE AND user_id IS NULL)
        OR
        (is_default = FALSE AND user_id IS NOT NULL)
    ),
    CONSTRAINT uq_categories_owner_type_name UNIQUE (user_id, type, name),
    INDEX idx_categories_type_active (type, is_active),
    INDEX idx_categories_user (user_id)
) ENGINE=InnoDB;

-- ================================================================
-- 3. CSV IMPORT
-- ================================================================

CREATE TABLE import_batches (
    import_batch_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    status ENUM('uploaded', 'processing', 'completed', 'completed_with_errors', 'failed')
        NOT NULL DEFAULT 'uploaded',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
    failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_import_batches_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_import_batches_user_date (user_id, created_at)
) ENGINE=InnoDB;

-- ================================================================
-- 4. RECURRING TRANSACTION RULES
-- ================================================================

CREATE TABLE recurring_transactions (
    recurring_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NULL,
    frequency ENUM('daily', 'weekly', 'monthly', 'yearly') NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NULL,
    next_run_date DATE NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_recurring_amount CHECK (amount > 0),
    CONSTRAINT chk_recurring_dates CHECK (ends_on IS NULL OR ends_on >= starts_on),
    CONSTRAINT fk_recurring_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_recurring_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_recurring_due (is_active, next_run_date),
    INDEX idx_recurring_user (user_id)
) ENGINE=InnoDB;

-- ================================================================
-- 5. TRANSACTIONS
-- Soft delete is used so the user can retain transaction history.
-- ================================================================

CREATE TABLE transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    recurring_id BIGINT UNSIGNED NULL,
    import_batch_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description VARCHAR(255) NULL,
    ai_suggested_category_id INT UNSIGNED NULL,
    ai_confidence DECIMAL(5,4) NULL,
    source ENUM('manual', 'csv_import', 'recurring') NOT NULL DEFAULT 'manual',
    transaction_date DATE NOT NULL,
    status ENUM('active', 'deleted') NOT NULL DEFAULT 'active',
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_transactions_amount CHECK (amount > 0),
    CONSTRAINT chk_transactions_ai_confidence CHECK (
        ai_confidence IS NULL OR (ai_confidence >= 0 AND ai_confidence <= 1)
    ),
    CONSTRAINT chk_transactions_delete_state CHECK (
        (status = 'active' AND deleted_at IS NULL)
        OR
        (status = 'deleted' AND deleted_at IS NOT NULL)
    ),
    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_recurring
        FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(recurring_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_import_batch
        FOREIGN KEY (import_batch_id) REFERENCES import_batches(import_batch_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_transactions_ai_category
        FOREIGN KEY (ai_suggested_category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_transactions_user_date (user_id, transaction_date),
    INDEX idx_transactions_user_type_date (user_id, type, transaction_date),
    INDEX idx_transactions_category_date (category_id, transaction_date),
    INDEX idx_transactions_active (user_id, status)
) ENGINE=InnoDB;

CREATE TABLE import_rows (
    import_row_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    raw_data JSON NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    suggested_category_id INT UNSIGNED NULL,
    suggestion_confidence DECIMAL(5,4) NULL,
    status ENUM('pending', 'imported', 'skipped', 'failed') NOT NULL DEFAULT 'pending',
    error_message VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_import_rows_confidence CHECK (
        suggestion_confidence IS NULL
        OR (suggestion_confidence >= 0 AND suggestion_confidence <= 1)
    ),
    CONSTRAINT fk_import_rows_batch
        FOREIGN KEY (import_batch_id) REFERENCES import_batches(import_batch_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_import_rows_transaction
        FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_import_rows_category
        FOREIGN KEY (suggested_category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT uq_import_rows_batch_row UNIQUE (import_batch_id, row_number)
) ENGINE=InnoDB;

-- ================================================================
-- 6. TRANSACTION HISTORY / AI LEARNING
-- ================================================================

CREATE TABLE transaction_revisions (
    revision_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    changed_by_user_id BIGINT UNSIGNED NULL,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE', 'RESTORE') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaction_revisions_transaction
        FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_transaction_revisions_user
        FOREIGN KEY (changed_by_user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_transaction_revisions_transaction (transaction_id, changed_at),
    INDEX idx_transaction_revisions_user (changed_by_user_id, changed_at)
) ENGINE=InnoDB;

CREATE TABLE ai_category_suggestions (
    suggestion_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    description_text VARCHAR(255) NULL,
    suggested_category_id INT UNSIGNED NOT NULL,
    accepted_category_id INT UNSIGNED NULL,
    confidence DECIMAL(5,4) NULL,
    was_overridden BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT chk_ai_suggestion_confidence CHECK (
        confidence IS NULL OR (confidence >= 0 AND confidence <= 1)
    ),
    CONSTRAINT fk_ai_suggestions_transaction
        FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ai_suggestions_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ai_suggestions_category
        FOREIGN KEY (suggested_category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ai_suggestions_accepted_category
        FOREIGN KEY (accepted_category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_ai_suggestions_user (user_id, created_at),
    INDEX idx_ai_suggestions_transaction (transaction_id)
) ENGINE=InnoDB;

-- ================================================================
-- 7. BUDGETS
-- One budget per user/category/month.
-- ================================================================

CREATE TABLE budgets (
    budget_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    month_start DATE NOT NULL,
    limit_amount DECIMAL(12,2) NOT NULL,
    alert_threshold_percent DECIMAL(5,2) NOT NULL DEFAULT 80.00,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_budgets_amount CHECK (limit_amount >= 0),
    CONSTRAINT chk_budgets_threshold CHECK (
        alert_threshold_percent >= 0 AND alert_threshold_percent <= 100
    ),
    CONSTRAINT fk_budgets_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_budgets_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT uq_budgets_user_category_month UNIQUE (user_id, category_id, month_start),
    INDEX idx_budgets_user_month (user_id, month_start)
) ENGINE=InnoDB;

-- ================================================================
-- 8. AI MONTHLY INSIGHTS
-- ================================================================

CREATE TABLE insights (
    insight_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    month_start DATE NOT NULL,
    version_no INT UNSIGNED NOT NULL DEFAULT 1,
    summary_text TEXT NOT NULL,
    tip_text TEXT NULL,
    flagged_category_id INT UNSIGNED NULL,
    flagged_growth_percent DECIMAL(8,2) NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_insights_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_insights_category
        FOREIGN KEY (flagged_category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT uq_insights_user_month_version UNIQUE (user_id, month_start, version_no),
    INDEX idx_insights_user_month (user_id, month_start)
) ENGINE=InnoDB;

-- ================================================================
-- 9. SAVING TIPS ENGINE
-- ================================================================

CREATE TABLE saving_tip_templates (
    template_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by_user_id BIGINT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    tip_text TEXT NOT NULL,
    rationale_text TEXT NULL,
    base_priority DECIMAL(8,2) NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tip_templates_creator
        FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tip_templates_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_tip_templates_active (is_active, base_priority)
) ENGINE=InnoDB;

CREATE TABLE saving_tips (
    saving_tip_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    period_month DATE NOT NULL,
    title VARCHAR(150) NOT NULL,
    tip_text TEXT NOT NULL,
    rationale_text TEXT NULL,
    potential_savings_amount DECIMAL(12,2) NULL,
    impact_score DECIMAL(10,2) NOT NULL DEFAULT 0,
    rank_position INT UNSIGNED NULL,
    status ENUM('active', 'dismissed') NOT NULL DEFAULT 'active',
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dismissed_at DATETIME NULL,
    pinned_at DATETIME NULL,
    CONSTRAINT chk_saving_tip_savings CHECK (
        potential_savings_amount IS NULL OR potential_savings_amount >= 0
    ),
    CONSTRAINT fk_saving_tips_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_saving_tips_template
        FOREIGN KEY (template_id) REFERENCES saving_tip_templates(template_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_saving_tips_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_saving_tips_dashboard (user_id, period_month, status, impact_score)
) ENGINE=InnoDB;

-- ================================================================
-- 10. NOTIFICATIONS
-- ================================================================

CREATE TABLE notifications (
    notification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type ENUM(
        'budget_near_limit',
        'budget_exceeded',
        'insight_ready',
        'saving_tip',
        'system',
        'announcement'
    ) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    related_budget_id BIGINT UNSIGNED NULL,
    related_insight_id BIGINT UNSIGNED NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_budget
        FOREIGN KEY (related_budget_id) REFERENCES budgets(budget_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_insight
        FOREIGN KEY (related_insight_id) REFERENCES insights(insight_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_notifications_user_unread (user_id, is_read, created_at)
) ENGINE=InnoDB;

-- ================================================================
-- 11. BOOKMARKS + NOTES
-- Supports saving tips and monthly insights for later reference.
-- ================================================================

CREATE TABLE bookmarks (
    bookmark_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('saving_tip', 'insight', 'report') NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookmarks_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_bookmarks_user_item UNIQUE (user_id, item_type, item_id),
    INDEX idx_bookmarks_user (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE user_notes (
    note_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('saving_tip', 'insight', 'transaction', 'report') NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    note_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_notes_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_notes_user_item (user_id, item_type, item_id)
) ENGINE=InnoDB;

-- ================================================================
-- 12. REPORT EXPORT + SHARING
-- ================================================================

CREATE TABLE report_exports (
    report_export_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    report_type ENUM(
        'monthly',
        'six_month',
        'daily',
        'weekly',
        'savings_summary'
    ) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    export_format ENUM('pdf', 'image') NOT NULL,
    file_path VARCHAR(500) NULL,
    status ENUM('queued', 'generated', 'failed') NOT NULL DEFAULT 'queued',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_report_export_dates CHECK (end_date >= start_date),
    CONSTRAINT fk_report_exports_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_report_exports_user_date (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE report_shares (
    share_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_export_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    share_type ENUM('email', 'download_link') NOT NULL,
    recipient_email VARCHAR(190) NULL,
    shared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    CONSTRAINT fk_report_shares_report
        FOREIGN KEY (report_export_id) REFERENCES report_exports(report_export_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_report_shares_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_report_shares_user (user_id, shared_at)
) ENGINE=InnoDB;

-- ================================================================
-- 13. ADMIN / SYSTEM CONTENT
-- ================================================================

CREATE TABLE announcements (
    announcement_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by_user_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_announcements_dates CHECK (
        ends_at IS NULL OR starts_at IS NULL OR ends_at >= starts_at
    ),
    CONSTRAINT fk_announcements_creator
        FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_announcements_active (is_active, starts_at, ends_at)
) ENGINE=InnoDB;

-- ================================================================
-- 14. ADVANCED SYSTEM INTELLIGENCE
-- ================================================================

CREATE TABLE activity_logs (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action_type ENUM('view', 'create', 'update', 'delete', 'login', 'logout', 'export', 'share')
        NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_activity_logs_user_time (user_id, created_at),
    INDEX idx_activity_logs_entity (entity_type, entity_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE monthly_forecasts (
    forecast_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    forecast_month DATE NOT NULL,
    projected_income DECIMAL(12,2) NOT NULL DEFAULT 0,
    projected_expense DECIMAL(12,2) NOT NULL DEFAULT 0,
    projected_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    confidence_score DECIMAL(5,4) NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_forecast_values CHECK (
        projected_income >= 0 AND projected_expense >= 0
    ),
    CONSTRAINT chk_forecast_confidence CHECK (
        confidence_score IS NULL
        OR (confidence_score >= 0 AND confidence_score <= 1)
    ),
    CONSTRAINT fk_monthly_forecasts_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_monthly_forecasts_user_month UNIQUE (user_id, forecast_month),
    INDEX idx_monthly_forecasts_user (user_id, forecast_month)
) ENGINE=InnoDB;

CREATE TABLE transaction_flags (
    flag_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    flag_type ENUM('unusually_large', 'possible_duplicate') NOT NULL,
    score DECIMAL(8,4) NULL,
    explanation TEXT NULL,
    status ENUM('open', 'reviewed', 'dismissed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_transaction_flags_transaction
        FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_transaction_flags_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_transaction_flags_user_status (user_id, status, created_at),
    INDEX idx_transaction_flags_transaction (transaction_id)
) ENGINE=InnoDB;

-- ================================================================
-- 15. REPORTING VIEWS
-- ================================================================

CREATE OR REPLACE VIEW vw_user_monthly_balance AS
SELECT
    t.user_id,
    DATE_FORMAT(t.transaction_date, '%Y-%m-01') AS month_start,
    SUM(CASE WHEN t.type = 'income'  THEN t.amount ELSE 0 END) AS total_income,
    SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) AS total_expense,
    SUM(CASE
        WHEN t.type = 'income' THEN t.amount
        WHEN t.type = 'expense' THEN -t.amount
        ELSE 0
    END) AS balance
FROM transactions t
WHERE t.status = 'active'
GROUP BY t.user_id, DATE_FORMAT(t.transaction_date, '%Y-%m-01');

CREATE OR REPLACE VIEW vw_budget_vs_actual AS
SELECT
    b.budget_id,
    b.user_id,
    b.category_id,
    c.name AS category_name,
    b.month_start,
    b.limit_amount,
    COALESCE(SUM(
        CASE
            WHEN t.status = 'active'
                 AND t.type = 'expense'
                 AND t.transaction_date >= b.month_start
                 AND t.transaction_date < DATE_ADD(b.month_start, INTERVAL 1 MONTH)
            THEN t.amount
            ELSE 0
        END
    ), 0) AS actual_spending,
    ROUND(
        COALESCE(SUM(
            CASE
                WHEN t.status = 'active'
                     AND t.type = 'expense'
                     AND t.transaction_date >= b.month_start
                     AND t.transaction_date < DATE_ADD(b.month_start, INTERVAL 1 MONTH)
                THEN t.amount
                ELSE 0
            END
        ), 0) / NULLIF(b.limit_amount, 0) * 100,
        2
    ) AS consumption_percent
FROM budgets b
INNER JOIN categories c ON c.category_id = b.category_id
LEFT JOIN transactions t
    ON t.user_id = b.user_id
   AND t.category_id = b.category_id
GROUP BY
    b.budget_id,
    b.user_id,
    b.category_id,
    c.name,
    b.month_start,
    b.limit_amount;

CREATE OR REPLACE VIEW vw_category_usage AS
SELECT
    c.category_id,
    c.user_id,
    c.name,
    c.type,
    c.is_default,
    COUNT(t.transaction_id) AS transaction_count,
    COALESCE(SUM(
        CASE WHEN t.status = 'active' THEN t.amount ELSE 0 END
    ), 0) AS total_amount
FROM categories c
LEFT JOIN transactions t ON t.category_id = c.category_id
GROUP BY c.category_id, c.user_id, c.name, c.type, c.is_default;

-- ================================================================
-- 16. TRIGGERS
-- Enforce category ownership/type consistency and retain transaction history.
-- ================================================================

DELIMITER $$

CREATE TRIGGER trg_transactions_before_insert
BEFORE INSERT ON transactions
FOR EACH ROW
BEGIN
    DECLARE category_type VARCHAR(20);
    DECLARE category_owner BIGINT UNSIGNED;
    DECLARE category_active BOOLEAN;

    SELECT type, user_id, is_active
      INTO category_type, category_owner, category_active
      FROM categories
     WHERE category_id = NEW.category_id
     LIMIT 1;

    IF category_active IS NULL OR category_active = FALSE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selected category does not exist or is inactive.';
    END IF;

    IF category_type <> NEW.type THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaction type does not match category type.';
    END IF;

    IF category_owner IS NOT NULL AND category_owner <> NEW.user_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'User cannot use another user''s personal category.';
    END IF;
END$$

CREATE TRIGGER trg_transactions_before_update
BEFORE UPDATE ON transactions
FOR EACH ROW
BEGIN
    DECLARE category_type VARCHAR(20);
    DECLARE category_owner BIGINT UNSIGNED;
    DECLARE category_active BOOLEAN;

    SELECT type, user_id, is_active
      INTO category_type, category_owner, category_active
      FROM categories
     WHERE category_id = NEW.category_id
     LIMIT 1;

    IF category_active IS NULL OR category_active = FALSE THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selected category does not exist or is inactive.';
    END IF;

    IF category_type <> NEW.type THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Transaction type does not match category type.';
    END IF;

    IF category_owner IS NOT NULL AND category_owner <> NEW.user_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'User cannot use another user''s personal category.';
    END IF;
END$$

CREATE TRIGGER trg_transactions_after_insert
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    INSERT INTO transaction_revisions (
        transaction_id,
        changed_by_user_id,
        action_type,
        old_values,
        new_values
    )
    VALUES (
        NEW.transaction_id,
        NEW.user_id,
        'INSERT',
        NULL,
        JSON_OBJECT(
            'user_id', NEW.user_id,
            'category_id', NEW.category_id,
            'amount', NEW.amount,
            'type', NEW.type,
            'description', NEW.description,
            'transaction_date', NEW.transaction_date,
            'source', NEW.source,
            'status', NEW.status
        )
    );
END$$

CREATE TRIGGER trg_transactions_after_update
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    INSERT INTO transaction_revisions (
        transaction_id,
        changed_by_user_id,
        action_type,
        old_values,
        new_values
    )
    VALUES (
        NEW.transaction_id,
        NEW.user_id,
        CASE
            WHEN OLD.status = 'deleted' AND NEW.status = 'active' THEN 'RESTORE'
            WHEN OLD.status = 'active' AND NEW.status = 'deleted' THEN 'DELETE'
            ELSE 'UPDATE'
        END,
        JSON_OBJECT(
            'user_id', OLD.user_id,
            'category_id', OLD.category_id,
            'amount', OLD.amount,
            'type', OLD.type,
            'description', OLD.description,
            'transaction_date', OLD.transaction_date,
            'source', OLD.source,
            'status', OLD.status,
            'deleted_at', OLD.deleted_at
        ),
        JSON_OBJECT(
            'user_id', NEW.user_id,
            'category_id', NEW.category_id,
            'amount', NEW.amount,
            'type', NEW.type,
            'description', NEW.description,
            'transaction_date', NEW.transaction_date,
            'source', NEW.source,
            'status', NEW.status,
            'deleted_at', NEW.deleted_at
        )
    );
END$$

-- Hard DELETE is intentionally not audited here because transactions use
-- soft deletion. The application should mark status='deleted' and set
-- deleted_at so the full history remains available in transaction_revisions.

DELIMITER ;

-- ================================================================
-- 17. DEFAULT SYSTEM CATEGORIES
-- Income: Allowance, Part-time Job, Scholarship, Gift, Other Income
-- Expense: Food, Transport, Hostel/Rent, Academics, Subscriptions,
--          Entertainment, Miscellaneous
-- ================================================================

INSERT INTO categories (user_id, name, type, is_default, is_active) VALUES
(NULL, 'Allowance',       'income',  TRUE, TRUE),
(NULL, 'Part-time Job',   'income',  TRUE, TRUE),
(NULL, 'Scholarship',     'income',  TRUE, TRUE),
(NULL, 'Gift',            'income',  TRUE, TRUE),
(NULL, 'Other Income',    'income',  TRUE, TRUE),

(NULL, 'Food',            'expense', TRUE, TRUE),
(NULL, 'Transport',      'expense', TRUE, TRUE),
(NULL, 'Hostel/Rent',     'expense', TRUE, TRUE),
(NULL, 'Academics',       'expense', TRUE, TRUE),
(NULL, 'Subscriptions',   'expense', TRUE, TRUE),
(NULL, 'Entertainment',   'expense', TRUE, TRUE),
(NULL, 'Miscellaneous',   'expense', TRUE, TRUE);

-- ================================================================
-- 18. STARTER SAVING-TIP TEMPLATES
-- Admin can add/edit/remove these later through the control panel.
-- ================================================================

INSERT INTO saving_tip_templates
    (created_by_user_id, category_id, title, tip_text, rationale_text, base_priority, is_active)
VALUES
(
    NULL,
    (SELECT category_id FROM categories WHERE name = 'Food' AND type = 'expense' AND is_default = TRUE LIMIT 1),
    'Reduce food-delivery frequency',
    'Compare your recent food-delivery spending with your monthly pattern and set a realistic weekly limit.',
    'Useful when food spending rises above the student''s own historical average.',
    80,
    TRUE
),
(
    NULL,
    (SELECT category_id FROM categories WHERE name = 'Subscriptions' AND type = 'expense' AND is_default = TRUE LIMIT 1),
    'Review recurring subscriptions',
    'Review recurring subscriptions and keep only the services you currently use.',
    'Can reduce repeated monthly expenses without changing essential spending.',
    70,
    TRUE
),
(
    NULL,
    (SELECT category_id FROM categories WHERE name = 'Transport' AND type = 'expense' AND is_default = TRUE LIMIT 1),
    'Track transport patterns',
    'Review your transport spending and compare it with your recent weekly average before setting a target.',
    'Helps identify recurring transport costs that may be reduced or planned better.',
    60,
    TRUE
);

INSERT INTO announcements (created_by_user_id,title,message,starts_at,ends_at,is_active) VALUES (NULL,'Welcome to Campus Coin','Track income and expenses, set budgets and review your monthly insights.',CURRENT_TIMESTAMP,NULL,TRUE);

-- ================================================================
-- 19. DEMO ACCOUNTS
-- Development/demo credentials. Change before public deployment.
-- Admin: admin@campuscoin.local / Admin@12345
-- Student: student@campuscoin.local / Student@12345
-- ================================================================

INSERT INTO users
    (role, name, email, password_hash, academic_year, monthly_allowance_baseline, monthly_savings_goal, status, email_verified_at)
VALUES
    ('admin', 'Campus Coin Administrator', 'admin@campuscoin.local', '$2y$12$qpvaJLV89HL.NBHFDLGv5OEzTFYoTPP9IycI/vHXuOi9dlHLrZxQy', NULL, NULL, NULL, 'active', CURRENT_TIMESTAMP),
    ('student', 'Hassan Demo', 'student@campuscoin.local', '$2y$12$iczx0ANrKY8fyhDuStqtrue.658qHjn/0kXihj6M3eN8qtxR7W3wi', '2nd Year', 60000.00, 15000.00, 'active', CURRENT_TIMESTAMP);

INSERT INTO user_preferences (user_id, dark_mode, font_size, currency_code)
SELECT user_id, TRUE, 'medium', 'PKR'
FROM users
WHERE email IN ('admin@campuscoin.local', 'student@campuscoin.local');

-- Demo allowance
INSERT INTO transactions
    (user_id, category_id, amount, type, description, source, transaction_date)
SELECT u.user_id, c.category_id, 60000.00, 'income', 'Monthly allowance', 'manual', DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
FROM users u
CROSS JOIN categories c
WHERE u.email = 'student@campuscoin.local'
  AND c.name = 'Allowance' AND c.type = 'income' AND c.is_default = TRUE;

-- Demo expenses
INSERT INTO transactions
    (user_id, category_id, amount, type, description, source, transaction_date)
SELECT u.user_id, c.category_id, x.amount, 'expense', x.description, 'manual', x.dt
FROM users u
CROSS JOIN (
    SELECT 1200.00 amount, 'Campus Cafe' description, 'Food' cat, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) dt
    UNION ALL SELECT 650.00, 'Bus and metro', 'Transport', DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY)
    UNION ALL SELECT 3200.00, 'Hostel contribution', 'Hostel/Rent', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY)
    UNION ALL SELECT 1800.00, 'Books and stationery', 'Academics', DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
    UNION ALL SELECT 699.00, 'Streaming subscription', 'Subscriptions', DATE_SUB(CURRENT_DATE, INTERVAL 8 DAY)
    UNION ALL SELECT 950.00, 'Cinema outing', 'Entertainment', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY)
) AS x
INNER JOIN categories c
    ON c.name = x.cat AND c.type = 'expense' AND c.is_default = TRUE
WHERE u.email = 'student@campuscoin.local';

-- Demo budgets
INSERT INTO budgets
    (user_id, category_id, month_start, limit_amount, alert_threshold_percent, is_active)
SELECT u.user_id, c.category_id, DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
       CASE c.name
           WHEN 'Food' THEN 5000.00
           WHEN 'Transport' THEN 3000.00
           WHEN 'Academics' THEN 4000.00
           WHEN 'Subscriptions' THEN 1500.00
           WHEN 'Entertainment' THEN 2500.00
           ELSE 5000.00
       END,
       80.00, TRUE
FROM users u
INNER JOIN categories c
    ON c.type = 'expense' AND c.is_default = TRUE
WHERE u.email = 'student@campuscoin.local'
  AND c.name IN ('Food','Transport','Academics','Subscriptions','Entertainment');

-- END OF MASTER DATABASE
-- ================================================================
