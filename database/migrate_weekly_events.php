<?php
/**
 * Migration & Initialisation du Système d'Événements Hebdomadaires Narratifs (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== Migration : Événements Hebdomadaires Narratifs (Chroniques du Sengoku) ===\n";

try {
    if (in_array('--sqlite', $argv ?? [])) {
        $sqliteFile = __DIR__ . '/../openshogun.sqlite';
        $db = new PDO('sqlite:' . $sqliteFile);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Database::setConnection($db);
    } else {
        $db = Database::getConnection();
    }
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS weekly_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT UNIQUE NOT NULL,
                title TEXT NOT NULL,
                jp_title TEXT NOT NULL DEFAULT '',
                era_year TEXT NOT NULL DEFAULT 'Époque Sengoku',
                description TEXT NOT NULL,
                image_url TEXT NOT NULL DEFAULT '/public/assets/buildings/hq.jpg',
                starts_at TEXT NOT NULL,
                ends_at TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 0,
                reward_final_gold INTEGER NOT NULL DEFAULT 50,
                reward_final_honor INTEGER NOT NULL DEFAULT 100,
                reward_final_seal_days INTEGER NOT NULL DEFAULT 1,
                reward_final_title TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS weekly_event_tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                step_order INTEGER NOT NULL DEFAULT 1,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                task_type TEXT NOT NULL,
                target_key TEXT NOT NULL,
                target_amount INTEGER NOT NULL DEFAULT 1,
                reward_metal INTEGER NOT NULL DEFAULT 0,
                reward_crystal INTEGER NOT NULL DEFAULT 0,
                reward_deuterium INTEGER NOT NULL DEFAULT 0,
                reward_gold INTEGER NOT NULL DEFAULT 0,
                reward_honor INTEGER NOT NULL DEFAULT 0,
                reward_item TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES weekly_events (id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS player_weekly_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                is_completed INTEGER NOT NULL DEFAULT 0,
                final_claimed INTEGER NOT NULL DEFAULT 0,
                completed_at TEXT DEFAULT NULL,
                claimed_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, event_id)
            );

            CREATE TABLE IF NOT EXISTS player_weekly_event_tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                task_id INTEGER NOT NULL,
                current_progress INTEGER NOT NULL DEFAULT 0,
                is_completed INTEGER NOT NULL DEFAULT 0,
                is_claimed INTEGER NOT NULL DEFAULT 0,
                claimed_at TEXT DEFAULT NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, task_id)
            );
        ");
    } else {
        $sqlFile = __DIR__ . '/migrations/004_create_weekly_events.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $db->exec($sql);
        }
    }
    echo "[OK] Tables des événements hebdomadaires créées avec succès.\n";

    // Vérifier si des événements existent déjà
    $stmtCount = $db->query("SELECT COUNT(*) FROM weekly_events");
    $eventCount = (int)$stmtCount->fetchColumn();

    if ($eventCount === 0) {
        echo "[INFO] Amorçage des 4 premières Chroniques Historiques Féodales...\n";

        $now = time();
        // Semaine 1 : Commence maintenant, dure 7 jours
        $start1 = date('Y-m-d 00:00:00', $now);
        $end1   = date('Y-m-d 23:59:59', $now + (7 * 86400));

        // Semaine 2 : Semaine suivante
        $start2 = date('Y-m-d 00:00:00', $now + (8 * 86400));
        $end2   = date('Y-m-d 23:59:59', $now + (14 * 86400));

        // Semaine 3
        $start3 = date('Y-m-d 00:00:00', $now + (15 * 86400));
        $end3   = date('Y-m-d 23:59:59', $now + (21 * 86400));

        // Semaine 4
        $start4 = date('Y-m-d 00:00:00', $now + (22 * 86400));
        $end4   = date('Y-m-d 23:59:59', $now + (28 * 86400));

        $events = [
            [
                'slug'                   => 'okehazama_1560',
                'title'                  => "L'Alerte d'Okehazama",
                'jp_title'               => "桶狭間の奇襲 (Okehazama no Kishū)",
                'era_year'               => "Juin 1560 — Ère Eiroku",
                'description'            => "L'armée d'invasion colossale du puissant seigneur Imagawa Yoshimoto pénètre en province d'Owari sous un orage dantesque. Le jeune Oda Nobunaga rassemble ses hommes au temple Zen Enshō-ji et prépare une contre-attaque fulgurante. Pour soutenir les forteresses assiégées et alimenter l'avant-garde, votre domaine doit d'urgence stocker du ravitaillement, consolider le Donjon Tenshu et lever une troupe de vaillants piquiers Ashigaru !",
                'image_url'              => "/public/assets/buildings/hq.jpg",
                'starts_at'              => $start1,
                'ends_at'                => $end1,
                'is_active'              => 1, // Actif dès le lancement
                'reward_final_gold'      => 60,
                'reward_final_honor'     => 120,
                'reward_final_seal_days' => 1,
                'reward_final_title'     => "Héros d'Okehazama",
                'tasks'                  => [
                    [
                        'step_order'    => 1,
                        'title'         => "Acte I : Ravitaillement d'Urgence",
                        'description'   => "Amasser des vivres pour les messagers et garnisons en alerte. Posséder au moins 2 500 unités de Bois de Cèdre et 2 000 de Riz Impérial en réserve.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "deuterium",
                        'target_amount' => 2000,
                        'reward_metal'  => 800,
                        'reward_crystal'=> 600,
                        'reward_gold'   => 5,
                        'reward_honor'  => 25,
                    ],
                    [
                        'step_order'    => 2,
                        'title'         => "Acte II : La Vigie du Donjon",
                        'description'   => "Inspecter les horizons depuis le sommet du Donjon seigneurial. Élever ou posséder le Donjon Tenshu au Niveau 2 ou supérieur.",
                        'task_type'     => "upgrade_building",
                        'target_key'    => "hq",
                        'target_amount' => 2,
                        'reward_metal'  => 1200,
                        'reward_deuterium' => 800,
                        'reward_gold'   => 10,
                        'reward_honor'  => 35,
                    ],
                    [
                        'step_order'    => 3,
                        'title'         => "Acte III : Le Rempart de Piques",
                        'description'   => "Former une solide compagnie de piquiers pour briser la cavalerie ennemie. Entraîner et maintenir au moins 25 Lanciers Yari Ashigaru.",
                        'task_type'     => "train_troops",
                        'target_key'    => "yari_ashigaru",
                        'target_amount' => 25,
                        'reward_deuterium' => 1500,
                        'reward_gold'   => 15,
                        'reward_honor'  => 50,
                        'reward_item'   => "rice_flour:40",
                    ]
                ]
            ],
            [
                'slug'                   => 'sakai_tea_1569',
                'title'                  => "La Voie du Thé à Sakai",
                'jp_title'               => "堺の茶の湯 (Sakai no Cha-no-yu)",
                'era_year'               => "Automne 1569 — Ère Eiroku",
                'description'            => "Dans la florissante cité franche de Sakai, le grand maître Sen no Rikyū codifie l'art sobre et sacré du Wabi-cha pour apaiser les esprits des seigneurs de guerre. Les plus puissants daimyos recherchent des bols Chawan façonnés dans l'argile alluviale la plus pure ainsi que les meilleures feuilles de thé récoltées sur les coteaux brumeux.",
                'image_url'              => "/public/assets/resources/terroir_tea.jpg",
                'starts_at'              => $start2,
                'ends_at'                => $end2,
                'is_active'              => 0,
                'reward_final_gold'      => 75,
                'reward_final_honor'     => 150,
                'reward_final_seal_days' => 2,
                'reward_final_title'     => "Maître du Cha-no-yu",
                'tasks'                  => [
                    [
                        'step_order'    => 1,
                        'title'         => "Acte I : L'Argile des Potiers",
                        'description'   => "Extraire l'argile des berges pour façonner les bols Chawan et les tuiles des pavillons. Posséder au moins 1 500 unités d'Argile de Céramique.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "clay",
                        'target_amount' => 1500,
                        'reward_metal'  => 900,
                        'reward_crystal'=> 900,
                        'reward_gold'   => 8,
                        'reward_honor'  => 30,
                    ],
                    [
                        'step_order'    => 2,
                        'title'         => "Acte II : La Récolte d'Émeraude",
                        'description'   => "Faire cueillir les bourgeons les plus délicats sur vos coteaux de théiers. Posséder au moins 1 500 feuilles de Thé & Matcha.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "tea",
                        'target_amount' => 1500,
                        'reward_crystal'=> 1200,
                        'reward_deuterium' => 1000,
                        'reward_gold'   => 12,
                        'reward_honor'  => 40,
                    ],
                    [
                        'step_order'    => 3,
                        'title'         => "Acte III : L'Hospitalité Harmonieuse",
                        'description'   => "Préparer le banquet cérémoniel et brasser le saké des grandes réceptions. Posséder au moins 200 gourdes de Saké affiné.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "sake",
                        'target_amount' => 200,
                        'reward_metal'  => 1500,
                        'reward_deuterium' => 1500,
                        'reward_gold'   => 20,
                        'reward_honor'  => 60,
                        'reward_item'   => "wooden_beams:30",
                    ]
                ]
            ],
            [
                'slug'                   => 'rakuichi_rakuza_1577',
                'title'                  => "L'Édit du Rakuichi Rakuza",
                'jp_title'               => "楽市楽座 (Rakuichi Rakuza)",
                'era_year'               => "Printemps 1577 — Ère Tenshō",
                'description'            => "Au pied de l'immense forteresse d'Azuchi, Oda Nobunaga proclame l'abolition des péages corporatifs et fonde les grands marchés libres. Artisans, marchands de saké, charpentiers et cultivateurs de soja affluent de toutes les provinces pour commercer sans entraves. Dynamisez l'artisanat de votre domaine pour rayonner sur les foires !",
                'image_url'              => "/public/assets/resources/terroir_village.jpg",
                'starts_at'              => $start3,
                'ends_at'                => $end3,
                'is_active'              => 0,
                'reward_final_gold'      => 85,
                'reward_final_honor'     => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title'     => "Protecteur des Corporations",
                'tasks'                  => [
                    [
                        'step_order'    => 1,
                        'title'         => "Acte I : Les Sillons d'Or Vert",
                        'description'   => "Fournir le soja indispensable aux fabriques de tofu, sauces et pâtes de miso des étals. Posséder au moins 2 000 fèves de Soja.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "soybean",
                        'target_amount' => 2000,
                        'reward_metal'  => 1200,
                        'reward_crystal'=> 1000,
                        'reward_gold'   => 10,
                        'reward_honor'  => 35,
                    ],
                    [
                        'step_order'    => 2,
                        'title'         => "Acte II : L'Atelier de Raffinage",
                        'description'   => "Moderniser les meules hydrauliques pour approvisionner le marché en farine blanche. Posséder la Meunerie urbaine au Niveau 2 ou supérieur.",
                        'task_type'     => "upgrade_building",
                        'target_key'    => "grain_mill",
                        'target_amount' => 2,
                        'reward_crystal'=> 1500,
                        'reward_deuterium' => 1200,
                        'reward_gold'   => 15,
                        'reward_honor'  => 45,
                        'reward_item'   => "rice_flour:60",
                    ],
                    [
                        'step_order'    => 3,
                        'title'         => "Acte III : Les Grands Entrepôts",
                        'description'   => "Prouver la prospérité de votre domaine en constituant une grande réserve commerciale. Posséder au moins 6 000 Bois de Cèdre.",
                        'task_type'     => "stock_resource",
                        'target_key'    => "metal",
                        'target_amount' => 6000,
                        'reward_metal'  => 2000,
                        'reward_deuterium' => 2000,
                        'reward_gold'   => 20,
                        'reward_honor'  => 70,
                    ]
                ]
            ],
            [
                'slug'                   => 'kawanakajima_1561',
                'title'                  => "Les Dragons de Kawanakajima",
                'jp_title'               => "川中島の合戦 (Kawanakajima no Kassen)",
                'era_year'               => "Automne 1561 — Ère Eiroku",
                'description'            => "Dans la brume matinale de la plaine fluviale de Kawanakajima, le Tigre de Kai (Takeda Shingen) et le Dragon d'Echigo (Uesugi Kenshin) s'affrontent au corps à corps au cours de leur plus célèbre duel tactique. Les charges de cavalerie et les volées de flèches forgent les plus nobles traditions martiales du Japon féodal !",
                'image_url'              => "/public/assets/resources/ressource_bois_cedre.jpg",
                'starts_at'              => $start4,
                'ends_at'                => $end4,
                'is_active'              => 0,
                'reward_final_gold'      => 100,
                'reward_final_honor'     => 250,
                'reward_final_seal_days' => 3,
                'reward_final_title'     => "Vétéran de Kawanakajima",
                'tasks'                  => [
                    [
                        'step_order'    => 1,
                        'title'         => "Acte I : L'Exploitation Sylvicole",
                        'description'   => "Abattre les cèdres millénaires pour confectionner palissades et hampes de lances. Développer la Forêt de Cèdres au Niveau 2 ou supérieur.",
                        'task_type'     => "upgrade_rural",
                        'target_key'    => "foret",
                        'target_amount' => 2,
                        'reward_metal'  => 1500,
                        'reward_crystal'=> 1000,
                        'reward_gold'   => 12,
                        'reward_honor'  => 40,
                    ],
                    [
                        'step_order'    => 2,
                        'title'         => "Acte II : L'Ordre des Samouraïs",
                        'description'   => "Équiper et rassembler vos guerriers d'élite pour le choc frontal. Entraîner au moins 15 Samouraïs Bushi au domaine.",
                        'task_type'     => "train_troops",
                        'target_key'    => "samurai",
                        'target_amount' => 15,
                        'reward_metal'  => 2000,
                        'reward_deuterium' => 1800,
                        'reward_gold'   => 20,
                        'reward_honor'  => 60,
                    ],
                    [
                        'step_order'    => 3,
                        'title'         => "Acte III : La Sérénité Divine",
                        'description'   => "Prier pour la victoire et obtenir la bénédiction des Kami. Développer le Sanctuaire Shintō au Niveau 2 ou supérieur.",
                        'task_type'     => "upgrade_rural",
                        'target_key'    => "sanctuaire_shinto",
                        'target_amount' => 2,
                        'reward_crystal'=> 2000,
                        'reward_deuterium' => 2000,
                        'reward_gold'   => 25,
                        'reward_honor'  => 80,
                        'reward_item'   => "sake:60",
                    ]
                ]
            ]
        ];

        $stmtInsEvent = $db->prepare("
            INSERT INTO weekly_events (slug, title, jp_title, era_year, description, image_url, starts_at, ends_at, is_active, reward_final_gold, reward_final_honor, reward_final_seal_days, reward_final_title)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtInsTask = $db->prepare("
            INSERT INTO weekly_event_tasks (event_id, step_order, title, description, task_type, target_key, target_amount, reward_metal, reward_crystal, reward_deuterium, reward_gold, reward_honor, reward_item)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($events as $ev) {
            $stmtInsEvent->execute([
                $ev['slug'],
                $ev['title'],
                $ev['jp_title'],
                $ev['era_year'],
                $ev['description'],
                $ev['image_url'],
                $ev['starts_at'],
                $ev['ends_at'],
                $ev['is_active'],
                $ev['reward_final_gold'],
                $ev['reward_final_honor'],
                $ev['reward_final_seal_days'],
                $ev['reward_final_title'],
            ]);
            $eventId = (int)$db->lastInsertId();

            foreach ($ev['tasks'] as $t) {
                $stmtInsTask->execute([
                    $eventId,
                    $t['step_order'],
                    $t['title'],
                    $t['description'],
                    $t['task_type'],
                    $t['target_key'],
                    $t['target_amount'],
                    $t['reward_metal'] ?? 0,
                    $t['reward_crystal'] ?? 0,
                    $t['reward_deuterium'] ?? 0,
                    $t['reward_gold'] ?? 0,
                    $t['reward_honor'] ?? 0,
                    $t['reward_item'] ?? null
                ]);
            }
            echo "- Événement '{$ev['title']}' inséré avec " . count($ev['tasks']) . " actes narratifs.\n";
        }

        echo "[SUCCÈS] Les 4 Chroniques Féodales initiales ont été créées avec honneur !\n";
    } else {
        echo "[INFO] $eventCount événement(s) déjà présent(s) en base de données.\n";
    }

} catch (Exception $e) {
    echo "[ERREUR] Exception migration : " . $e->getMessage() . "\n";
}
