<?php
/**
 * Vue Partielle Mutualisée : Centre des Travaux & Chantiers du Fief (Multi-Activités)
 * Utilisée dans : views/city.php et views/resources.php (colonne droite / accordéon est)
 *
 * Regroupe :
 * 1. Chantiers de Bâtiments & Parcelles (BuildingEngine)
 * 2. Enrôlement des Soldats du Dojo (BarracksEngine)
 * 3. Machines de Siège, Navires & Haras (ShipyardEngine)
 * 4. Recherches Scientifiques de l'Académie (ResearchEngine)
 * 5. Banquets & Célébrations au Donjon Tenshu (PlanetEngine)
 * 6. Ateliers & Raffinages d'artisans (PlanetEngine / craft_queue)
 */

require_once __DIR__ . '/../../config/game_constants.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';

$queue = $queue ?? [];
$barracksQueue = $barracksQueue ?? [];
$shipyardQueue = $shipyardQueue ?? [];
$activeResearch = $activeResearch ?? null;
$activeFeast = $activeFeast ?? null;
$craftQueue = $craftQueue ?? [];

$queueTitle = $queueTitle ?? 'Activités & Chantiers du Fief';
$isTerran = $isTerran ?? (($planet['faction'] ?? ($user['faction'] ?? '')) === 'terran');
$isAdmin = $isAdmin ?? ((class_exists('Auth') && Auth::check() && Auth::isAdmin()) || !empty($user['is_admin']) || (($user['role'] ?? '') === 'admin'));

$totalActiveJobs = count($queue) 
    + count($barracksQueue) 
    + count($shipyardQueue) 
    + (!empty($activeResearch) ? 1 : 0) 
    + (!empty($activeFeast) ? 1 : 0) 
    + count($craftQueue);
?>

