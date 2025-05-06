<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['typing' => false]);
    exit;
}

$receiverId = $_SESSION['user_id'];
$senderId = $_GET['sender_id'] ?? null;

if (!$senderId) {
    echo json_encode(['typing' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT is_typing, updated_at 
    FROM typing_status 
    WHERE sender_id = :sender_id AND receiver_id = :receiver_id
");
$stmt->execute([
    'sender_id' => $senderId,
    'receiver_id' => $receiverId
]);

$status = $stmt->fetch(PDO::FETCH_ASSOC);
if ($status && $status['is_typing'] == 1 && strtotime($status['updated_at']) > time() - 5) {
    echo json_encode(['typing' => true]);
} else {
    echo json_encode(['typing' => false]);
}
