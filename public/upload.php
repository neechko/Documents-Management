<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/upload.php';

require_login();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = upload_document();
    if ($res['success']) {
        header('Location: documents.php?msg=uploaded');
        exit;
    } else {
        $msg = $res['error'];
    }
}

$pdo = getPDO();
$cats = $pdo->query('SELECT id, name, parent_id FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$docs = $pdo->query('SELECT id, title FROM documents WHERE is_deleted = 0 ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);
$preAddTo = isset($_GET['add_to']) ? (int)$_GET['add_to'] : 0;

function renderCatOptions($cats, $parent = null, $prefix = '') {
    $out = '';
    foreach ($cats as $c) {
        if ($c['parent_id'] == $parent) {
            $out .= '<option value="' . $c['id'] . '" class="bg-slate-900">' . htmlspecialchars($prefix . $c['name']) . '</option>';
            $out .= renderCatOptions($cats, $c['id'], $prefix . '→ ');
        }
    }
    return $out;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Dokumen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .drag-over { background-color: rgba(59, 130, 246, 0.2) !important; border-color: rgb(59, 130, 246) !important; }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(to bottom right, #020617, #0f172a, #020617); background-attachment: fixed;">
<nav class="glass sticky top-0 z-50 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="index.php" class="flex items-center gap-3 flex-shrink-0">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v12a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h3a1 1 0 000-2h-2a2 2 0 00-2 2v12H4V5z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white">Pengurusan Dokumen</h1>
            </a>

            <div class="hidden md:flex items-center gap-6">
                <a href="documents.php" class="text-slate-300 hover:text-white text-sm transition">Dokumen</a>
                <a href="categories.php" class="text-slate-300 hover:text-white text-sm transition">Kategori</a>
                <a href="audit.php" class="text-slate-300 hover:text-white text-sm transition">Audit</a>
                <a href="logout.php" class="px-3 py-1.5 text-sm bg-red-500/20 text-red-300 hover:bg-red-500/30 rounded-lg transition">Logout</a>
            </div>

            <div class="md:hidden flex items-center">
                <button id="navToggle" aria-expanded="false" class="p-2 rounded-md text-slate-300 hover:text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobileMenu" class="md:hidden hidden px-4 pb-3">
        <a href="documents.php" class="block py-2 text-slate-300 hover:text-white">Dokumen</a>
        <a href="categories.php" class="block py-2 text-slate-300 hover:text-white">Kategori</a>
        <a href="audit.php" class="block py-2 text-slate-300 hover:text-white">Audit</a>
        <div class="border-t border-slate-700 mt-2 pt-2">
            <a href="logout.php" class="block py-2 text-red-300">Logout</a>
        </div>
    </div>
</nav>

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h2 class="text-3xl font-bold text-white mb-8">Upload Dokumen</h2>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-lg">
            <p class="text-red-300 text-sm"><?php echo htmlspecialchars($msg); ?></p>
        </div>
    <?php endif; ?>

    <div class="glass p-8 rounded-2xl border border-slate-700">
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-slate-300 font-medium text-sm mb-3">Dokumen Tujuan</label>
                <select name="document_id" class="w-full px-4 py-2.5 bg-white/10 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-blue-500" id="documentSelect">
                    <option value="" class="bg-slate-900">🆕 Buat Dokumen Baru</option>
                    <?php foreach ($docs as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php if($preAddTo && $preAddTo == $d['id']) echo 'selected'; ?> class="bg-slate-900"><?php echo htmlspecialchars($d['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-300 font-medium text-sm mb-3">Judul Dokumen</label>
                <input name="title" class="w-full px-4 py-2.5 bg-white/10 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-blue-500" placeholder="Masukkan judul dokumen" required>
            </div>

            <div>
                <label class="block text-slate-300 font-medium text-sm mb-3">Deskripsi</label>
                <textarea name="description" class="w-full px-4 py-2.5 bg-white/10 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-blue-500" rows="3" placeholder="Deskripsi singkat (opsional)"></textarea>
            </div>

            <div>
                <label class="block text-slate-300 font-medium text-sm mb-3">Kategori</label>
                <select name="category_id" class="w-full px-4 py-2.5 bg-white/10 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                    <option value="" class="bg-slate-900">-- Pilih Kategori --</option>
                    <?php echo renderCatOptions($cats); ?>
                </select>
            </div>

            <div>
                <label class="block text-slate-300 font-medium text-sm mb-3">File</label>
                <div class="border-2 border-dashed border-slate-600 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-500/5 transition" id="dropZone">
                    <svg class="w-12 h-12 text-slate-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"></path>
                    </svg>
                    <p class="text-slate-300 font-medium">Klik atau drag file ke sini</p>
                    <p class="text-slate-400 text-sm mt-1">PDF, Gambar, Office (max 50MB)</p>
                    <input type="file" name="file" class="hidden" id="fileInput" required>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg hover:from-blue-600 hover:to-cyan-600 transition font-medium">Upload</button>
                <a href="documents.php" class="flex-1 px-4 py-2.5 bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition font-medium text-center">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('documentSelect').addEventListener('change', function() {
    var title = document.querySelector('input[name="title"]');
    if (this.value) {
        title.removeAttribute('required');
        title.placeholder = 'Judul tidak diperlukan (akan menggunakan versi baru)';
    } else {
        title.setAttribute('required', 'required');
        title.placeholder = 'Masukkan judul dokumen';
    }
});

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('click', () => fileInput.click());

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
    dropZone.addEventListener(event, e => e.preventDefault());
});

dropZone.addEventListener('dragover', () => dropZone.classList.add('drag-over'));
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', (e) => {
    dropZone.classList.remove('drag-over');
    fileInput.files = e.dataTransfer.files;
});
</script>
<script src="assets/responsive.js"></script>
</body>
</html>
