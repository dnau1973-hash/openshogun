-- Seed Initial Data for OpenGalaxy

-- Vaisseaux du jeu
INSERT INTO `ships` (`code`, `name`, `faction`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `attack`, `defense`, `shield`, `speed`, `cargo_capacity`, `base_build_time`, `description`) VALUES
-- Vaisseaux Communs
('transporter_light', 'Transporteur Léger', 'all', 2000, 2000, 500, 5, 200, 10, 5000, 5000, 30, 'Vaisseau cargo standard idéal pour les raids et le commerce.'),
('transporter_heavy', 'Transporteur Lourd', 'all', 6000, 6000, 2000, 10, 800, 50, 4000, 25000, 75, 'Immense cargo spatial capable de déplacer des volumes gigantesques.'),
('spy_probe', 'Sonde d''Espionnage', 'all', 0, 1000, 200, 1, 10, 1, 20000, 5, 5, 'Sonde ultra-rapide équipée de capteurs longue portée.'),
('colony_ship', 'Vaisseau de Colonisation', 'all', 20000, 30000, 10000, 50, 3000, 200, 2500, 10000, 300, 'Équipé de modules de terraformation pour fonder une nouvelle planète.'),

-- Flotte Terrans
('terran_interceptor', 'Chasseur Rapier Terran', 'terran', 3000, 1000, 0, 60, 400, 20, 12000, 50, 25, 'Chasseur d''interception agile de la flotte humaine.'),
('terran_cruiser', 'Croiseur Vengeance Terran', 'terran', 20000, 7000, 2000, 400, 2500, 150, 8000, 800, 120, 'Croiseur lourd terran doté de canons cinétiques perçants.'),
('terran_dreadnought', 'Cuirassé Dreadnought Terran', 'terran', 45000, 25000, 10000, 1200, 8000, 600, 5000, 2000, 350, 'Monstre d''acier terrien dévastateur en combat orbital.'),

-- Flotte Vorash
('vorash_drone', 'Drone Vorash Éperon', 'vorash', 2200, 800, 0, 55, 300, 10, 13000, 80, 18, 'Unité d''assaut biologique produite rapidement et en masse.'),
('vorash_manticore', 'Mante Stellaire Vorash', 'vorash', 16000, 6000, 1500, 380, 2000, 100, 9500, 1200, 90, 'Prédateur spatial vorash excellant dans le pillage de convois.'),
('vorash_leviathan', 'Léviathan Vorash', 'vorash', 40000, 20000, 8000, 1100, 7000, 450, 6000, 3000, 260, 'Bête vivante des étoiles capable d''écraser des flottes entières.'),

-- Flotte Aethelis
('aethelis_mirage', 'Corsaire Mirage Aethelis', 'aethelis', 3500, 1500, 200, 65, 350, 50, 14000, 60, 28, 'Vaisseau psionique équipé d''un champ de camouflage et d''un bouclier déviateur.'),
('aethelis_prism', 'Frégate Prisme Aethelis', 'aethelis', 22000, 9000, 3000, 450, 2200, 250, 9000, 900, 130, 'Projette des lasers à harmoniques de distorsion énergétique.'),
('aethelis_titan', 'Bastion Astral Aethelis', 'aethelis', 50000, 30000, 15000, 1300, 7500, 900, 6500, 2500, 380, 'Forteresse d''énergie pure générant un champ de protection suprême.');

-- Recherches Technologiques
INSERT INTO `researches` (`code`, `name`, `metal_cost`, `crystal_cost`, `deuterium_cost`, `base_time`, `description`) VALUES
('energy_tech', 'Technologie Énergétique', 800, 400, 200, 40, 'Améliore le rendement des réacteurs et permet de débloquer de nouveaux systèmes.'),
('laser_tech', 'Armement Laser', 1200, 600, 100, 60, 'Augmente la puissance de feu de tous les vaisseaux de +10% par niveau.'),
('shield_tech', 'Technologie Bouclier', 1000, 1200, 500, 80, 'Renforce les boucliers déflecteurs de +10% par niveau.'),
('armor_tech', 'Blindage Moléculaire', 2000, 800, 0, 70, 'Augmente l''intégrité structurelle des coques de +10% par niveau.'),
('propulsion_tech', 'Moteur à Combustion Impulsion', 1500, 1500, 600, 90, 'Augmente la vitesse de déplacement de la flotte de +10% par niveau.'),
('warp_tech', 'Propulsion Hyperespace', 5000, 10000, 4000, 200, 'Permet les sauts spatiaux rapides et débloque les vaisseaux lourds.'),
('espionage_tech', 'Technologie d''Espionnage', 500, 1000, 300, 50, 'Donne des informations plus précises lors des missions de reconnaissance.');

-- Planètes inoccupées du secteur pour la colonisation et l'exploration
INSERT INTO `planets` (`name`, `coord_x`, `coord_y`, `planet_type`, `metal`, `crystal`, `deuterium`, `is_capital`) VALUES
('Kepler-452 Prime', 0, 1, 'terrestrial', 2500, 2000, 1200, 0),
('Nova Terra', 1, 0, 'oceanic', 3000, 1500, 2000, 0),
('Aethelgard Outpost', -1, 1, 'arctic', 1800, 3500, 800, 0),
('Tartarus Minor', 2, -1, 'volcanic', 5000, 1000, 500, 0),
('Chronos IV', -2, 0, 'desert', 3200, 2400, 900, 0),
('Starlight Basin', 0, -2, 'terrestrial', 2000, 2000, 2000, 0),
('Vorash Nexus Hive', 3, 2, 'volcanic', 4000, 1500, 1000, 0),
('Orion Deep Sector', -3, -2, 'oceanic', 2800, 3000, 1400, 0);

