<?php
/**
 * Vue de la Carte des Provinces du Japon Féodal (Plein Écran / Pleine Largeur)
 * OpenShogun - Carte interactive plein format avec déplacement fluide & donjons authentiques
 */
require_once __DIR__ . '/../core/QuestEngine.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';

$planetX = isset($planet['coord_x']) ? (int)$planet['coord_x'] : 0;
$planetY = isset($planet['coord_y']) ? (int)$planet['coord_y'] : 0;
$planetId = isset($planet['id']) ? (int)$planet['id'] : 0;
$planetName = isset($planet['name']) ? (string)$planet['name'] : 'Fief';
$userId = isset($user['id']) ? (int)$user['id'] : 0;

$centerX = isset($_GET['x']) ? (int)$_GET['x'] : $planetX;
$centerY = isset($_GET['y']) ? (int)$_GET['y'] : $planetY;

try {
    if ($userId > 0) {
        $questEngine = new QuestEngine();
        $questEngine->recordAction($userId, 'visit_map');
    }
} catch (Throwable $e) {
    error_log("views/map.php quest error: " . $e->getMessage());
}

$stationedPecheurs = 0;
$stationedMineurs = 0;
try {
    if ($planetId > 0) {
        $dbMap = Database::getConnection();
        $stPec = $dbMap->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'pecheur'");
        $stPec->execute([$planetId]);
        $stationedPecheurs = (int)$stPec->fetchColumn() ?: 0;

        $stMin = $dbMap->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'mineur'");
        $stMin->execute([$planetId]);
        $stationedMineurs = (int)$stMin->fetchColumn() ?: 0;
    }
} catch (Throwable $e) {
    error_log("views/map.php stationed units query error: " . $e->getMessage());
}

$gameSpeedFactor = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));

$queueTitle = 'Mouvements & Chantiers';
ob_start();
?>
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title d-flex align-items-center gap-2">
                    <span><i class="fa-solid fa-map-location-dot text-danger me-1"></i> Carte des Provinces &amp; Fiefs</span>
                    <span class="badge bg-secondary-lt font-monospace" style="font-size:0.72rem;">[<?= $planetX ?> : <?= $planetY ?>]</span>
                </h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Fief d'attache : <strong><?= htmlspecialchars($planetName) ?></strong> &bull; Exploration panoramique &amp; terroirs du Japon Féodal
                </div>
            </div>

            <!-- Outils de coordonnées & boutons d'accès -->
            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <form id="jumpCoordsForm" onsubmit="event.preventDefault(); jumpToCoords();" class="d-flex align-items-center gap-1 bg-surface border rounded px-2 py-1 m-0">
                    <span class="text-secondary small font-weight-bold">Aller en :</span>
                    <span class="small font-weight-bold">X</span>
                    <input type="number" id="inputCoordX" value="<?= $centerX ?>" class="form-control form-control-sm text-center font-weight-bold" style="width: 55px; padding: 2px 4px;">
                    <span class="small font-weight-bold">Y</span>
                    <input type="number" id="inputCoordY" value="<?= $centerY ?>" class="form-control form-control-sm text-center font-weight-bold" style="width: 55px; padding: 2px 4px;">
                    <button type="submit" class="btn btn-sm btn-primary py-0 px-2" style="font-size:0.75rem; height:24px;">Aller</button>
                </form>
                <a href="/?page=resources" class="btn btn-secondary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-wheat-awn me-1"></i>Terroir
                </a>
                <a href="/?page=city" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-chess-rook me-1"></i>Cité Castrale &rarr;
                </a>
            </div>
        </div>

        <!-- Légende des Terroirs du Japon Féodal -->
        <div class="card-body bg-surface-secondary border-bottom py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2 small flex-shrink-0">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="font-weight-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;"><i class="fa-solid fa-map me-1"></i>Terroirs :</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_plains.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Plaines</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_forest.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Forêt</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_mountain.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Montagnes</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_lake.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Eaux</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_hills.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Collines</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_village.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Fief</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_authentic_castle.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Donjon Sacré</span>
                <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_imperial_dojo.jpg?v=3" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Dōjō Impérial</span>
                <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-success-lt" style="font-size:0.68rem; padding:2px 4px;"><i class="fa-solid fa-wheat-awn me-1"></i>+25%</span> Oasis</span>
            </div>
            <div class="text-secondary small font-italic d-none d-md-block">
                <i class="fa-solid fa-compass me-1"></i>Mini-map &amp; quadrants interactifs intégrés en haut à droite
            </div>
        </div>

        <!-- Conteneur Interactif Drag-and-Drop -->
        <div class="galaxy-map-wrapper map-fullwidth-wrapper" id="galaxyMapContainer" style="width: 100%; border: none; border-radius: 0; flex: 1 1 auto; min-height: 560px;">
            <!-- Rendu interactif via GalaxyMapController -->
        </div>
<?php
$centerCardContent = ob_get_clean();

ob_start();
?>

<!-- =====================================================
     MODAL CARTE — Informations de la Zone Sélectionnée
     Présentation Scrupuleusement Identique au Modèle Pêcheur & Terroir (resources.php)
     Illustration HD en haut (185px) avec overlay sombre, badges, titre & bouton fermer
     ===================================================== -->
<div id="mapTileModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
" onclick="if(event.target===this) closeMapModal();">

    <div class="card shadow-lg border-0 overflow-hidden" style="
        background: #ffffff;
        border-radius: 12px;
        width: 100%; max-width: 820px;
        max-height: 90vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: #1e293b;
    ">
        <!-- 1. Illustration en haut (Bandeau HD avec overlay dégradé, titre, badges et bouton fermer) -->
        <div class="position-relative overflow-hidden" style="width: 100%; height: 200px; background: #0f172a; flex-shrink: 0;">
            <img id="mapModalBannerImg" src="/public/assets/map/plaines.jpg?v=1" alt="Illustration Parcelle" style="width: 100%; height: 100%; object-fit: cover; object-position: center 35%;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>

            <!-- Bouton fermer blanc en haut à droite -->
            <button type="button" class="btn-close btn-close-white" onclick="closeMapModal()" aria-label="Fermer" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>

            <!-- Titres et Badges superposés en bas du bandeau -->
            <div style="position: absolute; bottom: 12px; left: 16px; right: 16px; z-index: 5;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                <div style="max-width: 72%;">
                    <div id="mapModalCategoryBadge" class="mb-1">
                        <span class="badge bg-primary text-white fw-bold px-2 py-1 shadow-sm" style="font-size: 0.75rem;">
                            TERROIR FÉODAL
                        </span>
                    </div>
                    <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="mapModalTitle" style="font-size: 1.25rem;">
                        Nom de la Parcelle
                    </h3>
                    <div class="text-white-50 font-monospace small mt-0.5 text-truncate" id="mapModalSubtitle">
                        Coordonnées &amp; Détails
                    </div>
                </div>
                <div>
                    <span id="mapModalCoords" class="badge bg-secondary text-white font-monospace fs-6 px-3 py-1 shadow-sm">
                        [0 : 0]
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Corps de la Modale -->
        <div id="mapModalBody" class="card-body p-3" style="color: #334155;">
            <!-- Rempli dynamiquement par JavaScript -->
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL CARTE — Expédition de Pêche Directe (Craft & Récolte Fluide)
     ===================================================== -->
<div id="mapFishingModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 9050;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
" onclick="if(event.target===this) closeQuickFishingModal();">

    <div class="card shadow-lg border-0 overflow-hidden" style="
        background: #ffffff;
        border-radius: 12px;
        width: 100%; max-width: 680px;
        max-height: 90vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: #1e293b;
    ">
        <!-- 1. Illustration en haut (Bandeau HD avec overlay dégradé, titre, badges et bouton fermer) -->
        <div class="position-relative overflow-hidden" style="width: 100%; height: 200px; background: #0f172a; flex-shrink: 0;">
            <img src="/public/assets/units/pecheur.jpg" alt="Pêcheurs Féodaux" style="width: 100%; height: 100%; object-fit: cover; object-position: center 25%;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>

            <!-- Bouton fermer blanc en haut à droite -->
            <button type="button" class="btn-close btn-close-white" onclick="closeQuickFishingModal()" aria-label="Fermer" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>

            <!-- Titres et Badges superposés en bas du bandeau -->
            <div style="position: absolute; bottom: 12px; left: 16px; right: 16px; z-index: 5;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                <div style="max-width: 68%;">
                    <div class="mb-1">
                        <span class="badge bg-cyan text-white fw-bold px-2 py-1 shadow-sm" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-fish me-1"></i>PÊCHEURS FÉODAUX (RYŌSHI)
                        </span>
                    </div>
                    <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="fishingModalDestName" style="font-size: 1.25rem;">
                        Lac d'Eaux Vives
                    </h3>
                    <div class="text-white-50 font-monospace small mt-0.5 text-truncate">
                        <span id="fishingModalDestCoords">[X : Y]</span> &bull; <span id="fishingModalDist">--</span>
                    </div>
                </div>
                <div>
                    <span class="badge bg-cyan text-white font-monospace fs-6 px-3 py-1 shadow-sm" id="fishingModalStockBadge">
                        <i class="fa-solid fa-fish me-1"></i>Stock : --
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Corps de la Modale -->
        <div class="card-body p-3 p-md-4">
            <p class="small text-secondary mb-3" style="line-height:1.45;">
                Dépêchez vos <strong>Pêcheurs Féodaux (Ryōshi 🐟)</strong> pour poser leurs nasses et filets sur ce sanctuaire d'eau douce. Les cargaisons de poisson frais seront directement acheminées et entreposées dans votre fief.
            </p>

            <!-- Disponibilité Garnison -->
            <div class="d-flex justify-content-between align-items-center p-2 rounded bg-surface-secondary border mb-3 small">
                <span class="fw-semibold text-dark">
                    <i class="fa-solid fa-person-rifle text-primary me-1"></i>Pêcheurs en garnison :
                </span>
                <span class="badge bg-cyan text-white font-monospace px-2 py-1" style="font-size:0.8rem;" id="fishingModalAvailCount">
                    <?= number_format($stationedPecheurs) ?> Ryōshi
                </span>
            </div>

            <!-- Formulaire de saisie du nombre de pêcheurs -->
            <div id="fishingFormContainer">
                <div class="bg-light p-3 rounded border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <label class="form-label fw-bold small text-dark mb-0">Effectif de Pêcheurs à mobiliser :</label>
                        <span class="badge bg-cyan-lt text-cyan font-monospace" style="font-size:0.72rem;">
                            Disponible : <strong id="fishingModalAvailNum"><?= $stationedPecheurs ?></strong>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2.5">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2.5" onclick="adjustFishingCount(-1)">-1</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustFishingCount(-5)">-5</button>
                        <input type="number" id="inputFishingCount" class="form-control text-center font-monospace fw-bold fs-4" 
                               min="1" max="<?= $stationedPecheurs ?>" value="<?= min($stationedPecheurs, 1) ?>" 
                               oninput="updateFishingEstimates()" style="max-width: 140px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustFishingCount(5)">+5</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2.5" onclick="adjustFishingCount(1)">+1</button>
                        <button type="button" class="btn btn-sm btn-outline-cyan font-monospace px-2.5 ms-auto fw-bold" onclick="setFishingCountMax()">Max</button>
                    </div>

                    <!-- Métriques de capture estimée & capacité -->
                    <div class="d-flex flex-column gap-1.5 p-2 rounded bg-white border font-monospace small" style="font-size:0.75rem;">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-solid fa-fish text-cyan me-1"></i>Prise estimée :</span>
                            <strong class="text-dark" id="fishingEstCatch">-- Poisson</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-solid fa-boxes-stacked text-warning me-1"></i>Capacité de fret totale :</span>
                            <strong class="text-dark" id="fishingEstCargo">-- unités</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-regular fa-clock text-info me-1"></i>Durée totale de l'expédition :</span>
                            <strong class="text-dark" id="fishingEstDuration">--</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message d'alerte si 0 pêcheur -->
            <div id="fishingNoUnitsAlert" class="alert alert-warning p-2.5 small mb-3 <?= ($stationedPecheurs > 0) ? 'd-none' : '' ?>">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-warning fs-3"></i>
                    <div>
                        <strong>Aucun Pêcheur Féodal en garnison !</strong><br>
                        Pour dépêcher cette expédition lacustre, vous devez former des <strong>Pêcheurs (Ryōshi 🐟)</strong> au Dojo Militaire.
                        <div class="mt-2">
                            <a href="/?page=barracks" class="btn btn-sm btn-warning text-dark font-monospace py-0.5 px-2">
                                <i class="fa-solid fa-torii-gate me-1"></i>Former au Dojo &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons de validation -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <button type="button" class="btn btn-outline-secondary" onclick="closeQuickFishingModal()">Annuler</button>
                <button type="button" class="btn btn-cyan text-white fw-bold shadow-sm" id="btnLaunchQuickFishing" 
                        onclick="submitQuickFishing()" <?= ($stationedPecheurs <= 0) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-paper-plane me-1"></i>Dépêcher l'Expédition de Pêche
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL CARTE — Expédition Minière Directe (Mineurs des Cimes & Forage Fluide)
     ===================================================== -->
