<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function get_env_var($key) {
    if ($val = getenv($key)) return $val;
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return null;
}

// Extract variables
$host = get_env_var('MYSQL_HOST') ?: get_env_var('MYSQLHOST');
$user = get_env_var('MYSQL_USER') ?: get_env_var('MYSQLUSER');
$pass = get_env_var('MYSQL_PASSWORD') ?: get_env_var('MYSQLPASSWORD');
$db_name = get_env_var('MYSQL_DATABASE') ?: get_env_var('MYSQLDATABASE');
$port = get_env_var('MYSQL_PORT') ?: get_env_var('MYSQLPORT');
$url = get_env_var('MYSQL_URL') ?: get_env_var('DATABASE_URL');

// Parse connection URL if provided (Railway provides MYSQL_URL)
if ($url && !$host) {
    $parsed = parse_url($url);
    if ($parsed) {
        $host = $parsed['host'] ?? null;
        $user = $parsed['user'] ?? null;
        $pass = $parsed['pass'] ?? null;
        $db_name = ltrim($parsed['path'] ?? '', '/');
        $port = $parsed['port'] ?? 3306;
    }
}

try {
    if ($host) {
        // We are on Railway (or have Env Vars set)
        $conn = new mysqli($host, $user, $pass, $db_name, $port);
    } else {
        // Fallback to local XAMPP
        // Using 127.0.0.1 instead of localhost forces TCP instead of socket, avoiding "No such file or directory" socket errors on Railway
        $host = '127.0.0.1';
        $user = 'root';
        $pass = ''; // Default XAMPP password is empty
        $db_name = 'medical_store';
        $port = 3306;
        $conn = new mysqli($host, $user, $pass, $db_name, $port);
    }
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    if (get_env_var('RAILWAY_ENVIRONMENT_NAME') || get_env_var('RAILWAY_PROJECT_ID') || !$host) {
        die("<h2>Database Connection Failed</h2>" . 
            "<p>It looks like the application is trying to connect to the database but failing.</p>" . 
            "<p><strong>If you are deployed on Railway:</strong> Did you link the MySQL database to your web service?</p>" .
            "<p>Go to your Railway Dashboard -> Click your PHP Web Service -> Variables -> Click 'New Variable' -> 'Add Reference' -> Select your MySQL database to expose its variables.</p>" .
            "<hr><p><strong>Error details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>");
    } else {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }
}
?>
