<?php
/**
 * Migration : Ajout de la colonne 'slot' dans la table 'planet_buildings'
 * Permet à n'importe quel bâtiment d'être placé sur n'importe quel emplacement de la cité (style Travian)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/game_constants.php';

echo "--- MIGRATION : SLOTS DYNAMIQUES POUR PLANET_BUILDINGS ---\n";

$db = Database::getConnection();

try {
    // 1. Vérifier si la colonne slot existe déjà
    $colStmt = $db->query("SHOW COLUMNS FROM planet_buildings LIKE 'slot'");
    if (!$colStmt->fetch()) {
        $db->exec("ALTER TABLE planet_buildings ADD COLUMN slot TINYINT UNSIGNED NULL AFTER planet_id");
        echo "✓ Colonne 'slot' ajoutée avec succès dans 'planet_buildings'.\n";
    } else {
        echo "- Colonne 'slot' déjà présente.\n";
    }

    // 2. Vérifier si l'index unique uniq_planet_slot existe
    $idxStmt = $db->query("SHOW INDEX FROM planet_buildings WHERE Key_name = 'uniq_planet_slot'");
    if (!$idxStmt->fetch()) {
        $db->exec("ALTER TABLE planet_buildings ADD UNIQUE KEY uniq_planet_slot (planet_id, slot)");
        echo "✓ Index unique 'uniq_planet_slot' créé avec succès.\n";
    } else {
        echo "- Index 'uniq_planet_slot' déjà présent.\n";
    }

    // 3. Assigner les slots par défaut pour les bâtiments existants
    $defaultSlots = [
        'hq' => 19,
        'storage' => 20,
        'tank' => 21,
        'barracks' => 22,
        'shipyard' => 23,
        'market' => 24,
        'research_lab' => 25,
        'radar' => 26,
        'quantum_vault' => 27,
        'embassy' => 28,
        'wall' => 34
    ];

    $stmtRows = $db->query("SELECT id, planet_id, building_type, slot FROM planet_buildings WHERE slot IS NULL");
    $nullRows = $stmtRows->fetchAll();

    $stmtUp = $db->prepare("UPDATE planet_buildings SET slot = ? WHERE id = ?");
    $assigned = 0;

    foreach ($nullRows as $row) {
        $type = $row['building_type'];
        $desiredSlot = $defaultSlots[$type] ?? null;

        // Si le slot souhaité est déjà pris sur cette planète, trouver le premier slot libre entre 19 et 34
        if ($desiredSlot) {
            $checkTaken = $db->prepare("SELECT id FROM planet_buildings WHERE planet_id = ? AND slot = ? AND id != ?");
            $checkTaken->execute([$row['planet_id'], $desiredSlot, $row['id']]);
            if ($checkTaken->fetch()) {
                $desiredSlot = null;
            }
        }

        if (!$desiredSlot) {
            $stmtTaken = $db->prepare("SELECT slot FROM planet_buildings WHERE planet_id = ? AND slot IS NOT NULL");
            $stmtTaken->execute([$row['planet_id']]);
            $takenSlots = $stmtTaken->fetchAll(PDO::FETCH_COLUMN);
            for ($s = 19; $s <= 34; $s++) {
                if (!in_array($s, $takenSlots)) {
                    $desiredSlot = $s;
                    break;
                }
            }
        }

        if ($desiredSlot) {
            $stmtUp->execute([$desiredSlot, $row['id']]);
            $assigned++;
        }
    }

    echo "✓ Slots assignés pour {$assigned} bâtiments existants.\n";
    echo "--- MIGRATION TERMINÉE AVEC SUCCÈS ---\n";

} catch (Exception $e) {
    echo "✗ Erreur lors de la migration : " . $e->getMessage() . "\n";
    exit(1);
}

