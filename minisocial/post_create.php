<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    $userId = $_SESSION['user_id'];
    $imagePath = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            $imagePath = 'uploads/' . uniqid() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        } else {
            die("Error: Only JPG, PNG, and GIF files are allowed.");
        }
    }

    // Insert post into the database
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, created_at) VALUES (:user_id, :content, :image, NOW())");
    $stmt->execute([
        'user_id' => $userId,
        'content' => $content,
        'image' => $imagePath
    ]);

    header("Location: index.php");
    exit;
}
?>