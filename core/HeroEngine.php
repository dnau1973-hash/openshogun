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
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Crée et initialise un Samouraï Héros pour un joueur ainsi que ses 3 premières aventures
     */
    public function createHeroForUser(int $userId, string $name, int $planetId, ?int $coordX = null, ?int $coordY = null): array {
        $stmt = $this->db->prepare("
            INSERT INTO heroes 
            (user_id, current_planet_id, name, level, experience, health, status, last_health_update, unassigned_points) 
            VALUES (?, ?, ?, 1, 0, 100.0, 'home', UNIX_TIMESTAMP(), 4)
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
            SELECT h.*, p.name as planet_name, p.coord_x, p.coord_y, u.faction, u.username
            FROM heroes h 
            JOIN planets p ON p.id = h.current_planet_id 
            JOIN users u ON u.id = h.user_id 
            WHERE h.user_id = ?
        ");
        $stmt->execute([$userId]);
        $hero = $stmt->fetch();

        if (!$hero) {
            return null;
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
        }

        return [
            'combat_strength' => $combatStrength,
            'base_strength' => 150 + ($strengthPoints * 80),
            'equipment_strength' => $equipmentStrength,
            'offense_bonus_pct' => round($offenseBonusPct, 1),
            'defense_bonus_pct' => round($defenseBonusPct, 1),
            'hourly_production' => $hourlyProd,
            'equipment_speed_bonus' => $equipmentSpeedBonus
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

        // Durée de réanimation
        $gameSpeed = max(1, (float)GameConfig::get('game_speed', 5));
        $duration = max(30, (int)((300 + ($lvl * 60)) / $gameSpeed));
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
                'message' => "Le rituel sacré de résurrection a débuté au donjon ! Votre Samouraï recouvrera ses forces sous peu.",
                'finish_time' => $finishTime,
                'duration' => $duration
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la résurrection : ' . $e->getMessage()];
        }
    }

    /**
     * Récupère la liste des aventures disponibles pour un joueur
     */
    public function getAdventures(int $userId): array {
        $hero = $this->getHeroByUserId($userId);
        if (!$hero) return [];

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
     * Lance le héros en aventure féodale
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

        // 2. Gain d'XP
        $xpRanges = ['easy' => [80, 140], 'medium' => [140, 220], 'hard' => [220, 350]];
        $xpRng = $xpRanges[$diff] ?? [100, 150];
        $xpGain = rand($xpRng[0], $xpRng[1]);
        $xpRes = $this->addExperience($userId, $xpGain);

        // 3. Déterminer le trésor / butin trouvé
        $lootRoll = rand(1, 100);
        $cargoData = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        $lootMsg = "";
        $rewardedItem = null;
        $ralliedTroops = null;

        if ($lootRoll <= 50) {
            // Ressources
            $mult = rand(5, 15) * 100;
            $cargoData['metal'] = $mult;
            $cargoData['crystal'] = (int)round($mult * 0.8);
            $cargoData['deuterium'] = (int)round($mult * 0.6);
            $lootMsg = "Des coffres de guerre dissimulés contenant {$cargoData['metal']} 🪵 Bois, {$cargoData['crystal']} 🪨 Pierre et {$cargoData['deuterium']} 🌾 Koku de Riz ont été découverts !";
        } elseif ($lootRoll <= 80) {
            // Équipement / Arsenal
            $rewardedItem = $this->grantRandomEquipment($userId);
            $lootMsg = "Une relique légendaire a été exhumée : « {$rewardedItem['name']} » ({$rewardedItem['description']}) !";
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
     * Attribue un équipement de samouraï aléatoire
     */
    public function grantRandomEquipment(int $userId): array {
        $pool = [
            [
                'code' => 'katana_tamahagane',
                'type' => 'weapon',
                'name' => 'Katana Forgé en Tamahagane',
                'desc' => 'Lame d\'exception forgée dans le meilleur acier japonais.',
                'bonus' => ['strength' => 300, 'offense_bonus' => 1.5]
            ],
            [
                'code' => 'yari_ancestrale',
                'type' => 'weapon',
                'name' => 'Yari Ancestrale des Clans',
                'desc' => 'Longue lance d\'hast redoutable contre les cavaliers et les bêtes.',
                'bonus' => ['strength' => 250, 'defense_bonus' => 2.0]
            ],
            [
                'code' => 'kabuto_cornes_or',
                'type' => 'helmet',
                'name' => 'Kabuto aux Cornes d\'Or',
                'desc' => 'Casque orné inspirant le respect et stimulant l\'apprentissage tactique.',
                'bonus' => ['strength' => 150, 'exp_bonus' => 15]
            ],
            [
                'code' => 'cuirasse_oyoroi',
                'type' => 'armor',
                'name' => 'Cuirasse O-Yoroi Laquée',
                'desc' => 'Armure laquée lourde protégeant le samouraï contre les traits mortels.',
                'bonus' => ['strength' => 350, 'defense_bonus' => 1.5]
            ],
            [
                'code' => 'etalon_kai',
                'type' => 'horse',
                'name' => 'Pur-Sang Écarlate de Kai',
                'desc' => 'Fier destrier de la cavalerie de Takeda augmentant la rapidité de marche.',
                'bonus' => ['speed' => 35, 'strength' => 100]
            ],
            [
                'code' => 'omamori_sacree',
                'type' => 'talisman',
                'name' => 'Omamori Sacrée d\'Inari',
                'desc' => 'Amulette de soie bénie assurant la prospérité des récoltes du domaine.',
                'bonus' => ['production_rice' => 40, 'strength' => 80]
            ]
        ];

        $chosen = $pool[array_rand($pool)];

        $stmt = $this->db->prepare("
            INSERT INTO hero_inventory 
            (user_id, item_code, item_type, name, description, bonus_data, is_equipped) 
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $chosen['code'], $chosen['type'], $chosen['name'], $chosen['desc'], json_encode($chosen['bonus'])]);

        $chosen['id'] = (int)$this->db->lastInsertId();
        $chosen['item_code'] = $chosen['code'];
        $chosen['description'] = $chosen['desc'];
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
     * Récupère l'inventaire du joueur
     */
    public function getInventory(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM hero_inventory WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$it) {
            $it['bonus_data'] = json_decode($it['bonus_data'], true) ?: [];
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
}
