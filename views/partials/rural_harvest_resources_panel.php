<?php
/**
 * Panneau Mutualisé : Bilan Intégral des Récoltes, Stocks & Oasis Féodales (OpenShogun)
 * Utilisable universellement sur la vue Terroir (resources.php) et la Cité Castrale (city.php).
 * Intègre l'ENSEMBLE des composantes de production :
 * 1. Matières premières fondamentales (Bois, Pierre, Riz) avec cadences nettes, stocks, jauges & décomposition des facteurs
 * 2. Terroirs agricoles spécialisés (Argile & Céramique, Thé & Matcha, Soja & Miso) avec niveaux, rendements & ouvriers
 * 3. Vivres raffinées & réserves stratégiques (Farine de Riz, Saké des Festivités, Poutres de Charpente)
 * 4. Ferveur divine & Alimentation énergétique (Sanctuaire Shintō, Pavillon de thé, ratio d'efficacité)
 * 5. Démographie & Mobilisation ouvrière (Logements Minka, effectifs requis vs en poste, satisfaction)
 * 6. Matrice des multiplicateurs actifs (Ateliers urbains, Héros Samouraï, Fête Matsuri, Oasis)
 * 7. Oasis sauvages annexées sous tutelle (statut, coordonnées & bonus)
 */

require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';
require_once __DIR__ . '/../../core/OasisEngine.php';
require_once __DIR__ . '/../../core/PopulationEngine.php';
require_once __DIR__ . '/../../core/HeroEngine.php';
require_once __DIR__ . '/../../core/GameConfig.php';

// Initialisation sécurisée des données du domaine si non fournies par le contexte parent
if (!isset($planet) || empty($planet['id'])) {
    return;
}

$planetId = (int)$planet['id'];

// Récupérer les bâtiments et niveaux si non fournis
if (!isset($buildings)) {
    $planetEngine = $planetEngine ?? new PlanetEngine();
    $buildings = $planetEngine->getBuildings($planetId);
}
$hqLevel = $hqLevel ?? (int)($buildings['hq'] ?? 1);

// Récupérer les parcelles rurales enrichies si non fournies
if (!isset($plots) || !is_array($plots)) {
    $ruralPlotEngine = $ruralPlotEngine ?? new RuralPlotEngine();
    $plots = $ruralPlotEngine->getEnrichedPlots($planetId, $planet, $hqLevel);
}

// Récupérer la capacité de logement si non fournie
if (!isset($maxPop)) {
    $ruralPlotEngine = $ruralPlotEngine ?? new RuralPlotEngine();
    $housingCap = $ruralPlotEngine->getVillageHousingCapacity($planetId);
    $maxPop = (int)($housingCap['total_capacity'] ?? ($planet['population_max'] ?? 100));
}

// Récupérer les productions horaires de base si absentes
if (empty($planet['prod_rates']) && !isset($prodRates)) {
    $planetEngine = $planetEngine ?? new PlanetEngine();
    $fields = $fields ?? $planetEngine->getFields($planetId);
    $prodRates = $planetEngine->calculateProduction($fields, $planetId);
} else {
    $prodRates = $prodRates ?? $planet['prod_rates'];
}

// Récupérer les oasis annexées si non fournies
if (!isset($annexedOases)) {
    $oasisEngine = $oasisEngine ?? new OasisEngine();
    $annexedOases = $oasisEngine->getAnnexedOasesForPlanet($planetId);
}

