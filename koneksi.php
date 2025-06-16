<?php
$conn = mysqli_connect("localhost","root","","db_carwash");


if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
?>