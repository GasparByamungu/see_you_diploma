<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle minibus deletion
if (isset($_POST['delete_minibus'])) {
    $id = $_POST['minibus_id'];
    $stmt = $pdo->prepare("DELETE FROM minibuses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: minibuses.php");
    exit();
}

// Get all minibuses with their images and driver information
$stmt = $pdo->query("
    SELECT DISTINCT 
           m.id, m.name, m.capacity, m.price_per_km, m.features, 
           m.status, m.driver_id, m.created_at,
           d.name as driver_name, d.license_number, d.phone as driver_phone,
           GROUP_CONCAT(DISTINCT mi.image_path ORDER BY mi.display_order) as images,
           (SELECT COUNT(*) FROM bookings b WHERE b.minibus_id = m.id AND b.status = 'confirmed') as active_bookings
    FROM minibuses m 
    LEFT JOIN drivers d ON m.driver_id = d.id 
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    GROUP BY m.id, m.name, m.capacity, m.price_per_km, m.features, 
             m.status, m.driver_id, m.created_at,
             d.name, d.license_number, d.phone
    ORDER BY m.created_at DESC
");
$minibuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the images string into arrays
foreach ($minibuses as &$minibus) {
    $minibus['images'] = $minibus['images'] ? explode(',', $minibus['images']) : [];
}

// Get available drivers for assignment
$available_drivers = $pdo->query("
    SELECT id, name, license_number 
    FROM drivers 
    WHERE status = 'available'
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_minibuses = count($minibuses);
$available_minibuses = count(array_filter($minibuses, fn($m) => $m['status'] === 'available'));
$booked_minibuses = count(array_filter($minibuses, fn($m) => $m['status'] === 'booked'));
$maintenance_minibuses = count(array_filter($minibuses, fn($m) => $m['status'] === 'maintenance'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Minibuses - Safari Minibus Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .minibus-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .minibus-card:hover {
            transform: translateY(-5px);
        }
        .image-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .feature-badge {
            font-size: 0.8rem;
            margin: 2px;
        }
        .driver-info {
            font-size: 0.9rem;
        }
        .action-buttons {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .minibus-card:hover .action-buttons {
            opacity: 1;
        }
        /* Table scroll styles */
        .table-responsive {
            max-height: 500px;
            min-height: 200px;
            overflow-y: scroll;
            border: 1px solid #ddd;
        }
        .table-responsive::-webkit-scrollbar {
            width: 12px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }
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
                        <a class="nav-link active" href="minibuses.php">
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

    <!-- Main Content -->
    <div class="container-fluid py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="display-6 fw-bold text-primary mb-2">
                            <i class="bi bi-truck me-3"></i>Minibus Management
                        </h1>
                        <p class="text-muted mb-0">Manage your fleet, assign drivers, and track vehicle status</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addMinibusModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Minibus
                    </button>
                </div>
            </div>
        </div>
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-truck display-6 mb-2"></i>
                        <h4><?php echo $total_minibuses; ?></h4>
                        <small>Total Minibuses</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle display-6 mb-2"></i>
                        <h4><?php echo $available_minibuses; ?></h4>
                        <small>Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check display-6 mb-2"></i>
                        <h4><?php echo $booked_minibuses; ?></h4>
                        <small>Booked</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-tools display-6 mb-2"></i>
                        <h4><?php echo $maintenance_minibuses; ?></h4>
                        <small>In Maintenance</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-custom">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light sticky-top bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Images</th>
                                        <th>Name</th>
                                        <th>Capacity</th>
                                        <th>Price/1km (TZS)</th>
                                        <th>Features</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($minibuses)): ?>
                                        <tr><td colspan="9" class="text-center text-muted">No minibuses available.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($minibuses as $minibus): ?>
                                        <tr>
                                            <td class="align-middle">#<?php echo str_pad($minibus['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <div class="image-preview-container d-flex gap-2">
                                                    <?php if (!empty($minibus['images'])): ?>
                                                        <?php foreach (array_slice($minibus['images'], 0, 3) as $image): ?>
                                                            <img src="../<?php echo htmlspecialchars($image); ?>" 
                                                                 alt="Minibus" 
                                                                 class="img-thumbnail" 
                                                                 style="width: 80px; height: 60px; object-fit: cover;">
                                                        <?php endforeach; ?>
                                                        <?php if (count($minibus['images']) > 3): ?>
                                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                                 style="width: 80px; height: 60px;">
                                                                +<?php echo count($minibus['images']) - 3; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                             style="width: 80px; height: 60px;">
                                                            No Image
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="fw-bold"><?php echo htmlspecialchars($minibus['name']); ?></div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-info">
                                                    <?php echo $minibus['capacity']; ?> passengers
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold">TZS <?php echo number_format($minibus['price_per_km']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $features = json_decode($minibus['features'] ?? '[]', true);
                                                if (!empty($features)): ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($features as $feature): ?>
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                                <?php echo htmlspecialchars($feature); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No features</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($minibus['driver_id'] && $minibus['driver_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-person-badge me-2 text-primary"></i>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($minibus['driver_name']); ?></div>
                                                            <small class="text-muted d-block">
                                                                License: <?php echo htmlspecialchars($minibus['license_number']); ?>
                                                            </small>
                                                            <small class="text-muted">
                                                                Phone: <?php echo htmlspecialchars($minibus['driver_phone']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">
                                                        <i class="bi bi-person-x me-1"></i>No driver assigned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-<?php 
                                                    echo $minibus['status'] == 'available' ? 'success' : 
                                                        ($minibus['status'] == 'booked' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <i class="bi bi-<?php 
                                                        echo $minibus['status'] == 'available' ? 'check-circle' : 
                                                            ($minibus['status'] == 'booked' ? 'calendar-check' : 'tools'); 
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($minibus['status']); ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group">
                                                    <a href="edit_minibus.php?id=<?php echo $minibus['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Minibus">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMinibus(<?php echo $minibus['id']; ?>)" title="Delete Minibus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Minibus Modal -->
    <div class="modal fade" id="addMinibusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Minibus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_minibus.php" method="POST" enctype="multipart/form-data" id="addMinibusForm">
                    <input type="hidden" name="form_submitted" value="1">
                    <div class="modal-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" max="35" required>
                            <div class="form-text">Maximum capacity is 35 passengers</div>
                        </div>
                        <div class="mb-3">
                            <label for="price_per_km" class="form-label">Price per 1km (TZS)</label>
                            <input type="number" class="form-control" id="price_per_km" name="price_per_km" required>
                            <div class="form-text">This is the price of minibus per 1km</div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <label for="exterior_image" class="form-label">Front View</label>
                                        <input type="file" class="form-control" id="exterior_image" name="images[]" accept="image/*">
                                        <div class="image-preview mt-2" id="exterior_preview"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <label for="interior_image" class="form-label">Inner View</label>
                                        <input type="file" class="form-control" id="interior_image" name="images[]" accept="image/*">
                                        <div class="image-preview mt-2" id="interior_preview"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <label for="additional_image" class="form-label">Back View</label>
                                        <input type="file" class="form-control" id="additional_image" name="images[]" accept="image/*">
                                        <div class="image-preview mt-2" id="additional_preview"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Minibus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Minibus Modal -->
    <div class="modal fade" id="editMinibusModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Minibus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="edit_minibus.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_price_per_km" class="form-label">Price per Kilometer (TZS)</label>
                                    <input type="number" class="form-control" id="edit_price_per_km" name="price_per_km" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_features" class="form-label">Features</label>
                                    <select class="form-select" id="edit_features" name="features[]" multiple>
                                        <option value="Air Conditioning">Air Conditioning</option>
                                        <option value="WiFi">WiFi</option>
                                        <option value="USB Ports">USB Ports</option>
                                        <option value="Entertainment System">Entertainment System</option>
                                        <option value="Luggage Space">Luggage Space</option>
                                        <option value="Comfortable Seats">Comfortable Seats</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_driver" class="form-label">Assign Driver</label>
                                    <select class="form-select" id="edit_driver" name="driver_id">
                                        <option value="">No Driver</option>
                                        <?php foreach ($available_drivers as $driver): ?>
                                        <option value="<?php echo $driver['id']; ?>">
                                            <?php echo htmlspecialchars($driver['name']); ?> 
                                            (<?php echo htmlspecialchars($driver['license_number']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <option value="available">Available</option>
                                        <option value="booked">Booked</option>
                                        <option value="maintenance">Maintenance</option>
                                    </select>
                                </div>
                            </div>
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
    <div class="modal fade" id="deleteMinibusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this minibus? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="minibus_id" id="delete_minibus_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_minibus" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMinibus(minibus) {
            document.getElementById('edit_id').value = minibus.id;
            document.getElementById('edit_name').value = minibus.name;
            document.getElementById('edit_capacity').value = minibus.capacity;
            document.getElementById('edit_price_per_km').value = minibus.price_per_km;
            document.getElementById('edit_driver').value = minibus.driver_id || '';
            document.getElementById('edit_status').value = minibus.status;

            // Handle features
            const features = JSON.parse(minibus.features || '[]');
            const featuresSelect = document.getElementById('edit_features');
            Array.from(featuresSelect.options).forEach(option => {
                option.selected = features.includes(option.value);
            });

            new bootstrap.Modal(document.getElementById('editMinibusModal')).show();
        }

        function deleteMinibus(id) {
            document.getElementById('delete_minibus_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteMinibusModal')).show();
        }

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

        document.getElementById('exterior_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('exterior_preview').innerHTML = `<img src="${e.target.result}" class="img-fluid">`;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('interior_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('interior_preview').innerHTML = `<img src="${e.target.result}" class="img-fluid">`;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('additional_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('additional_preview').innerHTML = `<img src="${e.target.result}" class="img-fluid">`;
                };
                reader.readAsDataURL(file);
            }
        });

        <?php if (isset($_SESSION['show_add_modal']) && $_SESSION['show_add_modal']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var addModal = new bootstrap.Modal(document.getElementById('addMinibusModal'));
            addModal.show();
        });
        <?php unset($_SESSION['show_add_modal']); endif; ?>
    </script>
</body>
</html> 