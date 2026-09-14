<?php
/**
 * Moteur du Tableau d'Honneur, Remise de Médailles et Profils Joueurs (Style Travian)
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MessageEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

class HonorEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère le Top 10 pour une catégorie donnée de la semaine
     * Catégories : 'progression', 'attack', 'defense', 'raid'
     */
    public function getWeeklyTop(string $category, int $limit = 10): array {
        $orderClause = match ($category) {
            'progression' => "GREATEST(0, (CAST(u.points AS SIGNED) - CAST(COALESCE(s.start_week_points, u.points) AS SIGNED))) DESC, u.points DESC",
            'attack' => "COALESCE(s.attack_points, 0) DESC, u.points DESC",
            'defense' => "COALESCE(s.defense_points, 0) DESC, u.points DESC",
            'raid' => "COALESCE(s.raid_resources, 0) DESC, u.points DESC",
            default => "u.points DESC"
        };

        $sql = "
            SELECT u.id as user_id, u.username, u.faction, u.points,
                   COALESCE(s.attack_points, 0) as attack_points,
                   COALESCE(s.defense_points, 0) as defense_points,
                   COALESCE(s.raid_resources, 0) as raid_resources,
                   COALESCE(s.start_week_points, u.points) as start_week_points,
                   GREATEST(0, (CAST(u.points AS SIGNED) - CAST(COALESCE(s.start_week_points, u.points) AS SIGNED))) as progression_points,
                   a.tag as alliance_tag
            FROM users u
            LEFT JOIN user_weekly_stats s ON s.user_id = u.id
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE u.is_bot = 0 OR u.is_bot = 1
            ORDER BY {$orderClause}
            LIMIT " . (int)$limit;

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        // Récupérer les médailles pour chaque joueur
        foreach ($rows as &$r) {
            $r['score'] = match ($category) {
                'progression' => (int)$r['progression_points'],
                'attack' => (int)$r['attack_points'],
                'defense' => (int)$r['defense_points'],
                'raid' => (int)$r['raid_resources'],
                default => (int)$r['points']
            };
        }

        return $rows;
    }

    /**
     * Récupère le Tableau d'Honneur complet (les 4 classements)
     */
    public function getFullHonorRoll(int $limit = 10): array {
        return [
            'progression' => $this->getWeeklyTop('progression', $limit),
            'attack' => $this->getWeeklyTop('attack', $limit),
            'defense' => $this->getWeeklyTop('defense', $limit),
            'raid' => $this->getWeeklyTop('raid', $limit),
            'week_code' => date('Y') . "-S" . date('W')
        ];
    }

    /**
     * Distribue les médailles de la semaine aux commandants du Top 10
     */
    public function awardWeeklyMedals(?string $weekCode = null): array {
        if ($weekCode === null) {
            $weekCode = date('Y') . "-S" . date('W');
        }

        $categories = [
            'progression' => "Essor du Clan",
            'attack' => "Conquérant de la Semaine",
            'defense' => "Gardien Inébranlable",
            'raid' => "Grand Pillard de Riz"
        ];

        $awarded = [];
        $stmtInsertMedal = $this->db->prepare("
            INSERT INTO user_medals (user_id, category, rank, week_code, description, awarded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $messageEngine = new MessageEngine();

        foreach ($categories as $catKey => $catLabel) {
            $topList = $this->getWeeklyTop($catKey, 10);
            $rank = 1;

            foreach ($topList as $player) {
                // Ne décerner que si score > 0 (sauf pour progression où score peut être >= 0)
                if ($catKey !== 'progression' && $player['score'] <= 0) {
                    continue;
                }

                $medalLabel = match ($rank) {
                    1 => "🥇 Médaille d'Or",
                    2 => "🥈 Médaille d'Argent",
                    3 => "🥉 Médaille de Bronze",
                    default => "🎖️ Ruban d'Honneur (Top {$rank})"
                };

                $desc = "{$medalLabel} : {$catLabel} ({$weekCode}) avec un score de " . number_format($player['score']);

                $stmtInsertMedal->execute([
                    $player['user_id'],
                    $catKey,
                    $rank,
                    $weekCode,
                    $desc
                ]);

                $awarded[] = [
                    'user_id' => $player['user_id'],
                    'username' => $player['username'],
                    'category' => $catLabel,
                    'rank' => $rank,
                    'medal' => $medalLabel
                ];

                // Message de félicitations pour les 3 premiers
                if ($rank <= 3) {
                    $messageEngine->sendMessage(
                        null,
                        (int)$player['user_id'],
                        "🎖️ Décoration Impériale : {$medalLabel} décernée !",
                        "Salutations Daimyō {$player['username']},\n\nLe Shogunat et la Cour Impériale ont l'immense honneur de vous décerner la {$medalLabel} pour vos exploits martiaux en tant que {$catLabel} pour la période {$weekCode} !\n\nCette distinction orne désormais votre Fiche de Daimyō et le Panthéon des clans du Japon.\n\nGloire à votre clan !"
                    );
                }

                $rank++;
            }
        }

        // Réinitialiser les compteurs de la semaine
        $this->db->exec("
            UPDATE user_weekly_stats s 
            JOIN users u ON s.user_id = u.id 
            SET s.attack_points = 0, 
                s.defense_points = 0, 
                s.raid_resources = 0, 
                s.start_week_points = u.points,
                s.updated_at = NOW()
        ");

        // Insérer les joueurs qui n'étaient pas dans weekly_stats
        $this->db->exec("
            INSERT INTO user_weekly_stats (user_id, attack_points, defense_points, raid_resources, start_week_points)
            SELECT id, 0, 0, 0, points FROM users
            ON DUPLICATE KEY UPDATE start_week_points = start_week_points
        ");

        return [
            'success' => true,
            'week_code' => $weekCode,
            'medals_awarded_count' => count($awarded),
            'details' => $awarded
        ];
    }

    /**
     * Récupère la fiche joueur détaillée complète
     */
    public function getUserProfile(int $userId): ?array {
        // 1. Informations de base
        $stmtUser = $this->db->prepare("
            SELECT u.id, u.username, u.email, u.faction, u.points, u.bio, u.is_admin, u.is_bot, u.created_at, u.last_active,
                   a.name as alliance_name, a.tag as alliance_tag
            FROM users u
            LEFT JOIN alliances a ON u.alliance_id = a.id
            WHERE u.id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        if (!$user) return null;

        // 2. Rang général au classement
        $stmtRank = $this->db->prepare("
            SELECT COUNT(*) + 1 as rank_pos FROM users WHERE points > ?
        ");
        $stmtRank->execute([$user['points']]);
        $rankPos = (int)$stmtRank->fetchColumn();

        // 3. Colonies du joueur
        $stmtPlanets = $this->db->prepare("
            SELECT id, name, coord_x, coord_y, planet_type, is_capital 
            FROM planets 
            WHERE user_id = ? 
            ORDER BY is_capital DESC, id ASC
        ");
        $stmtPlanets->execute([$userId]);
        $planets = $stmtPlanets->fetchAll();

        // 4. Vitrine des Médailles d'Honneur (Style Travian)
        $stmtMedals = $this->db->prepare("
            SELECT * FROM user_medals 
            WHERE user_id = ? 
            ORDER BY rank ASC, awarded_at DESC
        ");
        $stmtMedals->execute([$userId]);
        $rawMedals = $stmtMedals->fetchAll();

        $medals = [];
        foreach ($rawMedals as $m) {
            $catLabel = match ($m['category']) {
                'progression' => "Essor du Clan",
                'attack' => "Conquêtes",
                'defense' => "Défense Héroïque",
                'raid' => "Pillage de Riz",
                default => "Honneur"
            };

            $icon = match ((int)$m['rank']) {
                1 => "🥇",
                2 => "🥈",
                3 => "🥉",
                default => "🎖️"
            };

            $color = match ((int)$m['rank']) {
                1 => "#facc15",
                2 => "#cbd5e1",
                3 => "#d97706",
                default => "#818cf8"
            };

            $medals[] = [
                'id' => $m['id'],
                'category' => $m['category'],
                'category_label' => $catLabel,
                'rank' => (int)$m['rank'],
                'icon' => $icon,
                'color' => $color,
                'week_code' => $m['week_code'],
                'description' => $m['description'],
                'awarded_at' => $m['awarded_at']
            ];
        }

        // 5. Statistiques de combat de la semaine
        $stmtStats = $this->db->prepare("SELECT * FROM user_weekly_stats WHERE user_id = ?");
        $stmtStats->execute([$userId]);
        $stats = $stmtStats->fetch() ?: [
            'attack_points' => 0,
            'defense_points' => 0,
            'raid_resources' => 0,
            'start_week_points' => $user['points']
        ];
        $progressionPoints = max(0, (int)$user['points'] - (int)$stats['start_week_points']);
        $factionInfo = FACTIONS[$user['faction']] ?? ['name' => ucfirst($user['faction']), 'icon' => '🏯'];
        $totalPlayers = (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        return [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'faction' => $user['faction'],
            'faction_name' => $factionInfo['name'],
            'faction_icon' => $factionInfo['icon'],
            'points' => (int)$user['points'],
            'rank_pos' => $rankPos,
            'rank_position' => $rankPos,
            'total_players' => $totalPlayers,
            'alliance_name' => $user['alliance_name'],
            'alliance_tag' => $user['alliance_tag'],
            'bio' => $user['bio'] ?: "Fier Daimyō au service de l'honneur de son clan et de l'Empereur.",
            'is_admin' => (int)$user['is_admin'] === 1,
            'is_bot' => (int)$user['is_bot'] === 1,
            'created_at' => $user['created_at'],
            'last_active' => $user['last_active'],
            'is_online' => (time() - strtotime($user['last_active'])) < 300,
            'planets' => $planets,
            'colonies' => $planets,
            'medals' => $medals,
            'medals_count' => count($medals),
            'total_medals' => count($medals),
            'weekly_stats' => [
                'progression' => $progressionPoints,
                'attack_points' => (int)$stats['attack_points'],
                'defense_points' => (int)$stats['defense_points'],
                'raid_resources' => (int)$stats['raid_resources']
            ]
        ];
    }

    /**
     * Met à jour les statistiques de combat et de pillage après une bataille
     */
    public function updateCombatStats(int $attackerId, ?int $defenderId, int $attDmg, int $defDmg, int $lootTotal): void {
        // Mettre à jour l'attaquant
        $this->db->prepare("
            INSERT INTO user_weekly_stats (user_id, attack_points, raid_resources, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                attack_points = attack_points + VALUES(attack_points),
                raid_resources = raid_resources + VALUES(raid_resources),
                updated_at = NOW()
        ")->execute([$attackerId, $attDmg, $lootTotal]);

        // Mettre à jour le défenseur si joueur réel ou bot
        if ($defenderId && $defenderId > 0) {
            $this->db->prepare("
                INSERT INTO user_weekly_stats (user_id, defense_points, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    defense_points = defense_points + VALUES(defense_points),
                    updated_at = NOW()
            ")->execute([$defenderId, $defDmg]);
        }
    }

    /**
     * Met à jour la bio / manifeste d'un joueur
     */
    public function updateBio(int $userId, string $bio): bool {
        $bio = trim(strip_tags($bio));
        if (mb_strlen($bio) > 1000) {
            $bio = mb_substr($bio, 0, 1000);
        }
        $stmt = $this->db->prepare("UPDATE users SET bio = ? WHERE id = ?");
        return $stmt->execute([$bio, $userId]);
    }
}
