<?php
/**
 * ImperialDojoEngine.php
 * Moteur de gestion de la Bataille Finale & de la Construction du Dōjō Impérial sur la tuile [0 : 0]
 * (Tenchi no Seichi - 天地の聖地)
 */

require_once __DIR__ . '/Database.php';

class ImperialDojoEngine {
    private ?PDO $db = null;

    /**
     * Définition des 10 Niveaux Titanesques du Dōjō Impérial
     */
    public const LEVEL_CONFIG = [
        1 => [
            'name'          => 'Purification du Sol Sacré (Jichinsai - 地鎮祭)',
            'wood'          => 2500000,
            'granite'       => 1000000,
            'metal'         => 0,
            'rice'          => 1500000,
            'beams'         => 500,
            'tiles'         => 0,
            'oni_tiles'     => 0,
            'gold'          => 0,
            'build_time'    => 3600, // En secondes (ajustable par vitesse)
            'max_hp'        => 5000000,
            'description'   => 'Purification rituelle du tertre central, terrassement du sanctuaire et creusement des fossés d\'eau sacrés.'
        ],
        2 => [
            'name'          => 'Socle Cyclopéen d\'Ishigaki (石垣)',
            'wood'          => 4000000,
            'granite'       => 3500000,
            'metal'         => 500000,
            'rice'          => 2500000,
            'beams'         => 1500,
            'tiles'         => 0,
            'oni_tiles'     => 0,
            'gold'          => 0,
            'build_time'    => 7200,
            'max_hp'        => 10000000,
            'description'   => 'Érection de remparts de granit taillé à angle droit et fondations impériales insensibles aux séismes.'
        ],
        3 => [
            'name'          => 'Piliers de Cèdre Millénaire (Daikokubashira - 大黒柱)',
            'wood'          => 8000000,
            'granite'       => 5000000,
            'metal'         => 4000000,
            'rice'          => 3000000,
            'beams'         => 3500,
            'tiles'         => 0,
            'oni_tiles'     => 0,
            'gold'          => 5000,
            'build_time'    => 14400,
            'max_hp'        => 18000000,
            'description'   => 'Dressage des piliers maîtres de cèdre du Mont Hiei soutenant l\'ossature monumentale.'
        ],
        4 => [
            'name'          => 'Nef des Dix Mille Lames (Budō Tenshu - 武道天守)',
            'wood'          => 10000000,
            'granite'       => 6000000,
            'metal'         => 10000000,
            'rice'          => 5000000,
            'beams'         => 5000,
            'tiles'         => 0,
            'oni_tiles'     => 0,
            'gold'          => 15000,
            'build_time'    => 21600,
            'max_hp'        => 28000000,
            'description'   => 'Grande salle d\'armes consacrée, râteliers de katanas sacrés et autels d\'entraînement des maîtres du sabre.'
        ],
        5 => [
            'name'          => 'Rempart Yagura d\'Acier (Hagane Yagura - 鋼櫓)',
            'wood'          => 12000000,
            'granite'       => 8000000,
            'metal'         => 15000000,
            'rice'          => 3000000,
            'beams'         => 6500,
            'tiles'         => 0,
            'oni_tiles'     => 0,
            'gold'          => 20000,
            'build_time'    => 28800,
            'max_hp'        => 40000000,
            'description'   => 'Tours de guet fortifiées intégrées, blindage en plaques de fer trempé et casemates d\'archers d\'élite.'
        ],
        6 => [
            'name'          => 'Premier Étage Céleste (Tenshin no Kai - 天心の階)',
            'wood'          => 15000000,
            'granite'       => 10000000,
            'metal'         => 12000000,
            'rice'          => 4000000,
            'beams'         => 8000,
            'tiles'         => 5000,
            'oni_tiles'     => 0,
            'gold'          => 25000,
            'build_time'    => 36000,
            'max_hp'        => 55000000,
            'description'   => 'Élévation du belvédère intermédiaire et pose des premières rangées de tuiles Hongawara ignifugées.'
        ],
        7 => [
            'name'          => 'Pavillon du Shōgunat (Shōgun no Ma - 将軍の間)',
            'wood'          => 18000000,
            'granite'       => 12000000,
            'metal'         => 15000000,
            'rice'          => 6000000,
            'beams'         => 10000,
            'tiles'         => 15000,
            'oni_tiles'     => 0,
            'gold'          => 35000,
            'build_time'    => 43200,
            'max_hp'        => 70000000,
            'description'   => 'Salle d\'audience impériale ornée d\'or et de paravents de soie, trône du futur maître de l\'archipel.'
        ],
        8 => [
            'name'          => 'Toiture Impériale Hongawara (Kawara Buki - 瓦葺)',
            'wood'          => 20000000,
            'granite'       => 15000000,
            'metal'         => 18000000,
            'rice'          => 8000000,
            'beams'         => 12000,
            'tiles'         => 35000,
            'oni_tiles'     => 50,
            'gold'          => 50000,
            'build_time'    => 50400,
            'max_hp'        => 90000000,
            'description'   => 'Toiture monumentale à quadruple avant-toit scellée par 35 000 tuiles et les premières Onigawara démoniaques.'
        ],
        9 => [
            'name'          => 'Le Belvédère des Nuages (Kumo no Tenshu - 雲の天守)',
            'wood'          => 25000000,
            'granite'       => 20000000,
            'metal'         => 25000000,
            'rice'          => 10000000,
            'beams'         => 15000,
            'tiles'         => 60000,
            'oni_tiles'     => 120,
            'gold'          => 75000,
            'build_time'    => 64800,
            'max_hp'        => 120000000,
            'description'   => 'Dernière terrasse surplombant les 66 provinces du Japon. Toiture renforcée contre les bombes de siège.'
        ],
        10 => [
            'name'          => 'L\'Apothéose Céleste : Le Trône d\'Or (Seii Taishōgun - 征夷大将軍)',
            'wood'          => 40000000,
            'granite'       => 30000000,
            'metal'         => 35000000,
            'rice'          => 15000000,
            'beams'         => 20000,
            'tiles'         => 100000,
            'oni_tiles'     => 250,
            'gold'          => 100000,
            'build_time'    => 86400,
            'max_hp'        => 160000000,
            'description'   => 'Point culminant absolu du monde. Pose des Kinshachi dorés et sacre de l\'Alliance au Shōgunat éternel !'
        ]
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureTables();
        $this->ensureDojoTile();
    }

