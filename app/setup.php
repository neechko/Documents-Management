<?php
// Run this script once to create a default admin user.
// Usage (CLI or browser): php app/setup.php

require_once __DIR__ . '/db.php';

function create_admin($username = 'admin', $password = 'admin123')
{
    $pdo = getPDO();
    // check exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) {
        echo "Admin user already exists: $username\n";
        return;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (username, password, created_at) VALUES (:u, :p, NOW())');
    $ins->execute([':u' => $username, ':p' => $hash]);
    echo "Admin user created: $username with password $password\n";
}

// Run
create_admin();

?>
