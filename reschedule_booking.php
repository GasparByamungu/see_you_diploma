<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['booking_id']) || empty($_POST['new_start_date']) || empty($_POST['new_pickup_time'])) {
            throw new Exception("Please fill in all required fields");
        }

        $booking_id = (int)$_POST['booking_id'];
        $new_start_date = $_POST['new_start_date'];
        $new_pickup_time = $_POST['new_pickup_time'];

        // Get booking details
        $stmt = $pdo->prepare("
            SELECT b.*, m.price_per_day 
            FROM bookings b
            JOIN minibuses m ON b.minibus_id = m.id
            WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending'
        ");
        $stmt->execute([$booking_id, $_SESSION['user_id']]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Booking not found or cannot be rescheduled");
        }

        // Check if minibus is available for new date and time
        $stmt = $pdo->prepare("
            SELECT b.*, u.name as booked_by_name, u.phone as booked_by_phone
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.minibus_id = ? 
            AND b.id != ?
            AND b.status != 'cancelled'
            AND b.start_date = ?
            AND b.pickup_time = ?
        ");
        $stmt->execute([
            $booking['minibus_id'],
            $booking_id,
            $new_start_date,
            $new_pickup_time
        ]);
        $existing_booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_booking) {
            // Format the dates for better readability
            $existing_start = date('M d, Y', strtotime($existing_booking['start_date']));
            $existing_time = date('h:i A', strtotime($existing_booking['pickup_time']));
            
            $error_message = "Cannot reschedule: This minibus is already booked for your selected date and time. Here are the details:<br><br>";
            $error_message .= "<strong>Booked by:</strong> " . htmlspecialchars($existing_booking['booked_by_name']) . "<br>";
            $error_message .= "<strong>Date:</strong> " . $existing_start . "<br>";
            $error_message .= "<strong>Pickup Time:</strong> " . $existing_time . "<br><br>";
            $error_message .= "Please try:<br>";
            $error_message .= "1. Selecting a different date or time<br>";
            $error_message .= "2. Choosing a different minibus<br>";
            $error_message .= "3. Contacting us for assistance";
            
            throw new Exception($error_message);
        }

        // Update booking
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET start_date = ?, 
                pickup_time = ?,
                status = 'pending',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $new_start_date,
            $new_pickup_time,
            $booking_id,
            $_SESSION['user_id']
        ]);

        $_SESSION['success_message'] = "Your reschedule request has been submitted and is pending approval.";
        header("Location: bookings.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: bookings.php");
        exit();
    }
} else {
    header("Location: bookings.php");
    exit();
} 