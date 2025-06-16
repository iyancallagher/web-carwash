<?php
session_start();
require "koneksi.php";


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/bootstrap-table@1.21.2/dist/bootstrap-table.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Carwash - Point Of Sales</title>
</head>
<body>
<style>
    body{
        margin: 0;
        background-image: url('img/DSC08500.png'); /* Ganti dengan gambar latar belakang carwash */
        font-family: poppins;
        background-size: cover;
        background-position: center;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
.main{
    height: 100vh;
}
.login-box{
    width: 500px;
    height: 300px;
    background-color: #27445D;
    border-radius: 10px;
}
.login-box label{
    color: white;
}
.btn{
    border-radius: 10px;
}
.judul {
    display: flex;
    align-items: center;
}
</style>
<body>
    <div class="main d-flex flex-column justify-content-center align-items-center">
        <div class="judul">
        </div>
        <div class="login-box p-5">
            <form action="" method="post">
                <div>
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan Password" >
                </div>
                <label for="password" class="mt-2">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="passwords" id="passwords" placeholder="Masukkan Password" required>
                        <span class="input-group-text" style="cursor: pointer;">
                            <i class="fa fa-eye" id="togglePasswordIcon"></i>
                        </span>
                    </div>
                <div>
                    <button class="btn btn-primary form-control mt-3" type="submit" name="loginbtn">Login</button>
                </div>
            </form>
        </div>
        <div class="mt-3">
        <?php
if (isset($_POST['loginbtn'])) {
    // Securely retrieve the form data
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['passwords']);

    // Query to select user from the database
    $query = mysqli_query($conn, "SELECT * FROM karyawan WHERE username='$username'");
    
    // Check for query errors
    if (!$query) {
        die('Query failed: ' . mysqli_error($conn));
    }
    
    $countdata = mysqli_num_rows($query);
    $data = mysqli_fetch_array($query);

    // Check if a user was found
    if ($countdata > 0) {
        // Check if the password matches the one in the database
        if ($password === $data['password']) {
            // Correct password, start session
            $_SESSION['username'] = $data['username'];
            $_SESSION['id'] = $data['id'];
            $_SESSION['login'] = true;

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            header('Location: index.php');
            exit();
        } else {
            // Incorrect password
            ?>
            <div class="alert alert-warning" role="alert">
                Periksa kembali password dan username Anda.
            </div>
            <?php
        }
    } else {
        // User not found
        ?>
        <div class="alert alert-warning" role="alert">
            Pengguna tidak ditemukan.
        </div>
        <?php
    }
}
?>
    </div>
    </div>
    <script>
    const togglePasswordIcon = document.querySelector('#togglePasswordIcon');
    const password = document.querySelector('#password');

    togglePasswordIcon.addEventListener('click', function () {
        // Toggle the type attribute
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Toggle the icon class
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
</script>
</body>
</html>