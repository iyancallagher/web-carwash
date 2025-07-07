<?php
require "session.php";
require "koneksi.php";

// =========================================
// 1. Ambil dan proses kebutuhan harian (multi‑item)
// =========================================

// Setiap baris terdiri dari keterangan belanja & nominal
$keteranganList       = $_POST['keterangan']       ?? [];
$kebutuhanNominalList = $_POST['kebutuhan_nominal'] ?? [];

$totalKebutuhan = 0;
$detailKebutuhan = [];

foreach ($kebutuhanNominalList as $idx => $nominal) {
    // Pastikan angka & valid
    $nominal = (int) $nominal;
    $ket     = isset($keteranganList[$idx]) ? trim($keteranganList[$idx]) : '';

    if ($nominal > 0) {
        $totalKebutuhan   += $nominal;
        $detailKebutuhan[] = [
            'keterangan' => $ket,
            'nominal'    => $nominal,
        ];
    }
}

// Simpan detail kebutuhan dalam bentuk JSON untuk laporan_close
$detailKebutuhanJSON = json_encode($detailKebutuhan, JSON_UNESCAPED_UNICODE);

// =========================================
// 2. Hitung omset, gaji, dll. (tidak berubah)
// =========================================

$omsetQuery   = mysqli_query($conn, "SELECT SUM(harga) AS total_omset FROM pelanggan WHERE status = 'selesai' AND tanggal = CURDATE()");
$omsetData    = mysqli_fetch_assoc($omsetQuery);
$totalOmset   = $omsetData['total_omset'] ?? 0;

$totalMobilQuery = mysqli_query($conn, "SELECT COUNT(*) AS total_mobil FROM pelanggan WHERE status = 'selesai' AND tanggal = CURDATE()");
$totalMobil      = mysqli_fetch_assoc($totalMobilQuery)['total_mobil'] ?? 0;

