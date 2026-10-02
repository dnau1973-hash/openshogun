<?php
/**
 * Cours Pédagogique 01 : PHP & Programmation Orientée Objet (POO) + Le Singleton
 * Section : Atelier Pédagogique / École du Backend
 * Niveau : Débutant / 12 ans
 * Charte : Tabler.io / Dela Gothic One / Immersion Féodale Sengoku
 */
declare(strict_types=1);

$lessonKey = 'php-poo-singleton';
$lessonTitle = "PHP & POO : La Forge de Sabres et le Facteur Impérial Unique";
$lessonSubtitle = "Découvre les classes, les objets, le Singleton et les sessions comme si tu étais au château du Shogun !";
?>

<div class="container-xl py-3 lesson-wrapper">
    <!-- Fil d'Ariane & Navigation Haute -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/?page=pedagogy" class="text-decoration-none"><i class="fa-solid fa-graduation-cap me-1"></i>Atelier Pédagogique</a></li>
            <li class="breadcrumb-item"><a href="/?page=pedagogy#section-cours-backend" class="text-decoration-none">École du Backend</a></li>
            <li class="breadcrumb-item active" aria-current="page">Leçon 01 : POO &amp; Singleton</li>
        </ol>
    </nav>

    <!-- En-tête Hero du Cours -->
    <div class="card mb-4 border shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body p-4 p-md-5 text-white position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary text-white fw-bold small">
                    <i class="fa-brands fa-php fs-3"></i> Backend &bull; Niveau Débutant (12 ans &amp; +)
                </div>
                <div class="text-white-50 small">
                    <i class="fa-solid fa-clock me-1"></i> Temps de lecture : 7 minutes &bull; <i class="fa-solid fa-medal text-warning ms-2 me-1"></i>+20 XP Concepteur
                </div>
            </div>

            <h1 class="display-6 fw-bold mb-3 text-white" style="font-family: 'Dela Gothic One', cursive, sans-serif; letter-spacing: 0.5px;">
                <span class="text-primary"><i class="fa-solid fa-shapes me-2"></i></span><?= htmlspecialchars($lessonTitle) ?>
            </h1>
            <p class="fs-3 text-light opacity-90 mb-4" style="max-width: 820px; line-height: 1.6;">
                <?= htmlspecialchars($lessonSubtitle) ?>
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="#partie-poo" class="btn btn-primary rounded-pill">
                    <i class="fa-solid fa-hammer me-1"></i> 1. La Forge &amp; les Objets
                </a>
                <a href="#partie-singleton" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-envelope-open-text me-1"></i> 2. Le Facteur Unique (Singleton)
                </a>
                <a href="#partie-sessions" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-stamp me-1"></i> 3. Le Sceau de Cire (Sessions)
                </a>
                <a href="#mini-quiz" class="btn btn-warning rounded-pill text-dark fw-bold">
                    <i class="fa-solid fa-clipboard-question me-1"></i> 4. Mini-Quiz Défi
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 1 : LA FORGE ET LE MOULE À GÂTEAUX                           -->
    <!-- ===================================================================== -->
    <div id="partie-poo" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-azure"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-azure-lt text-azure rounded-circle fs-2"><i class="fa-solid fa-hammer"></i></span>
                <div>
                    <span class="badge bg-azure-lt text-azure text-uppercase fw-bold">Chapitre 1</span>
                    <h2 class="h1 fw-bold text-dark mb-0">La Classe et l'Objet : L'Atelier de Forge</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-7">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        Imagine que tu es le <strong>maître forgeron du Shogun</strong>. Avant de frapper l'acier incandescent, tu dessines un <strong class="text-dark">plan d'architecte très précis</strong> sur un rouleau de parchemin.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Ce plan décrit tout : la longueur de la lame, le matériau de la garde, et la technique pour trancher le bambou. 
                        <strong>Mais attention !</strong> Tu ne peux pas te battre avec une feuille de papier : ce n'est qu'une <em>définition</em>.
                    </p>
                    
                    <div class="row g-3 my-2">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="fw-bold text-azure mb-1"><i class="fa-solid fa-scroll me-1"></i> La Classe (Le Plan ou le Moule)</div>
                                <div class="small text-secondary">
                                    C'est le moule à gâteau ou le plan du forgeron. Il n'existe qu'une seule fois dans ton code. Il définit ce que les objets sauront faire.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="fw-bold text-green mb-1"><i class="fa-solid fa-cubes me-1"></i> L'Objet (Le Vrai Sabre Forgé)</div>
                                <div class="small text-secondary">
                                    C'est le gâteau qui sort du four ou le vrai katana en acier ! Avec un seul moule, tu peux créer 100 sabres différents avec chacun leur nom et leur propriétaire.
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-secondary mt-3" style="line-height: 1.7;">
                        En PHP, quand on écrit <code>new Katana()</code>, c'est comme crier : <em>« Maître forgeron, utilise le plan Katana pour me fabriquer un nouvel exemplaire tout neuf ! »</em>. Cette action s'appelle <strong>instancier</strong> un objet.
                    </p>
                </div>

                <div class="col-lg-5">
                    <!-- Schéma Métaphorique -->
                    <div class="card bg-dark text-white border-0 shadow-lg p-3">
                        <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary mb-3">
                            <span class="badge bg-primary">Parchemin PHP</span>
                            <span class="small text-muted font-monospace">Katana.php</span>
                        </div>
                        <pre class="m-0 text-success small font-monospace" style="tab-size: 2; line-height: 1.5;"><code><span class="text-primary">class</span> <span class="text-warning">Katana</span> {
  <span class="text-muted">// 1. Les Propriétés (ce qu'il possède)</span>
  <span class="text-primary">public</span> <span class="text-info">$nom</span>;
  <span class="text-primary">public</span> <span class="text-info">$tranchant</span> = 100;

  <span class="text-muted">// 2. Les Méthodes (ce qu'il sait faire)</span>
  <span class="text-primary">public function</span> <span class="text-warning">attaquer</span>() {
    <span class="text-primary">return</span> <span class="text-light">"ZLAN ! Dégâts infligés !"</span>;
  }
}

