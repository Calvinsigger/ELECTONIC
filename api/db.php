<?php
/* ===============================
   DATABASE CONNECTION FILE
   File: api/db.php
   =============================== */

$host = "localhost";
$dbname = "electronics_ordering";
$username = "root";      // default for XAMPP
$password = "";          // default for XAMPP

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Stop execution if connection fails
    die("Database connection failed: " . $e->getMessage());
}
