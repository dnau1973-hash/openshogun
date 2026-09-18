<?php
/**
 * Constantes et équilibrage de jeu La Voie du Shogun - Époque Sengoku Jidai
 */

const GAME_NAME = 'La Voie du Shogun';
const GAME_TAGLINE = 'Chroniques Féodales du Sengoku Jidai';

// Définition des 3 grands clans féodaux du Japon
const FACTIONS = [
    'terran' => [
        'name' => 'Clan Oda',
        'subname' => 'L\'Ambition & la Discipline',
        'icon' => '🏯',
        'color' => '#3b82f6', // Bleu nuit & or
        'description' => 'Maîtres de l\'organisation et pionniers des armes à feu (Tanegashima). Capacité unique : double développement simultané (1 rizière/carrière rurale + 1 bâtiment urbain en même temps).',
        'special_ability' => 'Double développement simultané (parcelle rurale + bâtiment urbain)',
        'special_building' => 'Grand Atelier d\'Arquebuserie'
    ],
    'vorash' => [
        'name' => 'Clan Takeda',
        'subname' => 'La Furie de la Cavalerie Rouge',
        'icon' => '🐎',
        'color' => '#ef4444', // Rouge écarlate de Kai
        'description' => 'Seigneurs des plaines et de la légendaire cavalerie rouge (Akazonae). Troupes montées vives et redoutables, bonus de pillage pénétrant les réserves adverses.',
        'special_ability' => 'Cavalerie -20% temps d\'entraînement & +25% de butin en raid',
        'special_building' => 'Haras & Écuries Rouges de Choc'
    ],
    'aethelis' => [
        'name' => 'Clan Tokugawa',
        'subname' => 'La Patience & la Forteresse',
        'icon' => '⛩️',
        'color' => '#8b5cf6', // Pourpre impérial & vert pin
        'description' => 'Seigneurs stratèges réputés pour leur résilience et châteaux imprenables. Cachettes secrètes doublées, vitesse de marche supérieure et défenses fortifiées.',
        'special_ability' => 'Cachettes secrètes x2 & Vitesse de marche des troupes +20%',
        'special_building' => 'Réseau de Souterrains & Pièges Secrets'
    ]
];

// Configuration des 18 parcelles de ressources par domaine castral
// 5 Bûcherons (Bois), 5 Carrières (Pierre), 4 Rizières (Riz), 4 Sanctuaires Shintō (Honneur)
const FIELD_LAYOUT = [
    1 => 'metal_mine',
    2 => 'metal_mine',
    3 => 'metal_mine',
    4 => 'metal_mine',
    5 => 'metal_mine',
    6 => 'crystal_mine',
    7 => 'crystal_mine',
    8 => 'crystal_mine',
    9 => 'crystal_mine',
    10 => 'crystal_mine',
    11 => 'deuterium_synth',
    12 => 'deuterium_synth',
    13 => 'deuterium_synth',
    14 => 'deuterium_synth',
    15 => 'solar_plant',
    16 => 'solar_plant',
    17 => 'solar_plant',
    18 => 'solar_plant',
    19 => 'deuterium_synth',
];

// Configuration des emplacements urbains de la Cité Castrale (Slots 19 à 33, style Travian Dorf 2)
const CITY_SLOT_LAYOUT = [
    19 => 'hq',            // Tenshu (Donjon Castral sur terrasse haute)
    20 => 'storage',       // Entrepôt Bois & Pierre (près de la rive)
    21 => 'tank',          // Grenier à Riz Kura
    22 => 'barracks',      // Dojo Militaire & Garnison
    23 => 'shipyard',      // Atelier de Siège & Écuries
    24 => 'market',        // Marché Féodal & Caravanes
    25 => 'research_lab',  // Académie des Savoirs & Forge
    26 => 'radar',         // Tour de Guet Yagura (octogonale centrale)
    27 => 'quantum_vault', // Cachette Secrète Sous Terre
    28 => 'embassy',       // Pavillon Diplomatique des Clans
    29 => 'free_plot',     // Emplacement Libre / Terrain à Bâtir
    30 => 'free_plot',     // Emplacement Libre / Terrain à Bâtir
    31 => 'free_plot',     // Emplacement Libre / Terrain à Bâtir
    32 => 'free_plot',     // Emplacement Libre / Terrain à Bâtir
    33 => 'free_plot',     // Emplacement Libre / Terrain à Bâtir
    34 => 'wall',          // Muraille & Remparts de Cité (Enceinte fortifiée)
];

