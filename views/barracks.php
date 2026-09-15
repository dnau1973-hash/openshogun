<?php
/**
 * Vue du Dojo Militaire & Entraînement des Troupes (Style Travian Feudal)
 */
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$barracksEngine = new BarracksEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$barracksLvl = $buildings['barracks'] ?? 0;

$availableUnits = $barracksEngine->getAvailableUnits((int)$planet['id'], $user['faction']);
$queue = $barracksEngine->getQueue((int)$planet['id']);

$factionNames = [
    'terran' => 'Clan Oda',
    'vorash' => 'Clan Takeda',
    'aethelis' => 'Clan Tokugawa'
];
$userClanName = $factionNames[$user['faction']] ?? 'Armée Provinciale';
?>

<div class="card" style="border-top: 4px solid var(--red-primary); overflow:hidden; margin-bottom:1.5rem;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; background:linear-gradient(to right, rgba(194,37,43,0.06), transparent); padding:1rem 1.25rem;">
        <div style="display:flex; align-items:center; gap:1rem;">
            <div style="width:52px; height:52px; border-radius:8px; background:var(--bg-ink, #ede5d5); border:1px solid var(--border-color); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
                <img src="/public/assets/tile_barracks.png" alt="Dojo Militaire" style="width:44px; height:44px; object-fit:contain;">
            </div>
            <div>
                <h2 class="card-title" style="margin:0; font-size:1.3rem; display:flex; align-items:center; gap:0.6rem; color:var(--text-main);">
                    <span>🥋 Dojo Militaire & Caserne de Fief</span>
                    <span style="font-size:0.8rem; font-weight:700; padding:2px 8px; border-radius:12px; background:var(--red-soft, rgba(194,37,43,0.1)); border:1px solid var(--border-highlight, #c2252b); color:var(--red-primary, #c2252b);">
                        Niveau <?= $barracksLvl ?>
                    </span>
                </h2>
                <div style="font-size:0.85rem; color:var(--text-muted); margin-top:3px;">
                    Régiments du <strong><?= htmlspecialchars($userClanName) ?></strong> • Entraînez vos piquiers, tireurs mousquetaires, fiers samouraïs et champions de la garde.
                </div>
            </div>
        </div>

        <?php if ($user['faction'] === 'vorash'): ?>
            <span style="font-size:0.8rem; font-weight:700; color:#991b1b; background:rgba(153,27,27,0.1); border:1px solid rgba(153,27,27,0.3); padding:0.35rem 0.75rem; border-radius:6px; display:inline-flex; align-items:center; gap:0.4rem;">
                ⚡ Bonus Takeda : Vitesse d'entraînement +20%
            </span>
        <?php endif; ?>
    </div>

    <div class="card-body" style="padding:1.25rem;">
        <?php if ($barracksLvl < 1): ?>
            <div style="text-align:center; padding:2.5rem; background:rgba(239,68,68,0.06); border:1px dashed var(--red-primary, #c2252b); border-radius:10px;">
                <div style="font-size:2.5rem; margin-bottom:0.5rem;">🏯</div>
                <h3 style="color:var(--red-primary, #c2252b); margin-bottom:0.5rem;">Dojo Militaire non construit</h3>
                <p style="color:var(--text-muted); margin-bottom:1.25rem; max-width:500px; margin-left:auto; margin-right:auto;">
                    Vous devez bâtir un Dojo Militaire dans votre cité pour forger des armes et entraîner les guerriers de votre domaine.
                </p>
                <a href="?page=city" class="btn btn-primary" style="padding:0.6rem 1.5rem;">
                    Bâtir le Dojo dans la Cité &rarr;
                </a>
            </div>
        <?php else: ?>
            <!-- File active d'entraînement -->
            <?php if (!empty($queue)): ?>
                <div class="card" style="margin-bottom:1.75rem; border-left:4px solid var(--red-primary); background:var(--bg-surface, #fdfbf7); box-shadow:0 3px 10px rgba(0,0,0,0.04);">
                    <div class="card-header" style="background:transparent; padding:0.75rem 1rem; border-bottom:1px solid var(--border-color);">
                        <h4 class="card-title" style="font-size:0.95rem; margin:0; display:flex; align-items:center; gap:0.5rem; color:var(--text-main);">
                            <span>⏳</span> Régiments en cours de formation au Dojo
                        </h4>
                    </div>
                    <div class="card-body" style="padding:0.75rem 1rem;">
                        <?php foreach ($queue as $q): ?>
                            <div class="queue-item" style="display:flex; justify-content:space-between; align-items:center; padding:0.6rem 0.85rem; background:var(--bg-ink, #ede5d5); border:1px solid var(--border-color); border-radius:6px; margin-bottom:0.5rem;">
                                <div class="queue-info">
                                    <h4 style="margin:0; font-size:0.95rem; color:var(--text-main);">
                                        <?= $q['count'] ?>x <?= htmlspecialchars($q['unit_name']) ?>
                                    </h4>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">
                                        Temps par guerrier : <?= $q['unit_train_time'] ?>s
                                    </span>
                                </div>
                                <div class="queue-timer" data-countdown="<?= $q['finishes_at'] ?>" style="font-weight:700; font-family:monospace; color:var(--red-primary);">
                                    Calcul...
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grille des Soldats & Nouveaux Visuels Féodaux -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">
                <?php foreach ($availableUnits as $u): ?>
                    <?php 
                        $diskJpg = __DIR__ . "/../public/assets/units/{$u['code']}.jpg";
                        if (file_exists($diskJpg)) {
                            $imgSrc = "/public/assets/units/{$u['code']}.jpg?v=" . filemtime($diskJpg);
                            $fullImg = "/public/assets/units/{$u['code']}.jpg?v=" . filemtime($diskJpg);
                        } else {
                            $imgSrc = "/public/assets/units/{$u['code']}.svg";
                            $fullImg = $imgSrc;
                        }

                        $roleLabels = [
                            1 => 'Infanterie de ligne',
                            2 => 'Tir & Harcèlement',
                            3 => 'Assaut d\'élite',
                            4 => 'Garde du Daimyō'
                        ];
                        $roleText = $roleLabels[$u['tier']] ?? 'Guerrier Féodal';
                    ?>
                    <div class="card unit-card <?= !$u['can_train'] ? 'unit-locked' : '' ?>" style="margin:0; background:var(--bg-surface, #fdfbf7); overflow:hidden; border:1px solid var(--border-color); border-radius:10px; display:flex; flex-direction:column; box-shadow:0 6px 18px rgba(0,0,0,0.05); transition:transform 0.2s ease, box-shadow 0.2s ease;">
                        
                        <!-- Illustration Grand Format du Guerrier (Style Feodal Washi) -->
                        <div style="position:relative; width:100%; height:240px; overflow:hidden; background:var(--bg-ink, #ede5d5); border-bottom:1px solid var(--border-color); cursor:pointer;"
                             onclick="openUnitLightbox('<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= $fullImg ?>', '<?= htmlspecialchars(addslashes($u['description'])) ?>', '<?= $roleText ?>', 'Rang <?= $u['tier'] ?>')"
                             title="Cliquer pour admirer l'illustration en grand format">
                            
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($u['name']) ?>" class="unit-img" style="width:100%; height:100%; object-fit:cover; object-position:top center; transition:transform 0.4s ease;">
                            
                            <!-- Badge de Rang -->
                            <div style="position:absolute; top:10px; left:10px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid rgba(194,37,43,0.5); border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:var(--red-primary, #c2252b); box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                <?= $u['icon'] ?> Rang <?= $u['tier'] ?>
                            </div>

                            <!-- Badge Effectif Garnison -->
                            <div style="position:absolute; top:10px; right:10px; background:rgba(253,251,247,0.95); backdrop-filter:blur(6px); border:1px solid rgba(22,101,52,0.5); border-radius:6px; padding:3px 10px; font-size:0.75rem; font-weight:800; color:#166534; box-shadow:0 2px 6px rgba(0,0,0,0.12);">
                                🛡️ Garnison : <?= number_format($u['stationed_count']) ?>
                            </div>

                            <!-- Bouton Agrandir Loupe -->
                            <div style="position:absolute; bottom:8px; right:8px; background:rgba(28,25,23,0.75); backdrop-filter:blur(4px); color:#ffffff; border-radius:4px; padding:3px 8px; font-size:0.7rem; display:flex; align-items:center; gap:4px; border:1px solid rgba(255,255,255,0.2);">
                                🔍 Vue détaillée
                            </div>
                        </div>

                        <!-- Titre & Rôle -->
                        <div class="card-header" style="padding:0.85rem 1.15rem; background:transparent; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h3 class="card-title" style="font-size:1.05rem; display:flex; align-items:center; gap:0.4rem; color:var(--text-main); margin:0; font-weight:700;">
                                    <span><?= htmlspecialchars($u['name']) ?></span>
                                </h3>
                                <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">
                                    <?= $roleText ?>
                                </span>
                            </div>
                            <span style="font-size:1.25rem;"><?= $u['icon'] ?></span>
                        </div>

                        <div class="card-body" style="padding:1.15rem; display:flex; flex-direction:column; flex:1;">
                            <p style="font-size:0.82rem; line-height:1.4; color:var(--text-muted); margin-bottom:0.85rem; min-height:42px;">
                                <?= htmlspecialchars($u['description']) ?>
                            </p>

                            <!-- Caractéristiques Militaire Travian-Style -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.75rem; background:var(--bg-ink, #ede5d5); padding:0.6rem; border-radius:6px; margin-bottom:0.85rem; border:1px solid var(--border-color); color:var(--text-main);">
                                <div title="Puissance d'attaque en bataille">⚔️ Attaque : <strong style="color:var(--red-primary);"><?= $u['attack'] ?></strong></div>
                                <div title="Défense contre l'infanterie">🛡️ Df Infan : <strong><?= $u['def_infantry'] ?></strong></div>
                                <div title="Défense contre la cavalerie">🐎 Df Caval : <strong><?= $u['def_mech'] ?></strong></div>
                                <div title="Vitesse de marche sur la carte">🏃 Vitesse : <strong><?= $u['speed'] ?></strong></div>
                                <div title="Capacité d'emport de ressources pillées">🎒 Fret : <strong><?= $u['cargo_capacity'] ?></strong></div>
                                <div title="Temps d'entraînement unitaire">⏱️ Vitesse : <strong><?= $u['effective_train_time'] ?>s</strong></div>
                            </div>

                            <!-- Coût de Recrutement -->
                            <div class="cost-row" style="margin:0.25rem 0 0.85rem 0; display:flex; gap:0.75rem; font-size:0.85rem; font-weight:600;">
                                <div class="cost-item" title="Bois de Cèdre"><span style="color:var(--res-metal);">🪵</span> <?= number_format($u['metal_cost']) ?></div>
                                <div class="cost-item" title="Pierre de Taille"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($u['crystal_cost']) ?></div>
                                <div class="cost-item" title="Riz Impérial"><span style="color:var(--res-deut);">🌾</span> <?= number_format($u['deuterium_cost']) ?></div>
                            </div>

                            <!-- Formulaire de Recrutement -->
                            <div style="margin-top:auto; padding-top:0.75rem; border-top:1px dashed var(--border-color);">
                                <?php if ($u['can_train']): ?>
                                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                        <!-- Sélecteur rapide -->
                                        <div style="display:flex; gap:0.35rem; font-size:0.75rem;">
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 5)">+5</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 10)">+10</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 25)">+25</button>
                                            <button type="button" class="btn btn-secondary" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="setRecruits('<?= $u['code'] ?>', 50)">+50</button>
                                        </div>
                                        <div style="display:flex; gap:0.5rem;">
                                            <input type="number" id="unit-count-<?= $u['code'] ?>" min="1" max="1000" value="5" 
                                                   style="width:75px; background:var(--bg-card); border:1px solid var(--border-color); color:var(--text-main); padding:0.45rem; border-radius:6px; text-align:center; font-weight:bold; font-size:0.9rem;">
                                            <button class="btn btn-primary" style="flex:1; font-size:0.85rem; font-weight:700; padding:0.45rem 0.75rem; display:flex; align-items:center; justify-content:center; gap:0.4rem;" 
                                                    onclick="trainTroops('<?= $u['code'] ?>')">
                                                <span>🥋</span> Entraîner
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align:center; padding:0.6rem; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:6px; font-size:0.8rem; color:var(--red-primary, #c2252b); font-weight:700;">
                                        🔒 Requiert Dojo Niveau <?= $u['required_barracks_level'] ?>
                                    </div>
                                <?php endif; ?>
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
                <span id="lightbox-badge" style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:10px; background:var(--red-soft); color:var(--red-primary); border:1px solid var(--border-highlight);">Rang</span>
            </div>
            <div id="lightbox-role" style="font-size:0.8rem; color:var(--text-muted); font-style:italic; margin-bottom:0.75rem;">Rôle</div>
            <p id="lightbox-desc" style="font-size:0.85rem; line-height:1.5; color:var(--text-main); margin:0;">Description</p>
        </div>
    </div>
</div>

<script>
function setRecruits(unitCode, amount) {
    const input = document.getElementById(`unit-count-${unitCode}`);
    if (input) {
        input.value = amount;
    }
}

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

async function trainTroops(unitCode) {
    const input = document.getElementById(`unit-count-${unitCode}`);
    const count = parseInt(input.value, 10);
    if (isNaN(count) || count <= 0) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Veuillez spécifier un effectif valide.', 'warning');
        } else {
            alert('Veuillez spécifier un effectif valide.');
        }
        return;
    }

    const formData = new FormData();
    formData.append('unit_code', unitCode);
    formData.append('count', count);

    try {
        const res = await fetch('/api/barracks.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert(data.error || 'Impossible de lancer l\'entraînement.', 'error');
            } else {
                alert(data.error || 'Impossible de lancer l\'entraînement.');
            }
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') {
            showModalAlert('Erreur de communication avec le dojo militaire.', 'error');
        } else {
            alert('Erreur de communication avec le dojo militaire.');
        }
    }
}
</script>
