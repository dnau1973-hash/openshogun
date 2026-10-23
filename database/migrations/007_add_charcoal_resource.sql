-- Migration 007: Ajout de la ressource Charbon de bois (Charcoal) produite par l'Atelier de Charpenterie (Niv 15+)
ALTER TABLE `planets`
    ADD COLUMN IF NOT EXISTS `charcoal` DOUBLE NOT NULL DEFAULT 0 AFTER `wooden_beams_max`,
    ADD COLUMN IF NOT EXISTS `charcoal_max` INT UNSIGNED NOT NULL DEFAULT 10000 AFTER `charcoal`;

