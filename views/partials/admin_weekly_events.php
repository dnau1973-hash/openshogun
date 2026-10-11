<?php
/**
 * Module d'Administration : Gestion des Chroniques & Événements Hebdomadaires
 * OpenShogun - Création, planification et configuration des actes narratifs féodaux.
 */

require_once __DIR__ . '/../../core/WeeklyEventEngine.php';

$adminEventEngine = new WeeklyEventEngine();
$allEvents = $adminEventEngine->getAllEvents();
?>

<div class="card shadow-sm border-0">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 bg-dark text-white">
        <div>
            <h3 class="card-title mb-0 d-flex align-items-center gap-2 fs-2">
                <i class="fa-solid fa-scroll text-warning"></i>
                <span>Chroniques Féodales Hebdomadaires</span>
            </h3>
            <div class="text-muted small mt-1 font-monospace">
                Créez, planifiez et gérez les récits historiques du Sengoku et les chaînes de quêtes hebdomadaires.
            </div>
        </div>
        <div>
            <button type="button" class="btn btn-warning fw-bold font-monospace shadow-sm" onclick="openCreateEventModal()">
                <i class="fa-solid fa-plus me-1"></i>Créer une Chronique
            </button>
        </div>
    </div>

    <div class="card-body p-3">
        <?php if (empty($allEvents)): ?>
            <div class="alert alert-info py-4 text-center">
                <i class="fa-solid fa-feather-pointed fs-1 mb-2 text-warning"></i>
                <h4>Aucune chronique hebdomadaire enregistrée</h4>
                <p class="text-muted mb-3">Initialisez les 4 chroniques historiques pré-configurées ou créez-en une nouvelle.</p>
                <a href="/database/migrate_weekly_events.php" target="_blank" class="btn btn-primary font-monospace">
                    <i class="fa-solid fa-database me-1"></i>Amorcer les Chroniques Historiques
                </a>
            </div>
        <?php else: ?>
            <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
                <div class="input-icon flex-grow-1" style="min-width: 250px;">
                    <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass text-warning"></i></span>
                    <input type="text" id="adminWeeklyEventFilter" class="form-control" placeholder="Rechercher par semaine, nom, slug ou période..." onkeyup="filterAdminWeeklyEvents()">
                </div>
                <span class="badge bg-dark font-monospace fs-4 text-nowrap py-2 px-3">
                    <i class="fa-solid fa-scroll text-warning me-1"></i><?= count($allEvents) ?> Chroniques enregistrées
                </span>
            </div>

            <div class="row g-3">
                <?php foreach ($allEvents as $ev): 
                    $evId = (int)$ev['id'];
                    $isActive = !empty($ev['is_active']);
                    $tasks = $adminEventEngine->getEventDetails($evId)['tasks'] ?? [];
                ?>
                    <div class="col-12 col-xl-6 admin-weekly-event-col">
                        <div class="card h-100 border <?= $isActive ? 'border-warning shadow' : 'border-secondary-subtle' ?>" style="background: <?= $isActive ? 'rgba(234, 179, 8, 0.04)' : '#ffffff' ?>;">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-<?= $isActive ? 'warning text-dark' : 'secondary-lt text-secondary' ?> font-monospace fw-bold">
                                        <?= $isActive ? '<i class="fa-solid fa-circle-play me-1"></i>ACTIF' : 'PROGRAMMÉ' ?>
                                    </span>
                                    <h4 class="card-title mb-0 fs-3 fw-bold"><?= htmlspecialchars($ev['title']) ?></h4>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-warning font-monospace fw-bold shadow-sm" onclick="editEvent(<?= $evId ?>)" title="Modifier les textes, dates, image et récompenses de cette chronique">
                                        <i class="fa-solid fa-pen-to-square me-1"></i>Éditer
                                    </button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Plus
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow">
                                            <?php if (!$isActive): ?>
                                                <li>
                                                    <a class="dropdown-item text-success font-monospace" href="javascript:void(0)" onclick="setEventActive(<?= $evId ?>)">
                                                        <i class="fa-solid fa-play me-2"></i>Activer maintenant
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item font-monospace" href="javascript:void(0)" onclick="editEvent(<?= $evId ?>)">
                                                    <i class="fa-solid fa-pen-to-square me-2"></i>Modifier la chronique
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item font-monospace text-primary" href="javascript:void(0)" onclick="openAddTaskModal(<?= $evId ?>)">
                                                    <i class="fa-solid fa-plus me-2"></i>Ajouter un Acte narratif
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger font-monospace" href="javascript:void(0)" onclick="deleteEvent(<?= $evId ?>, '<?= addslashes(htmlspecialchars($ev['title'])) ?>')">
                                                    <i class="fa-solid fa-trash me-2"></i>Supprimer la chronique
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-3">
                                <div class="d-flex gap-3 mb-3">
                                    <div class="rounded border shadow-sm flex-shrink-0 position-relative" style="width: 120px; height: 85px; background-image: url('<?= htmlspecialchars($ev['image_url']) ?>'); background-size: cover; background-position: center; cursor: pointer;" onclick="editEvent(<?= $evId ?>)" title="Cliquer pour éditer cette chronique">
                                        <span class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-warning font-monospace text-center py-0.5" style="font-size: 0.65rem;">
                                            <i class="fa-solid fa-pen me-0.5"></i>Éditer
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 font-monospace text-muted small mb-1 flex-wrap">
                                            <span class="text-primary fw-bold"><i class="fa-solid fa-calendar me-1"></i><?= htmlspecialchars($ev['era_year']) ?></span>
                                            <span>&bull;</span>
                                            <span>Du <?= date('d/m/Y', strtotime($ev['starts_at'])) ?> au <?= date('d/m/Y', strtotime($ev['ends_at'])) ?></span>
                                            <?php if (!empty($ev['jp_title'])): ?>
                                                <span class="badge bg-dark text-warning border border-warning-subtle font-monospace py-0 px-1"><?= htmlspecialchars($ev['jp_title']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-secondary small mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            <?= htmlspecialchars($ev['description']) ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Liste des Actes Narratifs -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 fw-bold font-monospace small text-uppercase text-secondary">
                                            <i class="fa-solid fa-list-check me-1 text-warning"></i>Actes de la Chronique (<?= count($tasks) ?>) :
                                        </label>
                                        <button type="button" class="btn btn-sm btn-ghost-primary font-monospace py-0 px-1" onclick="openAddTaskModal(<?= $evId ?>)">
                                            <i class="fa-solid fa-plus me-0.5"></i>Ajouter Acte
                                        </button>
                                    </div>
                                    <div class="list-group list-group-flush border rounded">
                                        <?php if (empty($tasks)): ?>
                                            <div class="list-group-item text-muted small py-2 font-monospace">Aucun acte configuré pour le moment.</div>
                                        <?php else: ?>
                                            <?php foreach ($tasks as $t): ?>
                                                <div class="list-group-item py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-1">
                                                    <div>
                                                        <span class="badge bg-secondary-lt font-monospace me-1">Acte <?= $t['step_order'] ?></span>
                                                        <strong class="text-dark small"><?= htmlspecialchars($t['title']) ?></strong>
                                                        <span class="text-muted font-monospace small ms-1">(<?= htmlspecialchars($t['task_type']) ?> : <?= htmlspecialchars($t['target_key']) ?> &ge; <?= number_format((int)$t['target_amount']) ?>)</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-ghost-primary py-0 px-1" onclick='editTask(<?= json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' title="Modifier cet acte narratif">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-ghost-danger py-0 px-1" onclick="deleteTask(<?= (int)$t['id'] ?>)" title="Supprimer cet acte">
                                                            <i class="fa-solid fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Récompenses de Clôture -->
                                <div class="p-2 rounded bg-surface-secondary border d-flex justify-content-between align-items-center flex-wrap gap-1 font-monospace small">
                                    <span class="text-muted"><i class="fa-solid fa-trophy text-warning me-1"></i>Récompense finale :</span>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php if (!empty($ev['reward_final_gold'])): ?>
                                            <span class="badge bg-warning-lt text-warning fw-bold">+<?= (int)$ev['reward_final_gold'] ?> Or</span>
                                        <?php endif; ?>
                                        <?php if (!empty($ev['reward_final_honor'])): ?>
                                            <span class="badge bg-danger-lt text-danger fw-bold">+<?= (int)$ev['reward_final_honor'] ?> Honneur</span>
                                        <?php endif; ?>
                                        <?php if (!empty($ev['reward_final_seal_days'])): ?>
                                            <span class="badge bg-info-lt text-info fw-bold">+<?= (int)$ev['reward_final_seal_days'] ?>j Sceau</span>
                                        <?php endif; ?>
                                        <?php if (!empty($ev['reward_final_title'])): ?>
                                            <span class="badge bg-teal-lt text-teal fw-bold">« <?= htmlspecialchars($ev['reward_final_title']) ?> »</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================
     MODALE DE CRÉATION / ÉDITION D'UNE CHRONIQUE
     ======================================================== -->
<div class="modal fade" id="modalEditWeeklyEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formWeeklyEvent" onsubmit="saveEventSubmit(event)">
                <input type="hidden" name="id" id="edit_event_id" value="0">
                <div class="modal-header bg-dark text-white py-2">
                    <h4 class="modal-title fs-3 d-flex align-items-center gap-2 mb-0">
                        <i class="fa-solid fa-scroll text-warning"></i>
                        <span id="modalEventTitleLabel">Créer une Chronique Féodale</span>
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small">Titre de la Chronique :</label>
                            <input type="text" name="title" id="edit_event_title" class="form-control" required placeholder="Ex: L'Alerte d'Okehazama">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Identifiant Unique (Slug) :</label>
                            <input type="text" name="slug" id="edit_event_slug" class="form-control font-monospace" placeholder="okehazama_1560">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Titre Japonais / Kanji :</label>
                            <input type="text" name="jp_title" id="edit_event_jp_title" class="form-control font-monospace" placeholder="桶狭間の奇襲">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Période / Ère Historique :</label>
                            <input type="text" name="era_year" id="edit_event_era_year" class="form-control" placeholder="Juin 1560 — Ère Eiroku">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Récit Historique Narratif (Immersion Sengoku) :</label>
                            <textarea name="description" id="edit_event_description" rows="4" class="form-control" required placeholder="Racontez le contexte de cette page d'Histoire réelle..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small">Image d'illustration (Format 16:9 recommandé) :</label>
                            <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                <input type="text" name="image_url" id="edit_event_image_url" class="form-control font-monospace flex-grow-1" value="/public/assets/buildings/hq.jpg" oninput="updateEditImagePreview(this.value)">
                                <select class="form-select font-monospace" style="max-width: 320px;" onchange="if(this.value){ document.getElementById('edit_event_image_url').value = this.value; updateEditImagePreview(this.value); }">
                                    <option value="">-- Choisir une illustration (Semaines 01 à 52) --</option>
                                    <?php for ($w = 1; $w <= 52; $w++): 
                                        $wStr = sprintf('%02d', $w);
                                        $imgPath = "/public/assets/events/event_week_{$wStr}.jpg";
                                    ?>
                                        <option value="<?= $imgPath ?>">Semaine <?= $wStr ?> (event_week_<?= $wStr ?>.jpg)</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="p-2 rounded bg-dark border text-center">
                                <div class="text-muted small font-monospace mb-1"><i class="fa-solid fa-eye me-1"></i>Aperçu en direct de l'illustration :</div>
                                <img id="edit_event_image_preview" src="/public/assets/buildings/hq.jpg" alt="Aperçu" style="max-height: 190px; width: auto; max-width: 100%; border-radius: 6px; object-fit: contain;" onerror="this.src='/public/assets/buildings/hq.jpg';">
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Date de début :</label>
                            <input type="datetime-local" name="starts_at" id="edit_event_starts_at" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Date de clôture :</label>
                            <input type="datetime-local" name="ends_at" id="edit_event_ends_at" class="form-control" required>
                        </div>
                    </div>

                    <div class="card p-2 mb-3 bg-surface-secondary border">
                        <label class="form-label fw-bold small text-warning mb-2"><i class="fa-solid fa-crown me-1"></i>Grande Récompense de Clôture :</label>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Pièces d'Or :</label>
                                <input type="number" name="reward_final_gold" id="edit_event_reward_gold" class="form-control text-center" value="50" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Points d'Honneur :</label>
                                <input type="number" name="reward_final_honor" id="edit_event_reward_honor" class="form-control text-center" value="100" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Jours Sceau Impérial :</label>
                                <input type="number" name="reward_final_seal_days" id="edit_event_reward_seal" class="form-control text-center" value="1" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Titre Honorifique :</label>
                                <input type="text" name="reward_final_title" id="edit_event_reward_title" class="form-control" placeholder="Héros d'Okehazama">
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_event_is_active" value="1">
                        <label class="form-check-label fw-bold" for="edit_event_is_active">Activer immédiatement cette chronique comme événement en cours</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning fw-bold font-monospace">
                        <i class="fa-solid fa-save me-1"></i>Enregistrer la Chronique
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================
     MODALE D'AJOUT D'UN ACTE NARRATIF (TÂCHE)
     ======================================================== -->
<div class="modal fade" id="modalAddTask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formWeeklyTask" onsubmit="saveTaskSubmit(event)">
                <input type="hidden" name="event_id" id="task_event_id" value="0">
                <input type="hidden" name="id" id="task_id" value="0">
                <div class="modal-header bg-dark text-white py-2">
                    <h4 class="modal-title fs-3 d-flex align-items-center gap-2 mb-0">
                        <i class="fa-solid fa-list-check text-warning"></i>
                        <span id="modalTaskTitleLabel">Ajouter un Acte Narratif</span>
                    </h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label fw-bold small">Ordre :</label>
                            <input type="number" name="step_order" id="task_step_order" class="form-control text-center font-monospace" value="1" min="1" max="10">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-bold small">Titre de l'Acte :</label>
                            <input type="text" name="title" id="task_title" class="form-control" required placeholder="Acte I : Ravitaillement">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Description narrative de l'épreuve :</label>
                        <textarea name="description" id="task_desc" rows="2" class="form-control" required placeholder="Ce que le Daimyō doit accomplir..."></textarea>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Type d'objectif :</label>
                            <select name="task_type" id="task_type" class="form-select font-monospace">
                                <option value="stock_resource">Posséder Ressource (stock_resource)</option>
                                <option value="upgrade_rural">Améliorer Terroir Rural (upgrade_rural)</option>
                                <option value="upgrade_building">Améliorer Bâtiment Urbain (upgrade_building)</option>
                                <option value="train_troops">Entraîner Troupes (train_troops)</option>
                                <option value="hero_stat">Force du Héros (hero_stat)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Cible (Clé technique) :</label>
                            <input type="text" name="target_key" id="task_target_key" class="form-control font-monospace" required placeholder="deuterium, clay, foret, yari_ashigaru...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Quantité / Niveau requis :</label>
                            <input type="number" name="target_amount" id="task_target_amount" class="form-control text-center font-monospace" required value="1000" min="1">
                        </div>
                    </div>

                    <div class="card p-2 bg-surface-secondary border mb-2">
                        <label class="form-label fw-bold small text-secondary mb-1">Gains de l'Acte :</label>
                        <div class="row g-1">
                            <div class="col-4">
                                <label class="small text-muted" style="font-size: 0.7rem;">Bois :</label>
                                <input type="number" name="reward_metal" id="task_reward_metal" placeholder="Bois" class="form-control form-control-sm text-center font-monospace" value="500">
                            </div>
                            <div class="col-4">
                                <label class="small text-muted" style="font-size: 0.7rem;">Pierre :</label>
                                <input type="number" name="reward_crystal" id="task_reward_crystal" placeholder="Pierre" class="form-control form-control-sm text-center font-monospace" value="500">
                            </div>
                            <div class="col-4">
                                <label class="small text-muted" style="font-size: 0.7rem;">Riz :</label>
                                <input type="number" name="reward_deuterium" id="task_reward_deuterium" placeholder="Riz" class="form-control form-control-sm text-center font-monospace" value="500">
                            </div>
                            <div class="col-4 mt-1">
                                <label class="small text-muted" style="font-size: 0.7rem;">Kobans (Or) :</label>
                                <input type="number" name="reward_gold" id="task_reward_gold" placeholder="Or" class="form-control form-control-sm text-center font-monospace" value="5">
                            </div>
                            <div class="col-4 mt-1">
                                <label class="small text-muted" style="font-size: 0.7rem;">Honneur :</label>
                                <input type="number" name="reward_honor" id="task_reward_honor" placeholder="Honneur" class="form-control form-control-sm text-center font-monospace" value="20">
                            </div>
                            <div class="col-4 mt-1">
                                <label class="small text-muted" style="font-size: 0.7rem;">Objet/Item :</label>
                                <input type="text" name="reward_item" id="task_reward_item" placeholder="ex: sake:30" class="form-control form-control-sm text-center font-monospace">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary fw-bold font-monospace" id="modalTaskSubmitBtn">Enregistrer l'Acte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateEditImagePreview(url) {
    const img = document.getElementById('edit_event_image_preview');
    if (img) {
        img.src = url || '/public/assets/buildings/hq.jpg';
    }
}

