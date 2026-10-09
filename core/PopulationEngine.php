<?php
/**
 * Moteur de Population, Travailleurs & Contentement (OpenShogun)
 * Gère les quotas de main-d'œuvre par bâtiment, l'équilibrage asymétrique du saké,
 * la jauge de satisfaction (0-100%) et la mécanique d'exode en cas de crise.
 */

require_once __DIR__ . '/Database.php';

class PopulationEngine {
    private PDO $db;

    // Quotas d'ouvriers requis par niveau pour les parcelles rurales
    public const FIELD_WORKERS = [
        'metal_mine'       => 2, // Camp de Bûcherons : 2 ouvriers sylvicoles par niveau
        'crystal_mine'     => 2, // Carrière de Granit : 2 carriers par niveau
        'deuterium_synth'  => 2, // Terrasses Rizicoles : 2 riziculteurs par niveau
        'solar_plant'      => 1  // Sanctuaire Shinto : 1 gardien/prêtre par niveau
    ];

    // Quotas d'ouvriers requis par niveau pour les édifices urbains
    public const BUILDING_WORKERS = [
        'sawmill'           => 3, // Charpenterie Kizukuri : 3 ouvriers par niveau
        'stonemason'        => 3, // Taille de Granit : 3 maîtres artisans par niveau
        'grain_mill'        => 3, // Meunerie de Riz : 3 meuniers par niveau
        'blacksmith'        => 4, // Forge Tamahagane : 4 forgerons d'élite par niveau
        'barracks'          => 2, // Dojo Militaire : 2 maîtres d'armes par niveau
        'shipyard'          => 3, // Écuries & Siège : 3 palefreniers/armuriers par niveau
        'research_lab'      => 2, // Académie des Savoirs : 2 érudits par niveau
        'hq'                => 2, // Tenshu : 2 officiers/gardes par niveau
        'market'            => 1, // Marché Castral : 1 intendant par niveau
        'radar'             => 1, // Poste de Vigie : 1 guetteur par niveau
        'wall'              => 1, // Muraille & Remparts : 1 garde par niveau
        'storage'           => 1, // Grenier : 1 magasinier par niveau
        'tank'              => 1, // Silo : 1 magasinier par niveau
        'teahouse'          => 1, // Pavillon de Thé : 1 serviteur par niveau
        'tournament_square' => 1, // Place d'Exercices : 1 sergent par niveau
        'embassy'           => 1, // Pavillon Diplomatique : 1 héraut par niveau
        'quantum_vault'     => 0  // Cachette secrète : 0 (dissimulée et passive)
    ];

