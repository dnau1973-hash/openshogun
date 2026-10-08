<?php
/**
 * Panneau Mutualisé : Bilan des Récoltes, Stocks & Oasis Féodales (OpenShogun)
 * Utilisable universellement sur la vue Terroir (resources.php) et la Cité Castrale (city.php).
 * Affiche l'ensemble des 8 indicateurs (Bois, Pierre, Riz, Argile, Thé, Soja, Sérénité, Logement)
 * avec rendements horaires, jauges de capacité de stockage, alertes de débordement et oasis annexées.
 */

require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';
require_once __DIR__ . '/../../core/OasisEngine.php';

// Initialisation sécurisée des données du domaine si non fournies par le contexte parent
if (!isset($planet) || empty($planet['id'])) {
    return;
}

$planetId = (int)$planet['id'];
$planetEngine = $planetEngine ?? new PlanetEngine();
$ruralPlotEngine = $ruralPlotEngine ?? new RuralPlotEngine();
$oasisEngine = $oasisEngine ?? new OasisEngine();

// Récupérer les bâtiments et niveaux si non fournis
$buildings = $buildings ?? $planetEngine->getBuildings($planetId);
$hqLevel = $hqLevel ?? (int)($buildings['hq'] ?? 1);

// Récupérer les parcelles rurales enrichies
if (!isset($plots) || !is_array($plots)) {
    $plots = $ruralPlotEngine->getEnrichedPlots($planetId, $planet, $hqLevel);
}

// Récupérer la capacité de logement
if (!isset($maxPop)) {
    $housingCap = $ruralPlotEngine->getVillageHousingCapacity($planetId);
    $maxPop = (int)($housingCap['total_capacity'] ?? ($planet['population_max'] ?? 100));
}

// Récupérer les productions horaires de base si absentes
if (empty($planet['prod_rates'])) {
    $fields = $fields ?? $planetEngine->getFields($planetId);
    $prodRates = $planetEngine->calculateProduction($fields, $planetId);
} else {
    $prodRates = $planet['prod_rates'];
}

// Récupérer les oasis annexées
if (!isset($annexedOases)) {
    $annexedOases = $oasisEngine->getAnnexedOasesForPlanet($planetId);
}

// Calcul des stocks et capacités maximales
$stockMetal = (float)($planet['metal'] ?? 0);
$maxMetal = max(1, (float)($planet['metal_max'] ?? 15000));
$pctMetal = min(100, round(($stockMetal / $maxMetal) * 100, 1));

$stockCrystal = (float)($planet['crystal'] ?? 0);
$maxCrystal = max(1, (float)($planet['crystal_max'] ?? 15000));
$pctCrystal = min(100, round(($stockCrystal / $maxCrystal) * 100, 1));

$stockDeut = (float)($planet['deuterium'] ?? 0);
$maxDeut = max(1, (float)($planet['deuterium_max'] ?? 15000));
$pctDeut = min(100, round(($stockDeut / $maxDeut) * 100, 1));

// Populations & Habitations
$curPop = (int)($planet['population'] ?? 0);
$pctPop = ($maxPop > 0) ? min(100, round(($curPop / $maxPop) * 100, 1)) : 0;

// Sérénité & Contentement féodal
$contentmentVal = (int)($planet['contentment'] ?? 100);
$shrineHourly = (float)($plots['sanctuaire_shinto']['prod_hourly'] ?? 0);

// Spécialités rurales (Argile, Thé, Soja)
$clayHourly = (float)($plots['fosse_argile']['prod_hourly'] ?? 0);
$clayLvl = (int)($plots['fosse_argile']['level'] ?? 0);
$clayMaxLvl = (int)($plots['fosse_argile']['max_level'] ?? 30);
$clayProgress = ($clayMaxLvl > 0) ? min(100, round(($clayLvl / $clayMaxLvl) * 100)) : 0;

$teaHourly = (float)($plots['culture_the']['prod_hourly'] ?? 0);
$teaLvl = (int)($plots['culture_the']['level'] ?? 0);
$teaMaxLvl = (int)($plots['culture_the']['max_level'] ?? 30);
$teaProgress = ($teaMaxLvl > 0) ? min(100, round(($teaLvl / $teaMaxLvl) * 100)) : 0;

$soyHourly = (float)($plots['champ_soja']['prod_hourly'] ?? 0);
$soyLvl = (int)($plots['champ_soja']['level'] ?? 0);
$soyMaxLvl = (int)($plots['champ_soja']['max_level'] ?? 30);
$soyProgress = ($soyMaxLvl > 0) ? min(100, round(($soyLvl / $soyMaxLvl) * 100)) : 0;

$panelTitle = $panelTitle ?? 'Récoltes, Stocks & Oasis';
?>

