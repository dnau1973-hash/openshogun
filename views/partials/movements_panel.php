<?php
/**
 * Panel des Mouvements & Expéditions en Cours (OpenShogun)
 * Widget mutualisé affiché dans la colonne Est de l'accordéon (Ressources, Cité, Carte)
 * Présente les marches militaires, les Mineurs des Cimes (⛏️), les Pêcheurs (🐟) et les aventures du Héros (🥷).
 */

require_once __DIR__ . '/../../core/FleetEngine.php';
require_once __DIR__ . '/../../core/HeroEngine.php';

$fleetEnginePanel = new FleetEngine();
$panelPlanetId = (int)($planet['id'] ?? 0);
$panelUserId   = (int)($user['id'] ?? 0);

try {
    $movementsSummary = $fleetEnginePanel->getMovementsSummary($panelUserId, $panelPlanetId);
} catch (Throwable $e) {
    $movementsSummary = [
        'hero' => null,
        'hero_missions' => [],
        'has_hero_moving' => false,
        'miner_missions' => [],
        'miner_missions_count' => 0,
        'fisher_missions' => [],
        'fisher_missions_count' => 0,
        'military_missions' => [],
        'military_missions_count' => 0,
        'all_missions' => [],
        'total_movements' => 0,
        'incoming_threats' => [],
        'incoming_threats_count' => 0
    ];
}

$hero = $movementsSummary['hero'];
$heroMissions = $movementsSummary['hero_missions'];
$hasHeroMoving = $movementsSummary['has_hero_moving'];
$minerMissions = $movementsSummary['miner_missions'];
$minerCount = $movementsSummary['miner_missions_count'];
$fisherMissions = $movementsSummary['fisher_missions'];
$fisherCount = $movementsSummary['fisher_missions_count'];
$militaryMissions = $movementsSummary['military_missions'];
$militaryCount = $movementsSummary['military_missions_count'];
$incomingThreats = $movementsSummary['incoming_threats'];
$threatsCount = $movementsSummary['incoming_threats_count'];
$totalMovements = $movementsSummary['total_movements'];
?>

