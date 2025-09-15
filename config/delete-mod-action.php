<?php
require_once 'database-conn.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'ok') {
    die("Unauthorized.");
}

$actionID = $_POST['action_id'] ?? null;
if (!$actionID || !is_numeric($actionID)) {
    die("Invalid action.");
}

$stmt = $pdo->prepare("DELETE FROM mod_actions WHERE id = ?");
$stmt->execute([$actionID]);

    echo json_encode(['success' => true, 'message' => 'Action deleted']);
    exit;
