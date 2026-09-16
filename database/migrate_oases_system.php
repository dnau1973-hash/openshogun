<?php
/**
 * Migration : Système d'Oasis Inoccupées, Animaux Sauvages & Bonus de Production
 * Style Travian pour OpenShogun
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GalaxyEngine.php';

echo "=== MIGRATION DU SYSTÈME D'OASIS & ANIMAUX SAUVAGES ===\n";

$db = Database::getConnection();

try {
    // 1. Création de la table 'oases'
    echo "1. Création de la table 'oases'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `oases` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `coord_x` INT NOT NULL,
            `coord_y` INT NOT NULL,
            `oasis_type` VARCHAR(40) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `bonus_wood` INT NOT NULL DEFAULT 0,
            `bonus_stone` INT NOT NULL DEFAULT 0,
            `bonus_rice` INT NOT NULL DEFAULT 0,
            `res_wood` INT NOT NULL DEFAULT 1200,
            `res_stone` INT NOT NULL DEFAULT 1200,
            `res_rice` INT NOT NULL DEFAULT 1200,
            `res_max` INT NOT NULL DEFAULT 5000,
            `last_loot_time` INT UNSIGNED NOT NULL DEFAULT 0,
            `owner_planet_id` INT UNSIGNED NULL,
            `annexed_at` INT UNSIGNED NULL,
            UNIQUE KEY `idx_oasis_coords` (`coord_x`, `coord_y`),
            KEY `idx_oasis_owner` (`owner_planet_id`),
            CONSTRAINT `fk_oasis_owner_planet` FOREIGN KEY (`owner_planet_id`) REFERENCES `planets`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Création de la table 'oasis_units'
    echo "2. Création de la table 'oasis_units'...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `oasis_units` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `oasis_id` INT UNSIGNED NOT NULL,
            `unit_code` VARCHAR(60) NOT NULL,
            `count` INT NOT NULL DEFAULT 0,
            `is_wild` TINYINT(1) NOT NULL DEFAULT 1,
            KEY `idx_oasis_unit` (`oasis_id`, `unit_code`),
            CONSTRAINT `fk_oasis_units_oasis` FOREIGN KEY (`oasis_id`) REFERENCES `oases`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Insertion des 3 unités de bêtes sauvages dans la table 'units'
    echo "3. Ajout des animaux sauvages dans la table 'units'...\n";
    $stmtUnits = $db->prepare("
        INSERT INTO `units` 
        (`code`, `name`, `faction`, `tier`, `icon`, `image`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, `base_train_time`, `description`)
        VALUES (?, ?, 'all', ?, ?, ?, 0, 0, 0, ?, ?, ?, ?, 0, 0, ?)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name), 
            description = VALUES(description),
            attack = VALUES(attack),
            def_infantry = VALUES(def_infantry),
            def_mech = VALUES(def_mech),
            icon = VALUES(icon),
            image = VALUES(image)
    ");

    $animals = [
        [
            'sanglier_sauvage', 'Sanglier Enragé des Monts', 1, '🐗', 'sanglier_sauvage.jpg',
            35, 40, 20, 7, 'Bête sauvage agressive chargeant en furie quiconque s\'approche de sa tanière.'
        ],
        [
            'loup_honshu', 'Loup Vicieux de Honshu', 2, '🐺', 'loup_honshu.jpg',
            60, 35, 55, 9, 'Prédateur rusé chassant en meute coordonnée dans les forêts et collines.'
        ],
        [
            'ours_hokkaido', 'Grand Ours Brun de Hokkaido', 3, '🐻', 'ours_hokkaido.jpg',
            140, 130, 110, 6, 'Colosse sauvage des contrées septentrionales, doué d\'une force brute titanesque.'
        ]
    ];

    foreach ($animals as $a) {
        $stmtUnits->execute([$a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9]]);
        echo "   ✓ [{$a[3]} {$a[1]}] enregistré dans units\n";
    }

    // 4. Génération de 24 Oasis stratégiques réparties sur les 4 quadrants
    echo "4. Déploiement des 24 Oasis naturelles sur la carte...\n";
    
    // Récupérer les coordonnées occupées par des planètes ou donjons
    $usedCoords = [];
    $stmtUsedP = $db->query("SELECT coord_x, coord_y FROM planets");
    while ($row = $stmtUsedP->fetch(PDO::FETCH_ASSOC)) {
        $usedCoords[$row['coord_x'] . ':' . $row['coord_y']] = true;
    }
    $stmtUsedC = $db->query("SELECT coord_x, coord_y FROM authentic_castles WHERE is_spawned = 1");
    while ($row = $stmtUsedC->fetch(PDO::FETCH_ASSOC)) {
        $usedCoords[$row['coord_x'] . ':' . $row['coord_y']] = true;
    }

    $oasisTemplates = [
        [
            'type' => 'lake_rice_50',
            'name' => 'Grand Lac aux Eaux Vivifiantes',
            'wood' => 0, 'stone' => 0, 'rice' => 50,
            'animals' => ['sanglier_sauvage' => 30, 'loup_honshu' => 20, 'ours_hokkaido' => 8]
        ],
        [
            'type' => 'forest_wood_50',
            'name' => 'Forêt Millénaire de Cèdres Géants',
            'wood' => 50, 'stone' => 0, 'rice' => 0,
            'animals' => ['sanglier_sauvage' => 35, 'loup_honshu' => 25, 'ours_hokkaido' => 10]
        ],
        [
            'type' => 'mountain_stone_50',
            'name' => 'Pics Escarpés aux Gisements de Fer',
            'wood' => 0, 'stone' => 50, 'rice' => 0,
            'animals' => ['sanglier_sauvage' => 25, 'loup_honshu' => 30, 'ours_hokkaido' => 12]
        ],
        [
            'type' => 'lake_wood_rice',
            'name' => 'Source Chaude d\'Onsen en Lisière',
            'wood' => 25, 'stone' => 0, 'rice' => 25,
            'animals' => ['sanglier_sauvage' => 25, 'loup_honshu' => 15, 'ours_hokkaido' => 5]
        ],
        [
            'type' => 'hills_stone_wood',
            'name' => 'Plateau Argileux & Vergers Sauvages',
            'wood' => 25, 'stone' => 25, 'rice' => 0,
            'animals' => ['sanglier_sauvage' => 20, 'loup_honshu' => 18, 'ours_hokkaido' => 6]
        ],
        [
            'type' => 'mountain_stone_rice',
            'name' => 'Gorge Minérale & Cascades Sacrées',
            'wood' => 0, 'stone' => 25, 'rice' => 25,
            'animals' => ['sanglier_sauvage' => 22, 'loup_honshu' => 20, 'ours_hokkaido' => 7]
        ]
    ];

    // Définir les 4 quadrants avec plages de coordonnées
    $quadrants = [
        'NO' => ['min_x' => -28, 'max_x' => -4, 'min_y' => 4, 'max_y' => 28],
        'NE' => ['min_x' => 4, 'max_x' => 28, 'min_y' => 4, 'max_y' => 28],
        'SO' => ['min_x' => -28, 'max_x' => -4, 'min_y' => -28, 'max_y' => -4],
        'SE' => ['min_x' => 4, 'max_x' => 28, 'min_y' => -28, 'max_y' => -4],
    ];

    $stmtInsertOasis = $db->prepare("
        INSERT INTO `oases` 
        (`coord_x`, `coord_y`, `oasis_type`, `name`, `bonus_wood`, `bonus_stone`, `bonus_rice`, `res_wood`, `res_stone`, `res_rice`, `res_max`, `last_loot_time`)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1500, 1500, 1500, 5000, UNIX_TIMESTAMP())
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");

    $stmtInsertAnimal = $db->prepare("
        INSERT INTO `oasis_units` (`oasis_id`, `unit_code`, `count`, `is_wild`)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE count = VALUES(count)
    ");

    $totalSpawned = 0;
    foreach ($quadrants as $qName => $qRange) {
        foreach ($oasisTemplates as $tpl) {
            // Trouver des coordonnées libres dans ce quadrant
            $placed = false;
            for ($attempt = 0; $attempt < 60; $attempt++) {
                $ox = rand($qRange['min_x'], $qRange['max_x']);
                $oy = rand($qRange['min_y'], $qRange['max_y']);
                $k = $ox . ':' . $oy;

                if (!isset($usedCoords[$k])) {
                    $usedCoords[$k] = true;
                    $stmtInsertOasis->execute([
                        $ox, $oy, $tpl['type'], $tpl['name'], 
                        $tpl['wood'], $tpl['stone'], $tpl['rice']
                    ]);
                    $oasisId = (int)$db->lastInsertId();
                    if ($oasisId === 0) {
                        $s = $db->prepare("SELECT id FROM oases WHERE coord_x = ? AND coord_y = ?");
                        $s->execute([$ox, $oy]);
                        $oasisId = (int)$s->fetchColumn();
                    }

                    // Insérer les animaux sauvages
                    foreach ($tpl['animals'] as $animCode => $animCount) {
                        $stmtInsertAnimal->execute([$oasisId, $animCode, $animCount]);
                    }

                    $placed = true;
                    $totalSpawned++;
                    break;
                }
            }
        }
    }

    echo "   ✓ {$totalSpawned} Oasis créées et peuplées d'animaux sauvages.\n";
    echo "\n=== MIGRATION DES OASIS RÉUSSIE AVEC SUCCÈS ! ===\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
