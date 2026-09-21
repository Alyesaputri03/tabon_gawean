<?php
require_once __DIR__ . '/auth.php';
wajib_login();

$tabel = trim($_GET['tabel'] ?? '');
$id    = intval($_GET['id'] ?? 0);

if (empty($tabel) || $id <= 0) {
    header('Location: index.php');
    exit;
}

// Ambil data detail layanan berdasarkan tabel dan ID
$res = supabase_request('/rest/v1/' . urlencode($tabel) . '?id=eq.' . $id . '&limit=1');
$layanan = $res['data'][0] ?? null;

if (!$layanan) {
    echo "<script>alert('Data layanan tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

// Pemetaan slug dan nama modul untuk tombol navigasi kembali
$map_divisi = [
    'layanan_keuangan'     => ['nama' => 'Keuangan', 'slug' => 'keuangan'],
    'layanan_kepegawaian'  => ['nama' => 'Kepegawaian', 'slug' => 'kepegawaian'],
    'layanan_administrasi' => ['nama' => 'Administrasi & Persuratan', 'slug' => 'administrasi-persuratan'],
    'layanan_pengadaan'    => ['nama' => 'Pengadaan & BMN', 'slug' => 'pengadaan'],
    'layanan_sakip'        => ['nama' => 'SAKIP', 'slug' => 'sakip'],
    'layanan_rb_zi'        => ['nama' => 'RB / ZI', 'slug' => 'rbzi'],
    'layanan_ppid'         => ['nama' => 'PPID Satker', 'slug' => 'ppid'],
    'layanan_sektoral'     => ['nama' => 'Statistik Sektoral', 'slug' => 'statistik-sektoral'],
    'layanan_ipds'         => ['nama' => 'Tim IPDS & Jaringan', 'slug' => 'tim-ipds'],
    'layanan_diseminasi'   => ['nama' => 'Diseminasi & Dokumentasi', 'slug' => 'diseminasi-dokumentasi']
];

if ($tabel === 'layanan_dinamis' && !empty($layanan['slug_modul'])) {
    $slug_kembali = $layanan['slug_modul'];
    $nama_tim = ucwords(str_replace('-', ' ', $slug_kembali));
} else {
    $slug_kembali = $map_divisi[$tabel]['slug'] ?? '';
    $nama_tim = $map_divisi[$tabel]['nama'] ?? 'Tim';
}

$link_kembali = !empty($slug_kembali) ? 'divisi.php?slug=' . urlencode($slug_kembali) : 'index.php';

// Format URL Link
$raw_url = trim($layanan['url_link'] ?? '');
$link_tujuan = (!empty($raw_url) && !preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Layanan - <?= htmlspecialchars($layanan['nama_layanan'] ?? 'Tabon Gawean'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Sisi Kiri: Logo Bersih & Selaras -->
      <a href="index.php" class="flex items-center hover:opacity-90 transition">
        <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-10 sm:h-12 w-auto object-contain drop-shadow-sm">
      </a>

      <!-- Sisi Kanan: Tombol Kembali -->
      <a 
        href="<?= htmlspecialchars($link_kembali); ?>" 
        class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200 shadow-sm"
      >
        <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
        <span>Kembali ke <?= htmlspecialchars((stripos($nama_tim, 'tim') === 0 ? '' : 'Tim ') . $nama_tim); ?></span>
      </a>

    </div>
  </header>

  <!-- Konten Utama -->
  <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full flex-1">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs font-medium text-slate-400 mb-6">
      <a href="index.php" class="hover:text-blue-600 transition">Beranda</a>
      <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300"></i>
      <a href="<?= htmlspecialchars($link_kembali); ?>" class="hover:text-blue-600 transition">
        <?= htmlspecialchars($nama_tim); ?>
      </a>
      <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300"></i>
      <span class="text-slate-600 truncate max-w-[200px] sm:max-w-xs"><?= htmlspecialchars($layanan['nama_layanan']); ?></span>
    </nav>

    <!-- Kartu Informasi Layanan -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-8 border-b border-slate-100">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-sm">
            <i data-lucide="layout-grid" class="w-7 h-7"></i>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">
                <?= htmlspecialchars((stripos($nama_tim, 'tim') === 0 ? '' : 'Tim ') . $nama_tim); ?>
              </span>
              <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600">
                <?= htmlspecialchars($layanan['kategori'] ?: 'Layanan'); ?>
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
              <?= htmlspecialchars($layanan['nama_layanan']); ?>
            </h1>
          </div>
        </div>

        <?php if (!empty($link_tujuan)): ?>
          <a 
            href="<?= htmlspecialchars($link_tujuan); ?>" 
            target="_blank" 
            rel="noopener noreferrer" 
            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md shadow-blue-500/20 transition shrink-0"
          >
            <span>Buka Tautan Layanan</span>
            <i data-lucide="external-link" class="w-4 h-4"></i>
          </a>
        <?php endif; ?>
      </div>

      <div class="py-8 border-b border-slate-100">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Deskripsi & Ruang Lingkup Layanan</h2>
        <p class="text-slate-700 text-sm sm:text-base leading-relaxed">
          <?= nl2br(htmlspecialchars($layanan['deskripsi'] ?: 'Tidak ada deskripsi rinci untuk layanan ini.')); ?>
        </p>
      </div>

      <div class="pt-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Tautan / URL Akses</span>
          <?php if (!empty($link_tujuan)): ?>
            <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-blue-600 hover:underline break-all inline-flex items-center gap-1.5">
              <i data-lucide="link" class="w-4 h-4 text-blue-400 shrink-0"></i>
              <span><?= htmlspecialchars($link_tujuan); ?></span>
            </a>
          <?php else: ?>
            <span class="text-sm text-slate-400 italic">Belum ada tautan yang disematkan.</span>
          <?php endif; ?>
        </div>

        <div>
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Tabel Supabase Database</span>
          <div class="inline-flex items-center gap-2 text-sm text-slate-600 font-mono bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <i data-lucide="database" class="w-4 h-4 text-slate-400"></i>
            <span><?= htmlspecialchars($tabel); ?> (ID: #<?= $layanan['id']; ?>)</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Tombol Navigasi Bawah -->
    <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
      <a href="<?= htmlspecialchars($link_kembali); ?>" class="hover:text-slate-600 flex items-center gap-1.5 transition">
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
        <span>Kembali</span>
      </a>
      <span>Tabon Gawean • BPS Kota Yogyakarta</span>
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