<?php
require_once __DIR__ . '/auth.php';
wajib_login();

// Ambil data profil user aktif
$user_login  = current_user();
$role_user   = $user_login['role'] ?? 'pegawai';
$divisi_user = $user_login['divisi'] ?? '';

$pesan_sukses = '';
$pesan_error  = '';

// Tangkap slug modul dari parameter URL
$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// 1. Ambil data informasi modul dari Supabase
$query_modul = supabase_request('/rest/v1/modul_layanan?slug=eq.' . urlencode($slug) . '&limit=1');
$modul = $query_modul['data'][0] ?? null;

if (!$modul) {
    echo "<script>alert('Modul tim tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$nama_divisi      = $modul['nama_modul'] ?? 'Layanan';
$deskripsi_divisi = $modul['deskripsi'] ?? '';
$ikon_divisi      = !empty($modul['ikon']) ? $modul['ikon'] : 'folder';

// Format nama tim header agar rapi (tidak dobel kata Tim)
$label_tim_header = (stripos($nama_divisi, 'Tim') === 0) ? $nama_divisi : 'Tim ' . $nama_divisi;

// Normalisasi pengecekan divisi (abaikan kata 'Tim', spasi, dan huruf besar/kecil)
$clean_divisi_target = strtolower(trim(preg_replace('/^tim\s+/i', '', $nama_divisi)));
$clean_divisi_user   = strtolower(trim(preg_replace('/^tim\s+/i', '', $divisi_user)));

$bisa_kelola = false;
if ($role_user === 'admin') {
    $bisa_kelola = true;
} elseif ($role_user === 'ketua_tim') {
    $bisa_kelola = (!empty($clean_divisi_user) && $clean_divisi_user === $clean_divisi_target);
}

// Pemetaan tabel database agar seluruh layanan bawaan muncul
$tabel_khusus = [
    'keuangan'                 => 'layanan_keuangan',
    'kepegawaian'              => 'layanan_kepegawaian',
    'administrasi-persuratan'  => 'layanan_administrasi',
    'pengadaan'                => 'layanan_pengadaan',
    'sakip'                    => 'layanan_sakip',
    'rbzi'                     => 'layanan_rb_zi',
    'ppid'                     => 'layanan_ppid',
    'statistik-sektoral'       => 'layanan_sektoral',
    'tim-ipds'                 => 'layanan_ipds',
    'diseminasi-dokumentasi'   => 'layanan_diseminasi'
];

$tabel_target = $tabel_khusus[$slug] ?? 'layanan_dinamis';

// 2. PROSES CRUD LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$bisa_kelola) {
        $pesan_error = "Akses ditolak! Anda tidak memiliki izin untuk mengelola modul divisi ini.";
    } else {
        // A. Tambah Layanan
        if (isset($_POST['tambah_layanan'])) {
            $nama_layanan = trim($_POST['nama_layanan'] ?? '');
            $url_link     = trim($_POST['url_link'] ?? '');
            $deskripsi    = trim($_POST['deskripsi'] ?? '');
            $kategori     = trim($_POST['kategori'] ?? 'Aplikasi');

            if (!empty($nama_layanan) && !empty($url_link)) {
                $payload = [
                    'nama_layanan' => $nama_layanan,
                    'url_link'     => $url_link,
                    'deskripsi'    => $deskripsi,
                    'kategori'     => $kategori,
                    'urutan'       => 99,
                    'is_deleted'   => 0
                ];
                
                if ($tabel_target === 'layanan_dinamis') {
                    $payload['slug_modul'] = $slug;
                }

                $res = supabase_request('/rest/v1/' . $tabel_target, 'POST', $payload);
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    $pesan_sukses = "Layanan berhasil ditambahkan!";
                } else {
                    $pesan_error = "Gagal menambah layanan: " . ($res['error'] ?: 'API Error ' . $res['status']);
                }
            } else {
                $pesan_error = "Nama layanan dan URL tautan wajib diisi!";
            }
        }

        // B. Edit Layanan
        if (isset($_POST['edit_layanan'])) {
            $id           = intval($_POST['id'] ?? 0);
            $nama_layanan = trim($_POST['nama_layanan'] ?? '');
            $url_link     = trim($_POST['url_link'] ?? '');
            $deskripsi    = trim($_POST['deskripsi'] ?? '');
            $kategori     = trim($_POST['kategori'] ?? 'Aplikasi');

            if ($id > 0 && !empty($nama_layanan) && !empty($url_link)) {
                $res = supabase_request('/rest/v1/' . $tabel_target . '?id=eq.' . $id, 'PATCH', [
                    'nama_layanan' => $nama_layanan,
                    'url_link'     => $url_link,
                    'deskripsi'    => $deskripsi,
                    'kategori'     => $kategori
                ]);
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    $pesan_sukses = "Perubahan layanan berhasil disimpan!";
                } else {
                    $pesan_error = "Gagal memperbarui layanan.";
                }
            }
        }

        // C. Soft Delete Layanan
        if (isset($_POST['hapus_layanan'])) {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $res = supabase_request('/rest/v1/' . $tabel_target . '?id=eq.' . $id, 'PATCH', ['is_deleted' => 1]);
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    $pesan_sukses = "Layanan dipindahkan ke Pemulihan.";
                }
            }
        }

        // D. Restore Layanan
        if (isset($_POST['restore_layanan'])) {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $res = supabase_request('/rest/v1/' . $tabel_target . '?id=eq.' . $id, 'PATCH', ['is_deleted' => 0]);
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    $pesan_sukses = "Layanan berhasil dipulihkan kembali!";
                }
            }
        }

        // E. Hapus Permanen Layanan
        if (isset($_POST['hapus_permanen_layanan'])) {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $res = supabase_request('/rest/v1/' . $tabel_target . '?id=eq.' . $id, 'DELETE');
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    $pesan_sukses = "Layanan berhasil dihapus secara permanen.";
                }
            }
        }
    }
}

