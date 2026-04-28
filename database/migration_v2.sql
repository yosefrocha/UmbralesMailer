-- ============================================================
-- MIGRACIÓN v2 — UmbralesMailer
-- Ejecutar en phpMyAdmin sobre la base de datos existente
-- ============================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 1. Rate limiting — intentos de login fallidos
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45)  NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip (ip_address),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Asegurar columnas necesarias en campaigns
-- -------------------------------------------------------
ALTER TABLE campaigns
    MODIFY COLUMN name VARCHAR(255) NOT NULL,
    MODIFY COLUMN description TEXT NULL,
    MODIFY COLUMN content_mode ENUM('text','html') NOT NULL DEFAULT 'text';

-- -------------------------------------------------------
-- 3. Asegurar columnas en campaign_messages
-- -------------------------------------------------------
ALTER TABLE campaign_messages
    MODIFY COLUMN text_body LONGTEXT NULL,
    MODIFY COLUMN html_body LONGTEXT NULL;

-- -------------------------------------------------------
-- 4. Collation uniforme en todas las tablas
-- -------------------------------------------------------
ALTER TABLE campaigns           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE campaign_messages   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE campaign_recipients CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE recipients          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE send_sessions       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE send_session_items  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE audit_logs          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE password_resets     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE unsubscribe_tokens  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE recipient_imports   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE login_attempts      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. Settings — crear si no existe
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    is_encrypted  TINYINT(1) NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Migración v2 completada correctamente.' AS resultado;
