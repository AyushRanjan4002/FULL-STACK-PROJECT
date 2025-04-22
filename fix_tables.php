<?php
header('Content-Type: text/html');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connection.php';

try {
    $conn = new mysqli('localhost', 'root', '', 'ticket_booking');
    
    // Drop existing tables to recreate them with correct structure
    $conn->query("DROP TABLE IF EXISTS payments");
    $conn->query("DROP TABLE IF EXISTS bookings");
    
    // Create bookings table with correct structure
    $sql = "CREATE TABLE bookings (
        id VARCHAR(50) PRIMARY KEY,
        event_id VARCHAR(50) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        email VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        event_name VARCHAR(255) NOT NULL,
        event_date DATE NOT NULL,
        ticket_type VARCHAR(50) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        total_amount DECIMAL(10,2) NOT NULL,
        booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'pending'
    )";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ Bookings table created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating bookings table: " . $conn->error . "</p>";
    }
    
    // Create payments table with correct structure
    $sql = "CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50),
        status VARCHAR(50) DEFAULT 'pending',
        transaction_id VARCHAR(255),
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p style='color:green'>✓ Payments table created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating payments table: " . $conn->error . "</p>";
    }
    
    // Create a test booking
    $sql = "INSERT INTO bookings (id, event_id, event_type, email, first_name, last_name, phone, event_name, event_date, ticket_type, quantity, total_amount, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $bookingId = '25';
    $eventId = 'EVT001';
    $eventType = 'movie';
    $email = 'mahendar@gmail.com';
    $firstName = 'Mahendar';
    $lastName = 'Singh';
    $phone = '09310915153';
    $eventName = 'Havoc';
    $eventDate = '2024-12-15';
    $ticketType = 'Premium';
    $quantity = 1;
    $totalAmount = 1999.00;
    $status = 'pending';
    
    $stmt->bind_param('ssssssssssisd', 
        $bookingId, 
        $eventId,
        $eventType,
        $email, 
        $firstName, 
        $lastName, 
        $phone, 
        $eventName, 
        $eventDate, 
        $ticketType, 
        $quantity, 
        $totalAmount,
        $status
    );
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>✓ Test booking created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating test booking: " . $stmt->error . "</p>";
    }
    
    echo "<p>Database tables have been reset and recreated with correct structure.</p>";
    echo "<p>You can now try the booking process again.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?> 