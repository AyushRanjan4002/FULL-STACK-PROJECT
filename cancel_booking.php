<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Create a log file for debugging
$logFile = 'cancel_booking_log.txt';
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

logMessage("Cancel booking request received");

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['bookingId']) || !isset($data['userEmail'])) {
        throw new Exception('Booking ID and user email are required');
    }
    
    $bookingId = $data['bookingId'];
    $userEmail = $data['userEmail'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Verify the booking exists and belongs to the user
    $verifyStmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND email = ?");
    $verifyStmt->bind_param("ss", $bookingId, $userEmail);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found or not authorized for this user");
    }
    
    $booking = $result->fetch_assoc();
    if ($booking['status'] === 'cancelled') {
        throw new Exception('This booking has already been cancelled');
    }
    
    // Update booking status to cancelled
    $updateBookingStmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $updateBookingStmt->bind_param("s", $bookingId);
    if (!$updateBookingStmt->execute()) {
        throw new Exception("Error updating booking status: " . $updateBookingStmt->error);
    }
    
    // Update payment status to refunded if payment exists
    $updatePaymentStmt = $conn->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = ?");
    $updatePaymentStmt->bind_param("s", $bookingId);
    $updatePaymentStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?> 