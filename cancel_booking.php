<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if booking ID is provided
if (!isset($_POST['booking_id'])) {
    $_SESSION['error_message'] = "Invalid booking ID";
    header("Location: bookings.php");
    exit();
}

$booking_id = (int)$_POST['booking_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as minibus_name 
        FROM bookings b
        JOIN minibuses m ON b.minibus_id = m.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or cannot be cancelled");
    }

    // Update booking status to cancelled
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled', 
            cancellation_reason = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$_POST['cancel_reason'], $booking_id, $_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success_message'] = "Booking cancelled successfully";
} catch (Exception $e) {
    // Only rollback if there is an active transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = $e->getMessage();
}

header("Location: bookings.php");
exit(); 