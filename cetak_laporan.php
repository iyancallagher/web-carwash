<?php
require 'vendor/autoload.php';
require 'koneksi.php';
session_start(); // PENTING!

$username = $_SESSION['username'] ?? 'karyawan';

$kebutuhan = $_GET['kebutuhan'] ?? 0;
use Dompdf\Dompdf;
// Ambil laporan terbaru
$laporan = mysqli_query($conn, "SELECT * FROM laporan_harian ORDER BY tanggal DESC LIMIT 1");
$dataLaporan = mysqli_fetch_assoc($laporan);
$tanggal = $dataLaporan['tanggal'] ?? date('Y-m-d');

// Ambil data laporan terakhir (atau sesuaikan dengan WHERE tanggal = CURDATE())
$query = mysqli_query($conn, "SELECT * FROM laporan_harian ORDER BY tanggal DESC LIMIT 1");
$data = mysqli_fetch_assoc($query);

// Cek jika data ada
if (!$data) {
    die("Tidak ada data laporan ditemukan.");
}
// Ambil data cucian karyawan untuk tanggal laporan
$queryCarwasher = mysqli_query($conn, "
    SELECT 
        karyawan.nama,
        COUNT(*) AS jumlah_cucian,
        SUM(pelanggan.harga) AS total_pemasukan,
        (SUM(pelanggan.harga) * 0.4 - COUNT(*) * 4000) AS gaji
    FROM pelanggan
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.status = 'selesai' AND pelanggan.tanggal = '$tanggal'
    GROUP BY karyawan.id
    ORDER BY total_pemasukan DESC
");

$carwashers = [];
while ($row = mysqli_fetch_assoc($queryCarwasher)) {
    $carwashers[] = $row;
}

ob_start(); // mulai tangkap HTML
?>


<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        .summary {
            margin-top: 20px;
            margin-bottom: 30px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f2f2f2;
        }
        .summary p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #999;
        }
        th {
            background-color: #00b894;
            color: white;
            padding: 8px;
        }
        td {
            padding: 6px;
        }
    </style>
</head>
<body>

<h2>Laporan Harian - <?= date('d M Y', strtotime($data['tanggal'])) ?></h2>
<p><strong>Admin :</strong> <?= htmlspecialchars($username) ?></p>
<table>
    <tr>
        <td class="label">Omset</td>
        <td>Rp <?= number_format($data['omset'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td class="label">Total Gaji</td>
        <td>Rp <?= number_format($data['total_gaji'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td class="label">Kebutuhan</td>
        <td>Rp <?= number_format($data['kebutuhan'], 0, ',', '.') ?></td>
    </tr>
    <tr>
        <td class="label"><strong>Terima Bersih</strong></td>
        <td><strong>Rp <?= number_format($data['terima_bersih'], 0, ',', '.') ?></strong></td>
    </tr>
    <tr>
        <td class="label">Dibuat pada</td>
        <td><?= date('d M Y H:i', strtotime($data['created_at'])) ?></td>
    </tr>
</table>
</body>
</html>

<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("laporan-harian.pdf", ["Attachment" => false]);
exit;
