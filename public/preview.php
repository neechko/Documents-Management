<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

require_login();

$pdo = getPDO();
$vid = isset($_GET['vid']) ? (int)$_GET['vid'] : 0;
if (!$vid) {
    die('Invalid preview id');
}

$stmt = $pdo->prepare('SELECT dv.*, d.title FROM document_versions dv JOIN documents d ON d.id = dv.document_id WHERE dv.id = :id LIMIT 1');
$stmt->execute([':id' => $vid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die('File not found');

$path = realpath(__DIR__ . '/../storage/files/' . $row['stored_filename']);
if (!$path || !file_exists($path)) die('File not found on disk');

$mime = $row['mime'] ?: mime_content_type($path);
$ext = strtolower($row['extension'] ?: pathinfo($path, PATHINFO_EXTENSION));
$filesize = filesize($path);
$humansize = $filesize > 1024*1024 ? round($filesize/(1024*1024), 2).'MB' : round($filesize/1024, 2).'KB';
$previewUrl = '../storage/files/' . rawurlencode($row['stored_filename']);
$downloadUrl = $previewUrl . '?download=1';
$backUrl = isset($_GET['from']) && $_GET['from'] === 'docs' ? 'documents.php' : 'documents.php';
$isPreviewable = false;
$previewType = 'none';

// Determine preview type
if (in_array($ext, ['pdf'])) {
    $isPreviewable = true;
    $previewType = 'pdf';
}
elseif (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
    $isPreviewable = true;
    $previewType = 'image';
}
elseif (in_array($ext, ['doc','docx','odt','xls','xlsx','ppt','pptx'])) {
    require_once __DIR__ . '/../app/helpers.php';
    $converted = convert_docx_to_pdf($path);
    if ($converted && file_exists($converted)) {
        $previewUrl = '../storage/files/' . rawurlencode(basename($converted));
        $isPreviewable = true;
        $previewType = 'pdf';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview: <?php echo htmlspecialchars($row['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(to bottom right, #020617, #0f172a, #020617);
            background-attachment: fixed;
            min-height: 100vh;
        }
        .glass { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .preview-container { height: calc(100vh - 80px); }
        .preview-iframe { border: none; width: 100%; height: 100%; }
        .image-viewer { display: flex; align-items: center; justify-content: center; overflow: auto; user-select: none; }
        .preview-image { cursor: grab; transition: transform 0.2s ease-out; }
        .preview-image:active { cursor: grabbing; }
        .zoom-controls { position: fixed; bottom-6 right-6; z-index: 40; display: flex; flex-direction: column; gap: 2px; }
        .zoom-btn { padding: 8px 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .zoom-btn:hover { background: rgba(255, 255, 255, 0.2); }
        .zoom-info { position: fixed; bottom-6 left-6; z-index: 40; text-slate-300 text-xs font-mono bg-slate-900/80 px-3 py-2 rounded-lg border border-slate-700; }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(to bottom right, #020617, #0f172a, #020617); background-attachment: fixed;">
<nav class="glass sticky top-0 z-50 border-b border-slate-800">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-4 flex-1">
                <a href="<?php echo $backUrl; ?>" class="text-slate-300 hover:text-white text-sm transition font-medium">← Kembali</a>
                <div class="h-6 w-px bg-slate-700"></div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-white font-semibold truncate"><?php echo htmlspecialchars($row['title']); ?></h1>
                    <p class="text-slate-400 text-xs"><?php echo htmlspecialchars($row['original_filename']); ?> • <?php echo $humansize; ?> • <?php echo date('d M Y H:i', strtotime($row['uploaded_at'])); ?></p>
                </div>
            </div>
            <a href="<?php echo $previewUrl; ?>" download class="px-3 py-1.5 text-sm bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 rounded-lg transition">
                ⬇ Download
            </a>
        </div>
    </div>
</nav>
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

<div class="preview-container">
    <?php if ($isPreviewable): ?>
        <?php if ($previewType === 'pdf'): ?>
            <iframe src="<?php echo $previewUrl; ?>" class="preview-iframe"></iframe>
        <?php elseif ($previewType === 'image'): ?>
            <div id="imageViewer" class="image-viewer">
                <img id="previewImg" src="<?php echo $previewUrl; ?>" class="preview-image" alt="<?php echo htmlspecialchars($row['title']); ?>">
            </div>
            <div class="zoom-controls">
                <button class="zoom-btn" onclick="zoomIn()">🔍+</button>
                <button class="zoom-btn" onclick="resetZoom()">Reset</button>
                <button class="zoom-btn" onclick="zoomOut()">🔍-</button>
            </div>
            <div class="zoom-info">
                <span id="zoomLevel">100%</span> | Scroll untuk zoom | Drag untuk pan
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="w-full h-full flex items-center justify-center p-4">
            <div class="glass p-8 rounded-2xl border border-slate-700 max-w-md text-center">
                <div class="mb-4 text-4xl">📄</div>
                <h2 class="text-white font-semibold mb-2">Preview Tidak Tersedia</h2>
                <p class="text-slate-300 text-sm mb-4">
                    Tipe file <strong><?php echo htmlspecialchars(strtoupper($ext)); ?></strong> tidak dapat di-preview di browser.
                </p>
                <p class="text-slate-400 text-xs mb-6">
                    <?php if (in_array($ext, ['doc','docx','odt','xls','xlsx','ppt','pptx'])): ?>
                        💡 Untuk preview DOCX/XLSX/PPT, pastikan LibreOffice terinstall di server untuk konversi otomatis.
                    <?php else: ?>
                        Silakan download file untuk membukanya dengan aplikasi terkait.
                    <?php endif; ?>
                </p>
                <a href="<?php echo $previewUrl; ?>" download class="inline-block px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition">
                    📥 Download File
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let zoomLevel = 100;
const minZoom = 50;
const maxZoom = 300;
const zoomStep = 20;
let isDragging = false;
let startX, startY, scrollLeft, scrollTop;

const img = document.getElementById('previewImg');
const viewer = document.getElementById('imageViewer');
const zoomDisplay = document.getElementById('zoomLevel');

if (img) {
    // Zoom dengan scroll
    viewer.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -zoomStep : zoomStep;
        zoomLevel = Math.max(minZoom, Math.min(maxZoom, zoomLevel + delta));
        updateZoom();
    });

    // Drag untuk pan
    img.addEventListener('mousedown', (e) => {
        isDragging = true;
        startX = e.pageX - viewer.offsetLeft;
        startY = e.pageY - viewer.offsetTop;
        scrollLeft = viewer.scrollLeft;
        scrollTop = viewer.scrollTop;
        img.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const x = e.pageX - viewer.offsetLeft;
        const y = e.pageY - viewer.offsetTop;
        const walkX = (x - startX);
        const walkY = (y - startY);
        viewer.scrollLeft = scrollLeft - walkX;
        viewer.scrollTop = scrollTop - walkY;
    });

    document.addEventListener('mouseup', () => {
        isDragging = false;
        img.style.cursor = 'grab';
    });

    img.addEventListener('mouseleave', () => {
        isDragging = false;
        img.style.cursor = 'grab';
    });
}

function updateZoom() {
    if (img) {
        img.style.transform = `scale(${zoomLevel / 100})`;
        zoomDisplay.textContent = zoomLevel + '%';
    }
}

function zoomIn() {
    zoomLevel = Math.min(maxZoom, zoomLevel + zoomStep);
    updateZoom();
}

function zoomOut() {
    zoomLevel = Math.max(minZoom, zoomLevel - zoomStep);
    updateZoom();
}

function resetZoom() {
    zoomLevel = 100;
    if (viewer) {
        viewer.scrollLeft = 0;
        viewer.scrollTop = 0;
    }
    updateZoom();
}
</script>
<script src="assets/responsive.js"></script>
</body>
</html>
