<?php
/**
 * Migration 010 : Rééquilibrage et Dissémination Homogène des Oasis Sauvages
 * OpenShogun — Écosystème Spatial du Japon Féodal
 */
require_once __DIR__ . '/../../core/OasisEngine.php';
require_once __DIR__ . '/../../core/GameConfig.php';

try {
    $oasisEngine = new OasisEngine($db);
    $density = (float)GameConfig::get('oasis_density_percent', 2.0);
    $radius = (int)GameConfig::getWorldRadius();

    // Régénère les oasis sauvages de manière homogène sur tout le rayon mondial (sans toucher aux oasis annexées)
    $oasisEngine->spawnOasesByDensity($density, $radius, true);

    // Mettre à jour l'ensemencement des lacs et monts si nécessaire
    require_once __DIR__ . '/../../core/LakeEngine.php';
    $lakeEngine = new LakeEngine($db);
    $lakeEngine->seedWorldLakes($radius + 5);

    require_once __DIR__ . '/../../core/MountainEngine.php';
    $mountainEngine = new MountainEngine($db);
    $mountainEngine->seedWorldMountains($radius + 5);

} catch (Throwable $e) {
    error_log("[Migration 010] Erreur lors du rééquilibrage des oasis : " . $e->getMessage());
}
