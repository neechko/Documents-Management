<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();

$pdo = getPDO();

$q = trim($_GET['q'] ?? '');
$category = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$ext = trim($_GET['ext'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$show_deleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] === '1';

$conditions = [];
$params = [];
if ($q !== '') {
    $conditions[] = '(d.title LIKE :q OR d.description LIKE :q OR dv.original_filename LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($category) {
    $conditions[] = 'd.category_id = :cat';
    $params[':cat'] = $category;
}
if ($ext !== '') {
    $conditions[] = 'dv.extension = :ext';
    $params[':ext'] = strtolower($ext);
}

$conds = $conditions;
if (!$show_deleted) {
    $conds[] = 'd.is_deleted = 0';
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$countSql = "SELECT COUNT(DISTINCT d.id) FROM documents d JOIN document_versions dv ON dv.document_id = d.id $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $per_page;

$sql = "SELECT d.id as doc_id, d.title, d.description, d.category_id, d.is_deleted, dv.id as version_id, dv.stored_filename, dv.original_filename, dv.mime, dv.extension, dv.size, dv.version_number, dv.uploaded_at
FROM documents d
JOIN document_versions dv ON dv.document_id = d.id
JOIN (SELECT document_id, MAX(version_number) AS mv FROM document_versions GROUP BY document_id) mv ON mv.document_id = dv.document_id AND mv.mv = dv.version_number
$where
ORDER BY dv.uploaded_at DESC
LIMIT :lim OFFSET :off";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$total_pages = max(1, ceil($total / $per_page));

function findCatName($cats, $id) {
    foreach ($cats as $c) if ($c['id'] == $id) return $c['name'];
    return '';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Dokumen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .row-hover { transition: all 0.2s; }
        .row-hover:hover { background-color: rgba(255, 255, 255, 0.05); }
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
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-white">Daftar Dokumen</h2>
        <a href="upload.php" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-lg hover:from-blue-600 hover:to-cyan-600 transition font-medium text-sm">
            + Upload Dokumen
        </a>
    </div>

    <div class="glass p-6 rounded-2xl border border-slate-700 mb-6">
        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="🔍 Cari dokumen..." class="px-4 py-2 bg-white/10 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-blue-500">
            <select name="category" class="px-4 py-2 bg-white/10 border border-slate-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                <option value="" class="bg-slate-900">Semua Kategori</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if($category == $c['id']) echo 'selected'; ?> class="bg-slate-900"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input name="ext" value="<?php echo htmlspecialchars($ext); ?>" placeholder="Ekstensi (pdf,docx)" class="px-4 py-2 bg-white/10 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:border-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition font-medium">Filter</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-700">
                    <th class="text-left py-4 px-4 text-slate-300 font-semibold text-sm">Judul</th>
                    <th class="text-left py-4 px-4 text-slate-300 font-semibold text-sm">File</th>
                    <th class="text-left py-4 px-4 text-slate-300 font-semibold text-sm">Kategori</th>
                    <th class="text-left py-4 px-4 text-slate-300 font-semibold text-sm">Ukuran</th>
                    <th class="text-left py-4 px-4 text-slate-300 font-semibold text-sm">Tanggal</th>
                    <th class="text-right py-4 px-4 text-slate-300 font-semibold text-sm">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr class="row-hover border-b border-slate-800">
                        <td class="py-4 px-4 text-white font-medium"><?php echo htmlspecialchars($r['title']); ?></td>
                        <td class="py-4 px-4"><span class="inline-block px-2 py-1 bg-slate-700 text-slate-200 text-xs rounded font-mono"><?php echo strtoupper($r['extension']); ?></span> <span class="text-slate-300 text-sm"><?php echo htmlspecialchars($r['original_filename']); ?></span></td>
                        <td class="py-4 px-4 text-slate-300 text-sm"><?php echo htmlspecialchars(findCatName($cats, $r['category_id'])); ?></td>
                        <td class="py-4 px-4 text-slate-300 text-sm"><?php echo round($r['size'] / 1024, 1); ?> KB</td>
                        <td class="py-4 px-4 text-slate-300 text-sm"><?php echo date('d/m/Y', strtotime($r['uploaded_at'])); ?></td>
                        <td class="py-4 px-4 text-right space-x-2">
                            <a href="preview.php?vid=<?php echo $r['version_id']; ?>&from=docs" target="_blank" class="text-blue-400 hover:text-blue-300 text-sm">Preview</a>
                            <a href="../storage/files/<?php echo rawurlencode($r['stored_filename']); ?>" download class="text-cyan-400 hover:text-cyan-300 text-sm">Download</a>
                            <?php if (!empty($r['is_deleted'])): ?>
                                <a href="manage_document.php?action=restore&id=<?php echo $r['doc_id']; ?>" class="text-green-400 hover:text-green-300 text-sm">Restore</a>
                            <?php else: ?>
                                <a href="upload.php?add_to=<?php echo $r['doc_id']; ?>" class="text-purple-400 hover:text-purple-300 text-sm">Edit</a>
                                <button type="button" onclick="openDeleteModal(<?php echo $r['doc_id']; ?>, '<?php echo addslashes($r['title']); ?>')" class="text-red-400 hover:text-red-300 text-sm font-medium">Hapus</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center gap-2">
            <?php for ($p=1;$p<=$total_pages;$p++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page'=>$p])); ?>" class="px-3 py-2 <?php echo $p==$page ? 'bg-blue-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'; ?> rounded-lg text-sm transition"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Hapus Dokumen -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="glass p-6 rounded-2xl border border-slate-700 max-w-sm w-full">
        <h3 class="text-white font-semibold text-lg mb-4">Hapus Dokumen?</h3>
        <p class="text-slate-300 text-sm mb-2">Dokumen <strong id="docTitle"></strong> akan dihapus.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition">
                Batal
            </button>
            <a id="deleteLink" href="#" class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition text-center">
                Hapus
            </a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(docId, docTitle) {
    document.getElementById('docTitle').textContent = docTitle;
    document.getElementById('deleteLink').href = 'manage_document.php?action=delete&id=' + docId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>
<script src="assets/responsive.js"></script>
</body>
</html>
