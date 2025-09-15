<?php
require_once 'scripts/database-conn.php';
session_start();

$id = $_SESSION['id'];
$displayName = $_POST['displayName'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$profileImage = $_POST['profileImage'] ?? null;


$params = [
    'displayName' => $displayName,
    'email' => $email,
    'profileImage' => $profileImage,
    'id' => $id
];

$sql = "UPDATE accounts SET displayName = :displayName, email = :email, profileImage = :profileImage";

// After updating displayName in DB:
$_SESSION['displayName'] = $displayName;

if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql .= ", password = :password";
    $params['password'] = $hashed;
}

$sql .= " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Update last active
        $pdo->prepare("UPDATE accounts SET lastActive = NOW() WHERE id = :id")
            ->execute([':id' => $_SESSION['id']]);

header("Location: profile.php");
?>
