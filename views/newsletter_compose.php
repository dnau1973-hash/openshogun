<?php
/**
 * Vue : Composition de Missive Impériale (Newsletter) — Page Dédiée Split-Screen
 * Outil réservé au Community Manager et aux Administrateurs d'OpenShogun
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/DevTeamEngine.php';
require_once __DIR__ . '/../core/MailingListEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$currentUserId = (int)Auth::id();
$currentUser = $auth->getCurrentUser();
$isGlobalAdmin = $auth->isAdmin();
$devEngine = new DevTeamEngine();

// Contrôle d'accès strict : Administrateur ou détenteur de la permission 'community.mailing'
if (!$isGlobalAdmin && !$devEngine->hasPermission($currentUserId, 'community.mailing')) {
    header('Location: /?page=dev_team&tab=mailing&forbidden=1');
    exit;
}

$mailingEngine = new MailingListEngine();
$mailingStats = $mailingEngine->getStatistics();

// Chargement éventuel d'un brouillon existant
$campaignId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0);
$draft = null;
if ($campaignId > 0) {
    $draft = $mailingEngine->getCampaign($campaignId);
}

// Pré-remplissage des champs
$initialSubject = $draft['subject'] ?? '';
$initialTarget = $draft['target_group'] ?? 'all_optin';
$initialBody = $draft['body_html'] ?? '';
$initialScheduled = !empty($draft['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($draft['scheduled_at'])) : '';

// Si corps vide, modèle d'introduction immersif par défaut
if (empty($initialBody) && empty($campaignId)) {
    $initialBody = '<h3>⛩️ Proclamation du Conseil des Régents</h3>'
                 . '<p>Honorables Seigneurs et Dames de guerre de l\'Archipel,</p>'
                 . '<p>De grands mouvements s\'annoncent au cœur des provinces impériales. Les forges s\'animent, les greniers s\'emplissent et les armées se rassemblent sous les bannières ancestrales.</p>'
                 . '<blockquote>« La paix se prépare dans le fracas de l\'entraînement et la vigilance des bastions. »</blockquote>'
                 . '<p>Voici les décrets majeurs promulgués pour les lunes à venir :</p>'
                 . '<ul>'
                 . '<li><strong>Nouvelles expéditions provinciales :</strong> De riches oasis et carrières ont été découvertes par nos éclaireurs.</li>'
                 . '<li><strong>Entraînement des légions :</strong> Les casernes et dojos bénéficient d\'une ferveur martiale accrue.</li>'
                 . '<li><strong>Célébration du Shōgunat :</strong> De glorieuses récompenses attendent les Daimyōs les plus valeureux.</li>'
                 . '</ul>'
                 . '<p>Que votre honneur demeure intact et que les esprits vous guident sur le sentier de la gloire féodale.</p>';
}

$currentSenderName = htmlspecialchars($currentUser['username'] ?? 'Chancellerie Impériale');
?>

<!-- Import Quill WYSIWYG Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">

<style>
    /* Personnalisation de l'éditeur WYSIWYG & Toolbar */
    .ql-toolbar.ql-snow {
        border-color: #e6e8ea !important;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        background-color: #f8fafc;
        padding: 10px 12px;
    }
    .ql-container.ql-snow {
        border-color: #e6e8ea !important;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        font-size: 15px;
        line-height: 1.65;
        min-height: 380px;
        background-color: #ffffff;
    }
    .ql-editor {
        min-height: 380px;
        padding: 18px 20px;
    }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 {
        color: #881337;
        font-family: Georgia, serif;
    }
    .ql-editor blockquote {
        border-left: 3px solid #b91c1c;
        padding-left: 14px;
        color: #4b5563;
        font-style: italic;
    }
    /* Conteneur d'aperçu dynamique e-mail */
    .preview-card-body {
        background-color: #1e293b;
        padding: 15px;
        border-radius: 8px;
    }
    .email-preview-frame-wrapper {
        margin: 0 auto;
        transition: width 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        border-radius: 8px;
        overflow: hidden;
        background-color: #f7f3ec;
    }
    .email-preview-frame {
        width: 100%;
        height: 720px;
        border: none;
        display: block;
        background-color: #f7f3ec;
    }
    .client-faux-header {
        background-color: #0f172a;
        color: #cbd5e1;
        padding: 10px 16px;
        font-size: 12px;
        font-family: monospace;
        border-bottom: 1px solid #334155;
    }
</style>

