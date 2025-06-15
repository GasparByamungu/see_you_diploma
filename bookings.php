<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pagination for user bookings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Show 10 bookings per page
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$count_stmt->execute([$_SESSION['user_id']]);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $limit);

// Get user's bookings with pagination
$stmt = $pdo->prepare("
    SELECT b.*,
           m.name as minibus_name, m.capacity,
           d.name as driver_name, d.phone as driver_phone,
           DATE_FORMAT(b.pickup_time, '%H:%i') as formatted_pickup_time,
           DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') as formatted_created_at,
           DATE_FORMAT(b.updated_at, '%Y-%m-%d %H:%i') as formatted_updated_at,
           CASE 
               WHEN b.reschedule_request = 1 THEN 'Rescheduled'
               ELSE b.status 
           END as display_status
    FROM bookings b
    JOIN minibuses m ON b.minibus_id = m.id
    LEFT JOIN drivers d ON m.driver_id = d.id
    WHERE b.user_id = ?
    ORDER BY b.updated_at DESC, b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Safari Minibus Rentals</title>
    <meta name="description" content="View and manage your Safari Minibus Rentals bookings, track status, and make payments.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <style>
        .custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .custom-modal.show {
            display: block;
        }

        .custom-modal-content {
            position: relative;
            background-color: #fff;
            margin: 50px auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .custom-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .custom-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            color: #6c757d;
        }

        .custom-modal-close:hover {
            color: #343a40;
        }

        /* Add styles to prevent content shift */
        .modal-body {
            min-height: 200px;
        }

        /* Ensure the modal content is always visible */
        .custom-modal-content {
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Populate time slots before showing modal
                if (modalId.startsWith('rescheduleModal')) {
                    populateTimeSlots(modalId);
                }
                
                // Show modal immediately without animation
                modal.style.display = 'block';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Hide modal immediately without animation
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('custom-modal')) {
                const modalId = event.target.id;
                hideModal(modalId);
            }
        }

        function populateTimeSlots(modalId) {
            const timeSelect = document.querySelector(`#${modalId} #new_pickup_time`);
            if (!timeSelect) return;

            // Clear existing options
            timeSelect.innerHTML = '';

            // Add a default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select pickup time';
            timeSelect.appendChild(defaultOption);

            // Pre-generated time slots for better performance
            const timeSlots = [
                '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30',
                '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
                '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
                '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30'
            ];

            timeSlots.forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            });
        }

        function viewBooking(id) {
            console.log('Fetching booking details for ID:', id);
            fetch(`get_booking.php?id=${id}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        const booking = data.booking;
                        const modal = document.getElementById(`viewBookingModal${id}`);
                        if (modal) {
                            // Update modal content
                            modal.querySelector('#viewBookingId').textContent = booking.id;
                            modal.querySelector('#viewStatus').textContent = booking.status;
                            modal.querySelector('#viewStatus').className = `badge bg-${getStatusColor(booking.status)}`;
                            modal.querySelector('#viewStartDate').textContent = booking.start_date;
                            modal.querySelector('#viewTotalAmount').textContent = booking.total_price;
                            modal.querySelector('#viewMinibus').textContent = booking.minibus_name;
                            modal.querySelector('#viewDriver').textContent = booking.driver_name || 'Not assigned';
                            modal.querySelector('#viewBookingTime').textContent = booking.created_at;
                            modal.querySelector('#viewPickupTime').textContent = booking.pickup_time;

                            // Show/hide cancellation reason
                            const cancelReasonDiv = modal.querySelector('#viewCancelReason');
                            const cancelReasonText = modal.querySelector('#viewCancelReasonText');
                            if (booking.status === 'cancelled' && booking.cancel_reason) {
                                cancelReasonText.textContent = booking.cancel_reason;
                                cancelReasonDiv.style.display = 'block';
                            } else {
                                cancelReasonDiv.style.display = 'none';
                            }

                            // Show the modal
                            showModal(`viewBookingModal${id}`);
                        } else {
                            console.error('Modal element not found:', `viewBookingModal${id}`);
                            alert('Error: Modal element not found');
                        }
                    } else {
                        console.error('API error:', data.message);
                        alert(data.message || 'An error occurred while fetching booking details');
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('An error occurred while fetching booking details. Please check the console for more information.');
                });
        }

        function getStatusColor(status) {
            switch(status) {
                case 'confirmed': return 'success';
                case 'pending': return 'warning';
                case 'cancelled': return 'danger';
                default: return 'info';
            }
        }

        function initiatePayment(bookingId) {
            // Show loading state
            const payButton = event.target.closest('button');
            const originalText = payButton.innerHTML;
            payButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
            payButton.disabled = true;

            // Get payment data from server
            fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'booking_id=' + bookingId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Initialize Flutterwave payment
                    FlutterwaveCheckout({
                        ...data.data,
                        callback: function(response) {
                            // Handle payment callback
                            if (response.status === 'successful') {
                                // Verify payment
                                window.location.href = `process_payment.php?transaction_id=${response.transaction_id}&booking_id=${bookingId}`;
                            } else {
                                alert('Payment failed. Please try again.');
                                payButton.innerHTML = originalText;
                                payButton.disabled = false;
                            }
                        },
                        onClose: function() {
                            // Reset button state when payment modal is closed
                            payButton.innerHTML = originalText;
                            payButton.disabled = false;
                        }
                    });
                } else {
                    alert(data.message || 'Failed to initialize payment');
                    payButton.innerHTML = originalText;
                    payButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing payment');
                payButton.innerHTML = originalText;
                payButton.disabled = false;
            });
        }

        // Check for payment status in URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const paymentStatus = urlParams.get('payment');
            
            if (paymentStatus === 'success') {
                alert('Payment successful! Your booking has been confirmed.');
            } else if (paymentStatus === 'failed') {
                alert('Payment failed. Please try again.');
            }
        });

        // Function to safely set text content
        function setTextContent(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value || 'N/A';
            }
        }

        // Function to populate time select
        function populateNewPickupTimeSelect() {
            const select = document.getElementById('new_pickup_time');
            if (!select) return;

            const startHour = 0;
            const endHour = 23;
            const interval = 30;

            for (let hour = startHour; hour <= endHour; hour++) {
                for (let minute = 0; minute < 60; minute += interval) {
                    const time = new Date();
                    time.setHours(hour, minute);
                    const option = document.createElement('option');
                    option.value = time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                    option.textContent = option.value;
                    select.appendChild(option);
                }
            }
        }

        // Function to show booking details
        function showBookingDetails(id) {
            fetch(`get_booking.php?id=${id}`)
                .then(response => response.json())
                .then(response => {
                    if (response.success && response.booking) {
                        const booking = response.booking;
                        
                        // Format pickup location with coordinates if available
                        const pickupLocation = booking.pickup_location || 'Not specified';
                        const pickupCoords = booking.pickup_latitude && booking.pickup_longitude 
                            ? ` (${booking.pickup_latitude}, ${booking.pickup_longitude})`
                            : '';
                        
                        // Update all booking details
                        document.getElementById('viewBookingId').textContent = booking.id;
                        document.getElementById('viewStatus').textContent = booking.status;
                        document.getElementById('viewMinibus').textContent = booking.minibus_name || 'Not assigned';
                        document.getElementById('viewDriver').textContent = booking.driver_name || 'Not assigned';
                        document.getElementById('viewDriverContact').textContent = booking.driver_phone || 'N/A';
                        document.getElementById('viewStartDate').textContent = booking.start_date;
                        document.getElementById('viewPickupTime').textContent = booking.pickup_time;
                        document.getElementById('viewPickupLocation').textContent = pickupLocation + pickupCoords;
                        document.getElementById('viewBookingTime').textContent = booking.created_at;
                        document.getElementById('viewTotalAmount').textContent = booking.total_price;

                        // Show the modal
                        const modal = document.getElementById('viewBookingModal');
                        if (modal) {
                            modal.style.display = 'block';
                        }
                    } else {
                        console.error('Error:', response.message || 'Failed to load booking details');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            populateNewPickupTimeSelect();
        });
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-truck me-2 fs-4"></i>
                <span>Safari Minibus Rentals</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-gear me-1"></i>Our Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">
                            <i class="bi bi-calendar-check me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5" style="margin-top: 80px;">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="display-6 fw-bold text-primary mb-2">
                            <i class="bi bi-calendar-check me-3"></i>My Bookings
                        </h1>
                        <p class="text-muted mb-0">View and manage your Safari Minibus Rentals bookings, track status, and make payments.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display session messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-custom text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-calendar-x display-1 text-muted mb-4"></i>
                            <h3 class="text-muted mb-3">No Bookings Yet</h3>
                            <p class="text-muted mb-4">You haven't made any bookings yet. Start your journey by booking your first minibus!</p>
                            <a href="index.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-search me-2"></i>Browse Minibuses
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Bookings Grid -->
            <div class="row">
                <?php foreach ($bookings as $booking): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card border-0 shadow-custom h-100 booking-card">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-primary">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    Booking #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?>
                                </h6>
                                <span class="badge bg-<?php
                                    echo $booking['status'] == 'confirmed' ? 'success' :
                                        ($booking['status'] == 'pending' ? 'warning' :
                                        ($booking['status'] == 'cancelled' ? 'danger' : 'info'));
                                ?> px-3 py-2">
                                    <i class="bi bi-<?php
                                        echo $booking['status'] == 'confirmed' ? 'check-circle' :
                                            ($booking['status'] == 'pending' ? 'clock' :
                                            ($booking['status'] == 'cancelled' ? 'x-circle' : 'info-circle'));
                                    ?> me-1"></i>
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h5 class="card-title text-primary mb-2">
                                    <i class="bi bi-truck me-2"></i>
                                    <?php echo htmlspecialchars($booking['minibus_name']); ?>
                                </h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-people me-1"></i><?php echo $booking['capacity']; ?> seats capacity
                                </p>
                            </div>

                            <div class="mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="bi bi-calendar me-2"></i>
                                            <small><?php echo date('M d, Y', strtotime($booking['start_date'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center text-muted">
                                            <i class="bi bi-clock me-2"></i>
                                            <small><?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-geo-alt me-2 text-primary mt-1"></i>
                                    <div>
                                        <small class="text-muted">Pickup Location</small>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($booking['pickup_location']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($booking['driver_name']): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-badge me-2 text-primary"></i>
                                    <div>
                                        <small class="text-muted">Driver</small>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($booking['driver_name']); ?></div>
                                        <?php if ($booking['driver_phone']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?php echo htmlspecialchars($booking['driver_phone']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-person-x me-2"></i>
                                    <small>Driver not assigned yet</small>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <div class="price-display text-center py-2 bg-light rounded">
                                    <div class="fw-bold text-success fs-5">
                                        TZS <?php echo number_format($booking['total_price']); ?>
                                    </div>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </button>

                                <?php if ($booking['status'] === 'pending'): ?>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-success btn-sm w-100"
                                                onclick="initiatePayment(<?php echo $booking['id']; ?>)">
                                            <i class="bi bi-credit-card me-1"></i>Pay Now
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <div class="dropdown w-100">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots me-1"></i>More
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <button class="dropdown-item" onclick="showModal('rescheduleModal<?php echo $booking['id']; ?>')">
                                                        <i class="bi bi-calendar me-2"></i>Reschedule
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger" onclick="showModal('cancelModal<?php echo $booking['id']; ?>')">
                                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                                        <!-- View Booking Modal -->
                                        <div id="viewBookingModal<?php echo $booking['id']; ?>" class="custom-modal">
                                            <div class="custom-modal-content">
                                                <div class="custom-modal-header">
                                                    <h5 class="modal-title">Booking Details #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="custom-modal-close" 
                                                            onclick="hideModal('viewBookingModal<?php echo $booking['id']; ?>')">
                                                        &times;
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="booking-details">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Minibus:</strong> <span id="viewMinibus"></span></p>
                                                                <p><strong>Booking ID:</strong> <span id="viewBookingId"></span></p>
                                                                <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
                                                                <p><strong>Booking Time:</strong> <span id="viewBookingTime"></span></p>
                                                                <p><strong>Total Amount:</strong> TZS <span id="viewTotalAmount"></span></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Pickup Date:</strong> <span id="viewStartDate"></span></p>
                                                                <p><strong>Pickup Time:</strong> <span id="viewPickupTime"></span></p>
                                                                <p><strong>Pickup Location:</strong> <span id="viewPickupLocation"></span></p>
                                                                <p><strong>Driver:</strong> <span id="viewDriver"></span></p>
                                                                <p><strong>Driver Contact:</strong> <span id="viewDriverContact"></span></p>
                                                            </div>
                                                        </div>
                                                        <div id="viewCancelReason" class="mt-3" style="display: none;">
                                                            <div class="alert alert-warning">
                                                                <h6 class="alert-heading">Cancellation Information</h6>
                                                                <p class="mb-0"><strong>Reason for Cancellation:</strong> <span id="viewCancelReasonText"></span></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reschedule Modal -->
                                        <div id="rescheduleModal<?php echo $booking['id']; ?>" class="custom-modal">
                                            <div class="custom-modal-content">
                                                <div class="custom-modal-header">
                                                    <h5 class="modal-title">Reschedule Booking #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="custom-modal-close" 
                                                            onclick="hideModal('rescheduleModal<?php echo $booking['id']; ?>')">
                                                        &times;
                                                    </button>
                                                </div>
                                                <form action="reschedule_booking.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="new_start_date" class="form-label">New Start Date</label>
                                                                    <input type="date" class="form-control" id="new_start_date" name="new_start_date" 
                                                                           min="<?php echo date('Y-m-d'); ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="new_pickup_time" class="form-label">New Pickup Time</label>
                                                                    <select class="form-select" id="new_pickup_time" name="new_pickup_time" required>
                                                                        <!-- Will be populated by JavaScript -->
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <div class="alert alert-info">
                                                                    <i class="bi bi-info-circle"></i> Your reschedule request will be reviewed by our team.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                onclick="hideModal('rescheduleModal<?php echo $booking['id']; ?>')">Close</button>
                                                        <button type="submit" class="btn btn-warning">Request Reschedule</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Cancel Modal -->
                                        <div id="cancelModal<?php echo $booking['id']; ?>" class="custom-modal">
                                            <div class="custom-modal-content">
                                                <div class="custom-modal-header">
                                                    <h5 class="modal-title">Cancel Booking #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="custom-modal-close" 
                                                            onclick="hideModal('cancelModal<?php echo $booking['id']; ?>')">
                                                        &times;
                                                    </button>
                                                </div>
                                                <form action="cancel_booking.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="cancel_reason" class="form-label">Reason for Cancellation</label>
                                                            <textarea class="form-control" id="cancel_reason" name="cancel_reason" 
                                                                      rows="3" required></textarea>
                                                        </div>

                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle"></i> Are you sure you want to cancel this booking? 
                                                            This action cannot be undone.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                onclick="hideModal('cancelModal<?php echo $booking['id']; ?>')">Close</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>
                        <i class="bi bi-truck me-2"></i>Safari Minibus Rentals
                    </h5>
                    <p class="mb-3">
                        Your trusted partner for comfortable and reliable minibus transportation
                        across Tanzania. Experience the beauty of our country with our premium fleet.
                    </p>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"><i class="bi bi-house me-2"></i>Home</a></li>
                        <li class="mb-2"><a href="about.php"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                        <li class="mb-2"><a href="services.php"><i class="bi bi-gear me-2"></i>Our Services</a></li>
                        <li class="mb-2"><a href="bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                        <li class="mb-2"><a href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact Information</h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone me-3 text-accent"></i>
                            <span>+255 683749514</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope me-3 text-accent"></i>
                            <span>info@safariminibus.co.tz</span>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-geo-alt me-3 text-accent mt-1"></i>
                            <span>Dar es Salaam, Tanzania</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.2);">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Safari Minibus Rentals. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle" id="backToTop" style="width: 50px; height: 50px; display: none; z-index: 1000;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');
            const backToTop = document.getElementById('backToTop');

            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTop.style.display = 'block';
            } else {
                navbar.classList.remove('scrolled');
                backToTop.style.display = 'none';
            }
        });

        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Add animation to booking cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe booking cards for animation
        document.querySelectorAll('.booking-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>