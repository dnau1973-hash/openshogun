/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `alliances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `tag` varchar(8) NOT NULL,
  `leader_id` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `tag` (`tag`),
  KEY `fk_alliance_leader` (`leader_id`),
  CONSTRAINT `fk_alliance_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `alliance_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alliance_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alliance_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `type` enum('invitation','application') NOT NULL DEFAULT 'invitation',
  `message` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','canceled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inv_alliance` (`alliance_id`),
  KEY `idx_inv_user` (`user_id`),
  KEY `idx_inv_status` (`status`),
  CONSTRAINT `fk_inv_alliance` FOREIGN KEY (`alliance_id`) REFERENCES `alliances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authentic_castles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `authentic_castles` (
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_coords` (`coord_x`,`coord_y`),
  KEY `idx_spawned` (`is_spawned`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barracks_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `barracks_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  `unit_train_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_barracks_planet` (`planet_id`),
  CONSTRAINT `fk_barracks_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `combat_reports` (
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
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `construction_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `construction_queue` (
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
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fleet_missions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fleet_missions` (
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
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('int','float','string','boolean') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hero_adventures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hero_adventures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `coord_x` int(11) NOT NULL,
  `coord_y` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'easy',
  `status` enum('available','in_progress','completed','expired') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ha_user` (`user_id`),
  KEY `idx_ha_status` (`status`),
  CONSTRAINT `fk_ha_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hero_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hero_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_type` enum('weapon','helmet','armor','horse','talisman','consumable') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `bonus_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`bonus_data`)),
  `is_equipped` tinyint(1) NOT NULL DEFAULT 0,
  `acquired_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hi_user` (`user_id`),
  CONSTRAINT `fk_hi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `heroes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `heroes` (
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_heroes_planet` (`current_planet_id`),
  CONSTRAINT `fk_heroes_planet` FOREIGN KEY (`current_planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_heroes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oases` (
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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oasis_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oasis_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oasis_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `is_wild` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_oasis_unit` (`oasis_id`,`unit_code`),
  CONSTRAINT `fk_oasis_units_oasis` FOREIGN KEY (`oasis_id`) REFERENCES `oases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planet_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planet_buildings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `slot` tinyint(3) unsigned DEFAULT NULL,
  `building_type` varchar(40) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_building` (`planet_id`,`building_type`),
  UNIQUE KEY `uniq_planet_slot` (`planet_id`,`slot`),
  CONSTRAINT `fk_buildings_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planet_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planet_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `field_slot` tinyint(3) unsigned NOT NULL,
  `type` enum('metal_mine','crystal_mine','deuterium_synth','solar_plant') NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_slot` (`planet_id`,`field_slot`),
  CONSTRAINT `fk_fields_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=987 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planet_ships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planet_ships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `ship_code` varchar(30) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_ship` (`planet_id`,`ship_code`),
  KEY `fk_ships_code` (`ship_code`),
  CONSTRAINT `fk_ships_code` FOREIGN KEY (`ship_code`) REFERENCES `ships` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_ships_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planet_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planet_units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `unit_code` varchar(60) NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_planet_unit` (`planet_id`,`unit_code`),
  KEY `fk_units_code` (`unit_code`),
  CONSTRAINT `fk_units_code` FOREIGN KEY (`unit_code`) REFERENCES `units` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_units_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=659 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planets` (
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
  `founder_planet_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coordinates` (`coord_x`,`coord_y`),
  KEY `idx_user_planet` (`user_id`),
  KEY `idx_founder_planet` (`founder_planet_id`),
  CONSTRAINT `fk_planets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `research_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `research_queue` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `researches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `researches` (
  `code` varchar(30) NOT NULL,
  `name` varchar(60) NOT NULL,
  `metal_cost` int(10) unsigned NOT NULL,
  `crystal_cost` int(10) unsigned NOT NULL,
  `deuterium_cost` int(10) unsigned NOT NULL,
  `base_time` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ships` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipyard_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipyard_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `planet_id` int(10) unsigned NOT NULL,
  `ship_code` varchar(30) NOT NULL,
  `count` int(10) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `finishes_at` int(10) unsigned NOT NULL,
  `unit_build_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipyard_planet` (`planet_id`),
  CONSTRAINT `fk_shipyard_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_announcement_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_announcement_reads` (
  `user_id` int(10) unsigned NOT NULL,
  `announcement_id` varchar(64) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`announcement_id`),
  KEY `idx_announcement_id` (`announcement_id`),
  CONSTRAINT `fk_announcement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_medals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_medals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `category` enum('progression','attack','defense','raid') NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  `week_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `awarded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_um_user` (`user_id`),
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_quests` (
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
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_researches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_researches` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_weekly_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_weekly_stats` (
  `user_id` int(10) unsigned NOT NULL,
  `attack_points` int(10) unsigned NOT NULL DEFAULT 0,
  `defense_points` int(10) unsigned NOT NULL DEFAULT 0,
  `raid_resources` bigint(20) unsigned NOT NULL DEFAULT 0,
  `start_week_points` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_uws_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `faction` enum('terran','vorash','aethelis') NOT NULL DEFAULT 'terran',
  `alliance_id` int(10) unsigned DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_moderator` tinyint(1) NOT NULL DEFAULT 0,
  `is_bot` tinyint(1) NOT NULL DEFAULT 0,
  `points` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_active` datetime NOT NULL DEFAULT current_timestamp(),
  `protection_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_protection` (`protection_until`),
  KEY `idx_users_moderator` (`is_moderator`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon` varchar(20) NOT NULL DEFAULT '💬',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_topics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_post_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_topic_cat` (`category_id`,`is_pinned`,`last_post_at`),
  KEY `idx_topic_user` (`user_id`),
  CONSTRAINT `fk_topic_category` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topic_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `forum_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `forum_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `is_first_post` tinyint(1) NOT NULL DEFAULT 0,
  `edited_at` datetime DEFAULT NULL,
  `edited_by_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_topic` (`topic_id`,`created_at`),
  KEY `idx_post_user` (`user_id`),
  CONSTRAINT `fk_post_topic` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_type` enum('global','alliance','whisper') NOT NULL DEFAULT 'global',
  `channel_target_id` int(10) unsigned DEFAULT NULL,
  `sender_id` int(10) unsigned NOT NULL,
  `recipient_id` int(10) unsigned DEFAULT NULL,
  `message` varchar(1000) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_global` (`channel_type`,`id`),
  KEY `idx_chat_alliance` (`channel_type`,`channel_target_id`,`id`),
  KEY `idx_chat_whisper` (`sender_id`,`recipient_id`,`id`),
  KEY `idx_chat_recipient` (`recipient_id`,`id`),
  CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `planet_feasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `planet_feasts` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `craft_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_queue` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

