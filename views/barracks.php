<?php
/**
 * Vue de la Caserne Militaire et Entraînement des Soldats (Style Travian)
 */
require_once __DIR__ . '/../core/BarracksEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$barracksEngine = new BarracksEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$barracksLvl = $buildings['barracks'] ?? 0;

$availableUnits = $barracksEngine->getAvailableUnits((int)$planet['id'], $user['faction']);
$queue = $barracksEngine->getQueue((int)$planet['id']);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">🥋 Dojo Militaire & Entraînement des Guerriers - Niveau <?= $barracksLvl ?></h2>
        <?php if ($user['faction'] === 'vorash'): ?>
            <span style="font-size:0.8rem; color:#fca5a5; background:rgba(239,68,68,0.2); padding:0.2rem 0.5rem; border-radius:4px;">
                ⚡ Bonus Clan Takeda : Entraînement accéléré (-20% temps)
            </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($barracksLvl < 1): ?>
            <div style="text-align:center; padding:2rem; background:rgba(239,68,68,0.1); border:1px solid #ef4444; border-radius:8px;">
                <h3 style="color:#f87171; margin-bottom:0.5rem;">Dojo militaire non construit</h3>
                <p style="color:var(--text-muted); margin-bottom:1rem;">Vous devez bâtir un Dojo Militaire dans votre cité castrale pour lever et entraîner des guerriers.</p>
                <a href="?page=city" class="btn btn-primary">Bâtir le Dojo</a>
            </div>
        <?php else: ?>
            <!-- File active d'entraînement -->
            <?php if (!empty($queue)): ?>
                <div class="card" style="margin-bottom:1.5rem; border-color:#dc2626;">
                    <div class="card-header">
                        <h4 class="card-title">🥋 Guerriers en cours de formation</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($queue as $q): ?>
                            <div class="queue-item">
                                <div class="queue-info">
                                    <h4><?= $q['count'] ?>x <?= htmlspecialchars($q['unit_name']) ?></h4>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">Formation unitaire : <?= $q['unit_train_time'] ?>s</span>
                                </div>
                                <div class="queue-timer" data-countdown="<?= $q['finishes_at'] ?>">Calcul...</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Grille des Soldats Disponibles -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.25rem;">
                <?php foreach ($availableUnits as $u): ?>
                    <?php 
                        $imgSrc = "/public/assets/units/{$u['code']}.jpg";
                        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc)) {
                            $imgSrc = "/public/assets/units/{$u['code']}.svg";
                        }
                    ?>
                    <div class="card unit-card <?= !$u['can_train'] ? 'unit-locked' : '' ?>" style="margin:0; background:rgba(15,23,42,0.9); overflow:hidden; border:1px solid var(--border-color); border-radius:8px; display:flex; flex-direction:column;">
                        <!-- Illustration du Soldat -->
                        <div style="position:relative; width:100%; height:190px; overflow:hidden; background:#070a13;">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($u['name']) ?>" class="unit-img" style="width:100%; height:100%; object-fit:cover; object-position:top center; transition:transform 0.4s ease;">
                            <div style="position:absolute; top:8px; left:8px; background:rgba(10,15,29,0.88); backdrop-filter:blur(4px); border:1px solid rgba(220,38,38,0.5); border-radius:4px; padding:2px 8px; font-size:0.7rem; font-weight:800; color:#dc2626;">
                                Rang <?= $u['tier'] ?>
                            </div>
                            <div style="position:absolute; bottom:8px; right:8px; background:rgba(10,15,29,0.9); backdrop-filter:blur(4px); border:1px solid rgba(74,222,128,0.6); border-radius:4px; padding:2px 8px; font-size:0.75rem; font-weight:800; color:#4ade80;">
                                Garnison : <?= number_format($u['stationed_count']) ?>
                            </div>
                        </div>

                        <div class="card-header" style="border-top:1px solid rgba(255,255,255,0.08); padding:0.75rem 1rem;">
                            <h3 class="card-title" style="font-size:1rem; display:flex; align-items:center; gap:0.4rem;">
                                <span><?= $u['icon'] ?></span>
                                <span><?= htmlspecialchars($u['name']) ?></span>
                            </h3>
                        </div>
                        <div class="card-body" style="padding:1rem; display:flex; flex-direction:column; flex:1;">
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem; min-height:36px;">
                                <?= htmlspecialchars($u['description']) ?>
                            </p>

                            <!-- Caractéristiques Militaire Travian-Style -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.75rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; margin-bottom:0.75rem;">
                                <div>⚔️ Attaque : <strong><?= $u['attack'] ?></strong></div>
                                <div>🛡️ Df Infan : <strong><?= $u['def_infantry'] ?></strong></div>
                                <div>🐎 Df Caval : <strong><?= $u['def_mech'] ?></strong></div>
                                <div>🏃 Vitesse : <strong><?= $u['speed'] ?></strong></div>
                                <div>🎒 Fret : <strong><?= $u['cargo_capacity'] ?></strong></div>
                                <div>⏱️ Temps : <strong><?= $u['effective_train_time'] ?>s</strong></div>
                            </div>

                            <!-- Coût -->
                            <div class="cost-row" style="margin:0.5rem 0;">
                                <div class="cost-item"><span style="color:var(--res-metal);">🪵</span> <?= number_format($u['metal_cost']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($u['crystal_cost']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-deut);">🌾</span> <?= number_format($u['deuterium_cost']) ?></div>
                            </div>

                            <!-- Formulaire de Recrutement -->
                            <div style="margin-top:auto; padding-top:0.75rem;">
                                <?php if ($u['can_train']): ?>
                                    <div style="display:flex; gap:0.5rem;">
                                        <input type="number" id="unit-count-<?= $u['code'] ?>" min="1" max="1000" value="5" 
                                               style="width:80px; background:rgba(0,0,0,0.5); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px; text-align:center;">
                                        <button class="btn btn-primary" style="flex:1; font-size:0.8rem;" 
                                                onclick="trainTroops('<?= $u['code'] ?>')">
                                            Entraîner les Guerriers
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align:center; padding:0.4rem; background:rgba(239,68,68,0.15); border-radius:4px; font-size:0.8rem; color:#fca5a5;">
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

<script>
async function trainTroops(unitCode) {
    const input = document.getElementById(`unit-count-${unitCode}`);
    const count = parseInt(input.value, 10);
    if (isNaN(count) || count <= 0) {
        showModalAlert('Veuillez spécifier un effectif valide.', 'warning');
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
            showModalAlert(data.error || 'Impossible de lancer l\'entraînement.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le dojo militaire.', 'error');
    }
}
</script>

