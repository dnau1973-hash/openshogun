<?php
/**
 * Game Studio - Game Elevate Designer : Chroniques Féodales & Événements Hebdomadaires
 * Fichier : views/gamestudio/elevate_events.php
 */
declare(strict_types=1);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Événements Hebdomadaires &amp; Immersion Sengoku</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-warning"><i class="fa-solid fa-scroll"></i></span>
            <span>Chroniques Féodales &amp; Événements Hebdomadaires</span>
            <span class="badge bg-warning-lt text-warning font-monospace">Quêtes Scénarisées &bull; Actes Narratifs</span>
        </h2>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-warning fw-bold font-monospace shadow-sm" onclick="if(window.openCreateEventModal) openCreateEventModal();">
            <i class="fa-solid fa-plus me-1"></i>Créer une Chronique
        </button>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
$chroniclesFile = __DIR__ . '/../studio/game-elevate-designer/weekly-chronicles.php';
if (file_exists($chroniclesFile)) {
    require $chroniclesFile;
} else {
    echo '<div class="alert alert-warning">Le module Chroniques Féodales est en cours de déploiement.</div>';
}
