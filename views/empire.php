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
                <span>👑</span> Grand Tableau de Bord de l'Empire
                <?php if ($isSealActive): ?>
                    <span class="badge bg-warning text-dark fs-5 shadow-sm">👑 Sceau Impérial Actif</span>
                <?php else: ?>
                    <a href="/?page=privilege" class="btn btn-sm btn-outline-warning">
                        👑 Activer le Sceau Impérial
                    </a>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="d-flex align-items-center gap-2">
                <a href="/?page=privilege" class="badge bg-dark text-warning p-2 fs-5 border border-warning text-decoration-none" title="Accéder aux Privilèges">
                    🪙 <?= number_format($goldCoins) ?> Koban
                </a>
                <a href="/?page=privilege" class="btn btn-warning fw-bold">
                    📜 Privilèges du Shōgun
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
            <span class="fs-1">🏯</span>
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
                👑 Investir dans le Sceau (dès 200 Koban)
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
                        <span class="avatar rounded bg-primary-lt fs-2">🏯</span>
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
                        <span class="avatar rounded bg-success-lt fs-2">👥</span>
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
                        <span class="avatar rounded bg-warning-lt fs-2">🌾</span>
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
                        <span class="avatar rounded bg-info-lt fs-2">🔨</span>
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
                    🌾 Terroirs &amp; Réserves
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-constructions" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    🔨 Chantiers de l'Empire (<?= $totals['active_constructions_count'] ?>)
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-military" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    ⚔️ Forces Militaires
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-culture" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    ⛩️ Culture &amp; Banquets
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="#tab-evasion" class="nav-link" data-bs-toggle="tab" aria-selected="false" role="tab" tabindex="-1">
                    🛡️ Repli Tactique
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
                                <th>🪵 Bois de Cèdre</th>
                                <th>🪨 Pierre de Taille</th>
                                <th>🌾 Riz Impérial</th>
                                <th>🍚 Farine &amp; Vivres</th>
                                <th>🍶 Saké d'Apparat</th>
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
                                        <span class="fs-3"><?= !empty($p['is_capital']) ? '👑' : '🏯' ?></span>
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
                                                [<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>] &bull; 👥 <?= number_format($p['population'] ?? 100) ?> hab.
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
                                    <div class="fw-bold"><?= number_format((int)($p['rice_flour'] ?? 0)) ?> 🍚</div>
                                    <?php if (!empty($item['famine']['flour_consumption_per_hour'])): ?>
                                        <div class="text-danger small" title="Consommation par les troupes d'élite">
                                            -<?= $item['famine']['flour_consumption_per_hour'] ?> farine/h
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted small">Aucun péril</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-warning"><?= number_format((int)($p['sake'] ?? 0)) ?> 🍶</div>
                                    <div class="text-muted small">/ <?= number_format((int)($p['sake_max'] ?? 10000)) ?> max</div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-warning" 
                                                onclick="openNpcExchangeModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= (int)$p['metal'] ?>, <?= (int)$p['crystal'] ?>, <?= (int)$p['deuterium'] ?>, <?= (int)$p['metal_max'] ?>, <?= (int)$p['crystal_max'] ?>, <?= (int)$p['deuterium_max'] ?>)">
                                            ⚖️ Intendant
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
                                    <div><?= number_format($totals['rice_flour']) ?> 🍚</div>
                                    <div class="text-danger small">-<?= $totals['elite_upkeep_flour'] ?>/h</div>
                                </td>
                                <td>
                                    <div class="text-warning"><?= number_format($totals['sake']) ?> 🍶</div>
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
                                    <span class="fs-4"><?= !empty($p['is_capital']) ? '👑' : '🏯' ?></span>
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
                                                    <span><?= $job['build_category'] === 'field' ? '🌾' : '🏗️' ?></span>
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
                                            <td class="text-end">
                                                <span class="badge bg-primary text-white font-monospace p-2 fs-5" data-countdown="<?= $job['finishes_at'] ?>">
                                                    ⏳ Calcul...
                                                </span>
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
                            <div class="fs-1 mb-2">🔨</div>
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
                                        <div class="fw-bold"><?= !empty($p['is_capital']) ? '👑 ' : '🏯 ' ?><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted font-monospace small">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</div>
                                    </td>
                                    <td>
                                        <?php if (!empty($garrison)): ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($garrison as $gu): 
                                                    $uInfo = $unitsDb[$gu['unit_code']] ?? ['name' => $gu['unit_code'], 'icon' => '⚔️'];
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
                                                🍚 <?= $item['famine']['flour_consumption_per_hour'] ?> farine/h
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">0 ration</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?switch_planet=<?= $p['id'] ?>&page=fleet" class="btn btn-sm btn-outline-danger">
                                            ⚔️ Manœuvres
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
                                    <strong><?= !empty($p['is_capital']) ? '👑 ' : '🏯 ' ?><?= htmlspecialchars($p['name']) ?></strong>
                                    <span class="badge bg-secondary font-monospace">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-secondary small">Saké en cave :</div>
                                        <div class="fs-3 fw-bold text-warning"><?= number_format((int)($p['sake'] ?? 0)) ?> 🍶</div>
                                    </div>
                                    <div>
                                        <div class="text-secondary small mb-1">Célébration Castrale :</div>
                                        <?php if ($feast): ?>
                                            <div class="alert alert-success p-2 mb-0 small">
                                                <div class="fw-bold">🎉 <?= htmlspecialchars($feast['name']) ?></div>
                                                <div class="mt-1" data-countdown="<?= $feast['finishes_at'] ?>">⏳ En cours...</div>
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
                                        🏯 Décréter un Banquet (Tenshu)
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
                            <span class="fs-2">⛩️</span>
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
                                        <div class="fw-bold"><?= !empty($p['is_capital']) ? '👑 ' : '🏯 ' ?><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-muted font-monospace small">[<?= $p['coord_x'] ?>|<?= $p['coord_y'] ?>]</div>
                                    </td>
                                    <td>
                                        <?php if ($isEvasion): ?>
                                            <span class="badge bg-success-lt fw-bold fs-6">
                                                ✓ Repli Tactique Activé
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
                                            <?= $isEvasion ? '🛡️ Activé (Cliquez pour désactiver)' : '⛩️ Activer le Repli' ?>
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

