<?php
/**
 * Test Automatisé : Interface Sobre & Ergonomique du Centre de Mises à Jour & Déploiement GitHub
 * Fichier : tests/test_admin_updates_view.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../core/UpdateEngine.php';

function assertTest(bool $condition, string $title): void {
    if ($condition) {
        echo "  [PASS] " . $title . "\n";
    } else {
        echo "  [FAIL] " . $title . "\n";
        exit(1);
    }
}

echo "========================================================================\n";
echo " TEST : CENTRE DE MISES À JOUR & DÉPLOIEMENT GITHUB (VUE SOBRE & ERGONOMIQUE)\n";
echo "========================================================================\n\n";

// 1. Vérification du moteur UpdateEngine
$engine = new UpdateEngine();
$local = $engine->getLocalInfo();
assertTest(!empty($local['short_sha']), "UpdateEngine::getLocalInfo() renvoie le SHA local actif");
assertTest(!empty($local['branch']), "UpdateEngine::getLocalInfo() renvoie la branche active");

// 2. Inspection du rendu du fichier de vue admin_updates.php
$viewPath = __DIR__ . '/../views/partials/admin_updates.php';
assertTest(file_exists($viewPath), "Le fichier views/partials/admin_updates.php existe");
$viewContent = file_get_contents($viewPath);

// En-tête sobre
assertTest(str_contains($viewContent, 'Mises à Jour &amp; Déploiement GitHub'), "Titre sobre présent dans l'en-tête");
assertTest(str_contains($viewContent, 'id="btn-check-updates"'), "Bouton de vérification présent");
assertTest(str_contains($viewContent, 'id="spinner-check"'), "Spinner de contrôle discret présent");

// 4 Cartes KPI sobres
assertTest(str_contains($viewContent, 'Version Locale (HEAD)'), "Carte KPI 1 : Version Locale présente");
assertTest(str_contains($viewContent, 'Branche Cible'), "Carte KPI 2 : Branche Cible présente");
assertTest(str_contains($viewContent, 'Espace de Travail'), "Carte KPI 3 : Espace de Travail présent");
assertTest(str_contains($viewContent, 'Statut Distant GitHub'), "Carte KPI 4 : Statut Distant GitHub présent");
assertTest(str_contains($viewContent, 'id="remote-status-pill"'), "Badge d'état distant réactif présent");

// Bannière dynamique et Zone de Déploiement
assertTest(str_contains($viewContent, 'id="update-status-banner"'), "Bannière dynamique de statut présente");
assertTest(str_contains($viewContent, 'id="update-action-box"'), "Boîte d'action de mise à jour présente");
assertTest(str_contains($viewContent, 'id="btn-install-update"'), "Bouton de déploiement en 1 clic présent");
assertTest(str_contains($viewContent, 'id="auto-stash-check"'), "Option switch auto-stash présente");
assertTest(str_contains($viewContent, 'id="update-commits-tbody"'), "Tableau des commits récents présent");

// Console d'exécution repliable
assertTest(str_contains($viewContent, 'id="collapseUpdateConsole"'), "Console d'exécution dans un volet repliable");
assertTest(str_contains($viewContent, 'id="update-console-log"'), "Terminal de logs présent");
assertTest(str_contains($viewContent, 'copyUpdateLogs()'), "Action de copie du journal présente");
assertTest(str_contains($viewContent, 'clearUpdateLogs()'), "Action d'effacement de la console présente");

// Paramètres de connexion GitHub repliables
assertTest(str_contains($viewContent, 'id="collapseGitHubSettings"'), "Volet repliable pour la configuration GitHub présent");
assertTest(str_contains($viewContent, 'id="github-settings-form"'), "Formulaire de configuration GitHub présent");
assertTest(str_contains($viewContent, 'id="inputGitHubToken"'), "Champ de token PAT avec masquage présent");
assertTest(str_contains($viewContent, 'toggleTokenVisibility()'), "Fonction de bascule de visibilité du token présente");

// Modale Tabler de confirmation
assertTest(str_contains($viewContent, 'id="modal-confirm-update-deploy"'), "Modale de confirmation de déploiement présente");
assertTest(str_contains($viewContent, 'executeInstallGitHubUpdate()'), "Fonction de déploiement confirmée liée");

// 3. Test de simulation de l'API check_github_updates
echo "\n[SIMULATION] Appel direct de l'action checkRemoteUpdates()...\n";
$remoteCheck = $engine->checkRemoteUpdates();
assertTest(isset($remoteCheck['success']), "Réponse checkRemoteUpdates() structurée avec succès");
assertTest(isset($remoteCheck['has_update']), "Indicateur has_update présent dans la réponse");
assertTest(isset($remoteCheck['behind_by']), "Nombre de commits de retard behind_by présent");

echo "\n========================================================================\n";
echo " TOUS LES TESTS DE L'INTERFACE SOBRE ET ERGONOMIQUE SONT 100% AU VERT !\n";
echo "========================================================================\n";
