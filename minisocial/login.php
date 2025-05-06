<?php
session_start();
require 'config.php';
include 'templates/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_picture'] = $user['profile_picture'] ?? 'default_profile.png';
        header('Location: profile.php');
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<link rel="stylesheet" href="style.css">
<div class="auth-container">
    <h2>Login</h2>
    <form action="login.php" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <a href="register.php">Don't have an account? Register here</a>
</div>
<?php if ($error): ?>
    <p style="color: red; text-align: center;"><?= $error ?></p>
<?php endif; ?>
