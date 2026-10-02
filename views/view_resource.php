<?php
/**
 * Vue Modulaire : Gestion Thématique par Ressource (5 Slots de Développement)
 * Supporte : wood, stone, clay, rice, tea, soybean, village
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/TerroirEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

if (!$planet) {
    header('Location: /?page=resources');
    exit;
}

$resourceType = trim((string)($_GET['type'] ?? 'wood'));
if (!isset(TerroirEngine::ZONES[$resourceType])) {
    $resourceType = 'wood';
}

$terroirEngine = new TerroirEngine();
$zDef = TerroirEngine::ZONES[$resourceType];
$slots = $terroirEngine->getZoneSlots((int)$planet['id'], $resourceType, $planet);

// Si vue Village, charger les métriques démographiques détaillées
$villageData = ($resourceType === 'village') ? $terroirEngine->getVillageSummary((int)$planet['id'], $planet) : null;

$bgFile = __DIR__ . '/..' . $zDef['bg_image'];
$bgVersion = file_exists($bgFile) ? filemtime($bgFile) : 1;
?>

<style>
/* Vue Thématique du Terroir Féodal */
.terroir-container {
    max-width: 1750px;
    width: 98%;
    margin: 1rem auto;
}

/* Scène visuelle panoramique haute résolution de la zone */
.resource-scene-viewport {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    max-height: 620px;
    background-image: url('<?= htmlspecialchars($zDef['bg_image']) ?>?v=<?= $bgVersion ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 12px;
    border: 2px solid rgba(255, 255, 255, 0.12);
    box-shadow: inset 0 0 60px rgba(0, 0, 0, 0.65), 0 10px 30px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    user-select: none;
    margin-bottom: 1.5rem;
}

/* Hotspots de slots positionnés sur le décor */
.terroir-slot-pin {
    position: absolute;
    transform: translate(-50%, -50%);
    cursor: pointer;
    z-index: 10;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.25s ease;
}

.terroir-slot-pin:hover {
    transform: translate(-50%, -50%) scale(1.22);
    z-index: 25;
}

.slot-pin-card {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(8px);
    border: 1.5px solid <?= $zDef['color'] ?>;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.6), 0 0 12px <?= $zDef['color'] ?>66;
    color: #f8fafc;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.terroir-slot-pin:hover .slot-pin-card {
    background: rgba(15, 23, 42, 0.98);
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.8), 0 0 20px <?= $zDef['color'] ?>;
    border-color: #ffffff;
}

.slot-pin-lvl {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: <?= $zDef['color'] ?>;
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 900;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    box-shadow: 0 0 8px <?= $zDef['color'] ?>;
}

.slot-pin-beacon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100%;
    height: 100%;
    transform: translate(-50%, -50%);
    border-radius: 50px;
    border: 1.5px solid <?= $zDef['color'] ?>;
    pointer-events: none;
    animation: pin-beacon-pulse 2s infinite ease-out;
}

@keyframes pin-beacon-pulse {
    0% { transform: translate(-50%, -50%) scale(0.9); opacity: 0.9; }
    100% { transform: translate(-50%, -50%) scale(1.4); opacity: 0; }
}

/* Grille des 5 cartes de développement */
.terroir-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem;
}

.slot-card {
    border-radius: 10px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    background: var(--tblr-card-bg, #ffffff);
}

.slot-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
    border-color: <?= $zDef['color'] ?>;
}

.slot-card.highlighted {
    border-color: <?= $zDef['color'] ?> !important;
    box-shadow: 0 0 20px <?= $zDef['color'] ?>55 !important;
}

.cost-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.45rem;
    border-radius: 4px;
    font-size: 0.75rem;
    background: rgba(0, 0, 0, 0.04);
}
</style>

