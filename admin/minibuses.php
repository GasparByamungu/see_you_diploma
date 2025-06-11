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

// Diagnostic queries to check database state
echo "<!-- Database Diagnostic Information -->\n";

// Check for duplicate minibuses
$minibuses_check = $pdo->query("
    SELECT id, name, COUNT(*) as count 
    FROM minibuses 
    GROUP BY id, name 
    HAVING COUNT(*) > 1
")->fetchAll(PDO::FETCH_ASSOC);
echo "<!-- Duplicate minibuses found: " . count($minibuses_check) . " -->\n";

// Get all minibuses with their images and driver information
$stmt = $pdo->query("
    SELECT DISTINCT 
           m.id, m.name, m.capacity, m.price_per_km, m.features, 
           m.status, m.driver_id, m.created_at,
           d.name as driver_name, d.license_number, d.phone as driver_phone,
           GROUP_CONCAT(DISTINCT mi.image_path ORDER BY mi.display_order) as images
    FROM minibuses m 
    LEFT JOIN drivers d ON m.driver_id = d.id 
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    GROUP BY m.id, m.name, m.capacity, m.price_per_km, m.features, 
             m.status, m.driver_id, m.created_at,
             d.name, d.license_number, d.phone
    ORDER BY m.created_at DESC
");
$minibuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Print the query results
echo "<!-- Debug: Number of minibuses found: " . count($minibuses) . " -->\n";
foreach ($minibuses as $minibus) {
    echo "<!-- Debug: Minibus ID: " . $minibus['id'] . 
         ", Name: " . $minibus['name'] . 
         ", Driver ID: " . $minibus['driver_id'] . 
         ", Driver Name: " . ($minibus['driver_name'] ?? 'None') . 
         ", Images: " . ($minibus['images'] ?? 'None') . " -->\n";
}

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

// Debug: Print available drivers
echo "<!-- Debug: Number of available drivers: " . count($available_drivers) . " -->\n";
foreach ($available_drivers as $driver) {
    echo "<!-- Debug: Driver ID: " . $driver['id'] . 
         ", Name: " . $driver['name'] . 
         ", License: " . $driver['license_number'] . " -->\n";
}
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
        .image-preview {
            width: 100%;
            height: 150px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-preview.empty {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
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
                        <a class="nav-link active" href="minibuses.php">Minibuses</a>
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

    <!-- Main Content -->
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Minibuses</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMinibusModal">
                <i class="bi bi-plus-circle"></i> Add New Minibus
            </button>
        </div>

        <!-- Minibuses Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
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
                            <?php foreach ($minibuses as $minibus): ?>
                            <tr>
                                <td><?php echo $minibus['id']; ?></td>
                                <td>
                                    <div class="image-preview-container">
                                        <?php if (!empty($minibus['images'])): ?>
                                            <?php foreach ($minibus['images'] as $image): ?>
                                                <img src="../<?php echo htmlspecialchars($image); ?>" alt="Minibus" class="image-preview">
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="bg-secondary text-white" style="width: 100px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($minibus['name']); ?></td>
                                <td><?php echo $minibus['capacity']; ?> passengers</td>
                                <td><?php echo number_format($minibus['price_per_km']); ?></td>
                                <td>
                                    <?php 
                                    $features = json_decode($minibus['features'] ?? '[]', true);
                                    foreach ($features as $feature): ?>
                                        <span class="badge bg-info me-1"><?php echo htmlspecialchars($feature); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if ($minibus['driver_id'] && $minibus['driver_name']): ?>
                                        <?php echo htmlspecialchars($minibus['driver_name']); ?>
                                        <br>
                                        <small class="text-muted">
                                            License: <?php echo htmlspecialchars($minibus['license_number']); ?><br>
                                            Phone: <?php echo htmlspecialchars($minibus['driver_phone']); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">No driver assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $minibus['status'] == 'available' ? 'success' : 
                                            ($minibus['status'] == 'booked' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($minibus['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editMinibus(<?php echo htmlspecialchars(json_encode($minibus)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteMinibus(<?php echo $minibus['id']; ?>)">
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
    </script>
</body>
</html> 