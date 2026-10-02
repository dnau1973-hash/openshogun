<?php
/**
 * Cours Pédagogique 02 : PDO & Sécurité contre les Injections SQL
 * Section : Atelier Pédagogique / École du Backend
 * Niveau : Débutant / 12 ans
 * Charte : Tabler.io / Dela Gothic One / Immersion Féodale Sengoku
 */
declare(strict_types=1);

$lessonKey = 'pdo-sql-injection';
$lessonTitle = "PDO & Sécurité : Le Coffre-Fort du Shogun et les Parchemins Piégés";
$lessonSubtitle = "Comment protéger les trésors du village contre les attaques par injection SQL grâce à la boîte aux lettres blindée des requêtes préparées.";
?>

<div class="container-xl py-3 lesson-wrapper">
    <!-- Fil d'Ariane & Navigation Haute -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/?page=pedagogy" class="text-decoration-none"><i class="fa-solid fa-graduation-cap me-1"></i>Atelier Pédagogique</a></li>
            <li class="breadcrumb-item"><a href="/?page=pedagogy#section-cours-backend" class="text-decoration-none">École du Backend</a></li>
            <li class="breadcrumb-item active" aria-current="page">Leçon 02 : PDO &amp; Injections SQL</li>
        </ol>
    </nav>

    <!-- En-tête Hero du Cours -->
    <div class="card mb-4 border shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);">
        <div class="card-status-top bg-danger"></div>
        <div class="card-body p-4 p-md-5 text-white position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger text-white fw-bold small">
                    <i class="fa-solid fa-shield-halved fs-3"></i> Sécurité &amp; BDD &bull; Niveau Débutant (12 ans &amp; +)
                </div>
                <div class="text-white-50 small">
                    <i class="fa-solid fa-clock me-1"></i> Temps de lecture : 8 minutes &bull; <i class="fa-solid fa-medal text-warning ms-2 me-1"></i>+25 XP Gardien du Trésor
                </div>
            </div>

            <h1 class="display-6 fw-bold mb-3 text-white" style="font-family: 'Dela Gothic One', cursive, sans-serif; letter-spacing: 0.5px;">
                <span class="text-danger"><i class="fa-solid fa-vault me-2"></i></span><?= htmlspecialchars($lessonTitle) ?>
            </h1>
            <p class="fs-3 text-light opacity-90 mb-4" style="max-width: 820px; line-height: 1.6;">
                <?= htmlspecialchars($lessonSubtitle) ?>
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="#partie-coffrefort" class="btn btn-danger rounded-pill">
                    <i class="fa-solid fa-database me-1"></i> 1. Le Coffre-Fort du Shogun
                </a>
                <a href="#partie-injection" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-user-ninja me-1"></i> 2. L'Attaque du Parchemin Piégé
                </a>
                <a href="#partie-requetepreparee" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-box-archive me-1"></i> 3. La Boîte Blindée (prepare/execute)
                </a>
                <a href="#mini-quiz" class="btn btn-warning rounded-pill text-dark fw-bold">
                    <i class="fa-solid fa-clipboard-question me-1"></i> 4. Mini-Quiz Défi
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 1 : LE COFFRE-FORT DES TRÉSORS DU SHOGUN                    -->
    <!-- ===================================================================== -->
    <div id="partie-coffrefort" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-orange"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-orange-lt text-orange rounded-circle fs-2"><i class="fa-solid fa-database"></i></span>
                <div>
                    <span class="badge bg-orange-lt text-orange text-uppercase fw-bold">Chapitre 1</span>
                    <h2 class="h1 fw-bold text-dark mb-0">La Base de Données : La Chambre Forte du Fief</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-7">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        Dans les sous-sols du château d'OpenShogun se trouve la pièce la plus précieuse de tout l'empire : 
                        <strong>la grande réserve centrale (la Base de Données MySQL)</strong>.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Elle contient des milliers de grands casiers organisés en tableaux (les <em class="text-dark">tables SQL</em>) :
                    </p>
                    <ul class="text-secondary" style="line-height: 1.8;">
                        <li>Le casier <code>users</code> avec les pseudos et mots de passe secrets des chefs de guerre.</li>
                        <li>Le casier <code>resources</code> qui compte chaque grain de riz, planche de bois et minerai de fer.</li>
                        <li>Le casier <code>planet_buildings</code> qui mémorise la hauteur de tes murailles et le niveau de ton donjon Tenshu.</li>
                    </ul>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Pour parler au grand archiviste qui garde cette pièce, on utilise un langage spécial : le <strong>SQL</strong> (<em>Structured Query Language</em>). Par exemple :
                        <br><code class="text-dark bg-light px-2 py-1 rounded border">SELECT bois, fer FROM resources WHERE user_id = 12;</code>
                    </p>
                </div>

                <div class="col-lg-5">
                    <div class="card bg-light border p-4 shadow-sm text-center">
                        <div class="avatar avatar-xl bg-orange text-white rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fa-solid fa-dungeon fs-1"></i>
                        </div>
                        <h4 class="text-dark fw-bold mb-2">L'Archiviste du Château (MySQL)</h4>
                        <p class="text-secondary small mb-3">
                            L'archiviste est extrêmement obéissant et très rapide... mais il est un peu trop naïf ! Il exécute au pied de la lettre TOUS les ordres qu'on lui donne sous forme de texte SQL.
                        </p>
                        <div class="badge bg-warning-lt text-warning-emphasis p-2 text-wrap">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Si on lui donne un ordre pirate, il l'exécute sans hésiter !
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 2 : L'INJECTION SQL ET LE PARCHEMIN PIÉGÉ                   -->
    <!-- ===================================================================== -->
    <div id="partie-injection" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-danger"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-danger-lt text-danger rounded-circle fs-2"><i class="fa-solid fa-skull-crossbones"></i></span>
                <div>
                    <span class="badge bg-danger-lt text-danger text-uppercase fw-bold">Chapitre 2</span>
                    <h2 class="h1 fw-bold text-dark mb-0">L'Attaque par Injection SQL : Le Parchemin Piégé du Bandit</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-6">
                    <h3 class="fs-2 text-dark mb-3">Le piège de la phrase collée (concaténation)</h3>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Imagine qu'un développeur distrait écrive son code en collant simplement le pseudo tapé par le joueur dans la phrase SQL :
                    </p>
                    <div class="bg-dark p-3 rounded text-danger font-monospace small mb-3">
                        <span class="text-muted">// CODE DANGEREUX À NE JAMAIS FAIRE !</span><br>
                        $sql = "SELECT * FROM users WHERE pseudo = '<span class="text-warning">$pseudoTape</span>'";
                    </div>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Si un bandit rusé se présente au formulaire de connexion et entre comme pseudo ce texte étrange :<br>
                        <code class="text-danger fw-bold bg-danger-lt px-2 py-1 rounded">' OR '1'='1</code>
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        La phrase finale lue par l'archiviste devient :<br>
                        <code class="text-dark bg-light px-2 py-1 rounded border">SELECT * FROM users WHERE pseudo = '' OR '1'='1'</code>
                    </p>
                    <div class="alert alert-danger bg-danger-lt border border-danger-subtle mb-0">
                        <i class="fa-solid fa-bomb me-2"></i>
                        Comme <strong>1 est TOUJOURS égal à 1</strong>, la condition est vraie pour tout le monde ! L'archiviste ouvre la porte en grand et le pirate est connecté sur le compte du premier joueur, voire de l'administrateur suprême !
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-dark text-white border-0 shadow-lg p-3">
                        <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary mb-3">
                            <span class="badge bg-danger">Autopsie du piratage</span>
                            <span class="small text-muted font-monospace">injection_demo.sql</span>
                        </div>
                        <div class="text-center py-4">
                            <div class="display-1 text-danger mb-2"><i class="fa-solid fa-unlock-keyhole"></i></div>
                            <h4 class="text-white">Le Coffre est Ouvert Sans Mot de Passe !</h4>
                            <p class="text-light opacity-75 small mb-0 px-3">
                                En glissant des guillemets et des mots-clés SQL dans un champ de texte innocent, le pirate a transformé une simple donnée en un ordre de triche.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Encadré Le Savais-tu ? -->
            <div class="alert alert-info bg-azure-lt border border-azure-subtle mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fa-solid fa-lightbulb text-warning fs-1"></i>
                    <div>
                        <strong class="text-dark d-block mb-1">Le savais-tu ? L'histoire de « Little Bobby Tables »</strong>
                        <div class="text-secondary small">
                            Une bande dessinée très célèbre chez les programmeurs (du site <em>xkcd</em>) raconte l'histoire d'une maman qui a appelé son fils : 
                            <code>Robert'); DROP TABLE Students;--</code>. 
                            Quand l'école a enregistré son nom dans son ordinateur mal protégé, la base de données a interprété le nom comme un ordre de destruction et a effacé toute la liste des élèves de l'école !
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 3 : LES REQUÊTES PRÉPARÉES (LA BOÎTE AUX LETTRES BLINDÉE)   -->
    <!-- ===================================================================== -->
    <div id="partie-requetepreparee" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-success"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-success-lt text-success rounded-circle fs-2"><i class="fa-solid fa-box-archive"></i></span>
                <div>
                    <span class="badge bg-success-lt text-success text-uppercase fw-bold">Chapitre 3</span>
                    <h2 class="h1 fw-bold text-dark mb-0">La Solution Magique PDO : Les Requêtes Préparées</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-7">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        Pour neutraliser définitivement ces attaques ninja, les bâtisseurs d'OpenShogun utilisent <strong>PDO</strong> et ses <strong class="text-success">requêtes préparées</strong> (<code>prepare</code> et <code>execute</code>).
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Imagine cela comme <strong>une boîte aux lettres magique avec une fente blindée</strong>. Au lieu de tout mélanger, le système sépare strictement l'action en deux étapes :
                    </p>

                    <div class="row g-3 my-2">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="fw-bold text-primary mb-1"><i class="fa-solid fa-1 me-1"></i> L'Ordre Gravé dans la Pierre</div>
                                <div class="small text-secondary">
                                    Avec <code>$db->prepare(...)</code>, on donne l'ordre SQL avec un trou marqué par un point d'interrogation <code>?</code> (ou <code>:pseudo</code>). L'archiviste prépare le casier à l'avance.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="fw-bold text-success mb-1"><i class="fa-solid fa-2 me-1"></i> La Donnée Glissée dans la Fente</div>
                                <div class="small text-secondary">
                                    Avec <code>$stmt->execute([$pseudo])</code>, la valeur est transmise à part. <strong>Elle est traitée comme du texte pur et inoffensif</strong>, jamais comme un ordre exécutable !
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-secondary mt-3" style="line-height: 1.7;">
                        Même si un joueur tape <code>' OR '1'='1</code> ou <code>DROP TABLE</code>, MySQL se dit simplement : <em>« Tiens, un joueur qui s'appelle bizarrement Monsieur Parchemin ! Je vais chercher un joueur qui a exactement ce nom bizarre »</em>. Rien n'explose, le château reste inviolable !
                    </p>
                </div>

                <div class="col-lg-5">
                    <!-- Comparatif de Code Propre -->
                    <div class="card bg-dark text-white border-0 shadow-lg p-3">
                        <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary mb-3">
                            <span class="badge bg-success">Le Code Blindé OpenShogun</span>
                            <span class="small text-muted font-monospace">Database.php</span>
                        </div>
                        <pre class="m-0 text-success small font-monospace" style="tab-size: 2; line-height: 1.5;"><code><span class="text-muted">// Étape 1 : On prépare le modèle d'ordre</span>
