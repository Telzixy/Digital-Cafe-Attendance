<?php
session_start();
include '../koneksi.php'; // Mundur satu folder untuk mengambil koneksi.php

// Proteksi Halaman: Pastikan hanya Karyawan yang bisa memproses absen
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Karyawan') {
    header("Location: /cafe-aeter/index.php");
    exit;
}

$id_karyawan = $_SESSION['id_karyawan'];
$tanggal     = date('Y-m-d');
$jam_sekarang= date('H:i:s');
$bulan_tahun = date('Y-m'); // Mengambil format Tahun-Bulan berjalan (Contoh: 2026-05)

if (isset($_POST['image_data'])) {
    $img  = $_POST['image_data'];
    $aksi = $_POST['aksi']; // Menangkap nilai 'masuk' atau 'pulang'

    // 1. Dekode data string gambar base64 dari Webcam.js menjadi file gambar asli
    $img       = str_replace('data:image/jpeg;base64,', '', $img);
    $img       = str_replace(' ', '+', $img);
    $data_foto = base64_decode($img);

    // 2. Manajemen Folder: Siapkan folder penyimpanan di dalam storage/foto_absen/
    $folder_tujuan = "../storage/foto_absen/" . $bulan_tahun . "/";
    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0777, true); // Membuat folder otomatis jika belum tersedia
    }

    // 3. Membuat Nama File Unik (Mencegah file tertimpa jika nama karyawan sama)
    // Contoh hasil nama file: masuk_EMP001_20260521_215000.jpg
    $nama_file_baru = $aksi . "_" . $id_karyawan . "_" . date('Ymd_His') . ".jpg";
    $jalur_lengkap  = $folder_tujuan . $nama_file_baru;

    // 4. Simpan berkas gambar fisik ke dalam folder server lokal htdocs
    file_put_contents($jalur_lengkap, $data_foto);

    // 5. Eksekusi Logika Database MySQL
    if ($aksi == 'masuk') {
        // Jika aksi adalah 'masuk', buat baris data absensi baru untuk hari ini
        $query = "INSERT INTO tabel_absensi (id_karyawan, tanggal, jam_masuk, status, foto_masuk) 
                  VALUES ('$id_karyawan', '$tanggal', '$jam_sekarang', 'Masuk', '$nama_file_baru')";
    } else if ($aksi == 'pulang') {
        // Jika aksi adalah 'pulang', update jam pulang dan foto pulang di baris data yang sudah ada hari ini
        $query = "UPDATE tabel_absensi SET jam_pulang='$jam_sekarang', foto_pulang='$nama_file_baru' 
                  WHERE id_karyawan='$id_karyawan' AND tanggal='$tanggal'";
    }

    // 6. Redirect kembali dengan notifikasi sukses atau error
    if (mysqli_query($koneksi, $query)) {
        echo "<script>
                alert('Absen " . ucfirst($aksi) . " Anda berhasil dicatat!');
                window.location.href = 'dashboard_karyawan.php';
              </script>";
    } else {
        echo "Gagal memperbarui data absensi ke database: " . mysqli_error($koneksi);
    }
} else {
    // Jika file ini diakses langsung tanpa kiriman data kamera, kembalikan ke dashboard
    header("Location: dashboard_karyawan.php");
    exit;
}
?>