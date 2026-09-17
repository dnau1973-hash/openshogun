</div> <!-- Fin .container -->

<!-- Modale Universelle d'Amélioration (Bâtiments et Parcelles) -->
<div class="modal-overlay" id="upgradeModal">
    <div class="modal-card">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Amélioration</h3>
            <button onclick="closeUpgradeModal()" class="modal-close-btn">&times;</button>
        </div>
        <div class="card-body">
            <p id="modalTargetLevel" style="font-weight:700; color:#dc2626; margin-bottom:1rem;"></p>
            
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">Ressources requises :</div>
            <div class="cost-row">
                <div class="cost-item"><span style="color:var(--res-metal);">🪵</span> <span id="modalCostMetal">0</span></div>
                <div class="cost-item"><span style="color:var(--res-crystal);">🪨</span> <span id="modalCostCrystal">0</span></div>
                <div class="cost-item"><span style="color:var(--res-deut);">🌾</span> <span id="modalCostDeut">0</span></div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin:1rem 0; font-size:0.9rem;">
                <span style="color:var(--text-muted);">Temps de travaux :</span>
                <span id="modalDuration" style="font-weight:700; color:#fff; font-family:monospace;">00:00:00</span>
            </div>

            <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem;">
                <button class="btn btn-secondary" onclick="closeUpgradeModal()">Fermer</button>
                <button class="btn btn-primary" id="modalConfirmBtn">Lancer les travaux</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale Personnalisée pour Messages, Alertes & Confirmations (Remplace alert/confirm) -->
<div class="modal-overlay" id="customAlertModal">
    <div class="modal-card alert-modal-card" id="customAlertCard">
        <div class="card-header">
            <h3 class="card-title" id="customAlertTitle">Décret du Shogunat</h3>
            <button onclick="closeCustomAlert()" class="modal-close-btn">&times;</button>
        </div>
        <div class="card-body">
            <div class="alert-modal-content">
                <span class="alert-modal-icon" id="customAlertIcon">ℹ️</span>
                <p class="alert-modal-text" id="customAlertText"></p>
            </div>
            <div class="alert-modal-actions" id="customAlertActions">
                <button class="btn btn-primary" id="customAlertBtnOk">Compris</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale d'Annonces & Notifications des Fonctionnalités -->
<?php require_once __DIR__ . '/announcement_modal.php'; ?>

