<?php
require_once 'database-conn.php';
require_once 'logger.php';
require_once 'ticket-functions.php';

if (session_status() === PHP_SESSION_NONE)
{
  session_start();
}
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['mod', 'ok'])) {
    die("Unauthorized access.");
}

$staffID       = $_SESSION['id'];
$targetUserID  = $_POST['targetUserID'];
$violationType = $_POST['violationType'];
$ruleDetail    = trim($_POST['ruleBullet']);
$forumLink     = $_POST['forumLink'] ?? null;
$evidence      = trim($_POST['evidence']);
$internalNote  = trim($_POST['internalNotes']);
//$severity = $_POST['violationSeverity'];

// Fetch display names
$staffName = getDisplayName($pdo, $staffID);
$targetUserName = getDisplayName($pdo, $targetUserID);

$severity = $_POST['violation_severity'] ?? '';
$standingChange = 0;

// Fallback safety
if (!$targetUserID || !$violationType || !$ruleDetail || !$evidence || !$severity) {
    die("Missing required data.");
}

// Determine standing deduction
$standingChange = 0;

if ($severity === 'Reminder') {
    $standingChange = 0;
} elseif ($severity === 'Warning') {
    if ($violationType === 'tos') $standingChange = -10;
    elseif ($violationType === 'coc' || $violationType === 'forum') $standingChange = -5;
} elseif ($severity === 'Game Ban' || $severity === 'Site Ban') {
    if ($violationType === 'tos') $standingChange = -15;
    else $standingChange = -10;
}

// Determine standing adjustment
$adjusted = false;
$oldStanding = null;
$newStanding = null;

if ($standingChange !== 0) {
    $stmt = $pdo->prepare("SELECT accountHealth FROM accounts WHERE id = ?");
    $stmt->execute([$targetUserID]);
    $user = $stmt->fetch();

    if ($user) {
        $oldStanding = (int)$user['accountHealth'];
        $newStanding = max(0, min(100, $oldStanding + $standingChange));

        $update = $pdo->prepare("UPDATE accounts SET accountHealth = ? WHERE id = ?");
        $update->execute([$newStanding, $targetUserID]);
        $adjusted = true;
    }
}


// Insert into mod_actions log
$insert = $pdo->prepare("INSERT INTO mod_actions 
    (userID, staffID, rule_type, rule_detail, forum_link, evidence, internal_note, action, standing_change) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert->execute([
    $targetUserID,
    $staffID,
    $violationType,
    $ruleDetail,
    $forumLink,
    $evidence,
    $internalNote,
    'Issued ' . $severity,
    $standingChange
]);

// Send message to user
$subject = "Rule Violation Issued";
$ruleLabel = match ($violationType) {
    'tos' => "Terms of Service",
    'coc' => "Code of Conduct",
    'forum' => "Forum Rules",
    default => "Rule"
};

// Log admin activity
logTakeAction($staffID, $staffName, $targetUserID, $targetUserName, $violationType, $severity);

// Compose message content
$subject = "Rule Violation Notice";
$messageBody = "<b>You have received a {$severity} for violating our {$ruleLabel} bullet(s) {$ruleDetail}.</b><br>";

if (!empty($evidence)) {
    $messageBody .= "<br><b>Evidence:</b><br>" . nl2br(htmlspecialchars($evidence));
}

if (!empty($forumLink)) {
    $messageBody .= "<br><a href='" . htmlspecialchars($forumLink) . "'>View Forum Rules</a>";
}

// Send message using existing logic
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content, sent_at, is_staff_notice, is_read) VALUES (?, ?, ?, ?, NOW(), 1, 0)");
$stmt->execute([
    0,           // sender_id (0 = Moderator Team)
    $targetUserID,      // recipient_id
    $subject,
    $messageBody
]);

echo json_encode([
    'success' => true,
    'severity' => $severity
]);
exit;