<span class="text-info">$stmt</span> = <span class="text-primary">$db</span>-&gt;<span class="text-warning">prepare</span>(
  <span class="text-light">"SELECT * FROM users WHERE pseudo = :nom"</span>
);

<span class="text-muted">// Étape 2 : On injecte la valeur isolée</span>
<span class="text-info">$stmt</span>-&gt;<span class="text-warning">execute</span>([
  <span class="text-light">':nom'</span> =&gt; <span class="text-info">$pseudoDuJoueur</span>
]);

<span class="text-muted">// Étape 3 : On lit les résultats sans peur !</span>
<span class="text-info">$joueur</span> = <span class="text-info">$stmt</span>-&gt;<span class="text-warning">fetch</span>();
</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 4 : LE MINI-QUIZ DÉFI DU GARDE DU CORPS                      -->
    <!-- ===================================================================== -->
    <div id="mini-quiz" class="card border-danger shadow-sm mb-4">
        <div class="card-status-top bg-danger"></div>
        <div class="card-header bg-danger text-white d-flex align-items-center justify-content-between">
            <h3 class="card-title fw-bold m-0" style="font-family: 'Dela Gothic One', cursive, sans-serif;">
                <i class="fa-solid fa-graduation-cap me-2"></i>Le Défi de Gabriel : Teste tes Connaissances Sécurité !
            </h3>
            <span class="badge bg-white text-danger fw-bold">2 Questions</span>
        </div>
        <div class="card-body p-4">
            <p class="text-secondary mb-4">
                Prouve que tu es digne de garder les clés de la chambre forte en répondant sans faute aux deux énigmes !
            </p>

            <form id="quiz-pdo-form">
                <!-- Question 1 -->
                <div class="card bg-light border p-3 mb-3">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-danger text-white me-2">Question 1</span>
                        Comment un pirate tente-t-il de réussir une <span class="text-danger">Injection SQL</span> ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_sql_opt1" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q1_sql_opt1">
                            En glissant des bouts de code SQL malicieux dans un champ de texte pour tromper la base de données.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_sql_opt2" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_sql_opt2">
                            En appuyant très fort sur la touche Entrée du clavier.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_sql_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_sql_opt3">
                            En envoyant une lettre recommandée par la vraie poste.
                        </label>
                    </div>
                    <div id="q1-feedback" class="mt-2 small d-none"></div>
                </div>

                <!-- Question 2 -->
                <div class="card bg-light border p-3 mb-4">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-danger text-white me-2">Question 2</span>
                        Pourquoi les <span class="text-success">requêtes préparées</span> (<code>prepare</code> / <code>execute</code>) sont-elles invulnérables ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_sql_opt1" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_sql_opt1">
                            Parce qu'elles suppriment automatiquement l'ordinateur du joueur suspect.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_sql_opt2" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q2_sql_opt2">
                            Parce qu'elles séparent strictement l'ordre SQL de la donnée saisie, traitée comme du simple texte inoffensif.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_sql_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_sql_opt3">
                            Parce que MySQL ne fonctionne que le week-end.
                        </label>
                    </div>
                    <div id="q2-feedback" class="mt-2 small d-none"></div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-danger px-4 fw-bold" onclick="validatePdoQuiz()">
                        <i class="fa-solid fa-check me-2"></i>Valider mes Réponses
                    </button>
                    <div id="quiz-final-score" class="fw-bold"></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation Entre les Cours (Boutons Précédent / Sommaire / Suivant) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 bg-white rounded border shadow-sm">
        <div>
            <a href="/?page=pedagogy&lesson=php-poo-singleton" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Cours Précédent : La Forge POO &amp; Singleton
            </a>
        </div>
        <div class="d-flex gap-2">
            <a href="/?page=pedagogy#section-cours-backend" class="btn btn-outline-secondary">
                <i class="fa-solid fa-list me-1"></i> Sommaire
            </a>
            <a href="/?page=pedagogy&lesson=routing-get-post" class="btn btn-success">
                Cours Suivant : Le Routage, $_GET &amp; $_POST <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<script>
