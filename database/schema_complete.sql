-- ============================================================================
-- OpenShogun — Migration 001 : Schéma Baseline Consolidé Unifié
-- Version : 1.0.0 (Consolidation des 42 tables du Japon Féodal)
-- ============================================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- Table : alliances
CREATE TABLE IF NOT EXISTS `alliances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `tag` varchar(8) NOT NULL,
  `leader_id` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `tag` (`tag`),
  KEY `idx_alliance_leader` (`leader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : alliance_invitations
CREATE TABLE IF NOT EXISTS `alliance_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `type` enum('invitation','application') NOT NULL DEFAULT 'invitation',
  `message` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','canceled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_alliance` (`alliance_id`),
  KEY `idx_inv_user` (`user_id`),
  KEY `idx_inv_status` (`status`),
  CONSTRAINT `fk_inv_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : authentic_castles
CREATE TABLE IF NOT EXISTS `authentic_castles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `japanese_name` varchar(100) NOT NULL,
  `kanji` varchar(50) NOT NULL,
  `province` varchar(100) NOT NULL,
  `historical_builder` varchar(150) NOT NULL,
  `construction_year` varchar(50) NOT NULL,
  `classification` varchar(100) NOT NULL DEFAULT 'Trésor National du Japon',
  `icon` varchar(10) NOT NULL DEFAULT '?',
  `image` varchar(255) DEFAULT NULL,
  `short_desc` text NOT NULL,
  `full_description` longtext NOT NULL,
  `architectural_features` text NOT NULL,
  `final_battle_lore` longtext NOT NULL,
  `relic_bonus` text NOT NULL,
  `default_x` int(11) NOT NULL,
  `default_y` int(11) NOT NULL,
  `coord_x` int(11) DEFAULT NULL,
  `coord_y` int(11) DEFAULT NULL,
  `is_spawned` tinyint(1) NOT NULL DEFAULT 0,
  `planet_id` int(11) DEFAULT NULL,
  `defense_power` int(11) NOT NULL DEFAULT 25000,
  `controlling_user_id` int(11) DEFAULT NULL,
  `controlling_faction` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_coords` (`coord_x`,`coord_y`),
  KEY `idx_spawned` (`is_spawned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : barracks_queue
CREATE TABLE IF NOT EXISTS `barracks_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  `unit_train_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_barracks_planet` (`planet_id`),
  CONSTRAINT `fk_barracks_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : chat_messages
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_type` enum('global','alliance','whisper') NOT NULL DEFAULT 'global',
  `channel_target_id` int(10) unsigned DEFAULT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `recipient_id` int(10) unsigned DEFAULT NULL,
  `message` varchar(1000) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chat_global` (`channel_type`,`id`),
  KEY `idx_chat_alliance` (`channel_type`,`channel_target_id`,`id`),
  KEY `idx_chat_whisper` (`sender_id`,`recipient_id`,`id`),
  KEY `idx_chat_recipient` (`recipient_id`,`id`),
  CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : combat_reports
