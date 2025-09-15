<?php
session_start();
require_once 'scripts/database-conn.php';
require_once 'scripts/logger.php';
require_once 'scripts/ticket-functions.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

error_log("Session ID: " . session_id());
error_log("Session Role: " . ($_SESSION['role'] ?? 'none'));

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'ok') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = intval($_GET['user_id'] ?? 0);
if (!$userId) {
    echo "Invalid user ID.";
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, email, ip_address, displayName, profileImage, birthdate, accountRole, securityQuestion, accountHealth FROM accounts WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

playerAdminPanelVisit(
  $_SESSION['id'],
  $_SESSION['displayName'],
  $user['id'],
  $user['displayName']
);


if (!$user) {
    echo "User not found.";
    exit;
}

// Fetch notes
$notesStmt = $pdo->prepare("SELECT id, note, created_at FROM user_notes WHERE user_id = :uid ORDER BY created_at ASC");
$notesStmt->execute([':uid' => $userId]);
$notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_action'], $_POST['adjust_user_id']) && ($_SESSION['role'] === 'ok')) {
    $adjustUserID = (int)$_POST['adjust_user_id'];
    $action = $_POST['adjust_action'];
    $adminID = $_SESSION['id'];

    // Fetch current standing
    $stmt = $pdo->prepare("SELECT accountHealth FROM accounts WHERE id = ?");
    $stmt->execute([$adjustUserID]);
    $current = (int)($stmt->fetchColumn() ?? 0);

    // fallback if no user data found
    if (!$adjustUserID) {
        echo "<div class='alert alert-danger'>User not found. Invalid user ID.</div>";
        return;
    }

    switch ($action) {
        case 'increase_5':
            $newStanding = min(100, $current + 5);
            $changeLabel = '+5%';
            break;
        case 'increase_10':
            $newStanding = min(100, $current + 10);
            $changeLabel = '+10%';
            break;
        case 'decrease_5':
            $newStanding = max(0, $current - 5);
            $changeLabel = '-5%';
            break;
        case 'decrease_10':
            $newStanding = max(0, $current - 10);
            $changeLabel = '-10%';
            break;
        case 'reset':
            $newStanding = 100;
            $changeLabel = 'Reset standing to 100%';
            break;
        default:
            $newStanding = $current;
            $changeLabel = 'No Change';
            break;
    }

    // Only proceed if there's an actual change
    if ($newStanding !== $current) {
        // Update in database
        $stmt = $pdo->prepare("UPDATE accounts SET accountHealth = ? WHERE id = ?");
        $stmt->execute([$newStanding, $adjustUserID]);

        $ip = $_SERVER['REMOTE_ADDR'];
        $displayName = $_SESSION['displayName'];
        $targetUsername = $user['displayName'];


        // Log admin action
        $logStmt = $pdo->prepare("INSERT INTO moderation_log (modID, action, ip, timestamp) VALUES (?, ?, ?, NOW())");
        $logStmt->execute([
            $adminID,
            "<a href='profile.php?id={$adminID}' target='_blank'>{$displayName} (#{$adminID})</a> manually adjusted account standing for user <a href='profile.php?id={$adjustUserID}' target='_blank'>{$targetUsername} (#{$adjustUserID})</a>. Standing was {$current}% and is now {$newStanding}% ({$changeLabel}).",
            $ip
        ]);
    }
    // reload the page to reflect changes
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7Wj6j7nHDK5GZl5WbLzDyp1G8nXKZdJQnN4" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
</head>
<body style="background-color:rgb(45, 50, 51)">
<div class="container mt-5" style="color:white;">
    <div id="admin-panel" data-user-id="<?= htmlspecialchars($user['id']) ?>"></div>
    <h2><center>Admin Panel</center></h2>
    <hr>
    <b><center>User: <?php echo htmlspecialchars($user['displayName']); echo ' '; echo '(#' . htmlspecialchars($user['id']) . ')'?></center></b>
<?php
// Determine badge styling based on role
$role = $user['accountRole'] ?? 'user';
$badgeClass = match ($role) {
    'ok' => 'bg-danger',       // Admin
    'mod' => 'bg-primary',     // Moderator
    'vip' => 'bg-warning text-dark', // VIP
    default => 'bg-secondary', // Standard User
};

$roleLabel = match ($role) {
    'ok' => 'Administrator',
    'mod' => 'Moderator',
    'vip' => 'VIP',
    default => 'User',
};
?>

<div class="text-center my-2">
    <span class="badge <?= $badgeClass ?>" style="font-size: 1.1rem; padding: .6em 1.2em;">
        <?= $roleLabel ?>
    </span>
</div>
                <?php
                $health = (int)$user['accountHealth'] ?? 0;

                // Choose a label color based on health
                if ($health >= 80) {
                    $healthClass = 'text-success'; // green
                } elseif ($health >= 50) {
                    $healthClass = 'text-warning'; // yellow
                } else {
                    $healthClass = 'text-danger';  // red
                }
            ?>
    <center>
    <label for="account-standing">Account Standing:</label>
    <span id="account-standing" class="<?= $healthClass ?> fw-bold ms-2"><?= $health ?>%</span>
    </center>

<br />
    <h4>Reset User Profile Options</h4>
    <small>Please only use this to fix moderator mistakes and/or system bugs.</small>
        <?php if ($_SESSION['role'] === 'ok'): ?>
        <div align="center">
<form method="post" class="mt-2 d-flex align-items-center gap-2">
    <input type="hidden" name="adjust_user_id" value="<?= $user['id'] ?>">
    <select name="adjust_action" class="form-select form-select-sm w-auto">
        <option selected disabled>Admin Adjust Account Standing</option>
        <option value="increase_5">Increase by 5%</option>
        <option value="increase_10">Increase by 10%</option>
        <option value="decrease_5">Decrease by 5%</option>
        <option value="decrease_10">Decrease by 10%</option>
        <option value="reset">Reset to 100%</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
</form>
    </div>
<?php endif; ?>
<br />
    <form method="POST" action="scripts/admin-actions.php" id="reset-options-form">
    <input type="hidden" name="action" value="reset_options">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reset_displayName" id="resetDisplayName">
        <label class="form-check-label" for="resetDisplayName">Reset Display Name</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reset_profileImage" id="resetProfileImage">
        <label class="form-check-label" for="resetProfileImage">Reset Profile Image</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="reset_securityQuestion" id="resetSecurityQuestion">
        <label class="form-check-label" for="resetSecurityQuestion">Reset Security Question</label>
    </div>

    <button type="submit" class="btn btn-warning mt-3" onclick="return confirm('Are you sure you want to reset this?')">Reset</button>
</form>
<hr>
    
    <h4>Notes History</h4>
    <style>
      #notes-box {
    max-height: 300px;
    overflow-y: auto;
    background-color: white;
    color: black;
    padding: 1rem;
    border-radius: .5rem;
}
    </style>
    <?php
    $noteStmt = $pdo->prepare("SELECT n.id, n.note, n.created_at, a.username AS admin_user, a.displayName AS admin_name, a.id AS staff_id
                               FROM user_notes n 
                               JOIN accounts a ON n.staff_id = a.id 
                               WHERE n.user_id = :uid 
                               ORDER BY n.created_at ASC");
    $noteStmt->execute([':uid' => $user['id']]);
    $notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);

   if (!$notes): ?>
    <p class="text-muted"><font color="white">No notes found.</font></p>
