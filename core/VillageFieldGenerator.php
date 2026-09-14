<?php
/**
 * Générateur de Parcelles et Emplacements de Villages (Style Travian)
 * 
 * Gère la génération aléatoire des coordonnées des villages
 * ainsi que la répartition procédurale des 18 parcelles de ressources
 * selon différents archétypes de terroirs.
 */

class VillageFieldGenerator {

    /**
     * Archétypes de terroirs de villages (18 parcelles au total)
     */
    public const ARCHETYPES = [
        'balanced' => [
            'name' => 'Domaine Équilibré',
            'icon' => '⚖️',
            'desc' => 'Répartition harmonieuse des 4 ressources féodales',
            'weight' => 35,
            'counts' => [
                'metal_mine' => 5,       // Bûcherons
                'crystal_mine' => 5,     // Carrières
                'deuterium_synth' => 4,  // Rizières
                'solar_plant' => 4       // Sanctuaires
            ]
        ],
        'wood' => [
            'name' => 'Domaine Sylvestre',
            'icon' => '🪵',
            'desc' => 'Forêts denses de cèdres centenaires propices aux constructions en bois',
            'weight' => 15,
            'counts' => [
                'metal_mine' => 7,
                'crystal_mine' => 4,
                'deuterium_synth' => 4,
                'solar_plant' => 3
            ]
        ],
        'stone' => [
            'name' => 'Fief des Carrières',
            'icon' => '🪨',
            'desc' => 'Massifs rocheux fournissant une pierre abondante pour les murailles',
            'weight' => 15,
            'counts' => [
                'metal_mine' => 4,
                'crystal_mine' => 7,
                'deuterium_synth' => 4,
                'solar_plant' => 3
            ]
        ],
        'rice' => [
            'name' => 'Grenier Impérial',
            'icon' => '🌾',
            'desc' => 'Bassin fertile aux multiples terrasses rizicoles pour nourrir les armées',
            'weight' => 15,
            'counts' => [
                'metal_mine' => 3,
                'crystal_mine' => 3,
                'deuterium_synth' => 8,
                'solar_plant' => 4
            ]
        ],
        'temple' => [
            'name' => 'Terre Sacrée Shintō',
            'icon' => '⛩️',
            'desc' => 'Sanctuaires ancestraux procurant une sérénité et une spiritualité accrues',
            'weight' => 10,
            'counts' => [
                'metal_mine' => 4,
                'crystal_mine' => 4,
                'deuterium_synth' => 3,
                'solar_plant' => 7
            ]
        ],
        'super_rice' => [
            'name' => 'Grande Plaine des Rizières',
            'icon' => '🌾👑',
            'desc' => 'Terroir rare aux 11 rizières inondées, centre névralgique de ravitaillement',
            'weight' => 5,
            'counts' => [
                'metal_mine' => 2,
                'crystal_mine' => 2,
                'deuterium_synth' => 11,
                'solar_plant' => 3
            ]
        ],
        'mountain_forest' => [
            'name' => 'Forêt de Montagne',
            'icon' => '🌲🪨',
            'desc' => 'Hauts plateaux alliant grands massifs forestiers et riches carrières',
            'weight' => 3,
            'counts' => [
                'metal_mine' => 6,
                'crystal_mine' => 6,
                'deuterium_synth' => 3,
                'solar_plant' => 3
            ]
        ],
        'sanctuary' => [
            'name' => 'Haut Sanctuaire Impérial',
            'icon' => '⛩️✨',
            'desc' => 'Mont sacré dédié aux kamis avec 10 sanctuaires et temples érigés',
            'weight' => 2,
            'counts' => [
                'metal_mine' => 2,
                'crystal_mine' => 3,
                'deuterium_synth' => 3,
                'solar_plant' => 10
            ]
        ],
    ];

    /**
     * Retourne tous les archétypes disponibles
     */
    public static function getArchetypes(): array {
        return self::ARCHETYPES;
    }

    /**
     * Sélectionne un archétype au hasard selon les poids
     */
    public static function getRandomArchetypeKey(bool $isCapital = false): string {
        // Pour une capitale, privilégier un profil équilibré ou légèrement diversifié
        if ($isCapital) {
            $capPool = [
                'balanced' => 60,
                'wood' => 10,
                'stone' => 10,
                'rice' => 15,
                'temple' => 5
            ];
            $rand = rand(1, array_sum($capPool));
            $cur = 0;
            foreach ($capPool as $key => $weight) {
                $cur += $weight;
                if ($rand <= $cur) {
                    return $key;
                }
            }
            return 'balanced';
        }

        $totalWeight = 0;
        foreach (self::ARCHETYPES as $data) {
            $totalWeight += $data['weight'];
        }

        $rand = rand(1, $totalWeight);
        $cur = 0;
        foreach (self::ARCHETYPES as $key => $data) {
            $cur += $data['weight'];
            if ($rand <= $cur) {
                return $key;
            }
        }

        return 'balanced';
    }

    /**
     * Génère la répartition des 18 parcelles en mélangeant aléatoirement les emplacements
     * @return array [1 => 'metal_mine', 2 => 'crystal_mine', ..., 18 => 'solar_plant']
     */
    public static function generateSlotDistribution(string $archetypeKey): array {
        $archetype = self::ARCHETYPES[$archetypeKey] ?? self::ARCHETYPES['balanced'];
        
        $pool = [];
        foreach ($archetype['counts'] as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $pool[] = $type;
            }
        }

