<?php
// cetak_laporan.php – Generate PDF laporan harian (versi estetis, tetap kompatibel Dompdf)
// -----------------------------------------------------------------------------
// • Pastikan kolom detail_kebutuhan (TEXT) sudah ada pada tabel laporan_harian.
// • Dompdf tetap memerlukan HTML + CSS sederhana; hindari flexbox/grid modern.
// -----------------------------------------------------------------------------

require 'vendor/autoload.php';
require 'koneksi.php';
session_start();

use Dompdf\Dompdf;

//--------------------------------------------------
// 1. Session & Parameter
//--------------------------------------------------
$username = $_SESSION['username'] ?? 'karyawan';
$tglParam = $_GET['tanggal'] ?? null;              // ?tanggal=YYYY-MM-DD

//--------------------------------------------------
// 2. Ambil Data Laporan
//--------------------------------------------------
if ($tglParam) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM laporan_harian WHERE tanggal = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $tglParam);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
} else {
    $res  = mysqli_query($conn, "SELECT * FROM laporan_harian ORDER BY tanggal DESC LIMIT 1");
    $data = mysqli_fetch_assoc($res);
}

if (!$data) {
    die('Tidak ada data laporan ditemukan.');
}

$tanggal      = $data['tanggal'];
$detailJSON   = $data['detail_kebutuhan'] ?? '[]';
$details      = json_decode($detailJSON, true) ?: [];

//--------------------------------------------------
// 3. Data Gaji per Carwasher
//--------------------------------------------------
$stmtCar = mysqli_prepare($conn, "
    SELECT karyawan.nama,
           COUNT(*)                                    AS cucian,
           SUM(pelanggan.harga)                        AS pemasukan,
           (SUM(pelanggan.harga) * 0.4 - COUNT(*)*4000)AS gaji
    FROM pelanggan
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.status = 'selesai' AND pelanggan.tanggal = ?
    GROUP BY karyawan.id
    ORDER BY pemasukan DESC
");


//--------------------------------------------------
// 4. HTML & Styling
//--------------------------------------------------
ob_start(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    /** Basic reset **/
    *{ box-sizing:border-box; }
    body{ font-family:DejaVu Sans, sans-serif; font-size:12.5px; color:#222; margin:0 10px; }
    h1,h2,h3{ margin:0; }
    h2{ text-align:center; color:#2ecc71; margin-top:10px; }
    p.meta{ margin:4px 0 12px; }

    /* Helpers */
    .text-center{ text-align:center; }
    .text-right { text-align:right; }

    /* Table skeleton */
    table{ width:100%; border-collapse:collapse; margin-top:6px; }
    th,td{ border:1px solid #888; padding:5px 6px; }
    th{ background:#2ecc71; color:#fff; font-weight:bold; }
    tr:nth-child(even) td{ background:#f3fdf6; }

    /* Summary (no outer border) */
    table.summary td{ border:none; padding:4px 6px; }
    table.summary td.label{ width:40%; font-weight:bold; }
    table.summary tr:nth-child(odd) td{ background:#f8f8f8; }

    /* Avoid page break inside long section */
    .avoid-break{ page-break-inside:avoid; }
</style>
</head>
<body>

<h2>Laporan Harian – <?= date('d M Y', strtotime($tanggal)) ?></h2>
<p class="meta"><strong>Disusun oleh:</strong> <?= htmlspecialchars($username) ?></p>

<!-- Ringkasan Keuangan -->
<table class="summary avoid-break">
    <tr><td class="label">Omset</td>          <td class="text-right">Rp <?= number_format($data['omset'],0,',','.') ?></td></tr>
    <tr><td class="label">Total Gaji</td>     <td class="text-right">Rp <?= number_format($data['total_gaji'],0,',','.') ?></td></tr>
    <tr><td class="label">Total Kebutuhan</td><td class="text-right">Rp <?= number_format($data['kebutuhan'],0,',','.') ?></td></tr>
    <tr><td class="label"><strong>Terima Bersih</strong></td>
        <td class="text-right"><strong>Rp <?= number_format($data['terima_bersih'],0,',','.') ?></strong></td></tr>
    <tr><td class="label">Close order</td>    <td><?= date('d M Y H:i', strtotime($data['created_at'])) ?></td></tr>
</table>

<?php if ($details): ?>
<h3>Rincian Kebutuhan</h3>
<table class="avoid-break">
    <thead>
        <tr><th style="width:5%">No</th><th>Keterangan</th><th style="width:25%">Nominal (Rp)</th></tr>
    </thead>
    <tbody>
    <?php foreach ($details as $i=>$det): ?>
        <tr>
            <td class="text-center"><?= $i+1 ?></td>
            <td><?= htmlspecialchars($det['keterangan']) ?></td>
            <td class="text-right"><?= number_format($det['nominal'],0,',','.') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

//--------------------------------------------------
// 5. Output via Dompdf
//--------------------------------------------------
$dompdf = new Dompdf([
    'defaultFont' => 'DejaVu Sans'
]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('laporan-harian-' . $tanggal . '.pdf', [ 'Attachment' => false ]);
exit;