<?php else: ?>
    <div id="notes-box" class="p-3 bg-white rounded border" style="color: black; max-height: 300px; overflow-y: auto;">
        <?php foreach ($notes as $note): ?>
            <div class="mb-3 pb-2 border-bottom">
                <?= htmlspecialchars($note['admin_name']) ?>
                <small class="text-muted"><?= htmlspecialchars($note['created_at']) ?></small><br>
                <div id="note-display-<?= $note['id'] ?>"><?= nl2br(htmlspecialchars($note['note'])) ?></div>

                <?php
                $canEditOrDelete = ($_SESSION['id'] === $note['staff_id'] || $_SESSION['role'] === 'ok');
                if ($canEditOrDelete):
                ?>
                    <div class="mt-2">
                        <form method="POST" class="d-inline-block me-2">
                            <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                             <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="edit_note_init" value="1">
                            <button class="btn btn-sm btn-info" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">Edit</button>
                        </form>
                        <form method="POST" action="scripts/admin-actions.php" class="delete-note-form" onsubmit="return confirm('Delete this note?')">
                            <input type="hidden" name="action" value="delete_note">
                            <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                             <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button class="btn btn-sm btn-danger" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (isset($_POST['edit_note_init'], $_POST['note_id']) && $_POST['note_id'] == $note['id']): ?>
                    <form method="POST" class="mt-3 edit-note-form" data-note-id="<?= $note['id'] ?>">
                        <textarea name="content" class="form-control mb-2" rows="4"><?= htmlspecialchars($note['note']) ?></textarea>
                        <button class="btn btn-sm btn-success" onsubmit="return confirm('Edit this note?')">Save</button>
                        <a href="admin-panel.php?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


    <form method="POST" class="mb-4" id="note-form">
        <input type="hidden" name="action" value="add_note">
        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
        <div class="mb-3">
            <label for="note" class="form-label">Add New Note:</label>
            <textarea name="note" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" name="add_note" class="btn btn-primary" onclick="return confirm('Submit this note?')">Submit Note</button>
    </form>

    <hr>

    <h4>Take Action</h4>
    <form method="post" action="scripts/submit-violation.php" class="mb-4" id="violationForm">
    <input type="hidden" name="targetUserID" value="<?= $user['id'] ?>">

    <div class="mb-3 row">
        <div class="mb-3">
  <label for="violationSeverity" class="form-label">Violation Severity</label>
  <select name="violation_severity" id="violationSeverity" class="form-select" required>
    <option value="">Select severity</option>
    <option value="Reminder">Reminder</option>
    <option value="Warning">Warning</option>
    <option value="Game Ban">Game Ban</option>
    <option value="Site Ban">Site Ban</option>
  </select>
