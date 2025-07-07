<?php
require "session.php";
require "koneksi.php";

// Reset pesan
unset($error, $success);

// Hapus data pelanggan
if (isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $delete = mysqli_query($conn, "DELETE FROM pelanggan WHERE id = $id");
    if ($delete) {
        $success = "Data berhasil dihapus!";
    } else {
        $error = "Gagal menghapus data: " . mysqli_error($conn);
    }
}
// Update status pelanggan
elseif (isset($_POST['update_status'])) {
    $id_pelanggan = (int) $_POST['id_pelanggan'];
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);

    $update = mysqli_query($conn, "UPDATE pelanggan SET status = '$status_baru' WHERE id = $id_pelanggan");

    if (!$update) {
        $error = "Gagal memperbarui status: " . mysqli_error($conn);
    } else {
        $success = "Status berhasil diperbarui!";
    }
}
// Input pelanggan baru
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_id']) && !isset($_POST['update_status'])) {
    $plat_nomor = isset($_POST['plat_nomor']) ? mysqli_real_escape_string($conn, $_POST['plat_nomor']) : '';
    $warna = isset($_POST['warna']) ? mysqli_real_escape_string($conn, $_POST['warna']) : '';
    $id_jenis_mobil = isset($_POST['id_jenis_mobil']) ? (int) $_POST['id_jenis_mobil'] : 0;
    $id_karyawan = isset($_POST['id_karyawan']) ? (int) $_POST['id_karyawan'] : 0;
    $harga = isset($_POST['harga']) ? (int) $_POST['harga'] : 0;
    $harga_tambahan = isset($_POST['hargaTambahan']) ? (int) $_POST['hargaTambahan'] : 0;
    $total_harga = $harga + $harga_tambahan;

    if ($plat_nomor && $warna && $id_jenis_mobil && $id_karyawan && $harga) {
        $insert = mysqli_query($conn, "INSERT INTO pelanggan (plat_nomor, warna, id_jenis_mobil, carwasher, harga, harga_tambahan, status, tanggal)
            VALUES ('$plat_nomor', '$warna', '$id_jenis_mobil', '$id_karyawan', '$harga', '$harga_tambahan', 'pending', CURDATE())");

        if (!$insert) {
            $error = "Gagal menambahkan data: " . mysqli_error($conn);
        } else {
            $success = "Data berhasil ditambahkan!";
        }
    } else {
        $error = "Lengkapi semua data terlebih dahulu!";
    }
}

// Ambil data dropdown jenis mobil dan karyawan
$queryJenisMobil = mysqli_query($conn, "SELECT * FROM jenis_mobil");
$queryKaryawan = mysqli_query($conn, "SELECT * FROM karyawan WHERE role = 'karyawan'");

