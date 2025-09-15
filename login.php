<?php
session_start();
require_once 'scripts/database-conn.php';
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Accessing: " . $_SERVER['PHP_SELF'] . "\n", FILE_APPEND);


if (isset($_POST['login'])) {
  function cleanInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
  }

  // Avoid redirect loop: only redirect if NOT already on activate.php
$currentPage = basename($_SERVER['PHP_SELF']);
if (
    isset($_SESSION['id']) &&
    isset($_SESSION['is_active']) &&
    !$_SESSION['is_active'] &&
    $currentPage !== 'activate.php'
) {
    header("Location: activate.php");
    exit;
}


  $username = cleanInput($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!$username || !$password) {
    $_SESSION['login_error'] = "Please fill in both fields.";
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: login.php 6\n", FILE_APPEND);
    header("Location: login.php");
    exit;
  }

  $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = :username");
  $stmt->execute([':username' => $username]);

  if ($stmt->rowCount() === 1) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($password, $user['password'])) {
      session_regenerate_id(true);

      // Set session variables
      $_SESSION['id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['displayName'] = $user['displayName'];
      $_SESSION['role'] = $user['accountRole'];
      $_SESSION['last_activity'] = time();
      $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
      $_SESSION['is_active'] = (int)$user['is_active']; // ← Forces a true/false integer


      // Update last active timestamp
      $updateStmt = $pdo->prepare("UPDATE accounts SET lastActive = NOW(), ip_address = :ip WHERE id = :id");
      $updateStmt->execute([':id' => $user['id'], ':ip' => $_SESSION['ip']]);

      if (!$user['is_active']) {
        $_SESSION['login_notice'] = "Your account is not activated. Please activate it before proceeding.";
         error_log("Account not active, redirecting to activate.php", 3, __DIR__ . '/debug_log.txt');
        file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: activate.php 7\n", FILE_APPEND);
        header("Location: activate.php");
        exit;
      }

      $_SESSION['login_success'] = "Welcome back, {$user['username']}!";
      file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: index.php 8\n", FILE_APPEND);
      error_log("Redirecting to index.php", 3, __DIR__ . '/debug_log.txt');
      header("Location: index.php");
      exit;

    } else {
      $_SESSION['login_error'] = "Invalid username or password.";
    }
  } else {
    $_SESSION['login_error'] = "Invalid username or password.";
  }
  file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Redirecting to: login.php 9\n", FILE_APPEND);
  header("Location: login.php");
  exit;
}
?>


<!DOCTYPE HTML>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiros MMORPG</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <div class="container-fluid">
        <div style="margin-top: 1%;">
        <img src=https://storage.proboards.com/7179504/images/cScCaZxl0UXjosgfuOWe.png style="margin: auto; display: block;">
</div>
        <div class="container-md" style="margin-top:50px;"><h2  align="center"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-check" viewBox="0 0 16 16">
  <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
  <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
</svg> Login</h2>
    <p align="center">You must login to proceed to the website.</p>
    <i><p style="font-size: 10pt;" align="center">Upon using our service you automatically agree to our Terms of Service and Code of Conduct.</p></i>
    <hr />
    <div style="width: 80%; padding-left: 480px;">
      <div class="row g-3">
        <?php
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty':
            echo "<div class='alert alert-warning'>Please fill in both fields.</div>";
            break;
        case 'invalid':
            echo "<div class='alert alert-danger'>Invalid credentials.</div>";
            break;
        case 'timeout':
            $minutes = htmlspecialchars($_GET['minutes'] ?? 5);
            echo "<div class='alert alert-danger'>Account locked. Try again in $minutes minute(s).</div>";
            break;
        case 'locked':
            echo "<div class='alert alert-danger'>This account is permanently locked. Contact support.</div>";
            break;
    }
}
?>
      <form action="login.php" method="POST">
        <div class="row g-3 align-items-center">
  <div class="col-auto">
    <label for="username" class="col-form-label">Username</label>
  </div>
  <div class="col-auto">
    <input type="text" id="username" name="username" class="form-control" aria-describedby="usernameHelp" required>
  </div>
</div>
<br />
    <div class="row g-3 align-items-center">
  <div class="col-auto">
    <label for="passwordInput" class="col-form-label">Password</label>
  </div>
  <div class="col-auto">
    <input type="password" id="passwordInput" name="password" class="form-control" aria-describedby="passwordHelpInline" required>
  </div>
</div>
<br />
</div>
<br />
<div class="col-12"> 
    <button class="btn btn-primary" type="submit" name="login" style="margin-left: 215px;">Login <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-right" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708"/>
  <path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708"/>
</svg></button>
    <br /><br/><i style="margin-right: 0px; color:blue;"><a href="register.php"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
  <path fill-rule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
</svg> New to Kiros? Register a new account.</a></i>
  </div>
</div></form>
<br />
<hr />
<p align=center>Need help? Contact support@kiros-mmorpg.com.</p>
    </div>
</body>
<footer>
    <div class="p-3 text-light-emphasis --bs-tertiary-color">
        <p align="center" style="font-size: 10pt;">Kiros is the property of Kiros MMORPG Ltd (c) 2015-2025. All rights reserved.</p>
        <nav class="navbar navbar-expand-lg bg-body-tertiary" align="center">
  <div class="container-md" align="center">
    <a class="navbar-brand" href="#">Terms of Service</a>
    <a class="navbar-brand" href="#">Code of Conduct</a>
    <a class="navbar-brand" href="#">F.A.Q.</a>
    <a class="navbar-brand" href="#">Support</a>
    <a class="navbar-brand" href="#">Contact Us</a>
  </div>
</nav>
</div>
</footer>
</html>
</DOCTYPE>