    // Quotas d'ouvriers requis par niveau pour les 9 structures du Terroir Féodal (planet_rural_plots)
    public const RURAL_PLOT_WORKERS = [
        'tenshu'           => 2, // Donjon Tenshu : 2 gardes/officiers par niveau
        'foret'            => 2, // Forêt de Cèdres : 2 bûcherons par niveau
        'carriere'         => 2, // Carrière de Granit : 2 carriers par niveau
        'riziere'          => 2, // Rizière Inondée : 2 riziculteurs par niveau
        'fosse_argile'     => 2, // Fosse d'Argile : 2 potiers/extracteurs par niveau
        'champ_soja'       => 2, // Champ de Soja : 2 agriculteurs par niveau
        'culture_the'      => 1, // Coteaux de Théiers : 1 cueilleur par niveau
        'sanctuaire_shinto'=> 1, // Sanctuaire Shintō : 1 gardien/prêtre par niveau
        'village'          => 0  // Village & Habitations : 0 ouvrier (fournit des logements)
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Nombre d'ouvriers requis pour un bâtiment urbain selon son niveau
     */
    public static function getBuildingWorkersRequired(string $type, int $level): int {
        if ($level <= 0) return 0;
        $rate = self::BUILDING_WORKERS[$type] ?? 1;
        return $level * $rate;
    }

    /**
     * Nombre d'ouvriers requis pour une parcelle rurale selon son niveau
     */
    public static function getFieldWorkersRequired(string $type, int $level): int {
        if ($level <= 0) return 0;
        $rate = self::FIELD_WORKERS[$type] ?? 2;
        return $level * $rate;
    }

    /**
     * Nombre d'ouvriers requis pour une parcelle du Terroir Féodal selon son niveau
     */
    public static function getRuralPlotWorkersRequired(string $type, int $level): int {
        if ($level <= 0) return 0;
        $rate = self::RURAL_PLOT_WORKERS[$type] ?? 2;
        return $level * $rate;
    }

    /**
     * Calcule le total d'ouvriers requis sur tout le domaine (bâtiments + parcelles classiques + 9 parcelles du terroir)
     */
    public static function calculateTotalWorkersRequired(array $buildings, array $fields, ?int $planetId = null, ?array $ruralPlots = null): int {
        $total = 0;
        
        // 1. Prise en compte des 9 parcelles du Terroir Féodal (planet_rural_plots) si disponibles
        $hasPlots = false;
        if (is_array($ruralPlots) && !empty($ruralPlots)) {
            $hasPlots = true;
            foreach ($ruralPlots as $p) {
                $lvl = (int)($p['level'] ?? 0);
                $type = (string)($p['structure_type'] ?? ($p['type'] ?? ''));
                $total += self::getRuralPlotWorkersRequired($type, $lvl);
            }
        } elseif ($planetId !== null && $planetId > 0) {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT structure_type, level FROM planet_rural_plots WHERE planet_id = ?");
                $stmt->execute([$planetId]);
                $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($plots)) {
                    $hasPlots = true;
                    foreach ($plots as $p) {
                        $lvl = (int)($p['level'] ?? 0);
                        $type = (string)($p['structure_type'] ?? '');
                        $total += self::getRuralPlotWorkersRequired($type, $lvl);
                    }
                }
            } catch (Exception $e) {
                // Fallback silencieux vers $fields
            }
        }

        // 2. Parcelles classiques (planet_fields) : seulement si aucune parcelle du terroir n'a été comptabilisée
        if (!$hasPlots) {
            foreach ($fields as $f) {
                $lvl = (int)($f['level'] ?? 0);
                $type = (string)($f['type'] ?? '');
                $total += self::getFieldWorkersRequired($type, $lvl);
            }
        }

        // 3. Bâtiments urbains de la cité castrale
        foreach ($buildings as $type => $lvl) {
            $total += self::getBuildingWorkersRequired((string)$type, (int)$lvl);
        }

