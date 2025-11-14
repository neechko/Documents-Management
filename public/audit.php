<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM audit_logs WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
        header('Location: audit.php?deleted=1');
        exit;
    }
    if ($action === 'delete_all') {
        $pdo->exec('DELETE FROM audit_logs');
        header('Location: audit.php?deleted=all');
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$total = $pdo->query('SELECT COUNT(*) as cnt FROM audit_logs')->fetch(PDO::FETCH_ASSOC)['cnt'];
$logs = $pdo->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

$pages = ceil($total / $limit);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log</title>
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
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-white">Audit Log Sistem</h2>
        <div class="flex items-center gap-4">
            <div class="text-slate-400 text-sm">Total: <span class="font-semibold text-white"><?php echo $total; ?></span></div>
            <?php if ($total > 0): ?>
                <button onclick="openDeleteAllModal()" class="px-4 py-2 bg-red-500/20 text-red-300 hover:bg-red-500/30 rounded-lg text-sm font-medium transition">
                    Hapus Semua
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="glass p-4 rounded-lg border border-green-700/50 bg-green-500/10 text-green-300 text-sm mb-6">
            ✓ <?php echo $_GET['deleted'] === 'all' ? 'Semua audit log telah dihapus' : 'Audit log telah dihapus'; ?>
        </div>
    <?php endif; ?>

    <div class="glass p-6 rounded-2xl border border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-left py-3 px-4 text-slate-300 font-semibold">Waktu</th>
                        <th class="text-left py-3 px-4 text-slate-300 font-semibold">User</th>
                        <th class="text-left py-3 px-4 text-slate-300 font-semibold">Aksi</th>
                        <th class="text-left py-3 px-4 text-slate-300 font-semibold">Detail</th>
                        <th class="text-right py-3 px-4 text-slate-300 font-semibold">Opsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="border-b border-slate-800 hover:bg-slate-800/50 transition">
                            <td class="py-3 px-4 text-slate-300"><?php echo substr($log['created_at'], 5, 11); ?> <span class="text-slate-500"><?php echo substr($log['created_at'], 11, 5); ?></span></td>
                            <td class="py-3 px-4 text-slate-300"><?php echo htmlspecialchars($log['user_id']); ?></td>
                            <td class="py-3 px-4">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-300"><?php echo htmlspecialchars($log['action']); ?></span>
                            </td>
                            <td class="py-3 px-4 text-slate-400 text-xs"><?php echo htmlspecialchars(substr($log['details'], 0, 60)); ?>...</td>
                            <td class="py-3 px-4 text-right">
                                <button onclick="openDeleteModal(<?php echo $log['id']; ?>)" class="text-red-400 hover:text-red-300 text-xs font-medium">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 mt-6 pt-6 border-t border-slate-700">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-slate-800/50 text-slate-300 hover:bg-slate-700/50'; ?> px-3 py-2 rounded-lg text-sm transition">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Hapus Single -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="glass p-6 rounded-2xl border border-slate-700 max-w-sm w-full">
        <h3 class="text-white font-semibold text-lg mb-4">Hapus Audit Log?</h3>
        <p class="text-slate-300 text-sm mb-6">Apakah Anda yakin ingin menghapus audit log ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition">
                Batal
            </button>
            <form id="deleteForm" method="post" style="flex: 1;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition">
                    Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Semua -->
<div id="deleteAllModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="glass p-6 rounded-2xl border border-slate-700 max-w-sm w-full">
        <h3 class="text-white font-semibold text-lg mb-4">Hapus Semua Audit Log?</h3>
        <p class="text-slate-300 text-sm mb-2">Apakah Anda yakin ingin menghapus SEMUA audit log?</p>
        <p class="text-red-400 text-xs mb-6">⚠️ Tindakan ini tidak dapat dibatalkan dan akan menghapus seluruh riwayat sistem.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteAllModal()" class="flex-1 px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition">
                Batal
            </button>
            <form method="post" style="flex: 1;">
                <input type="hidden" name="action" value="delete_all">
                <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    Hapus Semua
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
function openDeleteAllModal() {
    document.getElementById('deleteAllModal').classList.remove('hidden');
}
function closeDeleteAllModal() {
    document.getElementById('deleteAllModal').classList.add('hidden');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeDeleteModal();
        closeDeleteAllModal();
    }
});
</script>
<script src="assets/responsive.js"></script>
</body>
</html>
