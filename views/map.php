<?php
/**
 * Vue de la Carte des Provinces du Japon Féodal (Plein Écran / Pleine Largeur)
 * OpenShogun - Carte interactive plein format avec déplacement fluide & donjons authentiques
 */
require_once __DIR__ . '/../core/QuestEngine.php';

$centerX = isset($_GET['x']) ? (int)$_GET['x'] : (int)$planet['coord_x'];
$centerY = isset($_GET['y']) ? (int)$_GET['y'] : (int)$planet['coord_y'];

$questEngine = new QuestEngine();
$questEngine->recordAction((int)$user['id'], 'visit_map');
?>

<!-- Navigation breadcrumb Tabler -->
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Exploration panoramique & Provinces</div>
            <h2 class="page-title">
                🗾 Carte des Provinces &amp; Fiefs
                <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">[<?= (int)$planet['coord_x'] ?> : <?= (int)$planet['coord_y'] ?>]</span>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="/?page=resources" class="btn btn-secondary">
                    🌾 Terroir
                </a>
                <a href="/?page=station" class="btn btn-secondary">
                    🏯 Cité Castrale
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card card-map-fullwidth mb-3" style="width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,0.05);">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
        <div class="d-flex align-items-center gap-2">
            <span style="font-size: 1.5rem;">🗾</span>
            <div>
                <h3 class="card-title m-0 font-weight-bold" style="font-size: 1.15rem;">
                    Provinces &amp; Fiefs du Japon Féodal
                </h3>
                <div class="text-secondary small">
                    Fief d'attache : <strong><?= htmlspecialchars($planet['name']) ?></strong> <span class="badge bg-secondary-lt font-monospace">[<?= $planet['coord_x'] ?> : <?= $planet['coord_y'] ?>]</span> &bull; Exploration panoramique pleine largeur
                </div>
            </div>
        </div>

        <!-- Barre de saut rapide et d'instructions -->
        <div class="galaxy-nav-bar d-flex align-items-center gap-2 flex-wrap m-0">
            <form id="jumpCoordsForm" onsubmit="event.preventDefault(); jumpToCoords();" class="d-flex align-items-center gap-1 bg-surface border rounded px-2 py-1">
                <span class="text-secondary small font-weight-bold">Aller en :</span>
                <span class="small font-weight-bold">X</span>
                <input type="number" id="inputCoordX" value="<?= $centerX ?>" class="form-control form-control-sm text-center font-weight-bold" style="width: 60px;">
                <span class="small font-weight-bold">Y</span>
                <input type="number" id="inputCoordY" value="<?= $centerY ?>" class="form-control form-control-sm text-center font-weight-bold" style="width: 60px;">
                <button type="submit" class="btn btn-sm btn-primary">Marcher</button>
            </form>

            <div class="badge bg-danger-lt border border-danger-subtle py-2 px-2 text-danger">
                🖐️ <em>Glissez la carte (Drag &amp; Drop) ou flèches clavier.</em>
            </div>
        </div>
    </div>

    <!-- Légende des Terroirs & Saut de Quadrants (Style Travian) -->
    <div class="card-body bg-surface-secondary border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="font-weight-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">🗾 Terroirs :</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_plains.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Plaines</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_forest.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Forêt de Cèdres</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_mountain.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Montagnes</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_lake.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Lacs &amp; Eaux</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_hills.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Collines</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_village.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Fief Castral</span>
            <span class="d-inline-flex align-items-center gap-1"><img src="/public/assets/map/tile_authentic_castle.jpg?v=2" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Donjon Sacré</span>
            <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-success-lt" style="font-size:0.68rem; padding:2px 4px;">🌾+25%</span> Oasis</span>
        </div>

        <div class="d-flex align-items-center gap-1">
            <span class="font-weight-bold text-secondary me-1">Saut de Zone :</span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(-16, 16)" title="Nord-Ouest [- / +]">↖️ N-O</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(16, 16)" title="Nord-Est [+ / +]">↗️ N-E</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(-16, -16)" title="Sud-Ouest [- / -]">↙️ S-O</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(16, -16)" title="Sud-Est [+ / -]">↘️ S-E</button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="galaxyMap.moveTo(0, 0)" title="Centre Impérial [0 : 0]">⛩️ Centre</button>
        </div>
    </div>

    <!-- Conteneur Interactif Drag-and-Drop Pleine Largeur -->
    <div class="galaxy-map-wrapper map-fullwidth-wrapper" id="galaxyMapContainer" style="width: 100%; border: none; border-radius: 0;">
        <!-- Rendu interactif via GalaxyMapController -->
    </div>
