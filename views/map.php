<?php
/**
 * Vue de la Carte des Provinces du Japon Féodal (Plein Écran / Pleine Largeur)
 * OpenShogun - Carte interactive plein format avec déplacement fluide & donjons authentiques
 */
$centerX = isset($_GET['x']) ? (int)$_GET['x'] : (int)$planet['coord_x'];
$centerY = isset($_GET['y']) ? (int)$_GET['y'] : (int)$planet['coord_y'];
?>

<div class="card card-map-fullwidth" style="border-top: 4px solid var(--red-primary); margin: 0 0 1.5rem 0; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 25px rgba(0,0,0,0.06);">
    <div class="card-header" style="background: linear-gradient(135deg, rgba(194,37,43,0.06) 0%, rgba(253,251,247,0.98) 100%); padding: 0.9rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.6rem;">🗾</span>
            <div>
                <h2 class="card-title" style="margin: 0; font-size: 1.25rem; color: var(--text-main); font-weight: 900;">
                    Carte des Provinces & Fiefs du Japon Féodal
                </h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Fief d'attache : <strong><?= htmlspecialchars($planet['name']) ?></strong> <span style="color: var(--red-primary); font-family: monospace;">[<?= $planet['coord_x'] ?> : <?= $planet['coord_y'] ?>]</span> &bull; Exploration panoramique pleine largeur
                </div>
            </div>
        </div>

        <!-- Barre de saut rapide et d'instructions -->
        <div class="galaxy-nav-bar" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin: 0;">
            <form id="jumpCoordsForm" onsubmit="event.preventDefault(); jumpToCoords();" style="display: flex; align-items: center; gap: 0.4rem; background: var(--bg-ink, #ede5d5); padding: 0.3rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color);">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Aller en :</label>
                <span style="font-size: 0.8rem; font-weight: 700;">X</span>
                <input type="number" id="inputCoordX" value="<?= $centerX ?>" style="width: 60px; background: #fff; border: 1px solid var(--border-color); color: var(--text-main); padding: 0.25rem 0.4rem; border-radius: 4px; text-align: center; font-weight: 700;">
                <span style="font-size: 0.8rem; font-weight: 700;">Y</span>
                <input type="number" id="inputCoordY" value="<?= $centerY ?>" style="width: 60px; background: #fff; border: 1px solid var(--border-color); color: var(--text-main); padding: 0.25rem 0.4rem; border-radius: 4px; text-align: center; font-weight: 700;">
                <button type="submit" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem; font-weight: 700;">Marcher</button>
            </form>

            <div style="font-size: 0.8rem; color: var(--red-primary); background: rgba(194,37,43,0.07); padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(194,37,43,0.2);">
                🖐️ <em>Glissez la carte (Drag & Drop) ou utilisez les flèches du clavier.</em>
            </div>
        </div>
    </div>

    <!-- Légende des Terroirs & Saut de Quadrants (Style Travian) -->
    <div style="background: var(--bg-ink, #ede5d5); border-bottom: 1px solid var(--border-color); padding: 0.5rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; font-size: 0.8rem;">
        <div style="display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap;">
            <span style="font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">🗾 Terroirs :</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_plains.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Plaines</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_forest.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Forêt de Cèdres</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_mountain.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Montagnes</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_lake.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Lacs & Eaux</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_hills.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Collines</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_village.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Fief Castral</span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;"><img src="/public/assets/map/tile_authentic_castle.jpg" style="width:18px; height:18px; border-radius:3px; object-fit:cover; border:1px solid rgba(0,0,0,0.15);"> Donjon Sacré</span>
        </div>

        <div style="display: flex; align-items: center; gap: 0.4rem;">
            <span style="font-weight: 700; color: var(--text-muted); margin-right: 0.2rem;">Saut de Zone :</span>
            <button type="button" class="btn btn-secondary" onclick="galaxyMap.moveTo(-16, 16)" style="font-size:0.75rem; padding:0.25rem 0.55rem;" title="Nord-Ouest [- / +]">↖️ N-O</button>
            <button type="button" class="btn btn-secondary" onclick="galaxyMap.moveTo(16, 16)" style="font-size:0.75rem; padding:0.25rem 0.55rem;" title="Nord-Est [+ / +]">↗️ N-E</button>
            <button type="button" class="btn btn-secondary" onclick="galaxyMap.moveTo(-16, -16)" style="font-size:0.75rem; padding:0.25rem 0.55rem;" title="Sud-Ouest [- / -]">↙️ S-O</button>
            <button type="button" class="btn btn-secondary" onclick="galaxyMap.moveTo(16, -16)" style="font-size:0.75rem; padding:0.25rem 0.55rem;" title="Sud-Est [+ / -]">↘️ S-E</button>
            <button type="button" class="btn btn-secondary" onclick="galaxyMap.moveTo(0, 0)" style="font-size:0.75rem; padding:0.25rem 0.55rem;" title="Centre Impérial [0 : 0]">⛩️ Centre</button>
        </div>
    </div>

    <div class="card-body" style="padding: 0; position: relative;">
        <!-- Conteneur Interactif Drag-and-Drop Pleine Largeur -->
        <div class="galaxy-map-wrapper map-fullwidth-wrapper" id="galaxyMapContainer" style="width: 100%; border: none; border-radius: 0;">
            <!-- Rendu interactif via GalaxyMapController -->
        </div>

        <!-- Panneau de Renseignements Planétaires / Fief Sélectionné -->
        <div id="planetDetailsCard" class="card" style="margin: 1.25rem; display: none; border: 1px solid #dc2626; background: rgba(17,18,24,0.96); border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
            <div class="card-header" style="background: linear-gradient(135deg, rgba(185,28,28,0.25) 0%, rgba(17,18,24,0.9) 100%); border-bottom: 1px solid rgba(220,38,38,0.3); padding: 0.9rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" id="selectedPlanetTitle" style="margin: 0; font-size: 1.15rem; color: #fff;">Détails du Domaine Castral</h3>
                <span id="selectedPlanetCoords" style="color: #facc15; font-family: monospace; font-size: 1rem; font-weight: 800;"></span>
            </div>
            <div class="card-body" id="selectedPlanetBody" style="padding: 1.25rem; color: #e4e4e7;">
                <!-- Rempli dynamiquement par JavaScript -->
            </div>
        </div>
    </div>
