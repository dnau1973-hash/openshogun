-- Migration : Table planet_rural_plots pour le domaine rural à 9 parcelles uniques
-- Support de la progression verticale profonde (Niveaux 20 à 100) et coordonnées procédurales

CREATE TABLE IF NOT EXISTS `planet_rural_plots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `planet_id` INT UNSIGNED NOT NULL,
    `slot_id` TINYINT UNSIGNED NOT NULL, -- 1 à 9
    `structure_type` VARCHAR(32) NOT NULL, -- tenshu, foret, carriere, fosse_argile, riziere, champ_soja, culture_the, sanctuaire_shinto, village
    `level` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_level` INT UNSIGNED NOT NULL DEFAULT 30, -- Potentiel tiré entre 20 et 100
    `pos_x` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    `pos_y` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
    `prod_hourly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_planet_slot` (`planet_id`, `slot_id`),
    UNIQUE KEY `uniq_planet_structure` (`planet_id`, `structure_type`),
    KEY `idx_planet_id` (`planet_id`),
    CONSTRAINT `fk_rural_plots_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

