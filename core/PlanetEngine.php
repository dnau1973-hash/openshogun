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
        // 1. Résolution des constructions terminées
        $this->processConstructionQueue($planetId);

        // 2. Résolution du chantier spatial
        $this->processShipyardQueue($planetId);

        // 3. Résolution de la caserne militaire
        $this->processBarracksQueue($planetId);

        // 4. Récupération de la planète
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

        $metalMax = (int)(15000 * pow(1.5, $storageLvl));
        $crystalMax = (int)(15000 * pow(1.5, $storageLvl));
        $deutMax = (int)(15000 * pow(1.5, $tankLvl));
        $sakeMax = (int)(10000 * pow(1.4, $grainMillLvl));
        $flourMax = (int)(10000 * pow(1.4, $grainMillLvl));

        // 5. Calcul de l'énergie et des productions horaires (avec bonus d'oasis annexées)
        $fields = $this->getFields($planetId);
        $prodRates = $this->calculateProduction($fields, $planetId);

        $now = time();
        $lastUpdate = $planet['last_resource_update'] ?: $now;
        $elapsed = max(0, $now - $lastUpdate);

        if ($elapsed > 0) {
            // Facteur horaire
            $hours = $elapsed / 3600.0;
            
            $newMetal = min($metalMax, $planet['metal'] + ($prodRates['metal'] * $hours));
            $newCrystal = min($crystalMax, $planet['crystal'] + ($prodRates['crystal'] * $hours));
            $newDeut = min($deutMax, $planet['deuterium'] + ($prodRates['deuterium'] * $hours));

            try {
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
            } catch (Exception $e) {
                // Fallback si la migration des colonnes sake_max n'est pas encore appliquée
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

        // Valeurs garanties pour les ressources raffinées
        $planet['sake'] = (float)($planet['sake'] ?? 0);
        $planet['rice_flour'] = (float)($planet['rice_flour'] ?? 0);
        $planet['sake_max'] = (int)($planet['sake_max'] ?? $sakeMax);
        $planet['rice_flour_max'] = (int)($planet['rice_flour_max'] ?? $flourMax);

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

        return [
            'metal' => (int)(($metalBase + ($metalMineProd * $speed)) * $energyRatio * $oasisBonusMult['wood'] * $sawmillMult) + $heroProdBonus['metal'],
            'crystal' => (int)(($crystalBase + ($crystalMineProd * $speed)) * $energyRatio * $oasisBonusMult['stone'] * $stonemasonMult) + $heroProdBonus['crystal'],
            'deuterium' => (int)(($deutBase + ($deutSynthProd * $speed)) * $energyRatio * $oasisBonusMult['rice'] * $grainMillMult) + $heroProdBonus['deuterium'],
            'energy_max' => $energyMax,
            'energy_used' => $energyUsed,
            'energy_ratio' => $energyRatio,
            'oasis_bonuses' => $oasisBonuses,
            'hero_bonuses' => $heroProdBonus,
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
     * Transformation / Raffinage du Riz en Saké ou Farine de Riz (Meunerie & Brasserie Sakagura)
     *
     * @param int $planetId Identifiant du fief / planète
     * @param string $product 'sake' ou 'rice_flour'
     * @param float $riceAmount Quantité de riz brut (deuterium) à raffiner
     * @return array Résultat de l'opération
     */
    public function craftRiceProduct(int $planetId, string $product, float $riceAmount): array {
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

        // 2. Mettre à jour les ressources de la planète
        $planet = $this->updatePlanet($planetId);
        if ($planet['deuterium'] < $riceAmount) {
            return [
                'success' => false, 
                'error' => "Stock de Riz insuffisant (" . number_format((int)$planet['deuterium']) . " disponible, " . number_format($riceAmount) . " requis)."
            ];
        }

        // 3. Ratios de conversion et bonus de niveau
        // Farine : 5 Riz -> 1 Farine
        // Saké   : 10 Riz -> 1 Saké
        // Bonus Meunerie : +2% de rendement par niveau
        $efficiencyMultiplier = 1.0 + ($millLvl * 0.02);

        if ($product === 'rice_flour') {
            $baseCostPerUnit = 5;
            $rawProduced = floor(($riceAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Farine de Riz (Komeko)";
            $productIcon = "🍚";
            $currentStock = (float)($planet['rice_flour'] ?? 0);
            $maxStock = (int)($planet['rice_flour_max'] ?? (10000 * pow(1.4, $millLvl)));
        } else {
            $baseCostPerUnit = 10;
            $rawProduced = floor(($riceAmount / $baseCostPerUnit) * $efficiencyMultiplier);
            $productName = "Saké Féodal";
            $productIcon = "🍶";
            $currentStock = (float)($planet['sake'] ?? 0);
            $maxStock = (int)($planet['sake_max'] ?? (10000 * pow(1.4, $millLvl)));
        }

        if ($rawProduced < 1) {
            return [
                'success' => false, 
                'error' => "Quantité de riz insuffisante pour produire au moins 1 unité de {$productName} (minimum {$baseCostPerUnit} Riz requis)."
            ];
        }

        // 4. Vérifier la capacité de stockage
        $availableSpace = max(0, $maxStock - $currentStock);
        if ($availableSpace <= 0) {
            return [
                'success' => false, 
                'error' => "Vos réserves de {$productName} sont saturées (" . number_format($currentStock) . "/" . number_format($maxStock) . "). Augmentez le niveau de votre Meunerie ou consommez vos stocks."
            ];
        }

        $actualProduced = min($rawProduced, $availableSpace);
        // Si la réserve limite la production, on n'utilise que le riz proportionnel
        if ($actualProduced < $rawProduced) {
            $riceAmount = ceil(($actualProduced / $efficiencyMultiplier) * $baseCostPerUnit);
        }

        // 5. Transaction atomique en base
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE planets 
                SET deuterium = GREATEST(0, deuterium - ?),
                    {$product} = {$product} + ?
                WHERE id = ? AND deuterium >= ?
            ");
            $stmt->execute([$riceAmount, $actualProduced, $planetId, $riceAmount]);

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Échec de l'opération : stock de riz modifié entretemps."];
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => "Erreur lors du raffinage : " . $e->getMessage()];
        }

        // Récupérer le nouvel état
        $updatedPlanet = $this->getPlanet($planetId);

        return [
            'success' => true,
            'message' => "Raffinage accompli avec succès ! <strong>+{$actualProduced} {$productName} {$productIcon}</strong> produits (consommé : <strong>-{$riceAmount} Riz 🌾</strong>).",
            'product' => $product,
            'product_name' => $productName,
            'product_icon' => $productIcon,
            'produced' => $actualProduced,
            'consumed_rice' => $riceAmount,
            'new_deuterium' => $updatedPlanet['deuterium'],
            'new_product_stock' => $updatedPlanet[$product] ?? 0,
            'planet' => $updatedPlanet
        ];
    }
}

