<?php
/**
 * API Endpoint : Gestion des Chroniques & Rapports Militaires
 * Suppression unitaire, purge complète et marquage
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';

$auth = new Auth();
if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Session expirée ou utilisateur non connecté.']);
    exit;
}

$user = $auth->getCurrentUser();
$userId = (int)$user['id'];
$db = Database::getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'delete') {
        $reportId = (int)($_POST['id'] ?? 0);
        if ($reportId <= 0) {
            throw new Exception("Identifiant de rapport invalide.");
        }

        // Vérifier que le rapport appartient bien au joueur connecté (attaquant ou défenseur)
        $stmt = $db->prepare("
            DELETE FROM combat_reports 
            WHERE id = ? AND (attacker_id = ? OR defender_id = ?)
        ");
        $stmt->execute([$reportId, $userId, $userId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'La chronique militaire a été définitivement supprimée.'
            ]);
        } else {
            throw new Exception("Rapport introuvable ou vous n'avez pas l'autorisation de le supprimer.");
        }
    } elseif ($action === 'delete_all') {
        // Purge de l'ensemble des rapports du joueur connecté
        $stmt = $db->prepare("
            DELETE FROM combat_reports 
            WHERE attacker_id = ? OR defender_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        $deletedCount = $stmt->rowCount();

        echo json_encode([
            'success' => true,
            'message' => "Toutes vos chroniques militaires ont été purgées ({$deletedCount} rapport(s) supprimé(s)).",
            'deleted_count' => $deletedCount
        ]);
    } elseif ($action === 'mark_all_read') {
        $db->prepare("UPDATE combat_reports SET read_by_attacker = 1 WHERE attacker_id = ?")->execute([$userId]);
        $db->prepare("UPDATE combat_reports SET read_by_defender = 1 WHERE defender_id = ?")->execute([$userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Tous les rapports ont été marqués comme consultés.'
        ]);
    } else {
        throw new Exception("Action non reconnue.");
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