<div class="container-fluid px-3 px-lg-4 my-3">
    
    <!-- En-tête de la Page & Fil d'Ariane -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-arrows mb-2">
                        <li class="breadcrumb-item"><a href="/?page=resources" class="text-secondary"><i class="fa-solid fa-chess-rook me-1"></i>Fief</a></li>
                        <li class="breadcrumb-item"><a href="/?page=dev_team&tab=mailing" class="text-secondary"><i class="fa-solid fa-bullhorn me-1"></i>Studio Dev &bull; Mailing List</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><span class="text-danger fw-bold"><i class="fa-solid fa-pen-nib me-1"></i>Composer une Missive</span></li>
                    </ol>
                </nav>
                <h1 class="page-title font-game text-danger d-flex align-items-center gap-2">
                    <i class="fa-solid fa-feather-pointed me-1"></i>Composer une Missive Impériale
                </h1>
                <p class="text-muted small mb-0">
                    Atelier de rédaction officiel des décrets du Shōgunat — Éditeur riche WYSIWYG &amp; Rendu en direct
                </p>
            </div>
            <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-secondary-lt border d-flex align-items-center gap-1" id="autosave-status">
                    <i class="fa-solid fa-cloud text-secondary"></i>
                    <span id="autosave-text">Brouillon local</span>
                </span>
                <a href="/?page=dev_team&tab=mailing" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="fa-solid fa-arrow-left me-1"></i>Retour à la Mailing List
                </a>
            </div>
        </div>
    </div>

    <!-- Alertes contextuelles -->
    <div id="newsletter-alerts-container"></div>

    <!-- DISPOSITION SPLIT-SCREEN (2 COLONNES) -->
    <div class="row g-3 g-xl-4">
        
        <!-- ══════════════════════════════════════════════════════════════════
             COLONNE GAUCHE : OUTILS, PARAMÈTRES & ÉDITION WYSIWYG
             ══════════════════════════════════════════════════════════════════ -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 border shadow-sm">
                
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2 text-dark font-game fs-5">
                        <i class="fa-solid fa-sliders text-danger me-1"></i>Atelier de Rédaction &amp; Paramètres
                    </h3>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-wand-magic-sparkles me-1 text-warning"></i>Modèles Rapides
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Inspirations Féodales</h6>
                            <a class="dropdown-item" href="#" onclick="applyTemplate('war'); return false;">
                                <i class="fa-solid fa-khanda text-danger me-2"></i>Mobilisation &amp; Siège Féodal
                            </a>
                            <a class="dropdown-item" href="#" onclick="applyTemplate('patch'); return false;">
                                <i class="fa-solid fa-arrows-rotate text-teal me-2"></i>Mise à Jour &amp; Équilibrage du Monde
                            </a>
                            <a class="dropdown-item" href="#" onclick="applyTemplate('peace'); return false;">
                                <i class="fa-solid fa-torii-gate text-primary me-2"></i>Célébration &amp; Trêve Sacrée
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" onclick="clearEditor(); return false;">
                                <i class="fa-solid fa-trash me-2"></i>Effacer le contenu
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-lg-4">
                    <form id="form-newsletter-compose" onsubmit="event.preventDefault();">
                        <input type="hidden" id="newsletter-draft-id" value="<?= (int)($draft['id'] ?? 0) ?>">

                        <!-- 1. Sujet de la missive -->
                        <div class="mb-3">
                            <label class="form-label fw-bold required d-flex justify-content-between">
                                <span><i class="fa-solid fa-heading me-1 text-danger"></i>Sujet de la Missive Impériale</span>
                                <span class="small text-muted" id="subject-counter">0 / 120</span>
                            </label>
                            <input type="text" class="form-control form-control-lg fw-bold" id="newsletter-subject" required 
                                   placeholder="ex: Chroniques du Shōgunat : Mobilisation pour le Siège d'Automne"
                                   value="<?= htmlspecialchars($initialSubject) ?>" maxlength="120"
                                   oninput="updateSubjectCounter(); triggerPreviewUpdate();">
                            <div class="form-hint">
                                L'objet qui apparaîtra dans les boîtes de réception des seigneurs féodaux.
                            </div>
                        </div>

                        <!-- 2. Groupe de destinataires & Date d'envoi (Grille 2 cols) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold required">
                                    <i class="fa-solid fa-users me-1 text-primary"></i>Groupe des Destinataires
                                </label>
                                <select class="form-select" id="newsletter-target-group" required onchange="handleTargetGroupChange(); triggerPreviewUpdate();">
                                    <optgroup label="Diffusion Globale">
                                        <option value="all_optin" <?= $initialTarget === 'all_optin' ? 'selected' : '' ?>>
                                            Tous les Abonnés Newsletter (<?= $mailingStats['newsletter_subscribers'] ?>) [Recommandé RGPD]
                                        </option>
                                        <option value="all_active" <?= $initialTarget === 'all_active' ? 'selected' : '' ?>>
                                            Tous les Daimyōs Actifs (<?= $mailingStats['active_verified'] ?>)
                                        </option>
                                    </optgroup>
                                    <optgroup label="Par Faction Féodale (Abonnés)">
                                        <option value="faction_terran" <?= $initialTarget === 'faction_terran' ? 'selected' : '' ?>>
                                            Clan Tokugawa uniquement
                                        </option>
                                        <option value="faction_vorash" <?= $initialTarget === 'faction_vorash' ? 'selected' : '' ?>>
                                            Clan Oda uniquement
                                        </option>
                                        <option value="faction_aethelis" <?= $initialTarget === 'faction_aethelis' ? 'selected' : '' ?>>
                                            Clan Takeda uniquement
                                        </option>
                                    </optgroup>
                                    <optgroup label="Équipes Internes & Tests">
                                        <option value="dev_team" <?= $initialTarget === 'dev_team' ? 'selected' : '' ?>>
                                            Membres de la Dev Team uniquement
                                        </option>
                                        <option value="test_self" <?= $initialTarget === 'test_self' ? 'selected' : '' ?>>
                                            Test personnel (Expédier sur mon propre e-mail)
                                        </option>
                                    </optgroup>
                                </select>
                                <div class="form-hint text-truncate" id="target-group-hint">
                                    Missive transmise en conformité RGPD aux joueurs abonnés.
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-bold">
                                    <i class="fa-solid fa-clock me-1 text-warning"></i>Planification / Envoi
                                </label>
                                <input type="datetime-local" class="form-control" id="newsletter-scheduled-at" 
                                       value="<?= htmlspecialchars($initialScheduled) ?>">
                                <div class="form-hint">
                                    Laisser vide pour expédition immédiate.
                                </div>
                            </div>
                        </div>

                        <!-- 3. Éditeur WYSIWYG -->
                        <div class="mb-4">
                            <label class="form-label fw-bold required d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-pen-to-square me-1 text-danger"></i>Corps de la Missive (Éditeur Riche)</span>
                                <span class="badge bg-danger-lt border text-danger">Style Parchemin &amp; Or Actif</span>
                            </label>

                            <!-- Conteneur Quill -->
                            <div id="quill-editor-container"><?= $initialBody ?></div>
                            <input type="hidden" id="newsletter-body-input" name="body_html">
                        </div>

                        <!-- 4. Barre d'actions -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2" id="btn-save-draft" onclick="handleSaveDraft()">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer le Brouillon
                                </button>
                                <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" id="btn-send-test" onclick="handleSendTest()">
                                    <i class="fa-solid fa-vial me-1"></i>Envoyer un Test
                                </button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger btn-lg d-flex align-items-center gap-2 px-4 shadow" id="btn-send-campaign" onclick="handleConfirmCampaign()">
                                    <i class="fa-solid fa-paper-plane me-1"></i>Envoyer la Missive
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             COLONNE DROITE : PRÉVISUALISATION DYNAMIQUE EN DIRECT
             ══════════════════════════════════════════════════════════════════ -->
        <div class="col-12 col-xl-6">
            <div class="card h-100 border shadow-sm">
                
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                    <h3 class="card-title mb-0 d-flex align-items-center gap-2 text-dark font-game fs-5">
                        <i class="fa-solid fa-eye text-warning me-1"></i>Prévisualisation en Temps Réel
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" id="btn-view-desktop" onclick="setPreviewDevice('desktop')">
                                <i class="fa-solid fa-desktop me-1"></i>Bureau (640px)
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-view-mobile" onclick="setPreviewDevice('mobile')">
                                <i class="fa-solid fa-mobile-screen me-1"></i>Mobile (380px)
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="triggerPreviewUpdate(true)" title="Forcer le rafraîchissement">
                            <i class="fa-solid fa-rotate me-1"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body preview-card-body">
                    <!-- Faux en-tête client messagerie -->
                    <div class="client-faux-header rounded-top">
                        <div class="d-flex justify-content-between">
                            <span><strong>De :</strong> OpenShogun &bull; Le Shōgunat &lt;chancellerie@openshogun.local&gt;</span>
                            <span class="text-warning">● Rendu HTML Réel</span>
                        </div>
                        <div class="mt-1">
                            <strong>À :</strong> <span id="preview-target-badge" class="badge bg-secondary-lt">Tous les Abonnés (Opt-in)</span>
                        </div>
                        <div class="mt-1 text-truncate">
                            <strong>Objet :</strong> <span id="preview-subject-text" class="text-white fw-bold">Chroniques du Shōgunat</span>
                        </div>
                    </div>

                    <!-- Cadre de l'e-mail (iframe pour isolation totale de style) -->
                    <div class="email-preview-frame-wrapper rounded-bottom" id="preview-frame-wrapper" style="max-width: 640px;">
                        <iframe id="newsletter-preview-iframe" class="email-preview-frame" sandbox="allow-same-origin"></iframe>
                    </div>
                </div>

                <div class="card-footer py-2 px-3 bg-light d-flex justify-content-between align-items-center small text-muted">
                    <span>
                        <i class="fa-solid fa-circle-check text-success me-1"></i>Gabarit Parchemin clair &amp; Or Impérial conforme standards RFC &bull; Zéro fond sombre
                    </span>
                    <span id="preview-updated-at">Mis à jour à l'instant</span>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- MODAL TABLER : CONFIRMATION D'EXPÉDITION D'UNE MISSIVE -->
