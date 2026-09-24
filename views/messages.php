<?php
/**
 * Centre de Communications Féodales (Messagerie Diplomatique entre Daimyōs)
 * Interface Tabler UI native
 */
require_once __DIR__ . '/../core/MessageEngine.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/game_constants.php';

$messageEngine = new MessageEngine();
$userId = (int)$user['id'];

$tab = $_GET['tab'] ?? 'inbox';
$msgId = isset($_GET['msg_id']) ? (int)$_GET['msg_id'] : null;
$presetTo = $_GET['to'] ?? '';
$presetSubject = $_GET['subject'] ?? '';

// Si on consulte un message spécifique
$activeMessage = null;
if ($msgId) {
    $activeMessage = $messageEngine->getMessage($msgId, $userId);
    if ($activeMessage && empty($_GET['tab'])) {
        $tab = ((int)$activeMessage['sender_id'] === $userId) ? 'outbox' : 'inbox';
    }
}

$inbox = $messageEngine->getInbox($userId);
$outbox = $messageEngine->getOutbox($userId);
$unreadCount = $messageEngine->getUnreadCount($userId);
$otherPlayers = $messageEngine->getAllOtherPlayers($userId);

// Configuration de la pagination
$perPage = 10;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

// Pagination de la boîte de réception
$totalInbox = count($inbox);
$totalInboxPages = max(1, (int)ceil($totalInbox / $perPage));
$inboxPageNum = min($pageNum, $totalInboxPages);
$inboxOffset = ($inboxPageNum - 1) * $perPage;
$pagedInbox = array_slice($inbox, $inboxOffset, $perPage);

// Pagination de la boîte d'envoi
$totalOutbox = count($outbox);
$totalOutboxPages = max(1, (int)ceil($totalOutbox / $perPage));
$outboxPageNum = min($pageNum, $totalOutboxPages);
$outboxOffset = ($outboxPageNum - 1) * $perPage;
$pagedOutbox = array_slice($outbox, $outboxOffset, $perPage);
?>

