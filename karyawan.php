<?php 
require 'session.php';
require 'koneksi.php';

$queryKaryawan = mysqli_query($conn, "SELECT * FROM karyawan WHERE role = 'karyawan' ORDER BY id ASC");
$jumlahKaryawan = mysqli_num_rows($queryKaryawan);

// Tambah data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nama'], $_POST['role'])) {
    $nama = $_POST['nama'];
    $role = $_POST['role'];
    $query = "INSERT INTO karyawan (nama, role) VALUES ('$nama', '$role')";
    echo mysqli_query($conn, $query) ? "success" : "error";
    exit();
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM karyawan WHERE id='$id'");
    echo "<script>
        alert('Data berhasil dihapus');
        window.location='karyawan.php';
    </script>";
}

// Edit data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $nama = $_POST['edit_nama'];
    $role = $_POST['edit_role'];
    mysqli_query($conn, "UPDATE karyawan SET nama='$nama', role='$role' WHERE id='$id'");
    echo "<script>
        alert('Data berhasil diperbarui');
        window.location='karyawan.php';
    </script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Karyawan - Master Snow</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Bootstrap & Bootstrap Table -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            display: flex;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background-color: #f1f4f9;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background-color: #1b2d3c;
            color: white;
            padding: 20px 0;
        }

        .content {
            margin-top: 80px;
            width: 100%;
            padding: 30px;
        }

        h2 {
            color: #1b2d3c;
            font-weight: 600;
        }

        .table-container {
            margin-top: 20px;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background-color: #1b2d3c;
            border: none;
        }

        .btn-primary:hover {
            background-color: #27445d;
        }

        .table thead {
            background-color: #1b2d3c;
            color: white;
        }

        .table tbody tr:hover {
            background-color: #f4f8fc;
        }

        .modal-header {
            background-color: #1b2d3c;
            color: white;
        }

        .modal-title {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h2>Data Karyawan</h2>
        <div class="table-container">
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fa-solid fa-user-plus"></i> Tambah Karyawan
            </button>
            <table class="table table-bordered table-striped" data-toggle="table" data-search="true" data-pagination="true">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jumlahKaryawan == 0): ?>
                        <tr><td colspan="4" class="text-center">Tidak ada data karyawan.</td></tr>
                    <?php else:
                        $no = 1;
                        while ($data = mysqli_fetch_array($queryKaryawan)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= ucwords(strtolower($data['nama'])); ?></td>
                            <td><?= ucwords($data['role']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openEditModal(<?= $data['id']; ?>, '<?= $data['nama']; ?>', '<?= $data['role']; ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="hapusData(<?= $data['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade mt-5" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <form id="formTambah" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select class="form-control" name="role" required>
                            <option value="" disabled selected>Pilih Role</option>
                            <option value="karyawan">Karyawan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade mt-5" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="karyawan.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="editId">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="edit_nama" id="editNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="edit_role" id="editRole" class="form-control" required>
                            <option value="karyawan">Carwasher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.js"></script>
    <script>
        function hapusData(id) {
            Swal.fire({
                title: 'Yakin hapus?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'karyawan.php?hapus=' + id;
                }
            });
        }

        function openEditModal(id, nama, role) {
            document.getElementById('editId').value = id;
            document.getElementById('editNama').value = nama;
            document.getElementById('editRole').value = role;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        document.getElementById("formTambah").addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch("karyawan.php", { method: "POST", body: formData })
                .then(res => res.text())
                .then(data => {
                    if (data === "success") {
                        Swal.fire("Berhasil!", "Data berhasil ditambahkan.", "success").then(() => location.reload());
                    } else {
                        Swal.fire("Gagal!", "Terjadi kesalahan saat menyimpan.", "error");
                    }
                });
        });
    </script>
</body>
</html>