<div class="terroir-container">
    <!-- Barre de Navigation Supérieure -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="?page=resources" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i> Carte Globale du Terroir
                </a>
                <span class="badge bg-<?= $zDef['color_class'] ?>-lt fw-bold px-2 py-1" style="font-size:0.8rem;">
                    <i class="<?= $zDef['icon'] ?> me-1"></i> <?= htmlspecialchars($zDef['badge_text']) ?>
                </span>
                <span class="text-muted font-monospace" style="font-size:0.85rem;"><?= htmlspecialchars($zDef['jp_name']) ?></span>
            </div>
            <h1 class="h2 mb-0 d-flex align-items-center gap-2">
                <i class="<?= $zDef['icon'] ?>" style="color:<?= $zDef['color'] ?>;"></i>
                <?= htmlspecialchars($zDef['name']) ?> &mdash; <span class="text-secondary"><?= htmlspecialchars($planet['name']) ?></span>
            </h1>
        </div>

        <!-- Sélecteur rapide des 7 zones -->
        <div class="btn-group" role="group">
            <a href="?page=view_resource&type=wood" class="btn btn-sm <?= ($resourceType === 'wood') ? 'btn-success' : 'btn-outline-secondary' ?>" title="Forêt & Bûcheronnage">
                <i class="fa-solid fa-tree me-1"></i> Bois
            </a>
            <a href="?page=view_resource&type=stone" class="btn btn-sm <?= ($resourceType === 'stone') ? 'btn-secondary' : 'btn-outline-secondary' ?>" title="Montagne & Pierre">
                <i class="fa-solid fa-mountain me-1"></i> Pierre
            </a>
            <a href="?page=view_resource&type=clay" class="btn btn-sm <?= ($resourceType === 'clay') ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Gisement d'Argile">
                <i class="fa-solid fa-jar me-1"></i> Argile
            </a>
            <a href="?page=view_resource&type=rice" class="btn btn-sm <?= ($resourceType === 'rice') ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Terrasses Rizicoles">
                <i class="fa-solid fa-wheat-awn me-1"></i> Riz
            </a>
            <a href="?page=view_resource&type=tea" class="btn btn-sm <?= ($resourceType === 'tea') ? 'btn-teal' : 'btn-outline-secondary' ?>" title="Champs de Thé">
                <i class="fa-solid fa-leaf me-1"></i> Thé
            </a>
            <a href="?page=view_resource&type=soybean" class="btn btn-sm <?= ($resourceType === 'soybean') ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Champs de Soja">
                <i class="fa-solid fa-seedling me-1"></i> Soja
            </a>
            <a href="?page=view_resource&type=village" class="btn btn-sm <?= ($resourceType === 'village') ? 'btn-danger' : 'btn-outline-secondary' ?>" title="Village & Démographie">
                <i class="fa-solid fa-people-roof me-1"></i> Village
            </a>
        </div>
    </div>

    <!-- Widgets Spécifiques au Village Central (Démographie, Sérénité, Travailleurs) -->
    <?php if ($resourceType === 'village' && $villageData): ?>
        <?php
            $wf = $villageData['workforce'];
            $ct = $villageData['contentment'];
            $score = $ct['score'] ?? 80;
            $ctColor = ($score >= 75) ? 'success' : (($score >= 40) ? 'warning' : 'danger');
        ?>
        <div class="row row-cards mb-3">
            <!-- Widget 1: Démographie & Logements -->
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm shadow-sm border-start border-3 border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar rounded bg-primary-lt text-primary fs-3">
                                <i class="fa-solid fa-people-roof"></i>
                            </span>
                            <span class="badge bg-primary-lt fw-bold">Capacité : <?= number_format($villageData['max_pop']) ?> hab.</span>
                        </div>
                        <div class="h1 fw-bold mb-1"><?= number_format($villageData['population']) ?> <small class="fs-4 text-muted">villageois</small></div>
                        <div class="progress progress-sm mb-1">
                            <div class="progress-bar bg-primary" style="width: <?= min(100, round(($villageData['population'] / max(1, $villageData['max_pop'])) * 100)) ?>%"></div>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            Logements Minka & Nagaya &bull; <?= max(0, $villageData['max_pop'] - $villageData['population']) ?> places d'accueil libres
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget 2: Affectation des Travailleurs -->
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm shadow-sm border-start border-3 border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar rounded bg-warning-lt text-warning fs-3">
                                <i class="fa-solid fa-person-digging"></i>
                            </span>
                            <?php if ($wf['is_understaffed']): ?>
                                <span class="badge bg-danger-lt fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Sous-effectif (-<?= $wf['understaffed_malus_pct'] ?>%)</span>
                            <?php else: ?>
                                <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-check me-1"></i>Effectif complet</span>
                            <?php endif; ?>
                        </div>
                        <div class="h1 fw-bold mb-1"><?= number_format($wf['assigned_workers']) ?> <small class="fs-4 text-muted">/ <?= number_format($wf['required_workers']) ?> postes</small></div>
                        <div class="progress progress-sm mb-1">
                            <div class="progress-bar bg-warning" style="width: <?= min(100, round($wf['workforce_ratio'] * 100)) ?>%"></div>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <?= number_format($wf['idle_workers']) ?> villageois inactifs disponibles pour les nouvelles extensions
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget 3: Jauge de Contentement Féodale -->
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm shadow-sm border-start border-3 border-<?= $ctColor ?> h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="avatar rounded bg-<?= $ctColor ?>-lt text-<?= $ctColor ?> fs-3">
                                <i class="fa-solid <?= ($score >= 75) ? 'fa-face-smile' : (($score >= 40) ? 'fa-face-meh' : 'fa-face-frown') ?>"></i>
                            </span>
                            <span class="badge bg-<?= $ctColor ?>-lt fw-bold"><?= $ct['status_label'] ?? 'Harmonie' ?></span>
                        </div>
                        <div class="h1 fw-bold text-<?= $ctColor ?> mb-1"><?= $score ?>% <small class="fs-4 text-muted">de satisfaction</small></div>
                        <div class="progress progress-sm mb-1">
                            <div class="progress-bar bg-<?= $ctColor ?>" style="width: <?= $score ?>%"></div>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            Saké : <strong><?= ($villageData['sake'] > 0) ? '<span class="text-success">+15% (Bonus actif)</span>' : '<span class="text-secondary">Neutre (0%)</span>' ?></strong> &bull; Farine : <?= number_format($villageData['rice_flour']) ?> kg
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scène Interactive Panoramique avec les 5 Slots Positionnés -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <div>
                <strong style="color:<?= $zDef['color'] ?>;"><i class="<?= $zDef['icon'] ?> me-1"></i><?= htmlspecialchars($zDef['name']) ?></strong>
                <span class="text-muted ms-2" style="font-size:0.85rem;">&bull; 5 Emplacements d'aménagement féodal</span>
            </div>
            <span class="text-muted" style="font-size:0.8rem;">
                <i class="fa-solid fa-crosshairs me-1"></i> Cliquez sur un repère pour cibler la parcelle
            </span>
        </div>
        <div class="card-body p-3">
            <div class="resource-scene-viewport">
                <!-- Badge Transparence IA (Haut Droite de la Scène Panoramique) -->
                <div style="position:absolute; top:12px; right:12px; z-index:25;">
                    <?= class_exists('AiPromptHelper') ? AiPromptHelper::renderBadge(basename($zDef['bg_image']), $zDef['name'] . ' (5 Slots)', $zDef['bg_image'], 'ai-prompt-badge-pill', true) : '' ?>
                </div>

                <!-- Les 5 Slots Positionnés Spatialement sur le Décor -->
                <?php foreach ($slots as $idx => $s): ?>
                    <?php
                        $pos = $s['spatial_pos'];
                        $isUp = !empty($s['is_upgrading']);
                    ?>
                    <div class="terroir-slot-pin"
                         id="pin-slot-<?= $idx ?>"
                         style="top: <?= $pos['top'] ?>%; left: <?= $pos['left'] ?>%;"
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<strong><?= htmlspecialchars($s['name']) ?></strong><br>Niveau <?= $s['level'] ?> &bull; <?= $s['workers'] ?> ouvriers<br>Cadence : +<?= number_format($s['prod_hourly']) ?>/h"
                         onclick="focusSlotCard(<?= $idx ?>)">
                        
                        <div class="slot-pin-beacon"></div>
                        <div class="slot-pin-card">
                            <span class="slot-pin-lvl <?= $isUp ? 'bg-warning animate-pulse' : '' ?>">
                                <?= $isUp ? '<i class="fa-solid fa-hourglass-half"></i>' : $s['level'] ?>
                            </span>
                            <span><?= htmlspecialchars($s['name']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Grille des 5 Cartes de Développement -->
            <div class="terroir-slots-grid">
                <?php foreach ($slots as $idx => $s): ?>
                    <?php
                        $cost = $s['cost'];
                        $isUp = !empty($s['is_upgrading']);
                        $canAfford = !empty($s['can_afford']);
                    ?>
                    <div class="card slot-card shadow-sm" id="card-slot-<?= $idx ?>">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar avatar-xs rounded bg-<?= $zDef['color_class'] ?>-lt text-<?= $zDef['color_class'] ?>">
                                    <i class="<?= $zDef['icon'] ?>"></i>
                                </span>
                                <div>
                                    <div class="fw-bold" style="font-size:0.85rem; line-height:1.2;"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">Slot #<?= $idx ?> <?= $s['field_slot'] ? '(Parcelle ' . $s['field_slot'] . ')' : '' ?></div>
                                </div>
                            </div>
                            <span class="badge bg-<?= $zDef['color_class'] ?> text-white fw-bold">
                                Niv. <?= $s['level'] ?>
                            </span>
                        </div>

                        <div class="card-body p-3">
                            <p class="text-muted mb-3" style="font-size:0.75rem; min-height:34px;"><?= htmlspecialchars($s['desc']) ?></p>

                            <!-- Métriques du Bâtiment -->
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded bg-light">
                                <div style="font-size:0.75rem;">
                                    <i class="fa-solid fa-person-digging text-warning me-1"></i>
                                    <strong><?= $s['workers'] ?></strong> <?= htmlspecialchars($s['worker_role']) ?>s
                                </div>
                                <div style="font-size:0.75rem;">
                                    <i class="fa-solid fa-arrow-trend-up text-success me-1"></i>
                                    <strong style="color:<?= $zDef['color'] ?>;">+<?= number_format($s['prod_hourly']) ?> / h</strong>
                                </div>
                            </div>

                            <!-- Coût d'élévation au niveau supérieur -->
                            <div class="mb-3">
                                <div class="text-muted mb-1" style="font-size:0.7rem; font-weight:600;">COÛT VERS NIVEAU <?= $s['level'] + 1 ?> :</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="cost-pill <?= ($planet['metal'] < $cost['metal']) ? 'text-danger' : 'text-dark' ?>">
                                        <i class="fa-solid fa-tree text-success"></i> <?= number_format($cost['metal']) ?>
                                    </span>
                                    <span class="cost-pill <?= ($planet['crystal'] < $cost['crystal']) ? 'text-danger' : 'text-dark' ?>">
                                        <i class="fa-solid fa-mountain text-secondary"></i> <?= number_format($cost['crystal']) ?>
                                    </span>
                                    <?php if ($cost['deuterium'] > 0): ?>
                                        <span class="cost-pill <?= ($planet['deuterium'] < $cost['deuterium']) ? 'text-danger' : 'text-dark' ?>">
                                            <i class="fa-solid fa-wheat-awn text-warning"></i> <?= number_format($cost['deuterium']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Bouton d'Action ou Statut Chantier -->
                            <?php if ($isUp): ?>
                                <div class="p-2 rounded bg-warning-lt text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1 fw-bold text-warning" style="font-size:0.8rem;">
                                        <i class="fa-solid fa-hourglass-half fa-spin"></i> Chantier en cours...
                                    </div>
                                </div>
                            <?php else: ?>
                                <button type="button"
                                        class="btn btn-sm w-100 <?= $canAfford ? 'btn-outline-' . $zDef['color_class'] : 'btn-light disabled' ?>"
                                        onclick="upgradeTerroirSlot('<?= $resourceType ?>', <?= $idx ?>)"
                                        <?= !$canAfford ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-circle-arrow-up me-1"></i>
                                    <?= $canAfford ? "Élever au Niveau " . ($s['level'] + 1) : "Ressources insuffisantes" ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function focusSlotCard(slotIdx) {
    const card = document.getElementById(`card-slot-${slotIdx}`);
    if (card) {
        document.querySelectorAll('.slot-card').forEach(c => c.classList.remove('highlighted'));
        card.classList.add('highlighted');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

async function upgradeTerroirSlot(resourceType, slotIdx) {
    try {
        const btn = event.currentTarget;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Amélioration...';
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
            // Rechargement fluide de la page pour actualiser les ressources et les slots
            window.location.reload();
        } else {
            alert(data.error || 'Impossible de lancer l\'amélioration de la parcelle.');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-arrow-up me-1"></i> Réessayer';
            }
        }
    } catch (err) {
        console.error('Erreur upgrade terroir :', err);
        alert('Une erreur réseau est survenue.');
    }
}
</script>
