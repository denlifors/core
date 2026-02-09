<?php
/**
 * Database Installation Script
 * Run this file once to set up the database
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'DenLiFors');

echo "<h1>DenLifors Database Installation</h1>";

try {
    // Connect without database first
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✓ Database created/verified</p>";
    
    // Select database
    $pdo->exec("USE `" . DB_NAME . "`");
    echo "<p>✓ Database selected</p>";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    
    // Remove CREATE DATABASE and USE statements if present
    $schema = preg_replace('/CREATE DATABASE.*?;/i', '', $schema);
    $schema = preg_replace('/USE.*?;/i', '', $schema);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $executed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (PDOException $e) {
                // Ignore errors for existing tables/records
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<p style='color:orange;'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }
    
    echo "<p>✓ Executed $executed SQL statements</p>";
    echo "<h2 style='color:green;'>Installation completed successfully!</h2>";
    echo "<p><strong>Default admin credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@denlifors.ru</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    echo "<p style='color:red;'><strong>⚠ IMPORTANT: Change the admin password after first login!</strong></p>";
    echo "<p><a href='index.php'>Go to website</a> | <a href='admin/'>Go to admin panel</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>

