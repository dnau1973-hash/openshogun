<?php
/**
 * Service de Génération Procédurale du Domaine Rural Féodal (Village Spawner)
 * 9 Parcelles Uniques par village, répartition spatiale procédurale, potentiels verticaux (Niveaux 20 à 100).
 */
require_once __DIR__ . '/Database.php';

class VillageGeneratorService {
    private ?PDO $db;

    // Définition canonique des 9 structures indispensables
    public const STRUCTURE_TYPES = [
        'tenshu',
        'foret',
        'carriere',
        'fosse_argile',
        'riziere',
        'champ_soja',
        'culture_the',
        'sanctuaire_shinto',
        'village'
    ];

    // Points d'ancrage topologiques sur le panorama 16:9
    public const ANCHOR_SLOTS = [
        1 => ['id' => 1, 'name' => 'Promontoire Castral (Nord-Centre)',   'x' => 49.0, 'y' => 18.0, 'affinity' => 'tenshu'],
        2 => ['id' => 2, 'name' => 'Forêt de Cèdres (Nord-Ouest)',        'x' => 19.0, 'y' => 32.0, 'affinity' => 'foret'],
        3 => ['id' => 3, 'name' => 'Falaise de Granit (Nord-Est)',        'x' => 82.0, 'y' => 28.0, 'affinity' => 'carriere'],
        4 => ['id' => 4, 'name' => 'Coteaux de Thé (Ouest)',             'x' => 11.0, 'y' => 54.0, 'affinity' => 'culture_the'],
        5 => ['id' => 5, 'name' => 'Hameau Central (Cœur du Fief)',       'x' => 49.0, 'y' => 55.0, 'affinity' => 'village'],
        6 => ['id' => 6, 'name' => 'Berge Argileuse (Est)',               'x' => 81.0, 'y' => 51.0, 'affinity' => 'fosse_argile'],
        7 => ['id' => 7, 'name' => 'Bassin Alluvial (Sud-Ouest)',         'x' => 17.0, 'y' => 81.0, 'affinity' => 'riziere'],
        8 => ['id' => 8, 'name' => 'Sillons Agricoles (Sud-Centre)',      'x' => 50.0, 'y' => 86.0, 'affinity' => 'champ_soja'],
        9 => ['id' => 9, 'name' => 'Bosquet Sacré (Sud-Est)',             'x' => 85.0, 'y' => 78.0, 'affinity' => 'sanctuaire_shinto'],
    ];

