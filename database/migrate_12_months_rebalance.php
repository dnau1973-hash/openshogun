<?php
/**
 * Migration : Équilibrage des Temps de Construction, Recrutement & Recherches (Serveur 12 Mois)
 * Projet : La Voie du Shogun
 */
require_once __DIR__ . '/../core/Database.php';

echo "===============================================================\n";
echo "  MIGRATION : ÉQUILIBRAGE DU RYTHME SENGOKU (SERVEUR 12 MOIS)  \n";
echo "===============================================================\n\n";

$db = Database::getConnection();

// 1. Mise à jour de `game_settings`
echo "1. Mise à jour des paramètres globaux (game_settings)...\n";
$settingsUpdates = [
    'game_speed' => '1',        // Rythme standard 1x
    'resource_speed' => '1',    // Production standard 1x
    'fleet_speed' => '2'        // Marches provinciales à vitesse 2x équilibrée
];

foreach ($settingsUpdates as $k => $v) {
    $exists = $db->prepare("SELECT COUNT(*) FROM game_settings WHERE setting_key = ?");
    $exists->execute([$k]);
    if ($exists->fetchColumn() > 0) {
        $db->prepare("UPDATE game_settings SET setting_value = ? WHERE setting_key = ?")->execute([$v, $k]);
    } else {
        $db->prepare("INSERT INTO game_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, 'int', 'Gameplay pace balance')")->execute([$k, $v]);
    }
    echo "   ✓ $k défini à $v\n";
}

// 2. Mise à jour des temps d'entraînement des unités militaires (`units`)
echo "\n2. Calibrage des temps d'entraînement des troupes (table units)...\n";
$unitTimes = [
    // Tier 1 : Fantassins & Sentinelles de base (~1m20 à 1m35)
    'piquier_ashigaru_yari'         => 90,
    'sentinelle_yari_tokugawa'      => 95,
    'fantassin_leger_takeda'        => 80,
    
    // Tier 2 : Tireurs, Arquebusiers & Archers (~3m20 à 3m40)
    'arquebusier_oda_tanegashima'   => 210,
    'archer_protecteur_muraille'    => 220,
    'archer_yumi_monte'             => 200,

    // Tier 3 : Samouraïs & Cavalerie d'assaut (~7m30 à 9m00)
    'samourai_katana'               => 480,
    'cavalier_rouge_akazonae'       => 540,
    'ombre_shinobi_infiltree'       => 450,

    // Tier 4 : Gardes Hatamotos & Maîtres d'armes d'élite (~19m à 21m)
    'garde_hatamoto_armure_lourde'  => 1200,
    'hatamoto_venerable_tokugawa'   => 1300,
    'maitre_nodachi_kai'            => 1150
];

$stmtUnit = $db->prepare("UPDATE units SET base_train_time = ? WHERE code = ?");
foreach ($unitTimes as $code => $time) {
    $stmtUnit->execute([$time, $code]);
    echo "   ✓ Unité [$code] -> base_train_time = {$time}s\n";
}

// 3. Mise à jour des temps de construction de la cavalerie et des engins de siège (`ships`)
echo "\n3. Calibrage des engins de siège, convois et montures (table ships)...\n";
$shipTimes = [
    'spy_probe'          => 90,     // Éclaireur Shinobi (1m30)
    'transporter_light'  => 240,    // Chariot Léger (4m)
    'transporter_heavy'  => 600,    // Grand Convoi Logistique (10m)
    'terran_interceptor' => 300,    // Cavalier Léger Oda (5m)
    'vorash_drone'       => 270,    // Cavalier Éclaireur Takeda (4m30)
    'aethelis_mirage'    => 360,    // Embuscade Shinobi Montée (6m)
    'vorash_manticore'   => 900,    // Cavalerie Cuirassée Takeda (15m)
    'terran_cruiser'     => 1500,   // Bélier Blindé d'Acier (25m)
    'aethelis_prism'     => 2100,   // Catapulte Horokubiya (35m)
    'vorash_leviathan'   => 3000,   // Bélier Titanesque Kai (50m)
    'terran_dreadnought' => 4200,   // Grande Tour de Siège & Baliste (1h10)
    'aethelis_titan'     => 4800,   // Forteresse Roulante Blindée (1h20)
    'colony_ship'        => 10800   // Expédition d'Établissement Castral (3h00)
];

$stmtShip = $db->prepare("UPDATE ships SET base_build_time = ? WHERE code = ?");
foreach ($shipTimes as $code => $time) {
    $stmtShip->execute([$time, $code]);
    echo "   ✓ Engin [$code] -> base_build_time = {$time}s\n";
}

// 4. Mise à jour des temps des technologies et savoirs de l'Académie (`researches`)
echo "\n4. Calibrage des recherches scientifiques et forges (table researches)...\n";
$researchTimes = [
    'energy_tech'     => 150,   // Méditation Shintō & Sérénité (2m30 base)
    'espionage_tech'  => 180,   // Réseau de Renseignements Shinobi (3m base)
    'laser_tech'      => 210,   // Art du Tir & Poudre Noire (3m30 base)
    'armor_tech'      => 240,   // Forge & Métallurgie du Tamahagane (4m base)
    'shield_tech'     => 270,   // Tactique de Mur de Pavois (4m30 base)
    'propulsion_tech' => 300,   // Art Militaire des Relais de Poste (5m base)
    'warp_tech'       => 600    // Grandes Voies Stratégiques Gokaidō (10m base)
];

$stmtRes = $db->prepare("UPDATE researches SET base_time = ? WHERE code = ?");
foreach ($researchTimes as $code => $time) {
    $stmtRes->execute([$time, $code]);
    echo "   ✓ Savoir [$code] -> base_time = {$time}s\n";
}

echo "\n===============================================================\n";
echo "  MIGRATION DU RYTHME DU SERVEUR 12 MOIS TERMINÉE AVEC SUCCÈS !  \n";
echo "===============================================================\n";
