<?php
session_start();
require 'config.php';

// ---- SEARCH ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ---- PAGINATION SETTINGS ----
$posts_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// ---- COUNT TOTAL POSTS (for pagination) ----
if ($search !== '') {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE title LIKE :search OR content LIKE :search");
    $count_stmt->execute(['search' => '%' . $search . '%']);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM posts");
}
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);
if ($total_pages < 1) $total_pages = 1;
if ($current_page > $total_pages) $current_page = $total_pages;

// ---- CALCULATE OFFSET ----
$offset = ($current_page - 1) * $posts_per_page;

// ---- FETCH POSTS (with search + pagination) ----
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT posts.*, users.username as author FROM posts LEFT JOIN users ON posts.user_id = users.id WHERE posts.title LIKE :search OR posts.content LIKE :search ORDER BY posts.created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT posts.*, users.username as author FROM posts LEFT JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$posts = $stmt->fetchAll();

// ---- USER STATS (last login + post count) ----
$user_post_count = null;
if(isset($_SESSION['user_id'])) {
    $count_user_posts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $count_user_posts->execute([$_SESSION['user_id']]);
    $user_post_count = $count_user_posts->fetchColumn();
}

$search_param = $search !== '' ? '&search=' . urlencode($search) : '';

// Color palette to cycle through for post card accents
$accent_colors = ['#E23636', '#F0B323', '#C0392B', '#E67E22', '#922B21'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #E23636;
            --accent-dark: #C0392B;
            --gold: #F0B323;
            --dark: #0D0D0D;
            --dark2: #1A1A2E;
            --dark3: #16213E;
            --card-bg: #1E1E2E;
            --text: #E8E8F0;
            --text-muted: #8888A8;
        }
        body {
            background: var(--dark2);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            min-height: 100vh;
            background-image: radial-gradient(ellipse at top, #2a0a0a 0%, #1A1A2E 50%, #0D0D1A 100%);
        }
        h1, .navbar-brand, .page-title {
            font-family: 'Poppins', sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, #0D0D0D 0%, #1a0505 50%, #0D0D0D 100%);
            border-bottom: 2px solid var(--accent);
            box-shadow: 0 4px 20px rgba(226,54,54,0.3);
        }
        .navbar-custom .navbar-brand {
            color: var(--accent) !important;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 1.3rem;
        }
        .navbar-custom .nav-link {
            color: rgba(232,232,240,0.85) !important;
            font-weight: 500;
        }
        .navbar-custom .nav-link:hover { color: var(--gold) !important; }
        .navbar-custom .btn-new-post {
            background: var(--accent);
            color: white;
            border-radius: 6px;
            padding: 6px 16px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #ff4444;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .navbar-custom .btn-new-post:hover {
            background: var(--accent-dark);
            color: white;
            box-shadow: 0 0 12px rgba(226,54,54,0.5);
        }

        .profile-badge { position: relative; display: inline-block; }
        .profile-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(226,54,54,0.15);
            color: var(--text);
            padding: 5px 14px 5px 6px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid rgba(226,54,54,0.3);
        }
        .profile-pill .avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            background: var(--card-bg);
            color: var(--text);
            border-radius: 12px;
            border: 1px solid rgba(226,54,54,0.3);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            padding: 14px 18px;
            min-width: 220px;
            z-index: 50;
            font-size: 0.88rem;
        }
        .profile-badge:hover .profile-dropdown,
        .profile-badge:focus-within .profile-dropdown { display: block; }
        .profile-dropdown .row { display: flex; justify-content: space-between; padding: 4px 0; }
        .profile-dropdown .row span:first-child { color: var(--text-muted); }
        .profile-dropdown .row strong { color: var(--gold); }
        .profile-dropdown hr { border: none; border-top: 1px solid rgba(226,54,54,0.2); margin: 6px 0; }

        .page-title {
            font-weight: 700;
            font-size: 2.3rem;
            margin: 2rem 0 1.5rem;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .search-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            margin-bottom: 1.75rem;
            border: 1px solid rgba(226,54,54,0.2);
        }
        .search-card .form-control {
            border-radius: 6px 0 0 6px;
            border-color: rgba(226,54,54,0.3);
            background: #13131f;
            color: var(--text);
        }
        .search-card .form-control::placeholder { color: var(--text-muted); }
        .search-card .form-control:focus {
            box-shadow: 0 0 0 3px rgba(226,54,54,0.2);
            border-color: var(--accent);
            background: #13131f;
            color: var(--text);
        }
        .search-card .btn-search {
            background: var(--accent);
            color: white;
            border-radius: 0 6px 6px 0;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .search-card .btn-search:hover { background: var(--accent-dark); }
        .clear-search { color: var(--text-muted); text-decoration: none; font-size: 0.9rem; }
        .clear-search:hover { color: var(--accent); }

        .post-card {
            background: var(--card-bg);
            border: none;
            border-left: 5px solid var(--card-color, var(--accent));
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            margin-bottom: 1.25rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(226,54,54,0.2);
        }
        .post-card .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--text);
        }
        .post-card .card-title a { color: var(--text) !important; }
        .post-card .card-title a:hover { color: var(--gold) !important; }
        .post-card .card-text { color: var(--text-muted); }
        .post-card .post-meta { color: var(--text-muted); font-size: 0.85rem; }
        .post-card .post-meta strong { color: var(--gold); }
        .post-card .actions a {
            font-size: 0.9rem;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: 600;
        }
        .post-card .actions a.edit-link { color: #5b8af0; }
        .post-card .actions a.delete-link { color: var(--accent); }

        .read-more-link {
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }
        .read-more-link:hover { opacity: 0.8; }

        .pagination .page-link {
            color: var(--accent);
            border-color: rgba(226,54,54,0.3);
            background: var(--card-bg);
            font-weight: 500;
        }
        .pagination .page-link:hover { background: rgba(226,54,54,0.15); }
        .pagination .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        .pagination .page-item.disabled .page-link { color: var(--text-muted); background: var(--card-bg); }

        .empty-state {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            color: var(--text-muted);
            border: 1px solid rgba(226,54,54,0.2);
        }

        .badge-color {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            background: var(--card-color, var(--accent));
            box-shadow: 0 0 6px var(--card-color, var(--accent));
        }

        .post-meta a { color: var(--text-muted); }
        .post-meta a:hover { color: var(--gold); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand navbar-custom px-3 px-md-4 py-3">
    <a class="navbar-brand" href="index.php">📝 Blog App</a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="profile-badge" tabindex="0">
                <div class="profile-pill">
                    <span class="avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </div>
                <div class="profile-dropdown">
                    <div class="row"><span>Role</span><strong><?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></strong></div>
                    <div class="row"><span>Your posts</span><strong><?= $user_post_count ?></strong></div>
                    <hr>
                    <div class="row"><span>Last login</span></div>
                    <div class="row"><strong><?= $_SESSION['previous_login'] ? date('d M Y, H:i', strtotime($_SESSION['previous_login'])) : 'First time here!' ?></strong></div>
                </div>
            </div>
            <a href="create.php" class="btn-new-post">+ New Post</a>
            <a href="logout.php" class="nav-link">Logout</a>
        <?php else: ?>
            <a href="login.php" class="nav-link">Login</a>
            <a href="register.php" class="nav-link">Register</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container" style="max-width: 760px;">

    <h1 class="page-title">All Posts</h1>

    <!-- Search -->
    <div class="search-card">
        <form action="index.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control" placeholder="Search posts by title or content..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-search">Search</button>
        </form>
        <?php if($search !== ''): ?>
            <div class="mt-2">
                <span class="post-meta">Showing results for "<strong><?= htmlspecialchars($search) ?></strong>" — <?= $total_posts ?> found</span>
                &middot; <a href="index.php" class="clear-search">Clear search</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if(empty($posts)): ?>
        <div class="empty-state">
            <?php if($search !== ''): ?>
                <p class="mb-2">No posts found matching "<strong><?= htmlspecialchars($search) ?></strong>".</p>
                <a href="index.php">View all posts</a>
            <?php else: ?>
                <p class="mb-2">No posts yet.</p>
                <a href="login.php">Login</a> to create one!
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach($posts as $index => $post): ?>
            <?php $color = $accent_colors[$index % count($accent_colors)]; ?>
            <div class="card post-card" style="--card-color: <?= $color ?>;">
                <div class="card-body">
                    <h2 class="card-title h5">
                        <a href="post.php?id=<?= $post['id'] ?>" style="text-decoration:none; color:inherit;">
                            <span class="badge-color"></span><?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h2>
                    <p class="card-text" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= htmlspecialchars($post['content']) ?>
                    </p>
                    <p class="post-meta mb-2">
                        By <strong><?= htmlspecialchars($post['author'] ?? 'Unknown') ?></strong>
                        &middot; <?= date('d M Y', strtotime($post['created_at'])) ?>
                    </p>
                    <div class="d-flex align-items-center justify-content-between">
                        <a href="post.php?id=<?= $post['id'] ?>" class="read-more-link" style="color: var(--card-color);">Read more →</a>
                        <?php
                            $can_manage = isset($_SESSION['user_id']) &&
                                (($post['user_id'] == $_SESSION['user_id']) || ($_SESSION['role'] === 'admin'));
                        ?>
                        <?php if($can_manage): ?>
                            <div class="actions">
                                <a href="edit.php?id=<?= $post['id'] ?>" class="edit-link">Edit</a>
                                <a href="delete.php?id=<?= $post['id'] ?>" class="delete-link" onclick="return confirm('Delete this post?')">Delete</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav class="d-flex justify-content-center mt-4">
                <ul class="pagination">
                    <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?><?= $search_param ?>">&laquo; Prev</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $search_param ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?><?= $search_param ?>">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
            <p class="text-center post-meta mb-5">Page <?= $current_page ?> of <?= $total_pages ?> &middot; <?= $total_posts ?> total posts</p>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mb-5"></div>
</div>

</body>
</html>
