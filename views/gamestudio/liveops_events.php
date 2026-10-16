<?php
/**
 * Vue Partielle MVC : Événements Live & Saisons (Producteur & Live Ops)
 * Fichier : views/gamestudio/liveops_events.php
 */
require_once __DIR__ . '/../../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();

$activeEvents = $liveOps->getActiveLiveEvents();
$allEvents = $liveOps->getAllLiveEvents();
$presets = $liveOps->getEventPresets();
$boosts = $liveOps->getActiveGlobalBoosts();
?>

<div class="gamestudio-liveops-events">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-warning d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-calendar-star text-warning me-1"></i>Événements Live &amp; Saisons Féodales
            </h3>
            <div class="text-secondary small mt-1">
                Pilotage en direct des fêtes du Shōgunat, bénédictions d'univers temporaires et multiplicateurs globaux.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($activeEvents)): ?>
                <span class="badge bg-success-lt py-2 px-3 fw-bold d-inline-flex align-items-center gap-2">
                    <span class="status-dot status-dot-animated bg-success"></span>
                    <span><?= count($activeEvents) ?> Événement(s) Actif(s)</span>
                </span>
            <?php else: ?>
                <span class="badge bg-secondary-lt py-2 px-3 text-secondary">
                    <i class="fa-solid fa-moon me-1"></i>Période de Paix (Aucun boost actif)
                </span>
            <?php endif; ?>
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

    <!-- ── RÉSUMÉ DES MULTIPLICATEURS GLOBAUX EN COURS ── -->
    <div class="card mb-4 border-0 shadow-sm bg-dark text-white">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-4 border-end-lg border-secondary">
                    <div class="text-secondary text-uppercase small fw-bold">État des Bénédictions d'Univers</div>
                    <div class="h2 text-white m-0 mt-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-torii-gate text-warning"></i>
                        <span><?= !empty($activeEvents) ? 'Bénédictions Actives' : 'Ordre Naturel' ?></span>
                    </div>
                    <div class="text-muted small mt-2">
                        <?= !empty($activeEvents)
                            ? 'Les Daimyōs profitent actuellement des faveurs impériales sur leurs récoltes et garnisons.'
                            : 'Les productions, temps de travaux et vitesses de marche s\'exécutent selon les constantes de base.' ?>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="row g-2 text-center">
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Récoltes</div>
                                <div class="h3 m-0 <?= $boosts['prod_boost_pct'] > 0 ? 'text-success fw-bold' : 'text-muted' ?>">
                                    <?= $boosts['prod_boost_pct'] > 0 ? '+' . $boosts['prod_boost_pct'] . '%' : 'Normal' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Chantiers</div>
                                <div class="h3 m-0 <?= $boosts['build_speed_pct'] > 0 ? 'text-primary fw-bold' : 'text-muted' ?>">
                                    <?= $boosts['build_speed_pct'] > 0 ? '+' . $boosts['build_speed_pct'] . '%' : 'Normal' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Dojo / Troupes</div>
                                <div class="h3 m-0 <?= $boosts['train_speed_pct'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= $boosts['train_speed_pct'] > 0 ? '+' . $boosts['train_speed_pct'] . '%' : 'Normal' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Marche</div>
                                <div class="h3 m-0 <?= $boosts['march_speed_pct'] > 0 ? 'text-info fw-bold' : 'text-muted' ?>">
                                    <?= $boosts['march_speed_pct'] > 0 ? '+' . $boosts['march_speed_pct'] . '%' : 'Normal' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Honneur</div>
                                <div class="h3 m-0 <?= $boosts['honor_gain_pct'] > 0 ? 'text-warning fw-bold' : 'text-muted' ?>">
                                    <?= $boosts['honor_gain_pct'] > 0 ? '+' . $boosts['honor_gain_pct'] . '%' : 'Normal' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="p-2 rounded bg-dark-lt border border-secondary">
                                <div class="text-secondary small">Événements</div>
                                <div class="h3 m-0 text-white fw-bold"><?= count($activeEvents) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SECTION 1 : ÉVÉNEMENTS ACTIFS ── -->
    <?php if (!empty($activeEvents)): ?>
        <div class="card mb-4 border-success shadow-sm">
            <div class="card-header bg-success-lt d-flex justify-content-between align-items-center">
                <h4 class="card-title text-success m-0 fw-bold">
                    <i class="fa-solid fa-bolt me-1 text-warning"></i>Événements Temporels en Cours de Déroulement
                </h4>
                <span class="badge bg-success text-white"><?= count($activeEvents) ?> en ligne</span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($activeEvents as $ev): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-md rounded shadow-sm" style="background:<?= htmlspecialchars($ev['color'] ?? '#3b82f6') ?>; color:#fff; font-size:1.5rem;">
                                    <i class="fa-solid <?= htmlspecialchars($ev['icon'] ?? 'fa-bolt') ?>"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h4 class="m-0 text-dark fw-bold"><?= htmlspecialchars($ev['title']) ?></h4>
                                        <?php if (!empty($ev['jp_title'])): ?>
                                            <span class="text-secondary small font-japanese"><?= htmlspecialchars($ev['jp_title']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge bg-success text-white">Actif</span>
                                    </div>
                                    <div class="text-secondary small mt-1"><?= htmlspecialchars($ev['description'] ?? '') ?></div>
                                    <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                                        <?php foreach ($ev['multipliers'] ?? [] as $mKey => $mVal): if ((int)$mVal > 0): ?>
                                            <span class="badge bg-primary-lt">
                                                <?= match($mKey) {
                                                    'prod_boost_pct'  => '🌲 Récoltes +' . $mVal . '%',
                                                    'build_speed_pct' => '🔨 Chantiers +' . $mVal . '%',
                                                    'train_speed_pct' => '⚔️ Entraînement +' . $mVal . '%',
                                                    'march_speed_pct' => '🐎 Marche +' . $mVal . '%',
                                                    'honor_gain_pct'  => '🎖️ Honneur +' . $mVal . '%',
                                                    default           => $mKey . ' +' . $mVal . '%'
                                                } ?>
                                            </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-end">
                                    <div class="text-muted small">Prend fin le :</div>
                                    <div class="font-monospace fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($ev['ends_at'])) ?></div>
                                    <div class="text-warning small"><i class="fa-solid fa-hourglass-half me-1"></i><?= gmdate('H\h i\m', $ev['remaining_seconds']) ?> restants</div>
                                </div>
                                <form method="POST" onsubmit="return confirm('Voulez-vous vraiment clore prématurément cet événement impérial ?');">
                                    <input type="hidden" name="action" value="stop_live_event">
                                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($ev['id']) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fa-solid fa-stop me-1"></i>Clore l'Événement
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ── SECTION 2 : DÉCLENCHEUR D'ÉVÉNEMENT (PRESETS & SUR MESURE) ── -->
    <div class="row row-cards mb-4">
        <!-- 1. Presets Thématiques Immédiats (1 clic) -->
        <div class="col-lg-5">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="card-title m-0 text-dark fw-bold">
                        <i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i>Modèles d'Événements Thématiques
                    </h4>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted small mb-3">Sélectionnez un modèle féodal préconfiguré pour lancer instantanément un événement officiel :</p>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($presets as $pSlug => $preset): ?>
                            <div class="border rounded p-3 hover-shadow transition" style="background:#f8fafc;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm rounded text-white" style="background:<?= $preset['color'] ?>;">
                                            <i class="fa-solid <?= $preset['icon'] ?>"></i>
                                        </span>
                                        <div>
                                            <strong class="text-dark"><?= htmlspecialchars($preset['title']) ?></strong>
                                            <div class="text-secondary small font-japanese"><?= htmlspecialchars($preset['jp_title']) ?></div>
                                        </div>
                                    </div>
                                    <span class="badge <?= $preset['badge_class'] ?>">Preset</span>
                                </div>
                                <p class="text-secondary small mb-2"><?= htmlspecialchars($preset['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php foreach ($preset['multipliers'] as $mk => $mv): if ($mv > 0): ?>
                                            <span class="badge bg-secondary-lt" style="font-size:0.7rem;"><?= str_replace('_pct', '', $mk) ?> +<?= $mv ?>%</span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="trigger_live_event">
                                        <input type="hidden" name="slug" value="<?= $pSlug ?>">
                                        <input type="hidden" name="title" value="<?= htmlspecialchars($preset['title']) ?>">
                                        <input type="hidden" name="jp_title" value="<?= htmlspecialchars($preset['jp_title']) ?>">
                                        <input type="hidden" name="icon" value="<?= htmlspecialchars($preset['icon']) ?>">
                                        <input type="hidden" name="color" value="<?= htmlspecialchars($preset['color']) ?>">
                                        <input type="hidden" name="description" value="<?= htmlspecialchars($preset['description']) ?>">
                                        <input type="hidden" name="duration_hours" value="24">
                                        <?php foreach ($preset['multipliers'] as $mk => $mv): ?>
                                            <input type="hidden" name="<?= $mk ?>" value="<?= (int)$mv ?>">
                                        <?php endforeach; ?>
                                        <input type="hidden" name="broadcast_announcement" value="1">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-play me-1"></i>Lancer 24h
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Formulaire Événement Sur-Mesure -->
        <div class="col-lg-7">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light">
                    <h4 class="card-title m-0 text-dark fw-bold">
                        <i class="fa-solid fa-sliders text-warning me-1"></i>Décréter un Événement Sur-Mesure
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="trigger_live_event">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label required">Titre de l'Événement</label>
                                <input type="text" class="form-control" name="title" placeholder="Ex: Grand Tournoi des Trois Rives" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Titre Japonais (Optionnel)</label>
                                <input type="text" class="form-control font-japanese" name="jp_title" placeholder="Ex: 三川大武芸会">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proclamation Narrative</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Décrivez le contexte historique féodal et l'effet accordé aux Daimyōs..."></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Durée de l'Événement</label>
                                <select class="form-select" name="duration_hours">
                                    <option value="2">⚡ 2 Heures (Flash)</option>
                                    <option value="12">12 Heures</option>
                                    <option value="24" selected>24 Heures (1 Journée)</option>
                                    <option value="48">48 Heures (Week-end)</option>
                                    <option value="72">72 Heures (3 Jours)</option>
                                    <option value="168">7 Jours (Semaine)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Icône FontAwesome</label>
                                <select class="form-select font-monospace" name="icon">
                                    <option value="fa-bolt">fa-bolt (Éclair)</option>
                                    <option value="fa-wheat-awn">fa-wheat-awn (Riz / Moisson)</option>
                                    <option value="fa-horse">fa-horse (Cavalerie)</option>
                                    <option value="fa-hammer">fa-hammer (Charpente)</option>
                                    <option value="fa-fire-burner">fa-fire-burner (Forge)</option>
                                    <option value="fa-shield-halved">fa-shield-halved (Défense)</option>
                                    <option value="fa-trophy">fa-trophy (Tournoi)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Couleur Thématique</label>
                                <select class="form-select" name="color">
                                    <option value="#3b82f6">Bleu Impérial (#3b82f6)</option>
                                    <option value="#ef4444">Rouge Écarlate Takeda (#ef4444)</option>
                                    <option value="#22c55e">Vert Pin Naturel (#22c55e)</option>
                                    <option value="#eab308">Or / Tamahagane (#eab308)</option>
                                    <option value="#8b5cf6">Pourpre Tokugawa (#8b5cf6)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Multiplicateurs -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="fw-bold text-dark small mb-2 text-uppercase">Multiplicateurs de Jeu (%)</div>
                            <div class="row g-2">
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-muted">Boost Récoltes (%)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" class="form-control" name="prod_boost_pct" value="25" min="0" max="200">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-muted">Vitesse Chantiers (%)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" class="form-control" name="build_speed_pct" value="15" min="0" max="200">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-muted">Vitesse Troupes (%)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" class="form-control" name="train_speed_pct" value="20" min="0" max="200">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-muted">Vitesse Marche (%)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" class="form-control" name="march_speed_pct" value="30" min="0" max="200">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-muted">Gain d'Honneur (%)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+</span>
                                        <input type="number" class="form-control" name="honor_gain_pct" value="25" min="0" max="200">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="broadcast_announcement" id="chkBroadcast" value="1" checked>
                            <label class="form-check-label small" for="chkBroadcast">
                                Diffuser immédiatement une bannière d'annonce en haut d'écran pour tous les Daimyōs connectés
                            </label>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="fa-solid fa-bullhorn me-1"></i>Proclamer l'Événement &amp; Activer les Multiplicateurs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SECTION 3 : HISTORIQUE DES ÉVÉNEMENTS PASSÉS & ARCHIVES ── -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h4 class="card-title m-0 text-dark fw-bold">
                <i class="fa-solid fa-clock-rotate-left text-secondary me-1"></i>Chroniques des Événements &amp; Archives Live Ops
            </h4>
            <span class="badge bg-secondary-lt"><?= count($allEvents) ?> total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Événement</th>
                        <th>Multiplicateurs Actifs</th>
                        <th>Période</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allEvents)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fa-solid fa-calendar-xmark fs-2 d-block mb-2"></i>
                                Aucun événement live n'a encore été proclamé. Utilisez les modèles ci-dessus pour lancer votre premier événement !
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($allEvents, 0, 15) as $ev):
                            $isAct = !empty($ev['is_active']) && (strtotime($ev['ends_at']) > time());
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-xs rounded text-white" style="background:<?= htmlspecialchars($ev['color'] ?? '#3b82f6') ?>;">
                                            <i class="fa-solid <?= htmlspecialchars($ev['icon'] ?? 'fa-bolt') ?>" style="font-size:0.75rem;"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($ev['title']) ?></div>
                                            <div class="text-secondary small font-japanese"><?= htmlspecialchars($ev['jp_title'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php foreach ($ev['multipliers'] ?? [] as $mk => $mv): if ((int)$mv > 0): ?>
                                            <span class="badge bg-secondary-lt font-monospace" style="font-size:0.7rem;">
                                                <?= match($mk) {
                                                    'prod_boost_pct'  => '🌲+' . $mv . '%',
                                                    'build_speed_pct' => '🔨+' . $mv . '%',
                                                    'train_speed_pct' => '⚔️+' . $mv . '%',
                                                    'march_speed_pct' => '🐎+' . $mv . '%',
                                                    'honor_gain_pct'  => '🎖️+' . $mv . '%',
                                                    default           => '+' . $mv . '%'
                                                } ?>
                                            </span>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </td>
                                <td class="font-monospace small">
                                    <?= date('d/m H:i', strtotime($ev['starts_at'])) ?> &rarr; <?= date('d/m H:i', strtotime($ev['ends_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($isAct): ?>
                                        <span class="badge bg-success text-white">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white">Terminé</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

