<?php
/**
 * Moteur de la Caserne Militaire et Entraînement des Soldats (Style Travian)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class BarracksEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    /**
     * Récupère les soldats stationnés dans la garnison de la planète
     */
    public function getStationedUnits(int $planetId, string $faction): array {
        $stmt = $this->db->prepare("
            SELECT u.*, COALESCE(pu.count, 0) as stationed_count 
            FROM units u 
            LEFT JOIN planet_units pu ON pu.unit_code = u.code AND pu.planet_id = ?
            WHERE u.faction = 'all' OR u.faction = ?
            ORDER BY u.tier ASC, u.attack ASC
        ");
        $stmt->execute([$planetId, $faction]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère la liste des unités entraînables avec conditions de niveau
     */
    public function getAvailableUnits(int $planetId, string $faction): array {
        $buildings = $this->planetEngine->getBuildings($planetId);
        $barracksLvl = $buildings['barracks'] ?? 0;

        $units = $this->getStationedUnits($planetId, $faction);

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0;

        // Bonus du Banquet des Guerriers (Kanpai aux Samouraïs au Tenshu)
        $feastSpeedBonus = 1.0;
        try {
            $activeFeast = $this->planetEngine->getActiveFeast($planetId);
            if ($activeFeast && $activeFeast['feast_type'] === 'warriors') {
                $tLvl = (int)($activeFeast['tenshu_level'] ?? 1);
                $reduction = 0.10 + ($tLvl * 0.01);
                $feastSpeedBonus = 1.0 - min(0.35, $reduction);
            }
        } catch (Exception $e) {
            // Silencieux
        }

        // Prérequis de niveau de caserne par palier (Tier)
        // Tier 1: Caserne niv 1 | Tier 2: Caserne niv 3 | Tier 3: Caserne niv 5 | Tier 4: Caserne niv 8
        $tierReqs = [1 => 1, 2 => 3, 3 => 5, 4 => 8];
        $tierFlour = [1 => 0, 2 => 15, 3 => 35, 4 => 75];

        $filteredUnits = [];
        $excludedCodes = ['sanglier_sauvage', 'loup_honshu', 'ours_hokkaido', 'colonizer'];

        foreach ($units as &$u) {
            if (in_array($u['code'], $excludedCodes)) {
                continue; // Les animaux ne sont plus entraînables (capturés via cages) et les colons se forment au Tenshu
            }
            $requiredLvl = $tierReqs[$u['tier']] ?? 1;
            $u['required_barracks_level'] = $requiredLvl;
            $u['can_train'] = ($barracksLvl >= $requiredLvl);

            $flourCost = isset($u['rice_flour_cost']) ? (int)$u['rice_flour_cost'] : 0;
            if ($flourCost === 0 && ($tierFlour[$u['tier']] ?? 0) > 0 && ($u['faction'] ?? 'all') !== 'all') {
                $flourCost = $tierFlour[$u['tier']];
            }
            $u['rice_flour_cost'] = $flourCost;

            $effectiveTime = max(5, (int)(($u['base_train_time'] / (1 + ($barracksLvl * 0.15))) * $vorashBonus * $feastSpeedBonus / $speed));
            $u['effective_train_time'] = $effectiveTime;
            $filteredUnits[] = $u;
        }

        return $filteredUnits;
    }

    /**
     * Récupère la file active d'entraînement de la caserne
     */
    public function getQueue(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT bq.*, u.name as unit_name, u.icon as unit_icon 
            FROM barracks_queue bq 
            JOIN units u ON bq.unit_code = u.code 
            WHERE bq.planet_id = ? 
            ORDER BY bq.finishes_at ASC
        ");
        $stmt->execute([$planetId]);
        return $stmt->fetchAll();
    }

    /**
     * Lance l'entraînement d'un régiment de soldats
     */
    public function trainUnits(int $planetId, string $unitCode, int $count, string $faction): array {
        if ($count <= 0) {
            throw new Exception("Effectif de recrues invalide.");
        }

        if (in_array($unitCode, ['sanglier_sauvage', 'loup_honshu', 'ours_hokkaido'])) {
            throw new Exception("Les animaux sauvages ne peuvent plus être formés au Dojo. Utilisez les Cages du Héros pour les capturer lors de vos expéditions !");
        }
        if ($unitCode === 'colonizer') {
            throw new Exception("Les Colons Pionniers doivent être formés au Tenshu (Palais Castral).");
        }

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $barracksLvl = $buildings['barracks'] ?? 0;

        $stmt = $this->db->prepare("SELECT * FROM units WHERE code = ? AND (faction = 'all' OR faction = ?)");
        $stmt->execute([$unitCode, $faction]);
        $unit = $stmt->fetch();
        if (!$unit) {
            throw new Exception("Type de soldat non disponible pour votre civilisation.");
        }

        $tierReqs = [1 => 1, 2 => 3, 3 => 5, 4 => 8];
        $reqLvl = $tierReqs[$unit['tier']] ?? 1;
        if ($barracksLvl < $reqLvl) {
            throw new Exception("Caserne niveau $reqLvl requise pour recruter cette unité.");
        }

        $tierFlour = [1 => 0, 2 => 15, 3 => 35, 4 => 75];
        $flourPerUnit = isset($unit['rice_flour_cost']) ? (int)$unit['rice_flour_cost'] : 0;
        if ($flourPerUnit === 0 && ($tierFlour[$unit['tier']] ?? 0) > 0 && ($unit['faction'] ?? 'all') !== 'all') {
            $flourPerUnit = $tierFlour[$unit['tier']];
        }

        $totalMetal = $unit['metal_cost'] * $count;
        $totalCrystal = $unit['crystal_cost'] * $count;
        $totalDeut = $unit['deuterium_cost'] * $count;
        $totalFlour = $flourPerUnit * $count;

        if ($planet['metal'] < $totalMetal || $planet['crystal'] < $totalCrystal || $planet['deuterium'] < $totalDeut) {
            throw new Exception("Ressources insuffisantes pour équiper et entraîner cette troupe.");
        }

        if ($totalFlour > 0 && ($planet['rice_flour'] ?? 0) < $totalFlour) {
            throw new Exception("Stock de Farine de Riz insuffisant (" . number_format((int)($planet['rice_flour'] ?? 0)) . " disponible, " . number_format($totalFlour) . " requis pour les rations d'élite).");
        }

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0;

        // Bonus du Banquet des Guerriers
        $feastSpeedBonus = 1.0;
        try {
            $activeFeast = $this->planetEngine->getActiveFeast($planetId);
            if ($activeFeast && $activeFeast['feast_type'] === 'warriors') {
                $tLvl = (int)($activeFeast['tenshu_level'] ?? 1);
                $reduction = 0.10 + ($tLvl * 0.01);
                $feastSpeedBonus = 1.0 - min(0.35, $reduction);
            }
        } catch (Exception $e) {
            // Silencieux
        }

        $unitTime = max(5, (int)(($unit['base_train_time'] / (1 + ($barracksLvl * 0.15))) * $vorashBonus * $feastSpeedBonus / $speed));
        $totalTime = $unitTime * $count;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ?, rice_flour = GREATEST(0, rice_flour - ?) 
                WHERE id = ?
            ")->execute([$totalMetal, $totalCrystal, $totalDeut, $totalFlour, $planetId]);
        } catch (Exception $e) {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
                WHERE id = ?
            ")->execute([$totalMetal, $totalCrystal, $totalDeut, $planetId]);
        }

        $now = time();
        $finishesAt = $now + $totalTime;

        $this->db->prepare("
            INSERT INTO barracks_queue 
            (planet_id, unit_code, count, started_at, finishes_at, unit_train_time) 
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$planetId, $unitCode, $count, $now, $finishesAt, $unitTime]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => "Entraînement de $count {$unit['name']} lancé dans la caserne !",
            'finishes_at' => $finishesAt
        ];
    }

    /**
     * Résolution des régiments dont l'entraînement est terminé
     */
    public function processQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM barracks_queue 
            WHERE planet_id = ? AND finishes_at <= ?
        ");
        $stmt->execute([$planetId, $now]);
        $completed = $stmt->fetchAll();

        foreach ($completed as $item) {
            $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE count = count + ?
            ")->execute([$planetId, $item['unit_code'], $item['count'], $item['count']]);

            $this->db->prepare("DELETE FROM barracks_queue WHERE id = ?")->execute([$item['id']]);
        }
    }
}

