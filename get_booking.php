<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Error reporting disabled for performance

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to view booking details');
    }

    // Check if booking ID is provided
    if (!isset($_GET['id'])) {
        throw new Exception('Booking ID is required');
    }

    $booking_id = (int)$_GET['id'];
    
    // Get booking details with related information
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            m.name as minibus_name,
            d.name as driver_name,
            d.phone as driver_phone,
            b.pickup_location,
            b.pickup_latitude,
            b.pickup_longitude
        FROM bookings b
        LEFT JOIN minibuses m ON b.minibus_id = m.id
        LEFT JOIN drivers d ON m.driver_id = d.id
        WHERE b.id = ?
    ");
    
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    // Format dates and times
    $booking['start_date'] = date('M d, Y', strtotime($booking['start_date']));
    $booking['pickup_time'] = date('h:i A', strtotime($booking['pickup_time']));
    $booking['created_at'] = date('M d, Y h:i A', strtotime($booking['created_at']));
    $booking['updated_at'] = date('M d, Y h:i A', strtotime($booking['updated_at']));
    $booking['total_price'] = number_format($booking['total_price']);

    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}