<?php
/**
 * Vue Admin : Traitement dédié d'un ticket de support (Bug / Suggestion)
 * Inclus depuis admin.php après vérification admin + détection action=traiter.
 * Variables injectées par le routeur : $traiterTicketId, $traiterSupportPage,
 * $auth, $supportEngine, $user.
 */

// ── Contrôle d'accès (redondant mais défensif) ──────────────────────────────
if (!Auth::check() || !$auth->isAdmin()) {
    header('Location: ?page=admin');
    exit;
}

// ── Chargement du ticket ─────────────────────────────────────────────────────
$adminUser     = $auth->getCurrentUser();
$adminId       = (int)$adminUser['id'];
$backUrl       = "?page=admin&tab=support&support_page=" . max(1, $traiterSupportPage);

if ($traiterTicketId <= 0) {
    header("Location: $backUrl");
    exit;
}

// Récupérer le ticket (admin peut tout voir)
$stmtT = Database::getConnection()->prepare("
    SELECT t.*, u.username, u.faction,
           p.name AS planet_name, p.coord_x, p.coord_y
    FROM support_tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN planets p ON p.user_id = u.id AND p.is_capital = 1
    WHERE t.id = ?
    LIMIT 1
");
$stmtT->execute([$traiterTicketId]);
$ticket = $stmtT->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => "Ticket #$traiterTicketId introuvable."];
    header("Location: $backUrl");
    exit;
}

// ── Traitement du formulaire POST ────────────────────────────────────────────
$flashMsg   = null;
$flashType  = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validation CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!Auth::verifyCsrf($csrfToken)) {
        $flashMsg  = "Jeton de sécurité invalide ou expiré. Rechargez et réessayez.";
        $flashType = 'error';
    } else {

        $postAction = $_POST['post_action'] ?? 'save';

        // ── Suppression ────────────────────────────────────────────────────
        if ($postAction === 'delete') {
            $deleted = $supportEngine->deleteTicket($traiterTicketId);
            $_SESSION['flash'] = [
                'type' => $deleted ? 'success' : 'error',
                'msg'  => $deleted
                    ? "Ticket #$traiterTicketId supprimé définitivement."
                    : "Impossible de supprimer le ticket #$traiterTicketId.",
            ];
            header("Location: $backUrl");
            exit;

        // ── Enregistrement ─────────────────────────────────────────────────
        } else {
            $newStatus     = $_POST['status']         ?? $ticket['status'];
            $adminResponse = $_POST['admin_response'] ?? '';
            $notifyUser    = !empty($_POST['notify_user']);

            // Assainissement HTML Quill
            $adminResponse = SupportEngine::sanitizeHtml($adminResponse);

            // Validation statut
            if (!in_array($newStatus, SupportEngine::VALID_STATUSES, true)) {
                $newStatus = $ticket['status'];
            }

            try {
                $updated = $supportEngine->updateTicketStatus(
                    $traiterTicketId,
                    $newStatus,
                    $adminResponse ?: null,
                    $adminId,
                    $notifyUser
                );
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => "Ticket #$traiterTicketId mis à jour avec succès (statut : $newStatus).",
                ];
                header("Location: $backUrl");
                exit;
            } catch (Exception $e) {
                $flashMsg  = "Erreur lors de la mise à jour : " . $e->getMessage();
                $flashType = 'error';
            }
        }
    }
}

// ── Jeton CSRF pour le formulaire ────────────────────────────────────────────
$csrfToken = Auth::csrfToken();

// ── Helpers d'affichage ───────────────────────────────────────────────────────
$isBug      = ($ticket['type'] === 'bug');
$catLabel   = SupportEngine::CATEGORIES[$ticket['category']] ?? $ticket['category'];
$sevLabels = ['low' => '<i class="fa-solid fa-circle text-success me-1"></i>Faible', 'medium' => '<i class="fa-solid fa-circle text-warning me-1"></i>Moyen', 'high' => '<i class="fa-solid fa-circle text-orange me-1"></i>Élevé', 'critical' => '<i class="fa-solid fa-circle text-danger me-1"></i>Critique'];
$sevLabel   = $sevLabels[$ticket['severity']] ?? $ticket['severity'];
$statusColors = [
    'pending'     => ['label' => '<i class="fa-solid fa-hourglass-half text-warning me-1"></i>En attente',         'color' => '#eab308', 'bg' => 'rgba(234,179,8,0.1)'],
    'in_progress' => ['label' => "<i class='fa-solid fa-magnifying-glass text-primary me-1'></i>En cours d'examen",  'color' => '#2563eb', 'bg' => 'rgba(37,99,235,0.1)'],
    'resolved'    => ['label' => '<i class="fa-solid fa-circle-check text-success me-1"></i>Résolu / Corrigé',   'color' => '#16a34a', 'bg' => 'rgba(22,163,74,0.1)'],
    'planned'     => ['label' => '<i class="fa-solid fa-thumbtack text-purple me-1"></i>Retenu (Future MAJ)','color' => '#7c3aed', 'bg' => 'rgba(124,58,237,0.1)'],
    'closed'      => ['label' => '<i class="fa-solid fa-circle-xmark text-secondary me-1"></i>Fermé / Sans suite', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)'],
];
$currentStatusCfg = $statusColors[$ticket['status']] ?? ['label' => $ticket['status'], 'color' => '#64748b', 'bg' => '#f1f5f9'];
?>

