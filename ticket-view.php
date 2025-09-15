<?php
// Start output buffering to ensure no premature output
ob_start();

require_once 'scripts/ticket-functions.php';

if (!isset($_GET['ticketID']) || !is_numeric($_GET['ticketID'])) {
    header("Location: tickets.php?p=mine"); // or another fallback page
    exit;
}

$ticketID = (int)$_GET['ticketID'];

// Handle internal note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_note']) && !empty($_POST['note']) && isStaff($pdo, $userID)) {
    $note = trim($_POST['note']);
    if ($note) {
        addTicketNote($pdo, $ticketID, $userID, $note);
        header("Location: tickets.php?p=view&ticketID=$ticketID");
        exit;
    }
}

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply']) && !empty($_POST['reply'])) {
    $reply = trim($_POST['reply']);
    if ($reply) {
        addTicketReply($pdo, $ticketID, $userID, $reply);
        header("Location: tickets.php?p=view&ticketID=$ticketID");
        exit;
    }
}

// Load ticket
$ticket = fetchTicketById($pdo, $ticketID);
if (!$ticket) {
    header("Location: tickets.php?p=mine&error=notfound");
    exit;
}

$isStaff = in_array($_SESSION['role'], ['mod', 'ok']);
$authorName = getDisplayName($pdo, $ticket['authorID']);
$claimedName = $isStaff ? getDisplayName($pdo, $ticket['claimedBy']) : 'A Moderator';
$resolvedName = $isStaff ? getDisplayName($pdo, $ticket['resolvedBy']) : 'A Moderator';

// End of header-safe section
?>

<div class="ticket-view container bg-dark p-3 rounded border border-secondary">
    <?php if ($ticket['adminFlag'] === 1) {
          if ($_SESSION['role'] === 'mod') { 
        echo "<div class='alert alert-primary'>This ticket has been flagged for admin assistance. Someone should be along shortly to help!</div>";
    }
    if ($_SESSION['role'] === 'ok') {
        echo "<div class='alert alert-primary d-flex justify-content-between align-items-center'>
              <span>This ticket has been flagged for admin assistance by the moderator handling it. Click 'I got it!' if you're able to assist!</span></div>";
    }
    } ?>

    <h5 class="mb-3"><?php if ($ticket['department'] === 'Moderator') { echo '<span><img src=https://imgur.com/F2gC0nl.png title="This ticket is in the Moderator Department."></span>'; } else { echo '<span><img src=https://imgur.com/ZkjULOf.png title="This ticket is in the Administrator Department."></span>'; } ?> Ticket #<?= htmlspecialchars($ticketID) ?></h5>

    <div class="d-flex justify-content-between align-items-center bg-secondary px-2 py-1 rounded-top">
        <strong>
<?php 
if (!empty($ticket['category'])) {
    echo htmlspecialchars($ticket['category']) . ': ';
} 
echo htmlspecialchars($ticket['subject']); 
?>
</strong>

        <?php if ($isStaff && $ticket['status'] !== 'Resolved'): ?>
            <form class="d-flex gap-2 align-items-center" method="post" action="scripts/ticket-functions.php" name="ticketActions">
                <input type="hidden" name="ticketID" value="<?= $ticketID ?>">
                <input type="hidden" name="userID" value="<?= $_SESSION['id'] ?>">
                <select name="action" class="form-select form-select-sm">
                    <option disabled selected>Moderator Tools</option>
                    <?php if ($ticket['status'] === 'Claimed') { ?>
                    <option value="resolve">Resolve Ticket</option>
                    <?php if ($ticket['adminFlag'] !== 1) { ?>
                    <option value="admin_flag">Flag for Admin Assistance</option>
                    <?php } ?>
                    <?php } else { ?>
                    <option value="claim">Claim Ticket</option>
                    <?php } ?>
                    <option value="auto_bug">AUTO: Do Not Report Bugs</option>
                    <option value="auto_contact">AUTO: support@kiros-mmorpg.com</option>
                    <option value="admin_escalate">Escalate to Admin Department</option>
                    <?php if ($ticket['department'] === 'Administrator' || $ticket['status'] === 'Claimed') { ?>
                    <option value="mod_deescalate">Move to Moderator Department</option>
                    <?php } ?>
                    <?php if ($ticket['adminFlag'] === 1 && $_SESSION['role'] === 'ok') { ?>
                    <option value="got_it">I Got It!</option>
                    <?php } ?>
                </select>
                <button type="submit" class="btn btn-sm btn-success">Go</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="bg-dark-subtle px-3 py-2 text-light border-bottom">
        <strong>Submitted by:</strong> <?= htmlspecialchars($authorName) ?>
        <strong>on</strong> <?= htmlspecialchars($ticket['submittedOn']) ?>
    </div>

    <div class="p-3 bg-body text-light border-bottom" style="white-space: pre-wrap;">
        <?= nl2br(strip_tags($ticket['content'], '<a><b><i><u><br>')) ?>
    </div>

    <?php if ($ticket['status'] === 'Claimed'): ?>
        <div class="bg-dark-subtle px-3 py-2 text-light d-flex justify-content-between">
            <span><strong>Claimed By:</strong> <?= htmlspecialchars($claimedName) ?></span>
            <span><strong>Claimed On:</strong> <?= htmlspecialchars($ticket['claimedOn']) ?></span>
        </div>
    <?php elseif ($ticket['status'] === 'Resolved'): ?>
        <div class="bg-dark-subtle px-3 py-2 text-light d-flex justify-content-between">
            <span><strong>Resolved By:</strong> <?= htmlspecialchars($resolvedName) ?></span>
            <span><strong>Resolved On:</strong> <?= htmlspecialchars($ticket['resolvedOn']) ?></span>
        </div>
    <?php elseif ($ticket['status'] === 'Unclaimed'): ?>
        <div class="bg-dark-subtle px-3 py-2 text-light d-flex justify-content-between">
            <span>Unclaimed</span>
        </div>
    <?php endif ?>

    <!-- Replies section -->
     <br />
