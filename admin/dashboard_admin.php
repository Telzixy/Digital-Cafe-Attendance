<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

// Cek apakah ada kiriman tanggal lewat URL, jika tidak ada baru pakai tanggal hari ini
$tanggal_ini = isset($_GET['tanggal_filter']) ? $_GET['tanggal_filter'] : date('Y-m-d');

$bulan_ini   = date('m', strtotime($tanggal_ini));
$tahun_ini   = date('Y', strtotime($tanggal_ini));

// Ambil kata kunci dari form pencarian
$cari = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';

// Query Statistik
// Query Statistik - Mengabaikan Admin
$total_karyawan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_karyawan WHERE level != 'Admin'"))['total'];

$total_masuk    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi a JOIN tabel_karyawan k ON a.id_karyawan = k.id_karyawan WHERE a.tanggal='$tanggal_ini' AND a.status='Masuk' AND k.level != 'Admin'"))['total'];

$total_sakit    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi a JOIN tabel_karyawan k ON a.id_karyawan = k.id_karyawan WHERE a.tanggal='$tanggal_ini' AND a.status='Sakit' AND k.level != 'Admin'"))['total'];

$total_izin     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi a JOIN tabel_karyawan k ON a.id_karyawan = k.id_karyawan WHERE a.tanggal='$tanggal_ini' AND a.status='Izin' AND k.level != 'Admin'"))['total'];

$total_absen    = max(0, $total_karyawan - ($total_masuk + $total_sakit + $total_izin));

// Query Log
$query_log = "SELECT a.*, k.nama_lengkap FROM tabel_absensi a 
              JOIN tabel_karyawan k ON a.id_karyawan = k.id_karyawan 
              WHERE a.tanggal = '$tanggal_ini'";

if (!empty($cari)) {
    $query_log .= " AND (k.nama_lengkap LIKE '%$cari%' OR a.id_karyawan LIKE '%$cari%')";
}
$query_log .= " ORDER BY a.jam_masuk DESC";
$result_log = mysqli_query($koneksi, $query_log);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/admin.css">
    <link rel="stylesheet" href="../assets/sidebar.css">
</head>
<body>

<div class="tablet-box">
    <div class="sidebar">
        <div class="logo-box">☕Aeter By Loca Cafe </div>
        <a href="dashboard_admin.php" class="menu-item">Dashboard Admin</a>
        <a href="data_karyawan.php" class="menu-item">Data Karyawan</a>
        <a href="rekap_bulanan.php" class="menu-item">Rekap Bulanan</a>
        <div style="margin-top: auto;">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main-panel">
       <div class="header">
    <h1>Admin</h1>
    
    <div class="date-badge" style="position: relative; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden;">
        
        <?= date(' d F Y 📅', strtotime($tanggal_ini)); ?>
        
        <form method="GET" action="" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; margin: 0; padding: 0;">
            <input type="date" name="tanggal_filter" value="<?= $tanggal_ini; ?>" onchange="this.form.submit()" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; font-size: 40px;">
        </form>
        
    </div>
</div>

        <div class="stats-grid">
            <div class="card" style="background: #2E7D32;">HADIR<br><strong><?= $total_masuk ?></strong></div>
            <div class="card" style="background: #D84315;">SAKIT<br><strong><?= $total_sakit ?></strong></div>
            <div class="card" style="background: #1565C0;">IZIN<br><strong><?= $total_izin ?></strong></div>
            <div class="card" style="background: #C62828;">BELUM<br><strong><?= $total_absen ?></strong></div>
        </div>

        <form method="GET" action="">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" 
                   placeholder="🔍 Cari nama atau ID..." 
                   style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ccc; margin-bottom: 20px;">
        </form>

        <table>
                <th>No</th>
                <th>ID Karyawan</th>
                <th>Nama Karyawan</th>
                <th>Jam Masuk</th>
                <th>Foto Masuk</th>
                <th>Jam Pulang</th>
                <th>Foto Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
            <tbody>
            <?php
            // Query JOIN untuk mengambil data absensi sekaligus nama lengkap karyawan dari tabel_karyawan
            $query_log = "SELECT a.*, k.nama_lengkap 
                          FROM tabel_absensi a 
                          JOIN tabel_karyawan k ON a.id_karyawan = k.id_karyawan 
                          WHERE a.tanggal = '$tanggal_ini' 
                          ORDER BY a.jam_masuk DESC";
            $result_log = mysqli_query($koneksi, $query_log);
            $no = 1;

            if (mysqli_num_rows($result_log) > 0) {
                while ($row = mysqli_fetch_assoc($result_log)) {
                    
                    // TAMBAHAN: Ambil Tahun-Bulan dari tanggal database agar cocok dengan folder penyimpanan file karyawan
                    $folder_arsip = date('Y-m', strtotime($row['tanggal']));

                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . $row['id_karyawan'] . "</td>";
                    echo "<td>" . $row['nama_lengkap'] . "</td>";
                    echo "<td>" . ($row['jam_masuk'] ?? '-') . "</td>";
                    
                    // Validasi tampilan foto jepretan webcam saat masuk (Diubah ke $folder_arsip)
                    if (!empty($row['foto_masuk'])) {
                        echo "<td><img src='../storage/foto_absen/" . $folder_arsip . "/" . $row['foto_masuk'] . "' class='img-absen' alt='Foto Masuk' style='width:50px; height:auto; border-radius:4px;'></td>";
                    } else {
                        echo "<td>-</td>";
                    }

                    echo "<td>" . ($row['jam_pulang'] ?? '-') . "</td>";
                    
                    // Validasi tampilan foto jepretan webcam saat pulang (Diubah ke $folder_arsip)
                    if (!empty($row['foto_pulang'])) {
                        echo "<td><img src='../storage/foto_absen/" . $folder_arsip . "/" . $row['foto_pulang'] . "' class='img-absen' alt='Foto Pulang' style='width:50px; height:auto; border-radius:4px;'></td>";
                    } else {
                        echo "<td>-</td>";
                    }

                    echo "<td><strong>" . $row['status'] . "</strong></td>";
                    
                    // Cek jika statusnya Sakit/Izin, tampilkan link tautan dokumen surat dokter (Diubah ke $folder_arsip)
                    if ($row['status'] == 'Sakit' || $row['status'] == 'Izin') {
                        echo "<td><a href='../storage/bukti_izin/" . $folder_arsip . "/" . $row['bukti_izin'] . "' target='_blank' style='color:#2980b9; font-weight:bold;'>Lihat Surat (" . $row['keterangan'] . ")</a></td>";
                    } else {
                        echo "<td>-</td>";
                    }
                    
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>Belum ada aktivitas absensi dari karyawan hari ini.</td></tr>";
            }
            ?>
        </tbody>
        </table>
    </div>
</div>
</body>
</html>