<?php
/**
 * Vue Dédiée : Détail & Amélioration d'un Bâtiment de Cité Castrale (Style Travian build.php)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$planet) {
    header('Location: /?page=city');
    exit;
}

$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();

// Récupérer le numéro de slot ou le code de bâtiment
$slot = isset($_GET['slot']) ? (int)$_GET['slot'] : null;
$codeParam = $_GET['code'] ?? null;

if ($codeParam && !isset($_GET['slot'])) {
    foreach (CITY_SLOT_LAYOUT as $s => $c) {
        if ($c === $codeParam) {
            $slot = $s;
            break;
        }
    }
}

if (!$slot || $slot < 19 || $slot > 34) {
    $slot = 19;
}

$code = CITY_SLOT_LAYOUT[$slot] ?? 'free_plot';
$isEmptyPlot = ($code === 'free_plot');

// Bâtiments de la planète
$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = (int)($buildings['hq'] ?? 1);

// Navigation slots urbains précédents / suivants (19 à 34)
$prevSlot = ($slot > 19) ? $slot - 1 : 34;
$nextSlot = ($slot < 34) ? $slot + 1 : 19;

// Secteurs
$buildingSectors = [
    'hq' => ['name' => 'Tenshu Donjon', 'sec' => 'sec-hq', 'icon' => '🏯'],
    'shipyard' => ['name' => 'Écuries & Cavalerie', 'sec' => 'sec-military', 'icon' => '🐎'],
    'barracks' => ['name' => 'Dojo Militaire', 'sec' => 'sec-military', 'icon' => '🥋'],
    'radar' => ['name' => 'Poste de Vigie', 'sec' => 'sec-military', 'icon' => '🔭'],
    'wall' => ['name' => 'Muraille & Remparts', 'sec' => 'sec-military', 'icon' => '🧱'],
    'research_lab' => ['name' => 'Académie des Savoirs', 'sec' => 'sec-science', 'icon' => '📜'],
    'embassy' => ['name' => 'Pavillon Diplomatique', 'sec' => 'sec-science', 'icon' => '⛩️'],
    'storage' => ['name' => 'Greniers de Cèdre & Pierre', 'sec' => 'sec-logistics', 'icon' => '🪵'],
    'tank' => ['name' => 'Silo à Riz Impérial', 'sec' => 'sec-logistics', 'icon' => '🌾'],
    'quantum_vault' => ['name' => 'Cachette Secrète', 'sec' => 'sec-logistics', 'icon' => '🔒'],
    'market' => ['name' => 'Marché Castral', 'sec' => 'sec-logistics', 'icon' => '⚖️'],
    'free_plot' => ['name' => 'Terrain Vierge', 'sec' => 'sec-logistics', 'icon' => '⛳'],
];

$sectorInfo = $buildingSectors[$code] ?? ['name' => 'Bâtiment Castral', 'sec' => 'sec-logistics', 'icon' => '🏯'];

// File active de construction
$queue = $buildingEngine->getQueue((int)$planet['id']);
$activeJob = null;
$fieldsInQueue = 0;
$buildingsInQueue = 0;

foreach ($queue as $q) {
    if ($q['build_category'] === 'field') {
        $fieldsInQueue++;
    } else {
        $buildingsInQueue++;
        if ($q['target_id'] === $code) {
            $activeJob = $q;
        }
    }
}

$isTerran = ($user['faction'] === 'terran');
$canQueueNewBuilding = $isTerran ? ($buildingsInQueue === 0) : (count($queue) === 0);

if (!$isEmptyPlot) {
    $bInfo = BUILDINGS[$code] ?? null;
    $lvl = (int)($buildings[$code] ?? 0);
    $upgradeDetails = $buildingEngine->getUpgradeDetails('building', $code, $lvl, $hqLevel);
    $targetLevel = $upgradeDetails['target_level'];
    $cost = $upgradeDetails['cost'];
    $duration = $upgradeDetails['duration'];
    $tileImg = $bInfo['tile_img'] ?? 'tile_tenshu.png';

    // Vérification des ressources
    $hasMetal = $planet['metal'] >= $cost['metal'];
    $hasCrystal = $planet['crystal'] >= $cost['crystal'];
    $hasDeut = $planet['deuterium'] >= $cost['deuterium'];
    $canAfford = $hasMetal && $hasCrystal && $hasDeut;

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

    // Statistiques comparatives actuelles vs niveau supérieur
    $statData = match($code) {
        'wall' => [
            'label' => 'Bonus Défense Garnison',
            'cur' => '+' . ($lvl * 4) . '% (+' . ($lvl * 25) . ' pts mur)',
            'next' => '+' . ($targetLevel * 4) . '% (+' . ($targetLevel * 25) . ' pts mur)',
            'gain' => '+4% déf. & +25 pts structure'
        ],
        'hq' => [
            'label' => 'Réduction Temps Travaux',
            'cur' => '-' . (int)($lvl * 20) . '%',
            'next' => '-' . (int)($targetLevel * 20) . '%',
            'gain' => '-20% temps de chantier'
        ],
        'storage' => [
            'label' => 'Capacité Bois & Pierre',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLevel * 15000),
            'gain' => '+15,000 stockage'
        ],
        'tank' => [
            'label' => 'Capacité Silo à Riz',
            'cur' => number_format(10000 + $lvl * 15000),
            'next' => number_format(10000 + $targetLevel * 15000),
            'gain' => '+15,000 koku de réserve'
        ],
        'quantum_vault' => [
            'label' => 'Ressources Protégées (Cachette)',
            'cur' => number_format(1000 + $lvl * 1000),
            'next' => number_format(1000 + $targetLevel * 1000),
            'gain' => '+1,000 protégés du pillage'
        ],
        'barracks' => [
            'label' => 'Vitesse Entraînement Dojo',
            'cur' => 'Niveau ' . $lvl . ' (Accéléré)',
            'next' => 'Niveau ' . $targetLevel . ' (Plus Rapide)',
            'gain' => 'Cadence d\'enrôlement accrue'
        ],
        'shipyard' => [
            'label' => 'Écuries & Haras Féodaux',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => 'Mobilisation de cavalerie accélérée'
        ],
        'research_lab' => [
            'label' => 'Recherche Académique',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => 'Étude accélérée des savoirs'
        ],
        'radar' => [
            'label' => 'Portée du Poste de Guet',
            'cur' => ($lvl * 3) . ' lieues',
            'next' => ($targetLevel * 3) . ' lieues',
            'gain' => '+3 lieues de vigilance'
        ],
        'embassy' => [
            'label' => 'Prestige Diplomatique',
            'cur' => 'Rang ' . $lvl,
            'next' => 'Rang ' . $targetLevel,
            'gain' => '+1 rang de clan féodal'
        ],
        'market' => [
            'label' => 'Marchands & Convois',
            'cur' => ($lvl * 2) . ' marchands',
            'next' => ($targetLevel * 2) . ' marchands',
            'gain' => '+2 caravanes marchandes'
        ],
        default => [
            'label' => 'Niveau d\'élévation',
            'cur' => 'Niveau ' . $lvl,
            'next' => 'Niveau ' . $targetLevel,
            'gain' => '+1 niveau'
        ]
    };
}
?>

<div class="container field-view-container" style="max-width: 1400px; margin: 0 auto; padding: 1.5rem 1rem;">

    <!-- Barre de Navigation Supérieure (Retour à la Cité + Sélecteur de Slot Urbain) -->
    <div class="field-nav-bar">
        <a href="/?page=city" class="field-back-btn">
            <span>&larr;</span>
            <span>Retour à la Cité Castrale</span>
        </a>

        <div class="field-slot-switcher">
            <a href="/?page=building&slot=<?= $prevSlot ?>" class="field-arrow-btn" title="Bâtiment précédent">
                &larr; Slot #<?= $prevSlot ?>
            </a>
            <div class="field-current-indicator">
                <span class="field-slot-badge">Slot Castral #<?= $slot ?> sur 34</span>
            </div>
            <a href="/?page=building&slot=<?= $nextSlot ?>" class="field-arrow-btn" title="Bâtiment suivant">
                Slot #<?= $nextSlot ?> &rarr;
            </a>
        </div>
    </div>

    <?php if ($isEmptyPlot): ?>
        <!-- CAS : EMPLACEMENT LIBRE / TERRAIN DISPONIBLE -->
        <div class="field-hero-card">
            <div class="field-hero-bg" style="background-image: url('/public/assets/shogun_castle_city_bg.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1 ?>');"></div>
            <div class="field-hero-overlay"></div>

            <div class="field-hero-content">
                <div class="field-tile-stage">
                    <div class="field-tile-pedestal" style="border-style:dashed;">
                        <span style="font-size:3.5rem; opacity:0.6;">🏗️</span>
                    </div>
                    <div class="field-level-emblem" style="background:linear-gradient(135deg, #475569, #334155);">
                        <span class="emblem-lvl-text">TERRAIN</span>
                        <span class="emblem-lvl-number">LIBRE</span>
                    </div>
                </div>

                <div class="field-meta-pane">
                    <div class="field-header-row">
                        <div>
                            <div class="field-type-pill sec-logistics">
                                <span>⛳</span>
                                <span>Emplacement Disponible</span>
                            </div>
                            <h1 class="field-title">Terrain Castral #<?= $slot ?></h1>
                            <span class="field-subtitle">Cour intérieure &bull; Domaine de <?= htmlspecialchars($planet['name']) ?></span>
                        </div>
                        <div class="field-status-badge ready">
                            <span>Terrain prêt à bâtir</span>
                        </div>
                    </div>

                    <p class="field-description">
                        Cet emplacement est viabilisé au sein de l'enceinte de votre forteresse. Au fur et à mesure de l'élévation de votre Tenshu (Donjon Castral), de nouvelles structures féodales pourront y être fondées.
                    </p>

                    <div style="margin-top:1.5rem;">
                        <a href="/?page=city" class="btn btn-secondary">
                            &larr; Explorer les autres édifices de la Cité
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- CAS : BÂTIMENT FÉODAL ACTIF / CONSTRUCTIBLE -->
        <div class="field-hero-card">
            <div class="field-hero-bg" style="background-image: url('/public/assets/shogun_castle_city_bg.jpg?v=<?= file_exists(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') ? filemtime(__DIR__ . '/../public/assets/shogun_castle_city_bg.jpg') : 1 ?>');"></div>
            <div class="field-hero-overlay"></div>

            <div class="field-hero-content">
                <!-- TILE DU BÂTIMENT SUR SON PIÉDESTAL -->
                <div class="field-tile-stage">
                    <div class="field-tile-pedestal">
                        <img src="/public/assets/<?= $tileImg ?>" 
                             class="field-tile-img" 
                             alt="<?= htmlspecialchars($bInfo['name']) ?>" 
                             draggable="false">
                        <div class="field-tile-glow"></div>
                    </div>
                    <div class="field-level-emblem">
                        <span class="emblem-lvl-text">NIVEAU</span>
                        <span class="emblem-lvl-number"><?= $lvl ?></span>
                    </div>
                </div>

                <!-- IDENTITÉ ET DÉTAILS DU BÂTIMENT -->
                <div class="field-meta-pane">
                    <div class="field-header-row">
                        <div>
                            <div class="field-type-pill <?= $sectorInfo['sec'] ?>">
                                <span><?= $bInfo['icon'] ?></span>
                                <span><?= htmlspecialchars($sectorInfo['name']) ?></span>
                            </div>
                            <h1 class="field-title"><?= htmlspecialchars($bInfo['name']) ?></h1>
                            <span class="field-subtitle">Emplacement castral #<?= $slot ?> &bull; Cité de <?= htmlspecialchars($planet['name']) ?></span>
                        </div>

                        <?php if ($activeJob): ?>
                            <div class="field-status-badge upgrading">
                                <span class="pulse-dot"></span>
                                <span>Chantier en cours : Niveau <?= $activeJob['target_level'] ?></span>
                            </div>
                        <?php elseif ($lvl > 0): ?>
                            <div class="field-status-badge ready">
                                <span>Statut : Fortifié & Opérationnel</span>
                            </div>
                        <?php else: ?>
                            <div class="field-status-badge ready" style="background:rgba(234, 179, 8, 0.12); color:#b45309; border-color:rgba(234, 179, 8, 0.3);">
                                <span>Statut : Non Bâti</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="field-description">
                        <?= htmlspecialchars($bInfo['description']) ?>
                    </p>

                    <!-- BANDEAU DE RENDEMENT & EFFETS -->
                    <div class="field-quick-stats">
                        <div class="quick-stat-box">
                            <span class="stat-label">Effet Actuel (Niveau <?= $lvl ?>)</span>
                            <span class="stat-value"><?= $statData['cur'] ?></span>
                        </div>
                        <div class="quick-stat-box highlight">
                            <span class="stat-label">Au Niveau <?= $targetLevel ?></span>
                            <span class="stat-value"><?= $statData['next'] ?></span>
                            <span class="stat-gain">(<?= $statData['gain'] ?>)</span>
                        </div>
                    </div>

                    <!-- SHORTCUTS OPÉRATIONNELS DIRECTS -->
                    <?php if ($lvl > 0): ?>
                        <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <?php if ($code === 'barracks'): ?>
                                <a href="/?page=barracks" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    🥋 Ouvrir le Dojo d'Entraînement des Troupes &rarr;
                                </a>
                            <?php elseif ($code === 'shipyard'): ?>
                                <a href="/?page=shipyard" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    🐎 Accéder aux Écuries de Cavalerie & Machines &rarr;
                                </a>
                            <?php elseif ($code === 'research_lab'): ?>
                                <a href="/?page=research" class="btn btn-primary" style="font-size:0.85rem; padding:0.45rem 1rem;">
                                    📜 Consulter l'Académie & Savoirs Féodaux &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SECTION D'AMÉLIORATION & COÛTS -->
        <div class="field-upgrade-grid">
            
            <!-- Colonne Gauche : Coûts pour le Niveau Suivant -->
            <div class="field-card">
                <div class="field-card-header">
                    <h3>🧱 Coûts d'Amélioration &bull; Niveau <?= $targetLevel ?></h3>
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
                    </div>

                    <!-- Durée des Travaux -->
                    <div class="construction-time-banner">
                        <div class="time-label-group">
                            <span class="time-icon">⏱️</span>
                            <div>
                                <strong>Temps de construction pour le Niveau <?= $targetLevel ?> :</strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                    Réduit par le Tenshu (Donjon Castral Niv. <?= $hqLevel ?>) &bull; Maîtres-bâtisseurs féodaux
                                </div>
                            </div>
                        </div>
                        <div class="time-value-display">
                            <?= gmdate('H:i:s', $duration) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Lancement des Travaux / Chantier -->
            <div class="field-card">
                <div class="field-card-header">
                    <h3>🔨 Décret de Construction Castral</h3>
                    <?php if ($isTerran): ?>
                        <span title="Bonus Clan Oda" style="font-size:0.75rem; color:#93c5fd; background:rgba(59,130,246,0.2); padding:0.15rem 0.45rem; border-radius:4px;">Clan Oda (Double Chantier)</span>
                    <?php endif; ?>
                </div>
                <div class="field-card-body" style="display: flex; flex-direction: column; justify-content: space-between;">

                    <?php if ($activeJob): ?>
                        <!-- Chantier en cours sur ce bâtiment -->
                        <div class="field-active-job-box">
                            <div class="job-status-title">
                                <span class="job-spinner">⏳</span>
                                <span>Travaux en cours vers le <strong>Niveau <?= $activeJob['target_level'] ?></strong></span>
                            </div>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin:0.5rem 0 1rem 0;">
                                Vos bâtisseurs et charpentiers travaillent sur cette bâtisse. La forteresse bénéficiera de ses nouvelles capacités dès achèvement.
                            </p>

                            <div class="job-timer-display" data-countdown="<?= $activeJob['finishes_at'] ?>">
                                Calcul du temps restant...
                            </div>

                            <div style="margin-top: 1.25rem;">
                                <button type="button" class="field-btn-cancel" onclick="cancelBuildingBuild(<?= (int)$activeJob['id'] ?>)">
                                    🛑 Interrompre les travaux (80% remboursé)
                                </button>
                            </div>
                        </div>

                    <?php elseif (!$canQueueNewBuilding): ?>
                        <!-- File de construction saturée -->
                        <div class="field-blocked-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🏗️</div>
                            <h4>Chantier Castral Déjà Mobilisé</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                <?php if ($isTerran): ?>
                                    Une autre bâtisse urbaine est déjà en construction dans votre cité. Le Clan Oda permet 1 bâtiment urbain et 1 parcelle rurale en simultané.
                                <?php else: ?>
                                    Vos équipes de bâtisseurs travaillent déjà sur un autre chantier du domaine. Attendez la fin des travaux en cours.
                                <?php endif; ?>
                            </p>
                            <a href="/?page=city" class="field-btn-secondary" style="margin-top:1rem; display:inline-block;">
                                Voir les chantiers de la Cité &rarr;
                            </a>
                        </div>

                    <?php elseif (!$canAfford): ?>
                        <!-- Ressources insuffisantes -->
                        <div class="field-blocked-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">⚠️</div>
                            <h4>Matériaux Insuffisants</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                Vos greniers ne disposent pas encore de la quantité requise de bois, de pierre ou de riz pour ce chantier castral.
                            </p>
                            <?php if ($missingWaitSeconds > 0): ?>
                                <div class="time-to-afford">
                                    ⏳ Matériaux réunis dans environ : <strong><?= gmdate('H:i:s', $missingWaitSeconds) ?></strong>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="field-btn-primary disabled" disabled style="margin-top:1rem; opacity:0.5; cursor:not-allowed;">
                                <?= ($lvl === 0) ? '🔨 Construire au Niveau 1' : '⚡ Améliorer au Niveau ' . $targetLevel ?>
                            </button>
                        </div>

                    <?php else: ?>
                        <!-- Prêt pour lancer les travaux -->
                        <div class="field-ready-box">
                            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">🏯</div>
                            <h4>Ordre de Travaux Prêt</h4>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.4rem;">
                                Les maîtres-artisans ont dressé les plans. Vous pouvez ordonner l'élévation du <strong>Niveau <?= $targetLevel ?></strong>.
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <button type="button" class="field-btn-primary" id="btnLaunchBuildingUpgrade" onclick="launchBuildingUpgrade('<?= $code ?>', <?= $targetLevel ?>)">
                                    <?= ($lvl === 0) ? '🔨 Ériger au Niveau 1' : '⚡ Élever au Niveau ' . $targetLevel ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<script>
async function launchBuildingUpgrade(buildingCode, targetLevel) {
    const btn = document.getElementById('btnLaunchBuildingUpgrade');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Mobilisation des bâtisseurs...';
    }

    const formData = new FormData();
    formData.append('action', 'upgrade');
    formData.append('category', 'building');
    formData.append('target_id', buildingCode);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'ordonner ce chantier.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerText = '⚡ Élever au Niveau ' + targetLevel;
            }
        }
    } catch (e) {
        showModalAlert('Erreur de communication avec le serveur castral.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerText = '⚡ Élever au Niveau ' + targetLevel;
        }
    }
}

async function cancelBuildingBuild(queueId) {
    const confirmed = await showModalConfirm('Voulez-vous vraiment suspendre ces travaux castraux ? 80% des matériaux vous seront restitués.', 'Interruption de Chantier');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('queue_id', queueId);

    try {
        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            showModalAlert(data.error || 'Impossible d\'interrompre les travaux.', 'error');
        }
    } catch (e) {
        showModalAlert('Erreur de transmission.', 'error');
    }
}

// Mise à jour des comptes à rebours
function updateBuildingCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-countdown]').forEach(el => {
        const target = parseInt(el.getAttribute('data-countdown'), 10);
        const diff = target - now;
        if (diff <= 0) {
            el.innerText = 'Travaux achevés ! Actualisation...';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            el.innerText = `⏳ Temps restant : ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }
    });
}
setInterval(updateBuildingCountdowns, 1000);
updateBuildingCountdowns();
</script>