</div>

        <label class="col-sm-3 col-form-label">Violation Type</label>
        <div class="col-sm-9 d-flex gap-2">
            <select name="violationType" class="form-select" id="violationType" required>
                <option value="">Select</option>
                <option value="tos">Terms of Service</option>
                <option value="coc">Code of Conduct</option>
                <option value="forum">Forum Rules</option>
            </select>
            <input type="text" name="ruleBullet" class="form-control" placeholder="Bullet point or rule #..." required>
        </div>
    </div>

    <div class="mb-3 row" id="forumLinkRow" style="display:none;">
        <label class="col-sm-3 col-form-label">Forum Rule Link</label>
        <div class="col-sm-9">
            <input type="url" name="forumLink" class="form-control" placeholder="Link to forum rule topic">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Evidence (sent to user)</label>
        <textarea name="evidence" class="form-control" rows="4" required></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Action Notes</label>
        <textarea name="internalNotes" class="form-control" rows="3" placeholder="These are internal, so add whatever you want to note here!"></textarea>
    </div>
    <button type="submit" class="btn btn-warning">Submit Violation</button>
</form>

<!-- JavaScript to toggle forum link -->
<script>
document.getElementById('violationType').addEventListener('change', function () {
    document.getElementById('forumLinkRow').style.display = (this.value === 'forum') ? 'flex' : 'none';
});
</script>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM mod_actions WHERE userID = ? AND date >= DATE_SUB(NOW(), INTERVAL 5 MONTH) ORDER BY date DESC");
        $stmt->execute([$user['id']]);
        $actions = $stmt->fetchAll();

         if (!empty($actions)): ?>
