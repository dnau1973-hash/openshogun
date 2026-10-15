<?php
/**
 * Vue : Galerie d'Art Féodale & Photogrid Officiel (OpenShogun)
 * Présentation des illustrations du jeu sur le modèle Tabler.io Photogrid
 * Catégories : buildings, carrousel, castles, clans, events, items, resources, tiles, units
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/Auth.php';

$isAuth = Auth::check();

// Configuration des 9 répertoires d'illustrations demandés
$categoryConfig = [
    'all' => [
        'label' => 'Toutes les illustrations',
        'full_label' => 'Toutes les estampes et illustrations',
        'icon' => 'fa-images',
        'badge' => 'bg-primary text-white',
        'color' => 'primary',
        'desc' => 'L’intégralité des créations graphiques et illustrations historiques du jeu OpenShogun.',
    ],
    'buildings' => [
        'label' => 'Bâtiments',
        'full_label' => 'Bâtiments Castraux & Infrastructures',
        'icon' => 'fa-landmark',
        'badge' => 'bg-blue text-white',
        'color' => 'blue',
        'desc' => 'Dojos, forges, académies, greniers, casernes et édifices du domaine féodal.',
    ],
    'carrousel' => [
        'label' => 'Carrousel',
        'full_label' => 'Panoramas & Carrousel',
        'icon' => 'fa-panorama',
        'badge' => 'bg-azure text-white',
        'color' => 'azure',
        'desc' => 'Fresques panoramiques 16:9, décors atmosphériques et paysages du Sengoku.',
    ],
    'castles' => [
        'label' => 'Châteaux',
        'full_label' => 'Châteaux & Forteresses Historiques',
        'icon' => 'fa-chess-rook',
        'badge' => 'bg-red text-white',
        'color' => 'red',
        'desc' => 'Himeji, Hikone, Inuyama, Bitchū Matsuyama et citadelles impériales du Japon.',
    ],
    'clans' => [
        'label' => 'Clans',
        'full_label' => 'Clans & Armoiries de Guerre',
        'icon' => 'fa-flag',
        'badge' => 'bg-yellow text-dark',
        'color' => 'yellow',
        'desc' => 'Conseils de guerre, bannières et scènes stratégiques des grandes maisons féodales.',
    ],
    'events' => [
        'label' => 'Événements',
        'full_label' => 'Événements & Chroniques Féodales',
        'icon' => 'fa-scroll',
        'badge' => 'bg-purple text-white',
        'color' => 'purple',
        'desc' => 'Illustrations des 52 semaines de chroniques saisonnières et périls du Shogunat.',
    ],
    'items' => [
        'label' => 'Objets & Reliques',
        'full_label' => 'Reliques, Armures & Trésors',
        'icon' => 'fa-gem',
        'badge' => 'bg-orange text-white',
        'color' => 'orange',
        'desc' => 'Katanas de maître, montures légendaires, armures précieuses et reliques shinto.',
    ],
    'resources' => [
        'label' => 'Ressources',
        'full_label' => 'Ressources & Terroirs',
        'icon' => 'fa-wheat-awn',
        'badge' => 'bg-green text-white',
        'color' => 'green',
        'desc' => 'Riz impérial, cèdre noble, pierre de taille, ferveur shinto et gisements de minerai.',
    ],
    'tiles' => [
        'label' => 'Tuiles',
        'full_label' => 'Tuiles & Hexagones de Terroir',
        'icon' => 'fa-cubes',
        'badge' => 'bg-teal text-white',
        'color' => 'teal',
        'desc' => 'Composants isométriques de l’arpentage territorial et des parcelles du village.',
    ],
    'units' => [
        'label' => 'Unités',
        'full_label' => 'Armées, Régiments & Héros',
        'icon' => 'fa-shield-halved',
        'badge' => 'bg-danger text-white',
        'color' => 'danger',
        'desc' => 'Samouraïs, archers, cavaliers d’élite, ninjas et le Mineur des Cimes (Kōfu).',
    ],
    'bibliotheque' => [
        'label' => 'Bibliothèque',
        'full_label' => 'Bibliothèque & Imports Personnalisés',
        'icon' => 'fa-folder-plus',
        'badge' => 'bg-indigo text-white',
        'color' => 'indigo',
        'desc' => 'Illustrations et documents visuels téléversés depuis le Narrative Designer et le Game Studio.',
    ],
];

/**
 * Nettoie et humanise un nom de fichier d'illustration pour un affichage prestigieux
 */
