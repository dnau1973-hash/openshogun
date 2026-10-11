<?php
/**
 * Modale Interactive : Chroniques Féodales du Sengoku (Événement Hebdomadaire)
 * OpenShogun - Récit historique immersif, progression séquentielle et récompenses.
 */

require_once __DIR__ . '/../../core/WeeklyEventEngine.php';

$weeklyEngine = $weeklyEngine ?? new WeeklyEventEngine();
$activeEvent = $activeEvent ?? ($weeklyEngine->getActiveEvent((int)($user['id'] ?? 0), (int)($planet['id'] ?? 0)));

$hasActiveEvent = !empty($activeEvent);
if (!$hasActiveEvent) {
    $activeEvent = [
        'id' => 0,
        'title' => "Chroniques Féodales du Sengoku",
        'jp_title' => "戦国年代記",
        'era_year' => "Époque Sengoku",
        'description' => "Aucune chronique n'est active pour le moment. Les scribes et historiens du Shōgunat préparent la prochaine saga historique. Revenez très bientôt pour relever de nouveaux défis féodaux !",
        'image_url' => "/public/assets/buildings/hq.jpg",
        'seconds_remaining' => 0,
        'tasks' => [],
        'reward_final_gold' => 0,
        'reward_final_honor' => 0,
        'reward_final_seal_days' => 0,
        'reward_final_title' => ''
    ];
}

$pStatus = $activeEvent['player_status'] ?? [
    'completed_tasks'  => 0,
    'claimed_tasks'    => 0,
    'total_tasks'      => 0,
    'progress_percent' => 0,
    'all_completed'    => false,
    'final_claimed'    => false,
    'tasks'            => []
];

$tasks = !empty($pStatus['tasks']) ? $pStatus['tasks'] : ($activeEvent['tasks'] ?? []);
$secRemaining = (int)($activeEvent['seconds_remaining'] ?? 0);
?>

<style>
/* Style Parchemin Féodal & Chronique du Sengoku */
.weekly-chronicle-modal .modal-content {
    background: #0f172a;
    color: #f8fafc;
    border: 1px solid rgba(234, 179, 8, 0.35);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8), 0 0 25px rgba(234, 179, 8, 0.15);
    border-radius: 12px;
    overflow: hidden;
}

.weekly-chronicle-hero {
    position: relative;
    height: 190px;
    background-size: cover;
    background-position: center;
    border-bottom: 2px solid rgba(234, 179, 8, 0.4);
}

.weekly-chronicle-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.4) 0%, rgba(15, 23, 42, 0.95) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem 1.5rem;
}

.weekly-chronicle-story-box {
    background: rgba(30, 41, 59, 0.75);
    border-left: 4px solid #eab308;
    padding: 1rem 1.25rem;
    border-radius: 6px;
    font-size: 0.88rem;
    line-height: 1.55;
    color: #cbd5e1;
    font-style: italic;
}

.weekly-task-card {
    background: rgba(30, 41, 59, 0.6);
    border: 1px solid rgba(71, 85, 105, 0.5);
    border-radius: 8px;
    padding: 0.85rem 1rem;
    transition: all 0.2s ease;
}

.weekly-task-card.is-completed {
    border-color: rgba(34, 197, 94, 0.5);
    background: rgba(34, 197, 94, 0.08);
}

.weekly-task-card.is-claimed {
    border-color: rgba(148, 163, 184, 0.2);
    background: rgba(15, 23, 42, 0.4);
    opacity: 0.85;
}

.reward-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.73rem;
    font-weight: 600;
    font-family: monospace;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.pulse-gold-btn {
    animation: pulseGold 2s infinite;
}

