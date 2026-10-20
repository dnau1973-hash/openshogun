<?php
/**
 * Vue Partielle Mutualisée : Colonne Est en Accordéon (Page Ressources / Domaine Rural)
 * Contient les 5 blocs stratégiques : Didacticiel, Chantiers en cours, Récoltes & Stocks, Main-d'œuvre & Démographie, Garnison.
 * Tous les items ont une hauteur fixe identique avec barre de défilement (scroll) automatique.
 */

require_once __DIR__ . '/../../core/QuestEngine.php';
require_once __DIR__ . '/../../core/BarracksEngine.php';
require_once __DIR__ . '/../../core/ShipyardEngine.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';
require_once __DIR__ . '/../../core/BuildingEngine.php';
require_once __DIR__ . '/../../core/ResearchEngine.php';
require_once __DIR__ . '/../../config/game_constants.php';

if (!isset($queue) && !empty($planet['id'])) {
    require_once __DIR__ . '/../../core/BuildingEngine.php';
    $bEngine = new BuildingEngine();
    $queue = $bEngine->getQueue((int)$planet['id']);
}
if (!isset($annexedOases) && !empty($planet['id'])) {
    require_once __DIR__ . '/../../core/OasisEngine.php';
    $oEngine = new OasisEngine();
    $annexedOases = $oEngine->getAnnexedOasesForPlanet((int)$planet['id']);
}

require_once __DIR__ . '/../../core/ResearchEngine.php';
$researchEngine = new ResearchEngine();

if (!isset($planetEngine)) {
    require_once __DIR__ . '/../../core/PlanetEngine.php';
    $planetEngine = new PlanetEngine();
}

$barracksEngine = $barracksEngine ?? new BarracksEngine();
$shipyardEngine = $shipyardEngine ?? new ShipyardEngine();

if (!isset($barracksQueue) && !empty($planet['id'])) {
    try {
        $barracksQueue = $barracksEngine->getQueue((int)$planet['id']);
    } catch (Throwable $e) {
        $barracksQueue = [];
    }
}
$barracksQueue = $barracksQueue ?? [];

if (!isset($shipyardQueue) && !empty($planet['id'])) {
    try {
        $shipyardQueue = $shipyardEngine->getQueue((int)$planet['id']);
    } catch (Throwable $e) {
        $shipyardQueue = [];
    }
}
$shipyardQueue = $shipyardQueue ?? [];

if (!isset($activeResearch) && !empty($user['id'])) {
    try {
        $activeResearch = $researchEngine->getActiveQueue((int)$user['id']);
    } catch (Throwable $e) {
        $activeResearch = null;
    }
}

if (!isset($activeFeast) && !empty($planet['id'])) {
    try {
        $activeFeast = $planetEngine->getActiveFeast((int)$planet['id']);
    } catch (Throwable $e) {
        $activeFeast = null;
    }
}

if (!isset($craftQueue) && !empty($planet['id'])) {
    try {
        $craftQueue = $planetEngine->getCraftQueue((int)$planet['id']);
    } catch (Throwable $e) {
        $craftQueue = [];
    }
}
$craftQueue = $craftQueue ?? [];

$totalActiveJobs = count($queue ?? []) 
    + count($barracksQueue) 
    + count($shipyardQueue) 
    + (!empty($activeResearch) ? 1 : 0) 
    + (!empty($activeFeast) ? 1 : 0) 
    + count($craftQueue);

$queue = $queue ?? [];
$isTerran = $isTerran ?? (($user['faction'] ?? 'terran') === 'terran');
$annexedOases = $annexedOases ?? [];
$queueTitle = $queueTitle ?? 'Chantiers en cours';

// Données du Didacticiel
$questEngine = new QuestEngine();
try {
    $questSummary = ($user && $planet) ? $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']) : null;
} catch (Throwable $e) {
    $questSummary = null;
}
$activeQuest = $questSummary['active_quest'] ?? null;
$hasActiveQuest = !empty($activeQuest) && empty($questSummary['all_completed']);

// Données de la Garnison (Troupes du Dojo & Armes de Siège)
try {
    $stationedTroops = ($user && $planet) ? $barracksEngine->getStationedUnits((int)$planet['id'], $user['faction'] ?? 'terran') : [];
} catch (Throwable $e) {
    $stationedTroops = [];
}
$totalTroopsCount = 0;
foreach ($stationedTroops as $t) {
    $totalTroopsCount += (int)($t['stationed_count'] ?? 0);
}

