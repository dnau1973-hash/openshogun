<?php
/**
 * Moteur de calcul des ressources et de gestion planétaire
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/../config/game_constants.php';

class PlanetEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
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
        $sakeMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $flourMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $beamsMax = (int)(10000 * pow(1.4, $sawmillLvl));

        // 5. Calcul de l'énergie et des productions horaires (avec bonus d'oasis annexées)
        $fields = $this->getFields($planetId);
        $prodRates = $this->calculateProduction($fields, $planetId);

        // 6. Calcul démographique (capacité d'habitants et croissance)
        $maxPopulation = $this->calculateMaxPopulation($buildings, $fields);
        $curPop = (int)($planet['population'] ?? 100);
        $curFlour = (float)($planet['rice_flour'] ?? 0);

        $now = time();
        $lastUpdate = $planet['last_resource_update'] ?: $now;
        $elapsed = max(0, $now - $lastUpdate);

        if ($elapsed > 0) {
            // Facteur horaire
            $hours = $elapsed / 3600.0;
            
            $newMetal = min($metalMax, $planet['metal'] + ($prodRates['metal'] * $hours));
            $newCrystal = min($crystalMax, $planet['crystal'] + ($prodRates['crystal'] * $hours));
            $newDeut = min($deutMax, $planet['deuterium'] + ($prodRates['deuterium'] * $hours));

            // Croissance démographique soutenue par la farine de riz
            if ($curPop < $maxPopulation && $curFlour > 0) {
                $growthCap = (int)ceil(25 * $hours);
                $growth = min($maxPopulation - $curPop, $growthCap);
                $flourNeeded = max(0, min($curFlour, ceil($growth * 0.05)));
                $curPop += $growth;
                $curFlour = max(0, $curFlour - $flourNeeded);
            }

            // Entretien des troupes d'élite & Famine féodale (si activée)
            $famineResult = $this->processFamine($planetId, $hours, $curFlour);

            try {
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
                    $curFlour,
                    $curPop,
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
            } catch (Exception $e) {
                try {
                    // Fallback si la colonne population n'existe pas encore
                    $stmtUpdate = $this->db->prepare("
                        UPDATE planets 
                        SET metal = ?, crystal = ?, deuterium = ?, 
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

            $planet['metal'] = $newMetal;
            $planet['crystal'] = $newCrystal;
            $planet['deuterium'] = $newDeut;
            $planet['energy_used'] = $prodRates['energy_used'];
            $planet['energy_max'] = $prodRates['energy_max'];
            $planet['metal_max'] = $metalMax;
            $planet['crystal_max'] = $crystalMax;
            $planet['deuterium_max'] = $deutMax;
            $planet['last_resource_update'] = $now;
        }

        // Valeurs garanties pour les ressources raffinées et la démographie
        $planet['sake'] = (float)($planet['sake'] ?? 0);
        $planet['rice_flour'] = (float)$curFlour;
        $planet['sake_max'] = (int)($planet['sake_max'] ?? $sakeMax);
        $planet['rice_flour_max'] = (int)($planet['rice_flour_max'] ?? $flourMax);
        $planet['wooden_beams'] = (float)($planet['wooden_beams'] ?? 0);
        $planet['wooden_beams_max'] = (int)($planet['wooden_beams_max'] ?? $beamsMax);
        $planet['population'] = (int)$curPop;
        $planet['population_max'] = (int)$maxPopulation;
        $planet['famine_active'] = !empty($famineResult['famine']) || (!empty($planet['famine_active']));
        $planet['last_famine_losses'] = (int)($famineResult['casualties'] ?? ($planet['last_famine_losses'] ?? 0));
        $planet['famine_enabled'] = (bool)GameConfig::get('famine_enabled', false);

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

            if ($cat === 'field') {
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
                    $up = $this->db->prepare("
                        INSERT INTO planet_buildings (planet_id, building_type, level) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE level = ?
                    ");
                    $up->execute([$planetId, $bType, $targetLevel, $targetLevel]);
                }
            }

            // Supprimer de la file
            $del = $this->db->prepare("DELETE FROM construction_queue WHERE id = ?");
            $del->execute([$item['id']]);
        }
    }

    /**
     * Résolution du chantier spatial
     */
    public function processShipyardQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM shipyard_queue 
            WHERE planet_id = ? AND finishes_at <= ?
        ");
        $stmt->execute([$planetId, $now]);
        $completed = $stmt->fetchAll();

        foreach ($completed as $item) {
            $up = $this->db->prepare("
                INSERT INTO planet_ships (planet_id, ship_code, count) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $up->execute([$planetId, $item['ship_code'], $item['count'], $item['count']]);

            $del = $this->db->prepare("DELETE FROM shipyard_queue WHERE id = ?");
            $del->execute([$item['id']]);
        }
    }

    /**
     * Résolution des régiments de soldats terminés
     */
    public function processBarracksQueue(int $planetId): void {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT * FROM barracks_queue 
            WHERE planet_id = ? AND finishes_at <= ?
        ");
        $stmt->execute([$planetId, $now]);
        $completed = $stmt->fetchAll();

        foreach ($completed as $item) {
            $up = $this->db->prepare("
                INSERT INTO planet_units (planet_id, unit_code, count) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE count = count + ?
            ");
            $up->execute([$planetId, $item['unit_code'], $item['count'], $item['count']]);

            $del = $this->db->prepare("DELETE FROM barracks_queue WHERE id = ?");
            $del->execute([$item['id']]);
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

                if ($product === 'wooden_beams') {
                    $this->db->beginTransaction();
                    try {
                        $up = $this->db->prepare("
                            UPDATE planets 
                            SET wooden_beams = LEAST(wooden_beams_max, wooden_beams + ?) 
                            WHERE id = ?
                        ");
                        $up->execute([$amount, $planetId]);

                        $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
                        $del->execute([$item['id']]);

                        $this->db->commit();
                    } catch (Exception $e) {
                        $this->db->rollBack();
                    }
                } elseif (in_array($product, ['sake', 'rice_flour'])) {
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
                } elseif ($item['product'] === 'sake') {
                    $item['product_name'] = 'Saké Impérial';
                    $item['product_icon'] = '🍶';
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

        // Bonus des édifices urbains de spécialisation (Style Travian : Scierie, Briqueterie, Moulin, Pavillon de Thé)
        $sawmillMult = 1.0;
        $stonemasonMult = 1.0;
        $grainMillMult = 1.0;
        $teahouseLvl = 0;
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

        return [
            'metal' => (int)((($metalBase + ($metalMineProd * $speed)) * $energyRatio * $oasisBonusMult['wood'] * $sawmillMult) * $matsuriBonusMult) + $heroProdBonus['metal'],
            'crystal' => (int)((($crystalBase + ($crystalMineProd * $speed)) * $energyRatio * $oasisBonusMult['stone'] * $stonemasonMult) * $matsuriBonusMult) + $heroProdBonus['crystal'],
            'deuterium' => (int)((($deutBase + ($deutSynthProd * $speed)) * $energyRatio * $oasisBonusMult['rice'] * $grainMillMult) * $matsuriBonusMult) + $heroProdBonus['deuterium'],
            'energy_max' => $energyMax,
            'energy_used' => $energyUsed,
            'energy_ratio' => $energyRatio,
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
    public function ensureSchemaMigration(): void {
        static $executed = false;
        if ($executed) return;
        $executed = true;

        try {
            // 1. Colonne sake, rice_flour et wooden_beams sur planets
            $stmt = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'sake'");
            if ($stmt && $stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE `planets` 
                    ADD COLUMN `sake` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `rice_flour` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `sake_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `rice_flour_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `wooden_beams` DOUBLE NOT NULL DEFAULT 0,
                    ADD COLUMN `wooden_beams_max` INT UNSIGNED NOT NULL DEFAULT 10000,
                    ADD COLUMN `population` INT UNSIGNED NOT NULL DEFAULT 100
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
                $stmtPop = $this->db->query("SHOW COLUMNS FROM `planets` LIKE 'population'");
                if ($stmtPop && $stmtPop->rowCount() === 0) {
                    $this->db->exec("ALTER TABLE `planets` ADD COLUMN `population` INT UNSIGNED NOT NULL DEFAULT 100");
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

        $riceAmount = floor($riceAmount);
        if ($riceAmount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité de riz valide supérieure à zéro."];
        }

        // 1. Vérifier le bâtiment Meunerie & Brasserie
        $buildings = $this->getBuildings($planetId);
        $millLvl = (int)($buildings['grain_mill'] ?? 0);
        if ($millLvl < 1) {
            return ['success' => false, 'error' => "La Meunerie & Brasserie de riz (Sakagura) doit être érigée au Niveau 1 minimum pour raffiner le riz."];
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
     * Façonnage de Bois de Cèdre en Poutres en bois (Atelier de Charpenterie Kizukuri)
     */
    public function calculateWoodCraftDetails(int $planetId, string $product, float $woodAmount): array {
        $buildings = $this->getBuildings($planetId);
        $sawmillLvl = (int)($buildings['sawmill'] ?? 1);
        $planet = $this->getPlanet($planetId);

        $efficiencyMultiplier = 1.0 + ($sawmillLvl * 0.02);
        $baseCostPerUnit = 10; // 10 Bois de Cèdre pour 1 Poutre en bois
        $rawProduced = floor(($woodAmount / $baseCostPerUnit) * $efficiencyMultiplier);
        $productName = "Poutres en bois";
        $productIcon = "🪵";
        $currentStock = (float)($planet['wooden_beams'] ?? 0);
        $maxStock = (int)($planet['wooden_beams_max'] ?? (10000 * pow(1.4, max(1, $sawmillLvl))));

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
        $durationSeconds = max(10, (int)round(($actualWoodEngaged * 0.5) / (1 + ($sawmillLvl * 0.15)) / $gameSpeed));

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
     * Lance le façonnage de Bois de Cèdre en Poutres en bois dans la Charpenterie
     */
    public function craftWoodProduct(int $planetId, string $product, float $woodAmount): array {
        $this->ensureSchemaMigration();
        if ($product !== 'wooden_beams') {
            return ['success' => false, 'error' => "Produit de charpente invalide (Poutres en bois uniquement)."];
        }

        $woodAmount = floor($woodAmount);
        if ($woodAmount <= 0) {
            return ['success' => false, 'error' => "Veuillez indiquer une quantité de bois valide supérieure à zéro."];
        }

        // 1. Vérifier le bâtiment Atelier de Charpenterie (Niveau 10 minimum pour les poutres)
        $buildings = $this->getBuildings($planetId);
        $sawmillLvl = (int)($buildings['sawmill'] ?? 0);
        if ($sawmillLvl < 10) {
            return ['success' => false, 'error' => "L'Atelier de Charpenterie (Kizukuri) doit être érigé au Niveau 10 minimum pour façonner des poutres en bois (Niveau actuel : {$sawmillLvl}/10)."];
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
                    'error' => "Un façonnage de poutres est déjà en cours dans la Charpenterie (temps restant : {$remaining}). Décrétez le <strong>Sceau Impérial (Privilège du Shōgun)</strong> pour débloquer la file d'attente automatique jusqu'à 4 commandes consécutives !"
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
                'error' => "Quantité de bois insuffisante pour produire au moins 1 Poutre en bois (minimum {$details['base_cost_per_unit']} Bois requis)."
            ];
        }

        if ($details['available_space'] <= 0) {
            return [
                'success' => false, 
                'error' => "Vos réserves de Poutres en bois sont saturées (" . number_format($details['current_stock']) . "/" . number_format($details['max_stock']) . "). Augmentez le niveau de votre Atelier de Charpenterie."
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
        return [
            'success' => true,
            'message' => "Le façonnage de <strong>+" . number_format($details['actual_produced']) . " Poutres en bois 🪵</strong> a débuté ! Durée estimée : <strong>{$durationFmt}</strong> (consommé : -{$engagedWood} Bois 🪵).",
            'product' => $product,
            'product_name' => $details['product_name'],
            'product_icon' => $details['product_icon'],
            'produced' => $details['actual_produced'],
            'consumed_wood' => $engagedWood,
            'duration_seconds' => $details['duration_seconds'],
            'craft' => $activeQueue[0] ?? null,
            'new_metal' => $updatedPlanet['metal'],
            'new_product_stock' => $updatedPlanet['wooden_beams'] ?? 0,
            'planet' => $updatedPlanet
        ];
    }

    /**
     * Annule un lot de raffinage ou charpente en cours et rembourse 80% des matières engagées
     */
    public function cancelCraft(int $planetId, int $craftId): array {
        $stmt = $this->db->prepare("SELECT * FROM craft_queue WHERE id = ? AND planet_id = ?");
        $stmt->execute([$craftId, $planetId]);
        $craft = $stmt->fetch();

        if (!$craft) {
            return ['success' => false, 'error' => "Ce lot est introuvable ou déjà terminé."];
        }

        $bType = $craft['building_type'] ?? ($craft['product'] === 'wooden_beams' ? 'sawmill' : 'grain_mill');
        $isWood = ($bType === 'sawmill' || $craft['product'] === 'wooden_beams' || ($craft['cost_resource'] ?? '') === 'metal');
        $rawEngaged = !empty($craft['cost_amount']) ? (float)$craft['cost_amount'] : (float)$craft['rice_amount'];
        $refundAmount = (float)floor($rawEngaged * 0.8);
        $refundResName = $isWood ? 'Bois de Cèdre 🪵' : 'Riz 🌾';

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM craft_queue WHERE id = ?");
            $del->execute([$craftId]);

            if ($refundAmount > 0) {
                if ($isWood) {
                    $up = $this->db->prepare("UPDATE planets SET metal = metal + ? WHERE id = ?");
                    $up->execute([$refundAmount, $planetId]);
                } else {
                    $up = $this->db->prepare("UPDATE planets SET deuterium = deuterium + ? WHERE id = ?");
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
     * Calcule la capacité maximale de population (logements) selon les édifices et parcelles
     */
    public function calculateMaxPopulation(array $buildings, array $fields): int {
        // Hameau de base
        $capacity = 100;

        // Tenshu (palais castral) : 50 habitants par niveau
        $capacity += ((int)($buildings['hq'] ?? 1)) * 50;

        // Bâtiments urbains : 20 habitants par niveau
        $urbanKeys = [
            'storage', 'tank', 'barracks', 'shipyard', 'market', 'research_lab',
            'radar', 'quantum_vault', 'embassy', 'sawmill', 'stonemason',
            'grain_mill', 'blacksmith', 'teahouse', 'tournament_square', 'wall'
        ];
        foreach ($urbanKeys as $k) {
            $capacity += ((int)($buildings[$k] ?? 0)) * 20;
        }

        // Parcelles rurales du terroir : 10 habitants par niveau
        foreach ($fields as $f) {
            $capacity += ((int)($f['level'] ?? 0)) * 10;
        }

        return $capacity;
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

        // 1. Vérifier si un banquet est déjà actif
        $active = $this->getActiveFeast($planetId);
        if ($active) {
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
        if ($tenshuLvl < $cfg['min_tenshu']) {
            return [
                'success' => false, 
                'error' => "Votre Tenshu doit atteindre le Niveau {$cfg['min_tenshu']} pour organiser le {$cfg['name']} (actuellement Niveau {$tenshuLvl})."
            ];
        }

        $sakeCost = $cfg['sake_cost_per_lvl'] * $tenshuLvl;
        $planet = $this->updatePlanet($planetId);
        if (($planet['sake'] ?? 0) < $sakeCost) {
            return [
                'success' => false,
                'error' => "Stock de Saké insuffisant dans vos cuves (" . number_format((int)($planet['sake'] ?? 0)) . " disponible, " . number_format($sakeCost) . " requis pour un Tenshu Niv. {$tenshuLvl})."
            ];
        }

        $now = time();
        $finishesAt = $now + $cfg['duration'];

        $this->db->beginTransaction();
        try {
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
            'message' => "Le <strong>{$cfg['name']} {$cfg['icon']}</strong> a débuté au Tenshu ! Les festivités battront leur plein pendant " . ($cfg['duration'] / 3600) . " heures.",
            'feast_type' => $feastType,
            'tenshu_level' => $tenshuLvl,
            'sake_consumed' => $sakeCost,
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

        // Slots débloqués selon niveau du Tenshu
        $maxSlots = 0;
        if ($hqLvl >= 15) {
            $maxSlots = 3;
        } elseif ($hqLvl >= 10) {
            $maxSlots = 2;
        } elseif ($hqLvl >= 5) {
            $maxSlots = 1;
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
        $availableSlots = max(0, $maxSlots - $totalUsed);

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
            'next_threshold' => ($hqLvl < 5) ? 5 : (($hqLvl < 10) ? 10 : (($hqLvl < 15) ? 15 : null))
        ];
    }

    /**
     * Lance le recrutement de Colons (Pionniers Féodaux) au Tenshu
     */
    public function trainColonizer(int $planetId, int $userId, int $count = 1): array {
        $count = max(1, (int)$count);
        $status = $this->getTenshuColonizerStatus($planetId);

        if ($count > $status['available_slots']) {
            return [
                'success' => false,
                'error' => "Emplacements insuffisants au Tenshu. Vous pouvez recruter au maximum {$status['available_slots']} Pionnier(s) Féodal(s) actuellement."
            ];
        }

        $planet = $this->getPlanet($planetId);
        if (!$planet || (int)$planet['user_id'] !== $userId) {
            return ['success' => false, 'error' => "Ce village ne vous appartient pas."];
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
                INSERT INTO barracks_queue (planet_id, unit_code, count, started_at, finishes_at, unit_train_time)
                VALUES (?, 'colonizer', ?, ?, ?, ?)
            ");
            $stmtQueue->execute([$planetId, $count, $startTime, $finishesAt, $status['train_time']]);

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



