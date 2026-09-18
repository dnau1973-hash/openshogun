<?php
/**
 * Migration : Ajout du 19e slot de ressource (Style Travian / OpenShogun)
 * Ajoute automatiquement le slot #19 dans planet_fields pour tous les villages existants.
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';

try {
    $db = Database::getConnection();
    echo "=== Début de la migration des 19 slots de ressources ===\n";

    // 1. Récupérer toutes les planètes
    $planets = $db->query("SELECT id, name FROM planets ORDER BY id ASC")->fetchAll();
    echo "Planètes détectées : " . count($planets) . "\n";

    $stmtCheck = $db->prepare("SELECT id FROM planet_fields WHERE planet_id = ? AND field_slot = 19");
    $stmtInsert = $db->prepare("
        INSERT INTO planet_fields (planet_id, field_slot, type, level) 
        VALUES (?, 19, ?, 0)
        ON DUPLICATE KEY UPDATE type = VALUES(type)
    ");

    $addedCount = 0;
    foreach ($planets as $p) {
        $pId = (int)$p['id'];
        $stmtCheck->execute([$pId]);
        if (!$stmtCheck->fetch()) {
            // Par défaut pour le 19e slot : deuterium_synth (Rizières Koku pour nourrir le fief)
            $stmtInsert->execute([$pId, 'deuterium_synth']);
            $addedCount++;
            echo " - Slot 19 ajouté pour le fief #{$pId} ({$p['name']})\n";
        }
    }

    echo "=== Migration terminée avec succès : {$addedCount} slots créés ===\n";
} catch (Exception $e) {
    echo "Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}

