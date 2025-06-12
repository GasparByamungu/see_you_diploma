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
    <meta name="description" content="Admin panel for managing drivers, adding new drivers, and updating driver information.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .driver-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-available { background-color: var(--success); }
        .status-assigned { background-color: var(--warning); }
        .status-off { background-color: var(--danger); }
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
                        <a class="nav-link active" href="drivers.php">
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

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="display-6 fw-bold text-primary mb-2">
                            <i class="bi bi-person-badge me-3"></i>Driver Management
                        </h1>
                        <p class="text-muted mb-0">Manage your driver team, add new drivers, and update driver information</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="bi bi-person-plus me-2"></i>Add New Driver
                    </button>
                </div>
            </div>
        </div>

        <!-- Driver Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-people display-6 mb-2"></i>
                        <h4><?php echo count($drivers); ?></h4>
                        <small>Total Drivers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($drivers, fn($d) => $d['status'] === 'available')); ?></h4>
                        <small>Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-person-check display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($drivers, fn($d) => $d['status'] === 'assigned')); ?></h4>
                        <small>Assigned</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-person-x display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($drivers, fn($d) => $d['status'] === 'off')); ?></h4>
                        <small>Off Duty</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drivers Table -->
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-white border-0 py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 text-primary">
                        <i class="bi bi-list-ul me-2"></i>Driver Team
                    </h4>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search drivers..." id="searchInput">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($drivers)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-badge display-1 text-muted mb-3"></i>
                    <h5 class="text-muted">No Drivers Found</h5>
                    <p class="text-muted">Start building your team by adding your first driver.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                        <i class="bi bi-person-plus me-2"></i>Add First Driver
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="driversTable">
                        <thead>
                            <tr>
                                <th class="border-0 py-3">Driver</th>
                                <th class="border-0 py-3">License Info</th>
                                <th class="border-0 py-3">Contact</th>
                                <th class="border-0 py-3">Status</th>
                                <th class="border-0 py-3">Joined</th>
                                <th class="border-0 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drivers as $driver): ?>
                            <tr class="driver-row">
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="driver-avatar me-3">
                                            <?php echo strtoupper(substr($driver['name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($driver['name']); ?></div>
                                            <small class="text-muted">ID: #<?php echo str_pad($driver['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="bi bi-card-text me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($driver['license_number']); ?>
                                        </div>
                                        <small class="text-muted">License Number</small>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="bi bi-telephone me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($driver['phone']); ?>
                                        </div>
                                        <small class="text-muted">Phone Number</small>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="status-indicator status-<?php echo $driver['status']; ?>"></span>
                                    <span class="badge bg-<?php
                                        echo $driver['status'] == 'available' ? 'success' :
                                            ($driver['status'] == 'assigned' ? 'warning' : 'danger');
                                    ?> px-3 py-2">
                                        <i class="bi bi-<?php
                                            echo $driver['status'] == 'available' ? 'check-circle' :
                                                ($driver['status'] == 'assigned' ? 'person-check' : 'person-x');
                                        ?> me-1"></i>
                                        <?php echo ucfirst($driver['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="text-muted">
                                        <?php echo date('M d, Y', strtotime($driver['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="editDriver(<?php echo htmlspecialchars(json_encode($driver)); ?>)"
                                                title="Edit Driver">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteDriver(<?php echo $driver['id']; ?>)"
                                                title="Delete Driver">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
            if (confirm('Are you sure you want to delete this driver? This action cannot be undone.')) {
                document.getElementById('delete_driver_id').value = id;
                new bootstrap.Modal(document.getElementById('deleteDriverModal')).show();
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.driver-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
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

        // Phone number formatting
        document.querySelectorAll('input[type="tel"]').forEach(input => {
            input.addEventListener('input', function() {
                // Remove non-numeric characters except +
                let value = this.value.replace(/[^\d+]/g, '');

                // Ensure it starts with + for international format
                if (value && !value.startsWith('+')) {
                    value = '+255' + value;
                }

                this.value = value;
            });
        });
    </script>
</body>
</html> 