<?php
/**
 * API Endpoint: Lancement de recherche technologique
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ResearchEngine.php';

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

$researchEngine = new ResearchEngine();
$code = $_POST['research_code'] ?? '';

try {
    $result = $researchEngine->startResearch((int)$user['id'], (int)$planet['id'], $code);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

