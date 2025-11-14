<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $parent = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        if ($name) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, parent_id) VALUES (:n, :p)');
            $stmt->execute([':n'=>$name, ':p'=>$parent]);
        }
    }
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $parent = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        if ($id && $name) {
            $stmt = $pdo->prepare('UPDATE categories SET name = :n, parent_id = :p WHERE id = :id');
            $stmt->execute([':n'=>$name, ':p'=>$parent, ':id'=>$id]);
        }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute([':id'=>$id]);
        }
    }
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query('SELECT id, name, parent_id FROM categories ORDER BY parent_id, name')->fetchAll(PDO::FETCH_ASSOC);

function render_tree($cats, $parent = null, $prefix = '') {
    $out = '';
    foreach ($cats as $c) {
        if ($c['parent_id'] == $parent) {
            $out .= '<tr class="border-b border-slate-800 hover:bg-slate-800/50 transition"><td class="py-3 px-4 text-slate-300">' . htmlspecialchars($prefix . $c['name']) . '</td>';
            $out .= '<td class="py-3 px-4 text-right space-x-2"><button type="button" class="text-yellow-400 hover:text-yellow-300 text-sm" onclick="fillEdit(' . $c['id'] . ',\'' . addslashes($c['name']) . '\',' . (int)$c['parent_id'] . ')">Edit</button>';
            $out .= '<form method="post" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $c['id'] . '"><button class="text-red-400 hover:text-red-300 text-sm" onclick="return confirm(\'Hapus kategori?\')">Hapus</button></form></td></tr>';
            $out .= render_tree($cats, $c['id'], $prefix . '→ ');
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
    <title>Kategori</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h2 class="text-3xl font-bold text-white mb-8">Manajemen Kategori</h2>

    <div class="grid md:grid-cols-3 gap-8">
        <div class="glass p-6 rounded-2xl border border-slate-700">
            <h3 class="text-white font-semibold mb-4">Tambah / Edit</h3>
            <form method="post" id="catForm" class="space-y-4">
                <input type="hidden" name="action" value="create" id="actionField">
                <input type="hidden" name="id" id="catId">
                
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Nama Kategori</label>
                    <input name="name" id="catName" class="w-full px-4 py-2 bg-white/10 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-blue-500" placeholder="Masukkan nama" required>
                </div>
                
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Parent (opsional)</label>
                    <select name="parent_id" id="catParent" class="w-full px-4 py-2 bg-white/10 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <option value="" class="bg-slate-900">-- Kategori Utama --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" class="bg-slate-900"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition font-medium text-sm">Simpan</button>
                    <button type="button" onclick="resetForm()" class="w-full px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition font-medium text-sm">Batal</button>
                </div>
            </form>
        </div>

        <div class="md:col-span-2 glass p-6 rounded-2xl border border-slate-700">
            <h3 class="text-white font-semibold mb-4">Daftar Kategori</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        <?php echo render_tree($categories); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function fillEdit(id, name, parent) {
    document.getElementById('actionField').value = 'edit';
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catParent').value = parent || '';
}
function resetForm() {
    document.getElementById('actionField').value = 'create';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catParent').value = '';
}
</script>
<script src="assets/responsive.js"></script>
</body>
</html>
