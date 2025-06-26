<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
        $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 10000;
        $bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : null;
        $property_type = isset($_GET['type']) ? $_GET['type'] : null;

        $query = "SELECT p.*, u.name as landlord_name FROM properties p 
          JOIN users u ON p.landlord_id = u.id 
          WHERE p.status = 'available'";


        if ($min_price > 0) $query .= " AND p.rent_amount >= $min_price";
        if ($max_price < 10000) $query .= " AND p.rent_amount <= $max_price";
        if ($bedrooms !== null) {
            if ($bedrooms === '3+') {
                $query .= " AND p.bedrooms >= 3";
            } else {
                $query .= " AND p.bedrooms = $bedrooms";
            }
        }
        if ($property_type !== null) $query .= " AND p.property_type = '$property_type'";

        $result = $conn->query($query);
        if (!$result) {
            jsonResponse(['error' => $conn->error], 500);
        }
        
        $properties = $result->fetch_all(MYSQLI_ASSOC);
        jsonResponse($properties);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
?>