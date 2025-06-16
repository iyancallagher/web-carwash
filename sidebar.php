<?php
require "koneksi.php";
$username = $_SESSION['username'] ?? 'karyawan';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sidebar Elegan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins&display=swap');

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #f0f2f5;
    }

    /* Navbar */
    .navbar {
      background-color: #1d3557;
      height: 56px;
      z-index: 1100;
      padding: 0 1rem;
    }
    .navbar .navbar-brand {
      font-weight: 700;
      color: #fff;
      font-size: 1.25rem;
    }
    .navbar .user-info {
      color: #f1f1f1;
      font-weight: 500;
      font-size: 0.95rem;
    }
    #burgerBtn {
      border: none;
      font-size: 1.3rem;
      color: #f1f1f1;
      background: transparent;
      padding: 0.2rem 0.5rem;
      transition: color 0.3s ease;
    }
    #burgerBtn:hover {
      color: #a8dadc;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 56px;
      left: 0;
      width: 260px;
      height: calc(100% - 56px);
      background-color: #27445D;
      color: #e9ecef;
      padding-top: 1.5rem;
      box-shadow: 2px 0 8px rgba(0,0,0,0.15);
      transform: translateX(-100%);
      transition: transform 0.3s ease-in-out;
      z-index: 1050;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .sidebar.show {
      transform: translateX(0);
    }

    .sidebar nav {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      padding: 0 1rem;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 12px 16px;
      color: #e9ecef;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 500;
      font-size: 1rem;
      transition: background-color 0.25s ease, padding-left 0.3s ease;
    }

    .sidebar a i {
      min-width: 22px;
      font-size: 1.15rem;
      background-color: rgba(255, 255, 255, 0.15);
      padding: 6px;
      border-radius: 8px;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #f1f1f1;
      transition: background-color 0.3s ease;
    }

    .sidebar a:hover {
      background-color: #1b3a57;
      padding-left: 25px;
      color: #a8dadc;
    }

    .sidebar a:hover i {
      background-color: #a8dadc;
      color: #1d3557;
    }

    .sidebar a.active {
      background-color: #142d4c;
      color: #a8dadc;
      font-weight: 700;
      padding-left: 25px;
    }

    .sidebar a.active i {
      background-color: #a8dadc;
      color: #1d3557;
    }

    /* Logout bottom */
    .sidebar .logout {
      margin: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    /* Main content */
    .main-content {
      margin-top: 56px;
      padding: 25px 30px;
      transition: margin-left 0.3s ease;
      margin-left: 0;
      min-height: 100vh;
      user-select: text;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        width: 220px;
      }
      .sidebar a {
        font-size: 0.95rem;
      }
      #burgerBtn {
        font-size: 1.2rem;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-dark fixed-top d-flex justify-content-between align-items-center">
    <button id="burgerBtn" aria-label="Toggle sidebar">
      <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand">MASTER SNOW</span>
    <div class="user-info">
      Halo, <?php echo htmlspecialchars($username); ?>
    </div>
  </nav>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <nav>
      <a href="index.php" class="active"><i class="fas fa-house-chimney"></i> Dashboard</a>
      <a href="sistem.php"><i class="fas fa-credit-card"></i> Sistem</a>
      <a href="harga.php"><i class="fas fa-tag"></i> Harga</a>
      <a href="karyawan.php"><i class="fas fa-user"></i> Karyawan</a>
      <a href="laporan.php"><i class="fas fa-database"></i> Laporan Harian</a>
      <a href="laporan_gaji.php"><i class="fas fa-database"></i> Laporan Gaji</a>
    </nav>
    <div class="logout">
      <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
  </aside>
  <script>
    const burgerBtn = document.getElementById('burgerBtn');
    const sidebar = document.getElementById('sidebar');
    const links = sidebar.querySelectorAll('nav a, .logout a');
    const mainContent = document.querySelector('.main-content');

    burgerBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });

    // Tutup sidebar saat klik link navigasi
    links.forEach(link => {
      link.addEventListener('click', () => {
        sidebar.classList.remove('show');
      });
    });

    // Tutup sidebar saat klik area konten utama
    mainContent.addEventListener('click', () => {
      if (sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
      }
    });
  </script>

</body>
</html>
