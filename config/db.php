<?php
// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "hospital_db";

// Create Connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check Connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");
?>