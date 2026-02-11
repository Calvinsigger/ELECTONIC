<?php
/* ===============================
   DATABASE CONNECTION FILE
   File: api/db.php
   =============================== */

// Read DB configuration from environment variables so the app can
// run both locally (XAMPP) and on hosted platforms (Elastic Beanstalk / RDS).
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '';
$dbname = getenv('DB_NAME') ?: 'electronics_ordering';
$username = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "mysql:host={$host}";
    if (!empty($port)) {
        $dsn .= ";port={$port}";
    }
    $dsn .= ";dbname={$dbname};charset=utf8";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $conn = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {
    // In production we avoid outputting credentials; show a generic message
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed.');
}
