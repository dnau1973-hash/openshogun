<?php
/**
 * Vue Partielle Admin : Configuration du Service de Messagerie & Transporteur SMTP
 * Interface Tabler.io native sans alert/confirm JavaScript
 */
require_once __DIR__ . '/../../core/MailService.php';
require_once __DIR__ . '/../../core/Auth.php';

$mailService = MailService::getInstance();
$mailCfg = $mailService->getConfig();
$adminUser = Auth::getCurrentUser();
$adminEmail = $adminUser['email'] ?? 'admin@domaine.local';
?>

<div class="row row-cards">
    <!-- Colonne Principale : Paramétrage du Transporteur -->
    <div class="col-lg-8">
        <div class="card mb-4 border">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title text-primary d-flex align-items-center gap-2 m-0">
                        <span>✉️</span> Service de Messagerie &amp; Transporteur d'E-mails
                    </h3>
                    <div class="text-secondary small mt-1">
                        Configurez l'expédition des e-mails pour l'activation des nouveaux Daimyōs, les notifications féodales et alertes impériales.
                    </div>
                </div>
                <span class="badge bg-primary-lt font-monospace text-uppercase" id="badge-mail-driver">
                    <?= htmlspecialchars($mailCfg['driver']) ?>
                </span>
            </div>

            <form id="form-mail-config" onsubmit="handleSaveMailConfig(event)">
                <div class="card-body">

                    <!-- Feedback Alert -->
                    <div id="mail-settings-alert" class="alert d-none mb-3 alert-dismissible" role="alert">
                        <div id="mail-settings-alert-text"></div>
                        <button type="button" class="btn-close" onclick="document.getElementById('mail-settings-alert').classList.add('d-none');"></button>
                    </div>

                    <!-- 1. Sélection du Transporteur -->
                    <div class="mb-4">
                        <label class="form-label fw-bold required">Mode d'Expédition des Messages</label>
                        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column flex-sm-row gap-2">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="driver" value="mail" class="form-selectgroup-input" <?= ($mailCfg['driver'] === 'mail') ? 'checked' : '' ?> onchange="toggleSmtpFields(this.value)">
                                <span class="form-selectgroup-label d-flex align-items-center p-3">
                                    <span class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </span>
                                    <span class="form-selectgroup-label-content">
                                        <span class="font-weight-medium d-block text-dark fw-bold">📮 Fonction mail() Locale</span>
                                        <span class="text-secondary small">Utilise le service sendmail/Postfix de l'hôte Linux. Aucune authentification requise.</span>
                                    </span>
                                </span>
                            </label>

                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="driver" value="smtp" class="form-selectgroup-input" <?= ($mailCfg['driver'] === 'smtp') ? 'checked' : '' ?> onchange="toggleSmtpFields(this.value)">
                                <span class="form-selectgroup-label d-flex align-items-center p-3">
                                    <span class="me-3">
                                        <span class="form-selectgroup-check"></span>
                                    </span>
                                    <span class="form-selectgroup-label-content">
                                        <span class="font-weight-medium d-block text-dark fw-bold">⚡ Serveur SMTP Distant</span>
                                        <span class="text-secondary small">Recommandé en production (Gmail, Infomaniak, Brevo, Mailgun, OVH) avec chiffrement TLS/SSL.</span>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Bloc Paramètres SMTP (repliable si driver == mail) -->
                    <div id="smtp-settings-block" style="display: <?= ($mailCfg['driver'] === 'smtp') ? 'block' : 'none' ?>;" class="p-3 bg-light rounded border mb-4">
                        <h4 class="text-dark fw-bold mb-3 d-flex align-items-center gap-2">
                            <span>🔐</span> Paramètres du Relais SMTP
                        </h4>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label required">Serveur Hôte SMTP</label>
                                <input type="text" name="host" class="form-control font-monospace" placeholder="ex: smtp.gmail.com ou mail.mondomaine.fr" value="<?= htmlspecialchars($mailCfg['host']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Port SMTP</label>
                                <input type="number" name="port" class="form-control font-monospace" placeholder="587" min="1" max="65535" value="<?= (int)$mailCfg['port'] ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Protocole de Chiffrement</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" <?= ($mailCfg['encryption'] === 'tls') ? 'selected' : '' ?>>STARTTLS (Port standard 587)</option>
                                    <option value="ssl" <?= ($mailCfg['encryption'] === 'ssl') ? 'selected' : '' ?>>SSL / TLS Direct (Port 465)</option>
                                    <option value="none" <?= ($mailCfg['encryption'] === 'none') ? 'selected' : '' ?>>Aucun chiffrement (Port 25)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Identifiant / Nom d'utilisateur</label>
                                <input type="text" name="username" class="form-control" placeholder="ex: contact@mondomaine.fr" autocomplete="off" value="<?= htmlspecialchars($mailCfg['username']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mot de passe SMTP</label>
                                <input type="password" name="password" class="form-control" placeholder="<?= $mailCfg['password_masked'] ?: 'Saisir le mot de passe' ?>" autocomplete="new-password">
                                <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Chiffré en AES-256 dans la base de données.</small>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Expéditeur & Validation des Joueurs -->
                    <h4 class="text-dark fw-bold mb-3 d-flex align-items-center gap-2">
                        <span>🪶</span> Identité de l'Expéditeur Impérial
                    </h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label required">Adresse E-mail d'Expédition (From)</label>
                            <input type="email" name="from_address" class="form-control font-monospace" placeholder="noreply@openshogun.com" value="<?= htmlspecialchars($mailCfg['from_address']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Nom Affiché de l'Expéditeur</label>
                            <input type="text" name="from_name" class="form-control" placeholder="OpenShogun &mdash; Le Shōgunat" value="<?= htmlspecialchars($mailCfg['from_name']) ?>" required>
                        </div>
                    </div>

                    <!-- 4. Règle Métier d'Activation -->
                    <div class="form-check form-switch p-3 bg-light rounded border mb-2">
                        <input class="form-check-input ms-0 me-3" type="checkbox" id="require_verification" name="require_verification" value="1" <?= $mailCfg['require_verification'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_verification">
                            <strong class="d-block text-dark">Exiger la confirmation obligatoire de l'e-mail à l'inscription</strong>
                            <span class="text-secondary small">
                                Si coché, tout nouveau joueur voit son compte placé en attente (<code>is_active = 0</code>) et reçoit un jeton cryptographique valable 24 heures. La connexion reste bloquée tant que le lien n'est pas validé.
                            </span>
                        </label>
                    </div>

                </div>

                <div class="card-footer bg-light d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" id="btn-save-mail-config">
                        <span>💾</span> Enregistrer la Configuration de Messagerie
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Colonne Latérale : Outil de Test & Diagnostic Immédiat -->
    <div class="col-lg-4">
        <div class="card mb-4 border">
            <div class="card-header bg-light">
                <h3 class="card-title text-indigo d-flex align-items-center gap-2 m-0">
                    <span>🚀</span> Diagnostic &amp; Test d'Expédition
                </h3>
            </div>
            <div class="card-body">
                <p class="text-secondary small mb-3">
                    Envoyez immédiatement un message de test au format HTML féodal pour vérifier la connectivité réseau, le handshake TLS et la délivrabilité.
                </p>

                <form id="form-test-email" onsubmit="handleSendTestEmail(event)">
                    <div class="mb-3">
                        <label class="form-label required">Adresse E-mail Destinataire</label>
                        <input type="email" id="test-email-target" class="form-control" required placeholder="admin@domaine.com" value="<?= htmlspecialchars($adminEmail) ?>">
                        <small class="text-muted d-block mt-1">Prérempli avec l'adresse du compte administrateur connecté.</small>
                    </div>

                    <button type="submit" class="btn btn-indigo w-100 d-flex align-items-center justify-content-center gap-2" id="btn-send-test-email">
                        <span>📤</span> Lancer l'Envoi de Test
                    </button>
                </form>

                <!-- Zone de Diagnostic en Temps Réel -->
                <div id="test-email-result-box" class="mt-3 p-3 rounded small font-monospace d-none"></div>
            </div>
        </div>

        <!-- Note de Sécurité & Protocoles -->
        <div class="card border border-info-subtle bg-info-lt">
            <div class="card-body p-3">
                <h4 class="text-info fw-bold mb-1 d-flex align-items-center gap-2">
                    <span>🛡️</span> Sécurité &amp; Conformité RFC
                </h4>
                <p class="small text-secondary m-0" style="line-height: 1.5;">
                    Ce moteur implémente une communication SMTP native via sockets PHP sécurisés (RFC 5321 / 821) sans dépendre d'aucune bibliothèque externe lourde. Les mots de passe sont scellés par chiffrement <code>AES-256-CBC</code> et les jetons d'activation sont hachés en <code>SHA-256</code> en base de données.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSmtpFields(driverValue) {
    const smtpBlock = document.getElementById('smtp-settings-block');
    const badge = document.getElementById('badge-mail-driver');
    if (smtpBlock) {
        smtpBlock.style.display = (driverValue === 'smtp') ? 'block' : 'none';
    }
    if (badge) {
        badge.textContent = driverValue.toUpperCase();
    }
}

async function handleSaveMailConfig(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btn-save-mail-config');
    const alertBox = document.getElementById('mail-settings-alert');
    const alertText = document.getElementById('mail-settings-alert-text');

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Enregistrement...`;

    try {
        const formData = new FormData(form);
        formData.append('action', 'save_mail_settings');

        const res = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        alertBox.className = `alert alert-${data.success ? 'success' : 'danger'} alert-dismissible mb-3`;
        alertText.innerHTML = data.success ? `✅ <strong>Succès :</strong> ${data.message}` : `❌ <strong>Erreur :</strong> ${data.error || 'Échec de la sauvegarde'}`;
        alertBox.classList.remove('d-none');

    } catch (err) {
        alertBox.className = 'alert alert-danger alert-dismissible mb-3';
        alertText.innerHTML = `❌ <strong>Erreur réseau :</strong> ${err.message}`;
        alertBox.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function handleSendTestEmail(e) {
    e.preventDefault();
    const targetEmail = document.getElementById('test-email-target').value;
    const btn = document.getElementById('btn-send-test-email');
    const resBox = document.getElementById('test-email-result-box');

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Connexion & Expédition...`;

    resBox.classList.remove('d-none', 'bg-success-lt', 'bg-danger-lt', 'text-success', 'text-danger');
    resBox.classList.add('bg-light', 'text-muted');
    resBox.innerHTML = `⏳ Négociation du transporteur en cours vers ${targetEmail}...`;

    try {
        const formData = new FormData();
        formData.append('action', 'send_test_email');
        formData.append('test_email', targetEmail);

        const res = await fetch('/api/admin.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        resBox.classList.remove('bg-light', 'text-muted');
        if (data.success) {
            resBox.classList.add('bg-success-lt', 'text-success');
            resBox.innerHTML = `✔ <strong>Succès :</strong> ${data.message}`;
        } else {
            resBox.classList.add('bg-danger-lt', 'text-danger');
            resBox.innerHTML = `✗ <strong>Échec du test :</strong><br>${data.error || 'Erreur inconnue.'}`;
        }
    } catch (err) {
        resBox.classList.remove('bg-light', 'text-muted');
        resBox.classList.add('bg-danger-lt', 'text-danger');
        resBox.innerHTML = `✗ <strong>Erreur réseau :</strong> ${err.message}`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>
