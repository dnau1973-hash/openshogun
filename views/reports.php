<?php
/**
 * Vue des Rapports de Combat et d'Espionnage
 */
$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT cr.*, u1.username as att_user, u2.username as def_user 
    FROM combat_reports cr 
    LEFT JOIN users u1 ON cr.attacker_id = u1.id 
    LEFT JOIN users u2 ON cr.defender_id = u2.id 
    WHERE cr.attacker_id = ? OR cr.defender_id = ? 
    ORDER BY cr.created_at DESC 
    LIMIT 30
");
$stmt->execute([$user['id'], $user['id']]);
$reports = $stmt->fetchAll();

$selectedReportId = isset($_GET['id']) ? (int)$_GET['id'] : ($reports[0]['id'] ?? 0);
$currentReport = null;
foreach ($reports as $r) {
    if ((int)$r['id'] === $selectedReportId) {
        $currentReport = $r;
        break;
    }
}

$repData = $currentReport ? json_decode($currentReport['report_data'], true) : null;
?>

<div class="grid-main">
    <!-- Liste des rapports -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📜 Chroniques de Siège & d'Infiltration</h2>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($reports)): ?>
                <p style="padding:2rem; text-align:center; color:var(--text-muted);">Aucun rapport de siège ou d'espionnage pour le moment.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column;">
                    <?php foreach ($reports as $rep): ?>
                        <?php 
                            $isWin = ($rep['winner'] === 'attacker' && $rep['attacker_id'] == $user['id']) || 
                                     ($rep['winner'] === 'defender' && $rep['defender_id'] == $user['id']);
                            $isSelected = ((int)$rep['id'] === $selectedReportId);
                        ?>
                        <a href="?page=reports&id=<?= $rep['id'] ?>" 
                           style="display:flex; justify-content:space-between; align-items:center; padding:0.85rem 1.25rem; border-bottom:1px solid rgba(255,255,255,0.05); background:<?= $isSelected ? 'rgba(220,38,38,0.1)' : 'transparent' ?>;">
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <span style="font-size:1.2rem;">
                                    <?= ($rep['mission_type'] === 'spy') ? '🥷' : ($isWin ? '🏆' : '💥') ?>
                                </span>
                                <div>
                                    <div style="font-weight:700; color:#fff; font-size:0.9rem;"><?= htmlspecialchars($rep['title']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= date('d/m/Y H:i', $rep['created_at']) ?></div>
                                </div>
                            </div>
                            <span style="font-size:0.75rem; padding:0.2rem 0.5rem; border-radius:4px; font-weight:700; background:<?= ($rep['mission_type'] === 'spy') ? '#0284c7' : ($isWin ? '#15803d' : '#b91c1c') ?>; color:#fff;">
                                <?= strtoupper($rep['mission_type']) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Détail du rapport sélectionné -->
    <div>
        <?php if ($currentReport && $repData): ?>
            <div class="card" style="border-color:#dc2626;">
                <div class="card-header">
                    <h3 class="card-title" style="font-size:1rem;">Détail de la Bataille</h3>
                </div>
                <div class="card-body">
                    <?php if ($currentReport['mission_type'] === 'spy'): ?>
                        <!-- Rapport d'espionnage -->
                        <h4 style="color:#dc2626; margin-bottom:0.5rem;">🥷 Infiltration Furtive Shinobi</h4>
                        <p style="font-size:0.85rem; margin-bottom:1rem;">Fief cible : <strong><?= htmlspecialchars($repData['planet_name']) ?> <?= $repData['coords'] ?></strong></p>
                        
                        <div style="background:rgba(0,0,0,0.3); padding:0.75rem; border-radius:6px; margin-bottom:1rem;">
                            <h5 style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.4rem;">Ressources détectées dans les réserves :</h5>
                            <div style="display:flex; justify-content:space-around; font-size:0.85rem;">
                                <span>🪵 <?= number_format($repData['resources']['metal'] ?? 0) ?></span>
                                <span>🪨 <?= number_format($repData['resources']['crystal'] ?? 0) ?></span>
                                <span>🌾 <?= number_format($repData['resources']['deuterium'] ?? 0) ?></span>
                            </div>
                        </div>

                        <?php if (!empty($repData['fleet'])): ?>
                            <div style="margin-bottom:1rem;">
                                <h5 style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.4rem;">Garnison du Fief :</h5>
                                <ul style="padding-left:1.2rem; font-size:0.85rem;">
                                    <?php foreach ($repData['fleet'] as $code => $cnt): ?>
                                        <li><?= htmlspecialchars($code) ?> : <strong><?= $cnt ?></strong> unités</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Rapport de Combat -->
                        <div style="text-align:center; margin-bottom:1rem; padding:0.5rem; background:rgba(0,0,0,0.3); border-radius:6px;">
                            <div style="font-size:1.1rem; font-weight:800; color:<?= ($currentReport['winner'] === 'attacker') ? '#dc2626' : '#ef4444' ?>;">
                                <?= ($currentReport['winner'] === 'attacker') ? 'VICTOIRE DE L\'ATTAQUANT' : 'VICTOIRE DU DÉFENSEUR' ?>
                            </div>
                            <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">
                                Daimyō <?= htmlspecialchars($repData['attacker_name']) ?> vs Daimyō <?= htmlspecialchars($repData['defender_name']) ?>
                            </div>
                        </div>

                        <!-- Butin pillé -->
                        <?php if (!empty($repData['looted'])): ?>
                            <div style="background:rgba(34,197,94,0.1); border:1px solid #22c55e; padding:0.75rem; border-radius:6px; margin-bottom:1rem;">
                                <h5 style="color:#4ade80; font-size:0.8rem; margin-bottom:0.3rem;">Butin capturé en raid :</h5>
                                <div style="display:flex; justify-content:space-around; font-size:0.85rem; font-weight:700;">
                                    <span>🪵 +<?= number_format($repData['looted']['metal']) ?></span>
                                    <span>🪨 +<?= number_format($repData['looted']['crystal']) ?></span>
                                    <span>🌾 +<?= number_format($repData['looted']['deuterium']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Pertes Attaquant -->
                        <div style="margin-bottom:0.75rem; font-size:0.85rem;">
                            <strong>Pertes de l'armée attaquante :</strong>
                            <?php if (empty($repData['attacker_lost'])): ?>
                                <span style="color:#4ade80;">Aucune perte !</span>
                            <?php else: ?>
                                <ul style="padding-left:1.2rem; color:#f87171;">
                                    <?php foreach ($repData['attacker_lost'] as $code => $cnt): ?>
                                        <li><?= htmlspecialchars($code) ?> : -<?= $cnt ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Pertes Défenseur -->
                        <div style="font-size:0.85rem;">
                            <strong>Pertes de la garnison du fief :</strong>
                            <?php if (empty($repData['defender_lost'])): ?>
                                <span style="color:#4ade80;">Aucune perte !</span>
                            <?php else: ?>
                                <ul style="padding-left:1.2rem; color:#f87171;">
                                    <?php foreach ($repData['defender_lost'] as $code => $cnt): ?>
                                        <li><?= htmlspecialchars($code) ?> : -<?= $cnt ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <!-- Action diplomatique directe -->
                        <?php 
                            $targetCommander = ($currentReport['attacker_id'] == $user['id']) ? ($repData['defender_name'] ?? '') : ($repData['attacker_name'] ?? '');
                        ?>
                        <?php if (!empty($targetCommander) && $targetCommander !== $user['username']): ?>
                            <div style="margin-top:1.25rem; padding-top:0.75rem; border-top:1px solid rgba(255,255,255,0.08); text-align:center;">
                                <a href="?page=messages&tab=compose&to=<?= urlencode($targetCommander) ?>" class="btn btn-secondary" style="font-size:0.8rem; padding:0.35rem 0.8rem;">
                                    ✉️ Envoyer une missive à <?= htmlspecialchars($targetCommander) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align:center; color:var(--text-muted); padding:2rem;">
                    Sélectionnez un rapport dans la liste de gauche pour en voir les détails.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

