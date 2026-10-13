-- Migration 006 : Réserves Halieutiques des Lacs (Stocks Aléatoires 10 000 - 25 000 & Épuisement)
-- OpenShogun

CREATE TABLE IF NOT EXISTS `lake_fish_stocks` (
    `coord_x` INT NOT NULL,
    `coord_y` INT NOT NULL,
    `fish_stock` INT UNSIGNED NOT NULL DEFAULT 0,
    `fish_initial` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`coord_x`, `coord_y`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
