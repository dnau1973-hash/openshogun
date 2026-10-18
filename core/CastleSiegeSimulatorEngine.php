<?php
/**
 * CastleSiegeSimulatorEngine — Moteur Métier de Simulation des Sièges sur les 12 Châteaux Authentiques
 * OpenShogun / Game Studio (Game Elevate Designer)
 * 
 * Modélise les affrontements échelonnés en 3 phases (Douves/Portes, Murailles Ishigaki, Donjon Tenshu)
 * avec topographies historiques (Yamajiro, Hirajiro, Hirayamajiro, Mizujiro) et météo tactique.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CastleEngine.php';
require_once __DIR__ . '/CombatEngine.php';
require_once __DIR__ . '/GameConfig.php';

class CastleSiegeSimulatorEngine {
    private PDO $db;
    private CastleEngine $castleEngine;

    // Métadonnées topographiques des 12 châteaux authentiques
    public const CASTLE_TOPOGRAPHY = [
        'himeji' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Colline Fortifiée (Hirayamajiro)',
            'altitude_m' => 45,
            'gate_layers' => 3, // Labyrinthe en spirale (Portes Hishi, I, Ro, Ha)
            'wall_height_m' => 26,
            'has_water_moat' => true,
            'moat_depth_m' => 4,
            'special_mechanic' => 'Labyrinthe en spirale : -30% aux attaques directes, 3 portes successives à abattre.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.15,
            'siege_weapon_eff' => 0.85,
            'climb_difficulty' => 1.25,
        ],
        'matsumoto' => [
            'type' => 'hirajiro',
            'type_name' => 'Plaine Aquatique (Hirajiro)',
            'altitude_m' => 10,
            'gate_layers' => 2,
            'wall_height_m' => 15,
            'has_water_moat' => true,
            'moat_depth_m' => 6, // Triple ceinture de douves profondes
            'special_mechanic' => 'Triples douves d\'eau : -25% de vitesse pour les béliers, tirs d\'arquebuses défensifs +20%.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.10,
            'siege_weapon_eff' => 0.75,
            'climb_difficulty' => 1.10,
        ],
        'inuyama' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Promontoire Fluvial (Hirayamajiro)',
            'altitude_m' => 40,
            'gate_layers' => 2,
            'wall_height_m' => 20,
            'has_water_moat' => false,
            'moat_depth_m' => 0,
            'special_mechanic' => 'Falaise abrupte sur le fleuve Kiso : Assauts de flanc impossibles, concentration frontale.',
            'cavalry_allowed' => false, // Falaise impraticable pour les chevaux
            'cavalry_penalty' => 0.80,
            'siege_weapon_eff' => 0.80,
            'climb_difficulty' => 1.40,
        ],
        'hikone' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Colline du Lac Biwa (Hirayamajiro)',
            'altitude_m' => 50,
            'gate_layers' => 2,
            'wall_height_m' => 22,
            'has_water_moat' => true,
            'moat_depth_m' => 3,
            'special_mechanic' => 'Pont basculant Tenbin-yagura : Rupture d\'élan des béliers (-40% efficacité bélier).',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.05,
            'siege_weapon_eff' => 0.70,
            'climb_difficulty' => 1.20,
        ],
        'matsue' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Bastion du Lac Shinji (Hirayamajiro)',
            'altitude_m' => 29,
            'gate_layers' => 2,
            'wall_height_m' => 18,
            'has_water_moat' => true,
            'moat_depth_m' => 5,
            'special_mechanic' => 'Tourelles d\'angle Yagura renforcées : Tirs croisés permanents affaiblissant les engins de jet.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.10,
            'siege_weapon_eff' => 0.85,
            'climb_difficulty' => 1.15,
        ],
        'bitchu_matsuyama' => [
            'type' => 'yamajiro',
            'type_name' => 'Donjon de Haute Montagne (Yamajiro)',
            'altitude_m' => 430, // Plus haut donjon du Japon
            'gate_layers' => 2,
            'wall_height_m' => 28,
            'has_water_moat' => false,
            'moat_depth_m' => 0,
            'special_mechanic' => 'Relief de montagne escarpé (430m) : Cavalerie lourde bannie, fatigue des fantassins (-35% puissance offensive).',
            'cavalry_allowed' => false, // Strictement interdit
            'cavalry_penalty' => 1.00,
            'siege_weapon_eff' => 0.50, // Difficile d'acheminer de lourdes catapultes
            'climb_difficulty' => 1.60,
        ],
        'maruoka' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Colline de la Brume (Hirayamajiro)',
            'altitude_m' => 20,
            'gate_layers' => 1,
            'wall_height_m' => 14,
            'has_water_moat' => true,
            'moat_depth_m' => 2,
            'special_mechanic' => 'Tuiles de pierre volcanique de Shaku : Immunité complète aux incendies et flèches de feu.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.05,
            'siege_weapon_eff' => 0.90,
            'climb_difficulty' => 1.10,
        ],
        'kochi' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Citadelle Unifiée (Hirayamajiro)',
            'altitude_m' => 44,
            'gate_layers' => 2,
            'wall_height_m' => 22,
            'has_water_moat' => true,
            'moat_depth_m' => 3,
            'special_mechanic' => 'Pointes de fer Shinobigaeshi & meurtrières Otesuji : Malus de -50% d\'efficacité pour l\'escalade shinobi.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.15,
            'siege_weapon_eff' => 0.85,
            'climb_difficulty' => 1.50,
        ],
        'uwajima' => [
            'type' => 'mizujiro',
            'type_name' => 'Forteresse Côtière Pentagonale (Mizujiro)',
            'altitude_m' => 80,
            'gate_layers' => 2,
            'wall_height_m' => 19,
            'has_water_moat' => true,
            'moat_depth_m' => 4,
            'special_mechanic' => 'Plan pentagonal trompeur de Takatora : Illusion d\'optique exposant l\'attaquant à des tirs de revers.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.15,
            'siege_weapon_eff' => 0.80,
            'climb_difficulty' => 1.30,
        ],
        'marugame' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Forteresse des Hautes Murailles (Hirayamajiro)',
            'altitude_m' => 66,
            'gate_layers' => 3,
            'wall_height_m' => 60, // Murailles en éventail les plus hautes du Japon
            'has_water_moat' => true,
            'moat_depth_m' => 5,
            'special_mechanic' => 'Murailles Ogi-no-kōbai (60m) : Remparts géants quasiment infranchissables sans engins de siège (+50% PV muraille).',
            'cavalry_allowed' => false,
            'cavalry_penalty' => 0.70,
            'siege_weapon_eff' => 0.95,
            'climb_difficulty' => 1.85,
        ],
        'matsuyama' => [
            'type' => 'hirayamajiro',
            'type_name' => 'Citadelle Complexe d\'Iyo (Hirayamajiro)',
            'altitude_m' => 132,
            'gate_layers' => 3,
            'wall_height_m' => 24,
            'has_water_moat' => false,
            'moat_depth_m' => 0,
            'special_mechanic' => 'Style complexe renritsu-shiki avec 21 dépendances : Cours intérieures formant des zones de tir croisé mortelles.',
            'cavalry_allowed' => false,
            'cavalry_penalty' => 0.50,
            'siege_weapon_eff' => 0.80,
            'climb_difficulty' => 1.35,
        ],
        'hirosaki' => [
            'type' => 'hirajiro',
            'type_name' => 'Sentinelle Boréale (Hirajiro)',
            'altitude_m' => 25,
            'gate_layers' => 3,
            'wall_height_m' => 16,
            'has_water_moat' => true,
            'moat_depth_m' => 4,
            'special_mechanic' => 'Froid sibérien de Tsugaru : Armes à feu moins fiables sous la neige, douves partiellement gelées mais instables.',
            'cavalry_allowed' => true,
            'cavalry_penalty' => 0.20,
            'siege_weapon_eff' => 0.80,
            'climb_difficulty' => 1.15,
        ],
    ];

    // Caractéristiques de base des troupes pour le simulateur de siège
    public const TROOP_STATS = [
        'ashigaru' => [
            'name' => 'Ashigarus Yari (Lanciers)',
            'category' => 'infantry',
            'attack' => 40,
            'defense' => 55,
            'siege_role' => 'corps',
            'phase1_eff' => 0.7,
            'phase2_eff' => 0.8,
            'phase3_eff' => 1.0,
        ],
        'arquebusier' => [
            'name' => 'Arquebusiers Tanegashima',
            'category' => 'ranged',
            'attack' => 65,
            'defense' => 60,
            'siege_role' => 'covering_fire',
            'phase1_eff' => 1.1,
            'phase2_eff' => 1.15,
            'phase3_eff' => 0.85,
        ],
        'archer' => [
            'name' => 'Archers Yumi d\'Élite',
            'category' => 'ranged',
            'attack' => 55,
            'defense' => 50,
            'siege_role' => 'harassment',
            'phase1_eff' => 1.2,
            'phase2_eff' => 1.1,
            'phase3_eff' => 0.8,
        ],
        'samurai' => [
            'name' => 'Samouraïs au Katana',
            'category' => 'infantry_elite',
            'attack' => 85,
            'defense' => 75,
            'siege_role' => 'breach',
            'phase1_eff' => 0.8,
            'phase2_eff' => 1.1,
            'phase3_eff' => 1.4, // Redoutables au corps-à-corps dans le donjon
        ],
        'cavalry' => [
            'name' => 'Cavalerie de Choc Akazonae',
            'category' => 'cavalry',
            'attack' => 95,
            'defense' => 60,
            'siege_role' => 'charge',
            'phase1_eff' => 0.9,
            'phase2_eff' => 0.2, // Ne grimpe pas aux murs
            'phase3_eff' => 0.1, // Inopérante dans les étages du donjon
        ],
        'shinobi' => [
            'name' => 'Ninjas Shinobi (Sape & Escalade)',
            'category' => 'special_forces',
            'attack' => 110,
            'defense' => 45,
            'siege_role' => 'infiltration',
            'phase1_eff' => 1.3, // Ouverture des poternes
            'phase2_eff' => 1.6, // Maîtres de l'escalade d'ishigaki
            'phase3_eff' => 1.25,
        ],
        'hatamoto' => [
            'name' => 'Garde d\'Honneur Hatamoto',
            'category' => 'infantry_heavy',
            'attack' => 210,
            'defense' => 270,
            'siege_role' => 'elite_guard',
            'phase1_eff' => 0.9,
            'phase2_eff' => 1.0,
            'phase3_eff' => 1.5,
        ],
        'ram' => [
            'name' => 'Bélier Blindé de Siège',
            'category' => 'siege_engine',
            'attack' => 400,
            'defense' => 500,
            'siege_role' => 'gate_breaker',
            'phase1_eff' => 2.5, // Spécialiste briseur de portes
            'phase2_eff' => 0.1,
            'phase3_eff' => 0.0,
        ],
        'catapult' => [
            'name' => 'Catapulte Ôzutsu / Tour de Siège',
            'category' => 'siege_engine',
            'attack' => 800,
            'defense' => 350,
            'siege_role' => 'wall_breaker',
            'phase1_eff' => 1.5,
            'phase2_eff' => 2.2, // Pilonnage des remparts ishigaki
            'phase3_eff' => 0.1,
        ],
    ];

    // Modificateurs de Météo
    public const WEATHER_MODIFIERS = [
        'clear' => [
            'name' => 'Ciel Clair & Ensoleillé',
            'icon' => 'fa-sun text-warning',
            'att_mult' => 1.0,
            'def_mult' => 1.0,
            'tanegashima_mult' => 1.0,
            'shinobi_mult' => 1.0,
            'desc' => 'Conditions idéales. Aucun modificateur appliqué.',
        ],
        'mist' => [
            'name' => 'Mer de Brume / Brouillard d\'Automne',
            'icon' => 'fa-smog text-secondary',
            'att_mult' => 0.9,
            'def_mult' => 0.9,
            'tanegashima_mult' => 0.8,
            'shinobi_mult' => 1.35, // Camouflage naturel
            'desc' => 'Visibilité réduite pour les tireurs (-20%), infiltration shinobi favorisée (+35%).',
        ],
        'rain' => [
            'name' => 'Pluie Battante & Averse Estivale',
            'icon' => 'fa-cloud-showers-heavy text-info',
            'att_mult' => 0.85,
            'def_mult' => 0.95,
            'tanegashima_mult' => 0.55, // Mèches humides
            'shinobi_mult' => 1.1,
            'desc' => 'Arquebuses Tanegashima fortement pénalisées (-45%), sol boueux.',
        ],
        'snow' => [
            'name' => 'Tempête de Neige Boréale (Yuki)',
            'icon' => 'fa-snowflake text-primary',
            'att_mult' => 0.75,
            'def_mult' => 1.10, // Le défenseur connaît le terrain
            'tanegashima_mult' => 0.70,
            'shinobi_mult' => 0.85, // Traces dans la neige
            'desc' => 'Froid glacial, mobilité ralentie de -25%, le donjon bénéficie d\'un retranchement accru.',
        ],
        'night' => [
            'name' => 'Nuit Sans Lune (Raid Nocturne)',
            'icon' => 'fa-moon text-indigo',
            'att_mult' => 1.15, // Effet de surprise initial
            'def_mult' => 0.85,
            'tanegashima_mult' => 0.75,
            'shinobi_mult' => 1.50, // Heure de prédilection des ninjas
            'desc' => 'Effet de surprise majeur (+15% att), panique de la garnison et ninjas décuplés (+50%).',
        ],
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->castleEngine = new CastleEngine();
    }

    /**
     * Récupère la liste enrichie des 12 châteaux avec leurs métadonnées de siège
     */
    public function getCastlesList(): array {
        $castles = $this->castleEngine->getAllCastles();
        $enriched = [];

        foreach ($castles as $c) {
            $code = $c['code'];
            $topo = self::CASTLE_TOPOGRAPHY[$code] ?? [
                'type' => 'hirayamajiro',
                'type_name' => 'Château de Colline',
                'altitude_m' => 30,
                'gate_layers' => 2,
                'wall_height_m' => 18,
                'has_water_moat' => true,
                'moat_depth_m' => 3,
                'special_mechanic' => 'Forteresse féodale standard.',
                'cavalry_allowed' => true,
                'cavalry_penalty' => 0.1,
                'siege_weapon_eff' => 0.8,
                'climb_difficulty' => 1.2,
            ];

            // Image
            $imgFile = !empty($c['image']) ? $c['image'] : ($code . '.jpg');
            $imgDisk = __DIR__ . '/../public/assets/castles/' . $imgFile;
            $hasImg = file_exists($imgDisk);
            $imgSrc = $hasImg ? ('/public/assets/castles/' . $imgFile) : '/public/assets/shogun_castle_city_bg.jpg';

            $enriched[$code] = array_merge($c, [
                'topography' => $topo,
                'image_src' => $imgSrc,
                'has_image' => $hasImg
            ]);
        }

        return $enriched;
    }

    /**
     * Calcule la garnison ancestrale recommandée selon le château
     */
    public function getAncestralGarrison(string $castleCode): array {
        $topo = self::CASTLE_TOPOGRAPHY[$castleCode] ?? self::CASTLE_TOPOGRAPHY['himeji'];
        $baseMultiplier = match($topo['type']) {
            'yamajiro' => 0.85, // Moins de troupes, mais terrain imprenable
            'mizujiro' => 0.95,
            'hirajiro' => 1.20, // Plus de troupes car plaine ouverte
            default => 1.00
        };

        return [
            'ashigaru'    => (int)round(250 * $baseMultiplier),
            'archer'      => (int)round(120 * $baseMultiplier),
            'arquebusier' => (int)round(80 * $baseMultiplier),
            'samurai'     => (int)round(60 * $baseMultiplier),
            'cavalry'     => $topo['cavalry_allowed'] ? (int)round(20 * $baseMultiplier) : 0,
            'shinobi'     => (int)round(15 * $baseMultiplier),
            'hatamoto'    => (int)round(10 * $baseMultiplier),
        ];
    }

    /**
     * Préréglages de siège emblématiques du Sengoku
     */
    public function getHistoricalPresets(): array {
        return [
            'himeji_grand_siege' => [
                'id' => 'himeji_grand_siege',
                'title' => 'Le Grand Siège d\'Himeji (1580)',
                'subtitle' => 'L\'Armée d\'Oda à l\'assaut du Héron Blanc',
                'castle_code' => 'himeji',
                'weather' => 'clear',
                'wall_level' => 18,
                'tactics' => 'frontal_assault',
                'att_clan' => 'terran',
                'att_troops' => [
                    'ashigaru' => 500,
                    'archer' => 150,
                    'arquebusier' => 200,
                    'samurai' => 120,
                    'cavalry' => 60,
                    'shinobi' => 25,
                    'hatamoto' => 20,
                    'ram' => 10,
                    'catapult' => 6,
                ],
                'def_clan' => 'aethelis',
                'def_troops' => [
                    'ashigaru' => 300,
                    'archer' => 140,
                    'arquebusier' => 100,
                    'samurai' => 80,
                    'cavalry' => 30,
                    'shinobi' => 20,
                    'hatamoto' => 15,
                ]
            ],
            'bitchu_mountain_mist' => [
                'id' => 'bitchu_mountain_mist',
                'title' => 'L\'Assaut Céleste de Bitchū Matsuyama',
                'subtitle' => 'Escalade à 430m dans une mer de brume',
                'castle_code' => 'bitchu_matsuyama',
                'weather' => 'mist',
                'wall_level' => 14,
                'tactics' => 'shinobi_infiltration',
                'att_clan' => 'terran',
                'att_troops' => [
                    'ashigaru' => 350,
                    'archer' => 100,
                    'arquebusier' => 80,
                    'samurai' => 90,
                    'cavalry' => 0, // Impossible en montagne
                    'shinobi' => 60, // Forte infiltration
                    'hatamoto' => 10,
                    'ram' => 2,
                    'catapult' => 1,
                ],
                'def_clan' => 'neutral',
                'def_troops' => [
                    'ashigaru' => 180,
                    'archer' => 90,
                    'arquebusier' => 60,
                    'samurai' => 50,
                    'cavalry' => 0,
                    'shinobi' => 15,
                    'hatamoto' => 10,
                ]
            ],
            'hikone_red_devils' => [
                'id' => 'hikone_red_devils',
                'title' => 'Le Choc des Diables Rouges de Hikone',
                'subtitle' => 'Charge de cavalerie Ii et pont basculant',
                'castle_code' => 'hikone',
                'weather' => 'clear',
                'wall_level' => 15,
                'tactics' => 'artillery_bombardment',
                'att_clan' => 'vorash',
                'att_troops' => [
                    'ashigaru' => 400,
                    'archer' => 120,
                    'arquebusier' => 150,
                    'samurai' => 100,
                    'cavalry' => 120,
                    'shinobi' => 20,
                    'hatamoto' => 15,
                    'ram' => 6,
                    'catapult' => 8,
                ],
                'def_clan' => 'aethelis',
                'def_troops' => [
                    'ashigaru' => 220,
                    'archer' => 110,
                    'arquebusier' => 80,
                    'samurai' => 70,
                    'cavalry' => 50, // Diables Rouges
                    'shinobi' => 15,
                    'hatamoto' => 15,
                ]
            ],
            'matsumoto_night_raid' => [
                'id' => 'matsumoto_night_raid',
                'title' => 'L\'Infiltration Nocturne de Matsumoto',
                'subtitle' => 'Traversée des douves du Corbeau sous la lune',
                'castle_code' => 'matsumoto',
                'weather' => 'night',
                'wall_level' => 16,
                'tactics' => 'shinobi_infiltration',
                'att_clan' => 'vorash',
                'att_troops' => [
                    'ashigaru' => 300,
                    'archer' => 90,
                    'arquebusier' => 70,
                    'samurai' => 110,
                    'cavalry' => 20,
                    'shinobi' => 75,
                    'hatamoto' => 15,
                    'ram' => 4,
                    'catapult' => 3,
                ],
                'def_clan' => 'terran',
                'def_troops' => [
                    'ashigaru' => 280,
                    'archer' => 130,
                    'arquebusier' => 100,
                    'samurai' => 60,
                    'cavalry' => 15,
                    'shinobi' => 20,
                    'hatamoto' => 10,
                ]
            ],
            'uwajima_naval_trap' => [
                'id' => 'uwajima_naval_trap',
                'title' => 'Le Piège Pentagonale d\'Uwajima',
                'subtitle' => 'Débarquement amphibie contre le plan de Takatora',
                'castle_code' => 'uwajima',
                'weather' => 'rain',
                'wall_level' => 13,
                'tactics' => 'frontal_assault',
                'att_clan' => 'terran',
                'att_troops' => [
                    'ashigaru' => 420,
                    'archer' => 140,
                    'arquebusier' => 90,
                    'samurai' => 85,
                    'cavalry' => 25,
                    'shinobi' => 30,
                    'hatamoto' => 12,
                    'ram' => 5,
                    'catapult' => 4,
                ],
                'def_clan' => 'aethelis',
                'def_troops' => [
                    'ashigaru' => 210,
                    'archer' => 95,
                    'arquebusier' => 75,
                    'samurai' => 55,
                    'cavalry' => 15,
                    'shinobi' => 15,
                    'hatamoto' => 10,
                ]
            ],
        ];
    }

    /**
     * Exécute une simulation unitaire de siège en 3 échelons
     */
    public function simulateSiege(array $config): array {
        $castleCode = $config['castle_code'] ?? 'himeji';
        $castles = $this->getCastlesList();
        $castle = $castles[$castleCode] ?? $castles['himeji'];
        $topo = $castle['topography'];

        $weatherKey = $config['weather'] ?? 'clear';
        $weather = self::WEATHER_MODIFIERS[$weatherKey] ?? self::WEATHER_MODIFIERS['clear'];

        $wallLevel = max(0, min(20, (int)($config['wall_level'] ?? 10)));
        $tactics = $config['tactics'] ?? 'frontal_assault';
        $attClan = $config['att_clan'] ?? 'terran';
        $defClan = $config['def_clan'] ?? 'aethelis';

        $attTroops = $config['att_troops'] ?? [];
        $defTroops = $config['def_troops'] ?? [];

        // Application de la restriction de cavalerie si Yamajiro / falaise
        if (!$topo['cavalry_allowed'] && !empty($attTroops['cavalry'])) {
            $attTroops['cavalry'] = 0; // Impossible d'acheminer des chevaux
        }
        if (!$topo['cavalry_allowed'] && !empty($defTroops['cavalry'])) {
            $defTroops['cavalry'] = 0;
        }

        // Modificateurs de doctrine tactique
        $tacticsMod = match($tactics) {
            'artillery_bombardment' => ['phase1_mult' => 1.30, 'phase2_mult' => 1.25, 'phase3_mult' => 0.85, 'losses_att' => 0.80],
            'shinobi_infiltration'  => ['phase1_mult' => 1.20, 'phase2_mult' => 1.40, 'phase3_mult' => 1.10, 'losses_att' => 0.90],
            'attrition_siege'       => ['phase1_mult' => 1.10, 'phase2_mult' => 1.05, 'phase3_mult' => 1.00, 'losses_att' => 0.70],
            default                 => ['phase1_mult' => 1.15, 'phase2_mult' => 1.05, 'phase3_mult' => 1.15, 'losses_att' => 1.10], // frontal_assault
        };

        // Calcul des PV initiaux de la Muraille et du Donjon
        $wallBaseHP = 4000 + ($wallLevel * 800);
        if ($castleCode === 'marugame') {
            $wallBaseHP = (int)round($wallBaseHP * 1.50); // Murailles géantes de 60m
        }
        $wallCurrentHP = $wallBaseHP;

        $tenshuBaseHP = 15000 + ($wallLevel * 1200);
        $tenshuCurrentHP = $tenshuBaseHP;

        // États des 3 phases
        $phaseLogs = [];
        $attCurrent = $attTroops;
        $defCurrent = $defTroops;

        // ══════════════════════════════════════════════════════════════════
        // ÉCHELON 1 : DOUVES & BARBACANES EXTÉRIEURES (ŌTEMON)
        // ══════════════════════════════════════════════════════════════════
        $p1 = $this->resolvePhase1(
            $castle, $weather, $tacticsMod, $wallLevel,
            $attClan, $attCurrent, $defClan, $defCurrent, $wallCurrentHP
        );
        $phaseLogs[] = $p1['log'];
        $attCurrent = $p1['att_survivors'];
        $defCurrent = $p1['def_survivors'];
        $wallCurrentHP = $p1['wall_hp_remaining'];

        if (!$p1['breached']) {
            return $this->formatSiegeResult(
                'defeat_phase1', "L'assaut a été écrasé devant les douves et portes extérieures du château.",
                $castle, $weather, $phaseLogs, 1, $attTroops, $attCurrent, $defTroops, $defCurrent,
                $wallBaseHP, $wallCurrentHP, $tenshuBaseHP, $tenshuCurrentHP
            );
        }

        // ══════════════════════════════════════════════════════════════════
        // ÉCHELON 2 : L'ASCENSION DES MURAILLES ISHIGAKI & REMPARTS
        // ══════════════════════════════════════════════════════════════════
        $p2 = $this->resolvePhase2(
            $castle, $weather, $tacticsMod, $wallLevel,
            $attClan, $attCurrent, $defClan, $defCurrent, $wallCurrentHP
        );
        $phaseLogs[] = $p2['log'];
        $attCurrent = $p2['att_survivors'];
        $defCurrent = $p2['def_survivors'];
        $wallCurrentHP = $p2['wall_hp_remaining'];

        if (!$p2['breached']) {
            return $this->formatSiegeResult(
                'defeat_phase2', "Les assaillants ont été repoussés avec de lourdes pertes au pied des murailles Ishigaki.",
                $castle, $weather, $phaseLogs, 2, $attTroops, $attCurrent, $defTroops, $defCurrent,
                $wallBaseHP, $wallCurrentHP, $tenshuBaseHP, $tenshuCurrentHP
            );
        }

        // ══════════════════════════════════════════════════════════════════
        // ÉCHELON 3 : LE COMBAT DANS LE DONJON TENSHU & CAPTURE
        // ══════════════════════════════════════════════════════════════════
        $p3 = $this->resolvePhase3(
            $castle, $weather, $tacticsMod, $wallLevel,
            $attClan, $attCurrent, $defClan, $defCurrent, $tenshuCurrentHP
        );
        $phaseLogs[] = $p3['log'];
        $attCurrent = $p3['att_survivors'];
        $defCurrent = $p3['def_survivors'];
        $tenshuCurrentHP = $p3['tenshu_hp_remaining'];

        if ($p3['castle_captured']) {
            return $this->formatSiegeResult(
                'victory_conquest', "VICTOIRE TOTALE ! Le donjon Tenshu est tombé, le drapeau du clan flotte sur le château et la Relique Antique est capturée !",
                $castle, $weather, $phaseLogs, 3, $attTroops, $attCurrent, $defTroops, $defCurrent,
                $wallBaseHP, $wallCurrentHP, $tenshuBaseHP, $tenshuCurrentHP, true
            );
        } else {
            return $this->formatSiegeResult(
                'defeat_phase3', "Échec de l'assaut final dans les étages du Tenshu. La garnison d'élite a héroïquement protégé le sanctuaire.",
                $castle, $weather, $phaseLogs, 3, $attTroops, $attCurrent, $defTroops, $defCurrent,
                $wallBaseHP, $wallCurrentHP, $tenshuBaseHP, $tenshuCurrentHP, false
            );
        }
    }

    /**
     * Résolution Échelon 1 (Douves & Portes Ōtemon)
     */
    private function resolvePhase1(
        array $castle, array $weather, array $tacticsMod, int $wallLevel,
        string $attClan, array $attTroops, string $defClan, array $defTroops, int $wallHP
    ): array {
        $topo = $castle['topography'];
        $attPower = 0;
        $defPower = 0;

        foreach ($attTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            $eff = $stat['phase1_eff'] * ($stat['attack'] * $count);
            if ($type === 'ram') {
                $eff *= $topo['siege_weapon_eff'];
                if ($castle['code'] === 'hikone') $eff *= 0.60; // Pont basculant
            }
            if ($type === 'arquebusier') {
                $eff *= $weather['tanegashima_mult'];
            }
            if ($type === 'shinobi') {
                $eff *= $weather['shinobi_mult'];
            }
            $attPower += $eff;
        }

        foreach ($defTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            $eff = $stat['phase1_eff'] * ($stat['defense'] * $count);
            if ($type === 'archer' || $type === 'arquebusier') {
                $eff *= 1.25; // Tir depuis les créneaux
            }
            $defPower += $eff;
        }

        // Bonus défense du château et douves
        $gateResistance = 1500 * $topo['gate_layers'];
        if ($topo['has_water_moat']) {
            $gateResistance += 1000 * $topo['moat_depth_m'];
            $attPower *= 0.85; // Malus de passage d'eau
        }
        $defTotal = $defPower + $gateResistance;

        $attPowerTotal = $attPower * $tacticsMod['phase1_mult'] * $weather['att_mult'];
        if ($attClan === 'terran') $attPowerTotal *= 1.10; // Bonus Oda arquebuses

        $breached = $attPowerTotal >= ($defTotal * 0.70);
        $damageToWall = (int)min($wallHP, round($attPowerTotal * 0.35));
        $wallRemaining = max(0, $wallHP - $damageToWall);

        // Pertes subies
        $attLossRate = min(0.40, max(0.08, ($defTotal / max(1, $attPowerTotal)) * 0.18)) * $tacticsMod['losses_att'];
        $defLossRate = min(0.35, max(0.05, ($attPowerTotal / max(1, $defTotal)) * 0.15));

        $attSurvivors = $this->applyLosses($attTroops, $attLossRate, ['ram' => 0.25]);
        $defSurvivors = $this->applyLosses($defTroops, $defLossRate);

        return [
            'breached' => $breached,
            'att_survivors' => $attSurvivors,
            'def_survivors' => $defSurvivors,
            'wall_hp_remaining' => $wallRemaining,
            'log' => [
                'phase' => 1,
                'title' => 'Échelon 1 : Franchissement des Douves & Rupture des Portes Ōtemon',
                'breached' => $breached,
                'att_power' => (int)round($attPowerTotal),
                'def_power' => (int)round($defTotal),
                'damage_to_wall' => $damageToWall,
                'wall_hp_remaining' => $wallRemaining,
                'att_losses_pct' => round($attLossRate * 100, 1),
                'def_losses_pct' => round($defLossRate * 100, 1),
                'message' => $breached 
                    ? "Les béliers et sapeurs ont brisé les ferrures des portes extérieures ! Les troupes s'engouffrent vers les remparts."
                    : "L'assaut a échoué face aux tirs de meurtrières et à la profondeur des douves. Les portes restent hermétiques."
            ]
        ];
    }

    /**
     * Résolution Échelon 2 (Murailles Ishigaki & Remparts)
     */
    private function resolvePhase2(
        array $castle, array $weather, array $tacticsMod, int $wallLevel,
        string $attClan, array $attTroops, string $defClan, array $defTroops, int $wallHP
    ): array {
        $topo = $castle['topography'];
        $attPower = 0;
        $defPower = 0;

        foreach ($attTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            $eff = $stat['phase2_eff'] * ($stat['attack'] * $count);
            if ($type === 'cavalry') {
                $eff *= (1 - $topo['cavalry_penalty']);
            }
            if ($type === 'shinobi') {
                $eff *= $weather['shinobi_mult'];
                if ($castle['code'] === 'kochi') $eff *= 0.50; // Pointes Shinobigaeshi
            }
            if ($type === 'catapult') {
                $eff *= $topo['siege_weapon_eff'];
            }
            $attPower += $eff;
        }

        foreach ($defTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            $eff = $stat['phase2_eff'] * ($stat['defense'] * $count);
            $defPower += $eff;
        }

        // Difficulté d'escalade des murailles
        $wallResistance = ($wallHP * 0.40) * $topo['climb_difficulty'];
        $defTotal = ($defPower + $wallResistance) * $weather['def_mult'];
        if ($defClan === 'aethelis') $defTotal *= 1.15; // Bonus Tokugawa

        $attPowerTotal = $attPower * $tacticsMod['phase2_mult'] * $weather['att_mult'];
        $breached = $attPowerTotal >= ($defTotal * 0.75);

        $damageToWall = (int)min($wallHP, round($attPowerTotal * 0.40));
        $wallRemaining = max(0, $wallHP - $damageToWall);

        $attLossRate = min(0.50, max(0.12, ($defTotal / max(1, $attPowerTotal)) * 0.22)) * $tacticsMod['losses_att'];
        $defLossRate = min(0.40, max(0.08, ($attPowerTotal / max(1, $defTotal)) * 0.18));

        $attSurvivors = $this->applyLosses($attTroops, $attLossRate, ['catapult' => 0.15]);
        $defSurvivors = $this->applyLosses($defTroops, $defLossRate);

        return [
            'breached' => $breached,
            'att_survivors' => $attSurvivors,
            'def_survivors' => $defSurvivors,
            'wall_hp_remaining' => $wallRemaining,
            'log' => [
                'phase' => 2,
                'title' => 'Échelon 2 : L\'Ascension des Murailles Ishigaki & Remparts',
                'breached' => $breached,
                'att_power' => (int)round($attPowerTotal),
                'def_power' => (int)round($defTotal),
                'damage_to_wall' => $damageToWall,
                'wall_hp_remaining' => $wallRemaining,
                'att_losses_pct' => round($attLossRate * 100, 1),
                'def_losses_pct' => round($defLossRate * 100, 1),
                'message' => $breached 
                    ? "Les échelles et grappins shinobi ont pris pied sur le sommet des remparts ! La cour Honmaru est ouverte."
                    : "Les lancers de pierres Ishi-otoshi et l'à-pic des remparts ont décimé les vagues d'assaut au pied des murailles."
            ]
        ];
    }

    /**
     * Résolution Échelon 3 (Combat au Donjon Tenshu)
     */
    private function resolvePhase3(
        array $castle, array $weather, array $tacticsMod, int $wallLevel,
        string $attClan, array $attTroops, string $defClan, array $defTroops, int $tenshuHP
    ): array {
        $topo = $castle['topography'];
        $attPower = 0;
        $defPower = 0;

        foreach ($attTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            // Dans le donjon, les armes de siège ne pénètrent pas, la cavalerie est exclue
            $eff = $stat['phase3_eff'] * ($stat['attack'] * $count);
            $attPower += $eff;
        }

        foreach ($defTroops as $type => $count) {
            if ($count <= 0) continue;
            $stat = self::TROOP_STATS[$type] ?? null;
            if (!$stat) continue;
            $eff = $stat['phase3_eff'] * ($stat['defense'] * $count);
            if ($type === 'samurai' || $type === 'hatamoto') {
                $eff *= 1.30; // Défense héroïque du Tenshu
            }
            $defPower += $eff;
        }

        $tenshuResistance = $tenshuHP * 0.35;
        $defTotal = $defPower + $tenshuResistance;

        $attPowerTotal = $attPower * $tacticsMod['phase3_mult'];
        if ($attClan === 'vorash') $attPowerTotal *= 1.15; // Furie Takeda au corps-à-corps

        $captured = $attPowerTotal > ($defTotal * 0.90);
        $damageToTenshu = (int)min($tenshuHP, round($attPowerTotal * 0.45));
        $tenshuRemaining = max(0, $tenshuHP - $damageToTenshu);

        $attLossRate = min(0.60, max(0.15, ($defTotal / max(1, $attPowerTotal)) * 0.28));
        $defLossRate = $captured ? 1.00 : min(0.70, ($attPowerTotal / max(1, $defTotal)) * 0.35);

        $attSurvivors = $this->applyLosses($attTroops, $attLossRate);
        $defSurvivors = $this->applyLosses($defTroops, $defLossRate);

        return [
            'castle_captured' => $captured,
            'att_survivors' => $attSurvivors,
            'def_survivors' => $defSurvivors,
            'tenshu_hp_remaining' => $tenshuRemaining,
            'log' => [
                'phase' => 3,
                'title' => 'Échelon 3 : Duel d\'Honneur & Prise du Donjon Tenshu',
                'captured' => $captured,
                'att_power' => (int)round($attPowerTotal),
                'def_power' => (int)round($defTotal),
                'damage_to_tenshu' => $damageToTenshu,
                'tenshu_hp_remaining' => $tenshuRemaining,
                'att_losses_pct' => round($attLossRate * 100, 1),
                'def_losses_pct' => round($defLossRate * 100, 1),
                'message' => $captured 
                    ? "Le dernier rempart de la garde Hatamoto est tombé ! Le donjon capitule et le trésor est conquis !"
                    : "Les défenseurs ont retranché le dernier escalier du donjon et repoussé l'assaut final dans un bain de sang."
            ]
        ];
    }

    /**
     * Applique les pourcentages de pertes à une composition d'armée
     */
    private function applyLosses(array $troops, float $lossRate, array $specificModifiers = []): array {
        $survivors = [];
        foreach ($troops as $type => $count) {
            $effectiveRate = $lossRate * ($specificModifiers[$type] ?? 1.0);
            $effectiveRate = min(1.0, max(0.0, $effectiveRate));
            $lost = (int)round($count * $effectiveRate);
            $survivors[$type] = max(0, $count - $lost);
        }
        return $survivors;
    }

    /**
     * Calcule le total de soldats d'une composition
     */
    private function countSoldiers(array $troops): int {
        $total = 0;
        foreach ($troops as $count) {
            $total += max(0, (int)$count);
        }
        return $total;
    }

    /**
     * Formate le bilan complet du siège unitaire
     */
    private function formatSiegeResult(
        string $status, string $message, array $castle, array $weather,
        array $phaseLogs, int $finalPhase, array $attInitial, array $attFinal,
        array $defInitial, array $defFinal, int $wallBase, int $wallRemaining,
        int $tenshuBase, int $tenshuRemaining, bool $conquered = false
    ): array {
        $attSoldiersInitial = $this->countSoldiers($attInitial);
        $attSoldiersFinal = $this->countSoldiers($attFinal);
        $attSoldiersLost = $attSoldiersInitial - $attSoldiersFinal;

        $defSoldiersInitial = $this->countSoldiers($defInitial);
        $defSoldiersFinal = $this->countSoldiers($defFinal);
        $defSoldiersLost = $defSoldiersInitial - $defSoldiersFinal;

        return [
            'success' => true,
            'status' => $status,
            'conquered' => $conquered,
            'final_phase' => $finalPhase,
            'summary_message' => $message,
            'castle' => [
                'code' => $castle['code'],
                'name' => $castle['name'],
                'kanji' => $castle['kanji'],
                'province' => $castle['province'],
                'relic_bonus' => $castle['relic_bonus'],
                'topography' => $castle['topography']
            ],
            'weather' => $weather,
            'wall_metrics' => [
                'initial_hp' => $wallBase,
                'remaining_hp' => $wallRemaining,
                'damage_dealt' => $wallBase - $wallRemaining,
                'integrity_pct' => round(($wallRemaining / max(1, $wallBase)) * 100, 1)
            ],
            'tenshu_metrics' => [
                'initial_hp' => $tenshuBase,
                'remaining_hp' => $tenshuRemaining,
                'damage_dealt' => $tenshuBase - $tenshuRemaining,
                'integrity_pct' => round(($tenshuRemaining / max(1, $tenshuBase)) * 100, 1)
            ],
            'casualties' => [
                'attacker' => [
                    'initial' => $attSoldiersInitial,
                    'survivors' => $attSoldiersFinal,
                    'lost' => $attSoldiersLost,
                    'loss_pct' => round(($attSoldiersLost / max(1, $attSoldiersInitial)) * 100, 1),
                    'breakdown_initial' => $attInitial,
                    'breakdown_survivors' => $attFinal,
                ],
                'defender' => [
                    'initial' => $defSoldiersInitial,
                    'survivors' => $defSoldiersFinal,
                    'lost' => $defSoldiersLost,
                    'loss_pct' => round(($defSoldiersLost / max(1, $defSoldiersInitial)) * 100, 1),
                    'breakdown_initial' => $defInitial,
                    'breakdown_survivors' => $defFinal,
                ]
            ],
            'phases' => $phaseLogs,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Exécute une simulation Monte-Carlo sur N itérations (par défaut 100)
     */
    public function runMonteCarlo(array $config, int $iterations = 100): array {
        $iterations = max(10, min(200, $iterations));
        $wins = 0;
        $phase1Fails = 0;
        $phase2Fails = 0;
        $phase3Fails = 0;

        $totalAttLossesPct = 0;
        $totalDefLossesPct = 0;
        $totalWallDamagePct = 0;
        $singleResults = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Légère variance aléatoire (±8%) sur les effectifs et tirs
            $variantConfig = $this->applyMonteCarloVariance($config);
            $res = $this->simulateSiege($variantConfig);

            if ($res['conquered']) {
                $wins++;
            } else {
                if ($res['final_phase'] === 1) $phase1Fails++;
                elseif ($res['final_phase'] === 2) $phase2Fails++;
                else $phase3Fails++;
            }

            $totalAttLossesPct += $res['casualties']['attacker']['loss_pct'];
            $totalDefLossesPct += $res['casualties']['defender']['loss_pct'];
            $totalWallDamagePct += (100 - $res['wall_metrics']['integrity_pct']);

            if ($i === 0) {
                $singleResults = $res; // Conserver la première exécution complète pour affichage détaillé
            }
        }

        $winRate = round(($wins / $iterations) * 100, 1);
        $lossRate = round(100 - $winRate, 1);

        return [
            'success' => true,
            'is_monte_carlo' => true,
            'iterations' => $iterations,
            'wins' => $wins,
            'losses' => $iterations - $wins,
            'win_rate' => $winRate,
            'loss_rate' => $lossRate,
            'failure_breakdown' => [
                'phase1_moat_pct' => round(($phase1Fails / $iterations) * 100, 1),
                'phase2_walls_pct' => round(($phase2Fails / $iterations) * 100, 1),
                'phase3_tenshu_pct' => round(($phase3Fails / $iterations) * 100, 1),
            ],
            'average_metrics' => [
                'att_loss_pct' => round($totalAttLossesPct / $iterations, 1),
                'def_loss_pct' => round($totalDefLossesPct / $iterations, 1),
                'wall_damage_pct' => round($totalWallDamagePct / $iterations, 1),
            ],
            'detailed_sample' => $singleResults,
            'config_analyzed' => [
                'castle_code' => $config['castle_code'] ?? 'himeji',
                'weather' => $config['weather'] ?? 'clear',
                'wall_level' => $config['wall_level'] ?? 10,
                'tactics' => $config['tactics'] ?? 'frontal_assault',
            ]
        ];
    }

    /**
     * Applique une variance aléatoire sur les troupes pour Monte-Carlo
     */
    private function applyMonteCarloVariance(array $config): array {
        $cfg = $config;
        foreach (['att_troops', 'def_troops'] as $key) {
            if (!empty($cfg[$key]) && is_array($cfg[$key])) {
                foreach ($cfg[$key] as $type => $qty) {
                    $variance = mt_rand(92, 108) / 100.0;
                    $cfg[$key][$type] = (int)round($qty * $variance);
                }
            }
        }
        return $cfg;
    }

    /**
     * Exporte un compte-rendu de siège en Markdown (pour GDD et documentation)
     */
    public function exportMarkdownReport(array $result): string {
        $c = $result['castle'] ?? [];
        $w = $result['weather'] ?? [];
        $cas = $result['casualties'] ?? [];
        $wall = $result['wall_metrics'] ?? [];
        $tenshu = $result['tenshu_metrics'] ?? [];

        $md = "# Rapport de Siège : " . ($c['name'] ?? 'Donjon Authentique') . " (" . ($c['kanji'] ?? '') . ")\n\n";
        $md .= "> **Généré par le Game Elevate Designer** &bull; Horodatage : " . ($result['timestamp'] ?? date('Y-m-d H:i:s')) . "\n";
        $md .= "> **Statut du Siège :** " . ($result['conquered'] ? 'VICTOIRE TOTALE DE L\'ASSAILLANT' : 'ÉCHEC DU SIÈGE') . "\n\n";

        $md .= "## 1. Caractéristiques de la Forteresse\n";
        $md .= "- **Province :** " . ($c['province'] ?? 'Inconnue') . "\n";
        $md .= "- **Typologie :** " . ($c['topography']['type_name'] ?? 'Hirayamajiro') . " (Altitude : " . ($c['topography']['altitude_m'] ?? 0) . " m)\n";
        $md .= "- **Condition Météorologique :** " . ($w['name'] ?? 'Clair') . "\n";
        $md .= "- **Spécificité Martiale :** " . ($c['topography']['special_mechanic'] ?? 'Aucune') . "\n";
        $md .= "- **Relique Impériale :** " . ($c['relic_bonus'] ?? 'Aucune') . "\n\n";

        $md .= "## 2. Bilan des Pertes & Attrition\n\n";
        $md .= "| Armée | Effectif Initial | Survivants | Pertes Subies | Taux d'Attrition |\n";
        $md .= "|---|---|---|---|---|\n";
        $md .= "| **Corps Assaillant** | " . ($cas['attacker']['initial'] ?? 0) . " | " . ($cas['attacker']['survivors'] ?? 0) . " | " . ($cas['attacker']['lost'] ?? 0) . " | " . ($cas['attacker']['loss_pct'] ?? 0) . "% |\n";
        $md .= "| **Garnison Défensive** | " . ($cas['defender']['initial'] ?? 0) . " | " . ($cas['defender']['survivors'] ?? 0) . " | " . ($cas['defender']['lost'] ?? 0) . " | " . ($cas['defender']['loss_pct'] ?? 0) . "% |\n\n";

        $md .= "## 3. Dégradation Structurelle\n";
        $md .= "- **Remparts Ishigaki :** " . ($wall['integrity_pct'] ?? 100) . "% restants (" . ($wall['remaining_hp'] ?? 0) . " / " . ($wall['initial_hp'] ?? 0) . " PV)\n";
        $md .= "- **Donjon Central Tenshu :** " . ($tenshu['integrity_pct'] ?? 100) . "% restants (" . ($tenshu['remaining_hp'] ?? 0) . " / " . ($tenshu['initial_hp'] ?? 0) . " PV)\n\n";

        $md .= "## 4. Chronique des 3 Échelons de Combat\n\n";
        if (!empty($result['phases']) && is_array($result['phases'])) {
            foreach ($result['phases'] as $p) {
                $md .= "### " . ($p['title'] ?? 'Échelon') . "\n";
                $md .= "- **Issue :** " . (!empty($p['breached']) || !empty($p['captured']) ? "SUCCÈS" : "ÉCHEC") . "\n";
                $md .= "- **Puissances Opposées :** Assaillants " . number_format($p['att_power'] ?? 0) . " vs Donjon " . number_format($p['def_power'] ?? 0) . "\n";
                $md .= "- **Pertes de la phase :** Assaillants " . ($p['att_losses_pct'] ?? 0) . "% &bull; Défenseurs " . ($p['def_losses_pct'] ?? 0) . "%\n";
                $md .= "- *" . ($p['message'] ?? '') . "*\n\n";
            }
        }

        return $md;
    }
}
