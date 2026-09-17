<!-- Modale d'Annonce des Nouvelles Fonctionnalités (Style Parchemin Impérial Sengoku) -->
<div class="modal-overlay" id="announcementModal" style="display:none; position:fixed; inset:0; background:rgba(5,7,15,0.88); backdrop-filter:blur(10px); z-index:1050; align-items:center; justify-content:center; padding:1rem;">
    <div class="modal-card announcement-modal-card" style="max-width:760px; width:100%; max-height:90vh; background:linear-gradient(160deg, #141620 0%, #0d0e15 100%); border:2px solid #b91c1c; border-radius:14px; box-shadow:0 0 50px rgba(185,28,28,0.35), inset 0 0 20px rgba(0,0,0,0.6); display:flex; flex-direction:column; overflow:hidden; animation: modalEntrance 0.35s ease-out;">
        
        <!-- En-tête Impérial -->
        <div style="background:linear-gradient(135deg, rgba(185,28,28,0.35) 0%, rgba(30,15,15,0.85) 100%); border-bottom:1px solid rgba(220,38,38,0.4); padding:1.25rem 1.75rem; display:flex; justify-content:space-between; align-items:center; position:relative;">
            <div style="display:flex; align-items:center; gap:1rem;">
                <div id="announcementIconBox" style="font-size:2.2rem; background:rgba(0,0,0,0.4); width:54px; height:54px; border-radius:10px; display:flex; align-items:center; justify-content:center; border:1px solid rgba(250,204,21,0.4); box-shadow:0 0 15px rgba(250,204,21,0.2);">
                    📜
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:0.6rem; flex-wrap:wrap;">
                        <span id="announcementBadge" style="background:#b91c1c; color:#fff; font-size:0.75rem; font-weight:800; padding:0.2rem 0.6rem; border-radius:4px; letter-spacing:0.5px; border:1px solid rgba(255,255,255,0.2);">
                            ⭐ NOUVEAUTÉ
                        </span>
                        <span id="announcementVersion" style="color:#facc15; font-size:0.85rem; font-weight:700; font-family:monospace;">
                            v1.0.0
                        </span>
                        <span id="announcementDate" style="color:var(--text-muted); font-size:0.8rem;">
                            17/09/2026
                        </span>
                    </div>
                    <h2 id="announcementTitle" style="font-size:1.35rem; font-weight:800; color:#fff; margin:0.35rem 0 0 0; text-shadow:0 2px 4px rgba(0,0,0,0.6);">
                        Décret du Shogunat : Nouvelles Fonctionnalités
                    </h2>
                </div>
            </div>
            <button type="button" onclick="closeAnnouncementModalSilently()" id="announcementCloseBtn" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); color:#9ca3af; width:34px; height:34px; border-radius:8px; font-size:1.4rem; line-height:1; cursor:pointer; transition:all 0.2s;" title="Fermer provisoirement">
                &times;
            </button>
        </div>

        <!-- Corps avec Scroll -->
        <div style="padding:1.5rem 1.75rem; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:1.25rem;">
            
            <!-- Résumé / Message du Shogun -->
            <div id="announcementSummaryBox" style="background:rgba(220,38,38,0.08); border-left:4px solid #dc2626; padding:0.9rem 1.2rem; border-radius:0 8px 8px 0; color:#e2e8f0; font-size:0.95rem; line-height:1.55;">
                <p id="announcementSummary" style="margin:0;"></p>
            </div>

            <!-- Liste détaillée des nouveautés -->
            <div>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.85rem;">
                    <span style="font-size:1.1rem;">🏯</span>
                    <h3 style="margin:0; font-size:1rem; font-weight:700; color:#facc15; text-transform:uppercase; letter-spacing:0.5px;">
                        Détails & Bénéfices Stratégiques
                    </h3>
                </div>

                <div id="announcementFeaturesList" style="display:flex; flex-direction:column; gap:0.85rem;">
                    <!-- Injecté en JS -->
                </div>
            </div>

        </div>

        <!-- Pied de la modale avec bouton d'acquittement obligatoire -->
        <div style="background:rgba(10,12,18,0.95); border-top:1px solid rgba(255,255,255,0.08); padding:1.1rem 1.75rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
            <div style="font-size:0.8rem; color:var(--text-muted); display:flex; align-items:center; gap:0.4rem;">
                <span>ℹ️</span> Valider la lecture évite la réapparition de cette annonce.
            </div>
            <div style="display:flex; gap:0.75rem;">
                <button type="button" id="btnAcknowledgeAnnouncement" onclick="acknowledgeCurrentAnnouncement()" class="btn btn-primary" style="background:linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); border:1px solid #ef4444; padding:0.65rem 1.4rem; font-weight:700; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem; border-radius:8px; cursor:pointer; box-shadow:0 0 15px rgba(220,38,38,0.4);">
                    <span>✓</span> J'ai pris connaissance de ces nouveautés
                </button>
            </div>
        </div>

    </div>
</div>

