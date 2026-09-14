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
        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0; // Vorash produisent 20% plus vite

        foreach ($ships as &$ship) {
            $ship['can_build'] = ($shipyardLvl >= 1);
            $effectiveTime = max(2, (int)(($ship['base_build_time'] / (1 + ($shipyardLvl * 0.4))) * $vorashBonus / $speed));
            $ship['effective_build_time'] = $effectiveTime;
        }

        return $ships;
    }

    /**
     * Récupère la file active du chantier spatial
     */
    public function getQueue(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT sq.*, s.name as ship_name 
            FROM shipyard_queue sq 
            JOIN ships s ON sq.ship_code = s.code 
            WHERE sq.planet_id = ? 
            ORDER BY sq.finishes_at ASC
        ");
        $stmt->execute([$planetId]);
        return $stmt->fetchAll();
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

        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        $vorashBonus = ($faction === 'vorash') ? 0.8 : 1.0;
        $unitTime = max(2, (int)(($ship['base_build_time'] / (1 + ($shipyardLvl * 0.4))) * $vorashBonus / $speed));
        $totalTime = $unitTime * $count;

        // Déduire les ressources
        $this->db->beginTransaction();
        $this->db->prepare("
            UPDATE planets 
            SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
            WHERE id = ?
        ")->execute([$totalMetal, $totalCrystal, $totalDeut, $planetId]);

        $now = time();
        $finishesAt = $now + $totalTime;

        $this->db->prepare("
            INSERT INTO shipyard_queue 
            (planet_id, ship_code, count, started_at, finishes_at, unit_build_time) 
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$planetId, $shipCode, $count, $now, $finishesAt, $unitTime]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => "Commande de $count {$ship['name']} lancée au chantier !",
            'finishes_at' => $finishesAt
        ];
    }
}

