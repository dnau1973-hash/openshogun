<?php
/**
 * Moteur de Déplacement des Flottes et Résolution des Missions Spatiales
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageFieldGenerator.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/CombatEngine.php';
require_once __DIR__ . '/Auth.php';
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

        // 3. Régénérer périodiquement les ressources des oasis sauvages
        try {
            require_once __DIR__ . '/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $oasisEngine->regenerateOases();
        } catch (Exception $e) {
            // Ignorer silencieusement
        }
    }

    /**
     * Résolution de l'arrivée sur l'objectif
     */
    private function resolveArrival(array $mission): void {
        $sourcePlanetId = (int)$mission['source_planet_id'];
        $targetPlanetId = !empty($mission['target_planet_id']) ? (int)$mission['target_planet_id'] : null;
        $targetOasisId = !empty($mission['target_oasis_id']) ? (int)$mission['target_oasis_id'] : null;
        $missionId = (int)$mission['id'];

        // Cas d'une aventure féodale du Samouraï Héros
        if ($mission['mission_type'] === 'adventure') {
            require_once __DIR__ . '/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $heroEngine->resolveAdventureArrival($mission);
            return;
        }

        // Cas d'une expédition vers une oasis naturelle ou sauvage
        if ($targetOasisId) {
            require_once __DIR__ . '/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $targetOasis = $oasisEngine->getOasisById($targetOasisId);
            if (!$targetOasis) {
                $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
                return;
            }

            $stmtAttacker = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmtAttacker->execute([$mission['user_id']]);
            $attackerUser = $stmtAttacker->fetch();

            $combatResult = $this->combatEngine->resolveOasisBattle($mission, $targetOasis, $attackerUser);
            $survivors = $combatResult['survivors'];
            $looted = $combatResult['looted'];
            $isStationed = !empty($combatResult['stationed']);

            $heroAlive = false;
            if (!empty($mission['has_hero'])) {
                require_once __DIR__ . '/HeroEngine.php';
                $heroEngine = new HeroEngine();
                $hero = $heroEngine->getHeroByUserId((int)$mission['user_id']);
                if ($hero && $hero['status'] !== 'dead' && (float)$hero['health'] > 0) {
                    $heroAlive = true;
                }
            }

            if ($isStationed) {
                // Guerriers stationnés dans l'oasis pacifiée pour son annexion
                if (!empty($mission['has_hero'])) {
                    $this->db->prepare("
                        UPDATE heroes 
                        SET status = 'home', current_planet_id = ?, last_health_update = UNIX_TIMESTAMP() 
                        WHERE user_id = ? AND status != 'dead'
                    ")->execute([$mission['source_planet_id'], $mission['user_id']]);
                }
                $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
            } elseif (array_sum($survivors) > 0 || $heroAlive) {
                // Les survivants (troupes ou héros vivant) retournent à la base avec le butin pillé
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
                // Garnison de raid entièrement anéantie par les bêtes sauvages
                if (!empty($mission['has_hero'])) {
                    $this->db->prepare("UPDATE heroes SET status = 'dead', health = 0.0 WHERE user_id = ? AND status != 'dead'")->execute([$mission['user_id']]);
                }
                $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
            }
            return;
        }

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
        $hostileMissions = ['attack', 'raid', 'spy', 'occupy'];

        // Protection Débutant : Si le fief défenseur est sous immunité féodale et que la mission est hostile, repli pacifique immédiat
        if ($targetPlanet['user_id'] && in_array($missionType, $hostileMissions) && Auth::isUserProtected((int)$targetPlanet['user_id'])) {
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        if ($missionType === 'attack' || $missionType === 'raid') {
            $combatResult = $this->combatEngine->resolveBattle($mission, $targetPlanet, $attackerUser, $defenderUser);
            $survivors = $combatResult['survivors'];
            $looted = $combatResult['looted'];

            $heroAlive = false;
            if (!empty($mission['has_hero'])) {
                require_once __DIR__ . '/HeroEngine.php';
                $heroEngine = new HeroEngine();
                $hero = $heroEngine->getHeroByUserId((int)$mission['user_id']);
                if ($hero && $hero['status'] !== 'dead' && (float)$hero['health'] > 0) {
                    $heroAlive = true;
                }
            }

            if (array_sum($survivors) > 0 || $heroAlive) {
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
                if (!empty($mission['has_hero'])) {
                    $this->db->prepare("UPDATE heroes SET status = 'dead', health = 0.0 WHERE user_id = ? AND status != 'dead'")->execute([$mission['user_id']]);
                }
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
    public function resolveReturn(array $mission): void {
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

        // Réintégrer le Samouraï Héros au domaine s'il a participé
        if (!empty($mission['has_hero'])) {
            $this->db->prepare("
                UPDATE heroes 
                SET status = 'home', current_planet_id = ?, last_health_update = UNIX_TIMESTAMP() 
                WHERE user_id = ? AND status != 'dead'
            ")->execute([$sourcePlanetId, $mission['user_id']]);
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

        // Initialiser les parcelles de ressources du domaine (Style Travian, niveau 0 = libre)
        $countFields = (int)$this->db->query("SELECT COUNT(*) FROM planet_fields WHERE planet_id = {$planetId}")->fetchColumn();
        if ($countFields < 18) {
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, null, 0, false);
        }

        // Aucun bâtiment pré-placé dans les slots (slots 100% libres pour le joueur)
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
    public function calculateFlightDuration(array $fleet, float $distance, string $faction, ?int $sourcePlanetId = null): int {
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

        // Bonus Place d'Exercices & Relais (tournament_square) : Style Travian
        // +10% de vitesse de déplacement par niveau lors des marches à longue distance (> 20 provinces)
        if ($sourcePlanetId !== null && $sourcePlanetId > 0 && $distance >= 20.0) {
            try {
                $buildings = $this->planetEngine->getBuildings($sourcePlanetId);
                $tsLvl = (int)($buildings['tournament_square'] ?? 0);
                if ($tsLvl > 0) {
                    $minSpeed = (int)round($minSpeed * (1.0 + ($tsLvl * 0.10)));
                }
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        $gameSpeed = max(1, (float)GameConfig::get('fleet_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        return max(3, (int)round((3500 * $distance) / ($minSpeed * $gameSpeed)));
    }

    /**
     * Envoie une mission spatiale ou expédition féodale
     */
    /**
     * Envoie une mission spatiale ou expédition féodale
     */
    public function dispatchMission(
        int $userId, 
        int $sourcePlanetId, 
        ?int $targetPlanetId, 
        string $missionType, 
        array $fleet, 
        array $cargo, 
        ?int $targetOasisId = null,
        bool $hasHero = false
    ): array {
        $this->planetEngine->updatePlanet($sourcePlanetId);

        // 1. Vérifier la possession de la planète source
        $stmtSource = $this->db->prepare("SELECT p.*, u.faction FROM planets p JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.user_id = ?");
        $stmtSource->execute([$sourcePlanetId, $userId]);
        $sourcePlanet = $stmtSource->fetch();
        if (!$sourcePlanet) throw new Exception("Fief source invalide.");

        // 2. Vérifier la destination (fief ou oasis)
        $targetPlanet = null;
        $targetOasis = null;

        if ($targetOasisId) {
            require_once __DIR__ . '/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $targetOasis = $oasisEngine->getOasisById($targetOasisId);
            if (!$targetOasis) throw new Exception("Oasis de destination introuvable.");

            $destX = (int)$targetOasis['coord_x'];
            $destY = (int)$targetOasis['coord_y'];
            $destName = "l'Oasis " . $targetOasis['name'];
        } else {
            if (!$targetPlanetId) throw new Exception("Veuillez spécifier un fief ou une oasis de destination.");
            $stmtTarget = $this->db->prepare("SELECT * FROM planets WHERE id = ?");
            $stmtTarget->execute([$targetPlanetId]);
            $targetPlanet = $stmtTarget->fetch();
            if (!$targetPlanet) throw new Exception("Coordonnées de destination introuvables.");

            if ($sourcePlanetId === $targetPlanetId) {
                throw new Exception("Vous ne pouvez pas envoyer une expédition sur votre propre fief.");
            }
            $destX = (int)$targetPlanet['coord_x'];
            $destY = (int)$targetPlanet['coord_y'];
            $destName = $targetPlanet['name'];
        }

        // 2b. Vérification de l'Immunité des Nouveaux Joueurs (Protection Débutant)
        $hostileMissions = ['raid', 'attack', 'occupy', 'spy'];
        $protectionRevoked = false;

        if ($targetPlanet && !empty($targetPlanet['user_id'])) {
            $targetUserId = (int)$targetPlanet['user_id'];
            if ($targetUserId !== $userId && in_array($missionType, $hostileMissions)) {
                // Vérifier si le joueur cible bénéficie de l'immunité
                if (Auth::isUserProtected($targetUserId)) {
                    $rem = Auth::getProtectionRemaining($targetUserId);
                    $untilText = $rem['until_formatted'] ?? '7 jours';
                    $remText = !empty($rem['formatted']) ? " (encore {$rem['formatted']})" : "";
                    throw new Exception("Ce seigneur bénéficie de la protection féodale des nouveaux joueurs (immunité active jusqu'au {$untilText}{$remText}). Ce domaine ne peut être ni attaqué ni espionné.");
                }

                // Règle du Sengoku : Si l'attaquant bénéficiait lui-même de l'immunité, attaquer un autre seigneur la révoque immédiatement
                if (Auth::isUserProtected($userId)) {
                    Auth::revokeProtection($userId);
                    $protectionRevoked = true;
                }
            }
        } elseif ($targetOasis && !empty($targetOasis['owner_planet_id'])) {
            // Oasis occupée par un autre seigneur
            $stmtOasisOwner = $this->db->prepare("SELECT user_id FROM planets WHERE id = ?");
            $stmtOasisOwner->execute([$targetOasis['owner_planet_id']]);
            $oasisOwnerId = (int)$stmtOasisOwner->fetchColumn();
            if ($oasisOwnerId && $oasisOwnerId !== $userId && in_array($missionType, $hostileMissions)) {
                if (Auth::isUserProtected($oasisOwnerId)) {
                    $rem = Auth::getProtectionRemaining($oasisOwnerId);
                    $untilText = $rem['until_formatted'] ?? '7 jours';
                    throw new Exception("Cette oasis est rattachée à un seigneur sous protection des nouveaux joueurs (immunité active jusqu'au {$untilText}).");
                }
                if (Auth::isUserProtected($userId)) {
                    Auth::revokeProtection($userId);
                    $protectionRevoked = true;
                }
            }
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

        // Vérifier le héros s'il est requis
        $heroIdToDeploy = null;
        if ($hasHero) {
            require_once __DIR__ . '/HeroEngine.php';
            $heroEngine = new HeroEngine();
            $hero = $heroEngine->getHeroByUserId($userId);
            if (!$hero || (int)$hero['current_planet_id'] !== $sourcePlanetId || $hero['status'] !== 'home' || (float)$hero['health'] <= 0) {
                throw new Exception("Votre Samouraï Héros n'est pas disponible pour cette expédition (statut ou santé invalide).");
            }
            $heroIdToDeploy = (int)$hero['id'];
        }

        if ($totalShips === 0 && !$hasHero) {
            throw new Exception("Veuillez sélectionner au moins un régiment, un engin de siège ou votre Samouraï Héros.");
        }

        // 4. Calcul de distance et durée
        $distance = self::calculateDistance($sourcePlanet['coord_x'], $sourcePlanet['coord_y'], $destX, $destY);
        $duration = $this->calculateFlightDuration($cleanFleet, $distance, $sourcePlanet['faction'], $sourcePlanetId);

        // 5. Calcul des rations de riz (koku) requises pour la marche
        $effectiveMarches = max(1, $totalShips + ($hasHero ? 1 : 0));
        $fuelReq = max(5, (int)round($distance * $effectiveMarches * 1.2));
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

        if ($heroIdToDeploy) {
            $this->db->prepare("UPDATE heroes SET status = 'mission' WHERE id = ?")->execute([$heroIdToDeploy]);
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
            (user_id, source_planet_id, target_planet_id, target_oasis_id, mission_type, fleet_data, cargo_data, has_hero, departure_time, arrival_time, return_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_route')
        ");
        $stmtInsert->execute([
            $userId,
            $sourcePlanetId,
            $targetPlanetId ?: null,
            $targetOasisId ?: null,
            $missionType,
            json_encode($cleanFleet),
            json_encode(['metal' => $cargoMetal, 'crystal' => $cargoCrystal, 'deuterium' => $cargoDeut]),
            $hasHero ? 1 : 0,
            $now,
            $arrivalTime,
            $returnTime
        ]);
        $missionId = (int)$this->db->lastInsertId();

        $this->db->commit();

        $deployMsg = 'Expédition féodale déployée avec succès vers ' . $destName . ' !';
        if ($protectionRevoked) {
            $deployMsg .= ' (⚠️ Votre immunité de nouveau joueur a été levée car vous avez engagé les hostilités contre un autre seigneur).';
        }

        return [
            'success' => true,
            'message' => $deployMsg,
            'mission_id' => $missionId,
            'duration' => $duration,
            'arrival_time' => $arrivalTime,
            'protection_revoked' => $protectionRevoked
        ];
    }
}

