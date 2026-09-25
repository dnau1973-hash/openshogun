<?php
/**
 * Album Bootstrap du Grimoire des Prompts d'OpenShogun
 * Présentation moderne inspirée de Bootstrap Album avec filtrage instantané,
 * recherche temps-réel, copie 1-clic et modale HD.
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
/* ==========================================================================
   STYLE DU GRIMOIRE BOOTSTRAP ALBUM (OPENSHOGUN)
   ========================================================================== */
.grimoire-album-section {
    color: #e2e8f0;
    margin-top: 1.5rem;
}

/* En-tête Jumbotron de l'Album */
.grimoire-hero-banner {
    background: linear-gradient(135deg, rgba(244, 114, 182, 0.12) 0%, rgba(30, 27, 75, 0.8) 50%, rgba(15, 23, 42, 0.95) 100%);
    border: 1px solid rgba(244, 114, 182, 0.35);
    border-radius: 14px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4), inset 0 0 40px rgba(244, 114, 182, 0.05);
    position: relative;
    overflow: hidden;
}

.grimoire-hero-banner::after {
    content: "🎨";
    position: absolute;
    right: 1.5rem;
    bottom: -1rem;
    font-size: 8rem;
    opacity: 0.06;
    pointer-events: none;
}

/* Barre de Recherche et Filtres Rapides */
.grimoire-filter-bar {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.grimoire-search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.grimoire-search-input-group {
    flex: 1;
    min-width: 280px;
    position: relative;
    display: flex;
    align-items: center;
}

.grimoire-search-icon {
    position: absolute;
    left: 1rem;
    color: #f472b6;
    font-size: 1.1rem;
    pointer-events: none;
}

.grimoire-search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    background: rgba(15, 23, 42, 0.85);
    border: 1.5px solid rgba(244, 114, 182, 0.35);
    border-radius: 10px;
    color: #fff;
    font-size: 0.92rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.grimoire-search-input:focus {
    outline: none;
    border-color: #f472b6;
    box-shadow: 0 0 15px rgba(244, 114, 182, 0.35);
    background: rgba(15, 23, 42, 0.95);
}

.grimoire-clear-search {
    position: absolute;
    right: 0.75rem;
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 1.1rem;
    display: none;
}

.grimoire-clear-search:hover {
    color: #fff;
}

/* Pilules de Catégories (Filtres Bootstrap Album) */
.grimoire-pills-container {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.grimoire-pill-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #cbd5e1;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.45rem 0.85rem;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.grimoire-pill-btn:hover {
    background: rgba(244, 114, 182, 0.18);
    border-color: rgba(244, 114, 182, 0.4);
    color: #fff;
    transform: translateY(-1px);
}

.grimoire-pill-btn.active {
    background: linear-gradient(135deg, #ec4899, #db2777);
    border-color: #f472b6;
    color: #fff;
    font-weight: 800;
    box-shadow: 0 4px 14px rgba(236, 72, 153, 0.35);
}

.grimoire-pill-count {
    background: rgba(0, 0, 0, 0.35);
    padding: 0.1rem 0.45rem;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
}

/* Grille de Cartes Bootstrap Album */
.grimoire-album-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .grimoire-album-grid {
        grid-template-columns: 1fr;
    }
}

/* Carte Album Individuelle */
.prompt-album-card {
    background: rgba(15, 23, 42, 0.82);
    border: 1px solid rgba(244, 114, 182, 0.25);
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.prompt-album-card:hover {
    transform: translateY(-4px);
    border-color: #f472b6;
    box-shadow: 0 12px 28px rgba(244, 114, 182, 0.25);
}

/* Zone Image & Vignette */
.prompt-card-media {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 10;
    background: #090d16;
    overflow: hidden;
    cursor: pointer;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.prompt-card-media.is-tile-bg {
    background: repeating-conic-gradient(#131b2e 0% 25%, #0b111e 0% 50%) 50% / 20px 20px !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prompt-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}

.prompt-card-media.is-tile-bg .prompt-card-img {
    object-fit: contain !important;
    padding: 1.25rem;
    max-height: 90%;
    width: auto;
}

.prompt-album-card:hover .prompt-card-img {
    transform: scale(1.04);
}

.prompt-media-overlay {
    position: absolute;
    top: 0.6rem;
    left: 0.6rem;
    right: 0.6rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    pointer-events: none;
    z-index: 2;
}

.prompt-cat-badge {
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(244, 114, 182, 0.4);
    color: #f472b6;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
}

.prompt-fmt-badge {
    background: rgba(0, 0, 0, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fde047;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    letter-spacing: 0.5px;
}

.prompt-zoom-hint {
    position: absolute;
    bottom: 0.6rem;
    right: 0.6rem;
    background: rgba(0, 0, 0, 0.75);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    opacity: 0;
    transform: translateY(4px);
    transition: all 0.2s ease;
}

.prompt-card-media:hover .prompt-zoom-hint {
    opacity: 1;
    transform: translateY(0);
}

/* Corps de la Carte */
.prompt-card-body {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

.prompt-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}

.prompt-card-title {
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.35;
}

.prompt-clan-badge {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.35);
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.15rem 0.45rem;
    border-radius: 4px;
    white-space: nowrap;
}

.prompt-subtitle {
    color: var(--text-muted, #94a3b8);
    font-size: 0.8rem;
    margin-bottom: 0.85rem;
    line-height: 1.4;
}

/* Pilule Chemin de Fichier */
.prompt-file-pill {
    background: rgba(0, 0, 0, 0.35);
    border: 1px dashed rgba(103, 232, 249, 0.3);
    border-radius: 6px;
    padding: 0.4rem 0.65rem;
    font-family: monospace;
    font-size: 0.75rem;
    color: #67e8f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    margin-bottom: 1rem;
    transition: all 0.15s ease;
}

.prompt-file-pill:hover {
    background: rgba(6, 182, 212, 0.15);
    border-color: #67e8f9;
    color: #fff;
}

.prompt-file-pill code {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Bloc Prompt Anglais */
.prompt-box {
    background: rgba(9, 13, 22, 0.7);
    border: 1px solid rgba(244, 114, 182, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.85rem;
    position: relative;
}

.prompt-box-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.4rem;
}

.prompt-lang-tag {
    font-size: 0.7rem;
    font-weight: 800;
    color: #f472b6;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.btn-copy-prompt-mini {
    background: rgba(244, 114, 182, 0.15);
    border: 1px solid rgba(244, 114, 182, 0.3);
    color: #f472b6;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-copy-prompt-mini:hover {
    background: #f472b6;
    color: #0f172a;
}

.prompt-text {
    color: #cbd5e1;
    font-size: 0.78rem;
    line-height: 1.5;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-height: 100px;
    overflow-y: auto;
    scrollbar-width: thin;
}

/* Bloc Traduction Française */
.translation-box {
    background: rgba(254, 240, 138, 0.05);
    border-left: 3px solid #facc15;
    border-radius: 0 6px 6px 0;
    padding: 0.6rem 0.75rem;
    margin-top: auto;
}

.translation-header {
    color: #facc15;
    font-size: 0.72rem;
    font-weight: 800;
    margin-bottom: 0.25rem;
}

.translation-text {
    color: #e2e8f0;
    font-size: 0.76rem;
    line-height: 1.45;
    margin: 0;
    font-style: italic;
}

/* Pied de Carte (Style Bootstrap Album) */
.prompt-card-footer {
    background: rgba(0, 0, 0, 0.25);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding: 0.75rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.prompt-btn-group {
    display: inline-flex;
    border-radius: 6px;
    overflow: hidden;
}

.btn-album-action {
    background: rgba(244, 114, 182, 0.12);
    border: 1px solid rgba(244, 114, 182, 0.3);
    color: #f472b6;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.4rem 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.btn-album-action:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.btn-album-action:last-child {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-left: none;
}

.btn-album-action:hover {
    background: #f472b6;
    color: #0f172a;
}

.prompt-res-tag {
    color: var(--text-muted, #94a3b8);
    font-size: 0.72rem;
    font-weight: 600;
}

/* Toast de Notification Flottant */
#grimoireToast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 700;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    pointer-events: none;
}

#grimoireToast.show {
    transform: translateY(0);
    opacity: 1;
}

/* Modale HD Grand Format */
#grimoireModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.88);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}

#grimoireModal.open {
    display: flex;
}

.grimoire-modal-content {
    background: #0f172a;
    border: 1.5px solid #f472b6;
    border-radius: 14px;
    max-width: 960px;
    width: 100%;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 35px rgba(244, 114, 182, 0.3);
}

.grimoire-modal-header {
    padding: 1rem 1.5rem;
    background: rgba(244, 114, 182, 0.1);
    border-bottom: 1px solid rgba(244, 114, 182, 0.25);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.grimoire-modal-body {
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.grimoire-modal-img {
    max-width: 100%;
    max-height: 55vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}
</style>

<div class="grimoire-album-section">

    <!-- JUMBOTRON / EN-TÊTE DE L'ALBUM -->
    <div class="grimoire-hero-banner">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(244, 114, 182, 0.2); border: 1px solid #f472b6; color: #fbcfe8; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 0.75rem;">
            <span>📜</span> Grimoire d'Art & Prompt Engineering &bull; Sengoku Jidai
        </div>
        <h2 style="color: #fff; margin: 0 0 0.6rem 0; font-size: 1.75rem; font-weight: 900; display: flex; align-items: center; gap: 0.6rem;">
            <span>🎨</span> Le Grimoire des Prompts Utilisés pour OpenShogun
        </h2>
        <p style="color: #cbd5e1; font-size: 0.95rem; margin: 0; max-width: 860px; line-height: 1.6;">
            Retrouvez ici l'intégralité des <strong>80 prompts d'art féodal</strong> ayant façonné l'univers visuel d'OpenShogun avec l'Intelligence Artificielle (Gemini, Imagen, Midjourney).
            Présenté sous forme d'<strong>Album interactif (style Bootstrap Album)</strong> : explorez les illustrations en situation, copiez les prompts en 1 clic et vérifiez chaque fichier dans le projet.
        </p>
        <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap; align-items: center;">
            <span style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15); color: #67e8f9; padding: 0.35rem 0.85rem; border-radius: 8px; font-size: 0.82rem; font-weight: 700;">
                📊 80 Assets Authentiques Actifs
            </span>
            <span style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15); color: #facc15; padding: 0.35rem 0.85rem; border-radius: 8px; font-size: 0.82rem; font-weight: 700;">
                🛡️ 0 Prompt Obsolète (Nettoyage Intégral)
            </span>
            <span style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.15); color: #a7f3d0; padding: 0.35rem 0.85rem; border-radius: 8px; font-size: 0.82rem; font-weight: 700;">
                🖼️ Style Ukiyo-e & 2.5D Vectoriel Pur
            </span>
            <button type="button" class="btn btn-secondary" onclick="exportGrimoireMarkdown()" style="font-size: 0.8rem; padding: 0.35rem 0.85rem; margin-left: auto; border-color: #f472b6; color: #f472b6; font-weight: 700;">
                📋 Copier Tout le Grimoire (Markdown)
            </button>
        </div>
    </div>

    <!-- BARRE DE CONTRÔLE : RECHERCHE & FILTRES RAPIDES PAR CATÉGORIE -->
    <div class="grimoire-filter-bar">
        <!-- Recherche Instantanée -->
        <div class="grimoire-search-wrapper">
            <div class="grimoire-search-input-group">
                <span class="grimoire-search-icon">🔍</span>
                <input type="text" 
                       id="grimoireSearchInput" 
                       class="grimoire-search-input" 
                       placeholder="Rechercher par nom (ex: Bûcheron, Bélier, Takeda), mot-clé anglais, clan ou nom de fichier..."
                       autocomplete="off">
                <button type="button" id="grimoireClearBtn" class="grimoire-clear-search" onclick="clearGrimoireSearch()" title="Effacer la recherche">&times;</button>
            </div>
            <div id="grimoireCounterBadge" style="font-size: 0.85rem; font-weight: 700; color: #cbd5e1; padding: 0.5rem 1rem; background: rgba(0,0,0,0.35); border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                Affichage de <span id="countVisible" style="color:#f472b6;"><?= count($promptsCatalog) ?></span> / <?= count($promptsCatalog) ?> prompts
            </div>
        </div>

        <!-- Pilules de Catégories (Filtres Rapides) -->
        <div class="grimoire-pills-container">
            <button type="button" class="grimoire-pill-btn active" data-filter="all" onclick="filterGrimoireCategory('all', this)">
                🌐 Tous <span class="grimoire-pill-count"><?= $categoryCounts['all'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="panoramas" onclick="filterGrimoireCategory('panoramas', this)">
                🌄 Panoramas <span class="grimoire-pill-count"><?= $categoryCounts['panoramas'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="terroirs" onclick="filterGrimoireCategory('terroirs', this)">
                🌾 Ressources <span class="grimoire-pill-count"><?= $categoryCounts['terroirs'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="buildings" onclick="filterGrimoireCategory('buildings', this)">
                🏯 Cité Castrale <span class="grimoire-pill-count"><?= $categoryCounts['buildings'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="tiles" onclick="filterGrimoireCategory('tiles', this)">
                🀄 Tuiles 2.5D <span class="grimoire-pill-count"><?= $categoryCounts['tiles'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="infantry" onclick="filterGrimoireCategory('infantry', this)">
                ⚔️ Infanterie <span class="grimoire-pill-count"><?= $categoryCounts['infantry'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="cavalry" onclick="filterGrimoireCategory('cavalry', this)">
                🐎 Cavalerie <span class="grimoire-pill-count"><?= $categoryCounts['cavalry'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="animals" onclick="filterGrimoireCategory('animals', this)">
                🐗 Bêtes Oasis <span class="grimoire-pill-count"><?= $categoryCounts['animals'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="siege" onclick="filterGrimoireCategory('siege', this)">
                💥 Siège <span class="grimoire-pill-count"><?= $categoryCounts['siege'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="hero" onclick="filterGrimoireCategory('hero', this)">
                👑 Héros <span class="grimoire-pill-count"><?= $categoryCounts['hero'] ?></span>
            </button>
            <button type="button" class="grimoire-pill-btn" data-filter="castles" onclick="filterGrimoireCategory('castles', this)">
                🏯 12 Châteaux <span class="grimoire-pill-count"><?= $categoryCounts['castles'] ?></span>
            </button>
        </div>
    </div>

    <!-- GRILLE D'ALBUM (STYLE BOOTSTRAP ALBUM) -->
    <div class="grimoire-album-grid" id="grimoireCardsGrid">
        <?php foreach ($promptsCatalog as $idx => $p): 
            $imgDiskPath = __DIR__ . '/../../public/assets/' . $p['file'];
            $imgExists = file_exists($imgDiskPath);
            $imgUrl = '/public/assets/' . $p['file'] . ($imgExists ? '?v=' . filemtime($imgDiskPath) : '');
            
            // Chaîne de recherche normalisée
            $searchIndex = strtolower($p['title'] . ' ' . $p['subtitle'] . ' ' . $p['display_path'] . ' ' . $p['clan'] . ' ' . $p['prompt'] . ' ' . $p['translation'] . ' ' . $p['category']);
            $isTile = ($p['category'] === 'tiles');
        ?>
            <div class="prompt-album-card" 
                 data-category="<?= htmlspecialchars($p['category']) ?>" 
                 data-search="<?= htmlspecialchars($searchIndex) ?>"
                 id="card-prompt-<?= $p['id'] ?>">

                <!-- Aperçu Visuel / Image Top -->
                <div class="prompt-card-media <?= $isTile ? 'is-tile-bg' : '' ?>" 
                     onclick="openGrimoireModal('<?= $imgUrl ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['display_path'])) ?>', 'card-prompt-<?= $p['id'] ?>')"
                     title="Cliquer pour admirer en plein écran">
                    <img src="<?= $imgUrl ?>" 
                         class="prompt-card-img" 
                         alt="<?= htmlspecialchars($p['title']) ?>" 
                         loading="lazy">
                    
                    <div class="prompt-media-overlay">
                        <span class="prompt-cat-badge"><?= htmlspecialchars($p['category_label']) ?></span>
                        <span class="prompt-fmt-badge"><?= htmlspecialchars($p['format']) ?></span>
                    </div>

                    <div class="prompt-zoom-hint">🔍 Agrandir</div>

                    <!-- Badge Transparence Prompt IA (« ? ») -->
                    <button type="button" 
                            class="ai-prompt-badge" 
                            data-ai-title="<?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>"
                            data-ai-img="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                            data-ai-prompt="<?= htmlspecialchars($p['prompt'], ENT_QUOTES, 'UTF-8') ?>"
                            data-ai-translation="<?= htmlspecialchars($p['translation'], ENT_QUOTES, 'UTF-8') ?>"
                            title="Détails du prompt & transparence IA">
                        <span class="ai-badge-icon">?</span>
                    </button>
                </div>

                <!-- Corps de la Carte -->
                <div class="prompt-card-body">
                    <div class="prompt-card-header">
                        <h4 class="prompt-card-title"><?= htmlspecialchars($p['title']) ?></h4>
                        <span class="prompt-clan-badge"><?= htmlspecialchars($p['clan']) ?></span>
                    </div>

                    <div class="prompt-subtitle">
                        <?= htmlspecialchars($p['subtitle']) ?>
                    </div>

                    <!-- Fichier Cible Cliquable pour Copier -->
                    <div class="prompt-file-pill" 
                         onclick="copyTextToClipboard('<?= htmlspecialchars(addslashes($p['display_path'])) ?>', this)" 
                         title="Cliquer pour copier le chemin du fichier">
                        <span style="display:flex; align-items:center; gap:0.4rem; overflow:hidden;">
                            <span>📁</span>
                            <code><?= htmlspecialchars($p['display_path']) ?></code>
                        </span>
                        <span style="opacity:0.7;">📋</span>
                    </div>

                    <!-- Bloc Prompt Anglais -->
                    <div class="prompt-box">
                        <div class="prompt-box-header">
                            <span class="prompt-lang-tag">🇬🇧 Prompt Exact Transmis à l'IA</span>
                            <button type="button" 
                                    class="btn-copy-prompt-mini" 
                                    onclick="copyPromptCardText(this)" 
                                    title="Copier ce prompt en anglais">
                                📋 Copier
                            </button>
                        </div>
                        <p class="prompt-text"><?= htmlspecialchars($p['prompt']) ?></p>
                    </div>

                    <!-- Bloc Traduction Française -->
                    <div class="translation-box">
                        <div class="translation-header">🇫🇷 Traduction & Contexte Pédagogique</div>
                        <p class="translation-text"><?= htmlspecialchars($p['translation']) ?></p>
                    </div>
                </div>

                <!-- Pied de Carte (Style Bootstrap Album) -->
                <div class="prompt-card-footer">
                    <div class="prompt-btn-group">
                        <button type="button" 
                                class="btn-album-action" 
                                onclick="copyPromptCardText(this)"
                                title="Copier le prompt anglais dans le presse-papiers">
                            <span>📋</span> Copier
                        </button>
                        <button type="button" 
                                class="btn-album-action" 
                                onclick="openGrimoireModal('<?= $imgUrl ?>', '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['display_path'])) ?>', 'card-prompt-<?= $p['id'] ?>')"
                                title="Voir l'illustration en grand format">
                            <span>🔍</span> Voir
                        </button>
                    </div>
                    <span class="prompt-res-tag"><?= htmlspecialchars($p['resolution']) ?></span>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- État Aucun Résultat -->
    <div id="grimoireNoResults" style="display: none; text-align: center; padding: 4rem 1rem; background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(244, 114, 182, 0.3); border-radius: 12px; margin-top: 2rem;">
        <div style="font-size: 3rem; margin-bottom: 0.75rem;">🔍</div>
        <h4 style="color: #fff; margin: 0 0 0.5rem 0;">Aucun prompt ne correspond à votre recherche</h4>
        <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 1.5rem;">
            Essayez de modifier votre mot-clé ou réinitialisez les filtres.
        </p>
        <button type="button" class="btn btn-secondary" onclick="clearGrimoireSearch()" style="border-color: #f472b6; color: #f472b6;">
            🔄 Réinitialiser la recherche
        </button>
    </div>

</div>

<!-- TOAST FLOTTANT DE CONFIRMATION DE COPIE -->
<div id="grimoireToast">
    <span>✅</span>
    <span id="grimoireToastMsg">Prompt copié dans le presse-papiers !</span>
</div>

<!-- MODALE D'ILLUSTRATION HD -->
<div id="grimoireModal" onclick="closeGrimoireModal()">
    <div class="grimoire-modal-content" onclick="event.stopPropagation()">
        <div class="grimoire-modal-header">
            <div>
                <h3 id="gmTitle" style="margin:0; color:#fff; font-size:1.2rem; font-weight:800;"></h3>
                <div id="gmFile" style="font-family:monospace; font-size:0.78rem; color:#67e8f9; margin-top:0.2rem;"></div>
            </div>
            <button onclick="closeGrimoireModal()" style="background:transparent; border:none; color:#f472b6; font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>
        <div class="grimoire-modal-body">
            <img id="gmImg" src="" alt="" class="grimoire-modal-img">
            <div style="width: 100%; display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeGrimoireModal()">Fermer</button>
                <button type="button" class="btn btn-primary" id="gmCopyBtn" onclick="copyModalPrompt()" style="background:#ec4899; border-color:#db2777; font-weight:700;">
                    📋 Copier le Prompt IA
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS INTERACTIFS DU GRIMOIRE BOOTSTRAP ALBUM -->
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
const clearBtn = document.getElementById('grimoireClearBtn');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
        }
        applyGrimoireFilters();
    });
}

function clearGrimoireSearch() {
    if (searchInput) {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        searchInput.focus();
    }
    applyGrimoireFilters();
}

// Appliquer filtres catégorie + recherche
function applyGrimoireFilters() {
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const cards = document.querySelectorAll('.prompt-album-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cardCat = card.getAttribute('data-category');
        const cardSearch = card.getAttribute('data-search') || '';

        const matchesCat = (currentActiveCategory === 'all' || cardCat === currentActiveCategory);
        const matchesQuery = (query === '' || cardSearch.includes(query));

        if (matchesCat && matchesQuery) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const countVisibleEl = document.getElementById('countVisible');
    if (countVisibleEl) countVisibleEl.innerText = visibleCount;

    const noResultsEl = document.getElementById('grimoireNoResults');
    if (noResultsEl) {
        noResultsEl.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

// Copier texte générique
function copyTextToClipboard(text, el = null) {
    navigator.clipboard.writeText(text).then(() => {
        showGrimoireToast('Chemin copié : ' + text);
        if (el) {
            const originalBg = el.style.background;
            el.style.background = 'rgba(16, 185, 129, 0.3)';
            setTimeout(() => { el.style.background = originalBg; }, 600);
        }
    }).catch(err => {
        prompt('Copiez manuellement :', text);
    });
}

// Copier le prompt depuis la carte
function copyPromptCardText(btn) {
    const card = btn.closest('.prompt-album-card');
    if (!card) return;
    const promptP = card.querySelector('.prompt-text');
    if (!promptP) return;
    const text = promptP.innerText.trim();

    navigator.clipboard.writeText(text).then(() => {
        showGrimoireToast('Prompt copié dans le presse-papiers !');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>✅</span> Copié !';
        setTimeout(() => { btn.innerHTML = originalText; }, 1800);
    }).catch(err => {
        prompt('Copiez le prompt :', text);
    });
}

// Toast Flottant
let toastTimeout = null;
function showGrimoireToast(msg) {
    const toast = document.getElementById('grimoireToast');
    const msgEl = document.getElementById('grimoireToastMsg');
    if (!toast || !msgEl) return;

    msgEl.innerText = msg;
    toast.classList.add('show');

    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 2400);
}

// Modale Grand Format
function openGrimoireModal(imgUrl, title, filePath, cardId) {
    const modal = document.getElementById('grimoireModal');
    const titleEl = document.getElementById('gmTitle');
    const fileEl = document.getElementById('gmFile');
    const imgEl = document.getElementById('gmImg');
    
    if (!modal) return;

    titleEl.innerText = title;
    fileEl.innerText = '📁 ' + filePath;
    imgEl.src = imgUrl;

    const card = document.getElementById(cardId);
    if (card) {
        const pText = card.querySelector('.prompt-text');
        modalActivePrompt = pText ? pText.innerText.trim() : '';
    }

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeGrimoireModal() {
    const modal = document.getElementById('grimoireModal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
}

function copyModalPrompt() {
    if (!modalActivePrompt) return;
    navigator.clipboard.writeText(modalActivePrompt).then(() => {
        showGrimoireToast('Prompt copié depuis la vue HD !');
        const btn = document.getElementById('gmCopyBtn');
        if (btn) {
            btn.innerText = '✅ Copié !';
            setTimeout(() => { btn.innerText = '📋 Copier le Prompt IA'; }, 1600);
        }
    });
}

// Exporter l'intégralité du Grimoire en Markdown
function exportGrimoireMarkdown() {
    const cards = document.querySelectorAll('.prompt-album-card');
    let md = '# 📜 Le Grimoire des 80 Prompts d\'Art Féodal (OpenShogun)\n\n';
    md += '> Généré automatiquement depuis l\'Atelier Pédagogique d\'OpenShogun.\n\n';

    cards.forEach(c => {
        const title = c.querySelector('.prompt-card-title')?.innerText || '';
        const subtitle = c.querySelector('.prompt-subtitle')?.innerText || '';
        const file = c.querySelector('.prompt-file-pill code')?.innerText || '';
        const prompt = c.querySelector('.prompt-text')?.innerText || '';
        const trans = c.querySelector('.translation-text')?.innerText || '';

        md += `### ${title} — *${subtitle}*\n`;
        md += `\`\`\`text\n${prompt}\n\`\`\`\n`;
        md += `**🇫🇷 Traduction :** *${trans}*\n\n`;
        md += `📁 **Fichier :** \`${file}\`\n\n---\n\n`;
    });

    navigator.clipboard.writeText(md).then(() => {
        showGrimoireToast('Grimoire complet (80 prompts en Markdown) copié !');
    }).catch(err => {
        alert('Erreur lors de la copie du Grimoire.');
    });
}

// Fermeture avec touche Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeGrimoireModal();
    }
});
</script>

