<?php
session_start();
include '../koneksi.php'; // Mundur satu folder ke root untuk mengambil koneksi.php

// Proteksi Halaman: Hanya Admin yang boleh mengekspor data
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Admin') {
    header("Location: /cafe-aeter/index.php");
    exit;
}

// Mengambil parameter bulan dan tahun dari URL
$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$bulan_tahun_filter = $tahun_pilihan . '-' . $bulan_pilihan;

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// ==========================================================================
// PENGATURAN HEADER UNTUK MEMAKSA BROWSER MENGUNDUH SEBAGAI EXCEL
// ==========================================================================
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Absensi_" . $nama_bulan[$bulan_pilihan] . "_" . $tahun_pilihan . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!-- KARENA MENGGUNAKAN ELEMEN HTML DI BAWAH INI, MICROSOFT EXCEL AKAN OTOMATIS CONVERT MENJADI TABEL SPREADSHEET -->
<html lang="id">
<head>
    <meta charset="UTF-8">
</head>
<body>

    <!-- Judul Laporan di dalam Excel -->
    <div style="text-align: center;">
        <h2>LAPORAN REKAPITULASI ABSENSI DIGITAL KARYAWAN</h2>
        <h3>CAFE AETER BY LOCA</h3>
        <p>Periode: <?php echo $nama_bulan[$bulan_pilihan] . " " . $tahun_pilihan; ?></p>
    </div>

    <br>

    <!-- Struktur Tabel Utama -->
    <table border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #4B3621; color: white; font-weight: bold;">
                <th style="padding: 10px; text-align: center;">No</th>
                <th style="padding: 10px; text-align: center;">ID Karyawan</th>
                <th style="padding: 10px; text-align: left;">Nama Lengkap</th>
                <th style="padding: 10px; text-align: center;">Jabatan</th>
                <th style="background-color: #2e7d32; color: white; padding: 10px; text-align: center;">Hadir (M)</th>
                <th style="background-color: #f39c12; color: white; padding: 10px; text-align: center;">Sakit (S)</th>
                <th style="background-color: #2980b9; color: white; padding: 10px; text-align: center;">Izin (I)</th>
                <th style="background-color: #c0392b; color: white; padding: 10px; text-align: center;">Alpha (A)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // 1. Ambil data semua karyawan level staf
            $query_karyawan = mysqli_query($koneksi, "SELECT * FROM tabel_karyawan WHERE level='Karyawan' ORDER BY id_karyawan ASC");
            $no = 1;

            // Hitung patokan hari kalender untuk menentukan Alpha
            $jumlah_hari_per_bulan = cal_days_in_month(CAL_GREGORIAN, $bulan_pilihan, $tahun_pilihan);
            if ($bulan_tahun_filter == date('Y-m')) {
                $hari_patokan = date('d'); // Sampai tanggal hari ini jika bulan berjalan
            } else {
                $hari_patokan = $jumlah_hari_per_bulan; // Sebulan penuh jika bulan lalu
            }

            if (mysqli_num_rows($query_karyawan) > 0) {
                while ($karyawan = mysqli_fetch_assoc($query_karyawan)) {
                    $id_karyawan = $karyawan['id_karyawan'];

                    // 2. Hitung jumlah kehadiran masing-masing kategori
                    $query_hadir = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Masuk' AND tanggal LIKE '$bulan_tahun_filter-%'");
                    $hadir = mysqli_fetch_assoc($query_hadir)['total'];

                    $query_sakit = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Sakit' AND tanggal LIKE '$bulan_tahun_filter-%'");
                    $sakit = mysqli_fetch_assoc($query_sakit)['total'];

                    $query_izin = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND status='Izin' AND tanggal LIKE '$bulan_tahun_filter-%'");
                    $izin = mysqli_fetch_assoc($query_izin)['total'];

                    // Logika perhitungan Alpha
                    $alpha = $hari_patokan - ($hadir + $sakit + $izin);
                    if ($alpha < 0) $alpha = 0;
                    ?>
                    <tr>
                        <td style="text-align: center; padding: 8px;"><?php echo $no++; ?></td>
                        <td style="text-align: center; padding: 8px;">'<?php echo $id_karyawan; ?>'</td> <!-- Tanda petik tunggal mencegah Excel memotong format teks ID -->
                        <td style="text-align: left; padding: 8px;"><?php echo $karyawan['nama_lengkap']; ?></td>
                        <td style="text-align: center; padding: 8px;"><?php echo $karyawan['jabatan']; ?></td>
                        <td style="text-align: center; padding: 8px; font-weight: bold; color: #2e7d32;"><?php echo $hadir; ?></td>
                        <td style="text-align: center; padding: 8px; font-weight: bold; color: #f39c12;"><?php echo $sakit; ?></td>
                        <td style="text-align: center; padding: 8px; font-weight: bold; color: #2980b9;"><?php echo $izin; ?></td>
                        <td style="text-align: center; padding: 8px; font-weight: bold; color: #c0392b;"><?php echo $alpha; ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='8' style='text-align:center; padding:10px;'>Tidak ada data rekapitulasi.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <br><br>
    <!-- Tanda Tangan Laporan Digital -->
    <div style="float: right; text-align: center; width: 250px;">
        <p>Palangkaraya, <?php echo date('d-m-Y'); ?></p>
        <p><strong>Pemilik Cafe Aeter</strong></p>
        <br><br><br>
        <p><u>...........................................</u></p>
    </div>

</body>
</html>