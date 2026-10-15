<?php
/**
 * Vue : Gestionnaire de Storyboard & Timeline Narrative (Game Studio)
 * Fichier : views/studio/narrative-designer/storyboard-manager.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../core/StoryboardEngine.php';

$storyboardEngine = new StoryboardEngine();
$allStoryboards = $storyboardEngine->getStoryboards();

// Sélection du storyboard actif (par ID ou premier disponible)
$requestedSbId = (int)($_GET['storyboard_id'] ?? ($allStoryboards[0]['id'] ?? 0));
$activeStoryboard = null;
if ($requestedSbId > 0) {
    $activeStoryboard = $storyboardEngine->getStoryboard($requestedSbId);
}
if (!$activeStoryboard && !empty($allStoryboards)) {
    $activeStoryboard = $storyboardEngine->getStoryboard((int)$allStoryboards[0]['id']);
}

// Données sérialisées pour le moteur JavaScript
$activeSbJson = json_encode($activeStoryboard, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$allSbJson = json_encode($allStoryboards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$galleryAssets = $storyboardEngine->getGalleryAssets();
$galleryJson = json_encode($galleryAssets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<style>
/* Styles de la Timeline Narrative & Drag and Drop */
.storyboard-timeline-container {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    color: #f8fafc;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.4);
}
.timeline-ruler {
    display: flex;
    justify-content: space-between;
    font-size: 0.72rem;
    font-family: var(--tblr-font-monospace);
    color: #94a3b8;
    border-bottom: 1px solid #334155;
    padding-bottom: 0.35rem;
    margin-bottom: 0.75rem;
}
.timeline-track {
    display: flex;
    gap: 0.35rem;
    height: 28px;
    background: #1e293b;
    border-radius: 6px;
    padding: 3px;
    overflow: hidden;
}
.timeline-block {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
    transition: filter 0.2s ease, transform 0.15s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 0 0.4rem;
    border: 1px solid rgba(255,255,255,0.15);
}
.timeline-block:hover {
    filter: brightness(1.2);
    transform: scaleY(1.05);
}
.timeline-block.active {
    background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
    box-shadow: 0 0 10px rgba(236, 72, 153, 0.6);
}

/* Cartes des Plans de la Timeline */
.shot-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    position: relative;
}
.shot-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 16px -2px rgba(0,0,0,0.08);
}
.shot-card.dragging {
    opacity: 0.45;
    transform: scale(0.97);
    border: 2px dashed #6366f1 !important;
}
.shot-card.drag-over {
    border-left: 4px solid #6366f1 !important;
    background-color: #f8fafc;
}
.shot-drag-handle {
    cursor: grab;
    padding: 0.4rem 0.6rem;
    color: #94a3b8;
    transition: color 0.15s ease;
}
.shot-drag-handle:hover {
    color: #4f46e5;
}
.shot-drag-handle:active {
    cursor: grabbing;
}
.shot-thumbnail-box {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    background: #111827;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.1);
}
.shot-thumbnail-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.shot-thumbnail-box:hover img {
    transform: scale(1.05);
}
.shot-thumb-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.6);
    opacity: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: opacity 0.2s ease;
}
.shot-thumbnail-box:hover .shot-thumb-overlay {
    opacity: 1;
}

/* Modale Lecteur Vidéo Plein Écran */
#storyboardPlayerModal .modal-content {
    background: #090d16;
    color: #f8fafc;
    border: 1px solid rgba(255,255,255,0.15);
}
.player-screen {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    max-height: 65vh;
    background: #000000;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.player-screen img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: opacity 0.4s ease, transform 0.5s ease;
}
.player-subtitles {
    position: absolute;
    bottom: 2rem;
    left: 5%;
    right: 5%;
    text-align: center;
    background: rgba(0, 0, 0, 0.75);
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-size: 1.15rem;
    font-weight: 600;
    color: #ffffff;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.15);
    text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}
.player-controls-bar {
    background: #111827;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 1px solid #1f2937;
}

