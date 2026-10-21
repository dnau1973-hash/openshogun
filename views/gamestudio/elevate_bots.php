<?php
/**
 * Game Studio - Game Elevate Designer : Clans IA & Daimyōs Autonomes
 * Fichier : views/gamestudio/elevate_bots.php
 * Interface de supervision de l'IA féodale, des cycles d'expansion et de l'éclosion spatiale homogène
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/game_constants.php';

$totalBots = count($botsList);
$isAiActive = !empty($settings['bots_enabled']);
$isColonizeActive = !empty($settings['bot_colonize_enabled']);
$isSpawnActive = !empty($botSpawnStatus['enabled']);
$spawnInterval = (int)($settings['bot_spawn_interval_min'] ?? 15);
$maxVillages = (int)($settings['bot_spawn_max_villages'] ?? 50);
$currentVillages = (int)($botSpawnStatus['current_villages'] ?? $totalBots);
$aggressiveness = $settings['bot_aggressiveness'] ?? 'moderate';
?>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- EN-TÊTE DE SECTION                                                  -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-indigo"><i class="fa-solid fa-robot"></i></span>
            <span>Clans IA &amp; Daimyōs Autonomes</span>
            <span class="badge bg-indigo-lt text-indigo font-monospace">Intelligence Féodale v2.0</span>
            <span class="badge bg-pink-lt text-pink font-monospace">Éclosion Spatiale 360°</span>
        </h2>
    </div>
    <div class="col-auto d-flex align-items-center gap-2 flex-wrap">
        <button type="button" onclick="triggerSpontaneousSpawn()" class="btn btn-sm btn-success fw-bold shadow-sm">
            <i class="fa-solid fa-spa text-pink me-1"></i>Faire Éclore un Fief PNJ
        </button>
        <button type="button" onclick="runBotCycle()" class="btn btn-sm btn-outline-primary fw-bold shadow-sm">
            <i class="fa-solid fa-gear me-1"></i>Forcer un Cycle IA
        </button>
        <button type="button" onclick="generatePresetBots()" class="btn btn-sm btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i>Ajouter 3 Daimyōs Multi-Clans
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 1. MÉTRIQUES CLÉS & ÉTAT EN TEMPS RÉEL                             -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="row row-cards g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-indigo text-white avatar">
                            <i class="fa-solid fa-robot"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Daimyōs IA Actifs</div>
                        <div class="text-secondary small">
                            <strong class="fs-2 text-indigo"><?= $totalBots ?></strong> clans enregistrés
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="<?= $isAiActive ? 'bg-success' : 'bg-secondary' ?> text-white avatar">
                            <i class="fa-solid fa-microchip"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Statut du Moteur IA</div>
                        <div class="text-secondary small">
                            <?php if ($isAiActive): ?>
                                <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i>Cycles Actifs</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt fw-bold"><i class="fa-solid fa-moon text-secondary me-1"></i>En Veille</span>
                            <?php endif; ?>
                            &bull; Profil <strong><?= ucfirst($aggressiveness) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-pink text-white avatar">
                            <i class="fa-solid fa-spa"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Éclosion Spontanée</div>
                        <div class="text-secondary small">
                            <?php if ($isSpawnActive): ?>
                                <span class="badge bg-pink-lt fw-bold">Toutes les <?= $spawnInterval ?> min</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt fw-bold">Désactivée</span>
                            <?php endif; ?>
                            &bull; <strong><?= $currentVillages ?> / <?= $maxVillages ?></strong> fiefs
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="bg-azure text-white avatar">
                            <i class="fa-solid fa-stopwatch"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">Prochaine Éclosion</div>
                        <div class="display-6 fw-bold text-primary font-monospace" id="botSpawnCountdownText" style="font-size: 1.25rem;">
                            <?= $isSpawnActive ? ($botSpawnStatus['next_spawn_in_formatted'] ?? '--m --s') : '--' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 2. DIRECTIVE GÉNÉRALE & PARAMÉTRAGE DE L'IA                         -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-sliders text-warning fs-3"></i>
            <div>
                <h3 class="card-title text-white mb-0">Directives Stratégiques &amp; Règles de Décision des PNJ</h3>
                <div class="small text-white-50">Configuration du rythme de progression, expansion des clans et politique militaire</div>
            </div>
        </div>
        <span class="badge bg-indigo text-white font-monospace">Moteur BotEngine</span>
    </div>
    <div class="card-body p-4">
        <!-- Préréglages Stratégiques 1-Clic Recommandés -->
        <div class="p-3 bg-surface-secondary border rounded shadow-xs mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-indigo"></i>Préréglages Stratégiques 1-Clic Recommandés :
                </span>
                <span class="text-secondary small">Calibre instantanément les paramètres généraux et d'éclosion</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn <?= in_array($aggressiveness, ['peaceful', 'passive'], true) ? 'btn-success shadow-sm' : 'btn-outline-secondary' ?> btn-sm bot-preset-btn fw-bold" onclick="applyElevateBotPreset('peaceful', this)">
                    <i class="fa-solid fa-shield text-success me-1"></i>Bonsaï Défensif (Agricole &amp; Shinto)
                </button>
                <button type="button" class="btn <?= $aggressiveness === 'moderate' ? 'btn-indigo shadow-sm' : 'btn-outline-secondary' ?> btn-sm bot-preset-btn fw-bold" onclick="applyElevateBotPreset('moderate', this)">
                    <i class="fa-solid fa-scale-balanced me-1"></i>Sengoku Équilibré (Standard Shogun)
                </button>
                <button type="button" class="btn <?= $aggressiveness === 'aggressive' ? 'btn-danger shadow-sm' : 'btn-outline-secondary' ?> btn-sm bot-preset-btn fw-bold" onclick="applyElevateBotPreset('aggressive', this)">
                    <i class="fa-solid fa-fire text-danger me-1"></i>Tempête Martiale Oda (Guerre Totale)
                </button>
            </div>
        </div>

        <form id="botSettingsForm" onsubmit="saveBotSettings(event)">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="fa-solid fa-power-off text-primary me-1"></i>Interrupteur Général de l'IA
                    </label>
                    <select name="bots_enabled" class="form-select" id="bots_enabled">
                        <option value="1" <?= $isAiActive ? 'selected' : '' ?>>IA Active (Cycles opérationnels)</option>
                        <option value="0" <?= !$isAiActive ? 'selected' : '' ?>>IA En Veille (Bots figés)</option>
                    </select>
                    <div class="form-hint">Permet aux clans de développer leurs mines, bâtiments et armées.</div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="fa-solid fa-chess-rook text-indigo me-1"></i>Colonisation Automatique
                    </label>
                    <select name="bot_colonize_enabled" class="form-select" id="bot_colonize_enabled">
                        <option value="1" <?= $isColonizeActive ? 'selected' : '' ?>>Autorisée (Conquêtes de fiefs)</option>
                        <option value="0" <?= !$isColonizeActive ? 'selected' : '' ?>>Désactivée (Domaine initial)</option>
                    </select>
                    <div class="form-hint">Autorise les Daimyōs IA à coloniser de nouvelles tuiles de l'archipel.</div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="fa-solid fa-layer-group text-success me-1"></i>Max Fiefs par Daimyō IA
                    </label>
                    <input type="number" name="bot_max_planets" id="bot_max_planets" min="1" max="10" 
                           value="<?= (int)($settings['bot_max_planets'] ?? 3) ?>" class="form-control text-center font-weight-bold">
                    <div class="form-hint">Plafond maximum de domaines contrôlés par un même seigneur PNJ.</div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="fa-solid fa-shield-halved text-danger me-1"></i>Profil d'Agressivité
                    </label>
                    <select name="bot_aggressiveness" class="form-select" id="bot_aggressiveness">
                        <option value="passive" <?= in_array($aggressiveness, ['peaceful', 'passive'], true) ? 'selected' : '' ?>>Passif / Pacifique (Mines &amp; Défense)</option>
                        <option value="moderate" <?= ($aggressiveness === 'moderate') ? 'selected' : '' ?>>Modéré (Équilibré)</option>
                        <option value="aggressive" <?= ($aggressiveness === 'aggressive') ? 'selected' : '' ?>>Belliqueux (Armées &amp; Raids)</option>
                    </select>
                    <div class="form-hint">Régit les priorités budgétaires : artisanat civil vs recrutement militaire.</div>
                </div>
            </div>

            <!-- Sous-bloc Éclosion Spontanée -->
            <div class="card bg-surface-secondary border-primary-subtle mb-3">
                <div class="card-header bg-primary-lt py-2 d-flex justify-content-between align-items-center">
                    <h4 class="card-title m-0 text-primary fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-spa text-pink me-1"></i>Éclosion Spontanée de Villages PNJ (Couverture Homogène 360°)
                    </h4>
                    <span class="badge bg-primary text-white">
                        Cadence : Toutes les <?= $spawnInterval ?> min
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                <i class="fa-solid fa-seedling text-success me-1"></i>Mécanisme d'Éclosion Spontanée
                            </label>
                            <select name="bot_spawn_enabled" class="form-select" id="bot_spawn_enabled">
                                <option value="1" <?= $isSpawnActive ? 'selected' : '' ?>>Éclosion Activée (Automatique)</option>
                                <option value="0" <?= !$isSpawnActive ? 'selected' : '' ?>>Éclosion En Sommeil (Désactivée)</option>
                            </select>
                            <div class="form-hint">Génère périodiquement un nouveau fief dans le quadrant le plus vide.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                <i class="fa-solid fa-stopwatch text-warning me-1"></i>Intervalle d'Apparition (Minutes)
                            </label>
                            <div class="input-group">
                                <input type="number" name="bot_spawn_interval_min" id="bot_spawn_interval_min" min="1" max="1440" 
                                       value="<?= $spawnInterval ?>" class="form-control text-center font-weight-bold">
                                <span class="input-group-text">min</span>
                            </div>
                            <div class="form-hint">Délai planifié entre chaque vague d'éclosion provinciale.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark mb-1">
                                <i class="fa-solid fa-chess-rook text-danger me-1"></i>Plafond Global de Villages PNJ
                            </label>
                            <input type="number" name="bot_spawn_max_villages" id="bot_spawn_max_villages" min="5" max="200" 
                                   value="<?= $maxVillages ?>" class="form-control text-center font-weight-bold">
                            <div class="form-hint">Nombre total limite de villages PNJ admis sur la carte.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnSaveBotSettings">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Enregistrer les Directives IA &amp; Éclosions
                </button>
            </div>
        </form>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- GUIDE & EXEMPLES CONCRETS DES RÈGLES DE DÉCISION DES PNJ   -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <div class="pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4 class="m-0 text-dark fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-diagram-project text-indigo"></i>Exemples Concrets &amp; Arbres de Décision du Moteur <code>BotEngine</code>
                    </h4>
                    <div class="text-secondary small">
                        Détail algorithmique des 6 règles régissant chaque cycle de simulation autonome des seigneurs féodaux.
                    </div>
                </div>
                <span class="badge bg-indigo-lt text-indigo font-monospace fw-bold">Cadence de cycle : ~5 minutes</span>
            </div>

            <!-- Grille des 6 Arbres de Décision avec Exemples Concrets -->
            <div class="row g-3 mb-4">
                <!-- Règle 1 : Économie & 18 Parcelles Rurales -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-success-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-success d-flex align-items-center gap-2">
                                <i class="fa-solid fa-seedling"></i>1. Décision Économique &amp; Parcelles Rurales
                            </span>
                            <span class="badge bg-success text-white font-monospace">Niv Max 15</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Algorithme :</strong> Tri ascendant des parcelles <code>usort(fields, level ASC)</code>. Le bot identifie la parcelle la plus faible sous le niveau 15 et lance son amélioration dès que ses greniers le permettent.
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-success small mb-2">
                                <div class="text-success fw-bold mb-1"><i class="fa-solid fa-lightbulb me-1"></i>Exemple Concret :</div>
                                Le Daimyō <strong>Oda Nobunaga</strong> inspecte son fief. Ses rizières sont Niv 4, ses scieries Niv 3, mais sa <em>Carrière de Granit #6</em> est restée Niv 2. Le moteur consacre immédiatement ses stocks de Koku et de Bois pour promouvoir la carrière au <strong>Niveau 3</strong> (+22 Pierre/h).
                            </div>
                            <div class="text-muted small">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>Garantit un développement harmonieux et homogène des 18 parcelles du terroir.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Règle 2 : Urbanisme & Tenshu Castral -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-primary-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary d-flex align-items-center gap-2">
                                <i class="fa-solid fa-chess-rook"></i>2. Décision d'Urbanisme &amp; Fortifications
                            </span>
                            <span class="badge bg-primary text-white font-monospace">Tenshu &bull; Caserne</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Algorithme :</strong> Arbitrage pondéré aléatoire : si le Tenshu / Donjon (<code>hq</code>) est &lt; Niv 10, le bot a <strong>60% de chance</strong> (tirage 1-10 &le; 6) de l'élever. Sinon, si la Caserne (<code>barracks</code>) est &lt; Niv 8, il améliore la Caserne.
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-primary small mb-2">
                                <div class="text-primary fw-bold mb-1"><i class="fa-solid fa-lightbulb me-1"></i>Exemple Concret :</div>
                                Avec 2 500 bois et 3 000 pierre en réserve, le bot tire un score de 4 (&le; 6) : il lance l'élévation du <strong>Tenshu Niveau 5</strong>, augmentant de +10% la vitesse de tous les futurs chantiers du fief.
                            </div>
                            <div class="text-muted small">
                                <i class="fa-solid fa-circle-check text-primary me-1"></i>Priorise le cœur castral pour réduire les temps de construction avant les armées.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Règle 3 : Conscription Militaire par Faction -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-danger-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-danger d-flex align-items-center gap-2">
                                <i class="fa-solid fa-person-rifle"></i>3. Conscription Martiale selon la Faction
                            </span>
                            <span class="badge bg-danger text-white font-monospace">5 à 15 unités / cycle</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Algorithme :</strong> Recrutement automatique d'un bataillon de <strong>5 à 15 unités</strong> par fief à chaque tick, selon la doctrine de guerre propre à la faction du seigneur :
                            </div>
                            <div class="small mb-2">
                                <span class="badge bg-primary-lt text-primary font-monospace me-1">Terran (Oda)</span> : Piquiers Ashigaru Yari &amp; Arquebusiers Tanegashima<br>
                                <span class="badge bg-danger-lt text-danger font-monospace me-1">Vorash (Takeda)</span> : Fantassins légers &amp; Archers Yumi montés (Cavalerie)<br>
                                <span class="badge bg-warning-lt text-warning font-monospace me-1">Aethelis (Tokugawa)</span> : Sentinelles Yari &amp; Archers de muraille
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-danger small">
                                <div class="text-danger fw-bold mb-1"><i class="fa-solid fa-shield-cat me-1"></i>Garnison Inviolable :</div>
                                Le bot maintient en permanence une garnison minimale de <strong>15 soldats</strong> au château : aucune troupe ne part en raid si les réserves de garde sont insuffisantes.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Règle 4 : Raids Féodaux & Sécurité Débutants -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-pink-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-pink d-flex align-items-center gap-2">
                                <i class="fa-solid fa-khanda"></i>4. Règles d'Engagement &amp; Raids Féodaux
                            </span>
                            <span class="badge bg-success text-white font-monospace">Immunité Débutant Inviolable</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Clause Sacrée :</strong> <code>WHERE u.protection_until IS NULL OR u.protection_until &le; NOW()</code>. Les seigneurs protégés par le bouclier débutant sont <strong>totalement exclus</strong> et invisibles pour le cerveau offensif des bots.
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-pink small mb-2">
                                <div class="text-pink fw-bold mb-1"><i class="fa-solid fa-crosshairs me-1"></i>Critères de Ciblage :</div>
                                • <strong>Rayon d'action :</strong> &le; 35 lieues cartographiques.<br>
                                • <strong>Probabilité :</strong> 0% (Pacifique), 15% (Modéré), 35% (Belliqueux).<br>
                                • <strong>Cooldown anti-acharnement :</strong> 10 min en Belliqueux, 20 min en Modéré.<br>
                                • <strong>Cibles prioritaires :</strong> Fiefs humains non protégés les plus proches, puis rivaux IA.
                            </div>
                            <div class="text-muted small">
                                <i class="fa-solid fa-circle-check text-success me-1"></i>Respect absolu du sanctuaire des nouveaux joueurs arrivant sur le serveur.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Règle 5 : Infiltration Shinobi & Espionnage -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-azure-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-azure d-flex align-items-center gap-2">
                                <i class="fa-solid fa-user-ninja"></i>5. Reconnaissance Shinobi &amp; Infiltration
                            </span>
                            <span class="badge bg-azure text-white font-monospace">Portée 45 lieues</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Algorithme :</strong> Envoi d'éclaireurs Shinobi pour sonder les garnisons et stocks ennemis avant toute campagne militaire.
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-azure small mb-2">
                                <div class="text-azure fw-bold mb-1"><i class="fa-solid fa-lightbulb me-1"></i>Fréquence &amp; Cadence :</div>
                                • Mode Pacifique : 0% espionnage.<br>
                                • Mode Modéré : 20% probabilité, cooldown 15 minutes.<br>
                                • Mode Belliqueux : 45% probabilité, cooldown 8 minutes.<br>
                                • Auto-ravitaillement : Si le fief manque d'espions, production automatique de 3 éclaireurs d'avant-garde.
                            </div>
                            <div class="text-muted small">
                                Les missions sont tracées dans la table <code>fleet_missions</code> avec durée de marche réelle.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Règle 6 : Colonisation Autonome & Expansion Fiefs -->
                <div class="col-lg-6">
                    <div class="card h-100 border shadow-xs bg-white">
                        <div class="card-header bg-indigo-lt py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-indigo d-flex align-items-center gap-2">
                                <i class="fa-solid fa-map-location-dot"></i>6. Colonisation &amp; Expansion Provinciale
                            </span>
                            <span class="badge bg-indigo text-white font-monospace">1 à 10 fiefs max</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="small mb-2">
                                <strong>Algorithme :</strong> Déclenchement à <strong>25% de chance</strong> si <code>bot_colonize_enabled = 1</code> et nombre de fiefs actuels &lt; <code>bot_max_planets</code>.
                            </div>
                            <div class="p-2 bg-light rounded border-start border-3 border-indigo small mb-2">
                                <div class="text-indigo fw-bold mb-1"><i class="fa-solid fa-lightbulb me-1"></i>Processus d'Implantation :</div>
                                1. Recherche automatique d'une coordonnée libre <code>(x, y)</code> dans l'archipel.<br>
                                2. Fondation du nouveau domaine : <em>"Fief Daimyō N"</em> avec 2 000 Bois, 1 500 Pierre et 800 Fer.<br>
                                3. Génération instantanée des 18 parcelles vierges de terroir via <code>VillageFieldGenerator</code>.
                            </div>
                            <div class="text-muted small">
                                <i class="fa-solid fa-circle-check text-indigo me-1"></i>Le clan étend sa présence territoriale sans dépasser le plafond fixé par l'administrateur.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Console Simulée : Exemple de Rapport d'un Cycle Réel BotEngine -->
            <div class="card bg-dark text-white shadow-sm border-0 mb-2">
                <div class="card-header bg-black py-2 d-flex justify-content-between align-items-center">
                    <span class="font-monospace text-success small fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-terminal"></i>Console BotEngine &bull; Exemple de Trace d'Exécution d'un Cycle de Simulation
                    </span>
                    <span class="badge bg-dark text-white-50 font-monospace">executeBotCycle() &bull; JSON Output</span>
                </div>
                <div class="card-body p-3 font-monospace small bg-black bg-opacity-75 text-light" style="font-size: 0.82rem; line-height: 1.5;">
                    <span class="text-success">[BotEngine]</span> Démarrage du cycle de simulation féodale pour <strong class="text-warning">3 Daimyōs actifs</strong>...<br>
                    <span class="text-cyan">&gt; Clan Oda Nobunaga [12 : 8] :</span> Rizières #3 &rarr; Niv 5 (+32 Riz/h) | Tenshu &rarr; Niv 4 | +12 Arquebusiers Tanegashima recrutés.<br>
                    <span class="text-danger">&gt; Clan Takeda Shingen [-14 : 22] :</span> Carrière de Granit #2 &rarr; Niv 3 | Caserne &rarr; Niv 3 | +10 Archers Yumi montés.<br>
                    <span class="text-pink">&gt; Mission Infiltration Shinobi :</span> Éclaireur de Takeda envoyé vers le fief voisin [-10 : 18] (Distance: 15.2 lieues, vol: 48s).<br>
                    <span class="text-purple">&gt; Colonisation Autonome :</span> Oda Nobunaga fonde <strong>"Fief Oda Nobunaga 2"</strong> aux coordonnées [18 : 6] (18 parcelles générées).<br>
                    <span class="text-success">&gt; Contrôle Sécurité Débutants :</span> 4 nouveaux joueurs sous bouclier débutant &rarr; 100% exclus des listes de cibles.<br>
                    <span class="text-warning">[Rapport Global]</span> 3 bots traités, +3 parcelles élevées, +2 bâtiments fortifiés, +22 soldats conscrits, 1 nouveau fief fondé.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- DÉCLENCHEURS STRATÉGIQUES IMMÉDIATS (1-CLICK)                       -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card mb-4 shadow-sm border-0 overflow-hidden">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div>
            <h4 class="card-title m-0 text-dark fw-bold d-flex align-items-center gap-2">
                <i class="fa-solid fa-bolt text-warning fs-3"></i>
                <span>Déclencheurs Stratégiques Immédiats (1-Click)</span>
            </h4>
            <div class="text-secondary small mt-1">Commandes directes pour peupler la carte, forcer une expansion ou tester les réactions des clans en temps réel.</div>
        </div>
        <span class="badge bg-warning-lt text-dark fw-bold font-monospace px-3 py-1">Actions 1-Click</span>
    </div>

    <div class="card-body p-4 bg-light bg-opacity-25">
        <div class="row g-4 align-items-stretch">
            <!-- Action 1 : Déployer 3 Daimyōs IA Historiques -->
            <div class="col-lg-4 d-flex">
                <div class="card w-100 border shadow-xs bg-white d-flex flex-column justify-content-between" style="border-top: 3px solid #206bc4 !important;">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold fs-4 text-primary d-flex align-items-center gap-2">
                                <i class="fa-solid fa-users-viewfinder text-primary"></i>3 Daimyōs Historiques
                            </span>
                            <span class="badge bg-primary text-white font-monospace">+3 Seigneurs</span>
                        </div>
                        <p class="text-secondary small mb-3 flex-grow-1" style="line-height: 1.45;">
                            Crée instantanément les clans légendaires <strong>Oda</strong>, <strong>Takeda</strong> et <strong>Tokugawa</strong> avec leurs châteaux capitaux et garnisons.
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <button type="button" onclick="generatePresetBots()" class="btn btn-primary w-100 fw-bold shadow-xs py-2">
                                <i class="fa-solid fa-plus-circle me-1"></i>Déployer les 3 Seigneurs
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action 2 : Faire Éclore un Fief PNJ Spontané -->
            <div class="col-lg-4 d-flex">
                <div class="card w-100 border shadow-xs bg-white d-flex flex-column justify-content-between" style="border-top: 3px solid #d6336c !important;">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold fs-4 text-pink d-flex align-items-center gap-2">
                                <i class="fa-solid fa-spa text-pink"></i>Éclosion Immédiate 360°
                            </span>
                            <span class="badge bg-pink text-white font-monospace">+1 Fief PNJ</span>
                        </div>
                        <p class="text-secondary small mb-3 flex-grow-1" style="line-height: 1.45;">
                            Sélectionne un angle libre à 360° autour de Kyoto et fait germer un domaine PNJ autonome avec 18 parcelles vierges de terroir.
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <button type="button" onclick="triggerSpontaneousSpawn()" class="btn text-white w-100 fw-bold shadow-xs py-2" style="background-color: #d6336c; border-color: #d6336c;">
                                <i class="fa-solid fa-seedling me-1"></i>Faire Éclore un Fief
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action 3 : Forcer un Cycle IA Immédiat -->
            <div class="col-lg-4 d-flex">
                <div class="card w-100 border shadow-xs bg-white d-flex flex-column justify-content-between" style="border-top: 3px solid #4263eb !important;">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold fs-4 text-indigo d-flex align-items-center gap-2">
                                <i class="fa-solid fa-gear text-indigo"></i>Cycle de Décision IA
                            </span>
                            <span class="badge bg-indigo text-white font-monospace">Tick IA</span>
                        </div>
                        <p class="text-secondary small mb-3 flex-grow-1" style="line-height: 1.45;">
                            Exécute immédiatement le cerveau des bots : collecte économique, investissements de parcelles, conscription et expéditions féodales.
                        </p>
                        <div class="mt-auto pt-2 border-top">
                            <button type="button" onclick="runBotCycle()" class="btn btn-indigo text-white w-100 fw-bold shadow-xs py-2">
                                <i class="fa-solid fa-play me-1"></i>Exécuter un Cycle
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 3. MONITORING SPATIAL & HOMOGÉNÉITÉ PAR QUADRANT                    -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar bg-primary-lt text-primary"><i class="fa-solid fa-map-location-dot"></i></span>
            <div>
                <h4 class="card-title m-0 fw-bold">Distribution Spatiale &amp; Homogénéité Géographique</h4>
                <div class="text-secondary small">
                    Analyse en temps réel de la densité des 4 quadrants féodaux pour garantir une répartition harmonieuse de l'archipel.
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" onclick="triggerSpontaneousSpawn()" class="btn btn-sm btn-success fw-bold">
                <i class="fa-solid fa-spa text-pink me-1"></i>Déclencher Éclosion Immédiate
            </button>
        </div>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <!-- Compte à rebours & métriques globales -->
            <div class="col-lg-4">
                <div class="p-3 border rounded bg-surface h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary fw-bold small text-uppercase">Statut d'Éclosion :</span>
                            <?php if ($isSpawnActive): ?>
                                <span class="badge bg-success-lt fw-bold"><i class="fa-solid fa-circle-check text-success me-1"></i>Actif (<?= $spawnInterval ?> min)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt fw-bold"><i class="fa-solid fa-moon text-secondary me-1"></i>En Sommeil</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-center py-2">
                            <div class="text-secondary small">Prochaine Éclosion dans :</div>
                            <div class="display-6 fw-bold text-primary font-monospace py-1" id="botSpawnCountdownWidget">
                                <?= $isSpawnActive ? ($botSpawnStatus['next_spawn_in_formatted'] ?? '--m --s') : '--' ?>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-2 mt-2">
                        <div class="small d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Fiefs PNJ :</span>
                            <strong><?= $currentVillages ?> / <?= $maxVillages ?></strong>
                        </div>
                        <div class="small d-flex justify-content-between">
                            <span class="text-muted">Dernière Apparition :</span>
                            <span class="text-dark fw-medium"><?= $botSpawnStatus['last_spawn_formatted'] ?? 'Aucune' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Répartition par Quadrant (Homogénéité) -->
            <div class="col-lg-8">
                <div class="p-3 border rounded bg-surface">
                    <div class="fw-bold mb-2 small text-uppercase text-secondary d-flex justify-content-between">
                        <span><i class="fa-solid fa-chart-pie me-1"></i>Densité Actuelle des Fiefs PNJ par Quadrant :</span>
                        <span class="badge bg-teal-lt text-teal fw-bold">Équilibrage Homogène 360°</span>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="small fw-bold text-dark">↖️ Nord-Ouest</div>
                                <div class="h2 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['NO'] ?? 0 ?></div>
                                <div class="text-muted small">fiefs PNJ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="small fw-bold text-dark">↗️ Nord-Est</div>
                                <div class="h2 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['NE'] ?? 0 ?></div>
                                <div class="text-muted small">fiefs PNJ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="small fw-bold text-dark">↙️ Sud-Ouest</div>
                                <div class="h2 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['SO'] ?? 0 ?></div>
                                <div class="text-muted small">fiefs PNJ</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-light">
                                <div class="small fw-bold text-dark">↘️ Sud-Est</div>
                                <div class="h2 m-0 text-primary font-monospace"><?= $botSpawnStatus['quadrants']['SE'] ?? 0 ?></div>
                                <div class="text-muted small">fiefs PNJ</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($botSpawnStatus['last_village'])): ?>
                        <?php $lv = $botSpawnStatus['last_village']; ?>
                        <div class="mt-3 p-2 bg-success-lt border border-success-subtle rounded small d-flex justify-content-between align-items-center flex-wrap gap-1">
                            <span>
                                <i class="fa-solid fa-spa text-pink me-1"></i><strong>Dernier Fief Éclos :</strong> 
                                <?= htmlspecialchars($lv['planet_name']) ?> (<?= htmlspecialchars($lv['username']) ?>) 
                                en <strong>[<?= $lv['x'] ?> : <?= $lv['y'] ?>]</strong> &bull; Quadrant <strong><?= $lv['quadrant'] ?></strong>
                            </span>
                            <a href="/?page=map&x=<?= $lv['x'] ?>&y=<?= $lv['y'] ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                <i class="fa-solid fa-map-location-dot me-1"></i>Localiser sur la Carte
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- 4. REGISTRE DES DAIMYŌS IA EN ACTIVITÉ                              -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clipboard-list text-warning fs-3"></i>
            <div>
                <h3 class="card-title text-white mb-0">Registre des Daimyōs IA en Activité</h3>
                <div class="small text-white-50">Gestion individuelle des clans simulés, capitales et purges administratives</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-indigo text-white font-monospace"><?= $totalBots ?> Daimyō(s) Déployé(s)</span>
            <button onclick="generatePresetBots()" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-plus me-1"></i>Ajouter 3 Daimyōs
            </button>
        </div>
    </div>

    <!-- Barre de recherche rapide -->
    <div class="card-body border-bottom py-2 bg-light">
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="botSearchInput" class="form-control form-control-sm" placeholder="Filtrer par nom de daimyō, clan ou coordonnées..." oninput="filterBotsTable()">
                </div>
            </div>
            <div class="col-md-auto ms-auto d-flex align-items-center gap-2">
                <label for="botsPerPageSelect" class="small text-muted mb-0">Par page :</label>
                <select id="botsPerPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changeBotsPerPage(this.value)">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($botsList)): ?>
            <div class="text-center p-5 text-secondary">
                <i class="fa-solid fa-inbox fs-1 d-block mb-3 text-muted"></i>
                <div class="h4 text-dark">Aucun Daimyō IA n'est actuellement déployé dans l'archipel</div>
                <p class="text-muted mb-3">Cliquez sur le bouton ci-dessous pour peupler immédiatement le royaume féodal avec les clans historiques.</p>
                <button onclick="generatePresetBots()" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>Générer le Groupe Initial (Oda, Takeda, Tokugawa)
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-nowrap card-table table-hover mb-0" id="tableBotsList">
                    <thead class="bg-light">
                        <tr>
                            <th>Daimyō IA</th>
                            <th>Clan Féodal</th>
                            <th>Fief Capitale</th>
                            <th class="text-center">Fiefs Annexes</th>
                            <th class="text-end">Puissance Militaire</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="botsTableBody">
                        <?php foreach ($botsList as $botIndex => $bot): ?>
                            <?php 
                                $fInfo = FACTIONS[$bot['faction']] ?? FACTIONS['terran'] ?? ['name' => ucfirst($bot['faction']), 'icon' => '🏯']; 
                                $isInitialHidden = ($botIndex >= 10);
                            ?>
                            <tr class="bot-table-row" data-index="<?= $botIndex ?>" data-search="<?= strtolower(htmlspecialchars($bot['username'] . ' ' . ($fInfo['name'] ?? '') . ' ' . $bot['capital_x'] . ' ' . $bot['capital_y'])) ?>" style="<?= $isInitialHidden ? 'display: none;' : '' ?>">
                                <td>
                                    <div class="font-weight-medium d-flex align-items-center gap-2">
                                        <span class="avatar avatar-xs bg-indigo-lt text-indigo rounded-circle">
                                            <i class="fa-solid fa-robot"></i>
                                        </span>
                                        <strong><?= htmlspecialchars($bot['username']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt">
                                        <?= $fInfo['icon'] ?? '' ?> <?= htmlspecialchars($fInfo['name'] ?? $bot['faction']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/?page=map&x=<?= $bot['capital_x'] ?>&y=<?= $bot['capital_y'] ?>" target="_blank" class="badge bg-danger-lt font-monospace text-decoration-none" title="Explorer sur la carte">
                                        <i class="fa-solid fa-location-dot me-1"></i>[<?= $bot['capital_x'] ?> : <?= $bot['capital_y'] ?>]
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-lt font-weight-bold">
                                        <?= (int)($bot['planet_count'] ?? 1) ?> fief(s)
                                    </span>
                                </td>
                                <td class="text-end font-weight-bold text-warning">
                                    <i class="fa-solid fa-trophy text-warning me-1"></i><?= number_format((int)($bot['points'] ?? 0)) ?>
                                </td>
                                <td class="text-end">
                                    <button onclick="deleteBot(<?= (int)$bot['id'] ?>, '<?= htmlspecialchars(addslashes($bot['username']), ENT_QUOTES) ?>')" 
                                            class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash me-1"></i>Purger
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination du Registre des Bots -->
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2" id="botsPaginationContainer">
                <p class="m-0 text-secondary small" id="botsPaginationInfo">
                    Affichage de <strong id="botsPaginationStart"><?= min(1, $totalBots) ?></strong> à <strong id="botsPaginationEnd"><?= min(10, $totalBots) ?></strong> sur <strong id="botsPaginationTotal"><?= $totalBots ?></strong> daimyōs IA
                </p>
                <ul class="pagination pagination-sm m-0" id="botsPaginationList">
                    <!-- Généré dynamiquement en JS -->
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════ -->
<!-- SCRIPTS LOGIQUE & INTERACTIONS AJAX                                 -->
<!-- ═══════════════════════════════════════════════════════════════════ -->
<script>
// Compte à rebours temps réel pour l'éclosion spontanée
let botSpawnSecondsRemaining = <?= (int)($botSpawnStatus['next_spawn_in_seconds'] ?? 0) ?>;
const isBotSpawnActive = <?= $isSpawnActive ? 'true' : 'false' ?>;

if (isBotSpawnActive && botSpawnSecondsRemaining > 0) {
    setInterval(() => {
        if (botSpawnSecondsRemaining > 0) {
            botSpawnSecondsRemaining--;
            const mins = Math.floor(botSpawnSecondsRemaining / 60);
            const secs = botSpawnSecondsRemaining % 60;
            const text = `${String(mins).padStart(2, '0')}m ${String(secs).padStart(2, '0')}s`;
            
            const el1 = document.getElementById('botSpawnCountdownText');
            if (el1) el1.textContent = text;
            
            const el2 = document.getElementById('botSpawnCountdownWidget');
            if (el2) el2.textContent = text;
        } else {
            const el1 = document.getElementById('botSpawnCountdownText');
            if (el1) el1.textContent = "Éclosion imminente...";
            const el2 = document.getElementById('botSpawnCountdownWidget');
            if (el2) el2.textContent = "Éclosion imminente...";
        }
    }, 1000);
}

// 0. Préréglages Stratégiques 1-Clic Recommandés
function applyElevateBotPreset(preset, btn) {
    document.querySelectorAll('.bot-preset-btn').forEach(b => {
        b.classList.remove('btn-indigo', 'btn-danger', 'btn-success', 'shadow-sm');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.remove('btn-outline-secondary');
        if (preset === 'peaceful') btn.classList.add('btn-success', 'shadow-sm');
        else if (preset === 'aggressive') btn.classList.add('btn-danger', 'shadow-sm');
        else btn.classList.add('btn-indigo', 'shadow-sm');
    }

    const botsEnabled = document.getElementById('bots_enabled');
    const colonizeEnabled = document.getElementById('bot_colonize_enabled');
    const maxPlanets = document.getElementById('bot_max_planets');
    const aggressiveness = document.getElementById('bot_aggressiveness');
    const spawnEnabled = document.getElementById('bot_spawn_enabled');
    const spawnInterval = document.getElementById('bot_spawn_interval_min');
    const spawnMax = document.getElementById('bot_spawn_max_villages');

    if (preset === 'peaceful') {
        if (botsEnabled) botsEnabled.value = '1';
        if (colonizeEnabled) colonizeEnabled.value = '0';
        if (maxPlanets) maxPlanets.value = '1';
        if (aggressiveness) aggressiveness.value = 'passive';
        if (spawnEnabled) spawnEnabled.value = '1';
        if (spawnInterval) spawnInterval.value = '60';
        if (spawnMax) spawnMax.value = '20';
    } else if (preset === 'moderate') {
        if (botsEnabled) botsEnabled.value = '1';
        if (colonizeEnabled) colonizeEnabled.value = '1';
        if (maxPlanets) maxPlanets.value = '3';
        if (aggressiveness) aggressiveness.value = 'moderate';
        if (spawnEnabled) spawnEnabled.value = '1';
        if (spawnInterval) spawnInterval.value = '15';
        if (spawnMax) spawnMax.value = '50';
    } else if (preset === 'aggressive') {
        if (botsEnabled) botsEnabled.value = '1';
        if (colonizeEnabled) colonizeEnabled.value = '1';
        if (maxPlanets) maxPlanets.value = '5';
        if (aggressiveness) aggressiveness.value = 'aggressive';
        if (spawnEnabled) spawnEnabled.value = '1';
        if (spawnInterval) spawnInterval.value = '5';
        if (spawnMax) spawnMax.value = '100';
    }
}

// 1. Sauvegarde des Directives IA & Éclosions
async function saveBotSettings(event) {
    if (event) event.preventDefault();
    const btn = document.getElementById('btnSaveBotSettings');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i>Enregistrement...`;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'save_settings');
        formData.append('bots_enabled', document.getElementById('bots_enabled').value);
        formData.append('bot_colonize_enabled', document.getElementById('bot_colonize_enabled').value);
        formData.append('bot_max_planets', document.getElementById('bot_max_planets').value);
        formData.append('bot_aggressiveness', document.getElementById('bot_aggressiveness').value);
        formData.append('bot_spawn_enabled', document.getElementById('bot_spawn_enabled').value);
        formData.append('bot_spawn_interval_min', document.getElementById('bot_spawn_interval_min').value);
        formData.append('bot_spawn_max_villages', document.getElementById('bot_spawn_max_villages').value);

        const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Directives Enregistrées", "Les paramètres de l'IA et de l'éclosion spatiale ont été mis à jour avec succès !", "success");
            } else {
                alert("Paramètres enregistrés avec succès !");
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Erreur", data.error || "Impossible d'enregistrer la directive.", "danger");
            } else {
                alert("Erreur: " + (data.error || "Impossible d'enregistrer."));
            }
        }
    } catch (e) {
        if (typeof showModalAlert === 'function') {
            showModalAlert("Erreur Réseau", "Une erreur est survenue lors de la transmission des directives.", "danger");
        } else {
            alert("Erreur réseau lors de l'enregistrement.");
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }
}

// 2. Déclenchement d'Éclosion Spontanée Immédiate
async function triggerSpontaneousSpawn() {
    const doSpawn = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'spawn_spontaneous_village');
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const v = data.village;
                const msg = `Un nouveau domaine a fait son apparition dans l'archipel !<br><br>
                     <i class="fa-solid fa-chess-rook text-danger me-1"></i><strong>${v.planet_name}</strong><br>
                     <i class="fa-solid fa-user me-1"></i>Seigneur : <strong>${v.username}</strong> (${v.faction})<br>
                     <i class="fa-solid fa-location-dot me-1"></i>Coordonnées : <strong>[${v.x} : ${v.y}]</strong> &bull; Quadrant : <strong>${v.quadrant}</strong><br>
                     ${v.is_new_daimyo ? '<i class="fa-solid fa-crown text-warning me-1"></i><em>Nouveau Daimyō fondateur</em>' : '<i class="fa-solid fa-chess-rook text-danger me-1"></i><em>Extension territoriale provinciale</em>'}<br><br>
                     <a href="/?page=map&x=${v.x}&y=${v.y}" target="_blank" class="btn btn-sm btn-primary"><i class="fa-solid fa-map-location-dot me-1"></i>Explorer sur la Carte</a>`;
                
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Nouveau Village PNJ Éclos", msg, "success");
                } else {
                    alert("Nouveau village PNJ éclos aux coordonnées [" + v.x + " : " + v.y + "] !");
                }
                setTimeout(() => location.reload(), 2500);
            } else {
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Éclosion Impossible", data.error || data.message, "warning");
                } else {
                    alert(data.error || data.message);
                }
            }
        } catch (e) {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Erreur Réseau", "Une erreur est survenue lors de l'éclosion du village.", "danger");
            } else {
                alert("Erreur réseau.");
            }
        }
    };

    if (typeof showModalConfirm === 'function') {
        showModalConfirm(
            "Éclosion Spontanée Immédiate", 
            "Voulez-vous déclencher l'apparition immédiate d'un nouveau village PNJ ? L'emplacement sera choisi de façon homogène sur la carte afin de combler le quadrant le moins occupé.", 
            doSpawn
        );
    } else if (confirm("Déclencher l'éclosion immédiate d'un fief PNJ ?")) {
        doSpawn();
    }
}

// 3. Forcer un Cycle IA Immédiat
async function runBotCycle() {
    const doCycle = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'run_bot_cycle');
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const rep = data.report;
                let colonisationsTxt = "";
                if (rep.colonies_founded && rep.colonies_founded.length > 0) {
                    colonisationsTxt = "<br><br><i class=\"fa-solid fa-chess-rook text-danger me-1\"></i><strong>Nouvelles Colonies Fondées :</strong><br>" + 
                        rep.colonies_founded.map(c => `• <strong>${c.bot}</strong> : ${c.colony_name} aux coordonnées ${c.coords}`).join('<br>');
                }

                const msg = `Simulation effectuée avec succès pour <strong>${rep.bots_processed}</strong> bot(s) :<br>
                     • Mines améliorées : <strong>+${rep.mines_upgraded}</strong><br>
                     • Bâtiments fortifiés : <strong>+${rep.buildings_upgraded}</strong><br>
                     • Soldats enrôlés : <strong>+${rep.troops_trained}</strong>
                     ${colonisationsTxt}`;
                
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Cycle IA Terminé", msg, "success");
                } else {
                    alert("Cycle IA terminé avec succès !");
                }
                setTimeout(() => location.reload(), 2500);
            } else {
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Info IA", data.message || data.error, "warning");
                } else {
                    alert(data.message || data.error);
                }
            }
        } catch (e) {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Erreur Réseau", "Impossible de déclencher le cycle IA.", "danger");
            } else {
                alert("Erreur réseau.");
            }
        }
    };

    if (typeof showModalConfirm === 'function') {
        showModalConfirm(
            "Déclencher un Cycle IA", 
            "Voulez-vous forcer l'exécution immédiate d'un cycle de simulation pour tous les bots ? Ils amélioreront leurs mines, entraîneront des unités et coloniseront de nouveaux mondes.", 
            doCycle
        );
    } else if (confirm("Forcer l'exécution immédiate d'un cycle IA ?")) {
        doCycle();
    }
}

// 4. Génération de Daimyōs Preset Multi-Clans
async function generatePresetBots() {
    const doGenerate = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'generate_bots');
            formData.append('count', 3);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const count = data.created_count;
                const names = (data.created_bots || []).join(', ');
                const msg = `${count} nouveau(x) Daimyō(s) établi(s) dans le royaume : <strong>${names}</strong>.`;
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Daimyōs Déployés", msg, "success");
                } else {
                    alert("Daimyōs créés : " + names);
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Erreur", data.error, "danger");
                } else {
                    alert(data.error);
                }
            }
        } catch (e) {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Erreur", "Une erreur est survenue lors de la création des Daimyōs.", "danger");
            } else {
                alert("Erreur lors de la génération.");
            }
        }
    };

    if (typeof showModalConfirm === 'function') {
        showModalConfirm(
            "Génération de Daimyōs", 
            "Créer automatiquement un groupe de 3 nouveaux Daimyōs IA (Clan Oda, Clan Takeda et Clan Tokugawa) avec leurs châteaux capitaux et garnisons ?", 
            doGenerate
        );
    } else if (confirm("Générer 3 nouveaux Daimyōs IA historiques ?")) {
        doGenerate();
    }
}

// 5. Purge d'un Bot
async function deleteBot(botId, botName) {
    const doDelete = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_bot');
            formData.append('bot_id', botId);
            const res = await fetch('/api/admin.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Purge Effectuée", data.message, "success");
                } else {
                    alert("Bot purgé avec succès !");
                }
                setTimeout(() => location.reload(), 1000);
            } else {
                if (typeof showModalAlert === 'function') {
                    showModalAlert("Erreur", data.error, "danger");
                } else {
                    alert(data.error);
                }
            }
        } catch (e) {
            if (typeof showModalAlert === 'function') {
                showModalAlert("Erreur", "Une erreur est survenue lors de la purge.", "danger");
            } else {
                alert("Erreur réseau.");
            }
        }
    };

    if (typeof showModalConfirm === 'function') {
        showModalConfirm(
            "Purger le Fief du Bot", 
            `Êtes-vous certain de vouloir purger le Daimyō <strong>${botName}</strong> ainsi que l'ensemble de ses fiefs et armées ?`, 
            doDelete
        );
    } else if (confirm(`Purger définitivement le bot ${botName} ?`)) {
        doDelete();
    }
}

// 6. Pagination & Filtrage Dynamique Tabler
let currentBotsPage = 1;
let botsPerPage = 10;
let filteredBotRows = [];

function buildPaginationUI(containerEl, currentPage, totalPages, onPageClickFnName) {
    if (!containerEl) return;
    containerEl.innerHTML = '';
    if (totalPages <= 1) return;

    // Bouton Précédent
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="${onPageClickFnName}(${currentPage - 1})" aria-label="Précédent">&lsaquo;</a>`;
    containerEl.appendChild(prevLi);

    // Fenêtre des numéros de pages
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="${onPageClickFnName}(1)">1</a>`;
        containerEl.appendChild(firstLi);

        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">&hellip;</span>`;
            containerEl.appendChild(ellipsisLi);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const numLi = document.createElement('li');
        numLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
        numLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="${onPageClickFnName}(${i})">${i}</a>`;
        containerEl.appendChild(numLi);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">&hellip;</span>`;
            containerEl.appendChild(ellipsisLi);
        }

        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="${onPageClickFnName}(${totalPages})">${totalPages}</a>`;
        containerEl.appendChild(lastLi);
    }

    // Bouton Suivant
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="${onPageClickFnName}(${currentPage + 1})" aria-label="Suivant">&rsaquo;</a>`;
    containerEl.appendChild(nextLi);
}

function initBotsPagination() {
    const rows = Array.from(document.querySelectorAll('.bot-table-row'));
    filteredBotRows = rows;
    if (rows.length === 0) return;
    
    const select = document.getElementById('botsPerPageSelect');
    if (select) botsPerPage = parseInt(select.value, 10) || 10;
    renderBotsPage(1);
}

function changeBotsPerPage(newVal) {
    botsPerPage = parseInt(newVal, 10) || 10;
    currentBotsPage = 1;
    renderBotsPage(currentBotsPage);
}

function renderBotsPage(page) {
    const totalBots = filteredBotRows.length;
    const allRows = Array.from(document.querySelectorAll('.bot-table-row'));
    allRows.forEach(r => r.style.display = 'none');

    if (totalBots === 0) {
        const startEl = document.getElementById('botsPaginationStart');
        const endEl = document.getElementById('botsPaginationEnd');
        const totalEl = document.getElementById('botsPaginationTotal');
        if (startEl) startEl.textContent = 0;
        if (endEl) endEl.textContent = 0;
        if (totalEl) totalEl.textContent = 0;
        const ul = document.getElementById('botsPaginationList');
        if (ul) ul.innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(totalBots / botsPerPage) || 1;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentBotsPage = page;

    const startIndex = (page - 1) * botsPerPage;
    const endIndex = Math.min(startIndex + botsPerPage, totalBots);

    for (let i = startIndex; i < endIndex; i++) {
        if (filteredBotRows[i]) {
            filteredBotRows[i].style.display = '';
        }
    }

    const startEl = document.getElementById('botsPaginationStart');
    const endEl = document.getElementById('botsPaginationEnd');
    const totalEl = document.getElementById('botsPaginationTotal');
    if (startEl) startEl.textContent = startIndex + 1;
    if (endEl) endEl.textContent = endIndex;
    if (totalEl) totalEl.textContent = totalBots;

    const ul = document.getElementById('botsPaginationList');
    buildPaginationUI(ul, page, totalPages, 'renderBotsPage');
}

function filterBotsTable() {
    const q = (document.getElementById('botSearchInput')?.value || '').toLowerCase().trim();
    const allRows = Array.from(document.querySelectorAll('.bot-table-row'));
    
    if (!q) {
        filteredBotRows = allRows;
    } else {
        filteredBotRows = allRows.filter(r => {
            const data = (r.getAttribute('data-search') || '').toLowerCase();
            return data.includes(q);
        });
    }
    renderBotsPage(1);
}

document.addEventListener('DOMContentLoaded', () => {
    initBotsPagination();
});
</script>

