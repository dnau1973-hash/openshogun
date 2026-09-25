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

        <!-- File d'entraînement en cours -->
        <?php if (!empty($trainingQueue)): ?>
            <div class="mt-2 pt-2 border-top">
                <div class="text-danger fw-bold mb-1" style="font-size:0.75rem;">
                    ⏳ Entraînement au Dojo :
                </div>
                <?php foreach ($trainingQueue as $tq): ?>
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= $tq['unit_icon'] ?> <?= $tq['count'] ?>x <?= htmlspecialchars($tq['unit_name']) ?></span>
                        <span class="queue-timer badge bg-secondary-lt" data-countdown="<?= $tq['finishes_at'] ?>" style="font-size:0.7rem;">Calcul...</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

