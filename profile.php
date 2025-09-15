<?php
require_once 'scripts/database-conn.php';
require 'scripts/session-security.php';
require 'scripts/functions.php';

//avoid hijacking, start session
if (session_status() === PHP_SESSION_NONE)
{
  session_start();
}

$username = $_SESSION['username'] ?? null;

if (!$username) {
  die ("No username found in this session.");
}

if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    die('Session hijack attempt detected.');
}

$timeout = 900; // 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    die("Session expired due to inactivity.");
}
$_SESSION['last_activity'] = time(); // Update on valid activity

$currentUserId = $_SESSION['id'];
$viewedId = $_GET['id'] ?? $currentUserId; // View own profile or specific user's
$isOwnProfile = $viewedId == $currentUserId;

$stmt = $pdo->prepare("SELECT id, username, displayName, email, accountRole, registrationDate, lastActive, profileImage, accountHealth FROM accounts WHERE id = ?");
$stmt->execute([$viewedId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "<div class='text-danger'>User not found.</div>";
    exit;
}

    //connection good?
    if (!$user) die ("No database access.");

    //assign values before calling them
    $userID = $_SESSION['id'];
    $userRole = $_SESSION['role'];


//you must be logged in to view this page
/*if (!isset($_SESSION['id'])) {
  die("Error: You must be logged in to view this page. Please <a href=http://localhost/Kiros%20Management/pages/login.php>login</a> to continue.");
}*/

?>
    <style>
    body {
      background-color: #121212;
      color: white;
    }
    #panelContainer {
      position: fixed;
      top: 0;
      left: -600px;
      width: 600px;
      max-width: 100%;
      height: 100%;
      background-color: #1c1c1c;
      overflow-y: auto;
      box-shadow: 2px 0 5px rgba(0,0,0,0.5);
      transition: left 0.3s ease;
      z-index: 9999;
    }
    #panelContainer.active {
      left: 0;
    }    
    @media (max-width: 768px) {
    #panelContainer {
    width: 100%;
    }
  }
    #panelClose {
      position: absolute;
      top: 10px;
      right: 15px;
      color: white;
      cursor: pointer;
    }
    iframe {
      width: 100%;
      height: 100vh;
      border: none;
    }
    .profile-image { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #444; }
  </style> 
         <?php include 'scripts/header.php'; ?>
<body class="bg-dark text-white">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <div class="container-fluid" style="margin-top: 5px; align-items:baseline;">
       <div class="container-md">
<div class="container py-5">
  <h2 class="mb-4"><?= $isOwnProfile ? 'Your' : htmlspecialchars($user['displayName'] . "'s") ?> Profile</h2>

  <div align="center">
    <img src="<?= htmlspecialchars($user['profileImage'] ?? 'https://via.placeholder.com/150') ?>"
         alt="Profile Image"
         class="profile-image mb-2"><br>
  </div>

<div class="table-responsive mb-4">
  <table class="table table-dark table-striped table-bordered rounded">
    <tbody>
      <tr>
        <td colspan="2" class="text-center">
          <div class="d-flex justify-content-center gap-2">
            <?php if ($_SESSION['role'] === 'ok'): ?>
                  <?php $adminPanelUrl = 'admin-panel.php?user_id=' . urlencode($user['id']); ?>
    <button class="btn btn-sm btn-danger" onclick="openPanel('<?php echo $adminPanelUrl; ?>')">Open Admin Panel</button>
            <?php endif; ?>

            <?php if (in_array($_SESSION['role'], ['ok','mod'])): ?>
              <?php $modPanelUrl = 'mod-panel.php?user_id=' . urlencode($user['id']); ?>
    <button class="btn btn-sm btn-primary" onclick="openPanel('<?php echo $modPanelUrl; ?>')">Open Mod Panel</button>
            <?php endif; ?>

            <a href="report.php?type=profile&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Report</a>
          </div>
        </td>
      </tr>
      <tr>
        <th scope="row">Display Name</th>
        <td><?= htmlspecialchars($user['displayName']) ?> (#<?= $user['id'] ?>)<?php
          $badge = match ($user['accountRole']) {
              'ok' => 'danger',
              'mod' => 'primary',
              default => 'secondary'
          };
          ?>
          <span class="badge bg-<?= $badge ?> ms-2"> <?php if ($user['accountRole'] === 'ok' || $user['accountRole'] === 'mod') { if ($user['accountRole'] === 'ok') { 
            echo 'Admin';
          } 
          elseif ($user['accountRole'] === 'mod') {
            echo 'Moderator';
          } }?></span></td>
      </tr>
      <!----\a\t outputs the word 'at'--->
      <tr>
  <th scope="row">Joined</th>
  <td>
    <?= date('Y-m-d \a\t H:i A', strtotime($user['registrationDate'] ?? '')) ?><br>
  </td>
</tr>
<tr>
  <th scope="row">Last Active</th>
  <td>
    <?= date('Y-m-d \a\t H:i A', strtotime($user['lastActive'] ?? '')) ?><br>
    <small class="text-muted">(<?= timeAgo($user['lastActive'] ?? '') ?>)</small>
  </td>
</tr>
    </tbody>
  </table>
</div>

<?php if ($isOwnProfile): ?>
  <hr>
  <h4>Update Account Info</h4>
  <form action="update-profile.php" method="POST" class="text-white">
    <div class="mb-3">
      <label>New Display Name</label>
      <input type="text" name="displayName" class="form-control" value="<?= htmlspecialchars($user['displayName']) ?>">
    </div>
    <div class="mb-3">
      <label>New Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
    </div>
    <div class="mb-3">
      <label>New Password</label>
      <input type="password" name="password" class="form-control">
    </div>
    <div class="mb-3">
      <label>Profile Image URL</label>
      <input type="url" name="profileImage" class="form-control" value="<?= htmlspecialchars($user['profileImage']) ?>">
      <small class="text-muted">Paste a direct image link including the file extension (e.g. <a href=https://imgur.com>Imgur</a>, .png).</small>
    </div>
    <button class="btn btn-success" type="submit">Update</button>
  </form>
  <hr />
              <?php
                $health = (int)$user['accountHealth'] ?? 0;
                $standingText = '';

                // Choose a label color based on health
                if ($health >= 80) {
                    $healthClass = 'text-success'; // green
                    $standingText = 'Your standing is <b>great!</b> This means you have avoided rule breaks and kept everything in good shape!';
                } elseif ($health >= 50) {
                    $healthClass = 'text-warning'; // yellow
                    $standingText = 'Your standing is <b>moderate</b>. Your standing has decreased due to rule violations that have resulted in a loss of standing. It is advised to avoid further violations, otherwise your account may face suspensions from the game and/or site.';
                } else {
                    $healthClass = 'text-danger';  // red
                    $standingText = 'Your standing is <b>poor</b>. Your account standing has decreased due to repeated rule violations, and is at risk of <b>permanent account suspension</b>. It is advised to avoid further violations, otherwise your account will be permanently suspended if your standing drops to <b>0%</b>.';
                }
            ?>
  <label for="account-standing">Account Standing:</label>
  <span id="account-standing" class="<?= $healthClass ?> fw-bold ms-2"><?= $health ?>%</span>
  <div id="standing-explain"><?= $standingText ?></div>
    <?php endif; ?>

  <!-- Slide-in panel -->
<div id="panelContainer">
  <span id="panelClose" onclick="closePanel()">✖</span>
  <iframe id="panelFrame" src=""></iframe>
</div>

<script>
function openPanel(url) {
  document.getElementById('panelFrame').src = url;
  document.getElementById('panelContainer').classList.add('active');
}
function closePanel() {
  document.getElementById('panelContainer').classList.remove('active');
  document.getElementById('panelFrame').src = '';
}
</script>

</div>
</body>
</html>
</div>
</div>
<?php include 'partials/footer.php'; ?>