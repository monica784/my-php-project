<?php
session_start();
require 'config.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ---- SERVER-SIDE VALIDATION ----
    if(empty($username) || empty($password)) {
        $error = "Please fill in all fields!";
    } elseif(strlen($username) < 3) {
        $error = "Username must be at least 3 characters long!";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores!";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if($stmt->fetch()) {
            $error = "Username already exists!";
        } else {
            // Hash password and save (default role = 'editor')
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'editor')");
            $stmt->execute([$username, $hashed]);
            $success = "Registration successful! You can now login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body { font-family: sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; }
        .hint { color: #888; font-size: 0.8rem; margin: -5px 0 10px 2px; }
        a { color: #007bff; text-decoration: none; display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <h1>📝 Register</h1>
    <?php if($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <form method="POST" onsubmit="return validateForm()">
        <input type="text" id="username" name="username" placeholder="Username" minlength="3" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only" required>
        <p class="hint">At least 3 characters. Letters, numbers, underscores only.</p>

        <input type="password" id="password" name="password" placeholder="Password" minlength="6" required>
        <p class="hint">At least 6 characters.</p>

        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account? Login</a>

    <script>
        // ---- CLIENT-SIDE VALIDATION ----
        function validateForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (username.length < 3) {
                alert("Username must be at least 3 characters long!");
                return false;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                alert("Username can only contain letters, numbers, and underscores!");
                return false;
            }
            if (password.length < 6) {
                alert("Password must be at least 6 characters long!");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>