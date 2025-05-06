<?php
session_start();
require 'config.php';

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("⚠ Ungültiger Benutzer.");
}

$viewedUserId = (int) $_GET['user_id'];

// Benutzer abrufen
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE id = ?");
$stmt->execute([$viewedUserId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    die("⚠ Benutzer nicht gefunden.");
}

// Beiträge dieses Users
$stmt = $pdo->prepare("
    SELECT posts.*, 
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count
    FROM posts
    WHERE posts.user_id = :uid
    ORDER BY posts.created_at DESC
");
$stmt->execute(['uid' => $viewedUserId]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function linkifyHashtags($text)
{
    return preg_replace_callback('/#(\w+)/u', function ($matches) {
        $tag = htmlspecialchars($matches[1]);
        return '<a href="hashtag.php?tag=' . urlencode($tag) . '">#' . $tag . '</a>';
    }, htmlspecialchars($text));
}

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Profil von <?= htmlspecialchars($profileUser['username']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <main>

        <section class="profile-overview">
            <h1>Profil von <?= htmlspecialchars($profileUser['username']) ?></h1>
            <img src="<?= htmlspecialchars($profileUser['profile_picture'] ?? 'default.png') ?>" alt="Profilbild"
                class="profile-picture-large">
            <h2><?= htmlspecialchars($profileUser['username']) ?></h2>
            <p><?= htmlspecialchars($profileUser['email'] ?? 'Keine E-Mail') ?></p>
        </section>

        <section>
            <h2>Beiträge von <?= htmlspecialchars($profileUser['username']) ?></h2>
            <?php if (empty($posts)): ?>
                <p><em>Keine Beiträge vorhanden.</em></p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-container">
                        <p><?= nl2br(linkifyHashtags($post['content'])) ?></p>
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="post-image"
                                onclick="openImageModal(this.src)">
                        <?php endif; ?>
                        <small><?= $post['created_at'] ?></small><br>
                        ❤️ <?= $post['like_count'] ?> Likes
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <div id="image-modal" class="image-modal" onclick="this.style.display='none'">
        <img id="modal-image" src="" alt="Vorschau">
    </div>

    <script>
        function openImageModal(src) {
            const modal = document.getElementById('image-modal');
            const image = document.getElementById('modal-image');
            image.src = src;
            modal.style.display = 'flex';
        }
    </script>

</body>

</html>