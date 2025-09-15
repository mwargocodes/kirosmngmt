<?php
require_once 'database-conn.php';
require_once 'session-security.php';
require_once 'ticket-functions.php';

$userID = $_SESSION['id'] ?? null;
if (!$userID) {
    echo "<div class='alert alert-danger'>You must be logged in to view moderator notices.</div>";
    return;
}

$stmt = $pdo->prepare("SELECT * FROM messages WHERE recipient_id = ? AND is_staff_notice = 1 ORDER BY sent_at DESC");
$stmt->execute([$userID]);
$messages = $stmt->fetchAll();

if (empty($messages)) {
    echo "<div class='text-muted'>No moderator notices at this time.</div>";
} else {
    foreach ($messages as $msg) {      
        $sender = getDisplayName($pdo, $msg['sender_id']);
                // Use Moderator Team label if sender_id is 0
        if ($msg['sender_id'] == 0) {
            $senderDisplay = "Moderator Team";
            $senderIDDisplay = "#0";
        } else {
            $senderDisplay = $sender;
            $senderIDDisplay = "#" . $msg['sender_id'];
        }

        echo "<div class='card mb-2 bg-dark border-warning'>";
        echo "  <div class='card-body'>";
        echo "    <h6 class='card-title text-warning'>" . htmlspecialchars($msg['subject']) . "</h6>";
        echo "      <small class='text-muted'>From: {$senderDisplay} ({$senderIDDisplay}) | " . date('M d, Y H:i', strtotime($msg['sent_at'])) . "</small>";
        echo "    <div class='mt-2'><a href='view-message.php?id={$msg['id']}' class='btn btn-sm btn-outline-warning'>View</a></div>";
        echo "  </div>";
        echo "</div>";
    }
}
?>