<div class="modal fade" id="modal-confirm-campaign" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title d-flex align-items-center gap-2 font-game">
                    <i class="fa-solid fa-paper-plane me-1"></i>Expédition de la Missive Impériale
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="avatar avatar-xl bg-danger-lt text-danger mb-2">
                        <i class="fa-solid fa-bullhorn fs-1"></i>
                    </div>
                    <h3 class="font-game text-dark">Confirmer le Décret Impérial</h3>
                    <p class="text-muted small">
                        Cette missive sera expédiée aux seigneurs de guerre de la base sélectionnée.
                    </p>
                </div>

                <div class="bg-light p-3 rounded border mb-3 small">
                    <div class="mb-2"><strong>Objet :</strong> <span id="modal-confirm-subject" class="text-dark"></span></div>
                    <div class="mb-2"><strong>Cible :</strong> <span id="modal-confirm-target" class="badge bg-teal text-white"></span></div>
                    <div><strong>Expéditeur :</strong> <?= $currentSenderName ?> (OpenShogun)</div>
                </div>

                <div class="alert alert-warning small d-flex align-items-center gap-2 mb-0">
                    <i class="fa-solid fa-triangle-exclamation fs-3 text-warning"></i>
                    <div>
                        L'expédition est irréversible. L'e-mail sera instantanément transmis au système SMTP / local pour chaque Daimyō.
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger d-flex align-items-center gap-2" id="btn-modal-execute-send" onclick="executeCampaignSend()">
                    <i class="fa-solid fa-paper-plane me-1"></i>Lancer l'Envoi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Quill & Logique de Prévisualisation Temps Réel -->
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>

