<?php
/**
 * Panel des Soldats et Garnison (Style Travian)
 * Widget affiché dans la barre latérale pour visualiser les effectifs militaires
 */
require_once __DIR__ . '/../../core/BarracksEngine.php';

$barracksEngineWidget = new BarracksEngine();
$stationedTroops = $barracksEngineWidget->getStationedUnits((int)$planet['id'], $user['faction']);
$trainingQueue = $barracksEngineWidget->getQueue((int)$planet['id']);

require_once __DIR__ . '/../../core/PlanetEngine.php';

$planetEngineWidget = new PlanetEngine();
$panelBuildings = $planetEngineWidget->getBuildings((int)$planet['id']);
$wallLevel = (int)($panelBuildings['wall'] ?? 0);
$wallMultPct = ($user['faction'] === 'aethelis') ? 5 : (($user['faction'] === 'vorash') ? 3.5 : 4);
$wallBonusFactor = 1.0 + ($wallLevel * ($wallMultPct / 100));

$totalSoldiers = 0;
$totalAttackPower = 0;
$totalDefensePower = 0;

foreach ($stationedTroops as $t) {
    $cnt = (int)$t['stationed_count'];
    $totalSoldiers += $cnt;
    $totalAttackPower += $cnt * (int)$t['attack'];
    $totalDefensePower += $cnt * ((int)$t['def_infantry'] + (int)$t['def_mech']);
}

$totalFortifiedDefense = (int)($totalDefensePower * $wallBonusFactor) + ($wallLevel * 25);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <h3 class="card-title m-0" style="font-size: 0.95rem;">
            <span>⚔️</span> Garnison du Domaine
        </h3>
        <a href="?page=barracks" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Accéder au Dojo Militaire">
            Dojo &rarr;
        </a>
    </div>
    <div class="card-body p-2">
        <!-- Liste des Soldats de la Faction -->
        <div class="d-flex flex-column gap-1 mb-2">
            <?php foreach ($stationedTroops as $t): ?>
                <?php
                    $count = (int)$t['stationed_count'];
                    $hasUnits = ($count > 0);
                    $imgFile = !empty($t['image']) ? $t['image'] : ($t['code'] . '.jpg');
                    $diskFile = __DIR__ . '/../../public/assets/units/' . $imgFile;
                    if (file_exists($diskFile)) {
                        $imgSrc = '/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile);
                    } else {
                        $legacyDisk = __DIR__ . "/../../public/assets/units/{$t['code']}.jpg";
                        if (file_exists($legacyDisk)) {
                            $imgSrc = "/public/assets/units/{$t['code']}.jpg?v=" . filemtime($legacyDisk);
                        } else {
                            $imgSrc = "/public/assets/units/{$t['code']}.svg";
                        }
                    }
                ?>
                <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded border bg-light-lt <?= !$hasUnits ? 'opacity-50' : '' ?>">
                    <div class="d-flex align-items-center gap-2">
                        <div class="ai-image-container" style="position:relative; width:28px; height:28px; flex-shrink:0;">
                            <img src="<?= $imgSrc ?>" alt="" class="rounded border" style="width:28px; height:28px; object-fit:cover;">
                            <?= AiPromptHelper::renderBadge($imgFile, $t['name'], $imgSrc, 'ai-prompt-badge-sm') ?>
                        </div>
                        <span class="small <?= $hasUnits ? 'fw-bold' : 'text-muted' ?>">
                            <?= htmlspecialchars($t['name']) ?>
                        </span>
                    </div>
                    <strong class="font-monospace <?= $hasUnits ? 'text-danger' : 'text-muted' ?>" style="font-size:0.85rem;">
                        <?= number_format($count) ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bilan Puissance de Garnison -->
        <div class="p-2 rounded border bg-light-lt small">
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Total Guerriers :</span>
                <strong><?= number_format($totalSoldiers) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Puissance d'Attaque :</span>
                <strong class="text-danger"><?= number_format($totalAttackPower) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">Défense des Troupes :</span>
                <strong class="text-success"><?= number_format($totalDefensePower) ?></strong>
            </div>
            <div class="d-flex justify-content-between pt-1 border-top border-dashed align-items-center">
                <a href="/?page=building&code=wall" class="text-decoration-none text-muted" title="Accéder aux Remparts Féodaux">
                    <span style="font-size:0.75rem;">🧱 Remparts (Niv. <?= $wallLevel ?>) :</span>
                </a>
                <strong class="text-primary" style="font-size:0.82rem;">
                    <?= ($wallLevel > 0) ? ('+' . ($wallLevel * $wallMultPct) . '% (' . number_format($totalFortifiedDefense) . ')') : '<a href="/?page=building&code=wall" class="text-decoration-underline text-warning small">Non Bâti</a>' ?>
                </strong>
            </div>
        </div>

        <!-- File d'entraînement en cours (Double Progression au fil de l'eau) -->
        <?php if (!empty($trainingQueue)): ?>
            <div class="mt-2 pt-2 border-top">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-danger fw-bold" style="font-size:0.75rem;">
                        ⏳ Entraînement (au fil de l'eau) :
                    </span>
                    <span class="badge bg-danger-lt" style="font-size:0.65rem;">
                        <?= count($trainingQueue) ?> lot(s)
                    </span>
                </div>
                <?php foreach ($trainingQueue as $tq): ?>
                    <div class="p-2 mb-2 rounded border bg-light-subtle" style="font-size:0.72rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold text-truncate"><?= $tq['unit_icon'] ?> <?= htmlspecialchars($tq['unit_name']) ?></span>
                            <span class="badge bg-danger text-white rounded-pill font-monospace" style="font-size:0.65rem; padding:1px 6px;">
                                <?= $tq['completed_count'] ?> / <?= $tq['total_count'] ?>
                            </span>
                        </div>
                        <!-- Barre 1 : Unité actuelle -->
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.65rem; margin-bottom:2px;">
                            <span>⚡ Guerrier <?= $tq['current_unit_number'] ?>/<?= $tq['total_count'] ?></span>
                            <span class="font-monospace text-danger fw-bold"><?= $tq['unit_pct'] ?>%</span>
                        </div>
                        <div class="progress mb-1" style="height:4px; background:rgba(0,0,0,0.06);">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: <?= $tq['unit_pct'] ?>%;"></div>
                        </div>
                        <!-- Barre 2 : Lot global -->
                        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.65rem; margin-bottom:2px;">
                            <span>📦 Lot global</span>
                            <span class="font-monospace text-primary fw-bold"><?= $tq['lot_pct'] ?>%</span>
                        </div>
                        <div class="progress" style="height:5px; background:rgba(0,0,0,0.06);">
                            <div class="progress-bar bg-primary" style="width: <?= $tq['lot_pct'] ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

