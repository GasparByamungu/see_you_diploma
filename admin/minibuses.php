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

// Note: Issue with duplicate minibus display has been fixed by using separate queries
// instead of complex JOINs that could cause duplication

// Get all minibuses first
$minibuses_stmt = $pdo->query("
    SELECT m.id, m.name, m.capacity, m.price_per_km, m.features,
           m.status, m.driver_id, m.created_at
    FROM minibuses m
    ORDER BY m.created_at DESC
");
$minibuses = $minibuses_stmt->fetchAll(PDO::FETCH_ASSOC);

// For each minibus, get driver and images separately to avoid JOIN issues
foreach ($minibuses as &$minibus) {
    // Get driver information
    if ($minibus['driver_id']) {
        $driver_stmt = $pdo->prepare("
            SELECT name as driver_name, license_number, phone as driver_phone
            FROM drivers WHERE id = ?
        ");
        $driver_stmt->execute([$minibus['driver_id']]);
        $driver = $driver_stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver) {
            $minibus['driver_name'] = $driver['driver_name'];
            $minibus['license_number'] = $driver['license_number'];
            $minibus['driver_phone'] = $driver['driver_phone'];
        } else {
            $minibus['driver_name'] = null;
            $minibus['license_number'] = null;
            $minibus['driver_phone'] = null;
        }
    } else {
        $minibus['driver_name'] = null;
        $minibus['license_number'] = null;
        $minibus['driver_phone'] = null;
    }

    // Get images
    $images_stmt = $pdo->prepare("
        SELECT image_path FROM minibus_images
        WHERE minibus_id = ?
        ORDER BY display_order
    ");
    $images_stmt->execute([$minibus['id']]);
    $images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);
    $minibus['images'] = implode(',', $images);
}

// Debug information (can be removed in production)
echo "<!-- Number of minibuses found: " . count($minibuses) . " -->\n";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Minibuses - Safari Minibus Rentals</title>
    <meta name="description" content="Admin panel for managing minibus fleet, adding new vehicles, and updating existing ones.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .image-preview {
            width: 100%;
            height: 150px;
            border: 2px dashed rgba(45, 80, 22, 0.3);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: var(--light-gray);
            transition: all var(--transition-normal);
        }

        .image-preview:hover {
            border-color: var(--primary-color);
            background-color: rgba(45, 80, 22, 0.05);
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: var(--radius-sm);
        }

        .image-preview.empty {
            color: var(--gray);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .minibus-table-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 2px solid var(--light-gray);
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-available { background-color: var(--success); }
        .status-booked { background-color: var(--warning); }
        .status-maintenance { background-color: var(--danger); }
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
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div class="mb-3 mb-md-0">
                        <h1 class="display-6 fw-bold text-primary mb-2">
                            <i class="bi bi-truck me-3"></i>Fleet Management
                        </h1>
                        <p class="text-muted mb-0">Manage your minibus fleet, add new vehicles, and update existing ones</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addMinibusModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Minibus
                    </button>
                </div>
            </div>
        </div>

        <!-- Fleet Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-truck display-6 mb-2"></i>
                        <h4><?php echo count($minibuses); ?></h4>
                        <small>Total Fleet</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($minibuses, fn($m) => $m['status'] === 'available')); ?></h4>
                        <small>Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($minibuses, fn($m) => $m['status'] === 'booked')); ?></h4>
                        <small>Booked</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-tools display-6 mb-2"></i>
                        <h4><?php echo count(array_filter($minibuses, fn($m) => $m['status'] === 'maintenance')); ?></h4>
                        <small>Maintenance</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Minibuses Table -->
        <div class="card border-0 shadow-custom">
            <div class="card-header bg-white border-0 py-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="mb-0 text-primary">
                        <i class="bi bi-list-ul me-2"></i>Minibus Fleet
                    </h4>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search minibuses..." id="searchInput">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($minibuses)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-truck display-1 text-muted mb-3"></i>
                    <h5 class="text-muted">No Minibuses Found</h5>
                    <p class="text-muted">Start building your fleet by adding your first minibus.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMinibusModal">
                        <i class="bi bi-plus-circle me-2"></i>Add First Minibus
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="minibusTable">
                        <thead>
                            <tr>
                                <th class="border-0 py-3">Vehicle</th>
                                <th class="border-0 py-3">Details</th>
                                <th class="border-0 py-3">Features</th>
                                <th class="border-0 py-3">Driver</th>
                                <th class="border-0 py-3">Status</th>
                                <th class="border-0 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($minibuses as $minibus): ?>
                            <tr class="minibus-row">
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <?php if (!empty($minibus['images'])): ?>
                                                <img src="../<?php echo htmlspecialchars($minibus['images'][0]); ?>"
                                                     alt="<?php echo htmlspecialchars($minibus['name']); ?>"
                                                     class="minibus-table-image">
                                            <?php else: ?>
                                                <div class="minibus-table-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-primary"><?php echo htmlspecialchars($minibus['name']); ?></div>
                                            <small class="text-muted">ID: #<?php echo str_pad($minibus['id'], 3, '0', STR_PAD_LEFT); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div>
                                        <div class="fw-semibold">
                                            <i class="bi bi-people me-1 text-primary"></i>
                                            <?php echo $minibus['capacity']; ?> Passengers
                                        </div>
                                        <div class="text-success fw-semibold">
                                            <i class="bi bi-currency-exchange me-1"></i>
                                            TZS <?php echo number_format($minibus['price_per_km']); ?>/km
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php
                                        $features = json_decode($minibus['features'] ?? '[]', true);
                                        $displayFeatures = array_slice($features, 0, 3);
                                        foreach ($displayFeatures as $feature): ?>
                                            <span class="badge bg-primary-light text-primary"><?php echo htmlspecialchars($feature); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($features) > 3): ?>
                                            <span class="badge bg-secondary">+<?php echo count($features) - 3; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <?php if ($minibus['driver_id'] && $minibus['driver_name']): ?>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($minibus['driver_name']); ?></div>
                                            <small class="text-muted">
                                                <i class="bi bi-card-text me-1"></i><?php echo htmlspecialchars($minibus['license_number']); ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="bi bi-person-x me-1"></i>Not assigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <span class="status-indicator status-<?php echo $minibus['status']; ?>"></span>
                                    <span class="badge bg-<?php
                                        echo $minibus['status'] == 'available' ? 'success' :
                                            ($minibus['status'] == 'booked' ? 'warning' : 'danger');
                                    ?> px-3 py-2">
                                        <?php echo ucfirst($minibus['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="editMinibus(<?php echo htmlspecialchars(json_encode($minibus)); ?>)"
                                                title="Edit Minibus">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteMinibus(<?php echo $minibus['id']; ?>)"
                                                title="Delete Minibus">
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

    <!-- Add Minibus Modal -->
    <div class="modal fade" id="addMinibusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Minibus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_minibus.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
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
                        <div class="mb-3">
                            <label class="form-label">Minibus Images</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label for="exterior_image" class="form-label">Exterior View</label>
                                            <input type="file" class="form-control" id="exterior_image" name="images[]" accept="image/*" required>
                                            <div class="image-preview mt-2" id="exterior_preview"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label for="interior_image" class="form-label">Interior View</label>
                                            <input type="file" class="form-control" id="interior_image" name="images[]" accept="image/*" required>
                                            <div class="image-preview mt-2" id="interior_preview"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <label for="back_image" class="form-label">Back View</label>
                                            <input type="file" class="form-control" id="back_image" name="images[]" accept="image/*" required>
                                            <div class="image-preview mt-2" id="back_preview"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Features</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="Android TV" id="feature_tv">
                                <label class="form-check-label" for="feature_tv">Android TV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="Music Sound System" id="feature_sound">
                                <label class="form-check-label" for="feature_sound">Music Sound System</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="Fridge" id="feature_fridge">
                                <label class="form-check-label" for="feature_fridge">Fridge</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="Luggage Space" id="feature_luggage">
                                <label class="form-check-label" for="feature_luggage">Luggage Space</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="features[]" value="Charging System" id="feature_charging">
                                <label class="form-check-label" for="feature_charging">Charging System</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="driver_id" class="form-label">Assign Driver</label>
                            <select class="form-select" id="driver_id" name="driver_id">
                                <option value="">Select a driver</option>
                                <?php foreach ($available_drivers as $driver): ?>
                                    <option value="<?php echo $driver['id']; ?>">
                                        <?php echo htmlspecialchars($driver['name'] . ' (' . $driver['license_number'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Minibus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="edit_minibus.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" max="35" required>
                            <div class="form-text">Maximum capacity is 35 passengers</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price_per_km" class="form-label">Price per 1km (TZS)</label>
                            <input type="number" class="form-control" id="edit_price_per_km" name="price_per_km" required>
                            <div class="form-text">This is the price per kilometer</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_id" class="form-label">Assign Driver</label>
                            <select class="form-select" id="edit_driver_id" name="driver_id">
                                <option value="">Select a driver</option>
                                <?php foreach ($available_drivers as $driver): ?>
                                    <option value="<?php echo $driver['id']; ?>">
                                        <?php echo htmlspecialchars($driver['name'] . ' (' . $driver['license_number'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Minibus Images</label>
                            <div class="d-grid gap-2">
                                <a href="edit_minibus.php?id=" id="change_pictures_link" class="btn btn-outline-primary">
                                    <i class="bi bi-images"></i> Change Pictures
                                </a>
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
                    Are you sure you want to delete this minibus?
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
        // Image preview functionality
        function setupImagePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            input.addEventListener('change', function() {
                preview.innerHTML = '';
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    preview.innerHTML = '<div class="empty">No image selected</div>';
                }
            });
        }

        // Setup previews for add modal
        setupImagePreview('exterior_image', 'exterior_preview');
        setupImagePreview('interior_image', 'interior_preview');
        setupImagePreview('back_image', 'back_preview');

        // Setup previews for edit modal
        setupImagePreview('edit_exterior_image', 'edit_exterior_preview');
        setupImagePreview('edit_interior_image', 'edit_interior_preview');
        setupImagePreview('edit_back_image', 'edit_back_preview');

        // Update edit modal to show current images
        function editMinibus(minibus) {
            document.getElementById('edit_id').value = minibus.id;
            document.getElementById('edit_name').value = minibus.name;
            document.getElementById('edit_capacity').value = minibus.capacity;
            document.getElementById('edit_price_per_km').value = minibus.price_per_km;
            document.getElementById('edit_status').value = minibus.status;
            document.getElementById('edit_driver_id').value = minibus.driver_id || '';
            
            // Update the change pictures link with the current minibus ID
            document.getElementById('change_pictures_link').href = 'edit_minibus.php?id=' + minibus.id;
            
            // Reset all checkboxes
            document.querySelectorAll('#editMinibusModal input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Set features checkboxes
            const features = JSON.parse(minibus.features || '[]');
            features.forEach(feature => {
                const checkbox = document.querySelector(`#editMinibusModal input[value="${feature}"]`);
                if (checkbox) checkbox.checked = true;
            });

            // Display current images
            const images = minibus.images || [];
            const previews = ['edit_exterior_preview', 'edit_interior_preview', 'edit_back_preview'];
            
            previews.forEach((previewId, index) => {
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.innerHTML = '';
                    if (images[index]) {
                        const img = document.createElement('img');
                        img.src = '../' + images[index];
                        preview.appendChild(img);
                    } else {
                        preview.innerHTML = '<div class="empty">No image</div>';
                    }
                }
            });
            
            new bootstrap.Modal(document.getElementById('editMinibusModal')).show();
        }

        function deleteMinibus(id) {
            document.getElementById('delete_minibus_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteMinibusModal')).show();
        }

        // Validate number of images
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files.length > 3) {
                    alert('Please select a maximum of 3 images');
                    this.value = '';
                }
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.minibus-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Add loading states to buttons
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

        // Auto-refresh page every 5 minutes
        setInterval(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html> 