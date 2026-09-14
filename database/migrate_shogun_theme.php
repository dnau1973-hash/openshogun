<?php
/**
 * Migration pour la Refonte Thématique Shogun / Japon Féodal
 */
require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

echo "Starting Feudal Japan theme migration...\n";

// 1. Mettre à jour la table `units`
$units = [
    'terran_marine' => ['name' => 'Piquier Ashigaru (Yari)', 'desc' => 'Fantassin de base discipliné de l\'armée Oda, maniant la longue lance yari avec rigueur.'],
    'terran_sentinel' => ['name' => 'Arquebusier Oda (Tanegashima)', 'desc' => 'Tireur d\'élite armé du mousquet à mèche japonais Tanegashima avec une formidable puissance perforante.'],
    'terran_exo_assault' => ['name' => 'Samouraï au Katana', 'desc' => 'Noble bretteur d\'élite au moral d\'acier, expert dans le combat rapproché au fil tranchant.'],
    'terran_titan_mech' => ['name' => 'Garde Hatamoto en Armure Lourde', 'desc' => 'Champion en armure laquée ornée de la garde rapprochée du Daimyō, brisant les lignes de défense.'],
    
    'vorash_skitter' => ['name' => 'Fantassin Léger Takeda', 'desc' => 'Guerrier conscrit vif et féroce lancé à vive allure à l\'assaut des réserves adverses.'],
    'vorash_chitin' => ['name' => 'Archer Yumi Monté', 'desc' => 'Cavalier archer agile harcelant les convois et défenses à distance.'],
    'vorash_berserker' => ['name' => 'Cavalier Rouge de Choc (Akazonae)', 'desc' => 'Légendaire cavalerie rouge d\'assaut du clan Takeda, perçant toute muraille sous la charge.'],
    'vorash_goliath' => ['name' => 'Guerrier Maître Nodachi de Kai', 'desc' => 'Colosse maniant la redoutable et gigantesque lame nodachi pour fendre armures et chevaux.'],
    
    'aethelis_initiate' => ['name' => 'Sentinelle Yari Tokugawa', 'desc' => 'Garde défensif dévoué à la protection imprenable du domaine castral.'],
    'aethelis_phalanx' => ['name' => 'Archer Protecteur de Muraille', 'desc' => 'Archer d\'élite tirant des volées de flèches protectrices depuis les créneaux du château.'],
    'aethelis_shadow' => ['name' => 'Ombre Shinobi Infiltrée', 'desc' => 'Maître espion et assassin furtif se glissant sans bruit derrière les lignes ennemies.'],
    'aethelis_colossus' => ['name' => 'Hatamoto Vénérable Tokugawa', 'desc' => 'Garde imprenable au grand pavois d\'acier, roc inébranlable du domaine.']
];

$stmtUnit = $db->prepare("UPDATE units SET name = ?, description = ? WHERE code = ?");
foreach ($units as $code => $u) {
    $stmtUnit->execute([$u['name'], $u['desc'], $code]);
}
echo "Units updated (" . count($units) . ").\n";

// 2. Mettre à jour la table `ships` (Engins & Cavalerie)
$ships = [
    'transporter_light' => ['name' => 'Chariot de Ravitaillement Léger', 'desc' => 'Convoi de bêtes de somme pour acheminer le riz et les matériaux de construction.'],
    'transporter_heavy' => ['name' => 'Grand Convoi Logistique de Fief', 'desc' => 'Longue caravane de transporteurs convoyant d\'immenses cargaisons de riz et de pierre.'],
    'spy_probe' => ['name' => 'Éclaireur Shinobi Furtif', 'desc' => 'Messager discret et éclaireur rapide capable d\'espionner un fief lointain en silence.'],
    'colony_ship' => ['name' => 'Expédition d\'Établissement Castral', 'desc' => 'Troupe de pionniers et maîtres charpentiers pour ériger un nouveau fief sur des terres fertiles.'],
    'terran_interceptor' => ['name' => 'Cavalier Léger d\'Interception', 'desc' => 'Cavalier rapide patrouillant aux frontières provinciales du clan Oda.'],
    'terran_cruiser' => ['name' => 'Bélier Blindé à Éperon d\'Acier', 'desc' => 'Engin de siège lourd renforcé pour fracasser les portes fortifiées des châteaux.'],
    'terran_dreadnought' => ['name' => 'Grande Tour de Siège & Baliste', 'desc' => 'Machine de guerre monumentale pilonnant les forteresses et murailles ennemies.'],
    'vorash_drone' => ['name' => 'Cavalier Éclaireur Takeda', 'desc' => 'Cavalier rapide d\'assaut menant les raids fulgurants.'],
    'vorash_manticore' => ['name' => 'Escadron de Cavalerie Cuirassée', 'desc' => 'Fer de lance des raids de pillage impitoyables du clan Takeda.'],
    'vorash_leviathan' => ['name' => 'Bélier Titanesque du Dragon de Kai', 'desc' => 'Engin de guerre colossal écrasant toute résistance sous sa masse.'],
    'aethelis_mirage' => ['name' => 'Embuscade Shinobi Montée', 'desc' => 'Troupe furtive Tokugawa expertisant le terrain et prenant l\'ennemi à revers.'],
    'aethelis_prism' => ['name' => 'Catapulte Flamboyante Horokubiya', 'desc' => 'Projette des bombes incendiaires et pots de poudre sur les positions ennemies.'],
    'aethelis_titan' => ['name' => 'Forteresse Roulante Blindée', 'desc' => 'Bastion mobile fortifié protégeant les troupes lors des marches provinciales.']
];

