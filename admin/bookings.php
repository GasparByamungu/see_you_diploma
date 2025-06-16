<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 100; // Show 100 bookings per page
$offset = ($page - 1) * $limit;

// Get total count for pagination
$total_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_pages = ceil($total_count / $limit);

// Get bookings with pagination
$stmt = $pdo->prepare("
    SELECT b.id, b.minibus_id, b.user_id, b.start_date, b.pickup_time, 
           b.pickup_location, b.pickup_latitude, b.pickup_longitude,
           b.dropoff_location, b.dropoff_latitude, b.dropoff_longitude,
           b.route_distance, b.route_duration, b.total_price, b.notes,
           b.status, b.cancellation_reason, b.reschedule_request,
           b.created_at, b.updated_at,
           u.name as user_name, u.phone as user_phone,
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
    JOIN users u ON b.user_id = u.id
    JOIN minibuses m ON b.minibus_id = m.id
    LEFT JOIN drivers d ON m.driver_id = d.id
    ORDER BY b.updated_at DESC, b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Safari Minibus Rentals</title>
    <meta name="description" content="Admin panel for managing customer bookings, confirming reservations, and tracking booking status.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0">
    <style>
        .booking-card {
            transition: all var(--transition-normal);
            border-left: 4px solid var(--primary-color);
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .booking-status-pending { border-left-color: var(--warning); }
        .booking-status-confirmed { border-left-color: var(--success); }
        .booking-status-cancelled { border-left-color: var(--danger); }
        .booking-status-completed { border-left-color: var(--info); }

        .customer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-pending { background-color: var(--warning); }
        .status-confirmed { background-color: var(--success); }
        .status-cancelled { background-color: var(--danger); }
        .status-completed { background-color: var(--info); }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg admin-nav">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="bi bi-shield-check me-2 fs-4"></i>
                <span>Safari Admin Panel</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="minibuses.php">
                            <i class="bi bi-truck me-1"></i>Minibuses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="drivers.php">
                            <i class="bi bi-person-badge me-1"></i>Drivers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">
                            <i class="bi bi-calendar-check me-1"></i>Bookings
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../index.php"><i class="bi bi-house me-2"></i>View Site</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="display-6 fw-bold text-primary mb-2">
                            <i class="bi bi-calendar-check me-3"></i>Booking Management
                        </h1>
                        <p class="text-muted mb-0">Monitor and manage customer bookings, confirmations, and reservations</p>
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

        <!-- Booking Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event display-6 mb-2"></i>
                        <h4><?php echo count($bookings); ?></h4>
                        <small>Total Bookings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-clock display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'pending')); ?></h4>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')); ?></h4>
                        <small>Confirmed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
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
                                <td><?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['user_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['user_phone']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['minibus_name']); ?><br>
                                    <small class="text-muted"><?php echo $booking['capacity']; ?> seats</small>
                                </td>
                                <td>
                                    <?php if ($booking['driver_name']): ?>
                                        <?php echo htmlspecialchars($booking['driver_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($booking['driver_phone']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($booking['start_date'])); ?><br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></small>
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
                                    <?php if ($booking['status'] === 'confirmed' || $booking['status'] === 'cancelled'): ?>
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewBookingModal<?php echo $booking['id']; ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewBookingModal<?php echo $booking['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-success btn-sm" onclick="confirmBooking(<?php echo $booking['id']; ?>)">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelBookingModal<?php echo $booking['id']; ?>">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Bookings pagination">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Booking Detail Modals -->
    <?php foreach ($bookings as $booking): ?>
    <div class="modal fade" id="viewBookingModal<?php echo $booking['id']; ?>" tabindex="-1" aria-labelledby="viewBookingModalLabel<?php echo $booking['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewBookingModalLabel<?php echo $booking['id']; ?>">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Booking ID:</strong> <?php echo $booking['id']; ?></p>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['user_phone']); ?></p>
                            <p><strong>Minibus:</strong> <?php echo htmlspecialchars($booking['minibus_name']); ?></p>
                            <p><strong>Driver:</strong> <?php echo $booking['driver_name'] ? htmlspecialchars($booking['driver_name']) : 'Not assigned'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                            <p><strong>Pickup Time:</strong> <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></p>
                            <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                            <?php if (!empty($booking['dropoff_location'])): ?>
                            <p><strong>Dropoff Location:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                            <?php endif; ?>
                            <p><strong>Total Price:</strong> TZS <?php echo number_format($booking['total_price']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $booking['status'] === 'confirmed' ? 'success' : 
                                        ($booking['status'] === 'pending' ? 'warning' : 
                                        ($booking['status'] === 'cancelled' ? 'danger' : 'info')); 
                                ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </p>
                            <?php if ($booking['status'] === 'cancelled'): ?>
                                <?php 
                                // Debug information
                                error_log("Booking ID: " . $booking['id']);
                                error_log("Status: " . $booking['status']);
                                error_log("Cancellation Reason: " . ($booking['cancellation_reason'] ?? 'null'));
                                ?>
                                <div class="alert alert-danger mt-3">
                                    <strong>Cancellation Information:</strong><br>
                                    <?php if (!empty($booking['cancellation_reason'])): ?>
                                        <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($booking['cancellation_reason'])); ?>
                                    <?php else: ?>
                                        <em>No reason was provided for cancellation.</em>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($booking['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#rescheduleBookingModal<?php echo $booking['id']; ?>">
                        <i class="bi bi-calendar-event me-1"></i>Reschedule Booking
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Reschedule Booking Modal -->
    <div class="modal fade" id="rescheduleBookingModal<?php echo $booking['id']; ?>" tabindex="-1" aria-labelledby="rescheduleBookingModalLabel<?php echo $booking['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rescheduleBookingModalLabel<?php echo $booking['id']; ?>">Reschedule Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rescheduleForm<?php echo $booking['id']; ?>" action="reschedule_booking.php" method="POST">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="reschedule_date<?php echo $booking['id']; ?>" class="form-label">New Pickup Date</label>
                                <input type="date" class="form-control" id="reschedule_date<?php echo $booking['id']; ?>" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="reschedule_time<?php echo $booking['id']; ?>" class="form-label">New Pickup Time</label>
                                <select class="form-select" id="reschedule_time<?php echo $booking['id']; ?>" name="pickup_time" required>
                                    <option value="">Select pickup time</option>
                                    <?php
                                    // Generate time options in 12-hour format
                                    for ($hour = 0; $hour < 24; $hour++) {
                                        // Format for o'clock times
                                        $time = sprintf("%02d:00", $hour);
                                        $display = date("g:i A", strtotime($time));
                                        echo "<option value=\"$time\">$display</option>";
                                        
                                        // Format for half past times
                                        $time = sprintf("%02d:30", $hour);
                                        $display = date("g:i A", strtotime($time));
                                        echo "<option value=\"$time\">$display</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reschedule_reason<?php echo $booking['id']; ?>" class="form-label">Reason for Rescheduling</label>
                            <textarea class="form-control" id="reschedule_reason<?php echo $booking['id']; ?>" name="reschedule_reason" rows="3" required></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> The customer will be notified of this rescheduling request and will need to confirm the new date and time.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitReschedule(<?php echo $booking['id']; ?>)">Submit Reschedule Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.booking-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('.booking-row');

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (selectedStatus === '' || rowStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add loading states to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
                    submitBtn.disabled = true;

                    // Re-enable after 5 seconds in case of error
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            } else {
                console.error('Modal element not found:', modalId);
            }
        }

        function submitReschedule(bookingId) {
            const form = document.getElementById('rescheduleForm' + bookingId);
            const formData = new FormData(form);

            fetch('reschedule_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Reschedule request submitted successfully');
                    // Reload the page to show updated status
                    window.location.reload();
                } else {
                    // Show error message
                    alert(data.message || 'Error submitting reschedule request');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting reschedule request');
            });
        }
    </script>
</body>
</html>