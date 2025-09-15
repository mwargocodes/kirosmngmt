<?php
require_once 'scripts/database-conn.php';
require_once 'scripts/session-security.php';

if (!isset($_SESSION['id'])) {
    die("You must be logged in to submit a ticket.");
}

$filter = $_GET['p'] ?? 'submit';

$userID = $_SESSION['id'];
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $extraInfo = trim($_POST['extraInfo'] ?? '');

    if (empty($category) || empty($subject) || empty($content)) {
        $errors[] = "All fields are required.";
    } else {
        try {
            $fullContent = $extraInfo ? "[Extra Info: {$extraInfo}]\n\n" . $content : $content;

            $stmt = $pdo->prepare("INSERT INTO tickets (authorID, submittedOn, category, subject, content, status) VALUES (:authorID, NOW(), :category, :subject, :content, 'Unclaimed')");
            $stmt->execute([
                ':authorID' => $userID,
                ':category' => $category,
                ':subject' => $subject,
                ':content' => $fullContent
            ]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Error submitting ticket: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Submit Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="bg-dark text-white">
<div class="container mt-4">
    <h3>Submit a New Ticket</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">Ticket submitted successfully!</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?= implode('<br>', $errors); ?>
        </div>
    <?php endif; ?>

    <form method="post" id="ticketForm">
        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" name="category" id="category" required>
                <option value="">Select a category</option>
                <option value="My Account">My Account</option>
                <option value="Game Issue">Game Issue</option>
                <option value="Forum Issue">Forum Issue</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <select class="form-select" name="subject" id="subject" required>
                <option value="">Select a subject</option>
                <option value="Login Problem">Login Problem</option>
                <option value="Password Reset">Password Reset</option>
                <option value="Player Report">Player Report</option>
                <option value="Bug Report">Bug Report</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Extra Info for 'Player Report' -->
        <div class="mb-3 hidden" id="player-report-info">
            <label for="extraInfo" class="form-label">Reported Player's Username or ID</label>
            <input type="text" class="form-control" name="extraInfo" placeholder="Enter username or ID">
        </div>

        <!-- Future dynamic sections can go here -->

        <div class="mb-3">
            <label for="content" class="form-label">Describe your issue</label>
            <textarea class="form-control" name="content" id="content" rows="8" maxlength="8000" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Ticket</button>
    </form>
</div>

<script>
document.getElementById('subject').addEventListener('change', function () {
    const value = this.value;
    document.getElementById('player-report-info').classList.toggle('hidden', value !== 'Player Report');
});
</script>
</body>
</html>
