<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Railway database environment variables
$railway_host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST');

if ($railway_host) {
    $host = $railway_host;
    $user = getenv('MYSQL_USER') ?: getenv('MYSQLUSER');
    $pass = getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD');
    $db_name = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE');
    $port = getenv('MYSQL_PORT') ?: getenv('MYSQLPORT');
    $conn = new mysqli($host, $user, $pass, $db_name, $port);
} else {
    // Local XAMPP database credentials
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Default XAMPP password is empty
    $db_name = 'medical_store';
    $conn = new mysqli($host, $user, $pass, $db_name);
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
