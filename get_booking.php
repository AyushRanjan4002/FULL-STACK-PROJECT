<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get booking ID from GET parameter
    $bookingId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$bookingId) {
        throw new Exception("Booking ID is required");
    }
    
    // Prepare SQL statement
    $sql = "SELECT b.*, p.status as payment_status, p.payment_method, p.transaction_id, p.amount as payment_amount 
            FROM bookings b 
            LEFT JOIN payments p ON b.id = p.booking_id 
            WHERE b.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found");
    }
    
    $booking = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 