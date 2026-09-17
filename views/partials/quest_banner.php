<?php
/**
 * Bannière Didacticiel & Quêtes du Daimyō (OpenShogun)
 * Affichée au sommet des vues clés pour accompagner le joueur dans son ascension
 */
require_once __DIR__ . '/../../core/QuestEngine.php';

if (!isset($user) || !isset($planet)) {
    return;
}

$questEngine = new QuestEngine();
$questSummary = $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']);
$activeQuest = $questSummary['active_quest'];
?>

<div id="questBannerContainer" class="quest-banner-wrapper" style="margin-bottom: 1.5rem;">
    <?php if ($questSummary['all_completed']): ?>
        <!-- Bannière de Maîtrise Complète -->
        <div class="card quest-banner-card quest-completed-card" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.15) 0%, rgba(20, 22, 30, 0.95) 100%); border: 1px solid #eab308; border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 4px 20px rgba(234, 179, 8, 0.15);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 2.2rem; filter: drop-shadow(0 0 10px rgba(234, 179, 8, 0.6));">👑</div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <h3 style="margin: 0; font-size: 1.15rem; color: #facc15; font-weight: 800;">
                                Didacticiel Accompli &bull; Maître du Shogunat
                            </h3>
                            <span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; font-size: 0.75rem; padding: 0.15rem 0.5rem; font-weight: 700;">
                                12 / 12 QUÊTES
                            </span>
                        </div>
                        <p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; color: #cbd5e1;">
                            Félicitations noble Daimyō ! Vous maîtrisez désormais tous les rouages du Terroir, de la Cité et des Armées provinciales. Le destin du Japon repose entre vos mains.
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="openQuestModal()" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;">
                        📜 Codex des Quêtes
                    </button>
                    <button type="button" onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.4rem 0.85rem; background: #eab308; border-color: #ca8a04; color: #000; font-weight: 800;">
                        🎖️ Voir ma Médaille
                    </button>
                </div>
            </div>
        </div>

    <?php elseif ($activeQuest): ?>
        <!-- Bannière Quête Active -->
        <?php 
            $isClaimable = $activeQuest['is_claimable'];
            $cardBorderColor = $isClaimable ? '#22c55e' : 'rgba(220, 38, 38, 0.6)';
            $cardBg = $isClaimable 
                ? 'linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(15, 23, 42, 0.95) 100%)' 
                : 'linear-gradient(135deg, rgba(220, 38, 38, 0.12) 0%, rgba(17, 24, 39, 0.95) 100%)';
        ?>
        <div class="card quest-banner-card" style="background: <?= $cardBg ?>; border: 1px solid <?= $cardBorderColor ?>; border-radius: 10px; padding: 1rem 1.25rem; box-shadow: 0 4px 20px <?= $isClaimable ? 'rgba(34, 197, 94, 0.2)' : 'rgba(0,0,0,0.4)' ?>; transition: all 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                
                <!-- Bloc de gauche : Mentor + Objectif -->
                <div style="display: flex; gap: 1rem; flex: 1; min-width: 290px;">
                    <div style="text-align: center;">
                        <div style="font-size: 2.3rem; line-height: 1; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));">
                            <?= $activeQuest['icon'] ?>
                        </div>
                        <span style="display: block; font-size: 0.65rem; color: #94a3b8; margin-top: 0.35rem; white-space: nowrap;">
                            Étape <?= $activeQuest['order'] ?>/<?= $questSummary['total_quests'] ?>
                        </span>
                    </div>

                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.75rem; font-weight: 800; color: #dc2626; text-transform: uppercase; letter-spacing: 0.5px;">
                                📜 DIDACTICIEL DU DAIMYŌ &bull; ÉTAPE <?= $activeQuest['order'] ?>
                            </span>
                            <?php if ($isClaimable): ?>
                                <span class="badge pulse-badge" style="background: #22c55e; color: #fff; font-size: 0.7rem; padding: 0.15rem 0.5rem; font-weight: 800; border-radius: 20px; animation: pulse 1.5s infinite;">
                                    ✨ OBJECTIF ATTEINT !
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background: rgba(255,255,255,0.08); color: #cbd5e1; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 4px;">
                                    En cours
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 style="margin: 0 0 0.35rem 0; font-size: 1.15rem; color: #fff; font-weight: 800;">
                            <?= htmlspecialchars($activeQuest['title']) ?> : <span style="color: <?= $isClaimable ? '#4ade80' : '#facc15' ?>;"><?= htmlspecialchars($activeQuest['objective']) ?></span>
                        </h3>

                        <p style="margin: 0; font-size: 0.85rem; color: #94a3b8; line-height: 1.5; font-style: italic;">
                            &laquo; <?= htmlspecialchars($activeQuest['lore']) ?> &raquo;
                            <span style="color: #cbd5e1; font-style: normal; font-weight: 600; font-size: 0.8rem;">— <?= htmlspecialchars($activeQuest['mentor_name']) ?></span>
                        </p>

                        <!-- Aperçu des récompenses -->
                        <div style="display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap; margin-top: 0.6rem; font-size: 0.8rem;">
                            <span style="color: #e2e8f0; font-weight: 700;">Récompense :</span>
                            <?php if (!empty($activeQuest['rewards']['metal'])): ?>
                                <span style="color: var(--res-metal, #60a5fa);">🪵 +<?= number_format($activeQuest['rewards']['metal']) ?> Bois</span>
                            <?php endif; ?>
                            <?php if (!empty($activeQuest['rewards']['crystal'])): ?>
                                <span style="color: var(--res-crystal, #e2e8f0);">🪨 +<?= number_format($activeQuest['rewards']['crystal']) ?> Pierre</span>
                            <?php endif; ?>
                            <?php if (!empty($activeQuest['rewards']['deuterium'])): ?>
                                <span style="color: var(--res-deut, #4ade80);">🌾 +<?= number_format($activeQuest['rewards']['deuterium']) ?> Riz</span>
                            <?php endif; ?>
                            <?php if (!empty($activeQuest['rewards']['points'])): ?>
                                <span style="color: #facc15;">⛩️ +<?= $activeQuest['rewards']['points'] ?> Honneur</span>
                            <?php endif; ?>
                            <?php if (!empty($activeQuest['rewards']['bonus_units'])): ?>
                                <span style="color: #f87171; font-weight: 700;">⚔️ +<?= $activeQuest['rewards']['bonus_units'] ?> Recrues d'élite</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bloc de droite : Bouton d'action ou de Réclamation -->
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem; justify-content: center; align-self: center;">
                    <?php if ($isClaimable): ?>
                        <button type="button" 
                                onclick="claimQuestReward('<?= $activeQuest['key'] ?>')" 
                                class="btn btn-primary pulse-btn" 
                                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-color: #047857; color: #fff; font-weight: 800; font-size: 0.95rem; padding: 0.6rem 1.4rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4); cursor: pointer; border-radius: 8px;">
                            ✨ Réclamer ma Récompense
                        </button>
                    <?php else: ?>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <?php if (str_starts_with($activeQuest['action_url'], 'javascript:')): ?>
                                <button type="button" onclick="<?= htmlspecialchars(substr($activeQuest['action_url'], 11)) ?>" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.45rem 1rem; font-weight: 700;">
                                    <?= htmlspecialchars($activeQuest['action_label']) ?> &rarr;
                                </button>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($activeQuest['action_url']) ?>" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.45rem 1rem; font-weight: 700; text-decoration: none;">
                                    <?= htmlspecialchars($activeQuest['action_label']) ?> &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <button type="button" onclick="openQuestModal()" style="background: transparent; border: none; color: #94a3b8; font-size: 0.8rem; cursor: pointer; text-decoration: underline;">
                        📜 Voir toutes les 12 quêtes (<?= $questSummary['claimed_count'] ?>/12)
                    </button>
                </div>

            </div>

            <!-- Barre de progression globale du Didacticiel -->
            <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 0.75rem; color: #94a3b8; white-space: nowrap;">Progression du Shogunat :</span>
                <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden;">
                    <div style="height: 100%; width: <?= $questSummary['overall_percent'] ?>%; background: linear-gradient(90deg, #dc2626 0%, #22c55e 100%); transition: width 0.4s ease;"></div>
                </div>
                <span style="font-size: 0.75rem; color: #cbd5e1; font-weight: 700; font-family: monospace;"><?= $questSummary['overall_percent'] ?>%</span>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
    100% { transform: scale(1); opacity: 1; }
}
.pulse-badge {
    animation: pulse 1.5s infinite ease-in-out;
}
.pulse-btn {
    animation: pulse 2s infinite ease-in-out;
}
</style>

