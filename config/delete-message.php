<?php
require_once 'database-conn.php';
require_once 'session-security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $messageID = (int)$_POST['message_id'];
    $userID = $_SESSION['id'];

    // Verify ownership or mod/admin access
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND (recipient_id = ? OR ? IN ('mod', 'ok'))");
    $stmt->execute([$messageID, $userID, $_SESSION['role']]);
    if ($stmt->fetch()) {
        $del = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $del->execute([$messageID]);
    }
}

header("Location: ../inbox.php");
exit;
