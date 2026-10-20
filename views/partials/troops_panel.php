<?php
/**
 * Panel de Garnison du Domaine Féodal (Infanterie & Armes de Siège)
 * Widget mutualisé affiché dans la colonne Est (Ressources / Terroir)
 */
require_once __DIR__ . '/../../core/BarracksEngine.php';
require_once __DIR__ . '/../../core/ShipyardEngine.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/AiPromptHelper.php';

$userFaction = $user['faction'] ?? 'terran';
$planetId = (int)($planet['id'] ?? 0);

$barracksEngineWidget = new BarracksEngine();
$shipyardEngineWidget = new ShipyardEngine();
$planetEngineWidget   = new PlanetEngine();

// 1. Troupes du Dojo (Infanterie, Archers, Spécialistes)
$stationedTroops = $barracksEngineWidget->getStationedUnits($planetId, $userFaction);
$barracksQueue   = $barracksEngineWidget->getQueue($planetId);

// 2. Vaisseaux & Armes de Siège
$availableShips = $shipyardEngineWidget->getAvailableShips($planetId, $userFaction);
$shipyardQueue  = $shipyardEngineWidget->getQueue($planetId);

// 3. Bâtiments (Remparts, Caserne, Atelier de Siège)
$panelBuildings   = $planetEngineWidget->getBuildings($planetId);
$wallLevel        = (int)($panelBuildings['wall'] ?? 0);
$barracksLevel    = (int)($panelBuildings['barracks'] ?? 0);
$shipyardLevel    = (int)($panelBuildings['shipyard'] ?? 0);
$wallMultPct      = ($userFaction === 'aethelis') ? 5 : (($userFaction === 'vorash') ? 3.5 : 4);
$wallBonusFactor  = 1.0 + ($wallLevel * ($wallMultPct / 100));

// 4. Métadonnées exhaustives des Armes de Siège Féodales
$siegeMeta = [
    'terran_cruiser'     => [
        'name'        => "Bélier Blindé à Éperon d'Acier",
        'short_name'  => "Bélier d'Acier",
        'faction'     => 'terran',
        'clan'        => 'Clan Oda',
        'icon'        => 'fa-solid fa-hammer',
        'badge_color' => '#3b82f6',
        'base_dmg'    => 150,
        'wall_mult'   => 3.0,
        'image'       => 'belier_acier_oda.jpg',
        'role'        => 'Briseur de portes'
    ],
    'terran_dreadnought' => [
        'name'        => "Grande Tour de Siège & Baliste",
        'short_name'  => "Tour & Baliste",
        'faction'     => 'terran',
        'clan'        => 'Clan Oda',
        'icon'        => 'fa-solid fa-chess-rook',
        'badge_color' => '#3b82f6',
        'base_dmg'    => 450,
        'wall_mult'   => 4.0,
        'image'       => 'tour_siege_baliste_oda.jpg',
        'role'        => 'Pilonnage lourd'
    ],
    'vorash_leviathan'   => [
        'name'        => "Bélier Titanesque du Dragon de Kai",
        'short_name'  => "Bélier Titanesque",
        'faction'     => 'vorash',
        'clan'        => 'Clan Takeda',
        'icon'        => 'fa-solid fa-dragon',
        'badge_color' => '#ef4444',
        'base_dmg'    => 400,
        'wall_mult'   => 3.5,
        'image'       => 'belier_dragon_kai.jpg',
        'role'        => 'Écraseur de remparts'
    ],
    'aethelis_prism'     => [
        'name'        => "Catapulte Flamboyante Horokubiya",
        'short_name'  => "Catapulte Horokubiya",
        'faction'     => 'aethelis',
        'clan'        => 'Clan Tokugawa',
        'icon'        => 'fa-solid fa-bomb',
        'badge_color' => '#8b5cf6',
        'base_dmg'    => 200,
        'wall_mult'   => 3.0,
        'image'       => 'catapulte_horokubiya_tokugawa.jpg',
        'role'        => 'Artillerie incendiaire'
    ],
    'aethelis_titan'     => [
        'name'        => "Forteresse Roulante Blindée",
        'short_name'  => "Forteresse Roulante",
        'faction'     => 'aethelis',
        'clan'        => 'Clan Tokugawa',
        'icon'        => 'fa-solid fa-shield-cat',
        'badge_color' => '#8b5cf6',
        'base_dmg'    => 600,
        'wall_mult'   => 4.0,
        'image'       => 'forteresse_roulante_tokugawa.jpg',
        'role'        => 'Bastion mobile'
    ]
];

