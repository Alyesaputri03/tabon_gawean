<?php
require_once 'koneksi.php';

$tabel_supabase = 'layanan_administrasi';
$nama_divisi    = 'Administrasi & Persuratan';
$ikon_default   = 'mail';
$pesan_sukses   = '';
$pesan_error    = '';

// 1. PROSES TAMBAH LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_layanan'])) {
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $url_link     = trim($_POST['url_link'] ?? '');

    if (!empty($nama_layanan) && !empty($url_link)) {
        $payload = [
            'nama_layanan' => $nama_layanan,
            'deskripsi'    => $deskripsi,
            'kategori'     => $kategori,
            'ikon'         => $ikon_default,
            'url_link'     => $url_link,
            'urutan'       => 99,
            'is_deleted'   => 0
        ];

        $res = supabase_request('/rest/v1/' . $tabel_supabase, 'POST', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan administrasi & persuratan berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menyimpan: " . ($res['error'] ?: 'Terjadi kesalahan sistem');
        }
    } else {
        $pesan_error = "Nama layanan dan URL tautan wajib diisi!";
    }
}

// 2. PROSES EDIT LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_layanan'])) {
    $id           = intval($_POST['id'] ?? 0);
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $url_link     = trim($_POST['url_link'] ?? '');

    if ($id > 0 && !empty($nama_layanan) && !empty($url_link)) {
        $res = supabase_request('/rest/v1/' . $tabel_supabase . '?id=eq.' . $id, 'PATCH', [
            'nama_layanan' => $nama_layanan,
            'deskripsi'    => $deskripsi,
            'kategori'     => $kategori,
            'url_link'     => $url_link
        ]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Perubahan layanan administrasi berhasil disimpan!";
        } else {
            $pesan_error = "Gagal memperbarui data: " . ($res['error'] ?: 'Terjadi kesalahan');
        }
    } else {
        $pesan_error = "Data edit tidak valid!";
    }
}

// 3. PROSES HAPUS SEMENTARA (SOFT DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_layanan'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/' . $tabel_supabase . '?id=eq.' . $id, 'PATCH', ['is_deleted' => 1]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan berhasil dipindahkan ke Pemulihan $nama_divisi.";
        }
    }
}

// 4. PROSES PULIHKAN (RESTORE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_layanan'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/' . $tabel_supabase . '?id=eq.' . $id, 'PATCH', ['is_deleted' => 0]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan berhasil dipulihkan kembali!";
        }
    }
}

// 5. PROSES HAPUS PERMANEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_permanen'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/' . $tabel_supabase . '?id=eq.' . $id, 'DELETE');
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan berhasil dihapus secara permanen dari basis data.";
        }
    }
}

// Ambil data aktif divisi Administrasi & Persuratan
$res_aktif = supabase_request('/rest/v1/' . $tabel_supabase . '?select=*&is_deleted=eq.0&order=id.asc');
$data_aktif = is_array($res_aktif['data']) ? $res_aktif['data'] : [];