function formatIllustrationTitle(string $filename, string $category): string {
    $raw = pathinfo($filename, PATHINFO_FILENAME);

    // Dictionnaire de correspondances précises
    $dictionary = [
        'building_barracks' => "Caserne d'Infanterie (Barracks)",
        'building_blacksmith' => "Grande Forge Féodale (Tamahagane)",
        'building_embassy' => "Ambassade des Clans & Factions",
        'building_grain_mill' => "Moulin à Grain & Grenier",
        'building_market' => "Marché Provincial & Échoppes",
        'building_quantum_vault' => "Chambre Forte Impériale",
        'building_radar' => "Tour de Guet & Postes de Vigie",
        'building_research_lab' => "Académie Militaire & Stratégie",
        'building_shipyard' => "Chantier Naval & Docks Fluviaux",
        'building_storage' => "Entrepôt Général des Terroirs",
        'building_tank' => "Arsenal & Armurerie Lourde",
        'building_tenshu' => "Tenshu Principal (Donjon Fortifié)",
        'building_wall' => "Muraille d'Enceinte & Remparts",
        'mineur' => "Mineur des Cimes (Kōfu - 鉱夫)",
        'archer_protecteur_muraille' => "Archer Protecteur de Muraille",
        'archer_yumi_monte' => "Archer à Cheval (Yabusame)",
        'ressource_bois_cedre' => "Bois de Cèdre Millénaire",
        'ressource_ferveur_shinto' => "Ferveur Shintō & Prières",
        'ressource_pierre_taille' => "Pierre de Taille des Carrières",
        'ressource_riz_imperial' => "Riz Impérial des Rizières",
        'terroir_clay' => "Carrière d'Argile & Terres Cuites",
        'clan_oda_war_council' => "Conseil de Guerre du Clan Oda",
        'clan_takeda_cavalry_charge' => "Charge de Cavalerie du Clan Takeda",
        'clan_tokugawa_covert_scout' => "Éclaireurs Secrets du Clan Tokugawa",
        'bitchu_matsuyama' => "Château de Bitchū Matsuyama",
        'hikone' => "Château de Hikone",
        'himeji' => "Château de Himeji (Héron Blanc)",
        'hirosaki' => "Château de Hirosaki",
        'inuyama' => "Château d'Inuyama",
        'iwakuni' => "Château d'Iwakuni",
        'kochi' => "Château de Kōchi",
        'kumamoto' => "Château de Kumamoto",
        'marugame' => "Château de Marugame",
        'matsue' => "Château de Matsue",
        'matsumoto' => "Château de Matsumoto (Corbeau Noir)",
        'uwajima' => "Château d'Uwajima",
        'armure_do_maru' => "Armure Dō-maru Impériale",
        'armure_ebene_takeda' => "Armure d'Ébène du Clan Takeda",
        'armure_rouge_iinao' => "Armure Rouge des Ii (Akazonae)",
        'cages_capture' => "Cages de Capture & Pièges",
        'cheval_bai_musashi' => "Cheval Bai de Musashi",
        'tile_barracks' => "Tuile Caserne d'Infanterie",
        'tile_blacksmith' => "Tuile Forge Féodale",
        'tile_bucheron' => "Tuile Concession Forestière",
        'tile_carriere' => "Tuile Carrière de Granit",
        'tile_embassy' => "Tuile Ambassade & Pavillon Diplomatique",
        'tile_ferme' => "Tuile Rizières & Terroirs Fertiles",
        'tile_grain_mill' => "Tuile Moulin à Eau & Farine",
        'tile_mine_fer' => "Tuile Mine de Fer & Sables Noirs",
        'tile_storage' => "Tuile Silos & Entrepôt",
        'tile_tenshu' => "Tuile Donjon Fortifié (Tenshu)",
        'tile_wall' => "Tuile Muraille & Bastions",
    ];

    if (isset($dictionary[$raw])) {
        return $dictionary[$raw];
    }

    // Traitements par regex pour numéros réguliers
    if (preg_match('/^carrousel_(\d+)$/i', $raw, $m)) {
        return "Panorama Sengoku #" . $m[1];
    }
    if (preg_match('/^event_week_(\d+)$/i', $raw, $m)) {
        return "Chronique Féodale &bull; Semaine " . $m[1];
    }

    // Traitement général : suppression des préfixes et formatage soigné
    $clean = preg_replace('/^(building_|tile_|ressource_|clan_)/i', '', $raw);
    $clean = str_replace(['_', '-'], ' ', (string)$clean);
    return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
}

/**
 * Formatage lisible du poids de fichier
 */
function formatBytes(int $bytes, int $precision = 1): string {
    if ($bytes <= 0) return '0 o';
    $units = ['o', 'Ko', 'Mo', 'Go'];
    $i = (int)floor(log($bytes, 1024));
    if ($i >= count($units)) $i = count($units) - 1;
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}

