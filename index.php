<?php
require_once 'koneksi.php';

$pesan_sukses = '';
$pesan_error  = '';

// 1. TAMBAH TIM BARU KE SUPABASE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_divisi'])) {
    $nama_modul  = trim($_POST['nama_modul'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $file_target = trim($_POST['file_target'] ?? '');
    $ikon        = 'folder';

    if (!empty($nama_modul)) {
        $slug_dasar = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama_modul)));
        $slug = $slug_dasar;

        // Cek duplikasi slug di Supabase
        $cek = supabase_request('/rest/v1/modul_layanan?select=id&slug=eq.' . urlencode($slug));
        if (!empty($cek['data'])) {
            $slug = $slug_dasar . '-' . time();
        }

        if (empty($file_target)) {
            $file_target = $slug . '.php';
        }

        $payload = [
            'nama_modul'  => $nama_modul,
            'deskripsi'   => $deskripsi,
            'slug'        => $slug,
            'file_target' => $file_target,
            'ikon'        => $ikon,
            'urutan'      => 99,
            'is_deleted'  => 0
        ];

        $res = supabase_request('/rest/v1/modul_layanan', 'POST', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Tim baru berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menyimpan: " . ($res['error'] ?: 'API Error ' . $res['status']);
        }
    } else {
        $pesan_error = "Nama tim tidak boleh kosong!";
    }
}

// 2. EDIT TIM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_divisi'])) {
    $id         = intval($_POST['id'] ?? 0);
    $nama_modul = trim($_POST['nama_modul'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');

    if ($id > 0 && !empty($nama_modul)) {
        $res = supabase_request('/rest/v1/modul_layanan?id=eq.' . $id, 'PATCH', [
            'nama_modul' => $nama_modul,
            'deskripsi'  => $deskripsi
        ]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Perubahan data tim berhasil disimpan!";
        } else {
            $pesan_error = "Gagal memperbarui: " . ($res['error'] ?: 'API Error');
        }
    }
}

// 3. HAPUS SEMENTARA (SOFT DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_divisi'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/modul_layanan?id=eq.' . $id, 'PATCH', ['is_deleted' => 1]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Tim dipindahkan ke menu Pemulihan.";
        }
    }
}

// 4. PULIHKAN (RESTORE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_divisi'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/modul_layanan?id=eq.' . $id, 'PATCH', ['is_deleted' => 0]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Tim berhasil dipulihkan kembali!";
        }
    }
}

// 5. HAPUS PERMANEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_permanen_divisi'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/modul_layanan?id=eq.' . $id, 'DELETE');
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Tim berhasil dihapus secara permanen dari Supabase.";
        }
    }
}

// Ambil modul tim yang AKTIF (dengan proteksi array)
$res_modul = supabase_request('/rest/v1/modul_layanan?select=*&is_deleted=eq.0&order=urutan.asc');
$daftar_modul = [];
if (!empty($res_modul['data']) && is_array($res_modul['data'])) {
    foreach ($res_modul['data'] as $m) {
        if (is_array($m)) {
            $daftar_modul[] = $m;
        }
    }
}

// Ambil modul tim yang TERHAPUS untuk menu pemulihan
$res_sampah = supabase_request('/rest/v1/modul_layanan?select=*&is_deleted=eq.1&order=id.desc');
$daftar_sampah = [];
if (!empty($res_sampah['data']) && is_array($res_sampah['data'])) {
    foreach ($res_sampah['data'] as $sp) {
        if (is_array($sp)) {
            $daftar_sampah[] = $sp;
        }
    }
}
$jumlah_sampah = count($daftar_sampah);

// Pemetaan tabel-tabel tim untuk pencarian global
$daftar_divisi = [
    'layanan_keuangan'      => ['divisi' => 'Keuangan', 'slug' => 'keuangan.php'],
    'layanan_kepegawaian'   => ['divisi' => 'Kepegawaian', 'slug' => 'kepegawaian.php'],
    'layanan_administrasi'  => ['divisi' => 'Administrasi & Persuratan', 'slug' => 'administrasi-persuratan.php'],
    'layanan_pengadaan'     => ['divisi' => 'Pengadaan & BMN', 'slug' => 'pengadaan.php'],
    'layanan_sakip'         => ['divisi' => 'SAKIP', 'slug' => 'sakip.php'],
    'layanan_rb_zi'         => ['divisi' => 'RB / ZI', 'slug' => 'rbzi.php'],
    'layanan_ppid'          => ['divisi' => 'PPID Satker', 'slug' => 'ppid.php'],
    'layanan_sektoral'      => ['divisi' => 'Statistik Sektoral', 'slug' => 'statistik-sektoral.php'],
    'layanan_ipds'          => ['divisi' => 'Tim IPDS & Jaringan', 'slug' => 'tim-ipds.php'],
    'layanan_diseminasi'    => ['divisi' => 'Diseminasi & Dokumentasi', 'slug' => 'diseminasi-dokumentasi.php'],
    'layanan_dinamis'       => ['divisi' => 'Tim Mandiri', 'slug' => 'divisi.php']
];

