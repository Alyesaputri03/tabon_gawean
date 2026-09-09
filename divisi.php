<?php
require_once 'koneksi.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// 1. Ambil data modul/tim dari Supabase
$res_modul = supabase_request('/rest/v1/modul_layanan?select=*&slug=eq.' . urlencode($slug) . '&limit=1');
$modul = !empty($res_modul['data'][0]) && is_array($res_modul['data'][0]) ? $res_modul['data'][0] : null;

if (!$modul) {
    die("
    <div style='text-align:center; padding:60px 20px; font-family:sans-serif;'>
      <h2 style='color:#1e293b; margin-bottom:8px;'>Tim Tidak Ditemukan</h2>
      <p style='color:#64748b; font-size:14px; margin-bottom:20px;'>Modul tim yang Anda cari tidak terdaftar di database.</p>
      <a href='index.php' style='display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:12px; font-size:13px; font-weight:600;'>Kembali ke Beranda</a>
    </div>");
}

$nama_divisi = $modul['nama_modul'];
$ikon_divisi = $modul['ikon'] ?: 'folder';

$pesan_sukses = '';
$pesan_error  = '';

// 2. PROSES TAMBAH LAYANAN (Otomatis ke tabel layanan_dinamis)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_layanan'])) {
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $url_link     = trim($_POST['url_link'] ?? '');

    if (!empty($nama_layanan) && !empty($url_link)) {
        $payload = [
            'slug_modul'   => $slug,
            'nama_layanan' => $nama_layanan,
            'deskripsi'    => $deskripsi,
            'kategori'     => $kategori,
            'ikon'         => $ikon_divisi,
            'url_link'     => $url_link,
            'urutan'       => 99,
            'is_deleted'   => 0
        ];

        $res = supabase_request('/rest/v1/layanan_dinamis', 'POST', $payload);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan baru berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menyimpan layanan: " . ($res['error'] ?: 'Terjadi kesalahan sistem');
        }
    } else {
        $pesan_error = "Nama layanan dan URL tautan wajib diisi!";
    }
}

// 3. PROSES EDIT LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_layanan'])) {
    $id           = intval($_POST['id'] ?? 0);
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $url_link     = trim($_POST['url_link'] ?? '');

    if ($id > 0 && !empty($nama_layanan) && !empty($url_link)) {
        $res = supabase_request('/rest/v1/layanan_dinamis?id=eq.' . $id, 'PATCH', [
            'nama_layanan' => $nama_layanan,
            'deskripsi'    => $deskripsi,
            'kategori'     => $kategori,
            'url_link'     => $url_link
        ]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Perubahan layanan berhasil disimpan!";
        } else {
            $pesan_error = "Gagal memperbarui data: " . $res['error'];
        }
    } else {
        $pesan_error = "Data edit tidak valid!";
    }
}

// 4. PROSES HAPUS SEMENTARA (SOFT DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_layanan'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/layanan_dinamis?id=eq.' . $id, 'PATCH', ['is_deleted' => 1]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan dipindahkan ke menu Pemulihan.";
        }
    }
}

// 5. PROSES RESTORE LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_layanan'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/layanan_dinamis?id=eq.' . $id, 'PATCH', ['is_deleted' => 0]);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan berhasil dipulihkan kembali!";
        }
    }
}

// 6. PROSES HAPUS PERMANEN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_permanen'])) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $res = supabase_request('/rest/v1/layanan_dinamis?id=eq.' . $id, 'DELETE');
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $pesan_sukses = "Layanan dihapus secara permanen.";
        }
    }
}

// Ambil data aktif khusus tim ini
$res_aktif = supabase_request('/rest/v1/layanan_dinamis?select=*&slug_modul=eq.' . urlencode($slug) . '&is_deleted=eq.0&order=id.asc');
$data_aktif = [];
if (!empty($res_aktif['data']) && is_array($res_aktif['data'])) {
    foreach ($res_aktif['data'] as $it) {
        if (is_array($it)) {
            $data_aktif[] = $it;
        }
    }
}

