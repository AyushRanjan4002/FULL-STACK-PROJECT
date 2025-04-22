<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['bookingId']) || !isset($data['userEmail'])) {
        throw new Exception('Missing required fields: bookingId or userEmail');
    }
    
    $bookingId = $data['bookingId'];
    $userEmail = $data['userEmail'];
    $paymentMethod = $data['paymentMethod'] ?? 'card';
    $status = $data['status'] ?? 'pending';
    $amount = $data['amount'] ?? 0;
    $transactionId = 'TXN_' . time() . rand(1000, 9999);

    // First verify that the booking exists and belongs to the user
    $verifyBookingSql = "SELECT id FROM bookings WHERE id = ? AND email = ?";
    $verifyStmt = $conn->prepare($verifyBookingSql);
    $verifyStmt->bind_param("ss", $bookingId, $userEmail);
    
    if (!$verifyStmt->execute()) {
        throw new Exception('Error verifying booking: ' . $verifyStmt->error);
    }
    
    $verifyResult = $verifyStmt->get_result();
    if ($verifyResult->num_rows === 0) {
        throw new Exception('Booking not found or does not belong to user');
    }
    $verifyStmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if payment record exists
        $checkSql = "SELECT id FROM payments WHERE booking_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $bookingId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            // Payment record doesn't exist, create a new one
            $insertSql = "INSERT INTO payments (booking_id, amount, payment_method, status, transaction_id) 
                          VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("sdsss", $bookingId, $amount, $paymentMethod, $status, $transactionId);
            
            if (!$insertStmt->execute()) {
                throw new Exception("Error inserting payment: " . $insertStmt->error);
            }
            $insertStmt->close();
        } else {
            // Payment record exists, update it
            $updateSql = "UPDATE payments SET 
                            payment_method = ?, 
                            status = ?, 
                            transaction_id = ?,
                            amount = ?
                          WHERE booking_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssds", $paymentMethod, $status, $transactionId, $amount, $bookingId);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Error updating payment: " . $updateStmt->error);
            }
            $updateStmt->close();
        }
        
        $checkStmt->close();
        
        // Update booking status
        $updateBookingSql = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND email = ?";
        $updateBookingStmt = $conn->prepare($updateBookingSql);
        $updateBookingStmt->bind_param("ss", $bookingId, $userEmail);
        
        if (!$updateBookingStmt->execute()) {
            throw new Exception("Error updating booking status: " . $updateBookingStmt->error);
        }
        $updateBookingStmt->close();

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => [
                'bookingId' => $bookingId,
                'amount' => $amount,
                'status' => $status,
                'transactionId' => $transactionId
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in update_payment.php: " . $e->getMessage());
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