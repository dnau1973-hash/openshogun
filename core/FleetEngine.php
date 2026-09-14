<?php
/**
 * Moteur de Déplacement des Flottes et Résolution des Missions Spatiales
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/CombatEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class FleetEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;
    private CombatEngine $combatEngine;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
        $this->combatEngine = new CombatEngine();
    }

    /**
     * Traite et résout toutes les missions de flottes arrivées à destination ou revenues à la base
     */
    public function processFleetMissions(): void {
        $now = time();

        // 1. Traiter les flottes arrivées à destination (en_route -> resolving)
        $stmtArrival = $this->db->prepare("
            SELECT * FROM fleet_missions 
            WHERE status = 'en_route' AND arrival_time <= ? 
            ORDER BY arrival_time ASC
        ");
        $stmtArrival->execute([$now]);
        $arrivingMissions = $stmtArrival->fetchAll();

        foreach ($arrivingMissions as $m) {
            $this->resolveArrival($m);
        }

        // 2. Traiter les flottes revenues à la base (returning -> completed)
        $stmtReturn = $this->db->prepare("
            SELECT * FROM fleet_missions 
            WHERE status = 'returning' AND return_time <= ? 
            ORDER BY return_time ASC
        ");
        $stmtReturn->execute([$now]);
        $returningMissions = $stmtReturn->fetchAll();

        foreach ($returningMissions as $m) {
            $this->resolveReturn($m);
        }
    }

    /**
     * Résolution de l'arrivée sur l'objectif
     */
    private function resolveArrival(array $mission): void {
        $sourcePlanetId = (int)$mission['source_planet_id'];
        $targetPlanetId = (int)$mission['target_planet_id'];
        $missionId = (int)$mission['id'];

        $stmtTarget = $this->db->prepare("SELECT * FROM planets WHERE id = ?");
        $stmtTarget->execute([$targetPlanetId]);
        $targetPlanet = $stmtTarget->fetch();
        if (!$targetPlanet) {
            // Cible disparue, retour direct
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        $stmtAttacker = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmtAttacker->execute([$mission['user_id']]);
        $attackerUser = $stmtAttacker->fetch();

        $defenderUser = null;
        if ($targetPlanet['user_id']) {
            $stmtDef = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmtDef->execute([$targetPlanet['user_id']]);
            $defenderUser = $stmtDef->fetch() ?: null;
        }

        $missionType = $mission['mission_type'];

        if ($missionType === 'attack' || $missionType === 'raid') {
            $combatResult = $this->combatEngine->resolveBattle($mission, $targetPlanet, $attackerUser, $defenderUser);
            $survivors = $combatResult['survivors'];
            $looted = $combatResult['looted'];

            if (array_sum($survivors) > 0) {
                // Les survivants retournent avec le butin
                $this->db->prepare("
                    UPDATE fleet_missions 
                    SET status = 'returning', fleet_data = ?, cargo_data = ? 
                    WHERE id = ?
                ")->execute([
                    json_encode($survivors),
                    json_encode($looted),
                    $missionId
                ]);
            } else {
                // Flotte entièrement anéantie
                $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
            }
        } elseif ($missionType === 'spy') {
            // Rapport d'espionnage
            $this->generateSpyReport($mission, $targetPlanet, $attackerUser, $defenderUser);
            // La sonde retourne
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
        } elseif ($missionType === 'transport') {
            // Décharger les ressources sur la cible
            $cargo = json_decode($mission['cargo_data'], true) ?: ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
            $this->planetEngine->updatePlanet($targetPlanetId);
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                WHERE id = ?
            ")->execute([$cargo['metal'], $cargo['crystal'], $cargo['deuterium'], $targetPlanetId]);

            // Flotte repart à vide
            $this->db->prepare("
                UPDATE fleet_missions 
                SET status = 'returning', cargo_data = JSON_OBJECT('metal', 0, 'crystal', 0, 'deuterium', 0) 
                WHERE id = ?
            ")->execute([$missionId]);
        } elseif ($missionType === 'colonize') {
            // Colonisation si la planète est libre
            if ($targetPlanet['user_id'] === null) {
                $this->colonizePlanet($targetPlanetId, (int)$attackerUser['id'], $attackerUser['username']);
                // Le vaisseau colonial est consommé, les escortes rentrent
                $fleet = json_decode($mission['fleet_data'], true) ?: [];
                unset($fleet['colony_ship']);
                if (array_sum($fleet) > 0) {
                    $this->db->prepare("UPDATE fleet_missions SET status = 'returning', fleet_data = ? WHERE id = ?")
                        ->execute([json_encode($fleet), $missionId]);
                } else {
                    $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
                }
            } else {
                // Déjà occupée, retour
                $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            }
        }
    }

    /**
     * Résolution du retour de la flotte sur sa base
     */
    private function resolveReturn(array $mission): void {
        $sourcePlanetId = (int)$mission['source_planet_id'];
        $fleet = json_decode($mission['fleet_data'], true) ?: [];
        $cargo = json_decode($mission['cargo_data'], true) ?: [];

        $this->db->beginTransaction();

        // Réintégrer les vaisseaux et soldats sur la planète mère
        $stmtCheckUnit = $this->db->prepare("SELECT code FROM units WHERE code = ?");
        foreach ($fleet as $code => $count) {
            if ($count <= 0) continue;
            $stmtCheckUnit->execute([$code]);
            if ($stmtCheckUnit->fetch()) {
                $this->db->prepare("
                    INSERT INTO planet_units (planet_id, unit_code, count) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE count = count + ?
                ")->execute([$sourcePlanetId, $code, $count, $count]);
            } else {
                $this->db->prepare("
                    INSERT INTO planet_ships (planet_id, ship_code, count) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE count = count + ?
                ")->execute([$sourcePlanetId, $code, $count, $count]);
            }
        }

        // Décharger le butin / cargaison
        $metal = $cargo['metal'] ?? 0;
        $crystal = $cargo['crystal'] ?? 0;
        $deut = $cargo['deuterium'] ?? 0;
        if ($metal > 0 || $crystal > 0 || $deut > 0) {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                WHERE id = ?
            ")->execute([$metal, $crystal, $deut, $sourcePlanetId]);
        }

        // Marquer la mission terminée
        $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$mission['id']]);

        $this->db->commit();
    }

    /**
     * Génère un rapport d'espionnage détaillé
     */
    private function generateSpyReport(array $mission, array $targetPlanet, array $attackerUser, ?array $defenderUser): void {
        $targetPlanetId = (int)$targetPlanet['id'];
        $fields = $this->planetEngine->getFields($targetPlanetId);
        $buildings = $this->planetEngine->getBuildings($targetPlanetId);

        $stmtShips = $this->db->prepare("SELECT ship_code, count FROM planet_ships WHERE planet_id = ? AND count > 0");
        $stmtShips->execute([$targetPlanetId]);
        $ships = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);

        $reportData = [
            'type' => 'spy',
            'planet_name' => $targetPlanet['name'],
            'coords' => "({$targetPlanet['coord_x']}, {$targetPlanet['coord_y']})",
            'resources' => [
                'metal' => (int)$targetPlanet['metal'],
                'crystal' => (int)$targetPlanet['crystal'],
                'deuterium' => (int)$targetPlanet['deuterium'],
            ],
            'fleet' => $ships,
            'buildings' => $buildings
        ];

        $title = "Chronique d'infiltration Shinobi sur {$targetPlanet['name']} ({$targetPlanet['coord_x']}, {$targetPlanet['coord_y']})";
        $stmt = $this->db->prepare("
            INSERT INTO combat_reports 
            (attacker_id, defender_id, attacker_planet_id, defender_planet_id, mission_type, title, report_data, winner, created_at) 
            VALUES (?, ?, ?, ?, 'spy', ?, ?, 'attacker', UNIX_TIMESTAMP())
        ");
        $stmt->execute([
            $attackerUser['id'],
            $defenderUser['id'] ?? 0,
            $mission['source_planet_id'],
            $targetPlanetId,
            $title,
            json_encode($reportData, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Établissement d'un nouveau fief sur une terre libre
     */
    private function colonizePlanet(int $planetId, int $userId, string $username): void {
        $this->db->prepare("
            UPDATE planets 
            SET user_id = ?, name = ?, is_capital = 0, last_resource_update = UNIX_TIMESTAMP() 
            WHERE id = ?
        ")->execute([$userId, "Fief " . ucfirst($username), $planetId]);

        // Initialiser ou activer les parcelles de ressources du domaine (Style Travian)
        $countFields = (int)$this->db->query("SELECT COUNT(*) FROM planet_fields WHERE planet_id = {$planetId}")->fetchColumn();
        if ($countFields < 18) {
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, null, 1, false);
        } else {
            $this->db->prepare("UPDATE planet_fields SET level = 1 WHERE planet_id = ? AND level = 0")->execute([$planetId]);
        }

        // Bâtiments de base
        $this->db->prepare("INSERT INTO planet_buildings (planet_id, building_type, level) VALUES (?, 'hq', 1)")
            ->execute([$planetId]);
    }

    /**
     * Calcule la distance euclidienne entre 2 coordonnées
     */
    public static function calculateDistance(int $x1, int $y1, int $x2, int $y2): float {
        return max(1.0, round(sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2)), 2));
    }

    /**
     * Calcule la durée de trajet selon la flotte, la distance et les bonus
     */
    public function calculateFlightDuration(array $fleet, float $distance, string $faction): int {
        $stmt = $this->db->query("SELECT code, speed FROM ships");
        $speeds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $stmtU = $this->db->query("SELECT code, speed FROM units");
        $speeds = array_merge($speeds, $stmtU->fetchAll(PDO::FETCH_KEY_PAIR));

        $minSpeed = 999999;
        foreach ($fleet as $code => $count) {
            if ($count > 0 && isset($speeds[$code])) {
                $minSpeed = min($minSpeed, (int)$speeds[$code]);
            }
        }
        if ($minSpeed === 999999) $minSpeed = 5000;

        // Bonus Aethelis : +20% vitesse de flotte
        if ($faction === 'aethelis') {
            $minSpeed = (int)($minSpeed * 1.20);
        }

        $gameSpeed = max(1, (float)GameConfig::get('fleet_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        return max(3, (int)round((3500 * $distance) / ($minSpeed * $gameSpeed)));
    }

    /**
     * Envoie une mission spatiale
     */
    public function dispatchMission(int $userId, int $sourcePlanetId, int $targetPlanetId, string $missionType, array $fleet, array $cargo): array {
        $this->planetEngine->updatePlanet($sourcePlanetId);

        // 1. Vérifier la possession de la planète source
        $stmtSource = $this->db->prepare("SELECT p.*, u.faction FROM planets p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.user_id = ?");
        $stmtSource->execute([$sourcePlanetId, $userId]);
        $sourcePlanet = $stmtSource->fetch();
        if (!$sourcePlanet) throw new Exception("Fief source invalide.");

        // 2. Vérifier la planète cible
        $stmtTarget = $this->db->prepare("SELECT * FROM planets WHERE id = ?");
        $stmtTarget->execute([$targetPlanetId]);
        $targetPlanet = $stmtTarget->fetch();
        if (!$targetPlanet) throw new Exception("Coordonnées de destination introuvables.");

        if ($sourcePlanetId === $targetPlanetId) {
            throw new Exception("Vous ne pouvez pas envoyer une expédition sur votre propre fief.");
        }

        // 3. Vérifier les vaisseaux et soldats disponibles
        $stmtShips = $this->db->prepare("SELECT ship_code, count FROM planet_ships WHERE planet_id = ?");
        $stmtShips->execute([$sourcePlanetId]);
        $availableShips = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtUnits = $this->db->prepare("SELECT unit_code, count FROM planet_units WHERE planet_id = ?");
        $stmtUnits->execute([$sourcePlanetId]);
        $availableUnits = $stmtUnits->fetchAll(PDO::FETCH_KEY_PAIR);

        $availableForces = array_merge($availableShips, $availableUnits);

        $totalShips = 0;
        $cleanFleet = [];
        foreach ($fleet as $code => $count) {
            $cnt = (int)$count;
            if ($cnt <= 0) continue;
            if (($availableForces[$code] ?? 0) < $cnt) {
                throw new Exception("Effectif insuffisant pour l'unité $code.");
            }
            $cleanFleet[$code] = $cnt;
            $totalShips += $cnt;
        }

        if ($totalShips === 0) {
            throw new Exception("Veuillez sélectionner au moins un régiment ou un engin de siège.");
        }

        // 4. Calcul de distance et durée
        $distance = self::calculateDistance($sourcePlanet['coord_x'], $sourcePlanet['coord_y'], $targetPlanet['coord_x'], $targetPlanet['coord_y']);
        $duration = $this->calculateFlightDuration($cleanFleet, $distance, $sourcePlanet['faction']);

        // 5. Calcul des rations de riz (koku) requises pour la marche
        $fuelReq = max(5, (int)round($distance * $totalShips * 1.2));
        $cargoMetal = max(0, (int)($cargo['metal'] ?? 0));
        $cargoCrystal = max(0, (int)($cargo['crystal'] ?? 0));
        $cargoDeut = max(0, (int)($cargo['deuterium'] ?? 0));

        $totalDeutNeeded = $fuelReq + $cargoDeut;

        if ($sourcePlanet['metal'] < $cargoMetal || $sourcePlanet['crystal'] < $cargoCrystal || $sourcePlanet['deuterium'] < $totalDeutNeeded) {
            throw new Exception("Ressources ou rations de riz insuffisantes (Ravitaillement de marche requis : $fuelReq Koku de Riz).");
        }

        // 6. Déduire les unités et les ressources
        $this->db->beginTransaction();

        foreach ($cleanFleet as $code => $cnt) {
            if (isset($availableUnits[$code])) {
                $this->db->prepare("UPDATE planet_units SET count = count - ? WHERE planet_id = ? AND unit_code = ?")
                    ->execute([$cnt, $sourcePlanetId, $code]);
            } else {
                $this->db->prepare("UPDATE planet_ships SET count = count - ? WHERE planet_id = ? AND ship_code = ?")
                    ->execute([$cnt, $sourcePlanetId, $code]);
            }
        }

        $this->db->prepare("
            UPDATE planets 
            SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
            WHERE id = ?
        ")->execute([$cargoMetal, $cargoCrystal, $totalDeutNeeded, $sourcePlanetId]);

        $now = time();
        $arrivalTime = $now + $duration;
        $returnTime = $arrivalTime + $duration;

        $stmtInsert = $this->db->prepare("
            INSERT INTO fleet_missions 
            (user_id, source_planet_id, target_planet_id, mission_type, fleet_data, cargo_data, departure_time, arrival_time, return_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_route')
        ");
        $stmtInsert->execute([
            $userId,
            $sourcePlanetId,
            $targetPlanetId,
            $missionType,
            json_encode($cleanFleet),
            json_encode(['metal' => $cargoMetal, 'crystal' => $cargoCrystal, 'deuterium' => $cargoDeut]),
            $now,
            $arrivalTime,
            $returnTime
        ]);

        $this->db->commit();

        return [
            'success' => true,
            'message' => 'Expédition féodale déployée avec succès vers ' . $targetPlanet['name'] . ' !',
            'duration' => $duration,
            'arrival_time' => $arrivalTime
        ];
    }
}

