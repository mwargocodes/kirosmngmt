<?php
session_start();
require_once 'database-conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    function cleanInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    $username = cleanInput($_POST['username'] ?? '');
    $email = filter_var(cleanInput($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['passwordInput'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $securityQuestion = cleanInput($_POST['security_question'] ?? '');
    $securityAnswer = trim($_POST['security_answer'] ?? '');

    $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
    $today = new DateTime();
    $age = $birthDateObj ? $birthDateObj->diff($today)->y : 0;

    if (!$birthDateObj || $age < 16) {
        $_SESSION['register_error'] = "You must be at least 16 years old to register.";
        header("Location: ../register.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM accounts WHERE username = :username OR email = :email");
    $stmt->execute([':username' => $username, ':email' => $email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = "That username or email is already taken.";
        header("Location: ../register.php");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $hashedAnswer = password_hash(strtolower($securityAnswer), PASSWORD_BCRYPT);
    $activationCode = bin2hex(random_bytes(32)); //generate new activation key

    $stmt = $pdo->prepare("
        INSERT INTO accounts (username, email, password, birthdate, displayName, accountRole, securityQuestion, securityAnswer, registrationDate, activation_code)
        VALUES (:username, :email, :password, :birthdate, :displayName, 'user', :security_question, :security_answer_hash, NOW(), :activation_code)
    ");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':birthdate' => $birthDateObj->format('Y-m-d'),
        ':displayName' => $username,
        ':security_question' => $securityQuestion,
        ':security_answer_hash' => $hashedAnswer,
        ':activation_code' => $activationCode
    ]);

    $activationLink = "localhost/Kiros%20Management/pages/activate.php?code=" . urlencode($activationCode);
    $to = $email;
    $subject = "Activate Your Kiros Account";
    $message = "Hello $username,

    Thank you for registering!

    To activate your account, please click the link below:
    $activationLink

    If this link does not work, you can manually enter the following activation code at https://kiros-mmorpg.com/activate.php:
    $activationCode

    If you didn't register, you can safely ignore this email.

    – Kiros Team";

    @mail($to, $subject, $message, "From: no-reply@kiros-mmorpg.com");

    $_SESSION['register_success'] = "Registration successful! You may now <a href='login.php'><strong>log in</strong></a>.";
    header("Location: ../register.php");
    exit;
} else {
    $_SESSION['register_error'] = "Invalid request.";
    header("Location: ../register.php");
    exit;
}