</div>

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

<style>
@keyframes mapModalIn {
    from { opacity:0; transform: scale(0.96) translateY(6px); }
    to   { opacity:1; transform: scale(1)   translateY(0); }
}
</style>

<script src="/public/js/galaxy_map.js?v=<?= file_exists(__DIR__ . '/../public/js/galaxy_map.js') ? filemtime(__DIR__ . '/../public/js/galaxy_map.js') : time() ?>"></script>
<script>
let galaxyMap = null;

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

// Fonction appelée lors du clic sur une tuile de la carte
window.selectPlanetTile = function(data) {
    const title  = document.getElementById('mapModalTitle');
    const coords = document.getElementById('mapModalCoords');
    const body   = document.getElementById('mapModalBody');

    coords.innerText = `[${data.coord_x} : ${data.coord_y}]`;

    // ── Terres vides ────────────────────────────────────────────
    if (data.empty) {
        title.innerText = '🌑 Terres Inexplorées';
        body.innerHTML = `
            <div class="text-center py-4 px-2">
                <div style="font-size:3rem; margin-bottom:0.75rem;">🌑</div>
                <h4 class="font-weight-bold mb-1" style="color:#1e293b;">Terres Inexplorées</h4>
                <p class="text-secondary mb-0">Ces terres lointaines ne contiennent actuellement aucun domaine castral recensé.</p>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Donjons Authentiques (現存十二天守) ─────────────────────
    if (data.is_authentic_castle) {
        title.innerHTML = `🏯 <span style="color:#b45309;">${data.castle_name}</span> <span class="text-secondary small">${data.castle_kanji || ''}</span>`;
        body.innerHTML = `
            <div style="background:#fffbeb; border:1px solid #fde68a; padding:1.1rem; border-radius:8px; margin-bottom:1.1rem;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <span class="badge bg-warning text-dark font-weight-bold" style="font-size:0.75rem;">
                        👑 TRÉSOR NATIONAL &bull; DONJON AUTHENTIQUE (現存十二天守)
                    </span>
                    <span class="text-secondary font-weight-bold small">📍 ${data.castle_province || 'Province Historique'}</span>
                </div>
                <p style="color:#78350f; font-size:0.9rem; margin:0.4rem 0 0.6rem 0; line-height:1.5;">
                    Ce donjon d'époque Sengoku-Edo est l'une des 12 forteresses d'origine préservées du Japon.
                    <strong style="color:#92400e;">Enjeu suprême de la Bataille Finale du Shogunat.</strong>
                </p>
                <div class="small d-flex gap-3 flex-wrap" style="color:#92400e;">
                    <span>Bâtisseur : <strong>${data.castle_builder || 'Maître Féodal'}</strong></span>
                    <span>Garnison : <strong class="text-success">25 000 pts défense</strong></span>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <a href="/?page=castle&code=${encodeURIComponent(data.castle_code || '')}"
                   class="btn btn-warning text-dark font-weight-bold">
                    📜 Découvrir l'Histoire &rarr;
                </a>
                <div class="d-flex gap-2">
                    ${data.planet_id ? `
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=spy"  class="btn btn-outline-secondary">🥷 Sonder</a>
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=raid" class="btn btn-danger">⚔️ Assaillir</a>
                    ` : `<span class="text-secondary small align-self-center">Sanctuaire Inviolé</span>`}
                </div>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Oasis Naturelle ─────────────────────────────────────────
    if (data.is_oasis) {
        const isOccupied   = (data.is_occupied == 1);
        const isOwn        = (data.owner_planet_id && parseInt(data.owner_planet_id, 10) === <?= (int)$planet['id'] ?>);
        const units        = data.units || [];
        const hasWildBeasts = units.some(u => u.is_wild == 1 && parseInt(u.count, 10) > 0);

        let statusBadge = '';
        if (isOwn) {
            statusBadge = '<span class="badge bg-success text-white font-weight-bold" style="font-size:0.75rem;">🌿 VOTRE FIEF NATUREL ANNEXÉ</span>';
        } else if (isOccupied) {
            statusBadge = `<span class="badge bg-primary text-white font-weight-bold" style="font-size:0.75rem;">🛡️ OCCUPÉE PAR ${data.username || 'UN DAIMYŌ'}</span>`;
        } else if (hasWildBeasts) {
            statusBadge = '<span class="badge bg-danger text-white font-weight-bold" style="font-size:0.75rem;">🐗 OASIS SAUVAGE — FAUNE HOSTILE</span>';
        } else {
            statusBadge = '<span class="badge bg-teal text-white font-weight-bold" style="font-size:0.75rem;">✨ OASIS LIBRE D\'OCCUPATION</span>';
        }

        let bonusBadgesHtml = '';
        if (data.bonus_rice  > 0) bonusBadgesHtml += `<span class="badge bg-warning-lt border border-warning" style="font-size:0.78rem;">🌾 +${data.bonus_rice}% Riz</span>`;
        if (data.bonus_wood  > 0) bonusBadgesHtml += `<span class="badge bg-success-lt border border-success" style="font-size:0.78rem;">🪵 +${data.bonus_wood}% Bois</span>`;
        if (data.bonus_stone > 0) bonusBadgesHtml += `<span class="badge bg-primary-lt border border-primary" style="font-size:0.78rem;">🪨 +${data.bonus_stone}% Pierre</span>`;

        let unitsHtml = '';
        if (units.length > 0) {
            unitsHtml = '<div class="d-flex flex-wrap gap-2 mt-2">';
            units.forEach(u => {
                const uCount = parseInt(u.count, 10);
                if (uCount <= 0) return;
                unitsHtml += `
                    <div class="d-flex align-items-center gap-2 bg-white border rounded px-2 py-1 small">
                        <span style="font-size:1.1rem;">${u.icon || (u.is_wild == 1 ? '🐗' : '⚔️')}</span>
                        <span class="font-weight-medium" style="color:#1e293b;">${u.unit_name || u.name}</span>
                        <strong class="text-danger ms-1">x${uCount}</strong>
                    </div>
                `;
            });
            unitsHtml += '</div>';
        } else {
            unitsHtml = '<p class="text-success small my-2">🕊️ Aucun animal sauvage ni soldat en garnison. L\'oasis est entièrement pacifiée !</p>';
        }

        title.innerHTML = `🌿 <span style="color:#15803d;">${data.oasis_name}</span> <span class="text-secondary small">${data.bonus_label || ''}</span>`;
        body.innerHTML = `
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:1.1rem; border-radius:8px; margin-bottom:1.1rem;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    ${statusBadge}
                    <div class="d-flex gap-1 flex-wrap">${bonusBadgesHtml}</div>
                </div>
                <p style="color:#166534; font-size:0.9rem; margin:0.4rem 0 0.75rem 0; line-height:1.45;">
                    Cette oasis naturelle regorge de terres fertiles et de ressources précieuses.
                    <strong>Pillez les récoltes</strong> en terrassant la faune, ou <strong>occupez le territoire</strong> pour conférer des bonus permanents à votre fief.
                </p>
                <div class="bg-white border rounded p-2 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                    <span class="text-secondary font-weight-medium">Récoltes accumulées :</span>
                    <div class="d-flex gap-3">
                        <span style="color:#b45309;">🪵 <strong>${(data.res_wood  || 0).toLocaleString()}</strong></span>
                        <span style="color:#2563eb;">🪨 <strong>${(data.res_stone || 0).toLocaleString()}</strong></span>
                        <span style="color:#15803d;">🌾 <strong>${(data.res_rice  || 0).toLocaleString()}</strong></span>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-secondary font-weight-bold text-uppercase small" style="letter-spacing:0.5px; font-size:0.75rem;">Faune &amp; Garnison :</span>
                    ${unitsHtml}
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=raid"   class="btn btn-warning font-weight-bold">⚔️ Piller l'Oasis</a>
                    <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=attack" class="btn btn-danger font-weight-bold">💥 Nettoyer</a>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    ${isOwn ? `
                        <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=occupy" class="btn btn-primary">🛡️ Renforcer</a>
                        <button onclick="abandonOasisDirect(${data.oasis_id})" class="btn btn-outline-danger">🏳️ Abandonner</button>
                    ` : `
                        <a href="?page=fleet&target_type=oasis&target_id=${data.oasis_id}&mission=occupy" class="btn btn-success font-weight-bold">🚩 Occuper</a>
                        ${!hasWildBeasts ? `<button onclick="annexOasisDirect(${data.oasis_id})" class="btn btn-primary font-weight-bold">✨ Annexion Directe</button>` : ''}
                    `}
                </div>
            </div>
        `;
        openMapModal();
        return;
    }

    // ── Terres Neutres (planète sans joueur) ────────────────────
    if (!data.user_id) {
        title.innerText = `🏞️ Terres Neutres`;
        body.innerHTML = `
            <div class="border rounded p-3 bg-surface mb-3">
                <h4 class="font-weight-bold mb-1" style="color:#1e293b;">${data.planet_name || 'Domaine inconnu'}</h4>
                <p class="text-secondary mb-1">Terrain : <strong style="color:#1e293b;">${data.planet_type || '—'}</strong></p>
                <p class="text-success font-weight-medium mb-0">✨ Terres fertiles libres pour un nouveau fief !</p>
            </div>
            <div class="d-flex justify-content-end gap-2 flex-wrap">
                <a href="?page=fleet&target_id=${data.planet_id}&mission=colonize" class="btn btn-primary">🏯 Établir un Fief</a>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=raid"     class="btn btn-outline-danger">⚔️ Piller</a>
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

    title.innerText = `🏯 ${data.planet_name} — ${data.username || 'Daimyō'}`;
    body.innerHTML = `
        ${data.is_protected ? `
            <div style="background:#f0fdf4; border:1px solid #86efac; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1.1rem; display:flex; align-items:center; gap:0.75rem;">
                <span style="font-size:1.6rem; flex-shrink:0;">🔰</span>
                <div>
                    <strong style="color:#15803d; font-size:0.9rem;">Immunité Féodale des Nouveaux Joueurs</strong>
                    <div style="color:#166534; font-size:0.8rem; margin-top:0.15rem;">Ce daimyō est protégé jusqu'au ${data.protection_until || '7 jours'}${data.protection_remaining ? ' (encore ' + data.protection_remaining + ')' : ''}. Les assauts, pillages et espionnages shinobi sont neutralisés.</div>
                </div>
            </div>
        ` : ''}
        <div style="background:#fef2f2; border:1px solid #fecaca; padding:1.1rem; border-radius:8px; margin-bottom:1.1rem;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:44px; height:44px; border-radius:50%; background:rgba(220,38,38,0.1); border:1px solid rgba(220,38,38,0.25); display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0;">🏯</div>
                <div>
                    <div style="font-weight:700; color:#1e293b; font-size:1.05rem;">
                        ${data.planet_name}
                        ${data.is_protected ? '<span class="badge bg-success-lt ms-2" style="font-size:0.7rem;">🔰 Protégé</span>' : ''}
                    </div>
                    <div class="small text-secondary">Fief du Daimyō
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(${targetUserId})"
                           class="font-weight-bold text-danger text-decoration-none ms-1">
                            👤 ${data.username || 'Daimyō'}
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
                👤 Fiche Daimyō
            </button>
            ${!isOwn ? (data.is_protected ? `
                <span class="badge bg-success-lt font-weight-bold py-2 px-3" title="Ce fief est inviolable sous immunité débutant">
                    🔰 Fief sous Immunité
                </span>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=transport" class="btn btn-outline-secondary">🐂 Convoi</a>
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(data.username || '')}" class="btn btn-outline-secondary">✉️ Missive</a>
            ` : `
                <a href="?page=fleet&target_id=${data.planet_id}&mission=spy"       class="btn btn-outline-secondary">🥷 Espionner</a>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=raid"      class="btn btn-danger">⚔️ Raid</a>
                <a href="?page=fleet&target_id=${data.planet_id}&mission=transport" class="btn btn-outline-secondary">🐂 Convoi</a>
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(data.username || '')}" class="btn btn-outline-secondary">✉️ Missive</a>
            `) : `
                <span class="badge bg-success-lt font-weight-bold py-2 px-3">
                    🏯 Votre propre domaine castral
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
</script>

