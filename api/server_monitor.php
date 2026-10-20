<?php
/**
 * API REST Endpoint : Supervision Serveur & Matériel Raspberry Pi
 * Fichier : api/server_monitor.php
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/GameStudioEngine.php';
require_once __DIR__ . '/../core/ServerMonitorEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$userId = (int)Auth::id();
$gameStudioEngine = new GameStudioEngine();
$isAdmin = $auth->isAdmin() || Auth::isAdmin();
$isStudioMember = $isAdmin || $gameStudioEngine->isAuthorized($userId);

if (!$isStudioMember) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès restreint au Game Studio.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'metrics';
$engine = new ServerMonitorEngine();

try {
    switch ($action) {
        case 'metrics':
        case 'diagnostics':
            $data = $engine->getSystemDiagnostics();
            echo json_encode([
                'success' => true,
                'data'    => $data
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        case 'ping_db':
            $metrics = $engine->getDatabaseMetrics();
            echo json_encode([
                'success' => ($metrics['latency_ms'] >= 0),
                'latency' => $metrics['latency_ms'],
                'data'    => $metrics,
                'message' => ($metrics['latency_ms'] >= 0)
                    ? "Connexion SQL réactive ({$metrics['latency_ms']} ms)"
                    : "Erreur de connexion à la base de données."
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'reset_opcache':
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Privilège administrateur requis.']);
                exit;
            }
            $res = $engine->resetOpcache();
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error'   => "Action non reconnue : $action"
            ]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

