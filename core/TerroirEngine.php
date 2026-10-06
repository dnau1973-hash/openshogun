<?php
/**
 * TerroirEngine - Moteur de gestion du Terroir Rural Féodal (OpenShogun)
 * Gère les zones interactives, les 5 slots d'action par ressource,
 * les coordonnées spatiales ergonomiques et l'intégration démographique.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/BuildingEngine.php';
require_once __DIR__ . '/PopulationEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class TerroirEngine {
    private ?PDO $db;
    private PlanetEngine $planetEngine;
    private BuildingEngine $buildingEngine;

    // Définition maîtresse des 8 composantes du Terroir Féodal (8 catégories × 5 parcelles = 40 parcelles)
    public const ZONES = [
        'wood' => [
            'type'        => 'wood',
            'name'        => 'Forêt & Bûcheronnage',
            'jp_name'     => '伐採場 (Bassai-ba)',
            'res_name'    => 'Bois de Cèdre',
            'icon'        => 'fa-solid fa-tree',
            'color'       => '#22c55e',
            'color_class' => 'success',
            'badge_text'  => 'Sylviculture',
            'desc'        => 'Abattage et façonnage des nobles cèdres pour les charpentes de donjons, palissades et armes.',
            'bg_image'    => '/public/assets/resources/terroir_wood.jpg',
            'tile_img'    => '/public/assets/tiles/tile_bucheron.png',
            'map_coords'  => ['left' => 21.0, 'top' => 38.0],
            'field_type'  => 'metal_mine',
            'db_slots'    => [1, 2, 3, 4, 5],
            'worker_name' => 'Bûcheron sylvicole',
            'workers_per_lvl' => 2,
            'slot_names'  => [
                1 => ['name' => 'Bûcheron des Grands Cèdres', 'desc' => 'Abattage sélectif des conifères centenaires en lisière haute.'],
                2 => ['name' => 'Exploitation du Versant Nord', 'desc' => 'Parcelle forestière escarpée alimentant les chantiers civils.'],
                3 => ['name' => 'Défrichage du Vallon Sombre', 'desc' => 'Coupe de bois dur et conifères résistants pour les palissades.'],
                4 => ['name' => 'Sylve Royale des Pins Noirs', 'desc' => 'Réserves sylvicoles réservées aux poutres maîtresses des Tenshu.'],
                5 => ['name' => 'Scierie Fluviale & Flottage', 'desc' => 'Débitage de troncs et transport hydraulique le long du ruisseau.']
            ]
        ],
        'stone' => [
            'type'        => 'stone',
            'name'        => 'Montagne & Carrière de Pierre',
            'jp_name'     => '石切場 (Ishikiri-ba)',
            'res_name'    => 'Pierre de Taille',
            'icon'        => 'fa-solid fa-mountain',
            'color'       => '#94a3b8',
            'color_class' => 'secondary',
            'badge_text'  => 'Extraction Minérale',
            'desc'        => 'Extraction et taille des blocs cyclopéens de granit volcanique pour murailles et remparts.',
            'bg_image'    => '/public/assets/resources/terroir_stone.jpg',
            'tile_img'    => '/public/assets/tiles/tile_carriere.png',
            'map_coords'  => ['left' => 58.0, 'top' => 25.0],
            'field_type'  => 'crystal_mine',
            'db_slots'    => [6, 7, 8, 9, 10],
            'worker_name' => 'Carrier de granit',
            'workers_per_lvl' => 2,
            'slot_names'  => [
                1 => ['name' => 'Carrière Haute de Nozura-zumi', 'desc' => 'Taille de moellons bruts pour l\'empilement naturel des remparts.'],
                2 => ['name' => 'Plateforme des Échafaudages', 'desc' => 'Extraction au pic et coins de fer sous les falaises vertigineuses.'],
                3 => ['name' => 'Gisement de Granit Oriental', 'desc' => 'Veine de roche dense et polie idéale pour les tours d\'angle.'],
                4 => ['name' => 'Fosse des Maçons Bâtisseurs', 'desc' => 'Façonnage d\'arêtes vives et pierres de taille millimétrées.'],
                5 => ['name' => 'Atelier de Concassage & Pavage', 'desc' => 'Préparation de ballast pour les chemins de ronde et fortifications.']
            ]
        ],
        'clay' => [
            'type'        => 'clay',
            'name'        => 'Gisement Alluvial d\'Argile & Poterie',
            'jp_name'     => '粘土鉱床 (Nendo Kōshō)',
            'res_name'    => 'Argile Fine & Céramique',
            'icon'        => 'fa-solid fa-jar',
            'color'       => '#f59e0b',
            'color_class' => 'warning',
            'badge_text'  => 'Artisanat Céramique',
            'desc'        => 'Excavation à ciel ouvert d\'argile pure, modelage et cuisson des tuiles incombustibles et récipients.',
            'bg_image'    => '/public/assets/resources/terroir_clay.jpg',
            'tile_img'    => '/public/assets/tiles/tile_stonemason.png',
            'map_coords'  => ['left' => 81.0, 'top' => 51.0],
            'field_type'  => null,
            'db_slots'    => [],
            'worker_name' => 'Potier & Extracteur',
            'workers_per_lvl' => 2,
            'slot_names'  => [
                1 => ['name' => 'Fosse d\'Extraction Alluviale', 'desc' => 'Excavation de sédiments plastiques à l\'aide de grues de bois.'],
                2 => ['name' => 'Puits de Glaise Profonde', 'desc' => 'Couches d\'argile pure sans gravier pour tuiles et jarres.'],
                3 => ['name' => 'Voie Ferrée & Berlines de Minerai', 'desc' => 'Chariots sur rails pour acheminer la motte vers le malaxage.'],
                4 => ['name' => 'Bassins de Décantation & Lavage', 'desc' => 'Clarification de la barbotine par sédimentation continue.'],
                5 => ['name' => 'Four Noborigama à Tuiles Kawara', 'desc' => 'Four à chambres ascendantes pour cuire les solides tuiles.' ]
            ]
        ],
        'rice' => [
            'type'        => 'rice',
            'name'        => 'Terrasses Rizicoles Inondées',
            'jp_name'     => '水田 (Suiden)',
            'res_name'    => 'Riz Impérial (Koku)',
            'icon'        => 'fa-solid fa-wheat-awn',
            'color'       => '#eab308',
            'color_class' => 'warning',
            'badge_text'  => 'Alimentation Vitale',
            'desc'        => 'Bassins inondés étagés le long du méandre fluvial. Le Koku de riz est l\'énergie motrice du fief.',
            'bg_image'    => '/public/assets/resources/terroir_rice.jpg',
            'tile_img'    => '/public/assets/tiles/tile_riziere.png',
            'map_coords'  => ['left' => 52.0, 'top' => 62.0],
            'field_type'  => 'deuterium_synth',
            'db_slots'    => [11, 12, 13, 14, 19],
            'worker_name' => 'Riziculteur',
            'workers_per_lvl' => 2,
            'slot_names'  => [
                1 => ['name' => 'Bassin des Eaux Célestes', 'desc' => 'Retenue d\'eau haute régulant le niveau des canaux d\'inondation.'],
                2 => ['name' => 'Terrasses en Étages du Lotus', 'desc' => 'Gradins rizicoles épousant le relief et baignés de soleil.'],
                3 => ['name' => 'Canaux d\'Irrigation Centraux', 'desc' => 'Vannes de bois redistribuant l\'eau vivifiante aux jeunes plants.'],
                4 => ['name' => 'Paddies Inondés du Delta', 'desc' => 'Rizières alluviales profondes travaillées avec buffles et charrues.'],
                5 => ['name' => 'Terrasses Fertiles du Méandre', 'desc' => 'Riches terres bordières garantissant d\'abondantes récoltes annuelles.']
            ]
        ],
        'tea' => [
            'type'        => 'tea',
            'name'        => 'Coteaux de Thé & Pavillons',
            'jp_name'     => '茶畑 (Chabatake)',
            'res_name'    => 'Feuilles de Thé & Matcha',
            'icon'        => 'fa-solid fa-leaf',
            'color'       => '#14b8a6',
            'color_class' => 'teal',
            'badge_text'  => 'Confort & Sérénité',
            'desc'        => 'Buissons de thé taillés en vagues géométriques. Fournit le thé de cérémonie qui calme les esprits.',
            'bg_image'    => '/public/assets/resources/terroir_tea.jpg',
            'tile_img'    => '/public/assets/tiles/tile_teahouse.png',
            'map_coords'  => ['left' => 20.0, 'top' => 54.0],
            'field_type'  => null,
            'db_slots'    => [],
            'worker_name' => 'Cueilleuse de Thé',
            'workers_per_lvl' => 1,
            'slot_names'  => [
                1 => ['name' => 'Coteau des Premières Pousses', 'desc' => 'Récolte délicate de Shincha aux arômes doux et rafraîchissants.'],
                2 => ['name' => 'Terrasses Ombragées (Gyokuro)', 'desc' => 'Parcelles couvertes de nattes de paille pour concentrer la chlorophylle.'],
                3 => ['name' => 'Hangar de Séchage & Aération', 'desc' => 'Bâtisse ventilée où les feuilles fraîchement cueillies perdent leur humidité.'],
                4 => ['name' => 'Pavillon de Torréfaction', 'desc' => 'Poêles en fonte chauffés au charbon de bois pour stopper l\'oxydation.'],
                5 => ['name' => 'Meule de Granit à Poudre Matcha', 'desc' => 'Broyage lent sous pierre meulière pour une poudre verte d\'une finesse impériale.']
            ]
        ],
        'soybean' => [
            'type'        => 'soybean',
            'name'        => 'Champs de Soja & Ateliers',
            'jp_name'     => '大豆畑 (Daizu-batake)',
            'res_name'    => 'Soja, Farine & Miso',
            'icon'        => 'fa-solid fa-seedling',
            'color'       => '#d97706',
            'color_class' => 'orange',
            'badge_text'  => 'Protéines & Nutrition',
            'desc'        => 'Champs de légumineuses fortifiant la terre et pourvoyant paysans et troupes en tofu et miso fermenté.',
            'bg_image'    => '/public/assets/resources/terroir_soybean.jpg',
            'tile_img'    => '/public/assets/tiles/tile_grain_mill.png',
            'map_coords'  => ['left' => 19.0, 'top' => 72.0],
            'field_type'  => null,
            'db_slots'    => [],
            'worker_name' => 'Semencier & Fermenteur',
            'workers_per_lvl' => 2,
            'slot_names'  => [
                1 => ['name' => 'Parcelles de Soja d\'Or', 'desc' => 'Sillons généreux enrichis en cendres naturelles et bien drainés.'],
                2 => ['name' => 'Champs de Gousses Vertes (Edamame)', 'desc' => 'Cueillette précoce pour la collation saine des ouvriers et soldats.'],
                3 => ['name' => 'Atelier de Broyage & Meule à Farine', 'desc' => 'Extraction de farine végétale et de lait de soja nourrissant.'],
                4 => ['name' => 'Presse Artisanale de Tofu', 'desc' => 'Moulage sous poids de granit pour presser de fermes blocs de tofu.'],
                5 => ['name' => 'Cave de Fermentation du Miso', 'desc' => 'Fûts de cèdre où le soja vieillit avec le sel pour développer son umami.']
            ]
        ],
        'shrine' => [
            'type'        => 'shrine',
            'name'        => 'Sérénité & Sanctuaires Shintō',
            'jp_name'     => '神社 (Jinja)',
            'res_name'    => 'Ferveur & Sérénité Spirituelle',
            'icon'        => 'fa-solid fa-torii-gate',
            'color'       => '#ec4899',
            'color_class' => 'pink',
            'badge_text'  => 'Spiritualité & Sérénité',
            'desc'        => 'Sanctuaires ancestraux et torii sacrés honourant les Kami. Maintiennent la sérénité et le rendement de l\'ensemble du domaine.',
            'bg_image'    => '/public/assets/resources/ressource_ferveur_shinto.jpg',
            'tile_img'    => '/public/assets/tiles/tile_sanctuaire.png',
            'map_coords'  => ['left' => 88.0, 'top' => 31.0],
            'field_type'  => 'solar_plant',
            'db_slots'    => [15, 16, 17, 18, 20],
            'worker_name' => 'Gardien Shintō',
            'workers_per_lvl' => 1,
            'slot_names'  => [
                1 => ['name' => 'Sanctuaire Ancestral d\'Inari', 'desc' => 'Dédié au renard céleste pour la bénédiction des récoltes féodales.'],
                2 => ['name' => 'Torii des Mille Cerisiers', 'desc' => 'Portique sacré canalisant les souffles spirituels protecteurs.'],
                3 => ['name' => 'Autel des Eaux Claires', 'desc' => 'Bassin de purification rituelle au pied des sources vives.'],
                4 => ['name' => 'Pavillon de Prière de la Forêt', 'desc' => 'Espace de contemplation et d\'offrandes sous les cèdres millénaires.'],
                5 => ['name' => 'Grand Sanctuaire du Soleil Levant', 'desc' => 'Haut lieu de culte irradiant une paix spirituelle inaltérable.']
            ]
        ],
        'housing' => [
            'type'        => 'housing',
            'name'        => 'Habitations & Village Démographique',
            'jp_name'     => '集落住居 (Shūraku Jūkyo)',
            'res_name'    => 'Capacité d\'Accueil (+5 hab./niv.)',
            'icon'        => 'fa-solid fa-house-chimney',
            'color'       => '#6366f1',
            'color_class' => 'indigo',
            'badge_text'  => 'Démographie & Logement',
            'desc'        => 'Modèle unique d\'habitation paysanne et artisanale. Chaque niveau supplémentaire augmente directement le plafond d\'accueil de +5 villageois.',
            'bg_image'    => '/public/assets/resources/terroir_village.jpg',
            'tile_img'    => '/public/assets/tiles/tile_storage.png',
            'map_coords'  => ['left' => 53.0, 'top' => 90.0],
            'field_type'  => null,
            'db_slots'    => [],
            'worker_name' => 'Villageois résident',
            'workers_per_lvl' => 0,
            'slot_names'  => [
                1 => ['name' => 'Habitation de la Vallée', 'desc' => 'Demeure paysanne traditionnelle adossée aux parcelles fertiles.'],
                2 => ['name' => 'Habitation des Coteaux', 'desc' => 'Maisons familiales établies sur les versants ensoleillés.'],
                3 => ['name' => 'Habitation du Ruisseau', 'desc' => 'Chaumières rurales bordant les cours d\'eau et moulins.'],
                4 => ['name' => 'Habitation des Artisans', 'desc' => 'Quartier d\'habitations pour maîtres maçons et charpentiers.'],
                5 => ['name' => 'Habitation de la Haute-Terre', 'desc' => 'Résidences spacieuses pour accueillir les familles de pionniers.']
            ]
        ]
    ];

    // Coordonnées spatiales précises (% left, % top) des 40 parcelles sur l'illustration panoramique 16:9
    public const SLOT_MAP_COORDS = [
        // 1. Carrière de Pierre (Nord / Massif rocheux)
        'stone' => [
            1 => ['left' => 22.0, 'top' => 9.0],
            2 => ['left' => 17.5, 'top' => 15.0],
            3 => ['left' => 24.5, 'top' => 17.5],
            4 => ['left' => 32.5, 'top' => 13.5],
            5 => ['left' => 26.0, 'top' => 32.0],
        ],
        // 2. Forêt d'Exploitation (Nord-Est)
        'wood' => [
            1 => ['left' => 73.0, 'top' => 13.0],
            2 => ['left' => 62.0, 'top' => 19.0],
            3 => ['left' => 57.0, 'top' => 24.0],
            4 => ['left' => 65.0, 'top' => 31.0],
            5 => ['left' => 74.0, 'top' => 24.0],
        ],
        // 3. Berges d'Argile (Est / Rivière)
        'clay' => [
            1 => ['left' => 89.5, 'top' => 33.0],
            2 => ['left' => 82.0, 'top' => 43.0],
            3 => ['left' => 93.0, 'top' => 42.0],
            4 => ['left' => 89.5, 'top' => 50.0],
            5 => ['left' => 95.0, 'top' => 54.0],
        ],
        // 4. Rizières en Terrasses (Centre-Sud)
        'rice' => [
            1 => ['left' => 64.0, 'top' => 63.0],
            2 => ['left' => 53.0, 'top' => 66.0],
            3 => ['left' => 59.0, 'top' => 71.0],
            4 => ['left' => 63.0, 'top' => 77.0],
            5 => ['left' => 56.0, 'top' => 81.0],
        ],
        // 5. Collines de Thé (Sud-Est)
        'tea' => [
            1 => ['left' => 94.0, 'top' => 68.0],
            2 => ['left' => 87.0, 'top' => 75.0],
            3 => ['left' => 94.0, 'top' => 81.0],
            4 => ['left' => 85.0, 'top' => 88.0],
            5 => ['left' => 92.0, 'top' => 93.0],
        ],
        // 6. Champs de Soja (Sud-Ouest)
        'soybean' => [
            1 => ['left' => 18.0, 'top' => 76.0],
            2 => ['left' => 28.0, 'top' => 74.0],
            3 => ['left' => 35.0, 'top' => 82.0],
            4 => ['left' => 12.0, 'top' => 90.0],
            5 => ['left' => 28.0, 'top' => 90.0],
        ],
        // 7. Sanctuaires Shintō (Ouest / Colline Sacrée)
        'shrine' => [
            1 => ['left' => 8.0,  'top' => 46.0],
            2 => ['left' => 10.5, 'top' => 50.0],
            3 => ['left' => 18.5, 'top' => 47.0],
            4 => ['left' => 15.0, 'top' => 57.0],
            5 => ['left' => 19.0, 'top' => 64.0],
        ],
        // 8. Cœur du Village & Habitations (Centre)
        'housing' => [
            1 => ['left' => 37.0, 'top' => 50.0],
            2 => ['left' => 41.0, 'top' => 45.0],
            3 => ['left' => 46.0, 'top' => 40.0],
            4 => ['left' => 54.0, 'top' => 39.0],
            5 => ['left' => 59.5, 'top' => 46.0],
        ],
    ];

    public function __construct(?PDO $db = null) {
        try {
            $this->db = $db ?? Database::getConnection();
        } catch (Exception $e) {
            $this->db = null;
        }
        $this->planetEngine = new PlanetEngine($this->db);
        $this->buildingEngine = new BuildingEngine($this->db);
        $this->ensureTerroirTable();
    }

    /**
     * S'assure que la table planet_terroir_slots existe
     */
    private function ensureTerroirTable(): void {
        if (!$this->db) return;
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `planet_terroir_slots` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `planet_id` INT UNSIGNED NOT NULL,
                `resource_type` VARCHAR(32) NOT NULL,
                `slot_index` TINYINT UNSIGNED NOT NULL,
                `level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `workers_assigned` INT UNSIGNED NOT NULL DEFAULT 2,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uniq_planet_res_slot` (`planet_id`, `resource_type`, `slot_index`),
                KEY `idx_planet_res` (`planet_id`, `resource_type`),
                CONSTRAINT `fk_terroir_planet` FOREIGN KEY (`planet_id`) REFERENCES `planets` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        } catch (Exception $e) {
            // Ignorer silencieusement si la table existe déjà ou environnement restreint
        }
    }

    /**
     * Récupère la liste des 8 zones avec données enrichies
     */
    public function getMapZones(int $planetId, array $planet): array {
        $fields = $this->planetEngine->getFields($planetId);
        $prodRates = $planet['prod_rates'] ?? [];
        $zones = self::ZONES;
        $housingCap = $this->getHousingCapacity($planetId);

        foreach ($zones as $key => &$z) {
            switch ($key) {
                case 'wood':
                    $z['rate_label'] = '+' . number_format($prodRates['metal'] ?? 100) . ' / h';
                    $z['status_sub'] = 'Production forestière active';
                    $z['level_avg'] = $this->calculateAvgLevelForFields($fields, 'metal_mine');
                    break;
                case 'stone':
                    $z['rate_label'] = '+' . number_format($prodRates['crystal'] ?? 80) . ' / h';
                    $z['status_sub'] = 'Extraction de granit active';
                    $z['level_avg'] = $this->calculateAvgLevelForFields($fields, 'crystal_mine');
                    break;
                case 'rice':
                    $z['rate_label'] = '+' . number_format($prodRates['deuterium'] ?? 50) . ' / h';
                    $z['status_sub'] = 'Riz impérial abondant';
                    $z['level_avg'] = $this->calculateAvgLevelForFields($fields, 'deuterium_synth');
                    break;
                case 'clay':
                    $z['rate_label'] = '+45 / h (Tuiles)';
                    $z['status_sub'] = 'Argile alluviale disponible';
                    $z['level_avg'] = $this->calculateAvgLevelForTerroir($planetId, 'clay');
                    break;
                case 'tea':
                    $z['rate_label'] = '+15 / h (Feuilles)';
                    $z['status_sub'] = 'Sérénité & Thé de cérémonie';
                    $z['level_avg'] = $this->calculateAvgLevelForTerroir($planetId, 'tea');
                    break;
                case 'soybean':
                    $z['rate_label'] = '+30 / h (Farine & Tofu)';
                    $z['status_sub'] = 'Nutrition équilibrée';
                    $z['level_avg'] = $this->calculateAvgLevelForTerroir($planetId, 'soybean');
                    break;
                case 'shrine':
                    $z['rate_label'] = '+' . ($planet['energy_max'] ?? 50) . ' Sérénité';
                    $z['status_sub'] = 'Ferveur spirituelle Shintō';
                    $z['level_avg'] = $this->calculateAvgLevelForFields($fields, 'solar_plant');
                    break;
                case 'housing':
                case 'village':
                    $pop = $planet['population'] ?? 100;
                    $z['rate_label'] = number_format($pop) . ' / ' . number_format($housingCap['total_capacity']) . ' hab.';
                    $z['status_sub'] = 'Capacité : 5 hab. par niveau';
                    $z['level_avg'] = round($housingCap['total_levels'] / 5, 1);
                    break;
            }
        }
        unset($z);

        return $zones;
    }

    /**
     * Récupère les niveaux des 5 parcelles d'habitation du village
     */
    public function getHousingLevels(int $planetId): array {
        $levels = [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1];
        if (!$this->db) return $levels;
        try {
            $stmt = $this->db->prepare("SELECT slot_index, level FROM planet_terroir_slots WHERE planet_id = ? AND (resource_type = 'housing' OR resource_type = 'village')");
            $stmt->execute([$planetId]);
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $idx = (int)$r['slot_index'];
                if ($idx >= 1 && $idx <= 5) {
                    $levels[$idx] = max($levels[$idx], (int)$r['level']);
                }
            }
        } catch (Exception $e) {}
        return $levels;
    }

    /**
     * Formule simplifiée de calcul de la capacité totale d'accueil du village :
     * Capacité Totale = 75 (Base villageoise) + (Somme des niveaux des 5 habitations × 5 villageois)
     */
    public function getHousingCapacity(int $planetId): array {
        $levels = $this->getHousingLevels($planetId);
        $totalLevels = array_sum($levels);
        $baseCap = 75;
        $bonus = $totalLevels * 5;
        $totalCap = $baseCap + $bonus;

        return [
            'base_capacity'    => $baseCap,
            'per_level_bonus'  => 5,
            'total_levels'     => $totalLevels,
            'housing_bonus'    => $bonus,
            'total_capacity'   => $totalCap,
            'housing_levels'   => $levels
        ];
    }

    /**
     * Récupère les 40 parcelles du domaine rural regroupées par les 8 catégories
     */
    public function getAll40Slots(int $planetId, array $planet): array {
        $categories = ['wood', 'stone', 'clay', 'rice', 'tea', 'soybean', 'shrine', 'housing'];
        $grouped = [];
        $globalIdx = 1;

        foreach ($categories as $catKey) {
            $slots = $this->getZoneSlots($planetId, $catKey, $planet);
            $grouped[$catKey] = [
                'meta'  => self::ZONES[$catKey],
                'slots' => []
            ];
            foreach ($slots as $slotIdx => $s) {
                $s['global_index'] = $globalIdx++;
                $s['category'] = $catKey;
                $s['category_meta'] = self::ZONES[$catKey];
                $grouped[$catKey]['slots'][$slotIdx] = $s;
            }
        }

        return $grouped;
    }

    /**
     * Récupère les 5 slots détaillés pour une zone de ressource
     */
    public function getZoneSlots(int $planetId, string $resourceType, array $planet): array {
        if ($resourceType === 'village') {
            $resourceType = 'housing';
        }
        if (!isset(self::ZONES[$resourceType])) {
            $resourceType = 'wood';
        }
        $zDef = self::ZONES[$resourceType];
        $fields = $this->planetEngine->getFields($planetId);
        $fieldsBySlot = [];
        foreach ($fields as $f) {
            $fieldsBySlot[(int)$f['field_slot']] = $f;
        }

        $queue = $this->buildingEngine->getQueue($planetId);
        $queueBySlot = [];
        foreach ($queue as $q) {
            if ($q['build_category'] === 'field') {
                $queueBySlot[(int)$q['target_id']] = $q;
            }
        }

        $buildings = $this->planetEngine->getBuildings($planetId);
        $hqLevel = $buildings['hq'] ?? 1;

        // Slots en table secondaire (clay, tea, soybean, housing)
        $terroirRows = [];
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM planet_terroir_slots WHERE planet_id = ? AND (resource_type = ? OR (resource_type = 'village' AND ? = 'housing'))");
                $stmt->execute([$planetId, $resourceType, $resourceType]);
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $terroirRows[(int)$r['slot_index']] = $r;
                }
            } catch (Exception $e) {}
        }

        $slots = [];
        for ($i = 1; $i <= 5; $i++) {
            $sMeta = $zDef['slot_names'][$i] ?? ['name' => "Parcelle #{$i}", 'desc' => 'Emplacement de production'];
            $pos = $zDef['spatial_coords'][$i] ?? ['top' => 30.0 + ($i * 10), 'left' => 20.0 + ($i * 12)];

            // Cas ressource standard rattachée aux parcelles de planet_fields (wood, stone, rice, shrine)
            $fSlot = (!empty($zDef['db_slots']) && isset($zDef['db_slots'][$i - 1])) ? $zDef['db_slots'][$i - 1] : null;

            if ($fSlot !== null && isset($fieldsBySlot[$fSlot])) {
                $fieldData = $fieldsBySlot[$fSlot];
                $lvl = (int)($fieldData['level'] ?? 1);
                $isUpgrading = isset($queueBySlot[$fSlot]);
                $activeQueueItem = $isUpgrading ? $queueBySlot[$fSlot] : null;

                $upDetails = $this->buildingEngine->getUpgradeDetails('field', $zDef['field_type'], $lvl, $hqLevel);
                $workers = $lvl * ($zDef['workers_per_lvl'] ?? 2);
                $speed = defined('SPEED_FACTOR') ? SPEED_FACTOR : 1;
                $prodHourly = round(($upDetails['next_prod'] ?? 30) * $speed);

                if ($resourceType === 'shrine') {
                    $prodLabel = '+' . ($lvl * 25) . ' Sérénité';
                } else {
                    $prodLabel = '+' . number_format($prodHourly) . ' / h';
                }

                $slots[$i] = [
                    'slot_index'      => $i,
                    'field_slot'      => $fSlot,
                    'name'            => $sMeta['name'],
                    'desc'            => $sMeta['desc'],
                    'level'           => $lvl,
                    'workers'         => $workers,
                    'worker_role'     => $zDef['worker_name'],
                    'prod_hourly'     => $prodHourly,
                    'prod_label'      => $prodLabel,
                    'cost'            => $upDetails['cost'],
                    'duration'        => $upDetails['duration'],
                    'can_afford'      => ($planet['metal'] >= $upDetails['cost']['metal'] &&
                                          $planet['crystal'] >= $upDetails['cost']['crystal'] &&
                                          $planet['deuterium'] >= $upDetails['cost']['deuterium']),
                    'is_upgrading'    => $isUpgrading,
                    'queue_item'      => $activeQueueItem,
                    'spatial_pos'     => $pos,
                    'map_coords'      => self::SLOT_MAP_COORDS[$resourceType][$i] ?? ['left' => 50.0, 'top' => 50.0],
                    'tile_img'        => $zDef['tile_img'] ?? '/public/assets/tiles/tile_bucheron.png'
                ];
            } else {
                // Zone terroir secondaire (clay, tea, soybean, housing ou shrine hors champs)
                $tRow = $terroirRows[$i] ?? null;
                $lvl = $tRow ? (int)$tRow['level'] : 1;
                $workers = $lvl * ($zDef['workers_per_lvl'] ?? 2);
                $baseCost = $this->getBaseCostForTerroir($resourceType, $i, $lvl);
                $prodHourly = $this->getHourlyProductionForTerroir($resourceType, $i, $lvl);

                if ($resourceType === 'housing') {
                    $prodLabel = '+' . ($lvl * 5) . ' hab. (Niv. ' . $lvl . ' × 5)';
                } elseif ($resourceType === 'shrine') {
                    $prodLabel = '+' . ($lvl * 25) . ' Sérénité';
                } else {
                    $prodLabel = '+' . number_format($prodHourly) . ' / h';
                }

                $slots[$i] = [
                    'slot_index'      => $i,
                    'field_slot'      => null,
                    'name'            => $sMeta['name'],
                    'desc'            => $sMeta['desc'],
                    'level'           => $lvl,
                    'workers'         => $workers,
                    'worker_role'     => $zDef['worker_name'],
                    'prod_hourly'     => $prodHourly,
                    'prod_label'      => $prodLabel,
                    'cost'            => $baseCost['cost'],
                    'duration'        => $baseCost['duration'],
                    'can_afford'      => ($planet['metal'] >= $baseCost['cost']['metal'] &&
                                          $planet['crystal'] >= $baseCost['cost']['crystal'] &&
                                          $planet['deuterium'] >= $baseCost['cost']['deuterium']),
                    'is_upgrading'    => false,
                    'queue_item'      => null,
                    'spatial_pos'     => $pos,
                    'map_coords'      => self::SLOT_MAP_COORDS[$resourceType][$i] ?? ['left' => 50.0, 'top' => 50.0],
                    'tile_img'        => $zDef['tile_img'] ?? '/public/assets/tiles/tile_storage.png'
                ];
            }
        }

        return $slots;
    }

    /**
     * Améliore un slot de terroir
     */
    public function upgradeSlot(int $planetId, string $resourceType, int $slotIndex): array {
        if ($resourceType === 'village') {
            $resourceType = 'housing';
        }
        if (!isset(self::ZONES[$resourceType])) {
            return ['success' => false, 'error' => 'Type de ressource invalide.'];
        }
        if ($slotIndex < 1 || $slotIndex > 5) {
            return ['success' => false, 'error' => 'Numéro de slot invalide (1 à 5).'];
        }

        $zDef = self::ZONES[$resourceType];

        // 1. Si rattaché à une parcelle standard de planet_fields
        if (!empty($zDef['db_slots']) && isset($zDef['db_slots'][$slotIndex - 1])) {
            $fSlot = $zDef['db_slots'][$slotIndex - 1];
            $fields = $this->planetEngine->getFields($planetId);
            $hasField = false;
            foreach ($fields as $f) {
                if ((int)$f['field_slot'] === $fSlot) {
                    $hasField = true;
                    break;
                }
            }
            if ($hasField) {
                return $this->buildingEngine->startUpgrade($planetId, 'field', (string)$fSlot, $fSlot, $zDef['field_type']);
            }
        }

        // 2. Si slot de terroir secondaire (clay, tea, soybean, housing)
        if (!$this->db) {
            return ['success' => false, 'error' => 'Base de données temporairement inaccessible.'];
        }

        try {
            $this->ensureTerroirTable();

            // Vérifier les ressources de la planète
            $stmtP = $this->db->prepare("SELECT metal, crystal, deuterium, population FROM planets WHERE id = ? FOR UPDATE");
            $this->db->beginTransaction();
            $stmtP->execute([$planetId]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);
            if (!$p) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Planète introuvable.'];
            }

            // Récupérer le niveau actuel
            $stmtS = $this->db->prepare("SELECT level FROM planet_terroir_slots WHERE planet_id = ? AND resource_type = ? AND slot_index = ?");
            $stmtS->execute([$planetId, $resourceType, $slotIndex]);
            $curRow = $stmtS->fetch(PDO::FETCH_ASSOC);
            $curLvl = $curRow ? (int)$curRow['level'] : 1;
            $nextLvl = $curLvl + 1;

            $costInfo = $this->getBaseCostForTerroir($resourceType, $slotIndex, $curLvl);
            $cost = $costInfo['cost'];

            if ($p['metal'] < $cost['metal'] || $p['crystal'] < $cost['crystal'] || $p['deuterium'] < $cost['deuterium']) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Ressources insuffisantes pour cette élévation.'];
            }

            // Déduire les ressources
            $stmtUpP = $this->db->prepare("UPDATE planets SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? WHERE id = ?");
            $stmtUpP->execute([$cost['metal'], $cost['crystal'], $cost['deuterium'], $planetId]);

            // Insérer ou mettre à jour le slot
            $stmtUpS = $this->db->prepare("INSERT INTO planet_terroir_slots (planet_id, resource_type, slot_index, level, workers_assigned)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE level = VALUES(level), workers_assigned = VALUES(workers_assigned)");
            $workers = $nextLvl * ($zDef['workers_per_lvl'] ?? 2);
            $stmtUpS->execute([$planetId, $resourceType, $slotIndex, $nextLvl, $workers]);

            // Si amélioration d'habitation, accueillir directement +5 villageois
            if ($resourceType === 'housing') {
                $this->db->prepare("UPDATE planets SET population = LEAST(population + 5, 5000) WHERE id = ?")->execute([$planetId]);
            }

            $this->db->commit();

            return [
                'success'    => true,
                'message'    => "« {$zDef['slot_names'][$slotIndex]['name']} » élevé avec succès au Niveau {$nextLvl} !",
                'new_level'  => $nextLvl,
                'new_workers'=> $workers
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Erreur technique : ' . $e->getMessage()];
        }
    }

    /**
     * Bilan démographique pour la vue dédiée au Village
     */
    public function getVillageSummary(int $planetId, array $planet): array {
        $buildings = $this->planetEngine->getBuildings($planetId);
        $fields = $this->planetEngine->getFields($planetId);

        // Formule simplifiée de calcul de la capacité totale d'accueil du village :
        // Base de 75 villageois + (Somme des niveaux des 5 parcelles d'habitations × 5 villageois)
        $housingCap = $this->getHousingCapacity($planetId);
        $maxPop = $housingCap['total_capacity'];

        $activeFeast = $this->planetEngine->getActiveFeast($planetId);
        $workforce = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop);
        $contentment = PopulationEngine::calculateContentment($planet, $activeFeast, $workforce, $buildings);
        $delinquency = $contentment['delinquency'] ?? PopulationEngine::calculateDelinquency($planet, $buildings, $workforce);

        return [
            'workforce'       => $workforce,
            'contentment'     => $contentment,
            'delinquency'     => $delinquency,
            'population'      => (int)($planet['population'] ?? 100),
            'max_pop'         => $maxPop,
            'housing_cap'     => $housingCap,
            'sake'            => (float)($planet['sake'] ?? 0),
            'rice_flour'      => (float)($planet['rice_flour'] ?? 0),
            'famine'          => !empty($planet['famine_active'])
        ];
    }

    private function calculateAvgLevelForFields(array $fields, string $type): float {
        $count = 0;
        $total = 0;
        foreach ($fields as $f) {
            if ($f['type'] === $type) {
                $count++;
                $total += (int)$f['level'];
            }
        }
        return $count > 0 ? round($total / $count, 1) : 1.0;
    }

    private function calculateAvgLevelForTerroir(int $planetId, string $resourceType): float {
        if (!$this->db) return 1.0;
        try {
            $stmt = $this->db->prepare("SELECT AVG(level) as avg_lvl FROM planet_terroir_slots WHERE planet_id = ? AND resource_type = ?");
            $stmt->execute([$planetId, $resourceType]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && $row['avg_lvl'] !== null ? round((float)$row['avg_lvl'], 1) : 1.0;
        } catch (Exception $e) {
            return 1.0;
        }
    }

    private function getBaseCostForTerroir(string $type, int $slotIndex, int $currentLevel): array {
        $mult = pow(1.45, $currentLevel);
        switch ($type) {
            case 'clay':
                $m = (int)round(70 * $mult);
                $c = (int)round(40 * $mult);
                $d = (int)round(20 * $mult);
                $sec = (int)round(90 * $mult);
                break;
            case 'tea':
                $m = (int)round(60 * $mult);
                $c = (int)round(30 * $mult);
                $d = (int)round(40 * $mult);
                $sec = (int)round(80 * $mult);
                break;
            case 'soybean':
                $m = (int)round(50 * $mult);
                $c = (int)round(35 * $mult);
                $d = (int)round(30 * $mult);
                $sec = (int)round(75 * $mult);
                break;
            case 'shrine':
                $m = (int)round(75 * $mult);
                $c = (int)round(30 * $mult);
                $d = (int)round(10 * $mult);
                $sec = (int)round(100 * $mult);
                break;
            case 'housing':
            case 'village':
            default:
                $m = (int)round(65 * $mult);
                $c = (int)round(40 * $mult);
                $d = (int)round(25 * $mult);
                $sec = (int)round(90 * $mult);
                break;
        }
        return [
            'cost' => ['metal' => $m, 'crystal' => $c, 'deuterium' => $d],
            'duration' => max(15, $sec)
        ];
    }

    private function getHourlyProductionForTerroir(string $type, int $slotIndex, int $level): int {
        $speed = defined('SPEED_FACTOR') ? SPEED_FACTOR : 1;
        switch ($type) {
            case 'clay':
                return (int)round(($level * 12) * $speed);
            case 'tea':
                return (int)round(($level * 8) * $speed);
            case 'soybean':
                return (int)round(($level * 10) * $speed);
            case 'shrine':
                return (int)round(($level * 25) * $speed);
            case 'housing':
            case 'village':
            default:
                return $level * 5; // Capacité de population apportée
        }
    }
}
