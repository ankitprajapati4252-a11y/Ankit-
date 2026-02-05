<?php
// MAMP Database Configuration
$host = "localhost";
$username = "root";
$password = "root";  // MAMP का डिफ़ॉल्ट पासवर्ड
$database = "hotel_ankit";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>