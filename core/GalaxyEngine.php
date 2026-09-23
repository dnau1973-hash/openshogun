<?php
/**
 * Moteur de Carte des Provinces et Navigation du Japon Féodal (OpenShogun)
 * Gère la génération procédurale du paysage (plaines, forêts, montagnes, lacs, collines)
 * et le découpage de l'archipel en 4 quadrants stratégiques.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class GalaxyEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Détermine de manière procédurale et déterministe le type de paysage naturel d'une coordonnée (X, Y)
     * Utilise un hachage spatial 2D pour garantir la persistance mathématique sans stockage SQL lourd.
     */
    public static function getTerrainType(int $x, int $y): array {
        // Hachage spatial 2D déterministe
        $seed = abs((int)(($x * 73856093) ^ ($y * 19349663))) % 1000;

        // Répartition paysagère inspirée du Japon féodal & Travian :
        // 0-599 (60%) : Plaines verdoyantes & rizières
        // 600-749 (15%) : Forêt de cèdres (Sugi) & bambouseraies
        // 750-869 (12%) : Montagnes escarpées & falaises
        // 870-949 (8%)  : Collines & vergers en terrasse
        // 950-999 (5%)  : Lacs paisibles & méandres de rivières
        if ($seed < 600) {
            return [
                'type' => 'plains',
                'name' => 'Plaines Fertiles',
                'desc' => 'Prairies verdoyantes et terres arables favorables au développement agricole.',
                'img' => '/public/assets/map/tile_plains.jpg?v=2'
            ];
        } elseif ($seed < 750) {
            return [
                'type' => 'forest',
                'name' => 'Forêt de Cèdres (Sugi)',
                'desc' => 'Bois denses de cèdres centenaires et bambouseraies sauvages.',
                'img' => '/public/assets/map/tile_forest.jpg?v=2'
            ];
        } elseif ($seed < 870) {
            return [
                'type' => 'mountain',
                'name' => 'Pics Rocheux & Montagnes',
                'desc' => 'Crêtes granitiques escarpées et falaises abruptes des monts de l\'archipel.',
                'img' => '/public/assets/map/tile_mountain.jpg?v=2'
            ];
        } elseif ($seed < 950) {
            return [
                'type' => 'hills',
                'name' => 'Collines & Coteaux',
                'desc' => 'Reliefs vallonnés parsemés de cultures en terrasses et vergers.',
                'img' => '/public/assets/map/tile_hills.jpg?v=2'
            ];
        } else {
            return [
                'type' => 'lake',
                'name' => 'Lac & Eaux Calmes',
                'desc' => 'Étendue d\'eau limpide bordée de roseaux et rivières sinueuses.',
                'img' => '/public/assets/map/tile_lake.jpg?v=2'
            ];
        }
    }

    /**
     * Identifie le quadrant géographique (Nord-Ouest, Nord-Est, Sud-Ouest, Sud-Est)
     * selon les coordonnées (X, Y)
     */
    public static function getQuadrant(int $x, int $y): array {
        if ($x === 0 && $y === 0) {
            return [
                'code' => 'KYOTO',
                'name' => 'Capitale Impériale (Kyoto)',
                'symbol' => '⛩️',
                'coords' => '[0 : 0]',
                'desc' => 'Centre spirituel et politique de l\'archipel.'
            ];
        }

        if ($x <= 0 && $y >= 0) {
            return [
                'code' => 'NO',
                'name' => 'Provinces du Nord-Ouest',
                'symbol' => '↖️',
                'coords' => '[- / +]',
                'desc' => 'Contrées montagneuses et forêts septentrionales.'
            ];
        } elseif ($x >= 0 && $y >= 0) {
            return [
                'code' => 'NE',
                'name' => 'Provinces du Nord-Est',
                'symbol' => '↗️',
                'coords' => '[+ / +]',
                'desc' => 'Plaines d\'Echigo et coteaux de Mutsu.'
            ];
        } elseif ($x <= 0 && $y <= 0) {
            return [
                'code' => 'SO',
                'name' => 'Provinces du Sud-Ouest',
                'symbol' => '↙️',
                'coords' => '[- / -]',
                'desc' => 'Fiefs côtiers du Shikoku et mers intérieures.'
            ];
        } else {
            return [
                'code' => 'SE',
                'name' => 'Provinces du Sud-Est',
                'symbol' => '↘️',
                'coords' => '[+ / -]',
                'desc' => 'Plaines du Tokaido et rivages de Mikawa.'
            ];
        }
    }

    /**
     * Récupère les secteurs de la carte spatiale autour d'un centre (cx, cy)
     * avec terrain procédural et donjons authentiques
     */
    public function getSectorMap(int $centerX, int $centerY, int $radius = 6): array {
        $minX = $centerX - $radius;
        $maxX = $centerX + $radius;
        $minY = $centerY - $radius;
        $maxY = $centerY + $radius;

        $stmt = $this->db->prepare("
            SELECT p.id as planet_id, p.name as planet_name, p.coord_x, p.coord_y, p.planet_type, 
                   p.user_id, u.username, u.faction, u.points, u.created_at, u.protection_until, u.is_bot, a.tag as alliance_tag
            FROM planets p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE p.coord_x BETWEEN ? AND ? AND p.coord_y BETWEEN ? AND ?
        ");
        $stmt->execute([$minX, $maxX, $minY, $maxY]);
        $planets = $stmt->fetchAll();

        // Indexer par "x:y"
        $gridMap = [];
        foreach ($planets as $p) {
            $isVillage = !empty($p['user_id']);
            $p['terrain_type'] = $isVillage ? 'village' : 'unoccupied';
            $p['terrain_name'] = $isVillage ? ('Fief de ' . ($p['username'] ?? 'Daimyō')) : 'Terres Libres';
            $p['terrain_img'] = $isVillage ? '/public/assets/map/tile_village.jpg?v=2' : '/public/assets/map/tile_plains.jpg?v=2';
            
            if ($isVillage) {
                $isProt = Auth::isUserProtected($p);
                $p['is_protected'] = $isProt ? 1 : 0;
                if ($isProt) {
                    $rem = Auth::getProtectionRemaining($p);
                    $p['protection_until'] = $rem['until_formatted'] ?? null;
                    $p['protection_remaining'] = $rem['formatted'] ?? null;
                } else {
                    $p['protection_until'] = null;
                    $p['protection_remaining'] = null;
                }
            } else {
                $p['is_protected'] = 0;
                $p['protection_until'] = null;
                $p['protection_remaining'] = null;
            }

            $gridMap[$p['coord_x'] . ':' . $p['coord_y']] = $p;
        }

        // Récupérer les donjons authentiques déployés dans ce secteur
        try {
            $stmtCastles = $this->db->prepare("
                SELECT id, code, name as castle_name, japanese_name, kanji, province, 
                       historical_builder, classification, icon, short_desc, coord_x, coord_y
                FROM authentic_castles
                WHERE is_spawned = 1 AND coord_x BETWEEN ? AND ? AND coord_y BETWEEN ? AND ?
            ");
            $stmtCastles->execute([$minX, $maxX, $minY, $maxY]);
            $castles = $stmtCastles->fetchAll();

            foreach ($castles as $c) {
                $k = $c['coord_x'] . ':' . $c['coord_y'];
                $castleData = [
                    'is_authentic_castle' => 1,
                    'castle_code' => $c['code'],
                    'castle_name' => $c['castle_name'],
                    'castle_kanji' => $c['kanji'],
                    'castle_classification' => $c['classification'],
                    'castle_province' => $c['province'],
                    'castle_builder' => $c['historical_builder'],
                    'castle_icon' => $c['icon'],
                    'terrain_type' => 'authentic_castle',
                    'terrain_name' => $c['castle_name'],
                    'terrain_img' => '/public/assets/map/tile_authentic_castle.jpg?v=2'
                ];

                if (isset($gridMap[$k])) {
                    $gridMap[$k] = array_merge($gridMap[$k], $castleData);
                } else {
                    $gridMap[$k] = array_merge([
                        'planet_id' => null,
                        'planet_name' => $c['castle_name'],
                        'coord_x' => (int)$c['coord_x'],
                        'coord_y' => (int)$c['coord_y'],
                        'planet_type' => 'authentic_castle',
                        'user_id' => null,
                        'username' => 'Garnison Antique',
                        'faction' => null,
                        'points' => 25000,
                        'alliance_tag' => 'TRÉSOR',
                    ], $castleData);
                }
            }
        } catch (Exception $e) {
            // Fallback silencieux
        }

        // Récupérer les oasis sauvages / occupées dans ce secteur
        try {
            require_once __DIR__ . '/OasisEngine.php';
            $oasisEngine = new OasisEngine();
            $oases = $oasisEngine->getOasesInSector($minX, $maxX, $minY, $maxY);

            foreach ($oases as $k => $o) {
                $isOccupied = !empty($o['owner_planet_id']);
                
                // Déterminer l'image de terrain de l'oasis selon son type
                $oasisImg = '/public/assets/map/tile_lake.jpg?v=2';
                if (strpos($o['oasis_type'], 'forest') !== false) {
                    $oasisImg = '/public/assets/map/tile_forest.jpg?v=2';
                } elseif (strpos($o['oasis_type'], 'mountain') !== false) {
                    $oasisImg = '/public/assets/map/tile_mountain.jpg?v=2';
                } elseif (strpos($o['oasis_type'], 'hills') !== false) {
                    $oasisImg = '/public/assets/map/tile_hills.jpg?v=2';
                }

                $bonusLabel = '';
                if ($o['bonus_rice'] > 0) $bonusLabel .= "+{$o['bonus_rice']}% 🌾 ";
                if ($o['bonus_wood'] > 0) $bonusLabel .= "+{$o['bonus_wood']}% 🪵 ";
                if ($o['bonus_stone'] > 0) $bonusLabel .= "+{$o['bonus_stone']}% 🪨 ";

                $oasisData = [
                    'is_oasis' => 1,
                    'oasis_id' => (int)$o['id'],
                    'oasis_type' => $o['oasis_type'],
                    'oasis_name' => $o['name'],
                    'bonus_wood' => (int)$o['bonus_wood'],
                    'bonus_stone' => (int)$o['bonus_stone'],
                    'bonus_rice' => (int)$o['bonus_rice'],
                    'bonus_label' => trim($bonusLabel),
                    'res_wood' => (int)$o['res_wood'],
                    'res_stone' => (int)$o['res_stone'],
                    'res_rice' => (int)$o['res_rice'],
                    'owner_planet_id' => $o['owner_planet_id'],
                    'owner_planet_name' => $o['owner_planet_name'],
                    'is_occupied' => $isOccupied ? 1 : 0,
                    'units' => $o['units'],
                    'terrain_type' => 'oasis',
                    'terrain_name' => $o['name'] . ' (' . trim($bonusLabel) . ')',
                    'terrain_img' => $oasisImg
                ];

                if (isset($gridMap[$k])) {
                    $gridMap[$k] = array_merge($gridMap[$k], $oasisData);
                } else {
                    $gridMap[$k] = array_merge([
                        'planet_id' => null,
                        'planet_name' => $o['name'],
                        'coord_x' => (int)$o['coord_x'],
                        'coord_y' => (int)$o['coord_y'],
                        'planet_type' => 'oasis',
                        'user_id' => null,
                        'username' => $isOccupied ? ($o['owner_username'] ?? 'Occupant') : 'Faune Sauvage',
                        'faction' => $o['owner_faction'] ?? null,
                        'points' => 0,
                        'alliance_tag' => 'OASIS',
                    ], $oasisData);
                }
            }
        } catch (Exception $e) {
            // Fallback silencieux
        }

        // Générer les terrains naturels pour toutes les cases du secteur
        $terrains = [];
        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                $k = $x . ':' . $y;
                if (!isset($gridMap[$k])) {
                    $terrains[$k] = self::getTerrainType($x, $y);
                } else {
                    $terrains[$k] = [
                        'type' => $gridMap[$k]['terrain_type'],
                        'name' => $gridMap[$k]['terrain_name'],
                        'img' => $gridMap[$k]['terrain_img']
                    ];
                }
            }
        }

        return [
            'center_x' => $centerX,
            'center_y' => $centerY,
            'radius' => $radius,
            'quadrant' => self::getQuadrant($centerX, $centerY),
            'bounds' => ['min_x' => $minX, 'max_x' => $maxX, 'min_y' => $minY, 'max_y' => $maxY],
            'planets' => $gridMap,
            'terrains' => $terrains
        ];
    }
}
