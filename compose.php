<?php
require_once 'scripts/database-conn.php';
require_once 'scripts/session-security.php';

// You must be logged in
if (!isset($_SESSION['id'])) {
    die("You must be logged in to send messages.");
}

$userID = $_SESSION['id'];
$userRole = $_SESSION['role'];
?>

<div class="container mt-4">
    <div class="card bg-dark text-white border-light">
        <div class="card-header">
            <h5 class="mb-0">Send a Message</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="scripts/send-message.php">
                <div class="mb-3">
                    <label for="recipient_id" class="form-label">Recipient User ID</label>
                    <input type="number" name="recipient_id" id="recipient_id" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" maxlength="255" required>
                </div>

                <div class="mb-3">
                    <label for="body" class="form-label">Message Body</label>
                    <textarea name="body" id="body" class="form-control" rows="6" required></textarea>
                </div>

                <button type="submit" class="btn btn-success">Send Message</button>
            </form>
        </div>
    </div>
</div>
