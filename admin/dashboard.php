<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get statistics
$stats = [
    'total_minibuses' => $pdo->query("SELECT COUNT(*) FROM minibuses")->fetchColumn(),
    'available_minibuses' => $pdo->query("SELECT COUNT(*) FROM minibuses WHERE status = 'available'")->fetchColumn(),
    'total_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'pending_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'total_drivers' => $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
    'available_drivers' => $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'available'")->fetchColumn()
];

// Get recent bookings
$recent_bookings = $pdo->query("
    SELECT b.*, 
           u.name as user_name, u.phone as user_phone,
           m.name as minibus_name, m.capacity,
           d.name as driver_name, d.phone as driver_phone
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN minibuses m ON b.minibus_id = m.id 
    LEFT JOIN drivers d ON m.driver_id = d.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Safari Minibus Rentals</title>
    <meta name="description" content="Admin dashboard for managing minibuses, drivers, and bookings.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="bookings.php">
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

    <!-- Dashboard Content -->
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="display-5 fw-bold text-primary mb-2">
                            <i class="bi bi-speedometer2 me-3"></i>Dashboard Overview
                        </h1>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening with your fleet.</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Last updated: <?php echo date('M d, Y - h:i A'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-icon bg-primary text-white">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total_minibuses']; ?></div>
                            <div class="stat-label">Total Minibuses</div>
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo $stats['available_minibuses']; ?> Available
                            </small>
                        </div>
                        <div class="text-end">
                            <a href="minibuses.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-icon bg-success text-white">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                            <small class="text-warning">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo $stats['pending_bookings']; ?> Pending
                            </small>
                        </div>
                        <div class="text-end">
                            <a href="bookings.php" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-icon bg-info text-white">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="stat-number"><?php echo $stats['total_drivers']; ?></div>
                            <div class="stat-label">Total Drivers</div>
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo $stats['available_drivers']; ?> Available
                            </small>
                        </div>
                        <div class="text-end">
                            <a href="drivers.php" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-icon bg-warning text-white">
                                <i class="bi bi-currency-exchange"></i>
                            </div>
                            <div class="stat-number">
                                <?php
                                $revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed'")->fetchColumn();
                                echo $revenue ? number_format($revenue/1000, 0) . 'K' : '0';
                                ?>
                            </div>
                            <div class="stat-label">Revenue (TZS)</div>
                            <small class="text-muted">
                                <i class="bi bi-graph-up me-1"></i>
                                Completed bookings
                            </small>
                        </div>
                        <div class="text-end">
                            <a href="bookings.php" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-custom">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1 text-primary">
                                    <i class="bi bi-clock-history me-2"></i>Recent Bookings
                                </h4>
                                <p class="text-muted mb-0">Latest booking activities and status updates</p>
                            </div>
                            <a href="bookings.php" class="btn btn-outline-primary">
                                <i class="bi bi-eye me-2"></i>View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_bookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x display-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No Recent Bookings</h5>
                            <p class="text-muted">New bookings will appear here when customers make reservations.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="border-0">Booking</th>
                                        <th class="border-0">Customer</th>
                                        <th class="border-0">Minibus</th>
                                        <th class="border-0">Driver</th>
                                        <th class="border-0">Schedule</th>
                                        <th class="border-0">Amount</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
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
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                                <small class="text-muted">
                                                    <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($booking['user_phone']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($booking['minibus_name']); ?></div>
                                                <small class="text-muted">
                                                    <i class="bi bi-people me-1"></i><?php echo $booking['capacity']; ?> seats
                                                </small>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($booking['driver_name']): ?>
                                                <div>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($booking['driver_name']); ?></div>
                                                    <small class="text-muted">
                                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($booking['driver_phone']); ?>
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="bi bi-person-x me-1"></i>Not assigned
                                                </span>
                                            <?php endif; ?>
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
                                                    <li><a class="dropdown-item" href="bookings.php?id=<?php echo $booking['id']; ?>">
                                                        <i class="bi bi-eye me-2"></i>View Details
                                                    </a></li>
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                    <li><a class="dropdown-item text-success" href="update_booking.php?id=<?php echo $booking['id']; ?>&status=confirmed">
                                                        <i class="bi bi-check-circle me-2"></i>Confirm
                                                    </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh dashboard data every 30 seconds
        setInterval(function() {
            // Update timestamp
            const now = new Date();
            const timeString = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            const timestampElement = document.querySelector('.text-end small');
            if (timestampElement) {
                timestampElement.textContent = `Last updated: ${timeString}`;
            }
        }, 30000);

        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Animate numbers on page load
        document.addEventListener('DOMContentLoaded', function() {
            const numbers = document.querySelectorAll('.stat-number');

            numbers.forEach(number => {
                const finalValue = parseInt(number.textContent.replace(/[^\d]/g, ''));
                let currentValue = 0;
                const increment = finalValue / 50;

                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        number.textContent = number.textContent; // Keep original format
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(currentValue);
                    }
                }, 30);
            });
        });

        // Add confirmation for status changes
        document.querySelectorAll('a[href*="status=confirmed"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to confirm this booking?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>