// Balayage dynamique des répertoires de public/assets/
$baseDir = __DIR__ . '/../public/assets';
$galleryItems = [];
$categoryCounts = [
    'all' => 0,
    'buildings' => 0,
    'carrousel' => 0,
    'castles' => 0,
    'clans' => 0,
    'events' => 0,
    'items' => 0,
    'resources' => 0,
    'tiles' => 0,
    'units' => 0,
    'bibliotheque' => 0,
];
$totalBytes = 0;

$scannedDirs = ['buildings', 'carrousel', 'castles', 'clans', 'events', 'items', 'resources', 'tiles', 'units', 'bibliotheque'];

foreach ($scannedDirs as $catKey) {
    $dirPath = $baseDir . '/' . $catKey;
    if (!is_dir($dirPath)) continue;

    $files = glob($dirPath . '/*.{jpg,jpeg,png,webp,svg,gif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($files as $filePath) {
        $filename = basename($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $fileSize = filesize($filePath) ?: 0;
        $fileTime = filemtime($filePath) ?: 0;

        $totalBytes += $fileSize;
        $categoryCounts[$catKey]++;
        $categoryCounts['all']++;

        $title = formatIllustrationTitle($filename, $catKey);

        $galleryItems[] = [
            'id' => md5($catKey . '/' . $filename),
            'filename' => $filename,
            'category' => $catKey,
            'category_label' => $categoryConfig[$catKey]['label'] ?? $catKey,
            'category_badge' => $categoryConfig[$catKey]['badge'] ?? 'bg-secondary text-white',
            'category_icon' => $categoryConfig[$catKey]['icon'] ?? 'fa-image',
            'title' => $title,
            'web_path' => '/public/assets/' . $catKey . '/' . $filename,
            'size' => $fileSize,
            'size_human' => formatBytes($fileSize),
            'ext' => strtoupper($extension),
            'mtime' => $fileTime,
        ];
    }
}

// Encodage JSON sécurisé pour le Lightbox et les filtres JS
$galleryJson = json_encode($galleryItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<?php if (!$isAuth): ?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie d'Art Féodale &bull; OpenShogun Photogrid</title>
    <!-- Google Fonts: Dela Gothic One -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="/public/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Tabler CSS -->
    <link rel="stylesheet" href="/public/css/tabler/tabler.min.css?v=1.0.0-beta21">
    <link rel="stylesheet" href="/public/css/style.css?v=<?= file_exists(__DIR__ . '/../public/css/style.css') ? filemtime(__DIR__ . '/../public/css/style.css') : time() ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎨</text></svg>">
</head>
<body class="antialiased bg-body-tertiary">
    <div class="page">
        <!-- Top Navigation pour Visiteurs -->
        <header class="navbar navbar-expand-md bg-white border-bottom py-2 sticky-top shadow-sm">
            <div class="container-xl px-3 px-lg-4 d-flex justify-content-between align-items-center">
                <a href="/" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                    <span class="fs-2 text-danger"><i class="fa-solid fa-palette"></i></span>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size: 1.15rem; letter-spacing: 0.3px;">OpenShogun &bull; Galerie d'Art</div>
                        <div class="text-secondary small fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.6px; text-transform: uppercase;">Archives Visuelles &amp; Estampes du Sengoku</div>
                    </div>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <a href="/?page=docs" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-book-open"></i> Règles
                    </a>
                    <a href="/?page=changelog" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="fa-solid fa-scroll"></i> Changelog
                    </a>
                    <a href="/" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Rejoindre la partie
                    </a>
                </div>
            </div>
        </header>

        <main class="page-body my-4">
            <div class="container-xl">
<?php endif; ?>

<!-- ==========================================
     STYLISATION TABLER PHOTOGRID & EFFETS DE SURVOL
     ========================================== -->
<style>
.gallery-card {
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease;
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: #ffffff;
    border-radius: 8px;
    overflow: hidden;
}
[data-bs-theme="dark"] .gallery-card {
    background: #182234;
    border-color: rgba(255, 255, 255, 0.08);
}
.gallery-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
}
.gallery-thumb-container {
    position: relative;
    overflow: hidden;
    background: #111827;
}
.gallery-thumb-container img {
    transition: transform 0.4s ease, filter 0.3s ease;
    object-fit: cover;
    width: 100%;
    height: 100%;
}
.gallery-card:hover .gallery-thumb-container img {
    transform: scale(1.06);
}
.gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.55) 0%, rgba(0,0,0,0) 40%, rgba(0, 0, 0, 0.75) 100%);
    opacity: 0;
    transition: opacity 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 0.5rem;
    pointer-events: none;
}
.gallery-card:hover .gallery-overlay {
    opacity: 1;
}
.gallery-zoom-btn {
    pointer-events: auto;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    color: #111827;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: transform 0.2s ease, background 0.2s ease;
}
.gallery-zoom-btn:hover {
    transform: scale(1.15);
    background: #ffffff;
    color: #b91c1c;
}
.gallery-nav-pills .nav-link {
    border-radius: 20px;
    padding: 0.45rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
[data-bs-theme="dark"] .gallery-nav-pills .nav-link {
    color: #94a3b8;
    background: #1e293b;
    border-color: rgba(255, 255, 255, 0.08);
}
.gallery-nav-pills .nav-link:hover {
    background: #e2e8f0;
    color: #0f172a;
}
[data-bs-theme="dark"] .gallery-nav-pills .nav-link:hover {
    background: #334155;
    color: #f8fafc;
}
.gallery-nav-pills .nav-link.active {
    background: #b91c1c !important;
    color: #ffffff !important;
    border-color: #991b1b !important;
    box-shadow: 0 3px 10px rgba(185, 28, 28, 0.35);
}
.gallery-nav-pills .nav-link.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}
.gallery-empty-state {
    padding: 4rem 1rem;
    text-align: center;
}
/* Modal Lightbox Féodal */
#galleryModal .modal-content {
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
    color: #f8fafc;
}
#galleryModal .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1rem 1.5rem;
}
#galleryModal .modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 0.75rem 1.5rem;
}
.lightbox-img-wrapper {
    min-height: 320px;
    max-height: 72vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at center, #1e293b 0%, #090d16 100%);
    position: relative;
    border-radius: 8px;
    overflow: hidden;
}
.lightbox-img-wrapper img {
    max-height: 70vh;
    max-width: 100%;
    object-fit: contain;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    border-radius: 4px;
}
.lightbox-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: rgba(15, 23, 42, 0.75);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.25rem;
    transition: all 0.2s ease;
    backdrop-filter: blur(4px);
    z-index: 10;
}
.lightbox-nav-btn:hover {
    background: rgba(185, 28, 28, 0.9);
    color: #fff;
    border-color: transparent;
    transform: translateY(-50%) scale(1.1);
}
.lightbox-nav-btn.prev-btn { left: 1rem; }
.lightbox-nav-btn.next-btn { right: 1rem; }
</style>

