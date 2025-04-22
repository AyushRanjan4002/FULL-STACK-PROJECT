<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('No data received');
    }
    
    // Validate required fields
    $required_fields = ['eventId', 'eventType', 'firstName', 'lastName', 'email', 'phone', 'ticketType', 'quantity', 'eventDate', 'eventName', 'totalAmount'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }
    
    // Generate a unique booking ID
    $bookingId = uniqid('BK');
    
    // Prepare SQL statement
    $sql = "INSERT INTO bookings (id, event_id, event_type, first_name, last_name, email, phone, event_name, event_date, ticket_type, quantity, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param('ssssssssssid', 
        $bookingId,
        $data['eventId'],
        $data['eventType'],
        $data['firstName'],
        $data['lastName'],
        $data['email'],
        $data['phone'],
        $data['eventName'],
        $data['eventDate'],
        $data['ticketType'],
        $data['quantity'],
        $data['totalAmount']
    );
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Error creating booking: " . $stmt->error);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully',
        'data' => [
            'bookingId' => $bookingId,
            'eventName' => $data['eventName'],
            'ticketType' => $data['ticketType'],
            'quantity' => $data['quantity'],
            'totalAmount' => $data['totalAmount'],
            'email' => $data['email']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 