// Vitesse du serveur et productions naturelles de base
$speed = (float)GameConfig::get('resource_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5);
$metalBase = (int)(20 * $speed);
$crystalBase = (int)(15 * $speed);
$deutBase = (int)(10 * $speed);

// ====================================================
// CALCUL DES STOCKS ET CAPACITÉS MAXIMALES
// ====================================================
// 1. Matières premières
$stockMetal = (float)($planet['metal'] ?? 0);
$maxMetal = max(1, (float)($planet['metal_max'] ?? 15000));
$pctMetal = min(100, round(($stockMetal / $maxMetal) * 100, 1));

$stockCrystal = (float)($planet['crystal'] ?? 0);
$maxCrystal = max(1, (float)($planet['crystal_max'] ?? 15000));
$pctCrystal = min(100, round(($stockCrystal / $maxCrystal) * 100, 1));

$stockDeut = (float)($planet['deuterium'] ?? 0);
$maxDeut = max(1, (float)($planet['deuterium_max'] ?? 15000));
$pctDeut = min(100, round(($stockDeut / $maxDeut) * 100, 1));

// 2. Vivres raffinées & matériaux de charpente
$stockFlour = (float)($planet['rice_flour'] ?? 0);
$maxFlour = max(1, (float)($planet['rice_flour_max'] ?? 10000));
$pctFlour = min(100, round(($stockFlour / $maxFlour) * 100, 1));

$stockSake = (float)($planet['sake'] ?? 0);
$maxSake = max(1, (float)($planet['sake_max'] ?? 10000));
$pctSake = min(100, round(($stockSake / $maxSake) * 100, 1));

$stockBeams = (float)($planet['wooden_beams'] ?? 0);
$maxBeams = max(1, (float)($planet['wooden_beams_max'] ?? 10000));
$pctBeams = min(100, round(($stockBeams / $maxBeams) * 100, 1));

// ====================================================
// BONUS & MULTIPLICATEURS DE RENDEMENT
// ====================================================
// Bonus du Samouraï Héros
$heroBonuses = $prodRates['hero_bonuses'] ?? ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
$heroBonusMetal = (int)($heroBonuses['metal'] ?? 0);
$heroBonusCrystal = (int)($heroBonuses['crystal'] ?? 0);
$heroBonusDeut = (int)($heroBonuses['deuterium'] ?? 0);
$hasHeroBonus = ($heroBonusMetal > 0 || $heroBonusCrystal > 0 || $heroBonusDeut > 0);

// Bonus des Ateliers Urbains
$buildingBonuses = $prodRates['building_bonuses'] ?? [];
$sawmillBonus = (int)($buildingBonuses['sawmill'] ?? 0);
$stonemasonBonus = (int)($buildingBonuses['stonemason'] ?? 0);
$grainMillBonus = (int)($buildingBonuses['grain_mill'] ?? 0);
$teahouseBonus = (int)($buildingBonuses['teahouse'] ?? 0);

// Bonus des Oasis Annexées
$oasisBonuses = $prodRates['oasis_bonuses'] ?? ['wood' => 0, 'stone' => 0, 'rice' => 0];

// Bonus du Matsuri Populaire
$matsuriBonus = (int)($prodRates['matsuri_bonus'] ?? 0);

// ====================================================
// ÉNERGIE, FERVEUR SPIRITUELLE & ALIMENTATION
// ====================================================
$energyMax = (int)($prodRates['energy_max'] ?? ($planet['energy_max'] ?? 20));
$energyUsed = (int)($prodRates['energy_used'] ?? ($planet['energy_used'] ?? 0));
$energyNet = $energyMax - $energyUsed;
$energyRatio = (float)($prodRates['energy_ratio'] ?? 1.0);
$pctEnergyUsed = ($energyMax > 0) ? min(100, round(($energyUsed / $energyMax) * 100, 1)) : 100;
$shrineHourly = (float)($plots['sanctuaire_shinto']['prod_hourly'] ?? 0);

// ====================================================
// POPULATION & MAIN-D'ŒUVRE
// ====================================================
$curPop = (int)($planet['population'] ?? 0);
$pctPop = ($maxPop > 0) ? min(100, round(($curPop / $maxPop) * 100, 1)) : 0;
$contentmentVal = (int)($planet['contentment'] ?? 100);

$workforceSummary = $prodRates['workforce'] ?? null;
if (!$workforceSummary && class_exists('PopulationEngine')) {
    $fields = $fields ?? ($planetEngine ? $planetEngine->getFields($planetId) : []);
    $buildings = $buildings ?? ($planetEngine ? $planetEngine->getBuildings($planetId) : []);
    $workforceSummary = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop);
}
$workforceRatio = (float)($prodRates['workforce_ratio'] ?? ($workforceSummary['workforce_ratio'] ?? 1.0));
$assignedWorkers = (int)($workforceSummary['assigned_workers'] ?? $curPop);
$requiredWorkers = (int)($workforceSummary['required_workers'] ?? 0);

