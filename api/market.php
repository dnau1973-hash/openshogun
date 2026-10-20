<?php
/**
 * API Marché Féodal - Gestion de la Bourse d'Échanges P2P
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$planetId = (int)($_SESSION['current_planet_id'] ?? 0);

if (!$planetId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Aucun fief sélectionné.']);
    exit;
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/MarketEngine.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$marketEngine = new MarketEngine();

try {
    switch ($action) {
        case 'create_offer':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête non autorisée.");
            }
            $offerRes = trim($_POST['offer_res'] ?? '');
            $offerAmount = (int)($_POST['offer_amount'] ?? 0);
            $demandRes = trim($_POST['demand_res'] ?? '');
            $demandAmount = (int)($_POST['demand_amount'] ?? 0);

            $result = $marketEngine->createOffer($userId, $planetId, $offerRes, $offerAmount, $demandRes, $demandAmount);
            echo json_encode($result);
            break;

        case 'cancel_offer':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête non autorisée.");
            }
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $result = $marketEngine->cancelOffer($userId, $offerId);
            echo json_encode($result);
            break;

        case 'accept_offer':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode de requête non autorisée.");
            }
            $offerId = (int)($_POST['offer_id'] ?? 0);
            $result = $marketEngine->acceptOffer($userId, $planetId, $offerId);
            echo json_encode($result);
            break;

        case 'list_offers':
            $filterOffer = !empty($_GET['offer_res']) ? trim($_GET['offer_res']) : null;
            $filterDemand = !empty($_GET['demand_res']) ? trim($_GET['demand_res']) : null;
            $offers = $marketEngine->getActiveOffers($filterOffer, $filterDemand, 50);
            $tradables = MarketEngine::getTradableResources();
            echo json_encode([
                'success' => true,
                'offers' => $offers,
                'tradables' => $tradables
            ]);
            break;

        case 'my_offers':
            $myOffers = $marketEngine->getUserOffers($userId);
            $marketLvl = $marketEngine->getMarketLevel($planetId);
            $maxSlots = $marketEngine->getMaxOfferSlots($marketLvl);
            echo json_encode([
                'success' => true,
                'my_offers' => $myOffers,
                'market_level' => $marketLvl,
                'max_slots' => $maxSlots
            ]);
            break;

        case 'exchange_directions':
            $summary = $marketEngine->getExchangeDirectionsSummary();
            $tradables = MarketEngine::getTradableResources();
            echo json_encode([
                'success' => true,
                'summary' => $summary,
                'tradables' => $tradables
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Action '{$action}' inconnue."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

