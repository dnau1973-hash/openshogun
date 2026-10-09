<?php
/**
 * Sandbox de Développement Isolée - Architecture Border Layout / Viewport (ExtJS Style)
 * Régions : North, West, Center, East, South
 * West : Accordion Inverted with plus icon officiel Tabler.io
 * - L'accordéon s'étire sur toute la hauteur restante (height: 100%, flex: 1).
 * - Le premier élément ou tout élément ouvert s'adapte, et si son contenu dépasse la place disponible, un scroll interne apparaît.
 * Ne touche à aucun fichier de production du jeu.
 * Accessible directement via http://votreserveur/sandbox.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/PlanetEngine.php';
require_once __DIR__ . '/core/BuildingEngine.php';
require_once __DIR__ . '/core/RuralPlotEngine.php';
require_once __DIR__ . '/core/PopulationEngine.php';
require_once __DIR__ . '/core/OasisEngine.php';
require_once __DIR__ . '/core/SlotPositionEngine.php';
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

// Cité Castrale (Slots 19 à 34)
$citySlots = $planetEngine->getCitySlotMap($planetId);
$buildingSectors = [
    'hq' => 'hq',
    'shipyard' => 'military',
    'barracks' => 'military',
    'radar' => 'military',
    'wall' => 'military',
    'research_lab' => 'science',
    'embassy' => 'science',
    'storage' => 'logistics',
    'tank' => 'logistics',
    'quantum_vault' => 'logistics',
    'market' => 'logistics',
    'sawmill' => 'logistics',
    'stonemason' => 'logistics',
    'grain_mill' => 'logistics',
    'blacksmith' => 'military',
    'teahouse' => 'science',
    'tournament_square' => 'military',
    'free_plot' => 'logistics',
];
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[SANDBOX] Layout 5 Régions (Accordion Tabler Inverted Plus) - OpenShogun</title>
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

        /* 3. CONTENEUR CENTRAL (Wrapper West + Center + East) */
        .viewport-middle-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: row;
            overflow: hidden;
            min-height: 0; /* Empêche le flex child de déborder */
            position: relative;
        }

        /* 4. RÉGION WEST (Panneau Latéral Gauche / Accordion Pleine Hauteur) */
        .region-west {
            width: 380px;
            min-width: 380px;
            flex-shrink: 0;
            background: #ffffff;
            border-right: 2px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Défilement géré à l'intérieur de l'accordéon */
            z-index: 100;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
        }

        .region-west.is-collapsed {
            margin-left: -380px;
            opacity: 0;
            pointer-events: none;
        }

        /* En-tête de la région West */
        .region-west-header {
            flex-shrink: 0;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        /* Conteneur de l'accordéon occupant toute la hauteur restante */
        .west-accordion-wrapper {
            flex-grow: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 0.5rem;
        }

        /* ========================================================
           TABLER.IO ACCORDION INVERTED WITH PLUS ICON OFFICIEL
           ======================================================== */
        .accordion-inverted .accordion-button::after {
            display: none !important; /* Neutralise le chevron standard droit */
        }

        .accordion-button-toggle-plus {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            margin-right: 0.75rem;
            flex-shrink: 0;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            transition: transform 0.25s ease, background 0.2s ease, color 0.2s ease;
        }

        .accordion-button-toggle-plus svg {
            width: 14px;
            height: 14px;
            stroke-width: 2.2;
            transition: transform 0.25s ease;
        }

        /* Rotation quart de tour (45°) au dépliage : le plus (+) devient une croix/fermeture */
        .accordion-button:not(.collapsed) .accordion-button-toggle-plus {
            transform: rotate(45deg);
            background: #e2e8f0;
            color: #0f172a;
            border-color: #94a3b8;
        }

        /* Accordéon Pleine Hauteur avec Scroll Automatique Interne */
        #westAccordion {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            gap: 0.4rem;
        }

        #westAccordion .accordion-item {
            border: 1px solid #e2e8f0;
            border-radius: 6px !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: flex-grow 0.25s ease;
        }

        /* L'élément ouvert s'étire pour occuper tout l'espace restant */
        #westAccordion .accordion-item:has(.accordion-collapse.show) {
            flex-grow: 1;
            min-height: 0;
        }

        #westAccordion .accordion-header {
            flex-shrink: 0;
        }

        #westAccordion .accordion-button {
            padding: 0.65rem 0.85rem;
            font-size: 0.88rem;
            font-weight: 600;
            background: #ffffff;
            box-shadow: none !important;
            border: none;
        }

        #westAccordion .accordion-button:not(.collapsed) {
            background-color: #f8fafc;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
        }

        /* La zone de contenu de l'accordéon prend tout l'espace et dispose de son propre scroll si ça déborde */
        #westAccordion .accordion-collapse {
            min-height: 0;
            overflow: hidden;
        }

        #westAccordion .accordion-collapse.show {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #westAccordion .accordion-body {
            flex-grow: 1;
            min-height: 0;
            overflow-y: auto; /* SCROLL AUTOMATIQUE SI SUPÉRIEUR À LA PLACE DISPONIBLE */
            overflow-x: hidden;
            padding: 0.75rem 0.85rem;
            background: #ffffff;
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
            transition: all 0.25s ease;
        }

        /* 6. RÉGION EAST (Panneau Latéral Droit / Statistiques & Oasis) */
        .region-east {
            width: 340px;
            min-width: 340px;
            flex-shrink: 0;
            background: #ffffff;
            border-left: 2px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            transition: margin-right 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
        }

        .region-east.is-collapsed {
            margin-right: -340px;
            opacity: 0;
            pointer-events: none;
        }

        /* Boutons Flottants pour Déplier / Réduire (ExtJS Collapsible Handles) */
        .collapse-btn-handle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 500;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            color: #475569;
            width: 24px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: background 0.15s, color 0.15s;
        }
        .collapse-btn-handle:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #94a3b8;
        }
        .collapse-btn-handle-west {
            left: 0;
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .collapse-btn-handle-east {
            right: 0;
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        /* 7. RÉGION SOUTH (Barre de statut / Notifications / Footer technique) */
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

        /* Sélecteur de vues féodales */
        .sandbox-nav-switcher .btn {
            font-size: 0.82rem;
            padding: 0.35rem 0.85rem;
            transition: all 0.2s ease;
        }
        .sandbox-nav-switcher .btn.active {
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);
        }

        /* Surface Cité Castrale 16:9 */
        .sandbox-city-surface {
            position: relative;
            width: 100%;
            max-width: 1400px;
            margin: auto;
            aspect-ratio: 16 / 9;
            background-image: url('/public/assets/shogun_castle_city_bg.jpg');
            background-size: cover;
            background-position: center;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.15);
        }

        .sandbox-city-surface .rts-hotspot {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            transition: transform 0.15s ease, background 0.2s ease;
        }
        .sandbox-city-surface .rts-hotspot:hover {
            transform: scale(1.06);
            background: rgba(255, 255, 255, 0.15);
            z-index: 99 !important;
        }

        .sandbox-city-surface .rts-hotspot .rts-level-bubble {
            top: 6%;
            right: 8%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }

        /* Modal Carte */
        @keyframes mapModalIn {
            from { opacity:0; transform: scale(0.96) translateY(6px); }
            to   { opacity:1; transform: scale(1)   translateY(0); }
        }
    </style>
    <?= SlotPositionEngine::renderCss('city') ?>