try {
    $allStationedShips = ($user && $planet) ? $shipyardEngine->getAvailableShips((int)$planet['id'], $user['faction'] ?? 'terran') : [];
} catch (Throwable $e) {
    $allStationedShips = [];
}
$totalSiegeCount = 0;
$siegeCodesList = ['terran_cruiser', 'terran_dreadnought', 'vorash_leviathan', 'aethelis_prism', 'aethelis_titan'];
foreach ($allStationedShips as $s) {
    $isSiege = in_array($s['code'], $siegeCodesList, true)
               || (stripos($s['name'], 'siège') !== false)
               || (stripos($s['name'], 'catapulte') !== false)
               || (stripos($s['name'], 'bélier') !== false)
               || (stripos($s['name'], 'tour') !== false);
    if ($isSiege) {
        $totalSiegeCount += (int)($s['stationed_count'] ?? 0);
    }
}
$totalMilitaryGarrison = $totalTroopsCount + $totalSiegeCount;

// Données de la Main-d'œuvre & Démographie
$curPop = (int)($planet['population'] ?? 0);
$maxPop = (int)($planet['population_max'] ?? 100);
$wfSummary = $planet['workforce'] ?? ($prodRates['workforce'] ?? null);
$isUnderstaffed = !empty($wfSummary['is_understaffed']);
$malusUnderstaffed = (float)($wfSummary['understaffed_malus_pct'] ?? 0);

// Données de l'Événement Hebdomadaire (Chronique Féodale)
require_once __DIR__ . '/../../core/WeeklyEventEngine.php';
$weeklyEngine = new WeeklyEventEngine();
try {
    $activeEvent = ($user && $planet) ? $weeklyEngine->getActiveEvent((int)$user['id'], (int)$planet['id']) : null;
} catch (Throwable $e) {
    $activeEvent = null;
}
$eventPlayerStatus = $activeEvent['player_status'] ?? null;
$hasClaimableEvent = false;
if (!empty($eventPlayerStatus['tasks'])) {
    foreach ($eventPlayerStatus['tasks'] as $t) {
        if (!empty($t['is_completed']) && empty($t['is_claimed'])) {
            $hasClaimableEvent = true;
            break;
        }
    }
}
if (!empty($eventPlayerStatus['all_completed']) && empty($eventPlayerStatus['final_claimed'])) {
    $hasClaimableEvent = true;
}

// Détermination de l'élément ouvert par défaut : Didacticiel si quête active, sinon Chantiers
$defaultOpen = $hasActiveQuest ? 'quest' : 'queue';
?>

<style>
/* ========================================================
   ACCORDÉON DE LA COLONNE EST (ALIGNÉ SUR LE CARD DU FIEF)
   ======================================================== */
.east-sidebar-col {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.resources-east-accordion {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
    height: 100%;
}

.resources-east-accordion .accordion-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px !important;
    overflow: hidden;
    background-color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    flex: 0 0 auto;
}

.resources-east-accordion .accordion-header {
    margin: 0;
    flex: 0 0 auto;
}

.resources-east-accordion .accordion-button {
    background-color: #ffffff !important;
    color: #1e293b;
    font-size: 0.88rem;
    padding: 0.75rem 1rem;
    box-shadow: none !important;
    border: none;
}

.resources-east-accordion .accordion-button:not(.collapsed) {
    background-color: #f8fafc !important;
    color: #0f172a;
    border-bottom: 1px solid #e2e8f0;
}

.resources-east-accordion .accordion-button::after {
    display: none !important; /* Neutralise le chevron standard droit */
}

.resources-east-accordion .accordion-button-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    color: #64748b;
    transition: transform 0.25s ease, color 0.2s ease;
}

.resources-east-accordion .accordion-button:not(.collapsed) .accordion-button-toggle {
    transform: rotate(180deg);
    color: #0f172a;
}