@keyframes pulseGold {
    0% { box-shadow: 0 0 0 0 rgba(234, 179, 8, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(234, 179, 8, 0); }
    100% { box-shadow: 0 0 0 0 rgba(234, 179, 8, 0); }
}
</style>

<!-- MODALE D'ÉVÉNEMENT HEBDOMADAIRE (TABLER) -->
<div class="modal fade weekly-chronicle-modal" id="weeklyChronicleModal" tabindex="-1" aria-hidden="true" onclick="if(event.target === this) window.closeWeeklyChronicleModal();">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <!-- En-tête visuelle de l'époque -->
            <div class="weekly-chronicle-hero" style="background-image: url('<?= htmlspecialchars($activeEvent['image_url']) ?>');">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" onclick="window.closeWeeklyChronicleModal();" data-bs-dismiss="modal" aria-label="Fermer" style="z-index: 10;"></button>
                <div class="weekly-chronicle-hero-overlay">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                        <span class="badge bg-warning text-dark fw-bold font-monospace text-uppercase" style="letter-spacing: 0.5px;">
                            <i class="fa-solid fa-scroll me-1"></i>Chronique Hebdomadaire
                        </span>
                        <div class="badge bg-dark border border-warning text-warning font-monospace py-1 px-2" id="weeklyEventCountdown" data-seconds="<?= $secRemaining ?>">
                            <i class="fa-regular fa-clock me-1"></i><span id="weeklyEventCountdownText">--:--:--</span>
                        </div>
                    </div>
                    <h2 class="h2 mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        <span><?= htmlspecialchars($activeEvent['title']) ?></span>
                        <?php if (!empty($activeEvent['jp_title'])): ?>
                            <small class="text-warning font-monospace fs-4 fw-normal opacity-75 d-none d-md-inline"><?= htmlspecialchars($activeEvent['jp_title']) ?></small>
                        <?php endif; ?>
                    </h2>
                    <div class="text-warning-lt font-monospace small mt-0.5">
                        <i class="fa-solid fa-calendar-day me-1"></i><?= htmlspecialchars($activeEvent['era_year']) ?>
                    </div>
                </div>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Récit Historique Immersif -->
                <div class="weekly-chronicle-story-box mb-4 shadow-sm">
                    <div class="d-flex align-items-center gap-2 mb-1 text-warning fw-bold font-monospace" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-feather-pointed"></i>CONTEXTE HISTORIQUE DU SENGOKU
                    </div>
                    <p class="mb-0 text-light"><?= nl2br(htmlspecialchars($activeEvent['description'])) ?></p>
                </div>

                <!-- Jauge de Progression Globale -->
                <div class="d-flex justify-content-between align-items-center mb-1 text-secondary" style="font-size: 0.8rem;">
                    <span class="fw-bold font-monospace text-uppercase"><i class="fa-solid fa-bars-progress me-1 text-warning"></i>Progression de la Chronique</span>
                    <span class="font-monospace fw-bold text-warning" id="weeklyGlobalProgressText">
                        <?= (int)$pStatus['completed_tasks'] ?> / <?= (int)$pStatus['total_tasks'] ?> Actes accomplis (<?= (int)$pStatus['progress_percent'] ?>%)
                    </span>
                </div>
                <div class="progress mb-4" style="height: 8px; background: rgba(255,255,255,0.1);">
                    <div class="progress-bar bg-warning" id="weeklyGlobalProgressBar" role="progressbar" style="width: <?= (int)$pStatus['progress_percent'] ?>%;"></div>
                </div>

                <!-- Liste des Actes Narratifs (Tâches) -->
                <h4 class="h4 text-white fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check text-warning"></i>
                    <span>Actes &amp; Épreuves à Accomplir</span>
                </h4>

                <div class="d-flex flex-column gap-3 mb-4" id="weeklyTasksList">
                    <?php foreach ($tasks as $idx => $t): 
                        $tId = (int)$t['id'];
                        $isComp = !empty($t['is_completed']);
                        $isClaim = !empty($t['is_claimed']);
                        $curProg = (int)($t['current_progress'] ?? 0);
                        $tarProg = (int)($t['target_amount'] ?? 1);
                        $tPct = ($tarProg > 0) ? min(100, round(($curProg / $tarProg) * 100)) : 100;
                    ?>
                        <div class="weekly-task-card <?= $isClaim ? 'is-claimed' : ($isComp ? 'is-completed' : '') ?>" id="task-card-<?= $tId ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary-lt font-monospace fw-bold" style="font-size:0.7rem;">
                                            Acte <?= $t['step_order'] ?>
                                        </span>
                                        <h5 class="mb-0 fw-bold text-white fs-4"><?= htmlspecialchars($t['title']) ?></h5>
                                    </div>
                                    <p class="text-secondary mb-0 mt-1" style="font-size:0.84rem;"><?= htmlspecialchars($t['description']) ?></p>
                                </div>
                                <div>
                                    <?php if ($isClaim): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary font-monospace" disabled>
                                            <i class="fa-solid fa-check me-1 text-success"></i>Accompli
                                        </button>
                                    <?php elseif ($isComp): ?>
                                        <button type="button" class="btn btn-sm btn-success font-monospace fw-bold pulse-gold-btn" onclick="claimWeeklyTask(<?= $tId ?>)">
                                            <i class="fa-solid fa-gift me-1"></i>Réclamer
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-dark border border-secondary text-secondary font-monospace py-1 px-2">
                                            <i class="fa-solid fa-hourglass-half me-1"></i>En cours
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Jauge d'Acte -->
                            <div class="d-flex justify-content-between align-items-center mb-1 font-monospace" style="font-size:0.75rem;">
                                <span class="text-secondary">Avancement :</span>
                                <span class="<?= $isComp ? 'text-success' : 'text-warning' ?> fw-bold">
                                    <?= number_format($curProg) ?> / <?= number_format($tarProg) ?> (<?= $tPct ?>%)
                                </span>
                            </div>
                            <div class="progress mb-2" style="height: 5px; background: rgba(255,255,255,0.08);">
                                <div class="progress-bar <?= $isComp ? 'bg-success' : 'bg-warning' ?>" role="progressbar" style="width: <?= $tPct ?>%;"></div>
                            </div>

                            <!-- Récompenses de l'Acte -->
                            <div class="d-flex align-items-center gap-1.5 flex-wrap mt-1">
                                <span class="text-muted small me-1"><i class="fa-solid fa-gift text-warning me-0.5"></i>Gain :</span>
                                <?php if (!empty($t['reward_metal'])): ?>
                                    <span class="reward-chip text-success"><i class="fa-solid fa-tree me-0.5"></i>+<?= number_format((int)$t['reward_metal']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['reward_crystal'])): ?>
                                    <span class="reward-chip text-secondary"><i class="fa-solid fa-mountain me-0.5"></i>+<?= number_format((int)$t['reward_crystal']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['reward_deuterium'])): ?>
                                    <span class="reward-chip text-warning"><i class="fa-solid fa-wheat-awn me-0.5"></i>+<?= number_format((int)$t['reward_deuterium']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['reward_gold'])): ?>
                                    <span class="reward-chip text-warning border-warning"><i class="fa-solid fa-coins me-0.5"></i>+<?= number_format((int)$t['reward_gold']) ?> Or</span>
                                <?php endif; ?>
                                <?php if (!empty($t['reward_honor'])): ?>
                                    <span class="reward-chip text-danger"><i class="fa-solid fa-award me-0.5"></i>+<?= number_format((int)$t['reward_honor']) ?> Honneur</span>
                                <?php endif; ?>
                                <?php if (!empty($t['reward_item'])): 
                                    [$itKey, $itQty] = explode(':', $t['reward_item'], 2);
                                ?>
                                    <span class="reward-chip text-teal border-teal"><i class="fa-solid fa-box-open me-0.5"></i>+<?= (int)$itQty ?> <?= htmlspecialchars($itKey) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grande Récompense Finale de Clôture -->
                <div class="p-3 rounded border border-warning" style="background: radial-gradient(circle at top right, rgba(234, 179, 8, 0.15), rgba(15, 23, 42, 0.85));">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="badge bg-warning text-dark font-monospace fw-bold mb-1">
                                <i class="fa-solid fa-crown me-1"></i>GRANDE RÉCOMPENSE DE CLÔTURE
                            </div>
                            <h4 class="mb-1 text-white fw-bold">Parachèvement de la Chronique</h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                <?php if (!empty($activeEvent['reward_final_gold'])): ?>
                                    <span class="reward-chip text-warning border-warning fs-4"><i class="fa-solid fa-coins me-1"></i>+<?= (int)$activeEvent['reward_final_gold'] ?> Or</span>
                                <?php endif; ?>
                                <?php if (!empty($activeEvent['reward_final_honor'])): ?>
                                    <span class="reward-chip text-danger fs-4"><i class="fa-solid fa-award me-1"></i>+<?= (int)$activeEvent['reward_final_honor'] ?> Honneur</span>
                                <?php endif; ?>
                                <?php if (!empty($activeEvent['reward_final_seal_days'])): ?>
                                    <span class="reward-chip text-info fs-4"><i class="fa-solid fa-stamp me-1"></i>+<?= (int)$activeEvent['reward_final_seal_days'] ?> Jours Sceau Impérial</span>
                                <?php endif; ?>
                                <?php if (!empty($activeEvent['reward_final_title'])): ?>
                                    <span class="reward-chip text-teal fs-4"><i class="fa-solid fa-signature me-1"></i>Titre : « <?= htmlspecialchars($activeEvent['reward_final_title']) ?> »</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if (!empty($pStatus['final_claimed'])): ?>
                                <button type="button" class="btn btn-secondary font-monospace" disabled>
                                    <i class="fa-solid fa-check-double me-1 text-success"></i>Chronique Honorée
                                </button>
                            <?php elseif (!empty($pStatus['all_completed'])): ?>
                                <button type="button" class="btn btn-warning fw-bold font-monospace pulse-gold-btn px-4 py-2" onclick="claimWeeklyFinal(<?= (int)$activeEvent['id'] ?>)">
                                    <i class="fa-solid fa-trophy me-1"></i>Clôturer &amp; Recevoir le Tribut
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary font-monospace" disabled title="Accomplissez tous les actes pour débloquer">
                                    <i class="fa-solid fa-lock me-1"></i>Verrouillé (<?= (int)$pStatus['completed_tasks'] ?>/<?= (int)$pStatus['total_tasks'] ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Fonctions universelles d'ouverture et de fermeture de la modale des Chroniques
window.openWeeklyChronicleModal = function() {
    const modalEl = document.getElementById('weeklyChronicleModal');
    if (!modalEl) {
        console.warn("Élément #weeklyChronicleModal introuvable dans le DOM.");
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.show();
    } else {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        let backdrop = document.getElementById('weekly-modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'weekly-modal-backdrop';
            backdrop.className = 'modal-backdrop fade show';
            backdrop.onclick = window.closeWeeklyChronicleModal;
            document.body.appendChild(backdrop);
        }
    }
};

window.closeWeeklyChronicleModal = function() {
    const modalEl = document.getElementById('weeklyChronicleModal');
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const inst = bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();
    }
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    const bd = document.getElementById('weekly-modal-backdrop');
    if (bd) bd.remove();
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
};

