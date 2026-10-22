-- Migration 009: Déplacer l'Éclaireur Shinobi Furtif (spy_probe) de ships vers units (Dojo & Quartier Militaire)

-- 1. Insérer spy_probe dans la table units
INSERT INTO `units` (
    `code`, `name`, `faction`, `tier`, `icon`, `image`, 
    `metal_cost`, `crystal_cost`, `deuterium_cost`, `rice_flour_cost`, 
    `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, 
    `base_train_time`, `description`
) VALUES (
    'spy_probe', 
    'Éclaireur Shinobi Furtif', 
    'all', 
    1, 
    '🥷', 
    'eclaireur_shinobi_furtif.jpg', 
    0, 
    1000, 
    200, 
    0, 
    1, 
    10, 
    5, 
    20000, 
    5, 
    90, 
    'Messager discret et éclaireur rapide capable d\'espionner un fief lointain en silence.'
) ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `faction` = VALUES(`faction`),
    `tier` = VALUES(`tier`),
    `icon` = VALUES(`icon`),
    `image` = VALUES(`image`),
    `metal_cost` = VALUES(`metal_cost`),
    `crystal_cost` = VALUES(`crystal_cost`),
    `deuterium_cost` = VALUES(`deuterium_cost`),
    `rice_flour_cost` = VALUES(`rice_flour_cost`),
    `attack` = VALUES(`attack`),
    `def_infantry` = VALUES(`def_infantry`),
    `def_mech` = VALUES(`def_mech`),
    `speed` = VALUES(`speed`),
    `cargo_capacity` = VALUES(`cargo_capacity`),
    `base_train_time` = VALUES(`base_train_time`),
    `description` = VALUES(`description`);

-- 2. Migrer les effectifs de planet_ships vers planet_units
INSERT INTO `planet_units` (`planet_id`, `unit_code`, `count`)
SELECT `planet_id`, 'spy_probe', `count` 
FROM `planet_ships` 
WHERE `ship_code` = 'spy_probe' AND `count` > 0
ON DUPLICATE KEY UPDATE `count` = `planet_units`.`count` + VALUES(`count`);

-- 3. Supprimer spy_probe de planet_ships
DELETE FROM `planet_ships` WHERE `ship_code` = 'spy_probe';

-- 4. Supprimer spy_probe de la table ships
DELETE FROM `ships` WHERE `code` = 'spy_probe';

