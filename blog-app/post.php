<?php
session_start();
require 'config.php';

// Validate ID
if(!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

// Fetch post with author username
$stmt = $pdo->prepare("SELECT posts.*, users.username as author FROM posts LEFT JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    header('Location: index.php');
    exit;
}

$can_manage = isset($_SESSION['user_id']) &&
    (($post['user_id'] == $_SESSION['user_id']) || ($_SESSION['role'] === 'admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> — Blog App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #E23636;
            --accent-dark: #C0392B;
            --gold: #F0B323;
            --card-bg: #1E1E2E;
            --text: #E8E8F0;
            --text-muted: #8888A8;
        }
        body {
            background-image: radial-gradient(ellipse at top, #2a0a0a 0%, #1A1A2E 50%, #0D0D1A 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }
        .navbar-custom {
            background: linear-gradient(90deg, #0D0D0D 0%, #1a0505 50%, #0D0D0D 100%);
            border-bottom: 2px solid var(--accent);
            box-shadow: 0 4px 20px rgba(226,54,54,0.3);
        }
        .navbar-custom .navbar-brand {
            color: var(--accent) !important;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .navbar-custom .nav-link { color: rgba(232,232,240,0.85) !important; font-weight: 500; }
        .navbar-custom .nav-link:hover { color: var(--gold) !important; }
        .navbar-custom .btn-new-post {
            background: var(--accent);
            color: white;
            border-radius: 6px;
            padding: 6px 16px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .navbar-custom .btn-new-post:hover { background: var(--accent-dark); color: white; }

        .post-container {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(226,54,54,0.25);
            box-shadow: 0 4px 30px rgba(0,0,0,0.5);
            padding: 2.5rem;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }
        .post-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            color: var(--text);
            margin-bottom: 0.75rem;
        }
        .post-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(226,54,54,0.2);
        }
        .post-meta .author { display: inline-flex; align-items: center; gap: 6px; }
        .post-meta .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--gold));
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
        .post-meta strong { color: var(--gold); }
        .post-meta span { color: var(--text-muted); }
        .post-content {
            font-size: 1.05rem;
            line-height: 1.85;
            color: #c0c0d8;
            white-space: pre-wrap;
        }
        .back-link {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .back-link:hover { color: var(--gold); }
        .action-btn {
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 6px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .edit-btn { background: rgba(91,138,240,0.15); color: #5b8af0; border: 1px solid rgba(91,138,240,0.3); }
        .edit-btn:hover { background: rgba(91,138,240,0.25); color: #5b8af0; }
        .delete-btn { background: rgba(226,54,54,0.15); color: var(--accent); border: 1px solid rgba(226,54,54,0.3); }
        .delete-btn:hover { background: rgba(226,54,54,0.25); color: var(--accent); }
        .divider { border-color: rgba(226,54,54,0.2); }
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
    <div class="mt-3">
        <a href="index.php" class="back-link">← Back to all posts</a>
    </div>

    <div class="post-container">
        <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>

        <div class="post-meta d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="author">
                <span class="avatar"><?= strtoupper(substr($post['author'] ?? 'U', 0, 1)) ?></span>
                <strong style="color: var(--gold);"><?= htmlspecialchars($post['author'] ?? 'Unknown') ?></strong>
            </span>
            <span>Posted on <?= date('d M Y, H:i', strtotime($post['created_at'])) ?></span>
        </div>

        <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>

        <?php if($can_manage): ?>
            <div class="d-flex gap-2 mt-4 pt-3" style="border-top: 1px solid rgba(226,54,54,0.2);">
                <a href="edit.php?id=<?= $post['id'] ?>" class="action-btn edit-btn">Edit Post</a>
                <a href="delete.php?id=<?= $post['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Delete this post?')">Delete Post</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>