(function() {
    // Initialisation des écouteurs de clics pour tous les boutons ouvrant le rouleau
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-bs-target="#weeklyChronicleModal"], button[title*="Chronique"], button[title*="chronique"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.openWeeklyChronicleModal();
            });
        });
    });

    // Compte à rebours de l'événement hebdomadaire
    let cdEl = document.getElementById('weeklyEventCountdown');
    if (cdEl) {
        let sec = parseInt(cdEl.dataset.seconds || 0, 10);
        function tickWeekly() {
            if (sec <= 0) {
                const txtEl = document.getElementById('weeklyEventCountdownText');
                if (txtEl) txtEl.textContent = "Terminé !";
                return;
            }
            const d = Math.floor(sec / 86400);
            const h = Math.floor((sec % 86400) / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            const pad = n => n.toString().padStart(2, '0');
            const txtEl = document.getElementById('weeklyEventCountdownText');
            if (txtEl) txtEl.textContent = `${d}j ${pad(h)}:${pad(m)}:${pad(s)}`;
            sec--;
        }
        tickWeekly();
        setInterval(tickWeekly, 1000);
    }
})();

// Réclamation d'un acte narratif
window.claimWeeklyTask = function(taskId) {
    fetch('/api/weekly_events.php?action=claim_task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'task_id=' + encodeURIComponent(taskId)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Erreur lors de la réclamation.');
            return;
        }
        if (typeof showToast === 'function') {
            showToast(data.message || 'Récompense perçue !', 'success');
        } else {
            alert(data.message || 'Récompense perçue !');
        }
        window.location.reload();
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau lors de la réclamation.');
    });
};

// Réclamation finale de clôture
window.claimWeeklyFinal = function(eventId) {
    fetch('/api/weekly_events.php?action=claim_final', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + encodeURIComponent(eventId)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.error || 'Erreur lors de la réclamation finale.');
            return;
        }
        if (typeof showToast === 'function') {
            showToast(data.message || 'Chronique parachevée avec honneur !', 'success');
        } else {
            alert(data.message || 'Chronique parachevée avec honneur !');
        }
        window.location.reload();
    })
    .catch(err => {
        console.error(err);
        alert('Erreur réseau.');
    });
};
</script>