// Métadonnées des parcelles rurales du domaine
const FIELD_TYPES = [
    'metal_mine' => [
        'name' => 'Camp de Bûcherons',
        'res_name' => 'Bois de Cèdre',
        'icon' => '🪵',
        'description' => 'Exploitation forestière taillant les nobles cèdres des forêts montagneuses du fief. Le bois est la matière première indispensable pour dresser les charpentes de vos donjons, vos palissades fortifiées et fabriquer les arcs et armes d\'hast de vos bataillons.',
        'base_cost' => ['metal' => 60, 'crystal' => 15, 'deuterium' => 0],
        'cost_multiplier' => 1.5,
        'base_time' => 75,
        'base_prod' => 30, // Bois de Cèdre par heure
        'base_energy_cons' => 10,
    ],
    'crystal_mine' => [
        'name' => 'Carrière de Pierre',
        'res_name' => 'Pierre de Taille',
        'icon' => '🪨',
        'description' => 'Carrière à ciel ouvert extrayant les blocs de granit et roches volcaniques des coteaux. Les pierres taillées permettent d\'édifier les fondations cyclopéennes (Nozura-zumi) de vos remparts, vos fossés et vos forteresses imprenables.',
        'base_cost' => ['metal' => 48, 'crystal' => 24, 'deuterium' => 0],
        'cost_multiplier' => 1.6,
        'base_time' => 90,
        'base_prod' => 20, // Pierre de Taille par heure
        'base_energy_cons' => 12,
    ],
    'deuterium_synth' => [
        'name' => 'Rizière Inondée',
        'res_name' => 'Riz Impérial (Koku)',
        'icon' => '🌾',
        'description' => 'Vastes rizières aménagées en terrasses irriguées par les canaux fluviaux. Le riz est la véritable monnaie du Japon féodal (mesurée en Koku) : il nourrit votre population, entretient vos garnisons samouraïs et finance vos campagnes militaires.',
        'base_cost' => ['metal' => 225, 'crystal' => 75, 'deuterium' => 0],
        'cost_multiplier' => 1.5,
        'base_time' => 110,
        'base_prod' => 12, // Koku de Riz par heure
        'base_energy_cons' => 20,
    ],
    'solar_plant' => [
        'name' => 'Sanctuaire Shintō & Moulin',
        'res_name' => 'Ferveur & Sérénité',
        'icon' => '⛩️',
        'description' => 'Lieu sacré érigé sous les pins ancestraux avec torii vermillon et roue à aubes fluviale. Il honore les esprits tutélaires (Kami), maintenant l\'harmonie spirituelle et l\'énergie indispensable au rendement de toutes les parcelles du domaine.',
        'base_cost' => ['metal' => 75, 'crystal' => 30, 'deuterium' => 0],
        'cost_multiplier' => 1.5,
        'base_time' => 80,
        'base_prod' => 25, // Ferveur & Honneur
        'base_energy_cons' => 0,
    ]
];

