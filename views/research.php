<?php
/**
 * Vue de la Recherche et Technologies
 */
require_once __DIR__ . '/../core/ResearchEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';

$researchEngine = new ResearchEngine();
$planetEngine = new PlanetEngine();

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$labLvl = $buildings['research_lab'] ?? 0;

$researches = $researchEngine->getResearches((int)$user['id'], (int)$planet['id']);
$activeResearch = $researchEngine->getActiveQueue((int)$user['id']);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📜 Académie des Savoirs & Forge Militaire - Niveau <?= $labLvl ?></h2>
    </div>
    <div class="card-body">
        <?php if ($labLvl < 1): ?>
            <div style="text-align:center; padding:2rem; background:rgba(239,68,68,0.1); border:1px solid #ef4444; border-radius:8px;">
                <h3 style="color:#f87171; margin-bottom:0.5rem;">Académie non construite</h3>
                <p style="color:var(--text-muted); margin-bottom:1rem;">Vous devez bâtir une Académie des Savoirs dans votre cité castrale pour perfectionner la métallurgie et l'art de la guerre.</p>
                <a href="?page=city" class="btn btn-primary">Bâtir l'Académie</a>
            </div>
        <?php else: ?>
            <!-- Recherche en cours -->
            <?php if ($activeResearch): ?>
                <div class="card" style="margin-bottom:1.5rem; border-color:#a855f7;">
                    <div class="card-header">
                        <h4 class="card-title" style="color:#c084fc;">📜 Enseignement & Perfectionnement en Cours</h4>
                    </div>
                    <div class="card-body">
                        <div class="queue-item">
                            <div class="queue-info">
                                <h4><?= htmlspecialchars($activeResearch['research_name']) ?></h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);">Palier <?= $activeResearch['target_level'] ?></span>
                            </div>
                            <div class="queue-timer" data-countdown="<?= $activeResearch['finishes_at'] ?>">Calcul...</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des Technologies -->
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1.25rem;">
                <?php foreach ($researches as $r): ?>
                    <div class="card" style="margin:0; background:rgba(15,23,42,0.85);">
                        <div class="card-header">
                            <h3 class="card-title" style="font-size:1rem;"><?= htmlspecialchars($r['name']) ?></h3>
                            <span class="building-lvl-badge">Niveau <?= $r['current_level'] ?></span>
                        </div>
                        <div class="card-body">
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem; min-height:40px;">
                                <?= htmlspecialchars($r['description']) ?>
                            </p>

                            <!-- Coût pour niveau suivant -->
                            <div class="cost-row" style="margin:0.5rem 0;">
                                <div class="cost-item"><span style="color:var(--res-metal);">🪵</span> <?= number_format($r['cost_metal']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-crystal);">🪨</span> <?= number_format($r['cost_crystal']) ?></div>
                                <div class="cost-item"><span style="color:var(--res-deut);">🌾</span> <?= number_format($r['cost_deuterium']) ?></div>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem;">
                                <span style="font-size:0.8rem; color:var(--text-muted); font-family:monospace;">
                                    ⏱️ <?= sprintf('%02d:%02d:%02d', ($r['duration']/3600), ($r['duration']/60%60), $r['duration']%60) ?>
                                </span>
                                <button class="btn btn-primary" style="font-size:0.8rem; padding:0.4rem 0.8rem;" 
                                        <?= $activeResearch ? 'disabled style="opacity:0.5;"' : '' ?>
                                        onclick="startTech('<?= $r['code'] ?>')">
                                    Développer (Niv <?= $r['next_level'] ?>)
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
async function startTech(code) {
    const formData = new FormData();
    formData.append('research_code', code);

    try {
        const res = await fetch('/api/research.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'initier la recherche.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec l\'académie des savoirs.', 'error');
    }
}
</script>

