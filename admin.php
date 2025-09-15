<?php
require_once 'scripts/database-conn.php';
require 'scripts/session-security.php';
require_once 'scripts/logger.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$username = $_SESSION['username'] ?? null;

if (!$username) die ("No username found in this session.");

if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die('Session hijack attempt detected.');
}

$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    die("Session expired due to inactivity.");
}
$_SESSION['last_activity'] = time();

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username");
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();
if (!$user) die ("No database access.");

$userID = $_SESSION['id'];
$userRole = $_SESSION['role'];

$staffStmt = $pdo->prepare("SELECT id, displayName, accountRole FROM accounts WHERE accountRole IN ('mod', 'ok') ORDER BY accountRole DESC, displayName ASC");
$staffStmt->execute();
$staffUsers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

$adminID = $userID;
$adminUsername = $user['displayName'];
logAdminVisit($adminID, $adminUsername); //log each visit to admin panel.

?>

<!DOCTYPE HTML>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kiros MMORPG Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
  <?php include 'scripts/header.php'; ?>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<div class="container-fluid mt-1 d-flex gap-3" style="align-items: flex-start;">
  
  <!-- Admin Logs Section -->
  <div class="flex-grow-1 bg-dark text-white p-3 rounded" style="min-width: 700px; max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5>Recent Administrator Actions</h5>
      <input type="text" id="log-search" class="form-control form-control-sm w-50 bg-dark text-white border-secondary" placeholder="Search logs...">
    </div>

    <div id="log-table-container"></div>
    <div id="pagination-container" class="mt-2"></div>
  </div>

  <!-- Moderators/Admins List Section -->
  <div class="bg-dark text-white p-3 rounded" style="width: 280px; max-height: 600px; overflow-y: auto;">
    <h5>Moderators & Admins</h5>
    <ul class="list-group list-group-flush">
      <?php foreach ($staffUsers as $staff): ?>
        <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
          <a href="profile.php?id=<?= $staff['id'] ?>" class="text-white text-decoration-none">
            <?= htmlspecialchars($staff['displayName']) ?>
          </a>
          <span class="badge <?= $staff['accountRole'] === 'ok' ? 'bg-danger' : 'bg-primary' ?>">
            <?= $staff['accountRole'] === 'ok' ? 'Admin' : 'Moderator' ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<script>
function loadLogs(page = 1) {
    fetch('scripts/adminlogs-table.php?page=' + page + '&part=table')
        .then(res => res.text())
        .then(html => {
            document.getElementById('log-table-container').innerHTML = html;

            fetch('scripts/adminlogs-table.php?page=' + page + '&part=pagination')
                .then(res => res.text())
                .then(paginationHtml => {
                    document.getElementById('pagination-container').innerHTML = paginationHtml;
                    attachPaginationHandlers();
                });

            applyFilter();
        });
}

function attachPaginationHandlers() {
    document.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (!isNaN(page)) loadLogs(page);
        });
    });

    document.querySelectorAll('.pagination-ellipsis').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            let page = prompt('Enter page number to jump to:');
            page = parseInt(page);
            if (page && page > 0) {
                loadLogs(page);
            } else {
                alert('Invalid page number.');
            }
        });
    });
}

function applyFilter() {
    const filter = document.getElementById('log-search').value.toLowerCase();
    const rows = document.querySelectorAll('#log-table-container tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

document.getElementById('log-search').addEventListener('input', applyFilter);
loadLogs();
setInterval(() => {
    const currentPage = document.querySelector('.pagination .active .page-link')?.dataset.page || 1;
    loadLogs(currentPage);
}, 30000);
</script>
</body>
</html>
