<?php
/**
 * Module Studio Dev : Simulateur de Dérivation des Bâtiments & Courbes de Progression
 * Métier : Game Elevate Designer
 * Emplacement : views/studio/game-elevate-designer/building-derivation.php
 */

require_once __DIR__ . '/../../../core/AuthManager.php';
require_once __DIR__ . '/../../../core/GameConfig.php';
require_once __DIR__ . '/../../../config/game_constants.php';
require_once __DIR__ . '/../../../core/BuildingEngine.php';
require_once __DIR__ . '/../../../core/PopulationEngine.php';

// Contrôle strict côté serveur du métier Game Elevate Designer
if (!AuthManager::hasJob('game-elevate-designer')) {
    http_response_code(403);
    ?>
    <div class="card border-danger shadow-sm my-4">
        <div class="card-body text-center py-5">
            <div class="empty-icon text-danger fs-1 mb-3"><i class="fa-solid fa-lock"></i></div>
            <h2 class="text-danger fw-bold">Accès Restreint (Code HTTP 403)</h2>
            <p class="text-muted fs-4">
                Ce simulateur de dérivation et d'équilibrage des chantiers est strictement réservé au métier <strong>Game Elevate Designer</strong>.
            </p>
            <div class="mt-4">
                <a href="/?page=dev_team" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour au Studio Dev
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Chargement des paramètres actuels de jeu
$currentTimeCoeff  = (float)GameConfig::get('building_time_coeff', 1.0);
$currentTimeGrowth = (float)GameConfig::get('building_time_growth', 1.28);
$currentCostCoeff  = (float)GameConfig::get('building_cost_coeff', 1.0);
$currentCostGrowth = (float)GameConfig::get('building_cost_growth', 1.0);
$currentGameSpeed  = max(1, (float)GameConfig::get('game_speed', 5));

// Compilation du catalogue complet des édifices et parcelles pour le simulateur
$catalog = [];

// 1. Bâtiments urbains
foreach (BUILDINGS as $bCode => $bDef) {
    // Détermination de l'impact économique / production de chaque bâtiment urbain
    $prodMeta = [
        'has_prod'   => false,
        'prod_type'  => 'none',
        'unit'       => '',
        'base_val'   => 0,
        'desc_prod'  => 'Fonction utilitaire/infrastructure'
    ];

    if ($bCode === 'sawmill') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'boost_wood', 'unit' => '% Prod Bois', 'base_val' => 5, 'desc_prod' => '+5% production Bois de Cèdre / niv'];
    } elseif ($bCode === 'stonemason') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'boost_stone', 'unit' => '% Prod Pierre', 'base_val' => 5, 'desc_prod' => '+5% production Pierre de Taille / niv'];
    } elseif ($bCode === 'grain_mill') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'boost_rice', 'unit' => '% Prod Riz', 'base_val' => 5, 'desc_prod' => '+5% production Riz / niv'];
    } elseif ($bCode === 'teahouse') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'boost_serenity', 'unit' => '% Sérénité', 'base_val' => 5, 'desc_prod' => '+5% Sérénité & Ferveur / niv'];
    } elseif ($bCode === 'storage') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'cap_materials', 'unit' => 'Capacité Mat.', 'base_val' => 15000, 'desc_prod' => 'Stock Bois & Pierre (x1.5 / niv)'];
    } elseif ($bCode === 'tank') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'cap_rice', 'unit' => 'Capacité Riz', 'base_val' => 15000, 'desc_prod' => 'Stock Riz Koku (x1.5 / niv)'];
    } elseif ($bCode === 'hq') {
        $prodMeta = ['has_prod' => true, 'prod_type' => 'speed_discount', 'unit' => '% Réduction Durée', 'base_val' => 3.6, 'desc_prod' => 'Accélération des chantiers (gouvernance)'];
    }

    $catalog[$bCode] = [
        'category'        => 'building',
        'code'            => $bCode,
        'name'            => $bDef['name'],
        'icon'            => $bDef['icon'],
        'category_label'  => 'Cité Castrale',
        'base_cost'       => [
            'metal'     => (int)($bDef['base_cost']['metal'] ?? 0),
            'crystal'   => (int)($bDef['base_cost']['crystal'] ?? 0),
            'deuterium' => (int)($bDef['base_cost']['deuterium'] ?? 0)
        ],
        'cost_multiplier' => (float)$bDef['cost_multiplier'],
        'base_time'       => (int)$bDef['base_time'],
        'max_level'       => (int)($bDef['max_level'] ?? 20),
        'description'     => $bDef['description'] ?? '',
        'production'      => $prodMeta,
        'workers_rate'    => PopulationEngine::BUILDING_WORKERS[$bCode] ?? 1
    ];
}

// 2. Parcelles rurales (terroir féodal & mines)
foreach (FIELD_TYPES as $fCode => $fDef) {
    $prodUnit = 'unités/h';
    $prodDesc = 'Ressources / heure';
    if ($fCode === 'metal_mine') {
        $prodUnit = 'Bois/h';
        $prodDesc = 'Bois de Cèdre coupé par heure';
    } elseif ($fCode === 'crystal_mine') {
        $prodUnit = 'Pierre/h';
        $prodDesc = 'Pierre de Taille extraite par heure';
    } elseif ($fCode === 'deuterium_synth') {
        $prodUnit = 'Riz/h';
        $prodDesc = 'Koku de Riz récolté par heure';
    } elseif ($fCode === 'solar_plant') {
        $prodUnit = 'Sérénité/h';
        $prodDesc = 'Ferveur & Sérénité générée par heure';
    }

    $catalog[$fCode] = [
        'category'        => 'field',
        'code'            => $fCode,
        'name'            => $fDef['name'] . ' (' . $fDef['res_name'] . ')',
        'icon'            => $fDef['icon'],
        'category_label'  => 'Terroir Rural',
        'base_cost'       => [
            'metal'     => (int)($fDef['base_cost']['metal'] ?? 0),
            'crystal'   => (int)($fDef['base_cost']['crystal'] ?? 0),
            'deuterium' => (int)($fDef['base_cost']['deuterium'] ?? 0)
        ],
        'cost_multiplier' => (float)$fDef['cost_multiplier'],
        'base_time'       => (int)$fDef['base_time'],
        'max_level'       => 20,
        'description'     => $fDef['description'] ?? '',
        'production'      => [
            'has_prod'   => true,
            'prod_type'  => 'hourly_res',
            'unit'       => $prodUnit,
            'base_val'   => (float)($fDef['base_prod'] ?? 20),
            'desc_prod'  => $prodDesc
        ],
        'workers_rate'    => PopulationEngine::FIELD_WORKERS[$fCode] ?? 2
    ];
}