<div class="accordion" id="violationAccordion">
  <?php foreach ($actions as $index => $v): 
      $staffName = getDisplayName($pdo, $v['staffID']);
      $timestamp = date('F j, Y, g:i a', strtotime($v['date']));
      $ruleLabel = match ($v['rule_type']) {
          'tos' => 'Terms of Service',
          'coc' => 'Code of Conduct',
          'forum' => 'Forum Rules',
          default => 'Rule'
      };
  ?>
  <div class="accordion-item bg-dark text-light border-secondary">
    <h2 class="accordion-header" id="heading<?= $index ?>">
      <button class="accordion-button collapsed bg-dark text-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
        <div class="container-fluid d-flex justify-content-between align-items-center">
          <div><span class="badge bg-danger me-2"><?= $v['standing_change'] ?>%</span></div>
          <div class="text-truncate mx-2">
            <?= $v['action'] ?> for <?= $ruleLabel ?> - "<?= htmlspecialchars($v['rule_detail']) ?>"
          </div>
          <div class="ms-auto small"><?= $timestamp ?></div>
        </div>
      </button>
    </h2>
    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#violationAccordion">
      <div class="accordion-body">
        <p><strong>Issued By:</strong> <?= htmlspecialchars($staffName) ?> (#<?= $v['staffID'] ?>)</p>
        <p><strong>Evidence:</strong><br><?= nl2br(htmlspecialchars($v['evidence'])) ?></p>
        <p><strong>Action Notes:</strong><br><?= nl2br(htmlspecialchars($v['internal_note'])) ?></p>
        <?php if (!empty($v['forum_link'])): ?>
          <p><strong>Forum Link:</strong> <a href="<?= htmlspecialchars($v['forum_link']) ?>" target="_blank"><?= htmlspecialchars($v['forum_link']) ?></a></p>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'ok'): ?>
        <form method="post" action="scripts/delete-mod-action.php" id="admin-delete-action" onsubmit="return confirm('Are you sure you want to delete this action?');">
          <input type="hidden" name="action_id" value="<?= $v['id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger mt-2">Delete</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
  <div class="alert alert-secondary">No recent violations found for this user.</div>
<?php endif; ?>


    <hr>
    <?php //birthdate calculator

    $birthdayStr = $user['birthdate'];
    $birthday = new DateTime($birthdayStr);
    $today = new DateTime();
    $age = $today->diff($birthday)->y;

    ?>

    <div class="row justify-content-start">
    <div class="col-4">
        <h4>Account Information</h4>
            <p>Email: <?= htmlspecialchars($user['email']) ?></p>
            <p>IP: <?= htmlspecialchars($user['ip_address']) ?></p>
            <p>Security Question: <?= htmlspecialchars($user['securityQuestion']) ?></p>
            <p>Birthdate: <?= htmlspecialchars($user['birthdate']) ?> (<?php echo $age ?> years old)</p>
            <p>User Role: <?php if ($user['accountRole'] === 'ok') { echo 'Administrator'; } elseif ($user['accountRole'] === 'mod') { echo 'Moderator'; } ?></p>
    </div>
    <div class="col-4"></div>
    <div class="col-4">
        <h4>Admin Options</h4>
        <form method="POST" action="scripts/admin-actions.php" id="admin-role-form">
        <input type="hidden" name="action" value="update_role">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

        <div class="form-check">
        <input class="form-check-input" type="radio" name="accountRole" id="modUser" value="mod"
               <?= $user['accountRole'] === 'mod' ? 'checked' : '' ?>>
        <label class="form-check-label" for="modUser">Make User a Moderator</label>
        </div>

        <div class="form-check">
                <input class="form-check-input" type="radio" name="accountRole" id="adminUser" value="ok"
               <?= $user['accountRole'] === 'ok' ? 'checked' : '' ?>>
        <label class="form-check-label" for="adminUser">Make User an Administrator</label>
        </div>

        <div class="form-check">
                <input class="form-check-input" type="radio" name="accountRole" id="vipUser" value="vip"
               <?= $user['accountRole'] === 'vip' ? 'checked' : '' ?>>
        <label class="form-check-label" for="vipUser">Make User a VIP</label>
        <div class="form-check">
        <input class="form-check-input" type="radio" name="accountRole" id="normalUser" value="user"
        <?= $user['accountRole'] === 'user' ? 'checked' : '' ?>>
        <label class="form-check-label" for="normalUser">Remove User Role</label>
        </div>
        <div class="row justify-content-end">
        <button class="btn btn-sm btn-success" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .55rem;">Apply</button>
        </div>
        </form>
    </div>
  </div>
