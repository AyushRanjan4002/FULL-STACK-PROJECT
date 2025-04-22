<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password
$dbname = "ticket_booking";

try {
    // Connect to MySQL server without specifying a database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode(["step" => "Starting database setup"]) . "\n";
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $conn->exec("USE `$dbname`");
    
    echo json_encode(["step" => "Database created or already exists"]) . "\n";
    
    // Create bookings table
    $conn->exec("CREATE TABLE IF NOT EXISTS `bookings` (
        `id` VARCHAR(50) PRIMARY KEY,
        `user_email` VARCHAR(100) NOT NULL,
        `event_name` VARCHAR(255) NOT NULL,
        `event_date` DATE NOT NULL,
        `ticket_type` VARCHAR(50) NOT NULL,
        `quantity` INT NOT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `booking_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending'
    )");
    
    echo json_encode(["step" => "Bookings table created or already exists"]) . "\n";
    
    // Create payments table
    $conn->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` VARCHAR(50) NOT NULL,
        `payment_method` VARCHAR(50) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `status` VARCHAR(20) NOT NULL,
        `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
    )");
    
    echo json_encode(["step" => "Payments table created or already exists"]) . "\n";
    
    // Add sample data if tables are empty
    $stmt = $conn->query("SELECT COUNT(*) FROM bookings");
    $bookingCount = $stmt->fetchColumn();
    
    if ($bookingCount == 0) {
        // Sample user emails
        $userEmails = [
            'test@example.com',
            'user@ticketbooking.com',
            'demo@test.com'
        ];
        
        // Sample bookings data
        $sampleBookings = [
            [
                'id' => 'BOOK-' . rand(10000, 99999),
                'user_email' => $userEmails[0],
                'event_name' => 'Avengers: Final Chapter Movie',
                'event_date' => '2024-12-25',
                'ticket_type' => 'VIP',
                'quantity' => 2,
                'total_amount' => 1200.00,
                'status' => 'completed'
            ],
            [
                'id' => 'BOOK-' . rand(10000, 99999),
                'user_email' => $userEmails[0],
                'event_name' => 'IPL Final Match 2024',
                'event_date' => '2024-11-15',
                'ticket_type' => 'Premium',
                'quantity' => 3,
                'total_amount' => 1800.00,
                'status' => 'pending'
            ],
            [
                'id' => 'BOOK-' . rand(10000, 99999),
                'user_email' => $userEmails[1],
                'event_name' => 'Rock Music Festival',
                'event_date' => '2025-01-10',
                'ticket_type' => 'Standard',
                'quantity' => 4,
                'total_amount' => 2000.00,
                'status' => 'completed'
            ],
            [
                'id' => 'BOOK-' . rand(10000, 99999),
                'user_email' => $userEmails[2],
                'event_name' => 'International Jazz Festival',
                'event_date' => '2024-10-05',
                'ticket_type' => 'VIP',
                'quantity' => 1,
                'total_amount' => 3500.00,
                'status' => 'cancelled'
            ]
        ];
        
        // Insert sample bookings
        $stmt = $conn->prepare("
            INSERT INTO bookings (id, user_email, event_name, event_date, ticket_type, quantity, total_amount, status)
            VALUES (:id, :user_email, :event_name, :event_date, :ticket_type, :quantity, :total_amount, :status)
        ");
        
        foreach ($sampleBookings as $booking) {
            $stmt->bindParam(':id', $booking['id']);
            $stmt->bindParam(':user_email', $booking['user_email']);
            $stmt->bindParam(':event_name', $booking['event_name']);
            $stmt->bindParam(':event_date', $booking['event_date']);
            $stmt->bindParam(':ticket_type', $booking['ticket_type']);
            $stmt->bindParam(':quantity', $booking['quantity']);
            $stmt->bindParam(':total_amount', $booking['total_amount']);
            $stmt->bindParam(':status', $booking['status']);
            $stmt->execute();
            
            // If booking is completed, add a payment record
            if ($booking['status'] === 'completed') {
                $paymentMethod = ['creditCard', 'debitCard', 'upi'][array_rand([0, 1, 2])];
                
                $paymentStmt = $conn->prepare("
                    INSERT INTO payments (booking_id, payment_method, amount, status)
                    VALUES (:booking_id, :payment_method, :amount, 'completed')
                ");
                
                $paymentStmt->bindParam(':booking_id', $booking['id']);
                $paymentStmt->bindParam(':payment_method', $paymentMethod);
                $paymentStmt->bindParam(':amount', $booking['total_amount']);
                $paymentStmt->execute();
            }
        }
        
        echo json_encode(["step" => "Sample data inserted"]) . "\n";
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Database and tables setup completed successfully"
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database setup failed: " . $e->getMessage()
    ]);
}
?> 