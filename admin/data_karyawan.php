<?php
session_start();
include '../koneksi.php'; // Mundur satu folder ke root untuk mengambil koneksi.php

// Proteksi Halaman: Hanya Admin yang boleh masuk
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'Admin') {
    header("Location: /cafe-aeter/index.php");
    exit;
}

// ==========================================================================
// 1. PROSES LOGIKA BACKEND (TAMBAH, EDIT, HAPUS)
// ==========================================================================

// --- A. PROSES TAMBAH KARYAWAN ---
if (isset($_POST['tambah_karyawan'])) {
    $id_karyawan  = mysqli_real_escape_string($koneksi, $_POST['id_karyawan']);
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password     = md5($_POST['password']); // Enkripsi MD5 standar sesuai database kita
    $jabatan      = mysqli_real_escape_string($koneksi, $_POST['jabatan']);

    // Cek apakah ID Karyawan atau Username sudah terdaftar sebelumnya
    $cek_duplikat = mysqli_query($koneksi, "SELECT * FROM tabel_karyawan WHERE id_karyawan='$id_karyawan' OR username='$username'");
    if (mysqli_num_rows($cek_duplikat) > 0) {
        echo "<script>alert('Gagal! ID Karyawan atau Username sudah digunakan.'); window.location.href='data_karyawan.php';</script>";
    } else {
        $query_tambah = "INSERT INTO tabel_karyawan (id_karyawan, nama_lengkap, username, password, jabatan, level) 
                         VALUES ('$id_karyawan', '$nama_lengkap', '$username', '$password', '$jabatan', 'Karyawan')";
        if (mysqli_query($koneksi, $query_tambah)) {
            echo "<script>alert('Data karyawan baru berhasil ditambahkan!'); window.location.href='data_karyawan.php';</script>";
        }
    }
}

// --- B. PROSES EDIT KARYAWAN ---
if (isset($_POST['edit_karyawan'])) {
    $id_karyawan  = $_POST['id_karyawan'];
    $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($koneksi, $_POST['username']);
    $jabatan      = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    
    // Jika password diisi baru, enkripsi MD5. Jika kosong, pakai password lama.
    if (!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $query_edit = "UPDATE tabel_karyawan SET nama_lengkap='$nama_lengkap', username='$username', password='$password', jabatan='$jabatan' WHERE id_karyawan='$id_karyawan'";
    } else {
        $query_edit = "UPDATE tabel_karyawan SET nama_lengkap='$nama_lengkap', username='$username', jabatan='$jabatan' WHERE id_karyawan='$id_karyawan'";
    }

    if (mysqli_query($koneksi, $query_edit)) {
        echo "<script>alert('Data karyawan berhasil diperbarui!'); window.location.href='data_karyawan.php';</script>";
    }
}

