<?php
require_once 'scripts/session-security.php';
require_once 'scripts/database-conn.php';
$page = $_GET['p'] ?? 'inbox';

$currentUser = $_SESSION['id'];
$isStaff = in_array($_SESSION['role'], ['mod', 'ok']);

// Get inbox messages
$stmt = $pdo->prepare("SELECT m.*, a.displayName AS sender_name, a.profileImage 
                       FROM messages m 
                       JOIN accounts a ON m.sender_id = a.id 
                       WHERE m.recipient_id = :uid
                       ORDER BY m.sent_at DESC");
$stmt->execute([':uid' => $currentUser]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'scripts/header.php';
?>
<body class="bg-dark text-white">
        <div class="container-fluid" style="margin-top: 15px; align-items:baseline;">
                   <div class="container-md">
<div class="d-flex justify-content-between mb-3">
        <h4>Your Inbox</h4>
    </div>

  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link <?= $page === 'inbox' ? 'active' : '' ?>" href="inbox.php?p=inbox">Inbox</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $page === 'mod' ? 'active' : '' ?>" href="inbox.php?p=mod">Moderator Notices</a>
    </li>
    <li class="nav-item ms-auto">
      <a class="nav-link <?= $page === 'compose' ? 'active' : '' ?>" href="inbox.php?p=compose">Compose</a>
    </li>
  </ul>

  <div class="card bg-dark text-white border-secondary">
    <div class="card-body">
      <?php
        switch ($page) {
          case 'mod':
            include 'scripts/load-mod-messages.php';
            break;
        case 'compose':
            include 'compose.php';
            break;
          case 'inbox':
          default:
            include 'scripts/load-messages.php';
            break;
        }
      ?>
    </div>
  </div>
</div>
</body>
</html>