/* HAUTEUR DYNAMIQUE SUR DESKTOP : ITEM ACTIF EXTENSIBLE À LA HAUTEUR DU CARD */
@media (min-width: 961px) {
    .resources-east-accordion .accordion-item.is-expanded,
    .resources-east-accordion .accordion-item:has(> .accordion-collapse.show),
    .resources-east-accordion .accordion-item:has(> .accordion-collapse.collapsing) {
        flex: 1 1 0%;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .resources-east-accordion .accordion-collapse.show,
    .resources-east-accordion .accordion-collapse.collapsing {
        flex: 1 1 0%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        height: auto !important;
        transition: none !important;
    }

    .resources-east-accordion .accordion-body {
        flex: 1 1 0%;
        min-height: 0;
        height: 100%;
        max-height: none;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.75rem;
        background-color: #ffffff !important;
    }
}

/* TABLETTE / MOBILE : HAUTEUR DÉFINIE AVEC SCROLL */
@media (max-width: 960px) {
    .resources-east-accordion .accordion-body {
        height: 480px;
        max-height: 480px;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0.75rem;
        background-color: #ffffff !important;
    }
}

.resources-east-accordion .accordion-body::-webkit-scrollbar {
    width: 6px;
}
.resources-east-accordion .accordion-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.resources-east-accordion .accordion-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.resources-east-accordion .accordion-body::-webkit-scrollbar-track {
    background: transparent;
}

/* Neutralisation des doubles cartes imbriquées à l'intérieur */
.resources-east-accordion .card {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    margin-bottom: 0 !important;
}
.resources-east-accordion .card > .card-header {
    display: none !important;
}
.resources-east-accordion .card > .card-body {
    padding: 0 !important;
}
</style>

<div class="accordion resources-east-accordion" id="resourcesEastAccordion">

    <!-- ── 1. DIDACTICIEL FÉODAL & QUÊTES DU DAIMYŌ ── -->
    <?php if ($hasActiveQuest): ?>
    <div class="accordion-item bg-white">
        <h3 class="accordion-header" id="heading-east-quest">
            <button class="accordion-button <?= ($defaultOpen === 'quest') ? '' : 'collapsed' ?>" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-quest" 
                    aria-expanded="<?= ($defaultOpen === 'quest') ? 'true' : 'false' ?>" 
                    aria-controls="east-item-quest">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-scroll text-primary"></i>
                    <span class="fw-bold">Didacticiel</span>
                    <?php if (!empty($activeQuest['is_claimable'])): ?>
                        <span class="badge bg-success-lt text-success ms-auto fw-bold" style="font-size:0.68rem;">Objectif atteint !</span>
                    <?php else: ?>
                        <span class="badge bg-primary-lt ms-auto font-monospace" style="font-size:0.68rem;">Étape <?= (int)$activeQuest['order'] ?>/<?= (int)($questSummary['total_quests'] ?? 12) ?></span>
                    <?php endif; ?>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-quest" 
             class="accordion-collapse collapse <?= ($defaultOpen === 'quest') ? 'show' : '' ?>" 
             aria-labelledby="heading-east-quest" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body">
                <?php require __DIR__ . '/quest_banner.php'; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── 1bis. CHRONIQUE FÉODALE DU SENGOKU (ÉVÉNEMENT HEBDOMADAIRE) ── -->
    <?php if ($activeEvent): ?>
    <div class="accordion-item bg-white border-warning-subtle">
        <h3 class="accordion-header" id="heading-east-weekly-event">
            <button class="accordion-button collapsed" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-weekly-event" 
                    aria-expanded="false" 
                    aria-controls="east-item-weekly-event">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-hourglass-start text-warning"></i>
                    <span class="fw-bold">Chronique Féodale</span>
                    <?php if ($hasClaimableEvent): ?>
                        <span class="badge bg-success text-white fw-bold ms-auto animate-pulse" style="font-size:0.68rem;">Récompense !</span>
                    <?php else: ?>
                        <span class="badge bg-warning-lt text-warning ms-auto font-monospace" style="font-size:0.68rem;"><?= (int)($eventPlayerStatus['completed_tasks'] ?? 0) ?>/<?= (int)($eventPlayerStatus['total_tasks'] ?? 0) ?> actes</span>
                    <?php endif; ?>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-weekly-event" 
             class="accordion-collapse collapse" 
             aria-labelledby="heading-east-weekly-event" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body p-2">
                <div class="p-2 rounded mb-2 text-white shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid rgba(234, 179, 8, 0.3);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-warning text-dark fw-bold font-monospace" style="font-size:0.65rem;"><?= htmlspecialchars($activeEvent['era_year']) ?></span>
                        <span class="font-monospace text-warning small"><i class="fa-regular fa-clock me-1"></i><?= gmdate('d\j H\h', max(0, $activeEvent['seconds_remaining'])) ?></span>
                    </div>
                    <div class="fw-bold text-white small mb-1"><?= htmlspecialchars($activeEvent['title']) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem; line-height: 1.35;"><?= htmlspecialchars(mb_strimwidth($activeEvent['description'], 0, 110, '...')) ?></div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between text-muted font-monospace mb-1" style="font-size:0.72rem;">
                        <span>Progression des Actes</span>
                        <strong class="text-warning"><?= (int)($eventPlayerStatus['progress_percent'] ?? 0) ?>%</strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (int)($eventPlayerStatus['progress_percent'] ?? 0) ?>%;"></div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-warning w-100 fw-bold d-flex align-items-center justify-content-center gap-1.5 shadow-sm" onclick="if(window.openWeeklyChronicleModal){window.openWeeklyChronicleModal();} return false;" data-bs-toggle="modal" data-bs-target="#weeklyChronicleModal">
                    <i class="fa-solid fa-scroll"></i>
                    <span>Dérouler la Chronique &amp; Récompenses</span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── 2. CHANTIERS EN COURS DU FIEF ── -->
    <div class="accordion-item bg-white">
        <h3 class="accordion-header" id="heading-east-queue">
            <button class="accordion-button <?= ($defaultOpen === 'queue') ? '' : 'collapsed' ?>" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-queue" 
                    aria-expanded="<?= ($defaultOpen === 'queue') ? 'true' : 'false' ?>" 
                    aria-controls="east-item-queue">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-list-check text-warning"></i>
                    <span class="fw-bold"><?= htmlspecialchars($queueTitle) ?></span>
                    <?php if ($isTerran): ?>
                        <span class="badge bg-danger-lt text-danger ms-auto fw-bold" style="font-size:0.65rem;" title="Privilège Oda : Chantiers simultanés">Oda</span>
                    <?php endif; ?>
                    <span class="badge bg-warning-lt fw-bold <?= $isTerran ? '' : 'ms-auto' ?>" style="font-size:0.68rem;"><?= (int)$totalActiveJobs ?> actif(s)</span>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-queue" 
             class="accordion-collapse collapse <?= ($defaultOpen === 'queue') ? 'show' : '' ?>" 
             aria-labelledby="heading-east-queue" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body">
                <?php 
                    try {
                        require __DIR__ . '/urban_construction_queue.php'; 
                    } catch (Throwable $e) {
                        echo '<div class="alert alert-warning py-1 px-2 small mb-0">Chargement des chantiers indisponible.</div>';
                    }
                ?>
            </div>
        </div>
    </div>

    <!-- ── 3. RÉCOLTES, STOCKS & OASIS ── -->
    <div class="accordion-item bg-white">
        <h3 class="accordion-header" id="heading-east-harvest">
            <button class="accordion-button collapsed" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-harvest" 
                    aria-expanded="false" 
                    aria-controls="east-item-harvest">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-wheat-awn text-success"></i>
                    <span class="fw-bold">Récoltes, Stocks &amp; Oasis</span>
                    <span class="badge bg-teal-lt text-teal font-monospace ms-auto" style="font-size:0.68rem;"><?= count($annexedOases) ?>/3 Oasis</span>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-harvest" 
             class="accordion-collapse collapse" 
             aria-labelledby="heading-east-harvest" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body">
                <?php 
                    $panelTitle = 'Récoltes, Stocks & Oasis';
                    require __DIR__ . '/rural_harvest_resources_panel.php'; 
                ?>
            </div>
        </div>
    </div>

    <!-- ── 4. MAIN-D'ŒUVRE & DÉMOGRAPHIE ── -->
    <div class="accordion-item bg-white">
        <h3 class="accordion-header" id="heading-east-workforce">
            <button class="accordion-button collapsed" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-workforce" 
                    aria-expanded="false" 
                    aria-controls="east-item-workforce">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-users-gear text-indigo"></i>
                    <span class="fw-bold">Main-d'œuvre &amp; Démographie</span>
                    <?php if ($isUnderstaffed): ?>
                        <span class="badge bg-warning-lt text-warning font-monospace ms-auto fw-bold" style="font-size:0.68rem;" title="Sous-effectif actif : productivité réduite">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>-<?= $malusUnderstaffed ?>%
                        </span>
                    <?php else: ?>
                        <span class="badge bg-indigo-lt text-indigo font-monospace ms-auto" style="font-size:0.68rem;">
                            <?= number_format($curPop) ?>/<?= number_format($maxPop) ?> Sujets
                        </span>
                    <?php endif; ?>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-workforce" 
             class="accordion-collapse collapse" 
             aria-labelledby="heading-east-workforce" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body">
                <?php require __DIR__ . '/workforce_demographics_panel.php'; ?>
            </div>
        </div>
    </div>

    <!-- ── 5. GARNISON DU DOMAINE ── -->
    <div class="accordion-item bg-white">
        <h3 class="accordion-header" id="heading-east-troops">
            <button class="accordion-button collapsed" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#east-item-troops" 
                    aria-expanded="false" 
                    aria-controls="east-item-troops">
                <div class="d-flex align-items-center gap-2 flex-grow-1 text-truncate pe-2">
                    <i class="fa-solid fa-khanda text-danger"></i>
                    <span class="fw-bold">Garnison du Domaine</span>
                    <div class="d-flex align-items-center gap-1 ms-auto" onclick="event.stopPropagation();">
                        <a href="?page=shipyard" 
                           class="btn btn-sm btn-outline-danger py-0 px-1.5" 
                           style="font-size: 0.65rem;" 
                           title="Atelier de Siège & Écuries">
                            <i class="fa-solid fa-bomb"></i> Siège
                        </a>
                        <a href="?page=barracks" 
                           class="btn btn-sm btn-outline-secondary py-0 px-1.5" 
                           style="font-size: 0.65rem;" 
                           title="Accéder au Dojo Militaire">
                            Dojo &rarr;
                        </a>
                    </div>
                    <span class="badge bg-secondary-lt fw-bold font-monospace" style="font-size:0.68rem;" title="<?= $totalTroopsCount ?> soldat(s), <?= $totalSiegeCount ?> arme(s) de siège">
                        <?= number_format($totalMilitaryGarrison) ?>
                        <?php if ($totalSiegeCount > 0): ?>
                            <span class="text-danger ms-0.5" title="<?= $totalSiegeCount ?> engin(s) de siège"><i class="fa-solid fa-bomb"></i><?= $totalSiegeCount ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <span class="accordion-button-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                </span>
            </button>
        </h3>
        <div id="east-item-troops" 
             class="accordion-collapse collapse" 
             aria-labelledby="heading-east-troops" 
             data-bs-parent="#resourcesEastAccordion">
            <div class="accordion-body">
                <?php 
                    try {
                        require __DIR__ . '/troops_panel.php'; 
                    } catch (Throwable $e) {
                        echo '<div class="alert alert-warning py-1 px-2 small mb-0">Chargement de la garnison indisponible.</div>';
                    }
                ?>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    function syncAccordionWithCenterCard() {
        const accordion = document.getElementById('resourcesEastAccordion');
        if (!accordion) return;

        // Sur mobile/tablette, laisser la hauteur fluide avec max-height
        if (window.innerWidth <= 960) {
            accordion.style.height = '';
            return;
        }

        // Trouver la carte centrale dans le même .grid-main
        const gridMain = accordion.closest('.grid-main');
        const centerCard = gridMain ? gridMain.querySelector(':scope > .card') : null;
        if (centerCard) {
            const targetHeight = centerCard.offsetHeight;
            if (targetHeight > 0) {
                accordion.style.height = targetHeight + 'px';
            }
        }

        // Marquer l'élément actuellement ouvert avec la classe is-expanded
        accordion.querySelectorAll('.accordion-item').forEach(function(item) {
            if (item.querySelector('.accordion-collapse.show')) {
                item.classList.add('is-expanded');
            } else {
                item.classList.remove('is-expanded');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const accordion = document.getElementById('resourcesEastAccordion');
        if (!accordion) return;

        syncAccordionWithCenterCard();

        accordion.addEventListener('show.bs.collapse', function(e) {
            accordion.querySelectorAll('.accordion-item').forEach(function(item) {
                item.classList.remove('is-expanded');
            });
            const openItem = e.target.closest('.accordion-item');
            if (openItem) openItem.classList.add('is-expanded');
            setTimeout(syncAccordionWithCenterCard, 50);
        });

        accordion.addEventListener('hidden.bs.collapse', function(e) {
            const item = e.target.closest('.accordion-item');
            if (item && !item.querySelector('.accordion-collapse.show')) {
                item.classList.remove('is-expanded');
            }
        });

        window.addEventListener('resize', syncAccordionWithCenterCard);

        if (window.ResizeObserver) {
            const gridMain = accordion.closest('.grid-main');
            const centerCard = gridMain ? gridMain.querySelector(':scope > .card') : null;
            if (centerCard) {
                new ResizeObserver(syncAccordionWithCenterCard).observe(centerCard);
            }
        }
    });

    if (document.readyState !== 'loading') {
        syncAccordionWithCenterCard();
    }
})();
</script>

