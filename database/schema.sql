-- Schema OpenGalaxy
-- Moteur de jeu de stratégie galactique par navigateur

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `combat_reports`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `fleet_missions`;
DROP TABLE IF EXISTS `research_queue`;
DROP TABLE IF EXISTS `user_researches`;
DROP TABLE IF EXISTS `researches`;
DROP TABLE IF EXISTS `shipyard_queue`;
DROP TABLE IF EXISTS `planet_ships`;
DROP TABLE IF EXISTS `ships`;
DROP TABLE IF EXISTS `construction_queue`;
DROP TABLE IF EXISTS `planet_buildings`;
DROP TABLE IF EXISTS `planet_fields`;
DROP TABLE IF EXISTS `planets`;
DROP TABLE IF EXISTS `alliances`;
DROP TABLE IF EXISTS `users`;

-- Utilisateurs
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `faction` ENUM('terran', 'vorash', 'aethelis') NOT NULL DEFAULT 'terran',
  `alliance_id` INT UNSIGNED NULL DEFAULT NULL,
  `points` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alliances
CREATE TABLE `alliances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `tag` VARCHAR(8) NOT NULL UNIQUE,
  `leader_id` INT UNSIGNED NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_alliance_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planètes
CREATE TABLE `planets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `name` VARCHAR(60) NOT NULL,
  `coord_x` INT NOT NULL,
  `coord_y` INT NOT NULL,
  `planet_type` ENUM('terrestrial', 'oceanic', 'desert', 'volcanic', 'arctic', 'gas') NOT NULL DEFAULT 'terrestrial',
  `metal` DOUBLE NOT NULL DEFAULT 1000,
  `crystal` DOUBLE NOT NULL DEFAULT 800,
  `deuterium` DOUBLE NOT NULL DEFAULT 400,
  `energy_used` INT NOT NULL DEFAULT 0,
  `energy_max` INT NOT NULL DEFAULT 100,
  `metal_max` INT UNSIGNED NOT NULL DEFAULT 15000,
  `crystal_max` INT UNSIGNED NOT NULL DEFAULT 15000,
  `deuterium_max` INT UNSIGNED NOT NULL DEFAULT 15000,
  `last_resource_update` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_capital` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_coordinates` (`coord_x`, `coord_y`),
  KEY `idx_user_planet` (`user_id`),
  CONSTRAINT `fk_planets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parcelles de ressources de la planète (18 parcelles comme Travian)
-- 5 Metal, 5 Crystal, 4 Deuterium, 4 Solar
CREATE TABLE `planet_fields` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `planet_id` INT UNSIGNED NOT NULL,
  `field_slot` TINYINT UNSIGNED NOT NULL,
  `type` ENUM('metal_mine', 'crystal_mine', 'deuterium_synth', 'solar_plant') NOT NULL,
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_planet_slot` (`planet_id`, `field_slot`),
  CONSTRAINT `fk_fields_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bâtiments intérieurs de la colonie
CREATE TABLE `planet_buildings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `planet_id` INT UNSIGNED NOT NULL,
  `slot` TINYINT UNSIGNED NULL,
  `building_type` VARCHAR(40) NOT NULL,
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_planet_building` (`planet_id`, `building_type`),
  UNIQUE KEY `uniq_planet_slot` (`planet_id`, `slot`),
  CONSTRAINT `fk_buildings_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File de construction (Bâtiments et Parcelles)
CREATE TABLE `construction_queue` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `planet_id` INT UNSIGNED NOT NULL,
  `build_category` ENUM('field', 'building') NOT NULL,
  `target_id` VARCHAR(50) NOT NULL, -- field_slot (1-18) ou nom de bâtiment
  `target_level` TINYINT UNSIGNED NOT NULL,
  `started_at` INT UNSIGNED NOT NULL,
  `finishes_at` INT UNSIGNED NOT NULL,
  KEY `idx_planet_queue` (`planet_id`),
  CONSTRAINT `fk_queue_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Répertoire des Vaisseaux
CREATE TABLE `ships` (
  `code` VARCHAR(30) PRIMARY KEY,
  `name` VARCHAR(60) NOT NULL,
  `faction` ENUM('all', 'terran', 'vorash', 'aethelis') NOT NULL DEFAULT 'all',
  `metal_cost` INT UNSIGNED NOT NULL,
  `crystal_cost` INT UNSIGNED NOT NULL,
  `deuterium_cost` INT UNSIGNED NOT NULL,
  `attack` INT UNSIGNED NOT NULL,
  `defense` INT UNSIGNED NOT NULL,
  `shield` INT UNSIGNED NOT NULL,
  `speed` INT UNSIGNED NOT NULL,
  `cargo_capacity` INT UNSIGNED NOT NULL,
  `base_build_time` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vaisseaux stationnés sur une planète
CREATE TABLE `planet_ships` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `planet_id` INT UNSIGNED NOT NULL,
  `ship_code` VARCHAR(30) NOT NULL,
  `count` INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_planet_ship` (`planet_id`, `ship_code`),
  CONSTRAINT `fk_ships_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ships_code` FOREIGN KEY (`ship_code`) REFERENCES `ships` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File du chantier spatial
