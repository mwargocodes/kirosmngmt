<?php
require_once 'scripts/ticket-functions.php';

$page = $_GET['p'] ?? 'mine';
$userID = $_SESSION['id'];
$role = $_SESSION['role'];

$tickets = fetchTicketsByFilter($pdo, $page, $userID);
$isAdmin = ($role === 'ok');
$isStaff = in_array($role, ['ok', 'mod']);
?>

<div class="ticket-list">
    <h5 class="mb-3">
        <?php
        $titles = [
            'mine' => 'My Tickets',
            'my_claimed' => 'My Current Tickets',
            'unclaimed' => 'Unclaimed Tickets',
            'claimed' => 'Claimed Tickets',
            'resolved' => 'Resolved Tickets',
            'submit' => 'Submit New Ticket',
            'admin' => 'Admin Tickets',
            'admin_mine' => 'My Admin Tickets',
            'admin_unclaimed' => 'Admin Unclaimed',
            'admin_resolved' => 'Admin Resolved',
            'admin_flags' => 'Admin Flags'
        ];
        echo $titles[$page] ?? 'Tickets';
        ?>
    </h5>

    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">No tickets found in this category.</div>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($tickets as $ticket): ?>
              <?php $authorName = getUserDisplayName($pdo, $ticket['authorID']); ?>
                <a href="tickets.php?p=view&ticketID=<?= $ticket['ticketID'] ?>" class="list-group-item list-group-item-action bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
    <div>
        <strong><?php if($ticket['category'] !== NULL) { echo $ticket['category'], ': ', $ticket['subject']; } else { echo $ticket['subject']; } ?></strong><br>
        <small class="text-muted">Submitted on <?= htmlspecialchars($ticket['submittedOn']) ?> by <?= htmlspecialchars($authorName) ?></small>
    </div>
    <div class="text-end">
        <?php
            $statusColors = [
                'Unclaimed' => 'secondary',
                'Claimed' => 'warning text-dark',
                'Resolved' => 'success'
            ];
            $deptColors = [
                'Moderator' => 'primary',
                'Administrator' => 'danger'
            ];
            $statusBadge = $statusColors[$ticket['status']] ?? 'light';
            $deptBadge = $deptColors[$ticket['department']] ?? 'light';
        ?>
        <span class="badge bg-<?= $statusBadge ?>">
  <?php if ($ticket['status'] === 'Unclaimed'): ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-slash" viewBox="0 0 16 16">
  <path d="M13.879 10.414a2.501 2.501 0 0 0-3.465 3.465zm.707.707-3.465 3.465a2.501 2.501 0 0 0 3.465-3.465m-4.56-1.096a3.5 3.5 0 1 1 4.949 4.95 3.5 3.5 0 0 1-4.95-4.95ZM11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m.256 7a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
</svg> Unclaimed
  <?php elseif ($ticket['status'] === 'Claimed'): ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-check" viewBox="0 0 16 16">
  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
  <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
</svg> Claimed
  <?php elseif ($ticket['status'] === 'Resolved'): ?>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-lock" viewBox="0 0 16 16">
  <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m0 5.996V14H3s-1 0-1-1 1-4 6-4q.845.002 1.544.107a4.5 4.5 0 0 0-.803.918A11 11 0 0 0 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664zM9 13a1 1 0 0 1 1-1v-1a2 2 0 1 1 4 0v1a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1zm3-3a1 1 0 0 0-1 1v1h2v-1a1 1 0 0 0-1-1"/>
</svg> Resolved
  <?php else: ?>
    <?= htmlspecialchars($ticket['status']) ?>
  <?php endif; ?>
</span>

        <?php if (strtolower($ticket['department']) === 'administrator'): ?>
                <span class="badge bg-danger text-light">Administrator</span>
            <?php elseif (strtolower($ticket['department']) === 'moderator'): ?>
                <span class="badge bg-primary text-light">Moderator</span>
            <?php else: ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($ticket['department']) ?></span>
            <?php endif; ?>
    </div>
</a>

            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
