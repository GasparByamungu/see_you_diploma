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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('custom-modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
    </script>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="minibuses.php">Minibuses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="drivers.php">Drivers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <h2 class="mb-4">Manage Bookings</h2>

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
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="showModal('viewBookingModal<?php echo $booking['id']; ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="update_booking.php" method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                                <i class="bi bi-check-circle"></i> Confirm
                                            </button>
                                        </form>
                                        <form action="update_booking.php" method="POST" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </form>
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
                                                <div class="mb-4">
                                                    <h5>Customer Information</h5>
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['user_name']); ?></p>
                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['user_phone']); ?></p>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <h5>Minibus Information</h5>
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['minibus_name']); ?></p>
                                                    <p><strong>Capacity:</strong> <?php echo $booking['capacity']; ?> seats</p>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <h5>Booking Details</h5>
                                                    <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></p>
                                                    <p><strong>Pickup Time:</strong> <?php echo date('h:i A', strtotime($booking['pickup_time'])); ?></p>
                                                    <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                                                    <?php if (!empty($booking['dropoff_location'])): ?>
                                                    <p><strong>Dropoff Location:</strong> <?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                                                    <?php endif; ?>
                                                    <p><strong>Total Price:</strong> TZS <?php echo number_format($booking['total_price']); ?></p>
                                                    <p><strong>Status:</strong> <?php echo ucfirst($booking['status']); ?></p>
                                                </div>
                                            </div>
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
    </div>
</body>
</html> 