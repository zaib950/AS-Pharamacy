<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Railway database environment variables
$railway_host = getenv('MYSQLHOST');

if ($railway_host) {
    $host = getenv('MYSQLHOST');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
    $db_name = getenv('MYSQLDATABASE');
    $port = getenv('MYSQLPORT');
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