        // Mélanger aléatoirement les 18 ressources
        shuffle($pool);

        $slots = [];
        for ($i = 0; $i < 18; $i++) {
            $slots[$i + 1] = $pool[$i];
        }

        return $slots;
    }

    /**
     * Renseigne les 18 parcelles en base de données pour un village donné
     * @param PDO $db
     * @param int $planetId
     * @param string|null $archetypeKey (si null, choisi aléatoirement)
     * @param int $defaultLevel Niveau par défaut (ex: 0 pour nouvelles colonies, 1 pour capitales)
     * @param array $firstSlotLevelBoost Liste de types ou de slots à booster au niveau 1
     * @return array ['archetype' => string, 'distribution' => array]
     */
    public static function populatePlanetFields(
        PDO $db,
        int $planetId,
        ?string $archetypeKey = null,
        int $defaultLevel = 0,
        bool $boostFirstEachType = false
    ): array {
        if ($archetypeKey === null || !isset(self::ARCHETYPES[$archetypeKey])) {
            $archetypeKey = self::getRandomArchetypeKey(false);
        }

        $distribution = self::generateSlotDistribution($archetypeKey);

        $stmt = $db->prepare("
            INSERT INTO planet_fields (planet_id, field_slot, type, level) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE type = VALUES(type), level = VALUES(level)
        ");

        $boostedTypes = [];

        foreach ($distribution as $slot => $type) {
            $lvl = $defaultLevel;
            // Pour un démarrage fluide, booster la première ressource rencontrée de chaque type à niveau 1
            if ($boostFirstEachType && !isset($boostedTypes[$type])) {
                $lvl = max(1, $lvl);
                $boostedTypes[$type] = true;
            }

            $stmt->execute([$planetId, $slot, $type, $lvl]);
        }

        return [
            'archetype' => $archetypeKey,
            'archetype_name' => self::ARCHETYPES[$archetypeKey]['name'],
            'distribution' => $distribution
        ];
    }

    /**
     * Recherche des coordonnées (X, Y) libres de façon purement aléatoire
     * dans un rayon donné (par défaut 35, extensible si la carte se remplit)
     */
    public static function findRandomFreeCoordinates(PDO $db, int $radius = 35): array {
        $checkStmt = $db->prepare("SELECT id FROM planets WHERE coord_x = ? AND coord_y = ?");

        $attempts = 0;
        $curRadius = max(15, $radius);

        while ($attempts < 2000) {
            $attempts++;
            
            // Si trop de tentatives, élargir le rayon de recherche
            if ($attempts > 500 && $attempts % 200 === 0) {
                $curRadius += 10;
            }

            $x = rand(-$curRadius, $curRadius);
            $y = rand(-$curRadius, $curRadius);

            // Ne pas occuper le centre impérial absolu (0, 0)
            if ($x === 0 && $y === 0) {
                continue;
            }

            $checkStmt->execute([$x, $y]);
            if (!$checkStmt->fetch()) {
                return ['x' => $x, 'y' => $y];
            }
        }

        // Secours aléatoire très large
        return [
            'x' => rand(50, 100) * (rand(0, 1) ? 1 : -1),
            'y' => rand(50, 100) * (rand(0, 1) ? 1 : -1)
        ];
    }

    /**
     * Analyse une liste de parcelles et détecte le profil de terroir du village
     */
    public static function detectArchetype(array $fields): array {
        $counts = [
            'metal_mine' => 0,
            'crystal_mine' => 0,
            'deuterium_synth' => 0,
            'solar_plant' => 0
        ];

        foreach ($fields as $f) {
            $type = $f['type'] ?? '';
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        // Tenter une correspondance exacte avec nos archétypes
        foreach (self::ARCHETYPES as $key => $data) {
            $match = true;
            foreach ($data['counts'] as $t => $cnt) {
                if (($counts[$t] ?? 0) !== $cnt) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return [
                    'key' => $key,
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'desc' => $data['desc'],
                    'counts' => $counts
                ];
            }
        }

        // Si répartition hybride/inconnue, déterminer la dominante
        arsort($counts);
        $topType = array_key_first($counts);
        $topCount = $counts[$topType];

        if ($topCount >= 8 && $topType === 'deuterium_synth') {
            $name = 'Grenier Impérial';
            $icon = '🌾';
        } elseif ($topCount >= 7 && $topType === 'metal_mine') {
            $name = 'Domaine Sylvestre';
            $icon = '🪵';
        } elseif ($topCount >= 7 && $topType === 'crystal_mine') {
            $name = 'Fief des Carrières';
            $icon = '🪨';
        } elseif ($topCount >= 7 && $topType === 'solar_plant') {
            $name = 'Terre Sacrée Shintō';
            $icon = '⛩️';
        } else {
            $name = 'Domaine Rustique';
            $icon = '🌾';
        }

        return [
            'key' => 'custom',
            'name' => $name,
            'icon' => $icon,
            'desc' => "Terroir spécifique ({$counts['metal_mine']} Bois, {$counts['crystal_mine']} Pierre, {$counts['deuterium_synth']} Riz, {$counts['solar_plant']} Sanctuaires)",
            'counts' => $counts
        ];
    }
}

