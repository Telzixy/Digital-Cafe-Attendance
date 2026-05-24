<?php
session_start();
include '../koneksi.php'; // Mundur satu folder ke root untuk mengambil koneksi.php

// Proteksi Halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Admin') {
    header("Location: /cafe-aeter/index.php");
    exit;
}

// Mengatur filter bulan dan tahun default ke bulan berjalan saat ini jika belum dipilih
$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$bulan_tahun_filter = $tahun_pilihan . '-' . $bulan_pilihan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi Bulanan - Cafe Aeter</title>
    <link rel="stylesheet" href="../assets/sidebar.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<div class="tablet-box">
    <div class="sidebar">
        <div class="logo-box">☕Aeter By Loca Cafe</div>
        <a href="dashboard_admin.php" class="menu-item">Dashboard Admin</a>
        <a href="data_karyawan.php" class="menu-item">Data Karyawan</a>
        <a href="rekap_bulanan.php" class="menu-item active">Rekap Bulanan</a>
        
        <div style="margin-top: auto;">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main-panel">
        <div class="header">
            <h1>Rekapitulasi</h1>
        </div>

        <div class="form-container" style="background: #fafafa; padding: 15px; margin-bottom: 25px; border: 1px solid #ddd; border-radius: 8px; clear: both;">
            <form method="GET" action="rekap_bulanan.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="width: 150px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:bold;">Pilih Bulan</label>
                    <select name="bulan" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <?php
                        $nama_bulan = ['01'=>'Januari', '02'=>'Februari', '03'=>'Maret', '04'=>'April', '05'=>'Mei', '06'=>'Juni', '07'=>'Juli', '08'=>'Agustus', '09'=>'September', '10'=>'Oktober', '11'=>'November', '12'=>'Desember'];
                        foreach ($nama_bulan as $key => $val) {
                            $selected = ($key == $bulan_pilihan) ? 'selected' : '';
                            echo "<option value='$key' $selected>$val</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="width: 150px;">
                    <label style="display:block; margin-bottom:5px; font-size:13px; font-weight:bold;">Pilih Tahun</label>
                    <select name="tahun" class="form-control" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <?php
                        $tahun_sekarang = date('Y');
                        for ($i = $tahun_sekarang; $i >= $tahun_sekarang - 3; $i--) {
                            $selected = ($i == $tahun_pilihan) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-admin btn-tambah" style="padding: 10px 20px; height: 38px; cursor: pointer; font-weight: bold;">Filter</button>
                    <a href="cetak_excel.php?bulan=<?= $bulan_pilihan; ?>&tahun=<?= $tahun_pilihan; ?>" class="btn-admin" style="background:#2E7D32; color: white; padding: 0 20px; height: 38px; line-height: 38px; display:inline-block; text-decoration:none; border-radius: 4px; font-weight: bold; font-size: 14px;">Export Excel</a>
                </div>
            </form>
        </div>

        <h2 style="margin-bottom: 15px; font-size: 18px; color: #333;">Laporan Periode : <?= $nama_bulan[$bulan_pilihan] . " " . $tahun_pilihan; ?></h2>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">ID Karyawan</th>
                    <th style="text-align: left; padding-left: 15px;">Nama Lengkap</th>
                    <th>Jabatan</th>
                    <th style="background-color: #2e7d32; color: white; width: 10%;">Hadir (H)</th>
                    <th style="background-color: #f39c12; color: white; width: 10%;">Sakit (S)</th>
                    <th style="background-color: #2980b9; color: white; width: 10%;">Izin (I)</th>
                    <th style="background-color: #c0392b; color: white; width: 10%;">Alpha (A)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // 1. Ambil semua data karyawan aktif terlebih dahulu
                $query_karyawan = mysqli_query($koneksi, "SELECT * FROM tabel_karyawan WHERE level='Karyawan' ORDER BY id_karyawan ASC");
                $no = 1;

                // Menghitung jumlah hari total pada bulan yang dipilih untuk penentuan asumsi angka Alpha
                $jumlah_hari_per_bulan = cal_days_in_month(CAL_GREGORIAN, $bulan_pilihan, $tahun_pilihan);

                if (mysqli_num_rows($query_karyawan) > 0) {
                    while ($karyawan = mysqli_fetch_assoc($query_karyawan)) {
                        $id_karyawan = $karyawan['id_karyawan'];

                        // 2. Hitung jumlah Masuk, Sakit, dan Izin berdasarkan id_karyawan dan filter bulan/tahun berjalan
                        $query_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Masuk' AND tanggal LIKE '$bulan_tahun_filter-%'");
                        $hadir = mysqli_fetch_assoc($query_hadir)['total'];

                        $query_sakit = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Sakit' AND tanggal LIKE '$bulan_tahun_filter-%'");
                        $sakit = mysqli_fetch_assoc($query_sakit)['total'];

                        $query_izin = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Izin' AND tanggal LIKE '$bulan_tahun_filter-%'");
                        $izin = mysqli_fetch_assoc($query_izin)['total'];

                        // Rumus Logika Sederhana Alpha
                        if ($bulan_tahun_filter == date('Y-m')) {
                            $hari_patokan = date('d'); // Jika bulan ini, patokannya sampai tanggal hari ini saja
                        } else {
                            $hari_patokan = $jumlah_hari_per_bulan; // Jika bulan lalu, total hari sebulan penuh
                        }
                        
                        $alpha = $hari_patokan - ($hadir + $sakit + $izin);
                        if ($alpha < 0) $alpha = 0; // Mengantisipasi bug nilai minus
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= $id_karyawan; ?></strong></td>
                            <td style="text-align: left; padding-left: 15px;"><?= $karyawan['nama_lengkap']; ?></td>
                            <td><?= $karyawan['jabatan']; ?></td>
                            <td style="font-weight: bold; color: #2e7d32;"><?= $hadir; ?></td>
                            <td style="font-weight: bold; color: #f39c12;"><?= $sakit; ?></td>
                            <td style="font-weight: bold; color: #2980b9;"><?= $izin; ?></td>
                            <td style="font-weight: bold; color: #c0392b;"><?= $alpha; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8'>Tidak ada data karyawan aktif untuk direkap.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div> </div> </body>
</html>