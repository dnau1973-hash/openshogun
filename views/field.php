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

// Récupérer le numéro de slot demandé (entre 1 et 19)
$slot = (int)($_GET['slot'] ?? 1);
if ($slot >= 20 && $slot <= 34) {
    header("Location: /?page=building&slot=$slot");
    exit;
}
if ($slot < 1 || $slot > 19) {
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

// Vérifier la disponibilité de la file selon la faction
$isTerran = ($user['faction'] === 'terran');
$canQueueNewField = $isTerran ? ($fieldsInQueue === 0) : (count($queue) === 0);

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
$prevSlot = ($slot > 1) ? $slot - 1 : 19;
$nextSlot = ($slot < 19) ? $slot + 1 : 1;
?>

<div class="container field-view-container" style="max-width: 1400px; margin: 0 auto; padding: 1.5rem 1rem;">

    <!-- Barre de Navigation Supérieure (Retour au Domaine + Sélecteur de Parcelle) -->
    <div class="field-nav-bar">
        <a href="/?page=resources" class="field-back-btn">
            <span>&larr;</span>
            <span>Retour au Terroir Féodal</span>
        </a>

        <div class="field-slot-switcher">
            <a href="/?page=field&slot=<?= $prevSlot ?>" class="field-arrow-btn" title="Parcelle précédente">
                &larr; Parcelle #<?= $prevSlot ?>
            </a>
            <div class="field-current-indicator">
                <span class="field-slot-badge">Parcelle #<?= $slot ?> sur 19</span>
            </div>
            <a href="/?page=field&slot=<?= $nextSlot ?>" class="field-arrow-btn" title="Parcelle suivante">
                Parcelle #<?= $nextSlot ?> &rarr;
            </a>
        </div>
    </div>

    <!-- CARTE PRINCIPALE : FOND DE CARTE DU TERROIR AVEC TILE DU CHAMP & DESCRIPTION -->
    <div class="field-hero-card">
        <!-- Fond de carte estompé du terroir féodal -->
        <div class="field-hero-bg" style="background-image: url('<?= $fieldHeroBgUrl ?>');"></div>
        <div class="field-hero-overlay"></div>

        <div class="field-hero-content">
            <!-- Emplacement Mis en Valeur : TILE DU CHAMP -->
            <div class="field-tile-stage">
                <div class="field-tile-pedestal">
                    <img src="/public/assets/<?= $tileImg ?>" 
                         class="field-tile-img" 
                         alt="<?= htmlspecialchars($info['name']) ?>" 
                         draggable="false">
                    <div class="field-tile-glow"></div>
                </div>
                <div class="field-level-emblem">
                    <span class="emblem-lvl-text">NIVEAU</span>
                    <span class="emblem-lvl-number"><?= $lvl ?></span>
                </div>
            </div>

            <!-- Identité & Description Thématique du Bâtiment -->
            <div class="field-meta-pane">
                <div class="field-header-row">
                    <div>
                        <div class="field-type-pill <?= $sectorClass ?>">
                            <span><?= $info['icon'] ?></span>
                            <span><?= htmlspecialchars($info['res_name'] ?? 'Ressource') ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap; margin-top:0.25rem;">
                            <h1 class="field-title" style="margin:0;"><?= htmlspecialchars($info['name']) ?></h1>
                            <?php if ($fieldIllustrationUrl): ?>
                                <button type="button" onclick="openArtworkModal('<?= $fieldIllustrationUrl ?>', '<?= htmlspecialchars(addslashes($info['name'])) ?>')" class="btn btn-secondary" style="font-size:0.75rem; padding:0.2rem 0.55rem; border-radius:6px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.3); color:#fde047; cursor:pointer;" title="Agrandir l'illustration artistique en haute définition">
                                    🎨 Estampe HD
                                </button>
                            <?php endif; ?>
                        </div>
                        <span class="field-subtitle">Emplacement cadastral #<?= $slot ?> &bull; Domaine de <?= htmlspecialchars($planet['name']) ?></span>
                    </div>

                    <?php if ($activeJob): ?>
                        <div class="field-status-badge upgrading">
                            <span class="pulse-dot"></span>
                            <span>Chantier en cours : Niveau <?= $activeJob['target_level'] ?></span>
                        </div>
                    <?php else: ?>
                        <div class="field-status-badge ready">
                            <span>Statut : Opérationnel</span>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="field-description">
                    <?= htmlspecialchars($info['description']) ?>
                </p>

                <!-- Bandeau récapitulatif rapide de rendement -->
                <div class="field-quick-stats">
                    <div class="quick-stat-box">
                        <span class="stat-label">Production actuelle</span>
                        <span class="stat-value">+<?= number_format($curProd) ?> <small><?= $unitLabel ?></small></span>
                    </div>
                    <div class="quick-stat-box highlight">
                        <span class="stat-label">Au Niveau <?= $targetLevel ?></span>
                        <span class="stat-value">+<?= number_format($nextProd) ?> <small><?= $unitLabel ?></small></span>
                        <span class="stat-gain">(+<?= number_format($diffProd) ?> / h)</span>
                    </div>
                    <?php if ($type !== 'solar_plant' && $info['base_energy_cons'] > 0): ?>
                        <div class="quick-stat-box">
                            <span class="stat-label">Sérénité requise</span>
                            <span class="stat-value" style="color:#fbbf24;">⛩️ <?= $curEnergy ?> &rarr; <?= $nextEnergy ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION DES TRAVAUX & DÉTAILS DU NIVEAU SUIVANT -->
    <div class="field-upgrade-grid">
        
        <!-- Colonne Gauche : Conditions & Coûts pour le Niveau Suivant -->
        <div class="field-card">
            <div class="field-card-header">
                <h3>🪵 Coûts d'Amélioration &bull; Niveau <?= $targetLevel ?></h3>
                <span style="font-size:0.8rem; color:var(--text-muted);">Stock disponible dans vos greniers</span>
            </div>
            <div class="field-card-body">
                <div class="cost-grid">
                    <!-- Bois -->
                    <div class="cost-item <?= $hasMetal ? 'cost-ok' : 'cost-missing' ?>">
                        <div class="cost-icon">🪵</div>
                        <div class="cost-details">
                            <span class="cost-name">Bois de Cèdre</span>
                            <span class="cost-req"><?= number_format($cost['metal']) ?></span>
                            <span class="cost-stock">Stock : <?= number_format($planet['metal']) ?></span>
                        </div>
                        <div class="cost-check"><?= $hasMetal ? '✓' : '✗' ?></div>
                    </div>

                    <!-- Pierre -->
                    <div class="cost-item <?= $hasCrystal ? 'cost-ok' : 'cost-missing' ?>">
                        <div class="cost-icon">🪨</div>
                        <div class="cost-details">
                            <span class="cost-name">Pierre de Taille</span>
                            <span class="cost-req"><?= number_format($cost['crystal']) ?></span>
                            <span class="cost-stock">Stock : <?= number_format($planet['crystal']) ?></span>
                        </div>
                        <div class="cost-check"><?= $hasCrystal ? '✓' : '✗' ?></div>
                    </div>

                    <!-- Riz -->
                    <div class="cost-item <?= $hasDeut ? 'cost-ok' : 'cost-missing' ?>">
                        <div class="cost-icon">🌾</div>
                        <div class="cost-details">
                            <span class="cost-name">Riz Impérial</span>
                            <span class="cost-req"><?= number_format($cost['deuterium']) ?></span>
                            <span class="cost-stock">Stock : <?= number_format($planet['deuterium']) ?></span>
                        </div>
                        <div class="cost-check"><?= $hasDeut ? '✓' : '✗' ?></div>
                    </div>

                    <!-- Sérénité / Ferveur -->
                    <?php if ($type !== 'solar_plant'): ?>
                        <div class="cost-item cost-ok">
                            <div class="cost-icon">⛩️</div>
                            <div class="cost-details">
                                <span class="cost-name">Ferveur / Sérénité</span>
                                <span class="cost-req"><?= $nextEnergy ?></span>
                                <span class="cost-stock">Dispo : <?= $planet['energy_max'] - $planet['energy_used'] ?></span>
                            </div>
                            <div class="cost-check">✓</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Durée des Travaux (Temps de construction pour le niveau suivant) -->
                <div class="construction-time-banner">
                    <div class="time-label-group">
                        <span class="time-icon">⏱️</span>
                        <div>
                            <strong>Temps de construction pour le Niveau <?= $targetLevel ?> :</strong>
                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                Réduit grâce au Tenshu (Donjon Castral Niv. <?= $hqLevel ?>) &bull; Vitesse x<?= (int)$speed ?>
                            </div>
                        </div>
                    </div>
                    <div class="time-value-display">
                        <?= gmdate('H:i:s', $duration) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Lancement des Travaux / État du Chantier -->
        <div class="field-card">
            <div class="field-card-header">
                <h3>🔨 Ordre de Construction Féodal</h3>
                <?php if ($isTerran): ?>
                    <span title="Bonus Clan Oda" style="font-size:0.75rem; color:#93c5fd; background:rgba(59,130,246,0.2); padding:0.15rem 0.45rem; border-radius:4px;">Clan Oda (Double Chantier)</span>
                <?php endif; ?>
            </div>
            <div class="field-card-body" style="display: flex; flex-direction: column; justify-content: space-between;">

                <?php if ($activeJob): ?>
                    <?php $isDemolishingJob = ((int)$activeJob['target_level'] === 0); ?>
                    <!-- Chantier en cours sur cette parcelle -->
                    <div class="field-active-job-box">
                        <div class="job-status-title">
                            <span class="job-spinner">⏳</span>
                            <?php if ($isDemolishingJob): ?>
                                <span>Démantèlement en cours vers le <strong>Niveau 0 (Raser)</strong></span>
                            <?php else: ?>
                                <span>Travaux en cours vers le <strong>Niveau <?= $activeJob['target_level'] ?></strong></span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin:0.5rem 0 1rem 0;">
                            <?php if ($isDemolishingJob): ?>
                                Vos maîtres d'œuvre déconstruisent cette exploitation pour réinitialiser la parcelle. Vous récupérerez 30% des matériaux à l'achèvement des travaux.
                            <?php else: ?>
                                Vos artisans et paysans s'activent sur la parcelle. Le rendement sera automatiquement accru dès la fin des travaux.
                            <?php endif; ?>
                        </p>

                        <div class="job-timer-display" data-countdown="<?= $activeJob['finishes_at'] ?>" style="<?= $isDemolishingJob ? 'color:#f87171;' : '' ?>">
                            Calcul du temps restant...
                        </div>

                        <div style="margin-top: 1.25rem;">
                            <button type="button" class="field-btn-cancel" onclick="cancelFieldBuild(<?= (int)$activeJob['id'] ?>)">
                                <?= $isDemolishingJob ? '🛑 Interrompre le démantèlement (Exploitation préservée)' : '🛑 Interrompre les travaux (80% remboursé)' ?>
                            </button>
                        </div>
                    </div>

                <?php elseif (!$canQueueNewField): ?>
                    <!-- File de construction saturée -->
                    <div class="field-blocked-box">
                        <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🏗️</div>
                        <h4>Maîtres d'œuvre déjà mobilisés</h4>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                            <?php if ($isTerran): ?>
                                Une autre parcelle rurale est déjà en cours de développement. Le Clan Oda permet 1 parcelle rurale et 1 bâtiment urbain en simultané.
                            <?php else: ?>
                                Vos équipes de bâtisseurs travaillent déjà sur un autre chantier du domaine. Attendez la fin des travaux pour lancer cette parcelle.
                            <?php endif; ?>
                        </p>
                        <a href="/?page=resources" class="field-btn-secondary" style="margin-top:1rem; display:inline-block;">
                            Voir les chantiers en cours &rarr;
                        </a>
                    </div>

                <?php elseif (!$canAfford): ?>
                    <!-- Ressources insuffisantes -->
                    <div class="field-blocked-box">
                        <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">⚠️</div>
                        <h4>Ressources Insuffisantes</h4>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                            Vos greniers ne disposent pas encore des matériaux nécessaires pour lancer les travaux de cette parcelle.
                        </p>
                        <?php if ($missingWaitSeconds > 0): ?>
                            <div class="time-to-afford">
                                ⏳ Ressources réunies dans environ : <strong><?= gmdate('H:i:s', $missingWaitSeconds) ?></strong>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="field-btn-primary disabled" disabled style="margin-top:1rem; opacity:0.5; cursor:not-allowed;">
                            🔨 Améliorer au Niveau <?= $targetLevel ?>
                        </button>
                    </div>

                <?php else: ?>
                    <!-- Prêt pour lancer les travaux -->
                    <div class="field-ready-box">
                        <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">✨</div>
                        <h4>Ordre de Travaux Prêt</h4>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                            Les matériaux sont prêts et vos maîtres-charpentiers attendent votre décret pour ériger le <strong>Niveau <?= $targetLevel ?></strong>.
                        </p>

                        <div style="margin-top: 1.5rem;">
                            <button type="button" class="field-btn-primary" id="btnLaunchUpgrade" onclick="launchFieldUpgrade(<?= $slot ?>, <?= $targetLevel ?>)">
                                🔨 Lancer l'Amélioration au Niveau <?= $targetLevel ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Raccourci vers la Cité & Autres Parcelles -->
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); text-align: center;">
                    <a href="/?page=resources" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: underline;">
                        &larr; Revenir à la vue générale des 18 parcelles
                    </a>
                </div>
            </div>
        </div>

    <!-- ZONE DE DÉMOLITION DE LA PARCELLE -->
    <?php if ($lvl > 0 && !$activeJob): ?>
        <div class="card" style="margin-top: 1.5rem; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; padding: 1.25rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h4 style="color: #f87171; margin: 0; font-size: 0.95rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🗑️</span> Raser cette Exploitation
                    </h4>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0.35rem 0 0 0;">
                        Rase définitivement cette exploitation pour réinitialiser la parcelle <strong>#<?= $slot ?></strong> en terrain vierge. Vous récupérerez <strong>30% des matériaux</strong> de ce niveau.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" onclick="confirmDemolishField(<?= $slot ?>, '<?= htmlspecialchars(addslashes($info['name'] ?? $type)) ?>')" style="background: linear-gradient(135deg, #991b1b, #dc2626); border: 1px solid #f87171; font-weight: 800; font-size: 0.82rem; padding: 0.55rem 1.1rem; border-radius: 8px; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35); transition: transform 0.15s ease;">
                        <span>💥</span> Raser l'Exploitation
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- BANDEAU DES 18 PARCELLES DU DOMAINE (NAVIGATION RAPIDE / STYLE TRAVIAN) -->
    <div class="field-strip-card">
        <div class="field-strip-header">
            <h4>🗺️ Toutes les Parcelles du Terroir (<?= htmlspecialchars($planet['name']) ?>)</h4>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Cliquez sur une parcelle pour y accéder directement</span>
        </div>
        <div class="field-strip-grid">
            <?php for ($i = 1; $i <= 18; $i++): ?>
                <?php 
                    $f = $fieldsBySlot[$i] ?? ['type' => 'metal_mine', 'level' => 0];
                    $fType = $f['type'];
                    $fLvl = (int)$f['level'];
                    $fInfo = FIELD_TYPES[$fType] ?? FIELD_TYPES['metal_mine'];
                    $isCurrent = ($i === $slot);
                    $fTile = match($fType) {
                        'metal_mine' => 'tile_bucheron.png',
                        'crystal_mine' => 'tile_carriere.png',
                        'deuterium_synth' => 'tile_riziere.png',
                        'solar_plant' => 'tile_sanctuaire.png',
                        default => 'tile_bucheron.png'
                    };
                ?>
                <a href="/?page=field&slot=<?= $i ?>" class="field-strip-item <?= $isCurrent ? 'active' : '' ?>" title="<?= htmlspecialchars($fInfo['name']) ?> #<?= $i ?> (Niveau <?= $fLvl ?>)">
                    <img src="/public/assets/<?= $fTile ?>" class="strip-item-img" alt="">
                    <span class="strip-item-num">#<?= $i ?></span>
                    <span class="strip-item-lvl">Nv.<?= $fLvl ?></span>
                </a>
            <?php endfor; ?>
        </div>
    </div>

