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
?>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Agora & Conseil de Guerre de l'Empire</div>
            <h2 class="page-title d-flex align-items-center gap-2">
                <span>💬</span> Forum Féodal du Japon
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
                    <a href="/?page=admin#tab-forum" class="btn btn-secondary">
                        ⚙️ Administration des Salons
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="forumAlertBox"></div>

<?php if ($currentView === 'index'): ?>
    <!-- ========================================== -->
    <!-- 1. INDEX DU FORUM : LISTE DES CATÉGORIES   -->
    <!-- ========================================== -->
    <?php
    $categories = $forumEngine->getCategories();
    ?>

    <div class="card">
        <div class="card-header bg-light">
            <h3 class="card-title d-flex align-items-center gap-2">
                <span>🏯</span> Salons de Discussion & Décrets Impériaux
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
                                <?= $cat['icon'] ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="/?page=forum&cat=<?= $cat['id'] ?>" class="fw-bold text-decoration-none fs-5">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </a>
                                    <?php if ((int)$cat['is_locked'] === 1): ?>
                                        <span class="badge bg-secondary-lt" title="Salon réservé aux proclamations du Shogunat">🔒 Officiel</span>
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
                    <span class="badge bg-secondary-lt fs-6">🔒 Salon Officiel</span>
                <?php endif; ?>
            </h3>
        </div>
        <div>
            <?php if ($canCreateTopic): ?>
                <a href="/?page=forum&action=new_topic&cat=<?= $cat['id'] ?>" class="btn btn-primary">
                    ✍️ Proclamer un Sujet
                </a>
            <?php else: ?>
                <button class="btn btn-secondary disabled" title="Seuls les membres du Shogunat peuvent proclamer des décrets ici">
                    🔒 Décrets Réservés au Staff
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
                                        <span title="Sujet Épinglé">📌</span>
                                    <?php elseif ($isLocked): ?>
                                        <span title="Sujet Verrouillé">🔒</span>
                                    <?php else: ?>
                                        <span class="text-muted">💬</span>
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
                <span><?= ((int)$topic['is_pinned'] === 1) ? '📌' : '💬' ?></span>
                <?= htmlspecialchars($topic['title']) ?>
                <?php if ($isTopicLocked): ?>
                    <span class="badge bg-secondary fs-6">🔒 Verrouillé</span>
                <?php endif; ?>
            </h2>
        </div>

        <!-- Outils de Modération Féodale (Staff) -->
        <?php if ($isStaff): ?>
            <div class="btn-list">
                <button class="btn btn-sm <?= ((int)$topic['is_pinned'] === 1) ? 'btn-warning' : 'btn-outline-warning' ?>" 
                        onclick="togglePinTopic(<?= $topic['id'] ?>)">
                    📌 <?= ((int)$topic['is_pinned'] === 1) ? 'Désépingler' : 'Épingler' ?>
                </button>
                <button class="btn btn-sm <?= $isTopicLocked ? 'btn-secondary' : 'btn-outline-secondary' ?>" 
                        onclick="toggleLockTopic(<?= $topic['id'] ?>)">
                    🔒 <?= $isTopicLocked ? 'Déverrouiller' : 'Verrouiller' ?>
                </button>
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteTopic(<?= $topic['id'] ?>, <?= $topic['category_id'] ?>)">
                    🗑️ Supprimer le Sujet
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
                            <span style="font-size: 2.5rem;">🏯</span>
                        </div>
                        <a href="javascript:void(0)" onclick="openPlayerProfileModal(<?= $post['user_id'] ?>)" class="fw-bold fs-5 text-decoration-none text-dark">
                            <?= htmlspecialchars($post['author_username']) ?>
                        </a>

                        <!-- Badges de Rang Féodal -->
                        <div class="my-1">
                            <?php if ($postAuthorIsAdmin): ?>
                                <span class="badge bg-danger text-white">⭐ ADMINISTRATEUR</span>
                            <?php elseif ($postAuthorIsMod): ?>
                                <span class="badge bg-info text-white">🛡️ MODÉRATEUR</span>
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
                            <div>🏆 <?= number_format($post['author_points']) ?> pts</div>
                            <div>🏰 <?= $post['author_planet_count'] ?> fief(s)</div>
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

                            <div class="post-content" id="post-content-<?= $post['id'] ?>" style="line-height: 1.7; font-size: 0.95rem; white-space: pre-wrap;"><?= htmlspecialchars($post['content']) ?></div>
                        </div>

                        <!-- Barre d'action du message -->
                        <div class="pt-3 mt-3 border-top d-flex justify-content-end gap-2">
                            <?php if ($canReply): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="quotePost('<?= htmlspecialchars(addslashes($post['author_username'])) ?>', <?= $post['id'] ?>)">
                                    💬 Citer
                                </button>
                            <?php endif; ?>

                            <?php if ($isAuthor || $isStaff): ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= $post['id'] ?>)">
                                    ✏️ Éditer
                                </button>
                            <?php endif; ?>

                            <?php if (($isAuthor || $isStaff) && (int)$post['is_first_post'] === 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePost(<?= $post['id'] ?>)">
                                    🗑️ Supprimer
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
                    <span>✍️</span> Inscrire une Réponse au Débat
                </h4>
            </div>
            <div class="card-body">
                <form id="formReply" onsubmit="submitReply(event)">
                    <div class="mb-3">
                        <textarea id="replyContent" class="form-control" rows="5" placeholder="Formulez vos arguments avec honneur et clarté..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="btnSubmitReply">
                            📜 Sceller & Publier la Réponse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary text-center py-3">
            🔒 Ce sujet a été verrouillé par la modération féodale. Les débats sont clos.
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
                <span>✍️</span> Proclamer un Nouveau Sujet dans : <?= htmlspecialchars($cat['name']) ?>
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
                    <textarea id="topicContent" class="form-control" rows="8" placeholder="Exposez le fond de votre pensée, décrets ou interrogations..." minlength="3" required></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <a href="/?page=forum&cat=<?= $cat['id'] ?>" class="btn btn-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTopic">
                        📢 Proclamer le Sujet sur le Forum
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<!-- Modale d'Édition de Message -->
<div class="modal modal-blur fade" id="modalEditPost" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Édition du Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editPostId" value="">
                <textarea id="editPostContent" class="form-control" rows="6"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="submitEditPost()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript d'interaction AJAX du Forum -->
<script>
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
    const content = document.getElementById('topicContent').value.trim();

    if (!title || !content) return;
    btn.disabled = true;
    btn.innerText = "Proclamation en cours...";

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
            btn.innerText = "📢 Proclamer le Sujet sur le Forum";
        }
    } catch (err) {
        showForumAlert("Erreur réseau.", 'danger');
        btn.disabled = false;
        btn.innerText = "📢 Proclamer le Sujet sur le Forum";
    }
}

