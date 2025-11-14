<?php
session_start();
require_once __DIR__ . '/../app/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login_user($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Pengurusan Dokumen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'slate': {
                            '50': '#f8fafc',
                            '900': '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.23); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.44); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(to bottom right, #020617, #0f172a, #020617); background-attachment: fixed;">
    <div class="w-full max-w-md">
        <div class="glass rounded-2xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 mb-4">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H3a1 1 0 00-1 1v12a1 1 0 001 1h14a1 1 0 001-1V6a1 1 0 00-1-1h3a1 1 0 000-2h-2a2 2 0 00-2 2v12H4V5z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">Pengurusan Dokumen</h1>
                <p class="text-slate-400 text-sm">Kelola dokumen Anda dengan mudah</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 mb-6">
                    <p class="text-red-400 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Username</label>
                    <input name="username" class="w-full px-4 py-2.5 bg-white/5 border border-slate-400/20 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-blue-500/50 focus:bg-white/10 transition" placeholder="Masukkan username" required autofocus>
                </div>
                <div>
                    <label class="block text-slate-300 text-sm font-medium mb-2">Password</label>
                    <input name="password" type="password" class="w-full px-4 py-2.5 bg-white/5 border border-slate-400/20 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-blue-500/50 focus:bg-white/10 transition" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-medium rounded-lg hover:from-blue-600 hover:to-cyan-600 transition duration-200 shadow-lg">
                    Login
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-400/20">
                <p class="text-center text-slate-400 text-xs">Default: admin / admin123</p>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-slate-400 text-sm">© 2025 Sistem Pengurusan Dokumen | Create by Mario</p>
        </div>
    </div>
</body>
</html>
</body>
</html>
