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

// ---- FETCH THE POST TO CHECK OWNERSHIP ----
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    header('Location: index.php');
    exit;
}

// ---- PERMISSION CHECK ----
// Only the post's owner OR an admin can delete it
$is_owner = ($post['user_id'] == $_SESSION['user_id']);
$is_admin = ($_SESSION['role'] === 'admin');

if(!$is_owner && !$is_admin) {
    die("You don't have permission to delete this post.");
}

$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$id]);

header('Location: index.php');
exit;
?>