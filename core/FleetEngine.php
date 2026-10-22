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

        // 4. Traiter les convois des routes commerciales automatisées du Sceau Impérial
        try {
            require_once __DIR__ . '/ImperialSealEngine.php';
            $sealEngine = new ImperialSealEngine($this->db);
            $sealEngine->processAutomatedTradeRoutes();
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

        // Cas d'une expédition de pêche féodale
        if ($mission['mission_type'] === 'fishing') {
            $this->resolveFishingArrival($mission);
            return;
        }

        // Cas d'une expédition minière féodale (montagnes)
        if ($mission['mission_type'] === 'mining') {
            $this->resolveMiningArrival($mission);
            return;
        }

        // Cas du Sanctuaire Suprême : Renfort de Garnison ou Siège Destructeur sur [0 : 0]
        if ($mission['mission_type'] === 'dojo_reinforce') {
            $this->resolveDojoReinforceArrival($mission);
            return;
        }

        if ($mission['mission_type'] === 'dojo_siege') {
            require_once __DIR__ . '/GameConfig.php';
            if (GameConfig::isImperialTruceActive()) {
                $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
                return;
            }
            $this->resolveDojoSiegeArrival($mission);
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

            // Trêve Impériale en vigueur sur oasis occupée par un autre seigneur : repli pacifique
            require_once __DIR__ . '/GameConfig.php';
            if (!empty($targetOasis['owner_planet_id']) && GameConfig::isImperialTruceActive()) {
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

        // 🕊️ Trêve Impériale en vigueur : Cessez-le-feu immédiat, repli pacifique des troupes sans combat ni pillage
        require_once __DIR__ . '/GameConfig.php';
        if (GameConfig::isImperialTruceActive() && in_array($missionType, $hostileMissions, true)) {
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        // Protection Débutant : Si le fief défenseur est sous immunité féodale et que la mission est hostile, repli pacifique immédiat
        if ($targetPlanet['user_id'] && in_array($missionType, $hostileMissions) && Auth::isUserProtected((int)$targetPlanet['user_id'])) {
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        // 🛡️ Neutralité Féodale Absolue : Si l'attaquant est un Bot PNJ et que le tempérament IA est sur Passif (ou bots inactifs),
        // aucune incursion ne peut être exécutée contre un seigneur humain : repli pacifique immédiat sans affrontement ni pillage.
        require_once __DIR__ . '/BotEngine.php';
        if (!empty($attackerUser['is_bot']) && !empty($defenderUser) && empty($defenderUser['is_bot']) && in_array($missionType, $hostileMissions, true)) {
            if (BotEngine::isPassiveMode()) {
                $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
                return;
            }
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

            // ⚔️ Réciprocité Féodale des IA : Si un joueur humain attaque un Bot PNJ, le Bot planifie une contre-attaque proportionnée
            if (!empty($defenderUser['is_bot']) && empty($attackerUser['is_bot'])) {
                try {
                    require_once __DIR__ . '/BotEngine.php';
                    $botEngine = new BotEngine();
                    $attackFleet = json_decode($mission['fleet_data'], true) ?: [];
                    $botEngine->scheduleAttackRetaliation(
                        $defenderUser,
                        $targetPlanet,
                        $attackerUser,
                        $sourcePlanetId,
                        $missionType,
                        $attackFleet,
                        $missionId
                    );
                } catch (Throwable $e) {}
            }
        } elseif ($missionType === 'spy') {
            // Rapport d'espionnage
            $this->generateSpyReport($mission, $targetPlanet, $attackerUser, $defenderUser);
            // La sonde retourne
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);

            // 🥷 Réciprocité Féodale des IA : Si un joueur humain espionne un Bot PNJ, le Bot planifie un contre-espionnage Shinobi
            if (!empty($defenderUser['is_bot']) && empty($attackerUser['is_bot'])) {
                try {
                    require_once __DIR__ . '/BotEngine.php';
                    $botEngine = new BotEngine();
                    $botEngine->scheduleSpyRetaliation(
                        $defenderUser,
                        $targetPlanet,
                        $attackerUser,
                        $sourcePlanetId,
                        $missionId
                    );
                } catch (Throwable $e) {}
            }
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
                $this->colonizePlanet($targetPlanetId, (int)$attackerUser['id'], $attackerUser['username'], $sourcePlanetId);
                // Le colon est consommé, les escortes éventuelles rentrent
                $fleet = json_decode($mission['fleet_data'], true) ?: [];
                if (isset($fleet['colonizer'])) {
                    $fleet['colonizer']--;
                    if ($fleet['colonizer'] <= 0) unset($fleet['colonizer']);
                } elseif (isset($fleet['colony_ship'])) {
                    $fleet['colony_ship']--;
                    if ($fleet['colony_ship'] <= 0) unset($fleet['colony_ship']);
                }

                // Notifier le Daimyo fondateur
                require_once __DIR__ . '/MessageEngine.php';
                $msgEngine = new MessageEngine();
                $msgEngine->sendSystemMessage(
                    (int)$attackerUser['id'],
                    "Expansion Féodale : Nouveau Fief Établi !",
                    "Félicitations noble Daimyō ! Vos Pionniers Féodaux ont établi avec succès les fondations d'un nouveau domaine à l'emplacement [{$targetPlanet['coord_x']}|{$targetPlanet['coord_y']}]. Ses 18 parcelles de ressources et ses 16 emplacements castraux sont prêts à être développés sous votre commandement."
                );

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
                try {
                    $this->db->prepare("
                        INSERT INTO planet_units (planet_id, unit_code, count) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ")->execute([$sourcePlanetId, $code, $count, $count]);
                } catch (Exception $e) {
                    $stmtExist = $this->db->prepare("SELECT id FROM planet_units WHERE planet_id = ? AND unit_code = ?");
                    $stmtExist->execute([$sourcePlanetId, $code]);
                    $rowU = $stmtExist->fetch(PDO::FETCH_ASSOC);
                    if ($rowU) {
                        $this->db->prepare("UPDATE planet_units SET count = count + ? WHERE id = ?")->execute([$count, $rowU['id']]);
                    } else {
                        $this->db->prepare("INSERT INTO planet_units (planet_id, unit_code, count) VALUES (?, ?, ?)")->execute([$sourcePlanetId, $code, $count]);
                    }
                }
            } else {
                try {
                    $this->db->prepare("
                        INSERT INTO planet_ships (planet_id, ship_code, count) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE count = count + ?
                    ")->execute([$sourcePlanetId, $code, $count, $count]);
                } catch (Exception $e) {
                    $stmtExist = $this->db->prepare("SELECT id FROM planet_ships WHERE planet_id = ? AND ship_code = ?");
                    $stmtExist->execute([$sourcePlanetId, $code]);
                    $rowS = $stmtExist->fetch(PDO::FETCH_ASSOC);
                    if ($rowS) {
                        $this->db->prepare("UPDATE planet_ships SET count = count + ? WHERE id = ?")->execute([$count, $rowS['id']]);
                    } else {
                        $this->db->prepare("INSERT INTO planet_ships (planet_id, ship_code, count) VALUES (?, ?, ?)")->execute([$sourcePlanetId, $code, $count]);
                    }
                }
            }
        }

        // Décharger le butin / cargaison
        $metal = (int)($cargo['metal'] ?? 0);
        $crystal = (int)($cargo['crystal'] ?? 0);
        $deut = (int)($cargo['deuterium'] ?? 0);
        $fish = (int)($cargo['fish'] ?? 0);
        if ($metal > 0 || $crystal > 0 || $deut > 0) {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                WHERE id = ?
            ")->execute([$metal, $crystal, $deut, $sourcePlanetId]);
        }

        if ($fish > 0) {
            try {
                $this->db->prepare("
                    UPDATE planets 
                    SET fish = LEAST(fish_max, fish + ?) 
                    WHERE id = ?
                ")->execute([$fish, $sourcePlanetId]);
            } catch (Exception $e) {
                // Silencieux si colonne absente
            }
        }

        $sulfur = (float)($cargo['sulfur'] ?? 0);
        $nitrate = (float)($cargo['nitrate'] ?? 0);
        $ironSand = (float)($cargo['iron_sand'] ?? 0);
        if ($sulfur > 0 || $nitrate > 0 || $ironSand > 0) {
            try {
                $this->db->prepare("
                    UPDATE planets 
                    SET sulfur = LEAST(sulfur_max, sulfur + ?),
                        nitrate = LEAST(nitrate_max, nitrate + ?),
                        iron_sand = LEAST(iron_sand_max, iron_sand + ?)
                    WHERE id = ?
                ")->execute([$sulfur, $nitrate, $ironSand, $sourcePlanetId]);
            } catch (Exception $e) {
                // Silencieux si colonnes absentes
            }
        }

        // Réintégrer le Samouraï Héros au domaine s'il a participé
        if (!empty($mission['has_hero'])) {
            $this->db->prepare("
                UPDATE heroes 
                SET status = 'home', current_planet_id = ?, last_health_update = UNIX_TIMESTAMP() 
                WHERE user_id = ? AND status != 'dead'
            ")->execute([$sourcePlanetId, $mission['user_id']]);
        }

        // Notification féodale en cas de retour d'expédition de pêche
        if ($mission['mission_type'] === 'fishing') {
            try {
                require_once __DIR__ . '/MessageEngine.php';
                $msgEngine = new MessageEngine();
                $body = "Vos Pêcheurs sont revenus de leur expédition sur le lac avec une récolte de " . number_format($fish) . " poissons frais. Les vivres ont été entreposés dans le Grenier à Riz Fortifié de votre fief.";
                if (!empty($cargo['lake_depleted'])) {
                    $body .= " ⚠️ Le lac a été entièrement vidé de ses poissons : il n'est désormais plus possible d'y pêcher.";
                }
                $msgEngine->sendSystemMessage(
                    (int)$mission['user_id'],
                    "Retour d'Expédition de Pêche Féodale",
                    $body
                );
            } catch (Exception $e) {
                // Silencieux
            }
        }

        // Notification féodale en cas de retour d'expédition minière
        if ($mission['mission_type'] === 'mining') {
            try {
                require_once __DIR__ . '/MessageEngine.php';
                $msgEngine = new MessageEngine();
                $body = "Vos Mineurs des Cimes sont revenus de leur expédition de montagne avec les minerais extraits :\n"
                    . "• Soufre Volcanique : +" . number_format($sulfur) . "\n"
                    . "• Nitrate (Salpêtre) : +" . number_format($nitrate) . "\n"
                    . "• Sable de Fer (Satetsu) : +" . number_format($ironSand) . "\n\n"
                    . "Les minerais bruts ont été déposés dans vos entrepôts de fief pour vos forges et manufactures.";
                if (!empty($cargo['mountain_depleted'])) {
                    $body .= "\n⚠️ Le filon minéral de cette montagne est désormais totalement épuisé.";
                }
                $msgEngine->sendSystemMessage(
                    (int)$mission['user_id'],
                    "Retour d'Expédition Minière Féodale",
                    $body
                );
            } catch (Exception $e) {
                // Silencieux
            }
        }

        // Marquer la mission terminée
        $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$mission['id']]);

        $this->db->commit();
    }

    /**
     * Coordonnées [x, y] de la cible d'une mission (oasis ou fief/parcelle)
     */
    private function getMissionTargetCoords(array $mission): ?array {
        try {
            if (!empty($mission['target_oasis_id'])) {
                $st = $this->db->prepare("SELECT coord_x, coord_y FROM oases WHERE id = ?");
                $st->execute([(int)$mission['target_oasis_id']]);
            } elseif (!empty($mission['target_planet_id'])) {
                $st = $this->db->prepare("SELECT coord_x, coord_y FROM planets WHERE id = ?");
                $st->execute([(int)$mission['target_planet_id']]);
            } else {
                return null;
            }
            $r = $st->fetch(PDO::FETCH_ASSOC);
            return $r ? [(int)$r['coord_x'], (int)$r['coord_y']] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Résolution de l'arrivée et de la pêche sur une parcelle de lac
     */
    private function resolveFishingArrival(array $mission): void {
        $missionId = (int)$mission['id'];
        $userId = (int)$mission['user_id'];
        $fleet = json_decode($mission['fleet_data'], true) ?: [];
        $pecheurCount = (int)($fleet['pecheur'] ?? 0);

        // Recherche halieutique à l'Académie des Savoirs
        $stmtTech = $this->db->prepare("SELECT level FROM user_researches WHERE user_id = ? AND research_code = 'fishing_tech'");
        $stmtTech->execute([$userId]);
        $techLvl = (int)$stmtTech->fetchColumn() ?: 0;

        // Rendement : 20 poissons par pêcheur, bonifié de +25% par niveau de recherche académique
        $baseCatchPerPecheur = 20;
        $mult = 1.0 + (0.25 * $techLvl);
        $totalFish = (int)round($pecheurCount * $baseCatchPerPecheur * $mult);

        // Calcul de la capacité de transport totale de l'expédition
        $totalCapacity = 0;
        $stmtUnits = $this->db->query("SELECT code, cargo_capacity FROM units");
        $unitCapacities = $stmtUnits->fetchAll(PDO::FETCH_KEY_PAIR);
        $stmtShips = $this->db->query("SELECT code, cargo_capacity FROM ships");
        $shipCapacities = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);
        $allCapacities = array_merge($unitCapacities, $shipCapacities);

        foreach ($fleet as $code => $count) {
            $cap = (int)($allCapacities[$code] ?? 50);
            $totalCapacity += ($cap * (int)$count);
        }
        if ($totalCapacity <= 0) {
            $totalCapacity = max(100, $pecheurCount * 100);
        }

        $fishWanted = min($totalCapacity, $totalFish);

        // Prélèvement dans la réserve finie du lac ciblé
        $fishCaught = $fishWanted;
        $lakeCoords = $this->getMissionTargetCoords($mission);
        if ($lakeCoords !== null) {
            require_once __DIR__ . '/LakeEngine.php';
            $fishCaught = (new LakeEngine($this->db))->harvest($lakeCoords[0], $lakeCoords[1], $fishWanted);
        }

        $cargo = json_decode($mission['cargo_data'], true) ?: [];
        $cargo['fish'] = $fishCaught;
        if ($fishCaught < $fishWanted) {
            $cargo['lake_depleted'] = true;
        }

        // La flottille engage le trajet de retour chargée de poisson frais
        $this->db->prepare("
            UPDATE fleet_missions 
            SET status = 'returning', cargo_data = ? 
            WHERE id = ?
        ")->execute([
            json_encode($cargo),
            $missionId
        ]);
    }

    /**
     * Résolution de l'arrivée et de l'extraction minière sur une parcelle de montagne
     */
    private function resolveMiningArrival(array $mission): void {
        $missionId = (int)$mission['id'];
        $userId = (int)$mission['user_id'];
        $fleet = json_decode($mission['fleet_data'], true) ?: [];
        $mineurCount = (int)($fleet['mineur'] ?? 0);

        // Recherche en métallurgie & géologie à l'Académie des Savoirs
        $stmtTech = $this->db->prepare("SELECT level FROM user_researches WHERE user_id = ? AND research_code = 'mining_tech'");
        $stmtTech->execute([$userId]);
        $techLvl = (int)$stmtTech->fetchColumn() ?: 0;

        // Rendement par mineur : 15 Soufre, 15 Nitrate, 25 Sable de Fer (Satetsu), bonifié de +25% par niveau de recherche
        $mult = 1.0 + (0.25 * $techLvl);
        $baseSulfurPerMineur = 15;
        $baseNitratePerMineur = 15;
        $baseIronSandPerMineur = 25;

        $targetSulfur = (int)round($mineurCount * $baseSulfurPerMineur * $mult);
        $targetNitrate = (int)round($mineurCount * $baseNitratePerMineur * $mult);
        $targetIronSand = (int)round($mineurCount * $baseIronSandPerMineur * $mult);

        // Calcul de la capacité de transport totale de l'expédition
        $totalCapacity = 0;
        $stmtUnits = $this->db->query("SELECT code, cargo_capacity FROM units");
        $unitCapacities = $stmtUnits->fetchAll(PDO::FETCH_KEY_PAIR);
        $stmtShips = $this->db->query("SELECT code, cargo_capacity FROM ships");
        $shipCapacities = $stmtShips->fetchAll(PDO::FETCH_KEY_PAIR);
        $allCapacities = array_merge($unitCapacities, $shipCapacities);

        foreach ($fleet as $code => $count) {
            $cap = (int)($allCapacities[$code] ?? 50);
            $totalCapacity += ($cap * (int)$count);
        }
        if ($totalCapacity <= 0) {
            $totalCapacity = max(100, $mineurCount * 120);
        }

        $totalDesired = $targetSulfur + $targetNitrate + $targetIronSand;
        if ($totalDesired > $totalCapacity && $totalDesired > 0) {
            $ratio = $totalCapacity / $totalDesired;
            $targetSulfur = (int)floor($targetSulfur * $ratio);
            $targetNitrate = (int)floor($targetNitrate * $ratio);
            $targetIronSand = (int)floor($targetIronSand * $ratio);
        }

        // Prélèvement dans le gisement de la montagne ciblée
        $harvested = [
            'sulfur' => $targetSulfur,
            'nitrate' => $targetNitrate,
            'iron_sand' => $targetIronSand
        ];
        $isDepleted = false;

        $mountainCoords = $this->getMissionTargetCoords($mission);
        if ($mountainCoords !== null) {
            require_once __DIR__ . '/MountainEngine.php';
            $mountainEngine = new MountainEngine($this->db);
            $harvestResult = $mountainEngine->harvest($mountainCoords[0], $mountainCoords[1], [
                'sulfur' => $targetSulfur,
                'nitrate' => $targetNitrate,
                'iron_sand' => $targetIronSand
            ]);
            $harvested = $harvestResult['harvested'];
            $isDepleted = !empty($harvestResult['depleted']);
        }

        $cargo = json_decode($mission['cargo_data'], true) ?: [];
        $cargo['sulfur'] = (int)($harvested['sulfur'] ?? 0);
        $cargo['nitrate'] = (int)($harvested['nitrate'] ?? 0);
        $cargo['iron_sand'] = (int)($harvested['iron_sand'] ?? 0);
        if ($isDepleted) {
            $cargo['mountain_depleted'] = true;
        }

        // La flottille engage le trajet de retour chargée des minerais bruts
        $this->db->prepare("
            UPDATE fleet_missions 
            SET status = 'returning', cargo_data = ? 
            WHERE id = ?
        ")->execute([
            json_encode($cargo),
            $missionId
        ]);
    }

    /**
     * Résolution de l'arrivée d'un renfort de garnison sur le Dōjō Impérial [0 : 0]
     */
    private function resolveDojoReinforceArrival(array $mission): void {
        $missionId = (int)$mission['id'];
        $userId = (int)$mission['user_id'];
        require_once __DIR__ . '/ImperialDojoEngine.php';
        $dojoEngine = new ImperialDojoEngine($this->db);
        $state = $dojoEngine->getDojoState();

        // Récupérer l'alliance de l'utilisateur
        $stmtUser = $this->db->prepare("SELECT alliance_id FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userAllianceId = (int)$stmtUser->fetchColumn();

        $controllingAllianceId = (int)($state['controlling_alliance_id'] ?? 0);
        if ($userAllianceId === 0 || $userAllianceId !== $controllingAllianceId) {
            // Repli : pas membre de l'alliance bâtisseuse
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        // Déposer les unités dans imperial_dojo_garrison
        $fleet = json_decode($mission['fleet_data'], true) ?: [];
        $now = time();
        $this->db->beginTransaction();
        try {
            foreach ($fleet as $unitCode => $count) {
                $cnt = (int)$count;
                if ($cnt <= 0) continue;
                $stmtCheck = $this->db->prepare("
                    SELECT id FROM imperial_dojo_garrison 
                    WHERE dojo_id = ? AND user_id = ? AND unit_code = ?
                ");
                $stmtCheck->execute([(int)$state['id'], $userId, $unitCode]);
                $garrId = $stmtCheck->fetchColumn();
                if ($garrId) {
                    $this->db->prepare("UPDATE imperial_dojo_garrison SET amount = amount + ? WHERE id = ?")
                        ->execute([$cnt, $garrId]);
                } else {
                    $this->db->prepare("
                        INSERT INTO imperial_dojo_garrison (dojo_id, alliance_id, user_id, unit_code, amount, arrived_at)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([(int)$state['id'], $userAllianceId, $userId, $unitCode, $cnt, $now]);
                }
            }

            // Héros rentre au fief s'il était présent
            if (!empty($mission['has_hero'])) {
                $this->db->prepare("
                    UPDATE heroes 
                    SET status = 'home', current_planet_id = ?, last_health_update = UNIX_TIMESTAMP() 
                    WHERE user_id = ? AND status != 'dead'
                ")->execute([$mission['source_planet_id'], $userId]);
            }

            $this->db->prepare("UPDATE fleet_missions SET status = 'completed' WHERE id = ?")->execute([$missionId]);
            $this->db->commit();

            require_once __DIR__ . '/MessageEngine.php';
            $msgEngine = new MessageEngine();
            $msgEngine->sendSystemMessage(
                $userId,
                "Garnison Fédérale du Dōjō Impérial [0 : 0]",
                "Vos régiments ont pris position avec honneur sur la Terre Sacrée [0 : 0]. Ils montent la garde sous les toitures du Dōjō Impérial pour repousser les bombes et les sièges adverses !"
            );
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
        }
    }

    /**
     * Résolution de l'arrivée d'un siège destructeur sur le Dōjō Impérial [0 : 0]
     */
    private function resolveDojoSiegeArrival(array $mission): void {
        $missionId = (int)$mission['id'];
        $attackerUserId = (int)$mission['user_id'];
        require_once __DIR__ . '/ImperialDojoEngine.php';
        $dojoEngine = new ImperialDojoEngine($this->db);
        $state = $dojoEngine->getDojoState();

        $stmtAttacker = $this->db->prepare("SELECT u.*, a.id as alliance_id, a.tag as alliance_tag FROM users u LEFT JOIN alliances a ON u.alliance_id = a.id WHERE u.id = ?");
        $stmtAttacker->execute([$attackerUserId]);
        $attackerUser = $stmtAttacker->fetch();

        $controllingAllianceId = (int)($state['controlling_alliance_id'] ?? 0);
        if ($controllingAllianceId > 0 && !empty($attackerUser['alliance_id']) && (int)$attackerUser['alliance_id'] === $controllingAllianceId) {
            // On n'attaque pas son propre sanctuaire d'alliance
            $this->db->prepare("UPDATE fleet_missions SET status = 'returning' WHERE id = ?")->execute([$missionId]);
            return;
        }

        $fleet = json_decode($mission['fleet_data'], true) ?: [];

        // Récupérer la garnison en faction
        $garrison = $dojoEngine->getGarrison();
        $totalGarrisonCount = 0;
        foreach ($garrison as $g) {
            $totalGarrisonCount += (int)$g['amount'];
        }

        // Barème des dégâts de siège
        $rawSiegeDamage = 0;
        $siegeDamageTable = [
            'siege_bomb'        => 75000,
            'horoku_grenade'    => 15000,
            'ram'               => 5000,
            'catapult'          => 8000,
            'trebuchet'         => 12000,
            'cannon'            => 25000,
            'tanegashima'       => 500,
            'samurai_katana'    => 400,
            'samurai_heavy'     => 600,
            'cavalry_yari'      => 450
        ];

        foreach ($fleet as $unitCode => $count) {
            $cnt = (int)$count;
            if ($cnt <= 0) continue;
            $unitDmg = $siegeDamageTable[$unitCode] ?? 200;
            $rawSiegeDamage += ($cnt * $unitDmg);
        }

        // Si garnison présente, la garnison défend et inflige des pertes mutuelles
        $survivors = $fleet;
        if ($totalGarrisonCount > 0) {
            $lossRatio = min(0.60, max(0.10, $totalGarrisonCount / (array_sum($fleet) + 1)));
            foreach ($survivors as $uCode => &$uCnt) {
                $uCnt = max(0, (int)round($uCnt * (1.0 - $lossRatio)));
            }
            unset($uCnt);
            // Réduction de garnison
            $this->db->prepare("UPDATE imperial_dojo_garrison SET amount = GREATEST(0, ROUND(amount * 0.75)) WHERE dojo_id = ?")
                ->execute([(int)$state['id']]);
            $this->db->prepare("DELETE FROM imperial_dojo_garrison WHERE amount <= 0")->execute();
            $rawSiegeDamage = (int)round($rawSiegeDamage * (1.0 - $lossRatio));
        }

        // Appliquer les dégâts au Dōjō Impérial
        $damageResult = $dojoEngine->applySiegeDamage(
            $attackerUserId,
            !empty($attackerUser['alliance_id']) ? (int)$attackerUser['alliance_id'] : null,
            $rawSiegeDamage
        );

        // La flotte survivante rentre au fief
        $this->db->prepare("
            UPDATE fleet_missions 
            SET status = 'returning', fleet_data = ? 
            WHERE id = ?
        ")->execute([json_encode($survivors), $missionId]);

        // Rapports de siège féodal
        require_once __DIR__ . '/MessageEngine.php';
        $msgEngine = new MessageEngine();
        $msgEngine->sendSystemMessage(
            $attackerUserId,
            "Rapport de Siège Destructeur sur le Dōjō Impérial [0 : 0]",
            "Votre assaut pyrotechnique a frappé la Terre Sacrée [0 : 0] !\n"
            . "• Dégâts infligés au sanctuaire : " . number_format($damageResult['net_damage']) . " PV\n"
            . "• État des défenses du Dōjō : " . number_format($damageResult['new_hp']) . " PV restants\n"
            . ($damageResult['was_regressed'] ? "🔥 RÉSULTAT STRATÉGIQUE : " . $damageResult['log'] : "La structure a encaissé le bombardement.")
        );

        // Notifier le chef de l'alliance bâtisseuse
        if ($controllingAllianceId > 0) {
            $stmtLeader = $this->db->prepare("SELECT leader_id FROM alliances WHERE id = ?");
            $stmtLeader->execute([$controllingAllianceId]);
            $leaderId = (int)$stmtLeader->fetchColumn();
            if ($leaderId > 0 && $leaderId !== $attackerUserId) {
                $msgEngine->sendSystemMessage(
                    $leaderId,
                    "⚠️ ALERTE DE GUERRE : Bombardement sur le Dōjō Impérial [0 : 0] !",
                    "Le seigneur " . ($attackerUser['username'] ?? 'Adverse') . " [" . ($attackerUser['alliance_tag'] ?? '') . "] a lancé un siège destructeur contre votre sanctuaire en [0 : 0] !\n"
                    . "• Dégâts subis : -" . number_format($damageResult['net_damage']) . " PV\n"
                    . "• PV restants : " . number_format($damageResult['new_hp']) . " PV\n"
                    . ($damageResult['was_regressed'] ? "🚨 GRAVE REVERS : " . $damageResult['log'] : "Vos garnisons et les tuiles ont tenu le choc !")
                );
            }
        }
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

        $stmtUnits = $this->db->prepare("SELECT unit_code, count FROM planet_units WHERE planet_id = ? AND count > 0");
        $stmtUnits->execute([$targetPlanetId]);
        $units = $stmtUnits->fetchAll(PDO::FETCH_KEY_PAIR);

        $allTroops = array_merge($ships, $units);

        $reportData = [
            'type' => 'spy',
            'planet_name' => $targetPlanet['name'],
            'coords' => "({$targetPlanet['coord_x']}, {$targetPlanet['coord_y']})",
            'resources' => [
                'metal' => (int)$targetPlanet['metal'],
                'crystal' => (int)$targetPlanet['crystal'],
                'deuterium' => (int)$targetPlanet['deuterium'],
            ],
            'fleet' => $allTroops,
            'ships' => $ships,
            'units' => $units,
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
    private function colonizePlanet(int $planetId, int $userId, string $username, ?int $founderPlanetId = null): void {
        // Déterminer le numéro séquentiel du fief pour le Daimyo
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM planets WHERE user_id = ?");
        $stmtCount->execute([$userId]);
        $villageNum = (int)$stmtCount->fetchColumn() + 1;
        $defaultName = "Fief de " . ucfirst($username) . " #" . $villageNum;

        $this->db->prepare("
            UPDATE planets 
            SET user_id = ?, name = ?, is_capital = 0, founder_planet_id = ?, last_resource_update = UNIX_TIMESTAMP() 
            WHERE id = ?
        ")->execute([$userId, $defaultName, $founderPlanetId, $planetId]);

        // Initialiser les parcelles de ressources du domaine (Style Travian, niveau 0 = libre)
        $countFields = (int)$this->db->query("SELECT COUNT(*) FROM planet_fields WHERE planet_id = {$planetId}")->fetchColumn();
        if ($countFields < 18) {
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, null, 0, false);
        }

        // Emplacements urbains 100% libres pour le joueur
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
        // 0. Contrôle prioritaire de la Trêve Impériale Sacrée (Noël, Nouvel An ou Décret d'Exception)
        require_once __DIR__ . '/GameConfig.php';
        $imperialTruce = GameConfig::getImperialTruceStatus();
        if ($imperialTruce['active'] && in_array($missionType, ['attack', 'raid', 'occupy', 'spy', 'dojo_siege'], true)) {
            $tName = $imperialTruce['name'] ?? 'Trêve Impériale';
            $tPeriod = !empty($imperialTruce['period']) ? " ({$imperialTruce['period']})" : "";
            $tDesc = $imperialTruce['description'] ?? 'Toutes les armes doivent rester déposées.';
            throw new Exception("🕊️ {$tName}{$tPeriod} en vigueur ! {$tDesc} Tout assaut, raid ou espionnage est strictement suspendu par la Cour Impériale durant cette période de concorde sacrée.");
        }

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

        // 2a. Contrôle spécifique pour les expéditions de pêche sur les lacs
        if ($missionType === 'fishing') {
            require_once __DIR__ . '/GalaxyEngine.php';
            $isLake = false;
            if ($targetOasis) {
                if (str_contains((string)$targetOasis['oasis_type'], 'lake')) {
                    $isLake = true;
                }
            } else {
                $terrain = GalaxyEngine::getTerrainType($destX, $destY);
                if ($terrain['type'] === 'lake') {
                    $isLake = true;
                }
            }
            if (!$isLake) {
                throw new Exception("Une expédition de pêche ne peut être envoyée que sur une parcelle de lac ou d'eaux calmes.");
            }

            // Réserve halieutique finie : un lac épuisé ne peut plus être pêché
            require_once __DIR__ . '/LakeEngine.php';
            $lakeStock = (new LakeEngine($this->db))->getStock($destX, $destY);
            if ($lakeStock !== null && $lakeStock['depleted']) {
                throw new Exception("Ce lac est épuisé : toutes ses réserves de poisson ont déjà été pêchées. Le lac demeure, mais il n'y a plus rien à prélever.");
            }
        }

        // 2a-bis. Contrôle spécifique pour les expéditions minières dans les montagnes
        if ($missionType === 'mining') {
            require_once __DIR__ . '/GalaxyEngine.php';
            $isMountain = false;
            if ($targetOasis) {
                if (str_contains((string)$targetOasis['oasis_type'], 'mountain')) {
                    $isMountain = true;
                }
            } else {
                $terrain = GalaxyEngine::getTerrainType($destX, $destY);
                if ($terrain['type'] === 'mountain') {
                    $isMountain = true;
                }
            }
            if (!$isMountain) {
                throw new Exception("Une expédition minière ne peut être envoyée que sur une parcelle de montagne ou de crête rocheuse.");
            }

            // Gisement minéral fini : une montagne épuisée ne peut plus être forée
            require_once __DIR__ . '/MountainEngine.php';
            $mountainStock = (new MountainEngine($this->db))->getStock($destX, $destY);
            if ($mountainStock !== null && $mountainStock['depleted']) {
                throw new Exception("Ce filon de montagne est épuisé : toutes ses réserves de soufre, nitrate et sable de fer ont été extraites.");
            }
        }

        // 2a-ter. Contrôles stricts pour la Terre Sacrée [0 : 0] (Tenchi no Seichi & Dōjō Impérial)
        if ($destX === 0 && $destY === 0) {
            require_once __DIR__ . '/GameConfig.php';

            // Règle 1 : Aucun joueur ne peut annexer ni coloniser la tuile [0 : 0]
            if ($missionType === 'colonize' || $missionType === 'occupy') {
                throw new Exception("La Terre Sacrée [0 : 0] (Tenchi no Seichi) est un sanctuaire impérial inviolable. Aucun joueur ne peut l'annexer ni y fonder de fief personnel.");
            }

            // Règle 2 : Le Dōjō doit obligatoirement être activé pour toute mission vers [0 : 0]
            if (!GameConfig::isImperialDojoUnlocked()) {
                throw new Exception("La Terre Sacrée [0 : 0] est protégée par les brumes impériales de Kyōto. Le Dōjō Impérial n'étant pas encore éveillé (déblocage en Ère IV), aucun mouvement militaire ne peut y pénétrer.");
            }

            // Règle 3 : Missions autorisées exclusivement vers le Dōjō Impérial actif
            if (!in_array($missionType, ['dojo_reinforce', 'dojo_siege', 'transport'])) {
                throw new Exception("Le Dōjō Impérial [0 : 0] ne supporte que les ordres de Garnison Fédérale (Renfort) ou de Siège Destructeur.");
            }
        }

        if ($missionType === 'dojo_reinforce') {
            if ($destX !== 0 || $destY !== 0) {
                throw new Exception("L'ordre de garnison fédérale ne peut être dépêché que sur la Terre Sacrée [0 : 0].");
            }
            require_once __DIR__ . '/ImperialDojoEngine.php';
            $dojoEng = new ImperialDojoEngine($this->db);
            $dojoSt = $dojoEng->getDojoState();
            $stmtU = $this->db->prepare("SELECT alliance_id FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uAllId = (int)$stmtU->fetchColumn();
            if (!$uAllId || $uAllId !== (int)($dojoSt['controlling_alliance_id'] ?? 0)) {
                throw new Exception("Seuls les membres de l'alliance bâtisseuse peuvent déployer des régiments en garnison sur le Dōjō Impérial.");
            }
        }

        if ($missionType === 'dojo_siege') {
            if ($destX !== 0 || $destY !== 0) {
                throw new Exception("L'ordre de siège destructeur ne peut cibler que la Terre Sacrée [0 : 0].");
            }
            require_once __DIR__ . '/ImperialDojoEngine.php';
            $dojoEng = new ImperialDojoEngine($this->db);
            $dojoSt = $dojoEng->getDojoState();
            $stmtU = $this->db->prepare("SELECT alliance_id FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uAllId = (int)$stmtU->fetchColumn();
            if ($uAllId > 0 && $uAllId === (int)($dojoSt['controlling_alliance_id'] ?? 0)) {
                throw new Exception("Le code de l'honneur interdit d'attaquer le sanctuaire sacré érigé par votre propre alliance !");
            }
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

                // Règle d'Alliance Féodale : Interdiction formelle d'attaquer ou d'espionner un allié
                $stmtAlly = $this->db->prepare("
                    SELECT u1.alliance_id as source_alliance, u2.alliance_id as target_alliance, a.name as alliance_name, a.tag as alliance_tag
                    FROM users u1
                    JOIN users u2 ON u2.id = ?
                    LEFT JOIN alliances a ON a.id = u1.alliance_id
                    WHERE u1.id = ?
                ");
                $stmtAlly->execute([$targetUserId, $userId]);
                $allyCheck = $stmtAlly->fetch();
                if ($allyCheck && !empty($allyCheck['source_alliance']) && $allyCheck['source_alliance'] === $allyCheck['target_alliance']) {
                    throw new Exception("Ce fief appartient à un Daimyō de votre propre alliance [{$allyCheck['alliance_tag']}] {$allyCheck['alliance_name']}. Le code d'honneur féodal interdit formellement d'attaquer ou d'espionner un allié.");
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

                // Vérification alliance sur l'oasis occupée
                $stmtAllyOasis = $this->db->prepare("
                    SELECT u1.alliance_id as source_alliance, u2.alliance_id as target_alliance, a.name as alliance_name, a.tag as alliance_tag
                    FROM users u1
                    JOIN users u2 ON u2.id = ?
                    LEFT JOIN alliances a ON a.id = u1.alliance_id
                    WHERE u1.id = ?
                ");
                $stmtAllyOasis->execute([$oasisOwnerId, $userId]);
                $allyOasis = $stmtAllyOasis->fetch();
                if ($allyOasis && !empty($allyOasis['source_alliance']) && $allyOasis['source_alliance'] === $allyOasis['target_alliance']) {
                    throw new Exception("Cette oasis appartient à un Daimyō de votre alliance [{$allyOasis['alliance_tag']}]. Vous ne pouvez pas attaquer un frère d'armes.");
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

        // Règle spécifique à l'expédition de pêche halieutique
        if ($missionType === 'fishing') {
            if (empty($cleanFleet['pecheur']) || (int)$cleanFleet['pecheur'] <= 0) {
                throw new Exception("Une expédition de pêche nécessite de mobiliser au moins 1 Pêcheur Féodal (Ryōshi).");
            }
        }

        // Règle spécifique à l'expédition minière
        if ($missionType === 'mining') {
            if (empty($cleanFleet['mineur']) || (int)$cleanFleet['mineur'] <= 0) {
                throw new Exception("Une expédition minière nécessite de mobiliser au moins 1 Mineur des Cimes (Kōfu ⛏️).");
            }
        }

        if ($totalShips === 0 && !$hasHero) {
            throw new Exception("Veuillez sélectionner au moins un régiment, un engin de siège ou votre Samouraï Héros.");
        }

        // Règle spécifique à la colonisation / fondation de fief
        if ($missionType === 'colonize') {
            if ($targetOasisId || !$targetPlanet) {
                throw new Exception("Une expédition de colonisation doit cibler un territoire de village libre, pas une oasis.");
            }
            if ($targetPlanet['user_id'] !== null) {
                throw new Exception("Ce territoire est déjà gouverné par un autre seigneur féodal. Impossible d'y fonder un fief.");
            }
            if (empty($cleanFleet['colonizer']) && empty($cleanFleet['colony_ship'])) {
                throw new Exception("Une expédition de colonisation nécessite au moins 1 Pionnier Féodal (Colon ⛩️) pour ériger le nouveau fief.");
            }
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
        if ($missionType === 'fishing' || $missionType === 'mining') {
            $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
            // Temps d'extraction fixe sur le site (pêche ou forage) : 1 heure (3600 secondes)
            $siteDuration = max(60, (int)round(3600 / $speed));
            $returnTime = $arrivalTime + $siteDuration + $duration;
        } else {
            $returnTime = $arrivalTime + $duration;
        }

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

    /**
     * Récupère le bilan complet et classifié des mouvements de troupes, mineurs, pêcheurs et héros
     */
    public function getMovementsSummary(int $userId, int $planetId): array {
        require_once __DIR__ . '/HeroEngine.php';
        $heroEngine = new HeroEngine();
        $hero = $heroEngine->getHeroByUserId($userId);

        // 1. Détection des menaces hostiles arrivant vers le fief actif
        $stmtThreats = $this->db->prepare("
            SELECT fm.*,
                   p1.name as source_name, p1.coord_x as sx, p1.coord_y as sy,
                   u1.username as attacker_name
            FROM fleet_missions fm
            JOIN planets p1 ON fm.source_planet_id = p1.id
            JOIN users u1 ON fm.user_id = u1.id
            WHERE fm.target_planet_id = ?
              AND fm.user_id != ?
              AND fm.status = 'en_route'
              AND fm.mission_type IN ('raid', 'attack', 'occupy', 'spy')
            ORDER BY fm.arrival_time ASC
        ");
        $stmtThreats->execute([$planetId, $userId]);
        $threats = $stmtThreats->fetchAll(PDO::FETCH_ASSOC);

        // 2. Toutes les marches actives de l'utilisateur (sortantes ou de retour)
        $stmtMissions = $this->db->prepare("
            SELECT fm.*, 
                   p1.name as source_name, p1.coord_x as sx, p1.coord_y as sy,
                   COALESCE(p2.name, CONCAT('Oasis ', o.name)) as target_name, 
                   COALESCE(p2.coord_x, o.coord_x, 0) as tx, 
                   COALESCE(p2.coord_y, o.coord_y, 0) as ty,
                   ha.name as adventure_name, ha.difficulty as adventure_diff
            FROM fleet_missions fm 
            JOIN planets p1 ON fm.source_planet_id = p1.id 
            LEFT JOIN planets p2 ON fm.target_planet_id = p2.id 
            LEFT JOIN oases o ON fm.target_oasis_id = o.id
            LEFT JOIN hero_adventures ha ON fm.adventure_id = ha.id
            WHERE fm.user_id = ? AND fm.status IN ('en_route', 'returning') 
            ORDER BY fm.arrival_time ASC
        ");
        $stmtMissions->execute([$userId]);
        $allMissions = $stmtMissions->fetchAll(PDO::FETCH_ASSOC);

        $now = time();
        $heroMissions = [];
        $minerMissions = [];
        $fisherMissions = [];
        $militaryMissions = [];

        foreach ($allMissions as $m) {
            $isOutbound = ($m['status'] === 'en_route');
            $targetTime = $isOutbound ? (int)$m['arrival_time'] : (int)$m['return_time'];
            $secRemaining = max(0, $targetTime - $now);
            $fleetData = json_decode($m['fleet_data'] ?? '[]', true) ?: [];
            $cargoData = json_decode($m['cargo_data'] ?? '[]', true) ?: [];
            $totalUnits = array_sum($fleetData);

            $targetName = $m['target_name'];
            if (empty($targetName)) {
                $targetName = ($m['tx'] || $m['ty']) ? "Territoire [{$m['tx']}:{$m['ty']}]" : 'Terres Féodales';
            }

            $formattedMission = [
                'id' => (int)$m['id'],
                'mission_type' => $m['mission_type'],
                'status' => $m['status'],
                'is_outbound' => $isOutbound,
                'source_planet_id' => (int)$m['source_planet_id'],
                'source_name' => $m['source_name'],
                'source_coords' => "[{$m['sx']}:{$m['sy']}]",
                'target_name' => $targetName,
                'target_coords' => "[{$m['tx']}:{$m['ty']}]",
                'target_time' => $targetTime,
                'seconds_remaining' => $secRemaining,
                'fleet' => $fleetData,
                'total_units' => $totalUnits,
                'cargo' => $cargoData,
                'has_hero' => !empty($m['has_hero']),
                'adventure_name' => $m['adventure_name'] ?? null,
                'adventure_diff' => $m['adventure_diff'] ?? null,
            ];

            if ($m['mission_type'] === 'adventure') {
                $heroMissions[] = $formattedMission;
            } elseif ($m['mission_type'] === 'mining') {
                $minerCount = (int)($fleetData['mineur'] ?? 0);
                $formattedMission['miner_count'] = $minerCount;
                $minerMissions[] = $formattedMission;
            } elseif ($m['mission_type'] === 'fishing') {
                $fisherCount = (int)($fleetData['pecheur'] ?? 0);
                $formattedMission['fisher_count'] = $fisherCount;
                $fisherMissions[] = $formattedMission;
            } else {
                if (!empty($m['has_hero'])) {
                    $heroMissions[] = $formattedMission;
                }
                $militaryMissions[] = $formattedMission;
            }
        }

        $totalMovements = count($allMissions);

        return [
            'hero' => $hero,
            'hero_missions' => $heroMissions,
            'has_hero_moving' => !empty($heroMissions),
            'miner_missions' => $minerMissions,
            'miner_missions_count' => count($minerMissions),
            'fisher_missions' => $fisherMissions,
            'fisher_missions_count' => count($fisherMissions),
            'military_missions' => $militaryMissions,
            'military_missions_count' => count($militaryMissions),
            'all_missions' => $allMissions,
            'total_movements' => $totalMovements,
            'incoming_threats' => $threats,
            'incoming_threats_count' => count($threats)
        ];
    }
}