function openCreateEventModal() {
    document.getElementById('formWeeklyEvent').reset();
    document.getElementById('edit_event_id').value = '0';
    document.getElementById('modalEventTitleLabel').textContent = 'Créer une Chronique Féodale';
    const now = new Date();
    document.getElementById('edit_event_starts_at').value = now.toISOString().slice(0, 16);
    const end = new Date(now.getTime() + 7 * 86400000);
    document.getElementById('edit_event_ends_at').value = end.toISOString().slice(0, 16);
    updateEditImagePreview('/public/assets/buildings/hq.jpg');
    new bootstrap.Modal(document.getElementById('modalEditWeeklyEvent')).show();
}

function editEvent(eventId) {
    fetch('/api/weekly_events.php?action=admin_get_event&event_id=' + eventId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'Erreur lors du chargement de la chronique.');
                return;
            }
            const ev = data.event;
            document.getElementById('edit_event_id').value = ev.id;
            document.getElementById('edit_event_title').value = ev.title || '';
            document.getElementById('edit_event_slug').value = ev.slug || '';
            document.getElementById('edit_event_jp_title').value = ev.jp_title || '';
            document.getElementById('edit_event_era_year').value = ev.era_year || '';
            document.getElementById('edit_event_description').value = ev.description || '';
            document.getElementById('edit_event_image_url').value = ev.image_url || '';
            updateEditImagePreview(ev.image_url);

            if (ev.starts_at) {
                document.getElementById('edit_event_starts_at').value = ev.starts_at.replace(' ', 'T').slice(0, 16);
            } else {
                document.getElementById('edit_event_starts_at').value = '';
            }
            if (ev.ends_at) {
                document.getElementById('edit_event_ends_at').value = ev.ends_at.replace(' ', 'T').slice(0, 16);
            } else {
                document.getElementById('edit_event_ends_at').value = '';
            }

            document.getElementById('edit_event_reward_gold').value = ev.reward_final_gold || 0;
            document.getElementById('edit_event_reward_honor').value = ev.reward_final_honor || 0;
            document.getElementById('edit_event_reward_seal').value = ev.reward_final_seal_days || 0;
            document.getElementById('edit_event_reward_title').value = ev.reward_final_title || '';
            document.getElementById('edit_event_is_active').checked = (parseInt(ev.is_active, 10) === 1);
            document.getElementById('modalEventTitleLabel').textContent = 'Modifier la Chronique #' + ev.id + ' — ' + (ev.title || '');
            new bootstrap.Modal(document.getElementById('modalEditWeeklyEvent')).show();
        })
        .catch(err => {
            console.error(err);
            alert('Erreur réseau lors de la récupération de la chronique.');
        });
}

function saveEventSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('formWeeklyEvent');
    const formData = new FormData(form);
    fetch('/api/weekly_events.php?action=admin_save_event', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Erreur lors de la sauvegarde.');
            return;
        }
        alert(data.message || 'Chronique sauvegardée !');
        window.location.reload();
    })
    .catch(err => alert('Erreur réseau lors de la sauvegarde.'));
}

function setEventActive(eventId) {
    if (!confirm('Activer cette chronique comme événement hebdomadaire en cours pour tous les joueurs ?')) return;
    const body = new URLSearchParams({ event_id: eventId });
    fetch('/api/weekly_events.php?action=admin_set_active', {
        method: 'POST',
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) alert(data.error || 'Erreur.');
        else window.location.reload();
    });
}

function deleteEvent(eventId, title) {
    if (!confirm(`Supprimer définitivement la chronique « ${title} » et ses actes associés ?`)) return;
    const body = new URLSearchParams({ event_id: eventId });
    fetch('/api/weekly_events.php?action=admin_delete_event', {
        method: 'POST',
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) alert(data.error || 'Erreur.');
        else window.location.reload();
    });
}

function openAddTaskModal(eventId) {
    document.getElementById('formWeeklyTask').reset();
    document.getElementById('task_event_id').value = eventId;
    document.getElementById('task_id').value = '0';
    document.getElementById('modalTaskTitleLabel').textContent = 'Ajouter un Acte Narratif';
    document.getElementById('modalTaskSubmitBtn').textContent = "Ajouter l'Acte";
    new bootstrap.Modal(document.getElementById('modalAddTask')).show();
}

