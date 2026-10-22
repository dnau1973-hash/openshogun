<?php
/**
 * Test de validation de l'Orchestrateur séquentiel de modales (ModalQueueManager)
 * OpenShogun - Prévention de l'empilement simultané des modales d'ouverture de session
 */

echo "=== Test 1 : Vérification du Core Engine (public/js/modal_queue.js) ===\n";
$jsPath = __DIR__ . '/../public/js/modal_queue.js';
assert(file_exists($jsPath), "Le fichier public/js/modal_queue.js n'existe pas !");
$jsContent = file_get_contents($jsPath);

assert(str_contains($jsContent, 'class ModalQueue'), "Classe ModalQueue manquante");
assert(str_contains($jsContent, 'window.ModalQueueManager = new ModalQueue()'), "Singleton window.ModalQueueManager manquant");
assert(str_contains($jsContent, 'enqueue(item)'), "Méthode enqueue() manquante");
assert(str_contains($jsContent, 'notifyClosed(id)'), "Méthode notifyClosed() manquante");
assert(str_contains($jsContent, '_cleanupBackdrops()'), "Méthode _cleanupBackdrops() manquante");
assert(str_contains($jsContent, 'transitionDelay = 350'), "Délai de transition 350ms manquant");
echo "✓ public/js/modal_queue.js est complet et correctement structuré !\n";

echo "\n=== Test 2 : Vérification de l'inclusion dans views/partials/header.php ===\n";
$headerContent = file_get_contents(__DIR__ . '/../views/partials/header.php');
assert(str_contains($headerContent, 'src="/public/js/modal_queue.js'), "modal_queue.js n'est pas inclus dans le header !");
assert(str_contains($headerContent, "priority: 10"), "Proclamation Impériale (Priorité 10) manquante dans header.php");
assert(str_contains($headerContent, "ModalQueueManager.enqueue"), "ModalQueueManager.enqueue non utilisé dans header.php");
echo "✓ header.php charge modal_queue.js et orchestre la Proclamation Impériale !\n";

echo "\n=== Test 3 : Vérification de l'orchestration des Annonces de version ===\n";
$annContent = file_get_contents(__DIR__ . '/../views/partials/announcement_modal.php');
assert(str_contains($annContent, "priority: 20"), "Annonces (Priorité 20) manquante dans announcement_modal.php");
assert(str_contains($annContent, "ModalQueueManager.enqueue"), "ModalQueueManager.enqueue non utilisé dans announcement_modal.php");
assert(str_contains($annContent, "ModalQueueManager.notifyClosed()"), "notifyClosed non appelé à la fermeture silencieuse");
echo "✓ announcement_modal.php est raccordé avec succès !\n";

echo "\n=== Test 4 : Vérification de l'orchestration du Codex Quêtes / Didacticiel ===\n";
$footerContent = file_get_contents(__DIR__ . '/../views/partials/footer.php');
assert(str_contains($footerContent, "priority: 30"), "Codex Quête (Priorité 30) manquant dans footer.php");
assert(str_contains($footerContent, "ModalQueueManager.enqueue"), "ModalQueueManager.enqueue non utilisé dans footer.php");
echo "✓ footer.php orchestre les quêtes/didacticiel validés !\n";

echo "\n=== Test 5 : Vérification de l'orchestration des Chroniques Féodales ===\n";
$weeklyContent = file_get_contents(__DIR__ . '/../views/partials/weekly_event_modal.php');
assert(str_contains($weeklyContent, "priority: 40"), "Chroniques Féodales (Priorité 40) manquante dans weekly_event_modal.php");
assert(str_contains($weeklyContent, "ModalQueueManager.enqueue"), "ModalQueueManager.enqueue non utilisé dans weekly_event_modal.php");
assert(str_contains($weeklyContent, "ModalQueueManager.notifyClosed()"), "notifyClosed non appelé à la fermeture des chroniques");
echo "✓ weekly_event_modal.php est raccordé avec succès !\n";

echo "\n=== Test 6 : Vérification de l'orchestration du Maître de la Sagesse ===\n";
$wisdomContent = file_get_contents(__DIR__ . '/../views/partials/wisdom_learning_modal.php');
assert(str_contains($wisdomContent, "priority: 50"), "Maître de la Sagesse (Priorité 50) manquant dans wisdom_learning_modal.php");
assert(str_contains($wisdomContent, "ModalQueueManager.enqueue"), "ModalQueueManager.enqueue non utilisé dans wisdom_learning_modal.php");
echo "✓ wisdom_learning_modal.php est raccordé avec succès !\n";

echo "\n============================================\n";
echo "🎉 TOUS LES TESTS D'ORCHESTRATION DES MODALES SONT VALIDÉS AVEC SUCCÈS !\n";
echo "============================================\n";
