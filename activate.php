<?php
session_start();
require_once 'scripts/database-conn.php';
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Accessing: " . $_SERVER['PHP_SELF'] . "\n", FILE_APPEND);


// Redirect if logged in but not active and trying to access protected pages
if (isset($_SESSION['id']) && isset($_SESSION['username']) && !$_SESSION['is_active']) {
  if (!str_contains($_SERVER['PHP_SELF'], 'activate.php')) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: activate.php 5\n", FILE_APPEND);
    header("Location: activate.php");
    exit;
  }
}

// Handle email update and resend activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_activation'])) {
  $newEmail = filter_var(trim($_POST['new_email'] ?? ''), FILTER_SANITIZE_EMAIL);

  if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    $activationCode = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE accounts SET email = :email, activation_code = :code WHERE id = :id");
    $stmt->execute([
      ':email' => $newEmail,
      ':code' => $activationCode,
      ':id' => $_SESSION['id']
    ]);

    // Send updated activation email
    $to = $newEmail;
    $subject = "Activate Your Account";
    $message = "Click the link to activate your account: http://localhost/Kiros%20Management/pages/activate.php?code=$activationCode";
    mail($to, $subject, $message);

    $_SESSION['activation_success'] = "A new activation email has been sent to your updated email address.";
  } else {
    $_SESSION['activation_error'] = "Please enter a valid email address.";
  }
  file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: activate.php 4\n", FILE_APPEND);
  header("Location: activate.php");
  exit;
}

// Handle manual activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_activate'])) {
  $username = trim($_POST['username'] ?? '');
  $code = trim($_POST['code'] ?? '');

  if (!$username || !$code) {
    $_SESSION['activation_error'] = "Both fields are required.";
  } else {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE (username = :username OR email = :email) AND activation_code = :code AND is_active = 0");
    $stmt->execute([
      ':username' => $username,
      ':email' => $username,
      ':code' => $code
    ]);

    if ($stmt->rowCount() === 1) {
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      $pdo->prepare("UPDATE accounts SET is_active = 1, activation_code = NULL WHERE id = :id")
          ->execute([':id' => $user['id']]);

      // Set session variables
      $_SESSION['id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['accountRole'];
      $_SESSION['last_activity'] = time();
      $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
      $_SESSION['is_active'] = (int)$user['is_active']; // ← Forces a true/false integer


      $_SESSION['activation_success'] = "Your account has been successfully activated! You may now <a href='index.php'>continue</a>.";
          file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: index.php 3\n", FILE_APPEND);
      header("Location: index.php");
      exit;
    } else {
      $_SESSION['activation_error'] = "Invalid activation code or account already active.";
    }
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: activate.php 2\n", FILE_APPEND);
    header("Location: activate.php");
    exit;
  }
}

//do the redirect only if the session was just set/updated:
if (isset($_SESSION['is_active']) && $_SESSION['is_active']) {
      file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: index.php 1\n", FILE_APPEND);
  header("Location: index.php");
  exit;
}
?>
