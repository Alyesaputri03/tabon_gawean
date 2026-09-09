<?php
require_once 'koneksi.php';

$tabel = trim($_GET['tabel'] ?? '');
$id    = intval($_GET['id'] ?? 0);

// Validasi parameter URL
if (empty($tabel) || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Daftar nama resmi divisi berdasarkan nama tabel Supabase
$daftar_divisi = [
    'layanan_keuangan'      => ['nama' => 'Keuangan', 'file' => 'keuangan.php', 'ikon' => 'wallet'],
    'layanan_kepegawaian'   => ['nama' => 'Kepegawaian', 'file' => 'kepegawaian.php', 'ikon' => 'users'],
    'layanan_administrasi'  => ['nama' => 'Administrasi & Persuratan', 'file' => 'administrasi-persuratan.php', 'ikon' => 'mail'],
    'layanan_pengadaan'     => ['nama' => 'Pengadaan & BMN', 'file' => 'pengadaan.php', 'ikon' => 'shopping-cart'],
    'layanan_sakip'         => ['nama' => 'SAKIP', 'file' => 'sakip.php', 'ikon' => 'award'],
    'layanan_rb_zi'         => ['nama' => 'RB / ZI', 'file' => 'rbzi.php', 'ikon' => 'shield-check'],
    'layanan_ppid'          => ['nama' => 'PPID Satker', 'file' => 'ppid.php', 'ikon' => 'file-text'],
    'layanan_sektoral'      => ['nama' => 'Statistik Sektoral', 'file' => 'statistik-sektoral.php', 'ikon' => 'bar-chart-3'],
    'layanan_ipds'          => ['nama' => 'Tim IPDS & Jaringan', 'file' => 'tim-ipds.php', 'ikon' => 'network'],
    'layanan_diseminasi'    => ['nama' => 'Diseminasi & Dokumentasi', 'file' => 'diseminasi-dokumentasi.php', 'ikon' => 'camera']
];

$info_divisi = $daftar_divisi[$tabel] ?? [
    'nama' => ucwords(str_replace(['layanan_', '_'], ['', ' '], $tabel)),
    'file' => 'index.php',
    'ikon' => 'folder'
];

// Ambil detail layanan dari Supabase
$res = supabase_request('/rest/v1/' . $tabel . '?select=*&id=eq.' . $id . '&limit=1');
$item = !empty($res['data'][0]) ? $res['data'][0] : null;

if (!$item) {
    die("
    <div style='text-align:center; padding:60px 20px; font-family:sans-serif;'>
      <h2 style='color:#1e293b; margin-bottom:10px;'>Data layanan tidak ditemukan</h2>
      <p style='color:#64748b; font-size:14px; margin-bottom:20px;'>Layanan mungkin sudah dihapus atau tautan tidak valid.</p>
      <a href='index.php' style='display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:12px; font-size:13px; font-weight:600;'>Kembali ke Beranda</a>
    </div>");
}

// Format tautan
$raw_url = trim($item['url_link'] ?? '');
$raw_file = trim($item['file_upload'] ?? '');
$link_tujuan = '#';

if (!empty($raw_url) && $raw_url !== '#') {
    $link_tujuan = (!preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
} elseif (!empty($raw_file)) {
    $link_tujuan = 'uploads/' . $raw_file;
}

$is_link_aktif = ($link_tujuan !== '#');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Layanan - <?= htmlspecialchars($item['nama_layanan']); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="flex items-center gap-4">
        <a href="index.php" class="flex items-center hover:opacity-90 transition -translate-y-1 sm:-translate-y-0.5">
          <img src="assets/logo.png?v=3" alt="Tabon Gawean" class="h-20 sm:h-40 w-auto object-contain">
        </a>

      <div class="flex items-center gap-2">
        <a 
          href="<?= htmlspecialchars($info_divisi['file']); ?>" 
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-blue-50 hover:text-blue-700 transition border border-slate-200/80 shadow-sm"
        >
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          <span>Kembali ke Tim <?= htmlspecialchars($info_divisi['nama']); ?></span>
        </a>
      </div>
    </div>
  </header>

  <!-- Konten Utama -->
  <main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 w-full flex-1">
    
    <!-- Navigasi Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-6">
      <a href="index.php" class="hover:text-blue-600 transition">Beranda</a>
      <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
      <a href="<?= htmlspecialchars($info_divisi['file']); ?>" class="hover:text-blue-600 transition">
        <?= htmlspecialchars($info_divisi['nama']); ?>
      </a>
      <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
      <span class="text-slate-600 font-semibold truncate max-w-xs sm:max-w-md">
        <?= htmlspecialchars($item['nama_layanan']); ?>
      </span>
    </nav>

    <!-- Kartu Informasi Detail -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
      
      <!-- Top Card Banner -->
      <div class="p-6 sm:p-8 border-b border-slate-100 bg-gradient-to-br from-white to-slate-50/70">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-sm">
              <i data-lucide="<?= htmlspecialchars($item['ikon'] ?: $info_divisi['ikon']); ?>" class="w-7 h-7"></i>
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700">
                  Tim <?= htmlspecialchars($info_divisi['nama']); ?>
                </span>
                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600">
                  <?= htmlspecialchars($item['kategori'] ?: 'Layanan Kedinasan'); ?>
                </span>
                <?php if ($item['is_deleted'] == 1): ?>
                  <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                    Di Arsip / Pemulihan
                  </span>
                <?php endif; ?>
              </div>
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug">
                <?= htmlspecialchars($item['nama_layanan']); ?>
              </h1>
            </div>
          </div>

          <!-- Tombol Aksi Akses Cepat -->
          <?php if ($is_link_aktif): ?>
            <a 
              href="<?= htmlspecialchars($link_tujuan); ?>" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/20 transition shrink-0"
            >
              <span>Buka Tautan Layanan</span>
              <i data-lucide="external-link" class="w-4 h-4"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Detail Body -->
      <div class="p-6 sm:p-8 space-y-6">
        
        <!-- Deskripsi Lengkap -->
        <div>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Deskripsi & Ruang Lingkup Layanan</h3>
          <div class="text-sm text-slate-700 leading-relaxed bg-slate-50/70 rounded-2xl p-5 border border-slate-100">
            <?= !empty($item['deskripsi']) ? nl2br(htmlspecialchars($item['deskripsi'])) : '<span class="text-slate-400 italic">Belum ada keterangan atau petunjuk operasional tambahan untuk layanan ini.</span>'; ?>
          </div>
        </div>

        <!-- Spesifikasi Metadata -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div class="p-4 rounded-2xl border border-slate-100 bg-white">
            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tautan / URL Akses</span>
            <?php if ($is_link_aktif): ?>
              <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" class="text-xs font-semibold text-blue-600 hover:underline break-all flex items-center gap-1.5">
                <i data-lucide="link" class="w-3.5 h-3.5 shrink-0"></i>
                <span><?= htmlspecialchars($link_tujuan); ?></span>
              </a>
            <?php else: ?>
              <span class="text-xs text-slate-400 italic">Tautan belum tersedia</span>
            <?php endif; ?>
          </div>

          <div class="p-4 rounded-2xl border border-slate-100 bg-white">
            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tabel Supabase Database</span>
            <span class="text-xs font-mono font-semibold text-slate-700 flex items-center gap-1.5">
              <i data-lucide="database" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
              <span><?= htmlspecialchars($tabel); ?> (ID: #<?= $item['id']; ?>)</span>
            </span>
          </div>
        </div>

        <!-- Tombol Aksi Bawah -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
          <a 
            href="<?= htmlspecialchars($info_divisi['file']); ?>" 
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
          >
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
          </a>

          <a 
            href="admin.php?tab=layanan&tabel=<?= urlencode($tabel); ?>" 
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-blue-600 transition"
          >
            <i data-lucide="settings" class="w-3.5 h-3.5"></i>
            <span>Kelola di Admin Console</span>
          </a>
        </div>

      </div>

    </div>

  </main>

  <!-- Footer -->
  <footer class="border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-500 mt-12">
    <p>© 2026 Tabon Gawean — BPS Kota Yogyakarta</p>
  </footer>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>