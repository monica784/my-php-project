<?php
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if(!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    header('Location: index.php');
    exit;
}

$is_owner = ($post['user_id'] == $_SESSION['user_id']);
$is_admin = ($_SESSION['role'] === 'admin');

if(!$is_owner && !$is_admin) {
    die("You don't have permission to edit this post.");
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if(empty($title) || empty($content)) {
        $error = "Please fill in all fields!";
    } elseif(strlen($title) < 3) {
        $error = "Title must be at least 3 characters long!";
    } elseif(strlen($title) > 255) {
        $error = "Title is too long (max 255 characters)!";
    } elseif(strlen($content) < 10) {
        $error = "Content must be at least 10 characters long!";
    } else {
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        header('Location: index.php');
        exit;
    }

    $post['title'] = $title;
    $post['content'] = $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post — Blog App</title>
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

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #5b8af0, var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 2rem 0 1.5rem;
        }
        .edit-card {
            background: var(--card-bg);
            border: 1px solid rgba(91,138,240,0.25);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            margin-bottom: 3rem;
        }
        .form-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            background: #13131f;
            border: 1px solid rgba(91,138,240,0.25);
            border-radius: 8px;
            color: var(--text);
            padding: 10px 14px;
        }
        .form-control::placeholder { color: var(--text-muted); }
        .form-control:focus {
            background: #13131f;
            border-color: #5b8af0;
            box-shadow: 0 0 0 3px rgba(91,138,240,0.2);
            color: var(--text);
        }
        textarea.form-control { height: 280px; resize: vertical; }
        .hint {
            color: var(--text-muted);
            font-size: 0.78rem;
            margin-top: 4px;
        }
        .btn-update {
            padding: 10px 28px;
            background: #5b8af0;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-update:hover {
            background: #4470d6;
            box-shadow: 0 0 16px rgba(91,138,240,0.4);
        }
        .error-msg {
            background: rgba(226,54,54,0.15);
            border: 1px solid rgba(226,54,54,0.3);
            color: #ff6b6b;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand navbar-custom px-3 px-md-4 py-3">
    <a class="navbar-brand" href="index.php">📝 Blog App</a>
    <div class="ms-auto">
        <a href="index.php" class="nav-link">← Back to Posts</a>
    </div>
</nav>

<div class="container" style="max-width: 760px;">
    <h1 class="page-title">✏️ Edit Post</h1>

    <?php if($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="edit-card">
        <form method="POST" onsubmit="return validatePostForm()">
            <div class="mb-4">
                <label class="form-label">Post Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" minlength="3" maxlength="255" required>
                <p class="hint">At least 3 characters, max 255.</p>
            </div>
            <div class="mb-4">
                <label class="form-label">Content</label>
                <textarea id="content" name="content" class="form-control" minlength="10" required><?= htmlspecialchars($post['content']) ?></textarea>
                <p class="hint">At least 10 characters.</p>
            </div>
            <button type="submit" class="btn-update">💾 Update Post</button>
        </form>
    </div>
</div>

<script>
    function validatePostForm() {
        const title = document.getElementById('title').value.trim();
        const content = document.getElementById('content').value.trim();
        if (title.length < 3) { alert("Title must be at least 3 characters long!"); return false; }
        if (content.length < 10) { alert("Content must be at least 10 characters long!"); return false; }
        return true;
    }
</script>
</body>
</html>
