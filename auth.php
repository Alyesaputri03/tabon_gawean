<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

// Tentukan password bersama untuk seluruh akun di sini
define('SHARED_PASSWORD', 'bps12345'); 

/**
 * Cek apakah pengguna sudah login. Jika belum, lempar ke login.php
 */
function wajib_login() {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Cek hak akses role
 */
function cek_role($roles = []) {
    wajib_login();
    $role_user = $_SESSION['user']['role'] ?? '';
    if (!in_array($role_user, (array)$roles)) {
        echo "<script>alert('Anda tidak memiliki izin untuk halaman ini!'); window.location.href='index.php';</script>";
        exit;
    }
}

/**
 * Ambil data user yang sedang aktif
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Cek apakah user yang login punya hak akses kelola (CRUD) pada divisi tertentu
 * @param string $nama_divisi_halaman Nama divisi halaman yang sedang dibuka
 * @return bool True jika boleh kelola, False jika hanya boleh lihat (Read-Only)
 */
function boleh_kelola_divisi($nama_divisi_halaman) {
    $user = current_user();
    if (!$user) return false;

    $role = $user['role'] ?? 'pegawai';

    // 1. Admin selalu punya akses penuh ke semua divisi
    if ($role === 'admin') {
        return true;
    }

    // 2. Pegawai/Staf biasa hanya boleh melihat (Read-Only)
    if ($role === 'pegawai') {
        return false;
    }

    // 3. Ketua Tim: Cocokkan divisi user dengan divisi halaman yang dibuka
    if ($role === 'ketua_tim') {
        $divisi_user = trim($user['divisi'] ?? '');
        $divisi_target = trim($nama_divisi_halaman ?? '');

        // Bersihkan kata 'Tim' dan spasi agar perbandingan akurat (misal: "Tim Keuangan" vs "Keuangan")
        $bersih_user   = strtolower(preg_replace('/^tim\s+/i', '', $divisi_user));
        $bersih_target = strtolower(preg_replace('/^tim\s+/i', '', $divisi_target));

        return (!empty($bersih_user) && $bersih_user === $bersih_target);
    }

    return false;
}