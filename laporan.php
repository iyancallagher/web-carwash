<?php
require "session.php";
require "koneksi.php";

// Ambil kebutuhan dari POST jika ada
$kebutuhan = $_POST['kebutuhan'] ?? 0;

// Ambil total omset hari ini
$omsetQuery = mysqli_query($conn, "SELECT SUM(harga) AS total_omset FROM pelanggan WHERE status = 'selesai' AND tanggal = CURDATE()");
$omsetData = mysqli_fetch_assoc($omsetQuery);
$totalOmset = $omsetData['total_omset'] ?? 0;

// Ambil total mobil dicuci
$totalMobilQuery = mysqli_query($conn, "SELECT COUNT(*) AS total_mobil FROM pelanggan WHERE status = 'selesai' AND tanggal = CURDATE()");
$totalMobil = mysqli_fetch_assoc($totalMobilQuery)['total_mobil'] ?? 0;

// Ambil data gaji per carwasher
$gajiQuery = mysqli_query($conn, "
    SELECT 
        karyawan.id,
        karyawan.nama,
        COUNT(*) AS jumlah_cucian,
        SUM(pelanggan.harga) AS total_pemasukan
    FROM pelanggan
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.status = 'selesai' AND pelanggan.tanggal = CURDATE()
    GROUP BY karyawan.id
    ORDER BY total_pemasukan DESC
");

$totalGaji = 0;
$carwashers = [];

while ($row = mysqli_fetch_assoc($gajiQuery)) {
    $jumlahCucian = $row['jumlah_cucian'];
    $totalPemasukan = $row['total_pemasukan'];
    $gaji = ($totalPemasukan * 0.4) - (4000 * $jumlahCucian); // Gaji dikurangi potongan per mobil

    $row['gaji'] = $gaji;
    $carwashers[] = $row;
    $totalGaji += $gaji;
}

// Hitung sisa uang
$terimaBersih = $totalOmset - $totalGaji - $kebutuhan;

// Proses simpan laporan jika "Close Order" ditekan
if (isset($_POST['close_order'])) {
    $tanggal = $_POST['tanggal_laporan'];
    $omset = $_POST['total_omset'];
    $gaji = $_POST['total_gaji'];
    $kebutuhan = $_POST['kebutuhan'];
    $bersih = $_POST['terima_bersih'];

    $cek = mysqli_query($conn, "SELECT * FROM laporan_harian WHERE tanggal = '$tanggal'");
    if (mysqli_num_rows($cek) == 0) {
        $simpan = mysqli_query($conn, "
            INSERT INTO laporan_harian (tanggal, omset, total_gaji, kebutuhan, terima_bersih)
            VALUES ('$tanggal', '$omset', '$gaji', '$kebutuhan', '$bersih')
        ");
        if ($simpan) {
            echo "<script>
                alert('Laporan berhasil disimpan!');
                window.open('cetak_laporan.php?tanggal=$tanggal', '_blank');
            </script>";
        } else {
            echo "<script>alert('Gagal menyimpan laporan!');</script>";
        }
    } else {
        echo "<script>alert('Laporan untuk hari ini sudah tersimpan.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Harian</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            margin-top: 80px;
        }
    </style>
</head>

<?php include "sidebar.php"; ?>

<body class="container">
    <h2 class="mb-4">Laporan Harian - <?= date('d M Y') ?></h2>

    <div class="alert alert-info">
        <strong>Total Omset Hari Ini:</strong> Rp <?= number_format($totalOmset, 0, ',', '.') ?><br>
        <strong>Total Mobil Dicuci:</strong> <?= $totalMobil ?> mobil
    </div>

    <h4 class="mt-4">Ringkasan Keuangan</h4>

    <div class="row">
        <!-- Form kebutuhan -->
        <div class="col-md-4">
            <form method="POST" class="mb-4">
                <label for="kebutuhan" class="form-label">Input Kebutuhan Harian (Rp):</label>
                <input type="number" name="kebutuhan" id="kebutuhan" value="<?= htmlspecialchars($kebutuhan) ?>" class="form-control" required>
                <button type="submit" class="btn btn-primary mt-2">Terapkan</button>
            </form>
        </div>

        <!-- Tabel ringkasan -->
        <div class="col-md-6">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>Total Omset</th>
                    <td>Rp <?= number_format($totalOmset, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Total Gaji</th>
                    <td>Rp <?= number_format($totalGaji, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th>Kebutuhan Harian</th>
                    <td>Rp <?= number_format($kebutuhan, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <th><strong>Terima Bersih</strong></th>
                    <td><strong>Rp <?= number_format($terimaBersih, 0, ',', '.') ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tombol Close Order -->
    <form method="POST">
        <input type="hidden" name="tanggal_laporan" value="<?= date('Y-m-d') ?>">
        <input type="hidden" name="total_omset" value="<?= $totalOmset ?>">
        <input type="hidden" name="total_gaji" value="<?= $totalGaji ?>">
        <input type="hidden" name="kebutuhan" value="<?= $kebutuhan ?>">
        <input type="hidden" name="terima_bersih" value="<?= $terimaBersih ?>">
        <button type="submit" name="close_order" class="btn btn-danger mt-3">Close Order</button>
    </form>
    <hr class="my-5">

<h4>Semua laporan</h4>

<table class="table table-bordered table-striped mt-3">
    <thead class="table-dark">
        <tr>
            <th>Tanggal</th>
            <th>Omset</th>
            <th>Total Gaji</th>
            <th>Kebutuhan</th>
            <th>Terima Bersih</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Ambil 10 laporan terakhir (tidak termasuk hari ini)
        $laporanQuery = mysqli_query($conn, "
            SELECT * FROM laporan_harian
        ");

        if (mysqli_num_rows($laporanQuery) > 0) {
            while ($laporan = mysqli_fetch_assoc($laporanQuery)) {
                echo "<tr>
                    <td>" . date('d M Y', strtotime($laporan['tanggal'])) . "</td>
                    <td>Rp " . number_format($laporan['omset'], 0, ',', '.') . "</td>
                    <td>Rp " . number_format($laporan['total_gaji'], 0, ',', '.') . "</td>
                    <td>Rp " . number_format($laporan['kebutuhan'], 0, ',', '.') . "</td>
                    <td>Rp " . number_format($laporan['terima_bersih'], 0, ',', '.') . "</td>
                    <td><a href='cetak_laporan.php?tanggal={$laporan['tanggal']}' target='_blank' class='btn btn-sm btn-secondary'>Cetak</a></td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center'>Belum ada laporan sebelumnya.</td></tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>
