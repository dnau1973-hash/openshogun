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
    <!-- TROISIÈME ITEM MAJEUR : VITESSES & ÉQUILIBRAGE DU MONDE FÉODAL     -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 border-danger shadow-sm overflow-hidden" id="section-world-speed-config" style="border-width: 2px;">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h3 class="card-title text-white m-0 d-flex align-items-center gap-2 fw-bold" style="font-size:1.25rem;">
                    <i class="fa-solid fa-bolt text-warning fs-2"></i>
                    <span>3. Vitesses &amp; Équilibrage du Monde Féodal</span>
                </h3>
                <div class="text-white-50 small mt-1">
                    Multiplicateurs temporels majeurs : chantiers, production des ressources, marche des armées féodales et règles d'immunité.
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-white text-danger fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-game-speed">
                    x<?= (int)$gameSpeed ?> Jeu
                </span>
                <span class="badge bg-white text-dark fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-resource-speed">
                    x<?= (int)$resourceSpeed ?> Prod
                </span>
                <span class="badge bg-white text-dark fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-fleet-speed">
                    x<?= (int)$fleetSpeed ?> Marche
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light bg-opacity-25">
            <!-- 1. Préréglages Rapides 1-Clic -->
            <div class="p-3 bg-white border rounded shadow-xs mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <span class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-gauge-high text-danger"></i>Préréglages Serveur 1-Clic Recommandés :
                    </span>
                    <span class="text-secondary small">Calibre instantanément les 3 curseurs temporels</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm speed-preset-btn fw-bold" onclick="applyDevSpeedPreset(1, 1, 1, this)">
                        <i class="fa-solid fa-hourglass-start me-1"></i>1x Classique (Temps Réel)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm speed-preset-btn fw-bold" onclick="applyDevSpeedPreset(3, 3, 2, this)">
                        <i class="fa-solid fa-shield-halved me-1"></i>3x Cadence Sengoku
                    </button>
                    <button type="button" class="btn btn-danger btn-sm speed-preset-btn fw-bold shadow-sm" onclick="applyDevSpeedPreset(5, 5, 5, this)">
                        <i class="fa-solid fa-bolt me-1"></i>5x Vitesse Shogun (Défaut)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm speed-preset-btn fw-bold" onclick="applyDevSpeedPreset(10, 10, 8, this)">
                        <i class="fa-solid fa-fire me-1"></i>10x Guerre Totale
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm speed-preset-btn fw-bold" onclick="applyDevSpeedPreset(20, 20, 10, this)">
                        <i class="fa-solid fa-forward-fast me-1"></i>20x Tournoi Éclair
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm speed-preset-btn fw-bold" onclick="applyDevSpeedPreset(50, 50, 20, this)">
                        <i class="fa-solid fa-rocket me-1"></i>50x Hyper / Dev
                    </button>
                </div>
            </div>

            <!-- Formulaire Principal de Vitesses et Équilibrage -->
            <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formWorldSpeedConfig">
                <input type="hidden" name="action" value="save_world_speed_balancing">

                <!-- 2. Les 3 Curseurs de Vitesse -->
                <div class="row g-4 mb-4">
                    <!-- Vitesse du Jeu (Chantiers & Recherches) -->
                    <div class="col-lg-4">
                        <div class="card h-100 border shadow-xs p-3 bg-white">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fa-solid fa-bolt text-danger me-1"></i>Vitesse du Jeu (Chantiers)</span>
                                <span class="badge bg-danger text-white font-monospace fs-4" id="badge-val-game-speed">x<?= (int)$gameSpeed ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="range" class="form-range flex-grow-1" id="sliderGameSpeed" name="game_speed_slider" min="1" max="100" value="<?= (int)$gameSpeed ?>" style="cursor:pointer; height:8px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 80px;" id="inputGameSpeed" name="game_speed" min="1" max="100" value="<?= (int)$gameSpeed ?>">
                            </div>
                            <div class="text-secondary small mt-auto">
                                <i class="fa-solid fa-circle-info text-danger me-1"></i>Divise la durée requise pour tous les chantiers (Tenshu, Parcelles rurales, Bâtiments urbains) et recherches de l'Académie.
                            </div>
                        </div>
                    </div>

                    <!-- Production des Ressources -->
                    <div class="col-lg-4">
                        <div class="card h-100 border shadow-xs p-3 bg-white">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Production des Ressources</span>
                                <span class="badge bg-warning text-dark font-monospace fs-4" id="badge-val-resource-speed">x<?= (int)$resourceSpeed ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="range" class="form-range flex-grow-1" id="sliderResourceSpeed" name="resource_speed_slider" min="1" max="100" value="<?= (int)$resourceSpeed ?>" style="cursor:pointer; height:8px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 80px;" id="inputResourceSpeed" name="resource_speed" min="1" max="100" value="<?= (int)$resourceSpeed ?>">
                            </div>
                            <div class="text-secondary small mt-auto">
                                <i class="fa-solid fa-circle-info text-warning me-1"></i>Multiplie la cadence horaire de Bois de Cèdre, Pierre de Granit, Koku de Riz, Poisson, Argile et Charbon.
                            </div>
                        </div>
                    </div>

                    <!-- Marche des Troupes & Expéditions -->
                    <div class="col-lg-4">
                        <div class="card h-100 border shadow-xs p-3 bg-white">
                            <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                <span><i class="fa-solid fa-person-walking text-primary me-1"></i>Marche des Troupes &amp; Trajets</span>
                                <span class="badge bg-primary text-white font-monospace fs-4" id="badge-val-fleet-speed">x<?= (int)$fleetSpeed ?></span>
                            </label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="range" class="form-range flex-grow-1" id="sliderFleetSpeed" name="fleet_speed_slider" min="1" max="50" value="<?= (int)$fleetSpeed ?>" style="cursor:pointer; height:8px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 80px;" id="inputFleetSpeed" name="fleet_speed" min="1" max="50" value="<?= (int)$fleetSpeed ?>">
                            </div>
                            <div class="text-secondary small mt-auto">
                                <i class="fa-solid fa-circle-info text-primary me-1"></i>Accélère les régiments féodaux pour les assauts, convois de tributs marchands, fondations de fiefs et quêtes du Héros.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Simulateur d'Impact Temporel en Direct (JS Temps Réel) -->
                <div class="card border-primary bg-primary bg-opacity-10 shadow-xs mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h5 class="m-0 text-primary fw-bold d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calculator text-primary"></i>Simulateur d'Impact Temporel en Temps Réel
                            </h5>
                            <span class="badge bg-white text-primary border font-monospace">Prévisualisation instantanée sans rechargement</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-building text-danger me-1"></i>Chantier Tenshu Niv 10 :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-danger fw-bold" id="sim-build-time">--</span>
                                        <span class="text-muted small text-decoration-line-through">base 1h 00m</span>
                                    </div>
                                    <div class="text-muted small mt-1">Impact Vitesse Jeu (Base 3 600 s)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-horse text-primary me-1"></i>Marche Régimentaire (10 Tuiles) :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-primary fw-bold" id="sim-march-time">--</span>
                                        <span class="text-muted small text-decoration-line-through">base 30m 00s</span>
                                    </div>
                                    <div class="text-muted small mt-1">Impact Vitesse Marche (Base 1 800 s)</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-wheat-awn text-warning me-1"></i>Rizière Niv 10 (Production) :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-success fw-bold" id="sim-resource-prod">--</span>
                                        <span class="text-muted small text-decoration-line-through">base 250/h</span>
                                    </div>
                                    <div class="text-muted small mt-1">Impact Vitesse Prod (Base 250 koku/h)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Règles & Équilibrage Féodal (Protection, Héros, Famine) -->
                <div class="row g-4 mb-4">
                    <!-- Immunité Débutant -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Immunité Débutant</span>
                            <span class="badge bg-success-lt font-monospace fw-bold" id="badge-val-protection-days"><?= (int)$beginnerProtectionDays ?> jours</span>
                        </label>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="range" class="form-range flex-grow-1" id="sliderProtectionDays" name="protection_days_slider" min="0" max="30" value="<?= (int)$beginnerProtectionDays ?>" style="cursor:pointer;">
                            <div class="input-group" style="width: 90px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold" id="inputProtectionDays" name="beginner_protection_days" min="0" max="60" value="<?= (int)$beginnerProtectionDays ?>">
                                <span class="input-group-text px-1 text-muted">j</span>
                            </div>
                        </div>
                        <div class="text-secondary small">Durée d'immunité accordée à l'inscription (0 = désactiver).</div>
                    </div>

                    <!-- Cages de Capture Kago Héros -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fa-solid fa-box-open text-warning me-1"></i>Cages Kago en Quête (%)</span>
                            <span class="badge bg-warning-lt font-monospace fw-bold" id="badge-val-cage-drop"><?= (int)$heroCageDropRate ?> %</span>
                        </label>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="range" class="form-range flex-grow-1" id="sliderCageDrop" name="cage_drop_slider" min="0" max="100" value="<?= (int)$heroCageDropRate ?>" style="cursor:pointer;">
                            <div class="input-group" style="width: 90px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold" id="inputCageDrop" name="hero_cage_drop_rate" min="0" max="100" value="<?= (int)$heroCageDropRate ?>">
                                <span class="input-group-text px-1 text-muted">%</span>
                            </div>
                        </div>
                        <div class="text-secondary small">Probabilité de rapporter des cages pour capturer la faune sauvage.</div>
                    </div>

                    <!-- Gain d'XP du Héros -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fa-solid fa-user-ninja text-purple me-1"></i>Gain d'XP Héros (%)</span>
                            <span class="badge bg-purple-lt font-monospace fw-bold" id="badge-val-hero-xp"><?= (int)$heroXpRatePercent ?> %</span>
                        </label>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <input type="range" class="form-range flex-grow-1" id="sliderHeroXp" name="hero_xp_slider" min="10" max="500" step="5" value="<?= (int)$heroXpRatePercent ?>" style="cursor:pointer;">
                            <div class="input-group" style="width: 90px;">
                                <input type="number" class="form-control text-center font-monospace fw-bold" id="inputHeroXp" name="hero_xp_rate_percent" min="10" max="500" step="5" value="<?= (int)$heroXpRatePercent ?>">
                                <span class="input-group-text px-1 text-muted">%</span>
                            </div>
                        </div>
                        <div class="text-secondary small">Multiplicateur d'XP en aventures (100% = normal, 200% = double XP).</div>
                    </div>
                </div>

                <!-- 5. Mécanisme de Famine & Farine Féodale (Optionnel) -->
                <div class="card border border-danger-subtle shadow-sm mb-4 overflow-hidden" style="border-left: 5px solid #dc3545 !important;">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger text-white p-2 rounded-2 fs-3 shadow-xs">
                                <i class="fa-solid fa-wheat-awn"></i>
                            </span>
                            <div>
                                <h4 class="card-title text-danger m-0 fw-bold d-flex align-items-center gap-2" style="font-size: 1.1rem;">
                                    Mécanisme de Famine &amp; Rations de Farine (Optionnel)
                                </h4>
                                <div class="text-secondary small mt-1">
                                    Régulation de subsistance pour les régiments d'élite et gestion des disettes de farine.
                                </div>
                            </div>
                        </div>
                        <span class="badge <?= !empty($famineEnabled) ? 'bg-danger text-white' : 'bg-secondary text-white' ?> font-monospace px-3 py-2 fs-5 shadow-xs" id="badge-famine-status-pill">
                            <i class="fa-solid <?= !empty($famineEnabled) ? 'fa-triangle-exclamation' : 'fa-circle-xmark' ?> me-1"></i><?= !empty($famineEnabled) ? 'Famine Activée' : 'Famine Désactivée' ?>
                        </span>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-50">
                        <div class="alert alert-danger bg-white border-danger border-opacity-25 text-dark mb-4 p-3 shadow-xs">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fa-solid fa-circle-exclamation text-danger fs-2 mt-1"></i>
                                <div class="small text-dark" style="line-height: 1.55;">
                                    <strong>Règle féodale de subsistance :</strong> Si activé, les régiments d'élite (Tier 2, 3 et 4) exigent un entretien horaire en <strong class="text-danger">Farine de Riz</strong>. En cas de pénurie totale (stock de farine à 0), une disette s'abat sur le fief et décime progressivement les garnisons d'élite à hauteur du taux horaire configuré. <em>(Désactivé par défaut)</em>.
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Contrôle 1 : Switch d'activation -->
                            <div class="col-lg-4">
                                <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                            <span><i class="fa-solid fa-power-off text-danger me-1"></i>État du Mécanisme</span>
                                            <span class="badge <?= !empty($famineEnabled) ? 'bg-danger text-white' : 'bg-secondary text-white' ?> font-monospace" id="badge-famine-switch-state">
                                                <?= !empty($famineEnabled) ? 'En service' : 'Inactif' ?>
                                            </span>
                                        </label>
                                        <div class="p-3 rounded bg-light border mb-2">
                                            <label class="form-check form-switch m-0 d-flex align-items-center gap-2 cursor-pointer">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" id="checkFamineEnabled" name="famine_enabled" value="1" <?= !empty($famineEnabled) ? 'checked' : '' ?> onchange="updateFamineSwitchBadges(this.checked)" style="cursor: pointer; transform: scale(1.2);">
                                                <span class="form-check-label fw-bold text-dark fs-4">Activer le péril de famine</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="text-secondary small mt-2">
                                        <i class="fa-solid fa-shield-halved text-success me-1"></i>Si décoché, les troupes d'élite ne subissent aucune perte même en cas d'épuisement des stocks.
                                    </div>
                                </div>
                            </div>

                            <!-- Contrôle 2 : Taux de pertes horaire -->
                            <div class="col-lg-4">
                                <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                            <span><i class="fa-solid fa-skull text-danger me-1"></i>Pertes horaires en disette</span>
                                            <span class="badge bg-danger text-white font-monospace fs-4" id="badge-val-famine-rate"><?= (float)$famineRate ?> %</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <input type="range" class="form-range flex-grow-1" id="sliderFamineRate" name="famine_rate_slider" min="0.5" max="25.0" step="0.5" value="<?= (float)$famineRate ?>" style="cursor:pointer; height:8px;">
                                            <div class="input-group" style="width: 100px;">
                                                <input type="number" class="form-control text-center font-monospace fw-bold fs-4" id="inputFamineRate" name="famine_rate" min="0.5" max="25.0" step="0.5" value="<?= (float)$famineRate ?>">
                                                <span class="input-group-text bg-light fw-bold text-danger px-2">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-secondary small mt-2">
                                        Pourcentage de soldats d'élite mourant de faim ou désertant par heure de rupture complète de farine.
                                    </div>
                                </div>
                            </div>

                            <!-- Contrôle 3 : Rations requises -->
                            <div class="col-lg-4">
                                <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                            <span><i class="fa-solid fa-bowl-rice text-warning me-1"></i>Rations Requises</span>
                                            <span class="badge bg-warning text-dark font-monospace fs-4" id="badge-val-famine-flour"><?= (float)$famineFlourConsumption ?> / h</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <input type="range" class="form-range flex-grow-1" id="sliderFamineFlour" name="famine_flour_slider" min="0.1" max="10.0" step="0.1" value="<?= (float)$famineFlourConsumption ?>" style="cursor:pointer; height:8px;">
                                            <div class="input-group" style="width: 100px;">
                                                <input type="number" class="form-control text-center font-monospace fw-bold fs-4" id="inputFamineFlour" name="famine_flour_consumption" min="0.1" max="10.0" step="0.1" value="<?= (float)$famineFlourConsumption ?>">
                                                <span class="input-group-text bg-light px-2"><i class="fa-solid fa-bowl-rice text-warning"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-secondary small mt-2">
                                        Unités de farine de riz nécessaires par heure pour nourrir 100 soldats d'élite et prévenir la famine.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Bouton d'Enregistrement -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pt-3 border-top">
                    <div class="text-secondary small">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>Les modifications sont appliquées instantanément à tous les fiefs et armées en cours.
                    </div>
                    <button type="submit" class="btn btn-danger btn-lg px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer les Vitesses &amp; l'Équilibrage
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- QUATRIÈME ITEM MAJEUR : DOTATION INITIALE & IMMUNITÉ DÉBUTANT       -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card mb-4 border-purple shadow-sm overflow-hidden" id="section-world-starting-endowment" style="border-width: 2px;">
        <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div>
                <h3 class="card-title text-white m-0 d-flex align-items-center gap-2 fw-bold" style="font-size:1.25rem;">
                    <i class="fa-solid fa-gift text-warning fs-2"></i>
                    <span>4. Dotation Initiale du Nouveau Seigneur &amp; Durée de l'Immunité</span>
                </h3>
                <div class="text-white-50 small mt-1">
                    Définissez les vivres, matériaux et trésors attribués d'office à la fondation du premier domaine, ainsi que la durée de la trêve sacrée (bouclier féodal).
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-white text-purple fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-starter-res">
                    <?= number_format((int)$starterWood, 0, ',', ' ') ?> / <?= number_format((int)$starterStone, 0, ',', ' ') ?> / <?= number_format((int)$starterRice, 0, ',', ' ') ?> Matériaux
                </span>
                <span class="badge bg-white text-dark fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-starter-gold">
                    <i class="fa-solid fa-coins text-warning me-1"></i><?= number_format((int)$starterGoldCoins, 0, ',', ' ') ?> Ryō
                </span>
                <span class="badge bg-white text-success fw-bold px-3 py-2 font-monospace fs-4 shadow-sm" id="badge-header-starter-protection">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i><?= (int)$beginnerProtectionDays ?> jours
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light bg-opacity-25">
            <!-- 1. Préréglages Rapides 1-Clic de Dotation -->
            <div class="p-3 bg-white border rounded shadow-xs mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <span class="fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-purple"></i>Préréglages de Démarrage 1-Clic Recommandés :
                    </span>
                    <span class="text-secondary small">Ajuste instantanément ressources, or et durée d'immunité</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-purple btn-sm starter-preset-btn fw-bold shadow-sm" onclick="applyStarterPreset(1000, 1000, 1000, 250, 100, 7, this)">
                        <i class="fa-solid fa-award me-1"></i>Standard Shogun (1 000 / 100 Or / 7j)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm starter-preset-btn fw-bold" onclick="applyStarterPreset(750, 750, 750, 0, 50, 3, this)">
                        <i class="fa-solid fa-scroll me-1"></i>Classique Travian (750 / 50 Or / 3j)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm starter-preset-btn fw-bold" onclick="applyStarterPreset(2500, 2500, 2500, 500, 250, 14, this)">
                        <i class="fa-solid fa-hand-holding-heart me-1"></i>Découverte &amp; Bienvenue (2 500 / 250 Or / 14j)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm starter-preset-btn fw-bold" onclick="applyStarterPreset(5000, 5000, 5000, 1000, 500, 3, this)">
                        <i class="fa-solid fa-bolt me-1"></i>Tournoi Éclair (5 000 / 500 Or / 3j)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm starter-preset-btn fw-bold" onclick="applyStarterPreset(300, 300, 300, 0, 0, 0, this)">
                        <i class="fa-solid fa-skull me-1"></i>Survie Hardcore (300 / 0 Or / 0j)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm starter-preset-btn fw-bold" onclick="applyStarterPreset(15000, 15000, 15000, 3000, 1000, 30, this)">
                        <i class="fa-solid fa-crown me-1"></i>Bâtisseur Impérial (15k / 1 000 Or / 30j)
                    </button>
                </div>
            </div>

            <!-- Formulaire Principal de Dotation & Protection -->
            <form method="POST" action="?page=gamestudio&tab=general&subtab=world_config" id="formWorldStarterEndowment">
                <input type="hidden" name="action" value="save_world_starting_endowment">

                <!-- 2. Les 4 Ressources Féodales de Dotation -->
                <div class="row g-3 mb-4">
                    <!-- Bois de Cèdre -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre (Sugi)</span>
                                    <span class="badge bg-green text-white font-monospace fs-4" id="badge-val-starter-wood"><?= number_format((int)$starterWood) ?></span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterWood" name="starter_wood_slider" min="0" max="20000" step="50" value="<?= (int)$starterWood ?>" style="cursor:pointer; height:8px;">
                                    <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 85px;" id="inputStarterWood" name="starter_wood" min="0" max="50000" value="<?= (int)$starterWood ?>">
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                Bois d'œuvre pour charpentes, scieries et Tenshu.
                            </div>
                        </div>
                    </div>

                    <!-- Pierre de Granit -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Granit</span>
                                    <span class="badge bg-secondary text-white font-monospace fs-4" id="badge-val-starter-stone"><?= number_format((int)$starterStone) ?></span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterStone" name="starter_stone_slider" min="0" max="20000" step="50" value="<?= (int)$starterStone ?>" style="cursor:pointer; height:8px;">
                                    <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 85px;" id="inputStarterStone" name="starter_stone" min="0" max="50000" value="<?= (int)$starterStone ?>">
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                Blocs taillés pour les remparts cyclopéens et carrières.
                            </div>
                        </div>
                    </div>

                    <!-- Koku de Riz -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Koku de Riz</span>
                                    <span class="badge bg-warning text-dark font-monospace fs-4" id="badge-val-starter-rice"><?= number_format((int)$starterRice) ?></span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterRice" name="starter_rice_slider" min="0" max="20000" step="50" value="<?= (int)$starterRice ?>" style="cursor:pointer; height:8px;">
                                    <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 85px;" id="inputStarterRice" name="starter_rice" min="0" max="50000" value="<?= (int)$starterRice ?>">
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                Rations agricoles pour les ouvriers et paysans du fief.
                            </div>
                        </div>
                    </div>

                    <!-- Poisson Féodal -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-fish text-azure me-1"></i>Poisson Féodal</span>
                                    <span class="badge bg-azure text-white font-monospace fs-4" id="badge-val-starter-fish"><?= number_format((int)$starterFish) ?></span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterFish" name="starter_fish_slider" min="0" max="10000" step="25" value="<?= (int)$starterFish ?>" style="cursor:pointer; height:8px;">
                                    <input type="number" class="form-control text-center font-monospace fw-bold fs-4" style="width: 85px;" id="inputStarterFish" name="starter_fish" min="0" max="50000" value="<?= (int)$starterFish ?>">
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                Vivres pêchés pour dojo, caserne et négoce lacustre.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Trésor Monétaire & Bouclier d'Immunité -->
                <div class="row g-4 mb-4">
                    <!-- Pièces d'Or / Ryō -->
                    <div class="col-lg-6">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-coins text-warning me-1"></i>Trésor Monétaire (Pièces d'Or / Ryō)</span>
                                    <span class="badge bg-warning text-dark font-monospace fs-4" id="badge-val-starter-gold"><?= number_format((int)$starterGoldCoins) ?> Ryō</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterGold" name="starter_gold_slider" min="0" max="2000" step="10" value="<?= (int)$starterGoldCoins ?>" style="cursor:pointer; height:8px;">
                                    <div class="input-group" style="width: 120px;">
                                        <input type="number" class="form-control text-center font-monospace fw-bold fs-4" id="inputStarterGold" name="starter_gold_coins" min="0" max="10000" value="<?= (int)$starterGoldCoins ?>">
                                        <span class="input-group-text bg-light fw-bold text-warning px-2"><i class="fa-solid fa-coins"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                <i class="fa-solid fa-circle-info text-warning me-1"></i>Solde de pièces d'or disponible dès l'inscription pour les finitions instantanées, le marchand PNJ ou les bonus impériaux.
                            </div>
                        </div>
                    </div>

                    <!-- Durée de l'Immunité Débutant -->
                    <div class="col-lg-6">
                        <div class="card h-100 border bg-white shadow-xs p-3 d-flex flex-column justify-content-between" style="border-left: 4px solid #2fb344 !important;">
                            <div>
                                <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center mb-2">
                                    <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Durée du Bouclier d'Immunité Débutant</span>
                                    <span class="badge <?= $beginnerProtectionDays > 0 ? 'bg-success text-white' : 'bg-secondary text-white' ?> font-monospace fs-4" id="badge-val-starter-protection"><?= (int)$beginnerProtectionDays ?> jours</span>
                                </label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="range" class="form-range flex-grow-1" id="sliderStarterProtection" name="starter_protection_slider" min="0" max="60" step="1" value="<?= (int)$beginnerProtectionDays ?>" style="cursor:pointer; height:8px;">
                                    <div class="input-group" style="width: 120px;">
                                        <input type="number" class="form-control text-center font-monospace fw-bold fs-4" id="inputStarterProtection" name="beginner_protection_days" min="0" max="60" value="<?= (int)$beginnerProtectionDays ?>">
                                        <span class="input-group-text bg-light fw-bold text-success px-2">jours</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-secondary small mt-2">
                                <span class="d-block mb-1" id="label-protection-status-desc">
                                    <?php if ($beginnerProtectionDays > 0): ?>
                                        <i class="fa-solid fa-circle-check text-success me-1"></i>Trêve sacrée active : <strong><?= (int)$beginnerProtectionDays ?> jours</strong> de protection inviolable contre les assauts et raids ennemis.
                                    <?php else: ?>
                                        <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Bouclier désactivé : les nouveaux seigneurs peuvent être attaqués dès leur première minute de jeu.
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Simulateur d'Autonomie Féodale Immédiate -->
                <div class="card border-purple bg-purple bg-opacity-10 shadow-xs mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h5 class="m-0 text-purple fw-bold d-flex align-items-center gap-2">
                                <i class="fa-solid fa-calculator text-purple"></i>Simulateur d'Autonomie Féodale Immédiate
                            </h5>
                            <span class="badge bg-white text-purple border font-monospace">Capacités d'investissement dès le premier jour</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-chess-rook text-purple me-1"></i>Élévation du Tenshu :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-purple fw-bold" id="sim-tenshu-level">Niveau 1</span>
                                    </div>
                                    <div class="text-muted small mt-1" id="sim-tenshu-desc">Finançable directement sans attendre les récoltes</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-seedling text-success me-1"></i>Parcelles Rurales Niv 1 :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-success fw-bold" id="sim-plots-count">~12 parcelles</span>
                                    </div>
                                    <div class="text-muted small mt-1">Sur les 18 parcelles du domaine initial</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white border rounded shadow-xs h-100">
                                    <div class="text-secondary small fw-bold mb-1">
                                        <i class="fa-solid fa-shield-halved text-success me-1"></i>Trêve Débutant :
                                    </div>
                                    <div class="d-flex align-items-baseline gap-2">
                                        <span class="h2 m-0 font-monospace text-success fw-bold" id="sim-protection-days"><?= (int)$beginnerProtectionDays ?> jours</span>
                                    </div>
                                    <div class="text-muted small mt-1" id="sim-protection-date">Bouclier inviolable à l'inscription</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Bouton d'Enregistrement -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pt-3 border-top">
                    <div class="text-secondary small">
                        <i class="fa-solid fa-circle-check text-success me-1"></i>Ces paramètres seront immédiatement appliqués à tout nouveau compte joueur ou lors de la fondation d'un premier fief.
                    </div>
                    <button type="submit" class="btn btn-purple btn-lg px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer la Dotation &amp; l'Immunité
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 5 : TÉLÉMÉTRIE D'OCCUPATION DU MONDE & DENSITÉ SPATIALE     -->
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

    // ── Item 3 : Vitesses & Équilibrage du Monde Féodal ──
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
        if (m < 60) {
            return s > 0 ? (m + "m " + (s < 10 ? "0" : "") + s + "s") : (m + "m 00s");
        }
        const h = Math.floor(m / 60);
        const remM = m % 60;
        return h + "h " + (remM < 10 ? "0" : "") + remM + "m";
    }

    function updateSpeedSimulator() {
        const gSpeed = parseInt(iGame ? iGame.value : 5, 10) || 1;
        const rSpeed = parseInt(iRes ? iRes.value : 5, 10) || 1;
        const fSpeed = parseInt(iFleet ? iFleet.value : 5, 10) || 1;

        // Tenshu niv 10 base = 3600s
        const buildSec = Math.max(1, Math.round(3600 / gSpeed));
        if (simBuild) simBuild.innerText = formatDurationFr(buildSec);

        // Marche infanterie 10 tuiles base = 1800s
        const marchSec = Math.max(1, Math.round(1800 / fSpeed));
        if (simMarch) simMarch.innerText = formatDurationFr(marchSec);

        // Rizière niv 10 base = 250 koku/h
        const prod = 250 * rSpeed;
        if (simProd) simProd.innerText = formatNumberFr(prod) + " koku/h";
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
                if (val >= parseInt(slider.min, 10) && val <= parseInt(slider.max, 10)) {
                    slider.value = val;
                }
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
                if (val >= parseFloat(s.min) && val <= parseFloat(s.max)) {
                    s.value = val;
                }
                if (b) b.innerText = this.value + (suffix ? ' ' + suffix : '');
            }
        });
    }

    syncSimpleControl('sliderProtectionDays', 'inputProtectionDays', 'badge-val-protection-days', 'jours');
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
            b.classList.remove('btn-danger', 'shadow-sm');
            b.classList.add('btn-outline-secondary');
        });
        if (btnElem) {
            btnElem.classList.remove('btn-outline-secondary');
            btnElem.classList.add('btn-danger', 'shadow-sm');
        }
    };

    window.updateFamineSwitchBadges = function(checked) {
        const statusPill = document.getElementById('badge-famine-status-pill');
        const switchState = document.getElementById('badge-famine-switch-state');
        if (statusPill) {
            if (checked) {
                statusPill.className = 'badge bg-danger text-white font-monospace px-3 py-2 fs-5 shadow-xs';
                statusPill.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Famine Activée';
            } else {
                statusPill.className = 'badge bg-secondary text-white font-monospace px-3 py-2 fs-5 shadow-xs';
                statusPill.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i>Famine Désactivée';
            }
        }
        if (switchState) {
            if (checked) {
                switchState.className = 'badge bg-danger text-white font-monospace';
                switchState.innerText = 'En service';
            } else {
                switchState.className = 'badge bg-secondary text-white font-monospace';
                switchState.innerText = 'Inactif';
            }
        }
    };

    // ── Item 4 : Dotation Initiale & Immunité Débutant ──
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

        // Mise à jour header badges
        if (hStarterRes) hStarterRes.innerText = formatNumberFr(w) + " / " + formatNumberFr(s) + " / " + formatNumberFr(r) + " Matériaux";
        if (hStarterGold && iGold) hStarterGold.innerHTML = '<i class="fa-solid fa-coins text-warning me-1"></i>' + formatNumberFr(iGold.value) + " Ryō";
        if (hProt) hProt.innerHTML = '<i class="fa-solid fa-shield-halved text-success me-1"></i>' + pDays + " jours";

        // Tenshu finançable
        let tenshuLvl = "Niveau 0";
        if (w >= 3500 && s >= 2800 && r >= 2300) tenshuLvl = "Niveau 5+";
        else if (w >= 2000 && s >= 1600 && r >= 1300) tenshuLvl = "Niveau 4";
        else if (w >= 1100 && s >= 880 && r >= 720) tenshuLvl = "Niveau 3";
        else if (w >= 600 && s >= 480 && r >= 400) tenshuLvl = "Niveau 2";
        else if (w >= 120 && s >= 100 && r >= 80) tenshuLvl = "Niveau 1";
        if (simTenshu) simTenshu.innerText = tenshuLvl;

        // Parcelles niveau 1 finançables (coût moyen 250 total par parcelle)
        const totalMat = w + s + r;
        const plotsAffordable = Math.min(18, Math.floor(totalMat / 250));
        if (simPlots) simPlots.innerText = "~" + plotsAffordable + " parcelles";

        // Protection débutant
        if (simProtDays) simProtDays.innerText = pDays > 0 ? (pDays + " jours") : "Désactivée (0j)";
        if (simProtDate) {
            simProtDate.innerText = pDays > 0 ? "Bouclier inviolable pendant " + pDays + " jours" : "Aucun bouclier (vulnérable dès le départ)";
        }
        if (protStatusDesc) {
            if (pDays > 0) {
                protStatusDesc.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i>Trêve sacrée active : <strong>' + pDays + ' jours</strong> de protection inviolable contre les assauts et raids ennemis.';
            } else {
                protStatusDesc.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i>Bouclier désactivé : les nouveaux seigneurs peuvent être attaqués dès leur première minute de jeu.';
            }
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
                if (val >= parseInt(slider.min, 10) && val <= parseInt(slider.max, 10)) {
                    slider.value = val;
                }
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
        if (sWood) sWood.value = w;
        if (iWood) iWood.value = w;
        if (bWood) bWood.innerText = formatNumberFr(w);

        if (sStone) sStone.value = s;
        if (iStone) iStone.value = s;
        if (bStone) bStone.innerText = formatNumberFr(s);

        if (sRice) sRice.value = r;
        if (iRice) iRice.value = r;
        if (bRice) bRice.innerText = formatNumberFr(r);

        if (sFish) sFish.value = f;
        if (iFish) iFish.value = f;
        if (bFish) bFish.innerText = formatNumberFr(f);

        if (sGold) sGold.value = gold;
        if (iGold) iGold.value = gold;
        if (bGold) bGold.innerText = formatNumberFr(gold) + ' Ryō';

        if (sProt) sProt.value = prot;
        if (iProt) iProt.value = prot;
        if (bProt) {
            bProt.className = (prot > 0 ? 'badge bg-success text-white font-monospace fs-4' : 'badge bg-secondary text-white font-monospace fs-4');
            bProt.innerText = prot + ' jours';
        }

        updateStarterSimulator();

        document.querySelectorAll('.starter-preset-btn').forEach(function(b) {
            b.classList.remove('btn-purple', 'shadow-sm');
            b.classList.add('btn-outline-secondary');
        });
        if (btnElem) {
            btnElem.classList.remove('btn-outline-secondary');
            btnElem.classList.add('btn-purple', 'shadow-sm');
        }
    };

    updateStarterSimulator();
    updateSpeedSimulator();
});
</script>
