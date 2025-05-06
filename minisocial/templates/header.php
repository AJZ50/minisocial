<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$profilePicture = 'default_profile.png';
$username = '...';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $username = htmlspecialchars($user['username']);
        if (!empty($user['profile_picture'])) {
            $profilePicture = htmlspecialchars($user['profile_picture']);
        }
    }
}
?>

<header>
    <h1><a href="index.php" style="text-decoration: none; color: inherit;">MiniSocial</a></h1>
    <div class="user-info">
        <div>
            <img src="<?= $profilePicture ?>" alt="Profile Picture" class="profile-picture">
            <span>Logged in as: <?= $username ?></span>
        </div>
        <div>
            <a href="profile.php">Profil</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.dark-mode-toggle');

    const isDarkModeEnabled = localStorage.getItem('dark-mode') === 'true';
    if (isDarkModeEnabled) {
        document.body.classList.add('dark-mode');
        toggle.textContent = 'Switch to Light Mode';
    }

    toggle.addEventListener('click', () => {
        const isDarkMode = document.body.classList.toggle('dark-mode');
        toggle.textContent = isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        localStorage.setItem('dark-mode', isDarkMode);
    });
});
</script>