// Efficacité globale nette de production (Énergie × Main-d'œuvre)
$globalEfficiencyPct = (int)round($energyRatio * $workforceRatio * 100);

// ====================================================
// SPÉCIALITÉS DU TERROIR RURAL (ARGILE, THÉ, SOJA)
// ====================================================
$clayHourly = (float)($plots['fosse_argile']['prod_hourly'] ?? 0);
$clayLvl = (int)($plots['fosse_argile']['level'] ?? 0);
$clayMaxLvl = (int)($plots['fosse_argile']['max_level'] ?? 30);
$clayProgress = ($clayMaxLvl > 0) ? min(100, round(($clayLvl / $clayMaxLvl) * 100)) : 0;
$clayWorkers = (int)($plots['fosse_argile']['workers_assigned'] ?? 0);

$teaHourly = (float)($plots['culture_the']['prod_hourly'] ?? 0);
$teaLvl = (int)($plots['culture_the']['level'] ?? 0);
$teaMaxLvl = (int)($plots['culture_the']['max_level'] ?? 30);
$teaProgress = ($teaMaxLvl > 0) ? min(100, round(($teaLvl / $teaMaxLvl) * 100)) : 0;
$teaWorkers = (int)($plots['culture_the']['workers_assigned'] ?? 0);

$soyHourly = (float)($plots['champ_soja']['prod_hourly'] ?? 0);
$soyLvl = (int)($plots['champ_soja']['level'] ?? 0);
$soyMaxLvl = (int)($plots['champ_soja']['max_level'] ?? 30);
$soyProgress = ($soyMaxLvl > 0) ? min(100, round(($soyLvl / $soyMaxLvl) * 100)) : 0;
$soyWorkers = (int)($plots['champ_soja']['workers_assigned'] ?? 0);

$panelTitle = $panelTitle ?? 'Récoltes, Stocks & Oasis';
?>

