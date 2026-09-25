<?php
/**
 * Vue Dédiée : Détail & Amélioration d'une Parcelle Rurale (Style Travian build.php)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$planet) {
    header('Location: /?page=resources');
    exit;
}

$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();

// Récupérer le numéro de slot demandé (entre 1 et 20)
$slot = (int)($_GET['slot'] ?? 1);
if ($slot > 20 && $slot <= 34) {
    header("Location: /?page=building&slot=$slot");
    exit;
}
if ($slot < 1 || $slot > 20) {
    $slot = 1;
}

// Récupérer toutes les parcelles de la planète
$fields = $planetEngine->getFields((int)$planet['id']);
$fieldsBySlot = [];
foreach ($fields as $f) {
    $fieldsBySlot[(int)$f['field_slot']] = $f;
}

$currentField = $fieldsBySlot[$slot] ?? [
    'field_slot' => $slot,
    'type' => FIELD_LAYOUT[$slot] ?? 'metal_mine',
    'level' => 0
];

$type = $currentField['type'];
$lvl = (int)$currentField['level'];
$info = FIELD_TYPES[$type] ?? FIELD_TYPES['metal_mine'];

// Bâtiments de la cité (Tenshu)
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

// Calcul des coûts et du temps d'amélioration
$upgradeDetails = $buildingEngine->getUpgradeDetails('field', $type, $lvl, $hqLevel);
$targetLevel = $upgradeDetails['target_level'];
$cost = $upgradeDetails['cost'];
$duration = $upgradeDetails['duration'];

// File de construction active
$queue = $buildingEngine->getQueue((int)$planet['id']);
$activeJob = null;
$fieldsInQueue = 0;
$buildingsInQueue = 0;

foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $fieldsInQueue++;
        if ((int)$q['target_id'] === $slot) {
            $activeJob = $q;
        }
    } else {
        $buildingsInQueue++;
    }
}

// Vérifier la disponibilité de la file selon la faction et le Sceau Impérial
require_once __DIR__ . '/../core/ImperialSealEngine.php';
$sealEngine = new ImperialSealEngine();
$isSealActive = $sealEngine->isSealActive((int)$user['id']);

$isTerran = ($user['faction'] === 'terran');
$maxAllowedFields = $isSealActive ? 2 : 1;
$maxAllowedTotal = $isSealActive ? 3 : 1;
$canQueueNewField = $isTerran ? ($fieldsInQueue < $maxAllowedFields) : (count($queue) < $maxAllowedTotal);

// Vérifier les ressources
$hasMetal = $planet['metal'] >= $cost['metal'];
$hasCrystal = $planet['crystal'] >= $cost['crystal'];
$hasDeut = $planet['deuterium'] >= $cost['deuterium'];
$canAfford = $hasMetal && $hasCrystal && $hasDeut;

// Temps d'attente estimé si ressources manquantes
$missingWaitSeconds = 0;
if (!$canAfford) {
    $prodRates = $planet['prod_rates'];
    $metalDiff = max(0, $cost['metal'] - $planet['metal']);
    $crystalDiff = max(0, $cost['crystal'] - $planet['crystal']);
    $deutDiff = max(0, $cost['deuterium'] - $planet['deuterium']);

    $waitTimes = [];
    if ($metalDiff > 0 && ($prodRates['metal'] ?? 0) > 0) {
        $waitTimes[] = ($metalDiff / ($prodRates['metal'] / 3600));
    }
    if ($crystalDiff > 0 && ($prodRates['crystal'] ?? 0) > 0) {
        $waitTimes[] = ($crystalDiff / ($prodRates['crystal'] / 3600));
    }
    if ($deutDiff > 0 && ($prodRates['deuterium'] ?? 0) > 0) {
        $waitTimes[] = ($deutDiff / ($prodRates['deuterium'] / 3600));
    }
    if (!empty($waitTimes)) {
        $missingWaitSeconds = (int)ceil(max($waitTimes));
    }
}

// Production actuelle vs suivante
$speed = (float)GameConfig::get('resource_speed', defined('SPEED_FACTOR') ? SPEED_FACTOR : 5);
if ($type === 'solar_plant') {
    $curProd = (int)(($info['base_prod'] ?? 0) * $lvl * pow(1.12, $lvl));
    $nextProd = (int)(($info['base_prod'] ?? 0) * $targetLevel * pow(1.12, $targetLevel));
    $unitLabel = 'Ferveur & Sérénité';
    $curEnergy = 0;
    $nextEnergy = 0;
} else {
    $curProd = (int)(($info['base_prod'] ?? 0) * $lvl * pow(1.15, $lvl) * $speed);
    $nextProd = (int)(($info['base_prod'] ?? 0) * $targetLevel * pow(1.15, $targetLevel) * $speed);
    $unitLabel = match($type) {
        'metal_mine' => 'Bois de Cèdre / h',
        'crystal_mine' => 'Pierre de Taille / h',
        'deuterium_synth' => 'Riz Impérial (Koku) / h',
        default => 'Ressources / h'
    };
    $curEnergy = (int)(($info['base_energy_cons'] ?? 0) * $lvl * pow(1.1, $lvl));
    $nextEnergy = (int)(($info['base_energy_cons'] ?? 0) * $targetLevel * pow(1.1, $targetLevel));
}
$diffProd = $nextProd - $curProd;

// Image de la tuile correspondante
$tileImg = match($type) {
    'metal_mine' => 'tile_bucheron.png',
    'crystal_mine' => 'tile_carriere.png',
    'deuterium_synth' => 'tile_riziere.png',
    'solar_plant' => 'tile_sanctuaire.png',
    default => 'tile_bucheron.png'
};

$tileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $tileImg)
    ? '/public/assets/tiles/' . $tileImg
    : '/public/assets/' . $tileImg;

$sectorClass = match($type) {
    'metal_mine' => 'sec-metal',
    'crystal_mine' => 'sec-crystal',
    'deuterium_synth' => 'sec-deut',
    'solar_plant' => 'sec-energy',
    default => 'sec-metal'
};

// Cartographie des illustrations artistiques des ressources féodales
$fieldHeroImages = [
    'metal_mine' => 'resources/ressource_bois_cedre.jpg',
    'crystal_mine' => 'resources/ressource_pierre_taille.jpg',
    'deuterium_synth' => 'resources/ressource_riz_imperial.jpg',
    'solar_plant' => 'resources/ressource_ferveur_shinto.jpg',
];
$fieldHeroRel = $fieldHeroImages[$type] ?? null;
$fieldHeroFile = $fieldHeroRel ? __DIR__ . '/../public/assets/' . $fieldHeroRel : null;
$fieldIllustrationUrl = ($fieldHeroFile && file_exists($fieldHeroFile))
    ? '/public/assets/' . $fieldHeroRel . '?v=' . filemtime($fieldHeroFile)
    : null;
$fieldHeroBgUrl = $fieldIllustrationUrl ?? ('/public/assets/shogun_rural_terroir_bg.jpg?v=' . (file_exists(__DIR__ . '/../public/assets/shogun_rural_terroir_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_rural_terroir_bg.jpg') : 1));

// Navigation parcelles précédente / suivante
$prevSlot = ($slot > 1) ? $slot - 1 : 20;
$nextSlot = ($slot < 20) ? $slot + 1 : 1;
?>

<!-- Navigation breadcrumb Tabler -->
<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Terroir de <?= htmlspecialchars($planet['name']) ?></div>
            <h2 class="page-title">
                <?= $info['icon'] ?? '🌾' ?> <?= htmlspecialchars($info['name']) ?>
                <span class="badge bg-secondary text-white ms-2" style="font-size:0.65rem; vertical-align:middle; color:#fff !important;">Parcelle #<?= $slot ?></span>
                <?php if ($activeJob): ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem; vertical-align:middle;">⏳ Chantier en cours</span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="/?page=field&slot=<?= $prevSlot ?>" class="btn btn-outline-secondary">
                    ← Parcelle #<?= $prevSlot ?>
                </a>
                <a href="/?page=resources" class="btn btn-secondary">
                    🌾 Vue Terroir
                </a>
                <a href="/?page=field&slot=<?= $nextSlot ?>" class="btn btn-outline-secondary">
                    Parcelle #<?= $nextSlot ?> →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stat Cards : production actuelle / prochaine -->
<div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="row align-items-center w-100 g-2">
                    <div class="col-auto"><span class="avatar rounded" style="background:rgba(146,64,14,0.12); font-size:1.3rem;"><?= $info['icon'] ?? '🪵' ?></span></div>
                    <div class="col">
                        <div class="font-weight-medium">Production actuelle</div>
                        <div class="text-secondary"><?= number_format($curProd) ?> <small><?= $unitLabel ?></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="row align-items-center w-100 g-2">
                    <div class="col-auto"><span class="avatar rounded bg-success-lt" style="font-size:1.3rem;">📈</span></div>
                    <div class="col">
                        <div class="font-weight-medium">Niveau <?= $targetLevel ?> → production</div>
                        <div class="text-success"><?= number_format($nextProd) ?> <small><?= $unitLabel ?></small> <span class="text-muted" style="font-size:0.75rem;">(+<?= number_format($diffProd) ?>)</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="row align-items-center w-100 g-2">
                    <div class="col-auto"><span class="avatar rounded bg-info-lt" style="font-size:1.3rem;">⏱️</span></div>
                    <div class="col">
                        <div class="font-weight-medium">Temps de construction</div>
                        <div class="text-info font-monospace"><?= gmdate('H:i:s', $duration) ?></div>
                        <div class="text-muted" style="font-size:0.72rem;">Tenshu Niv.<?= $hqLevel ?> · Vitesse ×<?= (int)$speed ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="row align-items-center w-100 g-2">
                    <div class="col-auto"><span class="avatar rounded bg-warning-lt" style="font-size:1.3rem;">⛩️</span></div>
                    <div class="col">
                        <div class="font-weight-medium">Sérénité requise (Niv.<?= $targetLevel ?>)</div>
                        <div><?php if ($type !== 'solar_plant' && $info['base_energy_cons'] > 0): ?>
                            <span class="text-warning"><?= $curEnergy ?> → <strong><?= $nextEnergy ?></strong></span>
                        <?php else: ?><span class="text-muted">— Sanctuaire producteur</span><?php endif; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-cards">

    <!-- Colonne gauche : info + illustration -->
    <div class="col-lg-5">

        <!-- Card identité du champ -->
        <div class="card mb-3">
            <?php if ($fieldIllustrationUrl): ?>
            <div class="card-img-top position-relative" style="position:relative; cursor:pointer; overflow:hidden; max-height:220px;" onclick="openArtworkModal('<?= $fieldIllustrationUrl ?>', '<?= htmlspecialchars(addslashes($info['name'])) ?>')" title="Agrandir l'estampe">
                <img src="<?= $fieldIllustrationUrl ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:100%; height:220px; object-fit:cover; display:block;">
                <span class="badge bg-dark text-white" style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.75) !important; font-size:0.75rem; padding:4px 8px; border-radius:4px; backdrop-filter:blur(3px); border:1px solid rgba(255,255,255,0.3); z-index:2; display:inline-flex; align-items:center; gap:4px;">
                    🔍 Agrandir
                </span>
                <!-- Badge Transparence IA Prompts (« ? ») -->
                <?= AiPromptHelper::renderBadge($fieldHeroRel, $info['name'], $fieldIllustrationUrl) ?>
            </div>
            <?php else: ?>
            <div style="height:120px; background:linear-gradient(135deg,rgba(146,64,14,0.15),rgba(22,101,52,0.1)); display:flex; align-items:center; justify-content:center; font-size:3rem;">
                <?= $info['icon'] ?? '🌾' ?>
            </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="position-relative me-3" style="cursor:pointer; display:inline-block;" onclick="openArtworkModal('<?= $tileUrl ?>', '<?= htmlspecialchars(addslashes($info['name'])) ?> &bull; Parcelle')" title="Agrandir la tuile">
                        <img src="<?= $tileUrl ?>" alt="<?= htmlspecialchars($info['name']) ?>" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:2px solid var(--tblr-border-color); display:block;">
                        <span class="badge bg-dark text-white position-absolute" style="bottom:-4px; right:-4px; font-size:0.6rem; padding:2px 4px; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.3); pointer-events:none;" title="Agrandir">
                            🔍
                        </span>
                    </div>
                    <div>
                        <h3 class="card-title mb-0"><?= htmlspecialchars($info['name']) ?></h3>
                        <div class="text-muted" style="font-size:0.8rem;">Parcelle #<?= $slot ?> sur 20 · Niveau actuel : <strong><?= $lvl ?></strong></div>
                    </div>
                </div>
                <p class="text-muted mb-3" style="font-size:0.875rem;"><?= htmlspecialchars($info['description']) ?></p>

                <?php if ($lvl > 0 && !$activeJob): ?>
                <button type="button" class="btn btn-outline-danger btn-sm w-100"
                        onclick="confirmDemolishField(<?= $slot ?>, '<?= htmlspecialchars(addslashes($info['name'] ?? $type)) ?>')">
                    💥 Raser l'Exploitation (récupère 30%)
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sélecteur rapide de toutes les parcelles -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🗺️ Toutes les Parcelles</h3>
                <div class="card-options text-muted" style="font-size:0.78rem;">Cliquez pour naviguer</div>
            </div>
            <div class="card-body p-2">
                <div class="row g-1">
                    <?php for ($i = 1; $i <= 20; $i++):
                        $f     = $fieldsBySlot[$i] ?? ['type' => 'metal_mine', 'level' => 0];
                        $fType = $f['type'];
                        $fLvl  = (int)$f['level'];
                        $fInfo = FIELD_TYPES[$fType] ?? FIELD_TYPES['metal_mine'];
                        $isCurrent = ($i === $slot);
                        $fTile = match($fType) {
                            'metal_mine'      => 'tile_bucheron.png',
                            'crystal_mine'    => 'tile_carriere.png',
                            'deuterium_synth' => 'tile_riziere.png',
                            'solar_plant'     => 'tile_sanctuaire.png',
                            default           => 'tile_bucheron.png'
                        };
                        $fTileUrl = file_exists(__DIR__ . '/../public/assets/tiles/' . $fTile)
                            ? '/public/assets/tiles/' . $fTile
                            : '/public/assets/' . $fTile;
                    ?>
                    <div class="col-auto">
                        <a href="/?page=field&slot=<?= $i ?>"
                           title="<?= htmlspecialchars($fInfo['name']) ?> #<?= $i ?> — Niv.<?= $fLvl ?>"
                           style="display:block; text-align:center; width:44px; text-decoration:none;">
                            <img src="<?= $fTileUrl ?>" alt=""
                                 style="width:40px; height:40px; border-radius:6px; object-fit:cover;
                                        border:2px solid <?= $isCurrent ? '#dc2626' : 'var(--tblr-border-color)' ?>;
                                        opacity:<?= $isCurrent ? '1' : '0.75' ?>;
                                        box-shadow:<?= $isCurrent ? '0 0 0 2px rgba(220,38,38,0.3)' : 'none' ?>;">
                            <div style="font-size:0.6rem; color:<?= $isCurrent ? '#dc2626' : 'var(--tblr-text-muted)' ?>; font-weight:<?= $isCurrent ? '800' : '400' ?>; line-height:1.4;">#<?= $i ?> N<?= $fLvl ?></div>
                        </a>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne droite : coûts + action -->
    <div class="col-lg-7">

        <!-- Coûts d'amélioration -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">🪵 Coûts d'Amélioration — Niveau <?= $targetLevel ?></h3>
                <div class="card-options text-muted" style="font-size:0.78rem;">Stock disponible dans vos greniers</div>
            </div>
            <div class="card-body">
                <?php
                $resources = [
                    ['icon'=>'🪵', 'name'=>'Bois de Cèdre',   'req'=>$cost['metal'],     'stock'=>$planet['metal'],     'ok'=>$hasMetal],
                    ['icon'=>'🪨', 'name'=>'Pierre de Taille', 'req'=>$cost['crystal'],   'stock'=>$planet['crystal'],   'ok'=>$hasCrystal],
                    ['icon'=>'🌾', 'name'=>'Riz Impérial',     'req'=>$cost['deuterium'], 'stock'=>$planet['deuterium'], 'ok'=>$hasDeut],
                ];
                foreach ($resources as $r):
                    $pct = ($r['req'] > 0) ? min(100, ($r['stock'] / $r['req']) * 100) : 100;
                ?>
                <div class="d-flex align-items-center mb-3">
                    <span style="font-size:1.3rem; min-width:2rem;"><?= $r['icon'] ?></span>
                    <div class="flex-fill mx-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium" style="font-size:0.875rem;"><?= $r['name'] ?></span>
                            <span class="text-muted" style="font-size:0.8rem;">
                                <?= number_format($r['stock']) ?> / <strong class="<?= $r['ok'] ? 'text-success' : 'text-danger' ?>"><?= number_format($r['req']) ?></strong> requis
                            </span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar <?= $r['ok'] ? 'bg-success' : 'bg-danger' ?>" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </div>
                    <span class="badge <?= $r['ok'] ? 'bg-success' : 'bg-danger' ?>" style="min-width:1.5rem;">
                        <?= $r['ok'] ? '✓' : '✗' ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Durée -->
                <div class="alert alert-info mt-3 mb-0" style="padding:0.6rem 0.85rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span>⏱️ Durée des travaux :</span>
                        <strong class="font-monospace"><?= gmdate('H:i:s', $duration) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone d'action : lancer / bloqié / en cours -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔨 Ordre de Construction</h3>
                <?php if ($isTerran): ?>
                    <div class="card-options"><span class="badge bg-blue-lt">Clan Oda — Double Chantier</span></div>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <?php if ($activeJob): ?>
                    <?php $isDemolishingJob = ((int)$activeJob['target_level'] === 0); ?>
                    <div class="alert alert-<?= $isDemolishingJob ? 'danger' : 'warning' ?> mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span style="font-size:1.3rem;">⏳</span>
                            <div>
                                <strong><?= $isDemolishingJob ? 'Démantèlement en cours' : 'Travaux en cours' ?></strong> →
                                <?= $isDemolishingJob ? 'Niveau 0 (Raser)' : 'Niveau ' . $activeJob['target_level'] ?>
                            </div>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.85rem;">
                            <?= $isDemolishingJob
                                ? 'Vos maîtres d\'œuvre déconstruisent cette exploitation. Vous récupérerez 30% des matériaux.'
                                : 'Vos artisans s\'activent sur la parcelle. Le rendement sera accru dès la fin des travaux.' ?>
                        </p>
                        <div class="font-monospace fw-bold fs-4 text-center py-2 job-timer-display" data-countdown="<?= $activeJob['finishes_at'] ?>">
                            Calcul...
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-danger w-100"
                            onclick="cancelFieldBuild(<?= (int)$activeJob['id'] ?>)">
                        🛑 <?= $isDemolishingJob ? 'Interrompre le démantèlement' : 'Interrompre les travaux (80% remboursé)' ?>
                    </button>

                <?php elseif (!$canQueueNewField): ?>
                    <div class="alert alert-secondary mb-3">
                        <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">🏗️</div>
                        <h4 class="alert-title">Maîtres d'œuvre déjà mobilisés</h4>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">
                            <?php if ($isTerran): ?>
                                Une autre parcelle rurale est déjà en développement. Le Clan Oda permet 1 parcelle rurale et 1 bâtiment simultanément.
                            <?php else: ?>
                                Vos bâtisseurs travaillent sur un autre chantier. Attendez la fin pour lancer cette parcelle.
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="/?page=resources" class="btn btn-secondary w-100">← Voir les chantiers en cours</a>

                <?php elseif (!$canAfford): ?>
                    <div class="alert alert-warning mb-3">
                        <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">⚠️</div>
                        <h4 class="alert-title">Ressources Insuffisantes</h4>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">
                            Vos greniers ne disposent pas des matériaux nécessaires.
                        </p>
                        <?php if ($missingWaitSeconds > 0): ?>
                        <div class="mt-2 text-center">
                            <span class="badge bg-warning text-dark">⏳ Ressources réunies dans : <?= gmdate('H:i:s', $missingWaitSeconds) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary w-100" disabled>
                        🔨 Améliorer au Niveau <?= $targetLevel ?>
                    </button>

                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <div style="font-size:1.5rem; text-align:center; margin-bottom:0.5rem;">✨</div>
                        <h4 class="alert-title">Ordre de Travaux Prêt</h4>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">
                            Matériaux prêts. Vos maîtres-charpentiers attendent votre décret pour ériger le <strong>Niveau <?= $targetLevel ?></strong>.
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg w-100" id="btnLaunchUpgrade"
                            onclick="launchFieldUpgrade(<?= $slot ?>, <?= $targetLevel ?>)">
                        🔨 Lancer l'Amélioration au Niveau <?= $targetLevel ?>
                    </button>
                <?php endif; ?>

                <div class="mt-3 text-center">
                    <a href="/?page=resources" class="text-muted" style="font-size:0.8rem;">← Revenir à la vue générale des 20 parcelles</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT INTERACTIF (inchangé) -->
<script>
async function launchFieldUpgrade(slot, targetLvl, fieldType = null) {
    const btn = document.getElementById('btnLaunchUpgrade');
    if (btn) { btn.disabled = true; btn.innerText = 'Transmission des ordres...'; }
    try {
        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('category', 'field');
        formData.append('target_id', slot);
        if (fieldType) formData.append('field_type', fieldType);
        const response = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur lors du lancement des travaux.', 'error');
            if (btn) { btn.disabled = false; btn.innerText = `🔨 Lancer l'Amélioration au Niveau ${targetLvl}`; }
        }
    } catch (err) {
        showModalAlert('Erreur réseau lors de la communication avec le fief.', 'error');
        if (btn) { btn.disabled = false; btn.innerText = `🔨 Lancer l'Amélioration au Niveau ${targetLvl}`; }
    }
}

async function cancelFieldBuild(queueId) {
    const confirmed = await showModalConfirm('Voulez-vous vraiment suspendre ces travaux ruraux ? 80% des matériaux investis vous seront restitués.', 'Interruption de Chantier');
    if (!confirmed) return;
    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('queue_id', queueId);
        const response = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { window.location.reload(); }
        else { showModalAlert(data.error || 'Impossible d\'interrompre ce chantier.', 'error'); }
    } catch (err) { showModalAlert('Erreur réseau lors de l\'interruption.', 'error'); }
}

async function confirmDemolishField(slot, fieldName) {
    const confirmed = await showModalConfirm(
        `Êtes-vous certain de vouloir raser <strong>${fieldName}</strong> sur la parcelle <strong>#${slot}</strong> ?<br><br>Vous récupérerez <strong>30% des matériaux</strong> à la fin des travaux.`,
        'Raser l\'Exploitation'
    );
    if (!confirmed) return;
    try {
        const formData = new FormData();
        formData.append('action', 'demolish');
        formData.append('category', 'field');
        formData.append('target_id', slot);
        formData.append('slot', slot);
        const response = await fetch('/api/build.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { window.location.reload(); }
        else { showModalAlert(data.error || 'Impossible de raser cette exploitation.', 'error'); }
    } catch (err) { showModalAlert('Erreur réseau lors de la suppression.', 'error'); }
}

function openArtworkModal(imgSrc, title) {
    const modal = document.getElementById('artworkModal');
    const img   = document.getElementById('artworkModalImg');
    const titleEl = document.getElementById('artworkModalTitle');
    if (modal && img && titleEl) { img.src = imgSrc; titleEl.innerText = title; modal.style.display = 'flex'; }
}
function closeArtworkModal(e) {
    const modal = document.getElementById('artworkModal');
    if (modal && (!e || e.target.id === 'artworkModal')) modal.style.display = 'none';
}
</script>

<!-- MODALE LIGHTBOX ESTAMPE HD -->
<div id="artworkModal" class="modal-overlay" style="display:none;" onclick="closeArtworkModal(event)">
    <div class="modal-card modal-card-lg" style="max-width:960px; padding:1.5rem;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--tblr-border-color); padding-bottom:0.75rem; margin-bottom:1rem;">
            <h3 id="artworkModalTitle" style="margin:0; font-size:1.1rem; font-weight:800;">🎨 Estampe Féodale</h3>
            <button type="button" class="btn-close" onclick="closeArtworkModal()"></button>
        </div>
        <div style="text-align:center;">
            <img id="artworkModalImg" src="" alt="Estampe" style="width:100%; height:auto; max-height:75vh; object-fit:contain; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        </div>
    </div>
</div>