CREATE TABLE IF NOT EXISTS `combat_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attacker_id` int(10) unsigned NOT NULL,
  `defender_id` int(10) unsigned NOT NULL,
  `attacker_planet_id` int(10) unsigned NOT NULL,
  `defender_planet_id` int(10) unsigned NOT NULL,
  `mission_type` enum('attack','raid','spy','occupy') NOT NULL,
  `title` varchar(150) NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`report_data`)),
  `winner` enum('attacker','defender','draw') NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  `read_by_attacker` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_defender` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cr_attacker` (`attacker_id`),
  KEY `idx_cr_defender` (`defender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : construction_queue
CREATE TABLE IF NOT EXISTS `construction_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `build_category` enum('field','building') NOT NULL,
  `target_id` varchar(50) NOT NULL,
  `target_level` tinyint(3) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_planet_queue` (`planet_id`),
  CONSTRAINT `fk_queue_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : craft_queue
CREATE TABLE IF NOT EXISTS `craft_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `product` varchar(30) NOT NULL,
  `rice_amount` double NOT NULL,
  `produced_amount` int(10) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cq_planet` (`planet_id`),
  KEY `idx_cq_finishes` (`finishes_at`),
  CONSTRAINT `fk_cq_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : farm_list_entries
CREATE TABLE IF NOT EXISTS `farm_list_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `farm_list_id` int(10) unsigned NOT NULL,
  `target_type` enum('planet','oasis') NOT NULL DEFAULT 'planet',
  `target_id` int(10) unsigned NOT NULL,
  `target_name` varchar(100) NOT NULL,
  `coord_x` int(11) NOT NULL,
  `coord_y` int(11) NOT NULL,
  `fleet_data` text NOT NULL,
  `last_raid_at` datetime DEFAULT NULL,
  `last_loot` text DEFAULT NULL,
  `last_status` varchar(50) DEFAULT NULL,
  `distance` double DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_fle_list` (`farm_list_id`),
  CONSTRAINT `fk_fle_list` FOREIGN KEY (`farm_list_id`) REFERENCES `farm_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table : farm_lists
CREATE TABLE IF NOT EXISTS `farm_lists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `source_planet_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fl_user` (`user_id`),
  KEY `idx_fl_source` (`source_planet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table : fleet_missions
CREATE TABLE IF NOT EXISTS `fleet_missions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `source_planet_id` int(10) unsigned NOT NULL,
  `target_planet_id` int(10) unsigned DEFAULT NULL,
  `target_oasis_id` int(10) unsigned DEFAULT NULL,
  `adventure_id` int(10) unsigned DEFAULT NULL,
  `mission_type` enum('attack','raid','transport','spy','colonize','occupy','adventure') NOT NULL,
  `fleet_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`fleet_data`)),
  `cargo_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cargo_data`)),
  `has_hero` tinyint(1) NOT NULL DEFAULT 0,
  `departure_time` int(10) unsigned NOT NULL,
  `arrival_time` int(10) unsigned NOT NULL,
  `return_time` int(10) unsigned NOT NULL,
  `status` enum('en_route','returning','completed','canceled') NOT NULL DEFAULT 'en_route',
  PRIMARY KEY (`id`),
  KEY `idx_fleet_arrival` (`arrival_time`,`status`),
  KEY `idx_fleet_return` (`return_time`,`status`),
  KEY `idx_fleet_user` (`user_id`),
  KEY `fk_fleet_source` (`source_planet_id`),
  KEY `fk_fleet_target` (`target_planet_id`),
  KEY `fk_fleet_target_oasis` (`target_oasis_id`),
  KEY `fk_fleet_adventure` (`adventure_id`),
  CONSTRAINT `fk_fleet_adventure` FOREIGN KEY (`adventure_id`) REFERENCES `hero_adventures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fleet_source` FOREIGN KEY (`source_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fleet_target` FOREIGN KEY (`target_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fleet_target_oasis` FOREIGN KEY (`target_oasis_id`) REFERENCES `oases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fleet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : forum_categories
CREATE TABLE IF NOT EXISTS `forum_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(20) NOT NULL DEFAULT '?',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : forum_posts
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `is_first_post` tinyint(1) NOT NULL DEFAULT 0,
  `edited_at` datetime DEFAULT NULL,
  `edited_by_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_topic` (`topic_id`,`created_at`),
  KEY `idx_post_user` (`user_id`),
  CONSTRAINT `fk_post_topic` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : forum_topics
CREATE TABLE IF NOT EXISTS `forum_topics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_post_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_topic_cat` (`category_id`,`is_pinned`,`last_post_at`),
  KEY `idx_topic_user` (`user_id`),
  CONSTRAINT `fk_topic_category` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topic_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : game_settings
CREATE TABLE IF NOT EXISTS `game_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('int','float','string','boolean') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : hero_adventures
CREATE TABLE IF NOT EXISTS `hero_adventures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `coord_x` int(11) NOT NULL,
  `coord_y` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'easy',
  `status` enum('available','in_progress','completed','expired') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ha_user` (`user_id`),
  KEY `idx_ha_status` (`status`),
  CONSTRAINT `fk_ha_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : hero_inventory
CREATE TABLE IF NOT EXISTS `hero_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_type` enum('weapon','helmet','armor','horse','talisman','consumable') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `bonus_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`bonus_data`)),
  `is_equipped` tinyint(1) NOT NULL DEFAULT 0,
  `acquired_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hi_user` (`user_id`),
  CONSTRAINT `fk_hi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : heroes
CREATE TABLE IF NOT EXISTS `heroes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `current_planet_id` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL DEFAULT 'Samouraï Champion',
  `level` int(10) unsigned NOT NULL DEFAULT 0,
  `experience` int(10) unsigned NOT NULL DEFAULT 0,
  `health` float NOT NULL DEFAULT 100,
  `status` enum('home','mission','adventure','dead','reviving') NOT NULL DEFAULT 'home',
  `revive_finish_time` int(10) unsigned DEFAULT NULL,
  `last_health_update` int(10) unsigned NOT NULL DEFAULT 0,
  `unassigned_points` int(10) unsigned NOT NULL DEFAULT 4,
  `stat_strength` int(10) unsigned NOT NULL DEFAULT 0,
  `stat_offense_bonus` int(10) unsigned NOT NULL DEFAULT 0,
  `stat_defense_bonus` int(10) unsigned NOT NULL DEFAULT 0,
  `stat_production` int(10) unsigned NOT NULL DEFAULT 0,
  `production_type` enum('balanced','metal','crystal','deuterium') NOT NULL DEFAULT 'balanced',
  `equipped_weapon` varchar(50) DEFAULT NULL,
  `equipped_helmet` varchar(50) DEFAULT NULL,
  `equipped_armor` varchar(50) DEFAULT NULL,
  `equipped_horse` varchar(50) DEFAULT NULL,
  `equipped_talisman` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_heroes_planet` (`current_planet_id`),
  CONSTRAINT `fk_heroes_planet` FOREIGN KEY (`current_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_heroes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : mailing_campaigns
CREATE TABLE IF NOT EXISTS `mailing_campaigns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `target_group` varchar(50) NOT NULL,
  `recipient_count` int(10) unsigned NOT NULL DEFAULT 0,
  `body_html` mediumtext NOT NULL,
  `status` enum('draft','sent','failed') NOT NULL DEFAULT 'sent',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mc_sender` (`sender_id`),
  KEY `idx_mc_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned DEFAULT NULL,
  `receiver_id` int(10) unsigned NOT NULL,
  `subject` varchar(100) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_receiver` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_sender` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : oases
CREATE TABLE IF NOT EXISTS `oases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `coord_x` int(11) NOT NULL,
  `coord_y` int(11) NOT NULL,
  `oasis_type` varchar(40) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bonus_wood` int(11) NOT NULL DEFAULT 0,
  `bonus_stone` int(11) NOT NULL DEFAULT 0,
  `bonus_rice` int(11) NOT NULL DEFAULT 0,
  `res_wood` int(11) NOT NULL DEFAULT 1200,
  `res_stone` int(11) NOT NULL DEFAULT 1200,
  `res_rice` int(11) NOT NULL DEFAULT 1200,
  `res_max` int(11) NOT NULL DEFAULT 5000,
  `last_loot_time` int(10) unsigned NOT NULL DEFAULT 0,
  `owner_planet_id` int(10) unsigned DEFAULT NULL,
  `annexed_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_oasis_coords` (`coord_x`,`coord_y`),
  KEY `idx_oasis_owner` (`owner_planet_id`),
  CONSTRAINT `fk_oasis_owner_planet` FOREIGN KEY (`owner_planet_id`) REFERENCES `planets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : oasis_units
CREATE TABLE IF NOT EXISTS `oasis_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oasis_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `is_wild` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_oasis_unit` (`oasis_id`,`unit_code`),
  CONSTRAINT `fk_oasis_units_oasis` FOREIGN KEY (`oasis_id`) REFERENCES `oases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planet_buildings
CREATE TABLE IF NOT EXISTS `planet_buildings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `slot` tinyint(3) unsigned DEFAULT NULL,
  `building_type` varchar(40) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_building` (`planet_id`,`building_type`),
  UNIQUE KEY `uniq_planet_slot` (`planet_id`,`slot`),
  CONSTRAINT `fk_buildings_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planet_feasts
CREATE TABLE IF NOT EXISTS `planet_feasts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `feast_type` varchar(40) NOT NULL,
  `tenshu_level` int(10) unsigned NOT NULL DEFAULT 1,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pf_planet` (`planet_id`),
  KEY `idx_pf_finishes` (`finishes_at`),
  CONSTRAINT `fk_pf_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planet_fields
CREATE TABLE IF NOT EXISTS `planet_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `field_slot` tinyint(3) unsigned NOT NULL,
  `type` enum('metal_mine','crystal_mine','deuterium_synth','solar_plant') NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_slot` (`planet_id`,`field_slot`),
  CONSTRAINT `fk_fields_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planet_ships
CREATE TABLE IF NOT EXISTS `planet_ships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `ship_code` varchar(30) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_ship` (`planet_id`,`ship_code`),
  KEY `fk_ships_code` (`ship_code`),
  CONSTRAINT `fk_ships_code` FOREIGN KEY (`ship_code`) REFERENCES `ships` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_ships_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planet_units
CREATE TABLE IF NOT EXISTS `planet_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_unit` (`planet_id`,`unit_code`),
  KEY `fk_units_code` (`unit_code`),
  CONSTRAINT `fk_units_code` FOREIGN KEY (`unit_code`) REFERENCES `units` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_units_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : planets
CREATE TABLE IF NOT EXISTS `planets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(60) NOT NULL,
  `coord_x` int(11) NOT NULL,
  `coord_y` int(11) NOT NULL,
  `planet_type` enum('terrestrial','oceanic','desert','volcanic','arctic','gas') NOT NULL DEFAULT 'terrestrial',
  `metal` double NOT NULL DEFAULT 1000,
  `crystal` double NOT NULL DEFAULT 800,
  `deuterium` double NOT NULL DEFAULT 400,
  `sake` double NOT NULL DEFAULT 0,
  `rice_flour` double NOT NULL DEFAULT 0,
  `energy_used` int(11) NOT NULL DEFAULT 0,
  `energy_max` int(11) NOT NULL DEFAULT 100,
  `metal_max` int(10) unsigned NOT NULL DEFAULT 15000,
  `crystal_max` int(10) unsigned NOT NULL DEFAULT 15000,
  `deuterium_max` int(10) unsigned NOT NULL DEFAULT 15000,
  `sake_max` int(10) unsigned NOT NULL DEFAULT 10000,
  `rice_flour_max` int(10) unsigned NOT NULL DEFAULT 10000,
  `population` int(10) unsigned NOT NULL DEFAULT 100,
  `famine_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_famine_losses` int(10) unsigned NOT NULL DEFAULT 0,
  `last_resource_update` int(10) unsigned NOT NULL DEFAULT 0,
  `is_capital` tinyint(1) NOT NULL DEFAULT 1,
  `tactical_evasion` tinyint(1) NOT NULL DEFAULT 0,
  `founder_planet_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `wooden_beams` double NOT NULL DEFAULT 0,
  `wooden_beams_max` int(10) unsigned NOT NULL DEFAULT 10000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coordinates` (`coord_x`,`coord_y`),
  KEY `idx_user_planet` (`user_id`),
  KEY `idx_founder_planet` (`founder_planet_id`),
  CONSTRAINT `fk_planets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : research_queue
CREATE TABLE IF NOT EXISTS `research_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `planet_id` int(10) unsigned NOT NULL,
  `research_code` varchar(30) NOT NULL,
  `target_level` tinyint(3) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_research_user` (`user_id`),
  CONSTRAINT `fk_rqueue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : researches
CREATE TABLE IF NOT EXISTS `researches` (
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `metal_cost` int(10) unsigned NOT NULL,
  `crystal_cost` int(10) unsigned NOT NULL,
  `deuterium_cost` int(10) unsigned NOT NULL,
  `base_time` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : ships
CREATE TABLE IF NOT EXISTS `ships` (
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `faction` enum('all','terran','vorash','aethelis') NOT NULL DEFAULT 'all',
  `image` varchar(100) DEFAULT NULL,
  `metal_cost` int(10) unsigned NOT NULL,
  `crystal_cost` int(10) unsigned NOT NULL,
  `deuterium_cost` int(10) unsigned NOT NULL,
  `attack` int(10) unsigned NOT NULL,
  `defense` int(10) unsigned NOT NULL,
  `shield` int(10) unsigned NOT NULL,
  `speed` int(10) unsigned NOT NULL,
  `cargo_capacity` int(10) unsigned NOT NULL,
  `base_build_time` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : shipyard_queue
CREATE TABLE IF NOT EXISTS `shipyard_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `ship_code` varchar(30) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  `unit_build_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipyard_planet` (`planet_id`),
  CONSTRAINT `fk_shipyard_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : support_tickets
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('bug','suggestion') NOT NULL DEFAULT 'bug',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `planet_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','in_progress','resolved','planned','closed') NOT NULL DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `responded_at` int(10) unsigned DEFAULT NULL,
  `created_at` int(10) unsigned NOT NULL,
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_support_user` (`user_id`),
  KEY `idx_support_status` (`status`),
  KEY `idx_support_type` (`type`),
  CONSTRAINT `fk_support_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : trade_routes
CREATE TABLE IF NOT EXISTS `trade_routes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `source_planet_id` int(10) unsigned NOT NULL,
  `target_planet_id` int(10) unsigned NOT NULL,
  `wood` int(10) unsigned NOT NULL DEFAULT 0,
  `stone` int(10) unsigned NOT NULL DEFAULT 0,
  `rice` int(10) unsigned NOT NULL DEFAULT 0,
  `interval_hours` int(10) unsigned NOT NULL DEFAULT 4,
  `transporter_pref` varchar(30) NOT NULL DEFAULT 'auto',
  `deliveries_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime NOT NULL,
  `last_status` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tr_user` (`user_id`),
  KEY `idx_tr_source` (`source_planet_id`),
  KEY `idx_tr_target` (`target_planet_id`),
  KEY `idx_tr_next` (`is_active`,`next_run_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table : units
CREATE TABLE IF NOT EXISTS `units` (
  `code` varchar(60) NOT NULL,
  `name` varchar(60) NOT NULL,
  `faction` enum('all','terran','vorash','aethelis') NOT NULL DEFAULT 'all',
  `tier` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `icon` varchar(20) NOT NULL DEFAULT '',
  `image` varchar(100) DEFAULT NULL,
  `metal_cost` int(10) unsigned NOT NULL,
  `crystal_cost` int(10) unsigned NOT NULL,
  `deuterium_cost` int(10) unsigned NOT NULL,
  `rice_flour_cost` int(10) unsigned NOT NULL DEFAULT 0,
  `attack` int(10) unsigned NOT NULL,
  `def_infantry` int(10) unsigned NOT NULL,
  `def_mech` int(10) unsigned NOT NULL,
  `speed` int(10) unsigned NOT NULL,
  `cargo_capacity` int(10) unsigned NOT NULL,
  `base_train_time` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : user_announcement_reads
CREATE TABLE IF NOT EXISTS `user_announcement_reads` (
  `user_id` int(10) unsigned NOT NULL,
  `announcement_id` varchar(64) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`announcement_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  CONSTRAINT `fk_announcement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : user_medals
CREATE TABLE IF NOT EXISTS `user_medals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category` enum('progression','attack','defense','raid') NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  `week_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_um_user` (`user_id`),
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : user_quests
CREATE TABLE IF NOT EXISTS `user_quests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `quest_key` varchar(50) NOT NULL,
  `status` enum('in_progress','completed','claimed') NOT NULL DEFAULT 'in_progress',
  `progress` int(10) unsigned NOT NULL DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_quest` (`user_id`,`quest_key`),
  KEY `idx_uq_user` (`user_id`),
  KEY `idx_uq_status` (`status`),
  CONSTRAINT `fk_uq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : user_researches
CREATE TABLE IF NOT EXISTS `user_researches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `research_code` varchar(30) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_research` (`user_id`,`research_code`),
  KEY `fk_research_code` (`research_code`),
  CONSTRAINT `fk_research_code` FOREIGN KEY (`research_code`) REFERENCES `researches` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_research_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : user_weekly_stats
CREATE TABLE IF NOT EXISTS `user_weekly_stats` (
  `user_id` int(10) unsigned NOT NULL,
  `attack_points` int(10) unsigned NOT NULL DEFAULT 0,
  `defense_points` int(10) unsigned NOT NULL DEFAULT 0,
  `raid_resources` bigint(20) unsigned NOT NULL DEFAULT 0,
  `start_week_points` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_uws_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gold_coins` int(10) unsigned NOT NULL DEFAULT 100,
  `imperial_seal_until` datetime DEFAULT NULL,
  `last_daily_gold` date DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `faction` enum('terran','vorash','aethelis') NOT NULL DEFAULT 'terran',
  `alliance_id` int(10) unsigned DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_moderator` tinyint(1) NOT NULL DEFAULT 0,
  `is_bot` tinyint(1) NOT NULL DEFAULT 0,
  `points` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `protection_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_protection` (`protection_until`),
  KEY `idx_users_moderator` (`is_moderator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table : activity_logs (Télémétrie et monitoring de navigation)
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

-- Table : planet_terroir_slots (5 emplacements de terroir par ressource)
CREATE TABLE IF NOT EXISTS `planet_terroir_slots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `planet_id` INT UNSIGNED NOT NULL,
    `resource_type` VARCHAR(32) NOT NULL,
    `slot_index` TINYINT UNSIGNED NOT NULL,
    `level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_planet_res_slot` (`planet_id`, `resource_type`, `slot_index`),
    KEY `idx_planet_res` (`planet_id`, `resource_type`),
    CONSTRAINT `fk_terroir_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
