<?php
/**
 * ScenarioEngine - Moteur de Scénarisation & Matrice des Tâches Métiers
 * Module Narrative Designer (OpenShogun Game Studio)
 * Fichier : core/ScenarioEngine.php
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/GameConfig.php';

class ScenarioEngine {
    private ?PDO $db;

    public const VALID_TYPES = ['step', 'special_event'];
    public const VALID_STATUSES = ['draft', 'in_review', 'in_production', 'ready', 'published', 'archived'];
    public const VALID_TASK_STATUSES = ['todo', 'in_progress', 'done', 'blocked'];
    public const VALID_COMPLEXITIES = ['S', 'M', 'L', 'XL'];

    /**
     * Matrice des 10 Métiers Clés de Développement du Jeu
     */
    public const ROLES = [
        'level_designer' => [
            'code'        => 'level_designer',
            'name'        => 'Game & Level Designer',
            'icon'        => 'fa-compass-drafting',
            'color'       => 'warning',
            'badge_class' => 'bg-warning-lt text-warning',
            'domain'      => 'Gameplay & World Building',
            'desc'        => 'Progression, flow du joueur, placement des POI, équilibrage des courbes et objectifs'
        ],
        'backend' => [
            'code'        => 'backend',
            'name'        => 'Développeur Back-End',
            'icon'        => 'fa-server',
            'color'       => 'teal',
            'badge_class' => 'bg-teal-lt text-teal',
            'domain'      => 'Moteur & Base de Données',
            'desc'        => 'Moteurs PHP, triggers d\'événements, cron, tables SQL, intégrité transactionnelle'
        ],
        'frontend' => [
            'code'        => 'frontend',
            'name'        => 'Développeur Front-End',
            'icon'        => 'fa-code',
            'color'       => 'info',
            'badge_class' => 'bg-info-lt text-info',
            'domain'      => 'Interface & UX',
            'desc'        => 'Intégration UI Tabler, modales interactives, animations CSS, responsive mobile'
        ],
        'network' => [
            'code'        => 'network',
            'name'        => 'Développeur Réseau & Infra',
            'icon'        => 'fa-network-wired',
            'color'       => 'primary',
            'badge_class' => 'bg-primary-lt text-primary',
            'domain'      => 'Réseau & Synchronisation',
            'desc'        => 'Sockets temps réel, webhooks/API, latence, gestion de charge des events collectifs'
        ],
        'artist_2d' => [
            'code'        => 'artist_2d',
            'name'        => 'Game Artist 2D & Ukiyo-e',
            'icon'        => 'fa-palette',
            'color'       => 'danger',
            'badge_class' => 'bg-danger-lt text-danger',
            'domain'      => 'Direction Artistique 2D',
            'desc'        => 'Portraits de personnages, icônes d\'items, estampes de paysages et illustrations'
        ],
        'artist_env' => [
            'code'        => 'artist_env',
            'name'        => 'Environment & Isometric Artist',
            'icon'        => 'fa-mountain-sun',
            'color'       => 'green',
            'badge_class' => 'bg-green-lt text-green',
            'domain'      => 'Décors & Isométrie',
            'desc'        => 'Bâtiments du fief, variantes météo (nuit/neige), tuiles de provinces et fiefs'
        ],
        'sound' => [
            'code'        => 'sound',
            'name'        => 'Sound Designer & Compositeur',
            'icon'        => 'fa-music',
            'color'       => 'cyan',
            'badge_class' => 'bg-cyan-lt text-cyan',
            'domain'      => 'Audio & Ambiance',
            'desc'        => 'Compositions koto/taiko, nappes ambiantes de nature, effets sonores (SFX)'
        ],
        'narrative' => [
            'code'        => 'narrative',
            'name'        => 'Narrative Designer & Scénariste',
            'icon'        => 'fa-feather-pointed',
            'color'       => 'purple',
            'badge_class' => 'bg-purple-lt text-purple',
            'domain'      => 'Écriture & Lore',
            'desc'        => 'Scénarisation, dialogues des PNJ, fidélité historique Sengoku, quêtes narratives'
        ],
        'qa' => [
            'code'        => 'qa',
            'name'        => 'QA Tester & Recetteur',
            'icon'        => 'fa-vial-circle-check',
            'color'       => 'orange',
            'badge_class' => 'bg-orange-lt text-orange',
            'domain'      => 'Qualité & Recette',
            'desc'        => 'Cahier de recette, tests de non-régression, vérification des conditions de trigger'
        ],
        'producer' => [
            'code'        => 'producer',
            'name'        => 'Producteur Live Ops & Économiste',
            'icon'        => 'fa-crown',
            'color'       => 'yellow',
            'badge_class' => 'bg-yellow-lt text-yellow',
            'domain'      => 'Planification & KPIs',
            'desc'        => 'Planification de release, métriques de complétion, économie et rétention joueur'
        ]
    ];

    /**
     * Galerie de Personnages / Narrateurs Sengoku Préconfigurés
     */
    public const SPEAKER_PRESETS = [
        'sensei' => [
            'name'   => 'Maître de la Sagesse (Chie no Sensei)',
            'role'   => 'Mentor Vénérable & Gardien des Traditions',
            'tone'   => 'Sagesse & Bushido',
            'avatar' => '🏯',
            'color'  => 'purple'
        ],
        'intendant' => [
            'name'   => 'Intendant Torii Mototada',
            'role'   => 'Maître Bâtisseur & Intendant en Chef',
            'tone'   => 'Forteresse & Chantiers',
            'avatar' => '📐',
            'color'  => 'warning'
        ],
        'asuka' => [
            'name'   => 'Dame Asuka du Clan Mori',
            'role'   => 'Déesse des Récoltes & Émissaire du Terroir',
            'tone'   => 'Agriculture & Peuple',
            'avatar' => '🌾',
            'color'  => 'teal'
        ],
        'nobunaga' => [
            'name'   => 'Général Oda Nobunaga',
            'role'   => 'Seigneur de Guerre & Maître de l\'Artillerie',
            'tone'   => 'Conquête & Guerre',
            'avatar' => '⚔️',
            'color'  => 'danger'
        ],
        'murakami' => [
            'name'   => 'Amiral Murakami Takeyoshi',
            'role'   => 'Grand Corsaire de la Mer Intérieure',
            'tone'   => 'Flotte & Mer de Seto',
            'avatar' => '⛵',
            'color'  => 'azure'
        ],
        'masamune' => [
            'name'   => 'Maître Forgeron Masamune',
            'role'   => 'Grand Artisan du Tamahagane',
            'tone'   => 'Forge & Métallurgie',
            'avatar' => '🔥',
            'color'  => 'orange'
        ],
        'kagetora' => [
            'name'   => 'Takayama Kagetora',
            'role'   => 'Grand Maître Artificier du Hōjutsu (Artillerie & Poudre Noire)',
            'tone'   => 'Feu Tonnerre & Sièges Coordonnés',
            'avatar' => '🧨',
            'color'  => 'danger'
        ]
    ];

    /**
     * Presets rapides de Conditions de Déclenchement (Triggers)
     */
    public const TRIGGER_PRESETS = [
        ['label' => '🏯 Tenshu Niv.1',          'value' => 'Tenshu niveau 1 construit'],
        ['label' => '🌾 Grenier Kura Niv.2',    'value' => 'Entrepôt & Grenier Kura niveau 2'],
        ['label' => '🗺️ 4 Parcelles Niv.3+',    'value' => 'Posséder au moins 4 parcelles rurales niveau 3+'],
        ['label' => '👥 Pop. ≥ 250',            'value' => 'Population du domaine ≥ 250 villageois'],
        ['label' => '🍶 Brasserie Niv.5+',      'value' => 'Meunerie & Brasserie niveau 5+'],
        ['label' => '⚒️ Grande Forge Niv.3+',   'value' => 'Grande Forge niveau 3+'],
        ['label' => '⚔️ Héros Samouraï Niv.5+', 'value' => 'Héros Samouraï niveau 5+'],
        ['label' => '❄️ Saison Live Ops',       'value' => 'Événement Live Déclenché (Feature Flag Live Ops)'],
        ['label' => '🔥 Guerre des 12 Châteaux', 'value' => 'Déclenchement mondial de la Guerre des 12 Châteaux (Genson Jūni Tenshu)'],
        ['label' => '🧨 Poudre Noire Niv.20',   'value' => 'Recherche « Poudre Noire (Kayaku) » développée jusqu\'au Niveau 20'],
        ['label' => '💣 Arsenal Pyrotechnique', 'value' => 'Production de 10 Grenades Hōroku et 5 Bombes de siège Teppō-dama en Forge'],
        ['label' => '🏯 2 Châteaux Authentiques', 'value' => 'L\'Alliance détient au minimum 2 des Douze Châteaux Authentiques (castles_owned >= 2)'],
        ['label' => '⛩️ Dōjō Impérial Niv.10',   'value' => 'Achèvement du Niveau 10 du Dōjō Impérial par l\'Alliance (Fin de Partie)']
    ];

    /**
     * Presets rapides de Récompenses Gameplay
     */
    public const REWARD_PRESETS = [
        ['label' => '🪙 +250 Kobans',           'value' => '250 Kobans impériaux'],
        ['label' => '🌲 +1 000 Bois',           'value' => '1 000 Bois de cèdre'],
        ['label' => '🪨 +1 000 Granit',         'value' => '1 000 Pierres de granit'],
        ['label' => '🌾 +1 000 Riz',            'value' => '1 000 Riz impérial'],
        ['label' => '🍶 +300 Saké',             'value' => '300 Saké impérial'],
        ['label' => '🔥 +15% Moral (24h)',      'value' => '+15% Moral du fief pendant 24h'],
        ['label' => '🌾 +25% Récoltes (48h)',   'value' => '+25% Rendement agricole pendant 48h'],
        ['label' => '🏅 Titre & Relique',       'value' => 'Relique & Titre honorifique du Shōgunat'],
        ['label' => '🧨 Déblocage Poudre Noire', 'value' => 'Apparition de la recherche « Poudre Noire (Kayaku) » dans l\'Académie des Savoirs'],
        ['label' => '💣 Arsenal Forge Débloqué', 'value' => 'Déverrouillage de l\'« Arsenal Pyrotechnique » dans la Grande Forge (Grenades & Bombes)'],
        ['label' => '🏯 2 Sceaux & Dōjō Impérial', 'value' => 'Attribution des 2 Sceaux Impériaux Fondateurs & Déblocage du Dōjō Impérial'],
        ['label' => '👑 Victoire Seii Taishōgun', 'value' => 'Fin de partie : Victoire Suprême & Inscription au Panthéon des Vainqueurs d\'OpenShogun']
    ];

    public function __construct() {
        try {
            $this->db = Database::getConnection();
            $this->ensureTables();
        } catch (Throwable $e) {
            $this->db = null;
        }

        // Initialisation unique en base de données
        $this->ensureDefaultScenarios();
    }

    /**
     * Initialise la table SQL si disponible et assure les colonnes additionnelles
     */
    private function ensureTables(): void {
        if (!$this->db) return;
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS `narrative_scenarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `type` VARCHAR(30) NOT NULL DEFAULT 'step',
                `step_number` INT NULL,
                `position_after_step` INT NULL DEFAULT NULL,
                `sequence_order` INT NOT NULL DEFAULT 0,
                `title` VARCHAR(255) NOT NULL,
                `subtitle` VARCHAR(255) NULL,
                `era` VARCHAR(100) NULL,
                `synopsis` TEXT NOT NULL,
                `speaker_name` VARCHAR(150) NULL,
                `speaker_role` VARCHAR(150) NULL,
                `speaker_avatar` VARCHAR(30) NULL DEFAULT '🏯',
                `dialogue` TEXT NOT NULL,
                `trigger_condition` TEXT NULL,
                `rewards_summary` TEXT NULL,
                `choices_json` LONGTEXT NULL,
                `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
                `tasks_json` LONGTEXT NULL,
                `created_by` INT NULL,
                `created_at` INT NOT NULL,
                `updated_at` INT NOT NULL,
                INDEX `idx_scenario_type` (`type`),
                INDEX `idx_scenario_status` (`status`),
                INDEX `idx_scenario_pipeline_order` (`position_after_step`, `sequence_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $this->db->exec($sql);

            // Auto-migration douce des colonnes speaker_avatar, choices_json, position_after_step et sequence_order si absentes
            try {
                $this->db->exec("ALTER TABLE `narrative_scenarios` ADD COLUMN `speaker_avatar` VARCHAR(30) NULL DEFAULT '🏯' AFTER `speaker_role`");
            } catch (Throwable $e) {}
            try {
                $this->db->exec("ALTER TABLE `narrative_scenarios` ADD COLUMN `choices_json` LONGTEXT NULL AFTER `rewards_summary`");
            } catch (Throwable $e) {}
            try {
                $this->db->exec("ALTER TABLE `narrative_scenarios` ADD COLUMN `position_after_step` INT NULL DEFAULT NULL AFTER `step_number`");
            } catch (Throwable $e) {}
            try {
                $this->db->exec("ALTER TABLE `narrative_scenarios` ADD COLUMN `sequence_order` INT NOT NULL DEFAULT 0 AFTER `position_after_step`");
            } catch (Throwable $e) {}
        } catch (Throwable $e) {
            // Ignorer si déjà existant
        }
    }

    /**
     * Ensemence les scénarios par défaut uniquement lors de la toute première initialisation en base de données SQL.
     * Ne ré-insère JAMAIS un scénario supprimé par l'utilisateur.
     */
    private function ensureDefaultScenarios(): void {
        if (!$this->db) return;

        try {
            // 1. Si déjà ensemencé selon GameConfig, ne rien faire
            $alreadySeeded = (bool)GameConfig::get('narrative_scenarios_seeded', false);
            if ($alreadySeeded) {
                return;
            }

            // 2. Si la table contient déjà des scénarios, marquer comme ensemencé et sortir
            $stmtCount = $this->db->query("SELECT COUNT(*) FROM narrative_scenarios");
            $dbCount = (int)$stmtCount->fetchColumn();

            if ($dbCount > 0) {
                GameConfig::set('narrative_scenarios_seeded', true);
                return;
            }

            // 3. Table SQL entièrement vierge lors de l'installation : injection initiale unique
            $defaults = $this->buildDefaultScenarios();
            foreach ($defaults as $scenario) {
                $this->saveScenarioFull($scenario);
            }
            GameConfig::set('narrative_scenarios_seeded', true);
        } catch (Throwable $e) {
            // Ignorer en cas d'erreur SQL
        }
    }

    /**
     * Génère la matrice standard des 10 tâches métiers pour un scénario donné
     */
    public function generateDefaultTasks(string $type, string $title, ?int $stepNumber = null, string $scenarioStatus = 'draft'): array {
        $tasks = [];
        $i = 1;

        $contextLabel = ($type === 'step') ? "Step {$stepNumber} : {$title}" : "Événement Spécial : {$title}";

        foreach (self::ROLES as $roleKey => $roleDef) {
            $taskTitle = match($roleKey) {
                'level_designer' => "Conception du parcours gameplay & équilibrage des objectifs ({$contextLabel})",
                'backend'        => "Moteur de validation des conditions de trigger & attribution des récompenses",
                'frontend'       => "Intégration de l'interface joueur, boîte de dialogue et HUD de progression",
                'network'        => "Synchronisation serveur en direct et broadcast de progression inter-joueurs",
                'artist_2d'      => "Illustration du personnage narrateur & estampe d'ambiance 16:9",
                'artist_env'     => "Modélisation des assets visuels du fief et modifications d'état du décor",
                'sound'          => "Création de la nappe musicale Sengoku et effets sonores de triomphe",
                'narrative'      => "Rédaction finale des dialogues, relecture historique et résonance du lore",
                'qa'             => "Cahier de recette, tests de non-régression et cas limites d'abandon/succès",
                'producer'       => "Cadrage du jalon de release, monitoring de rétention et KPIs d'engagement"
            };

            $desc = match($roleKey) {
                'level_designer' => "Définir les seuils de déclenchement, les coûts de progression et s'assurer que le joueur ne rencontre ni goulot d'étranglement ni facilité excessive.",
                'backend'        => "Implémenter la logique dans ScenarioEngine / QuestEngine. Vérifier l'atomicité des transactions SQL lors de la distribution des Kobans et ressources.",
                'frontend'       => "Développer la modale narrative avec avatar animé, typewriter de dialogue, boutons de choix et affichage des récompenses en particules.",
                'network'        => "Assurer que la notification de fin de step ou de proclamation de l'événement est transmise sans latence excessive sur le cluster.",
                'artist_2d'      => "Produire un portrait expressif du narrateur au format carré (512x512) et une bannière thématique ukiyo-e respectant la charte graphique féodale.",
                'artist_env'     => "Mettre à jour les tuiles isométriques correspondantes dans la cité castrale ou sur le terroir des 9 parcelles.",
                'sound'          => "Composer un leitmotiv distinctif (koto / shakuhachi / taiko) et concevoir les jingles de validation de quête.",
                'narrative'      => "Vérifier la concordance avec la Bible Narrative d'OpenShogun, l'exactitude des kanjis et l'élégance littéraire des répliques.",
                'qa'             => "Tester la reprise de sauvegarde, l'absence de doublons de récompenses, et l'affichage sur mobile/tablette/PC.",
                'producer'       => "Vérifier la vélocité des 10 métiers sur le sprint, arbitrer les priorités et programmer la date de déploiement en Live."
            };

            $complexity = match($roleKey) {
                'backend', 'frontend', 'level_designer' => 'L',
                'artist_2d', 'artist_env', 'qa'          => 'M',
                default                                  => 'S'
            };

            $taskStatus = match($scenarioStatus) {
                'published'     => 'done',
                'ready'         => in_array($roleKey, ['qa', 'producer'], true) ? 'in_progress' : 'done',
                'in_production' => in_array($roleKey, ['narrative', 'level_designer', 'backend', 'artist_2d', 'sound'], true)
                                    ? 'done'
                                    : (in_array($roleKey, ['frontend', 'artist_env', 'network'], true) ? 'in_progress' : 'todo'),
                'in_review'     => in_array($roleKey, ['narrative', 'level_designer', 'producer'], true)
                                    ? 'done'
                                    : (in_array($roleKey, ['artist_2d', 'backend'], true) ? 'in_progress' : 'todo'),
                default         => ($roleKey === 'narrative') ? 'in_progress' : 'todo'
            };

            $tasks[] = [
                'task_id'      => $i++,
                'role_code'    => $roleKey,
                'role_name'    => $roleDef['name'],
                'title'        => $taskTitle,
                'description'  => $desc,
                'assignee'     => $roleDef['name'],
                'complexity'   => $complexity,
                'status'       => $taskStatus,
                'deliverables' => match($roleKey) {
                    'backend'   => 'core/ScenarioEngine.php / migrations SQL',
                    'frontend'  => 'views/partials/modal_scenario.php / CSS',
                    'artist_2d' => 'public/assets/scenarios/portrait.jpg',
                    'qa'        => 'tests/test_scenario_' . ($stepNumber ?? 'event') . '.php',
                    default     => 'Spécifications GDD / Assets livrés'
                }
            ];
        }

        return $tasks;
    }

    /**
     * Normalise les choix narratifs (tableau de choix joueur)
     */
    private function normalizeChoices(mixed $rawChoices): array {
        if (is_string($rawChoices) && $rawChoices !== '') {
            $decoded = json_decode($rawChoices, true);
            $rawChoices = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($rawChoices)) {
            return [];
        }
        $clean = [];
        foreach ($rawChoices as $c) {
            if (!is_array($c)) continue;
            $label = trim((string)($c['label'] ?? ''));
            $outcome = trim((string)($c['outcome'] ?? ''));
            $style = in_array($c['style'] ?? '', ['warning', 'emerald', 'crimson', 'purple', 'success', 'danger', 'teal', 'outline-light'], true)
                ? (string)$c['style']
                : 'warning';
            if ($label !== '') {
                $clean[] = [
                    'label'   => $label,
                    'outcome' => $outcome,
                    'style'   => $style
                ];
            }
        }
        return $clean;
    }

    /**
     * Sauvegarde un scénario complet en base de données SQL uniquement
     */
    public function saveScenarioFull(array $sc): int {
        if (!$this->db) return 0;

        $now = time();
        $slug = !empty($sc['slug']) ? $this->slugify($sc['slug']) : $this->slugify($sc['title'] ?? 'scenario-' . $now);
        $type = in_array($sc['type'] ?? '', self::VALID_TYPES, true) ? $sc['type'] : 'step';
        $stepNumber = isset($sc['step_number']) && $sc['step_number'] !== '' ? (int)$sc['step_number'] : null;
        $positionAfterStep = isset($sc['position_after_step']) && $sc['position_after_step'] !== '' ? (int)$sc['position_after_step'] : null;
        $sequenceOrder = isset($sc['sequence_order']) ? (int)$sc['sequence_order'] : 0;
        $title = trim((string)($sc['title'] ?? 'Nouveau Scénario'));
        $subtitle = trim((string)($sc['subtitle'] ?? ''));
        $era = trim((string)($sc['era'] ?? 'Sengoku Jidai (~1560)'));
        $synopsis = trim((string)($sc['synopsis'] ?? ''));
        $speakerName = trim((string)($sc['speaker_name'] ?? 'Maître de la Sagesse'));
        $speakerRole = trim((string)($sc['speaker_role'] ?? 'Chie no Sensei (Mentor Féodal)'));
        $speakerAvatar = trim((string)($sc['speaker_avatar'] ?? '🏯')) ?: '🏯';
        $dialogue = trim((string)($sc['dialogue'] ?? ''));
        $triggerCond = trim((string)($sc['trigger_condition'] ?? ''));
        $rewards = trim((string)($sc['rewards_summary'] ?? ''));
        $status = in_array($sc['status'] ?? '', self::VALID_STATUSES, true) ? $sc['status'] : 'draft';
        $createdBy = (int)($sc['created_by'] ?? 1);

        $choices = $this->normalizeChoices($sc['choices'] ?? ($sc['choices_json'] ?? []));
        if (empty($choices)) {
            $choices = [
                ['label' => '⚔️ Décréter la Marche du Fief', 'outcome' => $rewards ?: 'Validation de l\'étape', 'style' => 'warning']
            ];
        }
        $choicesJson = json_encode($choices, JSON_UNESCAPED_UNICODE);

        $tasks = !empty($sc['tasks']) && is_array($sc['tasks']) 
            ? array_values($sc['tasks']) 
            : $this->generateDefaultTasks($type, $title, $stepNumber, $status);

        $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);

        try {
            // Vérification d'unicité du slug en base
            $stmtSlug = $this->db->prepare("SELECT COUNT(*) FROM narrative_scenarios WHERE slug = ?");
            $stmtSlug->execute([$slug]);
            if ((int)$stmtSlug->fetchColumn() > 0) {
                $slug .= '-' . $now . '-' . mt_rand(10, 99);
            }

            $stmt = $this->db->prepare("
                INSERT INTO narrative_scenarios 
                (slug, type, step_number, position_after_step, sequence_order, title, subtitle, era, synopsis, speaker_name, speaker_role, speaker_avatar, dialogue, trigger_condition, rewards_summary, choices_json, status, tasks_json, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $slug, $type, $stepNumber, $positionAfterStep, $sequenceOrder, $title, $subtitle, $era, $synopsis,
                $speakerName, $speakerRole, $speakerAvatar, $dialogue, $triggerCond, $rewards,
                $choicesJson, $status, $tasksJson, $createdBy, $now, $now
            ]);
            return (int)$this->db->lastInsertId();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Récupère la liste de tous les scénarios depuis la base de données avec filtres et progression des 10 tâches
     */
    public function getScenarios(string|array|null $type = null, ?string $status = null, ?string $search = null): array {
        if (is_array($type)) {
            $filters = $type;
            $type    = isset($filters['type']) && $filters['type'] !== '' ? (string)$filters['type'] : null;
            $status  = isset($filters['status']) && $filters['status'] !== '' ? (string)$filters['status'] : null;
            $search  = isset($filters['search']) && $filters['search'] !== '' ? (string)$filters['search'] : null;
        }
        if (!$this->db) return [];

        $results = [];
        try {
            $sql = "SELECT * FROM narrative_scenarios WHERE 1=1";
            $params = [];
            if ($type && in_array($type, self::VALID_TYPES, true)) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            if ($status && in_array($status, self::VALID_STATUSES, true)) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            if ($search && trim($search) !== '') {
                $sql .= " AND (title LIKE ? OR synopsis LIKE ? OR slug LIKE ?)";
                $term = '%' . trim($search) . '%';
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }
            $sql .= " ORDER BY CASE type WHEN 'step' THEN 1 ELSE 2 END, step_number ASC, sequence_order ASC, created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $tasks = !empty($row['tasks_json']) ? json_decode($row['tasks_json'], true) : [];
                $row['tasks'] = is_array($tasks) ? array_values($tasks) : [];
                $row['speaker_avatar'] = !empty($row['speaker_avatar']) ? $row['speaker_avatar'] : '🏯';
                $row['position_after_step'] = isset($row['position_after_step']) && $row['position_after_step'] !== null ? (int)$row['position_after_step'] : null;
                $row['sequence_order'] = (int)($row['sequence_order'] ?? 0);
                $row['choices'] = $this->normalizeChoices($row['choices_json'] ?? []);
                if (empty($row['choices'])) {
                    $row['choices'] = [
                        ['label' => '⚔️ Décréter la Marche du Fief', 'outcome' => $row['rewards_summary'] ?? '', 'style' => 'warning']
                    ];
                }
                $row['metrics'] = $this->calculateTaskMetrics($row['tasks']);
                $results[] = $row;
            }
        } catch (Throwable $e) {
            return [];
        }

        return $results;
    }

    /**
     * Récupère un scénario par son ID depuis la base de données
     */
    public function getScenarioById(int $id): ?array {
        if ($id <= 0 || !$this->db) return null;

        try {
            $stmt = $this->db->prepare("SELECT * FROM narrative_scenarios WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $tasks = !empty($row['tasks_json']) ? json_decode($row['tasks_json'], true) : [];
                $row['tasks'] = is_array($tasks) ? array_values($tasks) : [];
                $row['speaker_avatar'] = !empty($row['speaker_avatar']) ? $row['speaker_avatar'] : '🏯';
                $row['position_after_step'] = isset($row['position_after_step']) && $row['position_after_step'] !== null ? (int)$row['position_after_step'] : null;
                $row['sequence_order'] = (int)($row['sequence_order'] ?? 0);
                $row['speaker_avatar'] = !empty($row['speaker_avatar']) ? $row['speaker_avatar'] : '🏯';
                $row['choices'] = $this->normalizeChoices($row['choices_json'] ?? []);
                if (empty($row['choices'])) {
                    $row['choices'] = [
                        ['label' => '⚔️ Décréter la Marche du Fief', 'outcome' => $row['rewards_summary'] ?? '', 'style' => 'warning']
                    ];
                }
                $row['metrics'] = $this->calculateTaskMetrics($row['tasks']);
                return $row;
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Calcule la télémétrie des 10 tâches métiers (Taux de complétion, tâches restantes, blocages)
     */
    public function calculateTaskMetrics(array $tasks): array {
        $total = count($tasks);
        $byRole = [];
        foreach (self::ROLES as $rKey => $rDef) {
            $byRole[$rKey] = [
                'total' => 0, 'done' => 0, 'in_progress' => 0,
                'todo' => 0, 'blocked' => 0, 'percent' => 0
            ];
        }

        if ($total === 0) {
            return [
                'total' => 0, 'done' => 0, 'in_progress' => 0,
                'todo' => 0, 'blocked' => 0, 'percent' => 0, 'is_complete' => false,
                'by_role' => $byRole
            ];
        }

        $done = 0;
        $inProgress = 0;
        $todo = 0;
        $blocked = 0;

        foreach ($tasks as $t) {
            $st = $t['status'] ?? 'todo';
            $rCode = $t['role_code'] ?? 'level_designer';
            if (!isset($byRole[$rCode])) {
                $byRole[$rCode] = ['total' => 0, 'done' => 0, 'in_progress' => 0, 'todo' => 0, 'blocked' => 0, 'percent' => 0];
            }
            $byRole[$rCode]['total']++;

            match($st) {
                'done'        => [$done++, $byRole[$rCode]['done']++],
                'in_progress' => [$inProgress++, $byRole[$rCode]['in_progress']++],
                'blocked'     => [$blocked++, $byRole[$rCode]['blocked']++],
                default       => [$todo++, $byRole[$rCode]['todo']++]
            };
        }

        $percent = (int)round(($done / $total) * 100);

        foreach ($byRole as $rCode => &$metricsRole) {
            if ($metricsRole['total'] > 0) {
                $metricsRole['percent'] = (int)round(($metricsRole['done'] / $metricsRole['total']) * 100);
            }
        }

        return [
            'total'       => $total,
            'done'        => $done,
            'in_progress' => $inProgress,
            'todo'        => $todo,
            'blocked'     => $blocked,
            'percent'     => $percent,
            'is_complete' => ($done === $total),
            'by_role'     => $byRole
        ];
    }

    /**
     * Regroupe les tâches par rôle métier pour affichage structuré en accordéon/sections
     */
    public function getTasksGroupedByRole(array $tasks): array {
        $grouped = [];
        foreach (self::ROLES as $roleCode => $roleDef) {
            $grouped[$roleCode] = [
                'role'  => $roleDef,
                'tasks' => []
            ];
        }

        foreach ($tasks as $t) {
            $rCode = $t['role_code'] ?? 'level_designer';
            if (!isset($grouped[$rCode])) {
                $grouped[$rCode] = [
                    'role'  => self::ROLES[$rCode] ?? [
                        'code' => $rCode,
                        'name' => $t['role_name'] ?? 'Autre Métier',
                        'icon' => 'fa-gear',
                        'color' => 'secondary',
                        'domain' => 'Spécialité'
                    ],
                    'tasks' => []
                ];
            }
            $grouped[$rCode]['tasks'][] = $t;
        }

        return $grouped;
    }

    /**
     * Met à jour un scénario existant en base de données SQL uniquement
     */
    public function updateScenario(int $id, array $data): bool {
        if (!$this->db) return false;
        $existing = $this->getScenarioById($id);
        if (!$existing) return false;

        $now = time();
        $title = trim((string)($data['title'] ?? $existing['title']));
        $slug = !empty($data['slug']) ? $this->slugify($data['slug']) : $existing['slug'];
        $type = in_array($data['type'] ?? '', self::VALID_TYPES, true) ? $data['type'] : $existing['type'];
        $stepNumber = isset($data['step_number']) && $data['step_number'] !== '' ? (int)$data['step_number'] : $existing['step_number'];
        if ($type !== 'step') {
            $stepNumber = null;
        }
        $positionAfterStep = array_key_exists('position_after_step', $data)
            ? ($data['position_after_step'] !== null && $data['position_after_step'] !== '' ? (int)$data['position_after_step'] : null)
            : ($existing['position_after_step'] ?? null);
        $sequenceOrder = isset($data['sequence_order']) ? (int)$data['sequence_order'] : (int)($existing['sequence_order'] ?? 0);
        $subtitle = trim((string)($data['subtitle'] ?? $existing['subtitle']));
        $era = trim((string)($data['era'] ?? $existing['era']));
        $synopsis = trim((string)($data['synopsis'] ?? $existing['synopsis']));
        $speakerName = trim((string)($data['speaker_name'] ?? $existing['speaker_name']));
        $speakerRole = trim((string)($data['speaker_role'] ?? $existing['speaker_role']));
        $speakerAvatar = trim((string)($data['speaker_avatar'] ?? ($existing['speaker_avatar'] ?? '🏯')));
        if ($speakerAvatar === '') $speakerAvatar = '🏯';
        $dialogue = trim((string)($data['dialogue'] ?? $existing['dialogue']));
        $triggerCond = trim((string)($data['trigger_condition'] ?? $existing['trigger_condition']));
        $rewards = trim((string)($data['rewards_summary'] ?? $existing['rewards_summary']));
        $status = in_array($data['status'] ?? '', self::VALID_STATUSES, true) ? $data['status'] : $existing['status'];

        $choices = $existing['choices'] ?? [];
        if (isset($data['choices'])) {
            $choices = $this->normalizeChoices($data['choices']);
        }
        if (empty($choices)) {
            $choices = [
                ['label' => '⚔️ Décréter la Marche du Fief', 'outcome' => $rewards, 'style' => 'warning']
            ];
        }
        $choicesJson = json_encode($choices, JSON_UNESCAPED_UNICODE);

        $tasks = $existing['tasks'];
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $tasks = $data['tasks'];
        }

        $tasksJson = json_encode($tasks, JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $this->db->prepare("
                UPDATE narrative_scenarios
                SET slug = ?, type = ?, step_number = ?, position_after_step = ?, sequence_order = ?, title = ?, subtitle = ?, era = ?, synopsis = ?,
                    speaker_name = ?, speaker_role = ?, speaker_avatar = ?, dialogue = ?, trigger_condition = ?,
                    rewards_summary = ?, choices_json = ?, status = ?, tasks_json = ?, updated_at = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $slug, $type, $stepNumber, $positionAfterStep, $sequenceOrder, $title, $subtitle, $era, $synopsis,
                $speakerName, $speakerRole, $speakerAvatar, $dialogue, $triggerCond,
                $rewards, $choicesJson, $status, $tasksJson, $now, $id
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Met à jour rapidement l'ancrage d'un événement spécial dans le pipeline narratif (drag & drop inter-step)
     */
    public function updateScenarioPipelinePosition(int $scenarioId, ?int $afterStep, int $sequenceOrder = 0): bool {
        if ($scenarioId <= 0 || !$this->db) return false;
        try {
            $stmt = $this->db->prepare("
                UPDATE narrative_scenarios
                SET position_after_step = ?, sequence_order = ?, updated_at = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $afterStep !== null && $afterStep >= 0 ? $afterStep : null,
                $sequenceOrder,
                time(),
                $scenarioId
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Met à jour rapidement le statut d'un scénario dans le pipeline
     */
    public function updateScenarioStatus(int $scenarioId, string $newStatus): bool {
        if (!in_array($newStatus, self::VALID_STATUSES, true)) return false;
        return $this->updateScenario($scenarioId, ['status' => $newStatus]);
    }

    /**
     * Met à jour le statut d'une tâche métier spécifique
     */
    public function updateTaskStatus(int $scenarioId, string|int $taskId, string $newStatus): bool {
        if (!in_array($newStatus, self::VALID_TASK_STATUSES, true)) return false;

        $scenario = $this->getScenarioById($scenarioId);
        if (!$scenario) return false;

        $tasks = $scenario['tasks'];
        $modified = false;

        foreach ($tasks as $idx => $t) {
            if ((string)($t['task_id'] ?? '') === (string)$taskId) {
                $tasks[$idx]['status'] = $newStatus;
                $modified = true;
                break;
            }
        }

        if (!$modified) return false;

        return $this->updateScenario($scenarioId, ['tasks' => $tasks]);
    }

    /**
     * Supprime un scénario en base de données SQL uniquement
     */
    public function deleteScenario(int $id): bool {
        if ($id <= 0 || !$this->db) return false;

        try {
            $stmt = $this->db->prepare("DELETE FROM narrative_scenarios WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Duplique un scénario avec l'ensemble de ses 10 tâches métiers
     */
    public function duplicateScenario(int $id, int $userId = 1): ?array {
        $source = $this->getScenarioById($id);
        if (!$source) return null;

        $clone = $source;
        unset($clone['id'], $clone['metrics']);
        $clone['title'] = $source['title'] . ' (Copie)';
        $clone['slug'] = $source['slug'] . '-copie-' . time();
        $clone['status'] = 'draft';
        $clone['created_by'] = $userId;

        // Réinitialiser les statuts des tâches à 'todo'
        if (!empty($clone['tasks'])) {
            foreach ($clone['tasks'] as &$t) {
                $t['status'] = 'todo';
            }
        }

        $newId = $this->saveScenarioFull($clone);
        return $this->getScenarioById($newId);
    }

    /**
     * Calcule les métriques globales et séries graphiques sur un ensemble de scénarios
     */
    public function getGlobalStudioMetrics(array $scenarios): array {
        $totalScenarios = count($scenarios);
        $stepsCount = 0;
        $eventsCount = 0;

        $statusCounts = [
            'draft'         => 0,
            'in_review'     => 0,
            'in_production' => 0,
            'ready'         => 0,
            'published'     => 0,
            'archived'      => 0
        ];

        $taskStatusTotals = [
            'done'        => 0,
            'in_progress' => 0,
            'todo'        => 0,
            'blocked'     => 0,
            'total'       => 0
        ];

        $complexityTotals = [
            'S'  => 0,
            'M'  => 0,
            'L'  => 0,
            'XL' => 0
        ];

        $byRole = [];
        foreach (self::ROLES as $rCode => $rDef) {
            $byRole[$rCode] = [
                'code'        => $rCode,
                'name'        => $rDef['name'],
                'icon'        => $rDef['icon'],
                'color'       => $rDef['color'],
                'domain'      => $rDef['domain'],
                'total'       => 0,
                'done'        => 0,
                'in_progress' => 0,
                'todo'        => 0,
                'blocked'     => 0,
                'percent'     => 0
            ];
        }

        foreach ($scenarios as $sc) {
            if (($sc['type'] ?? 'step') === 'step') {
                $stepsCount++;
            } else {
                $eventsCount++;
            }

            $scStatus = $sc['status'] ?? 'draft';
            if (isset($statusCounts[$scStatus])) {
                $statusCounts[$scStatus]++;
            }

            $tasks = $sc['tasks'] ?? [];
            foreach ($tasks as $t) {
                $taskStatusTotals['total']++;
                $st = $t['status'] ?? 'todo';
                if (isset($taskStatusTotals[$st])) {
                    $taskStatusTotals[$st]++;
                } else {
                    $taskStatusTotals['todo']++;
                }

                $comp = strtoupper((string)($t['complexity'] ?? 'M'));
                if (isset($complexityTotals[$comp])) {
                    $complexityTotals[$comp]++;
                } else {
                    $complexityTotals['M']++;
                }

                $rCode = $t['role_code'] ?? 'level_designer';
                if (!isset($byRole[$rCode])) {
                    $byRole[$rCode] = [
                        'code'        => $rCode,
                        'name'        => $t['role_name'] ?? 'Autre Métier',
                        'icon'        => 'fa-gear',
                        'color'       => 'secondary',
                        'domain'      => 'Spécialité',
                        'total'       => 0,
                        'done'        => 0,
                        'in_progress' => 0,
                        'todo'        => 0,
                        'blocked'     => 0,
                        'percent'     => 0
                    ];
                }
                $byRole[$rCode]['total']++;
                if (isset($byRole[$rCode][$st])) {
                    $byRole[$rCode][$st]++;
                } else {
                    $byRole[$rCode]['todo']++;
                }
            }
        }

        foreach ($byRole as $rCode => &$rStat) {
            if ($rStat['total'] > 0) {
                $rStat['percent'] = (int)round(($rStat['done'] / $rStat['total']) * 100);
            }
        }
        unset($rStat);

        $globalPercent = ($taskStatusTotals['total'] > 0)
            ? (int)round(($taskStatusTotals['done'] / $taskStatusTotals['total']) * 100)
            : 0;

        return [
            'total_scenarios' => $totalScenarios,
            'steps_count'     => $stepsCount,
            'events_count'    => $eventsCount,
            'scenario_status' => $statusCounts,
            'tasks_totals'    => $taskStatusTotals,
            'complexity'      => $complexityTotals,
            'by_role'         => $byRole,
            'global_percent'  => $globalPercent
        ];
    }

    /**
     * Export du Scénario sous format Game Design Document (GDD Markdown)
     */
    public function exportMarkdown(int $id): string {
        $sc = $this->getScenarioById($id);
        if (!$sc) return "# Erreur : Scénario introuvable.\n";

        $typeLabel = ($sc['type'] === 'step') ? "Étape de Jeu (Step #{$sc['step_number']})" : "Événement Spécial";
        $cleanDialogue = str_replace(["\r\n", "\r"], "\n", trim((string)($sc['dialogue'] ?? '')));
        $blockquoteDialogue = str_replace("\n", "\n> ", $cleanDialogue);

        $md = "# 📜 CAHIER DE SCÉNARISATION & GDD : {$sc['title']}\n\n";
        if (!empty($sc['subtitle'])) {
            $md .= "*{$sc['subtitle']}*\n\n";
        }
        $md .= "- **Type** : {$typeLabel}\n";
        $md .= "- **Identifiant Technique** : `{$sc['slug']}`\n";
        $md .= "- **Période Historique** : {$sc['era']}\n";
        $md .= "- **Statut de Production** : `" . strtoupper((string)$sc['status']) . "`\n";
        $md .= "- **Avancement Global des Tâches Métiers** : **{$sc['metrics']['percent']}%** ({$sc['metrics']['done']}/{$sc['metrics']['total']} terminées, {$sc['metrics']['in_progress']} en cours, {$sc['metrics']['blocked']} bloquées)\n\n";

        $md .= "## 1. Trame Narrative & Synopsis\n\n";
        $md .= "{$sc['synopsis']}\n\n";

        $md .= "## 2. Voix Narrative & Discours In-Game\n\n";
        $md .= "> {$sc['speaker_avatar']} **{$sc['speaker_name']}** (*{$sc['speaker_role']}*)\n";
        $md .= "> \n";
        $md .= "> « {$blockquoteDialogue} »\n\n";

        $md .= "## 3. Déclencheurs, Choix du Daimyō & Conséquences\n\n";
        $md .= "- **Conditions d'Activation (Triggers)** : {$sc['trigger_condition']}\n";
        $md .= "- **Récompenses & Effets Globaux** : {$sc['rewards_summary']}\n\n";

        if (!empty($sc['choices'])) {
            $md .= "### Choix Narratifs Proposés au Joueur\n\n";
            foreach ($sc['choices'] as $idx => $ch) {
                $num = $idx + 1;
                $outcome = !empty($ch['outcome']) ? " ➔ *{$ch['outcome']}*" : "";
                $md .= "{$num}. **[{$ch['label']}]**{$outcome}\n";
            }
            $md .= "\n";
        }

        $md .= "## 4. Synthèse Graphique par Métier\n\n";
        $md .= "| Métier du Studio | Domaine | Progression | Tâches (Terminées / Total) |\n";
        $md .= "|---|---|---|---|\n";
        $byRole = $sc['metrics']['by_role'] ?? [];
        foreach (self::ROLES as $rCode => $rDef) {
            $rStat = $byRole[$rCode] ?? ['total' => 0, 'done' => 0, 'percent' => 0];
            $filled = (int)round(($rStat['percent'] / 100) * 10);
            $bar = str_repeat('█', $filled) . str_repeat('░', max(0, 10 - $filled));
            $md .= "| **{$rDef['name']}** | {$rDef['domain']} | `{$bar}` **{$rStat['percent']}%** | {$rStat['done']} / {$rStat['total']} |\n";
        }
        $md .= "\n";

        $md .= "## 5. Matrice Opérationnelle des Tâches Métiers\n\n";
        $md .= "| # | Métier Clé | Tâche Opérationnelle | Complexité | Assigné | Statut | Livrables |\n";
        $md .= "|---|---|---|---|---|---|---|\n";

        foreach ($sc['tasks'] as $t) {
            $stBadge = match($t['status'] ?? 'todo') {
                'done'        => '✅ Terminé',
                'in_progress' => '🔧 En cours',
                'blocked'     => '⛔ Bloqué',
                default       => '⏳ À faire'
            };
            $titleClean = str_replace('|', '/', (string)($t['title'] ?? ''));
            $delivClean = str_replace('|', '/', (string)($t['deliverables'] ?? ''));
            $roleClean = str_replace('|', '/', (string)($t['role_name'] ?? ''));
            $assignClean = str_replace('|', '/', (string)($t['assignee'] ?? ''));
            $md .= "| {$t['task_id']} | **{$roleClean}** | {$titleClean} | `{$t['complexity']}` | {$assignClean} | {$stBadge} | `{$delivClean}` |\n";
        }

        $md .= "\n---\n*Généré automatiquement par OpenShogun Game Studio (Narrative Designer)*\n";
        return $md;
    }

    /**
     * Construit les scénarios par défaut pour les steps du mode de jeu et les événements spéciaux
     */
    private function buildDefaultScenarios(): array {
        return [
            // STEP 1
            [
                'slug'              => 'step-01-eveil-du-fief',
                'type'              => 'step',
                'step_number'       => 1,
                'title'             => 'L\'Éveil du Fief & l\'Appel du Clan',
                'subtitle'          => 'Prologue : Fondation de la seigneurie et première moisson',
                'era'               => '1560 - Aube du Sengoku Jidai',
                'synopsis'          => 'Après des années de pillages féodaux, le conseil des anciens de la province accorde l\'autonomie à une jeune lignée de daimyōs. Le joueur doit poser les fondations de son premier domaine : défricher les terres, ériger le premier entrepôt de riz et prêter le serment d\'honneur du Bushido.',
                'speaker_name'      => 'Maître de la Sagesse (Chie no Sensei)',
                'speaker_role'      => 'Vénérable Mentor & Gardien des Traditions',
                'speaker_avatar'    => '🏯',
                'dialogue'          => 'Salutations, noble jeune Seigneur. Votre terre a longtemps sommeillé sous la cendre des guerres passées. Écoutez le bruissement des bambous et la voix de vos sujets : érigez votre premier grenier et honorez les esprits de cette vallée. Votre destinée commence sous le signe du dragon.',
                'trigger_condition' => 'Ouverture du Fief (Niveau de joueur 1, achèvement du pacte initial)',
                'rewards_summary'   => '100 Kobans impériaux, 500 Riz, 500 Bois de cèdre, Titre honorifique : « Seigneur Novice du Domaine »',
                'choices'           => [
                    ['label' => '🌾 Prioriser les Greniers & Paysans', 'outcome' => '+500 Riz, +10 Morale paysanne', 'style' => 'emerald'],
                    ['label' => '⚔️ Prêter le Serment du Bushido', 'outcome' => '+100 Kobans, +15 Prestige de Clan', 'style' => 'warning']
                ],
                'status'            => 'published',
                'created_by'        => 1
            ],
            // STEP 2
            [
                'slug'              => 'step-02-erection-forteresse',
                'type'              => 'step',
                'step_number'       => 2,
                'title'             => 'L\'Érection du Tenshu & la Muraille Cyclopéenne',
                'subtitle'          => 'Chapitre 2 : Fortification de la cité castrale face aux rōnins',
                'era'               => '1562 - Tumulte des Marches Frontalières',
                'synopsis'          => 'Les bruits de la renaissance du domaine ont attiré des bandes de pillards et mercenaires errants. Pour protéger les familles paysannes et centraliser le commandement, le seigneur doit ériger le donjon fortifié (Tenshu niveau 1) et dresser des palissades renforcées autour du bourg.',
                'speaker_name'      => 'Intendant Torii Mototada',
                'speaker_role'      => 'Maître Bâtisseur & Intendant en Chef',
                'speaker_avatar'    => '📐',
                'dialogue'          => 'Mon Daimyō, une armée sans donjon est comme un sabre sans poignée. Les guetteurs ont aperçu des fumées suspectes sur les crêtes de l\'Est. Ordonnez l\'extraction du granit et montons ce donjon avant le premier gel !',
                'trigger_condition' => 'Tenshu niveau 1 construit + Palissades de bois niveau 1',
                'rewards_summary'   => '250 Kobans, 1 000 Pierres de carrière, Déblocage du simulateur de combat de garnison',
                'choices'           => [
                    ['label' => '🏯 Mobiliser les tailleurs de granit', 'outcome' => '+1 000 Pierres, Tenshu renforcé (+10% Défense)', 'style' => 'warning'],
                    ['label' => '🏹 Poster des guetteurs Ashigaru aux crêtes', 'outcome' => '+250 Kobans, Alerte précoce contre les rōnins', 'style' => 'crimson']
                ],
                'status'            => 'in_production',
                'created_by'        => 1
            ],
            // STEP 3
            [
                'slug'              => 'step-03-pacte-des-terroirs',
                'type'              => 'step',
                'step_number'       => 3,
                'title'             => 'Le Pacte des Terroirs & Expansion Rurale',
                'subtitle'          => 'Chapitre 3 : Maîtrise des 9 parcelles et équilibre agraire',
                'era'               => '1565 - Essor Agricole des Provinces',
                'synopsis'          => 'Une forteresse ne peut tenir sans un approvisionnement continu. Ce scénario plonge le joueur dans la mise en valeur des 9 parcelles extérieures : rizières inondées, sylviculture des cèdres, carrière de granit et plantations de thé vert montagnard.',
                'speaker_name'      => 'Dame Asuka du Clan Mori',
                'speaker_role'      => 'Déesse des Récoltes & Émissaire du Terroir',
                'speaker_avatar'    => '🌾',
                'dialogue'          => 'Regardez ces collines en terrasses, Mon Seigneur. L\'eau douce descend des névés pour nourrir nos rizières. Donnez des outils aux paysans, creusez les canaux d\'irrigation et notre grenier ne connaîtra jamais l\'ombre de la disette.',
                'trigger_condition' => 'Posséder au moins 4 parcelles rurales de niveau 3 ou supérieur',
                'rewards_summary'   => '+25% Rendement agricole pendant 48h, Déverrouillage des outils agraires en fer',
                'choices'           => [
                    ['label' => '💧 Creuser les grands canaux d\'irrigation', 'outcome' => '+25% Rendement Riz & Thé pendant 48h', 'style' => 'emerald'],
                    ['label' => '⚒️ Forger des outils agraires en fer', 'outcome' => '+15% Production Bois & Granit permanente', 'style' => 'warning']
                ],
                'status'            => 'in_review',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 1
            [
                'slug'              => 'event-mousson-celeste',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'La Grande Mousson Céleste & Famine Blanche',
                'subtitle'          => 'Événement d\'Hiver : Tempête de neige féodale et solidarité des clans',
                'era'               => '1570 - Le Grand Hiver Sengoku',
                'synopsis'          => 'Une terrible tempête de neige s\'abat sur tout l\'archipel. Les rizières gèlent, les caravanes de marchands sont bloquées dans les cols et les oasis deviennent glacées. Les seigneurs doivent choisir entre distribuer leurs réserves de saké et de riz pour sauver leurs paysans ou thésauriser pour armer leurs soldats.',
                'speaker_name'      => 'Chie no Sensei (Maître de la Sagesse)',
                'speaker_role'      => 'Grand Ermite du Temple Shinto',
                'speaker_avatar'    => '🏯',
                'dialogue'          => 'Le ciel blanc ne distingue ni samouraï ni laboureur. Ouvrez les portes de vos greniers royaux, offrez le bouillon chaud et le saké à vos sujets. Le Daimyō qui protège son peuple en temps d\'épreuve trouvera dix mille sabres fidèles lorsque reviendra le printemps.',
                'trigger_condition' => 'Événement Live Déclenché (Saison Hivernale ou Feature Flag Live Ops)',
                'rewards_summary'   => 'Relique « Sceau du Flocon Éternel », Médaille de Bienveillance (+15 Morale permanente)',
                'choices'           => [
                    ['label' => '🍶 Ouvrir les greniers & distribuer le Saké', 'outcome' => '+15 Morale permanente, Sceau du Flocon Éternel', 'style' => 'emerald'],
                    ['label' => '🛡️ Rationner pour la garnison des samouraïs', 'outcome' => '+20% Défense hivernale, -10 Loyauté paysanne', 'style' => 'crimson']
                ],
                'status'            => 'ready',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 2
            [
                'slug'              => 'event-tournoi-100-lames',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'Le Tournoi Impérial des 100 Lames de Kyōto',
                'subtitle'          => 'Événement Combattant : Joutes d\'honneur entre champions de clans',
                'era'               => '1578 - Faste Impérial de Kyōto',
                'synopsis'          => 'L\'Empereur et le Shōgun organisent au cœur de la capitale un tournoi sacré pour honorer la déesse Amaterasu. Les plus grands champions de chaque fief (Oda, Takeda, Mori) sont conviés dans l\'arène du palais pour croiser le fer dans le respect absolu du code Bushido.',
                'speaker_name'      => 'Général Oda Nobunaga',
                'speaker_role'      => 'Seigneur de Guerre & Maître de l\'Artillerie',
                'speaker_avatar'    => '⚔️',
                'dialogue'          => 'Que les lâches tremblent ! Envoyez votre plus valeureux samouraï à Kyōto. Montrez à ces seigneurs engourdis ce que vaut la lame d\'un combattant forgé par nos rudes provinces !',
                'trigger_condition' => 'Avoir un Héros Samouraï niveau 5 ou supérieur + Dojo niveau 4',
                'rewards_summary'   => 'Katana légendaire de l\'Empereur (+35 Attaque Héros), 500 Kobans, Bannière impériale de prestige',
                'choices'           => [
                    ['label' => '⚔️ Engager notre Champion en duel singulier', 'outcome' => 'Katana de l\'Empereur (+35 ATQ), +500 Kobans', 'style' => 'crimson'],
                    ['label' => '🎌 Parrainer les joutes diplomatiques', 'outcome' => 'Bannière Impériale (+25 Prestige de Clan)', 'style' => 'warning']
                ],
                'status'            => 'in_production',
                'created_by'        => 1
            ],
            // ÉVÉNEMENT SPÉCIAL 3
            [
                'slug'              => 'event-incursion-wakou',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'L\'Incursion des Pirates Wakō sur la Mer Intérieure',
                'subtitle'          => 'Événement Maritime & Défense Côtière',
                'era'               => '1580 - Tempête sur la Mer de Seto',
                'synopsis'          => 'Des escadres de jonques de pirates Wakō pillent les navires marchands et menacent les ports de pêche. Tous les daimyōs côtiers doivent mobiliser leurs Atakebune et équiper leurs pêcheurs pour briser le blocus pirate.',
                'speaker_name'      => 'Amiral Murakami Takeyoshi',
                'speaker_role'      => 'Grand Corsaire du Clan Mori',
                'speaker_avatar'    => '⛵',
                'dialogue'          => 'Ces chiens de mer croient pouvoir braver nos côtes impunément ! Hissez nos étendards, chargez les canons à pierre et envoyons leurs jonques par le fond !',
                'trigger_condition' => 'Chantier naval niveau 2 ou 3 barques de pêche opérationnelles',
                'rewards_summary'   => 'Plans du Navire Blindé Tekkōsen, 1 500 Bois précieux, Coffres de butin pirate',
                'choices'           => [
                    ['label' => '⛵ Lancer l\'assaut naval des Atakebune', 'outcome' => 'Plans du Tekkōsen + Coffres de butin pirate', 'style' => 'crimson'],
                    ['label' => '🔥 Tendre une embuscade de brûlots côtiers', 'outcome' => '+1 500 Bois précieux, Flotte Wakō dispersée', 'style' => 'warning']
                ],
                'status'            => 'draft',
                'created_by'        => 1
            ],
            // STEP 4 - LA GUERRE DES 12 CHÂTEAUX (ACTE II)
            [
                'slug'              => 'step-04-poudre-noire-20',
                'type'              => 'step',
                'step_number'       => 4,
                'title'             => 'Le 20e Cercle du Kayaku & le Secret des Alchimistes',
                'subtitle'          => 'Step 4 : Atteindre la maîtrise absolue de la Poudre Noire au Niveau 20',
                'era'               => '1583 - Les Secrets de la Poudre Tonnerre',
                'synopsis'          => 'Pendant des mois, les alchimistes du domaine ont purifié le salpêtre des grottes volcaniques, distillé le soufre jaune et calciné le bois de saule pour obtenir le charbon idéal. En atteignant le Niveau 20 de la Poudre Noire, le fief maîtrise la formule explosive parfaite et débloque l\'Arsenal Pyrotechnique dans la Grande Forge.',
                'speaker_name'      => 'Takayama Kagetora',
                'speaker_role'      => 'Grand Maître Artificier du Hōjutsu',
                'speaker_avatar'    => '🧨',
                'dialogue'          => 'Admirez ce grain noir, Daimyō ! Ni trop humide, ni trop friable. Au 20e palier de notre art alchimique, la détonation n\'est plus un simple pétard : c\'est la foudre d\'Izanagi lui-même ! Notre Grande Forge peut désormais couler les redoutables grenades en céramique Hōroku et les bombes de brèche Teppō-dama capables de pulvériser les murailles de granit !',
                'trigger_condition' => 'Recherche « Poudre Noire (Kayaku) » développée jusqu\'au Niveau 20 dans l\'Académie des Savoirs',
                'rewards_summary'   => 'Déverrouillage de l\'« Arsenal Pyrotechnique » dans la Grande Forge, Recettes : Baril de Poudre (150 Nitrate + 50 Soufre + 100 Charbon), Grenade Hōroku-hiya et Bombe de siège Teppō-dama',
                'choices'           => [
                    ['label' => '⚒️ Allumer les fourneaux de l\'Arsenal Pyrotechnique', 'outcome' => 'Déblocage immédiat de la fabrication des Bombes et Grenades en Grande Forge', 'style' => 'crimson'],
                    ['label' => '🧪 Perfectionner la pureté pyrotechnique', 'outcome' => '+10% Dégâts explosifs supplémentaires pour les troupes de siège du fief', 'style' => 'warning']
                ],
                'status'            => 'in_production',
                'created_by'        => 1,
                'tasks'             => [
                    [
                        'task_id'      => 1,
                        'role_code'    => 'level_designer',
                        'role_name'    => 'Game & Level Designer',
                        'title'        => 'Équilibrage de la courbe de recherche Poudre Noire Niv. 1 à 20',
                        'description'  => 'Définir les coûts croissants en Nitrate, Soufre et Charbon pour les 20 niveaux de la techno gunpowder_tech.',
                        'assignee'     => 'Lead Level Designer',
                        'complexity'   => 'L',
                        'status'       => 'done',
                        'deliverables' => 'docs/gdd_bataille_12_chateaux.md / Courbes de progression'
                    ],
                    [
                        'task_id'      => 2,
                        'role_code'    => 'backend',
                        'role_name'    => 'Développeur Back-End',
                        'title'        => 'Contrôle strict du seuil Niveau 20 et déblocage conditionnel de l\'Arsenal',
                        'description'  => 'Implémenter dans ResearchEngine et ForgeEngine la vérification du niveau 20 de la Poudre Noire.',
                        'assignee'     => 'Dev Back Senior',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'core/ResearchEngine.php / core/ForgeEngine.php'
                    ],
                    [
                        'task_id'      => 3,
                        'role_code'    => 'frontend',
                        'role_name'    => 'Développeur Front-End',
                        'title'        => 'Interface de l\'Académie avec jauge de maîtrise Niveau 20 et alertes de complétion',
                        'description'  => 'Intégrer la jauge de progression 1..20 de la Poudre Noire avec badge d\'arsenal déverrouillé.',
                        'assignee'     => 'Dev Front UI/UX',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'views/research.php / assets CSS'
                    ],
                    [
                        'task_id'      => 4,
                        'role_code'    => 'network',
                        'role_name'    => 'Développeur Réseau & Infra',
                        'title'        => 'Notification en temps réel du franchissement du Palier 20 aux alliés',
                        'description'  => 'Diffuser un broadcast réseau au sein de l\'alliance lorsqu\'un membre débloque le Niveau 20 de Kayaku.',
                        'assignee'     => 'Dev Réseau',
                        'complexity'   => 'S',
                        'status'       => 'todo',
                        'deliverables' => 'core/SocketNotification.php'
                    ],
                    [
                        'task_id'      => 5,
                        'role_code'    => 'artist_2d',
                        'role_name'    => 'Game Artist 2D & Ukiyo-e',
                        'title'        => 'Illustration du rouleau d\'alchimie du Kayaku et médaillon pyrotechnique',
                        'description'  => 'Produire les icônes d\'alchimie, du creuset et de la flamme tonnerre dans le style estampe ukiyo-e.',
                        'assignee'     => 'Concept Artist 2D',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/researches/gunpowder.jpg'
                    ],
                    [
                        'task_id'      => 6,
                        'role_code'    => 'artist_env',
                        'role_name'    => 'Environment & Isometric Artist',
                        'title'        => 'Variante visuelle de l\'Académie des Savoirs avec laboratoire de salpêtre',
                        'description'  => 'Ajouter des détails visuels (mortiers, cornues et volutes de fumée noire) au bâtiment de l\'Académie.',
                        'assignee'     => 'Isometric Artist',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'public/assets/buildings/research_lab_lvl20.png'
                    ],
                    [
                        'task_id'      => 7,
                        'role_code'    => 'sound',
                        'role_name'    => 'Sound Designer & Compositeur',
                        'title'        => 'Effet sonore de déflagration d\'essai et crépitements de poudres',
                        'description'  => 'Concevoir le SFX de montée au niveau 20 : crépitement de mèche puis détonation sourde et écho féodal.',
                        'assignee'     => 'Sound Designer',
                        'complexity'   => 'S',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/sounds/black_powder_lvl20.mp3'
                    ],
                    [
                        'task_id'      => 8,
                        'role_code'    => 'narrative',
                        'role_name'    => 'Narrative Designer & Scénariste',
                        'title'        => 'Rédaction du grimoire des 20 paliers alchimiques du salpêtre',
                        'description'  => 'Écrire les descriptions narratives immersives des 20 niveaux de découverte de la poudre noire.',
                        'assignee'     => 'Scénariste Principal',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'docs/lore_alchimie_kayaku.md'
                    ],
                    [
                        'task_id'      => 9,
                        'role_code'    => 'qa',
                        'role_name'    => 'QA Tester & Recetteur',
                        'title'        => 'Test du palier critique : verrouillé à 19, accessible à 20',
                        'description'  => 'Écrire un test automatisé vérifiant qu\'aucune commande de craft de bombe n\'est autorisée avant le niveau 20.',
                        'assignee'     => 'QA Automation Engineer',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'tests/test_black_powder_threshold.php'
                    ],
                    [
                        'task_id'      => 10,
                        'role_code'    => 'producer',
                        'role_name'    => 'Producteur Live Ops & Économiste',
                        'title'        => 'Monitoring du temps moyen de recherche et équilibrage de la rétention',
                        'description'  => 'Vérifier la télémétrie du passage au niveau 20 pour calibrer le timing de déclenchement des sièges.',
                        'assignee'     => 'Live Ops Producer',
                        'complexity'   => 'S',
                        'status'       => 'todo',
                        'deliverables' => 'Dashboards KPIs Game Studio'
                    ]
                ]
            ],
            // STEP 5 - LA GUERRE DES 12 CHÂTEAUX (ACTE III)
            [
                'slug'              => 'step-05-fourneaux-tonnerre',
                'type'              => 'step',
                'step_number'       => 5,
                'title'             => 'Les Fourneaux du Tonnerre & Armes de Siège',
                'subtitle'          => 'Step 5 : Fonder les premières Grenades Hōroku et Bombes de Siège en Grande Forge',
                'era'               => '1584 - Tumulte des Fonderies',
                'synopsis'          => 'Les forges tournent jour et nuit. Les maîtres potiers cuisent les coques de céramique étanches tandis que les forgerons coulent la fonte des bombes renforcées au Satetsu. Dès la sortie des premiers engins explosifs, le clan débloque l\'ordre d\'opération militaire « Siège Coordonné d\'Alliance » contre les 12 Châteaux Authentiques.',
                'speaker_name'      => 'Maître Forgeron Masamune',
                'speaker_role'      => 'Grand Artisan de la Forge & du Tamahagane',
                'speaker_avatar'    => '🔥',
                'dialogue'          => 'Le métal et le feu ne font qu\'un ! Sous la direction du Maître Kagetora, nos creusets ont enfanté les premières bombes Teppō-dama. Regardez cette fonte trempée au sable de fer Satetsu : les remparts des douze forteresses n\'y résisteront pas. Donnez l\'ordre à vos alliés de se regrouper, l\'heure du siège a sonné !',
                'trigger_condition' => 'Avoir produit au moins 10 Grenades Hōroku-hiya et 5 Bombes de siège Teppō-dama dans la Grande Forge',
                'rewards_summary'   => 'Déblocage de l\'ordre d\'opération « Siège Coordonné d\'Alliance », +25% Vitesse de marche des caravanes d\'artillerie, 5 Barils de poudre noire offerts',
                'choices'           => [
                    ['label' => '💣 Équiper les sapeurs d\'artillerie de siège', 'outcome' => 'Déblocage de l\'ordre de Siège Coordonné d\'Alliance + 5 Barils de Kayaku', 'style' => 'crimson'],
                    ['label' => '🛡️ Distribuer les Grenades Hōroku aux garnisons', 'outcome' => '+30% Puissance défensive en cas d\'assaut de forteresse adverse', 'style' => 'warning']
                ],
                'status'            => 'in_production',
                'created_by'        => 1,
                'tasks'             => [
                    [
                        'task_id'      => 1,
                        'role_code'    => 'level_designer',
                        'role_name'    => 'Game & Level Designer',
                        'title'        => 'Équilibrage des recettes Forge : Baril de poudre, Grenade Hōroku & Bombe Teppō-dama',
                        'description'  => 'Paramétrer les coûts de craft en Nitrate, Soufre, Charbon, Fer et Satetsu, ainsi que les dégâts de siège.',
                        'assignee'     => 'Combat & Economy Designer',
                        'complexity'   => 'L',
                        'status'       => 'done',
                        'deliverables' => 'docs/gdd_bataille_12_chateaux.md (Section IV)'
                    ],
                    [
                        'task_id'      => 2,
                        'role_code'    => 'backend',
                        'role_name'    => 'Développeur Back-End',
                        'title'        => 'Moteur de fabrication de l\'Arsenal Pyrotechnique en Grande Forge',
                        'description'  => 'Développer l\'API de commande de craft des barils, grenades et bombes avec déduction transactionnelle des stocks.',
                        'assignee'     => 'Dev Back Senior',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'core/SiegeWeaponsEngine.php / migrations SQL'
                    ],
                    [
                        'task_id'      => 3,
                        'role_code'    => 'frontend',
                        'role_name'    => 'Développeur Front-End',
                        'title'        => 'Interface de l\'Atelier Pyrotechnique dans la Grande Forge',
                        'description'  => 'Créer l\'onglet dédié dans views/forge.php avec cartes d\'armes, temps restant et boutons de fabrication rapide.',
                        'assignee'     => 'Dev Front',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'views/forge.php / modal_craft_bomb.php'
                    ],
                    [
                        'task_id'      => 4,
                        'role_code'    => 'network',
                        'role_name'    => 'Développeur Réseau & Infra',
                        'title'        => 'File d\'attente de production de forge et synchronisation de stock',
                        'description'  => 'Gérer le timer asynchrone de fin de coulée des bombes et l\'actualisation de l\'inventaire sans rechargement.',
                        'assignee'     => 'Dev Réseau',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'core/CronEngine.php / Webhooks'
                    ],
                    [
                        'task_id'      => 5,
                        'role_code'    => 'artist_2d',
                        'role_name'    => 'Game Artist 2D & Ukiyo-e',
                        'title'        => 'Icônes ukiyo-e de la Grenade Hōroku-hiya et de la Bombe Teppō-dama',
                        'description'  => 'Créer les assets haute définition des bombes sphériques en fonte cerclée de bronze et grenades de céramique.',
                        'assignee'     => 'Concept Artist 2D',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/items/horoku_grenade.png, public/assets/items/siege_bomb.png'
                    ],
                    [
                        'task_id'      => 6,
                        'role_code'    => 'artist_env',
                        'role_name'    => 'Environment & Isometric Artist',
                        'title'        => 'Modélisation des fonderies de bombes en activité dans la Grande Forge',
                        'description'  => 'Illustrer les cheminées crachant des étincelles, les moules en sable et les piles de boulets explosifs.',
                        'assignee'     => 'Isometric Artist',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'public/assets/buildings/forge_pyro_active.png'
                    ],
                    [
                        'task_id'      => 7,
                        'role_code'    => 'sound',
                        'role_name'    => 'Sound Designer & Compositeur',
                        'title'        => 'SFX de martelage d\'enclume, coulée de fonte liquide et sifflement de mèche',
                        'description'  => 'Enregistrer les ambiances immersives de forge d\'artillerie et les bruits de chargement de barils.',
                        'assignee'     => 'Sound Designer',
                        'complexity'   => 'S',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/sounds/forge_bomb_craft.mp3'
                    ],
                    [
                        'task_id'      => 8,
                        'role_code'    => 'narrative',
                        'role_name'    => 'Narrative Designer & Scénariste',
                        'title'        => 'Fiches descriptives et contextes d\'utilisation des engins pyrotechniques',
                        'description'  => 'Rédiger les infobulles tactiques expliquant la distinction entre grenades (anti-troupe) et bombes (anti-murs).',
                        'assignee'     => 'Scénariste Principal',
                        'complexity'   => 'S',
                        'status'       => 'done',
                        'deliverables' => 'core/ItemDescriptions.php'
                    ],
                    [
                        'task_id'      => 9,
                        'role_code'    => 'qa',
                        'role_name'    => 'QA Tester & Recetteur',
                        'title'        => 'Banc de tests unitaires sur les recettes et déductions de stocks rares',
                        'description'  => 'Valider la déduction exacte des ressources (Nitrate, Soufre, Charbon, Satetsu, Or) et l\'annulation sécurisée.',
                        'assignee'     => 'QA Automation Engineer',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'tests/test_forge_pyro_craft.php'
                    ],
                    [
                        'task_id'      => 10,
                        'role_code'    => 'producer',
                        'role_name'    => 'Producteur Live Ops & Économiste',
                        'title'        => 'Surveillance des volumes de production avant le grand week-end de siège',
                        'description'  => 'Analyser le stock moyen de bombes par clan pour s\'assurer que les alliances disposent de l\'arsenal nécessaire.',
                        'assignee'     => 'Live Ops Producer',
                        'complexity'   => 'S',
                        'status'       => 'todo',
                        'deliverables' => 'Tableau de bord de monitoring Game Studio'
                    ]
                ]
            ],
            // ÉVÉNEMENT SPÉCIAL 4 - LA GUERRE DES 12 CHÂTEAUX (ACTE I)
            [
                'slug'              => 'event-decret-12-chateaux',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'Le Décret des Douze Châteaux (Genson Jūni Tenshu)',
                'subtitle'          => 'Événement Mondial : L\'Éveil des 12 Châteaux Authentiques et l\'Alchimie du Kayaku',
                'era'               => '1582 - Apogée du Sengoku Jidai',
                'synopsis'          => 'Le Premier Shōgun fit jadis sceller les douze reliques impériales dans les 12 Châteaux Authentiques (Himeji, Matsumoto, Hikone, etc.). Un décret impérial mondial proclame l\'ouverture de la Grande Guerre : pour prétendre au Shōgunat absolu, toute alliance doit conquérir au moins 2 de ces 12 forteresses imprenables. Face à leurs remparts cyclopéens, les katanas sont impuissants. L\'Académie des Savoirs dévoile les premiers manuscrits secrets de la Poudre Noire (Kayaku).',
                'speaker_name'      => 'Takayama Kagetora',
                'speaker_role'      => 'Grand Maître Artificier du Hōjutsu (Artillerie & Poudre Noire)',
                'speaker_avatar'    => '🧨',
                'dialogue'          => 'Noble Daimyō ! Le décret impérial a retenti : seule l\'Alliance qui fera tomber au moins deux des Douze Châteaux Authentiques réunira les Sceaux sacrés pour ériger le Dōjō Impérial et sceller le destin du Japon ! Mais vos katanas se briseront sur les pierres millénaires de Himeji et de Matsumoto. Ouvrez sans délai votre Académie des Savoirs et poussez la recherche de la Poudre Noire (Kayaku) jusqu\'au Niveau 20 ! Le feu tonnerre sera notre seule clé.',
                'trigger_condition' => 'Déclenchement mondial de la Guerre des 12 Châteaux (Feature Flag Mondial / Live Ops)',
                'rewards_summary'   => 'Éveil des 12 Châteaux sur la Carte du Monde, Déblocage de la recherche « Poudre Noire (Kayaku) » dans l\'Académie des Savoirs, 500 Salpêtre (Nitrate), 300 Soufre, 500 Charbon',
                'choices'           => [
                    ['label' => '🧨 Ouvrir l\'Académie & chercher le Kayaku', 'outcome' => 'Apparition de la Poudre Noire dans l\'Académie (+500 Salpêtre, +300 Soufre)', 'style' => 'crimson'],
                    ['label' => '🗺️ Dépêcher des éclaireurs vers les 12 Châteaux', 'outcome' => 'Révélation des coordonnées cartographiques des 12 Châteaux Authentiques', 'style' => 'warning']
                ],
                'status'            => 'in_production',
                'created_by'        => 1,
                'tasks'             => [
                    [
                        'task_id'      => 1,
                        'role_code'    => 'level_designer',
                        'role_name'    => 'Game & Level Designer',
                        'title'        => 'Mapping cartographique des 12 Châteaux Authentiques & équilibrage des PV',
                        'description'  => 'Définir l\'emplacement sur la World Map des 12 forteresses historiques et leurs statistiques défensives (2,5M PV, 1M remparts).',
                        'assignee'     => 'Lead Level Designer',
                        'complexity'   => 'L',
                        'status'       => 'done',
                        'deliverables' => 'docs/gdd_bataille_12_chateaux.md (Section V)'
                    ],
                    [
                        'task_id'      => 2,
                        'role_code'    => 'backend',
                        'role_name'    => 'Développeur Back-End',
                        'title'        => 'Table SQL des 12 Châteaux et logique d\'apparition dynamique de black_powder',
                        'description'  => 'Créer la table authentic_castles avec état de siège et brancher l\'activation conditionnelle de la techno dans ResearchEngine.',
                        'assignee'     => 'Dev Back Senior',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'core/AuthenticCastlesEngine.php / migration SQL'
                    ],
                    [
                        'task_id'      => 3,
                        'role_code'    => 'frontend',
                        'role_name'    => 'Développeur Front-End',
                        'title'        => 'Bannière d\'alerte mondiale et pins de forteresses sur la World Map',
                        'description'  => 'Intégrer les pins dorés des 12 Châteaux sur la carte du monde avec barres de PV et nom du château historique.',
                        'assignee'     => 'Dev Front',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'views/world_map.php / partials/header.php'
                    ],
                    [
                        'task_id'      => 4,
                        'role_code'    => 'network',
                        'role_name'    => 'Développeur Réseau & Infra',
                        'title'        => 'Broadcast mondial Websocket de proclamation de la Guerre des 12 Châteaux',
                        'description'  => 'Diffuser la sonnerie du décret impérial à l\'ensemble des joueurs connectés simultanément.',
                        'assignee'     => 'Dev Réseau',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'core/SocketServer.php'
                    ],
                    [
                        'task_id'      => 5,
                        'role_code'    => 'artist_2d',
                        'role_name'    => 'Game Artist 2D & Ukiyo-e',
                        'title'        => 'Portrait ukiyo-e du Maître Artificier Takayama Kagetora',
                        'description'  => 'Peindre le portrait de Takayama Kagetora avec tenue de canonnier féodal, lunettes d\'artificier et mèche allumée.',
                        'assignee'     => 'Character Artist',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/characters/takayama_kagetora.jpg'
                    ],
                    [
                        'task_id'      => 6,
                        'role_code'    => 'artist_env',
                        'role_name'    => 'Environment & Isometric Artist',
                        'title'        => 'Illustrations isométriques des 12 Châteaux Historiques (Himeji, Matsumoto, etc.)',
                        'description'  => 'Dessiner les 12 donjons authentiques avec leurs spécificités architecturales (Himeji blanc, Matsumoto noir).',
                        'assignee'     => 'Isometric Artist',
                        'complexity'   => 'XL',
                        'status'       => 'in_progress',
                        'deliverables' => 'public/assets/castles/himeji.png, public/assets/castles/matsumoto.png...'
                    ],
                    [
                        'task_id'      => 7,
                        'role_code'    => 'sound',
                        'role_name'    => 'Sound Designer & Compositeur',
                        'title'        => 'Thème musical martial de proclamation du Décret Impérial',
                        'description'  => 'Composer l\'introduction dramatique avec taikos lourds, cornes de guerre et clameur de cour impériale.',
                        'assignee'     => 'Sound Designer',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/music/decret_12_chateaux.mp3'
                    ],
                    [
                        'task_id'      => 8,
                        'role_code'    => 'narrative',
                        'role_name'    => 'Narrative Designer & Scénariste',
                        'title'        => 'Écriture du Décret des Douze Sceaux et fiches historiques des 12 forteresses',
                        'description'  => 'Rédiger les notices historiques authentiques de chaque forteresse du patrimoine national japonais.',
                        'assignee'     => 'Scénariste Principal',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'docs/lore_12_chateaux_authentiques.md'
                    ],
                    [
                        'task_id'      => 9,
                        'role_code'    => 'qa',
                        'role_name'    => 'QA Tester & Recetteur',
                        'title'        => 'Tests de non-régression sur l\'éveil des châteaux et l\'ouverture de la recherche',
                        'description'  => 'Valider que la techno Kayaku apparaît correctement dès l\'activation du feature flag sans corrompre les arbres existants.',
                        'assignee'     => 'QA Lead',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'tests/test_12_castles_activation.php'
                    ],
                    [
                        'task_id'      => 10,
                        'role_code'    => 'producer',
                        'role_name'    => 'Producteur Live Ops & Économiste',
                        'title'        => 'Cadrage de l\'événement mondial et synchronisation des communications communautaires',
                        'description'  => 'Planifier l\'annonce Discord, l\'e-mail aux joueurs et la durée de la saison de siège des 12 châteaux.',
                        'assignee'     => 'Live Ops Producer',
                        'complexity'   => 'S',
                        'status'       => 'todo',
                        'deliverables' => 'Plan de release Live Ops 2.0'
                    ]
                ]
            ],
            // ÉVÉNEMENT SPÉCIAL 5 - LA GUERRE DES 12 CHÂTEAUX (ACTE IV)
            [
                'slug'              => 'event-deux-piliers-alliance',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'Les Deux Piliers de l\'Alliance : Conquête des Châteaux Authentiques',
                'subtitle'          => 'Événement d\'Alliance Majeur : Règle impériale des 2 Châteaux et Sceaux Fondateurs',
                'era'               => '1585 - La Guerre des Reliques',
                'synopsis'          => 'Après des semaines de bombardements coordonnés en alliance où les grenades ont balayé les garnisons et les bombes ont éventré les murailles, l\'Alliance s\'empare d\'au moins 2 Châteaux Authentiques parmi les 12 du monde. Les deux premiers Sceaux Impériaux s\'illuminent, accordant à l\'Alliance le droit sacré de lancer la construction du Dōjō Impérial (Kōshitsu Dōjō).',
                'speaker_name'      => 'Takayama Kagetora',
                'speaker_role'      => 'Grand Maître Artificier du Hōjutsu',
                'speaker_avatar'    => '🧨',
                'dialogue'          => 'Hourra pour notre Alliance ! Les bannières ennemies sont tombées à Himeji et Matsumoto ! Nous détenons désormais les Deux Piliers sacrés du Shōgunat. Mais prenez garde : si nos rivaux coalisés nous reprennent une forteresse et nous font chuter sous le seuil des 2 châteaux, le chantier céleste sera gelé ! Protégez vos bastions et ordonnez la pose de la Première Pierre du Dōjō Impérial !',
                'trigger_condition' => 'L\'Alliance du joueur contrôle au minimum 2 Châteaux Authentiques parmi les 12 du monde (castles_owned >= 2)',
                'rewards_summary'   => 'Attribution des 2 Sceaux Impériaux Fondateurs, Déblocage du chantier suprême du « Dōjō Impérial (Kōshitsu Dōjō) », Bannière des Conquérants de Châteaux, +20% Prestige Mondial d\'Alliance',
                'choices'           => [
                    ['label' => '⛩️ Poser la Première Pierre du Dōjō Impérial', 'outcome' => 'Ouverture du Chantier Monumental du Dōjō Impérial pour toute l\'Alliance', 'style' => 'warning'],
                    ['label' => '🏯 Fortifier en urgence les deux forteresses conquises', 'outcome' => 'Régénération immédiate de 500 000 PV de remparts et garnison renforcée', 'style' => 'emerald']
                ],
                'status'            => 'in_production',
                'created_by'        => 1,
                'tasks'             => [
                    [
                        'task_id'      => 1,
                        'role_code'    => 'level_designer',
                        'role_name'    => 'Game & Level Designer',
                        'title'        => 'Règle stricte des 2 Châteaux minimum et mécanique de gel / dégel du Dōjō',
                        'description'  => 'Définir l\'état actif/gelé du chantier du Dōjō en fonction du count(castles_owned) et le bonus de +15% vitesse par château supplémentaire.',
                        'assignee'     => 'Alliance & System Designer',
                        'complexity'   => 'L',
                        'status'       => 'done',
                        'deliverables' => 'docs/gdd_bataille_12_chateaux.md (Section VI)'
                    ],
                    [
                        'task_id'      => 2,
                        'role_code'    => 'backend',
                        'role_name'    => 'Développeur Back-End',
                        'title'        => 'Trigger automatique de déblocage / suspension du Dōjō selon le compteur de châteaux',
                        'description'  => 'Développer la vérification transactionnelle à chaque capture/perte de forteresse pour mettre à jour alliance_dojo_status.',
                        'assignee'     => 'Dev Back Senior',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'core/AllianceCastleEngine.php'
                    ],
                    [
                        'task_id'      => 3,
                        'role_code'    => 'frontend',
                        'role_name'    => 'Développeur Front-End',
                        'title'        => 'Tableau de bord de l\'Alliance avec Sceaux Fondateurs et compteur de châteaux',
                        'description'  => 'Intégrer dans views/alliance.php le composant affichant les châteaux contrôlés (ex: 2/12) et l\'état du chantier du Dōjō.',
                        'assignee'     => 'Dev Front',
                        'complexity'   => 'M',
                        'status'       => 'in_progress',
                        'deliverables' => 'views/alliance.php / partials/alliance_castles_badge.php'
                    ],
                    [
                        'task_id'      => 4,
                        'role_code'    => 'network',
                        'role_name'    => 'Développeur Réseau & Infra',
                        'title'        => 'Synchronisation des sièges coordonnés et calcul de résonance d\'attaque',
                        'description'  => 'Gérer le moteur d\'impact synchronisé des flottes de bombardement arrivant dans une même fenêtre de 60 secondes.',
                        'assignee'     => 'Dev Réseau',
                        'complexity'   => 'XL',
                        'status'       => 'todo',
                        'deliverables' => 'core/CombatSyncEngine.php'
                    ],
                    [
                        'task_id'      => 5,
                        'role_code'    => 'artist_2d',
                        'role_name'    => 'Game Artist 2D & Ukiyo-e',
                        'title'        => 'Estampes des Sceaux Fondateurs (Pilier de la Terre & Pilier du Ciel)',
                        'description'  => 'Illustrer les 2 sceaux impériaux gravés d\'or et d\'encre rouge vermillon symbolisant la double souveraineté.',
                        'assignee'     => 'Concept Artist 2D',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/items/seal_earth.png, public/assets/items/seal_sky.png'
                    ],
                    [
                        'task_id'      => 6,
                        'role_code'    => 'artist_env',
                        'role_name'    => 'Environment & Isometric Artist',
                        'title'        => 'État visuel de forteresse conquise avec bannières de l\'alliance victorieuse',
                        'description'  => 'Créer les variations de remparts avec étendards flottants personnalisables aux couleurs du clan vainqueur.',
                        'assignee'     => 'Isometric Artist',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'public/assets/castles/captured_variant.png'
                    ],
                    [
                        'task_id'      => 7,
                        'role_code'    => 'sound',
                        'role_name'    => 'Sound Designer & Compositeur',
                        'title'        => 'Sonnerie triomphale d\'alliance et cri de guerre de victoire',
                        'description'  => 'Produire le jingle sonore retentissant lorsqu\'une forteresse est capturée et que le 2e sceau est validé.',
                        'assignee'     => 'Sound Designer',
                        'complexity'   => 'S',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/sounds/castle_capture_triumph.mp3'
                    ],
                    [
                        'task_id'      => 8,
                        'role_code'    => 'narrative',
                        'role_name'    => 'Narrative Designer & Scénariste',
                        'title'        => 'Chroniques des prises de châteaux et serment de bâtisseurs du Dōjō',
                        'description'  => 'Rédiger les messages d\'éloge impérial envoyés à tous les membres de l\'alliance à la capture du 2e château.',
                        'assignee'     => 'Scénariste Principal',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'docs/chroniques_siege_deux_chateaux.md'
                    ],
                    [
                        'task_id'      => 9,
                        'role_code'    => 'qa',
                        'role_name'    => 'QA Tester & Recetteur',
                        'title'        => 'Tests exhaustifs de la règle des 2 châteaux : gel immédiat si perte du 2e',
                        'description'  => 'Simuler la perte d\'un château sous attaque ennemie et vérifier que la construction du Dōjō est immédiatement suspendue.',
                        'assignee'     => 'QA Lead',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'tests/test_two_castles_freeze_logic.php'
                    ],
                    [
                        'task_id'      => 10,
                        'role_code'    => 'producer',
                        'role_name'    => 'Producteur Live Ops & Économiste',
                        'title'        => 'Analyse de la répartition géopolitique des châteaux entre les alliances majeures',
                        'description'  => 'Vérifier qu\'aucune alliance ne monopolise plus de 4 châteaux sans coalition adverse pour préserver la dynamique compétitive.',
                        'assignee'     => 'Lead Game Economist',
                        'complexity'   => 'S',
                        'status'       => 'todo',
                        'deliverables' => 'Rapport géopolitique Live Ops'
                    ]
                ]
            ],
            // ÉVÉNEMENT SPÉCIAL 6 - LA GUERRE DES 12 CHÂTEAUX (ACTE V - FIN DU JEU)
            [
                'slug'              => 'event-apotheose-dojo-imperial',
                'type'              => 'special_event',
                'step_number'       => null,
                'title'             => 'L\'Apothéose du Dōjō Impérial : Le Point Culminant de Fin de Partie',
                'subtitle'          => 'Événement Ultime : Achèvement du Niveau 10 du Dōjō Impérial & Victoire du Shōgunat',
                'era'               => '1588 - L\'Ère de l\'Harmonie Impériale (Fin du Jeu)',
                'synopsis'          => 'Mobilisant des millions de troncs de cèdres, de pierres de granit, de poutres de charpente, de tonnes d\'acier, de tuiles Hongawara, de tuiles démoniaques Onigawara et de kobans d\'or offerts par tous les membres de l\'Alliance, le Dōjō Impérial atteint son 10e et dernier niveau. Maintenu sous la garde indéfectible des 2 Châteaux Authentiques minimum, le sanctuaire suprême s\'illumine. L\'ère des guerres s\'achève : l\'Alliance est proclamée Maîtresse Suprême du Japon et entre vivante au Panthéon de l\'Histoire.',
                'speaker_name'      => 'Maître de la Sagesse (Chie no Sensei)',
                'speaker_role'      => 'Grand Maître Spirituel & Messager de l\'Empereur',
                'speaker_avatar'    => '🏯',
                'dialogue'          => 'Les cloches sacrées résonnent d\'Edo jusqu\'à Kyōto ! Regardez le Dōjō Impérial s\'élever au-dessus des nuages, reflet parfait de l\'honneur, de la ruse tactique et de l\'union fraternelle de votre Alliance. Les Douze Châteaux se prosternent devant vos deux bannières victorieuses. Par décret du Trône Impérial, votre Alliance est sacrée Gardienne de l\'Archipel (Seii Taishōgun). Votre légende est gravée dans l\'or pour l\'éternité !',
                'trigger_condition' => 'Achèvement du Niveau 10 du Dōjō Impérial par l\'Alliance + Maintien d\'au moins 2 Châteaux Authentiques',
                'rewards_summary'   => 'Victoire Finale de la Partie d\'OpenShogun, Titre Suprême : « Seii Taishōgun & Gardiens du Dōjō Impérial », Inscription au Panthéon des Vainqueurs, Aura Dorée Mondiale et Relique d\'Or Éternelle',
                'choices'           => [
                    ['label' => '👑 Proclamer l\'Avènement du Nouveau Shōgunat', 'outcome' => 'Déclenchement du Sacre Mondial & Entrée Triomphale au Panthéon d\'OpenShogun', 'style' => 'warning'],
                    ['label' => '🎌 Graver les noms de tous les membres de l\'Alliance', 'outcome' => 'Palmarès d\'Honneur avec distribution des Reliques d\'Or Impériales à tous les frères d\'armes', 'style' => 'emerald']
                ],
                'status'            => 'in_production',
                'created_by'        => 1,
                'tasks'             => [
                    [
                        'task_id'      => 1,
                        'role_code'    => 'level_designer',
                        'role_name'    => 'Game & Level Designer',
                        'title'        => 'Équilibrage des 10 paliers de construction du Dōjō Impérial et conditions de victoire',
                        'description'  => 'Définir les coûts colossaux de chaque palier (Poutres, Granit, Acier, Or, Honneur) et les conditions strictes de victoire finale.',
                        'assignee'     => 'Lead Game Designer',
                        'complexity'   => 'XL',
                        'status'       => 'done',
                        'deliverables' => 'docs/gdd_bataille_12_chateaux.md (Section VI)'
                    ],
                    [
                        'task_id'      => 2,
                        'role_code'    => 'backend',
                        'role_name'    => 'Développeur Back-End',
                        'title'        => 'Routine de Fin de Jeu : détection du Niveau 10, gel du monde et Panthéon SQL',
                        'description'  => 'Développer le script transactionnel de couronnement mondial, gel des actions hostiles et enregistrement de l\'alliance au Panthéon.',
                        'assignee'     => 'Lead Dev Back',
                        'complexity'   => 'XL',
                        'status'       => 'done',
                        'deliverables' => 'core/ImperialDojoEngine.php / core/FleetEngine.php'
                    ],
                    [
                        'task_id'      => 3,
                        'role_code'    => 'frontend',
                        'role_name'    => 'Développeur Front-End',
                        'title'        => 'Écran d\'apothéose mondiale de fin de jeu et vue monumentale du Dōjō Impérial',
                        'description'  => 'Concevoir l\'écran de couronnement avec feux d\'artifice, bannière impériale géante, liste des vainqueurs et reliques dorées.',
                        'assignee'     => 'Lead Dev Front',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'views/endgame_apotheose.php / views/pantheon.php'
                    ],
                    [
                        'task_id'      => 4,
                        'role_code'    => 'network',
                        'role_name'    => 'Développeur Réseau & Infra',
                        'title'        => 'Verrouillage sécurisé des serveurs et broadcast mondial de victoire',
                        'description'  => 'Assurer la diffusion instantanée de l\'écran de victoire à 100% des joueurs connectés avec bascule en mode célébration.',
                        'assignee'     => 'Dev Réseau & SecOps',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'core/ServerLockdownService.php'
                    ],
                    [
                        'task_id'      => 5,
                        'role_code'    => 'artist_2d',
                        'role_name'    => 'Game Artist 2D & Ukiyo-e',
                        'title'        => 'Grande fresque triomphale d\'apothéose du Dōjō Impérial sous la pleine lune',
                        'description'  => 'Peindre l\'illustration finale de fin de jeu au format panoramique 16:9 haute résolution pour le Panthéon.',
                        'assignee'     => 'Lead Concept Artist',
                        'complexity'   => 'XL',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/illustrations/imperial_dojo_apotheose.jpg'
                    ],
                    [
                        'task_id'      => 6,
                        'role_code'    => 'artist_env',
                        'role_name'    => 'Environment & Isometric Artist',
                        'title'        => 'Modélisation des 10 stades d\'élévation isométrique du Dōjō Impérial',
                        'description'  => 'Modéliser les 10 rendus isométriques du sanctuaire, depuis les fondations cyclopéennes jusqu\'au dôme céleste laqué d\'or.',
                        'assignee'     => 'Lead 3D/Isometric Artist',
                        'complexity'   => 'XL',
                        'status'       => 'in_progress',
                        'deliverables' => 'public/assets/buildings/dojo_tier1.png... dojo_tier10.png'
                    ],
                    [
                        'task_id'      => 7,
                        'role_code'    => 'sound',
                        'role_name'    => 'Sound Designer & Compositeur',
                        'title'        => 'Hymne triomphal impérial pour l\'Apothéose de fin de jeu',
                        'description'  => 'Composer la symphonie féodale finale avec grand ensemble de taiko, koto céleste, flûte shakuhachi et cloches shinto.',
                        'assignee'     => 'Compositeur Audio',
                        'complexity'   => 'L',
                        'status'       => 'done',
                        'deliverables' => 'public/assets/music/apotheose_victory_theme.mp3'
                    ],
                    [
                        'task_id'      => 8,
                        'role_code'    => 'narrative',
                        'role_name'    => 'Narrative Designer & Scénariste',
                        'title'        => 'Texte solennel du Sacre Impérial et éloge des héros du serveur',
                        'description'  => 'Rédiger le parchemin final proclamant la dynastie victorieuse et clôturant l\'arc narratif d\'OpenShogun.',
                        'assignee'     => 'Lead Narrative Designer',
                        'complexity'   => 'M',
                        'status'       => 'done',
                        'deliverables' => 'docs/epilogue_sacre_imperial.md'
                    ],
                    [
                        'task_id'      => 9,
                        'role_code'    => 'qa',
                        'role_name'    => 'QA Tester & Recetteur',
                        'title'        => 'Recette intégrale du cycle de victoire et persistance du Panthéon SQL',
                        'description'  => 'Tester le déclenchement de la victoire sur serveur de staging, vérifier l\'exactitude des récompenses et la non-régression.',
                        'assignee'     => 'QA Lead',
                        'complexity'   => 'L',
                        'status'       => 'in_progress',
                        'deliverables' => 'tests/test_endgame_victory_flow.php'
                    ],
                    [
                        'task_id'      => 10,
                        'role_code'    => 'producer',
                        'role_name'    => 'Producteur Live Ops & Économiste',
                        'title'        => 'Plan de clôture de saison, remise des prix et teaser de l\'Ère suivante',
                        'description'  => 'Organiser la cérémonie de clôture du serveur, la remise des badges permanents et le planning du reset.',
                        'assignee'     => 'Executive Producer',
                        'complexity'   => 'M',
                        'status'       => 'todo',
                        'deliverables' => 'Feuille de route de fin de saison & Reset'
                    ]
                ]
            ]
        ];
    }

    /**
     * Analyse un fichier Markdown (.md) pour extraire le Synopsis & Contexte Historique
     * ainsi que les métadonnées narratives éventuelles (Titre, Sous-titre, Ère, Personnage, Dialogue, Triggers, Récompenses).
     */
    public function parseMarkdownToScenario(string $markdown, string $filename = ''): array {
        $raw = str_replace(["\r\n", "\r"], "\n", trim($markdown));
        if ($raw === '') {
            return [
                'title'             => $filename ? pathinfo($filename, PATHINFO_FILENAME) : 'Scénario Importé (.md)',
                'subtitle'          => '',
                'era'               => '1580 - Sengoku Jidai',
                'synopsis'          => '',
                'speaker_name'      => '',
                'speaker_role'      => '',
                'speaker_avatar'    => '🏯',
                'dialogue'          => '',
                'trigger_condition' => '',
                'rewards_summary'   => '',
            ];
        }

        // 1. Extraction du Titre principal (# Titre)
        $title = '';
        if (preg_match('/^#\s+(.+)$/m', $raw, $mTitle)) {
            $title = trim(preg_replace('/^[🏯⚔️📜🔥⛩️🏮🧨]+\s*/u', '', strip_tags($mTitle[1])));
        }
        if ($title === '' && $filename !== '') {
            $title = ucwords(str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
        }
        if ($title === '') {
            $title = 'Scénario Importé (.md)';
        }

        // 2. Extraction du Sous-titre (## ... ou ligne d'accroche)
        $subtitle = '';
        if (preg_match('/^##\s+(.+)$/m', $raw, $mSub)) {
            $subtitle = trim(preg_replace('/^[🏯⚔️📜🔥⛩️🏮🧨IVX\.\s]+/u', '', strip_tags($mSub[1])));
        }

        // 3. Extraction de la Période / Ère Historique si mentionnée
        $era = '1580 - Sengoku Jidai';
        if (preg_match('/\*\*Période\s*(?:\/\s*Ère)?\s*:\*\*\s*(.+)$/mi', $raw, $mEra)) {
            $era = trim($mEra[1]);
        } elseif (preg_match('/(15\d{2}|16\d{2})\s*[-–]\s*([^\n\*]+)/u', $raw, $mEra2)) {
            $era = trim($mEra2[1] . ' - ' . $mEra2[2]);
        }

        // 4. Extraction du Personnage & Dialogue (citation > *« ... »* ou > "...")
        $speakerName = '';
        $speakerRole = '';
        $speakerAvatar = '🏯';
        $dialogue = '';

        if (preg_match('/\*\*(?:Personnage|Interlocuteur)\s*:\*\*\s*([^\n]+)/iu', $raw, $mSp)) {
            $spLine = trim(str_replace('*', '', $mSp[1]));
            $parts = preg_split('/[,—–-]/u', $spLine, 2);
            $speakerName = trim($parts[0] ?? '');
            if (!empty($parts[1])) {
                $speakerRole = trim(preg_replace('/Avatar\s*:.*/iu', '', $parts[1]));
            }
        }

        if (preg_match('/^>\s*\*?[«"]?([^»"\n]{25,})[»"]?\*?/m', $raw, $mDia)) {
            $dialogue = trim(str_replace(['*', '«', '»', '"'], '', $mDia[1]));
        }

        // 5. Extraction Trigger & Récompenses éventuels
        $trigger = '';
        if (preg_match('/\*\*(?:Condition(?:s)? d\'activation|Trigger|Déclencheur|Objectif Stratégique(?: Majeur)?)\s*:\*\*\s*([^\n]+)/iu', $raw, $mTr)) {
            $trigger = trim(str_replace('*', '', $mTr[1]));
        }

        $rewards = '';
        if (preg_match('/\*\*(?:Récompenses?(?:\s*&\s*Effets)?|Dotation)\s*:\*\*\s*([^\n]+)/iu', $raw, $mRw)) {
            $rewards = trim(str_replace('*', '', $mRw[1]));
        }

        // 6. Le Synopsis & Contexte Historique reçoit le contenu Markdown complet (ou la section Synopsis/Histoire si présente, suivie du document complet)
        return [
            'title'             => $title,
            'subtitle'          => $subtitle,
            'era'               => $era,
            'synopsis'          => $raw,
            'speaker_name'      => $speakerName,
            'speaker_role'      => $speakerRole,
            'speaker_avatar'    => $speakerAvatar,
            'dialogue'          => $dialogue,
            'trigger_condition' => $trigger,
            'rewards_summary'   => $rewards,
        ];
    }

    /**
     * Helper de slugification
     */
    private function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'scenario-' . time() : $text;
    }
}

