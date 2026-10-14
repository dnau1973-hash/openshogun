<?php
declare(strict_types=1);

/**
 * Moteur des Castes Sociales Féodales (Shinōkōshō 士農工商)
 * Gère les 5 strates de la société d'OpenShogun :
 * 1. Nōmin / Hinin  : Paysans, Pêcheurs & Ouvriers de base
 * 2. Kōgiyō         : Artisans, Maçons & Bâtisseurs
 * 3. Shōnin         : Marchands, Intendants & Caravaniers
 * 4. Bushi / Gakusha: Samouraïs & Érudits de l'Académie
 * 5. Daimyō / Kuge  : Haute Noblesse & Shōgunat
 */
class SocialCasteEngine {
    // Définition des 5 castes féodales et de leurs exigences de consommation par habitant par heure
    public const CASTES = [
        1 => [
            'tier' => 1,
            'key' => 'nomin',
            'name' => 'Nōmin (Paysans & Ouvriers)',
            'short_name' => 'Paysans & Ouvriers',
            'kanji' => '農民',
            'icon' => 'fa-wheat-awn',
            'color' => '#16a34a',
            'badge' => 'success',
            'desc' => 'Base travailleuse du fief. Cultivent le riz, abattent les cèdres et exploitent les carrières.',
            'needs' => [
                'deuterium' => 0.04 // Riz brut de subsistance vitale
            ],
            'bonus_desc' => 'Production agricole et extraction de matières brutes.'
        ],
        2 => [
            'tier' => 2,
            'key' => 'kogiyo',
            'name' => 'Kōgiyō (Artisans & Bâtisseurs)',
            'short_name' => 'Artisans & Bâtisseurs',
            'kanji' => '工商',
            'icon' => 'fa-hammer',
            'color' => '#0284c7',
            'badge' => 'info',
            'desc' => 'Maîtres de la charpente kizukuri, de la taille de pierre et de la poterie fine.',
            'needs' => [
                'fish' => 0.02,     // Poisson frais des lacs
                'soybean' => 0.02  // Soja des cultures du terroir
            ],
            'bonus_desc' => 'Améliore la vitesse de construction et débloque les savoirs d\'ingénierie.'
        ],
        3 => [
            'tier' => 3,
            'key' => 'shonin',
            'name' => 'Shōnin (Marchands & Caravaniers)',
            'short_name' => 'Marchands & Négociants',
            'kanji' => '商人',
            'icon' => 'fa-scale-balanced',
            'color' => '#d97706',
            'badge' => 'warning',
            'desc' => 'Commerçants des marchés castraux gérant les caravanes et la circulation des richesses.',
            'needs' => [
                'rice_flour' => 0.02, // Farine de riz raffinée
                'tea' => 0.015        // Thé vert des coteaux
            ],
            'bonus_desc' => 'Augmente la rentabilité commerciale et l\'opulence du domaine.'
        ],
        4 => [
            'tier' => 4,
            'key' => 'gakusha',
            'name' => 'Gakusha & Bushi (Érudits & Samouraïs)',
            'short_name' => 'Érudits & Samouraïs',
            'kanji' => '武士',
            'icon' => 'fa-graduation-cap',
            'color' => '#7c3aed',
            'badge' => 'purple',
            'desc' => 'Scribes de l\'Académie des Savoirs et guerriers d\'honneur dédiés à la stratégie et aux arts martiaux.',
            'needs' => [
                'sake' => 0.015,       // Saké de riz de qualité
                'tea' => 0.02          // Thé de cérémonie
            ],
            'bonus_desc' => 'Accélère les recherches de l\'Académie et débloque les savoirs supérieurs (Poudre noire, Trempe).'
        ],
        5 => [
            'tier' => 5,
            'key' => 'daimyo',
            'name' => 'Daimyō & Kuge (Haute Noblesse)',
            'short_name' => 'Noblesse Castrale',
            'kanji' => '公家',
            'icon' => 'fa-crown',
            'color' => '#dc2626',
            'badge' => 'danger',
            'desc' => 'Lignée seigneuriale commandant le château Tenshu et les destinées de la province.',
            'needs' => [
                'sake' => 0.03,        // Saké impérial vieilli
                'rice_flour' => 0.03   // Mets nobles raffinés
            ],
            'bonus_desc' => 'Confère un prestige souverain et débloque les ultimes secrets militaires.'
        ]
    ];

