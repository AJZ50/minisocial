<?php
session_start();
require 'config.php';

function linkifyHashtags($text)
{
    return preg_replace_callback('/#(\w+)/u', function ($matches) {
        $tag = htmlspecialchars($matches[1]);
        return '<a href="hashtag.php?tag=' . urlencode($tag) . '">#' . $tag . '</a>';
    }, htmlspecialchars($text));
}


$tag = $_GET['tag'] ?? '';
if (!$tag) {
    die("Kein Hashtag angegeben.");
}

$stmt = $pdo->prepare("
    SELECT posts.*, users.username, users.profile_picture
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.content LIKE :tag
    ORDER BY posts.created_at DESC
");
$stmt->execute(['tag' => "%#$tag%"]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>#<?= htmlspecialchars($tag) ?> – Hashtag</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <main>
        <h1>#<?= htmlspecialchars($tag) ?></h1>
        <?php if (empty($posts)): ?>
            <p>Keine Beiträge mit diesem Hashtag gefunden.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-container">
                    <div class="post-header">
                        <a href="user_profile.php?user_id=<?= $post['user_id'] ?>">
                            <img src="<?= htmlspecialchars($post['profile_picture']) ?>" class="profile-picture"
                                alt="Profilbild">
                            <strong><?= htmlspecialchars($post['username']) ?></strong>
                        </a>
                    </div>
                    <p><?= nl2br(linkifyHashtags($post['content'])) ?></p>
                    <?php if (!empty($post['image'])): ?>
                        <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="post-image"
                            onclick="openImageModal(this.src)">
                    <?php endif; ?>
                    <small><?= $post['created_at'] ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        function openImageModal(src) {
            const modal = document.getElementById('image-modal');
            const image = document.getElementById('modal-image');
            image.src = src;
            modal.style.display = 'flex';
        }
    </script>

    <div id="image-modal" class="image-modal" onclick="this.style.display='none'">
        <img id="modal-image" src="" alt="Vorschau">
    </div>

</body>

</html>