<div class="card shadow-sm border-0 mb-3" id="ruralHarvestPanel">
    <!-- En-tête -->
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center bg-dark text-white">
        <h3 class="card-title mb-0 fs-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-boxes-stacked text-warning"></i>
            <span><?= htmlspecialchars($panelTitle) ?></span>
        </h3>
        <span class="badge bg-secondary-lt text-white font-monospace" style="font-size:0.7rem;">
            <?= count($annexedOases) ?> / 3 Oasis
        </span>
    </div>

    <div class="card-body p-3">

        <!-- ====================================================
             1. RESSOURCES DE BASE (BOIS, PIERRE, RIZ)
             ==================================================== -->
        <div class="text-uppercase text-muted fw-bold mb-2 font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
            <i class="fa-solid fa-warehouse me-1 text-primary"></i>Matières Premières &amp; Entrepôts
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
                    <span class="badge bg-success-lt font-monospace fw-bold">
                        +<?= number_format($prodRates['metal'] ?? 0) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Stock : <strong class="text-body"><?= number_format($stockMetal) ?></strong> / <?= number_format($maxMetal) ?></span>
                    <span class="<?= ($pctMetal >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctMetal ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctMetal >= 90) ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= $pctMetal ?>%;"></div>
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
                    <span class="badge bg-secondary-lt font-monospace fw-bold">
                        +<?= number_format($prodRates['crystal'] ?? 0) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Stock : <strong class="text-body"><?= number_format($stockCrystal) ?></strong> / <?= number_format($maxCrystal) ?></span>
                    <span class="<?= ($pctCrystal >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctCrystal ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctCrystal >= 90) ? 'bg-danger' : 'bg-secondary' ?>" role="progressbar" style="width: <?= $pctCrystal ?>%;"></div>
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
                    <span class="badge bg-warning-lt font-monospace fw-bold">
                        +<?= number_format($prodRates['deuterium'] ?? 0) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Grenier : <strong class="text-body"><?= number_format($stockDeut) ?></strong> / <?= number_format($maxDeut) ?></span>
                    <span class="<?= ($pctDeut >= 90) ? 'text-danger fw-bold' : '' ?>"><?= $pctDeut ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar <?= ($pctDeut >= 90) ? 'bg-danger' : 'bg-warning' ?>" role="progressbar" style="width: <?= $pctDeut ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- ====================================================
             2. SPÉCIALITÉS DU TERROIR (ARGILE, THÉ, SOJA)
             ==================================================== -->
        <div class="text-uppercase text-muted fw-bold mb-2 font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
            <i class="fa-solid fa-seedling me-1 text-warning"></i>Terroirs Spécialisés &amp; Récoltes
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Argile & Tuiles -->
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
                    <span>Fosse : <strong class="text-body">Niveau <?= $clayLvl ?></strong> / <?= $clayMaxLvl ?></span>
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
                    </div>
                    <span class="badge bg-teal-lt font-monospace fw-bold">
                        +<?= number_format($teaHourly) ?>/h
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Coteaux : <strong class="text-body">Niveau <?= $teaLvl ?></strong> / <?= $teaMaxLvl ?></span>
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
                    <span>Sillons : <strong class="text-body">Niveau <?= $soyLvl ?></strong> / <?= $soyMaxLvl ?></span>
                    <span><?= $soyProgress ?>%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-lime" role="progressbar" style="width: <?= $soyProgress ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- ====================================================
             3. SÉRÉNITÉ & HABITATIONS DÉMOGRAPHIQUES
             ==================================================== -->
        <div class="text-uppercase text-muted fw-bold mb-2 font-monospace" style="font-size:0.68rem; letter-spacing:0.5px;">
            <i class="fa-solid fa-torii-gate me-1 text-danger"></i>Harmonie Féodale &amp; Démographie
        </div>

        <div class="d-flex flex-column gap-2 mb-3">
            <!-- Sérénité & Kamis -->
            <div class="p-2 rounded bg-surface-secondary border">
                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.8rem;">
                    <div class="d-flex align-items-center gap-1">
                        <i class="fa-solid fa-torii-gate text-danger me-1"></i>
                        <span>Sérénité du Domaine</span>
                    </div>
                    <span class="badge bg-danger-lt font-monospace fw-bold">
                        +<?= number_format($shrineHourly) ?> sérénité
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1 text-muted font-monospace" style="font-size:0.72rem;">
                    <span>Contentement : <strong class="text-body"><?= $contentmentVal ?>%</strong></span>
                    <span class="text-<?= ($contentmentVal >= 75) ? 'success' : (($contentmentVal >= 50) ? 'warning' : 'danger') ?>">
                        <?= ($contentmentVal >= 75) ? 'Prospère' : (($contentmentVal >= 50) ? 'Paisible' : 'Agité') ?>
                    </span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= min(100, max(5, $contentmentVal)) ?>%;"></div>
                </div>
            </div>

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
        </div>

        <!-- ====================================================
             4. OASIS ANNEXÉES (BONUS DE RÉCOLTE)
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
