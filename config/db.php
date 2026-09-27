<?php
declare(strict_types=1);

$server   = "localhost\\SQLEXPRESS";
$database = "LearningSupport";
$username = "php_user";
$password = "StrongPassword_123!";

$dsn = "sqlsrv:Server=$server;Database=$database;Encrypt=true;TrustServerCertificate=true";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// ADMIN CREDENTIALS (ONE ADMIN ONLY)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '123456'); 
