<?php
/**
 * Widget Flottant : Chat Féodal en Direct (Docked Bottom-Right)
 * Intégré sur toutes les pages de jeu pour échanger en direct
 */
$chatUser = $user ?? Auth::getCurrentUser();
$hasAlliance = !empty($chatUser['alliance_id']);
$userAllianceId = $hasAlliance ? (int)$chatUser['alliance_id'] : 0;
?>

<!-- ── WIDGET CHAT FLOTTANT FÉODAL ── -->
<div id="feudalChatContainer" class="d-print-none" style="position:fixed; bottom:15px; right:20px; z-index:1050; font-family:var(--tblr-font-sans-serif, sans-serif);">

    <!-- Bouton Rétracté (Pilule Flottante) -->
    <button type="button" id="feudalChatToggleBtn" onclick="window.feudalChat && window.feudalChat.toggle()"
            class="btn shadow-lg d-flex align-items-center gap-2"
            style="background:linear-gradient(135deg, #1c1917 0%, #292524 100%); color:#fbbf24; border:2px solid #b45309; border-radius:30px; padding:0.5rem 1rem; font-weight:700; font-size:0.85rem; transition:transform 0.2s, box-shadow 0.2s;">
        <span style="font-size:1.1rem;">🏮</span>
        <span>Chat Féodal</span>
        <span id="feudalChatUnreadBadge" class="badge bg-danger text-white rounded-pill d-none" style="font-size:0.7rem; padding:0.25rem 0.5rem;">0</span>
    </button>

    <!-- Fenêtre de Chat Dépliée -->
    <div id="feudalChatWindow" class="card shadow-lg d-none"
         style="width:360px; max-width:calc(100vw - 30px); height:480px; max-height:calc(100vh - 100px); display:flex; flex-direction:column; background:#ffffff; border:2px solid #b45309; border-radius:12px; overflow:hidden;">
        
        <!-- En-tête Chat -->
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between text-white"
             style="background:linear-gradient(135deg, #1c1917 0%, #292524 100%); border-bottom:1px solid #78350f;">
            <div class="d-flex align-items-center gap-2">
                <span style="font-size:1.15rem;">🏮</span>
                <div>
                    <h5 class="m-0 font-weight-bold" style="font-size:0.9rem; color:#fef3c7;">Taverne du Shōgunat</h5>
                    <div class="text-muted small" style="font-size:0.7rem; color:#d6d3d1 !important;">
                        <span class="status-dot status-dot-animated bg-success d-inline-block me-1" style="width:6px; height:6px;"></span>
                        <span id="feudalChatOnlineCounter">En direct</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <a href="/?page=chat" class="btn btn-sm btn-icon text-muted" title="Ouvrir le salon en plein écran" style="color:#d6d3d1 !important;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                </a>
                <button type="button" class="btn btn-sm btn-icon text-muted" onclick="window.feudalChat && window.feudalChat.toggle()" title="Réduire le chat" style="color:#d6d3d1 !important; font-size:1.2rem; line-height:1;">
                    &times;
                </button>
            </div>
        </div>

        <!-- Onglets des Canaux -->
        <div class="px-2 pt-2 bg-light border-bottom">
            <ul class="nav nav-tabs nav-fill card-header-tabs" id="feudalChatTabs" style="margin:0; border-bottom:none;">
                <li class="nav-item">
                    <button class="nav-link active py-1 px-2 small font-weight-bold" id="chatTabGlobal" onclick="window.feudalChat.setChannel('global')">
                        <span>🏯 Général</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-1 px-2 small font-weight-bold <?= !$hasAlliance ? 'text-muted' : '' ?>" id="chatTabAlliance" onclick="window.feudalChat.setChannel('alliance')" title="<?= $hasAlliance ? 'Canal de votre Alliance' : 'Rejoignez un clan féodal pour accéder à ce canal' ?>">
                        <span>🎌 Clan</span>
                        <?php if (!$hasAlliance): ?><span style="font-size:0.7rem;">🔒</span><?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-1 px-2 small font-weight-bold position-relative" id="chatTabWhisper" onclick="window.feudalChat.setChannel('whisper')">
                        <span>✉️ Privé</span>
                        <span id="feudalWhisperUnreadDot" class="badge bg-danger rounded-circle p-1 d-none position-absolute" style="top:2px; right:4px;"></span>
                    </button>
                </li>
            </ul>
        </div>

        <!-- Barre de Contact Actif (Pour le mode Chuchotement) -->
        <div id="feudalChatWhisperBar" class="py-1 px-3 bg-indigo-lt border-bottom d-none align-items-center justify-content-between" style="font-size:0.75rem;">
            <div class="d-flex align-items-center gap-1">
                <span>Chuchotement à :</span>
                <strong id="feudalWhisperTargetName" class="text-primary">Daimyō</strong>
            </div>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="window.feudalChat.showWhisperList()" style="font-size:0.72rem;">Changer</button>
        </div>

        <!-- Zone Sélecteur de Contact Privé (Masqué par défaut) -->
        <div id="feudalChatWhisperListPanel" class="p-2 border-bottom bg-white d-none" style="max-height:140px; overflow-y:auto; font-size:0.8rem;">
            <div class="font-weight-bold text-muted small mb-1">Conversations privées récentes :</div>
            <div id="feudalChatConversationsContainer">
                <span class="text-muted small">Chargement...</span>
            </div>
        </div>

        <!-- Fil des Messages Déroulant -->
        <div id="feudalChatMessages" class="flex-grow-1 p-2"
             style="overflow-y:auto; background:#fafaf9; font-size:0.82rem; display:flex; flex-direction:column; gap:0.45rem;">
            <div class="text-center py-4 text-muted small">
                <span>🏮 Connexion au salon...</span>
            </div>
        </div>

        <!-- Raccourcis Emojis Rapides Féodaux -->
        <div class="px-2 py-1 bg-light border-top d-flex gap-1 overflow-x-auto" style="scrollbar-width:none; font-size:0.85rem;">
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('⚔️')">⚔️</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🏯')">🏯</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🎌')">🎌</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🍵')">🍵</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🍶')">🍶</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🥷')">🥷</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('📜')">📜</button>
            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🌾')">🌾</button>
        </div>

        <!-- Barre de Saisie & Envoi -->
        <div class="card-footer p-2 bg-white border-top">
            <form id="feudalChatForm" onsubmit="window.feudalChat.handleSend(event)" class="d-flex gap-1 m-0">
                <input type="text" id="feudalChatInput" class="form-control form-control-sm"
                       placeholder="Votre message au Shogunat..." maxlength="1000" autocomplete="off"
                       style="font-size:0.82rem; border-color:#d6d3d1;">
                <button type="submit" id="feudalChatSubmitBtn" class="btn btn-primary btn-sm px-3"
                        style="background:#b45309; border-color:#92400e; font-weight:700;">
                    Envoyer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ── SCRIPT DU CHAT FÉODAL FLOTTANT ── -->
