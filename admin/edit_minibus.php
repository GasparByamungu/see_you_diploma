<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if minibus ID is provided
if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: minibuses.php");
    exit();
}

$minibus_id = isset($_POST['id']) ? (int)$_POST['id'] : (int)$_GET['id'];

// Get minibus details
$stmt = $pdo->prepare("
    SELECT m.*, 
           d.name as driver_name, 
           d.license_number, 
           d.phone as driver_phone
    FROM minibuses m 
    LEFT JOIN drivers d ON m.driver_id = d.id 
    WHERE m.id = ?
");
$stmt->execute([$minibus_id]);
$minibus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$minibus) {
    header("Location: minibuses.php");
    exit();
}

// Get all available drivers for dropdown
$stmt = $pdo->query("
    SELECT id, name, phone, license_number 
    FROM drivers 
    WHERE status = 'available' 
    OR id = " . ($minibus['driver_id'] ? $minibus['driver_id'] : 0) . "
    ORDER BY name
");
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction at the very beginning
        $pdo->beginTransaction();

        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['capacity']) || empty($_POST['price_per_km'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Process features
        $features = [];
        if (!empty($_POST['features'])) {
            if (is_array($_POST['features'])) {
                $features = $_POST['features'];
            } else {
                $features = explode(',', $_POST['features']);
            }
        }
        $features_json = json_encode($features);

        // Handle driver removal first
        $driver_id = null; // Initialize as null
        $current_driver_id = $minibus['driver_id'];

        error_log("POST data: " . print_r($_POST, true));
        error_log("Current driver ID: " . $current_driver_id);

        if (isset($_POST['remove_driver']) && $_POST['remove_driver'] == 'on') {
            // Get current driver details for logging
            $stmt = $pdo->prepare("SELECT name, license_number FROM drivers WHERE id = ?");
            $stmt->execute([$current_driver_id]);
            $current_driver = $stmt->fetch(PDO::FETCH_ASSOC);

            // Set driver back to available
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?");
            $stmt->execute([$current_driver_id]);

            // Set minibus driver_id to null
            $stmt = $pdo->prepare("UPDATE minibuses SET driver_id = NULL WHERE id = ?");
            $stmt->execute([$minibus_id]);

            // Log the removal
            error_log(sprintf(
                "Driver removed from minibus - Driver ID: %d, Name: %s, License: %s, Minibus ID: %d",
                $current_driver_id,
                $current_driver['name'] ?? 'Unknown',
                $current_driver['license_number'] ?? 'Unknown',
                $minibus_id
            ));

            $_SESSION['success_message'] = "Driver has been successfully removed from the minibus.";
        } else {
            // If not removing driver, get the selected driver_id
            $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
            error_log("New driver ID: " . $driver_id);

            // Check if trying to assign a new driver when there's already an assigned driver
            if ($driver_id && $minibus['driver_id'] && $driver_id != $minibus['driver_id']) {
                throw new Exception("Cannot assign a new driver. This minibus already has an assigned driver. Please remove the current driver first before assigning a new one.");
            }
        }

        // Update minibus details
        $update_sql = "
            UPDATE minibuses 
            SET name = :name,
                capacity = :capacity,
                price_per_km = :price_per_km,
                features = :features,
                driver_id = :driver_id,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $update_params = [
            'name' => $_POST['name'],
            'capacity' => $_POST['capacity'],
            'price_per_km' => $_POST['price_per_km'],
            'features' => $features_json,
            'driver_id' => $driver_id,
            'status' => $_POST['status'],
            'id' => $minibus_id
        ];

        error_log("Update SQL: " . $update_sql);
        error_log("Update parameters: " . print_r($update_params, true));

        $stmt = $pdo->prepare($update_sql);
        $stmt->execute($update_params);

        error_log("Minibus updated with ID: " . $minibus_id . " and features: " . $features_json);

        // Handle driver status changes only if we're assigning a new driver
        if ($driver_id && !isset($_POST['remove_driver'])) {
            // If there was a previous driver, set them back to available
            if ($current_driver_id && $current_driver_id != $driver_id) {
                $stmt = $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?");
                $stmt->execute([$current_driver_id]);
                error_log("Previous driver ID {$current_driver_id} set to available");
            }

            // Set new driver to assigned
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'assigned' WHERE id = ?");
            $stmt->execute([$driver_id]);
            error_log("New driver ID {$driver_id} set to assigned");

            // Add success message for driver assignment
            $_SESSION['success_message'] = "Driver has been successfully assigned to the minibus.";
        }

        // Add success message for general update
        if (!isset($_SESSION['success_message'])) {
            $_SESSION['success_message'] = "Minibus details have been successfully updated.";
        }

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/minibuses/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Get current display order
            $stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM minibus_images WHERE minibus_id = ?");
            $stmt->execute([$minibus_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $display_order = $result['max_order'] ? $result['max_order'] + 1 : 0;

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $new_file_name = uniqid('minibus_') . '.' . $file_ext;
                    $file_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO minibus_images (minibus_id, image_path, display_order)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$minibus_id, 'uploads/minibuses/' . $new_file_name, $display_order + $key]);
                        error_log("New image uploaded for minibus ID: " . $minibus_id);
                    }
                }
            }
        }

        // Handle image deletions
        if (!empty($_POST['delete_images'])) {
            // Get the image paths before deletion
            $stmt = $pdo->prepare("SELECT image_path FROM minibus_images WHERE id IN (" . implode(',', array_fill(0, count($_POST['delete_images']), '?')) . ")");
            $stmt->execute($_POST['delete_images']);
            $images_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM minibus_images WHERE id IN (" . implode(',', array_fill(0, count($_POST['delete_images']), '?')) . ")");
            $stmt->execute($_POST['delete_images']);

            // Delete physical files
            foreach ($images_to_delete as $image_path) {
                $full_path = '../' . $image_path;
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
            error_log("Deleted " . count($_POST['delete_images']) . " images for minibus ID: " . $minibus_id);
        }

        $pdo->commit();
        error_log("Minibus update successful for ID: " . $minibus_id);
        header("Location: minibuses.php");
        exit();

    } catch (Exception $e) {
        // Check if we're in a transaction before trying to rollback
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error updating minibus: " . $e->getMessage());
        $error_message = "Error updating minibus: " . $e->getMessage();
        
        // Store the error message in session for display
        $_SESSION['error_message'] = $error_message;
        header("Location: edit_minibus.php?id=" . $minibus_id);
        exit();
    }
}