<!-- EN-TÊTE PRINCIPAL DU PAVILLON DES ESTAMPES -->
<div class="row align-items-center mb-4">
    <div class="col-12 col-md-7">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-danger-lt text-danger border border-danger fw-bold">
                <i class="fa-solid fa-palette me-1"></i> Photogrid Tabler
            </span>
            <span class="badge bg-secondary-lt text-secondary">
                <i class="fa-solid fa-layer-group me-1"></i> 9 Domaines Visuels
            </span>
            <span class="badge bg-primary-lt text-primary">
                <i class="fa-solid fa-database me-1"></i> <?= $categoryCounts['all'] ?> Illustrations
            </span>
        </div>
        <h1 class="page-title d-flex align-items-center gap-2 text-dark m-0" style="font-family:'Dela Gothic One', serif; font-size:1.65rem;">
            <span>Galerie d'Art Féodale</span>
        </h1>
        <p class="text-secondary mt-1 mb-0" style="font-size:0.92rem;">
            Archives visuelles officielles d'OpenShogun &mdash; Bâtiments, panoramas, forteresses, clans, reliques, terroirs, tuiles et guerriers.
        </p>
    </div>
    <div class="col-12 col-md-5 mt-3 mt-md-0">
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-md-end align-items-stretch align-items-sm-center">
            <!-- Barre de Recherche en Temps Réel -->
            <div class="input-icon flex-grow-1" style="max-width:320px;">
                <span class="input-icon-addon">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" id="gallerySearchInput" class="form-control" placeholder="Rechercher une illustration..." autocomplete="off">
            </div>
            <button class="btn btn-outline-secondary" onclick="resetGalleryFilters()" title="Réinitialiser filtres et recherche">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
        </div>
    </div>
</div>