// 3. Ambil data layanan AKTIF dari tabel target
$endpoint_aktif = ($tabel_target === 'layanan_dinamis')
    ? '/rest/v1/' . $tabel_target . '?slug_modul=eq.' . urlencode($slug) . '&is_deleted=eq.0&order=urutan.asc'
    : '/rest/v1/' . $tabel_target . '?is_deleted=eq.0&order=urutan.asc';

$query_layanan = supabase_request($endpoint_aktif);
$daftar_layanan = (!empty($query_layanan['data']) && is_array($query_layanan['data'])) ? $query_layanan['data'] : [];

// 4. Ambil data layanan TERHAPUS untuk menu pemulihan (hanya untuk pengelola)
$daftar_sampah = [];
if ($bisa_kelola) {
    $endpoint_sampah = ($tabel_target === 'layanan_dinamis')
        ? '/rest/v1/' . $tabel_target . '?slug_modul=eq.' . urlencode($slug) . '&is_deleted=eq.1&order=id.desc'
        : '/rest/v1/' . $tabel_target . '?is_deleted=eq.1&order=id.desc';

    $query_sampah = supabase_request($endpoint_sampah);
    $daftar_sampah = (!empty($query_sampah['data']) && is_array($query_sampah['data'])) ? $query_sampah['data'] : [];
}
$jumlah_sampah = count($daftar_sampah);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($label_tim_header); ?> - Tabon Gawean</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Sisi Kiri: Logo Bersih & Nama Tim -->
      <div class="flex items-center gap-4">
        <a href="index.php" class="flex items-center hover:opacity-90 transition">
          <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-10 sm:h-12 w-auto object-contain drop-shadow-sm">
        </a>
        <span class="h-7 w-px bg-slate-200 hidden sm:block"></span>
        <span class="text-sm sm:text-base font-bold text-slate-800 hidden sm:block">
          <?= htmlspecialchars($label_tim_header); ?>
        </span>
      </div>

      <!-- Sisi Kanan: Kembali ke Beranda & Tombol Pemulihan (Khusus Pengelola) -->
      <div class="flex items-center gap-3">
        <a 
          href="index.php" 
          class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200 shadow-sm"
        >
          <i data-lucide="arrow-left" class="w-4 h-4 text-slate-500"></i>
          <span class="hidden sm:inline">Kembali ke Beranda</span>
        </a>

        <?php if ($bisa_kelola): ?>
          <button 
            type="button" 
            onclick="bukaModalSampahLayanan()" 
            title="Buka menu pemulihan layanan terhapus"
            class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200 shadow-sm"
          >
            <i data-lucide="archive-restore" class="w-4 h-4 text-slate-500"></i>
            <span class="hidden sm:inline">Pemulihan</span>
            <?php if ($jumlah_sampah > 0): ?>
              <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-rose-500 text-white leading-none">
                <?= $jumlah_sampah; ?>
              </span>
            <?php endif; ?>
          </button>
        <?php endif; ?>
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

    <!-- Banner Info Modul Tim -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 mb-8 shadow-sm flex items-start gap-5">
      <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-sm">
        <i data-lucide="<?= htmlspecialchars($ikon_divisi); ?>" class="w-7 h-7"></i>
      </div>
      <div>
        <div class="flex items-center gap-2 mb-2">
          <span class="inline-block px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[11px] font-bold uppercase tracking-wider">
            Modul Operasional Tim
          </span>
          <?php if (!$bisa_kelola): ?>
            <span class="inline-block px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
            Read-Only
            </span>
          <?php endif; ?>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 leading-tight">
          Layanan <?= htmlspecialchars($nama_divisi); ?>
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
          <?= htmlspecialchars($deskripsi_divisi ?: 'Portal berkas kerja dan modul aplikasi internal tim.'); ?>
        </p>
      </div>
    </div>

    <!-- Grid Daftar Layanan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($daftar_layanan as $l): ?>
        <?php
          $raw_url = trim($l['url_link'] ?? '');
          $link_tujuan = (!empty($raw_url) && !preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col justify-between hover:border-blue-400 hover:shadow-md transition">
          <div>
            <div class="flex items-center justify-between mb-4">
              <span class="text-[11px] font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                <?= htmlspecialchars($l['kategori'] ?: 'Aplikasi'); ?>
              </span>

              <?php if ($bisa_kelola): ?>
                <!-- Tombol Edit & Hapus (Hanya muncul jika berhak kelola) -->
                <div class="flex items-center gap-1">
                  <button 
                    type="button" 
                    onclick='bukaModalEditLayanan(<?= json_encode($l); ?>)'
                    title="Edit Layanan"
                    class="w-7 h-7 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition"
                  >
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                  </button>

                  <form method="POST" action="divisi.php?slug=<?= urlencode($slug); ?>" onsubmit="return confirm('Pindahkan layanan ini ke Pemulihan?');" class="inline">
                    <input type="hidden" name="id" value="<?= $l['id']; ?>">
                    <button 
                      type="submit" 
                      name="hapus_layanan" 
                      title="Hapus Layanan"
                      class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition"
                    >
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>

            <h4 class="font-bold text-slate-900 text-base leading-snug">
              <?= htmlspecialchars($l['nama_layanan']); ?>
            </h4>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed line-clamp-3">
              <?= htmlspecialchars($l['deskripsi']); ?>
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 transition">
              <span>Buka Tautan</span>
              <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>

            <a href="detail.php?tabel=<?= urlencode($tabel_target); ?>&id=<?= $l['id']; ?>" class="text-[11px] text-slate-400 hover:text-slate-600 transition">
              Detail
            </a>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if ($bisa_kelola): ?>
        <!-- Tombol Tambah Layanan Baru -->
        <button 
          type="button" 
          onclick="bukaModalTambahLayanan()" 
          class="group min-h-[200px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 hover:bg-blue-50/50 p-6 flex flex-col items-center justify-center text-center transition"
        >
          <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition shadow-sm mb-3">
            <i data-lucide="plus" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-slate-700 group-hover:text-blue-600 text-sm transition">
            Tambah Layanan Baru
          </span>
          <p class="text-xs text-slate-400 mt-1">
            Tambahkan tautan atau berkas kerja <?= htmlspecialchars($label_tim_header); ?>
          </p>
        </button>
      <?php endif; ?>
    </div>

  </main>

  <?php if ($bisa_kelola): ?>
    <!-- MODAL 1: TAMBAH LAYANAN -->
    <div id="modalTambahLayanan" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <i data-lucide="plus-circle" class="w-5 h-5"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-base sm:text-lg">Tambah Layanan Baru</h3>
          </div>
          <button type="button" onclick="tutupModalTambahLayanan()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <form method="POST" action="divisi.php?slug=<?= urlencode($slug); ?>" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Layanan / Aplikasi *</label>
            <input type="text" name="nama_layanan" required placeholder="Contoh: Formulir Kinerja" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">URL / Tautan Link *</label>
            <input type="text" name="url_link" required placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori</label>
            <input type="text" name="kategori" value="Aplikasi" placeholder="Aplikasi / Panduan / Berkas" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
            <textarea name="deskripsi" rows="3" placeholder="Jelaskan kegunaan tautan ini..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="button" onclick="tutupModalTambahLayanan()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
              Batal
            </button>
            <button type="submit" name="tambah_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
              <i data-lucide="save" class="w-4 h-4"></i>
              <span>Simpan Layanan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: EDIT LAYANAN -->
    <div id="modalEditLayanan" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <i data-lucide="pencil" class="w-5 h-5"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-base sm:text-lg">Edit Layanan</h3>
          </div>
          <button type="button" onclick="tutupModalEditLayanan()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <form method="POST" action="divisi.php?slug=<?= urlencode($slug); ?>" class="space-y-4">
          <input type="hidden" name="id" id="edit_layanan_id">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Layanan / Aplikasi *</label>
            <input type="text" name="nama_layanan" id="edit_layanan_nama" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">URL / Tautan Link *</label>
            <input type="text" name="url_link" id="edit_layanan_url" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori</label>
            <input type="text" name="kategori" id="edit_layanan_kategori" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
            <textarea name="deskripsi" id="edit_layanan_deskripsi" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="button" onclick="tutupModalEditLayanan()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
              Batal
            </button>
            <button type="submit" name="edit_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
              <i data-lucide="check" class="w-4 h-4"></i>
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: PEMULIHAN LAYANAN -->
    <div id="modalSampahLayanan" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-8 flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
              <i data-lucide="archive-restore" class="w-5 h-5"></i>
            </div>
            <div>
              <h3 class="font-bold text-slate-900 text-base sm:text-lg">Pemulihan Layanan</h3>
              <p class="text-xs text-slate-400">Daftar tautan/layanan yang dihapus dari modul ini</p>
            </div>
          </div>
          <button type="button" onclick="tutupModalSampahLayanan()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <div class="overflow-y-auto pr-1 flex-1 space-y-3">
          <?php if ($jumlah_sampah === 0): ?>
            <div class="text-center py-10">
              <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                <i data-lucide="check" class="w-6 h-6"></i>
              </div>
              <p class="text-xs font-semibold text-slate-500">Tidak ada layanan yang terhapus.</p>
            </div>
          <?php else: ?>
            <?php foreach ($daftar_sampah as $sl): ?>
              <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($sl['nama_layanan']); ?></h4>
                  <p class="text-xs text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($sl['deskripsi'] ?: $sl['url_link']); ?></p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <form method="POST" action="divisi.php?slug=<?= urlencode($slug); ?>" class="inline">
                    <input type="hidden" name="id" value="<?= $sl['id']; ?>">
                    <button 
                      type="submit" 
                      name="restore_layanan" 
                      title="Pulihkan Layanan Ini"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition"
                    >
                      <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                      <span>Pulihkan</span>
                    </button>
                  </form>

                  <form method="POST" action="divisi.php?slug=<?= urlencode($slug); ?>" onsubmit="return confirm('Peringatan: Layanan ini akan dihapus secara permanen. Lanjutkan?');" class="inline">
                    <input type="hidden" name="id" value="<?= $sl['id']; ?>">
                    <button 
                      type="submit" 
                      name="hapus_permanen_layanan" 
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
          <button type="button" onclick="tutupModalSampahLayanan()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-500 mt-10">
    <p>© 2026 Tabon Gawean — BPS Kota Yogyakarta</p>
  </footer>

  <script>
    lucide.createIcons();

    // Kontrol Modal Tambah Layanan
    function bukaModalTambahLayanan() {
      const el = document.getElementById('modalTambahLayanan');
      if (el) el.classList.remove('hidden');
    }
    function tutupModalTambahLayanan() {
      const el = document.getElementById('modalTambahLayanan');
      if (el) el.classList.add('hidden');
    }

    // Kontrol Modal Edit Layanan
    function bukaModalEditLayanan(data) {
      const el = document.getElementById('modalEditLayanan');
      if (!el) return;
      document.getElementById('edit_layanan_id').value = data.id || '';
      document.getElementById('edit_layanan_nama').value = data.nama_layanan || '';
      document.getElementById('edit_layanan_url').value = data.url_link || '';
      document.getElementById('edit_layanan_kategori').value = data.kategori || 'Aplikasi';
      document.getElementById('edit_layanan_deskripsi').value = data.deskripsi || '';
      el.classList.remove('hidden');
    }
    function tutupModalEditLayanan() {
      const el = document.getElementById('modalEditLayanan');
      if (el) el.classList.add('hidden');
    }

    // Kontrol Modal Pemulihan Layanan
    function bukaModalSampahLayanan() {
      const el = document.getElementById('modalSampahLayanan');
      if (el) el.classList.remove('hidden');
    }
    function tutupModalSampahLayanan() {
      const el = document.getElementById('modalSampahLayanan');
      if (el) el.classList.add('hidden');
    }
  </script>
</body>
</html>