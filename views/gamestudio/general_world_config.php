<?php
/**
 * Vue Partielle MVC : Configuration du Monde & Dimensionnement des Tuiles
 * Game Studio Général - OpenShogun
 * Fichier : views/gamestudio/general_world_config.php
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
        <div class="alert alert-<?= htmlspecialchars($generalFlash['type'] ?? 'info') ?> alert-dismissible shadow-sm mb-4" role="alert">
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

    <!-- En-tête de section -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 p-3 bg-white border rounded shadow-xs">
        <div>
            <h4 class="m-0 text-azure d-flex align-items-center gap-2 fw-bold" style="font-size:1.2rem;">
                <i class="fa-solid fa-earth-asia text-azure"></i>Configuration Spatiale &amp; Arpentage de l'Archipel
            </h4>
            <div class="text-secondary small mt-1">
                Ajustez les dimensions de la grille de coordonnées, calculez le nombre total de tuiles générées, supervisez l'écosystème des oasis et la répartition géographique du Japon féodal.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-azure text-white py-2 px-3 fw-bold font-monospace shadow-xs">
                <i class="fa-solid fa-shapes me-1"></i>Rayon Actuel : R = <?= (int)$worldRadius ?>
            </span>
            <span class="badge bg-dark text-white py-2 px-3 fw-bold font-monospace shadow-xs">
                <i class="fa-solid fa-border-all me-1"></i><?= number_format((int)$totalTiles, 0, ',', ' ') ?> Tuiles Totales
            </span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PREMIER ITEM MAJEUR : NOMBRE DE TUILES GÉNÉRÉES POUR LE MONDE      -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 border-azure shadow-sm overflow-hidden" id="section-world-tiles-config" style="border-width: 2px;">
        <div class="card-header bg-azure text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h3 class="card-title text-white m-0 d-flex align-items-center gap-2 fw-bold" style="font-size:1.25rem;">
                    <i class="fa-solid fa-shapes text-warning fs-2"></i>
                    <span>1. Dimension du Monde &amp; Nombre de Tuiles Générées</span>
                </h3>
                <div class="text-white-50 small mt-1">
                    Premier paramètre fondamental de l'univers : définit la frontière maximale de l'archipel féodal sur les axes cartésiens [-R ; +R].
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-azure fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-total-tiles-preview">
                    <?= number_format((int)$totalTiles, 0, ',', ' ') ?> Tuiles
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light bg-opacity-25">
            <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formWorldRadiusConfig">
                <input type="hidden" name="action" value="save_world_radius">

                <div class="row g-4 align-items-center mb-4">
                    <!-- Contrôle Principal : Slider et Input du Rayon -->
                    <div class="col-lg-7">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <i class="fa-solid fa-ruler-combined text-azure me-1"></i>Rayon Géographique de la Carte (R) :
                            </span>
                            <span class="text-secondary small font-monospace">
                                Coordonnées de <strong class="text-azure" id="label-coord-range">[-<?= (int)$worldRadius ?> : +<?= (int)$worldRadius ?>]</strong>
                            </span>
                        </label>
                        
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" 
                                   class="form-range flex-grow-1" 
                                   id="worldRadiusSlider" 
                                   name="world_radius_slider" 
                                   min="10" 
                                   max="120" 
                                   step="1" 
                                   value="<?= (int)$worldRadius ?>"
                                   style="cursor: pointer; height: 8px;">
                            
                            <div class="input-group" style="width: 130px;">
                                <span class="input-group-text bg-white fw-bold text-azure font-monospace">R =</span>
                                <input type="number" 
                                       class="form-control text-center fw-bold font-monospace fs-4" 
                                       id="worldRadiusInput" 
                                       name="world_radius" 
                                       min="10" 
                                       max="200" 
                                       value="<?= (int)$worldRadius ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between text-secondary small font-monospace mt-1 px-1">
                            <span>10 (Min)</span>
                            <span>35 (Standard)</span>
                            <span>50 (Grand)</span>
                            <span>75 (Épique)</span>
                            <span>100 (Méga)</span>
                            <span>120+</span>
                        </div>
                    </div>

                    <!-- Formule & Bouton de Sauvegarde -->
                    <div class="col-lg-5">
                        <div class="card card-body bg-white border p-3 shadow-xs">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-secondary small fw-bold">Formule Cartésienne :</span>
                                <span class="badge bg-light text-dark font-monospace">(2R + 1)²</span>
                            </div>
                            <div class="small text-secondary mb-3">
                                Pour un rayon <strong>R</strong>, la grille s'étend sur une largeur de <code>(2R + 1)</code> et une hauteur de <code>(2R + 1)</code> cases autour de Kyoto <code>[0 : 0]</code>.
                            </div>
                            <button type="submit" class="btn btn-azure btn-lg fw-bold w-100 shadow-sm" id="btnSaveWorldRadius">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer la Dimension du Monde
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── PRÉRÉGLAGES RAPIDES 1-CLIC ── -->
                <div class="mb-4">
                    <label class="form-label small text-uppercase text-secondary fw-bold mb-2">
                        <i class="fa-solid fa-bolt text-warning me-1"></i>Préréglages Immédiats Recommandés (1-Clic) :
                    </label>
                    <div class="row g-2">
                        <?php foreach ($presets as $preset): ?>
                            <?php $isCurrentPreset = ((int)$preset['radius'] === (int)$worldRadius); ?>
                            <div class="col-sm-6 col-md-4 col-xl">
                                <button type="button" 
                                        class="btn <?= $isCurrentPreset ? 'btn-azure shadow-sm' : 'btn-outline-secondary' ?> w-100 p-2 text-start preset-btn d-flex flex-column justify-content-between h-100"
                                        data-radius="<?= (int)$preset['radius'] ?>"
                                        onclick="applyWorldPreset(<?= (int)$preset['radius'] ?>)">
                                    <div class="d-flex justify-content-between align-items-center w-100 mb-1">
                                        <span class="fw-bold fs-5"><i class="fa-solid <?= $preset['icon'] ?> me-1"></i><?= htmlspecialchars($preset['name']) ?></span>
                                        <span class="badge <?= $preset['badge'] ?> font-monospace">R = <?= (int)$preset['radius'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end w-100 pt-1 border-top border-white-50">
                                        <span class="font-monospace fw-bold text-truncate"><?= number_format((int)$preset['total_tiles'], 0, ',', ' ') ?> tuiles</span>
                                        <span class="small text-muted font-monospace"><?= htmlspecialchars($preset['dimension']) ?></span>
                                    </div>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── 4 CARTES KPIS DYNAMIQUES DU NOMBRE DE TUILES (MISES À JOUR EN TEMPS RÉEL) ── -->
                <div class="row row-cards mb-3">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-sm border-start border-3 border-azure shadow-xs h-100 bg-white">
                            <div class="card-body p-3">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Nombre Total de Tuiles</div>
                                <div class="h1 m-0 text-azure fw-bold font-monospace" id="kpi-total-tiles">
                                    <?= number_format((int)$totalTiles, 0, ',', ' ') ?>
                                </div>
                                <div class="text-secondary small mt-2 pt-2 border-top">
                                    <i class="fa-solid fa-shapes text-azure me-1"></i><span id="kpi-total-tiles-sub">100% de la surface mondiale</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-sm border-start border-3 border-indigo shadow-xs h-100 bg-white">
                            <div class="card-body p-3">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Largeur × Hauteur</div>
                                <div class="h1 m-0 text-indigo fw-bold font-monospace" id="kpi-grid-dim">
                                    <?= (int)$gridDimension ?> × <?= (int)$gridDimension ?>
                                </div>
                                <div class="text-secondary small mt-2 pt-2 border-top">
                                    <i class="fa-solid fa-border-all text-indigo me-1"></i>Grille cartésienne symétrique
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-sm border-start border-3 border-teal shadow-xs h-100 bg-white">
                            <div class="card-body p-3">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Frontière &amp; Périmètre</div>
                                <div class="h1 m-0 text-teal fw-bold font-monospace" id="kpi-perimeter">
                                    <?= number_format((int)$boundaryPerimeter, 0, ',', ' ') ?>
                                </div>
                                <div class="text-secondary small mt-2 pt-2 border-top">
                                    <i class="fa-solid fa-shield text-teal me-1"></i>Tuiles de bordure (8R)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-sm border-start border-3 border-purple shadow-xs h-100 bg-white">
                            <div class="card-body p-3">
                                <div class="text-secondary small fw-bold text-uppercase mb-1">Taux d'Occupation Actuel</div>
                                <div class="h1 m-0 text-purple fw-bold font-monospace" id="kpi-saturation">
                                    <?= htmlspecialchars((string)$saturationRate) ?>%
                                </div>
                                <div class="text-secondary small mt-2 pt-2 border-top">
                                    <i class="fa-solid fa-chart-pie text-purple me-1"></i><?= number_format((int)$totalOccupied, 0, ',', ' ') ?> / <span id="kpi-total-occupied-base"><?= number_format((int)$totalTiles, 0, ',', ' ') ?></span> tuiles
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── ALERTE DE SÉCURITÉ EN TEMPS RÉEL (SI R < R_MIN) ── -->
                <div class="alert alert-danger d-none shadow-sm mb-0" id="boxRadiusSecurityWarning">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-triangle-exclamation text-danger fs-1 me-3"></i>
                        <div>
                            <strong>Attention critique au redimensionnement :</strong>
                            <div id="textRadiusSecurityWarning">
                                Le rayon choisi (<span id="warnSelectedRadius">-</span>) est inférieur au rayon minimal requis (<span id="warnMinRequiredRadius"><?= (int)$minRequiredRadius ?></span>).
                                Des fiefs ou des entités existantes se trouveront en dehors de la carte !
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($outOfBoundsEntities)): ?>
                    <div class="alert alert-warning shadow-sm mt-3 mb-0">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-circle-exclamation text-warning fs-2 me-2"></i>
                            <strong>Entités actuellement situées au-delà des frontières configurées (<?= count($outOfBoundsEntities) ?>) :</strong>
                        </div>
                        <div class="small">
                            <?php foreach (array_slice($outOfBoundsEntities, 0, 5) as $oob): ?>
                                <span class="badge bg-dark text-white font-monospace me-1 mb-1">
                                    <?= htmlspecialchars($oob['type']) ?> &bull; <?= htmlspecialchars($oob['name']) ?> <?= htmlspecialchars($oob['coords']) ?> (+<?= (int)$oob['excess'] ?>)
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($outOfBoundsEntities) > 5): ?>
                                <span class="text-muted fst-italic">... et <?= count($outOfBoundsEntities) - 5 ?> autres</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- DEUXIÈME ITEM MAJEUR : OUTILS DE CONFIGURATION DES OASIS            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 border-success shadow-sm overflow-hidden" id="section-world-oases-config" style="border-width: 2px;">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h3 class="card-title text-white m-0 d-flex align-items-center gap-2 fw-bold" style="font-size:1.25rem;">
                    <i class="fa-solid fa-leaf text-warning fs-2"></i>
                    <span>2. Configuration &amp; Écosystème des Oasis Naturelles</span>
                </h3>
                <div class="text-white-50 small mt-1">
                    Maîtrisez la densité de couverture (%), le cycle de réapparition automatique après capture et la garnison de faune sauvage (Sangliers, Loups, Ours).
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-success fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-oases-total-preview">
                    <?= number_format((int)$oasesCount) ?> Oasis Actives
                </span>
                <span class="badge bg-success-subtle text-white font-monospace">
                    <?= htmlspecialchars((string)$oasisDensityPercent) ?>% de couverture
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light bg-opacity-25">
            <!-- Formulaire des Paramètres d'Oasis -->
            <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formOasisSettings" class="mb-4">
                <input type="hidden" name="action" value="save_oasis_settings">

                <div class="row g-4 align-items-center">
                    <!-- Slider de Densité Cible (%) -->
                    <div class="col-lg-6">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <i class="fa-solid fa-percent text-success me-1"></i>Densité de Couverture Cible (%) :
                            </span>
                            <span class="text-secondary small font-monospace">
                                Objectif Calculé : <strong class="text-success" id="label-target-oases-count"><?= (int)$targetOasesCount ?></strong> oasis cibles
                            </span>
                        </label>

                        <div class="d-flex align-items-center gap-3">
                            <input type="range" 
                                   class="form-range flex-grow-1" 
                                   id="oasisDensitySlider" 
                                   name="oasis_density_slider" 
                                   min="0.5" 
                                   max="10.0" 
                                   step="0.1" 
                                   value="<?= (float)$oasisDensityPercent ?>"
                                   style="cursor: pointer; height: 8px;">

                            <div class="input-group" style="width: 130px;">
                                <input type="number" 
                                       class="form-control text-center fw-bold font-monospace fs-4" 
                                       id="oasisDensityInput" 
                                       name="oasis_density_percent" 
                                       min="0.5" 
                                       max="20.0" 
                                       step="0.1" 
                                       value="<?= (float)$oasisDensityPercent ?>">
                                <span class="input-group-text bg-white fw-bold text-success font-monospace">%</span>
                            </div>
                        </div>

                        <!-- Raccourcis de densité -->
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 font-monospace" onclick="applyOasisDensity(1.0)">1.0% (Rare)</button>
                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 font-monospace fw-bold" onclick="applyOasisDensity(2.0)">2.0% (Standard)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 font-monospace" onclick="applyOasisDensity(3.5)">3.5% (Abondant)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 font-monospace" onclick="applyOasisDensity(5.0)">5.0% (Luxuriant)</button>
                        </div>
                    </div>

                    <!-- Switch de Réapparition Automatique & Bouton Sauvegarde -->
                    <div class="col-lg-6">
                        <div class="card card-body bg-white border p-3 shadow-xs">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       role="switch" 
                                       id="oasisRespawnToggle" 
                                       name="oasis_respawn_on_capture" 
                                       value="1" 
                                       <?= $oasisRespawnOnCapture ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-dark ms-2" for="oasisRespawnToggle">
                                    <i class="fa-solid fa-arrows-rotate text-success me-1"></i>Réapparition Automatique après Capture (Style Travian)
                                </label>
                                <div class="text-secondary small mt-1">
                                    Dès qu'une oasis sauvage est capturée par un fief, une nouvelle oasis sauvage apparaît immédiatement dans un quadrant pour maintenir constante la densité mondiale.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success fw-bold w-100 shadow-sm" id="btnSaveOasisSettings">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer les Paramètres des Oasis
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- ── OUTILS OPÉRATIONNELS D'ACTION DIRECTE (GÉNÉRATEUR & FAUNE) ── -->
            <div class="card card-body bg-white border mb-4 shadow-xs">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-toolbox text-secondary"></i>Outils Opérationnels du Générateur d'Oasis
                    </h4>
                    <span class="text-secondary small">
                        Calcul basé sur les <strong class="text-dark"><?= number_format((int)$totalTiles, 0, ',', ' ') ?> tuiles</strong> du monde actuel
                    </span>
                </div>

                <div class="row g-3 align-items-center">
                    <!-- Outil 1 : Génération & Rééquilibrage du Réseau -->
                    <div class="col-md-7">
                        <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formRepopulateOases" class="p-3 bg-light rounded border h-100">
                            <input type="hidden" name="action" value="repopulate_world_oases">
                            <input type="hidden" name="oasis_density_percent" id="hiddenRepopDensity" value="<?= (float)$oasisDensityPercent ?>">

                            <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-seedling text-success"></i>Génération &amp; Rééquilibrage Mondial
                            </div>
                            <div class="text-secondary small mb-3">
                                Peuple la carte jusqu'au quota cible (<span class="font-monospace fw-bold text-success" id="label-repop-target"><?= (int)$targetOasesCount ?></span> oasis) réparties équitablement entre les 4 quadrants.
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="clear_unoccupied" id="checkClearUnoccupied" value="1" checked>
                                <label class="form-check-label small fw-bold text-dark" for="checkClearUnoccupied">
                                    <i class="fa-solid fa-shield-halved text-primary me-1"></i>Sécurité : Remplacer uniquement les oasis sauvages existantes (préserve 100% des oasis annexées par des joueurs)
                                </label>
                            </div>

                            <button type="button" onclick="confirmRepopulateOases()" class="btn btn-success fw-bold w-100 shadow-sm">
                                <i class="fa-solid fa-bolt me-1"></i>Lancer la Génération &amp; Rééquilibrage
                            </button>
                        </form>
                    </div>

                    <!-- Outil 2 : Régénération de la Faune Sauvage -->
                    <div class="col-md-5">
                        <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formRespawnFauna" class="p-3 bg-light rounded border h-100 d-flex flex-column justify-content-between">
                            <input type="hidden" name="action" value="respawn_wild_fauna">

                            <div>
                                <div class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-paw text-warning"></i>Régénération de la Faune Sauvage
                                </div>
                                <div class="text-secondary small mb-3">
                                    Réapprovisionne la garnison animale (Sangliers, Loups du Kiso, Ours bruns) sur les oasis sauvages dépeuplées ou pacifiées par les raids.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-outline-warning text-dark fw-bold w-100 shadow-sm mt-auto">
                                <i class="fa-solid fa-paw me-1"></i>Régénérer les Bêtes Sauvages
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── 4 CARTES KPIS DE L'ÉCOSYSTÈME D'OASIS ── -->
            <div class="row row-cards mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-sm border-start border-3 border-success shadow-xs h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">Oasis Sauvages Libres</div>
                            <div class="h1 m-0 text-success fw-bold font-monospace"><?= number_format((int)$oasisWild) ?></div>
                            <div class="text-secondary small mt-2 pt-2 border-top">
                                <i class="fa-solid fa-tree text-success me-1"></i>Disponibles à la conquête
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card card-sm border-start border-3 border-primary shadow-xs h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">Oasis Annexées / Fiefs</div>
                            <div class="h1 m-0 text-primary fw-bold font-monospace"><?= number_format((int)$oasisCaptured) ?></div>
                            <div class="text-secondary small mt-2 pt-2 border-top">
                                <i class="fa-solid fa-chess-rook text-primary me-1"></i>Sous protectorat d'un Daimyō
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card card-sm border-start border-3 border-warning shadow-xs h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">Faune Sauvage Totale</div>
                            <div class="h1 m-0 text-warning fw-bold font-monospace"><?= number_format((int)$oasisFaunaCount) ?></div>
                            <div class="text-secondary small mt-2 pt-2 border-top font-monospace">
                                <i class="fa-solid fa-paw text-warning me-1"></i>Sangliers, Loups &amp; Ours
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card card-sm border-start border-3 border-teal shadow-xs h-100 bg-white">
                        <div class="card-body p-3">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">Couverture Réelle</div>
                            <?php $actualOasisPct = $totalTiles > 0 ? round(($oasesCount / $totalTiles) * 100, 2) : 0; ?>
                            <div class="h1 m-0 text-teal fw-bold font-monospace"><?= $actualOasisPct ?>%</div>
                            <div class="text-secondary small mt-2 pt-2 border-top">
                                <i class="fa-solid fa-chart-line text-teal me-1"></i>Cible : <?= (float)$oasisDensityPercent ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TABLE DES OASIS ENREGISTRÉES DU MONDE ── -->
            <div class="card shadow-xs border">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-list-check text-success"></i>Registre des Oasis Actives sur l'Archipel (<?= count($allOasesList) ?> recensées)
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="filterOasisTable" class="form-control form-control-sm" placeholder="Filtrer par nom ou coords..." style="width: 220px;" onkeyup="filterOasesRows()">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 380px;">
                    <table class="table table-vcenter table-striped table-hover card-table text-nowrap" id="tableOasesList">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="w-1">#</th>
                                <th>Nom du Havre Naturel</th>
                                <th class="text-center">Coordonnées</th>
                                <th>Bonus de Récolte</th>
                                <th class="text-center">Statut Féodal</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allOasesList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-seedling text-muted fs-2 d-block mb-2"></i>
                                        Aucune oasis n'est actuellement déployée. Utilisez le générateur ci-dessus pour peupler l'archipel !
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allOasesList as $oa): ?>
                                    <?php 
                                    $isOccupied = !empty($oa['owner_planet_id']);
                                    $x = (int)$oa['coord_x'];
                                    $y = (int)$oa['coord_y'];
                                    ?>
                                    <tr class="oasis-data-row">
                                        <td class="text-secondary font-monospace">#<?= (int)$oa['id'] ?></td>
                                        <td class="fw-bold">
                                            <span class="oasis-name-col">
                                                <i class="fa-solid fa-leaf text-success me-1"></i><?= htmlspecialchars($oa['name']) ?>
                                            </span>
                                            <div class="small text-secondary font-monospace"><?= htmlspecialchars($oa['oasis_type'] ?? 'nature') ?></div>
                                        </td>
                                        <td class="text-center font-monospace oasis-coords-col">
                                            <span class="badge bg-dark bg-opacity-75 text-white font-monospace">[<?= $x ?> : <?= $y ?>]</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if (!empty($oa['bonus_wood'])): ?>
                                                    <span class="badge bg-green-lt font-monospace">+<?= (int)$oa['bonus_wood'] ?>% Bois</span>
                                                <?php endif; ?>
                                                <?php if (!empty($oa['bonus_stone'])): ?>
                                                    <span class="badge bg-blue-lt font-monospace">+<?= (int)$oa['bonus_stone'] ?>% Pierre</span>
                                                <?php endif; ?>
                                                <?php if (!empty($oa['bonus_rice'])): ?>
                                                    <span class="badge bg-yellow-lt font-monospace">+<?= (int)$oa['bonus_rice'] ?>% Riz</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isOccupied): ?>
                                                <span class="badge bg-primary text-white font-monospace">
                                                    <i class="fa-solid fa-crown me-1"></i>Annexée par <?= htmlspecialchars($oa['owner_username'] ?? 'Fief') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-lt text-success font-monospace fw-bold">
                                                    <i class="fa-solid fa-paw me-1"></i>Sauvage Libre
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/?page=galaxy&x=<?= $x ?>&y=<?= $y ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Voir sur la Carte des Provinces">
                                                <i class="fa-solid fa-map-location-dot me-1"></i>Carte
                                            </a>
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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3 : TÉLÉMÉTRIE D'OCCUPATION DU MONDE & DENSITÉ SPATIALE     -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="row row-cards mb-4">
        <!-- Décomposition de l'occupation spatiale -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm border">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-layer-group text-primary"></i>Décomposition de l'Arpentage Fiefs &amp; Nature
                    </h4>
                    <span class="badge bg-primary-lt fw-bold font-monospace"><?= number_format((int)$totalOccupied, 0, ',', ' ') ?> Tuiles Colonisées</span>
                </div>
                <div class="card-body">
                    <!-- Jauge Multi-Segments -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Répartition des Tuiles sur la Grille</span>
                            <span class="font-monospace"><?= number_format((int)$totalTiles, 0, ',', ' ') ?> tuiles au total</span>
                        </div>
                        <div class="progress" style="height: 18px; border-radius: 6px;">
                            <?php 
                            $pctHuman = $totalTiles > 0 ? ($humanVillages / $totalTiles) * 100 : 0;
                            $pctBots = $totalTiles > 0 ? ($botVillages / $totalTiles) * 100 : 0;
                            $pctFree = $totalTiles > 0 ? ($freeLands / $totalTiles) * 100 : 0;
                            $pctCastles = $totalTiles > 0 ? ($authenticCastles / $totalTiles) * 100 : 0;
                            $pctOases = $totalTiles > 0 ? ($oasesCount / $totalTiles) * 100 : 0;
                            $pctNatural = 100 - ($pctHuman + $pctBots + $pctFree + $pctCastles + $pctOases);
                            ?>
                            <div class="progress-bar bg-primary" style="width: <?= max(0.5, $pctHuman) ?>%" title="Fiefs Joueurs: <?= $humanVillages ?>"></div>
                            <div class="progress-bar bg-warning" style="width: <?= max(0.5, $pctBots) ?>%" title="Fiefs Bots: <?= $botVillages ?>"></div>
                            <div class="progress-bar bg-secondary" style="width: <?= max(0.5, $pctFree) ?>%" title="Terres Libres: <?= $freeLands ?>"></div>
                            <div class="progress-bar bg-danger" style="width: <?= max(0.5, $pctCastles) ?>%" title="12 Châteaux: <?= $authenticCastles ?>"></div>
                            <div class="progress-bar bg-success" style="width: <?= max(0.5, $pctOases) ?>%" title="Oasis: <?= $oasesCount ?>"></div>
                            <div class="progress-bar bg-teal bg-opacity-25" style="width: <?= max(1, $pctNatural) ?>%" title="Nature Vierge: <?= $naturalTiles ?>"></div>
                        </div>
                    </div>

                    <!-- Liste Détaillée des Entités -->
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary rounded-circle p-1"></span>
                                    <span class="small fw-bold">Fiefs Daimyōs Humains :</span>
                                </span>
                                <span class="font-monospace fw-bold text-primary"><?= number_format((int)$humanVillages) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning rounded-circle p-1"></span>
                                    <span class="small fw-bold">Fiefs Seigneurs Bots (IA) :</span>
                                </span>
                                <span class="font-monospace fw-bold text-warning"><?= number_format((int)$botVillages) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary rounded-circle p-1"></span>
                                    <span class="small fw-bold">Terres Libres Arpentées :</span>
                                </span>
                                <span class="font-monospace fw-bold text-secondary"><?= number_format((int)$freeLands) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger rounded-circle p-1"></span>
                                    <span class="small fw-bold">12 Donjons Authentiques :</span>
                                </span>
                                <span class="font-monospace fw-bold text-danger"><?= number_format((int)$authenticCastles) ?> / 12</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success rounded-circle p-1"></span>
                                    <span class="small fw-bold">Oasis Naturelles :</span>
                                </span>
                                <span class="font-monospace fw-bold text-success"><?= number_format((int)$oasesCount) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge bg-teal rounded-circle p-1"></span>
                                    <span class="small fw-bold">Terres Naturelles Vierges :</span>
                                </span>
                                <span class="font-monospace fw-bold text-teal"><?= number_format((int)$naturalTiles) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition par Biomes Naturels Déterministes -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm border">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-tree text-success"></i>Biomes Naturels Déterministes (Hachage 2D)
                    </h4>
                    <span class="badge bg-success-lt font-monospace"><?= number_format((int)$naturalTiles, 0, ',', ' ') ?> tuiles</span>
                </div>
                <div class="card-body p-3">
                    <div class="text-secondary small mb-3">
                        Génération procédurale spatiale selon l'algorithme déterministe OpenShogun (aucun stockage SQL redondant) :
                    </div>
                    <div class="list-group list-group-flush border rounded overflow-hidden">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fa-solid fa-seedling text-success me-2"></i>Plaines Fertiles &amp; Rizières (60%)</span>
                            <span class="badge bg-success-lt font-monospace"><?= number_format((int)$biomesEstimate['plains']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fa-solid fa-tree text-green me-2"></i>Forêts de Cèdres (Sugi) (15%)</span>
                            <span class="badge bg-green-lt font-monospace"><?= number_format((int)$biomesEstimate['forest']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fa-solid fa-mountain text-secondary me-2"></i>Montagnes &amp; Falaises (12%)</span>
                            <span class="badge bg-secondary-lt font-monospace"><?= number_format((int)$biomesEstimate['mountain']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fa-solid fa-mountain-sun text-warning me-2"></i>Collines &amp; Terrasses (8%)</span>
                            <span class="badge bg-warning-lt font-monospace"><?= number_format((int)$biomesEstimate['hills']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fa-solid fa-water text-azure me-2"></i>Lacs &amp; Eaux Calmes (5%)</span>
                            <span class="badge bg-azure-lt font-monospace"><?= number_format((int)$biomesEstimate['lake']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 4 : MINICARTE RADAR DES 4 QUADRANTS & KYOTO [0:0]           -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 border shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="card-title text-dark m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-compass text-danger"></i>Découpage Tactique des 4 Quadrants &amp; Capitale Impériale
                </h4>
                <div class="text-secondary small">
                    Organisation spatiale autour de Kyoto <code>[0 : 0]</code> pour la colonisation des Daimyōs et des Clans IA.
                </div>
            </div>
            <span class="badge bg-dark text-white font-monospace">Origine Centrale : [0 : 0]</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 text-center">
                <!-- Quadrant Nord-Ouest -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-sm border bg-light h-100 p-3">
                        <div class="fw-bold text-dark fs-4 mb-1">↖️ Nord-Ouest</div>
                        <div class="badge bg-secondary text-white font-monospace mb-2">[-X / +Y]</div>
                        <div class="text-secondary small mb-2">Provinces de Montagnes &amp; Forêts Septentrionales</div>
                        <div class="mt-auto pt-2 border-top">
                            <span class="h2 m-0 text-dark fw-bold font-monospace"><?= (int)($quadrants['NO'] ?? 0) ?></span>
                            <span class="text-muted small d-block">fiefs installés</span>
                        </div>
                    </div>
                </div>

                <!-- Quadrant Nord-Est -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-sm border bg-light h-100 p-3">
                        <div class="fw-bold text-dark fs-4 mb-1">↗️ Nord-Est</div>
                        <div class="badge bg-secondary text-white font-monospace mb-2">[+X / +Y]</div>
                        <div class="text-secondary small mb-2">Plaines d'Echigo &amp; Coteaux de Mutsu</div>
                        <div class="mt-auto pt-2 border-top">
                            <span class="h2 m-0 text-dark fw-bold font-monospace"><?= (int)($quadrants['NE'] ?? 0) ?></span>
                            <span class="text-muted small d-block">fiefs installés</span>
                        </div>
                    </div>
                </div>

                <!-- Quadrant Sud-Ouest -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-sm border bg-light h-100 p-3">
                        <div class="fw-bold text-dark fs-4 mb-1">↙️ Sud-Ouest</div>
                        <div class="badge bg-secondary text-white font-monospace mb-2">[-X / -Y]</div>
                        <div class="text-secondary small mb-2">Fiefs Côtiers du Shikoku &amp; Mers Intérieures</div>
                        <div class="mt-auto pt-2 border-top">
                            <span class="h2 m-0 text-dark fw-bold font-monospace"><?= (int)($quadrants['SO'] ?? 0) ?></span>
                            <span class="text-muted small d-block">fiefs installés</span>
                        </div>
                    </div>
                </div>

                <!-- Quadrant Sud-Est -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-sm border bg-light h-100 p-3">
                        <div class="fw-bold text-dark fs-4 mb-1">↘️ Sud-Est</div>
                        <div class="badge bg-secondary text-white font-monospace mb-2">[+X / -Y]</div>
                        <div class="text-secondary small mb-2">Plaines du Tokaido &amp; Rivages de Mikawa</div>
                        <div class="mt-auto pt-2 border-top">
                            <span class="h2 m-0 text-dark fw-bold font-monospace"><?= (int)($quadrants['SE'] ?? 0) ?></span>
                            <span class="text-muted small d-block">fiefs installés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SCRIPT JS DE CALCUL EN TEMPS RÉEL (TUILES & OASIS)                   -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    const labelRepopTarget = document.getElementById('label-repop-target');

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
        if (labelRepopTarget) labelRepopTarget.innerText = formatNumberFr(targetCount);
    }

    function updateWorldCalculations(radius) {
        radius = parseInt(radius, 10);
        if (isNaN(radius) || radius < 1) radius = 10;

        const dim = (radius * 2) + 1;
        const total = dim * dim;
        currentCalculatedTotalTiles = total;
        const perimeter = radius * 8;
        const saturation = total > 0 ? ((totalOccupied / total) * 100).toFixed(2) : 0;

        // Mise à jour des affichages Item 1
        if (badgePreview) badgePreview.innerText = formatNumberFr(total) + " Tuiles";
        if (labelRange) labelRange.innerText = "[-" + radius + " : +" + radius + "]";
        if (kpiTotal) kpiTotal.innerText = formatNumberFr(total);
        if (kpiGridDim) kpiGridDim.innerText = dim + " × " + dim;
        if (kpiPerimeter) kpiPerimeter.innerText = formatNumberFr(perimeter);
        if (kpiSaturation) kpiSaturation.innerText = saturation + "%";
        if (kpiOccBase) kpiOccBase.innerText = formatNumberFr(total);

        // Alerte de sécurité si le rayon est inférieur au rayon minimal requis
        if (boxWarning && warnRadiusSpan) {
            if (radius < minRequiredRadius) {
                warnRadiusSpan.innerText = radius;
                boxWarning.classList.remove('d-none');
            } else {
                boxWarning.classList.add('d-none');
            }
        }

        // Réactualiser également le nombre cible d'oasis (Item 2) basé sur ce nouveau nombre de tuiles
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
                if (val >= slider.min && val <= slider.max) {
                    slider.value = val;
                }
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
                if (val >= parseFloat(oasisSlider.min) && val <= parseFloat(oasisSlider.max)) {
                    oasisSlider.value = val;
                }
                updateOasisCalculations(val);
            }
        });
    }

    window.applyWorldPreset = function(presetRadius) {
        if (slider) slider.value = presetRadius;
        if (input) input.value = presetRadius;
        updateWorldCalculations(presetRadius);

        // Highlight preset buttons
        document.querySelectorAll('.preset-btn').forEach(function(btn) {
            const r = parseInt(btn.getAttribute('data-radius'), 10);
            if (r === presetRadius) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-azure', 'shadow-sm');
            } else {
                btn.classList.remove('btn-azure', 'shadow-sm');
                btn.classList.add('btn-outline-secondary');
            }
        });
    };

    window.applyOasisDensity = function(densityVal) {
        if (oasisSlider) oasisSlider.value = densityVal;
        if (oasisInput) oasisInput.value = densityVal.toFixed(1);
        updateOasisCalculations(densityVal);
    };

    window.confirmRepopulateOases = function() {
        const density = oasisInput ? parseFloat(oasisInput.value) : 2.0;
        const target = labelTargetOases ? labelTargetOases.innerText : '';
        const msg = `Confirmez-vous le rééquilibrage du réseau d'oasis avec une densité de ${density}% (~${target} oasis au total) ?`;
        
        if (typeof showConfirmModal === 'function') {
            showConfirmModal(
                "Génération & Rééquilibrage des Oasis",
                msg,
                () => { document.getElementById('formRepopulateOases').submit(); },
                'btn-success',
                '<i class="fa-solid fa-leaf text-success"></i>'
            );
        } else {
            if (confirm(msg)) {
                document.getElementById('formRepopulateOases').submit();
            }
        }
    };

    window.filterOasesRows = function() {
        const query = (document.getElementById('filterOasisTable')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.oasis-data-row');
        rows.forEach(function(r) {
            const name = (r.querySelector('.oasis-name-col')?.innerText || '').toLowerCase();
            const coords = (r.querySelector('.oasis-coords-col')?.innerText || '').toLowerCase();
            if (!query || name.includes(query) || coords.includes(query)) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    };
});
</script>