// 3. Structures du Terroir Rural Vivant (Fosse argile, Soja, Thé, Habitations village)
$ruralExtras = [
    'foret' => [
        'name' => 'Forêt de Cèdres (Parcelle)',
        'icon' => '<i class="fa-solid fa-tree text-success"></i>',
        'res_name' => 'Bois de Cèdre',
        'unit' => 'Bois/h',
        'base_val' => 35,
        'desc' => 'Parcelle forestière du terroir - Bois de cèdre récolté par heure',
        'base_cost' => ['metal' => 45, 'crystal' => 60, 'deuterium' => 20],
        'mult' => 1.45,
        'time' => 180,
        'workers' => 2,
    ],
    'carriere' => [
        'name' => 'Carrière de Granit (Parcelle)',
        'icon' => '<i class="fa-solid fa-mountain text-secondary"></i>',
        'res_name' => 'Pierre de Taille',
        'unit' => 'Pierre/h',
        'base_val' => 35,
        'desc' => 'Parcelle de falaise du terroir - Granit extrait par heure',
        'base_cost' => ['metal' => 60, 'crystal' => 40, 'deuterium' => 20],
        'mult' => 1.45,
        'time' => 200,
        'workers' => 2,
    ],
    'riziere' => [
        'name' => 'Rizière Inondée (Parcelle)',
        'icon' => '<i class="fa-solid fa-wheat-awn text-warning"></i>',
        'res_name' => 'Riz Impérial',
        'unit' => 'Riz/h',
        'base_val' => 35,
        'desc' => 'Bassin alluviaux du terroir - Riz nourricier récolté par heure',
        'base_cost' => ['metal' => 35, 'crystal' => 45, 'deuterium' => 60],
        'mult' => 1.45,
        'time' => 220,
        'workers' => 2,
    ],
    'fosse_argile' => [
        'name' => 'Fosse d\'Argile & Céramique',
        'icon' => '<i class="fa-solid fa-cubes-stacked text-orange"></i>',
        'res_name' => 'Argile & Tuiles',
        'unit' => 'Argile/h',
        'base_val' => 35,
        'desc' => 'Gisement fluvial alluviale et poterie - Argile & tuiles par heure',
        'base_cost' => ['metal' => 45, 'crystal' => 50, 'deuterium' => 30],
        'mult' => 1.45,
        'time' => 190,
        'workers' => 2,
    ],
    'champ_soja' => [
        'name' => 'Champ de Soja & Miso',
        'icon' => '<i class="fa-solid fa-seedling text-lime"></i>',
        'res_name' => 'Soja & Tofu',
        'unit' => 'Soja/h',
        'base_val' => 35,
        'desc' => 'Culture vivrière de soja et miso - Soja par heure',
        'base_cost' => ['metal' => 40, 'crystal' => 30, 'deuterium' => 40],
        'mult' => 1.45,
        'time' => 180,
        'workers' => 2,
    ],
    'culture_the' => [
        'name' => 'Coteaux de Théiers (Matcha)',
        'icon' => '<i class="fa-solid fa-leaf text-teal"></i>',
        'res_name' => 'Feuilles de Thé',
        'unit' => 'Thé/h',
        'base_val' => 35,
        'desc' => 'Terrasses étagées de théiers taillés - Feuilles de thé par heure',
        'base_cost' => ['metal' => 50, 'crystal' => 35, 'deuterium' => 45],
        'mult' => 1.45,
        'time' => 200,
        'workers' => 1,
    ],
    'sanctuaire_shinto' => [
        'name' => 'Bosquet & Sanctuaire Shintō',
        'icon' => '<i class="fa-solid fa-torii-gate text-danger"></i>',
        'res_name' => 'Sérénité & Ferveur',
        'unit' => 'Sérénité/h',
        'base_val' => 30,
        'desc' => 'Bosquet sacré au torii rouge - Sérénité divine générée par heure',
        'base_cost' => ['metal' => 80, 'crystal' => 70, 'deuterium' => 50],
        'mult' => 1.45,
        'time' => 240,
        'workers' => 1,
    ],
    'village' => [
        'name' => 'Village & Habitations (Minka)',
        'icon' => '<i class="fa-solid fa-people-roof text-indigo"></i>',
        'res_name' => 'Logements Habitants',
        'unit' => 'Logements',
        'base_val' => 25,
        'desc' => 'Hameau central - Capacité d\'accueil maximale de villageois',
        'base_cost' => ['metal' => 70, 'crystal' => 50, 'deuterium' => 30],
        'mult' => 1.45,
        'time' => 210,
        'workers' => 0,
    ]
];

foreach ($ruralExtras as $rCode => $rDef) {
    $catalog['rural_' . $rCode] = [
        'category'        => 'rural',
        'code'            => 'rural_' . $rCode,
        'raw_type'        => $rCode,
        'name'            => $rDef['name'],
        'icon'            => $rDef['icon'],
        'category_label'  => 'Terroir Vivant (9 Parcelles)',
        'base_cost'       => [
            'metal'     => (int)$rDef['base_cost']['metal'],
            'crystal'   => (int)$rDef['base_cost']['crystal'],
            'deuterium' => (int)$rDef['base_cost']['deuterium']
        ],
        'cost_multiplier' => (float)$rDef['mult'],
        'base_time'       => (int)$rDef['time'],
        'max_level'       => 30,
        'description'     => $rDef['desc'],
        'production'      => [
            'has_prod'   => true,
            'prod_type'  => $rCode === 'village' ? 'cap_housing' : ($rCode === 'sanctuaire_shinto' ? 'hourly_serenity' : 'hourly_res'),
            'unit'       => $rDef['unit'],
            'base_val'   => (float)$rDef['base_val'],
            'desc_prod'  => $rDef['desc']
        ],
        'workers_rate'    => $rDef['workers']
    ];
}
?>

