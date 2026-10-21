<?php
/**
 * Bannière Compacte d'Événement Hebdomadaire (Bandeau ou Pill d'accès rapide)
 * OpenShogun - Invite le joueur à consulter sa progression dans la chronique féodale.
 */

require_once __DIR__ . '/../../core/WeeklyEventEngine.php';

$weeklyEngine = $weeklyEngine ?? new WeeklyEventEngine();
$activeEvent = $activeEvent ?? ($weeklyEngine->getActiveEvent((int)($user['id'] ?? 0), (int)($planet['id'] ?? 0)));

if (!$activeEvent) {
    return;
}

$pStatus = $activeEvent['player_status'] ?? [
    'completed_tasks'  => 0,
    'claimed_tasks'    => 0,
    'total_tasks'      => 0,
    'progress_percent' => 0,
    'all_completed'    => false,
    'final_claimed'    => false
];

$hasClaimable = false;
if (!empty($pStatus['tasks'])) {
    foreach ($pStatus['tasks'] as $t) {
        if (!empty($t['is_completed']) && empty($t['is_claimed'])) {
            $hasClaimable = true;
            break;
        }
    }
}
if (!empty($pStatus['all_completed']) && empty($pStatus['final_claimed'])) {
    $hasClaimable = true;
}
?>

<div class="weekly-event-banner-wrap mb-3">
    <div class="card shadow-sm border-warning" style="background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md rounded bg-warning text-dark flex-shrink-0 shadow-sm" style="font-size:1.25rem;">
                    <i class="fa-solid fa-scroll"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-warning-lt text-warning fw-bold font-monospace" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            CHRONIQUE HEBDOMADAIRE • <?= htmlspecialchars($activeEvent['week_label'] ?? ('Semaine ' . date('W'))) ?>
                        </span>
                        <?php if (!empty($activeEvent['period_text'])): ?>
                            <span class="badge bg-dark text-warning border border-warning-subtle font-monospace" style="font-size: 0.68rem;">
                                <i class="fa-regular fa-calendar-days me-1"></i><?= htmlspecialchars($activeEvent['period_text']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="text-secondary font-monospace small">
                            <i class="fa-solid fa-hourglass-start me-0.5"></i><?= htmlspecialchars($activeEvent['era_year']) ?>
                        </span>
                    </div>
                    <h4 class="mb-0 text-white fw-bold d-flex align-items-center gap-2" style="font-size:0.98rem;">
                        <span><?= htmlspecialchars($activeEvent['title']) ?></span>
                        <?php if ($hasClaimable): ?>
                            <span class="badge bg-success text-white fw-bold pulse-gold-btn" style="font-size:0.68rem;">
                                <i class="fa-solid fa-gift me-1"></i>Récompense disponible !
                            </span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="text-end d-none d-md-block me-1 font-monospace" style="font-size: 0.78rem;">
                    <span class="text-secondary">Progression : </span>
                    <strong class="text-warning"><?= (int)$pStatus['completed_tasks'] ?> / <?= (int)$pStatus['total_tasks'] ?> actes</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-warning fw-bold font-monospace d-flex align-items-center gap-1.5 shadow-sm" onclick="if(window.openWeeklyChronicleModal){window.openWeeklyChronicleModal();} return false;" data-bs-toggle="modal" data-bs-target="#weeklyChronicleModal">
                    <i class="fa-solid fa-feather-pointed"></i>
                    <span>Consulter la Chronique</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/weekly_event_modal.php'; ?>
