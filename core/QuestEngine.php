<?php
/**
 * Moteur des Quêtes Didacticiel & Voie du Daimyō (OpenShogun)
 * Système d'initiation et de progression féodale inspiré des Maîtres de Quête de Travian
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PlanetEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class QuestEngine {
    private PDO $db;
    private PlanetEngine $planetEngine;
    private static bool $schemaChecked = false;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->planetEngine = new PlanetEngine();
        $this->ensureSchema();
    }

    /**
     * Garantit automatiquement la présence de la table user_quests
     */
    public function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `user_quests` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `quest_key` VARCHAR(50) NOT NULL,
                    `status` ENUM('in_progress', 'completed', 'claimed') NOT NULL DEFAULT 'in_progress',
                    `progress` INT UNSIGNED NOT NULL DEFAULT 0,
                    `completed_at` DATETIME NULL,
                    `claimed_at` DATETIME NULL,
                    UNIQUE KEY `uniq_user_quest` (`user_id`, `quest_key`),
                    KEY `idx_uq_user` (`user_id`),
                    KEY `idx_uq_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {
            // Ignorer si déjà existant
        }
    }

    /**
     * Catalogue complet des 12 quêtes du Didacticiel Féodal
     */
    public static function getCatalog(): array {
        return [
            'wood_field_lvl1' => [
                'key' => 'wood_field_lvl1',
                'order' => 1,
                'category' => 'terroir',
                'title' => 'Premier Arpent de Cèdre',
                'objective' => 'Élever un Camp de Bûcherons au niveau 1',
                'icon' => '🪵',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Bienvenue dans vos terres, noble Daimyō ! Pour ériger vos fortifications et tailler les lances de vos conscrits, le bois des cèdres centenaires est indispensable. Rendez-vous sur votre Terroir et ordonnez à vos bûcherons d\'ouvrir leur premier campement.',
                'action_url' => '?page=resources',
                'action_label' => 'Ouvrir le Terroir',
                'target_slot_hint' => 'Parcelles 1 à 5',
                'rewards' => [
                    'metal' => 150,
                    'crystal' => 150,
                    'deuterium' => 100,
                    'points' => 10,
                ],
                'check' => ['type' => 'field_level', 'field_type' => 'metal_mine', 'min_level' => 1]
            ],
            'stone_field_lvl1' => [
                'key' => 'stone_field_lvl1',
                'order' => 2,
                'category' => 'terroir',
                'title' => 'Fondations de Granit',
                'objective' => 'Élever une Carrière de Pierre au niveau 1',
                'icon' => '🪨',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Les remparts cyclopéens (Nozura-zumi) qui résisteront aux béliers et aux tirs ennemis ne peuvent s\'édifier sans blocs de granit massifs. Extrayez la pierre des coteaux pour asseoir la solidité de votre forteresse.',
                'action_url' => '?page=resources',
                'action_label' => 'Ouvrir le Terroir',
                'target_slot_hint' => 'Parcelles 6 à 10',
                'rewards' => [
                    'metal' => 150,
                    'crystal' => 150,
                    'deuterium' => 100,
                    'points' => 10,
                ],
                'check' => ['type' => 'field_level', 'field_type' => 'crystal_mine', 'min_level' => 1]
            ],
            'rice_field_lvl1' => [
                'key' => 'rice_field_lvl1',
                'order' => 3,
                'category' => 'terroir',
                'title' => 'Nourrir le Domaine',
                'objective' => 'Élever une Rizière Inondée au niveau 1',
                'icon' => '🌾',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Au Japon des provinces en guerre, le riz impérial (Koku) est l\'essence même de la puissance. Il nourrit vos sujets, permet l\'entretien de vos samouraïs et sert de monnaie d\'échange pour vos campagnes.',
                'action_url' => '?page=resources',
                'action_label' => 'Ouvrir le Terroir',
                'target_slot_hint' => 'Parcelles 11 à 14',
                'rewards' => [
                    'metal' => 100,
                    'crystal' => 100,
                    'deuterium' => 250,
                    'points' => 10,
                ],
                'check' => ['type' => 'field_level', 'field_type' => 'deuterium_synth', 'min_level' => 1]
            ],
            'shrine_field_lvl1' => [
                'key' => 'shrine_field_lvl1',
                'order' => 4,
                'category' => 'terroir',
                'title' => 'Ferveur & Sérénité',
                'objective' => 'Élever un Sanctuaire Shintō au niveau 1',
                'icon' => '⛩️',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'L\'harmonie spirituelle apaise les esprits des paysans et appelle la bénédiction des Kamis. Sans sérénité suffisante, le travail aux champs ralentit lourdement. Érigez un sanctuaire pour maintenir la vigueur spirituelle.',
                'action_url' => '?page=resources',
                'action_label' => 'Ouvrir le Terroir',
                'target_slot_hint' => 'Parcelles 15 à 18',
                'rewards' => [
                    'metal' => 120,
                    'crystal' => 120,
                    'deuterium' => 120,
                    'points' => 30,
                ],
                'check' => ['type' => 'field_level', 'field_type' => 'solar_plant', 'min_level' => 1]
            ],
            'tenshu_lvl2' => [
                'key' => 'tenshu_lvl2',
                'order' => 5,
                'category' => 'city',
                'title' => 'Le Siège du Commandement',
                'objective' => 'Agrandir le Tenshu (Donjon Castral) au niveau 2',
                'icon' => '🏯',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Visitez la Cité Castrale ! Le Tenshu est la tour maîtresse d\'où vous gouvernez la province. Chaque niveau supplémentaire accélère la vitesse de construction de tous les édifices et parcelles de votre domaine.',
                'action_url' => '?page=city',
                'action_label' => 'Visiter la Cité Castrale',
                'target_slot_hint' => 'Emplacement central (Tenshu)',
                'rewards' => [
                    'metal' => 200,
                    'crystal' => 200,
                    'deuterium' => 200,
                    'points' => 20,
                ],
                'check' => ['type' => 'building_level', 'building' => 'hq', 'min_level' => 2]
            ],
            'storage_expansion' => [
                'key' => 'storage_expansion',
                'order' => 6,
                'category' => 'city',
                'title' => 'Sécuriser les Récoltes',
                'objective' => 'Élever l\'Entrepôt de Matériaux ou le Grenier au niveau 2',
                'icon' => '📦',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Les récoltes abondantes risquent de déborder et d\'être perdues si vos réserves sont trop étroites ! Agrandissez votre Entrepôt ou votre Grenier à riz au niveau 2 pour stocker davantage de vivres et matériaux.',
                'action_url' => '?page=city',
                'action_label' => 'Visiter la Cité Castrale',
                'target_slot_hint' => 'Entrepôt ou Grenier',
                'rewards' => [
                    'metal' => 250,
                    'crystal' => 250,
                    'deuterium' => 250,
                    'points' => 20,
                ],
                'check' => ['type' => 'building_or_level', 'buildings' => ['storage' => 2, 'tank' => 2]]
            ],
            'city_wall_lvl1' => [
                'key' => 'city_wall_lvl1',
                'order' => 7,
                'category' => 'city',
                'title' => 'Enceinte & Fortifications',
                'objective' => 'Ériger la Muraille & Remparts de Cité au niveau 1',
                'icon' => '🧱',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Un domaine sans remparts attire les pillards comme le miel attire les frelons. Érigez une solide enceinte fortifiée autour de votre cité pour procurer un solide bonus de protection à votre garnison.',
                'action_url' => '?page=city',
                'action_label' => 'Ériger la Muraille',
                'target_slot_hint' => 'Remparts extérieurs (Slot 34)',
                'rewards' => [
                    'metal' => 300,
                    'crystal' => 300,
                    'deuterium' => 200,
                    'points' => 25,
                ],
                'check' => ['type' => 'building_level', 'building' => 'wall', 'min_level' => 1]
            ],
            'train_garrison' => [
                'key' => 'train_garrison',
                'order' => 8,
                'category' => 'military',
                'title' => 'Lever les Armes au Dojo',
                'objective' => 'Entraîner des recrues et rassembler au moins 20 guerriers',
                'icon' => '🥋',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Le tambour de guerre résonne ! Rendez-vous au Dojo Militaire pour former vos guerriers d\'élite. Une garnison d\'au moins 20 soldats disciplinés imposera le respect à vos rivaux.',
                'action_url' => '?page=barracks',
                'action_label' => 'Ouvrir le Dojo',
                'target_slot_hint' => 'Dojo Militaire & Recrutement',
                'rewards' => [
                    'metal' => 200,
                    'crystal' => 150,
                    'deuterium' => 300,
                    'points' => 30,
                    'bonus_units' => 5 // 5 recrues d'honneur offertes selon clan
                ],
                'check' => ['type' => 'total_units', 'min_count' => 20]
            ],
            'explore_map' => [
                'key' => 'explore_map',
                'order' => 9,
                'category' => 'exploration',
                'title' => 'L\'Œil sur les Provinces',
                'objective' => 'Consulter la Carte des Provinces et repérer les environs',
                'icon' => '🗺️',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Déployez la carte féodale du Japon ! Repérez les coordonnées de votre domaine, les oasis sauvages peuplées d\'animaux féroces regorgeant de ressources, et les 12 châteaux authentiques qui domineront la guerre finale.',
                'action_url' => '?page=map',
                'action_label' => 'Explorer la Carte',
                'target_slot_hint' => 'Médaillon Provinces / Carte',
                'rewards' => [
                    'metal' => 250,
                    'crystal' => 250,
                    'deuterium' => 250,
                    'points' => 25,
                ],
                'check' => ['type' => 'action_recorded', 'action_key' => 'visit_map']
            ],
            'daimyo_motto' => [
                'key' => 'daimyo_motto',
                'order' => 10,
                'category' => 'honor',
                'title' => 'Proclamation du Daimyō',
                'objective' => 'Personnaliser votre Fiche de Daimyō et rédiger votre Devise',
                'icon' => '📜',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'Tout grand chef de guerre grave sa volonté dans la mémoire des hommes. Ouvrez votre fiche officielle de Daimyō et inscrivez votre devise de clan pour proclamer vos ambitions.',
                'action_url' => 'javascript:openEditMottoModal()',
                'action_label' => 'Modifier ma Devise',
                'target_slot_hint' => 'Profil Daimyō en haut à droite',
                'rewards' => [
                    'metal' => 350,
                    'crystal' => 350,
                    'deuterium' => 350,
                    'points' => 50,
                ],
                'check' => ['type' => 'custom_bio']
            ],
            'build_academy' => [
                'key' => 'build_academy',
                'order' => 11,
                'category' => 'city',
                'title' => 'Forge & Savoir Militaire',
                'objective' => 'Bâtir l\'Académie des Savoirs & Forge au niveau 1',
                'icon' => '🔬',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'La force physique sans l\'art de la guerre n\'est rien. Bâtissez l\'Académie des Savoirs pour étudier les tactiques de siège, la forge du tamahagane et l\'art des relais de poste.',
                'action_url' => '?page=city',
                'action_label' => 'Bâtir l\'Académie',
                'target_slot_hint' => 'Cité Castrale (Académie)',
                'rewards' => [
                    'metal' => 400,
                    'crystal' => 400,
                    'deuterium' => 300,
                    'points' => 35,
                ],
                'check' => ['type' => 'building_level', 'building' => 'research_lab', 'min_level' => 1]
            ],
            'first_expedition' => [
                'key' => 'first_expedition',
                'order' => 12,
                'category' => 'military',
                'title' => 'La Première Expédition',
                'objective' => 'Dépêcher une mission d\'armée (raid, transport ou espionnage)',
                'icon' => '🐎',
                'mentor_name' => 'Katsumoto, Maître d\'Armes',
                'lore' => 'L\'heure de la consécration est venue ! Préparez une escouade et dépêchez-la sur les routes provinciales : partez piller les réserves d\'une oasis sauvage gardée par des bêtes ou envoyez un éclaireur sonder un domaine rival.',
                'action_url' => '?page=fleet',
                'action_label' => 'Dépêcher une Expédition',
                'target_slot_hint' => 'Écran des Expéditions',
                'rewards' => [
                    'metal' => 600,
                    'crystal' => 600,
                    'deuterium' => 600,
                    'points' => 100,
                ],
                'check' => ['type' => 'fleet_mission_sent']
            ]
        ];
    }

    /**
     * Récupère l'état de toutes les quêtes pour un joueur
     * Vérifie dynamiquement les critères d'accomplissement
     */
    public function getPlayerQuestsStatus(int $userId, int $planetId): array {
        $catalog = self::getCatalog();

        // Récupérer les enregistrements existants dans user_quests
        $stmt = $this->db->prepare("SELECT * FROM user_quests WHERE user_id = ?");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $userQuests = [];
        foreach ($rows as $r) {
            $userQuests[$r['quest_key']] = $r;
        }

        // Données du domaine pour validation
        $buildings = $this->planetEngine->getBuildings($planetId);
        $fields = $this->planetEngine->getFields($planetId);
        $user = $this->getUserData($userId);

        $questList = [];
        $activeQuest = null;
        $claimableCount = 0;
        $claimedCount = 0;
        $totalQuests = count($catalog);

        foreach ($catalog as $key => $q) {
            $record = $userQuests[$key] ?? null;
            $status = $record['status'] ?? 'in_progress';
            $progress = (int)($record['progress'] ?? 0);

            // Si déjà réclamée, rien à ré-évaluer
            if ($status === 'claimed') {
                $claimedCount++;
                $questList[] = array_merge($q, [
                    'status' => 'claimed',
                    'progress' => 100,
                    'claimed_at' => $record['claimed_at'] ?? null,
                    'is_claimable' => false,
                ]);
                continue;
            }

            // Évaluation dynamique de la complétion
            $isMet = $this->evaluateCondition($q['check'], $userId, $planetId, $buildings, $fields, $user);

            if ($isMet && $status !== 'completed') {
                $status = 'completed';
                $this->upsertQuestStatus($userId, $key, 'completed', 100);
            }

            $isClaimable = ($status === 'completed');
            if ($isClaimable) {
                $claimableCount++;
            }

            $questItem = array_merge($q, [
                'status' => $status,
                'progress' => $isMet ? 100 : $progress,
                'is_claimable' => $isClaimable,
                'claimed_at' => null,
            ]);

            $questList[] = $questItem;

            // La première quête non réclamée devient la quête active
            if ($activeQuest === null) {
                $activeQuest = $questItem;
            }
        }

        $allCompleted = ($claimedCount === $totalQuests);
        $overallPercent = round(($claimedCount / max(1, $totalQuests)) * 100);

        return [
            'quests' => $questList,
            'active_quest' => $activeQuest,
            'claimable_count' => $claimableCount,
            'claimed_count' => $claimedCount,
            'total_quests' => $totalQuests,
            'all_completed' => $allCompleted,
            'overall_percent' => $overallPercent,
        ];
    }

    /**
     * Réclamer la récompense d'une quête
     */
    public function claimReward(int $userId, int $planetId, string $questKey): array {
        $catalog = self::getCatalog();
        if (!isset($catalog[$questKey])) {
            return ['success' => false, 'error' => 'Quête inconnue au registre du Shogunat.'];
        }

        $quest = $catalog[$questKey];

        // Vérifier le statut actuel
        $statusData = $this->getPlayerQuestsStatus($userId, $planetId);
        $targetQuest = null;
        foreach ($statusData['quests'] as $q) {
            if ($q['key'] === $questKey) {
                $targetQuest = $q;
                break;
            }
        }

        if (!$targetQuest || $targetQuest['status'] === 'claimed') {
            return ['success' => false, 'error' => 'Cette récompense a déjà été réclamée.'];
        }

        if ($targetQuest['status'] !== 'completed') {
            return ['success' => false, 'error' => 'Les exigences de cette quête ne sont pas encore accomplies.'];
        }

        $this->db->beginTransaction();
        try {
            // 1. Créditer les ressources sur la planète
            $rewards = $quest['rewards'];
            $addMetal = (int)($rewards['metal'] ?? 0);
            $addCrystal = (int)($rewards['crystal'] ?? 0);
            $addDeut = (int)($rewards['deuterium'] ?? 0);
            $addPoints = (int)($rewards['points'] ?? 0);
            $bonusUnitsCount = (int)($rewards['bonus_units'] ?? 0);

            $stmtPlanet = $this->db->prepare("
                UPDATE planets 
                SET metal = metal + ?, crystal = crystal + ?, deuterium = deuterium + ? 
                WHERE id = ?
            ");
            $stmtPlanet->execute([$addMetal, $addCrystal, $addDeut, $planetId]);

            // 2. Créditer les points de prestige / honneur à l'utilisateur
            if ($addPoints > 0) {
                $stmtUser = $this->db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $stmtUser->execute([$addPoints, $userId]);
            }

            // 3. Si renforts de troupes en récompense
            $rewardedUnitName = '';
            if ($bonusUnitsCount > 0) {
                $user = $this->getUserData($userId);
                $faction = $user['faction'] ?? 'terran';
                $unitCodeMap = [
                    'terran' => 'piquier_ashigaru_yari',
                    'vorash' => 'fantassin_leger_takeda',
                    'aethelis' => 'sentinelle_yari_tokugawa'
                ];
                $unitCode = $unitCodeMap[$faction] ?? 'piquier_ashigaru_yari';

                $stmtUnits = $this->db->prepare("
                    INSERT INTO planet_units (planet_id, unit_code, count) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE count = count + VALUES(count)
                ");
                $stmtUnits->execute([$planetId, $unitCode, $bonusUnitsCount]);

                // Récupérer le nom de l'unité
                $stmtUName = $this->db->prepare("SELECT name FROM units WHERE code = ?");
                $stmtUName->execute([$unitCode]);
                $rewardedUnitName = $stmtUName->fetchColumn() ?: 'Soldats d\'élite';
            }

            // 4. Marquer la quête comme claimed
            $this->upsertQuestStatus($userId, $questKey, 'claimed', 100, true);

            // 5. Si 12ème quête terminée, octroyer une médaille d'honneur de fin de didacticiel
            $checkAllClaimed = $this->db->prepare("
                SELECT COUNT(*) FROM user_quests WHERE user_id = ? AND status = 'claimed'
            ");
            $checkAllClaimed->execute([$userId]);
            $claimedTotal = (int)$checkAllClaimed->fetchColumn();

            $awardedMedal = false;
            if ($claimedTotal >= count($catalog)) {
                // Attribuer médaille de progression de bienvenue / Maître du Shogunat (+100 Koban)
                try {
                    $weekCode = date('Y') . "-S" . date('W');
                    $stmtMedal = $this->db->prepare("
                        INSERT INTO user_medals (user_id, category, `rank`, week_code, description, awarded_at) 
                        VALUES (?, 'progression', 1, ?, 'Diplôme Impérial du Shogunat : Didacticiel de Daimyō accompli avec honneur (+100 Koban)', NOW())
                    ");
                    $stmtMedal->execute([$userId, $weekCode]);
                    // 🪙 Dotation Impériale : 100 Koban offerts
                    $this->db->prepare("UPDATE users SET gold_coins = gold_coins + 100 WHERE id = ?")->execute([$userId]);
                    $awardedMedal = true;
                } catch (Exception $e) {
                    // Ignorer si déjà existant
                }
            }

            $this->db->commit();

            // Mettre à jour l'état de la planète et renvoyer le nouveau statut
            $updatedPlanet = $this->planetEngine->getPlanet($planetId);
            $newStatus = $this->getPlayerQuestsStatus($userId, $planetId);

            return [
                'success' => true,
                'message' => "Récompense de la quête « {$quest['title']} » réclamée avec succès !",
                'claimed_quest' => $questKey,
                'rewards' => $rewards,
                'rewarded_unit_name' => $rewardedUnitName,
                'bonus_units' => $bonusUnitsCount,
                'awarded_medal' => $awardedMedal,
                'planet' => $updatedPlanet,
                'quests_status' => $newStatus,
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de l\'attribution de la récompense : ' . $e->getMessage()];
        }
    }

    /**
     * Enregistre une action spécifique déclenchée par le joueur (ex: visite de la carte)
     */
    public function recordAction(int $userId, string $actionKey): void {
        $actionKey = trim($actionKey);
        if ($actionKey === 'visit_map') {
            $this->upsertQuestStatus($userId, 'explore_map', 'completed', 100);
        } elseif ($actionKey === 'send_fleet') {
            $this->upsertQuestStatus($userId, 'first_expedition', 'completed', 100);
        }
    }

    /**
     * Évalue si les conditions d'une quête sont satisfaites
     */
    private function evaluateCondition(array $check, int $userId, int $planetId, array $buildings, array $fields, array $user): bool {
        $type = $check['type'] ?? '';

        switch ($type) {
            case 'field_level':
                $targetType = $check['field_type'] ?? '';
                $minLevel = (int)($check['min_level'] ?? 1);
                foreach ($fields as $f) {
                    if ($f['type'] === $targetType && (int)$f['level'] >= $minLevel) {
                        return true;
                    }
                }
                return false;

            case 'building_level':
                $bName = $check['building'] ?? '';
                $minLevel = (int)($check['min_level'] ?? 1);
                return isset($buildings[$bName]) && (int)$buildings[$bName] >= $minLevel;

            case 'building_or_level':
                // Ex: storage >= 2 OU tank >= 2
                $targets = $check['buildings'] ?? [];
                foreach ($targets as $bName => $minLevel) {
                    if (isset($buildings[$bName]) && (int)$buildings[$bName] >= (int)$minLevel) {
                        return true;
                    }
                }
                return false;

            case 'total_units':
                $minCount = (int)($check['min_count'] ?? 20);
                $stmt = $this->db->prepare("SELECT SUM(count) FROM planet_units WHERE planet_id = ?");
                $stmt->execute([$planetId]);
                $totalUnits = (int)$stmt->fetchColumn();
                return ($totalUnits >= $minCount);

            case 'action_recorded':
                $actionKey = $check['action_key'] ?? '';
                if ($actionKey === 'visit_map') {
                    $stmt = $this->db->prepare("SELECT status FROM user_quests WHERE user_id = ? AND quest_key = 'explore_map'");
                    $stmt->execute([$userId]);
                    $st = $stmt->fetchColumn();
                    return in_array($st, ['completed', 'claimed']);
                }
                return false;

            case 'custom_bio':
                $bio = trim($user['bio'] ?? '');
                return !empty($bio) && $bio !== "Ce Daimyō n'a pas encore rédigé sa chronique...";

            case 'fleet_mission_sent':
                // Vérifier s'il y a déjà eu une mission de flotte lancée
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM fleet_missions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $missionsCount = (int)$stmt->fetchColumn();
                if ($missionsCount > 0) {
                    return true;
                }
                // Ou si l'action a été enregistrée
                $stmt2 = $this->db->prepare("SELECT status FROM user_quests WHERE user_id = ? AND quest_key = 'first_expedition'");
                $stmt2->execute([$userId]);
                $st2 = $stmt2->fetchColumn();
                return in_array($st2, ['completed', 'claimed']);

            default:
                return false;
        }
    }

    /**
     * Met à jour ou insère l'état d'une quête dans user_quests
     */
    private function upsertQuestStatus(int $userId, string $questKey, string $status, int $progress = 0, bool $isClaim = false): void {
        if ($isClaim) {
            $stmt = $this->db->prepare("
                INSERT INTO user_quests (user_id, quest_key, status, progress, completed_at, claimed_at) 
                VALUES (?, ?, 'claimed', 100, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE 
                    status = 'claimed', 
                    progress = 100, 
                    claimed_at = NOW()
            ");
            $stmt->execute([$userId, $questKey]);
        } else {
            $completedAtClause = ($status === 'completed') ? 'NOW()' : 'NULL';
            $stmt = $this->db->prepare("
                INSERT INTO user_quests (user_id, quest_key, status, progress, completed_at) 
                VALUES (?, ?, ?, ?, {$completedAtClause}) 
                ON DUPLICATE KEY UPDATE 
                    status = IF(status = 'claimed', 'claimed', VALUES(status)), 
                    progress = VALUES(progress), 
                    completed_at = IF(status = 'claimed', completed_at, VALUES(completed_at))
            ");
            $stmt->execute([$userId, $questKey, $status, $progress]);
        }
    }

    /**
     * Données utilisateur
     */
    private function getUserData(int $userId): array {
        $stmt = $this->db->prepare("SELECT id, username, faction, points, bio FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [];
    }
}

