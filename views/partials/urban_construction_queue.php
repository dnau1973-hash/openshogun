<?php
/**
 * Vue Partielle Mutualisée : File des Chantiers (Urbains & Ruraux)
 * Utilisée dans : views/city.php et views/resources.php
 *
 * Variables attendues :
 * - $queue (array) : liste des travaux en cours depuis BuildingEngine::getQueue()
 * - $planet (array, optionnel) : données du fief actif
 * - $isTerran (bool, optionnel) : appartenance au Clan Oda
 * - $queueTitle (string, optionnel) : titre du bloc (défaut : "Chantiers Urbains")
 */

require_once __DIR__ . '/../../config/game_constants.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';

$queue = $queue ?? [];
$queueTitle = $queueTitle ?? 'Chantiers Urbains';
$isTerran = $isTerran ?? (($planet['faction'] ?? '') === 'terran');
?>

<div class="card shadow-sm" id="urban-construction-queue-card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="card-title mb-0 fs-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-helmet-safety text-warning"></i>
            <span><?= htmlspecialchars($queueTitle) ?></span>
        </h3>
        <div class="d-flex align-items-center gap-1">
            <?php if ($isTerran): ?>
                <span class="badge bg-danger-lt text-danger fw-bold" style="font-size:0.65rem;" title="Privilège Clan Oda : Chantiers rural &amp; urbain simultanés autorisés">
                    <i class="fa-solid fa-bolt me-1"></i>Oda
                </span>
            <?php endif; ?>
            <span class="badge bg-warning-lt fw-bold"><?= count($queue) ?> actif(s)</span>
        </div>
    </div>
    <div class="card-body p-2">
        <?php if (empty($queue)): ?>
            <p style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:1.25rem 0; margin-bottom:0;">
                <i class="fa-solid fa-helmet-safety text-secondary d-block mb-2 fs-2 opacity-75"></i>
                Aucune construction en cours sur ce fief.
            </p>
        <?php else: ?>
            <?php foreach ($queue as $q): ?>
                <?php
                    $isRural = ($q['build_category'] === 'rural_plot');
                    $isField = ($q['build_category'] === 'field');

                    if ($isRural) {
                        $rType = $q['target_id'];
                        $rMeta = RuralPlotEngine::STRUCTURES[$rType] ?? null;
                        $name = $rMeta ? $rMeta['name'] : ucfirst($rType);
                        $icon = $rMeta['icon'] ?? 'fa-solid fa-seedling';
                        $catBadge = '<span class="badge bg-green-lt" style="font-size:0.65rem;">Domaine Rural</span>';
                    } elseif ($isField) {
                        $tSlot = (int)$q['target_id'];
                        $tType = isset($fieldsBySlot[$tSlot]['type']) ? $fieldsBySlot[$tSlot]['type'] : (FIELD_LAYOUT[$tSlot] ?? 'metal_mine');
                        $name = (FIELD_TYPES[$tType]['name'] ?? 'Parcelle') . " #{$tSlot}";
                        $icon = 'fa-solid fa-wheat-awn';
                        $catBadge = '<span class="badge bg-success-lt" style="font-size:0.65rem;">Parcelle Rurale</span>';
                    } else {
                        $name = BUILDINGS[$q['target_id']]['name'] ?? $q['target_id'];
                        $icon = BUILDINGS[$q['target_id']]['icon'] ?? 'fa-solid fa-landmark';
                        $catBadge = '<span class="badge bg-primary-lt" style="font-size:0.65rem;">Cité Castrale</span>';
                    }

                    $qNow = time();
                    $qStart = (int)($q['started_at'] ?? $qNow);
                    $qEnd = (int)($q['finishes_at'] ?? $qNow);
                    $qTotal = max(1, $qEnd - $qStart);
                    $qElapsed = max(0, $qNow - $qStart);
                    $qPct = min(100, max(0, (int)round(($qElapsed / $qTotal) * 100)));
                    $isDemolish = ((int)$q['target_level'] === 0);
                ?>
                <div class="queue-item p-2 mb-2 rounded bg-surface-secondary border" style="display: flex; flex-direction: column; align-items: stretch; gap: 0.4rem;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <div class="queue-info">
                            <h4 class="mb-0 fw-bold d-flex align-items-center gap-2" style="font-size:0.9rem;">
                                <i class="<?= $icon ?> text-warning"></i>
                                <span><?= htmlspecialchars($name) ?></span>
                            </h4>
                            <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                <?php if ($isDemolish): ?>
                                    <span class="badge bg-danger-lt fw-bold" style="font-size:0.7rem;">
                                        <i class="fa-solid fa-trash-can me-1"></i>Démolition
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-lt fw-bold" style="font-size:0.7rem;">
                                        Niveau <?= $q['target_level'] ?>
                                    </span>
                                <?php endif; ?>
                                <?= $catBadge ?>
                            </div>
                        </div>

                        <!-- Bouton Annulation de Chantier (Remboursement partiel) -->
                        <div>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger py-0 px-2 font-monospace" 
                                    style="font-size:0.75rem;" 
                                    title="Annuler ce chantier (Remboursement de 80% des ressources)"
                                    onclick="<?= $isRural ? "typeof cancelRuralUpgrade === 'function' ? cancelRuralUpgrade({$q['id']}, this) : cancelBuild({$q['id']})" : "cancelBuild({$q['id']})" ?>">
                                <i class="fa-solid fa-xmark me-1"></i>Annuler
                            </button>
                        </div>
                    </div>

                    <!-- Barre d'avancement animée et décompte dynamique -->
                    <div class="queue-progress-box mt-1">
                        <div class="progress" style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?= $isDemolish ? 'danger' : 'warning' ?> building-progress-bar"
                                 role="progressbar"
                                 style="width: <?= $qPct ?>%;"
                                 aria-valuenow="<?= $qPct ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 data-started="<?= $qStart ?>"
                                 data-finishes="<?= $qEnd ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.75rem;">
                            <span class="text-secondary fw-semibold">Avancement : <strong class="text-dark building-progress-pct"><?= $qPct ?>%</strong></span>
                            <span class="queue-timer font-monospace fw-bold text-danger building-time-remaining" data-countdown="<?= $qEnd ?>">En cours</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
