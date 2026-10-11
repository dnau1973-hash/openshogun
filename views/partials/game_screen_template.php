<?php
/**
 * Template de Grille & Présentation Harmonisée (OpenShogun)
 * Utilisé uniformément par : views/resources.php, views/city.php, views/map.php
 *
 * Garantit une identité visuelle 100% cohérente :
 * - Conteneur écran pleine largeur (.rts-screen-container)
 * - Grille 2 colonnes (.grid-main)
 * - Carte centrale (.rts-center-card) : coins arrondis unifiés (8px), ombre Tabler, bordure
 * - Colonne droite (.east-sidebar-col) : accordéon latéral mutualisé avec hauteur alignée
 *
 * @var string $centerCardContent Contenu HTML interne de la carte centrale (en-tête, corps, scènes)
 * @var string|null $queueTitle Titre de la file de construction dans l'accordéon ('Chantiers Ruraux' ou 'Chantiers Urbains')
 * @var string|null $extraHtml Modales, scripts ou overlays spécifiques à rendre sous la grille
 */

$queueTitle = $queueTitle ?? 'Chantiers en Cours';
?>

<style>
.rts-screen-container {
    width: 100%;
    max-width: 1880px;
    margin: 0.75rem auto;
    padding: 0 1rem;
}
@media (min-width: 1200px) {
    .rts-screen-container {
        padding: 0 1.25rem;
    }
}

.grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 370px;
    gap: 1.25rem;
    align-items: stretch;
}

@media (max-width: 1200px) {
    .grid-main {
        grid-template-columns: 1fr;
    }
}

.rts-center-card {
    border-radius: 8px !important;
    border: 1px solid var(--tblr-border-color, #e6e7e9);
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.east-sidebar-col {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
</style>

<div class="rts-screen-container">
    <div class="grid-main">
        <!-- ========================================================
             COLONNE GAUCHE / CENTRE : CARTE PRINCIPALE INTERACTIVE
             ======================================================== -->
        <div class="card rts-center-card shadow-sm">
            <?= $centerCardContent ?? '' ?>
        </div>

        <!-- ========================================================
             COLONNE DROITE / EST : ACCORDÉON MUTUALISÉ
             ======================================================== -->
        <div class="east-sidebar-col">
            <?php require __DIR__ . '/resources_east_accordion.php'; ?>
        </div>
    </div>
</div>

<?php if (!empty($extraHtml)): ?>
    <?= $extraHtml ?>
<?php endif; ?>

