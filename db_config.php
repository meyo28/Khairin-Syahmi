<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "daddb";

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}
?>