<!-- Modale Universelle de la Fiche Daimyō / Profil (Style Travian) -->
<div class="modal-overlay" id="playerProfileModal" style="display:none; position:fixed; inset:0; background:rgba(5,7,15,0.85); backdrop-filter:blur(10px); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-card" style="max-width:750px; width:92%; max-height:90vh; background:rgba(17,18,24,0.96); border:1px solid #dc2626; border-radius:12px; box-shadow:0 0 50px rgba(220,38,38,0.25); display:flex; flex-direction:column; overflow:hidden;">
        <!-- En-tête Profil -->
        <div class="card-header" style="background:linear-gradient(135deg, rgba(185,28,28,0.3) 0%, rgba(17,18,24,0.9) 100%); border-bottom:1px solid rgba(220,38,38,0.3); padding:1.25rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <span id="profAvatar" style="font-size:2.5rem; filter:drop-shadow(0 0 10px rgba(220,38,38,0.5));">🏯</span>
                <div>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <h2 id="profUsername" style="font-size:1.4rem; font-weight:800; color:#fff; margin:0;">Daimyō</h2>
                        <span id="profOnlineBadge" style="font-size:0.7rem; padding:0.15rem 0.4rem; border-radius:4px; font-weight:700;"></span>
                    </div>
                    <div style="display:flex; gap:0.75rem; align-items:center; margin-top:0.25rem; font-size:0.8rem;">
                        <span id="profFactionBadge" class="faction-badge"></span>
                        <span id="profAlliance" style="color:var(--text-muted);"></span>
                        <span style="color:#facc15; font-weight:700;" id="profRank">Rang #1</span>
                    </div>
                </div>
            </div>
            <button onclick="closePlayerProfileModal()" style="background:transparent; border:none; color:#fff; font-size:1.6rem; cursor:pointer;">&times;</button>
        </div>

        <!-- Corps Scrollable du Profil -->
        <div class="card-body" style="padding:1.5rem; overflow-y:auto; flex:1;">
            <!-- Vitrine des Médailles de Prestige (Style Travian) -->
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 style="color:#facc15; font-size:1rem; font-weight:800; margin:0; display:flex; align-items:center; gap:0.4rem;">
                        <span>🎖️</span> Vitrine des Médailles d'Honneur
                    </h3>
                    <span id="profMedalsCount" style="font-size:0.75rem; color:var(--text-muted);">0 distinction(s)</span>
                </div>
                <div id="profMedalsList" style="display:flex; gap:0.75rem; flex-wrap:wrap; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); padding:1rem; border-radius:8px; min-height:60px; align-items:center;">
                    <!-- Les médailles s'injecteront ici -->
                </div>
            </div>

            <!-- Devise / Chronique du Daimyō -->
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                    <h3 style="color:#dc2626; font-size:1rem; font-weight:800; margin:0; display:flex; align-items:center; gap:0.4rem;">
                        <span>📜</span> Devise & Chronique du Daimyō
                    </h3>
                    <button id="profEditBioBtn" onclick="toggleBioEdit()" style="display:none; background:transparent; border:none; color:#dc2626; font-size:0.8rem; cursor:pointer; text-decoration:underline;">
                        ✏️ Modifier ma devise
                    </button>
                </div>
                <div id="profBioView" style="background:rgba(0,0,0,0.2); border-left:3px solid #dc2626; padding:0.85rem 1rem; border-radius:0 6px 6px 0; color:#e2e8f0; font-size:0.85rem; line-height:1.6; font-style:italic;">
                    <!-- Bio texte -->
                </div>
                <div id="profBioEditContainer" style="display:none; margin-top:0.5rem;">
                    <textarea id="profBioInput" class="form-control" rows="3" maxlength="1000" style="width:100%; font-size:0.85rem;"></textarea>
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem;">
                        <button onclick="toggleBioEdit()" class="btn btn-secondary" style="font-size:0.75rem; padding:0.25rem 0.6rem;">Annuler</button>
                        <button onclick="saveBio()" class="btn btn-primary" style="font-size:0.75rem; padding:0.25rem 0.6rem;">Enregistrer</button>
                    </div>
                </div>
            </div>

            <!-- Statistiques de Guerre de la Semaine -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;">
                <div style="background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.2); border-radius:6px; padding:0.75rem; text-align:center;">
                    <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Progression Semaine</div>
                    <div id="profStatProg" style="font-size:1.1rem; font-weight:800; color:#dc2626; margin-top:0.2rem;">+0 pts</div>
                </div>
                <div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); border-radius:6px; padding:0.75rem; text-align:center;">
                    <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Points Conquête</div>
                    <div id="profStatAtt" style="font-size:1.1rem; font-weight:800; color:#f87171; margin-top:0.2rem;">0 pts</div>
                </div>
                <div style="background:rgba(52,211,153,0.08); border:1px solid rgba(52,211,153,0.2); border-radius:6px; padding:0.75rem; text-align:center;">
                    <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Points Défense</div>
                    <div id="profStatDef" style="font-size:1.1rem; font-weight:800; color:#34d399; margin-top:0.2rem;">0 pts</div>
                </div>
                <div style="background:rgba(168,85,247,0.08); border:1px solid rgba(168,85,247,0.2); border-radius:6px; padding:0.75rem; text-align:center;">
                    <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase;">Riz Pillé Hebdo</div>
                    <div id="profStatRaid" style="font-size:1.1rem; font-weight:800; color:#c084fc; margin-top:0.2rem;">0</div>
                </div>
            </div>

            <!-- Territoire & Fiefs Recensés -->
            <div>
                <h3 style="color:#fff; font-size:1rem; font-weight:800; margin:0 0 0.75rem 0; display:flex; align-items:center; gap:0.4rem;">
                    <span>🏯</span> Fiefs & Domaines Provinciaux
                </h3>
                <div style="max-height:180px; overflow-y:auto; border:1px solid rgba(255,255,255,0.08); border-radius:6px;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.85rem; text-align:left;">
                        <thead>
                            <tr style="background:rgba(255,255,255,0.04); color:var(--text-muted); border-bottom:1px solid rgba(255,255,255,0.08);">
                                <th style="padding:0.5rem 0.75rem;">Fief</th>
                                <th style="padding:0.5rem 0.75rem;">Province</th>
                                <th style="padding:0.5rem 0.75rem;">Coordonnées</th>
                                <th style="padding:0.5rem 0.75rem; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="profPlanetsList">
                            <!-- Lignes des fiefs -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pied de page Profil Actions -->
        <div style="background:rgba(10,15,29,0.9); border-top:1px solid rgba(255,255,255,0.06); padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            <div id="profFooterLeft">
                <!-- Actions contextuelles comme Contacter -->
            </div>
            <button class="btn btn-secondary" onclick="closePlayerProfileModal()">Fermer</button>
        </div>
    </div>