    /**
     * Auto-guérison et création des tables de la Bataille Finale
     */
    public function ensureTables(): void {
        if (!$this->db) return;
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `imperial_dojo` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `coord_x` INT NOT NULL DEFAULT 0,
                    `coord_y` INT NOT NULL DEFAULT 0,
                    `controlling_alliance_id` INT UNSIGNED NULL,
                    `current_level` INT NOT NULL DEFAULT 0,
                    `status` ENUM('unclaimed', 'under_construction', 'frozen', 'completed') NOT NULL DEFAULT 'unclaimed',
                    `health_points` BIGINT NOT NULL DEFAULT 0,
                    `max_health_points` BIGINT NOT NULL DEFAULT 5000000,
                    `construction_started_at` INT NULL,
                    `construction_finishes_at` INT NULL,
                    `target_level` INT NOT NULL DEFAULT 1,
                    `stock_wood` BIGINT NOT NULL DEFAULT 0,
                    `stock_granite` BIGINT NOT NULL DEFAULT 0,
                    `stock_metal` BIGINT NOT NULL DEFAULT 0,
                    `stock_rice` BIGINT NOT NULL DEFAULT 0,
                    `stock_beams` INT NOT NULL DEFAULT 0,
                    `stock_tiles` INT NOT NULL DEFAULT 0,
                    `stock_oni_tiles` INT NOT NULL DEFAULT 0,
                    `stock_gold` INT NOT NULL DEFAULT 0,
                    `created_at` INT NOT NULL,
                    `updated_at` INT NOT NULL,
                    INDEX `idx_coords` (`coord_x`, `coord_y`),
                    INDEX `idx_alliance` (`controlling_alliance_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `imperial_dojo_contributions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `dojo_id` INT NOT NULL,
                    `alliance_id` INT UNSIGNED NOT NULL,
                    `user_id` INT NOT NULL,
                    `level` INT NOT NULL,
                    `resource_type` VARCHAR(50) NOT NULL,
                    `amount` BIGINT NOT NULL,
                    `contributed_at` INT NOT NULL,
                    INDEX `idx_user` (`user_id`),
                    INDEX `idx_alliance_level` (`alliance_id`, `level`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `imperial_dojo_garrison` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `dojo_id` INT NOT NULL,
                    `alliance_id` INT UNSIGNED NOT NULL,
                    `user_id` INT NOT NULL,
                    `unit_code` VARCHAR(50) NOT NULL,
                    `amount` INT NOT NULL,
                    `arrived_at` INT NOT NULL,
                    INDEX `idx_dojo_user` (`dojo_id`, `user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `imperial_dojo_siege_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `dojo_id` INT NOT NULL,
                    `attacker_user_id` INT NOT NULL,
                    `attacker_alliance_id` INT UNSIGNED NULL,
                    `target_alliance_id` INT UNSIGNED NULL,
                    `damage_dealt` BIGINT NOT NULL,
                    `hp_before` BIGINT NOT NULL,
                    `hp_after` BIGINT NOT NULL,
                    `level_before` INT NOT NULL,
                    `level_after` INT NOT NULL,
                    `was_regressed` TINYINT(1) NOT NULL DEFAULT 0,
                    `log_text` TEXT NOT NULL,
                    `created_at` INT NOT NULL,
                    INDEX `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            error_log("ImperialDojoEngine::ensureTables error: " . $e->getMessage());
        }
    }

    /**
     * Initialise l'enregistrement unique du Dōjō Impérial en [0 : 0]
     */
    public function ensureDojoTile(): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM imperial_dojo WHERE coord_x = 0 AND coord_y = 0 LIMIT 1");
        $stmt->execute();
        $dojo = $stmt->fetch();
        if (!$dojo) {
            $now = time();
            $stmtInsert = $this->db->prepare("
                INSERT INTO imperial_dojo (
                    coord_x, coord_y, controlling_alliance_id, current_level, status,
                    health_points, max_health_points, target_level,
                    stock_wood, stock_granite, stock_metal, stock_rice,
                    stock_beams, stock_tiles, stock_oni_tiles, stock_gold,
                    created_at, updated_at
                ) VALUES (
                    0, 0, NULL, 0, 'unclaimed',
                    0, 5000000, 1,
                    0, 0, 0, 0,
                    0, 0, 0, 0,
                    ?, ?
                )
            ");
            $stmtInsert->execute([$now, $now]);
            $stmt->execute();
            $dojo = $stmt->fetch();
        }

        // S'assurer qu'une entrée existe dans `planets` aux coordonnées 0:0 pour la résolution des flottes
        try {
            $stmtP = $this->db->prepare("SELECT id FROM planets WHERE coord_x = 0 AND coord_y = 0 LIMIT 1");
            $stmtP->execute();
            if (!$stmtP->fetchColumn()) {
                $stmtInsP = $this->db->prepare("
                    INSERT INTO planets (name, coord_x, coord_y, planet_type, user_id, metal, crystal, deuterium, is_capital, last_resource_update, created_at)
                    VALUES ('Dōjō Impérial (Tenchi no Seichi)', 0, 0, 'imperial_dojo', NULL, 0, 0, 0, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                ");
                $stmtInsP->execute();
            }
        } catch (Throwable $e) {}

        return $dojo ?: [];
    }

    /**
     * Récupère l'état complet du Dōjō Impérial
     */
    public function getDojoState(): array {
        $dojo = $this->ensureDojoTile();
        $this->updateConstructionStatus($dojo);
        // Rafraîchir après mise à jour potentielle
        $stmt = $this->db->prepare("
            SELECT d.*, a.name as alliance_name, a.tag as alliance_tag, a.leader_id
            FROM imperial_dojo d
            LEFT JOIN alliances a ON d.controlling_alliance_id = a.id
            WHERE d.coord_x = 0 AND d.coord_y = 0
            LIMIT 1
        ");
        $stmt->execute();
        $res = $stmt->fetch() ?: $dojo;

        $targetLvl = max(1, min(10, (int)$res['target_level']));
        $res['target_level_config'] = self::LEVEL_CONFIG[$targetLvl] ?? self::LEVEL_CONFIG[1];

        // Nombre de châteaux authentiques détenus par l'alliance bâtisseuse
        $res['castles_owned'] = 0;
        if (!empty($res['controlling_alliance_id'])) {
            $res['castles_owned'] = $this->countAllianceCastles((int)$res['controlling_alliance_id']);
        }

        // Taux d'achèvement des ressources pour le palier visé
        $res['progress_percent'] = $this->calculateStockProgress($res, $targetLvl);
        return $res;
    }

    /**
     * Calcule le pourcentage global de ressources réunies pour le palier cible
     */
    public function calculateStockProgress(array $dojo, int $targetLvl): int {
        $cfg = self::LEVEL_CONFIG[$targetLvl] ?? self::LEVEL_CONFIG[1];
        $weights = [
            'wood'      => $cfg['wood'],
            'granite'   => $cfg['granite'],
            'metal'     => $cfg['metal'],
            'rice'      => $cfg['rice'],
            'beams'     => $cfg['beams'],
            'tiles'     => $cfg['tiles'],
            'oni_tiles' => $cfg['oni_tiles'],
            'gold'      => $cfg['gold']
        ];
        $totalRequired = 0;
        $totalAcquired = 0;
        foreach ($weights as $k => $req) {
            if ($req <= 0) continue;
            $stockKey = 'stock_' . $k;
            $curr = (int)($dojo[$stockKey] ?? 0);
            $totalRequired += $req;
            $totalAcquired += min($req, $curr);
        }
        if ($totalRequired <= 0) return 100;
        return (int)min(100, round(($totalAcquired / $totalRequired) * 100));
    }

    /**
     * Vérifie si une alliance détient au moins 2 châteaux authentiques
     */
    public function countAllianceCastles(int $allianceId): int {
        if (!$this->db || $allianceId <= 0) return 0;
        try {
            // Un château authentique est contrôlé soit par la faction/tag d'alliance, soit par un membre de l'alliance
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT c.id)
                FROM authentic_castles c
                JOIN users u ON c.controlling_user_id = u.id
                WHERE c.is_spawned = 1 AND u.alliance_id = ?
            ");
            $stmt->execute([$allianceId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Revendique le chantier de [0 : 0] pour une alliance éligible (>= 2 Châteaux)
     */
    public function claimDojoForAlliance(int $allianceId, int $userId): array {
        if (!$this->db) return ['success' => false, 'error' => 'Base de données indisponible'];
        $castlesCount = $this->countAllianceCastles($allianceId);
        if ($castlesCount < 2) {
            return [
                'success' => false,
                'error' => "Votre alliance ne détient que {$castlesCount} / 2 Châteaux Authentiques requis. Conquérez au moins 2 donjons ancestraux pour revendiquer la Terre Sacrée [0 : 0] !"
            ];
        }

        $dojo = $this->getDojoState();
        if (!empty($dojo['controlling_alliance_id']) && (int)$dojo['controlling_alliance_id'] !== $allianceId) {
            return [
                'success' => false,
                'error' => "Le Dōjō Impérial est actuellement sous le contrôle du clan [{$dojo['alliance_tag']}]. Vous devez d'abord détruire leurs défenses ou briser leur domination castrale !"
            ];
        }

        $now = time();
        $stmt = $this->db->prepare("
            UPDATE imperial_dojo 
            SET controlling_alliance_id = ?, status = 'under_construction', updated_at = ?
            WHERE coord_x = 0 AND coord_y = 0
        ");
        $stmt->execute([$allianceId, $now]);

        return [
            'success' => true,
            'message' => "La Terre Sacrée [0 : 0] est désormais sous la tutelle de votre alliance ! Les artisans impériaux peuvent commencer à acheminer les matériaux."
        ];
    }

    /**
     * Contribution de ressources d'un joueur pour le chantier du Dōjō Impérial
     */
    public function contributeResources(int $userId, int $planetId, array $contributions): array {
        if (!$this->db) return ['success' => false, 'error' => 'Erreur SQL'];

        // Vérifier l'alliance du joueur
        $stmtUser = $this->db->prepare("SELECT id, username, alliance_id FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        if (!$user || empty($user['alliance_id'])) {
            return ['success' => false, 'error' => 'Vous devez appartenir à une alliance pour ravitailler le chantier de [0 : 0].'];
        }

        $allianceId = (int)$user['alliance_id'];
        $dojo = $this->getDojoState();

        if (empty($dojo['controlling_alliance_id'])) {
            // Tenter de revendiquer automatiquement si éligible
            $claimRes = $this->claimDojoForAlliance($allianceId, $userId);
            if (!$claimRes['success']) {
                return $claimRes;
            }
            $dojo = $this->getDojoState();
        } elseif ((int)$dojo['controlling_alliance_id'] !== $allianceId) {
            return ['success' => false, 'error' => 'Ce chantier appartient à une alliance adverse. Seuls ses membres peuvent y déposer des matériaux.'];
        }

        // Vérifier le gel
        $castlesCount = $this->countAllianceCastles($allianceId);
        if ($castlesCount < 2) {
            $this->db->prepare("UPDATE imperial_dojo SET status = 'frozen' WHERE coord_x = 0 AND coord_y = 0")->execute();
            return [
                'success' => false,
                'error' => "Le chantier est GELÉ ! Votre alliance a perdu le contrôle d'un Château Authentique ({$castlesCount}/2). Reprenez vos 2 châteaux avant de poursuivre les travaux !"
            ];
        }

        // Vérifier les ressources de la planète
        $stmtPlanet = $this->db->prepare("
            SELECT id, user_id, metal, crystal, deuterium, rice, timber_beams, tiles_kawara, tiles_onigawara, koban_gold
            FROM planets WHERE id = ? AND user_id = ?
        ");
        $stmtPlanet->execute([$planetId, $userId]);
        $planet = $stmtPlanet->fetch();
        if (!$planet) {
            return ['success' => false, 'error' => 'Fief d\'origine invalide.'];
        }

        $targetLvl = max(1, min(10, (int)$dojo['target_level']));
        $cfg = self::LEVEL_CONFIG[$targetLvl];

        // Calcul des plafonds pour ne pas accepter plus que nécessaire
        $allowed = [
            'wood'       => ['col' => 'crystal',          'stock' => 'stock_wood',       'req' => $cfg['wood']],
            'granite'    => ['col' => 'deuterium',        'stock' => 'stock_granite',    'req' => $cfg['granite']],
            'metal'      => ['col' => 'metal',            'stock' => 'stock_metal',      'req' => $cfg['metal']],
            'rice'       => ['col' => 'rice',             'stock' => 'stock_rice',       'req' => $cfg['rice']],
            'beams'      => ['col' => 'timber_beams',     'stock' => 'stock_beams',      'req' => $cfg['beams']],
            'tiles'      => ['col' => 'tiles_kawara',     'stock' => 'stock_tiles',      'req' => $cfg['tiles']],
            'oni_tiles'  => ['col' => 'tiles_onigawara',  'stock' => 'stock_oni_tiles',  'req' => $cfg['oni_tiles']],
            'gold'       => ['col' => 'koban_gold',       'stock' => 'stock_gold',       'req' => $cfg['gold']]
        ];

        $deductions = [];
        $additions = [];
        $totalDonatedItems = 0;

        $this->db->beginTransaction();
        try {
            foreach ($allowed as $resType => $info) {
                $requestedAmount = max(0, (int)($contributions[$resType] ?? 0));
                if ($requestedAmount <= 0) continue;

                $planetAvailable = (int)($planet[$info['col']] ?? 0);
                $currentStock = (int)($dojo[$info['stock']] ?? 0);
                $stillNeeded = max(0, $info['req'] - $currentStock);

                if ($stillNeeded <= 0) continue; // Déjà plein pour ce palier

                $effectiveAmount = min($requestedAmount, $planetAvailable, $stillNeeded);
                if ($effectiveAmount > 0) {
                    $deductions[$info['col']] = $effectiveAmount;
                    $additions[$info['stock']] = $effectiveAmount;

                    // Enregistrer contribution
                    $stmtLog = $this->db->prepare("
                        INSERT INTO imperial_dojo_contributions (
                            dojo_id, alliance_id, user_id, level, resource_type, amount, contributed_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtLog->execute([
                        (int)$dojo['id'], $allianceId, $userId, $targetLvl, $resType, $effectiveAmount, time()
                    ]);
                    $totalDonatedItems += $effectiveAmount;
                }
            }

            if ($totalDonatedItems <= 0) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Aucune ressource valide n\'a pu être transférée (stocks insuffisants ou quota du palier déjà atteint).'];
            }

            // Déduire du fief
            $updatePlanetSql = "UPDATE planets SET ";
            $paramsPlanet = [];
            foreach ($deductions as $col => $val) {
                $updatePlanetSql .= "`$col` = GREATEST(0, `$col` - ?), ";
                $paramsPlanet[] = $val;
            }
            $updatePlanetSql = rtrim($updatePlanetSql, ', ') . " WHERE id = ?";
            $paramsPlanet[] = $planetId;
            $this->db->prepare($updatePlanetSql)->execute($paramsPlanet);

            // Ajouter au Dōjō
            $now = time();
            $updateDojoSql = "UPDATE imperial_dojo SET ";
            $paramsDojo = [];
            foreach ($additions as $col => $val) {
                $updateDojoSql .= "`$col` = `$col` + ?, ";
                $paramsDojo[] = $val;
            }
            $updateDojoSql .= "`updated_at` = ? WHERE id = ?";
            $paramsDojo[] = $now;
            $paramsDojo[] = (int)$dojo['id'];
            $this->db->prepare($updateDojoSql)->execute($paramsDojo);

            $this->db->commit();

            // Vérifier si le palier est désormais complet et peut démarrer sa construction
            $this->checkAndStartPalierConstruction((int)$dojo['id']);

            return [
                'success' => true,
                'message' => "Vos convois de ravitaillement sont arrivés en Terre Sacrée [0 : 0] ! Vos dons ont été scellés dans le grand registre impérial."
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors du transfert : ' . $e->getMessage()];
        }
    }

    /**
     * Vérifie si tous les quotas du palier sont remplis pour lancer le compte à rebours de construction
     */
    public function checkAndStartPalierConstruction(int $dojoId): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT * FROM imperial_dojo WHERE id = ?");
        $stmt->execute([$dojoId]);
        $dojo = $stmt->fetch();
        if (!$dojo || $dojo['status'] === 'completed' || $dojo['construction_finishes_at'] !== null) {
            return false;
        }

        $targetLvl = max(1, min(10, (int)$dojo['target_level']));
        $cfg = self::LEVEL_CONFIG[$targetLvl];

        if (
            (int)$dojo['stock_wood'] >= $cfg['wood'] &&
            (int)$dojo['stock_granite'] >= $cfg['granite'] &&
            (int)$dojo['stock_metal'] >= $cfg['metal'] &&
            (int)$dojo['stock_rice'] >= $cfg['rice'] &&
            (int)$dojo['stock_beams'] >= $cfg['beams'] &&
            (int)$dojo['stock_tiles'] >= $cfg['tiles'] &&
            (int)$dojo['stock_oni_tiles'] >= $cfg['oni_tiles'] &&
            (int)$dojo['stock_gold'] >= $cfg['gold']
        ) {
            // Calcul du temps avec bonus de châteaux supplémentaires (+15% vitesse par château > 2)
            $allianceId = (int)$dojo['controlling_alliance_id'];
            $castles = $this->countAllianceCastles($allianceId);
            $extraCastles = max(0, $castles - 2);
            $speedMultiplier = 1.0 + ($extraCastles * 0.15);

            $baseTime = $cfg['build_time'];
            $speedFactor = defined('SPEED_FACTOR') ? SPEED_FACTOR : 5;
            $adjustedTime = (int)max(60, round(($baseTime / $speedFactor) / $speedMultiplier));

            $now = time();
            $finishAt = $now + $adjustedTime;

            $stmtStart = $this->db->prepare("
                UPDATE imperial_dojo 
                SET status = 'under_construction',
                    construction_started_at = ?,
                    construction_finishes_at = ?,
                    updated_at = ?
                WHERE id = ?
            ");
            $stmtStart->execute([$now, $finishAt, $now, $dojoId]);
            return true;
        }
        return false;
    }

    /**
     * Met à jour l'état de construction si le temps est écoulé
     */
    public function updateConstructionStatus(array &$dojo): void {
        if (!$this->db || empty($dojo['id'])) return;
        $now = time();

        // 1. Si gelé et a retrouvé 2 châteaux, dégel
        if ($dojo['status'] === 'frozen') {
            if (!empty($dojo['controlling_alliance_id']) && $this->countAllianceCastles((int)$dojo['controlling_alliance_id']) >= 2) {
                $this->db->prepare("UPDATE imperial_dojo SET status = 'under_construction', updated_at = ? WHERE id = ?")->execute([$now, $dojo['id']]);
                $dojo['status'] = 'under_construction';
            }
            return;
        }

        // 2. Si en cours de construction et temps atteint
        if (
            $dojo['status'] === 'under_construction' &&
            !empty($dojo['construction_finishes_at']) &&
            $now >= (int)$dojo['construction_finishes_at']
        ) {
            $allianceId = (int)$dojo['controlling_alliance_id'];
            // Vérifier strict maintien des 2 châteaux
            if ($this->countAllianceCastles($allianceId) < 2) {
                $this->db->prepare("UPDATE imperial_dojo SET status = 'frozen', updated_at = ? WHERE id = ?")->execute([$now, $dojo['id']]);
                $dojo['status'] = 'frozen';
                return;
            }

            $completedLvl = (int)$dojo['target_level'];
            $cfg = self::LEVEL_CONFIG[$completedLvl];
            $newMaxHp = $cfg['max_hp'];
            $isFinalVictory = ($completedLvl >= 10);

            $newStatus = $isFinalVictory ? 'completed' : 'under_construction';
            $nextTarget = $isFinalVictory ? 10 : ($completedLvl + 1);

            $stmtComplete = $this->db->prepare("
                UPDATE imperial_dojo 
                SET current_level = ?,
                    target_level = ?,
                    status = ?,
                    health_points = ?,
                    max_health_points = ?,
                    construction_started_at = NULL,
                    construction_finishes_at = NULL,
                    stock_wood = 0,
                    stock_granite = 0,
                    stock_metal = 0,
                    stock_rice = 0,
                    stock_beams = 0,
                    stock_tiles = 0,
                    stock_oni_tiles = 0,
                    stock_gold = 0,
                    updated_at = ?
                WHERE id = ?
            ");
            $stmtComplete->execute([
                $completedLvl, $nextTarget, $newStatus, $newMaxHp, $newMaxHp, $now, (int)$dojo['id']
            ]);

            // Recharger les données locales
            $dojo['current_level'] = $completedLvl;
            $dojo['target_level'] = $nextTarget;
            $dojo['status'] = $newStatus;
            $dojo['health_points'] = $newMaxHp;
            $dojo['max_health_points'] = $newMaxHp;
            $dojo['construction_started_at'] = null;
            $dojo['construction_finishes_at'] = null;

            // Déclencher le triomphe si Niveau 10
            if ($isFinalVictory) {
                $this->triggerServerVictory($allianceId);
            }
        }
    }

    /**
     * Déclenche la victoire finale du serveur et le couronnement
     */
    public function triggerServerVictory(int $allianceId): void {
        if (!$this->db || $allianceId <= 0) return;
        try {
            // Créer la table du Panthéon si elle n'existe pas
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `pantheon_eras` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `era_name` VARCHAR(100) NOT NULL,
                    `winning_alliance_id` INT UNSIGNED NOT NULL,
                    `winning_alliance_name` VARCHAR(100) NOT NULL,
                    `winning_alliance_tag` VARCHAR(10) NOT NULL,
                    `shogun_leader_id` INT NOT NULL,
                    `shogun_leader_name` VARCHAR(100) NOT NULL,
                    `achieved_at` INT NOT NULL,
                    `total_contributions_json` LONGTEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Récupérer le chef de l'alliance
            $stmtA = $this->db->prepare("
                SELECT a.id, a.name, a.tag, a.leader_id, u.username as leader_name
                FROM alliances a
                JOIN users u ON a.leader_id = u.id
                WHERE a.id = ?
            ");
            $stmtA->execute([$allianceId]);
            $alliance = $stmtA->fetch();

            if ($alliance) {
                $now = time();
                $stmtWin = $this->db->prepare("
                    INSERT INTO pantheon_eras (
                        era_name, winning_alliance_id, winning_alliance_name, winning_alliance_tag,
                        shogun_leader_id, shogun_leader_name, achieved_at
                    ) VALUES (
                        'Ère de l\'Harmonie Impériale (Tenka Fubu)', ?, ?, ?, ?, ?, ?
                    )
                ");
                $stmtWin->execute([
                    $allianceId, $alliance['name'], $alliance['tag'],
                    $alliance['leader_id'], $alliance['leader_name'], $now
                ]);
            }
        } catch (Throwable $e) {
            error_log("Victory trigger error: " . $e->getMessage());
        }
    }

    /**
     * Résolution des dégâts de siège infligés par des bombardements rivaux
     */
    public function applySiegeDamage(int $attackerUserId, ?int $attackerAllianceId, int $rawDamage): array {
        if (!$this->db) return ['success' => false, 'damage' => 0];
        $dojo = $this->getDojoState();
        if ($dojo['status'] === 'unclaimed') {
            return ['success' => false, 'error' => 'Aucun Dōjō n\'est érigé sur la tuile [0 : 0].'];
        }

        $targetAllianceId = !empty($dojo['controlling_alliance_id']) ? (int)$dojo['controlling_alliance_id'] : null;
        if ($attackerAllianceId && $targetAllianceId && $attackerAllianceId === $targetAllianceId) {
            return ['success' => false, 'error' => 'Vous ne pouvez pas attaquer le sanctuaire de votre propre alliance !'];
        }

        // Réduction des dégâts grâce aux Tuiles Onigawara posées
        $currentLvl = (int)$dojo['current_level'];
        $damageReduction = 0.0;
        if ($currentLvl >= 8) {
            $damageReduction = 0.50; // -50% dégâts grâce aux Onigawara
        } elseif ($currentLvl >= 6) {
            $damageReduction = 0.25; // -25% dégâts grâce aux Hongawara
        }

        $netDamage = (int)round($rawDamage * (1.0 - $damageReduction));
        $oldHp = (int)$dojo['health_points'];
        $newHp = max(0, $oldHp - $netDamage);
        $wasRegressed = false;
        $levelBefore = $currentLvl;
        $levelAfter = $currentLvl;
        $now = time();

        if ($newHp <= 0) {
            // Effondrement / Brèche critique
            $wasRegressed = true;
            if ($dojo['construction_finishes_at'] !== null) {
                // Annulation de la construction en cours, perte des ressources du niveau
                $levelAfter = $currentLvl;
                $newHp = (int)($dojo['max_health_points'] * 0.5); // Rétabli à 50% du niveau actuel
                $stmtCollapse = $this->db->prepare("
                    UPDATE imperial_dojo 
                    SET construction_started_at = NULL,
                        construction_finishes_at = NULL,
                        status = 'under_construction',
                        health_points = ?,
                        stock_wood = 0, stock_granite = 0, stock_metal = 0, stock_rice = 0,
                        stock_beams = 0, stock_tiles = 0, stock_oni_tiles = 0, stock_gold = 0,
                        updated_at = ?
                    WHERE id = ?
                ");
                $stmtCollapse->execute([$newHp, $now, (int)$dojo['id']]);
                $logTxt = "EFFONDREMENT DU CHANTIER ! Une pluie de bombes ennemies a détruit les travaux du palier en cours. Tous les matériaux accumulés sont perdus dans les flammes !";
            } else {
                // Régression d'un palier entier
                $levelAfter = max(0, $currentLvl - 1);
                $newMaxHp = $levelAfter > 0 ? self::LEVEL_CONFIG[$levelAfter]['max_hp'] : 5000000;
                $newHp = (int)($newMaxHp * 0.5);
                $newTarget = max(1, $levelAfter + 1);

                $stmtRegress = $this->db->prepare("
                    UPDATE imperial_dojo 
                    SET current_level = ?,
                        target_level = ?,
                        health_points = ?,
                        max_health_points = ?,
                        updated_at = ?
                    WHERE id = ?
                ");
                $stmtRegress->execute([$levelAfter, $newTarget, $newHp, $newMaxHp, $now, (int)$dojo['id']]);
                $logTxt = "RÉTROGRADATION DU DŌJŌ ! Les fondations ont cédé sous le bombardement. Le sanctuaire retombe au Niveau {$levelAfter} !";
            }
        } else {
            $this->db->prepare("UPDATE imperial_dojo SET health_points = ?, updated_at = ? WHERE id = ?")
                ->execute([$newHp, $now, (int)$dojo['id']]);
            $logTxt = "Brèche de siège : {$netDamage} points de structure détruits sur le Dōjō Impérial.";
        }

        // Journal de siège
        $stmtLog = $this->db->prepare("
            INSERT INTO imperial_dojo_siege_logs (
                dojo_id, attacker_user_id, attacker_alliance_id, target_alliance_id,
                damage_dealt, hp_before, hp_after, level_before, level_after,
                was_regressed, log_text, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtLog->execute([
            (int)$dojo['id'], $attackerUserId, $attackerAllianceId, $targetAllianceId,
            $netDamage, $oldHp, $newHp, $levelBefore, $levelAfter,
            $wasRegressed ? 1 : 0, $logTxt, $now
        ]);

        return [
            'success'       => true,
            'net_damage'    => $netDamage,
            'old_hp'        => $oldHp,
            'new_hp'        => $newHp,
            'was_regressed' => $wasRegressed,
            'log'           => $logTxt
        ];
    }

    /**
     * Récupère la garnison conjointe stationnée sur [0 : 0]
     */
    public function getGarrison(): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("
            SELECT g.*, u.username
            FROM imperial_dojo_garrison g
            JOIN users u ON g.user_id = u.id
            ORDER BY g.amount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère les 10 meilleurs contributeurs de l'alliance bâtisseuse
     */
    public function getTopContributors(int $allianceId): array {
        if (!$this->db || $allianceId <= 0) return [];
        $stmt = $this->db->prepare("
            SELECT c.user_id, u.username, u.faction,
                   COUNT(c.id) as donations_count,
                   SUM(CASE WHEN c.resource_type = 'tiles' THEN c.amount ELSE 0 END) as total_tiles,
                   SUM(CASE WHEN c.resource_type = 'oni_tiles' THEN c.amount ELSE 0 END) as total_oni_tiles,
                   SUM(CASE WHEN c.resource_type = 'beams' THEN c.amount ELSE 0 END) as total_beams,
                   SUM(CASE WHEN c.resource_type = 'gold' THEN c.amount ELSE 0 END) as total_gold
            FROM imperial_dojo_contributions c
            JOIN users u ON c.user_id = u.id
            WHERE c.alliance_id = ?
            GROUP BY c.user_id, u.username, u.faction
            ORDER BY total_tiles DESC, total_beams DESC, donations_count DESC
            LIMIT 10
        ");
        $stmt->execute([$allianceId]);
        return $stmt->fetchAll();
    }
}
