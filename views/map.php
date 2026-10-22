<?php
/**
 * Vue de la Carte des Provinces du Japon Féodal (Plein Écran / Pleine Largeur)
 * OpenShogun - Carte interactive plein format avec déplacement fluide & donjons authentiques
 */
require_once __DIR__ . '/../core/QuestEngine.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GameConfig.php';

$centerX = isset($_GET['x']) ? (int)$_GET['x'] : (int)$planet['coord_x'];
$centerY = isset($_GET['y']) ? (int)$_GET['y'] : (int)$planet['coord_y'];

$questEngine = new QuestEngine();
$questEngine->recordAction((int)$user['id'], 'visit_map');

$dbMap = Database::getConnection();
$stPec = $dbMap->prepare("SELECT count FROM planet_units WHERE planet_id = ? AND unit_code = 'pecheur'");
$stPec->execute([(int)$planet['id']]);
$stationedPecheurs = (int)$stPec->fetchColumn() ?: 0;
$gameSpeedFactor = max(1, (float)GameConfig::get('game_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5));

$queueTitle = 'Mouvements & Chantiers';
ob_start();
?>
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
            <div>
                <h2 class="card-title d-flex align-items-center gap-2">
                    <span><i class="fa-solid fa-map-location-dot text-danger me-1"></i> Carte des Provinces &amp; Fiefs</span>
                    <span class="badge bg-secondary-lt font-monospace" style="font-size:0.72rem;">[<?= (int)$planet['coord_x'] ?> : <?= (int)$planet['coord_y'] ?>]</span>
                </h2>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                    Fief d'attache : <strong><?= htmlspecialchars($planet['name']) ?></strong> &bull; Exploration panoramique &amp; terroirs du Japon Féodal
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

        <!-- Légende des Terroirs & Saut de Quadrants (Style Travian) -->
        <div class="card-body bg-surface-secondary border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small flex-shrink-0">
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

            <div class="d-flex align-items-center gap-1">
                <span class="font-weight-bold text-secondary me-1">Quadrants :</span>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(-16, 16)" title="Nord-Ouest [- / +]"><i class="fa-solid fa-arrow-trend-up me-1"></i>N-O</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(16, 16)" title="Nord-Est [+ / +]"><i class="fa-solid fa-arrow-trend-up me-1"></i>N-E</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(-16, -16)" title="Sud-Ouest [- / -]"><i class="fa-solid fa-arrow-trend-down me-1"></i>S-O</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(16, -16)" title="Sud-Est [+ / -]"><i class="fa-solid fa-arrow-trend-down me-1"></i>S-E</button>
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(0, 0)" title="Centre Impérial [0 : 0]"><i class="fa-solid fa-torii-gate me-1"></i>Centre</button>
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
     MODAL CARTE — Informations de la Zone Sélectionnée (Modal Blanc Simple)
     ===================================================== -->
<div id="mapTileModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
" onclick="if(event.target===this) closeMapModal();">

    <div class="card shadow-lg" style="
        background: #ffffff;
        border: 1px solid var(--tblr-border-color, #e6e7e9);
        border-radius: 12px;
        width: 100%; max-width: 600px;
        max-height: 85vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: var(--tblr-body-color, #1e293b);
    ">
        <!-- En-tête -->
        <div class="card-header d-flex justify-content-between align-items-center py-3 px-3 border-bottom bg-white" style="border-radius: 12px 12px 0 0;">
            <div class="d-flex align-items-center gap-2 flex-fill min-w-0">
                <h3 id="mapModalTitle" class="card-title m-0 text-truncate font-weight-bold" style="font-size: 1.1rem; color: #1e293b;">Informations</h3>
                <span id="mapModalCoords" class="badge bg-secondary text-white font-monospace" style="color: #fff !important;"></span>
            </div>
            <button type="button" class="btn-close ms-2" onclick="closeMapModal()" aria-label="Fermer"></button>
        </div>

        <!-- Corps -->
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

    <div class="card shadow-lg" style="
        background: #ffffff;
        border: 1px solid #06b6d4;
        border-radius: 12px;
        width: 100%; max-width: 520px;
        max-height: 90vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: #1e293b;
    ">
        <!-- En-tête -->
        <div class="card-header d-flex justify-content-between align-items-center py-2.5 px-3 border-bottom bg-cyan-lt" style="border-radius: 12px 12px 0 0;">
            <div class="d-flex align-items-center gap-2 flex-fill min-w-0">
                <span class="avatar avatar-xs bg-cyan text-white rounded-circle" style="width:26px; height:26px; font-size:0.75rem;">
                    <i class="fa-solid fa-fish"></i>
                </span>
                <h3 class="card-title m-0 text-cyan-fg font-weight-bold" style="font-size: 1.05rem;" id="fishingModalTitle">
                    Dépêcher des Pêcheurs Féodaux
                </h3>
            </div>
            <button type="button" class="btn-close ms-2" onclick="closeQuickFishingModal()" aria-label="Fermer"></button>
        </div>

        <!-- Corps -->
        <div class="card-body p-3">
            <!-- Illustration & Cible -->
            <div class="position-relative overflow-hidden rounded mb-3 border border-cyan-subtle shadow-2xs" style="height: 135px; background:#0f172a;">
                <img src="/public/assets/units/pecheur.jpg" alt="Pêcheur Féodal" style="width:100%; height:100%; object-fit:cover; object-position: center 25%;">
                <div style="position:absolute; inset:0; background: linear-gradient(to top, rgba(15,23,42,0.9) 0%, rgba(15,23,42,0.25) 55%, transparent 100%);"></div>
                <div style="position:absolute; bottom:8px; left:12px; right:12px;" class="d-flex justify-content-between align-items-end text-white">
                    <div class="min-w-0 me-2">
                        <div class="fw-bold small text-truncate" id="fishingModalDestName">Lac d'Eaux Vives</div>
                        <div class="text-white-50 font-monospace" style="font-size:0.75rem;">
                            <span id="fishingModalDestCoords">[X : Y]</span> &bull; <span id="fishingModalDist">--</span>
                        </div>
                    </div>
                    <span class="badge bg-cyan text-white font-monospace shadow-2xs" id="fishingModalStockBadge" style="font-size:0.75rem;">
                        <i class="fa-solid fa-fish me-1"></i>Stock : --
                    </span>
                </div>
            </div>

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

<style>
@keyframes mapModalIn {
    from { opacity:0; transform: scale(0.96) translateY(6px); }
    to   { opacity:1; transform: scale(1)   translateY(0); }
}
</style>

<script src="/public/js/galaxy_map.js?v=<?= file_exists(__DIR__ . '/../public/js/galaxy_map.js') ? filemtime(__DIR__ . '/../public/js/galaxy_map.js') : time() ?>"></script>
<script>
let galaxyMap = null;
const mapPlayerX = <?= (int)$planet['coord_x'] ?>;
const mapPlayerY = <?= (int)$planet['coord_y'] ?>;
let stationedPecheurs = <?= (int)$stationedPecheurs ?>;
const mapGameSpeed = <?= (float)$gameSpeedFactor ?>;
let currentFishingTarget = null;

document.addEventListener('DOMContentLoaded', () => {
    galaxyMap = new GalaxyMapController('galaxyMapContainer', {
        initialX: <?= $centerX ?>,
        initialY: <?= $centerY ?>,
        playerX: <?= $planet['coord_x'] ?>,
        playerY: <?= $planet['coord_y'] ?>,
        userId: <?= $user['id'] ?>
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

function openMapModal() {
    const modal = document.getElementById('mapTileModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
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

// Fonction appelée lors du clic sur une tuile de la carte
window.selectPlanetTile = function(data) {
    const title  = document.getElementById('mapModalTitle');
    const coords = document.getElementById('mapModalCoords');
    const body   = document.getElementById('mapModalBody');

    coords.innerText = `[${data.coord_x} : ${data.coord_y}]`;

    // ── Terre Sacrée [0 : 0] / Le Dōjō Impérial ──────────────────────────────
    if (data.is_imperial_dojo || (parseInt(data.coord_x, 10) === 0 && parseInt(data.coord_y, 10) === 0)) {
        const isDojoUnlocked = !!(data.dojo_unlocked || data.is_imperial_dojo);

        if (!isDojoUnlocked) {
            // Dōjō NON ACTIVÉ : Terre Sacrée Scellée non-annexable
            title.innerHTML = `<i class="fa-solid fa-torii-gate text-secondary me-1"></i> <span class="text-secondary">Terre Sacrée Inviolable [0 : 0] &bull; Tenchi no Seichi</span> <span class="badge bg-secondary font-monospace">[0 : 0]</span>`;
            body.innerHTML = `
                <div style="background:#f8fafc; border:2px solid #cbd5e1; border-radius:10px; padding:1.25rem; margin-bottom:1.25rem;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:68px; height:68px; border-radius:10px; overflow:hidden; border:2px solid #94a3b8; flex-shrink:0; background:#e2e8f0; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-mountain text-secondary fs-1"></i>
                        </div>
                        <div>
                            <div style="font-weight:800; color:#1e293b; font-size:1.15rem;">
                                Terre Sacrée Inviolable de Kyōto
                            </div>
                            <div class="small text-secondary mt-0.5">
                                Sanctuaire Impérial scellé &bull; Enveloppé dans les brumes ancestrales
                            </div>
                            <div class="mt-1">
                                <span class="badge bg-secondary-lt text-secondary"><i class="fa-solid fa-lock me-1"></i>Dōjō Non Éveillé (Ères I à III)</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary py-2.5 px-3 mb-3 small d-flex align-items-center gap-2 rounded-2">
                        <i class="fa-solid fa-ban text-danger fs-3 flex-shrink-0"></i>
                        <div>
                            <strong class="d-block text-dark">Sanctuaire Impérial &bull; Non Annexable :</strong>
                            Aucun seigneur féodal ne peut coloniser, annexer ou attaquer le centre du monde [0 : 0]. Le Dōjō Impérial n'apparaîtra qu'à l'avènement de l'Ère IV, une fois la Guerre des 12 Châteaux remportée.
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-white rounded border small">
                        <span class="text-secondary">Statut Foncier :</span>
                        <strong class="text-danger"><i class="fa-solid fa-ban me-1"></i>Sanctuaire Impérial Inviolable &bull; Non Annexable</strong>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
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

        title.innerHTML = `<i class="fa-solid fa-torii-gate text-warning me-1"></i> <span style="color:#b45309;">Terre Sacrée [0 : 0] &bull; Dōjō Impérial</span> <span class="badge bg-warning text-dark font-monospace">[0 : 0]</span>`;

        let statusBadge = '<span class="badge bg-secondary">Terre Neutre Non Réclamée</span>';
        if (isFrozen) {
            statusBadge = '<span class="badge bg-danger"><i class="fa-solid fa-snowflake me-1"></i>Chantier Gelé (&lt; 2 Châteaux)</span>';
        } else if (isCompleted) {
            statusBadge = '<span class="badge bg-purple text-white"><i class="fa-solid fa-crown me-1"></i>Niveau 10 Suprême &bull; Victoire</span>';
        } else if (allianceTag) {
            statusBadge = `<span class="badge bg-success"><i class="fa-solid fa-hammer me-1"></i>Chantier Actif [${escapeHtml(allianceTag)}]</span>`;
        }

        body.innerHTML = `
            <div style="background:#fffbeb; border:2px solid #f59e0b; border-radius:10px; padding:1.25rem; margin-bottom:1.25rem;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:68px; height:68px; border-radius:10px; overflow:hidden; border:2px solid #d97706; flex-shrink:0; background:#fef3c7; box-shadow:0 0 10px rgba(245,158,11,0.4);">
                        <img src="/public/assets/map/tile_imperial_dojo.jpg?v=1" alt="Dojo Imperial" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div>
                        <div style="font-weight:800; color:#92400e; font-size:1.2rem;">
                            Dōjō Impérial (Tenchi no Seichi)
                        </div>
                        <div class="small text-secondary mt-0.5">
                            Le Sanctuaire Suprême de Kyōto &bull; Point culminant de la fin du monde
                        </div>
                        <div class="mt-1">${statusBadge}</div>
                    </div>
                </div>

                <!-- Règle de non-annexion individuelle formelle -->
                <div class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-center gap-2 rounded-2">
                    <i class="fa-solid fa-ban text-danger fs-3 flex-shrink-0"></i>
                    <div>
                        <strong class="text-dark d-block">Sanctuaire Impérial &bull; Non Annexable par un Daimyō :</strong>
                        Cette merveille mondiale ne peut être annexée par aucun joueur individuel. Le contrôle s'opère collectivement par les Alliances via les 10 Paliers de construction.
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-secondary small d-block">Palier Actuel :</span>
                            <strong class="text-dark font-monospace" style="font-size:1.05rem;">Niveau ${dojoLvl} / 10</strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-secondary small d-block">Alliance Bâtisseuse :</span>
                            <strong class="text-primary">${allianceTag ? `[${escapeHtml(allianceTag)}] ${escapeHtml(allianceName)}` : 'Non revendiqué'}</strong>
                        </div>
                    </div>
                </div>

                <!-- Jauge de Structure PV -->
                <div class="mb-3 p-2 bg-white rounded border">
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
                <div class="mb-3 p-2 bg-white rounded border">
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
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Terres Vierges Naturelles (Plaines, Collines, Lacs, etc.) ─────────────
    if (data.empty) {
        const isLake = (data.terrain_type === 'lake' || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('lac') ||
            data.terrain_name.toLowerCase().includes('eau') ||
            data.terrain_name.toLowerCase().includes('calme') ||
            data.terrain_name.toLowerCase().includes('vive')
        )));
        const isMountain = (data.terrain_type === 'mountain' || (data.terrain_name && data.terrain_name.toLowerCase().includes('montagne')));

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

        title.innerHTML = isLake
            ? `<i class="fa-solid fa-water text-cyan me-1"></i> <span style="color:#0891b2;">${data.terrain_name || 'Lac d\'Eaux Vives'}</span> <span class="badge bg-cyan-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`
            : (isMountain 
                ? `<i class="fa-solid fa-mountain text-warning me-1"></i> <span style="color:#b45309;">${data.terrain_name || 'Pics Rocheux & Montagnes'}</span> <span class="badge bg-warning-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`
                : `<i class="fa-solid fa-mountain-sun text-success me-1"></i> <span style="color:#15803d;">${data.terrain_name || 'Terres Vierges'}</span> <span class="badge bg-secondary-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`);

        body.innerHTML = `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; margin-bottom:1.25rem;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:58px; height:58px; border-radius:8px; overflow:hidden; border:2px solid #cbd5e1; flex-shrink:0; background:#f1f5f9;">
                        ${data.terrain_img ? `<img src="${data.terrain_img}" alt="" style="width:100%; height:100%; object-fit:cover;">` : '<span style="font-size:2rem; display:flex; align-items:center; justify-content:center; height:100%;"><i class="fa-solid fa-wheat-awn text-warning"></i></span>'}
                    </div>
                    <div>
                        <div style="font-weight:700; color:#1e293b; font-size:1.15rem;">
                            ${data.terrain_name || (isLake ? 'Lac d\'Eaux Vives' : (isMountain ? 'Pics Rocheux des Cimes' : 'Terres Vierges Inoccupées'))}
                        </div>
                        <div class="small text-secondary mt-0.5">
                            ${data.terrain_desc || (isLake ? 'Étendue d\'eau douce et sanctuaire naturel halieutique protégé.' : (isMountain ? 'Massif rocheux et crêtes volcaniques sanctuarisées riches en minerais féodaux.' : 'Plaines fertiles et terroirs prospères du Japon féodal, prêts à être défrichés.'))}
                        </div>
                    </div>
                </div>

                <!-- Statut de Propriété -->
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded border">
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
                            <div class="alert alert-success d-flex align-items-center gap-2 my-2 p-2" style="font-size:0.85rem; border-radius:6px;">
                                <span style="font-size:1.3rem;"><i class="fa-solid fa-sparkles text-warning"></i></span>
                                <div>
                                    <strong>Emplacement Disponible pour Nouveau Fief !</strong><br>
                                    Vous pouvez y dépêcher une expédition avec un <strong>Pionnier Féodal (Colon <i class="fa-solid fa-torii-gate text-danger"></i>)</strong> pour y fonder votre prochain domaine castral.
                                </div>
                            </div>
                            ${ruralPlotsHtml}
                        `)
                }
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="closeMapModal()">Fermer</button>
                ${isLake ? (
                    isDepleted ? `
                        <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                            <i class="fa-solid fa-ban me-1"></i>Réserve de Poisson Épuisée
                        </button>
                    ` : `
                        <button type="button" class="btn btn-cyan text-white font-weight-bold" onclick="openQuickFishingModal(${data.coord_x}, ${data.coord_y}, null, null, '${(data.terrain_name || 'Lac d\'Eaux Calmes').replace(/'/g, "\\'")}', ${currentStock})">
                            <i class="fa-solid fa-fish me-1"></i>Dépêcher une Expédition de Pêche &rarr;
                        </button>
                    `
                ) : (isMountain ? (
                    isMountainDepleted ? `
                        <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                            <i class="fa-solid fa-ban me-1"></i>Filon Minéral Épuisé
                        </button>
                    ` : `
                        <a href="?page=fleet&target_x=${data.coord_x}&target_y=${data.coord_y}&mission=mining" class="btn btn-warning text-dark font-weight-bold">
                            <i class="fa-solid fa-pickaxe me-1"></i>Dépêcher des Mineurs (Expédition Minière) &rarr;
                        </a>
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
        title.innerHTML = `<i class="fa-solid fa-chess-rook text-warning me-1"></i> <span style="color:#b45309;">${data.castle_name}</span> <span class="text-secondary small">${data.castle_kanji || ''}</span>`;
        body.innerHTML = `
            <div class="card card-sm bg-warning-lt p-3 mb-3 border border-warning">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <span class="badge bg-warning text-warning-fg fw-bold">
                        <i class="fa-solid fa-crown text-warning me-1"></i>TRÉSOR NATIONAL &bull; DONJON AUTHENTIQUE (現存十二天守)
                    </span>
                    <span class="fw-bold small text-muted"><i class="fa-solid fa-location-dot text-danger me-1"></i>${data.castle_province || 'Province Historique'}</span>
                </div>
                <p class="small mb-2">
                    Ce donjon d'époque Sengoku-Edo est l'une des 12 forteresses d'origine préservées du Japon.
                    <strong class="text-warning">Enjeu suprême de la Bataille Finale du Shogunat.</strong>
                </p>
                <div class="small d-flex gap-3 flex-wrap text-muted">
                    <span>Bâtisseur : <strong class="text-dark">${data.castle_builder || 'Maître Féodal'}</strong></span>
                    <span>Garnison : <strong class="text-success">25 000 pts défense</strong></span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
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
        const isLakeOasis   = (data.oasis_type && data.oasis_type.indexOf('lake') !== -1) ||
                              (data.oasis_name && (
                                  data.oasis_name.toLowerCase().includes('lac') ||
                                  data.oasis_name.toLowerCase().includes('eau') ||
                                  data.oasis_name.toLowerCase().includes('calme') ||
                                  data.oasis_name.toLowerCase().includes('vive')
                              ));
        const currentStock  = (data.fish_stock !== null && data.fish_stock !== undefined) ? parseInt(data.fish_stock, 10) : 0;
        const initialStock  = (data.fish_initial !== null && data.fish_initial !== undefined) ? parseInt(data.fish_initial, 10) : (currentStock || 25000);
        const isLakeDepleted = data.lake_depleted || (currentStock <= 0);
        const isOccupied    = (data.is_occupied == 1);
        const isOwn         = (data.owner_planet_id && parseInt(data.owner_planet_id, 10) === <?= (int)$planet['id'] ?>);
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

        title.innerHTML = `<i class="fa-solid ${isLakeOasis ? 'fa-water text-cyan' : 'fa-seedling text-success'} me-1"></i> <span style="color:${isLakeOasis ? '#0891b2' : '#15803d'};">${data.oasis_name}</span> <span class="text-secondary small">${data.bonus_label || ''}</span>`;
        body.innerHTML = `
            <div style="background:${isLakeOasis ? '#f0fdfa' : '#f0fdf4'}; border:1px solid ${isLakeOasis ? '#99f6e4' : '#bbf7d0'}; padding:1.1rem; border-radius:8px; margin-bottom:1.1rem;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    ${statusBadge}
                    <div class="d-flex gap-1 flex-wrap">${bonusBadgesHtml}</div>
                </div>
                <p style="color:${isLakeOasis ? '#0f766e' : '#166534'}; font-size:0.9rem; margin:0.4rem 0 0.75rem 0; line-height:1.45;">
                    ${isLakeOasis 
                        ? 'Ce lac naturel est un sanctuaire d\'eaux calmes et poissonneuses. <strong>Cette parcelle ne peut pas être annexée.</strong> Dépêchez vos Pêcheurs Féodaux formés au Dojo en expédition de pêche : les prises sont entreposées dans votre Grenier à Riz Fortifié.'
                        : 'Cette oasis naturelle regorge de terres fertiles et de ressources précieuses. <strong>Pillez les récoltes</strong> en terrassant la faune, ou <strong>occupez le territoire</strong> pour conférer des bonus permanents à votre fief.'
                    }
                </p>
                ${isLakeOasis ? renderLakeFishCounter(currentStock, initialStock, isLakeDepleted) : ''}
                <div class="bg-white border rounded p-2 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                    <span class="text-secondary font-weight-medium">Ressources &amp; Récoltes :</span>
                    <div class="d-flex gap-3 align-items-center flex-wrap">
                        ${isLakeOasis ? `<span style="color:#0891b2;"><i class="fa-solid fa-fish text-cyan me-1"></i><strong>${currentStock.toLocaleString()}</strong> Poisson</span>` : ''}
                        <span style="color:#b45309;"><i class="fa-solid fa-tree text-success me-1"></i><strong>${(data.res_wood  || 0).toLocaleString()}</strong></span>
                        <span style="color:#2563eb;"><i class="fa-solid fa-mountain text-secondary me-1"></i><strong>${(data.res_stone || 0).toLocaleString()}</strong></span>
                        <span style="color:#15803d;"><i class="fa-solid fa-wheat-awn text-warning me-1"></i><strong>${(data.res_rice  || 0).toLocaleString()}</strong></span>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-secondary font-weight-bold text-uppercase small" style="letter-spacing:0.5px; font-size:0.75rem;">Faune &amp; Garnison :</span>
                    ${unitsHtml}
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
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
        const isLakePlanet      = (data.terrain_type === 'lake' || data.planet_type === 'oceanic' || data.is_lake || (data.terrain_name && (
            data.terrain_name.toLowerCase().includes('lac') ||
            data.terrain_name.toLowerCase().includes('eau') ||
            data.terrain_name.toLowerCase().includes('calme') ||
            data.terrain_name.toLowerCase().includes('vive')
        )) || (data.planet_name && (
            data.planet_name.toLowerCase().includes('lac') ||
            data.planet_name.toLowerCase().includes('eau') ||
            data.planet_name.toLowerCase().includes('calme') ||
            data.planet_name.toLowerCase().includes('vive')
        )));
        const isMountainPlanet  = (data.terrain_type === 'mountain' || (data.terrain_name && data.terrain_name.toLowerCase().includes('montagne')) || data.is_mountain);

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

        title.innerHTML = isLakePlanet
            ? `<i class="fa-solid fa-water text-cyan me-1"></i> <span style="color:#0891b2;">${data.planet_name || data.terrain_name || 'Lac d\'Eaux Vives'}</span> <span class="badge bg-cyan-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`
            : (isMountainPlanet
                ? `<i class="fa-solid fa-mountain text-warning me-1"></i> <span style="color:#b45309;">${data.planet_name || data.terrain_name || 'Pics Rocheux & Montagnes'}</span> <span class="badge bg-warning-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`
                : `<i class="fa-solid fa-mountain-sun text-success me-1"></i> <span style="color:#15803d;">${data.planet_name || 'Domaine Libre'}</span> <span class="badge bg-secondary-lt text-dark font-monospace">[${data.coord_x} : ${data.coord_y}]</span>`);

        body.innerHTML = `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem; margin-bottom:1.25rem;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:58px; height:58px; border-radius:8px; overflow:hidden; border:2px solid #cbd5e1; flex-shrink:0; background:#f1f5f9;">
                        ${data.terrain_img ? `<img src="${data.terrain_img}" alt="" style="width:100%; height:100%; object-fit:cover;">` : '<span style="font-size:2rem; display:flex; align-items:center; justify-content:center; height:100%;"><i class="fa-solid fa-wheat-awn text-warning"></i></span>'}
                    </div>
                    <div>
                        <div style="font-weight:700; color:#1e293b; font-size:1.15rem;">
                            ${data.planet_name || (isLakePlanet ? 'Lac d\'Eaux Vives' : (isMountainPlanet ? 'Pics Rocheux & Montagnes' : 'Domaine Féodal Libre'))}
                        </div>
                        <div class="small text-secondary mt-0.5">
                            Type de terrain : <strong class="text-dark">${data.planet_type || (isLakePlanet ? 'oceanic' : (isMountainPlanet ? 'mountain' : 'terrestrial'))}</strong> &bull; ${isLakePlanet ? 'Sanctuaire d\'eau libre communautaire.' : (isMountainPlanet ? 'Sanctuaire minéralier des cimes non-annexable.' : 'Emplacement stratégique pour un nouveau fief.')}
                        </div>
                    </div>
                </div>

                <!-- Statut de Propriété -->
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-white rounded border">
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
                            <div class="alert alert-success d-flex align-items-center gap-2 my-2 p-2" style="font-size:0.85rem; border-radius:6px;">
                                <span style="font-size:1.3rem;"><i class="fa-solid fa-sparkles text-warning"></i></span>
                                <div>
                                    <strong>Emplacement Libre pour Colonisation !</strong><br>
                                    Vous pouvez y envoyer un <strong>Pionnier Féodal (Colon <i class="fa-solid fa-torii-gate text-danger"></i>)</strong> pour annexer ce terroir et fonder une colonie.
                                </div>
                            </div>
                            ${ruralPlotsHtml}
                        `)
                }
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="closeMapModal()">Fermer</button>
                <div class="d-flex gap-2">
                    ${isLakePlanet ? (
                        isDepleted ? `
                            <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                                <i class="fa-solid fa-ban me-1"></i>Réserve de Poisson Épuisée
                            </button>
                        ` : `
                            <button type="button" class="btn btn-cyan font-weight-bold text-white" onclick="openQuickFishingModal(${data.coord_x}, ${data.coord_y}, ${data.planet_id || 'null'}, null, '${(data.planet_name || data.terrain_name || 'Lac d\'Eaux Vives').replace(/'/g, "\\'")}', ${currentStock})">
                                <i class="fa-solid fa-fish me-1"></i>Dépêcher une Expédition de Pêche &rarr;
                            </button>
                        `
                    ) : (isMountainPlanet ? (
                        isMountainDepleted ? `
                            <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                                <i class="fa-solid fa-ban me-1"></i>Filon Minéral Épuisé
                            </button>
                        ` : `
                            <a href="?page=fleet&target_id=${data.planet_id || ''}&target_x=${data.coord_x}&target_y=${data.coord_y}&mission=mining" class="btn btn-warning text-dark font-weight-bold">
                                <i class="fa-solid fa-pickaxe me-1"></i>Dépêcher des Mineurs (Expédition Minière) &rarr;
                            </a>
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
    const isOwn = (parseInt(data.user_id, 10) === <?= (int)$user['id'] ?>);
    const fKey  = (data.faction || 'terran').toLowerCase();
    const fName = (data.faction || 'Terran').toUpperCase();
    const pts   = (data.points != null) ? Number(data.points).toLocaleString() : '0';
    const targetUserId = parseInt(data.user_id, 10) || 0;

    title.innerHTML = `<i class="fa-solid fa-chess-rook text-danger me-1"></i> ${data.planet_name} — ${data.username || 'Daimyō'}`;
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
        <div style="background:#fef2f2; border:1px solid #fecaca; padding:1.1rem; border-radius:8px; margin-bottom:1.1rem;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:44px; height:44px; border-radius:50%; background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.25); display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;"><i class="fa-solid fa-chess-rook text-danger"></i></div>
                <div>
                    <div style="font-weight:700; color:#1e293b; font-size:1.05rem;">
                        ${data.planet_name}
                        ${data.is_protected ? '<span class="badge bg-success-lt ms-2" style="font-size:0.7rem;"><i class="fa-solid fa-shield-halved text-success me-1"></i>Protégé</span>' : ''}
                    </div>
                    <div class="small text-secondary">Fief du Daimyō
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(${targetUserId})"
                           class="font-weight-bold text-danger text-decoration-none ms-1">
                            <i class="fa-solid fa-user me-1"></i>${data.username || 'Daimyō'}
                        </a>
                        ${data.alliance_tag ? `<span class="badge bg-warning-lt ms-1">[${data.alliance_tag}]</span>` : ''}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-4 flex-wrap small">
                <span>Clan : <span class="badge bg-secondary-lt font-weight-bold">${fName}</span></span>
                <span>Puissance : <strong class="text-danger">${pts} pts</strong></span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end align-items-center">
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
</script>
<?php
$extraHtml = ob_get_clean();
require __DIR__ . '/partials/game_screen_template.php';
?>

