<?php
/**
 * Migration : Renommage des codes de la table units vers des identifiants féodaux authentiques
 * OpenShogun - Remplacement des anciens codes galactiques (terran_marine, vorash_skitter, etc.)
 */
require_once __DIR__ . '/../core/Database.php';

echo "=== MIGRATION DES CODES DE LA TABLE UNITS ===\n";

$db = Database::getConnection();

// Mapping des anciens codes galactiques vers les nouveaux codes Shogun
$mapping = [
    // Clan Oda
    'terran_marine'       => 'piquier_ashigaru_yari',
    'terran_sentinel'     => 'arquebusier_oda_tanegashima',
    'terran_exo_assault'  => 'samourai_katana',
    'terran_titan_mech'   => 'garde_hatamoto_armure_lourde',
    
    // Clan Takeda
    'vorash_skitter'      => 'fantassin_leger_takeda',
    'vorash_chitin'       => 'archer_yumi_monte',
    'vorash_berserker'    => 'cavalier_rouge_akazonae',
    'vorash_goliath'      => 'maitre_nodachi_kai',
    
    // Clan Tokugawa
    'aethelis_initiate'   => 'sentinelle_yari_tokugawa',
    'aethelis_phalanx'    => 'archer_protecteur_muraille',
    'aethelis_shadow'     => 'ombre_shinobi_infiltree',
    'aethelis_colossus'   => 'hatamoto_venerable_tokugawa'
];

try {
    // 1. Suppression temporaire de la clé étrangère pour permettre la mise à jour
    echo "1. Suppression de la contrainte fk_units_code sur planet_units...\n";
    try {
        $db->exec("ALTER TABLE planet_units DROP FOREIGN KEY fk_units_code");
    } catch (Exception $e) {
        echo "   (Note: " . $e->getMessage() . ")\n";
    }

    // 2. Extension de la taille des colonnes à VARCHAR(60) pour accueillir les noms complets
    echo "2. Extension des colonnes à VARCHAR(60)...\n";
    $db->exec("ALTER TABLE units MODIFY code VARCHAR(60) NOT NULL");
    $db->exec("ALTER TABLE planet_units MODIFY unit_code VARCHAR(60) NOT NULL");
    $db->exec("ALTER TABLE barracks_queue MODIFY unit_code VARCHAR(60) NOT NULL");

    // 3. Mise à jour de la table units
    echo "3. Mise à jour des identifiants dans la table 'units'...\n";
    $stmtUnits = $db->prepare("UPDATE units SET code = ? WHERE code = ?");
    foreach ($mapping as $oldCode => $newCode) {
        $stmtUnits->execute([$newCode, $oldCode]);
        echo "   - {$oldCode} -> {$newCode}\n";
    }

    // 4. Mise à jour de la table planet_units
    echo "4. Mise à jour des garnisons dans 'planet_units'...\n";
    $stmtPlanetUnits = $db->prepare("UPDATE planet_units SET unit_code = ? WHERE unit_code = ?");
    foreach ($mapping as $oldCode => $newCode) {
        $stmtPlanetUnits->execute([$newCode, $oldCode]);
    }

    // 5. Mise à jour de la table barracks_queue
    echo "5. Mise à jour de la file d'entraînement dans 'barracks_queue'...\n";
    $stmtQueue = $db->prepare("UPDATE barracks_queue SET unit_code = ? WHERE unit_code = ?");
    foreach ($mapping as $oldCode => $newCode) {
        $stmtQueue->execute([$newCode, $oldCode]);
    }

    // 6. Rétablissement de la contrainte de clé étrangère
    echo "6. Réactivation de la contrainte fk_units_code sur planet_units...\n";
    $db->exec("ALTER TABLE planet_units ADD CONSTRAINT fk_units_code FOREIGN KEY (unit_code) REFERENCES units(code) ON DELETE CASCADE");

    echo "\n=== MIGRATION RÉUSSIE AVEC SUCCÈS ! ===\n";

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