    /**
     * Traite la consommation automatique des ressources supérieures lors du tick
     * et calcule la distribution des 5 castes de la population.
     *
     * @param array $planet Référence vers le tableau de données du fief
     * @param float $hours Nombre d'heures écoulées
     * @return array Bilan complet de la société féodale
     */
    public static function processSocialTick(array &$planet, float $hours): array {
        $totalPop = (int)($planet['population'] ?? 100);
        if ($totalPop <= 0) {
            $totalPop = 20;
            $planet['population'] = 20;
        }

        // Stocks actuels
        $stockRice    = max(0.0, (float)($planet['deuterium'] ?? 0));
        $stockFlour   = max(0.0, (float)($planet['rice_flour'] ?? 0));
        $stockFish    = max(0.0, (float)($planet['fish'] ?? 0));
        $stockSoy     = max(0.0, (float)($planet['soybean'] ?? 0));
        $stockTea     = max(0.0, (float)($planet['tea'] ?? 0));
        $stockSake    = max(0.0, (float)($planet['sake'] ?? 0));

        // Ratios cibles théoriques de composition sociale si toutes les ressources abondent
        // Caste 1: 50% max, Caste 2: 25%, Caste 3: 15%, Caste 4: 8%, Caste 5: 2%
        $targetPopCaste5 = (int)round($totalPop * 0.02);
        $targetPopCaste4 = (int)round($totalPop * 0.08);
        $targetPopCaste3 = (int)round($totalPop * 0.15);
        $targetPopCaste2 = (int)round($totalPop * 0.25);

        // 1. Évaluation et consommation pour la Caste 5 (Daimyō)
        $actualPopCaste5 = 0;
        if ($hours > 0 && $targetPopCaste5 > 0) {
            $reqSake5 = $targetPopCaste5 * self::CASTES[5]['needs']['sake'] * $hours;
            $reqFlour5 = $targetPopCaste5 * self::CASTES[5]['needs']['rice_flour'] * $hours;
            $ratio5 = 1.0;
            if ($reqSake5 > 0) $ratio5 = min($ratio5, $stockSake / $reqSake5);
            if ($reqFlour5 > 0) $ratio5 = min($ratio5, $stockFlour / $reqFlour5);
            $ratio5 = min(1.0, max(0.0, $ratio5));

            $actualPopCaste5 = (int)floor($targetPopCaste5 * $ratio5);
            if ($actualPopCaste5 > 0) {
                $usedSake5 = $actualPopCaste5 * self::CASTES[5]['needs']['sake'] * $hours;
                $usedFlour5 = $actualPopCaste5 * self::CASTES[5]['needs']['rice_flour'] * $hours;
                $stockSake -= min($stockSake, $usedSake5);
                $stockFlour -= min($stockFlour, $usedFlour5);
            }
        }

        // 2. Évaluation et consommation pour la Caste 4 (Érudits & Samouraïs)
        $actualPopCaste4 = 0;
        if ($hours > 0 && $targetPopCaste4 > 0) {
            $reqSake4 = $targetPopCaste4 * self::CASTES[4]['needs']['sake'] * $hours;
            $reqTea4 = $targetPopCaste4 * self::CASTES[4]['needs']['tea'] * $hours;
            $ratio4 = 1.0;
            if ($reqSake4 > 0) $ratio4 = min($ratio4, $stockSake / $reqSake4);
            if ($reqTea4 > 0) $ratio4 = min($ratio4, $stockTea / $reqTea4);
            $ratio4 = min(1.0, max(0.0, $ratio4));

            $actualPopCaste4 = (int)floor($targetPopCaste4 * $ratio4);
            if ($actualPopCaste4 > 0) {
                $usedSake4 = $actualPopCaste4 * self::CASTES[4]['needs']['sake'] * $hours;
                $usedTea4 = $actualPopCaste4 * self::CASTES[4]['needs']['tea'] * $hours;
                $stockSake -= min($stockSake, $usedSake4);
                $stockTea -= min($stockTea, $usedTea4);
            }
        }

        // 3. Évaluation et consommation pour la Caste 3 (Marchands)
        $actualPopCaste3 = 0;
        if ($hours > 0 && $targetPopCaste3 > 0) {
            $reqFlour3 = $targetPopCaste3 * self::CASTES[3]['needs']['rice_flour'] * $hours;
            $reqTea3 = $targetPopCaste3 * self::CASTES[3]['needs']['tea'] * $hours;
            $ratio3 = 1.0;
            if ($reqFlour3 > 0) $ratio3 = min($ratio3, $stockFlour / $reqFlour3);
            if ($reqTea3 > 0) $ratio3 = min($ratio3, $stockTea / $reqTea3);
            $ratio3 = min(1.0, max(0.0, $ratio3));

            $actualPopCaste3 = (int)floor($targetPopCaste3 * $ratio3);
            if ($actualPopCaste3 > 0) {
                $usedFlour3 = $actualPopCaste3 * self::CASTES[3]['needs']['rice_flour'] * $hours;
                $usedTea3 = $actualPopCaste3 * self::CASTES[3]['needs']['tea'] * $hours;
                $stockFlour -= min($stockFlour, $usedFlour3);
                $stockTea -= min($stockTea, $usedTea3);
            }
        }

        // 4. Évaluation et consommation pour la Caste 2 (Artisans)
        $actualPopCaste2 = 0;
        if ($hours > 0 && $targetPopCaste2 > 0) {
            $reqFish2 = $targetPopCaste2 * self::CASTES[2]['needs']['fish'] * $hours;
            $reqSoy2 = $targetPopCaste2 * self::CASTES[2]['needs']['soybean'] * $hours;
            $ratio2 = 1.0;
            if ($reqFish2 > 0) $ratio2 = min($ratio2, $stockFish / $reqFish2);
            if ($reqSoy2 > 0) $ratio2 = min($ratio2, $stockSoy / $reqSoy2);
            $ratio2 = min(1.0, max(0.0, $ratio2));

            $actualPopCaste2 = (int)floor($targetPopCaste2 * $ratio2);
            if ($actualPopCaste2 > 0) {
                $usedFish2 = $actualPopCaste2 * self::CASTES[2]['needs']['fish'] * $hours;
                $usedSoy2 = $actualPopCaste2 * self::CASTES[2]['needs']['soybean'] * $hours;
                $stockFish -= min($stockFish, $usedFish2);
                $stockSoy -= min($stockSoy, $usedSoy2);
            }
        }

        // Le reste de la population constitue la Caste 1 (Paysans & Ouvriers)
        $actualPopCaste1 = max(0, $totalPop - ($actualPopCaste2 + $actualPopCaste3 + $actualPopCaste4 + $actualPopCaste5));

        // Détermination du palier de civilisation global (1 à 5)
        $civLevel = 1;
        if ($actualPopCaste5 >= 1) {
            $civLevel = 5;
        } elseif ($actualPopCaste4 >= 2) {
            $civLevel = 4;
        } elseif ($actualPopCaste3 >= 3) {
            $civLevel = 3;
        } elseif ($actualPopCaste2 >= 4) {
            $civLevel = 2;
        }

        // Mise à jour des stocks modifiés dans l'objet planète
        if ($hours > 0) {
            $planet['rice_flour'] = max(0.0, $stockFlour);
            $planet['fish']       = max(0.0, $stockFish);
            $planet['soybean']    = max(0.0, $stockSoy);
            $planet['tea']        = max(0.0, $stockTea);
            $planet['sake']       = max(0.0, $stockSake);
        }

        $casteData = [
            1 => [
                'meta' => self::CASTES[1],
                'count' => $actualPopCaste1,
                'pct' => $totalPop > 0 ? round(($actualPopCaste1 / $totalPop) * 100, 1) : 100.0
            ],
            2 => [
                'meta' => self::CASTES[2],
                'count' => $actualPopCaste2,
                'pct' => $totalPop > 0 ? round(($actualPopCaste2 / $totalPop) * 100, 1) : 0.0
            ],
            3 => [
                'meta' => self::CASTES[3],
                'count' => $actualPopCaste3,
                'pct' => $totalPop > 0 ? round(($actualPopCaste3 / $totalPop) * 100, 1) : 0.0
            ],
            4 => [
                'meta' => self::CASTES[4],
                'count' => $actualPopCaste4,
                'pct' => $totalPop > 0 ? round(($actualPopCaste4 / $totalPop) * 100, 1) : 0.0
            ],
            5 => [
                'meta' => self::CASTES[5],
                'count' => $actualPopCaste5,
                'pct' => $totalPop > 0 ? round(($actualPopCaste5 / $totalPop) * 100, 1) : 0.0
            ],
        ];

        return [
            'civilization_level' => $civLevel,
            'civilization_name'  => self::CASTES[$civLevel]['name'],
            'total_population'   => $totalPop,
            'castes'             => $casteData,
            'erudite_count'      => $actualPopCaste4,
            'erudite_ratio'      => $totalPop > 0 ? ($actualPopCaste4 / $totalPop) : 0.0
        ];
    }
}
