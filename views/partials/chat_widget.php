<?php
/**
 * Widget Flottant : Chat Féodal en Direct & File des Discussions (Docked Bottom-Right)
 * Intégré sur toutes les pages de jeu pour échanger en temps réel avec le Shōgunat.
 */
if (($page ?? '') === 'chat') {
    // Si l'utilisateur est déjà sur la page dédiée du chat, ne pas afficher le widget flottant en double
    return;
}

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
         style="width:380px; max-width:calc(100vw - 30px); height:510px; max-height:calc(100vh - 80px); display:flex; flex-direction:column; overflow:hidden;">
        
        <!-- En-tête Chat -->
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between bg-primary text-white">
            <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:240px;">
                <!-- Bouton Retour à la File (visible en mode discussion) -->
                <button type="button" id="feudalChatBackBtn" class="btn btn-sm btn-outline-light p-1 px-2 d-none align-items-center gap-1"
                        onclick="window.feudalChat.showQueueView()" title="Retourner à la file des discussions"
                        style="font-size:0.75rem; border-radius:6px; line-height:1.2;">
                    <span>&larr;</span> <span>File</span>
                </button>

                <span id="feudalChatHeaderIcon" style="font-size:1.15rem;">🏮</span>
                <div class="text-truncate">
                    <h5 id="feudalChatHeaderTitle" class="m-0 fw-bold text-truncate text-white" style="font-size:0.88rem;">
                        Taverne du Shōgunat
                    </h5>
                    <div class="small text-truncate text-white-50" style="font-size:0.68rem;">
                        <span class="status-dot status-dot-animated bg-success d-inline-block me-1" style="width:6px; height:6px;"></span>
                        <span id="feudalChatOnlineCounter">En direct (2.5s)</span>
                    </div>
                </div>
            </div>

            <!-- Actions En-tête -->
            <div class="d-flex align-items-center gap-1">
                <!-- Bouton Bascule File / Discussion -->
                <button type="button" id="feudalChatQueueToggleBtn" class="btn btn-sm btn-icon text-muted"
                        onclick="window.feudalChat.toggleQueueView()" title="File des discussions"
                        style="color:#d6d3d1 !important;">
                    <span style="font-size:0.95rem;">🗂️</span>
                </button>
                <!-- Bouton Son Activé / Coupé -->
                <button type="button" id="feudalChatSoundBtn" class="btn btn-sm btn-icon text-muted"
                        onclick="window.feudalChat.toggleSound()" title="Notifications sonores (activé)"
                        style="color:#d6d3d1 !important;">
                    <span id="feudalChatSoundIcon" style="font-size:0.95rem;">🔔</span>
                </button>
                <!-- Agrandir en Plein Écran -->
                <a href="/?page=chat" class="btn btn-sm btn-icon text-muted" title="Ouvrir le salon en plein écran" style="color:#d6d3d1 !important;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                </a>
                <!-- Fermer / Réduire -->
                <button type="button" class="btn btn-sm btn-icon text-muted" onclick="window.feudalChat && window.feudalChat.toggle()" title="Réduire le chat" style="color:#d6d3d1 !important; font-size:1.2rem; line-height:1;">
                    &times;
                </button>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- VUE 1 : FILE DES DISCUSSIONS (Threads Queue)                     -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <div id="feudalChatQueueView" class="d-none flex-column flex-grow-1 bg-white" style="overflow-y:auto;">
            <!-- Barre de Recherche Rapide -->
            <div class="p-2 border-bottom bg-light">
                <input type="text" id="feudalChatQueueFilter" class="form-control form-control-sm"
                       placeholder="🔍 Filtrer les conversations ou Daimyōs..." oninput="window.feudalChat.filterQueue(this.value)"
                       style="font-size:0.78rem; border-color:#d6d3d1;">
            </div>

            <!-- Liste des Canaux Principaux -->
            <div class="px-2 pt-2 pb-1 text-uppercase text-muted fw-bold" style="font-size:0.65rem; letter-spacing:0.5px;">
                Canaux Principaux
            </div>
            <div class="list-group list-group-flush border-bottom">
                <!-- Canal Général -->
                <a href="javascript:void(0)" onclick="window.feudalChat.openThread('global')"
                   class="list-group-item list-group-item-action py-2 px-3 d-flex align-items-center justify-content-between hover-bg"
                   id="feudalQueueItem_global">
                    <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:260px;">
                        <span style="font-size:1.3rem;">🏯</span>
                        <div class="text-truncate">
                            <div class="d-flex align-items-center gap-1">
                                <span class="fw-bold" style="font-size:0.83rem; color:#1c1917;">Général</span>
                                <span class="badge bg-warning-lt text-warning" style="font-size:0.6rem;">Public</span>
                            </div>
                            <div class="text-muted small text-truncate" id="feudalQueueLastMsg_global" style="font-size:0.72rem;">
                                Chargement du dernier message...
                            </div>
                        </div>
                    </div>
                    <span class="text-muted small" id="feudalQueueTime_global" style="font-size:0.68rem;">--:--</span>
                </a>

                <!-- Canal d'Alliance -->
                <a href="javascript:void(0)" onclick="window.feudalChat.openThread('alliance')"
                   class="list-group-item list-group-item-action py-2 px-3 d-flex align-items-center justify-content-between hover-bg <?= !$hasAlliance ? 'disabled opacity-60' : '' ?>"
                   id="feudalQueueItem_alliance">
                    <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:260px;">
                        <span style="font-size:1.3rem;">🎌</span>
                        <div class="text-truncate">
                            <div class="d-flex align-items-center gap-1">
                                <span class="fw-bold" style="font-size:0.83rem; color:#1c1917;">Clan Féodal</span>
                                <span class="badge <?= $hasAlliance ? 'bg-indigo-lt text-indigo' : 'bg-secondary-lt text-secondary' ?>" style="font-size:0.6rem;">
                                    <?= $hasAlliance ? 'Alliance' : '🔒 Aucun Clan' ?>
                                </span>
                            </div>
                            <div class="text-muted small text-truncate" id="feudalQueueLastMsg_alliance" style="font-size:0.72rem;">
                                <?= $hasAlliance ? 'Chargement...' : 'Rejoignez un clan féodal' ?>
                            </div>
                        </div>
                    </div>
                    <span class="text-muted small" id="feudalQueueTime_alliance" style="font-size:0.68rem;">--:--</span>
                </a>
            </div>

            <!-- Section : Chuchotements Privés -->
            <div class="px-2 pt-2 pb-1 d-flex align-items-center justify-content-between">
                <span class="text-uppercase text-muted fw-bold" style="font-size:0.65rem; letter-spacing:0.5px;">
                    Chuchotements Privés
                </span>
                <span class="badge bg-secondary-lt" id="feudalQueueWhisperCount" style="font-size:0.65rem;">0</span>
            </div>
            <div class="list-group list-group-flush border-bottom" id="feudalQueueWhispersContainer">
                <div class="p-3 text-center text-muted small" style="font-size:0.75rem;">
                    Aucune conversation privée en cours.<br>
                    <span class="text-secondary">Sélectionnez un Daimyō ci-dessous pour lui chuchoter.</span>
                </div>
            </div>

            <!-- Section : Daimyōs Connectés (Accès Rapide) -->
            <div class="px-2 pt-2 pb-1 d-flex align-items-center justify-content-between">
                <span class="text-uppercase text-muted fw-bold" style="font-size:0.65rem; letter-spacing:0.5px;">
                    🟢 Daimyōs en Ligne
                </span>
                <span class="badge bg-success-lt" id="feudalQueueOnlineCount" style="font-size:0.65rem;">0</span>
            </div>
            <div class="list-group list-group-flush" id="feudalQueueOnlineContainer" style="max-height:160px; overflow-y:auto;">
                <div class="p-2 text-center text-muted small" style="font-size:0.75rem;">
                    Recherche des seigneurs connectés...
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- VUE 2 : CONVERSATION ACTIVE (Thread View)                         -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <div id="feudalChatThreadView" class="d-flex flex-column flex-grow-1" style="overflow:hidden;">
            <!-- Onglets Canaux Rapides -->
            <div class="px-2 pt-2 bg-light border-bottom">
                <ul class="nav nav-tabs nav-fill card-header-tabs" id="feudalChatTabs" style="margin:0; border-bottom:none;">
                    <li class="nav-item">
                        <button class="nav-link active py-1 px-2 small font-weight-bold" id="chatTabGlobal" onclick="window.feudalChat.setChannel('global')">
                            <span>🏯 Général</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-2 small font-weight-bold <?= !$hasAlliance ? 'text-muted' : '' ?>" id="chatTabAlliance" onclick="window.feudalChat.setChannel('alliance')" title="<?= $hasAlliance ? 'Canal de votre Clan' : 'Rejoignez un clan féodal pour accéder à ce canal' ?>">
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
                <div class="d-flex align-items-center gap-1 text-truncate">
                    <span>Chuchotement à :</span>
                    <strong id="feudalWhisperTargetName" class="text-primary text-truncate">Daimyō</strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="window.feudalChat.showQueueView()" style="font-size:0.72rem;">Changer</button>
                </div>
            </div>

            <!-- Fil des Messages Déroulant -->
            <div id="feudalChatMessages" class="flex-grow-1 p-2"
                 style="overflow-y:auto; background:#fafaf9; font-size:0.82rem; display:flex; flex-direction:column; gap:0.45rem;">
                <div class="text-center py-4 text-muted small">
                    <span>🏮 Connexion au salon féodal...</span>
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
                <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🔥')">🔥</button>
                <button type="button" class="btn btn-sm btn-ghost-secondary p-0 px-1" onclick="window.feudalChat.insertEmoji('🛡️')">🛡️</button>
            </div>

            <!-- Barre de Saisie & Envoi -->
            <div class="card-footer p-2 bg-white border-top">
                <form id="feudalChatForm" onsubmit="window.feudalChat.handleSend(event)" class="d-flex gap-1 m-0">
                    <input type="text" id="feudalChatInput" class="form-control form-control-sm"
                           placeholder="Votre message au Shōgunat..." maxlength="1000" autocomplete="off"
                           style="font-size:0.82rem; border-color:#d6d3d1;">
                    <button type="submit" id="feudalChatSubmitBtn" class="btn btn-primary btn-sm px-3">
                        Envoyer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── SCRIPT DU CHAT FÉODAL FLOTTANT AVEC FILE DES DISCUSSIONS ── -->
