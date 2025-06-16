<?php 
require 'session.php';
require 'koneksi.php';

$queryJenisMobil = mysqli_query($conn, "SELECT * FROM jenis_mobil");
$data = mysqli_fetch_array($queryJenisMobil);
$jumlahJenisMobil = mysqli_num_rows($queryJenisMobil);

// Fungsi tambah data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['merk']) && isset($_POST['harga'])) {
        $merk = $_POST['merk'];
        $harga = $_POST['harga'] . '000';

        $query = "INSERT INTO jenis_mobil (merk, harga) VALUES ('$merk', '$harga')";
        echo mysqli_query($conn, $query) ? "success" : "error";
        exit();
    }
}

// Fungsi hapus data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM jenis_mobil WHERE id='$id'");
    echo "<script>
        alert('Data berhasil dihapus');
        window.location='harga.php';
    </script>";
}

// Fungsi edit data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $merk = $_POST['edit_merk'];
    $harga = $_POST['edit_harga'];

    mysqli_query($conn, "UPDATE jenis_mobil SET merk='$merk', harga='$harga' WHERE id='$id'");
    echo "<script>
        alert('Data berhasil diperbarui');
        window.location='harga.php';
    </script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Harga - Master Snow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS dan Font -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">


    <!-- Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #27445D;
            padding-top: 20px;
            overflow: hidden;
            z-index: 1000;
        }

        .content {
            margin-top: 80px;
            padding: 20px;
            flex-grow: 1;
        }

        @media screen and (max-width: 768px) {
            .content {
                margin-left: 0;
                margin-top: 50px;
            }
        }

        .table-container {
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background-color: #27445D;
            color: #fff;
            font-weight: 600;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f0f4f7;
        }

        .table tbody tr:hover {
            background-color: #e3e9f0;
        }

        .table td, .table th {
            vertical-align: middle !important;
            padding: 12px;
        }

        .btn {
            font-size: 14px;
        }

        .btn-primary {
            background-color: #27445D;
            border-color: #27445D;
        }

        .btn-primary:hover {
            background-color: #1b2d3c;
            border-color: #1b2d3c;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
            <h2>Data Harga</h2>  

            <div class="table-container">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fa-solid fa-plus"></i> Tambah
                </button>
                <table data-toggle="table" data-search="true" data-pagination="true" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Merk</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($jumlahJenisMobil == 0) {
                            echo '<tr><td colspan="4" class="text-center">Data Harga Tidak Ada</td></tr>';
                        } else {
                            $no = 1;
                            mysqli_data_seek($queryJenisMobil, 0); // ulangi pointer
                            while ($data = mysqli_fetch_array($queryJenisMobil)) {
                                echo "<tr id='row-{$data['id']}'>
                                        <td>$no</td>
                                        <td>" . ucwords(strtolower($data['merk'])) . "</td>
                                        <td>Rp. " . number_format($data['harga'], 0, ',', '.') . "</td>
                                        <td>
                                            <button class='btn btn-primary' onclick=\"openEditModal('{$data['id']}', '{$data['merk']}', '{$data['harga']}')\"><i class='fa-solid fa-pen-to-square'></i></button>
                                            <button class='btn btn-danger' onclick=\"hapusData('{$data['id']}')\"><i class='fa-solid fa-trash'></i></button>
                                        </td>
                                    </tr>";
                                $no++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah -->
            <div class="modal fade mt-5" id="modalTambah" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Tambah Harga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formTambah">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Merk Mobil</label>
                                    <input type="text" class="form-control" name="merk" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Harga (Contoh: 65)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp.</span>
                                        <input type="number" class="form-control" name="harga" required>
                                        <span class="input-group-text">.000</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Edit -->
            <div class="modal fade mt-5" id="editModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Data Harga</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="edit_id" id="editId">
                            <div class="mb-3">
                                <label class="form-label">Merk</label>
                                <input type="text" name="edit_merk" id="editMerk" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga</label>
                                <input type="text" name="edit_harga" id="editHarga" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
    </div>

<script>
function hapusData(id) {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, hapus!"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "harga.php?hapus=" + id;
        }
    });
}

function openEditModal(id, merk, harga) {
    document.getElementById("editId").value = id;
    document.getElementById("editMerk").value = merk;
    document.getElementById("editHarga").value = harga;
    new bootstrap.Modal(document.getElementById("editModal")).show();
}

document.getElementById("formTambah").addEventListener("submit", function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    fetch("harga.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        if (data === "success") {
            Swal.fire("Berhasil!", "Data berhasil ditambahkan.", "success").then(() => location.reload());
        } else {
            Swal.fire("Gagal!", "Terjadi kesalahan saat menambahkan data.", "error");
        }
    });
});
</script>

</body>
</html>
