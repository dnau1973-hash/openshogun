<?php
/**
 * Vue Joueur : Modification d'une Demande de Support (Bug / Suggestion)
 * Accès : auteur authentifié + statut ticket = 'pending' uniquement.
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SupportEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$user   = $auth->getCurrentUser();
$userId = (int)$user['id'];

// Identifiant du ticket à modifier (passé en GET)
$ticketId = (int)($_GET['ticket_id'] ?? 0);
if ($ticketId <= 0) {
    header('Location: ?page=support&tab=history');
    exit;
}

// Pré-chargement serveur : vérifie l'accès AVANT d'afficher la page
// (évite d'afficher un formulaire vide que le JS devrait valider après)
$supportEngine = new SupportEngine();
try {
    $ticket = $supportEngine->getTicketForEdit($ticketId, $userId);
} catch (RuntimeException $e) {
    $accessError = $e->getMessage();
    $ticket      = null;
}

// Jeton CSRF pour le formulaire (régénéré si absent de session)
$csrfToken = Auth::csrfToken();
?>

<!-- =========================================================
     IMPORT QUILL.JS (CDN)
     ========================================================= -->
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<div class="edit-ticket-container" style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">

    <!-- En-tête -->
    <div class="card" style="margin-bottom: 1.5rem; border-top: 5px solid #0891b2; background: var(--bg-surface, #fdfbf7);">
        <div style="padding: 1.5rem 2rem; background: linear-gradient(135deg, rgba(8,145,178,0.07) 0%, rgba(253,251,247,0.98) 80%);">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem;">
                <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
                             color: #0891b2; background: rgba(8,145,178,0.1); padding: 2px 8px; border-radius: 4px;">
                    🏛️ Assistance & Boîte à Idées
                </span>
                <span style="color: var(--text-muted); font-size: 0.8rem;">• Modification de Demande #<?= $ticketId ?></span>
            </div>
            <h1 style="font-size: 1.6rem; margin: 0 0 0.5rem 0; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>✏️</span> Modifier votre Demande
            </h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                Vous pouvez modifier votre signalement ou suggestion tant qu'il n'a pas encore été pris en charge par l'administration.
            </p>
            <div style="margin-top: 1rem;">
                <a href="?page=support&tab=history" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem;">
                    ← Retour au suivi de mes demandes
                </a>
            </div>
        </div>
    </div>

    <?php if (!$ticket): ?>
    <!-- ===================== ACCÈS INTERDIT ===================== -->
    <div class="card" style="padding: 2.5rem; text-align: center; background: var(--bg-surface, #fdfbf7);
                              border: 1px solid #fee2e2; border-radius: 12px;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
        <h2 style="margin: 0 0 0.75rem 0; color: #dc2626;">Modification impossible</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 520px; margin: 0 auto 1.5rem auto;">
            <?= htmlspecialchars($accessError ?? "Ce ticket ne peut pas être modifié.") ?>
        </p>
        <a href="?page=support&tab=history" class="btn btn-primary">← Retour à mes demandes</a>
    </div>

    <?php else: ?>
    <!-- ===================== FORMULAIRE D'ÉDITION ===================== -->
    <div class="card" style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color);
                              border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">

        <!-- Message de feedback (succès / erreur) — masqué par défaut -->
        <div id="editTicketFeedback" role="alert" style="display: none; padding: 0.9rem 1.25rem; border-radius: 8px;
             margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
        </div>

        <form id="editTicketForm" onsubmit="submitEditTicket(event)" novalidate>

            <!-- Jeton CSRF caché -->
            <input type="hidden" name="csrf_token" id="editCsrfToken" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="ticket_id"  value="<?= $ticketId ?>">

            <!-- ─── 1. TYPE ─── -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                    1. Nature de la demande :
                </label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php foreach (['bug' => ['🪲', 'Dysfonctionnement'], 'suggestion' => ['💡', 'Suggestion']] as $val => [$ico, $lbl]): ?>
                    <label id="card-type-<?= $val ?>" style="flex: 1; min-width: 180px; border: 2px solid var(--border-color);
                           border-radius: 10px; padding: 1rem; cursor: pointer; transition: all .15s;
                           background: var(--bg-ink, #ede5d5);
                           <?= ($ticket['type'] === $val) ? "border-color: #0891b2; background: rgba(8,145,178,0.07);" : '' ?>">
                        <input type="radio" name="type" value="<?= $val ?>" id="type-<?= $val ?>"
                               <?= ($ticket['type'] === $val) ? 'checked' : '' ?>
                               onchange="toggleTypeFields()"
                               style="position: absolute; opacity: 0; width: 0;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.4rem;"><?= $ico ?></div>
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);"><?= $lbl ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ─── 2. CATÉGORIE ─── -->
            <div style="margin-bottom: 1.5rem;">
                <label for="editCategory" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                    2. Catégorie concernée :
                </label>
                <select id="editCategory" name="category"
                        style="width: 100%; padding: 0.7rem 1rem; border-radius: 8px; border: 2px solid var(--border-color);
                               background: var(--bg-surface, #fdfbf7); color: var(--text-main); font-size: 0.9rem; outline: none;">
                    <?php foreach (SupportEngine::CATEGORIES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($ticket['category'] === $key) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ─── 3. SÉVÉRITÉ (bugs seulement) ─── -->
            <div id="edit-group-severity" style="margin-bottom: 1.5rem; <?= ($ticket['type'] !== 'bug') ? 'display:none;' : '' ?>">
                <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                    3. Sévérité du dysfonctionnement :
                </label>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <?php foreach ([
                        'low'      => ['🟢', 'Mineur',   '#16a34a'],
                        'medium'   => ['🟡', 'Modéré',   '#ca8a04'],
                        'high'     => ['🟠', 'Important','#ea580c'],
                        'critical' => ['🔴', 'Critique', '#dc2626'],
                    ] as $sval => [$sico, $slbl, $scol]): ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 1rem;
                                  border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer;
                                  font-size: 0.85rem; font-weight: 600; background: var(--bg-ink, #ede5d5);">
                        <input type="radio" name="severity" value="<?= $sval ?>"
                               <?= ($ticket['severity'] === $sval) ? 'checked' : '' ?>>
                        <span><?= $sico ?> <?= $slbl ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ─── 4. TITRE ─── -->
            <div style="margin-bottom: 1.5rem;">
                <label for="editTitle" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                    4. Titre <span style="color: var(--text-muted); font-weight: 400;">(4 – 150 caractères)</span> :
                </label>
                <input type="text" id="editTitle" name="title"
                       value="<?= htmlspecialchars($ticket['title']) ?>"
                       maxlength="150" required
                       style="width: 100%; padding: 0.7rem 1rem; border-radius: 8px; border: 2px solid var(--border-color);
                              background: var(--bg-surface, #fdfbf7); color: var(--text-main); font-size: 0.95rem;
                              outline: none; box-sizing: border-box;">
            </div>

            <!-- ─── 5. DESCRIPTION (Quill WYSIWYG) ─── -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                    5. Description détaillée :
                </label>
                <!-- Conteneur de l'éditeur Quill -->
                <div id="editDescriptionEditor"
                     style="min-height: 200px; border: 2px solid var(--border-color); border-radius: 0 0 8px 8px;
                            background: #fff; font-size: 0.92rem; line-height: 1.6; color: #1e293b;"></div>
                <!-- Champ caché synchronisé avec Quill avant envoi -->
                <input type="hidden" name="description" id="editDescriptionHidden">
            </div>

            <!-- ─── BOUTON DE SOUMISSION ─── -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem; flex-wrap: wrap;">
                <a href="?page=support&tab=history" class="btn btn-secondary">Annuler</a>
                <button type="submit" id="editTicketSubmitBtn" class="btn btn-primary"
                        style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700;">
                    <span>💾</span> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

</div>

<?php if ($ticket): ?>
<script>
/* =====================================================================
   INITIALISATION QUILL
   ===================================================================== */