<script>
let quill = null;
let previewTimeout = null;
let currentDevice = 'desktop';

// Données d'images pour le template e-mail
const GAME_URL = window.location.origin;
const LOGO_URL = GAME_URL + '/assets/logo_transparent.png';
const SEAL_URL = GAME_URL + '/assets/items/sceau_chrysantheme.jpeg';
const SENDER_NAME = "<?= addslashes($currentSenderName) ?>";

document.addEventListener('DOMContentLoaded', function() {
    initQuillEditor();
    updateSubjectCounter();
    triggerPreviewUpdate(true);
});

/**
 * Initialisation de l'éditeur WYSIWYG Quill
 */
function initQuillEditor() {
    const editorEl = document.getElementById('quill-editor-container');
    if (!editorEl) return;

    if (typeof Quill !== 'undefined') {
        const toolbarOptions = [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': ['#881337', '#b91c1c', '#7f1d1d', '#b45309', '#d97706', '#1e293b', '#475569', '#15803d', '#1d4ed8'] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['blockquote', 'code-block'],
            ['link', 'clean']
        ];

        quill = new Quill('#quill-editor-container', {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            },
            placeholder: "Rédigez ici le décret impérial destiné aux seigneurs de guerre..."
        });

        // Détection de modification en temps réel
        quill.on('text-change', function() {
            document.getElementById('newsletter-body-input').value = quill.root.innerHTML;
            triggerPreviewUpdate();
            markUnsavedState();
        });

        document.getElementById('newsletter-body-input').value = quill.root.innerHTML;
    } else {
        // Fallback si Quill ne se charge pas
        editorEl.innerHTML = `<textarea id="newsletter-body-fallback" class="form-control" rows="12">${editorEl.innerHTML}</textarea>`;
        const textarea = document.getElementById('newsletter-body-fallback');
        textarea.addEventListener('input', function() {
            document.getElementById('newsletter-body-input').value = textarea.value;
            triggerPreviewUpdate();
            markUnsavedState();
        });
        document.getElementById('newsletter-body-input').value = textarea.value;
    }
}

/**
 * Récupère le contenu HTML actuel de l'éditeur
 */
function getEditorHtml() {
    if (quill) {
        return quill.root.innerHTML;
    }
    const fallback = document.getElementById('newsletter-body-fallback');
    return fallback ? fallback.value : '';
}

/**
 * Définit le contenu HTML dans l'éditeur
 */
function setEditorHtml(html) {
    if (quill) {
        quill.root.innerHTML = html;
        document.getElementById('newsletter-body-input').value = html;
    } else {
        const fallback = document.getElementById('newsletter-body-fallback');
        if (fallback) {
            fallback.value = html;
            document.getElementById('newsletter-body-input').value = html;
        }
    }
    triggerPreviewUpdate(true);
}

/**
 * Déclencheur avec debounce pour la mise à jour de la prévisualisation
 */
function triggerPreviewUpdate(immediate = false) {
    clearTimeout(previewTimeout);
    if (immediate) {
        renderLiveEmailPreview();
    } else {
        previewTimeout = setTimeout(renderLiveEmailPreview, 120);
    }
}

/**
 * Génère le rendu HTML complet dans l'iframe d'aperçu
 */