</div>

<!-- SCRIPT INTERACTIF POUR LE LANCEMENT ET L'ANNULATION DES TRAVAUX -->
<script>
async function launchFieldUpgrade(slot, targetLvl, fieldType = null) {
    const btn = document.getElementById('btnLaunchUpgrade');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Transmissions des ordres...';
    }

    try {
        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('category', 'field');
        formData.append('target_id', slot);
        if (fieldType) {
            formData.append('field_type', fieldType);
        }

        const response = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Erreur lors du lancement des travaux.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerText = `🔨 Lancer l'Amélioration au Niveau ${targetLvl}`;
            }
        }
    } catch (err) {
        console.error(err);
        showModalAlert('Erreur réseau lors de la communication avec le fief.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerText = `🔨 Lancer l'Amélioration au Niveau ${targetLvl}`;
        }
    }
}

async function cancelFieldBuild(queueId) {
    const confirmed = await showModalConfirm(
        'Voulez-vous vraiment suspendre ces travaux ruraux ? 80% des matériaux investis vous seront restitués.',
        'Interruption de Chantier'
    );
    if (!confirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('queue_id', queueId);

        const response = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'interrompre ce chantier.', 'error');
        }
    } catch (err) {
        console.error(err);
        showModalAlert('Erreur réseau lors de l\'interruption.', 'error');
    }
}

async function confirmDemolishField(slot, fieldName) {
    const confirmed = await showModalConfirm(
        `Êtes-vous certain de vouloir raser définitivement l'exploitation ${fieldName} sur la parcelle #${slot} ?\n\nUn ordre de démolition sera émis avec un compte à rebours. Vous récupérerez 30% des matériaux à la fin des travaux.`,
        'Démantèlement de l\'Exploitation'
    );
    if (!confirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'demolish');
        formData.append('category', 'field');
        formData.append('target_id', slot);
        formData.append('slot', slot);

        const response = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible de raser cette exploitation.', 'error');
        }
    } catch (err) {
        console.error(err);
        showModalAlert('Erreur réseau lors de la suppression.', 'error');
    }
}