<!-- ================= MODALE INTERACTIVE DU MARCHAND NPC ================= -->
<div class="modal modal-blur fade" id="modalNpcExchange" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span>⚖️</span> Intendant du Marché Castral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-3">
                    L'Intendant redistribue immédiatement vos surplus de Bois, Pierre et Riz au taux parfait de <strong>1:1:1</strong> pour un tribut de <strong>3 Koban 🪙</strong>.
                </div>

                <div class="p-2 bg-light border rounded mb-3 text-center">
                    <span class="text-muted small">Fief sélectionné : </span>
                    <strong id="npc_planet_name" class="text-dark">Fief</strong>
                    <div class="fs-4 fw-bold text-primary mt-1">
                        Total à répartir : <span id="npc_total_amount">0</span>
                    </div>
                    <div class="small text-muted">La somme totale doit être conservée au grain près.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪵 Bois de Cèdre :</span>
                        <span id="npc_wood_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_wood" oninput="onNpcRangeChange('wood')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_wood" oninput="onNpcInputChange('wood')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🪨 Pierre de Taille :</span>
                        <span id="npc_stone_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_stone" oninput="onNpcRangeChange('stone')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_stone" oninput="onNpcInputChange('stone')">
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between small fw-bold">
                        <span>🌾 Riz Impérial :</span>
                        <span id="npc_rice_val" class="font-monospace text-primary">0</span>
                    </label>
                    <input type="range" class="form-range" id="npc_range_rice" oninput="onNpcRangeChange('rice')">
                    <input type="number" class="form-control form-control-sm mt-1" id="npc_input_rice" oninput="onNpcInputChange('rice')">
                </div>

                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill" onclick="distributeEvenly()">
                        ⚖️ Répartir Équitablement (1/3 chacun)
                    </button>
                </div>

                <div id="npc_diff_alert" class="alert alert-danger p-2 small d-none">
                    La somme répartie ne correspond pas au total disponible.
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <span class="small text-muted">Coût : <strong class="text-warning">3 Koban</strong> (Solde : <?= $goldCoins ?>)</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btnSubmitNpcExchange" onclick="submitNpcExchange()">
                        🪙 Sceller le Troc (3 Koban)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Comptes à rebours
function updateEmpireCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-countdown]').forEach(el => {
        const target = parseInt(el.getAttribute('data-countdown'), 10);
        const diff = target - now;
        if (diff <= 0) {
            el.innerText = 'Terminé !';
        } else {
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            el.innerText = `⏳ ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }
    });
}
setInterval(updateEmpireCountdowns, 1000);
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

// Logique Intendant du Marché NPC
let currentNpcPlanetId = 0;
let currentNpcTotal = 0;
let currentNpcMax = { wood: 0, stone: 0, rice: 0 };

function openNpcExchangeModal(planetId, planetName, wood, stone, rice, maxW, maxS, maxR) {
    currentNpcPlanetId = planetId;
    currentNpcTotal = Math.floor(wood + stone + rice);
    currentNpcMax = { wood: maxW, stone: maxS, rice: maxR };

    document.getElementById('npc_planet_name').innerText = planetName;
    document.getElementById('npc_total_amount').innerText = currentNpcTotal.toLocaleString('fr-FR');

    ['wood', 'stone', 'rice'].forEach(res => {
        const r = document.getElementById(`npc_range_${res}`);
        const inp = document.getElementById(`npc_input_${res}`);
        const val = (res === 'wood') ? wood : ((res === 'stone') ? stone : rice);

        r.max = Math.min(currentNpcTotal, currentNpcMax[res]);
        r.value = val;
        inp.max = currentNpcMax[res];
        inp.value = val;
        document.getElementById(`npc_val_${res}`) && (document.getElementById(`npc_val_${res}`).innerText = val);
    });

    updateNpcDisplay();
    const modal = new bootstrap.Modal(document.getElementById('modalNpcExchange'));
    modal.show();
}

function distributeEvenly() {
    const part = Math.floor(currentNpcTotal / 3);
    const rest = currentNpcTotal - (part * 2);

    document.getElementById('npc_input_wood').value = part;
    document.getElementById('npc_input_stone').value = part;
    document.getElementById('npc_input_rice').value = rest;

    document.getElementById('npc_range_wood').value = part;
    document.getElementById('npc_range_stone').value = part;
    document.getElementById('npc_range_rice').value = rest;

    updateNpcDisplay();
}

function onNpcRangeChange(type) {
    const val = document.getElementById(`npc_range_${type}`).value;
    document.getElementById(`npc_input_${type}`).value = val;
    updateNpcDisplay();
}

function onNpcInputChange(type) {
    const val = document.getElementById(`npc_input_${type}`).value;
    document.getElementById(`npc_range_${type}`).value = val;
    updateNpcDisplay();
}

function updateNpcDisplay() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    document.getElementById('npc_wood_val').innerText = w.toLocaleString('fr-FR');
    document.getElementById('npc_stone_val').innerText = s.toLocaleString('fr-FR');
    document.getElementById('npc_rice_val').innerText = r.toLocaleString('fr-FR');

    const sum = w + s + r;
    const diff = currentNpcTotal - sum;
    const alertEl = document.getElementById('npc_diff_alert');
    const submitBtn = document.getElementById('btnSubmitNpcExchange');

    if (diff !== 0) {
        alertEl.classList.remove('d-none');
        alertEl.innerText = diff > 0 ? `Il reste ${diff.toLocaleString('fr-FR')} ressources à assigner.` : `Excédent de ${Math.abs(diff).toLocaleString('fr-FR')} ressources réparties en trop.`;
        submitBtn.disabled = true;
    } else {
        alertEl.classList.add('d-none');
        submitBtn.disabled = false;
    }
}

async function submitNpcExchange() {
    const w = parseInt(document.getElementById('npc_input_wood').value || 0, 10);
    const s = parseInt(document.getElementById('npc_input_stone').value || 0, 10);
    const r = parseInt(document.getElementById('npc_input_rice').value || 0, 10);

    const fd = new FormData();
    fd.append('action', 'npc_exchange');
    fd.append('planet_id', currentNpcPlanetId);
    fd.append('wood', w);
    fd.append('stone', s);
    fd.append('rice', r);

    try {
        const res = await fetch('/api/seal.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showModalAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showModalAlert(data.error || 'Erreur lors du troc.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication.', 'error');
    }
}
</script>

