<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function allowed_extensions()
{
    return ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx','txt'];
}

function upload_document()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'error' => 'Invalid method'];
    }

    if (!is_logged_in()) {
        return ['success' => false, 'error' => 'Unauthorized'];
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }

    $file = $_FILES['file'];
    $original = $file['name'];
    $size = $file['size'];
    $mime = mime_content_type($file['tmp_name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, allowed_extensions())) {
        return ['success' => false, 'error' => 'Extension tidak diijinkan'];
    }

    // limit 50MB
    if ($size > 50 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File terlalu besar (max 50MB)'];
    }

    $title = trim($_POST['title'] ?? pathinfo($original, PATHINFO_FILENAME));
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $existing_doc = !empty($_POST['document_id']) ? (int)$_POST['document_id'] : null;

    // sanitized stored filename
    $stored = generate_stored_filename($original, $title);
    $target = STORAGE_PATH . '/' . $stored;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke storage.'];
    }

    $pdo = getPDO();
    try {
        $pdo->beginTransaction();

        if ($existing_doc) {
            // add as new version to existing document
            $document_id = $existing_doc;
            $stmt = $pdo->prepare('SELECT MAX(version_number) AS mv FROM document_versions WHERE document_id = :doc');
            $stmt->execute([':doc' => $document_id]);
            $mv = (int)$stmt->fetchColumn();
            $version = $mv + 1;
        } else {
            // create document record
            $ins = $pdo->prepare('INSERT INTO documents (title, description, category_id, created_by) VALUES (:t, :d, :c, :u)');
            $ins->execute([':t' => $title, ':d' => $description, ':c' => $category_id, ':u' => $_SESSION['user']['id']]);
            $document_id = $pdo->lastInsertId();
            $version = 1;
        }

        $insv = $pdo->prepare('INSERT INTO document_versions (document_id, stored_filename, original_filename, mime, extension, size, version_number, uploaded_by) VALUES (:doc, :s, :o, :m, :e, :sz, :v, :u)');
        $insv->execute([
            ':doc' => $document_id,
            ':s' => $stored,
            ':o' => $original,
            ':m' => $mime,
            ':e' => $ext,
            ':sz' => $size,
            ':v' => $version,
            ':u' => $_SESSION['user']['id']
        ]);

        log_audit($_SESSION['user']['id'], 'upload', ($existing_doc ? 'New version for doc ' . $document_id : 'Uploaded document ' . $title) . " ({$original})");

        $pdo->commit();
        return ['success' => true, 'document_id' => $document_id];
    } catch (Exception $e) {
        $pdo->rollBack();
        @unlink($target);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

?>