</div>

<!-- Modale Complète Didacticiel Féodal & Voie du Daimyō (Style Maître de Quête Travian) -->
<div class="modal-overlay" id="questModal" style="display:none; position:fixed; inset:0; background:rgba(5,7,15,0.85); backdrop-filter:blur(10px); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-card" style="max-width:920px; width:94%; max-height:90vh; background:rgba(17,18,24,0.96); border:1px solid #dc2626; border-radius:12px; box-shadow:0 0 50px rgba(220,38,38,0.25); display:flex; flex-direction:column; overflow:hidden;">
        <!-- En-tête -->
        <div class="card-header" style="background:linear-gradient(135deg, rgba(185,28,28,0.3) 0%, rgba(17,18,24,0.9) 100%); border-bottom:1px solid rgba(220,38,38,0.3); padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:0.85rem;">
                <span style="font-size:2rem; filter:drop-shadow(0 0 8px rgba(220,38,38,0.6));">🎯</span>
                <div>
                    <h2 style="font-size:1.3rem; font-weight:800; color:#fff; margin:0; display:flex; align-items:center; gap:0.5rem;">
                        Codex des Quêtes & Didacticiel du Daimyō
                    </h2>
                    <div style="display:flex; align-items:center; gap:0.75rem; margin-top:0.2rem; font-size:0.8rem; color:#94a3b8;">
                        <span id="questModalProgressText">0/12 Quêtes Accomplies</span> &bull; 
                        <span style="color:#facc15; font-weight:700;" id="questModalPercentText">0% Complété</span>
                    </div>
                </div>
            </div>
            <button onclick="closeQuestModal()" style="background:transparent; border:none; color:#fff; font-size:1.6rem; cursor:pointer;">&times;</button>
        </div>

        <!-- Corps de la Modale en 2 Colonnes Responsive -->
        <div class="card-body" style="padding:0; display:flex; flex:1; overflow:hidden; min-height:480px;">
            <!-- Colonne Gauche : Liste des 12 Quêtes -->
            <div style="width:340px; border-right:1px solid rgba(255,255,255,0.08); background:rgba(10,12,18,0.6); overflow-y:auto; padding:0.75rem;" id="questModalList">
                <!-- Les tuiles de quêtes seront injectées dynamiquement ici -->
            </div>

            <!-- Colonne Droite : Fiche Détaillée de la Quête Sélectionnée -->
            <div style="flex:1; overflow-y:auto; padding:1.5rem; display:flex; flex-direction:column; justify-content:space-between;" id="questModalDetail">
                <!-- Détail de la quête injecté dynamiquement -->
            </div>
        </div>

        <!-- Pied de page -->
        <div style="background:rgba(10,15,29,0.9); border-top:1px solid rgba(255,255,255,0.06); padding:0.85rem 1.5rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:0.8rem; color:#94a3b8;">
                🥋 <em>Guide de l'art de la guerre enseigné par Katsumoto, Maître d'Armes</em>
            </div>
            <button class="btn btn-secondary" onclick="closeQuestModal()">Fermer le Codex</button>
        </div>
    </div>
</div>

<footer style="text-align:center; padding:2rem 1rem; color:var(--text-muted); font-size:0.85rem; border-top:1px solid rgba(255,255,255,0.05); margin-top:3rem;">
    <p>OpenShogun &copy; <?= date('Y') ?> - Jeu de stratégie féodale japonaise par navigateur inspiré de Travian.</p>
    <p style="margin-top:0.35rem; color:#64748b;">Moteur féodal Sengoku PHP 8 + MariaDB + JavaScript Vanilla</p>
</footer>

<script src="/public/js/app.js"></script>
<script>
let currentViewingProfileId = null;

