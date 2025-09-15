<?php
require_once 'scripts/database-conn.php';
require 'scripts/session-security.php';
require_once 'scripts/logger.php';

//avoid hijacking, start session
if (session_status() === PHP_SESSION_NONE)
{
  session_start();
}

$username = $_SESSION['username'] ?? null;

if (!$username) {
  die ("No username found in this session.");
}

if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die('Session hijack attempt detected.');
}

$timeout = 900; // 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    die("Session expired due to inactivity.");
}
$_SESSION['last_activity'] = time(); // Update on valid activity

//retrieve logged in user information
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    //connection good?
    if (!$user) die ("No database access.");

    //assign values before calling them
    $userID = $_SESSION['id'];
    $userRole = $_SESSION['role'];


//you must be logged in to view this page
/*if (!isset($_SESSION['id'])) {
  die("Error: You must be logged in to view this page. Please <a href=http://localhost/Kiros%20Management/pages/login.php>login</a> to continue.");
}*/

$role = $_SESSION['role'];
if ($role !== 'mod' && $role !== 'ok') {
    echo "<div class='text-danger'>Access denied.</div>";
    exit;
}

?>

<!DOCTYPE HTML>
<html lang="en" data-bs-theme="dark">