function validatePdoQuiz() {
    const q1 = document.querySelector('input[name="q1"]:checked');
    const q2 = document.querySelector('input[name="q2"]:checked');
    const f1 = document.getElementById('q1-feedback');
    const f2 = document.getElementById('q2-feedback');
    const scoreBox = document.getElementById('quiz-final-score');

    if (!q1 || !q2) {
        scoreBox.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Veuillez cocher une réponse pour chaque question !</span>';
        return;
    }

    let score = 0;

    // Q1 Check
    f1.classList.remove('d-none', 'text-success', 'text-danger');
    if (q1.value === 'correct') {
        score++;
        f1.classList.add('text-success');
        f1.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Excellent !</strong> L\'injection SQL consiste à duper le serveur en glissant du code dans une saisie texte.';
    } else {
        f1.classList.add('text-danger');
        f1.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Incorrect :</strong> Une injection SQL se produit quand du texte malicieux est interprété comme un ordre SQL.';
    }

    // Q2 Check
    f2.classList.remove('d-none', 'text-success', 'text-danger');
    if (q2.value === 'correct') {
        score++;
        f2.classList.add('text-success');
        f2.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Parfaitement vu !</strong> La requête préparée sépare l\'ordre SQL des données, rendant toute triche impossible.';
    } else {
        f2.classList.add('text-danger');
        f2.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Attention :</strong> Le blindage de PDO vient de la stricte dissociation entre la commande et la donnée.';
    }

    if (score === 2) {
        scoreBox.innerHTML = '<span class="text-success fs-3"><i class="fa-solid fa-shield-halved text-success me-1"></i>Score parfait : 2 / 2 ! Le coffre du Shogun est en totale sécurité avec toi !</span>';
    } else {
        scoreBox.innerHTML = '<span class="text-warning fs-4"><i class="fa-solid fa-rotate-right me-1"></i>Score : ' + score + ' / 2. Relis les explications de la boîte blindée et recommence !</span>';
    }
}
</script>