<!-- ── EN-TÊTE DU SIMULATEUR DE DÉRIVATION ── -->
<div class="card mb-4 border-0 shadow-sm" style="border-top: 3px solid #f59e0b !important;">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 bg-white">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-green-lt text-green fw-bold">
                    <i class="fa-solid fa-compass-drafting me-1"></i>Game Elevate Designer
                </span>
                <span class="badge bg-warning-lt text-warning fw-bold">
                    <i class="fa-solid fa-chart-line me-1"></i>Dérivation Mathématique
                </span>
                <span class="badge bg-primary-lt text-primary fw-bold">
                    <i class="fa-solid fa-bolt me-1"></i>Serveur x<?= $currentGameSpeed ?>
                </span>
            </div>
            <h2 class="card-title h3 m-0 text-dark fw-bold d-flex align-items-center gap-2">
                <i class="fa-solid fa-arrow-trend-up text-warning"></i>
                <span>Simulateur &amp; Équilibrage : Dérivation des Bâtiments</span>
            </h2>
            <div class="text-secondary small mt-1">
                Ajustez les coefficients temporels et économiques pour moduler la durée des chantiers et l'exigence en ressources niveau par niveau. 
                <strong>Les paramètres sauvegardés ici régissent immédiatement les coûts et délais de construction dans l'ensemble du jeu.</strong>
            </div>
        </div>

        <!-- Presets rapides en 1 clic -->
        <div class="d-flex flex-column align-items-end gap-1">
            <span class="text-muted small fw-medium">Préréglages d'équilibrage :</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" onclick="applyDerivationPreset(1.0, 1.28, 1.0, 1.0)" title="Formule canonique Sengoku standard">
                    <i class="fa-solid fa-balance-scale me-1 text-primary"></i>Standard
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="applyDerivationPreset(0.35, 1.18, 0.70, 0.95)" title="Chantiers ultra rapides et coûts allégés">
                    <i class="fa-solid fa-bolt me-1 text-warning"></i>Éclair
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="applyDerivationPreset(2.20, 1.35, 1.40, 1.12)" title="Campagne longue et exigeante">
                    <i class="fa-solid fa-hourglass-half me-1 text-danger"></i>Long Terme
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="applyDerivationPreset(1.0, 1.28, 0.50, 0.90)" title="Coûts coupés de moitié">
                    <i class="fa-solid fa-wheat-awn me-1 text-success"></i>Abondance
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="applyDerivationPreset(3.0, 1.38, 2.0, 1.20)" title="Économie hardcore de prestige">
                    <i class="fa-solid fa-monument me-1 text-purple"></i>Monumental
                </button>
            </div>
        </div>
    </div>

    <!-- ── FORMULAIRE ET CURSEURS DE RÉGLAGE ── -->
    <div class="card-body bg-light-subtle border-bottom">
        <form id="buildingDerivationForm" onsubmit="saveBuildingDerivation(event)">
            <div class="row g-3">
                <!-- 1. Coefficient de Durée Global -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 shadow-none border bg-white p-3">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-stopwatch text-warning"></i>
                                <span>Multiplicateur Durée</span>
                            </span>
                            <span class="badge bg-warning-lt fw-bold font-monospace fs-6" id="badge_time_coeff">x<?= number_format($currentTimeCoeff, 2) ?></span>
                        </label>
                        <div class="d-flex align-items-center gap-2 my-2">
                            <input type="range" id="range_time_coeff" min="0.1" max="5.0" step="0.05" value="<?= $currentTimeCoeff ?>" class="form-range flex-grow-1"
                                   oninput="updateInputFromRange('time_coeff', this.value, 'x', 2);">
                            <input type="number" id="input_time_coeff" name="building_time_coeff" min="0.05" max="10.0" step="0.05" value="<?= $currentTimeCoeff ?>" 
                                   class="form-control text-center font-monospace fw-bold" style="width: 80px;"
                                   oninput="updateRangeFromInput('time_coeff', this.value, 'x', 2);">
                        </div>
                        <div class="form-hint small text-secondary">
                            Rend les chantiers plus ou moins longs globalement (&lt;1x accélère, &gt;1x rallonge).
                        </div>
                    </div>
                </div>

                <!-- 2. Pente d'Accroissement Temporel -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 shadow-none border bg-white p-3">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-arrow-trend-up text-danger"></i>
                                <span>Pente Temporelle ($g_t$)</span>
                            </span>
                            <span class="badge bg-danger-lt fw-bold font-monospace fs-6" id="badge_time_growth"><?= number_format($currentTimeGrowth, 2) ?></span>
                        </label>
                        <div class="d-flex align-items-center gap-2 my-2">
                            <input type="range" id="range_time_growth" min="1.10" max="1.55" step="0.01" value="<?= $currentTimeGrowth ?>" class="form-range flex-grow-1"
                                   oninput="updateInputFromRange('time_growth', this.value, '', 2);">
                            <input type="number" id="input_time_growth" name="building_time_growth" min="1.05" max="2.00" step="0.01" value="<?= $currentTimeGrowth ?>" 
                                   class="form-control text-center font-monospace fw-bold" style="width: 80px;"
                                   oninput="updateRangeFromInput('time_growth', this.value, '', 2);">
                        </div>
                        <div class="form-hint small text-secondary">
                            Base exponentielle du temps par niveau ($g_t^L$). Valeur standard Travian = <code>1.28</code>.
                        </div>
                    </div>
                </div>

                <!-- 3. Coefficient de Coût Global -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 shadow-none border bg-white p-3">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-coins text-success"></i>
                                <span>Multiplicateur Coût</span>
                            </span>
                            <span class="badge bg-success-lt fw-bold font-monospace fs-6" id="badge_cost_coeff">x<?= number_format($currentCostCoeff, 2) ?></span>
                        </label>
                        <div class="d-flex align-items-center gap-2 my-2">
                            <input type="range" id="range_cost_coeff" min="0.1" max="5.0" step="0.05" value="<?= $currentCostCoeff ?>" class="form-range flex-grow-1"
                                   oninput="updateInputFromRange('cost_coeff', this.value, 'x', 2);">
                            <input type="number" id="input_cost_coeff" name="building_cost_coeff" min="0.05" max="10.0" step="0.05" value="<?= $currentCostCoeff ?>" 
                                   class="form-control text-center font-monospace fw-bold" style="width: 80px;"
                                   oninput="updateRangeFromInput('cost_coeff', this.value, 'x', 2);">
                        </div>
                        <div class="form-hint small text-secondary">
                            Rend les coups (coûts) en Bois, Pierre et Riz plus ou moins importants sur tous les édifices.
                        </div>
                    </div>
                </div>

                <!-- 4. Facteur d'Inflation des Coûts par Niveau -->
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 shadow-none border bg-white p-3">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span class="d-flex align-items-center gap-1">
                                <i class="fa-solid fa-chart-line-up text-primary"></i>
                                <span>Inflation des Coûts ($g_c$)</span>
                            </span>
                            <span class="badge bg-primary-lt fw-bold font-monospace fs-6" id="badge_cost_growth">x<?= number_format($currentCostGrowth, 2) ?></span>
                        </label>
                        <div class="d-flex align-items-center gap-2 my-2">
                            <input type="range" id="range_cost_growth" min="0.80" max="1.40" step="0.02" value="<?= $currentCostGrowth ?>" class="form-range flex-grow-1"
                                   oninput="updateInputFromRange('cost_growth', this.value, 'x', 2);">
                            <input type="number" id="input_cost_growth" name="building_cost_growth" min="0.50" max="2.00" step="0.02" value="<?= $currentCostGrowth ?>" 
                                   class="form-control text-center font-monospace fw-bold" style="width: 80px;"
                                   oninput="updateRangeFromInput('cost_growth', this.value, 'x', 2);">
                        </div>
                        <div class="form-hint small text-secondary">
                            Multiplie le ratio de coût $(m \times g_c)^L$. Augmente ou amortit la dérivation à haut niveau.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paramètres de Contexte de Simulation & Barre de Sauvegarde -->
            <div class="row g-3 align-items-center mt-2 pt-3 border-top">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label fw-bold small text-secondary mb-1">
                        <i class="fa-solid fa-landmark me-1"></i>Bâtiment / Parcelle testé :
                    </label>
                    <select id="sim_building_select" class="form-select form-select-sm" onchange="runDerivationSimulation()">
                        <optgroup label="🏯 Cité Castrale (Bâtiments Urbains)">
                            <?php foreach ($catalog as $code => $item): if ($item['category'] === 'building'): ?>
                                <option value="<?= htmlspecialchars($code) ?>" <?= $code === 'hq' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['name']) ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                        <optgroup label="🏞️ Terroir Vivant (9 Parcelles Féodales)">
                            <?php foreach ($catalog as $code => $item): if ($item['category'] === 'rural'): ?>
                                <option value="<?= htmlspecialchars($code) ?>">
                                    <?= htmlspecialchars($item['name']) ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                        <optgroup label="🌾 Parcelles & Exploitations Classiques">
                            <?php foreach ($catalog as $code => $item): if ($item['category'] === 'field'): ?>
                                <option value="<?= htmlspecialchars($code) ?>">
                                    <?= htmlspecialchars($item['name']) ?>
                                </option>
                            <?php endif; endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label fw-bold small text-secondary mb-1">
                        <i class="fa-solid fa-chess-rook me-1"></i>Tenshu Test :
                    </label>
                    <select id="sim_hq_level" class="form-select form-select-sm" onchange="runDerivationSimulation()">
                        <?php for ($h = 1; $h <= 20; $h++): ?>
                            <option value="<?= $h ?>" <?= $h === 1 ? 'selected' : '' ?>>Niveau <?= $h ?> (-<?= number_format((1 - pow(0.964, $h - 1)) * 100, 1) ?>%)</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label fw-bold small text-secondary mb-1">
                        <i class="fa-solid fa-users me-1"></i>Main-d'œuvre :
                    </label>
                    <select id="sim_population" class="form-select form-select-sm" onchange="runDerivationSimulation()">
                        <option value="0">0 hab (0%)</option>
                        <option value="250">250 hab (-2.5%)</option>
                        <option value="500">500 hab (-5%)</option>
                        <option value="1000" selected>1 000 hab (-10%)</option>
                        <option value="2500">2 500 hab (Max -25%)</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 col-lg-2">
                    <label class="form-label fw-bold small text-secondary mb-1">
                        <i class="fa-solid fa-layer-group me-1"></i>Niveaux projetés :
                    </label>
                    <select id="sim_max_level" class="form-select form-select-sm" onchange="runDerivationSimulation()">
                        <option value="15">15 niveaux</option>
                        <option value="20" selected>20 niveaux (Standard)</option>
                        <option value="25">25 niveaux (Étendu)</option>
                        <option value="30">30 niveaux (Extrême)</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-lg-3 d-flex justify-content-end align-items-end gap-2 ms-auto">
                    <span id="sync_status_badge" class="badge bg-success-lt text-success d-none d-xl-inline-block py-2">
                        <i class="fa-solid fa-check me-1"></i>Synchronisé
                    </span>
                    <button type="submit" id="btn_save_derivation" class="btn btn-primary fw-bold px-3 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Appliquer au Jeu Réel
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ── INDICATEURS SYNTHÉTIQUES (KPIS) ── -->
    <div class="card-body p-3 bg-white">
        <div class="row g-3">
            <div class="col-6 col-lg">
                <div class="border rounded p-3 text-center bg-body-tertiary h-100">
                    <div class="text-secondary small fw-medium mb-1">
                        <i class="fa-solid fa-hourglass-start me-1 text-primary"></i>Durée Niv 1 &rarr; Max
                    </div>
                    <div class="fs-4 fw-bold font-monospace text-dark" id="kpi_duration_range">--</div>
                    <div class="small text-muted mt-1" id="kpi_duration_sub">--</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 text-center bg-body-tertiary h-100">
                    <div class="text-secondary small fw-medium mb-1">
                        <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i>Temps Cumulé Total
                    </div>
                    <div class="fs-4 fw-bold font-monospace text-warning" id="kpi_total_time">--</div>
                    <div class="small text-muted mt-1">Du niveau 1 au niveau max</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 text-center bg-body-tertiary h-100">
                    <div class="text-secondary small fw-medium mb-1">
                        <i class="fa-solid fa-boxes-stacked me-1 text-success"></i>Coût Niv 1 &rarr; Max
                    </div>
                    <div class="fs-4 fw-bold font-monospace text-dark" id="kpi_cost_range">--</div>
                    <div class="small text-muted mt-1" id="kpi_cost_sub">--</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 text-center bg-body-tertiary h-100">
                    <div class="text-secondary small fw-medium mb-1">
                        <i class="fa-solid fa-vault me-1 text-purple"></i>Total Cumulé Dépensé
                    </div>
                    <div class="fs-4 fw-bold font-monospace text-purple" id="kpi_total_cost">--</div>
                    <div class="small text-muted mt-1">Bois + Pierre + Riz combinés</div>
                </div>
            </div>
            <div class="col-12 col-lg">
                <div class="border rounded p-3 text-center bg-body-tertiary h-100" style="border-left: 3px solid #06b6d4 !important;">
                    <div class="text-secondary small fw-medium mb-1">
                        <i class="fa-solid fa-chart-pie me-1 text-info"></i>Rendement &amp; Production
                    </div>
                    <div class="fs-4 fw-bold font-monospace text-info" id="kpi_prod_range">--</div>
                    <div class="small text-muted mt-1" id="kpi_prod_sub">Amortissement ROI moyen : --</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── GRAPHIQUE SVG INTERACTIF DE DÉRIVATION ── -->
    <div class="card-body border-top p-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div>
                <h4 class="card-title m-0 fw-bold d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-area text-warning"></i>
                    <span>Courbes de Dérivation : Durée vs Coût de Construction</span>
                </h4>
                <div class="text-secondary small">
                    Visualisation comparative de la progression exponentielle du coût et du délai. Survolez chaque palier pour inspecter les valeurs.
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 small">
                <span class="d-flex align-items-center gap-1">
                    <span style="display:inline-block; width:12px; height:12px; background:#f59e0b; border-radius:3px;"></span>
                    <strong class="text-dark">Durée chantier</strong>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span style="display:inline-block; width:12px; height:12px; background:#10b981; border-radius:3px;"></span>
                    <strong class="text-dark">Coût total</strong>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span style="display:inline-block; width:12px; height:12px; background:#06b6d4; border-radius:3px;"></span>
                    <strong class="text-dark">Production / Rendement</strong>
                </span>
            </div>
        </div>

        <div class="position-relative border rounded p-2 bg-light-subtle" style="overflow-x: auto;">
            <svg id="derivationSvgChart" viewBox="0 0 900 240" style="width: 100%; min-width: 600px; height: 240px; display: block;"></svg>
            <div id="chartTooltip" class="position-absolute d-none p-2 rounded shadow-sm bg-dark text-white small" style="pointer-events: none; z-index: 10; font-size: 0.75rem;"></div>
        </div>
    </div>

    <!-- ── TABLEAU DÉTAILLÉ NIVEAU PAR NIVEAU ── -->
    <div class="card-body p-0 border-top">
        <div class="table-responsive">
            <table class="table table-vcenter table-sm table-striped table-hover m-0 align-middle">
                <thead>
                    <tr class="bg-light text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <th class="ps-3 py-2 text-center" style="width: 70px;">Niveau</th>
                        <th class="py-2 text-end"><i class="fa-solid fa-tree text-success me-1"></i>Bois</th>
                        <th class="py-2 text-end"><i class="fa-solid fa-mountain text-primary me-1"></i>Pierre</th>
                        <th class="py-2 text-end"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz</th>
                        <th class="py-2 text-end fw-bold"><i class="fa-solid fa-coins text-secondary me-1"></i>Coût Palier</th>
                        <th class="py-2 text-end fw-bold text-warning"><i class="fa-solid fa-stopwatch me-1"></i>Durée</th>
                        <th class="py-2 text-end text-muted">Durée Cumulée</th>
                        <th class="py-2 text-center"><i class="fa-solid fa-users text-indigo me-1"></i>Travailleurs</th>
                        <th class="py-2 text-end fw-bold text-info"><i class="fa-solid fa-industry me-1"></i>Production / h</th>
                        <th class="py-2 text-center text-info"><i class="fa-solid fa-arrow-up-right-dots me-1"></i>$\Delta$ Prod Net</th>
                        <th class="py-2 text-center"><i class="fa-solid fa-hourglass-end text-purple me-1"></i>Amortissement (ROI)</th>
                        <th class="pe-3 py-2 text-center"><i class="fa-solid fa-chart-line text-secondary me-1"></i>$\Delta$ Coût</th>
                    </tr>
                </thead>
                <tbody id="derivationTableBody" class="font-monospace small">
                    <!-- Généré dynamiquement en JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Catalogue exporté pour la réactivité totale côté client
