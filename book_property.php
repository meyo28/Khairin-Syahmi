<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $property_id = (int)$_POST['property_id'];
    $student_id = (int)$_SESSION['user_id'];
    $message = $conn->real_escape_string($_POST['message']);
    $move_in_date = $conn->real_escape_string($_POST['move_in_date']);
    $status = 'pending';
    $created_at = date('Y-m-d H:i:s');

    // Check if property exists
    $property_check = $conn->query("SELECT id FROM properties WHERE id = $property_id");
    if ($property_check->num_rows == 0) {
        $_SESSION['error'] = "Property not found";
        header("Location: homepage.php");
        exit();
    }

    // Insert booking
    $query = "INSERT INTO bookings (property_id, student_id, message, move_in_date, status, created_at)
              VALUES ($property_id, $student_id, '$message', '$move_in_date', '$status', '$created_at')";

    if ($conn->query($query)) {
        $_SESSION['success'] = "Booking request submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting booking: " . $conn->error;
    }

    header("Location: homepage.php");
    exit();
}