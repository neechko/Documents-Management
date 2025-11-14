<?php
session_start();
require_once __DIR__ . '/../app/auth.php';
require_login();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Pengurusan Dokumen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); background: rgba(255, 255, 255, 0.15); }
    </style>
</head>
<body class="min-h-screen" style="background: linear-gradient(to bottom right, #020617, #0f172a, #020617); background-attachment: fixed;">
<nav class="glass sticky top-0 z-50 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v12a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h3a1 1 0 000-2h-2a2 2 0 00-2 2v12H4V5z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white">Pengurusan Dokumen</h1>
            </div>

            <div class="hidden md:flex items-center gap-6">
                <a href="documents.php" class="text-slate-300 hover:text-white text-sm transition">Dokumen</a>
                <a href="categories.php" class="text-slate-300 hover:text-white text-sm transition">Kategori</a>
                <a href="audit.php" class="text-slate-300 hover:text-white text-sm transition">Audit</a>
                <div class="flex items-center gap-2 pl-6 border-l border-slate-700">
                    <span class="text-slate-300 text-sm"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                    <a href="logout.php" class="px-3 py-1.5 text-sm bg-red-500/20 text-red-300 hover:bg-red-500/30 rounded-lg transition">Logout</a>
                </div>
            </div>

            <div class="md:hidden flex items-center">
                <button id="navToggle" aria-expanded="false" class="p-2 rounded-md text-slate-300 hover:text-white focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
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
    </div>
</nav>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="mb-16">
        <h2 class="text-4xl font-bold text-white mb-2">Selamat Datang</h2>
        <p class="text-slate-400"><?php echo htmlspecialchars($_SESSION['user']['username']); ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Upload Card -->
        <a href="upload.php" class="group">
            <div class="glass card-hover p-6 rounded-2xl cursor-pointer h-full">
                <div class="p-4 bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl w-fit mb-4 group-hover:scale-110 transition">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"></path>
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-1">Upload Dokumen</h3>
                <p class="text-slate-400 text-sm">Tambahkan atau perbarui dokumen</p>
            </div>
        </a>

        <!-- Documents Card -->
        <a href="documents.php" class="group">
            <div class="glass card-hover p-6 rounded-2xl cursor-pointer h-full">
                <div class="p-4 bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-xl w-fit mb-4 group-hover:scale-110 transition">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-1">Daftar Dokumen</h3>
                <p class="text-slate-400 text-sm">Kelola dan cari dokumen Anda</p>
            </div>
        </a>

        <!-- Categories Card -->
        <a href="categories.php" class="group">
            <div class="glass card-hover p-6 rounded-2xl cursor-pointer h-full">
                <div class="p-4 bg-gradient-to-br from-orange-500/20 to-red-500/20 rounded-xl w-fit mb-4 group-hover:scale-110 transition">
                    <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-1">Kategori</h3>
                <p class="text-slate-400 text-sm">Atur folder dan kategori</p>
            </div>
        </a>

        <!-- Audit Card -->
        <a href="audit.php" class="group">
            <div class="glass card-hover p-6 rounded-2xl cursor-pointer h-full">
                <div class="p-4 bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-xl w-fit mb-4 group-hover:scale-110 transition">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-white font-semibold mb-1">Audit Logs</h3>
                <p class="text-slate-400 text-sm">Lihat aktivitas sistem</p>
            </div>
        </a>
    </div>
</div>
<script src="assets/responsive.js"></script>
</body>
</html>