// Identification des armes de siège de la faction et celles détenues
$stationedSiege = [];
$otherShips     = [];
$siegeKeys      = array_keys($siegeMeta);

foreach ($availableShips as $s) {
    $code = $s['code'];
    $cnt  = (int)($s['stationed_count'] ?? 0);
    $isSiege = in_array($code, $siegeKeys, true)
               || (stripos($s['name'], 'siège') !== false)
               || (stripos($s['name'], 'catapulte') !== false)
               || (stripos($s['name'], 'bélier') !== false)
               || (stripos($s['name'], 'tour') !== false);

    if ($isSiege) {
        $meta = $siegeMeta[$code] ?? [
            'name'        => $s['name'],
            'short_name'  => $s['name'],
            'faction'     => $s['faction'],
            'clan'        => 'Génie de Siège',
            'icon'        => 'fa-solid fa-bomb',
            'badge_color' => '#dc2626',
            'base_dmg'    => 200,
            'wall_mult'   => 3.0,
            'image'       => !empty($s['image']) ? $s['image'] : 'catapulte_horokubiya_tokugawa.jpg',
            'role'        => 'Engin de siège'
        ];
        $s['meta'] = $meta;
        $stationedSiege[$code] = $s;
    } elseif ($cnt > 0) {
        $otherShips[$code] = $s;
    }
}

// 5. Calculs des Statistiques Consolidées
$totalSoldiers        = 0;
$totalSiegeEngines    = 0;
$totalOtherEngines    = 0;
$totalAttackPower     = 0;
$totalDefensePower    = 0;
$totalSiegeBasePower  = 0;
$totalSiegeWallPower  = 0;

// Stats Infanterie
foreach ($stationedTroops as $t) {
    $cnt = (int)$t['stationed_count'];
    $totalSoldiers += $cnt;
    $totalAttackPower += $cnt * (int)($t['attack'] ?? 0);
    $totalDefensePower += $cnt * ((int)($t['def_infantry'] ?? 0) + (int)($t['def_mech'] ?? 0));
}

// Stats Armes de Siège
foreach ($stationedSiege as $s) {
    $cnt = (int)($s['stationed_count'] ?? 0);
    $meta = $s['meta'];
    $totalSiegeEngines += $cnt;
    $totalAttackPower += $cnt * (int)($s['attack'] ?? 0);
    $totalDefensePower += $cnt * ((int)($s['defense'] ?? 0) + (int)($s['shield'] ?? 0));
    $totalSiegeBasePower += $cnt * (int)$meta['base_dmg'];
    $totalSiegeWallPower += (int)round($cnt * (int)$meta['base_dmg'] * (float)$meta['wall_mult']);
}

// Stats Autres Flottes / Cavalerie
foreach ($otherShips as $os) {
    $cnt = (int)($os['stationed_count'] ?? 0);
    $totalOtherEngines += $cnt;
    $totalAttackPower += $cnt * (int)($os['attack'] ?? 0);
    $totalDefensePower += $cnt * ((int)($os['defense'] ?? 0) + (int)($os['shield'] ?? 0));
}

$totalMilitaryUnits   = $totalSoldiers + $totalSiegeEngines + $totalOtherEngines;
$totalFortifiedDefense = (int)($totalDefensePower * $wallBonusFactor) + ($wallLevel * 25);
?>