<!-- STATS CARDS RÉSUMÉ -->
<div class="row row-cards mb-4">
    <div class="col-6 col-sm-3 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="avatar bg-primary-lt text-primary rounded-circle">
                    <i class="fa-solid fa-images fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 lh-1 text-dark" id="statTotalCount"><?= $categoryCounts['all'] ?></div>
                    <div class="text-secondary small mt-1">Œuvres Recensées</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="avatar bg-azure-lt text-azure rounded-circle">
                    <i class="fa-solid fa-folder-tree fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 lh-1 text-dark"><?= count($scannedDirs) ?></div>
                    <div class="text-secondary small mt-1">Dossiers Thématiques</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="avatar bg-green-lt text-green rounded-circle">
                    <i class="fa-solid fa-hard-drive fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 lh-1 text-dark"><?= formatBytes($totalBytes) ?></div>
                    <div class="text-secondary small mt-1">Poids Graphique Total</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-3 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="avatar bg-danger-lt text-danger rounded-circle">
                    <i class="fa-solid fa-wand-magic-sparkles fs-2"></i>
                </span>
                <div>
                    <div class="fw-bold fs-3 lh-1 text-dark" id="statVisibleCount"><?= $categoryCounts['all'] ?></div>
                    <div class="text-secondary small mt-1">Actuellement Visibles</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTRES DE NAVIGATION PAR ONGLETS (PILLS TABLER) -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-2 p-md-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <ul class="nav nav-pills gallery-nav-pills gap-1 m-0" id="categoryFilterList">
                <li class="nav-item">
                    <button class="nav-link active" data-filter="all" onclick="filterCategory('all', this)">
                        <i class="fa-solid fa-images"></i>
                        <span>Toutes</span>
                        <span class="badge bg-secondary-lt rounded-pill ms-1"><?= $categoryCounts['all'] ?></span>
                    </button>
                </li>
                <?php foreach ($scannedDirs as $cKey): 
                    $cCfg = $categoryConfig[$cKey];
                    $cCount = $categoryCounts[$cKey] ?? 0;
                ?>
                <li class="nav-item">
                    <button class="nav-link" data-filter="<?= htmlspecialchars($cKey) ?>" onclick="filterCategory('<?= htmlspecialchars($cKey) ?>', this)">
                        <i class="fa-solid <?= htmlspecialchars($cCfg['icon']) ?>"></i>
                        <span><?= htmlspecialchars($cCfg['label']) ?></span>
                        <span class="badge bg-secondary-lt rounded-pill ms-1"><?= $cCount ?></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Sélecteur de Tri -->
            <div class="d-flex align-items-center gap-2 ms-auto">
                <span class="text-secondary small fw-semibold d-none d-sm-inline">Trier :</span>
                <select id="gallerySortSelect" class="form-select form-select-sm" style="width: auto;" onchange="applySorting()">
                    <option value="default">Par défaut (Catégorie & Nom)</option>
                    <option value="name_asc">Nom alphabétique (A &rarr; Z)</option>
                    <option value="name_desc">Nom alphabétique (Z &rarr; A)</option>
                    <option value="size_desc">Poids (Du plus lourd)</option>
                    <option value="size_asc">Poids (Du plus léger)</option>
                </select>
            </div>
        </div>

        <!-- Description contextuelle de la catégorie courante -->
        <div id="currentCategoryDescription" class="mt-2 pt-2 border-top text-secondary small d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-info text-primary"></i>
            <span id="catDescText"><?= htmlspecialchars($categoryConfig['all']['desc']) ?></span>
        </div>
    </div>
</div>

<!-- ==========================================
     TABLER PHOTOGRID CONTAINER
     ========================================== -->