function openArtworkModal(imgSrc, title) {
    const modal = document.getElementById('artworkModal');
    const img = document.getElementById('artworkModalImg');
    const titleEl = document.getElementById('artworkModalTitle');
    if (modal && img && titleEl) {
        img.src = imgSrc;
        titleEl.innerText = title;
        modal.style.display = 'flex';
    }
}
function closeArtworkModal(e) {
    const modal = document.getElementById('artworkModal');
    if (modal && (!e || e.target.id === 'artworkModal')) {
        modal.style.display = 'none';
    }
}
</script>

<!-- MODALE LIGHTBOX ESTAMPE HD -->
<div id="artworkModal" class="modal-overlay" style="display:none;" onclick="closeArtworkModal(event)">
    <div class="modal-card modal-card-lg" style="max-width: 960px; padding: 1.5rem; background: var(--bg-surface, #fdfbf7);" onclick="event.stopPropagation()">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 id="artworkModalTitle" style="margin:0; font-size: 1.15rem; color: var(--text-main); font-weight: 800; display:flex; align-items:center; gap:0.5rem;">
                <span>🎨</span> Estampe Féodale Authentique
            </h3>
            <button type="button" class="modal-close-btn" onclick="closeArtworkModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <img id="artworkModalImg" src="" alt="Estampe" style="width: 100%; height: auto; max-height: 75vh; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.35); border: 1px solid var(--border-color);">
        </div>
    </div>
</div>

