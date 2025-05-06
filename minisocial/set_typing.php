<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) exit;

$senderId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? null;
$isTyping = $_POST['is_typing'] ?? 0;

if (!$receiverId) exit;

$stmt = $pdo->prepare("
    INSERT INTO typing_status (sender_id, receiver_id, is_typing)
    VALUES (:sender_id, :receiver_id, :is_typing)
    ON DUPLICATE KEY UPDATE is_typing = :is_typing, updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([
    'sender_id' => $senderId,
    'receiver_id' => $receiverId,
    'is_typing' => $isTyping
]);