// Get current images
$stmt = $pdo->prepare("SELECT * FROM minibus_images WHERE minibus_id = ? ORDER BY display_order");
$stmt->execute([$minibus_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Minibus - Safari Minibus Rentals</title>
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

    <!-- Edit Minibus Form -->
    <div class="container my-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Edit Minibus</h2>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Display error message if any -->
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($minibus['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacity (passengers)</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo htmlspecialchars($minibus['capacity']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="price_per_km" class="form-label">Price per 1km (TZS)</label>
                                <input type="number" class="form-control" id="price_per_km" name="price_per_km" value="<?php echo htmlspecialchars($minibus['price_per_km']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available" <?php echo $minibus['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="maintenance" <?php echo $minibus['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="unavailable" <?php echo $minibus['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                </select>
                            </div>

                            <!-- Driver Selection -->
                            <div class="mb-3">
                                <?php if ($minibus['driver_id']): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="remove_driver" name="remove_driver">
                                    <label class="form-check-label" for="remove_driver">
                                        Remove current driver
                                    </label>
                                </div>
                                <?php endif; ?>
                                <label for="edit_driver_id" class="form-label">Assign Driver</label>
                                <select class="form-select" id="edit_driver_id" name="driver_id" <?php echo ($minibus['driver_id'] && !isset($_POST['remove_driver'])) ? 'disabled' : ''; ?>>
                                    <option value="">Select a driver</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option value="<?php echo $driver['id']; ?>" <?php echo ($minibus['driver_id'] == $driver['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($driver['name'] . ' (' . $driver['license_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current Images</label>
                                <div class="row">
                                    <?php foreach ($images as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" class="card-img-top" alt="Minibus Image">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" id="delete_<?php echo $image['id']; ?>">
                                                    <label class="form-check-label" for="delete_<?php echo $image['id']; ?>">
                                                        Delete
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if (count($images) < 3): ?>
                            <div class="mb-3">
                                <label for="images" class="form-label">Add New Images</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                                <small class="text-muted">You can select multiple images. Maximum file size: 5MB. Allowed formats: JPG, JPEG, PNG. Maximum 3 images total.</small>
                                <small class="text-muted d-block">You can add <?php echo 3 - count($images); ?> more image(s).</small>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Maximum number of images (3) reached. Delete some images to add new ones.
                            </div>
                            <?php endif; ?>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Minibus</button>
                                <a href="minibuses.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const removeDriverCheckbox = document.getElementById('remove_driver');
            const driverSelect = document.getElementById('edit_driver_id');
            
            if (removeDriverCheckbox) {
                removeDriverCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // When checked, enable the dropdown and clear its value
                        driverSelect.disabled = false;
                        driverSelect.value = '';
                    } else {
                        // When unchecked, disable the dropdown and restore original value
                        driverSelect.disabled = true;
                        driverSelect.value = '<?php echo $minibus['driver_id']; ?>';
                    }
                });
            }

            // Add event listener for driver selection
            if (driverSelect) {
                driverSelect.addEventListener('change', function() {
                    // Only enable the dropdown if a driver is selected
                    if (this.value) {
                        driverSelect.disabled = false;
                    }
                });
            }
        });
    </script>
</body>
</html> 