const CATALOG = <?= json_encode($catalog, JSON_UNESCAPED_UNICODE) ?>;
const CURRENT_GAME_SPEED = <?= (float)$currentGameSpeed ?>;

// État courant de la simulation
let simState = {
    timeCoeff: <?= $currentTimeCoeff ?>,
    timeGrowth: <?= $currentTimeGrowth ?>,
    costCoeff: <?= $currentCostCoeff ?>,
    costGrowth: <?= $currentCostGrowth ?>,
    selectedBuilding: 'hq',
    hqLevel: 1,
    population: 1000,
    maxLevel: 20
};

// Fonctions d'assistance d'affichage et formatage
function formatDuration(sec) {
    sec = Math.round(sec);
    if (sec < 60) return `${sec}s`;
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    if (m < 60) return `${m}m ${s > 0 ? s + 's' : ''}`;
    const h = Math.floor(m / 60);
    const minRest = m % 60;
    if (h < 24) return `${h}h ${minRest > 0 ? minRest + 'm' : ''}`;
    const d = Math.floor(h / 24);
    const hRest = h % 24;
    return `${d}j ${hRest > 0 ? hRest + 'h' : ''}`;
}

function formatNumber(num) {
    return Math.round(num).toLocaleString('fr-FR');
}

// Synchronisation slider <-> input
function updateInputFromRange(field, val, prefix, decimals) {
    val = parseFloat(val);
    document.getElementById(`input_${field}`).value = val.toFixed(decimals);
    document.getElementById(`badge_${field}`).textContent = `${prefix}${val.toFixed(decimals)}`;
    markDirty();
    readFormAndSimulate();
}