<?php
$replies = getTicketReplies($pdo, $ticketID);
?>
<div class="bg-dark-subtle px-3 py-2 text-light border-top">
  <h6>Replies</h6></div>
  <?php foreach ($replies as $reply): ?>
    <div class="border rounded p-2 mb-2 bg-body-secondary">
      <div class="small text-muted">
        <?= isStaff($pdo, $reply['author_id']) ? ($_SESSION['role'] === 'ok' || $_SESSION['role'] === 'mod' ? getDisplayName($pdo, $reply['author_id']) : 'A Moderator') : getDisplayName($pdo, $reply['author_id']); ?>
        • <?= date('M d, Y H:i', strtotime($reply['created_at'])) ?>
      </div>
      <div><?= nl2br(strip_tags($reply['content'], '<b><u><i><a><br>')) ?>
      <br />
      <div class="small text-muted d-flex justify-content-between">
    <div>Reply ID: <?= htmlspecialchars($reply['id']) ?></div>
    <div>
      <?php if ($_SESSION['role'] === 'ok') { ?>
        <!---<form method="POST" action="scripts/admin-actions.php" class="delete-reply-form" onsubmit="return confirm('Delete this reply?')">
                            <input type="hidden" name="action" value="delete_reply">
                            <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                             <input type="hidden" name="ticket_id" value="<?= $ticketID ?>">
        <button class="btn btn-sm btn-danger" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">Delete</button>
                <form method="POST" action="scripts/admin-actions.php" class="delete-reply-form" onsubmit="return confirm('Delete this reply?')">
                            <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                             <input type="hidden" name="ticket_id" value="<?= $ticketID ?>">
                             <input type="hidden" name="edit_note_init" value="1">
        <button class="btn btn-sm btn-warning" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">Edit</button>--->
        <?php } ?>
    </div>
</div></div></div>

  <?php endforeach; ?>

<?php
$canReply = false;

// Only the author can reply until the ticket has been claimed
if ($ticket['authorID'] === $userID) {
    $canReply = true; // author can always reply
} elseif (isStaff($pdo, $userID) && $ticket['status'] === 'Claimed') {
    $canReply = true; // staff can only reply if claimed
}
?>

<?php if ($canReply): ?>
<form method="post" class="mt-3">
  <textarea name="reply" class="form-control mb-2" rows="3" placeholder="Write a reply..."></textarea>
  <button class="btn btn-primary" type="submit" name="submit_reply">Post Reply</button>
  <br />
</form>
<?php endif; ?>

    <!-- Notes section -->
     <hr />
<?php if (isStaff($pdo, $userID)): ?>
  <div class="bg-dark-subtle px-3 py-2 text-light border-top">
    <h6>Internal Notes</h6>
    <?php
    $notes = getTicketNotes($pdo, $ticketID);
    foreach ($notes as $note):
    ?>
      <div class="border-start border-4 border-success ps-2 mb-2 bg-dark-subtle rounded">
        <div class="small text-muted">
          <?= getDisplayName($pdo, $note['author_id']) ?> • <?= date('M d, Y H:i', strtotime($note['created_at'])) ?>
        </div>
        <div><?= nl2br(htmlspecialchars($note['content'])) ?></div>
      </div>
    <?php endforeach; ?>

    <form method="post" class="mt-2">
      <textarea name="note" class="form-control mb-2" rows="2" placeholder="Add an internal note..."></textarea>
      <button class="btn btn-primary btn-sm" type="submit" name="submit_note">Add Note</button>
    </form>
  </div>
<?php endif; ?>

</div>
