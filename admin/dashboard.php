<?php
session_start();
require_once '../config/database.php';

// Set timezone to Tanzania
date_default_timezone_set('Africa/Dar_es_Salaam');

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
    <link rel="stylesheet" href="../assets/css/style.css?v=1.0">
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

        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-lg-4 col-md-6 mb-4">
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

            <div class="col-lg-4 col-md-6 mb-4">
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

            <div class="col-lg-4 col-md-6 mb-4">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fetchLatestData() {
            fetch('minibuses.php')
                .then(response => response.text())
                .then(data => {
                    // Update the dashboard content with the latest data
                    document.getElementById('dashboardContent').innerHTML = data;
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Fetch data every 30 seconds
        setInterval(fetchLatestData, 30000);

        // Initial fetch
        fetchLatestData();

        // Auto-refresh disabled for performance
        // Manual refresh button available in the UI instead

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