function editTask(t) {
    document.getElementById('formWeeklyTask').reset();
    document.getElementById('task_id').value = t.id;
    document.getElementById('task_event_id').value = t.event_id;
    document.getElementById('task_step_order').value = t.step_order;
    document.getElementById('task_title').value = t.title || '';
    document.getElementById('task_desc').value = t.description || '';
    document.getElementById('task_type').value = t.task_type || 'stock_resource';
    document.getElementById('task_target_key').value = t.target_key || '';
    document.getElementById('task_target_amount').value = t.target_amount || 1;
    document.getElementById('task_reward_metal').value = t.reward_metal || 0;
    document.getElementById('task_reward_crystal').value = t.reward_crystal || 0;
    document.getElementById('task_reward_deuterium').value = t.reward_deuterium || 0;
    document.getElementById('task_reward_gold').value = t.reward_gold || 0;
    document.getElementById('task_reward_honor').value = t.reward_honor || 0;
    document.getElementById('task_reward_item').value = t.reward_item || '';
    document.getElementById('modalTaskTitleLabel').textContent = "Modifier l'Acte #" + t.step_order + " — " + (t.title || '');
    document.getElementById('modalTaskSubmitBtn').textContent = "Mettre à jour l'Acte";
    new bootstrap.Modal(document.getElementById('modalAddTask')).show();
}

function saveTaskSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('formWeeklyTask');
    const formData = new FormData(form);
    fetch('/api/weekly_events.php?action=admin_save_task', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Erreur lors de la sauvegarde de l\'acte.');
            return;
        }
        alert(data.message || 'Acte enregistré !');
        window.location.reload();
    })
    .catch(err => alert('Erreur réseau lors de la sauvegarde de l\'acte.'));
}

function deleteTask(taskId) {
    if (!confirm('Supprimer cet acte narratif ?')) return;
    const body = new URLSearchParams({ task_id: taskId });
    fetch('/api/weekly_events.php?action=admin_delete_task', {
        method: 'POST',
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) alert(data.error || 'Erreur.');
        else window.location.reload();
    });
}

function filterAdminWeeklyEvents() {
    const input = document.getElementById('adminWeeklyEventFilter');
    const q = (input ? input.value : '').toLowerCase().trim();
    document.querySelectorAll('.admin-weekly-event-col').forEach(col => {
        const text = col.innerText.toLowerCase();
        col.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}
</script>

