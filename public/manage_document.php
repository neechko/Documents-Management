<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';

require_login();
$pdo = getPDO();

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('Invalid id');

if ($action === 'delete') {
    $stmt = $pdo->prepare('UPDATE documents SET is_deleted = 1 WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    log_audit($_SESSION['user']['id'], 'delete_document', "Soft-deleted document {$id}");
}
if ($action === 'restore') {
    $stmt = $pdo->prepare('UPDATE documents SET is_deleted = 0 WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    log_audit($_SESSION['user']['id'], 'restore_document', "Restored document {$id}");
}

header('Location: documents.php');
exit;
