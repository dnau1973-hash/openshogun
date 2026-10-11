-- Migration : Système d'Événements Hebdomadaires Narratifs (Chroniques Féodales du Sengoku)

CREATE TABLE IF NOT EXISTS `weekly_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(64) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `jp_title` VARCHAR(150) NOT NULL DEFAULT '',
    `era_year` VARCHAR(60) NOT NULL DEFAULT 'Époque Sengoku',
    `description` TEXT NOT NULL,
    `image_url` VARCHAR(255) NOT NULL DEFAULT '/public/assets/buildings/hq.jpg',
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `reward_final_gold` INT UNSIGNED NOT NULL DEFAULT 50,
    `reward_final_honor` INT UNSIGNED NOT NULL DEFAULT 100,
    `reward_final_seal_days` INT UNSIGNED NOT NULL DEFAULT 1,
    `reward_final_title` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_we_active` (`is_active`),
    INDEX `idx_we_dates` (`starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `weekly_event_tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT UNSIGNED NOT NULL,
    `step_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `task_type` VARCHAR(50) NOT NULL, -- stock_resource, upgrade_rural, upgrade_building, train_troops, craft_refine, hero_stat
    `target_key` VARCHAR(50) NOT NULL, -- clay, tea, soybean, metal, crystal, deuterium, foret, riziere, hq, grain_mill, sawmill, yari_ashigaru, sake, etc.
    `target_amount` BIGINT NOT NULL DEFAULT 1,
    `reward_metal` INT UNSIGNED NOT NULL DEFAULT 0,
    `reward_crystal` INT UNSIGNED NOT NULL DEFAULT 0,
    `reward_deuterium` INT UNSIGNED NOT NULL DEFAULT 0,
    `reward_gold` INT UNSIGNED NOT NULL DEFAULT 0,
    `reward_honor` INT UNSIGNED NOT NULL DEFAULT 0,
    `reward_item` VARCHAR(60) DEFAULT NULL, -- ex: 'rice_flour:100', 'sake:50', 'wooden_beams:30'
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_wet_event` (`event_id`),
    INDEX `idx_wet_order` (`event_id`, `step_order`),
    CONSTRAINT `fk_wet_event` FOREIGN KEY (`event_id`) REFERENCES `weekly_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `player_weekly_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `final_claimed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` DATETIME DEFAULT NULL,
    `claimed_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_pwe_user_event` (`user_id`, `event_id`),
    INDEX `idx_pwe_event` (`event_id`),
    CONSTRAINT `fk_pwe_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pwe_event` FOREIGN KEY (`event_id`) REFERENCES `weekly_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `player_weekly_event_tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    `task_id` INT UNSIGNED NOT NULL,
    `current_progress` BIGINT NOT NULL DEFAULT 0,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `is_claimed` TINYINT(1) NOT NULL DEFAULT 0,
    `claimed_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_pwet_user_task` (`user_id`, `task_id`),
    INDEX `idx_pwet_user_event` (`user_id`, `event_id`),
    CONSTRAINT `fk_pwet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pwet_task` FOREIGN KEY (`task_id`) REFERENCES `weekly_event_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