// Ambil data pelanggan yang statusnya bukan 'selesai' dan tanggal hari ini
$queryPelanggan = mysqli_query($conn, "
    SELECT 
        pelanggan.id,
        pelanggan.plat_nomor,
        pelanggan.warna,
        pelanggan.harga,
        pelanggan.harga_tambahan,
        pelanggan.status,
        jenis_mobil.merk,
        karyawan.nama AS nama_karyawan
    FROM pelanggan
    JOIN jenis_mobil ON pelanggan.id_jenis_mobil = jenis_mobil.id
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.tanggal = CURDATE() AND pelanggan.status != 'Selesai'
    ORDER BY pelanggan.id DESC
");

$queryPelangganSelesai = mysqli_query($conn, "
    SELECT 
        pelanggan.id,
        pelanggan.plat_nomor,
        pelanggan.warna,
        pelanggan.harga,
        pelanggan.harga_tambahan,
        pelanggan.status,
        jenis_mobil.merk,
        karyawan.nama AS nama_karyawan
    FROM pelanggan
    JOIN jenis_mobil ON pelanggan.id_jenis_mobil = jenis_mobil.id
    JOIN karyawan ON pelanggan.carwasher = karyawan.id
    WHERE pelanggan.tanggal = CURDATE() AND pelanggan.status = 'Selesai'
    ORDER BY pelanggan.id DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Sistem - Master Snow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .content {
            margin-top: 80px;
            flex-grow: 1;
            padding: 20px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow-x: auto;
        }
        .table thead {
            background-color: #1b2d3c;
            color: white;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table td {
            white-space: nowrap;
        }
        @media screen and (max-width: 768px) {
            .content {
                margin-left: 0;
                margin-top: 10px;
            }
            .table-container {
                padding: 10px;
                margin-bottom: 20px;
            }
            .table-responsive {
                overflow-x: auto;
            }
            .table th, .table td {
                padding: 10px;
                font-size: 12px;
            }
            .table th {
                font-size: 14px;
            }
            .form-control, .form-select {
                font-size: 12px;
            }
            .btn {
                font-size: 14px;
                padding: 6px 12px;
            }
        }
        @media screen and (max-width: 576px) {
            .table-container {
                padding: 5px;
            }
            .table th, .table td {
                font-size: 10px;
                padding: 8px;
            }
            .btn {
                font-size: 12px;
                padding: 4px 8px;
            }
            .form-control, .form-select {
                font-size: 10px;
            }
            .content h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="container">
        <h2 class="mb-4">Bayar / Input Pelanggan</h2>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php } elseif (isset($success)) { ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php } ?>

        <div class="table-container">
            <!-- Form Input -->
            <form method="POST" action="">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Merk Mobil</th>
                            <th>Warna</th>
                            <th>Plat Nomor</th>
                            <th>Petugas</th>
                            <th>Harga Awal</th>
                            <th>Harga Tambahan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="id_jenis_mobil" id="merkSelect" class="form-select" onchange="updateHarga()" required>
                                    <option value="" disabled selected>Pilih Merk</option>
                                    <?php while ($row = mysqli_fetch_assoc($queryJenisMobil)) { ?>
                                        <option value="<?= $row['id'] ?>" data-harga="<?= $row['harga'] ?>">
                                            <?= $row['merk'] ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td><input type="text" name="warna" class="form-control" required placeholder="Warna" /></td>
                            <td><input type="text" name="plat_nomor" class="form-control" required placeholder="Plat Nomor" /></td>
                            <td>
                                <select name="id_karyawan" class="form-select" required>
                                    <option value="" disabled selected>Pilih Petugas</option>
                                    <?php
                                    // reset pointer agar fetch bisa kembali
                                    mysqli_data_seek($queryKaryawan, 0);
                                    while ($k = mysqli_fetch_assoc($queryKaryawan)) {
                                        echo "<option value='" . $k['id'] . "'>" . $k['nama'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="number" name="harga" id="hargaInput" class="form-control" required placeholder="Harga" readonly /></td>
                            <td><input type="number" name="hargaTambahan" id="hargaTambahanInput" class="form-control" placeholder="Harga Tambahan" /></td>
                            <td><button type="submit" class="btn btn-success"><i class="fa fa-plus"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </form>

            <!-- Tabel Data Pelanggan (status != selesai) -->
            <table data-toggle="table" data-search="true" data-pagination="true" class="table table-striped mt-5">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Plat Nomor</th>
                        <th>Warna</th>
                        <th>Merk Mobil</th>
                        <th>Harga</th>
                        <th>Harga Tambahan</th>
                        <th>Total Harga</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($p = mysqli_fetch_assoc($queryPelanggan)) {
                        $totalHarga = $p['harga'] + $p['harga_tambahan'];
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($p['plat_nomor']) ?></td>
                            <td><?= htmlspecialchars($p['warna']) ?></td>
                            <td><?= htmlspecialchars($p['merk']) ?></td>
                            <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($p['harga_tambahan'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($totalHarga, 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($p['nama_karyawan']) ?></td>
                            <td>
                            <form method="POST" class="statusForm">
                                <input type="hidden" name="id_pelanggan" value="<?= $p['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="form-select statusSelect" onchange="this.form.submit()">
                                    <?php
                                    $statuses = ['menunggu', 'Dicuci', 'selesai'];
                                    foreach ($statuses as $st) {
                                        $sel = ($p['status'] == $st) ? 'selected' : '';
                                        echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                                    }
                                    ?>
                                </select>
                            </form>

                            </td>
                            <td>
                                <form method="POST" class="deleteForm">
                                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
                    <!-- Tabel Data Pelanggan (status != selesai) -->
            <table data-toggle="table" data-search="true" data-pagination="true" class="table table-striped mt-5">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Plat Nomor</th>
                        <th>Warna</th>
                        <th>Merk Mobil</th>
                        <th>Harga</th>
                        <th>Harga Tambahan</th>
                        <th>Total Harga</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($p = mysqli_fetch_assoc($queryPelangganSelesai)) {
                        $totalHarga = $p['harga'] + $p['harga_tambahan'];
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($p['plat_nomor']) ?></td>
                            <td><?= htmlspecialchars($p['warna']) ?></td>
                            <td><?= htmlspecialchars($p['merk']) ?></td>
                            <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($p['harga_tambahan'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($totalHarga, 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($p['nama_karyawan']) ?></td>
                            <td>
                           <form method="POST" class="statusForm">
                                <input type="hidden" name="id_pelanggan" value="<?= $p['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="form-select statusSelect" onchange="this.form.submit()">
                                    <?php
                                    $statuses = ['selesai', 'Dicuci', 'menunggu'];
                                    foreach ($statuses as $st) {
                                        $sel = ($p['status'] == $st) ? 'selected' : '';
                                        echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                                    }
                                    ?>
                                </select>
                            </form>


                            </td>
                            <td>
                                <form method="POST" class="deleteForm">
                                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                    <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
    </div>
</div>

<script>
function updateHarga() {
    const merkSelect = document.getElementById('merkSelect');
    const hargaInput = document.getElementById('hargaInput');
    const selectedOption = merkSelect.options[merkSelect.selectedIndex];
    const harga = selectedOption.getAttribute('data-harga');
    hargaInput.value = harga ? harga : '';
}

// SweetAlert confirm delete
document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function () {
        const form = this.closest('form');
        Swal.fire({
            title: 'Yakin ingin menghapus data?',
            text: "Data yang sudah dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        })
    });
});

// Submit update status langsung saat dropdown berubah
document.querySelectorAll('.statusSelect').forEach(select => {
    select.addEventListener('change', function () {
        const form = this.closest('form');
        form.submit();
    });
});
</script>
</body>
</html>