<div class="card shadow-sm mb-3">
    <!-- En-tête de la Messagerie avec boutons d'actions alignés à droite -->
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="card-title d-flex align-items-center gap-2 m-0 text-dark">
                <span>📜</span> Messagers &amp; Missives Féodales
            </h2>
            <div class="text-secondary small mt-1">
                Centre des correspondances diplomatiques, traités de paix et dépêches du Shogunat.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
            <a href="?page=messages&tab=inbox" 
               class="btn btn-sm <?= ($tab === 'inbox' && !$activeMessage) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <span>📥 Réception</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger text-white ms-1"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=messages&tab=outbox" 
               class="btn btn-sm <?= ($tab === 'outbox' && !$activeMessage) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <span>📤 Envoyés</span>
                <span class="badge bg-secondary-lt ms-1"><?= $totalOutbox ?></span>
            </a>
            <a href="?page=messages&tab=compose" 
               class="btn btn-sm <?= ($tab === 'compose' && !$activeMessage) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <span>✍️ Rédiger une Missive</span>
            </a>
        </div>
    </div>

    <?php if ($activeMessage): ?>
        <!-- ========================================================
             1. VUE LECTURE D'UN MESSAGE SPÉCIFIQUE (Page dédiée)
             ======================================================== -->
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 pb-3 mb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary text-white">Missive</span>
                        <h3 class="m-0 fw-bold text-dark fs-3">
                            <?= htmlspecialchars($activeMessage['subject']) ?>
                        </h3>
                    </div>
                    <div class="text-secondary small d-flex flex-wrap gap-3 align-items-center">
                        <div>
                            <strong>Expéditeur :</strong> 
                            <?= $activeMessage['sender_name'] ? htmlspecialchars($activeMessage['sender_name']) : 'Mandat Impérial du Shogunat' ?>
                            <?php if (!empty($activeMessage['sender_faction'])): ?>
                                <span class="badge bg-secondary-lt ms-1">Clan <?= htmlspecialchars(ucfirst($activeMessage['sender_faction'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted">&bull;</span>
                        <div>
                            <strong>Destinataire :</strong> <?= htmlspecialchars($activeMessage['receiver_name']) ?>
                        </div>
                        <span class="text-muted">&bull;</span>
                        <div>
                            <strong>Date :</strong> <?= date('d/m/Y à H:i:s', $activeMessage['created_at']) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="?page=messages&tab=<?= htmlspecialchars($tab) ?>" class="btn btn-sm btn-outline-secondary">
                        &larr; Retour à la liste
                    </a>
                </div>
            </div>

            <!-- Corps du Message -->
            <div class="p-3 rounded border bg-light-subtle mb-4" style="white-space: pre-wrap; font-size: 0.95rem; min-height: 140px; line-height: 1.6; color: #1e293b;">
<?= htmlspecialchars($activeMessage['body']) ?>
            </div>

            <!-- Barre d'actions en bas de la missive -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                <div class="d-flex gap-2">
                    <a href="?page=messages&tab=<?= htmlspecialchars($tab) ?>" class="btn btn-secondary btn-sm">
                        &larr; Retour aux missives
                    </a>
                    <?php if ($activeMessage['sender_id'] && (int)$activeMessage['sender_id'] !== $userId): ?>
                        <a href="?page=messages&tab=compose&to=<?= urlencode($activeMessage['sender_name']) ?>&subject=<?= urlencode('Re: ' . $activeMessage['subject']) ?>" 
                           class="btn btn-primary btn-sm">
                            ↩️ Répondre
                        </a>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteMessage(<?= (int)$activeMessage['id'] ?>)">
                        🗑️ Supprimer cette missive
                    </button>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'inbox'): ?>
        <!-- ========================================================
             2. BOÎTE DE RÉCEPTION (Liste paginée)
             ======================================================== -->
        <?php if (empty($inbox)): ?>
            <div class="card-body text-center py-5">
                <div class="display-3 mb-2">📭</div>
                <h3 class="fw-bold text-dark">Votre boîte de réception est vide</h3>
                <p class="text-secondary small">Aucune missive reçue pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover table-striped card-table">
                    <thead>
                        <tr>
                            <th class="w-1 text-center"></th>
                            <th style="min-width: 180px;">Expéditeur</th>
                            <th>Objet de la missive</th>
                            <th style="width: 170px;">Reçu le</th>
                            <th class="w-1 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagedInbox as $m): ?>
                            <tr class="<?= !$m['is_read'] ? 'fw-bold table-light' : '' ?>" 
                                onclick="window.location.href='?page=messages&tab=inbox&msg_id=<?= $m['id'] ?>'" 
                                style="cursor:pointer;">
                                <td class="text-center">
                                    <?php if (!$m['is_read']): ?>
                                        <span class="badge bg-danger rounded-circle p-1" title="Non lu" style="display:inline-block; width:8px; height:8px;"></span>
                                    <?php else: ?>
                                        <span class="text-muted" style="opacity:0.4;">✉️</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4"><?= $m['sender_faction'] ? (FACTIONS[$m['sender_faction']]['icon'] ?? '👤') : '🤖' ?></span>
                                        <div>
                                            <div class="text-dark fw-bold"><?= $m['sender_name'] ? htmlspecialchars($m['sender_name']) : 'Mandat Impérial' ?></div>
                                            <?php if (!empty($m['sender_faction'])): ?>
                                                <div class="text-muted small">Clan <?= htmlspecialchars(ucfirst($m['sender_faction'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="<?= !$m['is_read'] ? 'text-primary fw-bold' : 'text-dark' ?>">
                                        <?= htmlspecialchars($m['subject']) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    <?= date('d/m/Y H:i', $m['created_at']) ?>
                                </td>
                                <td class="text-end" onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-sm btn-ghost-danger" onclick="deleteMessage(<?= $m['id'] ?>)" title="Supprimer la missive">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination de la boîte de réception -->
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="m-0 text-secondary small">
                    Affichage de <strong><?= ($totalInbox > 0) ? ($inboxOffset + 1) : 0 ?></strong> à <strong><?= min($inboxOffset + $perPage, $totalInbox) ?></strong> sur <strong><?= $totalInbox ?></strong> missives
                </p>
                <?php if ($totalInboxPages > 1): ?>
                <ul class="pagination pagination-sm m-0 ms-auto">
                    <li class="page-item <?= ($inboxPageNum <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=messages&tab=inbox&p=<?= $inboxPageNum - 1 ?>">
                            &lsaquo; Précédent
                        </a>
                    </li>
                    <?php for ($p = 1; $p <= $totalInboxPages; $p++): ?>
                        <li class="page-item <?= ($p === $inboxPageNum) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=messages&tab=inbox&p=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($inboxPageNum >= $totalInboxPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=messages&tab=inbox&p=<?= $inboxPageNum + 1 ?>">
                            Suivant &rsaquo;
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'outbox'): ?>
        <!-- ========================================================
             3. BOÎTE D'ENVOI (Liste paginée)
             ======================================================== -->
        <?php if (empty($outbox)): ?>
            <div class="card-body text-center py-5">
                <div class="display-3 mb-2">📤</div>
                <h3 class="fw-bold text-dark">Aucune missive envoyée</h3>
                <p class="text-secondary small">Vos correspondances diplomatiques expédiées apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover table-striped card-table">
                    <thead>
                        <tr>
                            <th class="w-1 text-center"></th>
                            <th style="min-width: 180px;">Destinataire</th>
                            <th>Objet de la missive</th>
                            <th style="width: 170px;">Envoyé le</th>
                            <th class="w-1 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagedOutbox as $m): ?>
                            <tr onclick="window.location.href='?page=messages&tab=outbox&msg_id=<?= $m['id'] ?>'" style="cursor:pointer;">
                                <td class="text-center text-muted" style="opacity:0.6;">📤</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-4"><?= $m['receiver_faction'] ? (FACTIONS[$m['receiver_faction']]['icon'] ?? '👤') : '👤' ?></span>
                                        <div>
                                            <div class="text-dark fw-bold"><?= htmlspecialchars($m['receiver_name']) ?></div>
                                            <?php if (!empty($m['receiver_faction'])): ?>
                                                <div class="text-muted small">Clan <?= htmlspecialchars(ucfirst($m['receiver_faction'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark">
                                        <?= htmlspecialchars($m['subject']) ?>
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    <?= date('d/m/Y H:i', $m['created_at']) ?>
                                </td>
                                <td class="text-end" onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-sm btn-ghost-danger" onclick="deleteMessage(<?= $m['id'] ?>)" title="Supprimer la copie de la missive">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination de la boîte d'envoi -->
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                <p class="m-0 text-secondary small">
                    Affichage de <strong><?= ($totalOutbox > 0) ? ($outboxOffset + 1) : 0 ?></strong> à <strong><?= min($outboxOffset + $perPage, $totalOutbox) ?></strong> sur <strong><?= $totalOutbox ?></strong> missives
                </p>
                <?php if ($totalOutboxPages > 1): ?>
                <ul class="pagination pagination-sm m-0 ms-auto">
                    <li class="page-item <?= ($outboxPageNum <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=messages&tab=outbox&p=<?= $outboxPageNum - 1 ?>">
                            &lsaquo; Précédent
                        </a>
                    </li>
                    <?php for ($p = 1; $p <= $totalOutboxPages; $p++): ?>
                        <li class="page-item <?= ($p === $outboxPageNum) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=messages&tab=outbox&p=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($outboxPageNum >= $totalOutboxPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=messages&tab=outbox&p=<?= $outboxPageNum + 1 ?>">
                            Suivant &rsaquo;
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'compose'): ?>
        <!-- ========================================================
             4. RÉDACTION D'UNE NOUVELLE MISSIVE
             ======================================================== -->
        <div class="card-body p-4">
            <div style="max-width: 720px; margin: 0 auto;">
                <h3 class="mb-3 fs-3 fw-bold d-flex align-items-center gap-2 text-dark">
                    <span>✍️</span> Rédiger une Missive Diplomatique
                </h3>

                <!-- Modèles Diplomatiques Rapides -->
                <div class="mb-3 p-3 rounded border bg-light-subtle">
                    <span class="form-label text-secondary small fw-bold mb-2 d-block">
                        ⚡ Modèles diplomatiques rapides :
                    </span>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyTemplate('pact')">
                            🤝 Pacte de Non-Agression
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyTemplate('trade')">
                            ⚖️ Accord Commercial
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyTemplate('alliance')">
                            🎌 Recrutement d'Alliance
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="applyTemplate('warning')">
                            ⚠️ Avertissement Militaire
                        </button>
                    </div>
                </div>

                <form id="composeForm" onsubmit="handleSendMessage(event)">
                    <!-- Destinataire -->
                    <div class="mb-3">
                        <label for="msg-receiver" class="form-label fw-bold text-dark">
                            Daimyō Destinataire :
                        </label>
                        <input type="text" id="msg-receiver" name="receiver" required 
                               value="<?= htmlspecialchars($presetTo) ?>"
                               list="player-list"
                               placeholder="Nom du Daimyō..."
                               class="form-control">
                        
                        <datalist id="player-list">
                            <?php foreach ($otherPlayers as $p): ?>
                                <option value="<?= htmlspecialchars($p['username']) ?>">
                                    <?= htmlspecialchars($p['username']) ?> (<?= FACTIONS[$p['faction']]['name'] ?? $p['faction'] ?> - <?= number_format($p['points']) ?> pts)
                                </option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Objet -->
                    <div class="mb-3">
                        <label for="msg-subject" class="form-label fw-bold text-dark">
                            Objet de la missive :
                        </label>
                        <input type="text" id="msg-subject" name="subject" required 
                               value="<?= htmlspecialchars($presetSubject) ?>"
                               placeholder="Ex: Demande de traité de paix, Échange de vivres..."
                               class="form-control">
                    </div>

                    <!-- Contenu -->
                    <div class="mb-4">
                        <label for="msg-body" class="form-label fw-bold text-dark">
                            Contenu de la missive :
                        </label>
                        <textarea id="msg-body" name="body" required rows="6" 
                                  placeholder="Rédigez votre missive diplomatique..."
                                  class="form-control" style="resize:vertical;"></textarea>
                    </div>

                    <!-- Boutons d'Action -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="?page=messages&tab=inbox" class="btn btn-outline-secondary">
                            Annuler
                        </a>
                        <button type="submit" id="btn-submit-msg" class="btn btn-primary fw-bold">
                            📜 Dépêcher le Messager
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function applyTemplate(type) {
    const subjInput = document.getElementById('msg-subject');
    const bodyInput = document.getElementById('msg-body');
    if (!subjInput || !bodyInput) return;

    switch (type) {
        case 'pact':
            subjInput.value = "Proposition de Pacte de Non-Agression";
            bodyInput.value = "Salutations Daimyō,\n\nJe vous propose d'établir un Pacte de Paix et de Non-Agression (PNA) entre nos fiefs respectifs. Une entente cordiale nous permettra de fortifier nos châteaux et cultiver nos rizières sans épuiser nos fiers guerriers.\n\nDans l'attente de votre sceau officiel,\nDaimyō <?= addslashes($user['username']) ?>";
            break;
        case 'trade':
            subjInput.value = "Offre d'Échange Commercial";
            bodyInput.value = "Salutations Daimyō,\n\nNos greniers et entrepôts disposent d'un excédent de ressources et je recherche un partenaire commercial pour des échanges réguliers de Bois, Pierre ou Riz Impérial.\n\nFaites-moi part de vos besoins.\nDaimyō <?= addslashes($user['username']) ?>";
            break;
        case 'alliance':
            subjInput.value = "Invitation à sceller une Alliance de Clans";
            bodyInput.value = "Salutations Daimyō,\n\nNous avons remarqué l'essor remarquable de votre fief. Notre clan recherche des Daimyōs vaillants et déterminés pour unifier les provinces sous une même bannière et mener des campagnes coordonnées.\n\nSeriez-vous prêt à unir nos destins ?\nDaimyō <?= addslashes($user['username']) ?>";
            break;
        case 'warning':
            subjInput.value = "AVERTISSEMENT : Mouvements de troupes hostiles";
            bodyInput.value = "Daimyō,\n\nNos éclaireurs et vigies ont aperçu des troupes en marche aux abords de nos terres. Tout raid ou incursion shinobi sur nos domaines sera considéré comme une déclaration de guerre immédiate.\n\nRappelez vos guerriers sans délai.\nDaimyō <?= addslashes($user['username']) ?>";
            break;
    }
}

async function handleSendMessage(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-msg');
    btn.disabled = true;
    btn.innerText = 'Dépêche en cours...';

    const receiver = document.getElementById('msg-receiver').value;
    const subject = document.getElementById('msg-subject').value;
    const body = document.getElementById('msg-body').value;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver', receiver);
    formData.append('subject', subject);
    formData.append('body', body);

    try {
        const res = await fetch('/api/messages.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            await showModalAlert(`Missive remise avec succès au messager du Daimyō ${data.receiver}.`, 'success', 'Missive Dépêchée');
            window.location.href = '?page=messages&tab=outbox';
        } else {
            showModalAlert(data.error || "Erreur lors de l'envoi de la missive.", 'error', 'Échec de transmission');
            btn.disabled = false;
            btn.innerText = '📜 Dépêcher le Messager';
        }
    } catch (err) {
        showModalAlert("Erreur de transmission avec vos coursiers.", 'error');
        btn.disabled = false;
        btn.innerText = '📜 Dépêcher le Messager';
    }
}

async function deleteMessage(messageId) {
    const confirmed = await showModalConfirm("Confirmez-vous la destruction définitive de cette missive ?", "Suppression de Missive");
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('message_id', messageId);

    try {
        const res = await fetch('/api/messages.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            window.location.href = '?page=messages&tab=<?= htmlspecialchars($tab) ?>';
        } else {
            showModalAlert(data.error || "Impossible de supprimer le message.", 'error');
        }
    } catch (e) {
        showModalAlert("Erreur réseau.", 'error');
    }
}
</script>