// Ambil data sampah khusus divisi Administrasi & Persuratan
$res_sampah = supabase_request('/rest/v1/' . $tabel_supabase . '?select=*&is_deleted=eq.1&order=id.desc');
$data_sampah = is_array($res_sampah['data']) ? $res_sampah['data'] : [];
$jumlah_sampah = count($data_sampah);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layanan <?= $nama_divisi; ?> - Tabon Gawean</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Sisi Kiri: Logo Besar & Nama Tim -->
      <div class="flex items-center gap-5">
        <a href="index.php" class="flex items-center hover:opacity-90 transition">
          <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-10 sm:h-30 w-auto object-contain drop-shadow-sm">
        </a>
        <span class="h-8 w-px bg-slate-300 hidden sm:block"></span>
        <span class="text-base sm:text-lg font-extrabold text-slate-800 hidden sm:block">
          Tim <?= htmlspecialchars($nama_divisi); ?>
        </span>
      </div>

      <!-- Kanan: Tombol Kembali & Pemulihan -->
      <div class="flex items-center gap-2.5">
        <a 
          href="index.php" 
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-sky-50 hover:text-sky-700 transition border border-slate-200/80 shadow-sm"
        >
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          <span>Kembali ke Beranda</span>
        </a>

        <button 
          type="button" 
          onclick="bukaModalSampah()" 
          title="Buka menu pemulihan berkas persuratan terhapus"
          class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 transition border border-slate-200/80 shadow-sm"
        >
          <i data-lucide="archive-restore" class="w-4 h-4 text-slate-500"></i>
          <span>Pemulihan</span>
          <?php if ($jumlah_sampah > 0): ?>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white leading-none">
              <?= $jumlah_sampah; ?>
            </span>
          <?php endif; ?>
        </button>
      </div>

    </div>
  </header>

  <!-- Konten Utama -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    
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

    <!-- Banner Divisi Administrasi & Persuratan -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm mb-8">
      <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
          <i data-lucide="mail" class="w-7 h-7"></i>
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Tata Kelola Naskah Dinas & Sarana</span>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Layanan Administrasi & Persuratan</h1>
          <p class="text-sm text-slate-600 mt-2 max-w-3xl">
            Akses registrasi daftar hadir, penomoran surat kedinasan, SRIKANDI, ETTD, peminjaman ruang rapat, serta format naskah dinas resmi BPS Kota Yogyakarta.
          </p>
        </div>
      </div>
    </div>

    <!-- Grid Menu Layanan Aktif -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($data_aktif as $item): ?>
        <?php
          $raw_url = trim($item['url_link'] ?? '');
          $link_tujuan = (!empty($raw_url) && !preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
        ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 flex flex-col justify-between hover:border-sky-400 hover:shadow-md transition relative group">
          <div>
            <!-- Header Kartu: Kategori & Aksi -->
            <div class="flex items-center justify-between gap-2 mb-4">
              <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 truncate max-w-[160px]">
                <?= htmlspecialchars($item['kategori'] ?: $nama_divisi); ?>
              </span>

              <div class="flex items-center gap-1">
                <!-- Tombol Edit -->
                <button 
                  type="button" 
                  onclick='bukaModalEdit(<?= json_encode($item); ?>)' 
                  title="Edit Layanan"
                  class="w-7 h-7 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 flex items-center justify-center transition"
                >
                  <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>

                <!-- Tombol Hapus (Soft Delete) -->
                <form method="POST" action="" onsubmit="return confirm('Pindahkan layanan persuratan ini ke Pemulihan?');" class="inline">
                  <input type="hidden" name="id" value="<?= $item['id']; ?>">
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
            </div>

            <h3 class="font-bold text-slate-900 text-lg leading-snug">
              <?= htmlspecialchars($item['nama_layanan']); ?>
            </h3>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
              <?= htmlspecialchars($item['deskripsi']); ?>
            </p>
          </div>

          <div class="mt-8 pt-4 border-t border-slate-100">
            <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-600 hover:text-sky-700 transition">
              <span>Buka Tautan</span>
              <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Tombol + Tambah Layanan Baru -->
      <button 
        type="button" 
        onclick="bukaModalTambah()" 
        class="group min-h-[220px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-sky-500 hover:bg-sky-50/50 p-6 flex flex-col items-center justify-center text-center transition"
      >
        <div class="w-12 h-12 rounded-full bg-sky-100 text-sky-600 group-hover:bg-sky-600 group-hover:text-white flex items-center justify-center transition shadow-sm mb-3">
          <i data-lucide="plus" class="w-6 h-6"></i>
        </div>
        <span class="font-bold text-slate-700 group-hover:text-sky-600 text-sm sm:text-base transition">
          Tambah Layanan Baru
        </span>
        <p class="text-xs text-slate-400 mt-1">Tambahkan tautan persuratan atau berkas administrasi dinas baru</p>
      </button>
    </div>
  </main>

  <!-- MODAL 1: TAMBAH LAYANAN -->
  <div id="modalTambah" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base sm:text-lg">Tambah Menu <?= $nama_divisi; ?></h3>
        </div>
        <button type="button" onclick="tutupModalTambah()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Layanan *</label>
          <input type="text" name="nama_layanan" required placeholder="Contoh: Format Surat Tugas Dinas 2026" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori / Badge</label>
          <input type="text" name="kategori" placeholder="Contoh: Naskah Dinas / Ruang Rapat" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tautan URL *</label>
          <input type="url" name="url_link" required placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" rows="2" placeholder="Jelaskan ringkasan fungsi tautan administrasi ini..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalTambah()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Batal</button>
          <button type="submit" name="tambah_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-500/20 transition flex items-center gap-1.5">
            <i data-lucide="save" class="w-4 h-4"></i>
            <span>Simpan Layanan</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 2: EDIT LAYANAN -->
  <div id="modalEdit" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
            <i data-lucide="pencil" class="w-5 h-5"></i>
          </div>
          <h3 class="font-bold text-slate-900 text-base sm:text-lg">Edit Layanan Administrasi</h3>
        </div>
        <button type="button" onclick="tutupModalEdit()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="id" id="edit_id">

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Layanan *</label>
          <input type="text" name="nama_layanan" id="edit_nama" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kategori / Badge</label>
          <input type="text" name="kategori" id="edit_kategori" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tautan URL *</label>
          <input type="url" name="url_link" id="edit_url" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" id="edit_deskripsi" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalEdit()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Batal</button>
          <button type="submit" name="edit_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-700 shadow-md shadow-sky-500/20 transition flex items-center gap-1.5">
            <i data-lucide="check" class="w-4 h-4"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 3: PEMULIHAN KHUSUS ADMINISTRASI -->
  <div id="modalSampah" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-8 flex flex-col max-h-[85vh]">
      
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
            <i data-lucide="archive-restore" class="w-5 h-5"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-base sm:text-lg">Pemulihan Layanan Administrasi</h3>
            <p class="text-xs text-slate-400">Daftar layanan terhapus yang berasal dari divisi <?= $nama_divisi; ?></p>
          </div>
        </div>
        <button type="button" onclick="tutupModalSampah()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>

      <!-- List Sampah -->
      <div class="overflow-y-auto pr-1 flex-1 space-y-3">
        <?php if ($jumlah_sampah === 0): ?>
          <div class="text-center py-10">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
              <i data-lucide="check" class="w-6 h-6"></i>
            </div>
            <p class="text-xs font-semibold text-slate-500">Tidak ada layanan yang terhapus di divisi ini.</p>
          </div>
        <?php else: ?>
          <?php foreach ($data_sampah as $s): ?>
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-600">
                  <?= htmlspecialchars($s['kategori'] ?: $nama_divisi); ?>
                </span>
                <h4 class="font-bold text-slate-800 text-sm mt-1"><?= htmlspecialchars($s['nama_layanan']); ?></h4>
                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($s['deskripsi']); ?></p>
              </div>

              <!-- Tombol Pulihkan & Hapus Permanen -->
              <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="" class="inline">
                  <input type="hidden" name="id" value="<?= $s['id']; ?>">
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

                <form method="POST" action="" onsubmit="return confirm('Peringatan: Layanan ini akan dihapus secara permanen dari Supabase. Lanjutkan?');" class="inline">
                  <input type="hidden" name="id" value="<?= $s['id']; ?>">
                  <button 
                    type="submit" 
                    name="hapus_permanen" 
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
        <button type="button" onclick="tutupModalSampah()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
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

    // Modal Tambah
    function bukaModalTambah() { 
      document.getElementById('modalTambah').classList.remove('hidden'); 
    }
    function tutupModalTambah() { 
      document.getElementById('modalTambah').classList.add('hidden'); 
    }

    // Modal Edit
    function bukaModalEdit(data) {
      document.getElementById('edit_id').value = data.id || '';
      document.getElementById('edit_nama').value = data.nama_layanan || '';
      document.getElementById('edit_kategori').value = data.kategori || '';
      document.getElementById('edit_url').value = data.url_link || '';
      document.getElementById('edit_deskripsi').value = data.deskripsi || '';
      document.getElementById('modalEdit').classList.remove('hidden');
    }
    function tutupModalEdit() { 
      document.getElementById('modalEdit').classList.add('hidden'); 
    }

    // Modal Sampah
    function bukaModalSampah() {
      document.getElementById('modalSampah').classList.remove('hidden');
    }
    function tutupModalSampah() {
      document.getElementById('modalSampah').classList.add('hidden');
    }
  </script>
</body>
</html>