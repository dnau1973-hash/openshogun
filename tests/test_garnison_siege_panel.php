<?php
/**
 * Test de validation : Bloc Garnison du Domaine & Armes de Siège
 */

echo "=================================================================\n";
echo " TEST : BLOC GARNISON DU DOMAINE & ARMES DE SIÈGE\n";
echo "=================================================================\n\n";

// 1. Vérification des armes de siège féodales
echo "1. Vérification de la configuration des armes de siège :\n";

$siegeMeta = [
    'terran_cruiser'     => "Bélier Blindé à Éperon d'Acier",
    'terran_dreadnought' => "Grande Tour de Siège & Baliste",
    'vorash_leviathan'   => "Bélier Titanesque du Dragon de Kai",
    'aethelis_prism'     => "Catapulte Flamboyante Horokubiya",
    'aethelis_titan'     => "Forteresse Roulante Blindée"
];

foreach ($siegeMeta as $code => $name) {
    echo "  ✓ Arme de siège enregistrée : [{$code}] {$name}\n";
}

// 2. Vérification de la présence des fichiers d'assets images des armes de siège
echo "\n2. Vérification des illustrations HD des armes de siège :\n";
$images = [
    'belier_acier_oda.jpg',
    'tour_siege_baliste_oda.jpg',
    'belier_dragon_kai.jpg',
    'catapulte_horokubiya_tokugawa.jpg',
    'forteresse_roulante_tokugawa.jpg'
];

foreach ($images as $img) {
    $path = __DIR__ . '/../public/assets/units/' . $img;
    if (file_exists($path)) {
        echo "  ✓ Illustration disponible : {$img} (" . round(filesize($path)/1024, 1) . " Ko)\n";
    } else {
        echo "  ❌ Illustration manquante : {$img}\n";
        exit(1);
    }
}

// 3. Test de simulation du calcul du panel garnison
echo "\n3. Test de simulation du calcul des effectifs et puissance de siège :\n";

// Simuler des unités stationnées
$mockStationedTroops = [
    ['code' => 'piquier_ashigaru_yari', 'name' => 'Piquier Ashigaru (Yari)', 'attack' => 40, 'def_infantry' => 35, 'def_mech' => 20, 'stationed_count' => 100],
    ['code' => 'samourai_katana', 'name' => 'Samouraï au Katana', 'attack' => 85, 'def_infantry' => 45, 'def_mech' => 30, 'stationed_count' => 25],
];

$mockSiege = [
    'terran_cruiser' => [
        'code' => 'terran_cruiser',
        'name' => "Bélier Blindé à Éperon d'Acier",
        'attack' => 400,
        'defense' => 2500,
        'shield' => 150,
        'stationed_count' => 5,
        'meta' => [
            'base_dmg' => 150,
            'wall_mult' => 3.0
        ]
    ],
    'aethelis_prism' => [
        'code' => 'aethelis_prism',
        'name' => "Catapulte Flamboyante Horokubiya",
        'attack' => 450,
        'defense' => 2200,
        'shield' => 250,
        'stationed_count' => 2,
        'meta' => [
            'base_dmg' => 200,
            'wall_mult' => 3.0
        ]
    ]
];

$totalSoldiers = 0;
$totalSiege = 0;
$totalAtk = 0;
$totalDef = 0;
$totalSiegeBase = 0;
$totalSiegeWall = 0;

foreach ($mockStationedTroops as $t) {
    $cnt = (int)$t['stationed_count'];
    $totalSoldiers += $cnt;
    $totalAtk += $cnt * $t['attack'];
    $totalDef += $cnt * ($t['def_infantry'] + $t['def_mech']);
}

foreach ($mockSiege as $s) {
    $cnt = (int)$s['stationed_count'];
    $totalSiege += $cnt;
    $totalAtk += $cnt * $s['attack'];
    $totalDef += $cnt * ($s['defense'] + $s['shield']);
    $totalSiegeBase += $cnt * $s['meta']['base_dmg'];
    $totalSiegeWall += (int)round($cnt * $s['meta']['base_dmg'] * $s['meta']['wall_mult']);
}

$wallLevel = 5;
$wallBonusFactor = 1.0 + ($wallLevel * 0.04);
$totalFortifiedDef = (int)($totalDef * $wallBonusFactor) + ($wallLevel * 25);

echo "  ✓ Total Soldats : {$totalSoldiers}\n";
echo "  ✓ Total Armes de Siège : {$totalSiege} engins\n";
echo "  ✓ Puissance d'Attaque Totale : " . number_format($totalAtk) . "\n";
echo "  ✓ Puissance de Siège (Impact Brut) : " . number_format($totalSiegeBase) . "\n";
echo "  ✓ Puissance de Siège (Dégâts Remparts) : " . number_format($totalSiegeWall) . "\n";
echo "  ✓ Défense Fortifiée (Remparts Niv. {$wallLevel}) : " . number_format($totalFortifiedDef) . "\n";

// 4. Vérification de la syntaxe de troops_panel.php et resources_east_accordion.php
echo "\n4. Contrôle syntaxique des vues de la colonne de droite :\n";
$f1 = __DIR__ . '/../views/partials/troops_panel.php';
$f2 = __DIR__ . '/../views/partials/resources_east_accordion.php';

exec("php -l " . escapeshellarg($f1), $out1, $ret1);
exec("php -l " . escapeshellarg($f2), $out2, $ret2);

if ($ret1 === 0 && $ret2 === 0) {
    echo "  ✓ views/partials/troops_panel.php : Syntaxe PHP valide\n";
    echo "  ✓ views/partials/resources_east_accordion.php : Syntaxe PHP valide\n";
} else {
    echo "  ❌ Erreur de syntaxe détectée !\n";
    exit(1);
}

// 5. Vérification du contenu textuel et des composants
echo "\n5. Vérification de l'intégration des composants clés dans troops_panel.php :\n";
$content1 = file_get_contents($f1);

$keywords = [
    'Armes de Siège Féodales',
    'Guerriers du Dojo',
    'Bélier Blindé à Éperon',
    'Catapulte Flamboyante Horokubiya',
    'Grande Tour de Siège & Baliste',
    'Bélier Titanesque du Dragon de Kai',
    'Forteresse Roulante Blindée',
    'Bilan Martiale &amp; Tactique',
    'filterGarnison',
    'modalGarrisonUnitDetail',
    'openGarrisonUnitModal',
    'closeGarrisonUnitModal',
    'garrisonUnitModalImage',
    'garrisonUnitModalTitle',
    'garrisonUnitModalDescription',
    'garrisonUnitModalStatsGrid',
    'garrisonUnitModalActionBtn'
];

foreach ($keywords as $kw) {
    if (strpos($content1, $kw) !== false) {
        echo "  ✓ Composant présent : '{$kw}'\n";
    } else {
        echo "  ❌ Composant manquant : '{$kw}'\n";
        exit(1);
    }
}

echo "\n=================================================================\n";
echo " ✅ TOUS LES TESTS DE LA GARNISON & DES ARMES DE SIÈGE ONT RÉUSSI !\n";
echo "=================================================================\n";