<script>
class FeudalChatClient {
    constructor() {
        this.isOpen = false;
        this.currentView = 'thread'; // 'thread' ou 'queue'
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
        this.isInitialFetch = true;
        this.lastReadId = parseInt(localStorage.getItem('feudal_chat_last_read_id') || '0', 10);
        this.soundEnabled = localStorage.getItem('feudal_chat_sound') !== '0';
        this.threadsData = null;
        this.originalDocumentTitle = document.title;
        this.unreadTitleActive = false;

        // Écouter la touche Echap pour fermer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.toggle();
            }
        });

        // Restaurer le titre quand l'utilisateur revient sur la page
        window.addEventListener('focus', () => {
            if (this.unreadTitleActive) {
                document.title = this.originalDocumentTitle;
                this.unreadTitleActive = false;
            }
        });
    }

    init() {
        this.updateSoundBtn();
        // Premier chargement
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
            this.markAsRead();
            if (this.currentView === 'thread') {
                this.scrollToBottom();
                const inp = document.getElementById('feudalChatInput');
                if (inp) inp.focus();
            }
        } else {
            win.classList.add('d-none');
            btn.classList.remove('d-none');
        }
    }

    showQueueView() {
        this.currentView = 'queue';
        document.getElementById('feudalChatThreadView').classList.add('d-none');
        document.getElementById('feudalChatQueueView').classList.remove('d-none');
        document.getElementById('feudalChatQueueView').classList.add('d-flex');

        // Mettre à jour l'en-tête
        document.getElementById('feudalChatBackBtn').classList.add('d-none');
        document.getElementById('feudalChatBackBtn').classList.remove('d-inline-flex');
        document.getElementById('feudalChatHeaderIcon').innerText = '🗂️';
        document.getElementById('feudalChatHeaderTitle').innerText = 'File des Discussions';

        // Re-rendre la file si données dispo
        if (this.threadsData) {
            this.renderQueueView(this.threadsData);
        }
    }

    showThreadView() {
        this.currentView = 'thread';
        document.getElementById('feudalChatQueueView').classList.add('d-none');
        document.getElementById('feudalChatQueueView').classList.remove('d-flex');
        document.getElementById('feudalChatThreadView').classList.remove('d-none');

        // En-tête
        document.getElementById('feudalChatBackBtn').classList.remove('d-none');
        document.getElementById('feudalChatBackBtn').classList.add('d-inline-flex');

        this.updateThreadHeader();
        this.scrollToBottom();
        const inp = document.getElementById('feudalChatInput');
        if (inp) inp.focus();
        if (this.isOpen) {
            this.markAsRead();
        }
    }

    toggleQueueView() {
        if (this.currentView === 'queue') {
            this.showThreadView();
        } else {
            this.showQueueView();
        }
    }

    updateThreadHeader() {
        const icon = document.getElementById('feudalChatHeaderIcon');
        const title = document.getElementById('feudalChatHeaderTitle');

        if (this.channel === 'global') {
            icon.innerText = '🏯';
            title.innerText = 'Canal Général';
        } else if (this.channel === 'alliance') {
            icon.innerText = '🎌';
            title.innerText = 'Canal du Clan';
        } else if (this.channel === 'whisper') {
            icon.innerText = '✉️';
            title.innerText = `Chuchotement: ${this.whisperTargetName || 'Daimyō'}`;
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
        if (ch === 'whisper') {
            wBar.classList.remove('d-none');
            wBar.classList.add('d-flex');
            document.getElementById('feudalWhisperTargetName').innerText = this.whisperTargetName || 'Sélectionner un Daimyō';
            if (!this.whisperTargetId) {
                this.showQueueView();
                return;
            }
        } else {
            wBar.classList.add('d-none');
            wBar.classList.remove('d-flex');
        }

        // Placeholder input
        const inp = document.getElementById('feudalChatInput');
        if (inp) {
            if (ch === 'global') inp.placeholder = "Message public à tout le Shōgunat...";
            else if (ch === 'alliance') inp.placeholder = "Message secret aux membres du clan...";
            else if (ch === 'whisper') inp.placeholder = `Chuchoter à ${this.whisperTargetName || 'un Daimyō'}...`;
        }

        // Si on était dans la file, basculer vers la vue thread
        if (this.currentView === 'queue') {
            this.showThreadView();
        } else {
            this.updateThreadHeader();
        }

        // Réinitialiser les messages pour recharger ce canal
        this.renderedMsgIds.clear();
        document.getElementById('feudalChatMessages').innerHTML = '<div class="text-center py-4 text-muted small"><span>Chargement du salon...</span></div>';
        this.lastIds[this.channel] = 0;
        this.fetchMessages();
    }

    openThread(channel, targetId = null, targetName = '') {
        this.setChannel(channel, targetId, targetName);
        this.showThreadView();
        if (this.isOpen) {
            this.markAsRead();
        }
    }

    whisperToUser(userId, username) {
        if (!this.isOpen) {
            this.toggle();
        }
        this.openThread('whisper', userId, username);
    }

    async fetchMessages(isBackground = false) {
        const lastId = this.lastIds[this.channel] || 0;
        let url = `/api/chat.php?action=fetch&channel_type=${this.channel}&last_id=${lastId}&limit=60`;
        if (this.channel === 'alliance') {
            url += `&target_id=${this.allianceId}`;
        } else if (this.channel === 'whisper') {
            if (!this.whisperTargetId) {
                // Si aucun contact sélectionné en whisper, interroger simplement threads
                url = `/api/chat.php?action=threads`;
            } else {
                url += `&other_user_id=${this.whisperTargetId}`;
            }
        }

        try {
            const res = await fetch(url);
            const textResp = await res.text();
            let data;
            try {
                data = JSON.parse(textResp);
            } catch (err) {
                return; // Silence lors des micro-déconnexions
            }

            if (data.success) {
                // 1. Mettre à jour les messages de la conversation active
                if (data.messages && data.messages.length > 0) {
                    let hasNewIncomingFromOthers = false;
                    let newIncomingCount = 0;
                    let maxBatchId = 0;

                    data.messages.forEach(m => {
                        if (m.id > maxBatchId) maxBatchId = m.id;
                        // Message considéré nouveau uniquement si id > lastReadId et non envoyé par soi-même
                        if (!m.is_self && m.id > this.lastReadId) {
                            hasNewIncomingFromOthers = true;
                            newIncomingCount++;
                        }
                    });

                    this.renderMessages(data.messages);
                    this.lastIds[this.channel] = Math.max(this.lastIds[this.channel] || 0, data.last_id);

                    if (this.isOpen) {
                        // Le chat est ouvert : la lecture est immédiate
                        this.markAsRead(maxBatchId);
                        // Sonner uniquement si c'est un nouveau message reçu pendant qu'on consulte le chat (pas au 1er chargement)
                        if (!this.isInitialFetch && hasNewIncomingFromOthers) {
                            this.playChime();
                        }
                    } else {
                        // Le chat est fermé
                        if (this.isInitialFetch) {
                            // Au tout premier chargement de la page :
                            // Si le joueur n'a jamais ouvert le chat (0), on initialise sur l'actuel pour éviter un faux affichage de 60 non lus
                            if (this.lastReadId === 0 && maxBatchId > 0) {
                                this.lastReadId = maxBatchId;
                                localStorage.setItem('feudal_chat_last_read_id', String(this.lastReadId));
                            } else if (newIncomingCount > 0) {
                                this.addUnread(newIncomingCount);
                            }
                            // Pas de carillon sonore au chargement initial
                        } else {
                            // En cours de session via polling
                            if (hasNewIncomingFromOthers) {
                                this.playChime();
                                this.addUnread(newIncomingCount);
                                if (document.hidden) {
                                    this.notifyDocumentTitle();
                                }
                            }
                        }
                    }
                } else if (lastId === 0 && this.renderedMsgIds.size === 0 && this.channel !== 'whisper') {
                    const c = document.getElementById('feudalChatMessages');
                    if (c) c.innerHTML = '<div class="text-center py-4 text-muted small"><span>Aucun message récent dans ce salon. Soyez le premier à proclamer !</span></div>';
                }

                this.isInitialFetch = false;

                // 2. Mettre à jour la file des discussions (threads)
                if (data.threads) {
                    this.threadsData = data.threads;
                    this.renderQueueView(data.threads);
                }
            }
        } catch (e) {
            // Ignorer silencieusement lors du polling réseau
        }
    }

    renderQueueView(threads) {
        if (!threads) return;

        // Canal Général
        if (threads.global) {
            const gMsg = document.getElementById('feudalQueueLastMsg_global');
            const gTime = document.getElementById('feudalQueueTime_global');
            if (gMsg) {
                gMsg.innerText = threads.global.last_sender 
                    ? `${threads.global.last_sender}: ${threads.global.last_message}` 
                    : threads.global.last_message;
            }
            if (gTime) gTime.innerText = threads.global.last_time || '--:--';
        }

        // Canal Alliance
        if (threads.alliance) {
            const aMsg = document.getElementById('feudalQueueLastMsg_alliance');
            const aTime = document.getElementById('feudalQueueTime_alliance');
            if (aMsg) {
                aMsg.innerText = threads.alliance.last_sender 
                    ? `${threads.alliance.last_sender}: ${threads.alliance.last_message}` 
                    : threads.alliance.last_message;
            }
            if (aTime) aTime.innerText = threads.alliance.last_time || '--:--';
        }

        // Chuchotements Privés
        const whispersContainer = document.getElementById('feudalQueueWhispersContainer');
        const whisperCountBadge = document.getElementById('feudalQueueWhisperCount');
        if (whispersContainer && threads.whispers) {
            whisperCountBadge.innerText = threads.whispers.length;
            if (threads.whispers.length === 0) {
                whispersContainer.innerHTML = `
                    <div class="p-3 text-center text-muted small" style="font-size:0.75rem;">
                        Aucune conversation privée en cours.<br>
                        <span class="text-secondary">Cliquez sur un Daimyō ci-dessous pour initier un chuchotement.</span>
                    </div>
                `;
            } else {
                whispersContainer.innerHTML = threads.whispers.map(w => {
                    const isCurrent = (this.channel === 'whisper' && this.whisperTargetId === w.user_id);
                    return `
                        <a href="javascript:void(0)" onclick="window.feudalChat.openThread('whisper', ${w.user_id}, '${this.escapeHtml(w.username)}')"
                           class="list-group-item list-group-item-action py-2 px-3 d-flex align-items-center justify-content-between hover-bg ${isCurrent ? 'active' : ''}">
                            <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:260px;">
                                <span style="font-size:0.8rem;">${w.is_online ? '🟢' : '⚪'}</span>
                                <div class="text-truncate">
                                    <div class="d-flex align-items-center gap-1">
                                        <span style="font-size:0.85rem;">${w.faction_icon || '🏯'}</span>
                                        <span class="fw-bold" style="font-size:0.82rem;">${this.escapeHtml(w.username)}</span>
                                        ${w.alliance_tag ? `<span class="badge bg-dark text-white" style="font-size:0.58rem;">[${this.escapeHtml(w.alliance_tag)}]</span>` : ''}
                                    </div>
                                    <div class="text-muted small text-truncate" style="font-size:0.72rem;">
                                        ${w.last_message_is_self ? '<span class="text-muted">Vous : </span>' : ''}${this.escapeHtml(w.last_message)}
                                    </div>
                                </div>
                            </div>
                            <span class="text-muted small" style="font-size:0.68rem;">${w.last_message_time || ''}</span>
                        </a>
                    `;
                }).join('');
            }
        }

        // Joueurs Connectés
        const onlineContainer = document.getElementById('feudalQueueOnlineContainer');
        const onlineCountBadge = document.getElementById('feudalQueueOnlineCount');
        if (onlineContainer && threads.online_users) {
            onlineCountBadge.innerText = threads.online_users.length;
            onlineContainer.innerHTML = threads.online_users.map(u => {
                const isMe = (u.id === this.currentUserId);
                return `
                    <div class="list-group-item py-1 px-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-1 text-truncate">
                            <span>${u.faction_icon || '🏯'}</span>
                            <a href="javascript:void(0)" onclick="openPlayerProfileModal(${u.id})" class="fw-bold text-dark text-decoration-none hover-underline" style="font-size:0.8rem;">
                                ${this.escapeHtml(u.username)}
                            </a>
                            ${u.alliance_tag ? `<span class="badge bg-secondary-lt" style="font-size:0.58rem;">[${this.escapeHtml(u.alliance_tag)}]</span>` : ''}
                            ${u.is_admin ? '<span class="badge bg-warning text-dark" style="font-size:0.55rem;">⭐ Admin</span>' : ''}
                            ${u.is_moderator ? '<span class="badge bg-primary text-white" style="font-size:0.55rem;">🛡️ Modo</span>' : ''}
                        </div>
                        ${!isMe ? `
                            <button type="button" class="btn btn-sm btn-ghost-primary p-0 px-2" title="Chuchoter en direct"
                                    onclick="window.feudalChat.openThread('whisper', ${u.id}, '${this.escapeHtml(u.username)}')">
                                ✉️
                            </button>
                        ` : '<span class="badge bg-light text-muted" style="font-size:0.6rem;">Vous</span>'}
                    </div>
                `;
            }).join('');
        }
    }

    filterQueue(query) {
        const q = (query || '').toLowerCase().trim();
        const items = document.querySelectorAll('#feudalChatQueueView .list-group-item');
        items.forEach(el => {
            const txt = el.innerText.toLowerCase();
            if (!q || txt.includes(q)) {
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
    }

    renderMessages(messages) {
        const container = document.getElementById('feudalChatMessages');
        if (!container) return;

        // Premier chargement : vider le placeholder
        if (this.renderedMsgIds.size === 0) {
            container.innerHTML = '';
        }

        const isNearBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 60);

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
                <button type="button" class="btn btn-link btn-sm text-muted p-0 ms-1" title="Chuchoter en privé" onclick="window.feudalChat.whisperToUser(${m.sender_id}, '${this.escapeHtml(m.sender_username)}')">
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

        if (isNearBottom || this.isOpen) {
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
            alert("Veuillez sélectionner un Daimyō pour lui chuchoter.");
            this.showQueueView();
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
            const textResp = await res.text();
            let data;
            try {
                data = JSON.parse(textResp);
            } catch (jsonErr) {
                alert("Erreur serveur : " + textResp.substring(0, 300));
                return;
            }

            if (data.success) {
                inp.value = '';
                this.fetchMessages();
            } else {
                alert(data.error || "Erreur lors de l'envoi du message.");
            }
        } catch (err) {
            alert("Erreur de connexion : " + (err.message || "Impossible de joindre le serveur."));
        } finally {
            btn.disabled = false;
            inp.focus();
        }
    }

    async deleteMessage(msgId) {
        if (!confirm("Retirer définitivement ce message du salon féodal ?")) return;

        const formData = new FormData();
        formData.append('message_id', msgId);

        try {
            const res = await fetch('/api/chat.php?action=delete', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(`chatMsg_${msgId}`);
                if (el) {
                    el.querySelector('.chat-msg-body').innerHTML = '<em class="text-muted">Message retiré par le Shōgunat ou son auteur</em>';
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

    playChime() {
        if (!this.soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // Ré5
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08); // La5

            gain.gain.setValueAtTime(0.09, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.32);

            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.32);
        } catch(e) {}
    }

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('feudal_chat_sound', this.soundEnabled ? '1' : '0');
        this.updateSoundBtn();
    }

    updateSoundBtn() {
        const icon = document.getElementById('feudalChatSoundIcon');
        const btn = document.getElementById('feudalChatSoundBtn');
        if (icon && btn) {
            if (this.soundEnabled) {
                icon.innerText = '🔔';
                btn.title = "Notifications sonores (activées - cliquer pour couper)";
            } else {
                icon.innerText = '🔕';
                btn.title = "Notifications sonores (coupées - cliquer pour réactiver)";
            }
        }
    }

    notifyDocumentTitle() {
        this.unreadTitleActive = true;
        document.title = `(🔔 Nouveau message) ${this.originalDocumentTitle}`;
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

    markAsRead(hintMaxId = 0) {
        let maxId = Math.max(this.lastReadId, hintMaxId);
        this.renderedMsgIds.forEach(id => {
            if (id > maxId) maxId = id;
        });
        if (this.lastIds && this.lastIds[this.channel] > maxId) {
            maxId = this.lastIds[this.channel];
        }
        if (maxId > this.lastReadId) {
            this.lastReadId = maxId;
            localStorage.setItem('feudal_chat_last_read_id', String(this.lastReadId));
        }
        this.clearUnread();
        if (this.unreadTitleActive) {
            document.title = this.originalDocumentTitle;
            this.unreadTitleActive = false;
        }
    }

    startPolling() {
        if (this.pollingTimer) clearInterval(this.pollingTimer);
        // Rafraîchissement automatique haute fréquence (2.5s)
        this.pollingTimer = setInterval(() => {
            this.fetchMessages(true);
        }, 2500);
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
