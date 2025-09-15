<?php
require_once 'session-security.php';
require_once 'database-conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$senderID = $_SESSION['id'] ?? null;
$recipientID = isset($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['body'] ?? '');


if (!$senderID || !$recipientID || empty($subject) || empty($message)) {
    die("All fields are required.");
}

// Prevent sending to self
if ($senderID === $recipientID) {
    die("You cannot send a message to yourself.");
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content, sent_at, is_staff_notice, is_read) VALUES (?, ?, ?, ?, NOW(), 0, 0)");
$stmt->execute([$senderID, $recipientID, $subject, $message]);

header("Location: ../inbox.php?p=inbox");
exit;
