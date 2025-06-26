<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get booking details with verification
if ($_SESSION['user_type'] == 'landlord') {
    $query = "SELECT b.*, p.title as property_title, u.name as student_name 
              FROM bookings b
              JOIN properties p ON b.property_id = p.id
              JOIN users u ON b.student_id = u.id
              WHERE b.id = $booking_id AND p.landlord_id = $user_id";
} else {
    $query = "SELECT b.*, p.title as property_title, u.name as landlord_name 
              FROM bookings b
              JOIN properties p ON b.property_id = p.id
              JOIN users u ON p.landlord_id = u.id
              WHERE b.id = $booking_id AND b.student_id = $user_id";
}

$result = $conn->query($query);
if ($result->num_rows == 0) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$booking = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($booking);