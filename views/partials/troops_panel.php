<?php
/**
 * Panel de Garnison du Domaine Féodal (Infanterie & Armes de Siège)
 * Widget mutualisé affiché dans la colonne Est (Ressources / Terroir)
 * Permet l'ouverture d'une modale HD illustrée au clic sur un soldat ou un engin de siège.
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
    } else {
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

<style>
.garnison-unit-row {
    cursor: pointer;
    transition: transform 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.garnison-unit-row:hover {
    transform: translateX(3px);
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
}
.garnison-unit-row:hover .garnison-thumb img {
    transform: scale(1.08);
}
.garnison-thumb img {
    transition: transform 0.25s ease;
}
</style>

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
            <button type="button" class="btn btn-outline-success py-0 px-2 garnison-filter-btn" data-target="other" onclick="filterGarnison('other', this)" style="font-size:0.72rem;">
                <i class="fa-solid fa-horse me-1"></i>Cavalerie &amp; Logistique (<?= $totalOtherEngines ?>)
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
                    <small class="text-secondary" style="font-size: 0.68rem;">Catapultes, béliers &amp; tours de démolition (Cliquer pour voir détails)</small>
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

                    // Données JSON pour l'ouverture de la modale illustrée
                    $siegeModalData = [
                        'name'            => $s['name'],
                        'short_name'      => $meta['short_name'],
                        'role_badge'      => $meta['clan'] . ' • ' . $meta['role'],
                        'badge_class'     => 'bg-danger',
                        'image_url'       => $imgSrc,
                        'description'     => !empty($s['description']) ? $s['description'] : ($meta['desc'] ?? ''),
                        'lore_title'      => 'Génie de Siège & Artillerie Sengoku (' . $meta['clan'] . ') :',
                        'stationed_count' => $count,
                        'is_siege'        => true,
                        'attack'          => (int)($s['attack'] ?? 0),
                        'defense'         => (int)($s['defense'] ?? 0) + (int)($s['shield'] ?? 0),
                        'siege_base'      => (int)$meta['base_dmg'],
                        'wall_mult'       => (float)$meta['wall_mult'],
                        'speed'           => (int)($s['speed'] ?? 0),
                        'cargo'           => (int)($s['cargo_capacity'] ?? 0),
                        'tactical_tip'    => 'Indispensable lors des assauts castraux pour ouvrir des brèches et pulvériser les remparts adverses.'
                    ];
                    $siegeJson = htmlspecialchars(json_encode($siegeModalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-surface garnison-unit-row <?= !$hasUnits ? 'opacity-60 bg-light-lt' : 'border-danger-subtle shadow-xs' ?>"
                     style="min-height:46px;"
                     onclick="openGarrisonUnitModal(<?= $siegeJson ?>)"
                     title="Cliquer pour afficher l'illustration HD et la description détaillée">
                    <!-- Miniature et descriptif -->
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <div class="position-relative flex-shrink-0 garnison-thumb" style="width:34px; height:34px; border-radius:6px; overflow:hidden; border:1px solid <?= $hasUnits ? '#dc2626' : '#cbd5e1' ?>;">
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
                            <a href="?page=shipyard" class="btn btn-outline-secondary py-0 px-1 text-muted" style="font-size:0.68rem; border-style:dashed;" onclick="event.stopPropagation();" title="Bâtir ou mobiliser cet engin">
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
                    <small class="text-secondary" style="font-size: 0.68rem;">Ashigaru, archers &amp; samouraïs (Cliquer pour voir détails)</small>
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

                    $roleBadge = ($t['code'] === 'spy_probe') ? 'Infiltration & Renseignement' : match((int)($t['tier'] ?? 1)) {
                        1 => 'Conscrit & Ligne',
                        2 => 'Spécialiste de Ligne',
                        3 => 'Guerrier d\'Élite',
                        4 => 'Champion & Garde Suprême',
                        default => 'Troupe du Dojo'
                    };
                    $tipText = match($t['code']) {
                        'pecheur' => 'Ouvrier halieutique indispensable pour mener des expéditions de pêche sur les parcelles de lac.',
                        'mineur' => 'Mineur de roche et métaux précieux mobilisable pour des expéditions sur les gisements montagnards.',
                        'colonizer' => 'Pionniers et maîtres charpentiers permettant de fonder un nouveau village castral indépendant.',
                        'spy_probe' => 'Éclaireur Shinobi furtif spécialisé dans l\'espionnage et l\'infiltration rapide des fiefs rivaux.',
                        default => 'Entraîné au Dojo, ce guerrier défend loyalement votre domaine castral et marche sous votre bannière lors des campagnes militaires.'
                    };

                    $troopModalData = [
                        'name'            => $t['name'],
                        'short_name'      => $t['name'],
                        'role_badge'      => 'Rang ' . ($t['tier'] ?? 1) . ' • ' . $roleBadge,
                        'badge_class'     => 'bg-primary',
                        'image_url'       => $imgSrc,
                        'description'     => !empty($t['description']) ? $t['description'] : 'Guerrier féodal entraîné au Dojo pour défendre les terres du Daimyō.',
                        'lore_title'      => 'Régiment du Dojo Militaire :',
                        'stationed_count' => $count,
                        'is_siege'        => false,
                        'attack'          => (int)($t['attack'] ?? 0),
                        'def_infantry'    => (int)($t['def_infantry'] ?? 0),
                        'def_mech'        => (int)($t['def_mech'] ?? 0),
                        'speed'           => (int)($t['speed'] ?? 0),
                        'cargo'           => (int)($t['cargo_capacity'] ?? 0),
                        'tactical_tip'    => $tipText
                    ];
                    $troopJson = htmlspecialchars(json_encode($troopModalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-surface garnison-unit-row <?= !$hasUnits ? 'opacity-50 bg-light-lt' : 'shadow-xs' ?>"
                     style="min-height:44px;"
                     onclick="openGarrisonUnitModal(<?= $troopJson ?>)"
                     title="Cliquer pour afficher l'illustration HD et la description détaillée">
                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                        <div class="position-relative flex-shrink-0 garnison-thumb" style="width:32px; height:32px; border-radius:6px; overflow:hidden; border:1px solid <?= $hasUnits ? '#3b82f6' : '#cbd5e1' ?>;">
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
         3. SECTION CAVALERIE, ÉCLAIREURS & LOGISTIQUE
         ==================================================== -->
    <?php if (!empty($otherShips)): ?>
        <div class="garnison-section mb-3" id="garnison-sec-other">
            <div class="d-flex align-items-center justify-content-between p-1.5 px-2 rounded mb-2" style="background: linear-gradient(90deg, rgba(16,185,129,0.1), rgba(6,182,212,0.05)); border-left: 3px solid #10b981;">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded bg-success text-white shadow-xs" style="width:24px; height:24px; font-size:0.75rem;">
                        <i class="fa-solid fa-horse"></i>
                    </span>
                    <div>
                        <strong class="d-block text-dark" style="font-size: 0.82rem;">Cavalerie, Shinobis &amp; Convois</strong>
                        <small class="text-secondary" style="font-size: 0.68rem;">Éclaireurs furtifs, convois logistiques &amp; escadrons montés (Cliquer pour voir détails)</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge <?= ($totalOtherEngines > 0) ? 'bg-success text-white' : 'bg-secondary-lt' ?> font-monospace fw-bold" style="font-size: 0.72rem;">
                        <?= number_format($totalOtherEngines) ?> unité(s)
                    </span>
                </div>
            </div>
            <div class="d-flex flex-column gap-1.5">
                <?php foreach ($otherShips as $os): ?>
                    <?php
                        $count    = (int)($os['stationed_count'] ?? 0);
                        $hasUnits = ($count > 0);
                        $code     = $os['code'];
                        $imgFile  = !empty($os['image']) ? $os['image'] : ($code . '.jpg');
                        $diskFile = __DIR__ . '/../../public/assets/units/' . $imgFile;
                        $imgSrc   = file_exists($diskFile) ? ('/public/assets/units/' . $imgFile . '?v=' . filemtime($diskFile)) : '/public/assets/tiles/tile_shipyard.png';

                        $isSpy       = ($code === 'spy_probe' || stripos($os['name'], 'shinobi') !== false || stripos($os['name'], 'éclaireur') !== false);
                        $isTransport = (str_starts_with($code, 'transporter') || stripos($os['name'], 'chariot') !== false || stripos($os['name'], 'convoi') !== false);

                        if ($isSpy) {
                            $roleBadge   = 'Shinobi & Renseignement';
                            $descDefault = 'Éclaireur furtif spécialisé dans l\'infiltration des fiefs rivaux pour espionner leurs stocks de riz, minerais et armées en garnison.';
                            $tipDefault  = 'Envoyez vos éclaireurs espionner les domaines cibles avant tout raid ou siège castral.';
                        } elseif ($isTransport) {
                            $roleBadge   = 'Logistique & Ravitaillement';
                            $descDefault = 'Chariot de transport à haute capacité d\'emport pour convoyer des vivres, du bois et des minerais entre vos provinces ou honorer les trocs du marché.';
                            $tipDefault  = 'Indispensable pour affréter des convois marchands et ravitailler vos avant-postes en denrées.';
                        } else {
                            $roleBadge   = 'Cavalerie & Mobilité';
                            $descDefault = 'Escadron monté rapide capable de mener des frappes de contournement et de pourchasser les fuyards.';
                            $tipDefault  = 'Excellente vitesse de déplacement, idéale pour les raids éclair sur les oasis sauvages.';
                        }

                        $otherModalData = [
                            'name'            => $os['name'],
                            'short_name'      => $os['name'],
                            'role_badge'      => $roleBadge,
                            'badge_class'     => 'bg-success',
                            'image_url'       => $imgSrc,
                            'description'     => !empty($os['description']) ? $os['description'] : $descDefault,
                            'lore_title'      => 'Écuries & Logistique du Fief :',
                            'stationed_count' => $count,
                            'is_siege'        => false,
                            'attack'          => (int)($os['attack'] ?? 0),
                            'defense'         => (int)($os['defense'] ?? 0) + (int)($os['shield'] ?? 0),
                            'speed'           => (int)($os['speed'] ?? 0),
                            'cargo'           => (int)($os['cargo_capacity'] ?? 0),
                            'tactical_tip'    => $tipDefault
                        ];
                        $otherJson = htmlspecialchars(json_encode($otherModalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-surface garnison-unit-row <?= !$hasUnits ? 'opacity-50 bg-light-lt' : 'shadow-xs border-success-subtle' ?>"
                         style="min-height:44px;"
                         onclick="openGarrisonUnitModal(<?= $otherJson ?>)"
                         title="Cliquer pour afficher l'illustration HD et les détails">
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <div class="position-relative flex-shrink-0 garnison-thumb" style="width:32px; height:32px; border-radius:6px; overflow:hidden; border:1px solid <?= $hasUnits ? '#10b981' : '#cbd5e1' ?>;">
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($os['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php if ($hasUnits): ?>
                                    <span class="position-absolute bottom-0 end-0 bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:12px; height:12px; font-size:8px;">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-truncate">
                                <span class="fw-semibold text-dark text-truncate d-block" style="font-size:0.78rem;" title="<?= htmlspecialchars($os['name']) ?>">
                                    <?= htmlspecialchars($os['name']) ?>
                                </span>
                                <div class="d-flex align-items-center gap-2 text-secondary" style="font-size:0.67rem;">
                                    <?php if ($isSpy): ?>
                                        <span title="Infiltration Shinobi"><i class="fa-solid fa-user-ninja text-secondary me-0.5"></i>Espionnage</span>
                                        <span title="Vitesse de reconnaissance"><i class="fa-solid fa-person-running text-info me-0.5"></i>Vit: <?= $os['speed'] ?></span>
                                    <?php elseif ($isTransport): ?>
                                        <span title="Capacité d'emport de fret"><i class="fa-solid fa-boxes-stacked text-warning me-0.5"></i>Fret: <?= number_format($os['cargo_capacity']) ?></span>
                                        <span title="Vitesse du convoi"><i class="fa-solid fa-truck-moving text-secondary me-0.5"></i>Vit: <?= $os['speed'] ?></span>
                                    <?php else: ?>
                                        <span><i class="fa-solid fa-khanda text-danger me-0.5"></i><?= $os['attack'] ?></span>
                                        <span><i class="fa-solid fa-shield-halved text-success me-0.5"></i><?= (int)$os['defense'] + (int)$os['shield'] ?></span>
                                        <span title="Vitesse de chevauchée"><i class="fa-solid fa-horse text-secondary me-0.5"></i>Vit: <?= $os['speed'] ?></span>
                                    <?php endif; ?>
                                    <span class="d-none d-sm-inline text-muted">&bull; <?= htmlspecialchars($roleBadge) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <?php if ($hasUnits): ?>
                                <span class="badge bg-success text-white font-monospace px-2 py-1 shadow-xs" style="font-size:0.8rem;">
                                    <?= number_format($count) ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=shipyard" class="btn btn-outline-secondary py-0 px-1 text-muted" style="font-size:0.68rem; border-style:dashed;" onclick="event.stopPropagation();" title="Mobiliser dans les écuries / atelier">
                                    0 <i class="fa-solid fa-plus ms-0.5 text-success"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalOtherEngines === 0): ?>
                <div class="p-2 mt-1 rounded bg-light text-center small text-muted border border-dashed" style="font-size:0.72rem;">
                    <i class="fa-solid fa-circle-info text-info me-1"></i>Aucune unité de cavalerie ou logistique en garnison.
                    <a href="?page=shipyard" class="text-success fw-bold text-decoration-underline ms-1">Mobiliser à l'Atelier</a>
                </div>
            <?php endif; ?>
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

<!-- ========================================================
     MODALE DE DÉTAIL D'UNITÉ & ARME DE SIÈGE (STYLE PÊCHEUR / MINEUR / HÉROS)
     ======================================================== -->
<div class="modal fade" id="modalGarrisonUnitDetail" tabindex="-1" aria-labelledby="garrisonUnitModalTitle" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: 14px;">
            <!-- Illustration d'en-tête (215px) avec overlay dégradé sombre et parallaxe cinématique -->
            <div class="depth-parallax-scene" style="position: relative; width: 100%; height: 215px; overflow: hidden; background: #0f172a;">
                <img id="garrisonUnitModalImage" class="depth-parallax-img" src="/public/assets/tiles/tile_barracks.png" alt="Unité Féodale" style="object-fit: cover; object-position: center 25%;">
                <div class="depth-parallax-overlay" style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.35) 50%, transparent 100%);"></div>
                <div style="position: absolute; bottom: 12px; left: 16px; right: 16px;" class="d-flex justify-content-between align-items-end flex-wrap gap-2 text-white depth-parallax-foreground">
                    <div style="max-width: 72%;">
                        <span id="garrisonUnitModalBadge" class="badge bg-danger text-white fw-bold px-2 py-1 mb-1 shadow-sm" style="font-size:0.75rem;">
                            Rôle Martiale
                        </span>
                        <h3 class="m-0 fw-bold text-white shadow-sm text-truncate" id="garrisonUnitModalTitle" style="font-size: 1.25rem;">
                            Nom de l'Unité
                        </h3>
                    </div>
                    <div>
                        <span id="garrisonUnitModalCountBadge" class="badge bg-dark bg-opacity-75 border border-light text-white font-monospace fs-6 px-3 py-1 shadow-sm">
                            0 en garnison
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white depth-parallax-foreground" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 12px; right: 12px; z-index: 10;" onclick="closeGarrisonUnitModal()"></button>
            </div>

            <!-- Corps de la modale -->
            <div class="modal-body p-4 bg-white">
                <!-- Description / Lore du Soldat ou de l'Engin -->
                <div class="p-3 rounded border mb-3" style="background: #fdfbf7; border-left: 4px solid #dc2626 !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-size: 1.1rem;"><i class="fa-solid fa-scroll text-danger"></i></span>
                        <strong class="text-danger small" id="garrisonUnitModalSubtitle">Chronique Militaire Féodale :</strong>
                    </div>
                    <p class="small text-secondary m-0" id="garrisonUnitModalDescription" style="line-height: 1.55;">
                        Description de l'unité...
                    </p>
                </div>

                <!-- Grille des Statistiques Martiales & Capacités -->
                <div class="p-3 rounded border mb-3" style="background: #f8fafc;">
                    <div class="small fw-bold text-dark mb-2 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-chart-simple text-danger"></i>
                        <span>Aptitudes &amp; Spécifications Martiales</span>
                    </div>
                    <div class="row g-2 text-center" id="garrisonUnitModalStatsGrid">
                        <!-- Injections dynamiques de stats -->
                    </div>
                </div>

                <!-- Encart Astuce Stratégique -->
                <div class="alert alert-secondary small p-2.5 mb-0 d-flex align-items-center gap-2" id="garrisonUnitModalTipBox" style="border-left: 4px solid #0ea5e9; background: rgba(14,165,233,0.06);">
                    <i class="fa-solid fa-circle-info fs-3 text-info flex-shrink-0"></i>
                    <span id="garrisonUnitModalTip" class="text-secondary" style="font-size:0.75rem;">
                        Conseil tactique...
                    </span>
                </div>
            </div>

            <!-- Pied de modale -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" onclick="closeGarrisonUnitModal()">
                    <i class="fa-solid fa-xmark me-1"></i>Fermer
                </button>
                <a href="?page=barracks" id="garrisonUnitModalActionBtn" class="btn btn-primary fw-bold px-3 shadow-sm">
                    <i class="fa-solid fa-torii-gate me-1"></i>Accéder au Dojo &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Script interactif : Modale & Filtres -->
<script>
function openGarrisonUnitModal(unit) {
    if (!unit) return;

    const imgEl        = document.getElementById('garrisonUnitModalImage');
    const titleEl      = document.getElementById('garrisonUnitModalTitle');
    const badgeEl      = document.getElementById('garrisonUnitModalBadge');
    const countBadgeEl = document.getElementById('garrisonUnitModalCountBadge');
    const descEl       = document.getElementById('garrisonUnitModalDescription');
    const subtitleEl   = document.getElementById('garrisonUnitModalSubtitle');
    const statsGrid    = document.getElementById('garrisonUnitModalStatsGrid');
    const tipEl        = document.getElementById('garrisonUnitModalTip');
    const actionBtn    = document.getElementById('garrisonUnitModalActionBtn');

    if (imgEl) imgEl.src = unit.image_url || '/public/assets/tiles/tile_barracks.png';
    if (titleEl) titleEl.textContent = unit.name || 'Guerrier Féodal';
    if (badgeEl) {
        badgeEl.textContent = unit.role_badge || 'Troupe Régulière';
        badgeEl.className = 'badge text-white fw-bold px-2 py-1 mb-1 shadow-sm ' + (unit.badge_class || 'bg-danger');
    }
    if (countBadgeEl) {
        const count = parseInt(unit.stationed_count || 0, 10);
        countBadgeEl.textContent = count.toLocaleString('fr-FR') + ' en garnison';
        if (count > 0) {
            countBadgeEl.className = 'badge bg-success bg-opacity-90 border border-light text-white font-monospace fs-6 px-3 py-1 shadow-sm';
        } else {
            countBadgeEl.className = 'badge bg-dark bg-opacity-75 border border-light text-white font-monospace fs-6 px-3 py-1';
        }
    }

    if (descEl) descEl.textContent = unit.description || 'Guerrier dévoué au service du Daimyō.';
    if (subtitleEl) subtitleEl.textContent = unit.lore_title || 'Chronique Militaire Féodale :';
    if (tipEl) tipEl.textContent = unit.tactical_tip || 'Mobilisez cette unité pour accroître la force de frappe de votre domaine.';

    // Construction de la grille de stats
    if (statsGrid) {
        let statsHtml = '';
        if (unit.is_siege) {
            statsHtml = `
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-khanda text-danger me-1"></i>Attaque</div>
                        <div class="fw-bold font-monospace text-danger fs-4">${(unit.attack || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-burst text-warning me-1"></i>Brèche Siège</div>
                        <div class="fw-bold font-monospace text-warning fs-4">${(unit.siege_base || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-shield-cat text-danger me-1"></i>x Muraille</div>
                        <div class="fw-bold font-monospace text-danger fs-4">x${unit.wall_mult || 1}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-cubes text-success me-1"></i>Blindage</div>
                        <div class="fw-bold font-monospace text-success fs-4">${(unit.defense || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-horse text-info me-1"></i>Vitesse</div>
                        <div class="fw-bold font-monospace text-info fs-4">${(unit.speed || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-sack-dollar text-warning me-1"></i>Capacité</div>
                        <div class="fw-bold font-monospace text-warning fs-4">${(unit.cargo || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
            `;
        } else {
            statsHtml = `
                <div class="col-3">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-khanda text-danger me-1"></i>Attaque</div>
                        <div class="fw-bold font-monospace text-danger fs-4">${(unit.attack || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-shield-halved text-success me-1"></i>Déf. Inf.</div>
                        <div class="fw-bold font-monospace text-success fs-4">${(unit.def_infantry || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-shield text-info me-1"></i>Déf. Cav.</div>
                        <div class="fw-bold font-monospace text-info fs-4">${(unit.def_mech || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-2 rounded border bg-white">
                        <div class="text-secondary small" style="font-size:0.68rem;"><i class="fa-solid fa-horse text-primary me-1"></i>Vitesse</div>
                        <div class="fw-bold font-monospace text-primary fs-4">${(unit.speed || 0).toLocaleString('fr-FR')}</div>
                    </div>
                </div>
            `;
        }
        statsGrid.innerHTML = statsHtml;
    }

    // Bouton d'action
    if (actionBtn) {
        if (unit.is_siege) {
            actionBtn.href = '?page=shipyard';
            actionBtn.className = 'btn btn-danger fw-bold px-3 shadow-sm';
            actionBtn.innerHTML = '<i class="fa-solid fa-hammer me-1"></i>Atelier de Siège &rarr;';
        } else {
            actionBtn.href = '?page=barracks';
            actionBtn.className = 'btn btn-primary fw-bold px-3 shadow-sm';
            actionBtn.innerHTML = '<i class="fa-solid fa-torii-gate me-1"></i>Dojo Militaire &rarr;';
        }
    }

    // Affichage de la modale
    const modalEl = document.getElementById('modalGarrisonUnitDetail');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(modalEl).modal('show');
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }
}

function closeGarrisonUnitModal() {
    const modalEl = document.getElementById('modalGarrisonUnitDetail');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bsModal = bootstrap.Modal.getInstance(modalEl);
            if (bsModal) bsModal.hide();
        } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(modalEl).modal('hide');
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    }
}

function filterGarnison(category, btn) {
    if (!btn) return;
    document.querySelectorAll('.garnison-filter-btn').forEach(function(b) {
        b.classList.remove('active', 'btn-danger', 'btn-primary', 'btn-success', 'btn-secondary');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active');
    if (category === 'siege') {
        btn.classList.add('btn-danger');
        btn.classList.remove('btn-outline-danger', 'btn-outline-secondary');
    } else if (category === 'infantry') {
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
    } else if (category === 'other') {
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-success', 'btn-outline-secondary');
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
    } else if (category === 'other') {
        if (secSiege) secSiege.classList.add('d-none');
        if (secInfantry) secInfantry.classList.add('d-none');
        if (secOther) secOther.classList.remove('d-none');
    } else {
        if (secSiege) secSiege.classList.remove('d-none');
        if (secInfantry) secInfantry.classList.remove('d-none');
        if (secOther) secOther.classList.remove('d-none');
    }
}
</script>
