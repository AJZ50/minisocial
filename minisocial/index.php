<!-- filepath: c:\xampp\htdocs\minisocial\index.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';

function extractTagsAndWords($text)
{
    preg_match_all('/#(\w+)/u', $text, $hashtags);
    $tags = $hashtags[1];
    $words = preg_split('/\W+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $filtered = array_filter($words, fn($w) => mb_strlen($w) >= 3 && $w[0] !== '#');
    return array_map('mb_strtolower', array_merge($tags, $filtered));
}

$currentUserId = $_SESSION['user_id'];
$interest = [];

// Eigene Beiträge
$stmt = $pdo->prepare("SELECT content FROM posts WHERE user_id = ?");
$stmt->execute([$currentUserId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    foreach (extractTagsAndWords($row['content']) as $item) {
        $interest[$item] = ($interest[$item] ?? 0) + 3;
    }
}

// Likes
$stmt = $pdo->prepare("SELECT posts.content FROM likes JOIN posts ON likes.post_id = posts.id WHERE likes.user_id = ?");
$stmt->execute([$currentUserId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    foreach (extractTagsAndWords($row['content']) as $item) {
        $interest[$item] = ($interest[$item] ?? 0) + 1;
    }
}


include 'templates/header.php';

function highlightHashtags($text)
{
    return preg_replace('/#(\w+)/', '<span class="hashtag">#$1</span>', htmlspecialchars($text));
}
function linkifyHashtags($text)
{
    return preg_replace_callback('/#(\w+)/u', function ($matches) {
        $tag = htmlspecialchars($matches[1]);
        return '<a href="hashtag.php?tag=' . urlencode($tag) . '">#' . $tag . '</a>';
    }, htmlspecialchars($text));
}


// Handle search functionality
$searchQuery = $_GET['search'] ?? '';

// Fetch posts from the database
try {
    if ($searchQuery) {
        $stmt = $pdo->prepare("SELECT posts.*, users.username, users.profile_picture FROM posts 
                               JOIN users ON posts.user_id = users.id 
                               WHERE users.username LIKE :search 
                               ORDER BY posts.created_at DESC");
        $stmt->execute(['search' => '%' . $searchQuery . '%']);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT posts.*, 
                   users.username, 
                   users.profile_picture,
                   (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
                   (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = {$_SESSION['user_id']}) AS user_liked
            FROM posts
            JOIN users ON posts.user_id = users.id
        ");

        $posts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post) {
            $score = 0;
            foreach (extractTagsAndWords($post['content']) as $item) {
                $score += $interest[$item] ?? 0;
            }
            $post['score'] = $score;
            $posts[] = $post;
        }

        usort($posts, function ($a, $b) {
            return $b['score'] <=> $a['score'] ?: strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
    }

} catch (PDOException $e) {
    die("Error fetching posts: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniSocial</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <main>
        <!-- Search Bar -->
        <form method="get" class="search-form">
            <input type="text" name="search" placeholder="Search users..."
                value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Post Creation Form -->
        <form method="post" action="post_create.php" class="create-post-form" enctype="multipart/form-data">
            <textarea name="content" placeholder="Share your thoughts" required></textarea>
            <input type="file" name="image" accept="image/*">
            <button type="submit">Post</button>
        </form>

        <!-- Display Posts -->
        <?php foreach ($posts as $post): ?>
            <div class="post-container">
                <div class="post-header">
                    <a href="user_profile.php?user_id=<?= $post['user_id'] ?>"
                        style="text-decoration: none; color: inherit;">
                        <img src="<?= htmlspecialchars($post['profile_picture'] ?? 'default.png') ?>" alt="Profilbild"
                            class="profile-picture">
                        <strong><?= htmlspecialchars($post['username']) ?></strong>
                    </a>
                </div>
                <p><?= nl2br(linkifyHashtags($post['content'])) ?></p>
                <?php if (!empty($post['image'])): ?>
                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="post-image"
                        onclick="openImageModal(this.src)">
                <?php endif; ?>
                <small><?= $post['created_at'] ?></small>

                <!-- Delete Post Button (only for the post owner) -->
                <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                    <form method="post" action="delete_post.php" class="delete-post-form">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <button type="submit" class="delete-button">Delete Post</button>
                    </form>
                <?php endif; ?>

                <!-- Like Button -->
                <button class="like-button" data-post-id="<?= $post['id'] ?>">
                    <?= $post['user_liked'] ? '❤️' : '🤍' ?>
                    (<span id="like-count-<?= $post['id'] ?>"><?= $post['like_count'] ?? 0 ?></span>)
                </button>

                <!-- Comments Section -->
                <div class="comments">
                    <h4>Comments:</h4>
                    <div id="comments-<?= $post['id'] ?>">
                        <?php
                        $commentStmt = $pdo->prepare("SELECT comments.*, users.username FROM comments 
                                              JOIN users ON comments.user_id = users.id 
                                              WHERE comments.post_id = :post_id 
                                              ORDER BY comments.created_at ASC");
                        $commentStmt->execute(['post_id' => $post['id']]);
                        $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($comments as $comment): ?>
                            <div class="comment" data-comment-id="<?= $comment['id'] ?>">
                                <strong><?= htmlspecialchars($comment['username']) ?>:</strong>
                                <?= nl2br(htmlspecialchars($comment['content'])) ?><br>
                                <small><?= $comment['created_at'] ?></small>

                                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                    <button class="delete-comment-button" data-comment-id="<?= $comment['id'] ?>">Delete</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Add Comment Form -->
                <form class="add-comment-form" method="post" data-post-id="<?= $post['id'] ?>">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <textarea name="content" placeholder="Leave a comment..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>
        <?php endforeach; ?>
    </main>

    <script>

        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                const postId = button.getAttribute('data-post-id');

                fetch('like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `post_id=${postId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const likeCount = document.getElementById(`like-count-${postId}`);
                            likeCount.textContent = data.like_count;

                            // Toggle Button Text
                            button.innerHTML = (data.user_liked
                                ? '❤️'
                                : '🤍') + ` (<span id="like-count-${postId}">${data.like_count}</span>)`;
                        } else {
                            alert('Fehler beim Liken: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Fehler:', error));
            });
        });

        // Add Comment AJAX
        document.querySelectorAll('.add-comment-form').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                const postId = form.getAttribute('data-post-id');
                const formData = new FormData(form);

                fetch('add_comment.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const commentsContainer = document.getElementById(`comments-${postId}`);
                            const newComment = document.createElement('div');
                            newComment.classList.add('comment');
                            newComment.innerHTML = `
                        <strong>${data.username}:</strong>
                        ${data.content}<br>
                        <small>${data.created_at}</small>
                    `;
                            commentsContainer.appendChild(newComment);
                            form.reset();
                        } else {
                            alert('Fehler beim Kommentieren: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Fehler:', error));
            });
        });

        // Delete Comment
        document.querySelectorAll('.delete-comment-button').forEach(button => {
            button.addEventListener('click', () => {
                const commentId = button.getAttribute('data-comment-id');

                if (confirm('Delete this comment?')) {
                    fetch('delete_comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `comment_id=${commentId}`
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                const commentDiv = button.closest('.comment');
                                commentDiv.remove();
                            } else {
                                alert('Could not delete comment.');
                            }
                        })
                        .catch(err => console.error('Delete error:', err));
                }
            });
        });

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