    private static bool $tableChecked = false;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureTableExists();
    }

    /**
     * S'assure de l'existence de la table planet_rural_plots
     */
    public function ensureTableExists(): void {
        if (!$this->db || self::$tableChecked) return;
        // En MySQL, les DDL (CREATE TABLE) provoquent un COMMIT implicite.
        // Si nous sommes dans une transaction, ne pas exécuter de DDL pour ne pas briser la transaction.
        if ($this->db->inTransaction()) {
            return;
        }
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS planet_rural_plots (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        planet_id INTEGER NOT NULL,
                        slot_id INTEGER NOT NULL,
                        structure_type TEXT NOT NULL,
                        level INTEGER NOT NULL DEFAULT 1,
                        max_level INTEGER NOT NULL DEFAULT 30,
                        pos_x REAL NOT NULL DEFAULT 50.00,
                        pos_y REAL NOT NULL DEFAULT 50.00,
                        workers_assigned INTEGER NOT NULL DEFAULT 2,
                        prod_hourly REAL NOT NULL DEFAULT 0.00,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE (planet_id, slot_id),
                        UNIQUE (planet_id, structure_type)
                    );
                ");
            } else {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS `planet_rural_plots` (
                        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        `planet_id` INT UNSIGNED NOT NULL,
                        `slot_id` TINYINT UNSIGNED NOT NULL,
                        `structure_type` VARCHAR(32) NOT NULL,
                        `level` INT UNSIGNED NOT NULL DEFAULT 1,
                        `max_level` INT UNSIGNED NOT NULL DEFAULT 30,
                        `pos_x` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                        `pos_y` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                        `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
                        `prod_hourly` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY `uniq_planet_slot` (`planet_id`, `slot_id`),
                        UNIQUE KEY `uniq_planet_structure` (`planet_id`, `structure_type`),
                        KEY `idx_planet_id` (`planet_id`),
                        CONSTRAINT `fk_rural_plots_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
            self::$tableChecked = true;
        } catch (Exception $e) {
            // Ignorer silencieusement si table déjà présente
        }
    }

    /**
     * Génère de manière procédurale les 9 parcelles d'un village
     *
     * @param int $planetId
     * @param bool $shufflePositions Mélanger ou conserver l'affinité topologique naturelle
     * @param bool $forceRecreate Écraser les parcelles existantes
     * @param bool $startAtZero Forcer toutes les structures au niveau 0 (ex: réinitialisation du monde)
     * @return array Liste des 9 parcelles générées
     */
    public function generateVillage(int $planetId, bool $shufflePositions = false, bool $forceRecreate = false, bool $startAtZero = false): array {
        if (!$this->db) return [];

        // Vérifier si des parcelles existent déjà
        if (!$forceRecreate) {
            $stmt = $this->db->prepare("SELECT * FROM planet_rural_plots WHERE planet_id = ? ORDER BY slot_id ASC");
            $stmt->execute([$planetId]);
            $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($existing) === 9) {
                return $existing;
            }
        }

        // Si reconstruction forcée, purger
        if ($forceRecreate) {
            $del = $this->db->prepare("DELETE FROM planet_rural_plots WHERE planet_id = ?");
            $del->execute([$planetId]);
        }

        // 1. Détermination de la spécialité majeure du fief (Archétype naturel)
        // Permet de donner à chaque fief une vocation unique (ex: grand fief céréalier vs gisement de granit)
        $specialties = ['foret', 'carriere', 'fosse_argile', 'riziere', 'champ_soja', 'culture_the'];
        $fiefSpecialty = $specialties[array_rand($specialties)];
        $secondarySpecialty = $specialties[array_rand($specialties)];

        // 2. Détermination de la distribution spatiale des 9 parcelles
        $assignment = [];
        if ($shufflePositions) {
            // Tenshu ancré au château (slot 1) et Village au centre (slot 5) pour la lisibilité
            // Les 7 parcelles de ressources sont mélangées sur les 7 autres points d'ancrage
            $resourceTypes = ['foret', 'carriere', 'fosse_argile', 'riziere', 'champ_soja', 'culture_the', 'sanctuaire_shinto'];
            $resourceAnchors = [2, 3, 4, 6, 7, 8, 9];
            shuffle($resourceTypes);

            $assignment[1] = 'tenshu';
            $assignment[5] = 'village';
            foreach ($resourceAnchors as $idx => $slotId) {
                $assignment[$slotId] = $resourceTypes[$idx];
            }
        } else {
            // Affinité naturelle canonique (chaque parcelle sur son biotope visuel)
            foreach (self::ANCHOR_SLOTS as $slotId => $anchor) {
                $assignment[$slotId] = $anchor['affinity'];
            }
        }

        // 3. Tirage des niveaux et plafonds (Progression 20 à 100)
        $generatedPlots = [];

        foreach ($assignment as $slotId => $structureType) {
            $anchor = self::ANCHOR_SLOTS[$slotId];

            // Légère variation procédurale des coordonnées (±0.8%) pour un rendu vivant
            $jitterX = (mt_rand(-8, 8) / 10.0);
            $jitterY = (mt_rand(-8, 8) / 10.0);
            $posX = round($anchor['x'] + $jitterX, 2);
            $posY = round($anchor['y'] + $jitterY, 2);

            // Tirage du niveau max atteignable (Plafond vertical entre 20 et 100)
            if ($structureType === 'tenshu') {
                // Tenshu : progression solide de 30 à 60
                $maxLevel = mt_rand(30, 60);
                $initialLevel = $startAtZero ? 0 : 1;
            } elseif ($structureType === 'village') {
                // Village : 40 à 80 pour soutenir la population
                $maxLevel = mt_rand(40, 80);
                $initialLevel = $startAtZero ? 0 : 1;
            } elseif ($structureType === $fiefSpecialty) {
                // Jackpot / Filon d'or du fief : potentiel exceptionnel (Niv. 75 à 100)
                $maxLevel = mt_rand(75, 100);
                $initialLevel = $startAtZero ? 0 : mt_rand(1, 3);
            } elseif ($structureType === $secondarySpecialty) {
                // Spécialité secondaire : potentiel élevé (Niv. 50 à 75)
                $maxLevel = mt_rand(50, 75);
                $initialLevel = $startAtZero ? 0 : mt_rand(1, 2);
            } else {
                // Potentiel standard équilibré : 25 à 45
                $maxLevel = mt_rand(25, 45);
                $initialLevel = $startAtZero ? 0 : 1;
            }

            $workers = ($initialLevel <= 0) ? 0 : max(2, (int)round(2 + ($initialLevel * 1.5)));
            $prodHourly = self::calculateHourlyProduction($structureType, $initialLevel);

            $generatedPlots[] = [
                'planet_id'        => $planetId,
                'slot_id'          => $slotId,
                'structure_type'   => $structureType,
                'level'            => $initialLevel,
                'max_level'        => $maxLevel,
                'pos_x'            => $posX,
                'pos_y'            => $posY,
                'workers_assigned' => $workers,
                'prod_hourly'      => $prodHourly,
            ];
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmtInsert = $this->db->prepare("
                INSERT OR REPLACE INTO planet_rural_plots (
                    planet_id, slot_id, structure_type, level, max_level, pos_x, pos_y, workers_assigned, prod_hourly
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        } else {
            $stmtInsert = $this->db->prepare("
                INSERT INTO planet_rural_plots (
                    planet_id, slot_id, structure_type, level, max_level, pos_x, pos_y, workers_assigned, prod_hourly
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    level = VALUES(level),
                    max_level = VALUES(max_level),
                    pos_x = VALUES(pos_x),
                    pos_y = VALUES(pos_y),
                    workers_assigned = VALUES(workers_assigned),
                    prod_hourly = VALUES(prod_hourly)
            ");
        }

        foreach ($generatedPlots as $p) {
            $stmtInsert->execute([
                $p['planet_id'],
                $p['slot_id'],
                $p['structure_type'],
                $p['level'],
                $p['max_level'],
                $p['pos_x'],
                $p['pos_y'],
                $p['workers_assigned'],
                $p['prod_hourly']
            ]);
        }

        return $generatedPlots;
    }

    /**
     * Conversion / Migration douce d'un fief existant vers les 9 parcelles uniques
     * Conserve les progrès réalisés (niveaux du Tenshu et moyennes de mines/champs)
     */
    public function convertExistingPlanet(int $planetId): array {
        if (!$this->db) return [];

        // Récupérer les données existantes de la planète
        $existingBuildings = [];
        try {
            $bStmt = $this->db->prepare("SELECT building_type, level FROM planet_buildings WHERE planet_id = ?");
            $bStmt->execute([$planetId]);
            while ($r = $bStmt->fetch(PDO::FETCH_ASSOC)) {
                $existingBuildings[$r['building_type']] = (int)$r['level'];
            }
        } catch (Exception $e) {}

        $fieldAvgs = [];
        try {
            $fStmt = $this->db->prepare("SELECT field_type, AVG(level) as avg_lvl, MAX(level) as max_lvl FROM planet_fields WHERE planet_id = ? GROUP BY field_type");
            $fStmt->execute([$planetId]);
            while ($r = $fStmt->fetch(PDO::FETCH_ASSOC)) {
                $fieldAvgs[$r['field_type']] = (int)round((float)$r['avg_lvl']);
            }
        } catch (Exception $e) {}

        $terroirAvgs = [];
        try {
            $tStmt = $this->db->prepare("SELECT resource_type, AVG(level) as avg_lvl FROM planet_terroir_slots WHERE planet_id = ? GROUP BY resource_type");
            $tStmt->execute([$planetId]);
            while ($r = $tStmt->fetch(PDO::FETCH_ASSOC)) {
                $terroirAvgs[$r['resource_type']] = (int)round((float)$r['avg_lvl']);
            }
        } catch (Exception $e) {}

        // Génération de base
        $plots = $this->generateVillage($planetId, false, true);

        // Ajuster les niveaux selon l'historique
        $updates = [
            'tenshu'           => max(1, $existingBuildings['hq'] ?? 1),
            'foret'            => max(1, $fieldAvgs['metal_mine'] ?? 1),
            'carriere'         => max(1, $fieldAvgs['crystal_mine'] ?? 1),
            'riziere'          => max(1, $fieldAvgs['deuterium_synth'] ?? 1),
            'fosse_argile'     => max(1, $terroirAvgs['clay'] ?? 1),
            'culture_the'      => max(1, $terroirAvgs['tea'] ?? 1),
            'champ_soja'       => max(1, $terroirAvgs['soybean'] ?? 1),
            'sanctuaire_shinto' => max(1, $terroirAvgs['shrine'] ?? 1),
            'village'          => max(1, $terroirAvgs['housing'] ?? ($terroirAvgs['village'] ?? 1)),
        ];

        $stmtUp = $this->db->prepare("UPDATE planet_rural_plots SET level = ?, max_level = GREATEST(max_level, ? + 15), prod_hourly = ? WHERE planet_id = ? AND structure_type = ?");
        foreach ($updates as $type => $lvl) {
            $prod = self::calculateHourlyProduction($type, $lvl);
            $stmtUp->execute([$lvl, $lvl, $prod, $planetId, $type]);
        }

        return $this->getPlanetPlots($planetId);
    }

    /**
     * Récupère les 9 parcelles d'un village
     */
    public function getPlanetPlots(int $planetId): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM planet_rural_plots WHERE planet_id = ? ORDER BY slot_id ASC");
        $stmt->execute([$planetId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 9) {
            return $rows;
        }
        // Auto-génération si manquant
        return $this->generateVillage($planetId);
    }

    /**
     * S'assure que toutes les planètes de la base possèdent leurs 9 parcelles
     */
    public function ensureAllPlanetsInitialized(): int {
        if (!$this->db) return 0;
        $stmt = $this->db->query("SELECT id FROM planets");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pId = (int)$row['id'];
            $chk = $this->db->prepare("SELECT COUNT(*) FROM planet_rural_plots WHERE planet_id = ?");
            $chk->execute([$pId]);
            if ((int)$chk->fetchColumn() < 9) {
                $this->convertExistingPlanet($pId);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Calcul de la cadence de production horaire par structure et niveau
     */
    public static function calculateHourlyProduction(string $structureType, int $level): float {
        if ($level <= 0) {
            return 0.0;
        }
        if ($structureType === 'tenshu') {
            return 0.0;
        }
        if ($structureType === 'village') {
            // Capacité de logements : 75 + level * 25
            return (float)(75 + ($level * 25));
        }
        if ($structureType === 'sanctuaire_shinto') {
            // Sérénité : level * 30
            return (float)($level * 30);
        }

        // Ressources primaires & secondaires : progression puissance 1.38
        $baseRate = 35.0;
        $prod = $baseRate * pow($level, 1.38) * (1.0 + ($level * 0.015));
        return round($prod, 1);
    }
}
