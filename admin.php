<?php
require_once 'koneksi.php';

$pesan_sukses = '';
$pesan_error  = '';

// PROSES SIMPAN TEKS BANNER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kutipan'])) {
    $isi_kutipan = trim($_POST['isi_kutipan'] ?? '');
    $penulis     = trim($_POST['penulis'] ?? '');

    $cek = supabase_request('/rest/v1/kutipan?select=id&limit=1');
    if (!empty($cek['data'][0]['id'])) {
        $id = $cek['data'][0]['id'];
        $res = supabase_request('/rest/v1/kutipan?id=eq.' . $id, 'PATCH', [
            'isi_kutipan' => $isi_kutipan,
            'penulis'     => $penulis
        ]);
    } else {
        $res = supabase_request('/rest/v1/kutipan', 'POST', [
            'isi_kutipan' => $isi_kutipan,
            'penulis'     => $penulis
        ]);
    }

    if ($res['status'] >= 200 && $res['status'] < 300) {
        $pesan_sukses = "Teks banner berhasil disimpan!";
    } else {
        $pesan_error = "Gagal menyimpan teks banner: " . ($res['error'] ?: 'API Error');
    }
}

// Ambil Data Kutipan Banner
$res_kutipan = supabase_request('/rest/v1/kutipan?select=*&limit=1');
$data_kutipan = !empty($res_kutipan['data'][0]) && is_array($res_kutipan['data'][0]) ? $res_kutipan['data'][0] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setting Banner - Tabon Gawean</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="flex items-center gap-4">
        <a href="index.php" class="flex items-center hover:opacity-90 transition -translate-y-1 sm:-translate-y-0.5">
          <img src="assets/logo.png?v=3" alt="Tabon Gawean" class="h-20 sm:h-40 w-auto object-contain">
        </a>

      <div class="flex items-center gap-2.5">
        <!-- Status Terhubung Supabase -->
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shadow-sm">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Sistem Aktif</span>
        </span>

        <div class="flex items-center gap-2.5">
          <a 
            href="index.php" 
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 transition border border-slate-200/80 shadow-sm"
          >
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Beranda</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Konten Utama -->
  <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full flex-1">
    
    <!-- Judul Halaman -->
    <div class="mb-8">
      <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pengaturan Banner Portal</h2>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Konfigurasi teks narasi dan penanggung jawab pada banner beranda MANDALA KRIDA.</p>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($pesan_sukses)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
          <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
          <span><?= htmlspecialchars($pesan_sukses); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-600 font-bold">&times;</button>
      </div>
    <?php elseif (!empty($pesan_error)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
          <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
          <span><?= htmlspecialchars($pesan_error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-600 font-bold">&times;</button>
      </div>
    <?php endif; ?>

    <!-- Form Pengaturan Banner -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
      <div class="flex items-center gap-3 pb-5 border-b border-slate-100 mb-6">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <i data-lucide="message-square" class="w-5 h-5"></i>
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-base">Teks Narasi & Motto Banner</h3>
          <p class="text-xs text-slate-400">Teks ini tampil langsung di bawah judul utama beranda</p>
        </div>
      </div>

      <form method="POST" action="admin.php" class="space-y-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Isi Narasi / Motto Portal *</label>
          <textarea 
            name="isi_kutipan" 
            rows="4" 
            required 
            class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition leading-relaxed"
          ><?= htmlspecialchars($data_kutipan['isi_kutipan'] ?? 'Sistem pengelolaan arsip dan dokumen administrasi terpadu satu pintu, mendukung tata kelola yang tertib, transparan, dan mudah diakses kapan saja dan di mana saja.'); ?></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Penulis / Nama Instansi</label>
          <input 
            type="text" 
            name="penulis" 
            value="<?= htmlspecialchars($data_kutipan['penulis'] ?? 'BPS Kota Yogyakarta'); ?>" 
            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
          >
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
          <button 
            type="submit" 
            name="simpan_kutipan" 
            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center gap-2"
          >
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>Simpan Perubahan Teks</span>
          </button>
        </div>
      </form>
    </div>

  </main>

  <!-- Footer -->
  <footer class="border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-500 mt-10">
    <p>© 2026 Tabon Gawean — BPS Kota Yogyakarta</p>
  </footer>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>