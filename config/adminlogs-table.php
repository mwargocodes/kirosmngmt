<?php
require_once 'database-conn.php';

$perPage = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$part = $_GET['part'] ?? 'table';

// Count total logs
$totalLogs = $pdo->query("SELECT COUNT(*) FROM moderation_log")->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);
$offset = ($page - 1) * $perPage;

// Return table
if ($part === 'table') {
    $stmt = $pdo->prepare("SELECT * FROM moderation_log ORDER BY logID DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="table-responsive">
      <table class="table table-dark table-striped table-sm align-middle">
        <thead>
          <tr>
            <th scope="col">Timestamp</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?= htmlspecialchars($log['timestamp']) ?></td>
              <td><?= $log['action'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    exit;
}

// Return pagination
if ($part === 'pagination') {
    echo '<nav><ul class="pagination justify-content-center">';
    $maxButtons = 6;

    if ($totalPages <= $maxButtons) {
        for ($i = 1; $i <= $totalPages; $i++) {
            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                    <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                  </li>';
        }
    } else {
        $half = floor($maxButtons / 2);
        $start = max(1, $page - $half);
        $end = min($totalPages, $start + $maxButtons - 1);

        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
            echo '<li class="page-item"><a class="page-link pagination-ellipsis" href="#">...</a></li>';
        }

        for ($i = $start; $i <= $end; $i++) {
            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                    <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                  </li>';
        }

        if ($end < $totalPages) {
            echo '<li class="page-item"><a class="page-link pagination-ellipsis" href="#">...</a></li>';
            echo '<li class="page-item"><a class="page-link" href="#" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
        }
    }

    echo '</ul></nav>';
    exit;
}
?>
