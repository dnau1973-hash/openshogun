<?php
/**
 * StoryboardEngine - Modèle et Moteur Métier du Narrative Designer (OpenShogun Game Studio)
 * Gestion des storyboards multi-projets, timelines interactives, découpages de plans et prompts IA
 * Fichier : core/StoryboardEngine.php
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class StoryboardEngine {
    private ?PDO $db = null;
    private string $jsonStorageFile;
    private bool $useJsonFallback = false;

    public function __construct(?PDO $db = null) {
        $this->jsonStorageFile = __DIR__ . '/../data/storyboards_storage.json';
        
        // Tentative de connexion DB sécurisée (avec fallback JSON si non disponible)
        try {
            $this->db = $db ?: Database::tryGetConnection();
            if ($this->db) {
                $this->ensureTables();
            } else {
                $this->useJsonFallback = true;
            }
        } catch (Throwable $e) {
            $this->useJsonFallback = true;
        }

        // Si aucun storyboard présent, ensemencer avec les modèles féodaux par défaut
        $this->ensureDefaultStoryboards();
    }

    /**
     * Crée les tables nécessaires si elles n'existent pas
     */
    private function ensureTables(): void {
        if ($this->useJsonFallback || !$this->db) return;

        try {
            $sqlStoryboards = "
            CREATE TABLE IF NOT EXISTS `storyboards` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(150) NOT NULL,
                `description` TEXT NULL,
                `video_type` ENUM('trailer', 'gameplay_demo', 'event_teaser', 'quest_cinematic', 'social_clip') DEFAULT 'trailer',
                `target_duration` INT NOT NULL DEFAULT 20,
                `audio_track_title` VARCHAR(150) NULL,
                `audio_track_url` VARCHAR(255) NULL,
                `aspect_ratio` VARCHAR(10) DEFAULT '16:9',
                `is_active` TINYINT(1) DEFAULT 1,
                `created_by` INT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_storyboard_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";

            $sqlShots = "
            CREATE TABLE IF NOT EXISTS `storyboard_shots` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `storyboard_id` INT NOT NULL,
                `order_index` INT NOT NULL DEFAULT 0,
                `title` VARCHAR(150) NOT NULL,
                `image_url` VARCHAR(255) NOT NULL,
                `narrative_text` TEXT NULL,
                `voiceover_text` TEXT NULL,
                `camera_movement` VARCHAR(80) NULL,
                `sound_fx` VARCHAR(150) NULL,
                `duration` DECIMAL(4,1) NOT NULL DEFAULT 4.0,
                `transition_type` VARCHAR(50) DEFAULT 'cut',
                `ai_prompt` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_storyboard_shots_rel` (`storyboard_id`, `order_index`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";

            $this->db->exec($sqlStoryboards);
            $this->db->exec($sqlShots);
        } catch (Throwable $e) {
            $this->useJsonFallback = true;
        }
    }

    /**
     * Ensemence les storyboards féodaux par défaut si la base ou le stockage est vierge
     */
    private function ensureDefaultStoryboards(): void {
        $existing = $this->getStoryboards();
        if (!empty($existing)) {
            return;
        }

        // 1. Trailer Officiel : Le Marché de la Cité Castrale (17s)
        $sb1 = [
            'title' => 'Trailer Officiel : Le Marché de la Cité Castrale',
            'slug' => 'trailer-marche-cite-castrale',
            'description' => 'Scène vivante de la vie quotidienne dans un bourg féodal Sengoku. Échoppes animées, passage gracieux des geishas, patrouille martiale de samouraïs et donjon Tenshu.',
            'video_type' => 'trailer',
            'target_duration' => 17,
            'audio_track_title' => 'Sengoku Market Murmur & Shakuhachi Dawn',
            'aspect_ratio' => '16:9',
            'shots' => [
                [
                    'order_index' => 0,
                    'title' => 'Plan 1 : Effervescence du Marché & Échoppes',
                    'image_url' => '/public/assets/carrousel/carrousel_01.jpg',
                    'narrative_text' => 'Une ruelle animée bordée d\'auvents en toile brute et de lanternes suspendues. Des paysans et marchands négocient des paniers de riz, des céramiques fumantes et du poisson frais.',
                    'voiceover_text' => 'Au cœur du fief, le bourg s\'éveille. Les récoltes abondent et la prospérité du seigneur nourrit chaque foyer.',
                    'camera_movement' => 'Travelling avant fluide au ras du sol',
                    'sound_fx' => 'Murmures de foule d\'époque, braises qui crépitent, tintement de pièces Mon',
                    'duration' => 4.5,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 1,
                    'title' => 'Plan 2 : L\'Élégance : Déambulation des Geishas',
                    'image_url' => '/public/assets/carrousel/carrousel_08.jpg',
                    'narrative_text' => 'Deux élégantes geishas avancent gracieusement sur des sandales en bois geta. Leurs kimonos en soie brodée de vagues vermillon contrastent avec les tenues rustiques des artisans.',
                    'voiceover_text' => 'Sous les ombrelles de soie, les traditions ancestrales côtoient la rumeur des grands destins.',
                    'camera_movement' => 'Travelling latéral doux accompagnant leur pas',
                    'sound_fx' => 'Claquement sec des geta sur la pierre, bruissement de soie, note pincée de koto',
                    'duration' => 4.5,
                    'transition_type' => 'crossfade',
                ],
                [
                    'order_index' => 2,
                    'title' => 'Plan 3 : L\'Ordre & la Force : Patrouille des Samouraïs',
                    'image_url' => '/public/assets/clans/clan_takeda_cavalry_charge.jpg',
                    'narrative_text' => 'Trois fiers samouraïs en armures laquées noires et rouge écarlate patrouillent calmement. La main posée sur la tsuba de leur katana, la foule s\'écarte avec respect.',
                    'voiceover_text' => 'Mais la paix d\'un domaine repose toujours sur le tranchant de ses lames.',
                    'camera_movement' => 'Plan moyen face aux guerriers en légère contre-plongée',
                    'sound_fx' => 'Bruit métallique des plaques d\'armure, battement sourd et lointain d\'un tambour taiko',
                    'duration' => 5.0,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 3,
                    'title' => 'Plan 4 : Le Siège du Pouvoir : Tenshu & Logo OpenShogun',
                    'image_url' => '/public/assets/buildings/building_tenshu.jpg',
                    'narrative_text' => 'La caméra s\'élève au-dessus des toits du marché pour embrasser le donjon impérial Tenshu sur son promontoire de pierre. Logo OpenShogun avec sceau impérial.',
                    'voiceover_text' => 'OpenShogun. Bâtissez votre fief. Forgez votre légende.',
                    'camera_movement' => 'Élévation majestueuse en grue vers le ciel doré',
                    'sound_fx' => 'Crescendo martial de taiko, souffle de vent, résonance de gong shinto',
                    'duration' => 3.0,
                    'transition_type' => 'fade_black',
                ],
            ]
        ];

        // 2. Teaser Épique : La Grande Forge du Tamahagane (18s)
        $sb2 = [
            'title' => 'Teaser Événement : La Grande Forge du Tamahagane',
            'slug' => 'teaser-forge-tamahagane',
            'description' => 'Focus narratif sur le travail minier des cimes, le fourneau Tatara ancestral et la trempe des armes de prestige au niveau 15 et 20 de la forge.',
            'video_type' => 'event_teaser',
            'target_duration' => 18,
            'audio_track_title' => 'Honneur d\'Acier & Enclume Sacrée',
            'aspect_ratio' => '16:9',
            'shots' => [
                [
                    'order_index' => 0,
                    'title' => 'Plan 1 : Le Mineur des Cimes (Kōfu)',
                    'image_url' => '/public/assets/units/mineur.jpg',
                    'narrative_text' => 'Au sommet des crêtes volcaniques brumeuses, le mineur extrait le sable de fer satetsu et le soufre pur sous une lumière dorée.',
                    'voiceover_text' => 'Dans les hauteurs inhospitalières des cimes, nos mineurs arrachent la matière première des dieux.',
                    'camera_movement' => 'Plan large panoramique sur la montagne puis zoom lent sur le mineur',
                    'sound_fx' => 'Vent des cimes, coup sec de pioche sur la roche volcanique',
                    'duration' => 4.5,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 1,
                    'title' => 'Plan 2 : Le Fourneau Tatara en Fusion',
                    'image_url' => '/public/assets/buildings/building_blacksmith.jpg',
                    'narrative_text' => 'Les braises incandescentes du fourneau Tatara atteignent les mille degrés. Les maîtres forgerons battent le fer à cadence régulière.',
                    'voiceover_text' => 'Charbon de bois et sable noir fusionnent dans le creuset des maîtres du Tamahagane.',
                    'camera_movement' => 'Gros plan dynamique sur les gerbes d\'étincelles et les marteaux',
                    'sound_fx' => 'Souffle puissant du soufflet de forge, marteaux lourds frappant en rythme',
                    'duration' => 4.5,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 2,
                    'title' => 'Plan 3 : La Trempe de la Lame Sacrée',
                    'image_url' => '/public/assets/items/armure_do_maru.jpeg',
                    'narrative_text' => 'La lame rougeoyante est plongée dans l\'eau bénie par les prières shinto. Une vapeur blanche s\'élève dans la pénombre.',
                    'voiceover_text' => 'Une lame digne d\'un daimyō ne tolère aucune impureté.',
                    'camera_movement' => 'Travelling avant vers la vapeur d\'eau qui s\'évapore',
                    'sound_fx' => 'Sifflement intense de l\'acier trempé dans l\'eau froide, clochette shinto',
                    'duration' => 4.5,
                    'transition_type' => 'crossfade',
                ],
                [
                    'order_index' => 3,
                    'title' => 'Plan 4 : La Garde d\'Honneur en Armure Lourde',
                    'image_url' => '/public/assets/units/archer_protecteur_muraille.jpg',
                    'narrative_text' => 'Les gardes d\'élite équipés de leurs armures de prestige et de leurs katanas étincelants s\'alignent sur les remparts.',
                    'voiceover_text' => 'Débloquez la Grande Forge Niveau 20 et menez vos légions à la victoire.',
                    'camera_movement' => 'Plan large en travelling arrière révélant l\'armée prête au combat',
                    'sound_fx' => 'Taiko martial puissant, cri de guerre unanime des samouraïs',
                    'duration' => 4.5,
                    'transition_type' => 'fade_black',
                ],
            ]
        ];

        // 3. Démo Gameplay : L'Arpentage & la Cité Fluviale (20s)
        $sb3 = [
            'title' => 'Démo Gameplay : L\'Arpentage & la Cité Fluviale',
            'slug' => 'demo-arpentage-cite-fluviale',
            'description' => 'Démonstration de l\'expansion du fief, de l\'arpentage des parcelles rurales et du développement castral.',
            'video_type' => 'gameplay_demo',
            'target_duration' => 20,
            'audio_track_title' => 'Harmonie du Terroir',
            'aspect_ratio' => '16:9',
            'shots' => [
                [
                    'order_index' => 0,
                    'title' => 'Plan 1 : Vue d\'Ensemble du Terroir Provincial',
                    'image_url' => '/public/assets/resources/terroir_clay.jpg',
                    'narrative_text' => 'Cartographie vue du ciel des 40 parcelles du domaine : rizières, forêts de cèdre et carrières de granit.',
                    'voiceover_text' => 'Chaque grande forteresse commence par le tracé rigoureux de ses parcelles.',
                    'camera_movement' => 'Descente fluide d\'un plan satellite vers la surface',
                    'sound_fx' => 'Ruisseau qui coule, oiseaux de campagne',
                    'duration' => 5.0,
                    'transition_type' => 'crossfade',
                ],
                [
                    'order_index' => 1,
                    'title' => 'Plan 2 : Le Chantier Naval & la Flotte Fluviale',
                    'image_url' => '/public/assets/buildings/building_shipyard.jpg',
                    'narrative_text' => 'Les charpentiers assemblent les navires de guerre le long des quais fluviaux fortifiés.',
                    'voiceover_text' => 'Contrôlez les voies d\'eau et approvisionnez vos garnisons sans délai.',
                    'camera_movement' => 'Travelling avant longeant la cale de halage',
                    'sound_fx' => 'Bruit de scies à bois, clapotis des vagues sur la coque',
                    'duration' => 5.0,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 2,
                    'title' => 'Plan 3 : La Muraille & les Tours de Vigilance',
                    'image_url' => '/public/assets/buildings/building_wall.jpg',
                    'narrative_text' => 'Les créneaux de pierre et les archers en vigie surveillent l\'horizon brumeux des terres ennemies.',
                    'voiceover_text' => 'Une défense inexpugnable décourage les daimyōs rivaux.',
                    'camera_movement' => 'Panoramique latéral le long du chemin de ronde',
                    'sound_fx' => 'Vent dans les bannières, sifflement d\'une flèche d\'essai',
                    'duration' => 5.0,
                    'transition_type' => 'cut',
                ],
                [
                    'order_index' => 3,
                    'title' => 'Plan 4 : La Cité Royale de Himeji',
                    'image_url' => '/public/assets/castles/himeji.jpg',
                    'narrative_text' => 'La cité castrale achevée, majestueuse et impériale sous un coucher de soleil pourpre.',
                    'voiceover_text' => 'Rejoignez des milliers de daimyōs. Jouez gratuitement à OpenShogun.',
                    'camera_movement' => 'Plan large fixe contemplatif, brume matinale au pied des murs',
                    'sound_fx' => 'Cloche de temple bouddhiste au loin, fondu musical apaisant',
                    'duration' => 5.0,
                    'transition_type' => 'fade_black',
                ],
            ]
        ];

        $this->saveStoryboardFull($sb1);
        $this->saveStoryboardFull($sb2);
        $this->saveStoryboardFull($sb3);
    }

    /**
     * Enregistre un storyboard complet avec ses scènes (compatible DB et JSON)
     */
    private function saveStoryboardFull(array $sb): int {
        if (!$this->useJsonFallback && $this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO storyboards (title, slug, description, video_type, target_duration, audio_track_title, aspect_ratio, created_by)
                    VALUES (:title, :slug, :description, :video_type, :target_duration, :audio_track_title, :aspect_ratio, 1)
                ");
                $stmt->execute([
                    ':title' => $sb['title'],
                    ':slug' => $sb['slug'] ?? $this->slugify($sb['title']),
                    ':description' => $sb['description'] ?? '',
                    ':video_type' => $sb['video_type'] ?? 'trailer',
                    ':target_duration' => (int)($sb['target_duration'] ?? 20),
                    ':audio_track_title' => $sb['audio_track_title'] ?? '',
                    ':aspect_ratio' => $sb['aspect_ratio'] ?? '16:9',
                ]);
                $sbId = (int)$this->db->lastInsertId();

                if (!empty($sb['shots'])) {
                    $stmtShot = $this->db->prepare("
                        INSERT INTO storyboard_shots (storyboard_id, order_index, title, image_url, narrative_text, voiceover_text, camera_movement, sound_fx, duration, transition_type)
                        VALUES (:sb_id, :order_idx, :title, :img, :narrative, :vo, :camera, :sfx, :dur, :trans)
                    ");
                    foreach ($sb['shots'] as $idx => $shot) {
                        $stmtShot->execute([
                            ':sb_id' => $sbId,
                            ':order_idx' => $idx,
                            ':title' => $shot['title'] ?? ('Plan ' . ($idx + 1)),
                            ':img' => $shot['image_url'] ?? '/public/assets/buildings/building_tenshu.jpg',
                            ':narrative' => $shot['narrative_text'] ?? '',
                            ':vo' => $shot['voiceover_text'] ?? '',
                            ':camera' => $shot['camera_movement'] ?? 'Plan large fixe',
                            ':sfx' => $shot['sound_fx'] ?? '',
                            ':dur' => (float)($shot['duration'] ?? 4.0),
                            ':trans' => $shot['transition_type'] ?? 'cut',
                        ]);
                    }
                }
                return $sbId;
            } catch (Throwable $e) {
                $this->useJsonFallback = true;
            }
        }

        // Mode JSON Fallback
        $data = $this->loadJsonData();
        $newId = count($data) > 0 ? (max(array_column($data, 'id')) + 1) : 1;
        $sb['id'] = $newId;
        $sb['slug'] = $sb['slug'] ?? $this->slugify($sb['title']);
        $sb['created_at'] = date('Y-m-d H:i:s');
        $sb['updated_at'] = date('Y-m-d H:i:s');
        $data[] = $sb;
        $this->saveJsonData($data);
        return $newId;
    }

    /**
     * Récupère la liste de tous les storyboards (sommaire avec stats)
     */
    public function getStoryboards(): array {
        if (!$this->useJsonFallback && $this->db) {
            try {
                $stmt = $this->db->query("
                    SELECT s.*, 
                           COUNT(sh.id) as shots_count,
                           COALESCE(SUM(sh.duration), 0) as total_duration
                    FROM storyboards s
                    LEFT JOIN storyboard_shots sh ON s.id = sh.storyboard_id
                    WHERE s.is_active = 1
                    GROUP BY s.id
                    ORDER BY s.updated_at DESC, s.id DESC
                ");
                $rows = $stmt->fetchAll();
                if (!empty($rows)) {
                    foreach ($rows as &$r) {
                        $r['id'] = (int)$r['id'];
                        $r['target_duration'] = (int)$r['target_duration'];
                        $r['shots_count'] = (int)$r['shots_count'];
                        $r['total_duration'] = round((float)$r['total_duration'], 1);
                    }
                    return $rows;
                }
            } catch (Throwable $e) {
                $this->useJsonFallback = true;
            }
        }

        // Repli JSON
        $data = $this->loadJsonData();
        $summary = [];
        foreach ($data as $sb) {
            $totalDur = 0.0;
            $shots = $sb['shots'] ?? [];
            foreach ($shots as $s) {
                $totalDur += (float)($s['duration'] ?? 4.0);
            }
            $summary[] = [
                'id' => (int)$sb['id'],
                'title' => $sb['title'],
                'slug' => $sb['slug'],
                'description' => $sb['description'] ?? '',
                'video_type' => $sb['video_type'] ?? 'trailer',
                'target_duration' => (int)($sb['target_duration'] ?? 20),
                'audio_track_title' => $sb['audio_track_title'] ?? '',
                'aspect_ratio' => $sb['aspect_ratio'] ?? '16:9',
                'shots_count' => count($shots),
                'total_duration' => round($totalDur, 1),
                'updated_at' => $sb['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
        }
        return $summary;
    }

    /**
     * Récupère un storyboard unique avec tous ses plans détaillés et timecodes
     */
    public function getStoryboard(int $id): ?array {
        if ($id <= 0) return null;

        if (!$this->useJsonFallback && $this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM storyboards WHERE id = :id AND is_active = 1 LIMIT 1");
                $stmt->execute([':id' => $id]);
                $sb = $stmt->fetch();
                if (!$sb) return null;

                $stmtShots = $this->db->prepare("
                    SELECT * FROM storyboard_shots 
                    WHERE storyboard_id = :id 
                    ORDER BY order_index ASC, id ASC
                ");
                $stmtShots->execute([':id' => $id]);
                $shots = $stmtShots->fetchAll() ?: [];

                // Calculs des timecodes cumulés (ex: 00:00 - 00:04.5)
                $sb['shots'] = $this->calculateTimecodes($shots);
                $sb['shots_count'] = count($sb['shots']);
                $sb['total_duration'] = round(array_sum(array_column($sb['shots'], 'duration')), 1);
                $sb['metrics'] = $this->calculateNarrativeMetrics($sb);
                return $sb;
            } catch (Throwable $e) {
                $this->useJsonFallback = true;
            }
        }

        // Repli JSON
        $data = $this->loadJsonData();
        foreach ($data as $sb) {
            if ((int)$sb['id'] === $id) {
                $shots = $sb['shots'] ?? [];
                $sb['shots'] = $this->calculateTimecodes($shots);
                $sb['shots_count'] = count($sb['shots']);
                $sb['total_duration'] = round(array_sum(array_column($sb['shots'], 'duration')), 1);
                $sb['metrics'] = $this->calculateNarrativeMetrics($sb);
                return $sb;
            }
        }
        return null;
    }

    /**
     * Crée un nouveau projet de storyboard
     */
    public function createStoryboard(array $data, int $userId = 1): ?array {
        $title = trim((string)($data['title'] ?? 'Nouveau Storyboard'));
        if ($title === '') $title = 'Storyboard Sans Titre';

        $payload = [
            'title' => $title,
            'slug' => $this->slugify($title),
            'description' => trim((string)($data['description'] ?? '')),
            'video_type' => in_array($data['video_type'] ?? '', ['trailer', 'gameplay_demo', 'event_teaser', 'quest_cinematic', 'social_clip'], true) ? $data['video_type'] : 'trailer',
            'target_duration' => max(5, min(300, (int)($data['target_duration'] ?? 20))),
            'audio_track_title' => trim((string)($data['audio_track_title'] ?? '')),
            'aspect_ratio' => in_array($data['aspect_ratio'] ?? '', ['16:9', '9:16', '1:1', '4:3'], true) ? $data['aspect_ratio'] : '16:9',
            'shots' => [
                [
                    'order_index' => 0,
                    'title' => 'Plan 1 : Scène d\'Ouverture',
                    'image_url' => '/public/assets/carrousel/carrousel_01.jpg',
                    'narrative_text' => 'Description de l\'ambiance initiale.',
                    'voiceover_text' => 'Dans les brumes du Japon féodal...',
                    'camera_movement' => 'Plan large d\'ensemble',
                    'sound_fx' => 'Vent dans les cèdres',
                    'duration' => 5.0,
                    'transition_type' => 'cut'
                ]
            ]
        ];

        $newId = $this->saveStoryboardFull($payload);
        return $this->getStoryboard($newId);
    }

    /**
     * Met à jour les métadonnées d'un storyboard
     */
    public function updateStoryboard(int $id, array $data): bool {
        if ($id <= 0) return false;

        $title = trim((string)($data['title'] ?? ''));
        $desc = trim((string)($data['description'] ?? ''));
        $type = $data['video_type'] ?? 'trailer';
        $targetDur = max(5, (int)($data['target_duration'] ?? 20));
        $audioTrack = trim((string)($data['audio_track_title'] ?? ''));
        $aspectRatio = $data['aspect_ratio'] ?? '16:9';

        if (!$this->useJsonFallback && $this->db) {
            try {
                $stmt = $this->db->prepare("
                    UPDATE storyboards 
                    SET title = :title, description = :desc, video_type = :type, 
                        target_duration = :target_dur, audio_track_title = :audio, aspect_ratio = :ar,
                        updated_at = NOW()
                    WHERE id = :id
                ");
                return $stmt->execute([
                    ':id' => $id,
                    ':title' => $title !== '' ? $title : 'Storyboard',
                    ':desc' => $desc,
                    ':type' => $type,
                    ':target_dur' => $targetDur,
                    ':audio' => $audioTrack,
                    ':ar' => $aspectRatio,
                ]);
            } catch (Throwable $e) {
                $this->useJsonFallback = true;
            }
        }

        // Repli JSON
        $all = $this->loadJsonData();
        foreach ($all as &$sb) {
            if ((int)$sb['id'] === $id) {
                if ($title !== '') $sb['title'] = $title;
                $sb['description'] = $desc;
                $sb['video_type'] = $type;
                $sb['target_duration'] = $targetDur;
                $sb['audio_track_title'] = $audioTrack;
                $sb['aspect_ratio'] = $aspectRatio;
                $sb['updated_at'] = date('Y-m-d H:i:s');
                $this->saveJsonData($all);
                return true;
            }
        }
        return false;
    }

    /**
     * Duplique un storyboard et tous ses plans
     */
    public function duplicateStoryboard(int $id, int $userId = 1): ?array {
        $source = $this->getStoryboard($id);
        if (!$source) return null;

        $cloneData = $source;
        unset($cloneData['id']);
        $cloneData['title'] = $source['title'] . ' (Copie)';
        $cloneData['slug'] = $this->slugify($cloneData['title']);

        $newId = $this->saveStoryboardFull($cloneData);
        return $this->getStoryboard($newId);
    }

    /**
     * Supprime un storyboard
     */
    public function deleteStoryboard(int $id): bool {
        if ($id <= 0) return false;

        if (!$this->useJsonFallback && $this->db) {
            try {
                $this->db->prepare("DELETE FROM storyboard_shots WHERE storyboard_id = :id")->execute([':id' => $id]);
                return $this->db->prepare("DELETE FROM storyboards WHERE id = :id")->execute([':id' => $id]);
            } catch (Throwable $e) {
                $this->useJsonFallback = true;
            }
        }

        $all = $this->loadJsonData();
        $filtered = array_values(array_filter($all, fn($sb) => (int)$sb['id'] !== $id));
        $this->saveJsonData($filtered);
        return true;
    }

    /**
     * Sauvegarde en bloc l'ensemble des plans d'un storyboard (après drag & drop ou édition)
     */
    public function saveStoryboardShots(int $storyboardId, array $shots): bool {
        if ($storyboardId <= 0) return false;

        // Assainissement des plans
        $cleanShots = [];
        foreach ($shots as $idx => $s) {
            $duration = max(0.5, min(60.0, (float)($s['duration'] ?? 4.0)));
            $cleanShots[] = [
                'order_index' => $idx,
                'title' => trim((string)($s['title'] ?? ('Plan ' . ($idx + 1)))),
                'image_url' => trim((string)($s['image_url'] ?? '/public/assets/buildings/building_tenshu.jpg')),
                'narrative_text' => trim((string)($s['narrative_text'] ?? '')),
                'voiceover_text' => trim((string)($s['voiceover_text'] ?? '')),
                'camera_movement' => trim((string)($s['camera_movement'] ?? 'Plan large')),
                'sound_fx' => trim((string)($s['sound_fx'] ?? '')),
                'duration' => $duration,
                'transition_type' => in_array($s['transition_type'] ?? '', ['cut', 'fade_black', 'crossfade', 'wipe'], true) ? $s['transition_type'] : 'cut',
            ];
        }

        if (!$this->useJsonFallback && $this->db) {
            try {
                $this->db->beginTransaction();
                $del = $this->db->prepare("DELETE FROM storyboard_shots WHERE storyboard_id = :id");
                $del->execute([':id' => $storyboardId]);

                $ins = $this->db->prepare("
                    INSERT INTO storyboard_shots (storyboard_id, order_index, title, image_url, narrative_text, voiceover_text, camera_movement, sound_fx, duration, transition_type)
                    VALUES (:sb_id, :order_idx, :title, :img, :narrative, :vo, :camera, :sfx, :dur, :trans)
                ");

                foreach ($cleanShots as $cs) {
                    $ins->execute([
                        ':sb_id' => $storyboardId,
                        ':order_idx' => $cs['order_index'],
                        ':title' => $cs['title'],
                        ':img' => $cs['image_url'],
                        ':narrative' => $cs['narrative_text'],
                        ':vo' => $cs['voiceover_text'],
                        ':camera' => $cs['camera_movement'],
                        ':sfx' => $cs['sound_fx'],
                        ':dur' => $cs['duration'],
                        ':trans' => $cs['transition_type'],
                    ]);
                }

                $this->db->prepare("UPDATE storyboards SET updated_at = NOW() WHERE id = :id")->execute([':id' => $storyboardId]);
                $this->db->commit();
                return true;
            } catch (Throwable $e) {
                if ($this->db && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $this->useJsonFallback = true;
            }
        }

        // Repli JSON
        $all = $this->loadJsonData();
        foreach ($all as &$sb) {
            if ((int)$sb['id'] === $storyboardId) {
                $sb['shots'] = $cleanShots;
                $sb['updated_at'] = date('Y-m-d H:i:s');
                $this->saveJsonData($all);
                return true;
            }
        }
        return false;
    }

    /**
     * Calcule les timecodes cumulés pour chaque plan (début, fin, label lisible)
     */
    private function calculateTimecodes(array $shots): array {
        $currentTime = 0.0;
        foreach ($shots as &$shot) {
            $dur = (float)($shot['duration'] ?? 4.0);
            $startTime = $currentTime;
            $endTime = $currentTime + $dur;

            $shot['start_time'] = round($startTime, 1);
            $shot['end_time'] = round($endTime, 1);
            $shot['duration'] = round($dur, 1);
            $shot['timecode_label'] = sprintf("%s &rarr; %s", $this->formatTimecode($startTime), $this->formatTimecode($endTime));
            $shot['duration_label'] = sprintf("%.1fs", $dur);

            $currentTime = $endTime;
        }
        return $shots;
    }

    /**
     * Formate des secondes en timecode mm:ss.s
     */
    public function formatTimecode(float $seconds): string {
        $min = (int)floor($seconds / 60);
        $sec = fmod($seconds, 60);
        return sprintf("%02d:%04.1f", $min, $sec);
    }

    /**
     * Calcule les indicateurs narratifs et le rythme du storyboard
     */
    public function calculateNarrativeMetrics(array $storyboard): array {
        $shots = $storyboard['shots'] ?? [];
        $totalDur = 0.0;
        $totalWords = 0;

        foreach ($shots as $s) {
            $totalDur += (float)($s['duration'] ?? 0);
            $voText = trim((string)($s['voiceover_text'] ?? ''));
            if ($voText !== '') {
                $totalWords += str_word_count($voText);
            }
        }

        $targetDur = (int)($storyboard['target_duration'] ?? 20);
        $diffDur = $totalDur - $targetDur;

        // Débit estimé de lecture voix off (Mots par minute)
        $wpm = ($totalDur > 0) ? round(($totalWords / ($totalDur / 60))) : 0;
        $wpmStatus = 'optimal';
        if ($wpm > 155) $wpmStatus = 'too_fast';
        elseif ($wpm > 0 && $wpm < 100) $wpmStatus = 'too_slow';
        elseif ($wpm === 0) $wpmStatus = 'empty';

        return [
            'total_duration' => round($totalDur, 1),
            'target_duration' => $targetDur,
            'duration_diff' => round($diffDur, 1),
            'total_shots' => count($shots),
            'average_shot_duration' => count($shots) > 0 ? round($totalDur / count($shots), 1) : 0,
            'total_words' => $totalWords,
            'wpm' => (int)$wpm,
            'wpm_status' => $wpmStatus,
        ];
    }

    /**
     * Génère les prompts IA vidéo complets (Master prompt + plan par plan pour Runway / Sora / Kling)
     */
    public function generateAiPrompts(int $storyboardId): array {
        $sb = $this->getStoryboard($storyboardId);
        if (!$sb) return [];

        $shots = $sb['shots'] ?? [];
        $shotPrompts = [];
        $sceneDescriptions = [];

        foreach ($shots as $idx => $shot) {
            $title = $shot['title'];
            $camera = $shot['camera_movement'] ?: 'cinematic camera';
            $narrative = $shot['narrative_text'] ?: $shot['voiceover_text'];

            $shotPrompt = sprintf(
                "Cinematic shot %d: %s. Action: %s. Camera: %s. Period: authentic medieval Sengoku Japan feudal era. Lighting: warm dramatic golden hour, atmospheric dust and volumetric smoke. Total War Shogun / Ghost of Tsushima art style, photorealistic 8K, 35mm film lens, 24fps.",
                ($idx + 1),
                $title,
                $narrative,
                $camera
            );

            $shotPrompts[] = [
                'index' => $idx + 1,
                'title' => $title,
                'prompt' => $shotPrompt,
                'camera' => $camera,
                'duration' => $shot['duration'],
            ];

            $sceneDescriptions[] = sprintf("Plan %d: %s (%s)", $idx + 1, $title, $camera);
        }

        $masterPrompt = sprintf(
            "Cinematic 4K trailer for feudal Japanese strategy game OpenShogun. Narrative arc: %s. Authentic Sengoku era, samurai warriors, vibrant marketplace, majestic castles and battlefields. Atmospheric lighting, rich historical textures, ultra-detailed 8K cinematography.",
            implode("; ", array_slice(array_column($shots, 'narrative_text'), 0, 4))
        );

        return [
            'storyboard_title' => $sb['title'],
            'master_prompt' => $masterPrompt,
            'shots' => $shotPrompts,
        ];
    }

    /**
     * Exporte le script de tournage en Markdown propre
     */
    public function exportMarkdown(int $storyboardId): string {
        $sb = $this->getStoryboard($storyboardId);
        if (!$sb) return '';

        $md = "# 🎬 Scénario & Storyboard : " . $sb['title'] . "\n\n";
        $md .= "**Type de vidéo :** " . ucfirst($sb['video_type']) . "  \n";
        $md .= "**Durée totale :** " . $sb['total_duration'] . "s (Cible : " . $sb['target_duration'] . "s)  \n";
        $md .= "**Nombre de plans :** " . $sb['shots_count'] . "  \n";
        $md .= "**Piste audio suggérée :** " . ($sb['audio_track_title'] ?: 'Ambiance Féodale & Taiko') . "\n\n";
        $md .= "---\n\n";
        $md .= "### 📋 Découpage Plan par Plan\n\n";
        $md .= "| Plan | Timecode | Durée | Mouvement Caméra | Voix Off & Narration | Ambiance Sonore |\n";
        $md .= "|:---:|:---:|:---:|:---|:---|:---|\n";

        foreach ($sb['shots'] as $s) {
            $md .= sprintf(
                "| **%s** | `%s` | `%s` | %s | %s | %s |\n",
                str_replace('|', '-', $s['title']),
                $s['timecode_label'],
                $s['duration_label'],
                str_replace('|', '-', $s['camera_movement'] ?: '-'),
                str_replace('|', '-', $s['voiceover_text'] ?: ($s['narrative_text'] ?: '-')),
                str_replace('|', '-', $s['sound_fx'] ?: '-')
            );
        }

        $md .= "\n---\n*Généré par OpenShogun Game Studio &bull; Narrative Designer*\n";
        return $md;
    }

    /**
     * Fournit le catalogue complet des 238 illustrations du jeu par catégorie pour le sélecteur modal
     */
    public function getGalleryAssets(): array {
        $baseDir = __DIR__ . '/../public/assets';
        $categories = [
            'buildings' => 'Bâtiments Castraux',
            'carrousel' => 'Panoramas & Carrousel',
            'castles' => 'Châteaux & Forteresses',
            'clans' => 'Clans & Armoiries',
            'events' => 'Événements Féodaux',
            'items' => 'Reliques & Objets',
            'resources' => 'Ressources & Terroirs',
            'tiles' => 'Tuiles Isométriques',
            'units' => 'Armées & Unités',
            'bibliotheque' => 'Bibliothèque & Imports',
        ];

        $assets = [];
        foreach ($categories as $catKey => $catLabel) {
            $dirPath = $baseDir . '/' . $catKey;
            if (!is_dir($dirPath)) continue;

            $files = glob($dirPath . '/*.{jpg,jpeg,png,webp,svg}', GLOB_BRACE) ?: [];
            sort($files, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($files as $f) {
                $filename = basename($f);
                $title = ucwords(str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
                $assets[] = [
                    'category' => $catKey,
                    'category_label' => $catLabel,
                    'filename' => $filename,
                    'title' => $title,
                    'path' => '/public/assets/' . $catKey . '/' . $filename,
                ];
            }
        }
        return $assets;
    }

    // --- Utilitaires de stockage JSON Fallback ---
    private function loadJsonData(): array {
        if (!file_exists($this->jsonStorageFile)) {
            $dir = dirname($this->jsonStorageFile);
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            file_put_contents($this->jsonStorageFile, json_encode([]));
            return [];
        }
        $raw = file_get_contents($this->jsonStorageFile);
        return json_decode($raw ?: '[]', true) ?: [];
    }

    private function saveJsonData(array $data): void {
        $dir = dirname($this->jsonStorageFile);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($this->jsonStorageFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'storyboard-' . time() : $text;
    }
}
