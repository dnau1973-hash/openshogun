<?php
/**
 * Vue Dédiée : Salon de Discussion, File des Discussions & Chat Féodal Plein Écran
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

// Marquer les chuchotements reçus comme lus
if (!empty($chatUser['id'])) {
    try {
        $db = Database::getConnection();
        $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE recipient_id = ? AND is_read = 0")
           ->execute([(int)$chatUser['id']]);
    } catch (Throwable $e) {}
}
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
                    Échangez en temps réel avec les seigneurs du royaume, préparez vos offensives avec vos frères d'armes ou négociez des pactes secrets en tête-à-tête.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <!-- Bouton Son -->
                    <button type="button" id="fullChatSoundToggleBtn" class="btn btn-outline-secondary d-flex align-items-center gap-2" onclick="toggleFullChatSound()">
                        <span id="fullChatSoundIcon">🔔</span>
                        <span id="fullChatSoundText">Son Activé</span>
                    </button>

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
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE GAUCHE : FILE DES DISCUSSIONS (Threads Queue en direct)   -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-4 col-xl-3">
            <!-- Recherche rapide dans la file -->
            <div class="card mb-2 shadow-sm border-0">
                <div class="card-body p-2">
                    <input type="text" id="fullQueueFilterInput" class="form-control form-control-sm"
                           placeholder="🔍 Filtrer les conversations..." oninput="filterFullQueue(this.value)"
                           style="font-size:0.8rem; border-color:#d6d3d1;">
                </div>
            </div>

            <!-- Canaux Principaux -->
            <div class="card mb-3 shadow-sm border-0" style="border-top:3px solid #b45309 !important;">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                        <span>🗂️</span>
                        <span>File des Canaux</span>
                    </h4>
                    <span class="status-dot status-dot-animated bg-success" title="Rafraîchissement automatique actif (2.5s)"></span>
                </div>
                <div class="list-group list-group-flush" id="chatFullChannelsList">
                    <!-- Canal Général -->
                    <a href="javascript:void(0)" onclick="setFullChatChannel('global')" id="fullChannelBtn_global"
                       class="list-group-item list-group-item-action py-2 d-flex align-items-center justify-content-between active">
                        <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:210px;">
                            <span style="font-size:1.4rem;">🏯</span>
                            <div class="text-truncate">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="font-weight-bold" style="font-size:0.84rem;">Général</span>
                                    <span class="badge bg-warning-lt text-warning" style="font-size:0.58rem;">Public</span>
                                </div>
                                <div class="text-muted small text-truncate" id="fullQueueLastMsg_global" style="font-size:0.7rem;">
                                    Chargement du dernier message...
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small" id="fullQueueTime_global" style="font-size:0.68rem;">--:--</span>
                    </a>

                    <!-- Canal Clan Féodal -->
                    <a href="javascript:void(0)" onclick="setFullChatChannel('alliance')" id="fullChannelBtn_alliance"
                       class="list-group-item list-group-item-action py-2 d-flex align-items-center justify-content-between <?= !$hasAlliance ? 'disabled opacity-50' : '' ?>">
                        <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:210px;">
                            <span style="font-size:1.4rem;">🎌</span>
                            <div class="text-truncate">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="font-weight-bold" style="font-size:0.84rem;">Clan Féodal</span>
                                    <span class="badge <?= $hasAlliance ? 'bg-indigo-lt text-indigo' : 'bg-secondary-lt text-secondary' ?>" style="font-size:0.58rem;">
                                        <?= $hasAlliance ? htmlspecialchars($allianceTag ? "[$allianceTag]" : 'Clan') : '🔒 Aucun Clan' ?>
                                    </span>
                                </div>
                                <div class="text-muted small text-truncate" id="fullQueueLastMsg_alliance" style="font-size:0.7rem;">
                                    <?= $hasAlliance ? 'Chargement...' : 'Rejoignez un clan' ?>
                                </div>
                            </div>
                        </div>
                        <span class="text-muted small" id="fullQueueTime_alliance" style="font-size:0.68rem;">--:--</span>
                    </a>
                </div>
            </div>

            <!-- Conversations Privées (Chuchotements en Direct) -->
            <div class="card mb-3 shadow-sm border-0" style="border-top:3px solid #6366f1 !important;">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h4 class="card-title m-0" style="font-size:0.9rem;">✉️ Chuchotements Privés</h4>
                    <span class="badge bg-indigo-lt" id="fullWhisperCountBadge"><?= count($conversations) ?></span>
                </div>
                <div class="list-group list-group-flush" id="chatFullWhispersList" style="max-height:260px; overflow-y:auto;">
                    <?php if (empty($conversations)): ?>
                        <div class="p-3 text-muted text-center small" id="fullWhispersEmptyState">
                            Aucune conversation privée en cours.<br>
                            <span class="text-secondary">Cliquez sur un Daimyō ci-dessous pour initier un chuchotement.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): ?>
                            <a href="javascript:void(0)" onclick="setFullChatWhisper(<?= (int)$c['user_id'] ?>, '<?= htmlspecialchars(addslashes($c['username'])) ?>')"
                               id="fullWhisperBtn_<?= (int)$c['user_id'] ?>"
                               class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:210px;">
                                    <span style="font-size:0.8rem;"><?= $c['is_online'] ? '🟢' : '⚪' ?></span>
                                    <div class="text-truncate">
                                        <div class="font-weight-bold text-truncate" style="font-size:0.82rem;">
                                            <span><?= $c['faction_icon'] ?? '🏯' ?></span>
                                            <?= htmlspecialchars($c['username']) ?>
                                            <?php if ($c['alliance_tag']): ?>
                                                <span class="badge bg-dark text-white ms-1" style="font-size:0.58rem;">[<?= htmlspecialchars($c['alliance_tag']) ?>]</span>
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

            <!-- Daimyōs Connectés (Accès Rapide) -->
            <div class="card shadow-sm border-0" style="border-top:3px solid #16a34a !important;">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h4 class="card-title m-0 d-flex align-items-center gap-2" style="font-size:0.9rem;">
                        <span class="status-dot status-dot-animated bg-success" style="width:7px; height:7px;"></span>
                        <span>Daimyōs en Ligne</span>
                    </h4>
                    <span class="badge bg-success-lt" id="fullOnlineCountBadge"><?= count($onlineUsers) ?></span>
                </div>
                <div class="list-group list-group-flush" id="chatFullOnlineList" style="max-height:220px; overflow-y:auto;">
                    <?php foreach ($onlineUsers as $ou): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between py-1 px-3">
                            <div class="d-flex align-items-center gap-1 text-truncate" style="max-width:210px;">
                                <span><?= $ou['faction_icon'] ?></span>
                                <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= (int)$ou['id'] ?>)"
                                   class="font-weight-bold text-dark text-decoration-none hover-underline text-truncate" style="font-size:0.8rem;">
                                    <?= htmlspecialchars($ou['username']) ?>
                                </a>
                                <?php if ($ou['alliance_tag']): ?>
                                    <span class="badge bg-secondary-lt ms-1" style="font-size:0.58rem;">[<?= htmlspecialchars($ou['alliance_tag']) ?>]</span>
                                <?php endif; ?>
                                <?php if ($ou['is_admin']): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:0.55rem;">⭐ Admin</span>
                                <?php elseif ($ou['is_moderator']): ?>
                                    <span class="badge bg-primary text-white ms-1" style="font-size:0.55rem;">🛡️ Modo</span>
                                <?php endif; ?>
                            </div>
                            <?php if ((int)$ou['id'] !== (int)$chatUser['id']): ?>
                                <button type="button" class="btn btn-sm btn-ghost-primary p-0 px-2"
                                        title="Chuchoter à <?= htmlspecialchars($ou['username']) ?>"
                                        onclick="setFullChatWhisper(<?= (int)$ou['id'] ?>, '<?= htmlspecialchars(addslashes($ou['username'])) ?>')">
                                    ✉️
                                </button>
                            <?php else: ?>
                                <span class="badge bg-light text-muted" style="font-size:0.6rem;">Vous</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- COLONNE DROITE : FIL DE DISCUSSION & ENVOI                        -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-8 col-xl-9">
            <div class="card shadow-sm border-0 h-100 d-flex flex-column" style="min-height:680px;">
                <!-- En-tête du Fil de Discussion -->
                <div class="card-header py-3 d-flex align-items-center justify-content-between bg-primary text-white">
                    <div class="d-flex align-items-center gap-3">
                        <span id="fullChatChannelIcon" style="font-size:1.8rem;">🏯</span>
                        <div>
                            <h3 class="m-0 fw-bold text-white" id="fullChatChannelTitle" style="font-size:1.15rem;">
                                Canal Général du Shōgunat
                            </h3>
                            <div class="small mt-1 text-white-50" id="fullChatChannelSubtitle" style="font-size:0.78rem;">
                                Salon public ouvert à tous les Daimyōs de l'archipel &bull; Rafraîchissement automatique toutes les 2.5s
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-dot status-dot-animated bg-success me-1"></span>
                        <span class="small d-none d-sm-inline text-white-50" style="font-size:0.75rem;">En direct</span>
                        <button type="button" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1 ms-2"
                                onclick="fetchFullMessages()" title="Actualiser instantanément">
                            <span>🔄</span> Rafraîchir
                        </button>
                    </div>
                </div>

                <!-- Fil des Messages -->
                <div id="fullChatMessagesContainer" class="card-body p-3 flex-grow-1"
                     style="overflow-y:auto; max-height:520px; display:flex; flex-direction:column; gap:0.65rem;">
                    <div class="text-center py-5 text-muted">
                        <span>🏮 Connexion au salon féodal...</span>
                    </div>
                </div>

                <!-- Barre d'Emojis Féodaux Rapides -->
                <div class="px-3 py-1 bg-light border-top d-flex gap-2 align-items-center overflow-x-auto" style="scrollbar-width:none;">
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
                                  placeholder="Rédigez votre proclamation aux Daimyōs... (Appuyez sur Entrée pour envoyer, Maj+Entrée pour un saut de ligne)"
                                  style="font-size:0.88rem; resize:none;"></textarea>
                        <button type="submit" id="fullChatSubmitBtn" class="btn btn-primary px-4 d-flex flex-column align-items-center justify-content-center"
                                style="font-weight:700; min-width:115px;">
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
let fullSoundEnabled = localStorage.getItem('feudal_chat_sound') !== '0';
const originalFullTitle = document.title;
let fullUnreadTitleActive = false;

window.addEventListener('focus', () => {
    if (fullUnreadTitleActive) {
        document.title = originalFullTitle;
        fullUnreadTitleActive = false;
    }
});

function toggleFullChatSound() {
    fullSoundEnabled = !fullSoundEnabled;
    localStorage.setItem('feudal_chat_sound', fullSoundEnabled ? '1' : '0');
    updateFullSoundBtnUI();
}

function updateFullSoundBtnUI() {
    const icon = document.getElementById('fullChatSoundIcon');
    const text = document.getElementById('fullChatSoundText');
    if (icon && text) {
        if (fullSoundEnabled) {
            icon.innerText = '🔔';
            text.innerText = 'Son Activé';
        } else {
            icon.innerText = '🔕';
            text.innerText = 'Son Coupé';
        }
    }
}

function playFullChatChime() {
    if (!fullSoundEnabled) return;
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

function setFullChatChannel(ch) {
    if (ch === 'alliance' && !fullHasAlliance) {
        alert("Vous devez appartenir à une alliance féodale pour accéder à ce canal.");
        return;
    }

    fullChannel = ch;
    fullWhisperTargetId = null;
    fullWhisperTargetName = '';

    // Style actif des boutons de la file
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
        sub.innerText = 'Salon public ouvert à tous les Daimyōs de l\'archipel &bull; Rafraîchissement automatique toutes les 2.5s';
        inp.placeholder = 'Rédigez votre proclamation à l\'ensemble du Shōgunat...';
    } else if (ch === 'alliance') {
        icon.innerText = '🎌';
        title.innerText = 'Canal du Clan Féodal';
        sub.innerText = 'Salon secret réservé exclusivement aux membres de votre clan &bull; Rafraîchissement automatique toutes les 2.5s';
        inp.placeholder = 'Rédigez votre message secret à vos frères d\'armes...';
    }

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
    document.getElementById('fullChatChannelSubtitle').innerText = `Conversation privée confidentielle en tête-à-tête &bull; Rafraîchissement automatique toutes les 2.5s`;
    document.getElementById('fullChatInput').placeholder = `Chuchoter un message privé à ${targetName}...`;

    resetAndLoadFullChat();
}

function resetAndLoadFullChat() {
    fullLastId = 0;
    fullRenderedIds.clear();
    document.getElementById('fullChatMessagesContainer').innerHTML = '<div class="text-center py-5 text-muted"><span>Chargement des échanges féodaux...</span></div>';
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
        const textResp = await res.text();
        let data;
        try {
            data = JSON.parse(textResp);
        } catch (jsonErr) {
            return;
        }

        if (data.success) {
            // 1. Mettre à jour le fil de messages
            if (data.messages && data.messages.length > 0) {
                const wasFirstFetch = (fullLastId === 0);
                let hasIncomingOthers = false;
                let maxBatchId = fullLastId;

                data.messages.forEach(m => {
                    if (m.id > maxBatchId) maxBatchId = m.id;
                    if (!m.is_self) hasIncomingOthers = true;
                });

                renderFullMessages(data.messages);
                if (data.last_id > fullLastId) {
                    fullLastId = data.last_id;
                }

                // Sur la page de chat dédiée, la lecture est effective en temps réel
                try {
                    const currentRead = parseInt(localStorage.getItem('feudal_chat_last_read_id') || '0', 10);
                    if (maxBatchId > currentRead) {
                        localStorage.setItem('feudal_chat_last_read_id', String(maxBatchId));
                    }
                } catch (e) {}

                // Ne sonner que lors des messages arrivant en direct (pas au chargement initial de l'historique)
                if (!wasFirstFetch && hasIncomingOthers) {
                    playFullChatChime();
                    if (document.hidden) {
                        fullUnreadTitleActive = true;
                        document.title = `(🔔 Nouveau message) ${originalFullTitle}`;
                    }
                }
            } else if (fullLastId === 0 && fullRenderedIds.size === 0) {
                document.getElementById('fullChatMessagesContainer').innerHTML = '<div class="text-center py-5 text-muted"><span>Aucun message dans ce salon pour le moment. Soyez le premier à proclamer !</span></div>';
            }

            // 2. Mettre à jour la file des discussions en direct (threads)
            if (data.threads) {
                updateFullThreadsQueue(data.threads);
            }
        }
    } catch (e) {
        // Ignorer lors du polling
    }
}

function updateFullThreadsQueue(threads) {
    if (!threads) return;

    // Général
    if (threads.global) {
        const gMsg = document.getElementById('fullQueueLastMsg_global');
        const gTime = document.getElementById('fullQueueTime_global');
        if (gMsg) {
            gMsg.innerText = threads.global.last_sender 
                ? `${threads.global.last_sender}: ${threads.global.last_message}` 
                : threads.global.last_message;
        }
        if (gTime) gTime.innerText = threads.global.last_time || '--:--';
    }

    // Alliance
    if (threads.alliance) {
        const aMsg = document.getElementById('fullQueueLastMsg_alliance');
        const aTime = document.getElementById('fullQueueTime_alliance');
        if (aMsg) {
            aMsg.innerText = threads.alliance.last_sender 
                ? `${threads.alliance.last_sender}: ${threads.alliance.last_message}` 
                : threads.alliance.last_message;
        }
        if (aTime) aTime.innerText = threads.alliance.last_time || '--:--';
    }

    // Chuchotements
    const wContainer = document.getElementById('chatFullWhispersList');
    const wBadge = document.getElementById('fullWhisperCountBadge');
    if (wContainer && threads.whispers) {
        wBadge.innerText = threads.whispers.length;
        if (threads.whispers.length > 0) {
            wContainer.innerHTML = threads.whispers.map(w => {
                const isActive = (fullChannel === 'whisper' && fullWhisperTargetId === w.user_id);
                return `
                    <a href="javascript:void(0)" onclick="setFullChatWhisper(${w.user_id}, '${escapeFullHtml(w.username)}')"
                       id="fullWhisperBtn_${w.user_id}"
                       class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2 ${isActive ? 'active' : ''}">
                        <div class="d-flex align-items-center gap-2 text-truncate" style="max-width:210px;">
                            <span style="font-size:0.8rem;">${w.is_online ? '🟢' : '⚪'}</span>
                            <div class="text-truncate">
                                <div class="font-weight-bold text-truncate" style="font-size:0.82rem;">
                                    <span>${w.faction_icon || '🏯'}</span>
                                    ${escapeFullHtml(w.username)}
                                    ${w.alliance_tag ? `<span class="badge bg-dark text-white ms-1" style="font-size:0.58rem;">[${escapeFullHtml(w.alliance_tag)}]</span>` : ''}
                                </div>
                                <div class="text-muted small text-truncate" style="font-size:0.7rem;">
                                    ${w.last_message_is_self ? '<span class="text-muted">Vous : </span>' : ''}${escapeFullHtml(w.last_message)}
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
    const oContainer = document.getElementById('chatFullOnlineList');
    const oBadge = document.getElementById('fullOnlineCountBadge');
    if (oContainer && threads.online_users) {
        oBadge.innerText = threads.online_users.length;
        oContainer.innerHTML = threads.online_users.map(ou => {
            const isMe = (ou.id === fullCurrentUserId);
            return `
                <div class="list-group-item d-flex align-items-center justify-content-between py-1 px-3">
                    <div class="d-flex align-items-center gap-1 text-truncate" style="max-width:210px;">
                        <span>${ou.faction_icon || '🏯'}</span>
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(${ou.id})"
                           class="font-weight-bold text-dark text-decoration-none hover-underline text-truncate" style="font-size:0.8rem;">
                            ${escapeFullHtml(ou.username)}
                        </a>
                        ${ou.alliance_tag ? `<span class="badge bg-secondary-lt ms-1" style="font-size:0.58rem;">[${escapeFullHtml(ou.alliance_tag)}]</span>` : ''}
                        ${ou.is_admin ? '<span class="badge bg-warning text-dark ms-1" style="font-size:0.55rem;">⭐ Admin</span>' : ''}
                        ${ou.is_moderator ? '<span class="badge bg-primary text-white ms-1" style="font-size:0.55rem;">🛡️ Modo</span>' : ''}
                    </div>
                    ${!isMe ? `
                        <button type="button" class="btn btn-sm btn-ghost-primary p-0 px-2"
                                title="Chuchoter à ${escapeFullHtml(ou.username)}"
                                onclick="setFullChatWhisper(${ou.id}, '${escapeFullHtml(ou.username)}')">
                            ✉️
                        </button>
                    ` : '<span class="badge bg-light text-muted" style="font-size:0.6rem;">Vous</span>'}
                </div>
            `;
        }).join('');
    }
}

function filterFullQueue(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('#chatFullChannelsList .list-group-item, #chatFullWhispersList .list-group-item, #chatFullOnlineList .list-group-item');
    items.forEach(el => {
        const txt = el.innerText.toLowerCase();
        if (!q || txt.includes(q)) {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });
}

function renderFullMessages(messages) {
    const container = document.getElementById('fullChatMessagesContainer');
    if (!container) return;

    if (fullRenderedIds.size === 0) {
        container.innerHTML = '';
    }

    let atBottom = (container.scrollHeight - container.scrollTop <= container.clientHeight + 80);

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

    if (atBottom || fullRenderedIds.size === messages.length) {
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
            fetchFullMessages();
        } else {
            alert(data.error || "Erreur lors de l'envoi.");
        }
    } catch (err) {
        alert("Erreur de connexion : " + (err.message || "Impossible de joindre le serveur."));
    } finally {
        btn.disabled = false;
        inp.focus();
    }
}

async function deleteFullChatMessage(msgId) {
    if (!confirm("Voulez-vous supprimer définitivement ce message du salon féodal ?")) return;

    const formData = new FormData();
    formData.append('message_id', msgId);

    try {
        const res = await fetch('/api/chat.php?action=delete', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById(`fullChatMsg_${msgId}`);
            if (el) {
                el.querySelector('.full-chat-msg-body').innerHTML = '<em class="text-muted">Message retiré par le Shōgunat ou son auteur</em>';
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
    updateFullSoundBtnUI();

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

    // Polling automatique en temps réel toutes les 2.5 secondes
    fullPollingTimer = setInterval(fetchFullMessages, 2500);
});
</script>