<div class="garnison-container p-1">

    <!-- ── BARRE D'ACTIONS RAPIDES & FILTRES D'AFFICHAGE ── -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-2">
        <!-- Filtres par pilules -->
        <div class="btn-group btn-group-sm" role="group" aria-label="Filtres Garnison">
            <button type="button" class="btn btn-outline-secondary py-0 px-2 active garnison-filter-btn" data-target="all" onclick="filterGarnison('all', this)" style="font-size:0.72rem;">
                <i class="fa-solid fa-layer-group me-1"></i>Tous (<?= $totalMilitaryUnits ?>)
            </button>
            <button type="button" class="btn btn-outline-danger py-0 px-2 garnison-filter-btn" data-target="siege" onclick="filterGarnison('siege', this)" style="font-size:0.72rem;">
                <i class="fa-solid fa-bomb me-1"></i>Siège (<?= $totalSiegeEngines ?>)
            </button>
            <button type="button" class="btn btn-outline-primary py-0 px-2 garnison-filter-btn" data-target="infantry" onclick="filterGarnison('infantry', this)" style="font-size:0.72rem;">
                <i class="fa-solid fa-person-rifle me-1"></i>Dojo (<?= $totalSoldiers ?>)
            </button>
        </div>

        <!-- Raccourcis Cité Castrale -->
        <div class="d-flex align-items-center gap-1 ms-auto">
            <a href="?page=shipyard" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.7rem;" title="Atelier de Siège & Écuries (Niv. <?= $shipyardLevel ?>)">
                <i class="fa-solid fa-hammer me-1"></i>Atelier &rarr;
            </a>
            <a href="?page=barracks" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.7rem;" title="Dojo Militaire (Niv. <?= $barracksLevel ?>)">
                <i class="fa-solid fa-torii-gate me-1"></i>Dojo &rarr;
            </a>
        </div>
    </div>

    <!-- ====================================================
         1. SECTION ARMES DE SIÈGE DU DOMAINE (NOUVELLE SECTION PHARE)
         ==================================================== -->
    <div class="garnison-section mb-3" id="garnison-sec-siege">
        <div class="d-flex align-items-center justify-content-between p-1.5 px-2 rounded mb-2" style="background: linear-gradient(90deg, rgba(220,38,38,0.12), rgba(245,158,11,0.06)); border-left: 3px solid #dc2626;">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs rounded bg-danger text-white shadow-xs" style="width:24px; height:24px; font-size:0.75rem;">
                    <i class="fa-solid fa-bomb"></i>
                </span>
                <div>
                    <strong class="d-block text-dark" style="font-size: 0.82rem;">Armes de Siège Féodales</strong>
                    <small class="text-secondary" style="font-size: 0.68rem;">Catapultes, béliers &amp; tours de démolition</small>
                </div>
            </div>
            <div class="text-end">
                <span class="badge <?= ($totalSiegeEngines > 0) ? 'bg-danger text-white' : 'bg-secondary-lt' ?> font-monospace fw-bold" style="font-size: 0.72rem;">
                    <?= number_format($totalSiegeEngines) ?> engin(s)
                </span>
            </div>
        </div>

        <div class="d-flex flex-column gap-1.5">
            <?php foreach ($stationedSiege as $s): ?>
                <?php
                    $count    = (int)($s['stationed_count'] ?? 0);
                    $hasUnits = ($count > 0);
                    $meta     = $s['meta'];
                    $imgFile  = !empty($s['image']) ? $s['image'] : $meta['image'];
                    $diskFile = __DIR__ . '/../../public/assets/units/' . $imgFile;
                    if (file_exists($diskFile)) {
                        $imgSrc = '/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile);
                    } else {
                        $imgSrc = '/public/assets/tiles/tile_shipyard.png';
                    }
                ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-surface transition-all <?= !$hasUnits ? 'opacity-60 bg-light-lt' : 'border-danger-subtle shadow-xs' ?>" style="min-height:46px;">
                    <!-- Miniature et descriptif -->
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <div class="position-relative flex-shrink-0" style="width:34px; height:34px; border-radius:6px; overflow:hidden; border:1px solid <?= $hasUnits ? '#dc2626' : '#cbd5e1' ?>;">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php if ($hasUnits): ?>
                                <span class="position-absolute bottom-0 end-0 bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width:12px; height:12px; font-size:8px;">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-truncate">
                            <div class="d-flex align-items-center gap-1.5">
                                <span class="fw-bold text-dark text-truncate" style="font-size:0.8rem;" title="<?= htmlspecialchars($s['name']) ?>">
                                    <?= htmlspecialchars($meta['short_name'] ?? $s['name']) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-secondary" style="font-size:0.67rem;">
                                <span title="Dégâts de brèche aux remparts"><i class="fa-solid fa-burst text-danger me-0.5"></i>Siège: <strong class="text-danger"><?= $meta['base_dmg'] ?></strong></span>
                                <span title="Multiplicateur d'impact sur muraille"><i class="fa-solid fa-shield-halved text-warning me-0.5"></i>x<?= $meta['wall_mult'] ?></span>
                                <span class="d-none d-sm-inline text-muted">&bull; <?= htmlspecialchars($meta['role']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Compteur effectif & lien si 0 -->
                    <div class="text-end flex-shrink-0">
                        <?php if ($hasUnits): ?>
                            <span class="badge bg-danger text-white font-monospace px-2 py-1 shadow-xs" style="font-size:0.82rem;">
                                <i class="fa-solid fa-bomb me-1"></i><?= number_format($count) ?>
                            </span>
                        <?php else: ?>
                            <a href="?page=shipyard" class="btn btn-outline-secondary py-0 px-1 text-muted" style="font-size:0.68rem; border-style:dashed;" title="Bâtir ou mobiliser cet engin">
                                0 <i class="fa-solid fa-plus ms-0.5 text-danger"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalSiegeEngines === 0): ?>
            <div class="p-2 mt-1 rounded bg-light text-center small text-muted border border-dashed" style="font-size:0.72rem;">
                <i class="fa-solid fa-circle-info text-info me-1"></i>Aucune arme de siège en garnison.
                <a href="?page=shipyard" class="text-danger fw-bold text-decoration-underline ms-1">Construire à l'Atelier</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- ====================================================
         2. SECTION TROUPES DU DOJO (INFANTERIE & GUERRIERS)
         ==================================================== -->
    <div class="garnison-section mb-3" id="garnison-sec-infantry">
        <div class="d-flex align-items-center justify-content-between p-1.5 px-2 rounded mb-2" style="background: linear-gradient(90deg, rgba(37,99,235,0.08), rgba(99,102,241,0.04)); border-left: 3px solid #2563eb;">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs rounded bg-primary text-white shadow-xs" style="width:24px; height:24px; font-size:0.75rem;">
                    <i class="fa-solid fa-khanda"></i>
                </span>
                <div>
                    <strong class="d-block text-dark" style="font-size: 0.82rem;">Guerriers du Dojo</strong>
                    <small class="text-secondary" style="font-size: 0.68rem;">Ashigaru, archers &amp; samouraïs</small>
                </div>
            </div>
            <div class="text-end">
                <span class="badge <?= ($totalSoldiers > 0) ? 'bg-primary text-white' : 'bg-secondary-lt' ?> font-monospace fw-bold" style="font-size: 0.72rem;">
                    <?= number_format($totalSoldiers) ?> soldat(s)
                </span>
            </div>
        </div>

        <div class="d-flex flex-column gap-1.5">
            <?php foreach ($stationedTroops as $t): ?>
                <?php
                    $count    = (int)($t['stationed_count'] ?? 0);
                    $hasUnits = ($count > 0);
                    $imgFile  = !empty($t['image']) ? $t['image'] : ($t['code'] . '.jpg');
                    $diskFile = __DIR__ . '/../../public/assets/units/' . $imgFile;
                    if (file_exists($diskFile)) {
                        $imgSrc = '/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile);
                    } else {
                        $imgSrc = '/public/assets/units/' . $t['code'] . '.svg';
                    }
                ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-surface transition-all <?= !$hasUnits ? 'opacity-50 bg-light-lt' : 'shadow-xs' ?>" style="min-height:44px;">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <div class="position-relative flex-shrink-0" style="width:32px; height:32px; border-radius:6px; overflow:hidden; border:1px solid <?= $hasUnits ? '#3b82f6' : '#cbd5e1' ?>;">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($t['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div class="text-truncate">
                            <span class="fw-semibold text-dark text-truncate d-block" style="font-size:0.78rem;" title="<?= htmlspecialchars($t['name']) ?>">
                                <?= htmlspecialchars($t['name']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-2 text-secondary" style="font-size:0.67rem;">
                                <span><i class="fa-solid fa-khanda text-danger me-0.5"></i><?= $t['attack'] ?></span>
                                <span><i class="fa-solid fa-shield-halved text-success me-0.5"></i><?= (int)$t['def_infantry'] + (int)$t['def_mech'] ?></span>
                                <span class="d-none d-sm-inline text-muted">&bull; Tier <?= $t['tier'] ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <?php if ($hasUnits): ?>
                            <span class="badge bg-primary text-white font-monospace px-2 py-1 shadow-xs" style="font-size:0.8rem;">
                                <?= number_format($count) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt font-monospace text-muted" style="font-size:0.75rem;">0</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ====================================================
         3. SECTION CAVALERIE & CONVOIS (SI PRÉSENTS)
         ==================================================== -->
    <?php if (!empty($otherShips)): ?>
        <div class="garnison-section mb-3" id="garnison-sec-other">
            <div class="d-flex align-items-center justify-content-between p-1.5 px-2 rounded mb-2 bg-light border-start border-3 border-success">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded bg-success-lt text-success" style="width:24px; height:24px; font-size:0.75rem;">
                        <i class="fa-solid fa-horse"></i>
                    </span>
                    <strong class="text-dark" style="font-size: 0.8rem;">Cavalerie &amp; Convois</strong>
                </div>
                <span class="badge bg-success-lt font-monospace fw-bold" style="font-size: 0.72rem;"><?= number_format($totalOtherEngines) ?></span>
            </div>
            <div class="d-flex flex-column gap-1">
                <?php foreach ($otherShips as $os): ?>
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded border bg-surface" style="font-size:0.75rem;">
                        <span class="fw-semibold text-truncate"><?= htmlspecialchars($os['name']) ?></span>
                        <strong class="font-monospace text-success"><?= number_format($os['stationed_count']) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ====================================================
         4. BILAN STRATÉGIQUE & PUISSANCE DE GARNISON
         ==================================================== -->
    <div class="card border shadow-none bg-surface mb-3">
        <div class="card-body p-2.5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-1.5">
                    <i class="fa-solid fa-chart-pie text-danger"></i>
                    <strong style="font-size:0.8rem;">Bilan Martiale &amp; Tactique</strong>
                </div>
                <span class="badge bg-secondary-lt font-monospace fw-bold" style="font-size:0.68rem;">
                    <?= number_format($totalMilitaryUnits) ?> unités totales
                </span>
            </div>

            <!-- Grille 2x2 Métriques Clés -->
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <div class="p-2 rounded border bg-light-lt">
                        <div class="text-secondary small d-flex align-items-center justify-content-between" style="font-size:0.68rem;">
                            <span><i class="fa-solid fa-khanda text-danger me-1"></i>Attaque Totale</span>
                        </div>
                        <div class="font-monospace fw-bold text-danger fs-4 mt-0.5">
                            <?= number_format($totalAttackPower) ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded border bg-light-lt">
                        <div class="text-secondary small d-flex align-items-center justify-content-between" style="font-size:0.68rem;">
                            <span><i class="fa-solid fa-bomb text-warning me-1"></i>Impact Siège</span>
                        </div>
                        <div class="font-monospace fw-bold text-warning fs-4 mt-0.5" title="Dégâts directs sur remparts : <?= number_format($totalSiegeWallPower) ?>">
                            <?= number_format($totalSiegeBasePower) ?> <small class="text-secondary" style="font-size:0.6rem;">(<?= number_format($totalSiegeWallPower) ?> mur)</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded border bg-light-lt">
                        <div class="text-secondary small d-flex align-items-center justify-content-between" style="font-size:0.68rem;">
                            <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Défense Troupes</span>
                        </div>
                        <div class="font-monospace fw-bold text-success fs-4 mt-0.5">
                            <?= number_format($totalDefensePower) ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded border bg-light-lt">
                        <div class="text-secondary small d-flex align-items-center justify-content-between" style="font-size:0.68rem;">
                            <span><i class="fa-solid fa-gopuram text-primary me-1"></i>Remparts Fortifiés</span>
                        </div>
                        <div class="font-monospace fw-bold text-primary fs-4 mt-0.5">
                            <?= number_format($totalFortifiedDefense) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détail Remparts & Bonus Castral -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-dashed small" style="font-size:0.73rem;">
                <a href="/?page=building&code=wall" class="text-decoration-none text-muted" title="Améliorer les Remparts Féodaux">
                    <i class="fa-solid fa-shield-cat text-secondary me-1"></i>Remparts (Niv. <?= $wallLevel ?>) :
                </a>
                <strong class="text-primary font-monospace">
                    <?php if ($wallLevel > 0): ?>
                        +<?= ($wallLevel * $wallMultPct) ?>% défense
                    <?php else: ?>
                        <a href="/?page=building&code=wall" class="text-warning text-decoration-underline">Non Bâti (0%)</a>
                    <?php endif; ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- ====================================================
         5. FILES D'ATTENTE ACTIVES (DOJO & ATELIER DE SIÈGE)
         ==================================================== -->
    <?php $hasQueue = (!empty($barracksQueue) || !empty($shipyardQueue)); ?>
    <?php if ($hasQueue): ?>
        <div class="card border shadow-none bg-surface">
            <div class="card-body p-2.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-1.5 text-danger">
                        <i class="fa-solid fa-hourglass-half"></i>
                        <strong style="font-size:0.8rem;">Mobilisation Active (au fil de l'eau)</strong>
                    </div>
                    <span class="badge bg-danger-lt" style="font-size:0.65rem;">
                        <?= count($barracksQueue) + count($shipyardQueue) ?> lot(s)
                    </span>
                </div>

                <!-- 5.a File de l'Atelier de Siège -->
                <?php if (!empty($shipyardQueue)): ?>
                    <div class="mb-2">
                        <div class="small fw-bold text-danger mb-1" style="font-size:0.7rem;">
                            <i class="fa-solid fa-hammer me-1"></i>Atelier de Siège :
                        </div>
                        <?php foreach ($shipyardQueue as $sq): ?>
                            <div class="p-2 mb-1.5 rounded border border-danger-subtle bg-light-subtle" style="font-size:0.72rem;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-truncate"><i class="fa-solid fa-bomb text-danger me-1"></i><?= htmlspecialchars($sq['ship_name']) ?></span>
                                    <span class="badge bg-danger text-white rounded-pill font-monospace" style="font-size:0.65rem; padding:1px 6px;">
                                        <?= $sq['completed_count'] ?> / <?= $sq['total_count'] ?>
                                    </span>
                                </div>
                                <div class="progress mb-1" style="height:5px; background:rgba(0,0,0,0.06);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: <?= $sq['unit_pct'] ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-secondary" style="font-size:0.65rem;">
                                    <span>Unité <?= $sq['current_unit_number'] ?>/<?= $sq['total_count'] ?></span>
                                    <span class="font-monospace text-danger fw-bold"><?= $sq['unit_pct'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- 5.b File du Dojo Militaire -->
                <?php if (!empty($barracksQueue)): ?>
                    <div>
                        <div class="small fw-bold text-primary mb-1" style="font-size:0.7rem;">
                            <i class="fa-solid fa-torii-gate me-1"></i>Dojo Militaire :
                        </div>
                        <?php foreach ($barracksQueue as $tq): ?>
                            <div class="p-2 mb-1.5 rounded border bg-light-subtle" style="font-size:0.72rem;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-truncate"><?= $tq['unit_icon'] ?> <?= htmlspecialchars($tq['unit_name']) ?></span>
                                    <span class="badge bg-primary text-white rounded-pill font-monospace" style="font-size:0.65rem; padding:1px 6px;">
                                        <?= $tq['completed_count'] ?> / <?= $tq['total_count'] ?>
                                    </span>
                                </div>
                                <div class="progress mb-1" style="height:5px; background:rgba(0,0,0,0.06);">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: <?= $tq['unit_pct'] ?>%;"></div>
                                </div>
                                <div class="d-flex justify-content-between text-secondary" style="font-size:0.65rem;">
                                    <span>Guerrier <?= $tq['current_unit_number'] ?>/<?= $tq['total_count'] ?></span>
                                    <span class="font-monospace text-primary fw-bold"><?= $tq['unit_pct'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Script interactif léger pour filtrer les sections sans conflit avec l'accordéon -->
<script>
function filterGarnison(category, btn) {
    if (!btn) return;
    document.querySelectorAll('.garnison-filter-btn').forEach(function(b) {
        b.classList.remove('active', 'btn-danger', 'btn-primary', 'btn-secondary');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active');
    if (category === 'siege') {
        btn.classList.add('btn-danger');
        btn.classList.remove('btn-outline-danger', 'btn-outline-secondary');
    } else if (category === 'infantry') {
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
    } else {
        btn.classList.add('btn-secondary');
        btn.classList.remove('btn-outline-secondary');
    }

    const secSiege = document.getElementById('garnison-sec-siege');
    const secInfantry = document.getElementById('garnison-sec-infantry');
    const secOther = document.getElementById('garnison-sec-other');

    if (category === 'siege') {
        if (secSiege) secSiege.classList.remove('d-none');
        if (secInfantry) secInfantry.classList.add('d-none');
        if (secOther) secOther.classList.add('d-none');
    } else if (category === 'infantry') {
        if (secSiege) secSiege.classList.add('d-none');
        if (secInfantry) secInfantry.classList.remove('d-none');
        if (secOther) secOther.classList.add('d-none');
    } else {
        if (secSiege) secSiege.classList.remove('d-none');
        if (secInfantry) secInfantry.classList.remove('d-none');
        if (secOther) secOther.classList.remove('d-none');
    }
}
</script>
