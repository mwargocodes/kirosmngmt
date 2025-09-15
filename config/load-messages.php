<?php
require_once 'database-conn.php';
require_once 'session-security.php';
require_once 'ticket-functions.php';

$userID = $_SESSION['id'] ?? null;
if (!$userID) {
    echo "<div class='alert alert-danger'>You must be logged in to view messages.</div>";
    return;
}

$stmt = $pdo->prepare("SELECT * FROM messages WHERE recipient_id = ? AND is_staff_notice = 0 ORDER BY sent_at DESC");
$stmt->execute([$userID]);
$messages = $stmt->fetchAll();

$viewedText = "";
if (empty($messages)) {
    echo "<div class='text-muted'>No messages found.</div>";
} else {
    foreach ($messages as $msg) {

        //has the message been viewed?
        if ($msg['is_read'] === 1)
            {
                $viewedText = "<small class='text-muted'>You have viewed this message.</small>";
            }
            
        $sender = getDisplayName($pdo, $msg['sender_id']);
        echo "<div class='card mb-2 bg-dark border-secondary'>";
        echo "  <div class='card-body'>";
        echo "    <h6 class='card-title'>" . htmlspecialchars($msg['subject']) . "</h6>";
        echo "    <p class='card-text'>" . htmlspecialchars(substr($msg['content'], 0, 100)) . "...</p>";
        echo "    <small class='text-muted'>From: {$sender} (#{$msg['sender_id']}) | " . date('M d, Y H:i', strtotime($msg['sent_at'])) . "</small>";
        echo "    <div class='d-flex justify-content-between align-items-center'><a href='view-message.php?id={$msg['id']}' class='btn btn-sm btn-outline-info'>View</a> " . $viewedText;
        echo "  </div>";
        echo "</div>";
    }
}
?>
