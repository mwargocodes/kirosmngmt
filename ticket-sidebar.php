<?php
$userID = $_SESSION['id'];
$isAdmin = in_array($_SESSION['role'], ['mod', 'ok']);
$myTickets = countTickets($pdo, 'mine', $userID);
$unclaimed = countTickets($pdo, 'unclaimed');
$adminFlags = countTickets($pdo, 'admin_flags');
?>

<h5><b>Help Desk</b></h5>
<ul class="nav flex-column">
    <li><a href="tickets.php?p=mine">My Tickets <?= badge($myTickets) ?></a></li>
    <li><a href="tickets.php?p=submit">Submit New Ticket</a></li>
    <li><a href="tickets.php?p=unclaimed">Unclaimed Tickets <?= badge($unclaimed) ?></a></li>
    <li><a href="tickets.php?p=claimed">Claimed Tickets</a></li>
    <li><a href="tickets.php?p=resolved">Resolved Tickets</a></li>
    <?php if ($isAdmin): ?>
        <hr>
        <li><a href="tickets.php?p=admin_mine">My Tickets</a></li>
        <li><a href="tickets.php?p=admin_unclaimed">Unclaimed Tickets</a></li>
        <li><a href="tickets.php?p=admin_resolved">Resolved Tickets</a></li>
        <li><a href="tickets.php?p=admin_flags">Admin Flags <?= badge($adminFlags) ?></a></li>
    <?php endif; ?>
</ul>

<?php
function badge($count) {
    return $count > 0 ? "<span class='badge bg-danger'>$count</span>" : "";
}
?>
