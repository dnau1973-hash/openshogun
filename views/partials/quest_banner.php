<?php
/**
 * Widget Synthétique du Codex des Quêtes Féodales (OpenShogun)
 * Présentation moderne avec vignette illustrée de l'étape active,
 * ouvrant la modale illustrée haute définition au format Craft/Mineur.
 */
require_once __DIR__ . '/../../core/QuestEngine.php';

if (!isset($user) || !isset($planet)) {
    return;
}

$questEngine = new QuestEngine();
$questSummary = $questEngine->getPlayerQuestsStatus((int)$user['id'], (int)$planet['id']);

// Masqué si toutes les quêtes ont été accomplies
if (!empty($questSummary['all_completed']) || empty($questSummary['active_quest'])) {
    return;
}

$activeQuest = $questSummary['active_quest'];
$isClaimable = !empty($activeQuest['is_claimable']);
$questImgUrl = !empty($activeQuest['image_url']) ? $activeQuest['image_url'] : '/public/assets/quests/quest_01_wood_field.jpg';
?>

<div id="questBannerContainer" class="card border shadow-sm mb-0" style="border-radius: 8px; overflow: hidden; background: #ffffff;">
    <div class="card-body p-3">
        <!-- En-tête compacte : étape et catégorie -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-uppercase fw-bold text-danger" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                ⛩️ Étape <?= (int)$activeQuest['order'] ?> / <?= (int)($questSummary['total_quests'] ?? 12) ?> &bull; <?= htmlspecialchars($activeQuest['category'] ?? 'Terroir') ?>
            </span>
            <?php if ($isClaimable): ?>
                <span class="badge bg-success text-white font-monospace" style="font-size: 0.65rem;">
                    <i class="fa-solid fa-sparkles me-1"></i>Prête !
                </span>
            <?php else: ?>
                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;">
                    <i class="fa-solid fa-hourglass-half me-1"></i>En cours
                </span>
            <?php endif; ?>
        </div>

        <!-- Corps compact avec vignette illustrée -->
        <div class="d-flex align-items-center gap-3 cursor-pointer" onclick="openCodexQuestModal('<?= htmlspecialchars($activeQuest['key']) ?>')" style="cursor: pointer;">
            <div style="width: 72px; height: 72px; min-width: 72px; border-radius: 8px; overflow: hidden; position: relative; border: 1px solid #e2e8f0; background: #0f172a;" class="shadow-sm">
                <img src="<?= htmlspecialchars($questImgUrl) ?>" alt="<?= htmlspecialchars($activeQuest['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php if ($isClaimable): ?>
                    <div style="position: absolute; inset: 0; background: rgba(22, 163, 74, 0.25); display: flex; align-items: center; justify-content: center;">
                        <span class="badge bg-success p-1 rounded-circle"><i class="fa-solid fa-check text-white"></i></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-grow-1 min-w-0">
                <h4 class="m-0 text-dark fw-bold text-truncate" style="font-size: 0.92rem;" title="<?= htmlspecialchars($activeQuest['title']) ?>">
                    <?= $activeQuest['icon'] ?> <?= htmlspecialchars($activeQuest['title']) ?>
                </h4>
                <div class="text-secondary small text-truncate mt-1" style="font-size: 0.75rem; line-height: 1.3;" title="<?= htmlspecialchars($activeQuest['objective']) ?>">
                    <?= htmlspecialchars($activeQuest['objective']) ?>
                </div>
            </div>
        </div>

        <!-- Bouton d'action pour réclamer ou consulter -->
        <div class="mt-2 pt-1">
            <?php if ($isClaimable): ?>
                <button type="button" 
                        onclick="openCodexQuestModal('<?= htmlspecialchars($activeQuest['key']) ?>')" 
                        class="btn btn-sm btn-success w-100 fw-bold shadow-sm pulse-btn d-flex align-items-center justify-content-center gap-2 py-2">
                    <i class="fa-solid fa-gift"></i>
                    <span>Objectif Atteint &bull; Réclamer</span>
                </button>
            <?php else: ?>
                <button type="button" 
                        onclick="openCodexQuestModal('<?= htmlspecialchars($activeQuest['key']) ?>')" 
                        class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-between py-1 px-3 text-secondary">
                    <span class="small"><i class="fa-solid fa-book-open me-1 text-primary"></i>Détails &amp; Décret</span>
                    <i class="fa-solid fa-chevron-right small text-muted"></i>
                </button>
            <?php endif; ?>
        </div>

        <!-- Jauge d'avancement du Codex -->
        <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between gap-2" style="font-size: 0.7rem; color: #64748b;">
            <div class="d-flex align-items-center gap-1 flex-grow-1">
                <div class="progress flex-grow-1" style="height: 5px; background: #e2e8f0; border-radius: 3px;">
                    <div class="progress-bar bg-danger" style="width: <?= (int)$questSummary['overall_percent'] ?>%;"></div>
                </div>
                <span class="font-monospace fw-bold text-dark"><?= (int)$questSummary['overall_percent'] ?>%</span>
            </div>
            <a href="javascript:void(0)" onclick="openCodexQuestModal()" class="text-secondary text-decoration-none hover-primary fw-semibold">
                Codex (<?= (int)$questSummary['claimed_count'] ?>/<?= (int)$questSummary['total_quests'] ?>)
            </a>
        </div>
    </div>
</div>