function updateRangeFromInput(field, val, prefix, decimals) {
    val = parseFloat(val);
    if (isNaN(val)) return;
    document.getElementById(`range_${field}`).value = val;
    document.getElementById(`badge_${field}`).textContent = `${prefix}${val.toFixed(decimals)}`;
    markDirty();
    readFormAndSimulate();
}

function markDirty() {
    const badge = document.getElementById('sync_status_badge');
    if (badge) {
        badge.className = 'badge bg-warning-lt text-warning d-none d-xl-inline-block py-2';
        badge.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Modifications non enregistrées';
    }
}

function markSynced() {
    const badge = document.getElementById('sync_status_badge');
    if (badge) {
        badge.className = 'badge bg-success-lt text-success d-none d-xl-inline-block py-2';
        badge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Synchronisé avec le jeu';
    }
}

// Application d'un préréglage
function applyDerivationPreset(tCoeff, tGrowth, cCoeff, cGrowth) {
    document.getElementById('input_time_coeff').value = tCoeff.toFixed(2);
    document.getElementById('range_time_coeff').value = tCoeff;
    document.getElementById('badge_time_coeff').textContent = `x${tCoeff.toFixed(2)}`;

    document.getElementById('input_time_growth').value = tGrowth.toFixed(2);
    document.getElementById('range_time_growth').value = tGrowth;
    document.getElementById('badge_time_growth').textContent = tGrowth.toFixed(2);

    document.getElementById('input_cost_coeff').value = cCoeff.toFixed(2);
    document.getElementById('range_cost_coeff').value = cCoeff;
    document.getElementById('badge_cost_coeff').textContent = `x${cCoeff.toFixed(2)}`;

    document.getElementById('input_cost_growth').value = cGrowth.toFixed(2);
    document.getElementById('range_cost_growth').value = cGrowth;
    document.getElementById('badge_cost_growth').textContent = `x${cGrowth.toFixed(2)}`;

    markDirty();
    readFormAndSimulate();
}

// Lecture des valeurs du formulaire et calcul
function readFormAndSimulate() {
    simState.timeCoeff = Math.max(0.05, parseFloat(document.getElementById('input_time_coeff').value) || 1.0);
    simState.timeGrowth = Math.max(1.05, parseFloat(document.getElementById('input_time_growth').value) || 1.28);
    simState.costCoeff = Math.max(0.05, parseFloat(document.getElementById('input_cost_coeff').value) || 1.0);
    simState.costGrowth = Math.max(0.5, parseFloat(document.getElementById('input_cost_growth').value) || 1.0);

    simState.selectedBuilding = document.getElementById('sim_building_select').value;
    simState.hqLevel = parseInt(document.getElementById('sim_hq_level').value) || 1;
    simState.population = parseInt(document.getElementById('sim_population').value) || 0;
    simState.maxLevel = parseInt(document.getElementById('sim_max_level').value) || 20;

    runDerivationSimulation();
}