<script>
class FeudalChatClient {
    constructor() {
        this.isOpen = false;
        this.channel = 'global'; // 'global', 'alliance', 'whisper'
        this.whisperTargetId = null;
        this.whisperTargetName = '';
        this.lastIds = { global: 0, alliance: 0, whisper: 0 };
        this.pollingTimer = null;
        this.currentUserId = <?= (int)($chatUser['id'] ?? 0) ?>;
        this.hasAlliance = <?= $hasAlliance ? 'true' : 'false' ?>;
        this.allianceId = <?= $userAllianceId ?>;
        this.renderedMsgIds = new Set();
        this.unreadCount = 0;

        // Écouter la touche Echap pour fermer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.toggle();
            }
        });
    }

    init() {
        // Démarrer un premier polling discret
        this.fetchMessages(true);
        this.startPolling();
    }

    toggle() {
        this.isOpen = !this.isOpen;
        const win = document.getElementById('feudalChatWindow');
        const btn = document.getElementById('feudalChatToggleBtn');

        if (this.isOpen) {
            win.classList.remove('d-none');
            btn.classList.add('d-none');
            this.clearUnread();
            this.scrollToBottom();
            const inp = document.getElementById('feudalChatInput');
            if (inp) inp.focus();
        } else {
            win.classList.add('d-none');
            btn.classList.remove('d-none');
        }
    }

    setChannel(ch, targetId = null, targetName = '') {
        if (ch === 'alliance' && !this.hasAlliance) {
            alert("Vous devez appartenir à une alliance féodale pour accéder à ce canal.");
            return;
        }

        this.channel = ch;
        if (targetId) {
            this.whisperTargetId = targetId;
            this.whisperTargetName = targetName;
        }

        // MAJ Onglets
        document.querySelectorAll('#feudalChatTabs .nav-link').forEach(btn => btn.classList.remove('active'));
        if (ch === 'global') document.getElementById('chatTabGlobal').classList.add('active');
        if (ch === 'alliance') document.getElementById('chatTabAlliance').classList.add('active');
        if (ch === 'whisper') document.getElementById('chatTabWhisper').classList.add('active');

        // Barre Chuchotement
        const wBar = document.getElementById('feudalChatWhisperBar');
        const wList = document.getElementById('feudalChatWhisperListPanel');
        if (ch === 'whisper') {
            wBar.classList.remove('d-none');
            wBar.classList.add('d-flex');
            document.getElementById('feudalWhisperTargetName').innerText = this.whisperTargetName || 'Sélectionner un Daimyō';
            if (!this.whisperTargetId) {
                this.showWhisperList();
            }
        } else {
            wBar.classList.add('d-none');
            wBar.classList.remove('d-flex');
            wList.classList.add('d-none');
        }

        // Placeholder input
        const inp = document.getElementById('feudalChatInput');
        if (ch === 'global') inp.placeholder = "Message public à tout le Shogunat...";
        else if (ch === 'alliance') inp.placeholder = "Message secret aux membres du clan...";
        else if (ch === 'whisper') inp.placeholder = `Chuchoter à ${this.whisperTargetName || 'un Daimyō'}...`;

        // Réinitialiser les messages rendus pour ce canal et charger
        this.renderedMsgIds.clear();
        document.getElementById('feudalChatMessages').innerHTML = '<div class="text-center py-4 text-muted small"><span>Chargement du salon...</span></div>';
        this.lastIds[this.channel] = 0;
        this.fetchMessages();
    }

    showWhisperList() {
        const p = document.getElementById('feudalChatWhisperListPanel');
        p.classList.toggle('d-none');
        if (!p.classList.contains('d-none')) {
            this.loadConversations();
        }
    }

    async loadConversations() {
        const container = document.getElementById('feudalChatConversationsContainer');
        try {
            const res = await fetch('/api/chat.php?action=conversations');
            const data = await res.json();
            if (data.success && data.conversations.length > 0) {
                container.innerHTML = data.conversations.map(c => `
                    <div class="d-flex align-items-center justify-content-between p-1 rounded hover-bg"
                         style="cursor:pointer; border-bottom:1px solid #f5f5f4;"
                         onclick="window.feudalChat.selectWhisperTarget(${c.user_id}, '${this.escapeHtml(c.username)}')">
                        <div class="d-flex align-items-center gap-1">
                            <span>${c.is_online ? '🟢' : '⚪'}</span>
                            <strong>${this.escapeHtml(c.username)}</strong>
                            ${c.alliance_tag ? `<span class="badge bg-secondary-lt">[${this.escapeHtml(c.alliance_tag)}]</span>` : ''}
                        </div>
                        <span class="text-muted" style="font-size:0.7rem;">${c.last_message_time}</span>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<span class="text-muted small">Aucune conversation privée récente. Cliquez sur un joueur pour lui chuchoter.</span>';
            }
        } catch (e) {
            container.innerHTML = '<span class="text-danger small">Erreur de chargement.</span>';
        }
    }

    selectWhisperTarget(userId, username) {
        this.whisperTargetId = userId;
        this.whisperTargetName = username;
        document.getElementById('feudalChatWhisperListPanel').classList.add('d-none');
        this.setChannel('whisper', userId, username);
    }

    whisperToUser(userId, username) {
        if (!this.isOpen) {
            this.toggle();
        }
        this.selectWhisperTarget(userId, username);
    }

    async fetchMessages(isBackground = false) {
        const lastId = this.lastIds[this.channel] || 0;
        let url = `/api/chat.php?action=fetch&channel_type=${this.channel}&last_id=${lastId}`;
        if (this.channel === 'alliance') {
            url += `&target_id=${this.allianceId}`;
        } else if (this.channel === 'whisper') {
            if (!this.whisperTargetId) return;
            url += `&other_user_id=${this.whisperTargetId}`;
        }

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success && data.messages) {
                if (data.messages.length > 0) {
                    this.renderMessages(data.messages);
                    this.lastIds[this.channel] = data.last_id;
                    if (!this.isOpen && !isBackground) {
                        this.addUnread(data.messages.length);
                    }
                } else if (lastId === 0 && this.renderedMsgIds.size === 0) {
                    document.getElementById('feudalChatMessages').innerHTML = '<div class="text-center py-4 text-muted small"><span>Aucun message dans ce salon pour le moment. Soyez le premier à proclamer !</span></div>';
                }
            }
        } catch (e) {
            // Ignorer silencieusement lors du polling réseau
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('feudalChatMessages');
        
        // Si c'est le chargement initial, vider le spinner
        if (this.renderedMsgIds.size === 0) {
            container.innerHTML = '';
        }

        let atBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 50);

        messages.forEach(m => {
            if (this.renderedMsgIds.has(m.id)) return;
            this.renderedMsgIds.add(m.id);

            const msgEl = document.createElement('div');
            msgEl.className = `p-2 rounded mb-1 ${m.is_self ? 'bg-orange-lt border-start border-3 border-warning' : 'bg-white border'}`;
            msgEl.style.boxShadow = '0 1px 2px rgba(0,0,0,0.03)';
            msgEl.id = `chatMsg_${m.id}`;

            let roleBadge = '';
            if (m.sender_is_admin) {
                roleBadge = '<span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem;">⭐ Admin</span>';
            } else if (m.sender_is_moderator) {
                roleBadge = '<span class="badge bg-primary text-white ms-1" style="font-size:0.6rem;">🛡️ Modo</span>';
            }

            let clanBadge = m.sender_alliance_tag ? `<span class="badge bg-dark text-white ms-1" style="font-size:0.6rem;">[${this.escapeHtml(m.sender_alliance_tag)}]</span>` : '';

            let delBtn = m.can_delete ? `
                <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1 opacity-75 hover-opacity-100" title="Supprimer ce message" onclick="window.feudalChat.deleteMessage(${m.id})">
                    &times;
                </button>
            ` : '';

            let whisperBtn = !m.is_self ? `
                <button type="button" class="btn btn-link btn-sm text-muted p-0 ms-2" title="Chuchoter en privé" onclick="window.feudalChat.whisperToUser(${m.sender_id}, '${this.escapeHtml(m.sender_username)}')">
                    ✉️
                </button>
            ` : '';

            msgEl.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-1" style="font-size:0.75rem;">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="me-1">${m.sender_faction_icon || '🏯'}</span>
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(${m.sender_id})" class="fw-bold text-decoration-none text-dark hover-underline">
                            ${this.escapeHtml(m.sender_username)}
                        </a>
                        ${clanBadge}
                        ${roleBadge}
                        ${whisperBtn}
                    </div>
                    <div class="d-flex align-items-center text-muted" style="font-size:0.68rem;">
                        <span>${m.time_formatted}</span>
                        ${delBtn}
                    </div>
                </div>
                <div class="chat-msg-body text-break" style="font-size:0.82rem; line-height:1.4;">
                    ${m.message}
                </div>
            `;

            container.appendChild(msgEl);
        });

        if (atBottom || this.isOpen) {
            this.scrollToBottom();
        }
    }

    scrollToBottom() {
        const c = document.getElementById('feudalChatMessages');
        if (c) {
            c.scrollTop = c.scrollHeight;
        }
    }

    async handleSend(e) {
        e.preventDefault();
        const inp = document.getElementById('feudalChatInput');
        const text = inp.value.trim();
        if (!text) return;

        if (this.channel === 'whisper' && !this.whisperTargetId) {
            alert("Veuillez sélectionner un destinataire pour chuchoter.");
            return;
        }

        const btn = document.getElementById('feudalChatSubmitBtn');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('channel_type', this.channel);
        formData.append('message', text);
        if (this.channel === 'alliance') {
            formData.append('target_id', this.allianceId);
        } else if (this.channel === 'whisper') {
            formData.append('recipient_id', this.whisperTargetId);
        }

        try {
            const res = await fetch('/api/chat.php?action=send', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                inp.value = '';
                this.fetchMessages();
            } else {
                alert(data.error || "Erreur lors de l'envoi du message.");
            }
        } catch (err) {
            alert("Erreur de connexion au serveur de chat.");
        } finally {
            btn.disabled = false;
            inp.focus();
        }
    }

    async deleteMessage(msgId) {
        if (!confirm("Retirer définitivement ce message du chat ?")) return;

        const formData = new FormData();
        formData.append('message_id', msgId);

        try {
            const res = await fetch('/api/chat.php?action=delete', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(`chatMsg_${msgId}`);
                if (el) {
                    el.querySelector('.chat-msg-body').innerHTML = '<em>Message retiré par le Shogunat ou son auteur</em>';
                }
            } else {
                alert(data.error || "Impossible de supprimer ce message.");
            }
        } catch (e) {
            alert("Erreur réseau.");
        }
    }

    insertEmoji(emoji) {
        const inp = document.getElementById('feudalChatInput');
        if (inp) {
            inp.value += emoji;
            inp.focus();
        }
    }

    addUnread(count) {
        this.unreadCount += count;
        const b = document.getElementById('feudalChatUnreadBadge');
        if (b) {
            b.innerText = this.unreadCount > 99 ? '99+' : this.unreadCount;
            b.classList.remove('d-none');
        }
    }

    clearUnread() {
        this.unreadCount = 0;
        const b = document.getElementById('feudalChatUnreadBadge');
        if (b) {
            b.classList.add('d-none');
        }
    }

    startPolling() {
        if (this.pollingTimer) clearInterval(this.pollingTimer);
        this.pollingTimer = setInterval(() => {
            this.fetchMessages(true);
        }, 3500);
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    window.feudalChat = new FeudalChatClient();
    window.feudalChat.init();
});
</script>

