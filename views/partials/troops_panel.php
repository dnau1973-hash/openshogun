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
    <div class="card-header">
        <h3 class="card-title" style="font-size: 1rem;">
            <span>⚔️</span> Garnison du Domaine
        </h3>
        <a href="?page=barracks" class="btn btn-secondary" style="font-size:0.7rem; padding:0.2rem 0.5rem;" title="Accéder au Dojo Militaire">
            Dojo &rarr;
        </a>
    </div>
    <div class="card-body" style="padding: 0.85rem;">
        <!-- Liste des Soldats de la Faction (Style Travian) -->
        <div style="display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1rem;">
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
                <div style="display:flex; justify-content:space-between; align-items:center; padding:0.35rem 0.5rem; background:var(--bg-ink, #ede5d5); border:1px solid var(--border-color); border-radius:4px; <?= !$hasUnits ? 'opacity:0.6;' : '' ?>">
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <img src="<?= $imgSrc ?>" alt="" class="unit-img-thumb" style="width:28px; height:28px; border-radius:4px; object-fit:cover; border:1px solid var(--border-color); background:var(--bg-card);">
                        <span style="font-size:0.85rem; font-weight:<?= $hasUnits ? '600' : 'normal' ?>; color:<?= $hasUnits ? 'var(--text-main)' : 'var(--text-muted)' ?>;">
                            <?= htmlspecialchars($t['name']) ?>
                        </span>
                    </div>
                    <strong style="font-size:0.9rem; font-family:monospace; color:<?= $hasUnits ? 'var(--red-primary, #c2252b)' : 'var(--text-muted)' ?>;">
                        <?= number_format($count) ?>
                    </strong>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bilan Puissance de Garnison -->
        <div style="background:var(--bg-surface, #fdfbf7); border:1px solid var(--border-color); padding:0.6rem; border-radius:6px; font-size:0.8rem;">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
                <span style="color:var(--text-muted);">Total Guerriers :</span>
                <strong style="color:var(--text-main);"><?= number_format($totalSoldiers) ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
                <span style="color:var(--text-muted);">Puissance d'Attaque :</span>
                <strong style="color:var(--red-primary, #c2252b);"><?= number_format($totalAttackPower) ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
                <span style="color:var(--text-muted);">Défense des Troupes :</span>
                <strong style="color:#166534;"><?= number_format($totalDefensePower) ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; padding-top:0.3rem; border-top:1px dashed var(--border-color); align-items:center;">
                <a href="/?page=building&code=wall" style="text-decoration:none; color:inherit;" title="Accéder aux Remparts Féodaux">
                    <span style="color:var(--text-muted); font-size:0.75rem;">🧱 Remparts (Niv. <?= $wallLevel ?>) :</span>
                </a>
                <strong style="color:var(--red-primary, #c2252b); font-size:0.85rem;">
                    <?= ($wallLevel > 0) ? ('+' . ($wallLevel * $wallMultPct) . '% (' . number_format($totalFortifiedDefense) . ')') : '<a href="/?page=building&code=wall" style="font-size:0.75rem; color:#b45309; text-decoration:underline;">Non Bâti</a>' ?>
                </strong>
            </div>
        </div>

        <!-- File d'entraînement en cours -->
        <?php if (!empty($trainingQueue)): ?>
            <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid rgba(255,255,255,0.08);">
                <div style="font-size:0.75rem; color:#dc2626; font-weight:700; margin-bottom:0.35rem;">
                    ⏳ Entraînement au Dojo :
                </div>
                <?php foreach ($trainingQueue as $tq): ?>
                    <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.25rem;">
                        <span><?= $tq['unit_icon'] ?> <?= $tq['count'] ?>x <?= htmlspecialchars($tq['unit_name']) ?></span>
                        <span class="queue-timer" data-countdown="<?= $tq['finishes_at'] ?>" style="font-size:0.75rem;">Calcul...</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

