<?php
/**
 * Moteur du Chantier Spatial et Production de Flotte
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class ShipyardEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    /**
     * Récupère la liste des vaisseaux constructibles par le joueur
     */
    public function getAvailableShips(int $planetId, string $faction): array {
        $buildings = $this->planetEngine->getBuildings($planetId);
        $shipyardLvl = $buildings['shipyard'] ?? 0;

        $stmt = $this->db->prepare("
            SELECT s.*, COALESCE(ps.count, 0) as stationed_count
            FROM ships s
            LEFT JOIN planet_ships ps ON ps.ship_code = s.code AND ps.planet_id = ?
            WHERE s.faction = 'all' OR s.faction = ?
            ORDER BY s.metal_cost ASC
        ");
        $stmt->execute([$planetId, $faction]);
        $ships = $stmt->fetchAll();

        // Calculer le temps de construction réel avec les bonus
        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0; // Vorash produisent 20% plus vite

        // Bonus du Banquet des Guerriers (Tenshu)
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

        foreach ($ships as &$ship) {
            $ship['can_build'] = ($shipyardLvl >= 1);
            $effectiveTime = max(10, (int)(($ship['base_build_time'] / (1 + ($shipyardLvl * 0.15))) * $vorashBonus * $feastSpeedBonus / $speed));
            $ship['effective_build_time'] = $effectiveTime;
        }

        return $ships;
    }

    /**
     * Récupère la file active du chantier spatial (avec métriques au fil de l'eau)
     */
    public function getQueue(int $planetId): array {
        // Traiter d'abord au fil de l'eau
        $this->planetEngine->processShipyardQueue($planetId);

        $stmt = $this->db->prepare("
            SELECT sq.*, s.name as ship_name, s.image as ship_image
            FROM shipyard_queue sq
            LEFT JOIN ships s ON sq.ship_code = s.code
            WHERE sq.planet_id = ?
            ORDER BY sq.started_at ASC, sq.id ASC
        ");
        $stmt->execute([$planetId]);
        $rows = $stmt->fetchAll();

        $now = time();
        foreach ($rows as &$r) {
            $totalCount = !empty($r['total_count']) ? (int)$r['total_count'] : (int)$r['count'];
            $r['total_count'] = $totalCount;
            $remainingCount = (int)$r['count'];
            $completedCount = max(0, $totalCount - $remainingCount);
            $r['completed_count'] = $completedCount;
            $unitTime = max(1, (int)$r['unit_build_time']);
            $startedAt = (int)$r['started_at'];
            $finishesAt = (int)$r['finishes_at'];

            $isStarted = ($now >= $startedAt);
            $r['is_active'] = $isStarted;

            if ($isStarted) {
                $unitElapsed = min($unitTime, max(0, $now - $startedAt));
                $unitRemaining = max(0, $unitTime - $unitElapsed);
                $unitPct = min(100.0, max(0.0, ($unitElapsed / $unitTime) * 100.0));
                $currentUnitNum = min($totalCount, $completedCount + 1);
                $lotPct = min(100.0, max(0.0, (($completedCount + ($unitElapsed / $unitTime)) / $totalCount) * 100.0));
            } else {
                $unitElapsed = 0;
                $unitRemaining = $unitTime;
                $unitPct = 0.0;
                $currentUnitNum = $completedCount + 1;
                $lotPct = 0.0;
            }

            $r['unit_elapsed'] = $unitElapsed;
            $r['unit_remaining'] = $unitRemaining;
            $r['unit_pct'] = round($unitPct, 1);
            $r['current_unit_number'] = $currentUnitNum;
            $r['lot_pct'] = round($lotPct, 1);
            $r['lot_remaining_time'] = max(0, $finishesAt - $now);
            $r['ship_name'] = $r['ship_name'] ?? $r['ship_code'];
        }

        return $rows;
    }

    /**
     * Lance la construction d'une quantité de vaisseaux
     */
    public function buildShips(int $planetId, string $shipCode, int $count, string $faction): array {
        if ($count <= 0) {
            throw new Exception("Quantité de vaisseaux invalide.");
        }

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $shipyardLvl = $buildings['shipyard'] ?? 0;

        if ($shipyardLvl < 1) {
            throw new Exception("Vous devez construire un Atelier de Siège & Écuries pour mobiliser ces unités.");
        }

        $stmt = $this->db->prepare("SELECT * FROM ships WHERE code = ? AND (faction = 'all' OR faction = ?)");
        $stmt->execute([$shipCode, $faction]);
        $ship = $stmt->fetch();
        if (!$ship) {
            throw new Exception("Unité ou engin de siège non disponible.");
        }

        $totalMetal = $ship['metal_cost'] * $count;
        $totalCrystal = $ship['crystal_cost'] * $count;
        $totalDeut = $ship['deuterium_cost'] * $count;

        if ($planet['metal'] < $totalMetal || $planet['crystal'] < $totalCrystal || $planet['deuterium'] < $totalDeut) {
            throw new Exception("Ressources insuffisantes pour cette commande.");
        }

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0;

        // Bonus du Banquet des Guerriers (Tenshu)
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

        $unitTime = max(10, (int)(($ship['base_build_time'] / (1 + ($shipyardLvl * 0.15))) * $vorashBonus * $feastSpeedBonus / $speed));
        $totalTime = $unitTime * $count;

        // Déduire les ressources
        $this->db->beginTransaction();
        $this->db->prepare("
            UPDATE planets
            SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ?
            WHERE id = ?
        ")->execute([$totalMetal, $totalCrystal, $totalDeut, $planetId]);

        $now = time();
        $stmtLast = $this->db->prepare("SELECT MAX(finishes_at) FROM shipyard_queue WHERE planet_id = ?");
        $stmtLast->execute([$planetId]);
        $lastFinish = (int)$stmtLast->fetchColumn();
        $startTime = max($now, $lastFinish);
        $finishesAt = $startTime + $totalTime;

        $this->db->prepare("
            INSERT INTO shipyard_queue
            (planet_id, ship_code, count, total_count, started_at, finishes_at, unit_build_time)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$planetId, $shipCode, $count, $count, $startTime, $finishesAt, $unitTime]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => "Commande de $count {$ship['name']} lancée au chantier !",
            'finishes_at' => $finishesAt
        ];
    }

    /**
     * Résolution progressive ("au fil de l'eau") du chantier spatial / engins de siège
     */
    public function processQueue(int $planetId): void {
        $this->planetEngine->processShipyardQueue($planetId);
    }
}

