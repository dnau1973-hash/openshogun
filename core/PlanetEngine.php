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

        $metalMax = (int)(15000 * pow(1.5, $storageLvl));
        $crystalMax = (int)(15000 * pow(1.5, $storageLvl));
        $deutMax = (int)(15000 * pow(1.5, $tankLvl));

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
            if ($item['build_category'] === 'field') {
                // Amélioration de parcelle
                $slot = (int)$item['target_id'];
                $lvl = (int)$item['target_level'];
                $up = $this->db->prepare("
                    UPDATE planet_fields 
                    SET level = ? 
                    WHERE planet_id = ? AND field_slot = ?
                ");
                $up->execute([$lvl, $planetId, $slot]);
            } else {
                // Amélioration de bâtiment
                $bType = $item['target_id'];
                $lvl = (int)$item['target_level'];
                $up = $this->db->prepare("
                    INSERT INTO planet_buildings (planet_id, building_type, level) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE level = ?
                ");
                $up->execute([$planetId, $bType, $lvl, $lvl]);
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

        return [
            'metal' => (int)(($metalBase + ($metalMineProd * $speed)) * $energyRatio * $oasisBonusMult['wood']),
            'crystal' => (int)(($crystalBase + ($crystalMineProd * $speed)) * $energyRatio * $oasisBonusMult['stone']),
            'deuterium' => (int)(($deutBase + ($deutSynthProd * $speed)) * $energyRatio * $oasisBonusMult['rice']),
            'energy_max' => $energyMax,
            'energy_used' => $energyUsed,
            'energy_ratio' => $energyRatio,
            'oasis_bonuses' => $oasisBonuses
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
}

