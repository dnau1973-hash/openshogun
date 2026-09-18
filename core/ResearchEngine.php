<?php
/**
 * Moteur des Technologies et Recherches
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class ResearchEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
    }

    public function getResearches(int $userId, int $planetId): array {
        $this->processQueue($userId);

        $buildings = $this->planetEngine->getBuildings($planetId);
        $labLvl = $buildings['research_lab'] ?? 0;

        $stmt = $this->db->prepare("
            SELECT r.*, COALESCE(ur.level, 0) as current_level 
            FROM researches r 
            LEFT JOIN user_researches ur ON ur.research_code = r.code AND ur.user_id = ? 
            ORDER BY r.base_time ASC
        ");
        $stmt->execute([$userId]);
        $researches = $stmt->fetchAll();

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));

        foreach ($researches as &$res) {
            $curLvl = (int)$res['current_level'];
            $nextLvl = $curLvl + 1;
            $mult = pow(1.8, $curLvl);
            $res['next_level'] = $nextLvl;
            $res['cost_metal'] = (int)($res['metal_cost'] * $mult);
            $res['cost_crystal'] = (int)($res['crystal_cost'] * $mult);
            $res['cost_deuterium'] = (int)($res['deuterium_cost'] * $mult);
            $res['duration'] = max(30, (int)(($res['base_time'] * pow(1.38, $curLvl) * $nextLvl) / ((1 + ($labLvl * 0.15)) * $speed)));
            $res['can_research'] = ($labLvl >= 1);
        }

        return $researches;
    }

    public function getActiveQueue(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT rq.*, r.name as research_name 
            FROM research_queue rq 
            JOIN researches r ON rq.research_code = r.code 
            WHERE rq.user_id = ? AND rq.finishes_at > UNIX_TIMESTAMP() 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function startResearch(int $userId, int $planetId, string $researchCode): array {
        $this->processQueue($userId);

        if ($this->getActiveQueue($userId)) {
            throw new Exception("Une recherche scientifique est déjà en cours.");
        }

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $labLvl = $buildings['research_lab'] ?? 0;

        if ($labLvl < 1) {
            throw new Exception("Vous devez construire un Laboratoire de Recherche.");
        }

        $stmt = $this->db->prepare("
            SELECT r.*, COALESCE(ur.level, 0) as current_level 
            FROM researches r 
            LEFT JOIN user_researches ur ON ur.research_code = r.code AND ur.user_id = ? 
            WHERE r.code = ?
        ");
        $stmt->execute([$userId, $researchCode]);
        $res = $stmt->fetch();
        if (!$res) throw new Exception("Recherche introuvable.");

        $curLvl = (int)$res['current_level'];
        $nextLvl = $curLvl + 1;
        $mult = pow(1.8, $curLvl);
        $costMetal = (int)($res['metal_cost'] * $mult);
        $costCrystal = (int)($res['crystal_cost'] * $mult);
        $costDeut = (int)($res['deuterium_cost'] * $mult);

        if ($planet['metal'] < $costMetal || $planet['crystal'] < $costCrystal || $planet['deuterium'] < $costDeut) {
            throw new Exception("Ressources insuffisantes pour cette technologie.");
        }

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        $duration = max(30, (int)(($res['base_time'] * pow(1.38, $curLvl) * $nextLvl) / ((1 + ($labLvl * 0.15)) * $speed)));

        $now = time();
        $finishesAt = $now + $duration;

        $this->db->beginTransaction();
        $this->db->prepare("
            UPDATE planets 
            SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
            WHERE id = ?
        ")->execute([$costMetal, $costCrystal, $costDeut, $planetId]);

        $this->db->prepare("
            INSERT INTO research_queue 
            (user_id, planet_id, research_code, target_level, started_at, finishes_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$userId, $planetId, $researchCode, $nextLvl, $now, $finishesAt]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => "Recherche de '{$res['name']}' lancée !",
            'finishes_at' => $finishesAt
        ];
    }

    public function processQueue(int $userId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM research_queue 
            WHERE user_id = ? AND finishes_at <= ?
        ");
        $stmt->execute([$userId, $now]);
        $completed = $stmt->fetchAll();

        foreach ($completed as $item) {
            $this->db->prepare("
                INSERT INTO user_researches (user_id, research_code, level) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE level = ?
            ")->execute([$userId, $item['research_code'], $item['target_level'], $item['target_level']]);

            // Ajouter des points au joueur
            $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?")
                ->execute([(int)$item['target_level'] * 5, $userId]);

            $this->db->prepare("DELETE FROM research_queue WHERE id = ?")->execute([$item['id']]);
        }
    }
}

