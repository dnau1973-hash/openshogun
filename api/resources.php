<?php
/**
 * API Endpoint: Synchronisation des ressources et files d'attente
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/PlanetEngine.php';
require_once __DIR__ . '/../core/BuildingEngine.php';
require_once __DIR__ . '/../core/FleetEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['error' => 'Aucune planète active']);
    exit;
}

$planetEngine = new PlanetEngine();
$buildingEngine = new BuildingEngine();
$fleetEngine = new FleetEngine();

// Mettre à jour les flottes globales
$fleetEngine->processFleetMissions();

// Mettre à jour la planète
$updatedPlanet = $planetEngine->updatePlanet((int)$planet['id']);
$queue = $buildingEngine->getQueue((int)$planet['id']);

echo json_encode([
    'success' => true,
    'planet' => [
        'id' => $updatedPlanet['id'],
        'name' => $updatedPlanet['name'],
        'metal' => (int)$updatedPlanet['metal'],
        'crystal' => (int)$updatedPlanet['crystal'],
        'deuterium' => (int)$updatedPlanet['deuterium'],
        'metal_max' => (int)$updatedPlanet['metal_max'],
        'crystal_max' => (int)$updatedPlanet['crystal_max'],
        'deuterium_max' => (int)$updatedPlanet['deuterium_max'],
        'energy_used' => (int)$updatedPlanet['energy_used'],
        'energy_max' => (int)$updatedPlanet['energy_max'],
        'prod_metal' => (int)$updatedPlanet['prod_rates']['metal'],
        'prod_crystal' => (int)$updatedPlanet['prod_rates']['crystal'],
        'prod_deuterium' => (int)$updatedPlanet['prod_rates']['deuterium'],
    ],
    'queue' => $queue,
    'server_time' => time()
]);

