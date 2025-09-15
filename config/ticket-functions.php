<?php 

require_once 'database-conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function countTickets($pdo, $filter, $userID = null) {
    $where = match($filter) {
        'mine' => 'authorID = :uid',
        'unclaimed' => "status = 'Unclaimed'",
        'admin_flags' => 'adminFlag = 1',
        default => '1=1'
    };
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE $where");
    if ($userID) $stmt->bindValue(':uid', $userID);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function fetchTicketsByFilter(PDO $pdo, string $filter, int $userId): array {
    switch ($filter) {
        case 'mine':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE authorID = :uid ORDER BY submittedOn DESC");
            $stmt->bindValue(':uid', $userId);
            break;

        case 'my_claimed':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE claimedBy = :uid AND status = 'Claimed' ORDER BY submittedON DESC");
            $stmt->bindValue(':uid', $userId);
            break;

        case 'unclaimed':
            $stmt = $pdo->query("SELECT * FROM tickets WHERE status = 'Unclaimed' ORDER BY submittedOn DESC");
            break;

        case 'claimed':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status = 'Claimed' AND claimedBy = :uid ORDER BY claimedOn DESC");
            $stmt->bindValue(':uid', $userId);
            break;

        case 'resolved':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status = 'Resolved' AND department = 'Moderator' ORDER BY resolvedOn DESC");
            break;

        case 'admin':
        case 'admin_mine':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status = 'Claimed' AND department = 'Administrator' AND claimedBy = :uid ORDER BY claimedOn DESC");
            $stmt->bindValue(':uid', $userId);
            break;
        case 'admin_unclaimed':
            $stmt = $pdo->query("SELECT * FROM tickets WHERE status = 'Unclaimed' AND department = 'Administrator' ORDER BY submittedOn DESC");
            break;
        case 'admin_resolved':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE status = 'Resolved' AND department = 'Administrator' ORDER BY resolvedOn DESC");
            break;
        case 'admin_flags':
            $stmt = $pdo->prepare("SELECT * FROM tickets WHERE adminFlag = 1");
            break;

        default:
            return [];
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function fetchTicketById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticketID = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserDisplayName($pdo, $id) {
    $stmt = $pdo->prepare("SELECT displayName FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

function fetchTicketReplies(PDO $pdo, int $ticketID): array {
    $stmt = $pdo->prepare("SELECT * FROM ticket_replies WHERE ticketID = :id ORDER BY created_at ASC");
    $stmt->execute([':id' => $ticketID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchTicketNotes(PDO $pdo, int $ticketID): array {
    $stmt = $pdo->prepare("SELECT * FROM ticket_notes WHERE ticketID = :id ORDER BY created_at ASC");
    $stmt->execute([':id' => $ticketID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDisplayName(PDO $pdo, ?int $userID, bool $forceAnonymize = false): string {
    if ($userID === null) return 'Unclaimed';
    $stmt = $pdo->prepare("SELECT displayName, accountRole FROM accounts WHERE id = :id");
    $stmt->execute([':id' => $userID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return 'Unknown';

    if ($forceAnonymize && !in_array($row['accountRole'], ['mod', 'ok'])) {
        return 'A Moderator';
    }

    return $row['displayName'];
}

// Replies
function getTicketReplies(PDO $pdo, int $ticketID): array {
    $stmt = $pdo->prepare("SELECT * FROM ticket_replies WHERE ticketID = :ticketID ORDER BY created_at ASC");
    $stmt->execute(['ticketID' => $ticketID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addTicketReply(PDO $pdo, int $ticketID, int $authorID, string $content): bool {
    $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticketID, author_id, content) VALUES (:ticketID, :authorID, :content)");
    return $stmt->execute([
        'ticketID' => $ticketID,
        'authorID' => $authorID,
        'content' => $content
    ]);
}

// Notes
function getTicketNotes(PDO $pdo, int $ticketID): array {
    $stmt = $pdo->prepare("SELECT * FROM ticket_notes WHERE ticketID = :ticketID ORDER BY created_at ASC");
    $stmt->execute(['ticketID' => $ticketID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addTicketNote(PDO $pdo, int $ticketID, int $authorID, string $note): bool {
    $stmt = $pdo->prepare("INSERT INTO ticket_notes (ticketID, author_id, content) VALUES (:ticketID, :authorID, :note)");
    return $stmt->execute([
        'ticketID' => $ticketID,
        'authorID' => $authorID,
        'note' => $note
    ]);

    $getStaffInfo = $pdo->prepare("SELECT * FROM accounts WHERE id = :id");
    $getStaffInfo->execute(['id' => $_SESSION['id']]);
    $staffInfo = $getStaffInfo->fetch(PDO::FETCH_ASSOC);

    logNotes($staffInfo['id'], $staffInfo['displayName'], $ticketID);
}

function isStaff(PDO $pdo, int $userID): bool {
    $stmt = $pdo->prepare("SELECT accountRole FROM accounts WHERE id = :id");
    $stmt->execute(['id' => $userID]);
    $role = $stmt->fetchColumn();

    return in_array($role, ['ok', 'mod']);
}

function getUserRoleLabel(string $role): string {
    return match ($role) {
        'ok' => 'Admin',
        'mod' => 'Moderator',
        default => 'User',
    };
}

// check for posted information from ticket tools function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ticketID'], $_POST['userID'])) {
    $action = $_POST['action'];
    $ticketID = (int)$_POST['ticketID'];
    $userID = (int)$_POST['userID'];

// Handle different actions
switch ($action) {
    case "claim":
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'Claimed', claimedBy = ?, claimedOn = NOW() WHERE ticketID = ?");
        $stmt->execute([$userID, $ticketID]);

        //automatic response
        $autoReply = $pdo->prepare("INSERT INTO ticket_replies (ticketID, author_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $autoReply->execute([
        $ticketID,
        $userID,
        "This ticket has been claimed by a staff member. Please allow up to 24-48 hours for a response."]);
        break;

    case "admin_flag":
        $stmt = $pdo->prepare("UPDATE tickets SET AdminFlag = 1 WHERE ticketID = ?");
        $stmt->execute([$ticketID]);
        break;

    case "resolve":
        $stmt = $pdo->prepare("UPDATE tickets SET Status = 'Resolved', resolvedBy = ?, ResolvedOn = NOW() WHERE ticketID = ?");
        $stmt->execute([$userID, $ticketID]);

        //automatic response
        $autoReply = $pdo->prepare("INSERT INTO ticket_replies (ticketID, author_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $autoReply->execute([
        $ticketID,
        $userID,
        "This ticket has been marked as resolved by the staff member handling it. If at any time you have additional questions or concerns, please do not hesitate to submit a new ticket."]);
        break;

    case "mod_deescalate":
        $stmt = $pdo->prepare("UPDATE tickets SET status = 'Unclaimed', department = 'Moderator', claimedBy = NULL, claimedOn = NULL WHERE ticketID = ?");
        $stmt->execute([$ticketID]);
        break;

    case "admin_escalate":
        $stmt = $pdo->prepare("UPDATE tickets SET department = 'Administrator', status = 'Unclaimed', claimedBy = NULL, claimedOn = NULL WHERE ticketID = ?");
        $stmt->execute([$ticketID]);
        break;

    case "got_it":
        $stmt = $pdo->prepare("UPDATE tickets SET adminFlag = 0 WHERE ticketID = ?");
        $stmt->execute([$ticketID]);
        break;


    //DNRB && Support Response Automation
    case "DNRB":
        //automatic response
        $autoReply = $pdo->prepare("INSERT INTO ticket_replies (ticketID, author_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $autoReply->execute([
        $ticketID,
        $userID,
"Hello there,

It appears that you've submitted a bug report. 
Unfortunately, moderators are unable to handle or solve issues regarding in-game bugs here in the Help Desk, nor are we able to submit reports directly to our developers. Only serious bugs or exploits should be submitted here in the Help Desk, as these issues should not be revealed to the public. 
An example of an exploitable bug would be being able to duplicate rare items over and over again.

As this is not a game breaking bug or an exploit, we ask that you please submit your report via a bug ticket instead, so that our developers may investigate the issue and address it accordingly. 
We cannot estimate the exact time the bug you are reporting will be fixed within, but we assure you that our development team will look into it and handle it as soon as they are able.

As this ticket is unable to be assisted with here in the Help Desk, we will go ahead and resolve it. Thank you for the report, we apologize that we are unable to assist you further.

The Kiros Mod Team"]);

        //auto resolve the ticket -- uncomment before deploying
        //$stmt = $pdo->prepare("UPDATE modbox_tickets SET Status = 'Resolved', resolvedBy = ?, ResolvedDate = NOW() WHERE ticketID = ?");
        //$stmt->execute([$userID, $ticketID]);
        break;

    case "Support":
        //automatic response
        $autoReply = $pdo->prepare("INSERT INTO ticket_replies (ticketID, author_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $autoReply->execute([
        $ticketID,
        $_SESSION['id'],
"Hello there,

We're sorry to hear that you're having trouble accessing your account, however, we are unable to assist with regaining access to your account here in the Help Desk. Moderators do not have access to account information and we are not permitted to exchange account information via a Help Desk ticket for privacy reasons. We kindly ask that you submit an email to <b>support@kiros-mmorpg.com</b> using the <u>email you used to register to the game</u> so that a support team member may assist you in regaining access to your account.

We apologize that we are unable to assist you further. As this is a support issue, we will be resolving this ticket. We hope you have a great day and wish you the best of luck! ♥"]);

        //auto resolve the ticket -- uncomment before deploying
        //$stmt = $pdo->prepare("UPDATE modbox_tickets SET Status = 'Resolved', resolvedBy = ?, ResolvedDate = NOW() WHERE ticketID = ?");
        //$stmt->execute([$userID, $ticketID]);
        break;
}


    // After processing the action, redirect back to the ticket view
    header("Location: ../tickets.php?p=view&ticketID=$ticketID");
    exit;
}