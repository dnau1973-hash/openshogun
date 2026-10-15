<?php
/**
 * Game Studio - Game Elevate Designer : Dérivation des Bâtiments & Progression
 * Fichier : views/gamestudio/elevate_buildings.php
 */
declare(strict_types=1);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-info"><i class="fa-solid fa-chart-line"></i></span>
            <span>Dérivation des Bâtiments &amp; Courbes de Progression</span>
            <span class="badge bg-info-lt text-info font-monospace">Modèle Exponentiel v2.0</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
require __DIR__ . '/../studio/game-elevate-designer/building-derivation.php';

