-- Migration 008: Ajout de la ressource Tuiles de Cuisson (roof_tiles / Kawara) et Tuiles Démoniaques (onigawara_tiles)
-- Produite au niveau 20 de la Taille de Granit & Maçonnerie (stonemason) pour la couverture du Grand Dōjō (Endgame)
ALTER TABLE `planets`
    ADD COLUMN IF NOT EXISTS `roof_tiles` DOUBLE NOT NULL DEFAULT 0 AFTER `charcoal_max`,
    ADD COLUMN IF NOT EXISTS `roof_tiles_max` INT UNSIGNED NOT NULL DEFAULT 15000 AFTER `roof_tiles`,
    ADD COLUMN IF NOT EXISTS `onigawara_tiles` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `roof_tiles_max`;

