<?php
/**
 * Cours Pédagogique 03 : Le Routage par URL & les Messagers $_GET et $_POST
 * Section : Atelier Pédagogique / École du Backend
 * Niveau : Débutant / 12 ans
 * Charte : Tabler.io / Dela Gothic One / Immersion Féodale Sengoku
 */
declare(strict_types=1);

$lessonKey = 'routing-get-post';
$lessonTitle = "Routage & Messagers : Les Panneaux de Kyoto, Cartes Postales et Ninjas";
$lessonSubtitle = "Comprendre comment les liens dirigent les aventuriers dans les quartiers du jeu et percer les secrets des messagers \$_GET et \$_POST.";
?>

<div class="container-xl py-3 lesson-wrapper">
    <!-- Fil d'Ariane & Navigation Haute -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/?page=pedagogy" class="text-decoration-none"><i class="fa-solid fa-graduation-cap me-1"></i>Atelier Pédagogique</a></li>
            <li class="breadcrumb-item"><a href="/?page=pedagogy#section-cours-backend" class="text-decoration-none">École du Backend</a></li>
            <li class="breadcrumb-item active" aria-current="page">Leçon 03 : Routage, $_GET &amp; $_POST</li>
        </ol>
    </nav>

    <!-- En-tête Hero du Cours -->
    <div class="card mb-4 border shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #064e3b 0%, #0f172a 100%);">
        <div class="card-status-top bg-success"></div>
        <div class="card-body p-4 p-md-5 text-white position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success text-white fw-bold small">
                    <i class="fa-solid fa-signs-post fs-3"></i> Réseau &amp; Web &bull; Niveau Débutant (12 ans &amp; +)
                </div>
                <div class="text-white-50 small">
                    <i class="fa-solid fa-clock me-1"></i> Temps de lecture : 7 minutes &bull; <i class="fa-solid fa-medal text-warning ms-2 me-1"></i>+25 XP Éclaireur du Web
                </div>
            </div>

            <h1 class="display-6 fw-bold mb-3 text-white" style="font-family: 'Dela Gothic One', cursive, sans-serif; letter-spacing: 0.5px;">
                <span class="text-success"><i class="fa-solid fa-compass me-2"></i></span><?= htmlspecialchars($lessonTitle) ?>
            </h1>
            <p class="fs-3 text-light opacity-90 mb-4" style="max-width: 820px; line-height: 1.6;">
                <?= htmlspecialchars($lessonSubtitle) ?>
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="#partie-routage" class="btn btn-success rounded-pill">
                    <i class="fa-solid fa-map-location-dot me-1"></i> 1. Les Panneaux de Kyoto (Le Routeur)
                </a>
                <a href="#partie-get" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-postcard me-1"></i> 2. La Carte Postale ($_GET)
                </a>
                <a href="#partie-post" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-envelope me-1"></i> 3. La Missive Ninja ($_POST)
                </a>
                <a href="#mini-quiz" class="btn btn-warning rounded-pill text-dark fw-bold">
                    <i class="fa-solid fa-clipboard-question me-1"></i> 4. Mini-Quiz Défi
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 1 : LE ROUTAGE ET LES PANNEAUX DE KYOTO                     -->
    <!-- ===================================================================== -->
    <div id="partie-routage" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-green"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-green-lt text-green rounded-circle fs-2"><i class="fa-solid fa-signs-post"></i></span>
                <div>
                    <span class="badge bg-green-lt text-green text-uppercase fw-bold">Chapitre 1</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Routeur : L'Aiguilleur aux Portes de Kyoto</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-7">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        Quand tu navigues sur OpenShogun, regarde bien la barre blanche tout en haut de ton navigateur web. Tu y vois des adresses comme :
                        <br><code class="text-dark bg-light px-2 py-1 rounded border">https://openshogun.com/index.php?page=barracks</code>
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        C'est exactement comme arriver au grand carrefour de la capitale impériale de Kyoto ! 
                        À l'entrée de la ville se tient un veilleur sage : <strong>le fichier <code>index.php</code> (qu'on appelle le Routeur)</strong>.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Il regarde le panneau que tu lui montres :
                    </p>
                    <ul class="text-secondary" style="line-height: 1.8;">
                        <li>Si le panneau indique <code>?page=building</code>, il t'emmène au quartier des architectes du fief.</li>
                        <li>Si le panneau indique <code>?page=barracks</code>, il ouvre la porte de la caserne d'entraînement des guerriers.</li>
                        <li>Si le panneau est illisible ou inconnu, il te raccompagne sagement au centre du village (<code>resources</code>).</li>
                    </ul>
                </div>

                <div class="col-lg-5">
                    <div class="card bg-dark text-white border-0 shadow-lg p-3">
                        <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary mb-3">
                            <span class="badge bg-success">L'Aiguillage dans index.php</span>
                            <span class="small text-muted font-monospace">Routeur PHP</span>
                        </div>
                        <pre class="m-0 text-success small font-monospace" style="tab-size: 2; line-height: 1.5;"><code><span class="text-muted">// 1. On lit le panneau d'indication</span>
