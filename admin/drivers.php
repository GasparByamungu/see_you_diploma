<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle driver deletion
if (isset($_POST['delete_driver'])) {
    $id = $_POST['driver_id'];
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: drivers.php");
    exit();
}

// Get all drivers
$drivers = $pdo->query("SELECT * FROM drivers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers - Safari Minibus Rentals</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="minibuses.php">Minibuses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="drivers.php">Drivers</a>
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

    <!-- Main Content -->
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Drivers</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                <i class="bi bi-plus-circle"></i> Add New Driver
            </button>
        </div>

        <!-- Drivers Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>License Number</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                            <tr>
                                <td><?php echo $driver['id']; ?></td>
                                <td><?php echo htmlspecialchars($driver['name']); ?></td>
                                <td><?php echo htmlspecialchars($driver['license_number']); ?></td>
                                <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $driver['status'] == 'available' ? 'success' : 
                                            ($driver['status'] == 'assigned' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($driver['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editDriver(<?php echo htmlspecialchars(json_encode($driver)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDriver(<?php echo $driver['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Driver Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_driver.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="license_number" class="form-label">License Number</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="off">Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Driver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Driver Modal -->
    <div class="modal fade" id="editDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="edit_driver.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_license_number" class="form-label">License Number</label>
                            <input type="text" class="form-control" id="edit_license_number" name="license_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="available">Available</option>
                                <option value="assigned">Assigned</option>
                                <option value="off">Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this driver?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="driver_id" id="delete_driver_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_driver" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDriver(driver) {
            document.getElementById('edit_id').value = driver.id;
            document.getElementById('edit_name').value = driver.name;
            document.getElementById('edit_license_number').value = driver.license_number;
            document.getElementById('edit_phone').value = driver.phone;
            document.getElementById('edit_status').value = driver.status;
            
            new bootstrap.Modal(document.getElementById('editDriverModal')).show();
        }

        function deleteDriver(id) {
            document.getElementById('delete_driver_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteDriverModal')).show();
        }
    </script>
</body>
</html> 