// Cœur mathématique de simulation de production / rendement
function computeStructureProduction(buildingDef, targetLevel) {
    if (!buildingDef.production || !buildingDef.production.has_prod) {
        return {
            hasProd: false,
            unit: '',
            production: 0,
            deltaProd: 0,
            label: 'Infrastructure'
        };
    }

    const p = buildingDef.production;
    const baseVal = p.base_val;
    let prod = 0;

    switch (p.prod_type) {
        case 'hourly_res':
            // Formule standard des parcelles/mines : base_prod * lvl * 1.15^lvl (ou 1.38 power rural)
            if (buildingDef.category === 'rural') {
                // Formule canonique VillageGeneratorService : 35 * lvl^1.38 * (1 + lvl * 0.015)
                prod = baseVal * Math.pow(targetLevel, 1.38) * (1.0 + (targetLevel * 0.015));
            } else {
                // Formule de mines classiques PlanetEngine
                prod = baseVal * targetLevel * Math.pow(1.15, targetLevel);
            }
            break;

        case 'hourly_serenity':
            // Sanctuaire Shinto : lvl * 30 Sérénité/h
            prod = targetLevel * baseVal;
            break;

        case 'cap_housing':
            // Village Minka : 75 + lvl * 25
            prod = 75 + (targetLevel * baseVal);
            break;

        case 'cap_materials':
        case 'cap_rice':
            // Entrepôts Kura & Matériaux : 15000 * 1.5^lvl
            prod = baseVal * Math.pow(1.5, targetLevel);
            break;

        case 'boost_wood':
        case 'boost_stone':
        case 'boost_rice':
        case 'boost_serenity':
            // Ateliers de transformation spécialisés : +5% par niveau
            prod = targetLevel * baseVal;
            break;

        case 'speed_discount':
            // Tenshu : réduction de temps (1 - 0.964^(lvl-1))*100
            prod = (1 - Math.pow(0.964, Math.max(0, targetLevel - 1))) * 100;
            break;

        default:
            prod = targetLevel * baseVal;
            break;
    }

    return {
        hasProd: true,
        unit: p.unit,
        production: Math.round(prod * 10) / 10,
        label: p.desc_prod
    };
}

// Cœur mathématique de simulation identique à BuildingEngine.php & RuralPlotEngine.php
function computeLevelUpgrade(buildingDef, targetLevel) {
    const curLevel = targetLevel - 1;
    let metal = 0, crystal = 0, deut = 0, duration = 0;

    if (buildingDef.category === 'rural') {
        // Formule dédiée aux 9 parcelles du Terroir Vivant (RuralPlotEngine)
        const polyFactor = 1.0 + (0.22 * Math.pow(Math.max(0, curLevel), 1.45));
        const expFactor = Math.pow(1.12 * simState.costGrowth, Math.min(curLevel, 40));
        const mult = polyFactor * expFactor * simState.costCoeff;

        metal = Math.max(0, Math.round(buildingDef.base_cost.metal * mult));
        crystal = Math.max(0, Math.round(buildingDef.base_cost.crystal * mult));
        deut = Math.max(0, Math.round(buildingDef.base_cost.deuterium * mult));

        const baseSec = 180.0 * (1.0 + 0.16 * Math.pow(curLevel, 1.35)) * Math.pow(1.065, Math.min(curLevel, 55)) * Math.pow(1.025, Math.max(0, curLevel - 55));
        const hqFactor = 1.0 + (Math.max(1, simState.hqLevel) * 0.05);
        const popBonus = Math.min(0.25, Math.max(0, simState.population / 100) * 0.01);
        const popFactor = 1.0 / (1.0 + popBonus);

        const rawDur = ((baseSec / hqFactor) * simState.timeCoeff * popFactor) / CURRENT_GAME_SPEED;
        duration = Math.max(10, Math.round(rawDur));
    } else {
        // Formule standard des édifices urbains & parcelles classiques
        const baseMult = buildingDef.cost_multiplier;
        const effectiveMult = Math.pow(baseMult * simState.costGrowth, curLevel);

        metal = Math.max(0, Math.round(buildingDef.base_cost.metal * effectiveMult * simState.costCoeff));
        crystal = Math.max(0, Math.round(buildingDef.base_cost.crystal * effectiveMult * simState.costCoeff));
        deut = Math.max(0, Math.round(buildingDef.base_cost.deuterium * effectiveMult * simState.costCoeff));

        const hqFactor = Math.pow(0.964, Math.max(0, simState.hqLevel - 1));
        const lvlFactor = Math.pow(simState.timeGrowth, curLevel) * Math.pow(targetLevel, 0.85);
        const popBonus = Math.min(0.25, Math.max(0, simState.population / 100) * 0.01);
        const popFactor = 1.0 / (1.0 + popBonus);

        const rawDuration = (buildingDef.base_time * lvlFactor * hqFactor * popFactor * simState.timeCoeff) / CURRENT_GAME_SPEED;
        duration = Math.max(5, Math.round(rawDuration));
    }

    const totalCost = metal + crystal + deut;
    const prodInfo = computeStructureProduction(buildingDef, targetLevel);
    const workers = targetLevel * (buildingDef.workers_rate || 0);

    return {
        level: targetLevel,
        metal,
        crystal,
        deut,
        totalCost,
        duration,
        workers,
        ...prodInfo
    };
}

function runDerivationSimulation() {
    const bDef = CATALOG[simState.selectedBuilding] || CATALOG['hq'];
    const maxLvl = simState.maxLevel;

    let rows = [];
    let cumCost = 0;
    let cumDuration = 0;

    for (let lvl = 1; lvl <= maxLvl; lvl++) {
        const item = computeLevelUpgrade(bDef, lvl);
        cumCost += item.totalCost;
        cumDuration += item.duration;

        let deltaCostPct = 0;
        let deltaTimePct = 0;
        let deltaProd = item.production;
        let paybackHours = null;

        if (rows.length > 0) {
            const prev = rows[rows.length - 1];
            deltaCostPct = prev.totalCost > 0 ? ((item.totalCost - prev.totalCost) / prev.totalCost) * 100 : 0;
            deltaTimePct = prev.duration > 0 ? ((item.duration - prev.duration) / prev.duration) * 100 : 0;
            deltaProd = Math.max(0, item.production - prev.production);

            // Temps de retour sur investissement (ROI) = Coût palier / Gain de production horaire
            if (item.hasProd && deltaProd > 0) {
                paybackHours = item.totalCost / deltaProd;
            }
        } else {
            if (item.hasProd && item.production > 0) {
                paybackHours = item.totalCost / item.production;
            }
        }

        rows.push({
            ...item,
            cumCost,
            cumDuration,
            deltaCostPct,
            deltaTimePct,
            deltaProd: Math.round(deltaProd * 10) / 10,
            paybackHours
        });
    }

    renderKPIs(rows, bDef);
    renderTable(rows, bDef);
    renderSvgChart(rows, bDef);
}