<div class="movements-ongoing-panel font-sans">

    <!-- ── 1. ALERTE DE MENACE ENTRANTE (ATTAQUES ENNEMIES VERS CE FIEF) ── -->
    <?php if ($threatsCount > 0): ?>
        <div class="alert alert-danger p-2 mb-3 border-danger shadow-sm animate-pulse">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="avatar avatar-xs bg-danger text-white rounded-circle">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
                <strong class="text-danger small">Alerte Incursion Féodale !</strong>
                <span class="badge bg-danger ms-auto font-monospace" style="font-size:0.68rem;"><?= $threatsCount ?> attaque(s)</span>
            </div>
            <div class="d-flex flex-column gap-1.5 mt-1.5">
                <?php foreach ($incomingThreats as $thr): ?>
                    <div class="d-flex justify-content-between align-items-center bg-white p-1.5 rounded border border-danger-subtle font-monospace small" style="font-size:0.72rem;">
                        <span class="text-truncate me-1 text-danger fw-bold">
                            <i class="fa-solid fa-skull-crossbones me-1"></i><?= htmlspecialchars($thr['attacker_name']) ?> [<?= $thr['sx'] ?>:<?= $thr['sy'] ?>]
                        </span>
                        <span class="badge bg-danger text-white py-0.5 px-1 font-monospace" data-countdown="<?= (int)$thr['arrival_time'] ?>">
                            --:--:--
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── 2. CHAMPION SAMOURAÏ & HÉROS (🥷) ── -->
    <div class="p-2.5 rounded border mb-3 bg-light shadow-2xs">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-user-ninja text-warning me-1"></i>Champion Samouraï
            </span>
            <a href="/?page=hero" class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" style="font-size:0.7rem;">
                Sanctuaire &rarr;
            </a>
        </div>

        <?php if ($hero): 
            $heroHp = (float)($hero['health'] ?? 100);
            $heroStatus = $hero['status'] ?? 'home';
            $hpColor = ($heroHp > 60) ? 'bg-success' : (($heroHp > 25) ? 'bg-warning' : 'bg-danger');
            $dailyQuota = $hero['daily_adventures'] ?? ['count' => 0, 'max' => 3, 'remaining' => 3];
        ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="avatar avatar-sm rounded-circle bg-warning text-dark border border-warning shadow-2xs" style="width:34px; height:34px; font-size:1.1rem; flex-shrink:0;">
                    <i class="fa-solid fa-user-ninja"></i>
                </span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-dark small text-truncate leading-tight"><?= htmlspecialchars($hero['name']) ?></strong>
                        <span class="badge bg-warning text-dark font-monospace" style="font-size:0.65rem;">Niv. <?= (int)$hero['level'] ?></span>
                    </div>
                    <!-- Barre de Santé -->
                    <div class="d-flex align-items-center gap-1.5 mt-1">
                        <div class="progress flex-grow-1" style="height: 5px;">
                            <div class="progress-bar <?= $hpColor ?>" role="progressbar" style="width: <?= min(100, $heroHp) ?>%;" aria-valuenow="<?= $heroHp ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="text-muted font-monospace" style="font-size:0.68rem;"><?= round($heroHp) ?>% PV</span>
                    </div>
                </div>
            </div>

            <!-- État du Héros : En Aventure vs En Marche vs Au Domaine -->
            <?php if (!empty($heroMissions)): ?>
                <?php foreach ($heroMissions as $hm): 
                    $isAdv = ($hm['mission_type'] === 'adventure');
                ?>
                    <div class="border rounded p-1.5 bg-white shadow-2xs mb-1 font-monospace" style="font-size:0.72rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-purple-lt text-purple fw-bold">
                                <?= $hm['is_outbound'] ? '↗️ ' : '↙️ ' ?><?= $isAdv ? 'Aventure Provinciale' : 'Guerre & Escorte' ?>
                            </span>
                            <span class="badge bg-dark text-white" data-countdown="<?= (int)$hm['target_time'] ?>">
                                --:--:--
                            </span>
                        </div>
                        <div class="text-truncate fw-bold text-dark" style="font-size:0.74rem;">
                            <?= htmlspecialchars($hm['adventure_name'] ?: $hm['target_name']) ?> <?= $hm['target_coords'] ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($heroStatus === 'reviving'): ?>
                <div class="alert alert-danger py-1 px-2 mb-0 small" style="font-size:0.72rem;">
                    <i class="fa-solid fa-skull me-1"></i>En rituel de résurrection spirituelle.
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center bg-white p-1.5 rounded border small" style="font-size:0.72rem;">
                    <span class="text-success fw-bold">
                        <i class="fa-solid fa-shield-halved me-1"></i>Au domaine (Prêt)
                    </span>
                    <span class="text-muted font-monospace">
                        Quêtes : <strong><?= (int)$dailyQuota['count'] ?>/<?= (int)$dailyQuota['max'] ?></strong>
                    </span>
                </div>
                <?php if ((int)$dailyQuota['remaining'] > 0): ?>
                    <div class="mt-1.5 text-center">
                        <a href="/?page=hero" class="btn btn-sm btn-outline-warning w-100 py-0.5 font-monospace text-dark" style="font-size:0.72rem;">
                            <i class="fa-solid fa-compass me-1 text-warning"></i>Partir en Aventure (<?= $dailyQuota['remaining'] ?> disp.)
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center text-muted small py-2">
                Aucun Héros assigné à ce Daimyō.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 3. MINEURS DES CIMES (⛏️ KŌFU) ── -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-pickaxe text-warning me-1"></i>Mineurs des Cimes (<?= $minerCount ?>)
            </span>
            <a href="/?page=fleet&mission=mining" class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" style="font-size:0.7rem;">
                <i class="fa-solid fa-plus me-0.5"></i>Forer
            </a>
        </div>

        <?php if (empty($minerMissions)): ?>
            <div class="border rounded p-2 bg-white text-center text-muted small" style="font-size:0.76rem;">
                <span class="d-block mb-1 opacity-60">⛏️</span>
                <span>Aucun Mineur des Cimes en haute montagne.</span>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($minerMissions as $mm): ?>
                    <div class="border rounded p-2 bg-white shadow-2xs font-monospace small" style="font-size:0.72rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-warning-lt text-warning fw-bold">
                                <?= $mm['is_outbound'] ? '↗️ Ascension' : '↙️ Descente filon' ?>
                            </span>
                            <span class="badge bg-dark text-white" data-countdown="<?= (int)$mm['target_time'] ?>">
                                --:--:--
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-dark">
                            <span class="fw-bold text-truncate" title="<?= htmlspecialchars($mm['target_name']) ?>">
                                ⛏️ <?= htmlspecialchars($mm['target_name']) ?> <?= $mm['target_coords'] ?>
                            </span>
                            <span class="badge bg-secondary-lt text-secondary ms-1">
                                <?= (int)($mm['miner_count'] ?? 1) ?> Kōfu
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 4. PÊCHEURS FÉODAUX (🐟 RYŌSHI) ── -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-fish text-cyan me-1"></i>Pêcheurs Féodaux (<?= $fisherCount ?>)
            </span>
            <a href="/?page=fleet&mission=fishing" class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" style="font-size:0.7rem;">
                <i class="fa-solid fa-plus me-0.5"></i>Pêcher
            </a>
        </div>

        <?php if (empty($fisherMissions)): ?>
            <div class="border rounded p-2 bg-white text-center text-muted small" style="font-size:0.76rem;">
                <span class="d-block mb-1 opacity-60">🐟</span>
                <span>Aucune équipe de pêche sur les lacs d'eau douce.</span>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($fisherMissions as $fm): ?>
                    <div class="border rounded p-2 bg-white shadow-2xs font-monospace small" style="font-size:0.72rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-cyan-lt text-cyan fw-bold">
                                <?= $fm['is_outbound'] ? '↗️ Pose des filets' : '↙️ Retour cargaison' ?>
                            </span>
                            <span class="badge bg-dark text-white" data-countdown="<?= (int)$fm['target_time'] ?>">
                                --:--:--
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-dark">
                            <span class="fw-bold text-truncate" title="<?= htmlspecialchars($fm['target_name']) ?>">
                                🐟 <?= htmlspecialchars($fm['target_name']) ?> <?= $fm['target_coords'] ?>
                            </span>
                            <span class="badge bg-secondary-lt text-secondary ms-1">
                                <?= (int)($fm['fisher_count'] ?? 1) ?> Ryōshi
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 5. TROUPES & LÉGIONS MARTIALES EN MARCHE (⚔️) ── -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-uppercase fw-bold text-secondary font-monospace" style="font-size:0.75rem; letter-spacing:0.5px;">
                <i class="fa-solid fa-horse text-danger me-1"></i>Marches Guerrières (<?= $militaryCount ?>)
            </span>
            <a href="/?page=fleet" class="btn btn-sm btn-ghost-primary py-0 px-1 font-monospace" style="font-size:0.7rem;">
                <i class="fa-solid fa-plus me-0.5"></i>Déployer
            </a>
        </div>

        <?php if (empty($militaryMissions)): ?>
            <div class="border rounded p-2 bg-white text-center text-muted small" style="font-size:0.76rem;">
                <i class="fa-solid fa-shield-halved text-muted opacity-50 mb-1 fs-3 d-block"></i>
                <span>Toutes vos légions sont au repos en garnison.</span>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($militaryMissions as $mil): 
                    $mType = $mil['mission_type'];
                    $missionBadge = match ($mType) {
                        'raid' => ['class' => 'bg-danger text-white', 'icon' => 'fa-solid fa-khanda', 'label' => 'Raid'],
                        'attack' => ['class' => 'bg-danger text-white', 'icon' => 'fa-solid fa-bomb', 'label' => 'Siège'],
                        'occupy' => ['class' => 'bg-primary text-white', 'icon' => 'fa-solid fa-flag', 'label' => 'Occuper'],
                        'spy' => ['class' => 'bg-secondary text-white', 'icon' => 'fa-solid fa-user-ninja', 'label' => 'Shinobi'],
                        'colonize' => ['class' => 'bg-teal text-white', 'icon' => 'fa-solid fa-chess-rook', 'label' => 'Expansion'],
                        'dojo_siege' => ['class' => 'bg-danger text-white', 'icon' => 'fa-solid fa-bomb', 'label' => 'Dōjō Siège'],
                        'dojo_reinforce' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-solid fa-torii-gate', 'label' => 'Dōjō Garnison'],
                        default => ['class' => 'bg-secondary text-white', 'icon' => 'fa-solid fa-horse', 'label' => strtoupper($mType)]
                    };
                ?>
                    <div class="border rounded p-2 bg-white shadow-2xs font-monospace small" style="font-size:0.72rem;">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                            <span class="badge <?= $missionBadge['class'] ?> py-0.5 px-1.5" style="font-size:0.65rem;">
                                <?= $mil['is_outbound'] ? '↗️ ' : '↙️ ' ?><i class="<?= $missionBadge['icon'] ?> me-1"></i><?= $missionBadge['label'] ?>
                            </span>
                            <span class="badge bg-dark text-white py-0.5 px-1.5" data-countdown="<?= (int)$mil['target_time'] ?>" style="font-size:0.68rem;">
                                --:--:--
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-dark mt-1">
                            <span class="fw-bold text-truncate" title="<?= htmlspecialchars($mil['target_name']) ?>">
                                &rarr; <?= htmlspecialchars($mil['target_name']) ?> <?= $mil['target_coords'] ?>
                            </span>
                            <span class="badge bg-secondary-lt text-secondary ms-1">
                                <?= (int)$mil['total_units'] ?> unité(s)
                            </span>
                        </div>
                        <?php if ($mil['has_hero']): ?>
                            <div class="mt-1 text-warning small" style="font-size:0.68rem;">
                                <i class="fa-solid fa-user-ninja me-1"></i>Accompagné par le Champion Samouraï
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── 6. PIED D'ACTIONS STRATÉGIQUES ── -->
    <div class="d-grid gap-1.5 pt-1 border-top">
        <a href="/?page=fleet" class="btn btn-sm btn-danger text-white fw-bold shadow-2xs d-flex align-items-center justify-content-center gap-1.5">
            <i class="fa-solid fa-horse"></i>
            <span>Expéditions Militaires</span>
        </a>
        <div class="d-flex gap-1.5">
            <a href="/?page=fleet#tab-farm-lists" class="btn btn-sm btn-outline-warning w-50 d-flex align-items-center justify-content-center gap-1 text-dark" style="font-size:0.72rem;">
                <i class="fa-solid fa-scroll text-warning"></i>
                <span>Carnet Raids</span>
            </a>
            <a href="/?page=hero" class="btn btn-sm btn-outline-secondary w-50 d-flex align-items-center justify-content-center gap-1" style="font-size:0.72rem;">
                <i class="fa-solid fa-user-ninja text-warning"></i>
                <span>Héros (Quêtes)</span>
            </a>
        </div>
    </div>

</div>