<span class="text-info">$page</span> = <span class="text-primary">$_GET</span>[<span class="text-light">'page'</span>] ?? <span class="text-light">'resources'</span>;

<span class="text-muted">// 2. L'aiguilleur vérifie la liste autorisée</span>
<span class="text-primary">switch</span> (<span class="text-info">$page</span>) {
  <span class="text-primary">case</span> <span class="text-light">'barracks'</span>:
    <span class="text-warning">require</span> <span class="text-light">'views/barracks.php'</span>;
    <span class="text-primary">break</span>;

  <span class="text-primary">case</span> <span class="text-light">'pedagogy'</span>:
    <span class="text-warning">require</span> <span class="text-light">'views/pedagogy.php'</span>;
    <span class="text-primary">break</span>;

  <span class="text-primary">default</span>:
    <span class="text-warning">require</span> <span class="text-light">'views/resources.php'</span>;
}
</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 2 : LE MESSAGER $_GET (LA CARTE POSTALE TRANSPARENTE)       -->
    <!-- ===================================================================== -->
    <div id="partie-get" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-azure"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-azure-lt text-azure rounded-circle fs-2"><i class="fa-solid fa-postcard"></i></span>
                <div>
                    <span class="badge bg-azure-lt text-azure text-uppercase fw-bold">Chapitre 2</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Messager <code>$_GET</code> : La Carte Postale Transparente</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-6">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        La méthode <strong class="text-azure">$_GET</strong> transmet les informations directement collées dans l'adresse du site, juste après un point d'interrogation <code>?</code>.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Imagine que tu écris un mot sur une <strong>carte postale</strong>.
                    </p>
                    <div class="p-3 bg-azure-lt rounded-3 border border-azure-subtle mb-3">
                        <div class="fw-bold text-azure mb-1"><i class="fa-solid fa-thumbs-up me-1"></i> Le Super Pouvoir de la Carte Postale :</div>
                        <div class="small text-secondary">
                            C'est ultra pratique pour partager un endroit ! Tu peux copier l'adresse <code>?page=map&x=12&y=45</code> et l'envoyer à ton ami sur Discord. Quand il clique dessus, il arrive exactement devant la même oasis que toi !
                        </div>
                    </div>
                    <div class="p-3 bg-danger-lt rounded-3 border border-danger-subtle">
                        <div class="fw-bold text-danger mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Le Gros Danger :</div>
                        <div class="small text-secondary">
                            Tout le monde dans la rue peut lire la carte postale ! Si tu écris un mot de passe dans l'URL (<code>?password=secret123</code>), il s'affiche sur ton écran, reste gravé dans l'historique du navigateur et dans les journaux des serveurs. <strong>Ne mets JAMAIS de mot de passe dans $_GET !</strong>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-light border p-4 shadow-sm text-center">
                        <div class="avatar avatar-xl bg-azure text-white rounded-circle mx-auto mb-3 shadow-sm">
                            <i class="fa-solid fa-eye fs-1"></i>
                        </div>
                        <h4 class="text-dark fw-bold mb-2">Visible aux Yeux de Tous</h4>
                        <div class="bg-white p-2 rounded border font-monospace text-dark small mb-3">
                            https://monsite.fr/?<span class="text-azure fw-bold">recherche=samourai</span>&amp;<span class="text-azure fw-bold">ordre=rang</span>
                        </div>
                        <p class="text-secondary small mb-0">
                            Parfait pour : les filtres de recherche, les numéros de page (<code>&amp;page=2</code>), et les coordonnées de carte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 3 : LE MESSAGER $_POST (LA MISSIVE SCELLÉE DU NINJA)         -->
    <!-- ===================================================================== -->
    <div id="partie-post" class="card mb-4 border shadow-sm">
        <div class="card-status-top bg-dark"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-dark text-white rounded-circle fs-2"><i class="fa-solid fa-user-ninja"></i></span>
                <div>
                    <span class="badge bg-dark-lt text-dark text-uppercase fw-bold">Chapitre 3</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Messager <code>$_POST</code> : La Lettre Scellée du Ninja</h2>
                </div>
            </div>

            <div class="row g-4 align-items-center mb-4">
                <div class="col-lg-6">
                    <p class="fs-3 text-secondary" style="line-height: 1.7;">
                        La méthode <strong class="text-dark">$_POST</strong> est l'arme secrète du samouraï.
                    </p>
                    <p class="text-secondary" style="line-height: 1.7;">
                        Ici, rien du tout n'apparaît dans la barre d'adresse de ton navigateur ! Les données voyagent cachées <strong>à l'intérieur du corps de la requête HTTP</strong> (dans l'enveloppe secrète).
                    </p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-1 text-dark"><i class="fa-solid fa-envelope"></i></span>
                            <div>
                                <strong class="text-dark">La Lettre Scellée à la Cire :</strong>
                                <div class="text-secondary small mt-1">
                                    C'est un ninja discret qui porte une enveloppe cachetée. Les passants dans la rue ne voient rien de ce qui est écrit à l'intérieur.
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-secondary" style="line-height: 1.7;">
                        C'est la méthode obligatoire pour : les <strong>mots de passe de connexion</strong>, le <strong>recrutement d'une armée</strong>, ou l'<strong>envoi de ressources</strong> à un allié !
                    </p>
                </div>

                <div class="col-lg-6">
                    <!-- Tableau comparatif -->
                    <div class="card border shadow-sm overflow-hidden">
                        <div class="card-header bg-dark text-white fw-bold">
                            <i class="fa-solid fa-scale-balanced me-2"></i>Le Duel : $_GET vs $_POST
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter table-striped m-0">
                                <thead>
                                    <tr>
                                        <th>Caractéristique</th>
                                        <th class="text-azure"><i class="fa-solid fa-postcard me-1"></i>$_GET</th>
                                        <th class="text-dark"><i class="fa-solid fa-envelope me-1"></i>$_POST</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Visibilité</td>
                                        <td><span class="badge bg-warning-lt text-warning-emphasis">Public (dans l'URL)</span></td>
                                        <td><span class="badge bg-success-lt text-success">Secret (invisible)</span></td>
                                    </tr>
                                    <tr>
                                        <td>Partage d'adresse</td>
                                        <td><i class="fa-solid fa-check text-success me-1"></i>Possible (favoris)</td>
                                        <td><i class="fa-solid fa-xmark text-danger me-1"></i>Non partageable</td>
                                    </tr>
                                    <tr>
                                        <td>Capacité de taille</td>
                                        <td>Petite (~2 000 caractères)</td>
                                        <td>Énorme (photos, fichiers)</td>
                                    </tr>
                                    <tr>
                                        <td>Utilisation typique</td>
                                        <td>Consulter une page, filtrer</td>
                                        <td>Se connecter, attaquer, créer</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Encadré Le Savais-tu ? -->
            <div class="alert alert-info bg-azure-lt border border-azure-subtle mb-0">
                <div class="d-flex gap-3 align-items-start">
                    <i class="fa-solid fa-lightbulb text-warning fs-1"></i>
                    <div>
                        <strong class="text-dark d-block mb-1">Le savais-tu ? La limite de longueur d'une URL !</strong>
                        <div class="text-secondary small">
                            Les navigateurs web limitent la longueur des adresses URL à environ 2 000 caractères. Impossible d'envoyer la photo d'un samouraï avec <code>$_GET</code> ! En revanche, <code>$_POST</code> peut transporter plusieurs mégaoctets de données, parfait pour téléverser ton avatar de clan !
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 4 : LE MINI-QUIZ DÉFI DU GRAND MESSAGER                      -->
    <!-- ===================================================================== -->
    <div id="mini-quiz" class="card border-success shadow-sm mb-4">
        <div class="card-status-top bg-success"></div>
        <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
            <h3 class="card-title fw-bold m-0" style="font-family: 'Dela Gothic One', cursive, sans-serif;">
                <i class="fa-solid fa-graduation-cap me-2"></i>Le Défi de Gabriel : Teste tes Connaissances Réseau !
            </h3>
            <span class="badge bg-white text-success fw-bold">2 Questions</span>
        </div>
        <div class="card-body p-4">
            <p class="text-secondary mb-4">
                Montre que tu sais quel messager envoyer au bon moment en répondant aux deux questions du test !
            </p>

            <form id="quiz-routing-form">
                <!-- Question 1 -->
                <div class="card bg-light border p-3 mb-3">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-success text-white me-2">Question 1</span>
                        Pourquoi ne doit-on <span class="text-danger">JAMAIS</span> envoyer un mot de passe avec le messager <span class="text-azure">$_GET</span> ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_route_opt1" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_route_opt1">
                            Parce que les serveurs PHP s'endorment quand ils voient des majuscules.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_route_opt2" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q1_route_opt2">
                            Parce que le mot de passe s'afficherait en clair dans la barre d'adresse et l'historique du navigateur !
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q1" id="q1_route_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q1_route_opt3">
                            Parce que la souris risque d'exploser.
                        </label>
                    </div>
                    <div id="q1-feedback" class="mt-2 small d-none"></div>
                </div>

                <!-- Question 2 -->
                <div class="card bg-light border p-3 mb-4">
                    <h4 class="fw-bold text-dark mb-2">
                        <span class="badge bg-success text-white me-2">Question 2</span>
                        À quoi sert le <span class="text-success">Routeur</span> (le fichier <code>index.php</code>) ?
                    </h4>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_route_opt1" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_route_opt1">
                            À faire griller du pain pour le petit-déjeuner des samouraïs.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_route_opt2" value="correct">
                        <label class="form-check-label fw-bold text-dark" for="q2_route_opt2">
                            À regarder le paramètre <code>?page=...</code> pour charger et afficher la bonne vue du jeu demandée par le joueur.
                        </label>
                    </div>
                    <div class="form-check my-2">
                        <input class="form-check-input" type="radio" name="q2" id="q2_route_opt3" value="wrong">
                        <label class="form-check-label text-secondary" for="q2_route_opt3">
                            À couper la connexion Internet dès qu'on perd une bataille.
                        </label>
                    </div>
                    <div id="q2-feedback" class="mt-2 small d-none"></div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-success px-4 fw-bold" onclick="validateRoutingQuiz()">
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
            <a href="/?page=pedagogy&lesson=pdo-sql-injection" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Cours Précédent : Le Coffre-Fort PDO
            </a>
        </div>
        <div class="d-flex gap-2">
            <a href="/?page=pedagogy#section-cours-backend" class="btn btn-primary">
                <i class="fa-solid fa-graduation-cap me-1"></i> Retour à l'Atelier Pédagogique
            </a>
        </div>
    </div>
</div>

<script>
function validateRoutingQuiz() {
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
        f1.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Exactement !</strong> $_GET étale tout dans l\'URL comme sur une carte postale transparente !';
    } else {
        f1.classList.add('text-danger');
        f1.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Attention :</strong> Les paramètres $_GET apparaissent directement dans la barre d\'adresse, ce qui est très dangereux pour les secrets.';
    }

    // Q2 Check
    f2.classList.remove('d-none', 'text-success', 'text-danger');
    if (q2.value === 'correct') {
        score++;
        f2.classList.add('text-success');
        f2.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i><strong>Bravo !</strong> Le Routeur est bien le chef d\'orchestre qui lit les panneaux et charge la bonne page.';
    } else {
        f2.classList.add('text-danger');
        f2.innerHTML = '<i class="fa-solid fa-circle-xmark me-1"></i><strong>Oups :</strong> Le routeur est le fichier pivot qui oriente chaque requête vers son fichier de vue.';
    }

    if (score === 2) {
        scoreBox.innerHTML = '<span class="text-success fs-3"><i class="fa-solid fa-compass text-success me-1"></i>Score parfait : 2 / 2 ! Tu connais les ruelles du web comme un ninja aguerri !</span>';
    } else {
        scoreBox.innerHTML = '<span class="text-warning fs-4"><i class="fa-solid fa-rotate-right me-1"></i>Score : ' + score + ' / 2. Révise les panneaux et recommence !</span>';
    }
}
</script>
