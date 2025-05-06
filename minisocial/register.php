<?php
session_start();
require 'config.php';
include 'templates/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_picture) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, 'default_profile.png']);

    $user_id = $pdo->lastInsertId();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['profile_picture'] = 'default_profile.png';

    header("Location: profile.php");
    exit;
}
?>

<link rel="stylesheet" href="style.css">
<div class="auth-container">
    <h2>Register</h2>
    <form action="register.php" method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account? Login here</a>
</div>
