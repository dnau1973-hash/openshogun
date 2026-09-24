<?php
/**
 * Moteur des 12 Donjons Authentiques du Japon (現存十二天守)
 * Sanctuaires historiques et enjeux stratégiques de la Bataille Finale du Shogunat.
 */
require_once __DIR__ . '/Database.php';

class CastleEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureTableAndSeed();
    }

    /**
     * Initialise la table et injecte les 12 châteaux authentiques si la table est vide
     */
    public function ensureTableAndSeed(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS authentic_castles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                japanese_name VARCHAR(100) NOT NULL,
                kanji VARCHAR(50) NOT NULL,
                province VARCHAR(100) NOT NULL,
                historical_builder VARCHAR(150) NOT NULL,
                construction_year VARCHAR(50) NOT NULL,
                classification VARCHAR(100) NOT NULL DEFAULT 'Trésor National du Japon',
                icon VARCHAR(10) NOT NULL DEFAULT '🏯',
                image VARCHAR(255) NULL,
                short_desc TEXT NOT NULL,
                full_description LONGTEXT NOT NULL,
                architectural_features TEXT NOT NULL,
                final_battle_lore LONGTEXT NOT NULL,
                relic_bonus TEXT NOT NULL,
                default_x INT NOT NULL,
                default_y INT NOT NULL,
                coord_x INT NULL,
                coord_y INT NULL,
                is_spawned TINYINT(1) NOT NULL DEFAULT 0,
                planet_id INT NULL,
                defense_power INT NOT NULL DEFAULT 25000,
                controlling_user_id INT NULL,
                controlling_faction VARCHAR(50) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_coords (coord_x, coord_y),
                INDEX idx_spawned (is_spawned)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $count = (int)$this->db->query("SELECT COUNT(*) FROM authentic_castles")->fetchColumn();
        if ($count === 0) {
            $this->seedInitialCastles();
        }
    }

    /**
     * Définitions complètes des 12 châteaux authentiques
     */
    private function seedInitialCastles(): void {
        $castles = [
            [
                'code' => 'bitchu_matsuyama',
                'name' => 'Château de Bitchū Matsuyama',
                'japanese_name' => 'Bitchū Matsuyama-jō',
                'kanji' => '備中松山城',
                'province' => 'Province de Bitchū (Takahashi, Préfecture d\'Okayama)',
                'historical_builder' => 'Akiba Shigenobu (1240) / Mizunoya Katsutaka (1683)',
                'construction_year' => '1683',
                'classification' => 'Bien Culturel Important & Plus Haut Donjon de Montagne',
                'icon' => '🏯',
                'image' => 'bitchu_matsuyama.jpg',
                'short_desc' => 'Unique donjon de montagne (yamajiro) d\'époque Edo encore préservé au Japon, perché à 430 mètres d\'altitude au-dessus d\'une mer de nuages.',
                'full_description' => 'Érigé sur le sommet escarpé du mont Gagyū, le château de Bitchū Matsuyama est un chef-d\'œuvre d\'ingénierie médiévale tirant parti des falaises abruptes pour créer une forteresse réputée imprenable. Au petit matin d\'automne, les brumes matinales submergent la vallée de Takahashi, laissant émerger le donjon blanc au-dessus des nuages, lui conférant le surnom légendaire de « Château dans le Ciel ». Sa structure à deux niveaux en bois de cyprès repose directement sur un affleurement de roche mère naturelle.',
                'architectural_features' => '• Donjon de montagne (yamajiro) le plus élevé du Japon (430 m).\n• Murailles de pierre sèches épousant les failles granitiques de la falaise.\n• Tours d\'angle Ote-mon et tourelle à deux niveaux San-no-maru parfaitement conservées.',
                'final_battle_lore' => 'Position zénithale dominante. Perché au-dessus des brumes, Bitchū Matsuyama offre une visibilité totale sur tous les mouvements militaires ennemis dans le centre de l\'archipel. En cas d\'assaut massif lors de la Bataille Finale, son relief escarpé divise par deux l\'efficacité des troupes d\'assaut adverses.',
                'relic_bonus' => 'Sceau du Sanctuaire Céleste : Confère +15% de résistance défensive à toutes les garnisons du clan.',
                'default_x' => -18,
                'default_y' => -18,
            ],
            [
                'code' => 'hikone',
                'name' => 'Château de Hikone',
                'japanese_name' => 'Hikone-jō',
                'kanji' => '彦根城',
                'province' => 'Province d\'Ōmi (Préfecture de Shiga)',
                'historical_builder' => 'Ii Naomasa & Ii Naotsugu (Clan Ii)',
                'construction_year' => '1603 - 1622',
                'classification' => 'Trésor National du Japon',
                'icon' => '🏯',
                'image' => 'hikone.jpg',
                'short_desc' => 'Forteresse légendaire des Diables Rouges (Akazonae) du clan Ii, surveillant le lac Biwa et verrouillant l\'accès à Kyōto.',
                'full_description' => 'Édifié après la bataille décisive de Sekigahara par le général Ii Naomasa sur les rives stratégiques du lac Biwa, le château de Hikone a été conçu pour prévenir toute rébellion contre le Shogunat Tokugawa. Son donjon à trois étages intègre des éléments recyclés de châteaux antérieurs dont celui d\'Otsu et de Nagahama. Doté d\'une richesse ornementale exceptionnelle combinant toits en pignon courbé (karahafu) et frontons triangulaires (irimoya-hafu), il est resté le fief inexpugnable des seigneurs héréditaires Ii pendant plus de 250 ans.',
                'architectural_features' => '• Donjon à trois niveaux et trois étages avec toitures étagées variées.\n• Cloche d\'alarme de garde Tsugibue veillant sur le détroit du lac Biwa.\n• Écuries seigneuriales d\'origine et pont à bascule défensif Tenbin-yagura.',
                'final_battle_lore' => 'Verrou de la route de la Capitale. Contrôler Hikone permet de bloquer l\'avancée des armées hostiles vers Kyōto et la plaine du Kansai. Lors de la Grande Bataille Finale, sa possession octroie une mobilité accrue sur les voies de ravitaillement centrales.',
                'relic_bonus' => 'Étendard des Diables Rouges : Confère +10% de puissance d\'assaut et +15% de vitesse de marche à toute la cavalerie.',
                'default_x' => 18,
                'default_y' => -18,
            ],
            [
                'code' => 'himeji',
                'name' => 'Château de Himeji',
                'japanese_name' => 'Himeji-jō',
                'kanji' => '姫路城',
                'province' => 'Province de Harima (Himeji, Préfecture de Hyōgo)',
                'historical_builder' => 'Akamatsu Sadanori / Toyotomi Hideyoshi / Ikeda Terumasa',
                'construction_year' => '1601 - 1609',
                'classification' => 'Trésor National du Japon & Patrimoine Mondial UNESCO',
                'icon' => '🏯',
                'image' => 'himeji.jpg',
                'short_desc' => 'Le « Héron Blanc » (Shirasagi-jō), plus grand, plus majestueux et plus sophistiqué complexe fortifié féodal d\'origine de tout le Japon.',
                'full_description' => 'Joyau absolu de l\'architecture militaire japonaise, le château de Himeji fascine par son éclatante blancheur de chaux ignifugée rappelant les ailes déployées d\'un héron blanc. Bâti par Ikeda Terumasa au faîte de la puissance féodale, le complexe se compose d\'un donjon principal à 5 niveaux et 7 étages relié par des coursives fortifiées à 3 donjons secondaires (Tenshu complexe de type renritsu-shiki). Son enceinte concentrique compte plus de 80 portes massives, des cours aveugles et des chemins déroutants conçus pour piéger impitoyablement tout assaillant dans des zones de tir croisé meurtrières.',
                'architectural_features' => '• Donjon principal majestueux à 6 étages et sous-sol culminant à 46 mètres.\n• Système complexe de 84 portes fortifiées et meurtrières triangulaires, carrées et rondes (sama).\n• Enduit de plâtre blanc résistant au feu recouvrant l\'intégralité des façades et des tuiles.',
                'final_battle_lore' => 'Le Siège Suprême du Shogunat. Himeji est l\'enjeu ultime : la coalition qui capture et défend le Héron Blanc gagne le titre d\'Hégémonie Impériale et le droit de proclamer l\'avènement de la nouvelle ère shogunale.',
                'relic_bonus' => 'Plumes du Héron Blanc : Augmente de +25% les défenses de muraille et octroie un prestige immense (+250 points d\'Honneur/semaine).',
                'default_x' => -18,
                'default_y' => 18,
            ],
            [
                'code' => 'hirosaki',
                'name' => 'Château de Hirosaki',
                'japanese_name' => 'Hirosaki-jō',
                'kanji' => '弘前城',
                'province' => 'Province de Mutsu (Hirosaki, Préfecture d\'Aomori)',
                'historical_builder' => 'Clan Tsugaru (Tamenobu & Nobuhira)',
                'construction_year' => '1611 (Reconstruit en 1810)',
                'classification' => 'Bien Culturel Important & Donjon Boréal',
                'icon' => '🏯',
                'image' => 'hirosaki.jpg',
                'short_desc' => 'Donjon authentique le plus septentrional de l\'archipel, gardien farouche des neiges éternelles et des frontières boréales de Mutsu.',
                'full_description' => 'Bâti dans le grand Nord sauvage par le clan Tsugaru pour pacifier et unifier la vaste province de Mutsu, le château de Hirosaki s\'élève au cœur d\'une triple enceinte de douves alimentées par les rivières de fonte glaciaire du mont Iwaki. Après la destruction par la foudre de son donjon d\'origine à 5 étages, la tourelle d\'angle à 3 niveaux a été transformée en donjon principal en 1810. La forteresse est renommée mondialement pour ses milliers de cerisiers entourant ses douves gelées en hiver.',
                'architectural_features' => '• Seul donjon préservé de l\'époque Edo dans toute la région de Tōhoku.\n• Portes monumentales en cèdre du Nord et douves concentriques triples.\n• Structure compacte renforcée contre les froids polaires et les congères.',
                'final_battle_lore' => 'Bastion des Terres du Nord. Il permet de lever les légions montagnardes de Mutsu et de prendre en tenaille depuis l\'arrière toute armée engagée dans le centre de l\'archipel.',
                'relic_bonus' => 'Givre de Mutsu : +15% de vitesse de déplacement des troupes et résistance accrue aux conditions hivernales.',
                'default_x' => 12,
                'default_y' => 31,
            ],
            [
                'code' => 'inuyama',
                'name' => 'Château d\'Inuyama',
                'japanese_name' => 'Inuyama-jō',
                'kanji' => '犬山城',
                'province' => 'Province d\'Owari (Préfecture d\'Aichi)',
                'historical_builder' => 'Oda Nobuyasu (Oncle d\'Oda Nobunaga)',
                'construction_year' => '1537',
                'classification' => 'Trésor National du Japon & Plus Ancien Donjon en Bois',
                'icon' => '🏯',
                'image' => 'inuyama.jpg',
                'short_desc' => 'Le plus ancien donjon en bois d\'origine du Japon, sentinelle ancestrale du clan Oda dominant la rivière Kiso.',
                'full_description' => 'Fondé en 1537 par Oda Nobuyasu, oncle du grand unificateur Oda Nobunaga, le château d\'Inuyama est vénéré comme le plus vieux donjon encore debout sur le sol japonais. Dressé fièrement sur un éperon rocheux de 40 mètres surplombant les eaux rapides de la rivière Kiso (surnommée le Rhin japonais), il a été le théâtre de batailles mémorables durant l\'ère Sengoku, notamment lors du conflit entre Toyotomi Hideyoshi et Tokugawa Ieyasu. Son dernier étage est ceinturé par un balcon panoramique extérieur en bois sans rambarde fermée, offrant une vue saisissante sur toute la plaine d\'Owari.',
                'architectural_features' => '• Boiseries et piliers d\'origine en cèdre et cyprès hinoki datant du XVIe siècle.\n• Galerie panoramique ouverte circulaire (mawarien) au dernier niveau.\n• Trésor National de structure bōrōgata primitive.',
                'final_battle_lore' => 'Le Berceau des Conquérants. Contrôler Inuyama, c\'est réveiller la flamme tactique d\'Oda Nobunaga. Il accélère la mobilisation des armes à feu et confère une clairvoyance tactique sur les mouvements ennemis.',
                'relic_bonus' => 'Sagesse des Anciens Oda : Réduit de -15% le coût en ressources de toutes les troupes militaires du clan.',
                'default_x' => 18,
                'default_y' => 18,
            ],
            [
                'code' => 'kochi',
                'name' => 'Château de Kōchi',
                'japanese_name' => 'Kōchi-jō',
                'kanji' => '高知城',
                'province' => 'Province de Tosa (Préfecture de Kōchi, Shikoku)',
                'historical_builder' => 'Yamauchi Kazutoyo',
                'construction_year' => '1601 - 1611 (Rebâti 1748)',
                'classification' => 'Bien Culturel Important & Palais Honmaru Intact',
                'icon' => '🏯',
                'image' => 'kochi.jpg',
                'short_desc' => 'Unique forteresse du Japon ayant conservé à la fois son donjon féodal d\'origine et son somptueux palais seigneurial Honmaru Goten.',
                'full_description' => 'Érigé sur la colline d\'Otakasa par Yamauchi Kazutoyo après que Tokugawa Ieyasu lui eut octroyé la province insoumise de Tosa, le château de Kōchi est une merveille d\'exhaustivité. C\'est l\'unique site au Japon où l\'on peut encore admirer à la fois le donjon principal et le palais de résidence du seigneur (Honmaru Goten), ainsi que la porte Ote-mon dans leur configuration originale. Ses murs sont hérissés de piques de fer anti-shinobi (shinobi-gaeshi) et de gouttières massives en pierre (mizu-kiri) conçues pour évacuer les déluges de typhons du Pacifique.',
                'architectural_features' => '• Ensemble complet exceptionnel : Donjon, Palais Honmaru Goten et Porte Ote-mon d\'origine.\n• Piques de fer recourbées anti-infiltration shinobi (shinobi-gaeshi).\n• Systèmes de gargouilles de pierre monumentales drainant les pluies de mousson.',
                'final_battle_lore' => 'Le Bastion Insoumis de Tosa. Véritable verrou de l\'île de Shikoku face à l\'océan Pacifique, Kōchi immunise son détenteur contre les attaques de revers maritimes.',
                'relic_bonus' => 'Pacte des Guerriers de Tosa : Augmente de +20% la protection des silos et greniers contre tout pillage adverse.',
                'default_x' => 12,
                'default_y' => -31,
            ],
            [
                'code' => 'marugame',
                'name' => 'Château de Marugame',
                'japanese_name' => 'Marugame-jō',
                'kanji' => '丸亀城',
                'province' => 'Province de Sanuki (Préfecture de Kagawa, Shikoku)',
                'historical_builder' => 'Ikoma Chikamasa & Kyōgoku Takakazu',
                'construction_year' => '1597 - 1660',
                'classification' => 'Bien Culturel Important & Plus Hautes Murailles de Pierre',
                'icon' => '🏯',
                'image' => 'marugame.jpg',
                'short_desc' => 'Célèbre pour ses vertigineux remparts de pierre de 60 mètres en éventail (ōgi-no-kōbai), les plus imposants du Japon.',
                'full_description' => 'Trônant sur la colline de Kameyama face à la mer intérieure de Seto, le château de Marugame est renommé avant tout pour ses remparts colossaux. Disposés en quatre terrasses successives du pied de la colline jusqu\'au sommet, ces murs de pierre taillée atteignent une hauteur cumulée de plus de 60 mètres. Leur courbure gracieuse, appelée ōgi-no-kōbai (courbure en éventail), s\'incline doucement à la base pour devenir presque verticale au sommet, rendant toute tentative d\'escalade totalement vaine.',
                'architectural_features' => '• Murailles de pierre les plus hautes du Japon (plus de 60 mètres de dénivelé total).\n• Courbure en éventail défensive ōgi-no-kōbai empêchant l\'escalade.\n• Donjon en bois compact à trois niveaux veillant sur les détroits maritimes de Seto.',
                'final_battle_lore' => 'La Citadelle de Pierre Infranchissable. Face aux machines de siège et aux assauts furieux, les remparts de Marugame absorbent les chocs et réduisent drastiquement les pertes de la garnison.',
                'relic_bonus' => 'Muraille de Sanuki : Ajoute +2,000 points de structure de base à tous les remparts de cité du clan.',
                'default_x' => 31,
                'default_y' => -12,
            ],
            [
                'code' => 'maruoka',
                'name' => 'Château de Maruoka',
                'japanese_name' => 'Maruoka-jō',
                'kanji' => '丸岡城',
                'province' => 'Province d\'Echizen (Préfecture de Fukui)',
                'historical_builder' => 'Shibata Katsutoyo (1576)',
                'construction_year' => '1576',
                'classification' => 'Bien Culturel Important & Toit en Tuiles de Pierre',
                'icon' => '🏯',
                'image' => 'maruoka.jpg',
                'short_desc' => 'Le « Château de la Brume » (Kasumi-ga-jō), donjon primitif d\'Echizen doté d\'une toiture unique en lourdes tuiles de pierre volcanique.',
                'full_description' => 'Bâti sur ordre du grand chef de guerre Shibata Katsuie par son neveu Katsutoyo en 1576, Maruoka est l\'un des plus anciens donjons d\'aspect archaïque du Japon. Sa caractéristique la plus fascinante réside dans son toit recouvert de quelque 6 000 tuiles plates sculptées dans la pierre bleue locale d\'Asuwa (shakudani-ishi), matériau conçu pour résister au poids écrasant de la neige et aux incendies. La légende raconte que lorsqu\'un ennemi approche de la forteresse, un serpent géant apparaît et répand un épais brouillard impénétrable qui cache le château aux yeux des assaillants.',
                'architectural_features' => '• Toiture exceptionnelle pesant plus de 60 tonnes constituée de tuiles de pierre d\'Asuwa.\n• Escaliers intérieurs en bois extrêmement raides (65 degrés) avec cordes de traction.\n• Donjon bōrōgata primitif à deux niveaux extérieurs et trois étages intérieurs.',
                'final_battle_lore' => 'Le Voile de Brume Protectrice. Dans la Bataille Finale, Maruoka dissimule l\'état réel des forces armées du clan et neutralise les tentatives d\'espionnage ennemi.',
                'relic_bonus' => 'Voile de Brume d\'Echizen : Immunité accrue contre l\'espionnage shinobi adverse.',
                'default_x' => -12,
                'default_y' => 31,
            ],
            [
                'code' => 'matsue',
                'name' => 'Château de Matsue',
                'japanese_name' => 'Matsue-jō',
                'kanji' => '松江城',
                'province' => 'Province d\'Izumo (Préfecture de Shimane)',
                'historical_builder' => 'Horio Yoshiharu (1607 - 1611)',
                'construction_year' => '1611',
                'classification' => 'Trésor National du Japon',
                'icon' => '🏯',
                'image' => 'matsue.jpg',
                'short_desc' => 'Le « Château du Pluvier » (Chidori-jō), imposante citadelle noire aux façades de bois laqué sombre veillant sur la terre des dieux.',
                'full_description' => 'Fier Trésor National élevé par le général vétéran Horio Yoshiharu, le château de Matsue domine majestueusement le lac Shinji et la lagune de Nakaumi. Surnommé le « Château du Pluvier » en raison de la ressemblance de ses toits en pignon triangulaire avec les ailes déployées de cet oiseau aquatique, il se distingue par son extérieur austère revêtu de planches de bois noircies à la suie et au tanin protecteur. Bâti dans la province sacrée d\'Izumo où se réunissent les huit millions de divinités shinto, il conserve un impressionnant puits intérieur souterrain de 24 mètres et des trappes à pierres meurtrières (ishi-otoshi).',
                'architectural_features' => '• Trésor National à 5 niveaux extérieurs et 6 étages intérieurs avec tour de guet supérieure.\n• Bardage extérieur sombre en bois laqué traité au charbon de bois.\n• Vastes douves navigables préservées reliées au système fluvial de la cité lacustre.',
                'final_battle_lore' => 'La Bénédiction des Kami. Siège spirituel de la province d\'Izumo, le contrôle de Matsue rallie la ferveur des fidèles et décuple la sérénité des sanctuaires de toute la faction.',
                'relic_bonus' => 'Bénédiction des Kami d\'Izumo : +20% de production de ferveur et sérénité (énergie sacrée) sur tous les domaines.',
                'default_x' => -31,
                'default_y' => 12,
            ],
            [
                'code' => 'matsumoto',
                'name' => 'Château de Matsumoto',
                'japanese_name' => 'Matsumoto-jō',
                'kanji' => '松本城',
                'province' => 'Province de Shinano (Préfecture de Nagano)',
                'historical_builder' => 'Ishikawa Kazumasa & Yasunaga (1592 - 1604)',
                'construction_year' => '1604',
                'classification' => 'Trésor National du Japon',
                'icon' => '🏯',
                'image' => 'matsumoto.jpg',
                'short_desc' => 'Le « Château du Corbeau » (Karasu-jō), chef-d\'œuvre d\'ébène laqué à cinq niveaux trônant majestueusement face aux Alpes japonaises.',
                'full_description' => 'L\'un des plus beaux et des plus célèbres châteaux du monde, Matsumoto se dresse fièrement sur une plaine entourée de vastes douves étincelantes dans lesquelles se reflètent les sommets enneigés des Alpes du Nord. Son allure noire saisissante, due aux boiseries laquées de suie sombre qui recouvrent ses murs, lui a valu le surnom de « Château du Corbeau » (Karasu-jō). Il dispose d\'une structure complexe unique : un donjon martial d\'époque Sengoku à 5 niveaux bardé de meurtrières, auquel a été adjointe en temps de paix (1634) une délicate aile d\'observation de la lune (Tsukimi-yagura) ceinte d\'un balcon laqué vermillon.',
                'architectural_features' => '• Plus ancien donjon à 5 niveaux et 6 étages conservé au Japon (Trésor National).\n• Contraste architectural saisissant entre le donjon de guerre et le pavillon de contemplation lunaire.\n• Douves d\'eau vive profondes et système d\'archères et arquebusières (ya-sama et teppō-sama).',
                'final_battle_lore' => 'Le Cœur Stratégique de Shinano. Perché au carrefour des cols alpins, Matsumoto verrouille tout le centre de Honshū. Ses tireurs d\'élite et arquebusiers infligent des dégâts dévastateurs aux avant-gardes ennemies.',
                'relic_bonus' => 'Regard du Corbeau de Shinano : Augmente de +20% la puissance d\'attaque des archers et unités à distance.',
                'default_x' => 31,
                'default_y' => 12,
            ],
            [
                'code' => 'matsuyama',
                'name' => 'Château de Matsuyama',
                'japanese_name' => 'Matsuyama-jō',
                'kanji' => '松山城',
                'province' => 'Province d\'Iyo (Matsuyama, Préfecture d\'Ehime)',
                'historical_builder' => 'Katō Yoshiaki (1602 - 1628 / Rebâti 1854)',
                'construction_year' => '1628 / 1854',
                'classification' => 'Bien Culturel Important & Citadelle Complexe de Colline',
                'icon' => '🏯',
                'image' => 'matsuyama.jpg',
                'short_desc' => 'Formidable citadelle perchée sur le mont Katsuyama avec 21 dépendances historiques d\'origine reliées par des murailles crénelées.',
                'full_description' => 'Fondé par le valeureux général Katō Yoshiaki, l\'un des légendaires « Sept Lances de Shizugatake », le château de Matsuyama culmine sur la colline escarpée de Katsuyama (132 mètres) au cœur de la plaine de Dōgo. C\'est l\'un des rares exemples de donjon de style complexe relié (renritsu-shiki) encore existants : le donjon principal à trois niveaux est solidement ceinturé par des tourelles de défense et des coursives fortifiées formant une cour close imprenable. Il compte 21 bâtiments historiques préservés, représentant l\'un des ensembles fortifiés les plus complets du Japon féodal tardif.',
                'architectural_features' => '• Ensemble fortifié de type renritsu-shiki avec 21 structures féodales authentiques.\n• Portes doubles en chicane Tonan-mon et cours intérieures conçues pour l\'encerclement.\n• Vue stratégique imprenable à 360° sur toute la mer intérieure de Seto.',
                'final_battle_lore' => 'Le Labyrinthe Invincible d\'Iyo. Sa succession de cours closes permet d\'absorber les assauts répétés et d\'organiser de foudroyantes contre-attaques de flanc.',
                'relic_bonus' => 'Labyrinthe d\'Iyo : Réduit de -20% les pertes subies lors des sièges et assauts massifs.',
                'default_x' => -31,
                'default_y' => -12,
            ],
            [
                'code' => 'uwajima',
                'name' => 'Château d\'Uwajima',
                'japanese_name' => 'Uwajima-jō',
                'kanji' => '宇和島城',
                'province' => 'Province d\'Iyo (Uwajima, Préfecture d\'Ehime)',
                'historical_builder' => 'Tōdō Takatora (1596 - 1601) / Date Munetoshi (1666)',
                'construction_year' => '1666',
                'classification' => 'Bien Culturel Important & Joyau Géométrique de Takatora',
                'icon' => '🏯',
                'image' => 'uwajima.jpg',
                'short_desc' => 'Chef-d\'œuvre du génie militaire Tōdō Takatora, donjon au plan pentagonal secret trompant la vue des assaillants.',
                'full_description' => 'Conçu par le plus brillant maître bâtisseur de forteresses du Japon féodal, Tōdō Takatora, le château d\'Uwajima a été perfectionné ultérieurement par Date Hidemune (fils aîné du célèbre Date Masamune, le « Dragon Borgne » de Sendai). Takatora y a appliqué son génie géométrique : le plan de l\'enceinte extérieure forme un pentagone irrégulier qui créait une illusion d\'optique chez les généraux ennemis, les incitant à attaquer sur un front pensé comme rectangulaire et les exposant immédiatement à des tirs de flanc mortels. Son donjon à trois étages conserve ses menuiseries raffinées de l\'ère Kanbun.',
                'architectural_features' => '• Plan pentagonal ingénieux conçu par le maître architecte Tōdō Takatora.\n• Donjon d\'époque Edo compact mais richement orné de pignons de style shoin.\n• Forteresse côtière dominant le goulet stratégique de la baie d\'Uwajima.',
                'final_battle_lore' => 'La Clé Navale de Bungo. Contrôler Uwajima permet de maîtriser les liaisons maritimes entre Kyūshū et Shikoku, constituant la tête de pont idéale pour la bataille finale.',
                'relic_bonus' => 'Géométrie Secrète de Takatora : Confère +10% de résistance globale et +15% de vitesse de navigation aux navires du clan.',
                'default_x' => -12,
                'default_y' => -31,
            ]
        ];

        $stmt = $this->db->prepare("
            INSERT INTO authentic_castles 
            (code, name, japanese_name, kanji, province, historical_builder, construction_year, classification, icon, image, short_desc, full_description, architectural_features, final_battle_lore, relic_bonus, default_x, default_y, coord_x, coord_y, is_spawned, defense_power)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 25000)
        ");

        foreach ($castles as $c) {
            $stmt->execute([
                $c['code'], $c['name'], $c['japanese_name'], $c['kanji'],
                $c['province'], $c['historical_builder'], $c['construction_year'],
                $c['classification'], $c['icon'], $c['image'],
                $c['short_desc'], $c['full_description'], $c['architectural_features'],
                $c['final_battle_lore'], $c['relic_bonus'],
                $c['default_x'], $c['default_y'],
                $c['default_x'], $c['default_y']
            ]);
        }
    }

    /**
     * Récupère tous les 12 châteaux
     */
    public function getAllCastles(): array {
        return $this->db->query("SELECT * FROM authentic_castles ORDER BY id ASC")->fetchAll();
    }

    /**
     * Récupère un château par son code ou son ID
     */
    public function getCastle(string|int $identifier): ?array {
        if (is_numeric($identifier)) {
            $stmt = $this->db->prepare("SELECT * FROM authentic_castles WHERE id = ?");
            $stmt->execute([(int)$identifier]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM authentic_castles WHERE code = ?");
            $stmt->execute([(string)$identifier]);
        }
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Récupère les châteaux déployés sur la carte
     */
    public function getSpawnedCastles(): array {
        return $this->db->query("SELECT * FROM authentic_castles WHERE is_spawned = 1 ORDER BY id ASC")->fetchAll();
    }

    /**
     * Coordonnées harmonieuses réparties sur l'ensemble des 4 quadrants de la carte
     * (3 par quadrant, 1 en zone médiane R~25 et 2 aux confins des provinces R~33, évitant tout recentrage au cœur).
     */
    public static function getHomogeneousCoordinatesMapping(): array {
        return [
            'bitchu_matsuyama' => ['x' => -18, 'y' => -18], // SO Médian
            'hikone'           => ['x' => 18,  'y' => -18], // SE Médian
            'himeji'           => ['x' => -18, 'y' => 18],  // NO Médian
            'hirosaki'         => ['x' => 12,  'y' => 31],  // NE Confins Boréal
            'inuyama'          => ['x' => 18,  'y' => 18],  // NE Médian
            'kochi'            => ['x' => 12,  'y' => -31], // SE Confins Méridional
            'marugame'         => ['x' => 31,  'y' => -12], // SE Confins Oriental
            'maruoka'          => ['x' => -12, 'y' => 31],  // NO Confins Septentrional
            'matsue'           => ['x' => -31, 'y' => 12],  // NO Confins Occidental
            'matsumoto'        => ['x' => 31,  'y' => 12],  // NE Confins Oriental
            'matsuyama'        => ['x' => -31, 'y' => -12], // SO Confins Occidental
            'uwajima'          => ['x' => -12, 'y' => -31], // SO Confins Méridional
        ];
    }

    /**
     * Déploie et répartit de manière homogène les 12 châteaux authentiques sur toute la carte
     */
    public function deployCastlesHomogeneously(int $radius = 35): array {
        $coordsMap = self::getHomogeneousCoordinatesMapping();
        $castles = $this->getAllCastles();
        $deployed = [];

        // Récupérer les emplacements de villages des joueurs pour éviter toute collision
        $stmtP = $this->db->query("SELECT coord_x, coord_y FROM planets WHERE user_id IS NOT NULL");
        $occupied = [];
        while ($r = $stmtP->fetch()) {
            $occupied[$r['coord_x'] . ':' . $r['coord_y']] = true;
        }

        foreach ($castles as $castle) {
            $code = $castle['code'];
            $target = $coordsMap[$code] ?? ['x' => (int)$castle['default_x'], 'y' => (int)$castle['default_y']];
            $x = (int)$target['x'];
            $y = (int)$target['y'];

            // Si le rayon demandé est différent de 35 (par exemple échelle proportionnelle), adapter
            if ($radius !== 35 && $radius >= 15) {
                $scale = $radius / 35.0;
                $x = (int)round($x * $scale);
                $y = (int)round($y * $scale);
            }

            // Éviter les collisions
            while (isset($occupied[$x . ':' . $y])) {
                $x += ($x >= 0) ? 1 : -1;
            }
            $occupied[$x . ':' . $y] = true;

            // Mettre à jour les coordonnées par défaut et en base
            $this->db->prepare("UPDATE authentic_castles SET default_x = ?, default_y = ? WHERE id = ?")
                     ->execute([$x, $y, $castle['id']]);

            $this->spawnCastle((int)$castle['id'], $x, $y);
            $deployed[] = [
                'id' => $castle['id'],
                'name' => $castle['name'],
                'coords' => "[{$x} : {$y}]"
            ];
        }

        return [
            'success' => true,
            'count' => count($deployed),
            'castles' => $deployed,
            'message' => "Les 12 Châteaux Authentiques du Japon ont été répartis de manière homogène sur l'ensemble de la carte !"
        ];
    }

    /**
     * Déploie tous les 12 châteaux sur la carte aux coordonnées homogènes
     */
    public function spawnAllCastles(): array {
        return $this->deployCastlesHomogeneously(35);
    }

    /**
     * Retire tous les 12 châteaux de la carte
     */
    public function despawnAllCastles(): array {
        $stmt = $this->db->query("SELECT id FROM authentic_castles WHERE is_spawned = 1");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $this->despawnCastle((int)$id);
        }

        return ['success' => true, 'count' => count($ids), 'message' => "Tous les Châteaux Authentiques ont été retirés de la carte."];
    }

    /**
     * Déploie un château spécifique sur les coordonnées (x, y)
     */
    public function spawnCastle(int $castleId, int $x, int $y): bool {
        $castle = $this->getCastle($castleId);
        if (!$castle) return false;

        // Vérifier si une planète existe déjà à ces coordonnées
        $stmtPlanet = $this->db->prepare("SELECT id, user_id FROM planets WHERE coord_x = ? AND coord_y = ?");
        $stmtPlanet->execute([$x, $y]);
        $existingPlanet = $stmtPlanet->fetch();

        $planetId = null;
        if ($existingPlanet) {
            $planetId = (int)$existingPlanet['id'];
            // Si c'est une planète inoccupée, on la configure comme donjon authentique
            if (empty($existingPlanet['user_id'])) {
                $this->db->prepare("
                    UPDATE planets 
                    SET name = ?, is_capital = 2 
                    WHERE id = ?
                ")->execute([$castle['name'], $planetId]);
            }
        } else {
            // Créer la planète sanctuaire sur la carte
            $insertPlanet = $this->db->prepare("
                INSERT INTO planets 
                (name, coord_x, coord_y, planet_type, metal, crystal, deuterium, energy_used, energy_max, metal_max, crystal_max, deuterium_max, is_capital, last_resource_update)
                VALUES (?, ?, ?, 'terrestrial', 50000, 50000, 50000, 0, 100, 100000, 100000, 100000, 2, UNIX_TIMESTAMP())
            ");
            $insertPlanet->execute([$castle['name'], $x, $y]);
            $planetId = (int)$this->db->lastInsertId();
        }

        // Mettre à jour l'entrée du château
        $up = $this->db->prepare("
            UPDATE authentic_castles 
            SET is_spawned = 1, coord_x = ?, coord_y = ?, planet_id = ? 
            WHERE id = ?
        ");
        return $up->execute([$x, $y, $planetId, $castleId]);
    }

    /**
     * Retire un château spécifique de la carte
     */
    public function despawnCastle(int $castleId): bool {
        $castle = $this->getCastle($castleId);
        if (!$castle) return false;

        if (!empty($castle['planet_id'])) {
            // Si la planète n'appartient à aucun joueur, la supprimer de la carte
            $stmt = $this->db->prepare("SELECT user_id FROM planets WHERE id = ?");
            $stmt->execute([$castle['planet_id']]);
            $planet = $stmt->fetch();
            if ($planet && empty($planet['user_id'])) {
                $this->db->prepare("DELETE FROM planets WHERE id = ?")->execute([$castle['planet_id']]);
            }
        }

        $up = $this->db->prepare("
            UPDATE authentic_castles 
            SET is_spawned = 0, planet_id = NULL 
            WHERE id = ?
        ");
        return $up->execute([$castleId]);
    }

    /**
     * Met à jour les coordonnées d'un château
     */
    public function updateCastleCoords(int $castleId, int $x, int $y): bool {
        $castle = $this->getCastle($castleId);
        if (!$castle) return false;

        if ($castle['is_spawned']) {
            $this->despawnCastle($castleId);
            return $this->spawnCastle($castleId, $x, $y);
        } else {
            $stmt = $this->db->prepare("UPDATE authentic_castles SET coord_x = ?, coord_y = ? WHERE id = ?");
            return $stmt->execute([$x, $y, $castleId]);
        }
    }
}
