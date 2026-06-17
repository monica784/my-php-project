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
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE title LIKE :search OR content LIKE :search ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$posts = $stmt->fetchAll();

$search_param = $search !== '' ? '&search=' . urlencode($search) : '';

// Color palette to cycle through for post card accents
$accent_colors = ['#5b5fef', '#ef5b8e', '#19b08a', '#f6a014', '#3aa6e0'];
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
            --accent: #5b5fef;
            --accent-dark: #4347c4;
            --pink: #ef5b8e;
            --green: #19b08a;
            --orange: #f6a014;
            --blue: #3aa6e0;
        }
        body {
            background: linear-gradient(180deg, #f0f1fb 0%, #fdf3f7 100%);
            font-family: 'Inter', sans-serif;
            color: #2d2d3a;
            min-height: 100vh;
        }
        h1, .navbar-brand, .page-title {
            font-family: 'Poppins', sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, var(--accent) 0%, var(--pink) 100%);
            box-shadow: 0 4px 14px rgba(91,95,239,0.25);
        }
        .navbar-custom .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.92) !important;
            font-weight: 500;
        }
        .navbar-custom .nav-link:hover { color: #fff !important; }
        .navbar-custom .btn-new-post {
            background: white;
            color: var(--accent);
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 600;
        }
        .navbar-custom .btn-new-post:hover { background: #fff0f5; color: var(--accent-dark); }

        .page-title {
            font-weight: 700;
            font-size: 2.3rem;
            margin: 2rem 0 1.5rem;
            background: linear-gradient(90deg, var(--accent), var(--pink));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .search-card {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 16px rgba(91,95,239,0.1);
            margin-bottom: 1.75rem;
        }
        .search-card .form-control {
            border-radius: 8px 0 0 8px;
            border-color: #e2e2ee;
        }
        .search-card .form-control:focus {
            box-shadow: 0 0 0 3px rgba(91,95,239,0.15);
            border-color: var(--accent);
        }
        .search-card .btn-search {
            background: linear-gradient(90deg, var(--accent), var(--pink));
            color: white;
            border-radius: 0 8px 8px 0;
            border: none;
            font-weight: 600;
        }
        .search-card .btn-search:hover { opacity: 0.92; }
        .clear-search { color: #8a8aa3; text-decoration: none; font-size: 0.9rem; }
        .clear-search:hover { color: var(--pink); }

        .post-card {
            background: white;
            border: none;
            border-left: 6px solid var(--card-color, var(--accent));
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(40,40,90,0.08);
            margin-bottom: 1.25rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 22px rgba(40,40,90,0.14);
        }
        .post-card .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #1f1f2e;
        }
        .post-card .card-text { color: #5a5a6e; }
        .post-card .post-meta { color: #a0a0b5; font-size: 0.85rem; }
        .post-card .actions a {
            font-size: 0.9rem;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: 600;
        }
        .post-card .actions a.edit-link { color: var(--blue); }
        .post-card .actions a.delete-link { color: var(--pink); }

        .pagination .page-link { color: var(--accent); border-color: #e2e2ee; font-weight: 500; }
        .pagination .page-item.active .page-link {
            background: linear-gradient(90deg, var(--accent), var(--pink));
            border-color: transparent;
        }
        .pagination .page-item.disabled .page-link { color: #c5c5d6; }

        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 3rem 1.5rem;
            text-align: center;
            color: #6a6a80;
            box-shadow: 0 4px 16px rgba(91,95,239,0.1);
        }

        .badge-color {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            background: var(--card-color, var(--accent));
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand navbar-custom px-3 px-md-4 py-3">
    <a class="navbar-brand" href="index.php">📝 Blog App</a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <?php if(isset($_SESSION['user_id'])): ?>
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
                    <h2 class="card-title h5"><span class="badge-color"></span><?= htmlspecialchars($post['title']) ?></h2>
                    <p class="card-text"><?= htmlspecialchars($post['content']) ?></p>
                    <p class="post-meta mb-2">Posted on <?= $post['created_at'] ?></p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="actions">
                            <a href="edit.php?id=<?= $post['id'] ?>" class="edit-link">Edit</a>
                            <a href="delete.php?id=<?= $post['id'] ?>" class="delete-link" onclick="return confirm('Delete this post?')">Delete</a>
                        </div>
                    <?php endif; ?>
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