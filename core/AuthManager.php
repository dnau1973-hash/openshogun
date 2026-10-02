<?php
/**
 * AuthManager - Gestionnaire centralisé d'autorisations & vérifications de métiers (Jobs/Roles)
 * OpenShogun Dev Studio
 */
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/DevTeamEngine.php';
require_once __DIR__ . '/Database.php';

class AuthManager {
    /**
     * Vérifie si l'utilisateur courant (ou spécifié) possède un métier spécifique
     * Exemples: 'game-elevate-designer', 'game_designer', 'qa-tester', etc.
     */
    public static function hasJob(string $jobSlug, ?int $userId = null): bool {
        $auth = new Auth();
        $uid = $userId ?? (int)Auth::id();
        if ($uid <= 0) {
            return false;
        }

        // Si l'utilisateur est administrateur suprême, il a un passe-droit de studio
        if (self::isAdmin($uid)) {
            return true;
        }

        $normalizedJob = str_replace('-', '_', strtolower(trim($jobSlug)));
        $jobMap = [
            'game_elevate_designer' => 'game_designer',
            'game_designer'         => 'game_designer',
            'community_manager'     => 'community_manager',
            'qa_tester'             => 'qa_tester',
            'backend_dev'           => 'backend_dev',
            'narrative_designer'    => 'narrative_designer',
            'sound_designer'        => 'sound_designer',
            'producer'              => 'producer',
        ];
        $roleId = $jobMap[$normalizedJob] ?? $normalizedJob;

        $devEngine = new DevTeamEngine();
        $userRoles = $devEngine->getUserRoles($uid);
        $roleIds = array_column($userRoles, 'id');

        return in_array($roleId, $roleIds, true);
    }

    /**
     * Vérifie si un utilisateur est administrateur
     */
    public static function isAdmin(?int $userId = null): bool {
        $auth = new Auth();
        if ($userId === null || $userId === (int)Auth::id()) {
            return $auth->isAdmin();
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return ((int)$stmt->fetchColumn() === 1);
        } catch (Exception $e) {
            return false;
        }
    }
}
