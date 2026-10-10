<?php
/**
 * Vue Partielle Mutualisée : Main-d'œuvre, Démographie & Quotas Ouvriers (OpenShogun)
 * Permet un contrôle transparent et détaillé de tous les postes de travail (Terroir Rural vs Cité Castrale),
 * des capacités d'habitation minka, du contentement féodal et des éventuels malus de sous-effectif.
 */

require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';
require_once __DIR__ . '/../../core/PopulationEngine.php';

if (!isset($planet) || empty($planet['id'])) {
    return;
}

$planetId = (int)$planet['id'];

// Initialisation défensive des dépendances
if (!isset($buildings)) {
    $planetEngine = $planetEngine ?? new PlanetEngine();
    $buildings = $planetEngine->getBuildings($planetId);
}
$hqLevel = $hqLevel ?? (int)($buildings['hq'] ?? 1);

if (!isset($plots) || !is_array($plots)) {
    $ruralPlotEngine = $ruralPlotEngine ?? new RuralPlotEngine();
    $plots = $ruralPlotEngine->getEnrichedPlots($planetId, $planet, $hqLevel);
}

if (!isset($maxPop)) {
    $ruralPlotEngine = $ruralPlotEngine ?? new RuralPlotEngine();
    $housingCap = $ruralPlotEngine->getVillageHousingCapacity($planetId);
    $maxPop = (int)($housingCap['total_capacity'] ?? ($planet['population_max'] ?? 100));
}

$fields = $fields ?? ($planetEngine ? $planetEngine->getFields($planetId) : []);

// Calcul ou consolidation du bilan de main-d'œuvre avec décomposition transparente
$workforceSummary = $planet['workforce'] ?? ($prodRates['workforce'] ?? null);
if (!$workforceSummary || empty($workforceSummary['breakdown'])) {
    $workforceSummary = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop, $plots);
}

$curPop           = (int)($planet['population'] ?? ($workforceSummary['total_population'] ?? 0));
$pctPop           = ($maxPop > 0) ? min(100, round(($curPop / $maxPop) * 100, 1)) : 0;
$contentmentVal   = (int)($planet['contentment'] ?? 100);
$workforceRatio   = (float)($workforceSummary['workforce_ratio'] ?? 1.0);
$assignedWorkers  = (int)($workforceSummary['assigned_workers'] ?? $curPop);
$requiredWorkers  = (int)($workforceSummary['required_workers'] ?? 0);
$idleWorkers      = (int)($workforceSummary['idle_workers'] ?? 0);
$unemploymentPct  = (float)($workforceSummary['unemployment_pct'] ?? 0.0);
$isUnderstaffed   = !empty($workforceSummary['is_understaffed']);
$malusPercent     = (float)($workforceSummary['understaffed_malus_pct'] ?? 0.0);

$breakdown        = $workforceSummary['breakdown'] ?? PopulationEngine::getDetailedWorkforceBreakdown($buildings, $fields, $planetId, $plots);
$ruralPlotsBreakdown = $breakdown['rural_plots'] ?? [];
$urbanBldBreakdown   = $breakdown['urban_buildings'] ?? [];
$totalRuralWorkers   = (int)($breakdown['total_rural_workers'] ?? 0);
$totalUrbanWorkers   = (int)($breakdown['total_urban_workers'] ?? 0);
?>