<style>
@keyframes modalEntrance {
    from { opacity: 0; transform: scale(0.92) translateY(15px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.announcement-feature-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 8px;
    padding: 1rem 1.15rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    transition: background 0.2s, border-color 0.2s, transform 0.15s;
}
.announcement-feature-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(220, 38, 38, 0.3);
    transform: translateY(-2px);
}
.announcement-feature-icon {
    font-size: 1.6rem;
    line-height: 1;
    background: rgba(0, 0, 0, 0.4);
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}
.announcement-category-pill {
    background: rgba(250, 204, 21, 0.12);
    color: #facc15;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    border: 1px solid rgba(250, 204, 21, 0.25);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<script>
let currentActiveAnnouncement = null;
let isAnnouncementPreviewMode = false;

document.addEventListener('DOMContentLoaded', () => {
    // Vérifier les annonces non lues uniquement si l'utilisateur est connecté
    checkUnreadAnnouncements();
});

async function checkUnreadAnnouncements() {
    try {
        const res = await fetch('/api/announcements.php?action=get_unread');
        if (!res.ok) return;
        const data = await res.json();

        if (data.success && data.has_unread && data.announcement) {
            isAnnouncementPreviewMode = false;
            displayAnnouncementModal(data.announcement);
        }
    } catch (e) {
        console.warn("Vérification des annonces:", e);
    }
}

function displayAnnouncementModal(announcement, isPreview = false) {
    currentActiveAnnouncement = announcement;
    isAnnouncementPreviewMode = isPreview;

    const modal = document.getElementById('announcementModal');
    if (!modal) return;

    // Éléments
    document.getElementById('announcementIconBox').innerText = announcement.icon || '📜';
    document.getElementById('announcementBadge').innerText = announcement.badge || '✨ NOUVEAUTÉ';
    document.getElementById('announcementVersion').innerText = announcement.version || '';
    document.getElementById('announcementDate').innerText = announcement.date || '';
    document.getElementById('announcementTitle').innerText = announcement.title || 'Mise à jour';

    const summaryBox = document.getElementById('announcementSummaryBox');
    const summaryText = document.getElementById('announcementSummary');
    if (announcement.summary && announcement.summary.trim() !== '') {
        summaryText.innerText = announcement.summary;
        summaryBox.style.display = 'block';
    } else {
        summaryBox.style.display = 'none';
    }

    // Liste des fonctionnalités
    const listContainer = document.getElementById('announcementFeaturesList');
    listContainer.innerHTML = '';

    if (Array.isArray(announcement.features) && announcement.features.length > 0) {
        announcement.features.forEach(f => {
            const card = document.createElement('div');
            card.className = 'announcement-feature-card';
            card.innerHTML = `
                <div class="announcement-feature-icon">${escapeHtml(f.icon || '🔹')}</div>
                <div style="flex:1;">
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem; flex-wrap:wrap;">
                        <span class="announcement-category-pill">${escapeHtml(f.category || 'Général')}</span>
                        <strong style="color:#fff; font-size:0.95rem;">${escapeHtml(f.title || '')}</strong>
                    </div>
                    <div style="color:var(--text-muted, #9ca3af); font-size:0.875rem; line-height:1.5;">
                        ${escapeHtml(f.description || '')}
                    </div>
                </div>
            `;
            listContainer.appendChild(card);
        });
    } else {
        listContainer.innerHTML = '<div style="color:var(--text-muted); font-style:italic; font-size:0.85rem;">Aucun détail spécifique renseigné.</div>';
    }

    const ackBtn = document.getElementById('btnAcknowledgeAnnouncement');
    if (isPreview) {
        ackBtn.innerHTML = '<span>👁️</span> Fermer l\'aperçu';
    } else {
        ackBtn.innerHTML = '<span>✓</span> J\'ai pris connaissance de ces nouveautés';
    }

    modal.style.display = 'flex';
}

function closeAnnouncementModalSilently() {
    const modal = document.getElementById('announcementModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function acknowledgeCurrentAnnouncement() {
    if (isAnnouncementPreviewMode) {
        closeAnnouncementModalSilently();
        return;
    }

    if (!currentActiveAnnouncement || !currentActiveAnnouncement.id) {
        closeAnnouncementModalSilently();
        return;
    }

    const btn = document.getElementById('btnAcknowledgeAnnouncement');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Enregistrement...';

    try {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('announcement_id', currentActiveAnnouncement.id);

        const res = await fetch('/api/announcements.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
            // S'il reste une autre annonce non lue dans la pile
            if (data.next_announcement) {
                displayAnnouncementModal(data.next_announcement, false);
            } else {
                closeAnnouncementModalSilently();
            }
        } else {
            alert(data.error || "Erreur lors de la validation de lecture.");
        }
    } catch (e) {
        console.error("Erreur validation annonce:", e);
        closeAnnouncementModalSilently();
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Fonction globale pour prévisualisation directe depuis l'admin
window.openAnnouncementPreview = function(announcementData) {
    displayAnnouncementModal(announcementData, true);
};

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

