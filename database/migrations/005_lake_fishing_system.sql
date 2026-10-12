-- Migration 005 : Système Halieutique Féodal (Parcelles de Lac, Pêcheurs du Dojo & Stockage Poisson)
-- OpenShogun

-- 1. Ajout des colonnes de stockage du poisson dans planets
ALTER TABLE `planets`
    ADD COLUMN IF NOT EXISTS `fish` DOUBLE NOT NULL DEFAULT 0 AFTER `deuterium_max`,
    ADD COLUMN IF NOT EXISTS `fish_max` INT UNSIGNED NOT NULL DEFAULT 15000 AFTER `fish`;

-- 2. Mise à jour de l'énumération mission_type dans fleet_missions pour inclure 'fishing'
ALTER TABLE `fleet_missions`
    MODIFY COLUMN `mission_type` ENUM('attack','raid','transport','spy','colonize','occupy','adventure','fishing') NOT NULL;

-- 3. Ajout de la technologie de recherche 'fishing_tech' (Halieutique Féodale)
INSERT INTO `researches` (`code`, `name`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `base_time`, `description`)
VALUES (
    'fishing_tech',
    'Halieutique Féodale',
    300,
    250,
    400,
    120,
    'Développe les filets traditionnels, nasses fluviales et techniques de salaison. Chaque niveau de recherche augmente le niveau d\'expérience et le rendement des Pêcheurs de +25% lors des expéditions sur les lacs.'
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `metal_cost` = VALUES(`metal_cost`),
    `crystal_cost` = VALUES(`crystal_cost`),
    `deuterium_cost` = VALUES(`deuterium_cost`),
    `base_time` = VALUES(`base_time`),
    `description` = VALUES(`description`);

-- 4. Ajout de la nouvelle unité civile 'pecheur' (Pêcheur Féodal / Ryōshi) dans units
INSERT INTO `units` (
    `code`, `name`, `faction`, `tier`, `icon`, `image`,
    `metal_cost`, `crystal_cost`, `deuterium_cost`, `rice_flour_cost`,
    `attack`, `def_infantry`, `def_mech`, `speed`, `cargo_capacity`, `base_train_time`, `description`
)
VALUES (
    'pecheur',
    'Pêcheur Féodal (Ryōshi)',
    'all',
    1,
    '🎣',
    '/public/assets/units/pecheur.jpg',
    60,
    40,
    50,
    0,
    5,
    10,
    10,
    14,
    100,
    45,
    'Villageois spécialisé dans la navigation fluviale et lacustre. Formé au Dojo, il navigue vers les lacs calmes pour ramener des cargaisons de poisson frais entreposées dans le Grenier à Riz Fortifié.'
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `faction` = VALUES(`faction`),
    `tier` = VALUES(`tier`),
    `icon` = VALUES(`icon`),
    `image` = VALUES(`image`),
    `metal_cost` = VALUES(`metal_cost`),
    `crystal_cost` = VALUES(`crystal_cost`),
    `deuterium_cost` = VALUES(`deuterium_cost`),
    `attack` = VALUES(`attack`),
    `def_infantry` = VALUES(`def_infantry`),
    `def_mech` = VALUES(`def_mech`),
    `speed` = VALUES(`speed`),
    `cargo_capacity` = VALUES(`cargo_capacity`),
    `base_train_time` = VALUES(`base_train_time`),
    `description` = VALUES(`description`);

