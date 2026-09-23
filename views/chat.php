<?php
/**
 * Vue Dédiée : Salon de Discussion & Chat Féodal Plein Écran
 * Accessible via /?page=chat
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ChatEngine.php';

$chatEngine = new ChatEngine();
$chatUser = $user ?? Auth::getCurrentUser();
$hasAlliance = !empty($chatUser['alliance_id']);
$userAllianceId = $hasAlliance ? (int)$chatUser['alliance_id'] : 0;
$allianceTag = $chatUser['alliance_tag'] ?? '';
$allianceName = $chatUser['alliance_name'] ?? '';

$onlineUsers = $chatEngine->getOnlineChatters(10);
$conversations = $chatEngine->getRecentConversations((int)$chatUser['id']);
?>

<div class="container-xl py-3">
    <!-- En-tête de la page -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-muted">Échanges &amp; Diplomatie en Direct</div>
                <h2 class="page-title d-flex align-items-center gap-2" style="color:#b45309; font-weight:800;">
                    <span>🏮</span>
                    <span>Taverne du Shōgunat — Salon des Daimyōs</span>
                </h2>
                <div class="text-secondary small mt-1">
                    Échangez en direct avec tous les seigneurs de guerre du royaume, préparez vos offensives avec vos frères d'armes ou négociez des pactes secrets en tête-à-tête.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if ($hasAlliance): ?>
                        <a href="/?page=alliance" class="btn btn-outline-warning d-flex align-items-center gap-2">
                            <span>🎌</span> Mon Alliance [<?= htmlspecialchars($allianceTag) ?>]
                        </a>
                    <?php else: ?>
                        <a href="/?page=alliance" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <span>🎌</span> Rejoindre un Clan
                        </a>
                    <?php endif; ?>
                    <a href="/?page=resources" class="btn btn-secondary">
                        &larr; Retour au Fief
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Disposition en 2 Colonnes -->
    <div class="row row-cards">
        <!-- Colonne Gauche : Canaux & Daimyōs en Ligne -->
        <div class="col-lg-4 col-xl-3">
            <!-- Choix des Canaux -->
            <div class="card mb-3 shadow-sm border-0" style="border-top:3px solid #b45309 !important;">
                <div class="card-header py-2">
                    <h4 class="card-title m-0" style="font-size:0.9rem;">🏯 Salons de Discussion</h4>
                </div>
                <div class="list-group list-group-flush" id="chatFullChannelsList">
                    <a href="javascript:void(0)" onclick="setFullChatChannel('global')" id="fullChannelBtn_global"
                       class="list-group-item list-group-item-action d-flex align-items-center justify-content-between active">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.2rem;">🏯</span>
                            <div>
                                <div class="font-weight-bold" style="font-size:0.85rem;">Canal Général</div>
                                <div class="text-muted small" style="font-size:0.7rem;">Tout le Shogunat</div>
                            </div>
                        </div>
                        <span class="badge bg-warning-lt text-warning">Public</span>
                    </a>

                    <a href="javascript:void(0)" onclick="setFullChatChannel('alliance')" id="fullChannelBtn_alliance"
                       class="list-group-item list-group-item-action d-flex align-items-center justify-content-between <?= !$hasAlliance ? 'disabled opacity-50' : '' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.2rem;">🎌</span>
                            <div>
                                <div class="font-weight-bold" style="font-size:0.85rem;">Canal d'Alliance</div>
                                <div class="text-muted small" style="font-size:0.7rem;">
                                    <?= $hasAlliance ? htmlspecialchars($allianceName ?: 'Votre Clan') : 'Nécessite une alliance' ?>
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-secondary-lt"><?= $hasAlliance ? 'Privé' : '🔒' ?></span>
                    </a>
                </div>
            </div>

            <!-- Conversations Privées (Chuchotements) -->
            <div class="card mb-3 shadow-sm border-0" style="border-top:3px solid #6366f1 !important;">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h4 class="card-title m-0" style="font-size:0.9rem;">✉️ Chuchotements Privés</h4>
                    <span class="badge bg-indigo-lt"><?= count($conversations) ?></span>
                </div>
                <div class="list-group list-group-flush" id="chatFullWhispersList" style="max-height:220px; overflow-y:auto;">
                    <?php if (empty($conversations)): ?>
                        <div class="p-3 text-muted text-center small">
                            Aucune conversation privée récente. Cliquez sur un joueur pour initier un chuchotement.
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): ?>
                            <a href="javascript:void(0)" onclick="setFullChatWhisper(<?= (int)$c['user_id'] ?>, '<?= htmlspecialchars(addslashes($c['username'])) ?>')"
                               id="fullWhisperBtn_<?= (int)$c['user_id'] ?>"
                               class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:180px;">
                                    <span><?= $c['is_online'] ? '🟢' : '⚪' ?></span>
                                    <div class="text-truncate">
                                        <div class="font-weight-bold text-truncate" style="font-size:0.82rem;">
                                            <?= htmlspecialchars($c['username']) ?>
                                            <?php if ($c['alliance_tag']): ?>
                                                <span class="badge bg-dark text-white ms-1" style="font-size:0.6rem;">[<?= htmlspecialchars($c['alliance_tag']) ?>]</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small text-truncate" style="font-size:0.7rem;">
                                            <?= htmlspecialchars($c['last_message']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-muted small" style="font-size:0.68rem;"><?= htmlspecialchars($c['last_message_time']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Daimyōs Connectés -->
            <div class="card shadow-sm border-0" style="border-top:3px solid #16a34a !important;">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                        <span class="status-dot status-dot-animated bg-success" style="width:7px; height:7px;"></span>
                        <span>Daimyōs en Ligne</span>
                    </h4>
                    <span class="badge bg-success-lt"><?= count($onlineUsers) ?></span>
                </div>
                <div class="list-group list-group-flush" style="max-height:240px; overflow-y:auto;">
                    <?php foreach ($onlineUsers as $ou): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                            <div class="d-flex align-items-center gap-2">
                                <span><?= $ou['faction_icon'] ?></span>
                                <div>
                                    <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= (int)$ou['id'] ?>)"
                                       class="font-weight-bold text-dark text-decoration-none hover-underline" style="font-size:0.82rem;">
                                        <?= htmlspecialchars($ou['username']) ?>
                                    </a>
                                    <?php if ($ou['alliance_tag']): ?>
                                        <span class="badge bg-secondary-lt ms-1" style="font-size:0.62rem;">[<?= htmlspecialchars($ou['alliance_tag']) ?>]</span>
                                    <?php endif; ?>
                                    <?php if ($ou['is_admin']): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.58rem;">⭐</span>
                                    <?php elseif ($ou['is_moderator']): ?>
                                        <span class="badge bg-primary text-white ms-1" style="font-size:0.58rem;">🛡️</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ((int)$ou['id'] !== (int)$chatUser['id']): ?>
                                <button type="button" class="btn btn-sm btn-ghost-primary p-1"
                                        title="Chuchoter à <?= htmlspecialchars($ou['username']) ?>"
                                        onclick="setFullChatWhisper(<?= (int)$ou['id'] ?>, '<?= htmlspecialchars(addslashes($ou['username'])) ?>')">
                                    ✉️
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Fil de Discussion & Envoi -->
        <div class="col-lg-8 col-xl-9">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column" style="min-height:650px;">
                <!-- En-tête du Fil de Discussion -->
                <div class="card-header py-3 d-flex align-items-center justify-content-between"
                     style="background:linear-gradient(135deg, #1c1917 0%, #292524 100%); color:#ffffff; border-radius:8px 8px 0 0;">
                    <div class="d-flex align-items-center gap-3">
                        <span id="fullChatChannelIcon" style="font-size:1.8rem;">🏯</span>
                        <div>
                            <h3 class="m-0 font-weight-bold" id="fullChatChannelTitle" style="color:#fbbf24; font-size:1.15rem;">
                                Canal Général du Shōgunat
                            </h3>
                            <div class="text-muted small mt-1" id="fullChatChannelSubtitle" style="color:#d6d3d1 !important; font-size:0.78rem;">
                                Salon public ouvert à tous les Daimyōs de l'archipel
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1"
                                onclick="fetchFullMessages()" title="Actualiser les messages">
                            <span>🔄</span> Rafraîchir
                        </button>
                    </div>
                </div>

                <!-- Fil des Messages -->
                <div id="fullChatMessagesContainer" class="card-body p-3 flex-grow-1"
                     style="background:#fcfbf9; overflow-y:auto; max-height:500px; display:flex; flex-direction:column; gap:0.65rem;">
                    <div class="text-center py-5 text-muted">
                        <span>🏮 Connexion au salon féodal...</span>
                    </div>
                </div>

                <!-- Barre d'Emojis Féodaux -->
                <div class="px-3 py-1 bg-light border-top d-flex gap-2 align-items-center">
                    <span class="text-muted small" style="font-size:0.75rem;">Émoticônes :</span>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('⚔️')">⚔️</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🏯')">🏯</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🎌')">🎌</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🍵')">🍵</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🍶')">🍶</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🥷')">🥷</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('📜')">📜</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🌾')">🌾</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🔥')">🔥</button>
                    <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-2" onclick="insertFullEmoji('🛡️')">🛡️</button>
                </div>

                <!-- Zone de Saisie & Envoi -->
                <div class="card-footer p-3 bg-white border-top">
                    <form id="fullChatForm" onsubmit="handleFullSend(event)" class="d-flex gap-2 m-0">
                        <textarea id="fullChatInput" class="form-control" rows="2" maxlength="1000"
                                  placeholder="Rédigez votre message aux Daimyōs... (Appuyez sur Entrée pour envoyer, Maj+Entrée pour un saut de ligne)"
                                  style="font-size:0.88rem; resize:none; border-color:#d6d3d1;"></textarea>
                        <button type="submit" id="fullChatSubmitBtn" class="btn btn-primary px-4 d-flex flex-column align-items-center justify-content-center"
                                style="background:#b45309; border-color:#92400e; font-weight:800; min-width:110px;">
                            <span>Envoyer</span>
                            <span style="font-size:0.7rem; font-weight:400; opacity:0.85;">(Entrée)</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let fullChannel = 'global';
let fullWhisperTargetId = null;
let fullWhisperTargetName = '';
let fullLastId = 0;
let fullPollingTimer = null;
const fullCurrentUserId = <?= (int)$chatUser['id'] ?>;
const fullHasAlliance = <?= $hasAlliance ? 'true' : 'false' ?>;
const fullAllianceId = <?= $userAllianceId ?>;
const fullRenderedIds = new Set();

function setFullChatChannel(ch) {
    if (ch === 'alliance' && !fullHasAlliance) {
        alert("Vous devez appartenir à une alliance pour accéder à ce canal.");
        return;
    }

    fullChannel = ch;
    fullWhisperTargetId = null;
    fullWhisperTargetName = '';

    // Style des boutons
    document.querySelectorAll('#chatFullChannelsList .list-group-item').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#chatFullWhispersList .list-group-item').forEach(el => el.classList.remove('active'));

    const activeBtn = document.getElementById(`fullChannelBtn_${ch}`);
    if (activeBtn) activeBtn.classList.add('active');

    // Mettre à jour l'en-tête
    const icon = document.getElementById('fullChatChannelIcon');
    const title = document.getElementById('fullChatChannelTitle');
    const sub = document.getElementById('fullChatChannelSubtitle');
    const inp = document.getElementById('fullChatInput');

    if (ch === 'global') {
        icon.innerText = '🏯';
        title.innerText = 'Canal Général du Shōgunat';
        sub.innerText = 'Salon public ouvert à tous les Daimyōs de l\'archipel';
        inp.placeholder = 'Rédigez votre message à l\'ensemble du Shōgunat...';
    } else if (ch === 'alliance') {
        icon.innerText = '🎌';
        title.innerText = 'Canal du Clan Féodal';
        sub.innerText = 'Salon secret réservé exclusivement aux membres de votre alliance';
        inp.placeholder = 'Rédigez votre message secret à vos frères d\'armes...';
    }

    // Réinitialiser le fil et charger
    resetAndLoadFullChat();
}

function setFullChatWhisper(targetId, targetName) {
    fullChannel = 'whisper';
    fullWhisperTargetId = targetId;
    fullWhisperTargetName = targetName;

    document.querySelectorAll('#chatFullChannelsList .list-group-item').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('#chatFullWhispersList .list-group-item').forEach(el => el.classList.remove('active'));

    const wBtn = document.getElementById(`fullWhisperBtn_${targetId}`);
    if (wBtn) wBtn.classList.add('active');

    document.getElementById('fullChatChannelIcon').innerText = '✉️';
    document.getElementById('fullChatChannelTitle').innerText = `Chuchotement avec ${targetName}`;
    document.getElementById('fullChatChannelSubtitle').innerText = `Conversation privée et confidentielle en tête-à-tête`;
    document.getElementById('fullChatInput').placeholder = `Chuchoter un message privé à ${targetName}...`;

    resetAndLoadFullChat();
}

function resetAndLoadFullChat() {
    fullLastId = 0;
    fullRenderedIds.clear();
    document.getElementById('fullChatMessagesContainer').innerHTML = '<div class="text-center py-5 text-muted"><span>Chargement des échanges...</span></div>';
    fetchFullMessages();
}

async function fetchFullMessages() {
    let url = `/api/chat.php?action=fetch&channel_type=${fullChannel}&last_id=${fullLastId}&limit=80`;
    if (fullChannel === 'alliance') {
        url += `&target_id=${fullAllianceId}`;
    } else if (fullChannel === 'whisper') {
        if (!fullWhisperTargetId) return;
        url += `&other_user_id=${fullWhisperTargetId}`;
    }

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.success && data.messages) {
            renderFullMessages(data.messages);
            if (data.last_id > fullLastId) {
                fullLastId = data.last_id;
            }
        }
    } catch (e) {
        // Ignorer lors du polling
    }
}

function renderFullMessages(messages) {
    const container = document.getElementById('fullChatMessagesContainer');
    if (fullRenderedIds.size === 0) {
        container.innerHTML = '';
    }

    let atBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 80);

    if (messages.length === 0 && fullRenderedIds.size === 0) {
        container.innerHTML = '<div class="text-center py-5 text-muted"><span>Aucun message dans ce salon pour le moment. Soyez le premier à proclamer un message !</span></div>';
        return;
    }

    messages.forEach(m => {
        if (fullRenderedIds.has(m.id)) return;
        fullRenderedIds.add(m.id);

        const msgEl = document.createElement('div');
        msgEl.className = `p-3 rounded mb-2 ${m.is_self ? 'bg-orange-lt border-start border-4 border-warning' : 'bg-white border'}`;
        msgEl.style.boxShadow = '0 1px 3px rgba(0,0,0,0.04)';
        msgEl.id = `fullChatMsg_${m.id}`;

        let roleBadge = '';
        if (m.sender_is_admin) {
            roleBadge = '<span class="badge bg-warning text-dark ms-2">⭐ Administrateur</span>';
        } else if (m.sender_is_moderator) {
            roleBadge = '<span class="badge bg-primary text-white ms-2">🛡️ Modérateur</span>';
        }

        let clanBadge = m.sender_alliance_tag ? `<span class="badge bg-dark text-white ms-2">[${escapeFullHtml(m.sender_alliance_tag)}]</span>` : '';

        let delBtn = m.can_delete ? `
            <button type="button" class="btn btn-sm btn-ghost-danger p-0 ms-2" title="Supprimer ce message" onclick="deleteFullChatMessage(${m.id})">
                🗑️
            </button>
        ` : '';

        let whisperBtn = !m.is_self ? `
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 ms-2" title="Chuchoter en privé" onclick="setFullChatWhisper(${m.sender_id}, '${escapeFullHtml(m.sender_username)}')">
                ✉️
            </button>
        ` : '';

        msgEl.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-1">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="me-2" style="font-size:1.1rem;">${m.sender_faction_icon || '🏯'}</span>
                    <a href="javascript:void(0)" onclick="openPlayerProfileModal(${m.sender_id})"
                       class="fw-bold text-decoration-none text-dark hover-underline" style="font-size:0.92rem;">
                        ${escapeFullHtml(m.sender_username)}
                    </a>
                    ${clanBadge}
                    ${roleBadge}
                    ${whisperBtn}
                </div>
                <div class="d-flex align-items-center text-muted" style="font-size:0.75rem;">
                    <span>${m.date_formatted}</span>
                    ${delBtn}
                </div>
            </div>
            <div class="full-chat-msg-body text-break mt-1" style="font-size:0.9rem; line-height:1.5;">
                ${m.message}
            </div>
        `;

        container.appendChild(msgEl);
    });

    if (atBottom) {
        container.scrollTop = container.scrollHeight;
    }
}

async function handleFullSend(e) {
    e.preventDefault();
    const inp = document.getElementById('fullChatInput');
    const text = inp.value.trim();
    if (!text) return;

    if (fullChannel === 'whisper' && !fullWhisperTargetId) {
        alert("Veuillez sélectionner un destinataire pour chuchoter.");
        return;
    }

    const btn = document.getElementById('fullChatSubmitBtn');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('channel_type', fullChannel);
    formData.append('message', text);
    if (fullChannel === 'alliance') {
        formData.append('target_id', fullAllianceId);
    } else if (fullChannel === 'whisper') {
        formData.append('recipient_id', fullWhisperTargetId);
    }

    try {
        const res = await fetch('/api/chat.php?action=send', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            inp.value = '';
            fetchFullMessages();
        } else {
            alert(data.error || "Erreur lors de l'envoi.");
        }
    } catch (err) {
        alert("Erreur de connexion.");
    } finally {
        btn.disabled = false;
        inp.focus();
    }
}

async function deleteFullChatMessage(msgId) {
    if (!confirm("Voulez-vous supprimer ce message ?")) return;

    const formData = new FormData();
    formData.append('message_id', msgId);

    try {
        const res = await fetch('/api/chat.php?action=delete', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById(`fullChatMsg_${msgId}`);
            if (el) {
                el.querySelector('.full-chat-msg-body').innerHTML = '<em>Message retiré par le Shogunat ou son auteur</em>';
            }
        } else {
            alert(data.error || "Action impossible.");
        }
    } catch (e) {
        alert("Erreur réseau.");
    }
}

function insertFullEmoji(emoji) {
    const inp = document.getElementById('fullChatInput');
    if (inp) {
        inp.value += emoji;
        inp.focus();
    }
}

function escapeFullHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Support Entrée pour envoyer (sans Shift)
document.addEventListener('DOMContentLoaded', () => {
    const inp = document.getElementById('fullChatInput');
    if (inp) {
        inp.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleFullSend(e);
            }
        });
    }

    // Premier chargement
    fetchFullMessages();

    // Polling toutes les 3.5s
    fullPollingTimer = setInterval(fetchFullMessages, 3500);
});
</script>

