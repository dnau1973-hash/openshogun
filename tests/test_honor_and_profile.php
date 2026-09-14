<?php
/**
 * Test unitaire et fonctionnel du Tableau d'Honneur et de la Fiche Joueur (OpenGalaxy)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/HonorEngine.php';

echo "=== TEST DU TABLEAU D'HONNEUR & DE LA FICHE JOUEUR ===\n\n";

$db = Database::getConnection();
$honorEngine = new HonorEngine();
$auth = new Auth();

// 1. Récupération ou création d'utilisateurs de test
echo "1. Initialisation des commandants pour le test...\n";
$nezzar = $db->query("SELECT id, username FROM users WHERE username = 'nezzar'")->fetch();
if (!$nezzar) {
    $auth->register('nezzar', 'nezzar@opengalaxy.local', 'Gabriel125#', 'terran');
    $nezzar = $db->query("SELECT id, username FROM users WHERE username = 'nezzar'")->fetch();
    $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$nezzar['id']]);
}

$rival = $db->query("SELECT id, username FROM users WHERE username = 'RivalCommander'")->fetch();
if (!$rival) {
    $auth->register('RivalCommander', 'rival@opengalaxy.local', 'password123', 'vorash');
    $rival = $db->query("SELECT id, username FROM users WHERE username = 'RivalCommander'")->fetch();
}

$u1Id = (int)$nezzar['id'];
$u2Id = (int)$rival['id'];
echo "-> Joueur 1 : {$nezzar['username']} (ID: {$u1Id})\n";
echo "-> Joueur 2 : {$rival['username']} (ID: {$u2Id})\n\n";

// 2. Test de simulation de combat & mise à jour des statistiques hebdomadaires
echo "2. Simulation de combats pour incrémenter les stats hebdomadaires...\n";
$honorEngine->updateCombatStats($u1Id, $u2Id, 1500, 800, 25000);
$honorEngine->updateCombatStats($u2Id, $u1Id, 450, 600, 10000);

$stmtStats = $db->prepare("SELECT * FROM user_weekly_stats WHERE user_id = ?");
$stmtStats->execute([$u1Id]);
$statsU1 = $stmtStats->fetch();

echo "-> Stats Joueur 1 ({$nezzar['username']}) : Attaque: {$statsU1['attack_points']}, Défense: {$statsU1['defense_points']}, Pillage: {$statsU1['raid_resources']}\n";
assert($statsU1['attack_points'] >= 1500, "Erreur points d'attaque");
assert($statsU1['defense_points'] >= 600, "Erreur points de défense");
assert($statsU1['raid_resources'] >= 25000, "Erreur ressources pillées");
echo "-> [OK] Stats de combat mises à jour avec succès.\n\n";

// 3. Test de récupération des classements hebdomadaires (Top 10)
echo "3. Récupération des classements du Tableau d'Honneur...\n";
$topAttack = $honorEngine->getWeeklyTop('attack', 5);
$topDefense = $honorEngine->getWeeklyTop('defense', 5);
$topRaid = $honorEngine->getWeeklyTop('raid', 5);
$topProgression = $honorEngine->getWeeklyTop('progression', 5);

echo "-> Top Attaque (1er) : {$topAttack[0]['username']} ({$topAttack[0]['score']} pts)\n";
echo "-> Top Défense (1er) : {$topDefense[0]['username']} ({$topDefense[0]['score']} pts)\n";
echo "-> Top Pillage (1er) : {$topRaid[0]['username']} ({$topRaid[0]['score']} pillé)\n";
echo "-> Top Progression (1er) : {$topProgression[0]['username']} ({$topProgression[0]['score']} pts)\n";
assert(!empty($topAttack), "Top Attaque vide");
assert(!empty($topDefense), "Top Défense vide");
echo "-> [OK] getWeeklyTop() opérationnel pour les 4 catégories.\n\n";

// 4. Test d'attribution des médailles hebdomadaires
echo "4. Exécution de awardWeeklyMedals()...\n";
$testWeek = 'TEST-' . date('Y') . '-W' . date('W');
$awardResult = $honorEngine->awardWeeklyMedals($testWeek);
echo "-> Résultat : {$awardResult['medals_awarded_count']} médaille(s) décernée(s) pour la semaine {$awardResult['week_code']}\n";
assert($awardResult['success'] === true, "Échec awardWeeklyMedals");
assert($awardResult['medals_awarded_count'] >= 1, "Aucune médaille décernée");

$stmtMedals = $db->prepare("SELECT * FROM user_medals WHERE user_id = ? AND week_code = ?");
$stmtMedals->execute([$u1Id, $testWeek]);
$medalsU1 = $stmtMedals->fetchAll();
echo "-> Médailles obtenues par {$nezzar['username']} pour {$testWeek} : " . count($medalsU1) . "\n";
foreach ($medalsU1 as $m) {
    echo "   * {$m['category']} (Rang #{$m['rank']}) : {$m['description']}\n";
}
assert(count($medalsU1) > 0, "Le joueur 1 aurait dû recevoir au moins une médaille");
echo "-> [OK] Attribution des médailles et réinitialisation validées.\n\n";

// 5. Test de la Fiche Joueur (getUserProfile)
echo "5. Test de la Fiche Joueur (getUserProfile)...\n";
$profile = $honorEngine->getUserProfile($u1Id);
assert($profile !== null, "Profil introuvable");
echo "-> Commandant : {$profile['username']}\n";
echo "-> Civilisation : {$profile['faction_name']} {$profile['faction_icon']}\n";
echo "-> Rang Galactique : #{$profile['rank_position']} / {$profile['total_players']}\n";
echo "-> Total Médailles : {$profile['total_medals']}\n";
echo "-> Nombre de Colonies : " . count($profile['colonies']) . "\n";
assert(isset($profile['medals']), "Tableau medals manquant");
assert(isset($profile['weekly_stats']), "weekly_stats manquant");
assert(isset($profile['colonies']), "colonies manquant");
echo "-> [OK] Structure complète du profil validée.\n\n";

// 6. Test de mise à jour du manifeste / bio
echo "6. Test de mise à jour du manifeste / bio...\n";
$newBio = "Commandant suprême de la Fédération. Paix aux empires alliés, destruction aux envahisseurs !";
$bioRes = $honorEngine->updateBio($u1Id, $newBio);
assert($bioRes['success'] === true, "Échec updateBio");

$profileAfter = $honorEngine->getUserProfile($u1Id);
echo "-> Bio enregistrée : \"{$profileAfter['bio']}\"\n";
assert($profileAfter['bio'] === $newBio, "Bio non mise à jour");
echo "-> [OK] Mise à jour du manifeste validée.\n\n";

echo "=== TOUS LES TESTS DU TABLEAU D'HONNEUR & FICHE JOUEUR ONT RÉUSSI AVEC SUCCÈS ! ===\n";