<div id="mapMiningModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 9050;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
" onclick="if(event.target===this) closeQuickMiningModal();">

    <div class="card shadow-lg border-0 overflow-hidden" style="
        background: #ffffff;
        border-radius: 12px;
        width: 100%; max-width: 680px;
        max-height: 90vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: #1e293b;
    ">
        <!-- 1. Illustration en haut (Bandeau HD avec overlay dégradé, titre, badges et bouton fermer) -->
        <div class="position-relative overflow-hidden" style="width: 100%; height: 200px; background: #0f172a; flex-shrink: 0;">
            <img src="/public/assets/units/mineur.jpg" alt="Mineurs des Cimes" style="width: 100%; height: 100%; object-fit: cover; object-position: center 25%;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>

            <!-- Bouton fermer blanc en haut à droite -->
            <button type="button" class="btn-close btn-close-white" onclick="closeQuickMiningModal()" aria-label="Fermer" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>

            <!-- Titres et Badges superposés en bas du bandeau -->
            <div style="position: absolute; bottom: 12px; left: 16px; right: 16px; z-index: 5;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white">
                <div style="max-width: 60%;">
                    <div class="mb-1">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-pickaxe me-1"></i>MINEURS DES CIMES (KŌFU)
                        </span>
                    </div>
                    <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="miningModalDestName" style="font-size: 1.25rem;">
                        Pics Rocheux &amp; Montagnes
                    </h3>
                    <div class="text-white-50 font-monospace small mt-0.5 text-truncate">
                        <span id="miningModalDestCoords">[X : Y]</span> &bull; <span id="miningModalDist">--</span>
                    </div>
                </div>
                <div class="d-flex gap-1 flex-wrap justify-content-end">
                    <span class="badge bg-warning text-dark font-monospace shadow-sm" id="miningModalSulfurBadge" style="font-size:0.7rem;">
                        🟡 Soufre: --
                    </span>
                    <span class="badge bg-cyan text-white font-monospace shadow-sm" id="miningModalNitrateBadge" style="font-size:0.7rem;">
                        ⚪ Nitrate: --
                    </span>
                    <span class="badge bg-secondary text-white font-monospace shadow-sm" id="miningModalIronSandBadge" style="font-size:0.7rem;">
                        🟤 Fer: --
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Corps de la Modale -->
        <div class="card-body p-3 p-md-4">
            <p class="small text-secondary mb-3" style="line-height:1.45;">
                Dépêchez vos <strong>Mineurs des Cimes (Kōfu ⛏️)</strong> pour gravir ces crêtes granitiques et volcaniques. Les cargaisons de <strong>Soufre</strong>, <strong>Nitrate (Salpêtre)</strong> et <strong>Sable de Fer (Satetsu)</strong> seront directement acheminées et stockées dans votre fief.
            </p>
                Dépêchez vos <strong>Mineurs des Cimes (Kōfu ⛏️)</strong> pour gravir ces crêtes granitiques et volcaniques. Les cargaisons de <strong>Soufre</strong>, <strong>Nitrate (Salpêtre)</strong> et <strong>Sable de Fer (Satetsu)</strong> seront directement acheminées et stockées dans votre fief.
            </p>

            <!-- Disponibilité Garnison -->
            <div class="d-flex justify-content-between align-items-center p-2 rounded bg-surface-secondary border mb-3 small">
                <span class="fw-semibold text-dark">
                    <i class="fa-solid fa-person-digging text-warning me-1"></i>Mineurs en garnison :
                </span>
                <span class="badge bg-warning text-dark font-monospace px-2 py-1" style="font-size:0.8rem;" id="miningModalAvailCount">
                    <?= number_format($stationedMineurs) ?> Kōfu
                </span>
            </div>

            <!-- Formulaire de saisie du nombre de mineurs -->
            <div id="miningFormContainer">
                <div class="bg-light p-3 rounded border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <label class="form-label fw-bold small text-dark mb-0">Effectif de Mineurs à mobiliser :</label>
                        <span class="badge bg-warning-lt text-dark font-monospace" style="font-size:0.72rem;">
                            Disponible : <strong id="miningModalAvailNum"><?= $stationedMineurs ?></strong>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2 mb-2.5">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2.5" onclick="adjustMiningCount(-1)">-1</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustMiningCount(-5)">-5</button>
                        <input type="number" id="inputMiningCount" class="form-control text-center font-monospace fw-bold fs-4" 
                               min="1" max="<?= $stationedMineurs ?>" value="<?= min($stationedMineurs, 1) ?>" 
                               oninput="updateMiningEstimates()" style="max-width: 140px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustMiningCount(5)">+5</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2.5" onclick="adjustMiningCount(1)">+1</button>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark font-monospace px-2.5 ms-auto fw-bold" onclick="setMiningCountMax()">Max</button>
                    </div>

                    <!-- Métriques d'extraction estimée & capacité -->
                    <div class="d-flex flex-column gap-1.5 p-2 rounded bg-white border font-monospace small" style="font-size:0.75rem;">
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-solid fa-pickaxe text-warning me-1"></i>Minerais extraits estimés :</span>
                            <strong class="text-dark" id="miningEstTotal">--</strong>
                        </div>
                        <div class="d-flex justify-content-between ps-3 text-muted" style="font-size:0.7rem;">
                            <span id="miningEstBreakdown">🟡 Soufre: 0 &bull; ⚪ Nitrate: 0 &bull; 🟤 Fer: 0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-solid fa-boxes-stacked text-primary me-1"></i>Capacité de fret totale :</span>
                            <strong class="text-dark" id="miningEstCargo">-- unités</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary"><i class="fa-regular fa-clock text-info me-1"></i>Durée totale de l'expédition :</span>
                            <strong class="text-dark" id="miningEstDuration">--</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message d'alerte si 0 mineur -->
            <div id="miningNoUnitsAlert" class="alert alert-warning p-2.5 small mb-3 <?= ($stationedMineurs > 0) ? 'd-none' : '' ?>">
                <div class="d-flex align-items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-warning fs-3"></i>
                    <div>
                        <strong>Aucun Mineur des Cimes en garnison !</strong><br>
                        Pour dépêcher cette expédition minière, vous devez former des <strong>Mineurs des Cimes (Kōfu ⛏️)</strong> au Dojo Militaire.
                        <div class="mt-2">
                            <a href="/?page=barracks" class="btn btn-sm btn-warning text-dark font-monospace py-0.5 px-2">
                                <i class="fa-solid fa-torii-gate me-1"></i>Former au Dojo &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons de validation -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <button type="button" class="btn btn-outline-secondary" onclick="closeQuickMiningModal()">Annuler</button>
                <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm" id="btnLaunchQuickMining" 
                        onclick="submitQuickMining()" <?= ($stationedMineurs <= 0) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-paper-plane me-1"></i>Dépêcher l'Expédition Minière
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes mapModalIn {
    from { opacity:0; transform: scale(0.96) translateY(6px); }
    to   { opacity:1; transform: scale(1)   translateY(0); }
}
</style>

<script src="/public/js/galaxy_map.js?v=<?= file_exists(__DIR__ . '/../public/js/galaxy_map.js') ? filemtime(__DIR__ . '/../public/js/galaxy_map.js') : time() ?>"></script>
<script>
let galaxyMap = null;
const mapPlayerX = <?= $planetX ?>;
const mapPlayerY = <?= $planetY ?>;
let stationedPecheurs = <?= (int)$stationedPecheurs ?>;
let stationedMineurs = <?= (int)$stationedMineurs ?>;
const mapGameSpeed = <?= (float)$gameSpeedFactor ?>;
let currentFishingTarget = null;
let currentMiningTarget = null;

document.addEventListener('DOMContentLoaded', () => {
    galaxyMap = new GalaxyMapController('galaxyMapContainer', {
        initialX: <?= $centerX ?>,
        initialY: <?= $centerY ?>,
        playerX: <?= $planetX ?>,
        playerY: <?= $planetY ?>,
        userId: <?= $userId ?>
        // radius non spécifié pour calcul automatique pleine largeur
    });
});

function jumpToCoords() {
    const x = parseInt(document.getElementById('inputCoordX').value, 10);
    const y = parseInt(document.getElementById('inputCoordY').value, 10);
    if (!isNaN(x) && !isNaN(y) && galaxyMap) {
        galaxyMap.moveTo(x, y);
    }
}

// Met à jour l'en-tête de la modale de carte (Illustration HD en haut, badges, titre, coordonnées)
function setMapModalHeader(imgUrl, categoryHtml, titleHtml, subtitleHtml, coordsText) {
    const bannerImg = document.getElementById('mapModalBannerImg');
    const catBadge  = document.getElementById('mapModalCategoryBadge');
    const titleEl   = document.getElementById('mapModalTitle');
    const subEl     = document.getElementById('mapModalSubtitle');
    const coordsEl  = document.getElementById('mapModalCoords');

    if (bannerImg) bannerImg.src = imgUrl || '/public/assets/map/plaines.jpg?v=1';
    if (catBadge)  catBadge.innerHTML = categoryHtml || '<span class="badge bg-primary text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;">CARTE</span>';
    if (titleEl)   titleEl.innerHTML = titleHtml || 'Parcelle';
    if (subEl)     subEl.innerHTML = subtitleHtml || '';
    if (coordsEl)  coordsEl.textContent = coordsText || '';
}