<span class="text-muted">// On forge 2 sabres distincts :</span>
<span class="text-info">$sabreOda</span> = <span class="text-primary">new</span> <span class="text-warning">Katana</span>();
<span class="text-info">$sabreOda</span>-&gt;<span class="text-info">nom</span> = <span class="text-light">"ÉclairPourpre"</span>;

<span class="text-info">$sabreTakeda</span> = <span class="text-primary">new</span> <span class="text-warning">Katana</span>();
<span class="text-info">$sabreTakeda</span>-&gt;<span class="text-info">nom</span> = <span class="text-light">"VentDuNord"</span>;
</code></pre>
                    </div>
                </div>
            </div>

            <!-- Encadré Le Savais-tu ? -->
            <div class="alert alert-info bg-azure-lt border border-azure-subtle mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fa-solid fa-lightbulb text-warning fs-1"></i>
                    <div>
                        <strong class="text-dark d-block mb-1">Le savais-tu ? La petite flèche magique <code>-&gt;</code></strong>
                        <div class="text-secondary small">
                            En PHP, quand tu vois <code>$monPersonnage-&gt;courir()</code>, la flèche <code>-&gt;</code> signifie : <em>« Hé, prends cet objet précis, et demande-lui d'exécuter son action ! »</em>. C'est l'équivalent de dire à ton chien : <em>« Médor-&gt;donneLaPatte() »</em> !
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 2 : LE SINGLETON (LE FACTEUR IMPÉRIAL UNIQUE)               -->
    <!-- ===================================================================== -->
    <div id="partie-singleton" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-orange"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-orange-lt text-orange rounded-circle fs-2"><i class="fa-solid fa-envelope-open-text"></i></span>
                <div>
                    <span class="badge bg-orange-lt text-orange text-uppercase fw-bold">Chapitre 2</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Design Pattern « Singleton » : Le Facteur Impérial Unique</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-6">
                    <h3 class="fs-2 text-dark mb-3">Le problème de la pagaille au château</h3>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Dans OpenShogun, nous avons un service qui envoie des e-mails ou des notifications : <code>MailService</code>.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Imagine si chaque fois qu'un samouraï, un tavernier ou le cuisinier voulait envoyer une missive, on devait :
                    </p>
                    <ul class="text-secondary" style="line-height: 1.8;">
                        <li>Construire un nouveau bureau de poste en bois dans la cour...</li>
                        <li>Acheter une nouvelle écurie et 10 nouveaux chevaux...</li>
                        <li>Embaucher un nouveau facteur en chef...</li>
                    </ul>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Ce serait un gâchis gigantesque d'énergie et de mémoire pour notre serveur !
                    </p>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-orange-lt border border-orange-subtle p-4">
                        <h4 class="text-dark fw-bold mb-2"><i class="fa-solid fa-chess-rook text-orange me-1"></i> La Règle du Singleton</h4>
                        <p class="text-secondary small mb-3">
                            Le <strong>Singleton</strong> est une règle de programmation qui dit : <em>« Pour ce service spécial, il n'existera JAMAIS qu'un seul et unique exemplaire dans tout le royaume informatique ! »</em>.
                        </p>
                        <div class="bg-white p-3 rounded border text-dark font-monospace small mb-2">
                            <span class="text-secondary">// Au lieu de faire new MailService() :</span><br>
                            <span class="text-primary">$facteur</span> = <span class="text-warning">MailService</span>::<span class="text-danger fw-bold">getInstance()</span>;<br>
                            <span class="text-primary">$facteur</span>-&gt;envoyerMissive(<span class="text-success">"Ennemi repéré !"</span>);
                        </div>
                        <div class="small text-muted">
                            Si le facteur existe déjà, <code>getInstance()</code> te le prête aussitôt. S'il n'existe pas encore, il le crée une bonne fois pour toutes !
                        </div>
                    </div>
                </div>
            </div>

            <!-- Encadré Le Savais-tu ? -->
            <div class="alert alert-warning bg-warning-lt border border-warning-subtle mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fa-solid fa-shield-cat text-warning fs-1"></i>
                    <div>
                        <strong class="text-dark d-block mb-1">Le saviez-tu ? Pourquoi le mot bizarre « Singleton » ?</strong>
                        <div class="text-secondary small">
                            En anglais, un « single » est quelque chose d'unique ou de célibataire. En informatique, le <strong>Singleton</strong> fait partie de ce qu'on appelle les <em>Design Patterns</em> (des recettes de cuisine géniales inventées par les pionniers de l'informatique pour résoudre des problèmes classiques).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 3 : LES SESSIONS ET LE SCEAU DE CIRE                         -->
    <!-- ===================================================================== -->
    <div id="partie-sessions" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-teal"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-teal-lt text-teal rounded-circle fs-2"><i class="fa-solid fa-stamp"></i></span>
                <div>
                    <span class="badge bg-teal-lt text-teal text-uppercase fw-bold">Chapitre 3</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Les Sessions (<code>$_SESSION</code>) : Le Sceau de Cire sur la Main</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-7">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        As-tu remarqué que quand tu te connectes à OpenShogun avec ton mot de passe, tu n'as pas besoin de le retaper à chaque fois que tu cliques sur la Caserne, la Carte ou le Sénat ?
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Pourtant, le protocole du Web (HTTP) a la mémoire d'un poisson rouge : <strong>dès qu'une page est affichée, il oublie complètement qui tu es !</strong>
                    </p>

                    <div class="p-3 bg-light rounded-3 border my-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-1 text-teal"><i class="fa-solid fa-fingerprint"></i></span>
                            <div>
                                <strong class="text-dark">L'Astuce du Garde du Château :</strong>
                                <div class="text-secondary small mt-1">
                                    Lorsque tu prouves ton identité à la grande porte (connexion réussie), le garde tamponne sur le dos de ta main un <strong>sceau de cire impérial avec un numéro secret</strong> (appelé le <code>PHPSESSID</code>).
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-secondary" style="line-height: 1.7;">
                        Quand tu te déplaces dans le château, les sentinelles regardent juste ton tampon : <em>« Ah oui, c'est bien le Daimyo de la session n° 84920 ! Bienvenue dans votre armurerie monseigneur ! »</em>.
                    </p>
                </div>

                <div class="col-lg-5">
                    <div class="card bg-teal-lt border border-teal-subtle p-4 text-center">
                        <div class="avatar avatar-xl bg-teal text-white rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fa-solid fa-key fs-1"></i>
                        </div>
                        <h4 class="text-dark fw-bold mb-1">Dans le code PHP du serveur :</h4>
                        <div class="bg-white p-3 rounded border text-start font-monospace small mb-3">
                            <span class="text-secondary">// 1. Le joueur s'est connecté</span><br>
                            <span class="text-primary">$_SESSION</span>[<span class="text-success">'user_id'</span>] = 42;<br>
                            <span class="text-primary">$_SESSION</span>[<span class="text-success">'pseudo'</span>] = <span class="text-success">"Musashi"</span>;<br>
                            <span class="text-primary">$_SESSION</span>[<span class="text-success">'is_admin'</span>] = <span class="text-danger">true</span>;
                        </div>
                        <div class="text-secondary small">
                            Sur toutes les autres pages, PHP vérifie simplement <code>Auth::check()</code> en regardant si le tiroir de session existe toujours !
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 4 : LE MINI-QUIZ DÉFI DU SAMOURAÏ CODEUR                     -->
    <!-- ===================================================================== -->
    <div id="mini-quiz" class="card border-primary shadow-sm mb-4">
        <div class="card-status-top bg-primary"></div>
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <h3 class="card-title fw-bold m-0" style="font-family: 'Dela Gothic One', cursive, sans-serif;">
                <i class="fa-solid fa-graduation-cap me-2"></i>Le Défi de Gabriel : Teste tes Connaissances !
            </h3>
            <span class="badge bg-white text-primary fw-bold">2 Questions</span>
        </div>
        <div class="card-body p-4">
            <p class="text-secondary mb-4">
                Réponds aux deux questions ci-dessous pour prouver que tu as bien assimilé la magie de la POO et du Singleton !
            </p>

            <form id="quiz-poo-form">
                <!-- Question 1 -->
                <div class="card bg-light border p-3 mb-3">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-primary text-white me-2">Question 1</span>
                        Si une <span class="text-primary">Classe</span> est le plan d'architecte d'un château féodal, qu'est-ce qu'un <span class="text-success">Objet</span> ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_opt1" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_opt1">
                            Une simple feuille de papier vierge sans aucun dessin.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_opt2" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q1_opt2">
                            Le véritable château construit avec du vrai bois et de la vraie pierre dans le jeu !
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_opt3">
                            Un bouton pour éteindre l'ordinateur.
                        </label>
                    </div>
                    <div id="q1-feedback" class="mt-2 small d-none"></div>
                </div>

                <!-- Question 2 -->
                <div class="card bg-light border p-3 mb-4">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-primary text-white me-2">Question 2</span>
                        Pourquoi le <code>MailService</code> utilise-t-il le patron de conception <span class="text-orange">Singleton</span> ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_opt1" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_opt1">
                            Pour envoyer 10 000 spams par seconde dans la boîte des joueurs.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_opt2" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q2_opt2">
                            Pour avoir un seul facteur impérial unique et éviter de gaspiller la mémoire du serveur.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_opt3">
                            Parce que les pigeons voyageurs ne savent voler qu'une seule fois dans leur vie.
                        </label>
                    </div>
                    <div id="q2-feedback" class="mt-2 small d-none"></div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="validatePooQuiz()">
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
            <a href="/?page=pedagogy#section-cours-backend" class="btn btn-outline-secondary">
                <i class="fa-solid fa-list me-1"></i> Sommaire de l'Atelier
            </a>
        </div>
        <div class="d-flex gap-2">
            <a href="/?page=pedagogy&lesson=pdo-sql-injection" class="btn btn-primary">
                Cours Suivant : Le Coffre-Fort PDO &amp; Injections SQL <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<script>
function validatePooQuiz() {
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
        f1.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Bravo !</strong> L\'objet est bien l\'élément réel et concret forgé à partir du plan !';
    } else {
        f1.classList.add('text-danger');
        f1.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Pas tout à fait :</strong> Le plan est la classe, et le château bâti en vrai est l\'objet !';
    }

    // Q2 Check
    f2.classList.remove('d-none', 'text-success', 'text-danger');
    if (q2.value === 'correct') {
        score++;
        f2.classList.add('text-success');
        f2.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Exactement !</strong> Le Singleton garantit un facteur impérial unique dans tout le château !';
    } else {
        f2.classList.add('text-danger');
        f2.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Oups :</strong> Le Singleton sert à n\'avoir qu\'un seul gestionnaire en mémoire pour ne pas encombrer le serveur.';
    }

    if (score === 2) {
        scoreBox.innerHTML = '<span class="text-success fs-3"><i class="fa-solid fa-trophy text-warning me-1"></i>Score parfait : 2 / 2 ! Tu maîtrises la POO comme un maître forgeron !</span>';
    } else {
        scoreBox.innerHTML = '<span class="text-warning fs-4"><i class="fa-solid fa-rotate-right me-1"></i>Score : ' + score + ' / 2. Relis les encadrés et retente ta chance !</span>';
    }
}
</script>
