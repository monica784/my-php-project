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
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if($stmt->fetch()) {
            $error = "Username already exists!";
        } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Blog App</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: var(--card-bg);
            border: 1px solid rgba(226,54,54,0.25);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 40px rgba(226,54,54,0.1);
        }
        .register-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            text-align: center;
            margin-bottom: 0.25rem;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .register-subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }
        .form-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
        }
        .form-control {
            background: #13131f;
            border: 1px solid rgba(226,54,54,0.25);
            border-radius: 8px;
            color: var(--text);
            padding: 10px 14px;
        }
        .form-control::placeholder { color: var(--text-muted); }
        .form-control:focus {
            background: #13131f;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(226,54,54,0.2);
            color: var(--text);
        }
        .hint {
            color: var(--text-muted);
            font-size: 0.78rem;
            margin-top: 4px;
            margin-bottom: 0;
        }
        .btn-register {
            width: 100%;
            padding: 11px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-register:hover {
            background: var(--accent-dark);
            box-shadow: 0 0 16px rgba(226,54,54,0.4);
        }
        .error-msg {
            background: rgba(226,54,54,0.15);
            border: 1px solid rgba(226,54,54,0.3);
            color: #ff6b6b;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .success-msg {
            background: rgba(25,176,138,0.15);
            border: 1px solid rgba(25,176,138,0.3);
            color: #19b08a;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .login-link {
            text-align: center;
            margin-top: 1.25rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .login-link a {
            color: var(--gold);
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { color: var(--accent); }
        .divider { border-color: rgba(226,54,54,0.15); margin: 1.5rem 0; }
        .back-link { text-align: center; margin-top: 1rem; }
        .back-link a { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; }
        .back-link a:hover { color: var(--accent); }
    </style>
</head>
<body>
    <div class="register-card">
        <h1 class="register-title">⚡ Register</h1>
        <p class="register-subtitle">Join the team, Hero</p>

        <?php if($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="success-msg"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Choose a username" minlength="3" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only" required>
                <p class="hint">At least 3 characters. Letters, numbers, underscores only.</p>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Choose a password" minlength="6" required>
                <p class="hint">At least 6 characters.</p>
            </div>
            <button type="submit" class="btn-register">Register</button>
        </form>

        <hr class="divider">

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
        <div class="back-link">
            <a href="index.php">← Back to Blog</a>
        </div>
    </div>

    <script>
        function validateForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            if (username.length < 3) { alert("Username must be at least 3 characters long!"); return false; }
            if (!/^[a-zA-Z0-9_]+$/.test(username)) { alert("Username can only contain letters, numbers, and underscores!"); return false; }
            if (password.length < 6) { alert("Password must be at least 6 characters long!"); return false; }
            return true;
        }
    </script>
</body>
</html>