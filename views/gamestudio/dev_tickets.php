<?php
/**
 * Vue Partielle MVC : Traitement des Tickets Support & Déclaration de Bugs
 * Module Développeur (Back-End, Front-End & Réseau)
 * Fichier : views/gamestudio/dev_tickets.php
 */
require_once __DIR__ . '/../../core/SupportEngine.php';
$supportEngine = new SupportEngine();

// Filtres GET
$filterType = !empty($_GET['type']) && in_array($_GET['type'], SupportEngine::VALID_TYPES) ? $_GET['type'] : null;
$filterStatus = !empty($_GET['status']) && in_array($_GET['status'], SupportEngine::VALID_STATUSES) ? $_GET['status'] : null;
$filterSeverity = !empty($_GET['severity']) && in_array($_GET['severity'], SupportEngine::VALID_SEVERITIES) ? $_GET['severity'] : null;
$searchQuery = trim($_GET['search'] ?? '');

$tickets = $supportEngine->getAllTickets($filterType, $filterStatus, $searchQuery, 100, 0, $filterSeverity);
$stats = $supportEngine->getStatistics();

// Statistiques complémentaires sévérités
$db = Database::getConnection();
$critCount = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE severity = 'critical' AND status IN ('pending', 'in_progress')")->fetchColumn();
$highCount = (int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE severity = 'high' AND status IN ('pending', 'in_progress')")->fetchColumn();

// Catégories mapping technique (Back-End, Front-End, Réseau)
$techCategoryMap = [
    'resources' => ['domaine' => 'Back-End & Moteur', 'file' => 'RuralPlotEngine.php / resources.php', 'icon' => 'fa-wheat-awn', 'color' => 'success'],
    'city'      => ['domaine' => 'Front & Back-End', 'file' => 'BuildingEngine.php / city.php', 'icon' => 'fa-chess-rook', 'color' => 'danger'],
    'troops'    => ['domaine' => 'Back-End & Combat', 'file' => 'BarracksEngine.php / barracks.php', 'icon' => 'fa-person-rifle', 'color' => 'warning'],
    'siege'     => ['domaine' => 'Back-End & Production', 'file' => 'ShipyardEngine.php / shipyard.php', 'icon' => 'fa-shield-halved', 'color' => 'secondary'],
    'hero'      => ['domaine' => 'Back-End & Quêtes', 'file' => 'HeroEngine.php / hero.php', 'icon' => 'fa-user-ninja', 'color' => 'indigo'],
    'map'       => ['domaine' => 'Front-End & SVG', 'file' => 'map.php / OasisEngine.php', 'icon' => 'fa-map-location-dot', 'color' => 'teal'],
    'quests'    => ['domaine' => 'Back-End & Didacticiel', 'file' => 'QuestEngine.php / pedagogy.php', 'icon' => 'fa-scroll', 'color' => 'purple'],
    'combat'    => ['domaine' => 'Moteur Algorithmique', 'file' => 'CombatEngine.php / reports.php', 'icon' => 'fa-burst', 'color' => 'danger'],
    'ui'        => ['domaine' => 'Front-End & CSS/JS', 'file' => 'tabler.min.css / style.css / JS', 'icon' => 'fa-palette', 'color' => 'cyan'],
    'other'     => ['domaine' => 'Réseau & Général', 'file' => 'Database.php / Router / API', 'icon' => 'fa-network-wired', 'color' => 'secondary'],
];

$activeTicketId = (int)($_GET['ticket_id'] ?? 0);
$activeTicket = null;
if ($activeTicketId > 0) {
    $activeTicket = $supportEngine->getTicketById($activeTicketId);
}
?>

<div class="gamestudio-dev-tickets">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="card-title text-teal d-flex align-items-center gap-2 m-0" style="font-size:1.35rem;">
                <i class="fa-solid fa-code text-teal"></i>
                <span>Développeur &bull; Traitement des Tickets Support &amp; Déclaration de Bugs</span>
            </h3>
            <div class="text-secondary small mt-1">
                Module Développeur (Back-End, Front-End &amp; Réseau) &bull; Triage, diagnostic technique et correctifs des remontées joueurs.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-triangle-exclamation me-1"></i><?= $critCount ?> Bloquant(s) Critique(s)
            </span>
            <span class="badge bg-teal-lt py-2 px-3 fw-bold">
                <i class="fa-solid fa-bug me-1"></i><?= count($tickets) ?> Ticket(s) listé(s)
            </span>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if (isset($_SESSION['dev_flash'])): ?>
        <div class="alert alert-<?= htmlspecialchars($_SESSION['dev_flash']['type']) ?> alert-dismissible mb-4 shadow-sm" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid <?= $_SESSION['dev_flash']['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-warning' ?> fs-3"></i>
                <div><?= htmlspecialchars($_SESSION['dev_flash']['msg']) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['dev_flash']); ?>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 1. CARTES DE KPI ET TÉLÉMÉTRIE BUGS                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-indigo-lt text-indigo avatar">
                                <i class="fa-solid fa-inbox fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Total Signalements</div>
                            <div class="text-dark fw-bold fs-2"><?= number_format($stats['total'] ?? 0) ?></div>
                            <div class="small text-muted"><?= $stats['total_bugs'] ?? 0 ?> bugs &bull; <?= $stats['total_suggestions'] ?? 0 ?> suggestions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0 border-start border-3 border-warning">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning-lt text-warning avatar">
                                <i class="fa-solid fa-hourglass-half fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">En Attente de Triage</div>
                            <div class="text-warning fw-bold fs-2"><?= number_format($stats['count_pending'] ?? 0) ?></div>
                            <div class="small text-muted">Bugs non encore pris en charge</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0 border-start border-3 border-info">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info-lt text-info avatar">
                                <i class="fa-solid fa-screwdriver-wrench fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">En Cours d'Analyse</div>
                            <div class="text-info fw-bold fs-2"><?= number_format($stats['count_in_progress'] ?? 0) ?></div>
                            <div class="small text-muted">Dev &amp; reproduction en cours</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0 border-start border-3 border-success">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success-lt text-success avatar">
                                <i class="fa-solid fa-circle-check fs-3"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary">Résolus &amp; Corrigés</div>
                            <div class="text-success fw-bold fs-2"><?= number_format($stats['count_resolved'] ?? 0) ?></div>
                            <div class="small text-muted">Plein succès &bull; Missives envoyées</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 2. BARRE DE FILTRES ET RECHERCHE                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="page" value="gamestudio">
                <input type="hidden" name="tab" value="dev_tickets">

                <!-- Recherche texte -->
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Rechercher par titre, description, joueur...">
                    </div>
                </div>

                <!-- Filtre Type -->
                <div class="col-md-2">
                    <select class="form-select" name="type" onchange="this.form.submit()">
                        <option value="">Tous Types</option>
                        <option value="bug" <?= $filterType === 'bug' ? 'selected' : '' ?>>🐞 Bugs</option>
                        <option value="suggestion" <?= $filterType === 'suggestion' ? 'selected' : '' ?>>💡 Suggestions</option>
                    </select>
                </div>

                <!-- Filtre Statut -->
                <div class="col-md-2">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="">Tous Statuts</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>⏳ En attente</option>
                        <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>🔧 En cours</option>
                        <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : '' ?>>✅ Résolu</option>
                        <option value="planned" <?= $filterStatus === 'planned' ? 'selected' : '' ?>>📅 Planifié</option>
                        <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>>❌ Fermé</option>
                    </select>
                </div>

                <!-- Filtre Sévérité -->
                <div class="col-md-2">
                    <select class="form-select" name="severity" onchange="this.form.submit()">
                        <option value="">Toutes Sévérités</option>
                        <option value="critical" <?= $filterSeverity === 'critical' ? 'selected' : '' ?>>🔥 Critique</option>
                        <option value="high" <?= $filterSeverity === 'high' ? 'selected' : '' ?>>⚠️ Haute</option>
                        <option value="medium" <?= $filterSeverity === 'medium' ? 'selected' : '' ?>>🟡 Moyenne</option>
                        <option value="low" <?= $filterSeverity === 'low' ? 'selected' : '' ?>>🟢 Basse</option>
                    </select>
                </div>

                <!-- Boutons -->
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrer
                    </button>
                    <?php if ($filterType || $filterStatus || $filterSeverity || $searchQuery): ?>
                        <a href="?page=gamestudio&tab=dev_tickets" class="btn btn-outline-secondary" title="Réinitialiser les filtres">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- 3. TABLEAU DES TICKETS ET CONSOLE DEV                               -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="card-title text-dark fw-bold m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-list-check text-teal"></i>
                <span>File des Remontées &amp; Triage Technique (<?= count($tickets) ?>)</span>
            </h4>
            <div class="text-secondary small">
                Cliquez sur <strong>Inspecter &amp; Traiter</strong> pour diagnostiquer le composant et notifier le joueur.
            </div>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="card-body text-center py-5">
                <div class="empty">
                    <div class="empty-icon text-success mb-3" style="font-size: 3rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="empty-title fs-3 fw-bold">Aucun ticket correspondant trouvé</p>
                    <p class="empty-subtitle text-secondary">
                        Tous les signalements sous ces critères ont été traités avec succès ou aucun bug n'a été déposé.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th class="w-1">#ID</th>
                            <th>Type</th>
                            <th>Sévérité</th>
                            <th>Composant / Domaine</th>
                            <th>Titre &amp; Description</th>
                            <th>Auteur / Fief</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <?php
                            $tech = $techCategoryMap[$t['category']] ?? $techCategoryMap['other'];
                            $sevBadge = match($t['severity']) {
                                'critical' => '<span class="badge bg-danger text-white"><i class="fa-solid fa-fire me-1"></i>Critique</span>',
                                'high'     => '<span class="badge bg-orange text-white"><i class="fa-solid fa-triangle-exclamation me-1"></i>Haute</span>',
                                'medium'   => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-circle-exclamation me-1"></i>Moyenne</span>',
                                default    => '<span class="badge bg-secondary-lt"><i class="fa-solid fa-info me-1"></i>Basse</span>'
                            };
                            $statusBadge = match($t['status']) {
                                'pending'     => '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-hourglass-start me-1"></i>En attente</span>',
                                'in_progress' => '<span class="badge bg-info text-white fw-bold"><i class="fa-solid fa-screwdriver-wrench me-1"></i>En cours</span>',
                                'resolved'    => '<span class="badge bg-success text-white fw-bold"><i class="fa-solid fa-check me-1"></i>Résolu</span>',
                                'planned'     => '<span class="badge bg-purple text-white fw-bold"><i class="fa-solid fa-calendar-check me-1"></i>Planifié</span>',
                                default       => '<span class="badge bg-secondary text-white"><i class="fa-solid fa-xmark me-1"></i>Fermé</span>'
                            };
                            $typeBadge = ($t['type'] === 'bug')
                                ? '<span class="badge bg-danger-lt text-danger fw-bold"><i class="fa-solid fa-bug me-1"></i>Bug</span>'
                                : '<span class="badge bg-primary-lt text-primary fw-bold"><i class="fa-solid fa-lightbulb me-1"></i>Suggestion</span>';
                            ?>
                            <tr>
                                <td class="font-monospace text-muted fw-bold">#<?= (int)$t['id'] ?></td>
                                <td><?= $typeBadge ?></td>
                                <td><?= $sevBadge ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-<?= $tech['color'] ?>-lt p-1" title="<?= htmlspecialchars($tech['domaine']) ?>">
                                            <i class="fa-solid <?= $tech['icon'] ?>"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars(SupportEngine::CATEGORIES[$t['category']] ?? $t['category']) ?></div>
                                            <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($tech['file']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($t['title']) ?>">
                                        <?= htmlspecialchars($t['title']) ?>
                                    </div>
                                    <div class="text-secondary small text-truncate" style="max-width: 260px;">
                                        <?= htmlspecialchars(strip_tags($t['description'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-user-shield text-secondary small"></i>
                                        <strong class="text-dark"><?= htmlspecialchars($t['username']) ?></strong>
                                    </div>
                                    <div class="text-muted small">Clan <?= strtoupper($t['faction'] ?? 'Terran') ?></div>
                                </td>
                                <td><?= $statusBadge ?></td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y', (int)$t['created_at']) ?><br>
                                    <span class="font-monospace"><?= date('H:i', (int)$t['created_at']) ?></span>
                                </td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-teal d-inline-flex align-items-center gap-1 shadow-sm"
                                            onclick='openDevTicketModal(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($tech, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fa-solid fa-wrench"></i>
                                        <span>Inspecter &amp; Traiter</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- MODALE TABLER D'INSPECTION & TRAITEMENT DÉVELOPPEUR                 -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="modal modal-blur fade" id="modalDevTicket" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-teal text-white rounded">
                        <i class="fa-solid fa-terminal"></i>
                    </span>
                    <div>
                        <h4 class="modal-title text-white fw-bold m-0" id="devModalTitle">Ticket #--</h4>
                        <div class="text-white-50 small" id="devModalSubtitle">Console de diagnostic et résolution technique</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_ticket_status">
                <input type="hidden" name="ticket_id" id="devModalTicketId" value="0">
                <input type="hidden" name="filter_params" value="<?= htmlspecialchars(http_build_query(['type' => $filterType, 'status' => $filterStatus, 'severity' => $filterSeverity, 'search' => $searchQuery])) ?>">

                <div class="modal-body p-4">
                    <!-- Fiche Diagnostic Composant -->
                    <div class="alert alert-light border shadow-sm p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <div class="text-secondary small fw-bold text-uppercase">Type &amp; Gravité</div>
                                <div class="mt-1 d-flex gap-1" id="devModalBadges">--</div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="text-secondary small fw-bold text-uppercase">Auteur Signalement</div>
                                <div class="mt-1 fw-bold text-dark d-flex align-items-center gap-1" id="devModalAuthor">--</div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="text-secondary small fw-bold text-uppercase">Composant Assigné</div>
                                <div class="mt-1 font-monospace small text-primary fw-bold" id="devModalComponent">--</div>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <div class="text-secondary small fw-bold text-uppercase">Fichiers Liés</div>
                                <div class="mt-1 font-monospace small text-muted" id="devModalFiles">--</div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu du signalement -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                            <span><i class="fa-solid fa-align-left text-primary me-1"></i>Description détaillée du problème rencontré :</span>
                            <span class="badge bg-secondary-lt text-secondary font-monospace" id="devModalDate">--</span>
                        </label>
                        <div class="p-3 bg-light rounded border text-dark" id="devModalDescription" style="max-height: 220px; overflow-y: auto; font-size: 0.92rem; line-height: 1.5;">
                            --
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Formulaire de Traitement & Correctif -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">
                                <i class="fa-solid fa-list-check text-teal me-1"></i>Nouveau Statut du Ticket :
                            </label>
                            <select class="form-select fw-bold" name="status" id="devModalStatusSelect">
                                <option value="pending">⏳ En attente (Non pris en charge)</option>
                                <option value="in_progress">🔧 En cours d'analyse &amp; correction</option>
                                <option value="resolved">✅ Résolu / Corrigé en production</option>
                                <option value="planned">📅 Retenu pour la prochaine version</option>
                                <option value="closed">❌ Fermé (Sans suite / Non reproduit)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">
                                <i class="fa-solid fa-bell text-warning me-1"></i>Notification Daimyō :
                            </label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="notify_user" value="1" id="devModalNotifyUser" checked>
                                <label class="form-check-label fw-medium text-dark" for="devModalNotifyUser">
                                    Envoyer une missive en jeu et un e-mail officiel au joueur
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold text-dark">
                                <i class="fa-solid fa-comment-dots text-primary me-1"></i>Réponse officielle de l'équipe de Dev / Shōgunat :
                            </label>
                            <textarea class="form-control" name="admin_response" id="devModalResponseText" rows="3" placeholder="Expliquez la cause du problème, la solution apportée ou le statut de correction pour informer le joueur..."></textarea>
                            <div class="form-text text-muted small">Ce message sera inclus dans la missive en jeu ainsi que dans l'e-mail officiel transmis au joueur si la case ci-dessus est cochée.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <!-- Lien vers Live Ops Compensation -->
                        <a href="#" id="devModalCompensateBtn" class="btn btn-outline-purple btn-sm d-inline-flex align-items-center gap-1 shadow-sm" title="Ouvrir le formulaire de dédommagement Live Ops pour ce joueur">
                            <i class="fa-solid fa-gift"></i>
                            <span>Compenser ce Daimyō (Live Ops)</span>
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-teal fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Enregistrer le Traitement</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDevTicketModal(ticket, tech) {
    if (!ticket) return;

    // Titres & ID
    document.getElementById('devModalTicketId').value = ticket.id;
    document.getElementById('devModalTitle').textContent = `Ticket #${ticket.id} : ${ticket.title}`;
    document.getElementById('devModalSubtitle').textContent = `Signalement déposé par le Daimyō ${ticket.username}`;

    // Badges Type & Gravité
    const typeLabel = (ticket.type === 'bug') ? '🐞 Bug' : '💡 Suggestion';
    const typeClass = (ticket.type === 'bug') ? 'bg-danger text-white' : 'bg-primary text-white';
    const sevLabel = ticket.severity.toUpperCase();
    const sevClass = (ticket.severity === 'critical') ? 'bg-danger text-white' : ((ticket.severity === 'high') ? 'bg-orange text-white' : 'bg-warning text-dark');
    document.getElementById('devModalBadges').innerHTML = `
        <span class="badge ${typeClass}">${typeLabel}</span>
        <span class="badge ${sevClass}">${sevLabel}</span>
    `;

    // Auteur & Composant
    const emailInfo = ticket.email ? ` <span class="text-muted fw-normal small">(${ticket.email})</span>` : '';
    document.getElementById('devModalAuthor').innerHTML = `${ticket.username} <span class="badge bg-secondary-lt">${(ticket.faction || 'Terran').toUpperCase()}</span>${emailInfo}`;
    document.getElementById('devModalComponent').textContent = tech.domaine || 'Général';
    document.getElementById('devModalFiles').textContent = tech.file || '--';

    // Description & Date
    const d = new Date(ticket.created_at * 1000);
    document.getElementById('devModalDate').textContent = d.toLocaleString('fr-FR');
    document.getElementById('devModalDescription').innerHTML = ticket.description || '<em>Aucune description fournie.</em>';

    // Formulaire
    document.getElementById('devModalStatusSelect').value = ticket.status || 'pending';
    document.getElementById('devModalResponseText').value = ticket.admin_response || '';

    // Lien de compensation Live Ops
    const compBtn = document.getElementById('devModalCompensateBtn');
    if (compBtn) {
        compBtn.href = `?page=gamestudio&tab=liveops_support&select_user_id=${ticket.user_id}&select_username=${encodeURIComponent(ticket.username)}`;
    }

    // Ouvrir la modale Tabler
    const modalEl = document.getElementById('modalDevTicket');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}
</script>