// 2. Publier une réponse
async function submitReply(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitReply');
    const content = document.getElementById('replyContent').value.trim();

    if (!content) return;
    btn.disabled = true;
    btn.innerText = "Publication...";

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
            btn.innerText = "📜 Sceller & Publier la Réponse";
        }
    } catch (err) {
        showForumAlert("Erreur réseau.", 'danger');
        btn.disabled = false;
        btn.innerText = "📜 Sceller & Publier la Réponse";
    }
}

// 3. Citer un message
function quotePost(username, postId) {
    const contentElem = document.getElementById(`post-content-${postId}`);
    const replyInput = document.getElementById('replyContent');
    if (!contentElem || !replyInput) return;

    const quoteText = contentElem.innerText.trim();
    replyInput.value += `[citation de ${username}]\n> ${quoteText.replace(/\n/g, '\n> ')}\n[/citation]\n\n`;
    document.getElementById('replyFormContainer').scrollIntoView({ behavior: 'smooth' });
    replyInput.focus();
}

// 4. Édition de message
function openEditModal(postId) {
    const contentElem = document.getElementById(`post-content-${postId}`);
    if (!contentElem) return;

    document.getElementById('editPostId').value = postId;
    document.getElementById('editPostContent').value = contentElem.innerText;

    const modal = new bootstrap.Modal(document.getElementById('modalEditPost'));
    modal.show();
}

async function submitEditPost() {
    const postId = document.getElementById('editPostId').value;
    const content = document.getElementById('editPostContent').value.trim();

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
        alert("Erreur réseau.");
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
</script>

