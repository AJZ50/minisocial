<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {
    $userId = $_SESSION['user_id'];
    $postId = $_POST['post_id'];

    try {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        $like = $stmt->fetch();

        if ($like) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);
        }

        // Get updated like count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $stmt->execute([$postId]);
        $likeCount = $stmt->fetchColumn();

        // Send JSON response
        echo json_encode([
            'success' => true,
            'like_count' => $likeCount,
            'user_liked' => !$like  // Wenn vorher kein Like → jetzt geliked
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
