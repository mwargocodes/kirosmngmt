<?php

//default connection values
$type = 'mysql'; //database type/software
$server = 'localhost'; //server IP/host
$db = 'kwebportal'; //name of the database being used
$port = '3306'; //3306 is XAMPP
$charset = 'utf8mb4'; //UTF-8 encoding

//authorize
$username = 'root@localhost';
$password = '';

//PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

//create DSN
$dsn = "$type:host=$server;dbname=$db;port=$port;charset=$charset";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), $e->getCode());
}