</div>

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

    // Gestion exclusive des 12 Donjons Authentiques du Japon (現存十二天守)
    if (data.is_authentic_castle) {
        title.innerHTML = `🏯 <span style="color:#fbbf24;">${data.castle_name}</span> &bull; <span style="font-size:0.95rem; color:#fde047;">${data.castle_kanji || ''}</span>`;
        body.innerHTML = `
            <div style="background:linear-gradient(135deg, rgba(245,158,11,0.18) 0%, rgba(185,28,28,0.2) 100%); border:1px solid rgba(245,158,11,0.5); padding:1.1rem; border-radius:8px; margin-bottom:1.1rem; box-shadow:0 4px 15px rgba(0,0,0,0.3);">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.6rem;">
                    <span class="badge" style="background:#f59e0b; color:#1c1917; font-weight:800; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; letter-spacing:0.5px;">
                        👑 TRÉSOR NATIONAL &bull; DONJON AUTHENTIQUE DU JAPON (現存十二天守)
                    </span>
                    <span style="color:#fde047; font-size:0.8rem; font-weight:700;">
                        📍 ${data.castle_province || 'Province Historique'}
                    </span>
                </div>
                <p style="color:#fef3c7; font-size:0.92rem; margin:0.4rem 0 0.6rem 0; line-height:1.45;">
                    Ce donjon d'époque Sengoku-Edo est l'une des 12 forteresses d'origine préservées du Japon. 
                    <strong style="color:#fbbf24;">Enjeu suprême de la Bataille Finale du Shogunat :</strong> les clans qui prendront le contrôle de ces citadelles sacrées détermineront l'avènement du prochain Shogun.
                </p>
                <div style="font-size:0.8rem; color:#f59e0b; display:flex; gap:1.2rem; flex-wrap:wrap;">
                    <span>Bâtisseur : <strong>${data.castle_builder || 'Maître Féodal'}</strong></span>
                    <span>Garnison Sacrée : <strong style="color:#4ade80;">25,000 pts défense</strong></span>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem;">
                <a href="/?page=castle&code=${encodeURIComponent(data.castle_code || '')}" class="btn btn-warning" style="font-weight:800; display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.4rem; font-size:0.95rem; background:#f59e0b; color:#18181b; border:none; text-decoration:none; border-radius:6px; box-shadow:0 3px 12px rgba(245,158,11,0.4);">
                    <span>📜</span> Découvrir l'Histoire & les Enjeux du Château &rarr;
                </a>
                <div style="display:flex; gap:0.5rem;">
                    ${data.planet_id ? `
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=spy" class="btn btn-secondary">🥷 Sonder la Citadelle</a>
                        <a href="?page=fleet&target_id=${data.planet_id}&mission=raid" class="btn btn-danger">⚔️ Assaillir</a>
                    ` : `
                        <span style="font-size:0.8rem; color:var(--text-muted); align-self:center;">Sanctuaire Inviolé</span>
                    `}
                </div>
            </div>
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

