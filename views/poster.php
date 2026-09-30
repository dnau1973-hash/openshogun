<?php
/**
 * Vue Dédiée : Mon Affiche Féodale & Fiche Daimyō (Japon Féodal)
 * Immersion historique du Clan, registres de guerre, fiefs provinciaux et actions diplomatiques
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/HonorEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();
$currentUserId = (int)($currentUser['id'] ?? 0);

// Identifiant du joueur ciblé
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;
if ($targetUserId <= 0) {
    $targetUserId = $currentUserId;
}

$isSelf = ($targetUserId === $currentUserId);
$autoEdit = isset($_GET['edit']) && $_GET['edit'] == '1' && $isSelf;

$honorEngine = new HonorEngine();
$profile = $honorEngine->getUserProfile($targetUserId);

// Si le joueur n'existe pas
if (!$profile) {
    ?>
    <div class="container-xl py-4">
        <div class="empty">
            <div class="empty-icon"><span class="fs-1">🏯</span></div>
            <p class="empty-title">Daimyō introuvable</p>
            <p class="empty-subtitle text-secondary">
                Ce seigneur n'est répertorié dans aucun registre féodal ni chronique du Shogunat.
            </p>
            <div class="empty-action">
                <a href="/?page=ranking" class="btn btn-primary">
                    Consulter le Registre des Daimyōs
                </a>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Données du Clan Féodal & Lore historique approfondi
$clanLoreData = [
    'terran' => [
        'name' => 'Clan Oda',
        'kanji' => '織田氏',
        'mon_name' => 'Oda Mokkō (織田木瓜)',
        'mon_symbol' => '🌸',
        'feudal_title' => 'Suzerain d\'Owari & Champion de l\'Unification',
        'historic_context' => "Issu de la province maritime d'Owari, le clan Oda était à l'origine une modeste lignée de gouverneurs délégués (shugodai) au service du clan Shiba durant l'époque Muromachi. Émergeant dans les fracas du Sengoku Jidai sous le commandement impétueux d'Oda Nobunaga, le clan a renversé l'ordre féodal traditionnel par la devise « Tenka Fubu » (天下布武 - Le royaume sous un seul sabre). De la victoire foudroyante d'Okehazama en 1560 à l'édification de l'imprenable forteresse d'Azuchi sur les rives du lac Biwa, les Oda ont brisé les frontières féodales et ouvert la voie vers l'unification du Japon.",
        'doctrine' => "Révolutionnaires militaires et économiques, les Oda furent les premiers à systématiser les armes à feu occidentales (Teppo) apportées à Tanegashima, perfectionnant le tir continu en salve rotative à la bataille de Nagashino (1575). Sur le plan économique, leur édit du Rakuichi Rakuza a aboli les péages et les corporations marchandes étouffantes, créant des marchés libres florissants. Leur organisation martiale permet la synergie exemplaire d'un double développement rural et urbain simultané.",
        'motto_quote' => "« Si le coucou ne chante pas, tue-le. » (鳴かぬなら殺してしまえホトトギス)",
        'motto_meaning' => "Détermination inexorable, innovation tactique sans concession et refus de la fatalité.",
        'banner_gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #3b82f6 100%)',
        'banner_badge_bg' => '#1e40af',
        'accent_color' => '#2563eb',
        'light_bg' => '#eff6ff',
        'pillars' => [
            ['icon' => '⚡', 'title' => 'Tenka Fubu', 'desc' => 'Unification sans merci et autorité centrale'],
            ['icon' => '💥', 'title' => 'Arquebuses Tanegashima', 'desc' => 'Tir rotatif synchronisé et domination balistique'],
            ['icon' => '⚖️', 'title' => 'Rakuichi Rakuza', 'desc' => 'Marchés libres, fin des péages et essor du commerce'],
            ['icon' => '🏗️', 'title' => 'Double Développement', 'desc' => 'Bâtiment urbain et domaine rural érigés de concert']
        ]
    ],
    'vorash' => [
        'name' => 'Clan Takeda',
        'kanji' => '武田氏',
        'mon_name' => 'Takeda Bishi (武田菱)',
        'mon_symbol' => '🐎',
        'feudal_title' => 'Tigre de Kai & Seigneur Suprême de Shinano',
        'historic_context' => "Descendants directs de la glorieuse lignée Seiwa Genji par Minamoto no Yoshimitsu, les Takeda ont régné d'une poigne d'acier sur la rude province de Kai depuis l'époque Kamakura. Sous la houlette de Takeda Shingen, le clan s'est forgé la réputation de force terrestre la plus redoutable de tout l'archipel féodal. Rejetant les murailles de pierre conventionnelles, Shingen professait : « Les hommes sont le château, les hommes sont les murailles, les hommes sont les douves, la bienveillance est l'amie et la haine est l'ennemie. »",
        'doctrine' => "La doctrine martiale Takeda incarne le célèbre étendard du Fūrinkazan (風林火山), inspiré de Sun Tzu : rapides comme le vent, silencieux comme la forêt, dévorants comme le feu, inébranlables comme la montagne. Leur cavalerie rouge d'élite (Akazonae), parée d'armures laquées d'écarlate vif et guidée par les célèbres 24 Généraux de Takeda, transperçait les rangs ennemis lors de charges dévastatrices. Les Takeda sont maîtres des offensives surprises et des razzias pénétrant au cœur des réserves de riz adverses.",
        'motto_quote' => "« Si le coucou ne chante pas, force-le à chanter. » (鳴かぬなら鳴かせてみせようホトトギス)",
        'motto_meaning' => "Puissance de commandement indomptable, discipline équestre et ferveur guerrière au combat.",
        'banner_gradient' => 'linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%)',
        'banner_badge_bg' => '#991b1b',
        'accent_color' => '#dc2626',
        'light_bg' => '#fef2f2',
        'pillars' => [
            ['icon' => '🌪️', 'title' => 'Fūrinkazan', 'desc' => 'Vent véloce, forêt secrète, feu dévorant, montagne d\'airain'],
            ['icon' => '🐎', 'title' => 'Cavalerie Akazonae', 'desc' => 'Charges d\'assaut montées en armures rouges écarlates'],
            ['icon' => '🌾', 'title' => 'Razzias Pénétrantes', 'desc' => '+25% de riz pillé et temps de dressage écourté (-20%)'],
            ['icon' => '🛡️', 'title' => 'La Muraille Humaine', 'desc' => 'Cohésion sans faille et loyauté absolue des samouraïs']
        ]
    ],
    'aethelis' => [
        'name' => 'Clan Tokugawa',
        'kanji' => '徳川氏',
        'mon_name' => 'Mitsuba Aoi (三つ葉葵)',
        'mon_symbol' => '⛩️',
        'feudal_title' => 'Généralissime de Mikawa & Fondateur du Shōgunat d\'Edo',
        'historic_context' => "Issu de la noble maison Matsudaira de la province disputée de Mikawa, le clan Tokugawa a façonné son destin à travers l'endurance, la diplomatie avisée et la patience face aux tempêtes du Sengoku Jidai. Conduit par le génie politique de Tokugawa Ieyasu, le clan triompha à la bataille décisive de Sekigahara en 1600. Investi du titre suprême de Shōgun en 1603, Ieyasu déplaça le cœur du pouvoir vers Edo (Tokyo) et instaura deux siècles et demi de paix ininterrompue sous la Pax Tokugawa.",
        'doctrine' => "Le clan Tokugawa est le maître incontesté de la résilience, du renseignement d'État et des fortifications inviolables. Leurs bastions disposent de doubles murailles, de douves inondables et de réseaux de cachettes souterraines doublées déjouant tout siège prolongé. Sous les ordres d'Hattori Hanzō, les légendaires Shinobis d'Iga veillent dans l'ombre pour protéger le clan, saboter les complots et accélérer les mouvements de troupes grâce à des sentiers secrets.",
        'motto_quote' => "« Si le coucou ne chante pas, attends qu'il chante. » (鳴かぬなら鳴くまで待とうホトトギス)",
        'motto_meaning' => "Maîtrise souveraine du temps, sagesse patiente et solidité dynastique inébranlable.",
        'banner_gradient' => 'linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #8b5cf6 100%)',
        'banner_badge_bg' => '#5b21b6',
        'accent_color' => '#7c3aed',
        'light_bg' => '#f5f3ff',
        'pillars' => [
            ['icon' => '⏳', 'title' => 'Voie de la Patience', 'desc' => 'Triomphe durable par l\'endurance et la stratégie politique'],
            ['icon' => '🏯', 'title' => 'Bastions Inviolables', 'desc' => 'Cachettes secrètes x2 et défenses fortifiées'],
            ['icon' => '🥷', 'title' => 'Shinobis d\'Iga', 'desc' => 'Réseaux de renseignement et protection d\'Hattori Hanzō'],
            ['icon' => '⛩️', 'title' => 'Pax Tokugawa', 'desc' => 'Marche rapide des armées (+20%) et paix civile prospère']
        ]
    ]
];

$factionKey = $profile['faction'] ?? 'terran';
$clan = $clanLoreData[$factionKey] ?? [
    'name' => $profile['faction_name'] ?? 'Clan Féodal',
    'kanji' => '武家',
    'mon_name' => 'Tomoe Céleste (三つ巴)',
    'mon_symbol' => '🏯',
    'feudal_title' => 'Seigneur Féodal des Terres Provinciales',
    'historic_context' => "Gardiens respectés de leurs domaines provinciaux et héritiers de l'art martial du sabre, les daimyōs de cette bannière défendent l'honneur de leurs ancêtres dans le strict respect du Bushidō et des lois promulguées par le Shogunat.",
    'doctrine' => "Équilibre martial entre piétons ashigaru et cavaliers samouraïs, combiné à une administration rigoureuse des récoltes de riz et des forteresses frontalières.",
    'motto_quote' => "« La droiture et la loyauté forgent le sabre du juste Daimyō. »",
    'motto_meaning' => "Fidélité au clan, honneur dans la victoire et sérénité face au destin.",
    'banner_gradient' => 'linear-gradient(135deg, #1f2937 0%, #374151 50%, #4b5563 100%)',
    'banner_badge_bg' => '#111827',
    'accent_color' => '#4b5563',
    'light_bg' => '#f9fafb',
    'pillars' => [
        ['icon' => '⚔️', 'title' => 'Code du Bushidō', 'desc' => 'Loyauté, bravoure et respect des traditions ancestrales'],
        ['icon' => '🌾', 'title' => 'Terres Fertiles', 'desc' => 'Réserves régulières de riz et approvisionnement maîtrisé']
    ]
];

// Fief principal / Capitale
$capitalPlanet = null;
foreach ($profile['planets'] as $p) {
    if (!empty($p['is_capital'])) {
        $capitalPlanet = $p;
        break;
    }
}
if (!$capitalPlanet && !empty($profile['planets'])) {
    $capitalPlanet = $profile['planets'][0];
}
$capitalCoordX = $capitalPlanet['coord_x'] ?? 0;
$capitalCoordY = $capitalPlanet['coord_y'] ?? 0;
?>

<div class="container-xl py-3">

    <!-- En-tête de page & fil d'Ariane -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-secondary">
                    <span>🏯 Chroniques Féodales du Shogunat</span> &bull; 
                    <span>Registre Officiel des Daimyōs</span>
                </div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Affiche Féodale :</span>
                    <span class="text-dark fw-bold"><?= htmlspecialchars($profile['username']) ?></span>
                    <?php if ($isSelf): ?>
                        <span class="badge bg-primary text-white ms-2" style="font-size:0.75rem;">Votre Affiche (Vous)</span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/?page=ranking" class="btn btn-outline-secondary">
                        <span class="me-1">📜</span> Registre des Rangs
                    </a>
                    <?php if ($isSelf): ?>
                        <a href="/?page=city" class="btn btn-outline-primary">
                            <span class="me-1">🏯</span> Gérer mon Fief
                        </a>
                        <button type="button" onclick="startMottoEdit()" class="btn btn-primary">
                            <span class="me-1">✏️</span> Modifier ma Devise
                        </button>
                    <?php else: ?>
                        <a href="/?page=messages&tab=compose&to=<?= urlencode($profile['username']) ?>" class="btn btn-primary">
                            <span class="me-1">✉️</span> Dépêcher une Missive
                        </a>
                        <a href="/?page=map&x=<?= $capitalCoordX ?>&y=<?= $capitalCoordY ?>" class="btn btn-outline-secondary">
                            <span class="me-1">🗺️</span> Localiser sur la Carte
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- HERO BANNER IMMERSIF DU CLAN FÉODAL -->
    <div class="card shadow-sm border-0 mb-4 overflow-hidden" style="background: <?= $clan['banner_gradient'] ?>; color: #ffffff;">
        <div class="card-body p-4 p-md-5 position-relative">
            <!-- Motif décoratif d'arrière-plan (Sceau et Kanjis traditionnels) -->
            <div style="position: absolute; right: 2%; top: 50%; transform: translateY(-50%); font-size: 11rem; font-weight: 900; opacity: 0.08; pointer-events: none; user-select: none; font-family: serif; line-height: 1;">
                <?= $clan['kanji'] ?>
            </div>

            <div class="row align-items-center position-relative" style="z-index: 2;">
                <!-- Mon / Emblème du Clan -->
                <div class="col-auto text-center mb-3 mb-md-0">
                    <div style="width: 110px; height: 110px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); border: 3px solid rgba(255, 255, 255, 0.4); display: flex; align-items: center; justify-content: center; font-size: 3.5rem; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); margin: 0 auto;">
                        <?= $clan['mon_symbol'] ?>
                    </div>
                    <div class="mt-2 text-uppercase fw-bold text-white-50" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                        <?= htmlspecialchars($clan['mon_name']) ?>
                    </div>
                </div>

                <!-- Informations du Daimyō et du Clan -->
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge" style="background: <?= $clan['banner_badge_bg'] ?>; border: 1px solid rgba(255,255,255,0.3); font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                            <?= htmlspecialchars($clan['name']) ?> &bull; <?= $clan['kanji'] ?>
                        </span>

                        <!-- Statut de présence -->
                        <?php if ($profile['is_online']): ?>
                            <span class="badge bg-success-lt text-white fw-bold d-inline-flex align-items-center gap-1" style="background: rgba(34, 197, 94, 0.25) !important; border: 1px solid #4ade80;">
                                <span class="status-dot status-dot-animated bg-success"></span> Présent au château
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt text-white-50 d-inline-flex align-items-center gap-1" style="background: rgba(255, 255, 255, 0.1) !important;">
                                <span>🌙</span> Absent des terres
                            </span>
                        <?php endif; ?>

                        <!-- Privilèges & Titres de modération/admin -->
                        <?php if ($profile['is_admin']): ?>
                            <span class="badge bg-danger text-white fw-bold" title="Administrateur du Shogunat">
                                👑 Shōgun Suprême
                            </span>
                        <?php elseif ($profile['is_moderator']): ?>
                            <span class="badge bg-warning text-dark fw-bold" title="Magistrat / Metsuke">
                                ⚖️ Magistrat Impérial
                            </span>
                        <?php endif; ?>

                        <!-- Statut de protection débutant -->
                        <?php if ($profile['is_protected']): ?>
                            <span class="badge bg-teal text-white fw-bold d-inline-flex align-items-center gap-1" title="<?= htmlspecialchars($profile['protection_info']['label'] ?? 'Trêve sacrée') ?>">
                                🛡️ Édit de Trêve Sacrée (Protégé)
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="display-6 fw-bold mb-1 text-white">
                        <?= htmlspecialchars($profile['username']) ?>
                    </h2>
                    <div class="fs-4 text-white-50 mb-3" style="font-family: Georgia, serif; font-style: italic;">
                        <?= htmlspecialchars($clan['feudal_title']) ?>
                    </div>

                    <!-- Métriques clés féodales -->
                    <div class="row g-3 g-md-4 pt-2 border-top border-white-20">
                        <div class="col-auto">
                            <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Rang National</div>
                            <div class="fs-3 fw-bold text-warning">
                                #<?= (int)$profile['rank_pos'] ?> <span class="fs-6 fw-normal text-white-50">/ <?= (int)$profile['total_players'] ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Prestige & Points</div>
                            <div class="fs-3 fw-bold text-white">
                                <?= number_format($profile['points'], 0, ',', ' ') ?> <span class="fs-6 fw-normal text-white-50">pts</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Alliance Féodale</div>
                            <div class="fs-3 fw-bold">
                                <?php if (!empty($profile['alliance_tag'])): ?>
                                    <a href="/?page=alliance" class="text-white text-decoration-none hover-underline">
                                        [<?= htmlspecialchars($profile['alliance_tag']) ?>] <?= htmlspecialchars($profile['alliance_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-white-50 fs-5 fw-normal">Sans suzeraineté</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Fiefs & Domaines</div>
                            <div class="fs-3 fw-bold text-white">
                                <?= count($profile['planets']) ?> <span class="fs-6 fw-normal text-white-50">province(s)</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Distinctions</div>
                            <div class="fs-3 fw-bold text-warning">
                                <?= (int)$profile['medals_count'] ?> <span class="fs-6 fw-normal text-white-50">médaille(s)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Rapides -->
                <div class="col-12 col-lg-auto mt-4 mt-lg-0 text-lg-end">
                    <div class="d-flex flex-wrap flex-lg-column gap-2 justify-content-start justify-content-lg-end">
                        <?php if (!$isSelf): ?>
                            <a href="/?page=messages&tab=compose&to=<?= urlencode($profile['username']) ?>" class="btn btn-light shadow-sm text-dark fw-bold">
                                <span class="me-1">📜</span> Écrire une Missive
                            </a>
                            <a href="/?page=chat&whisper=<?= urlencode($profile['username']) ?>" class="btn btn-outline-light">
                                <span class="me-1">💬</span> Chuchoter au Salon
                            </a>
                            <a href="/?page=fleet&target_x=<?= $capitalCoordX ?>&target_y=<?= $capitalCoordY ?>" class="btn btn-danger shadow-sm fw-bold">
                                <span class="me-1">⚔️</span> Lancer une Expédition
                            </a>
                        <?php else: ?>
                            <button type="button" onclick="startMottoEdit()" class="btn btn-light text-dark fw-bold shadow-sm">
                                <span class="me-1">✏️</span> Modifier ma Devise
                            </button>
                            <a href="/?page=empire" class="btn btn-outline-light">
                                <span class="me-1">👑</span> Bilan de l'Empire
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Citation & Philosophie du Clan -->
        <div class="px-4 py-3 border-top border-white-20 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: rgba(0, 0, 0, 0.2);">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-3">🏮</span>
                <div>
                    <span class="fw-bold text-warning me-2">Maxime Ancestrale :</span>
                    <span class="fst-italic text-white"><?= htmlspecialchars($clan['motto_quote']) ?></span>
                </div>
            </div>
            <div class="small text-white-50">
                <?= htmlspecialchars($clan['motto_meaning']) ?>
            </div>
        </div>
    </div>

    <!-- CORPS DE LA PAGE EN 2 COLONNES -->
    <div class="row g-4">

        <!-- COLONNE GAUCHE (7/12) : NARRATION HISTORIQUE & DEVISE -->
        <div class="col-12 col-lg-7">

            <!-- PARCHEMIN DE LA DEVISE DU DAIMYŌ -->
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-light-lt d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>📜</span> Parchemin &amp; Devise Personnelle du Daimyō
                    </h3>
                    <?php if ($isSelf): ?>
                        <button type="button" id="btnToggleEditMotto" onclick="toggleMottoEditBox()" class="btn btn-sm btn-outline-primary">
                            ✏️ Éditer ma Devise
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <!-- Vue lecture de la devise -->
                    <div id="mottoDisplayBox" class="p-3 rounded-2" style="background: #fafaf9; border-left: 5px solid <?= $clan['accent_color'] ?>; border: 1px solid #e7e5e4; border-left-width: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <p class="fs-4 fst-italic text-dark mb-0" id="mottoContentText" style="font-family: Georgia, serif; line-height: 1.6;">
                            « <?= nl2br(htmlspecialchars($profile['bio'])) ?> »
                        </p>
                    </div>

                    <?php if ($isSelf): ?>
                        <!-- Formulaire d'édition de la devise -->
                        <div id="mottoEditBox" class="mt-3" style="display: <?= $autoEdit ? 'block' : 'none' ?>;">
                            <div class="mb-2">
                                <label for="mottoInputArea" class="form-label fw-bold text-dark">
                                    Gravez votre serment de guerre dans les registres du Shogunat :
                                </label>
                                <textarea id="mottoInputArea" class="form-control" rows="3" maxlength="1000" placeholder="Rédigez votre devise, maxime ou proclamation de guerre..."><?= htmlspecialchars($profile['bio']) ?></textarea>
                                <div class="form-text text-secondary">
                                    Visible par tous les daimyōs sur votre affiche officielle et dans la salle du trône Tenshu. Max 1000 caractères.
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" onclick="cancelMottoEdit()" class="btn btn-secondary">
                                    Annuler
                                </button>
                                <button type="button" id="btnSaveMotto" onclick="saveMottoAjax()" class="btn btn-primary">
                                    <span>💾</span> Enregistrer la Devise
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHRONIQUE & DOCTRINE DU CLAN (JAPON FÉODAL) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>⛩️</span> Chroniques &amp; Doctrine Militaire du <?= htmlspecialchars($clan['name']) ?>
                    </h3>
                    <span class="badge text-white" style="background: <?= $clan['accent_color'] ?>;">
                        <?= $clan['kanji'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Origines et Contexte Historique -->
                    <div class="mb-4">
                        <h4 class="text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                            Origines &amp; Rôle durant les Époques Sengoku &amp; Muromachi
                        </h4>
                        <p class="text-dark" style="line-height: 1.7; text-align: justify;">
                            <?= htmlspecialchars($clan['historic_context']) ?>
                        </p>
                    </div>

                    <!-- Doctrine Militaire et Économique -->
                    <div class="mb-4">
                        <h4 class="text-uppercase fw-bold text-secondary mb-2" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                            Doctrine Stratégique, Spécialités &amp; Économie de Guerre
                        </h4>
                        <p class="text-dark" style="line-height: 1.7; text-align: justify;">
                            <?= htmlspecialchars($clan['doctrine']) ?>
                        </p>
                    </div>

                    <!-- Les Piliers du Clan -->
                    <div>
                        <h4 class="text-uppercase fw-bold text-secondary mb-3" style="font-size: 0.8rem; letter-spacing: 0.05em;">
                            Piliers &amp; Atouts Fondateurs du Clan
                        </h4>
                        <div class="row g-3">
                            <?php foreach ($clan['pillars'] as $pillar): ?>
                                <div class="col-12 col-md-6">
                                    <div class="p-3 border rounded-3 h-100" style="background: <?= $clan['light_bg'] ?>; border-color: rgba(0,0,0,0.06) !important;">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="fs-2"><?= $pillar['icon'] ?></span>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($pillar['title']) ?></div>
                                                <div class="small text-secondary mt-1"><?= htmlspecialchars($pillar['desc']) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FIEFS & DOMAINES PROVINCIAUX -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>🏯</span> Fiefs &amp; Domaines Provinciaux (<?= count($profile['planets']) ?>)
                    </h3>
                    <span class="text-secondary small">Cadastre Impérial</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr>
                                <th>Domaine</th>
                                <th>Statut</th>
                                <th>Coordonnées</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profile['planets'] as $fief): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fs-3"><?= !empty($fief['is_capital']) ? '🏯' : '🌾' ?></span>
                                            <div>
                                                <div class="fw-bold text-dark">
                                                    <?= htmlspecialchars($fief['name']) ?>
                                                </div>
                                                <div class="text-secondary small">
                                                    <?= htmlspecialchars($fief['planet_type'] ?? 'Province fertile') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($fief['is_capital'])): ?>
                                            <span class="badge bg-warning text-dark fw-bold">
                                                👑 Capitale Provinciale
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-secondary">
                                                Fief Secondaire
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/?page=map&x=<?= (int)$fief['coord_x'] ?>&y=<?= (int)$fief['coord_y'] ?>" class="badge bg-blue-lt text-blue text-decoration-none fw-bold" title="Voir sur la carte">
                                            [<?= (int)$fief['coord_x'] ?> : <?= (int)$fief['coord_y'] ?>]
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end">
                                            <a href="/?page=map&x=<?= (int)$fief['coord_x'] ?>&y=<?= (int)$fief['coord_y'] ?>" class="btn btn-sm btn-outline-secondary" title="Centrer la carte">
                                                🗺️ Carte
                                            </a>
                                            <?php if (!$isSelf): ?>
                                                <a href="/?page=fleet&target_x=<?= (int)$fief['coord_x'] ?>&target_y=<?= (int)$fief['coord_y'] ?>" class="btn btn-sm btn-outline-danger" title="Lancer une armée vers ce fief">
                                                    ⚔️ Expédition
                                                </a>
                                            <?php else: ?>
                                                <a href="/?page=city" class="btn btn-sm btn-outline-primary" title="Visiter ce domaine">
                                                    🏯 Visiter
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- COLONNE DROITE (5/12) : PERFORMANCES DE GUERRE & MÉDAILLES -->
        <div class="col-12 col-lg-5">

            <!-- STATISTIQUES HEBDOMADAIRES DU SHOGUNAT -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>⚔️</span> Campagne Militaire de la Semaine
                    </h3>
                    <span class="badge bg-primary text-white">Semaine <?= date('W') ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Progression -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #2563eb !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Essor Hebdomadaire</div>
                                <div class="fs-2 fw-bold text-primary mt-1">
                                    +<?= number_format($profile['weekly_stats']['progression'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">points gagnés</div>
                            </div>
                        </div>

                        <!-- Attaque -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #dc2626 !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Points Conquête</div>
                                <div class="fs-2 fw-bold text-danger mt-1">
                                    <?= number_format($profile['weekly_stats']['attack_points'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">dégâts infligés</div>
                            </div>
                        </div>

                        <!-- Défense -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #16a34a !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Défense Héroïque</div>
                                <div class="fs-2 fw-bold text-success mt-1">
                                    <?= number_format($profile['weekly_stats']['defense_points'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">dégâts repoussés</div>
                            </div>
                        </div>

                        <!-- Pillage -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #9333ea !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Riz Pillé Hebdo</div>
                                <div class="fs-2 fw-bold text-purple mt-1" style="color: #9333ea;">
                                    <?= number_format($profile['weekly_stats']['raid_resources'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">ressources saisies</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANTHÉON DES MÉDAILLES D'HONNEUR (STYLE TRAVIAN) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>🎖️</span> Vitrine des Médailles de Guerre
                    </h3>
                    <span class="badge bg-warning text-dark fw-bold">
                        <?= (int)$profile['medals_count'] ?> distinction(s)
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($profile['medals'])): ?>
                        <div class="text-center py-4 text-secondary">
                            <span class="fs-1 d-block mb-2">📜</span>
                            <div class="fw-bold">Aucune médaille de guerre décernée</div>
                            <div class="small text-muted mt-1">
                                Les médailles sont remises chaque dimanche soir aux daimyōs figurant dans le Top 10 des catégories militaires et d'essor.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($profile['medals'] as $medal): ?>
                                <div class="p-3 border rounded-3 d-flex align-items-start gap-3 bg-white shadow-xs" style="border-left: 4px solid <?= $medal['color'] ?> !important;">
                                    <div class="fs-1" style="line-height: 1;">
                                        <?= $medal['icon'] ?>
                                    </div>
                                    <div class="flex-fill">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark">
                                                <?= htmlspecialchars($medal['category_label']) ?> &bull; Rang #<?= (int)$medal['rank'] ?>
                                            </span>
                                            <span class="badge bg-secondary-lt text-secondary" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($medal['week_code']) ?>
                                            </span>
                                        </div>
                                        <div class="text-secondary small mt-1">
                                            <?= htmlspecialchars($medal['description'] ?? 'Décret officiel du Shogunat.') ?>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                            Décernée le <?= date('d/m/Y', strtotime($medal['awarded_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REGISTRE DIPLOMATIQUE & RELATIONS -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0">
                        <span>🤝</span> Registre Diplomatique
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Statut du Daimyō :</span>
                            <span class="fw-bold text-dark">
                                <?= $profile['is_bot'] ? 'Gouverneur IA' : 'Seigneur Humain' ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Arrivée au Shogunat :</span>
                            <span class="fw-bold text-dark">
                                <?= date('d/m/Y à H:i', strtotime($profile['created_at'])) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Dernier mouvement de cour :</span>
                            <span class="fw-bold text-dark">
                                <?= date('d/m/Y à H:i', strtotime($profile['last_active'])) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Alliance féodale :</span>
                            <span class="fw-bold text-dark">
                                <?= !empty($profile['alliance_name']) ? htmlspecialchars($profile['alliance_name']) : 'Indépendant' ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- SCRIPT JS POUR L'ÉDITION DYNAMIQUE DE LA DEVISE -->
<script>
function startMottoEdit() {
    const editBox = document.getElementById('mottoEditBox');
    const inputArea = document.getElementById('mottoInputArea');
    if (editBox) {
        editBox.style.display = 'block';
        if (inputArea) {
            inputArea.focus();
            inputArea.select();
            inputArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function toggleMottoEditBox() {
    const editBox = document.getElementById('mottoEditBox');
    if (!editBox) return;
    if (editBox.style.display === 'none' || editBox.style.display === '') {
        startMottoEdit();
    } else {
        cancelMottoEdit();
    }
}

function cancelMottoEdit() {
    const editBox = document.getElementById('mottoEditBox');
    if (editBox) editBox.style.display = 'none';
}

async function saveMottoAjax() {
    const input = document.getElementById('mottoInputArea');
    const btn = document.getElementById('btnSaveMotto');
    if (!input || !btn) return;

    const newBio = input.value.trim();
    btn.disabled = true;
    btn.innerHTML = "<span>⏳</span> Enregistrement...";

    try {
        const formData = new FormData();
        formData.append('action', 'update_bio');
        formData.append('bio', newBio);

        const resp = await fetch('/api/profile.php', {
            method: 'POST',
            body: formData
        });

        const res = await resp.json();
        if (res.success) {
            // Mettre à jour l'affichage sur la page
            const displayEl = document.getElementById('mottoContentText');
            if (displayEl) {
                displayEl.innerText = res.bio ? `« ${res.bio} »` : "« Aucune devise enregistrée. »";
            }
            cancelMottoEdit();

            // Toast de confirmation Tabler.io si disponible
            if (typeof showTablerToast === 'function') {
                showTablerToast(res.message || "Devise mise à jour avec succès !", "success");
            } else if (typeof showModalAlert === 'function') {
                showModalAlert("Décret Féodal", res.message || "Votre devise a été actualisée !", "success");
            } else {
                alert(res.message || "Votre devise a été actualisée !");
            }
        } else {
            throw new Error(res.error || "Impossible d'enregistrer la devise.");
        }
    } catch (err) {
        if (typeof showModalAlert === 'function') {
            showModalAlert("Erreur", err.message, "danger");
        } else {
            alert("Erreur : " + err.message);
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = "<span>💾</span> Enregistrer la Devise";
    }
}

// Déclenchement automatique au chargement si ?edit=1
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($autoEdit): ?>
    startMottoEdit();
    <?php endif; ?>
});
</script>