function renderLiveEmailPreview() {
    const subject = document.getElementById('newsletter-subject')?.value.trim() || 'Chroniques du Shōgunat • Décret Féodal';
    const targetGroup = document.getElementById('newsletter-target-group')?.value || 'all_optin';
    const bodyHtml = getEditorHtml() || '<p>Contenu en cours de rédaction...</p>';

    // Mise à jour de l'en-tête client faux
    const fauxSubject = document.getElementById('preview-subject-text');
    if (fauxSubject) fauxSubject.textContent = subject;

    const fauxBadge = document.getElementById('preview-target-badge');
    const targetSelect = document.getElementById('newsletter-target-group');
    if (fauxBadge && targetSelect) {
        fauxBadge.textContent = targetSelect.options[targetSelect.selectedIndex]?.text || targetGroup;
    }

    // Détermination de la faction de test
    let clanName = 'Chancellerie Impériale';
    let clanMoto = 'Allégeance aux Décrets du Shōgunat';
    let clanColor = '#92400e';
    let clanBg = '#fffbeb';
    let clanBorder = '#fde68a';

    if (targetGroup.includes('vorash')) {
        clanName = 'Clan Oda';
        clanMoto = 'Innovation, Artillerie & Conquête';
        clanColor = '#991b1b';
        clanBg = '#fef2f2';
        clanBorder = '#fca5a5';
    } else if (targetGroup.includes('aethelis')) {
        clanName = 'Clan Takeda';
        clanMoto = 'Fūrinkazan & Cavalerie Rouge';
        clanColor = '#b91c1c';
        clanBg = '#fff1f2';
        clanBorder = '#fecdd3';
    } else if (targetGroup.includes('terran')) {
        clanName = 'Clan Tokugawa';
        clanMoto = 'Sagesse, Patience & Fortifications';
        clanColor = '#1e3a8a';
        clanBg = '#eff6ff';
        clanBorder = '#93c5fd';
    }

    // Compilation complète du gabarit e-mail
    const emailHtml = `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${escapeHtml(subject)}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f7f3ec; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .missive-content h1, .missive-content h2, .missive-content h3 { color: #881337; font-family: Georgia, serif; margin: 18px 0 10px 0; }
        .missive-content p { margin: 0 0 14px 0; line-height: 1.68; color: #2d3748; }
        .missive-content a { color: #b91c1c; text-decoration: underline; font-weight: 600; }
        .missive-content blockquote { border-left: 3px solid #b91c1c; margin: 16px 0; padding: 10px 18px; background-color: #faf5ee; color: #4a5568; font-style: italic; }
        .missive-content ul, .missive-content ol { margin: 12px 0 16px 0; padding-left: 24px; color: #2d3748; }
        .missive-content li { margin-bottom: 6px; }
        @media only screen and (max-width: 620px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-cell { padding: 22px 18px !important; }
            .header-cell { padding: 20px 15px 16px 15px !important; }
            .cta-button { width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f3ec;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f7f3ec" style="background-color: #f7f3ec; margin: 0; padding: 25px 10px 35px 10px;">
        <tr>
            <td align="center" valign="top">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" class="email-container" style="max-width: 640px; width: 100%; background-color: #ffffff; border: 1px solid #e7ded0; border-radius: 8px; overflow: hidden; box-shadow: 0 5px 22px rgba(120, 90, 60, 0.08);">
                    
                    <!-- Liseré supérieur Doré & Vermillon -->
                    <tr>
                        <td height="5" style="height: 5px; font-size: 1px; line-height: 1px; background: linear-gradient(90deg, #991b1b 0%, #d4af37 35%, #b45309 65%, #991b1b 100%);"></td>
                    </tr>

                    <!-- En-tête : Logo Officiel Centré & Identité Féodale -->
                    <tr>
                        <td align="center" class="header-cell" style="padding: 28px 25px 20px 25px; background-color: #ffffff; border-bottom: 1px solid #f0e7db; text-align: center;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <a href="${GAME_URL}" target="_blank" style="text-decoration: none; display: inline-block;">
                                            <img src="${LOGO_URL}" alt="OpenShogun" width="220" style="width: 220px; max-width: 85%; height: auto; display: block; margin: 0 auto 12px auto;" border="0">
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <div style="font-family: Georgia, serif; font-size: 13px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px;">
                                            Chroniques du Shōgunat &bull; Missive Impériale
                                        </div>
                                        <div style="display: inline-block; background-color: ${clanBg}; border: 1px solid ${clanBorder}; color: ${clanColor}; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.8px;">
                                            ${escapeHtml(clanName)} &bull; ${escapeHtml(clanMoto)}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Corps de la Missive -->
                    <tr>
                        <td class="content-cell" style="padding: 35px 40px 30px 40px; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.7; color: #2d3748;">
                            
                            <h2 style="margin: 0 0 14px 0; color: #7f1d1d; font-family: Georgia, serif; font-size: 20px; font-weight: bold; line-height: 1.35;">
                                Salutations, Honorable Daimyō,
                            </h2>

                            <!-- Séparateur décoratif doré avec losange -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 12px 0 24px 0;">
                                <tr>
                                    <td style="border-bottom: 1px solid #ebdcc6; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                    <td style="width: 32px; text-align: center; color: #d4af37; font-size: 14px; line-height: 1; padding: 0 4px;">✦</td>
                                    <td style="border-bottom: 1px solid #ebdcc6; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            <div class="missive-content" style="color: #2d3748; font-size: 15px; line-height: 1.7; margin-bottom: 30px;">
                                ${bodyHtml}
                            </div>

                            <!-- Bouton CTA Impérial -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 35px auto 20px auto;">
                                <tr>
                                    <td align="center" style="border-radius: 6px; background-color: #991b1b;">
                                        <a href="${GAME_URL}" target="_blank" class="cta-button" style="display: inline-block; background-color: #991b1b; background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); border: 1px solid #d4af37; color: #ffffff !important; text-decoration: none; padding: 14px 34px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; font-weight: bold; border-radius: 5px; text-transform: uppercase; letter-spacing: 1.2px; box-shadow: 0 4px 14px rgba(185, 28, 28, 0.28);">
                                            Rejoindre le Champ de Bataille &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size: 13px; color: #64748b; font-style: italic; text-align: right; margin-top: 25px; border-top: 1px dashed #e8dfd1; padding-top: 15px;">
                                Transmis sous le sceau de ${escapeHtml(SENDER_NAME)}
                            </div>
                        </td>
                    </tr>

                    <!-- Pied de Page : Bandeau Décoratif Sceau Impérial, Mentions Légales & RGPD -->
                    <tr>
                        <td align="center" style="padding: 28px 25px 26px 25px; background-color: #faf7f2; border-top: 1px solid #eee5d8; text-align: center; color: #5a6a80; font-size: 12px; line-height: 1.65;">
                            
                            <!-- Bandeau décoratif Sceau du Chrysanthème -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 16px auto;">
                                <tr>
                                    <td style="border-bottom: 1px solid #e2d7c5; width: 65px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                    <td style="padding: 0 14px;" align="center">
                                        <img src="${SEAL_URL}" alt="Sceau Impérial du Chrysanthème" width="54" height="54" style="width: 54px; height: 54px; border-radius: 50%; border: 2px solid #d4af37; display: block; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);" border="0">
                                    </td>
                                    <td style="border-bottom: 1px solid #e2d7c5; width: 65px; font-size: 1px; line-height: 1px;">&nbsp;</td>
                                </tr>
                            </table>

                            <div style="font-weight: 700; color: #334155; font-size: 13px; margin-bottom: 6px;">
                                OpenShogun &bull; Chancellerie Impériale &amp; Relations Joueurs
                            </div>
                            <div style="color: #64748b; font-size: 11px; max-width: 500px; margin: 0 auto 12px auto;">
                                Vous recevez cette missive car vous êtes seigneur féodal sur OpenShogun et avez consenti à recevoir nos décrets officiels (RGPD). Vos données restent confidentielles et ne sont jamais cédées.
                            </div>
                            <div style="font-size: 11px; color: #94a3b8;">
                                <a href="${GAME_URL}/?page=poster" style="color: #991b1b; text-decoration: underline; font-weight: 600;">Se désinscrire de la liste de diffusion</a>
                                &bull;
                                <a href="${GAME_URL}/?page=poster" style="color: #64748b; text-decoration: underline;">Paramètres du Daimyō</a>
                                &bull;
                                <a href="${GAME_URL}/?page=docs" style="color: #64748b; text-decoration: underline;">Codex Féodal</a>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>`;

    // Injection dans l'iframe
    const iframe = document.getElementById('newsletter-preview-iframe');
    if (iframe) {
        iframe.srcdoc = emailHtml;
    }

    const timeEl = document.getElementById('preview-updated-at');
    if (timeEl) {
        const now = new Date();
        timeEl.textContent = `Aperçu synchronisé à ${now.toLocaleTimeString()}`;
    }
}