// Bâtiments de la cité castrale fortifiée
const BUILDINGS = [
    'hq' => [
        'name' => 'Tenshu (Donjon Castral)',
        'icon' => '🏯',
        'tile_img' => 'tile_tenshu.png',
        'description' => 'Le donjon fortifié et palais du Daimyō. Réduit la durée de construction de tous les bâtiments urbains et parcelles rurales du fief.',
        'base_cost' => ['metal' => 100, 'crystal' => 80, 'deuterium' => 40],
        'cost_multiplier' => 1.5,
        'base_time' => 160,
        'max_level' => 20
    ],
    'storage' => [
        'name' => 'Entrepôt de Matériaux (Bois & Pierre)',
        'icon' => '🪵',
        'tile_img' => 'tile_storage.png',
        'description' => 'Augmente la capacité de stockage maximale de Bois de Cèdre et de Pierre de Taille.',
        'base_cost' => ['metal' => 120, 'crystal' => 60, 'deuterium' => 0],
        'cost_multiplier' => 1.5,
        'base_time' => 120,
        'max_level' => 20
    ],
    'tank' => [
        'name' => 'Grenier à Riz Fortifié (Kura)',
        'icon' => '🌾',
        'tile_img' => 'tile_tank.png',
        'description' => 'Augmente la capacité de stockage maximale des récoltes de riz (Koku).',
        'base_cost' => ['metal' => 100, 'crystal' => 100, 'deuterium' => 0],
        'cost_multiplier' => 1.5,
        'base_time' => 120,
        'max_level' => 20
    ],
    'shipyard' => [
        'name' => 'Atelier de Siège & Écuries',
        'icon' => '🐎',
        'tile_img' => 'tile_shipyard.png',
        'description' => 'Permet d\'élever la cavalerie de guerre, les convois de transport et de fabriquer béliers et trébuchets.',
        'base_cost' => ['metal' => 400, 'crystal' => 200, 'deuterium' => 100],
        'cost_multiplier' => 1.6,
        'base_time' => 240,
        'max_level' => 20
    ],
    'research_lab' => [
        'name' => 'Académie des Savoirs & Forge',
        'icon' => '📜',
        'tile_img' => 'tile_research_lab.png',
        'description' => 'Permet de perfectionner la métallurgie du tamahagane, l\'art de la guerre et les tactiques militaires.',
        'base_cost' => ['metal' => 200, 'crystal' => 400, 'deuterium' => 200],
        'cost_multiplier' => 1.6,
        'base_time' => 220,
        'max_level' => 20
    ],
    'radar' => [
        'name' => 'Tour de Guet Yagura & Feux d\'Alarme',
        'icon' => '🏮',
        'tile_img' => 'tile_radar.png',
        'description' => 'Surveille les vallées et détecte les armées et espions ennemis en marche vers votre fief.',
        'base_cost' => ['metal' => 150, 'crystal' => 250, 'deuterium' => 100],
        'cost_multiplier' => 1.5,
        'base_time' => 160,
        'max_level' => 15
    ],
    'quantum_vault' => [
        'name' => 'Cachette Secrète Sous Terre',
        'icon' => '🕳️',
        'tile_img' => 'tile_quantum_vault.png',
        'description' => 'Protège une réserve secrète de vivres et matériaux contre les pillages adverses (capacité doublée pour le Clan Tokugawa).',
        'base_cost' => ['metal' => 100, 'crystal' => 100, 'deuterium' => 50],
        'cost_multiplier' => 1.4,
        'base_time' => 100,
        'max_level' => 15
    ],
    'market' => [
        'name' => 'Marché Féodal & Caravanes',
        'icon' => '🏪',
        'tile_img' => 'tile_market.png',
        'description' => 'Permet d\'échanger des ressources avec les marchands itinérants et autres daimyōs provinciaux.',
        'base_cost' => ['metal' => 300, 'crystal' => 200, 'deuterium' => 150],
        'cost_multiplier' => 1.5,
        'base_time' => 160,
        'max_level' => 15
    ],
    'embassy' => [
        'name' => 'Pavillon Diplomatique des Clans',
        'icon' => '🎌',
        'tile_img' => 'tile_embassy.png',
        'description' => 'Permet de sceller ou rejoindre un pacte d\'alliance entre daimyōs.',
        'base_cost' => ['metal' => 180, 'crystal' => 130, 'deuterium' => 70],
        'cost_multiplier' => 1.5,
        'base_time' => 180,
        'max_level' => 10
    ],
    'barracks' => [
        'name' => 'Dojo & Quartier Militaire',
        'icon' => '🥋',
        'tile_img' => 'tile_barracks.png',
        'description' => 'Entraîne les fantassins Ashigarus, archers Yumi, arquebusiers et samouraïs d\'élite pour la défense et les conquêtes.',
        'base_cost' => ['metal' => 200, 'crystal' => 150, 'deuterium' => 50],
        'cost_multiplier' => 1.5,
        'base_time' => 180,
        'max_level' => 20
    ],
    'wall' => [
        'name' => 'Muraille & Remparts de Cité',
        'icon' => '🧱',
        'tile_img' => 'tile_wall.png',
        'description' => 'Enceinte fortifiée en pierre de taille, palissades en cèdre et douves protégeant le fief. Confère une défense structurelle de base et un puissant bonus défensif multiplicateur (+4% par niveau) à l\'ensemble des guerriers stationnés en garnison face aux assauts ennemis.',
        'base_cost' => ['metal' => 110, 'crystal' => 160, 'deuterium' => 90],
        'cost_multiplier' => 1.45,
        'base_time' => 130,
        'max_level' => 20
    ]
];
