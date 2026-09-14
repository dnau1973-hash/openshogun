<?php
/**
 * Vue de la Carte Galactique 2D avec Drag & Drop
 */
$centerX = isset($_GET['x']) ? (int)$_GET['x'] : (int)$planet['coord_x'];
$centerY = isset($_GET['y']) ? (int)$_GET['y'] : (int)$planet['coord_y'];
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🗾 Carte des Provinces & Fiefs du Japon Féodal</h2>
        <div style="font-size:0.85rem; color:var(--text-muted);">
            Fief d'attache : <strong><?= htmlspecialchars($planet['name']) ?> [<?= $planet['coord_x'] ?> : <?= $planet['coord_y'] ?>]</strong>
        </div>
    </div>
    <div class="card-body">
        <!-- Barre de saut rapide et d'instructions -->
        <div class="galaxy-nav-bar">
            <form id="jumpCoordsForm" onsubmit="event.preventDefault(); jumpToCoords();" style="display:flex; align-items:center; gap:0.5rem;">
                <label style="font-size:0.85rem; color:var(--text-muted);">Sauter aux coordonnées :</label>
                <span>X:</span>
                <input type="number" id="inputCoordX" value="<?= $centerX ?>" style="width:65px; background:rgba(0,0,0,0.5); border:1px solid var(--border-color); color:#fff; padding:0.35rem; border-radius:4px; text-align:center;">
                <span>Y:</span>
                <input type="number" id="inputCoordY" value="<?= $centerY ?>" style="width:65px; background:rgba(0,0,0,0.5); border:1px solid var(--border-color); color:#fff; padding:0.35rem; border-radius:4px; text-align:center;">
                <button type="submit" class="btn btn-primary" style="font-size:0.8rem; padding:0.35rem 0.85rem;">Marcher vers</button>
            </form>

            <div style="font-size:0.8rem; color:#dc2626;">
                💡 <em>Astuce : Déplacez la carte à la souris ou avec les touches fléchées du clavier.</em>
            </div>
        </div>

        <!-- Conteneur Interactif Drag-and-Drop -->
        <div class="galaxy-map-wrapper" id="galaxyMapContainer">
            <!-- Rendu interactif via GalaxyMapController -->
        </div>

        <!-- Panneau de Renseignements Planétaires -->
        <div id="planetDetailsCard" class="card" style="margin-top:1.5rem; display:none; border-color:#dc2626; background:rgba(17,18,24,0.95);">
            <div class="card-header">
                <h3 class="card-title" id="selectedPlanetTitle">Détails du Domaine Castral</h3>
                <span id="selectedPlanetCoords" style="color:var(--border-highlight); font-family:monospace; font-size:1rem; font-weight:700;"></span>
            </div>
            <div class="card-body" id="selectedPlanetBody">
                <!-- Rempli par JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="/public/js/galaxy_map.js"></script>
<script>
let galaxyMap = null;

document.addEventListener('DOMContentLoaded', () => {
    galaxyMap = new GalaxyMapController('galaxyMapContainer', {
        initialX: <?= $centerX ?>,
        initialY: <?= $centerY ?>,
        playerX: <?= $planet['coord_x'] ?>,
        playerY: <?= $planet['coord_y'] ?>,
        userId: <?= $user['id'] ?>,
        radius: 4 // Grille 9x9 (81 secteurs chargés dynamiquement)
    });
});

function jumpToCoords() {
    const x = parseInt(document.getElementById('inputCoordX').value, 10);
    const y = parseInt(document.getElementById('inputCoordY').value, 10);
    if (!isNaN(x) && !isNaN(y) && galaxyMap) {
        galaxyMap.moveTo(x, y);
    }
}

// Fonction appelée lors du clic sur un fief de la carte
window.selectPlanetTile = function(data) {
    const card = document.getElementById('planetDetailsCard');
    const title = document.getElementById('selectedPlanetTitle');
    const coords = document.getElementById('selectedPlanetCoords');
    const body = document.getElementById('selectedPlanetBody');

    card.style.display = 'block';
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    coords.innerText = `[${data.coord_x} : ${data.coord_y}]`;

    if (data.empty) {
        title.innerText = 'Terres Inexplorées';
        body.innerHTML = `
            <p style="color:var(--text-muted); margin-bottom:0.5rem;">Ces terres lointaines ne contiennent actuellement aucun domaine castral recensé.</p>
        `;
        return;
    }

    if (!data.user_id) {
        title.innerText = `Terres Neutres : ${data.planet_name}`;
        body.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <p style="margin-bottom:0.3rem;">Nature du terrain : <strong>${data.planet_type}</strong></p>
                    <p style="color:#4ade80; font-weight:600;">✨ Terres fertiles libres pour l'établissement d'un nouveau fief !</p>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <a href="?page=fleet&target_id=${data.planet_id}&mission=colonize" class="btn btn-primary">Établir un Fief</a>
                    <a href="?page=fleet&target_id=${data.planet_id}&mission=raid" class="btn btn-danger">Piller le Domaine</a>
                </div>
            </div>
        `;
    } else {
        const isOwn = (parseInt(data.user_id, 10) === <?= (int)$user['id'] ?>);
        const fKey = (data.faction || 'terran').toLowerCase();
        const fName = (data.faction || 'Terran').toUpperCase();
        const pts = (data.points != null) ? Number(data.points).toLocaleString() : '0';
        const targetUserId = parseInt(data.user_id, 10) || 0;

        title.innerText = `${data.planet_name} (Fief de ${data.username || 'Daimyō'})`;
        body.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                    <p style="margin-bottom:0.3rem;">Daimyō : 
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(${targetUserId})" 
                           style="color:#dc2626; font-weight:700; text-decoration:none;" title="Consulter la fiche du Daimyō">
                            👤 ${data.username || 'Daimyō'}
                        </a>
                        ${data.alliance_tag ? `[${data.alliance_tag}]` : ''}
                    </p>
                    <p style="margin-bottom:0.3rem;">Clan : <span class="faction-badge ${fKey}">${fName}</span></p>
                    <p>Puissance du Domaine : <strong>${pts} pts</strong></p>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                    <button type="button" onclick="openPlayerProfileModal(${targetUserId})" class="btn btn-secondary" style="border-color:#dc2626; color:#dc2626;">
                        👤 Fiche du Daimyō
                    </button>
                    ${!isOwn ? `
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=spy" class="btn btn-secondary">🥷 Infiltration Shinobi</a>
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=raid" class="btn btn-primary" style="background:#b91c1c;">⚔️ Lancer un Raid</a>
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=transport" class="btn btn-secondary">🐂 Convoi</a>
                        <a href="?page=messages&tab=compose&to=${encodeURIComponent(data.username || '')}" class="btn btn-secondary">✉️ Missive</a>
                    ` : `
                        <span style="color:#4ade80; font-size:0.85rem; font-weight:700; padding:0.4rem 0.6rem; background:rgba(74,222,128,0.1); border-radius:4px;">
                            🏯 Votre propre domaine castral
                        </span>
                    `}
                </div>
            </div>
        `;
    }
};

</script>
