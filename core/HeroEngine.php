<?php
/**
 * Moteur du Samouraï Héros Champion (OpenShogun)
 * Système complet inspiré des mécanismes emblématiques de Travian :
 * Progression, points d'attributs, aventures féodales, vitalité, inventaire et combats.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class HeroEngine {
    public const MAX_DAILY_ADVENTURES = 3;

    private PDO $db;
    private static bool $schemaChecked = false;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureSchema();
    }

    /**
     * Garantit automatiquement la présence et la conformité des tables du système de Héros
     */
    public function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            // 1. Table `heroes`
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `heroes` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL UNIQUE,
                    `current_planet_id` INT UNSIGNED NOT NULL,
                    `name` VARCHAR(60) NOT NULL DEFAULT 'Samouraï Champion',
                    `level` INT UNSIGNED NOT NULL DEFAULT 1,
                    `experience` INT UNSIGNED NOT NULL DEFAULT 0,
                    `health` FLOAT NOT NULL DEFAULT 100.0,
                    `status` ENUM('home', 'mission', 'adventure', 'dead', 'reviving') NOT NULL DEFAULT 'home',
                    `revive_finish_time` INT UNSIGNED NULL DEFAULT NULL,
                    `last_health_update` INT UNSIGNED NOT NULL DEFAULT 0,
                    `unassigned_points` INT UNSIGNED NOT NULL DEFAULT 4,
                    `stat_strength` INT UNSIGNED NOT NULL DEFAULT 0,
                    `stat_offense_bonus` INT UNSIGNED NOT NULL DEFAULT 0,
                    `stat_defense_bonus` INT UNSIGNED NOT NULL DEFAULT 0,
                    `stat_production` INT UNSIGNED NOT NULL DEFAULT 0,
                    `production_type` ENUM('balanced', 'metal', 'crystal', 'deuterium') NOT NULL DEFAULT 'balanced',
                    `equipped_weapon` VARCHAR(50) NULL DEFAULT NULL,
                    `equipped_helmet` VARCHAR(50) NULL DEFAULT NULL,
                    `equipped_armor` VARCHAR(50) NULL DEFAULT NULL,
                    `equipped_horse` VARCHAR(50) NULL DEFAULT NULL,
                    `equipped_talisman` VARCHAR(50) NULL DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    KEY `idx_heroes_user` (`user_id`),
                    KEY `idx_heroes_planet` (`current_planet_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Table `hero_adventures`
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `hero_adventures` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `coord_x` INT NOT NULL,
                    `coord_y` INT NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
                    `status` ENUM('available', 'in_progress', 'completed', 'expired') NOT NULL DEFAULT 'available',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `expires_at` INT UNSIGNED NULL DEFAULT NULL,
                    KEY `idx_ha_user` (`user_id`),
                    KEY `idx_ha_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Table `hero_inventory`
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `hero_inventory` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `item_code` VARCHAR(50) NOT NULL,
                    `item_type` ENUM('weapon', 'helmet', 'armor', 'horse', 'talisman', 'consumable') NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `description` VARCHAR(255) NOT NULL,
                    `bonus_data` JSON NOT NULL,
                    `is_equipped` TINYINT(1) NOT NULL DEFAULT 0,
                    `acquired_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_hi_user` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. Colonnes fleet_missions
            try {
                $cols = $this->db->query("SHOW COLUMNS FROM `fleet_missions` LIKE 'has_hero'")->fetchAll();
                if (empty($cols)) {
                    $this->db->exec("ALTER TABLE `fleet_missions` ADD COLUMN `has_hero` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cargo_data`");
                }
            } catch (Exception $e) {}

            try {
                $colsAdv = $this->db->query("SHOW COLUMNS FROM `fleet_missions` LIKE 'adventure_id'")->fetchAll();
                if (empty($colsAdv)) {
                    $this->db->exec("ALTER TABLE `fleet_missions` ADD COLUMN `adventure_id` INT UNSIGNED NULL DEFAULT NULL AFTER `target_oasis_id`");
                }
            } catch (Exception $e) {}

            // 5. Colonnes d'équipements dans heroes
            $eqCols = ['equipped_weapon', 'equipped_helmet', 'equipped_armor', 'equipped_horse', 'equipped_talisman'];
            foreach ($eqCols as $ec) {
                try {
                    $ch = $this->db->query("SHOW COLUMNS FROM `heroes` LIKE '{$ec}'")->fetchAll();
                    if (empty($ch)) {
                        $this->db->exec("ALTER TABLE `heroes` ADD COLUMN `{$ec}` VARCHAR(50) NULL DEFAULT NULL");
                    }
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {
            // Ignorer silencieusement si déjà conforme
        }
    }

    /**
     * Crée et initialise un Samouraï Héros pour un joueur ainsi que ses 3 premières aventures
     */
    public function createHeroForUser(int $userId, string $name, int $planetId, ?int $coordX = null, ?int $coordY = null): array {
        $stmt = $this->db->prepare("
            INSERT INTO heroes 
            (user_id, current_planet_id, name, level, experience, health, status, last_health_update, unassigned_points) 
            VALUES (?, ?, ?, 1, 0, 100.0, 'home', UNIX_TIMESTAMP(), 4)
            ON DUPLICATE KEY UPDATE current_planet_id = VALUES(current_planet_id)
        ");
        $stmt->execute([$userId, $planetId, $name]);

        if ($coordX === null || $coordY === null) {
            $stmtCoords = $this->db->prepare("SELECT coord_x, coord_y FROM planets WHERE id = ?");
            $stmtCoords->execute([$planetId]);
            $p = $stmtCoords->fetch();
            $coordX = (int)($p['coord_x'] ?? 500);
            $coordY = (int)($p['coord_y'] ?? 500);
        }

        $stmtAdv = $this->db->prepare("
            INSERT INTO hero_adventures (user_id, coord_x, coord_y, name, difficulty, status) 
            VALUES (?, ?, ?, ?, ?, 'available')
        ");
        $advTemplates = [
            ['name' => 'Sanctuaire Shintō Abandonné dans la Forêt', 'diff' => 'easy', 'dx' => 2, 'dy' => 3],
            ['name' => 'Ruines d\'un Vieux Donjon Fief Noir', 'diff' => 'easy', 'dx' => -3, 'dy' => 2],
            ['name' => 'Gorge Brumeuse et Repaire de Ronins', 'diff' => 'medium', 'dx' => 4, 'dy' => -3]
        ];
        foreach ($advTemplates as $t) {
            $stmtAdv->execute([$userId, $coordX + $t['dx'], $coordY + $t['dy'], $t['name'], $t['diff']]);
        }

        return $this->getHeroByUserId($userId);
    }

    /**
     * Récupère les données complètes du héros pour un utilisateur
     * Gère la régénération passive des PV et la fin des rituels de résurrection
     */
    public function getHeroByUserId(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT h.*, 
                   COALESCE(p.name, 'Fief Principal') as planet_name, 
                   COALESCE(p.coord_x, 1) as coord_x, 
                   COALESCE(p.coord_y, 1) as coord_y, 
                   COALESCE(u.faction, 'terran') as faction, 
                   COALESCE(u.username, 'Daimyo') as username
            FROM heroes h 
            LEFT JOIN planets p ON p.id = h.current_planet_id 
            LEFT JOIN users u ON u.id = h.user_id 
            WHERE h.user_id = ?
        ");
        $stmt->execute([$userId]);
        $hero = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hero) {
            return null;
        }

        // Si la planète liée n'existe pas ou est invalide, relier au premier fief du joueur
        if (empty($hero['current_planet_id']) || $hero['planet_name'] === 'Fief Principal') {
            $stmtP = $this->db->prepare("SELECT id, name, coord_x, coord_y FROM planets WHERE user_id = ? ORDER BY id ASC LIMIT 1");
            $stmtP->execute([$userId]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);
            if ($p) {
                $this->db->prepare("UPDATE heroes SET current_planet_id = ? WHERE id = ?")->execute([$p['id'], $hero['id']]);
                $hero['current_planet_id'] = (int)$p['id'];
                $hero['planet_name'] = $p['name'];
                $hero['coord_x'] = (int)$p['coord_x'];
                $hero['coord_y'] = (int)$p['coord_y'];
            }
        }

        $now = time();

        // 1. Vérifier si un rituel de résurrection est terminé
        if ($hero['status'] === 'reviving' && $hero['revive_finish_time'] && $hero['revive_finish_time'] <= $now) {
            $this->db->prepare("
                UPDATE heroes 
                SET status = 'home', health = 100.0, revive_finish_time = NULL, last_health_update = ? 
                WHERE id = ?
            ")->execute([$now, $hero['id']]);

            $hero['status'] = 'home';
            $hero['health'] = 100.0;
            $hero['revive_finish_time'] = null;
        }

        // 2. Régénération passive de santé si en vie et blessé (et à la maison)
        if ($hero['status'] === 'home' && $hero['health'] < 100.0) {
            $lastUp = (int)$hero['last_health_update'] ?: $now;
            $elapsedSec = max(0, $now - $lastUp);

            if ($elapsedSec > 60) {
                $gameSpeed = max(1, (float)GameConfig::get('game_speed', 5));
                // Base : +15% de vie par 24h (86400s), accéléré par la vitesse du jeu
                $hpGainPerSec = (15.0 / 86400.0) * $gameSpeed;
                $gainedHp = $elapsedSec * $hpGainPerSec;

                $newHealth = min(100.0, (float)$hero['health'] + $gainedHp);

                $this->db->prepare("
                    UPDATE heroes 
                    SET health = ?, last_health_update = ? 
                    WHERE id = ?
                ")->execute([$newHealth, $now, $hero['id']]);

                $hero['health'] = $newHealth;
                $hero['last_health_update'] = $now;
            }
        }

        // 3. Calculer les statistiques effectives avec équipements
        $stats = $this->calculateEffectiveStats($hero);
        $hero['effective'] = $stats;
        $hero['xp_next_level'] = self::getXpForNextLevel((int)$hero['level']);
        $hero['xp_current_level_base'] = self::getXpForLevel((int)$hero['level']);
        $hero['xp_progress_percent'] = self::calculateXpPercent((int)$hero['level'], (int)$hero['experience']);
        $hero['daily_adventures'] = $this->getDailyAdventureQuota($userId);

        return $hero;
    }

    /**
     * Calcul de l'XP requise pour atteindre un niveau donné
     */
    public static function getXpForNextLevel(int $currentLevel): int {
        return ($currentLevel + 1) * 150;
    }

    public static function getXpForLevel(int $level): int {
        if ($level <= 0) return 0;
        return $level * 150;
    }

    public static function calculateXpPercent(int $level, int $totalXp): int {
        $base = self::getXpForLevel($level);
        $target = self::getXpForNextLevel($level);
        $needed = max(1, $target - $base);
        $currentInLevel = max(0, $totalXp - $base);
        return min(100, (int)round(($currentInLevel / $needed) * 100));
    }

    /**
     * Calcul des statistiques effectives
     */
    public function calculateEffectiveStats(array $hero): array {
        // Base stats
        $strengthPoints = (int)$hero['stat_strength'];
        $offensePoints = (int)$hero['stat_offense_bonus'];
        $defensePoints = (int)$hero['stat_defense_bonus'];
        $prodPoints = (int)$hero['stat_production'];

        // Force de combat : 150 de base + 80 par point
        $combatStrength = 150 + ($strengthPoints * 80);

        // Bonus armée : +0.2% par point (max 20%)
        $offenseBonusPct = min(20.0, $offensePoints * 0.2);
        $defenseBonusPct = min(20.0, $defensePoints * 0.2);

        // Production horaire du héros (multiplié par la vitesse de ressources)
        $resSpeed = max(1, (float)GameConfig::get('resource_speed', 5));
        $prodType = $hero['production_type'];

        $hourlyProd = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        if ($hero['status'] === 'home') {
            if ($prodType === 'balanced') {
                $val = (int)round($prodPoints * 6 * $resSpeed);
                $hourlyProd = ['metal' => $val, 'crystal' => $val, 'deuterium' => $val];
            } elseif ($prodType === 'metal') {
                $hourlyProd['metal'] = (int)round($prodPoints * 20 * $resSpeed);
            } elseif ($prodType === 'crystal') {
                $hourlyProd['crystal'] = (int)round($prodPoints * 20 * $resSpeed);
            } elseif ($prodType === 'deuterium') {
                $hourlyProd['deuterium'] = (int)round($prodPoints * 20 * $resSpeed);
            }
        }

        // Appliquer les bonus d'équipements actifs
        $stmtEq = $this->db->prepare("SELECT * FROM hero_inventory WHERE user_id = ? AND is_equipped = 1");
        $stmtEq->execute([$hero['user_id']]);
        $equippedItems = $stmtEq->fetchAll();

        $equipmentStrength = 0;
        $equipmentSpeedBonus = 0;
        $equipmentExpBonus = 0;

        foreach ($equippedItems as $it) {
            $bData = json_decode($it['bonus_data'], true) ?: [];
            if (!empty($bData['strength'])) {
                $combatStrength += (int)$bData['strength'];
                $equipmentStrength += (int)$bData['strength'];
            }
            if (!empty($bData['offense_bonus'])) {
                $offenseBonusPct = min(25.0, $offenseBonusPct + (float)$bData['offense_bonus']);
            }
            if (!empty($bData['defense_bonus'])) {
                $defenseBonusPct = min(25.0, $defenseBonusPct + (float)$bData['defense_bonus']);
            }
            if (!empty($bData['speed'])) {
                $equipmentSpeedBonus += (int)$bData['speed'];
            }
            if (!empty($bData['production_rice'])) {
                $hourlyProd['deuterium'] += (int)$bData['production_rice'];
            }
            if (!empty($bData['production_wood'])) {
                $hourlyProd['metal'] += (int)$bData['production_wood'];
            }
            if (!empty($bData['production_stone'])) {
                $hourlyProd['crystal'] += (int)$bData['production_stone'];
            }
            if (!empty($bData['exp_bonus'])) {
                $equipmentExpBonus += (int)$bData['exp_bonus'];
            }
        }

        return [
            'combat_strength' => $combatStrength,
            'base_strength' => 150 + ($strengthPoints * 80),
            'equipment_strength' => $equipmentStrength,
            'offense_bonus_pct' => round($offenseBonusPct, 1),
            'defense_bonus_pct' => round($defenseBonusPct, 1),
            'hourly_production' => $hourlyProd,
            'equipment_speed_bonus' => $equipmentSpeedBonus,
            'exp_bonus_pct' => $equipmentExpBonus
        ];
    }

    /**
     * Répartit les points d'attributs disponibles du Samouraï
     */
    public function allocatePoints(int $userId, int $addStrength, int $addOffense, int $addDefense, int $addProd): array {
        if ($addStrength < 0 || $addOffense < 0 || $addDefense < 0 || $addProd < 0) {
            return ['success' => false, 'error' => 'Valeurs de points invalides.'];
        }

        $totalSpent = $addStrength + $addOffense + $addDefense + $addProd;
        if ($totalSpent <= 0) {
            return ['success' => false, 'error' => 'Veuillez spécifier au moins un point à attribuer.'];
        }

        $hero = $this->getHeroByUserId($userId);
        if (!$hero) {
            return ['success' => false, 'error' => 'Héros introuvable.'];
        }

        if ((int)$hero['unassigned_points'] < $totalSpent) {
            return ['success' => false, 'error' => 'Points d\'attributs disponibles insuffisants.'];
        }

        // Limites max pour bonus % (max 100 points = 20%)
        if (((int)$hero['stat_offense_bonus'] + $addOffense) > 100) {
            return ['success' => false, 'error' => 'Le bonus offensif ne peut excéder 100 points (+20%).'];
        }
        if (((int)$hero['stat_defense_bonus'] + $addDefense) > 100) {
            return ['success' => false, 'error' => 'Le bonus défensif ne peut excéder 100 points (+20%).'];
        }

        $stmt = $this->db->prepare("
            UPDATE heroes 
            SET stat_strength = stat_strength + ?,
                stat_offense_bonus = stat_offense_bonus + ?,
                stat_defense_bonus = stat_defense_bonus + ?,
                stat_production = stat_production + ?,
                unassigned_points = unassigned_points - ?
            WHERE id = ?
        ");
        $stmt->execute([$addStrength, $addOffense, $addDefense, $addProd, $totalSpent, $hero['id']]);

        $updatedHero = $this->getHeroByUserId($userId);
        return [
            'success' => true,
            'message' => "Points d'attributs attribués avec honneur au Samouraï !",
            'hero' => $updatedHero
        ];
    }

    /**
     * Change le type de production du héros
     */
    public function setProductionType(int $userId, string $type): array {
        $allowed = ['balanced', 'metal', 'crystal', 'deuterium'];
        if (!in_array($type, $allowed)) {
            return ['success' => false, 'error' => 'Mode de production inconnu.'];
        }

        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['success' => false, 'error' => 'Héros introuvable.'];

        $this->db->prepare("UPDATE heroes SET production_type = ? WHERE id = ?")->execute([$type, $hero['id']]);

        return [
            'success' => true,
            'message' => "Orientation de la production du Samouraï modifiée.",
            'hero' => $this->getHeroByUserId($userId)
        ];
    }

    /**
     * Retourne le bonus de production apporté par le héros sur une planète donnée
     */
    public function getHeroProductionBonus(int $planetId): array {
        $stmt = $this->db->prepare("SELECT user_id FROM heroes WHERE current_planet_id = ? AND status = 'home'");
        $stmt->execute([$planetId]);
        $uId = $stmt->fetchColumn();
        if (!$uId) {
            return ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        }

        $hero = $this->getHeroByUserId((int)$uId);
        if (!$hero || $hero['status'] !== 'home' || $hero['health'] <= 0) {
            return ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        }

        return $hero['effective']['hourly_production'] ?? ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
    }

    /**
     * Ajoute de l'expérience au héros et gère le passage de niveau (+4 points par niveau)
     */
    public function addExperience(int $userId, int $xpEarned): array {
        if ($xpEarned <= 0) return ['levels_gained' => 0];

        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['levels_gained' => 0];

        $currentXp = (int)$hero['experience'] + $xpEarned;
        $currentLvl = (int)$hero['level'];
        $levelsGained = 0;

        while ($currentXp >= self::getXpForNextLevel($currentLvl)) {
            $currentLvl++;
            $levelsGained++;
        }

        $addPoints = $levelsGained * 4;

        $stmt = $this->db->prepare("
            UPDATE heroes 
            SET experience = ?, 
                level = ?, 
                unassigned_points = unassigned_points + ?,
                health = LEAST(100.0, health + ?) 
            WHERE id = ?
        ");
        // Soin partiel de +20% à chaque niveau gagné
        $healBonus = $levelsGained * 20.0;
        $stmt->execute([$currentXp, $currentLvl, $addPoints, $healBonus, $hero['id']]);

        return [
            'levels_gained' => $levelsGained,
            'new_level' => $currentLvl,
            'new_xp' => $currentXp,
            'new_points' => (int)$hero['unassigned_points'] + $addPoints
        ];
    }

    /**
     * Applique des dégâts de santé au héros
     */
    public function applyDamage(int $userId, float $damagePct): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero || $hero['status'] === 'dead') return ['died' => false];

        $newHealth = max(0.0, (float)$hero['health'] - $damagePct);
        $died = ($newHealth <= 0.0);
        $newStatus = $died ? 'dead' : $hero['status'];

        $this->db->prepare("
            UPDATE heroes 
            SET health = ?, status = ?, last_health_update = UNIX_TIMESTAMP() 
            WHERE id = ?
        ")->execute([$newHealth, $newStatus, $hero['id']]);

        return [
            'died' => $died,
            'new_health' => $newHealth,
            'status' => $newStatus
        ];
    }

    /**
     * Rituel de résurrection du héros
     */
    public function reviveHero(int $userId, int $planetId): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['success' => false, 'error' => 'Héros introuvable.'];

        if ($hero['status'] !== 'dead') {
            return ['success' => false, 'error' => 'Votre Samouraï est déjà en vie.'];
        }

        $planetEngine = new PlanetEngine();
        $planet = $planetEngine->getPlanet($planetId);
        if (!$planet || (int)$planet['user_id'] !== $userId) {
            return ['success' => false, 'error' => 'Fief invalide.'];
        }

        // Coût de résurrection basé sur le niveau
        $lvl = (int)$hero['level'];
        $costMetal = 800 + ($lvl * 150);
        $costCrystal = 800 + ($lvl * 150);
        $costDeut = 1200 + ($lvl * 250);

        if ($planet['metal'] < $costMetal || $planet['crystal'] < $costCrystal || $planet['deuterium'] < $costDeut) {
            return [
                'success' => false, 
                'error' => "Ressources insuffisantes pour le rituel de résurrection (Requis : {$costMetal} 🪵, {$costCrystal} 🪨, {$costDeut} 🌾)."
            ];
        }

        // Durée de régénération du héros après sa mort : 24 heures (86400s)
        $gameSpeed = max(1, (float)GameConfig::get('game_speed', 1));
        $duration = max(60, (int)(86400 / $gameSpeed));
        $finishTime = time() + $duration;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
                WHERE id = ?
            ")->execute([$costMetal, $costCrystal, $costDeut, $planetId]);

            $this->db->prepare("
                UPDATE heroes 
                SET status = 'reviving', 
                    current_planet_id = ?, 
                    revive_finish_time = ?, 
                    last_health_update = UNIX_TIMESTAMP() 
                WHERE id = ?
            ")->execute([$planetId, $finishTime, $hero['id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Le rituel sacré de régénération a débuté au donjon ! Votre Samouraï recouvrera l'intégralité de ses forces dans 24 heures.",
                'finish_time' => $finishTime,
                'duration' => $duration
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la régénération : ' . $e->getMessage()];
        }
    }

    /**
     * Garantit qu'un joueur a toujours un nombre minimal d'aventures provinciales disponibles
     */
    public function ensureAvailableAdventures(int $userId, int $targetCount = 3): int {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return 0;

        $stmtCount = $this->db->prepare("
            SELECT COUNT(*) FROM hero_adventures 
            WHERE user_id = ? AND status = 'available'
        ");
        $stmtCount->execute([$userId]);
        $currentCount = (int)$stmtCount->fetchColumn();

        if ($currentCount >= $targetCount) {
            return 0;
        }

        $needed = $targetCount - $currentCount;
        $centerX = (int)($hero['coord_x'] ?? 1);
        $centerY = (int)($hero['coord_y'] ?? 1);

        $templates = [
            ['name' => 'Sanctuaire Shintō Abandonné dans la Forêt', 'diff' => 'easy'],
            ['name' => 'Ruines d\'un Vieux Donjon Fief Noir', 'diff' => 'easy'],
            ['name' => 'Gorge Brumeuse et Repaire de Ronins', 'diff' => 'medium'],
            ['name' => 'Temple Antique Caché sous les Bambous', 'diff' => 'easy'],
            ['name' => 'Campement de Ronins Hors-la-loi', 'diff' => 'medium'],
            ['name' => 'Cimetière des Guerriers Oubliés', 'diff' => 'easy'],
            ['name' => 'Grotte du Dragon Fluvial', 'diff' => 'hard'],
            ['name' => 'Pagode Oubliée des Maîtres d\'Armes', 'diff' => 'medium'],
            ['name' => 'Défilé des Shinobis de l\'Ombre', 'diff' => 'hard'],
            ['name' => 'Vallée des Cerisiers Ancestraux', 'diff' => 'easy'],
            ['name' => 'Fortin Délaissé du Clan Déchu', 'diff' => 'medium'],
            ['name' => 'Montagne Sacrée du Dieu Tonnerre Raiden', 'diff' => 'hard']
        ];

        shuffle($templates);
        $created = 0;

        $stmtInsert = $this->db->prepare("
            INSERT INTO hero_adventures (user_id, coord_x, coord_y, name, difficulty, status) 
            VALUES (?, ?, ?, ?, ?, 'available')
        ");

        for ($i = 0; $i < $needed; $i++) {
            $t = $templates[$i % count($templates)];
            $dx = rand(-7, 7);
            $dy = rand(-7, 7);
            if ($dx === 0 && $dy === 0) {
                $dx = ($i % 2 === 0) ? 3 : -3;
                $dy = ($i % 2 === 0) ? 2 : -2;
            }

            $stmtInsert->execute([
                $userId,
                $centerX + $dx,
                $centerY + $dy,
                $t['name'],
                $t['diff']
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Récupère la liste des aventures disponibles pour un joueur
     */
    public function getAdventures(int $userId): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return [];

        // Garantir qu'il y a toujours au moins 3 aventures provinciales actives
        $this->ensureAvailableAdventures($userId, 3);

        $stmt = $this->db->prepare("
            SELECT * FROM hero_adventures 
            WHERE user_id = ? AND status = 'available' 
            ORDER BY id ASC
        ");
        $stmt->execute([$userId]);
        $adventures = $stmt->fetchAll();

        // Calculer la distance et la durée depuis le fief d'attache actuel
        $heroX = (int)$hero['coord_x'];
        $heroY = (int)$hero['coord_y'];
        $gameSpeed = max(1, (float)GameConfig::get('game_speed', 5));
        $speedBonus = (float)($hero['effective']['equipment_speed_bonus'] ?? 0);

        foreach ($adventures as &$adv) {
            $dist = sqrt(pow($adv['coord_x'] - $heroX, 2) + pow($adv['coord_y'] - $heroY, 2));
            $baseSpeed = 12 * (1 + ($speedBonus / 100.0));
            $duration = max(30, (int)round(($dist / $baseSpeed) * 3600 / $gameSpeed));

            $adv['distance'] = round($dist, 1);
            $adv['duration'] = $duration;
            $adv['duration_seconds'] = $duration;
        }

        return $adventures;
    }

    /**
     * Nombre d'aventures entreprises par le héros aujourd'hui (depuis minuit)
     */
    public function getDailyAdventuresCount(int $userId): int {
        $startOfDay = strtotime('today midnight');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM fleet_missions 
            WHERE user_id = ? 
              AND mission_type = 'adventure' 
              AND departure_time >= ?
        ");
        $stmt->execute([$userId, $startOfDay]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retourne les informations complètes sur le quota d'aventures quotidiennes (max 3/jour)
     */
    public function getDailyAdventureQuota(int $userId): array {
        $count = $this->getDailyAdventuresCount($userId);
        $max = self::MAX_DAILY_ADVENTURES;
        $remaining = max(0, $max - $count);
        $resetTimestamp = strtotime('tomorrow midnight');

        return [
            'count' => $count,
            'max' => $max,
            'remaining' => $remaining,
            'can_adventure' => ($remaining > 0),
            'reset_timestamp' => $resetTimestamp,
            'seconds_until_reset' => max(0, $resetTimestamp - time())
        ];
    }

    /**
     * Lance le héros en aventure féodale (limité à 3 par jour)
     */
    public function startAdventure(int $userId, int $adventureId): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['success' => false, 'error' => 'Héros introuvable.'];

        if ($hero['status'] !== 'home') {
            return ['success' => false, 'error' => 'Votre Samouraï doit être présent au domaine pour partir en aventure.'];
        }

        if ($hero['health'] < 15.0) {
            return ['success' => false, 'error' => 'La santé de votre Samouraï est trop faible (< 15%). Laissez-le panser ses blessures.'];
        }

        // Vérification de la limite quotidienne : 3 aventures par jour maxi
        $dailyQuota = $this->getDailyAdventureQuota($userId);
        if (!$dailyQuota['can_adventure']) {
            return [
                'success' => false,
                'error' => "Limite quotidienne atteinte : votre Samouraï ne peut accomplir que " . self::MAX_DAILY_ADVENTURES . " aventures par jour (" . $dailyQuota['count'] . "/" . self::MAX_DAILY_ADVENTURES . " effectuées). Il doit se reposer au fief jusqu'à minuit."
            ];
        }

        $stmtAdv = $this->db->prepare("SELECT * FROM hero_adventures WHERE id = ? AND user_id = ? AND status = 'available'");
        $stmtAdv->execute([$adventureId, $userId]);
        $adv = $stmtAdv->fetch();
        if (!$adv) {
            return ['success' => false, 'error' => 'Cette aventure n\'est plus accessible ou a déjà été accomplie.'];
        }

        // Calcul distance et durée
        $dist = sqrt(pow($adv['coord_x'] - (int)$hero['coord_x'], 2) + pow($adv['coord_y'] - (int)$hero['coord_y'], 2));
        $gameSpeed = max(1, (float)GameConfig::get('game_speed', 5));
        $duration = max(20, (int)round(($dist / 12) * 3600 / $gameSpeed));

        $now = time();
        $arrivalTime = $now + $duration;
        $returnTime = $arrivalTime + $duration;

        $this->db->beginTransaction();
        try {
            // Créer la mission de flotte avec mission_type = 'adventure'
            $stmtMission = $this->db->prepare("
                INSERT INTO fleet_missions 
                (user_id, source_planet_id, adventure_id, mission_type, fleet_data, cargo_data, has_hero, departure_time, arrival_time, return_time, status) 
                VALUES (?, ?, ?, 'adventure', '[]', '{}', 1, ?, ?, ?, 'en_route')
            ");
            $stmtMission->execute([$userId, $hero['current_planet_id'], $adventureId, $now, $arrivalTime, $returnTime]);
            $missionId = (int)$this->db->lastInsertId();

            // Mettre à jour l'aventure et le héros
            $this->db->prepare("UPDATE hero_adventures SET status = 'in_progress' WHERE id = ?")->execute([$adventureId]);
            $this->db->prepare("UPDATE heroes SET status = 'adventure' WHERE id = ?")->execute([$hero['id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Votre Samouraï est parti en quête vers « {$adv['name']} » !",
                'mission_id' => $missionId,
                'arrival_time' => $arrivalTime,
                'return_time' => $returnTime,
                'duration' => $duration
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors du départ en aventure : ' . $e->getMessage()];
        }
    }

    /**
     * Résout une aventure lorsque le héros arrive à destination
     */
    public function resolveAdventureArrival(array $mission): void {
        $missionId = (int)$mission['id'];
        $userId = (int)$mission['user_id'];
        $advId = (int)$mission['adventure_id'];

        $stmtAdv = $this->db->prepare("SELECT * FROM hero_adventures WHERE id = ?");
        $stmtAdv->execute([$advId]);
        $adv = $stmtAdv->fetch();
        if (!$adv) return;

        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return;

        // 1. Dégâts subis selon difficulté
        $diff = $adv['difficulty'];
        $damageRanges = [
            'easy' => [5, 15],
            'medium' => [12, 25],
            'hard' => [20, 38]
        ];
        $rng = $damageRanges[$diff] ?? [8, 18];
        $dmg = (float)rand($rng[0], $rng[1]);

        $dmgRes = $this->applyDamage($userId, $dmg);

        // 2. Gain d'XP (base réduite pour freiner la progression trop rapide + modificateur admin paramétrable)
        $xpRanges = ['easy' => [25, 45], 'medium' => [45, 75], 'hard' => [75, 120]];
        $xpRng = $xpRanges[$diff] ?? [30, 50];
        $xpGain = rand($xpRng[0], $xpRng[1]);

        // Modificateur d'expérience globale issu de l'Administration (Paramétrage du Monde)
        $xpRatePct = max(10, min(500, (int)GameConfig::get('hero_xp_rate_percent', 100)));
        if ($xpRatePct !== 100) {
            $xpGain = (int)round($xpGain * ($xpRatePct / 100.0));
        }

        // Bonus d'XP issu des reliques équipées (Miroir de Yata, Parchemins secrets, Kabutos dorés, etc.)
        $expBonusPct = (float)($hero['effective']['exp_bonus_pct'] ?? 0);
        if ($expBonusPct > 0) {
            $xpGain = (int)round($xpGain * (1.0 + ($expBonusPct / 100.0)));
        }

        $xpGain = max(1, $xpGain);
        $xpRes = $this->addExperience($userId, $xpGain);

        // 3. Déterminer le trésor / butin trouvé
        $lootRoll = rand(1, 100);
        $cargoData = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        $lootMsg = "";
        $rewardedItem = null;
        $ralliedTroops = null;

        if ($lootRoll <= 40) {
            // Ressources
            $mult = rand(5, 15) * 100;
            $cargoData['metal'] = $mult;
            $cargoData['crystal'] = (int)round($mult * 0.8);
            $cargoData['deuterium'] = (int)round($mult * 0.6);
            $lootMsg = "Des coffres de guerre dissimulés contenant {$cargoData['metal']} 🪵 Bois, {$cargoData['crystal']} 🪨 Pierre et {$cargoData['deuterium']} 🌾 Koku de Riz ont été découverts !";
        } elseif ($lootRoll <= 65) {
            // Équipement / Arsenal (Relique unique - jamais de doublon)
            $rewardedItem = $this->grantRandomEquipment($userId);
            if ($rewardedItem) {
                $lootMsg = "Une relique légendaire sacrée et inédite a été exhumée : « {$rewardedItem['name']} » ({$rewardedItem['description']}) !";
            } else {
                // Si toutes les reliques sont déjà possédées par le joueur, récompense en abondance de ressources
                $mult = rand(15, 25) * 100;
                $cargoData['metal'] = $mult;
                $cargoData['crystal'] = (int)round($mult * 0.8);
                $cargoData['deuterium'] = (int)round($mult * 0.6);
                $lootMsg = "Possédant déjà toutes les reliques sacrées de l'archipel, votre Samouraï découvre à la place un opulent trésor féodal : {$cargoData['metal']} 🪵 Bois, {$cargoData['crystal']} 🪨 Pierre et {$cargoData['deuterium']} 🌾 Koku de Riz !";
            }
        } elseif ($lootRoll <= 80) {
            // Cages de Capture Féodales (Kago 🎋) pour capturer les bêtes sauvages dans les oasis
            $cagesFound = rand(4, 10);
            $this->addCages($userId, $cagesFound);
            $totalCages = $this->getCagesCount($userId);
            $lootMsg = "Un lot de <strong>{$cagesFound} Cages Féodales de Chasse aux Fauves (Kago 🎋)</strong> en bambou armé a été récupéré (Stock total : {$totalCages}) ! Votre Samouraï pourra s'en servir pour capturer vivantes les bêtes sauvages des oasis sans combat.";
        } else {
            // Ralliement de guerriers conscrits
            $faction = $hero['faction'] ?? 'terran';
            $unitCodes = [
                'terran' => ['code' => 'piquier_ashigaru_yari', 'name' => 'Piquiers Ashigaru'],
                'vorash' => ['code' => 'fantassin_leger_takeda', 'name' => 'Fantassins Légers'],
                'aethelis' => ['code' => 'sentinelle_yari_tokugawa', 'name' => 'Sentinelles Yari']
            ];
            $uInfo = $unitCodes[$faction] ?? $unitCodes['terran'];
            $troopCount = rand(4, 10);
            $ralliedTroops = ['code' => $uInfo['code'], 'count' => $troopCount, 'name' => $uInfo['name']];
            $lootMsg = "Des ronins et vaillants guerriers errants ({$troopCount} {$uInfo['name']}) impressionnés par la bravoure du Samouraï se rallient à votre clan !";
        }

        // 4. Marquer l'aventure comme terminée
        $this->db->prepare("UPDATE hero_adventures SET status = 'completed' WHERE id = ?")->execute([$advId]);

        // 5. Générer une nouvelle aventure pour remplacer celle-ci
        $this->generateReplacementAdventure($userId, (int)$hero['coord_x'], (int)$hero['coord_y']);

        // 6. Si le héros a péri pendant l'aventure
        if ($dmgRes['died']) {
            $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
            $title = "Chronique Funeste : Votre Samouraï est tombé à « {$adv['name']} »";
            $body = "Hélas, noble Daimyō ! Lors de son expédition dans les contrées périlleuses de {$adv['name']}, votre Samouraï a succombé à ses blessures (-{$dmg}% PV).\n\nVous pouvez accomplir le rituel de résurrection depuis votre Tenshu ou votre dojo.";
        } else {
            // Le héros entame sa marche de retour avec le butin
            $stmtUpdateMission = $this->db->prepare("
                UPDATE fleet_missions 
                SET status = 'returning', 
                    cargo_data = ? 
                WHERE id = ?
            ");
            $stmtUpdateMission->execute([json_encode($cargoData), $missionId]);

            // Si troupes ralliées, les stocker dans fleet_data pour le retour
            if ($ralliedTroops) {
                $fleetData = [$ralliedTroops['code'] => $ralliedTroops['count']];
                $this->db->prepare("UPDATE fleet_missions SET fleet_data = ? WHERE id = ?")->execute([json_encode($fleetData), $missionId]);
            }

            $title = "Rapport d'Expédition Féodale : « {$adv['name']} »";
            $lvlMsg = ($xpRes['levels_gained'] > 0) ? "\n✨ Votre Samouraï s'élève au Niveau {$xpRes['new_level']} et gagne " . ($xpRes['levels_gained'] * 4) . " points d'attributs !" : "";
            $body = "Votre Samouraï est parvenu à explorer {$adv['name']}.\n\n- Vitalité entamée : -{$dmg}% PV (Santé restante : " . round($dmgRes['new_health']) . "%)\n- Expérience acquise : +{$xpGain} XP{$lvlMsg}\n- Butin de l'expédition : {$lootMsg}\n\nLe héros prend la route du retour vers le domaine.";
        }

        // Créer un rapport de mission
        require_once __DIR__ . '/MessageEngine.php';
        $msgEngine = new MessageEngine();
        $msgEngine->sendSystemMessage($userId, $title, $body);
    }

    /**
     * Attribue un équipement de samouraï aléatoire (Relique unique : jamais de doublon)
     */
    public function grantRandomEquipment(int $userId): ?array {
        $pool = [
            // ==========================================
            // 1. ARMES DU SAMOURAÏ (weapon) - 7 reliques
            // ==========================================
            [
                'code' => 'katana_tamahagane',
                'type' => 'weapon',
                'name' => 'Katana Forgé en Tamahagane',
                'desc' => 'Lame d\'exception forgée dans le meilleur acier plié selon les secrets des maîtres forgerons de Bizen.',
                'bonus' => ['strength' => 320, 'offense_bonus' => 1.5]
            ],
            [
                'code' => 'yari_ancestrale',
                'type' => 'weapon',
                'name' => 'Yari Ancestrale des Clans',
                'desc' => 'Longue lance d\'hast redoutable capable de repousser les charges de cavalerie et d\'éventrer les rangs ennemis.',
                'bonus' => ['strength' => 260, 'defense_bonus' => 2.0]
            ],
            [
                'code' => 'gunbai_commandement',
                'type' => 'weapon',
                'name' => 'Gunbai de Commandement Impérial',
                'desc' => 'Éventail de guerre massif en fer et laque, brandi pour ordonner des manœuvres offensives dévastatrices.',
                'bonus' => ['strength' => 200, 'offense_bonus' => 2.5]
            ],
            [
                'code' => 'nodachi_tempete',
                'type' => 'weapon',
                'name' => 'Nodachi Faucheur de Tempête',
                'desc' => 'Épée colossale à deux mains nécessitant une vigueur surhumaine pour faucher des lignes entières de fantassins.',
                'bonus' => ['strength' => 450, 'offense_bonus' => 1.0]
            ],
            [
                'code' => 'naginata_bugeisha',
                'type' => 'weapon',
                'name' => 'Naginata de la Noble Guerrière',
                'desc' => 'Arme d\'hast élégante à lame courbe, tournoyant avec une précision gracieuse et mortelle.',
                'bonus' => ['strength' => 240, 'offense_bonus' => 1.2, 'defense_bonus' => 1.2]
            ],
            [
                'code' => 'yumi_asagao',
                'type' => 'weapon',
                'name' => 'Grand Arc Yumi en Bambou Laqué',
                'desc' => 'Arc asymétrique d\'archer d\'élite, dont les flèches sifflantes percent les armures à grande distance.',
                'bonus' => ['strength' => 280, 'offense_bonus' => 2.0]
            ],
            [
                'code' => 'tanto_masamune',
                'type' => 'weapon',
                'name' => 'Tantō Céleste de Masamune',
                'desc' => 'Dague de maître dotée d\'une ligne de trempe hamon mystique, réputée ne couper que le mal.',
                'bonus' => ['strength' => 380, 'defense_bonus' => 1.5]
            ],

            // ==========================================
            // 2. CASQUES ET MASQUES (helmet) - 7 reliques
            // ==========================================
            [
                'code' => 'kabuto_cornes_or',
                'type' => 'helmet',
                'name' => 'Kabuto aux Cornes d\'Or du Shōgun',
                'desc' => 'Casque orné de bois dorés étincelants, inspirant le courage et stimulant l\'apprentissage tactique.',
                'bonus' => ['strength' => 150, 'exp_bonus' => 20]
            ],
            [
                'code' => 'kabuto_croissant_lune',
                'type' => 'helmet',
                'name' => 'Kabuto au Croissant de Lune de Sendai',
                'desc' => 'Casque spectaculaire surmonté d\'un fin croissant lunaire en laiton poli, symbole de fierté et de résilience.',
                'bonus' => ['strength' => 200, 'defense_bonus' => 1.8]
            ],
            [
                'code' => 'menpo_oni',
                'type' => 'helmet',
                'name' => 'Masque de Guerre Menpō du Démon Oni',
                'desc' => 'Masque facial en fer forgé aux crocs menaçants et moustaches de crin, pétrifiant d\'effroi les assaillants.',
                'bonus' => ['strength' => 250, 'offense_bonus' => 1.5]
            ],
            [
                'code' => 'kabuto_dragon_kai',
                'type' => 'helmet',
                'name' => 'Kabuto au Dragon Suprême de Kai',
                'desc' => 'Casque lourd orné d\'un dragon sculpté protégeant la tête du guerrier des impacts les plus violents.',
                'bonus' => ['strength' => 220, 'defense_bonus' => 2.2]
            ],
            [
                'code' => 'kasa_acier_shinobi',
                'type' => 'helmet',
                'name' => 'Jingasa en Acier Trempé de l\'Ombre',
                'desc' => 'Chapeau conique en plaques d\'acier trempé permettant une vision panoramique et une protection contre les flèches.',
                'bonus' => ['strength' => 180, 'defense_bonus' => 1.2, 'exp_bonus' => 10]
            ],
            [
                'code' => 'kabuto_soleil_levant',
                'type' => 'helmet',
                'name' => 'Kabuto de l\'Astre Solaire Radieux',
                'desc' => 'Chef-d\'œuvre d\'armurerie dont le maedate représente l\'aurore impériale, galvanisant la ferveur des troupes.',
                'bonus' => ['strength' => 160, 'offense_bonus' => 1.8, 'exp_bonus' => 10]
            ],
            [
                'code' => 'kabuto_cerf_sanada',
                'type' => 'helmet',
                'name' => 'Kabuto aux Cornes de Cerf et Six Pièces',
                'desc' => 'Casque écarlate légendaire flanqué de ramures de cerf et de l\'emblème Rokumonsen des guerriers sans peur.',
                'bonus' => ['strength' => 270, 'offense_bonus' => 2.0]
            ],

            // ==========================================
            // 3. ARMURES ET TENUES (armor) - 7 reliques
            // ==========================================
            [
                'code' => 'cuirasse_oyoroi',
                'type' => 'armor',
                'name' => 'Cuirasse Ō-Yoroi des Grands Seigneurs',
                'desc' => 'Armure seigneuriale laquée à plaques kozane tressées de soie pourpre, forteresse imprenable pour le champion.',
                'bonus' => ['strength' => 380, 'defense_bonus' => 2.0]
            ],
            [
                'code' => 'armure_do_maru',
                'type' => 'armor',
                'name' => 'Armure Dō-Maru des Gardes d\'Élite',
                'desc' => 'Armure composite enveloppante favorisant le combat au corps à corps et la vélocité tactique.',
                'bonus' => ['strength' => 280, 'offense_bonus' => 1.2, 'defense_bonus' => 1.2]
            ],
            [
                'code' => 'plastron_nanban',
                'type' => 'armor',
                'name' => 'Plastron Nanban d\'Acier Étranger',
                'desc' => 'Cuirasse renforcée d\'inspiration occidentale forgée pour dévier les balles d\'arquebuse et les piques.',
                'bonus' => ['strength' => 350, 'defense_bonus' => 2.5]
            ],
            [
                'code' => 'armure_rouge_iinao',
                'type' => 'armor',
                'name' => 'Armure Écarlate des Diables Rouges',
                'desc' => 'Ensemble d\'armure intégralement laqué de vermillon vif, semant la panique dans les rangs adverses lors des charges.',
                'bonus' => ['strength' => 320, 'offense_bonus' => 2.2]
            ],
            [
                'code' => 'jimbaori_armoiries',
                'type' => 'armor',
                'name' => 'Jimbaori Brodé aux Armoiries du Clan',
                'desc' => 'Manteau d\'apparat en soie damassée et fils d\'or, conférant une autorité royale et un moral inébranlable.',
                'bonus' => ['strength' => 200, 'offense_bonus' => 1.5, 'defense_bonus' => 1.5]
            ],
            [
                'code' => 'haramaki_champions',
                'type' => 'armor',
                'name' => 'Haramaki Léger des Maîtres d\'Escrime',
                'desc' => 'Plastron dorsal léger permettant des esquives fulgurantes et un enchaînement ininterrompu de frappes.',
                'bonus' => ['strength' => 260, 'offense_bonus' => 1.8, 'speed' => 10]
            ],
            [
                'code' => 'armure_ebene_takeda',
                'type' => 'armor',
                'name' => 'Armure d\'Ébène et d\'Or de Kōfu',
                'desc' => 'Armure cérémoniale et guerrière noire aux ferrures dorées, symbole de puissance immuable sur le champ de bataille.',
                'bonus' => ['strength' => 420, 'defense_bonus' => 1.8]
            ],

            // ==========================================
            // 4. MONTURES ET DESTRIERS (horse) - 7 reliques
            // ==========================================
            [
                'code' => 'etalon_kai',
                'type' => 'horse',
                'name' => 'Pur-Sang Écarlate des Plaines de Kai',
                'desc' => 'Fier étalon de guerre issu des haras réputés de Takeda, doué d\'une vitesse et d\'une endurance prodigieuses.',
                'bonus' => ['speed' => 40, 'strength' => 120]
            ],
            [
                'code' => 'destrier_noir_kiso',
                'type' => 'horse',
                'name' => 'Destrier Noir des Gorges de Kiso',
                'desc' => 'Cheval trapu et vigoureux des montagnes nippones, habitué à franchir les cols escarpés sous la neige.',
                'bonus' => ['speed' => 30, 'strength' => 180]
            ],
            [
                'code' => 'destrier_cuirasse_bamen',
                'type' => 'horse',
                'name' => 'Destrier Cuirassé au Bamen de Fer',
                'desc' => 'Colosse équestre protégé par un caparaçon laqué et un chanfrein de fer fendant sans faiblir les volées de traits.',
                'bonus' => ['speed' => 25, 'strength' => 220, 'defense_bonus' => 1.0]
            ],
            [
                'code' => 'cheval_bai_musashi',
                'type' => 'horse',
                'name' => 'Cheval Bai du Vagabond Invaincu',
                'desc' => 'Monture agile et attentive, capable de voyager sur de longues distances sans jamais faiblir.',
                'bonus' => ['speed' => 35, 'strength' => 140, 'exp_bonus' => 10]
            ],
            [
                'code' => 'etalon_blanc_benten',
                'type' => 'horse',
                'name' => 'Étalon Blanc Sanctifié de Benzaiten',
                'desc' => 'Cheval immaculé consacré aux divinités fluviales, dont le pas léger semble survoler fondrières et rizières.',
                'bonus' => ['speed' => 45, 'strength' => 90]
            ],
            [
                'code' => 'pur_sang_date',
                'type' => 'horse',
                'name' => 'Pur-Sang d\'Ōshū aux Sabots d\'Éclair',
                'desc' => 'Étalon fougueux élevé dans les pâturages du nord, réputé pour ses charges fulgurantes à la tête de la cavalerie.',
                'bonus' => ['speed' => 38, 'strength' => 150, 'offense_bonus' => 1.0]
            ],
            [
                'code' => 'destrier_ambre_kyoto',
                'type' => 'horse',
                'name' => 'Destrier Ambré de la Garde Impériale',
                'desc' => 'Monture majestueuse à la robe dorée sélectionnée parmi les étalons d\'élite de la capitale impériale.',
                'bonus' => ['speed' => 32, 'strength' => 160, 'defense_bonus' => 0.8]
            ],

            // ==========================================
            // 5. TALISMANS ET TRÉSORS SACRÉS (talisman) - 7 reliques
            // ==========================================
            [
                'code' => 'omamori_sacree',
                'type' => 'talisman',
                'name' => 'Omamori Sacrée des Moissons d\'Inari',
                'desc' => 'Amulette protectrice en brocart blanc et rouge bénie par les prêtresses renardes, abondant les réserves de riz.',
                'bonus' => ['production_rice' => 50, 'strength' => 90]
            ],
            [
                'code' => 'miroir_yata_bronze',
                'type' => 'talisman',
                'name' => 'Miroir Sacré de Yata en Bronze Poli',
                'desc' => 'Relique shintō millénaire reflétant la pureté de l\'âme du guerrier et éclairant les chemins de l\'illumination martiale.',
                'bonus' => ['strength' => 240, 'exp_bonus' => 20]
            ],
            [
                'code' => 'magatama_jade',
                'type' => 'talisman',
                'name' => 'Magatama en Jade Céleste de Yasakani',
                'desc' => 'Joyau incurvé taillé dans le jade le plus pur, dynamisant l\'exploitation forestière du domaine.',
                'bonus' => ['production_wood' => 50, 'strength' => 100]
            ],
            [
                'code' => 'clochette_kagura',
                'type' => 'talisman',
                'name' => 'Clochette Kagura des Rituels Miko',
                'desc' => 'Sonnaille cérémonielle en laiton chassant les esprits néfastes et favorisant la prospérité des carrières de pierre.',
                'bonus' => ['production_stone' => 50, 'strength' => 100]
            ],
            [
                'code' => 'parchemin_art_guerre',
                'type' => 'talisman',
                'name' => 'Parchemin Secret du Dokkōdō',
                'desc' => 'Traité philosophique et tactique manuscrit, instruisant le héros sur la voie de la solitude victorieuse.',
                'bonus' => ['strength' => 180, 'exp_bonus' => 25]
            ],
            [
                'code' => 'perle_ryujin',
                'type' => 'talisman',
                'name' => 'Perle de Marée du Dieu Dragon Ryūjin',
                'desc' => 'Gemme marine mystique contrôlant les flux des eaux, procurant une abondance harmonieuse au domaine.',
                'bonus' => ['production_rice' => 40, 'production_wood' => 30, 'production_stone' => 30]
            ],
            [
                'code' => 'sceau_chrysantheme',
                'type' => 'talisman',
                'name' => 'Sceau Impérial en Bois de Santal',
                'desc' => 'Sceau d\'autorité suprême gravé aux armoiries du chrysanthème, octroyant un prestige inouï et une influence souveraine.',
                'bonus' => ['strength' => 150, 'offense_bonus' => 1.5, 'defense_bonus' => 1.5]
            ]
        ];

        // RÈGLE : Ne peut pas obtenir deux fois la même relique
        $stmt = $this->db->prepare("SELECT item_code FROM hero_inventory WHERE user_id = ?");
        $stmt->execute([$userId]);
        $ownedCodes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $availablePool = array_values(array_filter($pool, function($item) use ($ownedCodes) {
            return !in_array($item['code'], $ownedCodes);
        }));

        if (empty($availablePool)) {
            return null; // Toutes les reliques uniques sont déjà possédées par le joueur
        }

        $chosen = $availablePool[array_rand($availablePool)];

        $stmtInsert = $this->db->prepare("
            INSERT INTO hero_inventory 
            (user_id, item_code, item_type, name, description, bonus_data, is_equipped) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmtInsert->execute([$userId, $chosen['code'], $chosen['type'], $chosen['name'], $chosen['desc'], json_encode($chosen['bonus'])]);

        $chosen['id'] = (int)$this->db->lastInsertId();
        $chosen['item_code'] = $chosen['code'];
        $chosen['description'] = $chosen['desc'];
        $chosen['bonus_data'] = $chosen['bonus'];
        $chosen['image_url'] = self::getItemImageUrl($chosen['code']);
        return $chosen;
    }

    /**
     * Génère une aventure de remplacement
     */
    public function generateReplacementAdventure(int $userId, int $centerX, int $centerY): void {
        $names = [
            ['name' => 'Temple Antique Caché sous les Bambous', 'diff' => 'easy'],
            ['name' => 'Campement de Ronins Hors-la-loi', 'diff' => 'medium'],
            ['name' => 'Cimetière des Guerriers Oubliés', 'diff' => 'easy'],
            ['name' => 'Grotte du Dragon Fluvial', 'diff' => 'hard'],
            ['name' => 'Ruines d\'un Village de Montagne Pillé', 'diff' => 'easy'],
            ['name' => 'Passage des Brumes de Kai', 'diff' => 'medium']
        ];
        $chosen = $names[array_rand($names)];
        $dx = rand(-6, 6);
        $dy = rand(-6, 6);
        if ($dx === 0 && $dy === 0) $dx = 3;

        $this->db->prepare("
            INSERT INTO hero_adventures (user_id, coord_x, coord_y, name, difficulty, status) 
            VALUES (?, ?, ?, ?, ?, 'available')
        ")->execute([$userId, $centerX + $dx, $centerY + $dy, $chosen['name'], $chosen['diff']]);
    }

    /**
     * Retourne l'URL de l'illustration d'une relique féodale
     */
    public static function getItemImageUrl(?string $itemCode): string {
        if (empty($itemCode)) {
            return '/public/assets/hero_samurai.jpg';
        }
        $extensions = ['jpeg', 'jpg', 'webp', 'png'];
        $baseDir = __DIR__ . '/../public/assets/items/';
        foreach ($extensions as $ext) {
            if (file_exists($baseDir . $itemCode . '.' . $ext)) {
                return '/public/assets/items/' . $itemCode . '.' . $ext;
            }
        }
        return '/public/assets/hero_samurai.jpg';
    }

    /**
     * Récupère l'inventaire du joueur
     */
    public function getInventory(int $userId): array {
        $this->ensureSchema();
        $stmt = $this->db->prepare("SELECT * FROM hero_inventory WHERE user_id = ? ORDER BY is_equipped DESC, id DESC");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$it) {
            if (empty($it['bonus_data'])) {
                $it['bonus_data'] = [];
            } elseif (is_string($it['bonus_data'])) {
                $it['bonus_data'] = json_decode($it['bonus_data'], true) ?: [];
            } elseif (!is_array($it['bonus_data'])) {
                $it['bonus_data'] = [];
            }
            $it['slot'] = $it['slot'] ?? ($it['item_type'] ?? 'weapon');
            $it['item_type'] = $it['slot'];
            $it['image_url'] = self::getItemImageUrl($it['item_code'] ?? '');
        }
        return $items;
    }

    /**
     * Équipe un objet de l'inventaire
     */
    public function equipItem(int $userId, int $itemId): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['success' => false, 'error' => 'Héros introuvable.'];

        $stmt = $this->db->prepare("SELECT * FROM hero_inventory WHERE id = ? AND user_id = ?");
        $stmt->execute([$itemId, $userId]);
        $item = $stmt->fetch();
        if (!$item) return ['success' => false, 'error' => 'Objet introuvable.'];

        $type = $item['item_type'];
        $slotFieldMap = [
            'weapon' => 'equipped_weapon',
            'helmet' => 'equipped_helmet',
            'armor' => 'equipped_armor',
            'horse' => 'equipped_horse',
            'talisman' => 'equipped_talisman'
        ];
        $field = $slotFieldMap[$type] ?? null;
        if (!$field) return ['success' => false, 'error' => 'Emplacement d\'équipement non pris en charge.'];

        $this->db->beginTransaction();
        try {
            // Déséquiper les objets précédents du même type
            $this->db->prepare("UPDATE hero_inventory SET is_equipped = 0 WHERE user_id = ? AND item_type = ?")
                ->execute([$userId, $type]);

            // Équiper le nouvel objet
            $this->db->prepare("UPDATE hero_inventory SET is_equipped = 1 WHERE id = ?")->execute([$itemId]);
            $this->db->prepare("UPDATE heroes SET {$field} = ? WHERE id = ?")->execute([$item['item_code'], $hero['id']]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "« {$item['name']} » a été équipé sur votre Samouraï !",
                'hero' => $this->getHeroByUserId($userId),
                'inventory' => $this->getInventory($userId)
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de l\'équipement : ' . $e->getMessage()];
        }
    }

    /**
     * Déséquipe un objet d'un emplacement
     */
    public function unequipSlot(int $userId, string $slot): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return ['success' => false, 'error' => 'Héros introuvable.'];

        $slotFieldMap = [
            'weapon' => 'equipped_weapon',
            'helmet' => 'equipped_helmet',
            'armor' => 'equipped_armor',
            'horse' => 'equipped_horse',
            'talisman' => 'equipped_talisman'
        ];
        $field = $slotFieldMap[$slot] ?? null;
        if (!$field) return ['success' => false, 'error' => 'Emplacement inconnu.'];

        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE hero_inventory SET is_equipped = 0 WHERE user_id = ? AND item_type = ?")
                ->execute([$userId, $slot]);
            $this->db->prepare("UPDATE heroes SET {$field} = NULL WHERE id = ?")->execute([$hero['id']]);
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Objet déséquipé.",
                'hero' => $this->getHeroByUserId($userId),
                'inventory' => $this->getInventory($userId)
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors du déséquipement : ' . $e->getMessage()];
        }
    }

    /**
     * Récupère le nombre de Cages Féodales (Kago 🎋) en stock pour le joueur
     */
    public function getCagesCount(int $userId): int {
        $this->ensureSchema();
        $stmt = $this->db->prepare("SELECT id, bonus_data FROM hero_inventory WHERE user_id = ? AND item_code = 'cages_capture' LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) return 0;
        $data = is_string($row['bonus_data']) ? json_decode($row['bonus_data'], true) : $row['bonus_data'];
        return (int)($data['cages_count'] ?? 0);
    }

    /**
     * Ajoute des Cages Féodales à l'inventaire du joueur
     */
    public function addCages(int $userId, int $count): void {
        $this->ensureSchema();
        if ($count <= 0) return;

        $stmt = $this->db->prepare("SELECT id, bonus_data FROM hero_inventory WHERE user_id = ? AND item_code = 'cages_capture' LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if ($row) {
            $data = is_string($row['bonus_data']) ? json_decode($row['bonus_data'], true) : $row['bonus_data'];
            $newCount = max(0, (int)($data['cages_count'] ?? 0) + $count);
            $data['cages_count'] = $newCount;
            $this->db->prepare("UPDATE hero_inventory SET bonus_data = ? WHERE id = ?")
                ->execute([json_encode($data), $row['id']]);
        } else {
            $data = ['cages_count' => $count];
            $stmtIns = $this->db->prepare("
                INSERT INTO hero_inventory (user_id, item_code, item_type, name, description, bonus_data, is_equipped)
                VALUES (?, 'cages_capture', 'consumable', 'Cages Féodales de Chasse (Kago 🎋)', 'Grandes cages de bambou armé permettant au Samouraï de capturer des bêtes sauvages (sangliers, loups, ours) dans les oasis sans combat pour protéger vos fiefs.', ?, 0)
            ");
            $stmtIns->execute([$userId, json_encode($data)]);
        }
    }

    /**
     * Consomme jusqu'à $count Cages Féodales et retourne le nombre effectivement consommé
     */
    public function consumeCages(int $userId, int $count): int {
        $this->ensureSchema();
        if ($count <= 0) return 0;

        $current = $this->getCagesCount($userId);
        $used = min($current, $count);
        if ($used > 0) {
            $stmt = $this->db->prepare("SELECT id, bonus_data FROM hero_inventory WHERE user_id = ? AND item_code = 'cages_capture' LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if ($row) {
                $data = is_string($row['bonus_data']) ? json_decode($row['bonus_data'], true) : $row['bonus_data'];
                $newCount = max(0, (int)($data['cages_count'] ?? 0) - $used);
                $data['cages_count'] = $newCount;
                if ($newCount <= 0) {
                    $this->db->prepare("DELETE FROM hero_inventory WHERE id = ?")->execute([$row['id']]);
                } else {
                    $this->db->prepare("UPDATE hero_inventory SET bonus_data = ? WHERE id = ?")
                        ->execute([json_encode($data), $row['id']]);
                }
            }
        }
        return $used;
    }
}

