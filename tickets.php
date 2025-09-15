<?php
ob_start();
require_once 'scripts/database-conn.php';
require_once 'scripts/session-security.php';
require_once 'scripts/ticket-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userID = $_SESSION['id'] ?? null;
$role = $_SESSION['role'] ?? 'user';
$isAdmin = in_array($role, ['mod', 'ok']);
$page = $_GET['p'] ?? 'mine';
$ticketID = $_GET['id'] ?? null;
?>
    <style>
        .sidebar-link.active {
            background-color: #333;
            font-weight: bold;
        }
    </style>
    <?php include 'scripts/header.php'; ?>
<body>
<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-header">Help Desk</div>
                <div class="list-group list-group-flush">
                    <a href="tickets.php?p=submit" class="list-group-item sidebar-link <?= $page === 'submit' ? 'active' : '' ?>">Submit New Ticket</a>
                    <a href="tickets.php?p=mine" class="list-group-item sidebar-link <?= $page === 'mine' ? 'active' : '' ?>">My Tickets</a>
                    <?php if ($role === 'ok' || $role === 'mod') { ?>
                    <a href="tickets.php?p=my_claimed" class="list-group-item sidebar-link <?= $page === 'my_claimed' ? 'active' : '' ?>">Current Tickets</a>
                    <a href="tickets.php?p=unclaimed" class="list-group-item sidebar-link <?= $page === 'unclaimed' ? 'active' : '' ?>">
                        Unclaimed Tickets <span class="badge bg-danger float-end"><?= getTicketCount($pdo, 'Unclaimed') ?></span>
                    </a>
                    <a href="tickets.php?p=claimed" class="list-group-item sidebar-link <?= $page === 'claimed' ? 'active' : '' ?>">Claimed Tickets</a>
                    <a href="tickets.php?p=resolved" class="list-group-item sidebar-link <?= $page === 'resolved' ? 'active' : '' ?>">Resolved Tickets</a>
                    <?php } ?>

                    <?php if ($isAdmin): ?>
                    <div class="card-header border-top mt-2">Admin Tickets</div>
                    <a href="tickets.php?p=admin_mine" class="list-group-item sidebar-link <?= $page === 'admin_mine' ? 'active' : '' ?>">My Tickets</a>
                    <a href="tickets.php?p=admin_unclaimed" class="list-group-item sidebar-link <?= $page === 'admin_unclaimed' ? 'active' : '' ?>">
                        Unclaimed <span class="badge bg-danger float-end"><?= getAdminTicketCount($pdo, 'Unclaimed', true) ?></span>
                    </a>
                    <a href="tickets.php?p=admin_resolved" class="list-group-item sidebar-link <?= $page === 'admin_resolved' ? 'active' : '' ?>">Resolved</a>
                    <a href="tickets.php?p=admin_flags" class="list-group-item sidebar-link <?= $page === 'admin_flags' ? 'active' : '' ?>">
                        Admin Flags <span class="badge bg-danger float-end"><?= getFlaggedCount($pdo) ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <?php
                    if ($page === 'ticket' && is_numeric($ticketID)) {
                        include 'ticket-view.php';
                    } else {
                        switch ($page) {
    case 'mine':
        include 'ticket-list.php';
        break;
    case 'unclaimed':
        include 'ticket-list.php';
        break;
    case 'claimed':
        include 'ticket-list.php';
        break;
    case 'my_claimed':
        include 'ticket-list.php';
        break;
    case 'resolved':
        include 'ticket-list.php';
        break;
    case 'admin_mine':
        include 'ticket-list.php';
        break;
    case 'admin_unclaimed':
        include 'ticket-list.php';
        break;
    case 'admin_resolved':
        include 'ticket-list.php';
        break;
    case 'admin_flags':
        include 'ticket-list.php';
        break;
    case 'submit':
        include 'submit-ticket.php';
        break;
    case 'view':
        include 'ticket-view.php';
        break;
    default:
        echo "<div class='alert alert-danger'>Invalid page specified.</div>";
        break;
                        }
                    }

                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>



<?php
// Helper functions
function getTicketCount($pdo, $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE status = :status");
    $stmt->execute([':status' => $status]);
    return $stmt->fetchColumn();
}

function getAdminTicketCount($pdo, $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE department = 'Administrator' AND status = :status");
    $stmt->execute([':status' => $status]);
    return $stmt->fetchColumn();
}

function getFlaggedCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE adminFlag = 1");
    return $stmt->fetchColumn();
}
?>
<?php
ob_end_flush();
?>