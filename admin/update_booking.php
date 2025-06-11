<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['booking_id']) || empty($_POST['status'])) {
            throw new Exception("Required fields are missing");
        }

        $booking_id = (int)$_POST['booking_id'];
        $status = $_POST['status'];

        // Start transaction
        $pdo->beginTransaction();

        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $booking_id]);

        // If booking is cancelled or completed, make minibus available again
        if ($status === 'cancelled' || $status === 'completed') {
            $stmt = $pdo->prepare("
                UPDATE minibuses m
                JOIN bookings b ON m.id = b.minibus_id
                SET m.status = 'available'
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
        }

        // If booking is confirmed, mark minibus as booked
        if ($status === 'confirmed') {
            $stmt = $pdo->prepare("
                UPDATE minibuses m
                JOIN bookings b ON m.id = b.minibus_id
                SET m.status = 'booked'
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
        }

        $pdo->commit();
        header("Location: bookings.php?success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: bookings.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: bookings.php");
    exit();
} 