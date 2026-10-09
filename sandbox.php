<?php
/**
 * Sandbox de Développement Isolée - Architecture Border Layout / Viewport (ExtJS Style)
 * Régions : North, Center, West, South
 * Ne touche à aucun fichier de production du jeu.
 * Accessible directement via http://votreserveur/sandbox.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PlanetEngine.php';
require_once __DIR__ . '/core/BuildingEngine.php';
require_once __DIR__ . '/core/RuralPlotEngine.php';
require_once __DIR__ . '/core/PopulationEngine.php';
require_once __DIR__ . '/core/OasisEngine.php';
require_once __DIR__ . '/config/game_constants.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();
$ruralPlotEngine = new RuralPlotEngine();
$oasisEngine = new OasisEngine();

$planetId = (int)$planet['id'];
$buildings = $planetEngine->getBuildings($planetId);
$hqLevel = $buildings['hq'] ?? 1;
$queue = $buildingEngine->getQueue($planetId);
$plots = $ruralPlotEngine->getEnrichedPlots($planetId, $planet, $hqLevel);
$housingCap = $ruralPlotEngine->getVillageHousingCapacity($planetId);
$maxPop = $housingCap['total_capacity'];
$fields = $planetEngine->getFields($planetId);
$workforce = PopulationEngine::calculateWorkforceSummary($planet, $buildings, $fields, $maxPop);
$prodRates = $planetEngine->calculateProduction($fields, $planetId);

$energyMax = (int)($prodRates['energy_max'] ?? ($planet['energy_max'] ?? 20));
$energyUsed = (int)($prodRates['energy_used'] ?? ($planet['energy_used'] ?? 0));
$energyNet = $energyMax - $energyUsed;

$annexedOases = $oasisEngine->getAnnexedOasesForPlanet($planetId);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet($planetId);
$isTerran = (($user['faction'] ?? 'terran') === 'terran');
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[SANDBOX] Layout 4 Régions (North, West, Center, South) - OpenShogun</title>
    <!-- Polices & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
    <!-- Tabler UI Framework -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <link rel="stylesheet" href="/public/css/style.css">

    <style>
        /* ========================================================
           BORDER LAYOUT / VIEWPORT ISOLÉ (100vw, 100vh, NO BODY SCROLL)
           ======================================================== */
        html, body {
            width: 100vw;
            height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        /* 1. Conteneur Global Viewport */
        .viewport-root {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* 2. RÉGION NORTH (Top Header & Menu) */
        .region-north {
            flex-shrink: 0;
            z-index: 1000;
            background: #ffffff;
            border-bottom: 2px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        /* 3. CONTENEUR CENTRAL (Wrapper West + Center) */
        .viewport-middle-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            min-height: 0; /* Essentiel pour empêcher le flex child de déborder */
        }

        /* 4. RÉGION WEST (Panneau Latéral Gauche / Navigation & Gestion) */
        .region-west {
            width: 380px;
            min-width: 320px;
            max-width: 440px;
            flex-shrink: 0;
            background: #ffffff;
            border-right: 2px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
        }

        /* 5. RÉGION CENTER (Zone de Jeu Principale / Carte Panoramique) */
        .region-center {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: auto;
            background: #0f172a;
            position: relative;
            min-width: 0;
            padding: 1.25rem;
        }

        /* 6. RÉGION SOUTH (Barre de statut / Notifications / Footer technique) */
        .region-south {
            flex-shrink: 0;
            background: #1e293b;
            color: #cbd5e1;
            border-top: 2px solid #334155;
            font-size: 0.8rem;
            z-index: 1000;
        }

        /* Carte 16:9 dans la zone Center */
        .sandbox-map-container {
            position: relative;
            width: 100%;
            max-width: 1400px;
            margin: auto;
            aspect-ratio: 16 / 9;
            background-image: url('/public/assets/shogun_rural_terroir_9plots.jpg');
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.15);
        }

        /* Badges de parcelles */
        .plot-pin {
            position: absolute;
            transform: translate(-50%, -50%);
            cursor: pointer;
            z-index: 20;
            transition: all 0.2s ease;
        }
        .plot-pin:hover {
            transform: translate(-50%, -50%) scale(1.15);
            z-index: 50;
        }
        .plot-pin-inner {
            background: rgba(15, 23, 42, 0.85);
            border: 2px solid #ffffff;
            color: #ffffff;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body>

<div class="viewport-root">

    <!-- ========================================================
         1. RÉGION NORTH (En-tête & Menu)
         ======================================================== -->
    <header class="region-north p-2 px-3">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Brand & Info sandbox -->
            <div class="d-flex align-items-center gap-3">
                <a href="/?page=resources" class="text-decoration-none d-flex align-items-center gap-2">
                    <span class="fs-2 text-danger"><i class="fa-solid fa-torii-gate"></i></span>
                    <span class="fw-bold text-dark font-game">OpenShogun</span>
                </a>
                <span class="badge bg-purple-lt text-purple fw-bold px-2 py-1">
                    <i class="fa-solid fa-flask me-1"></i>SANDBOX 4-RÉGIONS
                </span>
                <span class="text-secondary small d-none d-md-inline">
                    Fief : <strong><?= htmlspecialchars($planet['name']) ?></strong> [<?= (int)$planet['coord_x'] ?>:<?= (int)$planet['coord_y'] ?>]
                </span>
            </div>

            <!-- Mini HUD Ressources direct -->
            <div class="d-flex align-items-center gap-3 font-monospace small">
                <span class="badge bg-light text-dark border px-2 py-1" title="Bois">
                    <i class="fa-solid fa-tree text-success me-1"></i><?= number_format($planet['metal']) ?>
                </span>
                <span class="badge bg-light text-dark border px-2 py-1" title="Pierre">
                    <i class="fa-solid fa-mountain text-secondary me-1"></i><?= number_format($planet['crystal']) ?>
                </span>
                <span class="badge bg-light text-dark border px-2 py-1" title="Riz">
                    <i class="fa-solid fa-wheat-awn text-warning me-1"></i><?= number_format($planet['deuterium']) ?>
                </span>
                <span class="badge bg-light text-dark border px-2 py-1" title="Population">
                    <i class="fa-solid fa-users text-indigo me-1"></i><?= number_format($workforce['assigned_workers']) ?> / <?= number_format($workforce['required_workers']) ?>
                </span>
            </div>

            <!-- Actions de sortie Sandbox -->
            <div class="d-flex align-items-center gap-2">
                <a href="/?page=resources" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour au Jeu
                </a>
            </div>
        </div>
    </header>

    <!-- ========================================================
         CONTENEUR CENTRAL (WEST + CENTER)
         ======================================================== -->
    <div class="viewport-middle-wrapper">

        <!-- ========================================================
             2. RÉGION WEST (Colonne Gauche - Chantiers & Indicateurs)
             ======================================================== -->
        <aside class="region-west p-3">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h4 class="m-0 fw-bold d-flex align-items-center gap-2 text-dark">
                    <i class="fa-solid fa-compass text-primary"></i>
                    <span>Région WEST</span>
                </h4>
                <span class="badge bg-primary-lt">Sidebar</span>
            </div>

            <!-- Chantiers en cours -->
            <div class="card mb-3 shadow-none border">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-dark"><i class="fa-solid fa-helmet-safety text-warning me-1"></i>Chantiers en cours</span>
                    <span class="badge bg-warning-lt"><?= count($queue) ?></span>
                </div>
                <div class="card-body p-2">
                    <?php if (empty($queue)): ?>
                        <div class="text-center text-muted small py-3">
                            <i class="fa-solid fa-hammer d-block fs-3 mb-1 opacity-50"></i>
                            Aucun chantier actif.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($queue as $q): ?>
                                <li class="list-group-item px-1 py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($q['target_id']) ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Niveau <?= (int)$q['target_level'] ?></div>
                                    </div>
                                    <span class="badge bg-warning text-dark font-monospace">
                                        <?= max(0, $q['finishes_at'] - time()) ?>s
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bilan Démographique & Ouvriers -->
            <div class="card mb-3 shadow-none border">
                <div class="card-header bg-light py-2">
                    <span class="fw-bold small text-dark"><i class="fa-solid fa-users text-indigo me-1"></i>Démographie & Ouvriers</span>
                </div>
                <div class="card-body p-2 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Capacité totale :</span>
                        <strong class="text-dark"><?= number_format($maxPop) ?> hab</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Ouvriers requis :</span>
                        <strong class="text-danger"><?= number_format($workforce['required_workers']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Ouvriers affectés :</span>
                        <strong class="text-success"><?= number_format($workforce['assigned_workers']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-secondary">Inactifs / Disponibles :</span>
                        <strong class="text-muted"><?= number_format($workforce['idle_workers']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Rendements Horaires -->
            <div class="card shadow-none border">
                <div class="card-header bg-light py-2">
                    <span class="fw-bold small text-dark"><i class="fa-solid fa-chart-line text-success me-1"></i>Productions Horaires</span>
                </div>
                <div class="card-body p-2 small font-monospace">
                    <div class="d-flex justify-content-between mb-1">
                        <span>🪵 Bois de Cèdre :</span>
                        <strong class="text-success">+<?= number_format($prodRates['metal']) ?>/h</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>🪨 Pierre de Taille :</span>
                        <strong class="text-primary">+<?= number_format($prodRates['crystal']) ?>/h</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>🌾 Riz Koku :</span>
                        <strong class="text-warning">+<?= number_format($prodRates['deuterium']) ?>/h</strong>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ========================================================
             3. RÉGION CENTER (Zone Centrale - Carte & Jeu)
             ======================================================== -->
        <main class="region-center">
            <div class="d-flex justify-content-between align-items-center mb-2 px-2 text-white">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="m-0 fw-bold font-game"><i class="fa-solid fa-map me-2 text-warning"></i>Région CENTER</h3>
                    <span class="badge bg-dark border border-secondary text-white-50">Carte Panoramique 16:9</span>
                </div>
                <span class="text-white-50 small">Confinement strict sans défilement de page</span>
            </div>

            <!-- Conteneur Carte 16:9 avec les 9 parcelles -->
            <div class="sandbox-map-container my-auto">
                <?php foreach ($plots as $type => $p): 
                    $meta = RuralPlotEngine::STRUCTURES[$type] ?? null;
                    if (!$meta) continue;
                    $posX = $p['pos_x'];
                    $posY = $p['pos_y'];
                ?>
                    <div class="plot-pin" style="left: <?= $posX ?>%; top: <?= $posY ?>%;" title="<?= htmlspecialchars($meta['name']) ?> (Niveau <?= $p['level'] ?>)">
                        <div class="plot-pin-inner">
                            <i class="<?= $meta['icon'] ?> text-warning"></i>
                            <span>Niv.<?= $p['level'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

    </div>

    <!-- ========================================================
         4. RÉGION SOUTH (Barre de statut / Footer technique)
         ======================================================== -->
    <footer class="region-south py-1 px-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary-lt text-secondary">Région SOUTH</span>
                <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Environnement Isolé : <code>sandbox.php</code></span>
                <span class="d-none d-md-inline text-muted">&bull;</span>
                <span class="d-none d-md-inline text-muted">Layout Viewport 100vw / 100vh sans body scroll</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span>Serveur Speed : <strong>x5</strong></span>
                <span class="text-muted">&bull;</span>
                <span>OpenShogun Studio Lab</span>
            </div>
        </div>
    </footer>

</div>

<!-- Scripts Tabler & Bootstrap -->
<script src="/public/js/tabler/tabler.min.js"></script>
</body>
</html>

