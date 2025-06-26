<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../includes/security.php';

// Enable CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Authenticate user and return user ID
function authenticateUser() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION['user_id'];
}

// Parse input for PUT/DELETE
function getInputData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['error' => 'Invalid JSON data'], 400);
    }
    return $data;
}
