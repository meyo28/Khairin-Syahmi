<?php
session_start();
ob_start(); // Buffer output
header("Content-Type: application/json");

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include your database config (use the same file as landlord.php)
require_once __DIR__ . '/../db_config.php';

// Helper function to send JSON responses
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    ob_clean(); // Clear any previous output
    echo json_encode($data);
    exit;
}

// Helper function to authenticate user
function authenticateUser() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
    return $_SESSION['user_id'];
}

$method = $_SERVER['REQUEST_METHOD'];

// Handle method override for PUT requests
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

$user_id = authenticateUser();
$user_type = $_SESSION['user_type'] ?? null;

if (!$user_type) {
    jsonResponse(['error' => 'User type not found'], 400);
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $booking_id = (int)$_GET['id'];
                
                if ($user_type == 'landlord') {
                    $stmt = $conn->prepare("
                        SELECT b.*, p.title as property_title, u.name as student_name 
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        JOIN users u ON b.student_id = u.id
                        WHERE b.id = ? AND p.landlord_id = ?
                    ");
                    $stmt->bind_param("ii", $booking_id, $user_id);
                } else {
                    $stmt = $conn->prepare("
                        SELECT b.*, p.title as property_title, u.name as landlord_name 
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        JOIN users u ON p.landlord_id = u.id
                        WHERE b.id = ? AND b.student_id = ?
                    ");
                    $stmt->bind_param("ii", $booking_id, $user_id);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows == 0) {
                    jsonResponse(['error' => 'Booking not found'], 404);
                }
                
                jsonResponse($result->fetch_assoc());
                
            } else {
                // Get all bookings
                if ($user_type == 'landlord') {
                    $stmt = $conn->prepare("
                        SELECT b.*, p.title as property_title, u.name as student_name 
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        JOIN users u ON b.student_id = u.id
                        WHERE p.landlord_id = ?
                        ORDER BY b.created_at DESC
                    ");
                    $stmt->bind_param("i", $user_id);
                } else {
                    $stmt = $conn->prepare("
                        SELECT b.*, p.title as property_title, u.name as landlord_name 
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        JOIN users u ON p.landlord_id = u.id
                        WHERE b.student_id = ?
                        ORDER BY b.created_at DESC
                    ");
                    $stmt->bind_param("i", $user_id);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                jsonResponse($result->fetch_all(MYSQLI_ASSOC));
            }
            break;

        case 'POST':
            if ($user_type !== 'student') {
                jsonResponse(['error' => 'Only students can create bookings'], 403);
            }

            $property_id = (int)($_POST['property_id'] ?? 0);
            $move_in_date = $_POST['move_in_date'] ?? '';
            $message = $_POST['message'] ?? '';

            if (!$property_id || !$move_in_date || !$message) {
                jsonResponse(['error' => 'Missing required fields'], 400);
            }

            // Check if property exists and is available
            $stmt = $conn->prepare("SELECT id, available_date FROM properties WHERE id = ? AND status = 'available'");
            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $property = $stmt->get_result()->fetch_assoc();

            if (!$property) {
                jsonResponse(['error' => 'Property not found or not available'], 404);
            }

            if (strtotime($move_in_date) < strtotime($property['available_date'])) {
                jsonResponse(['error' => "Property not available until {$property['available_date']}"], 400);
            }

            // Insert booking
            $stmt = $conn->prepare("
                INSERT INTO bookings (property_id, student_id, message, move_in_date, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("iiss", $property_id, $user_id, $message, $move_in_date);

            if ($stmt->execute()) {
                jsonResponse(['success' => true, 'booking_id' => $conn->insert_id], 201);
            } else {
                jsonResponse(['error' => 'Failed to create booking: ' . $stmt->error], 500);
            }
            break;

        case 'PUT':
            if ($user_type !== 'landlord') {
                jsonResponse(['error' => 'Only landlords can update bookings'], 403);
            }

            $booking_id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $message = $_POST['message'] ?? '';

            if (!$booking_id || !in_array($status, ['approved', 'rejected', 'completed'])) {
                jsonResponse(['error' => 'Invalid input. Booking ID: ' . $booking_id . ', Status: ' . $status], 400);
            }

            // Verify landlord owns this booking
            $stmt = $conn->prepare("
                SELECT b.id, b.property_id FROM bookings b 
                JOIN properties p ON b.property_id = p.id 
                WHERE b.id = ? AND p.landlord_id = ?
            ");
            $stmt->bind_param("ii", $booking_id, $user_id);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();

            if (!$booking) {
                jsonResponse(['error' => 'Booking not found or access denied'], 404);
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update booking
                $stmt = $conn->prepare("
                    UPDATE bookings SET 
                        status = ?,
                        message = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("ssi", $status, $message, $booking_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update booking: ' . $stmt->error);
                }

                // Update property status
                $property_status = ($status === 'approved') ? 'unavailable' : 'available';
                $stmt = $conn->prepare("UPDATE properties SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $property_status, $booking['property_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update property status: ' . $stmt->error);
                }

                // Commit transaction
                $conn->commit();
                jsonResponse(['success' => true, 'message' => 'Booking updated successfully']);

            } catch (Exception $e) {
                // Rollback transaction
                $conn->rollback();
                jsonResponse(['error' => $e->getMessage()], 500);
            }
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>