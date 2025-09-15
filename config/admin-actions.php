<?php
session_start();
require_once 'database-conn.php';
require_once 'logger.php';
header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'ok') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? null;
$userId = intval($_POST['user_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

// Add Note
if ($_POST['action'] === 'add_note' && isset($_POST['note'])) {
    $note = trim($_POST['note']);
    if (empty($note)) {
        echo json_encode(['success' => false, 'message' => 'Note is empty.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO user_notes (user_id, staff_id, note, created_at) VALUES (:uid, :sid, :note, NOW())");
    $success = $stmt->execute([
        ':uid' => $userId,
        ':sid' => $_SESSION['id'],
        ':note' => $note
    ]);

    if ($success) {
        echo json_encode(['success' => true]);
        // Get affected user info
        $stmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        //get staff member info
        $staffStmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $staffStmt->execute([':id' => $_SESSION['id']]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        playerAddNote($staff['id'], $staff['displayName'], $userId, $user['displayName']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert note.']);
    }
    exit;
}


// Edit Note
if ($action === 'edit_note') {
    $noteId = intval($_POST['note_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if (!$noteId || !$content) {
        echo json_encode(['success' => false, 'message' => 'Missing note content or ID']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT note, user_id FROM user_notes WHERE id = :id");
    $stmt->execute([':id' => $noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get affected user info
        $stmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        //get staff member info
        $staffStmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $staffStmt->execute([':id' => $_SESSION['id']]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        playerEditNote($staff['id'], $staff['displayName'], $userId, $user['displayName'], $note['note']);

    $stmt = $pdo->prepare("UPDATE user_notes SET note = :content WHERE id = :id");
    $stmt->execute([':content' => $content, ':id' => $noteId]);
    echo json_encode(['success' => true]);
    exit;
}

// Delete note
if ($action === 'delete_note') {
    $noteId = intval($_POST['note_id'] ?? 0);
    if (!$noteId) {
        echo json_encode(['success' => false, 'message' => 'Note ID is missing']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT note, user_id FROM user_notes WHERE id = :id");
    $stmt->execute([':id' => $noteId]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);


    // Get affected user info
        $stmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        //get staff member info
        $staffStmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
        $staffStmt->execute([':id' => $_SESSION['id']]);
        $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
        playerDeleteNote($staff['id'], $staff['displayName'], $userId, $user['displayName'], $note['note']);

    $stmt = $pdo->prepare("DELETE FROM user_notes WHERE id = :id");
    $stmt->execute([':id' => $noteId]);

    echo json_encode(['success' => true, 'message' => 'Note deleted']);
    exit;
}

// Reset Options
if ($action === 'reset_options') {
    $resetFields = [];
    $tools = [];

    if (!empty($_POST['reset_displayName'])) {
        $resetFields[] = "displayName = 'Mod Reset'";
        $tools[] = "display name";
    }

    if (!empty($_POST['reset_profileImage'])) {
        $resetFields[] = "profileImage = NULL";
        $tools[] = "profile image";
    }

    if (!empty($_POST['reset_securityQuestion'])) {
        $resetFields[] = "securityQuestion = NULL";
        $tools[] = "security question";
    }

    if (empty($resetFields)) {
        echo json_encode(['success' => false, 'message' => 'No reset options selected.']);
        exit;
    }

    $sql = "UPDATE accounts SET " . implode(", ", $resetFields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]);

    // Logging
    $tool = implode(", ", $tools);

    // Get affected user info
    $stmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //get staff member info
    $staffStmt = $pdo->prepare("SELECT id, username, displayName FROM accounts WHERE id = :id");
    $staffStmt->execute([':id' => $_SESSION['id']]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

    if ($staff && $user) {
        playerInfoReset($staff['id'], $staff['displayName'], $userId, $user['displayName'], $tool);
    }

    echo json_encode(['success' => true, 'message' => 'Selected fields reset.']);
    exit;
}

if ($action === 'update_role') {
    $newRole = $_POST['accountRole'] ?? '';
    $validRoles = ['user', 'vip', 'mod', 'ok'];

    if (!in_array($newRole, $validRoles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
        exit;
    }

    // Fetch the user's current role for logging
    $stmt = $pdo->prepare("SELECT accountRole, displayName FROM accounts WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if ($user['accountRole'] === 'ok') {
        $previousRole = 'Administrator';
    }
    elseif ($user['accountRole']) {
        $previousRole = 'Moderator';
    }

    $displayName = $user['displayName'];

    // Update the user's role
    $stmt = $pdo->prepare("UPDATE accounts SET accountRole = :role WHERE id = :id");
    $stmt->execute([':role' => $newRole, ':id' => $userId]);

    // Log the change
    $adminId = $_SESSION['id'];
    $adminName = $_SESSION['displayName'] ?? 'Unknown';

    if ($newRole === 'ok') {
        $newRole = 'Administrator';
    }
    elseif ($newRole === 'mod')
    {
        $newRole = 'Moderator';
    }
    playerRoleUpdated($adminId, $adminName, $userId, $displayName, $previousRole, $newRole);

    echo json_encode(['success' => true, 'message' => 'Role updated successfully.']);
    exit;
}



echo json_encode(['success' => false, 'message' => 'Invalid request method or parameters.']);
