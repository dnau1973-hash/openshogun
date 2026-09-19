<?php
/**
 * Assistant d'Installation Web — OpenShogun
 * Déploiement interactif et sécurisé de l'univers féodal
 */

require_once __DIR__ . '/core/InstallEngine.php';

// Traitement AJAX pour tester la connexion MySQL en direct
if (isset($_GET['action']) && $_GET['action'] === 'test_db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $dbname = trim($_POST['db_name'] ?? 'openshogun');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $createDb = !empty($_POST['create_db']);

    $res = InstallEngine::testDatabaseConnection($host, $port, $dbname, $user, $pass, $createDb);
    echo json_encode($res);
    exit;
}

// Vérification de l'état d'installation existant
$isAlreadyInstalled = InstallEngine::isInstalled();
$installError = null;
$installSuccess = false;
$installData = null;

// Traitement de la soumission finale d'installation
if (!$isAlreadyInstalled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_install'])) {
    try {
        $installData = InstallEngine::runInstallation($_POST);
        $installSuccess = true;
    } catch (Exception $e) {
        $installError = $e->getMessage();
    }
}

// Analyse des prérequis
$reqs = InstallEngine::checkRequirements();
$canProceed = $reqs['all_passed'];

// Détection automatique de l'URL du site
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$autoHost = $_SERVER['HTTP_HOST'] ?? 'opengalaxy.local';
$detectedUrl = $protocol . $autoHost;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation & Initialisation — OpenShogun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-deep: #0e1117;
            --bg-card: #161b22;
            --bg-input: #0d1117;
            --gold-primary: #d97706;
            --gold-light: #fbbf24;
            --gold-hover: #b45309;
            --red-shogun: #dc2626;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(217, 119, 6, 0.25);
            --border-focus: #f59e0b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --font-title: 'Cinzel', serif;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-deep);
            background-image: radial-gradient(circle at top center, rgba(217, 119, 6, 0.08), transparent 70%),
                              radial-gradient(circle at bottom center, rgba(220, 38, 38, 0.06), transparent 70%);
            color: var(--text-main);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .installer-container {
            width: 100%;
            max-width: 860px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6), 0 0 30px rgba(217, 119, 6, 0.1);
            overflow: hidden;
        }

        .installer-header {
            background: linear-gradient(180deg, #1f242d 0%, #161b22 100%);
            border-bottom: 2px solid var(--gold-primary);
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .installer-header h1 {
            font-family: var(--font-title);
            font-size: 2.1rem;
            color: var(--gold-light);
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .installer-header p {
            color: var(--text-muted);
            margin-top: 0.4rem;
            font-size: 0.95rem;
        }

        /* Stepper Navigation */
        .stepper {
            display: flex;
            background: rgba(0, 0, 0, 0.35);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.85rem 1.5rem;
            gap: 0.5rem;
            overflow-x: auto;
        }

        .step-item {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .step-item.active {
            background: rgba(217, 119, 6, 0.15);
            color: var(--gold-light);
            border: 1px solid var(--border-color);
        }

        .step-item.completed {
            color: var(--success-color);
        }

        .step-number {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .step-item.active .step-number {
            background: var(--gold-primary);
            color: #000;
            font-weight: 700;
        }

        .step-item.completed .step-number {
            background: var(--success-color);
            color: #000;
        }

        /* Content Area */
        .installer-content {
            padding: 2.2rem;
        }

        .step-section {
            display: none;
        }

        .step-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2.section-title {
            font-family: var(--font-title);
            font-size: 1.4rem;
            color: var(--gold-light);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 0.75rem;
        }

        /* Table of Requirements */
        .req-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .req-table th, .req-table td {
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.9rem;
        }

        .req-table th {
            color: var(--gold-light);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(0, 0, 0, 0.2);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Form Inputs */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-grid.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group.col-span-2 {
            grid-column: span 2;
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .helper-text {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"],
        select {
            background: var(--bg-input);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            padding: 0.75rem 0.9rem;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
            color: var(--text-main);
        }

        .checkbox-label input {
            width: 18px;
            height: 18px;
            accent-color: var(--gold-primary);
        }

        /* Buttons & Actions */
        .installer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 6px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
            color: #1a1505;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(180deg, #fbbf24 0%, #b45309 100%);
            transform: translateY(-1px);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-test {
            background: rgba(217, 119, 6, 0.15);
            color: var(--gold-light);
            border: 1px solid var(--border-color);
        }

        .btn-test:hover {
            background: rgba(217, 119, 6, 0.25);
        }

        /* Alert boxes */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }

        /* Test Result Box */
        #db-test-result {
            display: none;
            margin-top: 1rem;
        }

        /* Installed Screen */
        .locked-card {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .locked-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="installer-container">
    <div class="installer-header">
        <h1><span>⚔️</span> OpenShogun — Assistant d'Installation</h1>
        <p>Déploiement et initialisation de l'univers stratégique féodal</p>
    </div>

    <?php if ($isAlreadyInstalled && !$installSuccess): ?>
        <!-- ÉCRAN DE VERROUILLAGE SI DÉJÀ INSTALLÉ -->
        <div class="installer-content locked-card">
            <div class="locked-icon">🛡️</div>
            <h2 style="font-family: var(--font-title); color: var(--gold-light); font-size: 1.8rem; margin-bottom: 0.75rem;">Système Déjà Installé & Sécurisé</h2>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 1.75rem; line-height: 1.6;">
                OpenShogun est déjà configuré et fonctionnel. Pour des raisons de sécurité, l'assistant d'installation est verrouillé par la présence du fichier <code style="color:var(--gold-light); background:rgba(0,0,0,0.3); padding:2px 6px; border-radius:4px;">config/installed.lock</code>.
            </p>
            <div class="alert alert-info" style="max-width: 640px; margin: 0 auto 2rem; text-align: left;">
                <span>ℹ️</span>
                <div>
                    <strong>Pour réinitialiser ou réinstaller complètement :</strong><br>
                    Supprimez manuellement le fichier <code>config/installed.lock</code> sur votre serveur, puis rechargez cette page.
                </div>
            </div>
            <a href="/" class="btn btn-primary" style="font-size: 1.05rem; padding: 0.85rem 2rem;">
                <span>⛩️</span> Accéder au Domaine de Jeu
            </a>
        </div>
    <?php elseif ($installSuccess): ?>
        <!-- ÉCRAN DE SUCCÈS APRÈS INSTALLATION -->
        <div class="installer-content locked-card">
            <div class="locked-icon">🎉</div>
            <h2 style="font-family: var(--font-title); color: var(--gold-light); font-size: 1.8rem; margin-bottom: 0.75rem;">Félicitations, Seigneur Shogun !</h2>
            <p style="color: var(--text-muted); max-width: 620px; margin: 0 auto 1.5rem; line-height: 1.6;">
                L'univers féodal <strong><?= htmlspecialchars($installData['game_title']) ?></strong> a été déployé avec succès. Les 30 tables de la base de données ont été initialisées, les 12 Donjons Authentiques ont été sanctifiés et votre capitale castrale est prête.
            </p>
            <div class="alert alert-success" style="max-width: 640px; margin: 0 auto 2rem; text-align: left;">
                <span>🏯</span>
                <div>
                    <strong>Récapitulatif de l'Administrateur :</strong><br>
                    • Utilisateur Shogun : <strong><?= htmlspecialchars($installData['admin_user']) ?></strong><br>
                    • Email : <strong><?= htmlspecialchars($installData['admin_email']) ?></strong><br>
                    • Fichier verrouillé : <code>config/installed.lock</code> généré avec succès.
                </div>
            </div>
            <a href="/" class="btn btn-primary" style="font-size: 1.05rem; padding: 0.85rem 2.2rem;">
                <span>⛩️</span> Entrer dans l'Univers & Se Connecter
            </a>
        </div>
    <?php else: ?>
        <!-- ASSISTANT PAS-À-PAS EN 4 ÉTAPES -->
        <div class="stepper">
            <div class="step-item active" id="badge-step-1"><span class="step-number">1</span> Prérequis</div>
            <div class="step-item" id="badge-step-2"><span class="step-number">2</span> Base de Données</div>
            <div class="step-item" id="badge-step-3"><span class="step-number">3</span> Univers & Shogun</div>
            <div class="step-item" id="badge-step-4"><span class="step-number">4</span> Déploiement</div>
        </div>

        <form method="POST" id="install-form" class="installer-content">
            <input type="hidden" name="do_install" value="1">

            <?php if ($installError): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <div><strong>Erreur lors de l'installation :</strong><br><?= htmlspecialchars($installError) ?></div>
                </div>
            <?php endif; ?>

            <!-- ÉTAPE 1 : PRÉREQUIS SYSTÈME -->
            <div class="step-section active" id="section-step-1">
                <h2 class="section-title"><span>📋</span> Vérification des Prérequis du Serveur</h2>
                
                <?php if (!$canProceed): ?>
                    <div class="alert alert-error">
                        <span>❌</span>
                        <div>Certains prérequis indispensables ne sont pas satisfaits. Veuillez corriger les points marqués en rouge avant de poursuivre l'installation.</div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <span>✅</span>
                        <div>Tous les prérequis minimaux du serveur sont validés ! Vous pouvez poursuivre la configuration.</div>
                    </div>
                <?php endif; ?>

                <table class="req-table">
                    <thead>
                        <tr>
                            <th>Composant Analysé</th>
                            <th>Requis</th>
                            <th>État Détecté</th>
                            <th>Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reqs['checks'] as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['required']) ?></td>
                                <td><?= htmlspecialchars($c['current']) ?></td>
                                <td>
                                    <?php if ($c['passed']): ?>
                                        <span class="badge badge-success">✔ Conforme</span>
                                    <?php else: ?>
                                        <span class="badge badge-error"><?= $c['critical'] ? '✖ Requis' : 'Avertissement' ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="installer-footer">
                    <div></div>
                    <button type="button" class="btn btn-primary" onclick="goToStep(2)" <?= $canProceed ? '' : 'disabled' ?>>
                        Suivant : Base de Données ➔
                    </button>
                </div>
            </div>

            <!-- ÉTAPE 2 : CONFIGURATION BASE DE DONNÉES -->
            <div class="step-section" id="section-step-2">
                <h2 class="section-title"><span>🗄️</span> Connexion à la Base de Données MySQL / MariaDB</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Renseignez les coordonnées de connexion de votre serveur MySQL. Vous pouvez tester la connexion en direct avant de passer à l'étape suivante.
                </p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="db_host">Hôte du serveur MySQL :</label>
                        <input type="text" id="db_host" name="db_host" value="127.0.0.1" required>
                        <span class="helper-text">Généralement <code>127.0.0.1</code> ou <code>localhost</code>.</span>
                    </div>

                    <div class="form-group">
                        <label for="db_port">Port MySQL :</label>
                        <input type="number" id="db_port" name="db_port" value="3306" required>
                        <span class="helper-text">Le port par défaut est <code>3306</code>.</span>
                    </div>

                    <div class="form-group">
                        <label for="db_name">Nom de la base de données :</label>
                        <input type="text" id="db_name" name="db_name" value="opengalaxy" required>
                        <span class="helper-text">Ex: <code>openshogun</code> ou <code>opengalaxy</code>.</span>
                    </div>

                    <div class="form-group">
                        <label for="db_user">Utilisateur MySQL :</label>
                        <input type="text" id="db_user" name="db_user" value="root" required>
                        <span class="helper-text">Utilisateur ayant les privilèges de création de tables.</span>
                    </div>

                    <div class="form-group col-span-2">
                        <label for="db_pass">Mot de passe MySQL :</label>
                        <input type="password" id="db_pass" name="db_pass" placeholder="Laisser vide si aucun mot de passe">
                    </div>

                    <div class="form-group col-span-2">
                        <label class="checkbox-label">
                            <input type="checkbox" id="create_db" name="create_db" value="1" checked>
                            <span>Créer automatiquement la base de données si elle n'existe pas</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <button type="button" class="btn btn-test" onclick="testDbConnection()">
                        <span>🔌</span> Tester la Connexion en Direct
                    </button>
                    <span id="db-test-spinner" style="display:none; color:var(--gold-light); font-size:0.85rem;">⏳ Vérification en cours...</span>
                </div>

                <div id="db-test-result" class="alert"></div>

                <div class="installer-footer">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                        ⬅ Précédent
                    </button>
                    <button type="button" class="btn btn-primary" onclick="goToStep(3)">
                        Suivant : Univers & Administrateur ➔
                    </button>
                </div>
            </div>

            <!-- ÉTAPE 3 : PARAMÈTRES DE JEU & ADMINISTRATEUR -->
            <div class="step-section" id="section-step-3">
                <h2 class="section-title"><span>👑</span> Paramètres de l'Univers & Shogun Suprême</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Définissez les constantes de votre monde féodal et créez le compte du Shogun Administrateur qui gouvernera l'Empire.
                </p>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="game_title">Titre de l'Univers de Jeu :</label>
                        <input type="text" id="game_title" name="game_title" value="OpenShogun — Chroniques Féodales" required>
                    </div>

                    <div class="form-group">
                        <label for="site_url">URL Principale du Site :</label>
                        <input type="text" id="site_url" name="site_url" value="<?= htmlspecialchars($detectedUrl) ?>" required>
                    </div>

                    <div class="form-group col-span-2">
                        <label for="speed_factor">Vitesse du Jeu (Facteur d'accélération) :</label>
                        <select id="speed_factor" name="speed_factor">
                            <option value="1">Vitesse Réelle x1 (Rythme historique lent)</option>
                            <option value="2">Vitesse Accélérée x2 (Rythme modéré)</option>
                            <option value="5" selected>Vitesse Énergique x5 (Recommandé - Rythme dynamique)</option>
                            <option value="10">Vitesse Éclair x10 (Parties rapides et tests intenses)</option>
                        </select>
                        <span class="helper-text">Multiplie la production des 20 parcelles, la vitesse des armées et les temps de recherche.</span>
                    </div>

                    <div class="form-group">
                        <label for="admin_user">Nom du Shogun (Administrateur) :</label>
                        <input type="text" id="admin_user" name="admin_user" value="nezzar" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_email">Email de l'Administrateur :</label>
                        <input type="email" id="admin_email" name="admin_email" value="admin@openshogun.local" required>
                    </div>

                    <div class="form-group">
                        <label for="admin_pass">Mot de passe Shogun :</label>
                        <input type="password" id="admin_pass" name="admin_pass" required minlength="6" placeholder="Au moins 6 caractères">
                    </div>

                    <div class="form-group">
                        <label for="admin_pass_confirm">Confirmer le mot de passe :</label>
                        <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required minlength="6">
                    </div>

                    <div class="form-group col-span-2">
                        <label for="admin_faction">Clan de Naissance du Shogun :</label>
                        <select id="admin_faction" name="admin_faction">
                            <option value="terran" selected>Clan Tokugawa (Discipline, défenses solides & génie logistique)</option>
                            <option value="vorash">Clan Oda (Poudre noire foudroyante, armées offensives & expansionnisme)</option>
                            <option value="aethelis">Clan Takeda (Cavalerie de choc légendaire & maîtrise tactique des monts)</option>
                        </select>
                    </div>
                </div>

                <div class="installer-footer">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                        ⬅ Précédent
                    </button>
                    <button type="button" class="btn btn-primary" onclick="validateAndGoToStep4()">
                        Suivant : Récapitulatif & Déploiement ➔
                    </button>
                </div>
            </div>

            <!-- ÉTAPE 4 : CONFIRMATION & LANCEMENT DE L'INSTALLATION -->
            <div class="step-section" id="section-step-4">
                <h2 class="section-title"><span>🚀</span> Récapitulatif & Déploiement Immédiat</h2>
                
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    Veuillez vérifier les paramètres ci-dessous. En cliquant sur <strong>« Lancer l'Installation d'OpenShogun »</strong>, l'assistant va initialiser la base de données, configurer les fichiers système et sanctifier l'archipel féodal.
                </p>

                <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; font-size: 0.9rem;">
                        <div><strong style="color:var(--gold-light);">Serveur MySQL :</strong> <span id="summary-db-host">127.0.0.1:3306</span></div>
                        <div><strong style="color:var(--gold-light);">Base de Données :</strong> <span id="summary-db-name">openshogun</span></div>
                        <div><strong style="color:var(--gold-light);">Utilisateur DB :</strong> <span id="summary-db-user">root</span></div>
                        <div><strong style="color:var(--gold-light);">Vitesse de Jeu :</strong> <span id="summary-speed">x5</span></div>
                        <div><strong style="color:var(--gold-light);">Shogun Administrateur :</strong> <span id="summary-admin-user">nezzar</span></div>
                        <div><strong style="color:var(--gold-light);">Email :</strong> <span id="summary-admin-email">admin@openshogun.local</span></div>
                        <div style="grid-column: span 2;"><strong style="color:var(--gold-light);">Clan Faction :</strong> <span id="summary-admin-faction">Clan Tokugawa</span></div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <span>⛩️</span>
                    <div>
                        <strong>Ce que le déploiement automatisé va effectuer :</strong><br>
                        • Écriture de <code>config/database.php</code><br>
                        • Création des <strong>30 tables</strong> complètes (châteaux, héros, oasis, unités, etc.)<br>
                        • Injection des unités féodales, vaisseaux et technologies de recherche<br>
                        • Déploiement des 12 Donjons Authentiques et des oasis naturelles de la carte<br>
                        • Fondation de votre Fief Capital avec ses 20 parcelles rurales<br>
                        • Création du verrou de sécurité <code>config/installed.lock</code>
                    </div>
                </div>

                <div class="installer-footer">
                    <button type="button" class="btn btn-secondary" onclick="goToStep(3)">
                        ⬅ Modifier les Paramètres
                    </button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-install" style="font-size: 1.05rem; padding: 0.9rem 2rem;">
                        <span>⚔️</span> Lancer l'Installation d'OpenShogun
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
let currentStep = 1;

function goToStep(step) {
    document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-item').forEach(el => el.classList.remove('active'));

    const targetSection = document.getElementById('section-step-' + step);
    const targetBadge = document.getElementById('badge-step-' + step);

    if (targetSection) targetSection.classList.add('active');
    if (targetBadge) {
        targetBadge.classList.add('active');
        // Marquer les précédents comme complétés
        for (let i = 1; i < step; i++) {
            const prevBadge = document.getElementById('badge-step-' + i);
            if (prevBadge) prevBadge.classList.add('completed');
        }
    }
    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function testDbConnection() {
    const host = document.getElementById('db_host').value;
    const port = document.getElementById('db_port').value;
    const dbname = document.getElementById('db_name').value;
    const user = document.getElementById('db_user').value;
    const pass = document.getElementById('db_pass').value;
    const createDb = document.getElementById('create_db').checked ? 1 : 0;

    const spinner = document.getElementById('db-test-spinner');
    const resultBox = document.getElementById('db-test-result');

    spinner.style.display = 'inline';
    resultBox.style.display = 'none';

    const formData = new FormData();
    formData.append('db_host', host);
    formData.append('db_port', port);
    formData.append('db_name', dbname);
    formData.append('db_user', user);
    formData.append('db_pass', pass);
    formData.append('create_db', createDb);

    fetch('install.php?action=test_db', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        spinner.style.display = 'none';
        resultBox.style.display = 'flex';
        if (data.success) {
            resultBox.className = 'alert alert-success';
            resultBox.innerHTML = '<span>✅</span><div><strong>Connexion réussie !</strong> ' + data.message + '</div>';
        } else {
            resultBox.className = 'alert alert-error';
            resultBox.innerHTML = '<span>❌</span><div><strong>Échec de connexion :</strong> ' + data.error + '</div>';
        }
    })
    .catch(err => {
        spinner.style.display = 'none';
        resultBox.style.display = 'flex';
        resultBox.className = 'alert alert-error';
        resultBox.innerHTML = '<span>❌</span><div><strong>Erreur de communication :</strong> ' + err + '</div>';
    });
}

function validateAndGoToStep4() {
    const adminUser = document.getElementById('admin_user').value.trim();
    const adminPass = document.getElementById('admin_pass').value;
    const adminPassConfirm = document.getElementById('admin_pass_confirm').value;

    if (!adminUser) {
        alert('Veuillez spécifier le nom du Shogun Administrateur.');
        return;
    }
    if (adminPass.length < 6) {
        alert('Le mot de passe administrateur doit comporter au moins 6 caractères.');
        return;
    }
    if (adminPass !== adminPassConfirm) {
        alert('La confirmation du mot de passe ne correspond pas au mot de passe saisi.');
        return;
    }

    // Remplir le récapitulatif de l'étape 4
    document.getElementById('summary-db-host').textContent = document.getElementById('db_host').value + ':' + document.getElementById('db_port').value;
    document.getElementById('summary-db-name').textContent = document.getElementById('db_name').value;
    document.getElementById('summary-db-user').textContent = document.getElementById('db_user').value;
    document.getElementById('summary-speed').textContent = 'x' + document.getElementById('speed_factor').value;
    document.getElementById('summary-admin-user').textContent = adminUser;
    document.getElementById('summary-admin-email').textContent = document.getElementById('admin_email').value;

    const factionSelect = document.getElementById('admin_faction');
    document.getElementById('summary-admin-faction').textContent = factionSelect.options[factionSelect.selectedIndex].text;

    goToStep(4);
}

document.getElementById('install-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('btn-submit-install');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span> Déploiement de l\'univers en cours...';
    }
});
</script>

</body>
</html>
