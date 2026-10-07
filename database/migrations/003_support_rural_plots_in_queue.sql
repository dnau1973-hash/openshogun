-- Migration 003 : Élargissement de la colonne build_category pour supporter les parcelles rurales (rural_plot)
-- Permet la gestion asynchrone non-instantanée des 9 parcelles féodales dans la file de construction.

ALTER TABLE `construction_queue` MODIFY COLUMN `build_category` VARCHAR(32) NOT NULL;

