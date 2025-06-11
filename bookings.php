<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's bookings with related information
$stmt = $pdo->prepare("
    SELECT b.*, 
           m.name as minibus_name, m.capacity,
           d.name as driver_name, d.phone as driver_phone
    FROM bookings b
    JOIN minibuses m ON b.minibus_id = m.id
    LEFT JOIN drivers d ON m.driver_id = d.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Safari Minibus Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
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

            // Generate time slots for 24 hours
            for (let hour = 0; hour < 24; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    const time = new Date();
                    time.setHours(hour, minute, 0);
                    
                    const timeString = time.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    });

                    const option = document.createElement('option');
                    option.value = timeString;
                    option.textContent = timeString;
                    timeSelect.appendChild(option);
                }
            }
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
                            modal.querySelector('#viewCreatedAt').textContent = booking.created_at;
                            modal.querySelector('#viewUpdatedAt').textContent = booking.updated_at;

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
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Safari Minibus Rentals</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <h2 class="mb-4">My Bookings</h2>

        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                You haven't made any bookings yet. <a href="index.php">Browse our minibuses</a> to make a booking.
            </div>
        <?php else: ?>
            <!-- Bookings Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Minibus</th>
                                    <th>Driver</th>
                                    <th>Dates</th>
                                    <th>Pickup</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['minibus_name']); ?><br>
                                        <small class="text-muted"><?php echo $booking['capacity']; ?> seats</small>
                                    </td>
                                    <td>
                                        <?php if ($booking['driver_name']): ?>
                                            <?php echo htmlspecialchars($booking['driver_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['driver_phone']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($booking['start_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['pickup_location']); ?><br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></small>
                                    </td>
                                    <td>TZS <?php echo number_format($booking['total_price']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] == 'confirmed' ? 'success' : 
                                                ($booking['status'] == 'pending' ? 'warning' : 
                                                ($booking['status'] == 'cancelled' ? 'danger' : 'info')); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                            <i class="bi bi-eye"></i> View Details
                                        </button>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="showModal('rescheduleModal<?php echo $booking['id']; ?>')">
                                            <i class="bi bi-calendar"></i> Reschedule
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="showModal('cancelModal<?php echo $booking['id']; ?>')">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                        <?php endif; ?>

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
                                                        <h5 class="mb-3">Booking Details</h5>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Booking ID:</strong> <span id="viewBookingId"></span></p>
                                                                <p><strong>Status:</strong> <span id="viewStatus" class="badge"></span></p>
                                                                <p><strong>Start Date:</strong> <span id="viewStartDate"></span></p>
                                                                <p><strong>Total Amount:</strong> TZS <span id="viewTotalAmount"></span></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Minibus:</strong> <span id="viewMinibus"></span></p>
                                                                <p><strong>Driver:</strong> <span id="viewDriver"></span></p>
                                                                <p><strong>Created At:</strong> <span id="viewCreatedAt"></span></p>
                                                                <p><strong>Last Updated:</strong> <span id="viewUpdatedAt"></span></p>
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
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Phone: +255 683749514<br>
                    Email: info@safariminibus.co.tz</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="services.php" class="text-light">Our Services</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</body>
</html> 