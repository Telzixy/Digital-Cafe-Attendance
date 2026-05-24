<?php
// Memulai session untuk menyimpan status login pengguna
session_start();
include 'koneksi.php'; // Langsung dipanggil karena berada di satu folder utama

$error = "";

// Jika pengguna sudah login, langsung alihkan ke dashboard masing-masing agar tidak kembali ke halaman login
if (isset($_SESSION['level'])) {
    if ($_SESSION['level'] == 'Admin') {
        header("Location: /cafe-aeter/admin/dashboard_admin.php");
        exit;
    } else if ($_SESSION['level'] == 'Karyawan') {
        header("Location: /cafe-aeter/karyawan/dashboard_karyawan.php");
        exit;
    }
}

if (isset($_POST['login'])) {
    // Mencegah SQL Injection untuk keamanan dasar login
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']); // Mengubah input teks biasa menjadi MD5 sesuai data di database

    // Query mencari user berdasarkan username dan password MD5
    $query  = "SELECT * FROM tabel_karyawan WHERE username='$username' AND password='$password'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $data = mysqli_fetch_assoc($result);

        // Menyimpan data penting ke dalam session untuk validasi hak akses di halaman dashboard
        $_SESSION['id_karyawan']  = $data['id_karyawan'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['level']        = $data['level'];

        // Pengalihan halaman berdasarkan hak akses (level) ke folder proyek cafe-aeter
        if ($data['level'] == 'Admin') {
            header("Location: /cafe-aeter/admin/dashboard_admin.php");
            exit;
        } else {
            header("Location: /cafe-aeter/karyawan/dashboard_karyawan.php");
            exit;
        }
    } else {
        $error = "Username atau Password Anda salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Aeter By Loca Cafe</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="tablet-box">
    <div class="left-side">
        <h2 style="color: #4B3621;">Digital Cafe Attendance</h2>
        <p style="color: #888; font-size: 12px;">Digital Core Intelligence</p>
        <div style="flex-grow: 1; display: flex; justify-content: center; align-items: center;">
            <img src="assets/logo.jpg" class="logo-img">
        </div>
    </div>

    <div class="right-side">
        <div class="header-box">
            <h1 style="color: #4B3621; font-size: 28px;">Aeter By Loca Cafe</h1>
            <p style="color: #888; font-size: 14px; font-style: italic;">Attendance Management System</p>
        </div>
        
        <form method="POST">
            <label style="font-size: 10px; font-weight:bold; color:#666;">USERNAME</label>
            <input type="text" name="username" class="form-control" required placeholder="Enter username...">
            
            <label style="font-size: 10px; font-weight:bold; color:#666;">PASSWORD</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
            
            <button type="submit" name="login" class="btn-checkin">CHECK-IN</button>
        </form>
    </div>
</div>

</body>
</html>