<?php /* ── IMPORT QUILL (uniquement sur cette page) ── */ ?>
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<div style="max-width: 1050px; margin: 0 auto; padding-bottom: 3rem;">

    <?php /* ── MESSAGE FLASH (erreur uniquement, les succès redirigent) ── */ ?>
    <?php if ($flashMsg): ?>
    <div role="alert" style="
        display: flex; align-items: center; gap: 0.6rem; padding: 0.9rem 1.25rem;
        border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 600;
        background: <?= $flashType === 'success' ? 'rgba(22,163,74,0.1)' : 'rgba(220,38,38,0.08)' ?>;
        border: 1px solid <?= $flashType === 'success' ? '#16a34a' : '#dc2626' ?>;
        color: <?= $flashType === 'success' ? '#15803d' : '#b91c1c' ?>;">
        <?= $flashType === 'success' ? '<i class="fa-solid fa-circle-check text-success me-1"></i>' : '<i class="fa-solid fa-circle-exclamation text-danger me-1"></i>' ?>
        <?= htmlspecialchars($flashMsg) ?>
    </div>
    <?php endif; ?>

    <?php /* ── EN-TÊTE + BREADCRUMB ── */ ?>
    <div class="card" style="margin-bottom: 1.5rem; border-top: 5px solid #206bc4; background: var(--bg-surface, #fdfbf7);">
        <div style="padding: 1.5rem 2rem; background: linear-gradient(135deg, rgba(32,107,196,0.06) 0%, rgba(253,251,247,0.98) 80%);">
            <nav style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                <a href="?page=admin&tab=dashboard" style="color: #206bc4; text-decoration: none; font-weight: 600;">Administration</a>
                <span>›</span>
                <a href="<?= htmlspecialchars($backUrl) ?>" style="color: #206bc4; text-decoration: none; font-weight: 600;">Support & Tickets</a>
                <span>›</span>
                <span>Traitement #<?= $ticket['id'] ?></span>
            </nav>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 1.55rem; margin: 0 0 0.3rem 0; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-headset me-1"></i>
                        Traitement du Ticket&nbsp;<strong style="color: #206bc4;">#<?= $ticket['id'] ?></strong>
                    </h1>
                    <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                        <?= $isBug ? '<i class="fa-solid fa-bug text-danger me-1"></i>Dysfonctionnement' : '<i class="fa-solid fa-lightbulb text-warning me-1"></i>Suggestion' ?> &bull;
                        <?= htmlspecialchars($catLabel) ?> &bull;
                        <?= htmlspecialchars($sevLabel) ?>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary"
                   style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    ← Retour à la liste
                </a>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start; flex-wrap: wrap;">

        <?php /* ══════════════════════════════════════════════════════════════
               COL GAUCHE — RÉCAPITULATIF DU TICKET (lecture seule)
               ══════════════════════════════════════════════════════════════ */ ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">

            <!-- Fiche auteur -->
            <div class="card" style="background: var(--bg-surface,#fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                <div style="padding: 0.85rem 1.25rem; background: var(--bg-ink,#ede5d5); border-bottom: 1px solid var(--border-color);">
                    <strong style="font-size: 0.85rem; color: var(--text-main);"><i class="fa-solid fa-user text-primary me-1"></i>Auteur &amp; Fief</strong>
                </div>
                <div style="padding: 1rem 1.25rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.85rem;">
                    <div><span style="color:var(--text-muted); font-weight:600;">Daimyō :</span><br>
                        <strong><?= htmlspecialchars($ticket['username']) ?></strong></div>
                    <div><span style="color:var(--text-muted); font-weight:600;">Clan :</span><br>
                        <strong style="text-transform:uppercase; color:#b91c1c;"><?= htmlspecialchars($ticket['faction'] ?? '—') ?></strong></div>
                    <div><span style="color:var(--text-muted); font-weight:600;">Fief :</span><br>
                        <?= $ticket['planet_name']
                            ? htmlspecialchars($ticket['planet_name']) . ' [' . (int)$ticket['coord_x'] . ':' . (int)$ticket['coord_y'] . ']'
                            : '—' ?></div>
                    <div><span style="color:var(--text-muted); font-weight:600;">Soumis le :</span><br>
                        <?= date('d/m/Y à H:i', $ticket['created_at']) ?></div>
                </div>
            </div>

            <!-- Statut actuel -->
            <div class="card" style="background: var(--bg-surface,#fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                <div style="padding: 0.85rem 1.25rem; background: var(--bg-ink,#ede5d5); border-bottom: 1px solid var(--border-color);">
                    <strong style="font-size: 0.85rem; color: var(--text-main);"><i class="fa-solid fa-chart-simple text-info me-1"></i>Statut actuel</strong>
                </div>
                <div style="padding: 1rem 1.25rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 1rem;
                          border-radius: 20px; font-size: 0.85rem; font-weight: 700;
                          color: <?= $currentStatusCfg['color'] ?>;
                          background: <?= $currentStatusCfg['bg'] ?>;
                          border: 1px solid <?= $currentStatusCfg['color'] ?>44;">
                        <?= $currentStatusCfg['label'] ?>
                    </span>
                    <?php if (!empty($ticket['updated_at']) && $ticket['updated_at'] > $ticket['created_at']): ?>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem;">
                            Dernière mise à jour : <?= date('d/m/Y à H:i', $ticket['updated_at']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenu du ticket (lecture seule) -->
            <div class="card" style="background: var(--bg-surface,#fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                <div style="padding: 0.85rem 1.25rem; background: var(--bg-ink,#ede5d5); border-bottom: 1px solid var(--border-color);">
                    <strong style="font-size: 0.85rem; color: var(--text-main);">
                        <?= $isBug ? '<i class="fa-solid fa-bug text-danger me-1"></i>Signalement du joueur' : '<i class="fa-solid fa-lightbulb text-warning me-1"></i>Suggestion du joueur' ?>
                    </strong>
                </div>
                <div style="padding: 1.25rem;">
                    <h3 style="font-size: 1.05rem; margin: 0 0 0.75rem 0; color: var(--text-main);">
                        <?= htmlspecialchars($ticket['title']) ?>
                    </h3>
                    <!-- Rendu HTML Quill (assaini à la saisie, on fait confiance au stockage) -->
                    <div class="ticket-description-readonly"
                         style="background: #fff; border: 1px solid var(--border-color); padding: 1rem; border-radius: 8px;
                                font-size: 0.9rem; line-height: 1.7; color: #1e293b; max-height: 340px; overflow-y: auto;">
                        <?= $ticket['description'] /* HTML déjà assaini par SupportEngine::sanitizeHtml à la saisie */ ?>
                    </div>
                </div>
            </div>

            <!-- Réponse admin précédente si elle existe -->
            <?php if (!empty($ticket['admin_response'])): ?>
            <div class="card" style="background: rgba(8,145,178,0.04); border: 1px solid #0891b244; border-radius: 12px; overflow: hidden;">
                <div style="padding: 0.85rem 1.25rem; background: rgba(8,145,178,0.1); border-bottom: 1px solid #0891b244;">
                    <strong style="font-size: 0.85rem; color: #0891b2;"><i class="fa-solid fa-shield-halved text-info me-1"></i>Réponse précédente de l'administration</strong>
                </div>
                <div style="padding: 1.25rem; font-size: 0.88rem; line-height: 1.6; color: var(--text-main);">
                    <?= $ticket['admin_response'] ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php /* ══════════════════════════════════════════════════════════════
               COL DROITE — FORMULAIRE DE TRAITEMENT
               ══════════════════════════════════════════════════════════════ */ ?>
        <div>
            <div class="card" style="background: var(--bg-surface,#fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; position: sticky; top: 1rem;">
                <div style="padding: 0.85rem 1.25rem; background: linear-gradient(135deg,rgba(32,107,196,0.08),rgba(253,251,247,0.98));
                            border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem;">
                    <strong style="font-size: 0.9rem; color: var(--text-main);"><i class="fa-solid fa-gear text-secondary me-1"></i>Décision &amp; Réponse du Shogunat</strong>
                </div>

                <form id="adminTraiterForm" method="POST" action="" onsubmit="syncQuillBeforeSubmit(event)">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="post_action" id="postActionField" value="save">

                    <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">

                        <!-- Sélecteur de statut -->
                        <div>
                            <label for="statusSelect" style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-main);">
                                Nouveau statut :
                            </label>
                            <select id="statusSelect" name="status" class="form-select" style="width: 100%;">
                                <?php
                                $statusOptions = [
                                    'pending'     => 'En attente',
                                    'in_progress' => "En cours d'examen",
                                    'resolved'    => 'Résolu / Corrigé',
                                    'planned'     => 'Retenu (Future MAJ)',
                                    'closed'      => 'Fermé / Sans suite',
                                ];
                                foreach ($statusOptions as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= ($ticket['status'] === $val) ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Notification joueur -->
                        <div>
                            <label class="form-check form-switch m-0" style="cursor: pointer;">
                                <input class="form-check-input" type="checkbox" name="notify_user" value="1" checked>
                                <span class="form-check-label fw-bold" style="font-size: 0.85rem;">
                                    Notifier le joueur par missive en jeu
                                </span>
                            </label>
                        </div>

                        <!-- Éditeur Quill pour la réponse -->
                        <div>
                            <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-main);">
                                Réponse officielle
                                <span style="font-weight: 400; color: var(--text-muted);">(visible par le joueur)</span> :
                            </label>

                            <!-- Barre d'outils Quill -->
                            <div id="adminResponseToolbar">
                                <span class="ql-formats">
                                    <button class="ql-bold" title="Gras"></button>
                                    <button class="ql-italic" title="Italique"></button>
                                    <button class="ql-underline" title="Souligné"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered" title="Liste numérotée"></button>
                                    <button class="ql-list" value="bullet" title="Liste à puces"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-blockquote" title="Citation"></button>
                                    <button class="ql-link" title="Lien"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-clean" title="Supprimer la mise en forme"></button>
                                </span>
                            </div>

                            <!-- Conteneur Quill -->
                            <div id="adminResponseEditor"
                                 style="min-height: 180px; border: 2px solid var(--border-color);
                                        border-radius: 0 0 8px 8px; background: #fff; font-size: 0.88rem;
                                        line-height: 1.6; color: #1e293b;"></div>

                            <!-- Champ caché synchronisé juste avant submit -->
                            <input type="hidden" name="admin_response" id="adminResponseHidden">
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color);">
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="confirmDelete()"
                                    title="Supprimer définitivement ce ticket">
                                <i class="fa-solid fa-trash me-1"></i>Supprimer
                            </button>
                            <div style="display: flex; gap: 0.75rem;">
                                <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary btn-sm">
                                    Annuler
                                </a>
                                <button type="submit" id="adminTraiterSubmitBtn" class="btn btn-primary btn-sm"
                                        style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700;">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer &amp; Transmettre
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /grid -->

</div><!-- /container -->

<?php /* ══════════════════════════════════════════════════════════════════════
       JAVASCRIPT — Quill init, synchronisation champ caché, confirmation delete
       ══════════════════════════════════════════════════════════════════════ */ ?>
<script>
/* ── Initialisation Quill ───────────────────────────────────────────────── */
const adminQuill = new Quill('#adminResponseEditor', {
    modules: { toolbar: '#adminResponseToolbar' },
    theme: 'snow',
    placeholder: 'Rédigez ici la réponse officielle du Shogunat visible par le joueur…',
});

// Pré-remplissage si une réponse admin existe déjà
(function preFill() {
    const existing = <?= json_encode($ticket['admin_response'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (existing && existing.trim() !== '') {
        adminQuill.root.innerHTML = existing;
    }
})();

/* ── Synchronisation du champ caché avant soumission ───────────────────── */
function syncQuillBeforeSubmit(event) {
    // Injecter le HTML Quill dans le champ caché avant envoi du formulaire
    document.getElementById('adminResponseHidden').value = adminQuill.root.innerHTML;

    const btn = document.getElementById('adminTraiterSubmitBtn');
    btn.disabled    = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enregistrement…';
    // Laisser le formulaire se soumettre normalement (pas de preventDefault)
}

/* ── Confirmation & déclenchement de la suppression ────────────────────── */
function confirmDelete() {
    const ticketId = <?= (int)$ticket['id'] ?>;
    if (!confirm(`Confirmer la suppression définitive du ticket #${ticketId} ?\n\nCette action est irréversible.`)) {
        return;
    }
    document.getElementById('postActionField').value = 'delete';
    // La synchro Quill n'est pas nécessaire pour une suppression
    document.getElementById('adminTraiterForm').submit();
}

/* ── Message flash de session (succès/erreur après redirection) ─────────── */
(function showSessionFlash() {
    const flashData = <?= json_encode($_SESSION['flash'] ?? null) ?>;
    <?php unset($_SESSION['flash']); ?>
    if (!flashData) return;
    if (typeof window.showToast === 'function') {
        showToast(flashData.msg, flashData.type === 'success' ? 'success' : 'danger');
    }
})();
</script>
