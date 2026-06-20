<?php
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ---- VALIDATE THE ID FROM URL ----
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

// ---- PERMISSION CHECK ----
// Only the post's owner OR an admin can edit it
$is_owner = ($post['user_id'] == $_SESSION['user_id']);
$is_admin = ($_SESSION['role'] === 'admin');

if(!$is_owner && !$is_admin) {
    die("You don't have permission to edit this post.");
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    // ---- SERVER-SIDE VALIDATION ----
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

    // Keep the edited values on screen if validation fails
    $post['title'] = $title;
    $post['content'] = $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        textarea { height: 200px; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        a { color: #007bff; text-decoration: none; }
        .error { color: red; }
        .hint { color: #888; font-size: 0.8rem; margin: -5px 0 5px 2px; }
    </style>
</head>
<body>
    <h1>✏️ Edit Post</h1>
    <a href="index.php">← Back</a><br><br>

    <?php if($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" onsubmit="return validatePostForm()">
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" minlength="3" maxlength="255" required>
        <p class="hint">At least 3 characters.</p>

        <textarea id="content" name="content" minlength="10" required><?= htmlspecialchars($post['content']) ?></textarea>
        <p class="hint">At least 10 characters.</p>

        <button type="submit">Update Post</button>
    </form>

    <script>
        function validatePostForm() {
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();

            if (title.length < 3) {
                alert("Title must be at least 3 characters long!");
                return false;
            }
            if (content.length < 10) {
                alert("Content must be at least 10 characters long!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>