/**
 * Changement de format d'appareil pour la prévisualisation (Bureau / Mobile)
 */
function setPreviewDevice(device) {
    currentDevice = device;
    const wrapper = document.getElementById('preview-frame-wrapper');
    const btnDesktop = document.getElementById('btn-view-desktop');
    const btnMobile = document.getElementById('btn-view-mobile');

    if (device === 'mobile') {
        wrapper.style.maxWidth = '380px';
        btnMobile.classList.add('active');
        btnDesktop.classList.remove('active');
    } else {
        wrapper.style.maxWidth = '640px';
        btnDesktop.classList.add('active');
        btnMobile.classList.remove('active');
    }
}

/**
 * Mise à jour du compteur de caractères du sujet
 */
function updateSubjectCounter() {
    const input = document.getElementById('newsletter-subject');
    const counter = document.getElementById('subject-counter');
    if (input && counter) {
        const len = input.value.length;
        counter.textContent = `${len} / 120`;
        counter.className = len > 100 ? 'small text-warning fw-bold' : 'small text-muted';
    }
}

/**
 * Changement de groupe cible (indice)
 */
function handleTargetGroupChange() {
    const group = document.getElementById('newsletter-target-group')?.value;
    const hint = document.getElementById('target-group-hint');
    if (!hint) return;

    switch (group) {
        case 'all_optin':
            hint.textContent = "Missive transmise uniquement aux joueurs ayant explicitement coché la case d'inscription (RGPD).";
            break;
        case 'all_active':
            hint.textContent = "Transmission à l'ensemble des comptes de joueurs réels confirmés du jeu.";
            break;
        case 'faction_terran':
            hint.textContent = "Ciblage exclusif des seigneurs du Clan Tokugawa (abonnés).";
            break;
        case 'faction_vorash':
            hint.textContent = "Ciblage exclusif des seigneurs du Clan Oda (abonnés).";
            break;
        case 'faction_aethelis':
            hint.textContent = "Ciblage exclusif des seigneurs du Clan Takeda (abonnés).";
            break;
        case 'dev_team':
            hint.textContent = "Diffusion restreinte aux membres possédant un rôle dans la Dev Team.";
            break;
        case 'test_self':
            hint.textContent = "L'e-mail sera envoyé uniquement à votre adresse personnelle de compte.";
            break;
    }
}

