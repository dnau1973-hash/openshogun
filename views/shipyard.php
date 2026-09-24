<?php
/**
 * Vue du Chantier Spatial et Production Navale
 */
require_once __DIR__ . '/../core/ShipyardEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$shipyardEngine = new ShipyardEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$shipyardLvl = $buildings['shipyard'] ?? 0;

$availableShips = $shipyardEngine->getAvailableShips((int)$planet['id'], $user['faction']);
$queue = $shipyardEngine->getQueue((int)$planet['id']);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🐎 Atelier de Siège & Écuries Provinciales - Niveau <?= $shipyardLvl ?></h2>
        <?php if ($user['faction'] === 'vorash'): ?>
            <span style="font-size:0.8rem; color:#fca5a5; background:rgba(239,68,68,0.2); padding:0.2rem 0.5rem; border-radius:4px;">
                ⚡ Bonus Clan Takeda : Entraînement accéléré (-20% temps)
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($shipyardLvl < 1): ?>
            <div style="text-align:center; padding:2rem; background:rgba(239,68,68,0.1); border:1px solid #ef4444; border-radius:8px;">
                <h3 style="color:#f87171; margin-bottom:0.5rem;">Atelier de siège non construit</h3>
                <p style="color:var(--text-muted); margin-bottom:1rem;">Vous devez construire un Atelier de Siège et des Écuries dans votre cité castrale pour mobiliser de la cavalerie et fabriquer des engins de guerre.</p>
                <a href="?page=city" class="btn btn-primary">Bâtir l'Atelier</a>
            </div>
        <?php else: ?>
            <!-- File active du Chantier -->
            <?php if (!empty($queue)): ?>
                <div class="card" style="margin-bottom:1.5rem; border-color:#dc2626;">
                    <div class="card-header">
                        <h4 class="card-title">🐎 En cours d'entraînement et d'assemblage</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($queue as $q): ?>
                            <div class="queue-item">
                                <div class="queue-info">
                                    <h4><?= $q['count'] ?>x <?= htmlspecialchars($q['ship_name']) ?></h4>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">Durée unitaire : <?= $q['unit_build_time'] ?>s</span>
                                </div>
                                <div class="queue-timer" data-countdown="<?= $q['finishes_at'] ?>">Calcul...</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grille des Vaisseaux & Engins de Siège Disponibles -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap:1.5rem;">
                <?php foreach ($availableShips as $s): ?>
                    <?php 
                        $imgFile = !empty($s['image']) ? $s['image'] : ($s['code'] . '.jpg');
                        $diskFile = __DIR__ . '/../public/assets/units/' . $imgFile;
                        if (file_exists($diskFile)) {
                            $imgSrc = '/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile);
                        } else {
                            $imgSrc = '/public/assets/tiles/tile_shipyard.png';
                        }
                        $fullImg = $imgSrc;

                        // Déterminer le badge de faction
                        $clanBadge = match($s['faction']) {
                            'terran' => ['name' => 'Clan Oda', 'color' => '#3b82f6', 'icon' => '🏯'],
                            'vorash' => ['name' => 'Clan Takeda', 'color' => '#ef4444', 'icon' => '🐎'],
                            'aethelis' => ['name' => 'Clan Tokugawa', 'color' => '#8b5cf6', 'icon' => '⛩️'],
                            default => ['name' => 'Logistique Impériale', 'color' => '#16a34a', 'icon' => '📦']
                        };

                        // Calcul du max finançable
                        $maxMetal = ($s['metal_cost'] > 0) ? floor($planet['metal'] / $s['metal_cost']) : 99999;
                        $maxCrystal = ($s['crystal_cost'] > 0) ? floor($planet['crystal'] / $s['crystal_cost']) : 99999;
                        $maxDeut = ($s['deuterium_cost'] > 0) ? floor($planet['deuterium'] / $s['deuterium_cost']) : 99999;
                        $maxAffordable = max(0, min($maxMetal, $maxCrystal, $maxDeut));
                    ?>
                    <div class="card unit-card" style="margin:0; background:var(--bg-surface, #fdfbf7); overflow:hidden; border:1px solid var(--border-color); border-radius:10px; display:flex; flex-direction:column; box-shadow:0 6px 18px rgba(0,0,0,0.05); transition:transform 0.2s ease, box-shadow 0.2s ease;">
                        
                        <!-- Illustration Grand Format de l'Engin / Cavalier -->
                        <div style="position:relative; width:100%; height:230px; overflow:hidden; background:var(--bg-ink, #ede5d5); border-bottom:1px solid var(--border-color); cursor:pointer;"
                             onclick="openUnitLightbox('<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= $fullImg ?>', '<?= htmlspecialchars(addslashes($s['description'])) ?>', '<?= $clanBadge['name'] ?>', 'Écuries & Siège')"
                             title="Cliquer pour admirer l'illustration en grand format">
                            
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="width:100%; height:100%; object-fit:cover; object-position:center; transition:transform 0.4s ease;">
                            
                            <!-- Badge de Clan / Faction -->
                            <div style="position:absolute; top:10px; left:10px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid <?= $clanBadge['color'] ?>; border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:<?= $clanBadge['color'] ?>; box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                <?= $clanBadge['icon'] ?> <?= $clanBadge['name'] ?>
                            </div>

                            <!-- Badge Effectif Écuries / Parc -->
                            <div style="position:absolute; top:10px; right:10px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid rgba(220,38,38,0.5); border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:#dc2626; box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                🐎 Disponibles : <?= number_format($s['stationed_count']) ?>
                            </div>

                            <!-- Bouton Agrandir Loupe -->
                            <div style="position:absolute; bottom:8px; right:8px; background:rgba(28,25,23,0.75); backdrop-filter:blur(4px); color:#ffffff; border-radius:4px; padding:3px 8px; font-size:0.7rem; display:flex; align-items:center; gap:4px; border:1px solid rgba(255,255,255,0.2);">
                                🔍 Agrandir
                            </div>
                        </div>

                        <!-- Titre & Faction -->
                        <div class="card-header" style="padding:0.85rem 1.15rem; background:transparent; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h3 class="card-title" style="font-size:1.05rem; display:flex; align-items:center; gap:0.4rem; color:var(--text-main); margin:0; font-weight:700;">
                                    <span><?= htmlspecialchars($s['name']) ?></span>
                                </h3>
                                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">
                                    <?= htmlspecialchars($clanBadge['name']) ?> &bull; Ateliers & Écuries
                                </span>
                            </div>
                            <span style="font-size:1.2rem;"><?= $clanBadge['icon'] ?></span>
                        </div>

                        <div class="card-body" style="padding:1.15rem; display:flex; flex-direction:column; flex:1;">
                            <p style="font-size:0.82rem; line-height:1.4; color:var(--text-muted); margin-bottom:0.85rem; min-height:42px;">
                                <?= htmlspecialchars($s['description']) ?>
                            </p>

                            <!-- Caractéristiques Martiales & Logistiques -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.75rem; background:var(--bg-ink, #ede5d5); padding:0.6rem; border-radius:6px; margin-bottom:0.85rem; border:1px solid var(--border-color); color:var(--text-main);">
                                <div title="Puissance d'assaut">⚔️ Attaque : <strong style="color:var(--red-primary, #dc2626);"><?= $s['attack'] ?></strong></div>
                                <div title="Pavois et défenses mobiles">🛡️ Pavois : <strong><?= $s['shield'] ?></strong></div>
                                <div title="Blindage et structure">🧱 Blindage : <strong><?= $s['defense'] ?></strong></div>
                                <div title="Vitesse de déplacement provincial">🐎 Vitesse : <strong><?= $s['speed'] ?></strong></div>
                                <div title="Capacité de transport de vivres et butin">🎒 Fret : <strong><?= $s['cargo_capacity'] ?></strong></div>
                                <div title="Temps de fabrication unitaire">⏱️ Vitesse : <strong><?= $s['effective_build_time'] ?>s</strong></div>
                            </div>

                            <!-- Coût en Matériaux -->
                            <div class="cost-row" style="margin:0.25rem 0 0.85rem 0; display:flex; gap:0.75rem; font-size:0.85rem; font-weight:600;">
                                <div class="cost-item" style="color:<?= ($planet['metal'] >= $s['metal_cost']) ? 'var(--res-metal)' : '#ef4444' ?>;" title="Bois de Cèdre"><span style="color:var(--res-metal);">🪵</span> <?= number_format($s['metal_cost']) ?></div>
                                <div class="cost-item" style="color:<?= ($planet['crystal'] >= $s['crystal_cost']) ? 'var(--res-crystal)' : '#ef4444' ?>;" title="Pierre de Taille"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($s['crystal_cost']) ?></div>
                                <div class="cost-item" style="color:<?= ($planet['deuterium'] >= $s['deuterium_cost']) ? 'var(--res-deut)' : '#ef4444' ?>;" title="Riz Impérial"><span style="color:var(--res-deut);">🌾</span> <?= number_format($s['deuterium_cost']) ?></div>
                            </div>

                            <!-- Formulaire de Mobilisation -->
                            <div style="margin-top:auto; padding-top:0.75rem; border-top:1px dashed var(--border-color);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; font-size:0.75rem; color:var(--text-muted);">
                                    <span>Ressources dispo pour : <strong style="color:var(--text-main);"><?= $maxAffordable ?></strong> max</span>
                                    <button type="button" onclick="document.getElementById('count-<?= $s['code'] ?>').value = <?= $maxAffordable ?>;" style="background:none; border:none; color:var(--red-primary, #dc2626); font-weight:700; cursor:pointer; text-decoration:underline; font-size:0.75rem; padding:0;">
                                        Max (<?= $maxAffordable ?>)
                                    </button>
                                </div>
                                <div style="display:flex; gap:0.5rem;">
                                    <input type="number" id="count-<?= $s['code'] ?>" min="1" max="<?= max(1, $maxAffordable) ?>" value="1" 
                                           style="width:80px; background:var(--bg-ink, #ede5d5); border:1px solid var(--border-color); color:var(--text-main); padding:0.5rem; border-radius:6px; text-align:center; font-weight:700; font-size:0.9rem;">
                                    <button class="btn btn-primary" style="flex:1; font-size:0.85rem; font-weight:700; padding:0.5rem 1rem;" 
                                            onclick="orderShips('<?= $s['code'] ?>')">
                                        Mobiliser
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Agrandissement d'Illustration (Lightbox Washi) -->
<div id="unit-lightbox-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(12,10,9,0.85); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;" onclick="closeUnitLightbox(event)">
    <div style="position:relative; max-width:560px; width:100%; background:var(--bg-surface, #fdfbf7); border-radius:12px; border:2px solid var(--border-highlight, #c2252b); overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
        <button onclick="closeUnitLightbox()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:50%; width:34px; height:34px; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10;">
            &times;
        </button>
        <div style="width:100%; height:380px; background:#1c1917; overflow:hidden;">
            <img id="lightbox-img" src="" alt="" style="width:100%; height:100%; object-fit:cover; object-position:center top;">
        </div>
        <div style="padding:1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                <h3 id="lightbox-title" style="margin:0; font-size:1.2rem; color:var(--text-main);">Titre</h3>
                <span id="lightbox-badge" style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:10px; background:rgba(220,38,38,0.1); color:#dc2626; border:1px solid rgba(220,38,38,0.3);">Écuries & Siège</span>
            </div>
            <div id="lightbox-role" style="font-size:0.8rem; color:var(--text-muted); font-style:italic; margin-bottom:0.75rem;">Rôle</div>
            <p id="lightbox-desc" style="font-size:0.85rem; line-height:1.5; color:var(--text-main); margin:0;">Description</p>
        </div>
    </div>
</div>

<script>
function openUnitLightbox(name, imgSrc, desc, role, badge) {
    document.getElementById('lightbox-title').textContent = name;
    document.getElementById('lightbox-img').src = imgSrc;
    document.getElementById('lightbox-desc').textContent = desc;
    document.getElementById('lightbox-role').textContent = role;
    document.getElementById('lightbox-badge').textContent = badge;
    const modal = document.getElementById('unit-lightbox-modal');
    modal.style.display = 'flex';
}

function closeUnitLightbox(e) {
    const modal = document.getElementById('unit-lightbox-modal');
    modal.style.display = 'none';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeUnitLightbox();
    }
});

async function orderShips(shipCode) {
    const input = document.getElementById(`count-${shipCode}`);
    const count = parseInt(input.value, 10);
    if (isNaN(count) || count <= 0) {
        showModalAlert('Veuillez entrer une quantité valide.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('ship_code', shipCode);
    formData.append('count', count);

    try {
        const res = await fetch('/api/shipyard.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de lancer la mobilisation des troupes.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec les écuries.', 'error');
    }
}
</script>