<div class="row g-2 g-md-3" id="photoGridContainer">
    <?php foreach ($galleryItems as $index => $item): ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-2 gallery-item"
         data-id="<?= htmlspecialchars($item['id']) ?>"
         data-index="<?= $index ?>"
         data-category="<?= htmlspecialchars($item['category']) ?>"
         data-title="<?= htmlspecialchars(strtolower($item['title'])) ?>"
         data-filename="<?= htmlspecialchars(strtolower($item['filename'])) ?>"
         data-size="<?= $item['size'] ?>"
         data-mtime="<?= $item['mtime'] ?>">
        
        <div class="card card-sm gallery-card h-100 shadow-sm cursor-pointer" onclick="openLightbox(<?= $index ?>)">
            <!-- Ratio 1:1 Carré parfait pour photogrid Tabler -->
            <div class="ratio ratio-1x1 gallery-thumb-container">
                <img src="<?= htmlspecialchars($item['web_path']) ?>"
                     alt="<?= htmlspecialchars($item['title']) ?>"
                     loading="lazy"
                     decoding="async"
                     class="w-100 h-100 object-fit-cover">

                <!-- Overlay interactif au survol -->
                <div class="gallery-overlay">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <span class="badge <?= htmlspecialchars($item['category_badge']) ?> rounded-pill small" style="font-size:0.68rem; padding:0.25rem 0.5rem;">
                            <i class="fa-solid <?= htmlspecialchars($item['category_icon']) ?> me-1"></i><?= htmlspecialchars($item['category_label']) ?>
                        </span>
                        <span class="badge bg-dark text-white rounded-pill small" style="font-size:0.65rem; background:rgba(0,0,0,0.65)!important;">
                            <?= htmlspecialchars($item['ext']) ?>
                        </span>
                    </div>

                    <div class="text-center my-auto">
                        <button type="button" class="gallery-zoom-btn" title="Agrandir en haute définition">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center w-100 text-white" style="font-size:0.72rem;">
                        <span class="text-truncate me-1" style="max-width: 65%;"><i class="fa-regular fa-file me-1"></i><?= htmlspecialchars($item['filename']) ?></span>
                        <span class="badge bg-dark-lt text-white" style="font-size:0.65rem;"><?= htmlspecialchars($item['size_human']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Légende sous la carte -->
            <div class="card-body p-2 bg-body">
                <div class="fw-bold text-dark text-truncate small" title="<?= htmlspecialchars($item['title']) ?>">
                    <?= htmlspecialchars($item['title']) ?>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1 text-secondary" style="font-size:0.72rem;">
                    <span class="text-truncate"><?= htmlspecialchars($item['category_label']) ?></span>
                    <span class="text-muted font-monospace"><?= htmlspecialchars($item['size_human']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ÉTAT VIDE SI RECHERCHE SANS RÉSULTAT -->
<div id="galleryEmptyState" class="card gallery-empty-state border-0 shadow-sm mt-3" style="display:none;">
    <div class="empty">
        <div class="empty-icon text-muted">
            <i class="fa-solid fa-image-slash fa-3x"></i>
        </div>
        <p class="empty-title fw-bold fs-2 text-dark mt-2">Aucune estampe trouvée</p>
        <p class="empty-subtitle text-secondary">
            Aucune illustration ne correspond à vos filtres actuels ou à votre terme de recherche.
        </p>
        <div class="empty-action mt-3">
            <button class="btn btn-outline-primary" onclick="resetGalleryFilters()">
                <i class="fa-solid fa-rotate-left me-1"></i> Réinitialiser les filtres
            </button>
        </div>
    </div>
</div>

<!-- ==========================================
     MODALE LIGHTBOX INTERACTIVE (HAUTE DÉFINITION)
     ========================================== -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <!-- Header Lightbox -->
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span id="lbCategoryBadge" class="badge bg-danger rounded-pill px-2 py-1"></span>
                    <h5 class="modal-title m-0 text-white fw-bold text-truncate" id="lbTitle" style="max-width: 50vw;">Titre de l'illustration</h5>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark-lt text-white font-monospace" id="lbCounter">1 / 238</span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>

            <!-- Corps Lightbox avec Vue HD & Flèches Précédent/Suivant -->
            <div class="modal-body p-2 p-md-3">
                <div class="lightbox-img-wrapper">
                    <!-- Flèche Gauche (Précédent) -->
                    <button type="button" class="lightbox-nav-btn prev-btn" onclick="prevImage()" title="Image précédente (Touche Gauche &larr;)">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <!-- Image Principale HD -->
                    <img id="lbMainImg" src="" alt="Aperçu HD" class="img-fluid">

                    <!-- Flèche Droite (Suivant) -->
                    <button type="button" class="lightbox-nav-btn next-btn" onclick="nextImage()" title="Image suivante (Touche Droite &rarr;)">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Footer Lightbox avec Métadonnées et Actions Rapides -->
            <div class="modal-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small">
                    <div><i class="fa-regular fa-file me-1 text-light"></i><span id="lbFilename" class="text-white font-monospace"></span></div>
                    <div><i class="fa-solid fa-weight-hanging me-1 text-light"></i><span id="lbSize" class="text-white"></span></div>
                    <div><i class="fa-solid fa-link me-1 text-light"></i><span id="lbPath" class="text-white font-monospace small"></span></div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-dark text-white border-secondary" onclick="copyAssetPath()" title="Copier le chemin relatif de l'image">
                        <i class="fa-solid fa-copy me-1"></i> <span id="btnCopyText">Copier chemin</span>
                    </button>
                    <a id="lbOpenBtn" href="#" target="_blank" class="btn btn-sm btn-outline-light" title="Ouvrir l'image en pleine résolution dans un nouvel onglet">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Plein écran
                    </a>
                    <a id="lbDownloadBtn" href="#" download class="btn btn-sm btn-danger shadow-sm" title="Télécharger l'estampe sur votre appareil">
                        <i class="fa-solid fa-download me-1"></i> Télécharger
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     MOTEUR JAVASCRIPT DU PHOTOGRID & LIGHTBOX
     ========================================== -->
<script>
(function() {
    // Données complètes transmises par PHP
    const galleryItems = <?= $galleryJson ?>;
    const categoryDescriptions = <?= json_encode(array_map(fn($c) => $c['desc'], $categoryConfig)) ?>;

    let currentFilter = 'all';
    let currentSearch = '';
    let currentActiveIndex = 0;
    let filteredIndices = [];

    // Sélecteurs DOM
    const gridContainer = document.getElementById('photoGridContainer');
    const searchInput = document.getElementById('gallerySearchInput');
    const emptyState = document.getElementById('galleryEmptyState');
    const statVisible = document.getElementById('statVisibleCount');
    const catDescText = document.getElementById('catDescText');

    // Modale Tabler / Bootstrap
    const modalEl = document.getElementById('galleryModal');
    let bsModal = null;

    function init() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bsModal = new bootstrap.Modal(modalEl);
        }

        // Initialiser la liste filtrée initiale
        updateFilteredList();

        // Écouteur recherche temps réel
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                currentSearch = e.target.value.trim().toLowerCase();
                applyFilters();
            });
        }

        // Navigation clavier pour Lightbox
        document.addEventListener('keydown', function(e) {
            if (!modalEl.classList.contains('show')) return;
            if (e.key === 'ArrowLeft') {
                prevImage();
            } else if (e.key === 'ArrowRight') {
                nextImage();
            }
        });
    }

    // Filtre par catégorie
    window.filterCategory = function(category, element) {
        currentFilter = category;

        // Mise à jour visuelle des boutons nav
        document.querySelectorAll('#categoryFilterList .nav-link').forEach(btn => btn.classList.remove('active'));
        if (element) {
            element.classList.add('active');
        }

        // Mise à jour de la description de la catégorie
        if (catDescText && categoryDescriptions[category]) {
            catDescText.textContent = categoryDescriptions[category];
        }

        applyFilters();
    };

    // Application combinée Catégorie + Recherche textuelle
    function applyFilters() {
        const items = document.querySelectorAll('.gallery-item');
        let visibleCount = 0;
        filteredIndices = [];

        items.forEach(el => {
            const cat = el.getAttribute('data-category');
            const title = el.getAttribute('data-title') || '';
            const filename = el.getAttribute('data-filename') || '';
            const idx = parseInt(el.getAttribute('data-index'), 10);

            const matchesCategory = (currentFilter === 'all' || cat === currentFilter);
            const matchesSearch = (!currentSearch || title.includes(currentSearch) || filename.includes(currentSearch) || cat.includes(currentSearch));

            if (matchesCategory && matchesSearch) {
                el.style.display = '';
                visibleCount++;
                filteredIndices.push(idx);
            } else {
                el.style.display = 'none';
            }
        });

        // Mise à jour des compteurs et état vide
        if (statVisible) statVisible.textContent = visibleCount;
        if (emptyState) {
            emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    function updateFilteredList() {
        filteredIndices = galleryItems.map((_, i) => i);
    }

    // Réinitialisation des filtres
    window.resetGalleryFilters = function() {
        currentFilter = 'all';
        currentSearch = '';
        if (searchInput) searchInput.value = '';

        document.querySelectorAll('#categoryFilterList .nav-link').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-filter') === 'all');
        });

        if (catDescText && categoryDescriptions['all']) {
            catDescText.textContent = categoryDescriptions['all'];
        }

        const sortSelect = document.getElementById('gallerySortSelect');
        if (sortSelect) sortSelect.value = 'default';

        applySorting();
        applyFilters();
    };

    // Tri dynamique des cartes dans le DOM
    window.applySorting = function() {
        const sortSelect = document.getElementById('gallerySortSelect');
        const mode = sortSelect ? sortSelect.value : 'default';
        const items = Array.from(document.querySelectorAll('.gallery-item'));

        items.sort((a, b) => {
            const idxA = parseInt(a.getAttribute('data-index'), 10);
            const idxB = parseInt(b.getAttribute('data-index'), 10);
            const itemA = galleryItems[idxA];
            const itemB = galleryItems[idxB];

            if (mode === 'name_asc') {
                return itemA.title.localeCompare(itemB.title, 'fr', { sensitivity: 'base' });
            } else if (mode === 'name_desc') {
                return itemB.title.localeCompare(itemA.title, 'fr', { sensitivity: 'base' });
            } else if (mode === 'size_desc') {
                return itemB.size - itemA.size;
            } else if (mode === 'size_asc') {
                return itemA.size - itemB.size;
            } else {
                // Par défaut : ordre initial
                return idxA - idxB;
            }
        });

        items.forEach(el => gridContainer.appendChild(el));
        applyFilters();
    };

    // Ouverture Lightbox
    window.openLightbox = function(index) {
        if (!galleryItems[index]) return;
        currentActiveIndex = index;
        renderLightboxItem();

        if (bsModal) {
            bsModal.show();
        } else {
            // Fallback si Bootstrap modal non monté
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    };

    function renderLightboxItem() {
        const item = galleryItems[currentActiveIndex];
        if (!item) return;

        document.getElementById('lbTitle').textContent = item.title;
        document.getElementById('lbMainImg').src = item.web_path;
        document.getElementById('lbFilename').textContent = item.filename;
        document.getElementById('lbSize').textContent = item.size_human + ' (' + item.ext + ')';
        document.getElementById('lbPath').textContent = item.web_path;
        document.getElementById('lbOpenBtn').href = item.web_path;
        document.getElementById('lbDownloadBtn').href = item.web_path;
        document.getElementById('lbDownloadBtn').download = item.filename;

        const badge = document.getElementById('lbCategoryBadge');
        if (badge) {
            badge.className = 'badge ' + item.category_badge + ' rounded-pill px-2 py-1';
            badge.innerHTML = '<i class="fa-solid ' + item.category_icon + ' me-1"></i>' + item.category_label;
        }

        // Compteur de position dans la sélection courante
        const posInFiltered = filteredIndices.indexOf(currentActiveIndex);
        const currentPos = (posInFiltered >= 0) ? (posInFiltered + 1) : 1;
        const totalInFilter = (filteredIndices.length > 0) ? filteredIndices.length : galleryItems.length;
        document.getElementById('lbCounter').textContent = currentPos + ' / ' + totalInFilter;

        // Réinitialiser le texte du bouton copier
        const copyBtn = document.getElementById('btnCopyText');
        if (copyBtn) copyBtn.textContent = 'Copier chemin';
    }

    // Navigation Précédent
    window.prevImage = function() {
        if (filteredIndices.length === 0) return;
        let pos = filteredIndices.indexOf(currentActiveIndex);
        if (pos <= 0) {
            pos = filteredIndices.length - 1;
        } else {
            pos--;
        }
        currentActiveIndex = filteredIndices[pos];
        renderLightboxItem();
    };

    // Navigation Suivant
    window.nextImage = function() {
        if (filteredIndices.length === 0) return;
        let pos = filteredIndices.indexOf(currentActiveIndex);
        if (pos >= filteredIndices.length - 1) {
            pos = 0;
        } else {
            pos++;
        }
        currentActiveIndex = filteredIndices[pos];
        renderLightboxItem();
    };

    // Copie du chemin de l'illustration
    window.copyAssetPath = function() {
        const item = galleryItems[currentActiveIndex];
        if (!item) return;
        navigator.clipboard.writeText(item.web_path).then(() => {
            const copyBtn = document.getElementById('btnCopyText');
            if (copyBtn) {
                copyBtn.textContent = 'Copié !';
                setTimeout(() => { copyBtn.textContent = 'Copier chemin'; }, 2000);
            }
        }).catch(err => {
            console.error('Erreur copie presse-papier:', err);
        });
    };

    // Lancement au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<?php if (!$isAuth): ?>
            </div><!-- /container-xl -->
        </main>

        <footer class="footer footer-transparent d-print-none py-4 border-top bg-white mt-auto">
            <div class="container-xl text-center text-muted small">
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-2 fw-semibold">
                    <a href="/?page=docs" class="text-secondary text-decoration-none hover-primary"><i class="fa-solid fa-book-open me-1"></i>Règles du jeu</a>
                    <span>&bull;</span>
                    <a href="/?page=changelog" class="text-secondary text-decoration-none hover-primary"><i class="fa-solid fa-scroll me-1"></i>Changelog</a>
                    <span>&bull;</span>
                    <a href="/?page=pedagogy" class="text-secondary text-decoration-none hover-primary"><i class="fa-solid fa-graduation-cap me-1"></i>Atelier Pédagogique</a>
                    <span>&bull;</span>
                    <a href="/?page=gallery" class="text-danger text-decoration-none fw-bold"><i class="fa-solid fa-palette me-1"></i>Galerie d'Art</a>
                </div>
                <div>OpenShogun &copy; <?= date('Y') ?> &bull; Jeu de stratégie féodale Sengoku &bull; Moteur PHP 8 &bull; Tabler.io</div>
            </div>
        </footer>
    </div>
    <script src="/public/js/tabler/tabler.min.js"></script>
</body>
</html>
<?php endif; ?>

