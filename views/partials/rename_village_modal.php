<?php
/**
 * Modale Universelle Tabler.io : Renommage d'un Fief / Village Féodal
 * Remplace définitivement les boîtes natives prompt() et alert() par une modale Tabler moderne.
 */
$defaultPlanetId = isset($planet['id']) ? (int)$planet['id'] : 0;
$defaultPlanetName = isset($planet['name']) ? (string)$planet['name'] : '';
?>
<div class="modal modal-blur fade" id="modalRenameVillage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius:12px; overflow:hidden;">
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 m-0">
                    <span class="avatar avatar-xs rounded bg-danger-lt text-danger">
                        <i class="fa-solid fa-pen-nib"></i>
                    </span>
                    <span>Renommer le Fief Féodal</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form id="formRenameVillage" onsubmit="event.preventDefault(); submitRenameVillageModal();">
                <input type="hidden" id="renameVillagePlanetId" name="planet_id" value="<?= $defaultPlanetId ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label required fw-bold text-dark" for="renameVillageInput">
                            Nouveau nom du fief ou village
                        </label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="fa-solid fa-torii-gate text-muted"></i>
                            </span>
                            <input type="text" 
                                   id="renameVillageInput" 
                                   name="name" 
                                   class="form-control form-control-lg font-monospace" 
                                   placeholder="Ex: Forteresse de Nagoya, Bourg d'Azuchi..." 
                                   value="<?= htmlspecialchars($defaultPlanetName, ENT_QUOTES, 'UTF-8') ?>"
                                   minlength="2" 
                                   maxlength="40" 
                                   required 
                                   autocomplete="off">
                        </div>
                        <div class="form-hint mt-2 text-muted small">
                            <i class="fa-solid fa-circle-info me-1"></i>Le nom doit comporter entre 2 et 40 caractères. Il sera visible par l'ensemble des Daimyōs sur la carte provinciale et dans les classements.
                        </div>
                    </div>
                    <div id="renameVillageAlert" class="alert alert-danger d-none py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation flex-shrink-0"></i>
                        <span id="renameVillageAlertText"></span>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light py-2 px-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger fw-bold" id="renameVillageSubmitBtn">
                        <i class="fa-solid fa-check me-1"></i>Enregistrer le nom
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Ouvre la modale Tabler.io pour renommer un village
 * @param {number|string|null} planetId
 * @param {string|null} currentName
 */
function openCityRenamePrompt(planetId = null, currentName = null) {
    const modalEl = document.getElementById('modalRenameVillage');
    if (!modalEl) return;

    const idInput = document.getElementById('renameVillagePlanetId');
    const nameInput = document.getElementById('renameVillageInput');
    const alertBox = document.getElementById('renameVillageAlert');

    if (planetId !== null && planetId !== undefined && idInput) {
        idInput.value = planetId;
    }
    if (currentName !== null && currentName !== undefined && nameInput) {
        nameInput.value = currentName;
    }

    if (alertBox) {
        alertBox.classList.add('d-none');
        const alertText = document.getElementById('renameVillageAlertText');
        if (alertText) alertText.textContent = '';
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    } else {
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
    }

    if (nameInput) {
        setTimeout(() => {
            nameInput.focus();
            nameInput.select();
        }, 300);
    }
}

/**
 * Traite la soumission du formulaire de renommage en Ajax Tabler
 */
async function submitRenameVillageModal() {
    const nameInput = document.getElementById('renameVillageInput');
    const idInput = document.getElementById('renameVillagePlanetId');
    const submitBtn = document.getElementById('renameVillageSubmitBtn');
    const alertBox = document.getElementById('renameVillageAlert');
    const alertText = document.getElementById('renameVillageAlertText');

    const newName = nameInput ? nameInput.value.trim() : '';
    const planetId = idInput ? idInput.value : '';

    const showError = (msg) => {
        if (alertText) alertText.textContent = msg;
        if (alertBox) alertBox.classList.remove('d-none');
    };

    if (newName.length < 2 || newName.length > 40) {
        showError("Le nom du fief doit contenir entre 2 et 40 caractères.");
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Enregistrement...';
    }

    try {
        const formData = new FormData();
        formData.append('action', 'rename');
        formData.append('name', newName);
        if (planetId) {
            formData.append('planet_id', planetId);
        }

        const res = await fetch('/api/planet.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            const modalEl = document.getElementById('modalRenameVillage');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
            if (typeof showToast === 'function') {
                showToast(data.message || 'Le fief a été rebaptisé avec succès !', 'success');
            }
            setTimeout(() => window.location.reload(), 900);
        } else {
            showError(data.error || 'Erreur lors du renommage du fief.');
        }
    } catch (e) {
        showError('Erreur de communication avec le serveur féodal.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Enregistrer le nom';
        }
    }
}
</script>

