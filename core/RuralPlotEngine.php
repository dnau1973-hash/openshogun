<?php
/**
 * Moteur Métier & Gestion des 9 Parcelles Rurales (OpenShogun)
 * Progression verticale profonde (Niveaux 20 à 100), coûts lissés, rendements et élévation.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/VillageGeneratorService.php';

class RuralPlotEngine {
    private ?PDO $db;
    private VillageGeneratorService $generator;

    // Métadonnées d'affichage et thématiques des 9 structures
    public const STRUCTURES = [
        'tenshu' => [
            'type'         => 'tenshu',
            'name'         => 'Donjon Tenshu',
            'jp_name'      => '天守 (Tenshu)',
            'category'     => 'admin',
            'icon'         => 'fa-solid fa-chess-rook',
            'color'        => '#ef4444',
            'color_class'  => 'danger',
            'res_name'     => 'Autorité & Commandement',
            'worker_role'  => 'Garnison & Intendants',
            'desc'         => 'Donjon seigneurial fortifié dominant le domaine. Accélère l\'ensemble des chantiers et affirme l\'autorité féodale.',
            'tile_img'     => '/public/assets/tiles/tile_hq.png',
            'bg_image'     => '/public/assets/buildings/hq.jpg',
            'base_cost'    => ['metal' => 120, 'crystal' => 90, 'deuterium' => 60, 'clay' => 80],
        ],
        'foret' => [
            'type'         => 'foret',
            'name'         => 'Forêt de Cèdres',
            'jp_name'      => '杉林 (Sugi-bayashi)',
            'category'     => 'wood',
            'icon'         => 'fa-solid fa-tree',
            'color'        => '#22c55e',
            'color_class'  => 'success',
            'res_name'     => 'Bois de Cèdre',
            'worker_role'  => 'Bûcherons sylvicoles',
            'desc'         => 'Exploitation forestière et camp de bûcheronnage abattant les nobles cèdres pour les charpentes et défenses.',
            'tile_img'     => '/public/assets/tiles/tile_bucheron.png',
            'bg_image'     => '/public/assets/resources/terroir_wood.jpg',
            'base_cost'    => ['metal' => 45, 'crystal' => 60, 'deuterium' => 20, 'clay' => 30],
        ],
        'carriere' => [
            'type'         => 'carriere',
            'name'         => 'Carrière de Granit',
            'jp_name'      => '石切場 (Ishikiri-ba)',
            'category'     => 'stone',
            'icon'         => 'fa-solid fa-mountain',
            'color'        => '#94a3b8',
            'color_class'  => 'secondary',
            'res_name'     => 'Pierre de Taille',
            'worker_role'  => 'Tailleurs de pierre',
            'desc'         => 'Extraction à flanc de falaise de blocs de granit pour fortifier les remparts et ériger les fondations cyclopéennes.',
            'tile_img'     => '/public/assets/tiles/tile_carriere.png',
            'bg_image'     => '/public/assets/resources/terroir_stone.jpg',
            'base_cost'    => ['metal' => 60, 'crystal' => 40, 'deuterium' => 20, 'clay' => 40],
        ],
        'culture_the' => [
            'type'         => 'culture_the',
            'name'         => 'Coteaux de Théiers',
            'jp_name'      => '茶畑 (Chabatake)',
            'category'     => 'tea',
            'icon'         => 'fa-solid fa-leaf',
            'color'        => '#10b981',
            'color_class'  => 'teal',
            'res_name'     => 'Feuilles de Thé & Matcha',
            'worker_role'  => 'Cueilleurs de thé',
            'desc'         => 'Terrasses étagées de théiers taillés alimentant la maison de thé, stimulant l\'énergie et la concentration du domaine.',
            'tile_img'     => '/public/assets/tiles/tile_ferme.png',
            'bg_image'     => '/public/assets/resources/terroir_tea.jpg',
            'base_cost'    => ['metal' => 50, 'crystal' => 35, 'deuterium' => 45, 'clay' => 25],
        ],
        'village' => [
            'type'         => 'village',
            'name'         => 'Village & Habitations',
            'jp_name'      => '村・民家 (Mura Minka)',
            'category'     => 'housing',
            'icon'         => 'fa-solid fa-people-roof',
            'color'        => '#6366f1',
            'color_class'  => 'indigo',
            'res_name'     => 'Capacité d\'Habitations',
            'worker_role'  => 'Artisans & Familles',
            'desc'         => 'Hameau central traditionnel composé de maisons minka aux toits de chaume. Détermine la capacité d\'accueil maximale de villageois.',
            'tile_img'     => '/public/assets/tiles/tile_storage.png',
            'bg_image'     => '/public/assets/resources/terroir_village.jpg',
            'base_cost'    => ['metal' => 70, 'crystal' => 50, 'deuterium' => 30, 'clay' => 60],
        ],
        'fosse_argile' => [
            'type'         => 'fosse_argile',
            'name'         => 'Fosse d\'Argile & Céramique',
            'jp_name'      => '粘土採掘場 (Nendo Saikutsu-ba)',
            'category'     => 'clay',
            'icon'         => 'fa-solid fa-cubes-stacked',
            'color'        => '#ea580c',
            'color_class'  => 'orange',
            'res_name'     => 'Argile & Tuiles',
            'worker_role'  => 'Potiers & Briquetiers',
            'desc'         => 'Gisements alluviaux en bordure de rivière et fours noborigama pour modeler les tuiles étanches et la céramique.',
            'tile_img'     => '/public/assets/tiles/tile_argiliere.png',
            'bg_image'     => '/public/assets/resources/terroir_clay.jpg',
            'base_cost'    => ['metal' => 45, 'crystal' => 50, 'deuterium' => 30, 'clay' => 20],
        ],
        'riziere' => [
            'type'         => 'riziere',
            'name'         => 'Rizière Inondée',
            'jp_name'      => '水田 (Suiden)',
            'category'     => 'rice',
            'icon'         => 'fa-solid fa-wheat-awn',
            'color'        => '#eab308',
            'color_class'  => 'warning',
            'res_name'     => 'Riz Impérial',
            'worker_role'  => 'Repiqueurs de riz',
            'desc'         => 'Bassins inondés miroitants produisant le riz nourricier, base des vivres, de la farine et du saké féodal.',
            'tile_img'     => '/public/assets/tiles/tile_riziere.png',
            'bg_image'     => '/public/assets/resources/terroir_rice.jpg',
            'base_cost'    => ['metal' => 35, 'crystal' => 45, 'deuterium' => 60, 'clay' => 35],
        ],
        'champ_soja' => [
            'type'         => 'champ_soja',
            'name'         => 'Champ de Soja',
            'jp_name'      => '大豆畑 (Daizu-batake)',
            'category'     => 'soybean',
            'icon'         => 'fa-solid fa-seedling',
            'color'        => '#84cc16',
            'color_class'  => 'lime',
            'res_name'     => 'Fèves de Soja & Miso',
            'worker_role'  => 'Agriculteurs',
            'desc'         => 'Sillons agricoles rectangulaires fertiles fournissant le soja essentiel aux sauces, tofu et miso nutritif.',
            'tile_img'     => '/public/assets/tiles/tile_ferme.png',
            'bg_image'     => '/public/assets/resources/terroir_soybean.jpg',
            'base_cost'    => ['metal' => 40, 'crystal' => 30, 'deuterium' => 40, 'clay' => 25],
        ],
        'sanctuaire_shinto' => [
            'type'         => 'sanctuaire_shinto',
            'name'         => 'Sanctuaire Shintō',
            'jp_name'      => '神社 (Jinja)',
            'category'     => 'shrine',
            'icon'         => 'fa-solid fa-torii-gate',
            'color'        => '#b91c1c',
            'color_class'  => 'danger',
            'res_name'     => 'Sérénité & Bénédictions',
            'worker_role'  => 'Prêtres Kannushi & Miko',
            'desc'         => 'Bosquet sacré orné de lanternes et d\'un grand torii vermillon. Rayonne la sérénité divine et soutient le moral des villageois.',
            'tile_img'     => '/public/assets/tiles/tile_temple.png',
            'bg_image'     => '/public/assets/resources/terroir_shrine.jpg',
            'base_cost'    => ['metal' => 80, 'crystal' => 70, 'deuterium' => 50, 'clay' => 50],
        ],
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->generator = new VillageGeneratorService($this->db);
        $this->ensureQueueSupportsRuralPlots();
    }

    /**
     * S'assure que construction_queue supporte la catégorie 'rural_plot' (MySQL)
     */
    public function ensureQueueSupportsRuralPlots(): void {
        if (!$this->db) return;
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver !== 'sqlite') {
                $this->db->exec("ALTER TABLE `construction_queue` MODIFY COLUMN `build_category` VARCHAR(32) NOT NULL");
            }
        } catch (Exception $e) {
            // Silencieux si déjà migré ou droits restreints
        }
    }

    /**
     * Formule de coût exponentielle/lissée pour la montée au niveau supérieur (Niveaux 1 à 100)
     * Formule : Base × (1 + 0.22 × (Niveau - 1)^1.45) × 1.12^min(Niveau - 1, 40)
     */
    public static function calculateUpgradeCost(string $structureType, int $currentLevel): array {
        require_once __DIR__ . '/GameConfig.php';
        $costCoeff = max(0.05, min(10.0, (float)GameConfig::get('building_cost_coeff', 1.0)));
        $costGrowth = max(0.5, min(2.0, (float)GameConfig::get('building_cost_growth', 1.0)));

        $def = self::STRUCTURES[$structureType] ?? self::STRUCTURES['foret'];
        $base = $def['base_cost'];

        $lvl = max(1, $currentLevel);
        $polyFactor = 1.0 + (0.22 * pow($lvl - 1, 1.45));
        $expFactor = pow(1.12 * $costGrowth, min($lvl - 1, 40));
        $multiplier = $polyFactor * $expFactor * $costCoeff;

        return [
            'metal'     => (int)round($base['metal'] * $multiplier),
            'crystal'   => (int)round($base['crystal'] * $multiplier),
            'deuterium' => (int)round($base['deuterium'] * $multiplier),
            'clay'      => (int)round(($base['clay'] ?? 30) * $multiplier),
        ];
    }

    /**
     * Formule de durée de chantier (en secondes)
     * Échelonnage progressif :
     * - Paliers bas (1 à 10) : ~3 min à ~20 min
     * - Paliers intermédiaires (15 à 40) : ~45 min à ~13 heures
     * - Hauts paliers (50 à 100) : ~1.5 jours à plus de 15 jours
     */
    public static function calculateUpgradeDuration(int $currentLevel, int $hqLevel = 1): int {
        require_once __DIR__ . '/GameConfig.php';
        $timeCoeff = max(0.05, min(10.0, (float)GameConfig::get('building_time_coeff', 1.0)));

        $lvl = max(1, $currentLevel);
        $base = 180.0 * (1.0 + 0.16 * pow($lvl - 1, 1.35)) * pow(1.065, min($lvl - 1, 55)) * pow(1.025, max(0, $lvl - 56));
        $hqFactor = 1.0 + (max(1, $hqLevel) * 0.05);
        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));

        return max(30, (int)round((($base / $hqFactor) * $timeCoeff) / $speed));
    }

    /**
     * Récupère les 9 parcelles enrichies avec leurs calculs pour l'affichage de la carte
     */
    public function getEnrichedPlots(int $planetId, array $planet, int $hqLevel = 1): array {
        // 1. Résolution des chantiers arrivés à échéance
        require_once __DIR__ . '/PlanetEngine.php';
        $pe = new PlanetEngine($this->db);
        $pe->processConstructionQueue($planetId);

        $rawPlots = $this->generator->getPlanetPlots($planetId);

        // 2. Récupérer les chantiers ruraux actifs dans la file
        $stmtQueue = $this->db->prepare("
            SELECT * FROM construction_queue 
            WHERE planet_id = ? AND build_category = 'rural_plot'
            ORDER BY finishes_at ASC
        ");
        $stmtQueue->execute([$planetId]);
        $queueRows = $stmtQueue->fetchAll(PDO::FETCH_ASSOC);
        $queueMap = [];
        $now = time();
        foreach ($queueRows as $q) {
            $queueMap[$q['target_id']] = $q;
        }

        $enriched = [];

        foreach ($rawPlots as $p) {
            $type = $p['structure_type'];
            $meta = self::STRUCTURES[$type] ?? self::STRUCTURES['foret'];
            $lvl = (int)$p['level'];
            $maxLvl = (int)$p['max_level'];
            $isMax = ($lvl >= $maxLvl);
            $nextLvl = min($maxLvl, $lvl + 1);

            $cost = self::calculateUpgradeCost($type, $lvl);
            $duration = self::calculateUpgradeDuration($lvl, $hqLevel);

            $canAfford = (
                ($planet['metal'] ?? 0) >= $cost['metal'] &&
                ($planet['crystal'] ?? 0) >= $cost['crystal'] &&
                ($planet['deuterium'] ?? 0) >= $cost['deuterium']
            );

            // État de chantier en cours
            $qItem = $queueMap[$type] ?? null;
            $isUpgrading = ($qItem !== null);
            $targetLevel = $isUpgrading ? (int)$qItem['target_level'] : $nextLvl;
            $startedAt = $isUpgrading ? (int)$qItem['started_at'] : null;
            $finishesAt = $isUpgrading ? (int)$qItem['finishes_at'] : null;
            $timeRemaining = $isUpgrading ? max(0, $finishesAt - $now) : 0;
            $totalDuration = $isUpgrading ? max(1, $finishesAt - $startedAt) : $duration;
            $elapsed = $isUpgrading ? max(0, $now - $startedAt) : 0;
            $queueProgressPct = $isUpgrading ? min(100, max(0, (int)round(($elapsed / $totalDuration) * 100))) : 0;

            // Rendements actuels et futurs
            $currentProd = (float)$p['prod_hourly'];
            $nextProd = VillageGeneratorService::calculateHourlyProduction($type, $nextLvl);

            if ($type === 'village') {
                $prodLabel = number_format($currentProd) . ' logements';
                $nextProdLabel = number_format($nextProd) . ' logements';
            } elseif ($type === 'sanctuaire_shinto') {
                $prodLabel = '+' . number_format($currentProd) . ' sérénité';
                $nextProdLabel = '+' . number_format($nextProd) . ' sérénité';
            } elseif ($type === 'tenshu') {
                $prodLabel = 'Autorité Niv. ' . $lvl;
                $nextProdLabel = 'Autorité Niv. ' . $nextLvl;
            } else {
                $prodLabel = '+' . number_format($currentProd) . ' / h';
                $nextProdLabel = '+' . number_format($nextProd) . ' / h';
            }

            $progressPct = ($maxLvl > 0) ? min(100, (int)round(($lvl / $maxLvl) * 100)) : 100;

            $enriched[$type] = array_merge($p, [
                'meta'               => $meta,
                'name'               => $meta['name'],
                'jp_name'            => $meta['jp_name'],
                'desc'               => $meta['desc'],
                'icon'               => $meta['icon'],
                'color'              => $meta['color'],
                'color_class'        => $meta['color_class'],
                'tile_img'           => $meta['tile_img'],
                'bg_image'           => $meta['bg_image'],
                'worker_role'        => $meta['worker_role'],
                'is_max'             => $isMax,
                'next_level'         => $nextLvl,
                'cost'               => $cost,
                'duration'           => $duration,
                'can_afford'         => $canAfford,
                'prod_label'         => $prodLabel,
                'next_prod_label'    => $nextProdLabel,
                'progress_pct'       => $progressPct,
                // Gestion du chantier actif
                'is_upgrading'       => $isUpgrading,
                'queue_id'           => $isUpgrading ? (int)$qItem['id'] : null,
                'target_level'       => $targetLevel,
                'started_at'         => $startedAt,
                'finishes_at'        => $finishesAt,
                'time_remaining'     => $timeRemaining,
                'queue_progress_pct' => $queueProgressPct,
            ]);
        }

        return $enriched;
    }

    /**
     * Lance le chantier d'élévation d'une parcelle rurale (File non-instantanée, avec persistance dans construction_queue)
     */
    public function upgradePlot(int $planetId, string $structureType): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Connexion base de données indisponible.'];
        }
        if (!isset(self::STRUCTURES[$structureType])) {
            return ['success' => false, 'error' => 'Type de structure inconnu.'];
        }
        if ($structureType === 'tenshu') {
            return [
                'success' => false,
                'error' => "Le Tenshu est le donjon de la cité castrale. Rendez-vous dans la Cité pour développer vos infrastructures."
            ];
        }

        try {
            // Instancier les services annexes AVANT la transaction pour éviter tout DDL implicite
            require_once __DIR__ . '/ImperialSealEngine.php';
            require_once __DIR__ . '/PlanetEngine.php';
            $sealEngine = new ImperialSealEngine($this->db);
            $pe = new PlanetEngine($this->db);

            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
            }

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'sqlite') ? '' : ' FOR UPDATE';

            // 1. Verrouiller la parcelle
            $stmtPlot = $this->db->prepare("SELECT * FROM planet_rural_plots WHERE planet_id = ? AND structure_type = ?" . $lockSql);
            $stmtPlot->execute([$planetId, $structureType]);
            $plot = $stmtPlot->fetch(PDO::FETCH_ASSOC);

            if (!$plot) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'error' => 'Parcelle introuvable pour ce fief.'];
            }

            $currentLevel = (int)$plot['level'];
            $maxLevel = (int)$plot['max_level'];

            if ($currentLevel >= $maxLevel) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'error' => "Cette structure a atteint son potentiel maximum (Niveau {$maxLevel})."];
            }

            // 2. Contrôle de la file de construction : récupérer tous les chantiers du fief
            $stmtQCheck = $this->db->prepare("
                SELECT * FROM construction_queue 
                WHERE planet_id = ?
                ORDER BY finishes_at ASC
            ");
            $stmtQCheck->execute([$planetId]);
            $existingQueue = $stmtQCheck->fetchAll(PDO::FETCH_ASSOC);

            $ruralInQueue = 0;
            $buildingsInQueue = 0;
            $ruralFinishes = [];

            foreach ($existingQueue as $qItem) {
                if ($qItem['build_category'] === 'rural_plot' && $qItem['target_id'] === $structureType) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    return ['success' => false, 'error' => "Un chantier est déjà en cours pour cette structure."];
                }
                if (in_array($qItem['build_category'], ['rural_plot', 'field'])) {
                    $ruralInQueue++;
                    $ruralFinishes[] = (int)$qItem['finishes_at'];
                } elseif ($qItem['build_category'] === 'building') {
                    $buildingsInQueue++;
                }
            }

            // 3. Verrouiller la planète pour contrôler les ressources, faction et Sceau
            try {
                $stmtPlanet = $this->db->prepare("
                    SELECT p.user_id, p.metal, p.crystal, p.deuterium, p.population, u.faction 
                    FROM planets p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.id = ?" . $lockSql
                );
                $stmtPlanet->execute([$planetId]);
                $planet = $stmtPlanet->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Secours si u.faction n'est pas accessible
                $stmtPlanet = $this->db->prepare("SELECT user_id, metal, crystal, deuterium, population FROM planets WHERE id = ?" . $lockSql);
                $stmtPlanet->execute([$planetId]);
                $planet = $stmtPlanet->fetch(PDO::FETCH_ASSOC);
                if ($planet) {
                    $planet['faction'] = 'terran';
                }
            }

            if (!$planet) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'error' => 'Planète introuvable.'];
            }

            $userFaction = $planet['faction'] ?? 'terran';

            // Contrôle de concurrence des chantiers selon la faction et le Sceau Impérial
            $isSealActive = $sealEngine->isSealActive((int)($planet['user_id'] ?? 0));

            if ($userFaction === 'terran') {
                // Clan Oda : Double développement simultané (1 rural + 1 urbain de base, 2+2 avec Sceau Impérial)
                $maxRuralAllowed = $isSealActive ? 2 : 1;
                if ($ruralInQueue >= $maxRuralAllowed) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    $msg = $isSealActive
                        ? "Vos deux créneaux de chantiers ruraux sont déjà occupés (maximum 2 simultanés avec le Sceau Impérial)."
                        : "Un chantier rural est déjà en cours. Décrétez le Sceau Impérial pour mener 2 chantiers ruraux de front !";
                    return ['success' => false, 'error' => $msg];
                }
            } else {
                // Autres clans (Takeda, Tokugawa) : 1 seul chantier total sur tout le domaine (sauf avec Sceau Impérial)
                $maxTotalAllowed = $isSealActive ? 2 : 1;
                if (count($existingQueue) >= $maxTotalAllowed) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    $msg = $isSealActive
                        ? "Vos deux créneaux de chantiers sont déjà occupés (maximum 2 simultanés avec le Sceau Impérial)."
                        : "Un chantier est déjà en cours sur votre fief. Seul le Clan Oda maîtrise le double développement rural et urbain simultané (ou décrétez le Sceau Impérial) !";
                    return ['success' => false, 'error' => $msg];
                }
            }

            $nextLevel = $currentLevel + 1;
            $cost = self::calculateUpgradeCost($structureType, $currentLevel);

            if ($planet['metal'] < $cost['metal'] || $planet['crystal'] < $cost['crystal'] || $planet['deuterium'] < $cost['deuterium']) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return ['success' => false, 'error' => 'Ressources insuffisantes pour cette élévation.'];
            }

            // 4. Déduire les ressources immédiatement
            $stmtDeduct = $this->db->prepare("
                UPDATE planets
                SET metal = metal - ?,
                    crystal = crystal - ?,
                    deuterium = deuterium - ?
                WHERE id = ?
            ");
            $stmtDeduct->execute([$cost['metal'], $cost['crystal'], $cost['deuterium'], $planetId]);

            // 5. Calcul des durées et planification du chantier
            $buildings = $pe->getBuildings($planetId);
            $hqLevel = (int)($buildings['hq'] ?? 1);
            $duration = self::calculateUpgradeDuration($currentLevel, $hqLevel);

            $now = time();
            if ($userFaction === 'terran') {
                if ($ruralInQueue < $maxRuralAllowed) {
                    $startTime = $now;
                } else {
                    $startTime = max($now, empty($ruralFinishes) ? $now : max($ruralFinishes));
                }
            } else {
                if (count($existingQueue) < $maxTotalAllowed) {
                    $startTime = $now;
                } else {
                    $maxFin = max(array_column($existingQueue, 'finishes_at'));
                    $startTime = max($now, (int)$maxFin);
                }
            }
            $finishesAt = $startTime + $duration;

            // 6. Insérer dans la file de construction (le niveau n'est pas incrémenté tout de suite !)
            $stmtInsert = $this->db->prepare("
                INSERT INTO construction_queue (planet_id, build_category, target_id, target_level, started_at, finishes_at)
                VALUES (?, 'rural_plot', ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$planetId, $structureType, $nextLevel, $startTime, $finishesAt]);
            $queueId = (int)$this->db->lastInsertId();

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }

            $meta = self::STRUCTURES[$structureType];
            return [
                'success'      => true,
                'message'      => "Chantier d'élévation lancé pour « {$meta['name']} » vers le Niveau {$nextLevel} !",
                'queue_id'     => $queueId,
                'target_level' => $nextLevel,
                'max_level'    => $maxLevel,
                'started_at'   => $startTime,
                'finishes_at'  => $finishesAt,
                'duration'     => $duration
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Erreur technique lors du lancement du chantier : ' . $e->getMessage()];
        }
    }

    /**
     * Calcule la capacité totale d'accueil des villageois (remplace l'ancienne formule terroir)
     */
    public function getVillageHousingCapacity(int $planetId): array {
        if (!$this->db) return ['total_capacity' => 100, 'level' => 1, 'max_level' => 40];

        $stmt = $this->db->prepare("SELECT level, max_level FROM planet_rural_plots WHERE planet_id = ? AND structure_type = 'village'");
        $stmt->execute([$planetId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $level = $row ? (int)$row['level'] : 1;
        $maxLevel = $row ? (int)$row['max_level'] : 40;
        $capacity = 75 + ($level * 25);

        return [
            'base_capacity'    => 75,
            'per_level_bonus'  => 25,
            'level'            => $level,
            'max_level'        => $maxLevel,
            'total_capacity'   => $capacity
        ];
    }
}
