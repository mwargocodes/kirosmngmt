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
   <div class="container my-5" style="max-width: 700px;">
  <form action="scripts/registration-handler.php" method="POST" class="bg-dark text-light p-4 rounded shadow">

    <h2 class="text-center mb-4">Register for Kiros</h2>

    <?php session_start(); ?>
    <!----error handling --->
    <?php if (isset($_SESSION['register_error'])): ?>
  <div class="alert alert-danger">
    <?= $_SESSION['register_error'] ?>
  </div>
  <?php unset($_SESSION['register_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['register_success'])): ?>
  <div class="alert alert-success">
    <?= $_SESSION['register_success'] ?>
  </div>
  <?php unset($_SESSION['register_success']); ?>
<?php endif; ?>


    <!-- Email -->
    <div class="mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      <small class="form-text text-muted">example@example.com</small>
    </div>

    <!-- Username -->
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" id="username" name="username" class="form-control" required maxlength="16" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      <small class="form-text text-muted">Must be 16 characters or fewer.</small>
    </div>

    <!-- Password -->
    <div class="mb-3">
      <label for="passwordInput" class="form-label">Password</label>
      <input type="password" id="passwordInput" name="passwordInput" class="form-control" required>
    </div>

    <!-- Birthdate -->
    <div class="mb-3">
      <label for="birthdate" class="form-label">Birthdate</label>
      <input type="date" id="birthdate" name="birthdate" class="form-control" required value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">
      <small class="form-text text-muted">Must be entered like YYYY-MM-DD. You must be at least 16 years old to register.</small>
    </div>

    <!-- Security Question -->
    <div class="mb-3">
      <label for="security_question" class="form-label">Security Question</label>
      <select name="security_question" id="security_question" class="form-select" required>
        <option value="">-- Choose a security question --</option>
        <option value="first_pet" <?= ($_POST['security_question'] ?? '') === 'first_pet' ? 'selected' : '' ?>>What was the name of your first pet?</option>
        <option value="mother_maiden" <?= ($_POST['security_question'] ?? '') === 'mother_maiden' ? 'selected' : '' ?>>What is your mother's maiden name?</option>
        <option value="birth_city" <?= ($_POST['security_question'] ?? '') === 'birth_city' ? 'selected' : '' ?>>In what city were you born?</option>
      </select>
    </div>

    <!-- Security Answer -->
    <div class="mb-3">
      <label for="security_answer" class="form-label">Security Answer</label>
      <input type="text" name="security_answer" id="security_answer" class="form-control" required value="<?= htmlspecialchars($_POST['security_answer'] ?? '') ?>">
    </div>

    <!-- Age Confirmation -->
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="ageAck" required>
      <label class="form-check-label" for="ageAck">
        I declare that I am 16 years or older.
      </label>
    </div>

    <!-- Buttons -->
    <div class="d-flex justify-content-between align-items-center">
      <a href="login.php" class="text-info">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-left" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
          <path fill-rule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
        </svg> Return to login page?
      </a>
      <button class="btn btn-primary" type="submit" name="register">
        Register
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-double-right" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708"/>
          <path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708"/>
        </svg>
      </button>
    </div>
  </form>
</div>
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