<?php
/**
 * Vue Partielle MVC : Feature Flags & Roadmap (Producteur & Live Ops)
 * Fichier : views/gamestudio/liveops_features.php
 */
require_once __DIR__ . '/../../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();

$featureFlags = $liveOps->getFeatureFlags();
$activeCount = count(array_filter($featureFlags, fn($f) => !empty($f['enabled'])));
$inactiveCount = count($featureFlags) - $activeCount;

// Regroupement par catégorie
$groupedFlags = [];
foreach ($featureFlags as $flag) {
    $cat = $flag['category'] ?? 'Général';
    $groupedFlags[$cat][] = $flag;
}
?>

<div class="gamestudio-liveops-features">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-success d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-toggle-on text-success me-1"></i>Feature Flags &amp; Roadmap de Production
            </h3>
            <div class="text-secondary small mt-1">
                Activation à chaud (Kill Switches) des modules de jeu en direct et suivi de la feuille de route des versions.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-check me-1"></i><?= $activeCount ?> Actifs
            </span>
            <span class="badge bg-secondary-lt py-2 px-3 text-secondary">
                <i class="fa-solid fa-pause me-1"></i><?= $inactiveCount ?> Inactifs
            </span>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (isset($_SESSION['liveops_flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['liveops_flash']['type']) ?> alert-dismissible mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid <?= $_SESSION['liveops_flash']['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning' ?> fs-3"></i>
                <div><?= htmlspecialchars($_SESSION['liveops_flash']['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['liveops_flash']); ?>
    <?php endif; ?>

    <!-- ── EXPLICATION PRODUCTEUR & FEATURE FLAGS ── -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start gap-3">
            <span class="fs-1 text-info"><i class="fa-solid fa-lightbulb"></i></span>
            <div>
                <h4 class="alert-title mb-1 fw-bold">Principe du Déploiement Continu &amp; Feature Flipping</h4>
                <p class="text-secondary small mb-0">
                    Les <strong>Feature Flags</strong> permettent au Producteur d'activer ou désactiver une fonctionnalité majeure du jeu instantanément, sans interruption de service ni besoin de redéployer le code. Si une mécanique présente un bug critique en direct (Live Issue), désactivez son interrupteur pour préserver l'expérience des joueurs.
                </p>
            </div>
        </div>
    </div>

    <!-- ── SECTION 1 : GRILLE DES FEATURE FLAGS PAR CATÉGORIE ── -->
    <div class="row row-cards mb-4">
        <?php foreach ($groupedFlags as $categoryName => $flags): ?>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                        <h4 class="card-title text-dark fw-bold m-0" style="font-size:0.95rem;">
                            <i class="fa-solid fa-folder-open text-primary me-2"></i><?= htmlspecialchars($categoryName) ?>
                        </h4>
                        <span class="badge bg-secondary-lt"><?= count($flags) ?> module(s)</span>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($flags as $f): 
                            $isEnabled = !empty($f['enabled']);
                        ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-start gap-3" style="max-width:80%;">
                                        <span class="avatar avatar-sm rounded <?= $isEnabled ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' ?>" style="font-size:1.1rem; flex-shrink:0;">
                                            <i class="fa-solid <?= htmlspecialchars($f['icon'] ?? 'fa-cube') ?>"></i>
                                        </span>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <strong class="text-dark"><?= htmlspecialchars($f['label']) ?></strong>
                                                <span class="badge font-monospace" style="font-size:0.65rem; background:rgba(0,0,0,0.06); color:#475569;">
                                                    <?= htmlspecialchars($f['key']) ?>
                                                </span>
                                            </div>
                                            <div class="text-secondary small mt-1"><?= htmlspecialchars($f['description'] ?? '') ?></div>
                                            <?php if (!empty($f['updated_at'])): ?>
                                                <div class="text-muted" style="font-size:0.7rem; margin-top:0.25rem;">
                                                    <i class="fa-solid fa-clock me-1"></i>Modifié le <?= date('d/m/Y à H:i', strtotime($f['updated_at'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="toggle_feature_flag">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($f['key']) ?>">
                                            <input type="hidden" name="enabled" value="<?= $isEnabled ? '0' : '1' ?>">
                                            
                                            <?php if ($isEnabled): ?>
                                                <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1 shadow-sm" title="Cliquez pour couper ce module en direct">
                                                    <i class="fa-solid fa-toggle-on fs-3"></i>
                                                    <span class="fw-bold">Actif</span>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" title="Cliquez pour activer ce module en direct">
                                                    <i class="fa-solid fa-toggle-off fs-3"></i>
                                                    <span>Désactivé</span>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ── SECTION 2 : FEUILLE DE ROUTE & JALONS DE VERSION ── -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="card-title text-dark fw-bold m-0">
                    <i class="fa-solid fa-map text-primary me-2"></i>Roadmap de Production &amp; Suivi des Jalons
                </h4>
                <div class="text-secondary small">État d'avancement des versions et livraison des fonctionnalités clés.</div>
            </div>
            <span class="badge bg-indigo text-white font-monospace px-3 py-2">
                Version Actuelle : v<?= htmlspecialchars(defined('GAME_VERSION') ? GAME_VERSION : '1.33.2') ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Jalon / Release</th>
                        <th>Fonctionnalités &amp; Modules</th>
                        <th>Cible</th>
                        <th>Statut Producteur</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">v1.33.2</div>
                            <div class="text-muted small">Hotfix Plafond Castral</div>
                        </td>
                        <td>
                            <div>Blocage d'évolution des bâtiments au niveau max (Tenshu &amp; Forge 20)</div>
                            <div class="text-secondary small">Sécurisation backend &amp; bannières d'apogée impériale</div>
                        </td>
                        <td class="font-monospace text-muted small">Octobre 2026</td>
                        <td>
                            <span class="badge bg-success text-white"><i class="fa-solid fa-check me-1"></i>Livré en Live</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">v1.34.0</div>
                            <div class="text-muted small">Tour de Contrôle Producteur</div>
                        </td>
                        <td>
                            <div>Module Producteur &amp; Live Ops unifié dans Game Studio</div>
                            <div class="text-secondary small">Événements temporels, Broadcast flottant, Feature Flags &amp; Compensations</div>
                        </td>
                        <td class="font-monospace text-primary fw-bold small">En Cours</td>
                        <td>
                            <span class="badge bg-primary text-white"><i class="fa-solid fa-spinner fa-spin me-1"></i>En Recette QA</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">v1.35.0</div>
                            <div class="text-muted small">Chroniques Scénarisées</div>
                        </td>
                        <td>
                            <div>Narrative Designer Storyboard &amp; Timeline interactive</div>
                            <div class="text-secondary small">Modélisation visuelle des cinématiques, upload d'estampes et son</div>
                        </td>
                        <td class="font-monospace text-muted small">Prochain Sprint</td>
                        <td>
                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-hammer me-1"></i>En Développement</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">v1.36.0</div>
                            <div class="text-muted small">Guerres de Sièges de Clans</div>
                        </td>
                        <td>
                            <div>Pactes d'alliances avancés, conquête des Forteresses Capitales</div>
                            <div class="text-secondary small">Événement mondial de fin d'ère Shōgunale</div>
                        </td>
                        <td class="font-monospace text-muted small">T4 2026</td>
                        <td>
                            <span class="badge bg-secondary-lt text-secondary"><i class="fa-solid fa-lightbulb me-1"></i>Conception</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