const editQuill = new Quill('#editDescriptionEditor', {
    theme: 'snow',
    placeholder: 'Décrivez le dysfonctionnement ou la suggestion en détail…',
    modules: {
        toolbar: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'code-block'],
            ['clean']
        ]
    }
});

// Pré-remplissage du contenu existant (HTML brut stocké en base)
// Quill attend du HTML via pasteHTML / dangerouslyPasteHTML
editQuill.root.innerHTML = <?= json_encode($ticket['description'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

/* =====================================================================
   TOGGLE AFFICHAGE SÉVÉRITÉ
   ===================================================================== */
function toggleTypeFields() {
    const isBug = document.querySelector('input[name="type"]:checked')?.value === 'bug';
    document.getElementById('edit-group-severity').style.display = isBug ? 'block' : 'none';

    ['bug', 'suggestion'].forEach(v => {
        const card = document.getElementById('card-type-' + v);
        if (!card) return;
        if ((v === 'bug') === isBug) {
            card.style.borderColor = '#0891b2';
            card.style.background  = 'rgba(8,145,178,0.07)';
        } else {
            card.style.borderColor = 'var(--border-color)';
            card.style.background  = 'var(--bg-ink, #ede5d5)';
        }
    });
}

/* =====================================================================
   SOUMISSION ASYNCHRONE VIA FETCH
   ===================================================================== */
async function submitEditTicket(event) {
    event.preventDefault();

    const btn      = document.getElementById('editTicketSubmitBtn');
    const feedback = document.getElementById('editTicketFeedback');

    // Masquer l'éventuel feedback précédent
    feedback.style.display = 'none';

    // Synchroniser le champ caché avec le contenu Quill
    const htmlContent = editQuill.root.innerHTML;
    document.getElementById('editDescriptionHidden').value = htmlContent;

    // Validation minimale côté client (texte brut sans balises)
    const plainText = editQuill.getText().trim();
    if (plainText.length < 10) {
        showFeedback('error', "⚠️ La description doit comporter au moins 10 caractères.");
        return;
    }
    const title = document.getElementById('editTitle').value.trim();
    if (title.length < 4) {
        showFeedback('error', "⚠️ Le titre doit comporter au moins 4 caractères.");
        return;
    }

    // Désactivation du bouton pendant la requête
    btn.disabled    = true;
    btn.textContent = '⏳ Enregistrement…';

    const formData = new FormData(document.getElementById('editTicketForm'));
    formData.set('action', 'update_ticket');

    try {
        const res  = await fetch('/api/support.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showFeedback('success', "✅ " + (data.message || "Demande modifiée avec succès !"));
            // Redirection différée vers l'historique
            setTimeout(() => {
                window.location.href = '?page=support&tab=history';
            }, 1800);
        } else {
            showFeedback('error', "❌ " + (data.error || "Une erreur est survenue."));
            btn.disabled    = false;
            btn.innerHTML   = '<span>💾</span> Enregistrer les modifications';
        }
    } catch (err) {
        showFeedback('error', "❌ Erreur réseau. Vérifiez votre connexion et réessayez.");
        btn.disabled  = false;
        btn.innerHTML = '<span>💾</span> Enregistrer les modifications';
    }
}

/**
 * Affiche un bandeau de feedback inline (sans rechargement de page).
 * @param {'success'|'error'} type
 * @param {string}            message
 */
function showFeedback(type, message) {
    const fb = document.getElementById('editTicketFeedback');
    if (type === 'success') {
        fb.style.cssText += '; background: rgba(22,163,74,0.1); border: 1px solid #16a34a; color: #15803d;';
    } else {
        fb.style.cssText += '; background: rgba(220,38,38,0.08); border: 1px solid #dc2626; color: #b91c1c;';
    }
    fb.textContent = message;
    fb.style.display = 'flex';
    fb.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
<?php endif; ?>
