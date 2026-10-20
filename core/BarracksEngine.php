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

    private function isPlanetAdmin(int $planetId): bool {
        if (class_exists('Auth') && Auth::check() && Auth::isAdmin()) {
            return true;
        }
        try {
            $st = $this->db->prepare("SELECT u.is_admin FROM users u JOIN planets p ON p.user_id = u.id WHERE p.id = ?");
            $st->execute([$planetId]);
            $val = $st->fetchColumn();
            return ((int)$val === 1 || $val === 'admin');
        } catch (Throwable $e) {
            return false;
        }
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
        $isAdmin = $this->isPlanetAdmin($planetId);

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
        $excludedCodes = ['sanglier_sauvage', 'loup_honshu', 'ours_hokkaido'];

        foreach ($units as &$u) {
            if (in_array($u['code'], $excludedCodes)) {
                continue; // Les animaux ne sont plus entraînables (capturés via cages)
            }
            $requiredLvl = ($u['code'] === 'colonizer') ? 10 : ($tierReqs[$u['tier']] ?? 1);
            $u['required_barracks_level'] = $requiredLvl;

            if ($u['code'] === 'colonizer') {
                $colonStatus = $this->planetEngine->getTenshuColonizerStatus($planetId);
                $u['available_slots'] = (int)($colonStatus['available_slots'] ?? 0);
                $u['max_slots'] = (int)($colonStatus['max_slots'] ?? 0);
                $u['can_train'] = $isAdmin ? true : ($barracksLvl >= $requiredLvl && $u['available_slots'] > 0);
            } else {
                $u['can_train'] = $isAdmin ? true : ($barracksLvl >= $requiredLvl);
            }

            $flourCost = isset($u['rice_flour_cost']) ? (int)$u['rice_flour_cost'] : 0;
            if ($flourCost === 0 && ($tierFlour[$u['tier']] ?? 0) > 0 && ($u['faction'] ?? 'all') !== 'all') {
                $flourCost = $tierFlour[$u['tier']];
            }
            $u['rice_flour_cost'] = $flourCost;

            $effectiveTime = max(5, (int)(($u['base_train_time'] / (1 + ($barracksLvl * 0.15))) * $vorashBonus * $feastSpeedBonus / $speed));
            $u['effective_train_time'] = $effectiveTime;
            $filteredUnits[] = $u;
        }

        // Positionner le Pionnier Féodal (colonizer) en 3e position (index 2)
        $colonKey = null;
        foreach ($filteredUnits as $k => $item) {
            if ($item['code'] === 'colonizer') {
                $colonKey = $k;
                break;
            }
        }
        if ($colonKey !== null) {
            $colonItem = $filteredUnits[$colonKey];
            array_splice($filteredUnits, $colonKey, 1);
            $insertPos = min(2, count($filteredUnits));
            array_splice($filteredUnits, $insertPos, 0, [$colonItem]);
        }

        return $filteredUnits;
    }

    /**
     * Récupère la file active d'entraînement de la caserne (avec métriques au fil de l'eau)
     */
    public function getQueue(int $planetId): array {
        // Traiter d'abord au fil de l'eau pour avoir les données fraîches
        $this->planetEngine->processBarracksQueue($planetId);

        $stmt = $this->db->prepare("
            SELECT bq.*, u.name as unit_name, u.icon as unit_icon, u.image as unit_image
            FROM barracks_queue bq
            LEFT JOIN units u ON bq.unit_code = u.code
            WHERE bq.planet_id = ?
            ORDER BY bq.started_at ASC, bq.id ASC
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
            $unitTime = max(1, (int)$r['unit_train_time']);
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
            $r['unit_name'] = $r['unit_name'] ?? ($r['unit_code'] === 'colonizer' ? 'Pionnier Féodal (Colon)' : $r['unit_code']);
            $r['unit_icon'] = $r['unit_icon'] ?? ($r['unit_code'] === 'colonizer' ? '⛩️' : '🥋');
        }

        return $rows;
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
            $status = $this->planetEngine->getTenshuColonizerStatus($planetId);
            $isAdmin = $this->isPlanetAdmin($planetId);
            if (!$isAdmin && $count > $status['available_slots']) {
                throw new Exception("Emplacements d'expansion insuffisants. Vous pouvez recruter au maximum {$status['available_slots']} Pionnier(s) Féodal(s).");
            }
        }

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $barracksLvl = $buildings['barracks'] ?? 0;
        $isAdmin = $this->isPlanetAdmin($planetId);

        $stmt = $this->db->prepare("SELECT * FROM units WHERE code = ? AND (faction = 'all' OR faction = ?)");
        $stmt->execute([$unitCode, $faction]);
        $unit = $stmt->fetch();
        if (!$unit) {
            throw new Exception("Type de soldat non disponible pour votre civilisation.");
        }

        $tierReqs = [1 => 1, 2 => 3, 3 => 5, 4 => 8];
        $reqLvl = ($unitCode === 'colonizer') ? 10 : ($tierReqs[$unit['tier']] ?? 1);
        if (!$isAdmin && $barracksLvl < $reqLvl) {
            throw new Exception("Dojo & Quartier Militaire niveau $reqLvl requis pour recruter cette troupe.");
        }

        if ($isAdmin) {
            $now = time();
            $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE count = count + VALUES(count)
            ")->execute([$planetId, $unitCode, $count]);

            return [
                'success' => true,
                'message' => "⚡ [Admin] $count {$unit['name']} mobilisés instantanément sans coût !",
                'finishes_at' => $now
            ];
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
        $stmtLast = $this->db->prepare("SELECT MAX(finishes_at) FROM barracks_queue WHERE planet_id = ?");
        $stmtLast->execute([$planetId]);
        $lastFinish = (int)$stmtLast->fetchColumn();
        $startTime = max($now, $lastFinish);
        $finishesAt = $startTime + $totalTime;

        $this->db->prepare("
            INSERT INTO barracks_queue
            (planet_id, unit_code, count, total_count, started_at, finishes_at, unit_train_time)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$planetId, $unitCode, $count, $count, $startTime, $finishesAt, $unitTime]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => "Entraînement de $count {$unit['name']} lancé dans la caserne !",
            'finishes_at' => $finishesAt
        ];
    }

    /**
     * Résolution progressive ("au fil de l'eau") des régiments en cours de formation
     */
    public function processQueue(int $planetId): void {
        $this->planetEngine->processBarracksQueue($planetId);
    }
}

