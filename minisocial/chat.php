<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

// QUICKCHAT: Letzte Chats inkl. letzter Nachricht + Online
$currentUserId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_picture, u.last_active,
           c.message, c.image_path, c.created_at
    FROM (
        SELECT 
            CASE 
                WHEN sender_id = :uid THEN receiver_id
                ELSE sender_id
            END AS chat_partner_id,
            MAX(id) AS last_msg_id
        FROM chats
        WHERE sender_id = :uid OR receiver_id = :uid
        GROUP BY chat_partner_id
    ) latest
    JOIN chats c ON c.id = latest.last_msg_id
    JOIN users u ON u.id = latest.chat_partner_id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute(['uid' => $currentUserId]);
$quickchatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// USER-SUCHE
$query = $_GET['query'] ?? '';
$users = [];

if ($query) {
    $stmt = $pdo->prepare("
        SELECT id, username, email, profile_picture 
        FROM users 
        WHERE (username LIKE :query OR email LIKE :query) AND id != :current_user_id
    ");
    $stmt->execute([
        'query' => '%' . $query . '%',
        'current_user_id' => $_SESSION['user_id']
    ]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Chat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'templates/header.php'; ?>
<main>
    <h1>Chat</h1>

    <!-- Quickchat -->
    <section class="quickchat">
        <h2>Letzte Chats</h2>
        <?php if (count($quickchatUsers) > 0): ?>
            <ul class="quickchat-list">
                <?php foreach ($quickchatUsers as $user): ?>
                    <li class="quickchat-item">
                        <a href="chat_window.php?user_id=<?= $user['id'] ?>">
                            <div class="quickchat-avatar-container">
                                <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'default.png') ?>" alt="Profilbild" class="quickchat-avatar">
                                <?php
                                $isOnline = strtotime($user['last_active']) > time() - 60;
                                if ($isOnline): ?>
                                    <span class="online-dot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="quickchat-info">
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <small class="preview">
                                    <?php if ($user['image_path']): ?>
                                        📷 Bild
                                    <?php elseif ($user['message']): ?>
                                        <?= htmlspecialchars(mb_strimwidth($user['message'], 0, 30, '...')) ?>
                                    <?php endif; ?>
                                </small>
                                <span class="timestamp"><?= date('H:i', strtotime($user['created_at'])) ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Du hast noch mit niemandem geschrieben.</p>
        <?php endif; ?>
    </section>

    <!-- Suche -->
    <form method="get" action="chat.php" class="search-form">
        <input type="text" name="query" placeholder="Search for users..." required>
        <button type="submit">Search</button>
    </form>

    <!-- Ergebnisse -->
    <?php if (!empty($users)): ?>
        <ul class="user-list">
            <?php foreach ($users as $user): ?>
                <li class="user-item">
                    <img src="<?= htmlspecialchars($user['profile_picture'] ?? 'default_profile.png') ?>" alt="Profile Picture" class="profile-picture">
                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <a href="chat_window.php?user_id=<?= $user['id'] ?>">Chat with <?= htmlspecialchars($user['username']) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($query): ?>
        <p>No users found for "<?= htmlspecialchars($query) ?>"</p>
    <?php endif; ?>
</main>
</body>
</html>
