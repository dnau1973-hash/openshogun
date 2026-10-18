<?php
/**
 * Game Studio - Game Elevate Designer : Simulateur de Sièges sur les 12 Châteaux Authentiques
 * Fichier : views/gamestudio/elevate_castles.php
 */
declare(strict_types=1);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage Martial &amp; Sanctuaires Impériaux</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-warning"><i class="fa-solid fa-chess-rook"></i></span>
            <span>Siège des 12 Châteaux Authentiques</span>
            <span class="badge bg-warning-lt text-dark font-monospace">現存十二天守 &bull; Genzon Jūni Tenshu</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
require __DIR__ . '/../studio/game-elevate-designer/castle-siege-simulator.php';

