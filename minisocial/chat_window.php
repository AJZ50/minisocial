<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'config.php';
include 'templates/header.php';

$receiverId = $_GET['user_id'] ?? null;
if (!$receiverId) {
    die("Error: No user selected for chat.");
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(['id' => $receiverId]);
$receiver = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$receiver) {
    die("Error: User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?= htmlspecialchars($receiver['username']) ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
</head>
<body>
    <h1>Chat with <?= htmlspecialchars($receiver['username']) ?></h1>

<main>
    <div id="chat-container"></div>

    <div id="typing-status" style="margin: 0.5rem 1rem; color: #ccc; font-style: italic;"></div>

    <form id="chat-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="receiver_id" value="<?= $receiverId ?>">

        <div class="input-area">
            <textarea name="message" id="message" placeholder="Type your message..." required></textarea>

            <label for="image-upload" class="upload-label">📎</label>
            <input type="file" id="image-upload" name="image" accept="image/*">

            <button type="button" id="emoji-toggle">😀</button>
            <button type="submit">Send</button>
        </div>

        <emoji-picker id="picker" style="display:none;"></emoji-picker>
    </form>
</main>

<script>
    const receiverId = <?= $receiverId ?>;
    let lastMessageCount = 0;

    function loadMessages() {
        $.get('load_messages.php', { receiver_id: receiverId }, function(data) {
            $('#chat-container').html(data);
            $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
        });
    }

    function loadMessagesWithNotification() {
        $.get('load_messages.php', { receiver_id: receiverId }, function(data) {
            const currentMessageCount = (data.match(/class="message /g) || []).length;

            if (currentMessageCount > lastMessageCount && document.hidden) {
                showNotification("Neue Nachricht", "Du hast eine neue Nachricht von <?= htmlspecialchars($receiver['username']) ?>.");
            }

            lastMessageCount = currentMessageCount;
            $('#chat-container').html(data);
            $('#chat-container').scrollTop($('#chat-container')[0].scrollHeight);
        });
    }

    setInterval(loadMessagesWithNotification, 3000);
    loadMessages();

    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('receiver_id', receiverId);

        $.ajax({
            url: 'send_message.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#message').val('');
                    $('#image-upload').val('');
                    loadMessages();
                    setTypingStatus(0);
                } else {
                    alert('Fehler: ' + data.error);
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Fehler beim Senden.');
            }
        });
    });

    $('#emoji-toggle').on('click', function () {
        $('#picker').toggle();
    });

    const picker = document.querySelector('#picker');
    const textarea = document.querySelector('#message');
    picker.addEventListener('emoji-click', event => {
        textarea.value += event.detail.unicode;
        textarea.focus();
    });

    // SCHREIBT GERADE...
    let typingTimeout;

    $('#message').on('input', function () {
        setTypingStatus(1);
        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => setTypingStatus(0), 2000);
    });

    $('#message').on('blur', function () {
        setTypingStatus(0);
    });

    function setTypingStatus(status) {
        $.post('set_typing.php', {
            receiver_id: receiverId,
            is_typing: status
        });
    }

    function checkTypingStatus() {
        $.get('get_typing.php', {
            sender_id: receiverId
        }, function(data) {
            if (data.typing) {
                $('#typing-status').text('schreibt gerade...');
            } else {
                $('#typing-status').text('');
            }
        });
    }

    setInterval(checkTypingStatus, 1500);

    // NOTIFICATION-PERMISSION
    if ("Notification" in window && Notification.permission !== "granted") {
        Notification.requestPermission();
    }

    function showNotification(title, body) {
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification(title, {
                body: body,
                icon: "default.png"
            });
        }
    }
</script>

</body>
</html>
