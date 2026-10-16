<?php
/**
 * Vue Partielle MVC : Broadcast In-Game & Mode Maintenance (Producteur & Live Ops)
 * Fichier : views/gamestudio/liveops_broadcast.php
 */
require_once __DIR__ . '/../../core/LiveOpsEngine.php';
$liveOps = new LiveOpsEngine();

$activeBroadcast = $liveOps->getActiveBroadcast();
$maintenanceStatus = $liveOps->getMaintenanceStatus();
$isMaintenance = !empty($maintenanceStatus['is_active']);
?>

<div class="gamestudio-liveops-broadcast">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-danger d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-bullhorn text-danger me-1"></i>Broadcast Flottant &amp; Mode Maintenance
            </h3>
            <div class="text-secondary small mt-1">
                Communication d'urgence, bannières in-game défilantes et verrouillage sécurisé de la forteresse.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($isMaintenance): ?>
                <span class="badge bg-danger text-white py-2 px-3 fw-bold d-inline-flex align-items-center gap-2">
                    <span class="status-dot status-dot-animated bg-white"></span>
                    <span>Mode Maintenance ACTIF</span>
                </span>
            <?php else: ?>
                <span class="badge bg-success-lt py-2 px-3 fw-bold text-success">
                    <i class="fa-solid fa-circle-check me-1"></i>Jeu En Ligne (Normal)
                </span>
            <?php endif; ?>

            <?php if ($activeBroadcast): ?>
                <span class="badge bg-warning-lt py-2 px-3 text-warning fw-bold">
                    <i class="fa-solid fa-tower-broadcast me-1"></i>Bannière Live Active
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

    <div class="row row-cards mb-4">
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE 1 : BANNIÈRE BROADCAST FLOTTANTE EN DIRECT                 -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark fw-bold m-0">
                        <i class="fa-solid fa-tower-broadcast text-primary me-2"></i>Bannière Flottante In-Game (Broadcast)
                    </h4>
                    <?php if ($activeBroadcast): ?>
                        <span class="badge bg-success text-white">En Ligne</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-lt">Inactif</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary small mb-3">
                        Diffuse un message immédiat en tête de page pour <strong>tous les joueurs connectés</strong> sur le terroir, la cité et la carte du Japon féodal.
                    </p>

                    <!-- Aperçu en direct si active -->
                    <?php if ($activeBroadcast): ?>
                        <div class="p-3 mb-4 rounded border bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-uppercase text-secondary">Aperçu Réel en Jeu :</span>
                                <span class="text-muted small">Expire le <?= date('d/m/Y à H:i', strtotime($activeBroadcast['expires_at'])) ?></span>
                            </div>
                            <div class="alert alert-<?= htmlspecialchars($activeBroadcast['type']) ?> d-flex align-items-center justify-content-between m-0 p-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-2"><i class="fa-solid <?= htmlspecialchars($activeBroadcast['icon']) ?>"></i></span>
                                    <div>
                                        <strong><?= htmlspecialchars($activeBroadcast['title']) ?> :</strong>
                                        <span><?= htmlspecialchars($activeBroadcast['message']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clear_broadcast">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash-can me-1"></i>Révoquer la Bannière Immédiatement
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire d'envoi d'une bannière -->
                    <form method="POST">
                        <input type="hidden" name="action" value="set_broadcast">

                        <div class="mb-3">
                            <label class="form-label required">Titre du Message</label>
                            <input type="text" class="form-control" name="title" placeholder="Ex: ⚡ Maintenance programmée à 14h00" value="<?= htmlspecialchars($activeBroadcast['title'] ?? 'Dépêche Impériale') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Corps de l'Annonce</label>
                            <textarea class="form-control" name="message" rows="3" placeholder="Ex: Une mise à jour du moteur tactique aura lieu dans 15 minutes. Vos convois et chantiers sont préservés." required><?= htmlspecialchars($activeBroadcast['message'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Type &amp; Couleur</label>
                                <select class="form-select" name="type">
                                    <option value="info" <?= ($activeBroadcast['type'] ?? '') === 'info' ? 'selected' : '' ?>>Bleu Impérial (Information)</option>
                                    <option value="warning" <?= ($activeBroadcast['type'] ?? '') === 'warning' ? 'selected' : '' ?>>Jaune / Ambre (Alerte / Événement)</option>
                                    <option value="danger" <?= ($activeBroadcast['type'] ?? '') === 'danger' ? 'selected' : '' ?>>Rouge Écarlate (Urgence / Coupure)</option>
                                    <option value="success" <?= ($activeBroadcast['type'] ?? '') === 'success' ? 'selected' : '' ?>>Vert Cédre (Succès / Récompense)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Durée d'Affichage</label>
                                <select class="form-select" name="duration_minutes">
                                    <option value="15">15 Minutes</option>
                                    <option value="30">30 Minutes</option>
                                    <option value="60" selected>1 Heure</option>
                                    <option value="120">2 Heures</option>
                                    <option value="360">6 Heures</option>
                                    <option value="1440">24 Heures (1 Jour)</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i>Diffuser la Bannière In-Game
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE 2 : INTERRUPTEUR DE MODE MAINTENANCE                       -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0 <?= $isMaintenance ? 'border-danger border-2' : '' ?>">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="card-title text-dark fw-bold m-0">
                        <i class="fa-solid fa-lock text-danger me-2"></i>Interrupteur Mode Maintenance
                    </h4>
                    <?php if ($isMaintenance): ?>
                        <span class="badge bg-danger text-white">VERROUILLÉ</span>
                    <?php else: ?>
                        <span class="badge bg-success text-white">OUVERT</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary small mb-3">
                        Permet de verrouiller l'accès au jeu pendant les migrations lourdes, sauvegardes ou interventions d'urgence. 
                        <strong>Les Administrateurs et membres whitelistés continuent à naviguer sans restriction.</strong>
                    </p>

                    <form method="POST">
                        <input type="hidden" name="action" value="set_maintenance">

                        <div class="mb-3 p-3 rounded border <?= $isMaintenance ? 'bg-danger-lt border-danger' : 'bg-light' ?>">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_active" id="chkMaintenance" value="1" <?= $isMaintenance ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label fw-bold <?= $isMaintenance ? 'text-danger' : 'text-dark' ?>" for="chkMaintenance" style="font-size:1.05rem;">
                                    <?= $isMaintenance ? '⚠️ Le Mode Maintenance est ACTIF' : 'Activer le Mode Maintenance' ?>
                                </label>
                            </div>
                            <div class="text-secondary small mt-1">
                                <?= $isMaintenance 
                                    ? 'Tous les joueurs réguliers sont redirigés vers l\'écran de maintenance thématique du Shōgunat.'
                                    : 'Le jeu est accessible normalement à tous les Daimyōs enregistrés.' ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message d'Attente Féodal</label>
                            <textarea class="form-control" name="message" rows="3"><?= htmlspecialchars($maintenanceStatus['message'] ?? 'Le Conseil de Guerre impérial procède à des ajustements techniques sur les forteresses. Réouverture imminente.') ?></textarea>
                            <div class="form-text text-muted">Ce texte sera affiché sur la page de maintenance vue par les joueurs.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Heure de Réouverture Prévue</label>
                                <input type="text" class="form-control" name="scheduled_end" placeholder="Ex: Aujourd'hui à 15h30" value="<?= htmlspecialchars($maintenanceStatus['scheduled_end'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">IDs Utilisateurs Autorisés (Whitelist)</label>
                                <input type="text" class="form-control font-monospace" name="whitelist" placeholder="Ex: 1, 2, 5" value="<?= implode(', ', $maintenanceStatus['whitelisted_users'] ?? [$userId]) ?>">
                                <div class="form-text text-muted">Séparez les IDs par une virgule.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn <?= $isMaintenance ? 'btn-danger' : 'btn-outline-primary' ?> w-100 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer les Paramètres de Maintenance
                        </button>
                    </form>

                    <!-- Aperçu visuel de l'écran de maintenance -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="fw-bold small text-secondary text-uppercase mb-2">Aperçu de l'Écran de Maintenance :</div>
                        <div class="p-3 text-center rounded border bg-dark text-white">
                            <div style="font-size:2rem; color:#ef4444;"><i class="fa-solid fa-torii-gate"></i></div>
                            <h5 class="text-white fw-bold mt-2 mb-1">Le Conseil du Shōgunat Délibère</h5>
                            <p class="text-muted small m-0" style="max-width:380px; margin:0 auto !important;">
                                « <?= htmlspecialchars($maintenanceStatus['message'] ?? '') ?> »
                            </p>
                            <?php if (!empty($maintenanceStatus['scheduled_end'])): ?>
                                <span class="badge bg-warning text-dark mt-2">Réouverture estimée : <?= htmlspecialchars($maintenanceStatus['scheduled_end']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