</div>
<script>
document.getElementById('violationForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    fetch('scripts/submit-violation.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully issued a ${data.severity} to this player.`);

            // Close the panel (adjust selector to match yours)
            const panel = document.getElementById('admin-panel');
            if (panel) {
                panel.classList.remove('show'); // or .open, .visible, etc.
            }

            // Reload the profile view or entire page
            window.parent.location.href = `profile.php?id=${userId}`;
        } else {
            alert("Violation submitted but no confirmation returned.");
        }
    })
    .catch(err => {
        console.error("Error submitting violation:", err);
        alert("An error occurred while submitting the violation.");
    });
});
</script>

</body>
<script>
document.getElementById('reset-options-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = new FormData(this);
    const response = await fetch('scripts/admin-actions.php', {
        method: 'POST',
        body: form
    });

    const result = await response.json();
    console.log(result); // Debug log

    const userId = document.getElementById('admin-panel')?.dataset.userId;
console.log("Reloading profile for user ID:", userId);
if (userId) {
    window.parent.location.href = `profile.php?id=${userId}`;
} else {
    alert("User ID not found for profile reload.");
}


    if (result.success) {
        alert('Selected resets applied successfully!');
        if (typeof closePanel === 'function') closePanel();

        // Reload the profile page with correct user ID
        const userId = document.getElementById('admin-panel').dataset.userId;
        window.parent.location.href = `profile.php?id=${userId}`;
    } else {
        alert(result.message || 'Failed to reset user fields.');
    }
});

// Edit note functionality
document.querySelectorAll('.edit-note-form').forEach(form => {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const noteId = this.dataset.noteId;
        const content = this.querySelector('textarea').value;

        const formData = new FormData();
        formData.append('action', 'edit_note');
        formData.append('note_id', noteId);
        formData.append('content', content);
        formData.append('user_id', <?= $user['id'] ?>);

        const response = await fetch('scripts/admin-actions.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert('Note updated.');
            window.location.reload();
        } else {
            alert(result.message || 'Error updating note.');
        }
    });
});

//delete note and reload the page
document.querySelectorAll('.delete-note-form').forEach(form => {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!confirm('Delete this note?')) return;

        const formData = new FormData(this);
        const response = await fetch('scripts/admin-actions.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert('Note deleted!');
            window.location.reload(); // ✅ Reload panel
        } else {
            alert(result.message || 'Failed to delete note.');
        }
    });
});


// Optional manual field reset function (if you're still using it)
function resetField(field) {
    if (!confirm(`Are you sure you want to reset ${field}?`)) return;

    const formData = new FormData();
    formData.append('user_id', <?= $userId ?>);
    formData.append('action', 'reset_field');
    formData.append('field', field);

    fetch('scripts/admin-actions.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || (data.success ? "Field reset." : "Failed."));
        if (data.success) location.reload();
    });
}

document.getElementById('admin-role-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = new FormData(this);
    const response = await fetch('scripts/admin-actions.php', {
        method: 'POST',
        body: form
    });

    const result = await response.json();
    alert(result.message || (result.success ? 'Role updated!' : 'Failed to update role.'));
    if (result.success) {
        if (typeof closePanel === 'function') closePanel();

        // Reload the profile page with correct user ID
        const userId = document.getElementById('admin-panel').dataset.userId;
        window.parent.location.href = `profile.php?id=${userId}`;
    }
});

document.getElementById('admin-delete-action').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = new FormData(this);
    const response = await fetch('scripts/delete-mod-action.php', {
        method: 'POST',
        body: form
    });

    const result = await response.json();
    alert(result.message || (result.success ? 'Action deleted!' : 'Failed to delete action.'));
    if (result.success) {
        if (typeof closePanel === 'function') closePanel();

        // Reload the profile page with correct user ID
        const userId = document.getElementById('admin-panel').dataset.userId;
        window.parent.location.href = `profile.php?id=${userId}`;
    }
});



//refined add-note
document.getElementById('note-form').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = new FormData(this);
    const response = await fetch('scripts/admin-actions.php', {
        method: 'POST',
        body: form
    });

    const result = await response.json();
    if (result.success) {
        alert('Note added!');
        window.location.reload();
    } else {
        alert(result.message || 'Failed to add note.');
    }
});




/*$.post("scripts/admin-actions.php", {
    action: "reset_options",
    user_id: targetUserId, // must be defined
    reset_displayName: $("#resetDisplayName").is(":checked") ? 1 : 0,
    reset_profileImage: $("#resetProfileImage").is(":checked") ? 1 : 0,
    reset_securityQuestion: $("#resetSecurityQuestion").is(":checked") ? 1 : 0
}, function(response) {
    // Handle response
});*/
</script>
<!-- Required for accordions to work -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>