// --- C. PROSES HAPUS KARYAWAN ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    
    // Jalankan query hapus data berdasarkan ID Karyawan
    $query_hapus = "DELETE FROM tabel_karyawan WHERE id_karyawan='$id_hapus'";
    if (mysqli_query($koneksi, $query_hapus)) {
        echo "<script>alert('Data karyawan telah dihapus dari sistem!'); window.location.href='data_karyawan.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - Cafe Aeter</title>
    
    <link rel="stylesheet" href="../assets/sidebar.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<div class="tablet-box">
    <div class="sidebar">
        <div class="logo-box">☕Aeter By Loca Cafe</div>
        <a href="dashboard_admin.php" class="menu-item">Dashboard Admin</a>
        <a href="data_karyawan.php" class="menu-item active">Data Karyawan</a>
        <a href="rekap_bulanan.php" class="menu-item">Rekap Bulanan</a>
        
        <div style="margin-top: auto;">
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="main-panel">
        <div class="header">
            <h1>Manajemen Data Karyawan</h1>
        </div>

        <button class="btn-admin btn-tambah" onclick="tampilFormTambah()" style="margin-bottom: 20px;">+ Tambah Karyawan Baru</button>

        <div id="box-form" class="form-inline">
            <h3 id="form-title">Tambah Akun Karyawan Baru</h3>
            <form action="data_karyawan.php" method="POST" id="form-aksi">
                <input type="text" name="id_karyawan" id="input_id" class="form-control" placeholder="ID Karyawan" required>
                <input type="text" name="nama_lengkap" id="input_nama" class="form-control" placeholder="Nama Lengkap" required>
                <input type="text" name="username" id="input_user" class="form-control" placeholder="Username" required>
                <input type="password" name="password" id="input_pass" class="form-control" placeholder="Password">
                <select name="jabatan" id="input_jabatan" class="form-control">
                    <option value="Barista">Barista</option>
                    <option value="Kasir">Kasir</option>
                    <option value="Kitchen Staf">Kitchen Staf</option>
                    <option value="Server / Waiter">Server / Waiter</option>
                </select>
                <button type="submit" name="tambah_karyawan" id="btn-submit" class="btn-admin btn-edit">Simpan Data</button>
                <button type="button" class="btn-admin btn-hapus" onclick="tutupForm()">Batal</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Jabatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        <tbody>
            <?php
            $query_tampil = mysqli_query($koneksi, "SELECT * FROM tabel_karyawan WHERE level='Karyawan' ORDER BY id_karyawan ASC");
            $no = 1;

            if (mysqli_num_rows($query_tampil) > 0) {
                while ($row = mysqli_fetch_assoc($query_tampil)) {
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= $row['id_karyawan']; ?></td>
                        <td><?= $row['nama_lengkap']; ?></td>
                        <td><?= $row['username']; ?></td>
                        <td><?= $row['jabatan']; ?></td>
                        <td>
                            <!-- Tombol Edit melempar data array ke fungsi JavaScript jalankanEdit() -->
                            <button class="btn-admin btn-edit" onclick="jalankanEdit(
                                '<?= $row['id_karyawan']; ?>',
                                '<?= mysqli_real_escape_string($koneksi, $row['nama_lengkap']); ?>',
                                '<?= mysqli_real_escape_string($koneksi, $row['username']); ?>',
                                '<?= $row['jabatan']; ?>'
                            )">Edit</button>
                            
                            <a href="data_karyawan.php?hapus=<?= $row['id_karyawan']; ?>" class="btn-admin btn-hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus akun <?= $row['nama_lengkap']; ?>?')">Hapus</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='6'>Belum ada data karyawan terdaftar. silakan tambah baru!</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- INTERAKSI JAVASCRIPT UNTUK EFISIENSI FORM CRUD (TANPA BULAK BALIK BUKA HALAMAN BARU) -->
<script>
    var boxForm = document.getElementById('box-form');

    function tampilFormTambah() {
        boxForm.style.display = 'block';
        document.getElementById('form-title').innerText = 'Tambah Akun Karyawan Baru';
        document.getElementById('btn-submit').name = 'tambah_karyawan';
        
        // Reset field form jadi kosong
        document.getElementById('input_id').value = '';
        document.getElementById('input_id').readOnly = false;
        document.getElementById('input_nama').value = '';
        document.getElementById('input_user').value = '';
        document.getElementById('input_pass').value = '';
        document.getElementById('input_pass').required = true;
        document.getElementById('pass-note').innerText = '';
    }

    function jalankanEdit(id, nama, user, jabatan) {
        boxForm.style.display = 'block';
        document.getElementById('form-title').innerText = 'Ubah Informasi Akun Karyawan';
        document.getElementById('btn-submit').name = 'edit_karyawan';
        
        // Isi field form dengan data baris tabel yang dipilih
        document.getElementById('input_id').value = id;
        document.getElementById('input_id').readOnly = true; // ID Karyawan bersifat PRIMARY KEY, tidak boleh diubah
        document.getElementById('input_nama').value = nama;
        document.getElementById('input_user').value = user;
        document.getElementById('input_pass').value = '';
        document.getElementById('input_pass').required = false; // Saat edit, password boleh dikosongkan jika tidak mau diganti
        document.getElementById('pass-note').innerText = '(*Kosongkan jika tidak ingin mengubah password)';
        document.getElementById('input_jabatan').value = jabatan;
    }

// Ganti bagian ini di dalam tag <script> Anda
function tutupForm() {
    // Sebelumnya ada 'style.style.display', cukup gunakan 'style.display'
    document.getElementById('box-form').style.display = 'none';
}
</script>
</body>
</html>