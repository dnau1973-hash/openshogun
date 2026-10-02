<?php
/**
 * Vue Dédiée : Mon Affiche Féodale & Fiche Daimyō (Japon Féodal)
 * Immersion historique du Clan, registres de guerre, fiefs provinciaux et actions diplomatiques
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/HonorEngine.php';
require_once __DIR__ . '/../core/AiPromptHelper.php';
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
    <div class="container-fluid px-0 py-4">
        <div class="empty">
            <div class="empty-icon"><i class="fa-solid fa-torii-gate text-secondary fs-1"></i></div>
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
        'mon_symbol' => '<i class="fa-solid fa-certificate text-warning"></i>',
        'feudal_title' => 'Suzerain d\'Owari & Champion de l\'Unification',
        'historic_context' => "Issu de la province maritime d'Owari, le clan Oda était à l'origine une modeste lignée de gouverneurs délégués (shugodai) au service du clan Shiba durant l'époque Muromachi. Émergeant dans les fracas du Sengoku Jidai sous le commandement impétueux d'Oda Nobunaga, le clan a renversé l'ordre féodal traditionnel par la devise « Tenka Fubu » (天下布武 - Le royaume sous un seul sabre). De la victoire foudroyante d'Okehazama en 1560 à l'édification de l'imprenable forteresse d'Azuchi sur les rives du lac Biwa, les Oda ont brisé les frontières féodales et ouvert la voie vers l'unification du Japon.",
        'doctrine' => "Révolutionnaires militaires et économiques, les Oda furent les premiers à systématiser les armes à feu occidentales (Teppo) apportées à Tanegashima, perfectionnant le tir continu en salve rotative à la bataille de Nagashino (1575). Sur le plan économique, leur édit du Rakuichi Rakuza a aboli les péages et les corporations marchandes étouffantes, créant des marchés libres florissants. Leur organisation martiale permet la synergie exemplaire d'un double développement rural et urbain simultané.",
        'motto_quote' => "« Si le coucou ne chante pas, tue-le. » (鳴かぬなら殺してしまえホトトギス)",
        'motto_meaning' => "Détermination inexorable, innovation tactique sans concession et refus de la fatalité.",
        'banner_gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #3b82f6 100%)',
        'banner_badge_bg' => '#1e40af',
        'accent_color' => '#2563eb',
        'light_bg' => '#eff6ff',
        'illustration' => '/public/assets/clans/clan_oda_war_council.jpg',
        'illustration_file' => 'clans/clan_oda_war_council.jpg',
        'pillars' => [
            ['icon' => '<i class="fa-solid fa-bolt text-warning"></i>', 'title' => 'Tenka Fubu', 'desc' => 'Unification sans merci et autorité centrale'],
            ['icon' => '<i class="fa-solid fa-fire text-danger"></i>', 'title' => 'Arquebuses Tanegashima', 'desc' => 'Tir rotatif synchronisé et domination balistique'],
            ['icon' => '<i class="fa-solid fa-scale-balanced text-primary"></i>', 'title' => 'Rakuichi Rakuza', 'desc' => 'Marchés libres, fin des péages et essor du commerce'],
            ['icon' => '<i class="fa-solid fa-hammer text-info"></i>', 'title' => 'Double Développement', 'desc' => 'Bâtiment urbain et domaine rural érigés de concert']
        ]
    ],
    'vorash' => [
        'name' => 'Clan Takeda',
        'kanji' => '武田氏',
        'mon_name' => 'Takeda Bishi (武田菱)',
        'mon_symbol' => '<i class="fa-solid fa-horse text-white"></i>',
        'feudal_title' => 'Tigre de Kai & Seigneur Suprême de Shinano',
        'historic_context' => "Descendants directs de la glorieuse lignée Seiwa Genji par Minamoto no Yoshimitsu, les Takeda ont régné d'une poigne d'acier sur la rude province de Kai depuis l'époque Kamakura. Sous la houlette de Takeda Shingen, le clan s'est forgé la réputation de force terrestre la plus redoutable de tout l'archipel féodal. Rejetant les murailles de pierre conventionnelles, Shingen professait : « Les hommes sont le château, les hommes sont les murailles, les hommes sont les douves, la bienveillance est l'amie et la haine est l'ennemie. »",
        'doctrine' => "La doctrine martiale Takeda incarne le célèbre étendard du Fūrinkazan (風林火山), inspiré de Sun Tzu : rapides comme le vent, silencieux comme la forêt, dévorants comme le feu, inébranlables comme la montagne. Leur cavalerie rouge d'élite (Akazonae), parée d'armures laquées d'écarlate vif et guidée par les célèbres 24 Généraux de Takeda, transperçait les rangs ennemis lors de charges dévastatrices. Les Takeda sont maîtres des offensives surprises et des razzias pénétrant au cœur des réserves de riz adverses.",
        'motto_quote' => "« Si le coucou ne chante pas, force-le à chanter. » (鳴かぬなら鳴かせてみせようホトトギス)",
        'motto_meaning' => "Puissance de commandement indomptable, discipline équestre et ferveur guerrière au combat.",
        'banner_gradient' => 'linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #dc2626 100%)',
        'banner_badge_bg' => '#991b1b',
        'accent_color' => '#dc2626',
        'light_bg' => '#fef2f2',
        'illustration' => '/public/assets/clans/clan_takeda_cavalry_charge.jpg',
        'illustration_file' => 'clans/clan_takeda_cavalry_charge.jpg',
        'pillars' => [
            ['icon' => '<i class="fa-solid fa-wind text-info"></i>', 'title' => 'Fūrinkazan', 'desc' => 'Vent véloce, forêt secrète, feu dévorant, montagne d\'airain'],
            ['icon' => '<i class="fa-solid fa-horse text-danger"></i>', 'title' => 'Cavalerie Akazonae', 'desc' => 'Charges d\'assaut montées en armures rouges écarlates'],
            ['icon' => '<i class="fa-solid fa-wheat-awn text-success"></i>', 'title' => 'Razzias Pénétrantes', 'desc' => '+25% de riz pillé et temps de dressage écourté (-20%)'],
            ['icon' => '<i class="fa-solid fa-shield-halved text-warning"></i>', 'title' => 'La Muraille Humaine', 'desc' => 'Cohésion sans faille et loyauté absolue des samouraïs']
        ]
    ],
    'aethelis' => [
        'name' => 'Clan Tokugawa',
        'kanji' => '徳川氏',
        'mon_name' => 'Mitsuba Aoi (三つ葉葵)',
        'mon_symbol' => '<i class="fa-solid fa-torii-gate text-white"></i>',
        'feudal_title' => 'Généralissime de Mikawa & Fondateur du Shōgunat d\'Edo',
        'historic_context' => "Issu de la noble maison Matsudaira de la province disputée de Mikawa, le clan Tokugawa a façonné son destin à travers l'endurance, la diplomatie avisée et la patience face aux tempêtes du Sengoku Jidai. Conduit par le génie politique de Tokugawa Ieyasu, le clan triompha à la bataille décisive de Sekigahara en 1600. Investi du titre suprême de Shōgun en 1603, Ieyasu déplaça le cœur du pouvoir vers Edo (Tokyo) et instaura deux siècles et demi de paix ininterrompue sous la Pax Tokugawa.",
        'doctrine' => "Le clan Tokugawa est le maître incontesté de la résilience, du renseignement d'État et des fortifications inviolables. Leurs bastions disposent de doubles murailles, de douves inondables et de réseaux de cachettes souterraines doublées déjouant tout siège prolongé. Sous les ordres d'Hattori Hanzō, les légendaires Shinobis d'Iga veillent dans l'ombre pour protéger le clan, saboter les complots et accélérer les mouvements de troupes grâce à des sentiers secrets.",
        'motto_quote' => "« Si le coucou ne chante pas, attends qu'il chante. » (鳴かぬなら鳴くまで待とうホトトギス)",
        'motto_meaning' => "Maîtrise souveraine du temps, sagesse patiente et solidité dynastique inébranlable.",
        'banner_gradient' => 'linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #8b5cf6 100%)',
        'banner_badge_bg' => '#5b21b6',
        'accent_color' => '#7c3aed',
        'light_bg' => '#f5f3ff',
        'illustration' => '/public/assets/clans/clan_tokugawa_covert_scout.jpg',
        'illustration_file' => 'clans/clan_tokugawa_covert_scout.jpg',
        'pillars' => [
            ['icon' => '<i class="fa-solid fa-hourglass-half text-warning"></i>', 'title' => 'Voie de la Patience', 'desc' => 'Triomphe durable par l\'endurance et la stratégie politique'],
            ['icon' => '<i class="fa-solid fa-torii-gate text-purple"></i>', 'title' => 'Bastions Inviolables', 'desc' => 'Cachettes secrètes x2 et défenses fortifiées'],
            ['icon' => '<i class="fa-solid fa-user-ninja text-dark"></i>', 'title' => 'Shinobis d\'Iga', 'desc' => 'Réseaux de renseignement et protection d\'Hattori Hanzō'],
            ['icon' => '<i class="fa-solid fa-place-of-worship text-teal"></i>', 'title' => 'Pax Tokugawa', 'desc' => 'Marche rapide des armées (+20%) et paix civile prospère']
        ]
    ]
];

$factionKey = $profile['faction'] ?? 'terran';
$clan = $clanLoreData[$factionKey] ?? [
    'name' => $profile['faction_name'] ?? 'Clan Féodal',
    'kanji' => '武家',
    'mon_name' => 'Tomoe Céleste (三つ巴)',
    'mon_symbol' => '<i class="fa-solid fa-shield-halved text-white"></i>',
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
        ['icon' => '<i class="fa-solid fa-khanda text-danger"></i>', 'title' => 'Code du Bushidō', 'desc' => 'Loyauté, bravoure et respect des traditions ancestrales'],
        ['icon' => '<i class="fa-solid fa-wheat-awn text-success"></i>', 'title' => 'Terres Fertiles', 'desc' => 'Réserves régulières de riz et approvisionnement maîtrisé']
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

<div class="container-fluid px-0 py-2">

    <!-- En-tête de page & fil d'Ariane -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-secondary">
                    <span><i class="fa-solid fa-chess-rook text-danger me-1"></i>Chroniques Féodales du Shogunat</span> &bull; 
                    <span>Registre Officiel des Daimyōs</span>
                </div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Affiche Féodale :</span>
                    <span class="text-dark fw-bold font-game"><?= htmlspecialchars($profile['username']) ?></span>
                    <?php if ($isSelf): ?>
                        <span class="badge bg-primary text-white ms-2" style="font-size:0.75rem;">Votre Affiche (Vous)</span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/?page=ranking" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-scroll text-warning me-1"></i> Registre des Rangs
                    </a>
                    <?php if ($isSelf): ?>
                        <a href="/?page=city" class="btn btn-outline-primary">
                            <i class="fa-solid fa-chess-rook text-danger me-1"></i> Gérer mon Fief
                        </a>
                        <button type="button" onclick="startMottoEdit()" class="btn btn-primary font-game">
                            <i class="fa-solid fa-pen-nib text-light me-1"></i> Modifier ma Devise
                        </button>
                    <?php else: ?>
                        <a href="/?page=messages&tab=compose&to=<?= urlencode($profile['username']) ?>" class="btn btn-primary font-game">
                            <i class="fa-solid fa-envelope text-light me-1"></i> Dépêcher une Missive
                        </a>
                        <a href="/?page=map&x=<?= $capitalCoordX ?>&y=<?= $capitalCoordY ?>" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-map-location-dot text-success me-1"></i> Localiser sur la Carte
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
                <!-- Avatar du Daimyō & Mon / Emblème du Clan -->
                <div class="col-auto text-center mb-3 mb-md-0">
                    <div class="position-relative d-inline-block">
                        <?php 
                        $rawAvatar = !empty($profile['avatar']) ? $profile['avatar'] : (!empty($_SESSION['user']['avatar']) ? $_SESSION['user']['avatar'] : '');
                        if (empty($rawAvatar)) {
                            $avatarPath = '/public/assets/hero_samurai.jpg';
                        } else {
                            $avatarPath = str_starts_with($rawAvatar, '/assets/') ? '/public' . $rawAvatar : $rawAvatar;
                        }
                        $avatarDisplayUrl = $avatarPath . (str_contains($avatarPath, '?') ? '&' : '?') . 'v=' . time();
                        ?>
                        <!-- Médaillon Avatar Circulaire avec bordure dorée/clan -->
                        <div id="daimyo-avatar-container" 
                             style="width: 120px; height: 120px; border-radius: 50%; background-image: url('<?= htmlspecialchars($avatarDisplayUrl) ?>'); background-size: cover; background-position: center; border: 3px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4); margin: 0 auto; position: relative;"
                             title="Avatar officiel de <?= htmlspecialchars($profile['username']) ?>">
                            
                            <!-- Médaillon Mon du Clan incrusté en bas à droite -->
                            <div style="position: absolute; bottom: -2px; right: -2px; background: <?= $clan['banner_badge_bg'] ?>; border: 2px solid #ffffff; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; box-shadow: 0 2px 8px rgba(0,0,0,0.5);" 
                                 title="Mon du Clan : <?= htmlspecialchars($clan['mon_name']) ?>">
                                <?= $clan['mon_symbol'] ?>
                            </div>
                        </div>

                        <?php if ($isSelf): ?>
                            <!-- Zone d'action pour le téléversement d'un avatar personnalisé -->
                            <div class="mt-2 text-center">
                                <label for="avatar-file-input" class="btn btn-sm btn-light border-0 shadow-sm font-game d-inline-flex align-items-center gap-1 cursor-pointer py-1 px-2" style="font-size: 0.75rem; background: rgba(255, 255, 255, 0.95); color: #1e293b;" title="Téléverser un avatar personnalisé (JPEG, PNG, WEBP max 2 Mo)">
                                    <i class="fa-solid fa-camera text-danger"></i>
                                    <span>Changer l'Avatar</span>
                                </label>
                                <input type="file" id="avatar-file-input" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="handleAvatarUpload(this)">
                                <div id="avatar-upload-status"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1 text-uppercase fw-bold text-white-50 font-game" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                        <?= htmlspecialchars($clan['mon_name']) ?>
                    </div>
                </div>

                <!-- Informations du Daimyō et du Clan -->
                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge font-game" style="background: <?= $clan['banner_badge_bg'] ?>; border: 1px solid rgba(255,255,255,0.3); font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                            <?= htmlspecialchars($clan['name']) ?> &bull; <?= $clan['kanji'] ?>
                        </span>

                        <!-- Statut de présence -->
                        <?php if (!empty($profile['is_online'])): ?>
                            <span class="badge bg-success-lt text-white fw-bold d-inline-flex align-items-center gap-1" style="background: rgba(34, 197, 94, 0.25) !important; border: 1px solid #4ade80;">
                                <span class="status-dot status-dot-animated bg-success"></span> Présent au château
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-lt text-white-50 d-inline-flex align-items-center gap-1" style="background: rgba(255, 255, 255, 0.1) !important;">
                                <i class="fa-solid fa-moon text-white-50"></i> Absent des terres
                            </span>
                        <?php endif; ?>

                        <!-- Privilèges & Titres de modération/admin -->
                        <?php if (!empty($profile['is_admin'])): ?>
                            <span class="badge bg-danger text-white fw-bold font-game" title="Administrateur du Shogunat">
                                <i class="fa-solid fa-crown text-warning me-1"></i> Shōgun Suprême
                            </span>
                        <?php elseif (!empty($profile['is_moderator'])): ?>
                            <span class="badge bg-warning text-dark fw-bold font-game" title="Magistrat / Metsuke">
                                <i class="fa-solid fa-scale-balanced text-dark me-1"></i> Magistrat Impérial
                            </span>
                        <?php endif; ?>

                        <!-- Statut de protection débutant (sécurisé) -->
                        <?php if (!empty($profile['is_protected'])): ?>
                            <span class="badge bg-teal text-white fw-bold d-inline-flex align-items-center gap-1" title="<?= htmlspecialchars($profile['protection_info']['label'] ?? 'Trêve sacrée') ?>">
                                <i class="fa-solid fa-shield-halved text-white me-1"></i> Édit de Trêve Sacrée (Protégé)
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="display-6 fw-bold mb-1 text-white font-game" style="letter-spacing:0.02em;">
                        <?= htmlspecialchars($profile['username']) ?>
                    </h2>
                    <div class="fs-4 text-white-50 mb-3 font-game" style="font-size: 1.1rem !important; opacity: 0.9;">
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
                                <i class="fa-solid fa-envelope me-1"></i> Écrire une Missive
                            </a>
                            <a href="/?page=chat&whisper=<?= urlencode($profile['username']) ?>" class="btn btn-outline-light">
                                <i class="fa-solid fa-comments me-1"></i> Chuchoter au Salon
                            </a>
                            <a href="/?page=fleet&target_x=<?= $capitalCoordX ?>&target_y=<?= $capitalCoordY ?>" class="btn btn-danger shadow-sm fw-bold">
                                <i class="fa-solid fa-khanda me-1"></i> Lancer une Expédition
                            </a>
                        <?php else: ?>
                            <button type="button" onclick="startMottoEdit()" class="btn btn-light text-dark fw-bold shadow-sm">
                                <i class="fa-solid fa-pen-to-square me-1"></i> Modifier ma Devise
                            </button>
                            <a href="/?page=empire" class="btn btn-outline-light">
                                <i class="fa-solid fa-crown me-1"></i> Bilan de l'Empire
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Citation & Philosophie du Clan -->
        <div class="px-4 py-3 border-top border-white-20 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: rgba(0, 0, 0, 0.2);">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-3 text-warning"><i class="fa-solid fa-torii-gate"></i></span>
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
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-scroll text-primary"></i> Parchemin &amp; Devise Personnelle du Daimyō
                    </h3>
                    <?php if ($isSelf): ?>
                        <button type="button" id="btnToggleEditMotto" onclick="toggleMottoEditBox()" class="btn btn-sm btn-outline-primary font-game">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Éditer ma Devise
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
                                <button type="button" id="btnSaveMotto" onclick="saveMottoAjax()" class="btn btn-primary font-game">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer la Devise
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CHRONIQUE & DOCTRINE DU CLAN (JAPON FÉODAL) -->
            <div class="card shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-torii-gate text-primary"></i> Chroniques &amp; Doctrine Militaire du <?= htmlspecialchars($clan['name']) ?>
                    </h3>
                    <span class="badge text-white font-game" style="background: <?= $clan['accent_color'] ?>;">
                        <?= $clan['kanji'] ?>
                    </span>
                </div>

                <?php if (!empty($clan['illustration'])): ?>
                    <div class="position-relative overflow-hidden border-bottom" style="max-height: 280px; background: #0f172a;">
                        <img src="<?= htmlspecialchars($clan['illustration']) ?>" 
                             alt="Chroniques &amp; Doctrine Martiale du <?= htmlspecialchars($clan['name']) ?>" 
                             class="w-100 object-fit-cover" 
                             style="max-height: 280px; object-position: center 30%; display: block;">
                        
                        <!-- Overlay en dégradé féodal avec devise & titre -->
                        <div class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(to top, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.4) 60%, transparent 100%);">
                            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="text-white fw-bold font-game fs-3 mb-0" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
                                        <?= htmlspecialchars($clan['feudal_title']) ?>
                                    </div>
                                    <div class="text-white-50 small font-game fst-italic">
                                        <?= htmlspecialchars($clan['motto_quote']) ?>
                                    </div>
                                </div>
                                <span class="badge bg-dark-lt text-white border border-white-50 small font-game px-2 py-1">
                                    <i class="fa-solid fa-scroll me-1 text-warning"></i> Chronique Officielle
                                </span>
                            </div>
                        </div>

                        <!-- Badge Circulaire Transparence IA Prompts (« ? ») -->
                        <?= class_exists('AiPromptHelper') ? AiPromptHelper::renderBadge($clan['illustration_file'], 'Chroniques Féodales du ' . $clan['name'], $clan['illustration']) : '' ?>
                    </div>
                <?php endif; ?>

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
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-chess-rook text-primary"></i> Fiefs &amp; Domaines Provinciaux (<?= count($profile['planets']) ?>)
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
                                            <span class="fs-3"><?= !empty($fief['is_capital']) ? '<i class="fa-solid fa-crown text-warning"></i>' : '<i class="fa-solid fa-wheat-awn text-success"></i>' ?></span>
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
                                                <i class="fa-solid fa-crown text-warning me-1"></i> Capitale Provinciale
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
                                                <i class="fa-solid fa-map-location-dot me-1"></i> Carte
                                            </a>
                                            <?php if (!$isSelf): ?>
                                                <a href="/?page=fleet&target_x=<?= (int)$fief['coord_x'] ?>&target_y=<?= (int)$fief['coord_y'] ?>" class="btn btn-sm btn-outline-danger font-game" title="Lancer une armée vers ce fief">
                                                    <i class="fa-solid fa-khanda me-1"></i> Expédition
                                                </a>
                                            <?php else: ?>
                                                <a href="/?page=city" class="btn btn-sm btn-outline-primary" title="Visiter ce domaine">
                                                    <i class="fa-solid fa-torii-gate me-1"></i> Visiter
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
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-shield-halved text-danger"></i> Campagne Militaire de la Semaine
                    </h3>
                    <span class="badge bg-primary text-white font-game">Semaine <?= date('W') ?></span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Progression -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #2563eb !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Essor Hebdomadaire</div>
                                <div class="fs-2 fw-bold text-primary mt-1 font-game">
                                    +<?= number_format($profile['weekly_stats']['progression'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">points gagnés</div>
                            </div>
                        </div>

                        <!-- Attaque -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #dc2626 !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Points Conquête</div>
                                <div class="fs-2 fw-bold text-danger mt-1 font-game">
                                    <?= number_format($profile['weekly_stats']['attack_points'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">dégâts infligés</div>
                            </div>
                        </div>

                        <!-- Défense -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #16a34a !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Défense Héroïque</div>
                                <div class="fs-2 fw-bold text-success mt-1 font-game">
                                    <?= number_format($profile['weekly_stats']['defense_points'] ?? 0, 0, ',', ' ') ?>
                                </div>
                                <div class="text-muted small">dégâts repoussés</div>
                            </div>
                        </div>

                        <!-- Pillage -->
                        <div class="col-6">
                            <div class="p-3 border rounded-3 text-center bg-white shadow-xs" style="border-top: 3px solid #9333ea !important;">
                                <div class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem;">Riz Pillé Hebdo</div>
                                <div class="fs-2 fw-bold text-purple mt-1 font-game" style="color: #9333ea;">
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
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-medal text-warning"></i> Vitrine des Médailles de Guerre
                    </h3>
                    <span class="badge bg-warning text-dark fw-bold font-game">
                        <?= (int)$profile['medals_count'] ?> distinction(s)
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($profile['medals'])): ?>
                        <div class="text-center py-4 text-secondary">
                            <i class="fa-solid fa-scroll fs-1 d-block mb-2 text-secondary"></i>
                            <div class="fw-bold font-game">Aucune médaille de guerre décernée</div>
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
                                            <span class="fw-bold text-dark font-game">
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
                    <h3 class="card-title text-dark fw-bold d-flex align-items-center gap-2 m-0 font-game">
                        <i class="fa-solid fa-handshake text-primary me-1"></i> Registre Diplomatique
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
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Enregistrement...';

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
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Enregistrer la Devise';
    }
}

/**
 * Téléversement et prévisualisation instantanée de l'avatar personnalisé
 */