function openMapModal() {
    const modal = document.getElementById('mapTileModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeMapModal() {
    document.getElementById('mapTileModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Fermeture avec Echap
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMapModal();
});

// Fonctions utilitaires pour le calcul et rendu des propriétés de parcelles
function getDeterministicPlotPotentials(x, y) {
    const structures = [
        'tenshu', 'foret', 'carriere', 'culture_the',
        'village', 'fosse_argile', 'riziere', 'champ_soja', 'sanctuaire_shinto'
    ];
    let seed = 0;
    const str = `shogun_plots_${x}_${y}`;
    for (let i = 0; i < str.length; i++) {
        seed = ((seed << 5) - seed) + str.charCodeAt(i);
        seed |= 0;
    }
    seed = Math.abs(seed);
    const plots = {};
    structures.forEach((stype, idx) => {
        const plotSeed = (seed + (idx * 7919)) % 10000;
        let maxLevel = 25 + (plotSeed % 41);
        if ((plotSeed % 11) === 0) {
            maxLevel = Math.min(100, maxLevel + 25 + (plotSeed % 15));
        }
        plots[stype] = { level: 0, max_level: maxLevel };
    });
    return plots;
}

function renderRuralPlotsGrid(ruralPlots) {
    const structureMeta = {
        'tenshu':            { name: 'Tenshu Donjon',      icon: 'fa-chess-rook',    color: '#b45309' },
        'foret':             { name: 'Forêt de Cèdres',   icon: 'fa-tree',          color: '#15803d' },
        'carriere':          { name: 'Carrière de Granit',icon: 'fa-mountain',      color: '#2563eb' },
        'fosse_argile':      { name: 'Fosse d\'Argile',    icon: 'fa-cubes-stacked', color: '#ea580c' },
        'riziere':           { name: 'Rizière Alluviale',  icon: 'fa-wheat-awn',     color: '#ca8a04' },
        'champ_soja':        { name: 'Champs de Soja',     icon: 'fa-seedling',      color: '#16a34a' },
        'culture_the':       { name: 'Coteaux de Thé',     icon: 'fa-leaf',          color: '#059669' },
        'sanctuaire_shinto': { name: 'Sanctuaire Shintō', icon: 'fa-torii-gate',    color: '#dc2626' },
        'village':           { name: 'Village & Habitats', icon: 'fa-house-chimney', color: '#475569' }
    };

    let html = `
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="text-uppercase small text-dark" style="letter-spacing:0.5px; font-size:0.78rem;">
                    <i class="fa-solid fa-layer-group text-primary me-1"></i>Propriétés &amp; Potentiels Verticaux (Niveaux Max d'Évolution)
                </strong>
                <span class="badge bg-success text-white font-weight-bold" style="font-size:0.72rem;">9 Parcelles de Terroir</span>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:0.5rem;">
    `;

    for (const [stype, pInfo] of Object.entries(ruralPlots)) {
        const meta = structureMeta[stype] || { name: stype, icon: 'fa-circle-dot', color: '#475569' };
        const maxLvl = pInfo.max_level || 30;
        const isJackpot = maxLvl >= 70;
        const borderStyle = isJackpot 
            ? 'border:1.5px solid #eab308; background:#fefce8; box-shadow:0 1px 3px rgba(234,179,8,0.2);' 
            : 'border:1px solid #cbd5e1; background:#ffffff;';

        html += `
            <div style="${borderStyle} border-radius:8px; padding:0.5rem 0.65rem; display:flex; align-items:center; justify-content:space-between; gap:0.4rem;">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span style="color:${meta.color}; font-size:1.1rem; width:20px; text-align:center;"><i class="fa-solid ${meta.icon}"></i></span>
                    <div class="lh-1 text-truncate">
                        <div style="font-size:0.8rem; font-weight:700; color:#1e293b;" class="text-truncate">${meta.name}</div>
                        <div class="text-muted" style="font-size:0.68rem; margin-top:2px;">Actuel : Niv. ${pInfo.level || 0}</div>
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="badge ${isJackpot ? 'bg-warning text-dark fw-bold' : 'bg-primary-lt text-dark fw-bold'}" style="font-size:0.75rem;">
                        Max ${maxLvl}
                    </span>
                    ${isJackpot ? '<div style="font-size:0.6rem; color:#b45309; font-weight:700;">Spécialité ⭐</div>' : ''}
                </div>
            </div>
        `;
    }

    html += `</div></div>`;
    return html;
}

// Rendu du compteur des réserves minérales pour les parcelles de montagne
function renderMountainMineralsCounter(sulfurStock, sulfurInit, nitrateStock, nitrateInit, ironSandStock, ironSandInit, isDepleted) {
    const sCur = parseInt(sulfurStock, 10) || 0;
    const sInit = parseInt(sulfurInit, 10) || (sCur || 10000);
    const sPct = Math.max(sCur <= 0 ? 0 : 2, Math.min(100, Math.round((sCur / (sInit || 1)) * 100)));

    const nCur = parseInt(nitrateStock, 10) || 0;
    const nInit = parseInt(nitrateInit, 10) || (nCur || 10000);
    const nPct = Math.max(nCur <= 0 ? 0 : 2, Math.min(100, Math.round((nCur / (nInit || 1)) * 100)));

    const iCur = parseInt(ironSandStock, 10) || 0;
    const iInit = parseInt(ironSandInit, 10) || (iCur || 15000);
    const iPct = Math.max(iCur <= 0 ? 0 : 2, Math.min(100, Math.round((iCur / (iInit || 1)) * 100)));

    const depleted = !!isDepleted || (sCur <= 0 && nCur <= 0 && iCur <= 0);

    return `
        <div class="card mb-3 shadow-none border ${depleted ? 'border-secondary-subtle bg-secondary-lt' : 'border-warning-subtle'}" style="border-radius:10px; ${depleted ? '' : 'background:#fffbeb;'}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-md ${depleted ? 'bg-secondary text-white' : 'bg-warning text-dark'} rounded shadow-sm" style="font-size:1.35rem;">
                            <i class="fa-solid fa-mountain"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.95rem;">
                                Filons &amp; Gisements Minéraux des Cimes
                            </div>
                            <div class="text-secondary small" style="font-size:0.75rem;">
                                Sanctuaire minéralier d'altitude &bull; Soufre, Salpêtre &amp; Sable de Fer
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge ${depleted ? 'bg-secondary' : 'bg-warning text-dark'} font-monospace px-3 py-2 shadow-sm" style="font-size:0.95rem; letter-spacing:0.5px;">
                            <i class="fa-solid fa-pickaxe me-1"></i>${depleted ? 'Gisements Épuisés' : 'Gisements Actifs'}
                        </span>
                    </div>
                </div>

                <!-- 3 Jauges de minerais -->
                <div class="d-flex flex-column gap-2 mb-3">
                    <!-- 1. Soufre -->
                    <div class="bg-white rounded p-2 border border-light-subtle">
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-dark fw-bold">
                                <i class="fa-solid fa-volcano text-warning me-1"></i>Soufre Volcanique :
                            </span>
                            <span class="font-monospace fw-bold ${sCur <= 0 ? 'text-secondary' : 'text-warning'}">
                                <strong>${sCur.toLocaleString()}</strong> / ${sInit.toLocaleString()}
                                <span class="text-muted fw-normal ms-1">(${sPct}%)</span>
                            </span>
                        </div>
                        <div class="progress progress-sm" style="height:7px; border-radius:4px; background:#e2e8f0;">
                            <div class="progress-bar bg-warning" style="width:${sPct}%;"></div>
                        </div>
                    </div>

                    <!-- 2. Nitrate -->
                    <div class="bg-white rounded p-2 border border-light-subtle">
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-dark fw-bold">
                                <i class="fa-solid fa-flask-vial text-info me-1"></i>Nitrate de Potasse (Salpêtre) :
                            </span>
                            <span class="font-monospace fw-bold ${nCur <= 0 ? 'text-secondary' : 'text-info'}">
                                <strong>${nCur.toLocaleString()}</strong> / ${nInit.toLocaleString()}
                                <span class="text-muted fw-normal ms-1">(${nPct}%)</span>
                            </span>
                        </div>
                        <div class="progress progress-sm" style="height:7px; border-radius:4px; background:#e2e8f0;">
                            <div class="progress-bar bg-info" style="width:${nPct}%;"></div>
                        </div>
                    </div>

                    <!-- 3. Sable de Fer -->
                    <div class="bg-white rounded p-2 border border-light-subtle">
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span class="text-dark fw-bold">
                                <i class="fa-solid fa-gem text-secondary me-1"></i>Sable de Fer (Satetsu 砂鉄) :
                            </span>
                            <span class="font-monospace fw-bold ${iCur <= 0 ? 'text-secondary' : 'text-secondary'}">
                                <strong>${iCur.toLocaleString()}</strong> / ${iInit.toLocaleString()}
                                <span class="text-muted fw-normal ms-1">(${iPct}%)</span>
                            </span>
                        </div>
                        <div class="progress progress-sm" style="height:7px; border-radius:4px; background:#e2e8f0;">
                            <div class="progress-bar bg-secondary" style="width:${iPct}%;"></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small" style="font-size:0.78rem;">
                    <span class="text-secondary">
                        <i class="fa-solid fa-circle-info ${depleted ? 'text-warning' : 'text-info'} me-1"></i>
                        ${depleted 
                            ? '<strong>Gisements forés à sec.</strong> Cette montagne demeure sur la province mais ne peut plus être forée.' 
                            : 'Foré par vos <strong>Mineurs des Cimes (<i class="fa-solid fa-pickaxe"></i>)</strong> créés au Dojo Militaire.'
                        }
                    </span>
                    <span class="text-dark font-weight-medium">
                        <i class="fa-solid fa-cubes text-secondary me-1"></i>Stocké dans les <strong>Entrepôts de Fief</strong>
                    </span>
                </div>
            </div>
        </div>
    `;
}

// Rendu du compteur des ressources disponibles en poisson pour les parcelles de lac
function renderLakeFishCounter(currentStock, initialStock, isDepleted) {
    const cur = parseInt(currentStock, 10) || 0;
    const init = parseInt(initialStock, 10) || (cur || 25000);
    const depleted = !!isDepleted || (cur <= 0);
    const pct = Math.max(depleted ? 0 : 2, Math.min(100, Math.round((cur / (init || 1)) * 100)));

    return `
        <div class="card mb-3 shadow-none border ${depleted ? 'border-secondary-subtle bg-secondary-lt' : 'border-info-subtle'}" style="border-radius:10px; ${depleted ? '' : 'background:#f0fdfa;'}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-md ${depleted ? 'bg-secondary text-white' : 'bg-cyan text-white'} rounded shadow-sm" style="font-size:1.35rem;">
                            <i class="fa-solid fa-fish"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.95rem;">
                                Ressources Disponibles en Poisson
                            </div>
                            <div class="text-secondary small" style="font-size:0.75rem;">
                                Sanctuaire halieutique naturel &bull; Vivres sauvages pêchables
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge ${depleted ? 'bg-secondary' : 'bg-cyan'} text-white font-monospace px-3 py-2 shadow-sm" style="font-size:1.1rem; letter-spacing:0.5px;">
                            <i class="fa-solid fa-fish me-1"></i><strong>${cur.toLocaleString()}</strong> ${depleted ? 'poisson (Épuisé)' : 'poissons disponibles'}
                        </span>
                    </div>
                </div>

                <!-- Jauge de réserve halieutique -->
                <div class="bg-white rounded p-2 border border-light-subtle mb-2">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-secondary font-weight-medium">
                            <i class="fa-solid fa-water text-cyan me-1"></i>Banc de poissons restant :
                        </span>
                        <span class="font-monospace fw-bold ${depleted ? 'text-secondary' : 'text-cyan'}">
                            <strong>${cur.toLocaleString()}</strong> / ${init.toLocaleString()} vivres
                            <span class="text-muted fw-normal ms-1">(${pct}%)</span>
                        </span>
                    </div>
                    <div class="progress progress-sm" style="height:9px; border-radius:4px; background:#e2e8f0;">
                        <div class="progress-bar ${depleted ? 'bg-secondary' : 'bg-cyan'}" style="width:${pct}%;"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small" style="font-size:0.78rem;">
                    <span class="text-secondary">
                        <i class="fa-solid fa-circle-info ${depleted ? 'text-warning' : 'text-info'} me-1"></i>
                        ${depleted 
                            ? '<strong>Réserve épuisée.</strong> Ce lac demeure paisiblement sur la province mais ne peut plus être pêché.' 
                            : 'Prélevé par vos <strong>Pêcheurs Féodaux (<i class="fa-solid fa-fish"></i>)</strong> créés au Dojo Militaire.'
                        }
                    </span>
                    <span class="text-dark font-weight-medium">
                        <i class="fa-solid fa-warehouse text-warning me-1"></i>Stocké au <strong>Grenier à Riz (Kura)</strong>
                    </span>
                </div>
            </div>
        </div>
    `;
}

// ── Résolution de l'illustration pour chaque type de parcelle dans la modale de carte ──
function getMapModalIllustration(data) {
    if (!data) return '/public/assets/map/plaines.jpg?v=1';

    // 1. Terre Sacrée Inviolable [0 : 0] Scellée
    if (data.is_sealed_dojo || (parseInt(data.coord_x, 10) === 0 && parseInt(data.coord_y, 10) === 0 && !data.is_imperial_dojo && !data.dojo_unlocked)) {
        return '/public/assets/map/terre_sacree.jpg?v=1';
    }
    // 2. Dōjō Impérial Éveillé [0 : 0] (Tenchi no Seichi)
    if (data.is_imperial_dojo || (parseInt(data.coord_x, 10) === 0 && parseInt(data.coord_y, 10) === 0 && (data.is_imperial_dojo || data.dojo_unlocked))) {
        return '/public/assets/map/dojo_imperial.jpg?v=1';
    }
    // 3. Donjon Historique Authentique (現存十二天守)
    if (data.is_authentic_castle) {
        return '/public/assets/map/donjon.jpg?v=1';
    }
    // 4. Fief de Daimyō (Joueur ou IA)
    if (data.user_id) {
        return '/public/assets/map/fief.jpg?v=1';
    }
    // 5. Oasis Naturelle (selon type d'oasis ou oasis générale)
    if (data.is_oasis) {
        const oType = (data.oasis_type || '').toLowerCase();
        const oName = (data.oasis_name || '').toLowerCase();
        if (oType.includes('hills') || oName.includes('colline') || oName.includes('coteau')) {
            return '/public/assets/map/collines.jpg?v=1';
        }
        if (oType.includes('mountain') || oType.includes('rock') || oName.includes('montagne') || oName.includes('pic')) {
            return '/public/assets/map/pics.jpg?v=1';
        }
        if (oType.includes('forest') || oName.includes('forêt') || oName.includes('bois')) {
            return '/public/assets/map/foret.jpg?v=1';
        }
        if (oType.includes('lake') || oName.includes('lac') || /\beaux?\b/i.test(oName)) {
            return '/public/assets/map/lac.jpg?v=1';
        }
        return '/public/assets/map/oasis.jpg?v=1';
    }

    // 6. Terrains Naturels et Domaines Libres (Plaines, Forêt, Montagnes, Collines, Lac)
    const tType = (data.terrain_type || data.planet_type || '').toLowerCase();
    const tName = (data.terrain_name || data.planet_name || '').toLowerCase();

    // Priorité explicite aux reliefs pour ne jamais confondre 'coteaux' (qui contient 'eau') avec un lac
    if (tType === 'hills' || tName.includes('colline') || tName.includes('coteau') || tName.includes('terrasse')) {
        return '/public/assets/map/collines.jpg?v=1';
    }
    if (tType === 'mountain' || data.is_mountain || tName.includes('montagne') || tName.includes('pic') || tName.includes('crête') || tName.includes('rocheux')) {
        return '/public/assets/map/pics.jpg?v=1';
    }
    if (tType === 'forest' || tName.includes('forêt') || tName.includes('cèdre') || tName.includes('bois') || tName.includes('sugi')) {
        return '/public/assets/map/foret.jpg?v=1';
    }
    if (tType === 'lake' || tType === 'oceanic' || data.is_lake || tName.includes('lac') || /\beaux?\b/i.test(tName)) {
        return '/public/assets/map/lac.jpg?v=1';
    }
    return '/public/assets/map/plaines.jpg?v=1';
}

// Fonction appelée lors du clic sur une tuile de la carte
window.selectPlanetTile = function(data) {
    const body = document.getElementById('mapModalBody');
    const modalImg = getMapModalIllustration(data);

    // ── Terre Sacrée [0 : 0] / Le Dōjō Impérial ──────────────────────────────
    if (data.is_imperial_dojo || (parseInt(data.coord_x, 10) === 0 && parseInt(data.coord_y, 10) === 0)) {
        const isDojoUnlocked = !!(data.dojo_unlocked || data.is_imperial_dojo);

        if (!isDojoUnlocked) {
            // Dōjō NON ACTIVÉ : Terre Sacrée Scellée non-annexable
            setMapModalHeader(
                modalImg,
                '<span class="badge bg-secondary text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-lock me-1"></i>TERRE SCELLÉE</span>',
                'Terre Sacrée Inviolable de Kyōto',
                'Sanctuaire Impérial scellé &bull; Enveloppé dans les brumes ancestrales',
                `[${data.coord_x} : ${data.coord_y}]`
            );
            body.innerHTML = `
                <div class="alert alert-secondary py-3 px-3 mb-3 d-flex align-items-center gap-3 rounded-2">
                    <i class="fa-solid fa-ban text-danger fs-1 flex-shrink-0"></i>
                    <div>
                        <strong class="d-block text-dark mb-1">Sanctuaire Impérial &bull; Non Annexable :</strong>
                        <span class="small text-secondary">Aucun seigneur féodal ne peut coloniser, annexer ou attaquer le centre du monde [0 : 0]. Le Dōjō Impérial n'apparaîtra qu'à l'avènement de l'Ère IV, une fois la Guerre des 12 Châteaux remportée.</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-light rounded border small">
                    <span class="text-secondary">Statut Foncier :</span>
                    <strong class="text-danger"><i class="fa-solid fa-ban me-1"></i>Sanctuaire Impérial Inviolable &bull; Non Annexable</strong>
                </div>

                <div class="d-flex justify-content-end pt-2 border-top">
                    <button type="button" class="btn btn-outline-secondary" onclick="closeMapModal()">Fermer</button>
                </div>
            `;
            openMapModal();
            return;
        }

        // Dōjō ACTIVÉ (Ère IV+)
        const dojoLvl = parseInt(data.dojo_level, 10) || 0;
        const targetLvl = parseInt(data.dojo_target_level, 10) || 1;
        const hp = parseInt(data.dojo_hp, 10) || 0;
        const maxHp = parseInt(data.dojo_max_hp, 10) || 5000000;
        const hpPercent = maxHp > 0 ? Math.min(100, Math.round((hp / maxHp) * 100)) : 0;
        const allianceTag = data.dojo_alliance_tag || '';
        const allianceName = data.dojo_alliance_name || 'Aucune alliance';
        const isFrozen = (data.dojo_status === 'frozen');
        const isCompleted = (data.dojo_status === 'completed' || dojoLvl >= 10);
        const progressPercent = parseInt(data.dojo_progress_percent, 10) || 0;

        let statusBadge = '<span class="badge bg-secondary">Terre Neutre Non Réclamée</span>';
        if (isFrozen) {
            statusBadge = '<span class="badge bg-danger"><i class="fa-solid fa-snowflake me-1"></i>Chantier Gelé (&lt; 2 Châteaux)</span>';
        } else if (isCompleted) {
            statusBadge = '<span class="badge bg-purple text-white"><i class="fa-solid fa-crown me-1"></i>Niveau 10 Suprême &bull; Victoire</span>';
        } else if (allianceTag) {
            statusBadge = `<span class="badge bg-success"><i class="fa-solid fa-hammer me-1"></i>Chantier Actif [${escapeHtml(allianceTag)}]</span>`;
        }

        setMapModalHeader(
            modalImg,
            '<span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-torii-gate me-1"></i>DŌJŌ IMPÉRIAL</span>',
            'Dōjō Impérial (Tenchi no Seichi)',
            'Le Sanctuaire Suprême de Kyōto &bull; Ère IV',
            `[${data.coord_x} : ${data.coord_y}]`
        );

        body.innerHTML = `
            <div class="mb-3">
                ${statusBadge}
            </div>

            <!-- Règle de non-annexion individuelle formelle -->
            <div class="alert alert-warning py-2.5 px-3 mb-3 small d-flex align-items-center gap-2 rounded-2">
                <i class="fa-solid fa-ban text-danger fs-3 flex-shrink-0"></i>
                <div>
                    <strong class="text-dark d-block">Sanctuaire Impérial &bull; Non Annexable par un Daimyō :</strong>
                    Cette merveille mondiale ne peut être annexée par aucun joueur individuel. Le contrôle s'opère collectivement par les Alliances via les 10 Paliers de construction.
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="p-2 bg-light rounded border">
                        <span class="text-secondary small d-block">Palier Actuel :</span>
                        <strong class="text-dark font-monospace" style="font-size:1.05rem;">Niveau ${dojoLvl} / 10</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded border">
                        <span class="text-secondary small d-block">Alliance Bâtisseuse :</span>
                        <strong class="text-primary">${allianceTag ? `[${escapeHtml(allianceTag)}] ${escapeHtml(allianceName)}` : 'Non revendiqué'}</strong>
                    </div>
                </div>
            </div>

            <!-- Jauge de Structure PV -->
            <div class="mb-3 p-2 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-1 small">
                    <span><i class="fa-solid fa-shield-halved text-danger me-1"></i>Résistance de Structure :</span>
                    <span class="font-monospace fw-bold">${Number(hp).toLocaleString()} / ${Number(maxHp).toLocaleString()} PV (${hpPercent}%)</span>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width:${hpPercent}%"></div>
                </div>
            </div>

            <!-- Jauge de ressources vers le palier suivant -->
            ${!isCompleted ? `
            <div class="mb-3 p-2 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center mb-1 small">
                    <span><i class="fa-solid fa-cubes-stacked text-warning me-1"></i>Ressources Palier ${targetLvl} :</span>
                    <span class="font-monospace fw-bold">${progressPercent}% Réunies</span>
                </div>
                <div class="progress" style="height:10px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width:${progressPercent}%"></div>
                </div>
            </div>
            ` : ''}

            <div class="d-flex gap-2 justify-content-end flex-wrap pt-2 border-top">
                <a href="?page=imperial_dojo" class="btn btn-warning font-weight-bold text-dark"><i class="fa-solid fa-torii-gate me-1"></i>Ouvrir le Grand Dōjō</a>
                <a href="?page=fleet&target_x=0&target_y=0&mission=dojo_reinforce" class="btn btn-primary"><i class="fa-solid fa-shield me-1"></i>Renforcer la Garnison</a>
                <a href="?page=fleet&target_x=0&target_y=0&mission=dojo_siege" class="btn btn-danger"><i class="fa-solid fa-bomb me-1"></i>Siège Destructeur</a>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Terres Vierges Naturelles (Plaines, Collines, Lacs, etc.) ─────────────
    if (data.empty) {
        const isHills = (data.terrain_type === 'hills' || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('colline') ||
            data.terrain_name.toLowerCase().includes('coteau') ||
            data.terrain_name.toLowerCase().includes('terrasse')
        )));
        const isMountain = (data.terrain_type === 'mountain' || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('montagne') ||
            data.terrain_name.toLowerCase().includes('pic') ||
            data.terrain_name.toLowerCase().includes('rocheux')
        )));
        const isForest = (data.terrain_type === 'forest' || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('forêt') ||
            data.terrain_name.toLowerCase().includes('cèdre') ||
            data.terrain_name.toLowerCase().includes('bois')
        )));
        const isLake = !isHills && !isMountain && !isForest && (data.terrain_type === 'lake' || data.is_lake || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('lac') ||
            /\beaux?\b/i.test(data.terrain_name)
        )));

        const currentStock = (data.fish_stock !== null && data.fish_stock !== undefined) ? parseInt(data.fish_stock, 10) : 0;
        const initialStock = (data.fish_initial !== null && data.fish_initial !== undefined) ? parseInt(data.fish_initial, 10) : (currentStock || 25000);
        const isDepleted = data.lake_depleted || (currentStock <= 0);

        const sulfurStock = (data.sulfur_stock !== null && data.sulfur_stock !== undefined) ? parseInt(data.sulfur_stock, 10) : 0;
        const sulfurInit = (data.sulfur_initial !== null && data.sulfur_initial !== undefined) ? parseInt(data.sulfur_initial, 10) : (sulfurStock || 10000);
        const nitrateStock = (data.nitrate_stock !== null && data.nitrate_stock !== undefined) ? parseInt(data.nitrate_stock, 10) : 0;
        const nitrateInit = (data.nitrate_initial !== null && data.nitrate_initial !== undefined) ? parseInt(data.nitrate_initial, 10) : (nitrateStock || 10000);
        const ironSandStock = (data.iron_sand_stock !== null && data.iron_sand_stock !== undefined) ? parseInt(data.iron_sand_stock, 10) : 0;
        const ironSandInit = (data.iron_sand_initial !== null && data.iron_sand_initial !== undefined) ? parseInt(data.iron_sand_initial, 10) : (ironSandStock || 15000);
        const isMountainDepleted = !!data.mountain_depleted || (sulfurStock <= 0 && nitrateStock <= 0 && ironSandStock <= 0);

        if (!data.rural_plots || Object.keys(data.rural_plots).length === 0) {
            data.rural_plots = getDeterministicPlotPotentials(data.coord_x, data.coord_y);
        }
        const ruralPlotsHtml = renderRuralPlotsGrid(data.rural_plots);

        let catBadgeHtml = '';
        let tileTitle = data.terrain_name || '';
        let tileSubtitle = data.terrain_desc || '';

        if (isLake) {
            catBadgeHtml = '<span class="badge bg-cyan text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-water me-1"></i>LAC NATUREL</span>';
            tileTitle = tileTitle || "Lac d'Eaux Vives";
            tileSubtitle = tileSubtitle || "Étendue d'eau douce et sanctuaire naturel halieutique protégé.";
        } else if (isMountain) {
            catBadgeHtml = '<span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-mountain me-1"></i>PICS & MONTAGNES</span>';
            tileTitle = tileTitle || "Pics Rocheux & Montagnes";
            tileSubtitle = tileSubtitle || "Massif rocheux et crêtes volcaniques sanctuarisées riches en minerais.";
        } else if (isForest) {
            catBadgeHtml = '<span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-tree me-1"></i>FORÊT FÉODALE</span>';
            tileTitle = tileTitle || "Forêt Sauvage de Cèdres";
            tileSubtitle = tileSubtitle || "Forêt dense de conifères et bois de construction.";
        } else if (isHills) {
            catBadgeHtml = '<span class="badge bg-lime text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-mound me-1"></i>COLLINES</span>';
            tileTitle = tileTitle || "Collines & Coteaux";
            tileSubtitle = tileSubtitle || "Coteaux fertiles et gisements argileux.";
        } else {
            catBadgeHtml = '<span class="badge bg-primary text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-wheat-awn me-1"></i>TERRE LIBRE</span>';
            tileTitle = tileTitle || "Terres Vierges Inoccupées";
            tileSubtitle = tileSubtitle || "Plaines fertiles et terroirs prospères du Japon féodal, prêts à être défrichés.";
        }

        setMapModalHeader(modalImg, catBadgeHtml, tileTitle, tileSubtitle, `[${data.coord_x} : ${data.coord_y}]`);

        body.innerHTML = `
            <!-- Statut de Propriété -->
            <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded border">
                <div>
                    <span class="text-secondary small d-block" style="font-size:0.75rem;">Statut de Propriété :</span>
                    ${isLake
                        ? '<strong class="text-cyan"><i class="fa-solid fa-water me-1"></i>Eaux Libres &bull; Non Annexable</strong>'
                        : (isMountain 
                            ? '<strong class="text-warning"><i class="fa-solid fa-mountain me-1"></i>Sanctuaire Minéral &bull; Non Annexable</strong>'
                            : '<strong class="text-success"><i class="fa-solid fa-lock-open me-1"></i>Terre Libre &bull; Domaine Non Réclamé</strong>')
                    }
                </div>
                <div class="text-end">
                    <span class="text-secondary small d-block" style="font-size:0.75rem;">Propriétaire Foncier :</span>
                    <span class="badge bg-secondary-lt text-dark">Aucun seigneur féodal</span>
                </div>
            </div>

            ${isLake 
                ? renderLakeFishCounter(currentStock, initialStock, isDepleted) 
                : (isMountain 
                    ? renderMountainMineralsCounter(sulfurStock, sulfurInit, nitrateStock, nitrateInit, ironSandStock, ironSandInit, isMountainDepleted)
                    : `
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-3 p-2.5" style="font-size:0.85rem; border-radius:6px;">
                            <span style="font-size:1.3rem;"><i class="fa-solid fa-sparkles text-warning"></i></span>
                            <div>
                                <strong>Emplacement Disponible pour Nouveau Fief !</strong><br>
                                Vous pouvez y dépêcher une expédition avec un <strong>Pionnier Féodal (Colon <i class="fa-solid fa-torii-gate text-danger"></i>)</strong> pour y fonder votre prochain domaine castral.
                            </div>
                        </div>
                        ${ruralPlotsHtml}
                    `)
            }

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top mt-2">
                <button type="button" class="btn btn-outline-secondary" onclick="closeMapModal()">Fermer</button>
                ${isLake ? (
                    isDepleted ? `
                        <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                            <i class="fa-solid fa-ban me-1"></i>Réserve de Poisson Épuisée
                        </button>
                    ` : `
                        <button type="button" class="btn btn-cyan text-white font-weight-bold" onclick="openQuickFishingModal(${data.coord_x}, ${data.coord_y}, null, null, '${(tileTitle).replace(/'/g, "\\'")}', ${currentStock})">
                            <i class="fa-solid fa-fish me-1"></i>Dépêcher une Expédition de Pêche &rarr;
                        </button>
                    `
                ) : (isMountain ? (
                    isMountainDepleted ? `
                        <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                            <i class="fa-solid fa-ban me-1"></i>Filon Minéral Épuisé
                        </button>
                    ` : `
                        <button type="button" class="btn btn-warning text-dark font-weight-bold shadow-sm" onclick="openQuickMiningModal(${data.coord_x}, ${data.coord_y}, null, null, '${(tileTitle).replace(/'/g, "\\'")}', ${sulfurStock}, ${nitrateStock}, ${ironSandStock})">
                            <i class="fa-solid fa-pickaxe me-1"></i>Dépêcher des Mineurs (Expédition Minière) &rarr;
                        </button>
                    `
                ) : `
                    <a href="?page=fleet&target_x=${data.coord_x}&target_y=${data.coord_y}&mission=colonize" class="btn btn-primary font-weight-bold">
                        <i class="fa-solid fa-chess-rook me-1"></i>Fonder un Fief ici avec un Pionnier &rarr;
                    </a>
                `)}
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Donjons Authentiques (現存十二天守) ─────────────────────
    if (data.is_authentic_castle) {
        setMapModalHeader(
            modalImg,
            '<span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-crown me-1"></i>TRÉSOR NATIONAL &bull; DONJON AUTHENTIQUE</span>',
            `${escapeHtml(data.castle_name)} <span class="small text-white-50 fw-normal">${escapeHtml(data.castle_kanji || '')}</span>`,
            `Province de ${escapeHtml(data.castle_province || 'Historique')} &bull; Bâtisseur : ${escapeHtml(data.castle_builder || 'Maître Féodal')}`,
            `[${data.coord_x} : ${data.coord_y}]`
        );

        body.innerHTML = `
            <div class="alert alert-warning py-2.5 px-3 mb-3 small d-flex align-items-center gap-2 rounded-2">
                <i class="fa-solid fa-shield-halved text-warning fs-3 flex-shrink-0"></i>
                <div>
                    <strong class="text-dark d-block">Forteresse Historique d'Époque Sengoku-Edo :</strong>
                    Ce donjon préservé est l'une des 12 forteresses d'origine du Japon féodal (現存十二天守). Enjeu territorial suprême de la fin du monde féodal.
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center p-2 mb-3 bg-light rounded border small">
                <span class="text-secondary">Puissance Défensive Antique :</span>
                <strong class="text-success"><i class="fa-solid fa-shield me-1"></i>25 000 pts de garnison d'élite</strong>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                <a href="/?page=castle&code=${encodeURIComponent(data.castle_code || '')}"
                   class="btn btn-warning text-dark font-weight-bold">
                    <i class="fa-solid fa-scroll me-1"></i>Découvrir l'Histoire &rarr;
                </a>
                <div class="d-flex gap-2">
                    ${data.planet_id ? `
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=spy"  class="btn btn-outline-secondary"><i class="fa-solid fa-user-ninja me-1"></i>Sonder</a>
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=raid" class="btn btn-danger"><i class="fa-solid fa-khanda me-1"></i>Assaillir</a>
                    ` : `<span class="text-secondary small align-self-center">Sanctuaire Inviolé</span>`}
                </div>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Oasis Naturelle ─────────────────────────────────────────
    if (data.is_oasis) {
        const isHillsOasis  = (data.oasis_type && data.oasis_type.indexOf('hills') !== -1) ||
                              (data.oasis_name && (
                                  data.oasis_name.toLowerCase().includes('colline') ||
                                  data.oasis_name.toLowerCase().includes('coteau')
                              ));
        const isLakeOasis   = !isHillsOasis && ((data.oasis_type && data.oasis_type.indexOf('lake') !== -1) ||
                              (data.oasis_name && (
                                  data.oasis_name.toLowerCase().includes('lac') ||
                                  /\beaux?\b/i.test(data.oasis_name)
                              )));
        const currentStock  = (data.fish_stock !== null && data.fish_stock !== undefined) ? parseInt(data.fish_stock, 10) : 0;
        const initialStock  = (data.fish_initial !== null && data.fish_initial !== undefined) ? parseInt(data.fish_initial, 10) : (currentStock || 25000);
        const isLakeDepleted = data.lake_depleted || (currentStock <= 0);
        const isOccupied    = (data.is_occupied == 1);
        const isOwn         = (data.owner_planet_id && parseInt(data.owner_planet_id, 10) === <?= $planetId ?>);
        const units         = data.units || [];
        const hasWildBeasts = units.some(u => u.is_wild == 1 && parseInt(u.count, 10) > 0);

        let statusBadge = '';
        if (isLakeOasis) {
            statusBadge = '<span class="badge bg-cyan text-white font-weight-bold" style="font-size:0.75rem;"><i class="fa-solid fa-water me-1"></i>LAC NATUREL — EAUX LIBRES NON-ANNEXABLE</span>';
        } else if (isOwn) {
            statusBadge = '<span class="badge bg-success text-white font-weight-bold" style="font-size:0.75rem;"><i class="fa-solid fa-seedling me-1"></i>VOTRE FIEF NATUREL ANNEXÉ</span>';
        } else if (isOccupied) {
            statusBadge = `<span class="badge bg-primary text-white font-weight-bold" style="font-size:0.75rem;"><i class="fa-solid fa-shield-halved me-1"></i>OCCUPÉE PAR ${data.username || 'UN DAIMYŌ'}</span>`;
        } else if (hasWildBeasts) {
            statusBadge = '<span class="badge bg-danger text-white font-weight-bold" style="font-size:0.75rem;"><i class="fa-solid fa-paw me-1"></i>OASIS SAUVAGE — FAUNE HOSTILE</span>';
        } else {
            statusBadge = '<span class="badge bg-teal text-white font-weight-bold" style="font-size:0.75rem;"><i class="fa-solid fa-sparkles me-1"></i>OASIS LIBRE D\'OCCUPATION</span>';
        }

        let bonusBadgesHtml = '';
        if (data.bonus_rice  > 0) bonusBadgesHtml += `<span class="badge bg-warning-lt border border-warning" style="font-size:0.78rem;"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>+${data.bonus_rice}% Riz</span>`;
        if (data.bonus_wood  > 0) bonusBadgesHtml += `<span class="badge bg-success-lt border border-success" style="font-size:0.78rem;"><i class="fa-solid fa-tree text-success me-1"></i>+${data.bonus_wood}% Bois</span>`;
        if (data.bonus_stone > 0) bonusBadgesHtml += `<span class="badge bg-primary-lt border border-primary" style="font-size:0.78rem;"><i class="fa-solid fa-mountain text-primary me-1"></i>+${data.bonus_stone}% Pierre</span>`;

        let unitsHtml = '';
        if (units.length > 0) {
            unitsHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
            units.forEach(u => {
                const uCount = parseInt(u.count, 10);
                if (uCount <= 0) return;
                unitsHtml += `
                    <div class="d-flex align-items-center gap-2 bg-white border rounded px-2 py-1 small">
                        <span style="font-size:1.1rem;">${u.icon || (u.is_wild == 1 ? '<i class="fa-solid fa-paw text-warning"></i>' : '<i class="fa-solid fa-khanda text-danger"></i>')}</span>
                        <span class="font-weight-medium" style="color:#1e293b;">${u.unit_name || u.name}</span>
                        <strong class="text-danger ms-1">x${uCount}</strong>
                    </div>
                `;
            });
            unitsHtml += '</div>';
        } else {
            unitsHtml = '<p class="text-success small my-2"><i class="fa-solid fa-dove me-1"></i>Aucun animal sauvage ni soldat en garnison. L\'oasis est entièrement pacifiée !</p>';
        }

        const catBadge = isLakeOasis
            ? '<span class="badge bg-cyan text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-water me-1"></i>OASIS LACUSTRE</span>'
            : '<span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-seedling me-1"></i>OASIS NATURELLE</span>';

        setMapModalHeader(
            modalImg,
            catBadge,
            escapeHtml(data.oasis_name),
            isLakeOasis ? "Sanctuaire halieutique protégé &bull; Eaux poissonneuses" : (data.bonus_label || "Sanctuaire féodal de récoltes"),
            `[${data.coord_x} : ${data.coord_y}]`
        );

        body.innerHTML = `
            <div class="d-flex gap-1 flex-wrap align-items-center mb-3">
                ${statusBadge}
                ${bonusBadgesHtml}
            </div>

            <p style="color:${isLakeOasis ? '#0f766e' : '#166534'}; font-size:0.9rem; margin:0 0 0.85rem 0; line-height:1.45;">
                ${isLakeOasis 
                    ? 'Ce lac naturel est un sanctuaire d\'eaux calmes et poissonneuses. <strong>Cette parcelle ne peut pas être annexée.</strong> Dépêchez vos Pêcheurs Féodaux formés au Dojo en expédition de pêche : les prises sont entreposées dans votre Grenier à Riz Fortifié.'
                    : 'Cette oasis naturelle regorge de terres fertiles et de ressources précieuses. <strong>Pillez les récoltes</strong> en terrassant la faune, ou <strong>occupez le territoire</strong> pour conférer des bonus permanents à votre fief.'
                }
            </p>

            ${isLakeOasis ? renderLakeFishCounter(currentStock, initialStock, isLakeDepleted) : ''}

            <div class="bg-light border rounded p-2 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                <span class="text-secondary font-weight-medium">Ressources &amp; Récoltes :</span>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    ${isLakeOasis ? `<span style="color:#0891b2;"><i class="fa-solid fa-fish text-cyan me-1"></i><strong>${currentStock.toLocaleString()}</strong> Poisson</span>` : ''}
                    <span style="color:#b45309;"><i class="fa-solid fa-tree text-success me-1"></i><strong>${(data.res_wood  || 0).toLocaleString()}</strong></span>
                    <span style="color:#2563eb;"><i class="fa-solid fa-mountain text-secondary me-1"></i><strong>${(data.res_stone || 0).toLocaleString()}</strong></span>
                    <span style="color:#15803d;"><i class="fa-solid fa-wheat-awn text-warning me-1"></i><strong>${(data.res_rice  || 0).toLocaleString()}</strong></span>
                </div>
            </div>

            <div class="mb-3">
                <span class="text-secondary font-weight-bold text-uppercase small" style="letter-spacing:0.5px; font-size:0.75rem;">Faune &amp; Garnison :</span>
                ${unitsHtml}
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=raid"   class="btn btn-warning font-weight-bold"><i class="fa-solid fa-khanda me-1"></i>Piller l'Oasis</a>
                    <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=attack" class="btn btn-danger font-weight-bold"><i class="fa-solid fa-bomb me-1"></i>Nettoyer</a>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    ${isLakeOasis ? (
                        isLakeDepleted ? `
                            <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                                <i class="fa-solid fa-ban me-1"></i>Réserve de Poisson Épuisée
                            </button>
                        ` : `
                            <button type="button" class="btn btn-cyan font-weight-bold text-white" onclick="openQuickFishingModal(${data.coord_x}, ${data.coord_y}, null, ${data.oasis_id}, '${(data.oasis_name || 'Oasis Lacustre').replace(/'/g, "\\'")}', ${currentStock})">
                                <i class="fa-solid fa-fish me-1"></i>Dépêcher des Pêcheurs &rarr;
                            </button>
                        `
                    ) : (isOwn ? `
                        <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=occupy" class="btn btn-primary"><i class="fa-solid fa-shield-halved me-1"></i>Renforcer</a>
                        <button onclick="abandonOasisDirect(${data.oasis_id})" class="btn btn-outline-danger"><i class="fa-solid fa-flag text-danger me-1"></i>Abandonner</button>
                    ` : `
                        <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=occupy" class="btn btn-success font-weight-bold"><i class="fa-solid fa-flag me-1"></i>Occuper</a>
                        ${!hasWildBeasts ? `<button onclick="annexOasisDirect(${data.oasis_id})" class="btn btn-primary font-weight-bold"><i class="fa-solid fa-sparkles me-1"></i>Annexion Directe</button>` : ''}
                    `)}
                </div>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Terres Neutres (planète sans joueur ou parcelle libre répertoriée) ─────
    if (!data.user_id) {
        const isHillsPlanet     = (data.terrain_type === 'hills' || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('colline') ||
            data.terrain_name.toLowerCase().includes('coteau') ||
            data.terrain_name.toLowerCase().includes('terrasse')
        )) || (data.planet_name && (
            data.planet_name.toLowerCase().includes('colline') ||
            data.planet_name.toLowerCase().includes('coteau') ||
            data.planet_name.toLowerCase().includes('terrasse')
        )));
        const isMountainPlanet  = (data.terrain_type === 'mountain' || (data.terrain_name && data.terrain_name.toLowerCase().includes('montagne')) || data.is_mountain);
        const isForestPlanet    = (data.terrain_type === 'forest' || (data.terrain_name && (data.terrain_name.toLowerCase().includes('forêt') || data.terrain_name.toLowerCase().includes('cèdre'))));
        const isLakePlanet      = !isHillsPlanet && !isMountainPlanet && !isForestPlanet && (data.terrain_type === 'lake' || data.planet_type === 'oceanic' || data.is_lake || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('lac') ||
            /\beaux?\b/i.test(data.terrain_name)
        )) || (data.planet_name && (
            data.planet_name.toLowerCase().includes('lac') ||
            /\beaux?\b/i.test(data.planet_name)
        )));

        const currentStock  = (data.fish_stock !== null && data.fish_stock !== undefined) ? parseInt(data.fish_stock, 10) : 0;
        const initialStock  = (data.fish_initial !== null && data.fish_initial !== undefined) ? parseInt(data.fish_initial, 10) : (currentStock || 25000);
        const isDepleted    = data.lake_depleted || (currentStock <= 0);

        const sulfurStock   = (data.sulfur_stock !== null && data.sulfur_stock !== undefined) ? parseInt(data.sulfur_stock, 10) : 0;
        const sulfurInit    = (data.sulfur_initial !== null && data.sulfur_initial !== undefined) ? parseInt(data.sulfur_initial, 10) : (sulfurStock || 10000);
        const nitrateStock  = (data.nitrate_stock !== null && data.nitrate_stock !== undefined) ? parseInt(data.nitrate_stock, 10) : 0;
        const nitrateInit   = (data.nitrate_initial !== null && data.nitrate_initial !== undefined) ? parseInt(data.nitrate_initial, 10) : (nitrateStock || 10000);
        const ironSandStock = (data.iron_sand_stock !== null && data.iron_sand_stock !== undefined) ? parseInt(data.iron_sand_stock, 10) : 0;
        const ironSandInit  = (data.iron_sand_initial !== null && data.iron_sand_initial !== undefined) ? parseInt(data.iron_sand_initial, 10) : (ironSandStock || 15000);
        const isMountainDepleted = !!data.mountain_depleted || (sulfurStock <= 0 && nitrateStock <= 0 && ironSandStock <= 0);

        if (!data.rural_plots || Object.keys(data.rural_plots).length === 0) {
            data.rural_plots = getDeterministicPlotPotentials(data.coord_x, data.coord_y);
        }
        const ruralPlotsHtml = renderRuralPlotsGrid(data.rural_plots);

        const catBadge = isLakePlanet
            ? '<span class="badge bg-cyan text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-water me-1"></i>EAUX COMMUNAUTAIRES</span>'
            : (isMountainPlanet
                ? '<span class="badge bg-warning text-dark fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-mountain me-1"></i>SANCTUAIRE DES CIMES</span>'
                : (isHillsPlanet
                    ? '<span class="badge bg-lime text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-mound me-1"></i>COLLINES</span>'
                    : (isForestPlanet
                        ? '<span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-tree me-1"></i>FORÊT FÉODALE</span>'
                        : '<span class="badge bg-primary text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-compass me-1"></i>DOMAINE LIBRE</span>')));

        const tName = data.planet_name || (isLakePlanet ? "Lac d'Eaux Vives" : (isMountainPlanet ? "Pics Rocheux & Montagnes" : (isHillsPlanet ? "Collines & Coteaux" : (isForestPlanet ? "Forêt de Cèdres (Sugi)" : "Domaine Libre"))));
        const tDesc = `Type de terrain : ${data.planet_type || (isLakePlanet ? 'oceanic' : (isMountainPlanet ? 'mountain' : (isHillsPlanet ? 'hills' : (isForestPlanet ? 'forest' : 'terrestrial'))))}`;

        setMapModalHeader(modalImg, catBadge, escapeHtml(tName), tDesc, `[${data.coord_x} : ${data.coord_y}]`);

        body.innerHTML = `
            <!-- Statut de Propriété -->
            <div class="d-flex align-items-center justify-content-between p-2 mb-3 bg-light rounded border">
                <div>
                    <span class="text-secondary small d-block" style="font-size:0.75rem;">Statut de Propriété :</span>
                    ${isLakePlanet
                        ? '<strong class="text-cyan"><i class="fa-solid fa-water me-1"></i>Eaux Libres &bull; Non Annexable</strong>'
                        : (isMountainPlanet
                            ? '<strong class="text-warning"><i class="fa-solid fa-mountain me-1"></i>Sanctuaire Minéral &bull; Non Annexable</strong>'
                            : '<strong class="text-success"><i class="fa-solid fa-lock-open me-1"></i>Terre Libre &bull; Domaine Non Réclamé</strong>')
                    }
                </div>
                <div class="text-end">
                    <span class="text-secondary small d-block" style="font-size:0.75rem;">Propriétaire Foncier :</span>
                    <span class="badge bg-secondary-lt text-dark">Aucun seigneur féodal</span>
                </div>
            </div>

            ${isLakePlanet 
                ? renderLakeFishCounter(currentStock, initialStock, isDepleted) 
                : (isMountainPlanet 
                    ? renderMountainMineralsCounter(sulfurStock, sulfurInit, nitrateStock, nitrateInit, ironSandStock, ironSandInit, isMountainDepleted)
                    : `
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-3 p-2.5" style="font-size:0.85rem; border-radius:6px;">
                            <span style="font-size:1.3rem;"><i class="fa-solid fa-sparkles text-warning"></i></span>
                            <div>
                                <strong>Emplacement Libre pour Colonisation !</strong><br>
                                Vous pouvez y envoyer un <strong>Pionnier Féodal (Colon <i class="fa-solid fa-torii-gate text-danger"></i>)</strong> pour annexer ce terroir et fonder une colonie.
                            </div>
                        </div>
                        ${ruralPlotsHtml}
                    `)
            }

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top mt-2">
                <button type="button" class="btn btn-outline-secondary" onclick="closeMapModal()">Fermer</button>
                <div class="d-flex gap-2">
                    ${isLakePlanet ? (
                        isDepleted ? `
                            <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                                <i class="fa-solid fa-ban me-1"></i>Réserve de Poisson Épuisée
                            </button>
                        ` : `
                            <button type="button" class="btn btn-cyan font-weight-bold text-white" onclick="openQuickFishingModal(${data.coord_x}, ${data.coord_y}, ${data.planet_id || 'null'}, null, '${(tName).replace(/'/g, "\\'")}', ${currentStock})">
                                <i class="fa-solid fa-fish me-1"></i>Dépêcher une Expédition de Pêche &rarr;
                            </button>
                        `
                    ) : (isMountainPlanet ? (
                        isMountainDepleted ? `
                            <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                                <i class="fa-solid fa-ban me-1"></i>Filon Minéral Épuisé
                            </button>
                        ` : `
                            <button type="button" class="btn btn-warning text-dark font-weight-bold shadow-sm" onclick="openQuickMiningModal(${data.coord_x}, ${data.coord_y}, ${data.planet_id || 'null'}, null, '${(tName).replace(/'/g, "\\'")}', ${sulfurStock}, ${nitrateStock}, ${ironSandStock})">
                                <i class="fa-solid fa-pickaxe me-1"></i>Dépêcher des Mineurs (Expédition Minière) &rarr;
                            </button>
                        `
                    ) : ((parseInt(data.coord_x, 10) === 0 && parseInt(data.coord_y, 10) === 0) ? `
                        <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                            <i class="fa-solid fa-ban me-1"></i>Sanctuaire Non Annexable
                        </button>
                    ` : `
                        <a href="?page=fleet&target_id=${data.planet_id || ''}&target_x=${data.coord_x}&target_y=${data.coord_y}&mission=colonize" class="btn btn-primary font-weight-bold">
                            <i class="fa-solid fa-chess-rook me-1"></i>Fonder un Fief ici avec un Pionnier &rarr;
                        </a>
                    `))}
                </div>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Fief d'un joueur ────────────────────────────────────────
    const isOwn = (parseInt(data.user_id, 10) === <?= $userId ?>);
    const fKey  = (data.faction || 'terran').toLowerCase();
    const fName = (data.faction || 'Terran').toUpperCase();
    const pts   = (data.points != null) ? Number(data.points).toLocaleString() : '0';
    const targetUserId = parseInt(data.user_id, 10) || 0;

    const catBadge = isOwn
        ? '<span class="badge bg-success text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-chess-rook me-1"></i>VOTRE DOMAINE CASTRAL</span>'
        : '<span class="badge bg-danger text-white fw-bold px-2 py-1 shadow-sm" style="font-size:0.75rem;"><i class="fa-solid fa-khanda me-1"></i>FIEF ENNEMI</span>';

    setMapModalHeader(
        modalImg,
        catBadge,
        escapeHtml(data.planet_name),
        `Daimyō : <a href="javascript:void(0)" onclick="openPlayerProfileModal(${targetUserId})" class="text-white text-decoration-underline fw-bold">${escapeHtml(data.username || 'Daimyō')}</a> ${data.alliance_tag ? '<span class="badge bg-warning-lt text-dark ms-1">[' + escapeHtml(data.alliance_tag) + ']</span>' : ''}`,
        `[${data.coord_x} : ${data.coord_y}]`
    );

    body.innerHTML = `
        ${data.is_protected ? `
            <div style="background:#f0fdf4; border:1px solid #86efac; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.1rem; display:flex; align-items:center; gap:0.75rem;">
                <span style="font-size:1.6rem; flex-shrink:0;"><i class="fa-solid fa-shield-halved text-success"></i></span>
                <div>
                    <strong style="color:#15803d; font-size:0.9rem;">Immunité Féodale des Nouveaux Joueurs</strong>
                    <div style="color:#166534; font-size:0.8rem; margin-top:0.15rem;">Ce daimyō est protégé jusqu'au ${data.protection_until || '7 jours'}${data.protection_remaining ? ' (encore ' + data.protection_remaining + ')' : ''}. Les assauts, pillages et espionnages shinobi sont neutralisés.</div>
                </div>
            </div>
        ` : ''}

        <div class="p-3 bg-light rounded border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="text-secondary small"><i class="fa-solid fa-flag me-1 text-primary"></i>Clan Féodal :</span>
                <strong class="text-dark font-monospace">${fName}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="text-secondary small"><i class="fa-solid fa-trophy me-1 text-warning"></i>Puissance du Daimyō :</span>
                <strong class="text-danger font-monospace">${pts} pts</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-secondary small"><i class="fa-solid fa-shield-halved me-1 text-info"></i>Statut de Sécurité :</span>
                <span>${data.is_protected ? '<span class="badge bg-success text-white">Sous Immunité</span>' : '<span class="badge bg-danger-lt text-danger">Vulnérable aux Raids</span>'}</span>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center pt-2 border-top">
            <button type="button" onclick="openPlayerProfileModal(${targetUserId})" class="btn btn-outline-danger">
                <i class="fa-solid fa-user me-1"></i>Fiche Daimyō
            </button>
            ${!isOwn ? (data.is_protected ? `
                <span class="badge bg-success-lt font-weight-bold py-2 px-3" title="Ce fief est inviolable sous immunité débutant">
                    <i class="fa-solid fa-shield-halved text-warning me-1"></i>Fief sous Immunité
                </span>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=transport" class="btn btn-outline-secondary"><i class="fa-solid fa-boxes-stacked me-1"></i>Convoi</a>
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(data.username || '')}" class="btn btn-outline-secondary"><i class="fa-solid fa-envelope me-1"></i>Missive</a>
            ` : `
                <a href="?page=fleet&target_id=${data.planet_id}&mission=spy"       class="btn btn-outline-secondary"><i class="fa-solid fa-user-ninja me-1"></i>Espionner</a>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=raid"      class="btn btn-danger"><i class="fa-solid fa-khanda me-1"></i>Raid</a>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=transport" class="btn btn-outline-secondary"><i class="fa-solid fa-boxes-stacked me-1"></i>Convoi</a>
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(data.username || '')}" class="btn btn-outline-secondary"><i class="fa-solid fa-envelope me-1"></i>Missive</a>
            `) : `
                <span class="badge bg-success-lt font-weight-bold py-2 px-3">
                    <i class="fa-solid fa-chess-rook text-primary me-1"></i>Votre propre domaine castral
                </span>
            `}
        </div>
    `;
    openMapModal();
};

window.annexOasisDirect = async function(oasisId) {
    try {
        const formData = new FormData();
        formData.append('action', 'annex');
        formData.append('oasis_id', oasisId);
        const res  = await fetch('/api/oasis.php', { method: 'POST', body: formData });
        const data = await res.json();
        closeMapModal();
        if (data.success) {
            await showModalAlert(data.message, 'success', 'Annexion Féodale Réussie');
            if (galaxyMap) galaxyMap.fetchMapData();
        } else {
            showModalAlert(data.message || data.error || 'Erreur lors de l\'annexion.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le conseil féodal.', 'error');
    }
};

window.abandonOasisDirect = async function(oasisId) {
    if (!confirm('Êtes-vous certain de vouloir abandonner cette oasis ? Vous perdrez les bonus de récolte associés.')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'abandon');
        formData.append('oasis_id', oasisId);
        const res  = await fetch('/api/oasis.php', { method: 'POST', body: formData });
        const data = await res.json();
        closeMapModal();
        if (data.success) {
            await showModalAlert(data.message, 'info', 'Oasis Abandonnée');
            if (galaxyMap) galaxyMap.fetchMapData();
        } else {
            showModalAlert(data.message || data.error || 'Erreur lors de l\'abandon.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le conseil féodal.', 'error');
    }
};

// ── Fonctions Expédition de Pêche Rapide Directe depuis la Carte ──────────
window.openQuickFishingModal = function(targetX, targetY, planetId, oasisId, targetName, fishStock) {
    closeMapModal();
    currentFishingTarget = {
        x: parseInt(targetX, 10),
        y: parseInt(targetY, 10),
        planetId: planetId ? parseInt(planetId, 10) : null,
        oasisId: oasisId ? parseInt(oasisId, 10) : null,
        name: targetName || "Lac d'Eaux Vives",
        stock: (fishStock !== null && fishStock !== undefined) ? parseInt(fishStock, 10) : null
    };

    // Remplir les informations textuelles
    const nameEl = document.getElementById('fishingModalDestName');
    if (nameEl) nameEl.textContent = currentFishingTarget.name;

    const coordsEl = document.getElementById('fishingModalDestCoords');
    if (coordsEl) coordsEl.textContent = `[${currentFishingTarget.x} : ${currentFishingTarget.y}]`;

    const dx = currentFishingTarget.x - mapPlayerX;
    const dy = currentFishingTarget.y - mapPlayerY;
    const dist = Math.sqrt(dx * dx + dy * dy);
    const distEl = document.getElementById('fishingModalDist');
    if (distEl) distEl.textContent = `${dist.toFixed(1)} lieue(s)`;

    const stockEl = document.getElementById('fishingModalStockBadge');
    if (stockEl) {
        stockEl.innerHTML = `<i class="fa-solid fa-fish me-1"></i>Stock : ${currentFishingTarget.stock !== null ? Number(currentFishingTarget.stock).toLocaleString() : 'Abondant'}`;
    }

    const availCountEl = document.getElementById('fishingModalAvailCount');
    if (availCountEl) availCountEl.textContent = `${stationedPecheurs.toLocaleString()} Ryōshi`;

    const availNumEl = document.getElementById('fishingModalAvailNum');
    if (availNumEl) availNumEl.textContent = stationedPecheurs;

    const inputCount = document.getElementById('inputFishingCount');
    if (inputCount) {
        inputCount.max = stationedPecheurs;
        inputCount.value = stationedPecheurs > 0 ? Math.min(stationedPecheurs, 5) : 0;
    }

    const noAlert = document.getElementById('fishingNoUnitsAlert');
    if (noAlert) {
        if (stationedPecheurs > 0) noAlert.classList.add('d-none');
        else noAlert.classList.remove('d-none');
    }

    const launchBtn = document.getElementById('btnLaunchQuickFishing');
    if (launchBtn) {
        launchBtn.disabled = (stationedPecheurs <= 0);
        launchBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Dépêcher l\'Expédition de Pêche';
    }

    updateFishingEstimates();

    const fModal = document.getElementById('mapFishingModal');
    if (fModal) {
        fModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeQuickFishingModal = function() {
    const fModal = document.getElementById('mapFishingModal');
    if (fModal) fModal.style.display = 'none';
    document.body.style.overflow = '';
};

// Fermeture avec Échap aussi pour le modal de pêche
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeQuickFishingModal();
});

window.adjustFishingCount = function(delta) {
    const input = document.getElementById('inputFishingCount');
    if (!input || stationedPecheurs <= 0) return;
    let val = (parseInt(input.value, 10) || 0) + delta;
    val = Math.max(1, Math.min(val, stationedPecheurs));
    input.value = val;
    updateFishingEstimates();
};

window.setFishingCountMax = function() {
    const input = document.getElementById('inputFishingCount');
    if (!input || stationedPecheurs <= 0) return;
    input.value = stationedPecheurs;
    updateFishingEstimates();
};

window.updateFishingEstimates = function() {
    const input = document.getElementById('inputFishingCount');
    if (!input) return;
    let count = parseInt(input.value, 10) || 0;
    if (stationedPecheurs > 0) {
        count = Math.max(1, Math.min(count, stationedPecheurs));
    } else {
        count = 0;
    }
    input.value = count;

    // Calculs
    let estFish = count * 20;
    if (currentFishingTarget && currentFishingTarget.stock !== null) {
        estFish = Math.min(estFish, currentFishingTarget.stock);
    }
    const catchEl = document.getElementById('fishingEstCatch');
    if (catchEl) catchEl.textContent = `${estFish.toLocaleString()} Poisson${estFish > 1 ? 's' : ''}`;

    const cargoCap = count * 100;
    const cargoEl = document.getElementById('fishingEstCargo');
    if (cargoEl) cargoEl.textContent = `${cargoCap.toLocaleString()} unités`;

    const durEl = document.getElementById('fishingEstDuration');
    if (durEl && currentFishingTarget) {
        const dx = currentFishingTarget.x - mapPlayerX;
        const dy = currentFishingTarget.y - mapPlayerY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        // Durée aller : vitesse 14 km/h
        const flightSec = Math.max(30, Math.round((10 + (3500 / 100) * Math.sqrt((10 * dist) / 14)) / mapGameSpeed));
        const siteSec = Math.max(60, Math.round(3600 / mapGameSpeed));
        const totalSec = (flightSec * 2) + siteSec;

        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        const h = Math.floor(m / 60);
        const remM = m % 60;
        if (h > 0) {
            durEl.textContent = `${h}h ${remM.toString().padStart(2, '0')}m ${s.toString().padStart(2, '0')}s`;
        } else {
            durEl.textContent = `${m}m ${s.toString().padStart(2, '0')}s`;
        }
    }
};

window.submitQuickFishing = async function() {
    if (!currentFishingTarget) return;
    const input = document.getElementById('inputFishingCount');
    const count = parseInt(input ? input.value : 0, 10);
    if (!count || count <= 0) {
        showModalAlert('Veuillez mobiliser au moins 1 Pêcheur Féodal.', 'warning', 'Effectif Requis');
        return;
    }
    if (count > stationedPecheurs) {
        showModalAlert('Votre garnison ne compte pas assez de pêcheurs disponibles.', 'warning', 'Garnison Insuffisante');
        return;
    }

    const btn = document.getElementById('btnLaunchQuickFishing');
    const prevHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Dépêche en cours...';
    }

    try {
        const formData = new FormData();
        formData.append('target_x', currentFishingTarget.x);
        formData.append('target_y', currentFishingTarget.y);
        if (currentFishingTarget.planetId) {
            formData.append('target_planet_id', currentFishingTarget.planetId);
        }
        if (currentFishingTarget.oasisId) {
            formData.append('target_oasis_id', currentFishingTarget.oasisId);
        }
        formData.append('mission_type', 'fishing');
        formData.append('fleet[pecheur]', count);
        formData.append('cargo_metal', 0);
        formData.append('cargo_crystal', 0);
        formData.append('cargo_deuterium', 0);

        const res = await fetch('/api/fleet.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data && data.success) {
            stationedPecheurs = Math.max(0, stationedPecheurs - count);
            const availCountEl = document.getElementById('fishingModalAvailCount');
            if (availCountEl) availCountEl.textContent = `${stationedPecheurs.toLocaleString()} Ryōshi`;
            const availNumEl = document.getElementById('fishingModalAvailNum');
            if (availNumEl) availNumEl.textContent = stationedPecheurs;

            closeQuickFishingModal();
            await showModalAlert(
                `Expédition dépêchée avec succès ! ${count} Pêcheurs Féodaux font voile vers le lac [${currentFishingTarget.x} : ${currentFishingTarget.y}]. Les prises de poisson seront livrées directement dans votre Grenier à Riz Fortifié.`,
                'success',
                'Expédition Halieutique Dépêchée'
            );

            if (galaxyMap && typeof galaxyMap.fetchMapData === 'function') {
                galaxyMap.fetchMapData();
            }
        } else {
            showModalAlert(data.error || data.message || "Erreur lors de l'envoi de l'expédition de pêche.", 'error', 'Expédition Refusée');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = prevHtml;
            }
        }
    } catch (err) {
        showModalAlert("Erreur de communication avec le serveur féodal.", 'error', 'Erreur Réseau');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = prevHtml;
        }
    }
};

// ── Fonctions Expédition Minière Rapide Directe depuis la Carte ────────────
window.openQuickMiningModal = function(targetX, targetY, planetId, oasisId, targetName, sulfur, nitrate, ironSand) {
    closeMapModal();
    currentMiningTarget = {
        x: parseInt(targetX, 10),
        y: parseInt(targetY, 10),
        planetId: planetId ? parseInt(planetId, 10) : null,
        oasisId: oasisId ? parseInt(oasisId, 10) : null,
        name: targetName || "Pics Rocheux & Montagnes",
        sulfur: (sulfur !== null && sulfur !== undefined) ? parseInt(sulfur, 10) : 10000,
        nitrate: (nitrate !== null && nitrate !== undefined) ? parseInt(nitrate, 10) : 10000,
        ironSand: (ironSand !== null && ironSand !== undefined) ? parseInt(ironSand, 10) : 15000
    };

    // Remplir les informations textuelles
    const nameEl = document.getElementById('miningModalDestName');
    if (nameEl) nameEl.textContent = currentMiningTarget.name;

    const coordsEl = document.getElementById('miningModalDestCoords');
    if (coordsEl) coordsEl.textContent = `[${currentMiningTarget.x} : ${currentMiningTarget.y}]`;

    const dx = currentMiningTarget.x - mapPlayerX;
    const dy = currentMiningTarget.y - mapPlayerY;
    const dist = Math.sqrt(dx * dx + dy * dy);
    const distEl = document.getElementById('miningModalDist');
    if (distEl) distEl.textContent = `${dist.toFixed(1)} lieue(s)`;

    const sBadge = document.getElementById('miningModalSulfurBadge');
    if (sBadge) sBadge.textContent = `🟡 Soufre: ${currentMiningTarget.sulfur.toLocaleString()}`;

    const nBadge = document.getElementById('miningModalNitrateBadge');
    if (nBadge) nBadge.textContent = `⚪ Nitrate: ${currentMiningTarget.nitrate.toLocaleString()}`;

    const iBadge = document.getElementById('miningModalIronSandBadge');
    if (iBadge) iBadge.textContent = `🟤 Fer: ${currentMiningTarget.ironSand.toLocaleString()}`;

    const availCountEl = document.getElementById('miningModalAvailCount');
    if (availCountEl) availCountEl.textContent = `${stationedMineurs.toLocaleString()} Kōfu`;

    const availNumEl = document.getElementById('miningModalAvailNum');
    if (availNumEl) availNumEl.textContent = stationedMineurs;

    const inputCount = document.getElementById('inputMiningCount');
    if (inputCount) {
        inputCount.max = stationedMineurs;
        inputCount.value = stationedMineurs > 0 ? Math.min(stationedMineurs, 5) : 0;
    }

    const noAlert = document.getElementById('miningNoUnitsAlert');
    if (noAlert) {
        if (stationedMineurs > 0) noAlert.classList.add('d-none');
        else noAlert.classList.remove('d-none');
    }

    const launchBtn = document.getElementById('btnLaunchQuickMining');
    if (launchBtn) {
        launchBtn.disabled = (stationedMineurs <= 0);
        launchBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i>Dépêcher l\'Expédition Minière';
    }

    updateMiningEstimates();

    const mModal = document.getElementById('mapMiningModal');
    if (mModal) {
        mModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeQuickMiningModal = function() {
    const mModal = document.getElementById('mapMiningModal');
    if (mModal) mModal.style.display = 'none';
    document.body.style.overflow = '';
};

// Fermeture avec Échap aussi pour le modal des mineurs
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeQuickMiningModal();
});

window.adjustMiningCount = function(delta) {
    const input = document.getElementById('inputMiningCount');
    if (!input || stationedMineurs <= 0) return;
    let val = (parseInt(input.value, 10) || 0) + delta;
    val = Math.max(1, Math.min(val, stationedMineurs));
    input.value = val;
    updateMiningEstimates();
};

window.setMiningCountMax = function() {
    const input = document.getElementById('inputMiningCount');
    if (!input || stationedMineurs <= 0) return;
    input.value = stationedMineurs;
    updateMiningEstimates();
};

window.updateMiningEstimates = function() {
    const input = document.getElementById('inputMiningCount');
    if (!input) return;
    let count = parseInt(input.value, 10) || 0;
    if (stationedMineurs > 0) {
        count = Math.max(1, Math.min(count, stationedMineurs));
    } else {
        count = 0;
    }
    input.value = count;

    // Calculs d'extraction : base 15 Soufre, 15 Nitrate, 25 Fer par mineur
    let estSulfur = count * 15;
    let estNitrate = count * 15;
    let estIronSand = count * 25;

    if (currentMiningTarget) {
        estSulfur = Math.min(estSulfur, currentMiningTarget.sulfur);
        estNitrate = Math.min(estNitrate, currentMiningTarget.nitrate);
        estIronSand = Math.min(estIronSand, currentMiningTarget.ironSand);
    }
    const totalHarvest = estSulfur + estNitrate + estIronSand;

    const totEl = document.getElementById('miningEstTotal');
    if (totEl) totEl.textContent = `${totalHarvest.toLocaleString()} minerais au total`;

    const brkEl = document.getElementById('miningEstBreakdown');
    if (brkEl) {
        brkEl.innerHTML = `🟡 Soufre: <strong>${estSulfur.toLocaleString()}</strong> &bull; ⚪ Nitrate: <strong>${estNitrate.toLocaleString()}</strong> &bull; 🟤 Fer: <strong>${estIronSand.toLocaleString()}</strong>`;
    }

    // Fret mineur : 120 unités par mineur
    const cargoCap = count * 120;
    const cargoEl = document.getElementById('miningEstCargo');
    if (cargoEl) cargoEl.textContent = `${cargoCap.toLocaleString()} unités (120/mineur)`;

    const durEl = document.getElementById('miningEstDuration');
    if (durEl && currentMiningTarget) {
        const dx = currentMiningTarget.x - mapPlayerX;
        const dy = currentMiningTarget.y - mapPlayerY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        // Durée aller : vitesse 12 km/h pour le mineur
        const flightSec = Math.max(30, Math.round((10 + (3500 / 100) * Math.sqrt((10 * dist) / 12)) / mapGameSpeed));
        const siteSec = Math.max(60, Math.round(3600 / mapGameSpeed));
        const totalSec = (flightSec * 2) + siteSec;

        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        const h = Math.floor(m / 60);
        const remM = m % 60;
        if (h > 0) {
            durEl.textContent = `${h}h ${remM.toString().padStart(2, '0')}m ${s.toString().padStart(2, '0')}s`;
        } else {
            durEl.textContent = `${m}m ${s.toString().padStart(2, '0')}s`;
        }
    }
};

window.submitQuickMining = async function() {
    if (!currentMiningTarget) return;
    const input = document.getElementById('inputMiningCount');
    const count = parseInt(input ? input.value : 0, 10);
    if (!count || count <= 0) {
        showModalAlert('Veuillez mobiliser au moins 1 Mineur des Cimes (Kōfu).', 'warning', 'Effectif Requis');
        return;
    }
    if (count > stationedMineurs) {
        showModalAlert('Votre garnison ne compte pas assez de mineurs disponibles.', 'warning', 'Garnison Insuffisante');
        return;
    }

    const btn = document.getElementById('btnLaunchQuickMining');
    const prevHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Dépêche en cours...';
    }

    try {
        const formData = new FormData();
        formData.append('target_x', currentMiningTarget.x);
        formData.append('target_y', currentMiningTarget.y);
        if (currentMiningTarget.planetId) {
            formData.append('target_planet_id', currentMiningTarget.planetId);
        }
        if (currentMiningTarget.oasisId) {
            formData.append('target_oasis_id', currentMiningTarget.oasisId);
        }
        formData.append('mission_type', 'mining');
        formData.append('fleet[mineur]', count);
        formData.append('cargo_metal', 0);
        formData.append('cargo_crystal', 0);
        formData.append('cargo_deuterium', 0);

        const res = await fetch('/api/fleet.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data && data.success) {
            stationedMineurs = Math.max(0, stationedMineurs - count);
            const availCountEl = document.getElementById('miningModalAvailCount');
            if (availCountEl) availCountEl.textContent = `${stationedMineurs.toLocaleString()} Kōfu`;
            const availNumEl = document.getElementById('miningModalAvailNum');
            if (availNumEl) availNumEl.textContent = stationedMineurs;

            closeQuickMiningModal();
            await showModalAlert(
                `Expédition minière dépêchée avec succès ! ${count} Mineurs des Cimes (Kōfu ⛏️) font route vers la montagne [${currentMiningTarget.x} : ${currentMiningTarget.y}]. Les minerais extraits (Soufre, Nitrate, Satetsu) seront acheminés dans vos entrepôts de fief.`,
                'success',
                'Expédition Minière Dépêchée'
            );

            if (galaxyMap && typeof galaxyMap.fetchMapData === 'function') {
                galaxyMap.fetchMapData();
            }
        } else {
            showModalAlert(data.error || data.message || "Erreur lors de l'envoi de l'expédition minière.", 'error', 'Expédition Refusée');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = prevHtml;
            }
        }
    } catch (err) {
        showModalAlert("Erreur de communication avec le serveur féodal.", 'error', 'Erreur Réseau');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = prevHtml;
        }
    }
};
</script>
<?php
$extraHtml = ob_get_clean();
require __DIR__ . '/partials/game_screen_template.php';
?>

