<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Flutterwave configuration
$public_key = 'FLWPUBK_TEST-028decede9bba29c28e65f115f9f5b7f-X';
$secret_key = 'FLWSECK_TEST-562b51fb731f7bcb6f78d610e9d507f3-X';
$encryption_key = 'FLWSECK_TESTa6aa28fc74fa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    
    if (!$booking_id) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Booking ID is required']);
        exit();
    }

    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as minibus_name, u.email, u.name as user_name
        FROM bookings b
        JOIN minibuses m ON b.minibus_id = m.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
        exit();
    }

    // Prepare payment data
    $payment_data = [
        'public_key' => $public_key,
        'tx_ref' => 'MINIBUS-' . $booking_id . '-' . time(),
        'amount' => $booking['total_price'],
        'currency' => 'TZS',
        'payment_options' => 'card,mobilemoney,ussd',
        'customer' => [
            'email' => $booking['email'],
            'name' => $booking['user_name'],
        ],
        'customizations' => [
            'title' => 'Safari Minibus Rentals',
            'description' => 'Payment for Minibus Booking #' . $booking_id,
            'logo' => 'https://safariminibus.co.tz/assets/images/logo.png'
        ],
        'meta' => [
            'booking_id' => $booking_id
        ]
    ];

    // Return payment data
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $payment_data]);
    exit();
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['transaction_id'])) {
    $transaction_id = $_GET['transaction_id'];
    $booking_id = $_GET['booking_id'];

    // Verify payment with Flutterwave
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$secret_key}"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Payment verification failed']);
        exit();
    }

    $result = json_decode($response, true);

    if ($result['status'] === 'success' && $result['data']['status'] === 'successful') {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
        $stmt->execute([$booking_id]);

        // Redirect to success page
        header("Location: bookings.php?payment=success");
        exit();
    } else {
        // Redirect to error page
        header("Location: bookings.php?payment=failed");
        exit();
    }
} 