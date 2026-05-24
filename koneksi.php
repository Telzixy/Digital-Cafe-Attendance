<?php
// Pengaturan zona waktu agar pencatatan jam absen sesuai dengan waktu lokal (WIB)
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = ""; // Kosongkan jika menggunakan XAMPP bawaan bawaan windows
$db   = "db_absensi_aeter"; // Nama database prototype Anda

// Membuka koneksi ke server MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Periksa apakah jalur koneksi berhasil atau gagal
if (!$koneksi) {
    die("Koneksi ke database db_absensi_aeter gagal: " . mysqli_connect_error());
}
?>