        return $total;
    }

    /**
     * Calcule le bilan complet de main-d'œuvre du domaine
     */
    public static function calculateWorkforceSummary(array $planet, array $buildings, array $fields, int $maxPopulation, ?array $ruralPlots = null): array {
        $totalPop = (int)($planet['population'] ?? 100);
        $planetId = isset($planet['id']) ? (int)$planet['id'] : null;
        $requiredWorkers = self::calculateTotalWorkersRequired($buildings, $fields, $planetId, $ruralPlots);
        $assignedWorkers = min($totalPop, $requiredWorkers);
        $idleWorkers = max(0, $totalPop - $requiredWorkers);
        $ratio = ($requiredWorkers > 0) ? min(1.0, $totalPop / $requiredWorkers) : 1.0;
        $malusPercent = round((1.0 - $ratio) * 100, 1);
        $unemploymentRate = $totalPop > 0 ? min(1.0, max(0.0, $idleWorkers / $totalPop)) : 0.0;

        return [
            'total_population'         => $totalPop,
            'max_population'           => $maxPopulation,
            'required_workers'         => $requiredWorkers,
            'assigned_workers'         => $assignedWorkers,
            'idle_workers'             => $idleWorkers,
            'workforce_ratio'          => $ratio,
            'unemployment_rate'        => $unemploymentRate,
            'unemployment_pct'         => round($unemploymentRate * 100, 1),
            'is_understaffed'          => ($ratio < 1.0),
            'understaffed_malus_pct'   => $malusPercent
        ];
    }

    /**
     * Calcule la délinquance et le maintien de l'ordre dans le fief.
     * Le manque de travail (chômage/inactifs) engendre de l'oisiveté et de la criminalité,
     * tempérée par les infrastructures d'autorité et de sécurité (Tenshu, Muraille, Dojo, Vigie).
     */
    public static function calculateDelinquency(array $planet, array $buildings = [], ?array $workforce = null): array {
        $totalPop = (int)($planet['population'] ?? 100);
        $idleWorkers = (int)($workforce['idle_workers'] ?? 0);

        // Seuil d'exonération démographique pour les très petits villages naissants (<= 20 villageois)
        if ($totalPop <= 20) {
            return [
                'unemployment_rate' => 0.0,
                'unemployment_pct'  => 0.0,
                'idle_workers'      => $idleWorkers,
                'base_delinquency'  => 0.0,
                'security_bonus'    => 0,
                'net_delinquency'   => 0.0,
                'moral_penalty'     => 0,
                'level_label'       => 'Ordre Parfait',
                'badge_color'       => 'success',
                'icon'              => 'fa-shield-halved',
                'desc'              => 'Petite communauté soudée, absence de criminalité.'
            ];
        }

        // Taux de chômage / inactivité (de 0.0 à 1.0)
        $unemploymentRate = min(1.0, max(0.0, $idleWorkers / max(1, $totalPop)));
        $unemploymentPct = round($unemploymentRate * 100, 1);

        // Seuil de tolérance : jusqu'à 15% d'inactifs, c'est considéré comme une réserve normale de main-d'œuvre.
        // Au-delà de 15%, l'oisiveté génère de la délinquance croissante (jusqu'à 100% à plein chômage).
        $baseDelinquency = 0.0;
        if ($unemploymentRate > 0.15) {
            $baseDelinquency = min(100.0, round((($unemploymentRate - 0.15) / 0.85) * 100.0, 1));
        }

        // Maintien de l'ordre féodal assuré par les édifices d'autorité et militaires :
        // - Tenshu (hq) : +3% d'ordre par niveau (magistrats et officiers seigneuriaux)
        // - Remparts (wall) : +2% d'ordre par niveau (contrôle des accès et patrouilles)
        // - Dojo Militaire (barracks) : +2% d'ordre par niveau (rondes de samouraïs et bushi)
        // - Poste de Vigie (radar) : +1% d'ordre par niveau (guetteurs et surveillance)
        $hqLvl = (int)($buildings['hq'] ?? 0);
        $wallLvl = (int)($buildings['wall'] ?? 0);
        $barracksLvl = (int)($buildings['barracks'] ?? 0);
        $radarLvl = (int)($buildings['radar'] ?? 0);

        $securityBonus = ($hqLvl * 3) + ($wallLvl * 2) + ($barracksLvl * 2) + ($radarLvl * 1);

        // Délinquance nette résiduelle
        $netDelinquency = max(0.0, round($baseDelinquency - $securityBonus, 1));

        // Impact sur le moral / contentement (pénalité de 0 à -25%)
        $moralPenalty = (int)round(($netDelinquency / 100.0) * 25);

        // Qualification de l'ordre public
        if ($netDelinquency <= 0.0) {
            $levelLabel = 'Ordre Parfait';
            $badgeColor = 'success';
            $icon = 'fa-shield-halved';
            $desc = 'Garnison vigilante et sérénité dans les ruelles du village.';
        } elseif ($netDelinquency <= 15.0) {
            $levelLabel = 'Tension Faible';
            $badgeColor = 'info';
            $icon = 'fa-user-secret';
            $desc = 'Petits larcins isolés et oisiveté modérée.';
        } elseif ($netDelinquency <= 35.0) {
            $levelLabel = 'Vols & Mécontentement';
            $badgeColor = 'warning';
            $icon = 'fa-mask';
            $desc = 'Vols récurrents et grogne grandissante parmi les sans-emploi.';
        } elseif ($netDelinquency <= 60.0) {
            $levelLabel = 'Troubles & Brigandage';
            $badgeColor = 'warning';
            $icon = 'fa-skull-crossbones';
            $desc = 'Bandes de pillards, rixes et insécurité marquée.';
        } else {
            $levelLabel = 'Criminalité Sévère';
            $badgeColor = 'danger';
            $icon = 'fa-fire';
            $desc = 'Loi du plus fort, désordre total dans le fief.';
        }

        return [
            'unemployment_rate' => $unemploymentRate,
            'unemployment_pct'  => $unemploymentPct,
            'idle_workers'      => $idleWorkers,
            'base_delinquency'  => $baseDelinquency,
            'security_bonus'    => $securityBonus,
            'net_delinquency'   => $netDelinquency,
            'moral_penalty'     => $moralPenalty,
            'level_label'       => $levelLabel,
            'badge_color'       => $badgeColor,
            'icon'              => $icon,
            'desc'              => $desc
        ];
    }

    /**
     * Calcul de la jauge de contentement féodale (0% à 100%)
     * Règles :
     * - Besoins vitaux (Nourriture / Farine de riz / Sérénité) : Dégradent le contentement si absents.
     * - Saké (Bien de confort / Luxe) : Confère un BONUS positif net (+15%) si présent.
     *   En cas de pénurie de saké : NE DÉGRADE PAS le contentement (aucun malus, valeur neutre).
     * - Délinquance : Dégrade le moral en cas de manque d'emplois / chômage (pénalité jusqu'à -25%).
     */
    public static function calculateContentment(
        array $planet,
        ?array $activeFeast = null,
        ?array $workforce = null,
        array $buildings = []
    ): array {
        $flour = (float)($planet['rice_flour'] ?? 0);
        $rice = (float)($planet['deuterium'] ?? 0);
        $sake = (float)($planet['sake'] ?? 0);
        $energyUsed = (int)($planet['energy_used'] ?? 0);
        $energyMax = (int)($planet['energy_max'] ?? 20);

        // 1. Base de satisfaction neutre
        $baseScore = 75;

        // 2. Besoins vitaux : Nourriture (Farine de riz ou Riz impérial)
        $hasFood = ($flour > 0 || $rice > 0);
        $foodDelta = 0;
        if ($hasFood) {
            $foodDelta = +15; // Nourris et sereins
        } else {
            $foodDelta = -55; // Famine / disette sévère
        }

        // 3. Besoins vitaux : Sérénité Shinto (Énergie spirituelle du domaine)
        $energyDelta = 0;
        $hasEnergy = ($energyUsed <= $energyMax);
        if (!$hasEnergy) {
            $energyDelta = -20; // Tension / ferveur insuffisante
        }

        // 4. RÈGLE ASYMÉTRIQUE DU SAKÉ (Bien de confort et de festivités)
        // Présence de saké => BONUS positif net (+15%)
        // Absence de saké => 0 (Valeur neutre, strictement AUCUN malus)
        $sakeBonusActive = ($sake > 0);
        $sakePoints = $sakeBonusActive ? 15 : 0;

        // 5. Célébration au Tenshu active (Bonus Matsuri)
        $feastPoints = ($activeFeast !== null) ? 10 : 0;

        // 6. Impact de la délinquance féodale (Manque de travail / Inactifs)
        $wf = $workforce ?? ($planet['workforce'] ?? null);
        $bld = !empty($buildings) ? $buildings : ($planet['buildings'] ?? []);
        $delinquency = self::calculateDelinquency($planet, $bld, $wf);
        $delinquencyDelta = -$delinquency['moral_penalty'];

        // Calcul final borné de 0 à 100%
        $totalScore = max(0, min(100, $baseScore + $foodDelta + $energyDelta + $sakePoints + $feastPoints + $delinquencyDelta));

        // Détermination du statut et du style Tabler
        $isExodus = ($totalScore < 25);
        if ($totalScore >= 80) {
            $statusLabel = 'Euphorique & Prospère';
            $badgeColor = 'success';
            $icon = 'fa-face-laugh-beam';
        } elseif ($totalScore >= 50) {
            $statusLabel = 'Paisible & Satisfait';
            $badgeColor = 'teal';
            $icon = 'fa-face-smile';
        } elseif ($totalScore >= 25) {
            $statusLabel = 'Inquiet & Tendu';
            $badgeColor = 'warning';
            $icon = 'fa-face-meh';
        } else {
            $statusLabel = 'Exode Imminent !';
            $badgeColor = 'danger';
            $icon = 'fa-face-frown';
        }

        return [
            'score'             => $totalScore,
            'status_label'      => $statusLabel,
            'badge_color'       => $badgeColor,
            'icon'              => $icon,
            'has_food'          => $hasFood,
            'food_delta'        => $foodDelta,
            'has_energy'        => $hasEnergy,
            'energy_delta'      => $energyDelta,
            'sake_bonus_active' => $sakeBonusActive,
            'sake_points'       => $sakePoints, // Strictement >= 0 (asymétrique)
            'feast_points'      => $feastPoints,
            'delinquency'       => $delinquency,
            'delinquency_delta' => $delinquencyDelta,
            'is_exodus'         => $isExodus
        ];
    }

    /**
     * Traite un cycle temporel de population : consommation horaire de vivres, saké,
     * mise à jour du contentement, et déclenchement de l'exode ou de la croissance démographique.
     */
    public static function processTick(
        int $planetId,
        float $hours,
        array &$planet,
        int $maxPopulation,
        ?array $activeFeast = null,
        array $buildings = [],
        array $fields = []
    ): array {
        $workforce = (!empty($buildings) || !empty($fields))
            ? self::calculateWorkforceSummary($planet, $buildings, $fields, $maxPopulation)
            : ($planet['workforce'] ?? null);

        if ($hours <= 0) {
            return self::calculateContentment($planet, $activeFeast, $workforce, $buildings);
        }

        $pop = (int)($planet['population'] ?? 100);
        $flour = (float)($planet['rice_flour'] ?? 0);
        $rice = (float)($planet['deuterium'] ?? 0);
        $sake = (float)($planet['sake'] ?? 0);

        // 1. Consommation vitale de nourriture (0.04 farine par habitant par heure)
        $foodNeeded = $pop * 0.04 * $hours;
        $flourConsumed = 0.0;
        $riceConsumed = 0.0;

        if ($flour >= $foodNeeded) {
            $flour -= $foodNeeded;
            $flourConsumed = $foodNeeded;
        } else {
            $flourConsumed = $flour;
            $remaining = $foodNeeded - $flour;
            $flour = 0.0;
            // Puiser dans les réserves de riz brut si la farine vient à manquer
            if ($rice >= $remaining) {
                $rice -= $remaining;
                $riceConsumed = $remaining;
            } else {
                $riceConsumed = $rice;
                $rice = 0.0;
            }
        }

        // 2. Consommation modérée de saké si disponible (0.01 saké par habitant par heure)
        // Règle asymétrique : si le stock s'épuise, aucun malus n'est appliqué
        $sakeConsumed = 0.0;
        if ($sake > 0) {
            $sakeNeeded = $pop * 0.01 * $hours;
            $sakeConsumed = min($sake, $sakeNeeded);
            $sake = max(0.0, $sake - $sakeConsumed);
        }

        $planet['rice_flour'] = $flour;
        $planet['deuterium'] = $rice;
        $planet['sake'] = $sake;

        // 3. Calcul du contentement
        $contentment = self::calculateContentment($planet, $activeFeast, $workforce, $buildings);
        $score = $contentment['score'];

        // 4. Exode ou Croissance démographique
        $exodusCasualties = 0;
        $popGrowth = 0;

        if ($contentment['is_exodus']) {
            // Seuil critique < 25% : Exode massif progressif des villageois
            $exodusRate = 0.06; // 6% d'habitants fuient le village par heure de crise
            $fleeing = (int)ceil($pop * $exodusRate * $hours);
            $newPop = max(20, $pop - $fleeing); // Plancher de sécurité de 20 habitants
            $exodusCasualties = $pop - $newPop;
            $pop = $newPop;
        } elseif ($score >= 50 && $contentment['has_food'] && $pop < $maxPopulation) {
            // Contentement suffisant et vivres disponibles : Croissance démographique
            $growthRate = 15 * ($score / 100);
            $growth = (int)floor($growthRate * $hours);
            $popGrowth = min($maxPopulation - $pop, max(0, $growth));
            $pop += $popGrowth;
        }

        $planet['population'] = $pop;
        $planet['contentment'] = $score;
        $planet['contentment_details'] = $contentment;
        if ($workforce !== null) {
            $planet['workforce'] = $workforce;
        }

        return [
            'contentment'       => $contentment,
            'flour_consumed'    => $flourConsumed,
            'rice_consumed'     => $riceConsumed,
            'sake_consumed'     => $sakeConsumed,
            'exodus_casualties' => $exodusCasualties,
            'population_growth' => $popGrowth
        ];
    }
}
