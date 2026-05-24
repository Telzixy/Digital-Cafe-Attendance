<?php
session_start();
include '../koneksi.php'; // Mundur satu folder ke root untuk mengambil koneksi.php

// Proteksi Halaman: Pastikan hanya Karyawan yang bisa mengakses script ini
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Karyawan') {
    header("Location: /cafe-aeter/index.php");
    exit;
}

if (isset($_POST['submit_izin'])) {
    $id_karyawan = $_SESSION['id_karyawan'];
    $tanggal     = date('Y-m-d');
    $status      = $_POST['status']; // Menangkap opsi 'Sakit' atau 'Izin'
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    $bulan_tahun = date('Y-m'); // Format Tahun-Bulan untuk pengarsipan folder (Contoh: 2026-05)

    // 1. Mengambil meta data informasi dari file yang diupload
    $nama_file   = $_FILES['bukti_izin']['name'];
    $ukuran_file = $_FILES['bukti_izin']['size'];
    $error_file  = $_FILES['bukti_izin']['error'];
    $tmp_file    = $_FILES['bukti_izin']['tmp_name'];

    // 2. Validasi Ekstensi
    $ekstensi_valid = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']; // Bisa ditambah format lain sesuai kebutuhan
    $ekstensi_file  = explode('.', $nama_file);
    $ekstensi_file  = strtolower(end($ekstensi_file));

    if (!in_array($ekstensi_file, $ekstensi_valid)) {
        echo "<script>
                alert('Format dokumen salah! Sistem hanya menerima file JPG, JPEG, PNG, PDF, DOC, atau DOCX.');
                window.location.href = 'dashboard_karyawan.php';
              </script>";
        exit;
    }

    // 3. Validasi Ukuran File (Maksimal Batas Toleransi 2MB = 2.097.152 bytes)
    if ($ukuran_file > 2097152) {
        echo "<script>
                alert('Ukuran file terlalu besar! Silakan kompres foto bukti maksimal 2MB.');
                window.location.href = 'dashboard_karyawan.php';
              </script>";
        exit;
    }

    // 4. Manajemen Folder: Siapkan struktur folder arsip di dalam storage/bukti_izin/
    $folder_tujuan = "../storage/bukti_izin/" . $bulan_tahun . "/";
    if (!is_dir($folder_tujuan)) {
        mkdir($folder_tujuan, 0777, true); // Membuat folder otomatis jika belum ada di server
    }

    // 5. Standarisasi Penamaan File Unik Baru
    // Contoh hasil nama file: sakit_EMP002_20260521_154512.png
    $nama_file_baru = strtolower($status) . "_" . $id_karyawan . "_" . date('Ymd_His') . "." . $ekstensi_file;
    $jalur_simpan   = $folder_tujuan . $nama_file_baru;

    // 6. Pindahkan berkas dari temporary folder local komputer ke folder storage htdocs XAMPP
    if (move_uploaded_file($tmp_file, $jalur_simpan)) {
        
        // 7. Input rekam data status ketidakhadiran baru ke database db_absensi_aeter
        $query = "INSERT INTO tabel_absensi (id_karyawan, tanggal, status, keterangan, bukti_izin) 
                  VALUES ('$id_karyawan', '$tanggal', '$status', '$keterangan', '$nama_file_baru')";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>
                    alert('Form pengajuan " . $status . " Anda berhasil dikirim ke Admin!');
                    window.location.href = 'dashboard_karyawan.php';
                  </script>";
        } else {
            echo "Gagal menginput data ketidakhadiran ke database: " . mysqli_error($koneksi);
        }
    } else {
        echo "<script>
                alert('Gagal mengunggah file gambar ke sistem server. Silakan coba lagi.');
                window.location.href = 'dashboard_karyawan.php';
              </script>";
    }
} else {
    // Jika file diakses langsung tanpa submit form, tendang balik ke halaman dashboard
    header("Location: dashboard_karyawan.php");
    exit;
}
?>