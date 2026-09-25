<?php
/**
 * Album Tabler.io du Grimoire des Prompts d'OpenShogun
 * 100% Conforme au Design System Tabler (Thème clair, cartes modulaires, recherche & filtres réactifs)
 */

$promptsCatalog = require __DIR__ . '/grimoire_prompts_data.php';

// Statistiques par catégorie
$categoryCounts = [
    'all' => count($promptsCatalog),
    'panoramas' => 0,
    'terroirs' => 0,
    'buildings' => 0,
    'tiles' => 0,
    'infantry' => 0,
    'cavalry' => 0,
    'animals' => 0,
    'siege' => 0,
    'hero' => 0,
    'castles' => 0,
];

foreach ($promptsCatalog as $p) {
    $cat = $p['category'] ?? 'other';
    if (isset($categoryCounts[$cat])) {
        $categoryCounts[$cat]++;
    }
}
?>

<style>
/* Micro-interactions conformes au Design System Tabler */
.grimoire-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.grimoire-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
}
.grimoire-card-media {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    background: #f8fafc;
}
.grimoire-card-media img {
    transition: transform 0.3s ease;
}
.grimoire-card-media:hover img {
    transform: scale(1.04);
}
.grimoire-card-media.is-tile-bg {
    background: repeating-conic-gradient(#f1f5f9 0% 25%, #ffffff 0% 50%) 50% / 16px 16px !important;
    display: flex;
    align-items: center;
    justify-content: center;
}
.grimoire-card-media.is-tile-bg img {
    object-fit: contain !important;
    padding: 1rem;
    max-height: 88%;
    width: auto;
}
.grimoire-zoom-hint {
    opacity: 0;
    transform: translateY(4px);
    transition: all 0.2s ease;
}
.grimoire-card-media:hover .grimoire-zoom-hint {
    opacity: 0.9;
    transform: translateY(0);
}
.grimoire-file-pill {
    transition: background-color 0.15s ease, border-color 0.15s ease;
}
.grimoire-file-pill:hover {
    background-color: #f1f5f9 !important;
    border-color: #cbd5e1 !important;
}
.grimoire-pill-btn {
    cursor: pointer;
    transition: all 0.15s ease;
}
.grimoire-pill-btn.active {
    background-color: var(--tblr-primary, #0054a6) !important;
    color: #ffffff !important;
    border-color: var(--tblr-primary, #0054a6) !important;
}
.grimoire-pill-btn.active .badge {
    background-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}
/* Modale plein écran HD */
#grimoireModalTabler {
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
}
</style>

<div class="grimoire-album-section">

    <!-- 1. EN-TÊTE HERO AUX NORMES TABLER (CARD ÉPURÉE) -->
    <div class="card bg-white border shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-primary-lt text-primary fw-bold text-uppercase mb-2">
                        📜 Grimoire d'Art &bull; Prompt Engineering Sengoku Jidai
                    </span>
                    <h2 class="card-title fs-2 text-dark d-flex align-items-center gap-2 mb-2">
                        <span>🎨</span> Le Grimoire des 80 Prompts d'OpenShogun
                    </h2>
                    <p class="text-secondary small mb-3" style="line-height: 1.6; max-width: 820px;">
                        Explorez l'intégralité des <strong>80 requêtes génératrices d'art</strong> ayant façonné les panoramas, héros, forteresses, engins et armées féodales du jeu. 
                        Copiez les prompts en un clic, inspectez les fichiers cibles et découvrez les secrets de direction artistique (Ukiyo-e, précision vectorielle 2.5D et palette d'époque).
                    </p>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <span class="badge bg-teal-lt p-2">
                            <span class="me-1">📊</span> 80 Assets Authentiques Actifs
                        </span>
                        <span class="badge bg-yellow-lt p-2">
                            <span class="me-1">🛡️</span> 100% Prompts Vérifiés
                        </span>
                        <span class="badge bg-azure-lt p-2">
                            <span class="me-1">🖼️</span> Style Ukiyo-e &amp; 2.5D Vectoriel Pur
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end">
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 shadow-sm" onclick="exportGrimoireMarkdown()">
                        <span>📋</span> Copier Tout le Grimoire (Markdown)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BARRE DE CONTRÔLE TABLER : RECHERCHE INSTANTANÉE & FILTRES RAPIDES -->
    <div class="card bg-white border shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center mb-3">
                <!-- Champ de Recherche Tabler avec Icône -->
                <div class="col-md-8 col-lg-9">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                        </span>
                        <input type="text" 
                               id="grimoireSearchInput" 
                               class="form-control" 
                               placeholder="Rechercher par nom (ex: Bûcheron, Bélier, Takeda), mot-clé anglais, clan ou fichier..."
                               autocomplete="off">
                    </div>
                </div>
                <!-- Compteur de résultats dynamique -->
                <div class="col-md-4 col-lg-3 text-md-end">
                    <span class="badge bg-light text-secondary border p-2 w-100 justify-content-center justify-content-md-end d-flex align-items-center gap-1">
                        <span>Affichage :</span>
                        <strong id="countVisible" class="text-primary font-monospace"><?= count($promptsCatalog) ?></strong>
                        <span class="text-muted">/ <?= count($promptsCatalog) ?></span>
                    </span>
                </div>
            </div>

            <!-- Pilules de Catégories (Nav-Pills Tabler) -->
            <div class="nav nav-pills flex-wrap gap-1 align-items-center m-0 p-0" id="grimoirePillsNav">
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn active py-1 px-2 border" data-filter="all" onclick="filterGrimoireCategory('all', this)">
                    🌐 Tous <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['all'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="panoramas" onclick="filterGrimoireCategory('panoramas', this)">
                    🌄 Panoramas <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['panoramas'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="terroirs" onclick="filterGrimoireCategory('terroirs', this)">
                    🌾 Ressources <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['terroirs'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="buildings" onclick="filterGrimoireCategory('buildings', this)">
                    🏯 Cité Castrale <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['buildings'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="tiles" onclick="filterGrimoireCategory('tiles', this)">
                    🀄 Tuiles 2.5D <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['tiles'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="infantry" onclick="filterGrimoireCategory('infantry', this)">
                    ⚔️ Infanterie <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['infantry'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="cavalry" onclick="filterGrimoireCategory('cavalry', this)">
                    🐎 Cavalerie <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['cavalry'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="animals" onclick="filterGrimoireCategory('animals', this)">
                    🐗 Bêtes Oasis <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['animals'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="siege" onclick="filterGrimoireCategory('siege', this)">
                    💥 Siège <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['siege'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="hero" onclick="filterGrimoireCategory('hero', this)">
                    👑 Héros <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['hero'] ?></span>
                </button>
                <button type="button" class="nav-link btn btn-sm grimoire-pill-btn py-1 px-2 border" data-filter="castles" onclick="filterGrimoireCategory('castles', this)">
                    🏯 12 Châteaux <span class="badge bg-secondary-lt ms-1"><?= $categoryCounts['castles'] ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. GRILLE DE CARTES TABLER (ROW ROW-CARDS) -->
    <div class="row row-cards g-3" id="grimoireCardsGrid">
        <?php foreach ($promptsCatalog as $idx => $p): 
            $imgDiskPath = __DIR__ . '/../../public/assets/' . $p['file'];
            $imgExists = file_exists($imgDiskPath);
            $imgUrl = '/public/assets/' . $p['file'] . ($imgExists ? '?v=' . filemtime($imgDiskPath) : '');
            
            // Chaîne de recherche normalisée
            $searchIndex = strtolower($p['title'] . ' ' . $p['subtitle'] . ' ' . $p['display_path'] . ' ' . $p['clan'] . ' ' . $p['prompt'] . ' ' . $p['translation'] . ' ' . $p['category']);
            $isTile = ($p['category'] === 'tiles');
            $cardId = 'card-prompt-' . $p['id'];
        ?>
            <div class="col-sm-6 col-lg-4 col-xl-3 prompt-album-col" 
                 data-category="<?= htmlspecialchars($p['category']) ?>" 
                 data-search="<?= htmlspecialchars($searchIndex) ?>"
                 id="<?= $cardId ?>">

                <div class="card card-sm bg-white border shadow-sm h-100 d-flex flex-column grimoire-card">
                    <!-- Zone Aperçu Visuel / Média Top -->
                    <div class="grimoire-card-media <?= $isTile ? 'is-tile-bg' : '' ?> border-bottom" 
                         style="aspect-ratio: 16 / 10;"
                         onclick="openGrimoireModalTabler('<?= $imgUrl ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['display_path'])) ?>', '<?= $cardId ?>')"
                         title="Cliquer pour admirer en grand format">
                        <img src="<?= $imgUrl ?>" 
                             class="w-100 h-100 object-fit-cover" 
                             alt="<?= htmlspecialchars($p['title']) ?>" 
                             loading="lazy">
                        
                        <!-- Badges en Overlay -->
                        <div class="position-absolute top-0 start-0 end-0 p-2 d-flex justify-content-between align-items-center pointer-events-none" style="z-index: 2;">
                            <span class="badge bg-dark text-white opacity-90 shadow-sm small">
                                <?= htmlspecialchars($p['category_label']) ?>
                            </span>
                            <span class="badge bg-azure text-white opacity-90 shadow-sm font-monospace small">
                                <?= htmlspecialchars($p['format']) ?>
                            </span>
                        </div>

                        <!-- Indicateur de zoom -->
                        <div class="position-absolute bottom-0 end-0 m-2 badge bg-dark text-white opacity-75 grimoire-zoom-hint pointer-events-none" style="z-index: 2;">
                            🔍 Agrandir
                        </div>

                        <!-- Bouton Transparence IA (« ? ») -->
                        <button type="button" 
                                class="ai-prompt-badge position-absolute bottom-0 start-0 m-2" 
                                data-ai-title="<?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ai-img="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                                data-ai-prompt="<?= htmlspecialchars($p['prompt'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ai-translation="<?= htmlspecialchars($p['translation'], ENT_QUOTES, 'UTF-8') ?>"
                                title="Détails du prompt & transparence IA"
                                style="z-index: 3;"
                                onclick="event.stopPropagation();">
                            <span class="ai-badge-icon">?</span>
                        </button>
                    </div>

                    <!-- Corps de la Carte Tabler -->
                    <div class="card-body p-3 d-flex flex-column flex-fill">
                        <!-- En-tête : Titre & Badge Clan -->
                        <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                            <h4 class="card-title fw-bold text-dark m-0 lh-sm" style="font-size: 0.95rem;">
                                <?= htmlspecialchars($p['title']) ?>
                            </h4>
                            <span class="badge bg-purple-lt small text-nowrap">
                                <?= htmlspecialchars($p['clan']) ?>
                            </span>
                        </div>

                        <div class="text-secondary small mb-2 lh-sm" style="font-size: 0.78rem;">
                            <?= htmlspecialchars($p['subtitle']) ?>
                        </div>

                        <!-- Chemin de Fichier Cliquable Tabler -->
                        <div class="grimoire-file-pill bg-body-tertiary border rounded p-1 px-2 font-monospace small text-teal d-flex justify-content-between align-items-center cursor-pointer mb-2" 
                             onclick="copyTextToClipboard('<?= htmlspecialchars(addslashes($p['display_path'])) ?>', this)" 
                             title="Cliquer pour copier le chemin du fichier">
                            <div class="text-truncate me-1 small">
                                📁 <code><?= htmlspecialchars($p['display_path']) ?></code>
                            </div>
                            <span class="text-secondary opacity-75 small">📋</span>
                        </div>

                        <!-- Bloc Prompt Anglais Tabler -->
                        <div class="bg-light border rounded p-2 mb-2 flex-fill d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary-lt font-monospace text-uppercase" style="font-size: 0.65rem;">
                                    🇬🇧 Prompt IA Transmis
                                </span>
                                <button type="button" 
                                        class="btn btn-xs btn-outline-primary py-0 px-2" 
                                        onclick="copyPromptCardText(this)" 
                                        style="font-size: 0.68rem;"
                                        title="Copier le prompt anglais">
                                    📋 Copier
                                </button>
                            </div>
                            <p class="text-secondary font-monospace small m-0 lh-sm grimoire-prompt-text" style="font-size: 0.74rem; max-height: 80px; overflow-y: auto;">
                                <?= htmlspecialchars($p['prompt']) ?>
                            </p>
                        </div>

                        <!-- Bloc Traduction Française Tabler -->
                        <div class="border-start border-3 border-warning bg-warning-lt p-2 rounded-end mt-auto">
                            <div class="text-warning-emphasis fw-bold mb-1" style="font-size: 0.68rem;">
                                🇫🇷 Traduction &amp; Contexte Pédagogique
                            </div>
                            <p class="text-secondary small m-0 fst-italic lh-sm grimoire-translation-text" style="font-size: 0.73rem;">
                                <?= htmlspecialchars($p['translation']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Pied de Carte Tabler (Actions) -->
                    <div class="card-footer bg-light p-2 d-flex justify-content-between align-items-center border-top">
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-outline-primary" 
                                    onclick="copyPromptCardText(this)"
                                    title="Copier le prompt anglais">
                                <span>📋</span> Copier
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-secondary" 
                                    onclick="openGrimoireModalTabler('<?= $imgUrl ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['display_path'])) ?>', '<?= $cardId ?>')"
                                    title="Admirer en haute résolution">
                                <span>🔍</span> Voir HD
                            </button>
                        </div>
                        <span class="text-muted small font-monospace" style="font-size: 0.72rem;">
                            <?= htmlspecialchars($p['resolution']) ?>
                        </span>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 4. ÉTAT AUCUN RÉSULTAT TABLER (EMPTY STATE) -->
    <div id="grimoireNoResults" class="empty border rounded bg-white p-5 my-4 shadow-sm" style="display: none;">
        <div class="empty-icon fs-1">🔍</div>
        <p class="empty-title fs-2 text-dark">Aucun prompt ne correspond à votre recherche</p>
        <p class="empty-subtitle text-secondary">
            Modifiez votre terme de recherche ou réinitialisez les filtres par catégorie.
        </p>
        <div class="empty-action">
            <button type="button" class="btn btn-outline-primary" onclick="clearGrimoireSearch()">
                🔄 Réinitialiser la recherche
            </button>
        </div>
    </div>

</div>

<!-- 5. MODALE TABLER DE PRÉVISUALISATION HD -->
<div class="modal fade" id="grimoireModalTabler" tabindex="-1" style="display: none;" aria-hidden="true" onclick="if(event.target === this) closeGrimoireModalTabler();">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="gmTitleTabler">
                        <!-- Rempli en JS -->
                    </h5>
                    <div id="gmFileTabler" class="text-muted small font-monospace mt-1"></div>
                </div>
                <button type="button" class="btn-close" onclick="closeGrimoireModalTabler()" aria-label="Fermer"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="gmImgTabler" src="" alt="" class="img-fluid rounded border shadow-sm mb-3" style="max-height: 55vh; object-fit: contain;">
                
                <div class="text-start p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-primary-lt fw-bold font-monospace">🇬🇧 Prompt IA Complet</span>
                        <button type="button" class="btn btn-sm btn-primary" id="gmCopyBtnTabler" onclick="copyModalPromptTabler()">
                            📋 Copier le Prompt IA
                        </button>
                    </div>
                    <p id="gmPromptTextTabler" class="font-monospace small text-secondary m-0" style="white-space: pre-wrap; font-size: 0.82rem; line-height: 1.5;"></p>
                </div>
            </div>
            <div class="modal-footer bg-light p-2 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary" onclick="closeGrimoireModalTabler()">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS DU GRIMOIRE TABLER -->
<script>
let currentActiveCategory = 'all';
let modalActivePrompt = '';

// Filtrer par Catégorie
function filterGrimoireCategory(cat, btn) {
    currentActiveCategory = cat;
    
    // Mettre à jour les pilules actives
    document.querySelectorAll('.grimoire-pill-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    applyGrimoireFilters();
}

// Recherche instantanée
const searchInput = document.getElementById('grimoireSearchInput');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        applyGrimoireFilters();
    });
}

function clearGrimoireSearch() {
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    filterGrimoireCategory('all', document.querySelector('.grimoire-pill-btn[data-filter="all"]'));
}

// Appliquer filtres catégorie + recherche
function applyGrimoireFilters() {
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const cols = document.querySelectorAll('.prompt-album-col');
    let visibleCount = 0;

    cols.forEach(col => {
        const cardCat = col.getAttribute('data-category');
        const cardSearch = col.getAttribute('data-search') || '';

        const matchesCat = (currentActiveCategory === 'all' || cardCat === currentActiveCategory);
        const matchesQuery = (query === '' || cardSearch.includes(query));

        if (matchesCat && matchesQuery) {
            col.style.display = 'block';
            visibleCount++;
        } else {
            col.style.display = 'none';
        }
    });

    const countVisibleEl = document.getElementById('countVisible');
    if (countVisibleEl) countVisibleEl.innerText = visibleCount;

    const noResultsEl = document.getElementById('grimoireNoResults');
    if (noResultsEl) {
        noResultsEl.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

// Notification Toast Tabler Unifiée
function triggerGrimoireToast(msg, type = 'success') {
    if (typeof showToast === 'function') {
        showToast(msg, type);
    } else {
        // Fallback discret Tabler Toast
        let container = document.getElementById('tablerToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'tablerToastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '99999';
            document.body.appendChild(container);
        }
        const toastEl = document.createElement('div');
        toastEl.className = 'toast show align-items-center text-white bg-dark border-0 shadow-lg mb-2';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body small fw-semibold">
                    ${msg}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        container.appendChild(toastEl);
        setTimeout(() => {
            if (toastEl.parentNode) toastEl.remove();
        }, 2500);
    }
}

// Copier texte générique (ex: chemin de fichier)
function copyTextToClipboard(text, el = null) {
    navigator.clipboard.writeText(text).then(() => {
        triggerGrimoireToast('Chemin copié : ' + text, 'info');
        if (el) {
            const originalBg = el.style.backgroundColor;
            el.style.backgroundColor = '#dcfce7';
            setTimeout(() => { el.style.backgroundColor = originalBg; }, 400);
        }
    }).catch(err => {
        prompt('Copiez manuellement :', text);
    });
}

// Copier le prompt depuis la carte
function copyPromptCardText(btn) {
    const card = btn.closest('.grimoire-card');
    if (!card) return;
    const promptP = card.querySelector('.grimoire-prompt-text');
    if (!promptP) return;
    const text = promptP.innerText.trim();

    navigator.clipboard.writeText(text).then(() => {
        triggerGrimoireToast('Prompt copié dans le presse-papiers !', 'success');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>✅</span> Copié !';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        setTimeout(() => { 
            btn.innerHTML = originalText; 
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 1500);
    }).catch(err => {
        prompt('Copiez le prompt :', text);
    });
}

// Modale Grand Format Tabler
function openGrimoireModalTabler(imgUrl, title, filePath, cardId) {
    const modal = document.getElementById('grimoireModalTabler');
    const titleEl = document.getElementById('gmTitleTabler');
    const fileEl = document.getElementById('gmFileTabler');
    const imgEl = document.getElementById('gmImgTabler');
    const promptEl = document.getElementById('gmPromptTextTabler');
    
    if (!modal) return;

    if (titleEl) titleEl.innerHTML = `<span>🎨</span> ${title}`;
    if (fileEl) fileEl.innerText = '📁 ' + filePath;
    if (imgEl) imgEl.src = imgUrl;

    const card = document.getElementById(cardId);
    if (card) {
        const pText = card.querySelector('.grimoire-prompt-text');
        modalActivePrompt = pText ? pText.innerText.trim() : '';
        if (promptEl) promptEl.innerText = modalActivePrompt;
    }

    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeGrimoireModalTabler() {
    const modal = document.getElementById('grimoireModalTabler');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
    document.body.classList.remove('modal-open');
}

function copyModalPromptTabler() {
    if (!modalActivePrompt) return;
    navigator.clipboard.writeText(modalActivePrompt).then(() => {
        triggerGrimoireToast('Prompt copié depuis la vue HD !', 'success');
        const btn = document.getElementById('gmCopyBtnTabler');
        if (btn) {
            btn.innerText = '✅ Copié !';
            setTimeout(() => { btn.innerText = '📋 Copier le Prompt IA'; }, 1500);
        }
    });
}

// Exporter l'intégralité du Grimoire en Markdown
function exportGrimoireMarkdown() {
    const cards = document.querySelectorAll('.prompt-album-col');
    let md = '# 📜 Le Grimoire des 80 Prompts d\'Art Féodal (OpenShogun)\n\n';
    md += '> Généré automatiquement depuis l\'Atelier Pédagogique d\'OpenShogun (Design System Tabler.io).\n\n';

    cards.forEach(c => {
        const title = c.querySelector('.card-title')?.innerText || '';
        const subtitle = c.querySelector('.text-secondary.small')?.innerText || '';
        const file = c.querySelector('.grimoire-file-pill code')?.innerText || '';
        const prompt = c.querySelector('.grimoire-prompt-text')?.innerText || '';
        const trans = c.querySelector('.grimoire-translation-text')?.innerText || '';

        md += `### ${title} — *${subtitle}*\n`;
        md += `\`\`\`text\n${prompt}\n\`\`\`\n`;
        md += `**🇫🇷 Traduction :** *${trans}*\n\n`;
        md += `📁 **Fichier :** \`${file}\`\n\n---\n\n`;
    });

    navigator.clipboard.writeText(md).then(() => {
        triggerGrimoireToast('Grimoire complet (80 prompts en Markdown) copié !', 'success');
    }).catch(err => {
        alert('Erreur lors de la copie du Grimoire.');
    });
}

// Fermeture avec touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeGrimoireModalTabler();
    }
});
</script>
