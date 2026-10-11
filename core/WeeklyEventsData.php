<?php
/**
 * Catalogue Officiel des 52 Chroniques Féodales du Sengoku (1 Événement par Semaine)
 * OpenShogun - Base historique exhaustive avec 52 récits, 156 actes narratifs et 52 prompts d'illustration IA.
 */

class WeeklyEventsData {

    public static function getAll(): array {
        return [
            // ─────────────────────────────────────────────────────────────
            // SEMAINE 01 : LES ARQUEBUSES DE TANEGASHIMA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 1,
                'slug' => 'semaine_01_tanegashima_1543',
                'title' => "L'Arrivée du Tonnerre à Tanegashima",
                'jp_title' => "種子島銃の伝来 (Tanegashima-jū no Denrai)",
                'era_year' => "1543 — Île de Tanegashima (Ère Tenbun)",
                'description' => "Une jonque chinoise en détresse s'échoue sur les rivages méridionaux de Tanegashima avec à son bord des marchands portugais munis d'armes à feu inconnues : l'arquebuse à mèche. Le jeune seigneur Tanegashima Tokitaka achète deux mousquets pour une somme colossale et ordonne immédiatement à ses maîtres forgerons de percer le secret de la culasse hélicoïdale et de la poudre noire.",
                'image_url' => "/public/assets/events/event_week_01.jpg",
                'reward_final_gold' => 60,
                'reward_final_honor' => 120,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Pionnier des Forges",
                'prompt' => "Cinematic panoramic view of 16th-century feudal Japan, Portuguese merchants and samurai meeting on Tanegashima sandy beach, wooden junk boat anchored, stormy waves, samurai inspecting an ornate Tanegashima matchlock arquebus gun in front of palm and cedar trees, rich hand-drawn ukiyo-e digital oil painting style of Grepolis and Travian, warm cinematic sunlight, detailed period costumes, highly detailed, 16:9 --no text, modern elements, frame",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Acier des Hauts Fourneaux",
                        'description' => "Fournir le fer nécessaire aux premiers essais des forgerons d'armures. Amasser au moins 2 500 unités de Métal.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 2500,
                        'reward_crystal' => 800,
                        'reward_gold' => 5,
                        'reward_honor' => 20
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Atelier des Armuriers",
                        'description' => "Ériger ou posséder une Fonderie / Caserne militaire de Niveau 2 pour marteler les premiers canons.",
                        'task_type' => "upgrade_building",
                        'target_key' => "barracks",
                        'target_amount' => 2,
                        'reward_deuterium' => 1000,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Première Salve d'Essai",
                        'description' => "Enrôler au moins 20 soldats et mousquetaires pour tester les volées de plombs.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 20,
                        'reward_gold' => 15,
                        'reward_honor' => 45,
                        'reward_item' => 'wooden_beams:20'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 02 : L'ALERTE D'OKEHAZAMA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 2,
                'slug' => 'semaine_02_okehazama_1560',
                'title' => "L'Alerte d'Okehazama",
                'jp_title' => "桶狭間の奇襲 (Okehazama no Kishū)",
                'era_year' => "Juin 1560 — Province d'Owari (Ère Eiroku)",
                'description' => "L'armée colossale de 25 000 guerriers d'Imagawa Yoshimoto pénètre en Owari. Bravant les conseils de prudence de ses vassaux, Oda Nobunaga réunit 2 500 samouraïs téméraires, s'élance sous une pluie diluvienne à travers la gorge d'Okehazama et frappe le quartier général ennemi comme un éclair foudroyant.",
                'image_url' => "/public/assets/events/event_week_02.jpg",
                'reward_final_gold' => 70,
                'reward_final_honor' => 140,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Vainqueur d'Okehazama",
                'prompt' => "Dramatic wide panoramic landscape of feudal Japan under torrential thunderstorm rain, young Oda Nobunaga leading mounted samurai charging down muddy hillside into enemy encampment in narrow misty valley, lightning strikes illuminating colorful heraldic sashimono banners, authentic samurai armor, Travian and Grepolis illustration aesthetic, dynamic composition, cinematic lighting, 16:9 --no modern vehicles, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Vivres d'Urgence pour l'Avant-Garde",
                        'description' => "Rassembler 3 000 koku de Riz Impérial pour alimenter la marche forcée.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 3000,
                        'reward_metal' => 1200,
                        'reward_gold' => 8,
                        'reward_honor' => 25
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Vigie du Donjon",
                        'description' => "Porter le Donjon Tenshu au Niveau 2 ou supérieur pour scruter les fumées d'invasion.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 2,
                        'reward_metal' => 1500,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Choc des Piques",
                        'description' => "Rassembler une compagnie de 25 piquiers d'élite pour transpercer les lignes.",
                        'task_type' => "train_troops",
                        'target_key' => "spearman",
                        'target_amount' => 25,
                        'reward_gold' => 20,
                        'reward_honor' => 50,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 03 : LES DRAGONS DE KAWANAKAJIMA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 3,
                'slug' => 'semaine_03_kawanakajima_1561',
                'title' => "Les Dragons de Kawanakajima",
                'jp_title' => "川中島の激闘 (Kawanakajima no Gekitō)",
                'era_year' => "Septembre 1561 — Plaine de Kawanakajima",
                'description' => "Dans la brume matinale au confluent des fleuves Sai et Chikuma, le Tigre de Kai (Takeda Shingen) et le Dragon d'Echigo (Uesugi Kenshin) s'affrontent au corps à corps dans l'une des batailles les plus sanglantes et tactiques du Moyen Âge japonais.",
                'image_url' => "/public/assets/events/event_week_03.jpg",
                'reward_final_gold' => 75,
                'reward_final_honor' => 150,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Tigre de Kawanakajima",
                'prompt' => "Epic medieval battle duel on foggy riverbanks in feudal Japan, legendary duel between two daimyo warlords on galloping warhorses, swirling morning mist over river, red samurai battle armor, Furinkazan war banners in background, Travian and Total War Shogun artistic digital painting style, warm morning sunbeam piercing thick fog, 16:9 widescreen --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Acier des Lames",
                        'description' => "Forger et stocker au moins 3 500 unités de Métal pour armer les cavaliers.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 3500,
                        'reward_crystal' => 1000,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Trempe Martiale du Héros",
                        'description' => "Élever la puissance martiale de votre Samouraï Héros à au moins 200.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 200,
                        'reward_deuterium' => 1500,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Armée des Vents et Forêts",
                        'description' => "Mobiliser au moins 40 guerriers sous vos bannières de guerre.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 20,
                        'reward_honor' => 50,
                        'reward_item' => 'sake:20'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 04 : LA PRISE D'INABAYAMA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 4,
                'slug' => 'semaine_04_inabayama_1567',
                'title' => "La Prise d'Inabayama",
                'jp_title' => "稲葉山城の戦い (Inabayama-jō no Tatakai)",
                'era_year' => "1567 — Mont Kinka, Province de Mino",
                'description' => "Perché au sommet du mont Kinka, le château réputé imprenable d'Inabayama est infiltré de nuit par Toyotomi Hideyoshi et ses éclaireurs escaladant les falaises rocheuses. Après la victoire, Nobunaga renomme la forteresse 'Gifu' en référence au mont Qi de Chine impériale.",
                'image_url' => "/public/assets/events/event_week_04.jpg",
                'reward_final_gold' => 65,
                'reward_final_honor' => 130,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Conquérant de Gifu",
                'prompt' => "Nighttime panoramic castle siege in feudal Japan, majestic fortress keep perched atop steep craggy forested mountain under moonlight, ninja scouts scaling sheer cliffs with ropes, distant campfires glowing in Mino valley below, starry night sky with cherry blossom petals drifting, painterly digital strategy game art style, rich atmospheric shadows, 16:9 --no text, modern gear, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Poutres d'Échafaudage",
                        'description' => "Rassembler 30 Poutres de Bois pour construire les engins d'escalade.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 30,
                        'reward_metal' => 1500,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Forêt de Cèdres",
                        'description' => "Exploiter la Forêt de Cèdres au Niveau 3 ou plus pour ravitailler les chantiers.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "foret",
                        'target_amount' => 3,
                        'reward_crystal' => 1000,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Escalade Nocturne",
                        'description' => "Rassembler une compagnie de 30 fantassins pour investir la forteresse.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 30,
                        'reward_gold' => 18,
                        'reward_honor' => 45,
                        'reward_item' => 'rice_flour:25'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 05 : LA VOIE DU THÉ À SAKAI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 5,
                'slug' => 'semaine_05_sakai_tea_1569',
                'title' => "La Voie du Thé à Sakai",
                'jp_title' => "堺の茶の湯 (Sakai no Cha-no-yu)",
                'era_year' => "1569 — Cité Franche de Sakai",
                'description' => "Dans la cité marchande autonome de Sakai, le maître Sen no Rikyū codifie l'art sacré du Wabi-cha. Les seigneurs de guerre déposent leurs katanas à l'extérieur des pavillons de thé pour partager un bol de matcha dans une communion de paix et d'humilité absolue.",
                'image_url' => "/public/assets/events/event_week_05.jpg",
                'reward_final_gold' => 70,
                'reward_final_honor' => 135,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Maître du Cha-no-yu",
                'prompt' => "Tranquil and elegant traditional Japanese tea garden in Sakai, mossy stepping stones leading to rustic wooden chashitsu tea pavilion, bamboo water spout Tsukubai, blooming camellia flowers, samurai katanas resting on exterior wooden rack, soft misty afternoon lighting, Grepolis and Travian refined artistic illustration, peaceful and harmonic atmosphere, 16:9 --no text, watermark, modern artifacts",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Argile des Bols Chawan",
                        'description' => "Extraire au moins 1 500 unités d'Argile de poterie pour façonner les bols sacrés.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 1500,
                        'reward_metal' => 1000,
                        'reward_gold' => 8,
                        'reward_honor' => 25
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Récolte de Jade",
                        'description' => "Amasser 1 200 unités de Thé Vert sur les collines du domaine.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 1200,
                        'reward_deuterium' => 1200,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Saké de Communion",
                        'description' => "Fournir au moins 40 barriques de Saké Raffiné pour honorer les hôtes.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 40,
                        'reward_gold' => 20,
                        'reward_honor' => 50,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 06 : LA BATAILLE D'ANEGAWA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 6,
                'slug' => 'semaine_06_anegawa_1570',
                'title' => "Les Eaux Rouges d'Anegawa",
                'jp_title' => "姉川の戦い (Anegawa no Tatakai)",
                'era_year' => "Juillet 1570 — Fleuve Anegawa, Province d'Ōmi",
                'description' => "Les armées coalisées d'Oda et Tokugawa affrontent les clans Asai et Asakura dans le lit caillouteux du fleuve Anegawa. Les combattants luttent pied à pied dans l'eau glacée teintée de cramoisi jusqu'à la percée héroïque de la division de Tokugawa Ieyasu.",
                'image_url' => "/public/assets/events/event_week_06.jpg",
                'reward_final_gold' => 75,
                'reward_final_honor' => 150,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Garde d'Anegawa",
                'prompt' => "Intense panoramic battlefield along wide shallow river in feudal Japan, samurai infantry clashing in sparkling water splashes, colorful heraldic flags with Tokugawa hollyhock mon and Oda crest, dramatic summer storm clouds, riverbanks lined with weeping willows, painterly tactical strategy game vector art, highly detailed warriors, 16:9 --no text, modern elements",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de Traversée",
                        'description' => "Stocker 3 500 unités de Riz Impérial pour soutenir les troupes engagées.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 3500,
                        'reward_metal' => 1500,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Muraille Protectrice",
                        'description' => "Élever le Mur d'Enceinte de la Cité au Niveau 2 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "wall",
                        'target_amount' => 2,
                        'reward_crystal' => 1200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Phalange Fluviale",
                        'description' => "Mobiliser au moins 35 fantassins et archers d'élite.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 22,
                        'reward_honor' => 55,
                        'reward_item' => 'wooden_beams:25'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 07 : LE MONT HIEI ET LES MOINES-GUERRIERS
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 7,
                'slug' => 'semaine_07_hieizan_1571',
                'title' => "Le Siège du Mont Hiei",
                'jp_title' => "比叡山焼き討ち (Hieizan Yakiuchi)",
                'era_year' => "Septembre 1571 — Temple Enryaku-ji",
                'description' => "Refusant l'ingérence des redoutables moines-guerriers Sōhei d'Enryaku-ji alliés à ses rivaux, Nobunaga encercle la montagne sacrée du mont Hiei. Une démonstration de force implacable qui soumet les ordres monastiques guerriers à l'autorité séculière du Shogunat.",
                'image_url' => "/public/assets/events/event_week_07.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 160,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Défricheur du Mont Hiei",
                'prompt' => "Atmospheric panoramic mountain monastery in feudal Japan surrounded by autumn maple foliage, towering ancient pagodas and cedar trees veiled in sacred mountain mist, warrior sohei monks in white cowls wielding naginata standing atop stone temple steps, GrepolisTravian painterly strategy art, striking vibrant red and gold leaves, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Outil des Carriers",
                        'description' => "Amasser 2 500 unités de Granit pour ériger les bastions d'encerclement.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 2500,
                        'reward_metal' => 1200,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Carrière de Pierre",
                        'description' => "Porter la Carrière de Granit rurale au Niveau 3 ou plus.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "carriere",
                        'target_amount' => 3,
                        'reward_deuterium' => 1500,
                        'reward_gold' => 15,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Discipline Martiale",
                        'description' => "Maintenir au moins 30 soldats réguliers sous vos ordres.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 30,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'sake:25'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 08 : LA DÉBÂCLE DE MIKATAGAHARA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 8,
                'slug' => 'semaine_08_mikatagahara_1573',
                'title' => "La Débâcle de Mikatagahara",
                'jp_title' => "三方ヶ原の戦い (Mikatagahara no Tatakai)",
                'era_year' => "Janvier 1573 — Plateau de Mikatagahara",
                'description' => "La redoutable cavalerie rouge de Takeda Shingen déferle sur les troupes de Tokugawa Ieyasu sur le plateau balayé par les vents glaciaux de Mikatagahara. Vaincu mais déterminé à en tirer les leçons, Ieyasu fait peindre son propre portrait affligé pour ne jamais oublier l'humilité et la persévérance.",
                'image_url' => "/public/assets/events/event_week_08.jpg",
                'reward_final_gold' => 75,
                'reward_final_honor' => 145,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Garde de Hamamatsu",
                'prompt' => "Winter panoramic battlefield on cold windy plateau in feudal Japan, red armored Takeda samurai cavalry riding through snow flurries, defeated retreating samurai rallying around Tokugawa castle gate in the background, stark dramatic contrast of snow and crimson banners, painterly feudal strategy art style, atmospheric winter sunlight, 16:9 --no text, modern items",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de Survie d'Hiver",
                        'description' => "Fournir au moins 1 500 unités de Soja des Moissons pour nourrir la garnison.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 1500,
                        'reward_metal' => 1200,
                        'reward_gold' => 10,
                        'reward_honor' => 25
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Consolider la Citadelle",
                        'description' => "Élever le Donjon Tenshu au Niveau 3 pour abriter les blessés.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 3,
                        'reward_crystal' => 1500,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Relever la Garnison",
                        'description' => "Reformer un bataillon de 30 lanciers Ashigaru prêts au combat.",
                        'task_type' => "train_troops",
                        'target_key' => "spearman",
                        'target_amount' => 30,
                        'reward_gold' => 22,
                        'reward_honor' => 55,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 09 : LA CHUTE D'ICHIJŌDANI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 9,
                'slug' => 'semaine_09_ichijodani_1573',
                'title' => "La Chute d'Ichijōdani",
                'jp_title' => "一乗谷の戦い (Ichijōdani no Tatakai)",
                'era_year' => "Août 1573 — Vallée d'Ichijōdani, Province d'Echizen",
                'description' => "La fastueuse capitale montagnarde du clan Asakura, surnommée le Petit Kyoto d'Echizen avec ses jardins de noblesse et ses ateliers de tissage, tombe face aux armées réunies d'Oda. Ses ruines préservent pour l'éternité le raffinement suprême de la culture provinciale du Sengoku.",
                'image_url' => "/public/assets/events/event_week_09.jpg",
                'reward_final_gold' => 70,
                'reward_final_honor' => 135,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Chroniqueur d'Echizen",
                'prompt' => "Scenic panoramic view of feudal valley estate in Ichijōdani, traditional Edo-precursor wooden samurai minka residences, koi pond garden, blooming hydrangeas and bamboo groves bordered by misty green hills, Travian and Grepolis hand-drawn vector art aesthetic, warm radiant summer light, serene architectural beauty, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Argile des Tuiles Nobles",
                        'description' => "Amasser 2 000 unités d'Argile de Céramique pour restaurer les demeures.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 2000,
                        'reward_metal' => 1000,
                        'reward_gold' => 8,
                        'reward_honor' => 25
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Fosse d'Argile Rurale",
                        'description' => "Porter la Fosse d'Argile au Niveau 3 ou plus.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "fosse_argile",
                        'target_amount' => 3,
                        'reward_crystal' => 1200,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Thé des Érudits",
                        'description' => "Posséder au moins 1 500 unités de Thé Vert en réserve.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 1500,
                        'reward_gold' => 18,
                        'reward_honor' => 45,
                        'reward_item' => 'sake:20'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 10 : LE SIÈGE D'ODANI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 10,
                'slug' => 'semaine_10_odani_1573',
                'title' => "Le Siège du Nid d'Aigle d'Odani",
                'jp_title' => "小谷城の戦い (Odani-jō no Tatakai)",
                'era_year' => "Septembre 1573 — Forteresse d'Odani, Lac Biwa",
                'description' => "Construit sur la crête escarpée du mont Odani dominant les eaux bleues du lac Biwa, le château d'Azai Nagamasa soutient un siège désespéré. Avant la chute finale, Oichi et ses trois filles (les légendaires trois sœurs Azai) sont escortées hors des remparts en sécurité.",
                'image_url' => "/public/assets/events/event_week_10.jpg",
                'reward_final_gold' => 75,
                'reward_final_honor' => 150,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Héros d'Odani",
                'prompt' => "Panoramic mountain ridge fortress in feudal Japan overlooking Lake Biwa, massive timber ramparts and curved tiled roofs along steep green ridgeline, morning sun reflecting in the lake water far below, samurai guards watching from wooden watchtowers, hand-painted digital strategy art, rich warm colors and dramatic heights, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de Haute Montagne",
                        'description' => "Acheminer 3 500 unités de Riz Impérial sur la crête.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 3500,
                        'reward_metal' => 1500,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Rizière Inondée",
                        'description' => "Développer la Rizière Inondée au Niveau 3 ou plus pour pérenniser les récoltes.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "riziere",
                        'target_amount' => 3,
                        'reward_crystal' => 1200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde du Serment",
                        'description' => "Former et entretenir 35 soldats prêts à défendre le seigneur.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 20,
                        'reward_honor' => 50,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 11 : LA SALVE DE NAGASHINO
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 11,
                'slug' => 'semaine_11_nagashino_1575',
                'title' => "La Salve Roulante de Nagashino",
                'jp_title' => "長篠の合戦 (Nagashino no Kassen)",
                'era_year' => "Mai 1575 — Plaine de Shitarabara, Mikawa",
                'description' => "Face à la charge réputée irrésistible de la cavalerie de Takeda Katsuyori, Nobunaga et Ieyasu érigent des palissades de bois successives le long d'un ruisseau boueux. 3 000 arquebusiers Tanegashima disposés en trois rangs tirent en salves alternées continues, révolutionnant pour toujours l'art de la guerre féodale.",
                'image_url' => "/public/assets/events/event_week_11.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Maître des Mousquets",
                'prompt' => "Panoramic battlefield in feudal Japan with wooden bamboo barricades across rolling grassy hills, lines of disciplined ashigaru arquebusiers firing matchlock volleys in unison, plumes of white gunpowder smoke rising in afternoon light, charging samurai cavalry faltering in muddy stream, Grepolis Total War artwork, high details, 16:9 --no text, modern weapons",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Poutres de Palissade",
                        'description' => "Fournir au moins 40 Poutres de Bois pour dresser les défenses anti-cavalerie.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 40,
                        'reward_metal' => 1800,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Poudre et le Plomb",
                        'description' => "Amasser 4 000 unités de Métal pour couler les munitions des mousquets.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 4000,
                        'reward_deuterium' => 1800,
                        'reward_gold' => 15,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Régiment des Trois Salves",
                        'description' => "Mobiliser un détachement de 40 guerriers et tireurs entraînés.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 25,
                        'reward_honor' => 65,
                        'reward_item' => 'wooden_beams:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 12 : L'ESSOR DU CHÂTEAU D'AZUCHI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 12,
                'slug' => 'semaine_12_azuchi_1576',
                'title' => "L'Élévation de la Splendeur d'Azuchi",
                'jp_title' => "安土城の築城 (Azuchi-jō no Chikujō)",
                'era_year' => "1576 — Rivages du Lac Biwa, Azuchi",
                'description' => "Sur une colline surplombant le lac Biwa, Nobunaga fait ériger un palais-forteresse révolutionnaire de sept étages, orné de laques écarlates et de toits d'or pur peints par l'école Kanō. Azuchi devient le symbole éclatant de l'unification impériale et du prestige cosmique du Shōgunat.",
                'image_url' => "/public/assets/events/event_week_12.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Bâtisseur d'Azuchi",
                'prompt' => "Magnificent grand Japanese castle keep Azuchi Tenshu rising majestically into blue skies, seven-tiered octagonal towers decorated with scarlet red lacquer, golden roof dragons shachihoko shining in sunlight, stone cyclopean foundation, cherry trees in bloom, view of calm Lake Biwa in background, prestigious Grepolis Travian concept art style, 16:9 widescreen --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Granits Cyclopéens",
                        'description' => "Amasser 4 000 unités de Granit pour tailler les fondations monumentales.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 4000,
                        'reward_metal' => 2000,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Apogée du Tenshu",
                        'description' => "Élever le Donjon Tenshu au Niveau 4 pour abriter les réceptions seigneuriales.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 4,
                        'reward_deuterium' => 2000,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Tuiles d'Argile Dorée",
                        'description' => "Stocker 2 500 unités d'Argile de Céramique pour cuire les tuiles incombustibles.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 2500,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 13 : LES BATAILLES NAVALES DE KIZUGAWAGUCHI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 13,
                'slug' => 'semaine_13_kizugawaguchi_1576',
                'title' => "Les Vaisseaux de Fer de Kizugawaguchi",
                'jp_title' => "木津川口の戦い (Kizugawaguchi no Tatakai)",
                'era_year' => "1576-1578 — Baie d'Osaka",
                'description' => "Pour briser le blocus naval et ravitailler les moines d'Ishiyama Hongan-ji, Nobunaga fait concevoir par Kuki Yoshitaka six navires de guerre géants Atakebune blindés de plaques de fer et armés de canons, défiant la flotte pirate légendaire des Mōri.",
                'image_url' => "/public/assets/events/event_week_13.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 160,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Amiral du Shogunat",
                'prompt' => "Panoramic medieval naval battle in coastal Japan bay, massive wooden armored Atakebune warships with iron plate shielding firing bronze cannons, billowing black powder smoke over calm sea, traditional Japanese oars and heraldic naval banners, Travian Grepolis naval strategy painting, 16:9 --no modern steamships, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Fer des Blindages Navals",
                        'description' => "Fournir au moins 4 500 unités de Métal pour forger les plaques défensives.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 4500,
                        'reward_crystal' => 1500,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Quilles de Chêne",
                        'description' => "Réunir 40 Poutres de Bois équarri pour armer les coques.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 40,
                        'reward_deuterium' => 1500,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Équipage de Bord",
                        'description' => "Mobiliser au moins 35 marins et guerriers de garnison.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 24,
                        'reward_honor' => 60,
                        'reward_item' => 'sake:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 14 : L'ÉDIT DU RAKUICHI RAKUZA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 14,
                'slug' => 'semaine_14_rakuichi_rakuza_1577',
                'title' => "L'Édit du Rakuichi Rakuza",
                'jp_title' => "楽市楽座令 (Rakuichi Rakuza-rei)",
                'era_year' => "1577 — Azuchi et Province d'Ōmi",
                'description' => "Pour dynamiser l'économie et briser les monopoles oppressants des corporations féodales (Za), le Shogunat décrète des marchés entièrement libres et affranchis de taxes d'entrée. Marchands d'épices, potiers, brasseurs de saké et armuriers affluent vers la cité.",
                'image_url' => "/public/assets/events/event_week_14.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 150,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Bienfaiteur des Foires",
                'prompt' => "Bustling lively medieval market square in castle town of feudal Japan, wooden merchant stalls laden with colorful textiles, ceramic pottery, rice sacks, barrels of sake, lanterns hanging under wooden shop eaves, merchants and peasants trading happily, distant samurai patrol, warm cheerful afternoon lighting, Grepolis city lifestyle concept art, 16:9 --no text, modern clothing, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Greniers de Soja et Miso",
                        'description' => "Stocker au moins 2 000 unités de Soja des Moissons pour nourrir la foire.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 2000,
                        'reward_metal' => 1200,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Halle du Marché",
                        'description' => "Ériger ou développer le Marché urbain au Niveau 2 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "market",
                        'target_amount' => 2,
                        'reward_crystal' => 1500,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Barriques de Célébration",
                        'description' => "Fournir au moins 50 barriques de Saké Raffiné pour célébrer l'inauguration.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 50,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 15 : LE CHOC DE TEDORIGAWA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 15,
                'slug' => 'semaine_15_tedorigawa_1577',
                'title' => "La Crue Sanglante de Tedorigawa",
                'jp_title' => "手取川の戦い (Tedorigawa no Tatakai)",
                'era_year' => "Novembre 1577 — Fleuve Tedori, Kaga",
                'description' => "Uesugi Kenshin, le redoutable Dragon d'Echigo, tend un piège magistral aux armées d'Oda en feignant la retraite au-delà du fleuve Tedori en crue. Lorsque les assaillants franchissent les flots glacés, Kenshin fait ouvrir les vannes et lance une contre-charge dévastatrice.",
                'image_url' => "/public/assets/events/event_week_15.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 160,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Résistant de Tedori",
                'prompt' => "Dramatic twilight river battle in autumn feudal Japan, rushing swollen river under stormy dusk sky, Uesugi Kenshin's cavalry in distinctive white hoods charging through shallow foaming rapids, fallen banners in rushing water, epic painterly historical RTS game artwork, cinematic orange and indigo lighting, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de Marche Forcée",
                        'description' => "Amasser 4 000 unités de Riz Impérial pour approvisionner le front.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 4000,
                        'reward_metal' => 1600,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Martiale du Héros",
                        'description' => "Élever la puissance martiale de votre Héros à au moins 220.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 220,
                        'reward_crystal' => 1400,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Avant-Garde Résistante",
                        'description' => "Mobiliser au moins 40 guerriers réguliers sous vos ordres.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 24,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 16 : L'OMBRE DES SHINOBIS D'IGA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 16,
                'slug' => 'semaine_16_iga_1579',
                'title' => "L'Ombre des Shinobis d'Iga",
                'jp_title' => "天正伊賀の乱 (Tenshō Iga no Ran)",
                'era_year' => "1579-1581 — Montagnes d'Iga et de Kōka",
                'description' => "Nichés au cœur de ravins sauvages et de forêts denses de bambous, les clans autonomes de shinobis d'Iga défient les invasions grâce à leur art consommé de l'embuscade nocturne, des poudres aveuglantes et de la guerre psychologique.",
                'image_url' => "/public/assets/events/event_week_16.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Ombre Silencieuse d'Iga",
                'prompt' => "Mysterious moonlit bamboo forest in feudal Japan, hooded shinobi ninja scouts silently leaping between tall bamboo stalks, concealed wooden mountain hideout with dim paper lanterns, misty mountain ravine, full moon casting silver light through bamboo canopy, atmospheric Japanese folklore illustration style, Travian aesthetic, 16:9 --no text, modern elements, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Bois des Arcs et Flèches",
                        'description' => "Récolter au moins 4 500 unités de Bois de Cèdre dans les forêts du domaine.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 4500,
                        'reward_crystal' => 1500,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Tour de Guet",
                        'description' => "Développer le Radar / Tour de Guet urbaine au Niveau 2 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "radar",
                        'target_amount' => 2,
                        'reward_deuterium' => 1500,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Éclaireurs de Nuit",
                        'description' => "Former au moins 35 fantassins et tireurs d'élite.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 25,
                        'reward_honor' => 65,
                        'reward_item' => 'rice_flour:35'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 17 : LE SIÈGE D'ITAMI ET LE CONSEILLER HANBEI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 17,
                'slug' => 'semaine_17_itami_1579',
                'title' => "Le Siège d'Itami & la Sagesse d'Hanbei",
                'jp_title' => "有岡城の戦い (Arioka-jō no Tatakai)",
                'era_year' => "1579 — Château d'Arioka (Itami), Province de Settsu",
                'description' => "Le seigneur rebelle Araki Murashige s'enferme dans la puissante forteresse d'Arioka. L'envoyé diplomatique Kuroda Kanbei y est jeté dans un cachot sombre, tandis que le stratège légendaire Takenaka Hanbei sauve secrètement le fils de son ami avant de s'éteindre avec bravoure.",
                'image_url' => "/public/assets/events/event_week_17.jpg",
                'reward_final_gold' => 75,
                'reward_final_honor' => 145,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Stratège Éclairé",
                'prompt' => "Panoramic feudal Japanese castle siege camp at sunset, seasoned tactician daimyo in ornate robes examining tactical battle maps inside open wooden war pavilion with hanging banners, distant fortified castle surrounded by earthen ramparts and moat, warm glowing sunset sky, painterly Travian strategy art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Ravitaillement du Siège",
                        'description' => "Amasser 4 000 unités de Riz Impérial pour maintenir l'encerclement.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 4000,
                        'reward_metal' => 1600,
                        'reward_gold' => 10,
                        'reward_honor' => 30
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Conseil de Guerre",
                        'description' => "Élever le Donjon Tenshu au Niveau 3 ou plus pour diriger les manœuvres.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 3,
                        'reward_crystal' => 1500,
                        'reward_gold' => 15,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Troupes de Ligne",
                        'description' => "Mobiliser au moins 35 fantassins disciplinés.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 22,
                        'reward_honor' => 55,
                        'reward_item' => 'wooden_beams:25'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 18 : LES SOIES ET ARTISANS DE NISHIJIN
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 18,
                'slug' => 'semaine_18_nishijin_1580',
                'title' => "L'Éclat des Soies de Nishijin",
                'jp_title' => "西陣織の復興 (Nishijin-ori no Fukkō)",
                'era_year' => "1580 — Quartier de Nishijin, Kyoto",
                'description' => "Après les ravages des guerres civiles, les maîtres tisserands de Kyoto se rassemblent dans le camp ouest (Nishijin) pour faire revivre l'art somptueux des soieries brodées d'or et d'argent destinées aux kimonos impériaux et aux dais seigneuriaux.",
                'image_url' => "/public/assets/events/event_week_18.jpg",
                'reward_final_gold' => 70,
                'reward_final_honor' => 140,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Tisserand d'Or",
                'prompt' => "Artisan workshop in feudal Kyoto, traditional wooden looms with master weavers crafting luxurious shimmering gold and silk brocade textiles, sunbeams streaming through paper shoji screens, bolts of richly patterned obi fabric, traditional tools, tranquil and detailed Japanese craft aesthetic, Grepolis Travian style, 16:9 --no text, modern machines, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Argile des Teinturiers",
                        'description' => "Stocker au moins 2 500 unités d'Argile pour les cuves de teinture végétale.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 2500,
                        'reward_metal' => 1200,
                        'reward_gold' => 10,
                        'reward_honor' => 25
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Thé des Ateliers",
                        'description' => "Amasser 2 000 unités de Thé Vert pour stimuler les artisans.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 2000,
                        'reward_crystal' => 1500,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Étoffes d'Honneur",
                        'description' => "Posséder au moins 40 Barriques de Saké et 30 Sacs de Farine de Riz.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 40,
                        'reward_gold' => 20,
                        'reward_honor' => 50,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 19 : LE SIÈGE DE TOTTORI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 19,
                'slug' => 'semaine_19_tottori_1581',
                'title' => "Le Siège Affamé de Tottori",
                'jp_title' => "鳥取城の兵糧攻め (Tottori-jō no Hyōrōzeme)",
                'era_year' => "1581 — Province d'Inaba",
                'description' => "Maître de la guerre économique et logistique, Hideyoshi achète discrètement à prix d'or tout le riz disponible dans la province d'Inaba plusieurs mois avant le siège. Lorsque Tottori est investie, ses greniers sont vides, contraignant la garnison à capituler sans combat sanglant inutile.",
                'image_url' => "/public/assets/events/event_week_19.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 155,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Maître de Logistique",
                'prompt' => "Panoramic medieval fortified castle Tottori in feudal Japan surrounded by autumn hills and sand dunes of Inaba, samurai supply wagons and cargo carts filled with wooden rice barrels moving along fortified road, crisp clear autumn atmosphere, painterly vector illustration of Travian, high historical fidelity, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Greniers Pleins de Riz",
                        'description' => "Constituer une réserve stratégique de 4 500 unités de Riz Impérial.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 4500,
                        'reward_metal' => 1800,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Réserves de Soja",
                        'description' => "Amasser au moins 2 500 unités de Soja des Moissons.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 2500,
                        'reward_crystal' => 1500,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Armée Déployée",
                        'description' => "Maintenir au moins 40 soldats sous les drapeaux.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 22,
                        'reward_honor' => 55,
                        'reward_item' => 'rice_flour:35'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 20 : L'INONDATION DE TAKAMATSU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 20,
                'slug' => 'semaine_20_takamatsu_1582',
                'title' => "L'Inondation de Takamatsu",
                'jp_title' => "備中高松城の水攻め (Bitchū Takamatsu-jō no Mizuzeme)",
                'era_year' => "Mai 1582 — Bitchū Takamatsu",
                'description' => "Face à la forteresse de plaine marécageuse de Bitchū Takamatsu, Hideyoshi fait ériger une digue titanesque de 3 kilomètres en douze jours seulement. Détournant les eaux de la rivière Ashimori gonflée par les pluies de mousson, il transforme le château en une île flottante isolée.",
                'image_url' => "/public/assets/events/event_week_20.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 165,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Génie des Eaux",
                'prompt' => "Panoramic bird's eye view of water siege in feudal Japan, wooden Japanese castle surrounded by vast flooded plains like a solitary island in a mirror lake, engineering workers on large earthen dykes in rainy monsoon season, distant mountains under stormy gray skies, ukiyo-e inspired Travian digital art, grand scale, 16:9 --no modern dams, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : La Terre et le Granit de la Digue",
                        'description' => "Amasser 4 500 unités de Granit pour ériger le rempart hydraulique.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 4500,
                        'reward_metal' => 2000,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Poutres de Soutènement",
                        'description' => "Fournir 45 Poutres de Bois équarri pour consolider l'ouvrage.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 45,
                        'reward_deuterium' => 2000,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Troupes de Blocus",
                        'description' => "Mobiliser au moins 40 fantassins pour garder la digue.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'sake:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 21 : L'EMBRASEMENT DU HONNŌ-JI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 21,
                'slug' => 'semaine_21_honnoji_1582',
                'title' => "L'Embrasement du Honnō-ji",
                'jp_title' => "本能寺の変 (Honnō-ji no Hen)",
                'era_year' => "21 Juin 1582 — Temple Honnō-ji, Kyoto",
                'description' => "Dans l'aube brumeuse de Kyoto, le général Akechi Mitsuhide détourne ses troupes en lançant le cri funeste : 'L'ennemi est au Honnō-ji !'. Encerclé dans le temple en flammes avec une poignée de gardes du corps dévoués, Oda Nobunaga périt héroïquement, bouleversant à jamais le destin de l'archipel.",
                'image_url' => "/public/assets/events/event_week_21.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Témoin du Honnō-ji",
                'prompt' => "Dramatic nocturnal panoramic scene in feudal Kyoto, traditional Buddhist temple courtyard at Honnō-ji engulfed in dramatic golden fire and smoke, brave samurai archers and halberdiers defending wooden temple gate under night sky with sparks flying, dramatic cinematic lighting, rich tragic atmosphere, Travian Grepolis art style, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Alerte et les Armes",
                        'description' => "Forger au moins 5 000 unités de Métal pour armer la garde d'élite.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 5000,
                        'reward_crystal' => 1800,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Suprême du Héros",
                        'description' => "Élever la puissance martiale de votre Héros Samouraï à au moins 250.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 250,
                        'reward_deuterium' => 2000,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Derniers Fidèles",
                        'description' => "Maintenir au moins 45 soldats prêts à braver les flammes.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 28,
                        'reward_honor' => 70,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 22 : LA GRANDE CHEVAUCHÉE DU CHŪGOKU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 22,
                'slug' => 'semaine_22_chugoku_oogaeshi_1582',
                'title' => "La Grande Chevauchée du Chūgoku",
                'jp_title' => "中国大返し (Chūgoku Ōgaeshi)",
                'era_year' => "Juin 1582 — Route de San'yōdō",
                'description' => "Apprenant la mort de Nobunaga alors qu'il est en campagne à l'ouest, Hideyoshi conclut une trêve secrète immédiate avec les Mōri et lance une marche forcée légendaire : 200 kilomètres franchis en sept jours avec 25 000 hommes, changeant chevaux et torches au pas de course.",
                'image_url' => "/public/assets/events/event_week_22.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 165,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Cavalier du Chūgoku",
                'prompt' => "Panoramic landscape of an army on forced march in feudal Japan, long column of samurai cavalry and infantry hurrying along coastal mountain highway at sunset, fluttering war banners, torches beginning to light as twilight deepens, dynamic sense of motion and urgency, Grepolis and Shogun Total War artwork, vibrant sky, 16:9 --no text, modern vehicles",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Rations de Course",
                        'description' => "Acheminer 4 500 unités de Riz Impérial pour ravitailler les relais équestres.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 4500,
                        'reward_metal' => 1800,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Domaine des Moissons",
                        'description' => "Récolter au moins 2 500 unités de Soja des Moissons.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 2500,
                        'reward_crystal' => 1600,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Avant-Garde Équestre",
                        'description' => "Mobiliser au moins 40 guerriers pour ouvrir la route.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 40,
                        'reward_gold' => 24,
                        'reward_honor' => 60,
                        'reward_item' => 'wooden_beams:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 23 : LE JUGEMENT DE YAMAZAKI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 23,
                'slug' => 'semaine_23_yamazaki_1582',
                'title' => "Le Jugement de Yamazaki",
                'jp_title' => "山崎の戦い (Yamazaki no Tatakai)",
                'era_year' => "2 Juillet 1582 — Mont Tennōzan, Yamazaki",
                'description' => "Aux abords de la colline stratégique du mont Tennōzan, les armées d'Akechi Mitsuhide et de Toyotomi Hideyoshi s'affrontent pour venger Oda Nobunaga. La prise éclair des hauteurs par l'avant-garde d'Hideyoshi scelle la défaite de Mitsuhide, dont le règne n'aura duré que treize jours.",
                'image_url' => "/public/assets/events/event_week_23.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Vengeur de Yamazaki",
                'prompt' => "Epic medieval battle on the slopes of Mount Tennozan in feudal Japan, decisive clash between samurai armies, banners of bellflower mon vs golden gourd standards, sweeping panoramic view over misty Yodo river valley, dramatic morning lighting, fine hand-painted game illustration style, rich detail, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Fer du Châtiment",
                        'description' => "Amasser 5 000 unités de Métal pour équiper le bataillon de vengeance.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 5000,
                        'reward_crystal' => 2000,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Crête du Tennōzan",
                        'description' => "Élever le Donjon Tenshu au Niveau 3 ou plus pour coordonner les colonnes.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 3,
                        'reward_deuterium' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Régiment Vainqueur",
                        'description' => "Déployer au moins 45 soldats et archers au sommet.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 30,
                        'reward_honor' => 70,
                        'reward_item' => 'rice_flour:45'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 24 : LA CONFÉRENCE DE KIYOSU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 24,
                'slug' => 'semaine_24_kiyosu_1582',
                'title' => "La Grande Conférence de Kiyosu",
                'jp_title' => "清洲会議 (Kiyosu Kaigi)",
                'era_year' => "Juillet 1582 — Château de Kiyosu, Owari",
                'description' => "Les quatre grands généraux survivants du clan Oda se réunissent au château de Kiyosu pour désigner l'héritier du royaume et partager les provinces. Par une manœuvre diplomatique brillante, Hideyoshi présente l'enfant Hidenobu et s'impose comme régent souverain.",
                'image_url' => "/public/assets/events/event_week_24.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 150,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Arbitre de Kiyosu",
                'prompt' => "Solemn interior hall of feudal Japanese castle palace, four powerful daimyos in opulent silk ceremonial kamishimo sitting on tatami mats before golden folding byobu screens, golden incense burners emitting fragrant smoke, samurai bodyguards waiting silently outside open shoji doors, rich warm candlelight, Grepolis cultural illustration, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Saké de Paix",
                        'description' => "Fournir au moins 50 barriques de Saké Raffiné pour honorer l'assemblée.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 50,
                        'reward_metal' => 1500,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Pavillon Cérémonial",
                        'description' => "Amasser 2 500 unités de Thé Vert pour les maîtres de cérémonie.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 2500,
                        'reward_crystal' => 1600,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde d'Apparat",
                        'description' => "Disposer d'au moins 35 guerriers montés et fantassins d'élite.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 35,
                        'reward_gold' => 22,
                        'reward_honor' => 55,
                        'reward_item' => 'rice_flour:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 25 : LA VICTOIRE DE SHIZUGATAKE
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 25,
                'slug' => 'semaine_25_shizugatake_1583',
                'title' => "Le Choc de Shizugatake",
                'jp_title' => "賤ヶ岳の戦い (Shizugatake no Tatakai)",
                'era_year' => "Mai 1583 — Mont Shizugatake, Lac Yogo",
                'description' => "Shibata Katsuie défie l'hégémonie de Hideyoshi sur les crêtes escarpées surplombant le lac Yogo. Hideyoshi réalise une nouvelle chevauchée éclair depuis Ōgaki et prend l'armée de Shibata à revers dans un affrontement titanesque.",
                'image_url' => "/public/assets/events/event_week_25.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Héros de Shizugatake",
                'prompt' => "Dramatic mountain ridge battle above Lake Yogo in feudal Japan, fierce hand-to-hand samurai infantry combat along narrow fortified crest, banners fluttering in morning alpine wind, serene mirror lake reflecting peaks below, dynamic high-angle tactical perspective, Total War Shogun and Travian digital painting, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Riz de Crête",
                        'description' => "Approvisionner 4 500 unités de Riz Impérial pour les bastions montagnards.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 4500,
                        'reward_metal' => 1800,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force du Héros",
                        'description' => "Élever la puissance martiale de votre Héros à au moins 240.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 240,
                        'reward_crystal' => 1600,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Bataillon de Percée",
                        'description' => "Mobiliser au moins 45 soldats pour briser la ligne adverse.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 26,
                        'reward_honor' => 65,
                        'reward_item' => 'wooden_beams:30'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 26 : LES SEPT LANCES DE SHIZUGATAKE
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 26,
                'slug' => 'semaine_26_shichihon_yari_1583',
                'title' => "Les Sept Lances Légendaires",
                'jp_title' => "賤ヶ岳の七本槍 (Shizugatake no Shichihon-yari)",
                'era_year' => "1583 — Ordre de Chevalerie du Régent",
                'description' => "Sept jeunes gardes du corps téméraires de Hideyoshi (Katō Kiyomasa, Fukushima Masanori, Katō Yoshiaki...) s'illustrent par des prouesses héroïques à la pointe de leurs lances Yari. Ils deviennent les Sept Lances de Shizugatake, futurs bâtisseurs de châteaux et gouverneurs de provinces.",
                'image_url' => "/public/assets/events/event_week_26.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 175,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Grande Lance du Shogunat",
                'prompt' => "Seven young elite samurai champions standing proudly in formation on cliff edge at sunrise, holding long ceremonial Yari spears adorned with golden tassels, distinctive ornate helmets kabuto, colorful battle sashes, morning sun rising behind them over Japanese valley, Grepolis Travian hero showcase vector painting, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Acier des Pointes de Lance",
                        'description' => "Amasser 5 000 unités de Métal pour forger les fers trempés.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 5000,
                        'reward_crystal' => 2000,
                        'reward_gold' => 14,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Compagnie des Lanciers",
                        'description' => "Entraîner et maintenir au moins 35 Lanciers Yari d'élite.",
                        'task_type' => "train_troops",
                        'target_key' => "spearman",
                        'target_amount' => 35,
                        'reward_deuterium' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Armure d'Honneur",
                        'description' => "Déployer au moins 50 soldats combinés sous vos étendards.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 30,
                        'reward_honor' => 70,
                        'reward_item' => 'rice_flour:45'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 27 : L'ÉDIFICATION DU CHÂTEAU D'OSAKA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 27,
                'slug' => 'semaine_27_osaka_castle_1583',
                'title' => "La Citadelle Dorée d'Osaka",
                'jp_title' => "大坂城築城 (Ōsaka-jō no Chikujō)",
                'era_year' => "1583 — Province de Settsu, Osaka",
                'description' => "Sur l'ancien site imprenable d'Ishiyama Hongan-ji, Toyotomi Hideyoshi mobilise des dizaines de milliers d'ouvriers et de daimyōs pour bâtir la plus grande forteresse du Japon : le château d'Osaka, avec ses douves gigantesques de 70 mètres de large et ses blocs de granit de plus de 100 tonnes.",
                'image_url' => "/public/assets/events/event_week_27.jpg",
                'reward_final_gold' => 95,
                'reward_final_honor' => 190,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Maître Bâtisseur d'Osaka",
                'prompt' => "Grand monumental panoramic view of Osaka Castle Tenshu keep under brilliant blue daylight sky, massive cyclopean stone walls rising directly out of wide reflective moat, gold leaf ornaments and black lacquered walls, cherry blossom trees along moat banks, traditional wooden punts on water, Travian and Grepolis epic castle concept artwork, 16:9 --no text, modern buildings",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Mégalithes de Granit",
                        'description' => "Rassembler 5 000 unités de Granit pour tailler les blocs de la muraille.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 5000,
                        'reward_metal' => 2200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Donjon Tenshu Souverain",
                        'description' => "Élever le Donjon Tenshu au Niveau 4 ou supérieur.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 4,
                        'reward_deuterium' => 2500,
                        'reward_gold' => 22,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Argile Imperméable des Douves",
                        'description' => "Stocker 3 000 unités d'Argile de Céramique pour sceller les berges.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 3000,
                        'reward_gold' => 30,
                        'reward_honor' => 70,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 28 : L'ÉCHIQUIER DE KOMAKI-NAGAKUTE
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 28,
                'slug' => 'semaine_28_komaki_nagakute_1584',
                'title' => "L'Échiquier de Komaki-Nagakute",
                'jp_title' => "小牧・長久手の戦い (Komaki-Nagakute no Tatakai)",
                'era_year' => "1584 — Owari et Mikawa",
                'description' => "Tokugawa Ieyasu et Toyotomi Hideyoshi s'affrontent dans une partie d'échecs géante d'observation, de tranchées et de redoutes retranchées à Komaki. Bien que Ieyasu l'emporte tactiquement à Nagakute, Hideyoshi conclut la paix par un mariage habile, unissant les deux géants.",
                'image_url' => "/public/assets/events/event_week_28.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 165,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Tactique de Nagakute",
                'prompt' => "Panoramic tactical battlefield in feudal Japan with intricate wooden field fortifications, trenches, earthen redoubts on rolling green hills, samurai officers with war fans gunbai directing maneuvers under bright summer sun, banner standards planted on hilltops, Grepolis military strategy vector art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Bois de Retranchement",
                        'description' => "Fournir 50 Poutres de Bois équarri pour dresser les redoutes de campagne.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 50,
                        'reward_metal' => 2000,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Martiale du Héros",
                        'description' => "Élever la puissance martiale de votre Samouraï Héros à au moins 250.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 250,
                        'reward_crystal' => 1800,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde d'Échiquier",
                        'description' => "Mobiliser au moins 45 soldats prêts à repousser toute incursion.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 28,
                        'reward_honor' => 65,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 29 : LA PACIFICATION DE SHIKOKU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 29,
                'slug' => 'semaine_29_shikoku_1585',
                'title' => "La Pacification de Shikoku",
                'jp_title' => "四国征伐 (Shikoku Seibatsu)",
                'era_year' => "1585 — Île de Shikoku",
                'description' => "Chōsokabe Motochika, le seigneur unificateur de Shikoku, voit débarquer une armada de 100 000 guerriers conduits par les généraux de Hideyoshi. Après des combats vaillants dans les défilés côtiers, Motochika accepte de se soumettre et conserve sa province ancestrale de Tosa.",
                'image_url' => "/public/assets/events/event_week_29.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 160,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Seigneur des Quatre Provinces",
                'prompt' => "Panoramic coastal landing of a massive feudal Japanese fleet on the rugged coastline of Shikoku, hundreds of wooden transport barges and samurai war junks, troops assembling on pebble beach, pine trees on sea cliffs, emerald green ocean water, bright summer sky, Total War and Travian historical game art, 16:9 --no modern ships, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Ravitaillement des Flottes",
                        'description' => "Amasser 5 000 unités de Riz Impérial pour approvisionner les barges de mer.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 5000,
                        'reward_metal' => 2000,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Pêche et le Sel marin",
                        'description' => "Stocker au moins 2 500 unités de Soja des Moissons.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 2500,
                        'reward_crystal' => 1800,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Corps Débarqué",
                        'description' => "Mobiliser au moins 45 soldats et archers d'assaut.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 26,
                        'reward_honor' => 60,
                        'reward_item' => 'sake:35'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 30 : LA GRANDE CHASSE AUX ÉPÉES (KATANAGARI)
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 30,
                'slug' => 'semaine_30_katanagari_1588',
                'title' => "L'Édit de la Chasse aux Épées",
                'jp_title' => "刀狩令 (Katanagari-rei)",
                'era_year' => "Août 1588 — Édit Impérial de Kyoto",
                'description' => "Pour désarmer les révoltes paysannes (Ikkō-ikki) et consacrer la séparation stricte entre guerriers et paysans, Hideyoshi ordonne la confiscation de toutes les épées, lances et fusils des civils. Le fer collecté est solennellement fondu pour forger les clous et statues du Grand Bouddha de Kyoto.",
                'image_url' => "/public/assets/events/event_week_30.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Garant de l'Ordre Impérial",
                'prompt' => "Historic village square scene in feudal Japan, samurai officials in formal armor supervising peasants turning in katanas, naginata spears and arquebuses piled into wooden oxcarts, traditional minka thatched houses, cherry blossom trees, village elders bowing respectfully, Grepolis Travian storytelling concept art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Fer Réquisitionné",
                        'description' => "Amasser 6 000 unités de Métal pour les fonderies impériales.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 6000,
                        'reward_crystal' => 2200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Paix des Rizières",
                        'description' => "Développer la Rizière Inondée rurale au Niveau 4 ou plus.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "riziere",
                        'target_amount' => 4,
                        'reward_deuterium' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde de Sécurité",
                        'description' => "Maintenir au moins 45 soldats réguliers pour faire respecter l'édit.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 28,
                        'reward_honor' => 65,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 31 : LA GRANDE CÉRÉMONIE DE THÉ DE KITANO
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 31,
                'slug' => 'semaine_31_kitano_cha_1587',
                'title' => "La Grande Cérémonie de Kitano",
                'jp_title' => "北野大茶湯 (Kitano Ōchanoyu)",
                'era_year' => "Octobre 1587 — Sanctuaire Kitano Tenmangū, Kyoto",
                'description' => "Hideyoshi invite l'archipel entier — du plus illustre seigneur de guerre au plus modeste paysan — à une immense fête du thé en plein air dans le parc sacré de Kitano Tenmangū. Plus de 800 pavillons éphémères sont dressés sous les pins et les érables vermillons.",
                'image_url' => "/public/assets/events/event_week_31.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 160,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Hôte de Kitano",
                'prompt' => "Grand outdoor tea ceremony festival at Kitano Tenmangu shrine in Kyoto, hundreds of open-air wooden tea booths and scarlet red parasols arranged under autumn maple and pine groves, Torii gates, stone lanterns toro, diverse people from samurai to peasants sipping tea peacefully, radiant golden afternoon light, Travian Grepolis celebration art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Feuilles Précieuses",
                        'description' => "Amasser au moins 2 500 unités de Thé Vert pour les milliers de convives.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 2500,
                        'reward_metal' => 1500,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Argile des Chawan d'Or",
                        'description' => "Stocker 3 000 unités d'Argile de Céramique pour cuire les bols de thé.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 3000,
                        'reward_crystal' => 1800,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Pavillon de Dégustation",
                        'description' => "Fournir 60 Barriques de Saké Raffiné et 40 Sacs de Farine de Riz.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 60,
                        'reward_gold' => 26,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 32 : LA CAMPAGNE DE KYŪSHŪ
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 32,
                'slug' => 'semaine_32_kyushu_1587',
                'title' => "La Pacification de Kyūshū",
                'jp_title' => "九州征伐 (Kyūshū Seibatsu)",
                'era_year' => "1587 — Île de Kyūshū (Chikuzen & Satsuma)",
                'description' => "Pour secourir le clan Ōtomo assiégé par les impétueux frères Shimazu de Satsuma, Hideyoshi conduit une colossale armée impériale à Kyūshū. Impressionné par la discipline militaire des Shimazu, il leur pardonne après leur reddition et confirme leur autorité sur Satsuma et Ōsumi.",
                'image_url' => "/public/assets/events/event_week_32.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Vainqueur de Kyūshū",
                'prompt' => "Panoramic volcanic landscape in southern Kyushu in feudal Japan, imposing active volcano Sakurajima puffing smoke across azure bay, samurai armadas arriving at shoreline, banners of the Toyotomi and Shimazu clans, colorful warrior formations, high-end strategy game illustration, 16:9 --no text, modern ships, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Ravitaillement Maritime",
                        'description' => "Amasser 5 500 unités de Riz Impérial pour le corps expéditionnaire.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 5500,
                        'reward_metal' => 2200,
                        'reward_gold' => 14,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Martiale du Héros",
                        'description' => "Élever la puissance martiale de votre Héros à au moins 260.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 260,
                        'reward_crystal' => 2000,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Contingent Déployé",
                        'description' => "Mobiliser au moins 50 soldats d'élite sous vos ordres.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 28,
                        'reward_honor' => 70,
                        'reward_item' => 'wooden_beams:35'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 33 : LE GRAND ARPENTAGE TAIKŌ KENCHI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 33,
                'slug' => 'semaine_33_taiko_kenchi_1589',
                'title' => "Le Grand Arpentage Taikō Kenchi",
                'jp_title' => "太閤検地 (Taikō Kenchi)",
                'era_year' => "1589-1595 — Dans toutes les 66 provinces",
                'description' => "Hideyoshi uniformise les unités de mesure sur tout l'archipel : taille standard du koku de riz et perche d'arpentage ken. Des inspecteurs arpentent chaque parcelle pour enregistrer précisément la fertilité du sol, garantissant une justice fiscale et une prospérité record pour le pays.",
                'image_url' => "/public/assets/events/event_week_33.jpg",
                'reward_final_gold' => 80,
                'reward_final_honor' => 155,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Arpenteur de l'Empire",
                'prompt' => "Peaceful rural landscape in feudal Japan with agricultural officials and surveyors using wooden measuring poles to survey golden terraced rice paddies, peasants harvesting rice sheaves with sickles, water wheels turning in irrigation canals, distant village hamlet, clear sunny day, Grepolis farming landscape art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Évaluation du Koku de Riz",
                        'description' => "Posséder au moins 6 000 unités de Riz Impérial dans vos silos.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6000,
                        'reward_metal' => 2000,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Parcelle Modèle",
                        'description' => "Élever le Champ de Soja au Niveau 3 ou plus pour certifier sa fertilité.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "champ_soja",
                        'target_amount' => 3,
                        'reward_crystal' => 1800,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Registre Terrien",
                        'description' => "Fournir au moins 45 Sacs de Farine de Riz et 50 barriques de Saké.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 50,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:45'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 34 : LE SIÈGE D'ODAWARA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 34,
                'slug' => 'semaine_34_odawara_1590',
                'title' => "Le Siège Monumental d'Odawara",
                'jp_title' => "小田原征伐 (Odawara Seibatsu)",
                'era_year' => "1590 — Forteresse d'Odawara, Sagami",
                'description' => "Le clan Hōjō s'enferme dans la plus colossale enceinte fortifiée de terre et de fossés du Japon : Odawara, avec ses 9 kilomètres de circonférence. Hideyoshi réunit plus de 200 000 guerriers, transformant le siège en un festival géant avec marchés, théâtres et maisons de thé pour faire plier le moral ennemi.",
                'image_url' => "/public/assets/events/event_week_34.jpg",
                'reward_final_gold' => 95,
                'reward_final_honor' => 190,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Vainqueur d'Odawara",
                'prompt' => "Epic panoramic castle siege at Odawara in feudal Japan, massive concentric earthen ramparts and timber walls surrounded by vast encampment of thousands of samurai pavilions under colorful banners, ocean visible on horizon, majestic Mount Fuji in far distance, rich sunny Travian RTS art style, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Ravitaillement du Grand Siège",
                        'description' => "Amasser 6 500 unités de Riz Impérial pour soutenir le blocus.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6500,
                        'reward_metal' => 2500,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Poutres de Circonvalation",
                        'description' => "Stocker au moins 50 Poutres de Bois équarri pour dresser les palissades.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 50,
                        'reward_crystal' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Armée Impériale Réunie",
                        'description' => "Mobiliser au moins 55 guerriers d'élite sous vos ordres.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 55,
                        'reward_gold' => 32,
                        'reward_honor' => 75,
                        'reward_item' => 'wooden_beams:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 35 : LE CHÂTEAU D'ISHIGAKIYAMA D'UNE NUIT
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 35,
                'slug' => 'semaine_35_ishigakiyama_1590',
                'title' => "Le Château d'Ishigakiyama d'une Nuit",
                'jp_title' => "石垣山一夜城 (Ishigakiyama Ichiya-jō)",
                'era_year' => "Juin 1590 — Mont Ishigaki, face à Odawara",
                'description' => "Sur le mont Ishigaki surplombant directement Odawara, Hideyoshi fait ériger secrètement un château de pierre complet derrière un écran d'arbres touffus. En une seule nuit, tous les arbres sont abattus : au matin, les défenseurs stupéfaits d'Odawara découvrent une citadelle imprenable apparue comme par magie.",
                'image_url' => "/public/assets/events/event_week_35.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Architecte Fantôme",
                'prompt' => "Dramatic reveal of stone mountain castle Ishigakiyama in feudal Japan, morning mist parting on wooded peak revealing newly built multi-tiered fortress overlooking Odawara castle in valley below, golden morning sunrise, autumn leaves falling, astonishment of samurai scouts, Grepolis castle painting style, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Granits de Nuit",
                        'description' => "Fournir 5 000 unités de Granit pour ériger les murailles secrètes.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 5000,
                        'reward_metal' => 2200,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Donjon Révélé",
                        'description' => "Élever le Donjon Tenshu au Niveau 3 ou supérieur.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 3,
                        'reward_deuterium' => 2200,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garnison Surprise",
                        'description' => "Déployer au moins 45 soldats prêts à monter la garde sur le pic.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 45,
                        'reward_gold' => 26,
                        'reward_honor' => 65,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 36 : L'ENTRÉE D'IEYASU DANS LE HAMEAU D'EDO
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 36,
                'slug' => 'semaine_36_edo_nyufu_1590',
                'title' => "L'Entrée des Tokugawa dans le Hameau d'Edo",
                'jp_title' => "徳川家康の江戸入府 (Ieyasu no Edo Nyūfu)",
                'era_year' => "Août 1590 — Baie d'Edo, Plaine du Kantō",
                'description' => "Transféré par Hideyoshi dans la plaine sauvage du Kantō, Tokugawa Ieyasu arrive dans le modeste village de pêcheurs marécageux d'Edo. Avec une vision colossale, il lance le creusement des canaux, l'assèchement des marais et la fondation de ce qui deviendra la plus grande mégapole du monde : Tokyo.",
                'image_url' => "/public/assets/events/event_week_36.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 175,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Fondateur d'Edo",
                'prompt' => "Panoramic landscape of early Edo settlement in 1590 Japan, Tokugawa Ieyasu and retinue arriving at modest wooden coastal castle on grassy bluff overlooking wide bay and marshlands, canals being dug by laborers, fishermen with nets, Mount Fuji majestic in the background, clear golden morning sunlight, Travian Grepolis historical painting, 16:9 --no modern skyscrapers, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Assèchement des Canaux",
                        'description' => "Amasser 5 500 unités de Bois de Cèdre pour construire les quais et ponts.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 5500,
                        'reward_crystal' => 2000,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Expansion du Village",
                        'description' => "Élever le Village Minka rural au Niveau 3 ou plus pour loger les pionniers.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "village",
                        'target_amount' => 3,
                        'reward_deuterium' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Argile des Digues Fluviales",
                        'description' => "Stocker 3 500 unités d'Argile pour endiguer la baie d'Edo.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 3500,
                        'reward_gold' => 28,
                        'reward_honor' => 65,
                        'reward_item' => 'rice_flour:45'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 37 : LA FÊTE DES CERISIERS DE DAIGO
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 37,
                'slug' => 'semaine_37_daigo_hanami_1598',
                'title' => "La Fête des Cerisiers de Daigo",
                'jp_title' => "醍醐の花見 (Daigo no Hanami)",
                'era_year' => "Avril 1598 — Temple Daigo-ji, Kyoto",
                'description' => "Au crépuscule de sa vie, Hideyoshi organise au temple Daigo-ji la plus splendide fête des fleurs de cerisiers (Hanami) de l'histoire du Japon. 700 cerisiers géants sont transplantés spécialement, et plus de 1 300 dames de la cour revêtent des parures de soie pour composer des poèmes waka.",
                'image_url' => "/public/assets/events/event_week_37.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 165,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Poète de Daigo",
                'prompt' => "Spectacular blossoming cherry garden Hanami festival at Daigo-ji temple in Kyoto, thousands of lush pink sakura blossoms drifting in gentle spring breeze, court ladies in gorgeous colorful kimono walking on vermilion wooden bridges over koi ponds, golden tea pavilions, warm spring sunshine, Grepolis poetic cultural illustration, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Thé de Printemps",
                        'description' => "Amasser au moins 3 000 unités de Thé Vert pour les poètes de la cour.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 3000,
                        'reward_metal' => 1800,
                        'reward_gold' => 12,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Douceurs de Riz",
                        'description' => "Fournir au moins 50 Sacs de Farine de Riz pour confectionner les dango de sakura.",
                        'task_type' => "stock_resource",
                        'target_key' => "rice_flour",
                        'target_amount' => 50,
                        'reward_crystal' => 1800,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Saké Impérial",
                        'description' => "Offrir au moins 60 Barriques de Saké Raffiné aux nobles de la cour.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 60,
                        'reward_gold' => 25,
                        'reward_honor' => 60,
                        'reward_item' => 'rice_flour:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 38 : LE CONSEIL DES CINQ GRANDS RÉGENTS
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 38,
                'slug' => 'semaine_38_gotairo_1598',
                'title' => "Le Serment des Cinq Régents Gotairō",
                'jp_title' => "五大老の誓い (Gotairō no Chikai)",
                'era_year' => "Août 1598 — Palais de Fushimi",
                'description' => "Sentant ses forces décliner, Hideyoshi rassemble les cinq plus puissants daimyōs de l'archipel (Tokugawa Ieyasu, Maeda Toshiie, Uesugi Kagekatsu, Mōri Terumoto, Ukita Hideie) pour leur faire jurer sur le sang de protéger son jeune fils Hideyori et de préserver la paix impériale.",
                'image_url' => "/public/assets/events/event_week_38.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Garant du Serment",
                'prompt' => "Solemn night council chamber in Fushimi castle in feudal Japan, five legendary daimyo regents kneeling in ornate silk robes writing formal oaths with calligraphy brushes on parchment scrolls, flickering candlelight illuminating gold lacquered screens, atmosphere of deep political tension and solemn gravity, Travian art style, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations du Grand Conseil",
                        'description' => "Amasser 5 500 unités de Riz Impérial dans les dépôts de la capitale.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 5500,
                        'reward_metal' => 2000,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Diplomatique du Héros",
                        'description' => "Élever la puissance martiale de votre Samouraï Héros à au moins 260.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 260,
                        'reward_crystal' => 2000,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Troupes de Garde Impériale",
                        'description' => "Maintenir au moins 50 soldats d'élite sous les armes.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 28,
                        'reward_honor' => 70,
                        'reward_item' => 'sake:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 39 : LA RÉSISTANCE HÉROÏQUE DE FUSHIMI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 39,
                'slug' => 'semaine_39_fushimi_1600',
                'title' => "La Résistance Héroïque de Fushimi",
                'jp_title' => "伏見城の戦い (Fushimi-jō no Tatakai)",
                'era_year' => "Août 1600 — Château de Fushimi, Kyoto",
                'description' => "Pour retarder l'armée de l'Ouest d'Ishida Mitsunari et laisser à Tokugawa Ieyasu le temps de concentrer ses légions, le vieux vassal Torii Mototada soutient avec 1 800 héros un siège désespéré de onze jours contre 40 000 assaillants dans le château de Fushimi.",
                'image_url' => "/public/assets/events/event_week_39.jpg",
                'reward_final_gold' => 95,
                'reward_final_honor' => 190,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Défenseur Héroïque",
                'prompt' => "Epic medieval castle defense at Fushimi in feudal Japan, brave samurai defenders fighting atop stone walls and wooden parapets against overwhelming besieging forces under night sky filled with flaming arrows, dramatic defensive stand, heroic sacrifice, Grepolis Total War illustration, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Acier des Remparts",
                        'description' => "Amasser 6 500 unités de Métal pour armer les fortifications.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 6500,
                        'reward_crystal' => 2200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Muraille d'Airain",
                        'description' => "Élever le Mur d'Enceinte de la Cité au Niveau 3 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "wall",
                        'target_amount' => 3,
                        'reward_deuterium' => 2500,
                        'reward_gold' => 22,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Serment des 1 800",
                        'description' => "Mobiliser au moins 50 guerriers fidèles prêts au sacrifice suprême.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 32,
                        'reward_honor' => 80,
                        'reward_item' => 'wooden_beams:35'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 40 : LA RETRAITE EN CHARGE DES SHIMAZU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 40,
                'slug' => 'semaine_40_shimazu_nogikuchi_1600',
                'title' => "La Retraite Furieuse de Shimazu",
                'jp_title' => "島津の退き口 (Shimazu no Nokikuchi)",
                'era_year' => "21 Octobre 1600 — Plaine de Sekigahara",
                'description' => "Voyant l'armée de l'Ouest s'effondrer à Sekigahara, Shimazu Yoshihiro et ses 1 500 vaillants samouraïs de Satsuma refusent de fuir lâchement : ils chargent droit à travers le centre des lignes de Tokugawa pour percer l'encerclement ennemi et regagner le sud avec honneur.",
                'image_url' => "/public/assets/events/event_week_40.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Foudre de Satsuma",
                'prompt' => "Breathtaking battlefield scene in feudal Japan, fierce column of Satsuma samurai executing a forward charge directly through enemy battle lines to break siege, war banners with cross-in-circle mon, smoke and dust swirling in autumn sunlight, resolute expressions of hardened warriors, Travian Total War art style, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de la Trouée",
                        'description' => "Amasser 6 000 unités de Riz Impérial pour alimenter la percée.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6000,
                        'reward_metal' => 2400,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Martiale de Percée",
                        'description' => "Élever la puissance martiale de votre Héros à au moins 270.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 270,
                        'reward_crystal' => 2200,
                        'reward_gold' => 22,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Escadron de Choc",
                        'description' => "Maintenir au moins 50 lanciers et guerriers d'assaut.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 30,
                        'reward_honor' => 75,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 41 : LE JOUR FATIDIQUE DE SEKIGAHARA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 41,
                'slug' => 'semaine_41_sekigahara_1600',
                'title' => "Le Choc Titanesque de Sekigahara",
                'jp_title' => "関ヶ原の大決戦 (Sekigahara no Kassen)",
                'era_year' => "21 Octobre 1600 — Sekigahara, Mino",
                'description' => "La plus grande bataille de l'histoire du Japon féodal rassemble 160 000 guerriers dans la cuvette brumeuse de Sekigahara. L'armée de l'Est de Tokugawa Ieyasu affronte l'armée de l'Ouest d'Ishida Mitsunari. La trahison décisive de Kobayakawa Hideaki sur le mont Matsuoyama offre la victoire à Ieyasu et forge l'unité du Japon moderne.",
                'image_url' => "/public/assets/events/event_week_41.jpg",
                'reward_final_gold' => 120,
                'reward_final_honor' => 250,
                'reward_final_seal_days' => 3,
                'reward_final_title' => "Vainqueur de Sekigahara",
                'prompt' => "Massive epic battlefield of Sekigahara in autumn feudal Japan, two colossal samurai armies clashing in vast misty valley, thousands of fluttering colorful heraldic sashimono banners, artillery cannon smoke rising, mounted cavalry charges colliding, dramatic clearing mist revealing sunlit battle lines, masterpiece Travian Total War panoramic art, 16:9 --no text, modern gear, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Arsenal du Destin",
                        'description' => "Fournir 7 000 unités de Métal pour armer les légions de l'Est.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 7000,
                        'reward_crystal' => 2500,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Donjon de Commandement",
                        'description' => "Élever le Donjon Tenshu au Niveau 4 ou supérieur pour commander le front.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 4,
                        'reward_deuterium' => 3000,
                        'reward_gold' => 25,
                        'reward_honor' => 60
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Ost Impérial Déployé",
                        'description' => "Mobiliser au moins 60 soldats, archers et cavaliers sous vos bannières.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 60,
                        'reward_gold' => 40,
                        'reward_honor' => 100,
                        'reward_item' => 'rice_flour:60'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 42 : L'INVESTITURE DU SHŌGUN IEYASU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 42,
                'slug' => 'semaine_42_shogun_sengen_1603',
                'title' => "L'Investiture Shogunale de Tokugawa Ieyasu",
                'jp_title' => "征夷大将軍宣下 (Sei-i Taishōgun Senge)",
                'era_year' => "Mars 1603 — Palais Impérial de Kyoto",
                'description' => "L'Empereur Go-Yōzei confère solennellement le titre suprême de Sei-i Taishōgun (Généralissime pacificateur des barbares) à Tokugawa Ieyasu. Cette investiture fonde le Shōgunat d'Edo et ouvre deux siècles et demi de paix, de floraison artistique et de stabilité féodale.",
                'image_url' => "/public/assets/events/event_week_42.jpg",
                'reward_final_gold' => 100,
                'reward_final_honor' => 200,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Généralissime Shōgun",
                'prompt' => "Grand imperial investiture ceremony in Kyoto palace courtyard, Tokugawa Ieyasu in magnificent gold and black ceremonial robes receiving the sacred imperial Shogun edict from imperial envoy, noble courtiers and kneeling daimyos in court robes, radiant sunlight on vermilion wooden pillars and white gravel courtyard, Grepolis Travian ceremony art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Tribut Impérial en Kobans",
                        'description' => "Posséder au moins 6 000 unités de Riz Impérial pour approvisionner la cour.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6000,
                        'reward_metal' => 2500,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Soies et Festins",
                        'description' => "Fournir 60 Barriques de Saké Raffiné pour fêter l'avènement.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 60,
                        'reward_crystal' => 2200,
                        'reward_gold' => 24,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde d'Honneur Shogunale",
                        'description' => "Disposer d'au moins 55 guerriers d'élite pour la parade triomphale.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 55,
                        'reward_gold' => 35,
                        'reward_honor' => 85,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 43 : LE TRACÉ DES CINQ GRANDES ROUTES (GOKAIDŌ)
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 43,
                'slug' => 'semaine_43_gokaido_1604',
                'title' => "Le Tracé des Cinq Grandes Routes Gokaidō",
                'jp_title' => "五街道の整備 (Gokaidō no Seibi)",
                'era_year' => "1604 — Tōkaidō, Nakasendō, Kōshū Kaidō",
                'description' => "Pour relier Edo à Kyoto et unifier les provinces, Ieyasu ordonne le pavage des Cinq Grandes Routes (dont la mythique Tōkaidō avec ses 53 stations relais). Des rangées de cèdres sont plantées le long des voies pour offrir de l'ombre aux voyageurs et courriers express Hikyaku.",
                'image_url' => "/public/assets/events/event_week_43.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 170,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Bâtisseur de la Tōkaidō",
                'prompt' => "Scenic panoramic view of Tokaido coastal highway in feudal Japan, cobblestone road lined with majestic ancient cryptomeria pine and cedar trees, traveler caravans, post station rest stop with wooden minka inn, ocean waves in distance under bright afternoon sunlight, hand-painted ukiyo-e style of Hiroshige mixed with Travian vector art, 16:9 --no text, modern roads, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Pavage des Voies",
                        'description' => "Amasser 5 500 unités de Granit pour paver les chaussées impériales.",
                        'task_type' => "stock_resource",
                        'target_key' => "crystal",
                        'target_amount' => 5500,
                        'reward_metal' => 2200,
                        'reward_gold' => 15,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Les Relais de Poste",
                        'description' => "Développer le Marché de la Cité au Niveau 3 ou plus pour accueillir les convois.",
                        'task_type' => "upgrade_building",
                        'target_key' => "market",
                        'target_amount' => 3,
                        'reward_deuterium' => 2400,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Ponts de Cèdre",
                        'description' => "Fournir au moins 50 Poutres de Bois pour construire les ponts de relais.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 50,
                        'reward_gold' => 30,
                        'reward_honor' => 70,
                        'reward_item' => 'rice_flour:45'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 44 : LES VAISSEAUX DU SCEAU ROUGE (SHUINSEN)
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 44,
                'slug' => 'semaine_44_shuinsen_1604',
                'title' => "Les Vaisseaux du Sceau Rouge Shuinsen",
                'jp_title' => "朱印船貿易 (Shuinsen Bōeki)",
                'era_year' => "1604-1616 — Nagasaki et Asie du Sud-Est",
                'description' => "Délivrant des lettres patentes ornées du sceau vermillon du Shōgun (Shuinjō), le Shogunat lance un commerce transocéanique prospère : des navires marchands hybrides naviguent jusqu'aux Philippines, au Siam et au Vietnam, important soieries, épices et nacre en échange d'argent japonais.",
                'image_url' => "/public/assets/events/event_week_44.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Navigateur du Sceau Rouge",
                'prompt' => "Grand majestic Japanese merchant ship Shuinsen sailing out of Nagasaki harbor at sunrise, traditional wooden hybrid vessel with large white sails bearing red imperial Shogun vermilion seal, calm shimmering blue sea, trading docks with merchants and lanterns on shore, Travian maritime concept art, 16:9 --no steam engines, text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Poutres de Marine",
                        'description' => "Rassembler 55 Poutres de Bois équarri pour armer les coques marchandes.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 55,
                        'reward_metal' => 2400,
                        'reward_gold' => 16,
                        'reward_honor' => 40
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Argile des Jarres d'Épices",
                        'description' => "Stocker 3 500 unités d'Argile pour fabriquer les jarres étanches de voyage.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 3500,
                        'reward_crystal' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Cargaisons d'Argent et de Thé",
                        'description' => "Posséder au moins 3 000 unités de Thé Vert et 60 Barriques de Saké.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 60,
                        'reward_gold' => 32,
                        'reward_honor' => 75,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 45 : LA RENAISSANCE DU DONJON BLANC D'HIMEJI
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 45,
                'slug' => 'semaine_45_himeji_1609',
                'title' => "La Renaissance du Héron Blanc d'Himeji",
                'jp_title' => "白鷺城の大改修 (Shirasagi-jō no Daikaishū)",
                'era_year' => "1609 — Château d'Himeji, Harima",
                'description' => "Ikeda Terumasa achève l'agrandissement colossal du château d'Himeji, surnommé le Héron Blanc (Shirasagi-jō) en raison de ses murs de plâtre blanc immaculé incombustibles et de son donjon féerique qui semble déployer ses ailes au-dessus de la plaine de Harima.",
                'image_url' => "/public/assets/events/event_week_45.jpg",
                'reward_final_gold' => 95,
                'reward_final_honor' => 190,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Architecte du Héron Blanc",
                'prompt' => "Iconic majestic white castle Himeji Shirasagi-jo shining brightly in crisp autumn sunlight, pristine white plaster walls, multi-tiered curved pagoda roofs with grey tiles, cyclopean stone walls, blooming cherry blossoms and red maples, calm reflection in moat water, masterpiece Travian Grepolis castle art, 16:9 --no text, modern tourists, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Le Plâtre Blanc d'Argile et Chaux",
                        'description' => "Fournir 4 000 unités d'Argile alluviale pour crépir les murs incombustibles.",
                        'task_type' => "stock_resource",
                        'target_key' => "clay",
                        'target_amount' => 4000,
                        'reward_metal' => 2500,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Donjon Tenshu du Héron",
                        'description' => "Élever le Donjon Tenshu au Niveau 4 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 4,
                        'reward_crystal' => 2500,
                        'reward_gold' => 22,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Murs Fortifiés",
                        'description' => "Porter le Mur d'Enceinte de la Cité au Niveau 3 ou supérieur.",
                        'task_type' => "upgrade_building",
                        'target_key' => "wall",
                        'target_amount' => 3,
                        'reward_gold' => 32,
                        'reward_honor' => 80,
                        'reward_item' => 'wooden_beams:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 46 : LE TRAITÉ DE TSUSHIMA ET LA PAIX COMMERCIALE
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 46,
                'slug' => 'semaine_46_tsushima_1609',
                'title' => "Le Traité de Tsushima & le Commerce Coréen",
                'jp_title' => "己酉約条 (Kiyū Yakujō)",
                'era_year' => "1609 — Île de Tsushima et Pusan",
                'description' => "Le clan Sō de Tsushima négocie avec succès le traité Kiyū avec la dynastie Joseon de Corée, rétablissant les relations diplomatiques pacifiques et rouvrant le comptoir commercial japonais officiel de la Maison Japonaise (Waegwan) à Pusan.",
                'image_url' => "/public/assets/events/event_week_46.jpg",
                'reward_final_gold' => 85,
                'reward_final_honor' => 165,
                'reward_final_seal_days' => 1,
                'reward_final_title' => "Diplomate de Tsushima",
                'prompt' => "Scenic panoramic coastal view of Tsushima island straits, diplomatic meeting between Korean Joseon envoys in traditional hanbok and Japanese samurai officials in ceremonial kamishimo at harbor pavilion, trading ships docked with peaceful banners, clear sea water and coastal cliffs, Grepolis Travian diplomacy art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Ravitaillement des Comptoirs",
                        'description' => "Amasser 6 000 unités de Riz Impérial pour alimenter le commerce insulaire.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6000,
                        'reward_metal' => 2200,
                        'reward_gold' => 14,
                        'reward_honor' => 35
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Halle Portuaire",
                        'description' => "Développer le Marché de la Cité au Niveau 3 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "market",
                        'target_amount' => 3,
                        'reward_crystal' => 2200,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Cargaisons de Soja et Thé",
                        'description' => "Stocker 3 000 unités de Soja des Moissons et 2 500 unités de Thé Vert.",
                        'task_type' => "stock_resource",
                        'target_key' => "soybean",
                        'target_amount' => 3000,
                        'reward_gold' => 28,
                        'reward_honor' => 65,
                        'reward_item' => 'sake:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 47 : LE DUEL LÉGENDAIRE DE GANRYŪJIMA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 47,
                'slug' => 'semaine_47_ganryujima_1612',
                'title' => "Le Duel Légendaire de Ganryūjima",
                'jp_title' => "巌流島の決闘 (Ganryūjima no Kettō)",
                'era_year' => "13 Mai 1612 — Île de Ganryūjima, Détroit de Shimonoseki",
                'description' => "Miyamoto Musashi affronte son plus grand rival Sasaki Kojirō sur une île sablonneuse isolée. Arrivé délibérément en retard pour déstabiliser Kojirō, Musashi bondit de sa barque armé d'un simple sabre en bois (bokken) taillé dans une rame de bateau et triomphe d'un coup de maître immortalisé par la légende.",
                'image_url' => "/public/assets/events/event_week_47.jpg",
                'reward_final_gold' => 95,
                'reward_final_honor' => 200,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Maître des Deux Sabres",
                'prompt' => "Legendary samurai duel at sunrise on sandy beach island Ganryujima, Miyamoto Musashi with wooden oar bokken facing Sasaki Kojiro with long nodachi sword, dramatic crashing waves on shore, wooden rowing punt boat beached, morning sea mist and dramatic sun rays, cinematic Japanese warrior martial art painting, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Acier des Katanas d'Élite",
                        'description' => "Fournir 6 500 unités de Métal pour équiper le maître d'armes.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 6500,
                        'reward_crystal' => 2500,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Zénith Martial du Héros",
                        'description' => "Élever la puissance martiale de votre Héros Samouraï à au moins 280.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 280,
                        'reward_deuterium' => 2500,
                        'reward_gold' => 24,
                        'reward_honor' => 60
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Les Disciples du Dojo",
                        'description' => "Maintenir au moins 50 soldats réguliers au dojo militaire.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 32,
                        'reward_honor' => 80,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 48 : LA CAMPAGNE D'HIVER D'OSAKA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 48,
                'slug' => 'semaine_48_osaka_fuyu_1614',
                'title' => "La Campagne d'Hiver d'Osaka",
                'jp_title' => "大坂冬の陣 (Ōsaka Fuyu no Jin)",
                'era_year' => "Novembre 1614 — Château d'Osaka",
                'description' => "Toyotomi Hideyori et 100 000 rōnins sans maître se retranchent dans la citadelle imprenable d'Osaka. Tokugawa Ieyasu encercle la forteresse avec 200 000 guerriers sous la neige et pilonne les remparts avec d'immenses couleuvrines et mortiers de bronze importés d'Europe.",
                'image_url' => "/public/assets/events/event_week_48.jpg",
                'reward_final_gold' => 100,
                'reward_final_honor' => 200,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Vétéran de l'Hiver d'Osaka",
                'prompt' => "Panoramic winter siege in feudal Japan, colossal Osaka Castle keep rising under heavy snowfall, frozen water in outer moats, Tokugawa encampment with thousands of campfires in snowy landscape, bronze cannons firing artillery smoke, stark contrast of snow and crimson banners, Total War and Travian concept art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Rations de Neige et de Froid",
                        'description' => "Amasser 7 000 unités de Riz Impérial pour survivre à l'hiver sous la tente.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 7000,
                        'reward_metal' => 2600,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Muraille Fortifiée",
                        'description' => "Élever le Mur d'Enceinte de la Cité au Niveau 3 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "wall",
                        'target_amount' => 3,
                        'reward_crystal' => 2600,
                        'reward_gold' => 24,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Régiment des Neiges",
                        'description' => "Mobiliser au moins 55 guerriers d'élite prêts à combattre dans le gel.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 55,
                        'reward_gold' => 35,
                        'reward_honor' => 85,
                        'reward_item' => 'wooden_beams:40'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 49 : LA BARRICADE DU SANADA MARU
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 49,
                'slug' => 'semaine_49_sanada_maru_1614',
                'title' => "La Légende du Sanada Maru",
                'jp_title' => "真田丸の攻防 (Sanada Maru no Kōbō)",
                'era_year' => "Décembre 1614 — Barbacane Sud d'Osaka",
                'description' => "Face aux attaques massives des Tokugawa, Sanada Yukimura érige une barbacane en demi-lune en terre et pieux acérés au sud d'Osaka : le Sanada Maru. Grâce à des pièges ingénieux et des tirs croisés d'arquebuses, ses samouraïs rouges repoussent des vagues d'assaut dix fois supérieures en nombre.",
                'image_url' => "/public/assets/events/event_week_49.jpg",
                'reward_final_gold' => 105,
                'reward_final_honor' => 210,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Défenseur du Sanada Maru",
                'prompt' => "Panoramic battle scene at Sanada Maru earthen fortress in feudal Japan, red-armored samurai led by Sanada Yukimura defending wooden palisade barbican with matchlocks and spears, repelling massive enemy assault in snowy winter trenches, brilliant red banners with Rokumonsen six coins crest, high adrenaline game illustration, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Poutres de la Barbacane",
                        'description' => "Fournir 60 Poutres de Bois équarri pour dresser la palissade rouge.",
                        'task_type' => "stock_resource",
                        'target_key' => "wooden_beams",
                        'target_amount' => 60,
                        'reward_metal' => 2800,
                        'reward_gold' => 18,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : La Force Héroïque de Yukimura",
                        'description' => "Élever la puissance martiale de votre Héros à au moins 290.",
                        'task_type' => "hero_stat",
                        'target_key' => "combat_strength",
                        'target_amount' => 290,
                        'reward_crystal' => 2800,
                        'reward_gold' => 25,
                        'reward_honor' => 60
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde aux Six Pièces d'Or",
                        'description' => "Mobiliser au moins 55 lanciers et guerriers de choc.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 55,
                        'reward_gold' => 36,
                        'reward_honor' => 90,
                        'reward_item' => 'rice_flour:55'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 50 : LA CAMPAGNE D'ÉTÉ D'OSAKA
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 50,
                'slug' => 'semaine_50_osaka_natsu_1615',
                'title' => "La Fournaise d'Été d'Osaka & la Charge Finale",
                'jp_title' => "大坂夏の陣 (Ōsaka Natsu no Jin)",
                'era_year' => "Mai 1615 — Chanoheyama & Plaine de Tennōji",
                'description' => "Privé de ses douves comblées lors de la trêve d'hiver, le château d'Osaka livre son baroud d'honneur en plaine ouverte. Sanada Yukimura mène une charge de cavalerie rouge si furieuse qu'il brise trois lignes de défense et fait chanceler l'état-major de Tokugawa Ieyasu lui-même avant de succomber en légende immortelle.",
                'image_url' => "/public/assets/events/event_week_50.jpg",
                'reward_final_gold' => 110,
                'reward_final_honor' => 220,
                'reward_final_seal_days' => 3,
                'reward_final_title' => "Dernier Samouraï du Sengoku",
                'prompt' => "Climactic final cavalry charge in open battlefield of Tennoji in feudal Japan, red-armored samurai cavalry galloping furiously through summer heat dust directly towards massive gold fan battle standard of Tokugawa headquarters, Osaka castle burning in far background under dramatic smoke plumes, heroic tragic composition, Grepolis Total War artwork, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Ultime Arsenal d'Acier",
                        'description' => "Amasser 7 500 unités de Métal pour fondre les dernières lames.",
                        'task_type' => "stock_resource",
                        'target_key' => "metal",
                        'target_amount' => 7500,
                        'reward_crystal' => 3000,
                        'reward_gold' => 20,
                        'reward_honor' => 50
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Donjon Tenshu Ultime",
                        'description' => "Élever le Donjon Tenshu au Niveau 4 ou plus.",
                        'task_type' => "upgrade_building",
                        'target_key' => "hq",
                        'target_amount' => 4,
                        'reward_deuterium' => 3000,
                        'reward_gold' => 28,
                        'reward_honor' => 65
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : L'Armée Sanglante",
                        'description' => "Mobiliser au moins 60 guerriers pour la charge suprême.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 60,
                        'reward_gold' => 40,
                        'reward_honor' => 95,
                        'reward_item' => 'rice_flour:60'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 51 : L'ÉDIT BUKE SHOHATTO
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 51,
                'slug' => 'semaine_51_buke_shohatto_1615',
                'title' => "Le Code Guerrier du Buke Shohatto",
                'jp_title' => "武家諸法度 (Buke Shohatto)",
                'era_year' => "Juillet 1615 — Château de Fushimi",
                'description' => "Pour sceller l'ordre moral et interdire toute guerre privée entre clans, le Shogunat promulgue les Treize Lois pour les Maisons Guerrières (Buke Shohatto). Les daimyōs doivent cultiver également les arts martiaux (Bu) et les lettres classiques (Bun), limiter leurs châteaux à une seule forteresse et soumettre leurs mariages à l'accord du Shogun.",
                'image_url' => "/public/assets/events/event_week_51.jpg",
                'reward_final_gold' => 90,
                'reward_final_honor' => 180,
                'reward_final_seal_days' => 2,
                'reward_final_title' => "Législateur du Shogunat",
                'prompt' => "Solemn gathering of feudal daimyo lords kneeling in symmetry inside grand tatami hall of Edo palace, elder Shogun magistrate reading imperial laws from long calligraphed scroll, cedar pillars, golden sliding screens, swords placed respectfully aside on lacquer stands, dignified peaceful governance atmosphere, Travian historical art, 16:9 --no text, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : Les Soies et Rations de la Cour",
                        'description' => "Posséder au moins 6 500 unités de Riz Impérial dans les dépôts.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 6500,
                        'reward_metal' => 2600,
                        'reward_gold' => 16,
                        'reward_honor' => 45
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : L'Académie de Sagesse",
                        'description' => "Stocker au moins 3 500 unités de Thé Vert pour les érudits et scribes.",
                        'task_type' => "stock_resource",
                        'target_key' => "tea",
                        'target_amount' => 3500,
                        'reward_crystal' => 2600,
                        'reward_gold' => 22,
                        'reward_honor' => 55
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : La Garde de Discipline",
                        'description' => "Maintenir au moins 50 soldats disciplinés respectant le code féodal.",
                        'task_type' => "train_troops",
                        'target_key' => "all",
                        'target_amount' => 50,
                        'reward_gold' => 32,
                        'reward_honor' => 80,
                        'reward_item' => 'rice_flour:50'
                    ]
                ]
            ],

            // ─────────────────────────────────────────────────────────────
            // SEMAINE 52 : L'AUBE DE LA GRANDE PAIX (GENNA EMBU)
            // ─────────────────────────────────────────────────────────────
            [
                'week' => 52,
                'slug' => 'semaine_52_genna_embu_1615',
                'title' => "L'Aube de la Grande Paix (Genna Embu)",
                'jp_title' => "元和偃武 (Genna Embu - La Paix Sacrée)",
                'era_year' => "Automne 1615 — Ère Genna, Toute l'Archipel",
                'description' => "L'ère des guerres civiles du Sengoku prend fin définitivement : le Shōgun proclame le Genna Embu — 'Le dépôt solennel des armes'. Les lances et épées sont rangées dans leurs fourreaux pour ouvrir 250 ans de paix féodale, d'essor commercial, de floraison du théâtre Kabuki, d'estampes ukiyo-e et d'harmonie retrouvée sous la protection du ciel.",
                'image_url' => "/public/assets/events/event_week_52.jpg",
                'reward_final_gold' => 120,
                'reward_final_honor' => 250,
                'reward_final_seal_days' => 3,
                'reward_final_title' => "Pacificateur Suprême de l'Empire",
                'prompt' => "Magnificent sunrise over peaceful feudal Japan landscape, tranquil castle town with bustling harbors and trading ships, prosperous village with golden ripe rice fields, Torii gate on mountain overlook, Mount Fuji majestic in pure clear morning sky, samurai walking peacefully with family, blooming cherry and maple trees, celebration lanterns, masterpiece Grepolis Travian peaceful feudal golden era vector painting, 16:9 --no text, modern elements, borders",
                'tasks' => [
                    [
                        'step_order' => 1,
                        'title' => "Acte I : L'Abondance des Moissons",
                        'description' => "Stocker 8 000 unités de Riz Impérial et 4 000 unités de Soja dans vos greniers.",
                        'task_type' => "stock_resource",
                        'target_key' => "deuterium",
                        'target_amount' => 8000,
                        'reward_metal' => 3000,
                        'reward_gold' => 25,
                        'reward_honor' => 60
                    ],
                    [
                        'step_order' => 2,
                        'title' => "Acte II : Le Domaine de Sérénité",
                        'description' => "Élever le Sanctuaire Shintō au Niveau 3 ou plus pour bénir la paix du fief.",
                        'task_type' => "upgrade_rural",
                        'target_key' => "sanctuaire_shinto",
                        'target_amount' => 3,
                        'reward_crystal' => 3000,
                        'reward_gold' => 30,
                        'reward_honor' => 75
                    ],
                    [
                        'step_order' => 3,
                        'title' => "Acte III : Le Banquet de Paix Éternelle",
                        'description' => "Offrir 70 Barriques de Saké Raffiné et 60 Sacs de Farine de Riz pour clore l'année.",
                        'task_type' => "stock_resource",
                        'target_key' => "sake",
                        'target_amount' => 70,
                        'reward_gold' => 50,
                        'reward_honor' => 120,
                        'reward_item' => 'rice_flour:70'
                    ]
                ]
            ]
        ];
    }
}
