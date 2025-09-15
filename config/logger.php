<?php
require_once 'database-conn.php';
date_default_timezone_set('America/New_York');

function logAdminVisit($adminID, $adminUsername): void {
global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminUsername} (#{$adminID})</a> accessed admin panel on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Panel visit logging failed: " . $e->getMessage());
}
}

//log visits to a player's admin panel
function playerAdminPanelVisit($adminID, $adminUsername, $targetID, $targetUsername) {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminUsername} (#{$adminID})</a> accessed admin panel of <a href='profile.php?id={$targetID}' target='_blank'>{$targetUsername} (#{$targetID})</a> on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Panel visit logging failed: " . $e->getMessage());
}
}

function playerInfoReset($adminID, $adminUsername, $targetID, $targetUsername, $tool) {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminUsername} (#{$adminID})</a> reset {$tool} for <a href='profile.php?id={$targetID}' target='_blank'>{$targetUsername} (#{$targetID})</a> on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Player info reset logging failed: " . $e->getMessage());
}
}

function playerAddNote($adminID, $adminName, $targetID, $targetName) {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminName} (#{$adminID})</a> updated mod notes for <a href='profile.php?id={$targetID}' target='_blank'>{$targetName} (#{$targetID})</a> on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Player info reset logging failed: " . $e->getMessage());
}
}

function playerEditNote($adminID, $adminName, $targetID, $targetName, $oldNoteContent) {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $escapedContent = htmlspecialchars($oldNoteContent);
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminName} (#{$adminID})</a> edited mod notes for <a href='profile.php?id={$targetID}' target='_blank'>{$targetName} (#{$targetID})</a> on {$timestamp}. Original content: <em>{$escapedContent}</em>";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Player note edit logging failed: " . $e->getMessage());
}
}

function playerDeleteNote($adminID, $adminName, $targetID, $targetName, $noteContent) {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $escapedContent = htmlspecialchars($noteContent); // prevent HTML injection and store old notes
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminName} (#{$adminID})</a> deleted a mod note for <a href='profile.php?id={$targetID}' target='_blank'>{$targetName} (#{$targetID})</a> on {$timestamp}. Content was: <em>{$escapedContent}</em>";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Player note deletion logging failed: " . $e->getMessage());
}
}

function playerRoleUpdated($adminID, $adminName, $targetID, $targetName, $oldRole, $newRole) {
    global $pdo;

    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');

    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminName} (#{$adminID})</a> changed the role of <a href='profile.php?id={$targetID}' target='_blank'>{$targetName} (#{$targetID})</a> from <strong>{$oldRole}</strong> to <strong>{$newRole}</strong> on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log (modID, action, ip) VALUES (:modID, :action, :ip)");
        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Player role update logging failed: " . $e->getMessage());
    }
}


/*function logModPanelVisit(PDO $pdo, string $pageName = null): void {
    // Ensure session is started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $page = $pageName ?? basename($_SERVER['PHP_SELF']);

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log 
            (modID, action, ip) 
            VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => "Viewed mod panel of " ,
            ':ip' => $ip,
        ]);
    } catch (PDOException $e) {
        error_log("Page visit logging failed: " . $e->getMessage());
    }
}*/

function logNotes ($staffID, $staffName, $ticketID) {
    global $pdo;

    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $logEntryMsg = "<a href='profile.php?id={$staffID}' target='_blank'>{$staffName} (#{$staffID})</a> added a note to issue <a href='tickets.php?id={$ticketID}>#{$ticketID}</a>.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log (modID, action, ip) VALUES (:modID, :action, :ip)");
        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Ticket note update logging failed: " . $e->getMessage());
    }
}

function logTakeAction($adminID, $adminName, $targetID, $targetName, $type, $severity): void {
    global $pdo; //global variable for database object

    //default variables from session
    $userId = $_SESSION['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $timestamp = date ('Y-m-d H:i:s'); //timestamp
    $logEntryMsg = "<a href='profile.php?id={$adminID}' target='_blank'>{$adminName} (#{$adminID})</a> issued a {$severity} for our {$type} for user <a href='profile.php?id={$targetID}' target='_blank'>{$targetName} (#{$targetID})</a> on {$timestamp}.";

    try {
        $stmt = $pdo->prepare("INSERT INTO moderation_log
        (modID, action, ip)
        VALUES (:modID, :action, :ip)");

        $stmt->execute([
            ':modID' => $userId,
            ':action' => $logEntryMsg,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Take action logging failed: " . $e->getMessage());
}
}