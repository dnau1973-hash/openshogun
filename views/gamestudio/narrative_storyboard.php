<?php
/**
 * Game Studio - Narrative Designer : Gestionnaire de Storyboard & Timeline Narrative
 * Fichier : views/gamestudio/narrative_storyboard.php
 */
declare(strict_types=1);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Narrative Designer &bull; Modélisation de la Narration Vidéo</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-purple"><i class="fa-solid fa-film"></i></span>
            <span>Gestionnaire de Storyboard &amp; Timeline Narrative</span>
            <span class="badge bg-purple-lt text-purple font-monospace">Multi-Projets &bull; Drag &amp; Drop v1.0</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
$managerFile = __DIR__ . '/../studio/narrative-designer/storyboard-manager.php';
if (file_exists($managerFile)) {
    require $managerFile;
} else {
    echo '<div class="alert alert-warning">Le module Storyboard Manager est en cours de déploiement.</div>';
}

