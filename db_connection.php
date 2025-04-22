<?php
// Database connection parameters
$host = "localhost";
$dbname = "ticket_booking";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password

function getConnection() {
    global $host, $dbname, $username, $password;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// For backward compatibility with existing code that uses $conn directly
try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("MySQLi Connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }
} catch(Exception $e) {
    error_log("MySQLi Connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?> 