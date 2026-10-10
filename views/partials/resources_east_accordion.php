<?php
/**
 * Vue Partielle Mutualisée : Colonne Est en Accordéon (Page Ressources / Domaine Rural)
 * Contient les 4 blocs stratégiques : Didacticiel, Chantiers en cours, Récoltes & Stocks, Garnison.
 * Tous les items ont une hauteur fixe identique avec barre de défilement (scroll) automatique.
 */

require_once __DIR__ . '/../../core/QuestEngine.php';
require_once __DIR__ . '/../../core/BarracksEngine.php';
require_once __DIR__ . '/../../core/PlanetEngine.php';
require_once __DIR__ . '/../../core/RuralPlotEngine.php';
require_once __DIR__ . '/../../config/game_constants.php';

$queue = $queue ?? [];
$isTerran = $isTerran ?? (($user['faction'] ?? 'terran') === 'terran');
$annexedOases = $annexedOases ?? [];
$queueTitle = $queueTitle ?? 'Chantiers en cours';

// Données du Didacticiel
$questEngine = new QuestEngine();
$questSummary = ($user && $planet) ? $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']) : null;
$activeQuest = $questSummary['active_quest'] ?? null;
$hasActiveQuest = !empty($activeQuest) && empty($questSummary['all_completed']);

// Données de la Garnison
$barracksEngine = new BarracksEngine();
$stationedTroops = ($user && $planet) ? $barracksEngine->getStationedUnits((int)$planet['id'], $user['faction'] ?? 'terran') : [];
$totalTroopsCount = 0;
foreach ($stationedTroops as $t) {
    $totalTroopsCount += (int)($t['stationed_count'] ?? 0);
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
                    <?php if ($hasActiveQuest): ?>
                        <?php if (!empty($activeQuest['is_claimable'])): ?>
                            <span class="badge bg-success-lt text-success ms-auto fw-bold" style="font-size:0.68rem;">Objectif atteint !</span>
                        <?php else: ?>
                            <span class="badge bg-primary-lt ms-auto font-monospace" style="font-size:0.68rem;">Étape <?= (int)$activeQuest['order'] ?>/<?= (int)($questSummary['total_quests'] ?? 12) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge bg-secondary-lt text-secondary ms-auto font-monospace" style="font-size:0.68rem;">Codex (12/12)</span>
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
                <?php if ($hasActiveQuest): ?>
                    <?php require __DIR__ . '/quest_banner.php'; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-award text-warning d-block mb-2" style="font-size: 2rem;"></i>
                        <strong class="text-dark d-block">Didacticiel Accompli !</strong>
                        <p class="small text-muted mt-1 mb-3">Toutes les épreuves du Shogunat ont été complétées avec succès.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openQuestModal()">
                            <i class="fa-solid fa-book-open me-1"></i> Ouvrir le Codex Féodal
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
                    <i class="fa-solid fa-helmet-safety text-warning"></i>
                    <span class="fw-bold"><?= htmlspecialchars($queueTitle) ?></span>
                    <?php if ($isTerran): ?>
                        <span class="badge bg-danger-lt text-danger ms-auto fw-bold" style="font-size:0.65rem;" title="Privilège Oda : Chantiers simultanés">Oda</span>
                    <?php endif; ?>
                    <span class="badge bg-warning-lt fw-bold <?= $isTerran ? '' : 'ms-auto' ?>" style="font-size:0.68rem;"><?= count($queue) ?> actif(s)</span>
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
                    require __DIR__ . '/urban_construction_queue.php'; 
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

    <!-- ── 4. GARNISON DU DOMAINE ── -->
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
                    <a href="?page=barracks" 
                       class="btn btn-sm btn-outline-secondary py-0 px-1.5 ms-auto" 
                       style="font-size: 0.65rem;" 
                       onclick="event.stopPropagation();" 
                       title="Accéder au Dojo Militaire">
                        Dojo &rarr;
                    </a>
                    <span class="badge bg-secondary-lt fw-bold font-monospace" style="font-size:0.68rem;"><?= number_format($totalTroopsCount) ?> troupes</span>
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
                <?php require __DIR__ . '/troops_panel.php'; ?>
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

