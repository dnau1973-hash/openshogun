<?php
/**
 * Vue des 40 Parcelles de Ressources & Terroir Féodal (OpenShogun)
 * Grille complète de 40 parcelles (8 catégories × 5 parcelles)
 * Simplification du système de logement : Modèle unique « Habitation » (Capacité = 75 + Somme des niveaux × 5)
 */
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/VillageFieldGenerator.php';
require_once __DIR__ . '/../core/OasisEngine.php';
require_once __DIR__ . '/../core/SlotPositionEngine.php';
require_once __DIR__ . '/../core/TerroirEngine.php';
require_once __DIR__ . '/../core/PopulationEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
require_once __DIR__ . '/../config/game_constants.php';

$buildingEngine = new BuildingEngine();
$planetEngine = new PlanetEngine();
$oasisEngine = new OasisEngine();
$terroirEngine = new TerroirEngine();

// Récupérer les 40 parcelles groupées par les 8 catégories thématiques
$all40Slots = $terroirEngine->getAll40Slots((int)$planet['id'], $planet);

// Bilan démographique avec la formule simplifiée d'habitation
$villageSummary = $terroirEngine->getVillageSummary((int)$planet['id'], $planet);
$workforce = $villageSummary['workforce'];
$contentment = $villageSummary['contentment'];
$housingCap = $villageSummary['housing_cap'];
$maxPop = $housingCap['total_capacity'];

// Oasis et files d'attente
$annexedOases = $oasisEngine->getAnnexedOasesForPlanet((int)$planet['id']);
$oasisBonuses = $oasisEngine->getTotalOasisBonusesForPlanet((int)$planet['id']);
$queue = $buildingEngine->getQueue((int)$planet['id']);

$buildings = $planetEngine->getBuildings((int)$planet['id']);
$hqLevel = $buildings['hq'] ?? 1;

// Calcul des cadences de production secondaire
$clayProdHourly = 0;
foreach ($all40Slots['clay']['slots'] as $cs) { $clayProdHourly += $cs['prod_hourly']; }

$teaProdHourly = 0;
foreach ($all40Slots['tea']['slots'] as $ts) { $teaProdHourly += $ts['prod_hourly']; }

$soybeanProdHourly = 0;
foreach ($all40Slots['soybean']['slots'] as $ss) { $soybeanProdHourly += $ss['prod_hourly']; }

$shrineEnergyHourly = 0;
foreach ($all40Slots['shrine']['slots'] as $shs) { $shrineEnergyHourly += $shs['prod_hourly']; }

// Détection de l'archétype de terroir du village
$fields = $planetEngine->getFields((int)$planet['id']);
$terroir = VillageFieldGenerator::detectArchetype($fields);

$isTerran = (($user['faction'] ?? 'terran') === 'terran');
?>

<style>
/* Disposition générale du Domaine Rural Féodal */
.container {
    max-width: 1850px !important;
    width: 98% !important;
    margin: 1rem auto !important;
}

/* Grille principale : 40 parcelles à gauche, Sidebar chantiers/troupes à droite */
.grid-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1200px) {
    .grid-main {
        grid-template-columns: 1fr;
    }
}

