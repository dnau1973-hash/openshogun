<?php
/**
 * Vue Joueur : Assistance, Signalement de Bugs & Boîte à Idées (OpenShogun)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/SupportEngine.php';

$auth = new Auth();
if (!Auth::check()) {
    header('Location: /');
    exit;
}

$user = $auth->getCurrentUser();
$planet = $auth->getCurrentPlanet();
$userId = (int)$user['id'];

$supportEngine = new SupportEngine();
$myTickets = $supportEngine->getUserTickets($userId);

// Tab active : 'new' (formulaire) ou 'history' (mes tickets)
$activeTab = $_GET['tab'] ?? (empty($myTickets) ? 'new' : 'history');
?>

<div class="support-container" style="max-width: 1100px; margin: 0 auto; padding-bottom: 3rem;">

    <!-- En-tête Héroïque Assistance & Idées -->
    <div class="card" style="margin-bottom: 2rem; border-top: 5px solid #0891b2; background: var(--bg-surface, #fdfbf7); box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        <div style="padding: 1.75rem 2rem; background: linear-gradient(135deg, rgba(8, 145, 178, 0.08) 0%, rgba(253, 251, 247, 0.98) 80%);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0891b2; background: rgba(8,145,178,0.1); padding: 2px 8px; border-radius: 4px;">
                            🏛️ Assistance & Boîte à Idées
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">&bull; Dialogue Direct Joueurs & Développeurs</span>
                    </div>
                    <h1 style="font-size: 1.8rem; margin: 0 0 0.5rem 0; color: var(--text-main); display: flex; align-items: center; gap: 0.6rem;">
                        <span>📮</span> Remontées de Dysfonctionnements & Suggestions
                    </h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; max-width: 800px;">
                        Vous constatez un comportement inattendu, une anomalie ou un calcul incorrect ? Ou vous avez une idée novatrice pour enrichir l'expérience féodale d'OpenShogun ? Transmettez directement vos retours à l'équipe de développement.
                    </p>
                </div>

                <div style="text-align: right; background: var(--bg-ink, #ede5d5); padding: 0.75rem 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Mes Remontées</div>
                    <div style="font-size: 1.3rem; font-weight: 900; color: #0891b2;">
                        <?= count($myTickets) ?> Ticket<?= count($myTickets) > 1 ? 's' : '' ?>
                    </div>
                </div>
            </div>

            <!-- Onglets de Bascule -->
            <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn <?= ($activeTab === 'new') ? 'btn-primary' : 'btn-secondary' ?>" onclick="switchSupportTab('new')" id="btn-tab-new" style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700;">
                    <span>📝</span> Soumettre une Demande
                </button>
                <button type="button" class="btn <?= ($activeTab === 'history') ? 'btn-primary' : 'btn-secondary' ?>" onclick="switchSupportTab('history')" id="btn-tab-history" style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700;">
                    <span>📋</span> Suivi de mes Demandes (<?= count($myTickets) ?>)
                </button>
            </div>
        </div>
    </div>

    <!-- ==================================================================
         ONGLET 1 : FORMULAIRE DE SOUMISSION DE NOUVELLE REMONTÉE
         ================================================================== -->
    <div id="section-support-new" style="display: <?= ($activeTab === 'new') ? 'block' : 'none' ?>;">
        <div class="card" style="background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <h2 style="margin: 0 0 1.5rem 0; font-size: 1.3rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <span>✍️</span> Rédiger un Signalement ou une Suggestion
            </h2>

            <form id="supportTicketForm" onsubmit="submitSupportTicket(event)">
                <!-- 1. Sélection du Type : Bug ou Suggestion -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.6rem; color: var(--text-main);">
                        1. Nature de votre Demande :
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                        <!-- Option Bug -->
                        <label id="card-type-bug" style="cursor: pointer; display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem 1.25rem; border: 2px solid #ef4444; border-radius: 10px; background: rgba(239, 68, 68, 0.05); transition: all 0.2s ease;">
                            <input type="radio" name="type" value="bug" checked onchange="toggleTypeFields()" style="margin-top: 4px; accent-color: #ef4444;">
                            <div>
                                <strong style="color: #dc2626; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span>🪲</span> Dysfonctionnement / Bug
                                </strong>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                                    Une erreur technique, une action qui ne s'exécute pas, un souci d'affichage ou un calcul incohérent.
                                </p>
                            </div>
                        </label>

                        <!-- Option Suggestion -->
                        <label id="card-type-suggestion" style="cursor: pointer; display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem 1.25rem; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-ink, #ede5d5); transition: all 0.2s ease;">
                            <input type="radio" name="type" value="suggestion" onchange="toggleTypeFields()" style="margin-top: 4px; accent-color: #f59e0b;">
                            <div>
                                <strong style="color: #b45309; font-size: 1rem; display: flex; align-items: center; gap: 0.4rem;">
                                    <span>💡</span> Suggestion / Boîte à Idées
                                </strong>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                                    Une proposition d'amélioration ergonomique, une idée de nouvelle unité, d'édifice ou de quête.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Domaine / Catégorie & Sévérité -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    <div>
                        <label for="ticket_category" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-main);">
                            2. Secteur Concerné :
                        </label>
                        <select name="category" id="ticket_category" class="form-control" style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);" required>
                            <?php foreach (SupportEngine::CATEGORIES as $k => $label): ?>
                                <option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="group-severity">
                        <label for="ticket_severity" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-main);">
                            Degré de Gêne / Sévérité :
                        </label>
                        <select name="severity" id="ticket_severity" class="form-control" style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);">
                            <option value="low">🟢 Faible (détail esthétique ou mineur)</option>
                            <option value="medium" selected>🟡 Moyen (anomalie notable mais non bloquante)</option>
                            <option value="high">🟠 Élevé (fonctionnalité majeure perturbée)</option>
                            <option value="critical">🔴 Critique / Bloquant (jeu inaccessible ou action bloquée)</option>
                        </select>
                    </div>
                </div>

                <!-- 3. Titre / Sujet du Signalement -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="ticket_title" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-main);">
                        3. Titre Concis de votre Demande :
                    </label>
                    <input type="text" name="title" id="ticket_title" class="form-control" placeholder="Ex: Erreur lors de l'annulation d'un chantier sur le slot #20" minlength="4" maxlength="150" required style="width: 100%; padding: 0.65rem 0.9rem; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.95rem;">
                </div>

                <!-- 4. Description Détaillée -->
                <div style="margin-bottom: 1.75rem;">
                    <label for="ticket_description" style="display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.4rem; color: var(--text-main);">
                        4. Description Détaillée :
                    </label>
                    <textarea name="description" id="ticket_description" rows="6" class="form-control" placeholder="Pour un bug : Décrivez ce que vous faisiez, ce qui s'est produit et le résultat attendu.&#10;Pour une suggestion : Présentez votre idée, son intérêt pour le jeu et comment vous l'imaginez." minlength="10" required style="width: 100%; padding: 0.8rem; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.9rem; line-height: 1.5;"></textarea>
                    <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.3rem; display: block;">
                        💡 Les coordonnées de votre fief actuel [<?= $planet['coord_x'] ?? 0 ?> : <?= $planet['coord_y'] ?? 0 ?>] et votre clan seront automatiquement joints à la demande pour faciliter l'analyse.
                    </small>
                </div>

                <!-- Bouton de Soumission -->
                <div style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
                    <button type="submit" id="submitTicketBtn" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🚀</span> Transmettre la Demande
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================================================================
         ONGLET 2 : SUIVI ET HISTORIQUE DES DEMANDES DU JOUEUR
         ================================================================== -->
    <div id="section-support-history" style="display: <?= ($activeTab === 'history') ? 'block' : 'none' ?>;">
        <?php if (empty($myTickets)): ?>
            <div class="card" style="text-align: center; padding: 3rem 1.5rem; background: var(--bg-surface, #fdfbf7); border: 1px dashed var(--border-color); border-radius: 12px;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                <h3 style="margin: 0 0 0.5rem 0; color: var(--text-main);">Aucune demande enregistrée</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 500px; margin: 0 auto 1.5rem auto;">
                    Vous n'avez pas encore soumis de dysfonctionnement ou de suggestion. Dès que vous en transmettez une, vous pourrez suivre ici l'état d'examen et les retours des développeurs.
                </p>
                <button type="button" class="btn btn-primary" onclick="switchSupportTab('new')">
                    📝 Déposer ma Première Remontée
                </button>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <?php foreach ($myTickets as $t): 
                    $isBug = ($t['type'] === 'bug');
                    $statusConfig = match($t['status']) {
                        'pending' => ['label' => 'En attente d\'examen', 'color' => '#eab308', 'bg' => 'rgba(234, 179, 8, 0.1)', 'icon' => '⏳'],
                        'in_progress' => ['label' => 'En cours d\'analyse', 'color' => '#2563eb', 'bg' => 'rgba(37, 99, 235, 0.1)', 'icon' => '🔍'],
                        'resolved' => ['label' => 'Résolu / Corrigé', 'color' => '#16a34a', 'bg' => 'rgba(22, 163, 74, 0.1)', 'icon' => '✅'],
                        'planned' => ['label' => 'Retenu (Future Version)', 'color' => '#7c3aed', 'bg' => 'rgba(124, 58, 237, 0.1)', 'icon' => '📌'],
                        'closed' => ['label' => 'Classé sans suite', 'color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)', 'icon' => '✖️'],
                        default => ['label' => $t['status'], 'color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.1)', 'icon' => '•']
                    };
                    $categoryLabel = SupportEngine::CATEGORIES[$t['category']] ?? $t['category'];
                ?>
                    <div class="card" style="margin: 0; background: var(--bg-surface, #fdfbf7); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); background: var(--bg-ink, #ede5d5); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; background: <?= $isBug ? 'rgba(220,38,38,0.1)' : 'rgba(245,158,11,0.1)' ?>; color: <?= $isBug ? '#dc2626' : '#b45309' ?>; border: 1px solid <?= $isBug ? '#dc262633' : '#b4530933' ?>;">
                                    <?= $isBug ? '🪲 Dysfonctionnement' : '💡 Suggestion' ?>
                                </span>
                                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                                    #<?= $t['id'] ?> &bull; <?= htmlspecialchars($categoryLabel) ?>
                                </span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 0.8rem; font-weight: 700; color: <?= $statusConfig['color'] ?>; background: <?= $statusConfig['bg'] ?>; border: 1px solid <?= $statusConfig['color'] ?>44; padding: 3px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <span><?= $statusConfig['icon'] ?></span>
                                    <span><?= $statusConfig['label'] ?></span>
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?= date('d/m/Y H:i', $t['created_at']) ?>
                                </span>
                            </div>
                        </div>

                        <div style="padding: 1.5rem;">
                            <h3 style="margin: 0 0 0.75rem 0; font-size: 1.15rem; color: var(--text-main);">
                                <?= htmlspecialchars($t['title']) ?>
                            </h3>
                            <div style="font-size: 0.9rem; line-height: 1.6; color: var(--text-main); white-space: pre-line; background: var(--bg-surface); border: 1px solid var(--border-color); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                <?= htmlspecialchars($t['description']) ?>
                            </div>

                            <!-- Réponse Officielle de l'Administration si présente -->
                            <?php if (!empty($t['admin_response'])): ?>
                                <div style="background: rgba(8, 145, 178, 0.06); border-left: 4px solid #0891b2; padding: 1.25rem; border-radius: 8px; margin-top: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                        <strong style="color: #0891b2; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem;">
                                            <span>🛡️</span> Réponse Officielle des Développeurs / Shogunat :
                                        </strong>
                                        <?php if (!empty($t['responded_at'])): ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                <?= date('d/m/Y H:i', $t['responded_at']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.88rem; line-height: 1.6; color: var(--text-main); white-space: pre-line;">
                                        <?= htmlspecialchars($t['admin_response']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-style: italic; display: flex; align-items: center; gap: 0.4rem;">
                                    <span>⏳</span> Votre demande est dans la file de révision des intendants. Une réponse vous sera communiquée ici et par missive en jeu dès son examen.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function switchSupportTab(tabKey) {
    const secNew = document.getElementById('section-support-new');
    const secHist = document.getElementById('section-support-history');
    const btnNew = document.getElementById('btn-tab-new');
    const btnHist = document.getElementById('btn-tab-history');

    if (tabKey === 'new') {
        secNew.style.display = 'block';
        secHist.style.display = 'none';
        btnNew.className = 'btn btn-primary';
        btnHist.className = 'btn btn-secondary';
    } else {
        secNew.style.display = 'none';
        secHist.style.display = 'block';
        btnNew.className = 'btn btn-secondary';
        btnHist.className = 'btn btn-primary';
    }
}

function toggleTypeFields() {
    const isBug = document.querySelector('input[name="type"]:checked').value === 'bug';
    const groupSeverity = document.getElementById('group-severity');
    const cardBug = document.getElementById('card-type-bug');
    const cardSugg = document.getElementById('card-type-suggestion');

    if (isBug) {
        groupSeverity.style.display = 'block';
        cardBug.style.borderColor = '#ef4444';
        cardBug.style.background = 'rgba(239, 68, 68, 0.05)';
        cardSugg.style.borderColor = 'var(--border-color)';
        cardSugg.style.background = 'var(--bg-ink, #ede5d5)';
    } else {
        groupSeverity.style.display = 'none';
        cardBug.style.borderColor = 'var(--border-color)';
        cardBug.style.background = 'var(--bg-ink, #ede5d5)';
        cardSugg.style.borderColor = '#f59e0b';
        cardSugg.style.background = 'rgba(245, 158, 11, 0.05)';
    }
}

async function submitSupportTicket(event) {
    event.preventDefault();
    const btn = document.getElementById('submitTicketBtn');
    btn.disabled = true;
    btn.textContent = 'Transmission en cours...';

    const form = document.getElementById('supportTicketForm');
    const formData = new FormData(form);
    formData.append('action', 'create_ticket');

    try {
        const res = await fetch('/api/support.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message || "Votre demande a été transmise avec succès !");
            window.location.href = '?page=support&tab=history';
        } else {
            alert("Erreur : " + (data.error || "Impossible d'enregistrer la demande."));
            btn.disabled = false;
            btn.innerHTML = '<span>🚀</span> Transmettre la Demande';
        }
    } catch (e) {
        alert("Erreur réseau lors de l'envoi de la demande.");
        btn.disabled = false;
        btn.innerHTML = '<span>🚀</span> Transmettre la Demande';
    }
}
</script>

