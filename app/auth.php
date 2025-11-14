<?php
require_once __DIR__ . '/db.php';

function login_user($username, $password)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        // set minimal session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username']
        ];
        return true;
    }
    return false;
}

function logout_user()
{
    unset($_SESSION['user']);
    session_destroy();
}

function is_logged_in()
{
    return !empty($_SESSION['user']);
}

function require_login()
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

?>
