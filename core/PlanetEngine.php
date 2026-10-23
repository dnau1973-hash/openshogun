<?php
/**
 * Moteur de calcul des ressources et de gestion planétaire
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PopulationEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class PlanetEngine {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Récupère une planète avec recalcul de ses files et ressources
     */
    public function getPlanet(int $planetId): ?array {
        return $this->updatePlanet($planetId);
    }

    /**
     * Met à jour les files d'attente terminées et recalcule les ressources de la planète
     */
    public function updatePlanet(int $planetId): array {
        // 0. Auto-migration du schéma si nécessaire
        $this->ensureSchemaMigration();

        // 1. Résolution des constructions terminées
        $this->processConstructionQueue($planetId);

        // 2. Résolution du chantier spatial
        $this->processShipyardQueue($planetId);

        // 3. Résolution de la caserne militaire
        $this->processBarracksQueue($planetId);

        // 4. Résolution de la file de raffinage de la meunerie (Farine & Saké)
        $this->processCraftQueue($planetId);

        // 5. Récupération de la planète
        $stmt = $this->db->prepare("
            SELECT p.*, u.faction, u.username
            FROM planets p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$planetId]);
        $planet = $stmt->fetch();
        if (!$planet) {
            throw new Exception("Planète introuvable.");
        }

        // 4. Calcul des capacités de stockage
        $buildings = $this->getBuildings($planetId);
        $storageLvl = $buildings['storage'] ?? 0;
        $tankLvl = $buildings['tank'] ?? 0;
        $grainMillLvl = $buildings['grain_mill'] ?? 0;
        $sawmillLvl = $buildings['sawmill'] ?? 0;

        $metalMax = (int)(15000 * pow(1.5, $storageLvl));
        $crystalMax = (int)(15000 * pow(1.5, $storageLvl));
        $deutMax = (int)(15000 * pow(1.5, $tankLvl));
        $fishMax = (int)(15000 * pow(1.5, $tankLvl));
        $sakeMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $flourMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $beamsMax = (int)(10000 * pow(1.4, $sawmillLvl));
        $charcoalMax = (int)(10000 * pow(1.4, $sawmillLvl));
        $clayMax = (int)(10000 * pow(1.4, $storageLvl));
        $teaMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $soyMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $sulfurMax = (int)(10000 * pow(1.4, $storageLvl));
        $nitrateMax = (int)(10000 * pow(1.4, $storageLvl));
        $ironSandMax = (int)(15000 * pow(1.5, $storageLvl));

        // 5. Calcul de l'énergie et des productions horaires (avec bonus d'oasis annexées)
        $fields = $this->getFields($planetId);
        $prodRates = $this->calculateProduction($fields, $planetId);

        // 6. Calcul démographique (capacité d'habitants et croissance)
        $maxPopulation = $this->calculateMaxPopulation($buildings, $fields, $planetId);
        $activeFeast = $this->getActiveFeast($planetId);

        $now = time();
        $lastUpdate = $planet['last_resource_update'] ?: $now;
        $elapsed = max(0, $now - $lastUpdate);

        $famineResult = ['famine' => false, 'casualties' => 0];

        if ($elapsed > 0) {
            // Facteur horaire
            $hours = $elapsed / 3600.0;

            // Traitement du cycle de population : vivres, saké (règle asymétrique), contentement, délinquance & exode
            PopulationEngine::processTick($planetId, $hours, $planet, $maxPopulation, $activeFeast, $buildings, $fields);

            $newMetal = min($metalMax, $planet['metal'] + ($prodRates['metal'] * $hours));
            $newCrystal = min($crystalMax, $planet['crystal'] + ($prodRates['crystal'] * $hours));
            $newDeut = min($deutMax, $planet['deuterium'] + ($prodRates['deuterium'] * $hours));
            $newClay = min($clayMax, (float)($planet['clay'] ?? 0) + (($prodRates['clay'] ?? 0) * $hours));
            $newTea = min($teaMax, (float)($planet['tea'] ?? 0) + (($prodRates['tea'] ?? 0) * $hours));
            $newSoy = min($soyMax, (float)($planet['soybean'] ?? 0) + (($prodRates['soybean'] ?? 0) * $hours));

            // Entretien des troupes d'élite & Famine féodale (si activée)
            $famineResult = $this->processFamine($planetId, $hours, $planet['rice_flour']);

            try {
                $stmtUpdate = $this->db->prepare("
                    UPDATE planets
                    SET metal = ?, crystal = ?, deuterium = ?,
                        rice_flour = ?, sake = ?, clay = ?, tea = ?, soybean = ?,
                        population = ?, contentment = ?,
                        energy_used = ?, energy_max = ?,
                        metal_max = ?, crystal_max = ?, deuterium_max = ?, fish_max = ?,
                        sake_max = ?, rice_flour_max = ?, clay_max = ?, tea_max = ?, soybean_max = ?,
                        sulfur_max = ?, nitrate_max = ?, iron_sand_max = ?,
                        last_resource_update = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $newMetal,
                    $newCrystal,
                    $newDeut,
                    $planet['rice_flour'],
                    $planet['sake'],
                    $newClay,
                    $newTea,
                    $newSoy,
                    $planet['population'],
                    $planet['contentment'],
                    $prodRates['energy_used'],
                    $prodRates['energy_max'],
                    $metalMax,
                    $crystalMax,
                    $deutMax,
                    $fishMax,
                    $sakeMax,
                    $flourMax,
                    $clayMax,
                    $teaMax,
                    $soyMax,
                    $sulfurMax,
                    $nitrateMax,
                    $ironSandMax,
                    $now,
                    $planetId
                ]);
            } catch (Exception $e) {
                try {
                    // Fallback sans colonnes terroirs si ancienne version de table
                    $stmtUpdate = $this->db->prepare("
                        UPDATE planets
                        SET metal = ?, crystal = ?, deuterium = ?,
                            rice_flour = ?, sake = ?, population = ?, contentment = ?,
                            energy_used = ?, energy_max = ?,
                            metal_max = ?, crystal_max = ?, deuterium_max = ?,
                            sake_max = ?, rice_flour_max = ?,
                            last_resource_update = ?
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([
                        $newMetal,
                        $newCrystal,
                        $newDeut,
                        $planet['rice_flour'],
                        $planet['sake'],
                        $planet['population'],
                        $planet['contentment'],
                        $prodRates['energy_used'],
                        $prodRates['energy_max'],
                        $metalMax,
                        $crystalMax,
                        $deutMax,
                        $sakeMax,
                        $flourMax,
                        $now,
                        $planetId
                    ]);
                } catch (Exception $e1) {
                    try {
                        // Fallback sans colonne contentment si ancienne version de table
                        $stmtUpdate = $this->db->prepare("
                            UPDATE planets
                            SET metal = ?, crystal = ?, deuterium = ?,
                                rice_flour = ?, population = ?,
                                energy_used = ?, energy_max = ?,
                                metal_max = ?, crystal_max = ?, deuterium_max = ?,
                                sake_max = ?, rice_flour_max = ?,
                                last_resource_update = ?
                            WHERE id = ?
                        ");
                        $stmtUpdate->execute([
                            $newMetal,
                            $newCrystal,
                            $newDeut,
                            $planet['rice_flour'],
                            $planet['population'],
                            $prodRates['energy_used'],
                            $prodRates['energy_max'],
                            $metalMax,
                            $crystalMax,
                            $deutMax,
                            $sakeMax,
                            $flourMax,
                            $now,
                            $planetId
                        ]);
                    } catch (Exception $e2) {
                        // Fallback initial
                        $stmtUpdate = $this->db->prepare("
                            UPDATE planets
                            SET metal = ?, crystal = ?, deuterium = ?,
                                energy_used = ?, energy_max = ?,
                                metal_max = ?, crystal_max = ?, deuterium_max = ?,
                                last_resource_update = ?
                            WHERE id = ?
                        ");
                        $stmtUpdate->execute([
                            $newMetal,
                            $newCrystal,
                            $newDeut,
                            $prodRates['energy_used'],
                            $prodRates['energy_max'],
                            $metalMax,
                            $crystalMax,
                            $deutMax,
                            $now,
                            $planetId
                        ]);
                    }
                }
            }

            $planet['metal'] = $newMetal;
            $planet['crystal'] = $newCrystal;
            $planet['deuterium'] = $newDeut;
            $planet['clay'] = $newClay;
            $planet['tea'] = $newTea;
            $planet['soybean'] = $newSoy;
            $planet['clay_max'] = $clayMax;
            $planet['tea_max'] = $teaMax;
            $planet['soybean_max'] = $soyMax;
            $planet['energy_used'] = $prodRates['energy_used'];
            $planet['energy_max'] = $prodRates['energy_max'];
            $planet['metal_max'] = $metalMax;
            $planet['crystal_max'] = $crystalMax;
            $planet['deuterium_max'] = $deutMax;
            $planet['fish_max'] = $fishMax;
            $planet['last_resource_update'] = $now;
        }

        // Bilan démographique et jauge de contentement féodale
        $workforceSummary = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPopulation);
        $contentmentDetails = PopulationEngine::calculateContentment($planet, $activeFeast, $workforceSummary, $buildings);

        // Valeurs garanties pour les ressources raffinées, démographie et satisfaction
        $planet['metal_max'] = (int)$metalMax;
        $planet['crystal_max'] = (int)$crystalMax;
        $planet['deuterium_max'] = (int)$deutMax;
        $planet['fish'] = (float)($planet['fish'] ?? 0);
        $planet['fish_max'] = (int)$fishMax;
        $planet['sake'] = (float)($planet['sake'] ?? 0);
        $planet['rice_flour'] = (float)($planet['rice_flour'] ?? 0);
        $planet['sake_max'] = (int)($planet['sake_max'] ?? $sakeMax);
        $planet['rice_flour_max'] = (int)($planet['rice_flour_max'] ?? $flourMax);
        $planet['wooden_beams'] = (float)($planet['wooden_beams'] ?? 0);
        $planet['wooden_beams_max'] = (int)($planet['wooden_beams_max'] ?? $beamsMax);
        $planet['charcoal'] = (float)($planet['charcoal'] ?? 0);
        $planet['charcoal_max'] = (int)($planet['charcoal_max'] ?? $charcoalMax);
        $planet['clay'] = (float)($planet['clay'] ?? 0);
        $planet['clay_max'] = (int)($planet['clay_max'] ?? $clayMax);
        $planet['tea'] = (float)($planet['tea'] ?? 0);
        $planet['tea_max'] = (int)($planet['tea_max'] ?? $teaMax);
        $planet['soybean'] = (float)($planet['soybean'] ?? 0);
        $planet['soybean_max'] = (int)($planet['soybean_max'] ?? $soyMax);
        $planet['sulfur'] = (float)($planet['sulfur'] ?? 0);
        $planet['sulfur_max'] = (int)($planet['sulfur_max'] ?? $sulfurMax);
        $planet['nitrate'] = (float)($planet['nitrate'] ?? 0);
        $planet['nitrate_max'] = (int)($planet['nitrate_max'] ?? $nitrateMax);
        $planet['iron_sand'] = (float)($planet['iron_sand'] ?? 0);
        $planet['iron_sand_max'] = (int)($planet['iron_sand_max'] ?? $ironSandMax);
        $planet['population'] = (int)($planet['population'] ?? 100);
        $planet['population_max'] = (int)$maxPopulation;
        $planet['contentment'] = (int)($planet['contentment'] ?? $contentmentDetails['score']);
        $planet['contentment_details'] = $contentmentDetails;
        $planet['workforce'] = $workforceSummary;
        $planet['delinquency'] = $contentmentDetails['delinquency'] ?? null;
        $planet['famine_active'] = !empty($famineResult['famine']) || (!empty($planet['famine_active']));
        $planet['last_famine_losses'] = (int)($famineResult['casualties'] ?? ($planet['last_famine_losses'] ?? 0));
        $planet['famine_enabled'] = (bool)GameConfig::get('famine_enabled', false);

        // Évolution de la société féodale par les besoins (Shinōkōshō)
        if (!isset($planet['social_castes'])) {
            require_once __DIR__ . '/SocialCasteEngine.php';
            $socialData = SocialCasteEngine::processSocialTick($planet, 0.0);
            $planet['civilization_level'] = $socialData['civilization_level'];
            $planet['civilization_name'] = $socialData['civilization_name'];
            $planet['social_castes'] = $socialData;
        }

        $planet['prod_rates'] = $prodRates;
        return $planet;
    }

    /**
     * Résolution des constructions terminées dans la file
     */
    public function processConstructionQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM construction_queue
            WHERE planet_id = ? AND finishes_at <= ?
            ORDER BY finishes_at ASC
        ");
        $stmt->execute([$planetId, $now]);
        $completed = $stmt->fetchAll();

        foreach ($completed as $item) {
            $cat = $item['build_category'];
            $targetLevel = (int)$item['target_level'];

            if ($cat === 'rural_plot') {
                $structureType = $item['target_id'];
                require_once __DIR__ . '/RuralPlotEngine.php';
                require_once __DIR__ . '/VillageGeneratorService.php';
                require_once __DIR__ . '/PopulationEngine.php';

                $newWorkers = PopulationEngine::getRuralPlotWorkersRequired($structureType, $targetLevel);
                $newProd = VillageGeneratorService::calculateHourlyProduction($structureType, $targetLevel);

                $up = $this->db->prepare("
                    UPDATE planet_rural_plots
                    SET level = ?,
                        workers_assigned = ?,
                        prod_hourly = ?
                    WHERE planet_id = ? AND structure_type = ?
                ");
                $up->execute([$targetLevel, $newWorkers, $newProd, $planetId, $structureType]);

                // Effets spécifiques
                if ($structureType === 'village') {
                    $newCapacity = 75 + ($targetLevel * 25);
                    $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                    $minSql = ($driver === 'sqlite') ? "MIN(population + 5, ?)" : "LEAST(population + 5, ?)";
                    $this->db->prepare("UPDATE planets SET population = {$minSql} WHERE id = ?")
                        ->execute([$newCapacity, $planetId]);
                } elseif ($structureType === 'tenshu') {
                    try {
                        $this->db->prepare("UPDATE planet_buildings SET level = ? WHERE planet_id = ? AND building_type = 'hq'")
                            ->execute([$targetLevel, $planetId]);
                    } catch (Exception $e) {}
                }
            } elseif ($cat === 'field') {
                $slot = (int)$item['target_id'];
                if ($targetLevel === 0) {
                    // Démolition de parcelle terminée : récupérer le type et niveau pour remboursement 30%
                    $stmtF = $this->db->prepare("SELECT type, level FROM planet_fields WHERE planet_id = ? AND field_slot = ?");
                    $stmtF->execute([$planetId, $slot]);
                    $fRow = $stmtF->fetch();
                    $fType = $fRow['type'] ?? 'metal_mine';
                    $prevLvl = (int)($fRow['level'] ?? 1);

                    require_once __DIR__ . '/BuildingEngine.php';
                    $be = new BuildingEngine($this->db);
                    $buildings = $this->getBuildings($planetId);
                    $hqLvl = (int)($buildings['hq'] ?? 1);
                    $det = $be->getUpgradeDetails('field', $fType, max(0, $prevLvl - 1), $hqLvl);
                    $rfM = (int)($det['cost']['metal'] * 0.3);
                    $rfC = (int)($det['cost']['crystal'] * 0.3);
                    $rfD = (int)($det['cost']['deuterium'] * 0.3);

                    // Réinitialiser la parcelle au niveau 0 (terrain vierge disponible)
                    $this->db->prepare("UPDATE planet_fields SET level = 0 WHERE planet_id = ? AND field_slot = ?")
                        ->execute([$planetId, $slot]);

                    // Créditer les matériaux récupérés
                    if ($rfM > 0 || $rfC > 0 || $rfD > 0) {
                        $this->db->prepare("UPDATE planets SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? WHERE id = ?")
                            ->execute([$rfM, $rfC, $rfD, $planetId]);
                    }
                } else {
                    $up = $this->db->prepare("
                        UPDATE planet_fields
                        SET level = ?
                        WHERE planet_id = ? AND field_slot = ?
                    ");
                    $up->execute([$targetLevel, $planetId, $slot]);
                }
            } else {
                $bType = $item['target_id'];
                if ($targetLevel === 0) {
                    // Démantèlement de bâtiment terminé : récupérer niveau pour remboursement 30%
                    $stmtB = $this->db->prepare("SELECT level FROM planet_buildings WHERE planet_id = ? AND building_type = ?");
                    $stmtB->execute([$planetId, $bType]);
                    $prevLvl = (int)$stmtB->fetchColumn();

                    require_once __DIR__ . '/BuildingEngine.php';
                    $be = new BuildingEngine($this->db);
                    $buildings = $this->getBuildings($planetId);
                    $hqLvl = (int)($buildings['hq'] ?? 1);
                    $det = $be->getUpgradeDetails('building', $bType, max(0, $prevLvl - 1), $hqLvl);
                    $rfM = (int)($det['cost']['metal'] * 0.3);
                    $rfC = (int)($det['cost']['crystal'] * 0.3);
                    $rfD = (int)($det['cost']['deuterium'] * 0.3);

                    // Supprimer le bâtiment pour libérer définitivement le slot urbain
                    $this->db->prepare("DELETE FROM planet_buildings WHERE planet_id = ? AND building_type = ?")
                        ->execute([$planetId, $bType]);

                    // Créditer les matériaux récupérés
                    if ($rfM > 0 || $rfC > 0 || $rfD > 0) {
                        $this->db->prepare("UPDATE planets SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? WHERE id = ?")
                            ->execute([$rfM, $rfC, $rfD, $planetId]);
                    }
                } else {
                    $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                    if ($driver === 'sqlite') {
                        $up = $this->db->prepare("
                            INSERT INTO planet_buildings (planet_id, building_type, level)
                            VALUES (?, ?, ?)
                            ON CONFLICT(planet_id, building_type) DO UPDATE SET level = excluded.level
                        ");
                        $up->execute([$planetId, $bType, $targetLevel]);
                    } else {
                        $up = $this->db->prepare("
                            INSERT INTO planet_buildings (planet_id, building_type, level)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE level = ?
                        ");
                        $up->execute([$planetId, $bType, $targetLevel, $targetLevel]);
                    }
                }
            }

            // Supprimer de la file
            $del = $this->db->prepare("DELETE FROM construction_queue WHERE id = ?");
            $del->execute([$item['id']]);
        }
    }

    /**
     * Résolution progressive ("au fil de l'eau") du chantier de siège et cavalerie
     */
    public function processShipyardQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM shipyard_queue
            WHERE planet_id = ?
            ORDER BY started_at ASC, id ASC
        ");
        $stmt->execute([$planetId]);
        $queueItems = $stmt->fetchAll();

        foreach ($queueItems as $item) {
            $startedAt = (int)$item['started_at'];
            if ($now < $startedAt) {
                // Cette commande en file n'a pas encore débuté
                break;
            }

            $currentRemaining = (int)$item['count'];
            $totalCount = !empty($item['total_count']) ? (int)$item['total_count'] : $currentRemaining;
            $unitTime = max(1, (int)$item['unit_build_time']);
            $elapsed = $now - $startedAt;

            // Nombre d'engins / montures achevés au fil de l'eau
            $unitsDone = (int)floor($elapsed / $unitTime);
            $unitsDone = min($currentRemaining, $unitsDone);

            if ($unitsDone > 0) {
                // Créditer immédiatement dans la flotte / garnison du fief
                $up = $this->db->prepare("
                    INSERT INTO planet_ships (planet_id, ship_code, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $up->execute([$planetId, $item['ship_code'], $unitsDone, $unitsDone]);

                if ($unitsDone >= $currentRemaining) {
                    // Lot entièrement terminé
                    $del = $this->db->prepare("DELETE FROM shipyard_queue WHERE id = ?");
                    $del->execute([$item['id']]);
                } else {
                    // Lot partiellement terminé : mise à jour au fil de l'eau
                    $newRemaining = $currentRemaining - $unitsDone;
                    $newStartedAt = $startedAt + ($unitsDone * $unitTime);

                    $upd = $this->db->prepare("
                        UPDATE shipyard_queue
                        SET count = ?, total_count = ?, started_at = ?
                        WHERE id = ?
                    ");
                    $upd->execute([$newRemaining, $totalCount, $newStartedAt, $item['id']]);

                    // Le lot courant est toujours actif, les suivants attendent
                    break;
                }
            } else {
                // Le lot courant est en train d'assembler une unité
                break;
            }
        }
    }

    /**
     * Résolution progressive ("au fil de l'eau") des régiments de soldats en formation
     */
    public function processBarracksQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM barracks_queue
            WHERE planet_id = ?
            ORDER BY started_at ASC, id ASC
        ");
        $stmt->execute([$planetId]);
        $queueItems = $stmt->fetchAll();

        foreach ($queueItems as $item) {
            $startedAt = (int)$item['started_at'];
            if ($now < $startedAt) {
                // Cette commande en file n'a pas encore débuté
                break;
            }

            $currentRemaining = (int)$item['count'];
            $totalCount = !empty($item['total_count']) ? (int)$item['total_count'] : $currentRemaining;
            $unitTime = max(1, (int)$item['unit_train_time']);
            $elapsed = $now - $startedAt;

            // Nombre de soldats achevés au fil de l'eau
            $unitsDone = (int)floor($elapsed / $unitTime);
            $unitsDone = min($currentRemaining, $unitsDone);

            if ($unitsDone > 0) {
                // Créditer immédiatement dans la garnison du fief
                $up = $this->db->prepare("
                    INSERT INTO planet_units (planet_id, unit_code, count)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE count = count + ?
                ");
                $up->execute([$planetId, $item['unit_code'], $unitsDone, $unitsDone]);

                if ($unitsDone >= $currentRemaining) {
                    // Lot entièrement terminé
                    $del = $this->db->prepare("DELETE FROM barracks_queue WHERE id = ?");
                    $del->execute([$item['id']]);
                } else {
                    // Lot partiellement terminé : mise à jour au fil de l'eau
                    $newRemaining = $currentRemaining - $unitsDone;
                    $newStartedAt = $startedAt + ($unitsDone * $unitTime);

                    $upd = $this->db->prepare("
                        UPDATE barracks_queue
                        SET count = ?, total_count = ?, started_at = ?
                        WHERE id = ?
                    ");
                    $upd->execute([$newRemaining, $totalCount, $newStartedAt, $item['id']]);

                    // Le lot courant est toujours actif, les suivants attendent
                    break;
                }
            } else {
                // Le lot courant est en train d'entraîner une unité
                break;
            }
        }
    }

    /**
     * Traite les lots de raffinage et de charpente terminés dans les ateliers (Meunerie, Charpenterie)
     */
    public function processCraftQueue(int $planetId): void {
        $now = time();
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM craft_queue
                WHERE planet_id = ? AND finishes_at <= ?
                ORDER BY finishes_at ASC
            ");
            $stmt->execute([$planetId, $now]);
            $completed = $stmt->fetchAll();

            foreach ($completed as $item) {
                $product = $item['product'];
                $amount = (float)$item['produced_amount'];

                if ($product === 'wooden_beams' || $product === 'charcoal') {
                    $this->db->beginTransaction();
                    try {
                        $up = $this->db->prepare("
                            UPDATE planets
                            SET {$product} = LEAST({$product}_max, {$product} + ?)
                            WHERE id = ?
                        ");
                        $up->execute([$amount, $planetId]);

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif (in_array($product, ['sake', 'rice_flour', 'sulfur', 'nitrate', 'iron_sand'])) {
                    $this->db->beginTransaction();
                    try {
                        $up = $this->db->prepare("
                            UPDATE planets
                            SET {$product} = LEAST({$product}_max, {$product} + ?)
                            WHERE id = ?
                        ");
                        $up->execute([$amount, $planetId]);

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif ($product === 'metal' || $product === 'metal_crude') {
                    $this->db->beginTransaction();
                    try {
                        $up = $this->db->prepare("
                            UPDATE planets
                            SET metal = LEAST(metal_max, metal + ?)
                            WHERE id = ?
                        ");
                        $up->execute([$amount, $planetId]);

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif (in_array($product, ['roof_tiles', 'onigawara_tiles'])) {
                    $this->db->beginTransaction();
                    try {
                        if ($product === 'roof_tiles') {
                            $up = $this->db->prepare("
                                UPDATE planets
                                SET roof_tiles = LEAST(roof_tiles_max, roof_tiles + ?)
                                WHERE id = ?
                            ");
                            $up->execute([$amount, $planetId]);
                        } else {
                            $up = $this->db->prepare("
                                UPDATE planets
                                SET onigawara_tiles = onigawara_tiles + ?
                                WHERE id = ?
                            ");
                            $up->execute([$amount, $planetId]);
                        }

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif ($product === 'prestige_weapons') {
                    $this->db->beginTransaction();
                    try {
                        $planet = $this->getPlanet($planetId);
                        $userId = (int)($planet['user_id'] ?? 0);
                        $honorGain = 150 * max(1, (int)$amount);
                        if ($userId > 0) {
                            $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?")->execute([$honorGain, $userId]);

                            $stmtHeroItem = $this->db->prepare("SELECT id FROM hero_inventory WHERE user_id = ? AND item_code = 'katana_tamahagane'");
                            $stmtHeroItem->execute([$userId]);
                            if (!$stmtHeroItem->fetch()) {
                                $bonus = json_encode(['strength' => 320, 'offense_bonus' => 1.5]);
                                $this->db->prepare("
                                    INSERT INTO hero_inventory (user_id, item_code, item_type, name, description, bonus_data, is_equipped)
                                    VALUES (?, 'katana_tamahagane', 'weapon', 'Katana Forgé en Tamahagane', 'Lame d\'exception forgée dans le meilleur acier plié selon les secrets des maîtres forgerons de Bizen.', ?, 0)
                                ")->execute([$userId, $bonus]);
                            } else {
                                $this->db->prepare("UPDATE hero SET experience = experience + 250 WHERE user_id = ?")->execute([$userId]);
                            }
                        }

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif ($product === 'prestige_army') {
                    $this->db->beginTransaction();
                    try {
                        $batches = max(1, (int)round($amount / 10));
                        $samourais = 10 * $batches;
                        $hatamotos = 2 * $batches;

                        $upUnit = $this->db->prepare("
                            INSERT INTO planet_units (planet_id, unit_code, count)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE count = count + ?
                        ");
                        $upUnit->execute([$planetId, 'samourai_katana', $samourais, $samourais]);
                        $upUnit->execute([$planetId, 'garde_hatamoto_armure_lourde', $hatamotos, $hatamotos]);

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } else {
                    $this->db->prepare("DELETE FROM craft_queue WHERE id = ?")->execute([$item['id']]);
                }
            }
        } catch (Exception $e) {
            // Table pas encore créée ou en migration
        }
    }

    /**
     * Récupère la file active de raffinage / charpente pour une planète
     */
    public function getCraftQueue(int $planetId, ?string $buildingType = null): array {
        $now = time();
        try {
            if ($buildingType) {
                $stmt = $this->db->prepare("
                    SELECT * FROM craft_queue
                    WHERE planet_id = ? AND (building_type = ? OR (building_type = '' AND ? = 'grain_mill'))
                    ORDER BY started_at ASC, id ASC
                ");
                $stmt->execute([$planetId, $buildingType, $buildingType]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT * FROM craft_queue
                    WHERE planet_id = ?
                    ORDER BY started_at ASC, id ASC
                ");
                $stmt->execute([$planetId]);
            }
            $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($queue as $idx => &$item) {
                $isCurrent = ($idx === 0 && (int)$item['started_at'] <= $now);
                $item['is_current'] = $isCurrent;
                $item['status'] = $isCurrent ? 'processing' : 'queued';

                if ($item['product'] === 'wooden_beams') {
                    $item['product_name'] = 'Poutres en bois';
                    $item['product_icon'] = '🪵';
                } elseif ($item['product'] === 'charcoal') {
                    $item['product_name'] = 'Charbon de bois';
                    $item['product_icon'] = '🪨';
                } elseif ($item['product'] === 'sake') {
                    $item['product_name'] = 'Saké Impérial';
                    $item['product_icon'] = '🍶';
                } elseif ($item['product'] === 'metal') {
                    $item['product_name'] = 'Fonte de Tamahagane';
                    $item['product_icon'] = '⚔️';
                } elseif ($item['product'] === 'metal_crude') {
                    $item['product_name'] = 'Réduction Rustique de Métal';
                    $item['product_icon'] = '🔥';
                } elseif ($item['product'] === 'prestige_weapons') {
                    $item['product_name'] = 'Katanas de Maître & Lames Sacrées';
                    $item['product_icon'] = '🗡️';
                } elseif ($item['product'] === 'prestige_army') {
                    $item['product_name'] = 'Garde d\'Honneur au Tamahagane';
                    $item['product_icon'] = '🎌';
                } elseif (in_array($item['product'], ['sulfur', 'nitrate', 'iron_sand'])) {
                    $item['product_name'] = match($item['product']) {
                        'sulfur' => 'Soufre Volcanique',
                        'nitrate' => 'Nitrate de Potasse',
                        'iron_sand' => 'Sable de Fer (Satetsu)',
                        default => 'Minerai Raffiné'
                    };
                    $item['product_icon'] = match($item['product']) {
                        'sulfur' => '🌋',
                        'nitrate' => '🧪',
                        'iron_sand' => '💎',
                        default => '⛏️'
                    };
                } else {
                    $item['product_name'] = 'Farine de Riz';
                    $item['product_icon'] = '🍚';
                }

                $totalDuration = max(1, (int)$item['finishes_at'] - (int)$item['started_at']);
                $item['total_duration'] = $totalDuration;

                if ($isCurrent) {
                    $item['time_remaining'] = max(0, (int)$item['finishes_at'] - $now);
                    $item['starts_in'] = 0;
                    $elapsed = max(0, $now - (int)$item['started_at']);
                    $item['progress'] = min(100, max(0, round(($elapsed / $totalDuration) * 100, 1)));
                } else {
                    $item['starts_in'] = max(0, (int)$item['started_at'] - $now);
                    $item['time_remaining'] = $totalDuration;
                    $item['progress'] = 0;
                }
            }
            unset($item);
            return $queue;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Traite l'entretien en farine des troupes d'élite et le mécanisme de famine si activé
     *
     * @param int $planetId
     * @param float $hours Heures écoulées depuis la dernière actualisation
     * @param float &$curFlour Référence au stock de farine de riz actuel
     * @return array Détails de l'entretien et des pertes de famine le cas échéant
     */
    public function processFamine(int $planetId, float $hours, float &$curFlour): array {
        $famineEnabled = (bool)GameConfig::get('famine_enabled', false);
        if (!$famineEnabled || $hours <= 0) {
            return ['famine' => false, 'casualties' => 0, 'flour_consumed' => 0];
        }

        try {
            // Récupérer les troupes d'élite présentes sur le fief (Tier >= 2)
            $stmtUnits = $this->db->prepare("
                SELECT pu.id, pu.unit_code, pu.count, u.name, u.tier
                FROM planet_units pu
                JOIN units u ON pu.unit_code = u.code
                WHERE pu.planet_id = ? AND pu.count > 0 AND u.tier >= 2
            ");
            $stmtUnits->execute([$planetId]);
            $eliteUnits = $stmtUnits->fetchAll();

            if (empty($eliteUnits)) {
                $this->db->prepare("UPDATE planets SET famine_active = 0, last_famine_losses = 0 WHERE id = ?")->execute([$planetId]);
                return ['famine' => false, 'casualties' => 0, 'flour_consumed' => 0];
            }

            $totalEliteCount = 0;
            foreach ($eliteUnits as $u) {
                $totalEliteCount += (int)$u['count'];
            }

            if ($totalEliteCount <= 0) {
                $this->db->prepare("UPDATE planets SET famine_active = 0, last_famine_losses = 0 WHERE id = ?")->execute([$planetId]);
                return ['famine' => false, 'casualties' => 0, 'flour_consumed' => 0];
            }

            // Ration requise : famine_flour_consumption par tranche de 100 soldats par heure
            $ratePer100 = (float)GameConfig::get('famine_flour_consumption', 1.0);
            $flourNeeded = ($totalEliteCount / 100.0) * $ratePer100 * $hours;

            // Cas 1 : Assez de farine pour nourrir les troupes
            if ($curFlour >= $flourNeeded) {
                $curFlour = max(0, $curFlour - $flourNeeded);
                $this->db->prepare("UPDATE planets SET famine_active = 0, last_famine_losses = 0 WHERE id = ?")->execute([$planetId]);
                return ['famine' => false, 'casualties' => 0, 'flour_consumed' => $flourNeeded];
            }

            // Cas 2 : Pénurie de farine -> Déclenchement de la Famine !
            $curFlour = 0;
            $hourlyLossRate = (float)GameConfig::get('famine_rate', 3.0) / 100.0;
            $lossFactor = min(1.0, max(0.01, $hourlyLossRate * $hours));

            $totalCasualties = 0;
            $this->db->beginTransaction();
            try {
                foreach ($eliteUnits as $u) {
                    $count = (int)$u['count'];
                    $lost = max(1, (int)round($count * $lossFactor));
                    $lost = min($count, $lost);
                    $remaining = $count - $lost;
                    $totalCasualties += $lost;

                    if ($remaining <= 0) {
                        $this->db->prepare("DELETE FROM planet_units WHERE id = ?")->execute([$u['id']]);
                    } else {
                        $this->db->prepare("UPDATE planet_units SET count = ? WHERE id = ?")->execute([$remaining, $u['id']]);
                    }
                }

                $this->db->prepare("
                    UPDATE planets
                    SET famine_active = 1, last_famine_losses = last_famine_losses + ?
                    WHERE id = ?
                ")->execute([$totalCasualties, $planetId]);

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
            }

            return [
                'famine' => true,
                'casualties' => $totalCasualties,
                'flour_consumed' => 0
            ];
        } catch (Exception $e) {
            return ['famine' => false, 'casualties' => 0, 'flour_consumed' => 0];
        }
    }

    /**
     * Calcule l'effectif des troupes d'élite et le besoin horaire en farine pour un fief
     */
    public function getEliteUnitsUpkeep(int $planetId): array {
        try {
            $stmtUnits = $this->db->prepare("
                SELECT pu.count, u.tier, u.name, u.icon
                FROM planet_units pu
                JOIN units u ON pu.unit_code = u.code
                WHERE pu.planet_id = ? AND pu.count > 0 AND u.tier >= 2
            ");
            $stmtUnits->execute([$planetId]);
            $rows = $stmtUnits->fetchAll();

            $totalElite = 0;
            foreach ($rows as $r) {
                $totalElite += (int)$r['count'];
            }

            $famineEnabled = (bool)GameConfig::get('famine_enabled', false);
            $ratePer100 = (float)GameConfig::get('famine_flour_consumption', 1.0);
            $flourPerHour = ($totalElite / 100.0) * $ratePer100;

            return [
                'famine_enabled' => $famineEnabled,
                'elite_units_count' => $totalElite,
                'flour_consumption_per_hour' => round($flourPerHour, 2),
                'famine_rate' => (float)GameConfig::get('famine_rate', 3.0),
                'units' => $rows
            ];
        } catch (Exception $e) {
            return [
                'famine_enabled' => false,
                'elite_units_count' => 0,
                'flour_consumption_per_hour' => 0,
                'famine_rate' => 3.0,
                'units' => []
            ];
        }
    }

    /**
     * Récupère les 18 parcelles d'une planète
     */
    public function getFields(int $planetId): array {
        $stmt = $this->db->prepare("SELECT * FROM planet_fields WHERE planet_id = ? ORDER BY field_slot ASC");
        $stmt->execute([$planetId]);
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            require_once __DIR__ . '/VillageFieldGenerator.php';
            VillageFieldGenerator::populatePlanetFields($this->db, $planetId, null, 1, false);
            $stmt->execute([$planetId]);
            $rows = $stmt->fetchAll();
        }
        return $rows;
    }

    /**
     * Récupère la liste des bâtiments d'une planète sous forme ['hq' => 3, 'storage' => 2, ...]
     */
    public function getBuildings(int $planetId): array {
        $stmt = $this->db->prepare("SELECT building_type, level FROM planet_buildings WHERE planet_id = ?");
        $stmt->execute([$planetId]);
        $rows = $stmt->fetchAll();
        $buildings = [];
        foreach ($rows as $row) {
            $buildings[$row['building_type']] = (int)$row['level'];
        }
        return $buildings;
    }

    /**
     * Récupère la cartographie des emplacements urbains (Slots 19 à 34) d'une cité
     * Retourne pour chaque slot le bâtiment qui y est affecté, son niveau et son statut
     */
    public function getCitySlotMap(int $planetId): array {
        $stmt = $this->db->prepare("
            SELECT id, slot, building_type, level
            FROM planet_buildings
            WHERE planet_id = ?
        ");
        $stmt->execute([$planetId]);
        $rows = $stmt->fetchAll();

        $buildingsByCode = [];
        $rowsBySlot = [];
        foreach ($rows as $r) {
            $buildingsByCode[$r['building_type']] = $r;
            if (!empty($r['slot'])) {
                $rowsBySlot[(int)$r['slot']] = $r;
            }
        }

        $defaultLayout = CITY_SLOT_LAYOUT;
        $slots = [];
        for ($s = 19; $s <= 34; $s++) {
            $code = $defaultLayout[$s] ?? 'free_plot';
            $level = 0;
            if (isset($rowsBySlot[$s])) {
                $code = $rowsBySlot[$s]['building_type'];
                $level = (int)$rowsBySlot[$s]['level'];
            } elseif (isset($buildingsByCode[$code])) {
                $level = (int)$buildingsByCode[$code]['level'];
                $this->db->prepare("UPDATE planet_buildings SET slot = ? WHERE id = ?")->execute([$s, $buildingsByCode[$code]['id']]);
            }

            $slots[$s] = [
                'slot' => $s,
                'code' => $code,
                'level' => $level
            ];
        }

        return $slots;
    }

    /**
     * Calcule la production horaire selon les niveaux de mines, l'énergie et les oasis annexées
     */
    public function calculateProduction(array $fields, ?int $planetId = null): array {
        $speed = (float)GameConfig::get('resource_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5);
        $metalBase = 20 * $speed;
        $crystalBase = 15 * $speed;
        $deutBase = 10 * $speed;

        $metalMineProd = 0;
        $crystalMineProd = 0;
        $deutSynthProd = 0;

        $energyMax = 20; // Énergie passive de la planète
        $energyUsed = 0;

        $hasRuralPlots = false;
        $clayHourlyProd = 0;
        $teaHourlyProd = 0;
        $soyHourlyProd = 0;
        if ($planetId !== null && $planetId > 0) {
            try {
                require_once __DIR__ . '/VillageGeneratorService.php';
                $stmtPlots = $this->db->prepare("SELECT structure_type, level, prod_hourly FROM planet_rural_plots WHERE planet_id = ?");
                $stmtPlots->execute([$planetId]);
                $rPlots = $stmtPlots->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rPlots)) {
                    $hasRuralPlots = true;
                    foreach ($rPlots as $rp) {
                        $sType = $rp['structure_type'];
                        $rLvl = (int)$rp['level'];
                        // Calcul dynamique de la cadence horaire indexée sur resource_speed
                        $sProd = VillageGeneratorService::calculateHourlyProduction($sType, $rLvl, $speed);
                        
                        // Consommation de ferveur spirituelle / énergie divine des parcelles en activité
                        $energyUsed += VillageGeneratorService::calculateHourlyEnergyConsumption($sType, $rLvl);

                        switch ($sType) {
                            case 'foret':
                                $metalMineProd += $sProd;
                                break;
                            case 'carriere':
                                $crystalMineProd += $sProd;
                                break;
                            case 'riziere':
                                $deutSynthProd += $sProd;
                                break;
                            case 'sanctuaire_shinto':
                                $energyMax += (int)$sProd;
                                break;
                            case 'fosse_argile':
                                $clayHourlyProd += $sProd;
                                break;
                            case 'culture_the':
                                $teaHourlyProd += $sProd;
                                break;
                            case 'champ_soja':
                                $soyHourlyProd += $sProd;
                                break;
                        }
                    }
                }
            } catch (Exception $e) {}
        }

        if (!$hasRuralPlots) {
            foreach ($fields as $f) {
                $lvl = (int)$f['level'];
                if ($lvl === 0) continue;

                switch ($f['type']) {
                    case 'solar_plant':
                        $energyMax += (int)(FIELD_TYPES['solar_plant']['base_prod'] * $lvl * pow(1.12, $lvl));
                        break;
                    case 'metal_mine':
                        $metalMineProd += FIELD_TYPES['metal_mine']['base_prod'] * $lvl * pow(1.15, $lvl);
                        $energyUsed += (int)(FIELD_TYPES['metal_mine']['base_energy_cons'] * $lvl * pow(1.1, $lvl));
                        break;
                    case 'crystal_mine':
                        $crystalMineProd += FIELD_TYPES['crystal_mine']['base_prod'] * $lvl * pow(1.15, $lvl);
                        $energyUsed += (int)(FIELD_TYPES['crystal_mine']['base_energy_cons'] * $lvl * pow(1.1, $lvl));
                        break;
                    case 'deuterium_synth':
                        $deutSynthProd += FIELD_TYPES['deuterium_synth']['base_prod'] * $lvl * pow(1.15, $lvl);
                        $energyUsed += (int)(FIELD_TYPES['deuterium_synth']['base_energy_cons'] * $lvl * pow(1.1, $lvl));
                        break;
                }
            }
        }

        // Bonus des édifices urbains de spécialisation (Style Travian : Scierie, Briqueterie, Moulin, Pavillon de Thé)
        $sawmillMult = 1.0;
        $stonemasonMult = 1.0;
        $grainMillMult = 1.0;
        $teahouseLvl = 0;
        $workforceRatio = 1.0;
        $workforceSummary = null;

        if ($planetId !== null && $planetId > 0) {
            try {
                $buildings = $this->getBuildings($planetId);
                $sawmillLvl = (int)($buildings['sawmill'] ?? 0);
                $stonemasonLvl = (int)($buildings['stonemason'] ?? 0);
                $grainMillLvl = (int)($buildings['grain_mill'] ?? 0);
                $teahouseLvl = (int)($buildings['teahouse'] ?? 0);

                if ($sawmillLvl > 0) $sawmillMult += ($sawmillLvl * 0.05);
                if ($stonemasonLvl > 0) $stonemasonMult += ($stonemasonLvl * 0.05);
                if ($grainMillLvl > 0) $grainMillMult += ($grainMillLvl * 0.05);
                if ($teahouseLvl > 0) $energyMax = (int)($energyMax * (1.0 + ($teahouseLvl * 0.05)));

                // Calcul du quota de main-d'œuvre et malus en cas de sous-effectif
                $stmtPop = $this->db->prepare("SELECT id, population, rice_flour, deuterium, sake, energy_used, energy_max FROM planets WHERE id = ?");
                $stmtPop->execute([$planetId]);
                $pRow = $stmtPop->fetch(PDO::FETCH_ASSOC);
                if ($pRow) {
                    $pRow['id'] = (int)$planetId;
                    $maxPop = $this->calculateMaxPopulation($buildings, $fields, $planetId);
                    $workforceSummary = PopulationEngine::calculateWorkforceSummary($pRow, $buildings, $fields, $maxPop);
                    $workforceRatio = $workforceSummary['workforce_ratio'];
                }
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        // Ratio énergétique :
        // Si l'énergie disponible est négative (consommation > production disponible),
        // la production de ressources ne fonctionne qu'à 10% (0.10)
        $energyRatio = 1.0;
        if ($energyUsed > $energyMax) {
            $energyRatio = 0.10; // Règle stricte : 10% de production en cas de déficit énergétique
        }

        // Bonus d'Oasis annexées (Style Travian : +25% ou +50% sur Bois, Pierre, Riz)
        $oasisBonusMult = ['wood' => 1.0, 'stone' => 1.0, 'rice' => 1.0];
        $oasisBonuses = ['wood' => 0, 'stone' => 0, 'rice' => 0];
        if ($planetId !== null && $planetId > 0) {
            try {
                require_once __DIR__ . '/OasisEngine.php';
                $oasisEngine = new OasisEngine();
                $oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet($planetId);
                $oasisBonusMult['wood'] += ($oasisBonuses['wood'] / 100.0);
                $oasisBonusMult['stone'] += ($oasisBonuses['stone'] / 100.0);
                $oasisBonusMult['rice'] += ($oasisBonuses['rice'] / 100.0);
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        // Bonus de production du Samouraï Héros stationné au domaine (Style Travian)
        $heroProdBonus = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        if ($planetId !== null && $planetId > 0) {
            try {
                require_once __DIR__ . '/HeroEngine.php';
                $heroEngine = new HeroEngine();
                $heroProdBonus = $heroEngine->getHeroProductionBonus($planetId);
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        // Bonus du Matsuri Populaire (Célébration au Tenshu alimentée par le Saké)
        $matsuriBonusMult = 1.0;
        $matsuriBonusPct = 0;
        if ($planetId !== null && $planetId > 0) {
            try {
                $activeFeast = $this->getActiveFeast($planetId);
                if ($activeFeast && $activeFeast['feast_type'] === 'matsuri') {
                    $tLvl = (int)($activeFeast['tenshu_level'] ?? 1);
                    $matsuriBonusMult = 1.0 + 0.05 + ($tLvl * 0.01);
                    $matsuriBonusPct = (int)round(($matsuriBonusMult - 1.0) * 100);
                    $energyMax += (10 + ($tLvl * 2));
                }
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        $effectiveMetalProd = $hasRuralPlots ? ($metalBase + $metalMineProd) : ($metalBase + ($metalMineProd * $speed));
        $effectiveCrystalProd = $hasRuralPlots ? ($crystalBase + $crystalMineProd) : ($crystalBase + ($crystalMineProd * $speed));
        $effectiveDeutProd = $hasRuralPlots ? ($deutBase + $deutSynthProd) : ($deutBase + ($deutSynthProd * $speed));
        $effectiveClayProd = $clayHourlyProd * $energyRatio * $workforceRatio * $matsuriBonusMult;
        $effectiveTeaProd = $teaHourlyProd * $energyRatio * $workforceRatio * $matsuriBonusMult;
        $effectiveSoyProd = $soyHourlyProd * $energyRatio * $workforceRatio * $matsuriBonusMult;

        return [
            'metal' => (int)((($effectiveMetalProd * $energyRatio * $workforceRatio * $oasisBonusMult['wood'] * $sawmillMult) * $matsuriBonusMult) + $heroProdBonus['metal']),
            'crystal' => (int)((($effectiveCrystalProd * $energyRatio * $workforceRatio * $oasisBonusMult['stone'] * $stonemasonMult) * $matsuriBonusMult) + $heroProdBonus['crystal']),
            'deuterium' => (int)((($effectiveDeutProd * $energyRatio * $workforceRatio * $oasisBonusMult['rice'] * $grainMillMult) * $matsuriBonusMult) + $heroProdBonus['deuterium']),
            'clay' => (int)$effectiveClayProd,
            'tea' => (int)$effectiveTeaProd,
            'soybean' => (int)$effectiveSoyProd,
            'energy_max' => $energyMax,
            'energy_used' => $energyUsed,
            'energy_ratio' => $energyRatio,
            'workforce_ratio' => $workforceRatio,
            'workforce' => $workforceSummary,
            'oasis_bonuses' => $oasisBonuses,
            'hero_bonuses' => $heroProdBonus,
            'matsuri_bonus' => $matsuriBonusPct,
            'building_bonuses' => [
                'sawmill' => (int)round(($sawmillMult - 1.0) * 100),
                'stonemason' => (int)round(($stonemasonMult - 1.0) * 100),
                'grain_mill' => (int)round(($grainMillMult - 1.0) * 100),
                'teahouse' => (int)($teahouseLvl * 5)
            ]
        ];
    }

    /**
     * Capacité protégée de la Soute Quantique
     */
    public function getVaultCapacity(int $planetId, string $faction): int {
        $buildings = $this->getBuildings($planetId);
        $vaultLvl = $buildings['quantum_vault'] ?? 0;
        if ($vaultLvl <= 0) return 100; // Protection passive minimale
        $capacity = (int)(500 * pow(1.5, $vaultLvl));
        if ($faction === 'aethelis') {
            $capacity *= 2; // Bonus racial Aethelis
        }
        return $capacity;
    }

    /**
     * Assure la présence des colonnes sake, rice_flour, population et des tables requises
     * Auto-migration transparente au runtime si les colonnes manquent dans la base MySQL
     */
    public function ensureSchemaMigration(bool $force = false): void {
        static $executed = false;
        if ($executed && !$force) return;
        if ($this->db->inTransaction()) return;
        $executed = true;

        try {
            // 1. Colonne sake, rice_flour, wooden_beams, clay, tea, soybean sur planets
            $stmt = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'sake'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `planets`
                    ADD COLUMN `sake` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `rice_flour` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `sake_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `rice_flour_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `wooden_beams` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `wooden_beams_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `clay` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `clay_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `tea` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `tea_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `soybean` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `soybean_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `population` INT UNSIGNED NOT NULL DEFAULT 100,
                    ADD COLUMN `contentment` TINYINT UNSIGNED NOT NULL DEFAULT 85
                ");
            } else {
                $stmtFlour = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'rice_flour'");
                if ($stmtFlour && $stmtFlour->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `rice_flour` DOUBLE NOT NULL DEFAULT 0");
                }
                $stmtBeams = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'wooden_beams'");
                if ($stmtBeams && $stmtBeams->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `wooden_beams` DOUBLE NOT NULL DEFAULT 0, ADD COLUMN `wooden_beams_max` INT UNSIGNED NOT NULL DEFAULT 10000");
                }
                $stmtCharcoal = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'charcoal'");
                if ($stmtCharcoal && $stmtCharcoal->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `charcoal` DOUBLE NOT NULL DEFAULT 0, ADD COLUMN `charcoal_max` INT UNSIGNED NOT NULL DEFAULT 10000");
                }
                $stmtClay = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'clay'");
                if ($stmtClay && $stmtClay->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `clay` DOUBLE NOT NULL DEFAULT 0, ADD COLUMN `clay_max` INT UNSIGNED NOT NULL DEFAULT 10000");
                }
                $stmtTea = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'tea'");
                if ($stmtTea && $stmtTea->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `tea` DOUBLE NOT NULL DEFAULT 0, ADD COLUMN `tea_max` INT UNSIGNED NOT NULL DEFAULT 10000");
                }
                $stmtSoy = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'soybean'");
                if ($stmtSoy && $stmtSoy->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `soybean` DOUBLE NOT NULL DEFAULT 0, ADD COLUMN `soybean_max` INT UNSIGNED NOT NULL DEFAULT 10000");
                }
                $stmtPop = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'population'");
                if ($stmtPop && $stmtPop->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `population` INT UNSIGNED NOT NULL DEFAULT 100");
                }
                $stmtCont = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'contentment'");
                if ($stmtCont && $stmtCont->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `contentment` TINYINT UNSIGNED NOT NULL DEFAULT 85");
                }
            }

            // 2. Colonne de units
            $stmtUnits = $this->db->query("SHOW COLUMNS FROM `units` LIKE 'rice_flour_cost'");
            if ($stmtUnits && $stmtUnits->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `units` ADD COLUMN `rice_flour_cost` INT UNSIGNED NOT NULL DEFAULT 0");
                $this->db->exec("UPDATE `units` SET `rice_flour_cost` = 15 WHERE `tier` = 2 AND `faction` != 'all'");
                $this->db->exec("UPDATE `units` SET `rice_flour_cost` = 35 WHERE `tier` = 3 AND `faction` != 'all'");
                $this->db->exec("UPDATE `units` SET `rice_flour_cost` = 75 WHERE `tier` = 4 AND `faction` != 'all'");
            }

            // 3. Table planet_feasts
            $this->db->exec("CREATE TABLE IF NOT EXISTS `planet_feasts` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `planet_id` INT UNSIGNED NOT NULL,
                `feast_type` VARCHAR(40) NOT NULL,
                `tenshu_level` INT UNSIGNED NOT NULL DEFAULT 1,
                `started_at` INT UNSIGNED NOT NULL,
                `finishes_at` INT UNSIGNED NOT NULL,
                INDEX `idx_pf_planet` (`planet_id`),
                INDEX `idx_pf_finishes` (`finishes_at`),
                CONSTRAINT `fk_pf_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 4. Table craft_queue (file de raffinage de la meunerie et charpenterie)
            $this->db->exec("CREATE TABLE IF NOT EXISTS `craft_queue` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `planet_id` INT UNSIGNED NOT NULL,
                `building_type` VARCHAR(30) NOT NULL DEFAULT 'grain_mill',
                `product` VARCHAR(30) NOT NULL,
                `cost_resource` VARCHAR(30) NOT NULL DEFAULT 'deuterium',
                `cost_amount` DOUBLE NOT NULL DEFAULT 0,
                `rice_amount` DOUBLE NOT NULL DEFAULT 0,
                `produced_amount` INT UNSIGNED NOT NULL,
                `started_at` INT UNSIGNED NOT NULL,
                `finishes_at` INT UNSIGNED NOT NULL,
                INDEX `idx_cq_planet` (`planet_id`),
                INDEX `idx_cq_building` (`building_type`),
                INDEX `idx_cq_finishes` (`finishes_at`),
                CONSTRAINT `fk_cq_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmtCqB = $this->db->query("SHOW COLUMNS FROM `craft_queue` LIKE 'building_type'");
            if ($stmtCqB && $stmtCqB->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `craft_queue`
                    ADD COLUMN `building_type` VARCHAR(30) NOT NULL DEFAULT 'grain_mill',
                    ADD COLUMN `cost_resource` VARCHAR(30) NOT NULL DEFAULT 'deuterium',
                    ADD COLUMN `cost_amount` DOUBLE NOT NULL DEFAULT 0,
                    ADD INDEX `idx_cq_building` (`building_type`)
                ");
            }

            // 5. Colonnes famine sur planets
            $stmtFamine = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'famine_active'");
            if ($stmtFamine && $stmtFamine->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `planets`
                    ADD COLUMN `famine_active` TINYINT(1) NOT NULL DEFAULT 0,
                    ADD COLUMN `last_famine_losses` INT UNSIGNED NOT NULL DEFAULT 0
                ");
            }

            // 6. Initialisation des paramètres de famine dans game_settings si absents
            $this->db->exec("INSERT IGNORE INTO `game_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
                ('famine_enabled', '0', 'boolean', 'Active ou désactive la famine féodale si le stock de farine est épuisé'),
                ('famine_rate', '3.0', 'float', 'Pourcentage horaire de pertes/désertion des troupes d\'élite en famine'),
                ('famine_flour_consumption', '1.0', 'float', 'Farine nécessaire par heure pour 100 soldats d\'élite')
            ");

            // 7. Colonne founder_planet_id sur planets pour tracer le village d'origine des colons
            $stmtFounder = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'founder_planet_id'");
            if ($stmtFounder && $stmtFounder->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `planets` ADD COLUMN `founder_planet_id` INT UNSIGNED DEFAULT NULL, ADD INDEX `idx_founder_planet` (`founder_planet_id`)");
            }

            // 8. Unité colonizer dans la table units
            $stmtCol = $this->db->query("SELECT code FROM `units` WHERE `code` = 'colonizer'");
            if ($stmtCol && $stmtCol->rowCount() === 0) {
                $this->db->exec("INSERT INTO `units`
                    (`code`, `name`, `faction`, `tier`, `icon`, `image`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `rice_flour_cost`, `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, `base_train_time`, `description`)
                    VALUES
                    ('colonizer', 'Pionnier Féodal (Colon)', 'all', 3, '⛩️', 'expedition_etablissement_castral.jpg', 4500, 4000, 4500, 150, 10, 30, 20, 4, 3000, 7200, 'Troupe de pionniers et maîtres charpentiers équipés pour fonder un nouveau village castral indépendant.')
                ");
            }

            // 9. Assurer qu'au moins un village par joueur possède is_capital = 1 (le premier créé)
            $this->db->exec("
                UPDATE planets p
                JOIN (
                    SELECT user_id, MIN(id) as first_id
                    FROM planets
                    WHERE user_id IS NOT NULL
                    GROUP BY user_id
                ) fp ON p.id = fp.first_id
                SET p.is_capital = 1
                WHERE p.user_id IS NOT NULL
                  AND p.user_id NOT IN (SELECT DISTINCT user_id FROM (SELECT user_id FROM planets WHERE is_capital = 1) existing_cap)
            ");

            // 10. Colonne total_count pour la génération au fil de l'eau dans barracks_queue et shipyard_queue
            $stmtBQ = $this->db->query("SHOW COLUMNS FROM `barracks_queue` LIKE 'total_count'");
            if ($stmtBQ && $stmtBQ->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `barracks_queue` ADD COLUMN `total_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `count`");
                $this->db->exec("UPDATE `barracks_queue` SET `total_count` = `count` WHERE `total_count` = 0");
            }

            $stmtSQ = $this->db->query("SHOW COLUMNS FROM `shipyard_queue` LIKE 'total_count'");
            if ($stmtSQ && $stmtSQ->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `shipyard_queue` ADD COLUMN `total_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `count`");
                $this->db->exec("UPDATE `shipyard_queue` SET `total_count` = `count` WHERE `total_count` = 0");
            }

            // 11. Synchronisation de la production horaire des parcelles rurales avec resource_speed
            try {
                $stmtCheck = $this->db->query("SELECT id, structure_type, level, prod_hourly FROM planet_rural_plots WHERE structure_type NOT IN ('tenshu', 'village', 'sanctuaire_shinto') LIMIT 1");
                if ($stmtCheck && ($rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC))) {
                    $speed = (float)GameConfig::get('resource_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5);
                    $expectedSample = round(35.0 * pow((int)$rowCheck['level'], 1.38) * (1.0 + ((int)$rowCheck['level'] * 0.015)) * $speed, 1);
                    if (abs((float)$rowCheck['prod_hourly'] - $expectedSample) > 1.0) {
                        require_once __DIR__ . '/VillageGeneratorService.php';
                        $stmtAllPlots = $this->db->query("SELECT id, structure_type, level FROM planet_rural_plots");
                        if ($stmtAllPlots) {
                            $upPlots = $this->db->prepare("UPDATE planet_rural_plots SET prod_hourly = ? WHERE id = ?");
                            while ($pr = $stmtAllPlots->fetch(PDO::FETCH_ASSOC)) {
                                $newP = VillageGeneratorService::calculateHourlyProduction($pr['structure_type'], (int)$pr['level'], $speed);
                                $upPlots->execute([$newP, $pr['id']]);
                            }
                        }
                    }
                }
            } catch (Exception $eSync) {}

            // 12. Colonnes fish et fish_max sur planets pour le système halieutique & lacs
            try {
                $stmtFish = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'fish'");
                if ($stmtFish && $stmtFish->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets`
                        ADD COLUMN `fish` DOUBLE NOT NULL DEFAULT 0,
                        ADD COLUMN `fish_max` INT UNSIGNED NOT NULL DEFAULT 15000
                    ");
                } else {
                    $stmtFishMax = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'fish_max'");
                    if ($stmtFishMax && $stmtFishMax->rowCount() === 0) {
                        $this->db->exec("ALTER TABLE `planets` ADD COLUMN `fish_max` INT UNSIGNED NOT NULL DEFAULT 15000");
                    }
                }
            } catch (Exception $eFish) {}

            // 13. Unité pecheur dans la table units
            try {
                $stmtPec = $this->db->query("SELECT code FROM `units` WHERE `code` = 'pecheur'");
                if ($stmtPec && $stmtPec->rowCount() === 0) {
                    $this->db->exec("INSERT INTO `units`
                        (`code`, `name`, `faction`, `tier`, `icon`, `image`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `rice_flour_cost`, `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, `base_train_time`, `description`)
                        VALUES
                        ('pecheur', 'Pêcheur Féodal (Ryōshi)', 'all', 1, '🐟', 'pecheur.jpg', 60, 40, 50, 0, 5, 10, 10, 14, 100, 45, 'Villageois spécialisé dans les sorties de pêche sur les lacs et eaux calmes pour ramener du poisson frais.')
                    ");
                }
            } catch (Exception $ePec) {}

            // 14. Technologie fishing_tech dans la table researches
            try {
                $stmtRes = $this->db->query("SELECT code FROM `researches` WHERE `code` = 'fishing_tech'");
                if ($stmtRes && $stmtRes->rowCount() === 0) {
                    $this->db->exec("INSERT INTO `researches`
                        (`code`, `name`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `base_time`, `description`)
                        VALUES
                        ('fishing_tech', 'Halieutique Féodale', 300, 250, 400, 120, 'Améliore les filets, nasses et techniques des pêcheurs villageois. Augmente de 25% la quantité de poisson frais pêchée par expédition.')
                    ");
                }
            } catch (Exception $eRes) {}

            // 15. Type de mission (VARCHAR 50 pour tous les types de missions sans conflit ni tronquage)
            try {
                $this->db->exec("ALTER TABLE `fleet_missions` MODIFY COLUMN `mission_type` VARCHAR(50) NOT NULL DEFAULT 'attack'");
            } catch (Exception $eEnum) {}

            // 16. Table lake_fish_stocks pour la gestion finie des réserves halieutiques
            try {
                require_once __DIR__ . '/LakeEngine.php';
                (new LakeEngine($this->db))->ensureSchema();
            } catch (Exception $eLake) {}

            // 17. Colonnes de minerais sulfur, nitrate, iron_sand sur planets
            try {
                $stmtSulfur = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'sulfur'");
                if ($stmtSulfur && $stmtSulfur->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets`
                        ADD COLUMN `sulfur` DOUBLE NOT NULL DEFAULT 0,
                        ADD COLUMN `sulfur_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                        ADD COLUMN `nitrate` DOUBLE NOT NULL DEFAULT 0,
                        ADD COLUMN `nitrate_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                        ADD COLUMN `iron_sand` DOUBLE NOT NULL DEFAULT 0,
                        ADD COLUMN `iron_sand_max` INT UNSIGNED NOT NULL DEFAULT 15000
                    ");
                }
            } catch (Exception $eMinPlanets) {}

            // 18. Unité mineur (Mineur des Cimes / Kōfu) dans la table units
            try {
                $stmtMineur = $this->db->query("SELECT code FROM `units` WHERE `code` = 'mineur'");
                if ($stmtMineur && $stmtMineur->rowCount() === 0) {
                    $this->db->exec("INSERT INTO `units`
                        (`code`, `name`, `faction`, `tier`, `icon`, `image`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `rice_flour_cost`, `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, `base_train_time`, `description`)
                        VALUES
                        ('mineur', 'Mineur des Cimes (Kōfu)', 'all', 1, '⛏️', 'mineur.jpg', 80, 50, 40, 0, 8, 12, 10, 12, 120, 50, 'Mineur aguerri aux parois rocheuses et crêtes volcaniques pour extraire le soufre, le nitrate et le sable de fer.')
                    ");
                }
            } catch (Exception $eMineur) {}

            // 19. Technologie mining_tech dans la table researches
            try {
                $stmtTechMine = $this->db->query("SELECT code FROM `researches` WHERE `code` = 'mining_tech'");
                if ($stmtTechMine && $stmtTechMine->rowCount() === 0) {
                    $this->db->exec("INSERT INTO `researches`
                        (`code`, `name`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `base_time`, `description`)
                        VALUES
                        ('mining_tech', 'Métallurgie & Géologie Féodale', 350, 300, 300, 150, 'Perfectionne le forage montagnard et l\'extraction minérale. Augmente de 25% les rendements de soufre, nitrate et sable de fer par expédition.')
                    ");
                }
            } catch (Exception $eMTech) {}

            // 20. Table mountain_mineral_stocks pour la gestion finie des gisements minéraux
            try {
                require_once __DIR__ . '/MountainEngine.php';
                (new MountainEngine($this->db))->ensureSchema();
            } catch (Exception $eMountain) {}

            // 21. Colonnes roof_tiles, roof_tiles_max et onigawara_tiles sur planets pour la toiture du Grand Dōjō (Tailleuse Niv. 20)
            try {
                $stmtTiles = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'roof_tiles'");
                if ($stmtTiles && $stmtTiles->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets`
                        ADD COLUMN `roof_tiles` DOUBLE NOT NULL DEFAULT 0,
                        ADD COLUMN `roof_tiles_max` INT UNSIGNED NOT NULL DEFAULT 15000,
                        ADD COLUMN `onigawara_tiles` INT UNSIGNED NOT NULL DEFAULT 0
                    ");
                }
            } catch (Exception $eTilesPlanets) {}
        } catch (Exception $e) {
            // Ignorer silencieusement si déjà en cours ou permissions limitées
        }
    }

    /**
     * Transformation / Raffinage du Riz en Saké ou Farine de Riz (Meunerie & Brasserie Sakagura)
     *
     * @param int $planetId Identifiant du fief / planète
     * @param string $product 'sake' ou 'rice_flour'
     * @param float $riceAmount Quantité de riz brut (deuterium) à raffiner
     * @return array Résultat de l'opération
     */
    public function calculateCraftDetails(int $planetId, string $product, float $riceAmount): array {
        $buildings = $this->getBuildings($planetId);
        $millLvl = (int)($buildings['grain_mill'] ?? 1);
        $planet = $this->getPlanet($planetId);

        $efficiencyMultiplier = 1.0 + ($millLvl * 0.02);

        if ($product === 'rice_flour') {
            $baseCostPerUnit = 5;
            $rawProduced = floor(($riceAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Farine de Riz (Komeko)";
            $productIcon = "🍚";
            $currentStock = (float)($planet['rice_flour'] ?? 0);
            $maxStock = (int)($planet['rice_flour_max'] ?? (10000 * pow(1.4, $millLvl)));
            $durationFactor = 0.4;
            $minDuration = 10;
        } else {
            $baseCostPerUnit = 10;
            $rawProduced = floor(($riceAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Saké Féodal";
            $productIcon = "🍶";
            $currentStock = (float)($planet['sake'] ?? 0);
            $maxStock = (int)($planet['sake_max'] ?? (10000 * pow(1.4, $millLvl)));
            $durationFactor = 0.8;
            $minDuration = 15;
        }

        $availableSpace = max(0, $maxStock - $currentStock);
        $actualProduced = min($rawProduced, $availableSpace);

        // Si la réserve limite la production, on n'utilise que le riz proportionnel
        $actualRiceEngaged = $riceAmount;
        if ($actualProduced < $rawProduced && $actualProduced > 0) {
            $actualRiceEngaged = ceil(($actualProduced / $efficiencyMultiplier) * $baseCostPerUnit);
        }

        // Vitesse du jeu
        $gameSpeed = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);
        if ($gameSpeed <= 0) $gameSpeed = 1;

        // Vitesse accélérée de 15% par niveau de meunerie
        $durationSeconds = max($minDuration, (int)round(($actualRiceEngaged * $durationFactor) / (1 + ($millLvl * 0.15)) / $gameSpeed));

        return [
            'product' => $product,
            'product_name' => $productName,
            'product_icon' => $productIcon,
            'mill_level' => $millLvl,
            'efficiency_multiplier' => $efficiencyMultiplier,
            'base_cost_per_unit' => $baseCostPerUnit,
            'raw_produced' => $rawProduced,
            'actual_produced' => $actualProduced,
            'current_stock' => $currentStock,
            'max_stock' => $maxStock,
            'available_space' => $availableSpace,
            'actual_rice_engaged' => $actualRiceEngaged,
            'duration_seconds' => $durationSeconds
        ];
    }

    /**
     * Vérifie si le propriétaire de la planète est un administrateur
     */
    public function isPlanetAdmin(int $planetId): bool {
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
     * Lance le raffinage de riz en Farine ou Saké avec durée de préparation
     *
     * @param int $planetId
     * @param string $product 'sake' ou 'rice_flour'
     * @param float $riceAmount Quantité de riz brut (deuterium) à engager
     * @return array Résultat de l'opération
     */
    public function craftRiceProduct(int $planetId, string $product, float $riceAmount): array {
        $this->ensureSchemaMigration();
        if (!in_array($product, ['sake', 'rice_flour'])) {
            return ['success' => false, 'error' => "Produit de raffinage invalide (Saké ou Farine uniquement)."];
        }

        $isAdmin = $this->isPlanetAdmin($planetId);
        $riceAmount = floor($riceAmount);
        if ($isAdmin && $riceAmount <= 0) {
            $riceAmount = ($product === 'sake') ? 250 : 100;
        }
        if ($riceAmount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité de riz valide supérieure à zéro."];
        }

        // 1. Vérifier le bâtiment Meunerie & Brasserie (Niveau 5 pour la farine, Niveau 10 pour le saké)
        $buildings = $this->getBuildings($planetId);
        $millLvl = (int)($buildings['grain_mill'] ?? 0);
        $minLvlRequired = ($product === 'sake') ? 10 : 5;
        if ($millLvl < $minLvlRequired) {
            $productLabel = ($product === 'sake') ? "le saké féodal" : "la farine de riz";
            return [
                'success' => false,
                'error' => "La Meunerie & Brasserie de riz (Sakagura) doit être érigée au Niveau {$minLvlRequired} minimum pour produire {$productLabel} (Niveau actuel : {$millLvl}/{$minLvlRequired})."
            ];
        }

        // Mode Administrateur : Raffinage instantané sans coût et sans délai
        if ($isAdmin) {
            $details = $this->calculateCraftDetails($planetId, $product, $riceAmount);
            $produced = max(1, (int)$details['raw_produced']);
            $now = time();
            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'grain_mill', ?, 'deuterium', 0, 0, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $product, $produced, $now - 2, $now - 1]);
            $this->processCraftQueue($planetId);
            $updatedPlanet = $this->getPlanet($planetId);
            $activeQueue = $this->getCraftQueue($planetId, 'grain_mill');

            return [
                'success' => true,
                'message' => "⚡ [Admin] Raffinage instantané de <strong>+" . number_format($produced) . " {$details['product_name']} {$details['product_icon']}</strong> achevé sans coût !",
                'product' => $product,
                'product_name' => $details['product_name'],
                'product_icon' => $details['product_icon'],
                'produced' => $produced,
                'consumed_rice' => 0,
                'duration_seconds' => 0,
                'craft' => $activeQueue[0] ?? null,
                'new_deuterium' => $updatedPlanet['deuterium'],
                'new_product_stock' => $updatedPlanet[$product] ?? 0,
                'planet' => $updatedPlanet
            ];
        }

        // 2. Traiter les productions terminées et vérifier la limite de la file
        $this->processCraftQueue($planetId);
        $activeQueue = $this->getCraftQueue($planetId);

        $planet = $this->getPlanet($planetId);
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);
        $maxQueue = $isSealActive ? 4 : 1;

        if (count($activeQueue) >= $maxQueue) {
            if ($maxQueue === 1) {
                $remaining = !empty($activeQueue[0]['time_remaining']) ? gmdate('i\m s\s', $activeQueue[0]['time_remaining']) : 'quelques instants';
                return [
                    'success' => false,
                    'error' => "Une cuvée ou mouture de raffinage est déjà en cours dans la Meunerie (temps restant : {$remaining}). Décrétez le <strong>Sceau Impérial (Privilège du Shōgun)</strong> pour débloquer la file d'attente automatique jusqu'à 4 commandes consécutives !"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "La file de raffinage de la Meunerie & Brasserie est déjà pleine (4/4 lots programmés). Attendez la finalisation d'un lot avant d'en ajouter un nouveau."
                ];
            }
        }

        // 3. Calculer les détails de production
        $details = $this->calculateCraftDetails($planetId, $product, $riceAmount);

        if ($details['actual_produced'] < 1) {
            return [
                'success' => false,
                'error' => "Quantité de riz insuffisante pour produire au moins 1 unité de {$details['product_name']} (minimum {$details['base_cost_per_unit']} Riz requis)."
            ];
        }

        if ($details['available_space'] <= 0) {
            return [
                'success' => false,
                'error' => "Vos réserves de {$details['product_name']} sont saturées (" . number_format($details['current_stock']) . "/" . number_format($details['max_stock']) . "). Augmentez le niveau de votre Meunerie ou consommez vos stocks."
            ];
        }

        // 4. Mettre à jour les ressources de la planète et vérifier le stock de riz
        $planet = $this->updatePlanet($planetId);
        $engagedRice = $details['actual_rice_engaged'];
        if ($planet['deuterium'] < $engagedRice) {
            return [
                'success' => false,
                'error' => "Stock de Riz insuffisant (" . number_format((int)$planet['deuterium']) . " disponible, " . number_format($engagedRice) . " requis)."
            ];
        }

        // 5. Engager les ressources et placer dans la file de raffinage (séquentielle)
        $now = time();
        if (empty($activeQueue)) {
            $startedAt = $now;
        } else {
            $lastFinish = 0;
            foreach ($activeQueue as $qItem) {
                if ((int)$qItem['finishes_at'] > $lastFinish) {
                    $lastFinish = (int)$qItem['finishes_at'];
                }
            }
            $startedAt = max($now, $lastFinish);
        }
        $finishesAt = $startedAt + $details['duration_seconds'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE planets
                SET deuterium = GREATEST(0, deuterium - ?)
                WHERE id = ? AND deuterium >= ?
            ");
            $stmt->execute([$engagedRice, $planetId, $engagedRice]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Échec de l'opération : stock de riz modifié entretemps."];
            }

            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'grain_mill', ?, 'deuterium', ?, ?, ?, ?, ?)
            ");
            $stmtQueue->execute([
                $planetId,
                $product,
                $engagedRice,
                $engagedRice,
                $details['actual_produced'],
                $startedAt,
                $finishesAt
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement du raffinage : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'grain_mill');

        $durationFmt = gmdate('i\m s\s', $details['duration_seconds']);
        return [
            'success' => true,
            'message' => "Le raffinage de <strong>+" . number_format($details['actual_produced']) . " {$details['product_name']} {$details['product_icon']}</strong> a débuté ! Durée de préparation : <strong>{$durationFmt}</strong> (consommé : -{$engagedRice} Riz 🌾).",
            'product' => $product,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'consumed_rice' => $engagedRice,
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'new_deuterium' => $updatedPlanet['deuterium'],
            'new_product_stock' => $updatedPlanet[$product] ?? 0,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Façonnage de Bois de Cèdre en Poutres en bois ou Charbon de bois (Atelier de Charpenterie Kizukuri)
     */
    public function calculateWoodCraftDetails(int $planetId, string $product, float $woodAmount): array {
        $buildings = $this->getBuildings($planetId);
        $sawmillLvl = (int)($buildings['sawmill'] ?? 1);
        $planet = $this->getPlanet($planetId);

        $isCharcoal = ($product === 'charcoal');
        $efficiencyMultiplier = 1.0 + ($sawmillLvl * 0.02);

        if ($isCharcoal) {
            $baseCostPerUnit = 1; // 1 Bois de Cèdre pour 1 Charbon de bois
            $rawProduced = floor(($woodAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Charbon de bois";
            $productIcon = "🪨";
            $currentStock = (float)($planet['charcoal'] ?? 0);
            $maxStock = (int)($planet['charcoal_max'] ?? (10000 * pow(1.4, max(1, $sawmillLvl))));
        } else {
            $baseCostPerUnit = 10; // 10 Bois de Cèdre pour 1 Poutre en bois
            $rawProduced = floor(($woodAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Poutres en bois";
            $productIcon = "🪵";
            $currentStock = (float)($planet['wooden_beams'] ?? 0);
            $maxStock = (int)($planet['wooden_beams_max'] ?? (10000 * pow(1.4, max(1, $sawmillLvl))));
        }

        $availableSpace = max(0, $maxStock - $currentStock);
        $actualProduced = min($rawProduced, $availableSpace);

        // Si la réserve limite la production, on n'utilise que le bois proportionnel
        $actualWoodEngaged = $woodAmount;
        if ($actualProduced < $rawProduced && $actualProduced > 0) {
            $actualWoodEngaged = ceil(($actualProduced / $efficiencyMultiplier) * $baseCostPerUnit);
        }

        $gameSpeed = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);
        if ($gameSpeed <= 0) $gameSpeed = 1;

        // Vitesse accélérée de 15% par niveau de charpenterie
        $durationFactor = $isCharcoal ? 0.35 : 0.5;
        $durationSeconds = max(10, (int)round(($actualWoodEngaged * $durationFactor) / (1 + ($sawmillLvl * 0.15)) / $gameSpeed));

        return [
            'product' => $product,
            'product_name' => $productName,
            'product_icon' => $productIcon,
            'sawmill_level' => $sawmillLvl,
            'efficiency_multiplier' => $efficiencyMultiplier,
            'base_cost_per_unit' => $baseCostPerUnit,
            'raw_produced' => $rawProduced,
            'actual_produced' => $actualProduced,
            'current_stock' => $currentStock,
            'max_stock' => $maxStock,
            'available_space' => $availableSpace,
            'actual_wood_engaged' => $actualWoodEngaged,
            'duration_seconds' => $durationSeconds
        ];
    }

    /**
     * Lance le façonnage de Bois de Cèdre (Poutres ou Charbon) dans la Charpenterie
     */
    public function craftWoodProduct(int $planetId, string $product, float $woodAmount): array {
        $this->ensureSchemaMigration();
        if (!in_array($product, ['wooden_beams', 'charcoal'])) {
            return ['success' => false, 'error' => "Produit de charpente invalide (Poutres en bois ou Charbon de bois uniquement)."];
        }

        $isAdmin = $this->isPlanetAdmin($planetId);
        $woodAmount = floor($woodAmount);
        if ($isAdmin && $woodAmount <= 0) {
            $woodAmount = 100;
        }
        if ($woodAmount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité de bois valide supérieure à zéro."];
        }

        // 1. Vérifier le bâtiment Atelier de Charpenterie (Niveau 10 minimum pour les poutres, Niveau 15 pour le charbon)
        $buildings = $this->getBuildings($planetId);
        $sawmillLvl = (int)($buildings['sawmill'] ?? 0);
        if (!$isAdmin && $product === 'wooden_beams' && $sawmillLvl < 10) {
            return ['success' => false, 'error' => "L'Atelier de Charpenterie (Kizukuri) doit être érigé au Niveau 10 minimum pour façonner des poutres en bois (Niveau actuel : {$sawmillLvl}/10)."];
        }
        if (!$isAdmin && $product === 'charcoal' && $sawmillLvl < 15) {
            return ['success' => false, 'error' => "L'Atelier de Charpenterie (Kizukuri) doit être érigé au Niveau 15 minimum pour produire du charbon de bois (Niveau actuel : {$sawmillLvl}/15)."];
        }

        // Mode Administrateur : Façonnage instantané sans coût et sans délai
        if ($isAdmin) {
            $details = $this->calculateWoodCraftDetails($planetId, $product, $woodAmount);
            $produced = max(1, (int)$details['raw_produced']);
            $now = time();
            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'sawmill', ?, 'metal', 0, 0, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $product, $produced, $now - 2, $now - 1]);
            $this->processCraftQueue($planetId);
            $updatedPlanet = $this->getPlanet($planetId);
            $activeQueue = $this->getCraftQueue($planetId, 'sawmill');
            $newStock = ($product === 'charcoal') ? ($updatedPlanet['charcoal'] ?? 0) : ($updatedPlanet['wooden_beams'] ?? 0);

            return [
                'success' => true,
                'message' => "⚡ [Admin] Façonnage instantané de <strong>+" . number_format($produced) . " {$details['product_name']} {$details['product_icon']}</strong> achevé sans coût !",
                'product' => $product,
                'product_name' => $details['product_name'],
                'product_icon' => $details['product_icon'],
                'produced' => $produced,
                'consumed_wood' => 0,
                'duration_seconds' => 0,
                'craft' => $activeQueue[0] ?? null,
                'new_metal' => $updatedPlanet['metal'],
                'new_product_stock' => $newStock,
                'planet' => $updatedPlanet
            ];
        }

        // 2. Traiter les productions terminées et vérifier la limite de la file
        $this->processCraftQueue($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'sawmill');

        $planet = $this->getPlanet($planetId);
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);
        $maxQueue = $isSealActive ? 4 : 1;

        if (count($activeQueue) >= $maxQueue) {
            if ($maxQueue === 1) {
                $remaining = !empty($activeQueue[0]['time_remaining']) ? gmdate('i\m s\s', $activeQueue[0]['time_remaining']) : 'quelques instants';
                return [
                    'success' => false,
                    'error' => "Une tâche de façonnage est déjà en cours dans la Charpenterie (temps restant : {$remaining}). Décrétez le <strong>Sceau Impérial (Privilège du Shōgun)</strong> pour débloquer la file d'attente automatique jusqu'à 4 commandes consécutives !"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "La file de façonnage de l'Atelier de Charpenterie est déjà pleine (4/4 lots programmés). Attendez la finalisation d'un lot avant d'en ajouter un nouveau."
                ];
            }
        }

        // 3. Calculer les détails de production
        $details = $this->calculateWoodCraftDetails($planetId, $product, $woodAmount);

        if ($details['actual_produced'] < 1) {
            return [
                'success' => false,
                'error' => "Quantité de bois insuffisante pour produire au moins 1 unité de {$details['product_name']} (minimum {$details['base_cost_per_unit']} Bois requis)."
            ];
        }

        if ($details['available_space'] <= 0) {
            return [
                'success' => false,
                'error' => "Vos réserves de {$details['product_name']} sont saturées (" . number_format($details['current_stock']) . "/" . number_format($details['max_stock']) . "). Augmentez le niveau de votre Atelier de Charpenterie."
            ];
        }

        // 4. Mettre à jour les ressources de la planète et vérifier le stock de bois
        $planet = $this->updatePlanet($planetId);
        $engagedWood = $details['actual_wood_engaged'];
        if ($planet['metal'] < $engagedWood) {
            return [
                'success' => false,
                'error' => "Stock de Bois de Cèdre insuffisant (" . number_format((int)$planet['metal']) . " disponible, " . number_format($engagedWood) . " requis)."
            ];
        }

        // 5. Engager les ressources et placer dans la file séquentielle de la charpenterie
        $now = time();
        if (empty($activeQueue)) {
            $startedAt = $now;
        } else {
            $lastFinish = 0;
            foreach ($activeQueue as $qItem) {
                if ((int)$qItem['finishes_at'] > $lastFinish) {
                    $lastFinish = (int)$qItem['finishes_at'];
                }
            }
            $startedAt = max($now, $lastFinish);
        }
        $finishesAt = $startedAt + $details['duration_seconds'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE planets
                SET metal = GREATEST(0, metal - ?)
                WHERE id = ? AND metal >= ?
            ");
            $stmt->execute([$engagedWood, $planetId, $engagedWood]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Échec de l'opération : stock de bois modifié entretemps."];
            }

            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'sawmill', ?, 'metal', ?, ?, ?, ?, ?)
            ");
            $stmtQueue->execute([
                $planetId,
                $product,
                $engagedWood,
                $engagedWood,
                $details['actual_produced'],
                $startedAt,
                $finishesAt
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement du façonnage : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'sawmill');

        $durationFmt = gmdate('i\m s\s', $details['duration_seconds']);
        $newStock = ($product === 'charcoal') ? ($updatedPlanet['charcoal'] ?? 0) : ($updatedPlanet['wooden_beams'] ?? 0);
        return [
            'success' => true,
            'message' => "La production de <strong>+" . number_format($details['actual_produced']) . " {$details['product_name']} {$details['product_icon']}</strong> a débuté ! Durée estimée : <strong>{$durationFmt}</strong> (consommé : -{$engagedWood} Bois 🪵).",
            'product' => $product,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'consumed_wood' => $engagedWood,
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'new_metal' => $updatedPlanet['metal'],
            'new_product_stock' => $newStock,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Calcule les détails de fabrication/raffinage d'un minerai (sulfur, nitrate, iron_sand)
     */
    public function calculateMineralCraftDetails(int $planetId, string $product, float $amount): array {
        $planet = $this->getPlanet($planetId);
        $buildings = $this->getBuildings($planetId);
        $stoneLvl = (int)($buildings['stonemason'] ?? 1);

        $recipes = [
            'sulfur' => [
                'name' => 'Soufre Volcanique',
                'icon' => '🌋',
                'cost_res' => 'crystal', // Pierre
                'cost_name' => 'Pierre de Granit 🪨',
                'ratio' => 4, // 4 Pierre -> 1 Soufre
                'duration_factor' => 0.5,
                'min_duration' => 15
            ],
            'nitrate' => [
                'name' => 'Nitrate de Potasse (Salpêtre)',
                'icon' => '🧪',
                'cost_res' => 'clay', // Argile
                'cost_name' => 'Argile Alluviale 🏺',
                'ratio' => 3, // 3 Argile -> 1 Nitrate
                'duration_factor' => 0.4,
                'min_duration' => 15
            ],
            'iron_sand' => [
                'name' => 'Sable de Fer (Satetsu 砂鉄)',
                'icon' => '💎',
                'cost_res' => 'crystal', // Pierre
                'cost_name' => 'Pierre de Granit 🪨',
                'ratio' => 5, // 5 Pierre -> 1 Sable de fer
                'duration_factor' => 0.6,
                'min_duration' => 20
            ]
        ];

        $recipe = $recipes[$product] ?? $recipes['sulfur'];
        $efficiencyMultiplier = 1.0 + ($stoneLvl * 0.02);
        $baseCostPerUnit = $recipe['ratio'];

        $rawProduced = floor(($amount / $baseCostPerUnit) * $efficiencyMultiplier);
        $currentStock = (float)($planet[$product] ?? 0);
        $maxStock = (int)($planet[$product . '_max'] ?? 10000);
        $availableSpace = max(0, $maxStock - $currentStock);
        $actualProduced = min($rawProduced, $availableSpace);

        $actualEngaged = $amount;
        if ($actualProduced < $rawProduced && $actualProduced > 0) {
            $actualEngaged = ceil(($actualProduced / $efficiencyMultiplier) * $baseCostPerUnit);
        }

        $gameSpeed = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);
        if ($gameSpeed <= 0) $gameSpeed = 1;

        $durationSeconds = max($recipe['min_duration'], (int)round(($actualEngaged * $recipe['duration_factor']) / (1 + ($stoneLvl * 0.15)) / $gameSpeed));

        return [
            'product' => $product,
            'product_name' => $recipe['name'],
            'product_icon' => $recipe['icon'],
            'cost_resource' => $recipe['cost_res'],
            'cost_name' => $recipe['cost_name'],
            'stone_level' => $stoneLvl,
            'efficiency_multiplier' => $efficiencyMultiplier,
            'base_cost_per_unit' => $baseCostPerUnit,
            'raw_produced' => $rawProduced,
            'actual_produced' => $actualProduced,
            'current_stock' => $currentStock,
            'max_stock' => $maxStock,
            'available_space' => $availableSpace,
            'actual_engaged' => $actualEngaged,
            'duration_seconds' => $durationSeconds
        ];
    }

    /**
     * Lance le raffinage artisanal de minerais (sulfur, nitrate, iron_sand)
     */
    public function craftMineralProduct(int $planetId, string $product, float $amount): array {
        $this->ensureSchemaMigration();
        if (!in_array($product, ['sulfur', 'nitrate', 'iron_sand'])) {
            return ['success' => false, 'error' => "Produit minéralier invalide (Soufre, Nitrate ou Sable de fer uniquement)."];
        }

        $isAdmin = $this->isPlanetAdmin($planetId);
        $amount = floor($amount);
        if ($isAdmin && $amount <= 0) {
            $amount = 100;
        }
        if ($amount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité valide supérieure à zéro."];
        }

        // Mode Administrateur : Raffinage minéral instantané sans coût et sans délai
        if ($isAdmin) {
            $details = $this->calculateMineralCraftDetails($planetId, $product, $amount);
            $produced = max(1, (int)$details['raw_produced']);
            $costRes = $details['cost_resource'];
            $now = time();
            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'stonemason', ?, ?, 0, 0, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $product, $costRes, $produced, $now - 2, $now - 1]);
            $this->processCraftQueue($planetId);
            $updatedPlanet = $this->getPlanet($planetId);
            $activeQueue = $this->getCraftQueue($planetId, 'stonemason');

            return [
                'success' => true,
                'message' => "⚡ [Admin] Synthèse instantanée de <strong>+" . number_format($produced) . " {$details['product_name']} {$details['product_icon']}</strong> achevée sans coût !",
                'product' => $product,
                'product_name' => $details['product_name'],
                'product_icon' => $details['product_icon'],
                'produced' => $produced,
                'consumed_amount' => 0,
                'consumed_resource' => $costRes,
                'duration_seconds' => 0,
                'craft' => $activeQueue[0] ?? null,
                'new_product_stock' => $updatedPlanet[$product] ?? 0,
                'planet' => $updatedPlanet
            ];
        }

        $this->processCraftQueue($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'stonemason');

        $planet = $this->getPlanet($planetId);
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);
        $maxQueue = $isSealActive ? 4 : 1;

        if (count($activeQueue) >= $maxQueue) {
            $remaining = !empty($activeQueue[0]['time_remaining']) ? gmdate('i\m s\s', $activeQueue[0]['time_remaining']) : 'quelques instants';
            return [
                'success' => false,
                'error' => "Une opération d'extraction/concassage est déjà en cours dans l'Atelier (temps restant : {$remaining})."
            ];
        }

        $details = $this->calculateMineralCraftDetails($planetId, $product, $amount);
        if ($details['actual_produced'] < 1) {
            return [
                'success' => false,
                'error' => "Quantité de matière insuffisante pour produire au moins 1 unité de {$details['product_name']}."
            ];
        }

        $costRes = $details['cost_resource'];
        $engagedCost = $details['actual_engaged'];
        $availableRes = (float)($planet[$costRes] ?? 0);

        if ($availableRes < $engagedCost) {
            return [
                'success' => false,
                'error' => "Stock insuffisant de {$details['cost_name']} (Requis : {$engagedCost}, Disponible : " . floor($availableRes) . ")."
            ];
        }

        $now = time();
        $startedAt = $now;
        if (!empty($activeQueue)) {
            $lastFinish = 0;
            foreach ($activeQueue as $qItem) {
                if ((int)$qItem['finishes_at'] > $lastFinish) {
                    $lastFinish = (int)$qItem['finishes_at'];
                }
            }
            $startedAt = max($now, $lastFinish);
        }
        $finishesAt = $startedAt + $details['duration_seconds'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE planets
                SET {$costRes} = GREATEST(0, {$costRes} - ?)
                WHERE id = ? AND {$costRes} >= ?
            ");
            $stmt->execute([$engagedCost, $planetId, $engagedCost]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Échec de l'opération : stock de matière modifié entretemps."];
            }

            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'stonemason', ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtQueue->execute([
                $planetId,
                $product,
                $costRes,
                $engagedCost,
                $engagedCost,
                $details['actual_produced'],
                $startedAt,
                $finishesAt
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement du craft minéral : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'stonemason');
        $durationFmt = gmdate('i\m s\s', $details['duration_seconds']);

        return [
            'success' => true,
            'message' => "La synthèse de <strong>+" . number_format($details['actual_produced']) . " {$details['product_name']} {$details['product_icon']}</strong> a débuté ! Durée : <strong>{$durationFmt}</strong>.",
            'product' => $product,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'consumed_amount' => $engagedCost,
            'consumed_resource' => $costRes,
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'new_product_stock' => $updatedPlanet[$product] ?? 0,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Calcule les détails de cuisson des tuiles impériales (Kawara-gama)
     * Débloqué STRICTEMENT au Niveau 20 de la Taille de Granit & Maçonnerie (stonemason)
     */
    public function calculateTileCraftDetails(int $planetId, string $tileType, float $batches): array {
        $planet = $this->getPlanet($planetId);
        $buildings = $this->getBuildings($planetId);
        $stoneLvl = (int)($buildings['stonemason'] ?? 0);

        $efficiencyMultiplier = 1.0 + ($stoneLvl * 0.02);
        $gameSpeed = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);
        if ($gameSpeed <= 0) $gameSpeed = 1;

        $batches = max(1, floor($batches));

        if ($tileType === 'onigawara_tiles') {
            // Tuiles Démoniaques d'Arête : 50 Argile + 30 Granit + 15 Charbon + 5 Sable de Fer -> 1 Onigawara
            $baseClay = 50;
            $baseCrystal = 30;
            $baseCharcoal = 15;
            $baseIronSand = 5;
            $yieldPerBatch = 1;

            $rawProduced = (int)floor($batches * $yieldPerBatch);
            $actualProduced = $rawProduced;
            $actualBatches = $batches;

            $engagedClay = $actualBatches * $baseClay;
            $engagedCrystal = $actualBatches * $baseCrystal;
            $engagedCharcoal = $actualBatches * $baseCharcoal;
            $engagedIronSand = $actualBatches * $baseIronSand;

            $durationSeconds = max(30, (int)round(($actualBatches * 60) / (1 + ($stoneLvl * 0.15)) / $gameSpeed));

            return [
                'product' => 'onigawara_tiles',
                'product_name' => 'Tuiles Démoniaques de Faîtage (Onigawara 鬼瓦)',
                'product_icon' => '👹',
                'stone_level' => $stoneLvl,
                'batches' => $actualBatches,
                'actual_produced' => $actualProduced,
                'raw_produced' => $rawProduced,
                'current_stock' => (float)($planet['onigawara_tiles'] ?? 0),
                'max_stock' => 999999,
                'available_space' => 999999,
                'engaged_clay' => $engagedClay,
                'engaged_crystal' => $engagedCrystal,
                'engaged_charcoal' => $engagedCharcoal,
                'engaged_iron_sand' => $engagedIronSand,
                'duration_seconds' => $durationSeconds
            ];
        }

        // Par défaut : Tuiles Communes (Hongawara - roof_tiles)
        // 1 lot de 10 Tuiles : 40 Argile + 20 Granit + 10 Charbon -> 10 Tuiles
        $baseClay = 40;
        $baseCrystal = 20;
        $baseCharcoal = 10;
        $yieldPerBatch = 10;

        $currentStock = (float)($planet['roof_tiles'] ?? 0);
        $maxStock = (int)($planet['roof_tiles_max'] ?? 15000);
        $availableSpace = max(0, $maxStock - $currentStock);

        $rawProduced = (int)floor($batches * $yieldPerBatch * $efficiencyMultiplier);
        $actualProduced = min($rawProduced, $availableSpace);

        $actualBatches = $batches;
        if ($actualProduced < $rawProduced && $actualProduced > 0) {
            $actualBatches = max(1, (int)ceil($actualProduced / ($yieldPerBatch * $efficiencyMultiplier)));
        }

        $engagedClay = $actualBatches * $baseClay;
        $engagedCrystal = $actualBatches * $baseCrystal;
        $engagedCharcoal = $actualBatches * $baseCharcoal;

        $durationSeconds = max(15, (int)round(($actualBatches * 35) / (1 + ($stoneLvl * 0.15)) / $gameSpeed));

        return [
            'product' => 'roof_tiles',
            'product_name' => 'Tuiles Impériales de Toiture (Hongawara 本瓦)',
            'product_icon' => '🧱',
            'stone_level' => $stoneLvl,
            'batches' => $actualBatches,
            'actual_produced' => $actualProduced,
            'raw_produced' => $rawProduced,
            'current_stock' => $currentStock,
            'max_stock' => $maxStock,
            'available_space' => $availableSpace,
            'engaged_clay' => $engagedClay,
            'engaged_crystal' => $engagedCrystal,
            'engaged_charcoal' => $engagedCharcoal,
            'duration_seconds' => $durationSeconds
        ];
    }

    /**
     * Lance la cuisson artisanale de tuiles dans le grand four Kawara-gama de la tailleuse
     * Strictement réservé au Niveau 20 de la Taille de Granit & Maçonnerie (stonemason)
     */
    public function craftTileProduct(int $planetId, string $tileType, float $batches): array {
        $this->ensureSchemaMigration();
        if (!in_array($tileType, ['roof_tiles', 'onigawara_tiles'])) {
            return ['success' => false, 'error' => "Type de tuile invalide (Hongawara ou Onigawara uniquement)."];
        }

        $buildings = $this->getBuildings($planetId);
        $stoneLvl = (int)($buildings['stonemason'] ?? 0);
        $isAdmin = $this->isPlanetAdmin($planetId);

        if ($stoneLvl < 20) {
            return [
                'success' => false,
                'error' => "Le grand fourneau Kawara-gama requiert impérativement une <strong>Taille de Granit & Maçonnerie de Niveau 20</strong> (Niveau actuel : {$stoneLvl}/20)."
            ];
        }

        $batches = floor($batches);
        if ($isAdmin && $batches <= 0) {
            $batches = 10;
        }
        if ($batches <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer un nombre de lots de tuiles supérieur à zéro."];
        }

        $details = $this->calculateTileCraftDetails($planetId, $tileType, $batches);

        if ($tileType === 'roof_tiles' && $details['available_space'] <= 0) {
            return [
                'success' => false,
                'error' => "Vos entrepôts de tuiles sont pleins (" . number_format($details['current_stock']) . " / " . number_format($details['max_stock']) . "). Acheminez-les vers le Grand Dōjō !"
            ];
        }

        // Mode Administrateur : Cuisson instantanée sans coût
        if ($isAdmin) {
            $produced = max(1, (int)$details['raw_produced']);
            $now = time();
            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'stonemason', ?, 'clay', 0, 0, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $tileType, $produced, $now - 2, $now - 1]);
            $this->processCraftQueue($planetId);
            $updatedPlanet = $this->getPlanet($planetId);
            $activeQueue = $this->getCraftQueue($planetId, 'stonemason');

            return [
                'success' => true,
                'message' => "⚡ [Admin] Cuisson impériale instantanée de <strong>+" . number_format($produced) . " {$details['product_name']} {$details['product_icon']}</strong> achevée sans coût !",
                'product' => $tileType,
                'product_name' => $details['product_name'],
                'product_icon' => $details['product_icon'],
                'produced' => $produced,
                'duration_seconds' => 0,
                'craft' => $activeQueue[0] ?? null,
                'new_product_stock' => $updatedPlanet[$tileType] ?? 0,
                'planet' => $updatedPlanet
            ];
        }

        $this->processCraftQueue($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'stonemason');

        $planet = $this->getPlanet($planetId);
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);
        $maxQueueSlots = $isSealActive ? 4 : 1;

        if (count($activeQueue) >= $maxQueueSlots) {
            $msg = $isSealActive
                ? "La file de cuisson des fours de maçonnerie est saturée (4/4 lots en cours)."
                : "Les fours de maçonnerie sont déjà occupés. Activez le Sceau Impérial pour ouvrir jusqu'à 4 files de cuisson simultanées.";
            return ['success' => false, 'error' => $msg];
        }

        // Vérification des matières premières
        $curClay = (float)($planet['clay'] ?? 0);
        $curCrystal = (float)($planet['crystal'] ?? 0);
        $curCharcoal = (float)($planet['charcoal'] ?? 0);
        $curIronSand = (float)($planet['iron_sand'] ?? 0);

        if ($curClay < $details['engaged_clay']) {
            return [
                'success' => false,
                'error' => "Argile alluviale insuffisante : requis " . number_format($details['engaged_clay']) . " 🏺 (disponible : " . number_format((int)$curClay) . ")."
            ];
        }
        if ($curCrystal < $details['engaged_crystal']) {
            return [
                'success' => false,
                'error' => "Pierre de granit insuffisante pour la chamotte : requis " . number_format($details['engaged_crystal']) . " 🪨 (disponible : " . number_format((int)$curCrystal) . ")."
            ];
        }
        if ($curCharcoal < $details['engaged_charcoal']) {
            return [
                'success' => false,
                'error' => "Charbon de bois insuffisant pour monter les fours à 1000°C : requis " . number_format($details['engaged_charcoal']) . " 🔥 (disponible : " . number_format((int)$curCharcoal) . ")."
            ];
        }
        if ($tileType === 'onigawara_tiles' && $curIronSand < ($details['engaged_iron_sand'] ?? 0)) {
            return [
                'success' => false,
                'error' => "Sable de fer (Satetsu) insuffisant pour le vernis protecteur : requis " . number_format($details['engaged_iron_sand']) . " 💎 (disponible : " . number_format((int)$curIronSand) . ")."
            ];
        }

        $now = time();
        $startedAt = $now;
        if (!empty($activeQueue)) {
            $lastCraft = end($activeQueue);
            $startedAt = max($now, (int)$lastCraft['finishes_at']);
        }
        $finishesAt = $startedAt + $details['duration_seconds'];

        $this->db->beginTransaction();
        try {
            if ($tileType === 'onigawara_tiles') {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET clay = GREATEST(0, clay - ?),
                        crystal = GREATEST(0, crystal - ?),
                        charcoal = GREATEST(0, charcoal - ?),
                        iron_sand = GREATEST(0, iron_sand - ?)
                    WHERE id = ? AND clay >= ? AND crystal >= ? AND charcoal >= ? AND iron_sand >= ?
                ");
                $stmt->execute([
                    $details['engaged_clay'],
                    $details['engaged_crystal'],
                    $details['engaged_charcoal'],
                    $details['engaged_iron_sand'],
                    $planetId,
                    $details['engaged_clay'],
                    $details['engaged_crystal'],
                    $details['engaged_charcoal'],
                    $details['engaged_iron_sand']
                ]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET clay = GREATEST(0, clay - ?),
                        crystal = GREATEST(0, crystal - ?),
                        charcoal = GREATEST(0, charcoal - ?)
                    WHERE id = ? AND clay >= ? AND crystal >= ? AND charcoal >= ?
                ");
                $stmt->execute([
                    $details['engaged_clay'],
                    $details['engaged_crystal'],
                    $details['engaged_charcoal'],
                    $planetId,
                    $details['engaged_clay'],
                    $details['engaged_crystal'],
                    $details['engaged_charcoal']
                ]);
            }

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Échec de l'opération : stock de matière modifié entretemps."];
            }

            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'stonemason', ?, 'clay', ?, ?, ?, ?, ?)
            ");
            $stmtQueue->execute([
                $planetId,
                $tileType,
                $details['engaged_clay'],
                $details['engaged_charcoal'],
                $details['actual_produced'],
                $startedAt,
                $finishesAt
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement de la cuisson des tuiles : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'stonemason');
        $durationFmt = gmdate('i\m s\s', $details['duration_seconds']);

        return [
            'success' => true,
            'message' => "La cuisson de <strong>+" . number_format($details['actual_produced']) . " {$details['product_name']} {$details['product_icon']}</strong> a été lancée dans les fours Kawara-gama ! Durée : <strong>{$durationFmt}</strong>.",
            'product' => $tileType,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'engaged_clay' => $details['engaged_clay'],
            'engaged_crystal' => $details['engaged_crystal'],
            'engaged_charcoal' => $details['engaged_charcoal'],
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'new_product_stock' => $updatedPlanet[$tileType] ?? 0,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Calcule les détails de fabrication pour la Grande Forge du Tamahagane
     * - Palier 15+ : Fonte de Métal (Fourneau Tatara des Maîtres & Réduction Rustique)
     * - Palier 20+ : Armes de Prestige (Katanas de Maître) & Armées de Prestige (Garde d'Honneur au Tamahagane)
     */
    public function calculateForgeCraftDetails(int $planetId, string $product, float $amount): array {
        $buildings = $this->getBuildings($planetId);
        $blacksmithLvl = (int)($buildings['blacksmith'] ?? 0);
        $planet = $this->getPlanet($planetId);

        $efficiencyMultiplier = 1.0 + ($blacksmithLvl * 0.02);
        $gameSpeed = (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1);
        if ($gameSpeed <= 0) $gameSpeed = 1;

        $currentMetal = (float)($planet['metal'] ?? 0);
        $storageLvl = (int)($buildings['storage'] ?? 0);
        $maxMetal = (int)($planet['metal_max'] ?? (15000 * pow(1.5, $storageLvl)));
        $availableMetalSpace = max(0, $maxMetal - $currentMetal);

        if ($product === 'metal') {
            // Fonte Tatara : 1 Sable de fer (iron_sand) + 2 Charbon (charcoal) -> 40 Métal
            $baseIronSand = 1;
            $baseCharcoal = 2;
            $yieldPerBatch = 40;

            $batches = max(1, floor($amount));
            $rawProduced = floor($batches * $yieldPerBatch * $efficiencyMultiplier);
            $actualProduced = min($rawProduced, $availableMetalSpace);

            $actualBatches = $batches;
            if ($actualProduced < $rawProduced && $actualProduced > 0) {
                $actualBatches = max(1, (int)ceil($actualProduced / ($yieldPerBatch * $efficiencyMultiplier)));
            }
            $engagedIronSand = $actualBatches * $baseIronSand;
            $engagedCharcoal = $actualBatches * $baseCharcoal;

            $durationSeconds = max(10, (int)round(($actualBatches * 25) / (1 + ($blacksmithLvl * 0.15)) / $gameSpeed));

            return [
                'product' => 'metal',
                'product_name' => 'Fonte de Métal (Fourneau Tatara)',
                'product_icon' => '⚔️',
                'forge_level' => $blacksmithLvl,
                'batches' => $actualBatches,
                'actual_produced' => $actualProduced,
                'raw_produced' => $rawProduced,
                'current_stock' => $currentMetal,
                'max_stock' => $maxMetal,
                'available_space' => $availableMetalSpace,
                'engaged_iron_sand' => $engagedIronSand,
                'engaged_charcoal' => $engagedCharcoal,
                'duration_seconds' => $durationSeconds
            ];
        } elseif ($product === 'metal_crude') {
            // Réduction Rustique : 10 Pierre (crystal) + 5 Charbon (charcoal) -> 25 Métal
            $baseCrystal = 10;
            $baseCharcoal = 5;
            $yieldPerBatch = 25;

            $batches = max(1, floor($amount));
            $rawProduced = floor($batches * $yieldPerBatch * $efficiencyMultiplier);
            $actualProduced = min($rawProduced, $availableMetalSpace);

            $actualBatches = $batches;
            if ($actualProduced < $rawProduced && $actualProduced > 0) {
                $actualBatches = max(1, (int)ceil($actualProduced / ($yieldPerBatch * $efficiencyMultiplier)));
            }
            $engagedCrystal = $actualBatches * $baseCrystal;
            $engagedCharcoal = $actualBatches * $baseCharcoal;

            $durationSeconds = max(10, (int)round(($actualBatches * 30) / (1 + ($blacksmithLvl * 0.15)) / $gameSpeed));

            return [
                'product' => 'metal_crude',
                'product_name' => 'Réduction Rustique de Métal',
                'product_icon' => '🔥',
                'forge_level' => $blacksmithLvl,
                'batches' => $actualBatches,
                'actual_produced' => $actualProduced,
                'raw_produced' => $rawProduced,
                'current_stock' => $currentMetal,
                'max_stock' => $maxMetal,
                'available_space' => $availableMetalSpace,
                'engaged_crystal' => $engagedCrystal,
                'engaged_charcoal' => $engagedCharcoal,
                'duration_seconds' => $durationSeconds
            ];
        } elseif ($product === 'prestige_weapons') {
            // Armes de Prestige : 500 Métal + 10 Sable de fer + 20 Charbon + 10 Saké
            $batches = max(1, floor($amount));
            $engagedMetal = 500 * $batches;
            $engagedIronSand = 10 * $batches;
            $engagedCharcoal = 20 * $batches;
            $engagedSake = 10 * $batches;

            $durationSeconds = max(30, (int)round(($batches * 180) / (1 + ($blacksmithLvl * 0.15)) / $gameSpeed));

            return [
                'product' => 'prestige_weapons',
                'product_name' => 'Katanas de Maître & Lames Sacrées',
                'product_icon' => '🗡️',
                'forge_level' => $blacksmithLvl,
                'batches' => $batches,
                'actual_produced' => $batches,
                'engaged_metal' => $engagedMetal,
                'engaged_iron_sand' => $engagedIronSand,
                'engaged_charcoal' => $engagedCharcoal,
                'engaged_sake' => $engagedSake,
                'duration_seconds' => $durationSeconds
            ];
        } elseif ($product === 'prestige_army') {
            // Armée de Prestige : 800 Métal + 400 Pierre + 15 Charbon + 15 Saké -> 10 Samouraïs Katana + 2 Gardes Hatamoto
            $batches = max(1, floor($amount));
            $engagedMetal = 800 * $batches;
            $engagedCrystal = 400 * $batches;
            $engagedCharcoal = 15 * $batches;
            $engagedSake = 15 * $batches;

            $durationSeconds = max(45, (int)round(($batches * 240) / (1 + ($blacksmithLvl * 0.15)) / $gameSpeed));

            return [
                'product' => 'prestige_army',
                'product_name' => 'Garde d\'Honneur au Tamahagane',
                'product_icon' => '🎌',
                'forge_level' => $blacksmithLvl,
                'batches' => $batches,
                'actual_produced' => $batches * 10,
                'engaged_metal' => $engagedMetal,
                'engaged_crystal' => $engagedCrystal,
                'engaged_charcoal' => $engagedCharcoal,
                'engaged_sake' => $engagedSake,
                'duration_seconds' => $durationSeconds
            ];
        }

        throw new Exception("Recette de forge inconnue : {$product}");
    }

    /**
     * Lance une tâche de fonte de métal ou de fabrication d'armes/armées de prestige à la Grande Forge
     */
    public function craftForgeProduct(int $planetId, string $product, float $amount): array {
        $this->ensureSchemaMigration();
        if (!in_array($product, ['metal', 'metal_crude', 'prestige_weapons', 'prestige_army'])) {
            return ['success' => false, 'error' => "Produit de forge invalide."];
        }

        $isAdmin = $this->isPlanetAdmin($planetId);
        $amount = floor($amount);
        if ($isAdmin && $amount <= 0) {
            $amount = 1;
        }
        if ($amount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité ou un nombre de lots valide supérieur à zéro."];
        }

        $buildings = $this->getBuildings($planetId);
        $forgeLvl = (int)($buildings['blacksmith'] ?? 0);

        // Vérification des prérequis de niveau de forge
        if (!$isAdmin && in_array($product, ['metal', 'metal_crude']) && $forgeLvl < 15) {
            return [
                'success' => false,
                'error' => "La Grande Forge du Tamahagane doit atteindre le <strong>Niveau 15</strong> minimum pour allumer les fourneaux de fonte de métal (Niveau actuel : {$forgeLvl}/15)."
            ];
        }

        if (!$isAdmin && in_array($product, ['prestige_weapons', 'prestige_army']) && $forgeLvl < 20) {
            return [
                'success' => false,
                'error' => "La Grande Forge du Tamahagane doit atteindre le <strong>Niveau 20</strong> minimum pour forger des armes d'exception et mobiliser des armées de prestige (Niveau actuel : {$forgeLvl}/20)."
            ];
        }

        // Mode Administrateur : Forge instantanée sans coût et sans délai
        if ($isAdmin) {
            $details = $this->calculateForgeCraftDetails($planetId, $product, $amount);
            $produced = max(1, (int)($details['raw_produced'] ?? $details['actual_produced'] ?? 1));
            $batches = max(1, (int)($details['batches'] ?? 1));
            $now = time();
            $costRes = match($product) {
                'metal' => 'iron_sand',
                'metal_crude' => 'crystal',
                'prestige_weapons' => 'prestige_weapons',
                'prestige_army' => 'prestige_army',
                default => 'metal'
            };
            $stmtQueue = $this->db->prepare("
                INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                VALUES (?, 'blacksmith', ?, ?, ?, 0, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $product, $costRes, $batches, $produced, $now - 2, $now - 1]);
            $this->processCraftQueue($planetId);
            $updatedPlanet = $this->getPlanet($planetId);
            $activeQueue = $this->getCraftQueue($planetId, 'blacksmith');

            return [
                'success' => true,
                'message' => "⚡ [Admin] Forge instantanée de <strong>{$details['product_name']} {$details['product_icon']}</strong> achevée sans coût !",
                'product' => $product,
                'product_name' => $details['product_name'],
                'product_icon' => $details['product_icon'],
                'produced' => $produced,
                'duration_seconds' => 0,
                'craft' => $activeQueue[0] ?? null,
                'planet' => $updatedPlanet
            ];
        }

        $this->processCraftQueue($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'blacksmith');

        $planet = $this->getPlanet($planetId);
        require_once __DIR__ . '/ImperialSealEngine.php';
        $sealEngine = new ImperialSealEngine($this->db);
        $isSealActive = $sealEngine->isSealActive((int)$planet['user_id']);
        $maxQueue = $isSealActive ? 4 : 1;

        if (count($activeQueue) >= $maxQueue) {
            $remaining = !empty($activeQueue[0]['time_remaining']) ? gmdate('i\m s\s', $activeQueue[0]['time_remaining']) : 'quelques instants';
            if ($maxQueue === 1) {
                return [
                    'success' => false,
                    'error' => "Un travail de forge ou de fonte est déjà en cours dans la Grande Forge (temps restant : {$remaining}). Décrétez le <strong>Sceau Impérial</strong> pour débloquer jusqu'à 4 commandes consécutives !"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "La file de travail de la Grande Forge est déjà saturée (4/4 tâches programmées). Attendez la finalisation d'un lot."
                ];
            }
        }

        $details = $this->calculateForgeCraftDetails($planetId, $product, $amount);

        // Si production de métal brut, vérifier la capacité de stockage
        if (in_array($product, ['metal', 'metal_crude'])) {
            if ($details['actual_produced'] < 1) {
                if ($details['available_space'] <= 0) {
                    return [
                        'success' => false,
                        'error' => "Vos entrepôts de Métal sont déjà saturés (" . number_format($details['current_stock']) . " / " . number_format($details['max_stock']) . "). Augmentez la capacité de vos entrepôts."
                    ];
                }
                return [
                    'success' => false,
                    'error' => "Quantité insuffisante pour couler au moins 1 unité de Métal."
                ];
            }
        }

        // Vérification des stocks de matières premières
        $planet = $this->updatePlanet($planetId);
        if ($product === 'metal') {
            $reqIron = $details['engaged_iron_sand'];
            $reqChar = $details['engaged_charcoal'];
            if ((float)($planet['iron_sand'] ?? 0) < $reqIron) {
                return ['success' => false, 'error' => "Stock insuffisant de Sable de Fer 💎 (Requis : {$reqIron}, Disponible : " . floor($planet['iron_sand'] ?? 0) . ")."];
            }
            if ((float)($planet['charcoal'] ?? 0) < $reqChar) {
                return ['success' => false, 'error' => "Stock insuffisant de Charbon de Bois 🪨 (Requis : {$reqChar}, Disponible : " . floor($planet['charcoal'] ?? 0) . ")."];
            }
        } elseif ($product === 'metal_crude') {
            $reqCrystal = $details['engaged_crystal'];
            $reqChar = $details['engaged_charcoal'];
            if ((float)($planet['crystal'] ?? 0) < $reqCrystal) {
                return ['success' => false, 'error' => "Stock insuffisant de Pierre de Granit 🪨 (Requis : {$reqCrystal}, Disponible : " . floor($planet['crystal'] ?? 0) . ")."];
            }
            if ((float)($planet['charcoal'] ?? 0) < $reqChar) {
                return ['success' => false, 'error' => "Stock insuffisant de Charbon de Bois 🪨 (Requis : {$reqChar}, Disponible : " . floor($planet['charcoal'] ?? 0) . ")."];
            }
        } elseif ($product === 'prestige_weapons') {
            $reqMet = $details['engaged_metal'];
            $reqIron = $details['engaged_iron_sand'];
            $reqChar = $details['engaged_charcoal'];
            $reqSake = $details['engaged_sake'];
            if ((float)($planet['metal'] ?? 0) < $reqMet) {
                return ['success' => false, 'error' => "Stock insuffisant de Métal (Requis : {$reqMet}, Disponible : " . floor($planet['metal'] ?? 0) . ")."];
            }
            if ((float)($planet['iron_sand'] ?? 0) < $reqIron) {
                return ['success' => false, 'error' => "Stock insuffisant de Sable de Fer 💎 (Requis : {$reqIron}, Disponible : " . floor($planet['iron_sand'] ?? 0) . ")."];
            }
            if ((float)($planet['charcoal'] ?? 0) < $reqChar) {
                return ['success' => false, 'error' => "Stock insuffisant de Charbon de Bois 🪨 (Requis : {$reqChar}, Disponible : " . floor($planet['charcoal'] ?? 0) . ")."];
            }
            if ((float)($planet['sake'] ?? 0) < $reqSake) {
                return ['success' => false, 'error' => "Stock insuffisant de Saké Féodal 🍶 (Requis : {$reqSake}, Disponible : " . floor($planet['sake'] ?? 0) . ")."];
            }
        } elseif ($product === 'prestige_army') {
            $reqMet = $details['engaged_metal'];
            $reqStone = $details['engaged_crystal'];
            $reqChar = $details['engaged_charcoal'];
            $reqSake = $details['engaged_sake'];
            if ((float)($planet['metal'] ?? 0) < $reqMet) {
                return ['success' => false, 'error' => "Stock insuffisant de Métal (Requis : {$reqMet}, Disponible : " . floor($planet['metal'] ?? 0) . ")."];
            }
            if ((float)($planet['crystal'] ?? 0) < $reqStone) {
                return ['success' => false, 'error' => "Stock insuffisant de Pierre de Granit 🪨 (Requis : {$reqStone}, Disponible : " . floor($planet['crystal'] ?? 0) . ")."];
            }
            if ((float)($planet['charcoal'] ?? 0) < $reqChar) {
                return ['success' => false, 'error' => "Stock insuffisant de Charbon de Bois 🪨 (Requis : {$reqChar}, Disponible : " . floor($planet['charcoal'] ?? 0) . ")."];
            }
            if ((float)($planet['sake'] ?? 0) < $reqSake) {
                return ['success' => false, 'error' => "Stock insuffisant de Saké Féodal 🍶 (Requis : {$reqSake}, Disponible : " . floor($planet['sake'] ?? 0) . ")."];
            }
        }

        $now = time();
        $startedAt = $now;
        if (!empty($activeQueue)) {
            $lastFinish = 0;
            foreach ($activeQueue as $qItem) {
                if ((int)$qItem['finishes_at'] > $lastFinish) {
                    $lastFinish = (int)$qItem['finishes_at'];
                }
            }
            $startedAt = max($now, $lastFinish);
        }
        $finishesAt = $startedAt + $details['duration_seconds'];

        $this->db->beginTransaction();
        try {
            if ($product === 'metal') {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET iron_sand = GREATEST(0, iron_sand - ?),
                        charcoal = GREATEST(0, charcoal - ?)
                    WHERE id = ? AND iron_sand >= ? AND charcoal >= ?
                ");
                $stmt->execute([$details['engaged_iron_sand'], $details['engaged_charcoal'], $planetId, $details['engaged_iron_sand'], $details['engaged_charcoal']]);
                if ($stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => "Échec : stock modifié entretemps."];
                }

                $stmtQueue = $this->db->prepare("
                    INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                    VALUES (?, 'blacksmith', 'metal', 'iron_sand', ?, 0, ?, ?, ?)
                ");
                $stmtQueue->execute([$planetId, $details['batches'], $details['actual_produced'], $startedAt, $finishesAt]);
            } elseif ($product === 'metal_crude') {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET crystal = GREATEST(0, crystal - ?),
                        charcoal = GREATEST(0, charcoal - ?)
                    WHERE id = ? AND crystal >= ? AND charcoal >= ?
                ");
                $stmt->execute([$details['engaged_crystal'], $details['engaged_charcoal'], $planetId, $details['engaged_crystal'], $details['engaged_charcoal']]);
                if ($stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => "Échec : stock modifié entretemps."];
                }

                $stmtQueue = $this->db->prepare("
                    INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                    VALUES (?, 'blacksmith', 'metal_crude', 'crystal', ?, 0, ?, ?, ?)
                ");
                $stmtQueue->execute([$planetId, $details['batches'], $details['actual_produced'], $startedAt, $finishesAt]);
            } elseif ($product === 'prestige_weapons') {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET metal = GREATEST(0, metal - ?),
                        iron_sand = GREATEST(0, iron_sand - ?),
                        charcoal = GREATEST(0, charcoal - ?),
                        sake = GREATEST(0, sake - ?)
                    WHERE id = ? AND metal >= ? AND iron_sand >= ? AND charcoal >= ? AND sake >= ?
                ");
                $stmt->execute([
                    $details['engaged_metal'], $details['engaged_iron_sand'], $details['engaged_charcoal'], $details['engaged_sake'],
                    $planetId,
                    $details['engaged_metal'], $details['engaged_iron_sand'], $details['engaged_charcoal'], $details['engaged_sake']
                ]);
                if ($stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => "Échec : stock modifié entretemps."];
                }

                $stmtQueue = $this->db->prepare("
                    INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                    VALUES (?, 'blacksmith', 'prestige_weapons', 'prestige_weapons', ?, 0, ?, ?, ?)
                ");
                $stmtQueue->execute([$planetId, $details['batches'], $details['actual_produced'], $startedAt, $finishesAt]);
            } elseif ($product === 'prestige_army') {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET metal = GREATEST(0, metal - ?),
                        crystal = GREATEST(0, crystal - ?),
                        charcoal = GREATEST(0, charcoal - ?),
                        sake = GREATEST(0, sake - ?)
                    WHERE id = ? AND metal >= ? AND crystal >= ? AND charcoal >= ? AND sake >= ?
                ");
                $stmt->execute([
                    $details['engaged_metal'], $details['engaged_crystal'], $details['engaged_charcoal'], $details['engaged_sake'],
                    $planetId,
                    $details['engaged_metal'], $details['engaged_crystal'], $details['engaged_charcoal'], $details['engaged_sake']
                ]);
                if ($stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => "Échec : stock modifié entretemps."];
                }

                $stmtQueue = $this->db->prepare("
                    INSERT INTO craft_queue (planet_id, building_type, product, cost_resource, cost_amount, rice_amount, produced_amount, started_at, finishes_at)
                    VALUES (?, 'blacksmith', 'prestige_army', 'prestige_army', ?, 0, ?, ?, ?)
                ");
                $stmtQueue->execute([$planetId, $details['batches'], $details['actual_produced'], $startedAt, $finishesAt]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement de la commande de forge : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        $activeQueue = $this->getCraftQueue($planetId, 'blacksmith');
        $durationFmt = gmdate('i\m s\s', $details['duration_seconds']);

        return [
            'success' => true,
            'message' => "La forge de <strong>{$details['product_name']} {$details['product_icon']}</strong> a débuté ! Durée estimée : <strong>{$durationFmt}</strong>.",
            'product' => $product,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Annule un lot de raffinage, charpente ou forge en cours et rembourse 80% des matières engagées
     */
    public function cancelCraft(int $planetId, int $craftId): array {
        $stmt = $this->db->prepare("SELECT * FROM craft_queue WHERE id = ? AND planet_id = ?");
        $stmt->execute([$craftId, $planetId]);
        $craft = $stmt->fetch();

        if (!$craft) {
            return ['success' => false, 'error' => "Ce lot est introuvable ou déjà terminé."];
        }

        $bType = $craft['building_type'] ?? ($craft['product'] === 'wooden_beams' ? 'sawmill' : 'grain_mill');

        // Cas spécifique de la Grande Forge
        if ($bType === 'blacksmith') {
            $product = $craft['product'] ?? '';
            $rawEngaged = !empty($craft['cost_amount']) ? (float)$craft['cost_amount'] : 1;

            $this->db->beginTransaction();
            try {
                $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                $del->execute([$craftId]);

                $refundMsg = "";
                if ($product === 'metal') {
                    $ironRefund = floor($rawEngaged * 0.8);
                    $charcoalRefund = floor($rawEngaged * 2 * 0.8);
                    $up = $this->db->prepare("UPDATE planets SET iron_sand = iron_sand + ?, charcoal = charcoal + ? WHERE id = ?");
                    $up->execute([$ironRefund, $charcoalRefund, $planetId]);
                    $refundMsg = "+{$ironRefund} Sable de fer 💎 et +{$charcoalRefund} Charbon 🪨";
                } elseif ($product === 'metal_crude') {
                    $crystalRefund = floor($rawEngaged * 0.8);
                    $charcoalRefund = floor(($rawEngaged / 10) * 5 * 0.8);
                    $up = $this->db->prepare("UPDATE planets SET crystal = crystal + ?, charcoal = charcoal + ? WHERE id = ?");
                    $up->execute([$crystalRefund, $charcoalRefund, $planetId]);
                    $refundMsg = "+{$crystalRefund} Pierre de granit 🪨 et +{$charcoalRefund} Charbon 🪨";
                } elseif ($product === 'prestige_weapons') {
                    $batches = max(1, (int)$rawEngaged);
                    $mRef = floor(500 * $batches * 0.8);
                    $isRef = floor(10 * $batches * 0.8);
                    $cRef = floor(20 * $batches * 0.8);
                    $sRef = floor(10 * $batches * 0.8);
                    $up = $this->db->prepare("UPDATE planets SET metal = metal + ?, iron_sand = iron_sand + ?, charcoal = charcoal + ?, sake = sake + ? WHERE id = ?");
                    $up->execute([$mRef, $isRef, $cRef, $sRef, $planetId]);
                    $refundMsg = "+{$mRef} Métal ⚔️, +{$isRef} Sable 💎, +{$cRef} Charbon 🪨, +{$sRef} Saké 🍶";
                } elseif ($product === 'prestige_army') {
                    $batches = max(1, (int)$rawEngaged);
                    $mRef = floor(800 * $batches * 0.8);
                    $pRef = floor(400 * $batches * 0.8);
                    $cRef = floor(15 * $batches * 0.8);
                    $sRef = floor(15 * $batches * 0.8);
                    $up = $this->db->prepare("UPDATE planets SET metal = metal + ?, crystal = crystal + ?, charcoal = charcoal + ?, sake = sake + ? WHERE id = ?");
                    $up->execute([$mRef, $pRef, $cRef, $sRef, $planetId]);
                    $refundMsg = "+{$mRef} Métal ⚔️, +{$pRef} Pierre 🪨, +{$cRef} Charbon 🪨, +{$sRef} Saké 🍶";
                }

                // Réaligner séquentiellement les créneaux restants pour la forge
                $stmtRem = $this->db->prepare("SELECT id, started_at, finishes_at FROM craft_queue WHERE planet_id = ? AND building_type = 'blacksmith' ORDER BY started_at ASC, id ASC");
                $stmtRem->execute([$planetId]);
                $remainingQueue = $stmtRem->fetchAll(PDO::FETCH_ASSOC);

                $now = time();
                $timeCursor = $now;
                foreach ($remainingQueue as $idx => $rItem) {
                    $duration = max(1, (int)$rItem['finishes_at'] - (int)$rItem['started_at']);
                    if ($idx === 0) {
                        if ((int)$rItem['started_at'] <= $now && (int)$rItem['finishes_at'] > $now) {
                            $timeCursor = (int)$rItem['finishes_at'];
                        } else {
                            $newStart = $now;
                            $newFinish = $newStart + $duration;
                            $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                            $timeCursor = $newFinish;
                        }
                    } else {
                        $newStart = max($now, $timeCursor);
                        $newFinish = $newStart + $duration;
                        $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                        $timeCursor = $newFinish;
                    }
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Erreur lors de l'annulation : " . $e->getMessage()];
            }

            $updatedPlanet = $this->getPlanet($planetId);
            return [
                'success' => true,
                'message' => "Commande de forge annulée. <strong>{$refundMsg}</strong> (80% des matières engagées) ont été restitués.",
                'planet' => $updatedPlanet
            ];
        }

        // Cas spécifique de la cuisson de Tuiles dans la Tailleuse (stonemason)
        if ($bType === 'stonemason' && in_array($craft['product'], ['roof_tiles', 'onigawara_tiles'])) {
            $product = $craft['product'];
            $rawEngaged = !empty($craft['cost_amount']) ? (float)$craft['cost_amount'] : 40;

            $this->db->beginTransaction();
            try {
                $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                $del->execute([$craftId]);

                $refundMsg = "";
                if ($product === 'onigawara_tiles') {
                    $batches = max(1, (int)ceil($rawEngaged / 50));
                    $clayRefund = floor($rawEngaged * 0.8);
                    $stoneRefund = floor($batches * 30 * 0.8);
                    $charcoalRefund = floor($batches * 15 * 0.8);
                    $ironSandRefund = floor($batches * 5 * 0.8);

                    $up = $this->db->prepare("UPDATE planets SET clay = clay + ?, crystal = crystal + ?, charcoal = charcoal + ?, iron_sand = iron_sand + ? WHERE id = ?");
                    $up->execute([$clayRefund, $stoneRefund, $charcoalRefund, $ironSandRefund, $planetId]);
                    $refundMsg = "+{$clayRefund} Argile 🏺, +{$stoneRefund} Pierre 🪨, +{$charcoalRefund} Charbon 🔥 et +{$ironSandRefund} Sable de fer 💎";
                } else {
                    $batches = max(1, (int)ceil($rawEngaged / 40));
                    $clayRefund = floor($rawEngaged * 0.8);
                    $stoneRefund = floor($batches * 20 * 0.8);
                    $charcoalRefund = floor((float)($craft['rice_amount'] ?? ($batches * 10)) * 0.8);

                    $up = $this->db->prepare("UPDATE planets SET clay = clay + ?, crystal = crystal + ?, charcoal = charcoal + ? WHERE id = ?");
                    $up->execute([$clayRefund, $stoneRefund, $charcoalRefund, $planetId]);
                    $refundMsg = "+{$clayRefund} Argile 🏺, +{$stoneRefund} Pierre 🪨 et +{$charcoalRefund} Charbon 🔥";
                }

                // Réaligner séquentiellement les créneaux restants pour stonemason
                $stmtRem = $this->db->prepare("SELECT id, started_at, finishes_at FROM craft_queue WHERE planet_id = ? AND building_type = 'stonemason' ORDER BY started_at ASC, id ASC");
                $stmtRem->execute([$planetId]);
                $remainingQueue = $stmtRem->fetchAll(PDO::FETCH_ASSOC);

                $now = time();
                $timeCursor = $now;
                foreach ($remainingQueue as $idx => $rItem) {
                    $duration = max(1, (int)$rItem['finishes_at'] - (int)$rItem['started_at']);
                    if ($idx === 0) {
                        if ((int)$rItem['started_at'] <= $now && (int)$rItem['finishes_at'] > $now) {
                            $timeCursor = (int)$rItem['finishes_at'];
                        } else {
                            $newStart = $now;
                            $newFinish = $newStart + $duration;
                            $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                            $timeCursor = $newFinish;
                        }
                    } else {
                        $newStart = max($now, $timeCursor);
                        $newFinish = $newStart + $duration;
                        $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                        $timeCursor = $newFinish;
                    }
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Erreur lors de l'annulation : " . $e->getMessage()];
            }

            $updatedPlanet = $this->getPlanet($planetId);
            return [
                'success' => true,
                'message' => "Cuisson de tuiles annulée. <strong>{$refundMsg}</strong> (80% des matières engagées) ont été restitués.",
                'planet' => $updatedPlanet
            ];
        }

        $costRes = $craft['cost_resource'] ?? ($bType === 'sawmill' ? 'metal' : 'deuterium');
        $rawEngaged = !empty($craft['cost_amount']) ? (float)$craft['cost_amount'] : (float)$craft['rice_amount'];
        $refundAmount = (float)floor($rawEngaged * 0.8);
        $refundResName = match($costRes) {
            'metal' => 'Bois de Cèdre 🪵',
            'crystal' => 'Pierre de Granit 🪨',
            'clay' => 'Argile Alluviale 🏺',
            default => 'Riz 🌾'
        };

        $isWood = ($bType === 'sawmill');

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
            $del->execute([$craftId]);

            if ($refundAmount > 0) {
                if (in_array($costRes, ['metal', 'crystal', 'deuterium', 'clay'])) {
                    $up = $this->db->prepare("UPDATE planets SET {$costRes} = {$costRes} + ? WHERE id = ?");
                    $up->execute([$refundAmount, $planetId]);
                }
            }

            // Réaligner séquentiellement les créneaux temporels des tâches restantes POUR CE BÂTIMENT
            $stmtRem = $this->db->prepare("SELECT id, started_at, finishes_at FROM craft_queue WHERE planet_id = ? AND (building_type = ? OR (building_type = '' AND ? = 'grain_mill')) ORDER BY started_at ASC, id ASC");
            $stmtRem->execute([$planetId, $bType, $bType]);
            $remainingQueue = $stmtRem->fetchAll(PDO::FETCH_ASSOC);

            $now = time();
            $timeCursor = $now;

            foreach ($remainingQueue as $idx => $rItem) {
                $duration = max(1, (int)$rItem['finishes_at'] - (int)$rItem['started_at']);
                if ($idx === 0) {
                    if ((int)$rItem['started_at'] <= $now && (int)$rItem['finishes_at'] > $now) {
                        $timeCursor = (int)$rItem['finishes_at'];
                    } else {
                        $newStart = $now;
                        $newFinish = $newStart + $duration;
                        $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                        $timeCursor = $newFinish;
                    }
                } else {
                    $newStart = max($now, $timeCursor);
                    $newFinish = $newStart + $duration;
                    $this->db->prepare("UPDATE craft_queue SET started_at = ?, finishes_at = ? WHERE id = ?")->execute([$newStart, $newFinish, $rItem['id']]);
                    $timeCursor = $newFinish;
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors de l'annulation : " . $e->getMessage()];
        }

        $updatedPlanet = $this->getPlanet($planetId);
        return [
            'success' => true,
            'message' => "Lot annulé. <strong>+" . number_format($refundAmount) . " {$refundResName}</strong> (80% du stock engagé) ont été restitués à vos réserves.",
            'refunded_amount' => $refundAmount,
            'refunded_rice' => !$isWood ? $refundAmount : 0,
            'refunded_wood' => $isWood ? $refundAmount : 0,
            'new_deuterium' => $updatedPlanet['deuterium'],
            'new_metal' => $updatedPlanet['metal'],
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Rétro-compatibilité : Annule un lot de raffinage en cours et rembourse 80% du riz engagé
     */
    public function cancelRiceCraft(int $planetId, int $craftId): array {
        return $this->cancelCraft($planetId, $craftId);
    }

    /**
     * Calcule la capacité maximale de population (logements).
     * Règle : Seule la construction d'habitations (parcelles villageoises de terroir) offre des logements aux villageois.
     * Les bâtiments urbains et parcelles rurales ne fournissent plus de capacité d'habitation.
     * Formule : 75 (base féodale) + (Somme des niveaux des 5 parcelles d'habitations × 5)
     */
    public function calculateMaxPopulation(array $buildings = [], array $fields = [], ?int $planetId = null): int {
        if ($planetId !== null && $this->db) {
            try {
                // 1. Nouvelle refonte à 9 parcelles : vérifier la structure 'village'
                $stmtRural = $this->db->prepare("SELECT level FROM planet_rural_plots WHERE planet_id = ? AND structure_type = 'village'");
                $stmtRural->execute([$planetId]);
                $vRow = $stmtRural->fetch(PDO::FETCH_ASSOC);
                if ($vRow && isset($vRow['level'])) {
                    $vLvl = (int)$vRow['level'];
                    return 75 + ($vLvl * 25);
                }
            } catch (Exception $eRural) {
                // Table planet_rural_plots absente ou non migrée, passer au fallback
            }

            try {
                // 2. Fallback rétro-compatible sur les parcelles de terroir
                $levels = [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1];
                $stmt = $this->db->prepare("
                    SELECT slot_index, level 
                    FROM planet_terroir_slots 
                    WHERE planet_id = ? AND (resource_type = 'housing' OR resource_type = 'village')
                ");
                $stmt->execute([$planetId]);
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $idx = (int)$r['slot_index'];
                    if ($idx >= 1 && $idx <= 5) {
                        $levels[$idx] = max($levels[$idx], (int)$r['level']);
                    }
                }
                $totalLevels = array_sum($levels);
                return 75 + ($totalLevels * 5);
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }

        // Capacité de base par défaut : 100 logements (75 base + niveau 1 x 25 = 100 logements)
        return 100;
    }

    /**
     * Récupère la célébration féodale active au Tenshu (si en cours)
     */
    public function getActiveFeast(int $planetId): ?array {
        try {
            $now = time();
            $stmt = $this->db->prepare("
                SELECT * FROM planet_feasts
                WHERE planet_id = ? AND finishes_at > ?
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$planetId, $now]);
            $row = $stmt->fetch();
            if ($row) {
                $row['time_remaining'] = max(0, (int)$row['finishes_at'] - $now);
                return $row;
            }
        } catch (Exception $e) {
            // Table pas encore créée ou base en cours de mise à jour
        }
        return null;
    }

    /**
     * Déclenche une fête ou un banquet au Tenshu alimenté par le Saké
     */
    public function startFeast(int $planetId, string $feastType): array {
        $this->ensureSchemaMigration();
        if (!in_array($feastType, ['matsuri', 'warriors', 'imperial'])) {
            return ['success' => false, 'error' => "Type de banquet ou célébration inconnu."];
        }

        $isAdmin = $this->isPlanetAdmin($planetId);

        // 1. Vérifier si un banquet est déjà actif
        $active = $this->getActiveFeast($planetId);
        if ($active && !$isAdmin) {
            $remaining = gmdate('H:i:s', $active['time_remaining']);
            return ['success' => false, 'error' => "Une célébration féodale est déjà en cours au Tenshu (temps restant : {$remaining})."];
        }

        // 2. Niveau du Tenshu
        $buildings = $this->getBuildings($planetId);
        $tenshuLvl = (int)($buildings['hq'] ?? 1);

        $configs = [
            'matsuri' => [
                'name' => 'Matsuri Populaire des Saisons',
                'icon' => '🏮',
                'min_tenshu' => 1,
                'sake_cost_per_lvl' => 100,
                'duration' => 8 * 3600,
                'desc' => 'Boost de production et sérénité populaire'
            ],
            'warriors' => [
                'name' => 'Banquet des Guerriers (Kanpai aux Samouraïs)',
                'icon' => '⚔️',
                'min_tenshu' => 5,
                'sake_cost_per_lvl' => 200,
                'duration' => 6 * 3600,
                'desc' => 'Accélération massive du recrutement des troupes'
            ],
            'imperial' => [
                'name' => 'Grand Banquet Impérial & Diplomatique',
                'icon' => '👑',
                'min_tenshu' => 10,
                'sake_cost_per_lvl' => 350,
                'duration' => 12 * 3600,
                'desc' => 'Prestige suprême et points d\'honneur féodal'
            ]
        ];

        $cfg = $configs[$feastType];
        if (!$isAdmin && $tenshuLvl < $cfg['min_tenshu']) {
            return [
                'success' => false,
                'error' => "Votre Tenshu doit atteindre le Niveau {$cfg['min_tenshu']} pour organiser le {$cfg['name']} (actuellement Niveau {$tenshuLvl})."
            ];
        }

        $sakeCost = $cfg['sake_cost_per_lvl'] * $tenshuLvl;
        $planet = $this->updatePlanet($planetId);
        if (!$isAdmin && ($planet['sake'] ?? 0) < $sakeCost) {
            return [
                'success' => false,
                'error' => "Stock de Saké insuffisant dans vos cuves (" . number_format((int)($planet['sake'] ?? 0)) . " disponible, " . number_format($sakeCost) . " requis pour un Tenshu Niv. {$tenshuLvl})."
            ];
        }

        $now = time();
        $finishesAt = $now + $cfg['duration'];

        $this->db->beginTransaction();
        try {
            if ($isAdmin) {
                $this->db->prepare("DELETE FROM planet_feasts WHERE planet_id = ?")->execute([$planetId]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE planets
                    SET sake = GREATEST(0, sake - ?)
                    WHERE id = ? AND sake >= ?
                ");
                $stmt->execute([$sakeCost, $planetId, $sakeCost]);
                if ($stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return ['success' => false, 'error' => "Échec : stock de saké modifié entretemps."];
                }
            }

            $stmtFeast = $this->db->prepare("
                INSERT INTO planet_feasts (planet_id, feast_type, tenshu_level, started_at, finishes_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtFeast->execute([$planetId, $feastType, $tenshuLvl, $now, $finishesAt]);

            // Si banquet impérial, attribuer des points de prestige / honneur
            if ($feastType === 'imperial' && !empty($planet['user_id'])) {
                $honorGain = 50 + ($tenshuLvl * 5);
                $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?")
                    ->execute([$honorGain, $planet['user_id']]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du lancement de la fête : " . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => $isAdmin
                ? "⚡ [Admin] Le <strong>{$cfg['name']} {$cfg['icon']}</strong> a été décrété instantanément au Tenshu sans coût de Saké !"
                : "Le <strong>{$cfg['name']} {$cfg['icon']}</strong> a débuté au Tenshu ! Les festivités battront leur plein pendant " . ($cfg['duration'] / 3600) . " heures.",
            'feast_type' => $feastType,
            'tenshu_level' => $tenshuLvl,
            'sake_consumed' => $isAdmin ? 0 : $sakeCost,
            'finishes_at' => $finishesAt
        ];
    }

    /**
     * Récupère tous les fiefs/villages d'un joueur avec métadonnées utiles
     */
    public function getUserPlanets(int $userId): array {
        $this->ensureSchemaMigration();
        $stmt = $this->db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM planet_fields WHERE planet_id = p.id) as fields_count,
                   (SELECT level FROM planet_buildings WHERE planet_id = p.id AND building_type = 'hq') as hq_level
            FROM planets p
            WHERE p.user_id = ?
            ORDER BY p.is_capital DESC, p.id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Renomme un village du joueur
     */
    public function renamePlanet(int $planetId, int $userId, string $newName): array {
        $newName = trim(strip_tags($newName));
        if (mb_strlen($newName) < 2 || mb_strlen($newName) > 40) {
            return ['success' => false, 'error' => "Le nom du village doit contenir entre 2 et 40 caractères."];
        }

        $stmt = $this->db->prepare("SELECT id FROM planets WHERE id = ? AND user_id = ?");
        $stmt->execute([$planetId, $userId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Ce village ne vous appartient pas."];
        }

        $up = $this->db->prepare("UPDATE planets SET name = ? WHERE id = ? AND user_id = ?");
        $up->execute([$newName, $planetId, $userId]);

        return [
            'success' => true,
            'message' => "Le village a été renommé en « " . htmlspecialchars($newName) . " » avec succès.",
            'name' => $newName
        ];
    }

    /**
     * Proclame un village comme Capitale officielle du clan
     */
    public function setCapital(int $planetId, int $userId): array {
        $stmt = $this->db->prepare("SELECT id, name, is_capital FROM planets WHERE id = ? AND user_id = ?");
        $stmt->execute([$planetId, $userId]);
        $planet = $stmt->fetch();
        if (!$planet) {
            return ['success' => false, 'error' => "Ce village ne vous appartient pas."];
        }

        if (!empty($planet['is_capital'])) {
            return ['success' => false, 'error' => "Ce village est déjà la capitale de votre domaine."];
        }

        $this->db->beginTransaction();
        try {
            // Retirer le statut de capitale des autres fiefs
            $this->db->prepare("UPDATE planets SET is_capital = 0 WHERE user_id = ?")->execute([$userId]);
            // Attribuer la capitale au fief sélectionné
            $this->db->prepare("UPDATE planets SET is_capital = 1 WHERE id = ? AND user_id = ?")->execute([$planetId, $userId]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors de la proclamation de la capitale : " . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => "Le village « " . htmlspecialchars($planet['name']) . " » est désormais proclamé Capitale officielle de votre clan !",
            'planet_id' => $planetId
        ];
    }

    /**
     * Récupère le statut des slots de colons et d'expansion du Tenshu
     * Niveaux requis : 5 (Slot 1), 10 (Slot 2), 15 (Slot 3)
     */
    public function getTenshuColonizerStatus(int $planetId): array {
        $this->ensureSchemaMigration();
        $buildings = $this->getBuildings($planetId);
        $hqLvl = (int)($buildings['hq'] ?? 1);
        $isAdmin = $this->isPlanetAdmin($planetId);

        // Slots débloqués selon niveau du Tenshu
        $maxSlots = 0;
        if ($hqLvl >= 20) {
            $maxSlots = 3;
        } elseif ($hqLvl >= 15) {
            $maxSlots = 2;
        } elseif ($hqLvl >= 10) {
            $maxSlots = 1;
        }
        if ($isAdmin) {
            $maxSlots = max(3, $maxSlots);
        }

        // Fiefs annexés fondés par ce Tenshu
        $stmtFounded = $this->db->prepare("SELECT id, name, coord_x, coord_y, is_capital FROM planets WHERE founder_planet_id = ? AND id != ?");
        $stmtFounded->execute([$planetId, $planetId]);
        $foundedColonies = $stmtFounded->fetchAll(PDO::FETCH_ASSOC);
        $coloniesCount = count($foundedColonies);

        // Colons en stationnement dans ce village
        $stmtUnits = $this->db->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'colonizer'");
        $stmtUnits->execute([$planetId]);
        $stationedColons = (int)$stmtUnits->fetchColumn();

        // Colons en file de recrutement dans ce village
        $stmtQueue = $this->db->prepare("SELECT SUM(count) FROM barracks_queue WHERE planet_id = ? AND unit_code = 'colonizer'");
        $stmtQueue->execute([$planetId]);
        $queuedColons = (int)$stmtQueue->fetchColumn();

        // Colons actuellement en mission depuis ce village
        $stmtMissions = $this->db->prepare("
            SELECT fleet_data FROM fleet_missions
            WHERE source_planet_id = ? AND status IN ('en_route', 'returning')
        ");
        $stmtMissions->execute([$planetId]);
        $inMissionColons = 0;
        while ($m = $stmtMissions->fetch(PDO::FETCH_ASSOC)) {
            $fdata = json_decode($m['fleet_data'], true) ?: [];
            $inMissionColons += (int)($fdata['colonizer'] ?? $fdata['colony_ship'] ?? 0);
        }

        $totalUsed = $coloniesCount + $stationedColons + $queuedColons + $inMissionColons;
        $availableSlots = $isAdmin ? max(3, $maxSlots - $totalUsed) : max(0, $maxSlots - $totalUsed);

        // Coûts de recrutement d'un colon
        $stmtUnit = $this->db->query("SELECT * FROM units WHERE code = 'colonizer'");
        $unitInfo = $stmtUnit ? $stmtUnit->fetch(PDO::FETCH_ASSOC) : null;
        $costs = [
            'metal' => (int)($unitInfo['metal_cost'] ?? 4500),
            'crystal' => (int)($unitInfo['crystal_cost'] ?? 4000),
            'deuterium' => (int)($unitInfo['deuterium_cost'] ?? 4500),
            'rice_flour' => (int)($unitInfo['rice_flour_cost'] ?? 150),
            'base_train_time' => (int)($unitInfo['base_train_time'] ?? 7200)
        ];

        $gameSpeed = max(1, (float)GameConfig::get('fleet_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));
        $trainTime = max(10, (int)round($costs['base_train_time'] / ((1 + ($hqLvl * 0.15)) * $gameSpeed)));

        return [
            'hq_level' => $hqLvl,
            'max_slots' => $maxSlots,
            'colonies_count' => $coloniesCount,
            'founded_colonies' => $foundedColonies,
            'stationed_colons' => $stationedColons,
            'queued_colons' => $queuedColons,
            'in_mission_colons' => $inMissionColons,
            'total_used' => $totalUsed,
            'available_slots' => $availableSlots,
            'costs' => $costs,
            'train_time' => $trainTime,
            'next_threshold' => ($hqLvl < 10) ? 10 : (($hqLvl < 15) ? 15 : (($hqLvl < 20) ? 20 : null))
        ];
    }

    /**
     * Lance le recrutement de Colons (Pionniers Féodaux) au Tenshu
     */
    public function trainColonizer(int $planetId, int $userId, int $count = 1): array {
        $count = max(1, (int)$count);
        $isAdmin = $this->isPlanetAdmin($planetId);
        $status = $this->getTenshuColonizerStatus($planetId);

        if (!$isAdmin && $count > $status['available_slots']) {
            return [
                'success' => false,
                'error' => "Emplacements insuffisants au Tenshu. Vous pouvez recruter au maximum {$status['available_slots']} Pionnier(s) Féodal(s) actuellement."
            ];
        }

        $planet = $this->getPlanet($planetId);
        if (!$planet || (int)$planet['user_id'] !== $userId) {
            return ['success' => false, 'error' => "Ce village ne vous appartient pas."];
        }

        if ($isAdmin) {
            $now = time();
            $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count)
                VALUES (?, 'colonizer', ?)
                ON DUPLICATE KEY UPDATE count = count + VALUES(count)
            ")->execute([$planetId, $count]);

            return [
                'success' => true,
                'message' => "⚡ [Admin] {$count} Pionnier(s) Féodal(s) (Colon ⛩️) recruté(s) instantanément au Tenshu sans coût !",
                'finishes_at' => $now,
                'count' => $count
            ];
        }

        $totalWood = $status['costs']['metal'] * $count;
        $totalStone = $status['costs']['crystal'] * $count;
        $totalRice = $status['costs']['deuterium'] * $count;
        $totalFlour = $status['costs']['rice_flour'] * $count;

        if ($planet['metal'] < $totalWood || $planet['crystal'] < $totalStone || $planet['deuterium'] < $totalRice || ($planet['rice_flour'] ?? 0) < $totalFlour) {
            return [
                'success' => false,
                'error' => "Ressources insuffisantes pour équiper {$count} Pionnier(s) Féodal(s) (Requis : {$totalWood} Bois, {$totalStone} Pierre, {$totalRice} Riz, {$totalFlour} Farine de Riz)."
            ];
        }

        $duration = $status['train_time'] * $count;
        $now = time();

        // Déterminer la fin après les files déjà en cours
        $stmtLast = $this->db->prepare("SELECT MAX(finishes_at) FROM barracks_queue WHERE planet_id = ?");
        $stmtLast->execute([$planetId]);
        $lastFinish = (int)$stmtLast->fetchColumn();
        $startTime = max($now, $lastFinish);
        $finishesAt = $startTime + $duration;

        $this->db->beginTransaction();
        try {
            $stmtDeduct = $this->db->prepare("
                UPDATE planets
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ?, rice_flour = GREATEST(0, rice_flour - ?)
                WHERE id = ? AND metal >= ? AND crystal >= ? AND deuterium >= ? AND rice_flour >= ?
            ");
            $stmtDeduct->execute([$totalWood, $totalStone, $totalRice, $totalFlour, $planetId, $totalWood, $totalStone, $totalRice, $totalFlour]);
            if ($stmtDeduct->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Ressources insuffisantes ou modifiées entretemps."];
            }

            $stmtQueue = $this->db->prepare("
                INSERT INTO barracks_queue (planet_id, unit_code, count, total_count, started_at, finishes_at, unit_train_time)
                VALUES (?, 'colonizer', ?, ?, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $count, $count, $startTime, $finishesAt, $status['train_time']]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du recrutement du colon : " . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => "La formation de {$count} Pionnier(s) Féodal(s) (Colon ⛩️) a débuté au Tenshu !",
            'finishes_at' => $finishesAt,
            'count' => $count
        ];
    }
}



