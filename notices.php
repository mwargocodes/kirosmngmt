<?php
require_once 'scripts/database-conn.php';
require 'scripts/session-security.php';
require_once 'scripts/logger.php';
require_once 'scripts/staff-notices.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$userID = $_SESSION['id'];
$userRole = $_SESSION['role'];

// verify session, then check read status/update

markNoticesRead($pdo, $userID);

$username = $_SESSION['username'] ?? null;
if (!$username) die("No username found in this session.");
if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset(); session_destroy();
    die('Session hijack attempt detected.');
}
$timeout = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset(); session_destroy();
    die("Session expired due to inactivity.");
}
$_SESSION['last_activity'] = time();

$stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username");
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();
if (!$user) die("No database access.");

// Handle new notice submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userRole === 'ok') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if ($title && $content) {
        addStaffNotice($pdo, $userID, $title, $content);
    }
}

// Get notices
$recentNotices = getRecentStaffNotices($pdo, 5);

// Archive pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$archiveNotices = getStaffNoticesPage($pdo, $page, $perPage);
$totalNotices = countStaffNotices($pdo);
$totalPages = ceil($totalNotices / $perPage);

// handle deletion for admins only

if (isset($_POST['delete_notice']) && $userRole === 'ok') {
    $noticeID = (int)$_POST['delete_notice'];
    deleteStaffNotice($pdo, $noticeID);
}

?>
<!DOCTYPE HTML>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Staff Notices</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
  <?php include 'scripts/header.php'; ?>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<div class="container-fluid mt-3 d-flex gap-3" style="align-items: flex-start;">
  
  <!-- Compose Notice (Admins only) -->
  <?php if ($userRole === 'ok'): ?>
  <div class="flex-grow-1 bg-dark text-white p-3 rounded" style="min-width: 700px; max-width: 900px;">
    <h5>Compose New Notice</h5>
    <form method="post">
      <div class="mb-2">
        <input type="text" name="title" class="form-control" placeholder="Notice Title" required>
      </div>
      <div class="mb-2">
        <textarea name="content" class="form-control" rows="4" placeholder="Write your notice here..." required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Post Notice</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Recent Notices -->
  <div class="bg-dark text-white p-3 rounded" style="width: 65%; max-height: 600px; overflow-y: auto;">
    <h5>Recent Notices</h5>
    <?php foreach ($recentNotices as $notice): ?>
      <div class="border-bottom pb-2 mb-2">
        <strong><?= htmlspecialchars($notice['title']) ?></strong><br>
        <small>Posted by <?= htmlspecialchars($notice['displayName']) ?> on <?= htmlspecialchars($notice['created_at']) ?></small>
        <p class="mb-0"><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
        <?php if ($userRole === 'ok'): ?>
    <form method="post" class="d-inline">
        <button type="submit" name="delete_notice" value="<?= $notice['noticeID'] ?>" 
                class="btn btn-sm btn-danger">Delete</button>
    </form>
<?php endif; ?>

      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Archive -->
<div class="container mt-4">
  <h5>Archived Notices</h5>
  <?php foreach ($archiveNotices as $notice): ?>
    <div class="bg-dark text-white p-3 rounded mb-3">
      <h6><?= htmlspecialchars($notice['title']) ?></h6>
      <small>Posted by <?= htmlspecialchars($notice['username']) ?> on <?= htmlspecialchars($notice['created_at']) ?></small>
      <p><?= nl2br(htmlspecialchars($notice['content'])) ?></p>
      <?php if ($userRole === 'ok'): ?>
    <form method="post" class="d-inline">
        <button type="submit" name="delete_notice" value="<?= $notice['noticeID'] ?>" 
                class="btn btn-sm btn-danger">Delete</button>
    </form>
<?php endif; ?>

    </div>
  <?php endforeach; ?>

  <!-- Pagination -->
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

</body>
</html>


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
