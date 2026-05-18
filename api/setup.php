<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Setup Database</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
    h1 { color: #333; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .container { max-width: 600px; margin: 0 auto; background: #f8f9fa; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    a:hover { background: #0056b3; }
</style>";
echo "</head><body><div class='container'>";
echo "<h1>Database Setup</h1>";

// Ensure connection is valid
if (!$conn || $conn->connect_error) {
    die("<h2 class='error'>Connection Failed</h2><p>Could not connect to the database. Check your environment variables.</p></div></body></html>");
}

// Read the SQL file
$sqlFile = '../setup_db.sql';
if (!file_exists($sqlFile)) {
    die("<h2 class='error'>Error</h2><p>setup_db.sql file not found in the root directory.</p></div></body></html>");
}

$sqlContent = file_get_contents($sqlFile);

// Remove comments from SQL to prevent parsing issues
$sqlContent = preg_replace('/--.*$/m', '', $sqlContent);

// Split the file into individual queries by semicolon
$queries = explode(';', $sqlContent);

$successCount = 0;
$errorCount = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query) === TRUE) {
            $successCount++;
        } else {
            // Ignore errors for "Database already exists" or similar non-fatal errors if needed,
            // but we'll log them to be safe.
            echo "<p class='error'>Error executing query: " . $conn->error . "</p>";
            echo "<pre style='text-align: left; background: #eee; padding: 10px;'>" . htmlspecialchars($query) . "</pre>";
            $errorCount++;
        }
    }
}

if ($errorCount === 0) {
    echo "<h2 class='success'>Setup Complete!</h2>";
    echo "<p>Database tables created successfully. ($successCount queries executed)</p>";
} else {
    echo "<h2 class='error'>Setup finished with $errorCount errors.</h2>";
    echo "<p>$successCount queries executed successfully.</p>";
}

echo '<a href="../index.php">Go to App</a>';
echo "</div></body></html>";
?>
