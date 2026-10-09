<?php
// Database connection (PDO). Change these values if your MySQL setup is different.
$host    = 'localhost';
$dbname  = 'barangay_rms';
$user    = 'root';
$pass    = '';          // XAMPP default is an empty password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,                    // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+08:00'");      // Philippine time, same as PHP
} catch (PDOException $ex) {
    die('Cannot connect to the database. Start MySQL, import schema.sql, and check the settings in db.php.');
}