$semua_layanan = [];
foreach ($daftar_divisi as $tabel => $info) {
    $res = supabase_request('/rest/v1/' . $tabel . '?select=*&is_deleted=eq.0&order=urutan.asc');
    if (!empty($res['data']) && is_array($res['data'])) {
        foreach ($res['data'] as $row) {
            if (is_array($row)) {
                $target_link = $info['slug'];
                if ($tabel === 'layanan_dinamis' && !empty($row['slug_modul'])) {
                    $target_link = 'divisi.php?slug=' . urlencode($row['slug_modul']);
                }
                $row['divisi_induk'] = $info['divisi'];
                $row['file_induk']   = $target_link;
                $row['tabel_asal']   = $tabel;
                $semua_layanan[]     = $row;
            }
        }
    }
}

// Ambil data kutipan / banner
$res_kutipan = supabase_request('/rest/v1/kutipan?select=*&limit=1');
$kutipan = !empty($res_kutipan['data'][0]) && is_array($res_kutipan['data'][0]) ? $res_kutipan['data'][0] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tabon Gawean - BPS Kota Yogyakarta</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Sisi Kiri: Logo Besar & Jelas -->
      <a href="index.php" class="flex items-center hover:opacity-90 transition">
        <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-14 sm:h-16 w-auto object-contain drop-shadow-sm">
      </a>

      <!-- Sisi Kanan: Status & Tombol Aksi -->
      <div class="flex items-center gap-3">
        <!-- Status Terhubung Supabase -->
        <span class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-sm">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Sistem Aktif</span>
        </span>

        <!-- Tombol Pemulihan Tim -->
        <button 
          type="button" 
          onclick="bukaModalSampahDivisi()" 
          title="Buka menu pemulihan tim terhapus"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition border border-slate-200 shadow-sm"
        >
          <i data-lucide="archive-restore" class="w-4 h-4 text-slate-500"></i>
          <span>Pemulihan</span>
          <?php if ($jumlah_sampah > 0): ?>
            <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-rose-500 text-white leading-none">
              <?= $jumlah_sampah; ?>
            </span>
          <?php endif; ?>
        </button>

        <!-- Link ke Panel Admin -->
        <a 
          href="admin.php" 
          title="Panel Kontrol Admin"
          class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition border border-slate-200 shadow-sm"
        >
          <i data-lucide="settings" class="w-5 h-5"></i>
        </a>
      </div>
    </div>
  </header>

  <!-- Konten Utama -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full flex-1">
    
    <!-- Alert Notifikasi -->
    <?php if (!empty($pesan_sukses)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
          <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
          <span><?= htmlspecialchars($pesan_sukses); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
      </div>
    <?php elseif (!empty($pesan_error)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
          <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
          <span><?= htmlspecialchars($pesan_error); ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
      </div>
    <?php endif; ?>

    <!-- Hero Banner MANDALA KRIDA (Dinamis dari Supabase) -->
    <div class="relative rounded-3xl text-white p-8 sm:p-10 mb-10 shadow-xl overflow-hidden bg-slate-900">
      <img 
        src="assets/kantor-bps.jpg" 
        alt="Kantor BPS Kota Yogyakarta" 
        class="absolute inset-0 w-full h-full object-cover object-center transform scale-105 filter brightness-95"
      >
      <div class="absolute inset-0 bg-gradient-to-r from-blue-950/95 via-blue-900/90 to-blue-800/80 backdrop-blur-[1.5px]"></div>
      <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-3xl relative z-10">
        <span class="inline-block px-3 py-1 rounded-lg bg-blue-500/20 text-blue-200 text-xs font-bold uppercase tracking-wider mb-4 border border-blue-400/30 backdrop-blur-md">
          MANDALA KRIDA
        </span>
        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight drop-shadow-sm">
          MANajemen Dokumen Administrasi LAporan KineRja Arsip
        </h2>
        
        <div class="mt-4 pt-4 border-t border-white/20">
          <p class="text-sm sm:text-base text-blue-100 font-light drop-shadow-sm leading-relaxed">
            <?= htmlspecialchars($kutipan['isi_kutipan'] ?? 'Sistem pengelolaan arsip dan dokumen administrasi terpadu satu pintu, mendukung tata kelola yang tertib, transparan, dan mudah diakses kapan saja dan di mana saja.'); ?>
          </p>
          <p class="text-xs font-semibold text-blue-200 mt-2">
            — <?= htmlspecialchars($kutipan['penulis'] ?? 'BPS Kota Yogyakarta'); ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <h3 id="labelJudul" class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <i data-lucide="layout-grid" class="w-5 h-5 text-blue-600"></i>
          <span>Daftar Layanan Kerja Tim</span>
        </h3>
        <p id="labelDeskripsi" class="text-xs sm:text-sm text-slate-500 mt-1">
          Akses modul tugas, aplikasi internal, dan berkas kerja aktif per tim
        </p>
      </div>

      <div class="relative w-full sm:w-80">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="search" class="w-4 h-4"></i>
        </div>
        <input 
          type="text" 
          id="inputSearch" 
          placeholder="Cari layanan, formulir, atau berkas..." 
          class="w-full pl-10 pr-4 py-2.5 bg-white rounded-2xl border border-slate-200 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-sm"
        >
      </div>
    </div>

    <!-- 1. GRID DAFTAR MODUL TIM -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="containerModul">
      <?php foreach ($daftar_modul as $m): ?>
        <?php 
          $nama_file_asli = !empty($m['file_target']) ? $m['file_target'] : $m['slug'] . '.php';
          $target_file = file_exists($nama_file_asli) ? $nama_file_asli : 'divisi.php?slug=' . urlencode($m['slug']);
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between hover:border-blue-500 hover:shadow-lg transition-all duration-200 relative group">
          <div>
            <!-- Header Kartu: Ikon & Aksi -->
            <div class="flex items-center justify-between mb-5">
              <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition duration-200 shadow-sm">
                <i data-lucide="<?= htmlspecialchars($m['ikon'] ?: 'folder'); ?>" class="w-6 h-6"></i>
              </div>

              <div class="flex items-center gap-1.5">
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 uppercase tracking-wider">
                  Tim
                </span>

                <!-- Tombol Edit Tim -->
                <button 
                  type="button" 
                  onclick='bukaModalEditDivisi(<?= json_encode($m); ?>)'
                  title="Edit Tim"
                  class="w-7 h-7 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition"
                >
                  <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>

                <!-- Tombol Hapus Tim -->
                <form method="POST" action="index.php" onsubmit="return confirm('Pindahkan tim <?= htmlspecialchars($m['nama_modul']); ?> ke Pemulihan?');" class="inline">
                  <input type="hidden" name="id" value="<?= $m['id']; ?>">
                  <button 
                    type="submit" 
                    name="hapus_divisi" 
                    title="Hapus Tim"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition"
                  >
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                  </button>
                </form>
              </div>
            </div>

            <h4 class="font-bold text-slate-900 text-lg group-hover:text-blue-600 transition leading-snug">
              <?= htmlspecialchars($m['nama_modul']); ?>
            </h4>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed line-clamp-3">
              <?= htmlspecialchars($m['deskripsi']); ?>
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100">
            <a href="<?= htmlspecialchars($target_file); ?>" class="w-full inline-flex items-center justify-between text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
              <span>Buka Menu Layanan</span>
              <div class="w-6 h-6 rounded-full bg-blue-50 flex items-center justify-center group-hover:translate-x-1 transition duration-200">
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Tombol Tambah Tim Baru -->
      <button 
        type="button" 
        onclick="bukaModalDivisi()" 
        class="group min-h-[220px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 hover:bg-blue-50/50 p-6 flex flex-col items-center justify-center text-center transition"
      >
        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition shadow-sm mb-3">
          <i data-lucide="plus" class="w-6 h-6"></i>
        </div>
        <span class="font-bold text-slate-700 group-hover:text-blue-600 text-sm sm:text-base transition">
          Tambah Tim Baru
        </span>
        <p class="text-xs text-slate-400 mt-1">
          Tambahkan modul kerja tim baru ke portal
        </p>
      </button>
    </div>

    <!-- 2. GRID HASIL PENCARIAN REALTIME -->
    <div class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="containerHasilPencarian">
      <?php foreach ($semua_layanan as $layanan): ?>
        <?php
          $raw_url = trim($layanan['url_link'] ?? '');
          $link_tujuan = (!empty($raw_url) && !preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
        ?>
        <div class="kartu-layanan bg-white rounded-2xl border border-slate-200/80 p-6 flex flex-col justify-between hover:border-blue-400 hover:shadow-md transition"
             data-title="<?= strtolower(htmlspecialchars($layanan['nama_layanan'])); ?>"
             data-desc="<?= strtolower(htmlspecialchars($layanan['deskripsi'] ?? '')); ?>"
             data-divisi="<?= strtolower(htmlspecialchars($layanan['divisi_induk'])); ?>"
             data-kategori="<?= strtolower(htmlspecialchars($layanan['kategori'] ?? '')); ?>">
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700">
                <?= htmlspecialchars($layanan['divisi_induk']); ?>
              </span>
              <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                <?= htmlspecialchars($layanan['kategori'] ?: 'Layanan'); ?>
              </span>
            </div>
            
            <h4 class="font-bold text-slate-900 text-base leading-snug">
              <?= htmlspecialchars($layanan['nama_layanan']); ?>
            </h4>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
              <?= htmlspecialchars($layanan['deskripsi']); ?>
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition">
              <span>Buka Tautan</span>
              <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>

            <div class="flex items-center gap-2">
              <a href="detail.php?tabel=<?= urlencode($layanan['tabel_asal']); ?>&id=<?= $layanan['id']; ?>" class="text-[11px] text-slate-400 hover:text-slate-600">
                Detail
              </a>
              <span class="text-slate-200">•</span>
              <a href="<?= htmlspecialchars($layanan['file_induk']); ?>" class="text-[11px] text-blue-500 hover:underline">
                Ke Modul &rarr;
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pesan Pencarian Tidak Ditemukan -->
    <div id="noResults" class="hidden text-center py-16">
      <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="search-x" class="w-8 h-8"></i>
      </div>
      <h4 class="text-base font-bold text-slate-800">Layanan tidak ditemukan</h4>
      <p class="text-xs text-slate-500 mt-1">Periksa kembali kata kunci atau ejaan yang Anda masukkan.</p>
    </div>

  </main>

  <!-- MODAL 1: TAMBAH TIM BARU -->
  <div id="modalTambahDivisi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base sm:text-lg">Tambah Tim Baru</h3>
        </div>
        <button type="button" onclick="tutupModalDivisi()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form method="POST" action="index.php" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Tim *</label>
          <input type="text" name="nama_modul" required placeholder="Contoh: Tata Usaha" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" rows="3" placeholder="Jelaskan tugas dan fungsi tim ini..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalDivisi()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
            Batal
          </button>
          <button type="submit" name="tambah_divisi" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>Simpan Tim</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 2: EDIT TIM -->
  <div id="modalEditDivisi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <i data-lucide="pencil" class="w-5 h-5"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base sm:text-lg">Edit Tim</h3>
        </div>
        <button type="button" onclick="tutupModalEditDivisi()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form method="POST" action="index.php" class="space-y-4">
        <input type="hidden" name="id" id="edit_divisi_id">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Tim *</label>
          <input type="text" name="nama_modul" id="edit_divisi_nama" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" id="edit_divisi_deskripsi" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalEditDivisi()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
            Batal
          </button>
          <button type="submit" name="edit_divisi" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
            <i data-lucide="check" class="w-4 h-4"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 3: PEMULIHAN TIM -->
  <div id="modalSampahDivisi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-8 flex flex-col max-h-[85vh]">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
            <i data-lucide="archive-restore" class="w-5 h-5"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-base sm:text-lg">Pemulihan Tim Kerja</h3>
            <p class="text-xs text-slate-400">Daftar modul kerja yang terhapus dari beranda utama</p>
          </div>
        </div>
        <button type="button" onclick="tutupModalSampahDivisi()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <div class="overflow-y-auto pr-1 flex-1 space-y-3">
        <?php if ($jumlah_sampah === 0): ?>
          <div class="text-center py-10">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
              <i data-lucide="check" class="w-6 h-6"></i>
            </div>
            <p class="text-xs font-semibold text-slate-500">Tidak ada tim yang terhapus.</p>
          </div>
        <?php else: ?>
          <?php foreach ($daftar_sampah as $sd): ?>
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 uppercase">
                  Tim
                </span>
                <h4 class="font-bold text-slate-800 text-sm mt-1"><?= htmlspecialchars($sd['nama_modul']); ?></h4>
                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($sd['deskripsi']); ?></p>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="index.php" class="inline">
                  <input type="hidden" name="id" value="<?= $sd['id']; ?>">
                  <button 
                    type="submit" 
                    name="restore_divisi" 
                    title="Pulihkan Tim Ini"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition"
                  >
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Pulihkan</span>
                  </button>
                </form>

                <form method="POST" action="index.php" onsubmit="return confirm('Peringatan: Tim ini akan dihapus secara permanen dari Supabase. Lanjutkan?');" class="inline">
                  <input type="hidden" name="id" value="<?= $sd['id']; ?>">
                  <button 
                    type="submit" 
                    name="hapus_permanen_divisi" 
                    title="Hapus Selamanya"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition"
                  >
                    <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                    <span>Permanen</span>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="pt-4 border-t border-slate-100 mt-4 flex justify-end">
        <button type="button" onclick="tutupModalSampahDivisi()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
          Tutup
        </button>
      </div>
    </div>
  </div>

 <!-- Footer -->
  <footer class="border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-500 mt-10">
    <p>© 2026 Tabon Gawean — BPS Kota Yogyakarta</p>
  </footer>


  <script>
    lucide.createIcons();

    // Kontrol Modal Tambah Tim
    function bukaModalDivisi() {
      document.getElementById('modalTambahDivisi').classList.remove('hidden');
    }
    function tutupModalDivisi() {
      document.getElementById('modalTambahDivisi').classList.add('hidden');
    }

    // Kontrol Modal Edit Tim
    function bukaModalEditDivisi(data) {
      document.getElementById('edit_divisi_id').value = data.id || '';
      document.getElementById('edit_divisi_nama').value = data.nama_modul || '';
      document.getElementById('edit_divisi_deskripsi').value = data.deskripsi || '';
      document.getElementById('modalEditDivisi').classList.remove('hidden');
    }
    function tutupModalEditDivisi() {
      document.getElementById('modalEditDivisi').classList.add('hidden');
    }

    // Kontrol Modal Pemulihan Tim
    function bukaModalSampahDivisi() {
      document.getElementById('modalSampahDivisi').classList.remove('hidden');
    }
    function tutupModalSampahDivisi() {
      document.getElementById('modalSampahDivisi').classList.add('hidden');
    }

    // Mesin Pencarian Realtime
    const searchInput = document.getElementById('inputSearch');
    const containerModul = document.getElementById('containerModul');
    const containerHasilPencarian = document.getElementById('containerHasilPencarian');
    const itemCards = document.querySelectorAll('.kartu-layanan');
    const noResults = document.getElementById('noResults');
    const labelJudul = document.getElementById('labelJudul');
    const labelDeskripsi = document.getElementById('labelDeskripsi');

    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const keyword = this.value.toLowerCase().trim();

        if (keyword === '') {
          containerModul.classList.remove('hidden');
          containerHasilPencarian.classList.add('hidden');
          noResults.classList.add('hidden');

          labelJudul.innerHTML = '<i data-lucide="layout-grid" class="w-5 h-5 text-blue-600"></i><span>Daftar Layanan Kerja Tim</span>';
          labelDeskripsi.textContent = 'Akses modul tugas, aplikasi internal, dan berkas kerja aktif per tim';
          lucide.createIcons();
        } else {
          containerModul.classList.add('hidden');
          containerHasilPencarian.classList.remove('hidden');

          let matchCount = 0;
          itemCards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const desc = card.getAttribute('data-desc') || '';
            const divisi = card.getAttribute('data-divisi') || '';
            const kategori = card.getAttribute('data-kategori') || '';

            if (title.includes(keyword) || desc.includes(keyword) || divisi.includes(keyword) || kategori.includes(keyword)) {
              card.classList.remove('hidden');
              matchCount++;
            } else {
              card.classList.add('hidden');
            }
          });

          labelJudul.innerHTML = `<i data-lucide="search" class="w-5 h-5 text-blue-600"></i><span>Hasil Pencarian: "${this.value}"</span>`;
          labelDeskripsi.textContent = `Ditemukan ${matchCount} layanan yang cocok`;
          lucide.createIcons();

          if (matchCount === 0) {
            noResults.classList.remove('hidden');
          } else {
            noResults.classList.add('hidden');
          }
        }
      });
    }
  </script>
</body>
</html>