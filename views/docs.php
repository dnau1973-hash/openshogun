<?php
/**
 * Codex & Documentation Officielle d'OpenShogun
 * Manuel Stratégique, Guide du Daimyō et Art de la Guerre (Sengoku Jidai)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();

$db = Database::getConnection();

// Répertoire des 12 Troupes du Dojo
$stmt = $db->query("SELECT * FROM units ORDER BY faction, tier ASC");
$allUnits = $stmt->fetchAll();

// Répertoire des 13 Engins de Siège, Cavaleries et Convois
$stmtShips = $db->query("SELECT * FROM ships ORDER BY faction, metal_cost ASC");
$allShips = $stmtShips->fetchAll();

// Chapitre / Onglet actif (par défaut 'overview' pour offrir le guide complet et le paragraphe du héros samouraï)
$tab = $_GET['tab'] ?? 'overview';

// Métadonnées enrichies des 12 unités du Dojo
$unitTactics = [
    'piquier_ashigaru_yari' => [
        'role' => 'Infanterie de ligne & Anti-Cavalerie',
        'lore_detail' => 'Formés dans la rigueur absolue des réformes militaires d\'Oda Nobunaga, ces fantassins paysans sont armés d\'une pique nagae-yari de plus de 5 mètres. Organisés en rangs serrés, ils forment une haie d\'acier infranchissable capable de briser l\'élan des charges de cavalerie les plus furieuses.',
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

// Métadonnées tactiques des 13 Engins de Siège, Cavaleries et Convois
$shipTactics = [
    'terran_interceptor' => [
        'role' => 'Cavalerie Légère & Patrouille d\'Interception',
        'lore_detail' => 'Cavaliers d\'élite montés sur les vifs coursiers Kiso. Ils patrouillent le long des frontières de la province d\'Owari, repérant les colonnes d\'invasion et coupant la retraite des détachements ennemis.',
        'strengths' => 'Vitesse foudroyante (12), faible coût en riz, intercepte les fuyards et éclaireurs.',
        'weaknesses' => 'Faible puissance contre les murs de lances Ashigaru.',
        'quote' => '« Nul n\'échappe aux sabots d\'Owari quand l\'alerte est sonnée. »'
    ],
    'terran_cruiser' => [
        'role' => 'Bélier de Siège Lourd Blindé',
        'lore_detail' => 'Machine monumentale munie d\'une tête de frappe en acier trempé soutenue par de grosses chaînes. Protégée par un toit en madriers de cèdre et peaux de buffle, elle est conçue pour fracasser les portails massifs des forteresses.',
        'strengths' => 'Capacité d\'assaut destructrice (400), blindage élevé (2500 PV), ouvre des brèches dans les fortifications.',
        'weaknesses' => 'Lenteur de déplacement, nécessite une escorte militaire rapprochée.',
        'quote' => '« Même les portes les plus épaisses cèdent devant le battement d\'acier. »'
    ],
    'terran_dreadnought' => [
        'role' => 'Grande Tour de Siège & Baliste Géante',
        'lore_detail' => 'Véritable forteresse mobile à plusieurs étages dominant les murailles adverses. Équipée à son sommet d\'une baliste lourde décochant des javelines enflammées, elle permet aux archers et arquebusiers de balayer les chemins de ronde.',
        'strengths' => 'Attaque colossale (1200), blindage titanesque (8000), surplombe les remparts et réduit les bonus de muraille.',
        'weaknesses' => 'Coût colossal en matériaux, cible prioritaire des tirs incendiaires.',
        'quote' => '« Quand la tour s\'avance, l\'ombre du destin recouvre les assiégés. »'
    ],
    'vorash_drone' => [
        'role' => 'Cavalier Éclaireur & Éclaireur Équestre de Kai (疾風)',
        'lore_detail' => 'Cavaliers éclaireurs d\'élite formés sur les crêtes escarpées de la province de Kai. Montés sur les vifs coursiers de montagne Kiso et arborant la fière armure Akazonae aux quatre losanges Takeda, ils évoluent en avant-garde pour cartographier les fiefs ennemis, déceler les embuscades et mener des raids fulgurants.',
        'strengths' => 'Vitesse de reconnaissance et de raid suprême (13), coût modeste en riz, grande capacité de butin par rapport au coût.',
        'weaknesses' => 'Défense légère en cas d\'engagement prolongé contre les murs de piques.',
        'quote' => '« Rapide comme le vent, silencieux comme la forêt, dévastateur comme le feu. » — Takeda Shingen'
    ],
    'vorash_manticore' => [
        'role' => 'Cavalerie Rouge Cuirassée (Akazonae)',
        'lore_detail' => 'L\'illustre fer de lance de Takeda Shingen. Revêtus de somptueuses armures entièrement vermillon et chevauchant des destriers caparaçonnés, ces cavaliers lourds chargent en bloc compact, écrasant les lignes adverses par la terreur et la masse.',
        'strengths' => 'Attaque dévastatrice (380), solide blindage (2000), bonus de pillage (+25% de ressources raflées).',
        'weaknesses' => 'Formation exigeante et coûteuse à reconstituer.',
        'quote' => '« Le rouge de nos armures annonce le sang des téméraires. » — Takeda Shingen'
    ],
    'vorash_leviathan' => [
        'role' => 'Bélier Colossal du Dragon de Kai',
        'lore_detail' => 'Engin de guerre mythique surmonté d\'une gigantesque effigie de dragon crachant des flammèches. Poussé par des dizaines d\'hommes et bêtes de trait, son éperon massif ébranle les fondations mêmes des châteaux féodaux.',
        'strengths' => 'Attaque titanesque (1100), blindage extrême (7000), capacité de fret de 3000.',
        'weaknesses' => 'Consommation importante de vivres et lenteur sur les routes de montagne.',
        'quote' => '« Quand le Dragon frappe, la terre tremble et la pierre se fend. »'
    ],
    'aethelis_mirage' => [
        'role' => 'Troupe Furtive d\'Embuscade Montée',
        'lore_detail' => 'Ombres shinobi montant des chevaux aux sabots feutrés. Capables de contourner les lignes de front par des sentiers forestiers réputés impraticables, ils surgissent à revers des lignes ennemies pour semer la panique et assassiner les officiers.',
        'strengths' => 'Vitesse maximale (14), esquive et dissimulation exceptionnelles, déstabilise l\'arrière-garde.',
        'weaknesses' => 'Faible puissance frontale face aux engins blindés.',
        'quote' => '« Vous entendrez le souffle du cheval au moment précis où le ninjato frappera. »'
    ],
    'aethelis_prism' => [
        'role' => 'Catapulte Incendiaire Horokubiya',
        'lore_detail' => 'Machine d\'artillerie projetant des jarres de grès remplies de poudre noire et de résine enflammée (Horokubiya). L\'impact provoque de gigantesques gerbes de flammes et d\'étincelles qui consument les bâtisses en bois des forteresses.',
        'strengths' => 'Attaque incendiaire de zone (450), pavois protecteur (250), cause d\'immenses ravages sur les infrastructures.',
        'weaknesses' => 'Risque de retour de flamme si cerné en mêlée.',
        'quote' => '« Une seule jarre de feu allume le bûcher d\'une garnison tout entière. »'
    ],
    'aethelis_titan' => [
        'role' => 'Forteresse Roulante Blindée Imprenable',
        'lore_detail' => 'Bastion mobile cuirassé de plaques d\'acier et de madriers de chêne, hérissé de meurtrières pour tireurs et piquiers. Servant de point d\'appui inébranlable lors des sièges prolongés chers à la stratégie de patience de Tokugawa Ieyasu.',
        'strengths' => 'Blindage suprême (7500), pavois de 900, attaque puissante (1300), protège les troupes d\'escorte.',
        'weaknesses' => 'Lenteur extrême (6.5), fabrication complexe.',
        'quote' => '« Notre château ne nous attend pas derrière les remparts : nous l\'amenons avec nous. » — Tokugawa Ieyasu'
    ],
    'transporter_light' => [
        'role' => 'Chariot de Ravitaillement Léger',
        'lore_detail' => 'Chariots rustiques à deux roues attelés à des bœufs dociles ou chevaux de trait. Pilotés par des paysans du domaine, ils assurent le ravitaillement constant des avant-postes en sacs de riz et poutres de bois.',
        'strengths' => 'Très économique, capacité de fret de 5 000 ressources, indispensable pour les échanges provinciaux.',
        'weaknesses' => 'Totalement dépourvu de capacités offensives (Attaque 5).',
        'quote' => '« L\'armée qui mange à sa faim avance sans jamais faiblir. »'
    ],
    'transporter_heavy' => [
        'role' => 'Grand Convoi Logistique de Fief',
        'lore_detail' => 'Lourde caravane féodale protégée par des bâches renforcées et des gardes armés. Elle permet de transférer des trésors colossaux de pierre taillée, de lingots et de grains entre châteaux alliés.',
        'strengths' => 'Capacité d\'emport gigantesque de 25 000 ressources, résistant aux embuscades mineures.',
        'weaknesses' => 'Vitesse de déplacement modérée.',
        'quote' => '« Les richesses de la province voyagent sous le regard attentif des intendants. »'
    ],
    'colony_ship' => [
        'role' => 'Expédition d\'Établissement Castral',
        'lore_detail' => 'Rassemblement solennel de maîtres-charpentiers, architectes, forgerons, cultivateurs et moines bénédictins. Emportant plans de construction, plants sacrés et outils précieux, cette caravane fonde un nouveau fief castral sur une terre vierge.',
        'strengths' => 'Permet de fonder un nouveau château complet avec ses 18 parcelles de ressources et son donjon.',
        'weaknesses' => 'Unité consommée lors de la fondation, coûteuse à préparer.',
        'quote' => '« Planter la première poutre, c\'est faire naître un empire pour les générations futures. »'
    ],
    'spy_probe' => [
        'role' => 'Éclaireur Shinobi Furtif',
        'lore_detail' => 'Infiltrateur d\'élite agile opérant seul dans les ténèbres. Glissant le long des toitures et des douves sans éveiller l\'attention des sentinelles, il recueille des rapports secrets sur les défenses, les bâtiments et les réserves des fiefs rivaux.',
        'strengths' => 'Vitesse absolue (20), coût dérisoire, rapporte les données stratégiques complètes sans déclarer la guerre.',
        'weaknesses' => 'Éliminé immédiatement s\'il est intercepté par la garde d\'alerte ennemie.',
        'quote' => '« Voir sans être vu, savoir avant que le premier tambour de guerre ne résonne. »'
    ]
];

// Métadonnées des Clans
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

    <!-- ==============================================================
         EN-TÊTE DU CODEX & GUIDE OFFICIEL D'OPENSHOGUN
         ============================================================== -->
    <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--red-primary); background: var(--bg-surface, #fdfbf7); overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.06);">
        <div style="position: relative; padding: 2.25rem 2rem; background: linear-gradient(135deg, rgba(194, 37, 43, 0.07) 0%, rgba(253, 251, 247, 0.98) 70%, rgba(245, 158, 11, 0.08) 100%);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                <div style="max-width: 880px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: var(--red-primary); background: rgba(194,37,43,0.1); padding: 3px 10px; border-radius: 4px;">
                            📜 Documentation Officielle du Joueur
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">&bull; Manuel Stratégique & Chroniques du Shogunat</span>
                    </div>
                    <h1 style="font-size: 2.2rem; margin: 0 0 0.75rem 0; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem;">
                        <span>📖</span> Grande Encyclopédie & Guide du Daimyō
                    </h1>
                    <p style="font-size: 1.05rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                        Bienvenue dans les archives impériales du Japon féodal de l'ère Sengoku. Ce codex interactif réunit l'ensemble des règles fondamentales du jeu : 
                        le <strong>Héros Samouraï</strong> et ses <strong>35 reliques uniques</strong>, la personnalisation libre de vos <strong>18 parcelles de terroir</strong> et de votre <strong>cité castrale</strong>, 
                        les fiches illustrées des <strong>25 unités militaires</strong> (Dojo & Siège), la conquête des <strong>Oasis sauvages</strong>, 
                        les <strong>Alliances féodales</strong>, l'<strong>Artisanat du Moulin (Saké & Farine)</strong>, les <strong>Banquets au Tenshu</strong>, les <strong>Cages de capture de faune</strong> et la <strong>vie communautaire (Chat & Forum)</strong>.
                    </p>
                </div>
                
                <div style="text-align: right; background: var(--bg-ink, #ede5d5); padding: 1rem 1.5rem; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Édition Impériale Complète</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: var(--red-primary);">戦国時代 &bull; 1572</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">25 Unités &bull; 35 Reliques &bull; Alliances &bull; Artisanat</div>
                </div>
            </div>

            <!-- Sommaire & Navigation entre Chapitres de la Documentation -->
            <div style="display: flex; gap: 0.5rem; margin-top: 1.75rem; overflow-x: auto; padding-bottom: 0.25rem; flex-wrap: wrap;">
                <a href="?page=docs&tab=overview" class="btn <?= ($tab === 'overview') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>📜</span> Vue d'Ensemble
                </a>
                <a href="?page=docs&tab=hero" class="btn <?= ($tab === 'hero') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>⚔️</span> Ch. 1 : Héros & 35 Reliques
                </a>
                <a href="?page=docs&tab=resources" class="btn <?= ($tab === 'resources') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🌾</span> Ch. 2 : Terroir (1-18)
                </a>
                <a href="?page=docs&tab=city" class="btn <?= ($tab === 'city') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🏯</span> Ch. 3 : Cité Castrale (19-34)
                </a>
                <a href="?page=docs&tab=troops" class="btn <?= ($tab === 'troops') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🥋</span> Ch. 4 : Troupes du Dojo (12)
                </a>
                <a href="?page=docs&tab=siege" class="btn <?= ($tab === 'siege') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🐎</span> Ch. 5 : Siège & Écuries (13)
                </a>
                <a href="?page=docs&tab=oasis" class="btn <?= ($tab === 'oasis') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🌴</span> Ch. 6 : Oasis & Faune
                </a>
                <a href="?page=docs&tab=quests" class="btn <?= ($tab === 'quests') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🎯</span> Ch. 7 : Quêtes & Didacticiel
                </a>
                <a href="?page=docs&tab=map" class="btn <?= ($tab === 'map') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🗺️</span> Ch. 8 : Carte Féodale
                </a>
                <a href="?page=docs&tab=castles" class="btn <?= ($tab === 'castles') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🏯</span> Ch. 9 : 12 Donjons Historiques
                </a>
                <a href="?page=docs&tab=combat" class="btn <?= ($tab === 'combat') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>⚔️</span> Ch. 10 : Combat & Sièges
                </a>
                <a href="?page=docs&tab=alliances" class="btn <?= ($tab === 'alliances') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🚩</span> Ch. 11 : Alliances & Diplomatie
                </a>
                <a href="?page=docs&tab=craft" class="btn <?= ($tab === 'craft') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🍶</span> Ch. 12 : Artisanat & Banquets
                </a>
                <a href="?page=docs&tab=cages" class="btn <?= ($tab === 'cages') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>🦊</span> Ch. 13 : Cages & Faune Sauvage
                </a>
                <a href="?page=docs&tab=social" class="btn <?= ($tab === 'social') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap;">
                    <span>💬</span> Ch. 14 : Chat & Forum
                </a>
                <a href="?page=docs&tab=seal" class="btn <?= ($tab === 'seal') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap; border-color: #f59e0b; color: #b45309;">
                    <span>👑</span> Ch. 15 : Sceau Impérial &amp; Empire
                </a>
                <a href="?page=docs&tab=all" class="btn <?= ($tab === 'all') ? 'btn-primary' : 'btn-secondary' ?>" style="display: inline-flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.9rem; border-radius: 8px; white-space:nowrap; background: rgba(194,37,43,0.1); border-color: var(--red-primary); color: var(--red-primary);">
                    <span>📚</span> Tout Dérouler (1-15)
                </a>
            </div>
        </div>
    </div>

    <?php if ($tab === 'overview'): ?>
        <!-- ==============================================================
             VUE D'ENSEMBLE & GUIDE STRATÉGIQUE MAJEUR DU DAIMYŌ
             ============================================================== -->
        
        <!-- SECTION SPÉCIALE : LE HÉROS SAMOURAÏ (PARAGRAPHE DÉDIÉ MIS EN AVANT) -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(220,38,38,0.05) 0%, rgba(253,251,247,0.98) 100%); border-left: 6px solid var(--red-primary); border-radius: 12px; padding: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
                <div style="font-size: 3.5rem; line-height: 1; background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 16px; border: 2px solid var(--border-color); flex-shrink: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    🥋
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem;">
                        <span style="background: var(--red-primary); color: #fff; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; letter-spacing: 1px;">
                            Commandant Suprême du Fief
                        </span>
                        <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700;">Système Héroïque Sengoku &bull; Travian-Style</span>
                    </div>
                    <h2 style="margin: 0 0 0.75rem 0; font-size: 1.6rem; color: var(--text-main);">
                        ⚔️ Le Héros Samouraï & la Voie du Bushidō (武士道)
                    </h2>
                    <p style="font-size: 0.98rem; line-height: 1.7; color: var(--text-main); margin-bottom: 1rem; text-align: justify;">
                        Le <strong>Héros Samouraï</strong> est l'incarnation vivante de votre lignée féodale et le cœur battant de votre puissance provinciale. 
                        Présent à vos côtés dès la fondation de votre domaine, votre héros progresse en accumulant des points d'expérience (XP) 
                        au fil de périlleuses <strong>Aventures en monde ouvert</strong> ou en prenant la tête de vos armées lors de sanglantes batailles de siège et d'escarmouches. 
                        À chaque montée de niveau, il reçoit <strong>4 points d'attributs</strong> à investir dans quatre piliers complémentaires : 
                        la <em>Force Martiale</em> (+80 puissance de combat individuelle par point pour affronter seul les bêtes sauvages des oasis), 
                        le <em>Commandement Offensif</em> (+0.2% d'attaque à toute l'armée qui l'accompagne), 
                        la <em>Maîtrise Défensive</em> (+0.2% de défense à la garnison du château), 
                        et la <em>Gouvernance Féodale</em> (démultiplication de la production de ressources, avec spécialisation au choix sur le Bois, la Pierre ou le Riz).
                    </p>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted); margin-bottom: 1.25rem;">
                        Votre champion peut s'équiper de <strong>reliques et artefacts légendaires</strong> sur 6 emplacements dédiés (Arme, Bouclier, Casque Kabuto, Armure O-Yoroi, Bottes/Destrier et Amulette Omamori). 
                        S'il tombe au combat, sa mémoire et sa progression sont éternelles : son niveau, ses points et son arsenal restent intacts, et un solennel <strong>Rituel Shintō de Résurrection</strong> peut être célébré au fief pour le réincarner dans la plénitude de sa gloire.
                    </p>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a href="?page=docs&tab=hero" class="btn btn-primary" style="font-size: 0.85rem; font-weight: 700; padding: 0.45rem 1rem;">
                            📖 Découvrir le Chapitre Complet du Héros &rarr;
                        </a>
                        <a href="?page=hero" class="btn btn-secondary" style="font-size: 0.85rem; font-weight: 700; padding: 0.45rem 1rem;">
                            🥋 Gérer mon Héros Samouraï
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRILLE DES 3 GRANDS PILIERS STRATÉGIQUES RÉCENTS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

            <!-- PILIER 1 : CHOIX LIBRE DU TERROIR (1-18) -->
            <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem;">🌾</span>
                        <span style="background: rgba(34,197,94,0.1); color: #15803d; font-weight: 800; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                            Choix Libre &bull; Slots #1 à #18
                        </span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: var(--text-main);">
                        Terroir & Parcelles Modulaires
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 1rem 0;">
                        Finie la rigidité des domaines imposés ! Sur vos <strong>18 parcelles de ressources</strong>, tout terrain vacant (niveau 0) vous permet, 
                        d'un simple clic ouvrant la modale interactive de sélection, de choisir librement le bâtiment à ériger : 
                        <strong>🪵 Camp de Bûcherons</strong>, <strong>🪨 Carrière de Granit</strong>, <strong>🌾 Rizières Inondées</strong> ou <strong>⛩️ Sanctuaire d'Inari</strong>.
                        Vous pouvez ainsi spécialiser votre principauté selon votre stratégie (surplus de riz pour les grandes armées Takeda, bois massif pour le génie Oda, ou carrières pour les murailles Tokugawa).
                    </p>
                </div>
                <a href="?page=docs&tab=resources" class="btn btn-secondary" style="font-size: 0.85rem; font-weight: 700; align-self: flex-start;">
                    Consulter le Guide du Terroir &rarr;
                </a>
            </div>

            <!-- PILIER 2 : CITÉ CASTRALE ET SILOS LIBRES (19-34) -->
            <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem;">🏯</span>
                        <span style="background: rgba(59,130,246,0.1); color: #1d4ed8; font-weight: 800; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                            Architecture Libre &bull; Slots #19 à #34
                        </span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: var(--text-main);">
                        Cité Castrale & Silos Modulaires
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 1rem 0;">
                        À l'intérieur des remparts, la cité comporte 16 emplacements castraux. En cliquant sur un terrain libre (slots #19 à #33), 
                        une fenêtre féodale vous présente la liste des édifices constructibles (Dojo, Atelier de Siège & Écuries, Académie des Savoirs, Marché, Entrepôt, Grenier Kura, Cachette Secrète, Tour de Guet Yagura, Pavillon Diplomatique). 
                        Vous placez vos bâtiments où bon vous semble ! La Muraille scelle le slot #34 pour démultiplier votre garnison.
                    </p>
                </div>
                <a href="?page=docs&tab=city" class="btn btn-secondary" style="font-size: 0.85rem; font-weight: 700; align-self: flex-start;">
                    Consulter l'Architecture Urbaine &rarr;
                </a>
            </div>

            <!-- PILIER 3 : OASIS SAUVAGES & QUÊTES DIDACTIELLES -->
            <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem;">🌴</span>
                        <span style="background: rgba(245,158,11,0.1); color: #b45309; font-weight: 800; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                            Monde Ouvert & Quêtes
                        </span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: var(--text-main);">
                        Oasis Sauvages & Didacticiel Guidé
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 1rem 0;">
                        La Carte des Provinces abrite des <strong>Oasis sauvages</strong> peuplées de bêtes féroces (loups, sangliers, ours géants). 
                        Les piller permet de dérober de vastes butins, et une fois pacifiées, y stationner vos troupes permet de les annexer pour remporter 
                        des bonus permanents de récolte (+25% ou +50% en bois, pierre ou riz). 
                        Parallèlement, les nouveaux seigneurs sont guidés par le maître Katsumoto à travers <strong>12 Quêtes didactiques</strong> généreusement récompensées.
                    </p>
                </div>
                <a href="?page=docs&tab=oasis" class="btn btn-secondary" style="font-size: 0.85rem; font-weight: 700; align-self: flex-start;">
                    Découvrir les Oasis & Quêtes &rarr;
                </a>
            </div>

            <!-- PILIER 4 : DÉMOLITION AVEC CHRONO & DEVISE DU DAIMYŌ -->
            <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid #ef4444;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span style="font-size: 2rem;">🗑️</span>
                        <span style="background: rgba(239,68,68,0.1); color: #ef4444; font-weight: 800; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; text-transform: uppercase;">
                            Stratégie & Dynastie
                        </span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: var(--text-main);">
                        Démantèlement Temporisé & Devise Officielle
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 1rem 0;">
                        Adaptez librement votre domaine face aux crises : chaque bâtiment urbain ou parcelle rurale peut être <strong>démantelé</strong> avec un compte à rebours de démolition (50% de la durée). Durant les travaux, la bâtisse reste visible avec un badge <code>🗑️</code>, annulable à tout instant sans frais, et vous récupérez <strong>30% des matériaux</strong> à l'achèvement pour libérer l'emplacement !<br>
                        Depuis le Donjon Tenshu, proclamez également la <strong>Devise officielle</strong> de votre dynastie qui guidera vos samouraïs sur tout l'archipel.
                    </p>
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <a href="?page=docs&tab=city" class="btn btn-secondary" style="font-size: 0.82rem; font-weight: 700;">
                        Démolition en Cité &rarr;
                    </a>
                    <a href="?page=docs&tab=resources" class="btn btn-secondary" style="font-size: 0.82rem; font-weight: 700;">
                        Raser une Parcelle &rarr;
                    </a>
                </div>
            </div>

        </div>

        <!-- NOUVELLE ILLUSTRATION D'UNITÉ : CAVALIER ÉCLAIREUR TAKEDA -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.02) 100%), var(--bg-surface, #fdfbf7); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 1.5rem; border-left: 6px solid #ef4444;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <?php 
                    $takedaCavDisk = __DIR__ . '/../public/assets/units/cavalier_eclaireur_takeda.jpg';
                    $takedaCavSrc = '/public/assets/units/cavalier_eclaireur_takeda.jpg' . (file_exists($takedaCavDisk) ? '?v=' . filemtime($takedaCavDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Cavalier Éclaireur Takeda', '<?= $takedaCavSrc ?>', 'Reconnaissance & Raids de Kai', 'Monté sur les agiles coursiers Kiso des montagnes escarpées de Kai, ce cavalier léger repère les positions fortifiées adverses et lance des assauts fulgurants.', '« Rapide comme le vent, silencieux comme la forêt. »')">
                    <img src="<?= $takedaCavSrc ?>" alt="Cavalier Éclaireur Takeda" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #ef4444; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🐎 Clan Takeda &bull; Atelier & Écuries
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #ef4444; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            ✨ Nouvelle Illustration Intégrée
                        </span>
                        <span style="font-size: 0.8rem; color: #ef4444; font-weight: 700;">Écuries & Atelier de Siège (Rang I)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Cavalier Éclaireur Takeda (武田偵察騎兵)
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Monté sur un agile coursier <em>Kiso</em> des montagnes escarpées de Kai, ce cavalier léger équipé d'une armure laquée vermillon et d'une lance <em>yari</em> de reconnaissance fonce en avant-garde de vos armées. Il est l'unité de raid la plus rapide du Shogunat (vitesse 13 000) et le fer de lance de la doctrine du vent et de la forêt de Shingen.
                    </p>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.8rem; background: var(--bg-ink, #ede5d5); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: #dc2626; font-weight: 800;">⚔️ 55 Atq</span> &bull;
                            <span style="color: #2563eb; font-weight: 700;">🛡️ 310 Déf</span> &bull;
                            <span style="color: #16a34a; font-weight: 800;">⚡ 13 000 Vitesse</span> &bull;
                            <span style="color: #b45309; font-weight: 700;">🎒 80 Fret</span>
                        </div>
                        <a href="?page=docs&tab=siege" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem;">
                            🐎 Voir au Chapitre 5 (Engins & Cavalerie) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOUVELLE ILLUSTRATION D'UNITÉ : BÉLIER TITANESQUE DU DRAGON DE KAI -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(185, 28, 28, 0.08) 0%, rgba(249, 115, 22, 0.04) 100%), var(--bg-surface, #fdfbf7); border: 1px solid rgba(185, 28, 28, 0.35); border-radius: 12px; padding: 1.5rem; border-left: 6px solid #b91c1c;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <?php 
                    $dragonRamDisk = __DIR__ . '/../public/assets/units/belier_dragon_kai.jpg';
                    $dragonRamSrc = '/public/assets/units/belier_dragon_kai.jpg' . (file_exists($dragonRamDisk) ? '?v=' . filemtime($dragonRamDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Bélier Titanesque du Dragon de Kai', '<?= $dragonRamSrc ?>', 'Engin de Siège Colossal & Démolition', 'Monumental bélier d\'assaut orné d\'une tête de dragon crachant braises et flammèches. Poussé sous un charriot blindé en poutres de cèdre et cuir ignifuge, il broie les portes castrales les plus massives.', '« Le rugissement du dragon fait plier la pierre et trembler les tyrans. »')">
                    <img src="<?= $dragonRamSrc ?>" alt="Bélier Titanesque du Dragon de Kai" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #f97316; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🐉 Clan Takeda &bull; Atelier de Siège
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #b91c1c; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            ✨ Nouvelle Illustration Intégrée
                        </span>
                        <span style="font-size: 0.8rem; color: #b91c1c; font-weight: 700;">Atelier de Siège Provincial (Rang V &bull; Élite)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Bélier Titanesque du Dragon de Kai (甲斐龍破城槌)
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Le chef-d'œuvre du génie militaire des ingénieurs de Kai. Surmonté d'une gigantesque gueule de dragon forgée en fer noirci et bronze crachant du feu, cet engin monumental est protégé par des poutres de cèdre multicouches et des peaux trempées ignifuges. Sous l'impact de son éperon répété, les herses de fer cèdent et les remparts de pierre s'effondrent.
                    </p>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.8rem; background: var(--bg-ink, #ede5d5); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: #dc2626; font-weight: 800;">⚔️ 1 100 Atq</span> &bull;
                            <span style="color: #2563eb; font-weight: 700;">🛡️ 7 450 Blindage</span> &bull;
                            <span style="color: #16a34a; font-weight: 800;">⚡ 6 000 Vitesse</span> &bull;
                            <span style="color: #b45309; font-weight: 700;">🎒 3 000 Fret</span>
                        </div>
                        <a href="?page=docs&tab=siege" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem;">
                            🏯 Voir au Chapitre 5 (Atelier de Siège) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOUVELLE ILLUSTRATION D'UNITÉ : EMBUSCADE SHINOBI MONTÉE -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(59, 130, 246, 0.04) 100%), var(--bg-surface, #fdfbf7); border: 1px solid rgba(139, 92, 246, 0.35); border-radius: 12px; padding: 1.5rem; border-left: 6px solid #8b5cf6;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <?php 
                    $shinobiDisk = __DIR__ . '/../public/assets/units/shinobi_monte_tokugawa.jpg';
                    $shinobiSrc = '/public/assets/units/shinobi_monte_tokugawa.jpg' . (file_exists($shinobiDisk) ? '?v=' . filemtime($shinobiDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Embuscade Shinobi Montée', '<?= $shinobiSrc ?>', 'Troupe Furtive de Raid Nocturne & Assassinat', 'Ombres ninja montant des coursiers noirs aux sabots étouffés de feutre. Surgissant des forêts de bambous dans un nuage de fumée pour frapper l\'arrière-garde adverse.', '« Vous entendrez le souffle du destrier au moment précis où le ninjato frappera. »')">
                    <img src="<?= $shinobiSrc ?>" alt="Embuscade Shinobi Montée" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #a78bfa; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        ⛩️ Clan Tokugawa &bull; Écuries Furtives
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #8b5cf6; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            ✨ Nouvelle Illustration Intégrée
                        </span>
                        <span style="font-size: 0.8rem; color: #8b5cf6; font-weight: 700;">Écuries & Haras Provinciaux (Vitesse Suprême)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Embuscade Shinobi Montée (徳川忍騎襲撃)
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        L'arme secrète du réseau d'espionnage de Tokugawa Ieyasu. Montés sur de puissants chevaux noirs aux sabots bandés de tissu pour une approche totalement silencieuse, ces cavaliers shinobi manient le sabre <em>ninjato</em> et des fumigènes de diversion. C'est l'unité la plus rapide de tout l'archipel (vitesse 14 000), capable de contourner les lignes de front pour dévaster les convois ennemis.
                    </p>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.8rem; background: var(--bg-ink, #ede5d5); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: #dc2626; font-weight: 800;">⚔️ 65 Atq</span> &bull;
                            <span style="color: #2563eb; font-weight: 700;">🛡️ 400 Blindage</span> &bull;
                            <span style="color: #8b5cf6; font-weight: 800;">⚡ 14 000 Vitesse (Max)</span> &bull;
                            <span style="color: #b45309; font-weight: 700;">🎒 60 Fret</span>
                        </div>
                        <a href="?page=docs&tab=siege" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem;">
                            🐎 Voir au Chapitre 5 (Écuries & Siège) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOUVELLE ILLUSTRATION D'UNITÉ : CATAPULTE FLAMBOYANTE HOROKUBIYA -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(234, 88, 12, 0.08) 0%, rgba(139, 92, 246, 0.04) 100%), var(--bg-surface, #fdfbf7); border: 1px solid rgba(234, 88, 12, 0.35); border-radius: 12px; padding: 1.5rem; border-left: 6px solid #ea580c;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <?php 
                    $catapultDisk = __DIR__ . '/../public/assets/units/catapulte_horokubiya_tokugawa.jpg';
                    $catapultSrc = '/public/assets/units/catapulte_horokubiya_tokugawa.jpg' . (file_exists($catapultDisk) ? '?v=' . filemtime($catapultDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Catapulte Flamboyante Horokubiya', '<?= $catapultSrc ?>', 'Artillerie Incendiaire de Siège & Bombardement', 'Machine de jet projetant des jarres de grès remplies de poudre noire et de résine enflammée (Horokubiya). L\'impact embrase les toitures et les palissades en bois des citadelles.', '« Une seule jarre de feu allume le bûcher d\'une forteresse tout entière. »')">
                    <img src="<?= $catapultSrc ?>" alt="Catapulte Flamboyante Horokubiya" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #ea580c; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🔥 Clan Tokugawa &bull; Atelier d'Artillerie
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #ea580c; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            ✨ Nouvelle Illustration Intégrée
                        </span>
                        <span style="font-size: 0.8rem; color: #ea580c; font-weight: 700;">Atelier de Siège Provincial (Dégâts de Zone Incendiaires)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Catapulte Flamboyante Horokubiya (焙烙火矢投石機)
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        L'artillerie dévastatrice des armées de Tokugawa. Construite en poutres massives de cyprès et protégée par des pavois <em>tate</em> en bambou arborant le kamon Tokugawa, cette machine de traction projette de lourdes jarres en terre cuite remplies de poudre et de résine enflammée. Les éclats de poterie et les gerbes de feu réduisent en cendres les défenses adverses.
                    </p>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.8rem; background: var(--bg-ink, #ede5d5); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: #dc2626; font-weight: 800;">⚔️ 450 Atq (Feu)</span> &bull;
                            <span style="color: #2563eb; font-weight: 700;">🛡️ 2 450 Blindage</span> &bull;
                            <span style="color: #16a34a; font-weight: 800;">⚡ 9 000 Vitesse</span> &bull;
                            <span style="color: #b45309; font-weight: 700;">🎒 900 Fret</span>
                        </div>
                        <a href="?page=docs&tab=siege" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem;">
                            🏯 Voir au Chapitre 5 (Atelier de Siège) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- NOUVELLE ILLUSTRATION D'UNITÉ : FORTERESSE ROULANTE BLINDÉE -->
        <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(109, 40, 217, 0.08) 0%, rgba(30, 64, 175, 0.04) 100%), var(--bg-surface, #fdfbf7); border: 1px solid rgba(109, 40, 217, 0.35); border-radius: 12px; padding: 1.5rem; border-left: 6px solid #6d28d9;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
                <?php 
                    $fortressDisk = __DIR__ . '/../public/assets/units/forteresse_roulante_tokugawa.jpg';
                    $fortressSrc = '/public/assets/units/forteresse_roulante_tokugawa.jpg' . (file_exists($fortressDisk) ? '?v=' . filemtime($fortressDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Forteresse Roulante Blindée', '<?= $fortressSrc ?>', 'Bastion Mobile Imprenable & Siège Lourd', 'Château mobile à plusieurs étages surmonté d\'un toit en pagode et blindé de plaques de fer forgé. Il abrite tireurs et officiers sous un feu nourri d\'arquebuses.', '« La patience d\'un roc, la force d\'une montagne qui marche. » — Tokugawa Ieyasu')">
                    <img src="<?= $fortressSrc ?>" alt="Forteresse Roulante Blindée" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #c084fc; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        ⛩️ Clan Tokugawa &bull; Bastion Suprême
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #6d28d9; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            ✨ Nouvelle Illustration Intégrée
                        </span>
                        <span style="font-size: 0.8rem; color: #6d28d9; font-weight: 700;">Atelier de Siège Provincial (Blindage Suprême du Jeu)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Forteresse Roulante Blindée (徳川移動要塞)
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Le chef-d'œuvre défensif de la stratégie de Tokugawa Ieyasu. Véritable citadelle sur roues en madriers de chêne et plaques de fer forgé rivetées, ce bastion mobile fait ricocher toutes les flèches adverses. Il abrite une redoutable batterie d'arquebusiers Tanegashima tirant par des meurtrières protégées et sert de point d'appui impénétrable pour les assauts de longue durée.
                    </p>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <div style="display: flex; gap: 0.5rem; font-size: 0.8rem; background: var(--bg-ink, #ede5d5); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                            <span style="color: #dc2626; font-weight: 800;">⚔️ 1 300 Atq</span> &bull;
                            <span style="color: #2563eb; font-weight: 800;">🛡️ 8 400 Blindage (Record)</span> &bull;
                            <span style="color: #16a34a; font-weight: 800;">⚡ 6 500 Vitesse</span> &bull;
                            <span style="color: #b45309; font-weight: 700;">🎒 2 500 Fret</span>
                        </div>
                        <a href="?page=docs&tab=siege" class="btn btn-primary" style="font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.85rem;">
                            🏯 Voir au Chapitre 5 (Atelier de Siège) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLEAU SYNOPTIQUE DE L'ARMEMENT FÉODAL (25 UNITÉS) -->
        <div class="card" style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem 2rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <h3 style="margin: 0; font-size: 1.3rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                        <span>⚔️</span> Les Deux Pôles Militaires du Shogunat (25 Régiments & Engins)
                    </h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: var(--text-muted);">
                        La puissance militaire d'OpenShogun s'articule autour de deux édifices castraux complémentaires, chacun illustré en haute définition :
                    </p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: #2563eb; font-size: 1.1rem;">🥋 Le Dojo Militaire (12 Troupes)</h4>
                        <span style="font-size: 0.75rem; font-weight: 700; background: rgba(37,99,235,0.1); color: #2563eb; padding: 2px 8px; border-radius: 4px;">Infanterie & Tir</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                        Forme les fantassins paysans conscrits (Piquiers Nagae-yari, conscrits légers), les corps de tireurs d'élite (Arquebusiers Tanegashima, Archers protecteurs de muraille), 
                        les maîtres bretteurs (Samouraïs au Katana, Bretteurs Nodachi) et la garde rapprochée Hatamoto.
                    </p>
                    <a href="?page=docs&tab=troops" class="btn btn-secondary" style="font-size: 0.8rem; font-weight: 700;">
                        Voir les 12 Troupes du Dojo &rarr;
                    </a>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: #dc2626; font-size: 1.1rem;">🐎 L'Atelier de Siège & Écuries (13 Engins)</h4>
                        <span style="font-size: 0.75rem; font-weight: 700; background: rgba(220,38,38,0.1); color: #dc2626; padding: 2px 8px; border-radius: 4px;">Cavalerie & Machines</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                        Élève la cavalerie rapide et lourde (Cavaliers d'interception, Cavalerie rouge Akazonae de Kai, Éclaireurs montés), 
                        fabrique les engins de démolition (Béliers d'acier, Catapultes incendiaires Horokubiya, Tours de siège balistes, Forteresses roulantes) 
                        et arme les convois logistiques et caravanes d'établissement castral.
                    </p>
                    <a href="?page=docs&tab=siege" class="btn btn-secondary" style="font-size: 0.8rem; font-weight: 700;">
                        Voir les 13 Engins & Montures &rarr;
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <?php if ($tab === 'hero' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 1 : LE HÉROS SAMOURAÏ & LA VOIE DU BUSHIDŌ
             ============================================================== -->
        <div class="card" id="chapitre-heros" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid var(--red-primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--red-primary); text-transform: uppercase; letter-spacing: 1px;">Chapitre 1</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🥋</span> Le Héros Samouraï : Guide Intégral & Voie du Guerrier
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Directement inspiré des seigneurs de guerre de l'époque Sengoku et des mécaniques héroïques classiques de Travian, 
                        votre Héros Samouraï est un champion unique qui évolue en même temps que votre dynastie.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Points / Niveau</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: var(--red-primary);">+4 Points d'Attributs</div>
                </div>
            </div>

            <!-- Présentation Illustrée du Héros Samouraï -->
            <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; background: var(--bg-ink); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <div style="width: 200px; height: 260px; border-radius: 10px; overflow: hidden; border: 2px solid var(--red-primary); box-shadow: 0 4px 15px rgba(0,0,0,0.15); flex-shrink: 0; cursor: pointer; position: relative;"
                     onclick="openDocsLightbox('Le Héros Samouraï', '/public/assets/hero_samurai.jpg', 'Champion Suprême & Général d\'Armée', 'Commandant d\'élite au Katana enflammé et au Gunbai de commandement, le Samouraï mène vos légions au combat, explore les sanctuaires oubliés et fortifie les fiefs.', '« La voie du guerrier réside dans la détermination sans faille. »')"
                     title="Cliquer pour admirer l'illustration en grand format">
                    <img src="/public/assets/hero_samurai.jpg" alt="Le Héros Samouraï" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 280px;">
                    <span style="display: inline-block; background: rgba(220,38,38,0.1); color: var(--red-primary); font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; margin-bottom: 0.5rem; border: 1px solid rgba(220,38,38,0.2);">
                        🥋 CHAMPION DU DOMAINE CASTAL
                    </span>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.3rem; color: var(--text-main);">
                        Le Daimyō Champion & La Voie du Katana
                    </h3>
                    <p style="margin: 0 0 1rem 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                        Paré de son armure laquée ornée d'un dragon impérial, coiffé de son kabuto flamboyant et brandissant son katana fendeur d'acier avec son éventail de guerre (<em>gunbai</em>), votre héros incarne l'âme martiale et la puissance de conquête de votre clan féodal.
                    </p>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <div style="background: var(--bg-surface); padding: 0.5rem 0.85rem; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.8rem;">
                            <strong style="color: #dc2626;">⚔️ Polyvalence Totale :</strong> Combats personnels, raids, sièges & bonus économiques
                        </div>
                        <div style="background: var(--bg-surface); padding: 0.5rem 0.85rem; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.8rem;">
                            <strong style="color: #ea580c;">🗺️ Aventures Périlleuses :</strong> Découverte d'XP, vivres et reliques légendaires
                        </div>
                    </div>
                </div>
            </div>

            <!-- Les 4 Piliers d'Attributs -->
            <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>📊</span> 1. Les Quatre Piliers d'Attributs Martiaux
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <strong style="color: #dc2626; font-size: 1rem;">⚔️ Force Martiale</strong>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(220,38,38,0.1); color: #dc2626; padding: 2px 6px; border-radius: 4px;">+80 Puissance / pt</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Puissance combative personnelle du héros. Une valeur élevée lui permet de remporter des Aventures périlleuses avec des blessures minimes, et de décimer les animaux des oasis sans dépendre d'une vaste escorte.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #ea580c;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <strong style="color: #ea580c; font-size: 1rem;">🚩 Commandement Offensif</strong>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(234,88,12,0.1); color: #ea580c; padding: 2px 6px; border-radius: 4px;">+0.2% Attaque / pt</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Aura martiale qui galvanise vos armées de conquête. Lorsqu'il accompagne un détachement en raid ou en siège, toutes les troupes bénéficient de ce pourcentage de dégâts additionnel.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <strong style="color: #2563eb; font-size: 1rem;">🛡️ Maîtrise Défensive</strong>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(37,99,235,0.1); color: #2563eb; padding: 2px 6px; border-radius: 4px;">+0.2% Défense / pt</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Tactique de siège et défense du château. Lorsque le héros monte la garde dans le fief, toute la garnison retranchée derrière vos courtines gagne un bonus multiplicateur de résistance.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #16a34a;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <strong style="color: #16a34a; font-size: 1rem;">🌾 Gouvernance Féodale</strong>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(22,163,74,0.1); color: #16a34a; padding: 2px 6px; border-radius: 4px;">Spécialisation Terroir</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Supervise les récoltes et carrières de votre domaine. Vous pouvez orienter sa gouvernance de manière équilibrée sur les trois ressources ou la concentrer à 100% sur le Bois, la Pierre ou le Riz.
                    </p>
                </div>
            </div>

            <!-- Les Aventures & Expéditions -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--red-primary); font-size: 1rem;">
                        🗺️ 2. Aventures en Carte du Monde
                    </h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.5rem 0;">
                        Des aventures apparaissent dynamiquement sur la carte provinciale. En envoyant votre héros en expédition, il affronte des embuscades, 
                        secourt des sanctuaires isolés et rapporte de l'<strong>expérience (XP)</strong>, des <strong>artefacts légendaires</strong>, des vivres et des troupes de ralliement.
                    </p>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0 0 0.4rem 0;">
                        <em>Attention : chaque aventure inflige une perte de santé calculée selon la difficulté du terrain et la Force martiale de votre samouraï !</em>
                    </p>
                    <p style="font-size: 0.82rem; color: #d97706; margin: 0; font-weight: 600;">
                        ⏳ <strong>Quota féodal :</strong> Votre Samouraï peut accomplir <strong>au maximum 3 aventures par jour</strong>. Le quota se réinitialise chaque nuit à minuit.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: #7c3aed; font-size: 1rem;">
                        ⛩️ 3. Arsenal, Reliques & Résurrection
                    </h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.5rem 0;">
                        Votre héros possède 5 emplacements d'inventaire : <strong>Arme de poing</strong>, <strong>Casque Kabuto</strong>, <strong>Cuirasse &amp; Armure</strong>, <strong>Monture &amp; Destrier</strong> et <strong>Talisman Shintō</strong>.
                    </p>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0 0 0.4rem 0;">
                        ⛩️ <strong>Règle des Reliques Uniques :</strong> Chaque relique du Japon féodal est unique. <strong>Vous ne pouvez jamais obtenir deux fois la même relique</strong> dans vos aventures.
                    </p>
                    <p style="font-size: 0.82rem; color: #dc2626; margin: 0; font-weight: 600;">
                        💀 <strong>Régénération Post-Mortem :</strong> En cas de décès au combat, vous ne perdez <strong>jamais</strong> son niveau ni ses points. La <strong>régénération du héros après sa mort dure 24 heures</strong>, à l'issue desquelles il recouvrira l'intégralité de ses points de vie (100% PV).
                    </p>
                </div>
            </div>

            <!-- 4. Le Panthéon des 35 Reliques Féodales Uniques -->
            <div style="background: var(--bg-ink); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--border-color); margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0; font-size: 1.15rem; color: var(--red-primary); display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏆</span> 4. Le Panthéon Sacré des 35 Reliques Féodales Uniques
                        </h4>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                            35 reliques réparties équitablement en 5 familles de 7 reliques chacune. Chaque objet confère des bonus passifs majeurs.
                        </div>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 800; background: rgba(194,37,43,0.1); color: var(--red-primary); padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(194,37,43,0.2);">
                        7 Armes &bull; 7 Casques &bull; 7 Armures &bull; 7 Montures &bull; 7 Talismans
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                    <!-- 1. Armes -->
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); border-top: 3px solid #dc2626;">
                        <strong style="color: #dc2626; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                            🗡️ Armes du Samouraï (7)
                        </strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            <em>Katana Tamahagane, Yari Ancestrale, Gunbai Impérial, Nodachi Tempête, Naginata Bugeisha, Grand Arc Yumi, Tantō Masamune.</em>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #dc2626; font-weight: 700;">
                            Bonus : Force de combat brute (+200 à +450) &amp; Attaque offensive (+1.0% à +2.5%).
                        </div>
                    </div>

                    <!-- 2. Casques -->
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); border-top: 3px solid #f59e0b;">
                        <strong style="color: #f59e0b; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                            🪖 Casques &amp; Masques Kabuto (7)
                        </strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            <em>Cornes d'Or du Shōgun, Croissant de Sendai, Menpō Oni, Dragon de Kai, Kasa Shinobi, Soleil Levant, Cornes de Cerf Sanada.</em>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #d97706; font-weight: 700;">
                            Bonus : Résistance aux chocs, Défense de siège (+1.2% à +2.2%) &amp; Gain d'XP (+10% à +20%).
                        </div>
                    </div>

                    <!-- 3. Armures -->
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); border-top: 3px solid #2563eb;">
                        <strong style="color: #2563eb; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                            🥋 Armures &amp; Cuirasses Ō-Yoroi (7)
                        </strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            <em>Cuirasse Ō-Yoroi, Armure Dō-Maru, Plastron Nanban, Armure Écarlate Ii Naomasa, Jimbaori Damassé, Haramaki Léger, Armure d'Ébène Takeda.</em>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #2563eb; font-weight: 700;">
                            Bonus : Protection absolue de l'armée, Défense de garnison (+1.2% à +2.5%) &amp; Force.
                        </div>
                    </div>

                    <!-- 4. Montures -->
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); border-top: 3px solid #0891b2;">
                        <strong style="color: #0891b2; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                            🐎 Montures &amp; Destriers (7)
                        </strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            <em>Étalon de Kai, Destrier Noir de Kiso, Destrier au Bamen de Fer, Cheval Bai de Musashi, Étalon Blanc Benzaiten, Pur-Sang Date, Destrier Ambré de Kyoto.</em>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #0891b2; font-weight: 700;">
                            Bonus : Vitesse d'expédition prodigieuse (+25% à +45%) &amp; Réduction du temps de marche.
                        </div>
                    </div>

                    <!-- 5. Talismans -->
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); border-top: 3px solid #16a34a;">
                        <strong style="color: #16a34a; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.4rem;">
                            📿 Talismans &amp; Trésors Sacrés (7)
                        </strong>
                        <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                            <em>Omamori Inari, Miroir Sacré de Yata, Magatama en Jade, Clochette Kagura, Parchemin Dokkōdō, Perle de Marée Ryūjin, Sceau du Chrysanthème.</em>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #16a34a; font-weight: 700;">
                            Bonus : Production de ressources (+30 à +50/h Bois, Pierre, Riz), Prestige impérial &amp; XP.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'resources' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 2 : TERROIR & CHOIX LIBRE DES PARCELLES (1-18)
             ============================================================== -->
        <div class="card" id="chapitre-terroir" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #16a34a;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #16a34a; text-transform: uppercase; letter-spacing: 1px;">Chapitre 2</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🌾</span> Terroir Féodal & Choix Libre des 18 Parcelles
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Le terroir entourant votre donjon comporte 18 parcelles de production (numérotées de #1 à #18). 
                        Vous avez désormais l'entière liberté de décider de la nature de chaque parcelle !
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Parcelles Rurales</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #16a34a;">18 Slots Modulaires</div>
                </div>
            </div>

            <!-- Explication du Système de Slot Libre -->
            <div style="background: rgba(22,163,74,0.06); border: 1px solid rgba(22,163,74,0.2); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6; font-size: 0.9rem; color: var(--text-main);">
                <strong>🌟 Comment fonctionne la sélection libre de ressource ?</strong><br>
                Sur la page <em>Terroir & Ressources</em>, chaque parcelle non développée (niveau 0) apparaît sous forme de terrain agricole vacant arborant une puce <code>+</code>. 
                En cliquant sur cette parcelle, une fenêtre modale s'ouvre pour vous permettre de sélectionner le type d'édifice souhaité :
                scierie, carrière, rizière ou sanctuaire. Une fois le chantier initié, la parcelle adopte ce type. En cas d'annulation avant le niveau 1, le terrain est immédiatement libéré !
            </div>

            <!-- Les 4 Éléments du Terroir -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-metal); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 130px; border-radius: 6px; overflow: hidden; margin-bottom: 0.75rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Camp de Bûcherons', '/public/assets/resources/ressource_bois_cedre.jpg', 'Ressource Primaire : Bois de Cèdre', 'Abat et débite les cèdres centenaires dans les forêts de montagne. Indispensable pour dresser les charpentes de vos donjons, palissades fortifiées, béliers et armes d\'hast.', '« Les cèdres millénaires portent les toits de nos châteaux. »')">
                            <img src="/public/assets/resources/ressource_bois_cedre.jpg" alt="Bois de Cèdre" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 6px; right: 6px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.5rem 0; color: var(--res-metal);">🪵 Camp de Bûcherons (Bois de Cèdre)</h4>
                        <p style="margin:0 0 0.5rem 0; font-size:0.85rem; color: var(--text-muted);">
                            Abat et débite les cèdres centenaires. Ressource première pour ériger les charpentes de châteaux, bâtir les béliers et tailler les lances des fantassins.
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--res-metal);">Spécialité du Clan Oda</span>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-crystal); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 130px; border-radius: 6px; overflow: hidden; margin-bottom: 0.75rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Carrière de Granit', '/public/assets/resources/ressource_pierre_taille.jpg', 'Ressource Primaire : Pierre de Taille', 'Extrait les blocs de roche et de granit pour monter les remparts cyclopéens (Nozura-zumi) et les fondations imprenables des donjons historiques.', '« Une muraille sans faille résiste à mille assauts. »')">
                            <img src="/public/assets/resources/ressource_pierre_taille.jpg" alt="Pierre de Taille" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 6px; right: 6px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.5rem 0; color: var(--res-crystal);">🪨 Carrière de Granit (Pierre de Taille)</h4>
                        <p style="margin:0 0 0.5rem 0; font-size:0.85rem; color: var(--text-muted);">
                            Extrait les blocs de roche pour monter les remparts cyclopéens (Nozura-zumi) et les fondations imprenables des donjons historiques.
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--res-crystal);">Spécialité du Clan Tokugawa</span>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-deut); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 130px; border-radius: 6px; overflow: hidden; margin-bottom: 0.75rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Rizières Inondées', '/public/assets/resources/ressource_riz_imperial.jpg', 'Ressource Impériale : Riz (Koku)', 'Base nourricière de toute la principauté mesurée en koku. Chaque soldat, monture et engin formé exige des rations de riz pour sa subsistance.', '« Le koku de riz est l\'or véritable du Shogunat. »')">
                            <img src="/public/assets/resources/ressource_riz_imperial.jpg" alt="Riz Impérial" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 6px; right: 6px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.5rem 0; color: var(--res-deut);">🌾 Rizières Inondées (Riz Impérial / Koku)</h4>
                        <p style="margin:0 0 0.5rem 0; font-size:0.85rem; color: var(--text-muted);">
                            Base nourricière de toute la principauté. Chaque soldat, monture et engin formé exige des rations de riz pour sa subsistance et son entraînement.
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--res-deut);">Spécialité du Clan Takeda</span>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid var(--res-energy); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 130px; border-radius: 6px; overflow: hidden; margin-bottom: 0.75rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Sanctuaire Shintō & Torii', '/public/assets/resources/ressource_ferveur_shinto.jpg', 'Énergie Divine : Ferveur & Sérénité', 'Honore les esprits tutélaires Kami. Maintient l\'harmonie spirituelle et l\'énergie indispensable au rendement de toutes les parcelles du domaine.', '« La paix de l\'esprit féconde la terre des ancêtres. »')">
                            <img src="/public/assets/resources/ressource_ferveur_shinto.jpg" alt="Sanctuaire Shinto" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 6px; right: 6px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.5rem 0; color: var(--res-energy);">⛩️ Sanctuaire d'Inari (Sérénité Spirituelle)</h4>
                        <p style="margin:0 0 0.5rem 0; font-size:0.85rem; color: var(--text-muted);">
                            Diffuse la ferveur et l'énergie spirituelle sur le terroir. <em>Attention : si la consommation dépasse la production des sanctuaires, vos récoltes chutent à 10% !</em>
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--res-energy);">Énergie vitale pour tous les clans</span>
                </div>
            </div>

            <!-- Démantèlement & Réinitialisation d'une Parcelle -->
            <div style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; padding: 1.25rem; margin-top: 1.5rem; line-height: 1.6; font-size: 0.9rem; color: var(--text-main);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.25rem;">🗑️</span>
                    <strong style="color: #ef4444; font-size: 1rem;">Démanteler une Exploitation Rurale (Changement de Terroir)</strong>
                </div>
                <p style="margin: 0 0 0.5rem 0; font-size: 0.88rem; color: var(--text-muted);">
                    Vous avez besoin de plus de bois pour vos engins de siège ou de plus de rizières pour nourrir votre cavalerie ? 
                    Chaque exploitation développée (niveau 1 ou supérieur) peut être <strong>rasée</strong> depuis sa vue détaillée.
                </p>
                <ul style="padding-left: 1.25rem; margin: 0; font-size: 0.84rem; color: var(--text-muted); line-height: 1.6;">
                    <li>⏳ <strong>Compte à rebours de démolition :</strong> Les travaux prennent 50% de la durée de construction du niveau. Pendant ce temps, la parcelle affiche un badge <code>🗑️</code> et reste visible sur votre domaine.</li>
                    <li>🛑 <strong>Annulation sans risque :</strong> Vous pouvez interrompre le démantèlement à tout instant pour préserver votre exploitation intacte.</li>
                    <li>💰 <strong>Remboursement de 30% :</strong> Dès que le chrono expire, 30% des matériaux du niveau sont immédiatement recrédités dans vos greniers et la parcelle redevient un terrain vierge (<code>+</code>), prête à accueillir une autre ressource de votre choix !</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'city' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 3 : CITÉ CASTRALE & SILOS MODULAIRES (19-34)
             ============================================================== -->
        <div class="card" id="chapitre-cite" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: 1px;">Chapitre 3</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🏯</span> Cité Castrale & Silos Modulaires Libres (Slots 19 à 34)
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Le cœur urbain de votre domaine comporte 16 emplacements castraux (numérotés de #19 à #34). 
                        Vous disposez d'une liberté totale pour implanter vos édifices sur n'importe quel terrain libre !
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Emplacements Urbains</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #2563eb;">16 Terrains Modulaires</div>
                </div>
            </div>

            <div style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.2); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6; font-size: 0.9rem; color: var(--text-main);">
                <strong>🏗️ Comment construire librement dans la cité ?</strong><br>
                Sur la vue de la Cité Castrale, tout terrain non construit affiche une puce <code>+</code>. 
                En cliquant sur l'emplacement libre de votre choix (slots #19 à #33), le modal féodal vous présente tous les bâtiments disponibles à la construction. 
                Vous pouvez bâtir plusieurs entrepôts, greniers ou ateliers de siège selon vos ambitions dynastiques ! Le slot #34 est réservé à la Muraille pour encercler la forteresse.
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
                <!-- Tenshu -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Tenshu (Donjon Castral)', '/public/assets/buildings/building_tenshu.jpg', 'Siège du Commandement & Palais du Daimyō', 'Le donjon fortifié et palais du Daimyō. Réduit la durée de construction de tous les bâtiments urbains et parcelles rurales du fief.', '« Du haut du Tenshu, le regard du Daimyō embrasse la province. »')">
                            <img src="/public/assets/buildings/building_tenshu.jpg" alt="Tenshu" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: var(--red-primary);">🏯 Tenshu (Donjon Castral)</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Cœur du commandement. Réduit le temps de construction de tous les édifices urbains.</p>
                    </div>
                </div>

                <!-- Entrepôt -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Entrepôt de Matériaux', '/public/assets/buildings/building_storage.jpg', 'Logistique : Bois & Pierre', 'Augmente la capacité de stockage maximale de Bois de Cèdre et de Pierre de Taille pour alimenter les chantiers monumentaux.', '« Une armée sans vivres et sans bois ne peut tenir l\'hiver. »')">
                            <img src="/public/assets/buildings/building_storage.jpg" alt="Entrepôt" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: var(--res-metal);">🪵 Entrepôt de Matériaux (Bois & Pierre)</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Stocke le bois de cèdre et les pierres de taille nécessaires aux chantiers d'envergure.</p>
                    </div>
                </div>

                <!-- Grenier Kura -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Grenier à Riz Fortifié (Kura)', '/public/assets/buildings/building_tank.jpg', 'Silos à Grains Ignifugés', 'Augmente la capacité de stockage maximale des récoltes de riz impérial (koku) et protège les surplus contre l\'humidité.', '« Les greniers pleins font les daimyōs puissants. »')">
                            <img src="/public/assets/buildings/building_tank.jpg" alt="Grenier Kura" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: var(--res-deut);">🌾 Grenier à Riz Fortifié (Kura)</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Préserve les récoltes de riz impérial contre les pillages et soutient les armées.</p>
                    </div>
                </div>

                <!-- Dojo Militaire -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Dojo Militaire & Caserne', '/public/assets/buildings/building_barracks.jpg', 'Caserne & Enrôlement Féodal', 'Entraîne les fantassins Ashigarus, archers Yumi, arquebusiers Tanegashima et samouraïs d\'élite pour la défense et les conquêtes.', '« La discipline forge la lame, l\'honneur guide le coup. »')">
                            <img src="/public/assets/buildings/building_barracks.jpg" alt="Dojo Militaire" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #2563eb;">🥋 Dojo Militaire & Caserne</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Entraîne et arme vos fantassins, piquiers, archers et samouraïs d'assaut.</p>
                    </div>
                </div>

                <!-- Atelier de Siège & Écuries -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Atelier de Siège & Écuries', '/public/assets/buildings/building_shipyard.jpg', 'Génie Militaire & Cavalerie', 'Permet d\'élever la cavalerie de guerre montées, convois de ravitaillement et de fabriquer béliers géants et catapultes de siège.', '« Le tonnerre des sabots annonce l\'effondrement des portes ennemies. »')">
                            <img src="/public/assets/buildings/building_shipyard.jpg" alt="Atelier de Siège" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #dc2626;">🐎 Atelier de Siège & Écuries</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Fabrique les catapultes, béliers de siège et destriers caparaçonnés de cavalerie.</p>
                    </div>
                </div>

                <!-- Marché Féodal -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Marché Féodal & Caravanes', '/public/assets/buildings/building_market.jpg', 'Commerce Provincial & Convois', 'Permet d\'échanger des ressources avec les marchands itinérants et autres daimyōs provinciaux via des convois de marchands.', '« L\'or et le riz circulent là où la paix règne. »')">
                            <img src="/public/assets/buildings/building_market.jpg" alt="Marché Féodal" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #b45309;">⚖️ Marché Féodal & Caravanes</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Organise les échanges commerciaux et envoie des caravanes de vivres aux alliés.</p>
                    </div>
                </div>

                <!-- Académie & Forge -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Académie des Savoirs & Forge', '/public/assets/buildings/building_research_lab.jpg', 'Recherche Stratégique & Tamahagane', 'Permet de perfectionner la métallurgie du tamahagane, l\'art de la guerre et les tactiques militaires secrètes.', '« Le savoir des anciens aiguise l\'acier de demain. »')">
                            <img src="/public/assets/buildings/building_research_lab.jpg" alt="Académie des Savoirs" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #7c3aed;">📜 Académie des Savoirs & Forge</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Développe les technologies d'armement, métallurgie et art de la guerre.</p>
                    </div>
                </div>

                <!-- Tour de Guet Yagura -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Tour de Guet Yagura', '/public/assets/buildings/building_radar.jpg', 'Vigie & Feux d\'Alarme', 'Surveille les vallées et détecte à l\'avance les armées et espions ennemis en marche vers votre forteresse.', '« L\'œil qui veille au crépuscule prévient le massacre de l\'aube. »')">
                            <img src="/public/assets/buildings/building_radar.jpg" alt="Tour de Guet" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #0891b2;">🔭 Tour de Guet Yagura</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Détecte à l'avance les mouvements de troupes ennemies marchant vers votre fief.</p>
                    </div>
                </div>

                <!-- Cachette Secrète -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Cachette Secrète Sous Terre', '/public/assets/buildings/building_quantum_vault.jpg', 'Caveau Inviolable Anti-Pillage', 'Protège une réserve secrète de vivres et matériaux contre les pillages adverses (capacité doublée pour le Clan Tokugawa).', '« Ce que l\'œil de l\'ennemi ne voit pas ne peut être dérobé. »')">
                            <img src="/public/assets/buildings/building_quantum_vault.jpg" alt="Cachette Secrète" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #475569;">🕳️ Cachette Secrète Sous Terre</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Met vos précieuses ressources à l'abri des pillages lors des raids ennemis.</p>
                    </div>
                </div>

                <!-- Pavillon Diplomatique -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Pavillon Diplomatique des Clans', '/public/assets/buildings/building_embassy.jpg', 'Maison de Thé & Traités d\'Alliance', 'Permet de sceller ou rejoindre un pacte d\'alliance entre daimyōs sous les auspices des cérémonies du thé.', '« Une alliance scellée dans l\'honneur vaut cent divisions d\'infanterie. »')">
                            <img src="/public/assets/buildings/building_embassy.jpg" alt="Pavillon Diplomatique" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #059669;">⛩️ Pavillon Diplomatique</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Permet de fonder ou rejoindre une alliance entre puissants seigneurs féodaux.</p>
                    </div>
                </div>

                <!-- Muraille & Remparts -->
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="width: 100%; height: 110px; border-radius: 6px; overflow: hidden; margin-bottom: 0.6rem; border: 1px solid var(--border-color); cursor: pointer; position: relative;" onclick="openDocsLightbox('Muraille & Remparts de Cité', '/public/assets/buildings/building_wall.jpg', 'Enceinte Fortifiée & Douves (Slot #34)', 'Enceinte fortifiée en pierre de taille, palissades en cèdre et douves protégeant le fief (+4% défense garnison par niveau).', '« Nos remparts sont le roc où se brisent les vagues ennemies. »')">
                            <img src="/public/assets/buildings/building_wall.jpg" alt="Muraille & Remparts" style="width: 100%; height: 100%; object-fit: cover;">
                            <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fde047; font-size: 0.68rem; padding: 1px 5px; border-radius: 3px;">🔍 Agrandir</span>
                        </div>
                        <h4 style="margin:0 0 0.4rem 0; color: #16a34a;">🧱 Muraille & Remparts de Cité (Slot #34)</h4>
                        <p style="margin:0; font-size:0.83rem; color: var(--text-muted); line-height: 1.4;">Protège l'enceinte entière et décuple l'efficacité défensive de votre garnison (+4% par niveau).</p>
                    </div>
                </div>
            </div>

            <!-- Double Bandeau : Devise du Daimyō & Démantèlement Urbain -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-top: 1.5rem;">
                <!-- Devise du Daimyō -->
                <div style="background: linear-gradient(135deg, rgba(30, 27, 75, 0.08) 0%, rgba(253, 251, 247, 0.98) 100%); border: 1px solid rgba(220, 38, 38, 0.35); border-radius: 8px; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.3rem;">📜</span>
                        <h4 style="margin: 0; color: var(--red-primary); font-size: 1rem;">Devise & Chronique Officielle du Daimyō</h4>
                    </div>
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                        C'est du sommet de votre <strong>Donjon Tenshu (Slot #19)</strong> que vous proclamez la devise qui guide vos samouraïs. 
                        D'un simple clic sur <em>« Modifier ma Devise »</em>, vous pouvez graver votre serment de guerre, visible par tous les autres daimyōs sur votre profil féodal !
                    </p>
                </div>

                <!-- Démantèlement Urbain -->
                <div style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.3rem;">🗑️</span>
                        <h4 style="margin: 0; color: #ef4444; font-size: 1rem;">Démanteler une Bâtisse (Libérer un Slot)</h4>
                    </div>
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                        Besoin de réorganiser votre cité pour bâtir un second Dojo ou un grand marché ? 
                        Chaque bâtiment (sauf le Tenshu protégé) peut être <strong>rasé</strong> avec un compte à rebours de démolition (50% de la durée). 
                        Annulable à tout moment sans perte, l'achèvement des travaux vous rembourse <strong>30% des matériaux</strong> et libère l'emplacement (<code>+</code>).
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'troops' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 4 : TROUPES DU DOJO (12 RÉGIMENTS FÉODAUX)
             ============================================================== -->
        <div class="card" id="chapitre-troupes" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: 1px;">Chapitre 4</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🥋</span> Troupes du Dojo & Infanterie des Trois Grands Clans
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Entraînées au Dojo militaire, ces 12 unités d'infanterie et de tir constituent la ligne de front de chaque clan féodal.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Infanterie de Clan</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #2563eb;">12 Régiments Uniques</div>
                </div>
            </div>

            <!-- Grille des 3 clans pour les troupes -->
            <?php foreach ($clansMeta as $cKey => $clan): 
                $cUnits = $unitsByClan[$cKey] ?? [];
            ?>
                <div class="clan-section" id="clan-section-<?= $cKey ?>" style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid <?= $clan['color'] ?>44;">
                        <span style="font-size: 1.5rem;"><?= $clan['icon'] ?></span>
                        <h3 style="margin: 0; font-size: 1.3rem; color: <?= $clan['color'] ?>;">
                            <?= htmlspecialchars($clan['name']) ?> &bull; <?= htmlspecialchars($clan['daimyo']) ?>
                        </h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: auto;">
                            <?= htmlspecialchars($clan['doctrine']) ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
                        <?php foreach ($cUnits as $u): 
                            $tactics = $unitTactics[$u['code']] ?? [
                                'role' => 'Combattant',
                                'lore_detail' => $u['description'],
                                'strengths' => 'Discipline martiale.',
                                'weaknesses' => 'N/A',
                                'quote' => '« Pour l\'honneur du clan ! »'
                            ];
                            $imgName = !empty($u['image']) ? $u['image'] : ($u['code'] . '.jpg');
                            $diskPath = __DIR__ . '/../public/assets/units/' . $imgName;
                            $imgSrc = '/public/assets/units/' . $imgName . (file_exists($diskPath) ? '?v=' . filemtime($diskPath) : '');
                        ?>
                            <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;">
                                <div style="position: relative; height: 180px; overflow: hidden; background: var(--bg-ink); cursor: pointer;"
                                     onclick="openDocsLightbox('<?= addslashes($u['name']) ?>', '<?= $imgSrc ?>', '<?= addslashes($tactics['role']) ?>', '<?= addslashes($tactics['lore_detail']) ?>', '<?= addslashes($tactics['quote']) ?>')">
                                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($u['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                    <span style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                        Rang <?= $u['tier'] ?>
                                    </span>
                                </div>
                                <div style="padding: 1rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: var(--text-main);"><?= htmlspecialchars($u['name']) ?></h4>
                                        <div style="font-size: 0.75rem; color: <?= $clan['color'] ?>; font-weight: 700; margin-bottom: 0.5rem;"><?= htmlspecialchars($tactics['role']) ?></div>
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; font-size: 0.75rem; text-align: center; margin-bottom: 0.75rem; background: var(--bg-ink); padding: 6px; border-radius: 6px;">
                                            <div><span style="color:#dc2626; font-weight:800;">⚔️ <?= $u['attack'] ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Attaque</span></div>
                                            <div><span style="color:#2563eb; font-weight:700;">🛡️ <?= $u['def_infantry'] ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Déf. Inf</span></div>
                                            <div><span style="color:#16a34a; font-weight:700;">🐎 <?= $u['def_mech'] ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Déf. Cav</span></div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                                        <strong style="color: #15803d;">Atout :</strong> <?= htmlspecialchars($tactics['strengths']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'siege' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 5 : ATELIER DE SIÈGE & ÉCURIES (13 ENGINS & CAVALERIE)
             ============================================================== -->
        <div class="card" id="chapitre-siege" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #dc2626;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 1px;">Chapitre 5</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🐎</span> Atelier de Siège & Écuries Provinciales (13 Unités)
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Construits à l'Atelier de Siège et aux Écuries, ces 13 engins massifs, destriers cuirassés et convois logistiques 
                        sont indispensables pour faire tomber les murailles ennemies et acheminer les récoltes de votre principauté.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Machines & Destriers</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #dc2626;">13 Engins Illustrés</div>
                </div>
            </div>

            <!-- Grille des 13 Engins de Siège -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                <?php foreach ($allShips as $s): 
                    $code = $s['code'];
                    $meta = $shipTactics[$code] ?? [
                        'role' => 'Engin de Siège',
                        'lore_detail' => $s['description'],
                        'strengths' => 'Puissance offensive.',
                        'weaknesses' => 'Coût en matériaux.',
                        'quote' => '« La forteresse plie sous l\'assaut. »'
                    ];
                    $imgFile = !empty($s['image']) ? $s['image'] : ($code . '.jpg');
                    $diskPath = __DIR__ . '/../public/assets/units/' . $imgFile;
                    $imgSrc = '/public/assets/units/' . $imgFile . (file_exists($diskPath) ? '?v=' . filemtime($diskPath) : '');
                    $clanBadge = match($s['faction']) {
                        'terran' => ['name' => 'Clan Oda', 'color' => '#3b82f6', 'icon' => '🏯'],
                        'vorash' => ['name' => 'Clan Takeda', 'color' => '#ef4444', 'icon' => '🐎'],
                        'aethelis' => ['name' => 'Clan Tokugawa', 'color' => '#8b5cf6', 'icon' => '⛩️'],
                        default => ['name' => 'Logistique & Convois', 'color' => '#16a34a', 'icon' => '📦']
                    };
                ?>
                    <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="position: relative; height: 200px; overflow: hidden; background: var(--bg-ink); cursor: pointer;"
                             onclick="openDocsLightbox('<?= addslashes($s['name']) ?>', '<?= $imgSrc ?>', '<?= addslashes($meta['role']) ?>', '<?= addslashes($meta['lore_detail']) ?>', '<?= addslashes($meta['quote']) ?>')">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; object-position: top center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.8); color: <?= $clanBadge['color'] ?>; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                                <?= $clanBadge['icon'] ?> <?= $clanBadge['name'] ?>
                            </span>
                        </div>
                        <div style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h4 style="margin: 0 0 0.25rem 0; font-size: 1.1rem; color: var(--text-main);"><?= htmlspecialchars($s['name']) ?></h4>
                                <div style="font-size: 0.8rem; color: <?= $clanBadge['color'] ?>; font-weight: 700; margin-bottom: 0.75rem;"><?= htmlspecialchars($meta['role']) ?></div>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; font-size: 0.8rem; text-align: center; margin-bottom: 0.75rem; background: var(--bg-ink); padding: 8px; border-radius: 6px;">
                                    <div><span style="color:#dc2626; font-weight:800;">⚔️ <?= $s['attack'] ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Attaque</span></div>
                                    <div><span style="color:#2563eb; font-weight:700;">🛡️ <?= ($s['defense'] + $s['shield']) ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Blindage</span></div>
                                    <div><span style="color:#b45309; font-weight:700;">🎒 <?= $s['cargo_capacity'] ?></span><br><span style="font-size:0.65rem; color:var(--text-muted);">Fret</span></div>
                                </div>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.5rem;">
                                <strong style="color: #15803d;">Atout :</strong> <?= htmlspecialchars($meta['strengths']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'oasis' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 6 : OASIS SAUVAGES, FAUNE HOSTILE & ANNEXIONS
             ============================================================== -->
        <div class="card" id="chapitre-oasis" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #b45309;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 1px;">Chapitre 6</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🌴</span> Oasis Sauvages, Faune Hostile & Conquête Économique
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Inspirées du système classique de Travian, les oasis parsèment la carte des provinces et constituent des nœuds stratégiques cruciaux.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Bonus de Récolte</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #b45309;">+25% à +50%</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: #b45309;">🐾 1. Faune Sauvage Protectrice</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Chaque oasis inoccupée est peuplée par des bêtes sauvages (sangliers des monts, meutes de loups féroces, ours géants d'Hokkaido). 
                        Leur puissance défensive protège les richesses naturelles de l'oasis contre les prédateurs.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: #dc2626;">⚔️ 2. Raids de Pillage & Butins</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Tant que des animaux y subsistent, envoyer vos troupes ou votre Héros Samouraï permet d'éliminer la faune et de 
                        dérober instantanément les ressources stockées (bois, pierre, riz). Idéal pour accélérer le développement initial.
                    </p>
                </div>

                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem 0; color: #16a34a;">🏰 3. Annexion & Bonus Permanents</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Une fois tous les animaux terrassés, y dépêcher une armée avec votre Héros Samouraï permet d'annexer l'oasis à votre fief. 
                        Elle octroie alors un bonus permanent de <strong>+25% ou +50%</strong> sur la production horaire de votre domaine !
                    </p>
                </div>
            </div>

            <!-- ==============================================================
                 BESTIAIRE DES BÊTES SAUVAGES GARDIENNES D'OASIS
                 ============================================================== -->
            <div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px dashed var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 1px;">Bestiaire des Provinces</span>
                        <h3 style="margin: 0.2rem 0 0.4rem 0; font-size: 1.4rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                            <span>🐾</span> Faune Hostile & Bêtes Sauvages des Oasis
                        </h3>
                        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                            Avant de pouvoir annexer une oasis ou récolter ses précieux tributs, tout seigneur doit purger la faune féroce qui la défend. Chaque espèce possède ses propres caractéristiques martiales et sensibilités tactiques.
                        </p>
                    </div>
                </div>

                <?php
                    $sanglierDisk = __DIR__ . '/../public/assets/units/sanglier_sauvage.jpg';
                    $sanglierSrc = '/public/assets/units/sanglier_sauvage.jpg' . (file_exists($sanglierDisk) ? '?v=' . filemtime($sanglierDisk) : '');

                    $loupDisk = __DIR__ . '/../public/assets/units/loup_honshu.jpg';
                    $loupSrc = '/public/assets/units/loup_honshu.jpg' . (file_exists($loupDisk) ? '?v=' . filemtime($loupDisk) : '');

                    $oursDisk = __DIR__ . '/../public/assets/units/ours_hokkaido.jpg';
                    $oursSrc = '/public/assets/units/ours_hokkaido.jpg' . (file_exists($oursDisk) ? '?v=' . filemtime($oursDisk) : '');
                ?>

                <!-- CARTES DES 3 BÊTES SAUVAGES -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    
                    <!-- 1. SANGLIER ENRAGÉ DES MONTS -->
                    <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column;">
                        <div style="position: relative; width: 100%; height: 180px; overflow: hidden; cursor: pointer;"
                             onclick="openDocsLightbox('Sanglier Enragé des Monts', '<?= $sanglierSrc ?>', 'Bête Sauvage & Gardien des Sources (Tier 1)', 'Bête sauvage agressive chargeant en furie quiconque s\'approche de sa tanière. Ses défenses acérées brisent les premières lignes d\'infanterie avec violence.', '« Rien ne résiste à la charge aveugle du sanglier protecteur des sources. »')">
                            <img src="<?= $sanglierSrc ?>" alt="Sanglier Enragé des Monts" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #f59e0b; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                                🐗 Tier 1 &bull; Chargeur Brutal
                            </span>
                            <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                🔍 Agrandir
                            </span>
                        </div>
                        <div style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <h4 style="margin: 0; font-size: 1.15rem; color: var(--text-main);">Sanglier Enragé des Monts</h4>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #b45309;">(山猪)</span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                                    Bête agressive vivant près des forêts et des rizières. Il compense sa défense modérée contre les cavaliers par une excellente résistance frontale contre l'infanterie à pied.
                                </p>
                            </div>
                            <div>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.8rem; margin-bottom: 0.75rem;">
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #dc2626; font-weight: 800;">⚔️ Atq :</span> <strong>35</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #2563eb; font-weight: 800;">🛡️ Déf Inf :</span> <strong>40</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #0891b2; font-weight: 800;">🛡️ Déf Cav :</span> <strong>20</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #16a34a; font-weight: 800;">⚡ Vitesse :</span> <strong>7</strong>
                                    </div>
                                </div>
                                <div style="font-size: 0.75rem; color: #b45309; background: rgba(180, 83, 9, 0.08); padding: 6px 10px; border-radius: 6px; border-left: 3px solid #b45309;">
                                    💡 <em>Vulnérable face aux charges rapides de cavalerie et aux flèches d'archers montés.</em>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. LOUP VICIEUX DE HONSHU -->
                    <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column;">
                        <div style="position: relative; width: 100%; height: 180px; overflow: hidden; cursor: pointer;"
                             onclick="openDocsLightbox('Loup Vicieux de Honshu', '<?= $loupSrc ?>', 'Prédateur Vicieux & Chasseur en Meute (Tier 2)', 'Prédateur rusé chassant en meute coordonnée dans les forêts et collines. Rapide et létal, il fond sur les flancs des colonnes militaires.', '« Leurs yeux dorés percent la brume avant que leurs crocs ne déchirent la chair. »')">
                            <img src="<?= $loupSrc ?>" alt="Loup Vicieux de Honshu" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #38bdf8; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                                🐺 Tier 2 &bull; Traqueur Agile
                            </span>
                            <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                🔍 Agrandir
                            </span>
                        </div>
                        <div style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <h4 style="margin: 0; font-size: 1.15rem; color: var(--text-main);">Loup Vicieux de Honshu</h4>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #0284c7;">(本州狼)</span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                                    Chasseur redoutablement agile qui excelle à désarçonner les montures (haute défense contre cavalerie). Sa morsure vive cause de lourdes pertes aux troupes légères.
                                </p>
                            </div>
                            <div>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.8rem; margin-bottom: 0.75rem;">
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #dc2626; font-weight: 800;">⚔️ Atq :</span> <strong>60</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #2563eb; font-weight: 800;">🛡️ Déf Inf :</span> <strong>35</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #0891b2; font-weight: 800;">🛡️ Déf Cav :</span> <strong>55</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #16a34a; font-weight: 800;">⚡ Vitesse :</span> <strong>9</strong>
                                    </div>
                                </div>
                                <div style="font-size: 0.75rem; color: #0284c7; background: rgba(2, 132, 199, 0.08); padding: 6px 10px; border-radius: 6px; border-left: 3px solid #0284c7;">
                                    💡 <em>Privilégiez les lignes de lanciers Yari Ashigaru et Samouraïs d'élite pour contrer leur agilité.</em>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. GRAND OURS BRUN DE HOKKAIDO -->
                    <div style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column;">
                        <div style="position: relative; width: 100%; height: 180px; overflow: hidden; cursor: pointer;"
                             onclick="openDocsLightbox('Grand Ours Brun de Hokkaido', '<?= $oursSrc ?>', 'Colosse Septentrional & Terreur des Sommets (Tier 3)', 'Colosse sauvage des contrées glacées d\'Ezo, doué d\'une force brute titanesque capable de balayer un bataillon entier d\'un coup de patte.', '« Face au maître colosse d\'Ezo, même les lances des plus braves samouraïs volent en éclats. »')">
                            <img src="<?= $oursSrc ?>" alt="Grand Ours Brun de Hokkaido" style="width: 100%; height: 100%; object-fit: cover; object-position: center center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #ef4444; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                                🐻 Tier 3 &bull; Colosse Apex
                            </span>
                            <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                                🔍 Agrandir
                            </span>
                        </div>
                        <div style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <h4 style="margin: 0; font-size: 1.15rem; color: var(--text-main);">Grand Ours Brun de Hokkaido</h4>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #dc2626;">(北海道羆)</span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                                    Le titan absolu de la faune féodale. Doté d'une résistance herculéenne (130 déf. infanterie, 110 déf. cavalerie), il requiert une armée puissante ou un Héros aguerri.
                                </p>
                            </div>
                            <div>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; font-size: 0.8rem; margin-bottom: 0.75rem;">
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #dc2626; font-weight: 800;">⚔️ Atq :</span> <strong>140</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #2563eb; font-weight: 800;">🛡️ Déf Inf :</span> <strong>130</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #0891b2; font-weight: 800;">🛡️ Déf Cav :</span> <strong>110</strong>
                                    </div>
                                    <div style="background: var(--bg-ink, #ede5d5); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <span style="color: #16a34a; font-weight: 800;">⚡ Vitesse :</span> <strong>6</strong>
                                    </div>
                                </div>
                                <div style="font-size: 0.75rem; color: #dc2626; background: rgba(220, 38, 38, 0.08); padding: 6px 10px; border-radius: 6px; border-left: 3px solid #dc2626;">
                                    💡 <em>Ne jamais l'attaquer sans un Héros Samouraï doté d'une forte Puissance Martiale ou d'une armée conséquente.</em>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TABLEAU DES ARCHÉTYPES D'OASIS ET RÉPARTITION DE LA FAUNE -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.75rem 0; font-size: 1.05rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                        <span>📋</span> Répartition de la Faune par Archétype d'Oasis
                    </h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1rem 0;">
                        Lors de la génération de la carte du Shogunat, chaque type d'oasis abrite une garnison sauvage prédéterminée protégeant ses richesses :
                    </p>
                    <div style="overflow-x: auto;">
                        <table class="table" style="margin: 0; font-size: 0.85rem; width: 100%;">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.04);">
                                    <th style="padding: 8px 12px;">Type d'Oasis</th>
                                    <th style="padding: 8px 12px; text-align: center;">Bonus Économique</th>
                                    <th style="padding: 8px 12px; text-align: center;">🐗 Sangliers</th>
                                    <th style="padding: 8px 12px; text-align: center;">🐺 Loups</th>
                                    <th style="padding: 8px 12px; text-align: center;">🐻 Ours</th>
                                    <th style="padding: 8px 12px; text-align: center;">Niveau de Menace</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Grand Lac aux Eaux Vivifiantes</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-success">+50% Riz</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">30</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">20</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">8</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-danger">Élevé</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Forêt Millénaire de Cèdres Géants</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-primary">+50% Bois</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">35</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">25</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">10</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-danger">Redoutable</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Pics Escarpés aux Gisements de Fer</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-warning">+50% Pierre</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">25</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">30</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">12</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-danger">Extrême</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Source Chaude d'Onsen en Lisière</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-info">+25% Bois, +25% Riz</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">25</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">15</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">5</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-warning">Modéré</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Plateau Argileux & Vergers Sauvages</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-info">+25% Bois, +25% Pierre</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">20</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">18</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">6</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-warning">Modéré</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 12px;"><strong>Gorge Minérale & Cascades Sacrées</strong></td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-info">+25% Pierre, +25% Riz</span></td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">22</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">20</td>
                                    <td style="padding: 8px 12px; text-align: center; font-weight: 700;">7</td>
                                    <td style="padding: 8px 12px; text-align: center;"><span class="badge badge-warning">Modéré</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'quests' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 7 : DIDACTICIEL & QUÊTES FÉODALES
             ============================================================== -->
        <div class="card" id="chapitre-quetes" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #0891b2;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #0891b2; text-transform: uppercase; letter-spacing: 1px;">Chapitre 7</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🎯</span> Didacticiel & Voie des 12 Quêtes Féodales
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Pour guider les nouveaux seigneurs féodaux, le maître d'armes Katsumoto propose une série ordonnée de 12 quêtes 
                        couvrant les aspects vitaux du jeu, assorties de dotations généreuses en vivres, matériaux et points de gloire.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Progression Guidée</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #0891b2;">12 Quêtes du Daimyō</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; font-size: 0.85rem;">
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>1. Premier Arpent de Cèdre :</strong> Élever un Camp de Bûcherons au Niv. 1.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>2. Fondations de Granit :</strong> Élever une Carrière de Pierre au Niv. 1.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>3. Rizières Nourricières :</strong> Élever une Rizière Inondée au Niv. 1.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>4. Sanctuaire d'Inari :</strong> Élever un Sanctuaire Shintō au Niv. 1.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>5. Grenier Kura :</strong> Construire un Grenier à Riz dans la Cité.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>6. Entrepôt de Matériaux :</strong> Bâtir un Entrepôt de Cèdre et Pierre.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>7. Dojo & Conscription :</strong> Élever la Caserne / Dojo au Niveau 1.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>8. Premiers Soldats :</strong> Recruter au moins 2 guerriers de clan.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>9. Première Aventure du Héros :</strong> Accomplir une expédition héroïque.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>10. Muraille de Cité :</strong> Ériger les Remparts Castraux (Slot #34).
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>11. Marché Féodal :</strong> Bâtir un Marché pour échanger des denrées.
                </div>
                <div style="background: var(--bg-ink); padding: 1rem; border-radius: 8px; border-left: 3px solid #0891b2;">
                    <strong>12. Seigneur de Guerre :</strong> Rassembler une armée de 10 unités.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'map' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 8 : CARTE DU MONDE & LES 4 PROVINCES CARDINAUX
             ============================================================== -->
        <div class="card" id="chapitre-carte" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #7c3aed;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #7c3aed; text-transform: uppercase; letter-spacing: 1px;">Chapitre 8</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🗺️</span> Carte Féodale & les Quatre Provinces Cardinaux
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        L'archipel féodal d'OpenShogun s'étend sur une grille cartographique continue divisée en 4 grandes régions géographiques.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Divisions du Monde</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #7c3aed;">4 Zones Cardinaux</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <h4 style="margin:0 0 0.5rem 0; color: #3b82f6;">❄️ 1. Province du Nord (Mutsu & Dewa)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Terres boréales accidentées, forêts de résineux denses et reliefs montagneux réputés pour leurs gisements de pierre et leurs hardes d'ours sauvages.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <h4 style="margin:0 0 0.5rem 0; color: #ef4444;">🌋 2. Province du Sud (Kyūshū & Shikoku)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Climat tempéré favorable aux rizières abondantes, proximité maritime et routes commerciales animées par les caravanes de vivres.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #8b5cf6;">
                    <h4 style="margin:0 0 0.5rem 0; color: #8b5cf6;">🌅 3. Province de l'Est (Plaines du Kantō & Mikawa)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Vastes plaines fertiles idéales pour déployer les charges de cavalerie et bâtir d'immenses cités castrales fortifiées.</p>
                </div>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #10b981;">
                    <h4 style="margin:0 0 0.5rem 0; color: #10b981;">🏯 4. Province de l'Ouest (Kansai & Chūgoku)</h4>
                    <p style="margin:0; font-size:0.85rem; color: var(--text-muted);">Cœur historique de l'archipel impérial abritant les forteresses légendaires d'Azuchi et d'Osaka, berceau des traités diplomatiques.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'castles' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 9 : LES 12 DONJONS AUTHENTIQUES DU JAPON
             ============================================================== -->
        <div class="card" id="chapitre-donjons" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid var(--red-primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--red-primary); text-transform: uppercase; letter-spacing: 1px;">Chapitre 9</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🏯</span> Les 12 Donjons Authentiques du Japon (現存十二天守)
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Disséminés sur la Carte des Provinces, les 12 donjons authentiques ayant survécu depuis l'époque féodale 
                        constituent les Trésors Nationaux du jeu et les objectifs ultimes de la Bataille Finale pour le titre de Shogun.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Objectif de Victoire</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: var(--red-primary);">12 Châteaux Mythiques</div>
                </div>
            </div>

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
    <?php endif; ?>

    <?php if ($tab === 'combat' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 10 : RÈGLES DU COMBAT, MURAILLES & FORMULES
             ============================================================== -->
        <div class="card" id="chapitre-combat" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #475569;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 1px;">Chapitre 10</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>⚔️</span> Système de Combat, Murailles & Formules Martiales
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Comprendre les calculs d'affrontement pour mener vos sièges avec succès et défendre vos courtines.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Formules de Choc</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #475569;">Défense Pro-Rata</div>
                </div>
            </div>

            <div style="font-size: 0.95rem; line-height: 1.6; color: var(--text-muted);">
                <p>
                    Les affrontements militaires dans OpenShogun reposent sur la confrontation des forces offensives cumulées face aux défenses de l'adversaire (défense infanterie et défense cavalerie/méca), modulées par le bonus de fortification de la <strong>Muraille de Cité (+4% par niveau)</strong>, le <strong>Commandement du Héros Samouraï</strong> et les aptitudes doctrinales des clans.
                </p>
                <div style="background: var(--bg-ink); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); margin: 1rem 0; color: var(--text-main);">
                    <h4 style="margin: 0 0 0.5rem 0; color: var(--red-primary);">Triangle Tactique Féodal :</h4>
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        <li><strong>Piques Yari (Ashigaru) :</strong> Déciment la cavalerie rouge sous l'impact de leurs pointes acérées.</li>
                        <li><strong>Cavalerie Rouge & Archers Montés :</strong> Contournent et foudroient les tireurs et archers à découvert.</li>
                        <li><strong>Mousquets Tanegashima & Arcs :</strong> Perforent les armures lourdes et infligent des pertes sévères à distance avant le contact.</li>
                        <li><strong>Béliers & Tours de Siège :</strong> Pulvérisent les remparts adverses pour annuler le multiplicateur défensif du défenseur.</li>
                        <li><strong>Catapultes Horokubiya :</strong> Incendient et réduisent les niveaux des infrastructures urbaines ennemies.</li>
                    </ul>
                </div>

                <div style="background: rgba(22, 163, 74, 0.06); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(22, 163, 74, 0.35); margin: 1.5rem 0; color: var(--text-main);">
                    <h4 style="margin: 0 0 0.5rem 0; color: #15803d; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔰</span> Immunité Féodale des Nouveaux Joueurs (Protection de 7 Jours)
                    </h4>
                    <p style="margin-bottom: 0.75rem; font-size: 0.92rem; line-height: 1.55;">
                        Afin de permettre à chaque jeune Daimyō de bâtir ses rizières, d'élever ses remparts et de recruter ses premiers bataillons sans craindre les incursions dévastatrices de seigneurs plus aguerris ou des armées de bots, le Shogunat octroie une <strong>immunité inviolable de 7 jours</strong> dès la création du domaine castral.
                    </p>
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; line-height: 1.6;">
                        <li><strong>Inviolabilité Territoriale :</strong> Aucun autre seigneur (humain ou bot) ne peut lancer de raid de pillage, d'assaut de siège, d'occupation territoriale ni d'infiltration shinobi sur vos provinces.</li>
                        <li><strong>Convois d'Entraide Autorisés :</strong> Les convois de ressources et de vivres demeurent possibles pour permettre à vos alliés de vous soutenir.</li>
                        <li><strong>Exploration &amp; Chasse Libre :</strong> Vous pouvez librement envoyer votre Samouraï Héros accomplir des aventures féodales et pacifier les oasis sauvages pour vous emparer de leurs richesses.</li>
                        <li><strong>Rupture Martiale de l'Immunité :</strong> Si vous décidez de rompre le pacte de paix en lançant un raid, un assaut ou un espionnage contre le fief d'un <em>autre seigneur joueur</em>, votre protection de 7 jours sera <strong>immédiatement et irrévocablement levée</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'alliances' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 11 : ALLIANCES FÉODALES, PAVILLON DIPLOMATIQUE & GUERRES DE CLANS
             ============================================================== -->
        <div class="card" id="chapitre-alliances" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: 1px;">Chapitre 11</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🚩</span> Alliances Féodales, Pavillon Diplomatique & Guerres de Clans
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Dans le tourbillon de l'époque Sengoku, nul daimyō ne peut prétendre unifier le Japon en combattant seul. Les Alliances féodales permettent de coaliser vos forces militaires, de sécuriser vos frontières grâce à des pactes de non-agression, d'échanger des vivres d'urgence et de coordonner de gigantesques opérations de siège.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Capacité Maximale</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #2563eb;">Jusqu'à 60 Seigneurs</div>
                </div>
            </div>

            <!-- BÂTIMENT CLÉ : LE PAVILLON DIPLOMATIQUE -->
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <?php 
                    $embassyDisk = __DIR__ . '/../public/assets/building_embassy.jpg';
                    $embassySrc = '/public/assets/building_embassy.jpg' . (file_exists($embassyDisk) ? '?v=' . filemtime($embassyDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Pavillon Diplomatique', '<?= $embassySrc ?>', 'Bâtiment Urbain Castral (Emplacements 19-34)', 'Lieu solennel de réception des émissaires impériaux et ambassadeurs des clans rivaux. C\'est ici que se négocient les pactes, se ratifient les trêves et s\'élaborent les traités d\'alliance.', '« La plume de l\'ambassadeur prévient souvent ce que mille sabres ne peuvent réparer. »')">
                    <img src="<?= $embassySrc ?>" alt="Pavillon Diplomatique" style="width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #2563eb; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🏛️ Pavillon Diplomatique
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #2563eb; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            Condition Fondatrice
                        </span>
                        <span style="font-size: 0.8rem; color: #2563eb; font-weight: 700;">Emplacements Urbains (19 à 34)</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Fondation & Adhésion à un Clan
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Pour prendre part aux affaires diplomatiques d'OpenShogun, tout seigneur doit ériger un <strong>Pavillon Diplomatique</strong> dans sa cité castrale :
                    </p>
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; line-height: 1.6; color: var(--text-main);">
                        <li><strong>Niveau 1 :</strong> Vous pouvez recevoir et accepter des invitations pour rejoindre une alliance existante.</li>
                        <li><strong>Niveau 3 :</strong> Vous gagnez le droit impérial de <strong>fonder votre propre Clan</strong>, d'en concevoir la bannière, d'en proclamer la charte et de recruter vos premiers vassaux.</li>
                        <li><strong>Évolution (Niveaux 1 à 20) :</strong> Chaque niveau du Pavillon accroît de +3 le nombre maximal de membres pouvant prêter allégeance au clan (jusqu'à 60 daimyōs coalisés au niveau 20).</li>
                    </ul>
                </div>
            </div>

            <!-- HIÉRARCHIE & RANGS DE L'ALLIANCE -->
            <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>👑</span> Hiérarchie, Rôles & Prérogatives Féodales
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #b91c1c;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: #b91c1c; margin-bottom: 0.25rem;">Shōgun (Chef Suprême)</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Fondateur de la Dynastie</div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Possède les pleins pouvoirs impériaux : renommer le clan, changer le blason (Mon), promouvoir ou rétrograder les officiers, signer les pactes diplomatiques, déclarer la guerre ou dissoudre l'alliance.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #d97706;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: #d97706; margin-bottom: 0.25rem;">Taishō (Général d'Armée)</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Bras Droit Militaire</div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Supervise les opérations d'assaut et de défense commune. Il peut envoyer des invitations officielles, exclure les membres inactifs et ordonner des rassemblements de guerre sur les fiefs ennemis.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: #2563eb; margin-bottom: 0.25rem;">Karō (Chancelier Intendant)</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Affaires Civiles & Diplomatiques</div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Gère les requêtes d'admission, supervise les échanges de ressources et anime le forum secret de l'alliance ainsi que les relations avec les ambassades tierces.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #16a34a;">
                    <div style="font-size: 1.1rem; font-weight: 800; color: #16a34a; margin-bottom: 0.25rem;">Samouraï (Membre Confirmé)</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem;">Guerrier de la Bannière</div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Accès illimité au canal de discussion privé du clan, au forum interne, aux demandes de renforts d'urgence et aux convois solidaires d'approvisionnement sans commission de marché.
                    </p>
                </div>
            </div>

            <!-- RELATIONS DIPLOMATIQUES -->
            <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>🕊️</span> Les Trois Traités Diplomatiques
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.25); border-radius: 8px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #2563eb; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🤝</span> Pacte de Non-Agression (PNA)
                    </h4>
                    <p style="font-size: 0.88rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Signé mutuellement entre deux clans pour sceller une trêve d'honneur. Il empêche les attaques fortuites et les raids de pillage entre membres des deux alliances, facilitant une cohabitation sereine sur les frontières provinciales.
                    </p>
                </div>
                <div style="background: rgba(22,163,74,0.06); border: 1px solid rgba(22,163,74,0.25); border-radius: 8px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #16a34a; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🛡️</span> Confédération & Assistance Totale
                    </h4>
                    <p style="font-size: 0.88rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Alliance suprême fusionnant les intérêts stratégiques des deux clans. Permet de stationner des garnisons défensives dans les forteresses amies pour contrer un assaut ennemi et d'accélérer les convois d'approvisionnement.
                    </p>
                </div>
                <div style="background: rgba(220,38,38,0.06); border: 1px solid rgba(220,38,38,0.25); border-radius: 8px; padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #dc2626; display: flex; align-items: center; gap: 0.5rem;">
                        <span>⚔️</span> Déclaration de Guerre Ouverte
                    </h4>
                    <p style="font-size: 0.88rem; line-height: 1.5; margin: 0; color: var(--text-main);">
                        Officialise un état de belligérance impitoyable. Débloque le registre des affrontements de guerre (troupes terrassées, donjons assiégés, butins conquis) et motive les régiments pour écraser le rival.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'craft' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 12 : ARTISANAT DU MOULIN, BANQUETS AU TENSHU, COLONS & FAMINE
             ============================================================== -->
        <div class="card" id="chapitre-craft" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #d97706;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #d97706; text-transform: uppercase; letter-spacing: 1px;">Chapitre 12</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🍶</span> Artisanat du Moulin, Banquets au Tenshu, Colons & Famine
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        L'art de gouverner ne se limite pas à lever des sabres : il réside dans la prospérité des récoltes et le raffinement de l'intendance. Le Moulin permet de transformer le riz en Farine pure et en Saké impérial, indispensables pour célébrer les Banquets au Donjon Tenshu et accumuler les précieux Points de Culture (CP) requis pour fonder de nouvelles provinces.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Raffinement Agricole</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #d97706;">Farine & Saké Impérial</div>
                </div>
            </div>

            <!-- BÂTIMENT ARTISANAT : LE MOULIN À GRAINS -->
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <?php 
                    $millDisk = __DIR__ . '/../public/assets/building_grain_mill.jpg';
                    $millSrc = '/public/assets/building_grain_mill.jpg' . (file_exists($millDisk) ? '?v=' . filemtime($millDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Moulin à Grains & Raffinage', '<?= $millSrc ?>', 'Atelier d\'Artisanat Féodal (Emplacement Urbain)', 'Actionné par le courant des cours d\'eau ou la force du vent, le Moulin broie le riz brut en fine farine et abrite les cuves de fermentation pour brasser le précieux Saké des dieux.', '« Chaque grain moulu sous la meule prépare la grandeur des banquets futurs. »')">
                    <img src="<?= $millSrc ?>" alt="Moulin à Grains" style="width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #d97706; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🌾 Moulin d'Artisanat
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #d97706; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            Recettes de Raffinage
                        </span>
                        <span style="font-size: 0.8rem; color: #d97706; font-weight: 700;">Accessible dès le Moulin Niv. 1</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        La Transformation des Grains de Riz
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Le Moulin offre deux ateliers artisanaux permettant de valoriser vos surplus agricoles :
                    </p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                        <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border-color);">
                            <div style="font-weight: 800; color: #b45309; font-size: 0.9rem;">🌾 Farine de Riz (米粉 - Komeko)</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                Obtenue par broyage mécanique. Aliment de base pour les festivités provinciales et les réserves de longue conservation.
                            </div>
                        </div>
                        <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border-color);">
                            <div style="font-weight: 800; color: #b91c1c; font-size: 0.9rem;">🍶 Saké Impérial (日本酒 - Nihonshu)</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                Boisson sacrée issue d'une fermentation patiente. Offerte aux sanctuaires et bue lors des grands banquets au Tenshu.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LES BANQUETS DU TENSHU & POINTS DE CULTURE -->
            <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>🍱</span> Banquets au Tenshu & Rayonnement Culturel (CP)
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.6; color: var(--text-main); margin-bottom: 1rem;">
                Pour fonder ou conquérir de nouveaux villages sur la Carte des Provinces, votre domaine doit accumuler des <strong>Points de Culture (CP)</strong>. Si chaque bâtiment érigé en génère passivement chaque jour, organiser des <strong>Banquets</strong> au sommet du Donjon Tenshu accélère considérablement cette marche vers l'hégémonie :
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: #b45309; font-size: 1.1rem;">🏮 Petit Banquet Populaire</h4>
                        <span style="font-size: 0.75rem; font-weight: 700; background: rgba(245,158,11,0.15); color: #b45309; padding: 2px 8px; border-radius: 4px;">Tenshu Niv. 1+</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.75rem 0;">
                        Festin festif réunissant les artisans et paysans du domaine. Augmente le contentement populaire et confère un afflux immédiat de points culturels.
                    </p>
                    <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.85rem; line-height: 1.6; color: var(--text-main);">
                        <li><strong>Gain :</strong> +250 Points de Culture immédiats.</li>
                        <li><strong>Bonus :</strong> +10% de production de ressources sur le domaine pendant 24h.</li>
                        <li><strong>Coût :</strong> Réserves de Bois, Pierre et Riz.</li>
                    </ul>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #dc2626;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 style="margin: 0; color: #b91c1c; font-size: 1.1rem;">🏯 Grand Banquet Impérial</h4>
                        <span style="font-size: 0.75rem; font-weight: 700; background: rgba(220,38,38,0.15); color: #b91c1c; padding: 2px 8px; border-radius: 4px;">Tenshu Niv. 10+</span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.75rem 0;">
                        Réception fastueuse accueillant les ambassadeurs de Kyoto, princes de sang et généraux de haut rang. Requiert les produits raffinés du Moulin.
                    </p>
                    <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.85rem; line-height: 1.6; color: var(--text-main);">
                        <li><strong>Gain :</strong> Jusqu'à +2 000 Points de Culture instantanés (égal à la production journalière de la cité).</li>
                        <li><strong>Ingrédients :</strong> Saké Impérial et Farine de Riz du Moulin.</li>
                        <li><strong>Effet Stratégique :</strong> Débloque en un éclair le quota requis pour recruter vos Colons.</li>
                    </ul>
                </div>
            </div>

            <!-- COLONISATION & RISQUE DE FAMINE -->
            <div style="background: rgba(220, 38, 38, 0.05); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(220, 38, 38, 0.35); margin-top: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #b91c1c; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span> Entretien des Armées (Upkeep) & Risque de Famine Déserteuse
                </h4>
                <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-main); margin-bottom: 0.75rem;">
                    Dans OpenShogun, chaque guerrier du Dojo, cavalier et équipage de siège consomme une part de grain chaque heure pour sa subsistance (<strong>1 sac de riz / heure par soldat</strong>) :
                </p>
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.88rem; line-height: 1.6; color: var(--text-main);">
                    <li><strong>Production Vivrière Nette :</strong> Visible dans la barre de ressources sous la mention <code>Riz : +X/h</code>. Elle est égale à la production totale de vos rizières moins la ration de vos troupes.</li>
                    <li><strong>Déficit Vivrier :</strong> Si votre armée est plus nombreuse que la production de vos champs, la production devient négative (ex: <code>-150/h</code>). Vos réserves en Silo se vident alors progressivement pour nourrir vos hommes.</li>
                    <li><strong>Famine Déserteuse :</strong> Si les réserves de riz tombent à <strong>zéro absolu</strong> alors que le solde est négatif, la famine éclate dans vos casernes. Vos troupes périssent ou désertent alors automatiquement, une à une, jusqu'à ce que la consommation s'équilibre exactement avec la récolte de vos rizières !</li>
                    <li><strong>Conseil du Chancelier :</strong> Veillez toujours à développer vos rizières (parcelles 1 à 18) avant de lancer des levées en masse au Dojo.</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'cages' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 13 : CAGES DE CAPTURE & DOMESTICATION DE LA FAUNE SAUVAGE
             ============================================================== -->
        <div class="card" id="chapitre-cages" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #15803d;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: 1px;">Chapitre 13</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>🦊</span> Cages de Capture & Domestication de la Faune Sauvage
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Directement inspirées des tactiques ancestrales de dressage et des chasses en forêt primaire, les <strong>Cages de Capture en fer forgé</strong> permettent à votre Héros Samouraï de capturer vivantes les créatures sauvages qui peuplent les Oasis indépendantes sans verser une goutte de sang, pour en faire des sentinelles féroces et gratuites sur vos remparts.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Coût d'Entretien</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #15803d;">0 Riz (100% Gratuit)</div>
                </div>
            </div>

            <!-- ILLUSTRATION DE L'ITEM CAGES DE CAPTURE -->
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <?php 
                    $cagesDisk = __DIR__ . '/../public/assets/items/cages_capture.jpeg';
                    $cagesSrc = '/public/assets/items/cages_capture.jpeg' . (file_exists($cagesDisk) ? '?v=' . filemtime($cagesDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Cages de Capture de Faune Sauvage', '<?= $cagesSrc ?>', 'Équipement Consommable du Héros Samouraï', 'Forgées en barreaux d\'acier trempé munis de déclencheurs à contrepoids, ces cages permettent de capturer vivants ours géants, loups des forêts et sangliers lors des assauts sur les oasis.', '« Pourquoi abattre une bête féroce quand elle peut garder vos remparts jusqu\'à son dernier souffle ? »')">
                    <img src="<?= $cagesSrc ?>" alt="Cages de Capture" style="width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #15803d; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        ⛓️ Objet d'Inventaire
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #15803d; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            Mécanique de Capture
                        </span>
                        <span style="font-size: 0.8rem; color: #15803d; font-weight: 700;">1 Cage Consommée = 1 Bête Domestiquée</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Comment Capturer la Faune Sauvage ?
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        La capture d'animaux sauvages obéit à un protocole martial pacifique et stratégique :
                    </p>
                    <ol style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; line-height: 1.6; color: var(--text-main);">
                        <li><strong>Équiper les Cages :</strong> Placez les cages de fer dans la sacoche d'équipement de votre Héros Samouraï (depuis la page <em>Héros &bull; Équipement</em>).</li>
                        <li><strong>Lancer l'Attaque sur l'Oasis :</strong> Envoyez votre Héros assaillir une Oasis sauvage occupée par des prédateurs.</li>
                        <li><strong>Capture Instantanée Sans Dégâts :</strong> Dès l'arrivée, les bêtes sont neutralisées dans les cages <em>sans qu'aucun combat n'ait lieu</em> pour les créatures capturées ! Ni le Héros ni les bêtes ne subissent de blessures.</li>
                        <li><strong>Retour Triomphal :</strong> Les bêtes capturées accompagnent immédiatement le Héros à son retour au château et s'installent dans vos coursives défensives.</li>
                    </ol>
                </div>
            </div>

            <!-- LE BESTIAIRE FÉODAL EN DÉFENSE -->
            <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>🐺</span> Le Bestiaire Protecteur : Des Défenseurs d'Élite Gratuits
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.6; color: var(--text-main); margin-bottom: 1rem;">
                L'atout suprême de la faune sauvage réside dans sa totale autonomie vivrière : <strong>les bêtes ne consomment AUCUN sac de riz d'entretien horaire (0 Upkeep)</strong> ! Elles constituent un bouclier idéal contre les raids de cavalerie et les pillages nocturnes.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #4b5563;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <span style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">🐺 Loup des Bois</span>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(75,85,99,0.15); padding: 2px 6px; border-radius: 4px;">Anti-Infanterie</span>
                    </div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0 0 0.5rem 0; color: var(--text-muted);">
                        Chasse en meute serrée. Ses crocs déchiquètent les éclaireurs ennemis et perturbent les conscrits peu armés.
                    </p>
                    <div style="font-size: 0.8rem; font-weight: 700; color: #2563eb;">🛡️ Déf. Infanterie : 30 &bull; Cavalerie : 15</div>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #b45309;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <span style="font-size: 1.1rem; font-weight: 800; color: #b45309;">🐗 Sanglier Cuirassé</span>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(180,83,9,0.15); padding: 2px 6px; border-radius: 4px;">Anti-Cavalerie</span>
                    </div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0 0 0.5rem 0; color: var(--text-muted);">
                        Doté d'une peau épaisse et de défenses effilées. Sa charge brutale brise l'élan des chevaux et cavaliers adverses.
                    </p>
                    <div style="font-size: 0.8rem; font-weight: 700; color: #2563eb;">🛡️ Déf. Infanterie : 35 &bull; Cavalerie : 60</div>
                </div>

                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #b91c1c;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                        <span style="font-size: 1.1rem; font-weight: 800; color: #b91c1c;">🐻 Ours Brun Géant</span>
                        <span style="font-size: 0.75rem; font-weight: 800; background: rgba(185,28,28,0.15); padding: 2px 6px; border-radius: 4px;">Colosse Suprême</span>
                    </div>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0 0 0.5rem 0; color: var(--text-muted);">
                        Titan des cimes du mont Fuji. Encaisse des volées entières de flèches et fauche plusieurs assaillants d'un revers de patte.
                    </p>
                    <div style="font-size: 0.8rem; font-weight: 700; color: #2563eb;">🛡️ Déf. Infanterie : 140 &bull; Cavalerie : 200</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'social' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 14 : COMMUNICATION FÉODALE : CHAT EN TEMPS RÉEL & FORUM DU ROYAUME
             ============================================================== -->
        <div class="card" id="chapitre-social" style="margin-bottom: 2rem; background: var(--bg-surface, #fdfbf7); padding: 2rem; border: 1px solid var(--border-color); border-radius: 12px; border-left: 6px solid #7c3aed;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #7c3aed; text-transform: uppercase; letter-spacing: 1px;">Chapitre 14</span>
                    <h2 style="margin: 0.25rem 0 0.5rem 0; font-size: 1.8rem; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>💬</span> Communication Féodale : Chat en Temps Réel & Forum du Royaume
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; max-width: 900px; line-height: 1.6;">
                        Une communauté active et respectueuse est le pilier d'un empire durable. OpenShogun intègre un dispositif complet de communication en direct (chat multi-canaux avec widget persistant) ainsi qu'un grand forum du shogunat pour débattre des grandes affaires de l'archipel.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Canaux Synchrones</div>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #7c3aed;">Général &bull; Alliance &bull; Troc</div>
                </div>
            </div>

            <!-- CHAT EN TEMPS RÉEL -->
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <?php 
                    $teahouseDisk = __DIR__ . '/../public/assets/building_teahouse.jpg';
                    $teahouseSrc = '/public/assets/building_teahouse.jpg' . (file_exists($teahouseDisk) ? '?v=' . filemtime($teahouseDisk) : '');
                ?>
                <div style="position: relative; width: 280px; max-width: 100%; height: 175px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); cursor: pointer; flex-shrink: 0;"
                     onclick="openDocsLightbox('Maison de Thé & Salons de Discussion', '<?= $teahouseSrc ?>', 'Lieu de Parole & de Diplomatie Féodale', 'Autour d\'un bol de thé matcha ou d\'une coupe de saké, les seigneurs se rencontrent pour échanger nouvelles du front, négocier des trêves ou conter leurs exploits martiaux.', '« Dans le silence du salon de thé, les plus grandes alliances prennent naissance. »')">
                    <img src="<?= $teahouseSrc ?>" alt="Maison de Thé" style="width: 100%; height: 100%; object-fit: cover; object-position: center; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span style="position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.85); color: #7c3aed; font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px;">
                        🍵 Salon de Discussion
                    </span>
                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;">
                        🔍 Agrandir
                    </span>
                </div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span style="background: #7c3aed; color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.5px;">
                            Chat Instantané Multi-Canaux
                        </span>
                        <span style="font-size: 0.8rem; color: #7c3aed; font-weight: 700;">Widget Dépliable en Bas à Droite</span>
                    </div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; color: var(--text-main);">
                        Échangez en Direct avec Tout l'Archipel
                    </h3>
                    <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); margin: 0 0 0.75rem 0;">
                        Le chat d'OpenShogun offre trois espaces distincts accessibles depuis le widget persistant ou via la page dédiée <code>?page=chat</code> :
                    </p>
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; line-height: 1.6; color: var(--text-main);">
                        <li><strong>Canal Général (Royaume) :</strong> Tribune publique où tous les daimyōs discutent, célèbrent leurs victoires et commentent les batailles.</li>
                        <li><strong>Canal Alliance :</strong> Salon crypté réservé exclusivement aux membres de votre clan pour coordonner les assauts millimétrés et les renforts défensifs.</li>
                        <li><strong>Canal Commerce & Diplomatie :</strong> Bourse d'annonces pour échanger des cargaisons de ressources ou solliciter des traités diplomatiques.</li>
                    </ul>
                </div>
            </div>

            <!-- FORUM DU SHOGUNAT -->
            <h3 style="margin: 1.5rem 0 1rem 0; font-size: 1.25rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>📜</span> Le Forum Officiel du Shogunat (<code>?page=forum</code>)
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #b91c1c;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #b91c1c; font-size: 1.05rem;">📢 Annonces & Édits Impériaux</h4>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-muted);">
                        Communications solennelles de l'administration du jeu : annonces d'événements saisonniers, notes de mise à jour, équilibrages et tournois martiaux.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #2563eb;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #2563eb; font-size: 1.05rem;">⚔️ Chroniques de Guerre & Rapports</h4>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-muted);">
                        Publication des rapports de bataille historiques, déclarations d'hostilités solennelles entre clans et récits d'assauts mémorables.
                    </p>
                </div>
                <div style="background: var(--bg-ink, #ede5d5); padding: 1.25rem; border-radius: 8px; border-left: 4px solid #16a34a;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #16a34a; font-size: 1.05rem;">🍵 La Maison de Thé (Taverne RP)</h4>
                    <p style="font-size: 0.85rem; line-height: 1.5; margin: 0; color: var(--text-muted);">
                        Espace de convivialité, récits de fiction à l'époque Sengoku, poèmes haïkus et échanges libres dans le respect de l'honneur féodal.
                    </p>
                </div>
            </div>

            <div style="background: rgba(124, 58, 237, 0.06); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(124, 58, 237, 0.3); margin-top: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; color: #6d28d9; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🛡️</span> Charte de Conduite & Code du Bushidō
                </h4>
                <p style="margin: 0; font-size: 0.9rem; line-height: 1.6; color: var(--text-main);">
                    Sur les salons de chat comme sur les coursives du forum, chaque daimyō s'engage à respecter la courtoisie féodale. Les insultes, le harcèlement et les comportements déloyaux sont réprimandés par le Grand Chambellan sous peine de bannissement des canaux publics.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'seal' || $tab === 'all'): ?>
        <!-- ==============================================================
             CHAPITRE 15 : LE SCEAU IMPÉRIAL & LE GRAND TABLEAU DE BORD DE L'EMPIRE
             ============================================================== -->
        <div class="card" style="margin-bottom: 2rem; border-top: 4px solid #f59e0b; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: var(--bg-surface, #fdfbf7);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 1px;">Privilège du Shōgun &bull; Sengoku Plus</span>
                    <h2 style="margin: 0.25rem 0 0 0; font-size: 1.75rem; color: var(--text-main); font-weight: 900; display: flex; align-items: center; gap: 0.6rem;">
                        <span>👑</span> Chapitre 15 : Le Sceau Impérial &amp; l'Empire
                    </h2>
                </div>
                <span class="badge" style="background: rgba(245,158,11,0.15); color: #b45309; border: 1px solid rgba(245,158,11,0.4); font-size: 0.85rem; padding: 0.4rem 0.8rem; border-radius: 6px;">
                    Système Travian Plus &bull; 8 Privilèges
                </span>
            </div>

            <p style="font-size: 0.95rem; line-height: 1.7; color: var(--text-main); margin-bottom: 1.5rem;">
                Le <strong>Sceau Impérial</strong> (Privilège du Shōgun) est l'équivalent féodal du système <em>Travian Plus</em>. Conçu pour apporter un confort de gestion absolu sans compromettre l'équité martiale, il met à la disposition des souverains les plus ambitieux des outils d'intendance de haut rang. OpenShogun garantit un accès <strong>Free-to-Play</strong> : chaque daimyō reçoit 100 Koban à l'investiture, gagne 100 Koban à chaque médaille remportée et peut réclamer un <strong>tribut quotidien gratuit (+5 Koban)</strong> chaque jour de connexion !
            </p>

            <!-- GRILLE DES 8 PRIVILÈGES -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
                
                <!-- 1. ARCHITECTE DE COUR -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #3b82f6;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">🔨</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #1d4ed8; font-weight: 800;">1. Architecte de Cour (File Étendue)</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Planifiez vos chantiers castraux jour et nuit. Les maîtres bâtisseurs enchaînent jusqu'à <strong>4 constructions</strong> (jusqu'à 2 parcelles agricoles et 2 infrastructures urbaines pour Oda, et 3 chantiers consécutifs pour les autres clans).
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #1e40af;">
                        ⚡ Enchaînement automatique : le 2e chantier démarre dès la pose de la dernière pierre du premier !
                    </div>
                </div>

                <!-- 2. FILE DE RAFFINAGE SAKAGURA (NOUVEAU) -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #166534;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">🍶</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #166534; font-weight: 800;">2. File de Raffinage Meunerie (Sakagura)</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Enchaînez jusqu'à <strong>4 commandes de raffinage</strong> dans vos ateliers (Mouture de Farine de Riz 🍚 et Brassage de Saké 🍶). Vos meules et cuves tournent sans interruption même pendant votre sommeil !
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #14532d;">
                        🍚 Vivres garantis pour éviter la famine de vos troupes d'élite et alimenter vos banquets.
                    </div>
                </div>

                <!-- 3. ROUTES COMMERCIALES AUTOMATISÉES (NOUVEAU) -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #0891b2;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">🛣️</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #0891b2; font-weight: 800;">3. Routes Commerciales Automatisées</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Définissez des routes logistiques permanentes entre vos fiefs avec fréquence au choix (toutes les 1h, 2h, 4h, 8h, 12h ou 24h). Vos chariots de ravitaillement partent automatiquement sans clic manuel !
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #0e7490;">
                        📦 Ravitaillement continu de vos nouveaux fiefs en Bois, Pierre et Riz.
                    </div>
                </div>

                <!-- 4. TABLEAU DE BORD EMPIRE -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #f59e0b;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">👑</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #b45309; font-weight: 800;">4. Grand Tableau de Bord de l'Empire</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Accessible via la page <code>?page=empire</code> ou la barre de navigation. Consolidation complète de tous vos villages : jauges de stockage en temps réel, productions nettes horaires, totaux impériaux et surveillance du péril de famine.
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #92400e;">
                        🌾 Vue globale de tous les chantiers et banquets de célébration de l'Archipel.
                    </div>
                </div>

                <!-- 5. CARNET DE RAIDS -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #10b981;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">📜</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #047857; font-weight: 800;">5. Carnet de Raids (Farm List)</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Créez vos listes de cibles régulières (oasis sauvages, provinces inactives) et assignez à chacune une composition d'armée dédiée. Un simple clic sur le bouton vert <strong>« ⚡ Lancer la Tournée »</strong> déploie instantanément toutes vos vagues.
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #065f46;">
                        🏇 Gain de temps phénoménal : plus besoin de recomposer vos régiments manuellement.
                    </div>
                </div>

                <!-- 6. INTENDANT DU MARCHÉ -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #8b5cf6;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">⚖️</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #6d28d9; font-weight: 800;">6. Intendant du Marché (Troc 1:1:1)</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Vos greniers débordent de Bois mais manquent cruellement de Pierre ou de Riz pour ériger votre Donjon ? L'Intendant rééquilibre immédiatement vos réserves au ratio d'or <strong>1:1:1</strong> sans aucune taxe de perte, pour un tribut symbolique de 3 Koban.
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #5b21b6;">
                        🎯 Curseur interactif et bouton « Répartir équitablement en tiers » en 1 clic.
                    </div>
                </div>

                <!-- 7. ORDRE DE REPLI TACTIQUE -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #ef4444;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">⛩️</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #b91c1c; font-weight: 800;">7. Ordre de Repli Tactique (Évasion)</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Activez l'ordre de repli tactique sur vos châteaux pour ordonner à votre garnison et à votre Samouraï Héros d'évacuer discrètement dans les sous-bois lors d'une attaque ennemie. Vos troupes évitent le massacre nocturne et demeurent indemnes.
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #991b1b;">
                        🛡️ L'assaillant ne combat personne et ne pille que les ressources non protégées par vos Cachettes.
                    </div>
                </div>

                <!-- 8. MONNAIE FÉODALE (KOBAN) -->
                <div style="background: var(--bg-ink, #ede5d5); border: 1px solid var(--border-color); border-radius: 10px; padding: 1.25rem; border-left: 4px solid #d97706;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">🪙</span>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #b45309; font-weight: 800;">8. Trésor en Koban &amp; Économie Équitable</h3>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.55; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                        Le Koban est la pièce d'or ovale officielle du Shogunat. Les formules d'investiture du Sceau Impérial sont accessibles à tous les seigneurs : <strong>7 jours (200 Koban)</strong>, <strong>14 jours (360 Koban, -10%)</strong> ou <strong>30 jours (600 Koban, -25%)</strong>.
                    </p>
                    <div style="font-size: 0.78rem; font-weight: 700; color: #78350f;">
                        🎁 100 Koban offerts à la création &bull; +5 Koban offerts chaque jour de fidélité &bull; +100 Koban par médaille !
                    </div>
                </div>

            </div>

            <!-- TABLEAU COMPARATIF : SANS SCEAU VS AVEC LE SCEAU IMPÉRIAL -->
            <h3 style="font-size: 1.2rem; color: var(--text-main); font-weight: 800; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚖️</span> Tableau Comparatif : Régime Féodal Ordinaire vs Sceau Impérial
            </h3>
            <div style="overflow-x: auto;">
                <table class="docs-table" style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                    <thead>
                        <tr style="background: var(--bg-ink, #ede5d5); text-align: left;">
                            <th style="padding: 0.75rem 1rem; border: 1px solid var(--border-color);">Fonctionnalité Martiale</th>
                            <th style="padding: 0.75rem 1rem; border: 1px solid var(--border-color);">Daimyō Ordinaire</th>
                            <th style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.15); color: #b45309; font-weight: 800;">Sous le Sceau Impérial 👑</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">File de Construction</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">1 parcelle + 1 bâtiment (Oda) ou 1 seul chantier</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Jusqu'à 4 chantiers enchaînés (2 champs + 2 cités) ou 3 chantiers continus</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">File Meunerie &amp; Brasserie</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">1 commande de raffinage à la fois (bloquante)</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">File étendue : jusqu'à 4 commandes enchaînées automatiquement (Farine 🍚 &amp; Saké 🍶)</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">Routes Commerciales</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">Envoi manuel de chaque convoi via la place de rassemblement</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Convois automatiques programmés (toutes les 1h, 2h, 4h, 8h, 12h, 24h)</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">Tableau de Bord Empire</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">Consultation individuelle village par village</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Grand Tableau consolidé multi-fiefs, alertes famine et totaux</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">Carnet de Raids (Farm List)</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">Saisie manuelle des coordonnées et troupes</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Listes personnalisées &amp; Déploiement groupé en 1 Clic</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">Troc de Ressources</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">Échange marchand classique via caravanes</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Intendant du Marché NPC instantané au ratio 1:1:1</td>
                        </tr>
                        <tr>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); font-weight: 700;">Protection de Nuit</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); color: var(--text-muted);">Combat obligatoire de toute la garnison présente</td>
                            <td style="padding: 0.75rem 1rem; border: 1px solid var(--border-color); background: rgba(245,158,11,0.05); font-weight: 700; color: #15803d;">Ordre de Repli Tactique activable par fief (0 perte de troupe)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<!-- MODALE LIGHTBOX EN HAUTE DÉFINITION POUR LES ILLUSTRATIONS DU CODEX -->
<div class="modal-overlay" id="docsLightboxModal" style="display: none; position: fixed; inset: 0; background: rgba(5,7,15,0.9); backdrop-filter: blur(10px); z-index: 9999; align-items: center; justify-content: center;" onclick="closeDocsLightbox()">
    <div class="modal-card" style="max-width: 850px; width: 92%; max-height: 92vh; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
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
