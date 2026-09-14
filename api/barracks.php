<?php
/**
 * API Endpoint: Entraînement de soldats et troupes militaires
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/BarracksEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
if (!$planet) {
    echo json_encode(['success' => false, 'error' => 'Aucune planète active']);
    exit;
}

$barracksEngine = new BarracksEngine();
$unitCode = $_POST['unit_code'] ?? '';
$count = (int)($_POST['count'] ?? 0);

try {
    $result = $barracksEngine->trainUnits((int)$planet['id'], $unitCode, $count, $user['faction']);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

