<?php
/**
 * Moteur des Technologies et Recherches Féodales (Académie des Savoirs & Forge)
 * Formules exponentielles calquées sur BuildingEngine.php avec coefficients configurables
 * et vérification des paliers de civilisation (Shinōkōshō).
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/SocialCasteEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class ResearchEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;

    // Définition par défaut des technologies féodales, paliers de civilisation requis et métadonnées
    public const RESEARCH_DEFINITIONS = [
        'fishing_tech' => [
            'name' => 'Halieutique Féodale',
            'civ_tier' => 1,
            'category' => 'logistics',
            'category_label' => 'Subsistance & Terroirs',
            'icon' => '🐟',
            'base_cost' => ['metal' => 300, 'crystal' => 250, 'deuterium' => 400],
            'cost_multiplier' => 1.55,
            'base_time' => 120,
            'description' => 'Développe les filets traditionnels, nasses fluviales et techniques de salaison pour accroître l\'efficacité des Pêcheurs.'
        ],
        'energy_tech' => [
            'name' => 'Méditation Shintō & Sérénité',
            'civ_tier' => 1,
            'category' => 'doctrine',
            'category_label' => 'Doctrine Spirituelle',
            'icon' => '🕯️',
            'base_cost' => ['metal' => 800, 'crystal' => 400, 'deuterium' => 200],
            'cost_multiplier' => 1.55,
            'base_time' => 150,
            'description' => 'Renforce l\'harmonie spirituelle et la ferveur dans l\'ensemble du domaine.'
        ],
        'espionage_tech' => [
            'name' => 'Réseau de Renseignements Shinobi',
            'civ_tier' => 2,
            'category' => 'strategy',
            'category_label' => 'Renseignement Shinobi',
            'icon' => '🥷',
            'base_cost' => ['metal' => 500, 'crystal' => 1000, 'deuterium' => 300],
            'cost_multiplier' => 1.6,
            'base_time' => 180,
            'description' => 'Permet aux informateurs d\'obtenir des rapports détaillés sur les fiefs rivaux.'
        ],
        'laser_tech' => [
            'name' => 'Art du Tir & Balistique',
            'civ_tier' => 2,
            'category' => 'military',
            'category_label' => 'Tir & Balistique',
            'icon' => '🏹',
            'base_cost' => ['metal' => 1200, 'crystal' => 600, 'deuterium' => 100],
            'cost_multiplier' => 1.6,
            'base_time' => 210,
            'description' => 'Perfectionne la puissance de tir et la précision des archers Yumi et arquebusiers.'
        ],
        'shield_tech' => [
            'name' => 'Tactique de Mur de Pavois',
            'civ_tier' => 2,
            'category' => 'defense',
            'category_label' => 'Défense Castrale',
            'icon' => '⛩️',
            'base_cost' => ['metal' => 1000, 'crystal' => 1200, 'deuterium' => 500],
            'cost_multiplier' => 1.6,
            'base_time' => 270,
            'description' => 'Renforce la résistance des palissades et boucliers face aux tirs ennemis.'
        ],
        'armor_tech' => [
            'name' => 'Forge & Métallurgie du Tamahagane',
            'civ_tier' => 3,
            'category' => 'military',
            'category_label' => 'Armement & Forge',
            'icon' => '🛡️',
            'base_cost' => ['metal' => 2000, 'crystal' => 800, 'deuterium' => 100],
            'cost_multiplier' => 1.65,
            'base_time' => 240,
            'description' => 'Améliore la résistance des armures laquées et des lames forgées en acier noble.'
        ],
        'propulsion_tech' => [
            'name' => 'Art Militaire des Relais de Poste',
            'civ_tier' => 3,
            'category' => 'logistics',
            'category_label' => 'Logistique des Armées',
            'icon' => '🐎',
            'base_cost' => ['metal' => 1500, 'crystal' => 1500, 'deuterium' => 600],
            'cost_multiplier' => 1.6,
            'base_time' => 300,
            'description' => 'Améliore la rapidité de déplacement des coursiers et des armées provinciales.'
        ],
        'gunpowder_tech' => [
            'name' => 'Poudre Noire & Alchimie Féodale (Kayaku)',
            'civ_tier' => 4,
            'category' => 'military',
            'category_label' => 'Artillerie & Poudre',
            'icon' => '💥',
            'base_cost' => ['metal' => 3000, 'crystal' => 2500, 'deuterium' => 1500],
            'cost_multiplier' => 1.7,
            'base_time' => 450,
            'description' => 'Savante synthèse du soufre et du salpêtre permettant le tir explosif des arquebuses Tanegashima et grenades Horokubiya.'
        ],
        'katana_prestige_tech' => [
            'name' => 'Fonte de Maître & Katanas de Prestige',
            'civ_tier' => 4,
            'category' => 'military',
            'category_label' => 'Armement & Forge',
            'icon' => '⚔️',
            'base_cost' => ['metal' => 4500, 'crystal' => 3000, 'deuterium' => 2000],
            'cost_multiplier' => 1.75,
            'base_time' => 540,
            'description' => 'Secret ancestral de trempe sélective sous l\'argile, conférant aux katanas une coupe tranchante et une valeur honorifique suprême.'
        ],
        'warp_tech' => [
            'name' => 'Grandes Voies Stratégiques (Gokaidō)',
            'civ_tier' => 5,
            'category' => 'logistics',
            'category_label' => 'Grandes Voies (Gokaido)',
            'icon' => '🗾',
            'base_cost' => ['metal' => 5000, 'crystal' => 10000, 'deuterium' => 4000],
            'cost_multiplier' => 1.7,
            'base_time' => 600,
            'description' => 'Trace les grandes routes impériales permettant les marches accélérées d\'armées de siège et le contrôle hégémonique du Japon.'
        ]
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->planetEngine = new PlanetEngine($this->db);
    }

    /**
     * Récupère la liste fusionnée de toutes les définitions de recherche (statiques + enregistrées en base)
     */
    public static function getAllDefinitions(?PDO $db = null): array {
        $defs = self::RESEARCH_DEFINITIONS;

        // Charger les surcharges ou nouvelles recherches configurées en base
        try {
            $conn = $db ?? Database::getConnection();
            $customRows = $conn->query("SELECT * FROM researches")->fetchAll(PDO::FETCH_ASSOC);
            $customMeta = json_decode((string)GameConfig::get('custom_research_metadata', '{}'), true) ?: [];

            foreach ($customRows as $row) {
                $code = (string)$row['code'];
                $meta = $customMeta[$code] ?? [];

                if (isset($defs[$code])) {
                    // Mettre à jour les coûts/délais depuis la BD si modifiés par l'administrateur
                    $defs[$code]['name'] = $row['name'] ?: $defs[$code]['name'];
                    $defs[$code]['description'] = $row['description'] ?: $defs[$code]['description'];
                    $defs[$code]['base_cost']['metal'] = (int)$row['metal_cost'];
                    $defs[$code]['base_cost']['crystal'] = (int)$row['crystal_cost'];
                    $defs[$code]['base_cost']['deuterium'] = (int)$row['deuterium_cost'];
                    $defs[$code]['base_time'] = (int)$row['base_time'];
                    if (isset($meta['civ_tier'])) $defs[$code]['civ_tier'] = (int)$meta['civ_tier'];
                    if (isset($meta['cost_multiplier'])) $defs[$code]['cost_multiplier'] = (float)$meta['cost_multiplier'];
                    if (isset($meta['category'])) $defs[$code]['category'] = (string)$meta['category'];
                    if (isset($meta['icon'])) $defs[$code]['icon'] = (string)$meta['icon'];
                } else {
                    // Nouvelle recherche personnalisée injectée !
                    $civTier = (int)($meta['civ_tier'] ?? 1);
                    $defs[$code] = [
                        'name' => (string)$row['name'],
                        'civ_tier' => $civTier,
                        'category' => (string)($meta['category'] ?? 'general'),
                        'category_label' => (string)($meta['category_label'] ?? 'Traité Supérieur'),
                        'icon' => (string)($meta['icon'] ?? '📜'),
                        'base_cost' => [
                            'metal' => (int)$row['metal_cost'],
                            'crystal' => (int)$row['crystal_cost'],
                            'deuterium' => (int)$row['deuterium_cost']
                        ],
                        'cost_multiplier' => (float)($meta['cost_multiplier'] ?? 1.65),
                        'base_time' => (int)$row['base_time'],
                        'description' => (string)$row['description'],
                        'is_custom' => true
                    ];
                }
            }
        } catch (Throwable $e) {}

        return $defs;
    }

    /**
     * Récupère la définition d'une technologie par son code
     */
    public static function getDefinitionByCode(string $code, ?PDO $db = null): ?array {
        $all = self::getAllDefinitions($db);
        return $all[$code] ?? null;
    }

    /**
     * Calcule le coût et la durée d'une technologie calqués sur le modèle exponentiel de BuildingEngine
     */
    public function calculateTechUpgrade(array $res, int $currentLevel, int $labLevel, int $population = 0, float $eruditeRatio = 0.0): array {
        $targetLevel = $currentLevel + 1;

        // Coefficients dynamiques du Game Elevate Designer
        $timeCoeff   = max(0.05, min(10.0, (float)GameConfig::get('research_time_coeff', 1.0)));
        $timeGrowth  = max(1.01, min(100.0, (float)GameConfig::get('research_time_growth', 1.35)));
        $costCoeff   = max(0.05, min(10.0, (float)GameConfig::get('research_cost_coeff', 1.0)));
        $costGrowth  = max(0.5, min(2.5, (float)GameConfig::get('research_cost_growth', 1.65)));

        $code = (string)($res['code'] ?? '');
        $def = self::RESEARCH_DEFINITIONS[$code] ?? null;

        $costMultFactor = $def['cost_multiplier'] ?? 1.6;
        $baseMetal = (int)($res['metal_cost'] ?? ($def['base_cost']['metal'] ?? 1000));
        $baseCrystal = (int)($res['crystal_cost'] ?? ($def['base_cost']['crystal'] ?? 800));
        $baseDeut = (int)($res['deuterium_cost'] ?? ($def['base_cost']['deuterium'] ?? 400));
        $baseTime = (int)($res['base_time'] ?? ($def['base_time'] ?? 300));

        // Formule de coût exponentielle (comme les bâtiments)
        $mult = pow($costMultFactor * ($costGrowth / 1.65), $currentLevel);
        $metal = (int)round($baseMetal * $mult * $costCoeff);
        $crystal = (int)round($baseCrystal * $mult * $costCoeff);
        $deut = (int)round($baseDeut * $mult * $costCoeff);

        // Formule de temps exponentielle (comme BuildingEngine avec palier de niveau et réduction Académie + Érudits)
        $speed = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 1));
        
        // Réduction par le niveau de l'Académie des Savoirs (0.95^(labLevel - 1))
        $labFactor = pow(0.95, max(0, $labLevel - 1));
        
        // Progression exponentielle selon le niveau de recherche cible
        $lvlFactor = pow($timeGrowth, $currentLevel) * pow($targetLevel, 0.85);

        // Bonus d'Érudits de la Caste 4 (jusqu'à 25% d'accélération supplémentaire)
        $eruditeBonus = min(0.25, max(0.0, $eruditeRatio * 2.5));
        $eruditeFactor = 1.0 / (1.0 + $eruditeBonus);

        $duration = max(15, (int)round((($baseTime * $lvlFactor * $labFactor * $eruditeFactor * $timeCoeff) / $speed)));

        return [
            'target_level' => $targetLevel,
            'cost_metal' => $metal,
            'cost_crystal' => $crystal,
            'cost_deuterium' => $deut,
            'duration' => $duration
        ];
    }

    public function getResearches(int $userId, int $planetId): array {
        $this->processQueue($userId);

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $labLvl = (int)($buildings['research_lab'] ?? 0);
        $pop = (int)($planet['population'] ?? 100);
        $civLevel = (int)($planet['civilization_level'] ?? 1);
        $eruditeRatio = (float)($planet['social_castes']['erudite_ratio'] ?? 0.0);

        $rawRows = [];
        $userLevels = [];
        try {
            $stmtLvl = $this->db->prepare("SELECT research_code, level FROM user_researches WHERE user_id = ?");
            $stmtLvl->execute([$userId]);
            $userLevels = $stmtLvl->fetchAll(PDO::FETCH_KEY_PAIR);

            $stmt = $this->db->prepare("SELECT * FROM researches ORDER BY base_time ASC");
            $stmt->execute();
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}

        $researchesMap = [];
        foreach ($rawRows as $row) {
            $researchesMap[$row['code']] = $row;
        }

        // Intégrer toutes les recherches (définitions initiales + recherches injectées et surchargées en base)
        $allDefinitions = self::getAllDefinitions($this->db);
        $researches = [];
        foreach ($allDefinitions as $code => $def) {
            $curLvl = (int)($userLevels[$code] ?? 0);
            if (isset($researchesMap[$code])) {
                $item = $researchesMap[$code];
                // Préserver nom/description si non renseignés
                $item['name'] = $item['name'] ?: $def['name'];
                $item['description'] = $item['description'] ?: $def['description'];
            } else {
                $item = [
                    'code' => $code,
                    'name' => $def['name'],
                    'metal_cost' => $def['base_cost']['metal'],
                    'crystal_cost' => $def['base_cost']['crystal'],
                    'deuterium_cost' => $def['base_cost']['deuterium'],
                    'base_time' => $def['base_time'],
                    'description' => $def['description']
                ];
            }
            $item['current_level'] = $curLvl;

            $curLvl = (int)$item['current_level'];
            $upgradeDetails = $this->calculateTechUpgrade($item, $curLvl, $labLvl, $pop, $eruditeRatio);

            $requiredCiv = (int)($def['civ_tier'] ?? 1);
            $civMet = ($civLevel >= $requiredCiv);
            $casteInfo = SocialCasteEngine::CASTES[$requiredCiv] ?? SocialCasteEngine::CASTES[1];

            $item['next_level'] = $upgradeDetails['target_level'];
            $item['cost_metal'] = $upgradeDetails['cost_metal'];
            $item['cost_crystal'] = $upgradeDetails['cost_crystal'];
            $item['cost_deuterium'] = $upgradeDetails['cost_deuterium'];
            $item['duration'] = $upgradeDetails['duration'];
            $item['civ_tier_required'] = $requiredCiv;
            $item['civ_tier_met'] = $civMet;
            $item['civ_tier_name'] = $casteInfo['name'];
            $item['can_research'] = ($labLvl >= 1 && $civMet);

            $researches[] = $item;
        }

        return $researches;
    }

    public function getActiveQueue(int $userId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT rq.*, r.name as research_name 
                FROM research_queue rq 
                LEFT JOIN researches r ON rq.research_code = r.code 
                WHERE rq.user_id = ? AND rq.finishes_at > UNIX_TIMESTAMP() 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $res = $stmt->fetch();
            if ($res && empty($res['research_name'])) {
                $def = self::getDefinitionByCode($res['research_code'], $this->db);
                $res['research_name'] = $def['name'] ?? ucfirst($res['research_code']);
            }
            return $res ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function startResearch(int $userId, int $planetId, string $researchCode): array {
        $this->processQueue($userId);

        if ($this->getActiveQueue($userId)) {
            throw new Exception("Une recherche scientifique est déjà en cours dans l'Académie.");
        }

        $planet = $this->planetEngine->updatePlanet($planetId);
        $buildings = $this->planetEngine->getBuildings($planetId);
        $labLvl = (int)($buildings['research_lab'] ?? 0);

        if ($labLvl < 1) {
            throw new Exception("Vous devez d'abord construire l'Académie des Savoirs & Forge.");
        }

        $def = self::getDefinitionByCode($researchCode, $this->db);
        if (!$def) {
            throw new Exception("Cette technologie féodale est inconnue du traité de guerre.");
        }

        // Vérification du palier de civilisation (Shinōkōshō)
        $civLevel = (int)($planet['civilization_level'] ?? 1);
        $requiredCiv = (int)($def['civ_tier'] ?? 1);
        if ($civLevel < $requiredCiv) {
            $casteInfo = SocialCasteEngine::CASTES[$requiredCiv];
            throw new Exception("Niveau de civilisation insuffisant : requiert la caste '{$casteInfo['name']}' (Palier {$requiredCiv}). Comblez les besoins de votre population !");
        }

        // Récupération du niveau actuel
        $curLvl = 0;
        try {
            $stmt = $this->db->prepare("SELECT level FROM user_researches WHERE user_id = ? AND research_code = ?");
            $stmt->execute([$userId, $researchCode]);
            $curLvl = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {}

        $pop = (int)($planet['population'] ?? 100);
        $eruditeRatio = (float)($planet['social_castes']['erudite_ratio'] ?? 0.0);
        $calcItem = [
            'code' => $researchCode,
            'metal_cost' => $def['base_cost']['metal'],
            'crystal_cost' => $def['base_cost']['crystal'],
            'deuterium_cost' => $def['base_cost']['deuterium'],
            'base_time' => $def['base_time']
        ];
        $upgrade = $this->calculateTechUpgrade($calcItem, $curLvl, $labLvl, $pop, $eruditeRatio);

        $costMetal = $upgrade['cost_metal'];
        $costCrystal = $upgrade['cost_crystal'];
        $costDeut = $upgrade['cost_deuterium'];
        $duration = $upgrade['duration'];
        $nextLvl = $upgrade['target_level'];

        if ($planet['metal'] < $costMetal || $planet['crystal'] < $costCrystal || $planet['deuterium'] < $costDeut) {
            throw new Exception("Ressources insuffisantes pour lancer ce traité technologique.");
        }

        $now = time();
        $finishesAt = $now + $duration;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE planets 
                SET metal = metal - ?, crystal = crystal - ?, deuterium = deuterium - ? 
                WHERE id = ?
            ")->execute([$costMetal, $costCrystal, $costDeut, $planetId]);

            $this->db->prepare("
                INSERT INTO research_queue 
                (user_id, planet_id, research_code, target_level, started_at, finishes_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$userId, $planetId, $researchCode, $nextLvl, $now, $finishesAt]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => "Étude de '{$def['name']}' lancée pour le Niveau {$nextLvl} !",
            'finishes_at' => $finishesAt
        ];
    }

    public function processQueue(int $userId): void {
        $now = time();
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM research_queue 
                WHERE user_id = ? AND finishes_at <= ?
            ");
            $stmt->execute([$userId, $now]);
            $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($completed as $item) {
                $this->db->prepare("
                    INSERT INTO user_researches (user_id, research_code, level) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE level = ?
                ")->execute([$userId, $item['research_code'], $item['target_level'], $item['target_level']]);

                // Points d'honneur
                $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?")
                    ->execute([(int)$item['target_level'] * 5, $userId]);

                $this->db->prepare("DELETE FROM research_queue WHERE id = ?")->execute([$item['id']]);
            }
        } catch (Throwable $e) {}
    }
}