async function openPlayerProfileModal(userId) {
    const uid = parseInt(userId, 10);
    if (!uid || isNaN(uid) || uid <= 0) {
        showModalAlert("Information", "Aucun Daimyō répertorié sur ce territoire.", "info");
        return;
    }

    currentViewingProfileId = uid;
    const modal = document.getElementById('playerProfileModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.getElementById('profUsername').innerText = "Chargement...";
    document.getElementById('profBioView').innerText = "Lecture des parchemins...";
    document.getElementById('profMedalsList').innerHTML = "<span style='color:var(--text-muted); font-size:0.85rem;'>Récupération des distinctions...</span>";
    document.getElementById('profPlanetsList').innerHTML = "<tr><td colspan='4' style='padding:1rem; text-align:center; color:var(--text-muted);'>Recherche des fiefs...</td></tr>";

    try {
        const res = await fetch(`/api/profile.php?action=get_profile&user_id=${uid}`);
        const data = await res.json();

        if (!data.success) {
            showModalAlert("Erreur", data.error || "Impossible de charger le profil.", "danger");
            closePlayerProfileModal();
            return;
        }

        const p = data.profile;
        if (!p) {
            showModalAlert("Erreur", "Daimyō introuvable dans les registres impériaux.", "danger");
            closePlayerProfileModal();
            return;
        }

        const factionIcons = { 'terran': '🏯', 'vorash': '🐎', 'aethelis': '⛩️' };
        const fKey = (p.faction || 'terran').toLowerCase();

        document.getElementById('profAvatar').innerText = factionIcons[fKey] || '🏯';
        document.getElementById('profUsername').innerText = p.username || 'Daimyō Inconnu';
        document.getElementById('profRank').innerText = `Rang #${p.rank_pos || 1} (${(p.points || 0).toLocaleString()} pts)`;

        const onlineBadge = document.getElementById('profOnlineBadge');
        if (p.is_online) {
            onlineBadge.innerText = "🟢 En ligne";
            onlineBadge.style.background = "rgba(74, 222, 128, 0.2)";
            onlineBadge.style.color = "#4ade80";
        } else {
            onlineBadge.innerText = "⚪ Hors-ligne";
            onlineBadge.style.background = "rgba(148, 163, 184, 0.2)";
            onlineBadge.style.color = "#94a3b8";
        }

        const fBadge = document.getElementById('profFactionBadge');
        fBadge.className = `faction-badge ${fKey}`;
        fBadge.innerText = (p.faction_name || fKey).toUpperCase();

        document.getElementById('profAlliance').innerText = p.alliance_tag ? `[${p.alliance_tag}] ${p.alliance_name || ''}` : "Clan Indépendant";

        // Médailles (Style Travian)
        const medalsList = document.getElementById('profMedalsList');
        const medals = p.medals || [];
        document.getElementById('profMedalsCount').innerText = `${medals.length} distinction(s)`;

        if (medals.length === 0) {
            medalsList.innerHTML = "<span style='color:var(--text-muted); font-size:0.85rem;'>Aucune médaille d'honneur décernée pour le moment.</span>";
        } else {
            medalsList.innerHTML = medals.map(m => `
                <div style="background: rgba(255,255,255,0.05); border: 1px solid ${m.color || '#facc15'}; border-radius: 8px; padding: 0.5rem 0.75rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: transform 0.2s;"
                     title="${escapeHtmlModal(m.description || '')} (${m.week_code || ''}) - Décerné le ${m.awarded_at || ''}"
                     onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="font-size: 1.5rem;">${m.icon || '🎖️'}</span>
                    <div>
                        <div style="font-size: 0.8rem; font-weight: 700; color: ${m.color || '#facc15'};">${m.category_label || 'Honneur'} #${m.rank || 1}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">${m.week_code || ''}</div>
                    </div>
                </div>
            `).join('');
        }

        // Bio
        const bioText = p.bio || "Fier Daimyō au service de l'honneur de son clan et de l'Empereur.";
        document.getElementById('profBioView').innerText = bioText;
        document.getElementById('profBioInput').value = bioText;
        document.getElementById('profBioEditContainer').style.display = 'none';
        document.getElementById('profBioView').style.display = 'block';

        const editBtn = document.getElementById('profEditBioBtn');
        if (p.is_self) {
            editBtn.style.display = 'inline-block';
        } else {
            editBtn.style.display = 'none';
        }

        // Stats hebdo
        const ws = p.weekly_stats || {};
        document.getElementById('profStatProg').innerText = `+${(ws.progression || 0).toLocaleString()} pts`;
        document.getElementById('profStatAtt').innerText = `${(ws.attack_points || 0).toLocaleString()} pts`;
        document.getElementById('profStatDef').innerText = `${(ws.defense_points || 0).toLocaleString()} pts`;
        document.getElementById('profStatRaid').innerText = `${(ws.raid_resources || 0).toLocaleString()} Riz`;

        // Colonies
        const planetsTbody = document.getElementById('profPlanetsList');
        const colonies = p.planets || p.colonies || [];
        if (colonies.length === 0) {
            planetsTbody.innerHTML = "<tr><td colspan='4' style='padding:0.75rem; text-align:center; color:var(--text-muted);'>Aucun fief recensé.</td></tr>";
        } else {
            planetsTbody.innerHTML = colonies.map(pl => `
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 0.5rem 0.75rem; font-weight: 700; color: #fff;">
                        ${pl.is_capital ? '⭐ ' : '🏯 '} ${escapeHtmlModal(pl.name || 'Fief')}
                    </td>
                    <td style="padding: 0.5rem 0.75rem; color: var(--text-muted);">${pl.planet_type || 'Fief Castral'}</td>
                    <td style="padding: 0.5rem 0.75rem; font-family: monospace; color: #dc2626;">[${pl.coord_x} : ${pl.coord_y}]</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right;">
                        <a href="?page=map&x=${pl.coord_x}&y=${pl.coord_y}" class="btn btn-secondary" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;" title="Voir sur la carte">
                            🗾 Provinces
                        </a>
                        ${!p.is_self ? `
                            <a href="?page=fleet&target_id=${pl.id}&mission=raid" class="btn btn-primary" style="background:#b91c1c; font-size: 0.7rem; padding: 0.2rem 0.5rem; margin-left: 0.25rem;">
                                ⚔️ Raid
                            </a>
                        ` : ''}
                    </td>
                </tr>
            `).join('');
        }

        // Actions bas de page
        const footerLeft = document.getElementById('profFooterLeft');
        if (!p.is_self) {
            footerLeft.innerHTML = `
                <a href="?page=messages&tab=compose&to=${encodeURIComponent(p.username || '')}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85rem;">
                    <span>✉️</span> Dépêcher une Missive
                </a>
            `;
        } else {
            footerLeft.innerHTML = `
                <span style="color:#dc2626; font-size:0.85rem; font-weight:600;">👑 Il s'agit de votre Fiche de Daimyō officielle</span>
            `;
        }

    } catch (e) {
        console.error("Erreur fiche joueur:", e);
        showModalAlert("Erreur", "Impossible d'établir la liaison avec la base de données : " + (e.message || e), "danger");
        closePlayerProfileModal();
    }
}

function escapeHtmlModal(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closePlayerProfileModal() {
    const modal = document.getElementById('playerProfileModal');
    if (modal) modal.style.display = 'none';
}

function toggleBioEdit() {
    const view = document.getElementById('profBioView');
    const container = document.getElementById('profBioEditContainer');
    if (container.style.display === 'none') {
        container.style.display = 'block';
        view.style.display = 'none';
    } else {
        container.style.display = 'none';
        view.style.display = 'block';
    }
}

async function saveBio() {
    const bioText = document.getElementById('profBioInput').value;
    try {
        const formData = new FormData();
        formData.append('action', 'update_bio');
        formData.append('bio', bioText);

        const res = await fetch('/api/profile.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            document.getElementById('profBioView').innerText = data.bio;
            toggleBioEdit();
            showModalAlert("Manifeste Sauvegardé", data.message, "success");
        } else {
            showModalAlert("Erreur", data.error || "Impossible d'enregistrer.", "danger");
        }
    } catch (e) {
        showModalAlert("Erreur", "Une erreur est survenue lors de la sauvegarde.", "danger");
    }
}

/* ==========================================================================
   GESTION DU DIDACTICIEL & CODEX DES QUÊTES FÉODALES (STYLE TRAVIAN)
   ========================================================================== */
let cachedQuestsData = null;
let selectedQuestKey = null;

async function openQuestModal(forceKey = null) {
    const modal = document.getElementById('questModal');
    if (!modal) return;
    modal.style.display = 'flex';

    try {
        const res = await fetch('/api/quests.php?action=get_status');
        const json = await res.json();
        if (json.success && json.data) {
            cachedQuestsData = json.data;
            if (forceKey) {
                selectedQuestKey = forceKey;
            } else if (!selectedQuestKey || !cachedQuestsData.quests.find(q => q.key === selectedQuestKey)) {
                selectedQuestKey = cachedQuestsData.active_quest ? cachedQuestsData.active_quest.key : cachedQuestsData.quests[0].key;
            }
            renderQuestModal();
        }
    } catch (e) {
        console.error("Erreur chargement quêtes:", e);
    }
}

function closeQuestModal() {
    const modal = document.getElementById('questModal');
    if (modal) modal.style.display = 'none';
}

function selectQuestInModal(questKey) {
    selectedQuestKey = questKey;
    renderQuestModal();
}

function renderQuestModal() {
    if (!cachedQuestsData) return;
    const { quests, active_quest, claimed_count, total_quests, overall_percent } = cachedQuestsData;

    const progText = document.getElementById('questModalProgressText');
    const pctText = document.getElementById('questModalPercentText');
    if (progText) progText.innerText = `${claimed_count}/${total_quests} Quêtes Accomplies`;
    if (pctText) pctText.innerText = `${overall_percent}% Complété`;

    // Liste des quêtes à gauche
    const listContainer = document.getElementById('questModalList');
    if (listContainer) {
        let listHtml = '';
        quests.forEach(q => {
            const isSelected = (q.key === selectedQuestKey);
            const isClaimed = (q.status === 'claimed');
            const isClaimable = (q.is_claimable);
            
            let statusBadge = '';
            let borderStyle = isSelected ? 'border: 1px solid #dc2626; background: rgba(220,38,38,0.18);' : 'border: 1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02);';

            if (isClaimed) {
                statusBadge = '<span style="color:#4ade80; font-size:0.75rem; font-weight:700;">✓ Perçue</span>';
            } else if (isClaimable) {
                statusBadge = '<span style="color:#facc15; font-size:0.75rem; font-weight:800;">✨ Prête !</span>';
                if (!isSelected) {
                    borderStyle = 'border: 1px solid #22c55e; background: rgba(34,197,94,0.08);';
                }
            } else {
                statusBadge = '<span style="color:#94a3b8; font-size:0.75rem;">En cours</span>';
            }

            listHtml += `
                <div onclick="selectQuestInModal('${q.key}')" style="${borderStyle} border-radius:8px; padding:0.65rem 0.85rem; margin-bottom:0.5rem; cursor:pointer; display:flex; align-items:center; gap:0.75rem; transition:all 0.2s ease;">
                    <div style="font-size:1.6rem; min-width:32px; text-align:center;">${q.icon}</div>
                    <div style="flex:1; overflow:hidden;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-size:0.7rem; color:#dc2626; font-weight:800;">ÉTAPE ${q.order}</span>
                            ${statusBadge}
                        </div>
                        <div style="font-size:0.85rem; font-weight:700; color:#fff; white-space:nowrap; text-overflow:ellipsis; overflow:hidden; margin-top:2px;">
                            ${escapeHtmlModal(q.title)}
                        </div>
                    </div>
                </div>
            `;
        });
        listContainer.innerHTML = listHtml;
    }

    // Détail à droite
    const detailContainer = document.getElementById('questModalDetail');
    const currentQ = quests.find(q => q.key === selectedQuestKey) || active_quest || quests[0];
    if (detailContainer && currentQ) {
        const isClaimed = (currentQ.status === 'claimed');
        const isClaimable = (currentQ.is_claimable);

        let actionButtonHtml = '';
        if (isClaimed) {
            actionButtonHtml = `
                <div style="background:rgba(34,197,94,0.1); border:1px solid #22c55e; color:#4ade80; padding:0.75rem 1.25rem; border-radius:8px; font-weight:700; display:flex; align-items:center; gap:0.6rem; justify-content:center;">
                    <span>✓</span> Récompense perçue avec honneur le ${currentQ.claimed_at ? currentQ.claimed_at.substring(0, 16) : 'récemment'}.
                </div>
            `;
        } else if (isClaimable) {
            actionButtonHtml = `
                <button type="button" onclick="claimQuestReward('${currentQ.key}')" class="btn btn-primary pulse-btn" style="width:100%; background:linear-gradient(135deg, #10b981 0%, #059669 100%); border-color:#047857; color:#fff; font-weight:800; font-size:1.05rem; padding:0.85rem; border-radius:8px; cursor:pointer; box-shadow:0 4px 20px rgba(16,185,129,0.5);">
                    ✨ Réclamer ma Récompense Immédiatement
                </button>
            `;
        } else {
            if (currentQ.action_url.startsWith('javascript:')) {
                actionButtonHtml = `
                    <button type="button" onclick="closeQuestModal(); ${currentQ.action_url.substring(11)};" class="btn btn-primary" style="width:100%; padding:0.75rem; font-size:1rem; font-weight:700;">
                        ${escapeHtmlModal(currentQ.action_label)} &rarr;
                    </button>
                `;
            } else {
                actionButtonHtml = `
                    <a href="${currentQ.action_url}" class="btn btn-primary" style="display:block; text-align:center; text-decoration:none; padding:0.75rem; font-size:1rem; font-weight:700;">
                        ${escapeHtmlModal(currentQ.action_label)} &rarr;
                    </a>
                `;
            }
        }

        let rewardsHtml = '';
        if (currentQ.rewards.metal) {
            rewardsHtml += `<div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:0.5rem 0.8rem; text-align:center; min-width:90px;"><span style="color:var(--res-metal,#60a5fa); font-size:1.1rem;">🪵</span><div style="font-size:0.75rem; color:#94a3b8;">Bois de Cèdre</div><div style="font-size:0.95rem; font-weight:800; color:#fff;">+${Number(currentQ.rewards.metal).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.crystal) {
            rewardsHtml += `<div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:0.5rem 0.8rem; text-align:center; min-width:90px;"><span style="color:var(--res-crystal,#e2e8f0); font-size:1.1rem;">🪨</span><div style="font-size:0.75rem; color:#94a3b8;">Pierre de Taille</div><div style="font-size:0.95rem; font-weight:800; color:#fff;">+${Number(currentQ.rewards.crystal).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.deuterium) {
            rewardsHtml += `<div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:0.5rem 0.8rem; text-align:center; min-width:90px;"><span style="color:var(--res-deut,#4ade80); font-size:1.1rem;">🌾</span><div style="font-size:0.75rem; color:#94a3b8;">Riz Impérial</div><div style="font-size:0.95rem; font-weight:800; color:#fff;">+${Number(currentQ.rewards.deuterium).toLocaleString()}</div></div>`;
        }
        if (currentQ.rewards.points) {
            rewardsHtml += `<div style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.06); border-radius:6px; padding:0.5rem 0.8rem; text-align:center; min-width:90px;"><span style="color:#facc15; font-size:1.1rem;">⛩️</span><div style="font-size:0.75rem; color:#94a3b8;">Honneur</div><div style="font-size:0.95rem; font-weight:800; color:#facc15;">+${Number(currentQ.rewards.points).toLocaleString()} pts</div></div>`;
        }
        if (currentQ.rewards.bonus_units) {
            rewardsHtml += `<div style="background:rgba(220,38,38,0.12); border:1px solid #dc2626; border-radius:6px; padding:0.5rem 0.8rem; text-align:center; min-width:110px;"><span style="color:#f87171; font-size:1.1rem;">⚔️</span><div style="font-size:0.75rem; color:#fca5a5;">Garnison</div><div style="font-size:0.95rem; font-weight:800; color:#fff;">+${currentQ.rewards.bonus_units} Guerriers</div></div>`;
        }

        detailContainer.innerHTML = `
            <div>
                <!-- En-tête Quête -->
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                    <div>
                        <span style="font-size:0.75rem; font-weight:800; color:#dc2626; text-transform:uppercase;">QUÊTE ${currentQ.order} SUR ${total_quests} &bull; ${escapeHtmlModal(currentQ.category.toUpperCase())}</span>
                        <h2 style="font-size:1.4rem; font-weight:900; color:#fff; margin:0.25rem 0 0 0;">
                            ${currentQ.icon} ${escapeHtmlModal(currentQ.title)}
                        </h2>
                    </div>
                    <span style="font-size:0.8rem; padding:0.25rem 0.65rem; border-radius:6px; font-weight:700; ${isClaimed ? 'background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid #22c55e;' : (isClaimable ? 'background:rgba(250,204,21,0.2); color:#facc15; border:1px solid #eab308;' : 'background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);')}">
                        ${isClaimed ? '✓ Accompli' : (isClaimable ? '✨ Prêt à réclamer' : 'En cours')}
                    </span>
                </div>

                <!-- Dialogue du Conseiller Katsumoto -->
                <div style="background:rgba(0,0,0,0.25); border-left:3px solid #dc2626; border-radius:0 8px 8px 0; padding:1rem; margin-bottom:1.25rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                        <span style="font-size:1.1rem;">🥋</span>
                        <strong style="color:#facc15; font-size:0.85rem;">${escapeHtmlModal(currentQ.mentor_name)} :</strong>
                    </div>
                    <p style="margin:0; font-size:0.88rem; color:#cbd5e1; line-height:1.6; font-style:italic;">
                        &laquo; ${escapeHtmlModal(currentQ.lore)} &raquo;
                    </p>
                </div>

                <!-- Objectif précis -->
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:8px; padding:0.85rem 1rem; margin-bottom:1.25rem;">
                    <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; font-weight:700; margin-bottom:0.25rem;">Objectif à atteindre :</div>
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                        <span style="font-size:1rem; font-weight:800; color:${isClaimable || isClaimed ? '#4ade80' : '#fff'};">
                            ${isClaimable || isClaimed ? '✓ ' : '🎯 '} ${escapeHtmlModal(currentQ.objective)}
                        </span>
                        <span style="font-size:0.75rem; color:#94a3b8; background:rgba(0,0,0,0.4); padding:0.2rem 0.5rem; border-radius:4px;">
                            📍 ${escapeHtmlModal(currentQ.target_slot_hint || 'Fief')}
                        </span>
                    </div>
                </div>

                <!-- Récompenses promises -->
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:0.75rem; color:#94a3b8; text-transform:uppercase; font-weight:700; margin-bottom:0.5rem;">Récompenses accordées par le Shogunat :</div>
                    <div style="display:flex; gap:0.65rem; flex-wrap:wrap;">
                        ${rewardsHtml}
                    </div>
                </div>
            </div>

            <!-- Bouton d'action bas de panneau -->
            <div style="margin-top:1rem;">
                ${actionButtonHtml}
            </div>
        `;
    }
}

async function claimQuestReward(questKey) {
    try {
        const formData = new FormData();
        formData.append('action', 'claim');
        formData.append('quest_key', questKey);

        const res = await fetch('/api/quests.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            if (data.planet) {
                updateHudResources(data.planet);
            }

            if (data.quests_status) {
                cachedQuestsData = data.quests_status;
                selectedQuestKey = cachedQuestsData.active_quest ? cachedQuestsData.active_quest.key : questKey;
                renderQuestModal();
            }

            let rewardDetailMsg = '';
            if (data.rewards.metal) rewardDetailMsg += `+${Number(data.rewards.metal).toLocaleString()} 🪵 Bois, `;
            if (data.rewards.crystal) rewardDetailMsg += `+${Number(data.rewards.crystal).toLocaleString()} 🪨 Pierre, `;
            if (data.rewards.deuterium) rewardDetailMsg += `+${Number(data.rewards.deuterium).toLocaleString()} 🌾 Riz, `;
            if (data.rewards.points) rewardDetailMsg += `+${data.rewards.points} ⛩️ Honneur, `;
            if (data.bonus_units) rewardDetailMsg += `+${data.bonus_units} ⚔️ ${data.rewarded_unit_name}, `;
            rewardDetailMsg = rewardDetailMsg.replace(/, $/, '');

            showModalAlert("Récompense de Daimyō Perçue !", `${data.message}\n\nVos coffres reçoivent : ${rewardDetailMsg}`, "success");

            setTimeout(() => {
                window.location.reload();
            }, 1200);

        } else {
            showModalAlert("Décret Impérial", data.error || "Impossible de réclamer la récompense.", "warning");
        }
    } catch (e) {
        console.error("Erreur réclamation:", e);
        showModalAlert("Erreur", "Une erreur est survenue lors de la réclamation de la récompense.", "danger");
    }
}

function updateHudResources(planet) {
    if (!planet) return;
    const updateEl = (valId, barId, current, max) => {
        const valEl = document.getElementById(valId);
        const barEl = document.getElementById(barId);
        if (valEl) {
            valEl.setAttribute('data-current', current);
            valEl.innerText = Math.floor(current).toLocaleString();
        }
        if (barEl && max > 0) {
            barEl.style.width = Math.min(100, (current / max) * 100) + '%';
        }
    };
    updateEl('res-val-metal', 'bar-metal', planet.metal, planet.metal_max);
    updateEl('res-val-crystal', 'bar-crystal', planet.crystal, planet.crystal_max);
    updateEl('res-val-deut', 'bar-deut', planet.deuterium, planet.deuterium_max);
}
</script>
</body>
</html>

