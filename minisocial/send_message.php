<?php
session_start();
header('Content-Type: application/json');

file_put_contents('debug_log.txt', "--- Neue Anfrage ---\n", FILE_APPEND);
file_put_contents('debug_log.txt', "POST: " . print_r($_POST, true), FILE_APPEND);
file_put_contents('debug_log.txt', "FILES: " . print_r($_FILES, true), FILE_APPEND);

function jsonError(string $msg) {
    file_put_contents('debug_log.txt', "FEHLER: $msg\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}


if (!isset($_SESSION['user_id'])) {
    jsonError('Not logged in');
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Invalid request method');
}

$senderId = $_SESSION['user_id'];
$receiverId = $_POST['receiver_id'] ?? null;
$message = trim($_POST['message'] ?? '');
$imagePath = null;

// Bild prüfen und ggf. speichern
if (!empty($_FILES['image']['name'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        jsonError('Ungültiges Bildformat.');
    }

    $filename = uniqid('img_') . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        jsonError('Fehler beim Hochladen des Bildes.');
    }

    $imagePath = $targetPath;
}

// Abbrechen, wenn weder Text noch Bild vorhanden ist
if (empty($message) && !$imagePath) {
    jsonError('Keine Nachricht oder Bild gesendet.');
}

// Nachricht speichern
$stmt = $pdo->prepare("
    INSERT INTO chats (sender_id, receiver_id, message, image_path)
    VALUES (:sender_id, :receiver_id, :message, :image_path)
");
$stmt->execute([
    'sender_id' => $senderId,
    'receiver_id' => $receiverId,
    'message' => $message,
    'image_path' => $imagePath
]);

echo json_encode(['success' => true]);
