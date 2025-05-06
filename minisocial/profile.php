<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Profil aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newImagePath = null;

    if (!empty($_FILES['profile_picture']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'uploads/profile_' . $userId . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filename);
            $newImagePath = $filename;
        }
    }

    $update = "UPDATE users SET username = :username, email = :email";
    if ($newImagePath) {
        $update .= ", profile_picture = :image";
    }
    $update .= " WHERE id = :id";

    $stmt = $pdo->prepare($update);
    $params = [
        'username' => $newUsername,
        'email' => $newEmail,
        'id' => $userId
    ];
    if ($newImagePath) {
        $params['image'] = $newImagePath;
        $_SESSION['profile_picture'] = $newImagePath;
    }
    $stmt->execute($params);

    $_SESSION['username'] = $newUsername;
    $_SESSION['email'] = $newEmail;

    header("Location: profile.php");
    exit;
}

// Benutzer laden
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    die("⚠ Benutzer nicht gefunden.");
}


// Beiträge laden
$stmt = $pdo->prepare("
    SELECT posts.*, 
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = :uid) AS user_liked
    FROM posts
    WHERE posts.user_id = :uid
    ORDER BY posts.created_at DESC
");
$stmt->execute(['uid' => $userId]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

function linkifyHashtags($text)
{
    return preg_replace_callback('/#(\w+)/u', function ($matches) {
        $tag = htmlspecialchars($matches[1]);
        return '<a href="hashtag.php?tag=' . urlencode($tag) . '">#' . $tag . '</a>';
    }, htmlspecialchars($text));
}


// Kommentare vorbereiten
$commentStmt = $pdo->prepare("SELECT comments.*, users.username FROM comments 
                              JOIN users ON comments.user_id = users.id 
                              WHERE comments.post_id = :post_id 
                              ORDER BY comments.created_at ASC");
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Mein Profil</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'templates/header.php'; ?>

    <main>

        <section class="profile-overview">
            <h1>Mein Profil</h1>
            <img src="<?= htmlspecialchars($currentUser['profile_picture'] ?? 'default.png') ?>" alt="Profilbild"
                class="profile-picture-large">
            <h2><?= htmlspecialchars($currentUser['username'] ?? 'Kein Name') ?></h2>
            <?php if (isset($currentUser['email']) && trim($currentUser['email']) !== ''): ?>
                <p><?= htmlspecialchars($currentUser['email']) ?></p>
            <?php else: ?>
                <p><em>Keine E-Mail</em></p>
            <?php endif; ?>
        </section>

        <section class="profile-settings">
            <h3>Profil bearbeiten</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <label>Neues Profilbild:
                    <input type="file" name="profile_picture" accept="image/*">
                </label>
                <label>Neuer Benutzername:
                    <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username']) ?>"
                        required>
                </label>
                <label>Neue E-Mail:
                    <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>"
                        required>
                </label>
                <button type="submit">Speichern</button>
            </form>
        </section>

        <h2>Meine Beiträge</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post-container">
                <div class="post-header">
                    <img src="<?= htmlspecialchars($currentUser['profile_picture'] ?? 'default.png') ?>" alt="Profilbild"
                        class="profile-picture">
                    <strong><?= htmlspecialchars($currentUser['username']) ?></strong>
                </div>
                <p><?= nl2br(linkifyHashtags($post['content'])) ?></p>

                <?php if (!empty($post['image'])): ?>
                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="post-image"
                        onclick="openImageModal(this.src)">
                <?php endif; ?>

                <small><?= $post['created_at'] ?></small>

                <form method="post" action="delete_post.php" class="delete-post-form">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" class="delete-button">Delete Post</button>
                </form>

                <button class="like-button" data-post-id="<?= $post['id'] ?>">
                    <?= $post['user_liked'] ? '❤️' : '🤍' ?>
                    (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?? 0 ?></span>)
                </button>

                <div class="comments">
                    <h4>Kommentare:</h4>
                    <div id="comments-<?= $post['id'] ?>">
                        <?php
                        $commentStmt->execute(['post_id' => $post['id']]);
                        $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($comments as $comment): ?>
                            <div class="comment" data-comment-id="<?= $comment['id'] ?>">
                                <strong><?= htmlspecialchars($comment['username']) ?>:</strong>
                                <?= nl2br(htmlspecialchars($comment['content'])) ?><br>
                                <small><?= $comment['created_at'] ?></small>
                                <?php if ($comment['user_id'] == $userId): ?>
                                    <button class="delete-comment-button" data-comment-id="<?= $comment['id'] ?>">Delete</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form class="add-comment-form" method="post" data-post-id="<?= $post['id'] ?>">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <textarea name="content" placeholder="Leave a comment..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>
        <?php endforeach; ?>
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


    </main>
</body>

</html>