// Ambil data sampah khusus tim ini
$res_sampah = supabase_request('/rest/v1/layanan_dinamis?select=*&slug_modul=eq.' . urlencode($slug) . '&is_deleted=eq.1&order=id.desc');
$data_sampah = [];
if (!empty($res_sampah['data']) && is_array($res_sampah['data'])) {
    foreach ($res_sampah['data'] as $sp) {
        if (is_array($sp)) {
            $data_sampah[] = $sp;
        }
    }
}
$jumlah_sampah = count($data_sampah);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layanan <?= htmlspecialchars($nama_divisi); ?> - Tabon Gawean</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-between">

  <!-- Header Navigasi -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <a href="index.php" class="flex items-center hover:opacity-90 transition -translate-y-1 sm:-translate-y-1.5">
          <img src="assets/logo.png?v=4" alt="Tabon Gawean" class="h-16 sm:h-20 w-auto object-contain">
        </a>
        <span class="h-6 w-px bg-slate-200 hidden sm:block"></span>
        <span class="text-sm font-bold text-slate-800 hidden sm:block">Tim <?= htmlspecialchars($nama_divisi); ?></span>
      </div>

      <div class="flex items-center gap-2.5">
        <a 
          href="index.php" 
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-blue-50 hover:text-blue-700 transition border border-slate-200/80 shadow-sm"
        >
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
          <span>Kembali ke Beranda</span>
        </a>

        <button 
          type="button" 
          onclick="bukaModalSampah()" 
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

    <!-- Banner Tim Dinamis -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm mb-8">
      <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
          <i data-lucide="<?= htmlspecialchars($ikon_divisi); ?>" class="w-7 h-7"></i>
        </div>
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Modul Operasional Tim</span>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Layanan <?= htmlspecialchars($nama_divisi); ?></h1>
          <p class="text-sm text-slate-600 mt-2 max-w-3xl">
            <?= htmlspecialchars($modul['deskripsi'] ?: 'Pusat akses layanan kerja, aplikasi kedinasan, dan dokumen operasional tim.'); ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Grid Kartu Layanan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($data_aktif as $item): ?>
        <?php
          $raw_url = trim($item['url_link'] ?? '');
          $link_tujuan = (!empty($raw_url) && !preg_match("~^(?:f|ht)tps?://~i", $raw_url)) ? "https://" . $raw_url : $raw_url;
        ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 flex flex-col justify-between hover:border-blue-400 hover:shadow-md transition relative group">
          <div>
            <div class="flex items-center justify-between gap-2 mb-4">
              <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 truncate max-w-[160px]">
                <?= htmlspecialchars($item['kategori'] ?: $nama_divisi); ?>
              </span>
              <div class="flex items-center gap-1">
                <button 
                  type="button" 
                  onclick='bukaModalEdit(<?= json_encode($item); ?>)' 
                  class="w-7 h-7 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition"
                >
                  <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>
                <form method="POST" action="" onsubmit="return confirm('Pindahkan layanan ini ke Pemulihan?');" class="inline">
                  <input type="hidden" name="id" value="<?= $item['id']; ?>">
                  <button type="submit" name="hapus_layanan" class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition">
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

          <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
            <a href="<?= htmlspecialchars($link_tujuan); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
              <span>Buka Tautan</span>
              <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
            </a>

            <a href="detail.php?tabel=layanan_dinamis&id=<?= $item['id']; ?>" class="text-[11px] text-slate-400 hover:text-slate-600">
              Rincian &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Tombol Tambah Layanan Baru -->
      <button 
        type="button" 
        onclick="bukaModalTambah()" 
        class="group min-h-[220px] rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 hover:bg-blue-50/50 p-6 flex flex-col items-center justify-center text-center transition"
      >
        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition shadow-sm mb-3">
          <i data-lucide="plus" class="w-6 h-6"></i>
        </div>
        <span class="font-bold text-slate-700 group-hover:text-blue-600 text-sm sm:text-base transition">
          Tambah Layanan Baru
        </span>
        <p class="text-xs text-slate-400 mt-1">Tambahkan tautan atau berkas kerja tim <?= htmlspecialchars($nama_divisi); ?></p>
      </button>
    </div>
  </main>

  <!-- MODAL TAMBAH -->
  <div id="modalTambah" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <h3 class="font-bold text-slate-900 text-base sm:text-lg">Tambah Menu <?= htmlspecialchars($nama_divisi); ?></h3>
        <button type="button" onclick="tutupModalTambah()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 flex items-center justify-center">&times;</button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Layanan *</label>
          <input type="text" name="nama_layanan" required placeholder="Nama tautan atau berkas..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori / Badge</label>
          <input type="text" name="kategori" placeholder="Contoh: Operasional / Format" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tautan URL *</label>
          <input type="url" name="url_link" required placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" rows="2" placeholder="Keterangan singkat layanan..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none focus:border-blue-500"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalTambah()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100">Batal</button>
          <button type="submit" name="tambah_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md">Simpan Layanan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL EDIT -->
  <div id="modalEdit" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <h3 class="font-bold text-slate-900 text-base sm:text-lg">Edit Layanan</h3>
        <button type="button" onclick="tutupModalEdit()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 flex items-center justify-center">&times;</button>
      </div>

      <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="id" id="edit_id">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Layanan *</label>
          <input type="text" name="nama_layanan" id="edit_nama" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kategori / Badge</label>
          <input type="text" name="kategori" id="edit_kategori" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tautan URL *</label>
          <input type="url" name="url_link" id="edit_url" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Deskripsi Singkat</label>
          <textarea name="deskripsi" id="edit_deskripsi" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm outline-none"></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" onclick="tutupModalEdit()" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100">Batal</button>
          <button type="submit" name="edit_layanan" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL PEMULIHAN -->
  <div id="modalSampah" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-2xl w-full p-6 sm:p-8 flex flex-col max-h-[85vh]">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
        <h3 class="font-bold text-slate-900 text-base sm:text-lg">Pemulihan Layanan Tim <?= htmlspecialchars($nama_divisi); ?></h3>
        <button type="button" onclick="tutupModalSampah()" class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 flex items-center justify-center">&times;</button>
      </div>

      <div class="overflow-y-auto pr-1 flex-1 space-y-3">
        <?php if ($jumlah_sampah === 0): ?>
          <p class="text-center py-10 text-xs text-slate-500">Tidak ada layanan yang terhapus di tim ini.</p>
        <?php else: ?>
          <?php foreach ($data_sampah as $s): ?>
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
              <div>
                <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($s['nama_layanan']); ?></h4>
                <p class="text-xs text-slate-500 line-clamp-1"><?= htmlspecialchars($s['deskripsi']); ?></p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <form method="POST" action="" class="inline">
                  <input type="hidden" name="id" value="<?= $s['id']; ?>">
                  <button type="submit" name="restore_layanan" class="px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-100">Pulihkan</button>
                </form>
                <form method="POST" action="" onsubmit="return confirm('Hapus permanen?');" class="inline">
                  <input type="hidden" name="id" value="<?= $s['id']; ?>">
                  <button type="submit" name="hapus_permanen" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50">Permanen</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="pt-4 border-t border-slate-100 mt-4 flex justify-end">
        <button type="button" onclick="tutupModalSampah()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100">Tutup</button>
      </div>
    </div>
  </div>

  <footer class="border-t border-slate-200 bg-white py-5 text-center text-xs text-slate-500 mt-10">
    <p>© 2026 Tabon Gawean — BPS Kota Yogyakarta</p>
  </footer>

  <script>
    lucide.createIcons();
    function bukaModalTambah() { document.getElementById('modalTambah').classList.remove('hidden'); }
    function tutupModalTambah() { document.getElementById('modalTambah').classList.add('hidden'); }
    function bukaModalEdit(data) {
      document.getElementById('edit_id').value = data.id || '';
      document.getElementById('edit_nama').value = data.nama_layanan || '';
      document.getElementById('edit_kategori').value = data.kategori || '';
      document.getElementById('edit_url').value = data.url_link || '';
      document.getElementById('edit_deskripsi').value = data.deskripsi || '';
      document.getElementById('modalEdit').classList.remove('hidden');
    }
    function tutupModalEdit() { document.getElementById('modalEdit').classList.add('hidden'); }
    function bukaModalSampah() { document.getElementById('modalSampah').classList.remove('hidden'); }
    function tutupModalSampah() { document.getElementById('modalSampah').classList.add('hidden'); }
  </script>
</body>
</html>