/* Sélecteur d'illustrations du jeu */
.gallery-picker-grid {
    max-height: 55vh;
    overflow-y: auto;
}
.gallery-picker-item {
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 6px;
    overflow: hidden;
    transition: all 0.18s ease;
}
.gallery-picker-item:hover {
    border-color: #6366f1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- BARRE DE GESTION MULTI-STORYBOARDS (WORKSPACE & PROJETS)           -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- 1. Sélecteur de Storyboard Actif -->
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 460px;">
                <span class="text-secondary small fw-bold text-uppercase d-none d-sm-inline">
                    <i class="fa-solid fa-folder-open text-purple me-1"></i> Projet :
                </span>
                <select id="storyboardSelect" class="form-select form-select-sm fw-bold border-purple" onchange="switchStoryboard(this.value)">
                    <?php foreach ($allStoryboards as $sb): ?>
                        <option value="<?= $sb['id'] ?>" <?= ($activeStoryboard && $activeStoryboard['id'] === $sb['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sb['title']) ?> (<?= $sb['shots_count'] ?> plans &bull; <?= $sb['total_duration'] ?>s)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Badges du Storyboard Courant -->
            <?php if ($activeStoryboard): ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-purple-lt text-purple">
                    <i class="fa-solid fa-video me-1"></i><?= ucfirst(htmlspecialchars($activeStoryboard['video_type'])) ?>
                </span>
                <span class="badge bg-secondary-lt text-secondary">
                    <i class="fa-solid fa-expand me-1"></i><?= htmlspecialchars($activeStoryboard['aspect_ratio']) ?>
                </span>
                <span class="badge bg-dark-lt text-dark font-monospace" id="sbHeaderDurationBadge">
                    <i class="fa-regular fa-clock me-1"></i><?= $activeStoryboard['total_duration'] ?>s / <?= $activeStoryboard['target_duration'] ?>s
                </span>
            </div>
            <?php endif; ?>

            <!-- 3. Actions Rapides de Projet -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button type="button" class="btn btn-sm btn-purple shadow-sm d-flex align-items-center gap-1" onclick="openNewStoryboardModal()">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nouveau Storyboard</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical me-1"></i> Projet
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" onclick="openEditStoryboardModal()">
                                <i class="fa-solid fa-sliders text-primary"></i> Paramètres &amp; Métadonnées
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" onclick="duplicateCurrentStoryboard()">
                                <i class="fa-solid fa-clone text-success"></i> Dupliquer le projet
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" onclick="exportMarkdownScript()">
                                <i class="fa-solid fa-file-arrow-down text-info"></i> Exporter le Script (Markdown)
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2" onclick="openAiPromptsModal()">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple"></i> Générer Prompts IA Vidéo
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 text-danger" onclick="deleteCurrentStoryboard()">
                                <i class="fa-solid fa-trash-can"></i> Supprimer ce storyboard
                            </button>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-sm btn-success d-flex align-items-center gap-1 shadow-sm" onclick="startStoryboardPlayer()">
                    <i class="fa-solid fa-play"></i>
                    <span class="d-none d-md-inline">Lire Storyboard</span>
                </button>
            </div>
        </div>

        <?php if ($activeStoryboard && !empty($activeStoryboard['description'])): ?>
        <div class="mt-2 pt-2 border-top text-secondary small d-flex align-items-center justify-content-between">
            <div>
                <i class="fa-solid fa-quote-left text-purple opacity-50 me-1"></i>
                <span id="sbDescriptionText"><?= htmlspecialchars($activeStoryboard['description']) ?></span>
            </div>
            <?php if (!empty($activeStoryboard['audio_track_title'])): ?>
            <div class="font-monospace text-muted small ms-2">
                <i class="fa-solid fa-music text-warning me-1"></i><?= htmlspecialchars($activeStoryboard['audio_track_title']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- CARTES DE MÉTRIQUES & ANALYSE DU RYTHME NARRATIF (EN TEMPS RÉEL)    -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="row row-cards mb-3 g-2">
    <!-- 1. Durée Totale Cumulée vs Durée Cible -->
    <div class="col-6 col-sm-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body p-2 p-md-3 d-flex align-items-center gap-3">
                <span class="avatar bg-indigo-lt text-indigo rounded">
                    <i class="fa-solid fa-stopwatch fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 text-dark font-monospace lh-1" id="kpiTotalDuration">
                        <?= $activeStoryboard ? $activeStoryboard['total_duration'] : '0.0' ?>s
                    </div>
                    <div class="text-secondary small mt-1" id="kpiDurationSubtitle">
                        Cible : <?= $activeStoryboard ? $activeStoryboard['target_duration'] : 20 ?>s
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Nombre de Plans & Découpage -->
    <div class="col-6 col-sm-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body p-2 p-md-3 d-flex align-items-center gap-3">
                <span class="avatar bg-purple-lt text-purple rounded">
                    <i class="fa-solid fa-layer-group fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 text-dark font-monospace lh-1" id="kpiTotalShots">
                        <?= $activeStoryboard ? $activeStoryboard['shots_count'] : 0 ?>
                    </div>
                    <div class="text-secondary small mt-1">Plans Séquencés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Rythme Moyen par Plan -->
    <div class="col-6 col-sm-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body p-2 p-md-3 d-flex align-items-center gap-3">
                <span class="avatar bg-teal-lt text-teal rounded">
                    <i class="fa-solid fa-gauge-high fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 text-dark font-monospace lh-1" id="kpiAvgShotDuration">
                        <?= $activeStoryboard ? ($activeStoryboard['metrics']['average_shot_duration'] ?? '0.0') : '0.0' ?>s
                    </div>
                    <div class="text-secondary small mt-1">Cadence / Plan</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Débit Vocal Voix Off (Mots par Minute) -->
    <div class="col-6 col-sm-3">
        <div class="card card-sm border-0 shadow-sm">
            <div class="card-body p-2 p-md-3 d-flex align-items-center gap-3">
                <span class="avatar bg-warning-lt text-warning rounded">
                    <i class="fa-solid fa-microphone-lines fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 text-dark font-monospace lh-1" id="kpiWpm">
                        <?= $activeStoryboard ? ($activeStoryboard['metrics']['wpm'] ?? 0) : 0 ?> <span class="fs-5 text-muted">WPM</span>
                    </div>
                    <div class="text-secondary small mt-1" id="kpiWpmStatus">
                        Rythme Narration
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- RÈGLE TEMPORELLE & TIMELINE INTERACTIVE HORIZONTALE                -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="storyboard-timeline-container mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-bold small text-uppercase text-secondary d-flex align-items-center gap-2">
            <i class="fa-solid fa-film text-purple"></i>
            <span>Timeline Séquentielle</span>
            <span class="badge bg-dark-lt text-white font-monospace" id="timelineDurationLabel">00:00 &rarr; <?= $activeStoryboard ? $storyboardEngine->formatTimecode((float)$activeStoryboard['total_duration']) : '00:00.0' ?></span>
        </div>
        <div class="small text-muted">
            <i class="fa-solid fa-hand-pointer me-1"></i> Cliquez sur un bloc ou glissez-déposez les cartes ci-dessous
        </div>
    </div>

    <!-- Règle Temporelle avec Repères Numériques -->
    <div class="timeline-ruler" id="timelineRuler">
        <span>00:00.0</span>
        <span>00:05.0</span>
        <span>00:10.0</span>
        <span>00:15.0</span>
        <span>00:20.0</span>
        <span>00:25.0</span>
        <span>00:30.0</span>
    </div>

    <!-- Piste de Blocs Horizontaux Proportionnels -->
    <div class="timeline-track" id="timelineTrack">
        <!-- Rendu dynamique par JS -->
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- CONTENEUR DES CARTES DE PLANS (DRAG & DROP NATIF)                  -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="card-title text-dark m-0 d-flex align-items-center gap-2">
        <i class="fa-solid fa-clapperboard text-purple"></i>
        <span>Découpage des Scènes &amp; Plans</span>
        <span class="badge bg-secondary-lt text-secondary rounded-pill font-monospace" id="shotsCountBadge">
            <?= $activeStoryboard ? $activeStoryboard['shots_count'] : 0 ?>
        </span>
    </h3>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="addNewShot()">
            <i class="fa-solid fa-plus"></i> Ajouter un plan
        </button>
        <button type="button" class="btn btn-sm btn-primary shadow-sm d-flex align-items-center gap-1" onclick="saveStoryboardShots()">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
    </div>
</div>

<div class="row g-3" id="shotsContainer">
    <!-- Les cartes de plans seront injectées et gérées dynamiquement ici par JS -->
</div>

<div class="mt-4 mb-5 text-center">
    <button type="button" class="btn btn-outline-secondary border-dashed px-4 py-2" onclick="addNewShot()">
        <i class="fa-solid fa-plus me-1 text-purple"></i> Insérer un nouveau plan dans la timeline
    </button>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 1 : SÉLECTEUR D'ACTIFS DE LA GALERIE D'ART FÉODALE (238 IMG) -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="assetPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-danger fs-3"><i class="fa-solid fa-palette"></i></span>
                    <h5 class="modal-title m-0 text-white">Choisir une Illustration &bull; Galerie d'Art OpenShogun</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <!-- Filtres et Recherche dans la Galerie -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="input-icon flex-grow-1" style="max-width: 320px;">
                        <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="assetSearchInput" class="form-control form-control-sm" placeholder="Rechercher par nom ou thème..." oninput="filterAssets()">
                    </div>
                    <div class="btn-group btn-group-sm flex-wrap" role="group" id="assetCategoryButtons">
                        <button type="button" class="btn btn-outline-secondary active" onclick="filterAssetCategory('all', this)">Toutes</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('buildings', this)">Bâtiments</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('carrousel', this)">Panoramas</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('castles', this)">Châteaux</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('clans', this)">Clans</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('events', this)">Événements</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('items', this)">Objets</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('resources', this)">Ressources</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('tiles', this)">Tuiles</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterAssetCategory('units', this)">Unités</button>
                    </div>
                </div>

                <!-- Grille d'actifs -->
                <div class="row g-2 gallery-picker-grid" id="assetPickerGrid">
                    <!-- Injecté par JS -->
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <span class="small text-muted" id="assetPickerStatus">238 illustrations répertoriées</span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 2 : STORYBOARD PLAYER (VISIONNEUSE CINÉMATIQUE PLEIN ÉCRAN) -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="storyboardPlayerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-purple rounded-pill px-2 py-1"><i class="fa-solid fa-play me-1"></i> Mode Lecteur</span>
                    <h5 class="modal-title m-0 text-white fw-bold text-truncate" id="playerModalTitle" style="max-width: 50vw;">Trailer</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark-lt text-white font-monospace" id="playerTimecodeBadge">00:00.0 / 00:17.0</span>
                    <button type="button" class="btn-close btn-close-white" onclick="stopStoryboardPlayer()" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="modal-body p-2 p-md-3">
                <div class="player-screen">
                    <img id="playerScreenImg" src="" alt="Plan Cinématique">
                    <div class="player-subtitles" id="playerSubtitles">
                        Sous-titres de voix off...
                    </div>
                </div>

                <!-- Contrôles de lecture -->
                <div class="player-controls-bar mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-dark text-white border-secondary" onclick="playerPrevShot()" title="Plan précédent">
                            <i class="fa-solid fa-backward-step"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-purple px-3 shadow" id="playerPlayPauseBtn" onclick="playerTogglePlay()">
                            <i class="fa-solid fa-pause me-1"></i> Pause
                        </button>
                        <button type="button" class="btn btn-sm btn-dark text-white border-secondary" onclick="playerNextShot()" title="Plan suivant">
                            <i class="fa-solid fa-forward-step"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-dark text-white border-secondary" onclick="playerRestart()" title="Recommencer depuis le début">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>

                    <div class="flex-grow-1 mx-3" style="max-width: 450px;">
                        <div class="progress progress-sm" style="height: 6px; background:#1f2937;">
                            <div class="progress-bar bg-purple" id="playerProgressBar" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <div class="small text-secondary font-monospace" id="playerShotInfo">
                        Plan 1 / 4 &bull; 4.5s
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 3 : GÉNÉRATEUR DE PROMPTS IA VIDÉO (RUNWAY / SORA / KLING)  -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="aiPromptsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-purple fs-3"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <h5 class="modal-title m-0 text-white">Prompts IA Vidéo &bull; Runway Gen-3 / Sora / Kling / Luma</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 small d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-circle-info fs-3"></i>
                    <div>
                        Ces prompts sont rédigés selon la charte visuelle cinématographique d'OpenShogun (ambiance féodale Sengoku, éclairage golden hour, 4K 24fps, objectif 35mm).
                    </div>
                </div>

                <!-- Master Prompt Global -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold text-dark m-0">Master Prompt Global (Plan Continu)</label>
                        <button type="button" class="btn btn-sm btn-outline-purple" onclick="copyToClipboard('aiMasterPromptText', this)">
                            <i class="fa-solid fa-copy me-1"></i> Copier
                        </button>
                    </div>
                    <textarea id="aiMasterPromptText" class="form-control font-monospace small bg-light" rows="3" readonly></textarea>
                </div>

                <!-- Prompts Plan par Plan -->
                <label class="form-label fw-bold text-dark mb-2">Prompts Découpés par Plan (Multi-Shot)</label>
                <div id="aiShotsPromptsContainer" class="d-flex flex-column gap-3">
                    <!-- Injecté par JS -->
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE 4 : NOUVEAU STORYBOARD / ÉDITION DES PARAMÈTRES             -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="storyboardMetaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-purple text-white py-2 d-flex justify-content-between align-items-center">
                <h5 class="modal-title m-0 text-white" id="sbMetaModalTitle">Nouveau Storyboard</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-3">
                <form id="sbMetaForm" onsubmit="handleMetaFormSubmit(event)">
                    <input type="hidden" id="sbMetaId" value="0">
                    <div class="mb-3">
                        <label class="form-label required">Titre du Projet</label>
                        <input type="text" id="sbMetaTitle" class="form-control" required placeholder="Ex: Trailer Marché Féodal 17s">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Type de Vidéo</label>
                            <select id="sbMetaType" class="form-select">
                                <option value="trailer">Trailer Officiel</option>
                                <option value="gameplay_demo">Démo de Gameplay</option>
                                <option value="event_teaser">Teaser d'Événement</option>
                                <option value="quest_cinematic">Cinématique de Quête</option>
                                <option value="social_clip">Clip Court Réseaux</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Durée Cible (secondes)</label>
                            <input type="number" id="sbMetaTargetDuration" class="form-control font-monospace" min="5" max="300" value="20" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Ratio d'Image</label>
                            <select id="sbMetaAspectRatio" class="form-select">
                                <option value="16:9">16:9 (Paysage Cinéma)</option>
                                <option value="9:16">9:16 (Vertical Mobile)</option>
                                <option value="1:1">1:1 (Carré Réseaux)</option>
                                <option value="4:3">4:3 (Classique)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Piste Audio Suggérée</label>
                            <input type="text" id="sbMetaAudioTrack" class="form-control" placeholder="Ex: Taiko & Shakuhachi">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Synopsis / Description Narrative</label>
                        <textarea id="sbMetaDescription" class="form-control" rows="3" placeholder="Résumé de l'arc dramatique et de l'ambiance du teaser..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-purple" id="sbMetaSubmitBtn">Créer le projet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SCRIPT DU MOTEUR JAVASCRIPT : TIMELINE, DRAG & DROP ET PLAYER     -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
(function() {
    // Données initialisées par PHP
    let currentStoryboard = <?= $activeSbJson ?: 'null' ?>;
    let allStoryboards = <?= $allSbJson ?: '[]' ?>;
    const galleryAssets = <?= $galleryJson ?: '[]' ?>;

    // État du Drag and Drop
    let draggedIndex = null;
    let activeEditingShotIndex = null;

    // État du Storyboard Player
    let playerTimer = null;
    let playerCurrentTime = 0.0;
    let playerTotalDuration = 0.0;
    let isPlaying = false;
    let playerShots = [];

    // Modales Bootstrap
    let metaModal = null;
    let assetModal = null;
    let playerModal = null;
    let aiModal = null;

    function init() {
        if (typeof bootstrap !== 'undefined') {
            metaModal = new bootstrap.Modal(document.getElementById('storyboardMetaModal'));
            assetModal = new bootstrap.Modal(document.getElementById('assetPickerModal'));
            playerModal = new bootstrap.Modal(document.getElementById('storyboardPlayerModal'));
            aiModal = new bootstrap.Modal(document.getElementById('aiPromptsModal'));
        }

        renderStoryboardUI();
        initAssetPickerGrid();
    }

    // Rendu global de l'interface
    function renderStoryboardUI() {
        if (!currentStoryboard) {
            document.getElementById('shotsContainer').innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Aucun storyboard sélectionné. Cliquez sur « Nouveau Storyboard » pour démarrer.</p>
                </div>
            `;
            return;
        }

        renderMetrics();
        renderTimelineTrack();
        renderShotsList();
    }

    // 1. Calcul et rendu des métriques & KPIs
    function renderMetrics() {
        const shots = currentStoryboard.shots || [];
        let totalDur = 0.0;
        let totalWords = 0;

        shots.forEach(s => {
            totalDur += parseFloat(s.duration) || 4.0;
            const vo = (s.voiceover_text || '').trim();
            if (vo) totalWords += vo.split(/\s+/).filter(Boolean).length;
        });

        totalDur = Math.round(totalDur * 10) / 10;
        const targetDur = parseInt(currentStoryboard.target_duration, 10) || 20;
        const avgDur = shots.length > 0 ? Math.round((totalDur / shots.length) * 10) / 10 : 0;
        const wpm = (totalDur > 0) ? Math.round(totalWords / (totalDur / 60)) : 0;

        currentStoryboard.total_duration = totalDur;

        // Mise à jour des éléments DOM
        const kpiDurEl = document.getElementById('kpiTotalDuration');
        if (kpiDurEl) kpiDurEl.textContent = totalDur + 's';

        const kpiSubEl = document.getElementById('kpiDurationSubtitle');
        if (kpiSubEl) {
            const diff = Math.round((totalDur - targetDur) * 10) / 10;
            if (diff === 0) {
                kpiSubEl.innerHTML = `<span class="text-success"><i class="fa-solid fa-check me-1"></i>Parfait (Cible ${targetDur}s)</span>`;
            } else if (diff > 0) {
                kpiSubEl.innerHTML = `<span class="text-warning">+${diff}s vs Cible (${targetDur}s)</span>`;
            } else {
                kpiSubEl.innerHTML = `<span class="text-info">${diff}s vs Cible (${targetDur}s)</span>`;
            }
        }

        const kpiShotsEl = document.getElementById('kpiTotalShots');
        if (kpiShotsEl) kpiShotsEl.textContent = shots.length;

        const kpiAvgEl = document.getElementById('kpiAvgShotDuration');
        if (kpiAvgEl) kpiAvgEl.textContent = avgDur + 's';

        const kpiWpmEl = document.getElementById('kpiWpm');
        if (kpiWpmEl) kpiWpmEl.innerHTML = `${wpm} <span class="fs-5 text-muted">WPM</span>`;

        const kpiWpmStatusEl = document.getElementById('kpiWpmStatus');
        if (kpiWpmStatusEl) {
            if (wpm >= 110 && wpm <= 150) {
                kpiWpmStatusEl.innerHTML = '<span class="text-success fw-bold">Débit Optimal</span>';
            } else if (wpm > 150) {
                kpiWpmStatusEl.innerHTML = '<span class="text-danger fw-bold">Voix trop rapide</span>';
            } else if (wpm > 0) {
                kpiWpmStatusEl.innerHTML = '<span class="text-warning fw-bold">Rythme posé</span>';
            } else {
                kpiWpmStatusEl.textContent = 'Sans voix off';
            }
        }

        const sbBadge = document.getElementById('sbHeaderDurationBadge');
        if (sbBadge) sbBadge.innerHTML = `<i class="fa-regular fa-clock me-1"></i>${totalDur}s / ${targetDur}s`;

        const shotsBadge = document.getElementById('shotsCountBadge');
        if (shotsBadge) shotsBadge.textContent = shots.length;
    }

    // 2. Rendu de la règle de timeline
    function renderTimelineTrack() {
        const track = document.getElementById('timelineTrack');
        if (!track) return;
        track.innerHTML = '';

        const shots = currentStoryboard.shots || [];
        const totalDur = currentStoryboard.total_duration || 1;

        shots.forEach((shot, index) => {
            const dur = parseFloat(shot.duration) || 4.0;
            const pct = Math.max(5, (dur / totalDur) * 100);

            const block = document.createElement('div');
            block.className = 'timeline-block';
            block.style.width = pct + '%';
            block.title = `${shot.title} (${dur}s)`;
            block.innerHTML = `<span>P${index + 1}: ${dur}s</span>`;
            block.onclick = () => scrollToShot(index);

            track.appendChild(block);
        });

        const label = document.getElementById('timelineDurationLabel');
        if (label) {
            label.innerHTML = `00:00.0 &rarr; ${formatTimecode(totalDur)}`;
        }
    }

    // 3. Rendu de la liste de cartes de plans avec Drag & Drop
    function renderShotsList() {
        const container = document.getElementById('shotsContainer');
        if (!container) return;
        container.innerHTML = '';

        const shots = currentStoryboard.shots || [];
        let cumulatedTime = 0.0;

        shots.forEach((shot, index) => {
            const dur = parseFloat(shot.duration) || 4.0;
            const startTime = cumulatedTime;
            const endTime = cumulatedTime + dur;
            cumulatedTime = endTime;

            const cardCol = document.createElement('div');
            cardCol.className = 'col-12 col-md-6 col-xl-4 shot-item-col';
            cardCol.id = `shotCard-${index}`;
            cardCol.setAttribute('data-index', index);

            cardCol.innerHTML = `
                <div class="card shot-card shadow-sm h-100" draggable="true">
                    <!-- En-tête de carte avec Drag Handle et Timecode -->
                    <div class="card-header p-2 bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-1">
                            <span class="shot-drag-handle" title="Glisser pour réorganiser l'ordre des plans">
                                <i class="fa-solid fa-grip-vertical"></i>
                            </span>
                            <span class="badge bg-purple text-white fw-bold">Plan ${index + 1}</span>
                            <span class="badge bg-dark-lt text-dark font-monospace small">
                                ${formatTimecode(startTime)} &rarr; ${formatTimecode(endTime)}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-ghost-danger rounded-circle" onclick="deleteShot(${index})" title="Supprimer ce plan">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Vignette Image avec Sélecteur Modal -->
                    <div class="p-2 pb-0">
                        <div class="shot-thumbnail-box cursor-pointer" onclick="openAssetPicker(${index})">
                            <img src="${escapeHtml(shot.image_url)}" alt="${escapeHtml(shot.title)}" id="shotImg-${index}">
                            <div class="shot-thumb-overlay">
                                <button type="button" class="btn btn-sm btn-light shadow-sm">
                                    <i class="fa-solid fa-image me-1"></i> Choisir illustration
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Corps de la carte : Titre, Voix off, Durée, Cadrage -->
                    <div class="card-body p-2 pt-2 d-flex flex-column gap-2">
                        <!-- Titre du plan -->
                        <div>
                            <input type="text" class="form-control form-control-sm fw-bold border-0 bg-light" 
                                   value="${escapeHtml(shot.title)}" 
                                   placeholder="Titre du plan..."
                                   onchange="updateShotField(${index}, 'title', this.value)">
                        </div>

                        <!-- Voix off / Dialogue -->
                        <div>
                            <label class="form-label text-secondary small fw-bold mb-1">
                                <i class="fa-solid fa-microphone-lines text-warning me-1"></i> Voix Off / Dialogue :
                            </label>
                            <textarea class="form-control form-control-sm" rows="2" 
                                      placeholder="Texte lu par le narrateur..."
                                      onchange="updateShotField(${index}, 'voiceover_text', this.value)">${escapeHtml(shot.voiceover_text || '')}</textarea>
                        </div>

                        <!-- Note de mise en scène -->
                        <div>
                            <label class="form-label text-secondary small fw-bold mb-1">
                                <i class="fa-solid fa-film text-purple me-1"></i> Description Visuelle :
                            </label>
                            <textarea class="form-control form-control-sm" rows="2" 
                                      placeholder="Ce qui se passe à l'écran..."
                                      onchange="updateShotField(${index}, 'narrative_text', this.value)">${escapeHtml(shot.narrative_text || '')}</textarea>
                        </div>

                        <!-- Paramètres techniques : Durée, Caméra, Transition -->
                        <div class="row g-1 align-items-center mt-1 pt-1 border-top">
                            <!-- Durée -->
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0" onclick="adjustDuration(${index}, -0.5)">-</button>
                                    <input type="number" step="0.5" min="0.5" max="30" class="form-control form-control-sm text-center font-monospace fw-bold px-1" 
                                           value="${dur}" 
                                           onchange="updateShotField(${index}, 'duration', parseFloat(this.value) || 4.0)">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-1 py-0" onclick="adjustDuration(${index}, +0.5)">+</button>
                                    <span class="small text-muted">sec</span>
                                </div>
                            </div>
                            <!-- Cadrage -->
                            <div class="col-6">
                                <select class="form-select form-select-sm small" onchange="updateShotField(${index}, 'camera_movement', this.value)">
                                    <option value="Plan large d'ensemble" ${shot.camera_movement === "Plan large d'ensemble" ? 'selected' : ''}>Plan large d'ensemble</option>
                                    <option value="Travelling avant fluide" ${shot.camera_movement === 'Travelling avant fluide' ? 'selected' : ''}>Travelling avant fluide</option>
                                    <option value="Travelling latéral" ${shot.camera_movement === 'Travelling latéral' ? 'selected' : ''}>Travelling latéral</option>
                                    <option value="Panoramique horizontal" ${shot.camera_movement === 'Panoramique horizontal' ? 'selected' : ''}>Panoramique horizontal</option>
                                    <option value="Gros plan dramatique" ${shot.camera_movement === 'Gros plan dramatique' ? 'selected' : ''}>Gros plan dramatique</option>
                                    <option value="Vue aérienne (grue/drone)" ${shot.camera_movement === 'Vue aérienne (grue/drone)' ? 'selected' : ''}>Vue aérienne (drone)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Effets audio & Transition -->
                        <div class="row g-1 align-items-center">
                            <div class="col-7">
                                <input type="text" class="form-control form-control-sm small" 
                                       placeholder="Sons : Taiko, koto, foule..." 
                                       value="${escapeHtml(shot.sound_fx || '')}"
                                       onchange="updateShotField(${index}, 'sound_fx', this.value)">
                            </div>
                            <div class="col-5">
                                <select class="form-select form-select-sm small" onchange="updateShotField(${index}, 'transition_type', this.value)">
                                    <option value="cut" ${shot.transition_type === 'cut' ? 'selected' : ''}>Cut franc</option>
                                    <option value="crossfade" ${shot.transition_type === 'crossfade' ? 'selected' : ''}>Fondu enchaîné</option>
                                    <option value="fade_black" ${shot.transition_type === 'fade_black' ? 'selected' : ''}>Fondu au noir</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Configuration des événements Drag & Drop natifs HTML5
            attachDragAndDropEvents(cardCol, index);
            container.appendChild(cardCol);
        });
    }

    // Attachement des écouteurs Drag & Drop natifs
    function attachDragAndDropEvents(element, index) {
        const card = element.querySelector('.shot-card');

        card.addEventListener('dragstart', (e) => {
            draggedIndex = index;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', index);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            document.querySelectorAll('.shot-card').forEach(el => el.classList.remove('drag-over'));
            draggedIndex = null;
        });

        card.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            card.classList.add('drag-over');
        });

        card.addEventListener('dragleave', () => {
            card.classList.remove('drag-over');
        });

        card.addEventListener('drop', (e) => {
            e.preventDefault();
            card.classList.remove('drag-over');
            const targetIndex = parseInt(element.getAttribute('data-index'), 10);

            if (draggedIndex !== null && draggedIndex !== targetIndex) {
                // Réorganisation du tableau de plans
                const movedShot = currentStoryboard.shots.splice(draggedIndex, 1)[0];
                currentStoryboard.shots.splice(targetIndex, 0, movedShot);

                // Mise à jour de l'UI et auto-sauvegarde
                renderStoryboardUI();
                autoSaveShotsDebounced();
            }
        });
    }

    // Scroll automatique vers une carte depuis la timeline
    function scrollToShot(index) {
        const el = document.getElementById(`shotCard-${index}`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.querySelector('.shot-card').style.borderColor = '#6366f1';
            setTimeout(() => { el.querySelector('.shot-card').style.borderColor = ''; }, 1200);
        }
    }

    // Mise à jour de champ sur un plan
    window.updateShotField = function(index, field, value) {
        if (!currentStoryboard.shots || !currentStoryboard.shots[index]) return;
        currentStoryboard.shots[index][field] = value;
        renderMetrics();
        renderTimelineTrack();
        autoSaveShotsDebounced();
    };

    // Ajustement rapide de la durée (+/- 0.5s)
    window.adjustDuration = function(index, delta) {
        if (!currentStoryboard.shots || !currentStoryboard.shots[index]) return;
        let cur = parseFloat(currentStoryboard.shots[index].duration) || 4.0;
        cur = Math.max(0.5, Math.min(60.0, Math.round((cur + delta) * 10) / 10));
        currentStoryboard.shots[index].duration = cur;
        renderStoryboardUI();
        autoSaveShotsDebounced();
    };

    // Ajout d'un nouveau plan
    window.addNewShot = function() {
        if (!currentStoryboard) return;
        if (!currentStoryboard.shots) currentStoryboard.shots = [];

        const newIndex = currentStoryboard.shots.length;
        currentStoryboard.shots.push({
            order_index: newIndex,
            title: `Plan ${newIndex + 1} : Nouvelle Scène`,
            image_url: '/public/assets/buildings/building_tenshu.jpg',
            narrative_text: 'Description visuelle de la nouvelle scène...',
            voiceover_text: 'Texte lu en voix off...',
            camera_movement: 'Plan large d\'ensemble',
            sound_fx: 'Ambiance du fief',
            duration: 4.0,
            transition_type: 'cut'
        });

        renderStoryboardUI();
        autoSaveShotsDebounced();
        scrollToShot(newIndex);
    };

    // Suppression d'un plan
    window.deleteShot = function(index) {
        if (!currentStoryboard.shots || !currentStoryboard.shots[index]) return;
        if (!confirm(`Supprimer le plan ${index + 1} ?`)) return;

        currentStoryboard.shots.splice(index, 1);
        renderStoryboardUI();
        autoSaveShotsDebounced();
    };

    // Auto-sauvegarde temporisée (Debounced)
    let autoSaveTimeout = null;
    function autoSaveShotsDebounced() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            saveStoryboardShots(false);
        }, 800);
    }

    // Sauvegarde en base de données / API
    window.saveStoryboardShots = function(showFeedback = true) {
        if (!currentStoryboard || !currentStoryboard.id) return;

        fetch('/api/storyboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_shots',
                storyboard_id: currentStoryboard.id,
                shots: currentStoryboard.shots
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && showFeedback) {
                showToast('Timeline enregistrée avec succès !', 'success');
            }
        })
        .catch(err => {
            console.error('Erreur sauvegarde storyboard:', err);
        });
    };

    // Bascule de Storyboard actif
    window.switchStoryboard = function(id) {
        window.location.href = `?page=gamestudio&tab=narrative_storyboard&storyboard_id=${id}`;
    };

    // Modale Nouveau Storyboard
    window.openNewStoryboardModal = function() {
        document.getElementById('sbMetaId').value = 0;
        document.getElementById('sbMetaTitle').value = '';
        document.getElementById('sbMetaDescription').value = '';
        document.getElementById('sbMetaTargetDuration').value = 20;
        document.getElementById('sbMetaAudioTrack').value = '';
        document.getElementById('sbMetaModalTitle').textContent = 'Nouveau Storyboard';
        document.getElementById('sbMetaSubmitBtn').textContent = 'Créer le projet';
        if (metaModal) metaModal.show();
    };

    // Modale Édition Métadonnées
    window.openEditStoryboardModal = function() {
        if (!currentStoryboard) return;
        document.getElementById('sbMetaId').value = currentStoryboard.id;
        document.getElementById('sbMetaTitle').value = currentStoryboard.title;
        document.getElementById('sbMetaDescription').value = currentStoryboard.description || '';
        document.getElementById('sbMetaType').value = currentStoryboard.video_type || 'trailer';
        document.getElementById('sbMetaTargetDuration').value = currentStoryboard.target_duration || 20;
        document.getElementById('sbMetaAspectRatio').value = currentStoryboard.aspect_ratio || '16:9';
        document.getElementById('sbMetaAudioTrack').value = currentStoryboard.audio_track_title || '';
        document.getElementById('sbMetaModalTitle').textContent = 'Paramètres du Projet';
        document.getElementById('sbMetaSubmitBtn').textContent = 'Enregistrer les modifications';
        if (metaModal) metaModal.show();
    };

    // Soumission formulaire métadonnées
    window.handleMetaFormSubmit = function(e) {
        e.preventDefault();
        const id = parseInt(document.getElementById('sbMetaId').value, 10);
        const isNew = (id === 0);

        const payload = {
            action: isNew ? 'create' : 'update',
            id: id,
            data: {
                title: document.getElementById('sbMetaTitle').value,
                description: document.getElementById('sbMetaDescription').value,
                video_type: document.getElementById('sbMetaType').value,
                target_duration: parseInt(document.getElementById('sbMetaTargetDuration').value, 10),
                aspect_ratio: document.getElementById('sbMetaAspectRatio').value,
                audio_track_title: document.getElementById('sbMetaAudioTrack').value,
            }
        };

        fetch('/api/storyboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.storyboard) {
                if (metaModal) metaModal.hide();
                window.location.href = `?page=gamestudio&tab=narrative_storyboard&storyboard_id=${data.storyboard.id}`;
            } else {
                alert(data.error || 'Erreur lors de l\'enregistrement.');
            }
        });
    };

    // Duplication de storyboard
    window.duplicateCurrentStoryboard = function() {
        if (!currentStoryboard) return;
        if (!confirm(`Dupliquer le projet "${currentStoryboard.title}" ?`)) return;

        fetch('/api/storyboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'duplicate', id: currentStoryboard.id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.storyboard) {
                window.location.href = `?page=gamestudio&tab=narrative_storyboard&storyboard_id=${data.storyboard.id}`;
            }
        });
    };

    // Suppression de storyboard
    window.deleteCurrentStoryboard = function() {
        if (!currentStoryboard) return;
        if (!confirm(`Êtes-vous certain de vouloir supprimer définitivement "${currentStoryboard.title}" ?`)) return;

        fetch('/api/storyboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: currentStoryboard.id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = '?page=gamestudio&tab=narrative_storyboard';
            }
        });
    };

    // Export Markdown
    window.exportMarkdownScript = function() {
        if (!currentStoryboard) return;
        fetch(`/api/storyboard.php?action=export_markdown&id=${currentStoryboard.id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.markdown) {
                    const blob = new Blob([data.markdown], { type: 'text/markdown;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `storyboard_${currentStoryboard.slug || 'script'}.md`;
                    a.click();
                    URL.revokeObjectURL(url);
                }
            });
    };

    // Modale Génération Prompts IA
    window.openAiPromptsModal = function() {
        if (!currentStoryboard) return;
        fetch(`/api/storyboard.php?action=ai_prompts&id=${currentStoryboard.id}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    document.getElementById('aiMasterPromptText').value = d.master_prompt || '';

                    const container = document.getElementById('aiShotsPromptsContainer');
                    container.innerHTML = '';
                    (d.shots || []).forEach(s => {
                        const item = document.createElement('div');
                        item.className = 'p-2 bg-light border rounded';
                        item.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-dark">Plan ${s.index} : ${escapeHtml(s.title)} (${s.duration}s &bull; ${escapeHtml(s.camera)})</span>
                                <button type="button" class="btn btn-sm btn-outline-purple py-0 px-2" onclick="copyTextDirect(\`${escapeHtml(s.prompt)}\`, this)">
                                    <i class="fa-solid fa-copy me-1"></i> Copier
                                </button>
                            </div>
                            <div class="font-monospace small text-secondary bg-white p-2 rounded border">${escapeHtml(s.prompt)}</div>
                        `;
                        container.appendChild(item);
                    });

                    if (aiModal) aiModal.show();
                }
            });
    };

    // ═══════════════════════════════════════════════════════════════════
    // SÉLECTEUR D'ACTIFS DE LA GALERIE (238 ILLUSTRATIONS)
    // ═══════════════════════════════════════════════════════════════════
    let currentAssetCategory = 'all';
    let currentAssetSearch = '';

    window.openAssetPicker = function(shotIndex) {
        activeEditingShotIndex = shotIndex;
        if (assetModal) assetModal.show();
    };

    function initAssetPickerGrid() {
        filterAssets();
    }

    window.filterAssetCategory = function(cat, btn) {
        currentAssetCategory = cat;
        document.querySelectorAll('#assetCategoryButtons button').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        filterAssets();
    };

    window.filterAssets = function() {
        const searchInput = document.getElementById('assetSearchInput');
        if (searchInput) currentAssetSearch = searchInput.value.trim().toLowerCase();

        const grid = document.getElementById('assetPickerGrid');
        if (!grid) return;
        grid.innerHTML = '';

        let count = 0;
        galleryAssets.forEach(asset => {
            const matchesCat = (currentAssetCategory === 'all' || asset.category === currentAssetCategory);
            const matchesSearch = (!currentAssetSearch || 
                asset.title.toLowerCase().includes(currentAssetSearch) || 
                asset.filename.toLowerCase().includes(currentAssetSearch) ||
                asset.category_label.toLowerCase().includes(currentAssetSearch));

            if (matchesCat && matchesSearch) {
                count++;
                const col = document.createElement('div');
                col.className = 'col-4 col-sm-3 col-md-2';
                col.innerHTML = `
                    <div class="gallery-picker-item card card-sm h-100 shadow-sm" onclick="selectAssetForShot('${asset.path}')">
                        <div class="ratio ratio-1x1 bg-dark">
                            <img src="${asset.path}" alt="${escapeHtml(asset.title)}" class="w-100 h-100 object-fit-cover" loading="lazy">
                        </div>
                        <div class="p-1 text-truncate text-center small bg-white" style="font-size:0.68rem;" title="${escapeHtml(asset.title)}">
                            ${escapeHtml(asset.title)}
                        </div>
                    </div>
                `;
                grid.appendChild(col);
            }
        });

        const status = document.getElementById('assetPickerStatus');
        if (status) status.textContent = `${count} illustrations affichées`;
    };

    window.selectAssetForShot = function(assetPath) {
        if (activeEditingShotIndex !== null && currentStoryboard.shots[activeEditingShotIndex]) {
            currentStoryboard.shots[activeEditingShotIndex].image_url = assetPath;
            const imgEl = document.getElementById(`shotImg-${activeEditingShotIndex}`);
            if (imgEl) imgEl.src = assetPath;
            autoSaveShotsDebounced();
            if (assetModal) assetModal.hide();
        }
    };

    // ═══════════════════════════════════════════════════════════════════
    // STORYBOARD PLAYER (LECTEUR CINÉMATIQUE INTERACTIF)
    // ═══════════════════════════════════════════════════════════════════
    window.startStoryboardPlayer = function() {
        if (!currentStoryboard || !currentStoryboard.shots || currentStoryboard.shots.length === 0) {
            alert('Veuillez ajouter au moins un plan avant de lancer la lecture.');
            return;
        }

        playerShots = currentStoryboard.shots;
        playerTotalDuration = currentStoryboard.total_duration;
        playerCurrentTime = 0.0;
        isPlaying = true;

        document.getElementById('playerModalTitle').textContent = currentStoryboard.title;
        if (playerModal) playerModal.show();

        updatePlayerFrame();
        launchPlayerTimer();
    };

    function launchPlayerTimer() {
        clearInterval(playerTimer);
        const tickRateMs = 100;
        playerTimer = setInterval(() => {
            if (!isPlaying) return;
            playerCurrentTime += (tickRateMs / 1000);

            if (playerCurrentTime >= playerTotalDuration) {
                playerCurrentTime = playerTotalDuration;
                isPlaying = false;
                updatePlayPauseButton();
                clearInterval(playerTimer);
            }

            updatePlayerFrame();
        }, tickRateMs);
    }

    function updatePlayerFrame() {
        // Déterminer quel plan correspond au temps actuel
        let accumulated = 0.0;
        let activeIndex = 0;
        let currentShot = playerShots[0];

        for (let i = 0; i < playerShots.length; i++) {
            const dur = parseFloat(playerShots[i].duration) || 4.0;
            if (playerCurrentTime >= accumulated && playerCurrentTime < (accumulated + dur)) {
                activeIndex = i;
                currentShot = playerShots[i];
                break;
            }
            accumulated += dur;
        }

        // Si fin de vidéo atteinte
        if (playerCurrentTime >= playerTotalDuration && playerShots.length > 0) {
            activeIndex = playerShots.length - 1;
            currentShot = playerShots[activeIndex];
        }

        // Mettre à jour l'image et le sous-titre
        const img = document.getElementById('playerScreenImg');
        if (img && currentShot && img.src !== currentShot.image_url) {
            img.style.opacity = '0.7';
            setTimeout(() => {
                img.src = currentShot.image_url;
                img.style.opacity = '1';
            }, 100);
        }

        const sub = document.getElementById('playerSubtitles');
        if (sub && currentShot) {
            const text = currentShot.voiceover_text || currentShot.narrative_text || currentShot.title;
            sub.textContent = text;
            sub.style.display = text ? 'block' : 'none';
        }

        // Barre de progression
        const progress = document.getElementById('playerProgressBar');
        if (progress && playerTotalDuration > 0) {
            const pct = Math.min(100, (playerCurrentTime / playerTotalDuration) * 100);
            progress.style.width = pct + '%';
        }

        const tcBadge = document.getElementById('playerTimecodeBadge');
        if (tcBadge) {
            tcBadge.textContent = `${formatTimecode(playerCurrentTime)} / ${formatTimecode(playerTotalDuration)}`;
        }

        const info = document.getElementById('playerShotInfo');
        if (info && currentShot) {
            info.innerHTML = `Plan ${activeIndex + 1} / ${playerShots.length} &bull; ${escapeHtml(currentShot.camera_movement || '')}`;
        }
    }

    window.playerTogglePlay = function() {
        isPlaying = !isPlaying;
        updatePlayPauseButton();
        if (isPlaying && playerCurrentTime >= playerTotalDuration) {
            playerCurrentTime = 0.0;
            launchPlayerTimer();
        }
    };

    function updatePlayPauseButton() {
        const btn = document.getElementById('playerPlayPauseBtn');
        if (btn) {
            btn.innerHTML = isPlaying 
                ? '<i class="fa-solid fa-pause me-1"></i> Pause' 
                : '<i class="fa-solid fa-play me-1"></i> Lecture';
        }
    }

    window.playerRestart = function() {
        playerCurrentTime = 0.0;
        isPlaying = true;
        updatePlayPauseButton();
        updatePlayerFrame();
        launchPlayerTimer();
    };

    window.playerPrevShot = function() {
        // Trouver le début du plan courant ou précédent
        let acc = 0.0;
        for (let i = 0; i < playerShots.length; i++) {
            const dur = parseFloat(playerShots[i].duration) || 4.0;
            if (playerCurrentTime <= acc + dur) {
                playerCurrentTime = Math.max(0, acc - 0.1);
                break;
            }
            acc += dur;
        }
        updatePlayerFrame();
    };

    window.playerNextShot = function() {
        let acc = 0.0;
        for (let i = 0; i < playerShots.length; i++) {
            const dur = parseFloat(playerShots[i].duration) || 4.0;
            acc += dur;
            if (acc > playerCurrentTime) {
                playerCurrentTime = Math.min(playerTotalDuration, acc);
                break;
            }
        }
        updatePlayerFrame();
    };

    window.stopStoryboardPlayer = function() {
        isPlaying = false;
        clearInterval(playerTimer);
        if (playerModal) playerModal.hide();
    };

    // ═══════════════════════════════════════════════════════════════════
    // UTILITAIRES DIVERS
    // ═══════════════════════════════════════════════════════════════════
    function formatTimecode(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = (seconds % 60).toFixed(1);
        return `${String(min).padStart(2, '0')}:${String(sec).padStart(4, '0')}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    window.copyToClipboard = function(elementId, btn) {
        const el = document.getElementById(elementId);
        if (el) copyTextDirect(el.value, btn);
    };

    window.copyTextDirect = function(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copié !';
            setTimeout(() => { btn.innerHTML = orig; }, 1800);
        });
    };

    function showToast(message, type = 'success') {
        const container = document.getElementById('tablerToastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"><i class="fa-solid fa-circle-check me-1"></i> ${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // Lancement au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
