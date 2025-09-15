<?php 

require_once 'scripts/database-conn.php';
require_once 'scripts/session-security.php';


//works for all types requiring ID
$type = $_GET['type'] ?? null;
$id = $_GET['id'] ?? null;
$userID = $_SESSION['id'];


if (!$type || !$id || !is_numeric($id)) {
    die ("Invalid report");
}

$reportReason = $_POST['report_reason'] ?? null;
$reportCateogry = $_POST['report_category'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reportReason) {
    // build ticket content
    // append message URL into content body by default

    //establish what kind of type
    $subject = "Reporting a " . ucfirst($type) . ": ";

    // reporting a message (use as a template for building future reports!)
    if ($type === 'message') {
        $subject .= "Message (#$id)";
        $content = "Reported message: <a href='view-message.php?id=$id'>[click to view message]</a><br><br>" . htmlspecialchars($reportReason);
    }
    // reporting a user
    if ($type === 'profile') {
    // fetch displayName for the reported user
    $stmtUser = $pdo->prepare("SELECT displayName FROM accounts WHERE id = ?");
    $stmtUser->execute([$id]);
    $reportedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $reportedName = $reportedUser ? htmlspecialchars($reportedUser['displayName']) : "User #$id";

    $subject .= $reportedName . "(#$id)";
    $content  = "<a href='profile.php?id=$id'>[$reportedName]</a><br><br>"
                . nl2br(htmlspecialchars($reportReason));
    }

    if ($type === 'topic') {
      
    }

    // add into database
    $stmt = $pdo->prepare("INSERT INTO tickets (authorID, submittedOn, subject, content, status) VALUES (?, NOW(), ?, ?, 'Unclaimed')");
    $stmt->execute([$userID, $subject, $content]);

    $ticketID = $pdo->lastInsertId(); // get last ticket ID
    echo "<div class='alert alert-success'>Your report has been submitted. <a href='tickets.php?p=view&ticketID=$ticketID' class='alert-link'>View Ticket #$ticketID</a></div>";
    exit;
} 


include 'scripts/header.php'; ?>
<div class="container mt-3">
    <a href="javascript:history.back()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
</svg> Return</a>
<br>
<form method="post" class="card card-body bg-dark text-light border-light mt-3">
  <h5 class="mb-3">Report <?= ucfirst($type) ?> (#<?= htmlspecialchars($id) ?>)</h5>

  <!----<label for="report_category" class="form-label">Category</label>
  <select name="report_category" id="report_category" class="form-select mb-3" required>
    <option value="" disabled selected>Select a category...</option>
    <option value="Harassment">Harassment</option>
    <option value="Spam">Spam</option>
    <option value="Inappropriate Content">Inappropriate Content</option>
    <option value="Other">Other</option>
  </select>--->

  <label for="report_reason" class="form-label">Reason</label>
  <textarea name="report_reason" id="report_reason" class="form-control mb-3" rows="4" placeholder="Explain why you're reporting this..." required></textarea>

  <button type="submit" class="btn btn-danger">Submit Report</button>
</form>
</div>