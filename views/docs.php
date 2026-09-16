<?php
/**
 * Codex & Documentation Officielle d'OpenShogun
 * Chapitre 1 : Présentation des Troupes & Codex Militaire Féodal
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM units ORDER BY faction, tier ASC");
$allUnits = $stmt->fetchAll();

// Chapitre / Onglet actif
$tab = $_GET['tab'] ?? 'troops';

// Métadonnées enrichies des unités (rôle tactique, points forts/faibles, citations)
$unitTactics = [
    'piquier_ashigaru_yari' => [
        'role' => 'Infanterie de ligne & Anti-Cavalerie',
        'lore_detail' => 'Formés dans la rigueur absolue des réformes militaires d\'Oda Nobunaga, ces fantassins paysans sont armés d\'une pique nagae-yari de plus de 5 mètres. Organisés en rangs serrés, ils forment une haie d\'acier infranchissable capable de briser l\'élan des charges les plus furieuses.',
        'strengths' => 'Coût de recrutement très faible, excellent rapport qualité/prix contre la cavalerie, formation rapide en grand nombre.',
        'weaknesses' => 'Vulnérable face aux tirs d\'arquebuse et aux maîtres d\'armes samouraïs en duel rapproché.',
        'quote' => '« Une forêt de lances ne plie jamais devant l\'orage. »'
    ],
    'arquebusier_oda_tanegashima' => [
        'role' => 'Tir Perforant & Défense de Position',
        'lore_detail' => 'Pionniers dans l\'utilisation massive des mousquets à mèche importés par les marchands portugais à Tanegashima en 1543. Déployés derrière des palissades de bambou tressé (tate), ils délivrent des salves successives impitoyables qui déchiquètent les armures laquées adverses.',
        'strengths' => 'Puissance d\'arrêt défensive colossale (65 déf. infanterie / 55 déf. méca), tir dévastateur à distance.',
        'weaknesses' => 'Lenteur de rechargement, faible en combat de mêlée sans couverture d\'infanterie.',
        'quote' => '« Trois volées de feu suffisent à balayer une dynastie. » — Oda Nobunaga'
    ],
    'samourai_katana' => [
        'role' => 'Assaut d\'Élite & Percée de Lignes',
        'lore_detail' => 'Nobles bretteurs dévoués au code du Bushidō. Vêtus de cuirasses dō-maru renforcées et maniant le katana forgé dans l\'acier tamahagane, ces maîtres d\'armes pénètrent les brèches créées par les arquebusiers pour tailler en pièces les rangs adverses désorganisés.',
        'strengths' => 'Attaque puissante (85), vitesse élevée (8), excellent équilibre offensive/défensive, moral inébranlable.',
        'weaknesses' => 'Coût élevé en fer et en riz, effectifs plus réduits sur le champ de bataille.',
        'quote' => '« La lame est l\'âme vivante du guerrier. »'
    ],
    'garde_hatamoto_armure_lourde' => [
        'role' => 'Garde Rapprochée & Choc Titanesque',
        'lore_detail' => 'Les gardes du corps d\'élite (Hatamoto) sous la bannière directe du Daimyō. Équipés d\'armures lourdes Nanban dō inspirées des armures occidentales à l\'épreuve des balles et arborant de majestueux casques kabuto dorés, ils constituent le rempart ultime du clan Oda.',
        'strengths' => 'Attaque titanesque (210), défenses imprenables (120/150), capacité de fret de 120, brise les forteresses.',
        'weaknesses' => 'Coût colossal en ressources (600 bois, 450 pierre, 250 riz), vitesse de déplacement lente (5).',
        'quote' => '« Devant l\'Hatamoto, même les montagnes s\'écartent. »'
    ],
    'fantassin_leger_takeda' => [
        'role' => 'Fantassin Léger de Raid & Éclaireur',
        'lore_detail' => 'Conscrits des rudes montagnes de la province de Kai, ces fantassins légers sont entraînés à gravir les pentes abruptes et à surprendre les garnisons ennemies. Leur équipement minimaliste leur confère une célérité hors du commun pour piller les réserves de grain.',
        'strengths' => 'Vitesse de marche exceptionnelle (9), grande capacité de pillage (65 pour 90 bois), recrutement ultra rapide (10s).',
        'weaknesses' => 'Défense très faible (20/15), décimé en cas d\'engagement prolongé contre une ligne d\'infanterie lourde.',
        'quote' => '« Rapide comme le vent des montagnes de Kai. »'
    ],
    'archer_yumi_monte' => [
        'role' => 'Tir Mobile & Harcèlement de Cavalerie',
        'lore_detail' => 'Héritiers de l\'antique tradition du Yabusame (tir à l\'arc à cheval), ces cavaliers décochent des volées précises au grand arc yumi tout en caracolant hors de portée des lances ennemies. Ils excellent à couper les lignes de ravitaillement et épuiser les armées en marche.',
        'strengths' => 'Excellente défense anti-cavalerie (60) et anti-infanterie (55), mobilité de tir supérieure, harcèlement parfait.',
        'weaknesses' => 'Attaque modérée (20), capacité de fret limitée (25).',
        'quote' => '« Une flèche dans le ciel, un ennemi qui s\'effondre avant même d\'avoir vu son bourreau. »'
    ],
    'cavalier_rouge_akazonae' => [
        'role' => 'Cavalerie Rouge de Choc Frontal (Akazonae)',
        'lore_detail' => 'La légendaire cavalerie rouge d\'assaut levée par Takeda Shingen. Revêtus d\'armures entièrement laquées de vermillon flamboyant et brandissant de longues lances d\'assaut, ces cavaliers chargent en formations compactes irrésistibles qui terrifient quiconque ose leur faire face.',
        'strengths' => 'Attaque destructrice (95), vitesse rapide (8), bonus passif de raid (+25% de butin chez Takeda), foudroie les troupes à distance.',
        'weaknesses' => 'Vulnérable aux forêts de piques Ashigaru bien retranchées.',
        'quote' => '« Furieux comme le feu, inébranlable comme la montagne. » — Takeda Shingen'
    ],
    'maitre_nodachi_kai' => [
        'role' => 'Maître Bretteur Berserker & Perceur de Siège',
        'lore_detail' => 'Guerriers géants sélectionnés parmi les vétérans des monts de Kai. Brandissant le gigantesque sabre nodachi dont la lame forgée mesure près de deux mètres, ils fauchent d\'un seul moulinet plusieurs combattants et tranchent les jarrets des chevaux ennemis.',
        'strengths' => 'Attaque la plus haute du jeu (225 !), capacité de transport immense (140), pulvérise les armures lourdes ennemies.',
        'weaknesses' => 'Vitesse de déplacement lente (5), coûteux à former au Dojo.',
        'quote' => '« Aucun bouclier n\'a jamais arrêté la coupe d\'un Nodachi de Kai. »'
    ],
    'sentinelle_yari_tokugawa' => [
        'role' => 'Garde de Garnison & Mur de Boucliers',
        'lore_detail' => 'Soldats réguliers voués à la défense tenace des forteresses de Mikawa. Équipés de chapeaux de fer coniques (jingasa) et d\'armures robustes couleur bronze et indigo ornées du blason aux trois feuilles de mauve (Mitsuba Aoi), ils ne reculent jamais d\'un pas.',
        'strengths' => 'Très bonne résilience défensive (38 infanterie / 25 cavalerie), vitesse correcte (8), polyvalent.',
        'weaknesses' => 'Attaque moyenne (42), faible capacité de fret (45).',
        'quote' => '« Un pouce de terre du clan ne sera jamais cédé sans le sang de l\'assaillant. »'
    ],
    'archer_protecteur_muraille' => [
        'role' => 'Tir de Muraille & Défense Imprenable',
        'lore_detail' => 'Archers d\'élite affectés à la surveillance des courtines et des donjons de pierre. Tirant à l\'abri des merlons en chêne et des meurtrières sama, leurs volées de flèches pleuvent sur les assiégeants avec une régularité et une précision meurtrières.',
        'strengths' => 'Défense colossale en garnison (70 infanterie / 65 cavalerie), synergie magistrale avec la Muraille de Cité.',
        'weaknesses' => 'Attaque offensive faible (22), vitesse de marche modeste.',
        'quote' => '« Le château n\'est pas fait de pierres, il est fait des flèches de ses défenseurs. »'
    ],
    'ombre_shinobi_infiltree' => [
        'role' => 'Infiltration Nocturne & Assassinat d\'Élite',
        'lore_detail' => 'Membres des légendaires réseaux de ninjas Iga et Kōga alliés à Tokugawa Ieyasu. Spécialisés dans le sabotage, l\'empoisonnement des puits, le vol de parchemins et l\'élimination ciblée des officiers ennemis sous la lumière blafarde de la lune.',
        'strengths' => 'Attaque foudroyante (90), vitesse suprême (9), furtivité inégalée pour espionner et saboter les fiefs rivaux.',
        'weaknesses' => 'Coût élevé en ressources techniques, vulnérable si cerné en combat frontal ouvert.',
        'quote' => '« Nous sommes l\'ombre qui veille quand le jour s\'éteint. »'
    ],
    'hatamoto_venerable_tokugawa' => [
        'role' => 'Général Vétéran & Bastion Vivant du Domaine',
        'lore_detail' => 'Les vénérables Hatamoto du clan Tokugawa, vétérans des plus grandes batailles de l\'époque Sengoku. Portant la grande armure antique Ō-yoroi avec des plaques dorées et guidant les troupes avec l\'éventail de guerre en fer gunbai, leur simple présence galvanise toute la garnison.',
        'strengths' => 'Meilleures défenses du jeu (140 infanterie / 160 cavalerie !), attaque formidable (200), point d\'ancrage absolu.',
        'weaknesses' => 'Unité la plus coûteuse à former (650 bois, 500 pierre, 300 riz), temps de formation important.',
        'quote' => '« La patience est la clé de la domination sous le Ciel. » — Tokugawa Ieyasu'
    ]
];

// Clans metadata
$clansMeta = [
    'terran' => [
        'id' => 'terran',
        'name' => 'Clan Oda',
        'daimyo' => 'Oda Nobunaga',
        'province' => 'Province d\'Owari & Forteresse d\'Azuchi',
        'mon' => 'Oda Mokkō Mon (Fleur de Coing)',
        'doctrine' => 'Révolution Militaire, Mousquets Tanegashima & Piques Longues',
        'color' => '#3b82f6',
        'border' => '#2563eb',
        'bg_gradient' => 'linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(253, 251, 247, 0.98) 100%)',
        'badge' => '⚡ Double développement simultané & Maîtrise des Armes à Feu',
        'icon' => '🏯'
    ],
    'vorash' => [
        'id' => 'vorash',
        'name' => 'Clan Takeda',
        'daimyo' => 'Takeda Shingen',
        'province' => 'Montagnes de Kai & Forteresse de Tsutsujigasaki',
        'mon' => 'Takeda Hishi (Quatre Losanges)',
        'doctrine' => 'Furinkazan (Vent, Forêt, Feu, Montagne) & Cavalerie Rouge Akazonae',
        'color' => '#ef4444',
        'border' => '#dc2626',
        'bg_gradient' => 'linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(253, 251, 247, 0.98) 100%)',
        'badge' => '🐎 Cavalerie d\'assaut rapide, -20% temps Dojo & +25% de butin en raid',
        'icon' => '🐎'
    ],
    'aethelis' => [
        'id' => 'aethelis',
        'name' => 'Clan Tokugawa',
        'daimyo' => 'Tokugawa Ieyasu',
        'province' => 'Plaines de Mikawa & Château d\'Okazaki',
        'mon' => 'Mitsuba Aoi (Trois Feuilles de Mauve)',
        'doctrine' => 'Patience Inébranlable, Forteresses Imprenables & Ombres Shinobi',
        'color' => '#8b5cf6',
        'border' => '#7c3aed',
        'bg_gradient' => 'linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(253, 251, 247, 0.98) 100%)',
        'badge' => '⛩️ Cachettes secrètes doublées, vitesse de marche +20% & Défenses imprenables',
        'icon' => '⛩️'
    ]
];

// Regroupement des unités par clan
$unitsByClan = [];
foreach ($allUnits as $u) {
    $clanKey = $u['faction'];
    if (!isset($unitsByClan[$clanKey])) {
        $unitsByClan[$clanKey] = [];
    }
    $unitsByClan[$clanKey][] = $u;
}
?>

<div class="docs-container" style="max-width: 1400px; margin: 0 auto; padding: 1.5rem 1rem;">

    <!-- En-tête Héroïque du Codex Féodal -->
    <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--red-primary); background: var(--bg-surface, #fdfbf7); overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.06);">
        <div style="position: relative; padding: 2.25rem 2rem; background: linear-gradient(135deg, rgba(194, 37, 43, 0.07) 0%, rgba(253, 251, 247, 0.98) 70%, rgba(245, 158, 11, 0.08) 100%);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                <div style="max-width: 850px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--red-primary); background: rgba(194,37,43,0.1); padding: 3px 10px; border-radius: 4px;">
                            📜 Codex Impérial du Shogunat
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">&bull; Manuel Stratégique & Art de la Guerre</span>
                    </div>
                    <h1 style="font-size: 2.2rem; margin: 0 0 0.75rem 0; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem;">
                        <span>📖</span> Grande Encyclopédie d'OpenShogun
                    </h1>
                    <p style="font-size: 1.05rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                        Bienvenue, noble Daimyō, dans les archives militaires et administratives du Japon de l'époque Sengoku. 
                        Consultez ici les chroniques complètes des <strong>12 régiments féodaux</strong>, l'architecture des cités castrales, les secrets des 18 parcelles de terroir et les règles martiales régissant la conquête du titre de Shogun.
                    </p>
                </div>
                
                <div style="text-align: right; background: var(--bg-ink, #ede5d5); padding: 1rem 1.5rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Édition Impériale</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: var(--red-primary);">戦国時代 &bull; 1572</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">12 Troupes &bull; 3 Clans &bull; 12 Donjons</div>
                </div>
            </div>

            <!-- Sommaire & Navigation entre Chapitres de la Documentation -->
            <div style="display: flex; gap: 0.6rem; margin-top: 1.75rem; overflow-x: auto; padding-bottom: 0.25rem;">
                <a href="?page=docs&tab=troops" class="btn <?= ($tab === 'troops') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.9rem; padding: 0.6rem 1.1rem; border-radius: 8px;">
                    <span>🥋</span> Chapitre 1 : Présentation des Troupes
                </a>
                <a href="?page=docs&tab=city" class="btn <?= ($tab === 'city') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.9rem; padding: 0.6rem 1.1rem; border-radius: 8px;">
                    <span>🏯</span> Chapitre 2 : Cité & Bâtiments
                </a>
                <a href="?page=docs&tab=resources" class="btn <?= ($tab === 'resources') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.9rem; padding: 0.6rem 1.1rem; border-radius: 8px;">
                    <span>🌾</span> Chapitre 3 : Terroir & Récoltes
                </a>
                <a href="?page=docs&tab=castles" class="btn <?= ($tab === 'castles') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.9rem; padding: 0.6rem 1.1rem; border-radius: 8px;">
                    <span>🏯</span> Chapitre 4 : 12 Donjons Authentiques
                </a>
                <a href="?page=docs&tab=combat" class="btn <?= ($tab === 'combat') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.9rem; padding: 0.6rem 1.1rem; border-radius: 8px;">
                    <span>⚔️</span> Chapitre 5 : Règles du Combat & Sièges
                </a>
            </div>
        </div>
    </div>

    <?php if ($tab === 'troops'): ?>
        <!-- ==========================================================
             CHAPITRE 1 : PRÉSENTATION DES TROUPES & CODEX MILITAIRE
             ========================================================== -->
        
        <!-- Barre de Filtres Interactifs -->
        <div class="card" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 1rem 1.25rem; border: 1px solid var(--border-color); border-radius: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <span style="font-weight: 800; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-right: 0.5rem;">
                        🔍 Filtrer par Clan :
                    </span>
                    <button class="btn btn-primary troop-filter-btn active" onclick="filterTroopCards('all', this)" style="padding: 0.4rem 0.9rem; font-size: 0.85rem;">
                        🌸 Tous les Clans (12)
                    </button>
                    <button class="btn btn-secondary troop-filter-btn" onclick="filterTroopCards('terran', this)" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; border-left: 3px solid #3b82f6;">
                        🏯 Clan Oda (4)
                    </button>
                    <button class="btn btn-secondary troop-filter-btn" onclick="filterTroopCards('vorash', this)" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; border-left: 3px solid #ef4444;">
                        🐎 Clan Takeda (4)
                    </button>
                    <button class="btn btn-secondary troop-filter-btn" onclick="filterTroopCards('aethelis', this)" style="padding: 0.4rem 0.9rem; font-size: 0.85rem; border-left: 3px solid #8b5cf6;">
                        ⛩️ Clan Tokugawa (4)
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="#table-comparative" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                        📊 Tableau Comparatif Complet &darr;
                    </a>
                    <a href="#regles-dojo" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                        🥋 Règles d'Entraînement au Dojo &darr;
                    </a>
                </div>
            </div>
        </div>

        <!-- PRÉSENTATION DES 3 CLANS ET LEURS 4 UNITÉS RESPECTIVES -->
        <?php foreach ($clansMeta as $cId => $clan): ?>
            <?php $clanTroops = $unitsByClan[$cId] ?? []; ?>
            
            <section class="clan-section" id="clan-section-<?= $cId ?>" style="margin-bottom: 3.5rem;">
                
                <!-- Bannière d'Introduction du Clan -->
                <div class="card" style="margin-bottom: 1.5rem; border-left: 6px solid <?= $clan['color'] ?>; background: <?= $clan['bg_gradient'] ?>; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                    <div class="card-body" style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 2rem;"><?= $clan['icon'] ?></span>
                                    <div>
                                        <h2 style="margin: 0; font-size: 1.6rem; color: var(--text-main); font-weight: 900;">
                                            <?= htmlspecialchars($clan['name']) ?>
                                            <span style="font-size: 0.9rem; font-weight: 600; color: <?= $clan['color'] ?>; margin-left: 0.5rem;">
                                                — Seigneur <?= htmlspecialchars($clan['daimyo']) ?>
                                            </span>
                                        </h2>
                                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 3px;">
                                            Fief originel : <strong><?= htmlspecialchars($clan['province']) ?></strong> &bull; Blason : <strong><?= htmlspecialchars($clan['mon']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <span style="display: inline-block; padding: 0.45rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 800; background: rgba(255,255,255,0.9); border: 1px solid <?= $clan['color'] ?>; color: <?= $clan['color'] ?>; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                                    <?= htmlspecialchars($clan['badge']) ?>
                                </span>
                            </div>
                        </div>
                        <div style="margin-top: 0.75rem; font-size: 0.95rem; color: var(--text-main); line-height: 1.5; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
                            Doctrine martiale : <em><?= htmlspecialchars($clan['doctrine']) ?></em>
                        </div>
                    </div>
                </div>

                <!-- Grille des 4 Troupes du Clan -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($clanTroops as $u): ?>
                        <?php 
                            $tactics = $unitTactics[$u['code']] ?? [
                                'role' => 'Guerrier Féodal',
                                'lore_detail' => $u['description'],
                                'strengths' => 'Polyvalent sur le champ de bataille.',
                                'weaknesses' => 'Nécessite du soutien.',
                                'quote' => '« Honneur et fidélité au clan. »'
                            ];

                            // Gestion du fichier d'illustration
                            $imgName = !empty($u['image']) ? $u['image'] : ($u['code'] . '.jpg');
                            $imgPath = '/public/assets/units/' . $imgName;
                            $diskPath = __DIR__ . '/../public/assets/units/' . $imgName;
                            $versionQuery = file_exists($diskPath) ? ('?v=' . filemtime($diskPath)) : '';
                            $fullImgUrl = $imgPath . $versionQuery;

                            $tierNames = [
                                1 => 'Rang I &bull; Première Ligne',
                                2 => 'Rang II &bull; Tir & Soutien',
                                3 => 'Rang III &bull; Choc & Élite',
                                4 => 'Rang IV &bull; Maître & Garde'
                            ];
                        ?>
                        
                        <div class="card troop-card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 14px rgba(0,0,0,0.05); transition: transform 0.25s ease, box-shadow 0.25s ease;">
                            
                            <!-- Vignette Portrait Grand Format -->
                            <div style="position: relative; width: 100%; height: 260px; overflow: hidden; background: var(--bg-ink, #ede5d5); border-bottom: 1px solid var(--border-color); cursor: pointer;"
                                 onclick="openDocsLightbox('<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= $fullImgUrl ?>', '<?= htmlspecialchars(addslashes($tactics['role'])) ?>', '<?= htmlspecialchars(addslashes($tactics['lore_detail'])) ?>', '<?= htmlspecialchars(addslashes($tactics['quote'])) ?>')"
                                 title="Cliquer pour admirer l'illustration en haute résolution">
                                
                                <img src="<?= $fullImgUrl ?>" alt="<?= htmlspecialchars($u['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: transform 0.4s ease;">
                                
                                <!-- Badge de Rang (Tier) -->
                                <div style="position: absolute; top: 10px; left: 10px; background: rgba(253,251,247,0.94); backdrop-filter: blur(4px); border: 1px solid <?= $clan['color'] ?>; border-radius: 6px; padding: 3px 10px; font-size: 0.75rem; font-weight: 800; color: <?= $clan['color'] ?>; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                                    <?= $tierNames[$u['tier']] ?? ('Rang ' . $u['tier']) ?>
                                </div>

                                <!-- Badge Rôle Tactique -->
                                <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(17,18,24,0.85); backdrop-filter: blur(4px); border-radius: 6px; padding: 4px 10px; font-size: 0.75rem; font-weight: 700; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                                    ⚔️ <?= htmlspecialchars($tactics['role']) ?>
                                </div>

                                <!-- Loupe Agrandissement -->
                                <div style="position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(253,251,247,0.9); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                                    🔍
                                </div>
                            </div>

                            <!-- Corps de la Fiche Militaire -->
                            <div class="card-body" style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column;">
                                
                                <h3 style="margin: 0 0 0.4rem 0; font-size: 1.15rem; color: var(--text-main); font-weight: 800;">
                                    <?= htmlspecialchars($u['name']) ?>
                                </h3>

                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.9rem 0; flex: 1;">
                                    <?= htmlspecialchars($tactics['lore_detail']) ?>
                                </p>

                                <div style="font-style: italic; font-size: 0.8rem; color: var(--red-primary); margin-bottom: 0.9rem; padding-left: 0.6rem; border-left: 2px solid var(--red-primary);">
                                    <?= htmlspecialchars($tactics['quote']) ?>
                                </div>

                                <!-- Matrice des Caractéristiques Martiales -->
                                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.8rem;">
                                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 3px;">
                                            <span style="color: var(--text-muted);">⚔️ Attaque :</span>
                                            <strong style="color: #dc2626; font-size: 0.9rem;"><?= $u['attack'] ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 3px;">
                                            <span style="color: var(--text-muted);">🛡️ Déf. Infanterie :</span>
                                            <strong style="color: #2563eb; font-size: 0.9rem;"><?= $u['def_infantry'] ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 3px;">
                                            <span style="color: var(--text-muted);">🐎 Déf. Cavalerie :</span>
                                            <strong style="color: #16a34a; font-size: 0.9rem;"><?= $u['def_mech'] ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 3px;">
                                            <span style="color: var(--text-muted);">⚡ Vitesse de marche :</span>
                                            <strong style="color: var(--text-main); font-size: 0.9rem;"><?= $u['speed'] ?> cases/h</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; padding-top: 2px;">
                                            <span style="color: var(--text-muted);">🎒 Capacité butin :</span>
                                            <strong style="color: #b45309; font-size: 0.9rem;"><?= $u['cargo_capacity'] ?> unités</strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; padding-top: 2px;">
                                            <span style="color: var(--text-muted);">⏳ Temps Dojo :</span>
                                            <strong style="color: var(--text-main); font-size: 0.9rem;"><?= $u['base_train_time'] ?>s base</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Coût de Recrutement & Ravitaillement -->
                                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(253,251,247,0.7); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.9rem; font-size: 0.8rem;">
                                    <span style="color: var(--text-muted); font-weight: 700;">Coût :</span>
                                    <div style="display: flex; gap: 0.75rem; font-weight: 700;">
                                        <span style="color: var(--res-metal);" title="Bois de Cèdre">🪵 <?= number_format($u['metal_cost']) ?></span>
                                        <span style="color: var(--res-crystal);" title="Pierre de Taille">🪨 <?= number_format($u['crystal_cost']) ?></span>
                                        <span style="color: var(--res-deut);" title="Riz Impérial (Rations)">🌾 <?= number_format($u['deuterium_cost']) ?></span>
                                    </div>
                                </div>

                                <!-- Évaluation Tactique (Forces & Faiblesses) -->
                                <div style="font-size: 0.75rem; line-height: 1.4; border-top: 1px solid var(--border-color); padding-top: 0.6rem;">
                                    <div style="margin-bottom: 3px;">
                                        <strong style="color: #15803d;">✅ Atouts :</strong> <?= htmlspecialchars($tactics['strengths']) ?>
                                    </div>
                                    <div>
                                        <strong style="color: #b91c1c;">⚠️ Vigilance :</strong> <?= htmlspecialchars($tactics['weaknesses']) ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </section>
        <?php endforeach; ?>

        <!-- ==========================================================
             TABLEAU COMPARATIF EXHAUSTIF DES 12 TROUPES (MATRIX)
             ========================================================== -->
        <div class="card" id="table-comparative" style="margin-bottom: 3rem; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.05);">
            <div class="card-header" style="background: var(--bg-ink, #ede5d5); padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.2rem; color: var(--text-main); font-weight: 900; display: flex; align-items: center; gap: 0.6rem;">
                        <span>📊</span> Matrice Comparative des 12 Unités de l'Archipel
                    </h3>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 3px;">
                        Vue d'ensemble synoptique pour optimiser la composition de vos régiments de guerre et vos bataillons de siège.
                    </div>
                </div>
                <div style="font-size: 0.8rem; background: rgba(194,37,43,0.1); color: var(--red-primary); padding: 4px 10px; border-radius: 6px; font-weight: 700;">
                    12 Guerriers Époque Sengoku
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.03); border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 0.85rem 1rem;">Guerrier & Portrait</th>
                            <th style="padding: 0.85rem 0.75rem;">Clan</th>
                            <th style="padding: 0.85rem 0.75rem;">Rang</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: right; color: #dc2626;">⚔️ Attaque</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: right; color: #2563eb;">🛡️ Déf. Inf.</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: right; color: #16a34a;">🐎 Déf. Cav.</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: center;">⚡ Vitesse</th>
                            <th style="padding: 0.85rem 0.75rem; text-align: right; color: #b45309;">🎒 Pillage</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">🪵 Bois / 🪨 Pierre / 🌾 Riz</th>
                            <th style="padding: 0.85rem 1rem; text-align: center;">⏳ Dojo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUnits as $u): ?>
                            <?php 
                                $clanInfo = $clansMeta[$u['faction']] ?? [
                                    'name' => 'Féodal',
                                    'color' => '#6b7280'
                                ];
                                $imgName = !empty($u['image']) ? $u['image'] : ($u['code'] . '.jpg');
                                $thumbUrl = '/public/assets/units/' . $imgName;
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;" onmouseover="this.style.background='rgba(194,37,43,0.03)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 44px; height: 44px; border-radius: 6px; overflow: hidden; border: 1px solid var(--border-color); flex-shrink: 0; background: var(--bg-ink);">
                                        <img src="<?= $thumbUrl ?>" alt="<?= htmlspecialchars($u['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: top center;">
                                    </div>
                                    <div>
                                        <strong style="color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($u['name']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($unitTactics[$u['code']]['role'] ?? '') ?></div>
                                    </div>
                                </td>
                                <td style="padding: 0.75rem 0.75rem;">
                                    <span style="font-weight: 700; color: <?= $clanInfo['color'] ?>; font-size: 0.8rem; background: rgba(0,0,0,0.03); padding: 2px 8px; border-radius: 4px; border: 1px solid <?= $clanInfo['color'] ?>33;">
                                        <?= htmlspecialchars($clanInfo['name']) ?>
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; font-weight: 700; color: var(--text-muted);">
                                    Rang <?= $u['tier'] ?>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; text-align: right; font-weight: 800; font-size: 0.95rem; color: #dc2626;">
                                    <?= $u['attack'] ?>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; text-align: right; font-weight: 700; color: #2563eb;">
                                    <?= $u['def_infantry'] ?>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; text-align: right; font-weight: 700; color: #16a34a;">
                                    <?= $u['def_mech'] ?>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; text-align: center; font-weight: 700;">
                                    <?= $u['speed'] ?>
                                </td>
                                <td style="padding: 0.75rem 0.75rem; text-align: right; font-weight: 700; color: #b45309;">
                                    <?= $u['cargo_capacity'] ?>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right; font-family: monospace; font-size: 0.8rem;">
                                    <span style="color: var(--res-metal); font-weight: 700;"><?= $u['metal_cost'] ?></span> / 
                                    <span style="color: var(--res-crystal); font-weight: 700;"><?= $u['crystal_cost'] ?></span> / 
                                    <span style="color: var(--res-deut); font-weight: 700;"><?= $u['deuterium_cost'] ?></span>
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; color: var(--text-muted);">
                                    <?= $u['base_train_time'] ?>s
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==========================================================
             GUIDE TACTIQUE : RÈGLES D'ENTRAÎNEMENT & SYNERGIES DOJO
             ========================================================== -->
        <div class="card" id="regles-dojo" style="margin-bottom: 2rem; border-left: 5px solid #2563eb; background: var(--bg-surface, #fdfbf7); padding: 1.5rem 1.75rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; color: var(--text-main); font-weight: 900; display: flex; align-items: center; gap: 0.6rem;">
                <span>🥋</span> Principes Martiaux du Dojo & Composition d'Armée
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; font-size: 0.9rem; line-height: 1.6; color: var(--text-main);">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--red-primary);">
                        🏯 1. Déblocage par Niveau de Caserne (Dojo)
                    </h4>
                    <p style="margin: 0 0 0.75rem 0; color: var(--text-muted);">
                        Pour recruter des troupes avancées au Dojo, vous devez élever le niveau du bâtiment dans votre Cité Castrale :
                    </p>
                    <ul style="margin: 0; padding-left: 1.25rem; color: var(--text-main);">
                        <li><strong>Rang I (Conscrits) :</strong> Dojo Niveau 1</li>
                        <li><strong>Rang II (Tireurs & Archers) :</strong> Dojo Niveau 3</li>
                        <li><strong>Rang III (Cavalerie & Assaut) :</strong> Dojo Niveau 5</li>
                        <li><strong>Rang IV (Hatamoto & Maîtres) :</strong> Dojo Niveau 8</li>
                    </ul>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #2563eb;">
                        🌾 2. Ravitaillement en Riz Impérial (Koku)
                    </h4>
                    <p style="margin: 0; color: var(--text-muted);">
                        Chaque soldat exige des rations de <strong>Riz Impérial</strong> lors de sa formation au Dojo et consomme des vivres pour sa subsistance. 
                        Veillez à développer vos <strong>4 Rizières</strong> rurales et à agrandir votre <strong>Grenier à Riz Kura (Slot 21)</strong> afin d'éviter la disette et de soutenir de vastes garnisons.
                    </p>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: #16a34a;">
                        🧱 3. Synergie avec la Muraille de Protection (Slot 34)
                    </h4>
                    <p style="margin: 0; color: var(--text-muted);">
                        En cas d'assaut ennemi sur votre domaine, vos troupes stationnées en garnison bénéficient d'un <strong>bonus multiplicateur défensif</strong> conféré par la <strong>Muraille de Cité</strong> (+4% de défense par niveau). 
                        Les archers et sentinelles retranchés derrière des remparts de niveau 20 deviennent quasiment indestructibles face aux charges directes.
                    </p>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'city'): ?>
        <!-- ==========================================================
             CHAPITRE 2 : CITÉ CASTRALE & GESTION DES 15 EMPLACEMENTS
             ========================================================== -->
        <div class="card" style="padding: 2rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;">
            <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; color: var(--text-main);">
                🏯 Chapitre 2 : Cité Castrale & Architecture Urbaine (15 Emplacements)
            </h2>
            <p style="font-size: 1rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 1.5rem;">
                La Cité Castrale (accessible via le médaillon central) constitue le centre névralgique de votre principauté. 
                Elle comporte 15 emplacements numérotés de <strong>#19 à #34</strong>, chacun dédié à une infrastructure vitale :
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: var(--red-primary);">🏯 Slot #19 : Tenshu (Donjon Castral)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Cœur du commandement. Réduit le temps de construction de tous les édifices urbains.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: var(--res-metal);">🪵 Slot #20 : Entrepôt de Cèdre & Pierre</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Stocke le bois de cèdre et les pierres de taille nécessaires aux chantiers.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: var(--res-deut);">🌾 Slot #21 : Grenier à Riz Fortifié (Kura)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Préserve les récoltes de riz impérial contre la pourriture et les rongeurs.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #2563eb;">🥋 Slot #22 : Dojo Militaire & Garnison</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Entraîne et arme vos fantassins, piquiers, tireurs et samouraïs.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #dc2626;">🐎 Slot #23 : Atelier de Siège & Écuries</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Fabrique les catapultes, béliers de siège et destriers de cavalerie.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #b45309;">⚖️ Slot #24 : Marché Féodal & Caravanes</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Organise les échanges commerciaux et envoie des caravanes de vivres aux alliés.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #7c3aed;">📜 Slot #25 : Académie des Savoirs & Forge</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Développe les technologies d'armement, métallurgie et art de la guerre.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #0891b2;">🔭 Slot #26 : Tour de Guet Yagura</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Détecte à l'avance les mouvements de troupes ennemies marchant vers votre fief.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #475569;">🕳️ Slot #27 : Cachette Secrète Sous Terre</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Met vos précieuses ressources à l'abri des pillages lors des raids ennemis.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #059669;">⛩️ Slot #28 : Pavillon Diplomatique</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Permet de fonder ou rejoindre une alliance entre puissants seigneurs féodaux.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin:0 0 0.4rem 0; color: #16a34a;">🧱 Slot #34 : Muraille & Remparts de Cité</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Protège l'enceinte entière et décuple l'efficacité défensive de votre garnison.</p>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'resources'): ?>
        <!-- ==========================================================
             CHAPITRE 3 : TERROIR & PARCELLES RURALES
             ========================================================== -->
        <div class="card" style="padding: 2rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;">
            <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; color: var(--text-main);">
                🌾 Chapitre 3 : Terroir & Récoltes du Domaine (18 Parcelles Rurales)
            </h2>
            <p style="font-size: 1rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 1.5rem;">
                Le Terroir rural entoure la forteresse et alimente l'effort de guerre du clan. Il se compose de 18 parcelles agraires et minières :
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-metal);">
                    <h4 style="margin:0 0 0.5rem 0; color: var(--res-metal);">🪵 5 Camps de Bûcherons (Bois de Cèdre)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Indispensable pour bâtir les charpentes de bois des châteaux, les remparts et forger les hampes de lances.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-crystal);">
                    <h4 style="margin:0 0 0.5rem 0; color: var(--res-crystal);">🪨 5 Carrières de Pierre (Pierre de Taille)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Extrait les blocs de granit pour dresser les fondations cyclopéennes (Nozura-zumi) des donjons.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-deut);">
                    <h4 style="margin:0 0 0.5rem 0; color: var(--res-deut);">🌾 4 Rizières du Fief (Riz Impérial)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Base alimentaire de la population et ravitaillement indispensable pour entraîner des armées de guerriers.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-energy);">
                    <h4 style="margin:0 0 0.5rem 0; color: var(--res-energy);">⛩️ 4 Sanctuaires Shintō (Sérénité & Ferveur)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Énergie spirituelle veillant sur l'harmonie du domaine. Une sérénité insuffisante réduit vos récoltes à 10% !</p>
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'castles'): ?>
        <!-- ==========================================================
             CHAPITRE 4 : LES 12 DONJONS AUTHENTIQUES DU JAPON
             ========================================================== -->
        <div class="card" style="padding: 2rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;">
            <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; color: var(--text-main);">
                🏯 Chapitre 4 : Les 12 Donjons Authentiques du Japon (現存十二天守)
            </h2>
            <p style="font-size: 1rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 1.5rem;">
                Disséminés sur la Carte des Provinces, les 12 donjons authentiques d'époque Sengoku et Edo sont les joyaux architecturaux de l'archipel.
                Ces forteresses historiques sont les objectifs suprêmes de la Bataille Finale pour l'unification du Japon :
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Himeji</strong> (Harima) &bull; Le Héron Blanc
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Matsumoto</strong> (Shinano) &bull; Le Corbeau Noir
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château d'Inuyama</strong> (Owari) &bull; Le plus ancien donjon
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Hikone</strong> (Ōmi) &bull; Trésor National
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Matsue</strong> (Izumo) &bull; Le Pluvier Noir
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Kōchi</strong> (Tosa) &bull; Bastion de Shikoku
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Marugame</strong> (Sanuki) &bull; Murailles en éventail
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Maruoka</strong> (Echizen) &bull; Toiture de tuiles en pierre
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Bitchū Matsuyama</strong> &bull; La Forteresse des Nuages
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Matsuyama</strong> (Iyo) &bull; Complexe Castral
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château d'Uwajima</strong> (Iyo) &bull; Bastion Côtier
                </div>
                <div style="background: var(--bg-ink); padding: 0.85rem; border-radius: 6px; border: 1px solid var(--border-color);">
                    <strong>🏯 Château de Hirosaki</strong> (Mutsu) &bull; Donjon Septentrional
                </div>
            </div>
        </div>

    <?php elseif ($tab === 'combat'): ?>
        <!-- ==========================================================
             CHAPITRE 5 : RÈGLES DU COMBAT & FORMULES DE SIÈGE
             ========================================================== -->
        <div class="card" style="padding: 2rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 12px;">
            <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; color: var(--text-main);">
                ⚔️ Chapitre 5 : Règles du Combat, Formations & Expéditions
            </h2>
            <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted);">
                <p>
                    Les affrontements militaires dans OpenShogun reposent sur la confrontation des forces offensives cumulées face aux défenses de l'adversaire (défense infanterie et défense cavalerie/méca), modulées par le bonus des fortifications de la Cité (Muraille) et les capacités spéciales de clan.
                </p>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); margin: 1rem 0; color: var(--text-main);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--red-primary);">Triangle Tactique Féodal :</h4>
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <li><strong>Piques Yari (Ashigaru) :</strong> Déciment la cavalerie rouge sous l'impact de leurs pointes acérées.</li>
                        <li><strong>Cavalerie Rouge & Archers Montés :</strong> Contournent et foudroient les tireurs et archers à découvert.</li>
                        <li><strong>Mousquets Tanegashima & Arcs :</strong> Perforent les armures lourdes et infligent des pertes sévères à distance avant le contact.</li>
                        <li><strong>Murailles & Remparts :</strong> Multiplient la résistance de la garnison et infligent des dégâts structurels aux assaillants.</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODALE LIGHTBOX EN HAUTE DÉFINITION POUR LES ILLUSTRATIONS DU CODEX -->
<div class="modal-overlay" id="docsLightboxModal" style="display: none; position: fixed; inset: 0; background: rgba(5,7,15,0.9); backdrop-filter: blur(10px); z-index: 9999; align-items: center; justify-content: center;" onclick="closeDocsLightbox()">
    <div class="modal-card" style="max-width: 850px; width: 92%; max-height: 92vh; background: var(--bg-surface, #fdfbf7); border: 2px solid var(--red-primary); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 0 50px rgba(194,37,43,0.3);" onclick="event.stopPropagation()">
        <div class="card-header" style="padding: 1rem 1.25rem; background: var(--bg-ink, #ede5d5); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 id="lightboxUnitName" style="margin: 0; font-size: 1.25rem; color: var(--text-main); font-weight: 900;">Nom du Guerrier</h3>
                <span id="lightboxUnitRole" style="font-size: 0.8rem; font-weight: 700; color: var(--red-primary);">Rôle</span>
            </div>
            <button onclick="closeDocsLightbox()" style="background: none; border: none; font-size: 1.6rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <div class="card-body" style="padding: 1.25rem; overflow-y: auto; text-align: center;">
            <div style="width: 100%; max-height: 520px; border-radius: 10px; overflow: hidden; background: var(--bg-ink); border: 1px solid var(--border-color); box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                <img id="lightboxUnitImg" src="" alt="" style="width: 100%; height: auto; max-height: 520px; object-fit: contain;">
            </div>
            <p id="lightboxUnitLore" style="font-size: 0.95rem; line-height: 1.6; color: var(--text-main); margin-bottom: 0.5rem; text-align: justify;"></p>
            <div id="lightboxUnitQuote" style="font-style: italic; color: var(--red-primary); font-size: 0.9rem; margin-top: 0.5rem;"></div>
        </div>
    </div>
</div>

<script>
// Filtrage dynamique des cartes d'unités par clan
function filterTroopCards(clanId, btnEl) {
    const buttons = document.querySelectorAll('.troop-filter-btn');
    buttons.forEach(b => {
        b.classList.remove('btn-primary', 'active');
        b.classList.add('btn-secondary');
    });
    btnEl.classList.remove('btn-secondary');
    btnEl.classList.add('btn-primary', 'active');

    const sections = document.querySelectorAll('.clan-section');
    sections.forEach(sec => {
        if (clanId === 'all') {
            sec.style.display = 'block';
        } else {
            if (sec.id === 'clan-section-' + clanId) {
                sec.style.display = 'block';
            } else {
                sec.style.display = 'none';
            }
        }
    });
}

// Lightbox haute définition pour admirer l'illustration
function openDocsLightbox(name, imgUrl, role, lore, quote) {
    document.getElementById('lightboxUnitName').textContent = name;
    document.getElementById('lightboxUnitRole').textContent = role;
    document.getElementById('lightboxUnitImg').src = imgUrl;
    document.getElementById('lightboxUnitLore').textContent = lore;
    document.getElementById('lightboxUnitQuote').textContent = quote;
    
    const modal = document.getElementById('docsLightboxModal');
    modal.style.display = 'flex';
}

function closeDocsLightbox() {
    const modal = document.getElementById('docsLightboxModal');
    modal.style.display = 'none';
}
</script>

