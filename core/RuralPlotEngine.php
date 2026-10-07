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
    }

    /**
     * Formule de coût exponentielle/lissée pour la montée au niveau supérieur (Niveaux 1 à 100)
     * Formule : Base × (1 + 0.22 × (Niveau - 1)^1.45) × 1.12^min(Niveau - 1, 40)
     */
    public static function calculateUpgradeCost(string $structureType, int $currentLevel): array {
        $def = self::STRUCTURES[$structureType] ?? self::STRUCTURES['foret'];
        $base = $def['base_cost'];

        $lvl = max(1, $currentLevel);
        $polyFactor = 1.0 + (0.22 * pow($lvl - 1, 1.45));
        $expFactor = pow(1.12, min($lvl - 1, 40));
        $multiplier = $polyFactor * $expFactor;

        return [
            'metal'     => (int)round($base['metal'] * $multiplier),
            'crystal'   => (int)round($base['crystal'] * $multiplier),
            'deuterium' => (int)round($base['deuterium'] * $multiplier),
            'clay'      => (int)round(($base['clay'] ?? 30) * $multiplier),
        ];
    }

    /**
     * Formule de durée de chantier (en secondes)
     */
    public static function calculateUpgradeDuration(int $currentLevel, int $hqLevel = 1): int {
        $lvl = max(1, $currentLevel);
        $baseSeconds = 25.0 * pow(1.06, min($lvl - 1, 50)) * sqrt($lvl);
        $hqDiscount = 1.0 + (max(1, $hqLevel) * 0.05);
        $speed = defined('SPEED_FACTOR') ? SPEED_FACTOR : 1;

        return max(15, (int)round(($baseSeconds / $hqDiscount) / max(1, $speed)));
    }

    /**
     * Récupère les 9 parcelles enrichies avec leurs calculs pour l'affichage de la carte
     */
    public function getEnrichedPlots(int $planetId, array $planet, int $hqLevel = 1): array {
        $rawPlots = $this->generator->getPlanetPlots($planetId);
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
                'meta'            => $meta,
                'name'            => $meta['name'],
                'jp_name'         => $meta['jp_name'],
                'desc'            => $meta['desc'],
                'icon'            => $meta['icon'],
                'color'           => $meta['color'],
                'color_class'     => $meta['color_class'],
                'tile_img'        => $meta['tile_img'],
                'bg_image'        => $meta['bg_image'],
                'worker_role'     => $meta['worker_role'],
                'is_max'          => $isMax,
                'next_level'      => $nextLvl,
                'cost'            => $cost,
                'duration'        => $duration,
                'can_afford'      => $canAfford,
                'prod_label'      => $prodLabel,
                'next_prod_label' => $nextProdLabel,
                'progress_pct'    => $progressPct,
            ]);
        }

        return $enriched;
    }

    /**
     * Améliore une parcelle rurale (transaction SQL avec contrôle des stocks et du plafond)
     */
    public function upgradePlot(int $planetId, string $structureType): array {
        if (!$this->db) {
            return ['success' => false, 'error' => 'Connexion base de données indisponible.'];
        }
        if (!isset(self::STRUCTURES[$structureType])) {
            return ['success' => false, 'error' => 'Type de structure inconnu.'];
        }

        try {
            $this->db->beginTransaction();

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = ($driver === 'sqlite') ? '' : ' FOR UPDATE';

            // 1. Verrouiller la parcelle
            $stmtPlot = $this->db->prepare("SELECT * FROM planet_rural_plots WHERE planet_id = ? AND structure_type = ?" . $lockSql);
            $stmtPlot->execute([$planetId, $structureType]);
            $plot = $stmtPlot->fetch(PDO::FETCH_ASSOC);

            if (!$plot) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Parcelle introuvable pour ce fief.'];
            }

            $currentLevel = (int)$plot['level'];
            $maxLevel = (int)$plot['max_level'];

            if ($currentLevel >= $maxLevel) {
                $this->db->rollBack();
                return ['success' => false, 'error' => "Cette structure a atteint son potentiel maximum (Niveau {$maxLevel})."];
            }

            $nextLevel = $currentLevel + 1;
            $cost = self::calculateUpgradeCost($structureType, $currentLevel);

            // 2. Verrouiller la planète pour contrôler les ressources
            $stmtPlanet = $this->db->prepare("SELECT metal, crystal, deuterium, population, population_max FROM planets WHERE id = ?" . $lockSql);
            $stmtPlanet->execute([$planetId]);
            $planet = $stmtPlanet->fetch(PDO::FETCH_ASSOC);

            if (!$planet) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Planète introuvable.'];
            }

            if ($planet['metal'] < $cost['metal'] || $planet['crystal'] < $cost['crystal'] || $planet['deuterium'] < $cost['deuterium']) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Ressources insuffisantes pour cette élévation.'];
            }

            // 3. Déduire les ressources
            $stmtDeduct = $this->db->prepare("
                UPDATE planets
                SET metal = metal - ?,
                    crystal = crystal - ?,
                    deuterium = deuterium - ?
                WHERE id = ?
            ");
            $stmtDeduct->execute([$cost['metal'], $cost['crystal'], $cost['deuterium'], $planetId]);

            // 4. Mettre à jour la parcelle
            $newWorkers = max(2, (int)round(2 + ($nextLevel * 1.5)));
            $newProd = VillageGeneratorService::calculateHourlyProduction($structureType, $nextLevel);

            $stmtUpdatePlot = $this->db->prepare("
                UPDATE planet_rural_plots
                SET level = ?,
                    workers_assigned = ?,
                    prod_hourly = ?
                WHERE planet_id = ? AND structure_type = ?
            ");
            $stmtUpdatePlot->execute([$nextLevel, $newWorkers, $newProd, $planetId, $structureType]);

            // 5. Effet spécifique de la structure
            if ($structureType === 'village') {
                // Mise à jour directe de la capacité d'accueil des villageois : 75 + (nextLevel * 25)
                $newCapacity = 75 + ($nextLevel * 25);
                $minSql = ($driver === 'sqlite') ? "MIN(population + 5, ?)" : "LEAST(population + 5, ?)";
                $this->db->prepare("UPDATE planets SET population_max = ?, population = {$minSql} WHERE id = ?")
                    ->execute([$newCapacity, $newCapacity, $planetId]);
            } elseif ($structureType === 'tenshu') {
                // Synchroniser avec planet_buildings si hq présent
                try {
                    $this->db->prepare("UPDATE planet_buildings SET level = ? WHERE planet_id = ? AND building_type = 'hq'")
                        ->execute([$nextLevel, $planetId]);
                } catch (Exception $e) {}
            }

            $this->db->commit();

            $meta = self::STRUCTURES[$structureType];
            return [
                'success'     => true,
                'message'     => "« {$meta['name']} » élevé avec succès au Niveau {$nextLevel} / {$maxLevel} !",
                'new_level'   => $nextLevel,
                'max_level'   => $maxLevel,
                'new_prod'    => $newProd,
                'new_workers' => $newWorkers
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Erreur technique lors de l\'élévation : ' . $e->getMessage()];
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
