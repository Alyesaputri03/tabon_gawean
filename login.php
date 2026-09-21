<?php
// login.php
require_once __DIR__ . '/auth.php';

// Jika sudah login, langsung alihkan ke index.php
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $pesan_error = 'Email dan kata sandi wajib diisi!';
    } elseif ($password !== SHARED_PASSWORD) {
        $pesan_error = 'Kata sandi salah!';
    } else {
        // Cari email di Supabase
        $query = supabase_request('users?select=*&email=eq.' . urlencode($email) . '&limit=1');
        $user  = $query['data'][0] ?? null;

        if ($user) {
            // Set session login
            $_SESSION['user'] = [
                'id'     => $user['id'],
                'nama'   => $user['nama'],
                'email'  => $user['email'],
                'role'   => $user['role'],     // admin, ketua_tim, pegawai
                'divisi' => $user['divisi']    // divisi binaan ketua tim
            ];
            header('Location: index.php');
            exit;
        } else {
            $pesan_error = 'Email tidak terdaftar dalam sistem. Hubungi Admin!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Tabon Gawean BPS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

  <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-xl p-8">
    <div class="text-center mb-8">
      <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-16 mx-auto mb-3 object-contain">
      <h2 class="text-2xl font-extrabold text-slate-800">Masuk Portal</h2>
      <p class="text-xs text-slate-500 mt-1">Portal Modul & Layanan Terpadu Pegawai</p>
    </div>

    <?php if (!empty($pesan_error)): ?>
      <div class="mb-5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
        <span><?= htmlspecialchars($pesan_error); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Akun</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="mail" class="w-4 h-4"></i>
          </div>
          <input type="email" name="email" required placeholder="nama@bps.go.id" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="lock" class="w-4 h-4"></i>
          </div>
          <input type="password" name="password" required placeholder="••••••••" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
        </div>
        <p class="text-[11px] text-slate-400 mt-1.5">*Gunakan kata sandi internal yang telah dibagikan.</p>
      </div>

      <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2 mt-2">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        <span>Masuk Sekarang</span>
      </button>
    </form>
  </div>

  <script> lucide.createIcons(); </script>
</body>
</html>