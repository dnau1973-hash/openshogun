<!-- Modale d'Annonce des Nouvelles Fonctionnalités (Style Décret Impérial Washi) -->
<div class="modal-overlay" id="announcementModal">
    <div class="modal-card modal-card-lg announcement-modal-card">
        
        <!-- En-tête Impérial Washi -->
        <div class="modal-header">
            <div class="d-flex align-items-center gap-3">
                <div id="announcementIconBox" style="font-size:2.2rem; background:#ffffff; width:52px; height:52px; border-radius:10px; display:flex; align-items:center; justify-content:center; border:1px solid var(--border-color); box-shadow:0 2px 6px rgba(60,45,30,0.06);">
                    📜
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span id="announcementBadge" class="badge badge-danger">
                            ⭐ NOUVEAUTÉ
                        </span>
                        <span id="announcementVersion" style="color:#b45309; font-size:0.85rem; font-weight:800; font-family:monospace;">
                            v1.0.0
                        </span>
                        <span id="announcementDate" style="color:var(--text-muted); font-size:0.8rem; font-weight:600;">
                            17/09/2026
                        </span>
                    </div>
                    <h2 id="announcementTitle" class="modal-title mt-1" style="font-size:1.3rem;">
                        Décret du Shogunat : Nouvelles Fonctionnalités
                    </h2>
                </div>
            </div>
            <button type="button" onclick="closeAnnouncementModalSilently()" id="announcementCloseBtn" class="modal-close-btn" title="Fermer provisoirement">
                &times;
            </button>
        </div>

        <!-- Corps avec Scroll -->
        <div class="modal-body d-flex flex-column gap-3">
            
            <!-- Résumé / Message du Shogun -->
            <div id="announcementSummaryBox" style="background:#ffffff; border:1px solid var(--border-color); border-left:4px solid #b91c1c; padding:1rem 1.25rem; border-radius:0 8px 8px 0; color:#1c1917; font-size:0.95rem; line-height:1.6; box-shadow:0 1px 4px rgba(60,45,30,0.04);">
                <p id="announcementSummary" style="margin:0;"></p>
            </div>

            <!-- Liste détaillée des nouveautés -->
            <div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span style="font-size:1.2rem;">🏯</span>
                    <h3 style="margin:0; font-size:1rem; font-weight:800; color:#b45309; text-transform:uppercase; letter-spacing:0.5px;">
                        Détails & Bénéfices Stratégiques
                    </h3>
                </div>

                <div id="announcementFeaturesList" class="d-flex flex-column gap-3">
                    <!-- Injecté en JS -->
                </div>
            </div>

        </div>

        <!-- Pied de la modale avec bouton d'acquittement obligatoire -->
        <div class="modal-footer">
            <div style="font-size:0.82rem; color:var(--text-muted); display:flex; align-items:center; gap:0.4rem; font-weight:600;">
                <span>ℹ️</span> Valider la lecture évite la réapparition de cette annonce.
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="btnAcknowledgeAnnouncement" onclick="acknowledgeCurrentAnnouncement()" class="btn btn-primary" style="padding:0.65rem 1.5rem; font-weight:800; font-size:0.92rem;">
                    <span>✓</span> J'ai pris connaissance de ces nouveautés
                </button>
            </div>
        </div>

    </div>
</div>

<style>
.announcement-feature-card {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem 1.25rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    box-shadow: 0 1px 4px rgba(60, 45, 30, 0.04);
}
.announcement-feature-card:hover {
    border-color: #b91c1c;
    box-shadow: 0 4px 12px rgba(185, 28, 28, 0.1);
    transform: translateY(-2px);
}
.announcement-feature-icon {
    font-size: 1.6rem;
    line-height: 1;
    background: var(--bg-ink);
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    flex-shrink: 0;
}
.announcement-category-pill {
    background: #fef3c7;
    color: #b45309;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #fde68a;
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
                        <strong style="color:#1c1917; font-size:0.95rem; font-weight:800;">${escapeHtml(f.title || '')}</strong>
                    </div>
                    <div style="color:var(--text-muted); font-size:0.875rem; line-height:1.55;">
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

