<?php
/**
 * Widget Didacticiel & Quêtes du Daimyō (OpenShogun)
 * Affiché dans le menu latéral droit pour accompagner le joueur dans son ascension féodale.
 * Présentation identique aux autres cartes (sans dégradé).
 */
require_once __DIR__ . '/../../core/QuestEngine.php';

if (!isset($user) || !isset($planet)) {
    return;
}

$questEngine = new QuestEngine();
$questSummary = $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']);
$activeQuest = $questSummary['active_quest'];
?>

<div id="questBannerContainer" class="card">
    <?php if ($questSummary['all_completed']): ?>
        <!-- Bloc Didacticiel Complété (Style Washi Sobre) -->
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1rem;">
                <span>👑</span> Didacticiel Féodal
            </h3>
            <span class="badge" style="background: rgba(21, 128, 61, 0.12); color: #15803d; border: 1px solid #15803d; font-size: 0.72rem; padding: 0.15rem 0.45rem; font-weight: 700; border-radius: 4px;">
                12 / 12
            </span>
        </div>
        <div class="card-body" style="padding: 1rem;">
            <div style="font-size: 0.85rem; font-weight: 700; color: #15803d; margin-bottom: 0.35rem;">
                🏆 Didacticiel Accompli &bull; Maître du Shogunat
            </div>
            <p style="margin: 0 0 0.85rem 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.45;">
                Félicitations noble Daimyō ! Vous maîtrisez désormais tous les rouages du Terroir, de la Cité et des Armées provinciales. Le destin du Japon repose entre vos mains.
            </p>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" onclick="openQuestModal()" class="btn btn-secondary" style="flex: 1; font-size: 0.8rem; padding: 0.4rem 0.6rem;">
                    📜 Codex
                </button>
                <button type="button" onclick="openPlayerProfileModal(<?= (int)$user['id'] ?>)" class="btn btn-primary" style="flex: 1; font-size: 0.8rem; padding: 0.4rem 0.6rem; background: var(--red-primary, #c2252b); border-color: var(--red-deep, #991b1b); color: #ffffff;">
                    🎖️ Médaille
                </button>
            </div>
        </div>

    <?php elseif ($activeQuest): ?>
        <!-- Bloc Quête Active (Style Washi Sobre sans Dégradé) -->
        <?php $isClaimable = $activeQuest['is_claimable']; ?>
        <div class="card-header">
            <h3 class="card-title" style="font-size: 1rem;">
                <span>📜</span> Didacticiel du Daimyō
            </h3>
            <?php if ($isClaimable): ?>
                <span class="badge" style="background: #15803d; color: #ffffff; font-size: 0.7rem; padding: 0.15rem 0.45rem; font-weight: 800; border-radius: 4px;">
                    ✨ Objectif atteint !
                </span>
            <?php else: ?>
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">
                    Étape <?= $activeQuest['order'] ?> / <?= $questSummary['total_quests'] ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="card-body" style="padding: 1rem;">
            <!-- Mentor & Titre de la quête -->
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.65rem;">
                <div style="font-size: 1.6rem; line-height: 1;"><?= $activeQuest['icon'] ?></div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.88rem; font-weight: 800; color: var(--text-main); line-height: 1.3;">
                        <?= htmlspecialchars($activeQuest['title']) ?>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                        Conseiller : <strong style="color: var(--text-secondary);"><?= htmlspecialchars($activeQuest['mentor_name']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Objectif -->
            <div style="background: rgba(0, 0, 0, 0.03); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.55rem 0.7rem; margin-bottom: 0.65rem;">
                <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.15rem;">
                    Objectif :
                </div>
                <div style="font-size: 0.85rem; font-weight: 700; color: <?= $isClaimable ? '#15803d' : 'var(--text-main)' ?>; line-height: 1.35;">
                    <?= htmlspecialchars($activeQuest['objective']) ?>
                </div>
            </div>

            <!-- Lore / Conseil du mentor -->
            <div style="font-size: 0.76rem; color: var(--text-muted); font-style: italic; margin-bottom: 0.65rem; line-height: 1.35;">
                &laquo; <?= htmlspecialchars($activeQuest['lore']) ?> &raquo;
            </div>

            <!-- Aperçu des récompenses -->
            <div style="background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: 6px; padding: 0.45rem 0.6rem; margin-bottom: 0.75rem; font-size: 0.76rem;">
                <div style="color: var(--text-muted); font-weight: 700; margin-bottom: 0.2rem;">Récompense :</div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem 0.6rem;">
                    <?php if (!empty($activeQuest['rewards']['metal'])): ?>
                        <span style="color: var(--res-metal); font-weight: 600;">🪵 +<?= number_format($activeQuest['rewards']['metal']) ?> Bois</span>
                    <?php endif; ?>
                    <?php if (!empty($activeQuest['rewards']['crystal'])): ?>
                        <span style="color: var(--res-crystal); font-weight: 600;">🪨 +<?= number_format($activeQuest['rewards']['crystal']) ?> Pierre</span>
                    <?php endif; ?>
                    <?php if (!empty($activeQuest['rewards']['deuterium'])): ?>
                        <span style="color: var(--res-deut); font-weight: 600;">🌾 +<?= number_format($activeQuest['rewards']['deuterium']) ?> Riz</span>
                    <?php endif; ?>
                    <?php if (!empty($activeQuest['rewards']['points'])): ?>
                        <span style="color: #b45309; font-weight: 600;">⛩️ +<?= $activeQuest['rewards']['points'] ?> Honneur</span>
                    <?php endif; ?>
                    <?php if (!empty($activeQuest['rewards']['bonus_units'])): ?>
                        <span style="color: var(--red-primary, #c2252b); font-weight: 700;">⚔️ +<?= $activeQuest['rewards']['bonus_units'] ?> Recrues</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bouton d'action ou de Réclamation (sans dégradé) -->
            <?php if ($isClaimable): ?>
                <button type="button" 
                        onclick="claimQuestReward('<?= $activeQuest['key'] ?>')" 
                        class="btn btn-primary" 
                        style="width: 100%; background: #15803d; border-color: #166534; color: #ffffff; font-weight: 800; font-size: 0.85rem; padding: 0.55rem; border-radius: 6px; cursor: pointer; text-align: center; margin-bottom: 0.6rem;">
                    ✨ Réclamer ma Récompense
                </button>
            <?php else: ?>
                <?php if (str_starts_with($activeQuest['action_url'], 'javascript:')): ?>
                    <button type="button" 
                            onclick="<?= htmlspecialchars(substr($activeQuest['action_url'], 11)) ?>" 
                            class="btn btn-primary" 
                            style="width: 100%; font-size: 0.85rem; padding: 0.5rem; font-weight: 700; text-align: center; margin-bottom: 0.6rem;">
                        <?= htmlspecialchars($activeQuest['action_label']) ?> &rarr;
                    </button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($activeQuest['action_url']) ?>" 
                       class="btn btn-primary" 
                       style="display: block; width: 100%; font-size: 0.85rem; padding: 0.5rem; font-weight: 700; text-align: center; text-decoration: none; box-sizing: border-box; margin-bottom: 0.6rem;">
                        <?= htmlspecialchars($activeQuest['action_label']) ?> &rarr;
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Barre de progression sobre (sans dégradé) & Lien vers le Codex -->
            <div style="padding-top: 0.5rem; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                <div style="flex: 1; display: flex; align-items: center; gap: 0.4rem;">
                    <div style="flex: 1; height: 6px; background: var(--border-rice, #e5d8c5); border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; width: <?= $questSummary['overall_percent'] ?>%; background: var(--red-primary, #c2252b);"></div>
                    </div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; font-family: monospace;"><?= $questSummary['overall_percent'] ?>%</span>
                </div>
                <button type="button" onclick="openQuestModal()" style="background: transparent; border: none; color: var(--text-muted); font-size: 0.74rem; cursor: pointer; text-decoration: underline; padding: 0; white-space: nowrap;">
                    📜 Codex (<?= $questSummary['claimed_count'] ?>/12)
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
