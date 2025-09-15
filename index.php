<?php
require_once 'scripts/database-conn.php';
require 'scripts/session-security.php';

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

?>

<?php include 'scripts/header.php'; ?>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <div class="container-fluid" style="margin-top: 5px; align-items:baseline;">
       <div class="container-md">100% wide until medium breakpoint</div>
</div>