function renderKPIs(rows, bDef) {
    if (!rows.length) return;
    const first = rows[0];
    const last = rows[rows.length - 1];

    document.getElementById('kpi_duration_range').textContent = `${formatDuration(first.duration)} → ${formatDuration(last.duration)}`;
    document.getElementById('kpi_duration_sub').textContent = `Ratio de dérivation : x${(last.duration / Math.max(1, first.duration)).toFixed(1)}`;

    document.getElementById('kpi_total_time').textContent = formatDuration(last.cumDuration);

    document.getElementById('kpi_cost_range').textContent = `${formatNumber(first.totalCost)} → ${formatNumber(last.totalCost)}`;
    document.getElementById('kpi_cost_sub').textContent = `Ratio d'inflation : x${(last.totalCost / Math.max(1, first.totalCost)).toFixed(1)}`;

    document.getElementById('kpi_total_cost').textContent = formatNumber(last.cumCost);

    // KPI 5 : Rendement / Production
    const kpiProdRange = document.getElementById('kpi_prod_range');
    const kpiProdSub = document.getElementById('kpi_prod_sub');
    if (last.hasProd) {
        kpiProdRange.textContent = `${formatNumber(last.production)} ${last.unit}`;
        
        // Moyenne d'amortissement
        const validPaybacks = rows.map(r => r.paybackHours).filter(p => p !== null && isFinite(p) && p > 0);
        if (validPaybacks.length > 0) {
            const avgPayback = validPaybacks.reduce((a, b) => a + b, 0) / validPaybacks.length;
            const avgH = Math.round(avgPayback);
            if (avgH < 24) {
                kpiProdSub.textContent = `ROI moyen : ~${avgH}h (${(avgH / 24).toFixed(1)}j)`;
            } else {
                kpiProdSub.textContent = `ROI moyen : ~${Math.round(avgH / 24)} jours d'amortissement`;
            }
        } else {
            kpiProdSub.textContent = `Plafond au Niv ${last.level}`;
        }
    } else {
        kpiProdRange.textContent = 'Infrastructure';
        kpiProdSub.textContent = 'Bâtiment militaire ou stratégique';
    }
}

