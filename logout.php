<?php
session_start(); // Memulai/mendeteksi session yang sedang aktif

// 1. Hapus semua variabel session yang tersimpan
$_SESSION = array();

// 2. Jika login menggunakan cookie session, hancurkan juga cookie-nya di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan session secara total di server
session_destroy();

// 4. Alihkan halaman kembali ke halaman login utama (index.php)
header("Location: index.php");
exit;