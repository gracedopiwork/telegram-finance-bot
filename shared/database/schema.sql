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
    type ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
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
