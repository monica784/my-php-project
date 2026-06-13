<?php
session_start();
require 'config.php';

$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog App</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .post { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .post h2 { margin: 0 0 10px; color: #444; }
        .post p { color: #666; }
        .post small { color: #999; }
        .actions a { margin-right: 10px; color: #007bff; text-decoration: none; }
        .actions a.delete { color: red; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px; }
        nav { margin-bottom: 20px; }
        nav a { margin-right: 15px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <nav>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="create.php">New Post</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

    <h1>📝 Blog App</h1>

    <?php if(empty($posts)): ?>
        <p>No posts yet. <a href="login.php">Login</a> to create one!</p>
    <?php else: ?>
        <?php foreach($posts as $post): ?>
            <div class="post">
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <p><?= htmlspecialchars($post['content']) ?></p>
                <small>Posted on: <?= $post['created_at'] ?></small>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="actions">
                        <a href="edit.php?id=<?= $post['id'] ?>">Edit</a>
                        <a href="delete.php?id=<?= $post['id'] ?>" class="delete" onclick="return confirm('Delete this post?')">Delete</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>