<div class="card shadow-sm border-0 mb-3" id="ruralHarvestPanel">
    <!-- En-tête du Panneau Stratégique -->
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-dark text-white">
        <h3 class="card-title mb-0 fs-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-warning"></i>
            <span><?= htmlspecialchars($panelTitle) ?></span>
        </h3>
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-<?= ($globalEfficiencyPct >= 100) ? 'success' : 'warning' ?>-lt font-monospace" style="font-size:0.7rem;" title="Rendement global du fief (Énergie <?= round($energyRatio * 100) ?>% &times; Ouvriers <?= round($workforceRatio * 100) ?>%)">
                <i class="fa-solid fa-gauge-high me-1"></i><?= $globalEfficiencyPct ?>% Rendement
            </span>
            <span class="badge bg-secondary-lt text-white font-monospace" style="font-size:0.7rem;">
                <?= count($annexedOases) ?> / 3 Oasis
            </span>
        </div>
    </div>

    <div class="card-body p-3">

        <!-- ====================================================
             1. MATIÈRES PREMIÈRES & ENTREPÔTS (BOIS, PIERRE, RIZ)
             ==================================================== -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-warehouse me-1 text-primary"></i>Matières Premières &amp; Entrepôts
            </div>
            <span class="text-muted font-monospace" style="font-size:0.65rem;">Cadence / heure</span>
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Bois de Cèdre -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-tree text-success me-1"></i>
                        <strong>Bois de Cèdre</strong>
                        <?php if ($pctMetal >= 95): ?>
                            <span class="badge bg-danger text-white py-0 px-1" style="font-size:0.65rem;">Plein</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-success-lt font-monospace fw-bold">
                            +<?= number_format($prodRates['metal'] ?? 0) ?>/h
                        </span>
                        <?php if ($heroBonusMetal > 0): ?>
                            <span class="badge bg-primary-lt text-primary font-monospace py-0 px-1" style="font-size:0.65rem;" title="Bénédiction du Samouraï Héros incluse (+<?= number_format($heroBonusMetal) ?>/h)">
                                <i class="fa-solid fa-user-ninja me-0.5"></i>+<?= number_format($heroBonusMetal) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Stock : <strong class="text-body"><?= number_format($stockMetal) ?></strong> / <?= number_format($maxMetal) ?></span>
                    <span class="<?= ($pctMetal >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctMetal ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctMetal >= 90) ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= $pctMetal ?>%;"></div>
                </div>
                <!-- Décomposition des facteurs de production -->
                <div class="d-flex flex-wrap gap-1 mt-1 font-monospace" style="font-size:0.65rem;">
                    <span class="badge bg-light text-secondary border py-0 px-1" title="Production naturelle du domaine">Base: +<?= number_format($metalBase) ?></span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" title="Apport de la Forêt de Cèdres">Forêt Niv.<?= $plots['foret']['level'] ?? 1 ?>: +<?= number_format($plots['foret']['prod_hourly'] ?? 0) ?></span>
                    <?php if ($sawmillBonus > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0 px-1" title="Atelier Scierie">Scierie: +<?= $sawmillBonus ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($oasisBonuses['wood'])): ?>
                        <span class="badge bg-teal-subtle text-teal border border-teal-subtle py-0 px-1" title="Bonus d'Oasis annexées">Oasis: +<?= (int)$oasisBonuses['wood'] ?>%</span>
                    <?php endif; ?>
                    <?php if ($heroBonusMetal > 0): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1" title="Bénédiction du Samouraï Héros">Héros: +<?= number_format($heroBonusMetal) ?></span>
                    <?php endif; ?>
                    <?php if ($matsuriBonus > 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0 px-1" title="Célébration Matsuri">Matsuri: +<?= $matsuriBonus ?>%</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pierre de Taille -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-mountain text-secondary me-1"></i>
                        <strong>Pierre de Taille</strong>
                        <?php if ($pctCrystal >= 95): ?>
                            <span class="badge bg-danger text-white py-0 px-1" style="font-size:0.65rem;">Plein</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-secondary-lt font-monospace fw-bold">
                            +<?= number_format($prodRates['crystal'] ?? 0) ?>/h
                        </span>
                        <?php if ($heroBonusCrystal > 0): ?>
                            <span class="badge bg-primary-lt text-primary font-monospace py-0 px-1" style="font-size:0.65rem;" title="Bénédiction du Samouraï Héros incluse (+<?= number_format($heroBonusCrystal) ?>/h)">
                                <i class="fa-solid fa-user-ninja me-0.5"></i>+<?= number_format($heroBonusCrystal) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Stock : <strong class="text-body"><?= number_format($stockCrystal) ?></strong> / <?= number_format($maxCrystal) ?></span>
                    <span class="<?= ($pctCrystal >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctCrystal ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctCrystal >= 90) ? 'bg-danger' : 'bg-secondary' ?>" role="progressbar" style="width: <?= $pctCrystal ?>%;"></div>
                </div>
                <!-- Décomposition des facteurs de production -->
                <div class="d-flex flex-wrap gap-1 mt-1 font-monospace" style="font-size:0.65rem;">
                    <span class="badge bg-light text-secondary border py-0 px-1" title="Production naturelle du domaine">Base: +<?= number_format($crystalBase) ?></span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-0 px-1" title="Apport de la Carrière de Granit">Carrière Niv.<?= $plots['carriere']['level'] ?? 1 ?>: +<?= number_format($plots['carriere']['prod_hourly'] ?? 0) ?></span>
                    <?php if ($stonemasonBonus > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0 px-1" title="Atelier Briqueterie / Tailleuse">Tailleuse: +<?= $stonemasonBonus ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($oasisBonuses['stone'])): ?>
                        <span class="badge bg-teal-subtle text-teal border border-teal-subtle py-0 px-1" title="Bonus d'Oasis annexées">Oasis: +<?= (int)$oasisBonuses['stone'] ?>%</span>
                    <?php endif; ?>
                    <?php if ($heroBonusCrystal > 0): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1" title="Bénédiction du Samouraï Héros">Héros: +<?= number_format($heroBonusCrystal) ?></span>
                    <?php endif; ?>
                    <?php if ($matsuriBonus > 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0 px-1" title="Célébration Matsuri">Matsuri: +<?= $matsuriBonus ?>%</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Riz Impérial -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-wheat-awn text-warning me-1"></i>
                        <strong>Riz Impérial (Koku)</strong>
                        <?php if ($pctDeut >= 95): ?>
                            <span class="badge bg-danger text-white py-0 px-1" style="font-size:0.65rem;">Plein</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-warning-lt font-monospace fw-bold">
                            +<?= number_format($prodRates['deuterium'] ?? 0) ?>/h
                        </span>
                        <?php if ($heroBonusDeut > 0): ?>
                            <span class="badge bg-primary-lt text-primary font-monospace py-0 px-1" style="font-size:0.65rem;" title="Bénédiction du Samouraï Héros incluse (+<?= number_format($heroBonusDeut) ?>/h)">
                                <i class="fa-solid fa-user-ninja me-0.5"></i>+<?= number_format($heroBonusDeut) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Grenier : <strong class="text-body"><?= number_format($stockDeut) ?></strong> / <?= number_format($maxDeut) ?></span>
                    <span class="<?= ($pctDeut >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctDeut ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctDeut >= 90) ? 'bg-danger' : 'bg-warning' ?>" role="progressbar" style="width: <?= $pctDeut ?>%;"></div>
                </div>
                <!-- Décomposition des facteurs de production -->
                <div class="d-flex flex-wrap gap-1 mt-1 font-monospace" style="font-size:0.65rem;">
                    <span class="badge bg-light text-secondary border py-0 px-1" title="Production naturelle du domaine">Base: +<?= number_format($deutBase) ?></span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0 px-1" title="Apport de la Rizière Inondée">Rizière Niv.<?= $plots['riziere']['level'] ?? 1 ?>: +<?= number_format($plots['riziere']['prod_hourly'] ?? 0) ?></span>
                    <?php if ($grainMillBonus > 0): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0 px-1" title="Atelier Meunerie de Riz">Meunerie: +<?= $grainMillBonus ?>%</span>
                    <?php endif; ?>
                    <?php if (!empty($oasisBonuses['rice'])): ?>
                        <span class="badge bg-teal-subtle text-teal border border-teal-subtle py-0 px-1" title="Bonus d'Oasis annexées">Oasis: +<?= (int)$oasisBonuses['rice'] ?>%</span>
                    <?php endif; ?>
                    <?php if ($heroBonusDeut > 0): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1" title="Bénédiction du Samouraï Héros">Héros: +<?= number_format($heroBonusDeut) ?></span>
                    <?php endif; ?>
                    <?php if ($matsuriBonus > 0): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0 px-1" title="Célébration Matsuri">Matsuri: +<?= $matsuriBonus ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ====================================================
             2. TERROIRS AGRICOLES SPÉCIALISÉS (ARGILE, THÉ, SOJA)
             ==================================================== -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-seedling me-1 text-warning"></i>Terroirs Spécialisés &amp; Récoltes
            </div>
            <span class="text-muted font-monospace" style="font-size:0.65rem;">Progression 1-100</span>
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Argile & Céramique -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-cubes-stacked text-orange me-1"></i>
                        <span>Argile &amp; Céramique</span>
                    </div>
                    <span class="badge bg-orange-lt font-monospace fw-bold">
                        +<?= number_format($clayHourly) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Fosse : <strong class="text-body">Niv. <?= $clayLvl ?></strong> / <?= $clayMaxLvl ?> &bull; <?= $clayWorkers ?> ouvriers</span>
                    <span><?= $clayProgress ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-orange" role="progressbar" style="width: <?= $clayProgress ?>%;"></div>
                </div>
            </div>

            <!-- Thé & Matcha -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-leaf text-teal me-1"></i>
                        <span>Thé &amp; Matcha</span>
                        <?php if ($teahouseBonus > 0): ?>
                            <span class="badge bg-teal-lt text-teal py-0 px-1" style="font-size:0.65rem;" title="Pavillon de thé actif (+<?= $teahouseBonus ?>% Sérénité)">Pavillon</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-teal-lt font-monospace fw-bold">
                        +<?= number_format($teaHourly) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Coteaux : <strong class="text-body">Niv. <?= $teaLvl ?></strong> / <?= $teaMaxLvl ?> &bull; <?= $teaWorkers ?> ouvriers</span>
                    <span><?= $teaProgress ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-teal" role="progressbar" style="width: <?= $teaProgress ?>%;"></div>
                </div>
            </div>

            <!-- Fèves de Soja & Miso -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-seedling text-lime me-1"></i>
                        <span>Fèves de Soja</span>
                    </div>
                    <span class="badge bg-lime-lt font-monospace fw-bold">
                        +<?= number_format($soyHourly) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Sillons : <strong class="text-body">Niv. <?= $soyLvl ?></strong> / <?= $soyMaxLvl ?> &bull; <?= $soyWorkers ?> ouvriers</span>
                    <span><?= $soyProgress ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-lime" role="progressbar" style="width: <?= $soyProgress ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- ====================================================
             3. VIVRES RAFFINÉES & RÉSERVES STRATÉGIQUES (FARINE, SAKÉ, POUTRES)
             ==================================================== -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-bowl-rice me-1 text-warning"></i>Vivres Raffinées &amp; Réserves
            </div>
            <span class="text-muted font-monospace" style="font-size:0.65rem;">Transformation</span>
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Farine de Riz -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-bowl-rice text-warning me-1"></i>
                        <strong>Farine de Riz</strong>
                        <?php if (!empty($planet['famine_active'])): ?>
                            <span class="badge bg-danger text-white py-0 px-1" style="font-size:0.65rem;">Famine !</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-warning-lt font-monospace fw-bold">
                        <?= number_format($stockFlour) ?> / <?= number_format($maxFlour) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span><i class="fa-solid fa-shield-halberd me-1"></i>Rations d'élite &amp; prévention famine</span>
                    <span class="<?= ($pctFlour >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctFlour ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctFlour >= 90) ? 'bg-danger' : 'bg-warning' ?>" role="progressbar" style="width: <?= $pctFlour ?>%;"></div>
                </div>
            </div>

            <!-- Saké Féodal -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-wine-bottle text-indigo me-1"></i>
                        <strong>Saké Féodal</strong>
                        <?php if ($matsuriBonus > 0): ?>
                            <span class="badge bg-success text-white py-0 px-1" style="font-size:0.65rem;">Matsuri actif</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-indigo-lt font-monospace fw-bold">
                        <?= number_format($stockSake) ?> / <?= number_format($maxSake) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span><i class="fa-solid fa-champagne-glasses me-1"></i>Célébrations &amp; Ferveur populaire</span>
                    <span class="<?= ($pctSake >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctSake ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctSake >= 90) ? 'bg-danger' : 'bg-indigo' ?>" role="progressbar" style="width: <?= $pctSake ?>%;"></div>
                </div>
            </div>

            <?php if ($stockBeams > 0 || !empty($buildings['sawmill'])): ?>
                <!-- Poutres de Charpente -->
                <div class="p-2 rounded bg-surface-secondary border">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fa-solid fa-cubes text-teal me-1"></i>
                            <strong>Poutres de Charpente</strong>
                        </div>
                        <span class="badge bg-teal-lt font-monospace fw-bold">
                            <?= number_format($stockBeams) ?> / <?= number_format($maxBeams) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                        <span><i class="fa-solid fa-hammer me-1"></i>Ouvrages d'art &amp; Engins de siège</span>
                        <span><?= $pctBeams ?>%</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-teal" role="progressbar" style="width: <?= $pctBeams ?>%;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ====================================================
             4. FERVEUR DIVINE & ÉNERGIE (FACTEUR MOTEUR)
             ==================================================== -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-bolt me-1 text-info"></i>Ferveur Divine &amp; Énergie
            </div>
            <span class="badge bg-<?= ($energyRatio >= 1.0) ? 'info' : 'danger' ?>-lt font-monospace" style="font-size:0.65rem;">
                Ratio <?= round($energyRatio * 100) ?>%
            </span>
        </div>

        <div class="p-2 rounded bg-surface-secondary border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-torii-gate text-danger me-1"></i>
                    <strong>Ferveur Divine</strong>
                </div>
                <span class="badge bg-<?= ($energyNet >= 0) ? 'info' : 'danger' ?>-lt font-monospace fw-bold">
                    <?= $energyNet >= 0 ? '+' : '' ?><?= $energyNet ?> nette
                </span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                <span>Produite : <strong class="text-body"><?= $energyMax ?></strong> (Sanctuaire: +<?= (int)$shrineHourly ?>)</span>
                <span>Conso : <strong class="text-body"><?= $energyUsed ?></strong></span>
            </div>
            <div class="progress" style="height: 5px;">
                <div class="progress-bar <?= ($energyUsed > $energyMax) ? 'bg-danger' : 'bg-info' ?>" role="progressbar" style="width: <?= $pctEnergyUsed ?>%;"></div>
            </div>
            <?php if ($energyUsed > $energyMax): ?>
                <div class="alert alert-danger p-1 mt-1 mb-0 font-monospace text-center" style="font-size:0.68rem;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Déficit critique : production bridée à 10% ! Développez le Sanctuaire Shintō.
                </div>
            <?php endif; ?>
        </div>

        <!-- ====================================================
             5. MAIN-D'ŒUVRE & CLIMAT DÉMOGRAPHIQUE
             ==================================================== -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-users-gear me-1 text-indigo"></i>Main-d'œuvre &amp; Démographie
            </div>
            <span class="badge bg-<?= ($workforceRatio >= 1.0) ? 'indigo' : 'warning' ?>-lt font-monospace" style="font-size:0.65rem;">
                Effectif <?= round($workforceRatio * 100) ?>%
            </span>
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Logements & Capacité d'accueil -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-people-roof text-indigo me-1"></i>
                        <span>Capacité d'Habitations</span>
                        <?php if ($pctPop >= 95): ?>
                            <span class="badge bg-danger text-white py-0 px-1" style="font-size:0.65rem;">Saturé</span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-indigo-lt font-monospace fw-bold">
                        <?= number_format($maxPop) ?> places
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Sujets : <strong class="text-body"><?= number_format($curPop) ?></strong> / <?= number_format($maxPop) ?></span>
                    <span class="<?= ($pctPop >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctPop ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-indigo" role="progressbar" style="width: <?= $pctPop ?>%;"></div>
                </div>
            </div>

            <!-- Mobilisation Ouvrière & Contentement -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-person-digging text-blue me-1"></i>
                        <span>Mobilisation des Travailleurs</span>
                    </div>
                    <span class="badge bg-blue-lt font-monospace fw-bold">
                        <?= number_format($assignedWorkers) ?> / <?= number_format($requiredWorkers) ?> ouvriers
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Efficacité : <strong class="text-<?= ($workforceRatio >= 1.0) ? 'success' : 'warning' ?>"><?= round($workforceRatio * 100) ?>%</strong></span>
                    <span>Contentement : <strong class="text-body"><?= $contentmentVal ?>%</strong> (<?= ($contentmentVal >= 75) ? 'Prospère' : (($contentmentVal >= 50) ? 'Paisible' : 'Agité') ?>)</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-<?= ($workforceRatio >= 1.0) ? 'primary' : 'warning' ?>" role="progressbar" style="width: <?= min(100, round($workforceRatio * 100)) ?>%;"></div>
                </div>
                <?php if ($workforceRatio < 1.0): ?>
                    <div class="text-warning font-monospace mt-1" style="font-size:0.68rem;">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Sous-effectif : productivité globale réduite de <?= round((1.0 - $workforceRatio) * 100, 1) ?>%.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====================================================
             6. SYNTHÈSE DES MULTIPLICATEURS ACTIFS DU FIEF
             ==================================================== -->
        <div class="text-uppercase text-muted fw-bold mb-2 font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
            <i class="fa-solid fa-sliders me-1 text-success"></i>Modificateurs &amp; Bonus Actifs
        </div>

        <div class="p-2 rounded bg-surface-secondary border mb-3">
            <div class="d-flex flex-wrap gap-1" style="font-size:0.7rem;">
                <!-- Samouraï Héros -->
                <?php if ($hasHeroBonus): ?>
                    <span class="badge bg-primary text-white font-monospace py-1 px-1.5" title="Bénédiction de récolte permanente du Samouraï Héros">
                        <i class="fa-solid fa-user-ninja me-1"></i>Héros : +<?= $heroBonusMetal ?>B / +<?= $heroBonusCrystal ?>P / +<?= $heroBonusDeut ?>R
                    </span>
                <?php else: ?>
                    <span class="badge bg-light text-muted border py-1 px-1.5" title="Attribuez des points en Bénédiction de Récolte sur la fiche du Héros">
                        <i class="fa-solid fa-user-ninja me-1"></i>Héros : Aucun bonus
                    </span>
                <?php endif; ?>

                <!-- Scierie -->
                <?php if ($sawmillBonus > 0): ?>
                    <span class="badge bg-success-lt font-monospace py-1 px-1.5" title="Charpenterie Kizukuri">
                        <i class="fa-solid fa-hammer me-1"></i>Scierie +<?= $sawmillBonus ?>%
                    </span>
                <?php endif; ?>

                <!-- Briqueterie -->
                <?php if ($stonemasonBonus > 0): ?>
                    <span class="badge bg-secondary-lt font-monospace py-1 px-1.5" title="Taille de pierre de granit">
                        <i class="fa-solid fa-mountain me-1"></i>Tailleuse +<?= $stonemasonBonus ?>%
                    </span>
                <?php endif; ?>

                <!-- Meunerie -->
                <?php if ($grainMillBonus > 0): ?>
                    <span class="badge bg-warning-lt font-monospace py-1 px-1.5" title="Meunerie de riz traditionnelle">
                        <i class="fa-solid fa-wheat-awn me-1"></i>Meunerie +<?= $grainMillBonus ?>%
                    </span>
                <?php endif; ?>

                <!-- Pavillon de thé -->
                <?php if ($teahouseBonus > 0): ?>
                    <span class="badge bg-teal-lt font-monospace py-1 px-1.5" title="Pavillon de thé Chashitsu">
                        <i class="fa-solid fa-leaf me-1"></i>Pavillon Thé +<?= $teahouseBonus ?>%
                    </span>
                <?php endif; ?>

                <!-- Célébration Matsuri -->
                <?php if ($matsuriBonus > 0): ?>
                    <span class="badge bg-danger text-white font-monospace py-1 px-1.5" title="Célébration Matsuri au Donjon">
                        <i class="fa-solid fa-fire me-1"></i>Matsuri +<?= $matsuriBonus ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====================================================
             7. OASIS ANNEXÉES (BONUS DE RÉCOLTE)
             ==================================================== -->
        <div class="border-top pt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase text-muted fw-bold font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <i class="fa-solid fa-map-pin me-1 text-success"></i>Oasis Sauvages Sous Tutelle
                </span>
                <span class="badge bg-green-lt font-monospace" style="font-size:0.7rem;">
                    <?= count($annexedOases) ?> / 3
                </span>
            </div>

            <?php if (!empty($annexedOases)): ?>
                <div class="d-flex flex-column gap-1">
                    <?php foreach ($annexedOases as $ao): ?>
                        <div class="d-flex justify-content-between align-items-center p-1 px-2 rounded bg-surface-secondary border" style="font-size:0.75rem;">
                            <span>
                                <i class="fa-solid fa-location-crosshairs text-success me-1"></i>
                                <?= htmlspecialchars($ao['name']) ?> 
                                <span class="text-muted font-monospace">[<?= (int)$ao['coord_x'] ?>:<?= (int)$ao['coord_y'] ?>]</span>
                            </span>
                            <span class="badge bg-success-lt fw-bold">+<?= (int)($ao['bonus_rice'] ?? 25) ?>% Riz</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-2 rounded bg-surface-secondary border text-muted text-center" style="font-size:0.75rem;">
                    <i class="fa-solid fa-compass me-1 text-secondary"></i>Aucune oasis sous protectorat.
                    <div class="text-muted mt-1" style="font-size:0.68rem;">Explorez la Carte Provinciale pour pacifier et annexer jusqu'à 3 oasis.</div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
