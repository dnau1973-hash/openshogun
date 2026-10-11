<?php
/**
 * Cours Pédagogique : IA Vidéo & Prompting Cinématique — Le Trailer Officiel d'OpenShogun
 * Section : Atelier Pédagogique / Studio Multimédia & IA
 * Public : Gabriel (Apprenti Game Designer & Vidéaste) & Développeurs curieux
 * Charte : Tabler.io / Dela Gothic One / Thème clair & composants épurés
 */
declare(strict_types=1);

$lessonKey = 'ia-video-trailer';
$lessonTitle = "IA Vidéo & Prompting Cinématique : Créer le Trailer d'OpenShogun";
$lessonSubtitle = "De l'idée brute à l'animation cinématique : découvrez les secrets de fabrication d'un trailer de 70 secondes avec l'IA générative vidéo.";
?>

<div class="container-xl py-3 lesson-wrapper">
    <!-- Fil d'Ariane & Navigation Haute -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/?page=pedagogy" class="text-decoration-none"><i class="fa-solid fa-graduation-cap me-1"></i>Atelier Pédagogique</a></li>
            <li class="breadcrumb-item"><a href="/?page=pedagogy#section-ia-gemini" class="text-decoration-none">Univers Visuel &amp; IA</a></li>
            <li class="breadcrumb-item active" aria-current="page">Masterclass : IA Vidéo &amp; Trailer</li>
        </ol>
    </nav>

    <!-- En-tête Hero du Cours -->
    <div class="card mb-4 border shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);">
        <div class="card-status-top bg-warning"></div>
        <div class="card-body p-4 p-md-5 text-white position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-warning text-dark fw-bold small">
                    <i class="fa-solid fa-clapperboard"></i> Studio Créatif &bull; Masterclass Vidéo (Veo &bull; Runway &bull; Kling)
                </div>
                <div class="text-white-50 small">
                    <i class="fa-solid fa-clock me-1"></i> Temps d'apprentissage : 12 minutes &bull; <i class="fa-solid fa-trophy text-warning ms-2 me-1"></i>+50 XP Directeur Artistique
                </div>
            </div>

            <h1 class="display-6 fw-bold mb-3 text-white" style="font-family: 'Dela Gothic One', cursive, sans-serif; letter-spacing: 0.5px;">
                <span class="text-warning"><i class="fa-solid fa-film me-2"></i></span><?= htmlspecialchars($lessonTitle) ?>
            </h1>
            <p class="fs-3 text-light opacity-90 mb-4" style="max-width: 860px; line-height: 1.6;">
                <?= htmlspecialchars($lessonSubtitle) ?>
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="#partie-principes-video" class="btn btn-warning text-dark fw-bold rounded-pill">
                    <i class="fa-solid fa-book-sparkles me-1"></i> 1. Anatomie d'un Prompt Vidéo
                </a>
                <a href="#partie-narration" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-scroll me-1"></i> 2. La Narration en 6 Actes
                </a>
                <a href="#partie-prompts-prets" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-terminal me-1"></i> 3. Les 6 Prompts Prêts à l'Emploi
                </a>
                <a href="#partie-pipeline-montage" class="btn btn-outline-light rounded-pill">
                    <i class="fa-solid fa-scissors me-1"></i> 4. Montage &amp; Conseils Gabriel
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 1 : LES PRINCIPES FONDAMENTAUX DU PROMPTING VIDÉO            -->
    <!-- ===================================================================== -->
    <div id="partie-principes-video" class="card mb-4 border shadow-sm bg-white">
        <div class="card-status-top bg-primary"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-primary-lt text-primary rounded-circle fs-2"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <div>
                    <span class="badge bg-primary-lt text-primary text-uppercase fw-bold">Chapitre 1</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Comment une IA Génère-t-elle une Vidéo ?</h2>
                </div>
            </div>

            <p class="fs-3 text-secondary mb-4" style="line-height: 1.7;">
                Générer une image fixe avec Midjourney ou Gemini est déjà fascinant. Mais générer une <strong>vidéo cinématique fluide</strong> demande de franchir une dimension supplémentaire : <strong>le temps et la physique du mouvement</strong>.
            </p>

            <div class="row g-4 mb-4">
                <!-- Concept 1 : La Formule Magique -->
                <div class="col-lg-6">
                    <div class="card h-100 bg-light border p-4 shadow-none">
                        <h3 class="card-title text-primary d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-code"></i> La Formule Canonique du Prompt Vidéo
                        </h3>
                        <p class="small text-secondary mb-3">
                            Pour qu'un modèle vidéo (Google Veo, Runway Gen-3, Kling, Sora) ne produise pas une bouillie de pixels tremblotante, il faut lui donner des <strong>instructions de réalisateur de cinéma</strong> selon cette formule rigoureuse :
                        </p>
                        <div class="p-3 bg-dark text-white rounded font-monospace small mb-3">
                            <span class="text-warning">[Style &amp; Rendu]</span> + 
                            <span class="text-info">[Sujet &amp; Action]</span> + 
                            <span class="text-success">[Mouvement de Caméra]</span> + 
                            <span class="text-danger">[Lumière &amp; Atmosphère]</span> + 
                            <span class="text-secondary">[Paramètres 24fps / 4K]</span>
                        </div>
                        <ul class="list-unstyled small text-secondary space-y-2 mb-0">
                            <li><strong class="text-dark">&bull; Style &amp; Rendu :</strong> <em>« ukiyo-e woodblock painting mixed with semi-realistic feudal RTS game graphics »</em> (assure la charte OpenShogun).</li>
                            <li><strong class="text-dark">&bull; Action précise :</strong> Un seul verbe d'action net (ex: <em>harvesting rice with sickles</em>, <em>charging at full gallop</em>).</li>
                            <li><strong class="text-dark">&bull; Caméra dynamique :</strong> Indiquer le mouvement physique de la caméra (<em>dolly</em>, <em>pan</em>, <em>tilt-up</em>, <em>tracking</em>).</li>
                        </ul>
                    </div>
                </div>

                <!-- Concept 2 : Text-to-Video vs Image-to-Video -->
                <div class="col-lg-6">
                    <div class="card h-100 bg-light border p-4 shadow-none">
                        <h3 class="card-title text-purple d-flex align-items-center gap-2 mb-3">
                            <i class="fa-solid fa-image"></i> Le Grand Secret : Image-to-Video (I2V)
                        </h3>
                        <p class="small text-secondary mb-3">
                            En production professionnelle de jeu vidéo, on utilise rarement le <strong>Text-to-Video (T2V) brut</strong> d'une traite car l'IA a tendance à réinventer l'apparence des personnages à chaque plan.
                        </p>
                        <div class="alert alert-purple bg-purple-lt border-0 p-3 mb-3">
                            <strong class="d-block mb-1"><i class="fa-solid fa-lightbulb me-1"></i>La méthode recommandée pour OpenShogun :</strong>
                            <ol class="m-0 ps-3 small text-secondary">
                                <li><strong>Étape 1 :</strong> Générer l'image clé fixe haute résolution (dans l'univers estampe de notre Terroir).</li>
                                <li><strong>Étape 2 :</strong> Injecter cette image comme <em>First Frame</em> dans Runway Gen-3 ou Kling AI.</li>
                                <li><strong>Étape 3 :</strong> Ajouter le prompt décrivant uniquement le mouvement de caméra et l'action.</li>
                            </ol>
                        </div>
                        <p class="small text-secondary mb-0">
                            <strong>Résultat garanti :</strong> Le samouraï conserve son casque exact, le château conserve son architecture exacte, et l'animation reste d'une cohérence absolue d'un plan à l'autre !
                        </p>
                    </div>
                </div>
            </div>

            <!-- Lexique des Mouvements de Caméra Cinématographiques -->
            <div class="card border border-primary-subtle p-3 bg-surface">
                <h4 class="card-title text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-video text-primary"></i> Le Lexique du Réalisateur (À glisser dans vos prompts)
                </h4>
                <div class="row g-2 text-secondary small">
                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <strong class="text-dark d-block">Tilt-Up (Contre-plongée montante)</strong>
                            Caméra fixe qui pivote de bas en haut. Idéal pour révéler la puissance du samouraï, de son armure jusqu'à son masque.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <strong class="text-dark d-block">Tracking Shot (Plan suiveur)</strong>
                            La caméra se déplace à la même vitesse que le sujet au ras du sol. Parfait pour la cavalcade galopante des chevaux.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 bg-light rounded">
                            <strong class="text-dark d-block">Isometric 45° Timelapse</strong>
                            Vue plongeante en plongée tactique RTS avec défilement accéléré du temps. Essentiel pour montrer le village grandir.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 2 : LA NARRATION RÉÉCRITE EN 6 ACTES                         -->
    <!-- ===================================================================== -->
    <div id="partie-narration" class="card mb-4 border shadow-sm bg-white">
        <div class="card-status-top bg-success"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-success-lt text-success rounded-circle fs-2"><i class="fa-solid fa-scroll"></i></span>
                <div>
                    <span class="badge bg-success-lt text-success text-uppercase fw-bold">Chapitre 2</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Storyboard Réécrit : Une Montée Dramatique (70 sec)</h2>
                </div>
            </div>

            <p class="fs-3 text-secondary mb-4" style="line-height: 1.7;">
                Voici la version scénarisée des idées de <strong>David &amp; Gabriel</strong>. Pour qu'une bande-annonce captive les joueurs, elle doit suivre la règle de la <strong>courbe d'intensité</strong> : partir du calme contemplatif du labeur rural, faire monter la tension guerrière, montrer le génie stratégique du joueur (construction), et exploser dans un choc de siège titanesque.
            </p>

            <div class="table-responsive mb-4">
                <table class="table table-vcenter table-bordered bg-white">
                    <thead class="table-light text-uppercase font-monospace" style="font-size: 0.75rem;">
                        <tr>
                            <th style="width: 90px;">Chrono</th>
                            <th style="width: 140px;">Acte</th>
                            <th>Description Scénique &amp; Visuelle</th>
                            <th style="width: 250px;">Ambiance Sonore &amp; Musique</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        <tr>
                            <td class="font-monospace fw-bold text-primary">00:00 - 00:12</td>
                            <td><span class="badge bg-teal-lt">1. La Terre</span></td>
                            <td>
                                <strong>La Paix &amp; le Labeur Féodal :</strong> Paysans aux chapeaux coniques coupant le riz doré dans l'eau miroitante &bull; Bûcherons abattant un noble cèdre dans les rais de lumière &bull; Carriers fendant le granit à la montagne.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Flûte <em>Shakuhachi</em> solitaire et venteuse. Sons d'eau, bruits de faux, écho des coups de hache et ciseaux à pierre.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-primary">00:12 - 00:22</td>
                            <td><span class="badge bg-danger-lt">2. Le Guerrier</span></td>
                            <td>
                                <strong>L'Ombre du Seigneur :</strong> Manoir shōin-zukuri feutré. Mains laçant l'armure pectorale. La caméra remonte le torse en <em>tilt-up</em> et se fige sur le masque de guerre (<em>Mempō</em>) menaçant aux crocs d'argent.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Froissement de soie, cliquetis de plaques d'acier. Battement de cœur lourd (<em>sub-bass</em>). Tension d'un <em>Shamisen</em> pincé.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-primary">00:22 - 00:33</td>
                            <td><span class="badge bg-warning-lt">3. La Menace</span></td>
                            <td>
                                <strong>L'Alerte &amp; le Galop :</strong> Le paysan s'arrête, s'essuie le front et scrute l'horizon avec terreur. Cut violent : charge frénétique de cavalerie samouraï fendant les hautes herbes, bannières dorsales claquantes.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Silence brutal puis grondement de tonnerre : premiers roulements de tambours <em>Taiko</em> rythmés par le galop sourd.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-primary">00:33 - 00:46</td>
                            <td><span class="badge bg-primary-lt">4. L'Expansion</span></td>
                            <td>
                                <strong>Le Gameplay RTS en Timelapse :</strong> Vue aérienne à 45°. En accéléré fulgurant, les maisons minka poussent, les remparts et tours d'archers s'élèvent, le Donjon Tenshu s'érige étage par étage.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Accélération euphorique du tempo. Sons de marteaux, charpentes et bâtisseurs mixés dans une cadence stimulante.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-primary">00:46 - 00:54</td>
                            <td><span class="badge bg-indigo-lt">5. La Prière</span></td>
                            <td>
                                <strong>La Ferveur Divine :</strong> Clairière mystique d'un sanctuaire shintō crépusculaire. Un noble en kimono prie devant l'autel, torii rouge vif baigné de lanternes, volutes d'encens.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Cloche de temple bouddhique (<em>Kansho</em>) profonde, deux claquements de mains rituels résonnant dans l'écho.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-danger">00:54 - 01:12</td>
                            <td><span class="badge bg-danger text-white">6. Le Siège</span></td>
                            <td>
                                <strong>Bataille Nocturne Apocalyptique :</strong> Catapultes projetant des boules de feu fracassant les palissades. Nuée de flèches enflammées zébrant le ciel. Mêlée de lances, étincelles d'acier et charges d'infanterie.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Orchestration symphonique complète : déferlement de <em>Taikos</em> de guerre, chœurs martiaux japonais féroces, cris de ralliement.
                            </td>
                        </tr>
                        <tr>
                            <td class="font-monospace fw-bold text-dark">01:12 - 01:20</td>
                            <td><span class="badge bg-dark text-white">Outro</span></td>
                            <td>
                                <strong>Logo &amp; Titre :</strong> Coup de sabre tranchant l'écran noir dans une gerbe d'étincelles. Révélation d'<strong>OPENSHOGUN</strong> en kanji &amp; lettrage doré d'estampe.
                            </td>
                            <td class="text-secondary small">
                                <i class="fa-solid fa-music me-1"></i>Coup de gong résonnant et fondu au noir.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 3 : LES 6 PROMPTS VIDÉO OFFICIELS PRÊTS À L'EMPLOI            -->
    <!-- ===================================================================== -->
    <div id="partie-prompts-prets" class="card mb-4 border shadow-sm bg-white">
        <div class="card-status-top bg-warning"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-warning-lt text-warning rounded-circle fs-2"><i class="fa-solid fa-terminal"></i></span>
                <div>
                    <span class="badge bg-warning-lt text-warning text-uppercase fw-bold">Chapitre 3</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Grimoire des 6 Prompts Vidéo (Veo &bull; Runway &bull; Kling)</h2>
                </div>
            </div>

            <p class="fs-3 text-secondary mb-4" style="line-height: 1.7;">
                Copiez directement ces prompts en anglais dans votre outil vidéo IA préféré. Chaque prompt a été optimisé avec les mots-clés stylistiques de la charte graphique d'OpenShogun.
            </p>

            <div class="space-y-4">

                <!-- Plan 1 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-wheat-awn text-warning me-1"></i>Plan 1 : Les Terroirs &amp; Moissons (00:00 - 00:12)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 8 à 10s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>Cinematic 4k feudal Japan documentary footage in ukiyo-e woodblock painting art style blended with semi-realistic game graphics. Golden hour morning sunlight piercing through mountain mist. Wide angle shot of peasant farmers wearing conical bamboo hats harvesting golden flooded rice terraces with hand sickles, water reflecting the morning sky. Smooth slow camera panning into a deep cedar forest where lumberjacks chop giant logs with axes, floating sawdust. Next cut reveals a mountain granite quarry with stonecutters using ropes and wooden cranes. Vivid colors, rich natural lighting, 24fps cinematic film grain.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>ukiyo-e woodblock painting</em> verrouille le style graphique &bull; <em>water reflecting morning sky</em> force le rendu réaliste de l'eau &bull; <em>24fps film grain</em> élimine l'aspect numérique artificiel.
                    </div>
                </div>

                <!-- Plan 2 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-shield-halved text-danger me-1"></i>Plan 2 : L'Armure du Daimyō (00:12 - 00:22)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 6 à 8s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>Interior of a traditional feudal Japanese castle room with golden screen paintings and tatami mats, soft candle rim light. Close-up slow tilt-up camera movement from the lacquered samurai breastplate with silk lacing up to an ornate Kabuto helmet with golden horns. Camera halts on a frightening ornate black and crimson samurai face mask (menpo) with silver fangs. Deep shadows, cinematic shallow depth of field, dramatic atmospheric dust particles, epic tension, ultra-detailed textures.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>slow tilt-up camera movement</em> ordonne le mouvement vertical fluide &bull; <em>shallow depth of field</em> floute l'arrière-plan pour focaliser le regard sur le masque de guerre menpo.
                    </div>
                </div>

                <!-- Plan 3 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-horse text-primary me-1"></i>Plan 3 : L'Alerte &amp; Charge de Cavalerie (00:22 - 00:33)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 8s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>First shot: Peasant farmer in flooded rice paddy stops harvesting, wipes sweaty brow, stands up looking towards the horizon with a terrified, alerted expression. Second shot: Low-angle tracking shot of a thunderous samurai cavalry squadron charging across a vast windy grassland at full gallop. Battle banners (sashimono) flapping fiercely in the wind, horses kicking up plumes of dust, sunlight glinting off drawn katanas and armor. Dynamic camera movement, high motion blur, epic cinematic realism.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>Low-angle tracking shot</em> place la caméra au niveau de l'herbe pour donner une sensation d'écrasement monumental &bull; <em>sashimono flapping</em> insuffle de la dynamique de vent.
                    </div>
                </div>

                <!-- Plan 4 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-city text-azure me-1"></i>Plan 4 : Timelapse de Construction RTS (00:33 - 00:46)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 8 à 10s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>High-angle aerial 45-degree isometric RTS view of a feudal Japanese hill domain. Fast-forward construction timelapse: empty clearings rapidly transform as stone foundations raise, wooden minka huts, storehouses, defensive palisades, and archery towers construct at high speed. The central grand multi-tiered Japanese castle keep (Tenshu) erects floor by floor with dark curved tile roofs. Dynamic moving sun shadows, stylized feudal strategy game presentation, crisp details.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>45-degree isometric RTS view</em> restitue fidèlement la perspective stratégique d'OpenShogun &bull; <em>Fast-forward construction timelapse</em> ordonne la cinématique d'élévation accélérée des bâtiments.
                    </div>
                </div>

                <!-- Plan 5 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-torii-gate text-indigo me-1"></i>Plan 5 : Prière au Sanctuaire Shintō (00:46 - 00:54)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 6s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>Serene Shinto sacred shrine grove surrounded by ancient giant mossy cedar trees at twilight. A noble figure wearing an elegant silk kimono and hakama bowing and praying reverently before a wooden altar. Vermilion red Torii gate glowing in soft lantern light, curling incense smoke, peaceful spiritual atmosphere, gentle breeze moving ceremonial shimenawa ropes, hyper-detailed traditional Japanese aesthetic.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>curling incense smoke</em> et <em>gentle breeze moving shimenawa</em> ajoutent des micro-mouvements subtils qui empêchent l'image de paraître figée.
                    </div>
                </div>

                <!-- Plan 6 -->
                <div class="card border p-3 bg-surface">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark"><i class="fa-solid fa-fire text-danger me-1"></i>Plan 6 : Siège Épique &amp; Choc des Flèches (00:54 - 01:12)</span>
                        <span class="badge bg-secondary-lt font-monospace">Durée cible : 10 à 12s</span>
                    </div>
                    <div class="p-3 bg-dark text-light rounded font-monospace small position-relative mb-2">
                        <code>Nightfall epic medieval castle siege battle in feudal Japan. Trebuchets and catapults launching giant burning fireballs crashing into fortified wooden walls with explosions of sparks. Fortified ramparts lined with samurai archers releasing volleys of flaming arrows arcing across the smoke-filled dark sky. Clashing spears, katana sparks, dramatic war atmosphere, cinematic handheld camera tracking through the battlefield, fiery embers and orange rim light, high octane climax.</code>
                        <button type="button" class="btn btn-sm btn-outline-warning position-absolute top-0 end-0 m-2" onclick="navigator.clipboard.writeText(this.previousElementSibling.innerText); this.innerText='Copié !'; setTimeout(() => this.innerText='Copier', 2000);">
                            Copier
                        </button>
                    </div>
                    <div class="text-secondary small">
                        <strong>Clés techniques :</strong> <em>flaming arrows arcing across dark sky</em> génère des trajectoires lumineuses spectaculaires &bull; <em>handheld camera tracking</em> transmet l'urgence et la fureur du combat rapproché.
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- CHAPITRE 4 : LE PIPELINE DE MONTAGE POUR GABRIEL                      -->
    <!-- ===================================================================== -->
    <div id="partie-pipeline-montage" class="card mb-4 border shadow-sm bg-white">
        <div class="card-status-top bg-teal"></div>
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="avatar avatar-lg bg-teal-lt text-teal rounded-circle fs-2"><i class="fa-solid fa-scissors"></i></span>
                <div>
                    <span class="badge bg-teal-lt text-teal text-uppercase fw-bold">Chapitre 4</span>
                    <h2 class="h1 fw-bold text-dark mb-0">Le Guide de Montage Pas-à-Pas (Pour Gabriel)</h2>
                </div>
            </div>

            <p class="fs-3 text-secondary mb-4" style="line-height: 1.7;">
                Générer les clips vidéo n'est que la première étape ! Voici la feuille de route pour que Gabriel puisse assembler la vidéo comme un véritable monteur de studio de jeu vidéo :
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-primary text-white mb-2">Étape 1</span>
                        <strong class="d-block text-dark mb-1">Génération des 6 Rushes</strong>
                        <p class="small text-secondary m-0">Générer 2 ou 3 variantes de chaque plan avec Veo ou Kling pour ne garder que la prise la plus propre (sans artefacts ni membres déformés).</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-success text-white mb-2">Étape 2</span>
                        <strong class="d-block text-dark mb-1">Montage au Beat (Rythme)</strong>
                        <p class="small text-secondary m-0">Importer dans CapCut ou DaVinci Resolve. Poser la musique d'abord ! Chaque changement de plan doit tomber précisément sur un coup de Taiko ou de cloche.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-warning text-dark mb-2">Étape 3</span>
                        <strong class="d-block text-dark mb-1">Sound Design (SFX)</strong>
                        <p class="small text-secondary m-0">Rajouter par-dessus les bruitages : le sifflement d'une flèche, le hennissement d'un cheval, le cliquetis de l'acier. Le son fait 50% de l'impact !</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border h-100">
                        <span class="badge bg-danger text-white mb-2">Étape 4</span>
                        <strong class="d-block text-dark mb-1">Titrage &amp; Call To Action</strong>
                        <p class="small text-secondary m-0">Ajouter le logo final OpenShogun avec un flash d'étincelles et la mention : <em>« Rejoignez la bêta sur openshogun.fr »</em>.</p>
                    </div>
                </div>
            </div>

            <!-- Mini Défi Pédagogique -->
            <div class="alert alert-info border border-info-subtle bg-info-lt p-4 mb-0">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-flag-checkered fs-2 text-info"></i>
                    <h4 class="mb-0 text-dark fw-bold">Défi Pédagogique Père-Fils : À vous de jouer !</h4>
                </div>
                <p class="small text-secondary mb-3">
                    Choisissez le Plan 2 (Le Samouraï qui enfile son armure) ou le Plan 4 (Le Timelapse du village). Testez le prompt dans votre moteur d'IA vidéo, ajustez la vitesse de caméra et partagez le clip pour valider le premier plan officiel d'OpenShogun !
                </p>
                <div class="d-flex gap-2">
                    <a href="/?page=pedagogy" class="btn btn-sm btn-outline-primary">
                        &larr; Retour au Sommaire de l'Atelier
                    </a>
                    <a href="/?page=resources" class="btn btn-sm btn-primary">
                        Revoir le Fief en Jeu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