function renderTable(rows, bDef) {
    const tbody = document.getElementById('derivationTableBody');
    tbody.innerHTML = '';

    rows.forEach(r => {
        const tr = document.createElement('tr');
        const costDeltaBadge = r.level === 1 
            ? '<span class="text-muted">-</span>' 
            : `<span class="badge bg-secondary-lt text-secondary">+${Math.round(r.deltaCostPct)}%</span>`;

        // Formatage production
        let prodDisplay = '<span class="text-muted small">Infrastructure</span>';
        let deltaProdDisplay = '<span class="text-muted">-</span>';
        let roiDisplay = '<span class="text-muted">-</span>';

        if (r.hasProd) {
            prodDisplay = `<span class="fw-bold text-info">${formatNumber(r.production)}</span> <span class="text-muted" style="font-size:0.7rem;">${r.unit}</span>`;
            deltaProdDisplay = `<span class="badge bg-info-lt text-info">+${formatNumber(r.deltaProd)}</span>`;

            if (r.paybackHours !== null && isFinite(r.paybackHours) && r.paybackHours > 0) {
                const hours = Math.round(r.paybackHours);
                if (hours < 24) {
                    roiDisplay = `<span class="badge bg-purple-lt text-purple fw-bold">${hours}h</span>`;
                } else {
                    const days = (hours / 24).toFixed(1);
                    roiDisplay = `<span class="badge bg-purple-lt text-purple fw-bold">${days}j (${hours}h)</span>`;
                }
            } else {
                roiDisplay = '<span class="text-muted">N/A</span>';
            }
        }

        let workersDisplay = r.workers > 0 ? `<span class="badge bg-indigo-lt text-indigo"><i class="fa-solid fa-user me-1"></i>${formatNumber(r.workers)}</span>` : `<span class="text-muted">-</span>`;

        tr.innerHTML = `
            <td class="ps-3 text-center fw-bold">
                <span class="badge bg-dark-lt text-dark">Niv ${r.level}</span>
            </td>
            <td class="text-end text-success">${formatNumber(r.metal)}</td>
            <td class="text-end text-primary">${formatNumber(r.crystal)}</td>
            <td class="text-end text-warning">${formatNumber(r.deut)}</td>
            <td class="text-end fw-bold text-dark">${formatNumber(r.totalCost)}</td>
            <td class="text-end fw-bold text-warning">${formatDuration(r.duration)}</td>
            <td class="text-end text-muted small">${formatDuration(r.cumDuration)}</td>
            <td class="text-center">${workersDisplay}</td>
            <td class="text-end">${prodDisplay}</td>
            <td class="text-center">${deltaProdDisplay}</td>
            <td class="text-center">${roiDisplay}</td>
            <td class="pe-3 text-center">${costDeltaBadge}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Rendu SVG interactif haute fidélité avec triple courbe (Durée, Coût, Production)
function renderSvgChart(rows, bDef) {
    const svg = document.getElementById('derivationSvgChart');
    if (!svg || rows.length < 2) return;

    const width = 900;
    const height = 240;
    const padX = 50;
    const padY = 30;
    const plotW = width - padX * 2;
    const plotH = height - padY * 2;

    const maxCost = Math.max(...rows.map(r => r.totalCost));
    const maxDur = Math.max(...rows.map(r => r.duration));
    const maxProd = Math.max(...rows.map(r => r.production || 0));

    const numPoints = rows.length;
    const stepX = plotW / (numPoints - 1);

    // Points normalisés pour l'échelle SVG
    const costPoints = rows.map((r, i) => {
        const x = padX + i * stepX;
        const norm = maxCost > 0 ? (r.totalCost / maxCost) : 0;
        const y = height - padY - (norm * plotH);
        return { x, y, data: r };
    });

    const durPoints = rows.map((r, i) => {
        const x = padX + i * stepX;
        const norm = maxDur > 0 ? (r.duration / maxDur) : 0;
        const y = height - padY - (norm * plotH);
        return { x, y, data: r };
    });

    const hasProd = rows.some(r => r.hasProd && r.production > 0);
    const prodPoints = hasProd ? rows.map((r, i) => {
        const x = padX + i * stepX;
        const norm = maxProd > 0 ? (r.production / maxProd) : 0;
        const y = height - padY - (norm * plotH);
        return { x, y, data: r };
    }) : [];

    // Génération des chemins SVG (smooth line)
    function generatePath(pts) {
        return pts.map((p, i) => (i === 0 ? `M ${p.x} ${p.y}` : `L ${p.x} ${p.y}`)).join(' ');
    }

    const pathCost = generatePath(costPoints);
    const pathDur = generatePath(durPoints);
    const pathProd = prodPoints.length > 0 ? generatePath(prodPoints) : '';

    // Grille de fond
    let gridLines = '';
    for (let g = 0; g <= 4; g++) {
        const gy = padY + (g / 4) * plotH;
        gridLines += `<line x1="${padX}" y1="${gy}" x2="${width - padX}" y2="${gy}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3,3" />`;
    }

    // Éléments du SVG
    svg.innerHTML = `
        <defs>
            <linearGradient id="gradCost" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#10b981" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
            </linearGradient>
            <linearGradient id="gradDur" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#f59e0b" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.0"/>
            </linearGradient>
            <linearGradient id="gradProd" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.25"/>
                <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0"/>
            </linearGradient>
        </defs>
        ${gridLines}

        <!-- Lignes et remplissages -->
        <path d="${pathCost} L ${padX + (numPoints - 1) * stepX} ${height - padY} L ${padX} ${height - padY} Z" fill="url(#gradCost)"/>
        <path d="${pathCost}" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>

        <path d="${pathDur} L ${padX + (numPoints - 1) * stepX} ${height - padY} L ${padX} ${height - padY} Z" fill="url(#gradDur)"/>
        <path d="${pathDur}" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>

        ${hasProd ? `
            <path d="${pathProd} L ${padX + (numPoints - 1) * stepX} ${height - padY} L ${padX} ${height - padY} Z" fill="url(#gradProd)"/>
            <path d="${pathProd}" fill="none" stroke="#06b6d4" stroke-width="3" stroke-dasharray="4,2" stroke-linecap="round"/>
        ` : ''}
        
        <!-- Points interactifs -->
        ${durPoints.map((p, i) => `
            <circle cx="${p.x}" cy="${p.y}" r="4" fill="#f59e0b" stroke="#ffffff" stroke-width="2" class="chart-point" data-type="dur" data-idx="${i}" style="cursor:pointer;" />
        `).join('')}
        ${costPoints.map((p, i) => `
            <circle cx="${p.x}" cy="${p.y}" r="4" fill="#10b981" stroke="#ffffff" stroke-width="2" class="chart-point" data-type="cost" data-idx="${i}" style="cursor:pointer;" />
        `).join('')}
        ${prodPoints.map((p, i) => `
            <circle cx="${p.x}" cy="${p.y}" r="4" fill="#06b6d4" stroke="#ffffff" stroke-width="2" class="chart-point" data-type="prod" data-idx="${i}" style="cursor:pointer;" />
        `).join('')}

        <!-- Axe X (Niveaux) -->
        ${rows.map((r, i) => (i % 2 === 0 || i === rows.length - 1) ? `
            <text x="${padX + i * stepX}" y="${height - 10}" font-size="11" font-family="monospace" fill="#64748b" text-anchor="middle">N${r.level}</text>
        ` : '').join('')}
    `;

    // Gestion du tooltip interactif
    const tooltip = document.getElementById('chartTooltip');
    const points = svg.querySelectorAll('.chart-point');

    points.forEach(pt => {
        pt.addEventListener('mouseenter', e => {
            const idx = parseInt(pt.getAttribute('data-idx'));
            const row = rows[idx];
            if (!row) return;

            pt.setAttribute('r', '7');
            let prodLine = '';
            if (row.hasProd) {
                const roiText = (row.paybackHours !== null && isFinite(row.paybackHours)) 
                    ? ` (ROI: ${Math.round(row.paybackHours)}h)` 
                    : '';
                prodLine = `<div><i class="fa-solid fa-industry me-1 text-info"></i>Production : <strong>${formatNumber(row.production)} ${row.unit}</strong>${roiText}</div>`;
            }

            let workersLine = row.workers > 0 ? `<div><i class="fa-solid fa-users me-1 text-indigo"></i>Travailleurs : <strong>${formatNumber(row.workers)}</strong></div>` : '';

            tooltip.innerHTML = `
                <div class="fw-bold mb-1 text-warning">Palier Niveau ${row.level}</div>
                <div><i class="fa-solid fa-stopwatch me-1 text-warning"></i>Durée : <strong>${formatDuration(row.duration)}</strong></div>
                <div><i class="fa-solid fa-coins me-1 text-success"></i>Coût : <strong>${formatNumber(row.totalCost)}</strong> ressources</div>
                ${workersLine}
                ${prodLine}
                <div class="text-white-50 mt-1" style="font-size:0.7rem;">🪵 ${formatNumber(row.metal)} | 🪨 ${formatNumber(row.crystal)} | 🌾 ${formatNumber(row.deut)}</div>
            `;
            tooltip.classList.remove('d-none');
        });

        pt.addEventListener('mousemove', e => {
            const svgRect = svg.getBoundingClientRect();
            const relX = e.clientX - svgRect.left;
            const relY = e.clientY - svgRect.top;
            tooltip.style.left = `${relX + 15}px`;
            tooltip.style.top = `${relY - 30}px`;
        });

        pt.addEventListener('mouseleave', () => {
            pt.setAttribute('r', '4');
            tooltip.classList.add('d-none');
        });
    });
}

// Sauvegarde officielle des coefficients vers le jeu en ligne
async function saveBuildingDerivation(event) {
    if (event) event.preventDefault();

    const btn = document.getElementById('btn_save_derivation');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Application...';

    const formData = new FormData();
    formData.append('action', 'save_building_derivation');
    formData.append('building_time_coeff', simState.timeCoeff);
    formData.append('building_time_growth', simState.timeGrowth);
    formData.append('building_cost_coeff', simState.costCoeff);
    formData.append('building_cost_growth', simState.costGrowth);

    try {
        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            markSynced();
            // Notification visuelle
            const alertBox = document.getElementById('dev-alert');
            const alertContent = document.getElementById('dev-alert-content');
            if (alertBox && alertContent) {
                alertContent.innerHTML = `<strong>Équilibrage Appliqué !</strong> ${data.message}`;
                alertBox.className = 'alert alert-success alert-dismissible shadow-sm mb-3';
                alertBox.classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(data.message);
            }
        } else {
            alert('Erreur lors de la sauvegarde : ' + (data.error || 'Accès refusé.'));
        }
    } catch (err) {
        alert('Erreur réseau lors de la communication avec le serveur.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
    readFormAndSimulate();
});
</script>

