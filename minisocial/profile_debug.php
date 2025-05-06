<?php
session_start();
require 'config.php';

echo "<h2>🧪 Session-Diagnose</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    die("<strong>❌ Kein Benutzer eingeloggt – bitte zuerst <a href='login.php'>einloggen</a>.</strong>");
}

$userId = $_SESSION['user_id'];

echo "<p><strong>✅ user_id aus Session:</strong> $userId</p>";

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("<strong>❌ Kein Benutzer mit ID $userId in der Datenbank gefunden.</strong>");
    }

    echo "<h2>✅ Benutzer aus Datenbank geladen:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    echo "<p><strong>Benutzername:</strong> " . htmlspecialchars($user['username'] ?? 'Nicht gesetzt') . "</p>";
    echo "<p><strong>E-Mail:</strong> " . htmlspecialchars($user['email'] ?? 'Nicht gesetzt') . "</p>";

} catch (PDOException $e) {
    die("Fehler bei der Datenbankabfrage: " . $e->getMessage());
}
?>
