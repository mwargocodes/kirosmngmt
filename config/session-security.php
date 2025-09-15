<?php
// Secure session start with strict cookie settings
session_set_cookie_params([
    'lifetime' => 0,         // Session expires when browser closes
    'path' => '/',           // Accessible across the entire site
    'domain' => '',          // Default: current domain
    'secure' => true,        // Only allow HTTPS (set to true in production)
    'httponly' => true,      // Prevent JavaScript access
    'samesite' => 'Strict'   // Mitigate CSRF attacks
]);
session_start();

//Regenerate session ID to prevent fixation attacks
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

//Prevent session hijacking by checking IP & User-Agent
if (!isset($_SESSION['ip']) || !isset($_SESSION['user_agent'])) {
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} else {
    if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        die("Session hijacking detected! Please <a href='login.php'>login</a> again.");
    }
}

//Implement session timeout (30 minutes)
$timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    die("Session expired! Please <a href='login.php'>login</a> again.");
}
$_SESSION['last_activity'] = time(); // Update activity timestamp

?>