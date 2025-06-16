<?php
require "session.php";
require "koneksi.php";

$tanggalHariIni = date('Y-m-d');
$bulanIni = date('Y-m');

// Omset Hari Ini
$harianResult = mysqli_query($conn, "SELECT SUM(harga) AS omset_hari FROM pelanggan WHERE status='selesai' AND tanggal = '$tanggalHariIni'");
$omsetHari = mysqli_fetch_assoc($harianResult)['omset_hari'] ?? 0;

// Omset Bulanan
$bulananResult = mysqli_query($conn, "SELECT SUM(harga) AS omset_bulan FROM pelanggan WHERE status='selesai' AND tanggal LIKE '$bulanIni%'");
$omsetBulan = mysqli_fetch_assoc($bulananResult)['omset_bulan'] ?? 0;

// Statistik
$totalPelangganResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan WHERE tanggal = '$tanggalHariIni'");
$totalPelanggan = mysqli_fetch_assoc($totalPelangganResult)['total'] ?? 0;

$selesaiResult = mysqli_query($conn, "SELECT COUNT(*) AS selesai FROM pelanggan WHERE tanggal = '$tanggalHariIni' AND status='selesai'");
$jumlahSelesai = mysqli_fetch_assoc($selesaiResult)['selesai'] ?? 0;

$pending = $totalPelanggan - $jumlahSelesai;
$persenSelesai = $totalPelanggan > 0 ? round(($jumlahSelesai / $totalPelanggan) * 100) : 0;

// Omset Harian Bulan Ini
$dailyOmsetQuery = mysqli_query($conn, "
    SELECT DATE(tanggal) AS hari, SUM(harga) AS total 
    FROM pelanggan 
    WHERE status = 'selesai' AND tanggal LIKE '$bulanIni%' 
    GROUP BY hari ORDER BY hari ASC
");

$labels = [];
$values = [];

while ($row = mysqli_fetch_assoc($dailyOmsetQuery)) {
    $labels[] = date('j M', strtotime($row['hari']));
    $values[] = (int)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Master Snow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f1f3f5;
            font-family: 'Poppins', sans-serif;
            color: #495057;
        }
        .content {
            margin-top: 50px;
            flex-grow: 1;
            padding: 20px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            font-weight: 600;
            text-align: center;
        }
        .progress {
            height: 10px;
        }
        @media screen and (max-width: 768px) {
            .content { margin-top: 50px; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2 class="mb-4 text-dark">Dashboard</h2>
            <div class="row">
                <!-- Omset Bulanan -->
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <div class="card-header">Omset Bulanan</div>
                        <div class="card-body">
                            <h4>Rp <?= number_format($omsetBulan, 0, ',', '.') ?></h4>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Omset Harian -->
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <div class="card-header">Omset Hari Ini</div>
                        <div class="card-body">
                            <h4>Rp <?= number_format($omsetHari, 0, ',', '.') ?></h4>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Persentase Selesai -->
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <div class="card-header">Selesai Hari Ini</div>
                        <div class="card-body">
                            <h4><?= $persenSelesai ?>%</h4>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: <?= $persenSelesai ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Pending -->
                <div class="col-md-3 mb-4">
                    <div class="card p-3">
                        <div class="card-header">Pending Hari Ini</div>
                        <div class="card-body">
                            <h4><?= $pending ?></h4>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: <?= ($totalPelanggan > 0) ? round(($pending / $totalPelanggan) * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Omset Harian -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card p-4">
                        <h5 class="mb-3">Grafik Omset Harian (<?= date('F Y') ?>)</h5>
                        <canvas id="omsetChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('omsetChart').getContext('2d');
        const omsetChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Omset (Rp)',
                    data: <?= json_encode($values) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
