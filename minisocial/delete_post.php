<!-- filepath: c:\xampp\htdocs\minisocial\delete_post.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['post_id'] ?? null;

    if ($postId) {
        // Ensure the user owns the post before deleting
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $postId,
            'user_id' => $_SESSION['user_id']
        ]);
    }

    header("Location: index.php");
    exit;
}
?>