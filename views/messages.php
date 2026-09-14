<?php
/**
 * Centre de Communications Interstellaires (Messagerie entre Joueurs)
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
}

$inbox = $messageEngine->getInbox($userId);
$outbox = $messageEngine->getOutbox($userId);
$unreadCount = $messageEngine->getUnreadCount($userId);
$otherPlayers = $messageEngine->getAllOtherPlayers($userId);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">📜 Messagers & Missives Diplomatiques des Clans</h2>
        <div style="display:flex; gap:0.5rem;">
            <button class="btn <?= ($tab === 'inbox' && !$activeMessage) ? 'btn-primary' : 'btn-secondary' ?>" 
                    onclick="switchTab('inbox')" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                📥 Réception <?php if ($unreadCount > 0): ?><span class="badge-unread"><?= $unreadCount ?></span><?php endif; ?>
            </button>
            <button class="btn <?= ($tab === 'outbox' && !$activeMessage) ? 'btn-primary' : 'btn-secondary' ?>" 
                    onclick="switchTab('outbox')" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                📤 Envoyés (<?= count($outbox) ?>)
            </button>
            <button class="btn <?= ($tab === 'compose' || $presetTo) ? 'btn-primary' : 'btn-secondary' ?>" 
                    onclick="switchTab('compose')" style="font-size:0.8rem; padding:0.35rem 0.75rem;">
                ✍️ Rédiger une Missive
            </button>
        </div>
    </div>
    <div class="card-body">
        
        <!-- 1. VUE LECTURE D'UN MESSAGE SPÉCIFIQUE -->
        <?php if ($activeMessage): ?>
            <div style="background:rgba(15,23,42,0.85); border:1px solid var(--border-highlight); border-radius:8px; padding:1.25rem; margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:1rem;">
                    <div>
                        <h3 style="color:#fff; font-size:1.15rem; margin-bottom:0.4rem;">
                            <?= htmlspecialchars($activeMessage['subject']) ?>
                        </h3>
                        <div style="font-size:0.85rem; color:var(--text-muted); display:flex; gap:1.25rem; flex-wrap:wrap;">
                            <span>
                                <strong>Expéditeur :</strong> 
                                <?= $activeMessage['sender_name'] ? htmlspecialchars($activeMessage['sender_name']) : 'Mandat Impérial du Shogunat' ?>
                                <?php if ($activeMessage['sender_faction']): ?>
                                    (<?= htmlspecialchars(FACTIONS[$activeMessage['sender_faction']]['name'] ?? $activeMessage['sender_faction']) ?>)
                                <?php endif; ?>
                            </span>
                            <span>
                                <strong>Destinataire :</strong> <?= htmlspecialchars($activeMessage['receiver_name']) ?>
                            </span>
                            <span>
                                <strong>Date :</strong> <?= date('d/m/Y à H:i:s', $activeMessage['created_at']) ?>
                            </span>
                        </div>
                    </div>
                    <button class="btn btn-secondary" onclick="window.location.href='?page=messages&tab=<?= $tab ?>'" style="font-size:0.8rem;">
                        &larr; Retour
                    </button>
                </div>

                <!-- Corps du Message -->
                <div style="background:rgba(0,0,0,0.3); border-radius:6px; padding:1.25rem; line-height:1.6; color:#e2e8f0; font-size:0.95rem; white-space:pre-wrap; min-height:120px; border:1px solid rgba(255,255,255,0.05);">
<?= htmlspecialchars($activeMessage['body']) ?>
                </div>

                <!-- Actions du Message -->
                <div style="display:flex; justify-content:space-between; margin-top:1.25rem;">
                    <div>
                        <?php if ($activeMessage['sender_id'] && (int)$activeMessage['sender_id'] !== $userId): ?>
                            <a href="?page=messages&tab=compose&to=<?= urlencode($activeMessage['sender_name']) ?>&subject=<?= urlencode('Re: ' . $activeMessage['subject']) ?>" 
                               class="btn btn-primary" style="font-size:0.85rem; padding:0.4rem 1rem;">
                                ↩️ Répondre
                            </a>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-secondary" style="color:#ef4444; border-color:rgba(239,68,68,0.4); font-size:0.85rem;" 
                            onclick="deleteMessage(<?= $activeMessage['id'] ?>)">
                        🗑️ Supprimer le message
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. ONGLET BOÎTE DE RÉCEPTION -->
        <div id="tab-inbox" style="<?= ($tab === 'inbox' && !$activeMessage) ? 'display:block;' : 'display:none;' ?>">
            <?php if (empty($inbox)): ?>
                <div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
                    <div style="font-size:2.5rem; margin-bottom:0.75rem;">📭</div>
                    <h3>Votre boîte de réception est vide</h3>
                    <p style="font-size:0.85rem;">Aucune missive reçue pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="msg-table-container">
                    <table class="msg-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Expéditeur</th>
                                <th>Objet de la missive</th>
                                <th style="width:160px;">Reçu le</th>
                                <th style="width:100px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inbox as $m): ?>
                                <tr class="<?= !$m['is_read'] ? 'msg-unread' : '' ?>" 
                                    onclick="window.location.href='?page=messages&tab=inbox&msg_id=<?= $m['id'] ?>'" 
                                    style="cursor:pointer;">
                                    <td style="text-align:center;">
                                        <?= !$m['is_read'] ? '<span class="unread-dot" title="Non lu">●</span>' : '<span style="opacity:0.3;">✉️</span>' ?>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.4rem;">
                                            <span><?= $m['sender_faction'] ? (FACTIONS[$m['sender_faction']]['icon'] ?? '👤') : '🤖' ?></span>
                                            <strong><?= $m['sender_name'] ? htmlspecialchars($m['sender_name']) : 'Mandat Impérial' ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="msg-subject <?= !$m['is_read'] ? 'font-bold' : '' ?>">
                                            <?= htmlspecialchars($m['subject']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.8rem;">
                                        <?= date('d/m/Y H:i', $m['created_at']) ?>
                                    </td>
                                    <td style="text-align:right;" onclick="event.stopPropagation();">
                                        <button class="btn-cancel" onclick="deleteMessage(<?= $m['id'] ?>)" title="Supprimer">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. ONGLET MESSAGES ENVOYÉS -->
        <div id="tab-outbox" style="<?= ($tab === 'outbox' && !$activeMessage) ? 'display:block;' : 'display:none;' ?>">
            <?php if (empty($outbox)): ?>
                <div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
                    <div style="font-size:2.5rem; margin-bottom:0.75rem;">📤</div>
                    <h3>Aucun message envoyé</h3>
                    <p style="font-size:0.85rem;">Vos missives expédiées apparaîtront ici.</p>
                </div>
            <?php else: ?>
                <div class="msg-table-container">
                    <table class="msg-table">
                        <thead>
                            <tr>
                                <th style="width:40px;"></th>
                                <th>Destinataire</th>
                                <th>Objet de la missive</th>
                                <th style="width:160px;">Envoyé le</th>
                                <th style="width:100px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outbox as $m): ?>
                                <tr onclick="window.location.href='?page=messages&tab=outbox&msg_id=<?= $m['id'] ?>'" style="cursor:pointer;">
                                    <td style="text-align:center; opacity:0.6;">📤</td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.4rem;">
                                            <span><?= $m['receiver_faction'] ? (FACTIONS[$m['receiver_faction']]['icon'] ?? '👤') : '👤' ?></span>
                                            <strong><?= htmlspecialchars($m['receiver_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="msg-subject">
                                            <?= htmlspecialchars($m['subject']) ?>
                                        </span>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.8rem;">
                                        <?= date('d/m/Y H:i', $m['created_at']) ?>
                                    </td>
                                    <td style="text-align:right;" onclick="event.stopPropagation();">
                                        <button class="btn-cancel" onclick="deleteMessage(<?= $m['id'] ?>)" title="Supprimer">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. ONGLET RÉDACTION (NOUVEAU MESSAGE) -->
        <div id="tab-compose" style="<?= ($tab === 'compose' || (!empty($presetTo) && !$activeMessage)) ? 'display:block;' : 'display:none;' ?>">
            <div style="max-width:720px; margin:0 auto; background:rgba(15,23,42,0.85); border:1px solid var(--border-color); border-radius:8px; padding:1.5rem;">
                <h3 style="margin-bottom:1.25rem; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem; color:#fff;">
                    <span>✍️</span> Rédiger une Transmission Galactique
                </h3>

                <!-- Modèles Diplomatiques Rapides -->
                <div style="margin-bottom:1.25rem;">
                    <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">
                        ⚡ Modèles diplomatiques rapides :
                    </span>
                    <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                        <button type="button" class="btn btn-secondary" style="font-size:0.72rem; padding:0.25rem 0.55rem;"
                                onclick="applyTemplate('pact')">
                            🤝 Pacte de Non-Agression
                        </button>
                        <button type="button" class="btn btn-secondary" style="font-size:0.72rem; padding:0.25rem 0.55rem;"
                                onclick="applyTemplate('trade')">
                            ⚖️ Accord Commercial
                        </button>
                        <button type="button" class="btn btn-secondary" style="font-size:0.72rem; padding:0.25rem 0.55rem;"
                                onclick="applyTemplate('alliance')">
                            🌌 Recrutement d'Alliance
                        </button>
                        <button type="button" class="btn btn-secondary" style="font-size:0.72rem; padding:0.25rem 0.55rem; color:#f87171;"
                                onclick="applyTemplate('warning')">
                            ⚠️ Avertissement Militaire
                        </button>
                    </div>
                </div>

                <form id="composeForm" onsubmit="handleSendMessage(event)">
                    <!-- Destinataire -->
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label for="msg-receiver" style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.4rem; color:#94a3b8;">
                            Commandant Destinataire :
                        </label>
                        <div style="position:relative;">
                            <input type="text" id="msg-receiver" name="receiver" required 
                                   value="<?= htmlspecialchars($presetTo) ?>"
                                   list="player-list"
                                   placeholder="Nom du joueur adverse..."
                                   class="form-control" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid var(--border-color); color:#fff; padding:0.6rem 0.8rem; border-radius:6px; font-size:0.9rem;">
                            
                            <datalist id="player-list">
                                <?php foreach ($otherPlayers as $p): ?>
                                    <option value="<?= htmlspecialchars($p['username']) ?>">
                                        <?= htmlspecialchars($p['username']) ?> (<?= FACTIONS[$p['faction']]['name'] ?? $p['faction'] ?> - <?= number_format($p['points']) ?> pts)
                                    </option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>

                    <!-- Objet -->
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label for="msg-subject" style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.4rem; color:#94a3b8;">
                            Objet de la missive :
                        </label>
                        <input type="text" id="msg-subject" name="subject" required 
                               value="<?= htmlspecialchars($presetSubject) ?>"
                               placeholder="Ex: Demande de traité de paix, Échange de vivres..."
                               class="form-control" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid var(--border-color); color:#fff; padding:0.6rem 0.8rem; border-radius:6px; font-size:0.9rem;">
                    </div>

                    <!-- Contenu -->
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label for="msg-body" style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.4rem; color:#94a3b8;">
                            Contenu de la missive :
                        </label>
                        <textarea id="msg-body" name="body" required rows="6" 
                                  placeholder="Rédigez votre missive diplomatique..."
                                  class="form-control" style="width:100%; background:rgba(0,0,0,0.4); border:1px solid var(--border-color); color:#fff; padding:0.8rem; border-radius:6px; font-size:0.9rem; line-height:1.5; font-family:inherit; resize:vertical;"></textarea>
                    </div>

                    <!-- Boutons d'Action -->
                    <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                        <button type="button" class="btn btn-secondary" onclick="switchTab('inbox')" style="padding:0.5rem 1.25rem;">
                            Annuler
                        </button>
                        <button type="submit" id="btn-submit-msg" class="btn btn-primary" style="padding:0.5rem 1.5rem; font-weight:700; background: linear-gradient(135deg, #b91c1c, #dc2626); border-color:#ef4444;">
                            📜 Dépêcher le Messager
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tabName) {
    document.getElementById('tab-inbox').style.display = (tabName === 'inbox') ? 'block' : 'none';
    document.getElementById('tab-outbox').style.display = (tabName === 'outbox') ? 'block' : 'none';
    document.getElementById('tab-compose').style.display = (tabName === 'compose') ? 'block' : 'none';
}

function applyTemplate(type) {
    const subjInput = document.getElementById('msg-subject');
    const bodyInput = document.getElementById('msg-body');

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
            window.location.href = '?page=messages&tab=<?= $tab ?>';
        } else {
            showModalAlert(data.error || "Impossible de supprimer le message.", 'error');
        }
    } catch (e) {
        showModalAlert("Erreur réseau.", 'error');
    }
}
</script>