$stmtShip = $db->prepare("UPDATE ships SET name = ?, description = ? WHERE code = ?");
foreach ($ships as $code => $s) {
    $stmtShip->execute([$s['name'], $s['desc'], $code]);
}
echo "Ships / Siege engines updated (" . count($ships) . ").\n";

// 3. Mettre à jour la table `researches`
$researches = [
    'energy_tech' => ['name' => 'Méditation Shintō & Sérénité', 'desc' => 'Renforce l\'harmonie spirituelle et la ferveur dans l\'ensemble du domaine.'],
    'laser_tech' => ['name' => 'Art du Tir & Poudre Noire', 'desc' => 'Perfectionne la puissance de feu des archers et des arquebusiers.'],
    'shield_tech' => ['name' => 'Tactique de Mur de Pavois', 'desc' => 'Renforce la résistance des palissades et boucliers face aux tirs ennemis.'],
    'armor_tech' => ['name' => 'Forge & Métallurgie du Tamahagane', 'desc' => 'Améliore la résistance des armures laquées et des lames forgées.'],
    'propulsion_tech' => ['name' => 'Art Militaire des Relais de Poste', 'desc' => 'Améliore la rapidité de déplacement des coursiers et des armées provinciales.'],
    'warp_tech' => ['name' => 'Grandes Voies Stratégiques (Gokaidō)', 'desc' => 'Trace les grandes routes impériales permettant les marches accélérées d\'armées de siège.'],
    'espionage_tech' => ['name' => 'Réseau de Renseignements Shinobi', 'desc' => 'Permet aux informateurs d\'obtenir des rapports détaillés sur les fiefs rivaux.']
];

$stmtRes = $db->prepare("UPDATE researches SET name = ?, description = ? WHERE code = ?");
foreach ($researches as $code => $r) {
    $stmtRes->execute([$r['name'], $r['desc'], $code]);
}
echo "Researches updated (" . count($researches) . ").\n";

// 4. Renommer les planètes existantes pour adopter des noms de fiefs japonais
$provinces = [
    'Owari', 'Mikawa', 'Kai', 'Echigo', 'Mino', 'Omi', 'Suruga', 'Totomi',
    'Shinano', 'Kaga', 'Echizen', 'Yamashiro', 'Settsu', 'Harima', 'Bizen',
    'Satsuma', 'Higo', 'Chikuzen', 'Hizen', 'Tosa', 'Iyo', 'Mutsu', 'Dewa',
    'Sagami', 'Musashi', 'Shimosa', 'Kazusa', 'Hitachi', 'Kozuke'
];

$stmtPlanets = $db->query("SELECT id, user_id, name, is_capital FROM planets");
$updatePlanet = $db->prepare("UPDATE planets SET name = ? WHERE id = ?");
$pIdx = 0;
while ($row = $stmtPlanets->fetch(PDO::FETCH_ASSOC)) {
    $pId = (int)$row['id'];
    $uId = $row['user_id'];
    $isCap = (bool)$row['is_capital'];
    
    if ($uId) {
        $stmtU = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmtU->execute([$uId]);
        $uname = $stmtU->fetchColumn() ?: 'Daimyo';
        if ($isCap) {
            $newName = "Château " . ucfirst($uname);
        } else {
            $newName = "Fief " . ucfirst($uname) . " II";
        }
    } else {
        $prov = $provinces[$pIdx % count($provinces)];
        $pIdx++;
        $newName = "Province de " . $prov;
    }
    $updatePlanet->execute([$newName, $pId]);
}
echo "Planets / Fiefs rebranded.\n";

echo "Migration finished successfully!\n";

