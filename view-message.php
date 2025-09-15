<?php
require_once 'scripts/database-conn.php';
require_once 'scripts/session-security.php';
require_once 'scripts/ticket-functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid message ID.</div>";
    exit;
}

$messageID = (int)$_GET['id'];
$userID = $_SESSION['id'] ?? null;
$userRole = $_SESSION['role'] ?? 'user';

$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND (recipient_id = ? OR ? IN ('mod', 'ok'))");
$stmt->execute([$messageID, $userID, $userRole]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

// Default to system sender if it's from the Moderator Team
if ($message && $message['sender_id'] == 0) {
    $message['senderName'] = "Moderator Team";
    $message['profileImage'] = "https://imgur.com/bfz4CLQ.png"; // You can change this icon
    $message['senderID'] = "Team";
} else {
    // Fetch sender info normally
    $userQuery = $pdo->prepare("SELECT id, displayName, profileImage FROM accounts WHERE id = ?");
    $userQuery->execute([$message['sender_id']]);
    $sender = $userQuery->fetch(PDO::FETCH_ASSOC);

    if ($sender) {
        $message['senderName'] = $sender['displayName'];
        $message['profileImage'] = $sender['profileImage'];
        $message['senderID'] = $sender['id'];
    }
}


if (!$message) {
    echo "<div class='alert alert-warning'>Message not found or you do not have permission to view it.</div>";
    exit;
}

// Mark as read if not already
if ($message['is_read'] === 0) {
    $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $update->execute([$messageID]);
}
?>
<?php include 'scripts/header.php'; ?>
<div class="container mt-3">
    <a href="inbox.php?p=inbox"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
</svg> Back to inbox</a>
<br>
    <div class="card bg-dark border-secondary text-white">
                <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
            <b>Subject: <?= htmlspecialchars($message['subject']) ?></b>
            </div>
            <a href="report.php?type=message&id=<?= $message['id'] ?>" class="btn btn-sm btn-danger">Report</a>
        </div>
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <b>Sent By: 
  <img src="<?= htmlspecialchars($message['profileImage']) ?>" class="rounded-circle me-2" alt="avatar" width="36" height="36">
  <?php if ($message['senderID'] !== "Team"): ?>
    <a href="profile.php?id=<?= $message['senderID'] ?>" class="text-white fw-bold">
      <?= htmlspecialchars($message['senderName']) ?> (#<?= $message['senderID'] ?>)
    </a>
  <?php else: ?>
    <span class="text-success fw-bold"><?= htmlspecialchars($message['senderName']) ?></span>
  <?php endif; ?>
</b>

            </div>
            <small>on <?= date('F j, Y, g:i a', strtotime($message['sent_at'])) ?></small>
        </div>
        <div class="card-body">
            <p><?= nl2br(strip_tags($message['content'], '<br><a><b><i><u>')) ?></p>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <form method="post" action="scripts/delete-message.php" onsubmit="return confirm('Are you sure you want to delete this message?');">
                <input type="hidden" name="message_id" value="<?= $messageID ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
            <?php if ($message['is_staff_notice'] !== 1) { ?><button class="btn btn-sm btn-primary" id="toggleReply">Reply</button><?php } ?>

        </div>
    </div>

    <!-- Hidden Reply Form -->
<div id="replyForm" class="mt-3 d-none">
  <form method="post">
    <textarea name="reply_content" class="form-control" rows="3" placeholder="Write your reply..."></textarea>
    <button type="submit" name="send_reply" class="btn btn-success mt-2">Send</button>
  </form>
</div>

<script>
  document.getElementById('toggleReply').addEventListener('click', function () {
    document.getElementById('replyForm').classList.toggle('d-none');
  });
</script>
</div>
