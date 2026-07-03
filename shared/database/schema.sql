CREATE TABLE IF NOT EXISTS licenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(64) NOT NULL UNIQUE,
    plan VARCHAR(32) NOT NULL DEFAULT 'basic',
    status ENUM('active', 'expired', 'suspended') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    max_accounts INT NOT NULL DEFAULT 1,
    assigned_user_id BIGINT NULL,
    assigned_username VARCHAR(255) NULL,
    activated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS license_activations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    license_id BIGINT NOT NULL,
    telegram_user_id BIGINT NOT NULL,
    telegram_username VARCHAR(255) NULL,
    activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id)
);

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    amount BIGINT NOT NULL,
    type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment') NOT NULL,
    category VARCHAR(64) NOT NULL,
    sub_category VARCHAR(128) NOT NULL,
    notes TEXT NOT NULL,
    source ENUM('manual', 'receipt_photo') NOT NULL DEFAULT 'manual',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_sheets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL UNIQUE,
    spreadsheet_id VARCHAR(128) NOT NULL UNIQUE,
    spreadsheet_url VARCHAR(512) NULL,
    dashboard_version VARCHAR(32) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_synced_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_code VARCHAR(32) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telegram_username VARCHAR(120) NULL,
    plan VARCHAR(32) NOT NULL,
    amount BIGINT NOT NULL,
    currency VARCHAR(8) NOT NULL DEFAULT 'IDR',
    status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    payment_gateway VARCHAR(32) NOT NULL DEFAULT 'midtrans',
    payment_reference VARCHAR(128) NULL,
    payment_url VARCHAR(512) NULL,
    payment_token VARCHAR(128) NULL,
    paid_at DATETIME NULL,
    license_id BIGINT NULL,
    spreadsheet_id VARCHAR(128) NULL,
    spreadsheet_url VARCHAR(512) NULL,
    purchase_delivery_sent_at DATETIME NULL,
    purchase_sheet_wa_sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payment_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    provider VARCHAR(32) NOT NULL,
    event_type VARCHAR(64) NOT NULL,
    payload_json JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE IF NOT EXISTS user_ai_usage (
    telegram_user_id BIGINT NOT NULL,
    usage_month CHAR(7) NOT NULL,
    cost_idr INT UNSIGNED NOT NULL DEFAULT 0,
    text_parse_count INT UNSIGNED NOT NULL DEFAULT 0,
    vision_parse_count INT UNSIGNED NOT NULL DEFAULT 0,
    quota_exhausted_notified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (telegram_user_id, usage_month),
    INDEX idx_user_ai_usage_month (usage_month)
);

CREATE TABLE IF NOT EXISTS bot_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    recorded_at DATETIME NOT NULL,
    type ENUM('Pemasukan', 'Pengeluaran', 'Saving/Investment') NOT NULL,
    category VARCHAR(64) NOT NULL,
    sub_category VARCHAR(128) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    nature VARCHAR(32) NOT NULL,
    mood VARCHAR(32) NOT NULL,
    is_impulsive TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NOT NULL,
    source ENUM('manual', 'receipt_photo') NOT NULL DEFAULT 'manual',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bot_tx_user_date (telegram_user_id, recorded_at),
    INDEX idx_bot_tx_user_type (telegram_user_id, type)
);

CREATE TABLE IF NOT EXISTS financial_baselines (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    assessed_at DATETIME NOT NULL,
    next_review_at DATETIME NOT NULL,
    financial_stage_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    financial_stage VARCHAR(32) NOT NULL,
    stage_label VARCHAR(64) NOT NULL,
    ftsa_chd TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ftsa_rvd TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ftsa_ssd TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ftsa_esd TINYINT UNSIGNED NOT NULL DEFAULT 0,
    dominant_archetype VARCHAR(16) NOT NULL,
    dominant_archetype_label VARCHAR(64) NOT NULL,
    chd_level VARCHAR(32) NULL,
    rvd_level VARCHAR(32) NULL,
    ssd_level VARCHAR(32) NULL,
    esd_level VARCHAR(32) NULL,
    answers_json JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_financial_baselines_user (telegram_user_id, assessed_at)
);
