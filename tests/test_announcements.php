<?php
/**
 * Test unitaire et fonctionnel : Système d'annonces de fonctionnalités et notifications
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/AnnouncementEngine.php';

echo "=== TEST : SYSTÈME D'ANNONCES & NOTIFICATIONS ===\n\n";

$db = Database::getConnection();

// 1. Vérification de la structure de base de données
echo "[1] Vérification de la table 'user_announcement_reads'...\n";
$stmt = $db->query("SHOW TABLES LIKE 'user_announcement_reads'");
if ($stmt->rowCount() === 0) {
    echo "✗ Erreur: La table user_announcement_reads n'existe pas.\n";
    exit(1);
}
echo "✓ Table 'user_announcement_reads' présente.\n";

// 2. Vérification du chargement initial depuis le JSON
echo "\n[2] Lecture des annonces existantes...\n";
$initialList = AnnouncementEngine::getAllAnnouncements(false);
echo "✓ " . count($initialList) . " annonce(s) chargée(s) depuis config/announcements.json\n";

// 3. Test de création d'une annonce de test
echo "\n[3] Création d'une annonce de test dans le JSON...\n";
$testData = [
    'version' => 'v9.9.9-test',
    'title' => 'Mise à jour de Test Automatisé',
    'badge' => '🧪 TEST',
    'icon' => '🚀',
    'summary' => 'Ceci est une annonce temporaire pour les tests automatisés.',
    'date' => date('Y-m-d'),
    'is_published' => false, // Initialement en brouillon
    'author' => 'TestRunner',
    'features' => [
        [
            'title' => 'Fonctionnalité Test A',
            'icon' => '🔬',
            'category' => 'Test',
            'description' => 'Explication de test pour la fonctionnalité A.'
        ],
        [
            'title' => 'Fonctionnalité Test B',
            'icon' => '⚡',
            'category' => 'Performance',
            'description' => 'Explication de test pour la fonctionnalité B.'
        ]
    ]
];

$testId = AnnouncementEngine::saveAnnouncement($testData);
echo "✓ Annonce créée avec succès, ID = {$testId}\n";

$loaded = AnnouncementEngine::getAnnouncement($testId);
if (!$loaded || $loaded['title'] !== $testData['title']) {
    echo "✗ Erreur: L'annonce sauvegardée n'a pas pu être relue correctement.\n";
    exit(1);
}
echo "✓ Vérification relecture JSON conforme (Titre: '{$loaded['title']}')\n";

// 4. Test du statut publié / non publié
echo "\n[4] Test du statut de publication...\n";
$publishedList = AnnouncementEngine::getAllAnnouncements(true);
$foundInPublished = array_filter($publishedList, fn($a) => $a['id'] === $testId);
if (!empty($foundInPublished)) {
    echo "✗ Erreur: L'annonce en brouillon apparaît dans les publiées !\n";
    exit(1);
}
echo "✓ L'annonce en brouillon n'apparaît pas aux joueurs.\n";

AnnouncementEngine::setPublishedStatus($testId, true);
$publishedListAfter = AnnouncementEngine::getAllAnnouncements(true);
$foundAfter = array_filter($publishedListAfter, fn($a) => $a['id'] === $testId);
if (empty($foundAfter)) {
    echo "✗ Erreur: L'annonce publiée n'apparaît pas dans la liste publique !\n";
    exit(1);
}
echo "✓ Validation admin : l'annonce est désormais publiée avec succès.\n";

// 5. Test du suivi de lecture par utilisateur
echo "\n[5] Test du suivi de lecture utilisateur...\n";
// Trouver un utilisateur réel pour le test
$userId = (int)$db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
if ($userId <= 0) {
    echo "✗ Erreur: Aucun utilisateur trouvé dans la table users.\n";
    exit(1);
}

// Nettoyer toute trace préalable de lecture pour cette annonce de test
$db->exec("DELETE FROM user_announcement_reads WHERE announcement_id = '$testId'");

$unreadList = AnnouncementEngine::getUnreadForUser($userId);
$isUnread = false;
foreach ($unreadList as $u) {
    if ($u['id'] === $testId) {
        $isUnread = true;
        break;
    }
}
if (!$isUnread) {
    echo "✗ Erreur: L'annonce publiée devrait être marquée non lue pour l'utilisateur {$userId} !\n";
    exit(1);
}
echo "✓ L'annonce est correctement détectée comme non lue pour l'utilisateur {$userId}.\n";

// Valider la lecture
$marked = AnnouncementEngine::markAsRead($userId, $testId);
if (!$marked) {
    echo "✗ Erreur: Échec de markAsRead.\n";
    exit(1);
}
echo "✓ Lecture validée par le joueur.\n";

$unreadListAfterRead = AnnouncementEngine::getUnreadForUser($userId);
$stillUnread = false;
foreach ($unreadListAfterRead as $u) {
    if ($u['id'] === $testId) {
        $stillUnread = true;
        break;
    }
}
if ($stillUnread) {
    echo "✗ Erreur: L'annonce apparaît toujours comme non lue après validation !\n";
    exit(1);
}
echo "✓ L'annonce n'est plus retournée comme non lue pour l'utilisateur (la modale ne réapparaîtra pas).\n";

// 6. Test des statistiques de lecture
echo "\n[6] Test des statistiques de lecture...\n";
$stats = AnnouncementEngine::getReadStats();
if (!isset($stats[$testId]) || $stats[$testId] < 1) {
    echo "✗ Erreur: Les statistiques n'indiquent pas la lecture enregistrée.\n";
    exit(1);
}
echo "✓ Statistiques correctes: {$stats[$testId]} lecture(s) comptabilisée(s).\n";

// 7. Nettoyage de l'annonce de test
echo "\n[7] Suppression de l'annonce de test du JSON...\n";
$deleted = AnnouncementEngine::deleteAnnouncement($testId);
if (!$deleted) {
    echo "✗ Erreur lors de la suppression de l'annonce de test.\n";
    exit(1);
}
$checkDeleted = AnnouncementEngine::getAnnouncement($testId);
if ($checkDeleted !== null) {
    echo "✗ Erreur: L'annonce est toujours présente après suppression.\n";
    exit(1);
}
echo "✓ Annonce de test supprimée proprement du fichier JSON.\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS D'ANNONCES SONT VALIDES !\n";
echo "============================================\n";