/**
 * Indication d'état non sauvegardé
 */
function markUnsavedState() {
    const statusText = document.getElementById('autosave-text');
    const statusBadge = document.getElementById('autosave-status');
    if (statusText) statusText.textContent = "Modifications non enregistrées";
    if (statusBadge) {
        statusBadge.className = "badge bg-warning-lt border text-warning d-flex align-items-center gap-1";
    }
}

/**
 * Enregistrement du brouillon
 */
async function handleSaveDraft() {
    const subject = document.getElementById('newsletter-subject')?.value.trim();
    const targetGroup = document.getElementById('newsletter-target-group')?.value;
    const bodyHtml = getEditorHtml();
    const scheduledAt = document.getElementById('newsletter-scheduled-at')?.value;
    const draftId = document.getElementById('newsletter-draft-id')?.value;

    const btn = document.getElementById('btn-save-draft');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Sauvegarde...`;

    try {
        const formData = new FormData();
        formData.append('action', 'save_newsletter_draft');
        formData.append('subject', subject);
        formData.append('target_group', targetGroup);
        formData.append('body_html', bodyHtml);
        formData.append('scheduled_at', scheduledAt);
        if (draftId && parseInt(draftId) > 0) {
            formData.append('draft_id', draftId);
        }

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            if (data.draft_id) {
                document.getElementById('newsletter-draft-id').value = data.draft_id;
            }
            const statusText = document.getElementById('autosave-text');
            const statusBadge = document.getElementById('autosave-status');
            if (statusText) statusText.textContent = "Brouillon sauvegardé (" + new Date().toLocaleTimeString() + ")";
            if (statusBadge) {
                statusBadge.className = "badge bg-success-lt border text-success d-flex align-items-center gap-1";
            }
            showNotification(data.message, 'success');
        } else {
            showNotification(data.error || "Erreur de sauvegarde du brouillon.", 'danger');
        }
    } catch (err) {
        showNotification("Erreur réseau : impossible de joindre le serveur.", 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

/**
 * Envoi d'un e-mail de test personnel
 */
async function handleSendTest() {
    const subject = document.getElementById('newsletter-subject')?.value.trim();
    const bodyHtml = getEditorHtml();

    if (!subject || !bodyHtml) {
        showNotification("Veuillez renseigner au moins un sujet et du contenu pour le test.", 'warning');
        return;
    }

    const btn = document.getElementById('btn-send-test');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Expédition test...`;

    try {
        const formData = new FormData();
        formData.append('action', 'send_test_newsletter');
        formData.append('subject', subject);
        formData.append('body_html', bodyHtml);

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.error || "Échec de l'envoi de l'e-mail de test.", 'danger');
        }
    } catch (err) {
        showNotification("Erreur réseau lors de l'envoi du test.", 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

/**
 * Ouvre la modale de confirmation d'expédition globale
 */
function handleConfirmCampaign() {
    const subject = document.getElementById('newsletter-subject')?.value.trim();
    const bodyHtml = getEditorHtml();
    const targetGroup = document.getElementById('newsletter-target-group')?.value;

    if (!subject) {
        showNotification("Veuillez renseigner le sujet de la missive avant l'envoi.", 'warning');
        document.getElementById('newsletter-subject')?.focus();
        return;
    }
    if (!bodyHtml || bodyHtml === '<p><br></p>') {
        showNotification("Le corps de la missive ne peut pas être vide.", 'warning');
        return;
    }

    const targetSelect = document.getElementById('newsletter-target-group');
    const targetLabel = targetSelect?.options[targetSelect.selectedIndex]?.text || targetGroup;

    document.getElementById('modal-confirm-subject').textContent = subject;
    document.getElementById('modal-confirm-target').textContent = targetLabel;

    const modal = new bootstrap.Modal(document.getElementById('modal-confirm-campaign'));
    modal.show();
}

/**
 * Exécution finale de l'envoi de la campagne
 */
async function executeCampaignSend() {
    const subject = document.getElementById('newsletter-subject')?.value.trim();
    const targetGroup = document.getElementById('newsletter-target-group')?.value;
    const bodyHtml = getEditorHtml();
    const draftId = document.getElementById('newsletter-draft-id')?.value;

    const btn = document.getElementById('btn-modal-execute-send');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Expédition en cours...`;

    try {
        const formData = new FormData();
        formData.append('action', 'send_newsletter_campaign');
        formData.append('subject', subject);
        formData.append('target_group', targetGroup);
        formData.append('body_html', bodyHtml);
        if (draftId && parseInt(draftId) > 0) {
            formData.append('draft_id', draftId);
        }

        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        // Fermer la modale
        const modalEl = document.getElementById('modal-confirm-campaign');
        bootstrap.Modal.getInstance(modalEl)?.hide();

        if (data.success) {
            showNotification(`Succès : ${data.message} (+35 XP Forge accordés)`, 'success');
            setTimeout(() => {
                window.location.href = '/?page=dev_team&tab=mailing&sent=1';
            }, 1500);
        } else {
            showNotification(data.error || "Échec lors de l'expédition de la missive.", 'danger');
        }
    } catch (err) {
        showNotification("Erreur de transmission : " + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

/**
 * Modèles pré-conçus
 */
function applyTemplate(type) {
    if (type === 'war') {
        document.getElementById('newsletter-subject').value = "⚔️ Mobilisation Générale : Siège des Bastions Féodaux";
        setEditorHtml(`<h3>⛩️ Appel aux Armes du Shōgunat</h3>
<p>Daimyōs de l'Empire,</p>
<p>L'heure de gloire a sonné. Nos forteresses frontalières signalent les premiers tambours de guerre. Le Conseil militaire ordonne la mobilisation générale de toutes les légions provinciales.</p>
<blockquote>« Dans la tempête d'acier, seule la discipline des seigneurs forge la victoire. »</blockquote>
<p><strong>Directives stratégiques :</strong></p>
<ul>
<li><strong>Armement et chantiers :</strong> Accélération de la production de cavalerie et d'arquebuses.</li>
<li><strong>Ravitaillement :</strong> Remplissage prioritaire des réserves de riz et meuneries.</li>
<li><strong>Alliances :</strong> Convocations d'urgence dans les salons diplomatiques.</li>
</ul>
<p>Que chaque guerrier rejoigne son poste de combat avant le crépuscule.</p>`);
    } else if (type === 'patch') {
        document.getElementById('newsletter-subject').value = "📜 Chroniques du Monde : Mises à Jour & Équilibrages Féodaux";
        setEditorHtml(`<h3>⚙️ Décret de Réforme du Shōgunat</h3>
<p>Salutations seigneurs et bâtisseurs de fiefs,</p>
<p>Les intendants et architectes de la Cour impériale ont achevé la nouvelle phase d'arpentage et de rénovation des provinces. Voici les évolutions déployées ce jour :</p>
<ul>
<li><strong>Harmonisation cartographique :</strong> Refonte complète des routes commerciales et exploration fluide.</li>
<li><strong>Iconographie impériale :</strong> Déploiement des armoiries vectorielles sur l'ensemble des parchemins et rapports.</li>
<li><strong>Économie castrale :</strong> Rééquilibrage des rendements pour les rizières et les carrières de pierre.</li>
</ul>
<p>Découvrez tous les détails dans vos parchemins impériaux dès votre prochaine connexion.</p>`);
    } else if (type === 'peace') {
        document.getElementById('newsletter-subject').value = "🏮 Trêve Céleste & Célébration des Récoltes Impériales";
        setEditorHtml(`<h3>🌸 Bénédiction des Kamis & Célébration</h3>
<p>Honorables Daimyōs,</p>
<p>À l'occasion du grand festival des récoltes d'automne, le Shōgun décrète une trêve sacrée sur l'ensemble de l'Archipel. Durant cette période, les armes se taisent et les portes des châteaux s'ouvrent à la concorde et à la fête.</p>
<blockquote>« Même le tigre de guerre s'incline devant la générosité de la terre féodale. »</blockquote>
<p>Profitez de ces jours fastes pour fortifier vos remparts, instruire vos samouraïs dans les dojos et festoyer avec vos alliés.</p>`);
    }
    updateSubjectCounter();
    showNotification("Modèle appliqué avec succès dans l'éditeur !", 'info');
}

function clearEditor() {
    if (confirm("Effacer tout le contenu rédigé dans l'éditeur ?")) {
        setEditorHtml('<p><br></p>');
    }
}

/**
 * Affichage des notifications / toasts
 */
function showNotification(msg, type = 'info') {
    const container = document.getElementById('newsletter-alerts-container');
    if (!container) return;

    const icon = type === 'success' ? 'fa-circle-check' : (type === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2 mb-3 shadow-sm`;
    alert.innerHTML = `
        <i class="fa-solid ${icon} fs-4"></i>
        <div class="flex-grow-1">${msg}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    `;
    container.appendChild(alert);
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 250);
    }, 4500);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>
