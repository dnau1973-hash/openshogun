<?php
/**
 * Game Studio - Game Elevate Designer : Vitesses & Équilibrage du Jeu
 * Fichier : views/gamestudio/elevate_speed.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../core/GameConfig.php';
$gameSettings = GameConfig::load();
?>

<!-- EN-TÊTE DE SECTION -->
<div class="row align-items-center mb-3 g-2">
    <div class="col">
        <div class="page-pretitle text-secondary">Game Elevate Designer &bull; Équilibrage &amp; Systèmes de Jeu</div>
        <h2 class="page-title d-flex align-items-center gap-2">
            <span class="text-warning"><i class="fa-solid fa-bolt"></i></span>
            <span>Vitesses &amp; Équilibrage du Monde Féodal</span>
            <span class="badge bg-warning-lt text-dark font-monospace">Constantes &amp; Moteur Chantiers</span>
        </h2>
    </div>
</div>

<div id="dev-alert" class="alert d-none mb-3 shadow-sm alert-dismissible" role="alert">
    <div id="dev-alert-content"></div>
    <button type="button" class="btn-close" onclick="document.getElementById('dev-alert').classList.add('d-none');"></button>
</div>

<?php
require __DIR__ . '/../studio/game-elevate-designer/speed-balancing.php';
?>

<script>
function applyDevSpeedPreset(gSpeed, rSpeed, fSpeed) {
    const gInput = document.getElementById('dev_game_speed_input');
    const gRange = document.getElementById('dev_game_speed_range');
    const bG = document.getElementById('dev_badge_game_speed');
    if (gInput && gRange) {
        gInput.value = gSpeed;
        gRange.value = gSpeed;
        if (bG) bG.textContent = 'x' + gSpeed;
    }

    const rInput = document.getElementById('dev_resource_speed_input');
    const rRange = document.getElementById('dev_resource_speed_range');
    const bR = document.getElementById('dev_badge_resource_speed');
    if (rInput && rRange) {
        rInput.value = rSpeed;
        rRange.value = rSpeed;
        if (bR) bR.textContent = 'x' + rSpeed;
    }

    const fInput = document.getElementById('dev_fleet_speed_input');
    const fRange = document.getElementById('dev_fleet_speed_range');
    const bF = document.getElementById('dev_badge_fleet_speed');
    if (fInput && fRange) {
        fInput.value = fSpeed;
        fRange.value = fSpeed;
        if (bF) bF.textContent = 'x' + fSpeed;
    }
}

async function saveDevGameSettings(event) {
    if (event) event.preventDefault();
    const gForm = document.getElementById('devGameSettingsForm');
    const formData = gForm ? new FormData(gForm) : new FormData();
    formData.append('action', 'save_game_settings');

    const submitBtn = gForm ? gForm.querySelector('button[type="submit"]') : null;
    const oldBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...';
    }

    try {
        const res = await fetch('/api/dev_team.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showDevAlert('success', `<i class="fa-solid fa-floppy-disk text-success me-1"></i><strong>Succès :</strong> ${data.message}`);
        } else {
            showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur :</strong> ${data.error || "Impossible d'enregistrer les paramètres."}`);
        }
    } catch (err) {
        showDevAlert('danger', `<i class="fa-solid fa-circle-xmark text-danger me-1"></i><strong>Erreur réseau :</strong> ${err.message}`);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = oldBtnText;
        }
    }
}
</script>
