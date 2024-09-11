<?php
session_start();
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // Register User
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Password validation
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
            $error = "Password must be at least 8 characters long and include letters, numbers, and special characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed!";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
        // Login User
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Email not registered!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <div class="form-tabs">
        <button class="tab-button active" onclick="showForm('login')">Login</button>
        <button class="tab-button" onclick="showForm('register')">Register</button>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <div class="form" id="login-form">
        <form method="post">
            <h2>Login</h2>
            <input type="hidden" name="action" value="login">
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
    </div>

    <!-- Register Form -->
    <div class="form" id="register-form">
        <form method="post">
            <h2>Register</h2>
            <input type="hidden" name="action" value="register">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <button type="submit">Register</button>
        </form>
    </div>
</div>

<script>
    function showForm(form) {
        document.getElementById('login-form').classList.remove('active');
        document.getElementById('register-form').classList.remove('active');
        document.querySelector('.tab-button.active').classList.remove('active');
        
        if (form === 'login') {
            document.getElementById('login-form').classList.add('active');
            document.querySelector('.tab-button[onclick="showForm(\'login\')"]').classList.add('active');
        } else {
            document.getElementById('register-form').classList.add('active');
            document.querySelector('.tab-button[onclick="showForm(\'register\')"]').classList.add('active');
        }
    }

    // Default to showing login form
    showForm('login');
</script>

</body>
</html>