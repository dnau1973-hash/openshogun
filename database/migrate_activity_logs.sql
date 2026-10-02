-- Migration : Création de la table de télémétrie activity_logs
-- Projet : OpenShogun Féodal
-- Auteur : Data/Analytics Architect

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `page_slug` VARCHAR(64) NOT NULL,
    `tab_slug` VARCHAR(64) NULL,
    `action` VARCHAR(32) NOT NULL DEFAULT 'view',
    `ip_hash` VARCHAR(64) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `device_type` ENUM('desktop', 'mobile', 'tablet', 'bot', 'other') DEFAULT 'desktop',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_page_slug` (`page_slug`, `created_at`),
    INDEX `idx_ip_hash` (`ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
