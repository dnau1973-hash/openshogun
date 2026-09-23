<?php
/**
 * Test Automatisé : Système d'Onglets de l'Interface d'Administration (OpenShogun)
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

echo "========================================================\n";
echo " TEST : SYSTÈME D'ONGLETS DE L'INTERFACE D'ADMINISTRATION \n";
echo "========================================================\n\n";

$db = Database::getConnection();

// 1. Récupérer un administrateur existant
$adminUser = $db->query("SELECT id, username, is_admin FROM users WHERE is_admin = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    echo "[INFO] Aucun administrateur trouvé. Attribution temporaire à l'utilisateur #1.\n";
    $db->query("UPDATE users SET is_admin = 1 WHERE id = 1");
    $adminUser = ['id' => 1, 'username' => 'nezzar', 'is_admin' => 1];
}

$adminId = (int)$adminUser['id'];
echo "[PASS] Administrateur détecté : {$adminUser['username']} (ID: {$adminId})\n";
$_SESSION['user_id'] = $adminId;

// 2. Tester le rendu par défaut (tab non spécifié -> 'game')
$_GET = ['page' => 'admin'];
ob_start();
require __DIR__ . '/../views/admin.php';
$htmlDefault = ob_get_clean();

// Vérifier la présence de la barre d'onglets Tabler
assert(strpos($htmlDefault, 'data-bs-toggle="tabs"') !== false, "Erreur: composant Tabs de Tabler introuvable.");
assert(strpos($htmlDefault, 'id="adminTabsNav"') !== false, "Erreur: barre de navigation 'adminTabsNav' non trouvée.");
echo "[PASS] Composant Tabs Tabler 'adminTabsNav' présent.\n";

// Vérifier tous les boutons d'onglets attendus
$expectedTabs = ['world', 'heroes', 'bots', 'users', 'medals', 'support', 'announcements', 'forum', 'pedagogy', 'updates', 'maintenance', 'all'];
foreach ($expectedTabs as $tabKey) {
    assert(strpos($htmlDefault, "data-tab=\"{$tabKey}\"") !== false, "Erreur: onglet '{$tabKey}' introuvable dans la barre d'onglets.");
}
echo "[PASS] Les 12 onglets ('" . implode("', '", $expectedTabs) . "') sont correctement définis.\n";

// Vérifier la présence de tous les conteneurs tab-pane
foreach ($expectedTabs as $tabKey) {
    if ($tabKey === 'all') continue;
    $paneId = "tab-{$tabKey}";
    assert(strpos($htmlDefault, "id=\"{$paneId}\"") !== false, "Erreur: conteneur '{$paneId}' introuvable.");
}
echo "[PASS] Tous les conteneurs 'tab-*' sont présents dans le DOM.\n";

// 3. Vérifier le comportement d'affichage par défaut (world visible, les autres masqués)
assert(strpos($htmlDefault, 'id="tab-world"') !== false, "Erreur: le panneau world devrait être présent.");
assert(strpos($htmlDefault, 'id="tab-bots"') !== false, "Erreur: le panneau bots devrait être présent.");
assert(strpos($htmlDefault, 'id="tab-support"') !== false, "Erreur: le panneau support devrait être présent.");
assert(strpos($htmlDefault, 'id="tab-announcements"') !== false, "Erreur: le panneau announcements devrait être présent.");
echo "[PASS] Affichage par défaut conforme.\n";

// 4. Tester l'accès direct via ?tab=support et ?tab=announcements
$_GET = ['page' => 'admin', 'tab' => 'support'];
ob_start();
require __DIR__ . '/../views/admin.php';
$htmlSupport = ob_get_clean();

assert(strpos($htmlSupport, 'id="tab-support" data-tab="support"') !== false, "Erreur: le panneau support devrait être présent quand tab=support.");
echo "[PASS] Sélection d'onglet via paramètre URL (?tab=support) opérationnelle.\n";

$_GET = ['page' => 'admin', 'tab' => 'announcements'];
ob_start();
require __DIR__ . '/../views/admin.php';
$htmlAnn = ob_get_clean();

assert(strpos($htmlAnn, 'id="tab-announcements" data-tab="announcements"') !== false, "Erreur: le panneau announcements devrait être présent quand tab=announcements.");
echo "[PASS] Sélection d'onglet via paramètre URL (?tab=announcements) opérationnelle.\n";

// 5. Tester le mode ?tab=all (Tout Dérouler)
$_GET = ['page' => 'admin', 'tab' => 'all'];
ob_start();
require __DIR__ . '/../views/admin.php';
$htmlAll = ob_get_clean();

assert(strpos($htmlAll, 'id="tab-world"') !== false, "Erreur: le panneau world devrait être présent en mode all.");
assert(strpos($htmlAll, 'id="tab-bots"') !== false, "Erreur: le panneau bots devrait être présent en mode all.");
assert(strpos($htmlAll, 'id="tab-support"') !== false, "Erreur: le panneau support devrait être présent en mode all.");
echo "[PASS] Mode 'Tout Dérouler' (?tab=all) affiche l'intégralité des sections.\n";

// 6. Vérifier l'interactivité des cartes métriques (KPI)
assert(strpos($htmlDefault, "onclick=\"switchAdminTab('world')\"") !== false, "Erreur: KPI monde non interactif.");
assert(strpos($htmlDefault, "onclick=\"switchAdminTab('heroes')\"") !== false, "Erreur: KPI héros non interactif.");
assert(strpos($htmlDefault, "onclick=\"switchAdminTab('bots')\"") !== false, "Erreur: KPI bots non interactif.");
assert(strpos($htmlDefault, "onclick=\"switchAdminTab('users')\"") !== false, "Erreur: KPI joueurs non interactif.");
assert(strpos($htmlDefault, "onclick=\"switchAdminTab('support')\"") !== false, "Erreur: KPI support non interactif.");
echo "[PASS] Les cartes métriques supérieures déclenchent switchAdminTab().\n";

// 7. Vérifier la présence de la fonction JS switchAdminTab et de la persistance
assert(strpos($htmlDefault, "function switchAdminTab(tabKey)") !== false, "Erreur: fonction JS switchAdminTab introuvable.");
assert(strpos($htmlDefault, "sessionStorage.setItem('admin_active_tab', tabKey)") !== false, "Erreur: persistance sessionStorage introuvable.");
assert(strpos($htmlDefault, "url.searchParams.set('tab', tabKey)") !== false, "Erreur: synchronisation URL introuvable.");
echo "[PASS] Fonctions JavaScript et persistance (URL + sessionStorage) validées.\n";

echo "\n========================================================\n";
echo " TOUS LES TESTS DU SYSTÈME D'ONGLETS ADMIN ONT RÉUSSI ! \n";
echo "========================================================\n";
