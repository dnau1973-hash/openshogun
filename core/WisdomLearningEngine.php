<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SocialCasteEngine.php';

/**
 * Moteur du Mode Apprentissage & Maître de la Sagesse (Chie no Sensei)
 * Évalue la progression sociétale du joueur, ses pénuries et abondances de ressources,
 * et délivre des enseignements précis et non-intrusifs à des moments clés.
 */
class WisdomLearningEngine {
    private static bool $schemaChecked = false;

    /**
     * Garantit la présence de la table d'historisation des jalons de sagesse
     */
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            $db = Database::getConnection();
            $db->exec("
                CREATE TABLE IF NOT EXISTS `user_wisdom_milestones` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `milestone_key` VARCHAR(64) NOT NULL,
                    `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_uwm_user_milestone` (`user_id`, `milestone_key`),
                    CONSTRAINT `fk_uwm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {
            // Ignorer silencieusement si table déjà créée
        }
    }

    /**
     * Vérifie si un jalon a déjà été consulté par l'utilisateur
     */
    public static function hasSeenMilestone(int $userId, string $milestoneKey): bool {
        self::ensureSchema();
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_wisdom_milestones WHERE user_id = ? AND milestone_key = ?");
            $stmt->execute([$userId, $milestoneKey]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Marque un jalon comme lu/acquitté pour l'utilisateur
     */
    public static function markMilestoneSeen(int $userId, string $milestoneKey): bool {
        self::ensureSchema();
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO user_wisdom_milestones (user_id, milestone_key, viewed_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE viewed_at = NOW()
            ");
            return $stmt->execute([$userId, $milestoneKey]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Réinitialise un jalon pour permettre son réaffichage
     */
    public static function resetMilestone(int $userId, string $milestoneKey): bool {
        self::ensureSchema();
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM user_wisdom_milestones WHERE user_id = ? AND milestone_key = ?");
            return $stmt->execute([$userId, $milestoneKey]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Évalue l'état actuel de la société du joueur et détermine le conseil prioritaire du Maître
     * @param array $planet Données de la planète/fief actif
     * @param array $user Données du joueur
     * @param bool $ignoreSeen Si true, retourne le conseil même s'il a déjà été vu (pour ouverture manuelle)
     * @return array|null Le conseil à afficher ou null si aucune intervention requise
     */
    public static function evaluateWisdom(array $planet, array $user, bool $ignoreSeen = false): ?array {
        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) return null;

        // Si le mode apprentissage est désactivé par le joueur dans son profil
        $learningMode = (int)($user['learning_mode'] ?? 1);
        if ($learningMode === 0 && !$ignoreSeen) {
            return null;
        }

        // Calcul ou récupération de l'état social
        $social = $planet['social_castes'] ?? null;
        if (!$social) {
            $social = SocialCasteEngine::processSocialTick($planet, 0.0);
        }

        $civLevel   = (int)($social['civilization_level'] ?? 1);
        $curPop     = (int)($planet['population'] ?? 100);
        $maxPop     = max(1, (int)($planet['population_max'] ?? 100));
        
        $stockRice  = (float)($planet['deuterium'] ?? 0.0); // Riz brut
        $stockFish  = (float)($planet['fish'] ?? 0.0);
        $stockSoy   = (float)($planet['soybean'] ?? 0.0);
        $stockFlour = (float)($planet['rice_flour'] ?? 0.0);
        $stockTea   = (float)($planet['tea'] ?? 0.0);
        $stockSake  = (float)($planet['sake'] ?? 0.0);

        // --- 1. JALON 0 : ACCUEIL & ÉVEIL DU FIEF (Si jamais vu) ---
        $mKeyIntro = 'wisdom_intro_shinokosho';
        if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyIntro)) {
            if (!$ignoreSeen && self::hasSeenMilestone($userId, $mKeyIntro)) {
                // Ne pas bloquer, continuer vers d'autres jalons
            } else if (!self::hasSeenMilestone($userId, $mKeyIntro)) {
                return [
                    'key' => $mKeyIntro,
                    'title' => 'L\'Éveil de votre Société (Shinōkōshō)',
                    'subtitle' => 'Enseignement fondamental du Maître de la Sagesse',
                    'quote' => '« Un arbre puise sa force dans ses racines comme un empire repose sur le bien-être de son peuple. »',
                    'text' => "Salutations, noble Daimyō. Votre domaine compte actuellement <strong>{$curPop} villageois</strong>. Dans le Japon féodal, la société est divisée en strates sacrées (*Shinōkōshō*). Les villageois se contentent d'abord de riz pour subsister sous la forme de paysans (Nōmin). Mais plus vous comblerez leurs besoins en mets raffinés (poisson, soja, farine de riz, thé, saké), plus ils s'élèveront vers des castes supérieures et débloqueront les plus grands savoirs de l'Académie !",
                    'caste_target' => 1,
                    'status_type' => 'intro',
                    'badge' => 'Éveil Féodal',
                    'badge_color' => 'success',
                    'requirements' => [
                        ['name' => 'Riz de subsistance', 'icon' => 'fa-wheat-awn', 'color' => '#16a34a', 'status' => ($stockRice > 50 ? 'fulfilled' : 'missing'), 'desc' => 'Produit par vos rizières irriguées du terroir']
                    ],
                    'next_action_url' => '?page=resources',
                    'next_action_label' => 'Inspecter les Rizières du Terroir',
                    'can_dismiss' => true
                ];
            }
        }

        // --- 2. ALERTE CRISE : PÉNURIE DE RIZ (Famine menaçante pour les Nōmin) ---
        if ($stockRice < 20.0 && $curPop > 20) {
            $mKeyFamine = 'wisdom_alert_rice_famine';
            if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyFamine)) {
                return [
                    'key' => $mKeyFamine,
                    'title' => 'Péril au Fief : Les Greniers de Riz s\'Épuisent !',
                    'subtitle' => 'Alerte vitale pour la subsistance des Paysans',
                    'quote' => '« Sans grain dans l\'écuelle, le bras du paysan faiblit et la faux reste au sol. »',
                    'text' => "Seigneur, vos réserves de riz sont tombées à <strong>" . number_format($stockRice, 1) . " boisseaux</strong> ! Les paysans et ouvriers (*Nōmin*) dépendent directement de cette denrée vitale. Si le riz vient à manquer totalement, le moral de votre population s'effondrera et vos bâtisseurs cesseront leurs labeurs.",
                    'caste_target' => 1,
                    'status_type' => 'warning',
                    'badge' => 'Péril Vital',
                    'badge_color' => 'danger',
                    'requirements' => [
                        ['name' => 'Riz (Grain de subsistance)', 'icon' => 'fa-wheat-awn', 'color' => '#dc2626', 'status' => 'missing', 'desc' => 'Développez vos parcelles de rizières ou réduisez les dépenses']
                    ],
                    'next_action_url' => '?page=resources',
                    'next_action_label' => 'Étendre les Rizières',
                    'can_dismiss' => true
                ];
            }
        }

        // --- 3. PALIER 1 -> PALIER 2 : Émergence des Artisans & Bâtisseurs (Kōgiyō) ---
        // Nécessite Poisson frais + Soja
        if ($civLevel === 1) {
            $hasFish = ($stockFish >= 10.0);
            $hasSoy  = ($stockSoy >= 10.0);

            if (!$hasFish || !$hasSoy) {
                $mKeyP2Missing = 'wisdom_step_tier2_needs';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP2Missing)) {
                    $missingItems = [];
                    if (!$hasFish) $missingItems[] = "du poisson d'eau douce (via vos Pêcheries sur les lacs)";
                    if (!$hasSoy)  $missingItems[] = "du soja récolté (sur vos parcelles de légumineuses du terroir)";

                    return [
                        'key' => $mKeyP2Missing,
                        'title' => 'Éveiller la Caste des Artisans (Kōgiyō)',
                        'subtitle' => 'Palier II : Maîtres charpentiers & tailleurs de pierre',
                        'quote' => '« Le paysan nourrit le corps, mais l\'artisan façonne la grandeur du château. »',
                        'text' => "Pour que vos simples laboureurs se spécialisent en artisans qualifiés (*Kōgiyō*), leur table doit être enrichie. Il leur manque actuellement : " . implode(' et ', $missingItems) . ". Dès que ces denrées abonderont, vos bâtisseurs réduiront les délais d'édification des bâtiments et éveilleront la forge !",
                        'caste_target' => 2,
                        'status_type' => 'progression',
                        'badge' => 'Objectif Palier II',
                        'badge_color' => 'info',
                        'requirements' => [
                            ['name' => 'Poisson frais', 'icon' => 'fa-fish-fins', 'color' => '#0284c7', 'status' => ($hasFish ? 'fulfilled' : 'missing'), 'desc' => 'Pêcheries lacustres ou marché fluvial'],
                            ['name' => 'Cultures de Soja', 'icon' => 'fa-seedling', 'color' => '#16a34a', 'status' => ($hasSoy ? 'fulfilled' : 'missing'), 'desc' => 'Parcelles de soja du terroir rural']
                        ],
                        'next_action_url' => '?page=resources',
                        'next_action_label' => 'Aménager Pêcheries & Soja',
                        'can_dismiss' => true
                    ];
                }
            } else {
                // Les besoins sont comblés !
                $mKeyP2Ready = 'wisdom_tier2_celebration';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP2Ready)) {
                    return [
                        'key' => $mKeyP2Ready,
                        'title' => 'Félicitations ! Vos premiers Artisans s\'installent',
                        'subtitle' => 'Palier II atteint : Naissance de la guilde Kōgiyō',
                        'quote' => '« Le bruit du maillet et de la varlope résonne désormais dans les venelles du bourg. »',
                        'text' => "Le poisson et le soja abondent dans vos greniers ! Vos villageois ont évolué en artisans réputés (*Kōgiyō*). Vous bénéficiez désormais d'une vitesse accrue pour ériger vos bâtisses. Pour franchir le pas suivant vers la prospérité, préparez l'arrivée des Marchands (*Shōnin*) en meulant votre riz en farine fine et en cultivant le thé vert !",
                        'caste_target' => 2,
                        'status_type' => 'success',
                        'badge' => 'Succès : Palier II Débloqué',
                        'badge_color' => 'success',
                        'requirements' => [
                            ['name' => 'Poisson frais', 'icon' => 'fa-fish-fins', 'color' => '#0284c7', 'status' => 'fulfilled', 'desc' => number_format($stockFish, 1) . ' en stock'],
                            ['name' => 'Soja', 'icon' => 'fa-seedling', 'color' => '#16a34a', 'status' => 'fulfilled', 'desc' => number_format($stockSoy, 1) . ' en stock']
                        ],
                        'next_action_url' => '?page=city',
                        'next_action_label' => 'Visiter le Quartier Artisanal',
                        'can_dismiss' => true
                    ];
                }
            }
        }

        // --- 4. PALIER 2 -> PALIER 3 : Les Marchands & Caravaniers (Shōnin) ---
        // Nécessite Farine de riz + Thé vert
        if ($civLevel === 2) {
            $hasFlour = ($stockFlour >= 10.0);
            $hasTea   = ($stockTea >= 10.0);

            if (!$hasFlour || !$hasTea) {
                $mKeyP3Missing = 'wisdom_step_tier3_needs';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP3Missing)) {
                    $missingItems = [];
                    if (!$hasFlour) $missingItems[] = "de la farine de riz raffinée (produite au Moulin à eau ou atelier artisanal)";
                    if (!$hasTea)   $missingItems[] = "du thé vert d'Uji (cueilli sur les coteaux de théiers)";

                    return [
                        'key' => $mKeyP3Missing,
                        'title' => 'Attirer les Marchands & Négociants (Shōnin)',
                        'subtitle' => 'Palier III : Circulation de l\'or et routes commerciales',
                        'quote' => '« Lorsque le marchand déplie ses étoffes et compte ses sapèques, la province rayonne au-delà des monts. »',
                        'text' => "Votre fief a besoin d'une bourgeoisie commerçante (*Shōnin*) pour dynamiser ses échanges. Pour les séduire, offrez-leur : " . implode(' et ', $missingItems) . ". Leur présence multipliera vos recettes fiscales et financera vos expéditions lointaines !",
                        'caste_target' => 3,
                        'status_type' => 'progression',
                        'badge' => 'Objectif Palier III',
                        'badge_color' => 'warning',
                        'requirements' => [
                            ['name' => 'Farine de riz', 'icon' => 'fa-wheat-awn-circle-exclamation', 'color' => '#d97706', 'status' => ($hasFlour ? 'fulfilled' : 'missing'), 'desc' => 'Moulins et ateliers de mouture'],
                            ['name' => 'Thé vert d\'Uji', 'icon' => 'fa-leaf', 'color' => '#16a34a', 'status' => ($hasTea ? 'fulfilled' : 'missing'), 'desc' => 'Plantations sur les coteaux verdoyants']
                        ],
                        'next_action_url' => '?page=resources',
                        'next_action_label' => 'Développer Farine & Thé',
                        'can_dismiss' => true
                    ];
                }
            } else {
                $mKeyP3Ready = 'wisdom_tier3_celebration';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP3Ready)) {
                    return [
                        'key' => $mKeyP3Ready,
                        'title' => 'Les Marchés Castraux Florissants !',
                        'subtitle' => 'Palier III atteint : Naissance de la guilde Shōnin',
                        'quote' => '« L\'or appelle l\'or, et le parfum du thé attire les plus riches caravanes de l\'archipel. »',
                        'text' => "Splendide ! Vos stocks de farine de riz et de thé réjouissent les négociants. Les Marchands animent vos rues et débloquent les échanges marchands avancés. La prochaine étape exigera d'attirer l'élite de l'Académie : les Samouraïs et Érudits (*Bushi & Gakusha*), avides de saké sacré !",
                        'caste_target' => 3,
                        'status_type' => 'success',
                        'badge' => 'Succès : Palier III Débloqué',
                        'badge_color' => 'success',
                        'requirements' => [
                            ['name' => 'Farine de riz', 'icon' => 'fa-wheat-awn-circle-exclamation', 'color' => '#d97706', 'status' => 'fulfilled', 'desc' => number_format($stockFlour, 1) . ' en stock'],
                            ['name' => 'Thé vert', 'icon' => 'fa-leaf', 'color' => '#16a34a', 'status' => 'fulfilled', 'desc' => number_format($stockTea, 1) . ' en stock']
                        ],
                        'next_action_url' => '?page=research',
                        'next_action_label' => 'Ouvrir l\'Académie des Savoirs',
                        'can_dismiss' => true
                    ];
                }
            }
        }

        // --- 5. PALIER 3 -> PALIER 4 : Érudits & Samouraïs (Gakusha & Bushi) ---
        // Nécessite Saké de fête + Thé
        if ($civLevel === 3) {
            $hasSake = ($stockSake >= 10.0);
            $hasTea  = ($stockTea >= 10.0);

            if (!$hasSake || !$hasTea) {
                $mKeyP4Missing = 'wisdom_step_tier4_needs';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP4Missing)) {
                    return [
                        'key' => $mKeyP4Missing,
                        'title' => 'Rallier les Samouraïs & Érudits (Bushi & Gakusha)',
                        'subtitle' => 'Palier IV : Déblocage des hautes recherches de l\'Académie',
                        'quote' => '« Le sabre sans l\'esprit est un danger aveugle ; la lettre sans le sabre est une feuille au vent. »',
                        'text' => "Pour asseoir la réputation militaire et philosophique de votre fief, vous devez attirer les lettrés et guerriers d'honneur (*Bushi*). Ils exigent du <strong>Saké traditionnel</strong> de haute cuvée et du <strong>Thé vert</strong> pour la cérémonie du Chanoyu. Leur présence débloquera les secrets de la poudre noire et de la fonte des katanas légendaires !",
                        'caste_target' => 4,
                        'status_type' => 'progression',
                        'badge' => 'Objectif Palier IV',
                        'badge_color' => 'primary',
                        'requirements' => [
                            ['name' => 'Saké traditionnel', 'icon' => 'fa-wine-bottle', 'color' => '#9333ea', 'status' => ($hasSake ? 'fulfilled' : 'missing'), 'desc' => 'Brasseries de saké ou pressoirs'],
                            ['name' => 'Thé vert d\'Uji', 'icon' => 'fa-leaf', 'color' => '#16a34a', 'status' => ($hasTea ? 'fulfilled' : 'missing'), 'desc' => 'Récoltes des coteaux']
                        ],
                        'next_action_url' => '?page=research',
                        'next_action_label' => 'Consulter les Savoirs Avancés',
                        'can_dismiss' => true
                    ];
                }
            } else {
                $mKeyP4Ready = 'wisdom_tier4_celebration';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP4Ready)) {
                    return [
                        'key' => $mKeyP4Ready,
                        'title' => 'L\'Honneur des Samouraïs & la Lumière de l\'Académie !',
                        'subtitle' => 'Palier IV atteint : Noblesse d\'épée & Maîtres penseurs',
                        'quote' => '« Le Bushidō éclaire votre voie. Vos armées et vos académiciens font trembler les clans rivaux. »',
                        'text' => "Les Bushi et Gakusha ont rejoint votre bannière ! Vous pouvez désormais rechercher les technologies les plus destructrices et raffinées à l'Académie des Savoirs (poudre à canon Tanegashima, forge d'acier tamahagane). Il ne vous reste qu'un ultime sommet : réunir la Haute Noblesse (*Daimyō & Kuge*) pour prétendre au titre de Shōgun !",
                        'caste_target' => 4,
                        'status_type' => 'success',
                        'badge' => 'Succès : Palier IV Débloqué',
                        'badge_color' => 'success',
                        'requirements' => [
                            ['name' => 'Saké traditionnel', 'icon' => 'fa-wine-bottle', 'color' => '#9333ea', 'status' => 'fulfilled', 'desc' => number_format($stockSake, 1) . ' en stock'],
                            ['name' => 'Thé de cérémonie', 'icon' => 'fa-leaf', 'color' => '#16a34a', 'status' => 'fulfilled', 'desc' => number_format($stockTea, 1) . ' en stock']
                        ],
                        'next_action_url' => '?page=research',
                        'next_action_label' => 'Explorer les Nouvelles Recherches',
                        'can_dismiss' => true
                    ];
                }
            }
        }

        // --- 6. PALIER 4 -> PALIER 5 : La Haute Noblesse & Shōgunat (Daimyō & Kuge) ---
        if ($civLevel >= 4) {
            $hasSake5  = ($stockSake >= 25.0);
            $hasFlour5 = ($stockFlour >= 25.0);

            if ($civLevel === 4) {
                $mKeyP5Missing = 'wisdom_step_tier5_needs';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP5Missing)) {
                    return [
                        'key' => $mKeyP5Missing,
                        'title' => 'Convoquer la Haute Noblesse de Kyoto (Daimyō & Kuge)',
                        'subtitle' => 'Palier V : Le pinacle du prestige féodal impérial',
                        'quote' => '« Dix mille boisseaux de riz, du saké comme des rivières, et l\'Empereur lui-même tournera son regard vers votre donjon. »',
                        'text' => "Pour atteindre le rang suprême de la Haute Noblesse (*Daimyō & Kuge*), vos réserves de banquet doivent déborder de Saké millésimé et de Farine raffinée. En atteignant cette apogée, votre souveraineté sera incontestée dans tout le pays !",
                        'caste_target' => 5,
                        'status_type' => 'progression',
                        'badge' => 'Sommet : Palier V',
                        'badge_color' => 'danger',
                        'requirements' => [
                            ['name' => 'Saké en grande quantité', 'icon' => 'fa-wine-bottle', 'color' => '#9333ea', 'status' => ($hasSake5 ? 'fulfilled' : 'missing'), 'desc' => 'Nécessite au moins 25 unités de saké en réserve'],
                            ['name' => 'Farine de riz fine', 'icon' => 'fa-wheat-awn-circle-exclamation', 'color' => '#d97706', 'status' => ($hasFlour5 ? 'fulfilled' : 'missing'), 'desc' => 'Nécessite au moins 25 unités de farine']
                        ],
                        'next_action_url' => '?page=city',
                        'next_action_label' => 'Contempler le Donjon Tenshu',
                        'can_dismiss' => true
                    ];
                }
            } else {
                $mKeyP5Ready = 'wisdom_tier5_celebration';
                if ($ignoreSeen || !self::hasSeenMilestone($userId, $mKeyP5Ready)) {
                    return [
                        'key' => $mKeyP5Ready,
                        'title' => 'Apogée de l\'Empire Féodal : Gloire au Shōgun !',
                        'subtitle' => 'Palier V atteint : La Cour Impériale s\'incline devant vous',
                        'quote' => '« Tenka Fubu — L\'ordre règne sous le ciel, et votre nom est gravé pour l\'éternité dans les chroniques du Japon. »',
                        'text' => "Félicitations Suzerain ! Votre société a atteint le niveau de perfection absolu. De l'humble paysan jusqu'aux princes de la Cour de Kyoto, chaque âme vit dans l'harmonie et l'abondance. Vos forges façonnent les chefs-d'œuvre de la guerre et vos érudits détiennent les secrets de l'univers. Continuez de veiller sur vos terres avec bienveillance !",
                        'caste_target' => 5,
                        'status_type' => 'success',
                        'badge' => 'Légende Impériale',
                        'badge_color' => 'danger',
                        'requirements' => [
                            ['name' => 'Harmonie totale', 'icon' => 'fa-crown', 'color' => '#f59e0b', 'status' => 'fulfilled', 'desc' => 'Toutes les strates féodales comblées']
                        ],
                        'next_action_url' => '?page=empire',
                        'next_action_label' => 'Bilan de l\'Empire',
                        'can_dismiss' => true
                    ];
                }
            }
        }

        return null;
    }
}
