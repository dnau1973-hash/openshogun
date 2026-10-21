<?php
/**
 * Vue Partielle MVC : Configuration du Monde & Dimensionnement des Tuiles
 * Game Studio Général - OpenShogun
 * Fichier : views/gamestudio/general_world_config.php
 * Présentation sobre, ergonomique et unifiée (Sauvegarde globale en 1 clic).
 */
declare(strict_types=1);

// S'assurer que les données du monde sont disponibles
if (!isset($worldRadius)) {
    require_once __DIR__ . '/../../core/GameStudioEngine.php';
    $gEngine = new GameStudioEngine();
    $wData = $gEngine->getWorldConfigData();
    extract($wData);
}

// Récupération et purge du message flash
$generalFlash = $_SESSION['general_flash'] ?? null;
unset($_SESSION['general_flash']);
?>

<div class="gamestudio-world-config">
    <!-- Message Flash de confirmation ou d'avertissement -->
    <?php if ($generalFlash): ?>
        <div class="alert alert-<?= htmlspecialchars($generalFlash['type'] ?? 'info') ?> alert-dismissible shadow-sm mb-3" role="alert">
            <div class="d-flex align-items-center">
                <div class="me-3 fs-2">
                    <?php if (($generalFlash['type'] ?? '') === 'success'): ?>
                        <i class="fa-solid fa-circle-check text-success"></i>
                    <?php elseif (($generalFlash['type'] ?? '') === 'warning'): ?>
                        <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info text-primary"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <strong><?= ($generalFlash['type'] ?? '') === 'success' ? 'Succès :' : (($generalFlash['type'] ?? '') === 'warning' ? 'Avertissement :' : 'Information :') ?></strong>
                    <?= htmlspecialchars($generalFlash['msg'] ?? '') ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <!-- En-tête sobre avec Barre d'Action Unique -->
    <div class="card shadow-sm border mb-3">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="m-0 text-dark d-flex align-items-center gap-2 fw-bold" style="font-size:1.25rem;">
                    <i class="fa-solid fa-sliders text-primary"></i>Configuration Complète du Monde Féodal
                </h4>
                <div class="text-secondary small mt-0.5">
                    Ajustez l'arpentage territorial, l'écosystème des oasis, les multiplicateurs de vitesse, la dotation des nouveaux seigneurs et l'IA féodale.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary-lt py-2 px-3 fw-bold font-monospace">
                    <i class="fa-solid fa-shapes me-1"></i>Rayon Actuel : R = <?= (int)$worldRadius ?>
                </span>
                <span class="badge bg-dark-lt py-2 px-3 fw-bold font-monospace">
                    <i class="fa-solid fa-border-all me-1"></i><?= number_format((int)$totalTiles, 0, ',', ' ') ?> Tuiles Totales
                </span>
                <button type="submit" form="formWorldConfigAll" class="btn btn-primary px-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Enregistrer Tous les Paramètres</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ── FORMULAIRE GLOBAL UNIFIÉ DE CONFIGURATION ── -->
    <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formWorldConfigAll">
        <input type="hidden" name="action" value="save_all_world_settings">
        <input type="hidden" name="active_config_tab" id="active_config_tab_input" value="section-world-tiles-config">

        <!-- Navigation par Onglets Ergonomiques -->
        <div class="card card-tabs border shadow-sm mb-4">
            <ul class="nav nav-tabs nav-fill bg-light px-2 pt-2 border-bottom" role="tablist" id="worldConfigNavTabs">
                <li class="nav-item">
                    <a class="nav-link active fw-bold text-dark py-2.5 px-3" data-bs-toggle="tab" href="#section-world-tiles-config">
                        <i class="fa-solid fa-shapes text-primary me-1.5"></i>1. Dimension du Monde
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-dark py-2.5 px-3" data-bs-toggle="tab" href="#section-world-oases-config">
                        <i class="fa-solid fa-leaf text-success me-1.5"></i>2. Oasis &amp; Faune
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-dark py-2.5 px-3" data-bs-toggle="tab" href="#section-world-speed-config">
                        <i class="fa-solid fa-bolt text-danger me-1.5"></i>3. Vitesses &amp; Équilibrage
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-dark py-2.5 px-3" data-bs-toggle="tab" href="#section-world-starting-endowment">
                        <i class="fa-solid fa-gift text-purple me-1.5"></i>4. Dotation &amp; Immunité
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-dark py-2.5 px-3" data-bs-toggle="tab" href="#section-world-bots-config">
                        <i class="fa-solid fa-robot text-indigo me-1.5"></i>5. Clans IA &amp; PNJ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-danger py-2.5 px-3" data-bs-toggle="tab" href="#section-world-regeneration-config">
                        <i class="fa-solid fa-arrows-rotate text-danger me-1.5"></i>6. Réinitialisation
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 1 : DIMENSION DU MONDE & TUILES                         -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade show active" id="section-world-tiles-config" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-shapes text-primary"></i>1. Dimension du Monde &amp; Nombre de Tuiles Générées
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Définit la frontière cartésienne maximale de l'archipel [−R ; +R]. Le total de tuiles suit la loi géométrique (2R + 1)².
                                </div>
                            </div>
                            <span class="badge bg-primary-lt font-monospace fs-5 px-3 py-1.5" id="badge-total-tiles-preview">
                                <?= number_format((int)$totalTiles, 0, ',', ' ') ?> Tuiles
                            </span>
                        </div>

                        <!-- Contrôle du Rayon -->
                        <div class="row g-4 align-items-center mb-4">
                            <div class="col-lg-7">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-ruler-combined text-primary me-1"></i>Rayon Géographique de la Carte (R) :</span>
                                    <span class="text-secondary small font-monospace">
                                        Coordonnées de <strong class="text-primary" id="label-coord-range">[−<?= (int)$worldRadius ?> : +<?= (int)$worldRadius ?>]</strong>
                                    </span>
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range flex-grow-1" min="10" max="150" step="1" 
                                           id="worldRadiusSlider" name="world_radius_slider" value="<?= (int)$worldRadius ?>">
                                    <div class="input-group input-group-sm" style="width: 130px;">
                                        <span class="input-group-text font-monospace fw-bold bg-light">R =</span>
                                        <input type="number" class="form-control font-monospace fw-bold text-center" 
                                               id="worldRadiusInput" name="world_radius" min="10" max="200" value="<?= (int)$worldRadius ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Préréglages Recommandés -->
                            <div class="col-lg-5">
                                <div class="text-muted small fw-bold mb-1.5"><i class="fa-solid fa-bookmark me-1 text-primary"></i>Préréglages d'Archipel :</div>
                                <div class="d-flex flex-wrap gap-1.5">
                                    <?php foreach ($presets as $p): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary preset-btn <?= ((int)$p['radius'] === (int)$worldRadius) ? 'btn-primary text-white shadow-xs' : '' ?>" 
                                                data-radius="<?= (int)$p['radius'] ?>" 
                                                onclick="applyWorldPreset(<?= (int)$p['radius'] ?>)"
                                                title="<?= htmlspecialchars($p['name']) ?> (Grille <?= $p['dimension'] ?>)">
                                            <i class="fa-solid <?= $p['icon'] ?> me-1"></i>R=<?= $p['radius'] ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Alerte Sécurité si R < max requis -->
                        <div id="boxRadiusSecurityWarning" class="alert alert-warning mb-4 <?= ($worldRadius >= $minRequiredRadius) ? 'd-none' : '' ?>">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-warning"></i>
                                <div>
                                    <strong>Avertissement Géographique :</strong> Le rayon sélectionné (<span id="warnSelectedRadius"><?= (int)$worldRadius ?></span>) est inférieur à la coordonnée maximale actuellement occupée par une entité (<?= (int)$minRequiredRadius ?>).
                                </div>
                            </div>
                        </div>

                        <!-- 4 Indicateurs Clés -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center h-100">
                                    <div class="text-muted small fw-bold text-uppercase">Dimension Grille</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-dark" id="kpi-grid-dim"><?= $gridDimension ?> × <?= $gridDimension ?></div>
                                    <span class="text-muted small font-monospace">[−R : +R] inclus</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center h-100">
                                    <div class="text-muted small fw-bold text-uppercase">Total des Tuiles</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-primary" id="kpi-total-tiles"><?= number_format((int)$totalTiles, 0, ',', ' ') ?></div>
                                    <span class="text-muted small font-monospace">(2R + 1)² tuiles</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center h-100">
                                    <div class="text-muted small fw-bold text-uppercase">Périmètre Frontière</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-secondary" id="kpi-perimeter"><?= number_format((int)$boundaryPerimeter, 0, ',', ' ') ?></div>
                                    <span class="text-muted small font-monospace">Tuiles du pourtour</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center h-100">
                                    <div class="text-muted small fw-bold text-uppercase">Taux de Saturation</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-info" id="kpi-saturation"><?= $saturationRate ?>%</div>
                                    <span class="text-muted small font-monospace"><span id="kpi-total-occupied-base"><?= number_format((int)$totalOccupied, 0, ',', ' ') ?></span> occupées</span>
                                </div>
                            </div>
                        </div>

                        <!-- Synthèse Décomposition & Quadrants -->
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="border rounded p-3 bg-white h-100">
                                    <h5 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-tree text-success me-1.5"></i>Biomes Naturels Déterministes</h5>
                                    <p class="text-muted small mb-2">Répartition estimée des terrains vierges non colonisés :</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless m-0 font-monospace small">
                                            <tr><td><span class="badge bg-success-lt me-1.5">Plaines &amp; Rizières</span></td><td class="text-end fw-bold"><?= number_format($biomesEstimate['plains'] ?? 0, 0, ',', ' ') ?> (60%)</td></tr>
                                            <tr><td><span class="badge bg-teal-lt me-1.5">Forêts de Bambous</span></td><td class="text-end fw-bold"><?= number_format($biomesEstimate['forest'] ?? 0, 0, ',', ' ') ?> (15%)</td></tr>
                                            <tr><td><span class="badge bg-secondary-lt me-1.5">Monts Sacrés</span></td><td class="text-end fw-bold"><?= number_format($biomesEstimate['mountain'] ?? 0, 0, ',', ' ') ?> (12%)</td></tr>
                                            <tr><td><span class="badge bg-warning-lt me-1.5">Collines &amp; Carrières</span></td><td class="text-end fw-bold"><?= number_format($biomesEstimate['hills'] ?? 0, 0, ',', ' ') ?> (8%)</td></tr>
                                            <tr><td><span class="badge bg-primary-lt me-1.5">Lacs &amp; Rivières</span></td><td class="text-end fw-bold"><?= number_format($biomesEstimate['lake'] ?? 0, 0, ',', ' ') ?> (5%)</td></tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="border rounded p-3 bg-white h-100">
                                    <h5 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-compass text-danger me-1.5"></i>Découpage Tactique des 4 Quadrants</h5>
                                    <p class="text-muted small mb-2">Implantation des fiefs autour de la Capitale Impériale [0:0] :</p>
                                    <div class="row g-2 text-center font-monospace small">
                                        <div class="col-6"><div class="p-2 border rounded bg-light"><strong>Nord-Ouest (NO)</strong><br><span class="text-primary fw-bold"><?= (int)($quadrants['NO'] ?? 0) ?> fiefs</span></div></div>
                                        <div class="col-6"><div class="p-2 border rounded bg-light"><strong>Nord-Est (NE)</strong><br><span class="text-primary fw-bold"><?= (int)($quadrants['NE'] ?? 0) ?> fiefs</span></div></div>
                                        <div class="col-6"><div class="p-2 border rounded bg-light"><strong>Sud-Ouest (SO)</strong><br><span class="text-primary fw-bold"><?= (int)($quadrants['SO'] ?? 0) ?> fiefs</span></div></div>
                                        <div class="col-6"><div class="p-2 border rounded bg-light"><strong>Sud-Est (SE)</strong><br><span class="text-primary fw-bold"><?= (int)($quadrants['SE'] ?? 0) ?> fiefs</span></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 2 : OASIS NATURELLES & FAUNE SAUVAGE                   -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="section-world-oases-config" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-leaf text-success"></i>2. Configuration &amp; Écosystème des Oasis Naturelles
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Maîtrisez la densité de couverture (%), le cycle de réapparition automatique après capture et la garnison de faune sauvage.
                                </div>
                            </div>
                            <span class="badge bg-success-lt font-monospace fs-5 px-3 py-1.5">
                                <?= (int)$oasesCount ?> Oasis Actives &bull; <?= (int)$oasisFaunaCount ?> Bêtes
                            </span>
                        </div>

                        <!-- Paramètres Principaux des Oasis -->
                        <div class="row g-4 align-items-center mb-4">
                            <div class="col-lg-7">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-percent text-success me-1"></i>Densité de Couverture des Oasis (% du monde) :</span>
                                    <span class="text-secondary small font-monospace">
                                        Cible : <strong class="text-success" id="label-target-oases-count"><?= number_format((int)$targetOasesCount, 0, ',', ' ') ?></strong> oasis
                                    </span>
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range flex-grow-1" min="0.5" max="10.0" step="0.1" 
                                           id="oasisDensitySlider" name="oasis_density_slider" value="<?= number_format($oasisDensityPercent, 1, '.', '') ?>">
                                    <div class="input-group input-group-sm" style="width: 130px;">
                                        <input type="number" class="form-control font-monospace fw-bold text-center" 
                                               id="oasisDensityInput" name="oasis_density_percent" min="0.5" max="20.0" step="0.1" 
                                               value="<?= number_format($oasisDensityPercent, 1, '.', '') ?>">
                                        <span class="input-group-text font-monospace bg-light">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="p-3 border rounded bg-light">
                                    <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-check-label fw-bold text-dark cursor-pointer" for="oasisRespawnToggle">
                                                Réapparition Auto Après Capture
                                            </label>
                                            <div class="text-muted small">Fait réapparaître une oasis sauvage à la perte d'un fief</div>
                                        </div>
                                        <input class="form-check-input fs-3 cursor-pointer" type="checkbox" id="oasisRespawnToggle" 
                                               name="oasis_respawn_on_capture" value="1" <?= $oasisRespawnOnCapture ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Indicateurs Oasis -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Total Oasis Actives</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-success"><?= (int)$oasesCount ?></div>
                                    <span class="text-muted small">Sur <?= number_format((int)$totalTiles, 0, ',', ' ') ?> tuiles</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Oasis Capturées</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-primary"><?= (int)$oasisCaptured ?></div>
                                    <span class="text-muted small">Rattachées à un fief</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Oasis Sauvages Libres</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-teal"><?= (int)$oasisWild ?></div>
                                    <span class="text-muted small">Prêtes à être annexées</span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="p-3 border rounded bg-light text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Garnison Faune Sauvage</div>
                                    <div class="h2 m-0 mt-1 fw-bold font-monospace text-danger"><?= (int)$oasisFaunaCount ?></div>
                                    <span class="text-muted small">Loups, Ours, Sangliers</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Ponctuelles de Maintenance des Oasis -->
                        <div class="p-3 border rounded bg-white mb-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <strong class="text-dark"><i class="fa-solid fa-wrench text-secondary me-1.5"></i>Actions Immédiates sur l'Écosystème :</strong>
                                <span class="text-muted small">Ces opérations s'exécutent indépendamment de la sauvegarde générale.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="confirmRepopulateOases()">
                                    <i class="fa-solid fa-seedling me-1"></i>Régénérer &amp; Rééquilibrer les Oasis
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('formRespawnFauna').submit()">
                                    <i class="fa-solid fa-paw me-1"></i>Réapparition Complète de la Faune Sauvage
                                </button>
                            </div>
                        </div>

                        <!-- Tableau de Registre des Oasis -->
                        <div class="border rounded p-3 bg-white" id="tableOasesList">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <h5 class="fw-bold m-0 text-dark"><i class="fa-solid fa-list-check text-success me-1.5"></i>Registre des Oasis Déployées (<?= count($allOasesList) ?> premières)</h5>
                                <input type="text" class="form-control form-control-sm" style="width: 220px;" 
                                       placeholder="Filtrer par nom ou coords..." id="filterOasisTable" onkeyup="filterOasesRows()">
                            </div>
                            <div class="table-responsive" style="max-height: 280px;">
                                <table class="table table-sm table-hover table-vcenter m-0 small">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Nom de l'Oasis</th>
                                            <th>Coordonnées</th>
                                            <th>Ressource &amp; Bonus</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($allOasesList)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Aucune oasis actuellement générée.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($allOasesList as $oa): ?>
                                                <tr class="oasis-data-row">
                                                    <td class="fw-bold oasis-name-col"><?= htmlspecialchars($oa['name'] ?? 'Oasis Sauvage') ?></td>
                                                    <td class="font-monospace oasis-coords-col">[<?= (int)$oa['coord_x'] ?> : <?= (int)$oa['coord_y'] ?>]</td>
                                                    <td><span class="badge bg-light text-dark font-monospace">+<?= (int)($oa['bonus_percent'] ?? 25) ?>% <?= htmlspecialchars($oa['resource_type'] ?? 'Matériaux') ?></span></td>
                                                    <td>
                                                        <?php if (!empty($oa['owner_planet_id'])): ?>
                                                            <span class="badge bg-primary-lt">Occupée par <?= htmlspecialchars($oa['owner_username'] ?? 'Daimyō') ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-lt">Sauvage (Libre)</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 3 : VITESSES & ÉQUILIBRAGE DU MONDE FÉODAL             -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="section-world-speed-config" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-bolt text-danger"></i>3. Vitesses &amp; Équilibrage du Monde Féodal
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Multiplicateurs temporels majeurs : chantiers, production des ressources, marche des armées et règles de famine.
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 font-monospace">
                                <span class="badge bg-primary-lt font-monospace" id="badge-header-game-speed">x<?= (int)$gameSpeed ?> Jeu</span>
                                <span class="badge bg-success-lt font-monospace" id="badge-header-resource-speed">x<?= (int)$resourceSpeed ?> Prod</span>
                                <span class="badge bg-danger-lt font-monospace" id="badge-header-fleet-speed">x<?= (int)$fleetSpeed ?> Marche</span>
                            </div>
                        </div>

                        <!-- Préréglages Rapides de Vitesse -->
                        <div class="mb-4 d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-gauge-high me-1 text-danger"></i>Rythme du Monde :</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary speed-preset-btn" onclick="applyDevSpeedPreset(1, 1, 1, this)">x1 (Classique RTS)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary speed-preset-btn" onclick="applyDevSpeedPreset(5, 5, 5, this)">x5 (Standard Shogun)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary speed-preset-btn" onclick="applyDevSpeedPreset(10, 10, 8, this)">x10 (Guerre Rapide)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary speed-preset-btn" onclick="applyDevSpeedPreset(25, 25, 15, this)">x25 (Blitzkrieg)</button>
                        </div>

                        <!-- 3 Multiplicateurs Majeurs -->
                        <div class="row g-4 mb-4">
                            <!-- Vitesse Jeu -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="fa-solid fa-hammer text-primary me-1"></i>Vitesse du Jeu (Chantiers) :</span>
                                        <span class="badge bg-primary text-white font-monospace" id="badge-val-game-speed">x<?= (int)$gameSpeed ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <input type="range" class="form-range flex-grow-1" min="1" max="50" step="1" 
                                               id="sliderGameSpeed" name="game_speed_slider" value="<?= (int)$gameSpeed ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace fw-bold text-center" style="width: 70px;" 
                                               id="inputGameSpeed" name="game_speed" min="1" max="100" value="<?= (int)$gameSpeed ?>">
                                    </div>
                                    <div class="text-muted small mt-1.5">Accélère bâtiments et recherches.</div>
                                </div>
                            </div>

                            <!-- Vitesse Ressources -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="fa-solid fa-wheat-awn text-success me-1"></i>Vitesse Production :</span>
                                        <span class="badge bg-success text-white font-monospace" id="badge-val-resource-speed">x<?= (int)$resourceSpeed ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <input type="range" class="form-range flex-grow-1" min="1" max="50" step="1" 
                                               id="sliderResourceSpeed" name="resource_speed_slider" value="<?= (int)$resourceSpeed ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace fw-bold text-center" style="width: 70px;" 
                                               id="inputResourceSpeed" name="resource_speed" min="1" max="100" value="<?= (int)$resourceSpeed ?>">
                                    </div>
                                    <div class="text-muted small mt-1.5">Multiplie le rendement horaire.</div>
                                </div>
                            </div>

                            <!-- Vitesse Marche -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="fa-solid fa-person-running text-danger me-1"></i>Vitesse Marche Armées :</span>
                                        <span class="badge bg-danger text-white font-monospace" id="badge-val-fleet-speed">x<?= (int)$fleetSpeed ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <input type="range" class="form-range flex-grow-1" min="1" max="30" step="1" 
                                               id="sliderFleetSpeed" name="fleet_speed_slider" value="<?= (int)$fleetSpeed ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace fw-bold text-center" style="width: 70px;" 
                                               id="inputFleetSpeed" name="fleet_speed" min="1" max="50" value="<?= (int)$fleetSpeed ?>">
                                    </div>
                                    <div class="text-muted small mt-1.5">Réduit le temps de trajet des troupes.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Simulateur d'Impact -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Tenshu Niv 10 (Chantier)</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-primary" id="sim-build-time">--</div>
                                    <span class="text-muted small">Base 1h00 sans bonus</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Trajet 10 Tuiles (Marche)</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-danger" id="sim-march-time">--</div>
                                    <span class="text-muted small">Infanterie Ashigaru</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Rizière Niv 10 (Rendement)</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-success" id="sim-resource-prod">--</div>
                                    <span class="text-muted small">Base 250 koku/h</span>
                                </div>
                            </div>
                        </div>

                        <!-- Règles Complémentaires (XP Héros, Cages, Famine) -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100">
                                    <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-award text-warning me-1.5"></i>Progression &amp; Butin du Héros</h5>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                            <span>Multiplicateur d'XP Héros :</span>
                                            <span class="badge bg-warning-lt font-monospace" id="badge-val-hero-xp"><?= (int)$heroXpRatePercent ?>%</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="range" class="form-range flex-grow-1" min="10" max="500" step="10" 
                                                   id="sliderHeroXp" name="hero_xp_slider" value="<?= (int)$heroXpRatePercent ?>">
                                            <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 75px;" 
                                                   id="inputHeroXp" name="hero_xp_rate_percent" min="10" max="500" value="<?= (int)$heroXpRatePercent ?>">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                            <span>Taux d'Obtention de Cages :</span>
                                            <span class="badge bg-secondary-lt font-monospace" id="badge-val-cage-drop"><?= (int)$heroCageDropRate ?>%</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="range" class="form-range flex-grow-1" min="0" max="100" step="5" 
                                                   id="sliderCageDrop" name="cage_drop_slider" value="<?= (int)$heroCageDropRate ?>">
                                            <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 75px;" 
                                                   id="inputCageDrop" name="hero_cage_drop_rate" min="0" max="100" value="<?= (int)$heroCageDropRate ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-white h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="fw-bold text-dark m-0"><i class="fa-solid fa-wheat-awn-circle-exclamation text-danger me-1.5"></i>Système de Famine Féodale</h5>
                                        <span class="badge bg-secondary-lt font-monospace" id="badge-famine-switch-state"><?= $famineEnabled ? 'En service' : 'Inactif' ?></span>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input cursor-pointer" type="checkbox" id="checkFamineEnabled" 
                                               name="famine_enabled" value="1" <?= $famineEnabled ? 'checked' : '' ?> onchange="updateFamineSwitchBadges(this.checked)">
                                        <label class="form-check-label fw-bold text-dark cursor-pointer" for="checkFamineEnabled">
                                            Activer le Péril de Famine (Désertion des troupes sans céréales)
                                        </label>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">Taux de perte :</label>
                                            <div class="d-flex align-items-center gap-1.5">
                                                <input type="range" class="form-range flex-grow-1" min="0.5" max="15.0" step="0.5" 
                                                       id="sliderFamineRate" name="famine_rate_slider" value="<?= $famineRate ?>">
                                                <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 65px;" 
                                                       id="inputFamineRate" name="famine_rate" min="0.5" max="25.0" step="0.5" value="<?= $famineRate ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-muted mb-1">Conso Farine :</label>
                                            <div class="d-flex align-items-center gap-1.5">
                                                <input type="range" class="form-range flex-grow-1" min="0.1" max="5.0" step="0.1" 
                                                       id="sliderFamineFlour" name="famine_flour_slider" value="<?= $famineFlourConsumption ?>">
                                                <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 65px;" 
                                                       id="inputFamineFlour" name="famine_flour_consumption" min="0.1" max="10.0" step="0.1" value="<?= $famineFlourConsumption ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 4 : DOTATION INITIALE & IMMUNITÉ DÉBUTANT              -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="section-world-starting-endowment" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-gift text-purple"></i>4. Dotation Initiale du Nouveau Seigneur &amp; Durée de l'Immunité
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Stock de départ octroyé à tout joueur ou bot créant son premier village, et durée du bouclier inviolable.
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-purple-lt font-monospace fs-5 px-3 py-1.5" id="badge-header-starter-res">
                                    <?= number_format($starterWood) ?> / <?= number_format($starterStone) ?> / <?= number_format($starterRice) ?> Matériaux
                                </span>
                                <span class="badge bg-warning-lt font-monospace fs-5 px-3 py-1.5" id="badge-header-starter-gold">
                                    <i class="fa-solid fa-coins me-1"></i><?= number_format($starterGoldCoins) ?> Ryō
                                </span>
                                <span class="badge bg-success-lt font-monospace fs-5 px-3 py-1.5" id="badge-header-starter-protection">
                                    <i class="fa-solid fa-shield-halved me-1"></i><?= (int)$beginnerProtectionDays ?> jours
                                </span>
                            </div>
                        </div>

                        <!-- Préréglages Rapides de Dotation -->
                        <div class="mb-4 d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-box-open me-1 text-purple"></i>Kits de Départ :</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary starter-preset-btn" onclick="applyStarterPreset(500, 500, 500, 100, 50, 7, this)">Économe (500)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary starter-preset-btn" onclick="applyStarterPreset(1000, 1000, 1000, 250, 100, 7, this)">Standard Shogun (1 000)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary starter-preset-btn" onclick="applyStarterPreset(2500, 2200, 1800, 450, 200, 14, this)">Généreux (2 500)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary starter-preset-btn" onclick="applyStarterPreset(5000, 5000, 5000, 1000, 500, 21, this)">Fast-Start (5 000)</button>
                        </div>

                        <!-- Contrôles des Ressources -->
                        <div class="row g-3 mb-4">
                            <!-- Bois -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-tree text-success me-1"></i>Bois Initial :</span>
                                        <span class="badge bg-success-lt font-monospace" id="badge-val-starter-wood"><?= number_format($starterWood) ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="100" max="10000" step="100" 
                                               id="sliderStarterWood" name="starter_wood_slider" value="<?= $starterWood ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterWood" name="starter_wood" min="0" max="50000" value="<?= $starterWood ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Pierre -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre Initiale :</span>
                                        <span class="badge bg-secondary-lt font-monospace" id="badge-val-starter-stone"><?= number_format($starterStone) ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="100" max="10000" step="100" 
                                               id="sliderStarterStone" name="starter_stone_slider" value="<?= $starterStone ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterStone" name="starter_stone" min="0" max="50000" value="<?= $starterStone ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Riz -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-seedling text-warning me-1"></i>Riz Initial :</span>
                                        <span class="badge bg-warning-lt font-monospace" id="badge-val-starter-rice"><?= number_format($starterRice) ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="100" max="10000" step="100" 
                                               id="sliderStarterRice" name="starter_rice_slider" value="<?= $starterRice ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterRice" name="starter_rice" min="0" max="50000" value="<?= $starterRice ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Poisson -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-fish text-primary me-1"></i>Poisson Initial :</span>
                                        <span class="badge bg-primary-lt font-monospace" id="badge-val-starter-fish"><?= number_format($starterFish) ?></span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="0" max="5000" step="50" 
                                               id="sliderStarterFish" name="starter_fish_slider" value="<?= $starterFish ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterFish" name="starter_fish" min="0" max="50000" value="<?= $starterFish ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Or (Ryō) -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-coins text-warning me-1"></i>Pièces d'Or (Ryō) :</span>
                                        <span class="badge bg-warning text-dark font-monospace" id="badge-val-starter-gold"><?= number_format($starterGoldCoins) ?> Ryō</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="0" max="2000" step="25" 
                                               id="sliderStarterGold" name="starter_gold_slider" value="<?= $starterGoldCoins ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterGold" name="starter_gold_coins" min="0" max="10000" value="<?= $starterGoldCoins ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Immunité Débutant -->
                            <div class="col-sm-6 col-lg-4">
                                <div class="p-3 border rounded bg-light">
                                    <label class="form-label small fw-bold text-dark d-flex justify-content-between mb-1">
                                        <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Immunité Débutant (Jours) :</span>
                                        <span class="badge bg-success text-white font-monospace" id="badge-val-starter-protection"><?= (int)$beginnerProtectionDays ?> jours</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="0" max="30" step="1" 
                                               id="sliderStarterProtection" name="starter_protection_slider" value="<?= (int)$beginnerProtectionDays ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 80px;" 
                                               id="inputStarterProtection" name="beginner_protection_days" min="0" max="60" value="<?= (int)$beginnerProtectionDays ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Simulateur d'Impact Débutant -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Tenshu Finançable Immédiatement</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-primary" id="sim-tenshu-level">--</div>
                                    <span class="text-muted small">Sans production préalable</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Parcelles Rurales Finançables</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-success" id="sim-plots-count">--</div>
                                    <span class="text-muted small">Niveau 1 (Bois/Pierre/Riz)</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white text-center">
                                    <div class="text-muted small fw-bold text-uppercase">Durée du Bouclier Inviolable</div>
                                    <div class="h3 m-0 mt-1 fw-bold font-monospace text-info" id="sim-protection-days"><?= (int)$beginnerProtectionDays ?> jours</div>
                                    <span class="text-muted small" id="sim-protection-date">Protégé contre tout raid</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small" id="label-protection-status-desc">
                            <i class="fa-solid fa-circle-check text-success me-1"></i>Trêve sacrée active : <strong><?= (int)$beginnerProtectionDays ?> jours</strong> de protection inviolable contre les assauts et raids ennemis.
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 5 : CLANS IA & DAIMYŌS AUTONOMES                       -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="section-world-bots-config" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-dark fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-robot text-indigo"></i>5. Gestion des Clans IA &amp; Daimyōs Autonomes
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Pilotez le moteur d'intelligence féodale, l'agressivité des clans rivaux (Oda, Takeda, Tokugawa) et la colonisation autonome.
                                </div>
                            </div>
                            <span class="badge bg-indigo-lt font-monospace fs-5 px-3 py-1.5" id="badge-bots-status-header">
                                <i class="fa-solid <?= $botsEnabled ? 'fa-microchip' : 'fa-moon' ?> me-1"></i>
                                <span id="label-bots-status-text"><?= $botsEnabled ? 'Cycles IA Actifs' : 'IA en Veille' ?></span>
                            </span>
                        </div>

                        <!-- Profils Stratégiques d'IA -->
                        <div class="mb-4 d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-chess-knight me-1 text-indigo"></i>Doctrine Globale :</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary bot-world-preset-btn" onclick="applyWorldBotsPreset('peaceful', this)">Économique / Pacifique</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary bot-world-preset-btn" onclick="applyWorldBotsPreset('moderate', this)">Sengoku Équilibré</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary bot-world-preset-btn" onclick="applyWorldBotsPreset('aggressive', this)">Conquête Agressive Oda</button>
                        </div>

                        <!-- 3 Switches Principaux -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-check-label fw-bold text-dark cursor-pointer" for="switchBotsEnabled">
                                                Moteur IA Actif
                                            </label>
                                            <div class="text-muted small">Exécute les cycles de décision</div>
                                        </div>
                                        <input class="form-check-input fs-3 cursor-pointer" type="checkbox" id="switchBotsEnabled" 
                                               name="bots_enabled" value="1" <?= $botsEnabled ? 'checked' : '' ?> onchange="updateBotsEnabledUI(this.checked)">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-check-label fw-bold text-dark cursor-pointer" for="switchBotColonize">
                                                Colonisation Autonome
                                            </label>
                                            <div class="text-muted small">Les bots fondent de nouveaux fiefs</div>
                                        </div>
                                        <input class="form-check-input fs-3 cursor-pointer" type="checkbox" id="switchBotColonize" 
                                               name="bot_colonize_enabled" value="1" <?= $botColonizeEnabled ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light h-100">
                                    <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-check-label fw-bold text-dark cursor-pointer" for="switchBotSpawn">
                                                Éclosion Spontanée
                                            </label>
                                            <div class="text-muted small">Création automatique de clans PNJ</div>
                                        </div>
                                        <input class="form-check-input fs-3 cursor-pointer" type="checkbox" id="switchBotSpawn" 
                                               name="bot_spawn_enabled" value="1" <?= $botSpawnEnabled ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Paramètres Détaillés des Bots -->
                        <div class="row g-3 mb-4">
                            <!-- Tempérament / Agressivité -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                        <span>Agressivité Militaire :</span>
                                        <span class="badge <?= in_array($botAggressiveness, ['passive', 'peaceful'], true) ? 'bg-success-lt' : ($botAggressiveness === 'aggressive' ? 'bg-danger-lt' : 'bg-warning-lt') ?> font-monospace text-uppercase" id="badge-val-bot-aggressiveness"><?= in_array($botAggressiveness, ['passive', 'peaceful'], true) ? 'PASSIF' : strtoupper($botAggressiveness) ?></span>
                                    </label>
                                    <div class="d-flex flex-column gap-2">
                                        <label class="form-check m-0 cursor-pointer">
                                            <input class="form-check-input" type="radio" name="bot_aggressiveness" value="passive" <?= in_array($botAggressiveness, ['passive', 'peaceful'], true) ? 'checked' : '' ?> onchange="updateAggressivenessBadge(this.value)">
                                            <span class="form-check-label small"><strong>Passif :</strong> Défense pure, aucun raid</span>
                                        </label>
                                        <label class="form-check m-0 cursor-pointer">
                                            <input class="form-check-input" type="radio" name="bot_aggressiveness" value="moderate" <?= $botAggressiveness === 'moderate' ? 'checked' : '' ?> onchange="updateAggressivenessBadge(this.value)">
                                            <span class="form-check-label small"><strong>Modéré :</strong> Raids mesurés si rentable</span>
                                        </label>
                                        <label class="form-check m-0 cursor-pointer">
                                            <input class="form-check-input" type="radio" name="bot_aggressiveness" value="aggressive" <?= $botAggressiveness === 'aggressive' ? 'checked' : '' ?> onchange="updateAggressivenessBadge(this.value)">
                                            <span class="form-check-label small"><strong>Belliqueux :</strong> Harcèlement actif des rivaux</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Fiefs Max par Clan -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                        <span>Max Fiefs par Clan :</span>
                                        <span class="badge bg-indigo-lt font-monospace" id="badge-val-bot-max-planets"><?= (int)$botMaxPlanets ?> fiefs / clan</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <input type="range" class="form-range flex-grow-1" min="1" max="10" step="1" 
                                               id="sliderBotMaxPlanets" name="bot_max_planets_slider" value="<?= (int)$botMaxPlanets ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 70px;" 
                                               id="inputBotMaxPlanets" name="bot_max_planets" min="1" max="10" value="<?= (int)$botMaxPlanets ?>">
                                    </div>
                                    <div class="text-muted small mt-1.5">Limite l'expansion impériale de chaque seigneur IA.</div>
                                </div>
                            </div>

                            <!-- Éclosion Spontanée Cadence & Plafond -->
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-white h-100">
                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                        <span>Intervalle d'Éclosion :</span>
                                        <span class="badge bg-secondary-lt font-monospace" id="badge-val-bot-spawn-interval"><?= (int)$botSpawnIntervalMin ?> min</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <input type="range" class="form-range flex-grow-1" min="5" max="120" step="5" 
                                               id="sliderBotSpawnInterval" name="bot_spawn_interval_slider" value="<?= (int)$botSpawnIntervalMin ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 70px;" 
                                               id="inputBotSpawnInterval" name="bot_spawn_interval_min" min="5" max="120" value="<?= (int)$botSpawnIntervalMin ?>">
                                    </div>

                                    <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1 mt-2">
                                        <span>Plafond Mondial Fiefs PNJ :</span>
                                        <span class="badge bg-secondary-lt font-monospace" id="badge-val-bot-spawn-max"><?= (int)$botSpawnMaxVillages ?> fiefs max</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" class="form-range flex-grow-1" min="5" max="200" step="5" 
                                               id="sliderBotSpawnMax" name="bot_spawn_max_slider" value="<?= (int)$botSpawnMaxVillages ?>">
                                        <input type="number" class="form-control form-control-sm font-monospace text-center" style="width: 70px;" 
                                               id="inputBotSpawnMax" name="bot_spawn_max_villages" min="5" max="200" value="<?= (int)$botSpawnMaxVillages ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Immédiates sur les Bots -->
                        <div class="p-3 border rounded bg-white mb-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <strong class="text-dark"><i class="fa-solid fa-bolt text-indigo me-1.5"></i>Actions Directes de Simulation :</strong>
                                <span class="text-muted small">Déclencheurs instantanés hors formulaire :</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" formaction="?page=gamestudio&tab=general&subtab=world_config" 
                                        name="action" value="spawn_preset_bots" class="btn btn-sm btn-outline-indigo">
                                    <i class="fa-solid fa-user-plus me-1"></i>Déployer 3 Daimyōs IA
                                </button>
                                <button type="submit" formaction="?page=gamestudio&tab=general&subtab=world_config" 
                                        name="action" value="trigger_spontaneous_spawn" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-compass me-1"></i>Éclosion Spontanée 360°
                                </button>
                                <button type="submit" formaction="?page=gamestudio&tab=general&subtab=world_config" 
                                        name="action" value="run_bot_cycle" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-play me-1"></i>Exécuter un Cycle de Décision IA
                                </button>
                            </div>
                        </div>

                        <!-- Directives Stratégiques & Règles de Décision des PNJ -->
                        <div class="border rounded p-3 bg-light">
                            <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-sitemap text-indigo me-1.5"></i>Directives Stratégiques &amp; Règles de Décision des PNJ</h5>
                            <div class="row g-2 small">
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>1. Décision Économique &amp; Parcelles Rurales</strong><br><span class="text-muted">Équilibrage Bois/Pierre/Riz selon les besoins du fief.</span></div></div>
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>2. Décision d'Urbanisme &amp; Fortifications</strong><br><span class="text-muted">Élévation du Tenshu, Dépôt, Silo et Murailles féodales.</span></div></div>
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>3. Conscription Martiale selon la Faction</strong><br><span class="text-muted">Recrutement Ashigaru, Samouraïs et Archers selon doctrine.</span></div></div>
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>4. Règles d'Engagement &amp; Raids Féodaux</strong><br><span class="text-muted">Ciblage de cibles rentables, exclusion stricte des débutants sous bouclier.</span></div></div>
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>5. Reconnaissance Shinobi &amp; Infiltration</strong><br><span class="text-muted">Espionnage préalable avant assaut massif.</span></div></div>
                                <div class="col-md-4"><div class="p-2 border rounded bg-white"><strong>6. Colonisation &amp; Expansion Provinciale</strong><br><span class="text-muted">Fondation autonome jusqu'au plafond par clan.</span></div></div>
                            </div>
                            <div class="mt-2 text-muted small font-monospace">
                                Console simulateur : <code>executeBotCycle() &bull; JSON Output</code>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════════ -->
                <!-- ONGLET 6 : RÉGÉNÉRATION & RÉINITIALISATION DU MONDE           -->
                <!-- ═══════════════════════════════════════════════════════════════ -->
                <div class="tab-pane fade" id="section-world-regeneration-config" role="tabpanel">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h4 class="text-danger fw-bold m-0 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-triangle-exclamation text-danger"></i>6. Régénération &amp; Réinitialisation du Monde Féodal
                                </h4>
                                <div class="text-muted small mt-0.5">
                                    Remet à zéro l'archipel, les coordonnées, les bâtiments et les armées. Nécessite une confirmation stricte par mot-clé RESET.
                                </div>
                            </div>
                            <span class="badge bg-danger-lt font-monospace fs-5 px-3 py-1.5" id="badge-preserve-status-header">
                                <?= !empty($preserveHumanUsers) ? 'Joueurs Humains Conservés' : 'Purge Intégrale' ?>
                            </span>
                        </div>

                        <!-- Paramètre de Conservation des Joueurs Humains -->
                        <div class="card border shadow-none mb-4" id="cardPreserveHumanUsers">
                            <div class="card-body p-3">
                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-between">
                                    <div>
                                        <label class="form-check-label fw-bold text-dark fs-5 cursor-pointer" for="switchPreserveHumanUsers">
                                            Conserver les Joueurs Humains
                                        </label>
                                        <div class="text-muted small mt-0.5">
                                            Conserver les comptes des joueurs déjà présents dans le monde après la régénération du monde. Leurs anciens fiefs sont effacés et ils recevront un nouveau point de départ.
                                        </div>
                                    </div>
                                    <input class="form-check-input fs-2 cursor-pointer" type="checkbox" id="switchPreserveHumanUsers" 
                                           name="preserve_human_users" value="1" <?= !empty($preserveHumanUsers) ? 'checked' : '' ?> onchange="updatePreserveHumanUsersUI(this.checked)">
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-primary fs-5 py-1.5 px-3 font-monospace" id="badge-preserve-state">
                                        <i class="fa-solid fa-shield-halved me-1"></i><span id="badge-preserve-text">Comptes Joueurs Protégés</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Zone de Danger : Déclencheur du Reset -->
                        <div class="p-3 border border-danger rounded bg-danger-lt">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <h5 class="fw-bold text-danger m-0 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-skull-crossbones"></i>Zone Critique : Réinitialiser Tout l'Archipel
                                    </h5>
                                    <div class="text-muted small mt-1">
                                        Cette opération irréversible supprime toutes les villes et batailles en cours, et régénère les 12 châteaux authentiques.
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger px-4 shadow-sm" onclick="openStudioRegenerateModal()">
                                    <i class="fa-solid fa-triangle-exclamation me-1.5"></i>Ouvrir l'Assistant de Réinitialisation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pied de Carte Commun : Sauvegarde Globale Unique -->
            <div class="card-footer bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4 border-top">
                <div class="text-muted small d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-primary"></i>
                    <span>Toutes les modifications apportées aux onglets 1 à 6 sont enregistrées <strong>simultanément</strong>.</span>
                </div>
                <button type="submit" class="btn btn-primary px-4 shadow-sm d-flex align-items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Enregistrer Tous les Paramètres</span>
                </button>
            </div>
        </div>
    </form>

    <!-- ── FORMULAIRES CACHÉS D'ACTIONS PONCTUELLES (OASIS) ── -->
    <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formRepopulateOases" class="d-none">
        <input type="hidden" name="action" value="repopulate_world_oases">
        <input type="hidden" name="oasis_density_percent" id="hiddenRepopDensity" value="<?= number_format($oasisDensityPercent, 1, '.', '') ?>">
        <input type="hidden" name="clear_unoccupied" value="1">
    </form>
    <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formRespawnFauna" class="d-none">
        <input type="hidden" name="action" value="respawn_wild_fauna">
    </form>

    <!-- ── MODALE SÉCURISÉE DE RÉGÉNÉRATION DU MONDE (RESET) ── -->
    <div class="modal fade" id="modalStudioRegenerateWorld" tabindex="-1" aria-labelledby="modalRegenTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-danger">
                <div class="modal-header bg-danger text-white py-3">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="modalRegenTitle">
                        <i class="fa-solid fa-skull-crossbones fs-3"></i>
                        <span>Confirmation Obligatoire : Réinitialisation Complète du Monde</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger mb-3">
                        <div class="d-flex align-items-start">
                            <i class="fa-solid fa-triangle-exclamation fs-2 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Attention : Opération Irréversible !</h6>
                                <p class="mb-1" id="modalRegenPreserveDescription">
                                    <?= !empty($preserveHumanUsers) ? 'Les comptes des joueurs humains seront <strong>conservés</strong> avec remise à zéro de leurs fiefs.' : '⚠️ TOUS les comptes seront supprimés.' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formExecuteRegenerateWorld">
                        <input type="hidden" name="action" value="regenerate_world_action">
                        <input type="hidden" name="preserve_human_users" id="hiddenPreserveHumanUsers" value="<?= !empty($preserveHumanUsers) ? '1' : '0' ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Châteaux Neutres Authentiques à Générer :</label>
                                <input type="number" class="form-control form-control-sm font-monospace text-center" 
                                       id="inputNeutralPlanets" name="neutral_planets" min="5" max="50" value="12">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark">Peuplement Automatique :</label>
                                <div class="form-check form-switch pt-1">
                                    <input class="form-check-input" type="checkbox" id="switchDeployBots" name="deploy_bots" value="1" checked>
                                    <label class="form-check-label small" for="switchDeployBots">Déployer les Clans IA rivaux</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">Mot de Passe Super-Admin de Sécurité :</label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control font-monospace" id="inputAdminPassword" name="admin_password" value="Gabriel125#">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleAdminPasswordVisibility()">
                                    <i class="fa-solid fa-eye" id="iconEyeAdminPass"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-3 border rounded bg-light mb-3">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                                <span>Saisie du mot-clé de sécurité :</span>
                                <span id="labelRegenValidationStatus" class="font-monospace small"></span>
                            </label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white font-monospace fw-bold text-danger">RESET</span>
                                <input type="text" class="form-control font-monospace fw-bold text-uppercase" 
                                       id="inputRegenConfirmKeyword" name="confirm_keyword" placeholder="Tapez RESET pour déverrouiller" 
                                       autocomplete="off" oninput="onRegenKeywordChange(this)">
                                <button type="button" class="btn btn-outline-secondary" id="btnAutoFillRegenKeyword" onclick="autoFillRegenKeyword()">
                                    Remplir 1-Clic
                                </button>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="checkAcknowledgeRegen" onchange="onRegenCheckboxChange(this)">
                                <label class="form-check-label small text-muted cursor-pointer" for="checkAcknowledgeRegen">
                                    Je certifie être administrateur et autorise la régénération intégrale de l'archipel féodal.
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-danger px-4" id="btnConfirmRegenerateWorld" disabled>
                                <i class="fa-solid fa-skull-crossbones me-1.5"></i>Lancer la Régénération
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SCRIPT JS DE SYNCHRONISATION & CALCULS EN TEMPS RÉEL                -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Synchronisation de l'onglet actif avec le champ hidden
    const tabLinks = document.querySelectorAll('#worldConfigNavTabs a[data-bs-toggle="tab"]');
    const hiddenTabInput = document.getElementById('active_config_tab_input');
    tabLinks.forEach(function(link) {
        link.addEventListener('shown.bs.tab', function(e) {
            const targetId = (e.target.getAttribute('href') || '').replace('#', '');
            if (hiddenTabInput) hiddenTabInput.value = targetId;
        });
    });

    // Restaurer l'onglet depuis le hash d'URL si présent
    if (window.location.hash) {
        const hashTarget = document.querySelector('#worldConfigNavTabs a[href="' + window.location.hash + '"]');
        if (hashTarget && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(hashTarget).show();
            if (hiddenTabInput) hiddenTabInput.value = window.location.hash.replace('#', '');
        }
    }

    // ── Item 1 : Dimension du Monde ──
    const slider = document.getElementById('worldRadiusSlider');
    const input = document.getElementById('worldRadiusInput');
    const badgePreview = document.getElementById('badge-total-tiles-preview');
    const labelRange = document.getElementById('label-coord-range');
    const kpiTotal = document.getElementById('kpi-total-tiles');
    const kpiGridDim = document.getElementById('kpi-grid-dim');
    const kpiPerimeter = document.getElementById('kpi-perimeter');
    const kpiSaturation = document.getElementById('kpi-saturation');
    const kpiOccBase = document.getElementById('kpi-total-occupied-base');
    const boxWarning = document.getElementById('boxRadiusSecurityWarning');
    const warnRadiusSpan = document.getElementById('warnSelectedRadius');

    // ── Item 2 : Écosystème des Oasis ──
    const oasisSlider = document.getElementById('oasisDensitySlider');
    const oasisInput = document.getElementById('oasisDensityInput');
    const labelTargetOases = document.getElementById('label-target-oases-count');
    const hiddenRepopDensity = document.getElementById('hiddenRepopDensity');

    let currentCalculatedTotalTiles = <?= (int)$totalTiles ?>;
    const totalOccupied = <?= (int)$totalOccupied ?>;
    const minRequiredRadius = <?= (int)$minRequiredRadius ?>;

    function formatNumberFr(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function updateOasisCalculations(density) {
        density = parseFloat(density);
        if (isNaN(density) || density <= 0) density = 2.0;
        const targetCount = Math.round(currentCalculatedTotalTiles * (density / 100.0));
        if (labelTargetOases) labelTargetOases.innerText = formatNumberFr(targetCount);
        if (hiddenRepopDensity) hiddenRepopDensity.value = density.toFixed(1);
    }

    function updateWorldCalculations(radius) {
        radius = parseInt(radius, 10);
        if (isNaN(radius) || radius < 1) radius = 10;

        const dim = (radius * 2) + 1;
        const total = dim * dim;
        currentCalculatedTotalTiles = total;
        const perimeter = radius * 8;
        const saturation = total > 0 ? ((totalOccupied / total) * 100).toFixed(2) : 0;

        if (badgePreview) badgePreview.innerText = formatNumberFr(total) + " Tuiles";
        if (labelRange) labelRange.innerText = "[−" + radius + " : +" + radius + "]";
        if (kpiTotal) kpiTotal.innerText = formatNumberFr(total);
        if (kpiGridDim) kpiGridDim.innerText = dim + " × " + dim;
        if (kpiPerimeter) kpiPerimeter.innerText = formatNumberFr(perimeter);
        if (kpiSaturation) kpiSaturation.innerText = saturation + "%";
        if (kpiOccBase) kpiOccBase.innerText = formatNumberFr(totalOccupied);

        if (boxWarning && warnRadiusSpan) {
            if (radius < minRequiredRadius) {
                warnRadiusSpan.innerText = radius;
                boxWarning.classList.remove('d-none');
            } else {
                boxWarning.classList.add('d-none');
            }
        }

        if (oasisInput) {
            updateOasisCalculations(oasisInput.value);
        }
    }

    if (slider && input) {
        slider.addEventListener('input', function() {
            input.value = this.value;
            updateWorldCalculations(this.value);
        });
        input.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (!isNaN(val)) {
                if (val >= slider.min && val <= slider.max) slider.value = val;
                updateWorldCalculations(val);
            }
        });
    }

    if (oasisSlider && oasisInput) {
        oasisSlider.addEventListener('input', function() {
            oasisInput.value = this.value;
            updateOasisCalculations(this.value);
        });
        oasisInput.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if (!isNaN(val)) {
                if (val >= parseFloat(oasisSlider.min) && val <= parseFloat(oasisSlider.max)) oasisSlider.value = val;
                updateOasisCalculations(val);
            }
        });
    }

    window.applyWorldPreset = function(presetRadius) {
        if (slider) slider.value = presetRadius;
        if (input) input.value = presetRadius;
        updateWorldCalculations(presetRadius);
        document.querySelectorAll('.preset-btn').forEach(function(btn) {
            const r = parseInt(btn.getAttribute('data-radius'), 10);
            if (r === presetRadius) {
                btn.classList.add('btn-primary', 'text-white', 'shadow-xs');
                btn.classList.remove('btn-outline-secondary');
            } else {
                btn.classList.remove('btn-primary', 'text-white', 'shadow-xs');
                btn.classList.add('btn-outline-secondary');
            }
        });
    };

    window.confirmRepopulateOases = function() {
        const density = oasisInput ? parseFloat(oasisInput.value) : 2.0;
        const target = labelTargetOases ? labelTargetOases.innerText : '';
        const msg = `Confirmez-vous le rééquilibrage du réseau d'oasis avec une densité de ${density}% (~${target} oasis au total) ?`;
        if (confirm(msg)) {
            document.getElementById('formRepopulateOases').submit();
        }
    };

    window.filterOasesRows = function() {
        const query = (document.getElementById('filterOasisTable')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.oasis-data-row');
        rows.forEach(function(r) {
            const name = (r.querySelector('.oasis-name-col')?.innerText || '').toLowerCase();
            const coords = (r.querySelector('.oasis-coords-col')?.innerText || '').toLowerCase();
            r.style.display = (!query || name.includes(query) || coords.includes(query)) ? '' : 'none';
        });
    };

    // ── Item 3 : Vitesses & Équilibrage ──
    const sGame = document.getElementById('sliderGameSpeed');
    const iGame = document.getElementById('inputGameSpeed');
    const bGame = document.getElementById('badge-val-game-speed');
    const hGame = document.getElementById('badge-header-game-speed');

    const sRes = document.getElementById('sliderResourceSpeed');
    const iRes = document.getElementById('inputResourceSpeed');
    const bRes = document.getElementById('badge-val-resource-speed');
    const hRes = document.getElementById('badge-header-resource-speed');

    const sFleet = document.getElementById('sliderFleetSpeed');
    const iFleet = document.getElementById('inputFleetSpeed');
    const bFleet = document.getElementById('badge-val-fleet-speed');
    const hFleet = document.getElementById('badge-header-fleet-speed');

    const simBuild = document.getElementById('sim-build-time');
    const simMarch = document.getElementById('sim-march-time');
    const simProd = document.getElementById('sim-resource-prod');

    function formatDurationFr(seconds) {
        seconds = Math.round(seconds);
        if (seconds < 60) return seconds + "s";
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        if (m < 60) return s > 0 ? (m + "m " + (s < 10 ? "0" : "") + s + "s") : (m + "m 00s");
        const h = Math.floor(m / 60);
        const remM = m % 60;
        return h + "h " + (remM < 10 ? "0" : "") + remM + "m";
    }

    function updateSpeedSimulator() {
        const gSpeed = parseInt(iGame ? iGame.value : 5, 10) || 1;
        const rSpeed = parseInt(iRes ? iRes.value : 5, 10) || 1;
        const fSpeed = parseInt(iFleet ? iFleet.value : 5, 10) || 1;

        if (simBuild) simBuild.innerText = formatDurationFr(Math.max(1, Math.round(3600 / gSpeed)));
        if (simMarch) simMarch.innerText = formatDurationFr(Math.max(1, Math.round(1800 / fSpeed)));
        if (simProd) simProd.innerText = formatNumberFr(250 * rSpeed) + " koku/h";
    }

    function syncSpeedControl(slider, input, badge, headerBadge, suffixTitle) {
        if (!slider || !input) return;
        slider.addEventListener('input', function() {
            input.value = this.value;
            if (badge) badge.innerText = 'x' + this.value;
            if (headerBadge) headerBadge.innerText = 'x' + this.value + ' ' + suffixTitle;
            updateSpeedSimulator();
        });
        input.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (!isNaN(val)) {
                if (val >= parseInt(slider.min, 10) && val <= parseInt(slider.max, 10)) slider.value = val;
                if (badge) badge.innerText = 'x' + val;
                if (headerBadge) headerBadge.innerText = 'x' + val + ' ' + suffixTitle;
                updateSpeedSimulator();
            }
        });
    }

    syncSpeedControl(sGame, iGame, bGame, hGame, 'Jeu');
    syncSpeedControl(sRes, iRes, bRes, hRes, 'Prod');
    syncSpeedControl(sFleet, iFleet, bFleet, hFleet, 'Marche');

    function syncSimpleControl(sliderId, inputId, badgeId, suffix) {
        const s = document.getElementById(sliderId);
        const i = document.getElementById(inputId);
        const b = document.getElementById(badgeId);
        if (!s || !i) return;
        s.addEventListener('input', function() {
            i.value = this.value;
            if (b) b.innerText = this.value + (suffix ? ' ' + suffix : '');
        });
        i.addEventListener('input', function() {
            let val = parseFloat(this.value);
            if (!isNaN(val)) {
                if (val >= parseFloat(s.min) && val <= parseFloat(s.max)) s.value = val;
                if (b) b.innerText = this.value + (suffix ? ' ' + suffix : '');
            }
        });
    }

    syncSimpleControl('sliderCageDrop', 'inputCageDrop', 'badge-val-cage-drop', '%');
    syncSimpleControl('sliderHeroXp', 'inputHeroXp', 'badge-val-hero-xp', '%');
    syncSimpleControl('sliderFamineRate', 'inputFamineRate', 'badge-val-famine-rate', '%');
    syncSimpleControl('sliderFamineFlour', 'inputFamineFlour', 'badge-val-famine-flour', '');

    window.applyDevSpeedPreset = function(g, r, f, btnElem) {
        if (sGame) sGame.value = g;
        if (iGame) iGame.value = g;
        if (bGame) bGame.innerText = 'x' + g;
        if (hGame) hGame.innerText = 'x' + g + ' Jeu';

        if (sRes) sRes.value = r;
        if (iRes) iRes.value = r;
        if (bRes) bRes.innerText = 'x' + r;
        if (hRes) hRes.innerText = 'x' + r + ' Prod';

        if (sFleet) sFleet.value = f;
        if (iFleet) iFleet.value = f;
        if (bFleet) bFleet.innerText = 'x' + f;
        if (hFleet) hFleet.innerText = 'x' + f + ' Marche';

        updateSpeedSimulator();
        document.querySelectorAll('.speed-preset-btn').forEach(function(b) {
            b.classList.remove('btn-danger', 'text-white', 'shadow-xs');
            b.classList.add('btn-outline-secondary');
        });
        if (btnElem) {
            btnElem.classList.remove('btn-outline-secondary');
            btnElem.classList.add('btn-danger', 'text-white', 'shadow-xs');
        }
    };

    window.updateFamineSwitchBadges = function(checked) {
        const switchState = document.getElementById('badge-famine-switch-state');
        if (switchState) {
            switchState.className = checked ? 'badge bg-danger-lt font-monospace' : 'badge bg-secondary-lt font-monospace';
            switchState.innerText = checked ? 'En service' : 'Inactif';
        }
    };

    // ── Item 4 : Dotation Initiale ──
    const sWood = document.getElementById('sliderStarterWood');
    const iWood = document.getElementById('inputStarterWood');
    const bWood = document.getElementById('badge-val-starter-wood');

    const sStone = document.getElementById('sliderStarterStone');
    const iStone = document.getElementById('inputStarterStone');
    const bStone = document.getElementById('badge-val-starter-stone');

    const sRice = document.getElementById('sliderStarterRice');
    const iRice = document.getElementById('inputStarterRice');
    const bRice = document.getElementById('badge-val-starter-rice');

    const sFish = document.getElementById('sliderStarterFish');
    const iFish = document.getElementById('inputStarterFish');
    const bFish = document.getElementById('badge-val-starter-fish');

    const sGold = document.getElementById('sliderStarterGold');
    const iGold = document.getElementById('inputStarterGold');
    const bGold = document.getElementById('badge-val-starter-gold');

    const sProt = document.getElementById('sliderStarterProtection');
    const iProt = document.getElementById('inputStarterProtection');
    const bProt = document.getElementById('badge-val-starter-protection');
    const hProt = document.getElementById('badge-header-starter-protection');
    const hStarterRes = document.getElementById('badge-header-starter-res');
    const hStarterGold = document.getElementById('badge-header-starter-gold');

    const simTenshu = document.getElementById('sim-tenshu-level');
    const simPlots = document.getElementById('sim-plots-count');
    const simProtDays = document.getElementById('sim-protection-days');
    const simProtDate = document.getElementById('sim-protection-date');
    const protStatusDesc = document.getElementById('label-protection-status-desc');

    function updateStarterSimulator() {
        const w = parseInt(iWood ? iWood.value : 1000, 10) || 0;
        const s = parseInt(iStone ? iStone.value : 1000, 10) || 0;
        const r = parseInt(iRice ? iRice.value : 1000, 10) || 0;
        const pDays = parseInt(iProt ? iProt.value : 7, 10) || 0;

        if (hStarterRes) hStarterRes.innerText = formatNumberFr(w) + " / " + formatNumberFr(s) + " / " + formatNumberFr(r) + " Matériaux";
        if (hStarterGold && iGold) hStarterGold.innerHTML = '<i class="fa-solid fa-coins me-1"></i>' + formatNumberFr(iGold.value) + " Ryō";
        if (hProt) hProt.innerHTML = '<i class="fa-solid fa-shield-halved me-1"></i>' + pDays + " jours";

        let tenshuLvl = "Niveau 0";
        if (w >= 3500 && s >= 2800 && r >= 2300) tenshuLvl = "Niveau 5+";
        else if (w >= 2000 && s >= 1600 && r >= 1300) tenshuLvl = "Niveau 4";
        else if (w >= 1100 && s >= 880 && r >= 720) tenshuLvl = "Niveau 3";
        else if (w >= 600 && s >= 480 && r >= 400) tenshuLvl = "Niveau 2";
        else if (w >= 120 && s >= 100 && r >= 80) tenshuLvl = "Niveau 1";
        if (simTenshu) simTenshu.innerText = tenshuLvl;

        const totalMat = w + s + r;
        if (simPlots) simPlots.innerText = "~" + Math.min(18, Math.floor(totalMat / 250)) + " parcelles";
        if (simProtDays) simProtDays.innerText = pDays > 0 ? (pDays + " jours") : "Désactivée (0j)";
        if (simProtDate) simProtDate.innerText = pDays > 0 ? "Bouclier inviolable pendant " + pDays + " jours" : "Aucun bouclier (vulnérable dès le départ)";
        if (protStatusDesc) {
            protStatusDesc.innerHTML = pDays > 0
                ? '<i class="fa-solid fa-circle-check text-success me-1"></i>Trêve sacrée active : <strong>' + pDays + ' jours</strong> de protection inviolable contre les assauts et raids ennemis.'
                : '<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Bouclier désactivé : les nouveaux seigneurs peuvent être attaqués dès leur première minute de jeu.';
        }
    }

    function syncStarterControl(slider, input, badge, suffix) {
        if (!slider || !input) return;
        slider.addEventListener('input', function() {
            input.value = this.value;
            if (badge) badge.innerText = formatNumberFr(this.value) + (suffix ? ' ' + suffix : '');
            updateStarterSimulator();
        });
        input.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (!isNaN(val)) {
                if (val >= parseInt(slider.min, 10) && val <= parseInt(slider.max, 10)) slider.value = val;
                if (badge) badge.innerText = formatNumberFr(val) + (suffix ? ' ' + suffix : '');
                updateStarterSimulator();
            }
        });
    }

    syncStarterControl(sWood, iWood, bWood, '');
    syncStarterControl(sStone, iStone, bStone, '');
    syncStarterControl(sRice, iRice, bRice, '');
    syncStarterControl(sFish, iFish, bFish, '');
    syncStarterControl(sGold, iGold, bGold, 'Ryō');
    syncStarterControl(sProt, iProt, bProt, 'jours');

    window.applyStarterPreset = function(w, s, r, f, gold, prot, btnElem) {
        if (sWood) { sWood.value = w; iWood.value = w; bWood.innerText = formatNumberFr(w); }
        if (sStone) { sStone.value = s; iStone.value = s; bStone.innerText = formatNumberFr(s); }
        if (sRice) { sRice.value = r; iRice.value = r; bRice.innerText = formatNumberFr(r); }
        if (sFish) { sFish.value = f; iFish.value = f; bFish.innerText = formatNumberFr(f); }
        if (sGold) { sGold.value = gold; iGold.value = gold; bGold.innerText = formatNumberFr(gold) + ' Ryō'; }
        if (sProt) { sProt.value = prot; iProt.value = prot; bProt.innerText = prot + ' jours'; }

        updateStarterSimulator();
        document.querySelectorAll('.starter-preset-btn').forEach(function(b) {
            b.classList.remove('btn-purple', 'text-white', 'shadow-xs');
            b.classList.add('btn-outline-secondary');
        });
        if (btnElem) {
            btnElem.classList.remove('btn-outline-secondary');
            btnElem.classList.add('btn-purple', 'text-white', 'shadow-xs');
        }
    };

    // ── Item 5 : Clans IA ──
    window.updateBotsEnabledUI = function(isChecked) {
        const badgeHeader = document.getElementById('badge-bots-status-header');
        const labelHeaderText = document.getElementById('label-bots-status-text');
        if (badgeHeader && labelHeaderText) {
            badgeHeader.className = isChecked ? 'badge bg-indigo-lt font-monospace fs-5 px-3 py-1.5' : 'badge bg-secondary-lt font-monospace fs-5 px-3 py-1.5';
            badgeHeader.innerHTML = '<i class="fa-solid ' + (isChecked ? 'fa-microchip' : 'fa-moon') + ' me-1"></i><span id="label-bots-status-text">' + (isChecked ? 'Cycles IA Actifs' : 'IA en Veille') + '</span>';
        }
    };

    window.updateAggressivenessBadge = function(val) {
        const badge = document.getElementById('badge-val-bot-aggressiveness');
        if (badge) {
            if (val === 'passive' || val === 'peaceful') {
                badge.innerText = 'PASSIF';
                badge.className = 'badge bg-success-lt font-monospace text-uppercase';
            } else if (val === 'moderate') {
                badge.innerText = 'MODÉRÉ';
                badge.className = 'badge bg-warning-lt font-monospace text-uppercase';
            } else {
                badge.innerText = 'BELLIQUEUX';
                badge.className = 'badge bg-danger-lt font-monospace text-uppercase';
            }
        }
    };

    window.applyWorldBotsPreset = function(preset, btn) {
        document.querySelectorAll('.bot-world-preset-btn').forEach(b => {
            b.classList.remove('btn-indigo', 'btn-danger', 'btn-success', 'text-white', 'shadow-xs');
            b.classList.add('btn-outline-secondary');
        });
        if (btn) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-indigo', 'text-white', 'shadow-xs');
        }

        const swBots = document.getElementById('switchBotsEnabled');
        const swColonize = document.getElementById('switchBotColonize');
        const swSpawn = document.getElementById('switchBotSpawn');
        const slPlanets = document.getElementById('sliderBotMaxPlanets');
        const inPlanets = document.getElementById('inputBotMaxPlanets');
        const slInterval = document.getElementById('sliderBotSpawnInterval');
        const inInterval = document.getElementById('inputBotSpawnInterval');
        const slMax = document.getElementById('sliderBotSpawnMax');
        const inMax = document.getElementById('inputBotSpawnMax');

        const radPassive = document.querySelector('input[name="bot_aggressiveness"][value="passive"]');
        const radModerate = document.querySelector('input[name="bot_aggressiveness"][value="moderate"]');
        const radAggressive = document.querySelector('input[name="bot_aggressiveness"][value="aggressive"]');

        if (preset === 'peaceful') {
            if (swBots) swBots.checked = true;
            if (swColonize) swColonize.checked = false;
            if (swSpawn) swSpawn.checked = true;
            if (slPlanets) slPlanets.value = 1; if (inPlanets) inPlanets.value = 1;
            if (slInterval) slInterval.value = 60; if (inInterval) inInterval.value = 60;
            if (slMax) slMax.value = 20; if (inMax) inMax.value = 20;
            if (radPassive) radPassive.checked = true;
            updateAggressivenessBadge('passive');
        } else if (preset === 'moderate') {
            if (swBots) swBots.checked = true;
            if (swColonize) swColonize.checked = true;
            if (swSpawn) swSpawn.checked = true;
            if (slPlanets) slPlanets.value = 3; if (inPlanets) inPlanets.value = 3;
            if (slInterval) slInterval.value = 15; if (inInterval) inInterval.value = 15;
            if (slMax) slMax.value = 50; if (inMax) inMax.value = 50;
            if (radModerate) radModerate.checked = true;
            updateAggressivenessBadge('moderate');
        } else if (preset === 'aggressive') {
            if (swBots) swBots.checked = true;
            if (swColonize) swColonize.checked = true;
            if (swSpawn) swSpawn.checked = true;
            if (slPlanets) slPlanets.value = 5; if (inPlanets) inPlanets.value = 5;
            if (slInterval) slInterval.value = 5; if (inInterval) inInterval.value = 5;
            if (slMax) slMax.value = 100; if (inMax) inMax.value = 100;
            if (radAggressive) radAggressive.checked = true;
            updateAggressivenessBadge('aggressive');
        }

        if (swBots) updateBotsEnabledUI(swBots.checked);
        const bPlanets = document.getElementById('badge-val-bot-max-planets');
        if (bPlanets && inPlanets) bPlanets.innerText = inPlanets.value + ' fiefs / clan';
        const bInterval = document.getElementById('badge-val-bot-spawn-interval');
        if (bInterval && inInterval) bInterval.innerText = inInterval.value + ' min';
        const bMax = document.getElementById('badge-val-bot-spawn-max');
        if (bMax && inMax) bMax.innerText = inMax.value + ' fiefs max';
    };

    syncSimpleControl('sliderBotMaxPlanets', 'inputBotMaxPlanets', 'badge-val-bot-max-planets', 'fiefs / clan');
    syncSimpleControl('sliderBotSpawnInterval', 'inputBotSpawnInterval', 'badge-val-bot-spawn-interval', 'min');
    syncSimpleControl('sliderBotSpawnMax', 'inputBotSpawnMax', 'badge-val-bot-spawn-max', 'fiefs max');

    // ── Item 6 : Régénération & Conservation des Joueurs ──
    window.updatePreserveHumanUsersUI = function(isChecked) {
        const badgeState = document.getElementById('badge-preserve-state');
        const badgeHeader = document.getElementById('badge-preserve-status-header');
        const hiddenPreserve = document.getElementById('hiddenPreserveHumanUsers');
        const modalDesc = document.getElementById('modalRegenPreserveDescription');

        if (hiddenPreserve) hiddenPreserve.value = isChecked ? '1' : '0';
        if (isChecked) {
            if (badgeState) {
                badgeState.className = 'badge bg-primary fs-5 py-1.5 px-3 font-monospace';
                badgeState.innerHTML = '<i class="fa-solid fa-shield-halved me-1"></i><span id="badge-preserve-text">Comptes Joueurs Protégés</span>';
            }
            if (badgeHeader) {
                badgeHeader.innerText = 'Joueurs Humains Conservés';
                badgeHeader.className = 'badge bg-primary-lt font-monospace fs-5 px-3 py-1.5';
            }
            if (modalDesc) {
                modalDesc.innerHTML = '<strong>Option active :</strong> Les comptes des joueurs humains déjà présents dans le monde seront <strong>conservés</strong>. Leurs anciens fiefs sont remis à zéro et ils recevront un nouveau départ avec Héros et dotation.';
            }
        } else {
            if (badgeState) {
                badgeState.className = 'badge bg-danger fs-5 py-1.5 px-3 font-monospace';
                badgeState.innerHTML = '<i class="fa-solid fa-skull-crossbones me-1"></i><span id="badge-preserve-text">Purge Complète Prévue</span>';
            }
            if (badgeHeader) {
                badgeHeader.innerText = 'Purge Intégrale';
                badgeHeader.className = 'badge bg-danger-lt font-monospace fs-5 px-3 py-1.5';
            }
            if (modalDesc) {
                modalDesc.innerHTML = '<strong>⚠️ ATTENTION CRITIQUE :</strong> L\'option de conservation est désactivée. <strong>TOUS les comptes de joueurs humains seront IRRÉVOCABLEMENT SUPPRIMÉS</strong> !';
            }
        }
    };

    window.openStudioRegenerateModal = function() {
        const sw = document.getElementById('switchPreserveHumanUsers');
        if (sw) updatePreserveHumanUsersUI(sw.checked);

        const keyInp = document.getElementById('inputRegenConfirmKeyword');
        if (keyInp) { keyInp.value = ''; keyInp.classList.remove('is-valid', 'is-invalid'); }
        const chkAck = document.getElementById('checkAcknowledgeRegen');
        if (chkAck) chkAck.checked = false;
        const btnSubmit = document.getElementById('btnConfirmRegenerateWorld');
        if (btnSubmit) btnSubmit.disabled = true;

        const statusBadge = document.getElementById('labelRegenValidationStatus');
        if (statusBadge) statusBadge.innerHTML = '<span class="text-muted"><i class="fa-solid fa-lock me-1"></i>En attente</span>';

        const modalElem = document.getElementById('modalStudioRegenerateWorld');
        if (modalElem) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalElem).show();
            } else {
                modalElem.classList.add('show');
                modalElem.style.display = 'block';
            }
        }
        setTimeout(() => { if (keyInp) keyInp.focus(); }, 350);
    };

    window.toggleAdminPasswordVisibility = function() {
        const passInp = document.getElementById('inputAdminPassword');
        const icon = document.getElementById('iconEyeAdminPass');
        if (passInp && icon) {
            const isPass = passInp.type === 'password';
            passInp.type = isPass ? 'text' : 'password';
            icon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    };

    window.checkRegenValidation = function() {
        const keyInp = document.getElementById('inputRegenConfirmKeyword');
        const chkAck = document.getElementById('checkAcknowledgeRegen');
        const btnSubmit = document.getElementById('btnConfirmRegenerateWorld');
        const statusBadge = document.getElementById('labelRegenValidationStatus');

        const rawVal = keyInp && keyInp.value ? keyInp.value : '';
        const isKeywordValid = (rawVal.trim().toUpperCase() === 'RESET');
        const isCheckboxChecked = chkAck ? chkAck.checked : false;
        const isValid = isKeywordValid || isCheckboxChecked;

        if (keyInp) {
            if (isKeywordValid) { keyInp.classList.add('is-valid'); keyInp.classList.remove('is-invalid'); }
            else if (rawVal.trim().length > 0) { keyInp.classList.add('is-invalid'); keyInp.classList.remove('is-valid'); }
            else { keyInp.classList.remove('is-valid', 'is-invalid'); }
        }

        if (btnSubmit) btnSubmit.disabled = !isValid;
        if (statusBadge) {
            if (isValid) statusBadge.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-lock-open me-1"></i>Déverrouillé</span>';
            else if (rawVal.trim().length > 0) statusBadge.innerHTML = '<span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>Saisissez "RESET"</span>';
            else statusBadge.innerHTML = '<span class="text-muted"><i class="fa-solid fa-lock me-1"></i>En attente</span>';
        }
    };

    window.onRegenKeywordChange = function(inp) { window.checkRegenValidation(); };
    window.onRegenCheckboxChange = function(chk) {
        if (chk && chk.checked) {
            const keyInp = document.getElementById('inputRegenConfirmKeyword');
            if (keyInp && keyInp.value.trim().toUpperCase() !== 'RESET') keyInp.value = 'RESET';
        }
        window.checkRegenValidation();
    };

    window.autoFillRegenKeyword = function() {
        const keyInp = document.getElementById('inputRegenConfirmKeyword');
        if (keyInp) keyInp.value = 'RESET';
        const chkAck = document.getElementById('checkAcknowledgeRegen');
        if (chkAck) chkAck.checked = true;
        window.checkRegenValidation();
        if (keyInp) keyInp.focus();
    };

    updateStarterSimulator();
    updateSpeedSimulator();
});
</script>