$gajiQuery = mysqli_query($conn, "
    SELECT 
        karyawan.id,
        karyawan.nama,
        COUNT(*)                 AS jumlah_cucian,
        SUM(pelanggan.harga)      AS total_pemasukan
    FROM pelanggan
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.status = 'selesai' AND pelanggan.tanggal = CURDATE()
    GROUP BY karyawan.id
    ORDER BY total_pemasukan DESC
");

$totalGaji   = 0;
$carwashers  = [];

while ($row = mysqli_fetch_assoc($gajiQuery)) {
    $jumlahCucian   = $row['jumlah_cucian'];
    $totalPemasukan = $row['total_pemasukan'];
    $gaji           = ($totalPemasukan * 0.4) - (4000 * $jumlahCucian);

    $row['gaji'] = $gaji;
    $carwashers[] = $row;
    $totalGaji   += $gaji;
}

// =========================================
// 3. Ringkasan bersih
// =========================================
$terimaBersih = $totalOmset - $totalGaji - $totalKebutuhan;

// =========================================
// 4. Proses Close Order
// =========================================
if (isset($_POST['close_order'])) {
    $tanggal   = $_POST['tanggal_laporan'];
    $omset     = $_POST['total_omset'];
    $gaji      = $_POST['total_gaji'];
    $kebutuhan = $_POST['total_kebutuhan'];
    $bersih    = $_POST['terima_bersih'];
    $detailJSON= $_POST['detail_kebutuhan_json'];

    $cek = mysqli_query($conn, "SELECT * FROM laporan_harian WHERE tanggal = '$tanggal'");
    if (mysqli_num_rows($cek) == 0) {
        // *** Tambahkan kolom detail_kebutuhan (TEXT) di tabel laporan_harian ***
        $simpan = mysqli_query($conn, "
            INSERT INTO laporan_harian (tanggal, omset, total_gaji, kebutuhan, terima_bersih, detail_kebutuhan)
            VALUES ('$tanggal', '$omset', '$gaji', '$kebutuhan', '$bersih', '$detailJSON')
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

    <!-- =============================
         FORM INPUT KEBUTUHAN MULTI‑ITEM
         ============================= -->
    <h4 class="mt-4">Ringkasan Keuangan</h4>

    <div class="row">
        <!-- Form kebutuhan -->
        <div class="col-md-6">
            <form method="POST" id="formKebutuhan" class="mb-4">
                <table class="table table-bordered" id="tblKebutuhan">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60%">Keterangan</th>
                            <th style="width:30%">Nominal (Rp)</th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Tampilkan baris yang sudah di‑POST (jika ada) atau satu baris kosong default
                        $rowCount = max(count($keteranganList), 1);
                        for ($i = 0; $i < $rowCount; $i++) {
                            $ket = htmlspecialchars($keteranganList[$i]       ?? '', ENT_QUOTES);
                            $nom = htmlspecialchars($kebutuhanNominalList[$i] ?? '', ENT_QUOTES);
                            echo "<tr>
                                    <td><input type='text' name='keterangan[]' class='form-control' value='$ket' required></td>
                                    <td><input type='number' name='kebutuhan_nominal[]' class='form-control' value='$nom' required></td>
                                    <td class='text-center align-middle'><button type='button' class='btn btn-sm btn-danger remove-row'>&times;</button></td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary btn-sm" id="addRow">Tambah Baris</button>
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
                    <th>Total Kebutuhan</th>
                    <td>Rp <?= number_format($totalKebutuhan, 0, ',', '.') ?></td>
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
        <input type="hidden" name="total_kebutuhan" value="<?= $totalKebutuhan ?>">
        <input type="hidden" name="terima_bersih" value="<?= $terimaBersih ?>">
        <input type="hidden" name="detail_kebutuhan_json" value='<?= htmlspecialchars($detailKebutuhanJSON, ENT_QUOTES) ?>'>
        <button type="submit" name="close_order" class="btn btn-danger mt-3">Close Order</button>
    </form>

    <hr class="my-5">

    <!-- =============================
         TABEL LAPORAN SEBELUMNYA
         ============================= -->
    <h4>Semua Laporan</h4>

    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>Tanggal</th>
                <th>Omset</th>
                <th>Total Gaji</th>
                <th>Kebutuhan</th>
                <th>Terima Bersih</th>
                <th>Detail</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $laporanQuery = mysqli_query($conn, "SELECT * FROM laporan_harian ORDER BY tanggal DESC");
            if (mysqli_num_rows($laporanQuery) > 0) {
                while ($laporan = mysqli_fetch_assoc($laporanQuery)) {
                    $detailIcon = $laporan['detail_kebutuhan'] ? '<button class="btn btn-sm btn-secondary" data-bs-toggle="collapse" data-bs-target="#detail'. $laporan['id'] .'">Lihat</button>' : '-';
                    echo "<tr>
                            <td>" . date('d M Y', strtotime($laporan['tanggal'])) . "</td>
                            <td>Rp " . number_format($laporan['omset'], 0, ',', '.') . "</td>
                            <td>Rp " . number_format($laporan['total_gaji'], 0, ',', '.') . "</td>
                            <td>Rp " . number_format($laporan['kebutuhan'], 0, ',', '.') . "</td>
                            <td>Rp " . number_format($laporan['terima_bersih'], 0, ',', '.') . "</td>
                            <td class='text-center'>$detailIcon</td>
                            <td><a href='cetak_laporan.php?tanggal={$laporan['tanggal']}' target='_blank' class='btn btn-sm btn-secondary'>Cetak</a></td>
                          </tr>";

                    // Baris detail collapsible
                    if ($laporan['detail_kebutuhan']) {
                        echo "<tr class='collapse' id='detail{$laporan['id']}'>
                                <td colspan='7'>";
                        echo "<strong>Rincian Kebutuhan:</strong><br><ul class='mb-0'>";
                        foreach (json_decode($laporan['detail_kebutuhan'], true) as $det) {
                            echo "<li>" . htmlspecialchars($det['keterangan']) . " : Rp " . number_format($det['nominal'], 0, ',', '.') . "</li>";
                        }
                        echo "</ul></td></tr>";
                    }
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>Belum ada laporan sebelumnya.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Tambah & hapus baris kebutuhan
    document.getElementById('addRow').addEventListener('click', function () {
        const tbody = document.querySelector('#tblKebutuhan tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="keterangan[]" class="form-control" required></td>
            <td><input type="number" name="kebutuhan_nominal[]" class="form-control" required></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger remove-row">&times;</button></td>
        `;
        tbody.appendChild(tr);
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
        }
    });
    </script>
</body>
</html>
