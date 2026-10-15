<?php
/**
 * Game Studio - Narrative Designer : Charte Narrative & Lore Féodal
 * Fichier : views/gamestudio/narrative_lore.php
 */
declare(strict_types=1);
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Narrative Designer &bull; Univers &amp; Narration</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-warning"><i class="fa-solid fa-scroll"></i></span>
            <span>Charte Narrative &amp; Cohérence de l'Univers Sengoku</span>
            <span class="badge bg-purple-lt text-purple font-monospace">Bible Narrative v1.0</span>
        </h2>
    </div>
</div>

<?php
$loreFile = __DIR__ . '/../studio/narrative-designer/lore.php';
if (file_exists($loreFile)) {
    require $loreFile;
} else {
    echo '<div class="alert alert-info">Le document de charte narrative est en cours d\'initialisation.</div>';
}
