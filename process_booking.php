<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'landlord') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = (int)$_POST['booking_id'];
    $landlord_message = $conn->real_escape_string($_POST['landlord_message']);
    
    // Verify the booking belongs to landlord's property
    $check_query = "SELECT b.id FROM bookings b
                    JOIN properties p ON b.property_id = p.id
                    WHERE b.id = $booking_id AND p.landlord_id = {$_SESSION['user_id']}";
    $check_result = $conn->query($check_query);
    
    if ($check_result->num_rows == 0) {
        $_SESSION['error'] = "Invalid booking request";
        header("Location: landlord.php");
        exit();
    }
    
    // Determine action
    if (isset($_POST['approve'])) {
        $status = 'approved';
    } elseif (isset($_POST['reject'])) {
        $status = 'rejected';
    } elseif (isset($_POST['complete'])) {
        $status = 'completed';
    } else {
        $_SESSION['error'] = "Invalid action";
        header("Location: landlord.php");
        exit();
    }
    
    // Update booking
    $update_query = "UPDATE bookings SET 
                    status = '$status',
                    message = '$landlord_message',
                    updated_at = NOW()
                    WHERE id = $booking_id";
    
    if ($conn->query($update_query)) {
        $_SESSION['success'] = "Booking updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating booking: " . $conn->error;
    }
    
    header("Location: landlord.php");
    exit();
}