</head>
<body>

<div class="viewport-root">

    <!-- ========================================================
         1. RÉGION NORTH (En-tête & Menu)
         ======================================================== -->
    <header class="region-north p-2 px-3">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <!-- Brand & Info sandbox -->
            <div class="d-flex align-items-center gap-2">
                <a href="/?page=resources" class="text-decoration-none d-flex align-items-center gap-2">
                    <span class="fs-2 text-danger"><i class="fa-solid fa-torii-gate"></i></span>
                    <span class="fw-bold text-dark font-game">OpenShogun</span>
                </a>
                <span class="badge bg-purple-lt text-purple fw-bold px-2 py-1">
                    <i class="fa-solid fa-layer-group me-1"></i>5-RÉGIONS
                </span>
                
                <!-- Toggles pour afficher/masquer West et East -->
                <div class="btn-group btn-group-sm ms-2">
                    <button type="button" class="btn btn-outline-secondary" id="toggleWestBtn" onclick="toggleRegion('west')" title="Replier/Déplier Région West">
                        <i class="fa-solid fa-bars-staggered me-1"></i>West
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="toggleEastBtn" onclick="toggleRegion('east')" title="Replier/Déplier Région East">
                        <i class="fa-solid fa-table-columns me-1"></i>East
                    </button>
                </div>
            </div>

            <!-- SÉLECTEUR DE VUES FÉODALES (Terroir Féodal, Cité Castrale, La Carte) -->
            <div class="btn-group btn-group-sm sandbox-nav-switcher shadow-sm mx-auto" role="group" aria-label="Sélecteur de Vue Féodale">
                <button type="button" class="btn btn-primary active" id="btnViewTerroir" onclick="switchSandboxView('terroir')" title="Passer au Terroir Féodal (9 Parcelles)">
                    <i class="fa-solid fa-wheat-awn text-warning me-1"></i>
                    <span class="fw-bold">Terroir Féodal</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnViewCity" onclick="switchSandboxView('city')" title="Passer à la Cité Castrale (16 Bâtiments &amp; Tenshu)">
                    <i class="fa-solid fa-chess-rook text-primary me-1"></i>
                    <span class="fw-bold">Cité Castrale</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnViewMap" onclick="switchSandboxView('map')" title="Passer à la Carte des Provinces &amp; Fiefs">
                    <i class="fa-solid fa-map-location-dot text-info me-1"></i>
                    <span class="fw-bold">La Carte</span>
                </button>
            </div>

            <!-- Mini HUD Ressources direct -->
            <div class="d-flex align-items-center gap-2 font-monospace small">
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
                    <i class="fa-solid fa-arrow-left me-1"></i>Quitter
                </a>
            </div>
        </div>
    </header>

    <!-- ========================================================
         CONTENEUR CENTRAL (WEST + CENTER + EAST)
         ======================================================== -->
    <div class="viewport-middle-wrapper">

        <!-- ========================================================
             2. RÉGION WEST (Colonne Gauche - Accordion Inverted With Plus Icon)
             ======================================================== -->
        <aside class="region-west" id="regionWest">
            <div class="region-west-header d-flex align-items-center justify-content-between">
                <h4 class="m-0 fw-bold d-flex align-items-center gap-2 text-dark fs-3">
                    <i class="fa-solid fa-compass text-primary"></i>
                    <span>Région WEST</span>
                </h4>
                <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-primary-lt">Tabler Inverted</span>
                    <button type="button" class="btn btn-sm btn-light border-0 p-1" onclick="toggleRegion('west')" title="Réduire Région West">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                </div>
            </div>

            <!-- WRAPPER PLEINE HAUTEUR DE L'ACCORDÉON -->
            <div class="west-accordion-wrapper">

                <!-- ACCORDION INVERTED TABLER.IO -->
                <div class="accordion accordion-inverted" id="westAccordion">

                    <!-- 1. Didacticiel du Daimyō -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingQuest">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuest" aria-expanded="true" aria-controls="collapseQuest">
                                <span class="accordion-button-toggle-plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <i class="fa-solid fa-scroll text-warning me-2"></i>
                                <span class="flex-grow-1">Didacticiel du Daimyō</span>
                                <span class="badge bg-warning-lt text-warning ms-auto">Étape 2/12</span>
                            </button>
                        </h2>
                        <div id="collapseQuest" class="accordion-collapse collapse show" aria-labelledby="headingQuest" data-bs-parent="#westAccordion">
                            <div class="accordion-body small">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <div class="avatar avatar-sm rounded-circle bg-warning-lt text-warning flex-shrink-0">
                                        <i class="fa-solid fa-user-ninja"></i>
                                    </div>
                                    <div>
                                        <strong class="text-dark d-block">Maître Katsumoto :</strong>
                                        <span class="text-muted" style="font-size: 0.78rem;">« Seigneur, consolidez vos réserves en élevant votre Carrière de Granit au niveau 1. »</span>
                                    </div>
                                </div>
                                <div class="progress progress-sm mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 50%;"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted mb-3" style="font-size: 0.72rem;">
                                    <span>Progression : <strong>1 / 2</strong></span>
                                    <span class="badge bg-success-lt text-success">+150 Bois &bull; +150 Riz</span>
                                </div>

                                <!-- Contenu dense pour tester le comportement de scroll interne -->
                                <div class="p-2 bg-light rounded border mb-2">
                                    <strong class="d-block text-secondary text-uppercase" style="font-size:0.68rem;">Objectifs Féodaux Débloqués :</strong>
                                    <ul class="list-unstyled m-0 text-muted" style="font-size:0.75rem;">
                                        <li><i class="fa-solid fa-check text-success me-1"></i> 1. Fonder le premier arpent sylvicole</li>
                                        <li><i class="fa-solid fa-circle-dot text-warning me-1"></i> 2. Extraire la pierre de granit</li>
                                        <li><i class="fa-regular fa-circle text-muted me-1"></i> 3. Bâtir le pavillon de thé</li>
                                        <li><i class="fa-regular fa-circle text-muted me-1"></i> 4. Recruter 10 fantassins Ashigaru</li>
                                        <li><i class="fa-regular fa-circle text-muted me-1"></i> 5. Annexer une première oasis fluviale</li>
                                    </ul>
                                </div>
                                <button type="button" class="btn btn-sm btn-warning w-100 fw-bold">
                                    <i class="fa-solid fa-gift me-1"></i>Réclamer les récompenses
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Chantiers en Cours -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingQueue">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseQueue" aria-expanded="false" aria-controls="collapseQueue">
                                <span class="accordion-button-toggle-plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <i class="fa-solid fa-helmet-safety text-primary me-2"></i>
                                <span class="flex-grow-1">Chantiers en Cours</span>
                                <span class="badge bg-primary-lt text-primary ms-auto"><?= count($queue) ?> actif(s)</span>
                            </button>
                        </h2>
                        <div id="collapseQueue" class="accordion-collapse collapse" aria-labelledby="headingQueue" data-bs-parent="#westAccordion">
                            <div class="accordion-body p-2 small">
                                <?php if (empty($queue)): ?>
                                    <div class="text-center text-muted py-3" style="font-size:0.8rem;">
                                        <i class="fa-solid fa-hammer d-block fs-3 mb-1 opacity-50"></i>
                                        Aucun chantier actif sur ce fief.
                                    </div>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush small">
                                        <?php foreach ($queue as $q): ?>
                                            <li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($q['target_id']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">Élévation Niveau <?= (int)$q['target_level'] ?></div>
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
                    </div>

                    <!-- 3. Récoltes, Stocks & Oasis -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingHarvest">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHarvest" aria-expanded="false" aria-controls="collapseHarvest">
                                <span class="accordion-button-toggle-plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <i class="fa-solid fa-wheat-awn text-success me-2"></i>
                                <span class="flex-grow-1">Récoltes, Stocks &amp; Oasis</span>
                                <span class="badge bg-success-lt text-success ms-auto">+<?= number_format($prodRates['metal'] + $prodRates['crystal'] + $prodRates['deuterium']) ?>/h</span>
                            </button>
                        </h2>
                        <div id="collapseHarvest" class="accordion-collapse collapse" aria-labelledby="headingHarvest" data-bs-parent="#westAccordion">
                            <div class="accordion-body p-2 small font-monospace">
                                <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                    <span class="text-secondary"><i class="fa-solid fa-tree text-success me-1"></i>Bois de Cèdre :</span>
                                    <strong class="text-success">+<?= number_format($prodRates['metal']) ?>/h</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                    <span class="text-secondary"><i class="fa-solid fa-mountain text-secondary me-1"></i>Pierre de Taille :</span>
                                    <strong class="text-primary">+<?= number_format($prodRates['crystal']) ?>/h</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                    <span class="text-secondary"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Riz Impérial :</span>
                                    <strong class="text-warning">+<?= number_format($prodRates['deuterium']) ?>/h</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1 pb-1 border-bottom">
                                    <span class="text-secondary"><i class="fa-solid fa-leaf text-teal me-1"></i>Oasis Annexées :</span>
                                    <strong class="text-teal"><?= count($annexedOases) ?> oasis</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary"><i class="fa-solid fa-box text-purple me-1"></i>Magasins Kura :</span>
                                    <strong class="text-purple"><?= number_format($planet['metal_max'] ?? 15000) ?> max</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Garnison du Domaine -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingGarrison">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGarrison" aria-expanded="false" aria-controls="collapseGarrison">
                                <span class="accordion-button-toggle-plus">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <i class="fa-solid fa-shield-halved text-danger me-2"></i>
                                <span class="flex-grow-1">Garnison du Domaine</span>
                                <span class="badge bg-danger-lt text-danger ms-auto">45 guerriers</span>
                            </button>
                        </h2>
                        <div id="collapseGarrison" class="accordion-collapse collapse" aria-labelledby="headingGarrison" data-bs-parent="#westAccordion">
                            <div class="accordion-body p-2 small">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex justify-content-between align-items-center p-1 bg-light rounded">
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-person-rifle text-danger"></i> Ashigaru Yari
                                        </span>
                                        <span class="badge bg-dark font-monospace">30</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-1 bg-light rounded">
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-bow-arrow text-warning"></i> Archers Yumi
                                        </span>
                                        <span class="badge bg-dark font-monospace">12</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center p-1 bg-light rounded">
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="fa-solid fa-horse text-primary"></i> Cavalerie Samurai
                                        </span>
                                        <span class="badge bg-dark font-monospace">3</span>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center text-muted" style="font-size:0.75rem;">
                                    <span>Défense fortifiée : <strong>1 450 pts</strong></span>
                                    <a href="/?page=barracks" class="text-primary text-decoration-none">Dojo &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /#westAccordion -->

            </div><!-- /.west-accordion-wrapper -->
        </aside>

        <!-- Poignée de réouverture flottante West (quand replié) -->
        <button type="button" class="collapse-btn-handle collapse-btn-handle-west d-none" id="handleOpenWest" onclick="toggleRegion('west')" title="Déplier Région West">
            <i class="fa-solid fa-chevron-right fs-4"></i>
        </button>

        <!-- ========================================================
             3. RÉGION CENTER (Zone Centrale - Terroir / Cité / Carte)
             ======================================================== -->
        <main class="region-center" id="regionCenter">

            <!-- ----------------------------------------------------
                 VUE 1 : TERROIR FÉODAL (9 Parcelles Stratégiques)
                 ---------------------------------------------------- -->
            <div id="viewTerroir" class="sandbox-view w-100 d-flex flex-column my-auto">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2 text-white">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="m-0 fw-bold font-game"><i class="fa-solid fa-wheat-awn me-2 text-warning"></i>Terroir Féodal</h3>
                        <span class="badge bg-dark border border-secondary text-white-50">Carte Panoramique 16:9</span>
                        <span class="badge bg-warning-lt text-warning">9 Parcelles de Terroir</span>
                    </div>
                    <div class="text-white-50 small d-flex align-items-center gap-3">
                        <span><i class="fa-solid fa-compress me-1 text-info"></i>Flex Grow dynamique</span>
                    </div>
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
            </div>

            <!-- ----------------------------------------------------
                 VUE 2 : CITÉ CASTRALE (16 Bâtiments & Tenshu)
                 ---------------------------------------------------- -->
            <div id="viewCity" class="sandbox-view w-100 d-none flex-column my-auto">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2 text-white">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="m-0 fw-bold font-game"><i class="fa-solid fa-chess-rook me-2 text-primary"></i>Cité Castrale &amp; Village Féodal</h3>
                        <span class="badge bg-dark border border-secondary text-white-50">Secteurs Urbains 16:9</span>
                        <span class="badge bg-primary-lt text-primary">16 Bâtiments Castraux &amp; Tenshu</span>
                    </div>
                    <div class="text-white-50 small d-flex align-items-center gap-3">
                        <span><i class="fa-solid fa-landmark me-1 text-warning"></i>Tenshu &amp; Dojos</span>
                    </div>
                </div>

                <!-- Conteneur Cité Castrale 16:9 avec les 16 Bâtiments -->
                <div class="sandbox-city-surface my-auto">
                    <?php foreach ($citySlots as $slot => $slotData):
                        $code = $slotData['code'];
                        if ($code === 'free_plot' && isset(CITY_SLOT_LAYOUT[$slot])) {
                            $code = CITY_SLOT_LAYOUT[$slot];
                        }
                        $lvl = (int)($slotData['level'] ?? 0);
                        if ($lvl === 0 && isset($buildings[$code])) {
                            $lvl = (int)$buildings[$code];
                        }
                        $bInfo = BUILDINGS[$code] ?? null;
                        if (!$bInfo) continue;
                        $sector = $buildingSectors[$code] ?? 'logistics';
                    ?>
                        <div class="rts-hotspot sector-<?= $sector ?> hotspot-city-slot-<?= $slot ?>"
                             title="<?= htmlspecialchars($bInfo['name']) ?> (Niveau <?= $lvl ?>)"
                             onclick="showCityBuildingToast('<?= htmlspecialchars(addslashes($bInfo['name'])) ?>', <?= $lvl ?>, '<?= htmlspecialchars(addslashes($bInfo['description'] ?? '')) ?>')">
                            <div class="rts-level-bubble <?= ($code === 'hq') ? 'rts-tenshu-bubble' : '' ?> <?= $lvl === 0 ? 'level-zero' : '' ?>">
                                <?= $lvl > 0 ? $lvl : '+' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ----------------------------------------------------
                 VUE 3 : LA CARTE DES PROVINCES (Navigation & Fiefs)
                 ---------------------------------------------------- -->
            <div id="viewMap" class="sandbox-view w-100 d-none flex-column h-100">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2 text-white flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="m-0 fw-bold font-game"><i class="fa-solid fa-map-location-dot me-2 text-info"></i>Carte des Provinces &amp; Fiefs</h3>
                        <span class="badge bg-dark border border-secondary text-white-50 font-monospace">[<?= (int)$planet['coord_x'] ?> : <?= (int)$planet['coord_y'] ?>] Fief d'attache</span>
                        <span class="badge bg-info-lt text-info d-none d-md-inline"><i class="fa-solid fa-hand me-1"></i>Glissez la carte (Drag &amp; Drop)</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(-16, 16)" title="Nord-Ouest [- / +]">N-O</button>
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(16, 16)" title="Nord-Est [+ / +]">N-E</button>
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(-16, -16)" title="Sud-Ouest [- / -]">S-O</button>
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(16, -16)" title="Sud-Est [+ / -]">S-E</button>
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(0, 0)" title="Centre Impérial [0 : 0]"><i class="fa-solid fa-torii-gate me-1"></i>Centre</button>
                        <button type="button" class="btn btn-sm btn-primary py-1 px-2" onclick="if(galaxyMap) galaxyMap.moveTo(<?= (int)$planet['coord_x'] ?>, <?= (int)$planet['coord_y'] ?>)" title="Mon Fief"><i class="fa-solid fa-house-chimney me-1"></i>Mon Fief</button>
                    </div>
                </div>

                <!-- Légende rapide des terrains -->
                <div class="bg-dark bg-opacity-75 border border-secondary border-opacity-50 rounded px-2 py-1 mb-2 d-flex align-items-center justify-content-between text-white-50 small flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="fw-bold text-white"><i class="fa-solid fa-map me-1"></i>Terroirs :</span>
                        <span><img src="/public/assets/map/tile_plains.jpg?v=2" style="width:14px; height:14px; border-radius:2px;" alt=""> Plaines</span>
                        <span><img src="/public/assets/map/tile_forest.jpg?v=2" style="width:14px; height:14px; border-radius:2px;" alt=""> Forêt de Cèdres</span>
                        <span><img src="/public/assets/map/tile_mountain.jpg?v=2" style="width:14px; height:14px; border-radius:2px;" alt=""> Montagnes</span>
                        <span><img src="/public/assets/map/tile_lake.jpg?v=2" style="width:14px; height:14px; border-radius:2px;" alt=""> Lacs</span>
                        <span><img src="/public/assets/map/tile_village.jpg?v=2" style="width:14px; height:14px; border-radius:2px;" alt=""> Fief</span>
                        <span><span class="badge bg-success-lt" style="font-size:0.65rem;">+25%</span> Oasis</span>
                    </div>
                    <div class="text-white-50 font-monospace small">
                        Zoom : +/- &bull; Flèches clavier pour naviguer
                    </div>
                </div>

                <!-- Conteneur Carte interactif -->
                <div class="galaxy-map-wrapper map-fullwidth-wrapper flex-grow-1" id="galaxyMapContainer" style="width: 100%; height: 100%; min-height: 420px; border-radius: 8px; overflow: hidden; position: relative;"></div>
            </div>

        </main>

        <!-- Poignée de réouverture flottante East (quand replié) -->
        <button type="button" class="collapse-btn-handle collapse-btn-handle-east d-none" id="handleOpenEast" onclick="toggleRegion('east')" title="Déplier Région East">
            <i class="fa-solid fa-chevron-left fs-4"></i>
        </button>

        <!-- ========================================================
             4. RÉGION EAST (Colonne Droite - Économie & Oasis)
             ======================================================== -->
        <aside class="region-east p-3" id="regionEast">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <button type="button" class="btn btn-sm btn-light border-0 p-1" onclick="toggleRegion('east')" title="Réduire Région East">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
                <h4 class="m-0 fw-bold d-flex align-items-center gap-2 text-dark">
                    <span>Région EAST</span>
                    <i class="fa-solid fa-chart-pie text-success"></i>
                </h4>
            </div>

            <!-- Bilan Démographique & Ouvriers -->
            <div class="card mb-3 shadow-none border">
                <div class="card-header bg-light py-2">
                    <span class="fw-bold small text-dark"><i class="fa-solid fa-users text-indigo me-1"></i>Démographie &amp; Ouvriers</span>
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
                        <span class="text-secondary">Inactifs / Libres :</span>
                        <strong class="text-muted"><?= number_format($workforce['idle_workers']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Rendements Horaires -->
            <div class="card mb-3 shadow-none border">
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
                    <div class="d-flex justify-content-between mb-1">
                        <span>🌾 Riz Koku :</span>
                        <strong class="text-warning">+<?= number_format($prodRates['deuterium']) ?>/h</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-1 border-top">
                        <span>⚡ Énergie Fief :</span>
                        <strong class="<?= $energyNet >= 0 ? 'text-cyan' : 'text-danger' ?>"><?= $energyNet >= 0 ? '+' : '' ?><?= $energyNet ?></strong>
                    </div>
                </div>
            </div>

            <!-- Oasis Annexées -->
            <div class="card shadow-none border">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-dark"><i class="fa-solid fa-leaf text-teal me-1"></i>Oasis Annexées</span>
                    <span class="badge bg-teal-lt"><?= count($annexedOases) ?></span>
                </div>
                <div class="card-body p-2 small">
                    <?php if (empty($annexedOases)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fa-solid fa-tree d-block fs-3 mb-1 opacity-50"></i>
                            Aucune oasis annexée.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($annexedOases as $oa): ?>
                                <li class="list-group-item px-1 py-1 d-flex justify-content-between align-items-center">
                                    <span>[<?= (int)$oa['coord_x'] ?>:<?= (int)$oa['coord_y'] ?>] <?= htmlspecialchars($oa['name']) ?></span>
                                    <span class="badge bg-teal-lt">+25%</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

    </div>

    <!-- ========================================================
         5. RÉGION SOUTH (Barre de statut / Footer technique)
         ======================================================== -->
    <footer class="region-south py-1 px-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary-lt text-secondary">Région SOUTH</span>
                <span><i class="fa-solid fa-shield-halved text-success me-1"></i>Environnement Isolé : <code>sandbox.php</code></span>
                <span class="d-none d-md-inline text-muted">&bull;</span>
                <span class="d-none d-md-inline text-muted">Tabler Inverted Accordion + Plus icon (100% height &amp; internal scroll)</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span>Serveur Speed : <strong>x5</strong></span>
                <span class="text-muted">&bull;</span>
                <span>OpenShogun Studio Lab</span>
            </div>
        </div>
    </footer>

</div>

<!-- Modal d'informations pour la Carte -->
<div id="mapTileModal" style="
    display: none;
    position: fixed; inset: 0; z-index: 9000;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
" onclick="if(event.target===this) closeMapModal();">
    <div class="card shadow-lg" style="
        background: #ffffff;
        border: 1px solid var(--tblr-border-color, #e6e7e9);
        border-radius: 12px;
        width: 100%; max-width: 540px;
        max-height: 85vh; overflow-y: auto;
        position: relative;
        animation: mapModalIn 0.2s ease;
        color: #1e293b;
    ">
        <div class="card-header d-flex justify-content-between align-items-center py-2 px-3 border-bottom bg-white">
            <div class="d-flex align-items-center gap-2">
                <h4 id="mapModalTitle" class="card-title m-0 fw-bold">Informations</h4>
                <span id="mapModalCoords" class="badge bg-secondary text-white font-monospace"></span>
            </div>
            <button type="button" class="btn-close ms-2" onclick="closeMapModal()" aria-label="Fermer"></button>
        </div>
        <div id="mapModalBody" class="card-body p-3"></div>
    </div>
</div>

<!-- Scripts Tabler & Logique Sandbox -->
<script src="/public/js/tabler/tabler.min.js"></script>
<script src="/public/js/galaxy_map.js"></script>
<script>
let galaxyMap = null;

// ========================================================
// BASCULE DES VUES (Terroir Féodal, Cité Castrale, La Carte)
// ========================================================
function switchSandboxView(viewName) {
    const views = {
        'terroir': { btn: document.getElementById('btnViewTerroir'), el: document.getElementById('viewTerroir') },
        'city':    { btn: document.getElementById('btnViewCity'),    el: document.getElementById('viewCity') },
        'map':     { btn: document.getElementById('btnViewMap'),     el: document.getElementById('viewMap') }
    };

    if (!views[viewName]) return;

    // Mise à jour de l'état des boutons
    Object.keys(views).forEach(k => {
        const item = views[k];
        if (!item.btn || !item.el) return;
        
        if (k === viewName) {
            item.btn.classList.add('btn-primary', 'active');
            item.btn.classList.remove('btn-outline-secondary');
            item.el.classList.remove('d-none');
            item.el.classList.add('d-flex');
        } else {
            item.btn.classList.remove('btn-primary', 'active');
            item.btn.classList.add('btn-outline-secondary');
            item.el.classList.add('d-none');
            item.el.classList.remove('d-flex');
        }
    });

    // Initialisation ou redimensionnement dynamique de la Carte
    if (viewName === 'map') {
        setTimeout(() => {
            if (!galaxyMap) {
                galaxyMap = new GalaxyMapController('galaxyMapContainer', {
                    initialX: <?= (int)$planet['coord_x'] ?>,
                    initialY: <?= (int)$planet['coord_y'] ?>,
                    playerX: <?= (int)$planet['coord_x'] ?>,
                    playerY: <?= (int)$planet['coord_y'] ?>,
                    userId: <?= (int)$user['id'] ?>
                });
            } else {
                galaxyMap.renderGrid();
            }
        }, 50);
    }

    localStorage.setItem('sandbox_active_view', viewName);
    history.replaceState(null, null, '#view=' + viewName);
}

// Clic interactif sur un bâtiment de la Cité Castrale
function showCityBuildingToast(name, level, desc) {
    alert(`🏯 ${name} (Niveau ${level})\n\n${desc || 'Bâtiment traditionnel du domaine castral.'}`);
}

// Modal Carte
function openMapModal() {
    const modal = document.getElementById('mapTileModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeMapModal() {
    const modal = document.getElementById('mapTileModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMapModal();
});

window.selectPlanetTile = function(planetData) {
    const title = document.getElementById('mapModalTitle');
    const coords = document.getElementById('mapModalCoords');
    const body = document.getElementById('mapModalBody');
    if (!title || !coords || !body) return;

    coords.innerText = `[${planetData.coord_x} : ${planetData.coord_y}]`;

    if (planetData.is_oasis) {
        title.innerHTML = `<i class="fa-solid fa-leaf text-success me-1"></i> ${planetData.oasis_name || 'Oasis Naturelle'}`;
        body.innerHTML = `
            <div class="alert alert-success py-2 mb-2">
                <i class="fa-solid fa-sparkles me-1"></i> Terres fertiles regorgeant de ressources.
            </div>
            <p class="small text-muted mb-0">Coordonnées : <strong>[${planetData.coord_x} : ${planetData.coord_y}]</strong></p>
        `;
    } else if (planetData.name) {
        const isOwn = (parseInt(planetData.user_id, 10) === <?= (int)$user['id'] ?>);
        title.innerHTML = `<i class="fa-solid fa-chess-rook text-primary me-1"></i> ${planetData.name}`;
        body.innerHTML = `
            <div class="card p-2 bg-light mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-secondary small">Daimyō Souverain :</span>
                    <strong class="text-dark">${planetData.username || 'Inconnu'}</strong>
                </div>
            </div>
            ${isOwn ? '<span class="badge bg-success-lt font-weight-bold py-2 px-3"><i class="fa-solid fa-check me-1"></i>Votre propre domaine</span>' : ''}
        `;
    } else {
        title.innerHTML = `<i class="fa-solid fa-mountain-sun text-secondary me-1"></i> Province Sauvage`;
        body.innerHTML = `
            <p class="small text-muted mb-0">Terre vierge inoccupée. Coordonnées : <strong>[${planetData.coord_x} : ${planetData.coord_y}]</strong></p>
        `;
    }
    openMapModal();
};

// ========================================================
// LOGIQUE DE COLLAPSE WEST / EAST
// ========================================================
function toggleRegion(region) {
    if (region === 'west') {
        const west = document.getElementById('regionWest');
        const handle = document.getElementById('handleOpenWest');
        const btn = document.getElementById('toggleWestBtn');
        const isCollapsed = west.classList.toggle('is-collapsed');
        
        if (handle) handle.classList.toggle('d-none', !isCollapsed);
        if (btn) {
            btn.classList.toggle('active', !isCollapsed);
            btn.classList.toggle('btn-secondary', !isCollapsed);
            btn.classList.toggle('btn-outline-secondary', isCollapsed);
        }
        localStorage.setItem('sandbox_region_west_collapsed', isCollapsed ? '1' : '0');
    } else if (region === 'east') {
        const east = document.getElementById('regionEast');
        const handle = document.getElementById('handleOpenEast');
        const btn = document.getElementById('toggleEastBtn');
        const isCollapsed = east.classList.toggle('is-collapsed');
        
        if (handle) handle.classList.toggle('d-none', !isCollapsed);
        if (btn) {
            btn.classList.toggle('active', !isCollapsed);
            btn.classList.toggle('btn-secondary', !isCollapsed);
            btn.classList.toggle('btn-outline-secondary', isCollapsed);
        }
        localStorage.setItem('sandbox_region_east_collapsed', isCollapsed ? '1' : '0');
    }
}

// Restauration de l'état mémorisé au chargement
document.addEventListener('DOMContentLoaded', () => {
    // Restauration collapse
    if (localStorage.getItem('sandbox_region_west_collapsed') === '1') {
        toggleRegion('west');
    }
    if (localStorage.getItem('sandbox_region_east_collapsed') === '1') {
        toggleRegion('east');
    }

    // Restauration de la vue active
    const hash = window.location.hash;
    let targetView = 'terroir';
    if (hash.includes('city')) targetView = 'city';
    else if (hash.includes('map')) targetView = 'map';
    else if (localStorage.getItem('sandbox_active_view')) {
        targetView = localStorage.getItem('sandbox_active_view');
    }
    switchSandboxView(targetView);
});
</script>
</body>
</html>