/* Header de la vue : Indicateurs de stocks et flux (KPI) */
.kpi-resource-card {
    background: var(--tblr-card-bg, #ffffff);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-resource-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.kpi-resource-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.kpi-resource-val {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.1;
}

.kpi-resource-rate {
    font-size: 0.72rem;
    font-weight: 700;
}

/* Barre de filtrage rapide des 8 catégories */
.filter-category-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    background: var(--tblr-card-bg, #ffffff);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 0.6rem 0.75rem;
}

.btn-filter-cat {
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.85rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.btn-filter-cat.active {
    box-shadow: 0 0 10px rgba(234, 179, 8, 0.35);
}

/* Sections des 8 Catégories Thématiques */
.terroir-category-section {
    background: var(--tblr-card-bg, #ffffff);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.terroir-category-section:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
}

.category-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.category-title-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.category-icon-avatar {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

/* Grille de 5 Parcelles par Catégorie */
.terroir-category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.9rem;
}

@media (max-width: 1400px) {
    .terroir-category-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 600px) {
    .terroir-category-grid {
        grid-template-columns: 1fr;
    }
}

/* Tuile d'une Parcelle (Parcel Card) */
.parcel-tile-card {
    border-radius: 10px;
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    background: var(--tblr-bg-surface, #ffffff);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    position: relative;
}

.parcel-tile-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.parcel-tile-card.is-upgrading {
    border-color: #f59e0b !important;
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.3) !important;
}

/* En-tête de la tuile */
.parcel-card-header {
    padding: 0.5rem 0.65rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.parcel-slot-badge {
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    background: rgba(15, 23, 42, 0.1);
    color: var(--tblr-body-color, #1e293b);
}

.parcel-name-text {
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
}

/* Vignette visuelle de la parcelle */
.parcel-visual-box {
    position: relative;
    width: 100%;
    height: 95px;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 0.45rem;
    box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.45);
}

.parcel-level-badge {
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(4px);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 900;
    padding: 0.2rem 0.55rem;
    border-radius: 20px;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
}

.parcel-workers-pill {
    background: rgba(15, 23, 42, 0.82);
    backdrop-filter: blur(4px);
    color: #e2e8f0;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.18rem 0.45rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Corps d'informations de la tuile */
.parcel-card-body {
    padding: 0.65rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-grow: 1;
}

.parcel-prod-metric {
    font-size: 0.82rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 0, 0, 0.02);
    padding: 0.3rem 0.45rem;
    border-radius: 6px;
}

/* Coûts et Durée */
.parcel-cost-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.72rem;
}

.cost-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.1rem 0.35rem;
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.04);
    font-weight: 600;
}

.cost-chip.affordable {
    color: var(--tblr-body-color, #1e293b);
}

.cost-chip.missing {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
    font-weight: 800;
}

/* Boutons d'action */
.btn-upgrade-parcel {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.38rem 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}
</style>

<div class="grid-main">
    <!-- COLONNE PRINCIPALE : VUE DES 40 PARCELLES -->
    <div>
        <!-- 1. En-tête principal de la vue -->
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                <div>
                    <h2 class="card-title mb-1 fs-2">
                        <i class="fa-solid fa-table-cells-large text-warning me-2"></i>Domaine Rural Féodal &mdash; <?= htmlspecialchars($planet['name']) ?>
                    </h2>
                    <div class="text-muted" style="font-size:0.82rem;">
                        Terroir : <strong class="text-danger"><?= $terroir['icon'] ?> <?= htmlspecialchars($terroir['name']) ?></strong>
                        &bull; <strong>40 Parcelles de Production &amp; d'Accueil</strong> (8 Catégories &times; 5 Parcelles)
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <a href="?page=city" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-chess-rook me-1"></i> Cité Castrale &rarr;
                    </a>
                </div>
            </div>

            <!-- 2. Indicateurs supérieurs (Header de la vue) : Stocks, Flux, Sérénité, Population, Main-d'œuvre & Contentement -->
            <div class="card-body p-3 border-bottom bg-surface-secondary">
                
                <!-- Rangée 1 : Compteurs de ressources (Stocks & Flux horaires des 6 matières) -->
                <div class="row row-cards g-2 mb-3">
                    <!-- Bois de Cèdre -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-success">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Bois de Cèdre</div>
                                <div class="kpi-resource-val text-success"><?= number_format($planet['metal']) ?></div>
                                <div class="kpi-resource-rate text-success">+<?= number_format($planet['prod_rates']['metal']) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-success-lt text-success"><i class="fa-solid fa-tree"></i></span>
                        </div>
                    </div>

                    <!-- Pierre de Taille -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-secondary">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Pierre de Taille</div>
                                <div class="kpi-resource-val text-secondary"><?= number_format($planet['crystal']) ?></div>
                                <div class="kpi-resource-rate text-secondary">+<?= number_format($planet['prod_rates']['crystal']) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-secondary-lt text-secondary"><i class="fa-solid fa-mountain"></i></span>
                        </div>
                    </div>

                    <!-- Argile & Céramique -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-warning">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Argile &amp; Céramique</div>
                                <div class="kpi-resource-val text-warning"><?= number_format($clayProdHourly * 4) ?></div>
                                <div class="kpi-resource-rate text-warning">+<?= number_format($clayProdHourly) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-warning-lt text-warning"><i class="fa-solid fa-jar"></i></span>
                        </div>
                    </div>

                    <!-- Riz Impérial (Koku) -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-warning">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Riz Impérial (Koku)</div>
                                <div class="kpi-resource-val text-warning"><?= number_format($planet['deuterium']) ?></div>
                                <div class="kpi-resource-rate text-warning">+<?= number_format($planet['prod_rates']['deuterium']) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-warning-lt text-warning"><i class="fa-solid fa-wheat-awn"></i></span>
                        </div>
                    </div>

                    <!-- Feuilles de Thé -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-teal">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Feuilles de Thé</div>
                                <div class="kpi-resource-val text-teal"><?= number_format($teaProdHourly * 3) ?></div>
                                <div class="kpi-resource-rate text-teal">+<?= number_format($teaProdHourly) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-teal-lt text-teal"><i class="fa-solid fa-leaf"></i></span>
                        </div>
                    </div>

                    <!-- Champs de Soja -->
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="kpi-resource-card border-start border-3 border-orange">
                            <div>
                                <div class="text-muted text-uppercase fw-bold" style="font-size:0.65rem;">Soja &amp; Tofu</div>
                                <div class="kpi-resource-val text-orange"><?= number_format($soybeanProdHourly * 3) ?></div>
                                <div class="kpi-resource-rate text-orange">+<?= number_format($soybeanProdHourly) ?>/h</div>
                            </div>
                            <span class="kpi-resource-icon bg-orange-lt text-orange"><i class="fa-solid fa-seedling"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Rangée 2 : 4 Indicateurs Clés (Sérénité, Population Globale, Main-d'œuvre, Contentement) -->
                <div class="row row-cards g-2">
                    
                    <!-- 1. Jauge de Sérénité (alimentée par les 5 sanctuaires) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm shadow-sm h-100 border-start border-3 border-pink">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">
                                        <i class="fa-solid fa-torii-gate text-pink me-1"></i>Sérénité Shintō
                                    </span>
                                    <span class="badge bg-pink-lt fw-bold" style="font-size:0.7rem;">5 Sanctuaires</span>
                                </div>
                                <div class="h2 fw-bold mb-1 text-pink">
                                    <?= (int)$planet['energy_used'] ?> <small class="fs-4 text-muted">/ <?= (int)$planet['energy_max'] ?> ferveur</small>
                                </div>
                                <?php
                                    $energyPct = ($planet['energy_max'] > 0) ? min(100, round(($planet['energy_used'] / $planet['energy_max']) * 100)) : 100;
                                    $energyColor = ($planet['energy_used'] <= $planet['energy_max']) ? 'pink' : 'danger';
                                ?>
                                <div class="progress progress-sm mb-1">
                                    <div class="progress-bar bg-<?= $energyColor ?>" style="width: <?= $energyPct ?>%"></div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    <?php if ($planet['energy_used'] <= $planet['energy_max']): ?>
                                        <span class="text-success"><i class="fa-solid fa-check me-1"></i>Harmonie spirituelle préservée</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Tension / Ferveur insuffisante</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Compteur de Population Globale (fournie par les 5 habitations) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm shadow-sm h-100 border-start border-3 border-primary">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">
                                        <i class="fa-solid fa-people-roof text-primary me-1"></i>Population Globale
                                    </span>
                                    <span class="badge bg-primary-lt fw-bold" style="font-size:0.7rem;">5 Habitations</span>
                                </div>
                                <div class="h2 fw-bold mb-1 text-primary">
                                    <?= number_format($villageSummary['population']) ?> <small class="fs-4 text-muted">/ <?= number_format($maxPop) ?> hab.</small>
                                </div>
                                <?php
                                    $popPct = ($maxPop > 0) ? min(100, round(($villageSummary['population'] / $maxPop) * 100)) : 100;
                                ?>
                                <div class="progress progress-sm mb-1">
                                    <div class="progress-bar bg-primary" style="width: <?= $popPct ?>%"></div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    Capacité : <strong>75</strong> (base) + <strong><?= $housingCap['housing_bonus'] ?></strong> (<?= $housingCap['total_levels'] ?> niv. &times; 5 hab.)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Répartition de la Main-d'œuvre (Ouvriers en poste vs Inactifs) -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm shadow-sm h-100 border-start border-3 border-warning">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">
                                        <i class="fa-solid fa-person-digging text-warning me-1"></i>Main-d'Œuvre
                                    </span>
                                    <?php if ($workforce['is_understaffed']): ?>
                                        <span class="badge bg-danger-lt fw-bold" style="font-size:0.7rem;">Sous-effectif -<?= $workforce['understaffed_malus_pct'] ?>%</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-lt fw-bold" style="font-size:0.7rem;">Effectif complet</span>
                                    <?php endif; ?>
                                </div>
                                <div class="h2 fw-bold mb-1 text-warning">
                                    <?= number_format($workforce['assigned_workers']) ?> <small class="fs-4 text-muted">/ <?= number_format($workforce['required_workers']) ?> postes</small>
                                </div>
                                <div class="progress progress-sm mb-1">
                                    <div class="progress-bar bg-warning" style="width: <?= min(100, round($workforce['workforce_ratio'] * 100)) ?>%"></div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    Villageois disponibles non assignés : <strong><?= number_format($workforce['idle_workers']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Jauge de Contentement Féodale -->
                    <div class="col-sm-6 col-lg-3">
                        <?php
                            $ctScore = $contentment['score'];
                            $ctColor = $contentment['badge_color'];
                        ?>
                        <div class="card card-sm shadow-sm h-100 border-start border-3 border-<?= $ctColor ?>">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted text-uppercase fw-bold" style="font-size:0.7rem;">
                                        <i class="fa-solid <?= $contentment['icon'] ?> text-<?= $ctColor ?> me-1"></i>Contentement
                                    </span>
                                    <span class="badge bg-<?= $ctColor ?>-lt fw-bold" style="font-size:0.7rem;"><?= $contentment['status_label'] ?></span>
                                </div>
                                <div class="h2 fw-bold mb-1 text-<?= $ctColor ?>">
                                    <?= $ctScore ?>% <small class="fs-4 text-muted">satisfaction</small>
                                </div>
                                <div class="progress progress-sm mb-1">
                                    <div class="progress-bar bg-<?= $ctColor ?>" style="width: <?= $ctScore ?>%"></div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem;">
                                    Saké : <strong><?= ($contentment['sake_bonus_active']) ? '<span class="text-success">+15% (Bonus actif)</span>' : '<span class="text-muted">Neutre (0%)</span>' ?></strong> &bull; Farine : <?= number_format($planet['rice_flour']) ?> kg
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- 3. Barre de filtrage rapide des 8 Catégories Thématiques -->
            <div class="card-footer py-2 px-3">
                <div class="filter-category-bar">
                    <span class="text-muted fw-bold me-2 align-self-center" style="font-size:0.75rem;">Filtrer :</span>
                    <button type="button" class="btn-filter-cat btn-dark active" onclick="filterCategory('all', this)">
                        <i class="fa-solid fa-globe me-1"></i> Tout afficher (40)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-success" onclick="filterCategory('wood', this)">
                        <i class="fa-solid fa-tree me-1"></i> Bois (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-secondary" onclick="filterCategory('stone', this)">
                        <i class="fa-solid fa-mountain me-1"></i> Pierre (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-warning" onclick="filterCategory('clay', this)">
                        <i class="fa-solid fa-jar me-1"></i> Argile (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-warning" onclick="filterCategory('rice', this)">
                        <i class="fa-solid fa-wheat-awn me-1"></i> Riz (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-teal" onclick="filterCategory('tea', this)">
                        <i class="fa-solid fa-leaf me-1"></i> Thé (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-orange" onclick="filterCategory('soybean', this)">
                        <i class="fa-solid fa-seedling me-1"></i> Soja (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-pink" onclick="filterCategory('shrine', this)">
                        <i class="fa-solid fa-torii-gate me-1"></i> Sérénité (5)
                    </button>
                    <button type="button" class="btn-filter-cat btn-outline-primary" onclick="filterCategory('housing', this)">
                        <i class="fa-solid fa-house-chimney me-1"></i> Habitations (5)
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. GRILLE DES 40 PARCELLES (8 Catégories × 5 Parcelles) -->
        <div id="terroir-40-container">
            <?php foreach ($all40Slots as $catKey => $catGroup): ?>
                <?php
                    $cMeta = $catGroup['meta'];
                    $cSlots = $catGroup['slots'];
                ?>
                <div class="terroir-category-section" id="cat-section-<?= $catKey ?>" data-cat="<?= $catKey ?>">
                    
                    <!-- En-tête de la catégorie -->
                    <div class="category-section-header">
                        <div class="category-title-group">
                            <span class="category-icon-avatar bg-<?= $cMeta['color_class'] ?>-lt text-<?= $cMeta['color_class'] ?>">
                                <i class="<?= $cMeta['icon'] ?>"></i>
                            </span>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="mb-0 fw-bold fs-3"><?= htmlspecialchars($cMeta['name']) ?></h3>
                                    <span class="badge bg-<?= $cMeta['color_class'] ?>-lt fw-bold"><?= $cMeta['badge_text'] ?></span>
                                    <span class="text-muted font-monospace" style="font-size:0.75rem;"><?= $cMeta['jp_name'] ?></span>
                                </div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?= htmlspecialchars($cMeta['desc']) ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="badge bg-dark-lt text-white px-3 py-2 fw-bold" style="font-size:0.8rem;">
                                5 Parcelles aménageables &bull; Bénéfice : <strong class="text-<?= $cMeta['color_class'] ?>"><?= $cMeta['res_name'] ?></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Grille des 5 Tuiles / Parcelles -->
                    <div class="terroir-category-grid">
                        <?php foreach ($cSlots as $slotIdx => $s): ?>
                            <?php
                                $isUp = !empty($s['is_upgrading']);
                                $canAfford = !empty($s['can_afford']);
                                $cost = $s['cost'];
                            ?>
                            <div class="parcel-tile-card <?= $isUp ? 'is-upgrading' : '' ?>" id="card-parcel-<?= $catKey ?>-<?= $slotIdx ?>">
                                
                                <!-- Haut de tuile : Nom et Repère -->
                                <div class="parcel-card-header">
                                    <span class="parcel-slot-badge">#<?= $s['global_index'] ?></span>
                                    <span class="parcel-name-text" title="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></span>
                                    <span class="badge bg-<?= $cMeta['color_class'] ?>-lt text-<?= $cMeta['color_class'] ?>" style="font-size:0.65rem;">Slot <?= $slotIdx ?></span>
                                </div>

                                <!-- Vignette Graphique avec Niveau & Ouvriers -->
                                <div class="parcel-visual-box" style="background-image: linear-gradient(to top, rgba(15,23,42,0.85) 0%, rgba(15,23,42,0.2) 60%), url('<?= $cMeta['bg_image'] ?>');">
                                    <span class="parcel-level-badge <?= $isUp ? 'border-warning text-warning' : '' ?>">
                                        <?= $isUp ? '<i class="fa-solid fa-hourglass-half fa-spin me-1"></i>Niv. ' . ($s['level'] + 1) : 'Niveau ' . $s['level'] ?>
                                    </span>
                                    
                                    <?php if ($catKey !== 'housing'): ?>
                                        <span class="parcel-workers-pill">
                                            <i class="fa-solid fa-person-digging text-warning"></i> <?= $s['workers'] ?> ouv.
                                        </span>
                                    <?php else: ?>
                                        <span class="parcel-workers-pill text-indigo">
                                            <i class="fa-solid fa-people-roof"></i> Foyer #<?= $slotIdx ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Corps d'informations de la tuile -->
                                <div class="parcel-card-body">
                                    
                                    <!-- Métrique de rendement / apport -->
                                    <div class="parcel-prod-metric text-<?= $cMeta['color_class'] ?>">
                                        <span><i class="<?= $cMeta['icon'] ?> me-1"></i>Apport :</span>
                                        <strong><?= $s['prod_label'] ?></strong>
                                    </div>

                                    <!-- Coûts d'élévation -->
                                    <div class="parcel-cost-row">
                                        <span class="cost-chip <?= ($planet['metal'] >= $cost['metal']) ? 'affordable' : 'missing' ?>" title="Bois de Cèdre">
                                            <i class="fa-solid fa-tree text-success"></i> <?= number_format($cost['metal']) ?>
                                        </span>
                                        <span class="cost-chip <?= ($planet['crystal'] >= $cost['crystal']) ? 'affordable' : 'missing' ?>" title="Pierre de Taille">
                                            <i class="fa-solid fa-mountain text-secondary"></i> <?= number_format($cost['crystal']) ?>
                                        </span>
                                        <span class="cost-chip <?= ($planet['deuterium'] >= $cost['deuterium']) ? 'affordable' : 'missing' ?>" title="Riz Impérial">
                                            <i class="fa-solid fa-wheat-awn text-warning"></i> <?= number_format($cost['deuterium']) ?>
                                        </span>
                                    </div>

                                    <!-- Durée & Bouton d'action -->
                                    <div class="mt-auto pt-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1 text-muted" style="font-size:0.7rem;">
                                            <span><i class="fa-regular fa-clock me-1"></i>Durée :</span>
                                            <span class="font-monospace fw-bold"><?= gmdate('i:s', $s['duration']) ?></span>
                                        </div>

                                        <?php if ($isUp): ?>
                                            <button type="button" class="btn btn-sm btn-warning w-100 disabled" style="font-size:0.75rem;">
                                                <i class="fa-solid fa-hourglass-half fa-spin me-1"></i> En chantier...
                                            </button>
                                        <?php elseif ($canAfford): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-<?= $cMeta['color_class'] ?> w-100 btn-upgrade-parcel"
                                                    onclick="upgradeTerroirSlot('<?= $catKey ?>', <?= $slotIdx ?>, this)">
                                                <i class="fa-solid fa-arrow-up me-1"></i> Élever Niv. <?= $s['level'] + 1 ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 disabled" style="font-size:0.75rem;" title="Ressources insuffisantes pour cette élévation">
                                                <i class="fa-solid fa-lock me-1"></i> Ressources requises
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- COLONNE LATÉRALE : CHANTIERS, DIDACTICIEL, OASIS & TROUPES -->
    <div class="d-flex flex-column gap-3">
        <!-- Didacticiel Féodal & Quêtes du Daimyō -->
        <?php require __DIR__ . '/partials/quest_banner.php'; ?>

        <!-- File de Construction Active du Domaine -->
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0 fs-3">
                    <i class="fa-solid fa-trowel-bricks me-2 text-warning"></i>Chantiers Actifs
                </h3>
                <span class="badge bg-warning-lt fw-bold"><?= count($queue) ?> en cours</span>
            </div>
            <div class="card-body p-2">
                <?php if (empty($queue)): ?>
                    <div class="text-muted text-center py-3" style="font-size:0.85rem;">
                        <i class="fa-solid fa-hammer text-secondary d-block mb-1 fs-2"></i>
                        Aucun chantier en cours sur le fief.
                    </div>
                <?php else: ?>
                    <?php foreach ($queue as $q): ?>
                        <?php
                            $name = $q['target_id'];
                            if ($q['build_category'] === 'field') {
                                $tSlot = (int)$q['target_id'];
                                $tType = FIELD_LAYOUT[$tSlot] ?? 'metal_mine';
                                $name = (FIELD_TYPES[$tType]['name'] ?? 'Parcelle') . " #{$tSlot}";
                            } else {
                                $name = BUILDINGS[$q['target_id']]['name'] ?? $q['target_id'];
                            }
                            $qNow = time();
                            $qStart = (int)($q['started_at'] ?? $qNow);
                            $qEnd = (int)($q['finishes_at'] ?? $qNow);
                            $qTotal = max(1, $qEnd - $qStart);
                            $qElapsed = max(0, $qNow - $qStart);
                            $qPct = min(100, max(0, (int)round(($qElapsed / $qTotal) * 100)));
                        ?>
                        <div class="p-2 mb-2 rounded bg-surface-secondary border" style="font-size:0.85rem;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-truncate"><?= htmlspecialchars($name) ?></strong>
                                <span class="badge bg-primary-lt">Niveau <?= $q['target_level'] ?></span>
                            </div>
                            <div class="progress progress-sm mb-1">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning building-progress-bar"
                                     style="width: <?= $qPct ?>%;"
                                     data-countdown="<?= $qEnd ?>"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size:0.75rem;">
                                <span class="text-muted font-monospace building-time-remaining" data-countdown="<?= $qEnd ?>">Calcul...</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="cancelBuild(<?= $q['id'] ?>)">Annuler</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bilan des Récoltes & Oasis Annexées -->
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h3 class="card-title mb-0 fs-3">
                    <i class="fa-solid fa-chart-line me-2 text-success"></i>Récoltes &amp; Oasis
                </h3>
            </div>
            <div class="card-body p-3" style="font-size:0.85rem;">
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-tree text-success me-1"></i> Bois de Cèdre :</span>
                    <strong class="text-success">+<?= number_format($planet['prod_rates']['metal']) ?> / h</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-mountain text-secondary me-1"></i> Pierre de Taille :</span>
                    <strong class="text-secondary">+<?= number_format($planet['prod_rates']['crystal']) ?> / h</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fa-solid fa-wheat-awn text-warning me-1"></i> Riz Impérial :</span>
                    <strong class="text-warning">+<?= number_format($planet['prod_rates']['deuterium']) ?> / h</strong>
                </div>

                <?php if (!empty($annexedOases)): ?>
                    <hr class="my-2">
                    <div class="fw-bold text-success mb-2" style="font-size:0.78rem;">
                        <i class="fa-solid fa-seedling me-1"></i> Oasis Annexées (<?= count($annexedOases) ?> / 3) :
                    </div>
                    <?php foreach ($annexedOases as $ao): ?>
                        <div class="d-flex justify-content-between align-items-center p-1 rounded bg-surface-secondary mb-1" style="font-size:0.75rem;">
                            <span><?= htmlspecialchars($ao['name']) ?> [<?= $ao['coord_x'] ?>:<?= $ao['coord_y'] ?>]</span>
                            <span class="badge bg-success-lt">+<?= $ao['bonus_rice'] ?? 25 ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel des Troupes & Garnisons (Style Travian) -->
        <?php require __DIR__ . '/partials/troops_panel.php'; ?>
    </div>
</div>

<script>
// Filtrage dynamique des 8 Catégories Thématiques de Parcelles
function filterCategory(catKey, btn) {
    document.querySelectorAll('.btn-filter-cat').forEach(b => {
        b.classList.remove('active', 'btn-dark');
        if (!b.classList.contains('btn-outline-success') && 
            !b.classList.contains('btn-outline-secondary') && 
            !b.classList.contains('btn-outline-warning') && 
            !b.classList.contains('btn-outline-teal') && 
            !b.classList.contains('btn-outline-orange') && 
            !b.classList.contains('btn-outline-pink') && 
            !b.classList.contains('btn-outline-primary')) {
            b.classList.add('btn-outline-dark');
        }
    });

    if (btn) {
        btn.classList.add('active');
    }

    const sections = document.querySelectorAll('.terroir-category-section');
    sections.forEach(sec => {
        if (catKey === 'all' || sec.dataset.cat === catKey) {
            sec.style.display = 'block';
            sec.style.opacity = '1';
        } else {
            sec.style.display = 'none';
        }
    });
}

// Action asynchrone AJAX pour élever une parcelle du domaine
async function upgradeTerroirSlot(resourceType, slotIdx, btn) {
    try {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Chantiers...';
        }

        const formData = new FormData();
        formData.append('action', 'upgrade');
        formData.append('resource_type', resourceType);
        formData.append('slot_index', slotIdx);

        const res = await fetch('/api/terroir.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Impossible de lancer l\'amélioration de la parcelle.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
            }
        }
    } catch (err) {
        alert('Erreur réseau lors de l\'amélioration de la parcelle.');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-arrow-up me-1"></i> Réessayer';
        }
    }
}

// Annulation d'un chantier en cours
async function cancelBuild(queueId) {
    if (!confirm('Voulez-vous vraiment annuler ce chantier ? (80% des ressources seront remboursées)')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('queue_id', queueId);

        const res = await fetch('/api/build.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Impossible d\'annuler ce chantier.');
        }
    } catch (err) {
        alert('Erreur réseau lors de l\'annulation.');
    }
}
</script>
