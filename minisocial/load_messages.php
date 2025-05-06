<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}
require 'config.php';

$receiverId = $_GET['receiver_id'] ?? null;
if (!$receiverId) {
    die("Error: No receiver specified.");
}

$senderId = $_SESSION['user_id'];

// Nachrichten abrufen
$stmt = $pdo->prepare("
    SELECT chats.*, users.username AS sender_username 
    FROM chats 
    JOIN users ON chats.sender_id = users.id
    WHERE 
        (sender_id = :sender_id AND receiver_id = :receiver_id) OR 
        (sender_id = :receiver_id AND receiver_id = :sender_id)
    ORDER BY created_at ASC
");
$stmt->execute([
    'sender_id' => $senderId,
    'receiver_id' => $receiverId
]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HTML-Ausgabe
foreach ($messages as $message): 
    $isSent = $message['sender_id'] == $senderId;
    $text = nl2br(htmlspecialchars($message['message']));
    $img = isset($message['image_path']) && !empty($message['image_path']) ? htmlspecialchars($message['image_path']) : null;
?>
    <div class="message <?= $isSent ? 'sent' : 'received' ?>">
        <div class="message-content">
            <?php if (!empty($text)): ?>
                <p><?= $text ?></p>
            <?php endif; ?>

            <?php if (!empty($img)): ?>
                <img src="<?= $img ?>" alt="image" class="chat-image" style="max-width:100%; max-height:160px; object-fit:contain; border-radius:10px;">
            <?php endif; ?>

            <span class="timestamp"><?= htmlspecialchars($message['created_at']) ?></span>
        </div>
    </div>
<?php endforeach; ?>
