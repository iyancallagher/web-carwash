<?php
require "session.php";
require "koneksi.php";

// Ambil data cucian per carwasher hari ini
$query = mysqli_query($conn, "
SELECT 
    pelanggan.id,
    pelanggan.plat_nomor,
    pelanggan.warna,
    pelanggan.harga,
    pelanggan.harga_tambahan,
    pelanggan.status,
    (pelanggan.harga + pelanggan.harga_tambahan) AS total_harga,
    ((pelanggan.harga + pelanggan.harga_tambahan) * 0.4 - 4000) AS gaji,
    jenis_mobil.merk,
    karyawan.nama AS nama_karyawan
FROM pelanggan
JOIN jenis_mobil ON pelanggan.id_jenis_mobil = jenis_mobil.id
JOIN karyawan ON pelanggan.carwasher = karyawan.id
WHERE pelanggan.tanggal = CURDATE() AND pelanggan.status = 'selesai'
ORDER BY pelanggan.id DESC
") or die(mysqli_error($conn));


$data = [];
$total_gaji_semua = 0;

while ($row = mysqli_fetch_assoc($query)) {
    $nama = $row['nama_karyawan'];
    $total_harga = $row['harga'] + $row['harga_tambahan'];
    $gaji = $total_harga * 0.4 - 4000;

    if (!isset($data[$nama])) {
        $data[$nama] = [
            'mobil' => [],
            'jumlah' => 0,
            'total_gaji' => 0 // pastikan ini sudah ada dan diinisialisasi 0
        ];
    }

    // Simpan total harga dan gaji di setiap record
    $row['total_harga'] = $total_harga;
    $row['gaji'] = $gaji;

    $data[$nama]['mobil'][] = $row;
    $data[$nama]['jumlah']++;
    $data[$nama]['total_gaji'] += $gaji; // tambah ke total gaji per carwasher

    $total_gaji_semua += $gaji;
}
// Proses simpan laporan gaji
if (isset($_POST['simpan_gaji'])) {
    $tanggal = date('Y-m-d');

    // Cek apakah data hari ini sudah ada
    $cek = mysqli_query($conn, "SELECT * FROM gaji_harian WHERE tanggal = '$tanggal'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Data gaji hari ini sudah disimpan sebelumnya!');</script>";
    } else {
        foreach ($data as $nama => $detail) {
            $id_karyawan = mysqli_fetch_assoc(
                mysqli_query($conn, "SELECT id FROM karyawan WHERE nama = '" . mysqli_real_escape_string($conn, $nama) . "'")
            )['id'];

            $jumlah = $detail['jumlah'];
            $gaji = $detail['total_gaji'];

            mysqli_query($conn, "
                INSERT INTO gaji_harian (tanggal, id_karyawan, jumlah_mobil, total_gaji)
                VALUES ('$tanggal', '$id_karyawan', '$jumlah', '$gaji')
            ");
        }
        echo "<script>alert('Laporan gaji berhasil disimpan!');</script>";
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gaji Carwasher</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .margin {
            margin-top: 80px;
        }
    </style>
</head>
<?php include "sidebar.php"; ?>
<body class="container mt-5">
    <div class="margin">

    <h3 class="mb-4">Laporan Gaji Carwasher - <?= date('d M Y') ?></h3>
    <!-- Tombol simpan laporan -->
<form method="POST">
    <input type="hidden" name="simpan_gaji" value="1">
    <button type="submit" class="btn btn-success mt-3">Simpan Laporan Gaji Hari Ini</button>
</form>

    <?php foreach ($data as $nama => $detail): ?>
    <!-- Card per carwasher sudah ada di sini -->
<?php endforeach; ?>

<!-- Ringkasan gaji per carwasher -->
<div class="card mt-4">
    <div class="card-header bg-info text-black">
        <strong>Ringkasan Gaji per Carwasher</strong>
    </div>
    <div class="card-body">
        <ul class="list-group">
            <?php foreach ($data as $nama => $detail): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($nama) ?>
                    <span class="badge bg-primary rounded-pill">
                        Rp <?= number_format($detail['total_gaji'], 0, ',', '.') ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

    <?php if (empty($data)): ?>
        <div class="alert alert-warning">Belum ada mobil dicuci hari ini.</div>
    <?php else: ?>
        <?php foreach ($data as $nama => $detail): ?>
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <strong><?= htmlspecialchars($nama) ?></strong> - Total Mobil: <?= $detail['jumlah'] ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered m-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Plat Nomor</th>
                                <th>Merk</th>
                                <th>Warna</th>
                                <th>Harga</th>
                                <th>Harga Tambahan</th>
                                <th>Total Harga</th>
                                <th>Gaji</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail['mobil'] as $i => $m): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= strtoupper($m['plat_nomor']) ?></td>
                                    <td><?= $m['merk'] ?></td>
                                    <td><?= ucwords($m['warna']) ?></td>
                                    <td>Rp <?= number_format($m['harga'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($m['harga_tambahan'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($m['total_harga'], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($m['gaji'], 0, ',', '.') ?></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</body>
<hr class="my-5">
<h4>Laporan Gaji Hari Sebelumnya</h4>

<table class="table table-bordered table-striped mt-3">
    <thead class="table-dark">
        <tr>
            <th>Tanggal</th>
            <th>Nama Carwasher</th>
            <th>Jumlah Mobil</th>
            <th>Total Gaji</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $historyQuery = mysqli_query($conn, "
            SELECT g.tanggal, k.nama, g.jumlah_mobil, g.total_gaji
            FROM gaji_harian g
            JOIN karyawan k ON g.id_karyawan = k.id
            WHERE g.tanggal
        ");

        if (mysqli_num_rows($historyQuery) > 0) {
            while ($row = mysqli_fetch_assoc($historyQuery)) {
                echo "<tr>
                    <td>" . date('d M Y', strtotime($row['tanggal'])) . "</td>
                    <td>" . htmlspecialchars($row['nama']) . "</td>
                    <td>" . $row['jumlah_mobil'] . "</td>
                    <td>Rp " . number_format($row['total_gaji'], 0, ',', '.') . "</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>Belum ada data gaji sebelumnya.</td></tr>";
        }
        ?>
    </tbody>
</table>

</html>
