-- Create table for system settings (key-value store)
CREATE TABLE IF NOT EXISTS system_settings (
    name VARCHAR(100) PRIMARY KEY,
    value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed defaults (idempotent upserts depend on DB; here basic inserts)
INSERT IGNORE INTO system_settings (name, value) VALUES
('hotel_name', 'Richards Hotel'),
('currency_symbol', '₱'),
('tax_rate', '0'),
('base_rate_per_hour', '0'),
('email_from', 'no-reply@richardshotel.com'),
('email_from_name', 'Richards Hotel'),
('smtp_port', '587'),
('smtp_encryption', 'tls'),
('logo_path', 'images/logo/logo.png'),
('theme_color', '#0d6efd');