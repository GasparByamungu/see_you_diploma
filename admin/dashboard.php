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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Safari Minibus Rentals</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="minibuses.php">Minibuses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="drivers.php">Drivers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container my-5">
        <h2 class="mb-4">Admin Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Minibuses</h5>
                        <p class="card-text">
                            Total: <?php echo $stats['total_minibuses']; ?><br>
                            Available: <?php echo $stats['available_minibuses']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Bookings</h5>
                        <p class="card-text">
                            Total: <?php echo $stats['total_bookings']; ?><br>
                            Pending: <?php echo $stats['pending_bookings']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Drivers</h5>
                        <p class="card-text">
                            Total: <?php echo $stats['total_drivers']; ?><br>
                            Available: <?php echo $stats['available_drivers']; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Bookings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
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
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 