<div class="card shadow-sm border-0" id="urban-construction-queue-card">
    <!-- En-tête avec compteur consolidé -->
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-light border-bottom">
        <h3 class="card-title mb-0 fs-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-check text-warning"></i>
            <span><?= htmlspecialchars($queueTitle) ?></span>
        </h3>
        <div class="d-flex align-items-center gap-1">
            <?php if ($isTerran): ?>
                <span class="badge bg-danger-lt text-danger fw-bold" style="font-size:0.65rem;" title="Privilège Clan Oda : Chantiers rural &amp; urbain simultanés autorisés">
                    <i class="fa-solid fa-bolt me-1"></i>Oda
                </span>
            <?php endif; ?>
            <span class="badge bg-warning-lt fw-bold" style="font-size:0.75rem;">
                <i class="fa-solid fa-hourglass-half me-1"></i><?= $totalActiveJobs ?> actif(s)
            </span>
        </div>
    </div>

    <!-- Barre de filtrage rapide par pilier d'activité -->
    <?php if ($totalActiveJobs > 0): ?>
    <div class="p-1.5 border-bottom bg-white d-flex gap-1 flex-wrap align-items-center" id="queue-filter-bar" style="font-size:0.72rem;">
        <button type="button" class="btn btn-sm btn-dark py-0.5 px-2 queue-filter-btn active" onclick="filterQueueSection('all', this)">
            Tous <span class="badge bg-white text-dark ms-1"><?= $totalActiveJobs ?></span>
        </button>
        <?php if (count($queue) > 0): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 queue-filter-btn" onclick="filterQueueSection('buildings', this)">
            <i class="fa-solid fa-landmark me-1"></i>Bâtis <span class="badge bg-secondary text-white ms-1"><?= count($queue) ?></span>
        </button>
        <?php endif; ?>
        <?php if (count($barracksQueue) > 0): ?>
        <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 queue-filter-btn" onclick="filterQueueSection('troops', this)">
            <i class="fa-solid fa-user-ninja me-1"></i>Dojo <span class="badge bg-danger text-white ms-1"><?= count($barracksQueue) ?></span>
        </button>
        <?php endif; ?>
        <?php if (count($shipyardQueue) > 0): ?>
        <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0.5 px-2 queue-filter-btn" onclick="filterQueueSection('siege', this)">
            <i class="fa-solid fa-bomb me-1"></i>Siège / Haras <span class="badge bg-warning text-dark ms-1"><?= count($shipyardQueue) ?></span>
        </button>
        <?php endif; ?>
        <?php if (!empty($activeResearch)): ?>
        <button type="button" class="btn btn-sm btn-outline-info py-0.5 px-2 queue-filter-btn" onclick="filterQueueSection('research', this)">
            <i class="fa-solid fa-scroll me-1"></i>Savoirs <span class="badge bg-info text-white ms-1">1</span>
        </button>
        <?php endif; ?>
        <?php if (!empty($activeFeast)): ?>
        <button type="button" class="btn btn-sm btn-outline-purple py-0.5 px-2 queue-filter-btn text-purple" onclick="filterQueueSection('feast', this)">
            <i class="fa-solid fa-wine-bottle me-1"></i>Festivités <span class="badge bg-purple text-white ms-1">1</span>
        </button>
        <?php endif; ?>
        <?php if (count($craftQueue) > 0): ?>
        <button type="button" class="btn btn-sm btn-outline-success py-0.5 px-2 queue-filter-btn" onclick="filterQueueSection('craft', this)">
            <i class="fa-solid fa-boxes-stacked me-1"></i>Ateliers <span class="badge bg-success text-white ms-1"><?= count($craftQueue) ?></span>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card-body p-2" id="queue-items-container">
        <?php if ($totalActiveJobs === 0): ?>
            <!-- État vide apaisant & interactif -->
            <div class="text-center py-4 px-2 bg-light rounded border border-dashed">
                <span class="avatar avatar-md rounded-circle bg-secondary-lt text-secondary mb-2 mx-auto d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-torii-gate fs-2"></i>
                </span>
                <h4 class="fw-bold text-dark mb-1" style="font-size:0.92rem;">Aucune activité en cours sur ce fief</h4>
                <p class="text-secondary small mx-auto mb-3" style="max-width: 320px; font-size:0.78rem;">
                    Vos maçons, instructeurs du dojo, ingénieurs de siège et lettrés attendent vos décrets.
                </p>
                <div class="d-flex flex-wrap gap-1.5 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.75rem;" onclick="if (typeof openBuildingSlotModal === 'function') openBuildingSlotModal(19); else window.location.href='/?page=city';">
                        <i class="fa-solid fa-hammer me-1"></i>Bâtir
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size:0.75rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('barracks'); else window.location.href='/?page=barracks';">
                        <i class="fa-solid fa-user-ninja me-1"></i>Enrôler
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2" style="font-size:0.75rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('shipyard'); else window.location.href='/?page=shipyard';">
                        <i class="fa-solid fa-bomb me-1"></i>Engins Siège
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" style="font-size:0.75rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('research_lab'); else window.location.href='/?page=research';">
                        <i class="fa-solid fa-scroll me-1"></i>Rechercher
                    </button>
                </div>
            </div>
        <?php else: ?>

            <!-- 1. CHANTIERS DE BÂTIMENTS & PARCELLES -->
            <?php if (!empty($queue)): ?>
            <div class="queue-sec-group mb-2" data-cat="buildings">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-landmark text-primary me-1"></i>Élévations &amp; Chantiers Castraux (<?= count($queue) ?>)</span>
                </div>
                <?php foreach ($queue as $q): ?>
                    <?php
                        $isRural = ($q['build_category'] === 'rural_plot');
                        $isField = ($q['build_category'] === 'field');

                        if ($isRural) {
                            $rType = $q['target_id'];
                            $rMeta = RuralPlotEngine::STRUCTURES[$rType] ?? null;
                            $name = $rMeta ? $rMeta['name'] : ucfirst($rType);
                            $iconRaw = $rMeta['icon'] ?? 'fa-solid fa-seedling';
                            $catBadge = '<span class="badge bg-green-lt" style="font-size:0.65rem;">Domaine Rural</span>';
                        } elseif ($isField) {
                            $tSlot = (int)$q['target_id'];
                            $tType = isset($fieldsBySlot[$tSlot]['type']) ? $fieldsBySlot[$tSlot]['type'] : (FIELD_LAYOUT[$tSlot] ?? 'metal_mine');
                            $name = (FIELD_TYPES[$tType]['name'] ?? 'Parcelle') . " #{$tSlot}";
                            $iconRaw = FIELD_TYPES[$tType]['icon'] ?? 'fa-solid fa-wheat-awn';
                            $catBadge = '<span class="badge bg-success-lt" style="font-size:0.65rem;">Parcelle</span>';
                        } else {
                            $name = BUILDINGS[$q['target_id']]['name'] ?? $q['target_id'];
                            $iconRaw = BUILDINGS[$q['target_id']]['icon'] ?? 'fa-solid fa-landmark';
                            $catBadge = '<span class="badge bg-primary-lt" style="font-size:0.65rem;">Cité</span>';
                        }

                        if (strpos(trim($iconRaw), '<') === 0) {
                            $iconHtml = $iconRaw;
                        } else {
                            $iconHtml = '<i class="' . htmlspecialchars($iconRaw) . ' text-warning"></i>';
                        }

                        $qNow = time();
                        $qStart = (int)($q['started_at'] ?? $qNow);
                        $qEnd = (int)($q['finishes_at'] ?? $qNow);
                        $qTotal = max(1, $qEnd - $qStart);
                        $qElapsed = max(0, $qNow - $qStart);
                        $qPct = min(100, max(0, (int)round(($qElapsed / $qTotal) * 100)));
                        $isDemolish = ((int)$q['target_level'] === 0);
                    ?>
                    <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <div>
                                <h4 class="mb-0 fw-bold d-flex align-items-center gap-1.5" style="font-size:0.86rem;">
                                    <?= $iconHtml ?>
                                    <span><?= htmlspecialchars($name) ?></span>
                                </h4>
                                <div class="mt-0.5 d-flex align-items-center gap-1 flex-wrap">
                                    <?php if ($isDemolish): ?>
                                        <span class="badge bg-danger-lt fw-bold" style="font-size:0.68rem;">
                                            <i class="fa-solid fa-trash-can me-1"></i>Démolition
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt fw-bold" style="font-size:0.68rem;">
                                            Niveau <?= $q['target_level'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?= $catBadge ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center gap-1 px-1.5 py-0.5 shadow-sm" 
                                        style="font-size:0.72rem;"
                                        title="<?= $isAdmin ? 'Terminer instantanément sans coût (Admin)' : 'Terminer instantanément (2 Kobans)' ?>"
                                        onclick="instantFinishBuild(<?= (int)$q['id'] ?>)">
                                    <i class="fa-solid fa-bolt text-danger"></i>
                                    <span><?= $isAdmin ? 'Gratuit' : '2' ?></span>
                                    <i class="fa-solid <?= $isAdmin ? 'fa-bolt text-warning' : 'fa-coins' ?>"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-icon btn-outline-danger py-0.5 px-1" 
                                        style="font-size:0.72rem;"
                                        title="Annuler ce chantier (Remboursement de 80%)"
                                        onclick="<?= $isRural ? "typeof cancelRuralUpgrade === 'function' ? cancelRuralUpgrade({$q['id']}, this) : cancelBuild({$q['id']})" : "cancelBuild({$q['id']})" ?>">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="queue-progress-box mt-0.5">
                            <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolish ? 'danger' : 'warning' ?> building-progress-bar"
                                     role="progressbar"
                                     style="width: <?= $qPct ?>%;"
                                     data-started="<?= $qStart ?>"
                                     data-finishes="<?= $qEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                                <span class="text-secondary">Avancement : <strong class="text-dark building-progress-pct"><?= $qPct ?>%</strong></span>
                                <span class="queue-timer font-monospace fw-bold text-danger building-time-remaining" data-countdown="<?= $qEnd ?>">En cours</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 2. ENRÔLEMENT DES TROUPES AU DOJO (SOLDATS) -->
            <?php if (!empty($barracksQueue)): ?>
            <div class="queue-sec-group mb-2" data-cat="troops">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-user-ninja text-danger me-1"></i>Entraînement au Dojo Militaire (<?= count($barracksQueue) ?>)</span>
                    <button type="button" class="btn btn-link p-0 text-danger text-decoration-none small" style="font-size:0.7rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('barracks'); else window.location.href='/?page=barracks';">
                        Gérer &rarr;
                    </button>
                </div>
                <?php foreach ($barracksQueue as $bq): ?>
                    <?php
                        $bNow = time();
                        $bStart = (int)($bq['started_at'] ?? $bNow);
                        $bEnd = (int)($bq['finishes_at'] ?? $bNow);
                        $bTotal = max(1, $bEnd - $bStart);
                        $bElapsed = max(0, $bNow - $bStart);
                        $bPct = isset($bq['lotPct']) ? round($bq['lotPct']) : min(100, max(0, (int)round(($bElapsed / $bTotal) * 100)));
                        $bImg = !empty($bq['unit_image']) ? '/public/assets/units/' . $bq['unit_image'] : '/public/assets/units/' . ($bq['unit_code'] ?? 'ashigaru') . '.jpg';
                    ?>
                    <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= htmlspecialchars($bImg) ?>" alt="Troupe" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;" onerror="this.src='/public/assets/units/unit_ashigaru.jpg'">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="font-size:0.86rem;">
                                        <?= htmlspecialchars($bq['unit_name'] ?? $bq['unit_code'] ?? 'Troupe Féodale') ?>
                                    </h4>
                                    <div class="mt-0.5 d-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-danger-lt text-danger font-monospace" style="font-size:0.68rem;">
                                            <?= (int)($bq['count'] ?? 1) ?> recrue(s)
                                        </span>
                                        <?php if (!empty($bq['total_count']) && $bq['total_count'] > $bq['count']): ?>
                                            <span class="badge bg-secondary-lt" style="font-size:0.65rem;">
                                                <?= (int)$bq['completed_count'] ?> / <?= (int)$bq['total_count'] ?> prêtes
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-1.5" style="font-size:0.72rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('barracks'); else window.location.href='/?page=barracks';">
                                <i class="fa-solid fa-user-ninja me-1"></i>Dojo
                            </button>
                        </div>
                        <div class="queue-progress-box mt-0.5">
                            <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger building-progress-bar"
                                     role="progressbar"
                                     style="width: <?= $bPct ?>%;"
                                     data-started="<?= $bStart ?>"
                                     data-finishes="<?= $bEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                                <span class="text-secondary">Formation : <strong class="text-dark building-progress-pct"><?= $bPct ?>%</strong></span>
                                <span class="queue-timer font-monospace fw-bold text-danger" data-countdown="<?= $bEnd ?>">En cours</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 3. MACHINES DE SIÈGE, NAVIRES & CAVALERIE AUX HARAS -->
            <?php if (!empty($shipyardQueue)): ?>
            <div class="queue-sec-group mb-2" data-cat="siege">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-bomb text-warning me-1"></i>Machines de Siège &amp; Haras (<?= count($shipyardQueue) ?>)</span>
                    <button type="button" class="btn btn-link p-0 text-warning text-decoration-none small" style="font-size:0.7rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('shipyard'); else window.location.href='/?page=shipyard';">
                        Gérer &rarr;
                    </button>
                </div>
                <?php foreach ($shipyardQueue as $sq): ?>
                    <?php
                        $sCode = $sq['ship_code'] ?? '';
                        $sName = $sq['ship_name'] ?? $sCode;
                        $isSiege = in_array($sCode, ['terran_cruiser', 'terran_dreadnought', 'vorash_leviathan', 'aethelis_prism', 'aethelis_titan'], true) 
                                   || stripos($sName, 'siège') !== false 
                                   || stripos($sName, 'catapulte') !== false 
                                   || stripos($sName, 'bélier') !== false 
                                   || stripos($sName, 'trébuchet') !== false;

                        $sNow = time();
                        $sStart = (int)($sq['started_at'] ?? $sNow);
                        $sEnd = (int)($sq['finishes_at'] ?? $sNow);
                        $sTotal = max(1, $sEnd - $sStart);
                        $sElapsed = max(0, $sNow - $sStart);
                        $sPct = isset($sq['lotPct']) ? round($sq['lotPct']) : min(100, max(0, (int)round(($sElapsed / $sTotal) * 100)));
                        $sImg = !empty($sq['ship_image']) ? '/public/assets/units/' . $sq['ship_image'] : '/public/assets/units/' . $sCode . '.jpg';
                    ?>
                    <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= htmlspecialchars($sImg) ?>" alt="Unité de siège" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;" onerror="this.src='/public/assets/units/unit_cavalry.jpg'">
                                <div>
                                    <h4 class="mb-0 fw-bold" style="font-size:0.86rem;">
                                        <?= htmlspecialchars($sName) ?>
                                    </h4>
                                    <div class="mt-0.5 d-flex align-items-center gap-1 flex-wrap">
                                        <?php if ($isSiege): ?>
                                            <span class="badge bg-danger-lt text-danger font-monospace" style="font-size:0.68rem;">
                                                <i class="fa-solid fa-bomb me-1"></i><?= (int)($sq['count'] ?? 1) ?> Engin(s) Siège
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-lt text-dark font-monospace" style="font-size:0.68rem;">
                                                <i class="fa-solid fa-horse me-1"></i><?= (int)($sq['count'] ?? 1) ?> Monture(s)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0.5 px-1.5" style="font-size:0.72rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('shipyard'); else window.location.href='/?page=shipyard';">
                                <i class="fa-solid fa-horse me-1"></i><?= $isSiege ? 'Siège' : 'Haras' ?>
                            </button>
                        </div>
                        <div class="queue-progress-box mt-0.5">
                            <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isSiege ? 'danger' : 'warning' ?> building-progress-bar"
                                     role="progressbar"
                                     style="width: <?= $sPct ?>%;"
                                     data-started="<?= $sStart ?>"
                                     data-finishes="<?= $sEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                                <span class="text-secondary">Assemblage : <strong class="text-dark building-progress-pct"><?= $sPct ?>%</strong></span>
                                <span class="queue-timer font-monospace fw-bold text-<?= $isSiege ? 'danger' : 'warning-emphasis' ?>" data-countdown="<?= $sEnd ?>">En cours</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 4. ACADÉMIE DES SAVOIRS (RECHERCHE SCIENTIFIQUE) -->
            <?php if (!empty($activeResearch)): ?>
            <?php
                $rNow = time();
                $rStart = (int)($activeResearch['started_at'] ?? $rNow);
                $rEnd = (int)($activeResearch['finishes_at'] ?? $rNow);
                $rTotal = max(1, $rEnd - $rStart);
                $rElapsed = max(0, $rNow - $rStart);
                $rPct = min(100, max(0, (int)round(($rElapsed / $rTotal) * 100)));
            ?>
            <div class="queue-sec-group mb-2" data-cat="research">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-scroll text-info me-1"></i>Académie des Savoirs &amp; Parchemins</span>
                    <button type="button" class="btn btn-link p-0 text-info text-decoration-none small" style="font-size:0.7rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('research_lab'); else window.location.href='/?page=research';">
                        Gérer &rarr;
                    </button>
                </div>
                <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded bg-info-lt text-info">
                                <i class="fa-solid fa-scroll"></i>
                            </span>
                            <div>
                                <h4 class="mb-0 fw-bold" style="font-size:0.86rem;">
                                    <?= htmlspecialchars($activeResearch['research_name'] ?? $activeResearch['tech_name'] ?? $activeResearch['name'] ?? 'Traité de Stratégie') ?>
                                </h4>
                                <div class="mt-0.5">
                                    <span class="badge bg-info-lt text-info font-monospace" style="font-size:0.68rem;">
                                        Étude vers Niveau <?= (int)($activeResearch['target_level'] ?? 1) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info py-0.5 px-1.5" style="font-size:0.72rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('research_lab'); else window.location.href='/?page=research';">
                            <i class="fa-solid fa-scroll me-1"></i>Académie
                        </button>
                    </div>
                    <div class="queue-progress-box mt-0.5">
                        <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info building-progress-bar"
                                 role="progressbar"
                                 style="width: <?= $rPct ?>%;"
                                 data-started="<?= $rStart ?>"
                                 data-finishes="<?= $rEnd ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                            <span class="text-secondary">Méditation : <strong class="text-dark building-progress-pct"><?= $rPct ?>%</strong></span>
                            <span class="queue-timer font-monospace fw-bold text-info" data-countdown="<?= $rEnd ?>">En cours</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 5. BANQUETS & CÉLÉBRATIONS AU DONJON TENSHU -->
            <?php if (!empty($activeFeast)): ?>
            <?php
                $feastMeta = [
                    'matsuri' => ['Matsuri des Saisons', 'fa-solid fa-fan text-warning', '+500 Culture'],
                    'warriors' => ['Banquet des Guerriers (Kanpai)', 'fa-solid fa-khanda text-danger', '+1,200 Culture & +10% Attaque'],
                    'imperial' => ['Grand Banquet Impérial', 'fa-solid fa-crown text-purple', '+2,500 Culture & Diplomatie']
                ];
                $fType = $activeFeast['feast_type'] ?? 'matsuri';
                $fInfo = $feastMeta[$fType] ?? ['Célébration Shogunale', 'fa-solid fa-champagne-glasses text-warning', 'Festivités'];

                $fNow = time();
                $fStart = (int)($activeFeast['started_at'] ?? ($fNow - 60));
                $fEnd = (int)($activeFeast['finishes_at'] ?? $fNow);
                $fTotal = max(1, $fEnd - $fStart);
                $fElapsed = max(0, $fNow - $fStart);
                $fPct = min(100, max(0, (int)round(($fElapsed / $fTotal) * 100)));
            ?>
            <div class="queue-sec-group mb-2" data-cat="feast">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-wine-bottle text-purple me-1"></i>Salle des Banquets au Tenshu</span>
                    <button type="button" class="btn btn-link p-0 text-purple text-decoration-none small" style="font-size:0.7rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('hq');">
                        Donjon &rarr;
                    </button>
                </div>
                <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-xs rounded bg-purple-lt text-purple">
                                <i class="<?= $fInfo[1] ?>"></i>
                            </span>
                            <div>
                                <h4 class="mb-0 fw-bold" style="font-size:0.86rem;">
                                    <?= htmlspecialchars($activeFeast['name'] ?? $fInfo[0]) ?>
                                </h4>
                                <div class="mt-0.5">
                                    <span class="badge bg-purple-lt text-purple font-monospace" style="font-size:0.68rem;">
                                        <?= htmlspecialchars($fInfo[2]) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-1.5" style="font-size:0.72rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('hq');">
                            <i class="fa-solid fa-wine-bottle me-1"></i>Tenshu
                        </button>
                    </div>
                    <div class="queue-progress-box mt-0.5">
                        <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-purple building-progress-bar"
                                 role="progressbar"
                                 style="width: <?= $fPct ?>%;"
                                 data-started="<?= $fStart ?>"
                                 data-finishes="<?= $fEnd ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                            <span class="text-secondary">Festivités : <strong class="text-dark building-progress-pct"><?= $fPct ?>%</strong></span>
                            <span class="queue-timer font-monospace fw-bold text-purple" data-countdown="<?= $fEnd ?>">En cours</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 6. ATELIERS & RAFFINAGE FÉODAL (MEUNERIE, CHARPENTE, GRANIT, FORGE) -->
            <?php if (!empty($craftQueue)): ?>
            <div class="queue-sec-group mb-2" data-cat="craft">
                <div class="small fw-bold text-uppercase text-muted px-1 mb-1 d-flex justify-content-between align-items-center" style="font-size:0.68rem; letter-spacing:0.5px;">
                    <span><i class="fa-solid fa-boxes-stacked text-success me-1"></i>Ateliers d'Artisans &amp; Raffinage (<?= count($craftQueue) ?>)</span>
                </div>
                <?php foreach ($craftQueue as $cq): ?>
                    <?php
                        $cNow = time();
                        $cStart = (int)($cq['started_at'] ?? $cNow);
                        $cEnd = (int)($cq['finishes_at'] ?? $cNow);
                        $cTotal = max(1, $cEnd - $cStart);
                        $cElapsed = max(0, $cNow - $cStart);
                        $cPct = min(100, max(0, (int)round(($cElapsed / $cTotal) * 100)));
                        $prodName = $cq['product_name'] ?? ucfirst(str_replace('_', ' ', $cq['product'] ?? 'Ressource'));
                        $prodIcon = $cq['product_icon'] ?? 'fa-solid fa-box';
                        $bldgType = $cq['building_type'] ?? 'grain_mill';
                    ?>
                    <div class="queue-item p-2 mb-1.5 rounded bg-surface-secondary border shadow-none" style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-xs rounded bg-success-lt text-success fs-5">
                                    <?php if (strpos($prodIcon, '<') === 0 || strpos($prodIcon, 'fa-') !== false): ?>
                                        <i class="<?= htmlspecialchars($prodIcon) ?>"></i>
                                    <?php else: ?>
                                        <span><?= $prodIcon ?></span>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <h4 class="mb-0 fw-bold" style="font-size:0.86rem;">
                                        <?= htmlspecialchars($prodName) ?>
                                    </h4>
                                    <div class="mt-0.5">
                                        <span class="badge bg-success-lt text-success font-monospace" style="font-size:0.68rem;">
                                            +<?= number_format((float)$cq['produced_amount']) ?> unités
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success py-0.5 px-1.5" style="font-size:0.72rem;" onclick="if (typeof openBuildingSlotModalByCode === 'function') openBuildingSlotModalByCode('<?= htmlspecialchars($bldgType) ?>');">
                                <i class="fa-solid fa-boxes-stacked me-1"></i>Atelier
                            </button>
                        </div>
                        <div class="queue-progress-box mt-0.5">
                            <div class="progress" style="height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success building-progress-bar"
                                     role="progressbar"
                                     style="width: <?= $cPct ?>%;"
                                     data-started="<?= $cStart ?>"
                                     data-finishes="<?= $cEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.72rem;">
                                <span class="text-secondary">Raffinage : <strong class="text-dark building-progress-pct"><?= $cPct ?>%</strong></span>
                                <span class="queue-timer font-monospace fw-bold text-success" data-countdown="<?= $cEnd ?>">En cours</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function filterQueueSection(cat, btn) {
    const card = btn.closest('#urban-construction-queue-card');
    if (!card) return;

    card.querySelectorAll('.queue-filter-btn').forEach(b => {
        b.classList.remove('active', 'btn-dark');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active', 'btn-dark');
    btn.classList.remove('btn-outline-secondary');

    const sections = card.querySelectorAll('.queue-sec-group');
    sections.forEach(sec => {
        if (cat === 'all' || sec.dataset.cat === cat) {
            sec.style.display = '';
        } else {
            sec.style.display = 'none';
        }
    });
}
</script>
