<?php
/**
 * Vue Dédiée : Forum Féodal & Décrets du Shogunat (OpenShogun)
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/ForumEngine.php';
require_once __DIR__ . '/../config/game_constants.php';

$auth = new Auth();
$user = $auth->getCurrentUser();

$forumEngine = new ForumEngine();
$isStaff = $auth->isStaff();
$isAdmin = $auth->isAdmin();
$isModerator = $auth->isModerator();

$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
$topicId = isset($_GET['topic']) ? (int)$_GET['topic'] : null;
$action = $_GET['action'] ?? null;
$pageNum = max(1, (int)($_GET['p'] ?? 1));

// Détermination de la sous-vue active
$currentView = 'index';
if ($action === 'new_topic' && $catId) {
    $currentView = 'new_topic';
} elseif ($topicId) {
    $currentView = 'topic';
} elseif ($catId) {
    $currentView = 'category';
}

// Récupération de l'ensemble des salons du forum
$categories = $forumEngine->getCategories();
?>

<!-- Feuille de styles Quill WYSIWYG pour le Forum Féodal -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
<style>
.ql-toolbar.ql-snow {
    border-color: #cbd5e1 !important;
    background: #f8fafc;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
}
.ql-container.ql-snow {
    border-color: #cbd5e1 !important;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
    font-family: inherit;
    font-size: 0.95rem;
}
.ql-editor {
    min-height: 120px;
    line-height: 1.7;
}
.post-content blockquote {
    border-left: 4px solid #3b82f6;
    background: rgba(59, 130, 246, 0.05);
    padding: 0.6rem 1rem;
    margin: 0.75rem 0;
    border-radius: 0 6px 6px 0;
    color: #475569;
}
.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 0.5rem 0;
}
</style>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Agora & Conseil de Guerre de l'Empire</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <i class="fa-solid fa-comments text-primary me-1"></i>Forum Féodal du Japon
            </h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <?php if ($currentView !== 'index'): ?>
                    <a href="/?page=forum" class="btn btn-outline-secondary">
                        ← Index du Forum
                    </a>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                    <button type="button" class="btn btn-info text-white d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="collapse" data-bs-target="#adminForumPanel" aria-expanded="false" aria-controls="adminForumPanel">
                        <i class="fa-solid fa-gear me-1"></i>Gérer les Salons <span class="badge bg-white text-info ms-1"><?= count($categories) ?></span>
                    </button>
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm" onclick="openCreateForumCategoryModal()">
                        <i class="fa-solid fa-plus me-1"></i>Nouveau Salon
                    </button>
                <?php elseif ($isStaff): ?>
                    <span class="badge bg-azure-lt px-3 py-2 fs-6 d-flex align-items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-info me-1"></i>Modération Féodale Active
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="forumAlertBox"></div>

<?php if ($isAdmin): ?>
    <!-- ================================================================= -->
    <!-- PANNEAU RÉTRACTABLE D'ADMINISTRATION & GESTION DES SALONS (ADMIN) -->
    <!-- ================================================================= -->
    <div class="collapse mb-4 <?= (isset($_GET['manage_salons'])) ? 'show' : '' ?>" id="adminForumPanel">
        <div class="card border-info shadow-sm">
            <div class="card-header bg-info-lt d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h3 class="card-title text-info-emphasis d-flex align-items-center gap-2 m-0">
                        <i class="fa-solid fa-gear text-secondary me-1"></i>Administration &amp; Modération des Salons Féodaux
                    </h3>
                    <div class="text-secondary small mt-1">
                        Gérez l'ordonnancement, les thématiques et les droits d'accès des salons. Les salons verrouillés (<i class="fa-solid fa-lock text-secondary"></i>) sont réservés aux décrets officiels du Shōgunat.
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openCreateForumCategoryModal()">
                        <i class="fa-solid fa-plus me-1"></i>Créer un Salon
                    </button>
                    <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#adminForumPanel" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr class="bg-light">
                                <th style="width: 50px;" class="text-center">Icône</th>
                                <th>Nom du Salon</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 80px;">Ordre</th>
                                <th class="text-center" style="width: 150px;">Statut d'Accès</th>
                                <th class="text-center" style="width: 120px;">Sujets / Msg</th>
                                <th class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Aucun salon configuré. Cliquez sur « Créer un Salon » ci-dessus.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $fCat): ?>
                                    <tr>
                                        <td class="text-center fs-3">
                                            <?php if (!empty($fCat['icon']) && (str_starts_with($fCat['icon'], 'fa-') || str_contains($fCat['icon'], 'fa-'))): ?>
                                                <i class="fa-solid <?= htmlspecialchars($fCat['icon']) ?>"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-comments text-danger"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <a href="/?page=forum&cat=<?= $fCat['id'] ?>" class="text-reset">
                                                <?= htmlspecialchars($fCat['name']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($fCat['description'] ?? '') ?></td>
                                        <td class="text-center fw-bold"><?= (int)$fCat['display_order'] ?></td>
                                        <td class="text-center">
                                            <?php if ((int)$fCat['is_locked'] === 1): ?>
                                                <span class="badge bg-secondary-lt"><i class="fa-solid fa-lock me-1"></i>Staff Uniquement</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-lt"><i class="fa-solid fa-comments me-1"></i>Ouvert à Tous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small">
                                            <strong><?= number_format($fCat['topic_count']) ?></strong> suj. / <?= number_format($fCat['post_count']) ?> msg
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    title="Modifier ce salon"
                                                    onclick="openEditForumCategoryModal(<?= $fCat['id'] ?>, '<?= htmlspecialchars(addslashes($fCat['name'])) ?>', '<?= htmlspecialchars(addslashes($fCat['description'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($fCat['icon'])) ?>', <?= (int)$fCat['display_order'] ?>, <?= (int)$fCat['is_locked'] ?>)">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    title="Supprimer ce salon"
                                                    onclick="deleteForumCategory(<?= $fCat['id'] ?>, '<?= htmlspecialchars(addslashes($fCat['name'])) ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($isModerator): ?>
    <!-- Notice d'habilitation Modérateur -->
    <div class="alert alert-info alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="fa-solid fa-shield-halved text-info fs-2"></i>
        <div>
            <strong>Rang de Modérateur Féodal :</strong> Vous êtes investi de l'autorité du Shōgunat pour modérer les échanges, épingler les annonces cruciales, verrouiller les débats clos et corriger les outrages.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($currentView === 'index'): ?>
    <!-- ========================================== -->
    <!-- 1. INDEX DU FORUM : LISTE DES CATÉGORIES   -->
    <!-- ========================================== -->

    <div class="card">
        <div class="card-header bg-light">
            <h3 class="card-title d-flex align-items-center gap-2">
                <i class="fa-solid fa-chess-rook text-danger me-1"></i>Salons de Discussion &amp; Décrets Impériaux
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02);">
                        <th style="width: 50px;"></th>
                        <th>Salon Féodal</th>
                        <th class="text-center" style="width: 110px;">Sujets</th>
                        <th class="text-center" style="width: 110px;">Messages</th>
                        <th style="width: 280px;">Dernière Proclamation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="text-center" style="font-size: 1.8rem; padding-right: 0;">
                                <?php if (!empty($cat['icon']) && (str_starts_with($cat['icon'], 'fa-') || str_contains($cat['icon'], 'fa-'))): ?>
                                    <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-comments text-danger"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="/?page=forum&cat=<?= $cat['id'] ?>" class="fw-bold text-decoration-none fs-5">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                    <?php if ((int)$cat['is_locked'] === 1): ?>
                                        <span class="badge bg-secondary-lt" title="Salon réservé aux proclamations du Shogunat"><i class="fa-solid fa-lock me-1"></i>Officiel</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small mt-1">
                                    <?= htmlspecialchars($cat['description'] ?? '') ?>
                                </div>
                            </td>
                            <td class="text-center fw-bold fs-6">
                                <?= number_format($cat['topic_count']) ?>
                            </td>
                            <td class="text-center text-muted">
                                <?= number_format($cat['post_count']) ?>
                            </td>
                            <td>
                                <?php if (!empty($cat['last_topic_id'])): ?>
                                    <div class="text-truncate" style="max-width: 260px;">
                                        <a href="/?page=forum&topic=<?= $cat['last_topic_id'] ?>" class="text-reset fw-medium" title="<?= htmlspecialchars($cat['last_topic_title']) ?>">
                                            <?= htmlspecialchars($cat['last_topic_title']) ?>
                                        </a>
                                    </div>
                                    <div class="small text-muted">
                                        Par <strong><?= htmlspecialchars($cat['last_poster_username'] ?? 'Daimyō') ?></strong> · <?= date('d/m H:i', strtotime($cat['last_activity'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small"><em>Aucun sujet</em></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($currentView === 'category'): ?>
    <!-- ========================================== -->
    <!-- 2. VUE D'UN SALON (LISTE DES SUJETS)       -->
    <!-- ========================================== -->
    <?php
    $cat = $forumEngine->getCategory($catId);
    if (!$cat) {
        echo "<div class='alert alert-danger'>Ce salon féodal n'existe pas ou a été dissous.</div>";
        return;
    }
    $topicsData = $forumEngine->getTopics($catId, $pageNum, 20);
    $topics = $topicsData['topics'];
    $canCreateTopic = ((int)$cat['is_locked'] === 0 || $isStaff);
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/?page=forum">Forum</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($cat['name']) ?></li>
                </ol>
            </nav>
            <h3 class="mb-0 d-flex align-items-center gap-2">
                <span><?= $cat['icon'] ?></span> <?= htmlspecialchars($cat['name']) ?>
                <?php if ((int)$cat['is_locked'] === 1): ?>
                    <span class="badge bg-secondary-lt fs-6"><i class="fa-solid fa-lock me-1"></i>Salon Officiel</span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($isAdmin): ?>
                <button type="button" class="btn btn-outline-info" onclick="openEditForumCategoryModal(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($cat['icon'])) ?>', <?= (int)$cat['display_order'] ?>, <?= (int)$cat['is_locked'] ?>)">
                    <i class="fa-solid fa-gear me-1"></i>Paramètres du Salon
                </button>
            <?php endif; ?>
            <?php if ($canCreateTopic): ?>
                <a href="/?page=forum&action=new_topic&cat=<?= $cat['id'] ?>" class="btn btn-primary">
                    <i class="fa-solid fa-pen-nib me-1"></i>Proclamer un Sujet
                </a>
            <?php else: ?>
                <button class="btn btn-secondary disabled" title="Seuls les membres du Shogunat peuvent proclamer des décrets ici">
                    <i class="fa-solid fa-lock me-1"></i>Décrets Réservés au Staff
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02);">
                        <th style="width: 40px;"></th>
                        <th>Sujet de Discussion</th>
                        <th class="text-center" style="width: 100px;">Réponses</th>
                        <th class="text-center" style="width: 90px;">Vues</th>
                        <th style="width: 220px;">Dernière Réponse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topics)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                Aucun sujet n'a encore été ouvert dans ce salon.
                                <?php if ($canCreateTopic): ?>
                                    <div class="mt-2">
                                        <a href="/?page=forum&action=new_topic&cat=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            Ouvrir la première discussion
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topics as $t): ?>
                            <?php
                            $isPinned = ((int)$t['is_pinned'] === 1);
                            $isLocked = ((int)$t['is_locked'] === 1);
                            ?>
                            <tr style="<?= $isPinned ? 'background: rgba(234, 179, 8, 0.05);' : '' ?>">
                                <td class="text-center fs-4">
                                    <?php if ($isPinned): ?>
                                        <i class="fa-solid fa-thumbtack text-danger" title="Sujet Épinglé"></i>
                                    <?php elseif ($isLocked): ?>
                                        <i class="fa-solid fa-lock text-secondary" title="Sujet Verrouillé"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-comment text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="/?page=forum&topic=<?= $t['id'] ?>" class="fw-bold text-decoration-none <?= $isPinned ? 'text-warning-emphasis' : '' ?>">
                                            <?= htmlspecialchars($t['title']) ?>
                                        </a>
                                        <?php if ($isPinned): ?>
                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Épinglé</span>
                                        <?php endif; ?>
                                        <?php if ($isLocked): ?>
                                            <span class="badge bg-secondary" style="font-size: 0.65rem;">Verrouillé</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Initié par 
                                        <strong><?= htmlspecialchars($t['author_username']) ?></strong>
                                        <?php if (!empty($t['author_alliance_tag'])): ?>
                                            <span class="badge bg-danger-lt py-0">[<?= htmlspecialchars($t['author_alliance_tag']) ?>]</span>
                                        <?php endif; ?>
                                        · <?= date('d/m/Y', strtotime($t['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="text-center fw-bold text-secondary">
                                    <?= number_format(max(0, (int)$t['post_count'] - 1)) ?>
                                </td>
                                <td class="text-center text-muted small">
                                    <?= number_format($t['views_count']) ?>
                                </td>
                                <td>
                                    <div class="small">
                                        Par <strong><?= htmlspecialchars($t['last_poster_username'] ?? $t['author_username']) ?></strong>
                                    </div>
                                    <div class="small text-muted">
                                        <?= date('d/m/Y à H:i', strtotime($t['last_post_at'])) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($topicsData['pages'] > 1): ?>
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $topicsData['pages']; $p++): ?>
                <li class="page-item <?= ($p === $pageNum) ? 'active' : '' ?>">
                    <a class="page-link" href="/?page=forum&cat=<?= $catId ?>&p=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    <?php endif; ?>

<?php elseif ($currentView === 'topic'): ?>
    <!-- ========================================== -->
    <!-- 3. VUE D'UN SUJET (FIL DE MESSAGES)        -->
    <!-- ========================================== -->
    <?php
    $topic = $forumEngine->getTopic($topicId);
    if (!$topic) {
        echo "<div class='alert alert-danger'>Ce sujet est introuvable ou a été supprimé.</div>";
        return;
    }
    // Incrémenter les vues
    $forumEngine->incrementViews($topicId);

    $postsData = $forumEngine->getPosts($topicId, $pageNum, 15);
    $posts = $postsData['posts'];
    $isTopicLocked = ((int)$topic['is_locked'] === 1);
    $canReply = (!$isTopicLocked || $isStaff);
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="/?page=forum">Forum</a></li>
                    <li class="breadcrumb-item"><a href="/?page=forum&cat=<?= $topic['category_id'] ?>"><?= htmlspecialchars($topic['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($topic['title']) ?></li>
                </ol>
            </nav>
            <h2 class="mb-0 d-flex align-items-center gap-2">
                <span><?= ((int)$topic['is_pinned'] === 1) ? '<i class="fa-solid fa-thumbtack text-danger"></i>' : '<i class="fa-solid fa-comment text-secondary"></i>' ?></span>
                <?= htmlspecialchars($topic['title']) ?>
                <?php if ($isTopicLocked): ?>
                    <span class="badge bg-secondary fs-6"><i class="fa-solid fa-lock me-1"></i>Verrouillé</span>
                <?php endif; ?>
            </h2>
        </div>

        <!-- Outils de Modération Féodale (Staff) -->
        <?php if ($isStaff): ?>
            <div class="btn-list">
                <button class="btn btn-sm <?= ((int)$topic['is_pinned'] === 1) ? 'btn-warning' : 'btn-outline-warning' ?>" 
                        onclick="togglePinTopic(<?= $topic['id'] ?>)">
                    <i class="fa-solid fa-thumbtack me-1"></i><?= ((int)$topic['is_pinned'] === 1) ? 'Désépingler' : 'Épingler' ?>
                </button>
                <button class="btn btn-sm <?= $isTopicLocked ? 'btn-secondary' : 'btn-outline-secondary' ?>" 
                        onclick="toggleLockTopic(<?= $topic['id'] ?>)">
                    <i class="fa-solid fa-lock me-1"></i><?= $isTopicLocked ? 'Déverrouiller' : 'Verrouiller' ?>
                </button>
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteTopic(<?= $topic['id'] ?>, <?= $topic['category_id'] ?>)">
                    <i class="fa-solid fa-trash me-1"></i>Supprimer le Sujet
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Fil des messages -->
    <?php foreach ($posts as $post): ?>
        <?php
        $isAuthor = ((int)$post['user_id'] === (int)$user['id']);
        $postAuthorIsAdmin = ((int)$post['author_is_admin'] === 1);
        $postAuthorIsMod = ((int)$post['author_is_moderator'] === 1);
        $authorFaction = FACTIONS[$post['author_faction']] ?? FACTIONS['terran'];
        ?>
        <div class="card mb-3" id="post-<?= $post['id'] ?>" style="border-left: 4px solid <?= $postAuthorIsAdmin ? '#dc2626' : ($postAuthorIsMod ? '#0284c7' : '#94a3b8') ?>;">
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Volet Auteur -->
                    <div class="col-md-3 p-3 text-center border-end bg-light d-flex flex-column align-items-center justify-content-start">
                        <div class="mb-2">
                            <i class="fa-solid fa-chess-rook text-danger" style="font-size: 2.5rem;"></i>
                        </div>
                        <a href="/?page=poster&id=<?= (int)$post['user_id'] ?>" class="fw-bold fs-5 text-decoration-none text-dark" title="Consulter l'Affiche Féodale">
                            <?= htmlspecialchars($post['author_username']) ?>
                        </a>

                        <!-- Badges de Rang Féodal -->
                        <div class="my-1">
                            <?php if ($postAuthorIsAdmin): ?>
                                <span class="badge bg-danger text-white"><i class="fa-solid fa-star me-1"></i>ADMINISTRATEUR</span>
                            <?php elseif ($postAuthorIsMod): ?>
                                <span class="badge bg-info text-white"><i class="fa-solid fa-shield-halved me-1"></i>MODÉRATEUR</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt">Daimyō</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($post['author_alliance_tag'])): ?>
                            <div class="mb-1">
                                <span class="badge bg-danger-lt">[<?= htmlspecialchars($post['author_alliance_tag']) ?>]</span>
                            </div>
                        <?php endif; ?>

                        <div class="small text-muted mt-2">
                            <div><?= $authorFaction['icon'] ?> <?= htmlspecialchars($authorFaction['name']) ?></div>
                            <div><i class="fa-solid fa-trophy text-warning me-1"></i><?= number_format($post['author_points']) ?> pts</div>
                            <div><i class="fa-solid fa-chess-rook text-danger me-1"></i><?= $post['author_planet_count'] ?> fief(s)</div>
                        </div>
                    </div>

                    <!-- Corps du Message -->
                    <div class="col-md-9 p-3 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom small text-muted">
                                <div>
                                    Publié le <?= date('d/m/Y à H:i:s', strtotime($post['created_at'])) ?>
                                    <?php if (!empty($post['edited_at'])): ?>
                                        <span class="fst-italic ms-2" title="Modifié le <?= date('d/m/Y H:i', strtotime($post['edited_at'])) ?>">
                                            (modifié par <?= htmlspecialchars($post['editor_username'] ?? 'staff') ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="badge bg-light text-secondary">#<?= $post['id'] ?></span>
                                </div>
                            </div>

                            <div class="post-content" id="post-content-<?= $post['id'] ?>" style="line-height: 1.7; font-size: 0.95rem;"><?= $post['content'] ?></div>
                        </div>

                        <!-- Barre d'action du message -->
                        <div class="pt-3 mt-3 border-top d-flex justify-content-end gap-2">
                            <?php if ($canReply): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="quotePost('<?= htmlspecialchars(addslashes($post['author_username'])) ?>', <?= $post['id'] ?>)">
                                    <i class="fa-solid fa-quote-left me-1"></i>Citer
                                </button>
                            <?php endif; ?>

                            <?php if ($isAuthor || $isStaff): ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= $post['id'] ?>)">
                                    <i class="fa-solid fa-pen me-1"></i>Éditer
                                </button>
                            <?php endif; ?>

                            <?php if (($isAuthor || $isStaff) && (int)$post['is_first_post'] === 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePost(<?= $post['id'] ?>)">
                                    <i class="fa-solid fa-trash me-1"></i>Supprimer
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Formulaire de Réponse Rapide -->
    <?php if ($canReply): ?>
        <div class="card mb-4" id="replyFormContainer">
            <div class="card-header bg-light">
                <h4 class="card-title d-flex align-items-center gap-2">
                    <i class="fa-solid fa-pen-nib text-primary me-1"></i>Inscrire une Réponse au Débat
                </h4>
            </div>
            <div class="card-body">
                <form id="formReply" onsubmit="submitReply(event)">
                    <div class="mb-3">
                        <div id="replyQuillEditor" style="min-height: 160px; background: #ffffff;"></div>
                        <input type="hidden" id="replyContent">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="btnSubmitReply">
                            <i class="fa-solid fa-scroll me-1"></i>Sceller &amp; Publier la Réponse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary text-center py-3">
            <i class="fa-solid fa-lock text-secondary me-1"></i>Ce sujet a été verrouillé par la modération féodale. Les débats sont clos.
        </div>
    <?php endif; ?>

<?php elseif ($currentView === 'new_topic'): ?>
    <!-- ========================================== -->
    <!-- 4. FORMULAIRE DE CRÉATION DE SUJET         -->
    <!-- ========================================== -->
    <?php
    $cat = $forumEngine->getCategory($catId);
    if (!$cat) {
        echo "<div class='alert alert-danger'>Salon introuvable.</div>";
        return;
    }
    ?>

    <div class="card">
        <div class="card-header bg-light">
            <h3 class="card-title d-flex align-items-center gap-2">
                <i class="fa-solid fa-pen-nib text-primary me-1"></i>Proclamer un Nouveau Sujet dans : <?= htmlspecialchars($cat['name']) ?>
            </h3>
        </div>
        <div class="card-body">
            <form id="formNewTopic" onsubmit="submitNewTopic(event)">
                <div class="mb-3">
                    <label class="form-label required">Titre du Sujet</label>
                    <input type="text" id="topicTitle" class="form-control" placeholder="Titre concis et solennel" minlength="3" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Message d'Ouverture</label>
                    <div id="topicQuillEditor" style="min-height: 250px; background: #ffffff;"></div>
                    <input type="hidden" id="topicContent">
                </div>
                <div class="d-flex justify-content-between">
                    <a href="/?page=forum&cat=<?= $cat['id'] ?>" class="btn btn-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTopic">
                        <i class="fa-solid fa-bullhorn me-1"></i>Proclamer le Sujet sur le Forum
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<!-- Modale d'Édition de Message -->
<div class="modal modal-blur fade" id="modalEditPost" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen text-primary me-1"></i>Édition du Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editPostId" value="">
                <div id="editPostQuillEditor" style="min-height: 200px; background: #ffffff;"></div>
                <input type="hidden" id="editPostContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitEditPost()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modale de Création de Salon Féodal -->
<div class="modal modal-blur fade" id="modalCreateAdminForumCategory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form onsubmit="submitAdminCreateCategory(event)">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-plus text-primary me-1"></i>Fonder un Nouveau Salon Féodal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label required">Icône (Font Awesome)</label>
                            <input type="text" id="create_afc_icon" class="form-control text-center" value="fa-comments" placeholder="fa-comments" required>
                        </div>
                        <div class="col-8 mb-3">
                            <label class="form-label required">Titre du Salon</label>
                            <input type="text" id="create_afc_name" class="form-control" placeholder="Ex: Maison de Thé & Sérénité" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description d'Accompagnement</label>
                        <input type="text" id="create_afc_desc" class="form-control" placeholder="Brève explication de la thématique du salon...">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ordre d'Affichage</label>
                            <input type="number" id="create_afc_order" class="form-control" value="10">
                        </div>
                        <div class="col-6 mb-3 d-flex align-items-center pt-3">
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="create_afc_locked">
                                <span class="form-check-label small fw-bold"><i class="fa-solid fa-lock me-1"></i>Décrets Staff</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnAdminCreateCatModal">Créer le Salon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modale d'Édition de Catégorie de Forum -->
<div class="modal modal-blur fade" id="modalEditAdminForumCategory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form onsubmit="submitAdminEditCategory(event)">
                <div class="modal-header bg-info-lt">
                    <h5 class="modal-title"><i class="fa-solid fa-pen text-primary me-1"></i>Édition du Salon Féodal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_afc_id" value="">
                    <div class="row">
                        <div class="col-3 mb-3">
                            <label class="form-label required">Icône</label>
                            <input type="text" id="edit_afc_icon" class="form-control text-center" required>
                        </div>
                        <div class="col-9 mb-3">
                            <label class="form-label required">Titre du Salon</label>
                            <input type="text" id="edit_afc_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" id="edit_afc_desc" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ordre d'Affichage</label>
                            <input type="number" id="edit_afc_order" class="form-control">
                        </div>
                        <div class="col-6 mb-3 d-flex align-items-center pt-3">
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_afc_locked">
                                <span class="form-check-label small fw-bold"><i class="fa-solid fa-lock me-1"></i>Décrets Staff</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnAdminEditCatModal">Enregistrer les Modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Script Quill WYSIWYG CDN -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<!-- JavaScript d'interaction AJAX du Forum -->
<script>
const quillToolbarOptions = [
    [{ 'header': [2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    ['blockquote', 'code-block'],
    ['link', 'image'],
    ['clean']
];

let topicQuill = null;
let replyQuill = null;
let editPostQuill = null;

document.addEventListener('DOMContentLoaded', () => {
    const topicContainer = document.getElementById('topicQuillEditor');
    if (topicContainer) {
        topicQuill = new Quill('#topicQuillEditor', {
            theme: 'snow',
            placeholder: 'Exposez le fond de votre pensée, décrets ou interrogations...',
            modules: { toolbar: quillToolbarOptions }
        });
    }

    const replyContainer = document.getElementById('replyQuillEditor');
    if (replyContainer) {
        replyQuill = new Quill('#replyQuillEditor', {
            theme: 'snow',
            placeholder: 'Formulez vos arguments avec honneur et clarté...',
            modules: { toolbar: quillToolbarOptions }
        });
    }

    const editContainer = document.getElementById('editPostQuillEditor');
    if (editContainer) {
        editPostQuill = new Quill('#editPostQuillEditor', {
            theme: 'snow',
            placeholder: 'Modifiez votre message...',
            modules: { toolbar: quillToolbarOptions }
        });
    }
});

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showForumAlert(message, type = 'success') {
    const box = document.getElementById('forumAlertBox');
    if (!box) return;
    box.innerHTML = `
        <div class="alert alert-${type} alert-dismissible mb-3" role="alert">
            <div class="d-flex"><div>${message}</div></div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    `;
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 1. Proclamer un nouveau sujet
async function submitNewTopic(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitTopic');
    const title = document.getElementById('topicTitle').value.trim();
    let content = topicQuill ? topicQuill.root.innerHTML : document.getElementById('topicContent').value.trim();
    const plain = topicQuill ? topicQuill.getText().trim() : content;

    if (!title || (!plain && !content.includes('<img'))) {
        showForumAlert("Veuillez renseigner un titre et un message d'ouverture.", 'warning');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Proclamation en cours...';

    try {
        const formData = new FormData();
        formData.append('category_id', '<?= $catId ?>');
        formData.append('title', title);
        formData.append('content', content);

        const res = await fetch('/api/forum.php?action=create_topic', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = `/?page=forum&topic=${data.topic_id}`;
        } else {
            showForumAlert(data.error || "Erreur lors de la création.", 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bullhorn me-1"></i>Proclamer le Sujet sur le Forum';
        }
    } catch (err) {
        showForumAlert("Erreur réseau lors de la proclamation.", 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-bullhorn me-1"></i>Proclamer le Sujet sur le Forum';
    }
}

// 2. Publier une réponse
async function submitReply(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitReply');
    let content = replyQuill ? replyQuill.root.innerHTML : document.getElementById('replyContent').value.trim();
    const plain = replyQuill ? replyQuill.getText().trim() : content;

    if (!plain && !content.includes('<img')) {
        showForumAlert("Veuillez formuler votre réponse avant de publier.", 'warning');
        return;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Scellage en cours...';

    try {
        const formData = new FormData();
        formData.append('topic_id', '<?= $topicId ?>');
        formData.append('content', content);

        const res = await fetch('/api/forum.php?action=reply', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            showForumAlert(data.error || "Erreur de réponse.", 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-scroll me-1"></i>Sceller &amp; Publier la Réponse';
        }
    } catch (err) {
        showForumAlert("Erreur réseau lors de la publication.", 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-scroll me-1"></i>Sceller &amp; Publier la Réponse';
    }
}

// 3. Citer un message
function quotePost(username, postId) {
    const contentElem = document.getElementById(`post-content-${postId}`);
    if (!contentElem) return;

    const rawHtml = contentElem.innerHTML.trim();
    if (replyQuill) {
        const quoteHtml = `<blockquote><p><strong>${escapeHtml(username)} a proclamé :</strong></p>${rawHtml}</blockquote><p><br></p>`;
        const range = replyQuill.getSelection() || { index: replyQuill.getLength(), length: 0 };
        replyQuill.clipboard.dangerouslyPasteHTML(range.index, quoteHtml);
        document.getElementById('replyFormContainer').scrollIntoView({ behavior: 'smooth' });
        replyQuill.focus();
    } else {
        const replyInput = document.getElementById('replyContent');
        if (replyInput) {
            replyInput.value += `[citation de ${username}]\n> ${contentElem.innerText.trim().replace(/\n/g, '\n> ')}\n[/citation]\n\n`;
            document.getElementById('replyFormContainer').scrollIntoView({ behavior: 'smooth' });
            replyInput.focus();
        }
    }
}

// 4. Édition de message
function openEditModal(postId) {
    const contentElem = document.getElementById(`post-content-${postId}`);
    if (!contentElem) return;

    document.getElementById('editPostId').value = postId;
    const currentHtml = contentElem.innerHTML.trim();

    if (editPostQuill) {
        editPostQuill.root.innerHTML = currentHtml;
    } else {
        document.getElementById('editPostContent').value = currentHtml;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalEditPost'));
    modal.show();
}

async function submitEditPost() {
    const postId = document.getElementById('editPostId').value;
    const content = editPostQuill ? editPostQuill.root.innerHTML : document.getElementById('editPostContent').value.trim();
    const plain = editPostQuill ? editPostQuill.getText().trim() : content;

    if (!plain && !content.includes('<img')) {
        alert("Le message ne peut pas être vide.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('content', content);

        const res = await fetch('/api/forum.php?action=edit_post', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || "Erreur d'édition.");
        }
    } catch (err) {
        alert("Erreur réseau lors de la modification.");
    }
}

// 5. Supprimer un message
async function deletePost(postId) {
    if (!confirm("Voulez-vous supprimer ce message ?")) return;

    try {
        const formData = new FormData();
        formData.append('post_id', postId);

        const res = await fetch('/api/forum.php?action=delete_post', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || "Erreur de suppression.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}

// 6. Épingler / Désépingler
async function togglePinTopic(topicId) {
    try {
        const formData = new FormData();
        formData.append('topic_id', topicId);

        const res = await fetch('/api/forum.php?action=toggle_pin', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || "Erreur.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}

// 7. Verrouiller / Déverrouiller
async function toggleLockTopic(topicId) {
    try {
        const formData = new FormData();
        formData.append('topic_id', topicId);

        const res = await fetch('/api/forum.php?action=toggle_lock', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || "Erreur.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}

// 8. Supprimer un sujet
async function deleteTopic(topicId, catId) {
    if (!confirm("ATTENTION : Supprimer définitivement ce sujet et l'ensemble de ses réponses ?")) return;

    try {
        const formData = new FormData();
        formData.append('topic_id', topicId);

        const res = await fetch('/api/forum.php?action=delete_topic', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            window.location.href = `/?page=forum&cat=${catId}`;
        } else {
            alert(data.error || "Erreur.");
        }
    } catch (err) {
        alert("Erreur réseau.");
    }
}

<?php if ($isAdmin): ?>
// 9. Administration : Ouvrir la modale de création de salon
function openCreateForumCategoryModal() {
    document.getElementById('create_afc_icon').value = 'fa-comments';
    document.getElementById('create_afc_name').value = '';
    document.getElementById('create_afc_desc').value = '';
    document.getElementById('create_afc_order').value = 10;
    document.getElementById('create_afc_locked').checked = false;

    const modal = new bootstrap.Modal(document.getElementById('modalCreateAdminForumCategory'));
    modal.show();
}

// 10. Administration : Créer un salon féodal
async function submitAdminCreateCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('btnAdminCreateCatModal');
    if (btn) btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('icon', document.getElementById('create_afc_icon').value.trim() || 'fa-comments');
        formData.append('name', document.getElementById('create_afc_name').value.trim());
        formData.append('description', document.getElementById('create_afc_desc').value.trim());
        formData.append('display_order', document.getElementById('create_afc_order').value || 0);
        formData.append('is_locked', document.getElementById('create_afc_locked').checked ? '1' : '0');

        const res = await fetch('/api/forum.php?action=admin_create_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showForumAlert(data.message || "Salon créé avec succès !", "success");
            setTimeout(() => window.location.href = '/?page=forum', 600);
        } else {
            showForumAlert(data.error || "Impossible de créer le salon.", "danger");
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        showForumAlert("Erreur lors de la communication avec le serveur.", "danger");
        if (btn) btn.disabled = false;
    }
}

// 11. Administration : Ouvrir la modale d'édition de salon
function openEditForumCategoryModal(id, name, desc, icon, order, isLocked) {
    document.getElementById('edit_afc_id').value = id;
    document.getElementById('edit_afc_name').value = name;
    document.getElementById('edit_afc_desc').value = desc;
    document.getElementById('edit_afc_icon').value = icon;
    document.getElementById('edit_afc_order').value = order;
    document.getElementById('edit_afc_locked').checked = (parseInt(isLocked, 10) === 1);

    const modal = new bootstrap.Modal(document.getElementById('modalEditAdminForumCategory'));
    modal.show();
}

// 12. Administration : Modifier un salon féodal
async function submitAdminEditCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('btnAdminEditCatModal');
    if (btn) btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('category_id', document.getElementById('edit_afc_id').value);
        formData.append('name', document.getElementById('edit_afc_name').value.trim());
        formData.append('description', document.getElementById('edit_afc_desc').value.trim());
        formData.append('icon', document.getElementById('edit_afc_icon').value.trim() || 'fa-comments');
        formData.append('display_order', document.getElementById('edit_afc_order').value || 0);
        formData.append('is_locked', document.getElementById('edit_afc_locked').checked ? '1' : '0');

        const res = await fetch('/api/forum.php?action=admin_edit_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showForumAlert(data.message || "Salon mis à jour avec succès !", "success");
            setTimeout(() => window.location.reload(), 600);
        } else {
            showForumAlert(data.error || "Erreur de modification.", "danger");
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        showForumAlert("Erreur réseau lors de la mise à jour.", "danger");
        if (btn) btn.disabled = false;
    }
}

// 13. Administration : Supprimer un salon féodal
async function deleteForumCategory(catId, name) {
    if (!confirm(`ATTENTION : Supprimer définitivement le salon '${name}' et TOUS ses sujets et messages ?\n\nCette action est irréversible.`)) return;

    try {
        const formData = new FormData();
        formData.append('category_id', catId);

        const res = await fetch('/api/forum.php?action=admin_delete_category', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showForumAlert(data.message || "Salon supprimé avec succès.", "success");
            setTimeout(() => window.location.href = '/?page=forum', 600);
        } else {
            showForumAlert(data.error || "Erreur lors de la suppression.", "danger");
        }
    } catch (err) {
        showForumAlert("Erreur réseau lors de la suppression.", "danger");
    }
}
<?php endif; ?>
</script>

