<?php
require_once __DIR__ . '/config.php';

function getPDO()
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        // In production, better to log this and show friendly message
        die('Database connection failed: ' . $e->getMessage());
    }

    return $pdo;
}

?>
