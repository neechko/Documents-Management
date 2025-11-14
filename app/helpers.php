<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function sanitize_filename($name)
{
    $name = trim($name);
    if (function_exists('iconv')) {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    }
    // remove anything not alnum, space, underscore or dash
    $name = preg_replace('/[^A-Za-z0-9 _-]/', '', $name);
    // replace spaces with underscore
    $name = preg_replace('/[\s]+/', '_', $name);
    $name = trim($name, '_-');
    return $name;
}

function generate_stored_filename($original, $title = '')
{
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $date = date('Y-m-d_His');
    $base = $title ? sanitize_filename($title) : sanitize_filename(pathinfo($original, PATHINFO_FILENAME));
    $base = substr($base, 0, 120);
    return $base . '_' . $date . ($ext ? '.' . $ext : '');
}

function log_audit($user_id, $action, $details = '')
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details) VALUES (:u, :a, :d)');
        $stmt->execute([':u' => $user_id, ':a' => $action, ':d' => $details]);
    } catch (Exception $e) {
        // ignore logging errors
    }
}

function find_soffice_path()
{
    $candidates = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        '/usr/bin/soffice',
        '/usr/local/bin/soffice',
        'soffice'
    ];
    foreach ($candidates as $c) {
        if (is_executable($c) || @file_exists($c)) {
            return $c;
        }
    }
    return false;
}

function convert_docx_to_pdf($inputPath)
{
    // Returns path to generated PDF on success, or false on failure.
    $soffice = find_soffice_path();
    if (!$soffice) return false;

    $inputPath = realpath($inputPath);
    if (!$inputPath) return false;

    $tmpDir = STORAGE_PATH . '/.convert_tmp';
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

    $cmd = escapeshellarg($soffice) . ' --headless --convert-to pdf --outdir ' . escapeshellarg($tmpDir) . ' ' . escapeshellarg($inputPath);
    // exec and check
    exec($cmd . ' 2>&1', $out, $code);
    if ($code !== 0) {
        return false;
    }

    // expected output filename
    $base = pathinfo($inputPath, PATHINFO_FILENAME);
    $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . $base . '.pdf';
    if (!file_exists($pdfPath)) {
        // sometimes soffice outputs with different casing; try glob
        $found = glob($tmpDir . DIRECTORY_SEPARATOR . $base . '*.pdf');
        if ($found) $pdfPath = $found[0];
    }
    if (!file_exists($pdfPath)) return false;

    // move into storage with unique name
    $outName = sanitize_filename($base) . '_converted_' . date('Ymd_His') . '.pdf';
    $dest = STORAGE_PATH . '/' . $outName;
    if (!@rename($pdfPath, $dest)) {
        // try copy
        if (!@copy($pdfPath, $dest)) return false;
        @unlink($pdfPath);
    }

    return $dest;
}

?>
