<?php
$host = "localhost";
$user = "root";   // default XAMPP username
$pass = "";       // default XAMPP password (empty)
$dbname = "photo_gallery_app";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

