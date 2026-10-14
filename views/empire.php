<?php
/**
 * Grand Tableau de Bord de l'Empire (Vue Consolidée Multi-Fiefs)
 * Fonctionnalité majeure du Sceau Impérial / Privilège du Shōgun
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ImperialSealEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$user) {
    header('Location: /');
    exit;
}

$sealEngine = new ImperialSealEngine();
$sealStatus = $sealEngine->getSealStatus((int)$user['id']);
$isSealActive = $sealStatus['active'];
$goldCoins = $sealStatus['gold'];

$empireData = $sealEngine->getEmpireOverview((int)$user['id']);
$villages = $empireData['villages'];
$totals = $empireData['totals'];
$unitsDb = $empireData['units_db'];
?>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="text-muted small">Domaine Suprême du Clan <?= htmlspecialchars(ucfirst($user['faction'])) ?></div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span><i class="fa-solid fa-crown text-warning me-1"></i></span> Grand Tableau de Bord de l'Empire
                <?php if ($isSealActive): ?>
                    <span class="badge bg-warning text-dark fs-5 shadow-sm"><i class="fa-solid fa-crown me-1"></i>Sceau Impérial Actif</span>
                <?php else: ?>
                    <a href="/?page=privilege" class="btn btn-sm btn-outline-warning">
                        <i class="fa-solid fa-crown me-1"></i>Activer le Sceau Impérial
                    </a>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="d-flex align-items-center gap-2">
                <a href="/?page=privilege" class="badge bg-dark text-warning p-2 fs-5 border border-warning text-decoration-none" title="Accéder aux Privilèges">
                    <i class="fa-solid fa-coins text-warning me-1"></i><?= number_format($goldCoins) ?> Koban
                </a>
                <a href="/?page=privilege" class="btn btn-warning fw-bold">
                    <i class="fa-solid fa-scroll me-1"></i>Privilèges du Shōgun
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ================= BANNIÈRE STATUT / PRÉSENTATION ================= -->
<?php if (!$isSealActive): ?>
<div class="alert alert-warning mb-4 shadow-sm" style="border-left: 5px solid #f59e0b; background: linear-gradient(90deg, #fffbeb 0%, #ffffff 100%);">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="fs-1"><i class="fa-solid fa-chess-rook text-secondary"></i></span>
            <div>
                <h4 class="alert-title fw-bold text-dark m-0">Privilège du Shōgun : Débloquez la Puissance Impériale</h4>
                <div class="text-secondary small mt-1">
                    Le Sceau Impérial vous accorde l'<strong>Architecte de Cour</strong> (file de construction jusqu'à 4 travaux),
                    le <strong>Carnet de Raids</strong> en 1 clic, l'<strong>Intendant du Marché</strong> (troc 1:1:1),
                    l'<strong>Ordre de Repli Tactique</strong> et l'optimisation complète de votre Empire.
                </div>
            </div>
        </div>
        <div>
            <a href="/?page=privilege" class="btn btn-warning fw-bold px-3 py-2 shadow-sm">
                <i class="fa-solid fa-crown me-1"></i>Investir dans le Sceau (dès 200 Koban)
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ================= KPI TOTAUX DE L'EMPIRE ================= -->
<div class="row row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-primary-lt fs-2"><i class="fa-solid fa-chess-rook"></i></span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Fiefs Féodaux</div>
                        <div class="text-primary fs-3 fw-bold"><?= count($villages) ?> Domaines</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-success-lt fs-2"><i class="fa-solid fa-users"></i></span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Population Impériale</div>
                        <div class="text-success fs-3 fw-bold"><?= number_format($totals['population']) ?> âmes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-warning-lt fs-2"><i class="fa-solid fa-wheat-awn"></i></span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Production Totale / h</div>
                        <div class="text-warning fs-3 fw-bold">+<?= number_format($totals['prod_metal'] + $totals['prod_crystal'] + $totals['prod_deuterium']) ?> res/h</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar rounded bg-info-lt fs-2"><i class="fa-solid fa-hammer"></i></span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Chantiers Actifs</div>
                        <div class="text-info fs-3 fw-bold"><?= $totals['active_constructions_count'] ?> en cours</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ONGLETS DU TABLEAU DE BORD ================= -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="#tab-resources" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">
                    <i class="fa-solid fa-wheat-awn me-1"></i>Terroirs &amp; Réserves
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-constructions" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-helmet-safety me-1"></i>Chantiers de l'Empire (<?= $totals['active_constructions_count'] ?>)
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-military" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-khanda me-1"></i>Forces Militaires
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-culture" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-torii-gate me-1"></i>Culture &amp; Banquets
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-evasion" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    <i class="fa-solid fa-shield-halved me-1"></i>Repli Tactique
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">

            <!-- ================= ONGLET 1 : TERROIRS & RESSOURCES ================= -->
            <div class="tab-pane active show" id="tab-resources" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped table-hover m-0">
                        <thead>
                            <tr class="text-muted" style="font-size:0.8rem; background:rgba(0,0,0,0.02);">
                                <th>Fief Castral</th>
                                <th><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre</th>
                                <th><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Taille</th>
                                <th><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial</th>
                                <th><i class="fa-solid fa-bowl-rice text-warning me-1"></i>Farine &amp; Vivres</th>
                                <th><i class="fa-solid fa-wine-bottle text-danger me-1"></i>Saké d'Apparat</th>
                                <th><i class="fa-solid fa-tree text-success me-1"></i>Poutres en bois</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($villages as $item):
                                $p = $item['planet'];
                                $isCur = ($planet && (int)$p['id'] === (int)$planet['id']);
                                $pctW = min(100, round(($p['metal'] / max(1, $p['metal_max'])) * 100));
                                $pctS = min(100, round(($p['crystal'] / max(1, $p['crystal_max'])) * 100));
                                $pctR = min(100, round(($p['deuterium'] / max(1, $p['deuterium_max'])) * 100));
                            ?>
                            <tr class="<?= $isCur ? 'table-warning-subtle' : '' ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-3"><?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning"></i>' : '<i class="fa-solid fa-chess-rook text-danger"></i>' ?></span>
                                        <div>
                                            <div class="fw-bold d-flex align-items-center gap-1">
                                                <a href="?switch_planet=<?= $p['id'] ?>&page=resources" class="text-reset text-decoration-none">
                                                    <?= htmlspecialchars($p['name']) ?>
                                                </a>
                                                <?php if (!empty($p['is_capital'])): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Capitale</span>
                                                <?php endif; ?>
                                                <?php if ($isCur): ?>
                                                    <span class="badge bg-success text-white" style="font-size:0.6rem;">Fief Actif</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted font-monospace small">
                                                [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>] &bull; <i class="fa-solid fa-users text-primary me-1"></i><?= number_format($p['population'] ?? 100) ?> hab.
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= number_format((int)$p['metal']) ?></div>
                                    <div class="progress" style="height: 4px; max-width: 110px;">
                                        <div class="progress-bar bg-primary" style="width: <?= $pctW ?>%;"></div>
                                    </div>
                                    <div class="text-success small">+<?= number_format($p['prod_rates']['metal']) ?>/h</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= number_format((int)$p['crystal']) ?></div>
                                    <div class="progress" style="height: 4px; max-width: 110px;">
                                        <div class="progress-bar bg-info" style="width: <?= $pctS ?>%;"></div>
                                    </div>
                                    <div class="text-success small">+<?= number_format($p['prod_rates']['crystal']) ?>/h</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= number_format((int)$p['deuterium']) ?></div>
                                    <div class="progress" style="height: 4px; max-width: 110px;">
                                        <div class="progress-bar bg-success" style="width: <?= $pctR ?>%;"></div>
                                    </div>
                                    <div class="text-success small">+<?= number_format($p['prod_rates']['deuterium']) ?>/h</div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= number_format((int)($p['rice_flour'] ?? 0)) ?> <i class="fa-solid fa-bowl-rice text-warning"></i></div>
                                    <?php if (!empty($item['famine']['flour_consumption_per_hour'])): ?>
                                        <div class="text-danger small" title="Consommation par les troupes d'élite">
                                            -<?= $item['famine']['flour_consumption_per_hour'] ?> farine/h
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small">Aucun péril</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-warning"><?= number_format((int)($p['sake'] ?? 0)) ?> <i class="fa-solid fa-wine-bottle text-danger"></i></div>
                                    <div class="text-muted small">/ <?= number_format((int)($p['sake_max'] ?? 10000)) ?> max</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= number_format((int)($p['wooden_beams'] ?? 0)) ?> <i class="fa-solid fa-tree text-success"></i></div>
                                    <div class="text-muted small">/ <?= number_format((int)($p['wooden_beams_max'] ?? 10000)) ?> max</div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php $npcPData = $sealEngine->getNpcPlanetResourceData($p); ?>
                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick='openNpcExchangeModal(<?= (int)$p['id'] ?>, <?= json_encode($p['name']) ?>, <?= json_encode($npcPData['resources']) ?>, <?= json_encode($npcPData['maxes']) ?>)'>
                                            <i class="fa-solid fa-scale-balanced me-1"></i>Intendant
                                        </button>
                                        <a href="?switch_planet=<?= $p['id'] ?>&page=resources" class="btn btn-sm btn-outline-secondary">
                                            Visiter
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background:rgba(245,158,11,0.08); font-weight:bold;">
                            <tr>
                                <td>TOTAL EMPIRE</td>
                                <td>
                                    <div class="text-dark"><?= number_format($totals['metal']) ?></div>
                                    <div class="text-success small">+<?= number_format($totals['prod_metal']) ?>/h</div>
                                </td>
                                <td>
                                    <div class="text-dark"><?= number_format($totals['crystal']) ?></div>
                                    <div class="text-success small">+<?= number_format($totals['prod_crystal']) ?>/h</div>
                                </td>
                                <td>
                                    <div class="text-dark"><?= number_format($totals['deuterium']) ?></div>
                                    <div class="text-success small">+<?= number_format($totals['prod_deuterium']) ?>/h</div>
                                </td>
                                <td>
                                    <div><?= number_format($totals['rice_flour']) ?> <i class="fa-solid fa-bowl-rice text-warning"></i></div>
                                    <div class="text-danger small">-<?= $totals['elite_upkeep_flour'] ?>/h</div>
                                </td>
                                <td>
                                    <div class="text-warning"><?= number_format($totals['sake']) ?> <i class="fa-solid fa-wine-bottle text-danger"></i></div>
                                </td>
                                <td>
                                    <div class="text-dark"><?= number_format($totals['wooden_beams']) ?> <i class="fa-solid fa-tree text-success"></i></div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-warning text-dark"><?= count($villages) ?> Domaines</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ================= ONGLET 2 : CHANTIERS DE L'EMPIRE ================= -->
            <div class="tab-pane" id="tab-constructions" role="tabpanel">
                <div class="p-3">
                    <?php
                    $hasAnyJob = false;
                    foreach ($villages as $item):
                        $p = $item['planet'];
                        $qList = $item['queue'];
                        if (empty($qList)) continue;
                        $hasAnyJob = true;
                    ?>
                        <div class="card mb-3 border">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#fafaf9;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-4"><?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning"></i>' : '<i class="fa-solid fa-chess-rook text-danger"></i>' ?></span>
                                    <strong><?= htmlspecialchars($p['name']) ?> [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</strong>
                                </div>
                                <a href="?switch_planet=<?= $p['id'] ?>&page=city" class="btn btn-sm btn-outline-primary">
                                    Aller au Fief
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table m-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Projet Castral</th>
                                            <th>Niveau Visé</th>
                                            <th>État &amp; Achèvement</th>
                                            <th class="text-end">Temps Restant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($qList as $job):
                                            $isDemolish = ((int)$job['target_level'] === 0);
                                            $bName = ($job['build_category'] === 'field')
                                                ? "Parcelle Agricole #" . $job['target_id']
                                                : (BUILDINGS[$job['target_id']]['name'] ?? $job['target_id']);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold d-flex align-items-center gap-2">
                                                    <span><?= $job['build_category'] === 'field' ? '<i class="fa-solid fa-wheat-awn text-warning"></i>' : '<i class="fa-solid fa-helmet-safety text-danger"></i>' ?></span>
                                                    <span><?= htmlspecialchars($bName) ?></span>
                                                    <?php if ($isDemolish): ?>
                                                        <span class="badge bg-danger text-white">Démantèlement</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-blue-lt">Niveau <?= $job['target_level'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-secondary small">Fin programmée : <?= date('H:i:s', $job['finishes_at']) ?></span>
                                            </td>
                                            <td style="min-width: 170px;">
                                                <?php
                                                    $eNow = time();
                                                    $eStart = (int)($job['started_at'] ?? $eNow);
                                                    $eEnd = (int)($job['finishes_at'] ?? $eNow);
                                                    $eTotal = max(1, $eEnd - $eStart);
                                                    $eElapsed = max(0, $eNow - $eStart);
                                                    $ePct = min(100, max(0, (int)round(($eElapsed / $eTotal) * 100)));
                                                ?>
                                                <div class="progress progress-xs mb-1" style="height: 7px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolish ? 'danger' : 'warning' ?> building-progress-bar"
                                                         role="progressbar"
                                                         style="width: <?= $ePct ?>%;"
                                                         aria-valuenow="<?= $ePct ?>"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100"
                                                         data-started="<?= $eStart ?>"
                                                         data-finishes="<?= $eEnd ?>"></div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center small">
                                                    <span class="fw-bold text-secondary building-progress-pct"><?= $ePct ?>%</span>
                                                    <span class="font-monospace fw-bold text-dark building-time-remaining" data-countdown="<?= $eEnd ?>">Calcul...</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$hasAnyJob): ?>
                        <div class="text-center py-5 text-muted">
                            <div class="fs-1 mb-2"><i class="fa-solid fa-helmet-safety text-secondary"></i></div>
                            <h3>Aucun chantier en cours dans l'Empire</h3>
                            <p class="small">Vos maîtres d'œuvre et bâtisseurs sont au repos. Lancez de nouvelles améliorations depuis vos fiefs !</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================= ONGLET 3 : FORCES MILITAIRES ================= -->
            <div class="tab-pane" id="tab-military" role="tabpanel">
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-bordered table-striped m-0">
                            <thead>
                                <tr class="text-muted small" style="background:#fafaf9;">
                                    <th>Fief de Garnison</th>
                                    <th>Régiments en Garnison</th>
                                    <th>Consommation Rations</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($villages as $item):
                                    $p = $item['planet'];
                                    $garrison = $item['garrison'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning me-1"></i>' : '<i class="fa-solid fa-chess-rook text-danger me-1"></i>' ?><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted font-monospace small">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</div>
                                    </td>
                                    <td>
                                        <?php if (!empty($garrison)): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($garrison as $gu):
                                                    $uInfo = $unitsDb[$gu['unit_code']] ?? ['name' => $gu['unit_code'], 'icon' => '<i class="fa-solid fa-khanda text-danger"></i>'];
                                                ?>
                                                    <span class="badge bg-light text-dark border p-1 px-2 d-flex align-items-center gap-1">
                                                        <span><?= $uInfo['icon'] ?></span>
                                                        <strong><?= number_format($gu['count']) ?></strong> <?= htmlspecialchars($uInfo['name']) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small italic">Aucun régiment stationné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['famine']['flour_consumption_per_hour'])): ?>
                                            <span class="badge bg-danger-lt">
                                                <i class="fa-solid fa-bowl-rice text-warning me-1"></i><?= $item['famine']['flour_consumption_per_hour'] ?> farine/h
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">0 ration</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?switch_planet=<?= $p['id'] ?>&page=fleet" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-khanda me-1"></i>Manœuvres
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= ONGLET 4 : CULTURE & EXPANSION ================= -->
            <div class="tab-pane" id="tab-culture" role="tabpanel">
                <div class="p-3">
                    <div class="row g-3">
                        <?php foreach ($villages as $item):
                            $p = $item['planet'];
                            $feast = $item['feast'];
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#fafaf9;">
                                    <strong><?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning me-1"></i>' : '<i class="fa-solid fa-chess-rook text-danger me-1"></i>' ?><?= htmlspecialchars($p['name']) ?></strong>
                                    <span class="badge bg-secondary font-monospace">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-secondary small">Saké en cave :</div>
                                        <div class="fs-3 fw-bold text-warning"><?= number_format((int)($p['sake'] ?? 0)) ?> <i class="fa-solid fa-wine-bottle text-danger"></i></div>
                                    </div>
                                    <div>
                                        <div class="text-secondary small mb-1">Célébration Castrale :</div>
                                        <?php if ($feast): ?>
                                            <div class="alert alert-success p-2 mb-0 small">
                                                <div class="fw-bold"><i class="fa-solid fa-champagne-glasses text-warning me-1"></i><?= htmlspecialchars($feast['name']) ?></div>
                                                <div class="mt-1" data-countdown="<?= $feast['finishes_at'] ?>"><i class="fa-solid fa-hourglass-half text-info me-1"></i>En cours...</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-light border p-2 mb-0 small text-muted">
                                                Aucun banquet actif
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer p-2 text-end bg-white">
                                    <a href="?switch_planet=<?= $p['id'] ?>&page=building&code=hq" class="btn btn-sm btn-outline-warning w-100">
                                        <i class="fa-solid fa-chess-rook me-1"></i>Décréter un Banquet (Tenshu)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ================= ONGLET 5 : REPLI TACTIQUE ================= -->
            <div class="tab-pane" id="tab-evasion" role="tabpanel">
                <div class="p-3">
                    <div class="alert alert-info mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fs-2"><i class="fa-solid fa-torii-gate text-danger"></i></span>
                            <div>
                                <strong>Principe de l'Ordre de Repli Tactique (Privilège du Shōgun) :</strong>
                                <div class="small mt-1">
                                    Lorsqu'il est activé sur un fief, vos garnisons et votre Samouraï Héros se replient discrètement dans les collines lors d'un assaut ennemi.
                                    Vos troupes échappent ainsi à l'anéantissement de nuit. L'attaquant ne combat personne (mais peut s'emparer des ressources non dissimulées dans vos cachettes).
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover table-bordered m-0">
                            <thead>
                                <tr class="text-muted small" style="background:#fafaf9;">
                                    <th>Fief Castral</th>
                                    <th>Statut de l'Ordre</th>
                                    <th>Garnison Protégée</th>
                                    <th class="text-end">Commutateur Tactique</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($villages as $item):
                                    $p = $item['planet'];
                                    $isEvasion = !empty($item['tactical_evasion']);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= !empty($p['is_capital']) ? '<i class="fa-solid fa-crown text-warning me-1"></i>' : '<i class="fa-solid fa-chess-rook text-danger me-1"></i>' ?><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted font-monospace small">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</div>
                                    </td>
                                    <td>
                                        <?php if ($isEvasion): ?>
                                            <span class="badge bg-success-lt fw-bold fs-6">
                                                <i class="fa-solid fa-check text-success me-1"></i>Repli Tactique Activé
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">
                                                Combat à Mort (Garnison engagée)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-secondary small">
                                            <?= count($item['garrison']) ?> type(s) de troupes stationnées
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm <?= $isEvasion ? 'btn-success' : 'btn-outline-secondary' ?>"
                                                onclick="toggleEvasion(<?= $p['id'] ?>)">
                                            <?= $isEvasion ? '<i class="fa-solid fa-shield-halved text-success me-1"></i>Activé (Cliquez pour désactiver)' : '<i class="fa-solid fa-torii-gate me-1"></i>Activer le Repli' ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/npc_exchange_modal.php'; ?>

<script>
// Comptes à rebours et barres de progression de l'Empire
function updateEmpireCountdowns() {
    if (window.ProgressBar) {
        window.ProgressBar.initAll();
        window.ProgressBar.instances.forEach(pb => pb.update());
    }
}
updateEmpireCountdowns();

// Commutateur Repli Tactique
async function toggleEvasion(planetId) {
    const fd = new FormData();
    fd.append('action', 'toggle_evasion');
    fd.append('planet_id', planetId);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors de la modification de l\'ordre.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission.', 'error');
    }
}


</script>

