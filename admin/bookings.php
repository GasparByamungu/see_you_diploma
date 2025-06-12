<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get all bookings with related information
$stmt = $pdo->query("
    SELECT b.*, 
           u.name as user_name, u.phone as user_phone,
           m.name as minibus_name, m.capacity,
           d.name as driver_name, d.phone as driver_phone
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN minibuses m ON b.minibus_id = m.id
    LEFT JOIN drivers d ON m.driver_id = d.id
    ORDER BY b.created_at DESC
");
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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search bookings..." id="searchInput">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event display-6 mb-2"></i>
                        <h4><?php echo count($bookings); ?></h4>
                        <small>Total Bookings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-clock display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'pending')); ?></h4>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')); ?></h4>
                        <small>Confirmed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-currency-exchange display-6 mb-2"></i>
                        <h4>
                            <?php
                            $totalRevenue = array_sum(array_column(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'), 'total_price'));
                            echo number_format($totalRevenue/1000, 0) . 'K';
                            ?>
                        </h4>
                        <small>Revenue (TZS)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-white border-0 py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 text-primary">
                        <i class="bi bi-list-ul me-2"></i>All Bookings
                    </h4>
                    <div class="d-flex gap-2">
                        <select class="form-select" id="statusFilter" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                    <h5 class="text-muted">No Bookings Found</h5>
                    <p class="text-muted">Customer bookings will appear here when they make reservations.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="bookingsTable">
                        <thead>
                            <tr>
                                <th class="border-0 py-3">Booking</th>
                                <th class="border-0 py-3">Customer</th>
                                <th class="border-0 py-3">Vehicle</th>
                                <th class="border-0 py-3">Schedule</th>
                                <th class="border-0 py-3">Amount</th>
                                <th class="border-0 py-3">Status</th>
                                <th class="border-0 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row booking-status-<?php echo $booking['status']; ?>" data-status="<?php echo $booking['status']; ?>">
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-calendar-check text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="customer-avatar me-3">
                                            <?php echo strtoupper(substr($booking['user_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($booking['user_phone']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($booking['minibus_name']); ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i><?php echo $booking['capacity']; ?> seats
                                            <?php if ($booking['driver_name']): ?>
                                                | <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($booking['driver_name']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($booking['start_date'])); ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?>
                                        </small>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="fw-bold text-success">
                                        TZS <?php echo number_format($booking['total_price']); ?>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="status-indicator status-<?php echo $booking['status']; ?>"></span>
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
                                </td>
                                <td class="py-3">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewBookingModal<?php echo $booking['id']; ?>">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </button>
                                            </li>
                                            <?php if ($booking['status'] == 'pending'): ?>
                                            <li>
                                                <form action="update_booking.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="dropdown-item text-success" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                                        <i class="bi bi-check-circle me-2"></i>Confirm
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="update_booking.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Booking Detail Modals -->
    <?php foreach ($bookings as $booking): ?>
    <div class="modal fade" id="viewBookingModal<?php echo $booking['id']; ?>" tabindex="-1" aria-labelledby="viewBookingModalLabel<?php echo $booking['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewBookingModalLabel<?php echo $booking['id']; ?>">
                        <i class="bi bi-calendar-check me-2"></i>Booking Details #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="bi bi-person me-2"></i>Customer Information
                                    </h6>
                                    <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                                    <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($booking['user_phone']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">
                                        <i class="bi bi-truck me-2"></i>Vehicle Information
                                    </h6>
                                    <p class="mb-2"><strong>Minibus:</strong> <?php echo htmlspecialchars($booking['minibus_name']); ?></p>
                                    <p class="mb-2"><strong>Capacity:</strong> <?php echo $booking['capacity']; ?> seats</p>
                                    <p class="mb-0"><strong>Driver:</strong>
                                        <?php echo $booking['driver_name'] ? htmlspecialchars($booking['driver_name']) : 'Not assigned'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="bi bi-calendar-event me-2"></i>Booking Details
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                                    <p class="mb-2"><strong>Pickup Time:</strong> <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></p>
                                    <p class="mb-2"><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!empty($booking['dropoff_location'])): ?>
                                    <p class="mb-2"><strong>Dropoff Location:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-2"><strong>Total Price:</strong> <span class="text-success fw-bold">TZS <?php echo number_format($booking['total_price']); ?></span></p>
                                    <p class="mb-0"><strong>Status:</strong>
                                        <span class="badge bg-<?php
                                            echo $booking['status'] == 'confirmed' ? 'success' :
                                                ($booking['status'] == 'pending' ? 'warning' :
                                                ($booking['status'] == 'cancelled' ? 'danger' : 'info'));
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($booking['status'] == 'pending'): ?>
                    <form action="update_booking.php" method="POST" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this booking?')">
                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                        </button>
                    </form>
                    <form action="update_booking.php" method="POST" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                            <i class="bi bi-x-circle me-2"></i>Cancel Booking
                        </button>
                    </form>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

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

        // Auto-refresh every 2 minutes
        setInterval(() => {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>