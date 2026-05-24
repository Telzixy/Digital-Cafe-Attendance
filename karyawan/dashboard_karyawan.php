 <?php

session_start();

include '../koneksi.php'; // Mundur satu folder ke root untuk mengambil koneksi.php


// Proteksi Halaman: Jika belum login atau bukan Karyawan, tendang kembali ke index.php di folder utama

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Karyawan') {

    header("Location: /cafe-aeter/index.php");

    exit;

}


$id_karyawan = $_SESSION['id_karyawan'];

$nama_user   = $_SESSION['nama_lengkap'];

$tanggal_ini = date('Y-m-d');

$bulan_ini   = date('m');

$tahun_ini   = date('Y');


// Cek status absensi karyawan hari ini di database db_absensi_aeter

$cek_absen = mysqli_query($koneksi, "SELECT * FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND tanggal='$tanggal_ini'");

$data_absen = mysqli_fetch_assoc($cek_absen);


$sudah_masuk  = false;

$sudah_pulang = false;

$status_hari_ini = "";


if ($data_absen) {

    $status_hari_ini = $data_absen['status'];

    if ($status_hari_ini == 'Masuk') {

        $sudah_masuk = true;

        if (!empty($data_absen['jam_pulang'])) {

            $sudah_pulang = true;

        }

    }

}

?> 

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Cafe Aeter</title>
    
    <!-- Menghubungkan ke file CSS lokal sesuai instruksi Anda -->
    <link rel="stylesheet" href="../assets/karyawan.css">
    
   <!-- VERSI OFFLINE: Memanggil Webcam.js dari folder assets lokal -->
    <script src="../assets/webcam.min.js"></script>
<body>

<div class="container">
    <!-- Header Informasi User -->
    <div class="header">
        <div>
            <h1>Cafe Aeter by Loca</h1>
            <small>Halo, <strong><?= $nama_user; ?></strong> (<?= $id_karyawan; ?>)</small>
        </div>
        <a href="/cafe-aeter/logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="grid">
        <!-- KOTAK 1: VERIFIKASI ABSENSI DENGAN KAMERA -->
        <div class="card">
            <h3>Verifikasi Foto Absen</h3>
            <?php if ($status_hari_ini == 'Sakit' || $status_hari_ini == 'Izin'): ?>
                <p style="text-align: center; color: #e67e22; font-weight: bold; margin-top: 50px;">
                    Hari ini Anda mengajukan status: <?= $status_hari_ini; ?>
                </p>
            <?php else: ?>
                <!-- Wadah render lensa kamera -->
                <div id="my_camera"></div>
                
                <form action="proses_absen_kamera.php" method="POST" id="form-absen">
                    <!-- Menyimpan data string gambar mentah base64 dari kamera -->
                    <input type="hidden" name="image_data" id="image_data">
                    
                    <?php if (!$sudah_masuk): ?>
                        <input type="hidden" name="aksi" value="masuk">
                        <button type="button" class="btn btn-masuk" onclick="ambilSnapShot()">KLIK: ABSEN MASUK</button>
                    <?php elseif ($sudah_masuk && !$sudah_pulang): ?>
                        <input type="hidden" name="aksi" value="pulang">
                        <button type="button" class="btn btn-pulang" onclick="ambilSnapShot()">KLIK: ABSEN PULANG</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-disabled" disabled>ABSENSI HARI INI SELESAI</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <!-- KOTAK 2: FORM PENGAJUAN SAKIT / IZIN -->
        <div class="card">
            <h3>Pengajuan Sakit / Izin</h3>
            <?php if ($sudah_masuk): ?>
                <p style="color: #c0392b; font-size: 13px; text-align: center; margin-top: 50px; font-weight: bold;">
                    Anda sudah melakukan absen masuk hari ini.<br>Form izin otomatis dikunci.
                </p>
            <?php elseif ($status_hari_ini == 'Sakit' || $status_hari_ini == 'Izin'): ?>
                <p style="color: #27ae60; font-size: 14px; text-align: center; font-weight: bold; margin-top: 50px;">
                    Pengajuan ketidakhadiran (<?= $status_hari_ini; ?>) hari ini berhasil dikirim!
                </p>
            <?php else: ?>
                <form action="proses_izin_karyawan.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Jenis Pengajuan</label>
                        <select name="status" required>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alasan Keterangan</label>
                        <textarea name="keterangan" rows="3" required placeholder="Tulis alasan singkat ketidakhadiran..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Surat Dokter / Bukti Izin (Maks 2MB)</label>
                        <input type="file" name="bukti_izin" accept="image/*" required>
                    </div>
                    <button type="submit" name="submit_izin" class="btn" style="background-color: #e67e22;">KIRIM FORM IZIN</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABEL RIWAYAT ABSENSI PRIBADI BULAN BERJALAN -->
    <div class="card" style="background: white;">
        <h3>Riwayat Absensi Bulan Ini</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Menampilkan riwayat absensi user yang sedang login di bulan aktif
                $riwayat = mysqli_query($koneksi, "SELECT * FROM tabel_absensi WHERE id_karyawan='$id_karyawan' AND MONTH(tanggal)='$bulan_ini' AND YEAR(tanggal)='$tahun_ini' ORDER BY tanggal DESC");
                
                if (mysqli_num_rows($riwayat) > 0) {
                    while($row = mysqli_fetch_assoc($riwayat)) {
                        echo "<tr>";
                        echo "<td>".date('d-m-Y', strtotime($row['tanggal']))."</td>";
                        echo "<td>".($row['jam_masuk'] ?? '-')."</td>";
                        echo "<td>".($row['jam_pulang'] ?? '-')."</td>";
                        echo "<td><strong>".$row['status']."</strong></td>";
                        echo "<td>".($row['keterangan'] ?? '-')."</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Belum ada riwayat absensi pada bulan ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- KONFIGURASI ENGINE JAVASCRIPT WEBCAM.JS -->
<script language="JavaScript">
    // Kamera hanya akan aktif jika elemen #my_camera ada di layar (tidak dikunci status izin)
    if(document.getElementById('my_camera')) {
        Webcam.set({
            width: 320,
            height: 240,
            image_format: 'jpeg',
            jpeg_quality: 90
        });
        Webcam.attach('#my_camera');
    }

    // Fungsi mengambil jepretan kamera dan submit otomatis data base64 ke PHP
    function ambilSnapShot() {
        Webcam.snap(function(data_uri) {
            // Memasukkan enkripsi string mentah gambar ke input hidden #image_data
            document.getElementById('image_data').value = data_uri;
            // Kirim data formulir secara otomatis ke backend
            document.getElementById('form-absen').submit();
        });
    }
</script>
</body>
</html>