<div class="workforce-demographics-container p-1">

    <!-- ====================================================
         1. CAPACITÉ D'HABITATIONS (LOGEMENTS MINKA)
         ==================================================== -->
    <div class="card mb-3 border shadow-none bg-surface">
        <div class="card-body p-2.5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded bg-indigo-lt text-indigo">
                        <i class="fa-solid fa-people-roof"></i>
                    </span>
                    <div>
                        <strong class="d-block" style="font-size: 0.85rem;">Capacité d'Habitations</strong>
                        <small class="text-secondary" style="font-size: 0.72rem;">Logements villageois &amp; maisonnées minka</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-indigo-lt font-monospace fw-bold" style="font-size: 0.75rem;">
                        <?= number_format($maxPop) ?> places
                    </span>
                    <?php if ($pctPop >= 95): ?>
                        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">Saturé</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="progress mb-1" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                <div class="progress-bar bg-indigo" role="progressbar" style="width: <?= $pctPop ?>%;" aria-valuenow="<?= $pctPop ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                <span class="text-secondary">
                    Population résidente : <strong class="text-dark font-monospace"><?= number_format($curPop) ?></strong> / <?= number_format($maxPop) ?>
                </span>
                <span class="font-monospace fw-semibold <?= ($pctPop >= 90) ? 'text-danger' : 'text-secondary' ?>">
                    Occupation : <?= $pctPop ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- ====================================================
         2. MOBILISATION DES TRAVAILLEURS & CONTENTEMENT
         ==================================================== -->
    <div class="card mb-3 border shadow-none bg-surface">
        <div class="card-body p-2.5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded bg-<?= $isUnderstaffed ? 'warning' : 'primary' ?>-lt text-<?= $isUnderstaffed ? 'warning' : 'primary' ?>">
                        <i class="fa-solid fa-person-digging"></i>
                    </span>
                    <div>
                        <strong class="d-block" style="font-size: 0.85rem;">Mobilisation Ouvrière</strong>
                        <small class="text-secondary" style="font-size: 0.72rem;">
                            Contentement : <strong class="text-dark"><?= $contentmentVal ?>%</strong>
                            (<?= ($contentmentVal >= 75) ? 'Prospère' : (($contentmentVal >= 50) ? 'Paisible' : 'Agité') ?>)
                        </small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-<?= ($workforceRatio >= 1.0) ? 'success' : 'warning' ?>-lt font-monospace fw-bold" style="font-size: 0.75rem;">
                        <?= round($workforceRatio * 100) ?>% Efficacité
                    </span>
                </div>
            </div>

            <div class="progress mb-1" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                <div class="progress-bar bg-<?= ($workforceRatio >= 1.0) ? 'success' : 'warning' ?>" role="progressbar" style="width: <?= min(100, round($workforceRatio * 100)) ?>%;" aria-valuenow="<?= min(100, round($workforceRatio * 100)) ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                <span class="text-secondary">
                    Ouvriers affectés : <strong class="text-dark font-monospace"><?= number_format($assignedWorkers) ?></strong> / <strong class="text-secondary font-monospace"><?= number_format($requiredWorkers) ?></strong>
                </span>
                <span class="font-monospace text-secondary">
                    Réserve / Chômage : <strong class="text-dark"><?= number_format($idleWorkers) ?></strong> (<?= round($unemploymentPct, 1) ?>%)
                </span>
            </div>

            <?php if ($isUnderstaffed): ?>
                <div class="alert alert-warning py-1.5 px-2 mt-2 mb-0 d-flex align-items-center gap-2 border-0 bg-warning-lt" style="font-size: 0.72rem;">
                    <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                    <div>
                        <strong>Sous-effectif actif (-<?= $malusPercent ?>%) :</strong> La population actuelle ne comble pas tous les postes requis. La cadence de l'ensemble des récoltes et ateliers est freinée proportionnellement.
                    </div>
                </div>
            <?php else: ?>
                <div class="text-success mt-1.5 d-flex align-items-center gap-1" style="font-size: 0.7rem;">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Effectifs complets : Tous les ateliers et terroirs fonctionnent à 100% de leur capacité.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ====================================================
         3. CONTRÔLE DÉTAILLÉ DES CALCULS DES OUVRIERS
         ==================================================== -->
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <div class="text-uppercase text-muted fw-bold font-monospace" style="font-size: 0.68rem; letter-spacing: 0.5px;">
            <i class="fa-solid fa-calculator me-1 text-primary"></i>Décomposition des Postes Requis
        </div>
        <span class="badge bg-secondary-lt font-monospace fw-bold" style="font-size: 0.65rem;">
            Total : <?= number_format($requiredWorkers) ?> requis
        </span>
    </div>

    <!-- SECTEUR RURAL (TERROIR FÉODAL) -->
    <div class="card mb-2 border shadow-none bg-surface">
        <div class="card-header py-1.5 px-2.5 bg-light d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold d-flex align-items-center gap-1.5" style="font-size: 0.76rem;">
                <i class="fa-solid fa-tree text-success"></i> Terroir Rural (9 Parcelles)
            </span>
            <span class="badge bg-success-lt font-monospace" style="font-size: 0.68rem;">
                Sous-total : <?= number_format($totalRuralWorkers) ?> ouvriers
            </span>
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.74rem;">
            <?php foreach ($ruralPlotsBreakdown as $type => $item): ?>
                <?php if ($item['rate'] > 0 || $item['level'] > 0): ?>
                    <div class="list-group-item py-1.5 px-2.5 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-center" style="width: 16px; color: <?= htmlspecialchars($item['color']) ?>;">
                                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                            </span>
                            <div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($item['name']) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.68rem;"><?= htmlspecialchars($item['role']) ?></small>
                            </div>
                        </div>
                        <div class="text-end font-monospace">
                            <span class="badge bg-light text-secondary border py-0 px-1 me-1" style="font-size: 0.66rem;">
                                Niv. <?= (int)$item['level'] ?> &times; <?= (int)$item['rate'] ?>/niv.
                            </span>
                            <strong class="<?= $item['required'] > 0 ? 'text-dark' : 'text-muted' ?>" style="font-size: 0.76rem;">
                                <?= (int)$item['required'] ?>
                            </strong>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTEUR URBAIN (CITÉ CASTRALE) -->
    <div class="card mb-2 border shadow-none bg-surface">
        <div class="card-header py-1.5 px-2.5 bg-light d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold d-flex align-items-center gap-1.5" style="font-size: 0.76rem;">
                <i class="fa-solid fa-landmark text-primary"></i> Cité Castrale (Édifices Urbains)
            </span>
            <span class="badge bg-primary-lt font-monospace" style="font-size: 0.68rem;">
                Sous-total : <?= number_format($totalUrbanWorkers) ?> ouvriers
            </span>
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.74rem;">
            <?php if (empty($urbanBldBreakdown)): ?>
                <div class="list-group-item py-2 px-2.5 text-center text-muted" style="font-size: 0.72rem;">
                    Aucun bâtiment urbain actif ne mobilise d'ouvrier pour le moment.
                </div>
            <?php else: ?>
                <?php foreach ($urbanBldBreakdown as $type => $item): ?>
                    <div class="list-group-item py-1.5 px-2.5 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-center" style="width: 16px; color: <?= htmlspecialchars($item['color']) ?>;">
                                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                            </span>
                            <div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($item['name']) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.68rem;"><?= htmlspecialchars($item['role']) ?></small>
                            </div>
                        </div>
                        <div class="text-end font-monospace">
                            <span class="badge bg-light text-secondary border py-0 px-1 me-1" style="font-size: 0.66rem;">
                                Niv. <?= (int)$item['level'] ?> &times; <?= (int)$item['rate'] ?>/niv.
                            </span>
                            <strong class="<?= $item['required'] > 0 ? 'text-dark' : 'text-muted' ?>" style="font-size: 0.76rem;">
                                <?= (int)$item['required'] ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- SYNTHÈSE TOTALE -->
    <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
        <span class="text-secondary fw-semibold">
            <i class="fa-solid fa-equals me-1 text-primary"></i>Total des Besoins en Main-d'œuvre :
        </span>
        <span class="font-monospace fw-bold text-dark" style="font-size: 0.82rem;">
            <?= number_format($totalRuralWorkers) ?> (Rural) + <?= number_format($totalUrbanWorkers) ?> (Urbain) = <?= number_format($requiredWorkers) ?> ouvriers
        </span>
    </div>

</div>

