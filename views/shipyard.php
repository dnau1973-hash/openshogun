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

            <!-- Grille des Vaisseaux Disponibles -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.25rem;">
                <?php foreach ($availableShips as $s): ?>
                    <div class="card" style="margin:0; background:rgba(15,23,42,0.85);">
                        <div class="card-header">
                            <h3 class="card-title" style="font-size:1rem;"><?= htmlspecialchars($s['name']) ?></h3>
                            <span style="font-size:0.8rem; color:#dc2626; font-weight:700;">Écuries / Parc : <?= $s['stationed_count'] ?></span>
                        </div>
                        <div class="card-body">
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem; min-height:40px;">
                                <?= htmlspecialchars($s['description']) ?>
                            </p>

                            <!-- Caractéristiques de Combat -->
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.75rem; background:rgba(0,0,0,0.3); padding:0.5rem; border-radius:6px; margin-bottom:0.75rem;">
                                <div>⚔️ Attaque : <strong><?= $s['attack'] ?></strong></div>
                                <div>🛡️ Pavois : <strong><?= $s['shield'] ?></strong></div>
                                <div>🧱 Blindage : <strong><?= $s['defense'] ?></strong></div>
                                <div>🐎 Vitesse : <strong><?= $s['speed'] ?></strong></div>
                                <div>🎒 Fret : <strong><?= $s['cargo_capacity'] ?></strong></div>
                                <div>⏱️ Temps : <strong><?= $s['effective_build_time'] ?>s</strong></div>
                            </div>

                            <!-- Coût -->
                            <div class="cost-row" style="margin:0.5rem 0;">
                                <div class="cost-item"><span style="color:var(--res-metal);">🪵</span> <?= number_format($s['metal_cost']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($s['crystal_cost']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-deut);">🌾</span> <?= number_format($s['deuterium_cost']) ?></div>
                            </div>

                            <!-- Formulaire de Commande -->
                            <div style="display:flex; gap:0.5rem; margin-top:0.75rem;">
                                <input type="number" id="count-<?= $s['code'] ?>" min="1" max="500" value="1" 
                                       style="width:80px; background:rgba(0,0,0,0.5); border:1px solid var(--border-color); color:#fff; padding:0.4rem; border-radius:4px; text-align:center;">
                                <button class="btn btn-primary" style="flex:1; font-size:0.8rem;" 
                                        onclick="orderShips('<?= $s['code'] ?>')">
                                    Mobiliser
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
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

