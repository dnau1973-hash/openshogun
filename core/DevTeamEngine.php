<?php
/**
 * DevTeamEngine — Moteur de gestion de la Dev Team & Métiers du Jeu Vidéo
 * Architecture RBAC, Gestion des Rôles, Permissions & Gamification (Forge XP)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class DevTeamEngine {

    // ── Définition des 8 Métiers de l'industrie du jeu vidéo ─────────────────
    public const ROLES = [
        'game_designer' => [
            'id'          => 'game_designer',
            'title'       => 'Game & Level Designer',
            'honor_title' => 'L\'Architecte des Mondes',
            'icon'        => '📐',
            'badge_color' => 'bg-green-lt text-green',
            'category'    => 'Design',
            'description' => 'Équilibrage des formules de combat et production, arpentage des tuiles de la carte et tables de butin.',
            'default_permissions' => [
                'map.edit',
                'formulas.tune',
                'loot.manage',
                'debug.sandbox'
            ]
        ],
        'narrative_designer' => [
            'id'          => 'narrative_designer',
            'title'       => 'Narrative Designer',
            'honor_title' => 'Le Grand Chroniqueur',
            'icon'        => '📜',
            'badge_color' => 'bg-purple-lt text-purple',
            'category'    => 'Lore',
            'description' => 'Rédaction des quêtes scénarisées, chroniques de l\'archipel, dialogues de PNJ et décrets impériaux.',
            'default_permissions' => [
                'quests.manage',
                'lore.publish'
            ]
        ],
        'frontend_dev' => [
            'id'          => 'frontend_dev',
            'title'       => 'Développeur Frontend',
            'honor_title' => 'Le Tisserand d\'Illusions',
            'icon'        => '🎨',
            'badge_color' => 'bg-cyan-lt text-cyan',
            'category'    => 'Code',
            'description' => 'Conception des interfaces Tabler.io, intégration responsive, animations DOM, timers et réactivité client.',
            'default_permissions' => [
                'ui.inspect',
                'assets.manage'
            ]
        ],
        'backend_dev' => [
            'id'          => 'backend_dev',
            'title'       => 'Développeur Backend & Réseau',
            'honor_title' => 'Le Maître des Engrenages',
            'icon'        => '⚙️',
            'badge_color' => 'bg-indigo-lt text-indigo',
            'category'    => 'Code',
            'description' => 'Moteurs de jeu, calculs d\'intégrité, base de données PDO, cycles de cron, sécurité et API REST.',
            'default_permissions' => [
                'system.monitoring',
                'cron.trigger',
                'debug.sandbox'
            ]
        ],
        'artist' => [
            'id'          => 'artist',
            'title'       => 'Artiste 2D/3D & UI/UX',
            'honor_title' => 'L\'Enlumineur Céleste',
            'icon'        => '🖌️',
            'badge_color' => 'bg-pink-lt text-pink',
            'category'    => 'Art',
            'description' => 'Direction artistique féodale, icônes, bannières impériales, illustrations de bâtiments et cohérence visuelle.',
            'default_permissions' => [
                'assets.manage',
                'ui.inspect'
            ]
        ],
        'sound_designer' => [
            'id'          => 'sound_designer',
            'title'       => 'Sound Designer & Compositeur',
            'honor_title' => 'L\'Harmoniste de Guerre',
            'icon'        => '🎵',
            'badge_color' => 'bg-yellow-lt text-yellow',
            'category'    => 'Audio',
            'description' => 'Compositions musicales traditionnelles, bruitages martiaux (SFX), ambiance sonore et spatialisation audio.',
            'default_permissions' => [
                'audio.manage'
            ]
        ],
        'qa_tester' => [
            'id'          => 'qa_tester',
            'title'       => 'QA Tester (Assurance Qualité)',
            'honor_title' => 'L\'Inquisiteur des Failles',
            'icon'        => '🔍',
            'badge_color' => 'bg-danger-lt text-danger',
            'category'    => 'Qualité',
            'description' => 'Traque de bugs, tests de non-régression, vérification des limites mécaniques et validation des releases.',
            'default_permissions' => [
                'debug.sandbox',
                'bugs.manage'
            ]
        ],
        'producer' => [
            'id'          => 'producer',
            'title'       => 'Producteur & Live Ops',
            'honor_title' => 'Le Stratège de la Forge',
            'icon'        => '👑',
            'badge_color' => 'bg-warning-lt text-warning',
            'category'    => 'Direction',
            'description' => 'Coordination des sprints père-fils, arbitrage de la roadmap, attribution des tickets et déploiement continu.',
            'default_permissions' => [
                'sprints.manage',
                'team.manage',
                'system.monitoring',
                'cron.trigger',
                'debug.sandbox',
                'bugs.manage',
                'community.mailing'
            ]
        ],
        'community_manager' => [
            'id'          => 'community_manager',
            'title'       => 'Community Manager',
            'honor_title' => 'La Voix du Shōgunat',
            'icon'        => '📢',
            'badge_color' => 'bg-teal-lt text-teal',
            'category'    => 'Communauté',
            'description' => 'Animation de la communauté, relations joueurs, dépêches impériales, modération et gestion des campagnes de mailing list.',
            'default_permissions' => [
                'community.mailing',
                'lore.publish',
                'news.manage'
            ]
        ]
    ];

    // ── Définition de toutes les permissions unitaires ─────────────────────────
    public const PERMISSIONS = [
        'map.edit'           => ['label' => 'Édition de Carte & Tuiles', 'cat' => 'Design', 'desc' => 'Modifier le monde féodal et placer des éléments.'],
        'formulas.tune'      => ['label' => 'Ajustement des Formules', 'cat' => 'Design', 'desc' => 'Régler la production, coûts et temps de jeu.'],
        'loot.manage'        => ['label' => 'Tables de Butin', 'cat' => 'Design', 'desc' => 'Configurer les récompenses des donjons et oasis.'],
        'quests.manage'      => ['label' => 'Création de Quêtes', 'cat' => 'Lore', 'desc' => 'Rédiger et tester les missions de l\'univers.'],
        'lore.publish'       => ['label' => 'Publication du Lore', 'cat' => 'Lore', 'desc' => 'Diffuser chroniques et annonces immersives.'],
        'ui.inspect'         => ['label' => 'Inspecteur d\'Interface', 'cat' => 'Frontend', 'desc' => 'Contrôler le rendu, CSS et composants visuels.'],
        'assets.manage'      => ['label' => 'Gestionnaire d\'Assets', 'cat' => 'Art', 'desc' => 'Organiser la bibliothèque multimédia et visuels.'],
        'audio.manage'       => ['label' => 'Console Audio & SFX', 'cat' => 'Audio', 'desc' => 'Gérer les pistes sonores et bruitages.'],
        'system.monitoring'  => ['label' => 'Monitoring Système', 'cat' => 'Backend', 'desc' => 'Surveiller la mémoire, les requêtes et charges.'],
        'cron.trigger'       => ['label' => 'Déclencheur de Crons', 'cat' => 'Backend', 'desc' => 'Forcer manuellement les boucles de calculs.'],
        'debug.sandbox'      => ['label' => 'Commandes Sandbox / Triche', 'cat' => 'QA', 'desc' => 'Accéder aux commandes de test et d\'injection.'],
        'bugs.manage'        => ['label' => 'Gestion Avancée des Bugs', 'cat' => 'QA', 'desc' => 'Qualifier, valider et clôturer les signalements.'],
        'sprints.manage'     => ['label' => 'Gestion de Sprint & Roadmap', 'cat' => 'Direction', 'desc' => 'Piloter le calendrier des releases.'],
        'team.manage'        => ['label' => 'Gestion de la Dev Team', 'cat' => 'Direction', 'desc' => 'Attribuer et révoquer les métiers de l\'équipe.'],
        'community.mailing'  => ['label' => 'Campagnes Mailing List & Newsletter', 'cat' => 'Communauté', 'desc' => 'Composer et expédier les lettres impériales et newsletters aux joueurs.'],
        'news.manage'        => ['label' => 'Gestion des Annonces & Actualités', 'cat' => 'Communauté', 'desc' => 'Publier et modérer les actualités et dépêches du Shōgunat.']
    ];

    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Database::getConnection();
        $this->ensureDevTeamTables();
    }

    /**
     * Initialise et garantit l'existence des tables RBAC et Forge XP
     */
    public function ensureDevTeamTables(): void {
        try {
            // 1. Table dev_roles
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `dev_roles` (
                  `id` VARCHAR(32) PRIMARY KEY,
                  `title` VARCHAR(64) NOT NULL,
                  `honor_title` VARCHAR(64) NOT NULL,
                  `icon` VARCHAR(16) NOT NULL DEFAULT '🛠️',
                  `category` VARCHAR(32) NOT NULL DEFAULT 'General',
                  `description` TEXT NULL,
                  `display_order` INT NOT NULL DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Table dev_permissions
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `dev_permissions` (
                  `id` VARCHAR(64) PRIMARY KEY,
                  `label` VARCHAR(128) NOT NULL,
                  `category` VARCHAR(32) NOT NULL,
                  `description` TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Table dev_role_permissions
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `dev_role_permissions` (
                  `role_id` VARCHAR(32) NOT NULL,
                  `permission_id` VARCHAR(64) NOT NULL,
                  PRIMARY KEY (`role_id`, `permission_id`),
                  CONSTRAINT `fk_drp_role` FOREIGN KEY (`role_id`) REFERENCES `dev_roles` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `fk_drp_perm` FOREIGN KEY (`permission_id`) REFERENCES `dev_permissions` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. Table user_dev_roles
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `user_dev_roles` (
                  `user_id` INT UNSIGNED NOT NULL,
                  `role_id` VARCHAR(32) NOT NULL,
                  `assigned_at` INT UNSIGNED NOT NULL,
                  PRIMARY KEY (`user_id`, `role_id`),
                  CONSTRAINT `fk_udr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `fk_udr_role` FOREIGN KEY (`role_id`) REFERENCES `dev_roles` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 5. Table user_dev_overrides
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `user_dev_overrides` (
                  `user_id` INT UNSIGNED NOT NULL,
                  `permission_id` VARCHAR(64) NOT NULL,
                  `is_granted` TINYINT(1) NOT NULL DEFAULT 1,
                  PRIMARY KEY (`user_id`, `permission_id`),
                  CONSTRAINT `fk_udo_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `fk_udo_perm` FOREIGN KEY (`permission_id`) REFERENCES `dev_permissions` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 6. Table dev_forge_xp (Gamification)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `dev_forge_xp` (
                  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  `user_id` INT UNSIGNED NOT NULL,
                  `xp_amount` INT NOT NULL DEFAULT 0,
                  `action_type` VARCHAR(64) NOT NULL,
                  `description` VARCHAR(255) NULL,
                  `created_at` INT UNSIGNED NOT NULL,
                  KEY `idx_dev_xp_user` (`user_id`),
                  CONSTRAINT `fk_dfx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed des données de référence si nécessaire
            $this->seedReferenceData();

        } catch (Exception $e) {
            // Silencieux si déjà configuré
        }
    }

    /**
     * Remplissage automatique des rôles, permissions et associations
     */
    private function seedReferenceData(): void {
        // 1. Synchroniser / insérer tous les rôles définis dans self::ROLES
        $stmtR = $this->db->prepare("
            INSERT INTO dev_roles (id, title, honor_title, icon, category, description, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                title = VALUES(title), 
                honor_title = VALUES(honor_title), 
                icon = VALUES(icon), 
                category = VALUES(category), 
                description = VALUES(description)
        ");
        $order = 1;
        foreach (self::ROLES as $r) {
            $stmtR->execute([
                $r['id'],
                $r['title'],
                $r['honor_title'],
                $r['icon'],
                $r['category'],
                $r['description'],
                $order++
            ]);
        }

        // 2. Synchroniser / insérer toutes les permissions
        $stmtP = $this->db->prepare("
            INSERT INTO dev_permissions (id, label, category, description)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                category = VALUES(category),
                description = VALUES(description)
        ");
        foreach (self::PERMISSIONS as $pId => $pData) {
            $stmtP->execute([$pId, $pData['label'], $pData['cat'], $pData['desc']]);
        }

        // 3. Associer les permissions par défaut à leurs rôles
        $stmtRP = $this->db->prepare("
            INSERT IGNORE INTO dev_role_permissions (role_id, permission_id)
            VALUES (?, ?)
        ");
        foreach (self::ROLES as $rId => $rData) {
            foreach ($rData['default_permissions'] as $perm) {
                $stmtRP->execute([$rId, $perm]);
            }
        }

        // 4. Initialiser le premier administrateur si aucun rôle n'a encore été attribué
        $hasAnyAssigned = (int)$this->db->query("SELECT COUNT(*) FROM user_dev_roles")->fetchColumn();
        if ($hasAnyAssigned === 0) {
            $adminUser = $this->db->query("SELECT id FROM users WHERE is_admin = 1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($adminUser) {
                $firstAdminId = (int)$adminUser['id'];
                $this->assignRole($firstAdminId, 'producer');
                $this->assignRole($firstAdminId, 'backend_dev');
                $this->addForgeXp($firstAdminId, 250, 'init_forge', 'Fondation du studio de développement Shōgun');
            }
        }
    }

    /**
     * Vérifie si un utilisateur est membre officiel de la Dev Team
     */
    public function isDevTeamMember(int $userId): bool {
        if ($userId <= 0) return false;
        
        // Tout administrateur suprême est membre de droit
        $stmtAdmin = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmtAdmin->execute([$userId]);
        if ((int)$stmtAdmin->fetchColumn() === 1) {
            return true;
        }

        $stmt = $this->db->prepare("SELECT 1 FROM user_dev_roles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Récupère tous les rôles affectés à un utilisateur
     */
    public function getUserRoles(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, ur.assigned_at
            FROM user_dev_roles ur
            JOIN dev_roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
            ORDER BY r.display_order ASC
        ");
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si l'utilisateur est admin et n'a pas encore de rôle en base, on lui attribue virtuellement Producteur
        if (empty($roles)) {
            $stmtAdmin = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmtAdmin->execute([$userId]);
            if ((int)$stmtAdmin->fetchColumn() === 1) {
                return [self::ROLES['producer']];
            }
        }

        return $roles;
    }

    /**
     * Attribue plusieurs rôles métiers à un joueur en bloquant strictement les doublons
     * @param int $userId
     * @param array<string> $roleIds
     * @return array<array> Liste des rôles nouvellement assignés
     */
    public function assignRoles(int $userId, array $roleIds): array {
        if ($userId <= 0 || empty($roleIds)) return [];

        $assigned = [];
        $checkStmt = $this->db->prepare("SELECT 1 FROM user_dev_roles WHERE user_id = ? AND role_id = ? LIMIT 1");
        $insertStmt = $this->db->prepare("
            INSERT INTO user_dev_roles (user_id, role_id, assigned_at)
            VALUES (?, ?, ?)
        ");

        foreach ($roleIds as $rId) {
            $rId = trim((string)$rId);
            if (!isset(self::ROLES[$rId])) continue;

            // Bloquer les doublons : vérifier si déjà attribué
            $checkStmt->execute([$userId, $rId]);
            if ($checkStmt->fetchColumn()) {
                continue;
            }

            if ($insertStmt->execute([$userId, $rId, time()])) {
                $roleMeta = self::ROLES[$rId];
                $this->addForgeXp($userId, 50, 'role_assigned', "Attribution du métier '{$roleMeta['title']}'");
                $assigned[] = $roleMeta;
            }
        }

        return $assigned;
    }

    /**
     * Attribue un rôle métier à un joueur
     */
    public function assignRole(int $userId, string $roleId): bool {
        $res = $this->assignRoles($userId, [$roleId]);
        return !empty($res);
    }

    /**
     * Révoque un rôle métier
     */
    public function removeRole(int $userId, string $roleId): bool {
        $stmt = $this->db->prepare("DELETE FROM user_dev_roles WHERE user_id = ? AND role_id = ?");
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Calcule l'ensemble des permissions effectives d'un utilisateur (Rôles + Overrides)
     * @return array<string>
     */
    public function getEffectivePermissions(int $userId): array {
        // L'admin suprême a accès à absolument tout
        $stmtAdmin = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmtAdmin->execute([$userId]);
        if ((int)$stmtAdmin->fetchColumn() === 1) {
            return array_keys(self::PERMISSIONS);
        }

        // Permissions des rôles
        $stmt = $this->db->prepare("
            SELECT DISTINCT rp.permission_id
            FROM user_dev_roles ur
            JOIN dev_role_permissions rp ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        $rolePerms = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Overrides
        $stmtO = $this->db->prepare("SELECT permission_id, is_granted FROM user_dev_overrides WHERE user_id = ?");
        $stmtO->execute([$userId]);
        $overrides = $stmtO->fetchAll(PDO::FETCH_KEY_PAIR);

        $effective = array_flip($rolePerms);
        foreach ($overrides as $perm => $granted) {
            if ($granted) {
                $effective[$perm] = true;
            } else {
                unset($effective[$perm]);
            }
        }

        return array_keys($effective);
    }

    /**
     * Vérifie si un joueur possède une permission spécifique
     */
    public function hasPermission(int $userId, string $permission): bool {
        $perms = $this->getEffectivePermissions($userId);
        return in_array($permission, $perms, true);
    }

    /**
     * Middleware d'autorisation (garde) : bloque la requête si la permission est absente
     */
    public static function authorize(string $permission, ?int $userId = null): void {
        $auth = new Auth();
        if ($auth->isAdmin()) {
            return; // Passe-droit pour l'administrateur
        }

        $uid = $userId ?? (int)Auth::id();
        $engine = new self();
        if (!$engine->hasPermission($uid, $permission)) {
            http_response_code(403);
            if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error'   => "Accès refusé : la permission Dev Team '{$permission}' est requise."
                ]);
            } else {
                echo "<div class='alert alert-danger m-4'><h4>⛔ Accès Restreint</h4>Vous n'avez pas l'habilitation Dev Team requise (<strong>{$permission}</strong>).</div>";
            }
            exit;
        }
    }

    // ── GAMIFICATION : FORGE XP & NIVEAUX ──────────────────────────────────────

    /**
     * Ajoute de l'XP de forge à un membre
     */
    public function addForgeXp(int $userId, int $xp, string $actionType, string $description = ''): void {
        $stmt = $this->db->prepare("
            INSERT INTO dev_forge_xp (user_id, xp_amount, action_type, description, created_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $xp, $actionType, $description, time()]);
    }

    /**
     * Calcule le total d'XP de forge d'un membre
     */
    public function getTotalForgeXp(int $userId): int {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(xp_amount), 0) FROM dev_forge_xp WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Détermine le niveau de créateur et le titre honorifique d'après l'XP
     */
    public static function calculateLevel(int $totalXp): array {
        $tiers = [
            ['level' => 1, 'min_xp' => 0,    'title' => 'Apprenti Forgeron',          'badge' => 'bg-secondary-lt'],
            ['level' => 2, 'min_xp' => 150,  'title' => 'Artisan du Code & Lore',     'badge' => 'bg-cyan-lt'],
            ['level' => 3, 'min_xp' => 450,  'title' => 'Maître Bâtisseur Féodal',    'badge' => 'bg-info-lt'],
            ['level' => 4, 'min_xp' => 1000, 'title' => 'Grand Concepteur du Royaume', 'badge' => 'bg-purple-lt'],
            ['level' => 5, 'min_xp' => 2000, 'title' => 'Légende Vivante du Studio',   'badge' => 'bg-warning-lt'],
        ];

        $currentTier = $tiers[0];
        $nextTier = $tiers[1] ?? null;

        for ($i = count($tiers) - 1; $i >= 0; $i--) {
            if ($totalXp >= $tiers[$i]['min_xp']) {
                $currentTier = $tiers[$i];
                $nextTier = $tiers[$i + 1] ?? null;
                break;
            }
        }

        $progressPct = 100;
        if ($nextTier) {
            $range = $nextTier['min_xp'] - $currentTier['min_xp'];
            $current = $totalXp - $currentTier['min_xp'];
            $progressPct = min(100, max(0, round(($current / max(1, $range)) * 100)));
        }

        return [
            'level'       => $currentTier['level'],
            'title'       => $currentTier['title'],
            'badge'       => $currentTier['badge'],
            'xp'          => $totalXp,
            'next_min_xp' => $nextTier ? $nextTier['min_xp'] : null,
            'progress_pct'=> $progressPct
        ];
    }

    /**
     * Récupère le profil créateur complet pour l'UI ou l'API
     */
    public function getDevProfile(int $userId): ?array {
        if (!$this->isDevTeamMember($userId)) return null;

        $stmt = $this->db->prepare("SELECT id, username, faction, is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;

        $roles = $this->getUserRoles($userId);
        $perms = $this->getEffectivePermissions($userId);
        $totalXp = $this->getTotalForgeXp($userId);
        $levelInfo = self::calculateLevel($totalXp);

        return [
            'user_id'     => (int)$user['id'],
            'username'    => $user['username'],
            'faction'     => $user['faction'],
            'is_admin'    => (bool)$user['is_admin'],
            'roles'       => $roles,
            'permissions' => $perms,
            'forge_level' => $levelInfo,
            'primary_role'=> $roles[0] ?? self::ROLES['game_designer']
        ];
    }

    /**
     * Récupère la liste de tous les membres de la Dev Team
     */
    public function getAllDevTeamMembers(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT u.id, u.username, u.faction, u.is_admin
            FROM users u
            WHERE u.is_admin = 1 
               OR u.id IN (SELECT user_id FROM user_dev_roles)
            ORDER BY u.id ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $members = [];
        foreach ($users as $u) {
            $prof = $this->getDevProfile((int)$u['id']);
            if ($prof) $members[] = $prof;
        }
        return $members;
    }

    /**
     * Historique des actions de forge
     */
    public function getForgeHistory(int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT fx.*, u.username
            FROM dev_forge_xp fx
            JOIN users u ON fx.user_id = u.id
            ORDER BY fx.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
