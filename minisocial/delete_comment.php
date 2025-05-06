<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'])) {
    $commentId = $_POST['comment_id'];
    $userId = $_SESSION['user_id'];

    try {
        // Sicherheitscheck: Gehört der Kommentar dem User?
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        $comment = $stmt->fetch();

        if ($comment) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);

            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