CREATE TABLE `shipyard_queue` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `planet_id` INT UNSIGNED NOT NULL,
  `ship_code` VARCHAR(30) NOT NULL,
  `count` INT UNSIGNED NOT NULL,
  `started_at` INT UNSIGNED NOT NULL,
  `finishes_at` INT UNSIGNED NOT NULL,
  `unit_build_time` INT UNSIGNED NOT NULL,
  KEY `idx_shipyard_planet` (`planet_id`),
  CONSTRAINT `fk_shipyard_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recherches technologiques
CREATE TABLE `researches` (
  `code` VARCHAR(30) PRIMARY KEY,
  `name` VARCHAR(60) NOT NULL,
  `metal_cost` INT UNSIGNED NOT NULL,
  `crystal_cost` INT UNSIGNED NOT NULL,
  `deuterium_cost` INT UNSIGNED NOT NULL,
  `base_time` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Niveau de recherches par joueur
CREATE TABLE `user_researches` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `research_code` VARCHAR(30) NOT NULL,
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY `uniq_user_research` (`user_id`, `research_code`),
  CONSTRAINT `fk_research_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_research_code` FOREIGN KEY (`research_code`) REFERENCES `researches` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File de recherche
CREATE TABLE `research_queue` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `planet_id` INT UNSIGNED NOT NULL,
  `research_code` VARCHAR(30) NOT NULL,
  `target_level` TINYINT UNSIGNED NOT NULL,
  `started_at` INT UNSIGNED NOT NULL,
  `finishes_at` INT UNSIGNED NOT NULL,
  KEY `idx_research_user` (`user_id`),
  CONSTRAINT `fk_rqueue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Missions spatiales (Mouvements de flottes)
CREATE TABLE `fleet_missions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `source_planet_id` INT UNSIGNED NOT NULL,
  `target_planet_id` INT UNSIGNED NOT NULL,
  `mission_type` ENUM('attack', 'raid', 'transport', 'spy', 'colonize') NOT NULL,
  `fleet_data` JSON NOT NULL, -- {"fighter": 10, "cruiser": 2}
  `cargo_data` JSON NOT NULL, -- {"metal": 100, "crystal": 50, "deuterium": 20}
  `departure_time` INT UNSIGNED NOT NULL,
  `arrival_time` INT UNSIGNED NOT NULL,
  `return_time` INT UNSIGNED NOT NULL,
  `status` ENUM('en_route', 'returning', 'completed', 'canceled') NOT NULL DEFAULT 'en_route',
  KEY `idx_fleet_arrival` (`arrival_time`, `status`),
  KEY `idx_fleet_return` (`return_time`, `status`),
  KEY `idx_fleet_user` (`user_id`),
  CONSTRAINT `fk_fleet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fleet_source` FOREIGN KEY (`source_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fleet_target` FOREIGN KEY (`target_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rapports de combat et d'espionnage
CREATE TABLE `combat_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `attacker_id` INT UNSIGNED NOT NULL,
  `defender_id` INT UNSIGNED NOT NULL,
  `attacker_planet_id` INT UNSIGNED NOT NULL,
  `defender_planet_id` INT UNSIGNED NOT NULL,
  `mission_type` ENUM('attack', 'raid', 'spy') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `report_data` JSON NOT NULL,
  `winner` ENUM('attacker', 'defender', 'draw') NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  `read_by_attacker` TINYINT(1) NOT NULL DEFAULT 0,
  `read_by_defender` TINYINT(1) NOT NULL DEFAULT 0,
  KEY `idx_cr_attacker` (`attacker_id`),
  KEY `idx_cr_defender` (`defender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messagerie interne
CREATE TABLE `messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT UNSIGNED NULL, -- NULL = Message système
  `receiver_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_by_receiver` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_by_sender` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` INT UNSIGNED NOT NULL,
  KEY `idx_msg_receiver` (`receiver_id`),
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

