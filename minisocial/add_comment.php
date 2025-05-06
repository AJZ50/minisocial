<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['content'])) {
    $postId = (int) $_POST['post_id'];
    $content = trim($_POST['content']);
    $userId = $_SESSION['user_id'];

    if ($content !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at)
                               VALUES (:post_id, :user_id, :content, NOW())");
        $stmt->execute([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content
        ]);

        // Benutzername für sofortiges Feedback holen
        $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $userStmt->execute(['id' => $userId]);
        $user = $userStmt->fetch();

        echo json_encode([
            'success' => true,
            'username' => htmlspecialchars($user['username']),
            'content' => nl2br(htmlspecialchars($content)),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Kommentar konnte nicht gespeichert werden.']);