function handleAvatarUpload(input) {
    if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0];

    // Contrôle préliminaire côté client
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert("Format non supporté. Veuillez sélectionner une image JPEG, PNG ou WEBP.");
        input.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert("L'image de l'avatar est trop volumineuse (limite : 2 Mo).");
        input.value = '';
        return;
    }

    const avatarContainer = document.getElementById('daimyo-avatar-container');
    const statusEl = document.getElementById('avatar-upload-status');
    const previousBg = avatarContainer ? avatarContainer.style.backgroundImage : '';

    // 1. Prévisualisation instantanée via FileReader API
    const reader = new FileReader();
    reader.onload = function(e) {
        if (avatarContainer) {
            avatarContainer.style.backgroundImage = `url('${e.target.result}')`;
        }
    };
    reader.readAsDataURL(file);

    if (statusEl) {
        statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-light me-1"></span> <span class="text-white-50" style="font-size:0.7rem;">Envoi...</span>';
    }

    // 2. Téléversement asynchrone AJAX sécurisé
    const formData = new FormData();
    formData.append('action', 'upload_avatar');
    formData.append('avatar', file);

    fetch('/api/profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (avatarContainer) {
                const bustUrl = data.avatar_url + (data.avatar_url.includes('?') ? '&' : '?') + 'v=' + Date.now();
                avatarContainer.style.backgroundImage = `url('${bustUrl}')`;
            }
            if (statusEl) {
                statusEl.innerHTML = '<span class="text-warning fw-bold" style="font-size:0.72rem;"><i class="fa-solid fa-check me-1"></i>Avatar établi !</span>';
                setTimeout(() => { statusEl.innerHTML = ''; }, 4000);
            }
        } else {
            throw new Error(data.error || "Échec de l'enregistrement de l'avatar.");
        }
    })
    .catch(err => {
        if (avatarContainer) {
            avatarContainer.style.backgroundImage = previousBg;
        }
        if (statusEl) {
            statusEl.innerHTML = `<span class="text-danger fw-bold" style="font-size:0.7rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Erreur</span>`;
            setTimeout(() => { statusEl.innerHTML = ''; }, 4000);
        }
        alert("Erreur de téléversement : " + err.message);
    })
    .finally(() => {
        input.value = '';
    });
}

// Déclenchement automatique au chargement si ?edit=1
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($autoEdit): ?>
    startMottoEdit();
    <?php endif; ?>
});
</script>
